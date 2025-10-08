<?php
/**
 * Plugin Name: HTML to Image Generator
 * Plugin URI: https://www.crearewebsolutions.com/plugin/html-to-image-generator/
 * Description: Generate images from HTML elements on your website. Perfect for email signatures, business cards, and marketing materials.
 * Version: 1.1.4
 * Author: Nic Scott
 * Author URI: https://www.crearewebsolutions.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: html-to-image-generator
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CSIG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CSIG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CSIG_VERSION', '1.1.4');

// Load the main plugin class
require_once CSIG_PLUGIN_DIR . 'includes/class-plugin.php';

// Use statement to import the class
use CSIG\Plugin;

// Initialize plugin
add_action('plugins_loaded', function() {
    Plugin::get_instance();
});
