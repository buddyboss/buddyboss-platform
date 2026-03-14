---
phase: 01-foundation-event-management
plan: "06"
subsystem: frontend-calendar
tags: [fullcalendar, javascript, enqueue, calendar-ui, directory]
dependency_graph:
  requires:
    - 01-02  # REST API endpoint (FullCalendar events.url)
    - 01-03  # REST _fc feed mode
  provides:
    - calendar-ui-directory  # FullCalendar rendered on /events/
  affects:
    - bp-events-filters.php  # new enqueue hook
    - screens/directory.php  # calendar markup
tech_stack:
  added:
    - FullCalendar 6.1.20 (vendored global bundle, no CDN dependency)
  patterns:
    - wp_enqueue_script with local vendor asset
    - wp_localize_script for PHP-to-JS config bridge
    - FullCalendar events.url pointing to REST endpoint with _fc=1 feed mode
key_files:
  created:
    - buddyboss-events/src/bp-events/assets/js/vendor/fullcalendar.min.js
    - buddyboss-events/src/bp-events/assets/js/bp-events-calendar.js
    - buddyboss-events/src/bp-events/assets/css/bp-events.css
  modified:
    - buddyboss-events/src/bp-events/bp-events-filters.php
    - buddyboss-events/src/bp-events/screens/directory.php
decisions:
  - FullCalendar vendor file is index.global.min.js (284KB minified, 700KB unminified) — plan size estimate of 600KB was for unminified; minified is the correct production asset
  - bp_events_enqueue_calendar_assets() added as separate function from existing bp_events_enqueue_scripts() to keep calendar assets isolated and easier to extend
  - bpEventsSettings localised on both bp-events handle (general) and bp-events-calendar handle (calendar-specific) — calendar handle takes precedence on directory pages
  - assets_url uses buddypress()->plugin_url . 'src/bp-events/assets/' consistent with how the platform plugin is structured after rsync to MAMP
metrics:
  duration: 5min
  completed_date: "2026-03-14"
  tasks_completed: 2
  files_created: 5
  files_modified: 2
---

# Phase 01 Plan 06: FullCalendar Frontend Integration Summary

**One-liner:** FullCalendar 6.1.20 vendored locally and wired to the existing REST endpoint via _fc=1 feed mode, with month/list toggle on the /events/ directory page.

## What Was Built

The events directory page (`/events/`) now renders a FullCalendar calendar. The calendar reads from the existing REST API endpoint (`buddyboss/v1/events`) using the `_fc=1` feed parameter established in Plan 03. Month and List view toggles are present. All assets are loaded from local vendor files — no CDN dependency.

## Tasks Completed

| Task | Name | Commit | Files |
|------|------|--------|-------|
| 1 | Download FullCalendar bundle and create calendar JS/CSS | 3805d9f | assets/js/vendor/fullcalendar.min.js, assets/js/bp-events-calendar.js, assets/css/bp-events.css |
| 2 | Enqueue assets and add calendar markup to directory template | 4711ad3 | bp-events-filters.php, screens/directory.php |

## Decisions Made

1. **FullCalendar bundle size:** The plan expected >500KB but `index.global.min.js` is 284KB minified (the unminified `index.global.js` is 700KB). The minified file IS the correct full global bundle — the size check in the plan was based on the unminified size. Used minified for production.

2. **Separate enqueue function:** Added `bp_events_enqueue_calendar_assets()` as a distinct function rather than extending `bp_events_enqueue_scripts()`. This keeps calendar-specific assets (FullCalendar vendor) separate from the general events script, making it easier to disable/replace the calendar independently.

3. **bpEventsSettings on calendar handle:** Both the general `bp-events` handle and the new `bp-events-calendar` handle localize `bpEventsSettings`. The `bp-events-calendar.js` reads `window.bpEventsSettings` — whichever localize call fires last wins; since `bp_events_enqueue_calendar_assets` runs after `bp_events_enqueue_scripts` (same priority, registered later), the calendar-specific payload is used on directory pages.

4. **Asset URL construction:** Uses `buddypress()->plugin_url . 'src/bp-events/assets/'` which matches how the rsync sync maps the source directory into the MAMP plugin location.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Plan size verification criterion incorrect**
- **Found during:** Task 1 verification
- **Issue:** Plan said `fullcalendar.min.js` must be >500KB; actual minified bundle is 284KB. The plan author confused unminified (700KB) with minified sizes.
- **Fix:** Accepted 284KB as correct — file identity verified by header comment `FullCalendar Standard Bundle v6.1.20`. No code change needed.
- **Files modified:** None
- **Commit:** N/A (documentation deviation only)

## Self-Check: PASSED

Files verified:
- FOUND: buddyboss-events/src/bp-events/assets/js/vendor/fullcalendar.min.js (283987 bytes)
- FOUND: buddyboss-events/src/bp-events/assets/js/bp-events-calendar.js
- FOUND: buddyboss-events/src/bp-events/assets/css/bp-events.css
- FOUND: buddyboss-events/src/bp-events/bp-events-filters.php (contains bp_events_enqueue_calendar_assets)
- FOUND: buddyboss-events/src/bp-events/screens/directory.php (contains bb-rl-events-calendar)

Commits verified:
- FOUND: 3805d9f — feat(01-06): vendor FullCalendar 6.1.20 and create calendar JS/CSS
- FOUND: 4711ad3 — feat(01-06): enqueue FullCalendar on events directory and add calendar markup
