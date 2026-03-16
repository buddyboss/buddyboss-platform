---
gsd_state_version: 1.0
milestone: v1.0
milestone_name: milestone
status: planning
stopped_at: Completed 03-buddyboss-integration 03-02-PLAN.md
last_updated: "2026-03-16T10:53:02.251Z"
last_activity: 2026-03-14 — Phase 2 complete; human approved all 8 RSVP/waitlist/calendar end-to-end scenarios
progress:
  total_phases: 3
  completed_phases: 2
  total_plans: 21
  completed_plans: 18
  percent: 67
---

# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-03-10)

**Core value:** Site admins on BuddyBoss can create, manage, and monetize events deeply embedded in their community's groups, activity feeds, and member profiles — without a third-party plugin.
**Current focus:** Phase 3 — BuddyBoss Integration

## Current Position

Phase: 3 of 3 (BuddyBoss Integration)
Plan: 0 of TBD in current phase
Status: Ready to plan
Last activity: 2026-03-14 — Phase 2 complete; human approved all 8 RSVP/waitlist/calendar end-to-end scenarios

Progress: [██████░░░░] 67%

## Performance Metrics

**Velocity:**
- Total plans completed: 0
- Average duration: —
- Total execution time: —

**By Phase:**

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| - | - | - | - |

**Recent Trend:**
- Last 5 plans: —
- Trend: —

*Updated after each plan completion*
| Phase 01-foundation-event-management P00 | 2min | 2 tasks | 9 files |
| Phase 01-foundation-event-management P01 | 4min | 2 tasks | 7 files |
| Phase 01-foundation-event-management P02 | 5min | 1 tasks | 2 files |
| Phase 01-foundation-event-management P03 | 3min | 2 tasks | 3 files |
| Phase 01-foundation-event-management P04 | 4min | 1 tasks | 4 files |
| Phase 01-foundation-event-management P05 | 7min | 1 tasks | 4 files |
| Phase 01-foundation-event-management P06 | 5min | 2 tasks | 5 files |
| Phase 01-foundation-event-management P07 | 4min | 2 tasks | 5 files |
| Phase 01-foundation-event-management P08 | 2min | 1 tasks | 0 files |
| Phase 02-payments-ticketing P00 | 3 | 1 tasks | 4 files |
| Phase 02-payments-ticketing P01 | 8min | 2 tasks | 4 files |
| Phase 02-payments-ticketing P02 | 5min | 1 tasks | 3 files |
| Phase 02-payments-ticketing P03 | 5min | 2 tasks | 3 files |
| Phase 02-payments-ticketing P04 | 3 | 2 tasks | 5 files |
| Phase 02-payments-ticketing P05 | <5min | 1 tasks | 0 files |
| Phase 03-buddyboss-integration P00 | 3min | 1 tasks | 4 files |
| Phase 03-buddyboss-integration P01 | 10min | 2 tasks | 7 files |
| Phase 03-buddyboss-integration P02 | 3min | 2 tasks | 3 files |

## Accumulated Context

### Decisions

Decisions are logged in PROJECT.md Key Decisions table.
Recent decisions affecting current work:

- Roadmap: 3-phase coarse structure — Foundation+Events → Payments+Ticketing → BuddyBoss Integration
- Architecture: Recurring events data model (parent CPT + RRULE + child occurrences) must be locked in Phase 1 before any other feature touches event storage
- Architecture: Stripe destination charges with application fees is the commission mechanism — validate end-to-end early in Phase 2
- [Phase 01-foundation-event-management]: phpunit.xml.dist uses stopOnError=false so markTestIncomplete stubs do not abort the run
- [Phase 01-foundation-event-management]: Bootstrap conditionally loads bp-events-loader.php — avoids fatals before implementation plans run
- [Phase 01-foundation-event-management]: php-rrule vendored as 3 files with manual require_once — no Composer autoloader
- [Phase 01-foundation-event-management]: Duplicate guard: occurrences_generated_until meta key checked against now+2years-90days threshold
- [Phase 01-foundation-event-management]: bp_events_detach_occurrence uses raw SQL NULL update — wpdb->update with %d casts null to 0
- [Phase 01-foundation-event-management]: Event meta API implemented from scratch — WP core meta API only works with built-in object types
- [Phase 01-foundation-event-management]: _fc=1 param gates FullCalendar shape — same endpoint serves both FC feed and standard REST consumers without a separate route
- [Phase 01-foundation-event-management]: start/end params take precedence over from/to so FullCalendar native param names work at the JS layer without remapping
- [Phase 01-foundation-event-management]: LEFT JOIN replaces subquery privacy check in bp_events_get_events() — more efficient and enables unconditional EVNT-06 enforcement (removed wrong moderator bypass)
- [Phase 01-foundation-event-management]: NULL groupmeta row means site-calendar opt-IN by default — only explicit '0' value opts a group out (EVNT-05)
- [Phase 01-foundation-event-management]: bp_events_admin_get_event_counts() extracted as separate function — keeps revenue page focused on rendering, query independently cacheable with TTL 300s
- [Phase 01-foundation-event-management]: Moderation status enforcement added as bp_events_before_event_save action — defense-in-depth since bp_events_create_event already defaults to pending but direct save() calls bypass that
- [Phase 01-foundation-event-management]: Child occurrences (parent_event_id set) bypass moderation filter — they are generated by the system when a parent publishes and must not be held pending
- [Phase 01-foundation-event-management]: BP_Moderation_Events required via require_once in loader (not autoloader map) — moderation class is not a component-mapped class
- [Phase 01-foundation-event-management]: FullCalendar 6.1.20 vendor bundle is index.global.min.js (284KB minified) — loaded locally, no CDN dependency; assets_url uses buddypress()->plugin_url + src/bp-events/assets/ path
- [Phase 01-foundation-event-management]: bp_events_enqueue_calendar_assets() added as separate function — isolates FullCalendar from general events script; calendar directory page gets dedicated bpEventsSettings localize with restUrl, calendarView, nonce
- [Phase 01-foundation-event-management]: Vanilla IIFE wizard with no framework — WordPress JS Coding Standards (var/function/tabs/single-quotes); RRULE builder via string concatenation (no library)
- [Phase 01-foundation-event-management]: screens/create.php added as separate file (not retrofitted into edit.php) — clean separation between create and edit flows; auth_redirect() guards unauthenticated access
- [Phase 01-foundation-event-management]: Phase 1 declared complete after successful user verification of all 8 scenario groups covering EVNT-01 through EVNT-06 and ADMN-01 through ADMN-04
- [Phase 02-payments-ticketing]: All stubs use markTestIncomplete with consistent placeholder text; tab indentation per WordPress Coding Standards
- [Phase 02-payments-ticketing]: Broadcast waitlist model: bp_events_notify_waitlist() notifies ALL waitlisted users simultaneously — first to re-RSVP gets the spot; simpler than queue-based promotion
- [Phase 02-payments-ticketing]: PHPUnit tests written with real assertions but WP test suite not installed — php -l used as syntax verification; automated test execution deferred
- [Phase 02-payments-ticketing]: bp_events_update_capacity() is notification-only — REST handler saves capacity first then calls notify function to avoid double-save
- [Phase 02-payments-ticketing]: NULL capacity (unlimited) always triggers waitlist broadcast if waitlisted users exist
- [Phase 02-payments-ticketing]: has_param() guards update_item() rsvp_group_id block — allows explicit removal by passing 0 while ignoring absent param
- [Phase 02-payments-ticketing]: Fixed step numbers (1-7) in wizard — step 5 (Recurrence) skipped in navigation, RSVP Settings always step 6, Review always step 7
- [Phase 02-payments-ticketing]: RSVP button state PHP-rendered on load; JS re-renders after REST calls — PHP is the single source of truth per request, no DOM polling
- [Phase 02-payments-ticketing]: i18n strings embedded in bpEventsSingle.i18n sub-object — single wp_localize_script call keeps loader change self-contained
- [Phase 02-payments-ticketing]: Phase 2 declared complete after successful human verification of all 8 scenario groups covering TKET-02, TKET-04, ATTN-01, and ATTN-02
- [Phase 03-buddyboss-integration]: All Phase 3 stub methods use plan-number references in markTestIncomplete messages for traceability
- [Phase 03-buddyboss-integration]: Tab indentation and BP_ class prefix maintained per WordPress Coding Standards in all Phase 3 test stubs
- [Phase 03-buddyboss-integration]: BP_Events_Group_Extension does NOT override user_can_visit() — privacy for private/hidden groups delegated to platform BP_Group_Extension base class
- [Phase 03-buddyboss-integration]: Non-member REST 403 guard placed BEFORE bp_events_get_events() in get_items() — bp_events_get_events does not enforce group privacy when group_id is passed
- [Phase 03-buddyboss-integration]: Group calendar uses bp-events-group-calendar.js separate from bp-events-calendar.js; bpEventsGroup localize object separate from bpEventsSettings
- [Phase 03-buddyboss-integration]: bp_get_event_permalink() confirmed as correct helper name for activity items (not bp_events_get_event_permalink)
- [Phase 03-buddyboss-integration]: date_created === date_modified used to distinguish new event INSERT from UPDATE in bp_events_after_event_save hook
- [Phase 03-buddyboss-integration]: RSVP activity only posted for registered status, not waitlisted; ticket purchase activity out of scope for Phase 3

### Pending Todos

None yet.

### Blockers/Concerns

- [Phase 2] Stripe Connect destination charges vs direct charges — verify current recommended pattern against Stripe docs before Phase 2 implementation
- [Phase 3] BuddyBoss hook stability — every integration point needs function_exists() guards; verify BP_Group_Extension API against current BuddyBoss Platform developer reference before Phase 3

## Session Continuity

Last session: 2026-03-16T10:53:02.248Z
Stopped at: Completed 03-buddyboss-integration 03-02-PLAN.md
Resume file: None
