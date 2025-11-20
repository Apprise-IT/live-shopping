<?php
class Cache_Manager {
    
    private $cache_group = 'ecommerce_api';
    private $default_ttl = 3600; // 1 hour
    
    public function __construct() {
        add_action('save_post', array($this, 'clear_product_cache'));
        add_action('woocommerce_update_product', array($this, 'clear_product_cache'));
        add_action('woocommerce_new_order', array($this, 'clear_order_cache'));
        add_action('woocommerce_update_order', array($this, 'clear_order_cache'));
        add_action('comment_post', array($this, 'clear_review_cache'));
        add_action('wp_update_comment_count', array($this, 'clear_review_cache'));
    }
    
    public function get($key, $group = '') {
        $group = $group ?: $this->cache_group;
        $key = $this->normalize_key($key);
        
        $cached = wp_cache_get($key, $group);
        
        if ($cached && isset($cached['data']) && isset($cached['expires'])) {
            if ($cached['expires'] > time()) {
                return $cached['data'];
            } else {
                // Clean expired cache
                wp_cache_delete($key, $group);
            }
        }
        
        return false;
    }
    
    public function set($key, $data, $ttl = null, $group = '') {
        $group = $group ?: $this->cache_group;
        $key = $this->normalize_key($key);
        $ttl = $ttl ?: $this->default_ttl;
        
        $cache_data = array(
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        );
        
        return wp_cache_set($key, $cache_data, $group, $ttl);
    }
    
    public function delete($key, $group = '') {
        $group = $group ?: $this->cache_group;
        $key = $this->normalize_key($key);
        
        return wp_cache_delete($key, $group);
    }
    
    public function clear_group($group = '') {
        $group = $group ?: $this->cache_group;
        
        // WordPress doesn't have built-in group clearing, so we use a pattern
        global $wp_object_cache;
        if (isset($wp_object_cache->cache[$group])) {
            unset($wp_object_cache->cache[$group]);
        }
    }
    
    public function clear_all() {
        wp_cache_flush();
    }
    
    public function clear_product_cache($product_id = null) {
        if ($product_id) {
            $this->delete("product_{$product_id}");
            $this->delete("product_reviews_{$product_id}");
            $this->delete("product_rating_{$product_id}");
        }
        
        $this->clear_group('products');
        $this->delete('featured_products');
        $this->delete('recent_products');
    }
    
    public function clear_order_cache($order_id = null) {
        if ($order_id) {
            $this->delete("order_{$order_id}");
        }
        
        $this->clear_group('orders');
    }
    
    public function clear_review_cache($comment_id = null) {
        if ($comment_id) {
            $comment = get_comment($comment_id);
            if ($comment && $comment->comment_post_ID) {
                $this->clear_product_cache($comment->comment_post_ID);
            }
        }
        
        $this->clear_group('reviews');
    }
    
    public function clear_user_cache($user_id = null) {
        if ($user_id) {
            $this->delete("user_{$user_id}");
            $this->delete("user_orders_{$user_id}");
            $this->delete("user_reviews_{$user_id}");
        }
        
        $this->clear_group('users');
    }
    
    public function get_cache_stats() {
        global $wp_object_cache;
        
        $stats = array(
            'enabled' => wp_using_ext_object_cache(),
            'groups' => array(),
            'total_items' => 0
        );
        
        if (isset($wp_object_cache->cache[$this->cache_group])) {
            $stats['groups'][$this->cache_group] = count($wp_object_cache->cache[$this->cache_group]);
            $stats['total_items'] += $stats['groups'][$this->cache_group];
        }
        
        return $stats;
    }
    
    private function normalize_key($key) {
        return md5(serialize($key));
    }
    
    public function generate_key($prefix, $parameters = array()) {
        if (empty($parameters)) {
            return $prefix;
        }
        
        ksort($parameters); // Sort for consistent key generation
        return $prefix . '_' . md5(serialize($parameters));
    }
}
?>