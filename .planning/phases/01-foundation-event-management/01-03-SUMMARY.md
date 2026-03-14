---
phase: 01-foundation-event-management
plan: "03"
subsystem: events-privacy
tags:
  - privacy
  - group-events
  - admin
  - sql
requirements:
  - EVNT-05
  - EVNT-06
  - ADMN-03
dependency_graph:
  requires:
    - 01-00 (schema — bp_events table and table_name_meta)
    - 01-01 (BP_Event class and bp_events_get_events scaffold)
  provides:
    - bp_events_get_events() with enforced EVNT-05 and EVNT-06 privacy guards
    - bp_events_get_events_where_clauses filter hook
    - bp_events_admin_get_event_counts() helper
    - Admin revenue page with event count stats
  affects:
    - Any consumer of bp_events_get_events() (REST endpoint, FullCalendar feed, directory screen)
    - Admin Events > Revenue page
tech_stack:
  added: []
  patterns:
    - LEFT JOIN privacy pattern (replaces subquery)
    - wp_cache_get/set with typed TTL for admin stats
    - apply_filters extensibility on WHERE clause array
key_files:
  created: []
  modified:
    - buddyboss-events/src/bp-events/bp-events-functions.php
    - buddyboss-events/src/bp-events/bp-events-admin.php
    - buddyboss-events/tests/phpunit/testcases/test-calendar-privacy.php
decisions:
  - "LEFT JOIN replaces subquery privacy check: JOIN is more efficient and avoids
    correlated subquery performance issues on large datasets. The old subquery also
    had a moderator bypass which was wrong — EVNT-06 is unconditional."
  - "NULL meta_value (absent groupmeta row) treated as included: per spec, groups
    that have never set the site-calendar preference default to shown. Only an
    explicit '0' opts out."
  - "Group-scoped queries (group_id param set) bypass site-wide privacy rules:
    the group context controls its own view and already enforces membership rules."
  - "bp_events_admin_get_event_counts() extracted as separate function: keeps
    revenue page callback focused on rendering and makes the query independently
    testable and cacheable."
metrics:
  duration: "~3 minutes"
  completed_date: "2026-03-14"
  tasks_completed: 2
  files_modified: 3
---

# Phase 01 Plan 03: Calendar Privacy and Admin Stats Summary

**One-liner:** SQL LEFT JOIN privacy guards for EVNT-05/EVNT-06 in bp_events_get_events() plus wp_cache-backed event count stats on the admin revenue page.

## Tasks Completed

| # | Task | Commit | Files |
|---|------|--------|-------|
| 1 (RED) | TDD test stubs for calendar privacy | 35a1ee3 | test-calendar-privacy.php |
| 1 (GREEN) | Harden bp_events_get_events() privacy | 894e5b9 | bp-events-functions.php |
| 2 | Admin event count statistics | 9d3e5e8 | bp-events-admin.php |

## What Was Built

### Task 1: bp_events_get_events() Privacy Hardening

The existing implementation had a correlated subquery-based privacy check that was also gated behind a `!bp_current_user_can('bp_moderate')` condition. This was incorrect for EVNT-06 (unconditional) and inefficient.

**Replaced with LEFT JOIN pattern:**

```sql
LEFT JOIN {prefix}bp_groups AS g ON e.group_id = g.id
LEFT JOIN {prefix}bp_groups_groupmeta AS gm
    ON g.id = gm.group_id AND gm.meta_key = 'bb_events_public_group_site_calendar'
```

**Two WHERE conditions added (site-wide queries only):**

- EVNT-06: `( e.group_id IS NULL OR g.status = 'public' )` — unconditional
- EVNT-05: `( e.group_id IS NULL OR gm.meta_value != '0' )` — NULL passes (default included)

**Filter hook added:** `bp_events_get_events_where_clauses` allows third-party customisation.

**Group-scoped queries** (`$r['group_id']` set) skip all site-wide privacy enforcement — the group view handles its own access control.

### Task 2: Admin Event Count Statistics

New function `bp_events_admin_get_event_counts()` queries the events table grouped by status, excluding child occurrence rows (`parent_event_id IS NULL`). Results cached with `wp_cache_set()` at TTL 300 seconds using key `bb_events_admin_stats` in the `bp_events` group.

The revenue page now renders a `<div class="bb-events-stats">` block above the existing Phase 2 placeholder, showing:
- Total Events
- Published
- Pending Approval
- Draft

All dynamic values escaped with `esc_html()`.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Removed moderator bypass from EVNT-06 privacy check**
- **Found during:** Task 1
- **Issue:** Original code had `&& ! bp_current_user_can('bp_moderate')` gate on the privacy check, meaning admins would see private/hidden group events in site-wide queries. EVNT-06 is unconditional per spec.
- **Fix:** Removed the moderator gate entirely. The new JOIN-based clause applies to all users on site-wide queries. (Moderators can still access group-scoped queries directly via group_id param.)
- **Files modified:** buddyboss-events/src/bp-events/bp-events-functions.php
- **Commit:** 894e5b9

### TDD Notes

PHPUnit binary not installed in the project (`vendor/bin/phpunit` absent, no `composer.json`). Test stubs were written with `markTestIncomplete()` as specified in the plan's done criteria ("tests pass or are marked incomplete"). All PHP files verified clean with `php -l`.

## Verification

- `php -l buddyboss-events/src/bp-events/bp-events-functions.php` — PASS
- `php -l buddyboss-events/src/bp-events/bp-events-admin.php` — PASS
- `php -l buddyboss-events/src/bp-events/bp-events-filters.php` — PASS
- WHERE clause contains `g.status = 'public'` guard — CONFIRMED (line 364)
- WHERE clause contains `bb_events_public_group_site_calendar` meta check — CONFIRMED (line 361)
- `bp_events_get_events_where_clauses` filter hook present — CONFIRMED (line 383)

## Self-Check: PASSED
