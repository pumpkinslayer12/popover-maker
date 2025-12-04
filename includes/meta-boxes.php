<?php
/**
 * Meta boxes for Popover Maker.
 *
 * @package Popover_Maker
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Register meta boxes for the popover edit screen.
 *
 * @return void
 */
function popm_register_meta_boxes() {
    add_meta_box(
        'popm_form_settings',
        __('Form Settings', 'popover-maker'),
        'popm_render_form_settings_meta_box',
        'popm_popover',
        'normal',
        'high'
    );
}

/**
 * Render the Form Settings meta box.
 *
 * @param WP_Post $post Current post object.
 * @return void
 */
function popm_render_form_settings_meta_box($post) {
    // Add nonce for security.
    wp_nonce_field('popm_save_meta', 'popm_meta_nonce');

    // Get current values.
    $form_provider = get_post_meta($post->ID, '_popm_form_provider', true);
    $form_url      = get_post_meta($post->ID, '_popm_form_url', true);

    // Set defaults if empty.
    if ($form_provider === '') {
        $form_provider = 'google_forms';
    }
    ?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="popm_form_provider"><?php esc_html_e('Form Provider', 'popover-maker'); ?></label>
            </th>
            <td>
                <select id="popm_form_provider" name="popm_form_provider">
                    <option value="google_forms" <?php selected($form_provider, 'google_forms'); ?>>
                        <?php esc_html_e('Google Forms', 'popover-maker'); ?>
                    </option>
                </select>
                <p class="description"><?php esc_html_e('Select the form provider.', 'popover-maker'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="popm_form_url"><?php esc_html_e('Form URL', 'popover-maker'); ?></label>
            </th>
            <td>
                <input type="url" id="popm_form_url" name="popm_form_url" value="<?php echo esc_attr($form_url); ?>" class="large-text">
                <p class="description"><?php esc_html_e('Enter the embed URL for your form.', 'popover-maker'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Save meta box data.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function popm_save_meta($post_id) {
    // Check nonce.
    if (!isset($_POST['popm_meta_nonce']) || !wp_verify_nonce($_POST['popm_meta_nonce'], 'popm_save_meta')) {
        return;
    }

    // Check autosave.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check permissions.
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save Form Provider.
    if (isset($_POST['popm_form_provider'])) {
        $allowed_providers = array('google_forms');
        $form_provider = sanitize_text_field($_POST['popm_form_provider']);
        if (!in_array($form_provider, $allowed_providers, true)) {
            $form_provider = 'google_forms';
        }
        update_post_meta($post_id, '_popm_form_provider', $form_provider);
    }

    // Save Form URL.
    if (isset($_POST['popm_form_url'])) {
        $form_url = esc_url_raw($_POST['popm_form_url']);
        update_post_meta($post_id, '_popm_form_url', $form_url);
    }
}
