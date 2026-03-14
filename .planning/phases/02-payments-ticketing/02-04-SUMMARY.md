---
phase: 02-payments-ticketing
plan: "04"
subsystem: ui

tags: [php, javascript, rsvp, attendees, ical, gcal, wordpress, buddyboss]

# Dependency graph
requires:
  - phase: 02-payments-ticketing
    plan: "01"
    provides: "REST endpoints: POST/DELETE /rsvp, GET /attendees, GET /gcal-url, GET /ical"
  - phase: 02-payments-ticketing
    plan: "02"
    provides: "bp_events_user_can_rsvp(), waitlist functions, capacity enforcement"
  - phase: 02-payments-ticketing
    plan: "03"
    provides: "RSVP settings fields, rsvp_group_id meta, REST update_item integration"

provides:
  - "Single event page RSVP UI replacing Phase 1 placeholder"
  - "PHP-rendered initial button state (attending/waitlisted/at-capacity/restricted/guest)"
  - "Public attendee list with avatars"
  - "Organizer-only management panel with per-row Remove buttons"
  - "bp-events-single.js: RSVP/cancel/gcal/remove-attendee interactions"
  - "bp_events_enqueue_single_assets() enqueue function in loader"
  - "RSVP panel CSS states (primary/secondary/success/danger/sm button variants)"
  - "test_ical_endpoint_returns_valid_ics real assertion replacing markTestIncomplete stub"

affects:
  - 03-buddyboss-integration
  - ui
  - testing

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "PHP-renders initial RSVP button state; JS re-renders after REST calls — no reload needed"
    - "bpEventsSingle.i18n localised alongside bpEventsSingle data in same wp_localize_script call"
    - "Vanilla IIFE (var/function/tabs/single-quotes) per WordPress JS Coding Standards"
    - "fetch() with X-WP-Nonce header for all REST calls (no jQuery.ajax)"

key-files:
  created:
    - buddyboss-events/src/bp-events/assets/js/bp-events-single.js
  modified:
    - buddyboss-events/src/bp-templates/bp-nouveau/readylaunch/events/single/home.php
    - buddyboss-events/src/bp-events/bp-events-loader.php
    - buddyboss-events/src/bp-events/assets/css/bp-events.css
    - buddyboss-events/tests/phpunit/testcases/test-calendar-export.php

key-decisions:
  - "RSVP button state PHP-rendered on page load and re-rendered by JS after REST calls — single source of truth per request, no DOM polling"
  - "i18n strings embedded in bpEventsSingle.i18n sub-object (single wp_localize_script call instead of separate bpEventsSingleI18n object)"
  - "Organizer Remove button sends user_id in DELETE body — consistent with REST endpoint contract from Plan 01"

patterns-established:
  - "Single page JS: always initialise state from localised PHP data, never infer from rendered DOM"
  - "RSVP button data-state attribute drives JS branching (click = RSVP or cancel based on state)"

requirements-completed: [TKET-02, ATTN-01, ATTN-02]

# Metrics
duration: 3min
completed: 2026-03-14
---

# Phase 2 Plan 04: Single Event RSVP UI Summary

**PHP-rendered RSVP panel with live JS state management replacing Phase 1 placeholder — attendee list, organizer remove panel, and wired iCal/Google Calendar links**

## Performance

- **Duration:** 3 min
- **Started:** 2026-03-14T10:39:20Z
- **Completed:** 2026-03-14T10:42:32Z
- **Tasks:** 2
- **Files modified:** 5

## Accomplishments

- Replaced "Ticketing will be available in the next release" placeholder with fully functional RSVP panel showing correct initial state (attending / waitlisted / at-capacity / group-restricted / logged-out)
- Created bp-events-single.js as vanilla IIFE handling all client-side RSVP flows, Google Calendar fetch, and organizer attendee removal
- Added bp_events_enqueue_single_assets() to loader with bpEventsSingle localise (state + i18n combined) and comprehensive CSS for all button variants and panel layouts

## Task Commits

Each task was committed atomically:

1. **Task 1: Update single/home.php with RSVP panel and attendee list, add enqueue function** - `a6a9194` (feat)
2. **Task 2: Create bp-events-single.js and add CSS states** - `afff1bc` (feat)

**Plan metadata:** `(docs commit — see below)`

## Files Created/Modified

- `buddyboss-events/src/bp-templates/bp-nouveau/readylaunch/events/single/home.php` - Full RSVP panel, public attendee list, organizer management panel replacing Phase 1 placeholder
- `buddyboss-events/src/bp-events/bp-events-loader.php` - bp_events_enqueue_single_assets() with bpEventsSingle localise (state + i18n)
- `buddyboss-events/src/bp-events/assets/js/bp-events-single.js` - Vanilla IIFE: RSVP/cancel/gcal/remove-attendee handlers
- `buddyboss-events/src/bp-events/assets/css/bp-events.css` - RSVP panel, button variants, attendee list, organizer panel styles
- `buddyboss-events/tests/phpunit/testcases/test-calendar-export.php` - Real iCal endpoint assertions replacing markTestIncomplete stub

## Decisions Made

- RSVP button state is PHP-rendered on load and JS re-rendered after REST calls — PHP is the single source of truth per request, JS updates without page reload
- i18n strings embedded in bpEventsSingle.i18n sub-object (single wp_localize_script call) rather than a separate bpEventsSingleI18n object, keeping the plan's loader change self-contained
- Organizer Remove button sends user_id in DELETE /rsvp body (consistent with REST endpoint contract from Plan 01 rather than query param)

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- TKET-02, ATTN-01, ATTN-02 complete — Phase 2 RSVP/ticketing surface fully implemented
- Phase 3 (BuddyBoss Integration) can proceed: groups integration, activity feed events, member profile event lists
- test_ical_endpoint_returns_valid_ics has real assertions but requires WP test suite installed to run (noted in STATE.md from Plan 02)

---
*Phase: 02-payments-ticketing*
*Completed: 2026-03-14*
