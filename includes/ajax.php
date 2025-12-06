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
 * AJAX handler: Track popover dismissal.
 *
 * @return void
 */
function popm_ajax_track_dismissal() {
    // Verify nonce.
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'popm_tracking')) {
        wp_send_json_error('Invalid nonce');
    }

    // Get popover ID.
    $popover_id = isset($_POST['popover_id']) ? intval($_POST['popover_id']) : 0;
    if (!$popover_id) {
        wp_send_json_error('Invalid popover ID');
    }

    // Verify popover exists.
    $popover = get_post($popover_id);
    if (!$popover || $popover->post_type !== 'popm_popover') {
        wp_send_json_error('Invalid popover');
    }

    // Increment dismissals.
    popm_increment_dismissals($popover_id);

    wp_send_json_success();
}
