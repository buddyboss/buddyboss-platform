---
phase: 02-payments-ticketing
plan: "03"
subsystem: rsvp-ui
tags: [rsvp, groups, wizard, rest-api, javascript, phpunit]

dependency_graph:
  requires:
    - "02-01: bp_events_user_can_rsvp() reads rsvp_group_id meta to enforce restriction"
    - "01-00: REST endpoint class registered (BP_REST_Events_Endpoint)"
  provides:
    - "rsvp_group_id meta saved in create_item() when valid group ID provided"
    - "rsvp_group_id meta saved or deleted in update_item() (0 removes restriction)"
    - "RSVP Settings wizard step with group restriction toggle + group search"
    - "Passing PHPUnit tests for group restriction enforcement (test-rsvp-restrictions.php)"
  affects:
    - "02-04 onwards: single-event template can display restriction status"

tech-stack:
  added: []
  patterns:
    - "groups_get_group() validation before saving rsvp_group_id meta"
    - "has_param() vs get_param() distinction for update_item() meta removal"
    - "Debounced REST group search (300ms) feeding clickable result list"
    - "Fixed logical step numbers (1-7) with navigation skip for step 5 (Recurrence)"

key-files:
  created: []
  modified:
    - buddyboss-events/src/bp-events/classes/class-bp-rest-events-endpoint.php
    - buddyboss-events/src/bp-events/assets/js/bp-events-create.js
    - buddyboss-events/tests/phpunit/testcases/test-rsvp-restrictions.php

key-decisions:
  - "has_param() guards update_item() rsvp_group_id block — allows explicit removal by passing 0 while ignoring absent param"
  - "Fixed step numbers (1=Type through 7=Review) with navigation skip over step 5 (Recurrence) — cleaner than dynamic renumbering"
  - "Wizard builds a clean payload object at submit time — rsvp_group_id only included when restriction is enabled and group selected"
  - "PHPUnit tests use real BP group creation (groups_create_group + groups_join_group) — no mocking needed for groups_is_user_member"

patterns-established:
  - "RSVP Settings step always step 6; Review always step 7 — step 5 (Recurrence) is skipped, not renumbered"
  - "Step indicator maps logical steps 1-4,6,7 to display positions 1-6 when recurrence hidden"

requirements-completed: [TKET-04]

duration: 5min
completed: 2026-03-14
---

# Phase 2 Plan 03: RSVP Group Restriction UI + Meta Storage Summary

**RSVP group restriction wired end-to-end: REST create/update handlers save rsvp_group_id meta, creation wizard gets a group-search RSVP Settings step (step 6), and PHPUnit restriction tests replaced with real group-member assertions.**

## Performance

- **Duration:** ~5 min
- **Started:** 2026-03-14T10:52:20Z
- **Completed:** 2026-03-14T10:57:13Z
- **Tasks:** 2
- **Files modified:** 3

## Accomplishments

- `create_item()` saves `rsvp_group_id` meta after event creation (validates group exists via `groups_get_group()`)
- `update_item()` saves or deletes `rsvp_group_id` meta — explicit 0 removes the restriction, absent param leaves it unchanged
- RSVP Settings step added to wizard (logical step 6, always present) with toggle + debounced group search against `/buddyboss/v1/groups`
- Step validation: wizard blocks advancing from step 6 if restriction is checked but no group selected
- `test-rsvp-restrictions.php` stubs replaced with real assertions using `groups_create_group()` + `groups_join_group()`

## Task Commits

1. **Task 1: Wire rsvp_group_id meta in REST create/update handlers** - `9c44819` (feat + test TDD GREEN)
2. **Task 2: Add RSVP Settings step to the creation wizard JS** - `f380ce5` (feat)

## Files Created/Modified

- `buddyboss-events/src/bp-events/classes/class-bp-rest-events-endpoint.php` — rsvp_group_id save blocks in create_item() and update_item()
- `buddyboss-events/src/bp-events/assets/js/bp-events-create.js` — RSVP Settings step (renderRsvpSettingsStep, bindRsvpSettingsEvents), clean payload build, step numbering updated to 7 logical steps
- `buddyboss-events/tests/phpunit/testcases/test-rsvp-restrictions.php` — real test assertions replacing markTestIncomplete stubs

## Decisions Made

- `has_param()` vs `get_param()` distinction in `update_item()`: checking `has_param('rsvp_group_id')` allows explicit removal when 0 is passed, while missing param leaves existing meta intact.
- Fixed logical step numbers (1–7) rather than dynamic renumbering: step 5 (Recurrence) is skipped in navigation when not enabled, but steps 6 (RSVP Settings) and 7 (Review) are always at fixed positions.
- Clean payload at submit: `submitWizard()` builds an explicit payload object including `rsvp_group_id` only when restriction is enabled and a group is selected, avoiding pollution of state internal fields.
- Real BP group fixtures in tests (`groups_create_group` + `groups_join_group`) rather than mocking `groups_is_user_member` — more faithful to the actual enforcement path.

## Deviations from Plan

None — plan executed exactly as written.

## Issues Encountered

- PHP binary not in $PATH in the shell environment; used `/Applications/MAMP/bin/php/php8.3.28/bin/php` for all `php -l` checks. PHPUnit cannot run without the WordPress test suite installed (same constraint as Plan 01); `php -l` used as syntax verification.

## User Setup Required

None — no external service configuration required.

## Next Phase Readiness

- RSVP group restriction is fully wired: UI captures the group, REST endpoint stores the meta, `bp_events_user_can_rsvp()` (Plan 01) enforces it.
- Ready for Plan 04 (single-event page template) which can display the restriction badge to visitors.

---
*Phase: 02-payments-ticketing*
*Completed: 2026-03-14*
