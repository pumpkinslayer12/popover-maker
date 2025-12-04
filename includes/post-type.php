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

/**
 * Define custom admin columns for the popover list.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function popm_add_admin_columns($columns) {
    $new_columns = array();

    // Keep checkbox and title at the start.
    if (isset($columns['cb'])) {
        $new_columns['cb'] = $columns['cb'];
    }
    if (isset($columns['title'])) {
        $new_columns['title'] = $columns['title'];
    }

    // Add custom columns.
    $new_columns['popm_priority'] = __('Priority', 'popover-maker');
    $new_columns['popm_location'] = __('Location', 'popover-maker');
    $new_columns['popm_status']   = __('Status', 'popover-maker');
    $new_columns['popm_views']    = __('Views', 'popover-maker');

    // Keep date at the end.
    if (isset($columns['date'])) {
        $new_columns['date'] = $columns['date'];
    }

    return $new_columns;
}

/**
 * Render custom admin column content.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 * @return void
 */
function popm_render_admin_columns($column, $post_id) {
    switch ($column) {
        case 'popm_priority':
            $priority = get_post_meta($post_id, '_popm_priority', true);
            echo esc_html($priority !== '' ? $priority : '10');
            break;

        case 'popm_location':
            $location = get_post_meta($post_id, '_popm_display_location', true);
            $locations = array(
                'all'      => __('All', 'popover-maker'),
                'homepage' => __('Homepage', 'popover-maker'),
                'pages'    => __('Pages', 'popover-maker'),
                'posts'    => __('Posts', 'popover-maker'),
            );
            echo esc_html(isset($locations[$location]) ? $locations[$location] : $locations['all']);
            break;

        case 'popm_status':
            $start_date = get_post_meta($post_id, '_popm_start_date', true);
            $end_date   = get_post_meta($post_id, '_popm_end_date', true);
            $now        = current_time('timestamp');

            $status = __('Active', 'popover-maker');

            if ($start_date && strtotime($start_date) > $now) {
                $status = __('Scheduled', 'popover-maker');
            } elseif ($end_date && strtotime($end_date) < $now) {
                $status = __('Expired', 'popover-maker');
            }

            echo esc_html($status);
            break;

        case 'popm_views':
            $views = get_post_meta($post_id, '_popm_views', true);
            echo esc_html($views !== '' ? $views : '0');
            break;
    }
}
