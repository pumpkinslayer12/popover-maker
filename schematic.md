# Popover Maker - Technical Specification

## 1. Overview

WordPress plugin for modal popovers with embedded Google Forms. Priority-based display, cookie dismissal, basic analytics.

**Constraints:**
- Functional programming only (no classes). All functions prefixed `popm_`
- No external dependencies (no ACF, CMB2, etc.)
- WordPress 6.0+ / PHP 7.4+



## 2. Data Model

### Custom Post Type: `popm_popover`

```php
register_post_type('popm_popover', [
    'public' => false,
    'publicly_queryable' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'capability_type' => 'post',
    'map_meta_cap' => true,
    'supports' => ['title', 'thumbnail'],
    'menu_icon' => 'dashicons-feedback',
    'rewrite' => false,
]);
```

### Post Meta Fields

| Meta Key                 | Type   | Default          | Description                                       |
| ------------------------ | ------ | ---------------- | ------------------------------------------------- |
| `_popm_form_provider`    | string | `'google_forms'` | Provider identifier (MVP: Google only)            |
| `_popm_form_url`         | string | `''`             | Form embed URL. If set, overrides post_content    |
| `_popm_display_location` | string | `'all'`          | `'homepage'` \| `'pages'` \| `'posts'` \| `'all'` |
| `_popm_priority`         | int    | `10`             | 0-999. Higher = displays first                    |
| `_popm_start_date`       | string | `''`             | MySQL datetime (`Y-m-d H:i:s`) or empty           |
| `_popm_end_date`         | string | `''`             | MySQL datetime (`Y-m-d H:i:s`) or empty           |
| `_popm_width`            | string | `'900px'`        | CSS value (px, %, vw)                             |
| `_popm_max_height`       | string | `'600px'`        | CSS value (px, %, vh)                             |
| `_popm_cookie_days`      | int    | `7`              | Days to remember dismissal (0 = always show)      |
| `_popm_views`            | int    | `0`              | Read-only. View count                             |
| `_popm_engaged`          | int    | `0`              | Read-only. Engaged count (closed after 5+ sec)    |
| `_popm_bounced`          | int    | `0`              | Read-only. Bounced count (closed within 5 sec)    |


## 3. File Structure

```
popover-maker/
├── popover-maker.php          # Plugin bootstrap, hook registration
├── readme.md
├── includes/
│   ├── post-type.php          # CPT registration, admin columns
│   ├── meta-boxes.php         # Meta box render + save functions
│   ├── display.php            # Frontend query, render, asset enqueue
│   ├── analytics.php          # Increment view/engagement counts
│   └── ajax.php               # AJAX handlers for tracking
├── assets/
│   ├── css/
│   │   ├── admin.css          # Meta box styling
│   │   └── popover.css        # Frontend popover styles
│   └── js/
│       ├── admin.js           # Conditional field show/hide
│       └── popover.js         # Open/close, cookie, AJAX tracking
└── templates/
    └── popover-template.php   # HTML structure
```


## 4. Core Algorithm

Frontend display logic (hooked to `wp_footer`, priority 999):

```
1. Skip if admin, login page, or customizer preview
2. Query all published popm_popover posts
   - ORDER BY _popm_priority DESC, post_date DESC
3. For each popover, check (in order):
   a. Display location matches current page?
      - 'homepage' → is_front_page() only (not is_home())
      - 'pages' → is_page()
      - 'posts' → is_single()
      - 'all' → true (includes archives, search, 404)
   b. Within date range?
      - Compare _popm_start_date/_popm_end_date against current_time('timestamp')
      - Empty dates = no restriction
   c. Not dismissed via cookie?
      - Cookie name: popm_dismissed_{post_id}
4. Return first match (highest priority, newest if tied)
5. Render popover HTML to footer
6. Track view via AJAX
```

**Date handling:** HTML5 `datetime-local` input → convert to MySQL format (`Y-m-d H:i:s`) on save → compare with `strtotime()` + `current_time('timestamp')`

**Priority tiebreaker:** If same priority, newest `post_date` wins. This is intentional.

---
### 4.1 AJAX Specifications
<table>
<thead>
<tr>
<th>Action Name</th>
<th>Trigger</th>
<th>Request Data</th>
<th>Response</th>
</tr>
</thead>
<tbody>
<tr>
<td><code>popm_track_view</code></td>
<td>Popover opens</td>
<td><code>{ action, nonce, popover_id }</code></td>
<td><code>{ success: true }</code></td>
</tr>
<tr>
<td><code>popm_track_close</code></td>
<td>Popover closed by user</td>
<td><code>{ action, nonce, popover_id, duration }</code></td>
<td><code>{ success: true }</code></td>
</tr>
</tbody>
</table>
Nonce action string: popm_tracking
Localized JS object (via wp_localize_script):

popmData = {
    ajaxUrl: admin_url('admin-ajax.php'),
    nonce: wp_create_nonce('popm_tracking'),
    popoverId: {id}
}
```


### **4.2 Cookie Specification**

| Attribute | Value                                   |
| --------- | --------------------------------------- |
| Name      | `popm_dismissed_{post_id}`              |
| Value     | `1`                                     |
| Expiry    | `Date.now() + (cookie_days * 86400000)` |
| Path      | `/`                                     |
| SameSite  | `Lax`                                   |


### **4.3 Responsive Breakpoint**

Breakpoint: `768px` (matches demo HTML)

Below 768px:
- `.popm-popup` switches to `flex-direction: column`
- `.popm-image` gets `min-height: 200px` (down from 400px)
- `.popm-content` padding reduces to `40px 30px`

## 5. Implementation Phases

### Phase 1: Foundation
- Update `popover-maker.php` with constants, requires, hook registration
- Create `post-type.php`: register CPT, admin columns (priority, location, active status, views)
- **Verify:** "Popovers" menu appears, editors can access

### Phase 2: Admin Meta Boxes
- Create `meta-boxes.php` with 6 meta boxes:
  1. Form Settings (provider + URL)
  2. Display Rules (location + priority)
  3. Scheduling (start/end datetime-local inputs)
  4. Layout (width + max-height)
  5. Dismissal Settings (cookie days)
  6. Analytics (read-only: views, dismissals, rate)
- Create `popm_save_meta()` with nonce check, capability check, sanitization
- **Verify:** Settings save and persist on reload

### Phase 3: Frontend Display
- Create `display.php`:
  - `popm_get_active_popover()` — query + filter logic
  - `popm_inject_popover()` — hooked to wp_footer
  - `popm_render_popover()` — loads template
  - `popm_enqueue_frontend_assets()` — conditional CSS/JS enqueue
- Create `popover-template.php`:
  - Overlay div + modal container
  - Left: featured image (if set)
  - Right: iframe (if form URL) or post_content (fallback)
  - Close button (×)
- Create `popover.css`:
  - Full viewport overlay with backdrop
  - Centered modal, responsive (stack on mobile)
  - `.active` class toggles visibility
- Create `popover.js`:
  - `openPopover()` / `closePopover()` functions
  - Auto-open on page load
  - Close on: close button click, overlay click, ESC key
  - Cookie set/check functions
- **Verify:** Popover displays based on location, dates, cookie

### Phase 4: Analytics
- Create `analytics.php`:
  - `popm_increment_views($post_id)`
  - `popm_increment_engaged($post_id)` — user closed after 5+ seconds
  - `popm_increment_bounced($post_id)` — user closed within 5 seconds
- Create `ajax.php`:
  - `popm_ajax_track_view` — verify nonce, increment, return success
  - `popm_ajax_track_close` — verify nonce, check duration, increment engaged/bounced
- Update `popover.js` to track open timestamp, send duration with close AJAX
- **Verify:** View/engagement counts increment, engagement rate displays in admin

### Phase 5: Polish
- Accessibility: focus trap, ARIA attributes, ESC key handling
- Body scroll lock when popover open (`.popm-active` class on body)
- Admin preview mode: `?popm_preview={id}` bypasses rules for admins
- Admin notices for validation errors (end date before start date)
- **Verify:** Keyboard navigation works, screen reader announces popover


## 6. Edge Cases

| Scenario                           | Expected Behavior                                                                     |
| ---------------------------------- | ------------------------------------------------------------------------------------- |
| No popover matches current page    | Nothing renders, no errors                                                            |
| Multiple popovers, same priority   | Newest creation date wins                                                             |
| All matching popovers dismissed    | Nothing renders                                                                       |
| Form URL empty, post_content empty | Popover renders empty (admin's problem)                                               |
| Invalid form URL                   | Sanitized, iframe shows error                                                         |
| cookie_days = 0                    | Never set cookie, always show popover                                                 |
| User has cookies disabled          | Popover shows every page load                                                         |
| Date fields: only start set        | Active after start, no end restriction                                                |
| Date fields: only end set          | Active until end, no start restriction                                                |
| AJAX tracking fails                | Silent failure, console.log only                                                      |
| 100+ popovers                      | Query still works (consider meta_query optimization later)                            |
| Form URL not Google Forms domain   | Save anyway (future providers), no special validation                                 |
| End date before start date         | Admin notice: "End date must be after start date." Save prevented.                    |
| Plugin uninstall                   | Delete all `popm_popover` posts and associated meta. No options table entries in MVP. |

## **6.1 Admin Notice Messages**

| Condition | Notice Type | Message |
|-----------|-------------|---------|
| End date < start date | `error` | "Popover not saved: End date must be after start date." |
| Form URL empty + content empty | `warning` | "This popover has no content to display." |
| Popover saved successfully | `success` | (Use default WordPress behavior, no custom notice) |

## 7. Acceptance Criteria

### Functional
- [ ] CPT registered, appears in admin menu
- [ ] Editors and admins can create/edit popovers
- [ ] All meta fields save correctly with validation
- [ ] Popover displays on correct pages based on display_location
- [ ] Date range filtering works (start, end, both, neither)
- [ ] Priority system works (highest wins, newest tiebreaker)
- [ ] Cookie dismissal works (respects cookie_days, 0 = always show)
- [ ] Google Forms iframe embeds correctly
- [ ] Popover auto-opens on page load
- [ ] Close button, overlay click, ESC key all close popover
- [ ] Analytics increment correctly, display in admin

### Security
- [ ] Nonce verification on all form submissions
- [ ] Nonce verification on all AJAX requests
- [ ] Capability checks before admin actions
- [ ] All inputs sanitized (`sanitize_text_field`, `esc_url_raw`, `intval`)
- [ ] All outputs escaped (`esc_html`, `esc_attr`, `esc_url`)

### Quality
- [ ] No PHP warnings/errors
- [ ] No JavaScript console errors
- [ ] Responsive design (mobile stacks image on top)
- [ ] Works with popular caching plugins
- [ ] Keyboard accessible (tab navigation, focus trap)
- [ ] Basic ARIA attributes for screen readers
- [ ] All strings wrapped in translation functions


## 8. Hooks for Extensibility

**Actions:**
- `popm_before_popover_display` — fires before render
- `popm_after_popover_display` — fires after render

**Filters:**
- `popm_query_args` — modify WP_Query args
- `popm_popover_content` — filter content before display
- `popm_should_display` — return false to prevent display


## 9. CSS Classes Reference

```
.popm-overlay          — Full viewport backdrop
.popm-popup            — Modal container
.popm-close            — Close button
.popm-image            — Featured image section
.popm-content          — Content/iframe section
.popm-iframe           — Form iframe
.active                — Visibility toggle
.popm-active           — Body class when popover open
```


## 10. Future Possible Features

### Trigger Settings
Alternative ways to open the popover beyond immediate auto-open on page load.

**Potential meta fields:**
| Meta Key | Type | Default | Description |
|----------|------|---------|-------------|
| `_popm_trigger_type` | string | `'auto'` | `'auto'` \| `'button'` \| `'both'` |
| `_popm_button_text` | string | `'Open Form'` | Button label (max 50 chars) |
| `_popm_auto_delay` | int | `2` | Seconds before auto-open (0-60) |

**Trigger types:**
- **Auto**: Popover opens automatically after configurable delay
- **Button**: A trigger button appears on page; popover opens on click
- **Both**: Auto-open with delay AND persistent button for re-opening

**Implementation notes:**
- Requires new meta box: "Trigger Settings"
- Requires `admin.js` for conditional field visibility (hide button text when type is 'auto', hide delay when type is 'button')
- Requires `.popm-trigger-button` CSS class
- `popmData` JS object would need `autoDelay` and `triggerType` properties
