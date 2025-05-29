<?php
/**
 * Job application management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJSB_Application {
    
    /**
     * Submit a job application
     */
    public static function submit($data) {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('applications');
        
        // Check if user already applied for this job
        $existing = self::get_by_job_and_user($data['job_id'], $data['user_id']);
        if ($existing) {
            return new WP_Error('already_applied', __('You have already applied for this job.', 'ai-job-search-board'));
        }
        
        $defaults = array(
            'status' => 'pending',
            'applied_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Calculate AI match score
        $data['ai_match_score'] = self::calculate_match_score($data['job_id'], $data['user_id']);
        
        $result = $wpdb->insert($table, $data);
        
        if ($result !== false) {
            $application_id = $wpdb->insert_id;
            
            // Send notification emails
            self::send_application_notifications($application_id);
            
            return $application_id;
        }
        
        return false;
    }
    
    /**
     * Update application status
     */
    public static function update_status($id, $status, $notes = '') {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('applications');
        
        $data = array(
            'status' => $status,
            'updated_at' => current_time('mysql')
        );
        
        if (!empty($notes)) {
            $data['notes'] = $notes;
        }
        
        $result = $wpdb->update($table, $data, array('id' => $id));
        
        if ($result !== false) {
            // Send status update notification
            self::send_status_update_notification($id, $status);
        }
        
        return $result !== false;
    }
    
    /**
     * Get application by ID
     */
    public static function get($id) {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('applications');
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    /**
     * Get application by job and user
     */
    public static function get_by_job_and_user($job_id, $user_id) {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('applications');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE job_id = %d AND user_id = %d",
            $job_id,
            $user_id
        ));
    }
    
    /**
     * Get applications with filters
     */
    public static function get_applications($args = array()) {
        global $wpdb;
        
        $applications_table = AJSB_Database::get_table_name('applications');
        $jobs_table = AJSB_Database::get_table_name('jobs');
        
        $defaults = array(
            'limit' => 10,
            'offset' => 0,
            'orderby' => 'applied_at',
            'order' => 'DESC',
            'status' => '',
            'job_id' => '',
            'user_id' => '',
            'employer_id' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array();
        $where_values = array();
        
        // Status filter
        if (!empty($args['status'])) {
            $where_clauses[] = "a.status = %s";
            $where_values[] = $args['status'];
        }
        
        // Job filter
        if (!empty($args['job_id'])) {
            $where_clauses[] = "a.job_id = %d";
            $where_values[] = (int)$args['job_id'];
        }
        
        // User filter
        if (!empty($args['user_id'])) {
            $where_clauses[] = "a.user_id = %d";
            $where_values[] = (int)$args['user_id'];
        }
        
        // Employer filter
        if (!empty($args['employer_id'])) {
            $where_clauses[] = "j.employer_id = %d";
            $where_values[] = (int)$args['employer_id'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $order_sql = sprintf('ORDER BY a.%s %s', 
            sanitize_sql_orderby($args['orderby']), 
            $args['order'] === 'ASC' ? 'ASC' : 'DESC'
        );
        
        $limit_sql = sprintf('LIMIT %d OFFSET %d', (int)$args['limit'], (int)$args['offset']);
        
        $sql = "SELECT a.*, j.title as job_title, j.company, j.location 
                FROM $applications_table a 
                LEFT JOIN $jobs_table j ON a.job_id = j.id 
                $where_sql $order_sql $limit_sql";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get application count
     */
    public static function get_application_count($args = array()) {
        global $wpdb;
        
        $applications_table = AJSB_Database::get_table_name('applications');
        $jobs_table = AJSB_Database::get_table_name('jobs');
        
        $defaults = array(
            'status' => '',
            'job_id' => '',
            'user_id' => '',
            'employer_id' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array();
        $where_values = array();
        
        if (!empty($args['status'])) {
            $where_clauses[] = "a.status = %s";
            $where_values[] = $args['status'];
        }
        
        if (!empty($args['job_id'])) {
            $where_clauses[] = "a.job_id = %d";
            $where_values[] = (int)$args['job_id'];
        }
        
        if (!empty($args['user_id'])) {
            $where_clauses[] = "a.user_id = %d";
            $where_values[] = (int)$args['user_id'];
        }
        
        if (!empty($args['employer_id'])) {
            $where_clauses[] = "j.employer_id = %d";
            $where_values[] = (int)$args['employer_id'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $sql = "SELECT COUNT(*) FROM $applications_table a 
                LEFT JOIN $jobs_table j ON a.job_id = j.id 
                $where_sql";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return (int)$wpdb->get_var($sql);
    }
    
    /**
     * Delete application
     */
    public static function delete($id) {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('applications');
        
        return $wpdb->delete($table, array('id' => $id));
    }
    
    /**
     * Calculate AI match score between job and user
     */
    private static function calculate_match_score($job_id, $user_id) {
        $job = AJSB_Job::get($job_id);
        $profile = AJSB_User_Profile::get_by_user_id($user_id);
        
        if (!$job || !$profile) {
            return 0.0;
        }
        
        $score = 0.0;
        $max_score = 100.0;
        
        // Skills matching (40% weight)
        $job_skills = !empty($job->skills) ? explode(',', strtolower($job->skills)) : array();
        $user_skills = !empty($profile->skills) ? explode(',', strtolower($profile->skills)) : array();
        
        $job_skills = array_map('trim', $job_skills);
        $user_skills = array_map('trim', $user_skills);
        
        if (!empty($job_skills) && !empty($user_skills)) {
            $matching_skills = array_intersect($job_skills, $user_skills);
            $skills_score = (count($matching_skills) / count($job_skills)) * 40;
            $score += $skills_score;
        }
        
        // Experience level matching (20% weight)
        if ($job->experience_level === $profile->experience_level) {
            $score += 20;
        } elseif (self::is_compatible_experience_level($job->experience_level, $profile->experience_level)) {
            $score += 10;
        }
        
        // Location matching (15% weight)
        if ($job->remote_work || stripos($profile->location, $job->location) !== false) {
            $score += 15;
        }
        
        // Job type preference (10% weight)
        if ($job->job_type === $profile->desired_job_type) {
            $score += 10;
        }
        
        // Salary expectations (10% weight)
        if (!empty($job->salary_min) && !empty($profile->desired_salary_min)) {
            if ($job->salary_min >= $profile->desired_salary_min) {
                $score += 10;
            } elseif ($job->salary_max >= $profile->desired_salary_min) {
                $score += 5;
            }
        }
        
        // Remote work preference (5% weight)
        if ($job->remote_work && $profile->remote_preference) {
            $score += 5;
        }
        
        return min($score, $max_score);
    }
    
    /**
     * Check if experience levels are compatible
     */
    private static function is_compatible_experience_level($job_level, $user_level) {
        $levels = array('entry', 'junior', 'mid', 'senior', 'lead', 'executive');
        
        $job_index = array_search($job_level, $levels);
        $user_index = array_search($user_level, $levels);
        
        if ($job_index === false || $user_index === false) {
            return false;
        }
        
        // Allow one level difference
        return abs($job_index - $user_index) <= 1;
    }
    
    /**
     * Send application notifications
     */
    private static function send_application_notifications($application_id) {
        $settings = get_option('ajsb_settings', array());
        
        if (empty($settings['email_notifications'])) {
            return;
        }
        
        $application = self::get($application_id);
        if (!$application) {
            return;
        }
        
        $job = AJSB_Job::get($application->job_id);
        $user = get_user_by('id', $application->user_id);
        $employer = get_user_by('id', $job->employer_id);
        
        if (!$job || !$user || !$employer) {
            return;
        }
        
        // Send notification to employer
        $subject = sprintf(__('New Application for %s', 'ai-job-search-board'), $job->title);
        $message = sprintf(
            __('You have received a new application for the job "%s" from %s.', 'ai-job-search-board'),
            $job->title,
            $user->display_name
        );
        
        wp_mail($employer->user_email, $subject, $message);
        
        // Send confirmation to applicant
        $subject = sprintf(__('Application Submitted for %s', 'ai-job-search-board'), $job->title);
        $message = sprintf(
            __('Your application for "%s" at %s has been submitted successfully.', 'ai-job-search-board'),
            $job->title,
            $job->company
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Send status update notification
     */
    private static function send_status_update_notification($application_id, $status) {
        $settings = get_option('ajsb_settings', array());
        
        if (empty($settings['email_notifications'])) {
            return;
        }
        
        $application = self::get($application_id);
        if (!$application) {
            return;
        }
        
        $job = AJSB_Job::get($application->job_id);
        $user = get_user_by('id', $application->user_id);
        
        if (!$job || !$user) {
            return;
        }
        
        $status_labels = array(
            'pending' => __('Pending Review', 'ai-job-search-board'),
            'reviewed' => __('Under Review', 'ai-job-search-board'),
            'shortlisted' => __('Shortlisted', 'ai-job-search-board'),
            'interview' => __('Interview Scheduled', 'ai-job-search-board'),
            'accepted' => __('Accepted', 'ai-job-search-board'),
            'rejected' => __('Rejected', 'ai-job-search-board')
        );
        
        $status_label = isset($status_labels[$status]) ? $status_labels[$status] : $status;
        
        $subject = sprintf(__('Application Status Update: %s', 'ai-job-search-board'), $job->title);
        $message = sprintf(
            __('Your application status for "%s" at %s has been updated to: %s', 'ai-job-search-board'),
            $job->title,
            $job->company,
            $status_label
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Get application statuses
     */
    public static function get_statuses() {
        return array(
            'pending' => __('Pending Review', 'ai-job-search-board'),
            'reviewed' => __('Under Review', 'ai-job-search-board'),
            'shortlisted' => __('Shortlisted', 'ai-job-search-board'),
            'interview' => __('Interview Scheduled', 'ai-job-search-board'),
            'accepted' => __('Accepted', 'ai-job-search-board'),
            'rejected' => __('Rejected', 'ai-job-search-board')
        );
    }
}