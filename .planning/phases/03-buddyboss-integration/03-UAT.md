---
status: testing
phase: 03-buddyboss-integration
source: [03-01-SUMMARY.md, 03-02-SUMMARY.md, 03-03-SUMMARY.md, 03-04-SUMMARY.md]
started: 2026-03-17T00:00:00Z
updated: 2026-03-17T00:00:00Z
---

## Current Test
<!-- OVERWRITE each test - shows where we are -->

number: 2
name: Group Calendar Loads
expected: |
  Click the Events tab inside a group.
  A FullCalendar monthly/grid view loads showing events belonging to that group.
  No JS errors in the browser console.
awaiting: user response

## Tests

### 1. Group Events Tab Appears
expected: Navigate to any BuddyBoss group. An "Events" tab should appear in the group's navigation menu.
result: issue
reported: "Add new event form not showing"
severity: major

### 2. Group Calendar Loads
expected: Click the Events tab inside a group. A FullCalendar monthly/grid view loads showing events belonging to that group. No JS errors in the browser console.
result: [pending]

### 3. Group Calendar Only Shows That Group's Events
expected: Events from other groups (or standalone events) should NOT appear on a group's calendar. Only events that belong to that specific group appear.
result: [pending]

### 4. Private Group Events Hidden from Non-Members
expected: Log out (or use an account that is NOT a member of a private group). Navigate to that private group's Events tab — it should either not be accessible or show no events. The events should not leak into the site-wide calendar either.
result: [pending]

### 5. Event Creation Posts to Activity Feed
expected: Create and publish a new event (as an organizer). Go to the BuddyBoss activity feed (site-wide). An activity item should appear: "[User] created the event [Event Name]".
result: [pending]

### 6. RSVP Posts to Activity Feed
expected: RSVP to an event as a regular member. Go to the activity feed. An activity item should appear: "[User] is attending [Event Name]". (Waitlist registrations should NOT appear.)
result: [pending]

### 7. Private Group Event NOT in Site-Wide Feed
expected: Create an event inside a private group. Check the site-wide activity feed — the event creation activity should NOT appear there. It should only appear in the group's own activity feed.
result: [pending]

### 8. Group Member Invite Panel on Edit Screen
expected: Edit a group event (an event linked to a BuddyBoss group). An "Invite Members" panel should be visible on the edit screen showing a list/search of group members to invite.
result: [pending]

### 9. Invite Panel Absent for Standalone Events
expected: Edit a standalone event (one NOT linked to any group). The "Invite Members" panel should NOT appear — the edit screen shows no invite section.
result: [pending]

### 10. Send Invites to Group Members
expected: On a group event's edit screen, select one or more group members from the invite panel and click Send. The action should complete without errors. (The invite is saved — no email required for this test.)
result: [pending]

### 11. Member Profile Attending Tab
expected: Visit a member's profile at /members/{username}/events/attending. The page should load and show a list of events that member has RSVP'd to. If they haven't RSVP'd to anything, an empty state is shown (not a 404 or blank page).
result: [pending]

### 12. Member Profile Hosting Tab
expected: Visit a member's profile at /members/{username}/events/hosting. The page should load and show events that member has created/organised. If they haven't created any, an empty state is shown (not a 404 or blank page).
result: [pending]

## Summary

total: 12
passed: 0
issues: 1
pending: 11
skipped: 0

## Gaps

- truth: "Group Events tab appears in BuddyBoss group navigation"
  status: failed
  reason: "User reported: Add new event form not showing"
  severity: major
  test: 1
  root_cause: ""
  artifacts: []
  missing: []
  debug_session: ""
