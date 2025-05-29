<?php
/**
 * Database management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJSB_Database {
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Jobs table
        $jobs_table = $wpdb->prefix . 'ajsb_jobs';
        $jobs_sql = "CREATE TABLE $jobs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description longtext NOT NULL,
            company varchar(255) NOT NULL,
            location varchar(255) NOT NULL,
            job_type varchar(50) NOT NULL DEFAULT 'full-time',
            salary_min decimal(10,2) DEFAULT NULL,
            salary_max decimal(10,2) DEFAULT NULL,
            currency varchar(10) DEFAULT 'USD',
            requirements longtext,
            benefits longtext,
            skills longtext,
            experience_level varchar(50) DEFAULT 'entry',
            remote_work tinyint(1) DEFAULT 0,
            featured tinyint(1) DEFAULT 0,
            status varchar(20) DEFAULT 'active',
            employer_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            ai_tags longtext,
            PRIMARY KEY (id),
            KEY employer_id (employer_id),
            KEY status (status),
            KEY job_type (job_type),
            KEY location (location)
        ) $charset_collate;";
        
        // Applications table
        $applications_table = $wpdb->prefix . 'ajsb_applications';
        $applications_sql = "CREATE TABLE $applications_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            job_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            cover_letter longtext,
            resume_url varchar(500),
            status varchar(20) DEFAULT 'pending',
            ai_match_score decimal(5,2) DEFAULT NULL,
            applied_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            notes longtext,
            PRIMARY KEY (id),
            KEY job_id (job_id),
            KEY user_id (user_id),
            KEY status (status),
            UNIQUE KEY unique_application (job_id, user_id)
        ) $charset_collate;";
        
        // User profiles table
        $profiles_table = $wpdb->prefix . 'ajsb_user_profiles';
        $profiles_sql = "CREATE TABLE $profiles_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            first_name varchar(100),
            last_name varchar(100),
            phone varchar(20),
            location varchar(255),
            bio longtext,
            skills longtext,
            experience longtext,
            education longtext,
            resume_url varchar(500),
            portfolio_url varchar(500),
            linkedin_url varchar(500),
            github_url varchar(500),
            desired_salary_min decimal(10,2),
            desired_salary_max decimal(10,2),
            desired_job_type varchar(50),
            experience_level varchar(50) DEFAULT 'entry',
            remote_preference tinyint(1) DEFAULT 0,
            availability varchar(50) DEFAULT 'immediately',
            ai_profile longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        
        // Job views table for analytics
        $views_table = $wpdb->prefix . 'ajsb_job_views';
        $views_sql = "CREATE TABLE $views_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            job_id bigint(20) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            ip_address varchar(45),
            user_agent varchar(500),
            viewed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY job_id (job_id),
            KEY user_id (user_id),
            KEY viewed_at (viewed_at)
        ) $charset_collate;";
        
        // AI recommendations table
        $recommendations_table = $wpdb->prefix . 'ajsb_ai_recommendations';
        $recommendations_sql = "CREATE TABLE $recommendations_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            job_id bigint(20) NOT NULL,
            match_score decimal(5,2) NOT NULL,
            reasons longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY job_id (job_id),
            KEY match_score (match_score),
            UNIQUE KEY unique_recommendation (user_id, job_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($jobs_sql);
        dbDelta($applications_sql);
        dbDelta($profiles_sql);
        dbDelta($views_sql);
        dbDelta($recommendations_sql);
        
        // Update existing tables if needed
        self::update_tables();
    }
    
    /**
     * Update existing tables to add missing columns
     */
    public static function update_tables() {
        global $wpdb;
        
        // Check if experience_level column exists in user profiles table
        $profiles_table = $wpdb->prefix . 'ajsb_user_profiles';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $profiles_table LIKE 'experience_level'");
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $profiles_table ADD COLUMN experience_level varchar(50) DEFAULT 'entry' AFTER desired_job_type");
        }
    }
    
    /**
     * Drop database tables
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'ajsb_jobs',
            $wpdb->prefix . 'ajsb_applications',
            $wpdb->prefix . 'ajsb_user_profiles',
            $wpdb->prefix . 'ajsb_job_views',
            $wpdb->prefix . 'ajsb_ai_recommendations'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    /**
     * Get table name with prefix
     */
    public static function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . 'ajsb_' . $table;
    }
}