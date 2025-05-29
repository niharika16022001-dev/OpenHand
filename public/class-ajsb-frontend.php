<?php
/**
 * Frontend functionality class
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJSB_Frontend {
    
    public function __construct() {
        add_action('wp_head', array($this, 'add_meta_tags'));
        add_action('template_redirect', array($this, 'handle_job_view'));
        add_filter('the_content', array($this, 'filter_job_content'));
        add_action('wp_footer', array($this, 'add_job_application_modal'));
    }
    
    /**
     * Add meta tags for job pages
     */
    public function add_meta_tags() {
        if (is_singular('ajsb_job')) {
            global $post;
            
            $job = AJSB_Job::get($post->ID);
            if ($job) {
                echo '<meta property="og:title" content="' . esc_attr($job->title . ' at ' . $job->company) . '" />' . "\n";
                echo '<meta property="og:description" content="' . esc_attr(wp_trim_words(strip_tags($job->description), 30)) . '" />' . "\n";
                echo '<meta property="og:type" content="article" />' . "\n";
                
                // JSON-LD structured data for job posting
                $structured_data = array(
                    '@context' => 'https://schema.org',
                    '@type' => 'JobPosting',
                    'title' => $job->title,
                    'description' => strip_tags($job->description),
                    'hiringOrganization' => array(
                        '@type' => 'Organization',
                        'name' => $job->company
                    ),
                    'jobLocation' => array(
                        '@type' => 'Place',
                        'address' => $job->location
                    ),
                    'employmentType' => strtoupper(str_replace('-', '_', $job->job_type)),
                    'datePosted' => date('c', strtotime($job->created_at))
                );
                
                if (!empty($job->salary_min)) {
                    $structured_data['baseSalary'] = array(
                        '@type' => 'MonetaryAmount',
                        'currency' => $job->currency,
                        'value' => array(
                            '@type' => 'QuantitativeValue',
                            'minValue' => $job->salary_min,
                            'maxValue' => $job->salary_max ?: $job->salary_min,
                            'unitText' => 'YEAR'
                        )
                    );
                }
                
                if ($job->expires_at) {
                    $structured_data['validThrough'] = date('c', strtotime($job->expires_at));
                }
                
                echo '<script type="application/ld+json">' . json_encode($structured_data) . '</script>' . "\n";
            }
        }
    }
    
    /**
     * Handle job view tracking
     */
    public function handle_job_view() {
        if (is_singular('ajsb_job')) {
            global $post;
            
            $user_id = is_user_logged_in() ? get_current_user_id() : null;
            AJSB_Job::record_view($post->ID, $user_id);
        }
    }
    
    /**
     * Filter job content to add custom elements
     */
    public function filter_job_content($content) {
        if (is_singular('ajsb_job') && in_the_loop() && is_main_query()) {
            global $post;
            
            $job = AJSB_Job::get($post->ID);
            if ($job) {
                $job_details = $this->render_job_details($job);
                $content = $job_details . $content . $this->render_job_application_section($job);
            }
        }
        
        return $content;
    }
    
    /**
     * Render job details section
     */
    private function render_job_details($job) {
        $job_types = AJSB_Job::get_job_types();
        $experience_levels = AJSB_Job::get_experience_levels();
        
        $job_type_label = isset($job_types[$job->job_type]) ? $job_types[$job->job_type] : $job->job_type;
        $experience_label = isset($experience_levels[$job->experience_level]) ? $experience_levels[$job->experience_level] : $job->experience_level;
        
        $salary_range = '';
        if (!empty($job->salary_min)) {
            $salary_range = number_format($job->salary_min);
            if (!empty($job->salary_max) && $job->salary_max != $job->salary_min) {
                $salary_range .= ' - ' . number_format($job->salary_max);
            }
            $salary_range = $job->currency . ' ' . $salary_range;
        }
        
        ob_start();
        ?>
        <div class="ajsb-job-details">
            <div class="ajsb-job-header">
                <h1 class="ajsb-job-title"><?php echo esc_html($job->title); ?></h1>
                <div class="ajsb-job-company">
                    <strong><?php echo esc_html($job->company); ?></strong>
                    <span class="ajsb-job-location">
                        <?php echo esc_html($job->location); ?>
                        <?php if ($job->remote_work): ?>
                            <span class="ajsb-remote-badge"><?php _e('Remote', 'ai-job-search-board'); ?></span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            
            <div class="ajsb-job-meta">
                <div class="ajsb-meta-item">
                    <span class="ajsb-meta-label"><?php _e('Job Type:', 'ai-job-search-board'); ?></span>
                    <span class="ajsb-meta-value"><?php echo esc_html($job_type_label); ?></span>
                </div>
                
                <div class="ajsb-meta-item">
                    <span class="ajsb-meta-label"><?php _e('Experience Level:', 'ai-job-search-board'); ?></span>
                    <span class="ajsb-meta-value"><?php echo esc_html($experience_label); ?></span>
                </div>
                
                <?php if ($salary_range): ?>
                    <div class="ajsb-meta-item">
                        <span class="ajsb-meta-label"><?php _e('Salary:', 'ai-job-search-board'); ?></span>
                        <span class="ajsb-meta-value"><?php echo esc_html($salary_range); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="ajsb-meta-item">
                    <span class="ajsb-meta-label"><?php _e('Posted:', 'ai-job-search-board'); ?></span>
                    <span class="ajsb-meta-value"><?php echo human_time_diff(strtotime($job->created_at)); ?> <?php _e('ago', 'ai-job-search-board'); ?></span>
                </div>
            </div>
            
            <?php if (!empty($job->skills)): ?>
                <div class="ajsb-job-skills">
                    <h3><?php _e('Required Skills', 'ai-job-search-board'); ?></h3>
                    <div class="ajsb-skills-list">
                        <?php
                        $skills = explode(',', $job->skills);
                        foreach ($skills as $skill):
                            ?>
                            <span class="ajsb-skill-tag"><?php echo esc_html(trim($skill)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render job application section
     */
    private function render_job_application_section($job) {
        $applied = false;
        $application = null;
        
        if (is_user_logged_in()) {
            $application = AJSB_Application::get_by_job_and_user($job->id, get_current_user_id());
            $applied = !empty($application);
        }
        
        ob_start();
        ?>
        <div class="ajsb-job-application-section">
            <?php if ($applied): ?>
                <div class="ajsb-application-status">
                    <h3><?php _e('Your Application', 'ai-job-search-board'); ?></h3>
                    <p>
                        <?php _e('You applied for this job on', 'ai-job-search-board'); ?>
                        <strong><?php echo date('F j, Y', strtotime($application->applied_at)); ?></strong>
                    </p>
                    <p>
                        <?php _e('Status:', 'ai-job-search-board'); ?>
                        <span class="ajsb-status ajsb-status-<?php echo esc_attr($application->status); ?>">
                            <?php
                            $statuses = AJSB_Application::get_statuses();
                            echo esc_html($statuses[$application->status] ?? $application->status);
                            ?>
                        </span>
                    </p>
                    
                    <?php if ($application->ai_match_score): ?>
                        <p>
                            <?php _e('AI Match Score:', 'ai-job-search-board'); ?>
                            <strong><?php echo round($application->ai_match_score); ?>%</strong>
                        </p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="ajsb-apply-section">
                    <?php if (is_user_logged_in()): ?>
                        <button class="ajsb-btn ajsb-btn-primary ajsb-btn-large ajsb-apply-btn" data-job-id="<?php echo esc_attr($job->id); ?>">
                            <?php _e('Apply for this Job', 'ai-job-search-board'); ?>
                        </button>
                        
                        <?php
                        // Show AI recommendations if available
                        $ai_engine = new AJSB_AI_Engine();
                        $user_id = get_current_user_id();
                        $match_score = $ai_engine->calculate_job_match_score($job, AJSB_User_Profile::get_by_user_id($user_id));
                        
                        if ($match_score > 0):
                            ?>
                            <div class="ajsb-ai-match">
                                <div class="ajsb-match-score">
                                    <span class="ajsb-score-circle" data-score="<?php echo esc_attr($match_score); ?>">
                                        <?php echo round($match_score); ?>%
                                    </span>
                                    <span class="ajsb-match-text"><?php _e('AI Match Score', 'ai-job-search-board'); ?></span>
                                </div>
                                <p class="ajsb-match-description">
                                    <?php _e('Based on your profile, this job is a good match for your skills and preferences.', 'ai-job-search-board'); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="ajsb-login-prompt">
                            <p><?php _e('Please log in to apply for this job.', 'ai-job-search-board'); ?></p>
                            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="ajsb-btn ajsb-btn-primary">
                                <?php _e('Log In', 'ai-job-search-board'); ?>
                            </a>
                            <a href="<?php echo wp_registration_url(); ?>" class="ajsb-btn ajsb-btn-secondary">
                                <?php _e('Register', 'ai-job-search-board'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($job->requirements)): ?>
                <div class="ajsb-job-requirements">
                    <h3><?php _e('Requirements', 'ai-job-search-board'); ?></h3>
                    <div class="ajsb-requirements-content">
                        <?php echo wp_kses_post(wpautop($job->requirements)); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($job->benefits)): ?>
                <div class="ajsb-job-benefits">
                    <h3><?php _e('Benefits', 'ai-job-search-board'); ?></h3>
                    <div class="ajsb-benefits-content">
                        <?php echo wp_kses_post(wpautop($job->benefits)); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Add job application modal to footer
     */
    public function add_job_application_modal() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $profile = AJSB_User_Profile::get_by_user_id(get_current_user_id());
        ?>
        <div id="ajsb-application-modal" class="ajsb-modal" style="display: none;">
            <div class="ajsb-modal-content">
                <div class="ajsb-modal-header">
                    <h2><?php _e('Apply for Job', 'ai-job-search-board'); ?></h2>
                    <button class="ajsb-modal-close">&times;</button>
                </div>
                
                <div class="ajsb-modal-body">
                    <form id="ajsb-application-form">
                        <div class="ajsb-form-group">
                            <label for="ajsb-cover-letter"><?php _e('Cover Letter', 'ai-job-search-board'); ?></label>
                            <textarea id="ajsb-cover-letter" name="cover_letter" rows="6" placeholder="<?php esc_attr_e('Tell us why you\'re interested in this position...', 'ai-job-search-board'); ?>"></textarea>
                        </div>
                        
                        <div class="ajsb-form-group">
                            <label for="ajsb-resume-url"><?php _e('Resume URL', 'ai-job-search-board'); ?></label>
                            <input type="url" id="ajsb-resume-url" name="resume_url" value="<?php echo esc_attr($profile->resume_url ?? ''); ?>" placeholder="<?php esc_attr_e('https://example.com/resume.pdf', 'ai-job-search-board'); ?>" />
                            <small class="ajsb-help-text"><?php _e('Link to your online resume or portfolio', 'ai-job-search-board'); ?></small>
                        </div>
                        
                        <div class="ajsb-form-actions">
                            <button type="submit" class="ajsb-btn ajsb-btn-primary">
                                <?php _e('Submit Application', 'ai-job-search-board'); ?>
                            </button>
                            <button type="button" class="ajsb-btn ajsb-btn-secondary ajsb-modal-close">
                                <?php _e('Cancel', 'ai-job-search-board'); ?>
                            </button>
                        </div>
                        
                        <input type="hidden" id="ajsb-job-id" name="job_id" value="" />
                    </form>
                </div>
            </div>
        </div>
        
        <div id="ajsb-modal-overlay" class="ajsb-modal-overlay" style="display: none;"></div>
        <?php
    }
    
    /**
     * Get job search form HTML
     */
    public static function get_job_search_form() {
        $job_types = AJSB_Job::get_job_types();
        $experience_levels = AJSB_Job::get_experience_levels();
        
        ob_start();
        ?>
        <div class="ajsb-search-form">
            <form id="ajsb-job-search-form" class="ajsb-form">
                <div class="ajsb-search-row">
                    <div class="ajsb-search-field">
                        <input type="text" id="ajsb-search-keywords" name="search" placeholder="<?php esc_attr_e('Job title, keywords, or company', 'ai-job-search-board'); ?>" />
                    </div>
                    
                    <div class="ajsb-search-field">
                        <input type="text" id="ajsb-search-location" name="location" placeholder="<?php esc_attr_e('Location', 'ai-job-search-board'); ?>" />
                    </div>
                    
                    <div class="ajsb-search-field">
                        <button type="submit" class="ajsb-btn ajsb-btn-primary">
                            <?php _e('Search Jobs', 'ai-job-search-board'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="ajsb-search-filters">
                    <div class="ajsb-filter-group">
                        <select name="job_type" id="ajsb-filter-job-type">
                            <option value=""><?php _e('All Job Types', 'ai-job-search-board'); ?></option>
                            <?php foreach ($job_types as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="ajsb-filter-group">
                        <select name="experience_level" id="ajsb-filter-experience">
                            <option value=""><?php _e('All Experience Levels', 'ai-job-search-board'); ?></option>
                            <?php foreach ($experience_levels as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="ajsb-filter-group">
                        <label class="ajsb-checkbox-label">
                            <input type="checkbox" name="remote_work" value="1" id="ajsb-filter-remote" />
                            <?php _e('Remote Work', 'ai-job-search-board'); ?>
                        </label>
                    </div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get job listings HTML
     */
    public static function get_job_listings($args = array()) {
        $defaults = array(
            'limit' => 10,
            'show_search' => true,
            'show_pagination' => true
        );
        
        $args = wp_parse_args($args, $defaults);
        
        ob_start();
        ?>
        <div class="ajsb-job-listings" id="ajsb-job-listings">
            <?php if ($args['show_search']): ?>
                <?php echo self::get_job_search_form(); ?>
            <?php endif; ?>
            
            <div class="ajsb-jobs-container" id="ajsb-jobs-container">
                <div class="ajsb-loading" style="display: none;">
                    <?php _e('Loading jobs...', 'ai-job-search-board'); ?>
                </div>
                
                <div class="ajsb-jobs-list" id="ajsb-jobs-list">
                    <?php
                    $jobs = AJSB_Job::get_jobs(array(
                        'status' => 'active',
                        'limit' => $args['limit']
                    ));
                    
                    if (!empty($jobs)):
                        foreach ($jobs as $job):
                            echo self::render_job_card($job);
                        endforeach;
                    else:
                        ?>
                        <div class="ajsb-no-jobs">
                            <?php _e('No jobs found.', 'ai-job-search-board'); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($args['show_pagination']): ?>
                    <div class="ajsb-pagination" id="ajsb-pagination">
                        <!-- Pagination will be loaded via AJAX -->
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render job card
     */
    public static function render_job_card($job) {
        $job_types = AJSB_Job::get_job_types();
        $experience_levels = AJSB_Job::get_experience_levels();
        
        $job_type_label = isset($job_types[$job->job_type]) ? $job_types[$job->job_type] : $job->job_type;
        $experience_label = isset($experience_levels[$job->experience_level]) ? $experience_levels[$job->experience_level] : $job->experience_level;
        
        $salary_range = '';
        if (!empty($job->salary_min)) {
            $salary_range = number_format($job->salary_min);
            if (!empty($job->salary_max) && $job->salary_max != $job->salary_min) {
                $salary_range .= ' - ' . number_format($job->salary_max);
            }
            $salary_range = $job->currency . ' ' . $salary_range;
        }
        
        $applied = false;
        if (is_user_logged_in()) {
            $application = AJSB_Application::get_by_job_and_user($job->id, get_current_user_id());
            $applied = !empty($application);
        }
        
        ob_start();
        ?>
        <div class="ajsb-job-card" data-job-id="<?php echo esc_attr($job->id); ?>">
            <?php if ($job->featured): ?>
                <div class="ajsb-featured-badge"><?php _e('Featured', 'ai-job-search-board'); ?></div>
            <?php endif; ?>
            
            <div class="ajsb-job-header">
                <h3 class="ajsb-job-title">
                    <a href="<?php echo get_permalink($job->id); ?>" class="ajsb-job-link">
                        <?php echo esc_html($job->title); ?>
                    </a>
                </h3>
                <div class="ajsb-job-company"><?php echo esc_html($job->company); ?></div>
            </div>
            
            <div class="ajsb-job-meta">
                <div class="ajsb-location">
                    <?php echo esc_html($job->location); ?>
                    <?php if ($job->remote_work): ?>
                        <span class="ajsb-remote"><?php _e('Remote', 'ai-job-search-board'); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="ajsb-job-details">
                    <span class="ajsb-job-type"><?php echo esc_html($job_type_label); ?></span>
                    <span class="ajsb-experience"><?php echo esc_html($experience_label); ?></span>
                    <?php if ($salary_range): ?>
                        <span class="ajsb-salary"><?php echo esc_html($salary_range); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="ajsb-job-description">
                <?php echo wp_trim_words(wp_strip_all_tags($job->description), 30); ?>
            </div>
            
            <?php if (!empty($job->skills)): ?>
                <div class="ajsb-job-skills">
                    <?php
                    $skills = explode(',', $job->skills);
                    foreach (array_slice($skills, 0, 5) as $skill):
                        ?>
                        <span class="ajsb-skill-tag"><?php echo esc_html(trim($skill)); ?></span>
                    <?php endforeach; ?>
                    <?php if (count($skills) > 5): ?>
                        <span class="ajsb-more-skills">+<?php echo count($skills) - 5; ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="ajsb-job-footer">
                <div class="ajsb-job-date">
                    <?php echo sprintf(__('Posted %s ago', 'ai-job-search-board'), human_time_diff(strtotime($job->created_at))); ?>
                </div>
                
                <div class="ajsb-job-actions">
                    <?php if ($applied): ?>
                        <span class="ajsb-applied-badge"><?php _e('Applied', 'ai-job-search-board'); ?></span>
                    <?php else: ?>
                        <button class="ajsb-btn ajsb-btn-primary ajsb-apply-btn" data-job-id="<?php echo esc_attr($job->id); ?>">
                            <?php _e('Apply', 'ai-job-search-board'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <a href="<?php echo get_permalink($job->id); ?>" class="ajsb-btn ajsb-btn-secondary">
                        <?php _e('View Details', 'ai-job-search-board'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}