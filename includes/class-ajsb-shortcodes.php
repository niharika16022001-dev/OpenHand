<?php
/**
 * Shortcodes class
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJSB_Shortcodes {
    
    public function __construct() {
        add_shortcode('ajsb_job_listings', array($this, 'job_listings_shortcode'));
        add_shortcode('ajsb_job_search', array($this, 'job_search_shortcode'));
        add_shortcode('ajsb_user_profile', array($this, 'user_profile_shortcode'));
        add_shortcode('ajsb_user_applications', array($this, 'user_applications_shortcode'));
        add_shortcode('ajsb_ai_recommendations', array($this, 'ai_recommendations_shortcode'));
        add_shortcode('ajsb_job_stats', array($this, 'job_stats_shortcode'));
    }
    
    /**
     * Job listings shortcode
     */
    public function job_listings_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'job_type' => '',
            'location' => '',
            'featured' => '',
            'show_search' => 'true',
            'show_pagination' => 'true'
        ), $atts);
        
        $args = array(
            'limit' => (int)$atts['limit'],
            'show_search' => $atts['show_search'] === 'true',
            'show_pagination' => $atts['show_pagination'] === 'true'
        );
        
        // Add filters if specified
        $job_args = array(
            'status' => 'active',
            'limit' => (int)$atts['limit']
        );
        
        if (!empty($atts['job_type'])) {
            $job_args['job_type'] = $atts['job_type'];
        }
        
        if (!empty($atts['location'])) {
            $job_args['location'] = $atts['location'];
        }
        
        if ($atts['featured'] !== '') {
            $job_args['featured'] = (int)$atts['featured'];
        }
        
        return AJSB_Frontend::get_job_listings($args);
    }
    
    /**
     * Job search shortcode
     */
    public function job_search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_filters' => 'true'
        ), $atts);
        
        return AJSB_Frontend::get_job_search_form();
    }
    
    /**
     * User profile shortcode
     */
    public function user_profile_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="ajsb-login-required">' . 
                   __('Please log in to view your profile.', 'ai-job-search-board') . 
                   '</div>';
        }
        
        $user_id = get_current_user_id();
        $profile = AJSB_User_Profile::get_by_user_id($user_id);
        $user = wp_get_current_user();
        
        ob_start();
        ?>
        <div class="ajsb-user-profile">
            <h2><?php _e('My Profile', 'ai-job-search-board'); ?></h2>
            
            <form id="ajsb-profile-form" class="ajsb-form">
                <div class="ajsb-form-section">
                    <h3><?php _e('Personal Information', 'ai-job-search-board'); ?></h3>
                    
                    <div class="ajsb-form-row">
                        <div class="ajsb-form-group">
                            <label for="ajsb-first-name"><?php _e('First Name', 'ai-job-search-board'); ?></label>
                            <input type="text" id="ajsb-first-name" name="first_name" value="<?php echo esc_attr($profile->first_name ?? ''); ?>" />
                        </div>
                        
                        <div class="ajsb-form-group">
                            <label for="ajsb-last-name"><?php _e('Last Name', 'ai-job-search-board'); ?></label>
                            <input type="text" id="ajsb-last-name" name="last_name" value="<?php echo esc_attr($profile->last_name ?? ''); ?>" />
                        </div>
                    </div>
                    
                    <div class="ajsb-form-row">
                        <div class="ajsb-form-group">
                            <label for="ajsb-email"><?php _e('Email', 'ai-job-search-board'); ?></label>
                            <input type="email" id="ajsb-email" value="<?php echo esc_attr($user->user_email); ?>" disabled />
                            <small class="ajsb-help-text"><?php _e('Email cannot be changed here', 'ai-job-search-board'); ?></small>
                        </div>
                        
                        <div class="ajsb-form-group">
                            <label for="ajsb-phone"><?php _e('Phone', 'ai-job-search-board'); ?></label>
                            <input type="tel" id="ajsb-phone" name="phone" value="<?php echo esc_attr($profile->phone ?? ''); ?>" />
                        </div>
                    </div>
                    
                    <div class="ajsb-form-group">
                        <label for="ajsb-location"><?php _e('Location', 'ai-job-search-board'); ?></label>
                        <input type="text" id="ajsb-location" name="location" value="<?php echo esc_attr($profile->location ?? ''); ?>" placeholder="<?php esc_attr_e('City, State/Country', 'ai-job-search-board'); ?>" />
                    </div>
                    
                    <div class="ajsb-form-group">
                        <label for="ajsb-bio"><?php _e('Bio', 'ai-job-search-board'); ?></label>
                        <textarea id="ajsb-bio" name="bio" rows="4" placeholder="<?php esc_attr_e('Tell us about yourself...', 'ai-job-search-board'); ?>"><?php echo esc_textarea($profile->bio ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="ajsb-form-section">
                    <h3><?php _e('Professional Information', 'ai-job-search-board'); ?></h3>
                    
                    <div class="ajsb-form-group">
                        <label for="ajsb-skills"><?php _e('Skills', 'ai-job-search-board'); ?></label>
                        <textarea id="ajsb-skills" name="skills" rows="3" placeholder="<?php esc_attr_e('PHP, JavaScript, React, etc. (comma-separated)', 'ai-job-search-board'); ?>"><?php echo esc_textarea($profile->skills ?? ''); ?></textarea>
                    </div>
                    
                    <div class="ajsb-form-row">
                        <div class="ajsb-form-group">
                            <label for="ajsb-experience-level"><?php _e('Experience Level', 'ai-job-search-board'); ?></label>
                            <select id="ajsb-experience-level" name="experience_level">
                                <option value=""><?php _e('Select Experience Level', 'ai-job-search-board'); ?></option>
                                <?php foreach (AJSB_User_Profile::get_experience_levels() as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($profile->experience_level ?? '', $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="ajsb-form-group">
                            <label for="ajsb-desired-job-type"><?php _e('Desired Job Type', 'ai-job-search-board'); ?></label>
                            <select id="ajsb-desired-job-type" name="desired_job_type">
                                <option value=""><?php _e('Select Job Type', 'ai-job-search-board'); ?></option>
                                <?php foreach (AJSB_User_Profile::get_job_types() as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($profile->desired_job_type ?? '', $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="ajsb-form-group">
                        <label for="ajsb-experience"><?php _e('Work Experience', 'ai-job-search-board'); ?></label>
                        <textarea id="ajsb-experience" name="experience" rows="5" placeholder="<?php esc_attr_e('Describe your work experience...', 'ai-job-search-board'); ?>"><?php echo esc_textarea($profile->experience ?? ''); ?></textarea>
                    </div>
                    
                    <div class="ajsb-form-group">
                        <label for="ajsb-education"><?php _e('Education', 'ai-job-search-board'); ?></label>
                        <textarea id="ajsb-education" name="education" rows="3" placeholder="<?php esc_attr_e('Your educational background...', 'ai-job-search-board'); ?>"><?php echo esc_textarea($profile->education ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="ajsb-form-section">
                    <h3><?php _e('Job Preferences', 'ai-job-search-board'); ?></h3>
                    
                    <div class="ajsb-form-row">
                        <div class="ajsb-form-group">
                            <label for="ajsb-salary-min"><?php _e('Desired Salary Range', 'ai-job-search-board'); ?></label>
                            <div class="ajsb-salary-range">
                                <input type="number" id="ajsb-salary-min" name="desired_salary_min" value="<?php echo esc_attr($profile->desired_salary_min ?? ''); ?>" placeholder="<?php esc_attr_e('Min', 'ai-job-search-board'); ?>" />
                                <span>-</span>
                                <input type="number" id="ajsb-salary-max" name="desired_salary_max" value="<?php echo esc_attr($profile->desired_salary_max ?? ''); ?>" placeholder="<?php esc_attr_e('Max', 'ai-job-search-board'); ?>" />
                            </div>
                        </div>
                        
                        <div class="ajsb-form-group">
                            <label for="ajsb-availability"><?php _e('Availability', 'ai-job-search-board'); ?></label>
                            <select id="ajsb-availability" name="availability">
                                <?php foreach (AJSB_User_Profile::get_availability_options() as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($profile->availability ?? 'immediately', $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="ajsb-form-group">
                        <label class="ajsb-checkbox-label">
                            <input type="checkbox" name="remote_preference" value="1" <?php checked($profile->remote_preference ?? 0, 1); ?> />
                            <?php _e('Open to remote work', 'ai-job-search-board'); ?>
                        </label>
                    </div>
                </div>
                
                <div class="ajsb-form-section">
                    <h3><?php _e('Links', 'ai-job-search-board'); ?></h3>
                    
                    <div class="ajsb-form-row">
                        <div class="ajsb-form-group">
                            <label for="ajsb-resume-url"><?php _e('Resume URL', 'ai-job-search-board'); ?></label>
                            <input type="url" id="ajsb-resume-url" name="resume_url" value="<?php echo esc_attr($profile->resume_url ?? ''); ?>" placeholder="<?php esc_attr_e('https://example.com/resume.pdf', 'ai-job-search-board'); ?>" />
                        </div>
                        
                        <div class="ajsb-form-group">
                            <label for="ajsb-portfolio-url"><?php _e('Portfolio URL', 'ai-job-search-board'); ?></label>
                            <input type="url" id="ajsb-portfolio-url" name="portfolio_url" value="<?php echo esc_attr($profile->portfolio_url ?? ''); ?>" placeholder="<?php esc_attr_e('https://yourportfolio.com', 'ai-job-search-board'); ?>" />
                        </div>
                    </div>
                    
                    <div class="ajsb-form-row">
                        <div class="ajsb-form-group">
                            <label for="ajsb-linkedin-url"><?php _e('LinkedIn URL', 'ai-job-search-board'); ?></label>
                            <input type="url" id="ajsb-linkedin-url" name="linkedin_url" value="<?php echo esc_attr($profile->linkedin_url ?? ''); ?>" placeholder="<?php esc_attr_e('https://linkedin.com/in/yourprofile', 'ai-job-search-board'); ?>" />
                        </div>
                        
                        <div class="ajsb-form-group">
                            <label for="ajsb-github-url"><?php _e('GitHub URL', 'ai-job-search-board'); ?></label>
                            <input type="url" id="ajsb-github-url" name="github_url" value="<?php echo esc_attr($profile->github_url ?? ''); ?>" placeholder="<?php esc_attr_e('https://github.com/yourusername', 'ai-job-search-board'); ?>" />
                        </div>
                    </div>
                </div>
                
                <div class="ajsb-form-actions">
                    <button type="submit" class="ajsb-btn ajsb-btn-primary">
                        <?php _e('Save Profile', 'ai-job-search-board'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * User applications shortcode
     */
    public function user_applications_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="ajsb-login-required">' . 
                   __('Please log in to view your applications.', 'ai-job-search-board') . 
                   '</div>';
        }
        
        $user_id = get_current_user_id();
        $applications = AJSB_Application::get_applications(array(
            'user_id' => $user_id,
            'limit' => 50
        ));
        
        ob_start();
        ?>
        <div class="ajsb-user-applications">
            <h2><?php _e('My Applications', 'ai-job-search-board'); ?></h2>
            
            <?php if (empty($applications)): ?>
                <div class="ajsb-no-applications">
                    <p><?php _e('You haven\'t applied for any jobs yet.', 'ai-job-search-board'); ?></p>
                    <a href="<?php echo get_permalink(get_page_by_path('jobs')); ?>" class="ajsb-btn ajsb-btn-primary">
                        <?php _e('Browse Jobs', 'ai-job-search-board'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="ajsb-applications-list">
                    <?php foreach ($applications as $app): ?>
                        <div class="ajsb-application-card">
                            <div class="ajsb-application-header">
                                <h3 class="ajsb-job-title">
                                    <a href="<?php echo get_permalink($app->job_id); ?>">
                                        <?php echo esc_html($app->job_title); ?>
                                    </a>
                                </h3>
                                <div class="ajsb-company"><?php echo esc_html($app->company); ?></div>
                            </div>
                            
                            <div class="ajsb-application-meta">
                                <div class="ajsb-location"><?php echo esc_html($app->location); ?></div>
                                <div class="ajsb-applied-date">
                                    <?php echo sprintf(__('Applied %s ago', 'ai-job-search-board'), human_time_diff(strtotime($app->applied_at))); ?>
                                </div>
                            </div>
                            
                            <div class="ajsb-application-status">
                                <span class="ajsb-status ajsb-status-<?php echo esc_attr($app->status); ?>">
                                    <?php
                                    $statuses = AJSB_Application::get_statuses();
                                    echo esc_html($statuses[$app->status] ?? $app->status);
                                    ?>
                                </span>
                                
                                <?php if ($app->ai_match_score): ?>
                                    <div class="ajsb-match-score">
                                        <span class="ajsb-score"><?php echo round($app->ai_match_score); ?>%</span>
                                        <span class="ajsb-score-label"><?php _e('Match', 'ai-job-search-board'); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($app->cover_letter)): ?>
                                <div class="ajsb-cover-letter">
                                    <strong><?php _e('Cover Letter:', 'ai-job-search-board'); ?></strong>
                                    <p><?php echo wp_trim_words(esc_html($app->cover_letter), 30); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AI recommendations shortcode
     */
    public function ai_recommendations_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="ajsb-login-required">' . 
                   __('Please log in to view AI recommendations.', 'ai-job-search-board') . 
                   '</div>';
        }
        
        $atts = shortcode_atts(array(
            'limit' => 5
        ), $atts);
        
        $user_id = get_current_user_id();
        $ai_engine = new AJSB_AI_Engine();
        $recommendations = $ai_engine->get_user_recommendations($user_id, (int)$atts['limit']);
        
        ob_start();
        ?>
        <div class="ajsb-ai-recommendations">
            <h2><?php _e('AI Job Recommendations', 'ai-job-search-board'); ?></h2>
            
            <?php if (empty($recommendations)): ?>
                <div class="ajsb-no-recommendations">
                    <p><?php _e('No recommendations available. Please complete your profile to get personalized job recommendations.', 'ai-job-search-board'); ?></p>
                    <a href="<?php echo get_permalink(get_page_by_path('my-profile')); ?>" class="ajsb-btn ajsb-btn-primary">
                        <?php _e('Complete Profile', 'ai-job-search-board'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="ajsb-recommendations-list">
                    <?php foreach ($recommendations as $rec): ?>
                        <?php
                        $reasons = json_decode($rec->reasons, true);
                        if (!is_array($reasons)) {
                            $reasons = array();
                        }
                        ?>
                        <div class="ajsb-recommendation-card">
                            <div class="ajsb-match-score">
                                <div class="ajsb-score-circle" data-score="<?php echo esc_attr($rec->match_score); ?>">
                                    <span class="ajsb-score-text"><?php echo round($rec->match_score); ?>%</span>
                                </div>
                                <div class="ajsb-match-label"><?php _e('Match', 'ai-job-search-board'); ?></div>
                            </div>
                            
                            <div class="ajsb-recommendation-content">
                                <h3 class="ajsb-job-title">
                                    <a href="<?php echo get_permalink($rec->job_id); ?>">
                                        <?php echo esc_html($rec->title); ?>
                                    </a>
                                </h3>
                                
                                <div class="ajsb-job-meta">
                                    <span class="ajsb-company"><?php echo esc_html($rec->company); ?></span>
                                    <span class="ajsb-location"><?php echo esc_html($rec->location); ?></span>
                                </div>
                                
                                <?php if (!empty($reasons)): ?>
                                    <div class="ajsb-match-reasons">
                                        <strong><?php _e('Why this matches:', 'ai-job-search-board'); ?></strong>
                                        <ul>
                                            <?php foreach (array_slice($reasons, 0, 3) as $reason): ?>
                                                <li><?php echo esc_html($reason); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="ajsb-recommendation-actions">
                                    <button class="ajsb-btn ajsb-btn-primary ajsb-apply-btn" data-job-id="<?php echo esc_attr($rec->job_id); ?>">
                                        <?php _e('Apply Now', 'ai-job-search-board'); ?>
                                    </button>
                                    <a href="<?php echo get_permalink($rec->job_id); ?>" class="ajsb-btn ajsb-btn-secondary">
                                        <?php _e('View Details', 'ai-job-search-board'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="ajsb-recommendations-footer">
                    <button id="ajsb-refresh-recommendations" class="ajsb-btn ajsb-btn-secondary">
                        <?php _e('Refresh Recommendations', 'ai-job-search-board'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Job statistics shortcode
     */
    public function job_stats_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show' => 'total,active,applications,candidates'
        ), $atts);
        
        $show_stats = explode(',', $atts['show']);
        $show_stats = array_map('trim', $show_stats);
        
        global $wpdb;
        
        $jobs_table = AJSB_Database::get_table_name('jobs');
        $applications_table = AJSB_Database::get_table_name('applications');
        $profiles_table = AJSB_Database::get_table_name('user_profiles');
        
        $stats = array();
        
        if (in_array('total', $show_stats)) {
            $stats['total'] = array(
                'label' => __('Total Jobs', 'ai-job-search-board'),
                'value' => $wpdb->get_var("SELECT COUNT(*) FROM $jobs_table")
            );
        }
        
        if (in_array('active', $show_stats)) {
            $stats['active'] = array(
                'label' => __('Active Jobs', 'ai-job-search-board'),
                'value' => $wpdb->get_var("SELECT COUNT(*) FROM $jobs_table WHERE status = 'active'")
            );
        }
        
        if (in_array('applications', $show_stats)) {
            $stats['applications'] = array(
                'label' => __('Total Applications', 'ai-job-search-board'),
                'value' => $wpdb->get_var("SELECT COUNT(*) FROM $applications_table")
            );
        }
        
        if (in_array('candidates', $show_stats)) {
            $stats['candidates'] = array(
                'label' => __('Registered Candidates', 'ai-job-search-board'),
                'value' => $wpdb->get_var("SELECT COUNT(*) FROM $profiles_table")
            );
        }
        
        ob_start();
        ?>
        <div class="ajsb-job-stats">
            <?php foreach ($stats as $key => $stat): ?>
                <div class="ajsb-stat-item ajsb-stat-<?php echo esc_attr($key); ?>">
                    <div class="ajsb-stat-value"><?php echo number_format($stat['value']); ?></div>
                    <div class="ajsb-stat-label"><?php echo esc_html($stat['label']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}