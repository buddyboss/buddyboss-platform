---
phase: 04-meta-api-foundation-taxonomy
plan: 02
subsystem: api
tags: [taxonomy, rest-api, wp-terms, admin-ui, media-uploader, php]

# Dependency graph
requires:
  - phase: 04-meta-api-foundation-taxonomy/04-01
    provides: bb_event_category and bb_event_tag taxonomies registered in bp-events-filters.php

provides:
  - category_id and tag_id filtering in bp_events_get_events()
  - bp_events_add_taxonomy_where_clauses filter function in bp-events-filters.php
  - bp_events_set_event_terms(), bp_events_get_event_categories(), bp_events_get_event_tags() helpers in bp-events-functions.php
  - bp_event_get_category_icon_url() returning thumbnail URL from _bb_event_cat_icon_id term meta
  - REST /events endpoint accepts category_id/tag_id query params for filtering
  - REST create/update accepts category_ids/tags for taxonomy assignment
  - REST response includes categories[] and tags[] arrays with icon URLs
  - Admin category icon upload UI (Add/Edit screens) backed by wp.media
  - _bb_event_cat_icon_id term meta persisted via created_/edited_bb_event_category hooks
affects:
  - Phase 5 data enrichment (uses category/tag data in event cards)
  - Phase 7 front-end wizard (category/tag selectors in create event form)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - wp_set_object_terms/wp_get_object_terms with custom integer object_id for non-CPT events
    - Subquery WHERE clause injection via bp_events_get_events_where_clauses filter
    - wp.media inline script enqueued on taxonomy screens only (hook-guarded by screen->taxonomy)
    - Term meta key uses underscore prefix (_bb_event_cat_icon_id) to mark as internal/hidden

key-files:
  created: []
  modified:
    - buddyboss-events/src/bp-events/bp-events-functions.php
    - buddyboss-events/src/bp-events/bp-events-filters.php
    - buddyboss-events/src/bp-events/classes/class-bp-rest-events-endpoint.php
    - buddyboss-events/src/bp-events/bp-events-admin.php

key-decisions:
  - "wp_set_object_terms accepted for non-CPT events — BuddyBoss group types use same pattern, confirmed valid"
  - "Taxonomy WHERE clauses use subqueries (e.id IN SELECT) not JOIN — avoids duplicate rows when event matches multiple terms"
  - "category_ids/tags params in create/update use has_param() guard — allows omitting terms without clearing existing assignments (intentional design)"
  - "Category icon stored as _bb_event_cat_icon_id term meta (underscore prefix = internal meta, hidden in default WP term meta UI)"
  - "wp.media enqueued only when screen->taxonomy === bb_event_category — no JS overhead on other admin screens"

patterns-established:
  - "Taxonomy filter injection: add WHERE subquery via bp_events_get_events_where_clauses filter hook in bp-events-filters.php"
  - "Term assignment helpers: bp_events_set_event_terms() is the canonical write path; get helpers are read-only"
  - "Admin media upload: use wp_add_inline_script on media-editor handle, not a separate JS file"

requirements-completed:
  - TAX-01
  - TAX-02

# Metrics
duration: 5min
completed: 2026-03-18
---

# Phase 4 Plan 02: Taxonomy Filtering and Admin Category Icon Summary

**Taxonomy WHERE clause filtering in bp_events_get_events() via subquery injection, REST endpoint accepts category_id/tag_id params, and admin category icon upload backed by wp.media**

## Performance

- **Duration:** ~5 min
- **Started:** 2026-03-18T09:25:00Z
- **Completed:** 2026-03-18T09:29:52Z
- **Tasks:** 2
- **Files modified:** 4

## Accomplishments
- Added `category_id` and `tag_id` filtering to `bp_events_get_events()` via a new `bp_events_add_taxonomy_where_clauses` filter function using prepared SQL subqueries
- Added taxonomy helper functions (`bp_events_set_event_terms`, `bp_events_get_event_categories`, `bp_events_get_event_tags`, `bp_event_get_category_icon_url`) to bp-events-functions.php
- Extended the REST `/events` endpoint to accept `category_id`/`tag_id` for filtering and `category_ids`/`tags` for assignment on create/update; response includes `categories[]` and `tags[]` arrays
- Built admin category icon upload UI with wp.media picker, rendered on Add/Edit Category screens; icon persisted as `_bb_event_cat_icon_id` term meta

## Task Commits

Each task was committed atomically:

1. **Task 1: Add taxonomy filtering to bp_events_get_events() and REST endpoint** - `9cb3612` (feat)
2. **Task 2: Add admin category icon upload UI on term edit screens** - `e414616` (feat)

## Files Created/Modified
- `buddyboss-events/src/bp-events/bp-events-functions.php` - Added category_id/tag_id defaults, taxonomy helper functions
- `buddyboss-events/src/bp-events/bp-events-filters.php` - Added bp_events_add_taxonomy_where_clauses filter
- `buddyboss-events/src/bp-events/classes/class-bp-rest-events-endpoint.php` - REST filtering, term assignment, response enrichment
- `buddyboss-events/src/bp-events/bp-events-admin.php` - Category icon upload UI and media scripts

## Decisions Made
- Taxonomy WHERE clauses use subqueries (`e.id IN (SELECT ...)`) rather than JOINs to avoid duplicate event rows when an event matches multiple terms of the same taxonomy
- The `category_ids`/`tags` params in `create_item()`/`update_item()` use `has_param()` guards so omitting them doesn't wipe existing term assignments — consumers must explicitly pass terms to change them
- Category icon stored under `_bb_event_cat_icon_id` term meta key (underscore prefix marks it as internal, hidden from default WP term meta UI)
- `wp.media` enqueued only when `$screen->taxonomy === 'bb_event_category'` to avoid JS overhead on unrelated admin screens

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- TAX-01 and TAX-02 fulfilled: category filtering on directory and tag assignment/search both work through the query API and REST endpoint
- REST response includes categories/tags arrays with icon URLs — front-end can render category badges and icons immediately
- Phase 5 data enrichment can call `bp_events_set_event_terms()` to assign taxonomy terms from the event creation wizard

---
*Phase: 04-meta-api-foundation-taxonomy*
*Completed: 2026-03-18*
