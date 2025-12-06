<?php
/**
 * Analytics functions for Popover Maker.
 *
 * @package Popover_Maker
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Increment view count for a popover.
 *
 * @param int $post_id Popover post ID.
 * @return void
 */
function popm_increment_views($post_id) {
    $views = get_post_meta($post_id, '_popm_views', true);
    $views = $views !== '' ? intval($views) : 0;
    update_post_meta($post_id, '_popm_views', $views + 1);
}

/**
 * Increment dismissal count for a popover.
 *
 * @param int $post_id Popover post ID.
 * @return void
 */
function popm_increment_dismissals($post_id) {
    $dismissals = get_post_meta($post_id, '_popm_dismissals', true);
    $dismissals = $dismissals !== '' ? intval($dismissals) : 0;
    update_post_meta($post_id, '_popm_dismissals', $dismissals + 1);
}
