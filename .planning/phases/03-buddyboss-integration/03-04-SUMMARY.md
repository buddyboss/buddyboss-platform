---
phase: 03-buddyboss-integration
plan: "04"
subsystem: ui
tags: [buddyboss, profile, member-profile, events, screen-function, template]

# Dependency graph
requires:
  - phase: 03-buddyboss-integration/03-00
    provides: BP_Events_Component with setup_nav already registering attending/hosting sub-tabs
provides:
  - bp_events_screen_attending() screen function loaded by late_includes() on /members/{username}/events/attending
  - bp_events_screen_hosting() screen function loaded by late_includes() on /members/{username}/events/hosting
  - profile-attending.php template querying by user_id (attendee table)
  - profile-hosting.php template querying by organizer_id
affects:
  - 03-05-PLAN
  - end-to-end verification

# Tech tracking
tech-stack:
  added: []
  patterns:
    - screen function hooks template part onto bp_template_content BEFORE calling bp_core_load_template() — required for content to render in member profile plugin area
    - bp_is_user() && bp_is_current_action() guards in late_includes() to load profile sub-tab screens

key-files:
  created:
    - buddyboss-events/src/bp-events/screens/profile/attending.php
    - buddyboss-events/src/bp-events/screens/profile/hosting.php
    - buddyboss-events/src/bp-templates/bp-nouveau/readylaunch/events/profile-attending.php
    - buddyboss-events/src/bp-templates/bp-nouveau/readylaunch/events/profile-hosting.php
  modified:
    - buddyboss-events/src/bp-events/classes/class-bp-events-component.php
    - buddyboss-events/tests/phpunit/testcases/test-profile-events.php

key-decisions:
  - "bp_template_content hook must be added inside screen function BEFORE bp_core_load_template() — without it the member profile plugin area renders blank"
  - "late_includes() attending/hosting branches gate on bp_is_user() to distinguish profile sub-tabs from directory/single event routes"

patterns-established:
  - "Profile sub-tab screen pattern: do_action(hook) → add_action(bp_template_content, ...) → bp_core_load_template(members/single/plugins)"
  - "Profile template pattern: bp_displayed_user_id() for user ID, bp_events_get_events() with appropriate key, bp_get_template_part for event-card partial"

requirements-completed: [BB-04]

# Metrics
duration: 2min
completed: 2026-03-16
---

# Phase 3 Plan 04: Profile Attending/Hosting Tabs Summary

**Screen functions and display templates for /members/{username}/events/attending and /hosting — queries attendee table by user_id and events by organizer_id, reusing event-card.php partial**

## Performance

- **Duration:** 2 min
- **Started:** 2026-03-16T10:59:42Z
- **Completed:** 2026-03-16T11:01:46Z
- **Tasks:** 2
- **Files modified:** 6

## Accomplishments
- Created bp_events_screen_attending() and bp_events_screen_hosting() screen functions with correct bp_template_content hook ordering
- Updated late_includes() with attending/hosting branches gated on bp_is_user() + bp_is_current_action()
- Created profile-attending.php and profile-hosting.php display templates using bp_displayed_user_id() and event-card.php partial
- Updated PHPUnit tests from markTestIncomplete to real assertions verifying query arg contracts

## Task Commits

Each task was committed atomically:

1. **TDD RED: failing test stubs → real assertions** - `49a72a7` (test)
2. **Task 1: screen functions + late_includes edit** - `1bd6f89` (feat)
3. **Task 2: profile display templates** - `8f50691` (feat)

_Note: TDD tasks have multiple commits (test → feat)_

## Files Created/Modified
- `buddyboss-events/src/bp-events/screens/profile/attending.php` - bp_events_screen_attending() screen function
- `buddyboss-events/src/bp-events/screens/profile/hosting.php` - bp_events_screen_hosting() screen function
- `buddyboss-events/src/bp-events/classes/class-bp-events-component.php` - late_includes() attending/hosting branches added
- `buddyboss-events/src/bp-templates/bp-nouveau/readylaunch/events/profile-attending.php` - queries user_id, renders event cards
- `buddyboss-events/src/bp-templates/bp-nouveau/readylaunch/events/profile-hosting.php` - queries organizer_id, renders event cards
- `buddyboss-events/tests/phpunit/testcases/test-profile-events.php` - real assertions for attending/hosting query contracts

## Decisions Made
- The `add_action('bp_template_content', ...)` call must appear inside the screen function itself, before `bp_core_load_template()`. This is the established BuddyBoss core pattern (e.g., bp-groups) — without it the member profile plugin area renders as a blank wrapper.
- late_includes() branches gate on `bp_is_user()` so the attending/hosting routes are only matched in the member profile context, not on the global events directory or single event pages.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None — PHP binary not in PATH so MAMP PHP 7.4 was used for lint checks. All syntax checks passed.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Both profile sub-tab routes are now functional: screen functions exist, late_includes loads them, templates query and render events
- Ready for 03-05 end-to-end verification of the full BuddyBoss integration phase
- Visiting /members/{username}/events/attending will show events the member RSVP'd to
- Visiting /members/{username}/events/hosting will show events the member created

---
*Phase: 03-buddyboss-integration*
*Completed: 2026-03-16*
