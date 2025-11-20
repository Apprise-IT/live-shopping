<?php
class Auth_API {
    
    private $namespace = 'ecommerce-api/v1';
    private $token_meta_key = 'ecommerce_api_session_tokens';
    private $cache_manager;
    
    public function __construct($cache_manager = null) {
        $this->cache_manager = $cache_manager;
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('wp_logout', array($this, 'clear_user_tokens'));
    }
    
    public function register_routes() {
        // Login
        register_rest_route($this->namespace, '/auth/login', array(
            'methods' => 'POST',
            'callback' => array($this, 'login'),
            'permission_callback' => '__return_true'
        ));
        
        // Register
        register_rest_route($this->namespace, '/auth/register', array(
            'methods' => 'POST',
            'callback' => array($this, 'register'),
            'permission_callback' => '__return_true'
        ));
        
        // Get Profile
        register_rest_route($this->namespace, '/auth/profile', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_profile'),
            'permission_callback' => array($this, 'check_token_auth')
        ));
        
        // Update Profile
        register_rest_route($this->namespace, '/auth/profile', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_profile'),
            'permission_callback' => array($this, 'check_token_auth')
        ));
        
        // Forgot Password
        register_rest_route($this->namespace, '/auth/forgot-password', array(
            'methods' => 'POST',
            'callback' => array($this, 'forgot_password'),
            'permission_callback' => '__return_true'
        ));
        
        // Logout
        register_rest_route($this->namespace, '/auth/logout', array(
            'methods' => 'POST',
            'callback' => array($this, 'logout'),
            'permission_callback' => array($this, 'check_token_auth')
        ));
        
        // Validate Token
        register_rest_route($this->namespace, '/auth/validate-token', array(
            'methods' => 'GET',
            'callback' => array($this, 'validate_token'),
            'permission_callback' => array($this, 'check_token_auth')
        ));
    }
    
    public function check_auth() {
        return is_user_logged_in();
    }
    
    public function check_token_auth($request) {
        $token = $this->get_token_from_request($request);
        
        if (!$token) {
            return false;
        }
        
        $user_id = $this->validate_session_token($token);
        
        if ($user_id) {
            wp_set_current_user($user_id);
            return true;
        }
        
        return false;
    }
    
    public function login($request) {
        $parameters = $request->get_params();
        
        $username = sanitize_text_field($parameters['username']);
        $password = sanitize_text_field($parameters['password']);
        
        $user = wp_authenticate($username, $password);
        
        if (is_wp_error($user)) {
            return new WP_Error('authentication_failed', 'Invalid username or password', array('status' => 401));
        }
        
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        
        // Generate session token
        $session_token = $this->generate_session_token($user->ID);
        
        $user_data = array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => get_user_meta($user->ID, 'first_name', true),
            'last_name' => get_user_meta($user->ID, 'last_name', true),
            'session_token' => $session_token,
            'token_expires' => time() + (30 * 24 * 60 * 60) // 30 days
        );
        
        return $this->send_response($user_data, 200, 'Login successful');
    }
    
    public function register($request) {
        $parameters = $request->get_params();
        
        $username = sanitize_text_field($parameters['username']);
        $email = sanitize_email($parameters['email']);
        $password = sanitize_text_field($parameters['password']);
        $first_name = sanitize_text_field($parameters['first_name']);
        $last_name = sanitize_text_field($parameters['last_name']);
        
        // Check if user already exists
        if (username_exists($username)) {
            return new WP_Error('username_exists', 'Username already exists', array('status' => 400));
        }
        
        if (email_exists($email)) {
            return new WP_Error('email_exists', 'Email already exists', array('status' => 400));
        }
        
        // Create new user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            return new WP_Error('registration_failed', $user_id->get_error_message(), array('status' => 400));
        }
        
        // Update user meta
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name
        ));
        
        // Auto login after registration
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        // Generate session token
        $session_token = $this->generate_session_token($user_id);
        
        $user = get_userdata($user_id);
        $user_data = array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'session_token' => $session_token,
            'token_expires' => time() + (30 * 24 * 60 * 60) // 30 days
        );
        
        return $this->send_response($user_data, 201, 'Registration successful');
    }
    
    public function get_profile($request) {
        $user_id = get_current_user_id();
        $cache_key = $this->cache_manager ? $this->cache_manager->generate_key("user_profile_{$user_id}") : null;
        
        // Try cache first
        if ($this->cache_manager && $cached = $this->cache_manager->get($cache_key)) {
            return $this->send_response($cached);
        }
        
        $user = get_userdata($user_id);
        
        $user_data = array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => get_user_meta($user->ID, 'first_name', true),
            'last_name' => get_user_meta($user->ID, 'last_name', true),
            'billing' => $this->get_billing_address($user_id),
            'shipping' => $this->get_shipping_address($user_id)
        );
        
        // Cache the result
        if ($this->cache_manager) {
            $this->cache_manager->set($cache_key, $user_data, 1800); // 30 minutes
        }
        
        return $this->send_response($user_data);
    }
    
    public function update_profile($request) {
        $user_id = get_current_user_id();
        $parameters = $request->get_params();
        
        $user_data = array('ID' => $user_id);
        $update_errors = array();
        
        // Update basic user information
        if (!empty($parameters['first_name'])) {
            $user_data['first_name'] = sanitize_text_field($parameters['first_name']);
        }
        
        if (!empty($parameters['last_name'])) {
            $user_data['last_name'] = sanitize_text_field($parameters['last_name']);
        }
        
        if (!empty($parameters['display_name'])) {
            $user_data['display_name'] = sanitize_text_field($parameters['display_name']);
        }
        
        if (!empty($parameters['email'])) {
            $user_data['user_email'] = sanitize_email($parameters['email']);
        }
        
        // Update user data
        if (!empty($user_data)) {
            $result = wp_update_user($user_data);
            
            if (is_wp_error($result)) {
                return new WP_Error('update_failed', $result->get_error_message(), array('status' => 400));
            }
        }
        
        // Update billing address
        if (!empty($parameters['billing']) && is_array($parameters['billing'])) {
            $billing_updated = $this->update_billing_address($user_id, $parameters['billing']);
            if (!$billing_updated) {
                $update_errors[] = 'Failed to update billing address';
            }
        }
        
        // Update shipping address
        if (!empty($parameters['shipping']) && is_array($parameters['shipping'])) {
            $shipping_updated = $this->update_shipping_address($user_id, $parameters['shipping']);
            if (!$shipping_updated) {
                $update_errors[] = 'Failed to update shipping address';
            }
        }
        
        // Handle any update errors
        if (!empty($update_errors)) {
            return new WP_Error('partial_update', implode(', ', $update_errors), array('status' => 400));
        }
        
        // Clear user cache
        if ($this->cache_manager) {
            $this->cache_manager->clear_user_cache($user_id);
        }
        
        return $this->send_response(null, 200, 'Profile updated successfully');
    }

    /**
     * Update billing address
     */
    private function update_billing_address($user_id, $billing_data) {
        $billing_fields = array(
            'first_name' => 'billing_first_name',
            'last_name' => 'billing_last_name',
            'company' => 'billing_company',
            'address_1' => 'billing_address_1',
            'address_2' => 'billing_address_2',
            'city' => 'billing_city',
            'state' => 'billing_state',
            'postcode' => 'billing_postcode',
            'country' => 'billing_country',
            'email' => 'billing_email',
            'phone' => 'billing_phone'
        );
        
        foreach ($billing_fields as $field => $meta_key) {
            if (isset($billing_data[$field])) {
                $value = sanitize_text_field($billing_data[$field]);
                update_user_meta($user_id, $meta_key, $value);
            }
        }
        
        return true;
    }

    /**
     * Update shipping address
     */
    private function update_shipping_address($user_id, $shipping_data) {
        $shipping_fields = array(
            'first_name' => 'shipping_first_name',
            'last_name' => 'shipping_last_name',
            'company' => 'shipping_company',
            'address_1' => 'shipping_address_1',
            'address_2' => 'shipping_address_2',
            'city' => 'shipping_city',
            'state' => 'shipping_state',
            'postcode' => 'shipping_postcode',
            'country' => 'shipping_country'
        );
        
        foreach ($shipping_fields as $field => $meta_key) {
            if (isset($shipping_data[$field])) {
                $value = sanitize_text_field($shipping_data[$field]);
                update_user_meta($user_id, $meta_key, $value);
            }
        }
        
        return true;
    }
    
    public function forgot_password($request) {
        $parameters = $request->get_params();
        $email = sanitize_email($parameters['email']);
        
        if (!email_exists($email)) {
            return new WP_Error('email_not_found', 'No user found with this email address', array('status' => 404));
        }
        
        // Use WordPress built-in function to send password reset email
        $result = retrieve_password($email);
        
        if (is_wp_error($result)) {
            return new WP_Error('password_reset_failed', $result->get_error_message(), array('status' => 400));
        }
        
        return $this->send_response(null, 200, 'Password reset email sent');
    }
    
    public function logout($request) {
        $user_id = get_current_user_id();
        $token = $this->get_token_from_request($request);
        
        if ($token) {
            $this->invalidate_session_token($user_id, $token);
        }
        
        wp_logout();
        
        // Clear user cache
        if ($this->cache_manager) {
            $this->cache_manager->clear_user_cache($user_id);
        }
        
        return $this->send_response(null, 200, 'Logout successful');
    }
    
    public function validate_token($request) {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        $user_data = array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => get_user_meta($user->ID, 'first_name', true),
            'last_name' => get_user_meta($user->ID, 'last_name', true),
            'token_valid' => true
        );
        
        return $this->send_response($user_data, 200, 'Token is valid');
    }
    
    private function get_billing_address($user_id) {
        return array(
            'first_name' => get_user_meta($user_id, 'billing_first_name', true),
            'last_name' => get_user_meta($user_id, 'billing_last_name', true),
            'company' => get_user_meta($user_id, 'billing_company', true),
            'address_1' => get_user_meta($user_id, 'billing_address_1', true),
            'address_2' => get_user_meta($user_id, 'billing_address_2', true),
            'city' => get_user_meta($user_id, 'billing_city', true),
            'state' => get_user_meta($user_id, 'billing_state', true),
            'postcode' => get_user_meta($user_id, 'billing_postcode', true),
            'country' => get_user_meta($user_id, 'billing_country', true),
            'email' => get_user_meta($user_id, 'billing_email', true),
            'phone' => get_user_meta($user_id, 'billing_phone', true)
        );
    }
    
    private function get_shipping_address($user_id) {
        return array(
            'first_name' => get_user_meta($user_id, 'shipping_first_name', true),
            'last_name' => get_user_meta($user_id, 'shipping_last_name', true),
            'company' => get_user_meta($user_id, 'shipping_company', true),
            'address_1' => get_user_meta($user_id, 'shipping_address_1', true),
            'address_2' => get_user_meta($user_id, 'shipping_address_2', true),
            'city' => get_user_meta($user_id, 'shipping_city', true),
            'state' => get_user_meta($user_id, 'shipping_state', true),
            'postcode' => get_user_meta($user_id, 'shipping_postcode', true),
            'country' => get_user_meta($user_id, 'shipping_country', true)
        );
    }
    
    /**
     * Token Management Functions
     */
    private function generate_session_token($user_id) {
        $token = bin2hex(random_bytes(32)); // 64 character token
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        
        $tokens = get_user_meta($user_id, $this->token_meta_key, true);
        if (!is_array($tokens)) {
            $tokens = array();
        }
        
        // Clean expired tokens
        $tokens = array_filter($tokens, function($token_data) {
            return $token_data['expires'] > time();
        });
        
        // Add new token
        $tokens[$token] = array(
            'created' => time(),
            'expires' => $expires,
            'last_used' => time()
        );
        
        // Limit to 5 active tokens per user
        if (count($tokens) > 5) {
            $tokens = array_slice($tokens, -5, 5, true);
        }
        
        update_user_meta($user_id, $this->token_meta_key, $tokens);
        
        return $token;
    }
    
    private function validate_session_token($token) {
        if (empty($token)) {
            return false;
        }
        
        $users = get_users(array(
            'meta_key' => $this->token_meta_key,
            'fields' => 'ID'
        ));
        
        foreach ($users as $user_id) {
            $tokens = get_user_meta($user_id, $this->token_meta_key, true);
            
            if (is_array($tokens) && isset($tokens[$token])) {
                $token_data = $tokens[$token];
                
                // Check if token is expired
                if ($token_data['expires'] < time()) {
                    $this->invalidate_session_token($user_id, $token);
                    return false;
                }
                
                // Update last used time
                $tokens[$token]['last_used'] = time();
                update_user_meta($user_id, $this->token_meta_key, $tokens);
                
                return $user_id;
            }
        }
        
        return false;
    }
    
    private function invalidate_session_token($user_id, $token) {
        $tokens = get_user_meta($user_id, $this->token_meta_key, true);
        
        if (is_array($tokens) && isset($tokens[$token])) {
            unset($tokens[$token]);
            update_user_meta($user_id, $this->token_meta_key, $tokens);
            return true;
        }
        
        return false;
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
    
    public function clear_user_tokens($user_id) {
        delete_user_meta($user_id, $this->token_meta_key);
    }
    
    // Clean up expired tokens (can be called via cron)
    public function cleanup_expired_tokens() {
        $users = get_users(array(
            'meta_key' => $this->token_meta_key,
            'fields' => 'ID'
        ));
        
        foreach ($users as $user_id) {
            $tokens = get_user_meta($user_id, $this->token_meta_key, true);
            
            if (is_array($tokens)) {
                $valid_tokens = array_filter($tokens, function($token_data) {
                    return $token_data['expires'] > time();
                });
                
                update_user_meta($user_id, $this->token_meta_key, $valid_tokens);
            }
        }
    }
    
    private function send_response($data = null, $status = 200, $message = '') {
        $response = array(
            'status' => $status,
            'message' => $message,
            'data' => $data
        );
        return new WP_REST_Response($response, $status);
    }
}
?>