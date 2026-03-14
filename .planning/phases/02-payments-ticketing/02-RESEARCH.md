# Phase 02: Payments + Ticketing - Research

**Researched:** 2026-03-14
**Domain:** WordPress plugin — free RSVP system, waitlist management, calendar export (BuddyBoss Platform context)
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

**Phase scope change**
- Paid ticketing and Stripe Connect integration deferred entirely — no checkout, no commission capture, no refunds, no payout dashboard in this phase
- Phase 2 delivers: free RSVP, group-restricted RSVP, waitlist, and calendar export

**RSVP flow**
- One-click RSVP button on the event page — no form, no extra steps
- On RSVP: button changes to "Attending ✓" with a cancel/un-RSVP option — in-page state change only, no email, no redirect
- Attendee list is publicly visible on the event page — avatars and names shown, encourages social proof
- Organizer manages attendee list from their own event page view (organizer-only panel with list + remove controls) — no separate admin screen

**Waitlist mechanics**
- Broadcast notification model — when a spot opens, all waitlisted users are notified simultaneously; first to click RSVP claims the spot (no timed hold, no FIFO auto-assign)
- Three triggers open a spot: (1) attending user cancels RSVP, (2) organizer increases event capacity, (3) organizer manually removes an attendee
- When event hits capacity, the RSVP button automatically changes label to "Join Waitlist" — no separate CTA or toggle
- Waitlist notification delivery: BuddyBoss in-app notification (bp_notifications_add_notification) + wp_mail() email to all waitlisted users

**Group-restricted RSVP**
- Organizer configures restriction in the event creation form — a "Restrict RSVP to group members" toggle with a group search/selector in an RSVP settings section of the wizard
- Non-members see the RSVP button disabled with message "RSVP limited to members of [Group Name]" — event remains fully visible
- Group restriction affects RSVP only — event visibility is unchanged and still governed by group privacy rules from Phase 1 (EVNT-05/06)

### Claude's Discretion
- iCal / Google Calendar export placement on event page and implementation approach (ATTN-02 — not discussed)
- Exact notification copy for waitlist emails and in-app notifications
- Loading/optimistic UI behaviour during RSVP button state transition
- Database storage for waitlist entries (status column in bp_event_attendees already has 'registered' default — add 'waitlisted' status)

### Deferred Ideas (OUT OF SCOPE)
- PAY-01: Stripe Connect organizer OAuth onboarding — future phase
- PAY-02: Tiered commission capture via Stripe application fees — future phase
- PAY-03: Admin-configurable commission rates per BuddyBoss plan tier — future phase
- PAY-04: Proportional commission refund reversal — future phase
- PAY-05: Organizer payout dashboard (earnings, pending payouts, transaction history) — future phase
- TKET-01: Multiple named paid ticket tiers with individual pricing — future phase
- TKET-03: Promo/discount codes (percentage or fixed-amount) — future phase
- TKET-04 (paid variant): Group-restricted paid ticket purchase — future phase (RSVP restriction implemented this phase)
- Plan-tier enforcement for event creation (bp_events_user_can_create): Scaffolded hook from Phase 1, wires to BuddyBoss plan data — future phase when payments exist
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| TKET-02 | Organizer can create a free RSVP event with no payment required | bp_event_attendees table exists; status='registered'; REST sub-routes POST /events/{id}/rsvp and DELETE /events/{id}/rsvp; capacity check at RSVP time |
| TKET-04 | Organizer can restrict ticket purchase to members of a specific BuddyBoss group (RSVP variant only) | groups_is_user_member() check in permission callback; rsvp_group_id stored as event meta; creation wizard gets new RSVP settings step |
| ATTN-01 | Attendee can join a waitlist when an event is sold out and receives a notification when a spot becomes available | status='waitlisted' row in bp_event_attendees; bp_notifications_add_notification + wp_mail() broadcast; three spot-open triggers already identified |
| ATTN-02 | Attendee can export an event to their personal calendar via iCal or Google Calendar link | REST routes already implemented: GET /events/{id}/ical and GET /events/{id}/gcal-url; template already has the links; JS needed only for gcal-url fetch |
</phase_requirements>

---

## Summary

Phase 2 is entirely within the existing WordPress/BuddyBoss architecture established in Phase 1. No new libraries are required. The core deliverables are: (1) RSVP REST sub-routes wired to the existing `bp_event_attendees` table, (2) capacity-aware button switching between RSVP / Attending / Join Waitlist in the single-event template, (3) broadcast notifications via the BuddyBoss notification API and `wp_mail()` when waitlist spots open, (4) group-restriction meta stored on event creation and enforced in RSVP permission checks, and (5) calendar export UI wired to the already-implemented REST routes.

The existing codebase is well-prepared: `bp_event_attendees` already has a `status` column with a default of `'registered'`, so `'waitlisted'` requires no schema change. The REST endpoint `class-bp-rest-events-endpoint.php` already registers `/events/{id}/ical` and `/events/{id}/gcal-url` routes that are fully implemented. The single-event template (`single/home.php`) already has a calendar-links section and a Register panel placeholder marked "Phase 2". The creation wizard JS (`bp-events-create.js`) uses a plain-JS IIFE pattern consistent with WP coding standards that a new RSVP settings step can follow.

The only net-new infrastructure is: three PHP functions (`bp_events_rsvp_event`, `bp_events_cancel_rsvp`, `bp_events_get_attendees`), two REST sub-routes (POST and DELETE `/events/{id}/rsvp`), waitlist notification dispatch logic, event-meta keys for group restriction, and a new RSVP settings step in the wizard JS. Calendar export is already 95% done — it needs only a small JS handler in the single-event page to resolve the Google Calendar URL via REST.

**Primary recommendation:** Layer RSVP on top of Phase 1's table and REST endpoint without modifying any existing routes. All new capability lives in new functions, new sub-routes, and new template sections.

---

## Standard Stack

### Core (all already in use — no new installs)

| Library / API | Version | Purpose | Why Standard |
|---------------|---------|---------|--------------|
| `bp_event_attendees` table | Phase 1 schema | Stores RSVP and waitlist rows | Already exists; `status` column handles both states |
| `BP_REST_Events_Endpoint` | Phase 1 | REST controller for `/events` | Add sub-routes here, not a new controller |
| `bp_notifications_add_notification()` | BuddyBoss Platform | In-app notification delivery | Established BuddyBoss notification API |
| `wp_mail()` | WordPress core | Email delivery for waitlist broadcast | Standard WP email; no external library needed |
| `groups_is_user_member()` | BuddyBoss Platform | Group membership check for RSVP restriction | Standard BP groups function |
| `bp_events_get_meta()` / `bp_events_update_meta()` | Phase 1 | Event meta CRUD | Established event meta API pattern |

### Supporting

| Library / API | Version | Purpose | When to Use |
|---------------|---------|---------|-------------|
| `bp_parse_args()` | BuddyBoss core | Argument parsing in new CRUD functions | All new function argument arrays |
| `wp_verify_nonce()` | WordPress core | AJAX/REST security | All REST permission callbacks |
| `get_avatar()` / `bp_core_fetch_avatar()` | WordPress / BuddyBoss | Attendee avatar display in template | Attendee list rendering |
| Vanilla IIFE JS (no framework) | Phase 1 pattern | Single-event page RSVP button behaviour | Consistent with wizard JS style |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `wp_mail()` broadcast | Dedicated email library | wp_mail() sufficient for this scale; adding a library is a dependency for no gain |
| Broadcast model waitlist | FIFO auto-assign | User explicitly chose broadcast — first-click fairness, no queue complexity |
| New REST controller | New AJAX handler | Sub-routes on existing controller consistent with Phase 1 pattern |

**Installation:** No new packages required.

---

## Architecture Patterns

### Recommended File Structure for Phase 2 Additions

```
src/bp-events/
├── bp-events-functions.php          # Add: bp_events_rsvp_event(), bp_events_cancel_rsvp(),
│                                    #       bp_events_get_attendees(), bp_events_get_waitlist(),
│                                    #       bp_events_notify_waitlist(), bp_events_user_can_rsvp()
├── classes/
│   └── class-bp-rest-events-endpoint.php  # Add: rsvp sub-routes (POST/DELETE /events/{id}/rsvp)
│                                           #       attendees sub-route (GET /events/{id}/attendees)
├── assets/js/
│   └── bp-events-single.js          # NEW: RSVP button toggling, attendee list, gcal URL fetch
└── assets/css/
    └── bp-events.css                # Extend: RSVP button states, attendee list, waitlist state

src/bp-templates/bp-nouveau/readylaunch/events/single/
└── home.php                         # Extend: replace Register placeholder with RSVP button panel,
                                     #          add attendee list section, wire calendar links JS
```

### Pattern 1: RSVP REST Sub-routes on Existing Controller

**What:** Add `rsvp` and `attendees` sub-routes directly in `BP_REST_Events_Endpoint::register_routes()`, following the existing publish/cancel sub-route pattern.

**When to use:** Any new action on a single event resource.

**Example:**
```php
// Source: class-bp-rest-events-endpoint.php (Phase 1 pattern)
register_rest_route(
    $this->namespace,
    '/' . $this->rest_base . '/(?P<id>[\d]+)/rsvp',
    array(
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'rsvp_item' ),
            'permission_callback' => array( $this, 'rsvp_item_permissions_check' ),
        ),
        array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'cancel_rsvp_item' ),
            'permission_callback' => array( $this, 'rsvp_item_permissions_check' ),
        ),
    )
);
```

### Pattern 2: RSVP State Stored in bp_event_attendees

**What:** A user's RSVP state is a single row in `bp_event_attendees` with `status = 'registered'` or `status = 'waitlisted'`. No new table needed. The UNIQUE KEY `event_user (event_id, user_id)` prevents duplicates.

**When to use:** Any query about whether a user is attending or waitlisted.

**Example:**
```php
// Source: bp-events-functions.php (Phase 1 schema — bp_events_install())
// Attendee row for RSVP:
// INSERT INTO bp_event_attendees (event_id, user_id, status) VALUES (%d, %d, 'registered')
// Waitlist row:
// INSERT INTO bp_event_attendees (event_id, user_id, status) VALUES (%d, %d, 'waitlisted')
// ON DUPLICATE KEY UPDATE status = 'waitlisted'  -- if user cancels and rejoins waitlist
```

### Pattern 3: Capacity Check at RSVP Time

**What:** Count rows with `status = 'registered'` before inserting. If count >= capacity, insert as `'waitlisted'` instead. If capacity is NULL (unlimited), always insert as `'registered'`.

**When to use:** Every RSVP create operation.

```php
// Pseudocode — implement inside bp_events_rsvp_event()
$registered_count = $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$bp->events->table_name_attendees}
     WHERE event_id = %d AND status = 'registered'",
    $event_id
) );
$at_capacity = ! is_null( $event->capacity ) && $registered_count >= (int) $event->capacity;
$status = $at_capacity ? 'waitlisted' : 'registered';
```

### Pattern 4: Waitlist Spot-Open Broadcast

**What:** When a registered spot opens (cancel, capacity increase, organizer removal), find all `status = 'waitlisted'` rows for the event and send each user a notification and email.

**When to use:** After any operation that reduces the registered count or increases capacity.

**Notification call:**
```php
// Source: BuddyBoss Platform — bp_notifications_add_notification()
// https://www.buddyboss.com/resources/reference/functions/bp_notifications_add_notification/
bp_notifications_add_notification( array(
    'user_id'           => $waitlisted_user_id,
    'item_id'           => $event_id,
    'secondary_item_id' => 0,
    'component_name'    => 'events',
    'component_action'  => 'waitlist_spot_open',
    'is_new'            => 1,
) );
```

**Notification format callback** — extend `bp_events_format_notifications()` in `bp-events-functions.php` to handle `'waitlist_spot_open'` action:
```php
case 'waitlist_spot_open':
    $event = bp_events_get_event( $item_id );
    $text  = sprintf(
        __( 'A spot has opened for %s — RSVP now!', 'buddyboss' ),
        $event ? $event->title : __( 'an event', 'buddyboss' )
    );
    $link  = $event ? bp_get_event_permalink( $event ) : bp_get_events_directory_url();
    break;
```

### Pattern 5: Group Restriction via Event Meta

**What:** Store `rsvp_group_id` as event meta using the existing `bp_events_update_meta()` API. Check in `bp_events_user_can_rsvp()` using `groups_is_user_member()`.

**When to use:** When organizer enables the group restriction toggle in the creation wizard.

```php
// Store on event creation/edit — meta key: 'rsvp_group_id'
bp_events_update_meta( $event_id, 'rsvp_group_id', (int) $group_id );

// Check in RSVP permission:
$rsvp_group_id = (int) bp_events_get_meta( $event_id, 'rsvp_group_id', true );
if ( $rsvp_group_id > 0 ) {
    if ( ! groups_is_user_member( $user_id, $rsvp_group_id ) ) {
        return new WP_Error( 'bp_rest_events_rsvp_restricted', ... );
    }
}
```

### Pattern 6: Calendar Export (ATTN-02 — Already Implemented)

**What:** iCal download link points directly to the REST endpoint (no JS needed). Google Calendar requires a JS fetch to get the signed URL, then `window.open()`.

**When to use:** Both links are already in `single/home.php`. The iCal link works today. The Google Calendar link needs a small JS handler.

The single-event template already has:
```php
// Source: single/home.php (Phase 1) — already wired
<a href="<?php echo esc_url( rest_url( 'buddyboss/v1/events/' . $event->id . '/ical' ) ); ?>"
   class="bb-rl-cal-link bb-rl-cal-link--ical" download>
<a href="#" class="bb-rl-cal-link bb-rl-cal-link--gcal"
   data-event-id="<?php echo (int) $event->id; ?>">
```

The Google Calendar `<a>` just needs a JS click handler in `bp-events-single.js` that fetches `/events/{id}/gcal-url` and redirects.

### Pattern 7: JS for Single Event Page

**What:** New `bp-events-single.js` (vanilla IIFE, no framework — consistent with `bp-events-create.js`). Enqueued only on the single event screen.

**Enqueue gating condition:**
```php
// In bp_events_enqueue_single_assets() — add to bp-events-loader.php
if ( bp_is_current_component( 'events' ) && bp_is_single_item() ) {
    wp_enqueue_script( 'bp-events-single', ... );
    wp_localize_script( 'bp-events-single', 'bpEventsSingle', array(
        'restUrl'        => rest_url( 'buddyboss/v1/events' ),
        'nonce'          => wp_create_nonce( 'wp_rest' ),
        'eventId'        => (int) $bp->events->current_event->id,
        'currentUserId'  => bp_loggedin_user_id(),
        'isAttending'    => /* bool — pre-computed in PHP */,
        'isWaitlisted'   => /* bool — pre-computed in PHP */,
        'atCapacity'     => /* bool — pre-computed in PHP */,
        'canRsvp'        => /* bool — group restriction check result */,
        'restrictedMsg'  => /* string — group restriction message if applicable */,
    ) );
}
```

### Anti-Patterns to Avoid

- **Do not use a separate AJAX handler for RSVP.** Use the REST API sub-route pattern already established in Phase 1 — `admin-ajax.php` is the old pattern; `WP_REST_Server` is the current standard.
- **Do not store waitlist in a separate table.** The `status` column in `bp_event_attendees` is the correct storage — adding a table adds schema complexity for no benefit.
- **Do not re-query attendee count on every page load without caching.** The attendee count is needed to determine button state — cache it via `wp_cache_get/set` with a short TTL or precompute in PHP before localization.
- **Do not send email with `wp_mail()` inside a loop on the waitlist broadcast without time awareness.** For large waitlists, consider `wp_schedule_single_event()` to defer the email batch — but for this phase, direct `wp_mail()` per waitlisted user is acceptable.
- **Do not add `rsvp_group_id` as a column to the events table.** Event meta is the correct storage pattern established in Phase 1 for event-specific settings.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| In-app notification delivery | Custom notification table + UI | `bp_notifications_add_notification()` | BuddyBoss has a full notification system with bell icon UI |
| Group membership check | Query `bp_groups_members` directly | `groups_is_user_member( $user_id, $group_id )` | BP function handles caching and role hierarchy |
| Email sending | `wp_mail()` wrapper classes | `wp_mail()` directly | Standard WP; no abstraction needed at this scale |
| Avatar rendering | Custom avatar query | `bp_core_fetch_avatar()` or `get_avatar()` | BP avatar API handles BuddyBoss-specific avatar sources |
| Duplicate attendee prevention | Application-level check | UNIQUE KEY `event_user (event_id, user_id)` in DB | DB constraint is the source of truth; no race condition risk |

**Key insight:** BuddyBoss Platform's notification, groups, and avatar APIs are fully capable for this phase. Using them is both faster and consistent with what BuddyBoss admins expect.

---

## Common Pitfalls

### Pitfall 1: Race Condition on RSVP When Event is at Capacity

**What goes wrong:** Two users simultaneously RSVP to the last available spot. Application-level capacity check passes for both; both get `status = 'registered'`; event is over capacity.

**Why it happens:** PHP capacity check and INSERT are not atomic.

**How to avoid:** The `UNIQUE KEY event_user (event_id, user_id)` prevents the same user from double-RSVPing, but two *different* users racing is a real risk. Use `INSERT ... WHERE NOT EXISTS (SELECT ...)` or rely on the DB-level count + INSERT wrapped in a transaction, or accept occasional over-capacity and correct with waitlist promotion logic. For a community events plugin at typical BuddyBoss scale (not e-commerce ticketing), a simple check-then-insert is acceptable — document the edge case.

**Warning signs:** Attendee count exceeds `$event->capacity` in reporting queries.

### Pitfall 2: Waitlist Notification Sent Before RSVP Slot is Actually Available

**What goes wrong:** Organizer removes an attendee; notification fires; by the time waitlisted users click RSVP, the slot may have been filled by a different user who acted faster.

**Why it happens:** Broadcast model is intentional, but the RSVP endpoint must re-validate capacity at click time, not trust the notification.

**How to avoid:** The RSVP endpoint must always re-check capacity atomically. The notification is an advisory — it does not reserve a slot. This is the specified broadcast model.

**Warning signs:** Waitlisted users report being unable to RSVP after receiving a notification.

### Pitfall 3: Group Restriction Stored Without Validation

**What goes wrong:** Organizer saves `rsvp_group_id` for a group they are not a member of, or saves an invalid group ID. RSVP button then permanently blocks all users.

**Why it happens:** Meta save does not validate group existence or organizer membership.

**How to avoid:** In the RSVP settings step of the creation wizard REST handler, validate that the group ID exists (`groups_get_group()` returns a valid group) before saving meta. The UI group search/selector should already surface only valid groups.

### Pitfall 4: Calendar Export Links Shown Before REST Routes are Registered

**What goes wrong:** `wp_enqueue_scripts` fires before `rest_api_init`; the `rest_url()` call in the template returns a URL that 404s until a page reload.

**Why it happens:** The routes are registered in `rest_api_init` (correct) but the template link is static — it does not depend on registration timing. This is actually fine because `rest_url()` generates the URL pattern, not a live request.

**How to avoid:** This is a non-issue for the iCal link (direct `<a download>`). For Google Calendar, the JS fetch will get a 404 only if the route is not registered — confirm routes are registered in `rest_api_init` (already done in Phase 1).

### Pitfall 5: RSVP Button State Mismatch After Page Reload

**What goes wrong:** User RSVPs; button changes to "Attending" via JS; they reload the page; button reverts to "RSVP" because the PHP template does not know the user is attending.

**Why it happens:** The template is PHP-rendered without RSVP state. The JS state update is in-memory only.

**How to avoid:** Pre-compute RSVP state in PHP before rendering/localizing the single-event template and pass it into `bpEventsSingle.isAttending` / `isWaitlisted`. The JS initialises button state from the localized data, not the DOM.

### Pitfall 6: `allow_duplicate` on Waitlist Notifications

**What goes wrong:** Capacity changes multiple times quickly (organizer adds 5 seats, removes 2); waitlisted users receive multiple notifications within minutes.

**Why it happens:** `bp_notifications_add_notification()` defaults `allow_duplicate` to `false`, but if the is_new status changes between calls, it may create a new notification anyway.

**How to avoid:** For the waitlist notification, pass `'allow_duplicate' => false` explicitly. Clear the notification once the user successfully RSVPs.

---

## Code Examples

### RSVP Insert Pattern

```php
// Source: bp-events-functions.php pattern (same as Phase 1 attendees table schema)
function bp_events_rsvp_event( $event_id, $user_id = 0 ) {
    global $wpdb;
    $bp = buddypress();

    if ( ! $user_id ) {
        $user_id = bp_loggedin_user_id();
    }
    if ( ! $user_id || ! $event_id ) {
        return false;
    }

    $event = bp_events_get_event( $event_id );
    if ( ! $event ) {
        return false;
    }

    // Check capacity.
    $registered_count = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$bp->events->table_name_attendees}
         WHERE event_id = %d AND status = 'registered'",
        $event_id
    ) );

    $at_capacity = ! is_null( $event->capacity ) && $registered_count >= (int) $event->capacity;
    $status      = $at_capacity ? 'waitlisted' : 'registered';

    $result = $wpdb->replace(
        $bp->events->table_name_attendees,
        array(
            'event_id'     => $event_id,
            'user_id'      => $user_id,
            'status'       => $status,
            'date_created' => bp_core_current_time(),
        ),
        array( '%d', '%d', '%s', '%s' )
    );

    return ( false !== $result ) ? $status : false;
}
```

### Cancel RSVP + Waitlist Broadcast Trigger

```php
// Source: bp-events-functions.php pattern
function bp_events_cancel_rsvp( $event_id, $user_id = 0 ) {
    global $wpdb;
    $bp = buddypress();

    if ( ! $user_id ) {
        $user_id = bp_loggedin_user_id();
    }

    $was_registered = ( 'registered' === $wpdb->get_var( $wpdb->prepare(
        "SELECT status FROM {$bp->events->table_name_attendees}
         WHERE event_id = %d AND user_id = %d",
        $event_id, $user_id
    ) ) );

    $result = $wpdb->delete(
        $bp->events->table_name_attendees,
        array( 'event_id' => $event_id, 'user_id' => $user_id ),
        array( '%d', '%d' )
    );

    // Broadcast to waitlist only if a registered spot was freed.
    if ( $was_registered && $result ) {
        bp_events_notify_waitlist( $event_id );
    }

    return false !== $result;
}
```

### Waitlist Broadcast

```php
// Source: bp-events-functions.php pattern + BuddyBoss notification API
function bp_events_notify_waitlist( $event_id ) {
    global $wpdb;
    $bp = buddypress();

    $waitlisted_users = $wpdb->get_col( $wpdb->prepare(
        "SELECT user_id FROM {$bp->events->table_name_attendees}
         WHERE event_id = %d AND status = 'waitlisted'",
        $event_id
    ) );

    if ( empty( $waitlisted_users ) ) {
        return;
    }

    $event = bp_events_get_event( $event_id );
    if ( ! $event ) {
        return;
    }

    $event_link  = bp_get_event_permalink( $event );
    $event_title = $event->title;

    foreach ( $waitlisted_users as $user_id ) {
        // In-app notification.
        bp_notifications_add_notification( array(
            'user_id'           => (int) $user_id,
            'item_id'           => $event_id,
            'secondary_item_id' => 0,
            'component_name'    => 'events',
            'component_action'  => 'waitlist_spot_open',
            'is_new'            => 1,
            'allow_duplicate'   => false,
        ) );

        // Email notification.
        $user  = get_userdata( $user_id );
        if ( ! $user ) {
            continue;
        }
        wp_mail(
            $user->user_email,
            sprintf( __( 'A spot opened for %s', 'buddyboss' ), $event_title ),
            sprintf(
                __( "A spot has opened for \"%s\".\n\nRSVP now before it fills up: %s\n\nThis is a broadcast to all waitlisted attendees — first to RSVP gets the spot.", 'buddyboss' ),
                $event_title,
                $event_link
            )
        );
    }
}
```

### JS: RSVP Button Initialisation (bp-events-single.js skeleton)

```javascript
// Source: consistent with Phase 1 bp-events-create.js IIFE pattern
( function() {
    'use strict';

    var cfg = window.bpEventsSingle || {};

    function init() {
        var btn = document.getElementById( 'bb-rl-rsvp-btn' );
        if ( ! btn ) return;

        renderButtonState( btn );
        btn.addEventListener( 'click', handleRsvpClick );

        // Google Calendar link.
        var gcalLinks = document.querySelectorAll( '.bb-rl-cal-link--gcal' );
        gcalLinks.forEach( function( link ) {
            link.addEventListener( 'click', handleGcalClick );
        } );
    }

    function renderButtonState( btn ) {
        if ( ! cfg.currentUserId ) {
            btn.textContent = cfg.i18n.rsvp;
            return;
        }
        if ( ! cfg.canRsvp ) {
            btn.disabled    = true;
            btn.textContent = cfg.restrictedMsg;
            return;
        }
        if ( cfg.isAttending ) {
            btn.textContent = cfg.i18n.attending;
            btn.dataset.state = 'attending';
        } else if ( cfg.isWaitlisted ) {
            btn.textContent = cfg.i18n.waitlisted;
            btn.dataset.state = 'waitlisted';
        } else if ( cfg.atCapacity ) {
            btn.textContent = cfg.i18n.joinWaitlist;
            btn.dataset.state = 'none';
        } else {
            btn.textContent = cfg.i18n.rsvp;
            btn.dataset.state = 'none';
        }
    }

    function handleRsvpClick( e ) {
        e.preventDefault();
        var btn = e.currentTarget;
        var state = btn.dataset.state;
        if ( state === 'attending' || state === 'waitlisted' ) {
            doCancel( btn );
        } else {
            doRsvp( btn );
        }
    }

    function doRsvp( btn ) {
        btn.disabled = true;
        fetch( cfg.restUrl + '/' + cfg.eventId + '/rsvp', {
            method: 'POST',
            headers: { 'X-WP-Nonce': cfg.nonce, 'Content-Type': 'application/json' }
        } )
        .then( function( r ) { return r.json(); } )
        .then( function( data ) {
            cfg.isAttending  = data.status === 'registered';
            cfg.isWaitlisted = data.status === 'waitlisted';
            cfg.atCapacity   = data.at_capacity;
            renderButtonState( btn );
        } )
        .finally( function() { btn.disabled = false; } );
    }

    document.addEventListener( 'DOMContentLoaded', init );
}() );
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `admin-ajax.php` for front-end actions | `WP_REST_Server` sub-routes | WordPress 4.7 (2016) | Phase 1 already uses REST; RSVP follows same pattern |
| Separate waitlist table | `status` column in attendees table | Architecture decision Phase 1 | No schema change needed in Phase 2 |
| Custom iCal generation | REST route returns `.ics` with RFC 5545 headers | Phase 1 implementation | Already complete; just needs UI wiring |
| Separate notification plugin | `bp_notifications_add_notification()` | BuddyBoss Platform built-in | Direct API call, no plugin dependency |

**Deprecated/outdated:**
- `admin-ajax.php` for RSVP actions: Do not use. Phase 1 established REST; keep consistency.
- Storing waitlist in a separate `bp_event_waitlist` table: Unnecessary. `status` column approach is already in schema.

---

## Open Questions

1. **Optimistic UI during RSVP state transition**
   - What we know: Context marks this as Claude's discretion
   - What's unclear: Whether to show a spinner, immediately flip button state, or wait for API response
   - Recommendation: Disable button + show spinner on click; update state on API response. This is the most reliable pattern and avoids showing the wrong state if the API returns an error (e.g., group restriction denial).

2. **Google Calendar link placement**
   - What we know: Context marks this as Claude's discretion; current template has both iCal and Google Calendar links in the sidebar
   - What's unclear: Whether to keep both in sidebar or move them to the main column
   - Recommendation: Keep both in the existing `.bb-rl-event-single__calendar-links` sidebar section. The links are already there — no template restructuring needed.

3. **Attendee list avatar source**
   - What we know: `bp_core_fetch_avatar()` is the BuddyBoss-standard avatar function; `get_avatar()` works but may not return BuddyBoss-managed avatars
   - What's unclear: Whether BuddyBoss overrides the standard `get_avatar()` hook on this installation
   - Recommendation: Use `bp_core_fetch_avatar()` with `array( 'item_id' => $user_id, 'type' => 'thumb' )` for consistency with other BuddyBoss-rendered avatars.

4. **Organizer attendee removal endpoint**
   - What we know: The organizer panel should have remove controls (locked decision)
   - What's unclear: Whether the `DELETE /events/{id}/rsvp` route should accept a `user_id` param (organizer removing someone) or only remove the current user
   - Recommendation: Accept an optional `user_id` body param in the DELETE handler; organizer permission check (`$event->user_can_edit()`) grants access to remove any attendee; default to `bp_loggedin_user_id()` if `user_id` not provided (self-cancel).

---

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | PHPUnit (existing — `phpunit.xml.dist`) |
| Config file | `buddyboss-events/phpunit.xml.dist` |
| Quick run command | `cd /Users/tom/Local\ Sites/Events/buddyboss-events && vendor/bin/phpunit --filter test_rsvp` |
| Full suite command | `cd /Users/tom/Local\ Sites/Events/buddyboss-events && vendor/bin/phpunit` |

### Phase Requirements to Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| TKET-02 | RSVP creates registered attendee row | unit | `phpunit --filter test_rsvp_creates_registered_row` | ❌ Wave 0 |
| TKET-02 | RSVP at capacity creates waitlisted row | unit | `phpunit --filter test_rsvp_at_capacity_creates_waitlist_row` | ❌ Wave 0 |
| TKET-02 | Cancel RSVP removes attendee row | unit | `phpunit --filter test_cancel_rsvp_removes_row` | ❌ Wave 0 |
| TKET-04 | Non-member cannot RSVP to group-restricted event | unit | `phpunit --filter test_rsvp_group_restriction_blocks_non_member` | ❌ Wave 0 |
| TKET-04 | Group member can RSVP to group-restricted event | unit | `phpunit --filter test_rsvp_group_restriction_allows_member` | ❌ Wave 0 |
| ATTN-01 | Cancel RSVP triggers waitlist broadcast | unit | `phpunit --filter test_cancel_rsvp_triggers_waitlist_notification` | ❌ Wave 0 |
| ATTN-01 | Capacity increase triggers waitlist broadcast | unit | `phpunit --filter test_capacity_increase_triggers_waitlist_notification` | ❌ Wave 0 |
| ATTN-02 | iCal endpoint returns valid .ics content | unit | `phpunit --filter test_ical_endpoint_returns_valid_ics` | ❌ Wave 0 |

### Sampling Rate

- **Per task commit:** `phpunit --filter test_rsvp`
- **Per wave merge:** Full suite
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps

- [ ] `tests/phpunit/testcases/test-rsvp.php` — covers TKET-02 (registered/waitlisted rows, cancel)
- [ ] `tests/phpunit/testcases/test-rsvp-restrictions.php` — covers TKET-04 (group restriction)
- [ ] `tests/phpunit/testcases/test-waitlist.php` — covers ATTN-01 (notification broadcast triggers)
- [ ] `tests/phpunit/testcases/test-calendar-export.php` — covers ATTN-02 (iCal content validation)

---

## Sources

### Primary (HIGH confidence)

- Codebase direct read — `buddyboss-events/src/bp-events/bp-events-functions.php` — DB schema, CRUD patterns, meta API, notification format callback
- Codebase direct read — `buddyboss-events/src/bp-events/classes/class-bp-rest-events-endpoint.php` — existing routes, iCal/gcal implementations, REST patterns
- Codebase direct read — `buddyboss-events/src/bp-templates/.../single/home.php` — existing calendar links markup and RSVP placeholder
- Codebase direct read — `buddyboss-events/src/bp-events/assets/js/bp-events-create.js` — IIFE pattern, WP coding standards compliance
- [BuddyBoss bp_notifications_add_notification reference](https://www.buddyboss.com/resources/reference/functions/bp_notifications_add_notification/) — confirmed function signature and parameter list

### Secondary (MEDIUM confidence)

- `.planning/phases/02-payments-ticketing/02-CONTEXT.md` — locked decisions, existing code insights, meta key names
- `.planning/STATE.md` — accumulated Phase 1 decisions affecting Phase 2 (notification callback registration pattern, REST route registration pattern)

### Tertiary (LOW confidence)

- None — all claims verified against codebase or official BuddyBoss documentation.

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — verified against Phase 1 codebase; no new libraries
- Architecture: HIGH — patterns derived directly from Phase 1 code and locked CONTEXT.md decisions
- Pitfalls: HIGH — derived from schema constraints (UNIQUE KEY), API docs, and broadcast model analysis
- Notification API: HIGH — verified against official BuddyBoss documentation

**Research date:** 2026-03-14
**Valid until:** 2026-06-14 (stable WordPress/BuddyBoss APIs; 90-day window)
