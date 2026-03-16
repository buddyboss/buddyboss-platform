# Phase 3: BuddyBoss Integration - Research

**Researched:** 2026-03-16
**Domain:** BuddyBoss Platform APIs — BP_Group_Extension, bp_activity_add, group member queries, member profile nav
**Confidence:** HIGH (all findings verified directly from platform source at `/Users/tom/Local Sites/Events/Plugins/buddyboss-platform/`)

---

## Summary

Phase 3 wires the existing events component into three pillars of BuddyBoss: groups (tab + calendar), activity feeds (event lifecycle events), and member profiles (attended/hosted history). All required APIs exist and are well-established in the platform codebase. No third-party libraries are needed.

The `BP_Group_Extension` class is the canonical way to add a tab to any group. The `bp_activity_add()` function is the single entry point for writing to any feed. Group privacy enforcement is automatic when `component` is set to `groups` and `item_id` is the group ID — the platform's `bp_activity_user_can_read()` function enforces membership checks for private/hidden groups. Member profile tabs are added through `BP_Component::setup_nav()` exactly as the events component already does for its own "Attending/Hosting" tabs.

The events data model uses a custom database table (`wp_bp_events`) with a native `group_id` column (bigint, nullable) as the group foreign key. No post meta is involved — the link is a first-class column on the events table, already indexed. RSVP status lives in `wp_bp_event_attendees.status` (values: `registered`, `waitlisted`). The invite table `wp_bp_event_invites` already exists and is ready for group-member invite flow.

**Primary recommendation:** Build `BP_Events_Group_Extension` (extending `BP_Group_Extension`), a dedicated `bp-events-activity.php` file, and add an `events` sub-nav item inside the component's `setup_nav()`. All integration points are hooked on existing `bp_events_after_event_save` and RSVP actions already fired from Phase 1/2 code.

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| BB-01 | Each BuddyBoss group has an Events tab displaying a calendar view of that group's events | `BP_Group_Extension` + `display()` method rendering FullCalendar scoped to `group_id`; `bp_events_get_events(['group_id' => $gid])` |
| BB-02 | Event creation, RSVPs, and ticket purchases auto-post to activity feeds respecting group privacy | `bp_activity_add()` with `component=events`, `item_id=group_id`, `hide_sitewide` flag for private groups; hooked on `bp_events_after_event_save` and RSVP actions |
| BB-03 | Organizer can invite group members from the group member roster | `groups_get_group_members()` PHP API + `/buddyboss/v1/groups/{id}/members` REST endpoint; writes to `wp_bp_event_invites` |
| BB-04 | Member profiles display hosted and attended events sections | Add `events` main nav + `attending`/`hosting` sub-nav in `BP_Events_Component::setup_nav()`, or extend existing nav; query `bp_events_get_events(['organizer_id'=>$uid])` and `['user_id'=>$uid]` |
</phase_requirements>

---

## Standard Stack

### Core
| Library/API | Version | Purpose | Why Standard |
|-------------|---------|---------|--------------|
| `BP_Group_Extension` | Platform built-in | Register a group tab with display/edit screens | The only supported API for group tabs; used by Forums component |
| `bp_activity_add()` | Platform built-in | Write activity items to any feed | Single authoritative entry point for all activity |
| `groups_get_group_members()` | Platform built-in | Fetch group member roster | PHP API backed by `BP_Group_Member_Query` |
| `/buddyboss/v1/groups/{id}/members` | REST v1 | Expose group members to JS | `BP_REST_Group_Membership_Endpoint` already registered |
| `bp_activity_set_action()` | Platform built-in | Register custom activity types | Required before `bp_activity_add()` can use custom types |

### No Additional Libraries Needed
All integration is through existing platform PHP APIs and REST endpoints.

---

## Architecture Patterns

### Recommended File Structure for Phase 3

```
src/bp-events/
├── bp-events-activity.php          # NEW: register actions + bp_activity_add() calls
├── bp-events-filters.php           # EXTEND: add bp_activity hook registrations
├── bp-events-loader.php            # EXTEND: conditionally include bp-events-activity.php
├── classes/
│   └── class-bp-events-group-extension.php   # NEW: BP_Group_Extension subclass
└── screens/
    └── group/
        └── events.php              # NEW: template loaded by group extension display()
```

### Pattern 1: BP_Group_Extension — Registering a Group Tab

**What:** Subclass `BP_Group_Extension` and call `bp_register_group_extension()`. The `display()` method is called when the tab is visited.

**When to use:** Any time you need a new tab inside `/groups/{slug}/`.

**Example (from platform source `/bp-forums/groups.php` + `/bp-forums/classes/class-bp-forums-component.php`):**

```php
// File: src/bp-events/classes/class-bp-events-group-extension.php

class BP_Events_Group_Extension extends BP_Group_Extension {

    public function __construct() {
        parent::init( array(
            'slug'              => 'events',
            'name'              => __( 'Events', 'buddyboss' ),
            'nav_item_name'     => __( 'Events', 'buddyboss' ),
            'nav_item_position' => 25,
            'enable_nav_item'   => true,
            'visibility'        => 'public',   // BP enforces group privacy automatically
            'access'            => 'anyone',   // who can visit tab
            'show_tab'          => 'anyone',   // who sees the nav item
            'template_file'     => 'groups/single/plugins',
        ) );
    }

    /**
     * Called when the tab is visited. Render the calendar or event list.
     *
     * @param int|null $group_id Current group ID.
     */
    public function display( $group_id = null ) {
        $group_id = $group_id ?: bp_get_current_group_id();
        // Render template. Template can call bp_events_get_events(['group_id'=>$group_id]).
        bp_get_template_part( 'groups/single/events' );
    }
}

// Registration — call inside bp_setup_components (priority 15) or bp_init.
// Source: bp-forums/classes/class-bp-forums-component.php line 165
function bp_events_register_group_extension() {
    if ( bp_is_active( 'groups' ) ) {
        bp_register_group_extension( 'BP_Events_Group_Extension' );
    }
}
add_action( 'bp_init', 'bp_events_register_group_extension', 11 );
```

**Critical detail:** `bp_register_group_extension()` internally calls `new $class()` and hooks `_register()` onto both `bp_actions` (priority 8) and `admin_init`. You must not call `_register()` yourself.

**Privacy enforcement:** For private/hidden groups, `access` defaults to `member` automatically when the group is private. You can also pass `'show_tab' => 'member'` to hide the tab entirely from non-members. The platform's `bp_activity_user_can_read()` enforces group membership for activity items where `component = 'groups'`.

---

### Pattern 2: bp_activity_add() — Writing Activity Items

**What:** `bp_activity_add()` is the single function for adding an activity item to any feed. Setting `component` to the events component ID and optionally `item_id` to a group ID routes the item correctly.

**Full parameter list** (source: `bp-activity/bp-activity-functions.php` line 2064–2097):

| Parameter | Type | Default | Notes |
|-----------|------|---------|-------|
| `id` | int\|bool | `false` | Pass existing ID to update, false to create |
| `action` | string | `''` | Human-readable string (used as fallback). Omit if format_callback registered. |
| `content` | string | `''` | HTML content of the activity item |
| `component` | string | required | Component ID, e.g. `buddypress()->events->id` |
| `type` | string | required | Activity type slug, e.g. `'event_created'` |
| `primary_link` | string | `''` | URL for RSS feeds |
| `user_id` | int\|bool | current user | User associated with the item; `false` for system items |
| `item_id` | int\|bool | `false` | Primary item ID — use **group_id** for group-scoped items |
| `secondary_item_id` | int\|bool | `false` | Secondary ID — use **event_id** here |
| `recorded_time` | string | now | GMT datetime Y-m-d H:i:s |
| `hide_sitewide` | bool | `false` | **Set `true` for private/hidden group events** |
| `is_spam` | bool | `false` | Mark as spam |
| `privacy` | string | `'public'` | Privacy level |
| `status` | string | published | Activity status |
| `error_type` | string | `'bool'` | `'bool'` or `'wp_error'` |

**Returns:** Activity ID (int) on success, false on failure.

**Example — event created (group event, private group):**

```php
// Source: pattern derived from bp-groups/bp-groups-activity.php and bp-activity-functions.php

function bp_events_record_activity_event_created( BP_Event $event ) {
    if ( ! bp_is_active( 'activity' ) ) {
        return;
    }

    // Determine if this is a group event and if the group is private/hidden.
    $hide_sitewide = false;
    if ( ! empty( $event->group_id ) ) {
        $group = groups_get_group( $event->group_id );
        if ( 'public' !== $group->status ) {
            $hide_sitewide = true;
        }
    }

    $activity_id = bp_activity_add( array(
        'user_id'           => $event->organizer_id,
        'component'         => buddypress()->events->id,
        'type'              => 'event_created',
        'item_id'           => ! empty( $event->group_id ) ? $event->group_id : $event->id,
        'secondary_item_id' => $event->id,
        'primary_link'      => bp_events_get_event_permalink( $event ),
        'hide_sitewide'     => $hide_sitewide,
    ) );

    return $activity_id;
}
add_action( 'bp_events_after_event_save', 'bp_events_record_activity_event_created' );
```

**Note on item_id convention:** When `component` is the groups component ID, BuddyBoss uses `item_id` = group ID and `secondary_item_id` = the secondary object (post, event, etc.). For the events component, using `item_id` = group ID (when group event) routes the item to the group feed automatically.

**Required: Register action type before first use:**

```php
// Source: bp-groups/bp-groups-activity.php lines 30-72; bp-activity-functions.php line 332

function bp_events_register_activity_actions() {
    if ( ! bp_is_active( 'activity' ) ) {
        return;
    }
    $bp = buddypress();

    bp_activity_set_action(
        $bp->events->id,
        'event_created',
        __( 'Created an event', 'buddyboss' ),
        'bp_events_format_activity_action_event_created',  // format callback
        __( 'Events', 'buddyboss' ),
        array( 'activity', 'member', 'group', 'member_groups' )
    );

    bp_activity_set_action(
        $bp->events->id,
        'event_rsvp',
        __( 'RSVPed to an event', 'buddyboss' ),
        'bp_events_format_activity_action_event_rsvp',
        __( 'Events', 'buddyboss' ),
        array( 'activity', 'member', 'group', 'member_groups' )
    );
}
add_action( 'bp_register_activity_actions', 'bp_events_register_activity_actions' );
```

---

### Pattern 3: Group Member Query — Roster for Invite UI

**PHP API** (source: `bp-groups/bp-groups-functions.php` line 849):

```php
// Source: bp-groups/bp-groups-functions.php line 871-938

$result = groups_get_group_members( array(
    'group_id'            => $group_id,        // int
    'per_page'            => 20,               // int|false (false = all)
    'page'                => 1,                // int|false
    'exclude_admins_mods' => false,            // include admins/mods in results
    'exclude_banned'      => true,             // exclude banned members
    'exclude'             => array(),          // user IDs to exclude
    'group_role'          => array( 'member', 'mod', 'admin' ),
    'search_terms'        => false,            // string search
    'type'                => 'last_joined',    // alphabetical|last_joined|first_joined
    'populate_extras'     => true,
) );

// Returns: array( 'members' => [...WP_User objects with BP extras...], 'count' => int )
```

**REST API** (source: `bp-groups/classes/class-bp-rest-group-membership-endpoint.php` lines 63-89):

```
GET /buddyboss/v1/groups/{group_id}/members
```

Query params: `per_page`, `page`, `search`, `type`, `group_role` (array: member, mod, admin, banned).

The invite UI in the event create/edit wizard can call this REST endpoint to populate a searchable picker. Results can then be POSTed to the events invite REST endpoint.

---

### Pattern 4: Member Profile Tab — Adding Events to Profile Nav

The events component already adds main nav (`Events`) and sub-nav (`Attending`, `Hosting`) in `BP_Events_Component::setup_nav()`. The profile events section for BB-04 is already partially wired — users can visit `/members/{username}/events/attending` and `/members/{username}/events/hosting`. What is needed for BB-04 is:

1. Ensure those screen functions (`bp_events_screen_attending`, `bp_events_screen_hosting`) are implemented.
2. Add a template that queries `bp_events_get_events(['user_id' => bp_displayed_user_id()])` (attending) and `bp_events_get_events(['organizer_id' => bp_displayed_user_id()])` (hosting).

The nav structure is already registered. No new `BP_Component::setup_nav()` call is needed — just the screen functions and templates.

**How other components add profile nav** (source: `class-bp-members-component.php` line 337 — the `setup_nav()` pattern mirrors what `BP_Events_Component::setup_nav()` already does):

```php
// Already in class-bp-events-component.php setup_nav() — these exist:
$sub_nav[] = array(
    'name'            => __( 'Attending', 'buddyboss' ),
    'slug'            => 'attending',
    'parent_url'      => $events_link,
    'parent_slug'     => $this->slug,
    'screen_function' => 'bp_events_screen_attending',
    'position'        => 10,
);
$sub_nav[] = array(
    'name'            => __( 'Hosting', 'buddyboss' ),
    'slug'            => 'hosting',
    'parent_url'      => $events_link,
    'parent_slug'     => $this->slug,
    'screen_function' => 'bp_events_screen_hosting',
    'position'        => 20,
);
```

The screen functions and templates for these sub-nav items need to be created (they are listed in the component but not yet implemented in the screens directory).

---

### Anti-Patterns to Avoid

- **Do NOT set `component` to `buddypress()->groups->id` for events activity.** Use `buddypress()->events->id`. Setting component to groups would make the platform think it's a groups action, not an events action, breaking feed filtering.
- **Do NOT skip `bp_activity_set_action()`.** Without a registered action type, `bp_activity_add()` will succeed but the item will have no formatted action string and will not appear in filter dropdowns.
- **Do NOT call `_register()` on `BP_Group_Extension` directly.** Use `bp_register_group_extension()` exclusively — it handles the action hook timing.
- **Do NOT query group members directly via SQL.** Always use `groups_get_group_members()` — it handles caching and the `BP_Group_Member_Query` internals.
- **Do NOT assume `hide_sitewide = true` is sufficient for full privacy.** It hides from the sitewide feed but `bp_activity_user_can_read()` also checks group membership for items with component=groups. When using component=events, you must handle the privacy check yourself in activity queries (or set both `hide_sitewide=true` AND ensure your activity query respects group privacy).

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Group privacy check | Custom SQL against groups table | `groups_is_user_member( $uid, $gid )` + `groups_get_group()->status` | Already used in `BP_Event::user_can_view()` |
| Group member list | Custom SQL against `wp_bp_groups_members` | `groups_get_group_members()` | Handles banned/excluded users, pagination, caching |
| Activity visibility | Custom capability check | `bp_activity_user_can_read()` filter on `bp_activity_user_can_read` | Platform already enforces group membership |
| Profile nav registration | Custom rewrite rules | `BP_Component::setup_nav()` main/sub-nav pattern | Already wired in `BP_Events_Component` |
| REST endpoint for group members | Custom endpoint | `GET /buddyboss/v1/groups/{id}/members` | Already registered by `BP_REST_Group_Membership_Endpoint` |

---

## Common Pitfalls

### Pitfall 1: Activity Posted Twice on Save
**What goes wrong:** `bp_events_after_event_save` fires on both create and update. If the activity hook fires unconditionally, it posts a new "event created" item every time the organizer edits the event.
**Why it happens:** `BP_Event::save()` fires the same `bp_events_after_event_save` action for both insert and update.
**How to avoid:** Check `$event->date_created === $event->date_modified` (equal only on initial insert) or add a custom flag. Better: use a separate `bp_events_after_event_created` action fired only from the insert branch in `BP_Event::save()`.
**Warning signs:** Activity feed shows duplicate "created event" items for the same event.

### Pitfall 2: Private Group Events Appearing Sitewide
**What goes wrong:** `hide_sitewide = false` (default) causes private group events to show in the global activity feed.
**Why it happens:** `hide_sitewide` defaults to false in `bp_activity_add()`. The platform only auto-enforces group privacy for items with `component = buddypress()->groups->id`.
**How to avoid:** Always inspect the group's `status` field before calling `bp_activity_add()`. Set `hide_sitewide = true` when the group is `private` or `hidden`. See code example in Pattern 2 above.
**Warning signs:** `/activity` shows events from groups the viewer is not a member of.

### Pitfall 3: BP_Group_Extension `display()` Called Outside Group Context
**What goes wrong:** `bp_get_current_group_id()` returns 0 when called outside a group page.
**Why it happens:** `display()` receives `$group_id` as a parameter (available since BP 2.2+) but some code ignores it and calls `bp_get_current_group_id()` directly.
**How to avoid:** Always use `$group_id = $group_id ?: bp_get_current_group_id()` inside `display()`.

### Pitfall 4: activity.php Include Guard
**What goes wrong:** `bp_activity_add()` called when activity component is not active → fatal error.
**Why it happens:** The activity component is optional; `bp_activity_add()` is only defined if it's enabled.
**How to avoid:** Always guard with `bp_is_active( 'activity' )`. In the component's `includes()`, use the pattern already present: `if ( bp_is_active( 'activity' ) ) { $includes[] = 'activity'; }`.

### Pitfall 5: Group Extension Not Showing Tab on Fresh Registration
**What goes wrong:** Tab doesn't appear after adding code.
**Why it happens:** `bp_register_group_extension()` must fire at or after `bp_init` priority 11. If fired too early (e.g., `plugins_loaded`), the groups component is not yet ready.
**How to avoid:** Hook `bp_register_group_extension()` call to `bp_init` at priority 11 or higher, or directly inside a component's `setup_globals()` / `fully_loaded()` method.

---

## Our Current Event-to-Group Data Model

All data is stored in a **custom database table** (not post meta). Key tables:

### `wp_bp_events` (main events table)
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | Event ID |
| `organizer_id` | bigint | FK → wp_users.ID; indexed |
| `group_id` | bigint NULL | FK → group ID; **null = no group**; indexed |
| `status` | varchar(20) | `draft`, `pending`, `published`, `cancelled` |
| `start_date` | datetime | Indexed |
| `slug` | varchar(200) | Indexed (191 prefix) |

**Group association:** `group_id` column is the authoritative link. There is no wp_postmeta or groupmeta involved. Query pattern: `WHERE group_id = %d`.

### `wp_bp_event_attendees` (RSVP table)
| Column | Type | Notes |
|--------|------|-------|
| `event_id` | bigint | FK → bp_events.id |
| `user_id` | bigint | FK → wp_users.ID |
| `status` | varchar(20) | `registered` or `waitlisted` |
| `ticket_id` | bigint NULL | Set for paid tickets |
| `order_id` | bigint NULL | Set for paid orders |

UNIQUE KEY `event_user (event_id, user_id)` — one row per user per event.

### `wp_bp_event_invites` (invite table — ready for BB-03)
| Column | Type | Notes |
|--------|------|-------|
| `event_id` | bigint | FK → bp_events.id |
| `inviter_id` | bigint | Who sent the invite |
| `invitee_id` | bigint | Who received it |
| `status` | varchar(20) | `pending`, `accepted`, `declined` |

UNIQUE KEY `event_invitee (event_id, invitee_id)` — one row per invitee per event.

### `wp_bp_eventmeta` (metadata)
| Column | Type | Notes |
|--------|------|-------|
| `event_id` | bigint | FK → bp_events.id |
| `meta_key` | varchar(255) | |
| `meta_value` | longtext | |

Available for storing activity IDs against events (e.g., `meta_key = 'bp_activity_id'`).

### Key Query Functions
```php
// Get all events for a group (used by BB-01 group tab):
bp_events_get_events( array( 'group_id' => $group_id, 'status' => 'published' ) );

// Get events a user is attending (used by BB-04 profile tab):
bp_events_get_events( array( 'user_id' => $user_id ) );

// Get events a user is hosting (used by BB-04 profile tab):
bp_events_get_events( array( 'organizer_id' => $user_id ) );
```

---

## Privacy Enforcement

### How BuddyBoss Enforces Group Privacy in Activity

Source: `bp-activity/bp-activity-functions.php` lines 3720–3758 (function `bp_activity_user_can_read()`):

```php
// This is the platform's built-in logic (read-only reference):
if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $activity->component ) {
    $group = groups_get_group( $activity->item_id );
    if ( $group ) {
        if ( bp_loggedin_user_id() === $user_id ) {
            $retval = $group->user_has_access;
        } elseif ( 'private' === $group->status || 'hidden' === $group->status ) {
            if ( ! groups_is_user_member( $user_id, $activity->item_id )
                 || groups_is_user_banned( $user_id, $activity->item_id ) ) {
                $retval = false;
            }
        }
    }
}
```

**Important:** This check only fires when `$activity->component === buddypress()->groups->id`. Since our events use `component = buddypress()->events->id`, **this automatic check does NOT apply to us**. We must either:

1. Set `hide_sitewide = true` for private/hidden group events (hides from sitewide feed).
2. Add our own `bp_activity_user_can_read` filter to enforce events privacy.

### Available Privacy Hooks
```php
// Filter to add custom read-access logic for our event activity items:
add_filter( 'bp_activity_user_can_read', 'bp_events_activity_can_read', 10, 3 );
// $retval, $user_id, $activity
```

### Group Privacy Check Pattern (already in `BP_Event::user_can_view()`)
```php
// Source: class-bp-event.php lines 285-293
if ( ! empty( $this->group_id ) ) {
    $group = groups_get_group( $this->group_id );
    if ( 'public' === $group->status ) {
        return 'published' === $this->status;
    }
    return groups_is_user_member( bp_loggedin_user_id(), $this->group_id );
}
```

Reuse this exact pattern in the activity privacy filter.

---

## Code Examples

### Full Registration Sequence for Activity Integration

```php
// Source pattern: bp-groups/bp-groups-activity.php + bp-activity-functions.php

// Step 1: Register action types.
function bp_events_register_activity_actions() {
    if ( ! bp_is_active( 'activity' ) ) {
        return;
    }
    $bp = buddypress();
    bp_activity_set_action(
        $bp->events->id, 'event_created',
        __( 'Created an event', 'buddyboss' ),
        'bp_events_format_action_event_created',
        __( 'Events', 'buddyboss' ),
        array( 'activity', 'member', 'group', 'member_groups' )
    );
    bp_activity_set_action(
        $bp->events->id, 'event_rsvp',
        __( 'RSVPed to an event', 'buddyboss' ),
        'bp_events_format_action_event_rsvp',
        __( 'Events', 'buddyboss' ),
        array( 'activity', 'member', 'group', 'member_groups' )
    );
}
add_action( 'bp_register_activity_actions', 'bp_events_register_activity_actions' );

// Step 2: Format callback (returns human-readable action string).
function bp_events_format_action_event_created( $action, $activity ) {
    $user_link  = bp_core_get_userlink( $activity->user_id );
    $event      = bp_events_get_event( $activity->secondary_item_id );
    $event_link = $event ? '<a href="' . esc_url( bp_events_get_event_permalink( $event ) ) . '">' . esc_html( $event->title ) . '</a>' : '';
    return sprintf( __( '%1$s created the event %2$s', 'buddyboss' ), $user_link, $event_link );
}

// Step 3: Post activity after event save.
function bp_events_post_activity_on_create( BP_Event $event ) {
    if ( ! bp_is_active( 'activity' ) || empty( $event->id ) ) {
        return;
    }
    // Only on initial creation (date_created == date_modified).
    if ( $event->date_created !== $event->date_modified ) {
        return;
    }
    $hide_sitewide = false;
    if ( ! empty( $event->group_id ) ) {
        $group         = groups_get_group( $event->group_id );
        $hide_sitewide = ( 'public' !== $group->status );
    }
    bp_activity_add( array(
        'user_id'           => $event->organizer_id,
        'component'         => buddypress()->events->id,
        'type'              => 'event_created',
        'item_id'           => ! empty( $event->group_id ) ? $event->group_id : $event->id,
        'secondary_item_id' => $event->id,
        'primary_link'      => bp_events_get_event_permalink( $event ),
        'hide_sitewide'     => $hide_sitewide,
    ) );
}
add_action( 'bp_events_after_event_save', 'bp_events_post_activity_on_create', 10, 1 );
```

### Group Extension — Minimal Working Implementation

```php
// Source: class-bp-group-extension.php (API); bp-forums/groups.php (pattern)

class BP_Events_Group_Extension extends BP_Group_Extension {

    public function __construct() {
        parent::init( array(
            'slug'              => 'events',
            'name'              => __( 'Events', 'buddyboss' ),
            'nav_item_name'     => __( 'Events', 'buddyboss' ),
            'nav_item_position' => 25,
            'enable_nav_item'   => true,
            'visibility'        => 'public',
            // 'access' and 'show_tab' default to 'member' for private groups automatically.
        ) );
    }

    public function display( $group_id = null ) {
        $group_id = $group_id ?: bp_get_current_group_id();
        // Pass group_id to template via localized data or global.
        $GLOBALS['bp_events_current_group_id'] = $group_id;
        bp_get_template_part( 'groups/single/events' );
    }
}

add_action( 'bp_init', function() {
    if ( bp_is_active( 'groups' ) ) {
        bp_register_group_extension( 'BP_Events_Group_Extension' );
    }
}, 11 );
```

### REST: Fetching Group Members for Invite Picker

```javascript
// Source: endpoint from class-bp-rest-group-membership-endpoint.php lines 63-89

// GET /buddyboss/v1/groups/{group_id}/members?per_page=50&search=alice
fetch( `${bpEventsCreate.restUrl.replace('/events','')}/groups/${groupId}/members?per_page=50`, {
    headers: { 'X-WP-Nonce': bpEventsCreate.nonce }
} )
.then( r => r.json() )
.then( members => {
    // members is an array of member objects with id, name, avatar_urls
} );
```

---

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit (via WP test suite, if configured) |
| Config file | None detected — see Wave 0 |
| Quick run command | `wp eval 'bp_events_get_events(["group_id"=>1]);'` (smoke check via WP-CLI) |
| Full suite command | Not yet configured |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| BB-01 | Group events tab renders calendar scoped to group | manual | Visit `/groups/{slug}/events/` in browser | Wave 0 |
| BB-01 | `bp_events_get_events(['group_id'=>X])` returns only group X events | unit | PHPUnit once configured | Wave 0 |
| BB-02 | Creating event posts activity item | integration | `wp eval` smoke check | Wave 0 |
| BB-02 | Private group event has `hide_sitewide=1` in DB | integration | SQL: `SELECT hide_sitewide FROM wp_bp_activity WHERE type='event_created'` | Wave 0 |
| BB-03 | Invite picker fetches `/groups/{id}/members` | manual | Browser network tab on create screen | Wave 0 |
| BB-04 | Profile `/events/attending` lists events | manual | Visit member profile | Wave 0 |

### Wave 0 Gaps
- [ ] No automated test infrastructure detected in `src/bp-events/` — all validation is manual/WP-CLI for this phase
- [ ] Consider adding `tests/` directory with PHPUnit bootstrap for future phases

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `BP_Groups_Member::get_all_for_group()` | `groups_get_group_members()` → `BP_Group_Member_Query` | BuddyPress 2.0 | Old method deprecated; do not use |
| Setting properties directly on `BP_Group_Extension` subclass | Call `parent::init( $args )` in constructor | BuddyPress 1.8 | Pre-1.8 pattern still works but is legacy; use `init()` |
| `bp_activity_add()` with `component_name` / `component_action` keys | Use `component` / `type` keys | BuddyPress 2.0 | Old keys still accepted for BC but deprecated |

---

## Open Questions

1. **Should event activity use `component = 'events'` or `component = 'groups'`?**
   - What we know: Using `component = 'groups'` gives automatic group privacy enforcement in `bp_activity_user_can_read()`. Using `component = 'events'` requires a custom privacy filter but keeps the events feed logically separate.
   - What's unclear: Which approach BuddyBoss would consider canonical for a platform extension.
   - Recommendation: Use `component = buddypress()->events->id` (events) with a custom `bp_activity_user_can_read` filter. This keeps feeds clean and avoids event items appearing in the "Groups" filter dropdown in the activity feed.

2. **Does BB-03 require the invite to arrive as a notification?**
   - What we know: `wp_bp_event_invites` table already exists. Notifications component is separate.
   - What's unclear: Phase 3 requirements don't mention notifications explicitly; they say "send invites."
   - Recommendation: Phase 3 scope = write the invite row to `wp_bp_event_invites`. Leave BuddyBoss notification dispatch to a future phase unless the planner decides otherwise.

3. **Group Events tab: calendar view or list view for BB-01?**
   - What we know: The global events directory already uses FullCalendar. The requirement says "calendar view."
   - Recommendation: Reuse the existing `bp-events-calendar.js` and `bpEventsSettings` localization pattern inside the group tab template, passing `?group_id=X` to the events REST endpoint.

---

## Sources

### Primary (HIGH confidence)
- `/Users/tom/Local Sites/Events/Plugins/buddyboss-platform/bp-groups/classes/class-bp-group-extension.php` — full `BP_Group_Extension` API, `init()` params, `bp_register_group_extension()` implementation
- `/Users/tom/Local Sites/Events/Plugins/buddyboss-platform/bp-activity/bp-activity-functions.php` lines 2064–2210 — `bp_activity_add()` full parameter list; lines 332–390 `bp_activity_set_action()`; lines 3720–3758 `bp_activity_user_can_read()` privacy logic
- `/Users/tom/Local Sites/Events/Plugins/buddyboss-platform/bp-groups/bp-groups-functions.php` lines 849–939 — `groups_get_group_members()` full signature
- `/Users/tom/Local Sites/Events/Plugins/buddyboss-platform/bp-groups/classes/class-bp-rest-group-membership-endpoint.php` lines 63–89 — REST route `GET /buddyboss/v1/groups/{id}/members`
- `/Users/tom/Local Sites/Events/Plugins/buddyboss-platform/bp-forums/groups.php` — real-world `BP_Group_Extension` subclass implementation
- `/Users/tom/Local Sites/Events/Plugins/buddyboss-platform/bp-forums/classes/class-bp-forums-component.php` line 165 — `bp_register_group_extension()` call pattern
- `/Users/tom/Local Sites/Events/buddyboss-events/src/bp-events/classes/class-bp-event.php` — `BP_Event::user_can_view()` privacy logic; full property list
- `/Users/tom/Local Sites/Events/buddyboss-events/src/bp-events/bp-events-functions.php` — DB schema (all four tables with column definitions); `bp_events_get_events()` query API
- `/Users/tom/Local Sites/Events/buddyboss-events/src/bp-events/classes/class-bp-events-component.php` — existing `setup_nav()` with attending/hosting sub-nav already registered

---

## Metadata

**Confidence breakdown:**
- Standard Stack: HIGH — all APIs read directly from platform source
- Architecture: HIGH — `BP_Group_Extension` pattern verified from forums real-world implementation
- Pitfalls: HIGH — `hide_sitewide` and activity privacy verified from platform source code
- Data model: HIGH — schema read directly from `bp_events_install()` in `bp-events-functions.php`

**Research date:** 2026-03-16
**Valid until:** 2026-09-16 (platform PHP APIs are stable; REST routes are versioned at v1)
