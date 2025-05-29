/**
 * AI Job Search Board - Admin JavaScript
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        AJSB_Admin.init();
    });
    
    // Main admin object
    window.AJSB_Admin = {
        
        // Initialize all admin functionality
        init: function() {
            this.initApplicationManagement();
            this.initJobManagement();
            this.initAnalytics();
            this.initSettings();
            this.initFilters();
        },
        
        // Initialize application management
        initApplicationManagement: function() {
            var self = this;
            
            // Application status change
            $(document).on('change', '.ajsb-status-select', function() {
                var $select = $(this);
                var applicationId = $select.data('application-id');
                var newStatus = $select.val();
                
                self.updateApplicationStatus(applicationId, newStatus);
            });
            
            // View application details
            $(document).on('click', '.ajsb-view-application', function(e) {
                e.preventDefault();
                var applicationId = $(this).data('application-id');
                self.viewApplicationDetails(applicationId);
            });
            
            // Bulk actions
            $(document).on('click', '#ajsb-bulk-action-apply', function(e) {
                e.preventDefault();
                self.applyBulkAction();
            });
        },
        
        // Update application status
        updateApplicationStatus: function(applicationId, status, notes) {
            var $select = $('.ajsb-status-select[data-application-id="' + applicationId + '"]');
            var originalStatus = $select.data('original-status') || $select.val();
            
            // Store original status for rollback
            if (!$select.data('original-status')) {
                $select.data('original-status', originalStatus);
            }
            
            // Show loading state
            $select.prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'ajsb_admin_update_application_status',
                nonce: ajsb_admin_ajax.nonce,
                application_id: applicationId,
                status: status,
                notes: notes || ''
            })
            .done(function(response) {
                if (response.success) {
                    // Update original status
                    $select.data('original-status', status);
                    
                    // Show success message
                    self.showNotification(response.data, 'success');
                    
                    // Update row styling
                    var $row = $select.closest('tr');
                    $row.addClass('ajsb-status-updated');
                    
                    setTimeout(function() {
                        $row.removeClass('ajsb-status-updated');
                    }, 2000);
                } else {
                    // Rollback to original status
                    $select.val(originalStatus);
                    self.showNotification(response.data || 'Failed to update status', 'error');
                }
            })
            .fail(function() {
                // Rollback to original status
                $select.val(originalStatus);
                self.showNotification('Failed to update status', 'error');
            })
            .always(function() {
                $select.prop('disabled', false);
            });
        },
        
        // View application details
        viewApplicationDetails: function(applicationId) {
            // This could open a modal or redirect to a detailed view
            // For now, we'll show a simple alert
            this.showNotification('Application details view - Feature coming soon!', 'info');
        },
        
        // Apply bulk actions
        applyBulkAction: function() {
            var action = $('#ajsb-bulk-action').val();
            var selectedItems = [];
            
            $('.ajsb-bulk-checkbox:checked').each(function() {
                selectedItems.push($(this).val());
            });
            
            if (selectedItems.length === 0) {
                this.showNotification('Please select items to perform bulk action', 'warning');
                return;
            }
            
            if (!action) {
                this.showNotification('Please select an action', 'warning');
                return;
            }
            
            // Confirm bulk action
            if (!confirm('Are you sure you want to perform this action on ' + selectedItems.length + ' items?')) {
                return;
            }
            
            // Perform bulk action
            this.performBulkAction(action, selectedItems);
        },
        
        // Perform bulk action
        performBulkAction: function(action, items) {
            var self = this;
            
            $.post(ajaxurl, {
                action: 'ajsb_admin_bulk_action',
                nonce: ajsb_admin_ajax.nonce,
                bulk_action: action,
                items: items
            })
            .done(function(response) {
                if (response.success) {
                    self.showNotification(response.data, 'success');
                    
                    // Reload page to show changes
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    self.showNotification(response.data || 'Bulk action failed', 'error');
                }
            })
            .fail(function() {
                self.showNotification('Bulk action failed', 'error');
            });
        },
        
        // Initialize job management
        initJobManagement: function() {
            var self = this;
            
            // Delete job
            $(document).on('click', '.ajsb-delete-job', function(e) {
                e.preventDefault();
                
                if (!confirm('Are you sure you want to delete this job? This action cannot be undone.')) {
                    return;
                }
                
                var jobId = $(this).data('job-id');
                self.deleteJob(jobId);
            });
            
            // Job form enhancements
            this.enhanceJobForm();
        },
        
        // Delete job
        deleteJob: function(jobId) {
            var $row = $('[data-job-id="' + jobId + '"]').closest('tr');
            
            $.post(ajaxurl, {
                action: 'ajsb_admin_delete_job',
                nonce: ajsb_admin_ajax.nonce,
                job_id: jobId
            })
            .done(function(response) {
                if (response.success) {
                    // Remove row with animation
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                    
                    this.showNotification(response.data, 'success');
                } else {
                    this.showNotification(response.data || 'Failed to delete job', 'error');
                }
            }.bind(this))
            .fail(function() {
                this.showNotification('Failed to delete job', 'error');
            }.bind(this));
        },
        
        // Enhance job form
        enhanceJobForm: function() {
            // Skills input enhancement
            $('#ajsb_skills').on('input', function() {
                var $this = $(this);
                var value = $this.val();
                
                // Auto-format skills (capitalize first letter, add commas)
                var skills = value.split(',').map(function(skill) {
                    return skill.trim().toLowerCase().replace(/\b\w/g, function(l) {
                        return l.toUpperCase();
                    });
                });
                
                if (skills.join(', ') !== value) {
                    $this.val(skills.join(', '));
                }
            });
            
            // Salary range validation
            $('#ajsb_salary_min, #ajsb_salary_max').on('change', function() {
                var min = parseInt($('#ajsb_salary_min').val()) || 0;
                var max = parseInt($('#ajsb_salary_max').val()) || 0;
                
                if (max > 0 && min > max) {
                    alert('Minimum salary cannot be greater than maximum salary');
                    $(this).focus();
                }
            });
            
            // Expiration date validation
            $('#ajsb_expires_at').on('change', function() {
                var selectedDate = new Date($(this).val());
                var today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (selectedDate < today) {
                    alert('Expiration date cannot be in the past');
                    $(this).val('');
                }
            });
        },
        
        // Initialize analytics
        initAnalytics: function() {
            if ($('#ajsb-analytics-content').length) {
                this.loadAnalytics();
            }
        },
        
        // Load analytics data
        loadAnalytics: function() {
            var $container = $('#ajsb-analytics-content');
            
            $.post(ajaxurl, {
                action: 'ajsb_admin_get_analytics',
                nonce: ajsb_admin_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    this.renderAnalytics(response.data);
                } else {
                    $container.html('<div class="ajsb-error">Failed to load analytics data</div>');
                }
            }.bind(this))
            .fail(function() {
                $container.html('<div class="ajsb-error">Failed to load analytics data</div>');
            });
        },
        
        // Render analytics data
        renderAnalytics: function(data) {
            var $container = $('#ajsb-analytics-content');
            
            var html = '<div class="ajsb-analytics-grid">';
            
            // Overview stats
            html += '<div class="ajsb-analytics-card">';
            html += '<h3>Overview</h3>';
            html += '<div class="ajsb-quick-stats-grid">';
            html += '<div class="ajsb-quick-stat">';
            html += '<span class="ajsb-quick-stat-number">' + data.total_jobs + '</span>';
            html += '<span class="ajsb-quick-stat-label">Active Jobs</span>';
            html += '</div>';
            html += '<div class="ajsb-quick-stat">';
            html += '<span class="ajsb-quick-stat-number">' + data.total_applications + '</span>';
            html += '<span class="ajsb-quick-stat-label">Applications</span>';
            html += '</div>';
            html += '<div class="ajsb-quick-stat">';
            html += '<span class="ajsb-quick-stat-number">' + data.total_views + '</span>';
            html += '<span class="ajsb-quick-stat-label">Views (30 days)</span>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            
            // Applications by status
            if (data.applications_by_status && data.applications_by_status.length > 0) {
                html += '<div class="ajsb-analytics-card">';
                html += '<h3>Applications by Status</h3>';
                html += '<div class="ajsb-status-chart">';
                
                data.applications_by_status.forEach(function(item) {
                    var percentage = (item.count / data.total_applications) * 100;
                    html += '<div class="ajsb-status-item">';
                    html += '<span class="ajsb-status-label">' + item.status + '</span>';
                    html += '<span class="ajsb-status-count">' + item.count + '</span>';
                    html += '<div class="ajsb-status-bar">';
                    html += '<div class="ajsb-status-fill" style="width: ' + percentage + '%"></div>';
                    html += '</div>';
                    html += '</div>';
                });
                
                html += '</div>';
                html += '</div>';
            }
            
            // Top viewed jobs
            if (data.top_jobs && data.top_jobs.length > 0) {
                html += '<div class="ajsb-analytics-card">';
                html += '<h3>Top Viewed Jobs (30 days)</h3>';
                html += '<div class="ajsb-top-jobs">';
                
                data.top_jobs.forEach(function(job, index) {
                    html += '<div class="ajsb-top-job-item">';
                    html += '<span class="ajsb-job-rank">' + (index + 1) + '</span>';
                    html += '<div class="ajsb-job-info">';
                    html += '<div class="ajsb-job-title">' + job.title + '</div>';
                    html += '<div class="ajsb-job-company">' + job.company + '</div>';
                    html += '</div>';
                    html += '<span class="ajsb-job-views">' + job.views + ' views</span>';
                    html += '</div>';
                });
                
                html += '</div>';
                html += '</div>';
            }
            
            html += '</div>';
            
            $container.html(html);
        },
        
        // Initialize settings
        initSettings: function() {
            // AI provider change
            $(document).on('change', 'select[name="ajsb_settings[ai_provider]"]', function() {
                var provider = $(this).val();
                var $apiKeyField = $('input[name="ajsb_settings[ai_api_key]"]').closest('tr');
                
                if (provider === 'none') {
                    $apiKeyField.hide();
                } else {
                    $apiKeyField.show();
                }
            });
            
            // Test AI connection
            $(document).on('click', '#ajsb-test-ai-connection', function(e) {
                e.preventDefault();
                this.testAIConnection();
            }.bind(this));
        },
        
        // Test AI connection
        testAIConnection: function() {
            var $btn = $('#ajsb-test-ai-connection');
            var originalText = $btn.text();
            
            $btn.prop('disabled', true).text('Testing...');
            
            $.post(ajaxurl, {
                action: 'ajsb_admin_test_ai_connection',
                nonce: ajsb_admin_ajax.nonce,
                api_key: $('input[name="ajsb_settings[ai_api_key]"]').val(),
                provider: $('select[name="ajsb_settings[ai_provider]"]').val()
            })
            .done(function(response) {
                if (response.success) {
                    this.showNotification('AI connection successful!', 'success');
                } else {
                    this.showNotification(response.data || 'AI connection failed', 'error');
                }
            }.bind(this))
            .fail(function() {
                this.showNotification('AI connection test failed', 'error');
            }.bind(this))
            .always(function() {
                $btn.prop('disabled', false).text(originalText);
            });
        },
        
        // Initialize filters
        initFilters: function() {
            var self = this;
            
            // Filter form submission
            $(document).on('submit', '.ajsb-filters form', function(e) {
                e.preventDefault();
                self.applyFilters();
            });
            
            // Filter reset
            $(document).on('click', '.ajsb-filter-reset', function(e) {
                e.preventDefault();
                self.resetFilters();
            });
            
            // Auto-apply filters on change
            $(document).on('change', '.ajsb-filters select, .ajsb-filters input', function() {
                clearTimeout(self.filterTimeout);
                self.filterTimeout = setTimeout(function() {
                    self.applyFilters();
                }, 500);
            });
        },
        
        // Apply filters
        applyFilters: function() {
            var $form = $('.ajsb-filters form');
            var formData = $form.serialize();
            
            // Add current page parameters
            var currentUrl = new URL(window.location);
            formData += '&page=' + currentUrl.searchParams.get('page');
            
            // Update URL and reload
            var newUrl = currentUrl.pathname + '?' + formData;
            window.location.href = newUrl;
        },
        
        // Reset filters
        resetFilters: function() {
            var $form = $('.ajsb-filters form');
            $form[0].reset();
            this.applyFilters();
        },
        
        // Show notification
        showNotification: function(message, type) {
            // Remove existing notifications
            $('.ajsb-admin-notification').remove();
            
            var $notification = $('<div class="ajsb-admin-notification ajsb-admin-notification-' + type + '">' + message + '</div>');
            
            // Add to page
            $('.wrap').prepend($notification);
            
            // Show with animation
            $notification.slideDown(300);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $notification.slideUp(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Click to dismiss
            $notification.on('click', function() {
                $(this).slideUp(300, function() {
                    $(this).remove();
                });
            });
        },
        
        // Utility functions
        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },
        
        formatDate: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString();
        },
        
        formatCurrency: function(amount, currency) {
            currency = currency || 'USD';
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            }).format(amount);
        }
    };
    
    // Add CSS for admin notifications and enhancements
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .ajsb-admin-notification {
                padding: 12px 15px;
                margin: 15px 0;
                border-left: 4px solid;
                border-radius: 3px;
                cursor: pointer;
                display: none;
            }
            .ajsb-admin-notification-success {
                background: #d4edda;
                border-color: #28a745;
                color: #155724;
            }
            .ajsb-admin-notification-error {
                background: #f8d7da;
                border-color: #dc3545;
                color: #721c24;
            }
            .ajsb-admin-notification-warning {
                background: #fff3cd;
                border-color: #ffc107;
                color: #856404;
            }
            .ajsb-admin-notification-info {
                background: #d1ecf1;
                border-color: #17a2b8;
                color: #0c5460;
            }
            .ajsb-status-updated {
                background: #d4edda !important;
                transition: background 0.3s ease;
            }
            .ajsb-status-chart {
                margin-top: 15px;
            }
            .ajsb-status-item {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
                gap: 10px;
            }
            .ajsb-status-label {
                min-width: 100px;
                font-weight: 600;
                text-transform: capitalize;
            }
            .ajsb-status-count {
                min-width: 40px;
                text-align: right;
                font-weight: 600;
                color: #0073aa;
            }
            .ajsb-status-bar {
                flex: 1;
                height: 20px;
                background: #f0f0f0;
                border-radius: 10px;
                overflow: hidden;
            }
            .ajsb-status-fill {
                height: 100%;
                background: #0073aa;
                transition: width 0.8s ease;
            }
            .ajsb-top-jobs {
                margin-top: 15px;
            }
            .ajsb-top-job-item {
                display: flex;
                align-items: center;
                padding: 10px 0;
                border-bottom: 1px solid #eee;
                gap: 15px;
            }
            .ajsb-top-job-item:last-child {
                border-bottom: none;
            }
            .ajsb-job-rank {
                width: 30px;
                height: 30px;
                background: #0073aa;
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                font-size: 14px;
            }
            .ajsb-job-info {
                flex: 1;
            }
            .ajsb-job-title {
                font-weight: 600;
                color: #333;
            }
            .ajsb-job-company {
                font-size: 12px;
                color: #666;
                margin-top: 2px;
            }
            .ajsb-job-views {
                font-weight: 600;
                color: #0073aa;
            }
        `)
        .appendTo('head');
    
})(jQuery);