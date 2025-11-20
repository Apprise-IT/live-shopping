<?php
class Cart_API {
    
    private $namespace = 'ecommerce-api/v1';
    private $cache_manager;
    
    public function __construct($cache_manager = null) {
        $this->cache_manager = $cache_manager;
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        // Get cart
        register_rest_route($this->namespace, '/cart', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_cart'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        // Add to cart
        register_rest_route($this->namespace, '/cart/add', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_to_cart'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        // Update cart item
        register_rest_route($this->namespace, '/cart/update', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_cart'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        // Remove from cart - FIXED with multiple methods
        register_rest_route($this->namespace, '/cart/remove', array(
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'remove_from_cart'),
                'permission_callback' => array($this, 'check_auth')
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'remove_from_cart'),
                'permission_callback' => array($this, 'check_auth')
            )
        ));
        
        // Clear cart
        register_rest_route($this->namespace, '/cart/clear', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'clear_cart'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        // Get cart count
        register_rest_route($this->namespace, '/cart/count', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_cart_count'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        // Apply coupon
        register_rest_route($this->namespace, '/cart/apply-coupon', array(
            'methods' => 'POST',
            'callback' => array($this, 'apply_coupon'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        // Remove coupon
        register_rest_route($this->namespace, '/cart/remove-coupon', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'remove_coupon'),
            'permission_callback' => array($this, 'check_auth')
        ));
    }
    
    public function check_auth($request) {
        $token = $this->get_token_from_request($request);
        
        if (!$token) {
            return new WP_Error('rest_forbidden', 'Authentication token required', array('status' => 401));
        }
        
        $user_id = $this->validate_session_token($token);
        
        if ($user_id) {
            wp_set_current_user($user_id);
            $this->initialize_woocommerce_session();
            return true;
        }
        
        return new WP_Error('rest_forbidden', 'Invalid or expired authentication token', array('status' => 401));
    }
    
    private function initialize_woocommerce_session() {
        if (!class_exists('WooCommerce') || !function_exists('WC')) {
            return false;
        }
        
        if (null === WC()->session) {
            WC()->initialize_session();
        }
        
        if (null === WC()->cart) {
            WC()->initialize_cart();
        }
        
        return true;
    }
    
    public function get_cart($request) {
        if (!$this->initialize_woocommerce_session()) {
            return $this->send_error('WooCommerce not available', 500);
        }
        
        $user_id = get_current_user_id();
        $cache_key = $this->cache_manager ? $this->cache_manager->generate_key("user_cart_{$user_id}") : null;
        
        // Try cache first (shorter TTL for cart data)
        if ($this->cache_manager && $cached = $this->cache_manager->get($cache_key)) {
            return $this->send_success($cached);
        }
        
        $cart = WC()->cart;
        
        if (!$cart) {
            return $this->send_error('Cart not available', 500);
        }
        
        // Check if cart is empty
        if ($cart->is_empty()) {
            $empty_cart_response = array(
                'items' => array(),
                'totals' => array(
                    'subtotal' => 0,
                    'subtotal_tax' => 0,
                    'shipping_total' => 0,
                    'shipping_tax' => 0,
                    'discount_total' => 0,
                    'discount_tax' => 0,
                    'total' => 0,
                    'total_tax' => 0,
                    'currency' => get_woocommerce_currency(),
                    'currency_symbol' => get_woocommerce_currency_symbol()
                ),
                'item_count' => 0,
                'total_items' => 0,
                'needs_shipping' => false,
                'is_empty' => true
            );
            
            // Cache empty cart
            if ($this->cache_manager) {
                $this->cache_manager->set($cache_key, $empty_cart_response, 300); // 5 minutes
            }
            
            return $this->send_success($empty_cart_response, 200, 'Cart is empty');
        }
        
        $cart_items = array();
        $total_items = 0;
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            // Skip if product data is not available
            if (!isset($cart_item['data']) || !is_a($cart_item['data'], 'WC_Product')) {
                continue;
            }
            
            $product = $cart_item['data'];
            $product_id = $cart_item['product_id'];
            $variation_id = $cart_item['variation_id'];
            
            // Calculate item totals
            $line_subtotal = (float) $cart_item['line_subtotal'];
            $line_subtotal_tax = (float) $cart_item['line_subtotal_tax'];
            $line_total = (float) $cart_item['line_total'];
            $line_tax = (float) $cart_item['line_tax'];
            
            $total_items += $cart_item['quantity'];
            
            // Get product categories
            $categories = array();
            $product_categories = get_the_terms($product_id, 'product_cat');
            if (!empty($product_categories) && !is_wp_error($product_categories)) {
                foreach ($product_categories as $category) {
                    $categories[] = array(
                        'id' => $category->term_id,
                        'name' => $category->name,
                        'slug' => $category->slug
                    );
                }
            }
            
            // Get product tags
            $tags = array();
            $product_tags = get_the_terms($product_id, 'product_tag');
            if (!empty($product_tags) && !is_wp_error($product_tags)) {
                foreach ($product_tags as $tag) {
                    $tags[] = array(
                        'id' => $tag->term_id,
                        'name' => $tag->name,
                        'slug' => $tag->slug
                    );
                }
            }
            
            // Build cart item data
            $cart_item_data = array(
                'key' => $cart_item_key,
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'quantity' => $cart_item['quantity'],
                'name' => $product->get_name(),
                'sku' => $product->get_sku(),
                'price' => (float) $product->get_price(),
                'regular_price' => (float) $product->get_regular_price(),
                'sale_price' => $product->get_sale_price() ? (float) $product->get_sale_price() : null,
                'on_sale' => $product->is_on_sale(),
                
                // Item totals
                'line_subtotal' => $line_subtotal,
                'line_subtotal_tax' => $line_subtotal_tax,
                'line_total' => $line_total,
                'line_tax' => $line_tax,
                'line_total_with_tax' => $line_total + $line_tax,
                
                // Product info
                'type' => $product->get_type(),
                'status' => $product->get_status(),
                'description' => $product->get_description(),
                'short_description' => $product->get_short_description(),
                
                // Stock info
                'stock_quantity' => $product->get_stock_quantity(),
                'stock_status' => $product->get_stock_status(),
                'manage_stock' => $product->get_manage_stock(),
                'backorders_allowed' => $product->backorders_allowed(),
                'max_purchase' => $product->get_max_purchase_quantity(),
                'min_purchase' => $product->get_min_purchase_quantity(),
                
                // Images
                'thumbnail' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') ?: '',
                'medium_image' => wp_get_attachment_image_url($product->get_image_id(), 'medium') ?: '',
                'large_image' => wp_get_attachment_image_url($product->get_image_id(), 'large') ?: '',
                'full_image' => wp_get_attachment_image_url($product->get_image_id(), 'full') ?: '',
                'gallery_images' => $this->get_product_gallery($product),
                
                // Product attributes
                'attributes' => $this->get_cart_item_attributes($cart_item),
                'categories' => $categories,
                'tags' => $tags,
                
                // Permalink
                'permalink' => get_permalink($product_id),
                
                // Weight and dimensions
                'weight' => $product->get_weight() ? (float) $product->get_weight() : null,
                'dimensions' => array(
                    'length' => $product->get_length() ? (float) $product->get_length() : null,
                    'width' => $product->get_width() ? (float) $product->get_width() : null,
                    'height' => $product->get_height() ? (float) $product->get_height() : null
                ),
                
                // Shipping
                'virtual' => $product->is_virtual(),
                'downloadable' => $product->is_downloadable(),
                'shipping_required' => $product->needs_shipping(),
                'shipping_taxable' => $product->is_shipping_taxable(),
                
                // Tax
                'tax_status' => $product->get_tax_status(),
                'tax_class' => $product->get_tax_class()
            );
            
            $cart_items[] = $cart_item_data;
        }
        
        // Calculate cart totals
        $subtotal = (float) $cart->get_subtotal();
        $subtotal_tax = (float) $cart->get_subtotal_tax();
        $shipping_total = (float) $cart->get_shipping_total();
        $shipping_tax = (float) $cart->get_shipping_tax();
        $discount_total = (float) $cart->get_discount_total();
        $discount_tax = (float) $cart->get_discount_tax();
        $total = (float) $cart->get_total('edit');
        $total_tax = (float) $cart->get_total_tax();
        
        $cart_data = array(
            'items' => $cart_items,
            'totals' => array(
                'subtotal' => $subtotal,
                'subtotal_tax' => $subtotal_tax,
                'subtotal_with_tax' => $subtotal + $subtotal_tax,
                'shipping_total' => $shipping_total,
                'shipping_tax' => $shipping_tax,
                'shipping_with_tax' => $shipping_total + $shipping_tax,
                'discount_total' => $discount_total,
                'discount_tax' => $discount_tax,
                'discount_with_tax' => $discount_total + $discount_tax,
                'total' => $total,
                'total_tax' => $total_tax,
                'total_with_tax' => $total + $total_tax,
                'currency' => get_woocommerce_currency(),
                'currency_symbol' => get_woocommerce_currency_symbol(),
                'price_format' => get_woocommerce_price_format()
            ),
            'summary' => array(
                'item_count' => $cart->get_cart_contents_count(),
                'total_items' => $total_items,
                'needs_shipping' => $cart->needs_shipping(),
                'is_empty' => $cart->is_empty(),
                'shipping_methods' => $this->get_available_shipping_methods(),
                'applied_coupons' => $cart->get_applied_coupons()
            ),
            'meta' => array(
                'timestamp' => time(),
                'user_id' => get_current_user_id(),
                'cart_hash' => $cart->get_cart_hash()
            )
        );
        
        // Cache the cart data
        if ($this->cache_manager) {
            $this->cache_manager->set($cache_key, $cart_data, 300); // 5 minutes for cart data
        }
        
        return $this->send_success($cart_data, 200, 'Cart retrieved successfully');
    }

    /**
     * Get product gallery images
     */
    private function get_product_gallery($product) {
        $gallery_images = array();
        $attachment_ids = $product->get_gallery_image_ids();
        
        foreach ($attachment_ids as $attachment_id) {
            $gallery_images[] = array(
                'thumbnail' => wp_get_attachment_image_url($attachment_id, 'thumbnail') ?: '',
                'medium' => wp_get_attachment_image_url($attachment_id, 'medium') ?: '',
                'large' => wp_get_attachment_image_url($attachment_id, 'large') ?: '',
                'full' => wp_get_attachment_image_url($attachment_id, 'full') ?: '',
                'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true) ?: ''
            );
        }
        
        return $gallery_images;
    }

    /**
     * Get available shipping methods
     */
    private function get_available_shipping_methods() {
        $shipping_methods = array();
        
        if (WC()->shipping && WC()->shipping->get_packages()) {
            $packages = WC()->shipping->get_packages();
            
            foreach ($packages as $i => $package) {
                $methods = $package['rates'];
                
                foreach ($methods as $method) {
                    $shipping_methods[] = array(
                        'id' => $method->get_id(),
                        'label' => $method->get_label(),
                        'cost' => (float) $method->get_cost(),
                        'taxes' => $method->get_taxes(),
                        'method_id' => $method->get_method_id(),
                        'instance_id' => $method->get_instance_id()
                    );
                }
            }
        }
        
        return $shipping_methods;
    }
    
    public function add_to_cart($request) {
        if (!$this->initialize_woocommerce_session()) {
            return $this->send_error('WooCommerce not available', 500);
        }
        
        $parameters = $request->get_params();
        
        $product_id = isset($parameters['product_id']) ? absint($parameters['product_id']) : 0;
        $quantity = isset($parameters['quantity']) ? absint($parameters['quantity']) : 1;
        $variation_id = isset($parameters['variation_id']) ? absint($parameters['variation_id']) : 0;
        $variation = isset($parameters['variation']) ? (array) $parameters['variation'] : array();
        
        if ($product_id <= 0) {
            return $this->send_error('Valid product ID is required', 400);
        }
        
        if ($quantity <= 0) {
            return $this->send_error('Quantity must be greater than 0', 400);
        }
        
        $product = wc_get_product($product_id);
        
        if (!$product || !is_a($product, 'WC_Product')) {
            return $this->send_error('Product not found', 404);
        }
        
        if (!$product->is_purchasable()) {
            return $this->send_error('Product is not purchasable', 400);
        }
        
        if (!$product->is_in_stock()) {
            return $this->send_error('Product is out of stock', 400);
        }
        
        if ($product->is_type('variable') && $variation_id <= 0) {
            return $this->send_error('Variation ID is required for variable products', 400);
        }
        
        if ($product->is_type('variable') && $variation_id > 0) {
            $variation_product = wc_get_product($variation_id);
            if (!$variation_product || !$variation_product->is_type('variation')) {
                return $this->send_error('Invalid variation', 400);
            }
        }
        
        $sanitized_variation = array();
        foreach ($variation as $key => $value) {
            $sanitized_variation[sanitize_text_field($key)] = sanitize_text_field($value);
        }
        
        try {
            $cart_item_key = WC()->cart->add_to_cart(
                $product_id, 
                $quantity, 
                $variation_id, 
                $sanitized_variation
            );
            
            if (!$cart_item_key) {
                return $this->send_error('Failed to add product to cart. Please try again.', 400);
            }
            
            // Get the added cart item with null checking
            $cart_item = WC()->cart->get_cart_item($cart_item_key);
            
            if (!$cart_item || !isset($cart_item['data']) || !is_a($cart_item['data'], 'WC_Product')) {
                // If we can't get the cart item details, return basic success response
                $response_data = array(
                    'cart_item_key' => $cart_item_key,
                    'product_id' => $product_id,
                    'variation_id' => $variation_id,
                    'quantity' => $quantity,
                    'item_count' => WC()->cart->get_cart_contents_count(),
                    'product_name' => $product->get_name(),
                    'product_price' => (float) $product->get_price()
                );
            } else {
                $added_product = $cart_item['data'];
                $response_data = array(
                    'cart_item_key' => $cart_item_key,
                    'product_id' => $product_id,
                    'variation_id' => $variation_id,
                    'quantity' => $quantity,
                    'item_count' => WC()->cart->get_cart_contents_count(),
                    'product_name' => $added_product->get_name(),
                    'product_price' => (float) $added_product->get_price()
                );
            }
            
            // Clear cart cache
            $this->clear_cart_cache();
            
            return $this->send_success($response_data, 200, 'Product added to cart successfully');
            
        } catch (Exception $e) {
            $this->log_error('Exception in add_to_cart', array(
                'message' => $e->getMessage(),
                'product_id' => $product_id,
                'user_id' => get_current_user_id()
            ));
            return $this->send_error($e->getMessage(), 400);
        }
    }
    
    public function update_cart($request) {
        if (!$this->initialize_woocommerce_session()) {
            return $this->send_error('WooCommerce not available', 500);
        }
        
        $parameters = $request->get_params();
        
        $cart_item_key = isset($parameters['cart_item_key']) ? sanitize_text_field($parameters['cart_item_key']) : '';
        $quantity = isset($parameters['quantity']) ? absint($parameters['quantity']) : 1;
        
        if (empty($cart_item_key)) {
            return $this->send_error('Cart item key is required', 400);
        }
        
        // Get all cart items for better error reporting
        $cart_items = WC()->cart->get_cart();
        
        // Check if cart item exists
        if (!isset($cart_items[$cart_item_key])) {
            $available_items = array();
            foreach ($cart_items as $key => $item) {
                $product_name = 'Unknown Product';
                if (isset($item['data']) && is_a($item['data'], 'WC_Product')) {
                    $product_name = $item['data']->get_name();
                }
                $available_items[] = "{$key} ({$product_name})";
            }
            
            return $this->send_error(
                'Cart item not found. Available items: ' . 
                (!empty($available_items) ? implode(', ', $available_items) : 'Cart is empty'), 
                404
            );
        }
        
        $cart_item = $cart_items[$cart_item_key];
        
        if ($quantity <= 0) {
            WC()->cart->remove_cart_item($cart_item_key);
            $this->clear_cart_cache();
            return $this->send_success(null, 200, 'Item removed from cart');
        }
        
        // Check stock availability
        if (isset($cart_item['data']) && is_a($cart_item['data'], 'WC_Product')) {
            $product = $cart_item['data'];
            $stock_quantity = $product->get_stock_quantity();
            
            if ($stock_quantity !== null && $quantity > $stock_quantity && !$product->backorders_allowed()) {
                return $this->send_error(
                    sprintf('Only %d items available in stock', $stock_quantity), 
                    400
                );
            }
        }
        
        $result = WC()->cart->set_quantity($cart_item_key, $quantity);
        
        if (!$result) {
            return $this->send_error('Failed to update cart item', 400);
        }
        
        $response_data = array(
            'cart_item_key' => $cart_item_key,
            'quantity' => $quantity,
            'item_count' => WC()->cart->get_cart_contents_count(),
            'product_name' => isset($cart_item['data']) ? $cart_item['data']->get_name() : 'Product'
        );
        
        // Clear cart cache
        $this->clear_cart_cache();
        
        return $this->send_success($response_data, 200, 'Cart updated successfully');
    }
    
    public function remove_from_cart($request) {
        if (!$this->initialize_woocommerce_session()) {
            return $this->send_error('WooCommerce not available', 500);
        }
        
        // Get parameters based on request method
        $parameters = $request->get_params();
        $cart_item_key = isset($parameters['cart_item_key']) ? sanitize_text_field($parameters['cart_item_key']) : '';
        
        // For DELETE requests, also check query parameters
        if (empty($cart_item_key) && $request->get_method() === 'DELETE') {
            $query_params = $request->get_query_params();
            $cart_item_key = isset($query_params['cart_item_key']) ? sanitize_text_field($query_params['cart_item_key']) : '';
        }
        
        if (empty($cart_item_key)) {
            return $this->send_error('Cart item key is required', 400);
        }
        
        // Get current cart state
        $cart_items = WC()->cart->get_cart();
        $previous_count = WC()->cart->get_cart_contents_count();
        
        if (!isset($cart_items[$cart_item_key])) {
            // Provide more helpful error message
            $available_items = array();
            foreach ($cart_items as $key => $item) {
                $product_name = 'Unknown Product';
                if (isset($item['data']) && is_a($item['data'], 'WC_Product')) {
                    $product_name = $item['data']->get_name();
                }
                $available_items[] = "{$key} ({$product_name})";
            }
            
            return $this->send_error(
                'Cart item not found. Available items: ' . 
                (!empty($available_items) ? implode(', ', $available_items) : 'Cart is empty'), 
                404
            );
        }
        
        $cart_item = $cart_items[$cart_item_key];
        
        // Get product name with null safety
        $product_name = 'Unknown Product';
        $product_id = 0;
        
        if (isset($cart_item['data']) && is_a($cart_item['data'], 'WC_Product')) {
            $product = $cart_item['data'];
            $product_name = $product->get_name();
            $product_id = $product->get_id();
        } else if (isset($cart_item['product_id'])) {
            $product_id = $cart_item['product_id'];
            $product = wc_get_product($product_id);
            if ($product) {
                $product_name = $product->get_name();
            }
        }
        
        // Remove the item
        $result = WC()->cart->remove_cart_item($cart_item_key);
        
        if (!$result) {
            return $this->send_error('Failed to remove item from cart', 400);
        }
        
        $response_data = array(
            'removed_item' => array(
                'cart_item_key' => $cart_item_key,
                'product_id' => $product_id,
                'product_name' => $product_name,
                'quantity' => isset($cart_item['quantity']) ? $cart_item['quantity'] : 0
            ),
            'cart_summary' => array(
                'previous_item_count' => $previous_count,
                'current_item_count' => WC()->cart->get_cart_contents_count(),
                'total_items_removed' => $previous_count - WC()->cart->get_cart_contents_count(),
                'is_empty' => WC()->cart->is_empty()
            ),
            'remaining_items' => array_keys(WC()->cart->get_cart())
        );
        
        // Clear cart cache
        $this->clear_cart_cache();
        
        return $this->send_success($response_data, 200, 'Item removed from cart successfully');
    }
    
    public function clear_cart($request) {
        if (!$this->initialize_woocommerce_session()) {
            return $this->send_error('WooCommerce not available', 500);
        }
        
        $items_count = WC()->cart->get_cart_contents_count();
        WC()->cart->empty_cart();
        
        $response_data = array(
            'cleared_items' => $items_count,
            'item_count' => 0
        );
        
        // Clear cart cache
        $this->clear_cart_cache();
        
        return $this->send_success($response_data, 200, 'Cart cleared successfully');
    }
    
    public function get_cart_count($request) {
        if (!$this->initialize_woocommerce_session()) {
            return $this->send_error('WooCommerce not available', 500);
        }
        
        $count = WC()->cart->get_cart_contents_count();
        $total = WC()->cart->get_cart_total();
        
        return $this->send_success(array(
            'count' => $count,
            'total' => html_entity_decode(strip_tags($total))
        ));
    }
    
    public function apply_coupon($request) {
        if (!$this->initialize_woocommerce_session()) {
            return $this->send_error('WooCommerce not available', 500);
        }
        
        $parameters = $request->get_params();
        $coupon_code = isset($parameters['coupon_code']) ? sanitize_text_field($parameters['coupon_code']) : '';
        
        if (empty($coupon_code)) {
            return $this->send_error('Coupon code is required', 400);
        }
        
        // Check if coupon exists and is valid
        $coupon = new WC_Coupon($coupon_code);
        
        if (!$coupon->get_id()) {
            return $this->send_error('Invalid coupon code', 400);
        }
        
        // Apply coupon
        $result = WC()->cart->apply_coupon($coupon_code);
        
        if (!$result) {
            return $this->send_error('Failed to apply coupon', 400);
        }
        
        // Clear cart cache
        $this->clear_cart_cache();
        
        return $this->send_success(null, 200, 'Coupon applied successfully');
    }
    
    public function remove_coupon($request) {
        if (!$this->initialize_woocommerce_session()) {
            return $this->send_error('WooCommerce not available', 500);
        }
        
        $parameters = $request->get_params();
        $coupon_code = isset($parameters['coupon_code']) ? sanitize_text_field($parameters['coupon_code']) : '';
        
        if (empty($coupon_code)) {
            return $this->send_error('Coupon code is required', 400);
        }
        
        // Remove coupon
        $result = WC()->cart->remove_coupon($coupon_code);
        
        if (!$result) {
            return $this->send_error('Failed to remove coupon', 400);
        }
        
        // Clear cart cache
        $this->clear_cart_cache();
        
        return $this->send_success(null, 200, 'Coupon removed successfully');
    }
    
    /**
     * Get cart item attributes with better formatting
     */
    private function get_cart_item_attributes($cart_item) {
        $attributes = array();
        
        if (!empty($cart_item['variation'])) {
            foreach ($cart_item['variation'] as $key => $value) {
                $clean_key = str_replace('attribute_', '', $key);
                $attribute_name = $this->get_attribute_display_name($clean_key, 'name');
                $attribute_value = $this->get_attribute_display_name($clean_key, $value);
                
                $attributes[] = array(
                    'key' => $clean_key,
                    'name' => $attribute_name,
                    'value' => $value,
                    'display_name' => $attribute_name,
                    'display_value' => $attribute_value
                );
            }
        }
        
        // Also get product attributes
        if (isset($cart_item['data']) && is_a($cart_item['data'], 'WC_Product')) {
            $product = $cart_item['data'];
            $product_attributes = $product->get_attributes();
            
            foreach ($product_attributes as $attribute_name => $attribute) {
                if ($attribute->is_taxonomy()) {
                    $terms = wp_get_post_terms($product->get_id(), $attribute_name);
                    if (!empty($terms) && !is_wp_error($terms)) {
                        $term_names = wp_list_pluck($terms, 'name');
                        $attributes[] = array(
                            'key' => $attribute_name,
                            'name' => wc_attribute_label($attribute_name),
                            'value' => implode(', ', $term_names),
                            'display_name' => wc_attribute_label($attribute_name),
                            'display_value' => implode(', ', $term_names),
                            'is_taxonomy' => true
                        );
                    }
                } else {
                    $attributes[] = array(
                        'key' => $attribute_name,
                        'name' => wc_attribute_label($attribute_name),
                        'value' => $attribute->get_options(),
                        'display_name' => wc_attribute_label($attribute_name),
                        'display_value' => implode(', ', $attribute->get_options()),
                        'is_taxonomy' => false
                    );
                }
            }
        }
        
        return $attributes;
    }

    /**
     * Get attribute display name
     */
    private function get_attribute_display_name($attribute, $value) {
        $taxonomy = wc_attribute_taxonomy_name($attribute);
        
        if (taxonomy_exists($taxonomy)) {
            // For attribute name
            if ($value === 'name') {
                $attribute_taxonomy = wc_get_attribute(wc_attribute_taxonomy_id_by_name($attribute));
                return $attribute_taxonomy ? $attribute_taxonomy->name : $attribute;
            }
            
            // For attribute value
            $term = get_term_by('slug', $value, $taxonomy);
            if ($term && !is_wp_error($term)) {
                return $term->name;
            }
        }
        
        return $value;
    }

    private function get_token_from_request($request) {
        $auth_header = $request->get_header('Authorization');
        if ($auth_header && preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return sanitize_text_field(trim($matches[1]));
        }
        
        $x_auth_token = $request->get_header('X-Auth-Token');
        if ($x_auth_token) {
            return sanitize_text_field($x_auth_token);
        }
        
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
                
                if ($token_data['expires'] < time()) {
                    unset($tokens[$token]);
                    update_user_meta($user_id, 'ecommerce_api_session_tokens', $tokens);
                    return false;
                }
                
                $tokens[$token]['last_used'] = time();
                update_user_meta($user_id, 'ecommerce_api_session_tokens', $tokens);
                
                return $user_id;
            }
        }
        
        return false;
    }
    
    private function clear_cart_cache() {
        $user_id = get_current_user_id();
        if ($this->cache_manager) {
            $this->cache_manager->delete("user_cart_{$user_id}");
        }
    }
    
    private function log_error($message, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Cart_API Error: ' . $message . ' - Context: ' . json_encode($context));
        }
    }
    
    private function send_success($data = null, $status = 200, $message = '') {
        $response = array(
            'success' => true,
            'message' => $message,
            'data' => $data
        );
        
        return new WP_REST_Response($response, $status);
    }
    
    private function send_error($message, $status = 400) {
        return new WP_Error(
            'cart_api_error',
            $message,
            array('status' => $status)
        );
    }
}
?>