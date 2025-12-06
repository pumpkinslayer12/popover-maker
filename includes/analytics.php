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
 * Increment engaged count for a popover.
 *
 * Engaged = user closed the popover after 5+ seconds (interacted with content).
 *
 * @param int $post_id Popover post ID.
 * @return void
 */
function popm_increment_engaged($post_id) {
    $engaged = get_post_meta($post_id, '_popm_engaged', true);
    $engaged = $engaged !== '' ? intval($engaged) : 0;
    update_post_meta($post_id, '_popm_engaged', $engaged + 1);
}

/**
 * Increment bounced count for a popover.
 *
 * Bounced = user closed the popover within 5 seconds (dismissed immediately).
 *
 * @param int $post_id Popover post ID.
 * @return void
 */
function popm_increment_bounced($post_id) {
    $bounced = get_post_meta($post_id, '_popm_bounced', true);
    $bounced = $bounced !== '' ? intval($bounced) : 0;
    update_post_meta($post_id, '_popm_bounced', $bounced + 1);
}
