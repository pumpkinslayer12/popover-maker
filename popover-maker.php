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
 * The code that runs during plugin activation.
 * This action is documented in includes/popm-functions.php
 */
require_once POPM_PLUGIN_PATH . 'includes/popm-functions.php';

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
