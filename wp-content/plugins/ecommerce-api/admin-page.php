<?php
/**
 * Admin Page for Ecommerce Master API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ecommerce_API_Admin_Page {
    
    private $namespace;
    private $version;
    
    public function __construct($namespace, $version) {
        $this->namespace = $namespace;
        $this->version = $version;
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_ema_health_check', array($this, 'ajax_health_check'));
        add_action('wp_ajax_ema_clear_cache', array($this, 'ajax_clear_cache'));
        add_action('wp_ajax_ema_test_endpoint', array($this, 'ajax_test_endpoint'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Ecommerce API Manager',
            'API Manager',
            'manage_options',
            'ecommerce-api-manager',
            array($this, 'admin_page'),
            'dashicons-rest-api',
            30
        );
        
        add_submenu_page(
            'ecommerce-api-manager',
            'Dashboard - Ecommerce API Manager',
            'Dashboard',
            'manage_options',
            'ecommerce-api-manager',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'ecommerce-api-manager',
            'Compatibility Status - Ecommerce API Manager',
            'Compatibility',
            'manage_options',
            'ecommerce-api-manager-compatibility',
            array($this, 'compatibility_page')
        );

        add_submenu_page(
            'ecommerce-api-manager',
            'Documentation - Ecommerce API Manager',
            'Documentation',
            'manage_options',
            'ecommerce-api-manager-documentation',
            array($this, 'documentation_page')
        );
    }
    
    public function register_settings() {
        register_setting('ema_settings', 'ema_enable_hpos');
        register_setting('ema_settings', 'ema_enable_blocks');
        register_setting('ema_settings', 'ema_api_rate_limit');
        register_setting('ema_settings', 'ema_enable_debug');
        register_setting('ema_settings', 'ema_enable_caching');
        register_setting('ema_settings', 'ema_cache_ttl');
        register_setting('ema_settings', 'ema_token_expiry');
        register_setting('ema_settings', 'ema_max_tokens');
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ecommerce-api-manager') !== false) {
            wp_enqueue_style(
                'ecommerce-api-admin-css',
                EMA_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                EMA_VERSION
            );
            
            wp_enqueue_script(
                'ecommerce-api-admin-js',
                EMA_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                EMA_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('ecommerce-api-admin-js', 'ema_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'api_url' => get_rest_url() . $this->namespace,
                'nonce' => wp_create_nonce('ema_admin_nonce'),
                'changelog_url' => EMA_PLUGIN_URL . 'documentation/changelog.md'
            ));
        }
    }
    
    public function admin_page() {
        $settings = $this->get_settings();
        ?>
        <div class="wrap ecommerce-api-wrap">
            <h1 class="wp-heading">Ecommerce API Manager</h1>

            <?php if (isset($_GET['settings-updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Settings have been saved successfully.</strong></p>
                </div>
            <?php endif; ?>

            <div class="notice notice-info">
                <p><strong>API Status:</strong> Active and running (v<?php echo esc_html($this->version); ?>)</p>
            </div>

            <div class="nav-tab-wrapper">
                <a class="nav-tab nav-tab-active" data-tab="dashboard">Dashboard</a>
                <a class="nav-tab" data-tab="settings">API Settings</a>
                <a class="nav-tab" data-tab="endpoints">Endpoints</a>
                <a class="nav-tab" data-tab="logs">Activity Logs</a>
                <a class="nav-tab" data-tab="documentation">Documentation</a>
            </div>

            <!-- Dashboard Tab -->
            <div id="dashboard" class="tab-content active">
                <?php $this->render_dashboard_tab(); ?>
            </div>

            <!-- Settings Tab -->
            <div id="settings" class="tab-content">
                <?php $this->render_settings_tab($settings); ?>
            </div>

            <!-- Endpoints Tab -->
            <div id="endpoints" class="tab-content">
                <?php $this->render_endpoints_tab(); ?>
            </div>

            <!-- Logs Tab -->
            <div id="logs" class="tab-content">
                <?php $this->render_logs_tab(); ?>
            </div>

            <!-- Documentation Tab -->
            <div id="documentation" class="tab-content">
                <?php $this->render_documentation_tab(); ?>
            </div>
        </div>
        <?php
    }
    
    private function render_dashboard_tab() {
        ?>
        <div class="stats-grid">
            <div class="stat-box">
                <h3>Total API Requests</h3>
                <div class="stat-value"><?php echo number_format($this->get_total_requests()); ?></div>
                <div class="stat-change positive">↑ 12.5% from last month</div>
            </div>
            <div class="stat-box">
                <h3>Active Users</h3>
                <div class="stat-value"><?php echo number_format($this->get_active_users()); ?></div>
                <div class="stat-change positive">↑ 8.3% from last month</div>
            </div>
            <div class="stat-box">
                <h3>Orders Today</h3>
                <div class="stat-value"><?php echo number_format($this->get_today_orders()); ?></div>
                <div class="stat-change positive">↑ 5.2% from yesterday</div>
            </div>
            <div class="stat-box">
                <h3>API Response Time</h3>
                <div class="stat-value"><?php echo $this->get_avg_response_time(); ?>ms</div>
                <div class="stat-change negative">↑ 3ms from last week</div>
            </div>
        </div>

        <div class="postbox">
            <div class="postbox-header">
                <h2>Recent API Activity</h2>
            </div>
            <div class="inside">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Endpoint</th>
                            <th>Method</th>
                            <th>User</th>
                            <th>Status</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $this->display_recent_activity(); ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    private function render_settings_tab($settings) {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('ema_settings'); ?>
            
            <div class="postbox">
                <div class="postbox-header">
                    <h2>General Settings</h2>
                </div>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th><label for="api_version">API Version</label></th>
                            <td>
                                <input type="text" id="api_version" value="<?php echo esc_attr($this->version); ?>" readonly>
                                <p class="description">Current API version (read-only)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="base_url">Base URL</label></th>
                            <td>
                                <input type="text" id="base_url" value="<?php echo esc_attr(get_rest_url() . $this->namespace); ?>" readonly>
                                <p class="description">API base endpoint URL</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ema_token_expiry">Token Expiry</label></th>
                            <td>
                                <select id="ema_token_expiry" name="ema_token_expiry">
                                    <option value="7" <?php selected($settings['token_expiry'], 7); ?>>7 days</option>
                                    <option value="15" <?php selected($settings['token_expiry'], 15); ?>>15 days</option>
                                    <option value="30" <?php selected($settings['token_expiry'], 30); ?>>30 days</option>
                                    <option value="60" <?php selected($settings['token_expiry'], 60); ?>>60 days</option>
                                    <option value="90" <?php selected($settings['token_expiry'], 90); ?>>90 days</option>
                                </select>
                                <p class="description">Session token expiration time</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ema_max_tokens">Max Active Tokens</label></th>
                            <td>
                                <input type="number" id="ema_max_tokens" name="ema_max_tokens" value="<?php echo esc_attr($settings['max_tokens']); ?>" min="1" max="10">
                                <p class="description">Maximum active tokens per user</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ema_api_rate_limit">Rate Limit</label></th>
                            <td>
                                <input type="number" id="ema_api_rate_limit" name="ema_api_rate_limit" value="<?php echo esc_attr($settings['rate_limit']); ?>" min="10" max="1000">
                                <p class="description">Requests per hour per user</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="postbox">
                <div class="postbox-header">
                    <h2>Cache Settings</h2>
                </div>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th><label for="ema_enable_caching">Enable Caching</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="ema_enable_caching" name="ema_enable_caching" value="1" <?php checked($settings['enable_caching'], 1); ?>>
                                    Enable API response caching
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ema_cache_ttl">Cache Duration</label></th>
                            <td>
                                <select id="ema_cache_ttl" name="ema_cache_ttl">
                                    <option value="300" <?php selected($settings['cache_ttl'], 300); ?>>5 minutes</option>
                                    <option value="900" <?php selected($settings['cache_ttl'], 900); ?>>15 minutes</option>
                                    <option value="1800" <?php selected($settings['cache_ttl'], 1800); ?>>30 minutes</option>
                                    <option value="3600" <?php selected($settings['cache_ttl'], 3600); ?>>1 hour</option>
                                    <option value="7200" <?php selected($settings['cache_ttl'], 7200); ?>>2 hours</option>
                                </select>
                                <p class="description">Default cache duration for product data</p>
                            </td>
                        </tr>
                    </table>
                    <div class="actions">
                        <button type="button" class="button button-secondary ema-clear-cache" data-cache-type="all">Clear All Cache</button>
                        <button type="button" class="button button-secondary ema-clear-cache" data-cache-type="products">Clear Product Cache</button>
                        <button type="button" class="button button-secondary ema-clear-cache" data-cache-type="orders">Clear Order Cache</button>
                    </div>
                </div>
            </div>

            <div class="postbox">
                <div class="postbox-header">
                    <h2>Advanced Settings</h2>
                </div>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th><label for="ema_enable_hpos">Enable HPOS Support</label></th>
                            <td>
                                <input type="checkbox" id="ema_enable_hpos" name="ema_enable_hpos" value="1" <?php checked($settings['enable_hpos'], 1); ?>>
                                <p class="description">High-Performance Order Storage compatibility</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ema_enable_blocks">Enable Block Support</label></th>
                            <td>
                                <input type="checkbox" id="ema_enable_blocks" name="ema_enable_blocks" value="1" <?php checked($settings['enable_blocks'], 1); ?>>
                                <p class="description">Block-based cart and checkout compatibility</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ema_enable_debug">Enable Debug Mode</label></th>
                            <td>
                                <input type="checkbox" id="ema_enable_debug" name="ema_enable_debug" value="1" <?php checked($settings['enable_debug'], 0); ?>>
                                <p class="description">Log API requests and errors</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="actions">
                <button type="submit" class="button button-primary">Save Changes</button>
                <button type="button" class="button button-secondary" id="reset-defaults">Reset to Defaults</button>
            </div>
        </form>
        <?php
    }
    
    private function render_endpoints_tab() {
        ?>
        <div class="postbox">
            <div class="postbox-header">
                <h2>Available API Endpoints</h2>
            </div>
            <div class="inside">
                <h3 style="margin-top: 0;">Authentication Endpoints</h3>
                <div class="endpoint-card">
                    <h4><span class="endpoint-method method-post">POST</span> /auth/login</h4>
                    <p>Authenticate user and get session token</p>
                    <div class="code-snippet">{"username": "user@example.com", "password": "password"}</div>
                </div>
                <div class="endpoint-card">
                    <h4><span class="endpoint-method method-post">POST</span> /auth/register</h4>
                    <p>Register new user account</p>
                </div>
                <div class="endpoint-card">
                    <h4><span class="endpoint-method method-get">GET</span> /auth/profile</h4>
                    <p>Get current user profile (Requires authentication)</p>
                </div>

                <h3>Product Endpoints</h3>
                <div class="endpoint-card">
                    <h4><span class="endpoint-method method-get">GET</span> /products</h4>
                    <p>Get paginated products list with filters</p>
                    <div class="code-snippet">Query: ?page=1&per_page=12&category=electronics&min_price=10</div>
                </div>
                <div class="endpoint-card">
                    <h4><span class="endpoint-method method-get">GET</span> /products/{id}</h4>
                    <p>Get single product details</p>
                </div>
                <div class="endpoint-card">
                    <h4><span class="endpoint-method method-put">PUT</span> /products/update/{id}</h4>
                    <p>Update product (Requires authentication)</p>
                </div>

                <h3>Cart Endpoints</h3>
                <div class="endpoint-card">
                    <h4><span class="endpoint-method method-get">GET</span> /cart</h4>
                    <p>Get current user's cart (Requires authentication)</p>
                </div>
                <div class="endpoint-card">
                    <h4><span class="endpoint-method method-post">POST</span> /cart/add</h4>
                    <p>Add product to cart (Requires authentication)</p>
                </div>
                <div class="endpoint-card">
                    <h4><span class="endpoint-method method-delete">DELETE</span> /cart/remove</h4>
                    <p>Remove item from cart (Requires authentication)</p>
                </div>

                <h3>Order Endpoints</h3>
                <div class="endpoint-card">
                    <h4><span class="endpoint-method method-post">POST</span> /orders/create</h4>
                    <p>Create new order from cart (Requires authentication)</p>
                </div>
                <div class="endpoint-card">
                    <h4><span class="endpoint-method method-get">GET</span> /orders</h4>
                    <p>Get user's orders with pagination (Requires authentication)</p>
                </div>
                <div class="endpoint-card">
                    <h4><span class="endpoint-method method-get">GET</span> /orders/{id}</h4>
                    <p>Get single order details (Requires authentication)</p>
                </div>

                <h3>Additional Endpoints</h3>
                <div class="endpoint-card">
                    <h4><span class="endpoint-method method-get">GET</span> /addresses</h4>
                    <p>Get user addresses (Requires authentication)</p>
                </div>
                <div class="endpoint-card">
                    <h4><span class="endpoint-method method-put">PUT</span> /addresses/update</h4>
                    <p>Update user address (Requires authentication)</p>
                </div>
                <div class="endpoint-card">
                    <h4><span class="endpoint-method method-post">POST</span> /reviews/add</h4>
                    <p>Add product review (Requires authentication)</p>
                </div>
                <div class="endpoint-card">
                    <h4><span class="endpoint-method method-get">GET</span> /wishlist</h4>
                    <p>Get user wishlist (Requires authentication)</p>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_logs_tab() {
        ?>
        <div class="postbox">
            <div class="postbox-header">
                <h2>API Activity Logs</h2>
            </div>
            <div class="inside">
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <select name="log_filter" id="log-filter">
                            <option value="all">All Logs</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                        </select>
                        <button type="button" class="button" id="apply-log-filter">Filter</button>
                    </div>
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo $this->get_log_count(); ?> items</span>
                    </div>
                </div>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Endpoint</th>
                            <th>Method</th>
                            <th>User</th>
                            <th>IP Address</th>
                            <th>Status</th>
                            <th>Response Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $this->display_activity_logs(); ?>
                    </tbody>
                </table>
                <div class="tablenav bottom">
                    <div class="alignleft actions">
                        <button class="button button-secondary" id="export-logs">Export Logs</button>
                        <button class="button button-secondary" id="clear-old-logs">Clear Old Logs</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_documentation_tab() {
        ?>
        <div class="postbox">
            <div class="postbox-header">
                <h2>API Documentation</h2>
            </div>
            <div class="inside">
                <h3>Getting Started</h3>
                <p>The Ecommerce Master API provides comprehensive ecommerce functionality including authentication, products, cart, orders, reviews, wishlist, and address management.</p>
                
                <h3>Base URL</h3>
                <div class="code-snippet"><?php echo esc_html(get_rest_url() . $this->namespace); ?></div>

                <h3>Authentication</h3>
                <p>All authenticated endpoints require Bearer token authentication. Include the token in the Authorization header:</p>
                <div class="code-snippet">Authorization: Bearer {session_token}</div>

                <h3>Response Format</h3>
                <p>All API responses follow a consistent JSON format:</p>
                <div class="code-snippet">{
  "success": true,
  "message": "Success",
  "data": {...}
}</div>

                <h3>Error Handling</h3>
                <p>Error responses include appropriate HTTP status codes and error messages:</p>
                <div class="code-snippet">{
  "success": false,
  "message": "Error description",
  "data": null
}</div>

                <h3>Rate Limiting</h3>
                <p>The API implements rate limiting to ensure fair usage:</p>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Wishlist endpoints: 10 requests per minute per user</li>
                    <li>Review endpoints: 10 requests per minute per user</li>
                    <li>Address endpoints: 20 requests per minute per user</li>
                </ul>

                <div class="actions">
                    <button class="button button-primary" id="download-docs">Download Full Documentation</button>
                    <button class="button button-secondary" id="view-changelog">View API Changelog</button>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function compatibility_page() {
        $compatibility = new Compatibility();
        $status = $compatibility->get_detailed_status();
        ?>
        <div class="wrap ecommerce-api-wrap">
            <h1 class="wp-heading">Ecommerce API Manager - Compatibility Status</h1>
            
            <?php if (empty($status)): ?>
                <div class="notice notice-warning">
                    <p>Compatibility status not available. Please check if the Compatibility class is properly loaded.</p>
                </div>
            <?php else: ?>
                <?php foreach ($status as $section => $items): ?>
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php echo esc_html(ucfirst(str_replace('_', ' ', $section))); ?></h2>
                    </div>
                    <div class="inside">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Feature</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($item['name']); ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?php echo esc_attr($item['status'] === 'OK' ? 'success' : ($item['status'] === 'Warning' ? 'warning' : 'error')); ?>">
                                            <?php echo esc_html($item['status_icon'] . ' ' . $item['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($item['details']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function documentation_page() {
        ?>
        <div class="wrap ecommerce-api-wrap">
            <h1 class="wp-heading">Ecommerce API Manager - Documentation</h1>
            <div class="postbox">
                <div class="postbox-header">
                    <h2>API Documentation</h2>
                </div>
                <div class="inside">
                    <p>Complete API documentation is available in the documentation.md file included with the plugin.</p>
                    <p><strong>Base URL:</strong> <code><?php echo esc_html(get_rest_url() . $this->namespace); ?></code></p>
                    <p>For detailed endpoint documentation, request/response examples, and authentication details, please refer to the documentation.md file in the plugin directory.</p>
                    
                    <div class="actions">
                        <button class="button button-primary" id="download-docs">Download Full Documentation</button>
                        <button class="button button-secondary" id="view-changelog">View API Changelog</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    // Helper methods
    private function get_settings() {
        $defaults = array(
            'api_version' => $this->version,
            'base_url' => get_rest_url() . $this->namespace,
            'token_expiry' => get_option('ema_token_expiry', 30),
            'max_tokens' => get_option('ema_max_tokens', 5),
            'rate_limit' => get_option('ema_api_rate_limit', 1000),
            'enable_caching' => get_option('ema_enable_caching', 1),
            'cache_ttl' => get_option('ema_cache_ttl', 3600),
            'enable_hpos' => get_option('ema_enable_hpos', 1),
            'enable_blocks' => get_option('ema_enable_blocks', 1),
            'enable_debug' => get_option('ema_enable_debug', 0)
        );
        
        return $defaults;
    }
    
    private function get_total_requests() {
        // This would typically query your database
        return 45283;
    }
    
    private function get_active_users() {
        // This would typically query your database
        return 1247;
    }
    
    private function get_today_orders() {
        // This would typically query your database
        return 156;
    }
    
    private function get_avg_response_time() {
        // This would typically query your database
        return 124;
    }
    
    private function get_log_count() {
        // This would typically query your database
        return 150;
    }
    
    private function display_recent_activity() {
        // This would typically query your database
        $activities = array(
            array('endpoint' => '/auth/login', 'method' => 'POST', 'user' => 'user@example.com', 'status' => 200, 'time' => '2 minutes ago'),
            array('endpoint' => '/products', 'method' => 'GET', 'user' => 'john.doe@example.com', 'status' => 200, 'time' => '5 minutes ago'),
            array('endpoint' => '/cart/add', 'method' => 'POST', 'user' => 'jane.smith@example.com', 'status' => 200, 'time' => '8 minutes ago'),
            array('endpoint' => '/orders/create', 'method' => 'POST', 'user' => 'mike.wilson@example.com', 'status' => 400, 'time' => '12 minutes ago'),
            array('endpoint' => '/wishlist', 'method' => 'GET', 'user' => 'sarah.jones@example.com', 'status' => 200, 'time' => '15 minutes ago'),
        );
        
        foreach ($activities as $activity) {
            $method_class = 'method-' . strtolower($activity['method']);
            $status_class = $activity['status'] == 200 ? 'badge-success' : 'badge-error';
            
            echo '<tr>';
            echo '<td>' . esc_html($activity['endpoint']) . '</td>';
            echo '<td><span class="endpoint-method ' . esc_attr($method_class) . '">' . esc_html($activity['method']) . '</span></td>';
            echo '<td>' . esc_html($activity['user']) . '</td>';
            echo '<td><span class="badge ' . esc_attr($status_class) . '">' . esc_html($activity['status']) . '</span></td>';
            echo '<td>' . esc_html($activity['time']) . '</td>';
            echo '</tr>';
        }
    }
    
    private function display_activity_logs() {
        // This would typically query your database
        $logs = array(
            array('timestamp' => '2024-01-15 14:32:45', 'endpoint' => '/auth/login', 'method' => 'POST', 'user' => 'user@example.com', 'ip' => '192.168.1.100', 'status' => 200, 'response_time' => '145ms'),
            array('timestamp' => '2024-01-15 14:30:22', 'endpoint' => '/products', 'method' => 'GET', 'user' => 'john.doe@example.com', 'ip' => '192.168.1.105', 'status' => 200, 'response_time' => '98ms'),
            array('timestamp' => '2024-01-15 14:28:10', 'endpoint' => '/cart/add', 'method' => 'POST', 'user' => 'jane.smith@example.com', 'ip' => '192.168.1.110', 'status' => 200, 'response_time' => '112ms'),
            array('timestamp' => '2024-01-15 14:25:33', 'endpoint' => '/orders/create', 'method' => 'POST', 'user' => 'mike.wilson@example.com', 'ip' => '192.168.1.115', 'status' => 400, 'response_time' => '67ms'),
            array('timestamp' => '2024-01-15 14:22:18', 'endpoint' => '/wishlist', 'method' => 'GET', 'user' => 'sarah.jones@example.com', 'ip' => '192.168.1.120', 'status' => 200, 'response_time' => '89ms'),
        );
        
        foreach ($logs as $log) {
            $method_class = 'method-' . strtolower($log['method']);
            $status_class = $log['status'] == 200 ? 'badge-success' : 'badge-error';
            
            echo '<tr>';
            echo '<td>' . esc_html($log['timestamp']) . '</td>';
            echo '<td>' . esc_html($log['endpoint']) . '</td>';
            echo '<td><span class="endpoint-method ' . esc_attr($method_class) . '">' . esc_html($log['method']) . '</span></td>';
            echo '<td>' . esc_html($log['user']) . '</td>';
            echo '<td>' . esc_html($log['ip']) . '</td>';
            echo '<td><span class="badge ' . esc_attr($status_class) . '">' . esc_html($log['status']) . '</span></td>';
            echo '<td>' . esc_html($log['response_time']) . '</td>';
            echo '</tr>';
        }
    }
    
    // AJAX Handlers
    public function ajax_health_check() {
        check_ajax_referer('ema_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $compatibility = new Compatibility();
        $health = $compatibility->run_health_check();
        
        wp_send_json_success(array(
            'message' => $health['message'],
            'details' => $health
        ));
    }
    
    public function ajax_clear_cache() {
        check_ajax_referer('ema_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $cache_type = $_POST['cache_type'] ?? 'all';
        $cache_manager = new Cache_Manager();
        
        switch ($cache_type) {
            case 'products':
                $cache_manager->clear_product_cache();
                break;
            case 'orders':
                $cache_manager->clear_order_cache();
                break;
            case 'reviews':
                $cache_manager->clear_review_cache();
                break;
            case 'all':
            default:
                $cache_manager->clear_all();
                break;
        }
        
        wp_send_json_success('Cache cleared successfully');
    }
    
    public function ajax_test_endpoint() {
        check_ajax_referer('ema_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $endpoint = $_POST['endpoint'] ?? '/system/health';
        $method = $_POST['method'] ?? 'GET';
        
        $url = get_rest_url() . $this->namespace . $endpoint;
        
        $response = wp_remote_request($url, array(
            'method' => $method,
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Error testing endpoint: ' . $response->get_error_message());
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            wp_send_json_success(array(
                'status_code' => wp_remote_retrieve_response_code($response),
                'response' => $data
            ));
        }
    }
}