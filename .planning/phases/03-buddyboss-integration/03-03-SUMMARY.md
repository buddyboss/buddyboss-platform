---
phase: 03-buddyboss-integration
plan: "03"
subsystem: group-invites
tags: [invites, groups, rest-api, php, javascript, tdd]
dependency_graph:
  requires:
    - 03-00 (component scaffold, table_name_invites)
    - 03-01 (group extension, bp_events_get_current_event)
  provides:
    - bp_events_invite_member() — validated invite write to wp_bp_event_invites
    - POST /buddyboss/v1/events/{id}/invite — REST sub-route for batch invites
    - Invite panel UI on single/edit.php (group events only)
  affects:
    - bp-events-single.js (invite panel logic appended)
    - bp-events-loader.php (groupsRestUrl/groupId added to bpEventsSingle)
tech_stack:
  added: []
  patterns:
    - groups_is_user_member() for membership validation before insert
    - wpdb->replace() with UNIQUE KEY prevents duplicate invite rows
    - PHP server-side conditional on $event->group_id gates invite panel HTML
    - bpEventsSingle localization extended on edit screen with group context
    - jQuery IIFE appended to bp-events-single.js for invite panel interactions
key_files:
  created:
    - buddyboss-events/tests/phpunit/testcases/test-group-invite.php
  modified:
    - buddyboss-events/src/bp-events/bp-events-functions.php
    - buddyboss-events/src/bp-events/classes/class-bp-rest-events-endpoint.php
    - buddyboss-events/src/bp-templates/bp-nouveau/readylaunch/events/single/edit.php
    - buddyboss-events/src/bp-events/assets/js/bp-events-single.js
    - buddyboss-events/src/bp-events/bp-events-loader.php
decisions:
  - Invite panel gated entirely by server-side PHP conditional on $event->group_id — no JS show/hide needed
  - groupId and eventId passed via bpEventsSingle only on edit screen with group event — avoids create wizard where group_id is unknown
  - wpdb->replace() used for invite row — UNIQUE KEY on (event_id, invitee_id) prevents duplicates silently
  - invite_item() reuses update_item_permissions_check — only event organizer/admin can send invites
metrics:
  duration: "3 min"
  completed_date: "2026-03-16"
  tasks_completed: 2
  files_changed: 5
---

# Phase 3 Plan 03: Group Member Invite Flow Summary

**One-liner:** Group invite flow with PHP-gated invite panel, REST sub-route, and membership-validated invite write to wp_bp_event_invites.

## What Was Built

Group event organizers can now invite members of the linked group directly from the event edit screen. The feature has three layers:

1. **`bp_events_invite_member()`** — PHP function in `bp-events-functions.php` that validates group membership via `groups_is_user_member()` and writes a `pending` row to `wp_bp_event_invites` using `wpdb->replace()`. Returns `WP_Error('bp_events_invite_not_member')` when the invitee is not in the group.

2. **`POST /buddyboss/v1/events/{id}/invite`** — REST sub-route in `class-bp-rest-events-endpoint.php` with `invite_item()` method. Accepts a `user_ids` array, calls `bp_events_invite_member()` for each, and returns per-user success/error results. Protected by `update_item_permissions_check` (organizer or admin only).

3. **Invite panel UI** — `#bp-events-invite-panel` rendered in `single/edit.php` inside `<?php if ( ! empty( $event->group_id ) ) : ?>`. A jQuery IIFE appended to `bp-events-single.js` fetches the group member roster from `/buddyboss/v1/groups/{id}/members`, renders a searchable checklist, and POSTs selected `user_ids` to the invite REST endpoint. The loader passes `groupsRestUrl`, `groupId`, and `eventId` in `bpEventsSingle` only on the edit screen when the event has a group.

## Tasks Completed

| Task | Name | Commit | Files |
|------|------|--------|-------|
| 1 | Add bp_events_invite_member() and REST invite sub-route | 1024441 | bp-events-functions.php, class-bp-rest-events-endpoint.php, test-group-invite.php |
| 2 | Invite panel UI in single/edit.php and bp-events-single.js | c363b2f | single/edit.php, bp-events-single.js, bp-events-loader.php |

## Decisions Made

- **Server-side PHP conditional gates invite panel** — `if ( ! empty( $event->group_id ) )` in edit.php means no JS show/hide is needed. The panel is absent from the DOM entirely for standalone events.
- **groupId passed via bpEventsSingle only on edit screen** — the loader extends the localization only when `bp_is_action_variable( 'edit', 0 )` and group_id is set. This prevents the invite panel JS from activating on the view screen or create wizard.
- **`wpdb->replace()` for invite write** — the UNIQUE KEY `event_invitee (event_id, invitee_id)` on the table makes re-inviting idempotent; replace() updates the row back to `pending` if it exists.
- **`update_item_permissions_check` reused for invite route** — consistent with publish/cancel routes; only organizers and admins can send invites.

## Deviations from Plan

None — plan executed exactly as written.

## Verification Results

- `php -l` clean on bp-events-functions.php, class-bp-rest-events-endpoint.php, single/edit.php, bp-events-loader.php
- `bp_events_invite_member` confirmed in bp-events-functions.php (line 1474)
- `invite_item` confirmed in class-bp-rest-events-endpoint.php (route line 202, method line 867)
- `#bp-events-invite-panel` confirmed inside `if ( ! empty( $event->group_id ) )` in edit.php (lines 53-54)
- `groupsRestUrl` and `groupId` confirmed in bp-events-loader.php (line 135)
- `bpEventsSingle` confirmed as the localization object in bp-events-single.js (lines 16, 173)
- create.php confirmed unmodified

## Self-Check: PASSED
