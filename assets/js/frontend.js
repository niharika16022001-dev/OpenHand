/**
 * AI Job Search Board - Frontend JavaScript
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        AJSB_Frontend.init();
    });
    
    // Main frontend object
    window.AJSB_Frontend = {
        
        // Initialize all frontend functionality
        init: function() {
            this.initJobSearch();
            this.initJobApplication();
            this.initUserProfile();
            this.initRecommendations();
            this.initJobViews();
            this.initScoreCircles();
        },
        
        // Initialize job search functionality
        initJobSearch: function() {
            var self = this;
            
            // Job search form submission
            $(document).on('submit', '#ajsb-job-search-form', function(e) {
                e.preventDefault();
                self.performJobSearch(1);
            });
            
            // Filter changes
            $(document).on('change', '#ajsb-job-search-form select, #ajsb-job-search-form input[type="checkbox"]', function() {
                self.performJobSearch(1);
            });
            
            // Pagination
            $(document).on('click', '.ajsb-pagination a', function(e) {
                e.preventDefault();
                var page = $(this).data('page');
                if (page) {
                    self.performJobSearch(page);
                }
            });
        },
        
        // Perform job search via AJAX
        performJobSearch: function(page) {
            var $form = $('#ajsb-job-search-form');
            var $container = $('#ajsb-jobs-list');
            var $loading = $('.ajsb-loading');
            
            // Show loading state
            $loading.show();
            $container.hide();
            
            // Collect form data
            var formData = {
                action: 'ajsb_search_jobs',
                nonce: ajsb_ajax.nonce,
                search: $form.find('[name="search"]').val(),
                location: $form.find('[name="location"]').val(),
                job_type: $form.find('[name="job_type"]').val(),
                experience_level: $form.find('[name="experience_level"]').val(),
                remote_work: $form.find('[name="remote_work"]').is(':checked') ? 1 : '',
                page: page || 1
            };
            
            // Perform AJAX request
            $.post(ajsb_ajax.ajax_url, formData)
                .done(function(response) {
                    if (response.success) {
                        $container.html(response.data.html);
                        
                        // Update pagination
                        var pagination = self.buildPagination(response.data);
                        $('#ajsb-pagination').html(pagination);
                        
                        // Initialize score circles for new content
                        self.initScoreCircles();
                        
                        // Scroll to results
                        $('html, body').animate({
                            scrollTop: $('#ajsb-jobs-container').offset().top - 100
                        }, 500);
                    } else {
                        self.showError(ajsb_ajax.strings.error);
                    }
                })
                .fail(function() {
                    self.showError(ajsb_ajax.strings.error);
                })
                .always(function() {
                    $loading.hide();
                    $container.show();
                });
        },
        
        // Build pagination HTML
        buildPagination: function(data) {
            if (data.total_pages <= 1) {
                return '';
            }
            
            var html = '<div class="ajsb-pagination-wrapper">';
            
            // Previous button
            if (data.page > 1) {
                html += '<a href="#" class="ajsb-pagination-btn" data-page="' + (data.page - 1) + '">&laquo; Previous</a>';
            }
            
            // Page numbers
            var start = Math.max(1, data.page - 2);
            var end = Math.min(data.total_pages, data.page + 2);
            
            if (start > 1) {
                html += '<a href="#" class="ajsb-pagination-btn" data-page="1">1</a>';
                if (start > 2) {
                    html += '<span class="ajsb-pagination-dots">...</span>';
                }
            }
            
            for (var i = start; i <= end; i++) {
                if (i === data.page) {
                    html += '<span class="ajsb-pagination-current">' + i + '</span>';
                } else {
                    html += '<a href="#" class="ajsb-pagination-btn" data-page="' + i + '">' + i + '</a>';
                }
            }
            
            if (end < data.total_pages) {
                if (end < data.total_pages - 1) {
                    html += '<span class="ajsb-pagination-dots">...</span>';
                }
                html += '<a href="#" class="ajsb-pagination-btn" data-page="' + data.total_pages + '">' + data.total_pages + '</a>';
            }
            
            // Next button
            if (data.page < data.total_pages) {
                html += '<a href="#" class="ajsb-pagination-btn" data-page="' + (data.page + 1) + '">Next &raquo;</a>';
            }
            
            html += '</div>';
            
            return html;
        },
        
        // Initialize job application functionality
        initJobApplication: function() {
            var self = this;
            
            // Apply button click
            $(document).on('click', '.ajsb-apply-btn', function(e) {
                e.preventDefault();
                
                var jobId = $(this).data('job-id');
                if (jobId) {
                    self.showApplicationModal(jobId);
                }
            });
            
            // Modal close
            $(document).on('click', '.ajsb-modal-close, .ajsb-modal-overlay', function(e) {
                e.preventDefault();
                self.hideApplicationModal();
            });
            
            // Application form submission
            $(document).on('submit', '#ajsb-application-form', function(e) {
                e.preventDefault();
                self.submitApplication();
            });
            
            // Job view tracking
            $(document).on('click', '.ajsb-job-link', function() {
                var jobId = $(this).data('job-id');
                if (jobId) {
                    self.recordJobView(jobId);
                }
            });
        },
        
        // Show application modal
        showApplicationModal: function(jobId) {
            $('#ajsb-job-id').val(jobId);
            $('#ajsb-application-modal, #ajsb-modal-overlay').fadeIn(300);
            $('body').addClass('ajsb-modal-open');
            
            // Focus on first input
            setTimeout(function() {
                $('#ajsb-cover-letter').focus();
            }, 350);
        },
        
        // Hide application modal
        hideApplicationModal: function() {
            $('#ajsb-application-modal, #ajsb-modal-overlay').fadeOut(300);
            $('body').removeClass('ajsb-modal-open');
            
            // Reset form
            $('#ajsb-application-form')[0].reset();
        },
        
        // Submit job application
        submitApplication: function() {
            var $form = $('#ajsb-application-form');
            var $submitBtn = $form.find('[type="submit"]');
            var originalText = $submitBtn.text();
            
            // Disable submit button
            $submitBtn.prop('disabled', true).text(ajsb_ajax.strings.loading);
            
            // Collect form data
            var formData = {
                action: 'ajsb_apply_job',
                nonce: ajsb_ajax.nonce,
                job_id: $('#ajsb-job-id').val(),
                cover_letter: $('#ajsb-cover-letter').val(),
                resume_url: $('#ajsb-resume-url').val()
            };
            
            // Submit application
            $.post(ajsb_ajax.ajax_url, formData)
                .done(function(response) {
                    if (response.success) {
                        // Show success message
                        this.showSuccess(response.data);
                        
                        // Hide modal
                        this.hideApplicationModal();
                        
                        // Update apply buttons
                        $('[data-job-id="' + formData.job_id + '"] .ajsb-apply-btn')
                            .removeClass('ajsb-btn-primary')
                            .addClass('ajsb-btn-applied')
                            .prop('disabled', true)
                            .text('Applied');
                        
                        // Reload page if on job detail page
                        if ($('body').hasClass('single-ajsb_job')) {
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        }
                    } else {
                        this.showError(response.data);
                    }
                }.bind(this))
                .fail(function() {
                    this.showError(ajsb_ajax.strings.error);
                }.bind(this))
                .always(function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                });
        },
        
        // Record job view
        recordJobView: function(jobId) {
            $.post(ajsb_ajax.ajax_url, {
                action: 'ajsb_record_job_view',
                nonce: ajsb_ajax.nonce,
                job_id: jobId
            });
        },
        
        // Initialize user profile functionality
        initUserProfile: function() {
            var self = this;
            
            // Profile form submission
            $(document).on('submit', '#ajsb-profile-form', function(e) {
                e.preventDefault();
                self.saveProfile();
            });
            
            // Skills input enhancement
            $(document).on('input', '#ajsb-skills', function() {
                self.enhanceSkillsInput($(this));
            });
        },
        
        // Save user profile
        saveProfile: function() {
            var $form = $('#ajsb-profile-form');
            var $submitBtn = $form.find('[type="submit"]');
            var originalText = $submitBtn.text();
            
            // Disable submit button
            $submitBtn.prop('disabled', true).text(ajsb_ajax.strings.loading);
            
            // Collect form data
            var formData = {
                action: 'ajsb_save_profile',
                nonce: ajsb_ajax.nonce
            };
            
            // Add all form fields
            $form.find('input, select, textarea').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                
                if (name) {
                    if ($field.attr('type') === 'checkbox') {
                        formData[name] = $field.is(':checked') ? 1 : 0;
                    } else {
                        formData[name] = $field.val();
                    }
                }
            });
            
            // Submit profile
            $.post(ajsb_ajax.ajax_url, formData)
                .done(function(response) {
                    if (response.success) {
                        this.showSuccess(response.data);
                        
                        // Scroll to top
                        $('html, body').animate({
                            scrollTop: 0
                        }, 500);
                    } else {
                        this.showError(response.data);
                    }
                }.bind(this))
                .fail(function() {
                    this.showError(ajsb_ajax.strings.error);
                }.bind(this))
                .always(function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                });
        },
        
        // Enhance skills input with suggestions
        enhanceSkillsInput: function($input) {
            // This could be enhanced with autocomplete functionality
            var value = $input.val();
            var skills = value.split(',').map(function(skill) {
                return skill.trim();
            });
            
            // Basic validation - ensure skills are properly formatted
            var cleanedSkills = skills.filter(function(skill) {
                return skill.length > 0;
            });
            
            if (cleanedSkills.length !== skills.length) {
                $input.val(cleanedSkills.join(', '));
            }
        },
        
        // Initialize AI recommendations
        initRecommendations: function() {
            var self = this;
            
            // Refresh recommendations
            $(document).on('click', '#ajsb-refresh-recommendations', function(e) {
                e.preventDefault();
                self.refreshRecommendations();
            });
            
            // Load recommendations on page load
            if ($('#ajsb-recommendations-list').length) {
                self.loadRecommendations();
            }
        },
        
        // Load AI recommendations
        loadRecommendations: function() {
            var $container = $('#ajsb-recommendations-list');
            var $loading = $container.find('.ajsb-loading');
            
            if ($loading.length === 0) {
                $container.html('<div class="ajsb-loading">' + ajsb_ajax.strings.loading + '</div>');
            }
            
            $.post(ajsb_ajax.ajax_url, {
                action: 'ajsb_get_recommendations',
                nonce: ajsb_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    $container.html(response.data.html);
                    this.initScoreCircles();
                } else {
                    $container.html('<div class="ajsb-error">' + (response.data || ajsb_ajax.strings.error) + '</div>');
                }
            }.bind(this))
            .fail(function() {
                $container.html('<div class="ajsb-error">' + ajsb_ajax.strings.error + '</div>');
            });
        },
        
        // Refresh recommendations
        refreshRecommendations: function() {
            var $btn = $('#ajsb-refresh-recommendations');
            var originalText = $btn.text();
            
            $btn.prop('disabled', true).text(ajsb_ajax.strings.loading);
            
            // Force regeneration by calling the load function
            this.loadRecommendations();
            
            setTimeout(function() {
                $btn.prop('disabled', false).text(originalText);
            }, 2000);
        },
        
        // Initialize job view tracking
        initJobViews: function() {
            // Track views on single job pages
            if ($('body').hasClass('single-ajsb_job')) {
                var jobId = $('body').data('job-id') || $('.ajsb-job-details').data('job-id');
                if (jobId) {
                    this.recordJobView(jobId);
                }
            }
        },
        
        // Initialize score circles
        initScoreCircles: function() {
            $('.ajsb-score-circle').each(function() {
                var $circle = $(this);
                var score = parseInt($circle.data('score')) || 0;
                
                // Set CSS custom property for the conic gradient
                $circle.css('--score', score);
                
                // Animate the circle
                $circle.addClass('ajsb-score-animated');
            });
        },
        
        // Show success message
        showSuccess: function(message) {
            this.showNotification(message, 'success');
        },
        
        // Show error message
        showError: function(message) {
            this.showNotification(message, 'error');
        },
        
        // Show notification
        showNotification: function(message, type) {
            var $notification = $('<div class="ajsb-notification ajsb-notification-' + type + '">' + message + '</div>');
            
            // Remove existing notifications
            $('.ajsb-notification').remove();
            
            // Add to page
            $('body').prepend($notification);
            
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
        
        // Utility function to format numbers
        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },
        
        // Utility function to truncate text
        truncateText: function(text, length) {
            if (text.length <= length) {
                return text;
            }
            return text.substr(0, length) + '...';
        },
        
        // Debounce function for search
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    };
    
    // Add CSS for notifications
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .ajsb-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 5px;
                color: white;
                font-weight: 600;
                z-index: 10000;
                cursor: pointer;
                max-width: 400px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                display: none;
            }
            .ajsb-notification-success {
                background: #28a745;
            }
            .ajsb-notification-error {
                background: #dc3545;
            }
            .ajsb-notification-warning {
                background: #ffc107;
                color: #212529;
            }
            .ajsb-notification-info {
                background: #17a2b8;
            }
            body.ajsb-modal-open {
                overflow: hidden;
            }
            .ajsb-score-animated {
                transition: all 0.8s ease-in-out;
            }
            .ajsb-pagination-wrapper {
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 5px;
                margin: 20px 0;
            }
            .ajsb-pagination-btn {
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                text-decoration: none;
                color: #333;
                transition: all 0.3s ease;
            }
            .ajsb-pagination-btn:hover {
                background: #f5f5f5;
                color: #333;
            }
            .ajsb-pagination-current {
                padding: 8px 12px;
                background: #007cba;
                color: white;
                border-radius: 4px;
                font-weight: 600;
            }
            .ajsb-pagination-dots {
                padding: 8px 4px;
                color: #666;
            }
        `)
        .appendTo('head');
    
})(jQuery);