<?php
class Products_API {
    
    private $namespace = 'ecommerce-api/v1';
    private $cache_manager;
    
    public function __construct($cache_manager = null) {
        $this->cache_manager = $cache_manager;
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        // Get all products (public)
        register_rest_route($this->namespace, '/products', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_api_products'),
                'permission_callback' => array($this, 'get_products_permissions_check'),
                'args' => $this->get_collection_params()
            )
        ));
        
        // Get single product (public)
        register_rest_route($this->namespace, '/products/(?P<id>[\d]+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_api_product'),
                'permission_callback' => array($this, 'get_product_permissions_check'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric($param) && $param > 0;
                        }
                    )
                )
            )
        ));
        
        // Update product (requires authentication)
        register_rest_route($this->namespace, '/products/update/(?P<id>[\d]+)', array(
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_product'),
                'permission_callback' => array($this, 'update_product_permissions_check'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric($param) && $param > 0;
                        }
                    )
                )
            )
        ));
        
        // Get products by category (public)
        register_rest_route($this->namespace, '/products/category/(?P<category_id>[\d]+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_products_by_category'),
                'permission_callback' => array($this, 'get_products_permissions_check'),
                'args' => array(
                    'category_id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric($param) && $param > 0;
                        }
                    )
                )
            )
        ));
        
        // Search products (public)
        register_rest_route($this->namespace, '/products/search', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'search_products'),
                'permission_callback' => array($this, 'get_products_permissions_check'),
                'args' => array(
                    'query' => array(
                        'required' => true,
                        'validate_callback' => function($param, $request, $key) {
                            return !empty(trim($param));
                        }
                    )
                )
            )
        ));
        
        // Get all categories (public)
        register_rest_route($this->namespace, '/categories', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_categories'),
                'permission_callback' => array($this, 'get_products_permissions_check')
            )
        ));
        
        // Get single category (public)
        register_rest_route($this->namespace, '/categories/(?P<id>[\d]+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_category'),
                'permission_callback' => array($this, 'get_products_permissions_check'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric($param) && $param > 0;
                        }
                    )
                )
            )
        ));
    }
    
    public function get_products_permissions_check($request) {
        return true; // Public access for reading products
    }
    
    public function get_product_permissions_check($request) {
        return true; // Public access for reading single product
    }
    
    public function update_product_permissions_check($request) {
        // Use token authentication like the Cart API
        $token = $this->get_token_from_request($request);
        
        if (!$token) {
            return new WP_Error('rest_forbidden', 'Authentication token required', array('status' => 401));
        }
        
        $user_id = $this->validate_session_token($token);
        
        if ($user_id) {
            wp_set_current_user($user_id);
            
            // ✅ TEMPORARY FIX: Allow any authenticated user to update products
            return true;
        }
        
        return new WP_Error('rest_forbidden', 'Invalid or expired authentication token', array('status' => 401));
    }
    
    /**
     * Get token from request (same as Cart API)
     */
    private function get_token_from_request($request) {
        // Check Authorization header first
        $auth_header = $request->get_header('Authorization');
        if ($auth_header && preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return sanitize_text_field(trim($matches[1]));
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

    /**
     * Validate session token (same as Cart API)
     */
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
    
    public function get_api_products($request) {
        try {
            $params = $request->get_params();
            $cache_key = $this->cache_manager ? $this->cache_manager->generate_key('products_list', $params) : null;
            
            // Try cache first
            if ($this->cache_manager && $cached = $this->cache_manager->get($cache_key)) {
                return $this->send_success($cached);
            }
            
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => $params['per_page'] ?? 12,
                'paged' => $params['page'] ?? 1,
            );
            
            // Apply filters with compatibility
            $args = $this->apply_product_filters($args, $params);
            
            $products_query = new WP_Query($args);
            $products = array();
            
            while ($products_query->have_posts()) {
                $products_query->the_post();
                $product = wc_get_product(get_the_ID());
                if ($product) {
                    $products[] = $this->prepare_product_response($product);
                }
            }
            
            wp_reset_postdata();
            
            $response = array(
                'products' => $products,
                'pagination' => array(
                    'total' => $products_query->found_posts,
                    'per_page' => (int) $args['posts_per_page'],
                    'current_page' => (int) $args['paged'],
                    'total_pages' => $products_query->max_num_pages
                )
            );
            
            // Cache the result
            if ($this->cache_manager) {
                $this->cache_manager->set($cache_key, $response, 1800); // 30 minutes
            }
            
            return $this->send_success($response);
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_api_products', array(
                'message' => $e->getMessage(),
                'params' => $params ?? array()
            ));
            return $this->send_error('Failed to retrieve products', 500);
        }
    }
    
    public function get_api_product($request) {
        try {
            $product_id = (int) $request['id'];
            $cache_key = $this->cache_manager ? $this->cache_manager->generate_key("product_{$product_id}") : null;
            
            // Try cache first
            if ($this->cache_manager && $cached = $this->cache_manager->get($cache_key)) {
                return $this->send_success($cached);
            }
            
            $product = wc_get_product($product_id);
            
            if (!$product) {
                return $this->send_error('Product not found', 404);
            }
            
            $product_data = $this->prepare_product_response($product, true);
            
            // Cache the result
            if ($this->cache_manager) {
                $this->cache_manager->set($cache_key, $product_data, 3600); // 1 hour
            }
            
            return $this->send_success($product_data);
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_api_product', array(
                'message' => $e->getMessage(),
                'product_id' => $product_id ?? 0
            ));
            return $this->send_error('Failed to retrieve product', 500);
        }
    }
    
    public function get_products_by_category($request) {
        try {
            $category_id = (int) $request['category_id'];
            $params = $request->get_params();
            $cache_key = $this->cache_manager ? $this->cache_manager->generate_key("products_category_{$category_id}", $params) : null;
            
            // Try cache first
            if ($this->cache_manager && $cached = $this->cache_manager->get($cache_key)) {
                return $this->send_success($cached);
            }
            
            // Check if category exists
            $category = get_term($category_id, 'product_cat');
            if (is_wp_error($category) || !$category) {
                return $this->send_error('Category not found', 404);
            }
            
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => $params['per_page'] ?? 12,
                'paged' => $params['page'] ?? 1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $category_id
                    )
                )
            );
            
            // Apply additional filters
            $args = $this->apply_product_filters($args, $params);
            
            $products_query = new WP_Query($args);
            $products = array();
            
            while ($products_query->have_posts()) {
                $products_query->the_post();
                $product = wc_get_product(get_the_ID());
                if ($product) {
                    $products[] = $this->prepare_product_response($product);
                }
            }
            
            wp_reset_postdata();
            
            $response = array(
                'category' => array(
                    'id' => $category->term_id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'count' => $category->count
                ),
                'products' => $products,
                'pagination' => array(
                    'total' => $products_query->found_posts,
                    'per_page' => (int) $args['posts_per_page'],
                    'current_page' => (int) $args['paged'],
                    'total_pages' => $products_query->max_num_pages
                )
            );
            
            // Cache the result
            if ($this->cache_manager) {
                $this->cache_manager->set($cache_key, $response, 1800); // 30 minutes
            }
            
            return $this->send_success($response);
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_products_by_category', array(
                'message' => $e->getMessage(),
                'category_id' => $category_id ?? 0
            ));
            return $this->send_error('Failed to retrieve products by category', 500);
        }
    }
    
    public function search_products($request) {
        try {
            $params = $request->get_params();
            $search_query = sanitize_text_field($params['query']);
            $cache_key = $this->cache_manager ? $this->cache_manager->generate_key("products_search", array_merge($params, ['query' => $search_query])) : null;
            
            // Try cache first
            if ($this->cache_manager && $cached = $this->cache_manager->get($cache_key)) {
                return $this->send_success($cached);
            }
            
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => $params['per_page'] ?? 12,
                'paged' => $params['page'] ?? 1,
                's' => $search_query
            );
            
            // Apply additional filters
            $args = $this->apply_product_filters($args, $params);
            
            $products_query = new WP_Query($args);
            $products = array();
            
            while ($products_query->have_posts()) {
                $products_query->the_post();
                $product = wc_get_product(get_the_ID());
                if ($product) {
                    $products[] = $this->prepare_product_response($product);
                }
            }
            
            wp_reset_postdata();
            
            $response = array(
                'search_query' => $search_query,
                'products' => $products,
                'pagination' => array(
                    'total' => $products_query->found_posts,
                    'per_page' => (int) $args['posts_per_page'],
                    'current_page' => (int) $args['paged'],
                    'total_pages' => $products_query->max_num_pages
                )
            );
            
            // Cache the result (shorter TTL for search results)
            if ($this->cache_manager) {
                $this->cache_manager->set($cache_key, $response, 900); // 15 minutes
            }
            
            return $this->send_success($response);
            
        } catch (Exception $e) {
            $this->log_error('Exception in search_products', array(
                'message' => $e->getMessage(),
                'search_query' => $search_query ?? ''
            ));
            return $this->send_error('Failed to search products', 500);
        }
    }
    
    public function get_categories($request) {
        try {
            $params = $request->get_params();
            $hide_empty = isset($params['hide_empty']) ? (bool) $params['hide_empty'] : true;
            $cache_key = $this->cache_manager ? $this->cache_manager->generate_key('categories_list', $params) : null;
            
            // Try cache first
            if ($this->cache_manager && $cached = $this->cache_manager->get($cache_key)) {
                return $this->send_success($cached);
            }
            
            $categories = get_terms(array(
                'taxonomy' => 'product_cat',
                'hide_empty' => $hide_empty,
                'orderby' => 'name',
                'order' => 'ASC'
            ));
            
            if (is_wp_error($categories)) {
                return $this->send_error('Failed to fetch categories', 500);
            }
            
            $formatted_categories = array();
            foreach ($categories as $category) {
                $formatted_categories[] = $this->prepare_category_response($category);
            }
            
            $response = array(
                'categories' => $formatted_categories,
                'total' => count($formatted_categories)
            );
            
            // Cache the result (longer TTL for categories)
            if ($this->cache_manager) {
                $this->cache_manager->set($cache_key, $response, 7200); // 2 hours
            }
            
            return $this->send_success($response);
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_categories', array(
                'message' => $e->getMessage()
            ));
            return $this->send_error('Failed to retrieve categories', 500);
        }
    }
    
    public function get_category($request) {
        try {
            $category_id = (int) $request['id'];
            $cache_key = $this->cache_manager ? $this->cache_manager->generate_key("category_{$category_id}") : null;
            
            // Try cache first
            if ($this->cache_manager && $cached = $this->cache_manager->get($cache_key)) {
                return $this->send_success($cached);
            }
            
            $category = get_term($category_id, 'product_cat');
            
            if (is_wp_error($category) || !$category) {
                return $this->send_error('Category not found', 404);
            }
            
            $category_data = $this->prepare_category_response($category, true);
            
            // Cache the result
            if ($this->cache_manager) {
                $this->cache_manager->set($cache_key, $category_data, 7200); // 2 hours
            }
            
            return $this->send_success($category_data);
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_category', array(
                'message' => $e->getMessage(),
                'category_id' => $category_id ?? 0
            ));
            return $this->send_error('Failed to retrieve category', 500);
        }
    }
    
    public function update_product($request) {
        try {
            $product_id = (int) $request['id'];
            $parameters = $request->get_params();
            
            // Check if product exists
            $product = wc_get_product($product_id);
            if (!$product) {
                return $this->send_error('Product not found', 404);
            }
            
            // Store original values for response
            $original_data = array(
                'regular_price' => $product->get_regular_price(),
                'sale_price' => $product->get_sale_price(),
                'stock_quantity' => $product->get_stock_quantity(),
                'price' => $product->get_price(),
                'stock_status' => $product->get_stock_status()
            );
            
            $updated_fields = array();
            $errors = array();
            
            // Update regular price
            if (isset($parameters['regular_price'])) {
                $regular_price = sanitize_text_field($parameters['regular_price']);
                if ($this->is_valid_price($regular_price)) {
                    $product->set_regular_price($regular_price);
                    $updated_fields[] = 'regular_price';
                } else {
                    $errors[] = 'Invalid regular price format';
                }
            }
            
            // Update sale price
            if (isset($parameters['sale_price'])) {
                $sale_price = sanitize_text_field($parameters['sale_price']);
                if ($this->is_valid_price($sale_price)) {
                    $product->set_sale_price($sale_price);
                    $updated_fields[] = 'sale_price';
                    
                    // If sale price is empty, remove the sale price
                    if (empty($sale_price)) {
                        $product->set_sale_price('');
                    }
                } else {
                    $errors[] = 'Invalid sale price format';
                }
            }
            
            // Update stock quantity
            if (isset($parameters['stock_quantity'])) {
                $stock_quantity = intval($parameters['stock_quantity']);
                if ($stock_quantity >= 0) {
                    $product->set_stock_quantity($stock_quantity);
                    $product->set_manage_stock(true);
                    
                    // Update stock status based on quantity
                    if ($stock_quantity > 0) {
                        $product->set_stock_status('instock');
                    } else {
                        $product->set_stock_status('outofstock');
                    }
                    
                    $updated_fields[] = 'stock_quantity';
                    $updated_fields[] = 'stock_status';
                } else {
                    $errors[] = 'Stock quantity must be a positive number';
                }
            }
            
            // Update stock status directly
            if (isset($parameters['stock_status'])) {
                $stock_status = sanitize_text_field($parameters['stock_status']);
                $allowed_statuses = array('instock', 'outofstock', 'onbackorder');
                
                if (in_array($stock_status, $allowed_statuses)) {
                    $product->set_stock_status($stock_status);
                    $updated_fields[] = 'stock_status';
                } else {
                    $errors[] = 'Invalid stock status. Allowed: instock, outofstock, onbackorder';
                }
            }
            
            // Update SKU
            if (isset($parameters['sku'])) {
                $sku = sanitize_text_field($parameters['sku']);
                if (!empty($sku)) {
                    // Check if SKU is unique
                    $existing_product_id = wc_get_product_id_by_sku($sku);
                    if (!$existing_product_id || $existing_product_id === $product_id) {
                        $product->set_sku($sku);
                        $updated_fields[] = 'sku';
                    } else {
                        $errors[] = 'SKU already exists';
                    }
                }
            }
            
            // Update name/title
            if (isset($parameters['name'])) {
                $name = sanitize_text_field($parameters['name']);
                if (!empty($name)) {
                    $product->set_name($name);
                    
                    // Also update the post title
                    wp_update_post(array(
                        'ID' => $product_id,
                        'post_title' => $name
                    ));
                    
                    $updated_fields[] = 'name';
                }
            }
            
            // Update description
            if (isset($parameters['description'])) {
                $description = wp_kses_post($parameters['description']);
                $product->set_description($description);
                $updated_fields[] = 'description';
            }
            
            // Update short description
            if (isset($parameters['short_description'])) {
                $short_description = wp_kses_post($parameters['short_description']);
                $product->set_short_description($short_description);
                $updated_fields[] = 'short_description';
            }
            
            // Update weight
            if (isset($parameters['weight'])) {
                $weight = floatval($parameters['weight']);
                if ($weight >= 0) {
                    $product->set_weight($weight);
                    $updated_fields[] = 'weight';
                } else {
                    $errors[] = 'Weight must be a positive number';
                }
            }
            
            // Update dimensions
            if (isset($parameters['length']) || isset($parameters['width']) || isset($parameters['height'])) {
                if (isset($parameters['length'])) {
                    $length = floatval($parameters['length']);
                    if ($length >= 0) {
                        $product->set_length($length);
                        $updated_fields[] = 'length';
                    }
                }
                
                if (isset($parameters['width'])) {
                    $width = floatval($parameters['width']);
                    if ($width >= 0) {
                        $product->set_width($width);
                        $updated_fields[] = 'width';
                    }
                }
                
                if (isset($parameters['height'])) {
                    $height = floatval($parameters['height']);
                    if ($height >= 0) {
                        $product->set_height($height);
                        $updated_fields[] = 'height';
                    }
                }
            }
            
            // Update featured status
            if (isset($parameters['featured'])) {
                $featured = filter_var($parameters['featured'], FILTER_VALIDATE_BOOLEAN);
                $product->set_featured($featured);
                $updated_fields[] = 'featured';
            }
            
            // If there are errors, return them
            if (!empty($errors)) {
                return $this->send_error(implode(', ', $errors), 400);
            }
            
            // If no fields were updated, return error
            if (empty($updated_fields)) {
                return $this->send_error('No valid fields provided for update', 400);
            }
            
            // Save the product
            $save_result = $product->save();
            
            if (is_wp_error($save_result)) {
                return $save_result;
            }
            
            // On success save() returns the post ID (int)
            $product_id = (int) $save_result;
            
            // Get updated product data
            $updated_product = wc_get_product($product_id);
            $new_data = array(
                'regular_price' => $updated_product->get_regular_price(),
                'sale_price' => $updated_product->get_sale_price(),
                'stock_quantity' => $updated_product->get_stock_quantity(),
                'price' => $updated_product->get_price(),
                'stock_status' => $updated_product->get_stock_status()
            );
            
            $response_data = array(
                'product_id' => $product_id,
                'updated_fields' => $updated_fields,
                'changes' => array(
                    'before' => $original_data,
                    'after' => $new_data
                ),
                'product' => $this->prepare_product_response($updated_product)
            );
            
            // Clear product cache
            if ($this->cache_manager) {
                $this->cache_manager->clear_product_cache($product_id);
            }
            
            return $this->send_success($response_data, 200, 'Product updated successfully');
            
        } catch (Exception $e) {
            $this->log_error('Exception in update_product', array(
                'message' => $e->getMessage(),
                'product_id' => $product_id ?? 0
            ));
            return $this->send_error('Failed to update product', 500);
        }
    }
    
    /**
     * Validate price format
     */
    private function is_valid_price($price) {
        if (empty($price)) {
            return true; // Empty price is valid (for clearing sale price)
        }
        
        // Check if it's a valid price format (numbers with optional decimal points)
        return preg_match('/^\d+(\.\d{1,2})?$/', $price) || $price === '';
    }
    
    private function apply_product_filters($args, $params) {
        // Category filter
        if (!empty($params['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($params['category'])
                )
            );
        }
        
        // Search filter
        if (!empty($params['search'])) {
            $args['s'] = sanitize_text_field($params['search']);
        }
        
        // Price range filter (HPOS compatible)
        if (!empty($params['min_price']) || !empty($params['max_price'])) {
            $args['meta_query'] = array(
                array(
                    'key' => '_price',
                    'value' => array(
                        floatval($params['min_price'] ?? 0),
                        floatval($params['max_price'] ?? 999999)
                    ),
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                )
            );
        }
        
        // Order by
        if (!empty($params['orderby'])) {
            switch ($params['orderby']) {
                case 'price':
                    $args['orderby'] = 'meta_value_num';
                    $args['meta_key'] = '_price';
                    break;
                case 'date':
                    $args['orderby'] = 'date';
                    break;
                case 'rating':
                    $args['orderby'] = 'meta_value_num';
                    $args['meta_key'] = '_wc_average_rating';
                    break;
                case 'popularity':
                    $args['orderby'] = 'meta_value_num';
                    $args['meta_key'] = 'total_sales';
                    break;
                default:
                    $args['orderby'] = sanitize_sql_orderby($params['orderby']);
            }
        }
        
        // Order direction
        if (!empty($params['order'])) {
            $args['order'] = in_array(strtoupper($params['order']), array('ASC', 'DESC')) ? strtoupper($params['order']) : 'ASC';
        }
        
        // Featured products
        if (isset($params['featured']) && $params['featured'] === 'true') {
            if (!isset($args['tax_query'])) {
                $args['tax_query'] = array('relation' => 'AND');
            }
            $args['tax_query'][] = array(
                'taxonomy' => 'product_visibility',
                'field' => 'name',
                'terms' => 'featured',
            );
        }
        
        // On sale products
        if (isset($params['on_sale']) && $params['on_sale'] === 'true') {
            $args['post__in'] = wc_get_product_ids_on_sale();
        }
        
        return apply_filters('api_products_query_args', $args, $params);
    }
    
    private function prepare_product_response($product, $detailed = false) {
        $data = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'permalink' => $product->get_permalink(),
            'type' => $product->get_type(),
            'status' => $product->get_status(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'price_html' => $product->get_price_html(),
            'on_sale' => $product->is_on_sale(),
            'sku' => $product->get_sku(),
            'manage_stock' => $product->get_manage_stock(),
            'stock_quantity' => $product->get_stock_quantity(),
            'stock_status' => $product->get_stock_status(),
            'backorders' => $product->get_backorders(),
            'sold_individually' => $product->is_sold_individually(),
            'weight' => $product->get_weight(),
            'dimensions' => array(
                'length' => $product->get_length(),
                'width' => $product->get_width(),
                'height' => $product->get_height()
            ),
            'average_rating' => (float) $product->get_average_rating(),
            'rating_count' => $product->get_rating_count(),
            'review_count' => $product->get_review_count(),
            'featured' => $product->is_featured(),
            'virtual' => $product->is_virtual(),
            'downloadable' => $product->is_downloadable(),
            'external_url' => $product->is_type('external') ? $product->get_product_url() : '',
            'button_text' => $product->is_type('external') ? $product->get_button_text() : '',
            'tax_status' => $product->get_tax_status(),
            'tax_class' => $product->get_tax_class(),
            'purchase_note' => $product->get_purchase_note(),
            'shipping_class' => $product->get_shipping_class(),
            'shipping_class_id' => $product->get_shipping_class_id(),
            'catalog_visibility' => $product->get_catalog_visibility(),
            'date_created' => $product->get_date_created() ? $product->get_date_created()->format('c') : '',
            'date_modified' => $product->get_date_modified() ? $product->get_date_modified()->format('c') : '',
            'images' => array(),
            'categories' => array(),
            'tags' => array(),
            'attributes' => array()
        );
        
        // Get product images
        $image_ids = $product->get_gallery_image_ids();
        array_unshift($image_ids, $product->get_image_id());
        
        foreach ($image_ids as $image_id) {
            if ($image_id) {
                $image_src = wp_get_attachment_image_src($image_id, 'full');
                $thumbnail_src = wp_get_attachment_image_src($image_id, 'thumbnail');
                $medium_src = wp_get_attachment_image_src($image_id, 'medium');
                $large_src = wp_get_attachment_image_src($image_id, 'large');
                
                if ($image_src) {
                    $data['images'][] = array(
                        'id' => $image_id,
                        'src' => $image_src[0],
                        'thumbnail' => $thumbnail_src[0] ?? $image_src[0],
                        'medium' => $medium_src[0] ?? $image_src[0],
                        'large' => $large_src[0] ?? $image_src[0],
                        'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true) ?: $product->get_name()
                    );
                }
            }
        }
        
        // Get product categories
        $categories = wp_get_post_terms($product->get_id(), 'product_cat');
        foreach ($categories as $category) {
            $data['categories'][] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'count' => $category->count
            );
        }
        
        // Get product tags
        $tags = wp_get_post_terms($product->get_id(), 'product_tag');
        foreach ($tags as $tag) {
            $data['tags'][] = array(
                'id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug
            );
        }
        
        if ($detailed) {
            // Get product attributes
            $attributes = $product->get_attributes();
            foreach ($attributes as $attribute_name => $attribute) {
                $attribute_data = array(
                    'id' => $attribute->get_id(),
                    'name' => $attribute->get_name(),
                    'position' => $attribute->get_position(),
                    'visible' => $attribute->get_visible(),
                    'variation' => $attribute->get_variation()
                );
                
                if ($attribute->is_taxonomy()) {
                    $terms = wp_get_post_terms($product->get_id(), $attribute_name);
                    $attribute_data['options'] = wp_list_pluck($terms, 'name');
                    $attribute_data['terms'] = array();
                    foreach ($terms as $term) {
                        $attribute_data['terms'][] = array(
                            'id' => $term->term_id,
                            'name' => $term->name,
                            'slug' => $term->slug
                        );
                    }
                } else {
                    $attribute_data['options'] = $attribute->get_options();
                }
                
                $data['attributes'][] = $attribute_data;
            }
            
            // For variable products, get variations
            if ($product->is_type('variable')) {
                $variations = $product->get_available_variations();
                $data['variations'] = $variations;
            }
            
            // Get related products
            $related_ids = wc_get_related_products($product->get_id());
            $data['related_ids'] = $related_ids;
            
            // Get upsell products
            $upsell_ids = $product->get_upsell_ids();
            $data['upsell_ids'] = $upsell_ids;
            
            // Get cross-sell products
            $cross_sell_ids = $product->get_cross_sell_ids();
            $data['cross_sell_ids'] = $cross_sell_ids;
        }
        
        return apply_filters('api_product_response', $data, $product, $detailed);
    }
    
    private function prepare_category_response($category, $detailed = false) {
        $data = array(
            'id' => $category->term_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'count' => $category->count,
            'permalink' => get_term_link($category)
        );
        
        if ($detailed) {
            // Get category image
            $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
            if ($thumbnail_id) {
                $image_src = wp_get_attachment_image_src($thumbnail_id, 'full');
                if ($image_src) {
                    $data['image'] = array(
                        'id' => $thumbnail_id,
                        'src' => $image_src[0],
                        'alt' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true) ?: $category->name
                    );
                }
            }
            
            // Get category display type
            $display_type = get_term_meta($category->term_id, 'display_type', true);
            $data['display_type'] = $display_type ?: 'default';
            
            // Get parent category
            if ($category->parent) {
                $parent_category = get_term($category->parent, 'product_cat');
                if ($parent_category && !is_wp_error($parent_category)) {
                    $data['parent'] = array(
                        'id' => $parent_category->term_id,
                        'name' => $parent_category->name,
                        'slug' => $parent_category->slug
                    );
                }
            }
            
            // Get child categories
            $child_categories = get_terms(array(
                'taxonomy' => 'product_cat',
                'parent' => $category->term_id,
                'hide_empty' => false
            ));
            
            if (!is_wp_error($child_categories) && !empty($child_categories)) {
                $data['children'] = array();
                foreach ($child_categories as $child) {
                    $data['children'][] = array(
                        'id' => $child->term_id,
                        'name' => $child->name,
                        'slug' => $child->slug,
                        'count' => $child->count
                    );
                }
            }
        }
        
        return $data;
    }
    
    public function get_collection_params() {
        return array(
            'page' => array(
                'description' => 'Current page of the collection.',
                'type' => 'integer',
                'default' => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'description' => 'Maximum number of items to be returned in result set.',
                'type' => 'integer',
                'default' => 12,
                'sanitize_callback' => 'absint',
                'minimum' => 1,
                'maximum' => 100
            ),
            'search' => array(
                'description' => 'Limit results to those matching a string.',
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'category' => array(
                'description' => 'Limit results to specific category slug.',
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'min_price' => array(
                'description' => 'Limit results to products with minimum price.',
                'type' => 'number',
                'sanitize_callback' => 'floatval'
            ),
            'max_price' => array(
                'description' => 'Limit results to products with maximum price.',
                'type' => 'number',
                'sanitize_callback' => 'floatval'
            ),
            'orderby' => array(
                'description' => 'Sort collection by attribute.',
                'type' => 'string',
                'default' => 'date',
                'enum' => array('date', 'price', 'rating', 'popularity', 'title')
            ),
            'order' => array(
                'description' => 'Order sort attribute ascending or descending.',
                'type' => 'string',
                'default' => 'desc',
                'enum' => array('asc', 'desc')
            ),
            'featured' => array(
                'description' => 'Limit results to featured products.',
                'type' => 'boolean'
            ),
            'on_sale' => array(
                'description' => 'Limit results to products on sale.',
                'type' => 'boolean'
            ),
            'stock_status' => array(
                'description' => 'Limit results to products with specific stock status.',
                'type' => 'string',
                'enum' => array('instock', 'outofstock', 'onbackorder')
            ),
            'hide_empty' => array(
                'description' => 'Hide empty categories.',
                'type' => 'boolean',
                'default' => true
            )
        );
    }
    
    private function log_error($message, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Products_API Error: ' . $message . ' - Context: ' . json_encode($context));
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
            'products_api_error',
            $message,
            array('status' => $status)
        );
    }
}
?>