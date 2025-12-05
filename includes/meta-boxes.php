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

    add_meta_box(
        'popm_display_rules',
        __('Display Rules', 'popover-maker'),
        'popm_render_display_rules_meta_box',
        'popm_popover',
        'normal',
        'high'
    );

    add_meta_box(
        'popm_scheduling',
        __('Scheduling', 'popover-maker'),
        'popm_render_scheduling_meta_box',
        'popm_popover',
        'normal',
        'high'
    );

    add_meta_box(
        'popm_layout',
        __('Layout', 'popover-maker'),
        'popm_render_layout_meta_box',
        'popm_popover',
        'normal',
        'high'
    );

    add_meta_box(
        'popm_dismissal_settings',
        __('Dismissal Settings', 'popover-maker'),
        'popm_render_dismissal_settings_meta_box',
        'popm_popover',
        'normal',
        'high'
    );

    add_meta_box(
        'popm_analytics',
        __('Analytics', 'popover-maker'),
        'popm_render_analytics_meta_box',
        'popm_popover',
        'side',
        'default'
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
 * Render the Display Rules meta box.
 *
 * @param WP_Post $post Current post object.
 * @return void
 */
function popm_render_display_rules_meta_box($post) {
    // Get current values.
    $display_location = get_post_meta($post->ID, '_popm_display_location', true);
    $priority         = get_post_meta($post->ID, '_popm_priority', true);

    // Set defaults if empty.
    if ($display_location === '') {
        $display_location = 'all';
    }
    if ($priority === '') {
        $priority = 10;
    }
    ?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="popm_display_location"><?php esc_html_e('Display Location', 'popover-maker'); ?></label>
            </th>
            <td>
                <select id="popm_display_location" name="popm_display_location">
                    <option value="all" <?php selected($display_location, 'all'); ?>>
                        <?php esc_html_e('All Pages', 'popover-maker'); ?>
                    </option>
                    <option value="homepage" <?php selected($display_location, 'homepage'); ?>>
                        <?php esc_html_e('Homepage Only', 'popover-maker'); ?>
                    </option>
                    <option value="pages" <?php selected($display_location, 'pages'); ?>>
                        <?php esc_html_e('Pages Only', 'popover-maker'); ?>
                    </option>
                    <option value="posts" <?php selected($display_location, 'posts'); ?>>
                        <?php esc_html_e('Posts Only', 'popover-maker'); ?>
                    </option>
                </select>
                <p class="description"><?php esc_html_e('Where should this popover be displayed?', 'popover-maker'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="popm_priority"><?php esc_html_e('Priority', 'popover-maker'); ?></label>
            </th>
            <td>
                <input type="number" id="popm_priority" name="popm_priority" value="<?php echo esc_attr($priority); ?>" min="0" max="999" step="1" class="small-text">
                <p class="description"><?php esc_html_e('Higher priority popovers display first (0-999).', 'popover-maker'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Render the Scheduling meta box.
 *
 * @param WP_Post $post Current post object.
 * @return void
 */
function popm_render_scheduling_meta_box($post) {
    // Get current values.
    $start_date = get_post_meta($post->ID, '_popm_start_date', true);
    $end_date   = get_post_meta($post->ID, '_popm_end_date', true);

    // Convert MySQL format to HTML5 datetime-local format for display.
    $start_value = '';
    $end_value   = '';
    if ($start_date) {
        $start_value = date('Y-m-d\TH:i', strtotime($start_date));
    }
    if ($end_date) {
        $end_value = date('Y-m-d\TH:i', strtotime($end_date));
    }
    ?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="popm_start_date"><?php esc_html_e('Start Date', 'popover-maker'); ?></label>
            </th>
            <td>
                <input type="datetime-local" id="popm_start_date" name="popm_start_date" value="<?php echo esc_attr($start_value); ?>">
                <p class="description"><?php esc_html_e('Leave empty to start immediately.', 'popover-maker'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="popm_end_date"><?php esc_html_e('End Date', 'popover-maker'); ?></label>
            </th>
            <td>
                <input type="datetime-local" id="popm_end_date" name="popm_end_date" value="<?php echo esc_attr($end_value); ?>">
                <p class="description"><?php esc_html_e('Leave empty for no end date.', 'popover-maker'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Render the Layout meta box.
 *
 * @param WP_Post $post Current post object.
 * @return void
 */
function popm_render_layout_meta_box($post) {
    // Get current values.
    $width      = get_post_meta($post->ID, '_popm_width', true);
    $max_height = get_post_meta($post->ID, '_popm_max_height', true);

    // Set defaults if empty.
    if ($width === '') {
        $width = '900px';
    }
    if ($max_height === '') {
        $max_height = '600px';
    }
    ?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="popm_width"><?php esc_html_e('Width', 'popover-maker'); ?></label>
            </th>
            <td>
                <input type="text" id="popm_width" name="popm_width" value="<?php echo esc_attr($width); ?>" class="regular-text">
                <p class="description"><?php esc_html_e('CSS value (e.g., 900px, 80%, 90vw)', 'popover-maker'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="popm_max_height"><?php esc_html_e('Max Height', 'popover-maker'); ?></label>
            </th>
            <td>
                <input type="text" id="popm_max_height" name="popm_max_height" value="<?php echo esc_attr($max_height); ?>" class="regular-text">
                <p class="description"><?php esc_html_e('CSS value (e.g., 600px, 80%, 90vh)', 'popover-maker'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Render the Dismissal Settings meta box.
 *
 * @param WP_Post $post Current post object.
 * @return void
 */
function popm_render_dismissal_settings_meta_box($post) {
    // Get current value.
    $cookie_days = get_post_meta($post->ID, '_popm_cookie_days', true);

    // Set default if empty.
    if ($cookie_days === '') {
        $cookie_days = 7;
    }
    ?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="popm_cookie_days"><?php esc_html_e('Remember Dismissal', 'popover-maker'); ?></label>
            </th>
            <td>
                <input type="number" id="popm_cookie_days" name="popm_cookie_days" value="<?php echo esc_attr($cookie_days); ?>" min="0" max="365" step="1" class="small-text">
                <?php esc_html_e('days', 'popover-maker'); ?>
                <p class="description"><?php esc_html_e('Days to remember dismissal. Set to 0 to always show popover.', 'popover-maker'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Render the Analytics meta box.
 *
 * @param WP_Post $post Current post object.
 * @return void
 */
function popm_render_analytics_meta_box($post) {
    // Get current values.
    $views      = get_post_meta($post->ID, '_popm_views', true);
    $dismissals = get_post_meta($post->ID, '_popm_dismissals', true);

    // Set defaults if empty.
    $views      = $views !== '' ? intval($views) : 0;
    $dismissals = $dismissals !== '' ? intval($dismissals) : 0;

    // Calculate dismissal rate.
    $rate = $views > 0 ? round(($dismissals / $views) * 100, 1) : 0;
    ?>
    <p><strong><?php esc_html_e('Views:', 'popover-maker'); ?></strong> <?php echo esc_html($views); ?></p>
    <p><strong><?php esc_html_e('Dismissals:', 'popover-maker'); ?></strong> <?php echo esc_html($dismissals); ?></p>
    <p><strong><?php esc_html_e('Dismissal Rate:', 'popover-maker'); ?></strong> <?php echo esc_html($rate); ?>%</p>
    <p class="description"><?php esc_html_e('Analytics are tracked automatically when the popover is displayed.', 'popover-maker'); ?></p>
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

    // Save Display Location.
    if (isset($_POST['popm_display_location'])) {
        $allowed_locations = array('all', 'homepage', 'pages', 'posts');
        $display_location = sanitize_text_field($_POST['popm_display_location']);
        if (!in_array($display_location, $allowed_locations, true)) {
            $display_location = 'all';
        }
        update_post_meta($post_id, '_popm_display_location', $display_location);
    }

    // Save Priority.
    if (isset($_POST['popm_priority'])) {
        $priority = intval($_POST['popm_priority']);
        $priority = max(0, min(999, $priority)); // Clamp to 0-999.
        update_post_meta($post_id, '_popm_priority', $priority);
    }

    // Save Start Date.
    if (isset($_POST['popm_start_date'])) {
        $start_date = sanitize_text_field($_POST['popm_start_date']);
        if ($start_date) {
            // Convert HTML5 datetime-local to MySQL format.
            $start_date = date('Y-m-d H:i:s', strtotime($start_date));
        }
        update_post_meta($post_id, '_popm_start_date', $start_date);
    }

    // Save End Date.
    if (isset($_POST['popm_end_date'])) {
        $end_date = sanitize_text_field($_POST['popm_end_date']);
        if ($end_date) {
            // Convert HTML5 datetime-local to MySQL format.
            $end_date = date('Y-m-d H:i:s', strtotime($end_date));
        }
        update_post_meta($post_id, '_popm_end_date', $end_date);
    }

    // Save Width.
    if (isset($_POST['popm_width'])) {
        $width = sanitize_text_field($_POST['popm_width']);
        update_post_meta($post_id, '_popm_width', $width);
    }

    // Save Max Height.
    if (isset($_POST['popm_max_height'])) {
        $max_height = sanitize_text_field($_POST['popm_max_height']);
        update_post_meta($post_id, '_popm_max_height', $max_height);
    }

    // Save Cookie Days.
    if (isset($_POST['popm_cookie_days'])) {
        $cookie_days = intval($_POST['popm_cookie_days']);
        $cookie_days = max(0, min(365, $cookie_days)); // Clamp to 0-365.
        update_post_meta($post_id, '_popm_cookie_days', $cookie_days);
    }
}

/**
 * Add URL parameter for invalid date warning.
 *
 * @param string $location Redirect URL.
 * @param int    $post_id  Post ID.
 * @return string Modified redirect URL.
 */
function popm_redirect_post_location($location, $post_id) {
    // Only check for our post type.
    if (get_post_type($post_id) !== 'popm_popover') {
        return $location;
    }

    $start_date = get_post_meta($post_id, '_popm_start_date', true);
    $end_date   = get_post_meta($post_id, '_popm_end_date', true);

    // Check if both dates exist and end is before start.
    if ($start_date && $end_date && strtotime($end_date) < strtotime($start_date)) {
        $location = add_query_arg('popm_notice', 'invalid_dates', $location);
    }

    return $location;
}

/**
 * Display admin notices for popover validation.
 *
 * @return void
 */
function popm_admin_notices() {
    if (!isset($_GET['popm_notice'])) {
        return;
    }

    if ($_GET['popm_notice'] === 'invalid_dates') {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php esc_html_e('Warning: End date is before start date. This popover will not display until the dates are corrected.', 'popover-maker'); ?></p>
        </div>
        <?php
    }
}
