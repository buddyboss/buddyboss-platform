# Phase 4: Meta API Foundation + Taxonomy — Research

**Researched:** 2026-03-17
**Domain:** WordPress custom meta API (non-CPT), WordPress taxonomy registration with non-CPT object IDs, BuddyBoss BP_Component metadata patterns, taxonomy privacy filters
**Confidence:** HIGH — based on direct inspection of live BuddyBoss Platform source code and WordPress core

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| TAX-01 | Organizer can assign one or more hierarchical categories (with optional icon) to an event — categories are filterable on the event directory | register_taxonomy('bb_event_category'), wp_set_object_terms(), term meta for icons, REST endpoint + JS filter UI in index.php |
| TAX-02 | Organizer can assign free-form tags to an event — tags are searchable and displayed on the event page | register_taxonomy('bb_event_tag'), wp_set_object_terms(), tag display on single event template |
| TAX-03 | Public category archive pages (/event-category/[slug]/) list events in that category — private group events are never surfaced regardless of category assignment | pre_get_posts privacy filter applied at taxonomy registration time; see Pitfall 1 below |
</phase_requirements>

---

## Summary

Phase 4 has two distinct deliverables that must ship together: the `bb_eventmeta` PHP meta API and the WordPress taxonomy system for event categories and tags.

**Meta API:** The `bb_eventmeta` table was created in Phase 1 but has no PHP API. Every downstream phase (5, 6, 7, 8) will call `bp_event_get_meta()` / `bp_event_update_meta()`. Direct codebase inspection of the live BuddyBoss Platform source reveals the exact implementation pattern: the events component's `setup_globals()` must pass a `meta_tables` array (`['event' => $wpdb->prefix . 'bb_eventmeta']`) to `parent::setup_globals()`. This registers `$wpdb->eventmeta` and enables WordPress core `get_metadata('event', ...)` / `update_metadata('event', ...)` calls. Wrapper functions (`bp_event_get_meta()` etc.) must also apply the `bp_filter_metaid_column_name` query filter — exactly as BuddyBoss groups do — because the `bb_eventmeta` table uses `id` as its primary key column (not `meta_id`, which is what WordPress expects). ARCHITECTURE.md's reference to `bp_get_meta()` is incorrect: no such function exists in BuddyBoss Platform. The groupmeta API uses `get_metadata()`/`update_metadata()` directly, with the query filter applied.

**Taxonomy:** `wp_set_object_terms()` accepts any integer `object_id` with no FK validation against `wp_posts` — confirmed from WordPress core source (`taxonomy.php` line 2819: `$object_id = (int) $object_id`, then direct `$wpdb->insert` into `wp_term_relationships`). Registering taxonomies against a string object type (`'bb_event'`) and using event IDs as `object_id` values works correctly on the installed WordPress version. The privacy filter (`pre_get_posts` exclusion of private group events from public archive pages) must be applied the moment the taxonomy is registered — it cannot be retrofitted later without a site security incident.

**Primary recommendation:** Implement the meta API first (it requires a change to `setup_globals()` which is part of component boot), then register taxonomies with the privacy filter applied inline, then extend `bp_events_get_events()` for category/tag filtering, then build the admin category management UI with icon support.

---

## Standard Stack

### Core

| Library / API | Version | Purpose | Why Standard |
|---------------|---------|---------|--------------|
| WordPress `get_metadata()` / `update_metadata()` | WP 6.x (installed) | Backend for the bb_eventmeta PHP API | The same functions used by BuddyBoss groups; proven stable; avoids re-inventing a meta cache layer |
| `bp_filter_metaid_column_name` query filter | BuddyBoss Platform (installed) | Rewrites `meta_id` → `id` in SQL queries against custom BP meta tables | Required because bb_eventmeta uses `id` as PK, not `meta_id`; already exists as a BP core function |
| `register_taxonomy()` | WP 6.x | Register `bb_event_category` and `bb_event_tag` | Native WP taxonomy registration; handles archive URLs, REST, admin UI |
| `wp_set_object_terms()` | WP 6.x | Assign taxonomy terms to events using event ID as object_id | Confirmed: no FK validation against wp_posts in WP core source; used by BuddyBoss group types |
| `add_term_meta()` / `update_term_meta()` | WP 4.4+ | Store category icon attachment ID as term meta | Native WP term meta API, stable since WP 4.4 |
| `pre_get_posts` action hook | WP 6.x | Filter public taxonomy archive pages to exclude private group events | The only place where the privacy filter can intercept WP_Query-based archive pages |

### Supporting

| Library / API | Version | Purpose | When to Use |
|---------------|---------|---------|-------------|
| `wp_media_uploader` JS (bundled with WP) | WP 6.x | Category icon upload UI on term edit screen | Only on `{taxonomy}_edit_form_fields` and `{taxonomy}_add_form_fields` admin screens |
| `bp_events_get_events_where_clauses` filter | Custom (existing in codebase) | Add taxonomy JOIN + WHERE to `bp_events_get_events()` | When filtering the event directory by category or tag |
| `wp_get_object_terms()` | WP 6.x | Read taxonomy terms assigned to an event | On single event page and REST endpoint response |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `get_metadata('event', ...)` | Raw `$wpdb->get_var()` queries | Custom queries are more lines of code and bypass WP meta cache; get_metadata integrates with the object cache automatically |
| `pre_get_posts` privacy filter | `publicly_queryable => false` on taxonomy | Setting `publicly_queryable => false` disables ALL archive pages — loses the TAX-03 feature entirely; pre_get_posts is the correct approach |
| Term meta for category icon | Separate custom table | Term meta is a native WP API, sufficient for a single icon field per term |

**No new npm packages required.** No new PHP libraries required. The existing vanilla JS wizard handles the category/tag selector UI via added step.

---

## Architecture Patterns

### Critical Finding: Correct Meta API Pattern (from Live Source Inspection)

**ARCHITECTURE.md contains an error.** It references `bp_get_meta()` which does not exist in BuddyBoss Platform. The actual groupmeta implementation uses WordPress core `get_metadata()` / `update_metadata()` with a `'group'` object type. The meta table is registered by passing `meta_tables` to `parent::setup_globals()`.

**Two changes required to the existing codebase:**

#### Change 1: Add `meta_tables` to `setup_globals()` in `class-bp-events-component.php`

```php
// Source: direct inspection of buddyboss-platform/bp-groups/classes/class-bp-groups-component.php lines 265-269
// and buddyboss-platform/bp-core/classes/class-bp-component.php lines 752-754

// In BP_Events_Component::setup_globals(), add meta_tables to parent::setup_globals() args:
parent::setup_globals(
    array(
        'slug'          => bp_get_events_root_slug(),
        // ... existing args ...
        'global_tables' => $global_tables,
        'meta_tables'   => array(
            'event' => $this->table_name_meta,  // $wpdb->prefix . 'bb_eventmeta'
        ),
    )
);
```

This makes `parent::setup_globals()` call `register_meta_tables()`, which sets `$wpdb->eventmeta = 'bb_eventmeta'` — enabling `get_metadata('event', ...)` to resolve the correct table.

#### Change 2: Implement wrapper functions in `bp-events-functions.php`

```php
// Source: mirrors buddyboss-platform/bp-groups/bp-groups-functions.php lines 2975-3026
// The bp_filter_metaid_column_name filter rewrites `meta_id` -> `id` because
// bb_eventmeta uses `id` as the PK column, matching the BP groupmeta pattern.

function bp_event_get_meta( $event_id, $meta_key = '', $single = true ) {
    add_filter( 'query', 'bp_filter_metaid_column_name' );
    $retval = get_metadata( 'event', $event_id, $meta_key, $single );
    remove_filter( 'query', 'bp_filter_metaid_column_name' );
    return $retval;
}

function bp_event_update_meta( $event_id, $meta_key, $meta_value, $prev_value = '' ) {
    add_filter( 'query', 'bp_filter_metaid_column_name' );
    $retval = update_metadata( 'event', $event_id, $meta_key, $meta_value, $prev_value );
    remove_filter( 'query', 'bp_filter_metaid_column_name' );
    return $retval;
}

function bp_event_add_meta( $event_id, $meta_key, $meta_value, $unique = false ) {
    add_filter( 'query', 'bp_filter_metaid_column_name' );
    $retval = add_metadata( 'event', $event_id, $meta_key, $meta_value, $unique );
    remove_filter( 'query', 'bp_filter_metaid_column_name' );
    return $retval;
}

function bp_event_delete_meta( $event_id, $meta_key = false, $meta_value = false, $delete_all = false ) {
    add_filter( 'query', 'bp_filter_metaid_column_name' );
    $retval = delete_metadata( 'event', $event_id, $meta_key, $meta_value, $delete_all );
    remove_filter( 'query', 'bp_filter_metaid_column_name' );
    return $retval;
}
```

### Pattern 2: Taxonomy Registration with Privacy Filter

```php
// Register both taxonomies at 'init' (standard WP hook for taxonomy registration).
// Use a non-public but admin-accessible object type string 'bb_event'.
// The privacy filter MUST be added to pre_get_posts at the same time.

function bp_events_register_taxonomies() {
    register_taxonomy( 'bb_event_category', 'bb_event', array(
        'hierarchical'      => true,
        'labels'            => array(
            'name'              => __( 'Event Categories', 'buddyboss' ),
            'singular_name'     => __( 'Event Category', 'buddyboss' ),
            'add_new_item'      => __( 'Add New Event Category', 'buddyboss' ),
            'edit_item'         => __( 'Edit Event Category', 'buddyboss' ),
        ),
        'show_ui'           => true,       // Admin UI
        'show_in_rest'      => true,       // REST API access for admin
        'show_admin_column' => false,      // No post list column (not CPT)
        'public'            => true,       // Archive URLs generated
        'publicly_queryable' => true,      // Required for /event-category/ URLs
        'rewrite'           => array( 'slug' => 'event-category' ),
    ) );

    register_taxonomy( 'bb_event_tag', 'bb_event', array(
        'hierarchical'  => false,
        'labels'        => array(
            'name'          => __( 'Event Tags', 'buddyboss' ),
            'singular_name' => __( 'Event Tag', 'buddyboss' ),
        ),
        'show_ui'       => true,
        'show_in_rest'  => true,
        'public'        => true,
        'publicly_queryable' => true,
        'rewrite'       => array( 'slug' => 'event-tag' ),
    ) );
}
add_action( 'init', 'bp_events_register_taxonomies' );

// Privacy filter — MUST be registered at the same time as taxonomies.
// Excludes events belonging to private/hidden groups from public archive pages.
function bp_events_taxonomy_privacy_filter( WP_Query $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    if ( ! $query->is_tax( array( 'bb_event_category', 'bb_event_tag' ) ) ) {
        return;
    }

    global $wpdb;
    $groups_table = $wpdb->prefix . 'bp_groups';

    // Subquery: all event IDs that belong to non-public groups.
    // Logged-out visitors and non-members see zero results for these events.
    $private_event_ids = $wpdb->get_col(
        "SELECT e.id
         FROM {$wpdb->prefix}bb_events e
         INNER JOIN {$groups_table} g ON e.group_id = g.id
         WHERE g.status != 'public'"
    );

    if ( ! empty( $private_event_ids ) ) {
        $query->set( 'post__not_in', array_map( 'intval', $private_event_ids ) );
    }
}
add_action( 'pre_get_posts', 'bp_events_taxonomy_privacy_filter' );
```

**Important note on the archive page approach:** TAX-03 requires public archive pages at `/event-category/[slug]/`. These are WP_Query-based pages that match against `wp_term_relationships.object_id`. Since events are not CPT posts, these pages will naturally return empty (no `bp_event` post type posts exist). The archive pages therefore need a different implementation — see Anti-Patterns section.

### Pattern 3: Taxonomy Assignment on Event Save

```php
// Assign categories and tags when an event is created or updated.
// This is called from BP_REST_Events_Endpoint after create_item()/update_item().

function bp_events_set_event_terms( $event_id, $category_ids = array(), $tag_ids = array() ) {
    if ( ! empty( $category_ids ) ) {
        wp_set_object_terms( $event_id, array_map( 'intval', $category_ids ), 'bb_event_category' );
    }

    if ( ! empty( $tag_ids ) ) {
        // Tags can be passed as IDs (existing) or strings (new tags)
        wp_set_object_terms( $event_id, $tag_ids, 'bb_event_tag' );
    }
}
```

### Pattern 4: Category Icon Storage and Display

```php
// Store icon on term save (hooks into term add/edit forms):
function bp_events_save_category_icon( $term_id ) {
    if ( isset( $_POST['bb_event_cat_icon_id'] ) ) {
        $icon_id = absint( $_POST['bb_event_cat_icon_id'] );
        update_term_meta( $term_id, '_bb_event_cat_icon_id', $icon_id );
    }
}
add_action( 'created_bb_event_category', 'bp_events_save_category_icon' );
add_action( 'edited_bb_event_category', 'bp_events_save_category_icon' );

// Retrieve icon URL:
function bp_event_get_category_icon_url( $term_id ) {
    $icon_id = get_term_meta( $term_id, '_bb_event_cat_icon_id', true );
    if ( ! $icon_id ) {
        return '';
    }
    return wp_get_attachment_image_url( $icon_id, 'thumbnail' );
}
```

### Pattern 5: Extending `bp_events_get_events()` for Taxonomy Filtering

The existing `bp_events_get_events_where_clauses` filter in `bp-events-functions.php` line 383 is the correct hook point. Add a new function in `bp-events-filters.php`:

```php
// Hook into the existing filter to add taxonomy-based filtering.
function bp_events_add_taxonomy_where_clauses( $where_clauses, $r ) {
    global $wpdb;

    if ( ! empty( $r['category_id'] ) ) {
        $category_id = absint( $r['category_id'] );
        $where_clauses[] = $wpdb->prepare(
            "e.id IN (
                SELECT object_id FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tt.taxonomy = 'bb_event_category'
                AND tt.term_id = %d
            )",
            $category_id
        );
    }

    if ( ! empty( $r['tag_id'] ) ) {
        $tag_id = absint( $r['tag_id'] );
        $where_clauses[] = $wpdb->prepare(
            "e.id IN (
                SELECT object_id FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tt.taxonomy = 'bb_event_tag'
                AND tt.term_id = %d
            )",
            $tag_id
        );
    }

    return $where_clauses;
}
add_filter( 'bp_events_get_events_where_clauses', 'bp_events_add_taxonomy_where_clauses', 10, 2 );
```

**Also add `category_id` and `tag_id` to the default `$r` args in `bp_events_get_events()`** so `bp_parse_args()` recognises them.

### Recommended Project Structure for Phase 4

```
src/bp-events/
├── bp-events-functions.php     MODIFIED — add meta API functions (bp_event_*_meta),
│                               add category/tag helpers, extend bp_events_get_events()
│                               defaults to accept category_id / tag_id args
├── bp-events-filters.php       MODIFIED — add bp_events_add_taxonomy_where_clauses(),
│                               add bp_events_register_taxonomies(),
│                               add bp_events_taxonomy_privacy_filter()
├── bp-events-admin.php         MODIFIED — add category icon upload metabox on
│                               {taxonomy}_add/edit_form_fields hooks
├── classes/
│   └── class-bp-events-component.php  MODIFIED — add meta_tables to setup_globals()
└── ...

src/bp-templates/bp-nouveau/readylaunch/events/
├── index.php                   MODIFIED — add category dropdown filter UI
└── create.php                  MODIFIED — add step for category/tag assignment
```

### Anti-Patterns to Avoid

- **TAX-03 archive page approach using CPT shell:** Creating a `bp_event` CPT with matching IDs to events is fragile and impossible to guarantee (IDs drift). Instead, implement the archive pages as custom WordPress template overrides that call `bp_events_get_events(['category_id' => get_queried_object_id()])` rather than relying on WP_Query's native post lookup. Register the taxonomy with `has_archive => false` or override the archive template.

- **Using `bp_get_meta()` function:** This function does not exist in BuddyBoss Platform. Any call to it will trigger a fatal. Use `get_metadata('event', ...)` wrapped in `bp_filter_metaid_column_name` as shown above.

- **Omitting the `meta_tables` registration:** Calling `get_metadata('event', ...)` without first setting `$wpdb->eventmeta` will cause WordPress to look for a table named `{prefix}eventmeta` (which does not exist). The `bb_eventmeta` table name must be registered via `setup_globals()`.

- **Registering taxonomies without the privacy filter:** Any moment between registration and filter application is a security window. Both must happen in the same function at the same hook. Do not defer the filter to a separate `init` callback.

- **Using `taxonomy_exists()` or `is_taxonomy_hierarchical()` inside `pre_get_posts`:** Both are safe calls, but the tax check using `$query->is_tax()` is the correct guard.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Meta cache management | Custom transient per meta key | WordPress object cache via `get_metadata()` | `get_metadata()` integrates with `wp_cache` automatically; `update_metadata()` invalidates the cache on write |
| Term storage | Custom event_categories column in bb_events | `wp_set_object_terms()` + `wp_term_relationships` | WP handles term counts, cache invalidation, and REST representation; `bb_events` table schema stays frozen |
| Category icon upload | Custom file upload field | WP media library (`wp.media`) | WP media library handles file validation, CDN, thumbnails, and attachment metadata out of the box |
| Tag autocomplete | Custom AJAX endpoint | WP core `wp_ajax_bp_event_tag_search` calling `get_terms()` | 10 lines vs. building a search interface |

**Key insight:** The `bb_eventmeta` table already exists and is the correct extension point for all scalar per-event data. Resist any impulse to add columns to `bb_events` — the meta table is the designated overflow valve and every downstream phase depends on the meta API being in place.

---

## Common Pitfalls

### Pitfall 1: `wp_set_object_terms()` with a Taxonomy Not Registered Against `'bb_event'`

**What goes wrong:** `wp_set_object_terms($event_id, $terms, 'bb_event_category')` returns a `WP_Error('invalid_taxonomy')` if `register_taxonomy()` has not yet run when the call is made.

**Why it happens:** REST endpoint `create_item()` runs on `rest_api_init` or later. If `register_taxonomy()` hooks to `init` with a late priority, the taxonomy may not exist when the REST handler fires.

**How to avoid:** Register taxonomies on `init` at default priority (10). The REST API is initialised on `rest_api_init`, which fires after `init`. This ordering is correct.

### Pitfall 2: `get_metadata('event', ...)` Returns Empty Because `$wpdb->eventmeta` Is Not Set

**What goes wrong:** `bp_event_get_meta($event_id, 'some_key')` returns empty string or array even though the row exists in `bb_eventmeta`. No error is thrown.

**Why it happens:** WordPress silently uses the wrong table name when `$wpdb->eventmeta` is not registered. It falls back to looking for `{prefix}eventmeta` which does not exist, or silently returns empty.

**How to avoid:** Ensure `meta_tables => ['event' => $wpdb->prefix . 'bb_eventmeta']` is passed to `parent::setup_globals()`. Verify by checking `$wpdb->eventmeta` in a test after component boot.

**Warning signs:** `bp_event_get_meta()` always returns empty even after `bp_event_update_meta()` is called. Check: `var_dump(isset($wpdb->eventmeta))` — should be `true`.

### Pitfall 3: Public Taxonomy Archive Exposing Private Group Events (TAX-03 Security Requirement)

**What goes wrong:** `/event-category/workshops/` shows events from private groups to logged-out visitors because the archive query matches `wp_term_relationships` rows regardless of group privacy.

**Why it happens:** WordPress archives use `WP_Query` which knows nothing about BuddyBoss group privacy.

**How to avoid:** The `pre_get_posts` filter (Pattern 2 above) must be applied. Additionally, verify manually: log out, visit a category archive, confirm private group events are absent. Add this as a mandatory UAT step.

### Pitfall 4: Taxonomy Archive Pages Return No Results Because Events Are Not CPT Posts

**What goes wrong:** After registering the taxonomy and assigning terms, visiting `/event-category/workshops/` returns an empty page. This is because WordPress generates archive pages by querying `wp_posts` for the `bb_event` post type — but events are stored in `bb_events`, not `wp_posts`.

**Why it happens:** `register_taxonomy('bb_event_category', 'bb_event', ...)` registers against the `bb_event` object type. WordPress's archive query looks for `post_type = 'bb_event'` posts in `wp_posts`. None exist.

**How to avoid:** Two options:

**Option A (recommended for TAX-03):** Override the taxonomy archive template. Create `taxonomy-bb_event_category.php` in the theme or use `template_include` filter. In the template, ignore `WP_Query`'s results and instead call `bp_events_get_events(['category_id' => get_queried_object_id()])` directly. This sidesteps the CPT mismatch entirely.

**Option B:** Register a `bb_event` CPT with `public => false`, `publicly_queryable => false` and `query_var => false`. The taxonomy then associates with it, but the archive URL is handled by the template override anyway.

Option A requires less code and no CPT shim. The template override approach is already established in this codebase via `bp_events_readylaunch_template_filter()`.

### Pitfall 5: Admin Category Management UI Is Missing Without CPT Context

**What goes wrong:** `register_taxonomy()` with object type `'bb_event'` correctly adds the taxonomy to the admin menu under Posts > Event Categories. But because `'bb_event'` is not a registered CPT, clicking "Event Categories" may produce an admin screen that works correctly only if the taxonomy's `show_ui => true` is set.

**How to avoid:** Test admin taxonomy management (add/edit/delete category, set icon) immediately after registration. This is straightforward — `show_ui => true` is all that is needed.

---

## Code Examples

### Verified: How BuddyBoss Groups Registers Its Meta Table

```php
// Source: buddyboss-platform/bp-groups/classes/class-bp-groups-component.php lines 265-268
// Verified from live installation at /Applications/MAMP/htdocs/buddyboss-dev/

$meta_tables = array(
    'group'  => $bp->table_prefix . 'bp_groups_groupmeta',
    'member' => $bp->table_prefix . 'bp_groups_membermeta',
);
// Passed to parent::setup_globals() as 'meta_tables' => $meta_tables
// This causes register_meta_tables() to execute:
//   $wpdb->groupmeta = 'wp_bp_groups_groupmeta'
//   $wpdb->membermeta = 'wp_bp_groups_membermeta'
```

### Verified: The `bp_filter_metaid_column_name` Function

```php
// Source: buddyboss-platform/bp-core/bp-core-filters.php lines 1040-1066
// Rewrites `meta_id` to `id` in SQL queries to match BP's custom meta table schema
// where the PK column is named `id`, not `meta_id` (which WordPress expects).
// The bb_eventmeta table already uses `id` as PK (confirmed from bp-events-functions.php line 61).
// Therefore this filter MUST be applied before any get_metadata('event', ...) call.
```

### Verified: `wp_set_object_terms()` Accepts Non-Post `object_id`

```php
// Source: /Applications/MAMP/htdocs/buddyboss-dev/wp-includes/taxonomy.php line 2819
// The function simply casts object_id to int and inserts into wp_term_relationships.
// No FK validation against wp_posts exists. Confirmed: any integer works as object_id.
$object_id = (int) $object_id;  // Line 2819 — that's the only validation
// Then: $wpdb->insert( $wpdb->term_relationships, ['object_id' => $object_id, 'term_taxonomy_id' => $tt_id] )
```

### Existing Filter Hook in `bp_events_get_events()`

```php
// Source: bp-events-functions.php line 383 (existing codebase)
// This filter is ALREADY in place — use it to add taxonomy WHERE clauses.
$where_clauses = apply_filters( 'bp_events_get_events_where_clauses', $where_clauses, $r );
```

### Existing Wizard Template Structure (create.php)

The wizard at `src/bp-templates/bp-nouveau/readylaunch/events/create.php` has 5 steps currently (Details, Date & Time, Location, Visibility, Review). The taxonomy step (Categories & Tags) should insert as Step 2 or Step 5 (before Review), renumbering existing steps. The `window.bpEventsCreate` object is the JS configuration surface — add `taxonomiesRestUrl` and initial category/tag data there.

---

## State of the Art

| Old Approach (ARCHITECTURE.md) | Corrected Approach | Impact |
|-------------------------------|-------------------|--------|
| `bp_get_meta($event_id, $key, $single, $table, 'event_id', 'bp_event_meta')` — function does not exist | `get_metadata('event', $event_id, $key, $single)` wrapped in `bp_filter_metaid_column_name` | Would cause fatal PHP error at runtime if the old pattern were used |
| No `meta_tables` in `setup_globals()` | Add `meta_tables => ['event' => table_name_meta]` to `parent::setup_globals()` args | Without this, `$wpdb->eventmeta` is never set and all meta reads/writes silently fail |

---

## Open Questions

1. **TAX-03 archive page implementation approach**
   - What we know: WordPress archive pages for non-CPT taxonomies return empty results natively
   - What's unclear: Whether to use `template_include` filter + custom template or to implement the archive as a BuddyBoss screen function at a custom URL instead
   - Recommendation: Use `template_include` filter to swap in a custom taxonomy-bb_event_category.php template that calls `bp_events_get_events(['category_id' => get_queried_object_id()])`. This is the least invasive approach and preserves the `/event-category/[slug]/` URL structure required by TAX-03.

2. **Category filter UI in the wizard**
   - What we know: The create.php wizard currently has 5 steps and a numbered step indicator in PHP
   - What's unclear: Whether to insert the category step at position 2 (after Details) or as a final step before Review
   - Recommendation: Insert before Review (new Step 5, Review becomes Step 6). This keeps the wizard's logical flow — details → dates → location → visibility → categories → review — without disrupting the existing step numbering in the JS wizard.

3. **Tag input widget**
   - What we know: The wizard uses a vanilla JS IIFE with no framework
   - Recommendation: Implement tags as a simple comma-separated text input on the creation wizard, with a styled tag token UI rendered by JS. On the REST API, accept tags as an array of strings (create if not exists) or term IDs. Use `wp_set_object_terms()` with string values — it creates new terms automatically.

---

## Validation Architecture

Nyquist validation is enabled (`workflow.nyquist_validation: true` in config.json).

### Test Framework

| Property | Value |
|----------|-------|
| Framework | PHPUnit (configured from Phase 1) |
| Config file | `buddyboss-events/phpunit.xml.dist` |
| Quick run command | `cd /Users/tom/Local\ Sites/Events/buddyboss-events && php -l src/bp-events/bp-events-functions.php` (syntax check; full PHPUnit requires WP test suite) |
| Full suite command | `phpunit --configuration phpunit.xml.dist` |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| META-API | `bp_event_get_meta()` stores and retrieves a value | unit | `phpunit --filter test_bp_event_meta_roundtrip` | ❌ Wave 0 |
| META-API | `bp_event_update_meta()` overwrites existing value | unit | `phpunit --filter test_bp_event_update_meta` | ❌ Wave 0 |
| TAX-01 | Category assigned to event is returned by `wp_get_object_terms()` | unit | `phpunit --filter test_event_category_assignment` | ❌ Wave 0 |
| TAX-01 | `bp_events_get_events(['category_id' => X])` returns only events in category X | integration | `phpunit --filter test_get_events_category_filter` | ❌ Wave 0 |
| TAX-02 | Tag assigned to event is returned by `wp_get_object_terms()` | unit | `phpunit --filter test_event_tag_assignment` | ❌ Wave 0 |
| TAX-03 | Private group event does not appear in category archive query | integration | `phpunit --filter test_taxonomy_archive_privacy` | ❌ Wave 0 |
| TAX-03 | `bp_events_taxonomy_privacy_filter()` excludes private group event IDs | unit | `phpunit --filter test_privacy_filter_excludes_private` | ❌ Wave 0 |

### Sampling Rate

- **Per task commit:** `php -l {modified_file}` — syntax verification
- **Per wave merge:** Full PHPUnit suite against WP test suite if available; otherwise `php -l` on all modified files
- **Phase gate:** All tests passing (or stubs with `markTestIncomplete`) before `/gsd:verify-work`

### Wave 0 Gaps

- [ ] `tests/test-bp-event-meta.php` — covers META-API roundtrip, update, and delete
- [ ] `tests/test-bp-event-taxonomy.php` — covers TAX-01, TAX-02 category/tag assignment
- [ ] `tests/test-bp-event-taxonomy-privacy.php` — covers TAX-03 privacy filter
- [ ] `tests/bootstrap.php` — check if existing bootstrap covers events meta type registration

---

## Sources

### Primary (HIGH confidence)

- Direct inspection of live BuddyBoss Platform installation at `/Applications/MAMP/htdocs/buddyboss-dev/wp-content/plugins/buddyboss-platform/`
  - `bp-groups/bp-groups-functions.php` lines 2935-3026 — definitive groupmeta implementation pattern
  - `bp-groups/classes/class-bp-groups-component.php` lines 265-268 — `meta_tables` registration pattern
  - `bp-core/classes/class-bp-component.php` lines 734-768 — `register_meta_tables()` implementation (sets `$wpdb->eventmeta`)
  - `bp-core/bp-core-filters.php` lines 1040-1066 — `bp_filter_metaid_column_name()` implementation

- Direct inspection of WordPress core at `/Applications/MAMP/htdocs/buddyboss-dev/wp-includes/taxonomy.php`
  - Lines 2816-2912 — `wp_set_object_terms()` — confirms no FK validation against wp_posts; accepts any integer `object_id`

- Direct inspection of existing plugin codebase:
  - `src/bp-events/bp-events-functions.php` lines 60-68 — confirms `bb_eventmeta` table schema uses `id` as PK (not `meta_id`), and `event_id` (not `object_id`) as the FK column
  - `src/bp-events/classes/class-bp-events-component.php` — confirms `meta_tables` is currently ABSENT from `setup_globals()`
  - `src/bp-events/bp-events-functions.php` line 383 — confirms `bp_events_get_events_where_clauses` filter already exists

### Secondary (MEDIUM confidence)

- ARCHITECTURE.md (prior project research, 2026-03-17) — general architecture patterns; `bp_get_meta()` reference is incorrect and corrected by live source inspection
- PITFALLS.md (prior project research, 2026-03-17) — taxonomy privacy filter importance confirmed; Pitfall 1 directly applies to this phase
- STACK.md (prior project research, 2026-03-17) — term meta for category icons pattern confirmed

### Tertiary (LOW confidence)

- None required — all critical facts verified from live source inspection.

---

## Metadata

**Confidence breakdown:**

| Area | Level | Reason |
|------|-------|--------|
| Meta API implementation pattern | HIGH | Verified from live BuddyBoss Platform source at `/Applications/MAMP/htdocs/` |
| `wp_set_object_terms()` with non-CPT object_id | HIGH | Verified from WordPress core `taxonomy.php` source |
| Taxonomy archive page implementation | MEDIUM | TAX-03 approach (template override) is the correct solution but the exact template hook implementation needs a plan task to flesh out |
| Privacy filter correctness | HIGH | Pattern documented in PITFALLS.md with BuddyBoss issue precedent; filter code is straightforward SQL |
| Admin category icon UI | HIGH | Standard WP term meta + wp.media — well-established pattern |

**Research date:** 2026-03-17
**Valid until:** 2026-06-17 (stable WP/BuddyBoss APIs — low volatility)

---

*Phase 4 Meta API + Taxonomy research. Domain: WordPress custom meta API, BP_Component pattern, taxonomy with non-CPT object IDs.*
*Researched: 2026-03-17*
