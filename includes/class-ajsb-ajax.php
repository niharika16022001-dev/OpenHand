<?php
/**
 * AJAX handlers for the plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJSB_Ajax {
    
    public function __construct() {
        // Public AJAX actions
        add_action('wp_ajax_ajsb_search_jobs', array($this, 'search_jobs'));
        add_action('wp_ajax_nopriv_ajsb_search_jobs', array($this, 'search_jobs'));
        
        add_action('wp_ajax_ajsb_apply_job', array($this, 'apply_job'));
        add_action('wp_ajax_ajsb_save_profile', array($this, 'save_profile'));
        add_action('wp_ajax_ajsb_get_recommendations', array($this, 'get_recommendations'));
        add_action('wp_ajax_ajsb_record_job_view', array($this, 'record_job_view'));
        
        // Admin AJAX actions
        add_action('wp_ajax_ajsb_admin_update_application_status', array($this, 'admin_update_application_status'));
        add_action('wp_ajax_ajsb_admin_delete_job', array($this, 'admin_delete_job'));
        add_action('wp_ajax_ajsb_admin_get_analytics', array($this, 'admin_get_analytics'));
    }
    
    /**
     * Search jobs via AJAX
     */
    public function search_jobs() {
        check_ajax_referer('ajsb_nonce', 'nonce');
        
        $search = sanitize_text_field($_POST['search'] ?? '');
        $location = sanitize_text_field($_POST['location'] ?? '');
        $job_type = sanitize_text_field($_POST['job_type'] ?? '');
        $experience_level = sanitize_text_field($_POST['experience_level'] ?? '');
        $remote_work = isset($_POST['remote_work']) ? (int)$_POST['remote_work'] : '';
        $page = max(1, (int)($_POST['page'] ?? 1));
        $per_page = 10;
        
        $args = array(
            'search' => $search,
            'location' => $location,
            'job_type' => $job_type,
            'experience_level' => $experience_level,
            'remote_work' => $remote_work,
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'status' => 'active'
        );
        
        $jobs = AJSB_Job::get_jobs($args);
        $total = AJSB_Job::get_job_count($args);
        
        $html = '';
        if (!empty($jobs)) {
            foreach ($jobs as $job) {
                $html .= $this->render_job_card($job);
            }
        } else {
            $html = '<div class="ajsb-no-jobs">' . __('No jobs found matching your criteria.', 'ai-job-search-board') . '</div>';
        }
        
        wp_send_json_success(array(
            'html' => $html,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ));
    }
    
    /**
     * Apply for a job via AJAX
     */
    public function apply_job() {
        check_ajax_referer('ajsb_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to apply for jobs.', 'ai-job-search-board'));
        }
        
        $job_id = (int)($_POST['job_id'] ?? 0);
        $cover_letter = sanitize_textarea_field($_POST['cover_letter'] ?? '');
        $resume_url = esc_url_raw($_POST['resume_url'] ?? '');
        
        if (!$job_id) {
            wp_send_json_error(__('Invalid job ID.', 'ai-job-search-board'));
        }
        
        $job = AJSB_Job::get($job_id);
        if (!$job || $job->status !== 'active') {
            wp_send_json_error(__('Job not found or no longer active.', 'ai-job-search-board'));
        }
        
        $user_id = get_current_user_id();
        
        $application_data = array(
            'job_id' => $job_id,
            'user_id' => $user_id,
            'cover_letter' => $cover_letter,
            'resume_url' => $resume_url
        );
        
        $result = AJSB_Application::submit($application_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } elseif ($result) {
            wp_send_json_success(__('Application submitted successfully!', 'ai-job-search-board'));
        } else {
            wp_send_json_error(__('Failed to submit application. Please try again.', 'ai-job-search-board'));
        }
    }
    
    /**
     * Save user profile via AJAX
     */
    public function save_profile() {
        check_ajax_referer('ajsb_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to save your profile.', 'ai-job-search-board'));
        }
        
        $user_id = get_current_user_id();
        
        $profile_data = array(
            'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
            'last_name' => sanitize_text_field($_POST['last_name'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'location' => sanitize_text_field($_POST['location'] ?? ''),
            'bio' => sanitize_textarea_field($_POST['bio'] ?? ''),
            'skills' => sanitize_textarea_field($_POST['skills'] ?? ''),
            'experience' => sanitize_textarea_field($_POST['experience'] ?? ''),
            'education' => sanitize_textarea_field($_POST['education'] ?? ''),
            'resume_url' => esc_url_raw($_POST['resume_url'] ?? ''),
            'portfolio_url' => esc_url_raw($_POST['portfolio_url'] ?? ''),
            'linkedin_url' => esc_url_raw($_POST['linkedin_url'] ?? ''),
            'github_url' => esc_url_raw($_POST['github_url'] ?? ''),
            'desired_salary_min' => !empty($_POST['desired_salary_min']) ? (float)$_POST['desired_salary_min'] : null,
            'desired_salary_max' => !empty($_POST['desired_salary_max']) ? (float)$_POST['desired_salary_max'] : null,
            'desired_job_type' => sanitize_text_field($_POST['desired_job_type'] ?? ''),
            'experience_level' => sanitize_text_field($_POST['experience_level'] ?? ''),
            'remote_preference' => isset($_POST['remote_preference']) ? 1 : 0,
            'availability' => sanitize_text_field($_POST['availability'] ?? 'immediately')
        );
        
        $result = AJSB_User_Profile::save($user_id, $profile_data);
        
        if ($result) {
            // Regenerate AI recommendations
            $ai_engine = new AJSB_AI_Engine();
            $ai_engine->generate_recommendations($user_id);
            
            wp_send_json_success(__('Profile saved successfully!', 'ai-job-search-board'));
        } else {
            wp_send_json_error(__('Failed to save profile. Please try again.', 'ai-job-search-board'));
        }
    }
    
    /**
     * Get AI recommendations via AJAX
     */
    public function get_recommendations() {
        check_ajax_referer('ajsb_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to get recommendations.', 'ai-job-search-board'));
        }
        
        $user_id = get_current_user_id();
        $ai_engine = new AJSB_AI_Engine();
        
        $recommendations = $ai_engine->get_user_recommendations($user_id, 10);
        
        $html = '';
        if (!empty($recommendations)) {
            foreach ($recommendations as $rec) {
                $html .= $this->render_recommendation_card($rec);
            }
        } else {
            $html = '<div class="ajsb-no-recommendations">' . 
                    __('No recommendations available. Please complete your profile to get personalized job recommendations.', 'ai-job-search-board') . 
                    '</div>';
        }
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * Record job view via AJAX
     */
    public function record_job_view() {
        check_ajax_referer('ajsb_nonce', 'nonce');
        
        $job_id = (int)($_POST['job_id'] ?? 0);
        
        if (!$job_id) {
            wp_send_json_error(__('Invalid job ID.', 'ai-job-search-board'));
        }
        
        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        
        AJSB_Job::record_view($job_id, $user_id);
        
        wp_send_json_success();
    }
    
    /**
     * Admin: Update application status
     */
    public function admin_update_application_status() {
        check_ajax_referer('ajsb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'ai-job-search-board'));
        }
        
        $application_id = (int)($_POST['application_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        if (!$application_id || !$status) {
            wp_send_json_error(__('Invalid data provided.', 'ai-job-search-board'));
        }
        
        $result = AJSB_Application::update_status($application_id, $status, $notes);
        
        if ($result) {
            wp_send_json_success(__('Application status updated successfully.', 'ai-job-search-board'));
        } else {
            wp_send_json_error(__('Failed to update application status.', 'ai-job-search-board'));
        }
    }
    
    /**
     * Admin: Delete job
     */
    public function admin_delete_job() {
        check_ajax_referer('ajsb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'ai-job-search-board'));
        }
        
        $job_id = (int)($_POST['job_id'] ?? 0);
        
        if (!$job_id) {
            wp_send_json_error(__('Invalid job ID.', 'ai-job-search-board'));
        }
        
        $result = AJSB_Job::delete($job_id);
        
        if ($result) {
            wp_send_json_success(__('Job deleted successfully.', 'ai-job-search-board'));
        } else {
            wp_send_json_error(__('Failed to delete job.', 'ai-job-search-board'));
        }
    }
    
    /**
     * Admin: Get analytics data
     */
    public function admin_get_analytics() {
        check_ajax_referer('ajsb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'ai-job-search-board'));
        }
        
        global $wpdb;
        
        $jobs_table = AJSB_Database::get_table_name('jobs');
        $applications_table = AJSB_Database::get_table_name('applications');
        $views_table = AJSB_Database::get_table_name('job_views');
        
        // Get basic stats
        $total_jobs = $wpdb->get_var("SELECT COUNT(*) FROM $jobs_table WHERE status = 'active'");
        $total_applications = $wpdb->get_var("SELECT COUNT(*) FROM $applications_table");
        $total_views = $wpdb->get_var("SELECT COUNT(*) FROM $views_table WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        
        // Get applications by status
        $applications_by_status = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM $applications_table GROUP BY status"
        );
        
        // Get top viewed jobs
        $top_jobs = $wpdb->get_results(
            "SELECT j.title, j.company, COUNT(v.id) as views 
             FROM $jobs_table j 
             LEFT JOIN $views_table v ON j.id = v.job_id 
             WHERE j.status = 'active' AND v.viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY j.id 
             ORDER BY views DESC 
             LIMIT 10"
        );
        
        wp_send_json_success(array(
            'total_jobs' => $total_jobs,
            'total_applications' => $total_applications,
            'total_views' => $total_views,
            'applications_by_status' => $applications_by_status,
            'top_jobs' => $top_jobs
        ));
    }
    
    /**
     * Render job card HTML
     */
    private function render_job_card($job) {
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
            <div class="ajsb-job-header">
                <h3 class="ajsb-job-title">
                    <a href="#" class="ajsb-job-link" data-job-id="<?php echo esc_attr($job->id); ?>">
                        <?php echo esc_html($job->title); ?>
                    </a>
                </h3>
                <?php if ($job->featured): ?>
                    <span class="ajsb-featured-badge"><?php _e('Featured', 'ai-job-search-board'); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="ajsb-job-meta">
                <div class="ajsb-company"><?php echo esc_html($job->company); ?></div>
                <div class="ajsb-location">
                    <?php echo esc_html($job->location); ?>
                    <?php if ($job->remote_work): ?>
                        <span class="ajsb-remote"><?php _e('Remote', 'ai-job-search-board'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="ajsb-job-details">
                <span class="ajsb-job-type"><?php echo esc_html($job_type_label); ?></span>
                <span class="ajsb-experience"><?php echo esc_html($experience_label); ?></span>
                <?php if ($salary_range): ?>
                    <span class="ajsb-salary"><?php echo esc_html($salary_range); ?></span>
                <?php endif; ?>
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
                </div>
            <?php endif; ?>
            
            <div class="ajsb-job-actions">
                <div class="ajsb-job-date">
                    <?php echo sprintf(__('Posted %s ago', 'ai-job-search-board'), human_time_diff(strtotime($job->created_at))); ?>
                </div>
                
                <?php if ($applied): ?>
                    <button class="ajsb-btn ajsb-btn-applied" disabled>
                        <?php _e('Applied', 'ai-job-search-board'); ?>
                    </button>
                <?php else: ?>
                    <button class="ajsb-btn ajsb-btn-primary ajsb-apply-btn" data-job-id="<?php echo esc_attr($job->id); ?>">
                        <?php _e('Apply Now', 'ai-job-search-board'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render recommendation card HTML
     */
    private function render_recommendation_card($rec) {
        $reasons = json_decode($rec->reasons, true);
        if (!is_array($reasons)) {
            $reasons = array();
        }
        
        ob_start();
        ?>
        <div class="ajsb-recommendation-card" data-job-id="<?php echo esc_attr($rec->job_id); ?>">
            <div class="ajsb-match-score">
                <div class="ajsb-score-circle" data-score="<?php echo esc_attr($rec->match_score); ?>">
                    <span class="ajsb-score-text"><?php echo round($rec->match_score); ?>%</span>
                </div>
                <div class="ajsb-match-label"><?php _e('Match', 'ai-job-search-board'); ?></div>
            </div>
            
            <div class="ajsb-recommendation-content">
                <h4 class="ajsb-job-title">
                    <a href="#" class="ajsb-job-link" data-job-id="<?php echo esc_attr($rec->job_id); ?>">
                        <?php echo esc_html($rec->title); ?>
                    </a>
                </h4>
                
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
                    <button class="ajsb-btn ajsb-btn-secondary ajsb-view-job-btn" data-job-id="<?php echo esc_attr($rec->job_id); ?>">
                        <?php _e('View Details', 'ai-job-search-board'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}