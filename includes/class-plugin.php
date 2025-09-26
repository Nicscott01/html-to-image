<?php

namespace CSIG;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin {
    private static $instance = null;
    private $settings;
    private $admin;
    private $ajax;
    private $job_post_type;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        // Load other class files
        $this->load_dependencies();
        
        // Initialize components
        $this->settings = new Settings();
        $this->job_post_type = new Job_Post_Type();
        $this->admin = new Admin($this->settings);
        $this->ajax = new Ajax($this->settings);
        
        // Plugin hooks
        add_action('init', array($this, 'load_textdomain'));
        register_activation_hook(CSIG_PLUGIN_DIR . 'client-side-image-generator.php', array($this, 'activate'));
    }
    
    private function load_dependencies() {
        require_once CSIG_PLUGIN_DIR . 'includes/class-settings.php';
        require_once CSIG_PLUGIN_DIR . 'includes/class-job-post-type.php';
        require_once CSIG_PLUGIN_DIR . 'includes/class-admin.php';
        require_once CSIG_PLUGIN_DIR . 'includes/class-ajax.php';
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('csig', false, dirname(plugin_basename(CSIG_PLUGIN_DIR . 'client-side-image-generator.php')) . '/languages/');
    }
    
    public function activate() {
        // Flush rewrite rules after registering post type
        $this->job_post_type = new Job_Post_Type();
        flush_rewrite_rules();
    }
}