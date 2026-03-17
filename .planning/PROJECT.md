# BuddyBoss Events Plugin

## What This Is

A commercial WordPress plugin that adds a full-featured events management system to BuddyBoss-powered community sites. Site admins can run in-person, virtual, hybrid, and recurring events — with categories, structured locations, speaker/session management, front-end submission, and deep BuddyBoss community integration.

## Core Value

Site admins on BuddyBoss can create, manage, and promote events — deeply embedded in their community's groups, activity feeds, and member profiles — without needing a third-party events plugin.

## Current Milestone: v2.0 Feature Parity

**Goal:** Bring the plugin to Eventin-level feature parity (minus payments) — covering taxonomy, enriched location/event types, sessions & speakers, front-end submission, custom registration fields, and organizer analytics.

**Target features:**
- Event categories (hierarchical with icon/image) + tags (flat taxonomy)
- Structured location fields (city/state/zip/country) with Google Maps embed
- Hybrid event type + online meeting ID/password/platform label
- Sessions/Agenda (multi-session with speaker assignments)
- Speakers CPT with event assignment
- Front-end event submission + organizer dashboard with approval workflow
- Custom registration fields per event (text/dropdown/checkbox)
- Event reports/analytics — views, attendance count, CSV export
- FAQ section, countdown timer, external event link

## Requirements

### Validated

- ✓ Event creation: in-person, virtual, and recurring — Phase 1
- ✓ Admin control panel: creation permissions, moderation toggle — Phase 1
- ✓ Site-wide calendar view — Phase 1
- ✓ Free RSVP with capacity and waitlist — Phase 2
- ✓ Group RSVP restrictions — Phase 2
- ✓ iCal/Google Calendar export — Phase 2
- ✓ BuddyBoss group events tab — Phase 3
- ✓ Activity feed integration — Phase 3
- ✓ Group member invites — Phase 3
- ✓ Member profile attending/hosting tabs — Phase 3

### Active

- [ ] Event categories (hierarchical taxonomy with icon/image)
- [ ] Event tags (flat taxonomy)
- [ ] Structured venue address fields + Google Maps embed
- [ ] Hybrid event type (physical + virtual simultaneously)
- [ ] Online meeting details (ID, password, platform label)
- [ ] Sessions/Agenda with speaker assignments
- [ ] Speakers CPT
- [ ] Front-end event submission + organizer dashboard + approval workflow
- [ ] Custom registration fields per event
- [ ] Event reports/analytics + CSV export
- [ ] FAQ, countdown timer, external event link

### Out of Scope

- Mobile app — web/WordPress plugin first
- Custom streaming infrastructure — use external links (Zoom, Meet) for virtual events
- Stripe Connect / paid ticketing — deferred to future milestone
- QR code check-in — removed from scope
- Seating chart builder — high complexity, niche use case
- Certificate builder — complex Pro feature
- Multi-currency — defer to future milestone

## Context

- Built as a WordPress plugin distributed through BuddyBoss's ecosystem
- Requires BuddyBoss (the platform plugin) to be installed — this is a BuddyBoss add-on
- v1.0 shipped all 3 phases: Foundation, RSVP/Ticketing, BuddyBoss Integration
- v2.0 targets Eventin feature parity (minus payment features) using Eventin docs as reference
- Eventin reference docs: https://support.themewinter.com/docs/plugins/plugin-docs/event/

## Constraints

- **Tech Stack**: WordPress plugin architecture (PHP, WP hooks/filters, REST API)
- **Dependency**: BuddyBoss Platform plugin must be active — hooks into BP groups, activity, profiles
- **Distribution**: Commercial plugin sold through BuddyBoss — must meet WP plugin quality standards
- **No payments**: Stripe Connect deferred — v2.0 is feature parity without monetization

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| BuddyBoss add-on (not standalone) | Deep integration is the differentiator vs The Events Calendar et al | ✓ Good |
| Full BuddyBoss integration in v1 | Integration IS the product — partial integration weakens the value proposition | ✓ Good |
| Eventin as v2.0 feature reference | Proven feature set for events plugins — use as parity benchmark minus payments | — Pending |
| QR check-in removed from scope | Deferred by user — not part of v2.0 | — Pending |

---
*Last updated: 2026-03-17 after v2.0 milestone start*
