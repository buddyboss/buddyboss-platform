---
phase: 02-payments-ticketing
plan: "02"
subsystem: api
tags: [waitlist, rsvp, notifications, capacity, phpunit]

# Dependency graph
requires:
  - phase: 02-payments-ticketing/02-01
    provides: bp_events_notify_waitlist(), bp_events_get_waitlist(), bp_events_get_attendees(), waitlist RSVP cancel trigger
provides:
  - bp_events_update_capacity() function — checks registered count vs new capacity, calls bp_events_notify_waitlist() when spot opens
  - REST update_item() wired to call bp_events_update_capacity() when 'capacity' param is present
  - Three PHPUnit tests covering capacity increase, decrease (no notify), and NULL (unlimited) cases
affects:
  - 02-payments-ticketing (remaining plans — all three ATTN-01 triggers now complete)

# Tech tracking
tech-stack:
  added: []
  patterns: [notification-only function pattern — bp_events_update_capacity() handles only the notify logic, REST handler saves data separately]

key-files:
  created: []
  modified:
    - buddyboss-events/src/bp-events/bp-events-functions.php
    - buddyboss-events/src/bp-events/classes/class-bp-rest-events-endpoint.php
    - buddyboss-events/tests/phpunit/testcases/test-waitlist.php

key-decisions:
  - "bp_events_update_capacity() is notification-only — does not call bp_events_update_event() internally; REST handler saves capacity first then calls notify function to avoid double-save"
  - "NULL capacity (unlimited) always triggers waitlist broadcast if waitlisted users exist — infinite capacity means all spots are open"
  - "Capacity decrease with no waitlist is a silent no-op — returns true immediately after empty waitlist check"

patterns-established:
  - "Notification trigger functions are pure side-effect functions: they check state, optionally send notifications, always return true"
  - "REST update_item() calls auxiliary notification functions after the primary save, using $request->has_param() to detect changed fields"

requirements-completed:
  - ATTN-01

# Metrics
duration: 5min
completed: 2026-03-14
---

# Phase 2 Plan 02: Capacity-Increase Waitlist Trigger Summary

**bp_events_update_capacity() added to broadcast waitlist notifications when organizer increases event capacity, completing all three ATTN-01 spot-opening mechanisms**

## Performance

- **Duration:** ~5 min
- **Started:** 2026-03-14T10:35:00Z
- **Completed:** 2026-03-14T10:40:00Z
- **Tasks:** 1 (TDD: 2 commits — test RED then feat GREEN)
- **Files modified:** 3

## Accomplishments
- Added `bp_events_update_capacity($event_id, $new_capacity)` — notification-only function that fires `bp_events_notify_waitlist()` when a spot opens
- Wired into `update_item()` REST callback: capacity changes trigger the notify check after `bp_events_update_event()` saves the new value
- Replaced `markTestIncomplete` stub in test-waitlist.php with three concrete PHPUnit assertions (increase, decrease/no-op, NULL/unlimited)

## Task Commits

Each task was committed atomically following TDD:

1. **Task 1 (RED): Add failing tests** - `2a7f3c5` (test)
2. **Task 1 (GREEN): Implement bp_events_update_capacity + wiring** - `93d6b1b` (feat)

**Plan metadata:** _(to be added by final commit)_

_TDD tasks have two commits: test (RED) then feat (GREEN)._

## Files Created/Modified
- `buddyboss-events/src/bp-events/bp-events-functions.php` - Added `bp_events_update_capacity()` function after `bp_events_notify_waitlist()`
- `buddyboss-events/src/bp-events/classes/class-bp-rest-events-endpoint.php` - Added `bp_events_update_capacity()` call in `update_item()` after `rsvp_group_id` meta handling
- `buddyboss-events/tests/phpunit/testcases/test-waitlist.php` - Replaced stub with 3 real test methods

## Decisions Made
- `bp_events_update_capacity()` is notification-only — the REST handler already calls `bp_events_update_event()` to persist the capacity, so the notify function must not double-save. Function signature matches plan spec exactly.
- NULL capacity (unlimited) triggers notification because unlimited capacity means infinite spots available — all waitlisted users should be notified.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
- PHPUnit not installed (vendor/bin not present) — consistent with prior plan decisions; used `php -l` syntax verification as documented in STATE.md decisions.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- All three ATTN-01 waitlist trigger mechanisms are now complete: (1) cancel RSVP (Plan 01), (2) organizer removes attendee (Plan 01), (3) capacity increase (this plan)
- Ready to proceed to remaining Phase 2 plans (ticketing, Stripe integration)

## Self-Check: PASSED

- 02-02-SUMMARY.md: FOUND
- bp-events-functions.php: FOUND
- class-bp-rest-events-endpoint.php: FOUND
- commit 2a7f3c5 (RED): FOUND
- commit 93d6b1b (GREEN): FOUND

---
*Phase: 02-payments-ticketing*
*Completed: 2026-03-14*
