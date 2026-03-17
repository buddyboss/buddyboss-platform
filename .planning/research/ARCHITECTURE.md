# Architecture Research

**Domain:** BuddyBoss Events WordPress Plugin — v2.0 feature additions
**Researched:** 2026-03-17
**Confidence:** HIGH — based on direct codebase inspection, not training assumptions

---

## Standard Architecture

### System Overview

```
┌──────────────────────────────────────────────────────────────────────┐
│                        BP_Events_Component                            │
│            (BP_Component subclass — registered at bp_setup_components)│
│   setup_globals() · includes() · late_includes() · setup_nav()       │
│   rest_api_init() · setup_adminbar_nav()                              │
└────────────────────────┬─────────────────────────────────────────────┘
                         │ wires together via includes()
     ┌───────────────────┼────────────────────────────────┐
     ▼                   ▼                                ▼
┌──────────────┐  ┌──────────────────┐           ┌────────────────────┐
│  bp-events-  │  │  screens/        │           │  REST API Layer    │
│  functions   │  │  create.php      │           │                    │
│  .php        │  │  single/home.php │           │ BP_REST_Events_    │
│              │  │  single/edit.php │           │ Endpoint           │
│ CRUD: create │  │  directory.php   │           │                    │
│ update, del  │  │  profile/        │           │ BP_REST_Events_    │
│ query, perms │  │    attending.php │           │ Settings_Endpoint  │
└──────┬───────┘  │    hosting.php   │           └────────────────────┘
       │          └──────────────────┘
       ▼
┌──────────────────────────────────────────────────────────────────────┐
│                        BP_Event (class-bp-event.php)                  │
│         populate() · save() · delete() · user_can_view()             │
│         user_can_edit()                                               │
└────────────────────────┬─────────────────────────────────────────────┘
                         │ reads/writes
     ┌───────────────────┼─────────────────────────────────────────────┐
     ▼                   ▼                   ▼                         ▼
┌──────────────┐  ┌──────────────┐  ┌───────────────┐  ┌─────────────┐
│  bb_events   │  │ bb_eventmeta │  │bb_event_      │  │bb_event_    │
│  (main table)│  │ (meta API,   │  │attendees      │  │invites      │
│              │  │  placeholder)│  │               │  │             │
│ id, title,   │  │ event_id,    │  │ event_id,     │  │ event_id,   │
│ slug, type,  │  │ meta_key,    │  │ user_id,      │  │ inviter_id, │
│ venue_*,     │  │ meta_value   │  │ status,       │  │ invitee_id, │
│ virtual_*,   │  │              │  │ ticket_id,    │  │ status      │
│ recurrence_  │  │ (no PHP API  │  │ order_id      │  │             │
│ rule, status │  │  exists yet) │  │               │  │             │
└──────────────┘  └──────────────┘  └───────────────┘  └─────────────┘
```

**Key architectural fact:** The plugin does NOT use WordPress CPTs. All event data lives in custom tables accessed directly via `$wpdb`. The `bb_eventmeta` table exists in the schema but has no PHP meta API functions yet — it is the designated extension point for additional per-event key/value data.

---

## Component Responsibilities

| Component | Responsibility | File(s) |
|-----------|----------------|---------|
| BP_Events_Component | BP_Component subclass; boots all includes, registers nav, REST, tables | `classes/class-bp-events-component.php` |
| bp-events-functions.php | All CRUD functions (`bp_events_create_event`, `get_events`, etc.), schema install, permission checks | `bp-events-functions.php` |
| BP_Event | Event row model; populate from DB, save, delete, user_can_view/edit | `classes/class-bp-event.php` |
| Screen functions | One file per URL: create, single/home, single/edit, directory, profile/attending, profile/hosting | `screens/` |
| BP_REST_Events_Endpoint | WP_REST_Controller for /buddyboss/v1/events CRUD + RSVP sub-routes | `classes/class-bp-rest-events-endpoint.php` |
| bp-events-activity.php | Activity feed integration (hooked to bp_events_after_event_save) | `bp-events-activity.php` |
| bp-events-group-extension.php | BP_Group_Extension for groups tab | `bp-events-group-extension.php` |
| bp-events-admin.php | Admin menu, WP_List_Table, settings page | `bp-events-admin.php` |
| bp-events-cache.php | wp_cache group registration, cache invalidation on save/delete | `bp-events-cache.php` |
| bp-events-loader.php | Asset enqueueing (create wizard JS, single event JS), cron scheduling | `bp-events-loader.php` |

---

## Recommended Project Structure for v2.0

```
src/
├── bp-core/                        # (existing) Core BP infrastructure helpers
├── bp-events/
│   ├── assets/js/
│   │   ├── bp-events-create.js     # MODIFIED — add taxonomy, hybrid, sessions steps
│   │   ├── bp-events-single.js     # MODIFIED — sessions/speakers accordion, FAQ, timer
│   │   └── bp-events-organizer.js  # NEW — organizer dashboard JS (analytics, CSV)
│   ├── classes/
│   │   ├── class-bp-event.php               # MODIFIED — add category_ids, tag_ids accessors
│   │   ├── class-bp-events-component.php    # MODIFIED — new nav item: Organizer
│   │   ├── class-bp-rest-events-endpoint.php # MODIFIED — taxonomy, sessions, reg-fields in schema
│   │   ├── class-bp-rest-events-settings-endpoint.php # no change
│   │   ├── class-bp-speaker.php             # NEW — Speaker row model
│   │   └── class-bp-event-session.php       # NEW — Session row model
│   ├── screens/
│   │   ├── create.php              # no change (wizard scaffolding stays)
│   │   ├── directory.php           # MODIFIED — taxonomy filter UI
│   │   ├── single/
│   │   │   ├── home.php            # MODIFIED — pass sessions/speakers/FAQ to template
│   │   │   └── edit.php            # MODIFIED — pass same to edit template
│   │   └── profile/
│   │       ├── attending.php       # no change
│   │       ├── hosting.php         # no change
│   │       └── organizer.php       # NEW — organizer dashboard screen function
│   ├── bp-events-functions.php     # MODIFIED — meta API, taxonomy helpers, reg-field helpers
│   ├── bp-events-loader.php        # MODIFIED — enqueue organizer assets
│   ├── bp-events-filters.php       # MODIFIED — taxonomy filter on directory query
│   ├── bp-events-admin.php         # MODIFIED — Speakers/Categories admin sub-menus
│   ├── bp-events-activity.php      # no change
│   ├── bp-events-cache.php         # MODIFIED — add session and speaker cache groups
│   ├── bp-events-template.php      # MODIFIED — template tags for sessions/speakers/FAQ
│   └── bp-events-group-extension.php # no change
└── bp-templates/
    └── bp-nouveau/readylaunch/events/
        ├── create.php              # MODIFIED — taxonomy step, hybrid fields
        ├── index.php               # MODIFIED — category/tag filter UI
        ├── event-card.php          # no change
        ├── events-loop.php         # no change
        └── single/                 # NEW folder
            ├── home.php            # NEW (or MODIFIED single template)
            ├── sessions.php        # NEW — sessions/agenda partial
            ├── speakers.php        # NEW — speakers partial
            └── organizer-dash.php  # NEW — analytics dashboard partial
```

---

## Architectural Patterns

### Pattern 1: The `bb_eventmeta` Meta API (Mirror bp_groupmeta pattern)

**What:** Implement `bp_event_get_meta()`, `bp_event_update_meta()`, `bp_event_add_meta()`, `bp_event_delete_meta()` backed by `bb_eventmeta`. This table exists but has no API yet. The pattern is identical to BuddyPress's `bp_groupmeta_*` functions.

**When to use:** For per-event extension data that is single-valued, rarely queried in bulk, and doesn't need its own table. Good candidates: Google Maps embed URL, meeting ID/password, online platform label, FAQ content (JSON array), countdown timer settings, external event link.

**When NOT to use:** For anything that needs to be queried across events (e.g., "find all events in category X") or that has a many-to-one relationship with the event (sessions, speakers).

**Trade-offs:** Low implementation cost, consistent with BP patterns, no schema migration risk. Downside: meta_value is longtext with no type enforcement; bulk queries across events on a meta key require a JOIN.

```php
// Pattern example — mirrors bp_groups_get_groupmeta() exactly
function bp_event_get_meta( $event_id, $meta_key = '', $single = true ) {
    global $wpdb;
    $bp = buddypress();
    return bp_get_meta( $event_id, $meta_key, $single,
        $bp->events->table_name_meta, 'event_id', 'bp_event_meta' );
}
```

### Pattern 2: Dedicated Custom Table for Sessions (`bb_event_sessions`)

**What:** Sessions (agenda items) are stored in a new custom table, not as serialized post meta. Each row is one session, keyed to `event_id`.

**When to use:** Sessions are the right candidate for a custom table because:
- An event can have 0-N sessions (true one-to-many)
- Sessions need individual CRUD (add, reorder, delete one session)
- Sessions need speaker assignments (JOIN to a speakers table)
- Sessions display in ordered list (needs `sort_order` column, not a JSON blob key)
- Query pattern: "get all sessions for event X" — trivial with a table, awkward with serialized meta

**Trade-offs:** Requires schema migration (dbDelta is safe for adding tables). Adds one more table. Fully worth it vs JSON-in-meta because individual session operations are cleaner and the data is relational.

**Schema:**
```sql
CREATE TABLE {prefix}bb_event_sessions (
    id           bigint(20)   NOT NULL AUTO_INCREMENT,
    event_id     bigint(20)   NOT NULL DEFAULT 0,
    title        varchar(255) NOT NULL DEFAULT '',
    description  text         NOT NULL DEFAULT '',
    start_time   time                  DEFAULT NULL,
    end_time     time                  DEFAULT NULL,
    sort_order   int(11)      NOT NULL DEFAULT 0,
    date_created datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (id),
    KEY event_id (event_id),
    KEY sort_order (sort_order)
)
```

**Session-speaker relationship table** (`bb_event_session_speakers`):
```sql
CREATE TABLE {prefix}bb_event_session_speakers (
    session_id   bigint(20) NOT NULL,
    speaker_id   bigint(20) NOT NULL,
    sort_order   int(11)    NOT NULL DEFAULT 0,
    PRIMARY KEY (session_id, speaker_id)
)
```

### Pattern 3: Speakers as a Custom Table (`bb_event_speakers`), NOT a CPT

**What:** Speakers are stored in a custom table, not a WordPress CPT.

**Rationale for NOT using CPT:**
- Speakers exist only in relation to events — they are not standalone publishable content
- A CPT would appear in search, feeds, sitemaps by default — requires suppression
- CPT `post_status`, `post_author`, `post_date` fields are irrelevant noise
- CPT benefits (built-in REST, WP_Query, revisions) don't apply — speaker queries are always "speakers for event X" or "speakers for session Y", not global post loops
- The plugin already uses custom tables for all event data — consistency argues for the same approach

**Schema:**
```sql
CREATE TABLE {prefix}bb_event_speakers (
    id           bigint(20)   NOT NULL AUTO_INCREMENT,
    event_id     bigint(20)   NOT NULL DEFAULT 0,
    user_id      bigint(20)            DEFAULT NULL,  -- optional WP user link
    name         varchar(255) NOT NULL DEFAULT '',
    title        varchar(255) NOT NULL DEFAULT '',
    bio          text         NOT NULL DEFAULT '',
    photo_id     bigint(20)            DEFAULT NULL,  -- WP attachment ID
    sort_order   int(11)      NOT NULL DEFAULT 0,
    date_created datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (id),
    KEY event_id (event_id),
    KEY user_id (user_id)
)
```

**`user_id` is nullable.** A speaker can be an external person (not a site member). When `user_id` is set, the speaker card can link to the WP/BP user profile.

### Pattern 4: Taxonomies via `register_taxonomy()` (NOT custom tables)

**What:** Event categories (hierarchical) and event tags (flat) use WordPress's native taxonomy system registered to the `bb_event` object type — but the object type is a **virtual CPT slug**, not actual CPT posts.

**The challenge:** The existing `bb_events` table is NOT a CPT — events don't have `wp_posts` rows. WordPress taxonomies (`wp_term_relationships`) normally link to a `object_id` that is a post ID. To use native `register_taxonomy()`, a lightweight CPT (`bp_event`) must be registered with `publicly_queryable => false`, `show_in_rest => false`, `rewrite => false` — purely to give the taxonomy system a valid object type. Actual event data stays in `bb_events`. The CPT is a shim.

**Alternative:** Store taxonomy assignments in `bb_eventmeta` as a JSON array of term IDs. Simpler to implement but loses: `wp_term_relationships` queries, tag cloud widgets, taxonomy REST API endpoints, admin taxonomy management UI.

**Recommendation:** Use the shim CPT approach. The CPT never holds content — it's created only when needed for taxonomy term assignments. `wp_insert_post()` creates a shell post for each event with the same ID (impossible to guarantee without coordination) — actually, the cleaner approach is:

**Revised recommendation:** Store category and tag term IDs in `bb_eventmeta` as a serialized array (`_category_ids`, `_tag_ids`). Register the taxonomies with `register_taxonomy()` against a shell CPT for admin UI, but manage term assignments manually via `wp_set_object_terms()` using the event's ID as the object ID even if it isn't a real post ID. WordPress stores taxonomy relationships in `wp_term_relationships` using any integer as `object_id` — no strict FK constraint. This is how BuddyBoss itself stores group taxonomy data.

**Confidence:** MEDIUM. Verify that `wp_set_object_terms()` accepts non-post object IDs (it does — `object_id` is just a BIGINT with no FK). The BuddyBoss Platform uses this pattern for group types.

**Taxonomy registration:**
```php
// Register against 'bp_event' as a non-public CPT shim, or pass
// any string — WP doesn't validate the object type against real CPTs
// until display context. Use the existing event ID as object_id.
register_taxonomy( 'bb_event_category', 'bb_event', array(
    'hierarchical'      => true,
    'show_in_rest'      => true,   // enables admin UI + REST
    'show_admin_column' => true,
    'rewrite'           => array( 'slug' => 'event-category' ),
) );
register_taxonomy( 'bb_event_tag', 'bb_event', array(
    'hierarchical' => false,
    'show_in_rest' => true,
    'rewrite'      => array( 'slug' => 'event-tag' ),
) );
```

### Pattern 5: Custom Registration Fields as JSON Schema in `bb_eventmeta`

**What:** Per-event registration fields are stored as a JSON-encoded schema in a single `bb_eventmeta` row with key `_registration_fields`.

**Schema stored:**
```json
[
  { "id": "uuid", "type": "text", "label": "Company Name", "required": true },
  { "id": "uuid", "type": "dropdown", "label": "T-Shirt Size",
    "options": ["S","M","L","XL"], "required": false },
  { "id": "uuid", "type": "checkbox", "label": "Dietary needs", "required": false }
]
```

**Why meta, not a dedicated table:** Registration fields are a schema definition (read once per event, written on event save). They are not individual queryable records. The JSON blob is small (<5 KB for any realistic field set). A dedicated table would have one row per field per event and adds schema complexity for marginal benefit.

**Collected registration field responses** (attendee answers) go in a separate `bb_event_reg_responses` table so they are queryable per attendee:
```sql
CREATE TABLE {prefix}bb_event_reg_responses (
    id           bigint(20)   NOT NULL AUTO_INCREMENT,
    event_id     bigint(20)   NOT NULL DEFAULT 0,
    attendee_id  bigint(20)   NOT NULL DEFAULT 0,  -- bb_event_attendees.id
    field_id     varchar(36)  NOT NULL DEFAULT '',  -- UUID from schema
    field_value  text         NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY event_id (event_id),
    KEY attendee_id (attendee_id)
)
```

### Pattern 6: Front-End Event Submission via Organizer Screen Function

**What:** The `/events/submit` URL uses the same BP_Component `late_includes()` screen function pattern as `/events/create` (which already exists). The organizer dashboard is a new profile sub-nav tab at `/members/{user}/events/organizer`.

**How it integrates:**

In `BP_Events_Component::late_includes()`, add:
```php
} elseif ( bp_is_action_variable( 'submit', 0 ) ) {
    require $this->path . 'bp-events/screens/submit.php';
}
```

And in `BP_Events_Component::setup_nav()`, add a new sub-nav:
```php
$sub_nav[] = array(
    'name'            => __( 'Organizer', 'buddyboss' ),
    'slug'            => 'organizer',
    'parent_url'      => $events_link,
    'parent_slug'     => $this->slug,
    'screen_function' => 'bp_events_screen_organizer',
    'position'        => 25,
    'user_has_access' => bp_is_my_profile() && bp_events_user_is_organizer(),
);
```

**Approval workflow** uses the existing `status` field on `bb_events`. Front-end submissions create events with `status = 'pending'` (already supported). Admin approves by updating to `'published'`. No new columns needed — only a new admin view filtered to pending events.

### Pattern 7: Analytics Queries from Existing Tables

**What:** Analytics data is derived from the existing `bb_events` and `bb_event_attendees` tables, not stored in a separate analytics table. An optional `bb_event_views` table tracks page views.

**Aggregate queries needed for organizer dashboard:**

```sql
-- Attendance count per event
SELECT COUNT(*) FROM bb_event_attendees
WHERE event_id = %d AND status = 'registered';

-- Waitlist count
SELECT COUNT(*) FROM bb_event_attendees
WHERE event_id = %d AND status = 'waitlisted';

-- Events by organizer with attendance + capacity
SELECT e.id, e.title, e.start_date, e.capacity,
       COUNT(a.id) AS attendee_count
FROM bb_events e
LEFT JOIN bb_event_attendees a ON e.id = a.event_id AND a.status = 'registered'
WHERE e.organizer_id = %d
GROUP BY e.id
ORDER BY e.start_date DESC;
```

**View tracking** — add a lightweight `bb_event_views` table:
```sql
CREATE TABLE {prefix}bb_event_views (
    id         bigint(20) NOT NULL AUTO_INCREMENT,
    event_id   bigint(20) NOT NULL DEFAULT 0,
    user_id    bigint(20)          DEFAULT NULL,
    ip_hash    varchar(64)         DEFAULT NULL,  -- hashed for privacy
    viewed_at  datetime   NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (id),
    KEY event_id (event_id),
    KEY viewed_at (viewed_at)
)
```

**Important:** Insert a view record only once per user per event per day to avoid counting reloads. Use `INSERT IGNORE` with a `UNIQUE KEY (event_id, user_id, DATE(viewed_at))`. For logged-out users, use IP hash with a 24-hour transient to deduplicate.

**CSV export** is a server-side PHP function that runs `bp_events_get_events()` with organizer filter, JOINs attendees, and `fputcsv()` to a streamed response — no third-party library needed.

---

## Data Flow

### Front-End Event Submission Flow

```
Member visits /events/submit
    |
    v
bp_events_submit_setup() [screens/submit.php]
- auth_redirect() if not logged in
- bp_events_user_can_create() permission check
- add_action('bp_template_content', 'bp_events_submit_content')
    |
    v
bp-events-create.js renders wizard (same JS as admin create)
Step: Basic info -> Taxonomy -> Location/Hybrid -> Sessions -> Speakers -> Reg fields -> Preview
    |
    v
POST /buddyboss/v1/events  [BP_REST_Events_Endpoint::create_item()]
- Sets status = 'pending' (if moderation on) or 'published'
- Saves taxonomy term assignments via wp_set_object_terms()
- Saves sessions to bb_event_sessions via new BP_Event_Session class
- Saves speakers to bb_event_speakers via new BP_Speaker class
- Saves reg field schema to bb_eventmeta (_registration_fields)
    |
    v
do_action('bp_events_after_event_save', $event)
- bp_activity_add() for activity feed
- bp_events_notify_admin_pending() if status = 'pending'
```

### Analytics Data Flow

```
Member visits /members/{user}/events/organizer
    |
    v
bp_events_screen_organizer() [screens/profile/organizer.php]
    |
    v
bp_events_get_organizer_analytics( $user_id )
- bp_events_get_events( ['organizer_id' => $user_id] )
- For each event: COUNT attendees from bb_event_attendees
- COUNT views from bb_event_views
    |
    v
Template: organizer-dash.php renders table + CSV download button
    |
    v
CSV request: GET /buddyboss/v1/events/{id}/export
[BP_REST_Events_Endpoint::export_item()]
- Queries attendees + reg_responses
- Streams CSV response with Content-Disposition: attachment
```

### RSVP with Registration Fields Flow

```
Member clicks RSVP on event with custom fields
    |
    v
bp-events-single.js detects _registration_fields on event
- Renders dynamic form from JSON schema
    |
    v
POST /buddyboss/v1/events/{id}/rsvp  [existing endpoint]
- Extended to accept 'registration_fields': { field_id: value, ... }
- Validates required fields
    |
    v
bb_event_attendees row created (existing)
bb_event_reg_responses rows created (one per field answer)
```

---

## New vs Modified Components

### New Files

| File | Purpose |
|------|---------|
| `classes/class-bp-speaker.php` | Speaker row model (CRUD against bb_event_speakers) |
| `classes/class-bp-event-session.php` | Session row model (CRUD against bb_event_sessions) |
| `screens/profile/organizer.php` | Organizer dashboard screen function |
| `screens/submit.php` | Front-end submission screen function (mirrors create.php) |
| `bp-templates/.../events/single/` | Single event template partials (sessions, speakers, FAQ) |

### Modified Files

| File | What Changes |
|------|-------------|
| `classes/class-bp-events-component.php` | Add Organizer nav item; register new REST controllers |
| `classes/class-bp-rest-events-endpoint.php` | Add taxonomy fields, sessions, speakers, reg-fields to schema; add /export route |
| `classes/class-bp-event.php` | Add `category_ids`, `tag_ids` accessors (reads from wp_term_relationships) |
| `bp-events-functions.php` | Add meta API functions; taxonomy query helpers; session/speaker helpers; analytics functions |
| `bp-events-filters.php` | Extend `bp_events_get_events_where_clauses` filter for taxonomy filtering |
| `bp-events-admin.php` | Add Speakers and Categories admin sub-menus |
| `bp-events-loader.php` | Enqueue organizer dashboard JS; enqueue taxonomy picker assets |
| `bp-events-cache.php` | Add `bp_event_sessions` and `bp_event_speakers` cache groups |
| `bp-events-template.php` | Add template tags: `bp_event_get_sessions()`, `bp_event_get_speakers()`, `bp_event_get_faq()` |
| `assets/js/bp-events-create.js` | Add wizard steps: taxonomy, hybrid details, sessions, speakers, reg fields |
| `assets/js/bp-events-single.js` | Sessions accordion, FAQ accordion, countdown timer, conditionally show reg-field form |

### New Database Tables

| Table | Purpose |
|-------|---------|
| `bb_event_sessions` | Agenda/session rows per event |
| `bb_event_session_speakers` | Many-to-many: sessions <-> speakers |
| `bb_event_speakers` | Speaker records per event |
| `bb_event_reg_responses` | Attendee answers to custom registration fields |
| `bb_event_views` | Page view tracking for analytics |

### Extended Existing Tables (via `bb_eventmeta`)

| Meta Key | Data Stored |
|----------|-------------|
| `_registration_fields` | JSON schema array of custom reg field definitions |
| `_google_maps_url` | Google Maps embed URL (constructed from lat/lng) |
| `_virtual_meeting_id` | Meeting ID (Zoom/Teams/etc.) |
| `_virtual_meeting_password` | Meeting password |
| `_virtual_platform_label` | Human label: "Zoom", "Google Meet", etc. |
| `_faq` | JSON array of {question, answer} objects |
| `_external_event_url` | External event link (Eventbrite, etc.) |
| `_venue_city` | Structured address: city |
| `_venue_state` | Structured address: state/province |
| `_venue_zip` | Structured address: postal code |
| `_venue_country` | Structured address: country |

**Note on address fields:** `venue_address` (the full address string) already exists in `bb_events`. The structured sub-fields (`_venue_city`, etc.) go in `bb_eventmeta` to avoid an `ALTER TABLE` on the main events table. A future migration can promote them to first-class columns if query filtering on city/country is needed.

---

## Build Order for v2.0

Dependencies flow upward — each phase requires the layer above it to be complete.

```
Phase 4a: Meta API + Taxonomy Foundation
├── Implement bp_event_get/update/add/delete_meta() backed by bb_eventmeta
├── register_taxonomy() for bb_event_category and bb_event_tag
├── Taxonomy admin UI (uses WP native — minimal code)
└── Filter extension: bp_events_get_events() supports category/tag filtering
    (via wp_term_relationships JOIN — verifiable with simple queries)

Phase 4b: Data Enrichment (parallel-safe, no cross-dependencies)
├── Structured address sub-fields (bb_eventmeta keys, no table change)
├── Google Maps embed (meta key _google_maps_url + template partial)
├── Hybrid event type extension (extend existing 'type' field in bb_events)
├── Online meeting detail meta keys (ID, password, platform label)
├── FAQ section (meta key _faq JSON + template partial)
├── Countdown timer (meta key or computed from start_date — client-side JS)
└── External event link (meta key _external_event_url)

Phase 4c: Sessions + Speakers (depends on: 4a for meta API patterns)
├── dbDelta: create bb_event_sessions, bb_event_session_speakers, bb_event_speakers
├── BP_Event_Session class (CRUD)
├── BP_Speaker class (CRUD)
├── REST API: sessions and speakers as nested resources under /events/{id}
├── Admin UI: session/speaker management within event edit
└── Template partials: sessions accordion + speakers grid on single event

Phase 4d: Front-End Submission + Organizer Dashboard
(depends on: 4a taxonomy, 4b enrichment, 4c sessions/speakers)
├── screens/submit.php (screen function — mirrors create.php)
├── BP_Events_Component nav: add Organizer sub-tab
├── screens/profile/organizer.php (organizer dashboard screen function)
├── Template: organizer-dash.php (event list, attendance counts, pending drafts)
├── Approval workflow: admin view filtered to pending events (extends existing admin)
└── Enqueue submit-specific assets if needed (can reuse bp-events-create.js)

Phase 4e: Custom Registration Fields
(depends on: 4d front-end submission — reg fields are only useful with submission flow)
├── Meta API: save/retrieve _registration_fields JSON schema
├── REST API: extend RSVP endpoint to accept and validate field answers
├── db: create bb_event_reg_responses table
├── JS: dynamic form renderer from JSON schema (in bp-events-single.js)
└── Admin: display collected responses per event

Phase 4f: Analytics
(depends on: 4d organizer dashboard, 4e reg responses for complete attendee data)
├── db: create bb_event_views table
├── View tracking: hook into single event screen, insert view record
├── Analytics functions in bp-events-functions.php
├── REST API: /events/{id}/export endpoint (CSV streaming)
└── Template: analytics table in organizer-dash.php
```

**Rationale for this order:**
- 4a (meta API + taxonomy) is first because multiple later features depend on `bp_event_get/update_meta()` and taxonomy filtering. Build it once, use it everywhere.
- 4b (data enrichment) has no inter-feature dependencies — it's pure meta key additions. Can be built in parallel with 4c by a second developer if needed.
- 4c (sessions/speakers) introduces new tables — dbDelta is forward-safe but should be tested before anything builds on top of the schema.
- 4d (front-end submission) intentionally comes after 4b and 4c so the submission wizard can include all enrichment fields and sessions/speakers in one pass. Building submission before sessions would require revisiting the wizard.
- 4e (reg fields) depends on submission working — reg fields are meaningless without a functioning submission and RSVP flow.
- 4f (analytics) is last because it queries the data created by all prior features. View tracking can be added early (it's a hook) but the dashboard UI requires the organizer screen from 4d.

---

## Anti-Patterns

### Anti-Pattern 1: Serialized Sessions in `bb_eventmeta`

**What people do:** Store the entire sessions array as a single `bb_eventmeta` row: `meta_key = '_sessions', meta_value = serialize([...])`.

**Why it's wrong:** Adding, removing, or reordering a single session requires deserializing the full array, mutating it in PHP, and reserializing — no atomicity. Assigning a speaker to one session means touching the whole sessions blob. Impossible to query "which events have sessions starting after 2pm". Cache invalidation must bust the entire event cache.

**Do this instead:** Use `bb_event_sessions` table with individual row CRUD. Each session is a first-class row with `event_id`, `sort_order`, and `id`.

### Anti-Pattern 2: Speakers as a WordPress CPT

**What people do:** Register `bp_event_speaker` as a CPT, store speaker bios in `post_content`, photo in featured image.

**Why it's wrong:** Speakers appear in WP search results, XML sitemaps, and feed queries unless explicitly suppressed. The CPT carries irrelevant fields (`post_status`, `post_author`, revisions). Speaker querying ("speakers for event X") requires a relationship meta field on the CPT — which is just reinventing a custom table with extra steps. Speaker list ordering requires a custom sort field anyway.

**Do this instead:** `bb_event_speakers` custom table. Speaker photo stored as WP attachment ID (column `photo_id`) — this is the only WP-native feature worth preserving.

### Anti-Pattern 3: `ALTER TABLE` on `bb_events` for v2.0 Fields

**What people do:** Add new columns (`venue_city`, `meeting_id`, `faq_content`) directly to `bb_events` via `ALTER TABLE` in an update migration.

**Why it's wrong:** `ALTER TABLE` on a large events table requires a table lock in MySQL (unless using `ALGORITHM=INSTANT` on MySQL 8.0+ or pt-online-schema-change on older). Sites with thousands of events could see downtime. dbDelta doesn't add columns to existing tables — it only creates tables. Adding columns via dbDelta requires a custom `ALTER TABLE` migration with version-check guards.

**Do this instead:** Put new per-event attributes in `bb_eventmeta`. Only promote to a first-class column (with a proper guarded migration) if query performance on that column becomes measurably necessary.

### Anti-Pattern 4: Mixing Front-End Submission and Admin Creation Code Paths

**What people do:** Add front-end submission as a branch inside the existing `/events/create` screen function with an `is_admin()` gate.

**Why it's wrong:** Admin creation (`/wp-admin/admin.php?page=bp-events-new`) and front-end submission (`/events/submit`) have different permission requirements, different moderation defaults, and will eventually have different UI. Mixing them in one screen function creates a conditional tangle.

**Do this instead:** Front-end submission is a new screen function in `screens/submit.php`, registered under a new URL action variable (`submit`). It calls the same underlying REST API (`POST /buddyboss/v1/events`) as the admin form. The REST endpoint is the single source of truth — both paths converge there.

### Anti-Pattern 5: Analytics via WP_Query or `get_posts()`

**What people do:** Use `get_posts()` to retrieve events for the organizer dashboard, then loop and call individual meta queries to get attendance counts.

**Why it's wrong:** The plugin doesn't use CPTs — events are in `bb_events`. Even if it did, N+1 queries (one `get_post_meta()` per event to get attendance count) are orders of magnitude slower than a single aggregate SQL query.

**Do this instead:** `bp_events_get_events(['organizer_id' => $user_id])` returns all events in two queries (one count, one IDs + hydration). Attendance counts come from a single GROUP BY query across `bb_event_attendees`. Never loop and query.

---

## Integration Points

### External Services

| Service | Integration Pattern | Notes |
|---------|---------------------|-------|
| Google Maps | Embed URL constructed from `venue_lat`/`venue_lng` (already in `bb_events`) stored in `bb_eventmeta._google_maps_url`. No server-side Google API call needed for embed. Geocoding (address -> lat/lng) requires Google Maps Geocoding API key stored in wp_options. | Geocoding on save only, not on every page load |
| Zoom/Meet/Teams | No API integration — organizer pastes meeting URL, ID, password manually. Stored in `bb_eventmeta`. | Deliberate scope constraint — no OAuth to third-party meeting platforms in v2.0 |

### Internal Boundaries

| Boundary | Communication | Notes |
|----------|---------------|-------|
| Sessions <-> Speakers | `bb_event_session_speakers` JOIN table | Many-to-many; one speaker can appear in multiple sessions |
| Events <-> Taxonomy | `wp_term_relationships` (standard WP). `object_id` = event's `bb_events.id` | WP does not enforce that object_id is a real post ID |
| Events <-> Reg Responses | `bb_event_reg_responses.event_id` + `attendee_id` FK to `bb_event_attendees.id` | Schema defined per-event in `bb_eventmeta._registration_fields` |
| Front-end Submission <-> REST API | Front-end submit wizard POSTs to `/buddyboss/v1/events` (same endpoint as admin create) | Moderation status controlled by `bp_events_moderation_enabled()` — same flag, same logic |
| Organizer Dashboard <-> Existing Attendee Table | Direct SQL aggregate queries on `bb_event_attendees` | No new tables needed for count queries |
| View Tracking <-> Single Event Screen | Hook: `add_action('bp_events_screen_single', 'bp_events_record_view')` | Insert into `bb_event_views`; deduplicate via transient |
| Analytics Export <-> REST API | New route: `GET /buddyboss/v1/events/{id}/export` on `BP_REST_Events_Endpoint` | Streams CSV; requires `manage_options` or organizer permission check |

---

## Scaling Considerations

| Scale | Architecture Adjustments |
|-------|--------------------------|
| 0-1k events, typical community site | All queries are fine with existing indexes. No changes needed. |
| 1k-10k events, active platform | Add index on `bb_event_sessions.event_id` (already in schema above). Cache organizer analytics with a 5-minute transient. `bb_event_views` table grows fast — purge rows older than 90 days via WP Cron. |
| 10k+ events | Analytics aggregate queries need `bb_event_views` indexed on `(event_id, viewed_at)` (already in schema). Consider materialized analytics: nightly cron pre-computes per-event stats into `bb_eventmeta`. Taxonomy term filtering via `wp_term_relationships` JOIN may need covering index. |

---

## Sources

- Direct codebase inspection of `class-bp-event.php`, `class-bp-events-component.php`, `bp-events-functions.php`, `bp-events-loader.php` (HIGH confidence — authoritative)
- WordPress `$wpdb` documentation and dbDelta behavior (HIGH confidence — established WP API)
- BuddyPress `bp_get_meta()` / groupmeta pattern (MEDIUM confidence — verify `bp_get_meta()` function signature against current BuddyBoss Platform version before implementation)
- WordPress `register_taxonomy()` accepting non-CPT object types (MEDIUM confidence — documented behavior, but verify `wp_set_object_terms()` accepts arbitrary integer object_id without CPT registration on current WP version)
- MySQL `INSERT IGNORE` with `UNIQUE KEY` for deduplication (HIGH confidence — standard MySQL)

---
*Architecture research for: BuddyBoss Events v2.0 feature additions*
*Researched: 2026-03-17*
