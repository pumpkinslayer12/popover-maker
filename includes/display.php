<?php
/**
 * Frontend display functions for Popover Maker.
 *
 * @package Popover_Maker
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Get the active popover for the current page.
 *
 * Queries all published popovers, filters by location/dates/cookie,
 * and returns the highest priority match.
 *
 * @return WP_Post|null The matching popover post or null if none match.
 */
function popm_get_active_popover() {
    // Skip conditions - return null early.
    if (is_admin()) {
        return null;
    }
    if (is_customize_preview()) {
        return null;
    }
    if (isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php') {
        return null;
    }

    // Build query args.
    $args = array(
        'post_type'      => 'popm_popover',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => array(
            'meta_value_num' => 'DESC',
            'date'           => 'DESC',
        ),
        'meta_key'       => '_popm_priority',
    );

    // Allow filtering of query args.
    $args = apply_filters('popm_query_args', $args);

    // Run the query.
    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return null;
    }

    // Loop through results and find first matching popover.
    foreach ($query->posts as $popover) {
        // Check location match.
        if (!popm_location_matches($popover->ID)) {
            continue;
        }

        // Check date range.
        if (!popm_date_range_valid($popover->ID)) {
            continue;
        }

        // Check if dismissed via cookie.
        if (popm_is_dismissed($popover->ID)) {
            continue;
        }

        // Allow filtering of display decision.
        if (!apply_filters('popm_should_display', true, $popover)) {
            continue;
        }

        // Found a match - return it.
        return $popover;
    }

    // No matching popover found.
    return null;
}

/**
 * Check if popover's display location matches current page.
 *
 * @param int $post_id Popover post ID.
 * @return bool True if location matches, false otherwise.
 */
function popm_location_matches($post_id) {
    $location = get_post_meta($post_id, '_popm_display_location', true);

    // Default to 'all' if not set.
    if (!$location) {
        $location = 'all';
    }

    switch ($location) {
        case 'homepage':
            return is_front_page(); // NOT is_home().
        case 'pages':
            return is_page();
        case 'posts':
            return is_single();
        case 'all':
        default:
            return true; // Includes archives, search, 404.
    }
}

/**
 * Check if current time is within popover's date range.
 *
 * @param int $post_id Popover post ID.
 * @return bool True if within date range, false otherwise.
 */
function popm_date_range_valid($post_id) {
    $start = get_post_meta($post_id, '_popm_start_date', true);
    $end   = get_post_meta($post_id, '_popm_end_date', true);
    $now   = current_time('timestamp');

    // Empty dates = no restriction.
    // Check start date - if set and in future, not valid yet.
    if ($start && strtotime($start) > $now) {
        return false;
    }

    // Check end date - if set and in past, expired.
    if ($end && strtotime($end) < $now) {
        return false;
    }

    // Invalid range check (end before start).
    if ($start && $end && strtotime($end) < strtotime($start)) {
        return false;
    }

    return true;
}

/**
 * Check if user has dismissed this popover via cookie.
 *
 * @param int $post_id Popover post ID.
 * @return bool True if dismissed, false otherwise.
 */
function popm_is_dismissed($post_id) {
    $cookie_name = 'popm_dismissed_' . $post_id;
    return isset($_COOKIE[$cookie_name]);
}

/**
 * Inject popover HTML into footer if one should display.
 *
 * Hooked to wp_footer at priority 999.
 *
 * @return void
 */
function popm_inject_popover() {
    $popover = popm_get_active_popover();
    if (!$popover) {
        return;
    }

    // Load template.
    include POPM_PLUGIN_PATH . 'templates/popover-template.php';
}

/**
 * Enqueue frontend CSS and JS if a popover will display.
 *
 * Hooked to wp_enqueue_scripts.
 *
 * @return void
 */
function popm_enqueue_frontend_assets() {
    $popover = popm_get_active_popover();
    if (!$popover) {
        return;
    }

    wp_enqueue_style(
        'popm-popover',
        POPM_PLUGIN_URL . 'assets/css/popover.css',
        array(),
        '1.0'
    );

    wp_enqueue_script(
        'popm-popover',
        POPM_PLUGIN_URL . 'assets/js/popover.js',
        array(),
        '1.0',
        true
    );

    // Pass data to JS.
    wp_localize_script('popm-popover', 'popmData', array(
        'ajaxUrl'   => admin_url('admin-ajax.php'),
        'nonce'     => wp_create_nonce('popm_tracking'),
        'popoverId' => $popover->ID,
    ));
}
