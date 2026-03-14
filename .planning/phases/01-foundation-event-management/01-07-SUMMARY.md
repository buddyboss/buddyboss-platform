---
phase: 01-foundation-event-management
plan: "07"
subsystem: bp-events-ui
tags: [wizard, javascript, creation-flow, rest-api, css, routing]
dependency_graph:
  requires:
    - 01-01  # REST endpoint (bp_events_create_event, BP_REST_Events_Endpoint)
    - 01-02  # Occurrence generation (RRULE handling)
  provides:
    - create-wizard-js
    - create-screen-route
    - create-asset-enqueue
  affects:
    - bp-events-loader.php
    - class-bp-events-component.php
tech_stack:
  added: []
  patterns:
    - Vanilla JS IIFE wizard (no framework)
    - RRULE string builder via concatenation (no library)
    - wp_localize_script for REST config injection
    - bp_is_action_variable() for create route detection
key_files:
  created:
    - buddyboss-events/src/bp-events/assets/js/bp-events-create.js
    - buddyboss-events/src/bp-events/screens/create.php
  modified:
    - buddyboss-events/src/bp-events/bp-events-loader.php
    - buddyboss-events/src/bp-events/classes/class-bp-events-component.php
    - buddyboss-events/src/bp-events/assets/css/bp-events.css
decisions:
  - Vanilla IIFE wizard — no React/Vue; follows WordPress JS Coding Standards (var, function keyword, tabs, single quotes)
  - screens/create.php added as separate file (not retrofitted into edit.php) — clean separation between create and edit flows
  - late_includes() guards create route before single-item check — prevents route shadowing
  - CSS added to bp-events.css (not a new file) — keeps asset bundle count minimal
  - auth_redirect() on create screen — unauthenticated users sent to login before wizard loads
metrics:
  duration: 4min
  completed_date: "2026-03-14"
  tasks_completed: 2
  files_changed: 5
---

# Phase 01 Plan 07: Event Creation Wizard Summary

Multi-step creation wizard with vanilla JS IIFE managing 6-step state, conditional recurrence, and REST POST to `/buddyboss/v1/events`.

## What Was Built

### Task 1: bp-events-create.js — Full Wizard

A self-contained IIFE (no dependencies beyond the localised `bpEventsCreate` object) that drives the entire creation flow:

- **State object** holding all event fields: type, title, description, dates, timezone, venue, virtual, capacity, recurrence_rule, status.
- **6 step render functions**: Event Type (radio), Basic Details (title + description), Date & Time (datetime-local inputs + timezone select), Location/Virtual (conditional field sets), Recurrence (conditional), Review & Publish (read-only table).
- **Step 4** shows in-person venue fields, virtual fields, or both, determined by `state.type` — no page reload.
- **Step 5 (Recurrence)** only inserted when the user checks "Make this a recurring event" on step 3. Step numbering adjusts dynamically.
- **RRULE builder**: `buildRrule()` concatenates `FREQ`, `INTERVAL`, optional `BYDAY` (weekly only), and either `COUNT` or `UNTIL` end condition — no library.
- **Submit**: `fetch()` POSTs JSON to `bpEventsCreate.restUrl` with `X-WP-Nonce: bpEventsCreate.nonce`. HTTP 201 + `data.permalink` → `window.location.href` redirect. Non-success → inline error in `#bb-rl-wizard-error`.
- **Save Draft** sets `state.status = 'draft'`; **Publish** sets `state.status = 'published'` before submitting.
- Validation: title required at step 2, start_date required at step 3.
- Step indicator strip re-renders on each step transition showing active/complete state.

### Task 2: Create Screen Routing + Enqueue

**screens/create.php**
- New screen handler for `bp_is_action_variable('create', 0)`.
- Calls `auth_redirect()` if the user is not logged in.
- Outputs the wizard container: `#bb-rl-event-create-form`, `#bb-rl-wizard-steps`, `#bb-rl-wizard-content`, `#bb-rl-wizard-error`, plus all four navigation buttons (`#bb-rl-wizard-prev/next/draft/publish`).

**class-bp-events-component.php**
- `late_includes()` now checks for the create route first (before single-item check) and requires `screens/create.php` when matched.

**bp-events-loader.php**
- `bp_events_enqueue_create_assets()` added — enqueues `bp-events-create.js` and localises `bpEventsCreate` (restUrl + nonce) only when on the events component create URL.
- `add_action('wp_enqueue_scripts', ...)` fires after component detection is reliable.

**bp-events.css**
- Wizard styles added: step indicators, field layout, event type radios, recurrence day checkboxes, review table, navigation buttons, error state.

## Deviations from Plan

### Auto-fixed Issues

None — plan executed exactly as written.

**Note:** The plan referenced `screens/single/edit.php` as a potential target for the wizard markup. On inspection, `edit.php` is exclusively for editing existing events (checks `bp_current_action() === 'edit'` and requires an existing event with edit permission). A separate `screens/create.php` was created instead to keep the flows cleanly separated. This aligns with the plan's stated alternative path and the `must_haves.artifacts` list (which lists `edit.php` only as the "create/edit screen handler").

## Self-Check: PASSED

All files verified present. Both task commits confirmed in git log.
