---
name: Phase 1 Development Progress
description: What has been built for Phase 1 of the Events plugin
type: project
---

## Phase 1 Status: IN PROGRESS (scaffolded, not yet tested)

## Files created in /Users/tom/Local Sites/Events/buddyboss-events/src/

### bp-events/ (component)
- bp-events-loader.php — hooks bp_setup_events onto bp_setup_components at priority 9
- bp-events-functions.php — DB install (dbDelta), CRUD functions, permission checks, slug/URL helpers
- bp-events-filters.php — registers component in bp_optional_components filter, autoloader map, enqueues assets
- bp-events-admin.php — WP Admin menus, settings callbacks for all admin fields
- bp-events-template.php — template tags (bp_event_id, bp_event_title, etc.), screen functions
- bp-events-cache.php — cache group registration, cache invalidation hooks

### bp-events/classes/
- class-bp-events-component.php — extends BP_Component, setup_globals (DB tables), setup_nav, rest_api_init
- class-bp-event.php — Event model (populate, save, delete, user_can_view, user_can_edit)
- class-bp-events-list-table.php — WP_List_Table for admin Events list
- class-bp-rest-events-endpoint.php — full REST CRUD at /buddyboss/v1/events, iCal, gcal-url, occurrences, series
- class-bp-rest-events-settings-endpoint.php — REST for /buddyboss/v1/events/settings (all sub-routes)

### bp-events/screens/
- screens/directory.php — directory screen handler
- screens/single/home.php — single event screen + bp_events_get_current_event()
- screens/single/edit.php — edit screen handler

### bp-templates/bp-nouveau/readylaunch/events/
- index.php — events directory with calendar/list toggle + filters
- event-card.php — event card component
- events-loop.php — loop template
- create.php — multi-step create form (5 steps, JS-driven)
- single/home.php — single event detail page with sidebar
- single/edit.php — edit form (recurring edit choice)

### bp-core/admin/settings/
- bp-admin-setting-events.php — BP_Admin_Setting_Events extends BP_Admin_Setting_tab, tab_order 60

## DB Tables to be created on activation
- wp_bp_events
- wp_bp_eventmeta
- wp_bp_event_attendees
- wp_bp_event_invites

## REST API endpoints (Phase 1)
- GET/POST /buddyboss/v1/events
- GET/PUT/DELETE /buddyboss/v1/events/{id}
- POST /buddyboss/v1/events/{id}/publish
- POST /buddyboss/v1/events/{id}/cancel
- GET /buddyboss/v1/events/{id}/occurrences
- PUT /buddyboss/v1/events/{id}/occurrence
- PUT /buddyboss/v1/events/{id}/series
- GET /buddyboss/v1/events/{id}/ical
- GET /buddyboss/v1/events/{id}/gcal-url
- GET/PUT /buddyboss/v1/events/settings (+ /commission, /stripe, /permissions)
- GET /buddyboss/v1/events/admin/revenue (+ /events)

## Next steps
1. Test that component loads in MAMP (check WP Admin → BuddyBoss → Components)
2. Run DB install to create tables
3. Test REST API endpoints via browser/Postman
4. Build the JavaScript for the create form and calendar (React/Vue components)
5. Wire up the ReadyLaunch CSS
6. Begin Phase 2 (ticketing + Stripe Connect)
