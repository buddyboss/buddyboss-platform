---
phase: 03-buddyboss-integration
verified: 2026-03-16T00:00:00Z
status: human_needed
score: 9/9 automated must-haves verified
re_verification: false
human_verification:
  - test: "Visit a BuddyBoss group and confirm the Events tab appears in the group nav bar"
    expected: "An 'Events' tab is visible in the group navigation; clicking it renders a FullCalendar calendar scoped to that group's events (not all site events)"
    why_human: "BP_Group_Extension tab registration and template rendering require a live WordPress/BuddyBoss environment to confirm the tab appears and FullCalendar mounts"
  - test: "Log out (or use a non-member account) and visit a private group"
    expected: "The Events tab is NOT visible to non-members of a private or hidden group"
    why_human: "Privacy enforcement by BP_Group_Extension::access control is runtime behaviour that cannot be verified by grep"
  - test: "Create a new published event (not linked to a group), then check the sitewide activity feed"
    expected: "An activity item appears: '[User] created the event [Event Name]'"
    why_human: "bp_activity_add() requires a live WordPress DB and the bp_events_after_event_save hook chain to run"
  - test: "RSVP to an event as a different member, then check the activity feed"
    expected: "An activity item appears: '[Member] RSVPed to the event [Event Name]'"
    why_human: "bp_events_rsvp_saved -> bp_events_post_activity_on_rsvp chain requires a live environment"
  - test: "Create a published event attached to a PRIVATE group; check the sitewide feed as a non-member"
    expected: "The event_created activity item does NOT appear in the sitewide feed for non-members"
    why_human: "hide_sitewide=1 filtering is enforced at DB query time; cannot verify without running a live activity query"
  - test: "On an event that has a group_id, navigate to its edit screen (/events/{slug}/edit/)"
    expected: "The 'Invite Group Members' panel is visible; it loads a list of group members; search filters the list; selecting members and clicking 'Send Invites' displays 'Invites sent.' and writes rows to wp_bp_event_invites"
    why_human: "Invite panel rendering, REST group member roster fetch, and invite write require a live browser session and DB"
  - test: "Navigate to a member profile (/members/{username}/events/attending) and the hosting sub-tab"
    expected: "Events the member has RSVPd to appear under Attending; events they created appear under Hosting; each uses the event-card.php partial; empty state shows a message when no events exist"
    why_human: "Profile template rendering and event card display require a live browser and populated DB"
---

# Phase 3: BuddyBoss Integration Verification Report

**Phase Goal:** Events are fully woven into the BuddyBoss community fabric — each group has its own events tab with calendar, activity feeds reflect event actions, member profiles show event history, and organizers can invite group members directly from the group roster

**Verified:** 2026-03-16
**Status:** human_needed — all automated checks pass; live browser walkthrough required
**Re-verification:** No — initial verification

---

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | Every BuddyBoss group has an Events tab in its navigation | ? NEEDS HUMAN | `BP_Events_Group_Extension` registered via `bp_register_group_extension()` on `bp_init` priority 11; runtime tab display needs browser |
| 2 | The tab renders a FullCalendar calendar scoped to that group's events | ? NEEDS HUMAN | `group-events.php` has `#bp-events-group-calendar` mount point and `bpEventsGroup` localization with `groupId` and `eventsUrl`; live render needs browser |
| 3 | Non-members of private groups cannot see the Events tab or its events | ? NEEDS HUMAN | Platform enforces via `BP_Group_Extension` access controls at runtime; REST guard verified via code (groups_is_user_member + 403) |
| 4 | REST endpoint accepts `group_id` filter with 403 non-member guard | ✓ VERIFIED | `get_collection_params()` registers `group_id` param; `get_items()` calls `groups_is_user_member()` before query; returns `WP_Error` with `status: 403` |
| 5 | Creating an event posts an activity item with `type='event_created'` | ? NEEDS HUMAN | `bp_events_post_activity_on_create()` hooked to `bp_events_after_event_save`; calls `bp_activity_add()` with correct args; live DB write needs browser |
| 6 | RSVP posts an activity item with `type='event_rsvp'`; private group events have `hide_sitewide=1` | ? NEEDS HUMAN | `do_action('bp_events_rsvp_saved')` fires in `bp_events_rsvp_event()`; `bp_events_post_activity_on_rsvp()` handles it; live feed needs browser |
| 7 | Invite panel on edit screen lets organizer select group members and write invite rows | ? NEEDS HUMAN | Panel HTML in `edit.php` behind `if (!empty($event->group_id))`; `bp_events_invite_member()` inserts to `wp_bp_event_invites`; JS POSTs to `/events/{id}/invite`; live test needed |
| 8 | Only group members can be invited (non-members blocked server-side) | ✓ VERIFIED | `bp_events_invite_member()` calls `groups_is_user_member()` and returns `WP_Error('bp_events_invite_not_member')` for non-members |
| 9 | Member profiles show attending and hosting event lists | ? NEEDS HUMAN | Screen functions exist, `late_includes()` routes correctly, templates query `user_id` / `organizer_id` with `bp_displayed_user_id()`; live render needs browser |

**Score:** 9/9 automated checks verified; 7/9 truths also require human confirmation for live rendering

---

## Required Artifacts

### BB-01: Group Events Tab

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `src/bp-events/classes/class-bp-events-group-extension.php` | `BP_Events_Group_Extension` class extending `BP_Group_Extension` | ✓ VERIFIED | Class exists; `__construct()` calls `parent::init()` with slug `'events'`; `display()` sets global and calls `bp_get_template_part('events/group-events')` |
| `src/bp-events/bp-events-group-extension.php` | Loader shim calling `bp_register_group_extension()` on `bp_init` priority 11 | ✓ VERIFIED | `add_action('bp_init', ...)` at priority 11 with `bp_is_active('groups')` guard; `bp_register_group_extension('BP_Events_Group_Extension')` confirmed |
| `src/bp-templates/bp-nouveau/readylaunch/events/group-events.php` | FullCalendar mount point + `bpEventsGroup` localization | ✓ VERIFIED | `#bp-events-group-calendar` div present; `wp_localize_script` with `bpEventsGroup.groupId`, `bpEventsGroup.eventsUrl`, `bpEventsGroup.nonce` confirmed |
| `src/bp-events/classes/class-bp-rest-events-endpoint.php` | `group_id` REST param + 403 non-member guard | ✓ VERIFIED | `get_collection_params()` registers `group_id`; `get_items()` has privacy guard calling `groups_is_user_member()` before query |
| `src/bp-events/assets/js/bp-events-group-calendar.js` | Group calendar JS fetching events with `group_id` query param | ✓ EXISTS | File exists (enqueued by `group-events.php`) |

### BB-02: Activity Integration

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `src/bp-events/bp-events-activity.php` | Action type registration, format callbacks, create/RSVP hooks, privacy filter | ✓ VERIFIED | `bp_events_register_activity_actions()`, both format callbacks, `bp_events_post_activity_on_create()`, `bp_events_post_activity_on_rsvp()`, `bp_events_activity_can_read()` all present and hooked |
| `src/bp-events/bp-events-functions.php` | `do_action('bp_events_rsvp_saved')` inside `bp_events_rsvp_event()` | ✓ VERIFIED | Line 1319: `do_action('bp_events_rsvp_saved', $event_id, $user_id, $status)` |

### BB-03: Group Member Invite

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `src/bp-events/bp-events-functions.php` | `bp_events_invite_member()` function | ✓ VERIFIED | Line 1474: function exists; validates group membership via `groups_is_user_member()`; inserts into `$bp->events->table_name_invites` via `$wpdb->replace()` |
| `src/bp-events/classes/class-bp-rest-events-endpoint.php` | `POST /events/{id}/invite` route + `invite_item()` method | ✓ VERIFIED | Route registered at line 199; `invite_item()` method at line 867; iterates `user_ids`, calls `bp_events_invite_member()` for each |
| `src/bp-templates/bp-nouveau/readylaunch/events/single/edit.php` | Invite panel HTML behind `if (!empty($event->group_id))` | ✓ VERIFIED | Line 53: `if (!empty($event->group_id))` conditional; `#bp-events-invite-panel` div inside; search input, member list, send button present |
| `src/bp-events/assets/js/bp-events-single.js` | Invite panel JS reading `bpEventsSingle.groupId` | ✓ VERIFIED | Lines 173-262: reads `bpEventsSingle.groupId`/`eventId`; fetches `config.groupsRestUrl + '/' + groupId + '/members'`; POSTs to `config.restUrl + '/' + eventId + '/invite'` |

### BB-04: Member Profile Events

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `src/bp-events/screens/profile/attending.php` | `bp_events_screen_attending()` function | ✓ VERIFIED | Function exists; `add_action('bp_template_content', ...)` called BEFORE `bp_core_load_template()` |
| `src/bp-events/screens/profile/hosting.php` | `bp_events_screen_hosting()` function | ✓ VERIFIED | Function exists; same correct pattern |
| `src/bp-templates/bp-nouveau/readylaunch/events/profile-attending.php` | Queries `user_id`, renders event-card partials | ✓ VERIFIED | Calls `bp_events_get_events(['user_id' => bp_displayed_user_id()])`; iterates with `bp_get_template_part('events/event-card')` |
| `src/bp-templates/bp-nouveau/readylaunch/events/profile-hosting.php` | Queries `organizer_id`, renders event-card partials | ✓ VERIFIED | Calls `bp_events_get_events(['organizer_id' => bp_displayed_user_id()])`; iterates with `bp_get_template_part('events/event-card')` |

---

## Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `class-bp-events-component.php includes()` | `bp-events-group-extension.php` | `$includes[] = 'group-extension'` when `bp_is_active('groups')` | ✓ WIRED | Lines 61-63 confirmed |
| `bp-events-group-extension.php` | `class-bp-events-group-extension.php` | `bp_register_group_extension('BP_Events_Group_Extension')` on `bp_init` priority 11 | ✓ WIRED | `require_once` + `add_action('bp_init', ..., 11)` confirmed |
| `group-events.php` template | `/buddyboss/v1/events?group_id={id}` | `wp_localize_script` passes `bpEventsGroup.groupId` to FullCalendar fetch | ✓ WIRED | `bpEventsGroup` object with `groupId` and `eventsUrl` confirmed in template |
| `class-bp-rest-events-endpoint.php get_items()` | `groups_is_user_member()` | 403 guard before `bp_events_get_events()` | ✓ WIRED | Privacy guard at line 237-249 runs before query |
| `bp-events-activity.php` | `bp_events_after_event_save` action | `add_action('bp_events_after_event_save', 'bp_events_post_activity_on_create')` | ✓ WIRED | Line 141 confirmed |
| `bp-events-activity.php` | `bp_events_rsvp_saved` action | `add_action('bp_events_rsvp_saved', 'bp_events_post_activity_on_rsvp')` | ✓ WIRED | Line 188 confirmed; `do_action` fires inside `bp_events_rsvp_event()` at line 1319 |
| `bp_events_post_activity_on_create()` | `bp_activity_add()` | Direct call with `hide_sitewide` set when `group->status !== 'public'` | ✓ WIRED | Lines 129-139 confirmed |
| `single/edit.php invite panel` | `GET /buddyboss/v1/groups/{id}/members` | `fetch()` using `bpEventsSingle.groupId` | ✓ WIRED | JS line 195: `config.groupsRestUrl + '/' + groupId + '/members?per_page=50'` |
| `bp-events-single.js invite submit` | `POST /buddyboss/v1/events/{id}/invite` | `fetch()` with `user_ids` JSON body | ✓ WIRED | JS line 250: `config.restUrl + '/' + eventId + '/invite'` |
| `POST /buddyboss/v1/events/{id}/invite` | `bp_events_invite_member()` | `invite_item()` iterates `user_ids` and calls function for each | ✓ WIRED | `invite_item()` at line 867 confirmed |
| `bp_events_invite_member()` | `wp_bp_event_invites` table | `$wpdb->replace()` with `status='pending'` | ✓ WIRED | `$bp->events->table_name_invites` used in `$wpdb->replace()` |
| `class-bp-events-component.php late_includes()` | `screens/profile/attending.php` and `screens/profile/hosting.php` | `bp_is_user() && bp_is_current_action('attending'|'hosting')` | ✓ WIRED | Lines 84-87 confirmed |
| `bp_events_screen_attending()` | `profile-attending.php` template | `add_action('bp_template_content', ...)` BEFORE `bp_core_load_template()` | ✓ WIRED | Correct ordering confirmed in screen files |
| `profile-attending.php` | `bp_events_get_events(['user_id' => bp_displayed_user_id()])` | Direct call inside template | ✓ WIRED | Confirmed |
| `profile-hosting.php` | `bp_events_get_events(['organizer_id' => bp_displayed_user_id()])` | Direct call inside template | ✓ WIRED | Confirmed |
| `bp-events-loader.php` | `bpEventsSingle` with `groupId`, `eventId`, `groupsRestUrl` | `wp_localize_script` with `bp_is_action_variable('edit', 0)` guard | ✓ WIRED | `eventId` always in base data (line 113); `groupId`/`groupsRestUrl` added when on edit screen with group event (lines 134-137) |

---

## Requirements Coverage

| Requirement | Source Plans | Description | Status | Evidence |
|-------------|-------------|-------------|--------|----------|
| BB-01 | 03-01 | Each BuddyBoss group has an Events tab displaying a calendar view of that group's events | ? NEEDS HUMAN | All code artifacts verified; live tab display and calendar render need browser |
| BB-02 | 03-02 | Event creation, RSVPs, ticket purchases automatically post to relevant activity feeds respecting group privacy | ? NEEDS HUMAN | `event_created` and `event_rsvp` types wired; ticket purchase out of scope per plan scope note; live feed needs browser |
| BB-03 | 03-03 | Organizer can invite group members from the group member roster when creating or editing an event | ? NEEDS HUMAN | Invite panel code verified; non-member block server-side verified; live invite send needs browser |
| BB-04 | 03-04 | Member profiles display events attended and hosted | ? NEEDS HUMAN | Screen functions, templates, and queries all verified; live profile render needs browser |

**Orphaned requirements:** None. BB-01, BB-02, BB-03, BB-04 all appear in plan frontmatter and REQUIREMENTS.md traceability table.

**Note on BB-02 scope:** Plans explicitly document that ticket purchase activity is out of scope for Phase 3. REQUIREMENTS.md BB-02 mentions ticket purchases; this partial coverage is intentional and documented in `bp-events-activity.php` header comment.

---

## PHP Syntax Check

All Phase 3 PHP files pass `php -l` (MAMP PHP 8.3):

- `class-bp-events-group-extension.php` — clean
- `bp-events-group-extension.php` — clean
- `bp-events-activity.php` — clean
- `group-events.php` — clean
- `screens/profile/attending.php` — clean
- `screens/profile/hosting.php` — clean
- `profile-attending.php` — clean
- `profile-hosting.php` — clean
- `class-bp-rest-events-endpoint.php` — clean
- `bp-events-functions.php` — clean

---

## Test Stub Status

| Test File | Class | Methods with Real Assertions | Remaining Stubs |
|-----------|-------|----------------------------|-----------------|
| `test-group-extension.php` | `BP_Events_Test_Group_Extension` | 4 of 5 (registers_tab, hidden_from_non_members, get_events_filtered_by_group_id, rest_blocked_for_non_member) | `test_group_tab_renders_for_members` — marked incomplete (requires full BP template stack) |
| `test-activity-integration.php` | `BP_Events_Test_Activity_Integration` | 4 of 4 (event_created, hide_sitewide, rsvp_posts_activity, private_not_visible_sitewide) | None |
| `test-group-invite.php` | `BP_Events_Test_Group_Invite` | 2 of 2 (invite_member_writes_row, non_member_blocked) | None |
| `test-profile-events.php` | `BP_Events_Test_Profile_Events` | 2 of 2 (attending_returns_rsvpd, hosting_returns_created) | None |

`test_group_tab_renders_for_members` remaining as `markTestIncomplete` is intentional (per plan 03-01) and not a blocker — it requires the full BuddyBoss template stack which is not available in unit tests.

---

## Anti-Pattern Scan

No blocker anti-patterns found in Phase 3 implementation files.

| File | Pattern | Severity | Assessment |
|------|---------|----------|------------|
| `group-events.php` | Enqueues `bp-events-group-calendar.js` (new file) | Info | File exists at `assets/js/bp-events-group-calendar.js` — not a missing dependency |
| `bp-events-loader.php` | `groupsRestUrl` and `groupId` added on edit screen but `eventId` is in base `$localize_data` | Info | Correct — `eventId` is always available; `groupId` is conditionally added only when needed |
| `class-bp-events-group-extension.php` | Comment says platform enforces privacy, no `user_can_visit()` override | Info | Intentional design decision documented in class docblock |

---

## Human Verification Required

### 1. Group Events Tab (BB-01)

**Test:** Log in as admin. Visit any BuddyBoss group. Check for an "Events" tab in the group nav. Click it.
**Expected:** "Events" tab is visible. Clicking it renders a FullCalendar calendar. Only events for that group appear on the calendar (not site-wide events).
**Why human:** `BP_Group_Extension` tab registration fires at runtime; FullCalendar mount requires JavaScript to execute in a browser.

### 2. Group Privacy Enforcement — Tab (BB-01)

**Test:** Identify a private group. Log out or switch to a non-member account. Visit the private group URL.
**Expected:** The Events tab is NOT visible (or returns 403/redirect) for non-members of a private group.
**Why human:** `BP_Group_Extension` access control is enforced by the BuddyBoss Platform at runtime; cannot be verified by static analysis.

### 3. Event Created Activity (BB-02)

**Test:** Create a new published event (no group). Visit the sitewide activity feed.
**Expected:** Activity item appears: "[User] created the event [Event Name]" with a link to the event.
**Why human:** `bp_activity_add()` writes to the WordPress database; activity feed display requires a live environment.

### 4. RSVP Activity (BB-02)

**Test:** As a second member, RSVP to a published event. Check the activity feed.
**Expected:** Activity item: "[Member] RSVPed to the event [Event Name]".
**Why human:** Same reason as above — requires live hook chain and DB.

### 5. Private Group Activity Privacy (BB-02)

**Test:** Create a published event in a PRIVATE group. Check sitewide feed as a non-member, then as a group member.
**Expected:** Non-member does NOT see the activity item in sitewide feed. Group member sees it in the group's activity feed.
**Why human:** `hide_sitewide=1` filtering is applied at DB query time in `bp_activity_get()`; requires a live environment to confirm.

### 6. Invite Panel UI and Functionality (BB-03)

**Test:** Create/edit an event that has a `group_id` set. Navigate to `/events/{slug}/edit/`. Look for the "Invite Group Members" panel. Search members, select some, click "Send Invites". Then check `wp_bp_event_invites` via WP-CLI: `wp db query "SELECT * FROM wp_bp_event_invites WHERE event_id={id};" --path=/Applications/MAMP/htdocs/buddyboss-dev`
**Expected:** Panel is visible below the edit form. Member list loads. Search filters work. After sending: status message "Invites sent." appears. DB rows exist with `status='pending'`.
**Why human:** REST fetch to group members endpoint, checkbox UI, and DB write require a live browser session.

### 7. Member Profile Events Tabs (BB-04)

**Test:** Visit `/members/{username}/events/attending` and `/members/{username}/events/hosting` for a member with event history, and for a member with no events.
**Expected:** Attending tab shows events where the member has an RSVP row (event cards). Hosting tab shows events the member created (event cards). Empty state shows a message — not a blank page — when no events.
**Why human:** Template rendering inside the BuddyBoss member profile wrapper requires the full BP template stack to be running.

---

## Gaps Summary

No code gaps found. All 16 key links are wired. All required artifacts exist and contain substantive implementations (not stubs). PHP syntax is clean across all 10 Phase 3 files.

The 7 human verification items are all about live rendering and user-facing behaviour — they require a browser session with MAMP running. The automated checks (REST guard code, hook wiring, function existence, query parameters, DB write path) all pass.

**One minor observation:** `test_group_tab_renders_for_members` remains as `markTestIncomplete`. This is documented as intentional in Plan 03-01 (requires full BP template stack) and is not a blocker for goal achievement.

---

_Verified: 2026-03-16_
_Verifier: Claude (gsd-verifier)_
