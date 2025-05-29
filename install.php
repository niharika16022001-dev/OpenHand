<?php
/**
 * Installation and Demo Data Script
 * 
 * This file provides installation helpers and demo data for the AI Job Search Board plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJSB_Installer {
    
    /**
     * Install demo data
     */
    public static function install_demo_data() {
        // Create demo jobs
        self::create_demo_jobs();
        
        // Create demo user profiles
        self::create_demo_profiles();
        
        // Create demo applications
        self::create_demo_applications();
        
        return true;
    }
    
    /**
     * Create demo jobs
     */
    private static function create_demo_jobs() {
        $demo_jobs = array(
            array(
                'title' => 'Senior Full Stack Developer',
                'description' => 'We are looking for an experienced Full Stack Developer to join our growing team. You will be responsible for developing and maintaining web applications using modern technologies.',
                'company' => 'TechCorp Inc.',
                'location' => 'San Francisco, CA',
                'job_type' => 'full-time',
                'experience_level' => 'senior',
                'salary_min' => 120000,
                'salary_max' => 160000,
                'currency' => 'USD',
                'skills' => 'JavaScript, React, Node.js, Python, PostgreSQL, AWS',
                'requirements' => '• 5+ years of experience in full stack development\n• Proficiency in React and Node.js\n• Experience with cloud platforms (AWS preferred)\n• Strong problem-solving skills',
                'benefits' => '• Competitive salary and equity\n• Health, dental, and vision insurance\n• Flexible work hours\n• Remote work options\n• Professional development budget',
                'remote_work' => 1,
                'featured' => 1,
                'employer_id' => 1,
                'status' => 'active'
            ),
            array(
                'title' => 'Frontend Developer',
                'description' => 'Join our creative team as a Frontend Developer and help build amazing user experiences. You will work closely with designers and backend developers to create responsive web applications.',
                'company' => 'Creative Agency',
                'location' => 'New York, NY',
                'job_type' => 'full-time',
                'experience_level' => 'mid',
                'salary_min' => 80000,
                'salary_max' => 110000,
                'currency' => 'USD',
                'skills' => 'HTML, CSS, JavaScript, Vue.js, Sass, Figma',
                'requirements' => '• 3+ years of frontend development experience\n• Expertise in Vue.js or similar framework\n• Strong CSS and responsive design skills\n• Experience with design tools',
                'benefits' => '• Creative work environment\n• Health insurance\n• Flexible PTO\n• Learning stipend\n• Team outings',
                'remote_work' => 0,
                'featured' => 0,
                'employer_id' => 1,
                'status' => 'active'
            ),
            array(
                'title' => 'Data Scientist',
                'description' => 'We are seeking a talented Data Scientist to analyze complex datasets and provide actionable insights. You will work with machine learning algorithms and statistical models.',
                'company' => 'DataTech Solutions',
                'location' => 'Austin, TX',
                'job_type' => 'full-time',
                'experience_level' => 'senior',
                'salary_min' => 130000,
                'salary_max' => 170000,
                'currency' => 'USD',
                'skills' => 'Python, R, SQL, Machine Learning, TensorFlow, Pandas, Jupyter',
                'requirements' => '• PhD or Masters in Data Science, Statistics, or related field\n• 4+ years of experience in data science\n• Proficiency in Python and R\n• Experience with ML frameworks',
                'benefits' => '• Competitive compensation\n• Stock options\n• Health benefits\n• Research time\n• Conference attendance',
                'remote_work' => 1,
                'featured' => 1,
                'employer_id' => 1,
                'status' => 'active'
            ),
            array(
                'title' => 'Junior Web Developer',
                'description' => 'Perfect opportunity for a recent graduate or career changer to start their web development journey. You will receive mentorship and work on real projects.',
                'company' => 'StartupXYZ',
                'location' => 'Remote',
                'job_type' => 'full-time',
                'experience_level' => 'entry',
                'salary_min' => 55000,
                'salary_max' => 70000,
                'currency' => 'USD',
                'skills' => 'HTML, CSS, JavaScript, Git, Basic React',
                'requirements' => '• Bachelor\'s degree in Computer Science or related field\n• Basic knowledge of web technologies\n• Eagerness to learn and grow\n• Good communication skills',
                'benefits' => '• Mentorship program\n• Health insurance\n• Flexible hours\n• Learning resources\n• Growth opportunities',
                'remote_work' => 1,
                'featured' => 0,
                'employer_id' => 1,
                'status' => 'active'
            ),
            array(
                'title' => 'DevOps Engineer',
                'description' => 'Join our infrastructure team as a DevOps Engineer. You will be responsible for maintaining and improving our CI/CD pipelines and cloud infrastructure.',
                'company' => 'CloudFirst Inc.',
                'location' => 'Seattle, WA',
                'job_type' => 'full-time',
                'experience_level' => 'mid',
                'salary_min' => 100000,
                'salary_max' => 140000,
                'currency' => 'USD',
                'skills' => 'Docker, Kubernetes, AWS, Jenkins, Terraform, Linux, Python',
                'requirements' => '• 3+ years of DevOps experience\n• Strong knowledge of containerization\n• Experience with cloud platforms\n• Scripting skills in Python or Bash',
                'benefits' => '• Competitive salary\n• Health benefits\n• 401k matching\n• Flexible work\n• Tech stipend',
                'remote_work' => 0,
                'featured' => 0,
                'employer_id' => 1,
                'status' => 'active'
            ),
            array(
                'title' => 'UX/UI Designer',
                'description' => 'We are looking for a creative UX/UI Designer to join our product team. You will design user interfaces and improve user experiences across our platforms.',
                'company' => 'Design Studio Pro',
                'location' => 'Los Angeles, CA',
                'job_type' => 'contract',
                'experience_level' => 'mid',
                'salary_min' => 75000,
                'salary_max' => 95000,
                'currency' => 'USD',
                'skills' => 'Figma, Sketch, Adobe Creative Suite, Prototyping, User Research',
                'requirements' => '• 3+ years of UX/UI design experience\n• Portfolio demonstrating design process\n• Proficiency in design tools\n• Understanding of user-centered design',
                'benefits' => '• Creative freedom\n• Flexible schedule\n• Health insurance\n• Design tool subscriptions\n• Portfolio development time',
                'remote_work' => 1,
                'featured' => 0,
                'employer_id' => 1,
                'status' => 'active'
            )
        );
        
        foreach ($demo_jobs as $job_data) {
            AJSB_Job::create($job_data);
        }
    }
    
    /**
     * Create demo user profiles
     */
    private static function create_demo_profiles() {
        // This would typically create demo users and their profiles
        // For security reasons, we'll skip actual user creation in this demo
        
        $demo_profiles = array(
            array(
                'user_id' => 2, // Assuming user ID 2 exists
                'first_name' => 'John',
                'last_name' => 'Developer',
                'phone' => '+1-555-0123',
                'location' => 'San Francisco, CA',
                'bio' => 'Passionate full stack developer with 5 years of experience building web applications. Love working with modern JavaScript frameworks and cloud technologies.',
                'skills' => 'JavaScript, React, Node.js, Python, AWS, PostgreSQL, Git',
                'experience' => 'Senior Developer at TechStart (2020-2024)\nFull Stack Developer at WebCorp (2018-2020)\nJunior Developer at StartupABC (2017-2018)',
                'education' => 'Bachelor of Science in Computer Science\nUniversity of California, Berkeley (2013-2017)',
                'desired_salary_min' => 120000,
                'desired_salary_max' => 150000,
                'desired_job_type' => 'full-time',
                'experience_level' => 'senior',
                'remote_preference' => 1,
                'availability' => 'immediately'
            )
        );
        
        foreach ($demo_profiles as $profile_data) {
            // Check if user exists before creating profile
            if (get_user_by('id', $profile_data['user_id'])) {
                AJSB_User_Profile::save($profile_data['user_id'], $profile_data);
            }
        }
    }
    
    /**
     * Create demo applications
     */
    private static function create_demo_applications() {
        // This would create demo applications
        // Skipped for demo purposes as it requires existing users
    }
    
    /**
     * Remove demo data
     */
    public static function remove_demo_data() {
        global $wpdb;
        
        $jobs_table = AJSB_Database::get_table_name('jobs');
        $applications_table = AJSB_Database::get_table_name('applications');
        $profiles_table = AJSB_Database::get_table_name('user_profiles');
        $views_table = AJSB_Database::get_table_name('job_views');
        $recommendations_table = AJSB_Database::get_table_name('ai_recommendations');
        
        // Remove demo jobs (assuming they have employer_id = 1)
        $wpdb->delete($jobs_table, array('employer_id' => 1));
        
        // Remove related applications
        $wpdb->query("DELETE FROM $applications_table WHERE job_id NOT IN (SELECT id FROM $jobs_table)");
        
        // Remove orphaned views
        $wpdb->query("DELETE FROM $views_table WHERE job_id NOT IN (SELECT id FROM $jobs_table)");
        
        // Remove orphaned recommendations
        $wpdb->query("DELETE FROM $recommendations_table WHERE job_id NOT IN (SELECT id FROM $jobs_table)");
        
        return true;
    }
    
    /**
     * Check system requirements
     */
    public static function check_requirements() {
        $requirements = array(
            'php_version' => version_compare(PHP_VERSION, '7.4', '>='),
            'wordpress_version' => version_compare(get_bloginfo('version'), '5.0', '>='),
            'curl_extension' => extension_loaded('curl'),
            'json_extension' => extension_loaded('json'),
            'mysql_version' => true // We'll assume MySQL is compatible
        );
        
        return $requirements;
    }
    
    /**
     * Get installation status
     */
    public static function get_installation_status() {
        global $wpdb;
        
        $jobs_table = AJSB_Database::get_table_name('jobs');
        
        $status = array(
            'tables_created' => $wpdb->get_var("SHOW TABLES LIKE '$jobs_table'") === $jobs_table,
            'demo_data_installed' => $wpdb->get_var("SELECT COUNT(*) FROM $jobs_table WHERE employer_id = 1") > 0,
            'pages_created' => get_page_by_path('jobs') !== null,
            'settings_configured' => get_option('ajsb_settings') !== false
        );
        
        return $status;
    }
}

// Add admin menu for installation helpers
add_action('admin_menu', function() {
    if (current_user_can('manage_options')) {
        add_submenu_page(
            'ajsb-dashboard',
            __('Installation', 'ai-job-search-board'),
            __('Installation', 'ai-job-search-board'),
            'manage_options',
            'ajsb-installation',
            'ajsb_installation_page'
        );
    }
});

// Installation page
function ajsb_installation_page() {
    if (isset($_POST['install_demo_data'])) {
        AJSB_Installer::install_demo_data();
        echo '<div class="notice notice-success"><p>' . __('Demo data installed successfully!', 'ai-job-search-board') . '</p></div>';
    }
    
    if (isset($_POST['remove_demo_data'])) {
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
                    <input type="submit" name="install_demo_data" class="button button-primary" value="<?php esc_attr_e('Install Demo Data', 'ai-job-search-board'); ?>" />
                </form>
                
                <form method="post">
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