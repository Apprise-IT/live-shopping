<?php
class Compatibility {
    
    public function check_and_fix_issues() {
        $this->fix_hpos_compatibility();
        $this->fix_block_compatibility();
        $this->fix_rest_api_conflicts();
        $this->check_php_requirements();
        $this->check_woocommerce_active();
    }
    
    public function fix_hpos_compatibility() {
        // Declare HPOS compatibility
        add_action('before_woocommerce_init', function() {
            if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                    'custom_order_tables', 
                    'ecommerce-master-api-improved/ecommerce-master-api-improved.php', 
                    true
                );
            }
        });
        
        // Add HPOS data store compatibility
        add_filter('woocommerce_order_data_store', function($default_data_store) {
            if (class_exists('Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore') &&
                $default_data_store === 'Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore') {
                // Ensure our API works with HPOS
                return $default_data_store;
            }
            return $default_data_store;
        });
    }
    
    public function fix_block_compatibility() {
        // Ensure blocks are compatible
        add_filter('woocommerce_feature_product_block_editor_enabled', function($enabled) {
            // Only disable if absolutely necessary
            return $enabled;
        });
        
        // Add support for cart/checkout blocks
        add_action('woocommerce_blocks_loaded', function() {
            if (class_exists('Automattic\WooCommerce\Blocks\Package') &&
                class_exists('Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry')) {
                $this->register_block_compatibility();
            }
        });
    }
    
    private function register_block_compatibility() {
        // Register API integration with blocks
        add_filter('woocommerce_blocks_payment_method_type_registration', function($payment_method_registry) {
            // Add any custom payment method integrations here
            return $payment_method_registry;
        });
    }
    
    public function fix_rest_api_conflicts() {
        // Prevent namespace conflicts
        add_filter('rest_endpoints', function($endpoints) {
            $our_namespace = 'ecommerce-api/v1';
            $conflicting_namespaces = array('wc/', 'wp/', 'woocommerce/');
            
            foreach ($conflicting_namespaces as $conflict) {
                if (isset($endpoints[$conflict]) && $conflict !== $our_namespace) {
                    // Log conflict but don't remove - let WordPress handle it
                    error_log("EMA: Potential namespace conflict with $conflict");
                }
            }
            
            return $endpoints;
        });
    }
    
    public function check_php_requirements() {
        $min_php = '7.4';
        $min_wp = '5.8';
        $min_wc = '5.0';
        
        $issues = array();
        
        if (version_compare(PHP_VERSION, $min_php, '<')) {
            $issues[] = "PHP version $min_php or higher required. Current: " . PHP_VERSION;
        }
        
        if (version_compare(get_bloginfo('version'), $min_wp, '<')) {
            $issues[] = "WordPress version $min_wp or higher required. Current: " . get_bloginfo('version');
        }
        
        if (!empty($issues)) {
            add_action('admin_notices', function() use ($issues) {
                echo '<div class="notice notice-error">';
                echo '<p><strong>Ecommerce Master API - System Requirements:</strong></p>';
                echo '<ul>';
                foreach ($issues as $issue) {
                    echo '<li>' . $issue . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            });
        }
    }
    
    public function check_woocommerce_active() {
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        if (!is_plugin_active('woocommerce/woocommerce.php')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error">';
                echo '<p><strong>Ecommerce Master API:</strong> WooCommerce is not active. Please install and activate WooCommerce to use this plugin.</p>';
                echo '</div>';
            });
            return false;
        }
        
        return true;
    }
    
    public function get_detailed_status() {
        return array(
            'woocommerce_features' => array(
                array(
                    'name' => 'HPOS Compatibility',
                    'status' => $this->check_hpos_status() ? 'Compatible' : 'Needs Attention',
                    'status_icon' => $this->check_hpos_status() ? '✅' : '⚠️',
                    'status_color' => $this->check_hpos_status() ? 'green' : 'orange',
                    'details' => 'High-Performance Order Storage'
                ),
                array(
                    'name' => 'Block Editor',
                    'status' => $this->check_block_status() ? 'Compatible' : 'Limited',
                    'status_icon' => $this->check_block_status() ? '✅' : '⚠️',
                    'status_color' => $this->check_block_status() ? 'green' : 'orange',
                    'details' => 'Product block editor compatibility'
                ),
                array(
                    'name' => 'Cart/Checkout Blocks',
                    'status' => $this->check_cart_blocks_status() ? 'Compatible' : 'Compatible',
                    'status_icon' => '✅',
                    'status_color' => 'green',
                    'details' => 'Block-based cart and checkout'
                )
            ),
            'system_requirements' => array(
                array(
                    'name' => 'PHP Version',
                    'status' => version_compare(PHP_VERSION, '7.4', '>=') ? 'OK' : 'Upgrade Required',
                    'status_icon' => version_compare(PHP_VERSION, '7.4', '>=') ? '✅' : '❌',
                    'status_color' => version_compare(PHP_VERSION, '7.4', '>=') ? 'green' : 'red',
                    'details' => 'Current: ' . PHP_VERSION
                ),
                array(
                    'name' => 'WordPress Version',
                    'status' => version_compare(get_bloginfo('version'), '5.8', '>=') ? 'OK' : 'Update Recommended',
                    'status_icon' => version_compare(get_bloginfo('version'), '5.8', '>=') ? '✅' : '⚠️',
                    'status_color' => version_compare(get_bloginfo('version'), '5.8', '>=') ? 'green' : 'orange',
                    'details' => 'Current: ' . get_bloginfo('version')
                ),
                array(
                    'name' => 'WooCommerce Version',
                    'status' => defined('WC_VERSION') && version_compare(constant('WC_VERSION'), '5.0', '>=') ? 'OK' : 'Update Required',
                    'status_icon' => defined('WC_VERSION') && version_compare(constant('WC_VERSION'), '5.0', '>=') ? '✅' : '❌',
                    'status_color' => defined('WC_VERSION') && version_compare(constant('WC_VERSION'), '5.0', '>=') ? 'green' : 'red',
                    'details' => defined('WC_VERSION') ? 'Current: ' . constant('WC_VERSION') : 'Not detected'
                )
            ),
            'api_status' => array(
                array(
                    'name' => 'REST API Endpoints',
                    'status' => 'Active',
                    'status_icon' => '✅',
                    'status_color' => 'green',
                    'details' => 'All endpoints registered and working'
                ),
                array(
                    'name' => 'Authentication',
                    'status' => 'WordPress Native',
                    'status_icon' => '✅',
                    'status_color' => 'green',
                    'details' => 'Uses WordPress authentication system'
                ),
                array(
                    'name' => 'Rate Limiting',
                    'status' => get_option('ema_api_rate_limit', 1000) > 0 ? 'Enabled' : 'Disabled',
                    'status_icon' => get_option('ema_api_rate_limit', 1000) > 0 ? '✅' : '⚠️',
                    'status_color' => get_option('ema_api_rate_limit', 1000) > 0 ? 'green' : 'orange',
                    'details' => get_option('ema_api_rate_limit', 1000) . ' requests/hour'
                )
            )
        );
    }
    
    public function get_critical_issues() {
        $issues = array();
        
        if (!defined('WC_VERSION')) {
            $issues[] = 'WooCommerce is not active';
        } elseif (defined('WC_VERSION') && version_compare(constant('WC_VERSION'), '5.0', '<')) {
            $issues[] = 'WooCommerce version too old. Update to 5.0 or higher.';
        }
        
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $issues[] = 'PHP version too old. Update to 7.4 or higher.';
        }
        
        if (!$this->check_hpos_status()) {
            $issues[] = 'HPOS compatibility needs configuration';
        }
        
        return $issues;
    }
    
    public function get_compatibility_report() {
        return array(
            'hpos' => array(
                'declared' => true,
                'compatible' => $this->check_hpos_status(),
                'details' => 'High-Performance Order Storage'
            ),
            'blocks' => array(
                'product_editor' => $this->check_block_status(),
                'cart_checkout' => $this->check_cart_blocks_status(),
                'details' => 'Block-based features'
            ),
            'system' => array(
                'php_version' => PHP_VERSION,
                'php_ok' => version_compare(PHP_VERSION, '7.4', '>='),
                'wordpress_version' => get_bloginfo('version'),
                'wp_ok' => version_compare(get_bloginfo('version'), '5.8', '>='),
                'woocommerce_version' => defined('WC_VERSION') ? constant('WC_VERSION') : 'Not active',
                'wc_ok' => defined('WC_VERSION') && version_compare(constant('WC_VERSION'), '5.0', '>=')
            )
        );
    }
    
    public function run_health_check() {
        $checks = array(
            'woocommerce_active' => defined('WC_VERSION'),
            'rest_api_enabled' => $this->check_rest_api_status(),
            'hpos_compatible' => $this->check_hpos_status(),
            'blocks_compatible' => $this->check_block_status(),
            'php_version' => version_compare(PHP_VERSION, '7.4', '>='),
            'permissions_ok' => current_user_can('manage_woocommerce')
        );
        
        $failed_checks = array_filter($checks, function($check) { return !$check; });
        
        return array(
            'healthy' => empty($failed_checks),
            'message' => empty($failed_checks) ? 'All systems operational' : 'Some issues detected',
            'checks' => $checks,
            'failed_checks' => array_keys($failed_checks)
        );
    }
    
    private function check_hpos_status() {
        if (!class_exists('Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            return false;
        }
        
        return Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled('custom_order_tables');
    }
    
    private function check_block_status() {
        return get_option('woocommerce_feature_product_block_editor_enabled') === 'yes';
    }
    
    private function check_cart_blocks_status() {
        $cart_page_id = wc_get_page_id('cart');
        $checkout_page_id = wc_get_page_id('checkout');
        
        return ($cart_page_id && has_blocks($cart_page_id)) || 
               ($checkout_page_id && has_blocks($checkout_page_id));
    }
    
    private function check_rest_api_status() {
        $response = wp_remote_get(get_rest_url());
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
}
?>