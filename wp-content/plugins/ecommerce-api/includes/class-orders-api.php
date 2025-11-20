<?php
class Orders_API {
    
    private $namespace = 'ecommerce-api/v1';
    private $cache_manager;
    
    public function __construct($cache_manager = null) {
        $this->cache_manager = $cache_manager;
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        // Create order
        register_rest_route($this->namespace, '/orders/create', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_order'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        // Get user orders
        register_rest_route($this->namespace, '/orders', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_orders'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        // Get order details
        register_rest_route($this->namespace, '/orders/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_order'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        // Cancel order
        register_rest_route($this->namespace, '/orders/cancel', array(
            'methods' => 'PUT',
            'callback' => array($this, 'cancel_order'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        // Order tracking
        register_rest_route($this->namespace, '/orders/tracking/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'order_tracking'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        // Get order statuses
        register_rest_route($this->namespace, '/orders/statuses', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_order_statuses'),
            'permission_callback' => array($this, 'check_auth')
        ));

        register_rest_route($this->namespace, '/orders/debug/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'debug_order'),
            'permission_callback' => array($this, 'check_auth')
        ));
    }
    
    public function check_auth($request) {
        if (is_user_logged_in()) {
            return true;
        }
        return $this->check_token_auth($request);
    }

    public function check_token_auth($request) {
        $token = $this->get_token_from_request($request);
        
        if (!$token) {
            return new WP_Error(
                'rest_forbidden',
                __('Authentication required.', 'textdomain'),
                array('status' => 401)
            );
        }
        
        $user_id = $this->validate_session_token($token);
        
        if ($user_id) {
            wp_set_current_user($user_id);
            return true;
        }
        
        return new WP_Error(
            'rest_forbidden',
            __('Invalid authentication token.', 'textdomain'),
            array('status' => 401)
        );
    }
    
    public function create_order($request) {
        try {
            $parameters = $request->get_params();
            $user_id = get_current_user_id();
            
            // Get cart items
            $cart = WC()->cart;
            
            if ($cart->is_empty()) {
                return $this->send_error('Cart is empty', 400);
            }
            
            // Create order
            $order = wc_create_order(array('customer_id' => $user_id));
            
            if (is_wp_error($order)) {
                return $this->send_error('Failed to create order: ' . $order->get_error_message(), 400);
            }
            
            // Add products from cart
            foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                $order->add_product($product, $cart_item['quantity'], array(
                    'variation' => $cart_item['variation'],
                    'subtotal' => $cart_item['line_subtotal'],
                    'total' => $cart_item['line_total']
                ));
            }
            
            // Set addresses
            $billing_address = array(
                'first_name' => sanitize_text_field($parameters['billing_first_name']),
                'last_name' => sanitize_text_field($parameters['billing_last_name']),
                'email' => sanitize_email($parameters['billing_email']),
                'phone' => sanitize_text_field($parameters['billing_phone']),
                'address_1' => sanitize_text_field($parameters['billing_address_1']),
                'address_2' => sanitize_text_field($parameters['billing_address_2']),
                'city' => sanitize_text_field($parameters['billing_city']),
                'state' => sanitize_text_field($parameters['billing_state']),
                'postcode' => sanitize_text_field($parameters['billing_postcode']),
                'country' => sanitize_text_field($parameters['billing_country'])
            );
            
            $shipping_address = array(
                'first_name' => sanitize_text_field($parameters['shipping_first_name']),
                'last_name' => sanitize_text_field($parameters['shipping_last_name']),
                'address_1' => sanitize_text_field($parameters['shipping_address_1']),
                'address_2' => sanitize_text_field($parameters['shipping_address_2']),
                'city' => sanitize_text_field($parameters['shipping_city']),
                'state' => sanitize_text_field($parameters['shipping_state']),
                'postcode' => sanitize_text_field($parameters['shipping_postcode']),
                'country' => sanitize_text_field($parameters['shipping_country'])
            );
            
            $order->set_address($billing_address, 'billing');
            $order->set_address($shipping_address, 'shipping');
            
            // Set payment method
            $payment_method = sanitize_text_field($parameters['payment_method']);
            $order->set_payment_method($payment_method);
            
            // Calculate totals
            $order->calculate_totals();
            
            // Update order status
            $order->update_status('pending', 'Order created via API');
            
            // Clear cart
            $cart->empty_cart();
            
            // Clear user orders cache
            if ($this->cache_manager) {
                $this->cache_manager->clear_user_cache($user_id);
            }
            
            return $this->send_success(array(
                'order_id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'status' => $order->get_status(),
                'total' => $order->get_total()
            ), 201, 'Order created successfully');
            
        } catch (Exception $e) {
            $this->log_error('Exception in create_order', array(
                'message' => $e->getMessage(),
                'user_id' => get_current_user_id()
            ));
            return $this->send_error('Failed to create order', 500);
        }
    }
    
    public function get_orders($request) {
        try {
            $user_id = get_current_user_id();
            $parameters = $request->get_params();
            $cache_key = $this->cache_manager ? $this->cache_manager->generate_key("user_orders_{$user_id}", $parameters) : null;
            
            // Try cache first
            if ($this->cache_manager && $cached = $this->cache_manager->get($cache_key)) {
                return $this->send_success($cached);
            }
            
            $page = isset($parameters['page']) ? intval($parameters['page']) : 1;
            $per_page = isset($parameters['per_page']) ? intval($parameters['per_page']) : 10;
            
            $orders = wc_get_orders(array(
                'customer_id' => $user_id,
                'limit' => $per_page,
                'page' => $page,
                'orderby' => 'date',
                'order' => 'DESC'
            ));
            
            $formatted_orders = array();
            
            foreach ($orders as $order) {
                $formatted_orders[] = $this->format_order_data($order);
            }
            
            $total_orders = wc_get_customer_order_count($user_id);
            
            $response = array(
                'orders' => $formatted_orders,
                'pagination' => array(
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_orders' => $total_orders,
                    'total_pages' => ceil($total_orders / $per_page)
                )
            );
            
            // Cache the result
            if ($this->cache_manager) {
                $this->cache_manager->set($cache_key, $response, 900); // 15 minutes
            }
            
            return $this->send_success($response);
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_orders', array(
                'message' => $e->getMessage(),
                'user_id' => get_current_user_id()
            ));
            return $this->send_error('Failed to retrieve orders', 500);
        }
    }

    public function debug_order($request) {
        $order_id = $request['id'];
        $user_id = get_current_user_id();
        
        $debug_info = array(
            'requested_order_id' => $order_id,
            'current_user_id' => $user_id,
            'current_user_email' => wp_get_current_user()->user_email,
            'is_user_logged_in' => is_user_logged_in()
        );
        
        $order = wc_get_order($order_id);
        
        if ($order) {
            $debug_info['order_exists'] = true;
            $debug_info['order_customer_id'] = $order->get_customer_id();
            $debug_info['order_status'] = $order->get_status();
            $debug_info['order_email'] = $order->get_billing_email();
            $debug_info['order_user_relationship'] = ($order->get_customer_id() == $user_id) ? 'OWNER' : 'NOT_OWNER';
        } else {
            $debug_info['order_exists'] = false;
        }
        
        return $this->send_success($debug_info);
    }
    
    private function get_order_object($order_id) {
        if (function_exists('wc_get_order') && class_exists('Automattic\WooCommerce\Utilities\OrderUtil')) {
            return wc_get_order($order_id);
        }
        return false;
    }

    public function get_order($request) {
        try {
            $order_id = $request['id'];
            $user_id = get_current_user_id();
            
            $order = $this->get_order_object($order_id);
            
            if (!$order) {
                return $this->send_error('Order not found', 404);
            }
            
            // For guest orders, check if user has permission
            $customer_id = $order->get_customer_id();
            if ($customer_id != $user_id && $customer_id != 0) {
                return $this->send_error('Order not found', 404);
            }
            
            // If guest order, allow access if the current user is also guest (0) or has matching email
            if ($customer_id == 0) {
                $order_email = $order->get_billing_email();
                $current_user_email = wp_get_current_user()->user_email;
                if ($order_email !== $current_user_email && !current_user_can('manage_woocommerce')) {
                    return $this->send_error('Order not found', 404);
                }
            }
            
            $order_data = $this->format_order_data($order, true);
            
            // Cache the result
            if ($this->cache_manager) {
                $cache_key = $this->cache_manager->generate_key("order_{$order_id}");
                $this->cache_manager->set($cache_key, $order_data, 1800);
            }
            
            return $this->send_success($order_data);
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_order', array(
                'message' => $e->getMessage(),
                'order_id' => $order_id ?? 0,
                'user_id' => get_current_user_id()
            ));
            return $this->send_error('Failed to retrieve order', 500);
        }
    }
    
    public function cancel_order($request) {
        try {
            $parameters = $request->get_params();
            $order_id = isset($parameters['order_id']) ? intval($parameters['order_id']) : 0;
            $user_id = get_current_user_id();
            
            $order = wc_get_order($order_id);
            
            if (!$order || $order->get_customer_id() !== $user_id) {
                return $this->send_error('Order not found', 404);
            }
            
            if (!$order->has_status(['pending', 'on-hold', 'processing'])) {
                return $this->send_error('Order cannot be cancelled', 400);
            }
            
            $order->update_status('cancelled', 'Order cancelled by customer via API');
            
            // Clear order cache
            if ($this->cache_manager) {
                $this->cache_manager->clear_order_cache($order_id);
                $this->cache_manager->clear_user_cache($user_id);
            }
            
            return $this->send_success(null, 200, 'Order cancelled successfully');
            
        } catch (Exception $e) {
            $this->log_error('Exception in cancel_order', array(
                'message' => $e->getMessage(),
                'order_id' => $order_id ?? 0,
                'user_id' => get_current_user_id()
            ));
            return $this->send_error('Failed to cancel order', 500);
        }
    }
    
    public function order_tracking($request) {
        try {
            $order_id = $request['id'];
            $user_id = get_current_user_id();
            $cache_key = $this->cache_manager ? $this->cache_manager->generate_key("order_tracking_{$order_id}") : null;
            
            // Try cache first
            if ($this->cache_manager && $cached = $this->cache_manager->get($cache_key)) {
                return $this->send_success($cached);
            }
            
            $order = wc_get_order($order_id);
            
            if (!$order || $order->get_customer_id() !== $user_id) {
                return $this->send_error('Order not found', 404);
            }
            
            $tracking_data = array(
                'order_id' => $order->get_id(),
                'status' => $order->get_status(),
                'status_label' => wc_get_order_status_name($order->get_status()),
                'date_created' => $order->get_date_created()->date('Y-m-d H:i:s'),
                'date_modified' => $order->get_date_modified()->date('Y-m-d H:i:s'),
                'tracking_number' => $order->get_meta('_tracking_number'),
                'tracking_provider' => $order->get_meta('_tracking_provider'),
                'tracking_link' => $order->get_meta('_tracking_link'),
                'notes' => $this->get_order_notes($order)
            );
            
            // Cache the result
            if ($this->cache_manager) {
                $this->cache_manager->set($cache_key, $tracking_data, 300); // 5 minutes for tracking data
            }
            
            return $this->send_success($tracking_data);
            
        } catch (Exception $e) {
            $this->log_error('Exception in order_tracking', array(
                'message' => $e->getMessage(),
                'order_id' => $order_id ?? 0,
                'user_id' => get_current_user_id()
            ));
            return $this->send_error('Failed to retrieve tracking information', 500);
        }
    }
    
    public function get_order_statuses($request) {
        try {
            $cache_key = 'order_statuses';
            
            // Try cache first
            if ($this->cache_manager && $cached = $this->cache_manager->get($cache_key)) {
                return $this->send_success($cached);
            }
            
            $statuses = wc_get_order_statuses();
            $formatted_statuses = array();
            
            foreach ($statuses as $key => $label) {
                $formatted_statuses[] = array(
                    'key' => $key,
                    'label' => $label,
                    'slug' => str_replace('wc-', '', $key)
                );
            }
            
            // Cache the result (long TTL as statuses rarely change)
            if ($this->cache_manager) {
                $this->cache_manager->set($cache_key, $formatted_statuses, 86400); // 24 hours
            }
            
            return $this->send_success($formatted_statuses);
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_order_statuses', array(
                'message' => $e->getMessage()
            ));
            return $this->send_error('Failed to retrieve order statuses', 500);
        }
    }
    
    private function format_order_data($order, $include_items = false) {
        $order_data = array(
            'id' => $order->get_id(),
            'number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'status_label' => wc_get_order_status_name($order->get_status()),
            'date_created' => $order->get_date_created()->date('Y-m-d H:i:s'),
            'date_modified' => $order->get_date_modified()->date('Y-m-d H:i:s'),
            'total' => $order->get_total(),
            'currency' => $order->get_currency(),
            'payment_method' => $order->get_payment_method_title(),
            'billing' => array(
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'address_1' => $order->get_billing_address_1(),
                'address_2' => $order->get_billing_address_2(),
                'city' => $order->get_billing_city(),
                'state' => $order->get_billing_state(),
                'postcode' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country()
            ),
            'shipping' => array(
                'first_name' => $order->get_shipping_first_name(),
                'last_name' => $order->get_shipping_last_name(),
                'address_1' => $order->get_shipping_address_1(),
                'address_2' => $order->get_shipping_address_2(),
                'city' => $order->get_shipping_city(),
                'state' => $order->get_shipping_state(),
                'postcode' => $order->get_shipping_postcode(),
                'country' => $order->get_shipping_country()
            )
        );
        
        if ($include_items) {
            $items = array();
            
            foreach ($order->get_items() as $item_id => $item) {
                $product = $item->get_product();
                $items[] = array(
                    'id' => $item_id,
                    'product_id' => $item->get_product_id(),
                    'name' => $item->get_name(),
                    'quantity' => $item->get_quantity(),
                    'price' => $item->get_total(),
                    'subtotal' => $item->get_subtotal(),
                    'thumbnail' => $product ? wp_get_attachment_url($product->get_image_id()) : ''
                );
            }
            
            $order_data['items'] = $items;
            $order_data['totals'] = array(
                'subtotal' => $order->get_subtotal(),
                'shipping_total' => $order->get_shipping_total(),
                'discount_total' => $order->get_discount_total(),
                'tax_total' => $order->get_total_tax(),
                'total' => $order->get_total()
            );
        }
        
        return $order_data;
    }
    
    private function get_order_notes($order) {
        $notes = array();
        $order_notes = $order->get_customer_order_notes();
        
        foreach ($order_notes as $note) {
            $notes[] = array(
                'date' => $note->comment_date,
                'content' => $note->comment_content
            );
        }
        
        return $notes;
    }

    private function get_token_from_request($request) {
        // Check Authorization header first
        $auth_header = $request->get_header('Authorization');
        if ($auth_header && preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return sanitize_text_field($matches[1]);
        }
        
        // Check X-Auth-Token header
        $x_auth_token = $request->get_header('X-Auth-Token');
        if ($x_auth_token) {
            return sanitize_text_field($x_auth_token);
        }
        
        // Check query parameter
        $token_param = $request->get_param('token');
        if ($token_param) {
            return sanitize_text_field($token_param);
        }
        
        return null;
    }

    private function validate_session_token($token) {
        if (empty($token)) {
            return false;
        }
        
        $users = get_users(array(
            'meta_key' => 'ecommerce_api_session_tokens',
            'fields' => 'ID'
        ));
        
        foreach ($users as $user_id) {
            $tokens = get_user_meta($user_id, 'ecommerce_api_session_tokens', true);
            
            if (is_array($tokens) && isset($tokens[$token])) {
                $token_data = $tokens[$token];
                
                // Check if token is expired
                if ($token_data['expires'] < time()) {
                    // Remove expired token
                    unset($tokens[$token]);
                    update_user_meta($user_id, 'ecommerce_api_session_tokens', $tokens);
                    return false;
                }
                
                // Update last used time
                $tokens[$token]['last_used'] = time();
                update_user_meta($user_id, 'ecommerce_api_session_tokens', $tokens);
                
                return $user_id;
            }
        }
        
        return false;
    }
    
    private function log_error($message, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Orders_API Error: ' . $message . ' - Context: ' . json_encode($context));
        }
    }
    
    private function send_success($data = null, $status = 200, $message = 'Success') {
        $response = array(
            'success' => true,
            'message' => $message,
            'data' => $data
        );
        
        return new WP_REST_Response($response, $status);
    }
    
    private function send_error($message, $status = 400) {
        return new WP_Error(
            'orders_api_error',
            $message,
            array('status' => $status)
        );
    }
}
?>