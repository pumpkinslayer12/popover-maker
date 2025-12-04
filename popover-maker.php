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

/**
 * Register hooks.
 */
add_action('init', 'popm_register_post_type');
add_filter('manage_popm_popover_posts_columns', 'popm_add_admin_columns');
add_action('manage_popm_popover_posts_custom_column', 'popm_render_admin_columns', 10, 2);
