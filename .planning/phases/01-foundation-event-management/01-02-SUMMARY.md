---
phase: 01-foundation-event-management
plan: "02"
subsystem: REST API / FullCalendar feed
tags: [rest-api, fullcalendar, events, tdd, php]
dependency_graph:
  requires:
    - 01-01 (BP_Event class, bp_events_create_event, bp_events_get_events)
  provides:
    - GET /buddyboss/v1/events?_fc=1&start=...&end=... FullCalendar feed
    - prepare_item_for_response() with ISO8601 _fc branch
    - get_collection_params() with start/end/_fc params
  affects:
    - 01-06 (FullCalendar JS will call this feed endpoint)
tech_stack:
  added: []
  patterns:
    - FullCalendar 6 JSON event object shape (id, title, start, end, url, extendedProps)
    - REST param branching via _fc flag
    - MySQL datetime → ISO8601 via str_replace(' ', 'T', ...)
key_files:
  created: []
  modified:
    - buddyboss-events/src/bp-events/classes/class-bp-rest-events-endpoint.php
    - buddyboss-events/tests/phpunit/testcases/test-event-crud.php
decisions:
  - _fc=1 param gates FullCalendar shape — same endpoint serves both FC feed and standard REST consumers without a separate route
  - start/end params take precedence over from/to so FullCalendar's native param names work without remapping at the JS layer
  - per_page defaults to 200 in FC mode — FullCalendar fetches the whole visible range in one request; 200 is a safe ceiling for typical calendar windows
  - prepare_item_for_response() returns get_data() values when _fc=1 so get_items() can build a flat JSON array instead of a WP_REST_Response array
metrics:
  duration: 5min
  completed_date: "2026-03-14"
  tasks_completed: 1
  files_modified: 2
---

# Phase 01 Plan 02: FullCalendar Feed Mode REST Endpoint Summary

REST events endpoint extended with FullCalendar 6 feed mode — `?_fc=1&start=ISO8601&end=ISO8601` returns a flat JSON array of FC event objects with ISO8601 dates, url, and extendedProps; standard non-FC requests return the full BP_Event field set unchanged.

## Tasks Completed

| # | Task | Commit | Files |
|---|------|--------|-------|
| 1 | Implement get_items() FullCalendar feed shape + real CRUD tests | ce75290 | class-bp-rest-events-endpoint.php, test-event-crud.php |

## What Was Built

### get_collection_params() — new params added

- `start` — FullCalendar range start (ISO8601), maps to `from` filter arg
- `end` — FullCalendar range end (ISO8601), maps to `to` filter arg
- `_fc` — integer flag (default 0); when 1, activates FC feed mode
- `status` — now has `default: published` and uses `sanitize_key`

### get_items() — FullCalendar feed mode

When `_fc=1`:
- Maps `start`/`end` params → `from`/`to` args for `bp_events_get_events()`
- Defaults `per_page` to 200 (FullCalendar fetches the entire visible range)
- Calls `prepared->get_data()` to build a flat array, not a nested response array

### prepare_item_for_response() — _fc branch

When `_fc=1`, returns FullCalendar 6 event object:
```json
{
  "id": 42,
  "title": "Team Standup",
  "start": "2026-03-15T09:00:00",
  "end": "2026-03-15T09:30:00",
  "url": "https://example.com/events/team-standup/",
  "extendedProps": {
    "type": "in-person",
    "venue": "Main Office",
    "status": "published"
  }
}
```

MySQL `YYYY-MM-DD HH:MM:SS` dates are converted to ISO8601 via `str_replace(' ', 'T', ...)`.

### test-event-crud.php — real test assertions

Replaced `markTestIncomplete` stubs with full assertions:
- `test_create_in_person_event` — verifies venue_name, venue_address, capacity persist (EVNT-01)
- `test_create_virtual_event` — verifies virtual_url, virtual_type persist (EVNT-02)
- `test_draft_not_in_published_query` — verifies draft events absent from published query (EVNT-04)

## Deviations from Plan

None — plan executed exactly as written.

## Verification

- `php -l class-bp-rest-events-endpoint.php` — no errors
- `str_replace(' ', 'T', ...)` present in _fc branch at lines 576–577
- `get_collection_params()` contains start, end, _fc, status params
- Tests contain real assertions (no longer markTestIncomplete stubs)

## Self-Check: PASSED

- FOUND: buddyboss-events/src/bp-events/classes/class-bp-rest-events-endpoint.php
- FOUND: buddyboss-events/tests/phpunit/testcases/test-event-crud.php
- FOUND: commit ce75290
