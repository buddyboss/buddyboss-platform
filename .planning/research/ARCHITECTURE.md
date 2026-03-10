# Architecture Patterns

**Domain:** Commercial WordPress events plugin with BuddyBoss integration and Stripe Connect payments
**Researched:** 2026-03-10
**Confidence note:** Web search and WebFetch tools unavailable. All findings drawn from training knowledge of WordPress plugin architecture, Stripe Connect documentation, and BuddyBoss Platform extension patterns. Confidence levels assigned per domain.

---

## Recommended Architecture

The plugin is structured as a set of discrete modules that each own a vertical slice of functionality. A central Plugin Bootstrap wires them together via WordPress hooks. This mirrors how WooCommerce and The Events Calendar organize their codebases — it avoids god-class files and makes each module independently testable.

```
┌─────────────────────────────────────────────────────────┐
│                    Plugin Bootstrap                      │
│          (dependency injection, module loader)           │
└───────────────────┬─────────────────────────────────────┘
                    │ registers
        ┌───────────┼──────────────────────────┐
        ▼           ▼                          ▼
┌──────────────┐ ┌──────────────┐    ┌──────────────────┐
│   Event      │ │  Ticketing   │    │    Payments       │
│   Module     │ │  Module      │    │    Module         │
│              │ │              │    │  (Stripe Connect) │
│ CPT: event   │ │ CPT: ticket  │    │                   │
│ CPT: venue   │ │ Custom table │    │  Custom table:    │
│ Taxonomies   │ │  orders      │    │  stripe_accounts  │
└──────┬───────┘ └──────┬───────┘    └────────┬─────────┘
       │                │                     │
       └────────────────┼─────────────────────┘
                        │ feeds into
        ┌───────────────┼─────────────────────┐
        ▼               ▼                     ▼
┌──────────────┐ ┌──────────────┐    ┌──────────────────┐
│  BuddyBoss   │ │   Calendar   │    │  Admin Panel     │
│  Integration │ │   Views      │    │  Module          │
│  Module      │ │  Module      │    │                  │
│              │ │              │    │ Settings API     │
│ Activity     │ │ Site-wide    │    │ Permissions      │
│ Groups ext   │ │ Group cal    │    │ Commission rates │
│ Profile ext  │ │ REST feeds   │    │ Stripe config    │
└──────────────┘ └──────────────┘    └──────────────────┘
                        │
                ┌───────▼──────┐
                │  Webhook     │
                │  Handler     │
                │  (Stripe)    │
                └──────────────┘
```

---

## Component Boundaries

| Component | Responsibility | Communicates With | Owns |
|-----------|---------------|-------------------|------|
| **Plugin Bootstrap** | Loads modules, registers autoloader, checks BuddyBoss dependency | All modules | `buddyboss-events.php` main file |
| **Event Module** | CPT registration, event CRUD, recurring logic, venue/location, virtual link storage | Ticketing, BuddyBoss Integration, Calendar | CPT `bbevents_event`, CPT `bbevents_venue`, taxonomies |
| **Ticketing Module** | Ticket tier CRUD, availability tracking, order creation, attendee records | Event Module, Payments Module | CPT `bbevents_ticket_type`, custom table `bbevents_orders`, custom table `bbevents_attendees` |
| **Payments Module** | Stripe Connect OAuth flow, PaymentIntent creation, application fee calculation, refunds | Ticketing Module, Webhook Handler, Admin Panel | Custom table `bbevents_stripe_accounts`, options for platform keys |
| **BuddyBoss Integration** | Activity feed posts, group component extension, profile tab, member invites | Event Module, Ticketing Module | BuddyBoss component extension classes |
| **Calendar Views Module** | Site-wide calendar page, group calendar tab rendering, REST endpoint for event feeds | Event Module | Shortcode/block, REST route `/wp-json/bbevents/v1/events` |
| **Admin Panel Module** | Plugin settings page, permission matrix, commission rate config per plan tier, Stripe platform key entry | Payments Module, BuddyBoss Integration, Event Module | WP Settings API entries, options table |
| **Webhook Handler** | Receives Stripe webhook POSTs, verifies signature, routes events to Payments/Ticketing | Payments Module, Ticketing Module | REST route `/wp-json/bbevents/v1/stripe/webhook` |

---

## Data Flow

### Ticket Purchase Flow (Critical Path)

```
Buyer clicks "Buy Ticket"
        │
        ▼
[Frontend JS] POST to REST API
/wp-json/bbevents/v1/orders
        │
        ▼
[Ticketing Module]
- Validate ticket availability (row lock or transient lock)
- Create pending order record in bbevents_orders
- Calculate total + application fee amount
        │
        ▼
[Payments Module]
- Retrieve organizer's Stripe account ID from bbevents_stripe_accounts
- Create Stripe PaymentIntent with:
    amount: ticket_total
    application_fee_amount: calculated_fee (based on plan tier)
    transfer_data.destination: organizer_stripe_account_id
- Return client_secret to frontend
        │
        ▼
[Frontend Stripe.js]
- Confirm payment with client_secret
- Stripe collects card, charges buyer
        │
        ▼
[Stripe] — payment_intent.succeeded webhook fires
        │
        ▼
[Webhook Handler]
- Verify webhook signature (Stripe-Signature header)
- Route to Payments Module
        │
        ▼
[Payments Module]
- Confirm PaymentIntent status
- Notify Ticketing Module: order confirmed
        │
        ▼
[Ticketing Module]
- Update order status: pending → completed
- Generate attendee records
- Trigger do_action('bbevents_order_completed', $order_id)
        │
        ▼
[BuddyBoss Integration] (hooked to bbevents_order_completed)
- Post activity: "John bought a ticket to Summer Gala"
- Update member profile event attendance count
```

### Stripe Connect OAuth Flow (Organizer Onboarding)

```
Organizer clicks "Connect Stripe"
        │
        ▼
[Payments Module]
- Generate state token, store in transient
- Redirect to Stripe OAuth URL with:
    client_id: platform_client_id (from Admin Panel)
    redirect_uri: /wp-json/bbevents/v1/stripe/oauth/callback
        │
        ▼
[Stripe OAuth]
- Organizer authorizes
- Redirects back with code + state
        │
        ▼
[Payments Module OAuth Callback]
- Verify state token
- Exchange code for access_token + stripe_user_id
- Store stripe_user_id in bbevents_stripe_accounts keyed to WP user_id
```

### Commission Calculation

```
[Admin Panel] stores per-plan commission rates in wp_options:
  bbevents_commission_rates = {
    free: 5.0,
    pro: 3.5,
    plus: 2.0,
    ultimate: 1.0
  }

[Payments Module] at PaymentIntent creation:
  1. Look up site's BuddyBoss plan via filter: apply_filters('bbevents_site_plan', $plan)
  2. Retrieve rate for that plan
  3. application_fee_amount = floor(ticket_total * (rate / 100))
```

---

## Database Schema Approach

**Decision: Hybrid — Custom Post Types for content, Custom Tables for transactional records**

**Rationale:**

WordPress CPTs are appropriate for event and venue records: they get built-in post meta, taxonomies, WP_Query integration, REST API exposure, and revision history. The WP ecosystem (caching, search, REST) handles them well.

Custom tables are appropriate for orders, attendees, and Stripe account mappings: these are relational records with high query frequency, need JOINs, need precise row locking for ticket availability, and should never appear in post query loops. WooCommerce moved orders to custom tables in v7.1 for exactly these reasons (MEDIUM confidence — based on WooCommerce HPOS feature announcement).

### Custom Post Types

| CPT | Slug | Key Meta Fields |
|-----|------|-----------------|
| Event | `bbevents_event` | `_start_datetime`, `_end_datetime`, `_event_type` (in-person/virtual/recurring), `_venue_id`, `_virtual_url`, `_organizer_id`, `_group_id`, `_recurrence_rule` (iCal RRULE string), `_status` |
| Venue | `bbevents_venue` | `_address`, `_city`, `_country`, `_lat`, `_lng`, `_capacity` |
| Ticket Type | `bbevents_ticket_type` | `_event_id`, `_label` (Early Bird / VIP / General), `_price`, `_quantity`, `_quantity_sold`, `_sale_start`, `_sale_end` |

### Taxonomies

| Taxonomy | Slug | Applied To |
|----------|------|-----------|
| Event Category | `bbevents_category` | `bbevents_event` |
| Event Tag | `bbevents_tag` | `bbevents_event` |

### Custom Tables

**`{prefix}bbevents_orders`**
```sql
id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
event_id        BIGINT UNSIGNED NOT NULL  -- post ID
buyer_id        BIGINT UNSIGNED NOT NULL  -- WP user ID
status          ENUM('pending','completed','refunded','failed') NOT NULL DEFAULT 'pending'
subtotal        INT UNSIGNED NOT NULL  -- cents
application_fee INT UNSIGNED NOT NULL  -- cents
stripe_pi_id    VARCHAR(255) NOT NULL  -- pi_xxx
created_at      DATETIME NOT NULL
updated_at      DATETIME NOT NULL
PRIMARY KEY (id)
INDEX idx_event_id (event_id)
INDEX idx_buyer_id (buyer_id)
INDEX idx_stripe_pi_id (stripe_pi_id)
```

**`{prefix}bbevents_attendees`**
```sql
id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
order_id        BIGINT UNSIGNED NOT NULL
ticket_type_id  BIGINT UNSIGNED NOT NULL  -- post ID
user_id         BIGINT UNSIGNED  -- nullable (guest checkout future)
first_name      VARCHAR(255)
last_name       VARCHAR(255)
email           VARCHAR(255) NOT NULL
check_in_at     DATETIME
PRIMARY KEY (id)
INDEX idx_order_id (order_id)
INDEX idx_ticket_type_id (ticket_type_id)
INDEX idx_email (email)
```

**`{prefix}bbevents_stripe_accounts`**
```sql
id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
user_id             BIGINT UNSIGNED NOT NULL  -- WP user (organizer)
stripe_account_id   VARCHAR(255) NOT NULL     -- acct_xxx
access_token        TEXT NOT NULL             -- encrypted
livemode            TINYINT(1) NOT NULL DEFAULT 0
connected_at        DATETIME NOT NULL
PRIMARY KEY (id)
UNIQUE KEY idx_user_id (user_id)
```

**Ticket availability locking:** Use `UPDATE bbevents_ticket_types SET quantity_sold = quantity_sold + 1 WHERE id = ? AND quantity_sold < quantity` as an atomic SQL operation rather than PHP-level check-then-update. This prevents overselling under concurrent purchase load. (HIGH confidence — standard transactional pattern.)

---

## Patterns to Follow

### Pattern 1: BuddyBoss Component Extension

**What:** BuddyBoss exposes a `BP_Component` base class. Extending it registers your feature as a first-class BuddyBoss component with its own nav items, settings pages, and group extension hooks.

**When:** For the Groups integration (group events tab) and Member Profile tab.

**Example structure:**
```php
class BBEvents_Groups_Extension extends BP_Group_Extension {
    public function __construct() {
        parent::init( array(
            'slug'              => 'events',
            'name'              => __( 'Events', 'buddyboss-events' ),
            'nav_item_position' => 31,
            'screens'           => array(
                'edit' => array( 'enabled' => false ),
            ),
        ) );
    }

    public function display( $group_id = null ) {
        // Render group calendar view
    }
}
bp_register_group_extension( 'BBEvents_Groups_Extension' );
```

(MEDIUM confidence — based on BuddyBoss/BuddyPress documented `BP_Group_Extension` API. Verify exact constructor args against current BuddyBoss docs.)

### Pattern 2: Activity Feed Integration

**What:** Use `bp_activity_add()` to post activity items when key events occur.

**When:** Event created, RSVP submitted, ticket purchased.

**Example:**
```php
add_action( 'bbevents_order_completed', function( $order_id ) {
    $order = bbevents_get_order( $order_id );
    bp_activity_add( array(
        'user_id'           => $order->buyer_id,
        'component'         => 'bbevents',
        'type'              => 'bbevents_ticket_purchased',
        'action'            => sprintf(
            /* translators: %1$s user, %2$s event */
            __( '%1$s purchased a ticket to %2$s', 'buddyboss-events' ),
            bp_core_get_userlink( $order->buyer_id ),
            '<a href="' . get_permalink( $order->event_id ) . '">' . get_the_title( $order->event_id ) . '</a>'
        ),
        'item_id'           => $order->event_id,
        'secondary_item_id' => $order_id,
        'hide_sitewide'     => false,
    ) );
} );
```

(MEDIUM confidence — `bp_activity_add()` is documented BuddyPress/BuddyBoss API.)

### Pattern 3: Stripe Webhook Idempotency

**What:** Always check if a webhook event has already been processed before acting on it.

**When:** Every Stripe webhook handler.

**Example:**
```php
function handle_payment_intent_succeeded( \Stripe\Event $event ) {
    $pi_id = $event->data->object->id;

    // Check if already processed
    $order = bbevents_get_order_by_stripe_pi( $pi_id );
    if ( ! $order || $order->status === 'completed' ) {
        return; // Already handled or no matching order
    }

    bbevents_complete_order( $order->id );
}
```

(HIGH confidence — Stripe documentation explicitly requires idempotent webhook handling.)

### Pattern 4: Recurring Events as Parent/Child Posts

**What:** A recurring event series is stored as one parent CPT post (holds RRULE). Individual occurrences are generated as child posts (post_parent set) on-demand or via cron, up to a horizon (e.g., 6 months ahead). This is how The Events Calendar handles series.

**When:** Any recurring event type.

**Trade-off:** Generating child posts enables standard WP_Query, search, and REST API to work naturally on individual occurrences. The alternative (calculating occurrences dynamically from RRULE at query time) is complex and slow. (MEDIUM confidence — based on The Events Calendar's documented architecture and common WP patterns.)

### Pattern 5: REST API for Frontend Interactions

**What:** Use WP REST API for all frontend interactions (ticket purchase, RSVP, calendar feed, event listing). Avoid `admin-ajax.php` for new code.

**When:** All AJAX-style interactions.

**Rationale:** REST API provides proper HTTP semantics, authentication via nonces or Application Passwords, caching headers, and is the modern WP standard. `admin-ajax.php` is legacy and slower (loads full WP admin). (HIGH confidence — WordPress core guidance.)

---

## Anti-Patterns to Avoid

### Anti-Pattern 1: PHP-Level Ticket Availability Check

**What:** Read `quantity_sold`, check in PHP, then write incremented value in a separate query.

**Why bad:** Race condition under concurrent purchases. Two buyers can read the same `quantity_sold` value before either writes, resulting in overselling.

**Instead:** Use atomic SQL `UPDATE ... WHERE quantity_sold < quantity` with row-level locking and check affected rows === 1.

### Anti-Pattern 2: Storing Stripe Secret Keys in Post Meta or User Meta

**What:** Saving `sk_live_xxx` or access tokens in wp_postmeta or wp_usermeta.

**Why bad:** Post meta is exposed through REST API by default, logged in debug output, and included in export tools. Secret keys require encryption at rest and controlled access.

**Instead:** Store encrypted in a dedicated custom table (`bbevents_stripe_accounts`). Use `sodium_crypto_secretbox()` or a WP-compatible encryption library. Never log or expose access tokens.

### Anti-Pattern 3: Registering BuddyBoss Hooks Before `bp_loaded`

**What:** Calling `bp_register_group_extension()` or `bp_activity_add()` before BuddyBoss has finished loading.

**Why bad:** BuddyBoss component system is not initialized until `bp_loaded` fires. Early calls silently fail or cause fatal errors.

**Instead:** Wrap all BuddyBoss integration in `add_action('bp_loaded', ...)`.

### Anti-Pattern 4: Using WP Cron for Time-Critical Payment Confirmation

**What:** Relying solely on WP Cron to poll Stripe for payment status rather than webhook-driven confirmation.

**Why bad:** WP Cron only fires on page load — on low-traffic sites, a buyer could wait minutes for order confirmation. WP Cron is not real-time.

**Instead:** Webhooks are the primary confirmation path. WP Cron is a fallback reconciliation job only (e.g., find orders stuck in "pending" for >1 hour and recheck via Stripe API).

### Anti-Pattern 5: Blocking wp-admin During Stripe API Calls

**What:** Making synchronous Stripe API calls (e.g., creating a PaymentIntent) inside a WP admin page load.

**Why bad:** Network latency or Stripe outages block the admin UI. Stripe API calls should be user-triggered and async.

**Instead:** PaymentIntent creation happens via REST API call from the buyer's browser at purchase time, not during admin page renders.

---

## Build Order (Suggested)

Dependencies flow upward in this list — each layer requires the layer above it to be solid before building.

```
Phase 1: Foundation
├── Plugin Bootstrap (autoloader, dependency checks, activation hooks)
├── Database Schema (register custom tables on activation via dbDelta)
├── Admin Panel Module (Stripe platform keys, commission rates, permissions)
└── Event Module (CPT registration, basic CRUD, venue CPT)

Phase 2: Payments Core
├── Payments Module — Stripe Connect OAuth (organizer onboarding)
├── Payments Module — PaymentIntent creation with application fees
└── Webhook Handler (signature verification, routing scaffold)

Phase 3: Ticketing
├── Ticketing Module — Ticket Type CPT (tiers, pricing, availability)
├── Ticketing Module — Order creation (REST endpoint)
├── Ticketing Module — Order completion (hooked to webhook)
└── Attendee record generation

Phase 4: BuddyBoss Integration
├── Activity feed posts (event created, ticket purchased, RSVP)
├── Group Extension (group events tab, group calendar)
└── Member Profile tab (events attended, events hosted)

Phase 5: Calendar & Discovery Views
├── Site-wide calendar (shortcode/block + REST feed endpoint)
├── Group calendar (embedded in group extension)
└── Event detail page templates

Phase 6: Recurring Events
├── RRULE storage and parsing
├── Child post generation (cron + on-demand)
└── Recurring event UI (create/edit series vs single occurrence)
```

**Rationale for order:**
- Phase 1 must come first: database schema migrations are painful to retrofit; CPTs must be registered before any content is created.
- Phase 2 before Phase 3: the payment flow is the riskiest component (external API, OAuth, webhooks). Build and validate it with a single ticket type before building the full ticketing UI.
- Phase 3 before Phase 4: activity feed posts require completed orders to exist. BuddyBoss integration hooks into events/orders that must already work.
- Phase 5 is a read layer — it queries what was built in phases 1-4 and requires no new data structures.
- Phase 6 is deliberately last: recurring logic adds significant complexity (timezone handling, exception dates, series editing) and should not block delivery of the core product.

---

## WordPress API Approach

| Concern | Recommendation | Rationale |
|---------|---------------|-----------|
| Frontend data interactions | WP REST API (`/wp-json/bbevents/v1/`) | Modern standard, proper HTTP semantics, cacheable, authentication via nonces |
| Stripe webhook receiver | WP REST API route (`/wp-json/bbevents/v1/stripe/webhook`) | Accessible without WP cookie auth; can disable nonce check for Stripe's server-to-server POST; verify via Stripe signature instead |
| Admin settings UI | WP Settings API + admin menu pages | Standard WP pattern; React-based settings page (wp-scripts) for complex commission matrix |
| Event creation form | Custom block (Block Editor) or classic metabox fallback | Block editor is the current standard; metabox fallback for hosts still on classic editor |
| Calendar frontend | Vanilla JS or lightweight library (e.g., FullCalendar) consuming REST feed | Avoid heavy frameworks as a plugin dependency; FullCalendar is the industry standard for WordPress event calendar UIs (MEDIUM confidence) |

---

## Scalability Considerations

| Concern | At 100 users | At 10K users | At 1M users |
|---------|--------------|--------------|-------------|
| Ticket availability | SQL atomic update sufficient | Same | Consider Redis locks, queue-based reservation |
| Event queries | WP_Query with meta_query | Add custom table index, consider object cache | Dedicated events query layer, read replicas |
| Activity feed writes | Direct `bp_activity_add()` | Same | Queue activity writes via async job |
| Stripe webhooks | Synchronous handler fine | Add idempotency table, fast return 200 | Queue webhook processing, async confirmation |
| Recurring event generation | On-demand + cron | Same | Pre-generate all occurrences within rolling window |

---

## BuddyBoss-Specific Integration Points

| Hook / API | Purpose | Confidence |
|------------|---------|------------|
| `bp_loaded` action | Initialize all BuddyBoss integrations | HIGH |
| `BP_Group_Extension` class | Register group events tab | MEDIUM — verify constructor args against current BuddyBoss version |
| `bp_register_group_extension()` | Register the group extension | MEDIUM |
| `bp_activity_add()` | Post to activity feeds | MEDIUM |
| `bp_activity_action_types` filter | Register custom activity types | MEDIUM |
| `bp_get_displayed_user_id()` | Get user ID on profile pages | MEDIUM |
| `bp_core_add_nav_item()` / `BP_Component` | Add profile tab | MEDIUM |
| `apply_filters('bbevents_site_plan', $plan)` | Internal filter to retrieve site's BuddyBoss plan tier | Plugin-defined — requires BuddyBoss to expose plan data or admin-configured setting |

**Note:** BuddyBoss extends BuddyPress but adds its own layer. API compatibility should be verified against the BuddyBoss Platform plugin's developer docs before implementation, not just BuddyPress core docs. The BuddyBoss Platform maintains its own hooks reference. (MEDIUM confidence overall.)

---

## Sources

- WordPress Plugin Developer Handbook — CPT registration, Settings API, REST API (training knowledge, HIGH confidence for established patterns)
- Stripe Connect documentation — application fees, PaymentIntent with `transfer_data`, OAuth flow (training knowledge, HIGH confidence for core payment flow)
- WooCommerce HPOS migration — custom tables for orders rationale (training knowledge, MEDIUM confidence)
- The Events Calendar plugin architecture — recurring events parent/child pattern, FullCalendar usage (training knowledge, MEDIUM confidence)
- BuddyPress/BuddyBoss `BP_Group_Extension`, `bp_activity_add()` API (training knowledge, MEDIUM confidence — verify against current BuddyBoss Platform developer reference)
- GiveWP / Stripe WordPress plugin patterns — webhook idempotency, secret key handling (training knowledge, HIGH confidence for pattern)

**Verification needed before implementation:**
- Current BuddyBoss Platform hook reference: https://developer.buddyboss.com/reference/
- Stripe Connect application fees current API: https://stripe.com/docs/connect/charges-application-fees
- BuddyBoss Platform current version changelog for any `BP_Group_Extension` API changes
