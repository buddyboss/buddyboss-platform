# Requirements: BuddyBoss Events Plugin

**Defined:** 2026-03-10
**Core Value:** Site admins on BuddyBoss can create, manage, and promote events deeply embedded in their community's groups, activity feeds, and member profiles — without a third-party plugin.

## v1 Requirements (Complete — Milestone v1.0)

### Event Creation

- [x] **EVNT-01**: Organizer can create an in-person event with venue name, address, and capacity limit
- [x] **EVNT-02**: Organizer can create a virtual event using the existing BuddyBoss Zoom integration or by adding a Google Meet link
- [x] **EVNT-03**: Organizer can create a recurring event series (daily/weekly/monthly/custom) with RRULE-based recurrence and edit-this-only / edit-this-and-future options
- [x] **EVNT-04**: Organizer can save an event as draft or schedule a future publish date
- [x] **EVNT-05**: Admin can configure per-group whether group events appear only in the group calendar or also on the main site calendar
- [x] **EVNT-06**: Events from private and hidden BuddyBoss groups are never visible on the main site calendar (enforced by group privacy rules, not optional)

### Ticketing

- [x] **TKET-02**: Organizer can create a free RSVP event with no payment required
- [x] **TKET-04**: Organizer can restrict ticket purchase to members of a specific BuddyBoss group

### Attendee Experience

- [x] **ATTN-01**: Attendee can join a waitlist when an event is sold out and receives a notification when a spot becomes available
- [x] **ATTN-02**: Attendee can export an event to their personal calendar via iCal or Google Calendar link

### BuddyBoss Integration

- [x] **BB-01**: Each BuddyBoss group has an Events tab displaying a calendar view of that group's events
- [x] **BB-02**: Event creation, RSVPs, and ticket purchases automatically post to the relevant BuddyBoss activity feeds (site-wide and group feeds, respecting group privacy)
- [x] **BB-03**: Organizer can invite group members directly from the group member roster when creating or editing an event
- [x] **BB-04**: Member profiles display a section showing events the member has attended and events they have hosted

### Admin & Moderation

- [x] **ADMN-01**: Admin can configure who is permitted to create events site-wide (admins only / group organizers / all members / tiered by plan level)
- [x] **ADMN-02**: Admin has an event moderation queue — newly submitted events require admin approval before going live (toggleable)
- [x] **ADMN-03**: Admin can view a platform-wide dashboard showing all events, ticket sales revenue, and commission earned
- [x] **ADMN-04**: Users can report an event as offensive or inappropriate, routed into the existing BuddyBoss moderation system

## v2 Requirements (Active — Milestone v2.0)

### Taxonomy

- [ ] **TAX-01**: Organizer can assign one or more hierarchical categories (with optional icon) to an event — categories are filterable on the event directory
- [ ] **TAX-02**: Organizer can assign free-form tags to an event — tags are searchable and displayed on the event page
- [ ] **TAX-03**: Public category archive pages (`/event-category/[slug]/`) list events in that category — private group events are never surfaced regardless of category assignment

### Location & Event Type

- [ ] **LOC-01**: Organizer can enter a structured venue address (name, street, city, state/region, postcode, country) — replaces the current single-string address field
- [ ] **LOC-02**: Event page displays a Google Maps embed for in-person and hybrid events using the structured address
- [ ] **LOC-03**: Organizer can create a Hybrid event that has both a physical venue and an online meeting link simultaneously
- [ ] **LOC-04**: Organizer can add online meeting details (platform label, meeting URL, meeting ID, meeting password) to virtual and hybrid events

### Content

- [ ] **CONT-01**: Event page displays a live countdown timer to the event start date/time when enabled by the organizer
- [ ] **CONT-02**: Organizer can set an external event URL — attendees are redirected to that URL instead of seeing the RSVP panel

### Sessions & Speakers

- [ ] **SESS-01**: Organizer can add a multi-session agenda to an event — each session has a title, start/end time, description, and optional speaker assignment
- [ ] **SESS-02**: Admin and organizers can create speaker profiles (name, bio, photo, social links) reusable across events — speakers are assigned to sessions

### Front-End Submission

- [ ] **FEND-01**: Logged-in members can submit an event from the front end using a creation form — submission respects the site admin's event creation permission settings
- [ ] **FEND-02**: Front-end event submissions enter a pending approval queue — admin can approve or reject with an email notification sent to the submitter
- [ ] **FEND-03**: Member profile has a "My Events" organizer dashboard showing their submitted events with status (Pending/Published/Draft) and Edit/Delete actions

### Analytics & Reports

- [ ] **REPT-01**: Organizer can view a per-event report showing view count, total RSVPs, and attendance breakdown (Going/Maybe/Can't Attend/Waitlist)
- [ ] **REPT-02**: Organizer can download a CSV of all attendees for any event they own

## Future Requirements

### Payments & Commission (Deferred)

- **PAY-01**: Organizer can connect their Stripe account to the platform via Stripe Connect OAuth onboarding
- **PAY-02**: Platform applies tiered commission rates on ticket sales based on the site admin's BuddyBoss plan tier
- **PAY-03**: Admin can configure the commission percentage for each BuddyBoss plan tier
- **PAY-04**: When an organizer issues a refund, the platform commission is automatically reversed proportionally
- **PAY-05**: Organizer can view a payout dashboard showing earnings, pending payouts, and full transaction history
- **TKET-01**: Organizer can create multiple named ticket tiers per event with individual price, quantity, and description
- **TKET-03**: Organizer can create promo/discount codes per event (percentage or fixed-amount discount)

### Attendee Tools

- **ATTN-03**: Attendee can transfer their ticket to another registered user

### Notifications

- **NOTF-01**: Organizer receives email/in-app notification when a ticket is purchased
- **NOTF-02**: Attendee receives reminder notification before event start

### Discovery

- **DISC-01**: Site-wide events search and filtering by category, date range, location
- **DISC-02**: Featured/promoted events highlighted on main calendar

## Out of Scope

| Feature | Reason |
|---------|--------|
| Custom streaming infrastructure | Use external meeting links (Zoom, Meet) — streaming adds months of infra scope |
| Mobile app | Web/WordPress plugin first |
| Offline / cash payments | Stripe Connect only — deferred to future milestone |
| Multi-currency support | Single currency per Stripe account — defer to future milestone |
| Seating chart builder | High complexity, niche use case |
| Public event marketplace | Separate product surface — not a community plugin feature |
| WooCommerce-based checkout | Stripe Connect standalone avoids WC dependency; WC conflicts common on BB sites |
| Custom registration fields | BuddyBoss Platform has native registration — no duplication needed |
| QR code check-in | Removed from scope |
| FAQ section | Deferred — low priority |
| Certificate builder | Complex Pro feature, out of scope |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

### v1 (Complete)

| Requirement | Phase | Status |
|-------------|-------|--------|
| EVNT-01 | Phase 1 | Complete |
| EVNT-02 | Phase 1 | Complete |
| EVNT-03 | Phase 1 | Complete |
| EVNT-04 | Phase 1 | Complete |
| EVNT-05 | Phase 1 | Complete |
| EVNT-06 | Phase 1 | Complete |
| TKET-02 | Phase 2 | Complete |
| TKET-04 | Phase 2 | Complete |
| ATTN-01 | Phase 2 | Complete |
| ATTN-02 | Phase 2 | Complete |
| BB-01 | Phase 3 | Complete |
| BB-02 | Phase 3 | Complete |
| BB-03 | Phase 3 | Complete |
| BB-04 | Phase 3 | Complete |
| ADMN-01 | Phase 1 | Complete |
| ADMN-02 | Phase 1 | Complete |
| ADMN-03 | Phase 1 | Complete |
| ADMN-04 | Phase 1 | Complete |

### v2 (Milestone v2.0 — phases TBD by roadmap)

| Requirement | Phase | Status |
|-------------|-------|--------|
| TAX-01 | TBD | Pending |
| TAX-02 | TBD | Pending |
| TAX-03 | TBD | Pending |
| LOC-01 | TBD | Pending |
| LOC-02 | TBD | Pending |
| LOC-03 | TBD | Pending |
| LOC-04 | TBD | Pending |
| CONT-01 | TBD | Pending |
| CONT-02 | TBD | Pending |
| SESS-01 | TBD | Pending |
| SESS-02 | TBD | Pending |
| FEND-01 | TBD | Pending |
| FEND-02 | TBD | Pending |
| FEND-03 | TBD | Pending |
| REPT-01 | TBD | Pending |
| REPT-02 | TBD | Pending |

**Coverage:**
- v2 requirements: 16 total
- Mapped to phases: 0 (TBD — filled by roadmapper)
- Unmapped: 16 (pending roadmap)

---
*Requirements defined: 2026-03-10*
*Last updated: 2026-03-17 after milestone v2.0 requirements definition*
