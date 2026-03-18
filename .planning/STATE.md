---
gsd_state_version: 1.0
milestone: v2.0
milestone_name: milestone
status: completed
stopped_at: Completed 04-meta-api-foundation-taxonomy 04-02-PLAN.md
last_updated: "2026-03-18T09:30:58.455Z"
last_activity: 2026-03-17 — v2.0 roadmap created (Phases 4-8)
progress:
  total_phases: 8
  completed_phases: 3
  total_plans: 26
  completed_plans: 24
  percent: 0
---

# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-03-17)

**Core value:** Site admins on BuddyBoss can create, manage, and promote events deeply embedded in their community's groups, activity feeds, and member profiles — without a third-party plugin.
**Current focus:** Milestone v2.0 — Feature Parity

## Current Position

Phase: Phase 4 (Meta API Foundation + Taxonomy) — ready to plan
Plan: —
Status: Roadmap complete — ready for /gsd:plan-phase 4
Last activity: 2026-03-17 — v2.0 roadmap created (Phases 4-8)

Progress: [░░░░░░░░░░] 0% (v2.0 phases)

## Milestone v2.0 Phase Map

| Phase | Name | Requirements | Depends on |
|-------|------|--------------|------------|
| 4 | Meta API Foundation + Taxonomy | TAX-01, TAX-02, TAX-03 | Phase 3 (complete) |
| 5 | Data Enrichment | LOC-01, LOC-02, LOC-03, LOC-04, CONT-01, CONT-02 | Phase 4 |
| 6 | Sessions + Speakers | SESS-01, SESS-02 | Phase 4 |
| 7 | Front-End Submission + Organizer Dashboard | FEND-01, FEND-02, FEND-03 | Phase 5, Phase 6 |
| 8 | Analytics + Reports | REPT-01, REPT-02 | Phase 7 |

Note: Phase 5 and Phase 6 both depend only on Phase 4. They can execute in parallel. Phase 7 requires both Phase 5 and Phase 6 to be complete before the front-end wizard can include all enrichment fields and session/speaker steps in one pass.

## Performance Metrics

**Velocity (v1.0 historical):**
- Total plans completed: 21
- Average duration: ~4 min/plan
- Total execution time: ~84 min

**By Phase (v1.0 complete):**

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| 01-foundation-event-management | 9 | ~36min | ~4min |
| 02-payments-ticketing | 6 | ~27min | ~4.5min |
| 03-buddyboss-integration | 6 | ~26min | ~4.3min |

**v2.0 Velocity:**
- Total plans completed: 0
- Average duration: —
- Total execution time: —

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
| Phase 03-buddyboss-integration P03 | 3min | 2 tasks | 5 files |
| Phase 03-buddyboss-integration P04 | 2min | 2 tasks | 6 files |
| Phase 03-buddyboss-integration P05 | 5min | 2 tasks | 0 files |
| Phase 04-meta-api-foundation-taxonomy P00 | 2 | 1 tasks | 3 files |
| Phase 04-meta-api-foundation-taxonomy P01 | 2min | 2 tasks | 3 files |
| Phase 04-meta-api-foundation-taxonomy P02 | 5min | 2 tasks | 4 files |

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
- [Phase 03-buddyboss-integration]: Server-side PHP conditional on group_id gates invite panel — no JS show/hide; REST route reuses update_item_permissions_check; wpdb->replace() prevents duplicate invite rows
- [Phase 03-buddyboss-integration]: bp_template_content hook must be added inside screen function BEFORE bp_core_load_template() — without it the member profile plugin area renders blank
- [Phase 03-buddyboss-integration]: late_includes() attending/hosting branches gate on bp_is_user() to distinguish profile sub-tabs from directory/single event routes
- [Phase 03-buddyboss-integration]: Phase 3 declared complete — all four BuddyBoss integration surfaces (BB-01 through BB-04) verified by user in live MAMP environment
- [v2.0 roadmap]: 5 phases (4-8) derived from 16 requirements — standard granularity, natural delivery boundaries
- [v2.0 roadmap]: Phase 4 is the prerequisite gate — bb_eventmeta PHP API is called by Phases 5, 6, 7, and 8; taxonomy privacy filter applied from day one
- [v2.0 roadmap]: Phase 5 and Phase 6 have no inter-dependency — can execute in parallel; Phase 7 depends on both being complete to include all fields in wizard in a single pass
- [v2.0 roadmap]: Phase 6 depends only on Phase 4 (meta API), not Phase 5 — sessions/speakers need meta API but not enrichment fields
- [v2.0 roadmap]: Architecture constraint — bb_eventmeta table exists but has NO PHP API; implementing bp_event_get/update_meta() is Phase 4's first deliverable and a prerequisite for every downstream feature
- [v2.0 roadmap]: Architecture constraint — venue_address column migration via dbDelta() must happen in Phase 5 before structured address fields can be written
- [v2.0 roadmap]: Security constraint — taxonomy archive pre_get_posts privacy filter applied in Phase 4 from the moment taxonomies are registered; cannot be retrofitted
- [v2.0 roadmap]: Research flag for Phase 7 — verify BuddyBoss notification system hooks before writing approval/rejection email code; determine whether BuddyBoss Platform provides notification infrastructure that should be used instead of raw wp_mail()
- [Phase 04-meta-api-foundation-taxonomy]: All Phase 4 stubs use plan-number references in markTestIncomplete messages — TAX-03 privacy stubs point to 04-01-PLAN since privacy filter is registered at taxonomy bootstrap time
- [Phase 04-meta-api-foundation-taxonomy]: Tab indentation and BP_Events_Test_ class prefix maintained per WordPress Coding Standards in all Phase 4 test stubs
- [Phase 04-meta-api-foundation-taxonomy]: bp_filter_metaid_column_name wrapped around all metadata calls — bb_eventmeta uses 'id' as PK not 'meta_id'
- [Phase 04-meta-api-foundation-taxonomy]: meta_tables registration mirrors bp-groups pattern — passes 'event' key to parent::setup_globals() which calls register_meta_tables() to set $wpdb->eventmeta
- [Phase 04-meta-api-foundation-taxonomy]: Privacy filter registered on same init hook as taxonomy registration — no window where taxonomy is live but TAX-03 filter is absent
- [Phase 04-meta-api-foundation-taxonomy]: Taxonomy WHERE clauses use subqueries (e.id IN SELECT) not JOIN — avoids duplicate rows when event matches multiple terms
- [Phase 04-meta-api-foundation-taxonomy]: Category icon stored as _bb_event_cat_icon_id term meta (underscore prefix = internal meta, hidden in default WP term meta UI)

### Pending Todos

None yet.

### Blockers/Concerns

- [Phase 2] Stripe Connect destination charges vs direct charges — verify current recommended pattern against Stripe docs before Phase 2 implementation
- [Phase 3] BuddyBoss hook stability — every integration point needs function_exists() guards; verify BP_Group_Extension API against current BuddyBoss Platform developer reference before Phase 3
- [Phase 4] Verify wp_set_object_terms() accepts arbitrary integer object_id on the target WP version before writing taxonomy assignment code (BuddyBoss group types use this pattern — strong precedent, but confirm directly)
- [Phase 4] Verify bp_get_meta() function signature against current BuddyBoss Platform version before implementing the bb_eventmeta API
- [Phase 7] BuddyBoss notification system hooks — determine whether BuddyBoss Platform provides notification infrastructure (push + email + in-app) before writing approval/rejection email code; research flag from SUMMARY.md

## Session Continuity

Last session: 2026-03-18T09:30:58.452Z
Stopped at: Completed 04-meta-api-foundation-taxonomy 04-02-PLAN.md
Resume file: None
