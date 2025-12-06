<?php
/**
* Plugin Name: Popover Maker
*
* Plugin URI: https://github.com/pumpkinslayer12/mediatree-organizer/
*
* Description: Popover Maker allows you to use the block editor to add a simple popover to any wordpress page or post,
leveraging existing blocks.
*
* Author: pumpkinslayer12
*
* Author URI: https://https://github.com/pumpkinslayer12
*
* Version: 1.0
*
* Text Domain: popover-maker
*
* License: GPL v3 or later
*
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
*
* @package Popover_Maker
*/

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Define Constants for Plugin path and Plugin URL
 */
define('POPM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('POPM_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Include required files.
 */
require_once POPM_PLUGIN_PATH . 'includes/popm-functions.php';
require_once POPM_PLUGIN_PATH . 'includes/post-type.php';
require_once POPM_PLUGIN_PATH . 'includes/meta-boxes.php';
require_once POPM_PLUGIN_PATH . 'includes/display.php';

/**
 * Register hooks.
 */
add_action('init', 'popm_register_post_type');
add_filter('manage_popm_popover_posts_columns', 'popm_add_admin_columns');
add_action('manage_popm_popover_posts_custom_column', 'popm_render_admin_columns', 10, 2);
add_action('add_meta_boxes', 'popm_register_meta_boxes');
add_action('save_post_popm_popover', 'popm_save_meta');
add_filter('redirect_post_location', 'popm_redirect_post_location', 10, 2);
add_action('admin_notices', 'popm_admin_notices');

/**
 * Diagnostic functions
 */
add_action('wp_footer', function () {
    $popover = popm_get_active_popover();
    if ($popover) {
        // Load CSS.
        echo '<link rel="stylesheet" href="' . esc_url(POPM_PLUGIN_URL . 'assets/css/popover.css') . '">';
        // Load template.
        include POPM_PLUGIN_PATH . 'templates/popover-template.php';
        // Load JS.
        echo '<script src="' . esc_url(POPM_PLUGIN_URL . 'assets/js/popover.js') . '"></script>';
    } else {
        echo '<pre style="background:#fff;padding:10px;position:fixed;bottom:0;left:0;z-index:9999;">';
        var_dump('No popover');
        echo '</pre>';
    }
});