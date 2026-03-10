# Project Research Summary

**Project:** BuddyBoss Events Plugin
**Domain:** Commercial WordPress events plugin with BuddyBoss integration, Stripe Connect payments, and multi-tier ticketing
**Researched:** 2026-03-10
**Confidence:** MEDIUM (web research tools unavailable; all findings from training data, knowledge cutoff August 2025)

## Executive Summary

This is a commercial WordPress plugin that adds a first-class events system — with paid ticketing, Stripe Connect commissions, and deep BuddyBoss community integration — to BuddyBoss-powered community sites. No existing WordPress events plugin covers this combination: every competitor (The Events Calendar, Modern Events Calendar, Eventin, EventON) provides general-purpose event management but none integrates with BuddyBoss group/profile/activity systems, and none implements a platform-level commission model on ticket sales. The recommended approach is a modular OOP plugin using PSR-4 autoloading, with a hybrid data model (CPTs for event content, custom tables for transactional records), Stripe Connect destination charges for payment flow, and FullCalendar for the calendar UI.

The highest-value investment is getting three architectural decisions right before writing feature code: (1) storing all event datetimes in UTC with a separate timezone column, (2) modeling recurring events as a parent post with an RRULE and lazily-generated child occurrences rather than flat copies, and (3) using atomic SQL updates for ticket inventory to prevent overselling. These decisions cannot be migrated cheaply once data exists. The payment architecture — Stripe destination charges with application fees, webhook-driven order confirmation, and Stripe Payment Element exclusively (no card data touching the server) — is well-established and well-documented; follow it exactly.

The main strategic risk is market positioning: a platform commission on ticket sales is genuinely novel in WordPress (Eventbrite-style) and organizers conditioned by plugins that take 0% may resist. Mitigate this by ensuring the deep BuddyBoss integration, group-scoped discovery, and plan-based tiered rates justify the fee. The secondary risk is BuddyBoss API stability — BuddyBoss (a BuddyPress fork) has changed hooks between minor versions without deprecation notices. Every BuddyBoss integration point must be wrapped in `function_exists()` guards and gated on a minimum tested version.

---

## Key Findings

### Recommended Stack

The plugin runs on PHP 8.1+, WordPress 6.4+, and requires BuddyBoss Platform as a hard dependency. PHP dependencies are managed via Composer with PSR-4 autoloading: `stripe/stripe-php` (^13.0) for the Stripe API client, `nesbot/carbon` (^3.0) for timezone-aware date math, `simshaun/recurr` (^5.0) for RFC 5545 RRULE parsing, and `woocommerce/action-scheduler` (^3.7) for reliable background job processing. The frontend build toolchain is `@wordpress/scripts`, which wraps webpack and handles JS/CSS compilation without a custom config. FullCalendar v6 (vanilla JS build — not React) is the calendar UI library. All REST interactions use `wp.apiFetch` with nonce auth; no additional HTTP client needed.

See `.planning/research/STACK.md` for full rationale, alternatives considered, file structure, and installation commands.

**Core technologies:**
- **PHP 8.1+ / WordPress 6.4+**: runtime baseline — enables typed properties, Interactivity API era, aligns with BuddyBoss install base
- **stripe/stripe-php ^13.0**: official Stripe client — handles Connect OAuth, destination charges, application fees, webhook verification
- **simshaun/recurr ^5.0**: RFC 5545 RRULE parsing — avoids hand-rolling recurrence logic (DST boundary bugs guaranteed otherwise)
- **nesbot/carbon ^3.0**: timezone-aware date math for recurrence expansion
- **action-scheduler ^3.7**: reliable queue for async jobs (email, webhook processing) — WP Cron is insufficient
- **FullCalendar v6 (vanilla JS)**: industry-standard calendar UI — React build avoided to prevent Gutenberg version conflicts
- **@wordpress/scripts**: WP-native build toolchain — webpack + Babel + PostCSS without custom configuration
- **Custom tables via dbDelta()**: orders, attendees, and Stripe account mappings belong in indexed relational tables, not post meta

### Expected Features

See `.planning/research/FEATURES.md` for full competitor analysis, pricing breakdown, and feature dependency graph.

**Must have (table stakes — core events functionality):**
- Event creation with title, description, date/time, timezone, location, virtual link
- Single event detail page and event listing/archive
- Calendar view (month grid) and list view — FullCalendar covers both
- Basic RSVP (free attendance)
- Event categories, tags, search, and filter
- iCal / Google Calendar export
- SEO-friendly URLs and Schema.org Event markup
- Responsive mobile display

**Must have (table stakes — BuddyBoss integration, the product's reason to exist):**
- BuddyBoss group events tab via `BP_Group_Extension` — the primary differentiator; without this it is just another events plugin
- Activity feed integration (event created, RSVP'd, ticket purchased)
- Member profile events section (events hosted and attended)
- Group member invite flow
- BuddyBoss notification system integration
- Respect BuddyBoss permission model (admin / moderator / group organizer roles)

**Must have (table stakes — paid ticketing):**
- Multiple ticket tiers per event (Early Bird, VIP, General)
- Ticket quantity limits and availability tracking
- Stripe Connect organizer onboarding (Standard accounts via OAuth)
- Platform commission via application fees (destination charges)
- Tiered commission rates configurable per BuddyBoss plan tier
- Ticket purchase confirmation email
- Attendee list for organizers
- Order and refund management

**Should have (competitive differentiators):**
- Admin-configurable event creation permissions per plan tier
- Recurring events (weekly/monthly covers 90% of cases) — defer complex patterns to v2
- Virtual event support with external meeting URL (Zoom, Meet)
- Event discovery across all groups a member belongs to

**Defer to v2+:**
- QR code check-in — useful but not required for digital-first communities
- Waitlist management — manage via quantity limits in v1
- Co-organizer roles — single organizer per event works for v1
- Advanced recurring patterns (BYSETPOS, complex BYDAY)
- Event discovery feed aggregating across groups

**Confirmed anti-features (never build):**
- Custom video/streaming infrastructure — accept external URL only
- Offline/cash payments — Stripe Connect only per project scope
- Multi-currency — single currency per organizer account
- Public event marketplace (Eventbrite-style) — community-scoped, not public

### Architecture Approach

The plugin uses a modular vertical-slice architecture: each major concern (Event, Ticketing, Payments, BuddyBoss Integration, Calendar Views, Admin Panel, Webhook Handler) is an independent module with defined boundaries, registered by a central Plugin Bootstrap via WordPress hooks. The critical data model decision is hybrid: CPTs for event and venue content (leverages WP query, caching, REST, revisions), custom tables for orders, attendees, and Stripe account mappings (relational queries, row-level locking, never surfaces in post loops). The payment flow is webhook-driven — Stripe fires `payment_intent.succeeded` to confirm orders, not client-side redirect. Ticket availability uses atomic SQL updates (`UPDATE ... WHERE quantity_sold < quantity`) rather than PHP read-modify-write.

See `.planning/research/ARCHITECTURE.md` for full data flow diagrams, schema definitions, and anti-patterns.

**Major components:**
1. **Plugin Bootstrap** — autoloader, dependency checks, module registration; owns `buddyboss-events.php`
2. **Event Module** — CPT registration (`bbevents_event`, `bbevents_venue`), event CRUD, recurring logic via recurr
3. **Ticketing Module** — ticket tier CPT, atomic availability tracking, order creation; owns `bbevents_orders` and `bbevents_attendees` tables
4. **Payments Module** — Stripe Connect OAuth flow, PaymentIntent with application fees, commission calculation; owns `bbevents_stripe_accounts` table
5. **Webhook Handler** — Stripe signature verification, routes `payment_intent.succeeded`, `charge.refunded`, `charge.dispute.created` to Payments/Ticketing
6. **BuddyBoss Integration** — `BP_Group_Extension` for group tab, `bp_activity_add()` for feeds, profile tab; all wrapped in `add_action('bp_loaded', ...)`
7. **Calendar Views Module** — FullCalendar init, REST feed endpoint (`GET /wp-json/buddyboss-events/v1/events`), shortcode/block
8. **Admin Panel Module** — Settings API for commission rates, permissions matrix, Stripe platform keys

### Critical Pitfalls

See `.planning/research/PITFALLS.md` for full prevention strategies, detection signs, and phase-specific warnings.

1. **Application fee not reversed on refund/dispute** — build `charge.refunded` and `charge.dispute.created` webhook handlers from day one that call `stripe.applicationFees.createRefund()` proportionally; store the `application_fee` ID on every order record.

2. **Recurring events modeled as flat post copies** — define the data model first: parent post with RRULE in post meta, lazily-generated child occurrence posts, explicit support for "edit this / edit all future / edit all" semantics. This cannot be retrofitted.

3. **Race condition on ticket inventory (overselling)** — use atomic `UPDATE bbevents_ticket_types SET quantity_sold = quantity_sold + 1 WHERE id = ? AND quantity_sold < quantity` and check affected rows; never PHP read-then-write.

4. **PCI scope via custom card input fields** — enforce Stripe Payment Element exclusively; no PHP code should ever receive card numbers, CVVs, or expiry dates. Enforce this as a code review checklist item.

5. **BuddyBoss hook instability across versions** — wrap every BuddyBoss integration call in `function_exists()` guards; enforce minimum tested BuddyBoss version in plugin header; show admin notice on version mismatch.

6. **Commission rate locked too early or too late** — capture and store the commission rate and plan tier at PaymentIntent creation time (checkout initiation), never at event creation. The rate is baked into the PaymentIntent and cannot be changed post-confirmation.

---

## Implications for Roadmap

The architecture research and pitfall analysis both converge on the same build order: schema and payments first, ticketing second, BuddyBoss integration third, views fourth, recurring last. This is because: (a) the database schema is expensive to migrate; (b) Stripe Connect is the riskiest external dependency and should be validated early; (c) BuddyBoss integration hooks into events and orders that must already work; (d) calendar views are a read layer with no new data structures; (e) recurring events add significant complexity that should not block the core product.

### Phase 1: Foundation and Plugin Scaffold

**Rationale:** Database schema migrations are painful to retrofit — get this right before any content is created. The plugin bootstrap, CPT registration, and admin settings must exist before any other module can be built. Version-gated activation and `dbDelta()`-based schema management must be in place from the start to avoid the activation-hook-only pitfall.

**Delivers:** Installable plugin skeleton with correct autoloading, custom tables registered, CPTs registered, admin settings page (commission rates, permissions, Stripe platform keys), and BuddyBoss dependency check with admin notice.

**Features addressed:** Admin control panel, admin-configurable creation permissions
**Pitfalls to avoid:** Activation hook only (no update migration path), REST namespace collision, BuddyBoss version gate absent

**Research flag:** Standard patterns — skip phase research. WP plugin scaffolding is well-documented.

---

### Phase 2: Event CRUD and Data Model

**Rationale:** All other modules depend on events existing. The recurring event data model decision (parent + RRULE + child occurrences) must be locked in here before any other feature touches event storage. Timezone storage strategy (UTC + timezone name column) must also be enforced at this phase.

**Delivers:** Event creation (in-person, virtual), venue management, event detail page, event listing/archive, basic event meta (dates, timezone, virtual URL, organizer, group association), taxonomies (category, tag).

**Features addressed:** Event creation, single event detail page, event listing, venue/location fields, organizer fields, event categories/tags, event image, timezone display, virtual event support
**Pitfalls to avoid:** Recurring events as flat copies (data model defined here even if recurring UI comes later), datetime stored in local timezone

**Research flag:** Standard patterns for CPT registration and post meta. Recurring event data model needs careful attention — reference The Events Calendar's RRULE architecture.

---

### Phase 3: Stripe Connect and Payments Core

**Rationale:** Stripe Connect is the highest-risk external dependency. Build and validate it end-to-end with a minimal integration before building the full ticketing UI on top of it. This phase includes the full OAuth flow, PaymentIntent creation with application fees, and webhook handling — so the payment path is proven before ticket tiers and order management are layered in.

**Delivers:** Organizer Stripe Connect onboarding (OAuth flow, account status checks), PaymentIntent creation with destination charges and application fees, webhook handler with signature verification, `charge.refunded` and `charge.dispute.created` handlers, commission calculation from admin-configured rates.

**Features addressed:** Stripe Connect organizer onboarding, platform commission capture, tiered commission rates
**Stack elements used:** `stripe/stripe-php ^13.0`, `bbevents_stripe_accounts` table, WP REST API webhook endpoint
**Pitfalls to avoid:** Application fee not reversed on refund/dispute, abandoned onboarding leaving broken organizer state, commission rate locked at wrong time, PCI scope via custom card fields, no idempotency keys

**Research flag:** Needs careful attention — verify current Stripe Connect destination charges vs direct charges documentation before implementation. Stripe updates recommended patterns.

---

### Phase 4: Ticketing and Checkout

**Rationale:** Builds on the proven payment flow from Phase 3. Adds ticket tier management, atomic inventory tracking, order lifecycle, and the buyer-facing checkout UI. WooCommerce conflict testing must happen here.

**Delivers:** Ticket type CPT (multiple tiers per event, pricing, quantity limits), buyer checkout flow (REST endpoint + Stripe Payment Element UI), atomic inventory decrement, order record creation and completion (hooked to webhook), ticket confirmation email, attendee records, organizer attendee list view, refund management UI.

**Features addressed:** Multiple ticket tiers, ticket quantity limits, ticket purchase confirmation email, attendee list for organizer, order/booking management, refund handling, free RSVP alongside paid tickets
**Pitfalls to avoid:** Race condition on ticket inventory (overselling), WooCommerce checkout URL and hook conflicts, email deliverability via wp_mail default config, REST capacity endpoint without auth check

**Research flag:** WooCommerce conflict testing — develop with WooCommerce + WC Memberships installed from the start.

---

### Phase 5: BuddyBoss Integration

**Rationale:** BuddyBoss hooks into events and orders that now exist. Activity feed integration, group tab, and profile tab are the product's core differentiators and justify its existence over general-purpose events plugins. All BuddyBoss integration hooks must be on `bp_loaded` and wrapped in `function_exists()` guards.

**Delivers:** Group events tab via `BP_Group_Extension`, activity feed items (event created, RSVP, ticket purchased), member profile events section (hosted/attended), group member invite flow, BuddyBoss notification integration, plan-based permission checks for event creation.

**Features addressed:** BuddyBoss group events tab, activity feed posting, member profile events section, group member invite flow, BuddyBoss notification system integration, respect BuddyBoss permission model
**Pitfalls to avoid:** BuddyBoss hook instability across versions, activity feed flooding on bulk operations, registering hooks before `bp_loaded`

**Research flag:** Needs phase research — verify `BP_Group_Extension` constructor args, `bp_activity_add()` signature, and profile tab API against current BuddyBoss Platform developer reference (https://developer.buddyboss.com/reference/) before implementation. BuddyBoss-specific hooks are MEDIUM confidence.

---

### Phase 6: Calendar and Discovery Views

**Rationale:** Read-only layer that queries the data built in Phases 2-4. FullCalendar consumes a REST feed; no new data structures required. Site-wide calendar and group calendar can be built in the same phase since the group calendar is embedded in the `BP_Group_Extension` display method established in Phase 5.

**Delivers:** Site-wide calendar page (shortcode + block, FullCalendar month/week/list views), REST event feed endpoint (`GET /wp-json/buddyboss-events/v1/events?start=&end=`), group calendar tab (embedded in group extension), event detail page templates, event archive/listing templates, iCal/Google Calendar export, SEO Schema.org Event markup.

**Features addressed:** Calendar view (month grid), list view, site-wide calendar, group calendar, iCal export, SEO markup, search and filter
**Stack elements used:** FullCalendar v6 vanilla JS, `@wordpress/scripts`, `wp.apiFetch`

**Research flag:** Standard patterns — FullCalendar v6 integration with WP REST API is well-documented. Skip phase research.

---

### Phase 7: Recurring Events

**Rationale:** Deliberately last — recurring event logic (RRULE expansion, DST handling, exception dates, "edit all future" semantics, activity feed deduplication) is the most complex part of the plugin and should not block delivery of the core product. The data model for recurring events was defined in Phase 2; this phase implements the full UI and generation logic.

**Delivers:** Recurring event creation UI (frequency, interval, end condition), RRULE storage and parsing via `simshaun/recurr`, child occurrence generation (on-demand + scheduled via Action Scheduler up to 6-month rolling horizon), "edit this / edit this and future / edit all" operations, exception date handling, group-scoped recurring events.

**Features addressed:** Recurring events, advanced recurring patterns, group-scoped recurrence
**Stack elements used:** `simshaun/recurr ^5.0`, `nesbot/carbon ^3.0`, `action-scheduler ^3.7`
**Pitfalls to avoid:** Activity feed flooding when generating occurrences, DST boundary drift in recurrence expansion

**Research flag:** Needs phase research — RFC 5545 RRULE edge cases (BYDAY with MONTHLY, BYSETPOS, exception dates) and The Events Calendar's series architecture are worth a focused research pass before implementation.

---

### Phase Ordering Rationale

- **Schema first:** Database schema and CPT slugs are the foundation everything else queries. Retrofitting index columns or changing CPT slugs after data exists causes migrations and data loss.
- **Payments before ticketing:** Stripe Connect is the riskiest component. Validate the OAuth flow, webhook handler, and application fee logic with a single simple PaymentIntent before building ticket tier management on top.
- **Ticketing before BuddyBoss integration:** Activity feed posts and profile sections reference completed orders. BuddyBoss integration cannot be tested end-to-end without a working order lifecycle.
- **Views after data:** Calendar and discovery views are a read layer. Building them after the data model is stable means the REST feed endpoint can be designed around real data shapes.
- **Recurring last:** RRULE complexity should not block the core ticket-sale and community-integration product. The data model placeholder (RRULE field on the event CPT) is reserved in Phase 2 even though the UI is built in Phase 7.

---

### Research Flags Summary

| Phase | Research Needed | Reason |
|-------|----------------|--------|
| Phase 1 | Skip | WP plugin scaffolding — established patterns |
| Phase 2 | Attention needed | Recurring event data model decision is high-stakes and irreversible |
| Phase 3 | Verify Stripe docs | Destination charges vs direct charges; current Stripe Connect application fees API |
| Phase 4 | WC conflict testing | Run dev environment with WooCommerce installed from the start |
| Phase 5 | Phase research needed | BuddyBoss-specific hooks are MEDIUM confidence — verify against current BB developer reference |
| Phase 6 | Skip | FullCalendar + WP REST is well-documented |
| Phase 7 | Phase research needed | RFC 5545 edge cases and exception date handling warrant a research pass |

---

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | MEDIUM | Core WP patterns HIGH; specific library versions (stripe-php v13, recurr v5, @wordpress/scripts v28) need verification against Packagist/npm before pinning |
| Features | MEDIUM | BuddyBoss integration gaps (no competitor does this) HIGH; competitor feature lists and pricing MEDIUM — may have changed since August 2025 |
| Architecture | MEDIUM-HIGH | WP CPT/REST/Settings API patterns HIGH; BuddyBoss-specific integration hooks MEDIUM — verify against current BuddyBoss Platform developer reference |
| Pitfalls | HIGH | Stripe behavior (application fee reversal, idempotency, PCI scope) HIGH — based on stable, well-documented Stripe behavior. WooCommerce conflict patterns MEDIUM |

**Overall confidence:** MEDIUM — all findings are from training data (cutoff August 2025). No live web verification was possible. Version numbers should be verified before pinning in composer.json/package.json. BuddyBoss integration patterns should be verified against current BuddyBoss Platform developer docs before implementation.

### Gaps to Address

- **BuddyBoss Platform version compatibility matrix:** The exact minimum BuddyBoss version to declare as a plugin requirement needs hands-on verification. Training data confidence is MEDIUM on BuddyBoss-specific hooks.
- **Stripe Connect current API pattern:** Verify destination charges with `application_fee_amount` is still the recommended pattern for marketplace commission capture — Stripe periodically updates recommendations.
- **Library version pinning:** All Composer and npm packages should be verified at Packagist and npmjs.com before initial setup. Versions in STACK.md are training-data best-effort.
- **BuddyBoss plan tier API:** The commission calculation relies on a filter `apply_filters('bbevents_site_plan', $plan)` to retrieve the site's BuddyBoss plan. This requires either BuddyBoss exposing plan data programmatically or a manual admin setting — this API surface needs validation.
- **Organizer commission resistance:** Research flagged that organizers accustomed to 0% WP plugins may resist platform fees. No primary research was possible; this should be validated with target organizers before v1 launch.
- **WooCommerce coexistence:** PITFALLS.md flags checkout slug and hook conflicts. Development environment should include WooCommerce + WC Memberships from Phase 4 onward to surface conflicts early.

---

## Sources

### Primary (HIGH confidence)
- Stripe Connect documentation — application fees, destination charges, PaymentIntent, webhook signature verification, PCI scope reduction via Stripe.js
- WordPress Plugin Developer Handbook — CPT registration, Settings API, REST API, activation/deactivation/uninstall lifecycle
- RFC 5545 (iCalendar RRULE) — recurrence rule specification
- WP plugin activation/deactivation/uninstall patterns — dbDelta idempotency, version-check on `plugins_loaded`

### Secondary (MEDIUM confidence)
- BuddyPress/BuddyBoss Platform developer reference — `BP_Group_Extension`, `bp_activity_add()`, `bp_core_add_nav_item()` (verify at https://developer.buddyboss.com/reference/)
- The Events Calendar plugin architecture — recurring events parent/child pattern, FullCalendar usage
- WooCommerce HPOS migration — custom tables for orders rationale
- FullCalendar v6 documentation — vanilla JS build, rrule plugin, event feed format
- Competitor feature analysis — The Events Calendar, Modern Events Calendar, Eventin, EventON, WP Event Manager (verify current feature/pricing state)

### Tertiary (MEDIUM-LOW confidence)
- BuddyBoss Platform version history and hook stability — based on general BuddyPress fork behavior, not primary research into BuddyBoss's specific deprecation policy
- Organizer resistance to platform commission model — reasoned from general market behavior, not primary research

---

*Research completed: 2026-03-10*
*Ready for roadmap: yes*
