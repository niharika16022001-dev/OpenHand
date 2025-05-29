<?php
/**
 * Plugin Name: AI Job Search Board
 * Plugin URI: https://github.com/niharika16022001-dev/OpenHand
 * Description: An intelligent job search board powered by AI that matches candidates with relevant job opportunities.
 * Version: 1.0.0
 * Author: OpenHand Team
 * Author URI: https://github.com/niharika16022001-dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-job-search-board
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AJSB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AJSB_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AJSB_PLUGIN_VERSION', '1.0.0');
define('AJSB_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main AI Job Search Board Class
 */
class AI_Job_Search_Board {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Include required files early for activation hooks
        $this->includes();
        
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('AI_Job_Search_Board', 'uninstall'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('ai-job-search-board', false, dirname(AJSB_PLUGIN_BASENAME) . '/languages');
        
        // Initialize components
        $this->init_hooks();
        
        // Initialize admin if in admin area
        if (is_admin()) {
            new AJSB_Admin();
        }
        
        // Initialize frontend
        new AJSB_Frontend();
        
        // Initialize AJAX handlers
        new AJSB_Ajax();
        
        // Initialize AI engine
        new AJSB_AI_Engine();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        $files = array(
            'includes/class-ajsb-database.php',
            'includes/class-ajsb-job.php',
            'includes/class-ajsb-application.php',
            'includes/class-ajsb-user-profile.php',
            'includes/class-ajsb-ai-engine.php',
            'includes/class-ajsb-ajax.php',
            'admin/class-ajsb-admin.php',
            'public/class-ajsb-frontend.php',
            'includes/class-ajsb-shortcodes.php'
        );
        
        foreach ($files as $file) {
            $file_path = AJSB_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Register custom post types (must be done on init, not just in admin)
        add_action('init', array($this, 'register_post_types'));
        
        // Initialize shortcodes
        new AJSB_Shortcodes();
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style('ajsb-frontend-style', AJSB_PLUGIN_URL . 'assets/css/frontend.css', array(), AJSB_PLUGIN_VERSION);
        wp_enqueue_script('ajsb-frontend-script', AJSB_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), AJSB_PLUGIN_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('ajsb-frontend-script', 'ajsb_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ajsb_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'ai-job-search-board'),
                'error' => __('An error occurred. Please try again.', 'ai-job-search-board'),
                'success' => __('Success!', 'ai-job-search-board')
            )
        ));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'ajsb') !== false) {
            wp_enqueue_style('ajsb-admin-style', AJSB_PLUGIN_URL . 'assets/css/admin.css', array(), AJSB_PLUGIN_VERSION);
            wp_enqueue_script('ajsb-admin-script', AJSB_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), AJSB_PLUGIN_VERSION, true);
            
            wp_localize_script('ajsb-admin-script', 'ajsb_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ajsb_admin_nonce')
            ));
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        AJSB_Database::create_tables();
        
        // Create default pages
        $this->create_default_pages();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Include database class for uninstall
        require_once AJSB_PLUGIN_PATH . 'includes/class-ajsb-database.php';
        
        // Remove database tables
        AJSB_Database::drop_tables();
        
        // Remove options
        delete_option('ajsb_settings');
        delete_option('ajsb_version');
    }
    
    /**
     * Create default pages
     */
    private function create_default_pages() {
        $pages = array(
            'jobs' => array(
                'title' => __('Jobs', 'ai-job-search-board'),
                'content' => '[ajsb_job_listings]'
            ),
            'job-search' => array(
                'title' => __('Job Search', 'ai-job-search-board'),
                'content' => '[ajsb_job_search]'
            ),
            'my-profile' => array(
                'title' => __('My Profile', 'ai-job-search-board'),
                'content' => '[ajsb_user_profile]'
            ),
            'my-applications' => array(
                'title' => __('My Applications', 'ai-job-search-board'),
                'content' => '[ajsb_user_applications]'
            )
        );
        
        foreach ($pages as $slug => $page) {
            $existing_page = get_page_by_path($slug);
            if (!$existing_page) {
                wp_insert_post(array(
                    'post_title' => $page['title'],
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug
                ));
            }
        }
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $default_settings = array(
            'jobs_per_page' => 10,
            'enable_ai_matching' => true,
            'require_registration' => false,
            'email_notifications' => true,
            'ai_api_key' => '',
            'ai_provider' => 'openai'
        );
        
        add_option('ajsb_settings', $default_settings);
        add_option('ajsb_version', AJSB_PLUGIN_VERSION);
        add_option('ajsb_flush_rewrite_rules', true);
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Register job post type
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
            'show_in_menu' => true, // Show in main menu (will be moved to custom menu by admin class)
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
        
        // Flush rewrite rules if this is a fresh activation
        if (get_option('ajsb_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('ajsb_flush_rewrite_rules');
        }
    }
}

// Initialize the plugin
AI_Job_Search_Board::get_instance();