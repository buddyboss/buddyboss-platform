---
phase: 04-meta-api-foundation-taxonomy
plan: 03
subsystem: ui
tags: [wordpress, php, javascript, fullcalendar, taxonomy, wizard, rest-api]

# Dependency graph
requires:
  - phase: 04-meta-api-foundation-taxonomy/04-01
    provides: bb_event_category and bb_event_tag taxonomy registration, REST endpoints, template_include filter for taxonomy archive
  - phase: 04-meta-api-foundation-taxonomy/04-02
    provides: bp_event_get_category_icon_url(), category icon term meta, taxonomy where-clause filters on bp_events_get_events()
provides:
  - Categories & Tags step (step 7) in the multi-step creation wizard
  - Category checkboxes loaded from bpEventsCreate.categories (localized PHP data)
  - Tag tokenizer input — comma/Enter adds styled tag tokens; X removes them
  - category_ids[] and tags[] submitted in REST POST payload on event creation
  - Category filter dropdown on events directory page (bb-rl-events-category-filter)
  - Calendar JS re-fetches events with category_id param when filter changes
  - taxonomy-archive.php template for /event-category/[slug]/ and /event-tag/[slug]/
  - Archive template calls bp_events_get_events() directly with category_id or tag_id
  - Archive shows category icon, term title, description, event cards, pagination
affects:
  - Phase 5 (Data Enrichment) — create wizard step sequence will need to accommodate new fields
  - Phase 7 (Front-End Submission) — wizard step numbering is now 8 total (with recurrence)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Wizard step insertion pattern — new steps added between RSVP(6) and Review(8) without breaking navigation; Review is always the last step (8)
    - taxonomy_exists() guard on all server-side get_terms() calls — prevents fatal on fresh installs
    - function_exists() guard on bp_event_get_category_icon_url() calls in templates
    - FullCalendar category filter: removeAllEventSources() + addEventSource() pattern for dynamic URL params

key-files:
  created:
    - buddyboss-events/src/bp-templates/bp-nouveau/readylaunch/events/taxonomy-archive.php
  modified:
    - buddyboss-events/src/bp-templates/bp-nouveau/readylaunch/events/create.php
    - buddyboss-events/src/bp-events/bp-events-loader.php
    - buddyboss-events/src/bp-events/assets/js/bp-events-create.js
    - buddyboss-events/src/bp-templates/bp-nouveau/readylaunch/events/index.php
    - buddyboss-events/src/bp-events/assets/js/bp-events-calendar.js

key-decisions:
  - "Categories & Tags inserted as step 7 (before Review step 8) — RSVP Settings stays at step 6; Review is always last regardless of recurrence toggle"
  - "category_ids and tags submitted as JSON arrays in REST POST payload — REST endpoint in phase 04-02 already handles these fields"
  - "Server-side category rendering in index.php and loader.php uses taxonomy_exists() guard — safe on fresh installs before taxonomy is registered"
  - "FullCalendar category filter uses removeAllEventSources() + addEventSource() with rebuilt URL — avoids mutating the initial eventSource config object"
  - "taxonomy-archive.php uses function_exists() guard on bp_event_get_category_icon_url() — future-proof if icon helper is not yet registered"

patterns-established:
  - "Wizard step insertion: add render + bind functions, update dispatch tables, update navigation bounds, update step indicator arrays"
  - "Tag tokenizer: state.tags[] + renderTagTokens() re-renders entire token strip on add/remove — simple and correct"

requirements-completed: [TAX-01, TAX-02, TAX-03]

# Metrics
duration: 5min
completed: 2026-03-18
---

# Phase 4 Plan 03: Front-End Taxonomy UI Summary

**Category/tag wizard step, directory filter dropdown, and taxonomy archive template delivering full TAX-01/02/03 front-end experience**

## Performance

- **Duration:** ~5 min
- **Started:** 2026-03-18T09:32:00Z
- **Completed:** 2026-03-18T09:37:00Z
- **Tasks:** 2
- **Files modified:** 5

## Accomplishments
- Added Categories & Tags as step 7 in the 8-step creation wizard with hierarchical category checkboxes and comma/Enter tag tokenizer
- Added category filter dropdown to the events directory page; FullCalendar refetches events with category_id param on selection change
- Created taxonomy-archive.php that calls bp_events_get_events() directly to render events for /event-category/[slug]/ and /event-tag/[slug]/ URLs

## Task Commits

Each task was committed atomically:

1. **Task 1: Add Categories & Tags step to creation wizard and localize taxonomy data** - `eb57658` (feat)
2. **Task 2: Add category filter to directory page and create taxonomy archive template** - `054f50d` (feat)

## Files Created/Modified
- `buddyboss-events/src/bp-templates/bp-nouveau/readylaunch/events/create.php` - Added step 5 indicator (Categories), step 6 indicator (Review), step5Title and step6Title to i18n block
- `buddyboss-events/src/bp-events/bp-events-loader.php` - Added categoriesRestUrl, tagsRestUrl, and categories to bpEventsCreate localization
- `buddyboss-events/src/bp-events/assets/js/bp-events-create.js` - Added category_ids/tags to state, renderCategoriesStep(), bindCategoriesStepEvents(), updated navigation to 8-step max, updated review step to show categories/tags
- `buddyboss-events/src/bp-templates/bp-nouveau/readylaunch/events/index.php` - Added bb-rl-events-category-filter dropdown with server-rendered categories
- `buddyboss-events/src/bp-events/assets/js/bp-events-calendar.js` - Added category filter change listener that refetches FullCalendar events with category_id param
- `buddyboss-events/src/bp-templates/bp-nouveau/readylaunch/events/taxonomy-archive.php` - New template for event category/tag archive pages

## Decisions Made
- Categories & Tags inserted as step 7 (before Review step 8) — RSVP Settings stays at step 6; Review is always last
- category_ids and tags submitted as JSON arrays in REST POST payload
- Server-side category rendering uses taxonomy_exists() guard — safe on fresh installs
- FullCalendar category filter uses removeAllEventSources() + addEventSource() with rebuilt URL
- taxonomy-archive.php uses function_exists() guard on bp_event_get_category_icon_url()

## Deviations from Plan

None — plan executed exactly as written.

## Issues Encountered
None.

## User Setup Required
None — no external service configuration required.

## Next Phase Readiness
- TAX-01, TAX-02, TAX-03 fully implemented front-to-back: assign categories/tags in wizard, filter by category in directory, browse by category/tag archive
- Phase 4 (Meta API Foundation + Taxonomy) is now complete — all 3 plans executed
- Ready for Phase 5 (Data Enrichment) and Phase 6 (Sessions + Speakers) which both depend on Phase 4

---
*Phase: 04-meta-api-foundation-taxonomy*
*Completed: 2026-03-18*
