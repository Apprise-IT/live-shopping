<?php
/**
 * Plugin Name: Disable Sessions for REST API
 * Description: Prevents session interference with REST API requests
 */

add_action('init', function() {
    // Only close sessions for REST API requests
    if (defined('REST_REQUEST') && REST_REQUEST) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
});

// Prevent session start for REST API
add_action('wp_loaded', function() {
    if (defined('REST_REQUEST') && REST_REQUEST) {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_cookies', 0);
        }
    }
});