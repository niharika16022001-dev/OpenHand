<?php
/**
 * Job management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJSB_Job {
    
    /**
     * Create a new job
     */
    public static function create($data) {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('jobs');
        
        $defaults = array(
            'job_type' => 'full-time',
            'currency' => 'USD',
            'experience_level' => 'entry',
            'remote_work' => 0,
            'featured' => 0,
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Generate AI tags
        if (!empty($data['description']) && !empty($data['skills'])) {
            $data['ai_tags'] = self::generate_ai_tags($data);
        }
        
        $result = $wpdb->insert($table, $data);
        
        if ($result !== false) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update a job
     */
    public static function update($id, $data) {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('jobs');
        
        $data['updated_at'] = current_time('mysql');
        
        // Regenerate AI tags if content changed
        if (isset($data['description']) || isset($data['skills'])) {
            $job = self::get($id);
            if ($job) {
                $updated_job = array_merge((array)$job, $data);
                $data['ai_tags'] = self::generate_ai_tags($updated_job);
            }
        }
        
        $result = $wpdb->update($table, $data, array('id' => $id));
        
        return $result !== false;
    }
    
    /**
     * Get a job by ID
     */
    public static function get($id) {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('jobs');
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    /**
     * Delete a job
     */
    public static function delete($id) {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('jobs');
        
        return $wpdb->delete($table, array('id' => $id));
    }
    
    /**
     * Get jobs with filters
     */
    public static function get_jobs($args = array()) {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('jobs');
        
        $defaults = array(
            'status' => 'active',
            'limit' => 10,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'search' => '',
            'location' => '',
            'job_type' => '',
            'remote_work' => '',
            'experience_level' => '',
            'salary_min' => '',
            'salary_max' => '',
            'employer_id' => '',
            'featured' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array();
        $where_values = array();
        
        // Status filter
        if (!empty($args['status'])) {
            $where_clauses[] = "status = %s";
            $where_values[] = $args['status'];
        }
        
        // Search filter
        if (!empty($args['search'])) {
            $where_clauses[] = "(title LIKE %s OR description LIKE %s OR company LIKE %s OR skills LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        // Location filter
        if (!empty($args['location'])) {
            $where_clauses[] = "location LIKE %s";
            $where_values[] = '%' . $wpdb->esc_like($args['location']) . '%';
        }
        
        // Job type filter
        if (!empty($args['job_type'])) {
            $where_clauses[] = "job_type = %s";
            $where_values[] = $args['job_type'];
        }
        
        // Remote work filter
        if ($args['remote_work'] !== '') {
            $where_clauses[] = "remote_work = %d";
            $where_values[] = (int)$args['remote_work'];
        }
        
        // Experience level filter
        if (!empty($args['experience_level'])) {
            $where_clauses[] = "experience_level = %s";
            $where_values[] = $args['experience_level'];
        }
        
        // Salary filters
        if (!empty($args['salary_min'])) {
            $where_clauses[] = "salary_max >= %d";
            $where_values[] = (int)$args['salary_min'];
        }
        
        if (!empty($args['salary_max'])) {
            $where_clauses[] = "salary_min <= %d";
            $where_values[] = (int)$args['salary_max'];
        }
        
        // Employer filter
        if (!empty($args['employer_id'])) {
            $where_clauses[] = "employer_id = %d";
            $where_values[] = (int)$args['employer_id'];
        }
        
        // Featured filter
        if ($args['featured'] !== '') {
            $where_clauses[] = "featured = %d";
            $where_values[] = (int)$args['featured'];
        }
        
        // Expiration filter
        $where_clauses[] = "(expires_at IS NULL OR expires_at > NOW())";
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $order_sql = sprintf('ORDER BY %s %s', 
            sanitize_sql_orderby($args['orderby']), 
            $args['order'] === 'ASC' ? 'ASC' : 'DESC'
        );
        
        $limit_sql = sprintf('LIMIT %d OFFSET %d', (int)$args['limit'], (int)$args['offset']);
        
        $sql = "SELECT * FROM $table $where_sql $order_sql $limit_sql";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get job count with filters
     */
    public static function get_job_count($args = array()) {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('jobs');
        
        $defaults = array(
            'status' => 'active',
            'search' => '',
            'location' => '',
            'job_type' => '',
            'remote_work' => '',
            'experience_level' => '',
            'salary_min' => '',
            'salary_max' => '',
            'employer_id' => '',
            'featured' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array();
        $where_values = array();
        
        // Apply same filters as get_jobs method
        if (!empty($args['status'])) {
            $where_clauses[] = "status = %s";
            $where_values[] = $args['status'];
        }
        
        if (!empty($args['search'])) {
            $where_clauses[] = "(title LIKE %s OR description LIKE %s OR company LIKE %s OR skills LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        if (!empty($args['location'])) {
            $where_clauses[] = "location LIKE %s";
            $where_values[] = '%' . $wpdb->esc_like($args['location']) . '%';
        }
        
        if (!empty($args['job_type'])) {
            $where_clauses[] = "job_type = %s";
            $where_values[] = $args['job_type'];
        }
        
        if ($args['remote_work'] !== '') {
            $where_clauses[] = "remote_work = %d";
            $where_values[] = (int)$args['remote_work'];
        }
        
        if (!empty($args['experience_level'])) {
            $where_clauses[] = "experience_level = %s";
            $where_values[] = $args['experience_level'];
        }
        
        if (!empty($args['salary_min'])) {
            $where_clauses[] = "salary_max >= %d";
            $where_values[] = (int)$args['salary_min'];
        }
        
        if (!empty($args['salary_max'])) {
            $where_clauses[] = "salary_min <= %d";
            $where_values[] = (int)$args['salary_max'];
        }
        
        if (!empty($args['employer_id'])) {
            $where_clauses[] = "employer_id = %d";
            $where_values[] = (int)$args['employer_id'];
        }
        
        if ($args['featured'] !== '') {
            $where_clauses[] = "featured = %d";
            $where_values[] = (int)$args['featured'];
        }
        
        $where_clauses[] = "(expires_at IS NULL OR expires_at > NOW())";
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $sql = "SELECT COUNT(*) FROM $table $where_sql";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return (int)$wpdb->get_var($sql);
    }
    
    /**
     * Record job view
     */
    public static function record_view($job_id, $user_id = null) {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('job_views');
        
        $data = array(
            'job_id' => $job_id,
            'user_id' => $user_id,
            'ip_address' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'viewed_at' => current_time('mysql')
        );
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Generate AI tags for job
     */
    private static function generate_ai_tags($job_data) {
        $tags = array();
        
        // Extract keywords from title and description
        $text = $job_data['title'] . ' ' . $job_data['description'];
        if (!empty($job_data['skills'])) {
            $text .= ' ' . $job_data['skills'];
        }
        
        // Simple keyword extraction (can be enhanced with AI API)
        $keywords = self::extract_keywords($text);
        
        $tags = array(
            'keywords' => $keywords,
            'job_type' => $job_data['job_type'],
            'experience_level' => $job_data['experience_level'],
            'remote_work' => (bool)$job_data['remote_work'],
            'location' => $job_data['location'],
            'company' => $job_data['company']
        );
        
        return json_encode($tags);
    }
    
    /**
     * Extract keywords from text
     */
    private static function extract_keywords($text) {
        // Remove HTML tags and normalize text
        $text = wp_strip_all_tags($text);
        $text = strtolower($text);
        
        // Common tech keywords
        $tech_keywords = array(
            'php', 'javascript', 'python', 'java', 'react', 'vue', 'angular', 'node.js',
            'mysql', 'postgresql', 'mongodb', 'redis', 'docker', 'kubernetes',
            'aws', 'azure', 'gcp', 'git', 'linux', 'windows', 'macos',
            'html', 'css', 'sass', 'less', 'bootstrap', 'tailwind',
            'wordpress', 'drupal', 'laravel', 'symfony', 'django', 'flask',
            'machine learning', 'ai', 'data science', 'analytics', 'sql'
        );
        
        $found_keywords = array();
        
        foreach ($tech_keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $found_keywords[] = $keyword;
            }
        }
        
        return array_unique($found_keywords);
    }
    
    /**
     * Get job types
     */
    public static function get_job_types() {
        return array(
            'full-time' => __('Full Time', 'ai-job-search-board'),
            'part-time' => __('Part Time', 'ai-job-search-board'),
            'contract' => __('Contract', 'ai-job-search-board'),
            'freelance' => __('Freelance', 'ai-job-search-board'),
            'internship' => __('Internship', 'ai-job-search-board'),
            'temporary' => __('Temporary', 'ai-job-search-board')
        );
    }
    
    /**
     * Get experience levels
     */
    public static function get_experience_levels() {
        return array(
            'entry' => __('Entry Level', 'ai-job-search-board'),
            'junior' => __('Junior', 'ai-job-search-board'),
            'mid' => __('Mid Level', 'ai-job-search-board'),
            'senior' => __('Senior', 'ai-job-search-board'),
            'lead' => __('Lead', 'ai-job-search-board'),
            'executive' => __('Executive', 'ai-job-search-board')
        );
    }
}