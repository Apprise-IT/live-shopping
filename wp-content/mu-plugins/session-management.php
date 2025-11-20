<?php
/**
 * Plugin Name: Smart Session Management
 * Description: Properly handle sessions for REST API
 */

class Smart_Session_Handler {
    public function __construct() {
        add_action('plugins_loaded', array($this, 'maybe_start_session'));
        add_action('shutdown', array($this, 'maybe_close_session'));
    }
    
    public function maybe_start_session() {
        // Don't start sessions for REST API
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }
        
        // Don't start sessions for admin-ajax
        if (wp_doing_ajax()) {
            return;
        }
        
        // Start session only for regular page loads
        if (!wp_doing_cron() && !wp_doing_ajax() && !defined('REST_REQUEST')) {
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                session_start();
            }
        }
    }
    
    public function maybe_close_session() {
        // Always close session before HTTP requests
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
}

new Smart_Session_Handler();