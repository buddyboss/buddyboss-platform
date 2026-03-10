# Requirements: BuddyBoss Events Plugin

**Defined:** 2026-03-10
**Core Value:** Site admins on BuddyBoss can create, manage, and monetize events deeply embedded in their community's groups, activity feeds, and member profiles — without a third-party plugin.

## v1 Requirements

### Event Creation

- [ ] **EVNT-01**: Organizer can create an in-person event with venue name, address, and capacity limit
- [ ] **EVNT-02**: Organizer can create a virtual event using the existing BuddyBoss Zoom integration or by adding a Google Meet link
- [ ] **EVNT-03**: Organizer can create a recurring event series (daily/weekly/monthly/custom) with RRULE-based recurrence and edit-this-only / edit-this-and-future options
- [ ] **EVNT-04**: Organizer can save an event as draft or schedule a future publish date
- [ ] **EVNT-05**: Admin can configure per-group whether group events appear only in the group calendar or also on the main site calendar
- [ ] **EVNT-06**: Events from private and hidden BuddyBoss groups are never visible on the main site calendar (enforced by group privacy rules, not optional)

### Ticketing

- [ ] **TKET-01**: Organizer can create multiple named ticket tiers per event with individual price, quantity, and description
- [ ] **TKET-02**: Organizer can create a free RSVP event with no payment required
- [ ] **TKET-03**: Organizer can create promo/discount codes per event (percentage or fixed-amount discount)
- [ ] **TKET-04**: Organizer can restrict ticket purchase to members of a specific BuddyBoss group

### Payments & Commission

- [ ] **PAY-01**: Organizer can connect their Stripe account to the platform via Stripe Connect OAuth onboarding
- [ ] **PAY-02**: Platform applies tiered commission rates on ticket sales based on the site admin's BuddyBoss plan tier (free / pro / plus / ultimate — rates configurable by platform admin)
- [ ] **PAY-03**: Admin can configure the commission percentage for each BuddyBoss plan tier from the admin panel
- [ ] **PAY-04**: When an organizer issues a refund, the platform commission is automatically reversed proportionally via Stripe application fee refund
- [ ] **PAY-05**: Organizer can view a payout dashboard showing earnings, pending payouts, and full transaction history

### Attendee Experience

- [ ] **ATTN-01**: Attendee can join a waitlist when an event is sold out and receives a notification when a spot becomes available
- [ ] **ATTN-02**: Attendee can export an event to their personal calendar via iCal or Google Calendar link

### BuddyBoss Integration

- [ ] **BB-01**: Each BuddyBoss group has an Events tab displaying a calendar view of that group's events
- [ ] **BB-02**: Event creation, RSVPs, and ticket purchases automatically post to the relevant BuddyBoss activity feeds (site-wide and group feeds, respecting group privacy)
- [ ] **BB-03**: Organizer can invite group members directly from the group member roster when creating or editing an event
- [ ] **BB-04**: Member profiles display a section showing events the member has attended and events they have hosted

### Admin & Moderation

- [ ] **ADMN-01**: Admin can configure who is permitted to create events site-wide (admins only / group organizers / all members / tiered by plan level)
- [ ] **ADMN-02**: Admin has an event moderation queue — newly submitted events require admin approval before going live (toggleable)
- [ ] **ADMN-03**: Admin can view a platform-wide dashboard showing all events, ticket sales revenue, and commission earned
- [ ] **ADMN-04**: Users can report an event as offensive or inappropriate, routed into the existing BuddyBoss moderation system

## v2 Requirements

### Attendee Tools

- **ATTN-03**: Attendee can transfer their ticket to another registered user
- **ATTN-04**: QR code ticket generation with organizer check-in scanner

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
| Offline / cash payments | Stripe Connect only for v1 |
| Multi-currency support | Single currency per Stripe account — defer to v2 |
| Seating chart builder | High complexity, niche use case for v1 |
| Public event marketplace | Separate product surface — not a community plugin feature |
| WooCommerce-based checkout | Stripe Connect standalone avoids WC dependency; WC conflicts common on BB sites |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| EVNT-01 | Phase 1 | Pending |
| EVNT-02 | Phase 1 | Pending |
| EVNT-03 | Phase 1 | Pending |
| EVNT-04 | Phase 1 | Pending |
| EVNT-05 | Phase 1 | Pending |
| EVNT-06 | Phase 1 | Pending |
| TKET-01 | Phase 2 | Pending |
| TKET-02 | Phase 2 | Pending |
| TKET-03 | Phase 2 | Pending |
| TKET-04 | Phase 2 | Pending |
| PAY-01 | Phase 2 | Pending |
| PAY-02 | Phase 2 | Pending |
| PAY-03 | Phase 2 | Pending |
| PAY-04 | Phase 2 | Pending |
| PAY-05 | Phase 2 | Pending |
| ATTN-01 | Phase 2 | Pending |
| ATTN-02 | Phase 2 | Pending |
| BB-01 | Phase 3 | Pending |
| BB-02 | Phase 3 | Pending |
| BB-03 | Phase 3 | Pending |
| BB-04 | Phase 3 | Pending |
| ADMN-01 | Phase 1 | Pending |
| ADMN-02 | Phase 1 | Pending |
| ADMN-03 | Phase 1 | Pending |
| ADMN-04 | Phase 1 | Pending |

**Coverage:**
- v1 requirements: 25 total
- Mapped to phases: 25
- Unmapped: 0 ✓

---
*Requirements defined: 2026-03-10*
*Last updated: 2026-03-10 after roadmap creation*
