---
phase: 03-buddyboss-integration
plan: "01"
subsystem: api
tags: [buddyboss, bp-group-extension, rest-api, fullcalendar, privacy]

# Dependency graph
requires:
  - phase: 03-00
    provides: Phase 3 stubs and test scaffolding for group extension integration
provides:
  - BP_Events_Group_Extension subclass of BP_Group_Extension with slug=events and nav_item_position=25
  - bp-events-group-extension.php loader shim registers group extension on bp_init priority 11
  - group-events.php template with FullCalendar mount point, noscript fallback, and wp_localize_script bpEventsGroup
  - REST GET /buddyboss/v1/events?group_id=X non-member 403 guard using groups_is_user_member
  - bp-events-group-calendar.js scoped FullCalendar init with group_id extraParam
affects:
  - 03-buddyboss-integration
  - any plan that adds group-level UI or extends the REST events endpoint

# Tech tracking
tech-stack:
  added: []
  patterns:
    - BP_Group_Extension subclass delegates privacy to platform (no user_can_visit override)
    - Non-member REST guard runs BEFORE bp_events_get_events() — enforced at controller layer
    - Group calendar JS uses separate localize object (bpEventsGroup) from global calendar (bpEventsSettings)
    - Group global stored in $GLOBALS['bp_events_current_group_id'] — consistent with bp-forums pattern

key-files:
  created:
    - buddyboss-events/src/bp-events/classes/class-bp-events-group-extension.php
    - buddyboss-events/src/bp-events/bp-events-group-extension.php
    - buddyboss-events/src/bp-templates/bp-nouveau/readylaunch/events/group-events.php
    - buddyboss-events/src/bp-events/assets/js/bp-events-group-calendar.js
  modified:
    - buddyboss-events/src/bp-events/classes/class-bp-events-component.php
    - buddyboss-events/src/bp-events/classes/class-bp-rest-events-endpoint.php
    - buddyboss-events/tests/phpunit/testcases/test-group-extension.php

key-decisions:
  - "BP_Events_Group_Extension does NOT override user_can_visit() — privacy for private/hidden groups delegated to platform BP_Group_Extension base class"
  - "Non-member REST 403 guard placed BEFORE bp_events_get_events() in get_items() — bp_events_get_events does not enforce group privacy when group_id is passed"
  - "Group calendar uses bp-events-group-calendar.js (separate from bp-events-calendar.js) to avoid coupling the global events calendar to group-scoped logic"
  - "bpEventsGroup localize object separate from bpEventsSettings — clean namespace for group-calendar JS context"

patterns-established:
  - "Group extension pattern: BP_Group_Extension subclass + loader shim + bp_register_group_extension on bp_init priority 11"
  - "REST privacy guard pattern: check group status and membership BEFORE calling data functions that don't enforce privacy themselves"

requirements-completed: [BB-01]

# Metrics
duration: 10min
completed: 2026-03-16
---

# Phase 3 Plan 01: Group Extension — Events Tab in BuddyBoss Groups Summary

**BP_Events_Group_Extension registers an Events tab on every BuddyBoss group with FullCalendar scoped by group_id, REST non-member 403 guard enforced before data query, and privacy delegated to platform**

## Performance

- **Duration:** ~10 min
- **Started:** 2026-03-16T10:42:58Z
- **Completed:** 2026-03-16T10:47:11Z
- **Tasks:** 2 (plus TDD RED commit)
- **Files modified:** 7

## Accomplishments

- Created BP_Events_Group_Extension (BP_Group_Extension subclass): slug=events, nav_item_position=25, display() sets group global and calls template part
- Created loader shim bp-events-group-extension.php: calls bp_register_group_extension() on bp_init priority 11, guarded by bp_is_active('groups')
- Wired class-bp-events-component.php to include 'group-extension' when groups component is active
- Created group-events.php: FullCalendar mount point (#bp-events-group-calendar), noscript event list fallback via bp_events_get_events, bpEventsGroup localization with groupId, eventsUrl, and nonce
- Added bp-events-group-calendar.js: FullCalendar init scoped to group via group_id and _fc=1 extraParams, X-WP-Nonce header
- Added non-member 403 privacy guard in REST get_items() BEFORE bp_events_get_events() runs, using groups_is_user_member() and groups_get_group() status check
- Expanded REST group_id param with full description, default, sanitize_callback, and validate_callback

## Task Commits

Each task was committed atomically:

1. **TDD RED: Failing tests** - `8672f96` (test)
2. **Task 1: BP_Events_Group_Extension class and loader shim** - `296de23` (feat)
3. **Task 2: Group events template, REST param, non-member guard** - `d3b4f4d` (feat)

_Note: TDD tasks have multiple commits (test RED → feat GREEN)_

## Files Created/Modified

- `buddyboss-events/src/bp-events/classes/class-bp-events-group-extension.php` — BP_Group_Extension subclass, slug=events
- `buddyboss-events/src/bp-events/bp-events-group-extension.php` — loader shim, bp_register_group_extension on bp_init:11
- `buddyboss-events/src/bp-events/classes/class-bp-events-component.php` — added group-extension conditional include
- `buddyboss-events/src/bp-events/classes/class-bp-rest-events-endpoint.php` — 403 guard + enhanced group_id param
- `buddyboss-events/src/bp-templates/bp-nouveau/readylaunch/events/group-events.php` — FullCalendar mount, noscript, localize
- `buddyboss-events/src/bp-events/assets/js/bp-events-group-calendar.js` — group-scoped FullCalendar JS
- `buddyboss-events/tests/phpunit/testcases/test-group-extension.php` — real assertions for 4 group extension tests

## Decisions Made

- **No user_can_visit() override**: Privacy for private/hidden groups is entirely delegated to BP_Group_Extension base class. The platform enforces show_tab=member automatically for private/hidden groups — no custom override needed and adding one would risk diverging from platform behavior.

- **403 guard before data query**: bp_events_get_events() was confirmed to NOT enforce group privacy when group_id is passed. The guard must run at the controller layer, before the query, which is the correct defense position.

- **Separate JS file for group calendar**: bp-events-group-calendar.js keeps group-scoped FullCalendar init decoupled from the global directory calendar (bp-events-calendar.js). The group calendar needs group_id extraParam and nonce header that the global calendar doesn't need.

- **bpEventsGroup localize object**: Kept separate from bpEventsSettings to maintain clean namespace isolation between the two calendar contexts.

## Deviations from Plan

None — plan executed exactly as written. The plan noted that `group_id` was already in `$args` in get_items() (it was), so only the privacy guard and param spec enhancement were needed additions.

## Issues Encountered

None.

## Self-Check

- `buddyboss-events/src/bp-events/classes/class-bp-events-group-extension.php` — EXISTS
- `buddyboss-events/src/bp-events/bp-events-group-extension.php` — EXISTS
- `buddyboss-events/src/bp-templates/bp-nouveau/readylaunch/events/group-events.php` — EXISTS
- `buddyboss-events/src/bp-events/assets/js/bp-events-group-calendar.js` — EXISTS
- `groups_is_user_member` in class-bp-rest-events-endpoint.php — CONFIRMED
- `group-extension` conditional in class-bp-events-component.php — CONFIRMED

## Next Phase Readiness

- Group extension structural anchor for Phase 3 is complete — groups now have an Events tab
- REST endpoint supports group_id filtering with privacy enforcement
- Ready for 03-02: Activity stream integration (events posted to group activity feed)
- Stripe blocker from Phase 2 remains on record but does not affect Phase 3 plans

---
*Phase: 03-buddyboss-integration*
*Completed: 2026-03-16*
