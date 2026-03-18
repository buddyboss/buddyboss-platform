---
phase: 04-meta-api-foundation-taxonomy
plan: "00"
subsystem: testing
tags: [phpunit, taxonomy, meta-api, wp-unittest]

# Dependency graph
requires:
  - phase: 03-buddyboss-integration
    provides: "Completed Phase 3 — BuddyBoss integration verified"
provides:
  - "PHPUnit stubs for meta API (4 stubs — roundtrip, update, delete, unique add)"
  - "PHPUnit stubs for taxonomy assignment (5 stubs — category, tag, filters, icon meta)"
  - "PHPUnit stubs for taxonomy archive privacy (4 stubs — private/hidden/public group filtering)"
affects:
  - 04-01-meta-api-implementation
  - 04-02-taxonomy-registration
  - 04-03-taxonomy-privacy-filter

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "markTestIncomplete stubs with plan-number references (04-01-PLAN, 04-02-PLAN) for traceability"
    - "Tab indentation, @group annotations, WP_UnitTestCase base class per WordPress Coding Standards"

key-files:
  created:
    - buddyboss-events/tests/phpunit/testcases/test-bp-event-meta.php
    - buddyboss-events/tests/phpunit/testcases/test-bp-event-taxonomy.php
    - buddyboss-events/tests/phpunit/testcases/test-bp-event-taxonomy-privacy.php
  modified: []

key-decisions:
  - "All Phase 4 stubs use plan-number references in markTestIncomplete messages — TAX-03 privacy stubs point to 04-01-PLAN (alongside meta API) since privacy filter is registered at taxonomy bootstrap time"
  - "Tab indentation and BP_Events_Test_ class prefix maintained per WordPress Coding Standards in all Phase 4 test stubs"

patterns-established:
  - "Pattern: @group event-meta, @group event-taxonomy, @group event-taxonomy-privacy — distinct group labels allow selective PHPUnit test runs per feature area"

requirements-completed:
  - TAX-01
  - TAX-02
  - TAX-03

# Metrics
duration: 2min
completed: "2026-03-18"
---

# Phase 4 Plan 00: Meta API Foundation + Taxonomy Test Stubs

**13 PHPUnit markTestIncomplete stubs across 3 test classes covering META-API roundtrip/update/delete, TAX-01/02 category+tag assignment+filter, and TAX-03 privacy archive filtering**

## Performance

- **Duration:** 2 min
- **Started:** 2026-03-18T09:17:55Z
- **Completed:** 2026-03-18T09:19:21Z
- **Tasks:** 1
- **Files modified:** 3

## Accomplishments

- Created `test-bp-event-meta.php` with 4 stubs for meta API (roundtrip, update, delete, unique add)
- Created `test-bp-event-taxonomy.php` with 5 stubs for TAX-01/TAX-02 (category and tag assignment, category/tag filters, icon term meta)
- Created `test-bp-event-taxonomy-privacy.php` with 4 stubs for TAX-03 (private excluded, hidden excluded, public allowed, full integration)

## Task Commits

Each task was committed atomically:

1. **Task 1: Create PHPUnit test stubs for meta API, taxonomy assignment, and taxonomy privacy** - `dd2c9c3` (test)

**Plan metadata:** *(see final commit)*

## Files Created/Modified

- `buddyboss-events/tests/phpunit/testcases/test-bp-event-meta.php` - 4 stubs for bp_event_update_meta / bp_event_get_meta / bp_event_delete_meta / bp_event_add_meta
- `buddyboss-events/tests/phpunit/testcases/test-bp-event-taxonomy.php` - 5 stubs for wp_set_object_terms category/tag, bp_events_get_events filters, _bb_event_cat_icon_id term meta
- `buddyboss-events/tests/phpunit/testcases/test-bp-event-taxonomy-privacy.php` - 4 stubs for pre_get_posts privacy filter on taxonomy archives

## Decisions Made

- All Phase 4 stubs reference plan numbers in `markTestIncomplete` messages for traceability to implementation plans.
- TAX-03 privacy stubs point to `04-01-PLAN` (same as meta API) — privacy filter is part of taxonomy bootstrap which happens in 04-01, not 04-02.

## Deviations from Plan

None — plan executed exactly as written.

## Issues Encountered

None. The `php` binary is not in shell PATH but `/Applications/MAMP/bin/php/php8.3.28/bin/php` was used for syntax verification — all three files passed.

## User Setup Required

None — no external service configuration required.

## Next Phase Readiness

- All 13 test stubs are in place with `markTestIncomplete` markers — implementation plans 04-01 and 04-02 have clear test targets
- `test-bp-event-meta.php` (4 stubs) ready for 04-01 meta API implementation
- `test-bp-event-taxonomy.php` (5 stubs) ready for 04-02 taxonomy registration
- `test-bp-event-taxonomy-privacy.php` (4 stubs) ready for 04-01 privacy filter implementation

---
*Phase: 04-meta-api-foundation-taxonomy*
*Completed: 2026-03-18*
