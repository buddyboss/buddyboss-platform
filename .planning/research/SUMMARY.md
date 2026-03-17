# Project Research Summary

**Project:** BuddyBoss Events Plugin — v2.0 Feature Additions
**Domain:** WordPress Community Events Plugin (BuddyBoss Add-on)
**Researched:** 2026-03-17
**Confidence:** MEDIUM-HIGH

## Executive Summary

BuddyBoss Events v2.0 is a feature parity and differentiation release that brings the plugin to Eventin Pro-level capability while leveraging BuddyBoss's community infrastructure as a genuine competitive advantage. The plugin's foundation — custom `bb_events` tables, BP_Component integration, vanilla JS wizard, and php-rrule recurrence — is already validated from v1. v2.0 adds six capability layers on top: taxonomy and discovery, enriched event types and content, sessions and speakers, front-end submission with community moderation, custom registration fields, and organizer analytics. Research confirms that all of these are well-understood patterns in the WP events ecosystem, with Eventin, MEC, and Events Manager providing established reference implementations. Where Eventin gates features behind its Pro plan, BuddyBoss Events includes them — the "pro tier" is the product.

The recommended technical approach leans heavily on the existing architecture: implement a `bb_eventmeta` PHP API (mirroring the BuddyPress groupmeta pattern) to avoid altering the main `bb_events` table, add five new custom tables for sessions, speakers, session-speaker relationships, registration field responses, and view tracking, and register taxonomies against the plugin's event type using `wp_term_relationships` without requiring a CPT. Zero new PHP or npm dependencies are required — native WordPress APIs, `fputcsv()`, and extensions of the existing vanilla JS wizard handle every v2.0 feature.

The highest-risk areas are the taxonomy privacy filter (private group events must not leak to public taxonomy archives — this is the category of bug documented in BuddyBoss Platform issue #1692), the Google Maps API key surface (Gemini cross-access risk means an exposed key without HTTP referrer restrictions can be abused for AI inference billed to the site owner), and the custom registration fields schema (a normalized two-table design is mandatory — serialized blobs will cause irreversible problems for CSV export and querying). These three risks must be addressed at the earliest possible phase in their respective feature areas; they cannot be retrofitted cheaply.

---

## Key Findings

### Recommended Stack

v2.0 requires no new PHP libraries and no new npm packages. All features are implementable with native WordPress APIs (`register_taxonomy`, `add_term_meta`, `get_post_meta`, `$wpdb`, `fputcsv`), the existing vendored php-rrule, and extensions to the existing vanilla JS wizard. The key technology decisions are: Google Maps Embed API (free iframe, no billing account required per site), Leaflet.js + OpenStreetMap as an optional no-Google fallback (toggle in settings), `bb_eventmeta` for single-value per-event extension data, and custom tables for sessions and speakers.

See `/Users/tom/Local Sites/Events/.planning/research/STACK.md` for full rationale, code patterns, and alternatives considered.

**Core technologies:**
- `bb_eventmeta` meta API (new PHP functions, existing table): per-event extension data (maps URL, meeting details, FAQ, external link) — mirrors bp_groupmeta pattern, zero schema migration risk
- Custom tables (`bb_event_sessions`, `bb_event_speakers`, `bb_event_session_speakers`, `bb_event_reg_responses`, `bb_event_views`): relational data requiring independent row CRUD and JOIN queries
- `register_taxonomy()` with `wp_set_object_terms()`: categories and tags using `bb_events.id` as object_id — WP does not enforce FK constraint on `wp_term_relationships`
- Google Maps Embed API (iframe, free tier): no billing account on end-user sites — API key optional; share-URL fallback works without a key
- Leaflet.js 1.9.x + Nominatim geocoding: optional map provider toggle for privacy-conscious sites — no key, no cost
- `fputcsv()` + `admin_post` hook: attendee CSV export — canonical lightweight WP pattern
- Extended vanilla JS wizard: front-end submission multi-step form — avoids React/Vue conflicts in BuddyBoss theme context

### Expected Features

v2.0 must reach Eventin Pro-level capability. The Eventin feature tier map (verified 2026-03-17 from themewinter.com/eventin/pricing/) confirms that categories, sessions, and speakers are Eventin Free features, while Google Maps, custom registration fields, CSV export, front-end submission, and countdown timer are Pro-gated. BuddyBoss Events includes all of these.

See `/Users/tom/Local Sites/Events/.planning/research/FEATURES.md` for full competitor matrix, dependency graph, UX behavior references, and phase definitions.

**Must have (table stakes — FEATURES.md Phase 4):**
- Event categories (hierarchical) + event tags (flat) — every events plugin has these; filtering and discovery depend on them
- Structured venue address fields (city/state/zip/country) — prerequisite for Google Maps embed
- Google Maps embed on in-person event pages — user expectation for any in-person event
- Hybrid event type — post-pandemic expectation; low-complexity enum extension
- Online meeting details (platform, URL, ID, password) — plain fields, no API dependency
- FAQ section per event — low complexity, high organizer value
- Countdown timer — low complexity, high engagement value (Eventin Pro; we include it)
- External event redirect link — low complexity (Eventin Pro; we include it)

**Should have (differentiators — FEATURES.md Phases 5-6):**
- Sessions/Agenda — multi-session events need a schedule tab with time slots, rooms, speaker assignments
- Speakers — bios, photos, social links; assigned to sessions; speaker profile pages
- Front-end event submission + pending/approved/rejected workflow — community-generated events; the BuddyBoss-native "Manage Events" profile sub-tab is a genuine differentiator no competitor offers
- Admin approval workflow with email notifications — community moderation essential for trust
- Custom registration fields per event (text, textarea, dropdown, checkbox, radio) — collect dietary needs, t-shirt size, session preferences

**Include if time allows — P2:**
- Per-event analytics dashboard (RSVP count, capacity utilization, waitlist) + attendee CSV export

**Defer to v3:**
- Native Zoom/Meet API OAuth integration — complexity not justified; plain URL fields are sufficient
- Conditional logic in registration fields — v3 if demanded
- AI-generated event content, QR code check-in, event landing page builder, bulk CSV import

### Architecture Approach

The plugin does not use WordPress CPTs for event data — everything lives in custom `bb_events` tables accessed via `$wpdb`. v2.0 follows the same pattern: new features get new custom tables (sessions, speakers, reg responses, views) or new `bb_eventmeta` keys. The `BP_Events_Component` boot pattern, screen functions per URL, and REST endpoint subclassing `WP_REST_Controller` are all established and must be extended rather than replaced. Front-end submission and the organizer dashboard follow the same screen function + nav registration pattern as the existing attending/hosting profile tabs.

See `/Users/tom/Local Sites/Events/.planning/research/ARCHITECTURE.md` for schema definitions, data flow diagrams, build order, and anti-patterns.

**Major components and responsibilities:**
1. `bb_eventmeta` PHP meta API — new `bp_event_get/update/add/delete_meta()` functions; gate for all per-event extension data; enables every downstream feature without altering `bb_events`
2. `class-bp-event-session.php` + `class-bp-speaker.php` — new row-model classes for sessions and speakers; backed by three new custom tables (`bb_event_sessions`, `bb_event_speakers`, `bb_event_session_speakers`)
3. `screens/submit.php` + `screens/profile/organizer.php` — front-end submission screen and organizer dashboard; registered as new BP_Component nav items alongside existing attending/hosting tabs
4. Extended `BP_REST_Events_Endpoint` — adds taxonomy fields, sessions/speakers as nested resources, `/export` CSV route, extended RSVP endpoint accepting registration field answers
5. `bb_event_reg_responses` table — normalized attendee answers to custom registration fields (field_id, attendee_id, field_value); enables SQL-based CSV export and per-field filtering
6. `bb_event_views` table + analytics functions — page view deduplication and organizer-facing aggregate queries against `bb_event_attendees`

**Key patterns:**
- `bb_eventmeta` for all scalar extension data — avoids `ALTER TABLE` on `bb_events`
- Custom tables only for relational data needing independent row CRUD (sessions, speakers, reg responses, view tracking)
- Taxonomy term assignments via `wp_set_object_terms()` using event ID as `object_id` — no CPT shim required
- `pre_get_posts` privacy filter is mandatory on all taxonomy archive queries — apply from the moment taxonomy is registered
- Front-end submission POSTs to the same REST endpoint as admin creation — single source of truth; moderation status controlled by a settings flag

### Critical Pitfalls

See `/Users/tom/Local Sites/Events/.planning/research/PITFALLS.md` for full prevention strategies, security checklists, performance traps, and "looks done but isn't" verification steps.

1. **Taxonomy archive leaks private group events to public** — Register taxonomies with `publicly_queryable => false` or add a `pre_get_posts` filter that excludes events where the viewer lacks group membership. Must be built into the taxonomy layer on day one — not retrofitted. Confirmed by BuddyBoss Platform issue #1692 (private content showing to non-members).

2. **Google Maps API key exposes Gemini access** — Since Gemini uses the same key namespace as Maps (documented by Truffle Security, 2024/2025), an exposed Maps key without HTTP referrer restrictions enables AI inference billed to the site owner. Store with `autoload = false`, output server-side only on event pages with a venue, document referrer restriction in settings UI, add GDPR consent gate before loading Maps JS.

3. **Front-end submission bypasses admin capability configuration** — The REST handler must call `current_user_can( bp_events_get_create_capability() )` — the same function used by the admin creation path. Using only `is_user_logged_in()` silently overrides admin-configured permissions. Submitted events must appear in admin queue as `pending` — if the admin list only shows `published`, the moderation workflow is silently broken.

4. **Custom registration field answers require a normalized table** — Serialized blobs in `bb_event_attendees` are the anti-pattern and categorically "never acceptable" per PITFALLS.md. Create `bb_event_reg_responses` (attendee_id, field_id, field_value) as a proper table before writing any field answer code. CSV export with one column per field is impossible from a serialized blob at scale.

5. **`ALTER TABLE` on `bb_events` is off-limits for v2.0 fields** — New per-event attributes go in `bb_eventmeta`. Structured address sub-fields (`_venue_city`, etc.) belong in `bb_eventmeta`, not as new columns on `bb_events`. The existing `venue_address` blob stays; a parsing migration backfills structured fields from it. Promoting to first-class columns is a v3 concern only if query filtering by city becomes a measured requirement.

---

## Implications for Roadmap

Research points to six tightly-ordered sub-phases within the existing roadmap's Phase 4 slot. The ordering is driven by data dependencies and schema stability requirements. It cannot be reordered without causing rework.

### Phase 4a: Meta API + Taxonomy Foundation

**Rationale:** Every downstream feature uses `bp_event_get/update_meta()` and taxonomy filtering. Build it once, use it everywhere. This is the lowest-risk phase but the highest-leverage one — getting it wrong cascades to every subsequent phase.

**Delivers:** `bp_event_*_meta()` functions backed by `bb_eventmeta`; `bb_event_category` (hierarchical) and `bb_event_tag` (flat) taxonomies registered and filterable; `bp_events_get_events()` extended to support category/tag filtering; admin category and tag management screens.

**Addresses features from FEATURES.md:** Event categories, event tags.

**Avoids pitfalls:** Taxonomy must be registered with `publicly_queryable => false` OR `pre_get_posts` privacy filter applied before any public-facing archive pages exist. This phase sets the correct default permanently.

**Research flag:** Standard pattern. No research-phase needed. Verify `wp_set_object_terms()` accepts arbitrary integer `object_id` on the target WP version as first implementation step (BuddyBoss group types use this same pattern, providing strong precedent).

### Phase 4b: Data Enrichment

**Rationale:** These features are parallel-safe (no inter-feature dependencies among them) and all resolve to `bb_eventmeta` key additions or extensions of existing enum fields. No new tables. Low risk of unintended breakage.

**Delivers:** Structured venue address fields in `bb_eventmeta`; Google Maps embed (server-side iframe from address meta, API key in settings with GDPR consent gate); hybrid event type extension; online meeting detail fields; FAQ section (JSON array in meta); countdown timer (vanilla JS reading UTC timestamp from PHP); external event redirect link.

**Addresses features from FEATURES.md:** Structured venue, Google Maps, hybrid event type, online meeting details, FAQ, countdown timer, external event link.

**Avoids pitfalls:** Google Maps API key — store with `autoload = false`, output conditionally on event pages with a venue address only, document referrer restriction and Gemini risk in settings UI, GDPR consent gate before Maps JS loads.

**Research flag:** Standard patterns throughout. No research-phase needed. Maps API key security documentation (PITFALLS.md Pitfall 2) must be reviewed as part of settings UI design during implementation.

### Phase 4c: Sessions + Speakers

**Rationale:** Introduces new database schema (`bb_event_sessions`, `bb_event_session_speakers`, `bb_event_speakers`). Must be tested and stable before the front-end submission wizard is extended to include sessions and speakers — otherwise the wizard requires revisiting.

**Delivers:** `BP_Event_Session` and `BP_Speaker` row-model classes; three new tables installed via `dbDelta`; admin UI for session/speaker management within event edit screen; REST API nested resources under `/events/{id}/sessions` and `/events/{id}/speakers`; template partials for sessions accordion and speakers grid on single event page.

**Addresses features from FEATURES.md:** Sessions/Agenda, Speakers, session-speaker assignment, session display on event page.

**Avoids pitfalls:** Sessions MUST use the custom table — serialized `bb_eventmeta` is explicitly the anti-pattern for sessions (ARCHITECTURE.md Anti-Pattern 1). Speakers must NOT trigger BuddyBoss activity feed entries. Draft speaker data must not be returned to unauthenticated REST requests.

**Research flag:** Standard pattern. No research-phase needed. One verify step: confirm `dbDelta()` correctly creates the session/speaker tables (including the JOIN table) on the target WordPress version before building dependent code on top.

### Phase 4d: Front-End Submission + Organizer Dashboard

**Rationale:** Depends on Phase 4a (taxonomy assignments in wizard), 4b (venue/type fields in wizard), and 4c (session/speaker steps in wizard). Building submission before 4c would mean revisiting the wizard. The organizer dashboard is included here because it surfaces submitted events and their approval status.

**Delivers:** `screens/submit.php` screen function; organizer "Manage Events" sub-tab in BP member profile nav; `screens/profile/organizer.php` dashboard (event list with status badges, pending/approved/rejected); admin pending-events view; approval/rejection workflow with email notifications; settings toggle for auto-publish vs. require approval.

**Addresses features from FEATURES.md:** Front-end event submission, approval workflow, BuddyBoss-native organizer dashboard.

**Avoids pitfalls:** Submission handler must call `current_user_can( bp_events_get_create_capability() )` after nonce verification. Pending events must appear in admin queue alongside published events. The approval "publish" action must verify `publish_events` capability, not just `manage_options`. Never use `__return_true` as REST route `permission_callback`.

**Research flag:** Verify BuddyBoss notification system hooks — determine whether BuddyBoss Platform provides notification infrastructure (push + email + in-app) that should be used instead of raw `wp_mail()` for approval/rejection emails. Flag for `/gsd:research-phase` during planning.

### Phase 4e: Custom Registration Fields

**Rationale:** Depends on Phase 4d — the submission and RSVP flow must be stable before layering custom fields on top. Custom fields are meaningless without a functioning submission and RSVP path.

**Delivers:** Registration field builder UI in event edit screen (text, textarea, dropdown, checkbox, radio); `bb_event_reg_responses` table; extended RSVP REST endpoint accepting and validating field answers; dynamic form renderer from JSON schema in `bp-events-single.js`; field responses visible in admin; field columns in CSV export.

**Addresses features from FEATURES.md:** Custom registration fields, field responses stored per attendee.

**Avoids pitfalls:** `bb_event_reg_responses` normalized table is the only acceptable storage pattern — no serialized blobs. CSV export must use batched SQL queries, not PHP loops over attendee records (N+1 query trap). Required fields must validate both client-side (HTML5 `required` + JS) and server-side. All text answers sanitized with `sanitize_text_field()` before storage, escaped with `esc_html()` on output.

**Research flag:** Standard pattern once schema is agreed. No research-phase needed. Schema review is the critical gate — `bb_event_reg_responses` design must be finalized and reviewed before any field answer code is written.

### Phase 4f: Analytics + Reports

**Rationale:** Naturally last — queries all data produced by prior phases. View tracking can be hooked in early (it is a single action hook), but the dashboard UI and complete CSV export require the organizer screen from Phase 4d and registration field data from Phase 4e.

**Delivers:** `bb_event_views` table with deduplication (once per user per event per day via `INSERT IGNORE` + unique key); view tracking hook on single event screen; analytics aggregate functions in `bp-events-functions.php`; analytics table in organizer dashboard template; `GET /buddyboss/v1/events/{id}/export` REST route streaming CSV with one column per custom registration field.

**Addresses features from FEATURES.md:** Per-event analytics dashboard (RSVP count, capacity utilization, waitlist count), attendee CSV export with custom field columns.

**Avoids pitfalls:** Attendee count on listing pages must NOT trigger one query per event — single GROUP BY query across `bb_event_attendees`. Organizer analytics must be cached (5-minute transient). CSV export endpoint must require `manage_events` capability — return 403 to unauthenticated requests. `bb_event_views` rows older than 90 days purged via WP-Cron to prevent unbounded table growth.

**Research flag:** Verify analytics query performance (batched counts vs. live COUNT(), transient TTL) against representative data on staging using Query Monitor before shipping. Not a full research-phase, but an implementation-time verification checkpoint.

### Phase Ordering Rationale

- **Meta API first (4a):** `bp_event_get/update_meta()` is called by every subsequent phase. No dependencies of its own. Build it once — every other phase uses it.
- **Data enrichment second (4b):** No inter-feature dependencies among enrichment features. All resolve to meta keys. Completing before the wizard is extended means the submission form can include all enrichment fields in a single pass without rework.
- **Sessions/speakers third (4c):** Introduces schema. Must be stable before the submission wizard is extended to include session/speaker steps. Building sessions after submission forces a wizard revisit.
- **Submission fourth (4d):** Can include the full enrichment field set and sessions/speakers in one pass only if 4b and 4c are complete. Organizer dashboard bundled here because it surfaces submission output.
- **Registration fields fifth (4e):** Requires a stable submission and RSVP flow. Fields are meaningless without it.
- **Analytics last (4f):** Queries the data produced by all prior phases. Including it earlier produces an incomplete dashboard and export.

### Research Flags

Phases likely needing `/gsd:research-phase` during planning:
- **Phase 4d (Front-End Submission):** BuddyBoss notification system integration — determine whether BuddyBoss Platform provides notification hooks (push + email + in-app) that should be used instead of raw `wp_mail()` for approval/rejection emails. BuddyBoss-specific APIs are MEDIUM confidence.

Phases with standard patterns (skip research-phase, but note implementation-time checks):
- **Phase 4a (Meta API + Taxonomy):** BP groupmeta pattern is well-documented; taxonomy with non-CPT object type is confirmed WP behavior. Verify `wp_set_object_terms()` accepts arbitrary integer `object_id` before writing code.
- **Phase 4b (Data Enrichment):** All features are meta key additions or enum extensions; Maps and Leaflet patterns fully specified in STACK.md.
- **Phase 4c (Sessions + Speakers):** Custom table CRUD is standard; dbDelta for schema creation is well-documented. Verify `dbDelta()` creates all three tables (including JOIN table) correctly.
- **Phase 4e (Custom Registration Fields):** Standard EAV-normalized pattern once schema is agreed.
- **Phase 4f (Analytics):** Query performance verification in Query Monitor is the critical step — not a research-phase, but must happen before shipping.

---

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | All v2.0 technology decisions use native WP APIs or confirmed stable libraries. No new dependencies. Maps API pricing confirmed from Google docs March 2025. CMB2/ACF rejections well-argued. Leaflet 1.9.x current stable confirmed. |
| Features | MEDIUM-HIGH | Taxonomy, venue, hybrid type, submission workflow, FAQ, countdown, external link: HIGH (confirmed from official docs and multiple plugins). Sessions/speakers field-level details: MEDIUM (Eventin WebFetch returned CSS; field list extrapolated from search results + WP Event Manager Speaker & Schedule docs). Custom registration fields: MEDIUM (types confirmed; Eventin Pro implementation details unavailable from WebFetch). |
| Architecture | HIGH | Based on direct codebase inspection of `class-bp-event.php`, `class-bp-events-component.php`, `bp-events-functions.php`. `bb_eventmeta` table confirmed in schema. `bp_get_meta()` groupmeta pattern confirmed. One verify point: `wp_set_object_terms()` with non-post `object_id` (expected to work; confirm against current WP version in use). |
| Pitfalls | HIGH for WP core; MEDIUM for BuddyBoss-specific | Taxonomy privacy filter, nonce/CSRF patterns, postmeta EAV performance degradation: HIGH (WP developer docs, confirmed). Google Maps Gemini key risk: MEDIUM (Truffle Security 2024/2025 research — verify GCP scope restrictions are current). GDPR consent gate for Maps: MEDIUM (Complianz source — confirm against current EU guidance at implementation time). |

**Overall confidence:** MEDIUM-HIGH

### Gaps to Address

- **`wp_set_object_terms()` with non-CPT object_id:** Confirm this works on the target WP version before writing taxonomy assignment code. BuddyBoss group types use this pattern (strong precedent), but verify directly.
- **`bp_get_meta()` function signature:** Verify against the current BuddyBoss Platform version before implementing the `bb_eventmeta` API. The groupmeta pattern is the reference; confirm the function signature has not changed in recent platform versions.
- **BuddyBoss notification system hooks:** Before writing approval/rejection email code in Phase 4d, determine whether BuddyBoss Platform provides notification infrastructure (push + email + in-app) that should be used instead of raw `wp_mail()`.
- **Eventin sessions/speakers field-level detail:** The exact field set for Eventin's sessions and speakers could not be confirmed from WebFetch (returned CSS). The extrapolated field list in FEATURES.md is based on search results and WP Event Manager documentation. Validate against live Eventin data before finalizing the session/speaker schema.
- **GDPR two-click map pattern:** Confirm the legal adequacy of the "click to load map" consent approach against current EU guidance at Phase 4b implementation time — the Complianz source is MEDIUM confidence.

---

## Sources

### Primary (HIGH confidence)
- Direct codebase inspection: `class-bp-event.php`, `class-bp-events-component.php`, `bp-events-functions.php`, `bp-events-loader.php` — authoritative source for existing architecture decisions
- Eventin pricing page (verified 2026-03-17): https://themewinter.com/eventin/pricing/ — feature tier map, Pro vs Free delineation
- Eventin features page (verified 2026-03-17): https://themewinter.com/eventin/features/
- Google Maps Embed API overview: https://developers.google.com/maps/documentation/embed/get-started — free tier and no-key fallback confirmed
- WordPress developer docs: `pre_get_posts`, `register_taxonomy`, `wp_set_object_terms`, nonce best practices — established WP core APIs
- `fputcsv()` + `admin_post` hook — canonical lightweight WP CSV export pattern, confirmed across multiple sources

### Secondary (MEDIUM confidence)
- Eventin front-end submission docs: https://support.themewinter.com/docs/plugins/plugin-docs/event/front-end-event-submission/
- MEC Frontend Submission docs (workflow reference): https://webnus.net/dox/modern-events-calendar/frontend-event-submission/
- Events Manager approval and automated emails docs: https://wp-events-plugin.com/documentation/event-approval/
- The Events Calendar virtual/hybrid events: https://theeventscalendar.com/knowledgebase/creating-a-virtual-event/
- Truffle Security: Google API Keys and Gemini cross-access risk (2024/2025): https://trufflesecurity.com/blog/google-api-keys-werent-secrets-but-then-gemini-changed-the-rules
- Complianz: Google Maps and GDPR: https://complianz.io/google-maps-and-gdpr-what-you-should-know/
- Leaflet.js changelog — 1.9.x confirmed stable; 2.0 alpha August 2025 not production-ready
- BuddyBoss Platform GitHub issue #1692 — private content group privacy pattern

### Tertiary (LOW confidence / needs validation)
- Eventin sessions and speakers field-level details — WebFetch returned CSS; field list extrapolated from search results + WP Event Manager Speaker & Schedule add-on documentation. Validate against live Eventin Pro before finalizing session/speaker schema.
- Eventin custom registration field implementation — field types confirmed; exact Eventin Pro implementation unavailable from WebFetch.

---

*Research completed: 2026-03-17*
*Ready for roadmap: yes*
