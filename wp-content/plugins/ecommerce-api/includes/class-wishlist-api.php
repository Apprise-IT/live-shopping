<?php

class Wishlist_API {
    
    private $namespace = 'ecommerce-api/v1';
    private $max_wishlist_items = 100;
    private $rate_limit_attempts = 10;
    private $rate_limit_timeframe = 60; // seconds
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        
        // Allow customization of limits via filters
        $this->max_wishlist_items = apply_filters('wishlist_max_items', $this->max_wishlist_items);
        $this->rate_limit_attempts = apply_filters('wishlist_rate_limit_attempts', $this->rate_limit_attempts);
        $this->rate_limit_timeframe = apply_filters('wishlist_rate_limit_timeframe', $this->rate_limit_timeframe);
    }
    
    public function register_routes() {
        // Get wishlist
        register_rest_route($this->namespace, '/wishlist', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_wishlist'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        // Add to wishlist
        register_rest_route($this->namespace, '/wishlist/add', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_to_wishlist'),
            'permission_callback' => array($this, 'check_auth'),
            'args' => array(
                'product_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint'
                )
            )
        ));
        
        // Bulk add to wishlist
        register_rest_route($this->namespace, '/wishlist/bulk-add', array(
            'methods' => 'POST',
            'callback' => array($this, 'bulk_add_to_wishlist'),
            'permission_callback' => array($this, 'check_auth'),
            'args' => array(
                'product_ids' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        if (!is_array($param)) return false;
                        foreach ($param as $product_id) {
                            if (!is_numeric($product_id) || $product_id <= 0) return false;
                        }
                        return true;
                    },
                    'sanitize_callback' => function($param) {
                        return array_map('absint', $param);
                    }
                )
            )
        ));
        
        // Remove from wishlist - DELETE method
        register_rest_route($this->namespace, '/wishlist/remove', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'remove_from_wishlist'),
            'permission_callback' => array($this, 'check_auth'),
            'args' => array(
                'product_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint'
                )
            )
        ));
        
        // Remove from wishlist - POST method (alternative)
        register_rest_route($this->namespace, '/wishlist/remove', array(
            'methods' => 'POST',
            'callback' => array($this, 'remove_from_wishlist'),
            'permission_callback' => array($this, 'check_auth'),
            'args' => array(
                'product_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint'
                )
            )
        ));
        
        // Bulk remove from wishlist
        register_rest_route($this->namespace, '/wishlist/bulk-remove', array(
            'methods' => 'POST',
            'callback' => array($this, 'bulk_remove_from_wishlist'),
            'permission_callback' => array($this, 'check_auth'),
            'args' => array(
                'product_ids' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        if (!is_array($param)) return false;
                        foreach ($param as $product_id) {
                            if (!is_numeric($product_id) || $product_id <= 0) return false;
                        }
                        return true;
                    },
                    'sanitize_callback' => function($param) {
                        return array_map('absint', $param);
                    }
                )
            )
        ));
        
        // Clear wishlist
        register_rest_route($this->namespace, '/wishlist/clear', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'clear_wishlist'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        // Get wishlist count
        register_rest_route($this->namespace, '/wishlist/count', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_wishlist_count'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        // Check if product is in wishlist
        register_rest_route($this->namespace, '/wishlist/check/(?P<product_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'check_wishlist'),
            'permission_callback' => array($this, 'check_auth'),
            'args' => array(
                'product_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint'
                )
            )
        ));
    }
    
    public function check_auth($request) {
        $token = $this->get_token_from_request($request);
        
        if (!$token) {
            return $this->send_error_response('authentication_required', 'Authentication token required', 401);
        }
        
        $user_id = $this->validate_session_token($token);
        
        if ($user_id) {
            wp_set_current_user($user_id);
            
            // Check rate limiting for all requests
            $rate_limit_check = $this->check_rate_limit($user_id, 'general');
            if (is_wp_error($rate_limit_check)) {
                return $rate_limit_check;
            }
            
            return true;
        }
        
        return $this->send_error_response('invalid_token', 'Invalid or expired authentication token', 401);
    }
    
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
                if (isset($token_data['expires']) && $token_data['expires'] < time()) {
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
    
    /**
     * Rate limiting implementation
     */
    private function check_rate_limit($user_id, $action) {
        $transient_key = 'wishlist_rate_limit_' . $user_id . '_' . $action;
        $attempts = get_transient($transient_key);
        
        if ($attempts && $attempts >= $this->rate_limit_attempts) {
            return $this->send_error_response(
                'rate_limit_exceeded', 
                'Too many requests. Please try again later.', 
                429,
                array('retry_after' => $this->rate_limit_timeframe)
            );
        }
        
        if (!$attempts) {
            $attempts = 0;
        }
        
        set_transient($transient_key, $attempts + 1, $this->rate_limit_timeframe);
        return true;
    }
    
    /**
     * Enhanced response helpers
     */
    private function send_error_response($code, $message, $status = 400, $additional_data = array()) {
        $data = array_merge(array(
            'success' => false,
            'error' => array(
                'code' => $code,
                'message' => $message,
                'status' => $status
            )
        ), $additional_data);
        
        return new WP_REST_Response($data, $status);
    }

    private function send_success_response($data = array(), $message = '', $status = 200) {
        $response = array(
            'success' => true,
            'data' => $data
        );
        
        if (!empty($message)) {
            $response['message'] = $message;
        }
        
        return new WP_REST_Response($response, $status);
    }
    
    public function get_wishlist($request) {
        try {
            $user_id = get_current_user_id();
            
            // Check rate limiting
            $rate_limit_check = $this->check_rate_limit($user_id, 'get_wishlist');
            if (is_wp_error($rate_limit_check)) {
                return $rate_limit_check;
            }
            
            $wishlist = $this->get_cached_wishlist($user_id);
            $wishlist_items = array();
            
            foreach ($wishlist as $product_id) {
                $product_data = $this->get_product_data($product_id);
                if ($product_data) {
                    $product_data['date_added'] = $this->get_wishlist_item_date($user_id, $product_id);
                    $wishlist_items[] = $product_data;
                }
            }
            
            // Sort by date added (newest first)
            usort($wishlist_items, function($a, $b) {
                return strtotime($b['date_added']) - strtotime($a['date_added']);
            });
            
            $response_data = array(
                'wishlist' => $wishlist_items,
                'count' => count($wishlist_items),
                'user_id' => $user_id,
                'max_items' => $this->max_wishlist_items
            );
            
            // Trigger action for extensibility
            $this->trigger_wishlist_action('retrieved', $user_id, null, $response_data);
            
            return $this->send_success_response($response_data);
            
        } catch (Exception $e) {
            return $this->send_error_response('server_error', $e->getMessage(), 500);
        }
    }
    
    public function add_to_wishlist($request) {
        try {
            $parameters = $request->get_params();
            $user_id = get_current_user_id();
            $product_id = absint($parameters['product_id']);
            
            // Check rate limiting
            $rate_limit_check = $this->check_rate_limit($user_id, 'add_to_wishlist');
            if (is_wp_error($rate_limit_check)) {
                return $rate_limit_check;
            }
            
            // Validate product
            $validation_result = $this->validate_product_for_wishlist($product_id);
            if (is_wp_error($validation_result)) {
                return $validation_result;
            }
            
            $wishlist = $this->get_cached_wishlist($user_id);
            
            // Check if product already in wishlist
            if (in_array($product_id, $wishlist)) {
                return $this->send_error_response('already_exists', 'Product already in wishlist', 400);
            }
            
            // Check wishlist limit
            if (!$this->can_add_to_wishlist($user_id)) {
                return $this->send_error_response(
                    'wishlist_full', 
                    sprintf('Wishlist cannot exceed %d items', $this->max_wishlist_items), 
                    400
                );
            }
            
            // Add product to wishlist
            $wishlist[] = $product_id;
            $this->update_user_wishlist($user_id, $wishlist);
            
            // Store addition date
            $this->set_wishlist_item_date($user_id, $product_id);
            
            $product = wc_get_product($product_id);
            $response_data = array(
                'product_id' => $product_id,
                'product_name' => $product ? $product->get_name() : 'Unknown Product',
                'wishlist_count' => count($wishlist),
                'in_wishlist' => true,
                'date_added' => $this->get_wishlist_item_date($user_id, $product_id)
            );
            
            // Trigger action for extensibility
            $this->trigger_wishlist_action('item_added', $user_id, $product_id, $response_data);
            
            return $this->send_success_response(
                $response_data, 
                'Product added to wishlist successfully'
            );
            
        } catch (Exception $e) {
            return $this->send_error_response('server_error', $e->getMessage(), 500);
        }
    }
    
    public function bulk_add_to_wishlist($request) {
        try {
            $parameters = $request->get_params();
            $user_id = get_current_user_id();
            
            // Check rate limiting
            $rate_limit_check = $this->check_rate_limit($user_id, 'bulk_add_to_wishlist');
            if (is_wp_error($rate_limit_check)) {
                return $rate_limit_check;
            }
            
            if (!isset($parameters['product_ids']) || !is_array($parameters['product_ids'])) {
                return $this->send_error_response('invalid_request', 'Product IDs array is required', 400);
            }
            
            $product_ids = array_map('absint', $parameters['product_ids']);
            $product_ids = array_filter(array_unique($product_ids)); // Remove empty values and duplicates
            
            if (empty($product_ids)) {
                return $this->send_error_response('invalid_request', 'No valid product IDs provided', 400);
            }
            
            $results = array(
                'added' => array(),
                'failed' => array(),
                'already_exists' => array(),
                'invalid' => array()
            );
            
            $wishlist = $this->get_cached_wishlist($user_id);
            
            foreach ($product_ids as $product_id) {
                // Check if already in wishlist
                if (in_array($product_id, $wishlist)) {
                    $results['already_exists'][] = $product_id;
                    continue;
                }
                
                // Validate product
                $validation_result = $this->validate_product_for_wishlist($product_id);
                if (is_wp_error($validation_result)) {
                    $results['invalid'][] = $product_id;
                    continue;
                }
                
                // Check wishlist limit
                if (!$this->can_add_to_wishlist($user_id, count($results['added']) + 1)) {
                    $results['failed'][] = $product_id;
                    continue;
                }
                
                // Add to wishlist
                $wishlist[] = $product_id;
                $results['added'][] = $product_id;
                
                // Store addition date
                $this->set_wishlist_item_date($user_id, $product_id);
            }
            
            // Update wishlist if any items were added
            if (!empty($results['added'])) {
                $this->update_user_wishlist($user_id, $wishlist);
            }
            
            $response_data = array(
                'results' => $results,
                'wishlist_count' => count($wishlist),
                'total_processed' => count($product_ids)
            );
            
            // Trigger action for extensibility
            $this->trigger_wishlist_action('bulk_items_added', $user_id, null, $response_data);
            
            return $this->send_success_response(
                $response_data, 
                sprintf('Bulk operation completed. Added: %d, Failed: %d', 
                    count($results['added']), 
                    count($results['failed']) + count($results['invalid'])
                )
            );
            
        } catch (Exception $e) {
            return $this->send_error_response('server_error', $e->getMessage(), 500);
        }
    }
    
    public function remove_from_wishlist($request) {
        try {
            $parameters = $request->get_params();
            $user_id = get_current_user_id();
            $product_id = absint($parameters['product_id']);
            
            // Check rate limiting
            $rate_limit_check = $this->check_rate_limit($user_id, 'remove_from_wishlist');
            if (is_wp_error($rate_limit_check)) {
                return $rate_limit_check;
            }
            
            $wishlist = $this->get_cached_wishlist($user_id);
            
            // Check if product is in wishlist
            if (!in_array($product_id, $wishlist)) {
                return $this->send_error_response('not_found', 'Product not found in wishlist', 404);
            }
            
            $product = wc_get_product($product_id);
            $product_name = $product ? $product->get_name() : 'Product';
            
            // Remove product from wishlist
            $wishlist = array_diff($wishlist, array($product_id));
            $wishlist = array_values($wishlist); // Reindex array
            $this->update_user_wishlist($user_id, $wishlist);
            
            // Remove addition date
            $this->remove_wishlist_item_date($user_id, $product_id);
            
            $response_data = array(
                'product_id' => $product_id,
                'product_name' => $product_name,
                'wishlist_count' => count($wishlist),
                'in_wishlist' => false
            );
            
            // Trigger action for extensibility
            $this->trigger_wishlist_action('item_removed', $user_id, $product_id, $response_data);
            
            return $this->send_success_response(
                $response_data, 
                'Product removed from wishlist successfully'
            );
            
        } catch (Exception $e) {
            return $this->send_error_response('server_error', $e->getMessage(), 500);
        }
    }
    
    public function bulk_remove_from_wishlist($request) {
        try {
            $parameters = $request->get_params();
            $user_id = get_current_user_id();
            
            // Check rate limiting
            $rate_limit_check = $this->check_rate_limit($user_id, 'bulk_remove_from_wishlist');
            if (is_wp_error($rate_limit_check)) {
                return $rate_limit_check;
            }
            
            if (!isset($parameters['product_ids']) || !is_array($parameters['product_ids'])) {
                return $this->send_error_response('invalid_request', 'Product IDs array is required', 400);
            }
            
            $product_ids = array_map('absint', $parameters['product_ids']);
            $product_ids = array_filter($product_ids); // Remove empty values
            
            if (empty($product_ids)) {
                return $this->send_error_response('invalid_request', 'No valid product IDs provided', 400);
            }
            
            $wishlist = $this->get_cached_wishlist($user_id);
            
            $results = array(
                'removed' => array(),
                'not_found' => array()
            );
            
            foreach ($product_ids as $product_id) {
                if (in_array($product_id, $wishlist)) {
                    $wishlist = array_diff($wishlist, array($product_id));
                    $results['removed'][] = $product_id;
                    
                    // Remove addition date
                    $this->remove_wishlist_item_date($user_id, $product_id);
                } else {
                    $results['not_found'][] = $product_id;
                }
            }
            
            // Update wishlist if any items were removed
            if (!empty($results['removed'])) {
                $wishlist = array_values($wishlist); // Reindex array
                $this->update_user_wishlist($user_id, $wishlist);
            }
            
            $response_data = array(
                'results' => $results,
                'wishlist_count' => count($wishlist),
                'total_processed' => count($product_ids)
            );
            
            // Trigger action for extensibility
            $this->trigger_wishlist_action('bulk_items_removed', $user_id, null, $response_data);
            
            return $this->send_success_response(
                $response_data, 
                sprintf('Bulk removal completed. Removed: %d, Not found: %d', 
                    count($results['removed']), 
                    count($results['not_found'])
                )
            );
            
        } catch (Exception $e) {
            return $this->send_error_response('server_error', $e->getMessage(), 500);
        }
    }
    
    public function clear_wishlist($request) {
        try {
            $user_id = get_current_user_id();
            
            // Check rate limiting
            $rate_limit_check = $this->check_rate_limit($user_id, 'clear_wishlist');
            if (is_wp_error($rate_limit_check)) {
                return $rate_limit_check;
            }
            
            $wishlist = $this->get_cached_wishlist($user_id);
            $item_count = count($wishlist);
            
            if ($item_count === 0) {
                return $this->send_error_response('empty_wishlist', 'Wishlist is already empty', 400);
            }
            
            // Clear wishlist and dates
            $this->update_user_wishlist($user_id, array());
            delete_user_meta($user_id, 'user_wishlist_dates');
            
            $response_data = array(
                'items_cleared' => $item_count,
                'wishlist_count' => 0
            );
            
            // Trigger action for extensibility
            $this->trigger_wishlist_action('cleared', $user_id, null, $response_data);
            
            return $this->send_success_response(
                $response_data, 
                sprintf('Wishlist cleared successfully. %d items removed.', $item_count)
            );
            
        } catch (Exception $e) {
            return $this->send_error_response('server_error', $e->getMessage(), 500);
        }
    }
    
    public function get_wishlist_count($request) {
        try {
            $user_id = get_current_user_id();
            
            // Check rate limiting
            $rate_limit_check = $this->check_rate_limit($user_id, 'get_count');
            if (is_wp_error($rate_limit_check)) {
                return $rate_limit_check;
            }
            
            $wishlist = $this->get_cached_wishlist($user_id);
            $count = count($wishlist);
            
            $response_data = array(
                'count' => $count,
                'user_id' => $user_id,
                'max_items' => $this->max_wishlist_items
            );
            
            return $this->send_success_response($response_data);
            
        } catch (Exception $e) {
            return $this->send_error_response('server_error', $e->getMessage(), 500);
        }
    }
    
    public function check_wishlist($request) {
        try {
            $user_id = get_current_user_id();
            $product_id = absint($request['product_id']);
            
            // Check rate limiting
            $rate_limit_check = $this->check_rate_limit($user_id, 'check_wishlist');
            if (is_wp_error($rate_limit_check)) {
                return $rate_limit_check;
            }
            
            $wishlist = $this->get_cached_wishlist($user_id);
            $in_wishlist = in_array($product_id, $wishlist);
            
            $product = wc_get_product($product_id);
            $product_name = $product ? $product->get_name() : '';
            
            $response_data = array(
                'product_id' => $product_id,
                'product_name' => $product_name,
                'in_wishlist' => $in_wishlist,
                'user_id' => $user_id,
                'date_added' => $in_wishlist ? $this->get_wishlist_item_date($user_id, $product_id) : null
            );
            
            return $this->send_success_response($response_data);
            
        } catch (Exception $e) {
            return $this->send_error_response('server_error', $e->getMessage(), 500);
        }
    }
    
    /**
     * Helper Methods
     */
    
    private function get_cached_wishlist($user_id) {
        $cache_key = 'wishlist_' . $user_id;
        $cached = wp_cache_get($cache_key, 'wishlist');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $wishlist = get_user_meta($user_id, 'user_wishlist', true);
        
        if (empty($wishlist) || !is_array($wishlist)) {
            $wishlist = array();
        }
        
        wp_cache_set($cache_key, $wishlist, 'wishlist', 3600); // Cache for 1 hour
        
        return $wishlist;
    }
    
    private function update_user_wishlist($user_id, $wishlist) {
        update_user_meta($user_id, 'user_wishlist', $wishlist);
        $this->clear_wishlist_cache($user_id);
    }
    
    private function clear_wishlist_cache($user_id) {
        wp_cache_delete('wishlist_' . $user_id, 'wishlist');
        wp_cache_delete('wishlist_count_' . $user_id, 'wishlist');
    }
    
    private function validate_product_for_wishlist($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return $this->send_error_response('not_found', 'Product not found', 404);
        }
        
        if (!$product->is_purchasable()) {
            return $this->send_error_response('not_purchasable', 'Product is not purchasable', 400);
        }
        
        if (!$product->is_visible()) {
            return $this->send_error_response('not_visible', 'Product is not visible', 400);
        }
        
        return true;
    }
    
    private function can_add_to_wishlist($user_id, $additional_items = 1) {
        $wishlist = $this->get_cached_wishlist($user_id);
        $current_count = count($wishlist);
        
        return ($current_count + $additional_items) <= $this->max_wishlist_items;
    }
    
    private function get_product_data($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product || !$product->is_visible()) {
            return null;
        }
        
        $image_id = $product->get_image_id();
        $gallery_ids = $product->get_gallery_image_ids();
        
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
        
        return $this->sanitize_product_data(array(
            'product_id' => $product_id,
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'type' => $product->get_type(),
            'price' => $product->get_price() ? (float) $product->get_price() : null,
            'regular_price' => $product->get_regular_price() ? (float) $product->get_regular_price() : null,
            'sale_price' => $product->get_sale_price() ? (float) $product->get_sale_price() : null,
            'on_sale' => $product->is_on_sale(),
            'price_html' => $product->get_price_html(),
            'stock_status' => $product->get_stock_status(),
            'in_stock' => $product->is_in_stock(),
            'stock_quantity' => $product->get_stock_quantity(),
            'sku' => $product->get_sku(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'thumbnail' => $image_id ? wp_get_attachment_url($image_id) : wc_placeholder_img_src(),
            'gallery_images' => array_map('wp_get_attachment_url', $gallery_ids),
            'permalink' => get_permalink($product_id),
            'average_rating' => $product->get_average_rating() ? (float) $product->get_average_rating() : 0,
            'rating_count' => $product->get_rating_count(),
            'review_count' => $product->get_review_count(),
            'categories' => $categories,
            'attributes' => $this->get_product_attributes($product)
        ));
    }
    
    private function sanitize_product_data($product_data) {
        $allowed_html = array(
            'br' => array(),
            'em' => array(),
            'strong' => array(),
            'span' => array(
                'class' => true
            )
        );
        
        if (isset($product_data['description'])) {
            $product_data['description'] = wp_kses($product_data['description'], $allowed_html);
        }
        
        if (isset($product_data['short_description'])) {
            $product_data['short_description'] = wp_kses($product_data['short_description'], $allowed_html);
        }
        
        if (isset($product_data['price_html'])) {
            $product_data['price_html'] = wp_kses($product_data['price_html'], $allowed_html);
        }
        
        return $product_data;
    }
    
    private function trigger_wishlist_action($action, $user_id, $product_id = null, $data = array()) {
        do_action("wishlist_{$action}", $user_id, $product_id, $data);
        
        if ($product_id) {
            do_action("wishlist_{$action}_{$product_id}", $user_id, $data);
        }
        
        // Allow other plugins to modify the data
        return apply_filters("wishlist_{$action}_data", $data, $user_id, $product_id);
    }
    
    /**
     * Wishlist date management
     */
    private function get_wishlist_item_date($user_id, $product_id) {
        $dates = get_user_meta($user_id, 'user_wishlist_dates', true);
        
        if (is_array($dates) && isset($dates[$product_id])) {
            return $dates[$product_id];
        }
        
        return current_time('mysql');
    }
    
    private function set_wishlist_item_date($user_id, $product_id) {
        $dates = get_user_meta($user_id, 'user_wishlist_dates', true);
        
        if (!is_array($dates)) {
            $dates = array();
        }
        
        $dates[$product_id] = current_time('mysql');
        update_user_meta($user_id, 'user_wishlist_dates', $dates);
    }
    
    private function remove_wishlist_item_date($user_id, $product_id) {
        $dates = get_user_meta($user_id, 'user_wishlist_dates', true);
        
        if (is_array($dates) && isset($dates[$product_id])) {
            unset($dates[$product_id]);
            update_user_meta($user_id, 'user_wishlist_dates', $dates);
        }
    }
    
    /**
     * Get product attributes
     */
    private function get_product_attributes($product) {
        $attributes = array();
        $product_attributes = $product->get_attributes();
        
        foreach ($product_attributes as $attribute_name => $attribute) {
            $attribute_data = array(
                'name' => $attribute->get_name(),
                'options' => $attribute->get_options(),
                'visible' => $attribute->get_visible(),
                'variation' => $attribute->get_variation()
            );
            
            if ($attribute->is_taxonomy()) {
                $terms = wp_get_post_terms($product->get_id(), $attribute_name);
                $attribute_data['terms'] = array();
                
                if (!is_wp_error($terms) && !empty($terms)) {
                    foreach ($terms as $term) {
                        $attribute_data['terms'][] = array(
                            'id' => $term->term_id,
                            'name' => $term->name,
                            'slug' => $term->slug
                        );
                    }
                }
            }
            
            $attributes[] = $attribute_data;
        }
        
        return $attributes;
    }
}

// Initialize the Wishlist API
function initialize_wishlist_api() {
    new Wishlist_API();
}

// Hook into WordPress
add_action('rest_api_init', 'initialize_wishlist_api');