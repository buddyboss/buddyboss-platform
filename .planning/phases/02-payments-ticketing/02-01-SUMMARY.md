---
phase: 02-payments-ticketing
plan: "01"
subsystem: rsvp-backend
tags: [rsvp, waitlist, notifications, rest-api, php]
dependency_graph:
  requires:
    - "01-08: attendees DB table exists (bp_event_attendees)"
    - "01-00: REST endpoint class registered"
  provides:
    - "bp_events_rsvp_event() — capacity-aware RSVP function"
    - "bp_events_cancel_rsvp() — cancels RSVP and triggers waitlist broadcast"
    - "bp_events_notify_waitlist() — BuddyBoss notification + wp_mail broadcast"
    - "POST /buddyboss/v1/events/{id}/rsvp — REST RSVP creation"
    - "DELETE /buddyboss/v1/events/{id}/rsvp — REST RSVP cancellation"
    - "GET /buddyboss/v1/events/{id}/attendees — registered attendees list"
  affects:
    - "02-02 onwards: single-event template JS depends on these routes"
    - "bp_events_format_notifications(): waitlist_spot_open case now handled"
tech_stack:
  added: []
  patterns:
    - "$wpdb->replace() for upsert RSVP rows"
    - "broadcast-model waitlist: all waitlisted users notified simultaneously"
    - "Permission delegation: rsvp_item_permissions_check() calls bp_events_user_can_rsvp()"
key_files:
  created: []
  modified:
    - buddyboss-events/src/bp-events/bp-events-functions.php
    - buddyboss-events/src/bp-events/classes/class-bp-rest-events-endpoint.php
    - buddyboss-events/tests/phpunit/testcases/test-rsvp.php
    - buddyboss-events/tests/phpunit/testcases/test-waitlist.php
decisions:
  - "Broadcast waitlist model: bp_events_notify_waitlist() notifies ALL waitlisted users simultaneously — first to re-RSVP gets the spot; simpler than queue-based promotion"
  - "organizer_id field used for organizer check in cancel_rsvp_item (not user_id) to match BP_Event object property"
  - "PHPUnit tests written with real assertions; WP test suite not installed in this environment so automated run deferred — php -l used as syntax verification"
metrics:
  duration: "8min"
  completed_date: "2026-03-14"
  tasks_completed: 2
  files_modified: 4
---

# Phase 2 Plan 01: RSVP Backend Foundation Summary

**One-liner:** Capacity-aware RSVP functions with broadcast-model waitlist notification via BuddyBoss notifications and wp_mail, plus three REST sub-routes (POST/DELETE /rsvp, GET /attendees).

## What Was Built

### Task 1 — RSVP functions in bp-events-functions.php

Six new functions appended after `bp_get_events_directory_url()`:

1. **`bp_events_user_can_rsvp()`** — validates login, event exists + published, optional rsvp_group_id group membership check. Returns `true` or `WP_Error`.

2. **`bp_events_rsvp_event()`** — counts registered rows, compares to capacity, uses `$wpdb->replace()` for upsert. Returns `'registered'` or `'waitlisted'`.

3. **`bp_events_cancel_rsvp()`** — reads current status, deletes row, calls `bp_events_notify_waitlist()` if the cancelled row was `registered`.

4. **`bp_events_get_attendees()`** — SELECT with status filter, ORDER BY date_created ASC.

5. **`bp_events_get_waitlist()`** — wrapper calling `bp_events_get_attendees($event_id, 'waitlisted')`.

6. **`bp_events_notify_waitlist()`** — sends `bp_notifications_add_notification()` + `wp_mail()` to every waitlisted user with broadcast disclaimer.

Also extended `bp_events_format_notifications()` switch to handle `'waitlist_spot_open'` case before `default`.

### Task 2 — REST sub-routes in class-bp-rest-events-endpoint.php

Three new `register_rest_route()` calls after the gcal-url registration:

- `POST /buddyboss/v1/events/{id}/rsvp` → `rsvp_item()`
- `DELETE /buddyboss/v1/events/{id}/rsvp` → `cancel_rsvp_item()`
- `GET /buddyboss/v1/events/{id}/attendees` → `get_attendees()`

Four new methods added to the class:

- **`rsvp_item_permissions_check()`** — 401 guard + delegates to `bp_events_user_can_rsvp()`
- **`rsvp_item()`** — calls `bp_events_rsvp_event()`, recomputes at_capacity, returns `{status, at_capacity}`
- **`cancel_rsvp_item()`** — handles self-cancel and organizer/admin cross-user cancel; returns `{cancelled}`
- **`get_attendees()`** — maps attendee rows to `{user_id, display_name, avatar_url, status}`

## Deviations from Plan

### Environment Deviation

**PHPUnit automated tests could not be executed** — the WordPress PHPUnit test suite (`/tmp/wordpress-tests-lib`) is not installed in this environment and no PHPUnit binary is available. Tests in `test-rsvp.php` and `test-waitlist.php` were replaced with real assertions (removing all `markTestIncomplete` stubs), satisfying the TDD documentation intent. PHP syntax validation (`php -l`) was used as the available automated verification.

The test `test_capacity_increase_triggers_waitlist_notification` remains marked as incomplete — capacity-increase hook is deferred to a later plan (not in scope for this plan).

All other implementation requirements are complete and verified via `php -l` and `grep` presence checks.

## Commits

| Task | Commit | Message |
|------|--------|---------|
| 1 | `dbdb43f` | feat(02-01): add RSVP functions and waitlist notification to bp-events-functions.php |
| 2 | `7dc5509` | feat(02-01): add RSVP REST sub-routes to class-bp-rest-events-endpoint.php |

## Self-Check: PASSED

- bp-events-functions.php: No syntax errors detected
- class-bp-rest-events-endpoint.php: No syntax errors detected
- Six function names confirmed present (grep count: 6)
- waitlist_spot_open case confirmed (grep count: 2 — switch case + bp_notifications call)
- Four REST methods confirmed present (grep count: 4)
- Both commits verified in git log
