<?php
/**
 * Plugin Name: Ecommerce Master API
 * Description: Complete WooCommerce REST API enhancement with custom endpoints, webhook support, batch operations, and performance optimizations for headless e-commerce and mobile applications.
 * Version: 2.1.0
 * Author: Md. Abdullah Al Ahsan
 * License: GPL v2 or later
 * Text Domain: ecommerce-master-api
 * Domain Path: /languages
 * WC requires at least: 5.0.0
 * WC tested up to: 8.0.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'ema_woocommerce_missing_notice');
    return;
}

function ema_woocommerce_missing_notice() {
    echo '<div class="error"><p>Ecommerce Master API requires WooCommerce to be installed and active.</p></div>';
}

// Define constants
define('EMA_VERSION', '2.1.0');
define('EMA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EMA_PLUGIN_PATH', plugin_dir_path(__FILE__));

class Ecommerce_Master_API {
    
    private $namespace = 'ecommerce-api/v1';
    private $version = EMA_VERSION;
    
    public function __construct() {
        add_action('init', array($this, 'check_compatibility'));
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'show_admin_notices'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // HPOS Compatibility
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
        
        // Block compatibility
        add_filter('woocommerce_feature_product_block_editor_enabled', array($this, 'handle_block_compatibility'));
        
        // Load required files
        $this->load_dependencies();
        
        // Initialize session handling
        $this->init_session_management();
        
        // AJAX handlers
        add_action('wp_ajax_ema_health_check', array($this, 'ajax_health_check'));
        add_action('wp_ajax_ema_clear_cache', array($this, 'ajax_clear_cache'));
    }

    public function init_session_management() {
        // Only start session for non-REST API requests
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return; // Don't start sessions for REST API
        }
        
        if (!session_id() && !headers_sent()) {
            session_start();
        }
        
        // Close session early for REST API and performance
        add_action('rest_api_init', function() {
            if (session_id()) {
                session_write_close();
            }
        });
        
        // Close session before any HTTP requests
        add_action('http_api_curl', function() {
            if (session_id()) {
                session_write_close();
            }
        });
        
        // Close session before WordPress makes internal requests
        add_action('wp_loaded', function() {
            if (defined('REST_REQUEST') && REST_REQUEST && session_id()) {
                session_write_close();
            }
        });
    }

    public function enqueue_scripts() {
        // Frontend scripts if needed
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ecommerce-master-api') !== false) {
            wp_enqueue_script('ema-admin-js', EMA_PLUGIN_URL . 'assets/admin.js', array('jquery'), EMA_VERSION, true);
            wp_enqueue_style('ema-admin-css', EMA_PLUGIN_URL . 'assets/admin.css', array(), EMA_VERSION);
            
            // Localize script for AJAX
            wp_localize_script('ema-admin-js', 'ema_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'api_url' => get_rest_url() . $this->namespace,
                'nonce' => wp_create_nonce('ema_admin_nonce')
            ));
        }
    }

    public static function send_success($data = null, $message = '', $status = 200) {
        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => $message,
                'data'    => $data,
                'timestamp' => current_time('timestamp')
            ),
            $status
        );
    }

    public static function send_error($message, $status = 400, $data = null) {
        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => $message,
                'data'    => $data,
                'timestamp' => current_time('timestamp')
            ),
            $status
        );
    }
   
    public function load_dependencies() {
        $includes_path = EMA_PLUGIN_PATH . 'includes/';
        
        require_once $includes_path . 'class-cache-manager.php';
        require_once $includes_path . 'class-auth-api.php';
        require_once $includes_path . 'class-products-api.php';
        require_once $includes_path . 'class-orders-api.php';
        require_once $includes_path . 'class-cart-api.php';
        require_once $includes_path . 'class-utilities.php';
        require_once $includes_path . 'class-compatibility.php';
        require_once $includes_path . 'class-address-api.php';
        require_once $includes_path . 'class-reviews-api.php';
        require_once $includes_path . 'class-wishlist-api.php';
    }
    
    public function check_compatibility() {
        $compatibility = new Compatibility();
        $compatibility->check_and_fix_issues();
    }
    
    public function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    }
    
    public function handle_block_compatibility($enabled) {
        return $enabled;
    }
    
    public function register_routes() {
        // Initialize API classes with cache manager
        $cache_manager = new Cache_Manager();
        $auth_api = new Auth_API($cache_manager);
        $wishlist_api = new Wishlist_API($cache_manager);
        $reviews_api = new Reviews_API($cache_manager);
        $address_api = new Address_API($cache_manager);
        $products_api = new Products_API($cache_manager);
        $orders_api = new Orders_API($cache_manager);
        $cart_api = new Cart_API($cache_manager);
        
        // Register routes from each class
        $auth_api->register_routes();
        $wishlist_api->register_routes();
        $reviews_api->register_routes();
        $address_api->register_routes();
        $products_api->register_routes();
        $orders_api->register_routes();
        $cart_api->register_routes();
        
        // System endpoints
        register_rest_route($this->namespace, '/system/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_system_status'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));
        
        register_rest_route($this->namespace, '/system/compatibility', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_compatibility_status'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route($this->namespace, '/system/health', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_health_check'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route($this->namespace, '/system/cache/clear', array(
            'methods' => 'POST',
            'callback' => array($this, 'clear_cache'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Ecommerce Master API',
            'Ecommerce API',
            'manage_options',
            'ecommerce-master-api',
            array($this, 'admin_settings_page'),
            'dashicons-rest-api',
            100
        );
        
        add_submenu_page(
            'ecommerce-master-api',
            'Settings',
            'Settings',
            'manage_options',
            'ecommerce-master-api',
            array($this, 'admin_settings_page')
        );
        
        add_submenu_page(
            'ecommerce-master-api',
            'Compatibility Status',
            'Compatibility',
            'manage_options',
            'ecommerce-master-api-compatibility',
            array($this, 'compatibility_status_page')
        );

        add_submenu_page(
            'ecommerce-master-api',
            'API Documentation',
            'Documentation',
            'manage_options',
            'ecommerce-master-api-docs',
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
    }
    
    public function admin_settings_page() {
        ?>
        <div class="wrap ema-settings">
            <h1>Ecommerce Master API Settings</h1>
            
            <div class="ema-cards">
                <div class="ema-card">
                    <h2>API Information</h2>
                    <div class="ema-info-grid">
                        <div><strong>Version:</strong> <?php echo esc_html($this->version); ?></div>
                        <div><strong>Namespace:</strong> <?php echo esc_html($this->namespace); ?></div>
                        <div><strong>Base URL:</strong> <code><?php echo esc_html(get_rest_url() . $this->namespace); ?></code></div>
                        <div><strong>Status:</strong> <span class="ema-status-badge ema-status-success">Active</span></div>
                    </div>
                </div>
                
                <div class="ema-card">
                    <h2>Quick Actions</h2>
                    <p>
                        <button id="ema-health-check" class="button button-primary">Run Health Check</button>
                        <button class="button ema-clear-cache" data-cache-type="all">Clear All Cache</button>
                        <button class="button ema-test-endpoint" data-endpoint="/system/health" data-method="GET">Test API</button>
                    </p>
                    <div id="ema-health-result" style="margin-top: 10px;"></div>
                </div>
            </div>
            
            <div class="ema-tabs">
                <a href="#" class="ema-tab active" data-tab="general">General Settings</a>
                <a href="#" class="ema-tab" data-tab="performance">Performance</a>
                <a href="#" class="ema-tab" data-tab="endpoints">API Endpoints</a>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields('ema_settings'); ?>
                
                <div id="ema-tab-general" class="ema-tab-content active">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable HPOS Support</th>
                            <td>
                                <input type="checkbox" name="ema_enable_hpos" value="1" 
                                    <?php checked(1, get_option('ema_enable_hpos', 1)); ?> />
                                <p class="description">High-Performance Order Storage compatibility</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Enable Block Support</th>
                            <td>
                                <input type="checkbox" name="ema_enable_blocks" value="1" 
                                    <?php checked(1, get_option('ema_enable_blocks', 1)); ?> />
                                <p class="description">Block-based cart and checkout compatibility</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">API Rate Limit</th>
                            <td>
                                <input type="number" name="ema_api_rate_limit" 
                                       value="<?php echo esc_attr(get_option('ema_api_rate_limit', 1000)); ?>" 
                                       class="regular-text" />
                                <p class="description">Requests per hour per user</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Enable Debug Mode</th>
                            <td>
                                <input type="checkbox" name="ema_enable_debug" value="1" 
                                    <?php checked(1, get_option('ema_enable_debug', 0)); ?> />
                                <p class="description">Log API requests and errors</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="ema-tab-performance" class="ema-tab-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable Caching</th>
                            <td>
                                <input type="checkbox" name="ema_enable_caching" value="1" 
                                    <?php checked(1, get_option('ema_enable_caching', 1)); ?> />
                                <p class="description">Enable response caching for better performance</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Cache TTL</th>
                            <td>
                                <input type="number" name="ema_cache_ttl" 
                                       value="<?php echo esc_attr(get_option('ema_cache_ttl', 3600)); ?>" 
                                       class="regular-text" />
                                <p class="description">Cache time-to-live in seconds</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Cache Management</th>
                            <td>
                                <button type="button" class="button ema-clear-cache" data-cache-type="products">Clear Products Cache</button>
                                <button type="button" class="button ema-clear-cache" data-cache-type="orders">Clear Orders Cache</button>
                                <button type="button" class="button ema-clear-cache" data-cache-type="reviews">Clear Reviews Cache</button>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="ema-tab-endpoints" class="ema-tab-content">
                    <h3>Available Endpoints</h3>
                    <ul class="ema-endpoint-list">
                        <li><span class="ema-endpoint-method ema-method-post">POST</span> <code>/auth/login</code> - User login</li>
                        <li><span class="ema-endpoint-method ema-method-post">POST</span> <code>/auth/register</code> - User registration</li>
                        <li><span class="ema-endpoint-method ema-method-get">GET</span> <code>/products</code> - Get products</li>
                        <li><span class="ema-endpoint-method ema-method-get">GET</span> <code>/products/{id}</code> - Get single product</li>
                        <li><span class="ema-endpoint-method ema-method-get">GET</span> <code>/cart</code> - Get cart</li>
                        <li><span class="ema-endpoint-method ema-method-post">POST</span> <code>/cart/add</code> - Add to cart</li>
                        <li><span class="ema-endpoint-method ema-method-get">GET</span> <code>/orders</code> - Get user orders</li>
                        <li><span class="ema-endpoint-method ema-method-post">POST</span> <code>/orders/create</code> - Create order</li>
                        <li><span class="ema-endpoint-method ema-method-get">GET</span> <code>/addresses</code> - Get addresses</li>
                        <li><span class="ema-endpoint-method ema-method-put">PUT</span> <code>/addresses/update</code> - Update address</li>
                        <li><span class="ema-endpoint-method ema-method-post">POST</span> <code>/reviews/add</code> - Add review</li>
                        <li><span class="ema-endpoint-method ema-method-get">GET</span> <code>/reviews/product/{id}</code> - Get product reviews</li>
                        <li><span class="ema-endpoint-method ema-method-get">GET</span> <code>/wishlist</code> - Get wishlist</li>
                        <li><span class="ema-endpoint-method ema-method-post">POST</span> <code>/wishlist/add</code> - Add to wishlist</li>
                    </ul>
                    
                    <div class="ema-api-test">
                        <h4>Test Endpoint</h4>
                        <select id="ema-test-endpoint-select">
                            <option value="/system/health">GET /system/health</option>
                            <option value="/products">GET /products</option>
                            <option value="/auth/login">POST /auth/login</option>
                        </select>
                        <button class="button ema-test-endpoint" data-method="GET">Test Endpoint</button>
                    </div>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function compatibility_status_page() {
        $compatibility = new Compatibility();
        $status = $compatibility->get_detailed_status();
        ?>
        <div class="wrap">
            <h1>Ecommerce Master API - Compatibility Status</h1>
            
            <?php if (empty($status)): ?>
                <div class="notice notice-warning">
                    <p>Compatibility status not available. Please check if the Compatibility class is properly loaded.</p>
                </div>
            <?php else: ?>
                <?php foreach ($status as $section => $items): ?>
                <div class="card">
                    <h2><?php echo esc_html(ucfirst(str_replace('_', ' ', $section))); ?></h2>
                    <table class="widefat">
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
                                    <span style="color: <?php echo esc_attr($item['status_color']); ?>">
                                        <?php echo esc_html($item['status_icon'] . ' ' . $item['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($item['details']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function documentation_page() {
        ?>
        <div class="wrap">
            <h1>Ecommerce Master API Documentation</h1>
            <div class="card">
                <h2>API Documentation</h2>
                <p>Complete API documentation is available in the documentation.md file included with the plugin.</p>
                <p><strong>Base URL:</strong> <code><?php echo esc_html(get_rest_url() . $this->namespace); ?></code></p>
                <p>For detailed endpoint documentation, request/response examples, and authentication details, please refer to the documentation.md file in the plugin directory.</p>
            </div>
        </div>
        <?php
    }
    
    public function show_admin_notices() {
        $compatibility = new Compatibility();
        $issues = $compatibility->get_critical_issues();
        
        if (!empty($issues)) {
            echo '<div class="notice notice-warning">';
            echo '<p><strong>Ecommerce Master API Compatibility Issues:</strong></p>';
            echo '<ul>';
            foreach ($issues as $issue) {
                echo '<li>' . esc_html($issue) . '</li>';
            }
            echo '</ul>';
            echo '<p><a href="' . esc_url(admin_url('admin.php?page=ecommerce-master-api-compatibility')) . '" class="button">View Details</a></p>';
            echo '</div>';
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
    
    // API Endpoint Methods
    public function get_system_status($request) {
        $compatibility = new Compatibility();
        
        return rest_ensure_response(array(
            'status' => 'success',
            'data' => array(
                'plugin_version' => $this->version,
                // 'woocommerce_version' => defined('WC_VERSION') ? WC_VERSION : 'Not available',
                'wordpress_version' => get_bloginfo('version'),
                'php_version' => PHP_VERSION,
                'compatibility' => $compatibility->get_compatibility_report()
            )
        ));
    }
    
    public function get_compatibility_status($request) {
        $compatibility = new Compatibility();
        
        return rest_ensure_response(array(
            'status' => 'success',
            'data' => $compatibility->get_compatibility_report()
        ));
    }
    
    public function get_health_check($request) {
        $compatibility = new Compatibility();
        $health = $compatibility->run_health_check();
        
        return rest_ensure_response(array(
            'status' => $health['healthy'] ? 'healthy' : 'issues',
            'message' => $health['message'],
            'data' => $health
        ));
    }
    
    public function clear_cache($request) {
        if (!current_user_can('manage_options')) {
            return new WP_Error('rest_forbidden', 'Insufficient permissions', array('status' => 403));
        }
        
        $cache_manager = new Cache_Manager();
        $cache_manager->clear_all();
        
        return rest_ensure_response(array(
            'status' => 'success',
            'message' => 'Cache cleared successfully'
        ));
    }
    
    public function check_admin_permissions($request) {
        return current_user_can('manage_options');
    }
}

// Initialize the plugin
function initialize_ecommerce_master_api() {
    new Ecommerce_Master_API();
}
add_action('plugins_loaded', 'initialize_ecommerce_master_api');

// Activation hook
register_activation_hook(__FILE__, 'ema_activate');
function ema_activate() {
    // Set default options
    add_option('ema_enable_hpos', 1);
    add_option('ema_enable_blocks', 1);
    add_option('ema_api_rate_limit', 1000);
    add_option('ema_enable_debug', 0);
    add_option('ema_enable_caching', 1);
    add_option('ema_cache_ttl', 3600);
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'ema_deactivate');
function ema_deactivate() {
    flush_rewrite_rules();
}

// Debug function to check if routes are registered
function ema_debug_routes() {
    if (isset($_GET['debug_ema_routes']) && current_user_can('manage_options')) {
        $rest_server = rest_get_server();
        $routes = $rest_server->get_routes();
        
        echo '<pre>';
        foreach ($routes as $route => $handlers) {
            if (strpos($route, 'ecommerce-api') !== false) {
                echo "Route: " . $route . "\n";
                foreach ($handlers as $handler) {
                    echo "  Methods: " . implode(', ', $handler['methods']) . "\n";
                    echo "  Callback: " . (is_array($handler['callback']) ? 
                         get_class($handler['callback'][0]) . '->' . $handler['callback'][1] : 
                         'function') . "\n";
                    echo "\n";
                }
            }
        }
        echo '</pre>';
        exit;
    }
}
add_action('init', 'ema_debug_routes');
?>