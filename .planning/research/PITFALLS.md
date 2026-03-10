# Domain Pitfalls

**Domain:** Commercial WordPress events plugin with BuddyBoss integration, Stripe Connect, and multi-tier ticketing
**Researched:** 2026-03-10
**Confidence note:** Web research tools unavailable in this session. All findings draw on training knowledge (cutoff August 2025). Stripe Connect and BuddyBoss items marked HIGH confidence reflect stable, well-documented behavior. Items marked MEDIUM require verification against current official docs before implementation.

---

## Critical Pitfalls

Mistakes that cause rewrites, financial liability, or major compliance issues.

---

### Pitfall 1: Application Fee Is Not Automatically Refunded on Dispute or Partial Refund

**What goes wrong:** When a connected account receives a dispute (chargeback) or when you issue a partial refund, Stripe does NOT automatically reverse the application fee proportionally. The platform keeps the fee while the organizer absorbs the full loss. This creates an accounting mismatch, potential organizer hostility, and contractual liability.

**Why it happens:** Application fees are a separate object on the Stripe API from the underlying charge. Refunding the charge does not refund the fee unless you explicitly call `refund` on the `ApplicationFee` object as well.

**Consequences:**
- Organizer is debited the full ticket amount but BuddyBoss still holds the commission — negative net payout for organizer
- Dispute resolution logic becomes a support nightmare if not automated
- If organizer's Stripe account goes negative, payouts halt and ticket buyers can't be refunded

**Prevention:**
- Build a `charge.dispute.created` webhook handler from day one that automatically triggers a proportional application fee reversal via `stripe.applicationFees.createRefund(feeId, { amount: proportionalAmount })`
- Build a `charge.refunded` webhook that checks `refund.amount` vs `charge.amount` and reverses the proportional fee on partial refunds
- Store the `application_fee` ID on every order record in WP so refund logic can look it up
- Write integration tests that cover full refund, partial refund, and dispute scenarios against Stripe test mode

**Detection (warning signs):**
- Organizers report negative Stripe balances after chargebacks
- Accounting shows application fee revenue that doesn't net-settle correctly

**Phase:** Address in the Stripe Connect payments phase, before any real transactions.

**Confidence:** HIGH — this is documented Stripe behavior, not speculation.

---

### Pitfall 2: Stripe Connect Onboarding Abandonment Leaves Organizers in a Broken State

**What goes wrong:** Stripe Connect OAuth or hosted onboarding flows can be abandoned mid-way. If the plugin doesn't detect and handle the incomplete-account state, organizers who started onboarding but didn't finish can attempt to create paid events, causing silent failures or confusing error messages at checkout.

**Why it happens:** Stripe accounts go through states: `details_submitted: false`, `charges_enabled: false`, `payouts_enabled: false`. A partially onboarded account can exist in your DB with a `stripe_account_id` but be unable to accept charges.

**Consequences:**
- Ticket purchase attempts fail at the Stripe API level with no meaningful user-facing error
- Organizer and buyer both confused; trust eroded
- Revenue lost for events with live tickets but broken payment configuration

**Prevention:**
- On every event-creation or ticket-purchase attempt, check `stripe.accounts.retrieve(accountId)` and verify `charges_enabled: true`
- Show a persistent organizer dashboard notice: "Payment setup incomplete — buyers cannot purchase tickets"
- Gate ticket display on the front-end: if account not charges-enabled, hide purchase CTA and show "organizer hasn't set up payments yet"
- Cache the account status with a short TTL (5 minutes) — don't call Stripe on every page load

**Detection:** `charges_enabled: false` on the retrieved account object.

**Phase:** Stripe Connect onboarding phase.

**Confidence:** HIGH

---

### Pitfall 3: Recurring Event Data Model Treated as Simple Copies

**What goes wrong:** Recurring events are modeled as N independent post copies at creation time rather than as a parent-child relationship with a recurrence rule. This makes "edit all future occurrences" impossible without custom rebuild logic, causes calendar query performance to degrade with hundreds of copies, and makes exception handling (cancel one occurrence, reschedule one date) awkward.

**Why it happens:** The temptation is to use `wp_insert_post()` in a loop at save time — it's quick to implement. The correct model (RFC 5545 RRULE stored on a parent, occurrences generated on read or generated lazily) requires upfront schema design.

**Consequences:**
- "Edit all future occurrences" requires a rewrite — can't be patched in
- DB row explosion: a weekly event over one year = 52 posts; daily over a year = 365 posts, each with their own postmeta
- Calendar range queries (`WHERE post_date BETWEEN x AND y`) become slow without proper indexing
- Deleting/cancelling one occurrence without affecting others is brittle when each is an independent post

**Prevention:**
- Define the recurrence data model explicitly before writing a line of code: parent event post + recurrence rule (stored as structured postmeta: frequency, interval, count/until, exceptions array) + occurrence posts generated lazily or on a schedule
- Support "edit this occurrence", "edit this and all future", "edit all" as first-class operations with explicit DB semantics
- Index `event_start_date` and `event_parent_id` from day one
- Look at how The Events Calendar (TEC) handles RRULE storage as a reference — it's the established pattern in the WP ecosystem

**Detection (warning signs):**
- Schema discussion devolves to "just copy the post N times"
- No mention of exception dates or "edit future occurrences" in the data model design

**Phase:** Data model / schema design phase — this is the highest-leverage architectural decision and cannot be fixed later without a migration.

**Confidence:** HIGH

---

### Pitfall 4: BuddyBoss Hooks and Functions Assumed Stable Across Versions

**What goes wrong:** BuddyBoss (the platform plugin) has a history of renaming or removing hooks and functions between minor versions without marking them deprecated in advance. A plugin that hooks into `buddyboss_*` or `bp_*` actions can silently break when site admins update BuddyBoss without updating the events plugin.

**Why it happens:** BuddyBoss is a fork of BuddyPress with a faster release cadence and less strict backward-compatibility policy than core WordPress. Integration hooks that worked in 2.0 may not exist in 2.3.

**Consequences:**
- Group calendar tab disappears silently
- Activity feed posting fails silently — no PHP error, just no feed items
- Member profile events section blank
- Support burden increases significantly; difficult to diagnose remotely

**Prevention:**
- Wrap every BuddyBoss hook registration in an `function_exists()` or `has_action()` guard with a graceful fallback
- Maintain a minimum tested BuddyBoss version in the plugin header and test against it in CI
- Add a dashboard admin notice if the installed BuddyBoss version falls below the tested minimum
- Maintain a dedicated "BuddyBoss compatibility" changelog section and test against every BuddyBoss minor release
- Subscribe to the BuddyBoss changelog and developer announcements; treat each release as a potential compatibility event

**Detection (warning signs):**
- Integration features work in dev but fail on customer sites running slightly different BuddyBoss versions
- No BuddyBoss version check in plugin activation

**Phase:** All phases involving BuddyBoss integration. Version gate should be added in the plugin scaffolding phase (Phase 1).

**Confidence:** MEDIUM — based on patterns with BuddyPress forks generally; verify against BuddyBoss's actual versioning policy.

---

### Pitfall 5: Race Condition on Ticket Inventory Allows Overselling

**What goes wrong:** Two concurrent ticket purchases read available capacity simultaneously, both see "1 ticket remaining", both proceed, and both succeed — selling 2 tickets for 1 available slot.

**Why it happens:** WordPress's default `get_post_meta()` / `update_post_meta()` pattern is not atomic. Reading and updating capacity in two separate DB operations creates a classic TOCTOU (time-of-check time-of-use) race condition. The problem is invisible in development (single user) and only surfaces under real traffic or load tests.

**Consequences:**
- Oversold events: more ticket holders than physical/virtual seats
- Angry attendees, refunds, organizer credibility damage
- PCI and booking liability issues if the event has a hard capacity

**Prevention:**
- Use MySQL's atomic `UPDATE ... WHERE remaining_capacity > 0` pattern and check affected rows — never read-then-write
- Alternatively, use a WP transient-based optimistic lock: `add_transient("booking_lock_{$event_id}", true, 10)` returns false if lock exists
- Most robust: implement a custom DB table for ticket inventory with a `SELECT ... FOR UPDATE` approach inside a transaction (requires direct `$wpdb` usage with explicit transaction control)
- Consider using WooCommerce stock management if WC is available — it already solves this problem with MySQL-level atomic decrements
- Load test the checkout flow before launch with concurrent requests (wrk, k6, or even ApacheBench)

**Detection (warning signs):**
- Capacity stored as postmeta updated via PHP read-modify-write cycle
- No mention of locking or atomicity in the checkout implementation

**Phase:** Ticketing/checkout implementation phase.

**Confidence:** HIGH — this is a fundamental database concurrency pattern, not plugin-specific.

---

### Pitfall 6: PCI Compliance Scope Misunderstood — Stripe.js / Payment Element Not Enforced

**What goes wrong:** Developers add card fields as plain HTML inputs (or use a non-Stripe-hosted checkout flow) without understanding that card data must never touch the WordPress server. Even temporarily logging a card number in a debug statement creates full PCI DSS Level 1 audit scope.

**Why it happens:** Stripe Connect can be integrated in multiple ways. The "easy" path (passing raw card data to your server first) creates massive PCI liability. Stripe's hosted Payment Element or Checkout eliminates this risk, but requires deliberately choosing and enforcing it.

**Consequences:**
- PCI DSS Level 1 compliance requirement if card data touches the server — annual QSA audit, penetration testing, quarterly scans
- Catastrophic liability if a breach occurs
- Stripe's ToS violation if card data is proxied through the platform server

**Prevention:**
- Enforce Stripe.js / Stripe Payment Element exclusively — no custom card input fields
- Payment Element renders in an iframe hosted by Stripe; no card data ever reaches WordPress
- Use `payment_intent` + `confirmPayment()` client-side flow for Connected accounts
- Document this architectural constraint in the developer spec before any payment code is written
- Add a code review checklist item: "Does any PHP code receive card numbers, CVVs, or expiry dates? If yes, reject."

**Detection (warning signs):**
- Any PHP that receives `card_number`, `cvv`, or `expiry` POST parameters
- WP AJAX handler that processes card data server-side

**Phase:** Architecture/spec phase (before any code), enforced in payment implementation phase.

**Confidence:** HIGH — Stripe's documentation on PCI scope reduction is explicit.

---

## Moderate Pitfalls

---

### Pitfall 7: Plugin Activation / Deactivation / Uninstall Hooks Done Wrong

**What goes wrong:** Custom DB tables, scheduled events, and options are created on `register_activation_hook`, but the hook fires only when a user clicks "Activate" in the WP admin — not on plugin updates. This means DB schema changes in updates are never applied. Conversely, uninstall hooks that delete all data are too aggressive and destroy organizer event data when an admin deactivates/reactivates to troubleshoot.

**Why it happens:** Misunderstanding of WP plugin lifecycle. `register_activation_hook` ≠ "runs on every update". Updates run `upgrader_process_complete` or the plugin's own `init` hook.

**Consequences:**
- DB schema drift between plugin versions — queries against new columns fail silently
- Organizers lose all event data if `uninstall.php` deletes tables unconditionally
- Scheduled `wp_cron` jobs orphaned after deactivation pollute the cron table

**Prevention:**
- Store a `buddyboss_events_db_version` option; on `plugins_loaded`, compare to current schema version and run `dbDelta()` migrations if outdated — this handles both activation and update scenarios
- `register_deactivation_hook`: clear scheduled cron events only (not data)
- `uninstall.php`: delete data only if a "Remove all data on uninstall" option is explicitly enabled by the admin (default: OFF)
- Use `dbDelta()` for all table creation/alteration — it is idempotent and safe to call repeatedly

**Detection (warning signs):**
- All DB table creation only in `register_activation_hook`
- `uninstall.php` drops tables unconditionally

**Phase:** Plugin scaffolding phase (Phase 1).

**Confidence:** HIGH — standard WP plugin development pattern.

---

### Pitfall 8: Timezone Handling Assumes Server or WordPress Timezone

**What goes wrong:** Event start/end times stored in server timezone (or WP `date_default_timezone_get()`) display incorrectly for attendees in other timezones. Worse, recurring event boundary calculations (e.g., "repeat every Monday at 9am") drift across DST transitions if not anchored to the event's declared timezone.

**Why it happens:** PHP datetime handling is notoriously easy to get wrong. WordPress stores `post_date` in local time and `post_date_gmt` in UTC, but custom event date fields often default to whatever `date()` returns — which depends on server config.

**Consequences:**
- Event shows at wrong time for attendees; especially damaging for virtual events with international attendees
- Recurring events scheduled for "9am Monday" shift by 1 hour after DST transition

**Prevention:**
- Store ALL event datetimes in UTC in the database
- Store the event's declared timezone separately (e.g., `America/New_York`) as a string
- Display times by converting from UTC to the viewer's timezone client-side (using Intl.DateTimeFormat or a lightweight library like day.js with timezone plugin), or to the event's declared timezone for the organizer
- For recurring events, generate occurrence datetimes using PHP's `DateTimeImmutable` with the event timezone, then store the UTC equivalent
- Never use `date()` — always use `DateTime` / `DateTimeImmutable` with explicit timezone

**Detection (warning signs):**
- Event times stored as formatted strings (`"2026-03-15 09:00:00"`) without a timezone column
- `date('Y-m-d H:i:s')` calls in event creation code

**Phase:** Data model phase.

**Confidence:** HIGH

---

### Pitfall 9: WooCommerce Conflict — Incompatible Checkout Flows and Template Overrides

**What goes wrong:** Many BuddyBoss sites already have WooCommerce installed for membership or merchandise sales. The events plugin's ticket checkout flow can conflict with WC in multiple ways: WC's `woocommerce_checkout` shortcode captures the `/checkout` URL slug; WC template overrides in themes can affect event confirmation pages; WC's `wp_redirect` on order completion can hijack the post-payment flow.

**Why it happens:** WooCommerce aggressively hooks into WP's request lifecycle (query vars, template loading, URL rewriting). A custom checkout that also manipulates these will collide.

**Consequences:**
- "Thank you" page after ticket purchase routes to WC order confirmation instead of event confirmation
- CSS conflicts between WC payment form styling and event plugin styles
- WC session/cart cookie conflicts if the event plugin also uses WP sessions

**Prevention:**
- Use unique URL slugs for event checkout — never `/checkout`, `/cart`, or `/order-received` (WC owns these)
- Namespace all query vars: `buddyboss_event_checkout` not `checkout`
- Test on a site with WooCommerce + WC Memberships + WC Subscriptions installed — this is the most common BuddyBoss stack
- Consider building the ticket checkout ON TOP of WooCommerce as a product type — this resolves all conflicts by delegating payment processing to WC, but adds WC as a hard dependency (evaluate this tradeoff explicitly)
- If not using WC: add explicit compatibility declarations using `woocommerce_checkout_process` guards to bail early when in WC context

**Detection (warning signs):**
- Any use of `/checkout` as a URL slug
- No WooCommerce installed in development environment

**Phase:** Checkout/payment implementation phase.

**Confidence:** MEDIUM — specific conflict patterns vary by WC version; test against current WC.

---

### Pitfall 10: Email Deliverability — Ticket Confirmations Sent via wp_mail with Default Config

**What goes wrong:** WordPress `wp_mail()` uses PHP's `mail()` function by default, which sends from the server's IP without SPF, DKIM, or DMARC alignment. Ticket confirmation emails hit spam folders or are silently dropped. For paid events, this is a support emergency — buyers think their purchase failed.

**Why it happens:** `wp_mail()` is fine for low-volume admin notifications but not for transactional email at scale. Most WP hosting setups don't configure outbound SMTP authentication.

**Consequences:**
- Ticket confirmation emails marked spam or dropped — buyer has no proof of purchase
- Duplicate purchase attempts from confused buyers (double charges)
- Organizer and site admin flooded with "I didn't get my ticket" support requests

**Prevention:**
- Document the requirement for a transactional email provider in the plugin's system requirements (Postmark, SendGrid, Mailgun, or Amazon SES)
- Provide a settings field for SMTP configuration, or recommend a WP SMTP plugin (WP Mail SMTP by WPForms is the standard recommendation)
- Add a "send test email" button in the admin settings so admins can verify deliverability before going live
- Use HTML email templates with plain-text fallback; include a unique booking reference number in the subject line
- Queue confirmation emails via `wp_schedule_single_event()` with a retry mechanism rather than sending synchronously in the checkout request — prevents checkout timeout if mail is slow

**Detection (warning signs):**
- Direct `wp_mail()` calls in checkout with no SMTP documentation
- No test email feature in admin settings

**Phase:** Email/notification implementation phase.

**Confidence:** HIGH — well-documented WP ecosystem problem.

---

### Pitfall 11: Tiered Commission Rate Applied at Wrong Point in Transaction

**What goes wrong:** The application fee percentage is looked up from the organizer's BuddyBoss plan tier at event-creation time (or worse, at purchase time from a site option). If the site admin changes the commission rate or the organizer changes plans between ticket creation and purchase, buyers pay under the wrong commission structure. Worse: if the rate is applied after the PaymentIntent is confirmed, it can't be changed — Stripe captures the PaymentIntent with the fee amount baked in.

**Why it happens:** Commission rate is dynamic (per-plan, admin-configurable), but PaymentIntent application fee amount is set at intent creation and cannot be modified post-confirmation.

**Consequences:**
- BuddyBoss under-collects commission on old tickets sold under a changed rate
- Attempting to retroactively adjust fees requires voiding and reissuing — complex, disruptive

**Prevention:**
- Lock the commission rate at ticket purchase time (not event creation time) and store the captured rate on the order record
- Create the PaymentIntent with the calculated `application_fee_amount` at checkout initiation — not before
- Log the plan tier and rate used for each transaction for auditing
- Consider whether commission rate changes should be effective immediately or only for new events (a product/policy decision that needs answering before the data model is built)

**Detection (warning signs):**
- Commission rate fetched from `get_option()` at checkout without being stored on the order
- PaymentIntent created at event creation rather than at checkout

**Phase:** Stripe Connect / commission implementation phase.

**Confidence:** HIGH — Stripe PaymentIntent behavior is well-documented.

---

## Minor Pitfalls

---

### Pitfall 12: REST API Endpoints Not Namespaced, Collide with Future Core or Plugin Routes

**What goes wrong:** Event API endpoints registered as `/wp-json/events/...` collide with other plugins or future WP core additions.

**Prevention:** Use a unique namespace: `/wp-json/buddyboss-events/v1/...`. Include version number from day one.

**Phase:** API design / scaffolding phase.

**Confidence:** HIGH

---

### Pitfall 13: Capacity Check Exposed in REST API Response Without Auth Check

**What goes wrong:** An unauthenticated REST call returns remaining ticket capacity, allowing competitors or bad actors to monitor event inventory.

**Prevention:** Gate detailed inventory data (specific remaining count) behind authentication; public-facing only needs "available / sold out". Add `permission_callback` to all REST routes — never use `'__return_true'` on routes that return sensitive event or financial data.

**Phase:** API implementation phase.

**Confidence:** HIGH

---

### Pitfall 14: BuddyBoss Activity Feed Spam on Bulk Operations

**What goes wrong:** When an organizer creates a recurring event with 52 occurrences, 52 activity feed items are posted simultaneously — flooding group and site-wide feeds.

**Prevention:** Post a single activity item for the parent recurring event, not per-occurrence. Add a filter `buddyboss_events_post_activity` so site admins can control when activity items are generated.

**Phase:** Recurring events + BuddyBoss integration phase.

**Confidence:** HIGH — activity feed flooding is a classic BuddyPress/BuddyBoss integration mistake.

---

### Pitfall 15: No Idempotency Keys on Stripe API Calls

**What goes wrong:** Network timeout during PaymentIntent creation causes the PHP code to retry, creating duplicate charges. Buyer is charged twice.

**Prevention:** Pass a unique `idempotencyKey` on every Stripe API mutation. The key should be derived from the order's internal ID so retries hit the cached result. Stripe returns the same response for duplicate requests with the same key within 24 hours.

**Phase:** Stripe integration phase.

**Confidence:** HIGH — Stripe's own documentation calls this out as a required best practice.

---

## Phase-Specific Warnings

| Phase Topic | Likely Pitfall | Mitigation |
|-------------|---------------|------------|
| Plugin scaffolding | Activation hook only (no update migration path) | Use `plugins_loaded` version check + `dbDelta()` |
| Data model design | Recurring events as flat copies | Parent + RRULE + lazy generation from day one |
| Data model design | Datetimes in local/server timezone | Store UTC + timezone name column |
| Stripe Connect onboarding | Abandoned onboarding leaves broken organizer state | Check `charges_enabled` before allowing paid events |
| Stripe payments | Application fee not reversed on refund/dispute | Webhook handlers for `charge.refunded` and `charge.dispute.created` |
| Stripe payments | No idempotency keys | Add keys to all Stripe mutations |
| Stripe payments | Commission rate sourced at wrong time | Lock rate at PaymentIntent creation, store on order |
| Ticketing / checkout | Read-modify-write capacity check | Atomic MySQL update with row check |
| Ticketing / checkout | WooCommerce URL and hook conflicts | Unique slugs; test with WC installed |
| Payment UI | PCI scope via custom card inputs | Stripe Payment Element only — no raw card fields in PHP |
| Email / notifications | wp_mail deliverability | Require SMTP; queue emails; test button in admin |
| BuddyBoss integration | Hook / function names change across versions | Version guard in activation; `function_exists()` wrappers |
| BuddyBoss integration | Activity feed flooding on bulk operations | Single activity item per parent event |
| REST API | Namespace collision | `/wp-json/buddyboss-events/v1/` from day one |

---

## Sources

- Stripe Connect application fees documentation (docs.stripe.com/connect/direct-charges#collect-fees) — HIGH confidence, verify against current Stripe docs
- Stripe idempotency documentation (docs.stripe.com/api/idempotent_requests) — HIGH confidence
- WordPress Plugin Developer Handbook — activation/deactivation/uninstall hooks — HIGH confidence
- BuddyPress/BuddyBoss hook patterns — MEDIUM confidence (verify BuddyBoss-specific hooks against current BuddyBoss developer docs)
- WooCommerce checkout conflict patterns — MEDIUM confidence (verify against current WC version)
- PCI DSS SAQ A scope reduction via Stripe.js — HIGH confidence (Stripe's own PCI guide confirms this)
- RFC 5545 (iCalendar RRULE) for recurring event modeling — HIGH confidence

*Note: All sources are from training data (cutoff August 2025). No live web verification was possible in this session. Flag all MEDIUM-confidence items for verification before implementation begins.*
