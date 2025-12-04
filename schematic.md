# Popover Maker - WordPress Plugin Specification
## Version 2.2 - Functional Programming Architecture

**Document Purpose**: This specification provides detailed technical requirements for building the Popover Maker WordPress plugin using a functional programming approach. This is designed for systematic implementation by an AI coding agent in phased, testable increments.
---

## Table of Contents
1. [Project Overview](#project-overview)
2. [Technical Requirements](#technical-requirements)
3. [Architecture Overview](#architecture-overview)
4. [Database Schema](#database-schema)
5. [File Structure](#file-structure)
6. [Phase 1: Foundation](#phase-1-foundation)
7. [Phase 2: Display Logic](#phase-2-display-logic)
8. [Phase 3: Frontend Rendering](#phase-3-frontend-rendering)
9. [Phase 4: Analytics](#phase-4-analytics)
10. [Phase 5: Polish](#phase-5-polish)
11. [Testing Criteria](#testing-criteria)
12. [Edge Cases](#edge-cases)
13. [WordPress Best Practices](#wordpress-best-practices)

---

## Project Overview

### Purpose
Create a WordPress plugin that allows site administrators to create and manage modal popovers with embedded forms (starting with Google Forms) that display on the frontend based on configurable rules, scheduling, and priority.

### Key Design Decisions
- **Custom Post Type approach** (not Gutenberg block) for centralized management
- **Functional programming paradigm** - no classes, just functions with `popm_` prefix
- **Priority-based system** where only the highest priority popover displays
- **Form template system** starting with Google Forms, expandable to other providers
- **Cookie-based dismissal** to respect user preferences per-popover
- **Built-in analytics** tracking views and dismissals
- **No external dependencies** - vanilla WordPress only

### Functional Programming Philosophy
This plugin uses functional programming throughout instead of object-oriented classes:

**Why Functional?**
- Simpler debugging - direct function calls with clear stack traces
- Easier to modify - no inheritance or object state to track
- More WordPress-native - core WordPress is primarily functional
- Better for collaboration - no hidden dependencies or initialization order issues
- Easier to extend - other plugins can unhook/replace individual functions

**Implementation**:
- All functions prefixed with `popm_` for namespace protection
- Functions organized by purpose into separate files
- No classes, no objects (except WordPress core objects like WP_Query, WP_Post)
- Direct hook registration via `add_action()` and `add_filter()`
- Simple requires and hook calls in main plugin file

### User Workflow
1. Admin navigates to "Popovers" → "Add New"
2. Admin enters popover title (internal reference only)
3. Admin optionally sets featured image (displays left side of popover)
4. Admin chooses form provider (Phase 1: Google Forms only)
5. Admin pastes form URL
6. Admin configures display rules, scheduling, dimensions, and trigger settings
7. Admin publishes popover
8. System automatically displays popover on frontend based on rules and priority

---

## Technical Requirements

### Environment
- **Minimum WordPress Version**: 6.0
- **Minimum PHP Version**: 7.4
- **Required WordPress Features**: Custom Post Types, Post Meta, wp_enqueue_scripts, AJAX
- **Browser Support**: Modern browsers with ES6 JavaScript support
- **No External Dependencies**: No CMB2, ACF, or other plugin dependencies

### Compatibility
- Must work with any WordPress theme
- Must respect theme styles (use defensive CSS)
- Must work with common caching plugins
- Must be translation-ready (text domain: `popover-maker`)

---

## Architecture Overview

### High-Level Flow
```
1. Admin creates popover (CPT) with settings stored as post meta
2. On frontend page load (wp_footer hook):
   a. Query published popovers matching current page context
   b. Filter by date range (start/end dates in MySQL datetime format)
   c. Check cookie dismissal status
   d. Order by priority (DESC), then post_date (DESC)
      → NOTE: If multiple popovers have same priority, NEWEST creation date wins (intentional design choice)
   e. Select first match (highest priority, newest if tied)
   f. Inject HTML into footer
   g. Enqueue inline JavaScript for that specific popover
3. User interacts (view/dismiss)
4. AJAX request increments analytics meta
5. Cookie set on dismissal if enabled (cookie_days > 0)
```

**Date Format Transformation Pipeline**:
- HTML5 `datetime-local` input (YYYY-MM-DDTHH:MM) 
- → Convert to MySQL datetime format (Y-m-d H:i:s) for storage
- → Use `strtotime()` for comparison with `current_time('timestamp')`

### Core Components
- **Custom Post Type**: `popm_popover` (not publicly accessible URLs)
- **Post Meta**: All configuration stored as meta fields
- **Functions**: Organized by purpose in separate files (all prefixed with `popm_`)
- **Hooks**: Direct function registration on WordPress hooks
- **Frontend Injection**: `wp_footer` hook with conditional logic
- **Analytics Tracking**: AJAX handlers with nonce verification
- **Cookie Management**: JavaScript-based per-popover dismissal

**Programming Paradigm**: Functional programming throughout
- No classes, no objects (except WordPress core objects like WP_Query, WP_Post)
- All functions prefixed with `popm_` to avoid naming conflicts
- Functions organized by purpose into separate files
- Simple require_once and add_action/add_filter in main plugin file

---

## Database Schema

### Custom Post Type: `popm_popover`

**Post Type Configuration**:
```
register_post_type('popm_popover', [
    'public' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'capability_type' => 'post',
    'capabilities' => [
        'edit_post' => 'edit_posts',
        'delete_post' => 'delete_posts',
        'edit_posts' => 'edit_posts',
        'edit_others_posts' => 'edit_others_posts',
        'publish_posts' => 'publish_posts',
        'read_private_posts' => 'read_private_posts',
    ],
    'map_meta_cap' => true,
    'supports' => ['title', 'editor', 'thumbnail'],
    'menu_icon' => 'dashicons-feedback',
]);
```

**Access**: Editors and above (maps to `edit_posts` capability)

**Built-in Fields Used**:
- `post_title`: Popover name (admin reference only, not displayed to users)
- `post_content`: Fallback content if not using form URL (supports Gutenberg/Classic editor)
- `post_status`: draft/publish (standard WordPress states)
- `post_date`: Creation time (tiebreaker for priority conflicts)
- `_thumbnail_id`: Featured image (displays left side of popover)

### Post Meta Fields

All meta fields use `_popm_` prefix for namespace protection.

#### Form Configuration
| Meta Key | Type | Default | Description |
|----------|------|---------|-------------|
| `_popm_form_provider` | string | 'google_forms' | Form provider identifier (Phase 1: only 'google_forms') |
| `_popm_form_url` | string | '' | Form URL (if set, overrides post_content) |

#### Display Rules
| Meta Key | Type | Default | Description |
|----------|------|---------|-------------|
| `_popm_display_location` | string | 'all' | Where to show: 'homepage', 'pages', 'posts', 'all' |
| `_popm_priority` | int | 10 | Higher number = higher priority (0-999 range) |

#### Scheduling
| Meta Key | Type | Default | Description |
|----------|------|---------|-------------|
| `_popm_start_date` | string | '' | MySQL datetime format or empty (Y-m-d H:i:s) |
| `_popm_end_date` | string | '' | MySQL datetime format or empty (Y-m-d H:i:s) |

#### Layout & Dimensions
| Meta Key | Type | Default | Description |
|----------|------|---------|-------------|
| `_popm_width` | string | '900px' | Popover width (CSS value: px, %, vw) |
| `_popm_max_height` | string | '600px' | Popover max height (CSS value: px, %, vh) |

#### Trigger Settings
| Meta Key | Type | Default | Description |
|----------|------|---------|-------------|
| `_popm_trigger_type` | string | 'auto' | 'button', 'auto', or 'both' |
| `_popm_button_text` | string | 'Open Form' | Button text if trigger includes button |
| `_popm_auto_delay` | int | 2 | Seconds before auto-opening (0-60) |

#### Dismissal Settings
| Meta Key | Type | Default | Description |
|----------|------|---------|-------------|
| `_popm_cookie_days` | int | 7 | Days to remember dismissal (0 = don't remember) |

#### Analytics (Read-Only)
| Meta Key | Type | Default | Description |
|----------|------|---------|-------------|
| `_popm_views` | int | 0 | Number of times popover displayed |
| `_popm_dismissals` | int | 0 | Number of times close button clicked |

**Note**: Click tracking (`_popm_clicks`) is excluded from MVP due to cross-origin iframe restrictions. See "Future Enhancements" section for implementation via postMessage API.

---

## File Structure

```
popover-maker/
├── popover-maker.php              # Main plugin file (requires files, registers hooks)
├── readme.md                       # Plugin documentation
├── .gitignore                      # Already exists
│
├── includes/
│   ├── post-type.php              # CPT registration functions
│   ├── meta-boxes.php             # Admin meta box functions
│   ├── display.php                # Frontend query & render functions
│   ├── analytics.php              # Analytics tracking functions
│   └── ajax.php                   # AJAX handler functions
│
├── assets/
│   ├── css/
│   │   ├── admin.css              # Meta box styling
│   │   └── popover.css            # Frontend popover styles
│   │
│   └── js/
│       ├── admin.js               # Admin UI interactions (date pickers, etc.)
│       └── popover.js             # Frontend popover behavior
│
└── templates/
    └── popover-template.php       # HTML structure for frontend popover
```

### File Responsibilities

**popover-maker.php**:
- Plugin header with metadata
- Define constants (POPM_VERSION, POPM_PLUGIN_PATH, POPM_PLUGIN_URL)
- Require all include files
- Register all hooks (add_action, add_filter calls)
- Register activation/deactivation hooks (future use)

**post-type.php**:
- `popm_register_post_type()` - Register custom post type
- `popm_customize_columns()` - Add custom admin columns
- `popm_populate_columns()` - Populate custom column data
- `popm_make_columns_sortable()` - Make priority column sortable

**meta-boxes.php**:
- `popm_add_meta_boxes()` - Register all meta boxes
- `popm_render_form_settings_meta_box()` - Render form settings
- `popm_render_display_rules_meta_box()` - Render display rules
- `popm_render_scheduling_meta_box()` - Render scheduling options
- `popm_render_layout_meta_box()` - Render layout settings
- `popm_render_trigger_settings_meta_box()` - Render trigger options
- `popm_render_dismissal_settings_meta_box()` - Render dismissal settings
- `popm_render_analytics_meta_box()` - Display analytics (read-only)
- `popm_save_meta()` - Save/sanitize all meta data
- Helper functions for sanitization and validation

**display.php**:
- `popm_inject_popover()` - Hook into wp_footer, orchestrate display
- `popm_get_active_popover()` - Query and filter to find active popover
- `popm_check_display_location()` - Check if popover matches current page
- `popm_check_date_range()` - Check if within start/end dates
- `popm_check_cookie()` - Check if user has dismissed
- `popm_render_popover()` - Output HTML via template
- `popm_generate_popover_content()` - Generate iframe or use post_content
- `popm_enqueue_popover_assets()` - Enqueue CSS/JS

**analytics.php**:
- `popm_increment_views()` - Increment view count
- `popm_increment_dismissals()` - Increment dismissal count
- `popm_get_analytics()` - Get all analytics for a popover
- `popm_calculate_dismissal_rate()` - Calculate dismissal percentage

**ajax.php**:
- `popm_ajax_track_view()` - Handle view tracking AJAX
- `popm_ajax_track_dismissal()` - Handle dismissal tracking AJAX
- Helper functions for nonce verification and validation

**popover-template.php**:
- HTML structure for popover
- Receives variables: $popover, $settings, $image_url, $content
- Should be flexible for featured image presence/absence

---

## Phase 1: Foundation
**Goal**: Set up plugin structure, register CPT, create basic admin interface

### Deliverables
1. Update `popover-maker.php` with proper plugin header and initialization
2. Create `post-type.php` and register CPT
3. Verify CPT appears in admin menu as "Popovers"
4. Verify editors can access (test with Editor user role)

### Implementation Details

#### 1.1 Main Plugin File Updates
File: `popover-maker.php`

**Requirements**:
- Update plugin header to reflect functional architecture
- Define version constant (`POPM_VERSION = '2.2.0'`)
- Keep existing path/URL constants
- Require all include files (use require_once with POPM_PLUGIN_PATH)
- Register all hooks directly with add_action/add_filter
- Add text domain loading for translations

**Structure**:
```php
<?php
/**
 * Plugin Name: Popover Maker
 * Description: Create popovers with embedded forms
 * Version: 2.2.0
 * Author: pumpkinslayer12
 * Text Domain: popover-maker
 */

// Security check
if (!defined('WPINC')) {
    die;
}

// Define constants
define('POPM_VERSION', '2.2.0');
define('POPM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('POPM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Require all function files
require_once POPM_PLUGIN_PATH . 'includes/post-type.php';
require_once POPM_PLUGIN_PATH . 'includes/meta-boxes.php';
require_once POPM_PLUGIN_PATH . 'includes/display.php';
require_once POPM_PLUGIN_PATH . 'includes/analytics.php';
require_once POPM_PLUGIN_PATH . 'includes/ajax.php';

// Register hooks
add_action('init', 'popm_register_post_type');
add_action('plugins_loaded', 'popm_load_textdomain');

// Meta boxes
add_action('add_meta_boxes', 'popm_add_meta_boxes');
add_action('save_post_popm_popover', 'popm_save_meta');

// Admin columns
add_filter('manage_popm_popover_posts_columns', 'popm_customize_columns');
add_action('manage_popm_popover_posts_custom_column', 'popm_populate_columns', 10, 2);
add_filter('manage_edit-popm_popover_sortable_columns', 'popm_make_columns_sortable');

// Frontend display
add_action('wp_footer', 'popm_inject_popover', 999);
add_action('wp_enqueue_scripts', 'popm_enqueue_frontend_assets');

// AJAX handlers
add_action('wp_ajax_popm_track_view', 'popm_ajax_track_view');
add_action('wp_ajax_nopriv_popm_track_view', 'popm_ajax_track_view');
add_action('wp_ajax_popm_track_dismissal', 'popm_ajax_track_dismissal');
add_action('wp_ajax_nopriv_popm_track_dismissal', 'popm_ajax_track_dismissal');

// Admin assets
add_action('admin_enqueue_scripts', 'popm_enqueue_admin_assets');

/**
 * Load plugin text domain for translations
 */
function popm_load_textdomain() {
    load_plugin_textdomain('popover-maker', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
```

#### 1.2 Custom Post Type Registration
File: `includes/post-type.php`

**Requirements**:
- Create `popm_register_post_type()` function
- Register post type with specified config (see Database Schema)
- Set labels appropriately (singular: "Popover", plural: "Popovers")
- Hide from public queries (`public => false`, `publicly_queryable => false`)
- Show in admin menu with dashicons-feedback icon
- Support: title, editor (for fallback content), thumbnail (for image)
- Rewrite: disabled (no public URLs needed)

**Function Structure**:
```php
<?php
/**
 * Register the Popover custom post type
 */
function popm_register_post_type() {
    $labels = [
        'name' => __('Popovers', 'popover-maker'),
        'singular_name' => __('Popover', 'popover-maker'),
        'add_new' => __('Add New', 'popover-maker'),
        'add_new_item' => __('Add New Popover', 'popover-maker'),
        'edit_item' => __('Edit Popover', 'popover-maker'),
        'new_item' => __('New Popover', 'popover-maker'),
        'view_item' => __('View Popover', 'popover-maker'),
        'search_items' => __('Search Popovers', 'popover-maker'),
        'not_found' => __('No popovers found', 'popover-maker'),
        'not_found_in_trash' => __('No popovers found in trash', 'popover-maker'),
    ];
    
    $args = [
        'labels' => $labels,
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-feedback',
        'rewrite' => false,
    ];
    
    register_post_type('popm_popover', $args);
}
```

**Custom Admin Columns**:
Create additional functions for column customization:

```php
/**
 * Customize admin columns for popover list
 */
function popm_customize_columns($columns) {
    // Remove date, add custom columns
    unset($columns['date']);
    
    $columns['priority'] = __('Priority', 'popover-maker');
    $columns['location'] = __('Display Location', 'popover-maker');
    $columns['active'] = __('Active', 'popover-maker');
    $columns['views'] = __('Views', 'popover-maker');
    $columns['date'] = __('Date', 'popover-maker'); // Re-add at end
    
    return $columns;
}

/**
 * Populate custom column data
 */
function popm_populate_columns($column, $post_id) {
    switch ($column) {
        case 'priority':
            $priority = get_post_meta($post_id, '_popm_priority', true);
            echo esc_html($priority ?: '10');
            break;
            
        case 'location':
            $location = get_post_meta($post_id, '_popm_display_location', true);
            $locations = [
                'all' => __('Everywhere', 'popover-maker'),
                'homepage' => __('Homepage', 'popover-maker'),
                'pages' => __('Pages', 'popover-maker'),
                'posts' => __('Posts', 'popover-maker'),
            ];
            echo esc_html($locations[$location] ?? __('Everywhere', 'popover-maker'));
            break;
            
        case 'active':
            // Check if within date range
            $now = current_time('timestamp');
            $start = get_post_meta($post_id, '_popm_start_date', true);
            $end = get_post_meta($post_id, '_popm_end_date', true);
            
            $is_active = true;
            if (!empty($start) && $now < strtotime($start)) {
                $is_active = false;
            }
            if (!empty($end) && $now > strtotime($end)) {
                $is_active = false;
            }
            
            echo $is_active ? '✓ ' . __('Yes', 'popover-maker') : '✗ ' . __('No', 'popover-maker');
            break;
            
        case 'views':
            $views = get_post_meta($post_id, '_popm_views', true);
            echo esc_html(number_format((int) $views));
            break;
    }
}

/**
 * Make priority column sortable
 */
function popm_make_columns_sortable($columns) {
    $columns['priority'] = 'priority';
    $columns['views'] = 'views';
    return $columns;
}
```

**Acceptance Criteria**:
- [ ] "Popovers" menu appears in admin sidebar
- [ ] Clicking "Add New" shows standard post editor with title, content, featured image
- [ ] Custom columns display in list view (even if empty initially)
- [ ] Editors can create/edit popovers
- [ ] Admins can create/edit popovers

---

## Phase 2: Meta Boxes & Settings
**Goal**: Create admin interface for all popover settings

### Deliverables
1. Create `meta-boxes.php` with all meta box functions
2. Implement save/sanitization logic
3. Verify settings persist correctly
4. Add basic validation and error handling

### Implementation Details

#### 2.1 Meta Box Registration
File: `includes/meta-boxes.php`

**Requirements**:
- Create `popm_add_meta_boxes()` function (hooked to `add_meta_boxes`)
- Create `popm_save_meta()` function (hooked to `save_post_popm_popover`)
- Create separate render functions for each meta box:
  1. **Form Settings** - `popm_render_form_settings_meta_box()`
  2. **Display Rules** - `popm_render_display_rules_meta_box()`
  3. **Scheduling** - `popm_render_scheduling_meta_box()`
  4. **Layout** - `popm_render_layout_meta_box()`
  5. **Trigger Settings** - `popm_render_trigger_settings_meta_box()`
  6. **Dismissal Settings** - `popm_render_dismissal_settings_meta_box()`
  7. **Analytics** - `popm_render_analytics_meta_box()`

**Main Registration Function**:
```php
/**
 * Register all meta boxes for popover post type
 */
function popm_add_meta_boxes() {
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
        'side',
        'default'
    );
    
    // ... add remaining meta boxes
}
```

#### 2.2 Form Settings Meta Box
**Fields**:
1. **Form Provider** (read-only for now, always "Google Forms")
   - Display as disabled select or text
   - Value always set to 'google_forms'
   
2. **Form URL**
   - Input type: text (full width)
   - Placeholder: "https://docs.google.com/forms/d/e/..."
   - Label: "Google Form URL"
   - Help text: "Paste the Google Form URL. The form will be embedded in an iframe."

**Validation**:
- If provided, must be valid URL using `esc_url_raw()`
- Should contain 'docs.google.com/forms' (soft warning if not, but allow)
- Store as `_popm_form_url`

**Note**: If form URL is set, post_content is ignored. If empty, post_content used as fallback.

#### 2.3 Display Rules Meta Box
**Fields**:
1. **Display Location**
   - Input type: select
   - Options:
     - 'all' => "Everywhere (all pages and posts)"
     - 'homepage' => "Homepage Only"
     - 'pages' => "Pages Only"
     - 'posts' => "Posts Only"
   - Default: 'all'
   
2. **Priority**
   - Input type: number
   - Min: 0, Max: 999
   - Default: 10
   - Help text: "Higher numbers display first if multiple popovers are active. Range: 0-999."

**Validation**:
- Priority: Cast to integer, clamp between 0-999

#### 2.4 Scheduling Meta Box
**Fields**:
1. **Start Date/Time**
   - Input type: datetime-local (HTML5)
   - Optional (empty = active immediately)
   - Label: "Start Showing On"
   
2. **End Date/Time**
   - Input type: datetime-local (HTML5)
   - Optional (empty = never expires)
   - Label: "Stop Showing On"

**Validation**:
- Transform HTML5 `datetime-local` format (YYYY-MM-DDTHH:MM) to MySQL datetime format (Y-m-d H:i:s) for storage
- If both set, end date must be after start date (show admin notice if invalid)
- Allow empty values (store empty string)
- Use WordPress timezone functions for consistency

**JavaScript Enhancement** (optional for Phase 2):
- Add calendar picker UI for better UX
- Use WordPress core datepicker if available

**Storage Format**: Always store as MySQL datetime (Y-m-d H:i:s) or empty string

#### 2.5 Layout Meta Box
**Fields**:
1. **Popover Width**
   - Input type: text
   - Default: "900px"
   - Help text: "CSS value (e.g., 900px, 80%, 80vw)"
   
2. **Popover Max Height**
   - Input type: text
   - Default: "600px"
   - Help text: "CSS value (e.g., 600px, 80vh). Content will scroll if taller."

**Validation**:
- Allow CSS units: px, %, vw, vh, em, rem
- Sanitize with `sanitize_text_field()`
- If invalid format, revert to default

#### 2.6 Trigger Settings Meta Box
**Fields**:
1. **Trigger Type**
   - Input type: select
   - Options:
     - 'auto' => "Auto-open (on page load)"
     - 'button' => "Button (user clicks to open)"
     - 'both' => "Both (auto-open + button)"
   - Default: 'auto'
   
2. **Auto-Open Delay** (shown if trigger type includes auto)
   - Input type: number
   - Min: 0, Max: 60
   - Default: 2
   - Suffix: "seconds"
   - Help text: "Delay before auto-opening (0-60 seconds)"
   
3. **Button Text** (shown if trigger type includes button)
   - Input type: text
   - Default: "Open Form"
   - Max length: 50 characters

**JavaScript Enhancement**:
- Show/hide delay and button text fields based on trigger type selection

**Validation**:
- Delay: integer between 0-60
- Button text: sanitize with `sanitize_text_field()`, trim to 50 chars

#### 2.7 Dismissal Settings Meta Box
**Fields**:
1. **Remember Dismissal**
   - Input type: number
   - Min: 0, Max: 365
   - Default: 7
   - Suffix: "days"
   - Help text: "Days to remember when user closes popover. 0 = always show."

**Validation**:
- Cast to integer, clamp between 0-365

#### 2.8 Analytics Meta Box (Read-Only)
**Display**:
- Show current values from post meta
- Format:
  ```
  Views: [count]
  Dismissals: [count]
  Dismissal Rate: [dismissals/views * 100]%
  ```
- Style as read-only (grayed out, no input fields)
- Note: "Analytics reset on duplication"
- Note: "Click tracking not available in MVP (iframe limitation)"

**No Save Logic**: This box is display-only

#### 2.9 Save Logic
**Function**: `popm_save_meta($post_id)`
**Hook**: `save_post_popm_popover`

**Requirements**:
- Verify nonce (create nonce in each meta box using `wp_nonce_field()`)
- Check user capabilities (`current_user_can('edit_post', $post_id)`)
- Skip on autosave (`defined('DOING_AUTOSAVE') && DOING_AUTOSAVE`)
- Sanitize all inputs according to field type
- Use `update_post_meta()` for all fields
- Set defaults for missing fields

**Function Structure**:
```php
/**
 * Save popover meta data
 */
function popm_save_meta($post_id) {
    // Security checks
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!isset($_POST['popm_meta_nonce']) || !wp_verify_nonce($_POST['popm_meta_nonce'], 'popm_save_meta')) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Sanitize and save each field
    // Form settings
    if (isset($_POST['popm_form_url'])) {
        $form_url = esc_url_raw($_POST['popm_form_url']);
        update_post_meta($post_id, '_popm_form_url', $form_url);
    }
    
    // Display rules
    if (isset($_POST['popm_display_location'])) {
        $location = sanitize_text_field($_POST['popm_display_location']);
        $allowed = ['all', 'homepage', 'pages', 'posts'];
        if (in_array($location, $allowed)) {
            update_post_meta($post_id, '_popm_display_location', $location);
        }
    }
    
    if (isset($_POST['popm_priority'])) {
        $priority = intval($_POST['popm_priority']);
        $priority = max(0, min(999, $priority)); // Clamp between 0-999
        update_post_meta($post_id, '_popm_priority', $priority);
    }
    
    // ... continue for all fields
}
```

**Helper Functions** (create in same file):
```php
/**
 * Sanitize datetime input to MySQL format
 */
function popm_sanitize_datetime($input) {
    if (empty($input)) {
        return '';
    }
    
    // Convert HTML5 datetime-local to MySQL datetime
    $timestamp = strtotime($input);
    if ($timestamp === false) {
        return '';
    }
    
    return date('Y-m-d H:i:s', $timestamp);
}

/**
 * Validate and sanitize CSS dimension value
 */
function popm_sanitize_dimension($input) {
    // Allow px, %, vw, vh, em, rem
    if (preg_match('/^[0-9]+(\.[0-9]+)?(px|%|vw|vh|em|rem)$/', $input)) {
        return sanitize_text_field($input);
    }
    return '';
}
```

**Error Handling**:
- If end date is before start date, show admin notice and don't save end date
- If invalid URLs, show warning but still save (WordPress will sanitize)

**Admin Notices** (add separate function):
```php
/**
 * Display admin notices for validation errors
 */
function popm_admin_notices() {
    if (isset($_GET['popm_error'])) {
        $error = sanitize_text_field($_GET['popm_error']);
        if ($error === 'invalid_dates') {
            echo '<div class="notice notice-error"><p>' . 
                 __('End date must be after start date.', 'popover-maker') . 
                 '</p></div>';
        }
    }
}
add_action('admin_notices', 'popm_admin_notices');
```

**Acceptance Criteria**:
- [ ] All meta boxes appear on edit screen
- [ ] Settings save correctly when "Update" clicked
- [ ] Settings persist after page reload
- [ ] Invalid inputs show appropriate error messages
- [ ] Analytics box displays (even with 0 values)

---

## Phase 3: Frontend Display Logic
**Goal**: Query and display popovers on the frontend

### Deliverables
1. Create `display.php` with query and render functions
2. Create `templates/popover-template.php` for HTML structure
3. Create `assets/css/popover.css` for styling
4. Create `assets/js/popover.js` for interactions
5. Verify popover displays based on rules

### Implementation Details

#### 3.1 Display Functions Setup
File: `includes/display.php`

**Requirements**:
- Create `popm_inject_popover()` - Main function hooked to `wp_footer` (priority 999)
- Create `popm_get_active_popover()` - Returns popover post object or null
- Create `popm_check_display_location($popover)` - Check if matches current page
- Create `popm_check_date_range($popover)` - Check if within date range
- Create `popm_check_cookie($popover_id)` - Check if user dismissed
- Create `popm_render_popover($popover)` - Outputs HTML via template
- Create `popm_generate_popover_content($popover)` - Generate iframe or content
- Create `popm_enqueue_frontend_assets()` - Enqueue CSS (if popover will display)

**Main Injection Function**:
```php
/**
 * Inject popover into footer if conditions are met
 */
function popm_inject_popover() {
    // Don't show in admin or customizer
    if (is_admin() || is_customize_preview()) {
        return;
    }
    
    // Get active popover
    $popover = popm_get_active_popover();
    
    if (!$popover) {
        return;
    }
    
    // Render the popover
    popm_render_popover($popover);
    
    // Enqueue inline JavaScript with popover data
    popm_enqueue_popover_inline_script($popover);
}
```

#### 3.2 Query Logic
Function: `popm_get_active_popover()`

**Requirements**:
1. **Check if should display on this page**:
   - If admin or login page: return null
   - If Customizer preview: return null
   - If user is logged in as admin and URL has `?popm_preview=ID`: force display that popover (for testing)

2. **Query published popovers**:
   ```php
   $args = [
       'post_type' => 'popm_popover',
       'post_status' => 'publish',
       'posts_per_page' => -1,
       'orderby' => 'meta_value_num',
       'meta_key' => '_popm_priority',
       'order' => 'DESC',
       'suppress_filters' => true,
   ];
   $query = new WP_Query($args);
   ```

3. **Filter results**:
   - Loop through posts
   - For each, check:
     a. **Display location match**: Use `popm_check_display_location($popover)`
     b. **Date range check**: Use `popm_check_date_range($popover)`
     c. **Cookie check**: Use `popm_check_cookie($popover->ID)`
   - If multiple popovers pass all checks:
     - Already ordered by priority DESC
     - If tied on priority, use `post_date` DESC (newer wins - this is intentional per requirements)
   - Return first match or null

4. **Performance note**: Query all once, filter in PHP. For sites with 100+ popovers, consider adding meta_query for date range pre-filtering (optimization for future if needed).

**Function Structure**:
```php
/**
 * Get the active popover to display on current page
 * 
 * @return WP_Post|null Popover post object or null if none should display
 */
function popm_get_active_popover() {
    // Preview mode for admins
    if (current_user_can('edit_posts') && isset($_GET['popm_preview'])) {
        $preview_id = intval($_GET['popm_preview']);
        if (get_post_type($preview_id) === 'popm_popover') {
            return get_post($preview_id);
        }
    }
    
    // Query all published popovers ordered by priority
    $args = [
        'post_type' => 'popm_popover',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => ['meta_value_num' => 'DESC', 'date' => 'DESC'],
        'meta_key' => '_popm_priority',
        'suppress_filters' => true,
    ];
    
    $query = new WP_Query($args);
    
    if (!$query->have_posts()) {
        return null;
    }
    
    // Filter through popovers to find first match
    foreach ($query->posts as $popover) {
        // Check all conditions
        if (popm_check_display_location($popover) &&
            popm_check_date_range($popover) &&
            !popm_check_cookie($popover->ID)) {
            return $popover;
        }
    }
    
    return null;
}
```

#### 3.3 Display Location Logic Details
**Function**: `popm_check_display_location($popover)`

**Context Detection**:
- **Homepage**: `is_front_page()` only (the page set as site front page in Settings → Reading)
  - Note: `is_home()` is NOT included (that's the blog posts page, which may differ from front page)
- **Pages**: `is_page()` AND NOT `is_front_page()`
- **Posts**: `is_single()` (individual post views)
- **All**: Matches everything, including:
  - Homepage
  - Pages
  - Posts  
  - Archive pages (category, tag, date, author)
  - Search results
  - 404 pages
  - Custom post type archives
  - Any other WordPress page context

**Priority Order**: If a page matches multiple rules (e.g., homepage matches both 'homepage' and 'all'), the more specific rule in the query takes precedence based on the display_location value set for each popover.

**Function Structure**:
```php
/**
 * Check if popover should display on current page based on location setting
 * 
 * @param WP_Post $popover The popover post object
 * @return bool True if should display, false otherwise
 */
function popm_check_display_location($popover) {
    $location = get_post_meta($popover->ID, '_popm_display_location', true);
    
    // Default to 'all' if not set
    if (empty($location)) {
        $location = 'all';
    }
    
    switch ($location) {
        case 'homepage':
            return is_front_page();
            
        case 'pages':
            return is_page() && !is_front_page();
            
        case 'posts':
            return is_single();
            
        case 'all':
        default:
            return true; // Show everywhere
    }
}
```

#### 3.4 Date Range Logic
**Function**: `popm_check_date_range($popover)`

**Storage Format**: MySQL datetime (Y-m-d H:i:s) or empty string

**Function Structure**:
```php
/**
 * Check if popover is within its date range (if set)
 * 
 * @param WP_Post $popover The popover post object
 * @return bool True if within range (or no range set), false otherwise
 */
function popm_check_date_range($popover) {
    $now = current_time('timestamp');
    $start = get_post_meta($popover->ID, '_popm_start_date', true);
    $end = get_post_meta($popover->ID, '_popm_end_date', true);
    
    // Check start date
    if (!empty($start)) {
        $start_timestamp = strtotime($start);
        if ($start_timestamp && $now < $start_timestamp) {
            return false; // Not yet active
        }
    }
    
    // Check end date
    if (!empty($end)) {
        $end_timestamp = strtotime($end);
        if ($end_timestamp && $now > $end_timestamp) {
            return false; // Expired
        }
    }
    
    return true; // Within range or no dates set
}
```

#### 3.5 Cookie Logic
**Function**: `popm_check_cookie($popover_id)`

**Cookie Name Pattern**: `popm_dismissed_{post_id}`

**Function Structure**: 
```php
/**
 * Check if user has dismissed this popover
 * 
 * @param int $popover_id The popover post ID
 * @return bool True if dismissed (cookie exists), false otherwise
 */
function popm_check_cookie($popover_id) {
    $cookie_name = 'popm_dismissed_' . $popover_id;
    return isset($_COOKIE[$cookie_name]);
}
```

**Set** (handled in JavaScript on dismissal):
```javascript
Cookie settings:
- Name: popm_dismissed_{post_id}
- Value: 1 (or timestamp)
- Expires: Now + _popm_cookie_days (from settings)
- Path: /
- Secure: if HTTPS
- SameSite: Lax
```

#### 3.6 Popover Template
File: `templates/popover-template.php`

**Variables Passed In**:
- `$popover` (WP_Post object)
- `$settings` (array of all meta values)
- `$image_url` (string or empty)
- `$content` (string, either iframe or post_content)

**HTML Structure**:
```html
<div class="popm-overlay" id="popm-overlay-{id}" data-popover-id="{id}" data-cookie-days="{days}" data-trigger="{type}" data-delay="{seconds}">
    <div class="popm-popup" style="max-width: {width}; max-height: {max_height};">
        <button class="popm-close" aria-label="Close">×</button>
        
        <!-- Image section (only if $image_url not empty) -->
        <?php if (!empty($image_url)): ?>
        <div class="popm-image" style="background-image: url(<?php echo esc_url($image_url); ?>);"></div>
        <?php endif; ?>
        
        <!-- Content section -->
        <div class="popm-content">
            <?php echo $content; // Already sanitized ?>
        </div>
    </div>
</div>

<!-- Button trigger (only if trigger type includes button) -->
<?php if (in_array($settings['trigger_type'], ['button', 'both'])): ?>
<button class="popm-trigger-button" data-popover-id="{id}">
    <?php echo esc_html($settings['button_text']); ?>
</button>
<?php endif; ?>
```

**Data Attributes** (for JavaScript access):
- `data-popover-id`: Post ID
- `data-cookie-days`: From `_popm_cookie_days`
- `data-trigger`: From `_popm_trigger_type`
- `data-delay`: From `_popm_auto_delay`

**Accessibility**:
- Overlay should have `role="dialog"` and `aria-modal="true"`
- Close button needs `aria-label="Close"`
- Consider focus trap (Phase 5 enhancement)

#### 3.7 Content Generation
**Function**: `popm_generate_popover_content($popover)`

**Logic**:
1. Check if `_popm_form_url` is set and not empty
2. If yes, generate iframe
3. If no, use post_content

**Function Structure**:
```php
/**
 * Generate popover content (iframe or post content)
 * 
 * @param WP_Post $popover The popover post object
 * @return string HTML content for popover
 */
function popm_generate_popover_content($popover) {
    $form_url = get_post_meta($popover->ID, '_popm_form_url', true);
    
    if (!empty($form_url)) {
        // Use form iframe
        $form_url = esc_url($form_url);
        
        // Google Forms enhancement: add embedded param if not present
        if (strpos($form_url, 'docs.google.com/forms') !== false) {
            if (strpos($form_url, '?') === false) {
                $form_url .= '?embedded=true';
            } else if (strpos($form_url, 'embedded=true') === false) {
                $form_url .= '&embedded=true';
            }
        }
        
        return sprintf(
            '<iframe src="%s" width="100%%" height="100%%" frameborder="0" marginheight="0" marginwidth="0" class="popm-iframe">Loading…</iframe>',
            $form_url
        );
    } else {
        // Use post content
        return apply_filters('the_content', $popover->post_content);
    }
}
```

#### 3.8 CSS Styling
File: `assets/css/popover.css`

**Requirements**:
- Overlay: full viewport, dark backdrop (rgba(0,0,0,0.6))
- Popup: centered, white background, rounded corners, shadow
- Image: left side (if present), cover fit, responsive
- Content: scrollable if exceeds max height
- Close button: absolute positioned top-right, z-index high
- Button trigger: fixed bottom-right corner, styled (customizable later)

**Key Classes**:
- `.popm-overlay` - full viewport overlay, flex centered
- `.popm-popup` - the modal container
- `.popm-image` - image section (flexbox child)
- `.popm-content` - content section (flexbox child, overflow-y auto)
- `.popm-close` - close button
- `.popm-trigger-button` - trigger button
- `.popm-iframe` - iframe styling

**Responsive**:
```css
@media (max-width: 768px) {
    .popm-popup {
        flex-direction: column; /* Stack image on top */
        max-width: 95vw !important;
    }
    .popm-image {
        min-height: 200px; /* Reasonable mobile height */
    }
}
```

**Hidden by Default**:
```css
.popm-overlay {
    display: none;
    /* When active class added, display: flex */
}
.popm-overlay.active {
    display: flex;
}
```

#### 3.9 JavaScript Interactions
File: `assets/js/popover.js`

**Requirements**:
- Wait for DOM ready
- Check trigger type and handle accordingly
- Open/close functionality
- Cookie management
- Track view on open
- Track dismissal on close
- Track click on content interaction

**Core Functions**:

1. **`initPopover()`**:
   - Get popover element
   - Read data attributes
   - If trigger includes 'auto', set timeout based on delay
   - If trigger includes 'button', attach click listener
   - Attach close button listener
   - Attach overlay click listener (close on outside click)
   - Attach ESC key listener

2. **`openPopover(popoverId)`**:
   - Add 'active' class to overlay
   - Track view via AJAX (call once per page load)
   - Lock body scroll (add class to body, CSS handles it)
   - Set flag that view has been tracked

3. **`closePopover(popoverId, isDismissal)`**:
   - Remove 'active' class from overlay
   - Unlock body scroll
   - If isDismissal is true:
     - Get cookie_days from data attribute
     - **Only set cookie if cookie_days > 0** (0 = don't remember dismissal)
     - If cookie_days > 0: Set cookie (name: `popm_dismissed_{id}`, days from data attribute)
     - Track dismissal via AJAX

4. **`setCookie(name, value, days)`**:
   - Standard JavaScript cookie setting
   - Path: '/'
   - Secure: check if location.protocol === 'https:'
   - SameSite=Lax

5. **`trackAction(action, popoverId)`**:
   - AJAX POST to `admin-ajax.php`
   - Action: 'popm_track_{action}' (e.g., 'popm_track_view')
   - Data: { popover_id, nonce }
   - Error handling: fail silently (don't break user experience)

**Inline JavaScript** (in footer via `wp_localize_script`):
```javascript
var popmData = {
    ajax_url: 'admin-ajax.php URL',
    nonce: 'generated nonce',
    popover_id: 123
};
```

#### 3.10 Enqueue Assets
**Functions**: `popm_enqueue_frontend_assets()` and `popm_enqueue_popover_inline_script($popover)`

**CSS Enqueue**:
```php
/**
 * Enqueue frontend CSS (only when needed)
 */
function popm_enqueue_frontend_assets() {
    // This function can check if popover will display
    // For simplicity, always enqueue (lightweight CSS)
    // Or call from popm_inject_popover() before rendering
    wp_enqueue_style(
        'popm-popover-css',
        POPM_PLUGIN_URL . 'assets/css/popover.css',
        [],
        POPM_VERSION
    );
}
```

**JS Enqueue with Localized Data**:
```php
/**
 * Enqueue popover JavaScript with inline data
 * 
 * @param WP_Post $popover The popover being displayed
 */
function popm_enqueue_popover_inline_script($popover) {
    // Enqueue main script
    wp_enqueue_script(
        'popm-popover-js',
        POPM_PLUGIN_URL . 'assets/js/popover.js',
        ['jquery'], // or remove if using vanilla JS
        POPM_VERSION,
        true // In footer
    );
    
    // Get all settings
    $settings = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('popm_tracking'),
        'popover_id' => $popover->ID,
        'trigger_type' => get_post_meta($popover->ID, '_popm_trigger_type', true) ?: 'auto',
        'auto_delay' => (int) get_post_meta($popover->ID, '_popm_auto_delay', true) ?: 2,
        'cookie_days' => (int) get_post_meta($popover->ID, '_popm_cookie_days', true) ?: 7,
        'button_text' => get_post_meta($popover->ID, '_popm_button_text', true) ?: __('Open Form', 'popover-maker'),
    ];
    
    // Localize script with data
    wp_localize_script('popm-popover-js', 'popmData', $settings);
}
```

**Acceptance Criteria**:
- [ ] Popover displays on correct pages based on display location setting
- [ ] Popover respects date range (only shows when active)
- [ ] Popover respects dismissal cookie (doesn't show again if dismissed)
- [ ] Auto-open trigger works with delay
- [ ] Button trigger displays and opens popover on click
- [ ] Close button closes popover
- [ ] Clicking outside popover closes it
- [ ] ESC key closes popover
- [ ] Featured image displays on left side if set
- [ ] No featured image: content fills full width
- [ ] Mobile: image stacks on top
- [ ] Google Form iframe loads correctly
- [ ] Post content displays if no form URL set

---

## Phase 4: Analytics Tracking
**Goal**: Implement view/dismissal tracking via AJAX

### Deliverables
1. Create `analytics.php` with increment functions
2. Create `ajax.php` with AJAX handler functions
3. Verify JavaScript sends requests correctly
4. Verify analytics increment in admin

### Implementation Details

#### 4.1 Analytics Functions
File: `includes/analytics.php`

**Requirements**:
- Create functions for incrementing each metric
- Thread-safe incrementing (WordPress meta functions are generally safe)

**Functions**:

```php
/**
 * Increment view count for a popover
 * 
 * @param int $popover_id The popover post ID
 * @return bool True on success, false on failure
 */
function popm_increment_views($popover_id) {
    $current = (int) get_post_meta($popover_id, '_popm_views', true);
    return update_post_meta($popover_id, '_popm_views', $current + 1);
}

/**
 * Increment dismissal count for a popover
 * 
 * @param int $popover_id The popover post ID
 * @return bool True on success, false on failure
 */
function popm_increment_dismissals($popover_id) {
    $current = (int) get_post_meta($popover_id, '_popm_dismissals', true);
    return update_post_meta($popover_id, '_popm_dismissals', $current + 1);
}

/**
 * Get all analytics for a popover
 * 
 * @param int $popover_id The popover post ID
 * @return array Analytics data
 */
function popm_get_analytics($popover_id) {
    $views = (int) get_post_meta($popover_id, '_popm_views', true);
    $dismissals = (int) get_post_meta($popover_id, '_popm_dismissals', true);
    
    return [
        'views' => $views,
        'dismissals' => $dismissals,
        'dismissal_rate' => $views > 0 ? round(($dismissals / $views) * 100, 2) : 0,
    ];
}

/**
 * Calculate dismissal rate for a popover
 * 
 * @param int $popover_id The popover post ID
 * @return float Dismissal rate percentage
 */
function popm_calculate_dismissal_rate($popover_id) {
    $views = (int) get_post_meta($popover_id, '_popm_views', true);
    $dismissals = (int) get_post_meta($popover_id, '_popm_dismissals', true);
    
    if ($views === 0) {
        return 0;
    }
    
    return round(($dismissals / $views) * 100, 2);
}
```

**Note**: Click tracking function excluded from MVP. See "Future Enhancements" for implementation via postMessage API.

**Thread Safety Note**: WordPress meta functions are generally safe, but for high-traffic sites, consider using `$wpdb->query()` with MySQL's `ON DUPLICATE KEY UPDATE` pattern. Start simple, optimize if needed.

#### 4.2 AJAX Handler Functions
File: `includes/ajax.php`

**Requirements**:
- Create AJAX handler functions for tracking
- Hooks registered in main plugin file for both logged-in and logged-out users
- Verify nonces
- Call analytics functions
- Return JSON responses

**AJAX Actions** (registered in popover-maker.php):
1. `wp_ajax_popm_track_view` (logged in)
2. `wp_ajax_nopriv_popm_track_view` (logged out)
3. `wp_ajax_popm_track_dismissal` (logged in)
4. `wp_ajax_nopriv_popm_track_dismissal` (logged out)

**Note**: Click tracking actions excluded from MVP due to iframe cross-origin restrictions.

**Handler Functions**:

```php
/**
 * Handle AJAX request to track popover view
 */
function popm_ajax_track_view() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'popm_tracking')) {
        wp_send_json_error(['message' => __('Invalid nonce', 'popover-maker')]);
    }
    
    // Get and validate popover ID
    $popover_id = isset($_POST['popover_id']) ? intval($_POST['popover_id']) : 0;
    
    if ($popover_id <= 0) {
        wp_send_json_error(['message' => __('Invalid popover ID', 'popover-maker')]);
    }
    
    if (get_post_type($popover_id) !== 'popm_popover') {
        wp_send_json_error(['message' => __('Invalid popover', 'popover-maker')]);
    }
    
    // Increment view count
    $result = popm_increment_views($popover_id);
    
    if ($result) {
        wp_send_json_success([
            'message' => __('View tracked', 'popover-maker'),
            'popover_id' => $popover_id,
        ]);
    } else {
        wp_send_json_error(['message' => __('Failed to track view', 'popover-maker')]);
    }
}

/**
 * Handle AJAX request to track popover dismissal
 */
function popm_ajax_track_dismissal() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'popm_tracking')) {
        wp_send_json_error(['message' => __('Invalid nonce', 'popover-maker')]);
    }
    
    // Get and validate popover ID
    $popover_id = isset($_POST['popover_id']) ? intval($_POST['popover_id']) : 0;
    
    if ($popover_id <= 0) {
        wp_send_json_error(['message' => __('Invalid popover ID', 'popover-maker')]);
    }
    
    if (get_post_type($popover_id) !== 'popm_popover') {
        wp_send_json_error(['message' => __('Invalid popover', 'popover-maker')]);
    }
    
    // Increment dismissal count
    $result = popm_increment_dismissals($popover_id);
    
    if ($result) {
        wp_send_json_success([
            'message' => __('Dismissal tracked', 'popover-maker'),
            'popover_id' => $popover_id,
        ]);
    } else {
        wp_send_json_error(['message' => __('Failed to track dismissal', 'popover-maker')]);
    }
}
```

**Rate Limiting Consideration**:
- Track only once per page load (JavaScript should prevent duplicate calls)
- Could add transient-based rate limiting per IP if abuse is concern
- Start without rate limiting, add if needed

#### 4.3 JavaScript Tracking Updates
File: `assets/js/popover.js`

**Requirements**:
- Track view when popover opens (once per page load)
- Track dismissal when close button clicked

**View Tracking**:
```javascript
let viewTracked = false;
function openPopover(popoverId) {
    // ... existing open logic ...
    
    if (!viewTracked) {
        trackAction('view', popoverId);
        viewTracked = true;
    }
}
```

**Click Tracking**:
- **Excluded from MVP** due to cross-origin iframe restrictions
- Cannot detect user interactions inside embedded iframes
- See "Future Enhancements" section for postMessage-based implementation
- If form provider supports it in future, can add via postMessage API

**Dismissal Tracking**:
```javascript
function closePopover(popoverId, isDismissal) {
    // ... existing close logic ...
    
    if (isDismissal) {
        setCookie(...);
        trackAction('dismissal', popoverId);
    }
}
```

#### 4.4 Admin Display Updates
File: `includes/meta-boxes.php`

**Update Analytics Meta Box Render Function**:
- Use `popm_get_analytics()` function to fetch current values
- Display formatted with icons
- Handle division by zero for dismissal rate
- Note displayed: "Click tracking not available in MVP (iframe limitation)"

**Function Update**:
```php
/**
 * Render analytics meta box (read-only display)
 * 
 * @param WP_Post $post The current post object
 */
function popm_render_analytics_meta_box($post) {
    $analytics = popm_get_analytics($post->ID);
    
    echo '<div class="popm-analytics-display">';
    echo '<p><strong>👁 ' . __('Views:', 'popover-maker') . '</strong> ' . number_format($analytics['views']) . '</p>';
    echo '<p><strong>❌ ' . __('Dismissals:', 'popover-maker') . '</strong> ' . number_format($analytics['dismissals']) . '</p>';
    echo '<p><strong>📊 ' . __('Dismissal Rate:', 'popover-maker') . '</strong> ' . $analytics['dismissal_rate'] . '%</p>';
    echo '<p class="description">' . __('Analytics reset when popover is duplicated.', 'popover-maker') . '</p>';
    echo '<p class="description">' . __('Click tracking not available in MVP (iframe limitation).', 'popover-maker') . '</p>';
    echo '</div>';
}
```

**Acceptance Criteria**:
- [ ] Opening popover increments view count
- [ ] Dismissing popover increments dismissal count (only if cookie_days > 0)
- [ ] Analytics display correctly in admin meta box
- [ ] No duplicate tracking (view tracked once per page load)
- [ ] AJAX requests succeed (check browser console)
- [ ] Analytics persist across page reloads
- [ ] Cookie days = 0 does NOT set cookie (popover always shows)

---

## Phase 5: Polish & Refinements
**Goal**: Add finishing touches, accessibility, admin UI improvements

### Deliverables
1. Admin CSS for better meta box styling
2. Admin JS for conditional field visibility
3. Accessibility improvements (focus trap, ARIA)
4. Preview mode for testing
5. Admin notices for helpful feedback

### Implementation Details

#### 5.1 Admin Styling
File: `assets/css/admin.css`

**Requirements**:
- Style meta boxes for better visual hierarchy
- Add icons/colors for different field groups
- Style analytics display (cards or dashboard-style)
- Responsive admin layout

**Enqueue Function**:
```php
/**
 * Enqueue admin assets on popover edit screen
 * 
 * @param string $hook The current admin page hook
 */
function popm_enqueue_admin_assets($hook) {
    // Only on popover edit screen
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'popm_popover') {
        return;
    }
    
    // Enqueue admin CSS
    wp_enqueue_style(
        'popm-admin-css',
        POPM_PLUGIN_URL . 'assets/css/admin.css',
        [],
        POPM_VERSION
    );
    
    // Enqueue admin JS
    wp_enqueue_script(
        'popm-admin-js',
        POPM_PLUGIN_URL . 'assets/js/admin.js',
        ['jquery'],
        POPM_VERSION,
        true
    );
}
// Hook registered in main plugin file: add_action('admin_enqueue_scripts', 'popm_enqueue_admin_assets');
```

#### 5.2 Admin JavaScript
File: `assets/js/admin.js`

**Requirements**:
- Show/hide fields based on trigger type selection
  - If 'auto': show delay field
  - If 'button': show button text field
  - If 'both': show both
- Date/time picker enhancements (use WordPress core if available)
- Real-time validation feedback (optional)

**Conditional Fields**:
```javascript
jQuery(document).ready(function($) {
    const $triggerType = $('#popm_trigger_type');
    const $delayField = $('.popm-delay-field');
    const $buttonTextField = $('.popm-button-text-field');
    
    function toggleFields() {
        const type = $triggerType.val();
        $delayField.toggle(type === 'auto' || type === 'both');
        $buttonTextField.toggle(type === 'button' || type === 'both');
    }
    
    $triggerType.on('change', toggleFields);
    toggleFields(); // Initial state
});
```

#### 5.3 Accessibility Enhancements
File: `assets/js/popover.js`

**Requirements**:

1. **Focus Trap**:
   - When popover opens, trap focus inside
   - Tab cycles through close button and focusable content
   - Shift+Tab cycles backward
   - Implementation: Track focusable elements, override tab behavior

2. **Focus Return**:
   - Store reference to element that triggered popover
   - On close, return focus to trigger button

3. **ARIA Attributes**:
   - Overlay: `role="dialog"` `aria-modal="true"` `aria-labelledby="popm-title-{id}"`
   - Close button: `aria-label="Close popover"`
   - Hidden state: `aria-hidden="true"` when not active

4. **Keyboard Navigation**:
   - ESC closes popover (already implemented)
   - Enter on trigger button opens popover

5. **Screen Reader Announcements**:
   - Consider adding live region for status updates
   - "Popover opened" / "Popover closed" announcements

#### 5.4 Preview Mode
File: `includes/display.php` (already implemented in `popm_get_active_popover()`)

**Requirements**:
- Add `?popm_preview=123` URL parameter support
- Only works for logged-in admins/editors
- Bypasses all display rules, date ranges, cookies
- Shows popover with "PREVIEW MODE" banner

**Implementation** (already in popm_get_active_popover()):
```php
// Preview mode for admins (at start of function)
if (current_user_can('edit_posts') && isset($_GET['popm_preview'])) {
    $preview_id = intval($_GET['popm_preview']);
    if (get_post_type($preview_id) === 'popm_popover') {
        return get_post($preview_id); // Force this popover
    }
}
```

**Admin UI Addition**:
Add preview button to edit screen via meta box or admin column

```php
/**
 * Add preview button to popover edit screen
 */
function popm_add_preview_button() {
    global $post;
    
    if (get_post_type($post) !== 'popm_popover') {
        return;
    }
    
    $preview_url = home_url('?popm_preview=' . $post->ID);
    
    echo '<div class="misc-pub-section">';
    echo '<a href="' . esc_url($preview_url) . '" target="_blank" class="button">';
    echo __('Preview Popover', 'popover-maker');
    echo '</a>';
    echo '</div>';
}
add_action('post_submitbox_misc_actions', 'popm_add_preview_button');
```

#### 5.5 Admin Notices
File: `includes/meta-boxes.php` (add to this file)

**Add Helpful Notices Function**:

```php
/**
 * Display admin notices for validation errors and success messages
 */
function popm_admin_notices() {
    // Check for validation errors
    if (isset($_GET['popm_error'])) {
        $error = sanitize_text_field($_GET['popm_error']);
        
        switch ($error) {
            case 'invalid_dates':
                echo '<div class="notice notice-error"><p>' . 
                     __('End date must be after start date. Please correct the scheduling.', 'popover-maker') . 
                     '</p></div>';
                break;
                
            case 'no_content':
                echo '<div class="notice notice-warning"><p>' . 
                     __('This popover has no form URL and no content. Please add one or the other.', 'popover-maker') . 
                     '</p></div>';
                break;
        }
    }
    
    // Success notice after first save
    if (isset($_GET['popm_success']) && $_GET['popm_success'] === 'first_save') {
        echo '<div class="notice notice-success"><p>' . 
             __('Popover created! Use the Preview button to test it before publishing.', 'popover-maker') . 
             '</p></div>';
    }
}
add_action('admin_notices', 'popm_admin_notices');
```

**Trigger Notices in Save Function**:
Add redirect with error parameter when validation fails in `popm_save_meta()`

#### 5.6 Body Scroll Lock
File: `assets/css/popover.css`

**Add CSS**:
```css
body.popm-active {
    overflow: hidden;
}
```

**JavaScript**:
```javascript
function openPopover() {
    // ... existing code ...
    document.body.classList.add('popm-active');
}

function closePopover() {
    // ... existing code ...
    document.body.classList.remove('popm-active');
}
```

#### 5.7 Click Tracking Enhancement
**Challenge**: Clicks inside iframes can't be detected due to same-origin policy

**Solutions**:
1. **Track on form submission** (if possible to detect)
2. **Track overlay/close interactions** as proxy
3. **Use postMessage** if form provider supports it
4. **Skip for now**, mark as known limitation

**Recommendation**: Skip true click tracking for MVP, revisit in future with postMessage approach

**Acceptance Criteria**:
- [ ] Admin UI is polished and intuitive
- [ ] Conditional fields show/hide correctly
- [ ] Preview mode works for admins
- [ ] Focus trap works (keyboard users can't escape popover)
- [ ] ESC key closes popover
- [ ] Body scroll locks when popover open
- [ ] Admin notices provide helpful feedback
- [ ] Accessibility tested with screen reader (basic test)

---

## Testing Criteria

### Functional Testing

#### Admin Tests
- [ ] Popover CPT appears in menu
- [ ] Editors can create/edit popovers
- [ ] All meta boxes display correctly
- [ ] Settings save and persist
- [ ] Featured image can be set
- [ ] Analytics display (even with 0 values)
- [ ] Custom columns show in list view
- [ ] Preview button works

#### Frontend Tests
- [ ] Popover displays on correct pages (homepage/pages/posts/all)
- [ ] Popover respects date range
- [ ] Start date: doesn't show before start
- [ ] End date: doesn't show after end
- [ ] Priority system: highest priority shows first
- [ ] Tie-breaker: newest creation date wins
- [ ] Cookie dismissal: doesn't show again after dismissal
- [ ] Auto-open trigger works with delay
- [ ] Button trigger displays and functions
- [ ] Close button closes popover
- [ ] Overlay click closes popover
- [ ] ESC key closes popover

#### Display Tests
- [ ] Featured image displays left side
- [ ] No featured image: content full width
- [ ] Google Form iframe loads
- [ ] Post content displays (fallback)
- [ ] Responsive: mobile stacks image on top
- [ ] Max height: scrollbar appears if content tall
- [ ] Width setting applied correctly

#### Analytics Tests
- [ ] View increments on popover open
- [ ] Dismissal increments on close button (when cookie_days > 0)
- [ ] No duplicate tracking on same page
- [ ] Analytics display in admin
- [ ] Dismissal rate calculates correctly
- [ ] Cookie days = 0: no cookie set, popover always shows

### Edge Case Testing

#### Multiple Popovers
- [ ] Same priority, different dates: correct one shows
- [ ] Different priorities: highest shows
- [ ] All dismissed: none show
- [ ] No active popovers: nothing breaks

#### Form URL
- [ ] Google Forms URL with embedded param works
- [ ] Google Forms URL without param (auto-added)
- [ ] Invalid URL: sanitized, doesn't break
- [ ] No URL: fallback to content

#### Date Edge Cases
- [ ] Only start date: shows after start, no end
- [ ] Only end date: shows until end, no start required
- [ ] Neither date: always active
- [ ] Invalid date format: handled gracefully

#### Cookie Edge Cases
- [ ] Cookie days = 0: always shows (no cookie set)
- [ ] Cookie days = 365: respects long duration
- [ ] Cookie cleared: popover shows again
- [ ] Multiple browsers: independent cookies

#### Performance
- [ ] No popover active: minimal overhead (no query)
- [ ] 50+ popovers: query performant
- [ ] Caching plugin active: works correctly

### Browser Testing
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Chrome (Android)
- [ ] Mobile Safari (iOS)

### Accessibility Testing
- [ ] Keyboard navigation (Tab, Shift+Tab, ESC)
- [ ] Screen reader announces popover
- [ ] Focus trap works
- [ ] Focus returns to trigger on close
- [ ] ARIA attributes present
- [ ] Color contrast meets WCAG AA

---

## Edge Cases & Error Handling

### Display Logic Edge Cases

#### Case 1: User Changes Display Location
**Scenario**: Popover is published with "Homepage Only", admin changes to "Pages Only" while popover is live

**Expected**: Next page load on pages shows popover, homepage stops showing it

**Implementation**: No caching of query results, always re-query on page load

#### Case 2: Clock Skew
**Scenario**: Server time and user time differ

**Implementation**: Always use server time (`current_time()`) for date comparisons

#### Case 3: Timezone Confusion
**Scenario**: Admin sets dates in local timezone, server is UTC

**Implementation**: Use WordPress's timezone settings (`current_time()` respects wp_timezone_string)

#### Case 4: Overlapping Date Ranges
**Scenario**: Two popovers with same priority, overlapping active dates

**Expected**: Newest creation date wins

**Implementation**: Already handled by `post_date DESC` in query

### Analytics Edge Cases

#### Case 1: Rapid Clicks
**Scenario**: User dismisses and re-opens via button repeatedly

**Expected**: Each view counted, each dismissal counted

**Implementation**: JavaScript flag prevents duplicate tracking per page load

#### Case 2: AJAX Failure
**Scenario**: AJAX request fails (network error, server error)

**Expected**: User experience not impacted, analytics silently fail

**Implementation**: No error alerts, console.log only

#### Case 3: Concurrent Updates
**Scenario**: Multiple users viewing/dismissing same popover simultaneously

**Expected**: All counts eventually consistent

**Implementation**: WordPress meta updates are atomic, should be safe

### Form Submission Edge Cases

#### Case 1: Google Form Submission
**Scenario**: User submits form inside iframe

**Expected**: Click tracking doesn't fire (can't detect inside iframe)

**Implementation**: Skip click tracking for now, document limitation

#### Case 2: Form Redirects
**Scenario**: Google Form redirects after submission

**Expected**: User leaves page, popover closes naturally

**Implementation**: No special handling needed

#### Case 3: Embedded Form Errors
**Scenario**: Google Form URL is invalid or form deleted

**Expected**: Iframe shows error, popover still functional

**Implementation**: No validation of form existence, trust admin input

### Cookie Edge Cases

#### Case 1: Cookie Blocked by Browser
**Scenario**: User has cookies disabled

**Expected**: Popover shows every page load

**Implementation**: Check cookie existence, assume not set if unreadable

#### Case 2: Cookie Cleared
**Scenario**: User clears cookies

**Expected**: Popover shows again

**Implementation**: No server-side tracking, purely cookie-based

#### Case 3: Incognito Mode
**Scenario**: User in private browsing

**Expected**: Cookie set for session, cleared on browser close

**Implementation**: Standard cookie behavior, no special handling

### Admin UI Edge Cases

#### Case 1: No Featured Image
**Scenario**: User doesn't set featured image

**Expected**: Popover content full width, no image section

**Implementation**: Template checks `if (!empty($image_url))`

#### Case 2: No Form URL, No Content
**Scenario**: User saves popover with both empty

**Expected**: Admin notice, but allows saving (may want to add content later)

**Implementation**: Show notice, don't block save

#### Case 3: Duplicate Popover
**Scenario**: User duplicates existing popover

**Expected**: Analytics reset to 0, settings copied

**Implementation**: WordPress handles duplication, analytics auto-initialize to 0

---

## WordPress Best Practices

### Code Standards
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Use WordPress functions over PHP native when available
- Prefix all functions, classes, and hooks with `popm_` or `POPM_`
- Use `esc_` functions for output, `sanitize_` for input

### Security
- Nonce verification for all form submissions
- Capability checks for admin actions
- Escape all output (`esc_html`, `esc_attr`, `esc_url`)
- Sanitize all input (`sanitize_text_field`, `sanitize_url`, etc.)
- Validate AJAX requests (nonce + capability)
- Use prepared statements if custom database queries (though not needed here)

### Performance
- Query efficiently (one query for all popovers, filter in PHP)
- Only enqueue assets when needed (check before enqueuing)
- Use transients for expensive operations (not needed in MVP)
- Minimize inline JavaScript (localize data, keep logic in external file)

### Internationalization
- Wrap all strings in translation functions (`__()`, `_e()`, `esc_html__()`)
- Text domain: `'popover-maker'`
- Load text domain on `plugins_loaded` hook
- Prepare for future translation

### Hooks & Filters
**Actions Provided** (for extensibility):
- `popm_before_popover_display` - Fires before rendering popover
- `popm_after_popover_display` - Fires after rendering popover
- `popm_track_view` - Fires when view tracked
- `popm_track_dismissal` - Fires when dismissal tracked

**Filters Provided**:
- `popm_query_args` - Filter WP_Query args for popovers
- `popm_popover_content` - Filter content before display
- `popm_should_display` - Filter whether to display popover (return bool)

**Use WordPress Hooks**:
- `init` for CPT registration
- `add_meta_boxes` for meta boxes
- `save_post_{post_type}` for saving meta
- `wp_footer` for frontend injection
- `admin_enqueue_scripts` for admin assets
- `wp_enqueue_scripts` for frontend assets

---

## Implementation Roadmap

### Phase 1: Foundation (Estimated: 2-3 hours)
**Tasks**:
1. Update main plugin file
2. Create and implement post type registration functions
3. Add custom admin columns functions
4. Test: Verify CPT appears and is accessible

**Files to Create/Modify**:
- `popover-maker.php` (modify - add requires and hook registrations)
- `includes/post-type.php` (create - all CPT functions)

**Testing Checklist**:
- [ ] Plugin activates without errors
- [ ] "Popovers" menu appears
- [ ] Can create new popover
- [ ] Custom columns display

---

### Phase 2: Meta Boxes (Estimated: 4-5 hours)
**Tasks**:
1. Create meta box functions file
2. Implement all render functions for each meta box
3. Implement save function with validation
4. Add admin styles (optional)

**Files to Create/Modify**:
- `includes/meta-boxes.php` (create - all meta box functions)
- `assets/css/admin.css` (create)
- `popover-maker.php` (modify to add meta box hooks)

**Testing Checklist**:
- [ ] All meta boxes display
- [ ] Settings save correctly
- [ ] Validation works
- [ ] Settings persist after reload

---

### Phase 3: Frontend Display (Estimated: 5-6 hours)
**Tasks**:
1. Create display functions file
2. Implement query and filter logic functions
3. Create popover template
4. Create frontend CSS
5. Create frontend JavaScript
6. Wire up enqueues

**Files to Create/Modify**:
- `includes/display.php` (create - all display functions)
- `templates/popover-template.php` (create)
- `assets/css/popover.css` (create)
- `assets/js/popover.js` (create)
- `popover-maker.php` (modify to add display hooks)

**Testing Checklist**:
- [ ] Popover displays on correct pages
- [ ] Date range filtering works
- [ ] Cookie dismissal works
- [ ] Auto-open and button triggers work
- [ ] Responsive design works

---

### Phase 4: Analytics (Estimated: 2-3 hours)
**Tasks**:
1. Create analytics functions file
2. Create AJAX handler functions file
3. Update JavaScript to send tracking requests
4. Update admin analytics display

**Files to Create/Modify**:
- `includes/analytics.php` (create - all analytics functions)
- `includes/ajax.php` (create - all AJAX handlers)
- `assets/js/popover.js` (modify)
- `includes/meta-boxes.php` (modify analytics render function)
- `popover-maker.php` (modify to add AJAX hooks)

**Testing Checklist**:
- [ ] View tracking works
- [ ] Dismissal tracking works
- [ ] Analytics display in admin
- [ ] No duplicate tracking

---

### Phase 5: Polish (Estimated: 3-4 hours)
**Tasks**:
1. Add admin JavaScript for conditional fields
2. Improve admin styling
3. Add accessibility features
4. Add preview mode function
5. Add admin notices function
6. Final testing and bug fixes

**Files to Create/Modify**:
- `assets/js/admin.js` (create)
- `assets/css/admin.css` (modify)
- `assets/js/popover.js` (modify for accessibility)
- `includes/display.php` (modify - add preview button function)
- `includes/meta-boxes.php` (modify - add admin notices function)

**Testing Checklist**:
- [ ] All polish features working
- [ ] Accessibility tested
- [ ] Cross-browser tested
- [ ] No console errors

---

## Future Enhancements (Post-MVP)

### Click Tracking (High Priority)
**Problem**: Cross-origin iframe restrictions prevent detecting user clicks inside embedded forms

**Solution**:
- Implement postMessage API communication
- Form provider (Google Forms, Typeform, etc.) must support postMessage
- Listen for form submission/interaction messages from iframe
- Track as `_popm_clicks` or `_popm_form_submissions`
- Alternative: Use form provider's API webhooks if available
- Calculate true conversion rate: (submissions / views * 100)

### Form Provider Templates
- Add Typeform template
- Add Mailchimp template
- Add WPForms template
- Add Gravity Forms template
- Settings: "Form Provider" dropdown expands

### Advanced Display Rules
- Display on specific posts/pages (multi-select)
- Display by category/tag
- Display by user role
- Display by custom conditional logic
- Exclude pages/posts

### Trigger Enhancements
- Exit intent trigger
- Scroll depth trigger (e.g., 50% down page)
- Time on page trigger (e.g., after 30 seconds)
- Click trigger on specific element (CSS selector)

### A/B Testing
- Multiple popovers rotate randomly
- Track conversions per variant
- Declare winner based on conversion rate

### Analytics Dashboard
- Separate admin page with charts
- Date range filtering
- Export to CSV
- Comparison between popovers

### Integrations
- Google Analytics event tracking
- Facebook Pixel integration
- Zapier webhooks on form submission
- CRM integrations (HubSpot, Salesforce)

### Styling Options
- Color pickers for overlay, close button
- Animation options (fade, slide, zoom)
- Custom CSS field for advanced users
- Image position options (left, right, top, background)

---

## Success Criteria

### MVP Complete When:
1. ✅ Custom post type registered and functional
2. ✅ All meta boxes working with validation
3. ✅ Popover displays on frontend based on rules
4. ✅ Priority and date range filtering works
5. ✅ Cookie dismissal respects user preference (including cookie_days = 0)
6. ✅ Google Forms embed works
7. ✅ Analytics track views and dismissals (clicks excluded - see Future Enhancements)
8. ✅ Responsive design works on mobile
9. ✅ No JavaScript errors in console
10. ✅ Accessibility basics implemented (keyboard, ARIA)

### Quality Checklist:
- [ ] Code follows WordPress standards
- [ ] All user inputs sanitized
- [ ] All outputs escaped
- [ ] Nonces used for all forms/AJAX
- [ ] Capability checks for admin actions
- [ ] Text domain loaded for translations
- [ ] No PHP warnings/errors
- [ ] Works with popular themes (test 2-3)
- [ ] Works with common plugins (caching, SEO)
- [ ] Commented code for maintainability

---

## Notes for Claude Code

### Implementation Philosophy
- **Build incrementally**: Complete one phase fully before moving to next
- **Test after each phase**: Verify functionality before proceeding
- **Write clean code**: Follow WordPress standards, comment complex logic
- **Handle errors gracefully**: Never break the site, fail silently with logging
- **Think about users**: Both admins creating popovers and end users viewing them

### When Stuck
- Consult WordPress Codex for proper function usage
- Reference existing WordPress plugins for patterns
- Use `error_log()` for debugging (never `echo` in production)
- Test in isolation before integrating

### Code Style Preferences
- Use functions organized by purpose (one file per major feature area)
- Prefix ALL functions with `popm_` to avoid naming conflicts
- Descriptive function names (`popm_get_active_popover()` not `get_popover()`)
- Comment complex logic, not obvious code
- Keep functions small and focused (single responsibility)
- Group related functions in the same file
- Use helper functions for repeated logic

### WordPress-Specific Notes
- Always use `get_post_meta()` even if you know key exists (returns empty string if not)
- Use `update_post_meta()` over `add_post_meta()` (handles both add and update)
- Use `current_time('timestamp')` not `time()` for timezone awareness
- Use `home_url()` not `site_url()` for frontend URLs
- Use `admin_url()` for admin URLs
- Enqueue CSS/JS, never hardcode in HTML

### Security Reminders
- Nonce: Every form submission, every AJAX request
- Capability: Check before any admin action
- Escape: `esc_html()`, `esc_attr()`, `esc_url()` before output
- Sanitize: `sanitize_text_field()`, `sanitize_url()`, etc. on input
- Validate: Check types, ranges, formats before saving

---

## Appendix: Quick Reference

### Post Meta Keys Reference
```
_popm_form_provider       string   'google_forms'
_popm_form_url            string   URL
_popm_display_location    string   'all'|'homepage'|'pages'|'posts'
_popm_priority            int      0-999
_popm_start_date          string   'Y-m-d H:i:s' or empty
_popm_end_date            string   'Y-m-d H:i:s' or empty
_popm_width               string   CSS value
_popm_max_height          string   CSS value
_popm_trigger_type        string   'auto'|'button'|'both'
_popm_button_text         string   Max 50 chars
_popm_auto_delay          int      0-60
_popm_cookie_days         int      0-365
_popm_views               int      Read-only
_popm_dismissals          int      Read-only

Note: _popm_clicks excluded from MVP (see Future Enhancements)
```

### WordPress Function Quick Reference
```php
// Post Type
register_post_type($name, $args)
get_post_type($post)

// Meta
get_post_meta($post_id, $key, $single)
update_post_meta($post_id, $key, $value)
delete_post_meta($post_id, $key)

// Queries
new WP_Query($args)
is_front_page()
is_home()
is_page()
is_single()

// Time
current_time('timestamp')
current_time('mysql')

// Security
wp_create_nonce($action)
wp_verify_nonce($nonce, $action)
current_user_can($capability)

// Sanitization
sanitize_text_field($str)
sanitize_url($url)
esc_url_raw($url)
intval($value)

// Escaping
esc_html($text)
esc_attr($text)
esc_url($url)

// Enqueues
wp_enqueue_style($handle, $src, $deps, $ver)
wp_enqueue_script($handle, $src, $deps, $ver, $in_footer)
wp_localize_script($handle, $object_name, $data)

// AJAX
wp_ajax_{action}
wp_ajax_nopriv_{action}
wp_send_json_success($data)
wp_send_json_error($data)

// URLs
home_url($path)
admin_url($path)
plugin_dir_url(__FILE__)
plugin_dir_path(__FILE__)
```

### CSS Class Reference
```css
.popm-overlay          /* Full viewport overlay */
.popm-popup            /* Modal container */
.popm-image            /* Featured image section */
.popm-content          /* Content/form section */
.popm-close            /* Close button */
.popm-trigger-button   /* Trigger button */
.popm-iframe           /* Form iframe */
.active                /* Modifier: visible state */
.popm-active           /* Body class when popover open */
```

### JavaScript Function Reference
```javascript
initPopover()           // Initialize popover on page load
openPopover(id)         // Open popover
closePopover(id, isDismissal)  // Close popover
setCookie(name, value, days)   // Set cookie
trackAction(action, id)        // Send analytics AJAX
```

---

## Document Version History
- **v2.2** (2024-12-03): Revised to functional programming architecture
  - Removed all class-based patterns in favor of functions
  - Simplified file structure (no "class-" prefixes)
  - All functions use `popm_` prefix for namespace protection
  - Updated all code examples to functional style
  - Simplified initialization (just require files and register hooks)
  - Main plugin file directly registers all hooks
  - No object instantiation needed
  - Easier to debug with direct function calls
- **v2.1** (2024-12-03): Revised based on technical review
  - Fixed date format terminology: MySQL datetime (Y-m-d H:i:s), not ISO 8601
  - Removed `_popm_clicks` from MVP scope due to iframe cross-origin restrictions
  - Clarified homepage detection: `is_front_page()` only, NOT `is_home()`
  - Added explicit JavaScript check: only set cookie if `cookie_days > 0`
  - Documented 'all' display location includes archives, search, 404, custom post types
  - Added performance note: consider meta_query optimization for 100+ popovers
  - Emphasized priority tiebreaker behavior (newest creation date wins) is intentional
  - Updated all analytics references to remove click tracking
  - Added click tracking to Future Enhancements with postMessage solution
- **v2.0** (2024-12-03): Initial specification created for Custom Post Type architecture

---

**END OF SPECIFICATION**

This document should provide Claude Code with everything needed to implement the Popover Maker plugin systematically. Each phase builds on the previous one with clear deliverables and testing criteria. The specification is detailed enough to avoid ambiguity but flexible enough to allow for implementation decisions where appropriate.
