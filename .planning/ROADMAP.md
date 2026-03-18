# Roadmap: BuddyBoss Events Plugin

## Overview

Three phases that build from foundation outward: first a working plugin with full event management and admin control, then a complete payment and ticketing system, then the BuddyBoss community integrations that make this product genuinely different from any general-purpose events plugin. Each phase produces something independently verifiable. The BuddyBoss integration phase is intentionally last — it hooks into events and orders that must already exist and work.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [x] **Phase 1: Foundation + Event Management** - Installable plugin scaffold with full event CRUD (in-person, virtual, recurring), admin control panel, and site-wide calendar view (completed 2026-03-14)
- [x] **Phase 2: Payments + Ticketing** - Free RSVP system with capacity-aware waitlisting, group-restricted RSVP, waitlist broadcast notifications, and calendar export (paid ticketing deferred) (completed 2026-03-14)
- [x] **Phase 3: BuddyBoss Integration** - Group events tab, activity feed posting, member profile sections, and group member invite flow — the product's core differentiator (completed 2026-03-16)
- [ ] **Phase 4: Meta API Foundation + Taxonomy** - bb_eventmeta PHP API and hierarchical category/flat tag taxonomies with privacy-enforced public archives — prerequisite for all v2.0 features
- [ ] **Phase 5: Data Enrichment** - Structured venue address fields, Google Maps embed, hybrid event type, online meeting details, countdown timer, and external event link — all resolved to bb_eventmeta keys
- [ ] **Phase 6: Sessions + Speakers** - Multi-session agenda with speaker assignments backed by new custom tables (bb_event_sessions, bb_event_speakers, bb_event_session_speakers)
- [ ] **Phase 7: Front-End Submission + Organizer Dashboard** - Member-facing event creation form, approval workflow with notifications, and organizer "Manage Events" profile dashboard
- [ ] **Phase 8: Analytics + Reports** - Per-event view tracking, RSVP/attendance breakdown dashboard, and attendee CSV export

## Phase Details

### Phase 1: Foundation + Event Management
**Goal**: A fully installable WordPress plugin where admins can configure event permissions and commission settings, and organizers can create, edit, and publish in-person, virtual, and recurring events visible on a site-wide calendar
**Depends on**: Nothing (first phase)
**Requirements**: EVNT-01, EVNT-02, EVNT-03, EVNT-04, EVNT-05, EVNT-06, ADMN-01, ADMN-02, ADMN-03, ADMN-04
**Success Criteria** (what must be TRUE):
  1. Admin can activate the plugin on a BuddyBoss site and immediately see the settings panel for commission rates, creation permissions, and event moderation toggle — plugin fails activation gracefully if BuddyBoss Platform is not active
  2. Organizer can create an in-person event (with venue and capacity), a virtual event (with Zoom integration or Meet link), and a recurring event series (daily/weekly/monthly) — all appear on the site-wide calendar after publish
  3. Organizer can edit only one occurrence of a recurring series, or all future occurrences, without corrupting other events in the series
  4. Admin can restrict event creation to admins only, group organizers, all members, or tiered by plan — and those restrictions are enforced at the creation UI
  5. Events from private and hidden BuddyBoss groups never appear on the main site calendar regardless of admin settings
**Plans**: 9 plans

Plans:
- [ ] 01-PLAN-00.md — PHPUnit test infrastructure and 6 test stub files
- [ ] 01-PLAN-01.md — Vendor php-rrule, occurrence pre-generation, cron extension, series split
- [ ] 01-PLAN-02.md — REST endpoint get_items() with FullCalendar feed shape
- [ ] 01-PLAN-03.md — Privacy enforcement (EVNT-05/06) + admin event stats (ADMN-03)
- [ ] 01-PLAN-04.md — Admin approve handler + creation permission verification (ADMN-01/02)
- [ ] 01-PLAN-05.md — BP_Moderation_Events class (ADMN-04)
- [ ] 01-PLAN-06.md — FullCalendar calendar UI, month/list view, REST feed wiring
- [ ] 01-PLAN-07.md — Multi-step event creation wizard JS
- [ ] 01-PLAN-08.md — End-to-end human verification checkpoint

### Phase 2: Payments + Ticketing
**Goal**: Organizers can create free RSVP events with capacity limits, attendees can join a waitlist on sold-out events and receive broadcast notifications when a spot opens, organizers can restrict RSVP to members of a specific BuddyBoss group, and attendees can export events to iCal or Google Calendar
**Depends on**: Phase 1
**Requirements**: TKET-02, TKET-04, ATTN-01, ATTN-02
**Success Criteria** (what must be TRUE):
  1. Attendee can RSVP to a free event with one click — button changes to "Attending" in-page without redirect
  2. When event hits capacity, RSVP button automatically changes to "Join Waitlist" — all waitlisted users are notified simultaneously when a spot opens
  3. Organizer can restrict RSVP to members of a specific BuddyBoss group — non-members see a disabled button with an explanatory message
  4. Attendee can download an iCal file or open Google Calendar with the event pre-filled from the event page
**Plans**: 6 plans

Plans:
- [ ] 02-00-PLAN.md — PHPUnit test stubs for RSVP, restrictions, waitlist, calendar export
- [ ] 02-01-PLAN.md — RSVP PHP functions, REST sub-routes, waitlist notification functions
- [ ] 02-02-PLAN.md — Capacity-increase waitlist trigger (third ATTN-01 spot-open mechanism)
- [ ] 02-03-PLAN.md — Group restriction meta storage + RSVP Settings step in creation wizard
- [ ] 02-04-PLAN.md — Single event template RSVP panel, attendee list, JS, calendar export wiring
- [ ] 02-05-PLAN.md — End-to-end human verification checkpoint

### Phase 3: BuddyBoss Integration
**Goal**: Events are fully woven into the BuddyBoss community fabric — each group has its own events tab with calendar, activity feeds reflect event actions, member profiles show event history, and organizers can invite group members directly from the group roster
**Depends on**: Phase 2
**Requirements**: BB-01, BB-02, BB-03, BB-04
**Success Criteria** (what must be TRUE):
  1. Every BuddyBoss group has an Events tab showing a calendar view scoped to that group's events — events from private groups are not visible to non-members
  2. When an organizer creates an event, when a member RSVPs, and when a ticket is purchased, an activity item appears in the relevant BuddyBoss activity feed (site-wide feed for public events, group feed for group events) — private group events never surface in the site-wide feed
  3. Organizer can browse the group member roster while creating or editing an event and send invites directly to selected members
  4. Any member's profile displays a section listing events they have hosted and events they have attended
**Plans**: 6 plans

Plans:
- [ ] 03-00-PLAN.md — PHPUnit test stubs for BB-01 through BB-04
- [ ] 03-01-PLAN.md — Group Events tab (BP_Group_Extension, FullCalendar, REST group_id privacy guard)
- [ ] 03-02-PLAN.md — Activity feed integration (event create + RSVP hooks, hide_sitewide for private groups)
- [ ] 03-03-PLAN.md — Group member invite panel on event edit screen + REST invite sub-route
- [ ] 03-04-PLAN.md — Member profile attending/hosting tabs (screen functions + templates)
- [ ] 03-05-PLAN.md — End-to-end human verification checkpoint

### Phase 4: Meta API Foundation + Taxonomy
**Goal**: The bb_eventmeta PHP API is in place and events can be organized into hierarchical categories and flat tags — with public category archive pages that never expose private group events
**Depends on**: Phase 3
**Requirements**: TAX-01, TAX-02, TAX-03
**Success Criteria** (what must be TRUE):
  1. Organizer can assign one or more categories (with optional icon) to an event from the creation wizard — category filter on the event directory returns only matching events
  2. Organizer can assign free-form tags to an event — tags are searchable and visible on the event detail page
  3. Public category archive page (/event-category/[slug]/) lists events in that category — a logged-out visitor sees zero results for events belonging to private or hidden BuddyBoss groups regardless of category assignment
  4. Admin can manage event categories and tags from the WordPress admin — creating, editing, and deleting categories with icon/image support
**Plans**: 5 plans

Plans:
- [ ] 04-00-PLAN.md — PHPUnit test stubs for META-API, TAX-01, TAX-02, TAX-03
- [ ] 04-01-PLAN.md — Meta API (setup_globals meta_tables + wrapper functions) + taxonomy registration + privacy filter
- [ ] 04-02-PLAN.md — Taxonomy filtering in bp_events_get_events() + REST params + admin category icon UI
- [ ] 04-03-PLAN.md — Creation wizard category/tag step + directory filter + taxonomy archive template
- [ ] 04-04-PLAN.md — End-to-end human verification checkpoint

### Phase 5: Data Enrichment
**Goal**: Event pages carry structured location data with an embedded map, support hybrid and virtual meeting details, and organizers can add a countdown timer and external redirect link — all stored as bb_eventmeta keys with zero schema migration on the main bb_events table
**Depends on**: Phase 4
**Requirements**: LOC-01, LOC-02, LOC-03, LOC-04, CONT-01, CONT-02
**Success Criteria** (what must be TRUE):
  1. Organizer can enter a structured venue address (street, city, state/region, postcode, country) in the creation wizard — the existing single-string address field is migrated without data loss
  2. In-person and hybrid event pages display a Google Maps embed using the structured address — the map does not load until the visitor consents (GDPR two-click pattern); a fallback OpenStreetMap option is available in settings
  3. Organizer can set event type to Hybrid — the event page displays both the physical venue section and the online meeting details section simultaneously
  4. Organizer can add online meeting details (platform label, meeting URL, meeting ID, meeting password) to virtual and hybrid events — meeting password is visible only to confirmed attendees
  5. Event page shows a live countdown timer to the event start date/time when the organizer has enabled it — timer disappears after the event starts
  6. When an organizer sets an external event URL, the event page replaces the RSVP panel with a "Register Externally" button that redirects to that URL
**Plans**: TBD

### Phase 6: Sessions + Speakers
**Goal**: Events support a multi-session agenda with speaker assignments, backed by purpose-built custom tables — organizers can build a full conference-style schedule with named speakers assigned to each session slot
**Depends on**: Phase 4
**Requirements**: SESS-01, SESS-02
**Success Criteria** (what must be TRUE):
  1. Organizer can add multiple sessions to an event — each session has a title, start time, end time, description, and the event detail page shows a chronological agenda/schedule tab
  2. Admin or organizer can create a speaker profile (name, bio, photo, social links) and assign that speaker to one or more sessions — the speaker's name and photo appear on the session in the agenda
  3. Speaker profiles are reusable across events — creating a speaker once makes them available for assignment to any future session
**Plans**: TBD

### Phase 7: Front-End Submission + Organizer Dashboard
**Goal**: Logged-in members can create and manage events entirely from the front end — submissions enter an admin approval queue, organizers track status from their profile dashboard, and the entire workflow respects the site admin's event creation permission settings
**Depends on**: Phase 5, Phase 6
**Requirements**: FEND-01, FEND-02, FEND-03
**Success Criteria** (what must be TRUE):
  1. A logged-in member with the appropriate site permission can reach a front-end event creation form that includes all event fields (including enrichment fields from Phase 5 and session/speaker assignment from Phase 6) — a member without the required permission sees an explanatory message rather than a form
  2. A submitted event from the front end appears in the admin's pending events queue — admin can approve or reject it, and the submitter receives an email notification of the decision
  3. An organizer's member profile displays a "Manage Events" tab listing all their submitted events with status badges (Pending / Published / Draft) and Edit and Delete actions for each
  4. Approving a pending event publishes it and makes it visible on the site calendar — rejecting it keeps it hidden and sends the organizer a notification with the admin's optional rejection reason
**Plans**: TBD

### Phase 8: Analytics + Reports
**Goal**: Organizers can see how their events are performing — view counts, RSVP breakdown, and a downloadable attendee CSV — giving them the data they need to improve future events
**Depends on**: Phase 7
**Requirements**: REPT-01, REPT-02
**Success Criteria** (what must be TRUE):
  1. Organizer can view a per-event report showing total page views (deduplicated per user per day), total RSVPs, and a breakdown by status (Going / Maybe / Can't Attend / Waitlist)
  2. Organizer can download a CSV of all attendees for any event they own — the CSV includes name, email, RSVP status, and registration date; download is denied to any user who does not own the event
**Plans**: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8

Note: Phase 6 depends only on Phase 4 (not Phase 5) — Sessions/Speakers need the meta API but not the enrichment fields. Phases 5 and 6 can execute in parallel if desired, but Phase 7 depends on both being complete.

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Foundation + Event Management | 9/9 | Complete   | 2026-03-14 |
| 2. Payments + Ticketing | 6/6 | Complete   | 2026-03-14 |
| 3. BuddyBoss Integration | 6/6 | Complete   | 2026-03-16 |
| 4. Meta API Foundation + Taxonomy | 1/5 | In Progress|  |
| 5. Data Enrichment | 0/TBD | Not started | - |
| 6. Sessions + Speakers | 0/TBD | Not started | - |
| 7. Front-End Submission + Organizer Dashboard | 0/TBD | Not started | - |
| 8. Analytics + Reports | 0/TBD | Not started | - |
