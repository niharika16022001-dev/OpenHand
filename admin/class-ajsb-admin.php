<?php
/**
 * Admin interface class
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJSB_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_job_meta'));
        
        // Add custom post type for jobs
        add_action('init', array($this, 'register_job_post_type'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('AI Job Board', 'ai-job-search-board'),
            __('AI Job Board', 'ai-job-search-board'),
            'manage_options',
            'ajsb-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-businessman',
            30
        );
        
        add_submenu_page(
            'ajsb-dashboard',
            __('Dashboard', 'ai-job-search-board'),
            __('Dashboard', 'ai-job-search-board'),
            'manage_options',
            'ajsb-dashboard',
            array($this, 'dashboard_page')
        );
        
        // Jobs submenu is automatically added by the custom post type registration
        
        add_submenu_page(
            'ajsb-dashboard',
            __('Applications', 'ai-job-search-board'),
            __('Applications', 'ai-job-search-board'),
            'manage_options',
            'ajsb-applications',
            array($this, 'applications_page')
        );
        
        add_submenu_page(
            'ajsb-dashboard',
            __('Candidates', 'ai-job-search-board'),
            __('Candidates', 'ai-job-search-board'),
            'manage_options',
            'ajsb-candidates',
            array($this, 'candidates_page')
        );
        
        add_submenu_page(
            'ajsb-dashboard',
            __('Analytics', 'ai-job-search-board'),
            __('Analytics', 'ai-job-search-board'),
            'manage_options',
            'ajsb-analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'ajsb-dashboard',
            __('Settings', 'ai-job-search-board'),
            __('Settings', 'ai-job-search-board'),
            'manage_options',
            'ajsb-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'ajsb-dashboard',
            __('Installation', 'ai-job-search-board'),
            __('Installation', 'ai-job-search-board'),
            'manage_options',
            'ajsb-installation',
            array($this, 'installation_page')
        );
    }
    
    /**
     * Initialize admin
     */
    public function admin_init() {
        register_setting('ajsb_settings', 'ajsb_settings', array($this, 'sanitize_settings'));
        
        // Add settings sections and fields
        add_settings_section(
            'ajsb_general_settings',
            __('General Settings', 'ai-job-search-board'),
            null,
            'ajsb_settings'
        );
        
        add_settings_field(
            'jobs_per_page',
            __('Jobs per Page', 'ai-job-search-board'),
            array($this, 'jobs_per_page_callback'),
            'ajsb_settings',
            'ajsb_general_settings'
        );
        
        add_settings_field(
            'require_registration',
            __('Require Registration to Apply', 'ai-job-search-board'),
            array($this, 'require_registration_callback'),
            'ajsb_settings',
            'ajsb_general_settings'
        );
        
        add_settings_field(
            'email_notifications',
            __('Email Notifications', 'ai-job-search-board'),
            array($this, 'email_notifications_callback'),
            'ajsb_settings',
            'ajsb_general_settings'
        );
        
        // AI Settings
        add_settings_section(
            'ajsb_ai_settings',
            __('AI Settings', 'ai-job-search-board'),
            array($this, 'ai_settings_section_callback'),
            'ajsb_settings'
        );
        
        add_settings_field(
            'enable_ai_matching',
            __('Enable AI Matching', 'ai-job-search-board'),
            array($this, 'enable_ai_matching_callback'),
            'ajsb_settings',
            'ajsb_ai_settings'
        );
        
        add_settings_field(
            'ai_provider',
            __('AI Provider', 'ai-job-search-board'),
            array($this, 'ai_provider_callback'),
            'ajsb_settings',
            'ajsb_ai_settings'
        );
        
        add_settings_field(
            'ai_api_key',
            __('AI API Key', 'ai-job-search-board'),
            array($this, 'ai_api_key_callback'),
            'ajsb_settings',
            'ajsb_ai_settings'
        );
    }
    
    /**
     * Register job post type
     */
    public function register_job_post_type() {
        $labels = array(
            'name' => __('Jobs', 'ai-job-search-board'),
            'singular_name' => __('Job', 'ai-job-search-board'),
            'menu_name' => __('Jobs', 'ai-job-search-board'),
            'add_new' => __('Add New Job', 'ai-job-search-board'),
            'add_new_item' => __('Add New Job', 'ai-job-search-board'),
            'edit_item' => __('Edit Job', 'ai-job-search-board'),
            'new_item' => __('New Job', 'ai-job-search-board'),
            'view_item' => __('View Job', 'ai-job-search-board'),
            'search_items' => __('Search Jobs', 'ai-job-search-board'),
            'not_found' => __('No jobs found', 'ai-job-search-board'),
            'not_found_in_trash' => __('No jobs found in trash', 'ai-job-search-board')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'ajsb-dashboard', // Show under our custom menu
            'query_var' => true,
            'rewrite' => array('slug' => 'job'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt'),
            'show_in_rest' => true
        );
        
        register_post_type('ajsb_job', $args);
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'ajsb_job_details',
            __('Job Details', 'ai-job-search-board'),
            array($this, 'job_details_meta_box'),
            'ajsb_job',
            'normal',
            'high'
        );
    }
    
    /**
     * Job details meta box
     */
    public function job_details_meta_box($post) {
        wp_nonce_field('ajsb_job_meta_nonce', 'ajsb_job_meta_nonce');
        
        $company = get_post_meta($post->ID, '_ajsb_company', true);
        $location = get_post_meta($post->ID, '_ajsb_location', true);
        $job_type = get_post_meta($post->ID, '_ajsb_job_type', true);
        $experience_level = get_post_meta($post->ID, '_ajsb_experience_level', true);
        $salary_min = get_post_meta($post->ID, '_ajsb_salary_min', true);
        $salary_max = get_post_meta($post->ID, '_ajsb_salary_max', true);
        $currency = get_post_meta($post->ID, '_ajsb_currency', true) ?: 'USD';
        $remote_work = get_post_meta($post->ID, '_ajsb_remote_work', true);
        $featured = get_post_meta($post->ID, '_ajsb_featured', true);
        $skills = get_post_meta($post->ID, '_ajsb_skills', true);
        $requirements = get_post_meta($post->ID, '_ajsb_requirements', true);
        $benefits = get_post_meta($post->ID, '_ajsb_benefits', true);
        $expires_at = get_post_meta($post->ID, '_ajsb_expires_at', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="ajsb_company"><?php _e('Company', 'ai-job-search-board'); ?></label></th>
                <td><input type="text" id="ajsb_company" name="ajsb_company" value="<?php echo esc_attr($company); ?>" class="regular-text" required /></td>
            </tr>
            <tr>
                <th><label for="ajsb_location"><?php _e('Location', 'ai-job-search-board'); ?></label></th>
                <td><input type="text" id="ajsb_location" name="ajsb_location" value="<?php echo esc_attr($location); ?>" class="regular-text" required /></td>
            </tr>
            <tr>
                <th><label for="ajsb_job_type"><?php _e('Job Type', 'ai-job-search-board'); ?></label></th>
                <td>
                    <select id="ajsb_job_type" name="ajsb_job_type" required>
                        <?php foreach (AJSB_Job::get_job_types() as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($job_type, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ajsb_experience_level"><?php _e('Experience Level', 'ai-job-search-board'); ?></label></th>
                <td>
                    <select id="ajsb_experience_level" name="ajsb_experience_level" required>
                        <?php foreach (AJSB_Job::get_experience_levels() as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($experience_level, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ajsb_salary_min"><?php _e('Salary Range', 'ai-job-search-board'); ?></label></th>
                <td>
                    <select name="ajsb_currency" style="width: 80px;">
                        <option value="USD" <?php selected($currency, 'USD'); ?>>USD</option>
                        <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR</option>
                        <option value="GBP" <?php selected($currency, 'GBP'); ?>>GBP</option>
                        <option value="CAD" <?php selected($currency, 'CAD'); ?>>CAD</option>
                    </select>
                    <input type="number" id="ajsb_salary_min" name="ajsb_salary_min" value="<?php echo esc_attr($salary_min); ?>" placeholder="Min" style="width: 100px;" />
                    -
                    <input type="number" id="ajsb_salary_max" name="ajsb_salary_max" value="<?php echo esc_attr($salary_max); ?>" placeholder="Max" style="width: 100px;" />
                    <p class="description"><?php _e('Annual salary range (optional)', 'ai-job-search-board'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="ajsb_skills"><?php _e('Required Skills', 'ai-job-search-board'); ?></label></th>
                <td>
                    <textarea id="ajsb_skills" name="ajsb_skills" rows="3" class="large-text"><?php echo esc_textarea($skills); ?></textarea>
                    <p class="description"><?php _e('Comma-separated list of required skills', 'ai-job-search-board'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="ajsb_requirements"><?php _e('Requirements', 'ai-job-search-board'); ?></label></th>
                <td>
                    <textarea id="ajsb_requirements" name="ajsb_requirements" rows="5" class="large-text"><?php echo esc_textarea($requirements); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="ajsb_benefits"><?php _e('Benefits', 'ai-job-search-board'); ?></label></th>
                <td>
                    <textarea id="ajsb_benefits" name="ajsb_benefits" rows="5" class="large-text"><?php echo esc_textarea($benefits); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="ajsb_expires_at"><?php _e('Expires On', 'ai-job-search-board'); ?></label></th>
                <td>
                    <input type="date" id="ajsb_expires_at" name="ajsb_expires_at" value="<?php echo esc_attr($expires_at); ?>" />
                    <p class="description"><?php _e('Leave empty for no expiration', 'ai-job-search-board'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php _e('Options', 'ai-job-search-board'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="ajsb_remote_work" value="1" <?php checked($remote_work, 1); ?> />
                        <?php _e('Remote Work Available', 'ai-job-search-board'); ?>
                    </label>
                    <br />
                    <label>
                        <input type="checkbox" name="ajsb_featured" value="1" <?php checked($featured, 1); ?> />
                        <?php _e('Featured Job', 'ai-job-search-board'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save job meta
     */
    public function save_job_meta($post_id) {
        if (!isset($_POST['ajsb_job_meta_nonce']) || !wp_verify_nonce($_POST['ajsb_job_meta_nonce'], 'ajsb_job_meta_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) !== 'ajsb_job') {
            return;
        }
        
        // Save meta fields
        $fields = array(
            'ajsb_company',
            'ajsb_location',
            'ajsb_job_type',
            'ajsb_experience_level',
            'ajsb_salary_min',
            'ajsb_salary_max',
            'ajsb_currency',
            'ajsb_skills',
            'ajsb_requirements',
            'ajsb_benefits',
            'ajsb_expires_at'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Handle checkboxes
        update_post_meta($post_id, '_ajsb_remote_work', isset($_POST['ajsb_remote_work']) ? 1 : 0);
        update_post_meta($post_id, '_ajsb_featured', isset($_POST['ajsb_featured']) ? 1 : 0);
        
        // Sync with custom table
        $this->sync_job_to_custom_table($post_id);
    }
    
    /**
     * Sync job post to custom table
     */
    private function sync_job_to_custom_table($post_id) {
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'ajsb_job') {
            return;
        }
        
        $job_data = array(
            'title' => $post->post_title,
            'description' => $post->post_content,
            'company' => get_post_meta($post_id, '_ajsb_company', true),
            'location' => get_post_meta($post_id, '_ajsb_location', true),
            'job_type' => get_post_meta($post_id, '_ajsb_job_type', true),
            'experience_level' => get_post_meta($post_id, '_ajsb_experience_level', true),
            'salary_min' => get_post_meta($post_id, '_ajsb_salary_min', true),
            'salary_max' => get_post_meta($post_id, '_ajsb_salary_max', true),
            'currency' => get_post_meta($post_id, '_ajsb_currency', true),
            'skills' => get_post_meta($post_id, '_ajsb_skills', true),
            'requirements' => get_post_meta($post_id, '_ajsb_requirements', true),
            'benefits' => get_post_meta($post_id, '_ajsb_benefits', true),
            'remote_work' => get_post_meta($post_id, '_ajsb_remote_work', true),
            'featured' => get_post_meta($post_id, '_ajsb_featured', true),
            'expires_at' => get_post_meta($post_id, '_ajsb_expires_at', true),
            'employer_id' => $post->post_author,
            'status' => $post->post_status === 'publish' ? 'active' : 'draft'
        );
        
        // Check if job exists in custom table
        global $wpdb;
        $table = AJSB_Database::get_table_name('jobs');
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE id = %d", $post_id));
        
        if ($existing) {
            AJSB_Job::update($post_id, $job_data);
        } else {
            $job_data['id'] = $post_id;
            $wpdb->insert($table, $job_data);
        }
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        global $wpdb;
        
        // Get statistics
        $jobs_table = AJSB_Database::get_table_name('jobs');
        $applications_table = AJSB_Database::get_table_name('applications');
        $profiles_table = AJSB_Database::get_table_name('user_profiles');
        
        $total_jobs = $wpdb->get_var("SELECT COUNT(*) FROM $jobs_table WHERE status = 'active'");
        $total_applications = $wpdb->get_var("SELECT COUNT(*) FROM $applications_table");
        $total_candidates = $wpdb->get_var("SELECT COUNT(*) FROM $profiles_table");
        $pending_applications = $wpdb->get_var("SELECT COUNT(*) FROM $applications_table WHERE status = 'pending'");
        
        ?>
        <div class="wrap">
            <h1><?php _e('AI Job Board Dashboard', 'ai-job-search-board'); ?></h1>
            
            <div class="ajsb-dashboard-stats">
                <div class="ajsb-stat-card">
                    <h3><?php echo number_format($total_jobs); ?></h3>
                    <p><?php _e('Active Jobs', 'ai-job-search-board'); ?></p>
                </div>
                <div class="ajsb-stat-card">
                    <h3><?php echo number_format($total_applications); ?></h3>
                    <p><?php _e('Total Applications', 'ai-job-search-board'); ?></p>
                </div>
                <div class="ajsb-stat-card">
                    <h3><?php echo number_format($total_candidates); ?></h3>
                    <p><?php _e('Registered Candidates', 'ai-job-search-board'); ?></p>
                </div>
                <div class="ajsb-stat-card">
                    <h3><?php echo number_format($pending_applications); ?></h3>
                    <p><?php _e('Pending Applications', 'ai-job-search-board'); ?></p>
                </div>
            </div>
            
            <div class="ajsb-dashboard-content">
                <div class="ajsb-recent-activity">
                    <h2><?php _e('Recent Activity', 'ai-job-search-board'); ?></h2>
                    <?php $this->render_recent_activity(); ?>
                </div>
            </div>
        </div>
        
        <style>
        .ajsb-dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .ajsb-stat-card {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }
        .ajsb-stat-card h3 {
            font-size: 2em;
            margin: 0;
            color: #0073aa;
        }
        .ajsb-stat-card p {
            margin: 10px 0 0;
            color: #666;
        }
        </style>
        <?php
    }
    
    /**
     * Jobs page
     */
    public function jobs_page() {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'add':
                $this->render_add_job_form();
                break;
            case 'edit':
                $this->render_edit_job_form();
                break;
            default:
                $this->render_jobs_list();
                break;
        }
    }
    
    /**
     * Applications page
     */
    public function applications_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Job Applications', 'ai-job-search-board'); ?></h1>
            
            <div id="ajsb-applications-list">
                <?php $this->render_applications_list(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Candidates page
     */
    public function candidates_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Candidates', 'ai-job-search-board'); ?></h1>
            
            <div id="ajsb-candidates-list">
                <?php $this->render_candidates_list(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Analytics page
     */
    public function analytics_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Analytics', 'ai-job-search-board'); ?></h1>
            
            <div id="ajsb-analytics-content">
                <div class="ajsb-loading"><?php _e('Loading analytics...', 'ai-job-search-board'); ?></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Load analytics data via AJAX
            $.post(ajaxurl, {
                action: 'ajsb_admin_get_analytics',
                nonce: '<?php echo wp_create_nonce('ajsb_admin_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    // Render analytics charts and data
                    $('#ajsb-analytics-content').html('<p>Analytics data loaded successfully!</p>');
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('AI Job Board Settings', 'ai-job-search-board'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('ajsb_settings');
                do_settings_sections('ajsb_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    // Settings callbacks
    public function jobs_per_page_callback() {
        $settings = get_option('ajsb_settings', array());
        $value = $settings['jobs_per_page'] ?? 10;
        echo '<input type="number" name="ajsb_settings[jobs_per_page]" value="' . esc_attr($value) . '" min="1" max="50" />';
    }
    
    public function require_registration_callback() {
        $settings = get_option('ajsb_settings', array());
        $value = $settings['require_registration'] ?? false;
        echo '<input type="checkbox" name="ajsb_settings[require_registration]" value="1" ' . checked($value, true, false) . ' />';
    }
    
    public function email_notifications_callback() {
        $settings = get_option('ajsb_settings', array());
        $value = $settings['email_notifications'] ?? true;
        echo '<input type="checkbox" name="ajsb_settings[email_notifications]" value="1" ' . checked($value, true, false) . ' />';
    }
    
    public function ai_settings_section_callback() {
        echo '<p>' . __('Configure AI-powered features for better job matching.', 'ai-job-search-board') . '</p>';
    }
    
    public function enable_ai_matching_callback() {
        $settings = get_option('ajsb_settings', array());
        $value = $settings['enable_ai_matching'] ?? true;
        echo '<input type="checkbox" name="ajsb_settings[enable_ai_matching]" value="1" ' . checked($value, true, false) . ' />';
    }
    
    public function ai_provider_callback() {
        $settings = get_option('ajsb_settings', array());
        $value = $settings['ai_provider'] ?? 'openai';
        ?>
        <select name="ajsb_settings[ai_provider]">
            <option value="openai" <?php selected($value, 'openai'); ?>>OpenAI</option>
            <option value="custom" <?php selected($value, 'custom'); ?>>Custom API</option>
        </select>
        <?php
    }
    
    public function ai_api_key_callback() {
        $settings = get_option('ajsb_settings', array());
        $value = $settings['ai_api_key'] ?? '';
        echo '<input type="password" name="ajsb_settings[ai_api_key]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter your AI provider API key for enhanced matching features.', 'ai-job-search-board') . '</p>';
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['jobs_per_page'] = max(1, min(50, (int)($input['jobs_per_page'] ?? 10)));
        $sanitized['require_registration'] = !empty($input['require_registration']);
        $sanitized['email_notifications'] = !empty($input['email_notifications']);
        $sanitized['enable_ai_matching'] = !empty($input['enable_ai_matching']);
        $sanitized['ai_provider'] = sanitize_text_field($input['ai_provider'] ?? 'openai');
        $sanitized['ai_api_key'] = sanitize_text_field($input['ai_api_key'] ?? '');
        
        return $sanitized;
    }
    
    /**
     * Render recent activity
     */
    private function render_recent_activity() {
        global $wpdb;
        
        $applications_table = AJSB_Database::get_table_name('applications');
        $jobs_table = AJSB_Database::get_table_name('jobs');
        
        $recent_applications = $wpdb->get_results(
            "SELECT a.*, j.title as job_title, u.display_name 
             FROM $applications_table a 
             LEFT JOIN $jobs_table j ON a.job_id = j.id 
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
             ORDER BY a.applied_at DESC 
             LIMIT 10"
        );
        
        if (empty($recent_applications)) {
            echo '<p>' . __('No recent activity.', 'ai-job-search-board') . '</p>';
            return;
        }
        
        echo '<ul>';
        foreach ($recent_applications as $app) {
            echo '<li>';
            echo sprintf(
                __('%s applied for %s - %s ago', 'ai-job-search-board'),
                esc_html($app->display_name),
                esc_html($app->job_title),
                human_time_diff(strtotime($app->applied_at))
            );
            echo '</li>';
        }
        echo '</ul>';
    }
    
    /**
     * Render jobs list
     */
    private function render_jobs_list() {
        $jobs = AJSB_Job::get_jobs(array('limit' => 50));
        
        ?>
        <div class="wrap">
            <h1>
                <?php _e('Jobs', 'ai-job-search-board'); ?>
                <a href="<?php echo admin_url('post-new.php?post_type=ajsb_job'); ?>" class="page-title-action">
                    <?php _e('Add New', 'ai-job-search-board'); ?>
                </a>
            </h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Title', 'ai-job-search-board'); ?></th>
                        <th><?php _e('Company', 'ai-job-search-board'); ?></th>
                        <th><?php _e('Location', 'ai-job-search-board'); ?></th>
                        <th><?php _e('Type', 'ai-job-search-board'); ?></th>
                        <th><?php _e('Applications', 'ai-job-search-board'); ?></th>
                        <th><?php _e('Status', 'ai-job-search-board'); ?></th>
                        <th><?php _e('Date', 'ai-job-search-board'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="<?php echo admin_url('post.php?post=' . $job->id . '&action=edit'); ?>">
                                        <?php echo esc_html($job->title); ?>
                                    </a>
                                </strong>
                            </td>
                            <td><?php echo esc_html($job->company); ?></td>
                            <td><?php echo esc_html($job->location); ?></td>
                            <td><?php echo esc_html($job->job_type); ?></td>
                            <td>
                                <?php
                                $app_count = AJSB_Application::get_application_count(array('job_id' => $job->id));
                                echo number_format($app_count);
                                ?>
                            </td>
                            <td><?php echo esc_html($job->status); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($job->created_at)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render applications list
     */
    private function render_applications_list() {
        $applications = AJSB_Application::get_applications(array('limit' => 50));
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Applicant', 'ai-job-search-board'); ?></th>
                    <th><?php _e('Job', 'ai-job-search-board'); ?></th>
                    <th><?php _e('Company', 'ai-job-search-board'); ?></th>
                    <th><?php _e('Match Score', 'ai-job-search-board'); ?></th>
                    <th><?php _e('Status', 'ai-job-search-board'); ?></th>
                    <th><?php _e('Applied', 'ai-job-search-board'); ?></th>
                    <th><?php _e('Actions', 'ai-job-search-board'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                    <?php $user = get_user_by('id', $app->user_id); ?>
                    <tr>
                        <td><?php echo $user ? esc_html($user->display_name) : __('Unknown', 'ai-job-search-board'); ?></td>
                        <td><?php echo esc_html($app->job_title); ?></td>
                        <td><?php echo esc_html($app->company); ?></td>
                        <td>
                            <?php if ($app->ai_match_score): ?>
                                <?php echo round($app->ai_match_score); ?>%
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <select class="ajsb-status-select" data-application-id="<?php echo esc_attr($app->id); ?>">
                                <?php foreach (AJSB_Application::get_statuses() as $status => $label): ?>
                                    <option value="<?php echo esc_attr($status); ?>" <?php selected($app->status, $status); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><?php echo date('Y-m-d', strtotime($app->applied_at)); ?></td>
                        <td>
                            <button class="button ajsb-view-application" data-application-id="<?php echo esc_attr($app->id); ?>">
                                <?php _e('View', 'ai-job-search-board'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Render candidates list
     */
    private function render_candidates_list() {
        $profiles = AJSB_User_Profile::search_profiles(array('limit' => 50));
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Name', 'ai-job-search-board'); ?></th>
                    <th><?php _e('Location', 'ai-job-search-board'); ?></th>
                    <th><?php _e('Skills', 'ai-job-search-board'); ?></th>
                    <th><?php _e('Experience', 'ai-job-search-board'); ?></th>
                    <th><?php _e('Availability', 'ai-job-search-board'); ?></th>
                    <th><?php _e('Updated', 'ai-job-search-board'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profiles as $profile): ?>
                    <tr>
                        <td>
                            <?php echo esc_html($profile->first_name . ' ' . $profile->last_name); ?>
                        </td>
                        <td><?php echo esc_html($profile->location); ?></td>
                        <td>
                            <?php
                            if ($profile->skills) {
                                $skills = explode(',', $profile->skills);
                                echo esc_html(implode(', ', array_slice($skills, 0, 3)));
                                if (count($skills) > 3) {
                                    echo ' +' . (count($skills) - 3);
                                }
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html($profile->experience_level); ?></td>
                        <td><?php echo esc_html($profile->availability); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($profile->updated_at)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Installation page
     */
    public function installation_page() {
        // Include the installer class
        if (!class_exists('AJSB_Installer')) {
            require_once AJSB_PLUGIN_PATH . 'install.php';
        }
        
        if (isset($_POST['install_demo_data']) && wp_verify_nonce($_POST['ajsb_demo_nonce'], 'ajsb_demo_data')) {
            AJSB_Installer::install_demo_data();
            echo '<div class="notice notice-success"><p>' . __('Demo data installed successfully!', 'ai-job-search-board') . '</p></div>';
        }
        
        if (isset($_POST['remove_demo_data']) && wp_verify_nonce($_POST['ajsb_demo_nonce'], 'ajsb_demo_data')) {
            AJSB_Installer::remove_demo_data();
            echo '<div class="notice notice-success"><p>' . __('Demo data removed successfully!', 'ai-job-search-board') . '</p></div>';
        }
        
        $requirements = AJSB_Installer::check_requirements();
        $status = AJSB_Installer::get_installation_status();
        
        ?>
        <div class="wrap">
            <h1><?php _e('AI Job Search Board - Installation', 'ai-job-search-board'); ?></h1>
            
            <div class="ajsb-installation-grid">
                <div class="ajsb-installation-card">
                    <h2><?php _e('System Requirements', 'ai-job-search-board'); ?></h2>
                    <ul class="ajsb-requirements-list">
                        <li class="<?php echo $requirements['php_version'] ? 'ajsb-req-pass' : 'ajsb-req-fail'; ?>">
                            PHP 7.4+ (Current: <?php echo PHP_VERSION; ?>)
                        </li>
                        <li class="<?php echo $requirements['wordpress_version'] ? 'ajsb-req-pass' : 'ajsb-req-fail'; ?>">
                            WordPress 5.0+ (Current: <?php echo get_bloginfo('version'); ?>)
                        </li>
                        <li class="<?php echo $requirements['curl_extension'] ? 'ajsb-req-pass' : 'ajsb-req-fail'; ?>">
                            cURL Extension
                        </li>
                        <li class="<?php echo $requirements['json_extension'] ? 'ajsb-req-pass' : 'ajsb-req-fail'; ?>">
                            JSON Extension
                        </li>
                    </ul>
                </div>
                
                <div class="ajsb-installation-card">
                    <h2><?php _e('Installation Status', 'ai-job-search-board'); ?></h2>
                    <ul class="ajsb-status-list">
                        <li class="<?php echo $status['tables_created'] ? 'ajsb-status-complete' : 'ajsb-status-pending'; ?>">
                            Database Tables
                        </li>
                        <li class="<?php echo $status['pages_created'] ? 'ajsb-status-complete' : 'ajsb-status-pending'; ?>">
                            Default Pages
                        </li>
                        <li class="<?php echo $status['settings_configured'] ? 'ajsb-status-complete' : 'ajsb-status-pending'; ?>">
                            Settings Configured
                        </li>
                        <li class="<?php echo $status['demo_data_installed'] ? 'ajsb-status-complete' : 'ajsb-status-pending'; ?>">
                            Demo Data
                        </li>
                    </ul>
                </div>
                
                <div class="ajsb-installation-card">
                    <h2><?php _e('Demo Data', 'ai-job-search-board'); ?></h2>
                    <p><?php _e('Install sample jobs and data to test the plugin functionality.', 'ai-job-search-board'); ?></p>
                    
                    <form method="post" style="margin-bottom: 10px;">
                        <?php wp_nonce_field('ajsb_demo_data', 'ajsb_demo_nonce'); ?>
                        <input type="submit" name="install_demo_data" class="button button-primary" value="<?php esc_attr_e('Install Demo Data', 'ai-job-search-board'); ?>" />
                    </form>
                    
                    <form method="post">
                        <?php wp_nonce_field('ajsb_demo_data', 'ajsb_demo_nonce'); ?>
                        <input type="submit" name="remove_demo_data" class="button button-secondary" value="<?php esc_attr_e('Remove Demo Data', 'ai-job-search-board'); ?>" onclick="return confirm('Are you sure you want to remove all demo data?');" />
                    </form>
                </div>
                
                <div class="ajsb-installation-card">
                    <h2><?php _e('Quick Setup', 'ai-job-search-board'); ?></h2>
                    <ol>
                        <li><?php _e('Configure AI settings in Settings page', 'ai-job-search-board'); ?></li>
                        <li><?php _e('Add shortcodes to your pages:', 'ai-job-search-board'); ?></li>
                    </ol>
                    
                    <div class="ajsb-shortcode-examples">
                        <h4><?php _e('Shortcode Examples:', 'ai-job-search-board'); ?></h4>
                        <code>[ajsb_job_listings]</code><br>
                        <code>[ajsb_job_search]</code><br>
                        <code>[ajsb_user_profile]</code><br>
                        <code>[ajsb_ai_recommendations]</code>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .ajsb-installation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .ajsb-installation-card {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .ajsb-installation-card h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .ajsb-requirements-list,
        .ajsb-status-list {
            list-style: none;
            padding: 0;
        }
        .ajsb-requirements-list li,
        .ajsb-status-list li {
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .ajsb-req-pass::before,
        .ajsb-status-complete::before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
        }
        .ajsb-req-fail::before,
        .ajsb-status-pending::before {
            content: "✗ ";
            color: #dc3545;
            font-weight: bold;
        }
        .ajsb-shortcode-examples {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 3px;
            margin-top: 15px;
        }
        .ajsb-shortcode-examples code {
            display: block;
            margin: 5px 0;
            padding: 5px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        </style>
        <?php
    }
}