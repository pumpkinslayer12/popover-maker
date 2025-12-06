<?php
/**
 * AJAX handlers for Popover Maker.
 *
 * @package Popover_Maker
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * AJAX handler: Track popover view.
 *
 * @return void
 */
function popm_ajax_track_view() {
    // Verify nonce.
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'popm_tracking')) {
        wp_send_json_error('Invalid nonce');
    }

    // Get popover ID.
    $popover_id = isset($_POST['popover_id']) ? intval($_POST['popover_id']) : 0;
    if (!$popover_id) {
        wp_send_json_error('Invalid popover ID');
    }

    // Verify popover exists and is published.
    $popover = get_post($popover_id);
    if (!$popover || $popover->post_type !== 'popm_popover' || $popover->post_status !== 'publish') {
        wp_send_json_error('Invalid popover');
    }

    // Increment views.
    popm_increment_views($popover_id);

    wp_send_json_success();
}

/**
 * Engagement threshold in seconds.
 *
 * Users who close the popover after this many seconds are considered "engaged".
 * Users who close before this threshold are considered "bounced".
 */
define('POPM_ENGAGEMENT_THRESHOLD', 5);

/**
 * AJAX handler: Track popover close with engagement status.
 *
 * @return void
 */
function popm_ajax_track_close() {
    // Verify nonce.
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'popm_tracking')) {
        wp_send_json_error('Invalid nonce');
    }

    // Get popover ID.
    $popover_id = isset($_POST['popover_id']) ? intval($_POST['popover_id']) : 0;
    if (!$popover_id) {
        wp_send_json_error('Invalid popover ID');
    }

    // Get duration (seconds popover was open).
    $duration = isset($_POST['duration']) ? floatval($_POST['duration']) : 0;

    // Verify popover exists.
    $popover = get_post($popover_id);
    if (!$popover || $popover->post_type !== 'popm_popover') {
        wp_send_json_error('Invalid popover');
    }

    // Increment engaged or bounced based on threshold.
    if ($duration >= POPM_ENGAGEMENT_THRESHOLD) {
        popm_increment_engaged($popover_id);
    } else {
        popm_increment_bounced($popover_id);
    }

    wp_send_json_success();
}
