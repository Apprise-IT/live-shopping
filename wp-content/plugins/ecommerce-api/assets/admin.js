jQuery(document).ready(function($) {
    'use strict';
    
    // Health check functionality
    $('#ema-health-check').on('click', function() {
        const $button = $(this);
        const $result = $('#ema-health-result');
        
        $button.prop('disabled', true).text('Checking...');
        $result.html('<div class="notice notice-info"><p>Running comprehensive health check...</p></div>');
        
        $.ajax({
            url: ema_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'ema_health_check',
                nonce: ema_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success"><p>✅ ' + response.data.message + '</p></div>');
                    if (response.data.details) {
                        $result.append('<div class="ema-health-details"><pre>' + JSON.stringify(response.data.details, null, 2) + '</pre></div>');
                    }
                } else {
                    $result.html('<div class="notice notice-error"><p>❌ ' + response.data + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                $result.html('<div class="notice notice-error"><p>❌ Health check failed: ' + error + '</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false).text('Run Health Check');
            }
        });
    });
    
    // Cache management
    $('.ema-clear-cache').on('click', function() {
        const $button = $(this);
        const cacheType = $button.data('cache-type');
        
        $button.prop('disabled', true).text('Clearing...');
        
        $.ajax({
            url: ema_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'ema_clear_cache',
                cache_type: cacheType,
                nonce: ema_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Cache cleared successfully!', 'success');
                } else {
                    showNotice('Failed to clear cache: ' + response.data, 'error');
                }
            },
            error: function() {
                showNotice('Failed to clear cache', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text('Clear Cache');
            }
        });
    });
    
    // Real-time API testing
    $('.ema-test-endpoint').on('click', function() {
        const $button = $(this);
        const endpoint = $('#ema-test-endpoint-select').val() || $button.data('endpoint');
        const method = $button.data('method') || 'GET';
        
        $button.prop('disabled', true).text('Testing...');
        
        $.ajax({
            url: ema_ajax.api_url + endpoint,
            method: method,
            headers: {
                'X-WP-Nonce': ema_ajax.nonce
            },
            success: function(response) {
                showNotice('✅ API endpoint working correctly!', 'success');
                console.log('API Response:', response);
            },
            error: function(xhr) {
                showNotice('❌ API endpoint failed: ' + xhr.status + ' ' + xhr.statusText, 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text('Test Endpoint');
            }
        });
    });
    
    // Settings tabs
    $('.ema-tab').on('click', function(e) {
        e.preventDefault();
        const tab = $(this).data('tab');
        
        $('.ema-tab').removeClass('active');
        $('.ema-tab-content').removeClass('active');
        
        $(this).addClass('active');
        $('#ema-tab-' + tab).addClass('active');
    });
    
    function showNotice(message, type) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const notice = $(
            '<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>'
        );
        
        $('.wrap').prepend(notice);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Make dismissible
        notice.on('click', '.notice-dismiss', function() {
            notice.remove();
        });
    }
    
    // Performance monitoring
    let performanceData = {
        startTime: Date.now(),
        memoryUsage: 0
    };
    
    // Track form submissions
    $('form').on('submit', function() {
        performanceData.startTime = Date.now();
    });
    
    // Update memory usage periodically
    function updateMemoryUsage() {
        $.ajax({
            url: ema_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'ema_get_memory_usage',
                nonce: ema_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#ema-memory-usage').text(response.data.memory_usage);
                }
            }
        });
    }
    
    // Update every 30 seconds
    setInterval(updateMemoryUsage, 30000);
    updateMemoryUsage(); // Initial call
});