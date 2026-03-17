# Pitfalls Research

**Domain:** WordPress events plugin — v2.0 feature additions to an existing BuddyBoss-integrated plugin
**Researched:** 2026-03-17
**Confidence:** HIGH for WordPress core behavior; MEDIUM for BuddyBoss-specific interactions (verify against current BuddyBoss docs)

---

## Critical Pitfalls

### Pitfall 1: Taxonomy Archive Pages Expose Private Group Events to the Public

**What goes wrong:**
When you register `event_category` and `event_tag` taxonomies as `public => true`, WordPress automatically generates archive URLs like `/event-category/workshops/`. These archive pages run `WP_Query` against all published posts of the associated post type. However, "published" in WordPress means `post_status = 'publish'` — it has no concept of BuddyBoss group privacy. A group event that is restricted to private group members will still appear in the taxonomy archive because the event's `bp_event` post has `post_status = 'publish'`.

This is confirmed by a real BuddyBoss bug pattern: private group documents showing to non-members was a documented issue in `buddyboss-platform` issue #1692. The same mechanism applies to events.

**Why it happens:**
BuddyBoss group privacy is enforced at the component level (via `bp_has_groups()` and access checks in BuddyBoss template logic), not at the WordPress post_status level. A "private group event" is a published WordPress post with a `group_id` meta value — WordPress's native archive queries know nothing about this relationship.

**How to avoid:**
- Hook into `pre_get_posts` on all taxonomy archive queries for `bp_event` post type
- On each archive query, check whether the current user is logged in and is a member of the event's associated group
- The cleanest implementation: filter out any event post IDs where `group_id` is set and the current user is not a member of that group — use `bp_is_item_member()` or the equivalent BuddyBoss group membership check
- Alternatively: register the taxonomy with `public => false` and `publicly_queryable => false` (which prevents WordPress from generating archive pages) and build your own filtered archive endpoint via REST API only — this is the safer default
- At minimum: add a `posts_where` filter that excludes group-restricted events from unauthenticated requests

**Warning signs:**
- Taxonomy registered with `public => true` and no `pre_get_posts` hook filtering by group membership
- Events in private groups visible when browsing `/event-category/[slug]/` while logged out
- No test case covering "private group event does not appear in public taxonomy archive"

**Phase to address:** Taxonomy registration phase (the first v2.0 phase). This cannot be retrofitted cheaply — the privacy model must be embedded in the query layer from day one.

---

### Pitfall 2: Google Maps API Key Exposed in Page Source Enables Billing Abuse and (Since Gemini) AI Account Compromise

**What goes wrong:**
The Google Maps JavaScript API key is always visible in the page source — this is inherent to client-side maps. The traditional advice was that HTTP referrer restrictions on the key were sufficient. This changed with Google's Gemini introduction: researchers found that many Maps API keys also authenticate to Gemini, meaning an exposed key with insufficient restrictions gives an attacker access to AI inference and file uploads billed to your account (documented by Truffle Security in late 2024/2025).

The additional risk for this plugin: the admin saves the Maps API key in `wp_options` via a settings field. If the plugin renders that key into a `<script src>` tag on every event single page, the key is trivially extractable from any visitor's browser.

**Why it happens:**
Developers store the key in settings and inject it server-side into the page — the standard pattern — without enforcing referrer restrictions in the Google Cloud Console and without understanding the expanded attack surface created by Gemini API access on the same key.

**How to avoid:**
- Store the API key in `wp_options` with `autoload = false` and output it only server-side into the Maps API `<script>` tag on single event pages that have a venue address
- Document in the plugin settings UI: "After entering your API key, restrict it in Google Cloud Console to your domain (HTTP referrer restriction) and enable only the Maps JavaScript API and Geocoding API — do NOT enable Gemini or Vertex AI on this key"
- Provide a direct link from the settings page to `console.cloud.google.com/apis/credentials`
- Never log the API key — add a `wp_debug_log` guard or redact it from any error output
- For GDPR compliance: do not load the Maps JavaScript API until the user has consented to third-party cookies; show a static placeholder map image with an "Enable map" button as the default state

**Warning signs:**
- API key output in page source without HTTP referrer restriction configured
- Maps API loaded on every page load (not conditionally on event pages with a venue)
- No GDPR consent gate before Maps script loads
- Same Google Cloud project key used for Maps and any AI/Gemini API

**Phase to address:** Google Maps embed phase. Settings UI, key storage, and the consent gate must all be designed together.

---

### Pitfall 3: Structured Venue Address Fields Require a dbDelta Migration — venue_address Is a Single Blob in v1

**What goes wrong:**
The v1 schema stores venue address as a single `venue_address varchar(500)` column in the custom `bp_events` table. The v2.0 feature spec adds structured fields: city, state/province, postcode, country. Adding these as new columns requires a `dbDelta()` migration against the existing table. If the migration is not written and tested, any existing events with addresses will have their address data stranded in `venue_address` with the new structured columns empty — resulting in broken Maps embeds and malformed display on event pages.

**Why it happens:**
Developers add new postmeta keys for the structured fields (city, state, etc.) assuming postmeta is flexible enough to avoid a schema migration. But the event data lives in a custom table (`bp_events`), not in `wp_postmeta`. Adding columns to a custom table requires an explicit migration.

**How to avoid:**
- Write a `dbDelta()` migration that adds `venue_city`, `venue_state`, `venue_postcode`, `venue_country` columns to `{prefix}_bp_events`
- Write a one-time data migration that attempts to parse the existing `venue_address` blob into its component parts — or at minimum, copies the blob value into a `venue_address_line1` field and leaves structured fields blank, prompting organizers to update
- Version the schema: increment `buddyboss_events_db_version` to trigger migration on plugin update
- Test the migration against a DB with existing events before releasing v2.0

**Warning signs:**
- New venue fields added as postmeta rather than columns in the custom events table
- No schema version bump in the update path
- Migration not tested against a database with pre-existing events

**Phase to address:** Structured location / Google Maps phase. Must run before any venue data is written in the new format.

---

### Pitfall 4: Hybrid Event Type Already Exists in the Schema — "Migration" Risk Is in REST API Response Shape

**What goes wrong:**
The v1 schema already supports `type = 'hybrid'` as a varchar value — so there is no data migration needed for the type column itself. The risk is different: the REST API currently returns `type`, `virtual_url`, and `virtual_type` as flat fields. If v2.0 adds `meeting_id`, `meeting_password`, and `platform_label` as new fields, any REST API consumer (the mobile app, third-party integrations, JavaScript front-ends) will receive additional fields without warning. If they use strict schema validation, this breaks them.

The more dangerous version of this pitfall: if the decision is made to restructure the response (e.g., nest virtual details under a `virtual_details` object for cleanliness), existing consumers that read `event.virtual_url` will break completely.

**Why it happens:**
v1 REST API was designed for internal consumption and the `bp_rest_namespace()/bp_rest_version()` endpoint namespace is inherited from BuddyBoss Platform. The v1 API has no versioning strategy of its own. Adding fields to an existing versioned resource is additive (safe). Restructuring field names or nesting is a breaking change.

**How to avoid:**
- Add new fields (`meeting_id`, `meeting_password`, `platform_label`) as additional flat properties on the existing event response object — do NOT restructure existing field names
- `virtual_url` must remain at the same path in the response even if it is now semantically redundant with `meeting_id` for Zoom events
- If restructuring is genuinely needed, introduce it under a new version: `bp_rest_version()` currently returns `v1` — adding a `/v2/events` namespace is the correct path, with `/v1/events` maintained in read-only compatibility mode
- Document which fields are stable (permanent) vs. provisional (may be restructured in v2) in the API endpoint schema comments

**Warning signs:**
- Existing REST endpoint fields renamed or moved inside a nested object
- No API changelog maintained between v1 and v2
- Front-end templates that use `event.virtual_url` not tested after REST response shape changes

**Phase to address:** Hybrid event type / online meeting details phase.

---

### Pitfall 5: Front-End Submission Must Not Bypass Existing Admin-Side Capability Checks

**What goes wrong:**
v1 has an admin-controlled permission toggle: only users with the `create_events` capability (or above) can create events. Front-end submission adds a form that lets organizer-role users submit events for approval. The common mistake is implementing the front-end form's AJAX handler with only `is_user_logged_in()` as the permission check — omitting the `current_user_can('create_events')` check that admin configured. This effectively overrides the site admin's permission settings.

The reverse pitfall also exists: if the front-end submission handler adds the `pending` post status to the event but the existing admin event list query only shows `published` events, submitted events are silently invisible to the admin moderator — creating a broken workflow where events sit in limbo.

**Why it happens:**
Front-end form handlers are written in isolation from the admin-side permission system. Developers use `is_user_logged_in()` as the entry guard (seems obvious — only logged-in users can submit) without realising the admin has already built a capability layer that must be respected.

**How to avoid:**
- The AJAX/REST handler for front-end event submission must call `current_user_can( bp_events_get_create_capability() )` — the same capability function used by the admin creation path
- Add `pending` to the admin event list query alongside `published` and `draft` so submitted-for-approval events appear in the moderator queue
- The approval workflow's "publish" action must verify the approving user has `publish_events` capability, not just `manage_options`
- Nonce the front-end form with a nonce scoped to the action (`wp_nonce_field('bp_events_submit_event', 'bp_events_nonce')`) and verify server-side with `wp_verify_nonce()` — then check capability — in that order
- Never use `'__return_true'` as the REST route `permission_callback` for any write endpoint

**Warning signs:**
- Front-end submission AJAX handler checking only `is_user_logged_in()`
- No `pending` events visible in the admin event list
- Admin "Create Events" permission toggle doesn't affect front-end form availability
- REST endpoint registered without a `permission_callback`

**Phase to address:** Front-end submission phase.

---

### Pitfall 6: Custom Registration Fields Answers Have Nowhere to Live in the Existing Schema

**What goes wrong:**
The existing `bp_events_attendees` table has no column for custom field answers. When custom registration fields (text, dropdown, checkbox) are added per event, the answers collected at RSVP time need persistent storage. The tempting shortcut is to serialize all field answers into a single `answers` column added to `bp_events_attendees`. This works until: (a) you need to filter attendees by a specific field answer, (b) you need to export CSVs with one column per field, or (c) a field definition changes after answers were already collected.

The second risk: storing field definitions as serialized postmeta (or a JSON blob in `bp_events_meta`) means you cannot `WHERE meta_key = 'field_3_type'` — every query that needs field definitions must pull the entire blob and parse it in PHP, making field-aware queries prohibitively expensive.

**Why it happens:**
The EAV (entity-attribute-value) pattern for custom fields looks simple to add quickly. Developers know WordPress uses `wp_postmeta` this way and copy the pattern without understanding that postmeta-style EAV kills query performance and makes CSV export require a full PHP-side join.

**How to avoid:**
- Create two new custom tables: `bp_events_fields` (field definitions: event_id, field_order, field_type, field_label, field_options, required) and `bp_events_field_answers` (attendee_id, field_id, answer_value)
- Index `field_id` and `attendee_id` on the answers table
- Store field definitions as rows, not as a serialized blob — this allows `JOIN` queries for CSV export and answer filtering
- When a field definition changes (label rename, option added), version it: add a `field_version` column so existing answers remain interpretable against their original field version
- For CSV export, use a single SQL query with `GROUP_CONCAT` or pivot logic rather than loading all attendees into PHP and looping

**Warning signs:**
- Field answers stored as serialized array in a single `answers` column
- Field definitions stored as serialized postmeta
- CSV export implemented by looping attendees in PHP and calling per-attendee queries for field answers (N+1)
- No migration plan for what happens to existing attendee records when a field is deleted

**Phase to address:** Custom registration fields phase. Table schema must be designed before any field storage code is written.

---

## Technical Debt Patterns

Shortcuts that seem reasonable but create long-term problems.

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Store field answers as serialized postmeta | No new table needed | Cannot query by answer value; CSV export requires full PHP loop; breaks on serialization corruption during migration | Never |
| Register taxonomy as `public => true` without privacy filter | Archive pages work out of the box | Private group events leak to public taxonomy archives | Never |
| Load Google Maps API on every page | Simple implementation | GDPR violation; unnecessary billing on pages with no venue | Never |
| Use `is_user_logged_in()` alone as front-end form permission guard | Quick to write | Bypasses admin-configured capability restrictions | Never |
| Add new REST response fields without documentation | Fast to ship | Breaks strict-schema consumers; no way to know what's safe to remove later | Only if a REST changelog entry is added |
| Store sessions/agenda as serialized postmeta | No new table | Cannot query sessions independently; N+1 on event listing pages | Only for MVP with explicit refactor ticket created |
| Build analytics from live `COUNT()` queries against the attendees table | No caching layer needed | Slow on events with thousands of RSVPs; blocks on every page load | Only if < 500 events total |

---

## Integration Gotchas

Common mistakes when connecting to external services or existing plugin systems.

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| Google Maps JavaScript API | Injecting the key into every page regardless of whether the event has a venue | Conditionally enqueue Maps script only on single event pages where `venue_lat` and `venue_lng` are non-null |
| Google Maps + GDPR | Loading Maps script on page load without consent | Show a placeholder with "Click to load map" (two-click solution); defer Maps script until user interaction or cookie consent signal |
| BuddyBoss group privacy + taxonomy archives | Using default `WP_Query` on public taxonomy archive | Hook `pre_get_posts` to exclude events where the viewer lacks group membership |
| BuddyBoss existing event creation permissions | Writing a separate capability check in the front-end handler | Use the same capability function (`bp_events_get_create_capability()`) as the admin path — single source of truth |
| Existing v1 REST API consumers | Restructuring the event response object to nest virtual fields | Additive fields only on `/v1/events`; restructuring requires `/v2/events` |
| Speakers CPT + BuddyBoss activity feed | Registering Speakers CPT with `public => true` causing speaker posts to appear in BuddyBoss activity feed via `bp_activity_add_screen_notifications` hooks | Register Speakers CPT with `show_in_activity_stream => false` or explicitly unregister the post-type activity hook |
| wp_cron for analytics aggregation | Scheduling analytics cron jobs without checking if the job is already scheduled, resulting in duplicate jobs after each plugin update | Use `wp_next_scheduled()` before `wp_schedule_event()`; clear old cron on deactivation hook |

---

## Performance Traps

Patterns that work at small scale but fail as usage grows.

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|----------------|
| Counting attendees with `COUNT(*)` on every event card in a loop | Event listing pages take 2+ seconds; each event card triggers its own DB query | Pre-cache `attendee_count` as a column in `bp_events` table, updated atomically on RSVP; or use `get_transient` with a 5-minute TTL | Noticeably slow at 50+ events on a listing page |
| Loading sessions/agenda rows per event in a listing query | N+1: 1 query for events + 1 per event for sessions | Use a single `WHERE event_id IN (...)` query for all session data after the event list is fetched, then map in PHP | Slow at 20+ events per page |
| Analytics view counting via `UPDATE ... SET views = views + 1` on every page load | View count query blocks on high-traffic single event pages | Batch increment using a transient counter that flushes to the DB every N requests or every minute via cron | Contention visible at 10+ concurrent visitors on the same event page |
| Taxonomy term queries without `hide_empty => false` returning wrong counts | Taxonomy archive pages show term with 0 events after the last event is deleted | Always specify `hide_empty` explicitly; if showing empty categories is desired, set `hide_empty => false` | Immediate — confusing UX from the first deleted event |
| `WP_Query` on taxonomy archive using `tax_query` with `LIKE` operator for tag search | Slow full-table scan on large sites | Use exact term ID matching; index the `bp_events` table on `group_id` | Slow at 10,000+ events |
| Speakers CPT with `posts_per_page => -1` on the event single page to load all speakers | Memory exhaustion on events with 100+ speaker assignments | Paginate speaker lists; load speakers via a targeted `WHERE event_id = %d` query against the sessions/speakers join table, not via `WP_Query` | Memory issue at 50+ speakers per event |

---

## Security Mistakes

Domain-specific security issues beyond general web security.

| Mistake | Risk | Prevention |
|---------|------|------------|
| Front-end event submission handler checking only `is_user_logged_in()` | Any logged-in user can submit events regardless of admin-configured capability restrictions | Check `current_user_can( bp_events_get_create_capability() )` after nonce verification |
| Nonce created with one action string and verified with a different action string | CSRF protection silently fails — `wp_verify_nonce()` returns false but code ignores the return value | Assert that creation and verification use the same action string; treat nonce failure as hard abort, not a warning |
| Google Maps API key logged in WP_DEBUG output or stored in a world-readable option | API key exposed via debug logs or REST API options endpoint | Never log the key; store with `autoload = false`; exclude from any plugin export/debug report |
| Sessions/Speakers REST endpoint returning draft speaker data to unauthenticated requests | Unpublished speaker profiles visible via API | Enforce `post_status = 'publish'` filter on Speakers CPT REST queries for unauthenticated requests; set `permission_callback` to check role for draft access |
| Front-end approval workflow "publish" button backed by a nonce-less AJAX call | Admin-equivalent action (publishing a post) executable via CSRF from any page the approver visits | Nonce + `current_user_can('publish_events')` check on every approval/rejection action |
| Custom registration field `field_type = 'text'` answers not sanitised before storage | XSS stored in field answers, rendered on event management page | Run `sanitize_text_field()` on all text answers; run `sanitize_textarea_field()` on textarea answers; escape on output with `esc_html()` |
| Analytics CSV export endpoint accessible without authentication | Any visitor can download full attendee list with names and emails | Add `permission_callback` requiring `manage_events` capability on the CSV export REST route |

---

## UX Pitfalls

Common user experience mistakes in this domain.

| Pitfall | User Impact | Better Approach |
|---------|-------------|-----------------|
| Google Maps loads immediately on page load, before GDPR consent | Cookie banner appears; map is already running; legal violation | Show a static map placeholder image (use the Maps Static API or a screenshot) with a "View interactive map" button that loads the JS map only on click |
| Taxonomy category archive shows events from private groups with no indication of restricted access | Visitor sees event titles and details they should not see | Apply group privacy filter in `pre_get_posts`; exclude private group events entirely from public archive |
| Front-end submission form available to users who lack `create_events` capability | Form renders, user fills it out, submits, gets a permission error — wasted effort | Gate the form display with `current_user_can( bp_events_get_create_capability() )` — show a "Request organizer access" prompt instead |
| Pending approval events invisible to submitter after they submit | Organizer thinks submission was lost; re-submits | Show submitted events in the organizer's dashboard with status badge: "Pending Review" |
| Sessions listed with no speaker photos or bios on the event page | Attendees have no context for who is presenting | Speaker CPT must require an avatar (enforce in admin); session display template must include speaker card with photo and bio excerpt |
| Custom registration fields with `required = true` not validated client-side | User submits form with missing required fields; server rejects; confusing error state | Add HTML5 `required` attribute and JS validation; match server-side validation exactly so there are no server-only rules that surprise the user |

---

## "Looks Done But Isn't" Checklist

Things that appear complete but are missing critical pieces.

- [ ] **Taxonomy registration:** Verify that private group events do NOT appear on public taxonomy archive pages — browse the category archive while logged out and confirm
- [ ] **Google Maps embed:** Confirm the map does NOT load on events with no venue address and does NOT load before GDPR consent interaction
- [ ] **Google Maps API key:** Verify HTTP referrer restriction is documented in the settings UI help text and that the key is not visible in any debug log
- [ ] **Hybrid event type:** Confirm existing events with `type = 'in-person'` or `type = 'virtual'` render correctly with no visible regression after the new meeting fields are added
- [ ] **Front-end submission:** Confirm the form does not appear to users lacking `create_events` capability — log out, log in as a subscriber, confirm
- [ ] **Front-end submission:** Confirm submitted events appear in the admin moderation queue with status "Pending Review"
- [ ] **Approval workflow:** Confirm nonce verification runs before the approval/rejection action executes — not after
- [ ] **Custom registration fields:** Confirm field answers survive a round-trip: submit answers, view in admin, export CSV — all three surfaces show the same data
- [ ] **Custom registration fields:** Confirm required fields are validated both client-side AND server-side — disable JavaScript and confirm the server rejects an empty required field
- [ ] **Sessions/Speakers:** Confirm draft speaker posts are NOT returned by the Speakers REST endpoint to unauthenticated requests
- [ ] **Analytics CSV export:** Confirm the export route returns HTTP 403 to an unauthenticated request
- [ ] **Analytics queries:** Confirm the attendee count on event listing pages does NOT trigger one DB query per event — use Query Monitor to verify

---

## Recovery Strategies

When pitfalls occur despite prevention, how to recover.

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| Private group events leaking in taxonomy archives | MEDIUM | Add `pre_get_posts` filter immediately; purge any page caches (object cache, CDN) so old archive pages expire; audit server logs for what was exposed |
| Google Maps API key abused (billing or Gemini access) | HIGH | Rotate the key immediately in Google Cloud Console; update `wp_options` with the new key; add HTTP referrer restriction to the new key; review GCP billing alerts for unusual activity |
| Custom field answers stored as serialized blobs and now need querying | HIGH | Write a one-time PHP migration script that reads each serialized blob, expands it into rows in a new `bp_events_field_answers` table; run during a maintenance window; validate row counts before/after |
| Hybrid event REST response restructured and broke existing JS consumers | HIGH | Revert the response shape change; add the new structure under a new property while keeping the old flat properties; declare the restructuring approach in a v2 migration guide |
| Front-end submission handler bypassing capability check — events created by unauthorized users | LOW | Add capability check to handler; run a SQL audit to identify events created via the front-end path that should not have been; mark them as `draft` pending review |
| Analytics queries causing DB timeout on high-traffic events | MEDIUM | Add a `get_transient`/`set_transient` caching layer around the analytics query with a 5-minute TTL; add a dedicated index on `event_id` in the attendees table; defer to cron-based pre-aggregation if queries remain slow |

---

## Pitfall-to-Phase Mapping

How roadmap phases should address these pitfalls.

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| Taxonomy archive leaks private group events | Taxonomy registration phase | Browse category archive while logged out; confirm no private group events appear |
| Google Maps API key exposure + Gemini risk | Google Maps embed phase | Verify HTTP referrer restriction in GCP is documented in settings UI; key not in debug logs |
| Google Maps + GDPR (loads before consent) | Google Maps embed phase | Load single event page, confirm Maps JS does not fire before consent interaction |
| venue_address blob → structured fields dbDelta migration | Structured location / Maps phase | Run migration against a staging DB with existing events; verify address data preserved |
| Hybrid event type REST response shape change | Hybrid event type phase | Check all front-end templates that read `virtual_url`; confirm field is still present at same path |
| Front-end submission bypasses capability guard | Front-end submission phase | Log in as subscriber; confirm form is not displayed and AJAX handler rejects the request |
| Approval workflow CSRF (nonce-less publish action) | Front-end submission / approval phase | Attempt to call the approval AJAX endpoint directly without a valid nonce; confirm 403 response |
| Custom registration field answers have no table | Custom registration fields phase | Schema must be reviewed before any field answer code is written |
| Custom field answers: N+1 on CSV export | Custom registration fields phase | Export CSV with 200 attendees; confirm Query Monitor shows a bounded number of queries, not 200 |
| Sessions/Speakers: draft content visible via REST | Sessions / Speakers CPT phase | Call `/wp-json/bp/v1/events/{id}/speakers` without authentication; confirm no draft speakers in response |
| Analytics: live COUNT() queries on listing page | Analytics / reports phase | Load event listing with 100 events; confirm Query Monitor shows attendee count comes from cache or a single batched query |
| Speakers CPT triggering BuddyBoss activity feed entries | Speakers CPT phase | Create a speaker post; confirm no activity feed item is generated in the BuddyBoss activity stream |

---

## Sources

- BuddyBoss Platform GitHub issue #1692 (private group documents showing to non-members) — MEDIUM confidence, documents the category of privacy bug this pitfall belongs to
- WordPress `pre_get_posts` hook documentation — HIGH confidence (developer.wordpress.org/reference/hooks/pre_get_posts/)
- Truffle Security: "Google API Keys Weren't Secrets. But then Gemini Changed the Rules." (trufflesecurity.com/blog/google-api-keys-werent-secrets-but-then-gemini-changed-the-rules) — MEDIUM confidence, verify API key scope restrictions against current GCP docs
- Patchstack: API KEY for Google Maps WordPress plugin vulnerabilities (patchstack.com/database/wordpress/plugin/api-key-for-google-maps) — MEDIUM confidence
- Complianz: Google Maps and GDPR (complianz.io/google-maps-and-gdpr-what-you-should-know/) — MEDIUM confidence, verify against current EU/GDPR guidance
- WordPress Developer Blog: Understand and use WordPress nonces properly (developer.wordpress.org/news/2023/08/understand-and-use-wordpress-nonces-properly/) — HIGH confidence
- WP-Firewall CVE-2026-1867: Preventing Sensitive Data Exposure in Front Editor — MEDIUM confidence, illustrates the class of front-editor data exposure vulnerability
- WordPress core Trac #30814: Large wp_postmeta table causing slow queries — HIGH confidence, documents postmeta EAV performance degradation
- Advanced Custom Fields: WordPress Post Meta Query Performance Best Practices (advancedcustomfields.com/blog/wordpress-post-meta-query/) — MEDIUM confidence
- Sarathlal N: Scaling WordPress — How Custom Database Tables Solve the Post Meta Bottleneck — MEDIUM confidence
- Current schema inspection: `/Users/tom/Local Sites/Events/buddyboss-events/src/bp-events/bp-events-functions.php` — HIGH confidence (source of record for v1 table definitions)

---
*Pitfalls research for: BuddyBoss Events v2.0 feature additions*
*Researched: 2026-03-17*
