<?php
/**
 * Custom Post Type registration for Popover Maker.
 *
 * @package Popover_Maker
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Register the popm_popover custom post type.
 *
 * @return void
 */
function popm_register_post_type() {
    $labels = array(
        'name'                  => __('Popovers', 'popover-maker'),
        'singular_name'         => __('Popover', 'popover-maker'),
        'menu_name'             => __('Popovers', 'popover-maker'),
        'add_new'               => __('Add New', 'popover-maker'),
        'add_new_item'          => __('Add New Popover', 'popover-maker'),
        'edit_item'             => __('Edit Popover', 'popover-maker'),
        'new_item'              => __('New Popover', 'popover-maker'),
        'view_item'             => __('View Popover', 'popover-maker'),
        'search_items'          => __('Search Popovers', 'popover-maker'),
        'not_found'             => __('No popovers found', 'popover-maker'),
        'not_found_in_trash'    => __('No popovers found in Trash', 'popover-maker'),
        'all_items'             => __('All Popovers', 'popover-maker'),
    );

    $args = array(
        'labels'              => $labels,
        'public'              => false,
        'publicly_queryable'  => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'supports'            => array('title', 'thumbnail'),
        'menu_icon'           => 'dashicons-feedback',
        'rewrite'             => false,
    );

    register_post_type('popm_popover', $args);
}
