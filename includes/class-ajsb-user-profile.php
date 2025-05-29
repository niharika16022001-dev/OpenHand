<?php
/**
 * User profile management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJSB_User_Profile {
    
    /**
     * Create or update user profile
     */
    public static function save($user_id, $data) {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('user_profiles');
        
        $existing = self::get_by_user_id($user_id);
        
        $defaults = array(
            'remote_preference' => 0,
            'availability' => 'immediately',
            'updated_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Generate AI profile
        $data['ai_profile'] = self::generate_ai_profile($data);
        
        if ($existing) {
            // Update existing profile
            $result = $wpdb->update($table, $data, array('user_id' => $user_id));
            return $result !== false;
        } else {
            // Create new profile
            $data['user_id'] = $user_id;
            $data['created_at'] = current_time('mysql');
            
            $result = $wpdb->insert($table, $data);
            return $result !== false ? $wpdb->insert_id : false;
        }
    }
    
    /**
     * Get profile by user ID
     */
    public static function get_by_user_id($user_id) {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('user_profiles');
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id));
    }
    
    /**
     * Get profile by ID
     */
    public static function get($id) {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('user_profiles');
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    /**
     * Delete profile
     */
    public static function delete($user_id) {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('user_profiles');
        
        return $wpdb->delete($table, array('user_id' => $user_id));
    }
    
    /**
     * Search profiles
     */
    public static function search_profiles($args = array()) {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('user_profiles');
        
        $defaults = array(
            'limit' => 10,
            'offset' => 0,
            'orderby' => 'updated_at',
            'order' => 'DESC',
            'search' => '',
            'skills' => '',
            'location' => '',
            'experience_level' => '',
            'job_type' => '',
            'remote_preference' => '',
            'availability' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array();
        $where_values = array();
        
        // Search filter
        if (!empty($args['search'])) {
            $where_clauses[] = "(first_name LIKE %s OR last_name LIKE %s OR bio LIKE %s OR skills LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        // Skills filter
        if (!empty($args['skills'])) {
            $where_clauses[] = "skills LIKE %s";
            $where_values[] = '%' . $wpdb->esc_like($args['skills']) . '%';
        }
        
        // Location filter
        if (!empty($args['location'])) {
            $where_clauses[] = "location LIKE %s";
            $where_values[] = '%' . $wpdb->esc_like($args['location']) . '%';
        }
        
        // Experience level filter
        if (!empty($args['experience_level'])) {
            $where_clauses[] = "experience_level = %s";
            $where_values[] = $args['experience_level'];
        }
        
        // Job type filter
        if (!empty($args['job_type'])) {
            $where_clauses[] = "desired_job_type = %s";
            $where_values[] = $args['job_type'];
        }
        
        // Remote preference filter
        if ($args['remote_preference'] !== '') {
            $where_clauses[] = "remote_preference = %d";
            $where_values[] = (int)$args['remote_preference'];
        }
        
        // Availability filter
        if (!empty($args['availability'])) {
            $where_clauses[] = "availability = %s";
            $where_values[] = $args['availability'];
        }
        
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
     * Get profile count
     */
    public static function get_profile_count($args = array()) {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('user_profiles');
        
        $defaults = array(
            'search' => '',
            'skills' => '',
            'location' => '',
            'experience_level' => '',
            'job_type' => '',
            'remote_preference' => '',
            'availability' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array();
        $where_values = array();
        
        if (!empty($args['search'])) {
            $where_clauses[] = "(first_name LIKE %s OR last_name LIKE %s OR bio LIKE %s OR skills LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        if (!empty($args['skills'])) {
            $where_clauses[] = "skills LIKE %s";
            $where_values[] = '%' . $wpdb->esc_like($args['skills']) . '%';
        }
        
        if (!empty($args['location'])) {
            $where_clauses[] = "location LIKE %s";
            $where_values[] = '%' . $wpdb->esc_like($args['location']) . '%';
        }
        
        if (!empty($args['experience_level'])) {
            $where_clauses[] = "experience_level = %s";
            $where_values[] = $args['experience_level'];
        }
        
        if (!empty($args['job_type'])) {
            $where_clauses[] = "desired_job_type = %s";
            $where_values[] = $args['job_type'];
        }
        
        if ($args['remote_preference'] !== '') {
            $where_clauses[] = "remote_preference = %d";
            $where_values[] = (int)$args['remote_preference'];
        }
        
        if (!empty($args['availability'])) {
            $where_clauses[] = "availability = %s";
            $where_values[] = $args['availability'];
        }
        
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
     * Generate AI profile for better matching
     */
    private static function generate_ai_profile($profile_data) {
        $ai_profile = array();
        
        // Extract skills
        if (!empty($profile_data['skills'])) {
            $skills = explode(',', strtolower($profile_data['skills']));
            $ai_profile['skills'] = array_map('trim', $skills);
        }
        
        // Categorize skills
        $ai_profile['skill_categories'] = self::categorize_skills($ai_profile['skills'] ?? array());
        
        // Experience level
        if (!empty($profile_data['experience_level'])) {
            $ai_profile['experience_level'] = $profile_data['experience_level'];
        }
        
        // Location preferences
        $ai_profile['location'] = $profile_data['location'] ?? '';
        $ai_profile['remote_preference'] = (bool)($profile_data['remote_preference'] ?? false);
        
        // Job preferences
        $ai_profile['desired_job_type'] = $profile_data['desired_job_type'] ?? '';
        $ai_profile['availability'] = $profile_data['availability'] ?? 'immediately';
        
        // Salary expectations
        if (!empty($profile_data['desired_salary_min'])) {
            $ai_profile['salary_min'] = (float)$profile_data['desired_salary_min'];
        }
        if (!empty($profile_data['desired_salary_max'])) {
            $ai_profile['salary_max'] = (float)$profile_data['desired_salary_max'];
        }
        
        // Extract keywords from bio and experience
        $text_content = '';
        if (!empty($profile_data['bio'])) {
            $text_content .= $profile_data['bio'] . ' ';
        }
        if (!empty($profile_data['experience'])) {
            $text_content .= $profile_data['experience'] . ' ';
        }
        
        if (!empty($text_content)) {
            $ai_profile['keywords'] = self::extract_keywords($text_content);
        }
        
        return json_encode($ai_profile);
    }
    
    /**
     * Categorize skills into groups
     */
    private static function categorize_skills($skills) {
        $categories = array(
            'programming' => array('php', 'javascript', 'python', 'java', 'c++', 'c#', 'ruby', 'go', 'rust', 'swift'),
            'frontend' => array('html', 'css', 'react', 'vue', 'angular', 'jquery', 'bootstrap', 'tailwind'),
            'backend' => array('node.js', 'laravel', 'django', 'flask', 'express', 'spring', 'rails'),
            'database' => array('mysql', 'postgresql', 'mongodb', 'redis', 'sqlite', 'oracle'),
            'devops' => array('docker', 'kubernetes', 'aws', 'azure', 'gcp', 'jenkins', 'git', 'linux'),
            'design' => array('photoshop', 'illustrator', 'figma', 'sketch', 'ui/ux', 'graphic design'),
            'data' => array('machine learning', 'data science', 'analytics', 'sql', 'tableau', 'power bi'),
            'mobile' => array('ios', 'android', 'react native', 'flutter', 'xamarin'),
            'cms' => array('wordpress', 'drupal', 'joomla', 'shopify', 'magento')
        );
        
        $skill_categories = array();
        
        foreach ($skills as $skill) {
            foreach ($categories as $category => $category_skills) {
                if (in_array($skill, $category_skills)) {
                    if (!isset($skill_categories[$category])) {
                        $skill_categories[$category] = array();
                    }
                    $skill_categories[$category][] = $skill;
                }
            }
        }
        
        return $skill_categories;
    }
    
    /**
     * Extract keywords from text
     */
    private static function extract_keywords($text) {
        // Remove HTML tags and normalize text
        $text = wp_strip_all_tags($text);
        $text = strtolower($text);
        
        // Common professional keywords
        $keywords = array(
            'management', 'leadership', 'team lead', 'project management', 'agile', 'scrum',
            'communication', 'problem solving', 'analytical', 'creative', 'innovative',
            'startup', 'enterprise', 'consulting', 'freelance', 'remote work',
            'full stack', 'frontend', 'backend', 'devops', 'qa', 'testing'
        );
        
        $found_keywords = array();
        
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $found_keywords[] = $keyword;
            }
        }
        
        return array_unique($found_keywords);
    }
    
    /**
     * Get availability options
     */
    public static function get_availability_options() {
        return array(
            'immediately' => __('Immediately', 'ai-job-search-board'),
            '2weeks' => __('2 Weeks Notice', 'ai-job-search-board'),
            '1month' => __('1 Month Notice', 'ai-job-search-board'),
            '2months' => __('2 Months Notice', 'ai-job-search-board'),
            '3months' => __('3+ Months', 'ai-job-search-board'),
            'not_looking' => __('Not Currently Looking', 'ai-job-search-board')
        );
    }
    
    /**
     * Get experience levels
     */
    public static function get_experience_levels() {
        return AJSB_Job::get_experience_levels();
    }
    
    /**
     * Get job types
     */
    public static function get_job_types() {
        return AJSB_Job::get_job_types();
    }
}