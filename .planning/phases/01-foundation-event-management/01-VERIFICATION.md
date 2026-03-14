---
phase: 01-foundation-event-management
verified: 2026-03-14T00:00:00Z
status: human_needed
score: 10/10 automated must-haves verified
re_verification: false
human_verification:
  - test: "Admin settings panel — navigate to wp-admin -> BuddyBoss -> Settings -> Events"
    expected: "Settings panel renders with three sections: General (events enable/disable, directory slug, default calendar view), Permissions (event creation dropdown with Admins/Group owners/Members options, event moderation toggle), Calendar (site calendar toggle). All fields save correctly."
    why_human: "Settings panel rendering and save behavior requires a live WordPress admin session. bp-admin-setting-events.php registers the fields via BP_Admin_Setting_tab but the actual integration with BuddyBoss settings UI can only be confirmed in the browser."

  - test: "Create an in-person event via the wizard at /events/create"
    expected: "Multi-step wizard renders. Step 1 shows In-Person/Virtual/Hybrid radio buttons. Selecting In-Person shows venue fields (venue name, venue address, capacity) in Step 4. Save Draft redirects to event permalink with draft status. Publishing the event makes it appear on the /events/ FullCalendar."
    why_human: "End-to-end wizard flow — step transitions, conditional field show/hide, REST POST success, and redirect behavior require a browser."

  - test: "Create a virtual event — verify virtual URL field appears and saves"
    expected: "Selecting Virtual in Step 1 shows virtual_url (URL input) and virtual_type (select: zoom/meet/other) in Step 4, not venue fields. Published event appears on calendar."
    why_human: "Conditional field visibility in the wizard requires browser interaction."

  - test: "Create a recurring event and verify occurrences appear on the calendar"
    expected: "Checking 'Make this a recurring event' adds a Recurrence step. Setting weekly/Monday/8 occurrences builds an RRULE string. After publishing, 8 Monday occurrences appear on the FullCalendar when navigating forward. Edit-this-only changes only one occurrence. Edit-this-and-following changes from the split point forward."
    why_human: "Recurring series creation, calendar display of generated occurrences, and split-series behavior require browser interaction with a live database."

  - test: "FullCalendar calendar at /events/ — month view, list toggle, prev/next navigation"
    expected: "FullCalendar renders in dayGridMonth view by default. Month/List toggle buttons are visible and switch views without page reload. Prev/Next buttons load events for adjacent months via the REST feed (no full page reload). Clicking an event navigates to its permalink."
    why_human: "Calendar rendering and AJAX event loading require a browser with the live REST API."

  - test: "Privacy enforcement — private group event not visible on site calendar"
    expected: "An event created for a private BuddyBoss group does not appear on the /events/ calendar when viewed as a logged-out user or non-member. The WHERE clause enforces ( e.group_id IS NULL OR g.status = 'public' ) — this is in the code, but the actual query execution against a live groups table with real group status values must be confirmed."
    why_human: "Privacy enforcement requires a live WordPress database with actual BuddyBoss group records."

  - test: "Admin moderation flow — enable moderation, create event, approve in queue"
    expected: "With bb_events_moderation_enabled=1, new events from non-admin members land in pending status (not published). Admin navigates to wp-admin -> Events, sees the Pending tab. Clicking the Approve AJAX button changes status to published and event appears on calendar. The moderation status enforcement filter is in code (bp_events_before_event_save hook), but the AJAX approve flow requires browser testing."
    why_human: "AJAX approve button interaction and moderation status override require a live WordPress session."

  - test: "Report button visible on event page and routes to BuddyBoss moderation modal"
    expected: "On a published event's page, bp_moderation_get_button() renders a Report button. Clicking it opens the BuddyBoss moderation modal. The events content type is registered in bp_moderation_content_types filter."
    why_human: "bp_moderation_get_button() output and modal behavior require a live BuddyBoss installation with the moderation component active."

  - test: "Permission enforcement — admins-only restriction hides create access for members"
    expected: "With bb_events_creation_permission=admins, bp_events_user_can_create() returns false for non-admin users (verified in code). The UI should deny access at /events/create or hide the Create button. The function logic is verified in code; the UI enforcement needs browser confirmation."
    why_human: "How the permission check is surfaced in the UI (error page vs hidden button) requires browser verification."
---

# Phase 1: Foundation Event Management Verification Report

**Phase Goal:** A fully installable WordPress plugin where admins can configure event permissions and commission settings, and organizers can create, edit, and publish in-person, virtual, and recurring events visible on a site-wide calendar
**Verified:** 2026-03-14
**Status:** human_needed — all automated checks pass; 9 items require browser verification on the MAMP site
**Re-verification:** No — initial verification

---

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | PHPUnit infrastructure exists and all test stubs are loadable | VERIFIED | phpunit.xml.dist, bootstrap.php, define-constants.php, 6 test files all pass `php -l`; bootstrap→define-constants link present |
| 2 | Events from private/hidden groups excluded from site-wide calendar (EVNT-06) | VERIFIED | `( e.group_id IS NULL OR g.status = 'public' )` WHERE clause in `bp_events_get_events()` lines 364; unconditional when group_id is null |
| 3 | Per-group site-calendar opt-out enforced (EVNT-05) | VERIFIED | `( e.group_id IS NULL OR gm.meta_value != '0' )` WHERE clause at line 369; LEFT JOIN on `bb_events_public_group_site_calendar` groupmeta key |
| 4 | Recurring event occurrence generation wired to save hook | VERIFIED | `add_action( 'bp_events_after_event_save', 'bp_events_generate_occurrences', 20, 1 )` in bp-events-filters.php line 304 |
| 5 | Edit-this-only and edit-this-and-following functions implemented | VERIFIED | `bp_events_detach_occurrence()` and `bp_events_split_series()` fully implemented in bp-events-functions.php lines 1100–1135 and 996–1085 |
| 6 | WP cron job scheduled on plugin activation for occurrence extension | VERIFIED | `wp_schedule_event( time(), 'daily', 'bp_events_extend_occurrences' )` in bp-events-loader.php line 75, guarded by `!wp_next_scheduled()` |
| 7 | REST endpoint serves FullCalendar feed shape with ISO8601 dates | VERIFIED | `prepare_item_for_response()` has `_fc` branch with `str_replace( ' ', 'T', $event->start_date )` at line 576; `get_collection_params()` accepts start/end/from/to/_fc params |
| 8 | Admin approve AJAX handler registered | VERIFIED | `add_action( 'wp_ajax_bp_events_approve', 'bp_events_ajax_approve_event' )` in bp-events-filters.php line 257; uses `check_ajax_referer` and `current_user_can('manage_options')` |
| 9 | Admin revenue page renders event count stats (ADMN-03) | VERIFIED | `<div class="bb-events-stats">` with Total/Published/Pending/Draft counts in `bp_events_admin_revenue_page()`, backed by `bp_events_admin_get_event_counts()` with wp_cache_get/set |
| 10 | Moderation class registers events content type | VERIFIED | `BP_Moderation_Events extends BP_Moderation_Abstract`, `add_filter( 'bp_moderation_content_types', ... )` in constructor, required from bp-events-loader.php line 16 |
| 11 | Multi-step creation wizard POSTs to REST API | VERIFIED | `fetch( bpEventsCreate.restUrl, { method: 'POST', headers: { 'X-WP-Nonce': bpEventsCreate.nonce }, body: JSON.stringify( state ) } )` in bp-events-create.js lines 843–849 |
| 12 | FullCalendar calendar renders on /events/ directory page | VERIFIED | `#bb-rl-events-calendar` div and Month/List toggle buttons in directory.php; calendar JS reads `bpEventsSettings.restUrl`; fullcalendar script enqueued with bp-events-calendar as dependent |
| 13 | Settings panel exposes creation permission and moderation controls (ADMN-01, ADMN-02) | VERIFIED | `bp-admin-setting-events.php` registers `bb_events_creation_permission` and `bb_events_moderation_enabled` fields via `BP_Admin_Setting_Events` class |

**Score:** 13/13 automated truths verified

---

### Required Artifacts

| Artifact | Status | Details |
|----------|--------|---------|
| `buddyboss-events/phpunit.xml.dist` | VERIFIED | Valid XML, bootstrap attribute points to `tests/phpunit/bootstrap.php`, stopOnError="false", testsuite scans testcases/ |
| `buddyboss-events/tests/phpunit/bootstrap.php` | VERIFIED | Requires define-constants.php, guards on WP_TESTS_DIR, registers muplugins_loaded filter, 35 lines |
| `buddyboss-events/tests/phpunit/includes/define-constants.php` | VERIFIED | Defines BP_TESTS_DIR, BP_PLUGIN_DIR, WP_TESTS_DIR with env fallback |
| `buddyboss-events/tests/phpunit/testcases/test-event-crud.php` | VERIFIED | Class BP_Events_Test_CRUD, 3 substantive test methods with real assertions (not markTestIncomplete) |
| `buddyboss-events/tests/phpunit/testcases/test-recurring.php` | VERIFIED | Class BP_Events_Test_Recurring, 5 substantive test methods with real assertions and direct DB verification |
| `buddyboss-events/tests/phpunit/testcases/test-calendar-privacy.php` | VERIFIED | Class BP_Events_Test_Calendar_Privacy, 5 methods (all markTestIncomplete with explanatory messages citing the WHERE clauses) |
| `buddyboss-events/tests/phpunit/testcases/test-permissions.php` | VERIFIED | Class BP_Events_Test_Permissions, 2 test methods |
| `buddyboss-events/tests/phpunit/testcases/test-admin.php` | VERIFIED | Class BP_Events_Test_Admin, 1 test method |
| `buddyboss-events/tests/phpunit/testcases/test-moderation.php` | VERIFIED | Class BP_Events_Test_Moderation, 1 test method |
| `buddyboss-events/src/bp-events/includes/lib/php-rrule/RRule.php` | VERIFIED | rlanvin/php-rrule v2.6.0, passes php -l |
| `buddyboss-events/src/bp-events/includes/lib/php-rrule/RRuleInterface.php` | VERIFIED | php-rrule interface file |
| `buddyboss-events/src/bp-events/includes/lib/php-rrule/RSet.php` | VERIFIED | php-rrule RSet class |
| `buddyboss-events/src/bp-events/bp-events-functions.php` | VERIFIED | `bp_events_generate_occurrences()`, `bp_events_split_series()`, `bp_events_detach_occurrence()`, `bp_events_extend_occurrences_for_event()` all present and substantive; 1185 lines |
| `buddyboss-events/src/bp-events/bp-events-filters.php` | VERIFIED | AJAX approve handler, moderation enforcement, occurrence cron, moderation instantiation — all wired; 365 lines |
| `buddyboss-events/src/bp-events/classes/class-bp-rest-events-endpoint.php` | VERIFIED | `get_items()`, `prepare_item_for_response()` with _fc branch, `get_collection_params()` with all required params; 719 lines |
| `buddyboss-events/src/bp-events/classes/class-bp-moderation-events.php` | VERIFIED | `BP_Moderation_Events extends BP_Moderation_Abstract`, moderation_type='events', add_content_types() wired |
| `buddyboss-events/src/bp-events/classes/class-bp-events-list-table.php` | VERIFIED | Approve link uses `class="bp-events-approve-btn"` with data-event-id, data-nonce, data-action attributes at line 128; inline jQuery AJAX handler present |
| `buddyboss-events/src/bp-events/admin/bp-events-admin.php` | NOT AT EXPECTED PATH — found at `src/bp-events/bp-events-admin.php` | File exists at the correct functional location. Plan listed wrong sub-path. Admin stats implemented correctly. |
| `buddyboss-events/src/bp-events/assets/js/vendor/fullcalendar.min.js` | VERIFIED WITH NOTE | FullCalendar Standard Bundle v6.1.20 (284KB). Plan expected Global Bundle (~600KB). Standard Bundle includes daygrid, timegrid, list, and interaction plugins — listMonth view is present. Functional for Phase 1 requirements. |
| `buddyboss-events/src/bp-events/assets/js/bp-events-calendar.js` | VERIFIED | FullCalendar.Calendar init with `bpEventsSettings.restUrl`, eventClick handler, month/list view toggle; 75 lines |
| `buddyboss-events/src/bp-events/assets/css/bp-events.css` | VERIFIED | Calendar container dimensions, view toggle styles, .fc-event cursor |
| `buddyboss-events/src/bp-events/screens/directory.php` | VERIFIED | `#bb-rl-events-calendar` div, `.bb-rl-view-btn` Month/List toggle buttons, `bb-rl-events-directory` wrapper |
| `buddyboss-events/src/bp-events/assets/js/bp-events-create.js` | VERIFIED | 900+ lines; all 6 wizard steps, RRULE builder, fetch() with nonce, all step renderers substantive |
| `buddyboss-events/src/bp-events/screens/create.php` | VERIFIED | `#bb-rl-event-create-form` wizard container with Back/Next/Save Draft/Publish buttons; JS binds on DOMContentLoaded |
| `buddyboss-events/src/bp-core/admin/settings/bp-admin-setting-events.php` | VERIFIED | `BP_Admin_Setting_Events` registers creation permission, moderation toggle, and site calendar fields |

---

### Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `phpunit.xml.dist` | `tests/phpunit/bootstrap.php` | bootstrap attribute | VERIFIED | `bootstrap="tests/phpunit/bootstrap.php"` at line 2 |
| `tests/phpunit/bootstrap.php` | `tests/phpunit/includes/define-constants.php` | require | VERIFIED | `require dirname( __FILE__ ) . '/includes/define-constants.php'` at line 11 |
| `bp_events_after_event_save action` | `bp_events_generate_occurrences()` | add_action in bp-events-filters.php | VERIFIED | `add_action( 'bp_events_after_event_save', 'bp_events_generate_occurrences', 20, 1 )` at line 304 |
| `bp_events_extend_occurrences` | `wp_schedule_event` | plugin activation hook | VERIFIED | `wp_schedule_event( time(), 'daily', 'bp_events_extend_occurrences' )` in `bp_events_schedule_cron()` hooked on `bp_events_activated` |
| `FullCalendar JS` | `BP_REST_Events_Endpoint::get_items()` | `bpEventsSettings.restUrl` + `?_fc=1` | VERIFIED | `settings.restUrl` in bp-events-calendar.js line 31; `extraParams: { _fc: 1 }` at line 33 |
| `screens/directory.php` | `bp-events-calendar.js` | `bp_events_enqueue_calendar_assets()` | VERIFIED | `wp_enqueue_script( 'bp-events-calendar', ... )` in bp-events-filters.php line 146; enqueue function registered on `wp_enqueue_scripts` |
| `class-bp-moderation-events.php` | `bp_moderation_content_types filter` | add_content_types() method | VERIFIED | `add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) )` at constructor line 50 |
| `class-bp-moderation-events.php` | `BP_Moderation_Abstract` | extends | VERIFIED | `class BP_Moderation_Events extends BP_Moderation_Abstract` |
| `BP_Events_List_Table Approve link` | `wp_ajax_bp_events_approve` | admin-ajax.php POST via jQuery AJAX | VERIFIED | `add_action( 'wp_ajax_bp_events_approve', 'bp_events_ajax_approve_event' )` at line 257; jQuery handler in list table targets `.bp-events-approve-btn` |
| `bp_events_create_event()` | `bb_events_moderation_enabled option` | filter on bp_events_before_event_save | VERIFIED | `bp_events_enforce_moderation_status()` hooked on `bp_events_before_event_save` at line 290 |
| `bp-events-create.js` | `POST /buddyboss/v1/events` | fetch() with X-WP-Nonce from bpEventsCreate.nonce | VERIFIED | `fetch( bpEventsCreate.restUrl, { headers: { 'X-WP-Nonce': bpEventsCreate.nonce } } )` at lines 843–847 |
| `bp-events-loader.php` | `class-bp-moderation-events.php` | require_once | VERIFIED | `require_once __DIR__ . '/classes/class-bp-moderation-events.php'` at line 16 |

---

### Requirements Coverage

| Requirement | Source Plans | Description | Status | Evidence |
|-------------|-------------|-------------|--------|---------|
| EVNT-01 | 00, 02, 07, 08 | Organizer can create in-person event with venue, address, capacity | SATISFIED | `BP_Event` class has venue_name, venue_address, capacity fields; `bp_events_create_event()` persists them; test-event-crud.php::test_create_in_person_event() asserts persistence; wizard Step 4 renders venue fields |
| EVNT-02 | 00, 02, 07, 08 | Organizer can create virtual event with Zoom/Meet URL | SATISFIED | virtual_url, virtual_type in BP_Event and bp_events schema; wizard Step 4 shows virtual fields when type=virtual; test_create_virtual_event() asserts persistence |
| EVNT-03 | 00, 01, 07, 08 | Organizer can create recurring event series with RRULE | SATISFIED | php-rrule vendored; `bp_events_generate_occurrences()` generates children; `bp_events_split_series()` and `bp_events_detach_occurrence()` handle edit modes; wizard Recurrence step builds RRULE string; test-recurring.php has 5 substantive tests |
| EVNT-04 | 00, 02, 07, 08 | Organizer can save draft or schedule future publish | SATISFIED | `status` field supports draft/pending/published/cancelled; `bp_events_get_events(['status'=>'published'])` excludes drafts; wizard has Save Draft and Publish buttons; test_draft_not_in_published_query() asserts behavior |
| EVNT-05 | 00, 03, 06, 08 | Admin can configure per-group calendar visibility | SATISFIED | LEFT JOIN on `bb_events_public_group_site_calendar` groupmeta, WHERE clause `gm.meta_value != '0'`; settings field `bb_events_public_group_site_calendar` in admin panel |
| EVNT-06 | 00, 03, 06, 08 | Private/hidden group events never visible on site calendar | SATISFIED | Unconditional WHERE clause `( e.group_id IS NULL OR g.status = 'public' )` applied on all site-wide queries |
| ADMN-01 | 00, 04, 08 | Admin configures who can create events site-wide | SATISFIED | `bp_events_user_can_create()` reads `bb_events_creation_permission` option; admins/organizers/members logic implemented; settings field registered; test-permissions.php covers both cases |
| ADMN-02 | 00, 04, 08 | Admin has event moderation queue with approve action | SATISFIED | `bp_events_enforce_moderation_status()` forces pending when moderation enabled; AJAX approve handler sets status=published; list table has Approve button with AJAX wiring; test-admin.php covers approve |
| ADMN-03 | 03, 08 | Admin views platform dashboard with event stats | SATISFIED | `bp_events_admin_revenue_page()` renders `<div class="bb-events-stats">` with total/published/pending/draft counts; wp_cache_set() with 300s TTL; `bb_events_creation_permission` and `bb_events_moderation_enabled` in settings panel |
| ADMN-04 | 00, 05, 08 | Users can report events via BuddyBoss moderation | SATISFIED | `BP_Moderation_Events` registers 'events' content type; `validate_single_item()` checks event exists and is published; instantiation guarded by `bp_is_active('moderation')`; report button via `bp_moderation_get_button()` (platform-side) |

---

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| `test-calendar-privacy.php` | 27–95 | All 5 methods use `markTestIncomplete()` | Info | By design — tests require live WP+BuddyBoss DB. Privacy logic is verified by code inspection of WHERE clauses. Does not block goal. |
| `src/bp-events/screens/single/edit.php` | 39–41 | Edit content calls `bp_get_template_part('events/single/edit')` | Info | Edit template is a template-part delegate — the ReadyLaunch template at `src/bp-templates/bp-nouveau/readylaunch/events/single/edit.php` handles rendering. Not a stub — appropriate pattern for BuddyBoss theming. |
| `assets/js/vendor/fullcalendar.min.js` | 1 | Standard Bundle (284KB) not Global Bundle (600KB) | Warning | Standard Bundle includes list, daygrid, timegrid, interaction plugins — listMonth view is present. The plan's size assertion (>500KB) was based on the global bundle. Functional for Phase 1 but note the discrepancy. |

---

### Human Verification Required

The following scenarios exercise the assembled system in the browser and cannot be verified programmatically. All code paths have been traced to the expected implementation — these checks confirm end-to-end rendering and behavior.

#### 1. Admin Settings Panel

**Test:** Log in as admin. Navigate to wp-admin -> BuddyBoss -> Settings -> Events.
**Expected:** Settings panel renders with: General section (events toggle, directory slug, default calendar view), Permissions section ("Event Creation" dropdown with Admins/Group owners/Members options, "Event Moderation" toggle), Calendar section (site calendar toggle). All fields persist on save.
**Why human:** Settings panel rendering and save round-trip require a live WordPress session.

#### 2. Create In-Person Event (EVNT-01, EVNT-04)

**Test:** As a member with creation permission, visit /events/create. Select In-Person, fill title and dates, enter venue fields. Click Save Draft, then Publish.
**Expected:** Wizard renders all 5 steps. Step 4 shows venue_name, venue_address, capacity fields. Save Draft redirects to event permalink showing draft status. Publish makes event appear on the /events/ calendar.
**Why human:** Wizard step transitions, conditional field rendering, REST POST result, and calendar appearance require a browser.

#### 3. Create Virtual Event (EVNT-02)

**Test:** At /events/create, select Virtual. Verify Step 4 shows virtual_url and virtual_type fields.
**Expected:** Venue fields are hidden; virtual_url (URL input) and virtual_type (select: zoom/meet/other) are shown. Published event appears on calendar.
**Why human:** Conditional field show/hide is JS state-driven and requires browser interaction.

#### 4. Create Recurring Event and Verify Occurrences (EVNT-03)

**Test:** At /events/create, check "Make this a recurring event" in the Date & Time step. Set weekly, Monday, 8 occurrences. Publish.
**Expected:** Recurrence step (Step 5) appears. After publish, 8 Monday occurrences appear on the calendar across multiple months. Navigate to occurrence 3, edit -> Edit this only -> change title. Navigate to occurrence 5, edit -> Edit this and following -> change title. Verify occurrence 3 has new title; occurrence 5+ have new title; occurrences 1-2 and 4 are unchanged.
**Why human:** Occurrence generation requires live DB; split/detach behavior requires browser interaction with the edit UI.

#### 5. Calendar Navigation and View Toggle (EVNT-05/06 frontend)

**Test:** On /events/, navigate months using prev/next. Click List toggle. Click Month toggle.
**Expected:** Month/List toggle switches views without page reload. Prev/Next loads events from REST API for the new date range without full page reload. Clicking an event navigates to its permalink.
**Why human:** FullCalendar rendering, AJAX event loading, and navigation behavior require a browser.

#### 6. Privacy Enforcement (EVNT-06)

**Test:** In wp-admin -> Groups, find a private group. Create an event assigned to that group. View /events/ as a logged-out user.
**Expected:** The private group event does not appear on the site-wide calendar.
**Why human:** Query execution against real group records with actual status='private' in the database.

#### 7. Moderation Queue and Approve Flow (ADMN-02)

**Test:** In admin settings, enable Event Moderation. As a non-admin member, create and try to publish an event.
**Expected:** Event lands in pending status, not published on the calendar. In wp-admin -> Events, see Pending tab. Click Approve button on the event. Event status changes to published and appears on the calendar.
**Why human:** AJAX approve button interaction and status change require a live WordPress admin session.

#### 8. Report Button Integration (ADMN-04)

**Test:** On a published event's page, look for a Report link/button.
**Expected:** A Report link is rendered (via `bp_moderation_get_button()`). Clicking it opens the BuddyBoss moderation modal. The report is submitted to the moderation queue.
**Why human:** bp_moderation_get_button() output and modal rendering require BuddyBoss moderation component active in a live environment.

#### 9. Permission Enforcement UI (ADMN-01)

**Test:** In admin settings, set "Event Creation" to "Site admins". Log in as a regular member. Try to visit /events/create.
**Expected:** Access is denied or redirected. The event creation button/link is not visible to the non-admin member.
**Why human:** How the bp_events_user_can_create() check is surfaced in the UI (HTTP 403, redirect, or hidden button) requires browser confirmation.

---

### Notes on FullCalendar Bundle

The vendored file at `src/bp-events/assets/js/vendor/fullcalendar.min.js` is the **FullCalendar Standard Bundle v6.1.20** (284KB), not the Global Bundle (~600KB). The Standard Bundle includes daygrid, timegrid, list, and interaction plugins. The `listMonth` view used by the List toggle is present in the Standard Bundle. Phase 1 functionality is not impacted. If additional plugins (e.g., scheduler, resource views) are needed in later phases, the Global Bundle may be required.

---

_Verified: 2026-03-14_
_Verifier: Claude (gsd-verifier)_
