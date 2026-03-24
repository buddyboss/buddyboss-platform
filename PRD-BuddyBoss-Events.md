# Product Requirements Document
# BuddyBoss Events Plugin

**Version:** 1.0
**Status:** Draft
**Date:** 2026-03-10
**Owner:** BuddyBoss Product Team

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Problem Statement](#problem-statement)
3. [Goals & Success Metrics](#goals--success-metrics)
4. [User Personas](#user-personas)
5. [Product Overview](#product-overview)
6. [Feature Requirements](#feature-requirements)
7. [User Flows](#user-flows)
8. [Revenue Model](#revenue-model)
9. [Technical Constraints](#technical-constraints)
10. [Out of Scope](#out-of-scope)
11. [Release Criteria](#release-criteria)
12. [Open Questions](#open-questions)

---

## Executive Summary

BuddyBoss Events is a commercial WordPress plugin that brings native event management and paid ticketing to BuddyBoss-powered community sites. Unlike general-purpose WordPress events plugins (The Events Calendar, Modern Events Calendar, Eventin), BuddyBoss Events is built around the community — events live inside groups, post to activity feeds, appear on member profiles, and respect BuddyBoss's privacy model out of the box.

The plugin generates revenue for BuddyBoss through a platform commission on all ticket sales, with the commission rate determined by the site admin's BuddyBoss plan tier. This aligns BuddyBoss's revenue growth with the success of community organizers on the platform.

**v1 scope:** Full event management, multi-tier ticketing, Stripe Connect payments with tiered commission, and complete BuddyBoss integration (groups, feeds, profiles, invites).

---

## Problem Statement

### The gap in the market

BuddyBoss site admins who want to run events today must install a third-party plugin (typically The Events Calendar or a WooCommerce-based solution). These plugins:

- Have no awareness of BuddyBoss groups, so events exist in a silo separate from community activity
- Do not post to activity feeds, so events don't benefit from the organic reach of the social layer
- Cannot scope events to a specific group or respect group privacy settings
- Require multiple paid add-ons to reach feature parity with what admins expect
- Generate zero revenue for BuddyBoss despite running on the BuddyBoss platform

### The opportunity

No WordPress events plugin has been built with BuddyBoss as a first-class dependency. The BuddyBoss user base represents thousands of active communities — alumni networks, professional groups, creator communities, faith organisations — all of which run events. By owning events natively, BuddyBoss:

1. Removes a key reason admins need to install competing products
2. Creates a new recurring revenue stream (commission on ticket sales)
3. Deepens the platform lock-in through tighter community integration
4. Gives admins a compelling feature to justify upgrading their BuddyBoss plan

---

## Goals & Success Metrics

### Primary goals

| Goal | Metric | Target (6 months post-launch) |
|------|--------|-------------------------------|
| Adoption | % of active BuddyBoss sites with plugin installed | 20% |
| Revenue | Monthly commission revenue from ticket sales | TBD (pending commission rate decision) |
| Retention | Sites still using plugin 90 days after install | 70% |
| Satisfaction | Support ticket rate per active install | < 2% per month |

### Secondary goals

- Reduce third-party events plugin installs on BuddyBoss sites
- Increase plan upgrade rate by adding Events as a plan-tier feature gate
- Establish BuddyBoss as the events platform for community builders

### Anti-goals

- This is not a public-facing events marketplace (Eventbrite model)
- This is not a general-purpose WordPress events plugin targeting non-BuddyBoss sites

---

## User Personas

### Persona 1: The Community Admin (primary buyer)

**Who:** WordPress site owner running a BuddyBoss-powered community. Could be a professional association, alumni network, creator community, faith group, or interest-based club.

**Needs:**
- Run events for their community without duct-taping multiple plugins together
- Sell tickets and receive payment directly, without a middleman platform
- Control who can create events and how they appear across the site
- Keep events connected to the group/community context they belong to

**Pain points today:**
- Third-party events plugins don't know about BuddyBoss groups
- Complex setup: multiple plugins, multiple add-ons, separate Stripe configuration
- Events feel disconnected from the community social layer

**Success looks like:** Admin installs the plugin, connects Stripe, and has a working events system with group integration in under an hour.

---

### Persona 2: The Group Organizer (power user)

**Who:** A member of the BuddyBoss community who manages a specific group (e.g., a local chapter lead, a workshop host, a team coordinator). May or may not be a WordPress admin.

**Needs:**
- Create and manage events within their group without needing WordPress admin access
- Sell tickets with multiple pricing tiers
- Invite group members directly from the event creation screen
- See who's coming, manage the attendee list, issue refunds

**Success looks like:** Organizer creates an event, sets up early bird and general tickets, invites their group members, and gets paid — without ever leaving the community.

---

### Persona 3: The Event Attendee

**Who:** Any member of the community browsing events, buying tickets, RSVPing.

**Needs:**
- Discover events relevant to their groups
- Purchase tickets without friction
- Get confirmation and a way to add the event to their calendar
- Know they'll be notified if a sold-out event gets a cancellation

**Success looks like:** Attendee finds an event in their group's calendar, buys a ticket in two taps, receives email confirmation with iCal link, joins the waitlist for a second sold-out event.

---

### Persona 4: The BuddyBoss Platform Admin (site super admin)

**Who:** The technical WordPress admin responsible for the BuddyBoss install. May be different from the Community Admin.

**Needs:**
- Configure platform-wide settings once and let it run
- Ensure the commission/Stripe configuration is correct
- Have visibility into all events and revenue across the site
- Moderate problematic events

**Success looks like:** Admin configures commission rates for each plan tier, sets event creation permissions, and has a dashboard showing total platform revenue with zero ongoing maintenance.

---

## Product Overview

### How it works — the core loop

```
Community Admin installs plugin
         ↓
Admin connects BuddyBoss Stripe platform account
Admin configures commission rates per plan tier
Admin sets who can create events (global setting)
         ↓
Group Organizer creates an event in their group
Sets ticket tiers → Connects their own Stripe account
Invites group members → Event posts to group activity feed
         ↓
Attendees discover event (group calendar, site calendar, activity feed)
Attendees purchase tickets → Funds go to organizer Stripe
BuddyBoss application fee deducted automatically at organizer's plan rate
         ↓
Event runs → Attendees check in (future v2)
Organizer views dashboard → Earnings, payouts, transactions
```

### Calendar hierarchy

```
Site-wide calendar
├── All public events across all groups
└── Public group events (if admin has enabled main calendar posting for that group)

Group calendar (every BuddyBoss group)
├── Public groups → events visible to all site members
├── Private groups → events visible to group members only
└── Hidden groups → events visible to group members only; never on main calendar
```

---

## Feature Requirements

### 1. Event Management

#### 1.1 Event Types

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| EVNT-01 | Organizer can create an **in-person event** with venue name, full address, and a capacity limit | Must Have |
| EVNT-02 | Organizer can create a **virtual event** using the existing BuddyBoss Zoom integration or a Google Meet link | Must Have |
| EVNT-03 | Organizer can create a **recurring event series** (daily / weekly / monthly / custom) with RRULE-based recurrence — individual occurrences can be edited without affecting the rest of the series; all future occurrences can be edited at once | Must Have |
| EVNT-04 | Organizer can save an event as **draft** or **schedule** a future publish date | Must Have |

#### 1.2 Group Calendar Visibility

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| EVNT-05 | Admin can configure per-group whether group events appear **only in the group calendar** or also on the **main site calendar** | Must Have |
| EVNT-06 | Events from **private and hidden groups** are never shown on the main site calendar — this is enforced by privacy rules and cannot be overridden by any admin setting | Must Have |

---

### 2. Ticketing

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| TKET-01 | Organizer can create **multiple named ticket tiers** per event, each with its own name, price, quantity limit, and description (e.g. Early Bird £10 / qty 50, General £20 / qty 200, VIP £50 / qty 20) | Must Have |
| TKET-02 | Organizer can create a **free RSVP event** — no payment required, attendees simply register their attendance | Must Have |
| TKET-03 | Organizer can create **promo / discount codes** per event — percentage or fixed-amount discount, with optional usage limits and expiry dates | Must Have |
| TKET-04 | Organizer can restrict ticket purchase to **members of a specific BuddyBoss group** — non-members see the event but cannot purchase | Must Have |

---

### 3. Payments & Commission

#### 3.1 Stripe Connect Onboarding

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| PAY-01 | Organizer can **connect their Stripe account** to the platform via Stripe Connect OAuth onboarding — the flow completes in-plugin without leaving the WordPress admin | Must Have |

#### 3.2 Commission Model

The commission model is the platform's primary revenue mechanism. Commission is captured automatically via Stripe Connect application fees — the organizer receives their proceeds minus the platform fee without any manual reconciliation.

| Plan Tier | Commission Rate | Notes |
|-----------|----------------|-------|
| Free | Highest rate | TBD — recommended starting point: 10–15% |
| Pro | Medium-high | TBD |
| Plus | Medium-low | TBD |
| Ultimate | Lowest rate | TBD — recommended: 2–5% |

> **Decision required:** Exact commission percentages must be agreed before development of Phase 2. The rates are configurable post-launch but the initial defaults will set market expectations.

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| PAY-02 | Platform **automatically applies tiered commission** on every ticket sale based on the site admin's BuddyBoss plan tier — no manual intervention required | Must Have |
| PAY-03 | Admin can **configure the commission percentage** for each plan tier from the WordPress admin settings panel | Must Have |
| PAY-04 | When an organizer issues a **refund**, the platform commission is automatically reversed proportionally — organizer does not eat the commission on a refunded ticket | Must Have |

#### 3.3 Organizer Payouts

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| PAY-05 | Organizer can view a **payout dashboard** showing: total lifetime earnings, pending payout balance, payout schedule, and a searchable transaction history with per-ticket detail | Must Have |

---

### 4. Attendee Experience

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| ATTN-01 | Attendee can **join a waitlist** when an event is sold out — attendee receives an email notification when a spot opens (first-come first-served from waitlist) | Must Have |
| ATTN-02 | Attendee can **export any event** to their personal calendar via iCal download or a one-click Google Calendar link | Must Have |

---

### 5. BuddyBoss Integration

This is the product's primary differentiator. Every BuddyBoss community surface should feel aware of events.

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| BB-01 | Every BuddyBoss group has an **Events tab** showing a calendar view scoped to that group's events — visible to group members only if the group is private or hidden | Must Have |
| BB-02 | **Activity feed integration** — event creation, RSVPs, and ticket purchases automatically post to the relevant BuddyBoss activity feed (site-wide for public events; group feed for group events) — private group events never appear in the site-wide feed | Must Have |
| BB-03 | Organizer can **invite group members** directly from the group member roster when creating or editing an event — invitees receive a BuddyBoss notification and email | Must Have |
| BB-04 | **Member profiles** include an Events section showing: events the member has hosted, and events the member has attended | Must Have |

---

### 6. Admin & Moderation

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| ADMN-01 | Admin can configure **who is permitted to create events** site-wide: admins only / group organizers (group admin/moderator role) / all logged-in members / tiered by BuddyBoss plan level | Must Have |
| ADMN-02 | Admin can enable an **event moderation queue** — when enabled, newly submitted events require admin approval before going live (can be toggled off for trusted installs) | Must Have |
| ADMN-03 | Admin has a **platform-wide events dashboard** showing: all events across the site, total ticket sales revenue, commission earned by BuddyBoss, and per-event breakdowns | Must Have |
| ADMN-04 | Members can **report an event** as offensive or inappropriate using the existing BuddyBoss moderation / reporting system — reports appear in the standard BuddyBoss moderation queue | Must Have |

---

## User Flows

### Flow 1: Organizer creates a paid event

```
1. Organizer navigates to their group → Events tab → "Create Event"
2. Enters event title, description, date/time, timezone
3. Selects event type: In-Person (enters venue address) or Virtual (enters Zoom/Meet link)
4. Adds ticket tiers: clicks "Add Ticket Type" → name, price, quantity, description
5. Optionally adds promo codes
6. Optionally restricts tickets to group members only
7. Optionally invites group members (roster picker)
8. Submits event → goes to moderation queue (if enabled) or publishes immediately
9. On publish: activity feed post appears in group feed; event appears on group calendar
```

### Flow 2: Attendee purchases a ticket

```
1. Attendee sees event in activity feed or group/site calendar
2. Clicks through to event detail page
3. Selects ticket tier → clicks "Get Tickets"
4. Enters promo code (optional)
5. Stripe Payment Element loads (card details entered in Stripe iframe — no card data on server)
6. Submits payment
7. Stripe fires payment_intent.succeeded webhook → order confirmed
8. Attendee receives confirmation email with event details and iCal link
9. Activity feed posts: "[Attendee] is attending [Event]"
10. Ticket purchase appears on attendee's profile
```

### Flow 3: Organizer connects Stripe

```
1. Organizer navigates to their event dashboard (or prompted on first event creation)
2. Clicks "Connect Stripe Account"
3. Redirected to Stripe Connect OAuth flow (Stripe-hosted)
4. Completes onboarding on Stripe (existing account or new account)
5. Redirected back to plugin → account status shows "Connected"
6. Commission rate for their plan tier displayed (informational)
7. Organizer can now create paid ticket events
```

### Flow 4: Admin configures commission rates

```
1. Admin navigates to WP Admin → BuddyBoss Events → Settings → Commission
2. Sees table: Plan Tier | Commission Rate %
   Free    | [input]
   Pro     | [input]
   Plus    | [input]
   Ultimate| [input]
3. Sets rates → saves
4. Rates immediately apply to all new PaymentIntents created
   (Existing confirmed payments are not retroactively changed)
```

### Flow 5: Group privacy enforcement

```
Public group → admin can optionally allow events on main site calendar
             → activity feed posts visible to all site members

Private group → events only on group calendar
              → activity feed posts only in group feed (members only)
              → NEVER appear on main site calendar (hard rule)

Hidden group → same as Private
             → group itself is not discoverable to non-members
             → NEVER appear on main site calendar (hard rule)
```

---

## Revenue Model

### Platform commission

BuddyBoss captures revenue through Stripe Connect application fees on all ticket sales.

**Example transaction (Free plan, 10% commission):**
```
Attendee pays: £100
BuddyBoss takes: £10 (10% application fee, deducted by Stripe automatically)
Organizer receives: £90 (transferred to their Stripe account)
Stripe processing fee: charged separately to organiser (standard Stripe rates)
```

**Upgrade incentive:**

The tiered commission model creates a direct financial incentive for site admins to upgrade their BuddyBoss plan. A high-volume event community saves meaningfully by moving from Free to Ultimate.

**Example (£10,000/month in ticket sales):**

| Plan | Commission Rate | BuddyBoss earns | Organiser keeps |
|------|----------------|-----------------|-----------------|
| Free | 10% | £1,000 | £9,000 |
| Pro | 7% | £700 | £9,300 |
| Plus | 4% | £400 | £9,600 |
| Ultimate | 2% | £200 | £9,800 |

> **Note:** Exact rates are TBD. The above are illustrative examples only.

### Commission on refunds

When an organizer issues a refund, BuddyBoss's application fee is automatically reversed proportionally via Stripe's application fee refund API. This means:
- Full refund → full commission reversal
- Partial refund → proportional commission reversal
- The organizer never loses their portion on a refund, only their own proceeds

---

## Technical Constraints

| Constraint | Detail |
|------------|--------|
| **Platform** | WordPress plugin — must conform to WordPress plugin development standards |
| **Hard dependency** | BuddyBoss Platform plugin must be active — plugin will not activate without it and will display an admin notice if BuddyBoss is deactivated |
| **Minimum versions** | PHP 8.1+, WordPress 6.4+, BuddyBoss Platform (minimum version TBD) |
| **Payments** | Stripe Connect only — no other payment gateways in v1 |
| **PCI compliance** | All card input must use Stripe Payment Element (hosted iframe) — no card data ever touches the server |
| **Virtual events** | External meeting links only (Zoom via existing BuddyBoss integration, Google Meet URL) — no custom streaming infrastructure |
| **Commission timing** | Commission rate is locked at PaymentIntent creation time (checkout) — rates cannot be retroactively changed on confirmed orders |
| **WooCommerce** | Plugin must not conflict with WooCommerce — BuddyBoss sites commonly run both |

---

## Out of Scope (v1)

| Feature | Reason | Planned? |
|---------|--------|----------|
| QR code ticket scanning & check-in | Useful but not core to digital-first communities | v2 |
| Ticket transfer between users | Low demand for v1 | v2 |
| Co-organizer / co-host roles | Single organiser per event sufficient for v1 | v2 |
| In-app event notifications | Nice-to-have; email is sufficient for v1 | v2 |
| Event search & filtering (site-wide) | Calendar covers discovery for v1 | v2 |
| Multi-currency support | Single currency per Stripe account; adds significant complexity | Future |
| Custom streaming / video | Accept external URLs only — infrastructure is out of scope | Never |
| Offline / cash payments | Stripe Connect only | Never |
| Public event marketplace | Community-scoped only; public marketplace is a different product | Future |
| Seating charts | High complexity, niche use case | Future |
| Mobile app | Web/WordPress plugin first | Future |

---

## Release Criteria

The plugin is ready to ship when all of the following are true:

### Phase 1 — Foundation + Event Management
- [ ] Plugin activates cleanly on a fresh BuddyBoss install; fails gracefully with an admin notice if BuddyBoss Platform is inactive
- [ ] Organizer can create in-person, virtual (Zoom + Meet), and recurring events — all appear on site calendar after approval/publish
- [ ] Organizer can edit one occurrence of a recurring series without corrupting other occurrences
- [ ] Admin creation permission restrictions (all 4 modes) are correctly enforced at the event creation UI
- [ ] Events from private/hidden groups never appear on the main site calendar in any circumstance

### Phase 2 — Payments + Ticketing
- [ ] Organizer can complete Stripe Connect onboarding end-to-end in under 5 minutes
- [ ] Ticket purchase completes successfully — funds reach organiser Stripe, BuddyBoss application fee is deducted at the correct plan-tier rate
- [ ] Organiser-initiated refund automatically reverses the platform commission with no manual step
- [ ] Ticket availability is correctly enforced under concurrent purchases (no overselling)
- [ ] Organiser payout dashboard shows accurate earnings and transaction history
- [ ] Sold-out event waitlist auto-notifies attendee when a spot opens
- [ ] iCal and Google Calendar export links work correctly with accurate event data

### Phase 3 — BuddyBoss Integration
- [ ] Every BuddyBoss group has a working Events tab with group-scoped calendar
- [ ] Activity items appear correctly on event create, RSVP, and ticket purchase — in the correct feed, respecting group privacy
- [ ] Private/hidden group events never appear in the site-wide activity feed
- [ ] Organiser can send invites from the group member roster; invitees receive notification and email
- [ ] Member profile Events section displays correct hosted and attended events

### Cross-cutting
- [ ] No PHP errors or warnings on WP_DEBUG mode
- [ ] Plugin tested alongside WooCommerce — no checkout URL conflicts or hook interference
- [ ] All admin settings persist correctly across plugin deactivation/reactivation
- [ ] Email confirmations send reliably (tested with a transactional email provider)

---

## Open Questions

| # | Question | Owner | Priority |
|---|----------|-------|----------|
| 1 | What are the commission rates for each plan tier (Free / Pro / Plus / Ultimate)? | Business | **Critical** — blocks Phase 2 development |
| 2 | Does BuddyBoss expose the site's current plan tier programmatically via a PHP filter or function, or does the admin need to set it manually? | Engineering | **Critical** — determines commission calculation architecture |
| 3 | What is the minimum supported BuddyBoss Platform version? | Engineering | High — determines which BP hooks are available |
| 4 | Will the Stripe platform account (the account that captures application fees) be owned by BuddyBoss the company, meaning all installs share one platform account? | Business / Engineering | High — affects onboarding architecture |
| 5 | Should free events (RSVP only) be subject to any commission or platform fee? | Business | Medium |
| 6 | Should the event moderation queue be ON or OFF by default for new installs? | Product | Medium |
| 7 | What is the refund policy for BuddyBoss's application fee if a dispute is raised (not a voluntary refund)? | Business / Legal | Medium |
| 8 | Will this plugin be included in any BuddyBoss plan tier or sold as a separate add-on? | Business | Medium |
| 9 | Are there localisation / multi-language requirements for v1? | Product | Low |

---

## Appendix: v1 Requirements Summary

| ID | Requirement | Phase |
|----|-------------|-------|
| EVNT-01 | In-person event creation (venue, address, capacity) | 1 |
| EVNT-02 | Virtual event creation (BuddyBoss Zoom + Google Meet link) | 1 |
| EVNT-03 | Recurring event series with edit-this / edit-future options | 1 |
| EVNT-04 | Draft and scheduled publish | 1 |
| EVNT-05 | Admin configures group event visibility on main calendar | 1 |
| EVNT-06 | Private/hidden group events never on main calendar (enforced) | 1 |
| TKET-01 | Multiple ticket tiers per event | 2 |
| TKET-02 | Free RSVP events | 2 |
| TKET-03 | Promo / discount codes | 2 |
| TKET-04 | Group-member-only ticket restriction | 2 |
| PAY-01 | Stripe Connect OAuth onboarding for organizers | 2 |
| PAY-02 | Tiered commission auto-applied at plan rate | 2 |
| PAY-03 | Admin configures commission % per plan tier | 2 |
| PAY-04 | Refund automatically reverses commission | 2 |
| PAY-05 | Organizer payout dashboard | 2 |
| ATTN-01 | Waitlist with auto-notification | 2 |
| ATTN-02 | iCal / Google Calendar export | 2 |
| BB-01 | Group Events tab with group-scoped calendar | 3 |
| BB-02 | Activity feed integration (create / RSVP / purchase) | 3 |
| BB-03 | Group member invite from roster | 3 |
| BB-04 | Member profile Events section | 3 |
| ADMN-01 | Configurable event creation permissions | 1 |
| ADMN-02 | Event moderation / approval queue | 1 |
| ADMN-03 | Platform-wide revenue dashboard | 1 |
| ADMN-04 | User event reporting via BuddyBoss moderation | 1 |

---

*Document prepared: 2026-03-10*
*Next review: Before Phase 1 development kickoff*
