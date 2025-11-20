<?php

class Reviews_API {
    
    private $namespace = 'ecommerce-api/v1';
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('init', array($this, 'init_hooks'));
    }
    
    public function init_hooks() {
        // Check if table exists and create if needed
        if (!get_option('review_helpfulness_table_created')) {
            self::create_review_helpfulness_table();
            update_option('review_helpfulness_table_created', true);
        }
    }
    
    public function register_routes() {
        // Add review
        register_rest_route($this->namespace, '/reviews/add', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_review'),
            'permission_callback' => array($this, 'check_auth'),
            'args' => $this->get_review_endpoint_args()
        ));
        
        // Get product reviews
        register_rest_route($this->namespace, '/reviews/product/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_product_reviews'),
            'permission_callback' => '__return_true',
            'args' => $this->get_product_reviews_endpoint_args()
        ));
        
        // Get user reviews
        register_rest_route($this->namespace, '/reviews/user', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_reviews'),
            'permission_callback' => array($this, 'check_auth'),
            'args' => $this->get_pagination_args()
        ));
        
        // Get single review
        register_rest_route($this->namespace, '/reviews/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_single_review'),
            'permission_callback' => '__return_true'
        ));
        
        // Update review
        register_rest_route($this->namespace, '/reviews/update', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_review'),
            'permission_callback' => array($this, 'check_auth'),
            'args' => $this->get_update_review_endpoint_args()
        ));
        
        // Delete review
        register_rest_route($this->namespace, '/reviews/delete', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_review'),
            'permission_callback' => array($this, 'check_auth'),
            'args' => $this->get_delete_review_endpoint_args()
        ));
        
        // Get review statistics
        register_rest_route($this->namespace, '/reviews/statistics/product/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_review_statistics'),
            'permission_callback' => '__return_true'
        ));
        
        // Vote on review helpfulness
        register_rest_route($this->namespace, '/reviews/vote/helpful', array(
            'methods' => 'POST',
            'callback' => array($this, 'vote_helpful'),
            'permission_callback' => array($this, 'check_auth'),
            'args' => $this->get_vote_endpoint_args()
        ));
        
        // Report review
        register_rest_route($this->namespace, '/reviews/report', array(
            'methods' => 'POST',
            'callback' => array($this, 'report_review'),
            'permission_callback' => array($this, 'check_auth'),
            'args' => $this->get_report_endpoint_args()
        ));
        
        // Get featured reviews
        register_rest_route($this->namespace, '/reviews/featured/product/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_featured_reviews'),
            'permission_callback' => '__return_true'
        ));
        
        // Admin endpoints
        register_rest_route($this->namespace, '/admin/reviews/pending', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_pending_reviews'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_pagination_args()
        ));
        
        register_rest_route($this->namespace, '/admin/reviews/approve', array(
            'methods' => 'POST',
            'callback' => array($this, 'approve_review'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_admin_review_action_args()
        ));
        
        register_rest_route($this->namespace, '/admin/reviews/reject', array(
            'methods' => 'POST',
            'callback' => array($this, 'reject_review'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_admin_review_action_args()
        ));
    }
    
    /**
     * Argument schemas for all endpoints
     */
    private function get_review_endpoint_args() {
        return array(
            'product_id' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
                'sanitize_callback' => 'absint'
            ),
            'rating' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param >= 1 && $param <= 5;
                },
                'sanitize_callback' => 'absint'
            ),
            'review' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    $trimmed = trim($param);
                    return is_string($param) && strlen($trimmed) > 0 && strlen($trimmed) <= 1000;
                },
                'sanitize_callback' => 'sanitize_textarea_field'
            ),
            'title' => array(
                'required' => false,
                'validate_callback' => function($param) {
                    return is_string($param) && strlen(trim($param)) <= 200;
                },
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'images' => array(
                'required' => false,
                'validate_callback' => function($param) {
                    if (empty($param)) return true;
                    if (!is_array($param)) return false;
                    foreach ($param as $image_id) {
                        if (!is_numeric($image_id) || $image_id <= 0) return false;
                    }
                    return count($param) <= 5; // Max 5 images
                },
                'sanitize_callback' => function($param) {
                    return array_map('absint', (array)$param);
                }
            )
        );
    }
    
    private function get_update_review_endpoint_args() {
        return array(
            'review_id' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
                'sanitize_callback' => 'absint'
            ),
            'rating' => array(
                'required' => false,
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param >= 1 && $param <= 5;
                },
                'sanitize_callback' => 'absint'
            ),
            'review' => array(
                'required' => false,
                'validate_callback' => function($param) {
                    $trimmed = trim($param);
                    return is_string($param) && strlen($trimmed) > 0 && strlen($trimmed) <= 1000;
                },
                'sanitize_callback' => 'sanitize_textarea_field'
            ),
            'title' => array(
                'required' => false,
                'validate_callback' => function($param) {
                    return is_string($param) && strlen(trim($param)) <= 200;
                },
                'sanitize_callback' => 'sanitize_text_field'
            )
        );
    }
    
    private function get_delete_review_endpoint_args() {
        return array(
            'review_id' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
                'sanitize_callback' => 'absint'
            )
        );
    }
    
    private function get_vote_endpoint_args() {
        return array(
            'review_id' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
                'sanitize_callback' => 'absint'
            ),
            'helpful' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return in_array($param, array('yes', 'no'));
                },
                'sanitize_callback' => 'sanitize_text_field'
            )
        );
    }
    
    private function get_report_endpoint_args() {
        return array(
            'review_id' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
                'sanitize_callback' => 'absint'
            ),
            'reason' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    $valid_reasons = array('spam', 'inappropriate', 'false_information', 'other');
                    return in_array($param, $valid_reasons);
                },
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'description' => array(
                'required' => false,
                'validate_callback' => function($param) {
                    return is_string($param) && strlen(trim($param)) <= 500;
                },
                'sanitize_callback' => 'sanitize_textarea_field'
            )
        );
    }
    
    private function get_admin_review_action_args() {
        return array(
            'review_id' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
                'sanitize_callback' => 'absint'
            ),
            'reason' => array(
                'required' => false,
                'validate_callback' => function($param) {
                    return is_string($param) && strlen(trim($param)) <= 500;
                },
                'sanitize_callback' => 'sanitize_text_field'
            )
        );
    }
    
    private function get_product_reviews_endpoint_args() {
        $args = $this->get_pagination_args();
        $args['rating'] = array(
            'required' => false,
            'validate_callback' => function($param) {
                return is_numeric($param) && $param >= 1 && $param <= 5;
            },
            'sanitize_callback' => 'absint'
        );
        $args['sort'] = array(
            'required' => false,
            'validate_callback' => function($param) {
                return in_array($param, array('newest', 'oldest', 'highest_rating', 'lowest_rating', 'most_helpful', 'featured'));
            },
            'sanitize_callback' => 'sanitize_text_field'
        );
        $args['images_only'] = array(
            'required' => false,
            'validate_callback' => function($param) {
                return in_array($param, array('true', 'false', '1', '0'));
            },
            'sanitize_callback' => function($param) {
                return filter_var($param, FILTER_VALIDATE_BOOLEAN);
            }
        );
        return $args;
    }
    
    private function get_pagination_args() {
        return array(
            'page' => array(
                'required' => false,
                'default' => 1,
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
                'sanitize_callback' => 'absint'
            ),
            'per_page' => array(
                'required' => false,
                'default' => 10,
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0 && $param <= 100;
                },
                'sanitize_callback' => 'absint'
            )
        );
    }
    
    /**
     * Authentication and Permission Methods
     */
    public function check_auth($request) {
        if (is_user_logged_in()) {
            return true;
        }
        return $this->check_token_auth($request);
    }
    
    public function check_admin_permissions($request) {
        if (!current_user_can('manage_options') && !current_user_can('moderate_comments')) {
            return new WP_Error(
                'rest_forbidden',
                __('Insufficient permissions.', 'textdomain'),
                array('status' => 403)
            );
        }
        return true;
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
    
    /**
     * Main API Endpoint Methods
     */
    public function add_review($request) {
        try {
            $parameters = $request->get_params();
            $user_id = get_current_user_id();
            $user = get_userdata($user_id);
            
            // Check if user exists
            if (!$user) {
                return $this->send_error('User not found', 404);
            }
            
            $product_id = $parameters['product_id'];
            $rating = $parameters['rating'];
            $review = trim($parameters['review']);
            $title = isset($parameters['title']) ? trim($parameters['title']) : '';
            $images = isset($parameters['images']) ? $parameters['images'] : array();
            
            // Validate product
            $product = wc_get_product($product_id);
            if (!$product) {
                return $this->send_error('Product not found', 404);
            }
            
            if (!$product->get_reviews_allowed()) {
                return $this->send_error('Reviews are not allowed for this product', 400);
            }
            
            // Check purchase requirement
            if (!$this->has_user_purchased_product($user_id, $product_id)) {
                return $this->send_error('You must purchase this product before reviewing it', 400);
            }
            
            // Check duplicate review
            if ($this->has_user_reviewed_product($user_id, $product_id)) {
                return $this->send_error('You have already reviewed this product', 400);
            }
            
            // Prepare and insert comment
            $comment_data = array(
                'comment_post_ID' => $product_id,
                'comment_author' => $user->display_name,
                'comment_author_email' => $user->user_email,
                'comment_content' => $review,
                'comment_type' => 'review',
                'comment_parent' => 0,
                'user_id' => $user_id,
                'comment_approved' => $this->should_auto_approve_review($user_id) ? 1 : 0
            );
            
            $comment_id = wp_insert_comment($comment_data);
            
            if (is_wp_error($comment_id) || !$comment_id) {
                $this->log_error('Failed to insert review comment', array(
                    'user_id' => $user_id,
                    'product_id' => $product_id,
                    'error' => $comment_id
                ));
                return $this->send_error('Failed to add review', 500);
            }
            
            // Add metadata
            update_comment_meta($comment_id, 'rating', $rating);
            
            if (!empty($title)) {
                update_comment_meta($comment_id, 'title', $title);
            }
            
            // Handle review images
            if (!empty($images)) {
                $this->attach_review_images($comment_id, $images);
            }
            
            // Add audit trail
            $this->add_review_audit_trail($comment_id, 'created', $user_id);
            
            // Clear caches
            $this->clear_product_rating_cache($product_id);
            
            // Trigger hooks
            do_action('ecommerce_api_review_added', $comment_id, $user_id, $product_id);
            
            return $this->send_response(array(
                'review_id' => $comment_id,
                'status' => $comment_data['comment_approved'] ? 'approved' : 'pending',
                'message' => $comment_data['comment_approved'] ? 
                    'Review added successfully' : 
                    'Review submitted and awaiting moderation'
            ), 201);
            
        } catch (Exception $e) {
            $this->log_error('Exception in add_review', array(
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            return $this->send_error('An unexpected error occurred', 500);
        }
    }
    
    public function get_product_reviews($request) {
        try {
            $product_id = $request['id'];
            $parameters = $request->get_params();
            
            $page = $parameters['page'];
            $per_page = $parameters['per_page'];
            $rating = isset($parameters['rating']) ? intval($parameters['rating']) : 0;
            $sort = isset($parameters['sort']) ? $parameters['sort'] : 'newest';
            $images_only = isset($parameters['images_only']) ? $parameters['images_only'] : false;
            
            // Validate product
            $product = wc_get_product($product_id);
            if (!$product) {
                return $this->send_error('Product not found', 404);
            }
            
            // Build query args
            $args = array(
                'post_id' => $product_id,
                'status' => 'approve',
                'type' => 'review',
                'number' => $per_page,
                'offset' => ($page - 1) * $per_page
            );
            
            // Filter by rating
            if ($rating >= 1 && $rating <= 5) {
                $args['meta_query'] = array(
                    array(
                        'key' => 'rating',
                        'value' => $rating,
                        'compare' => '=',
                        'type' => 'NUMERIC'
                    )
                );
            }
            
            // Filter by images
            if ($images_only) {
                if (!isset($args['meta_query'])) {
                    $args['meta_query'] = array();
                }
                $args['meta_query'][] = array(
                    'key' => 'review_images',
                    'compare' => 'EXISTS'
                );
            }
            
            // Apply sorting
            $args = $this->apply_review_sorting($args, $sort);
            
            // Get reviews
            $reviews = get_comments($args);
            $formatted_reviews = array();
            
            foreach ($reviews as $review) {
                $formatted_reviews[] = $this->format_review_data($review);
            }
            
            // Get totals
            $count_args = $args;
            $count_args['number'] = '';
            $count_args['offset'] = '';
            $count_args['count'] = true;
            
            $total_reviews = get_comments($count_args);
            
            // Get statistics
            $rating_distribution = $this->get_rating_distribution($product_id);
            $reviews_with_images = $this->get_reviews_with_images_count($product_id);
            
            $response = array(
                'reviews' => $formatted_reviews,
                'statistics' => array(
                    'total_reviews' => $total_reviews,
                    'average_rating' => floatval($product->get_average_rating()),
                    'rating_distribution' => $rating_distribution,
                    'reviews_with_images' => $reviews_with_images
                ),
                'pagination' => array(
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_reviews' => $total_reviews,
                    'total_pages' => ceil($total_reviews / $per_page)
                )
            );
            
            return $this->send_response($response);
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_product_reviews', array(
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            return $this->send_error('An unexpected error occurred', 500);
        }
    }
    
    public function get_user_reviews($request) {
        try {
            $parameters = $request->get_params();
            $user_id = get_current_user_id();
            
            $page = $parameters['page'];
            $per_page = $parameters['per_page'];
            
            $args = array(
                'user_id' => $user_id,
                'type' => 'review',
                'number' => $per_page,
                'offset' => ($page - 1) * $per_page,
                'orderby' => 'comment_date',
                'order' => 'DESC'
            );
            
            $reviews = get_comments($args);
            $formatted_reviews = array();
            
            foreach ($reviews as $review) {
                $review_data = $this->format_review_data($review);
                $review_data['product'] = array(
                    'id' => $review->comment_post_ID,
                    'name' => get_the_title($review->comment_post_ID),
                    'permalink' => get_permalink($review->comment_post_ID),
                    'image' => get_the_post_thumbnail_url($review->comment_post_ID, 'woocommerce_thumbnail'),
                    'price' => $this->get_product_price($review->comment_post_ID)
                );
                $formatted_reviews[] = $review_data;
            }
            
            $count_args = $args;
            $count_args['number'] = '';
            $count_args['offset'] = '';
            $count_args['count'] = true;
            
            $total_reviews = get_comments($count_args);
            
            $response = array(
                'reviews' => $formatted_reviews,
                'pagination' => array(
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_reviews' => $total_reviews,
                    'total_pages' => ceil($total_reviews / $per_page)
                )
            );
            
            return $this->send_response($response);
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_user_reviews', array(
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            return $this->send_error('An unexpected error occurred', 500);
        }
    }
    
    public function get_single_review($request) {
        try {
            $review_id = $request['id'];
            
            $review = get_comment($review_id);
            
            if (!$review || $review->comment_type !== 'review') {
                return $this->send_error('Review not found', 404);
            }
            
            if ($review->comment_approved !== '1') {
                return $this->send_error('Review not available', 404);
            }
            
            $formatted_review = $this->format_review_data($review, true);
            
            return $this->send_response($formatted_review);
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_single_review', array(
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            return $this->send_error('An unexpected error occurred', 500);
        }
    }
    
    public function update_review($request) {
        try {
            $parameters = $request->get_params();
            $user_id = get_current_user_id();
            
            $review_id = $parameters['review_id'];
            $rating = isset($parameters['rating']) ? intval($parameters['rating']) : 0;
            $review = isset($parameters['review']) ? trim($parameters['review']) : '';
            $title = isset($parameters['title']) ? trim($parameters['title']) : '';
            
            $existing_review = get_comment($review_id);
            
            if (!$existing_review || $existing_review->comment_type !== 'review') {
                return $this->send_error('Review not found', 404);
            }
            
            if ($existing_review->user_id != $user_id && !current_user_can('moderate_comments')) {
                return $this->send_error('You are not authorized to update this review', 403);
            }
            
            // Prepare update data
            $comment_data = array('comment_ID' => $review_id);
            $updated_fields = array();
            
            if (!empty($review)) {
                $comment_data['comment_content'] = $review;
                $updated_fields[] = 'content';
            }
            
            // Update comment
            $result = wp_update_comment($comment_data);
            
            if (is_wp_error($result) || !$result) {
                $this->log_error('Failed to update review', array(
                    'review_id' => $review_id,
                    'user_id' => $user_id,
                    'error' => $result
                ));
                return $this->send_error('Failed to update review', 500);
            }
            
            // Update metadata
            if ($rating >= 1 && $rating <= 5) {
                update_comment_meta($review_id, 'rating', $rating);
                $updated_fields[] = 'rating';
            }
            
            if (!empty($title)) {
                update_comment_meta($review_id, 'title', $title);
                $updated_fields[] = 'title';
            }
            
            // Add audit trail
            if (!empty($updated_fields)) {
                $this->add_review_audit_trail($review_id, 'updated', $user_id, array(
                    'updated_fields' => $updated_fields
                ));
            }
            
            $this->clear_product_rating_cache($existing_review->comment_post_ID);
            do_action('ecommerce_api_review_updated', $review_id, $user_id);
            
            return $this->send_response(null, 200, 'Review updated successfully');
            
        } catch (Exception $e) {
            $this->log_error('Exception in update_review', array(
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            return $this->send_error('An unexpected error occurred', 500);
        }
    }
    
    public function delete_review($request) {
        try {
            $parameters = $request->get_params();
            $user_id = get_current_user_id();
            
            $review_id = $parameters['review_id'];
            
            $existing_review = get_comment($review_id);
            
            if (!$existing_review || $existing_review->comment_type !== 'review') {
                return $this->send_error('Review not found', 404);
            }
            
            if ($existing_review->user_id != $user_id && !current_user_can('moderate_comments')) {
                return $this->send_error('You are not authorized to delete this review', 403);
            }
            
            $product_id = $existing_review->comment_post_ID;
            
            // Add audit trail before deletion
            $this->add_review_audit_trail($review_id, 'deleted', $user_id);
            
            $result = wp_delete_comment($review_id, true);
            
            if (!$result) {
                $this->log_error('Failed to delete review', array(
                    'review_id' => $review_id,
                    'user_id' => $user_id
                ));
                return $this->send_error('Failed to delete review', 500);
            }
            
            $this->clear_product_rating_cache($product_id);
            do_action('ecommerce_api_review_deleted', $review_id, $user_id, $product_id);
            
            return $this->send_response(null, 200, 'Review deleted successfully');
            
        } catch (Exception $e) {
            $this->log_error('Exception in delete_review', array(
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            return $this->send_error('An unexpected error occurred', 500);
        }
    }
    
    public function get_review_statistics($request) {
        try {
            $product_id = $request['id'];
            
            $product = wc_get_product($product_id);
            if (!$product) {
                return $this->send_error('Product not found', 404);
            }
            
            $rating_distribution = $this->get_rating_distribution($product_id);
            $average_rating = $product->get_average_rating();
            $review_count = $product->get_review_count();
            $reviews_with_images = $this->get_reviews_with_images_count($product_id);
            
            $response = array(
                'average_rating' => floatval($average_rating),
                'review_count' => $review_count,
                'rating_distribution' => $rating_distribution,
                'reviews_with_images' => $reviews_with_images,
                'rating_summary' => array(
                    'excellent' => $this->get_rating_percentage($rating_distribution, 5),
                    'good' => $this->get_rating_percentage($rating_distribution, 4),
                    'average' => $this->get_rating_percentage($rating_distribution, 3),
                    'poor' => $this->get_rating_percentage($rating_distribution, 2),
                    'terrible' => $this->get_rating_percentage($rating_distribution, 1)
                )
            );
            
            return $this->send_response($response);
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_review_statistics', array(
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            return $this->send_error('An unexpected error occurred', 500);
        }
    }
    
    /**
     * New Enhanced Methods
     */
    public function vote_helpful($request) {
        try {
            $parameters = $request->get_params();
            $user_id = get_current_user_id();
            
            $review_id = $parameters['review_id'];
            $helpful = $parameters['helpful'] === 'yes';
            
            $review = get_comment($review_id);
            if (!$review || $review->comment_type !== 'review') {
                return $this->send_error('Review not found', 404);
            }
            
            // Check if user already voted
            $existing_vote = $this->get_user_review_vote($review_id, $user_id);
            
            if ($existing_vote) {
                // get_user_review_vote returns 'yes' or 'no', convert to boolean for comparison
                $existing_helpful = ($existing_vote === 'yes');
                if ($existing_helpful === $helpful) {
                    return $this->send_error('You have already voted on this review', 400);
                } else {
                    // Update existing vote
                    $this->update_review_vote($review_id, $user_id, $helpful);
                }
            } else {
                // Create new vote
                $this->add_review_vote($review_id, $user_id, $helpful);
            }
            
            // Get updated counts
            $helpful_data = $this->get_review_helpful_counts($review_id);
            
            return $this->send_response(array(
                'helpful_data' => $helpful_data,
                'user_vote' => $helpful ? 'yes' : 'no'
            ), 200, 'Vote recorded successfully');
            
        } catch (Exception $e) {
            $this->log_error('Exception in vote_helpful', array(
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            return $this->send_error('An unexpected error occurred', 500);
        }
    }
    
    public function report_review($request) {
        try {
            $parameters = $request->get_params();
            $user_id = get_current_user_id();
            
            $review_id = $parameters['review_id'];
            $reason = $parameters['reason'];
            $description = isset($parameters['description']) ? trim($parameters['description']) : '';
            
            $review = get_comment($review_id);
            if (!$review || $review->comment_type !== 'review') {
                return $this->send_error('Review not found', 404);
            }
            
            // Check if user already reported
            if ($this->has_user_reported_review($review_id, $user_id)) {
                return $this->send_error('You have already reported this review', 400);
            }
            
            // Record report
            $this->add_review_report($review_id, $user_id, $reason, $description);
            
            // Notify admin
            $this->notify_admin_of_review_report($review_id, $user_id, $reason, $description);
            
            return $this->send_response(null, 200, 'Review reported successfully');
            
        } catch (Exception $e) {
            $this->log_error('Exception in report_review', array(
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            return $this->send_error('An unexpected error occurred', 500);
        }
    }
    
    public function get_featured_reviews($request) {
        try {
            $product_id = $request['id'];
            
            $product = wc_get_product($product_id);
            if (!$product) {
                return $this->send_error('Product not found', 404);
            }
            
            $args = array(
                'post_id' => $product_id,
                'status' => 'approve',
                'type' => 'review',
                'number' => 3,
                'meta_query' => array(
                    array(
                        'key' => 'featured_review',
                        'value' => '1',
                        'compare' => '='
                    )
                )
            );
            
            $reviews = get_comments($args);
            $formatted_reviews = array();
            
            foreach ($reviews as $review) {
                $formatted_reviews[] = $this->format_review_data($review);
            }
            
            return $this->send_response($formatted_reviews);
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_featured_reviews', array(
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            return $this->send_error('An unexpected error occurred', 500);
        }
    }
    
    /**
     * Admin Methods
     */
    public function get_pending_reviews($request) {
        try {
            $parameters = $request->get_params();
            $page = isset($parameters['page']) ? intval($parameters['page']) : 1;
            $per_page = isset($parameters['per_page']) ? intval($parameters['per_page']) : 20;
            
            $args = array(
                'status' => 'hold',
                'type' => 'review',
                'number' => $per_page,
                'offset' => ($page - 1) * $per_page
            );
            
            $reviews = get_comments($args);
            $formatted_reviews = array();
            
            foreach ($reviews as $review) {
                $review_data = $this->format_review_data($review);
                $review_data['product'] = array(
                    'id' => $review->comment_post_ID,
                    'name' => get_the_title($review->comment_post_ID)
                );
                $review_data['report_count'] = $this->get_review_report_count($review->comment_ID);
                $formatted_reviews[] = $review_data;
            }
            
            $count_args = $args;
            $count_args['number'] = '';
            $count_args['offset'] = '';
            $count_args['count'] = true;
            
            $total_reviews = get_comments($count_args);
            
            $response = array(
                'reviews' => $formatted_reviews,
                'pagination' => array(
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_reviews' => $total_reviews,
                    'total_pages' => ceil($total_reviews / $per_page)
                )
            );
            
            return $this->send_response($response);
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_pending_reviews', array(
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            return $this->send_error('An unexpected error occurred', 500);
        }
    }
    
    public function approve_review($request) {
        try {
            $parameters = $request->get_params();
            $review_id = $parameters['review_id'];
            $reason = isset($parameters['reason']) ? trim($parameters['reason']) : '';
            
            $review = get_comment($review_id);
            if (!$review) {
                return $this->send_error('Review not found', 404);
            }
            
            $result = wp_set_comment_status($review_id, 'approve');
            
            if (!$result) {
                return $this->send_error('Failed to approve review', 500);
            }
            
            $this->add_review_audit_trail($review_id, 'approved', get_current_user_id(), array(
                'reason' => $reason
            ));
            
            do_action('ecommerce_api_review_approved', $review_id);
            
            return $this->send_response(null, 200, 'Review approved successfully');
            
        } catch (Exception $e) {
            $this->log_error('Exception in approve_review', array(
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            return $this->send_error('An unexpected error occurred', 500);
        }
    }
    
    public function reject_review($request) {
        try {
            $parameters = $request->get_params();
            $review_id = $parameters['review_id'];
            $reason = isset($parameters['reason']) ? trim($parameters['reason']) : '';
            
            $review = get_comment($review_id);
            if (!$review) {
                return $this->send_error('Review not found', 404);
            }
            
            $result = wp_set_comment_status($review_id, 'spam');
            
            if (!$result) {
                return $this->send_error('Failed to reject review', 500);
            }
            
            $this->add_review_audit_trail($review_id, 'rejected', get_current_user_id(), array(
                'reason' => $reason
            ));
            
            do_action('ecommerce_api_review_rejected', $review_id);
            
            return $this->send_response(null, 200, 'Review rejected successfully');
            
        } catch (Exception $e) {
            $this->log_error('Exception in reject_review', array(
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            return $this->send_error('An unexpected error occurred', 500);
        }
    }
    
    private function has_user_purchased_product($user_id, $product_id) {
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Use WooCommerce's built-in function if available
        if (function_exists('wc_customer_bought_product')) {
            return wc_customer_bought_product('', $user_id, $product_id);
        }
        
        // Fallback to custom query
        $customer_orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'status' => wc_get_is_paid_statuses(),
            'limit' => -1
        ));
        
        foreach ($customer_orders as $order) {
            foreach ($order->get_items() as $item) {
                // Get the product object from the item
                $product = $item->get_product();
                
                if ($product) {
                    // Check both product ID and variation ID
                    if ($product->get_id() == $product_id) {
                        return true;
                    }
                    
                    // For variable products, check parent ID too
                    if (method_exists($product, 'get_parent_id') && $product->get_parent_id() == $product_id) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    private function has_user_reviewed_product($user_id, $product_id) {
        $args = array(
            'user_id' => $user_id,
            'post_id' => $product_id,
            'type' => 'review',
            'count' => true
        );
        
        return get_comments($args) > 0;
    }
    
    private function should_auto_approve_review($user_id) {
        // Auto-approve based on user role or previous approved reviews
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Check if user has previous approved reviews
        $approved_reviews = get_comments(array(
            'user_id' => $user_id,
            'status' => 'approve',
            'type' => 'review',
            'count' => true
        ));
        
        if ($approved_reviews >= 3) {
            return true; // Auto-approve trusted reviewers
        }
        
        // Default to moderation
        return false;
    }
    
    private function apply_review_sorting($args, $sort) {
        switch ($sort) {
            case 'oldest':
                $args['orderby'] = 'comment_date';
                $args['order'] = 'ASC';
                break;
            case 'highest_rating':
                $args['meta_key'] = 'rating';
                $args['orderby'] = array('meta_value_num' => 'DESC', 'comment_date' => 'DESC');
                break;
            case 'lowest_rating':
                $args['meta_key'] = 'rating';
                $args['orderby'] = array('meta_value_num' => 'ASC', 'comment_date' => 'DESC');
                break;
            case 'most_helpful':
                $args['orderby'] = 'comment_karma';
                $args['order'] = 'DESC';
                break;
            case 'featured':
                if (!isset($args['meta_query'])) {
                    $args['meta_query'] = array();
                }
                $args['meta_query'][] = array(
                    'key' => 'featured_review',
                    'value' => '1',
                    'compare' => '='
                );
                $args['orderby'] = 'comment_date';
                $args['order'] = 'DESC';
                break;
            case 'newest':
            default:
                $args['orderby'] = 'comment_date';
                $args['order'] = 'DESC';
                break;
        }
        
        return $args;
    }
    
    private function format_review_data($review, $include_detailed = false) {
        $rating = get_comment_meta($review->comment_ID, 'rating', true);
        $title = get_comment_meta($review->comment_ID, 'title', true);
        $helpful_data = $this->get_review_helpful_counts($review->comment_ID);
        $images = $this->get_review_images($review->comment_ID);
        
        $formatted = array(
            'id' => $review->comment_ID,
            'author' => array(
                'name' => $review->comment_author,
                'email' => $review->comment_author_email,
                'avatar' => get_avatar_url($review->comment_author_email, array('size' => 96)),
                'user_id' => $review->user_id
            ),
            'title' => $title ?: '',
            'content' => $review->comment_content,
            'rating' => $rating ? intval($rating) : 0,
            'date' => $review->comment_date,
            'date_gmt' => $review->comment_date_gmt,
            'verified' => wc_review_is_from_verified_owner($review->comment_ID),
            'helpful_data' => $helpful_data,
            'images' => $images,
            'product_id' => $review->comment_post_ID
        );
        
        if ($include_detailed) {
            $current_user_id = get_current_user_id();
            $formatted['user_vote'] = $current_user_id ? $this->get_user_review_vote($review->comment_ID, $current_user_id) : null;
            $formatted['reports'] = $this->get_review_report_count($review->comment_ID);
            $formatted['featured'] = (bool) get_comment_meta($review->comment_ID, 'featured_review', true);
        }
        
        return $formatted;
    }
    
    private function get_rating_distribution($product_id) {
        global $wpdb;
        
        $distribution = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0);
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT meta_value as rating, COUNT(*) as count 
            FROM {$wpdb->commentmeta} cm 
            INNER JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID 
            WHERE c.comment_post_ID = %d 
            AND c.comment_approved = '1' 
            AND c.comment_type = 'review' 
            AND cm.meta_key = 'rating' 
            GROUP BY meta_value
        ", $product_id));
        
        foreach ($results as $result) {
            $rating = intval($result->rating);
            if ($rating >= 1 && $rating <= 5) {
                $distribution[$rating] = intval($result->count);
            }
        }
        
        return $distribution;
    }
    
    private function get_reviews_with_images_count($product_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT c.comment_ID)
            FROM {$wpdb->comments} c 
            INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id
            WHERE c.comment_post_ID = %d 
            AND c.comment_approved = '1' 
            AND c.comment_type = 'review'
            AND cm.meta_key = 'review_images'
        ", $product_id));
    }
    
    private function get_product_price($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) return null;
        
        return array(
            'regular' => $product->get_regular_price(),
            'sale' => $product->get_sale_price(),
            'current' => $product->get_price(),
            'currency' => get_woocommerce_currency(),
            'currency_symbol' => get_woocommerce_currency_symbol()
        );
    }
    
    /**
     * Image Handling Methods
     */
    private function attach_review_images($review_id, $image_ids) {
        if (empty($image_ids)) return;
        
        // Store image IDs as serialized array
        update_comment_meta($review_id, 'review_images', $image_ids);
        
        // Create image gallery relationships
        foreach ($image_ids as $image_id) {
            // You might want to create a custom table for this in production
            add_comment_meta($review_id, 'review_image', $image_id);
        }
    }
    
    private function get_review_images($review_id) {
        $image_ids = get_comment_meta($review_id, 'review_images', true);
        if (empty($image_ids) || !is_array($image_ids)) {
            return array();
        }
        
        $images = array();
        foreach ($image_ids as $image_id) {
            $image_data = wp_get_attachment_image_src($image_id, 'medium');
            if ($image_data) {
                $thumbnail_data = wp_get_attachment_image_src($image_id, 'thumbnail');
                $large_data = wp_get_attachment_image_src($image_id, 'large');
                
                $images[] = array(
                    'id' => $image_id,
                    'url' => $image_data[0],
                    'width' => $image_data[1],
                    'height' => $image_data[2],
                    'thumbnail' => $thumbnail_data ? $thumbnail_data[0] : '',
                    'large' => $large_data ? $large_data[0] : ''
                );
            }
        }
        
        return $images;
    }
    
    /**
     * Helpfulness Voting System
     */
    public static function create_review_helpfulness_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'review_helpfulness_votes';
        
        // Check if table exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                review_id bigint(20) NOT NULL,
                user_id bigint(20) NOT NULL,
                helpful tinyint(1) NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY user_review_vote (user_id, review_id),
                KEY review_id (review_id),
                KEY user_id (user_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    private function add_review_vote($review_id, $user_id, $helpful) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'review_helpfulness_votes';
        
        return $wpdb->insert(
            $table_name,
            array(
                'review_id' => $review_id,
                'user_id' => $user_id,
                'helpful' => $helpful ? 1 : 0
            ),
            array('%d', '%d', '%d')
        );
    }
    
    private function update_review_vote($review_id, $user_id, $helpful) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'review_helpfulness_votes';
        
        return $wpdb->update(
            $table_name,
            array('helpful' => $helpful ? 1 : 0),
            array('review_id' => $review_id, 'user_id' => $user_id),
            array('%d'),
            array('%d', '%d')
        );
    }
    
    private function get_user_review_vote($review_id, $user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'review_helpfulness_votes';
        
        $result = $wpdb->get_row($wpdb->prepare("
            SELECT helpful 
            FROM $table_name 
            WHERE review_id = %d AND user_id = %d
        ", $review_id, $user_id));
        
        if (!$result) return null;
        
        return $result->helpful ? 'yes' : 'no';
    }
    
    private function get_review_helpful_counts($review_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'review_helpfulness_votes';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT helpful, COUNT(*) as count 
            FROM $table_name 
            WHERE review_id = %d 
            GROUP BY helpful
        ", $review_id));
        
        $helpful_count = 0;
        $unhelpful_count = 0;
        
        foreach ($results as $result) {
            if ($result->helpful) {
                $helpful_count = intval($result->count);
            } else {
                $unhelpful_count = intval($result->count);
            }
        }
        
        return array(
            'helpful_count' => $helpful_count,
            'unhelpful_count' => $unhelpful_count,
            'total_votes' => $helpful_count + $unhelpful_count
        );
    }
    
    /**
     * Review Reporting System
     */
    private function add_review_report($review_id, $user_id, $reason, $description = '') {
        $reports = get_comment_meta($review_id, 'review_reports', true);
        if (!is_array($reports)) {
            $reports = array();
        }
        
        $reports[] = array(
            'user_id' => $user_id,
            'reason' => $reason,
            'description' => $description,
            'reported_at' => current_time('mysql')
        );
        
        update_comment_meta($review_id, 'review_reports', $reports);
    }
    
    private function has_user_reported_review($review_id, $user_id) {
        $reports = get_comment_meta($review_id, 'review_reports', true);
        if (!is_array($reports)) return false;
        
        foreach ($reports as $report) {
            if (isset($report['user_id']) && $report['user_id'] == $user_id) {
                return true;
            }
        }
        
        return false;
    }
    
    private function get_review_report_count($review_id) {
        $reports = get_comment_meta($review_id, 'review_reports', true);
        return is_array($reports) ? count($reports) : 0;
    }
    
    private function notify_admin_of_review_report($review_id, $user_id, $reason, $description) {
        $review = get_comment($review_id);
        $reporter = get_userdata($user_id);
        
        $subject = 'New Review Report - Review #' . $review_id;
        $message = "A review has been reported.\n\n";
        $message .= "Review ID: $review_id\n";
        $message .= "Product: " . get_the_title($review->comment_post_ID) . "\n";
        $message .= "Review Author: " . $review->comment_author . "\n";
        $message .= "Reported By: " . ($reporter ? $reporter->display_name . " (" . $reporter->user_email . ")" : 'Unknown User') . "\n";
        $message .= "Reason: " . $reason . "\n";
        $message .= "Description: " . $description . "\n\n";
        $message .= "Review Content:\n" . $review->comment_content . "\n\n";
        $message .= "Please review this report in the admin panel.";
        
        wp_mail(get_option('admin_email'), $subject, $message);
    }
    
    /**
     * Audit Trail System
     */
    private function add_review_audit_trail($review_id, $action, $user_id, $data = array()) {
        $audit_trail = get_comment_meta($review_id, 'review_audit_trail', true);
        if (!is_array($audit_trail)) {
            $audit_trail = array();
        }
        
        $audit_trail[] = array(
            'action' => $action,
            'user_id' => $user_id,
            'timestamp' => current_time('mysql'),
            'data' => $data
        );
        
        update_comment_meta($review_id, 'review_audit_trail', $audit_trail);
    }
    
    /**
     * Utility Methods
     */
    private function clear_product_rating_cache($product_id) {
        if (function_exists('wc_delete_product_transients')) {
            wc_delete_product_transients($product_id);
        }
        
        // Clear WooCommerce product cache
        clean_post_cache($product_id);
    }
    
    private function get_token_from_request($request) {
        $auth_header = $request->get_header('Authorization');
        if ($auth_header && preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return sanitize_text_field($matches[1]);
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
        if (empty($token)) return false;
        
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
    
    private function get_rating_percentage($distribution, $rating) {
        $total = array_sum($distribution);
        if ($total === 0) return 0;
        
        return round(($distribution[$rating] / $total) * 100, 1);
    }
    
    private function log_error($message, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Reviews_API Error: ' . $message . ' - Context: ' . json_encode($context));
        }
    }
    
    private function send_error($message, $status_code = 400) {
        return new WP_Error(
            'reviews_api_error',
            $message,
            array('status' => $status_code)
        );
    }
    
    private function send_response($data = null, $status_code = 200, $message = 'Success') {
        $response = array(
            'success' => true,
            'message' => $message,
            'data' => $data,
            'status' => $status_code
        );
        
        return rest_ensure_response($response)->set_status($status_code);
    }
}

// Initialize the Reviews API
function initialize_reviews_api() {
    new Reviews_API();
}

// Hook into both to ensure routes are registered
add_action('rest_api_init', 'initialize_reviews_api', 1);
add_action('init', 'initialize_reviews_api');

// Activation hook
register_activation_hook(__FILE__, array('Reviews_API', 'create_review_helpfulness_table'));