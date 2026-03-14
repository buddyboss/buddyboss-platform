# Roadmap: BuddyBoss Events Plugin

## Overview

Three phases that build from foundation outward: first a working plugin with full event management and admin control, then a complete payment and ticketing system, then the BuddyBoss community integrations that make this product genuinely different from any general-purpose events plugin. Each phase produces something independently verifiable. The BuddyBoss integration phase is intentionally last — it hooks into events and orders that must already exist and work.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [x] **Phase 1: Foundation + Event Management** - Installable plugin scaffold with full event CRUD (in-person, virtual, recurring), admin control panel, and site-wide calendar view (completed 2026-03-14)
- [ ] **Phase 2: Payments + Ticketing** - Free RSVP system with capacity-aware waitlisting, group-restricted RSVP, waitlist broadcast notifications, and calendar export (paid ticketing deferred)
- [ ] **Phase 3: BuddyBoss Integration** - Group events tab, activity feed posting, member profile sections, and group member invite flow — the product's core differentiator

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
**Plans**: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Foundation + Event Management | 9/9 | Complete   | 2026-03-14 |
| 2. Payments + Ticketing | 0/6 | Not started | - |
| 3. BuddyBoss Integration | 0/TBD | Not started | - |
