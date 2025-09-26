<?php

namespace CSIG;

if (!defined('ABSPATH')) {
    exit;
}

class Admin {
    
    private $settings;
    
    public function __construct(Settings $settings) {
        $this->settings = $settings;
        add_action('admin_menu', array($this, 'add_admin_pages'));
    }
    
    public function add_admin_pages() {
        // Main page - dashboard
        add_menu_page(
            __('Client-Side Image Generator', 'csig'),
            __('Image Generator', 'csig'),
            'manage_options',
            'client-side-image-generator',
            array($this, 'dashboard_page'),
            'dashicons-camera-alt'
        );
        
        // Global Settings submenu
        add_submenu_page(
            'client-side-image-generator',
            __('Global Settings', 'csig'),
            __('Global Settings', 'csig'),
            'manage_options',
            'csig-settings',
            array($this, 'settings_page')
        );
    }
    
    public function dashboard_page() {
        include CSIG_PLUGIN_DIR . 'templates/admin/dashboard-page.php';
    }
    
    public function settings_page() {
        include CSIG_PLUGIN_DIR . 'templates/admin/settings-page.php';
    }
}