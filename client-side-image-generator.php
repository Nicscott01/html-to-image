<?php
/**
 * Plugin Name: Client Side Image Generator
 * Description: Generate images from HTML elements using the .csig-card selector by default
 * Version: 1.0.5
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CSIG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CSIG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CSIG_VERSION', '1.0.5');

// Load the main plugin class
require_once CSIG_PLUGIN_DIR . 'includes/class-plugin.php';

// Use statement to import the class
use CSIG\Plugin;

// Initialize plugin
add_action('plugins_loaded', function() {
    Plugin::get_instance();
});
