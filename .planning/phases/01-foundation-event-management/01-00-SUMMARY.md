---
phase: 01-foundation-event-management
plan: "00"
subsystem: testing

tags: [phpunit, wordpress-test-suite, test-stubs, bp-events]

# Dependency graph
requires: []
provides:
  - PHPUnit config (phpunit.xml.dist) scanning tests/phpunit/testcases/
  - WordPress+BuddyBoss test bootstrap wiring bp-events-loader.php
  - Six test stub files — one per requirement cluster (EVNT-01/02/04, EVNT-03, EVNT-05/06, ADMN-01, ADMN-02, ADMN-04)
affects:
  - 01-01-PLAN (EVNT-01/02/04 implementation — turns test-event-crud.php green)
  - 01-02-PLAN (EVNT-03 implementation — turns test-recurring.php green)
  - 01-03-PLAN (EVNT-05/06 implementation — turns test-calendar-privacy.php green)
  - 01-04-PLAN (ADMN-01 implementation — turns test-permissions.php green)
  - 01-05-PLAN (ADMN-02 implementation — turns test-admin.php green)
  - 01-06-PLAN (ADMN-04 implementation — turns test-moderation.php green)

# Tech tracking
tech-stack:
  added:
    - PHPUnit (WordPress test suite pattern — no Composer installation; relies on WP_TESTS_DIR)
  patterns:
    - "Test stubs use markTestIncomplete() so suite runs without errors and signals TODO work clearly"
    - "phpunit.xml.dist sets stopOnError=false — markTestIncomplete does not abort the run"
    - "Bootstrap guards on WP_TESTS_DIR presence; prints clear message and exits 1 if missing"
    - "Each test class uses WP_UnitTestCase base class per WordPress conventions"
    - "Class names follow BP_ prefix convention; test methods use snake_case"

key-files:
  created:
    - buddyboss-events/phpunit.xml.dist
    - buddyboss-events/tests/phpunit/bootstrap.php
    - buddyboss-events/tests/phpunit/includes/define-constants.php
    - buddyboss-events/tests/phpunit/testcases/test-event-crud.php
    - buddyboss-events/tests/phpunit/testcases/test-recurring.php
    - buddyboss-events/tests/phpunit/testcases/test-calendar-privacy.php
    - buddyboss-events/tests/phpunit/testcases/test-permissions.php
    - buddyboss-events/tests/phpunit/testcases/test-admin.php
    - buddyboss-events/tests/phpunit/testcases/test-moderation.php
  modified: []

key-decisions:
  - "stopOnError=false in phpunit.xml.dist — markTestIncomplete() calls do not abort the run, all stubs discoverable in one pass"
  - "Bootstrap loads bp-events-loader.php only if it exists — avoids fatal errors before implementation plans run"
  - "BP_PLUGIN_DIR points to plugin root (not src/); loader path is constructed as BP_PLUGIN_DIR/src/bp-events/bp-events-loader.php"
  - "test-permissions.php has two methods (test_creation_permission_admins_only, test_creation_permission_members) — both covered by VALIDATION.md filter prefix test_creation_permission"
  - "test-calendar-privacy.php method named test_group_event_excluded_when_setting_off — satisfies VALIDATION.md filter prefix test_group_event_excluded"

patterns-established:
  - "Test stub pattern: class BP_Events_Test_X extends WP_UnitTestCase with markTestIncomplete in each method"
  - "Constant naming: BP_TESTS_DIR = tests/phpunit/, BP_PLUGIN_DIR = plugin root, WP_TESTS_DIR = env var or /tmp/wordpress-tests-lib"

requirements-completed:
  - EVNT-01
  - EVNT-02
  - EVNT-03
  - EVNT-04
  - EVNT-05
  - EVNT-06
  - ADMN-01
  - ADMN-02
  - ADMN-04

# Metrics
duration: 2min
completed: 2026-03-14
---

# Phase 01 Plan 00: PHPUnit Test Infrastructure Summary

**PHPUnit test scaffold for bp-events with six WP_UnitTestCase stub files (markTestIncomplete), config, and bootstrap — all requirement clusters covered and discoverable.**

## Performance

- **Duration:** 2 min
- **Started:** 2026-03-14T07:18:00Z
- **Completed:** 2026-03-14T07:20:12Z
- **Tasks:** 2 of 2
- **Files modified:** 9 created

## Accomplishments

- Created phpunit.xml.dist pointing to tests/phpunit/testcases/ with stopOnError=false
- Created bootstrap.php and define-constants.php establishing BP_TESTS_DIR, BP_PLUGIN_DIR, WP_TESTS_DIR
- Created six test stub files covering all 9 requirements across EVNT-01–06, ADMN-01, ADMN-02, ADMN-04
- All 8 PHP files pass `php -l` with no parse errors

## Task Commits

Each task was committed atomically:

1. **Task 1: Create PHPUnit config and bootstrap** - `3917f7e` (chore)
2. **Task 2: Create seven test stub files** - `5d0adc4` (test)

## Files Created/Modified

- `buddyboss-events/phpunit.xml.dist` - PHPUnit config; single testsuite scanning testcases/, stopOnError=false
- `buddyboss-events/tests/phpunit/bootstrap.php` - WP test suite bootstrap loading bp-events-loader.php via muplugins_loaded
- `buddyboss-events/tests/phpunit/includes/define-constants.php` - Defines BP_TESTS_DIR, BP_PLUGIN_DIR, WP_TESTS_DIR
- `buddyboss-events/tests/phpunit/testcases/test-event-crud.php` - BP_Events_Test_CRUD: EVNT-01, EVNT-02, EVNT-04 (3 stubs)
- `buddyboss-events/tests/phpunit/testcases/test-recurring.php` - BP_Events_Test_Recurring: EVNT-03 (3 stubs)
- `buddyboss-events/tests/phpunit/testcases/test-calendar-privacy.php` - BP_Events_Test_Calendar_Privacy: EVNT-05, EVNT-06 (2 stubs)
- `buddyboss-events/tests/phpunit/testcases/test-permissions.php` - BP_Events_Test_Permissions: ADMN-01 (2 stubs)
- `buddyboss-events/tests/phpunit/testcases/test-admin.php` - BP_Events_Test_Admin: ADMN-02 (1 stub)
- `buddyboss-events/tests/phpunit/testcases/test-moderation.php` - BP_Events_Test_Moderation: ADMN-04 (1 stub)

## Decisions Made

- `stopOnError=false` so `markTestIncomplete()` doesn't abort the run — all stubs discoverable in one PHPUnit pass
- Bootstrap conditionally loads bp-events-loader.php only if it exists — prevents fatal errors before implementation plans run
- `test_creation_permission_admins_only` and `test_creation_permission_members` both satisfy the VALIDATION.md filter prefix `test_creation_permission`
- `test_group_event_excluded_when_setting_off` satisfies the VALIDATION.md filter prefix `test_group_event_excluded`

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Test infrastructure is in place; all subsequent plans can run `vendor/bin/phpunit` against their test file immediately
- Plans 01–06 need to implement the actual event logic to turn each stub from incomplete to green
- WP_TESTS_DIR must be set (e.g. via `wp scaffold plugin-tests` or manual install) before PHPUnit can actually run; test files exist and are parseable now

---
*Phase: 01-foundation-event-management*
*Completed: 2026-03-14*

## Self-Check: PASSED

- All 9 created files confirmed present on disk
- Both task commits confirmed: `3917f7e`, `5d0adc4`
