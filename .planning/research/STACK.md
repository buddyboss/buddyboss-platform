# Technology Stack

**Project:** BuddyBoss Events Plugin
**Researched:** 2026-03-10
**Research mode:** Training data only (WebSearch/WebFetch unavailable during this session)

> **Note on sources:** External tools (WebSearch, WebFetch, Context7) were unavailable during this research session. All findings are from training data with knowledge cutoff August 2025. Version numbers are best-known as of that date — verify against official sources before pinning in composer.json/package.json.

---

## Recommended Stack

### WordPress Plugin Core

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| PHP | 8.1+ minimum, 8.2+ recommended | Runtime | BuddyBoss Platform itself requires PHP 7.4+ as of 2024; targeting 8.1+ enables typed properties, enums, fibers, and intersection types without alienating the BuddyBoss install base. 8.0 is EOL. |
| WordPress | 6.4+ | Host platform | Minimum viable target; 6.4 introduced the Interactivity API (not needed here, but signals the era). The vast majority of BuddyBoss installs run recent WP. |
| BuddyPress / BuddyBoss Platform | Latest stable (~2.6.x as of mid-2025) | Integration target | BuddyBoss Platform is a fork of BuddyPress — it exposes BP_Component, bp_activity_add(), bp_groups_*, and profile field APIs. This plugin is an add-on, so it declares BuddyBoss Platform as a required dependency. |

**Confidence:** MEDIUM — PHP and WP minimums are standard; BuddyBoss Platform version number needs verification at https://www.buddyboss.com/changelog/

---

### Plugin Architecture Pattern

| Approach | Rationale |
|----------|-----------|
| OOP plugin structure (no boilerplate generator) | The WordPress Plugin Boilerplate (WPPB) from DevinVinson is widely used but the generator hasn't been actively maintained since 2022. Better to hand-craft a PSR-4 structure using Composer autoloading. WPPB's Admin/Public split pattern is still the right mental model, just don't depend on the generator. |
| PSR-4 autoloading via Composer | `composer/installers` + PSR-4 means no manual `require` chains. Classes live in `src/`, loaded automatically. Standard in 2025 commercial plugins. |
| Singleton main plugin class | One entry point (`BuddyBoss_Events::instance()`), registers all hooks on `plugins_loaded`. Avoids double-instantiation. |
| Hook-first design | Everything the plugin does is triggered via WordPress action/filter hooks — no direct procedure calls from templates. This makes the plugin extensible and testable. |

**Confidence:** HIGH — this is established WordPress plugin practice.

---

### Composer / PHP Dependencies

| Library | Version | Purpose | Why |
|---------|---------|---------|-----|
| `stripe/stripe-php` | ^13.0 | Stripe API client | Stripe's official PHP library. v13 (released 2024) supports PHP 8.x natively, typed responses, and the Connect application fee APIs needed for commission capture. Do NOT use an older version — pre-v10 has a fundamentally different API surface. |
| `nesbot/carbon` | ^3.0 | Date/time handling | Recurring event logic requires robust timezone-aware date math. Carbon 3 requires PHP 8.1+, which aligns with our minimum. WordPress's own date functions are insufficient for complex recurrence rules. |
| `simshaun/recurr` | ^5.0 | iCal RRULE parsing | Implements RFC 5545 recurrence rules (DAILY, WEEKLY, MONTHLY, YEARLY, BYDAY etc.). This is the standard PHP library for this. Avoids hand-rolling recurrence logic, which is notoriously bug-prone around DST boundaries. |
| `woocommerce/action-scheduler` | ^3.7 | Background job processing | Used by WooCommerce, Action Scheduler is available as a standalone library. Needed for: sending reminder emails, processing refund webhooks asynchronously, syncing recurring event instances. More reliable than WP Cron for scheduled tasks. |

**Confidence:** MEDIUM — stripe-php v13 and simshaun/recurr v5 need version confirmation against Packagist. Carbon 3 and Action Scheduler versions are well-established.

---

### Stripe Connect Integration Approach

| Decision | Rationale |
|----------|-----------|
| **Stripe Connect — Standard accounts** | Site admins (organizers) connect their own Stripe accounts. Standard accounts give organizers full Stripe dashboard access, handle their own disputes/chargebacks, and issue their own receipts. Best for a marketplace model where organizers are independent businesses. |
| **Application Fees on PaymentIntents** | When creating a `PaymentIntent`, pass `application_fee_amount` (in cents). Stripe automatically routes the fee to the platform account (BuddyBoss). This is the canonical approach for per-transaction commission. NOT `transfer_data` (that's for destination charges, a different flow). |
| **Webhook handler as a WP REST endpoint** | Register `POST /wp-json/buddyboss-events/v1/stripe/webhook` to receive Stripe events. Verify signature with `\Stripe\Webhook::constructEvent()` using the endpoint's signing secret. Handle `payment_intent.succeeded`, `payment_intent.payment_failed`, `account.updated` (for Connect account status), `charge.refunded`. |
| **OAuth Connect flow for organizers** | Use Stripe's OAuth to let organizers connect their account from within the plugin's admin UI. Store their `stripe_user_id` (the `acct_xxx` account ID) against the WP user meta. |
| **Idempotency keys on all charge requests** | Pass a unique idempotency key (e.g., `order_{post_id}_{timestamp}`) to prevent double-charges on network retries. Critical for ticket purchase reliability. |

**Confidence:** HIGH — this is Stripe Connect's documented recommended approach for marketplace platforms. Application fees on PaymentIntents is the correct mechanism for this use case (not destination charges, not transfer-based flows).

---

### Database Layer

| Decision | Rationale |
|----------|-----------|
| **Custom post types for Events** | `bb_event` CPT. Leverages WP's built-in revision, capability, query infrastructure. Event meta (start/end datetime, timezone, venue, virtual link, capacity) stored in post meta. |
| **Custom tables for Tickets and Orders** | Ticket tiers and ticket purchases should NOT use post meta — relational queries over purchases (e.g., "how many of tier X remain?", "what tickets did user Y buy?") require indexed relational tables. Create `{prefix}_bb_event_tickets`, `{prefix}_bb_event_ticket_tiers`, `{prefix}_bb_event_orders` using `dbDelta()` on activation. |
| **`$wpdb` for custom table queries** | Use `$wpdb->prepare()` for all custom table queries. No ORM needed at WordPress plugin scale — ORMs add complexity without benefit when you're already inside the WP environment. |
| **WP Query for CPT queries** | `WP_Query` for all event queries. Enables integration with WP's caching layer (object cache, transients) without extra work. |

**Confidence:** HIGH — CPT + custom tables hybrid is the established pattern for commercial WP event plugins (see The Events Calendar, EventPress patterns).

---

### Frontend / JavaScript

| Library | Version | Purpose | Why |
|---------|---------|---------|-----|
| **FullCalendar** | ^6.1 | Calendar UI | The dominant JS calendar library. v6 is framework-agnostic (vanilla JS or React/Vue wrappers available), handles month/week/list views, recurring events, drag-and-drop if needed. Actively maintained. License: MIT for core, some premium plugins exist. For a WordPress plugin, use the vanilla JS (non-framework) build to avoid React version conflicts with Gutenberg. |
| **@wordpress/scripts** | ^28.x | Build tooling | The official WP build tooling. Wraps webpack + Babel + PostCSS with WP-specific presets. Provides `npm run build` and `npm run start` (watch mode) out of the box. Handles JS/CSS compilation, source maps, and asset hashing. The correct choice for 2025 — avoids maintaining your own webpack config. |
| **Vanilla JS / wp.apiFetch** | built-in | API requests | Use WordPress's built-in `wp.apiFetch` for all REST API calls from the frontend. It handles nonce authentication automatically. No need to add Axios or Fetch polyfills. |
| **WordPress Interactivity API** | built-in (WP 6.5+) | Interactive UI components | For simple reactive UI (ticket quantity selectors, availability counters), the Interactivity API is the WP-native solution. For the calendar view (which is complex), FullCalendar takes precedence. Do not add React/Vue just for interactive elements when the Interactivity API suffices. |

**What NOT to use:**
- **React for the calendar view** — tempting, but creates React version conflicts with Gutenberg. FullCalendar vanilla JS sidesteps this entirely.
- **Vue.js** — no ecosystem reason to add it to a WP plugin; adds bundle weight with no payoff.
- **jQuery UI Datepicker** — bundled with WordPress but outdated; use a lightweight modern alternative (Flatpickr or the native `<input type="datetime-local">`) for date inputs.
- **Moment.js** — deprecated; use Day.js (2kB gzipped) or native Intl APIs for any frontend date formatting.

**Confidence:** MEDIUM-HIGH — FullCalendar v6 and @wordpress/scripts are well-established. Interactivity API guidance is based on WP 6.5 release (April 2024); verify current WP/scripts version numbers.

---

### CSS / Styling

| Approach | Rationale |
|----------|-----------|
| **BEM naming + plugin prefix** | All CSS classes prefixed `.bb-events-`. BEM methodology prevents collisions with BuddyBoss's own styles. Example: `.bb-events-calendar`, `.bb-events-calendar__header`, `.bb-events-ticket-tier--sold-out`. |
| **PostCSS via @wordpress/scripts** | Already included in the build pipeline. Use CSS nesting (PostCSS plugin) and CSS custom properties — no need for Sass/SCSS in 2025. |
| **No CSS framework** | Tailwind or Bootstrap creates too many style collisions inside BuddyBoss themes. Hand-craft focused component CSS. Target 15–25kB total plugin CSS (gzipped). |

**Confidence:** HIGH — pattern is consistent with how other BuddyBoss add-ons are built.

---

### Testing

| Tool | Version | Purpose | Why |
|------|---------|---------|-----|
| **PHPUnit** | ^10.x | PHP unit + integration tests | WordPress's test suite requires PHPUnit. v10 is the current stable release (v11 drops WP compatibility shims, avoid for now). Use `wp-phpunit/wp-phpunit` for WP integration test scaffolding. |
| **Brain\Monkey** | ^2.6 | WP function mocking | Allows unit testing PHP classes that call `get_post_meta()`, `wp_insert_post()`, etc. without a full WP install. Essential for testing business logic (commission calculation, recurrence expansion) in isolation. |
| **WP_Mock** | alternative to Brain\Monkey | WP function mocking | The other popular option. Brain\Monkey is more actively maintained as of 2024. Choose one, not both. |
| **@wordpress/jest-console** + **Jest** | via @wordpress/scripts | JS unit tests | `@wordpress/scripts` includes Jest configuration. Write tests for FullCalendar data transform logic and ticket availability calculations. |
| **wp-browser** (Codeception) | ^3.5 | E2E / acceptance tests | Lucatume's wp-browser provides Codeception modules for WordPress. Enables browser-based acceptance tests (ticket purchase flow, Stripe webhook handling). Use for critical purchase path tests. Optional for initial phases. |

**What NOT to use:**
- **Cypress** for WordPress testing — possible but lacks WP-native test utilities. wp-browser/Codeception is more mature for WP plugin E2E.
- **PHPUnit 11+** — dropped some WP compatibility; stick with ^10.x until WP core catches up.

**Confidence:** MEDIUM — PHPUnit 10 and Brain\Monkey are established. `wp-browser` version needs verification.

---

### REST API Layer

| Decision | Rationale |
|----------|-----------|
| **WP REST API with custom namespace** | Register all endpoints under `/wp-json/buddyboss-events/v1/`. Use `register_rest_route()`. This is the correct WP approach — avoids wp-admin AJAX (legacy, inferior security model). |
| **Custom REST permissions callbacks** | Every endpoint gets an explicit `permission_callback`. Never use `__return_true` for write endpoints. |
| **REST API for frontend calendar data** | FullCalendar fetches events via the REST API (`GET /events?start=&end=`). Returns JSON matching FullCalendar's event object format directly — no client-side transformation needed. |

**Confidence:** HIGH — standard WP REST API practice.

---

### Internationalization (i18n)

| Decision | Rationale |
|----------|-----------|
| **WP i18n functions throughout** | All user-facing strings use `__()`, `_e()`, `_n()` with the plugin's text domain (`buddyboss-events`). No hardcoded English strings. |
| **POT file generated via WP-CLI** | `wp i18n make-pot` generates the translation template. Include in the build process. |
| **JS strings via `wp_set_script_translations()`** | For JS-rendered strings (FullCalendar labels, ticket UI), use `wp_set_script_translations()` + JED-format JSON translation files. |

**Confidence:** HIGH — standard WP plugin i18n practice.

---

### Development Tooling

| Tool | Version | Purpose | Why |
|------|---------|---------|-----|
| **WP-CLI** | ^2.9 | Database, scaffolding, deployment | Essential for local dev — `wp db reset`, `wp plugin activate`, `wp i18n make-pot`. Non-negotiable in a WP plugin project. |
| **composer** | ^2.x | PHP dependency management | Manages stripe-php, Carbon, recurr, and dev dependencies. Use `composer install --no-dev` for production builds. |
| **@wordpress/env** | ^10.x | Local Docker environment | `wp-env` provides a standardized Docker-based WP environment. Ensures consistent PHP/WP versions across the team. Simpler than LocalWP for CI/CD integration. |
| **PHP_CodeSniffer + WordPress Coding Standards** | PHPCS ^3.x + WPCS ^3.x | Code style enforcement | `phpcs --standard=WordPress` enforces WP coding standards (tabs, Yoda conditions, etc.). Required for any plugin sold through a WP ecosystem. |
| **PHPStan or Psalm** | PHPStan ^1.x | Static analysis | Catches type errors, undefined variables, incorrect API usage before runtime. Level 5 is a reasonable starting point for a new plugin. |
| **GitHub Actions** | — | CI pipeline | Run PHPCS + PHPStan + PHPUnit on every PR. Standard. |

**Confidence:** MEDIUM-HIGH — these tools are established; verify version numbers before setting up CI.

---

## Alternatives Considered

| Category | Recommended | Alternative | Why Not |
|----------|-------------|-------------|---------|
| Calendar UI | FullCalendar (vanilla) | React-BigCalendar | React version conflicts with Gutenberg; BuddyBoss themes don't load React on frontend |
| Calendar UI | FullCalendar (vanilla) | DHTMLX Scheduler | Commercial license required for non-GPL use; adds cost complexity |
| Recurrence | simshaun/recurr | Hand-rolled RRULE parser | RRULE edge cases (DST, leap years, BYDAY with MONTHLY) will cause bugs; use the library |
| Background jobs | Action Scheduler | WP Cron | WP Cron is unreliable (only fires on page load); Action Scheduler has a proper queue, retry logic, and admin UI |
| Testing (WP) | Brain\Monkey | WP_Mock | Both valid; Brain\Monkey has more recent maintenance activity as of 2024 |
| Build tools | @wordpress/scripts | Custom webpack | @wordpress/scripts is maintained by the WP core team, handles all edge cases; no reason to maintain your own config |
| PHP HTTP client | stripe/stripe-php (uses Guzzle internally) | wp_remote_post() | For Stripe, always use the official SDK — it handles retries, idempotency, webhook signature verification, and API versioning |
| Payments | Stripe Connect Standard | Stripe Connect Express | Express accounts give less control to organizers and are harder to use in non-US markets; Standard is more appropriate for a global platform |
| CSS | PostCSS via @wordpress/scripts | SCSS/Sass | Sass is not included in @wordpress/scripts by default; PostCSS with CSS nesting achieves the same result without adding a dependency |
| Date math (PHP) | Carbon 3 + recurr | PHP's DateTime | DateTime lacks the fluent API needed for complex timezone-aware recurrence logic at scale |
| Date math (JS) | Day.js (if needed) | Moment.js | Moment.js is in maintenance mode; Day.js is the drop-in replacement at 2kB vs 67kB |

---

## Plugin File Structure

```
buddyboss-events/
├── buddyboss-events.php          # Main plugin file, declares headers, instantiates
├── composer.json
├── package.json
├── .phpcs.xml
├── phpunit.xml
├── phpstan.neon
├── src/
│   ├── Plugin.php                # Singleton main class
│   ├── Events/
│   │   ├── EventPostType.php     # CPT registration
│   │   ├── EventQuery.php        # WP_Query wrappers
│   │   └── RecurrenceManager.php # recurr integration
│   ├── Tickets/
│   │   ├── TicketTier.php
│   │   ├── TicketOrder.php
│   └── └── AvailabilityService.php
│   ├── Payments/
│   │   ├── StripeConnectService.php
│   │   ├── WebhookHandler.php
│   └── └── CommissionCalculator.php
│   ├── BuddyBoss/
│   │   ├── GroupIntegration.php  # bp_groups hooks
│   │   ├── ActivityIntegration.php
│   └── └── ProfileIntegration.php
│   ├── Admin/
│   │   └── SettingsPage.php
│   └── REST/
│       ├── EventsEndpoint.php
│       └── StripeWebhookEndpoint.php
├── assets/
│   ├── src/
│   │   ├── js/
│   │   │   ├── calendar.js       # FullCalendar init
│   │   │   └── checkout.js       # Stripe.js + ticket purchase
│   │   └── css/
│   │       └── main.css
│   └── build/                    # @wordpress/scripts output
├── templates/                    # PHP template files (event single, calendar page)
├── languages/
│   └── buddyboss-events.pot
└── tests/
    ├── Unit/
    └── Integration/
```

---

## BuddyBoss Integration Approach

BuddyBoss Platform is a fork of BuddyPress. The integration API is BuddyPress's public API.

| Integration Point | Mechanism | Key Functions/Hooks |
|-------------------|-----------|-------------------|
| Groups tab | `BP_Group_Extension` class | Extend this class to add an Events tab to every group's navigation. This is the official BuddyPress/BuddyBoss API for group add-ons. |
| Activity feed | `bp_activity_add()` | Call on event creation, RSVP, and ticket purchase. Pass `component`, `type`, `action`, and `content` args. Register custom activity types with `bp_activity_set_action()`. |
| Member profiles | `bp_core_new_nav_item()` | Add an "Events" tab to member profile navigation. |
| Capability checks | `bp_is_active()` | Check that BuddyBoss/BuddyPress components (groups, activity) are active before hooking. |
| Plugin dependency | `bp_is_active()` + admin notice | On `admin_init`, check if BuddyBoss Platform is active; if not, deactivate this plugin and show an admin notice. Do NOT use `is_plugin_active()` (requires including admin.php outside admin context). |

**Confidence:** MEDIUM — BuddyBoss Platform is a fork of BuddyPress and exposes BuddyPress's public API. `BP_Group_Extension` is the correct class for group tabs. Verify against BuddyBoss's developer docs at https://www.buddyboss.com/resources/developers/ as BuddyBoss may have added proprietary extension points beyond BuddyPress.

---

## Stripe Connect: Key Implementation Notes

```php
// Commission on ticket purchase — application fee approach
$paymentIntent = \Stripe\PaymentIntent::create([
    'amount'                => $total_cents,
    'currency'              => 'usd',
    'application_fee_amount' => $commission_cents, // goes to platform (BuddyBoss)
    'transfer_data'         => [
        'destination' => $organizer_stripe_account_id, // acct_xxx
    ],
], [
    'stripe_account' => null, // create on platform, transfer to organizer
]);
```

This is the "destination charges" pattern — money flows through the platform, application fee is retained, remainder goes to organizer. This is different from "direct charges" (where the customer's card is charged on the organizer's account directly).

**Important:** "Destination charges" vs "direct charges" is a critical architectural decision. Destination charges give the platform more control over the payment flow and simplifies PCI scope. For a marketplace with commission capture, destination charges with `application_fee_amount` is the correct pattern.

**Confidence:** HIGH (training data) — but verify current Stripe Connect documentation as Stripe updates its recommended patterns. The distinction between charge types is well-documented at stripe.com/docs/connect/charges.

---

## Installation Reference

```bash
# PHP dependencies
composer require stripe/stripe-php:^13.0 \
                 nesbot/carbon:^3.0 \
                 simshaun/recurr:^5.0

composer require --dev \
    phpunit/phpunit:^10.0 \
    brain/monkey:^2.6 \
    squizlabs/php_codesniffer:^3.0 \
    wp-coding-standards/wpcs:^3.0 \
    phpstan/phpstan:^1.0

# JS dependencies
npm install --save-dev \
    @wordpress/scripts \
    @fullcalendar/core \
    @fullcalendar/daygrid \
    @fullcalendar/timegrid \
    @fullcalendar/list \
    @fullcalendar/rrule \
    rrule

# Dev environment
npm install --save-dev @wordpress/env
```

---

## Sources

| Claim | Source | Confidence |
|-------|--------|------------|
| stripe/stripe-php v13 for PHP 8.x | Training data (Packagist/GitHub releases through Aug 2025) | MEDIUM — verify at packagist.org/packages/stripe/stripe-php |
| simshaun/recurr v5 | Training data | MEDIUM — verify at packagist.org/packages/simshaun/recurr |
| FullCalendar v6 | Training data (fullcalendar.io changelog through Aug 2025) | MEDIUM — verify at fullcalendar.io/docs/release-notes |
| @wordpress/scripts v28 | Training data | MEDIUM — verify at npmjs.com/package/@wordpress/scripts |
| BP_Group_Extension class | Training data (BuddyPress Codex) | MEDIUM — verify BuddyBoss may have extended this |
| Stripe destination charges + application_fee_amount | Training data (Stripe Connect docs) | HIGH — well-documented stable API pattern |
| PHPUnit ^10.x for WP | Training data | MEDIUM — WP Core PHPUnit compatibility evolves; check make.wordpress.org |
| @wordpress/env for local dev | Training data | HIGH — official WP Core tool |
| Brain\Monkey ^2.6 | Training data | MEDIUM — verify at packagist.org/packages/brain/monkey |
| Action Scheduler ^3.7 | Training data (WooCommerce standalone package) | MEDIUM — verify at github.com/woocommerce/action-scheduler |
