# Phase 1: Foundation + Event Management - Research

**Researched:** 2026-03-13
**Domain:** WordPress plugin development, BuddyBoss Platform component architecture, FullCalendar 6, PHP RRULE, WP REST API
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

**Recurring Event Editing**
- Modal choice when clicking Edit on any occurrence: "Edit this event / Edit this and following / Edit all events in series" — matches Google Calendar UX
- "Edit this and following" splits the series: the original series ends before the edited occurrence, and a new series is created from that point forward. No in-place RRULE mutation.
- Occurrences are stored as child rows in `bp_events` on publish — each child has its own row with `parent_event_id` set. Existing schema supports this.
- Pre-generate occurrences 2 years ahead on publish. A WP cron job extends the window as time passes.

**Calendar View**
- FullCalendar JS library — month/week/list support, recurring event awareness, REST-compatible
- Launch with Month + List views only. Week view deferred.
- Events loaded dynamically via REST API (existing `BP_REST_Events_Endpoint`) — no full page reload on month navigation
- Default view: honour the `bb_events_default_calendar_view` admin setting (defaults to month). Setting callback already exists in `bp-events-admin.php`.

**Event Creation Form**
- Multi-step wizard at `/events/create` — matches the `create.php` ReadyLaunch template stub that already exists
- Steps: Event Type → Basic Details → Date & Time → Location/Virtual → Recurrence (conditional) → Review & Publish
- Event type (in-person / virtual / hybrid) drives JS show/hide of venue vs virtual URL fields — no page reload
- Final step shows "Save Draft" and "Publish" buttons — satisfies EVNT-04. Status field in `BP_Event` already supports `draft`.

### Claude's Discretion
- Exact FullCalendar configuration options and event colour/styling
- Recurrence cron job scheduling interval
- Form validation error messaging patterns
- Loading/skeleton states on calendar month navigation

### Deferred Ideas (OUT OF SCOPE)
- "Tiered by plan level" permission enforcement (free/pro/plus/ultimate) — the permission hook (`bp_events_user_can_create`) is scaffolded; plan-tier detection wired in Phase 2 when BuddyBoss plan data is available
- Week view on calendar — deferred post-launch
- Drag-and-drop event rescheduling on calendar — deferred
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| EVNT-01 | Organizer can create an in-person event with venue name, address, and capacity limit | `BP_Event` class has all fields (`venue_name`, `venue_address`, `venue_lat`, `venue_lng`, `capacity`). `bp_events_create_event()` wired. Multi-step form wizard needed in JS. |
| EVNT-02 | Organizer can create a virtual event using BuddyBoss Zoom integration or Google Meet link | `BP_Event::$virtual_url` and `$virtual_type` (zoom/meet/other) already modelled. Event type `virtual` or `hybrid` drives JS field visibility. |
| EVNT-03 | Organizer can create a recurring event series (daily/weekly/monthly/custom) with RRULE-based recurrence and edit-this-only / edit-this-and-future options | `recurrence_rule` + `parent_event_id` in schema. REST routes `/occurrence` and `/series` already registered. Need: RRULE expansion via `rlanvin/php-rrule`, occurrence pre-generation on publish, WP cron window-extension job, JS recurrence UI step. |
| EVNT-04 | Organizer can save an event as draft or schedule a future publish date | `BP_Event::$status` supports `draft`. REST endpoint defaults new events to `draft`. Final wizard step needs Save Draft + Publish buttons. |
| EVNT-05 | Admin can configure per-group whether group events appear only in the group calendar or also on the main site calendar | `bb_events_public_group_site_calendar` option exists and is saved. `bp_events_get_events()` already has group privacy WHERE clause. Need: per-group override meta + query adjustment. |
| EVNT-06 | Events from private and hidden BuddyBoss groups are never visible on the main site calendar (enforced by group privacy rules, not optional) | Already enforced in `bp_events_get_events()` WHERE clause (`group.status = 'public'`). REST `get_items()` calls `user_can_view()` per event. No new code needed — verify test coverage. |
| ADMN-01 | Admin can configure who is permitted to create events site-wide | `bb_events_creation_permission` option (admins/organizers/members) exists with UI in `bp-events-admin.php` and settings tab in `bp-admin-setting-events.php`. `bp_events_user_can_create()` reads this option. Already functional. |
| ADMN-02 | Admin has an event moderation queue — submitted events require approval before going live (toggleable) | `bb_events_moderation_enabled` toggle exists. `bp_events_create_event()` sets status to `pending` when enabled. `BP_Events_List_Table` shows Pending tab with Approve action link. Need: approve AJAX/REST handler wired to the Approve row action. |
| ADMN-03 | Admin can view a platform-wide dashboard showing all events, ticket sales revenue, and commission earned | All Events list table (WP_List_Table) exists. Revenue dashboard page stub exists — returns Phase 2 placeholder. For Phase 1: basic event list + stats (total events, published/pending counts). Revenue stats deferred to Phase 2. |
| ADMN-04 | Users can report an event as offensive or inappropriate, routed into the existing BuddyBoss moderation system | BuddyBoss moderation system uses `BP_Moderation_Abstract` subclasses. Need: `BP_Moderation_Events` class registering `events` item type + `bp_moderation_content_types` filter. Report button rendered via `bp_moderation_get_button()`. |
</phase_requirements>

---

## Summary

This phase is a build-on-existing-scaffolding project, not a greenfield build. The plugin already has a fully defined data model (`BP_Event` class with all fields), database schema (4 tables, all columns present), CRUD functions (`bp_events_create_event`, `bp_events_update_event`), REST API routes (all routes registered including occurrence/series endpoints), admin list table, and settings UI callbacks. All admin settings (ADMN-01, ADMN-02) are functionally implemented — they read/write options and the permission check functions already consume them. What is missing is the runtime behaviour that makes these pieces work together: the FullCalendar JS integration, the multi-step creation form JS, occurrence pre-generation logic, the WP cron window-extension job, the moderation class for events, and the approve action handler.

The key technical challenge is RRULE expansion. The `recurrence_rule` field stores an RFC 5545 RRULE string. On publish, the plugin must expand that string into individual occurrence rows (child rows with `parent_event_id` set) covering 2 years. The `rlanvin/php-rrule` library (v2.6.0, requires PHP >= 7.3, available via Composer) provides `getOccurrencesBetween()` which returns `DateTime[]` for a given range. Since BuddyBoss Platform does not use Composer in the plugin bundle itself, the library must be vendored into the plugin's source tree.

The second key challenge is the FullCalendar integration. FullCalendar 6 uses a JSON feed where the library sends `?start=ISO8601&end=ISO8601` parameters to the configured URL. The existing `get_items()` endpoint already accepts `from` and `to` parameters. The endpoint response must be shaped to match FullCalendar's expected event object format: `id`, `title`, `start`, `end`, `url`. The entire FullCalendar bundle (`index.global.min.js`, currently v6.1.20) should be enqueued via `wp_enqueue_script()` pointing to a local copy (no CDN dependency for a WordPress plugin).

**Primary recommendation:** Implement in three waves — (1) complete the server-side logic gaps (occurrence generation, approve action, moderation class, group-level calendar override), (2) implement the FullCalendar calendar UI and wire it to the REST endpoint, (3) implement the multi-step creation form JS wizard.

---

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| BuddyBoss Platform (`BP_Component`, `bp_parse_args`, `bp_get_option`) | Existing | Plugin lifecycle, settings, templates | Required — plugin is a BP component |
| WordPress REST API (`WP_REST_Controller`, `register_rest_route`) | Existing | Event CRUD API, FullCalendar feed | Already wired in `BP_REST_Events_Endpoint` |
| WordPress WP_List_Table | Existing | Admin event list | Already used in `BP_Events_List_Table` |
| WordPress `wp_schedule_event` / WP Cron | Existing | Recurring occurrence window extension | Standard WP background task mechanism |
| `rlanvin/php-rrule` | ^2.6.0 | RRULE string expansion to DateTime[] | Lightweight, RFC 5545 compliant, PHP >= 7.3 |
| FullCalendar | 6.1.20 | Interactive calendar UI | Locked decision; includes dayGrid + list plugins in global bundle |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| WordPress `dbDelta()` | Existing | Schema migrations | If schema changes are needed; currently schema is complete |
| WordPress `wp_cache_get/set` | Existing | Object caching | Already used in `BP_Event::populate()` |
| BuddyBoss `BP_Moderation_Abstract` | Existing | Integrate event reporting into moderation | For ADMN-04 — extend to create `BP_Moderation_Events` class |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `rlanvin/php-rrule` | `simshaun/recurr` | recurr is heavier and more OOP-heavy; php-rrule is faster and RFC 5545 strict. Decision: use php-rrule. |
| FullCalendar global bundle (CDN/local) | npm + webpack build | Webpack build requires Node toolchain — not appropriate for this plugin's no-build-system architecture. Use local copy of `index.global.min.js`. |
| WP cron for occurrence extension | Background process queue | WP cron is sufficient; background process is overkill for a scheduled daily/weekly task. |

**Installation:**
```bash
# Vendor php-rrule into the plugin (no Composer autoloader in WP plugin context)
composer require rlanvin/php-rrule --working-dir=buddyboss-events/vendor-src
# Then copy vendor/rlanvin/php-rrule/src/ into buddyboss-events/src/bp-events/includes/lib/php-rrule/
# Require the files manually in bp-events-functions.php (no PSR-4 autoloader available)
```

FullCalendar: download `index.global.min.js` from `https://cdn.jsdelivr.net/npm/fullcalendar@6.1.20/index.global.min.js` and store as `src/bp-events/assets/js/vendor/fullcalendar.min.js`.

---

## Architecture Patterns

### Recommended Project Structure (additions to existing)
```
src/bp-events/
├── assets/
│   ├── js/
│   │   ├── vendor/
│   │   │   └── fullcalendar.min.js    # FullCalendar 6 global bundle
│   │   ├── bp-events-calendar.js      # FullCalendar initialisation + REST feed
│   │   └── bp-events-create.js        # Multi-step wizard
│   └── css/
│       ├── bp-events.css              # Frontend styles
│       └── bp-events-admin.css        # Admin list table styles
├── includes/
│   └── lib/
│       └── php-rrule/                 # Vendored RRule library files
│           ├── RRule.php
│           ├── RRuleInterface.php
│           └── RSet.php
├── classes/
│   ├── class-bp-event.php             # DONE
│   ├── class-bp-events-component.php  # DONE
│   ├── class-bp-events-list-table.php # DONE
│   ├── class-bp-rest-events-endpoint.php        # DONE (needs FullCalendar feed shape)
│   ├── class-bp-rest-events-settings-endpoint.php # DONE
│   └── class-bp-moderation-events.php # NEW (ADMN-04)
└── [existing files unchanged]
```

### Pattern 1: Occurrence Pre-generation on Publish
**What:** When an event with a `recurrence_rule` is published (status changes to `published`), expand the RRULE to produce child rows 2 years ahead.
**When to use:** Called from `bp_events_after_event_save` action hook, when `$event->status === 'published'` and `!empty($event->recurrence_rule)` and `empty($event->parent_event_id)` (only for parent events).
**Example:**
```php
// Source: rlanvin/php-rrule v2.6.0 API + BuddyBoss function pattern
function bp_events_generate_occurrences( $event ) {
    if ( 'published' !== $event->status || empty( $event->recurrence_rule ) || ! empty( $event->parent_event_id ) ) {
        return;
    }

    require_once buddypress()->plugin_dir . 'bp-events/includes/lib/php-rrule/RRule.php';

    $rrule    = new RRule\RRule( $event->recurrence_rule . ';DTSTART=' . gmdate( 'Ymd\THis\Z', strtotime( $event->start_date ) ) );
    $until    = new DateTime( '+2 years' );
    $duration = strtotime( $event->end_date ) - strtotime( $event->start_date );

    foreach ( $rrule->getOccurrencesBetween( new DateTime( $event->start_date ), $until ) as $occurrence_dt ) {
        // Skip the first occurrence (that is the parent event itself).
        if ( $occurrence_dt->format( 'Y-m-d H:i:s' ) === $event->start_date ) {
            continue;
        }

        bp_events_create_event( array(
            'title'            => $event->title,
            'description'      => $event->description,
            'organizer_id'     => $event->organizer_id,
            'group_id'         => $event->group_id,
            'type'             => $event->type,
            'venue_name'       => $event->venue_name,
            'venue_address'    => $event->venue_address,
            'venue_lat'        => $event->venue_lat,
            'venue_lng'        => $event->venue_lng,
            'virtual_url'      => $event->virtual_url,
            'virtual_type'     => $event->virtual_type,
            'start_date'       => $occurrence_dt->format( 'Y-m-d H:i:s' ),
            'end_date'         => date( 'Y-m-d H:i:s', $occurrence_dt->getTimestamp() + $duration ),
            'timezone'         => $event->timezone,
            'capacity'         => $event->capacity,
            'status'           => 'published',
            'recurrence_rule'  => $event->recurrence_rule,
            'parent_event_id'  => $event->id,
        ) );
    }
}
add_action( 'bp_events_after_event_save', 'bp_events_generate_occurrences' );
```

### Pattern 2: FullCalendar REST Feed Integration
**What:** FullCalendar sends `?start=ISO8601&end=ISO8601` to the events feed URL. The REST endpoint must return a JSON array in FullCalendar's expected shape.
**When to use:** `get_items()` in `BP_REST_Events_Endpoint` — add a FullCalendar-shaped response path when the `_fc` query parameter is present, or shape all responses to be FC-compatible.
**Example:**
```javascript
// Source: FullCalendar 6 docs (fullcalendar.io/docs/events-json-feed)
// In bp-events-calendar.js
document.addEventListener( 'DOMContentLoaded', function() {
    var el      = document.getElementById( 'bb-rl-events-calendar' );
    var settings = window.bpEventsSettings;

    var calendar = new FullCalendar.Calendar( el, {
        initialView:  settings.calendarView === 'list' ? 'listMonth' : 'dayGridMonth',
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  '' // view toggle handled by our own UI
        },
        events: {
            url:    settings.restUrl,
            method: 'GET',
            extraParams: {
                _fc:      1,     // tells endpoint to return FC-shaped data
                per_page: 200,
            },
            failure: function() {
                // Show error state
            }
        },
        eventClick: function( info ) {
            window.location.href = info.event.url;
            info.jsEvent.preventDefault();
        }
    } );

    calendar.render();
} );
```

FullCalendar event object shape (returned from REST endpoint):
```json
{
    "id": 42,
    "title": "Team Standup",
    "start": "2026-03-15T09:00:00",
    "end": "2026-03-15T09:30:00",
    "url": "https://example.com/events/team-standup/",
    "extendedProps": {
        "type": "in-person",
        "venue": "Main Office",
        "status": "published"
    }
}
```

### Pattern 3: BuddyBoss Moderation Integration (ADMN-04)
**What:** Register `events` as a reportable content type by extending `BP_Moderation_Abstract`.
**When to use:** On `bp_setup_components` after events component loads.
**Example:**
```php
// Source: BP_Moderation_Groups pattern from buddyboss-platform/bp-moderation/classes/
class BP_Moderation_Events extends BP_Moderation_Abstract {

    public static $moderation_type = 'events';

    public function __construct() {
        parent::$moderation[ self::$moderation_type ] = self::class;
        $this->item_type = self::$moderation_type;

        add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

        if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
            return;
        }

        if ( ! bp_is_moderation_content_reporting_enable( 0, self::$moderation_type ) ) {
            return;
        }

        add_filter( "bp_moderation_{$this->item_type}_validate", array( $this, 'validate_single_item' ), 10, 2 );
    }

    public function add_content_types( $content_types ) {
        $content_types[ self::$moderation_type ] = __( 'Events', 'buddyboss' );
        return $content_types;
    }

    public static function get_permalink( $event_id ) {
        return bp_get_event_permalink( bp_events_get_event( $event_id ) );
    }

    public function validate_single_item( $validated, $event_id ) {
        $event = bp_events_get_event( $event_id );
        return ! empty( $event ) && 'published' === $event->status;
    }
}
```

### Pattern 4: WP Cron Occurrence Extension
**What:** A daily cron job checks each parent recurring event and generates occurrences if the furthest child is within 90 days of today + 2 years.
**When to use:** Scheduled via `wp_schedule_event` on plugin activation.
**Example:**
```php
// In bp-events-filters.php
add_action( 'bp_events_extend_occurrences', 'bp_events_cron_extend_occurrences' );

function bp_events_cron_extend_occurrences() {
    global $wpdb;
    $bp = buddypress();

    // Find parent events with recurrence rules.
    $parent_ids = $wpdb->get_col(
        "SELECT id FROM {$bp->events->table_name}
         WHERE status = 'published'
           AND recurrence_rule != ''
           AND parent_event_id IS NULL"
    );

    foreach ( $parent_ids as $parent_id ) {
        $event = bp_events_get_event( (int) $parent_id );
        if ( $event ) {
            bp_events_generate_occurrences( $event );
        }
    }
}
```

### Pattern 5: Multi-Step Wizard JS Architecture
**What:** The `create.php` template already provides `window.bpEventsCreate` config object. The JS wizard must render steps into `#bb-rl-event-create-form` and POST to the REST API on final submit.
**When to use:** `bp-events-create.js` loaded only on the create screen (`bp_is_current_component('events') && bp_is_action_variable('create', 0)`).

The wizard maintains a state object:
```javascript
var state = {
    step: 1,
    type: 'in-person',
    title: '',
    description: '',
    start_date: '',
    end_date: '',
    timezone: '',
    venue_name: '',
    venue_address: '',
    virtual_url: '',
    virtual_type: '',
    capacity: null,
    recurrence_rule: '',
    status: 'draft'
};
```

Step 3 (Location/Virtual) uses `state.type` to show/hide `#bb-rl-venue-fields` vs `#bb-rl-virtual-fields` without page reload.

Step 5 (Recurrence) is conditional — rendered only if user opts in. It generates an RRULE string from UI selections (frequency, interval, weekdays, end condition).

Final step sends `POST /buddyboss/v1/events` with the state object serialised as JSON, using `window.bpEventsCreate.nonce` as the `X-WP-Nonce` header.

### Anti-Patterns to Avoid
- **Storing RRULE occurrences as WP post meta on the CPT:** The data model uses custom tables (`bp_events`), not CPT. All storage goes through `BP_Event::save()`.
- **Calling `bp_events_generate_occurrences()` on every save:** Only call when status transitions to `published` AND `recurrence_rule` is non-empty AND `parent_event_id` is null. Guard against re-generation on subsequent edits to parent event details.
- **Generating unlimited occurrences:** Always pass a `$until` date constraint to `getOccurrencesBetween()`. Never iterate the full RRule without a bound.
- **Enqueueing FullCalendar on every page:** Enqueue only on `bp_is_current_component('events')` or group event pages.
- **Using `wp_enqueue_script` with a CDN URL:** WordPress plugins must serve assets locally; CDN dependencies create external network calls and CSP issues.
- **Mutating the RRULE in "edit this and following":** Locked decision — split the series into two, do not mutate the parent's RRULE.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Expanding RRULE strings into dates | Custom RRULE parser | `rlanvin/php-rrule` | RFC 5545 has 50+ edge cases (BYSETPOS, WKST, leap years, timezone offsets) |
| FullCalendar month/list navigation | Custom calendar grid | FullCalendar 6 | Locked decision; handles prev/next, view switching, dynamic event loading |
| Admin list table | Custom HTML table | `WP_List_Table` | Already implemented in `BP_Events_List_Table`; provides sorting, pagination, search, bulk actions |
| Report button rendering | Custom HTML | `bp_moderation_get_button()` | BuddyBoss moderation handles report modal, duplicate prevention, admin routing |
| RRULE string building from UI | Bespoke serialiser | Simple concatenation: `FREQ=WEEKLY;INTERVAL=1;BYDAY=MO,WE` | RRULE properties are semicolon-delimited key=value; no library needed for building basic rules from dropdown selections |

**Key insight:** The `rlanvin/php-rrule` library is the only external PHP dependency needed. Everything else reuses BuddyBoss/WordPress infrastructure.

---

## Common Pitfalls

### Pitfall 1: Duplicate Occurrence Generation
**What goes wrong:** Saving a parent event (e.g. updating the title) triggers `bp_events_after_event_save`, which re-runs occurrence generation and creates duplicate child rows.
**Why it happens:** The `save()` action fires on every save regardless of what changed.
**How to avoid:** Check whether child rows already exist for the parent before generating. Query `SELECT COUNT(*) FROM bp_events WHERE parent_event_id = %d` first. Only generate if count is 0. Alternatively, track a `bp_eventmeta` key `occurrences_generated_until`.
**Warning signs:** Rapidly growing `bp_events` row count after editing a recurring event.

### Pitfall 2: FullCalendar Feed Sending Wrong Date Format
**What goes wrong:** The REST endpoint returns `start_date` in MySQL format (`2026-03-15 09:00:00`) but FullCalendar expects ISO8601 (`2026-03-15T09:00:00`).
**Why it happens:** MySQL `datetime` fields use spaces not `T` separators.
**How to avoid:** In `prepare_item_for_response()`, format dates with `str_replace( ' ', 'T', $event->start_date )` for the FullCalendar-shaped response. The existing `prepare_item_for_response()` passes the raw MySQL string — add a `_fc` branch that reformats.
**Warning signs:** FullCalendar renders no events even though the network response returns data.

### Pitfall 3: Occurrence Cron Window Not Advancing
**What goes wrong:** Occurrences generated 2 years ahead at publish time run out. Users see the recurring event stop appearing on the calendar after 2 years.
**Why it happens:** The cron extension job re-calls `bp_events_generate_occurrences()` but the function skips generation because rows already exist.
**How to avoid:** The cron job must check the `max(start_date)` of child rows for each parent. Only generate from that date forward to `now + 2 years`. This is different from the publish-time generation — requires a separate `bp_events_extend_occurrences_for_event()` function.
**Warning signs:** Calendar shows recurring events stopping at exactly 2 years from original publish date.

### Pitfall 4: "Edit this and following" Splitting Logic
**What goes wrong:** When splitting a series, the original parent event still has child rows with `start_date` >= the split point. Queries that filter by `parent_event_id = original_parent` return occurrences that now belong to the new series.
**Why it happens:** Splitting creates a new parent but does not clean up or re-parent the old future child rows.
**How to avoid:** When handling "edit this and following" (the `update_series` endpoint): (1) delete all future child rows from the original series, (2) create a new parent event with the updated fields and new RRULE, (3) generate fresh child rows for the new parent. The existing `update_series` endpoint updates child rows in-place — this must be replaced with the split pattern per the locked decision.
**Warning signs:** After "edit this and following", the original series' children appear under the new series on the calendar.

### Pitfall 5: WP Cron Not Running on MAMP
**What goes wrong:** WP cron events registered with `wp_schedule_event()` do not fire automatically on MAMP development environments without incoming HTTP traffic.
**Why it happens:** WordPress pseudo-cron is triggered by page visits. A development MAMP site with no traffic never triggers cron.
**How to avoid:** During development, test cron jobs by calling the callback function directly. Use `wp_schedule_event()` with `wp_next_scheduled()` guard on plugin activation. For production, document that real cron (`* * * * * curl https://site.com/wp-cron.php?doing_wp_cron`) is recommended.
**Warning signs:** Occurrence window never extends beyond initial 2-year generation.

### Pitfall 6: `bp_events_user_can_create()` Always Returning False for `organizers` Permission
**What goes wrong:** When `bb_events_creation_permission` is set to `organizers`, the function checks `groups_is_user_admin() || groups_is_user_mod()` but requires a non-null `$group_id`. Site-level event creation without a group always returns false.
**Why it happens:** The `organizers` case requires a group context, but standalone events have no group.
**How to avoid:** For the creation form at `/events/create` (no group context), the UI should not show the Create Event button when permission is `organizers` and the user is not a group admin anywhere. The `bp_events_user_can_create()` function already handles this correctly — just ensure the template permission check passes `null` for `$group_id` correctly.
**Warning signs:** Members who are group admins cannot create standalone events even though they should be able to create group events.

---

## Code Examples

Verified patterns from existing codebase:

### REST Endpoint Shaping for FullCalendar
```php
// Source: existing class-bp-rest-events-endpoint.php prepare_item_for_response()
// Add FullCalendar-compatible shape when ?_fc=1 is present:
public function prepare_item_for_response( $event, $request ) {
    $is_fc = (bool) $request->get_param( '_fc' );

    if ( $is_fc ) {
        return rest_ensure_response( array(
            'id'    => $event->id,
            'title' => $event->title,
            'start' => str_replace( ' ', 'T', $event->start_date ),
            'end'   => str_replace( ' ', 'T', $event->end_date ),
            'url'   => bp_get_event_permalink( $event ),
            'extendedProps' => array(
                'type'   => $event->type,
                'venue'  => $event->venue_name ?: $event->virtual_url,
                'status' => $event->status,
            ),
        ) );
    }

    // ... existing full response shape ...
}
```

### FullCalendar Feed `?start`/`?end` Params Mapped to REST `from`/`to`
```php
// Source: existing bp-events-functions.php bp_events_get_events()
// FullCalendar sends ?start=2026-03-01T00:00:00... and ?end=2026-04-01T00:00:00...
// Map these in get_items():
$from = $request->get_param( 'start' ) ?: $request->get_param( 'from' );
$to   = $request->get_param( 'end' )   ?: $request->get_param( 'to' );
```

### BuddyBoss `bp_parse_args` Pattern (established convention)
```php
// Source: existing bp-events-functions.php
$r = bp_parse_args(
    $args,
    array(
        'recurrence_rule' => '',
        'parent_event_id' => null,
    ),
    'events_generate_occurrences'
);
```

### Admin Approve Action (ADMN-02 gap)
```php
// Source: pattern from BP_Events_List_Table column_title() — Approve link is rendered
// but no handler exists yet. Wire via REST:
add_action( 'wp_ajax_bp_events_approve', function() {
    check_ajax_referer( 'bp_events_admin_action', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Permission denied.', 'buddyboss' ) );
    }

    $event_id = absint( $_POST['event_id'] );
    $result   = bp_events_update_event( $event_id, array( 'status' => 'published' ) );

    if ( $result ) {
        wp_send_json_success();
    } else {
        wp_send_json_error( __( 'Could not approve event.', 'buddyboss' ) );
    }
} );
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| FullCalendar loaded as individual plugin packages via npm | Single `index.global.min.js` bundle (all plugins included) | FC v6 | Simpler WordPress integration — one `wp_enqueue_script` call |
| RRULE expansion done inline in application code | Dedicated library (`rlanvin/php-rrule`) | ~2015 onward | Eliminates edge case bugs; RFC 5545 compliance |
| WordPress AJAX (`admin-ajax.php`) for all dynamic data | WP REST API (`/wp-json/buddyboss/v1/`) | WP 4.7+ | FullCalendar natively supports REST feed URLs; no custom AJAX handler needed for calendar data |

**Deprecated/outdated:**
- `update_series` endpoint current implementation: Updates child rows in-place. The locked decision replaces this with a series-split approach. The existing endpoint method body must be rewritten.

---

## Open Questions

1. **php-rrule vendoring approach**
   - What we know: The plugin has no `composer.json` in the working `buddyboss-events/` directory. The Plugins reference copy has one.
   - What's unclear: Whether to add Composer support to the plugin or manually vendor only the needed RRule files (3 PHP files: `RRule.php`, `RRuleInterface.php`, `RSet.php`).
   - Recommendation: Manually vendor the 3 source files into `src/bp-events/includes/lib/php-rrule/`. Avoids requiring Composer in the MAMP dev workflow and matches BuddyBoss Platform's approach of bundling its own dependencies.

2. **FullCalendar locale / timezone handling**
   - What we know: `BP_Event::$timezone` stores a PHP timezone string. FullCalendar has `timeZone` option and locale support.
   - What's unclear: Whether the REST feed should return UTC times or local times for the event's timezone.
   - Recommendation: Store and return all datetimes in UTC. Pass `timeZone: 'local'` to FullCalendar so it converts to the viewer's browser timezone. This matches the existing schema (times stored in UTC-equivalent MySQL datetime).

3. **Admin revenue dashboard scope for Phase 1**
   - What we know: ADMN-03 requires a dashboard showing "all events, ticket sales revenue, and commission earned." Revenue/commission data does not exist until Phase 2.
   - What's unclear: Whether Phase 1 delivery of ADMN-03 should be a partial (events only, revenue placeholder) or deferred entirely.
   - Recommendation: Deliver Phase 1 portion as events count statistics (total events, published/pending/draft counts) on the existing revenue page stub. Revenue figures remain the Phase 2 placeholder already in place. This satisfies the "all events" part of ADMN-03 without blocking Phase 1.

---

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit (configured in `Plugins/buddyboss-events/phpunit.xml.dist`) |
| Config file | `Plugins/buddyboss-events/phpunit.xml.dist` |
| Quick run command | `vendor/bin/phpunit tests/phpunit/testcases/test-sample.php` |
| Full suite command | `vendor/bin/phpunit` |

**Note:** The PHPUnit infrastructure exists in the `Plugins/buddyboss-events/` reference copy. The working plugin source is in `buddyboss-events/`. Tests must be written targeting the working source. A `tests/` directory with bootstrap needs to be created in the working plugin (Wave 0 gap).

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| EVNT-01 | `bp_events_create_event()` creates in-person event with all venue fields | unit | `vendor/bin/phpunit tests/phpunit/test-event-crud.php::test_create_in_person_event -x` | ❌ Wave 0 |
| EVNT-02 | `bp_events_create_event()` creates virtual event with URL and type | unit | `vendor/bin/phpunit tests/phpunit/test-event-crud.php::test_create_virtual_event -x` | ❌ Wave 0 |
| EVNT-03 | Occurrence generation: publishing recurring event creates correct child rows | unit | `vendor/bin/phpunit tests/phpunit/test-recurring.php::test_publish_generates_occurrences -x` | ❌ Wave 0 |
| EVNT-03 | "Edit this only" detaches occurrence from series | unit | `vendor/bin/phpunit tests/phpunit/test-recurring.php::test_edit_single_occurrence -x` | ❌ Wave 0 |
| EVNT-03 | "Edit this and following" splits series correctly | unit | `vendor/bin/phpunit tests/phpunit/test-recurring.php::test_edit_this_and_following -x` | ❌ Wave 0 |
| EVNT-04 | Event saved with status=draft does not appear in published query | unit | `vendor/bin/phpunit tests/phpunit/test-event-crud.php::test_draft_not_in_published_query -x` | ❌ Wave 0 |
| EVNT-05 | Group event excluded from site calendar when per-group setting is off | unit | `vendor/bin/phpunit tests/phpunit/test-calendar-privacy.php::test_group_event_excluded -x` | ❌ Wave 0 |
| EVNT-06 | Private/hidden group events never appear in site calendar query | unit | `vendor/bin/phpunit tests/phpunit/test-calendar-privacy.php::test_private_group_never_visible -x` | ❌ Wave 0 |
| ADMN-01 | `bp_events_user_can_create()` respects admins/organizers/members setting | unit | `vendor/bin/phpunit tests/phpunit/test-permissions.php::test_creation_permission -x` | ❌ Wave 0 |
| ADMN-02 | Approving a pending event sets status to published | unit | `vendor/bin/phpunit tests/phpunit/test-admin.php::test_approve_pending_event -x` | ❌ Wave 0 |
| ADMN-04 | Event report routes to moderation system | integration | `vendor/bin/phpunit tests/phpunit/test-moderation.php::test_event_report -x` | ❌ Wave 0 |
| ADMN-03 | Admin revenue page returns event count data | integration | manual — requires WP admin context | manual-only |

### Sampling Rate
- **Per task commit:** `vendor/bin/phpunit tests/phpunit/test-event-crud.php -x` (relevant test file for the task)
- **Per wave merge:** `vendor/bin/phpunit` (full suite)
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] `tests/phpunit/bootstrap.php` — WordPress + BuddyBoss test bootstrap
- [ ] `tests/phpunit/test-event-crud.php` — covers EVNT-01, EVNT-02, EVNT-04
- [ ] `tests/phpunit/test-recurring.php` — covers EVNT-03
- [ ] `tests/phpunit/test-calendar-privacy.php` — covers EVNT-05, EVNT-06
- [ ] `tests/phpunit/test-permissions.php` — covers ADMN-01
- [ ] `tests/phpunit/test-admin.php` — covers ADMN-02
- [ ] `tests/phpunit/test-moderation.php` — covers ADMN-04
- [ ] Framework install: `composer install --working-dir=buddyboss-events` (if adding Composer) or copy PHPUnit bootstrap from `Plugins/buddyboss-events/tests/`

---

## Sources

### Primary (HIGH confidence)
- Existing plugin source code (`buddyboss-events/src/bp-events/`) — direct code inspection of all PHP classes, functions, templates, and REST endpoints
- Existing platform reference (`Plugins/buddyboss-platform/bp-moderation/`) — `BP_Moderation_Groups` pattern for moderation integration
- [FullCalendar 6 JSON feed docs](https://fullcalendar.io/docs/events-json-feed) — query param names (`start`, `end`), response format (JSON array of event objects)
- [FullCalendar 6 script tag init](https://fullcalendar.io/docs/initialize-globals) — global bundle CDN URL, `FullCalendar.Calendar` constructor, `initialView`, `events` option
- [rlanvin/php-rrule Packagist](https://packagist.org/packages/rlanvin/php-rrule) — v2.6.0, PHP >= 7.3 requirement
- [rlanvin/php-rrule RRuleInterface wiki](https://github.com/rlanvin/php-rrule/wiki/RRuleInterface) — `getOccurrencesBetween($begin, $end, $limit)` method signature and return type

### Secondary (MEDIUM confidence)
- [FullCalendar event-model docs](https://fullcalendar.io/docs/event-model) — `id`, `title`, `start`, `end`, `url`, `extendedProps` field names (confirmed from multiple sources)
- [FullCalendar CDN listing on jsDelivr](https://www.jsdelivr.com/package/npm/fullcalendar) — v6.1.20 is current stable release

### Tertiary (LOW confidence)
- WebSearch results on BuddyBoss moderation API — `bp_moderation_report_exist()` and `bp_moderation_get_button()` exist; confirmed function names from platform source inspection but full signature not verified against live platform docs

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — All libraries verified from official docs and direct code inspection
- Architecture: HIGH — Based entirely on existing codebase patterns with no speculation
- Pitfalls: HIGH — Identified from code inspection of existing implementation gaps (duplicate generation guard missing, update_series in-place vs split, date format mismatch)
- FullCalendar feed shape: HIGH — Verified from official FullCalendar docs
- php-rrule API: HIGH — Verified from Packagist and GitHub wiki
- Moderation integration: MEDIUM — Pattern confirmed from platform source; exact `BP_Moderation_Abstract` parent class method signatures not fully enumerated

**Research date:** 2026-03-13
**Valid until:** 2026-06-13 (stable dependencies; FullCalendar 6.x and php-rrule 2.x APIs are stable)
