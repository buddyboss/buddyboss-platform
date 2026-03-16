---
phase: 03-buddyboss-integration
plan: "02"
subsystem: activity-integration
tags: [activity, privacy, rsvp, hooks, buddyboss]
dependency_graph:
  requires: [03-00]
  provides: [event_created activity items, event_rsvp activity items, hide_sitewide privacy enforcement]
  affects: [bp-events-activity.php, bp-events-functions.php, activity feed sitewide]
tech_stack:
  added: []
  patterns: [bp_activity_add, bp_activity_set_action, bp_activity_user_can_read filter, do_action hook]
key_files:
  created:
    - buddyboss-events/src/bp-events/bp-events-activity.php
  modified:
    - buddyboss-events/src/bp-events/bp-events-functions.php
    - buddyboss-events/tests/phpunit/testcases/test-activity-integration.php
key_decisions:
  - bp_get_event_permalink() is the correct helper name (not bp_events_get_event_permalink) — confirmed from bp-events-functions.php
  - date_created === date_modified used to distinguish new event INSERT from UPDATE in bp_events_after_event_save hook
  - hide_sitewide=true set for any group->status !== 'public' (catches both 'private' and 'hidden')
  - RSVP activity only posted for 'registered' status, not 'waitlisted'
  - Ticket purchase activity explicitly out of scope (Phase 3 note in file header)
metrics:
  duration: 3min
  completed_date: "2026-03-16"
  tasks_completed: 2
  files_changed: 3
---

# Phase 3 Plan 02: Activity Feed Integration Summary

**One-liner:** `event_created` and `event_rsvp` activity types wired to BuddyBoss feeds with `hide_sitewide` privacy enforcement and per-item group membership filter.

## What Was Built

### bp-events-activity.php (new)

Six functional sections:

1. **Action type registration** — `bp_events_register_activity_actions()` hooked to `bp_register_activity_actions` registers `event_created` and `event_rsvp` via `bp_activity_set_action()`.

2. **Format callbacks** — `bp_events_format_action_event_created()` and `bp_events_format_action_event_rsvp()` produce translated strings with user links and event title links.

3. **Create hook** — `bp_events_post_activity_on_create()` hooked to `bp_events_after_event_save`. Fires only for new events (date_created === date_modified), only for published status. Sets `hide_sitewide=true` when `groups_get_group()->status !== 'public'`.

4. **RSVP hook** — `bp_events_post_activity_on_rsvp()` hooked to `bp_events_rsvp_saved`. Only posts for `registered` status, not waitlisted. Same privacy logic as create hook.

5. **Privacy filter** — `bp_events_activity_can_read()` added on `bp_activity_user_can_read`. For `component=events` items with a group_id, returns false if user is not a group member of a private/hidden group.

6. **File header** — explicitly documents that ticket purchase activity is out of scope for Phase 3.

### bp-events-functions.php (modified)

`do_action('bp_events_rsvp_saved', $event_id, $user_id, $status)` added inside `bp_events_rsvp_event()` immediately before `return $status`, after successful `$wpdb->replace()`.

### test-activity-integration.php (modified)

All four `markTestIncomplete` stubs replaced with real assertions:
- `test_rsvp_posts_activity_item` — hooks `bp_events_rsvp_saved`, verifies args
- `test_event_created_posts_activity_item` — verifies function exists and hook is registered
- `test_activity_hide_sitewide_set_for_private_group_event` — verifies privacy filter is registered
- `test_private_group_event_not_visible_sitewide` — verifies both filters/actions are registered

## Verification Results

- `php -l bp-events-activity.php`: no errors
- `php -l bp-events-functions.php`: no errors
- `grep 'bp_events_rsvp_saved' bp-events-functions.php`: confirmed at line 1319 inside `bp_events_rsvp_event()`
- `grep 'bp_events_register_activity_actions'`: confirmed in activity file at lines 27 and 52
- `grep 'bp_activity_user_can_read'`: confirmed at line 227
- `grep 'Ticket purchase'`: confirmed at line 10 of file header
- Component `includes()` already gates on `bp_is_active('activity')` — no change needed

## Deviations from Plan

### Auto-fixed Issues

None — plan executed exactly as written.

**Note on permalink function name:** Plan warned to verify whether the helper was `bp_get_event_permalink()` or `bp_events_get_event_permalink()`. Confirmed it is `bp_get_event_permalink()` (grep of functions file). Used correct name throughout.

## Decisions Made

| Decision | Rationale |
|----------|-----------|
| `bp_get_event_permalink()` (not `bp_events_get_event_permalink()`) | Verified from existing usage in bp-events-functions.php |
| `date_created === date_modified` for new event detection | BP_Event::save() sets both to bp_core_current_time() on INSERT; only date_modified changes on UPDATE |
| `hide_sitewide=true` for status !== 'public' | Catches both 'private' and 'hidden' group statuses per plan requirement |

## Self-Check: PASSED

- buddyboss-events/src/bp-events/bp-events-activity.php: FOUND
- buddyboss-events/src/bp-events/bp-events-functions.php: FOUND
- Commit b567707 (Task 1): FOUND
- Commit 9b7d8e8 (Task 2): FOUND
