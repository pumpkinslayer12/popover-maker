<?php
/**
 * Popover template.
 *
 * @package Popover_Maker
 *
 * Variables available:
 * @var WP_Post $popover The popover post object.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get meta values.
$form_url    = get_post_meta($popover->ID, '_popm_form_url', true);
$width       = get_post_meta($popover->ID, '_popm_width', true);
$max_height  = get_post_meta($popover->ID, '_popm_max_height', true);
$cookie_days = get_post_meta($popover->ID, '_popm_cookie_days', true);

// Set defaults if empty.
$width       = $width ? $width : '900px';
$max_height  = $max_height ? $max_height : '600px';
$cookie_days = $cookie_days !== '' ? intval($cookie_days) : 7;

// Check if featured image exists.
$has_image = has_post_thumbnail($popover->ID);

// Determine layout class.
$layout_class = '';
if ($has_image && $form_url) {
    $layout_class = 'popm-layout-both';
} elseif ($form_url) {
    $layout_class = 'popm-layout-form-only';
} elseif ($has_image) {
    $layout_class = 'popm-layout-image-only';
} else {
    $layout_class = 'popm-layout-empty';
}

// Apply content filter.
$form_url = apply_filters('popm_popover_content', $form_url, $popover);
?>
<div class="popm-overlay" data-popover-id="<?php echo esc_attr($popover->ID); ?>" data-cookie-days="<?php echo esc_attr($cookie_days); ?>">
    <div class="popm-popup <?php echo esc_attr($layout_class); ?>" style="width: <?php echo esc_attr($width); ?>; max-height: <?php echo esc_attr($max_height); ?>;" role="dialog" aria-modal="true" aria-labelledby="popm-title-<?php echo esc_attr($popover->ID); ?>">

        <button class="popm-close" aria-label="<?php esc_attr_e('Close', 'popover-maker'); ?>">&times;</button>

        <span id="popm-title-<?php echo esc_attr($popover->ID); ?>" class="screen-reader-text"><?php echo esc_html($popover->post_title); ?></span>

        <?php if ($has_image) : ?>
        <div class="popm-image">
            <?php echo get_the_post_thumbnail($popover->ID, 'large'); ?>
        </div>
        <?php endif; ?>

        <div class="popm-content">
            <?php if ($form_url) : ?>
            <iframe
                class="popm-iframe"
                src="<?php echo esc_url($form_url); ?>"
                frameborder="0"
                allowfullscreen
                title="<?php echo esc_attr($popover->post_title); ?>"
            ></iframe>
            <?php endif; ?>
        </div>

    </div>
</div>
<?php
// Fire action after popover display.
do_action('popm_after_popover_display', $popover);
?>
