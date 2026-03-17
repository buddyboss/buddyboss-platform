# Technology Stack

**Project:** BuddyBoss Events Plugin — v2.0 Feature Additions
**Researched:** 2026-03-17
**Confidence:** MEDIUM (verified approaches, some version numbers from training data — see per-item notes)

> **Scope note:** This document covers only the NEW additions required for v2.0. The existing validated stack (WordPress/PHP runtime, BuddyBoss Platform integration pattern, FullCalendar 6.x, @wordpress/scripts build pipeline, PHPUnit test suite, php-rrule, vanilla JS wizard) remains unchanged. Do not re-evaluate those choices.

---

## Recommended Stack — v2.0 Additions

### Maps: Google Maps Embed API (no paid tier)

**Decision:** Use the Google Maps Embed API (iframe-based), NOT the Maps JavaScript API.

| Criterion | Embed API | Maps JavaScript API |
|-----------|-----------|---------------------|
| Cost | Free, unlimited requests | Requires billing account; Starter plan ~$100/month as of March 2025 |
| API key required | Optional (iframe share URL works without one; API key enables customization) | Required |
| Customisation | None beyond zoom/size | Full marker/style control |
| Performance | Slower (loads iframe sub-page) | Faster JS-rendered |
| Complexity | Zero — generate URL from address meta | Significant JS overhead |

**Why Embed API wins here:** Events have a single known address per event. There is no need for custom markers, route planning, or interactive clustering. The Embed API costs nothing and requires no billing account — critical for a plugin distributed to potentially thousands of BuddyBoss sites where site owners should not need to set up Google billing just for a map pin. A simple iframe generated from the structured address fields is sufficient.

**Implementation:** Build the embed URL server-side in PHP from stored address meta fields:

```php
$address_encoded = urlencode( implode( ', ', [
    $street, $city, $state, $zip, $country
] ) );
$embed_url = "https://www.google.com/maps/embed/v1/place?key={$api_key}&q={$address_encoded}";
```

Make the API key optional — when absent, fall back to the share-URL iframe pattern (no key required):

```php
// Fallback — no API key needed
$share_url = "https://maps.google.com/maps?q={$address_encoded}&output=embed";
```

Store structured address fields as separate post meta keys (`_bb_event_venue_street`, `_bb_event_venue_city`, `_bb_event_venue_state`, `_bb_event_venue_zip`, `_bb_event_venue_country`, `_bb_event_venue_lat`, `_bb_event_venue_lng`). This enables querying events by city/country later without parsing a concatenated string.

**Confidence:** HIGH — Embed API pricing and free tier confirmed via Google's own documentation (March 2025). Share-URL pattern confirmed via community sources.

---

### Alternative Map Option: Leaflet.js + OpenStreetMap

If a site owner does not want any Google dependency, provide a toggle in event settings to switch to Leaflet.js + OpenStreetMap. This has no API key requirement and no cost at any scale.

**Implementation:** Enqueue `leaflet@1.9.x` CSS/JS conditionally (only on event single pages when the Leaflet option is enabled). Use the stored lat/lng meta to initialise the map. Use a geocoding request to Nominatim (OpenStreetMap's free geocoder) at save time to resolve address to lat/lng — cache the result in post meta.

**Confidence:** MEDIUM — Leaflet 1.9.x is confirmed current stable (2.0 alpha released August 2025, not production-ready). Nominatim free tier confirmed. Leaflet 2.0 should be tracked for adoption once stable.

---

### Taxonomy: Term Meta for Category Icons/Images

**Decision:** Use WordPress core term meta (available since WP 4.4) directly — no third-party library.

Register a custom taxonomy `bb_event_category` (hierarchical) and `bb_event_tag` (flat). Store icon/image association as term meta via `add_term_meta()` / `update_term_meta()`:

- `_bb_event_cat_icon_id` — WP media library attachment ID for the category icon/image

Expose a media upload field on the term edit screen using `{taxonomy}_edit_form_fields` and `{taxonomy}_add_form_fields` hooks with a standard WP media uploader button (wp.media). No third-party metabox library needed — this is a single field.

**Confidence:** HIGH — term meta is a native WP API, well-documented, stable since WP 4.4.

---

### Repeatable Meta: Serialized Post Meta (no library)

**Decision:** Store sessions/agenda, FAQ items, and custom registration field definitions as serialized post meta — NOT in a custom sub-table and NOT via CMB2 or ACF.

**Rationale for serialized post meta:**

Sessions and FAQs are display data, not queryable data. The question "show me all events that contain a session titled X" does not exist in the product spec. The only query needed is "give me all sessions for event ID 123" — which is a single `get_post_meta( $event_id, '_bb_event_sessions', true )` call returning the full array.

Serialized format for sessions:
```php
// Stored as: get_post_meta( $post_id, '_bb_event_sessions', true )
[
    [
        'title'       => 'Opening Keynote',
        'start_time'  => '09:00',
        'end_time'    => '10:00',
        'speaker_ids' => [42, 87],   // WP post IDs of Speaker CPT
        'location'    => 'Main Hall',
        'description' => '...',
    ],
    // ...
]
```

Serialized format for FAQ:
```php
// Stored as: get_post_meta( $post_id, '_bb_event_faqs', true )
[
    [ 'question' => '...', 'answer' => '...' ],
]
```

Serialized format for custom registration fields:
```php
// Schema stored per event: get_post_meta( $post_id, '_bb_event_reg_fields', true )
[
    [
        'id'       => 'dietary_requirements',
        'label'    => 'Dietary requirements',
        'type'     => 'text',     // text | dropdown | checkbox
        'required' => true,
        'options'  => [],         // populated for dropdown/checkbox types
    ],
]
// Attendee answers stored in RSVP table (see Database section below)
```

**Why NOT CMB2:** CMB2 is a dependency that has seen reduced maintenance activity since 2023. For repeatable groups, it still works, but it ships a full metabox framework that is overkill for three structured data types. Native WP admin metaboxes with custom JS for add/remove rows are 100 lines of code, not a library dependency.

**Why NOT ACF:** ACF Pro is commercial (cost), ACF Free was acquired by WP Engine and its future as a plugin dependency in a commercial plugin sold by BuddyBoss is strategically awkward.

**Why NOT a custom sub-table for sessions:** Sub-tables are the right call when rows need independent queries, foreign key joins, or per-row CRUD from multiple contexts. Sessions are always fetched as a set for a single event and never queried individually. Sub-table overhead (schema migration, dbDelta maintenance, custom query classes) is not justified here.

**When a sub-table IS appropriate:** Speaker assignments to sessions (if sessions eventually need their own admin listing/search). For v2.0, the serialized `speaker_ids` reference is sufficient.

**Confidence:** MEDIUM-HIGH — serialized post meta for non-queryable grouped data is a well-established WordPress pattern. The "don't serialize queryable data" rule is the important constraint, and sessions/FAQ/field-definitions do not need to be queryable.

---

### Speakers: Custom Post Type

**Decision:** Speakers are a CPT (`bb_event_speaker`), not serialized meta.

Speakers differ from sessions and FAQs because:
1. A speaker can be assigned to multiple events and multiple sessions.
2. The speaker listing page ("All Speakers") is a legitimate admin and front-end view.
3. Speaker profiles have rich content: bio, photo, social links — content that benefits from the WP editor/media library.
4. Reuse across events means normalising makes sense.

Store speaker-to-event relationships via post meta on the event: `_bb_event_speaker_ids` → array of Speaker CPT post IDs. Sessions reference the same IDs via the serialized `speaker_ids` field above.

**CPT args:** `public => false` (speakers are managed via the event, not browsed directly), `show_in_menu => true` under the Events admin menu, `supports => ['title', 'editor', 'thumbnail']`.

**No custom sub-table needed.** The event-to-speaker relationship is stored as post meta on the event. Speaker-to-session is part of the session serialised blob. This is sufficient for v2.0 read patterns.

**Confidence:** HIGH — CPT for speaker profiles is the standard pattern in Eventin, The Events Calendar, and every commercial events plugin surveyed.

---

### CSV Export: Native PHP fputcsv

**Decision:** Native PHP `fputcsv()` + `$wpdb` queries. No library.

Pattern: register a WP admin action (`admin_post_bb_event_export_csv`), verify nonce and capability, run `$wpdb->get_results()` against the RSVP table + event post meta, output with streaming headers, loop with `fputcsv()`.

```php
add_action( 'admin_post_bb_event_export_csv', [ $this, 'handle_csv_export' ] );

public function handle_csv_export(): void {
    check_admin_referer( 'bb_event_export_csv' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Forbidden', 403 );
    }

    $event_id = absint( $_GET['event_id'] ?? 0 );
    // ... query RSVPs, attendee meta, registration field answers

    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="event-' . $event_id . '-attendees.csv"' );
    header( 'Pragma: no-cache' );

    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, [ 'Name', 'Email', 'RSVP Status', 'Registered', /* dynamic field columns */ ] );

    foreach ( $rows as $row ) {
        fputcsv( $out, array_values( $row ) );
    }

    fclose( $out );
    exit;
}
```

For large events (1000+ attendees), add a `LIMIT`/`OFFSET` pagination loop or use `$wpdb->get_results()` with unbuffered queries to avoid memory exhaustion.

**Why no library:** PHP's `fputcsv()` handles quoting, escaping, and Unicode correctly. CSV generation is not complex enough to warrant a library. Libraries like PhpSpreadsheet add 2MB of code for XLSX support — unnecessary unless Excel format is required (it is not in the v2.0 spec).

**Confidence:** HIGH — `fputcsv()` + `admin_post` hook is the canonical lightweight WordPress CSV export pattern, confirmed across multiple sources.

---

### Front-End Submission Wizard: Extend Existing Vanilla JS Pattern

**Decision:** Extend the existing vanilla JS multi-step wizard (already validated in v1.0) to add the organizer submission flow. Do NOT introduce a new JS framework or form library.

The existing wizard uses step show/hide with validation per step. The organizer submission form needs:
- Standard steps: Title/Description → Location/Type → Date/Time → Sessions → Speakers → Registration Fields → Preview/Submit
- New capability: dynamic add/remove rows for sessions, FAQ, custom fields

**Add one lightweight pattern for dynamic rows:**

```js
// Generic add-row handler — attach to any .bb-events-repeater container
function initRepeater( container ) {
    container.querySelector( '.add-row' ).addEventListener( 'click', () => {
        const template = container.querySelector( '[data-template]' );
        const clone = template.cloneNode( true );
        clone.removeAttribute( 'data-template' );
        // Update name attributes: field[0][title] → field[N][title]
        const index = container.querySelectorAll( '.repeater-row:not([data-template])' ).length;
        clone.querySelectorAll( '[name]' ).forEach( el => {
            el.name = el.name.replace( /\[0\]/, `[${index}]` );
        } );
        container.querySelector( '.repeater-rows' ).appendChild( clone );
    } );
}
```

This is ~50 lines, zero dependencies. No need for Sortable.js or a drag-and-drop library for v2.0 — drag reorder is a UX enhancement that can be added in v3.0 if needed.

**Server-side form processing:** Handle via a REST endpoint (`POST /wp-json/buddyboss-events/v1/submissions`) that validates, sanitizes, and creates a draft event post pending admin approval. The approval workflow uses WP post statuses: `pending` → `publish` (on admin approval) or `trash` (on rejection). No additional library needed.

**Confidence:** HIGH — extending the existing wizard is the correct approach. Adding a framework for dynamic rows would introduce React/Vue version conflicts in the BuddyBoss theme context, which was already ruled out in v1.0.

---

### Organizer Dashboard: WP Admin + Custom Dashboard Page

**Decision:** The organizer dashboard is a front-end page rendered by a plugin-provided shortcode/block that queries events where `post_author = current_user_id`. No dedicated dashboard framework.

Display metrics (views count, RSVP count) via simple `$wpdb` or `get_post_meta()` calls. Views are tracked by incrementing a post meta counter `_bb_event_view_count` on every single event page load (with basic bot filtering via user-agent check).

Alternatively, use WP's `wp_statistics` hook if the site has a stats plugin — but don't depend on it. The built-in counter is sufficient for organizer-facing analytics.

**Confidence:** MEDIUM — simple counter approach is proven. More sophisticated analytics (unique views, traffic sources) would require a dedicated analytics table, which is out of scope for v2.0.

---

### Hybrid Event Type: Post Meta Only

**Decision:** Hybrid events are a meta flag on the existing `bb_event` CPT. No schema changes.

Add post meta:
- `_bb_event_type`: `in-person` | `virtual` | `hybrid` (extends existing `in-person` / `virtual`)
- `_bb_event_online_platform`: `zoom` | `teams` | `meet` | `other` (free text label)
- `_bb_event_online_meeting_id`: string
- `_bb_event_online_meeting_password`: string (encrypted at rest via `wp_encrypt` or stored as a hash — do not store in plain post meta visible to all admins)
- `_bb_event_online_join_url`: full URL

**Note on meeting password storage:** Online meeting passwords are often low-sensitivity (shared with registered attendees), but storing in plain post meta is fine for the access pattern — only registered attendees see it.

**Confidence:** HIGH — extending event type via post meta is straightforward.

---

### Countdown Timer: Vanilla JS (no library)

**Decision:** Implement a countdown timer with vanilla JS. No library.

A countdown timer to an event date is ~20 lines of JavaScript using `Date` arithmetic and `setInterval`. The only non-trivial concern is timezone handling — use the event's stored UTC timestamp and `Intl.DateTimeFormat` to render in the visitor's local timezone.

```js
function initCountdown( targetTimestamp, container ) {
    const update = () => {
        const diff = targetTimestamp * 1000 - Date.now();
        if ( diff <= 0 ) { container.textContent = 'Event has started'; return; }
        const d = Math.floor( diff / 86400000 );
        const h = Math.floor( ( diff % 86400000 ) / 3600000 );
        const m = Math.floor( ( diff % 3600000 ) / 60000 );
        const s = Math.floor( ( diff % 60000 ) / 1000 );
        container.innerHTML = `${d}d ${h}h ${m}m ${s}s`;
    };
    update();
    setInterval( update, 1000 );
}
```

Output the UTC timestamp from PHP via `wp_localize_script`. No countdown library dependency.

**Confidence:** HIGH — vanilla countdown timers are trivially implemented. No library justified.

---

## Database Layer — v2.0 Changes

### No New Custom Tables Required for v2.0

| Data | Storage | Rationale |
|------|---------|-----------|
| Structured address fields | Separate post meta keys | Queryable by city/country if needed later |
| Sessions / Agenda | Serialized post meta `_bb_event_sessions` | Display-only, not queryable per-row |
| FAQ items | Serialized post meta `_bb_event_faqs` | Display-only |
| Custom registration field schemas | Serialized post meta `_bb_event_reg_fields` | Per-event config, not queried independently |
| Custom field answers (per attendee) | `meta` column on existing RSVP table | Attendee answers are already tied to an RSVP row |
| Speaker CPT | `wp_posts` (CPT) | Rich content, reusable across events |
| Speaker-to-event | Post meta `_bb_event_speaker_ids` on event | Array of speaker post IDs |
| Event view count | Post meta `_bb_event_view_count` | Simple integer counter |
| Event type / online details | Post meta (separate keys) | Scalar values, no relational queries |

**Decision: extend the existing RSVP table** to add a `meta` column (JSON or serialized) for storing custom registration field answers per attendee. Adding a full `_bb_event_rsvp_meta` sub-table is premature for v2.0 — the answers are read once (on the attendee list / CSV export), and a JSON column in the existing row is sufficient.

If custom field answer querying becomes a requirement (e.g., "how many attendees selected Option A"), migrate to a sub-table at that point (v3.0 concern).

**Confidence:** MEDIUM — this is a pragmatic v2.0 decision. The trade-off (future migration cost vs current simplicity) is clearly documented.

---

## Composer / npm Changes for v2.0

### No New PHP Libraries Required

All v2.0 features are implementable with:
- Native WordPress APIs (`register_taxonomy`, `register_post_type`, `add_term_meta`, `get_post_meta`, `$wpdb`, `fputcsv`)
- Existing vendored dependencies (php-rrule already present)

Do NOT add:
- CMB2 — native metabox + custom JS is sufficient
- ACF — commercial/strategic concerns, overkill
- PhpSpreadsheet — XLSX not required
- Any geocoding PHP library — use Google Maps Embed URL directly

### No New npm Packages Required

Do NOT add:
- Leaflet.js as a default npm dependency — enqueue conditionally from CDN with SRI hash, or vendor the minified build directly in `/assets/vendor/leaflet/`. Adding it to `package.json` complicates the build for a feature that may be toggled off.
- Any React/Vue component library — would conflict with BuddyBoss theme JS context
- Countdown timer library — vanilla JS is 20 lines
- Sortable.js / drag-and-drop — v2.0 does not require session reordering by drag

---

## Alternatives Considered

| Category | Recommended | Alternative | Why Not |
|----------|-------------|-------------|---------|
| Maps | Google Maps Embed API (free iframe) | Maps JavaScript API | Requires billing account setup on every end-user site; $100+/month at Starter tier as of March 2025 |
| Maps (no Google) | Leaflet.js + OpenStreetMap | Google Maps only | Leaflet is the correct fallback for privacy-conscious or non-Google sites — build as a toggle, not a replacement |
| Repeatable meta | Native serialized post meta | CMB2 | CMB2 maintenance declining; overkill for 3 data types; native approach has zero dependencies |
| Repeatable meta | Native serialized post meta | ACF Pro | Commercial license; WP Engine acquisition creates strategic concerns for BuddyBoss commercial plugin |
| Sessions storage | Serialized post meta | Custom sub-table | Sub-table adds schema migration complexity with no query benefit at v2.0 scale |
| Speakers storage | Custom Post Type | Serialized meta | Speakers have rich content, are reused across events, and need their own admin listing — CPT is correct |
| CSV export | Native fputcsv | PhpSpreadsheet | XLSX not required; PhpSpreadsheet adds ~2MB to plugin; fputcsv is sufficient |
| Countdown timer | Vanilla JS (~20 lines) | Third-party plugin dependency | Introducing a plugin dependency for 20 lines of JS is unnecessary coupling |
| Front-end form | Extend existing vanilla JS wizard | React/Vue form library | Framework conflicts with BuddyBoss theme JS environment; was ruled out in v1.0 |

---

## What NOT to Add

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| Maps JavaScript API (paid) | Requires billing account on every BuddyBoss site using the plugin; Starter plan $100+/month as of March 2025 | Google Maps Embed API (free iframe) |
| CMB2 | Declining maintenance, overkill for 3 structured meta types | Native WP metabox + 100 lines of custom JS |
| ACF (Free or Pro) | WP Engine acquisition creates distribution/licensing concerns; ACF Pro is commercial | Native `register_post_meta()` + custom metaboxes |
| PhpSpreadsheet | ~2MB library for XLSX output not in spec | `fputcsv()` for CSV |
| React or Vue | Conflicts with Gutenberg and BuddyBoss theme JS; already ruled out in v1.0 | Vanilla JS + existing wizard pattern |
| Sortable.js | Session drag-reorder not in v2.0 spec | Plain HTML add/remove rows |
| jquery-ui-datepicker | Bundled with WP but outdated; already excluded in v1.0 | `<input type="datetime-local">` or Flatpickr (already in v1.0 or use native) |

---

## Stack Patterns by Feature Area

**If showing a map with a known address (default):**
- Generate a Google Maps Embed API iframe URL from structured address meta
- No JS required — pure PHP template output
- API key optional; works without one via share-URL fallback

**If site owner wants no Google dependency:**
- Toggle in event settings: "Map provider: Google Maps / OpenStreetMap"
- Load Leaflet.js + tile layer from CDN with SRI hash
- Geocode address to lat/lng via Nominatim at event save time; cache in post meta

**If organizer is submitting from front-end:**
- REST endpoint `POST /wp-json/buddyboss-events/v1/submissions` creates a `pending` post
- Admin approval changes status to `publish`
- Existing vanilla JS wizard handles the multi-step UI
- Dynamic repeater rows via 50 lines of custom JS, no library

**If exporting attendee data:**
- `admin_post` hook + `fputcsv()` + `$wpdb` query
- Include dynamic column headers derived from `_bb_event_reg_fields` schema
- Stream directly to browser, no temp file needed

**If building the Sessions/Agenda UI:**
- Sessions stored as serialized array in post meta
- Admin UI: PHP-rendered metabox with JS add/remove rows (no library)
- Front-end: PHP template loop over `get_post_meta( $id, '_bb_event_sessions', true )`
- Speaker references: array of Speaker CPT post IDs — resolved to speaker names/photos via `get_post()` at render time

---

## Version Compatibility

| Package | Compatible With | Notes |
|---------|-----------------|-------|
| Leaflet 1.9.x | WordPress 6.4+, all modern browsers | Leaflet 2.0 is alpha (August 2025) — use 1.9.x for now; monitor 2.0 stable release |
| Google Maps Embed API | No version concern | iframe-based, no JS SDK to version |
| Native term meta | WordPress 4.4+ | Well below the WP 6.4+ minimum already set |
| `fputcsv()` | PHP 8.1+ | Core PHP function, no compatibility concerns |

---

## Sources

| Claim | Source | Confidence |
|-------|--------|------------|
| Maps JavaScript API billing required, Starter ~$100/month | [Google Maps pricing documentation via web search, March 2025](https://developers.google.com/maps/third-party-platforms/wordpress/generate-api-key) | HIGH |
| Maps Embed API free with no usage limits | [Google Embed API overview](https://developers.google.com/maps/documentation/embed/get-started) | HIGH |
| Term meta available since WP 4.4 | WordPress developer docs (training data, confirmed stable) | HIGH |
| Serialized meta performance trade-off | [ACF meta query best practices](https://www.advancedcustomfields.com/blog/wordpress-post-meta-query/); [Meta Box database optimization](https://metabox.io/optimizing-database-custom-fields/) | MEDIUM |
| CMB2 maintenance status | [CMB2 GitHub](https://github.com/CMB2/CMB2) — last reviewed training data Aug 2025; verify activity at time of use | MEDIUM |
| Leaflet 1.9.x current stable; 2.0 alpha August 2025 | [Leaflet.js changelog](https://leafletjs.com/) via web search | MEDIUM |
| fputcsv + admin_post pattern | Multiple WordPress developer sources, training data | HIGH |
| Speakers as CPT is standard in events plugins | Eventin documentation, The Events Calendar pattern — training data | HIGH |

---

*Stack research for: BuddyBoss Events Plugin v2.0 feature additions*
*Researched: 2026-03-17*
