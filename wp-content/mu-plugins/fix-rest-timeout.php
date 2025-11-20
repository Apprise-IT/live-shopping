<?php
/**
 * Plugin Name: Fix REST API Timeout
 * Description: Fix REST API timeout issues on localhost
 */

// Increase all timeouts
add_filter('http_request_timeout', function($timeout) {
    return 60; // 60 seconds
});

// Fix SSL verification for localhost
add_filter('https_ssl_verify', function($verify, $url) {
    if (strpos($url, 'localhost') !== false || strpos($url, '127.0.0.1') !== false) {
        return false;
    }
    return $verify;
}, 10, 2);

// Fix localhost URL issues
add_filter('http_request_host_is_external', function($allow, $host, $url) {
    $local_hosts = ['localhost', '127.0.0.1', '::1'];
    if (in_array($host, $local_hosts)) {
        return true;
    }
    return $allow;
}, 10, 3);