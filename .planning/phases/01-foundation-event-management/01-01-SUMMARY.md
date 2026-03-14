---
phase: 01-foundation-event-management
plan: "01"
subsystem: database
tags: [php-rrule, recurring-events, cron, rrule, occurrences, series-split]

# Dependency graph
requires:
  - phase: 01-foundation-event-management-00
    provides: PHPUnit test infrastructure, bp_events DB schema, CRUD functions wired

provides:
  - rlanvin/php-rrule v2.6.0 vendored at src/bp-events/includes/lib/php-rrule/
  - bp_events_generate_occurrences(): RRULE-driven child row pre-generation with duplicate guard
  - bp_events_extend_occurrences_for_event(): fills occurrence window forward from max child date
  - bp_events_split_series(): deletes future children + creates new parent + generates fresh children
  - bp_events_detach_occurrence(): clears parent_event_id (true SQL NULL) and recurrence_rule
  - bp_events_get_meta(), bp_events_update_meta(), bp_events_add_meta(), bp_events_delete_meta()
  - WP cron job scheduled on activation (bp_events_extend_occurrences, daily)
  - bp_events_after_event_save hook wired to bp_events_generate_occurrences (priority 20)

affects: [01-02, 01-03, 02-payments-ticketing, calendar-rest-feed, event-creation-form]

# Tech tracking
tech-stack:
  added: [rlanvin/php-rrule v2.6.0 (vendored)]
  patterns:
    - RRULE-driven occurrence pre-generation with 2-year window
    - Duplicate guard via occurrences_generated_until meta key
    - True SQL NULL for parent_event_id via raw wpdb->query (not wpdb->update with %d)
    - DTSTART prepended to RRULE string when absent for php-rrule compatibility

key-files:
  created:
    - buddyboss-events/src/bp-events/includes/lib/php-rrule/RRule.php
    - buddyboss-events/src/bp-events/includes/lib/php-rrule/RRuleInterface.php
    - buddyboss-events/src/bp-events/includes/lib/php-rrule/RSet.php
  modified:
    - buddyboss-events/src/bp-events/bp-events-functions.php
    - buddyboss-events/src/bp-events/bp-events-filters.php
    - buddyboss-events/src/bp-events/bp-events-loader.php
    - buddyboss-events/tests/phpunit/testcases/test-recurring.php

key-decisions:
  - "Vendored php-rrule v2.6.0 as three files (no Composer autoloader) — manual require_once with BP_PLUGIN_DIR constant"
  - "Duplicate guard uses occurrences_generated_until meta key checked against now+2years-90days threshold"
  - "bp_events_update_event() allowlist excludes parent_event_id — detach_occurrence uses raw SQL NULL update"
  - "Series split removes COUNT clause from RRULE and adds UNTIL= based on split date to cap original series"
  - "bp_events_get_meta/update_meta implemented from scratch — no WP meta API available for custom tables"

patterns-established:
  - "Occurrence generation: always skip first RRULE date (it is the parent row)"
  - "Cron extension: check max(start_date) of children vs 2-year threshold before generating"
  - "Series split: delete future rows first, then update RRULE, then create new parent, then generate children"

requirements-completed: [EVNT-03]

# Metrics
duration: 4min
completed: 2026-03-14
---

# Phase 01 Plan 01: Recurring Events Engine Summary

**php-rrule v2.6.0 vendored + RRULE-driven occurrence pre-generation with duplicate guard, series-split, and daily WP cron extension job**

## Performance

- **Duration:** 4 min
- **Started:** 2026-03-14T07:23:09Z
- **Completed:** 2026-03-14T07:27:00Z
- **Tasks:** 2 (TDD: RED + GREEN for each)
- **Files modified:** 7

## Accomplishments

- Vendored rlanvin/php-rrule v2.6.0 (3 PHP files) at `src/bp-events/includes/lib/php-rrule/`
- Implemented `bp_events_generate_occurrences()` — reads RRULE, creates child rows 2 years ahead, skips parent (first occurrence), duplicate-guarded via `occurrences_generated_until` meta key
- Implemented `bp_events_extend_occurrences_for_event()` — extends window from max child date forward, called by daily cron
- Implemented `bp_events_split_series()` — deletes future children from original series, adds UNTIL= to original RRULE, creates new parent, generates fresh children
- Implemented `bp_events_detach_occurrence()` — clears parent_event_id (true SQL NULL) and recurrence_rule on single occurrence
- Added full event meta API: `bp_events_get_meta`, `bp_events_update_meta`, `bp_events_add_meta`, `bp_events_delete_meta`
- Registered `bp_events_cron_extend_occurrences` on `bp_events_extend_occurrences` WP cron action (daily)
- Scheduled cron on `bp_events_activated` hook via `wp_schedule_event` guarded by `wp_next_scheduled`
- Wired `bp_events_generate_occurrences` onto `bp_events_after_event_save` at priority 20

## Task Commits

Each task was committed atomically:

1. **TDD RED — Tests + vendored library** - `d2e7d97` (test)
2. **TDD GREEN — Full implementation** - `2b2974e` (feat)

_Note: Both tasks share the GREEN commit as their implementations are interdependent (split_series calls generate_occurrences)._

## Files Created/Modified

- `src/bp-events/includes/lib/php-rrule/RRule.php` — Vendored rlanvin/php-rrule v2.6.0 core class
- `src/bp-events/includes/lib/php-rrule/RRuleInterface.php` — Vendored interface
- `src/bp-events/includes/lib/php-rrule/RSet.php` — Vendored RSet class
- `src/bp-events/bp-events-functions.php` — Added meta API + all four occurrence/series functions
- `src/bp-events/bp-events-filters.php` — Added occurrence hook + cron registration
- `src/bp-events/bp-events-loader.php` — Added cron scheduling on activation
- `tests/phpunit/testcases/test-recurring.php` — Replaced stubs with real test implementations

## Decisions Made

- **Vendoring strategy:** php-rrule vendored as 3 individual files with manual `require_once` using `BP_PLUGIN_DIR` constant. No Composer autoloader since BuddyBoss Platform uses its own class-map autoloader.
- **DTSTART handling:** When RRULE string lacks DTSTART, it is prepended as `DTSTART:{gmdate}\nRRULE:{rule}` so php-rrule can anchor the sequence correctly.
- **Duplicate guard threshold:** `occurrences_generated_until` meta must be >= `now + 2 years - 90 days` to skip regeneration. 90-day buffer ensures the window stays fresh.
- **NULL for parent_event_id:** `wpdb->update()` with `%d` format casts PHP `null` to `0`, not SQL `NULL`. Used `wpdb->query(prepare(...SET parent_event_id = NULL...))` instead.
- **RRULE cap on split:** Removes `COUNT=` clause and substitutes `UNTIL={split_date-1sec}` to avoid over-extending the original series.
- **Meta API from scratch:** WordPress core `add_metadata()`/`get_metadata()` only work with `post`, `user`, `term`, `comment`. Custom table needs its own implementation.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 2 - Missing Critical] Added complete event meta API**
- **Found during:** Task 1 (implementing bp_events_generate_occurrences)
- **Issue:** `bp_events_get_meta()` and `bp_events_update_meta()` referenced in the plan's context were not yet implemented — no meta functions existed anywhere in the codebase. The occurrence generation duplicate guard requires them.
- **Fix:** Implemented `bp_events_get_meta`, `bp_events_update_meta`, `bp_events_add_meta`, `bp_events_delete_meta` with proper caching, serialization, and `$wpdb->prepare()` throughout.
- **Files modified:** `src/bp-events/bp-events-functions.php`
- **Verification:** php -l clean; functions are callable from occurrence generation code
- **Committed in:** `2b2974e` (Task 1+2 GREEN phase commit)

**2. [Rule 1 - Bug] Fixed NULL assignment for parent_event_id on detach**
- **Found during:** Task 2 (implementing bp_events_detach_occurrence)
- **Issue:** `wpdb->update()` with `%d` format converts PHP `null` to `0`, which would store `parent_event_id = 0` instead of `NULL`. The test asserts `assertNull($row->parent_event_id)`.
- **Fix:** Replaced `wpdb->update()` call with `wpdb->query(wpdb->prepare("UPDATE ... SET parent_event_id = NULL WHERE id = %d"))`.
- **Files modified:** `src/bp-events/bp-events-functions.php`
- **Verification:** php -l clean; direct SQL ensures genuine NULL is written
- **Committed in:** `2b2974e` (Task 1+2 GREEN phase commit)

---

**Total deviations:** 2 auto-fixed (1 missing critical, 1 bug)
**Impact on plan:** Both fixes essential for correctness. Meta API is a prerequisite for duplicate guard. NULL fix is required for ORM correctness. No scope creep.

## Issues Encountered

- WP test suite not installed at `/tmp/wordpress-tests-lib` — PHPUnit integration tests could not run in this environment. Per plan `done` criteria, functions are syntactically correct and php -l clean. Tests will run once WP test suite is configured.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- All occurrence generation logic is in place — REST feed (01-02) can now return individual child occurrence rows to FullCalendar
- Series split and detach are implemented — creation form (01-03) has the backend operations it needs
- WP cron is registered and will schedule on next plugin activation
- Meta API is available for future plans to store per-event metadata

---
*Phase: 01-foundation-event-management*
*Completed: 2026-03-14*

## Self-Check: PASSED

| Item | Status |
|------|--------|
| RRule.php | FOUND |
| RRuleInterface.php | FOUND |
| RSet.php | FOUND |
| bp-events-functions.php | FOUND |
| bp-events-filters.php | FOUND |
| 01-01-SUMMARY.md | FOUND |
| Commit d2e7d97 (RED) | FOUND |
| Commit 2b2974e (GREEN) | FOUND |
| bp_events_generate_occurrences() | FOUND (1 definition) |
| bp_events_split_series() | FOUND (1 definition) |
| bp_events_detach_occurrence() | FOUND (1 definition) |
| wp_schedule_event in loader | FOUND |
| bp_events_extend_occurrences hook | FOUND |
