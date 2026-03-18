---
phase: 04-meta-api-foundation-taxonomy
plan: 01
subsystem: database
tags: [wordpress-meta-api, taxonomy, privacy-filter, wp_set_object_terms, get_metadata]

# Dependency graph
requires:
  - phase: 04-meta-api-foundation-taxonomy
    provides: bb_eventmeta table (created in 04-00 schema migration)
provides:
  - bp_event_get_meta() / bp_event_update_meta() / bp_event_add_meta() / bp_event_delete_meta() wrapper functions
  - meta_tables registration enabling $wpdb->eventmeta
  - bb_event_category hierarchical taxonomy (TAX-01, TAX-02)
  - bb_event_tag flat taxonomy (TAX-02)
  - pre_get_posts privacy filter excluding private/hidden group events from archives (TAX-03)
  - template_include override for taxonomy archive pages
affects:
  - 05-data-enrichment
  - 06-sessions-speakers
  - 07-frontend-submission
  - 08-analytics-reports

# Tech tracking
tech-stack:
  added: []
  patterns:
    - WordPress meta API with bp_filter_metaid_column_name query filter for non-standard PK column name
    - BuddyBoss meta_tables pattern (mirror of bp-groups meta_tables registration)
    - pre_get_posts privacy guard registered at same time as taxonomy to prevent security windows
    - WP taxonomy registered against custom object type string ('bb_event') rather than a CPT

key-files:
  created: []
  modified:
    - buddyboss-events/src/bp-events/classes/class-bp-events-component.php
    - buddyboss-events/src/bp-events/bp-events-functions.php
    - buddyboss-events/src/bp-events/bp-events-filters.php

key-decisions:
  - "bp_filter_metaid_column_name applied via add_filter/remove_filter around every metadata call — bb_eventmeta uses 'id' as PK not 'meta_id', this filter rewrites the SQL column reference"
  - "meta_tables passed to parent::setup_globals() mirrors bp-groups pattern — causes register_meta_tables() to set $wpdb->eventmeta automatically"
  - "bb_event object type string used for taxonomy registration — events are not a CPT so wp_set_object_terms() is used with arbitrary integer IDs (BuddyBoss group types establish this precedent)"
  - "Privacy filter registered on same init hook as taxonomy (both in bp-events-filters.php) — no gap between taxonomy going live and filter being active"

patterns-established:
  - "Meta API pattern: always wrap get/update/add/delete_metadata calls with add_filter/remove_filter('query','bp_filter_metaid_column_name') for event meta"
  - "Taxonomy privacy: pre_get_posts filter checks bb_events JOIN bp_groups for non-public status before taxonomy archive renders"

requirements-completed: [TAX-01, TAX-02, TAX-03]

# Metrics
duration: 2min
completed: 2026-03-18
---

# Phase 4 Plan 01: Meta API Foundation + Taxonomy Summary

**bb_eventmeta PHP API (4 CRUD wrappers) and bb_event_category/bb_event_tag taxonomy registration with TAX-03 privacy filter blocking private group events from public archives**

## Performance

- **Duration:** 2 min
- **Started:** 2026-03-18T09:21:31Z
- **Completed:** 2026-03-18T09:23:20Z
- **Tasks:** 2
- **Files modified:** 3

## Accomplishments

- Added meta_tables to BP_Events_Component::setup_globals() enabling WordPress to resolve $wpdb->eventmeta to the bb_eventmeta table
- Implemented four event meta wrapper functions (get/update/add/delete) using WordPress core metadata API with bp_filter_metaid_column_name to handle the non-standard PK column name
- Registered bb_event_category as hierarchical taxonomy and bb_event_tag as flat taxonomy, both shown in admin under the bp-events menu
- Added TAX-03 security filter on pre_get_posts to exclude events in private/hidden groups from taxonomy archive pages
- Added template_include override for taxonomy archive pages (falls back to default WP template if custom template not found)

## Task Commits

Each task was committed atomically:

1. **Task 1: Add meta_tables to setup_globals() and implement meta API wrapper functions** - `476803a` (feat)
2. **Task 2: Register taxonomies and add privacy filter for archive pages** - `b7c215f` (feat)

**Plan metadata:** *(docs commit follows)*

## Files Created/Modified

- `buddyboss-events/src/bp-events/classes/class-bp-events-component.php` - Added meta_tables array to parent::setup_globals() call
- `buddyboss-events/src/bp-events/bp-events-functions.php` - Added bp_event_get/update/add/delete_meta() functions in new "Event Meta API" section
- `buddyboss-events/src/bp-events/bp-events-filters.php` - Added bp_events_register_taxonomies(), bp_events_taxonomy_privacy_filter(), bp_events_taxonomy_archive_template() in new "Event Taxonomies" section

## Decisions Made

- bp_filter_metaid_column_name applied via add_filter/remove_filter around every metadata call because bb_eventmeta uses 'id' as PK not 'meta_id'
- meta_tables registration mirrors the BuddyBoss bp-groups pattern — calls parent::setup_globals() which invokes register_meta_tables() internally
- 'bb_event' used as object type string for taxonomy registration — events are not a CPT, WP has no FK check on object_id so arbitrary IDs work (BuddyBoss group types use this same pattern)
- Privacy filter registered at same time as taxonomies (same file, same request lifecycle) — no window where taxonomy is live but filter is absent

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Meta API foundation complete — Phases 5, 6, 7, and 8 can now call bp_event_get_meta() / bp_event_update_meta()
- Taxonomy infrastructure ready — TAX-01 (categories), TAX-02 (tags), TAX-03 (privacy) all implemented
- Phase 5 (Data Enrichment) and Phase 6 (Sessions + Speakers) can now proceed in parallel

---
*Phase: 04-meta-api-foundation-taxonomy*
*Completed: 2026-03-18*
