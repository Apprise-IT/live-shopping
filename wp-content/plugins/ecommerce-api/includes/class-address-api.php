<?php
class Address_API {
    
    private $namespace = 'ecommerce-api/v1';
    private $cache_manager;
    
    public function __construct($cache_manager = null) {
        $this->cache_manager = $cache_manager;
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        // Get addresses
        register_rest_route($this->namespace, '/addresses', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_addresses'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        // Add address
        register_rest_route($this->namespace, '/addresses/add', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_address'),
            'permission_callback' => array($this, 'check_auth'),
            'args' => $this->get_address_args()
        ));
        
        // Update address
        register_rest_route($this->namespace, '/addresses/update', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_address'),
            'permission_callback' => array($this, 'check_auth'),
            'args' => $this->get_address_args()
        ));
        
        // Delete address
        register_rest_route($this->namespace, '/addresses/delete', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_address'),
            'permission_callback' => array($this, 'check_auth'),
            'args' => $this->get_delete_args()
        ));
        
        // Set default address
        register_rest_route($this->namespace, '/addresses/set-default', array(
            'methods' => 'POST',
            'callback' => array($this, 'set_default_address'),
            'permission_callback' => array($this, 'check_auth'),
            'args' => $this->get_default_address_args()
        ));
        
        // Get countries and states
        register_rest_route($this->namespace, '/addresses/countries', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_countries_states'),
            'permission_callback' => '__return_true'
        ));
    }
    
    private function get_address_args() {
        return array(
            'type' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return in_array($param, array('billing', 'shipping'));
                },
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'first_name' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_string($param) && strlen(trim($param)) > 0;
                },
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'last_name' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_string($param) && strlen(trim($param)) > 0;
                },
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'company' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'address_1' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_string($param) && strlen(trim($param)) > 0;
                },
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'address_2' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'city' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_string($param) && strlen(trim($param)) > 0;
                },
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'state' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_string($param) && strlen(trim($param)) > 0;
                },
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'postcode' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_string($param) && strlen(trim($param)) > 0;
                },
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'country' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_string($param) && strlen(trim($param)) === 2;
                },
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'email' => array(
                'required' => false,
                'validate_callback' => function($param) {
                    return empty($param) || is_email($param);
                },
                'sanitize_callback' => 'sanitize_email'
            ),
            'phone' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'is_default' => array(
                'required' => false,
                'validate_callback' => function($param) {
                    return in_array($param, array('true', 'false', '1', '0', true, false));
                },
                'sanitize_callback' => function($param) {
                    return filter_var($param, FILTER_VALIDATE_BOOLEAN);
                }
            )
        );
    }
    
    private function get_delete_args() {
        return array(
            'type' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return in_array($param, array('billing', 'shipping'));
                },
                'sanitize_callback' => 'sanitize_text_field'
            )
        );
    }
    
    private function get_default_address_args() {
        return array(
            'type' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return in_array($param, array('billing', 'shipping'));
                },
                'sanitize_callback' => 'sanitize_text_field'
            )
        );
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
    
    public function get_addresses($request) {
        try {
            $user_id = get_current_user_id();
            $cache_key = $this->cache_manager ? $this->cache_manager->generate_key("user_addresses_{$user_id}") : null;
            
            // Try cache first
            if ($this->cache_manager && $cached = $this->cache_manager->get($cache_key)) {
                return $this->send_success($cached);
            }
            
            $billing_address = $this->get_formatted_address($user_id, 'billing');
            $shipping_address = $this->get_formatted_address($user_id, 'shipping');
            
            $addresses = array(
                'billing' => $billing_address,
                'shipping' => $shipping_address,
                'default_billing' => get_user_meta($user_id, 'default_billing_address', true),
                'default_shipping' => get_user_meta($user_id, 'default_shipping_address', true)
            );
            
            // Cache the result
            if ($this->cache_manager) {
                $this->cache_manager->set($cache_key, $addresses, 1800); // 30 minutes
            }
            
            return $this->send_success($addresses);
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_addresses', array(
                'message' => $e->getMessage(),
                'user_id' => get_current_user_id()
            ));
            return $this->send_error('Failed to retrieve addresses', 500);
        }
    }
    
    public function add_address($request) {
        try {
            $parameters = $request->get_params();
            $user_id = get_current_user_id();
            $type = $parameters['type'];
            
            $address_fields = $this->get_address_fields($parameters, $type);
            
            foreach ($address_fields as $field => $value) {
                update_user_meta($user_id, $type . '_' . $field, $value);
            }
            
            // Set as default if requested
            if (isset($parameters['is_default']) && $parameters['is_default']) {
                update_user_meta($user_id, 'default_' . $type . '_address', $type);
            }
            
            // Clear cache
            if ($this->cache_manager) {
                $this->cache_manager->delete("user_addresses_{$user_id}");
            }
            
            return $this->send_success(null, 201, 'Address added successfully');
            
        } catch (Exception $e) {
            $this->log_error('Exception in add_address', array(
                'message' => $e->getMessage(),
                'user_id' => get_current_user_id(),
                'type' => $parameters['type'] ?? 'unknown'
            ));
            return $this->send_error('Failed to add address', 500);
        }
    }
    
    public function update_address($request) {
        try {
            $parameters = $request->get_params();
            $user_id = get_current_user_id();
            $type = $parameters['type'];
            
            $address_fields = $this->get_address_fields($parameters, $type);
            
            foreach ($address_fields as $field => $value) {
                update_user_meta($user_id, $type . '_' . $field, $value);
            }
            
            // Set as default if requested
            if (isset($parameters['is_default'])) {
                if ($parameters['is_default']) {
                    update_user_meta($user_id, 'default_' . $type . '_address', $type);
                } else {
                    $current_default = get_user_meta($user_id, 'default_' . $type . '_address', true);
                    if ($current_default === $type) {
                        delete_user_meta($user_id, 'default_' . $type . '_address');
                    }
                }
            }
            
            // Clear cache
            if ($this->cache_manager) {
                $this->cache_manager->delete("user_addresses_{$user_id}");
            }
            
            return $this->send_success(null, 200, 'Address updated successfully');
            
        } catch (Exception $e) {
            $this->log_error('Exception in update_address', array(
                'message' => $e->getMessage(),
                'user_id' => get_current_user_id(),
                'type' => $parameters['type'] ?? 'unknown'
            ));
            return $this->send_error('Failed to update address', 500);
        }
    }
    
    public function delete_address($request) {
        try {
            $parameters = $request->get_params();
            $user_id = get_current_user_id();
            $type = $parameters['type'];
            
            $address_fields = array(
                'first_name', 'last_name', 'company', 'address_1', 'address_2',
                'city', 'state', 'postcode', 'country', 'email', 'phone'
            );
            
            foreach ($address_fields as $field) {
                delete_user_meta($user_id, $type . '_' . $field);
            }
            
            // Clear default if this was the default address
            $current_default = get_user_meta($user_id, 'default_' . $type . '_address', true);
            if ($current_default === $type) {
                delete_user_meta($user_id, 'default_' . $type . '_address');
            }
            
            // Clear cache
            if ($this->cache_manager) {
                $this->cache_manager->delete("user_addresses_{$user_id}");
            }
            
            return $this->send_success(null, 200, 'Address deleted successfully');
            
        } catch (Exception $e) {
            $this->log_error('Exception in delete_address', array(
                'message' => $e->getMessage(),
                'user_id' => get_current_user_id(),
                'type' => $parameters['type'] ?? 'unknown'
            ));
            return $this->send_error('Failed to delete address', 500);
        }
    }
    
    public function set_default_address($request) {
        try {
            $parameters = $request->get_params();
            $user_id = get_current_user_id();
            $type = $parameters['type'];
            
            // Verify address exists
            $address_1 = get_user_meta($user_id, $type . '_address_1', true);
            if (empty($address_1)) {
                return $this->send_error('Address not found', 404);
            }
            
            update_user_meta($user_id, 'default_' . $type . '_address', $type);
            
            // Clear cache
            if ($this->cache_manager) {
                $this->cache_manager->delete("user_addresses_{$user_id}");
            }
            
            return $this->send_success(null, 200, 'Default address set successfully');
            
        } catch (Exception $e) {
            $this->log_error('Exception in set_default_address', array(
                'message' => $e->getMessage(),
                'user_id' => get_current_user_id(),
                'type' => $parameters['type'] ?? 'unknown'
            ));
            return $this->send_error('Failed to set default address', 500);
        }
    }
    
    public function get_countries_states($request) {
        try {
            $cache_key = 'countries_states';
            
            // Try cache first
            if ($this->cache_manager && $cached = $this->cache_manager->get($cache_key)) {
                return $this->send_success($cached);
            }
            
            if (!class_exists('WC_Countries')) {
                return $this->send_error('WooCommerce not available', 500);
            }
            
            $wc_countries = new WC_Countries();
            $countries = $wc_countries->get_countries();
            $states = $wc_countries->get_states();
            
            $formatted_countries = array();
            
            foreach ($countries as $code => $name) {
                $formatted_countries[] = array(
                    'code' => $code,
                    'name' => $name,
                    'states' => isset($states[$code]) ? $states[$code] : array()
                );
            }
            
            $response = array(
                'countries' => $formatted_countries,
                'default_country' => $wc_countries->get_base_country(),
                'default_state' => $wc_countries->get_base_state()
            );
            
            // Cache for longer period as this rarely changes
            if ($this->cache_manager) {
                $this->cache_manager->set($cache_key, $response, 86400); // 24 hours
            }
            
            return $this->send_success($response);
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_countries_states', array(
                'message' => $e->getMessage()
            ));
            return $this->send_error('Failed to retrieve countries and states', 500);
        }
    }
    
    private function get_formatted_address($user_id, $type) {
        $address = array(
            'first_name' => get_user_meta($user_id, $type . '_first_name', true),
            'last_name' => get_user_meta($user_id, $type . '_last_name', true),
            'company' => get_user_meta($user_id, $type . '_company', true),
            'address_1' => get_user_meta($user_id, $type . '_address_1', true),
            'address_2' => get_user_meta($user_id, $type . '_address_2', true),
            'city' => get_user_meta($user_id, $type . '_city', true),
            'state' => get_user_meta($user_id, $type . '_state', true),
            'postcode' => get_user_meta($user_id, $type . '_postcode', true),
            'country' => get_user_meta($user_id, $type . '_country', true)
        );
        
        if ($type === 'billing') {
            $address['email'] = get_user_meta($user_id, 'billing_email', true);
            $address['phone'] = get_user_meta($user_id, 'billing_phone', true);
        }
        
        // Format the address using WooCommerce formatter
        if (class_exists('WC_Countries')) {
            $wc_countries = new WC_Countries();
            $formatted = $wc_countries->get_formatted_address($address);
            $address['formatted'] = $formatted;
        }
        
        return $address;
    }
    
    private function get_address_fields($parameters, $type) {
        $fields = array();
        
        $address_fields = array(
            'first_name', 'last_name', 'company', 'address_1', 'address_2',
            'city', 'state', 'postcode', 'country'
        );
        
        foreach ($address_fields as $field) {
            if (isset($parameters[$field])) {
                $fields[$field] = sanitize_text_field($parameters[$field]);
            }
        }
        
        // Additional fields for billing address
        if ($type === 'billing') {
            if (isset($parameters['email'])) {
                $fields['email'] = sanitize_email($parameters['email']);
            }
            if (isset($parameters['phone'])) {
                $fields['phone'] = sanitize_text_field($parameters['phone']);
            }
        }
        
        return $fields;
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
    
    private function log_error($message, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Address_API Error: ' . $message . ' - Context: ' . json_encode($context));
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
            'address_api_error',
            $message,
            array('status' => $status)
        );
    }
}
?>