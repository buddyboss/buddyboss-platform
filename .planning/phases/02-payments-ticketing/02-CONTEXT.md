# Phase 2: Payments + Ticketing - Context

**Gathered:** 2026-03-14
**Status:** Ready for planning

<domain>
## Phase Boundary

Free RSVP system, waitlist management, and calendar export. Organizers can create events that attendees RSVP to with a single click, manage capacity with automatic waitlist handling, and allow attendees to export events to external calendars. Paid ticketing (Stripe Connect, commission capture, checkout, refunds, payout dashboard) is explicitly deferred to a future phase.

Requirements in scope: TKET-02, TKET-04 (group restriction for RSVP only), ATTN-01, ATTN-02.

</domain>

<decisions>
## Implementation Decisions

### Phase scope change
- Paid ticketing and Stripe Connect integration deferred entirely — no checkout, no commission capture, no refunds, no payout dashboard in this phase
- Phase 2 delivers: free RSVP, group-restricted RSVP, waitlist, and calendar export

### RSVP flow
- One-click RSVP button on the event page — no form, no extra steps
- On RSVP: button changes to "Attending ✓" with a cancel/un-RSVP option — in-page state change only, no email, no redirect
- Attendee list is publicly visible on the event page — avatars and names shown, encourages social proof
- Organizer manages attendee list from their own event page view (organizer-only panel with list + remove controls) — no separate admin screen

### Waitlist mechanics
- Broadcast notification model — when a spot opens, all waitlisted users are notified simultaneously; first to click RSVP claims the spot (no timed hold, no FIFO auto-assign)
- Three triggers open a spot: (1) attending user cancels RSVP, (2) organizer increases event capacity, (3) organizer manually removes an attendee
- When event hits capacity, the RSVP button automatically changes label to "Join Waitlist" — no separate CTA or toggle
- Waitlist notification delivery: BuddyBoss in-app notification (bp_notifications_add_notification) + wp_mail() email to all waitlisted users

### Group-restricted RSVP
- Organizer configures restriction in the event creation form — a "Restrict RSVP to group members" toggle with a group search/selector in an RSVP settings section of the wizard
- Non-members see the RSVP button disabled with message "RSVP limited to members of [Group Name]" — event remains fully visible
- Group restriction affects RSVP only — event visibility is unchanged and still governed by group privacy rules from Phase 1 (EVNT-05/06)

### Claude's Discretion
- iCal / Google Calendar export placement on event page and implementation approach (ATTN-02 — not discussed)
- Exact notification copy for waitlist emails and in-app notifications
- Loading/optimistic UI behaviour during RSVP button state transition
- Database storage for waitlist entries (status column in bp_event_attendees already has 'registered' default — add 'waitlisted' status)

</decisions>

<code_context>
## Existing Code Insights

### Reusable Assets
- `bp_event_attendees` table: Already has `ticket_id`, `order_id`, `status`, `date_created` columns. RSVP entries use `status = 'registered'`; waitlist entries can use `status = 'waitlisted'` — no schema change needed
- `bp_events_get_events()`: Supports filtering by attendee user ID via INNER JOIN on attendees table — reusable for "events I'm attending" queries
- `BP_REST_Events_Endpoint`: Existing REST endpoint — RSVP actions (add/cancel) should be added as sub-routes here
- `event-card.php` (ReadyLaunch): Existing event card with BEM classes — RSVP button can be added here
- `single/home.php` (ReadyLaunch): Single event page template — attendee list panel and RSVP button go here

### Established Patterns
- `bp_get_option()` / `bp_update_option()`: All settings follow this pattern — RSVP-related event meta (group restriction, capacity) follows `bp_events_get_event_meta()` / `bp_events_update_event_meta()`
- WP nonce pattern: All AJAX/REST actions use `wp_verify_nonce()` — RSVP REST endpoints follow suit
- `bp_parse_args()` for function args: Continue this for any new CRUD functions

### Integration Points
- Phase 1 creation wizard (`screens/create.php`): RSVP settings section (group restriction toggle + group selector) needs to be added as a new fieldset within the existing multi-step wizard
- `bp_events_user_can_create` hook: Scaffolded for plan-tier detection — RSVP permission check (`bp_events_user_can_rsvp`) should follow the same hook pattern
- BuddyBoss notification API (`bp_notifications_add_notification`): Used for waitlist spot-open notifications — needs `component_name = 'events'` and `component_action = 'waitlist_spot_open'`

</code_context>

<specifics>
## Specific Ideas

No specific references — standard community event RSVP patterns apply.

</specifics>

<deferred>
## Deferred Ideas

- **PAY-01**: Stripe Connect organizer OAuth onboarding — future phase
- **PAY-02**: Tiered commission capture via Stripe application fees — future phase
- **PAY-03**: Admin-configurable commission rates per BuddyBoss plan tier — future phase
- **PAY-04**: Proportional commission refund reversal — future phase
- **PAY-05**: Organizer payout dashboard (earnings, pending payouts, transaction history) — future phase
- **TKET-01**: Multiple named paid ticket tiers with individual pricing — future phase
- **TKET-03**: Promo/discount codes (percentage or fixed-amount) — future phase
- **TKET-04 (paid variant)**: Group-restricted paid ticket purchase — future phase (RSVP restriction implemented this phase)
- **Plan-tier enforcement** for event creation (`bp_events_user_can_create`): Scaffolded hook from Phase 1, wires to BuddyBoss plan data — future phase when payments exist

</deferred>

---

*Phase: 02-payments-ticketing*
*Context gathered: 2026-03-14*
