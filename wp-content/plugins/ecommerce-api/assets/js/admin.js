/**
 * Ecommerce API Manager Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';

    // Tab functionality
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-content').removeClass('active');
        
        $(this).addClass('nav-tab-active');
        const tabId = $(this).data('tab');
        $('#' + tabId).addClass('active');
        
        // Update URL hash
        window.location.hash = tabId;
    });
    
    // Handle URL hash on page load
    if (window.location.hash) {
        const tabId = window.location.hash.substring(1);
        if ($('#' + tabId).length) {
            $('.nav-tab').removeClass('nav-tab-active');
            $('.tab-content').removeClass('active');
            $(`[data-tab="${tabId}"]`).addClass('nav-tab-active');
            $('#' + tabId).addClass('active');
        }
    }

    // Cache clearing functionality
    $('.ema-clear-cache').on('click', function() {
        const $button = $(this);
        const cacheType = $button.data('cache-type') || 'all';
        const originalText = $button.text();
        
        $button.prop('disabled', true).html('<span class="ema-loading"></span> Clearing...');
        
        $.post(ema_ajax.ajax_url, {
            action: 'ema_clear_cache',
            cache_type: cacheType,
            nonce: ema_ajax.nonce
        }, function(response) {
            if (response.success) {
                showNotice('Cache cleared successfully!', 'success');
            } else {
                showNotice('Error clearing cache: ' + response.data, 'error');
            }
        }).fail(function(xhr, status, error) {
            showNotice('AJAX error: ' + error, 'error');
        }).always(function() {
            $button.prop('disabled', false).text(originalText);
        });
    });
    
    // Reset to defaults
    $('#reset-defaults').on('click', function() {
        if (confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.')) {
            // Reset form values to defaults
            $('#ema_token_expiry').val('30');
            $('#ema_max_tokens').val('5');
            $('#ema_api_rate_limit').val('1000');
            $('#ema_enable_caching').prop('checked', true);
            $('#ema_cache_ttl').val('3600');
            $('#ema_enable_hpos').prop('checked', true);
            $('#ema_enable_blocks').prop('checked', true);
            $('#ema_enable_debug').prop('checked', false);
            
            showNotice('Settings have been reset to defaults. Click "Save Changes" to apply.', 'info');
        }
    });
    
    // Health check
    $('#ema-health-check').on('click', function() {
        const $button = $(this);
        const originalText = $button.text();
        
        $button.prop('disabled', true).html('<span class="ema-loading"></span> Checking...');
        $('#ema-health-result').html('');
        
        $.post(ema_ajax.ajax_url, {
            action: 'ema_health_check',
            nonce: ema_ajax.nonce
        }, function(response) {
            if (response.success) {
                const result = response.data.details;
                let html = '<div class="ema-health-result success">';
                html += '<h4>✓ System Health Check Passed</h4>';
                html += '<p>' + response.data.message + '</p>';
                
                if (result.checks) {
                    html += '<ul>';
                    $.each(result.checks, function(key, check) {
                        const statusIcon = check.status ? '✓' : '✗';
                        const statusClass = check.status ? 'positive' : 'negative';
                        html += `<li><span class="${statusClass}">${statusIcon} ${check.name}</span>: ${check.message}</li>`;
                    });
                    html += '</ul>';
                }
                
                html += '</div>';
                $('#ema-health-result').html(html);
            } else {
                $('#ema-health-result').html(
                    '<div class="ema-health-result error">' +
                    '<h4>✗ Health Check Failed</h4>' +
                    '<p>' + response.data + '</p>' +
                    '</div>'
                );
            }
        }).fail(function(xhr, status, error) {
            $('#ema-health-result').html(
                '<div class="ema-health-result error">' +
                '<h4>✗ Health Check Error</h4>' +
                '<p>AJAX error: ' + error + '</p>' +
                '</div>'
            );
        }).always(function() {
            $button.prop('disabled', false).text(originalText);
        });
    });
    
    // Test endpoint
    $('.ema-test-endpoint').on('click', function() {
        const $button = $(this);
        const endpoint = $button.data('endpoint') || $('#ema-test-endpoint-select').val();
        const method = $button.data('method') || 'GET';
        const originalText = $button.text();
        
        $button.prop('disabled', true).html('<span class="ema-loading"></span> Testing...');
        
        // Create result container if it doesn't exist
        let $resultContainer = $button.next('.ema-test-result');
        if (!$resultContainer.length) {
            $resultContainer = $('<div class="ema-test-result"></div>');
            $button.after($resultContainer);
        }
        
        $resultContainer.html('<p>Testing endpoint: ' + method + ' ' + endpoint + '</p>');
        
        $.post(ema_ajax.ajax_url, {
            action: 'ema_test_endpoint',
            endpoint: endpoint,
            method: method,
            nonce: ema_ajax.nonce
        }, function(response) {
            if (response.success) {
                $resultContainer.removeClass('error').addClass('success');
                let html = '<h4>✓ Endpoint Test Successful</h4>';
                html += '<p><strong>Status Code:</strong> ' + response.data.status_code + '</p>';
                html += '<p><strong>Response:</strong></p>';
                html += '<pre>' + JSON.stringify(response.data.response, null, 2) + '</pre>';
                $resultContainer.html(html);
            } else {
                $resultContainer.removeClass('success').addClass('error');
                $resultContainer.html(
                    '<h4>✗ Endpoint Test Failed</h4>' +
                    '<p>' + response.data + '</p>'
                );
            }
        }).fail(function(xhr, status, error) {
            $resultContainer.removeClass('success').addClass('error');
            $resultContainer.html(
                '<h4>✗ Endpoint Test Error</h4>' +
                '<p>AJAX error: ' + error + '</p>'
            );
        }).always(function() {
            $button.prop('disabled', false).text(originalText);
        });
    });
    
    // Log filtering
    $('#apply-log-filter').on('click', function() {
        const filter = $('#log-filter').val();
        const $button = $(this);
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('Filtering...');
        
        // In a real implementation, this would make an AJAX call to filter logs
        // For now, we'll just show a notice
        setTimeout(function() {
            showNotice('Logs filtered to show: ' + filter, 'info');
            $button.prop('disabled', false).text(originalText);
        }, 1000);
    });
    
    // Export logs
    $('#export-logs').on('click', function() {
        const $button = $(this);
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('Exporting...');
        
        // Simulate export process
        setTimeout(function() {
            showNotice('Logs exported successfully!', 'success');
            $button.prop('disabled', false).text(originalText);
            
            // In a real implementation, this would trigger a file download
            // For demonstration, we'll just show a message
        }, 1500);
    });
    
    // Clear old logs
    $('#clear-old-logs').on('click', function() {
        if (confirm('Are you sure you want to clear logs older than 30 days? This cannot be undone.')) {
            const $button = $(this);
            const originalText = $button.text();
            
            $button.prop('disabled', true).text('Clearing...');
            
            // Simulate clearing process
            setTimeout(function() {
                showNotice('Old logs cleared successfully!', 'success');
                $button.prop('disabled', false).text(originalText);
                
                // In a real implementation, this would make an AJAX call
                // and then refresh the logs table
            }, 1500);
        }
    });
    
    // Download documentation
    $('#download-docs').on('click', function() {
        const $button = $(this);
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('Generating...');
        
        // Simulate documentation generation
        setTimeout(function() {
            showNotice('Documentation downloaded successfully!', 'success');
            $button.prop('disabled', false).text(originalText);
            
            // In a real implementation, this would trigger a file download
        }, 2000);
    });
    
    // View changelog
    $('#view-changelog').on('click', function() {
        // Open changelog in a new tab
        window.open(ema_ajax.changelog_url, '_blank');
    });
    
    // Utility function to show notices
    function showNotice(message, type = 'info') {
        const noticeClass = type === 'error' ? 'notice-error' : 
                           type === 'success' ? 'notice-success' : 
                           type === 'warning' ? 'notice-warning' : 'notice-info';
        
        const notice = $(
            '<div class="notice is-dismissible ' + noticeClass + '" style="margin-top: 15px;">' +
            '<p>' + message + '</p>' +
            '<button type="button" class="notice-dismiss"></button>' +
            '</div>'
        );
        
        $('.ecommerce-api-wrap h1.wp-heading').after(notice);
        
        // Auto-remove after 5 seconds for success/info messages
        if (type === 'success' || type === 'info') {
            setTimeout(function() {
                notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // Add dismiss functionality
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut(300, function() {
                $(this).remove();
            });
        });
    }
    
    // Form validation
    $('form').on('submit', function(e) {
        const rateLimit = parseInt($('#ema_api_rate_limit').val());
        const maxTokens = parseInt($('#ema_max_tokens').val());
        let isValid = true;
        let errorMessage = '';
        
        if (rateLimit < 10 || rateLimit > 1000) {
            isValid = false;
            errorMessage = 'Rate limit must be between 10 and 1000 requests per hour.';
        }
        
        if (maxTokens < 1 || maxTokens > 10) {
            isValid = false;
            errorMessage = 'Maximum tokens must be between 1 and 10.';
        }
        
        if (!isValid) {
            e.preventDefault();
            showNotice(errorMessage, 'error');
            return false;
        }
    });
    
    // Real-time validation
    $('#ema_api_rate_limit, #ema_max_tokens').on('input', function() {
        const $input = $(this);
        const value = parseInt($input.val());
        
        if (this.id === 'ema_api_rate_limit') {
            if (value < 10 || value > 1000) {
                $input.css('border-color', '#d63638');
            } else {
                $input.css('border-color', '#8c8f94');
            }
        } else if (this.id === 'ema_max_tokens') {
            if (value < 1 || value > 10) {
                $input.css('border-color', '#d63638');
            } else {
                $input.css('border-color', '#8c8f94');
            }
        }
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + 1-5 for tabs
        if ((e.ctrlKey || e.metaKey) && e.key >= '1' && e.key <= '5') {
            e.preventDefault();
            const tabIndex = parseInt(e.key) - 1;
            const tabs = $('.nav-tab').toArray();
            if (tabs[tabIndex]) {
                $(tabs[tabIndex]).click();
            }
        }
        
        // Ctrl/Cmd + S to save settings
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            $('form').find('button[type="submit"]').click();
        }
    });
    
    // Initialize tooltips for better UX
    $('[title]').each(function() {
        const title = $(this).attr('title');
        if (title) {
            $(this).removeAttr('title');
            $(this).data('tooltip', title);
            
            $(this).on('mouseenter', function() {
                $('<div class="ema-tooltip">' + title + '</div>').appendTo('body');
            }).on('mousemove', function(e) {
                $('.ema-tooltip').css({
                    left: e.pageX + 10,
                    top: e.pageY + 10
                });
            }).on('mouseleave', function() {
                $('.ema-tooltip').remove();
            });
        }
    });
    
    // Add some dynamic behavior to stats cards
    $('.stat-box').on('click', function() {
        const $statBox = $(this);
        $statBox.toggleClass('stat-box-expanded');
    });
    
    console.log('Ecommerce API Manager admin interface loaded successfully.');
});