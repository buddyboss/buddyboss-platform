# Phase 1: Foundation + Event Management - Context

**Gathered:** 2026-03-13
**Status:** Ready for planning

<domain>
## Phase Boundary

A fully installable WordPress plugin where admins configure event permissions and moderation settings, and organizers can create, edit, and publish in-person, virtual, and recurring events visible on a site-wide calendar. Payments, ticketing, and BuddyBoss group/activity/profile integration are out of scope for this phase.

</domain>

<decisions>
## Implementation Decisions

### Recurring Event Editing
- Modal choice when clicking Edit on any occurrence: "Edit this event / Edit this and following / Edit all events in series" — matches Google Calendar UX
- "Edit this and following" splits the series: the original series ends before the edited occurrence, and a new series is created from that point forward. No in-place RRULE mutation.
- Occurrences are stored as child rows in `bp_events` on publish — each child has its own row with `parent_event_id` set. Existing schema supports this.
- Pre-generate occurrences 2 years ahead on publish. A WP cron job extends the window as time passes.

### Calendar View
- FullCalendar JS library — month/week/list support, recurring event awareness, REST-compatible
- Launch with Month + List views only. Week view deferred.
- Events loaded dynamically via REST API (existing `BP_REST_Events_Endpoint`) — no full page reload on month navigation
- Default view: honour the `bb_events_default_calendar_view` admin setting (defaults to month). Setting callback already exists in `bp-events-admin.php`.

### Event Creation Form
- Multi-step wizard at `/events/create` — matches the `create.php` ReadyLaunch template stub that already exists
- Steps: Event Type → Basic Details → Date & Time → Location/Virtual → Recurrence (conditional) → Review & Publish
- Event type (in-person / virtual / hybrid) drives JS show/hide of venue vs virtual URL fields — no page reload
- Final step shows "Save Draft" and "Publish" buttons — satisfies EVNT-04. Status field in `BP_Event` already supports `draft`.

### Claude's Discretion
- Exact FullCalendar configuration options and event colour/styling
- Recurrence cron job scheduling interval
- Form validation error messaging patterns
- Loading/skeleton states on calendar month navigation

</decisions>

<code_context>
## Existing Code Insights

### Reusable Assets
- `BP_Event` class (`class-bp-event.php`): Full CRUD object, all fields defined including `recurrence_rule`, `parent_event_id`, `status`. Ready to use — no changes needed for recurring storage model.
- `bp_events_create_event()` / `bp_events_update_event()`: CRUD functions already wired to `BP_Event`. Recurring child row creation can call these directly.
- `bp-events-admin.php`: Admin settings callbacks fully implemented — `bb_events_default_calendar_view`, `bb_events_creation_permission`, `bb_events_moderation_enabled` all exist.
- `event-card.php` (ReadyLaunch): Event card template exists with BEM classes (`bb-rl-event-card`), date badge, type label, venue/virtual location display.
- `create.php` (ReadyLaunch): Template stub exists at correct path — needs wizard markup added.
- `BP_REST_Events_Endpoint` + `BP_REST_Events_Settings_Endpoint`: Class stubs registered in `BP_Events_Component::rest_api_init()` — need implementation for FullCalendar to consume.
- 4 DB tables (`bp_events`, `bp_eventmeta`, `bp_event_attendees`, `bp_event_invites`): Schema already defined in `bp_events_install()` — all columns for recurring events present.

### Established Patterns
- BuddyBoss component pattern (`BP_Component` subclass): Followed correctly in `BP_Events_Component`. All new features should hook into this component lifecycle.
- BP template functions (e.g. `bp_event_title()`, `bp_get_event_permalink()`): Template tags follow BuddyBoss convention — new template tags for calendar should match this pattern.
- `bp_parse_args()` for function args: Used consistently in CRUD functions — continue this pattern.
- `bp_get_option()` / `bp_update_option()`: Used for all settings — maintain this for any new settings.

### Integration Points
- REST API endpoint stubs (`BP_REST_Events_Endpoint`): FullCalendar's `events` feed URL must point here — implement `get_items()` to return events in FullCalendar's expected JSON shape.
- `bp_setup_components` action (priority 9): Component already hooked — no changes needed.
- `bp-core/admin/settings/bp-admin-setting-events.php`: Settings fields registered here — already wired to admin callbacks in `bp-events-admin.php`.

</code_context>

<specifics>
## Specific Ideas

- Recurring event edit modal should feel like Google Calendar — three clear choices with plain-language labels
- Calendar should be the visual centrepiece of the events directory; list view is a toggle, not the primary experience

</specifics>

<deferred>
## Deferred Ideas

- "Tiered by plan level" permission enforcement (free/pro/plus/ultimate) — the permission hook (`bp_events_user_can_create`) is scaffolded; plan-tier detection wired in Phase 2 when BuddyBoss plan data is available
- Week view on calendar — deferred post-launch
- Drag-and-drop event rescheduling on calendar — deferred

</deferred>

---

*Phase: 01-foundation-event-management*
*Context gathered: 2026-03-13*
