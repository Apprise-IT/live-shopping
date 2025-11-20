<?php
class Utilities {
    
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

    public static function get_client_ip() {
        $ip_keys = array(
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    public static function validate_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function sanitize_text($text) {
        return sanitize_text_field($text);
    }
    
    public static function sanitize_html($html) {
        return wp_kses_post($html);
    }
    
    public static function log_api_request($endpoint, $user_id, $status, $message = '') {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'endpoint' => $endpoint,
            'user_id' => $user_id,
            'ip_address' => self::get_client_ip(),
            'status' => $status,
            'message' => $message
        );
        
        // Get existing logs
        $logs = get_option('emapi_request_logs', array());
        
        // Keep only last 1000 entries
        if (count($logs) >= 1000) {
            array_shift($logs);
        }
        
        $logs[] = $log_entry;
        update_option('emapi_request_logs', $logs);
    }
    
    public static function format_price($price) {
        return floatval($price);
    }
    
    public static function get_pagination_links($current_page, $total_pages, $base_url) {
        $links = array();
        
        if ($total_pages <= 1) {
            return $links;
        }
        
        if ($current_page > 1) {
            $links['prev'] = add_query_arg('page', $current_page - 1, $base_url);
        }
        
        if ($current_page < $total_pages) {
            $links['next'] = add_query_arg('page', $current_page + 1, $base_url);
        }
        
        $links['first'] = add_query_arg('page', 1, $base_url);
        $links['last'] = add_query_arg('page', $total_pages, $base_url);
        $links['current'] = $current_page;
        $links['total'] = $total_pages;
        
        return $links;
    }

    public static function generate_random_string($length = 32) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characters_length = strlen($characters);
        $random_string = '';
        
        for ($i = 0; $i < $length; $i++) {
            $random_string .= $characters[rand(0, $characters_length - 1)];
        }
        
        return $random_string;
    }

    public static function format_bytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public static function get_current_timestamp() {
        return current_time('timestamp');
    }

    public static function get_current_datetime() {
        return current_time('mysql');
    }

    public static function is_valid_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public static function array_to_xml($array, $root_element = 'root', $xml = null) {
        if ($xml === null) {
            $xml = new SimpleXMLElement('<' . $root_element . '/>');
        }
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item_' . $key;
                }
                self::array_to_xml($value, $key, $xml->addChild($key));
            } else {
                if (is_numeric($key)) {
                    $key = 'item_' . $key;
                }
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
        
        return $xml->asXML();
    }

    public static function validate_phone_number($phone) {
        // Basic phone validation - can be customized based on requirements
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        return strlen($cleaned) >= 10;
    }

    public static function get_time_ago($datetime) {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $time);
        }
    }

    public static function truncate_text($text, $length = 100, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        $truncated = substr($text, 0, $length);
        $last_space = strrpos($truncated, ' ');
        
        if ($last_space !== false) {
            $truncated = substr($truncated, 0, $last_space);
        }
        
        return $truncated . $suffix;
    }

    public static function slugify($text) {
        // Replace non-letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        
        // Transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        
        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        
        // Trim
        $text = trim($text, '-');
        
        // Remove duplicate -
        $text = preg_replace('~-+~', '-', $text);
        
        // Lowercase
        $text = strtolower($text);
        
        if (empty($text)) {
            return 'n-a';
        }
        
        return $text;
    }

    public static function get_file_extension($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    public static function is_image_file($filename) {
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        $extension = self::get_file_extension($filename);
        return in_array($extension, $allowed_extensions);
    }

    public static function get_gravatar_url($email, $size = 80) {
        $hash = md5(strtolower(trim($email)));
        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp";
    }

    public static function array_sort_by_column(&$array, $column, $direction = SORT_ASC) {
        $sort_column = array();
        foreach ($array as $key => $row) {
            $sort_column[$key] = $row[$column];
        }
        array_multisort($sort_column, $direction, $array);
    }

    public static function get_browser_info() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return array(
            'user_agent' => $user_agent,
            'is_mobile' => wp_is_mobile(),
            'ip_address' => self::get_client_ip()
        );
    }

    public static function generate_qr_code_url($data, $size = 200) {
        $encoded_data = urlencode($data);
        return "https://api.qrserver.com/v1/create-qr-code/?data={$encoded_data}&size={$size}x{$size}";
    }

    public static function calculate_discount_percentage($original_price, $sale_price) {
        if ($original_price <= 0) {
            return 0;
        }
        
        $discount = $original_price - $sale_price;
        $percentage = ($discount / $original_price) * 100;
        
        return round($percentage, 2);
    }

    public static function validate_credit_card($number) {
        // Remove any non-digit characters
        $number = preg_replace('/\D/', '', $number);
        
        // Check if the number is numeric and has proper length
        if (!is_numeric($number) || strlen($number) < 13 || strlen($number) > 19) {
            return false;
        }
        
        // Luhn algorithm
        $sum = 0;
        $reverse = strrev($number);
        
        for ($i = 0; $i < strlen($reverse); $i++) {
            $digit = (int)$reverse[$i];
            
            if ($i % 2 == 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            
            $sum += $digit;
        }
        
        return $sum % 10 == 0;
    }

    public static function get_country_name($country_code) {
        $countries = WC()->countries->get_countries();
        return $countries[$country_code] ?? $country_code;
    }

    public static function format_order_number($order_id) {
        return '#' . str_pad($order_id, 8, '0', STR_PAD_LEFT);
    }

    public static function get_woocommerce_currency_info() {
        return array(
            'currency' => get_woocommerce_currency(),
            'currency_symbol' => get_woocommerce_currency_symbol(),
            'currency_position' => get_option('woocommerce_currency_pos'),
            'thousand_separator' => get_option('woocommerce_price_thousand_sep'),
            'decimal_separator' => get_option('woocommerce_price_decimal_sep'),
            'number_of_decimals' => get_option('woocommerce_price_num_decimals')
        );
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
}
?>