# Feature Research

**Domain:** WordPress Events Plugin (BuddyBoss Add-on) — v2.0 Feature Parity
**Researched:** 2026-03-17
**Confidence:** MEDIUM — Eventin feature pages verified via WebFetch/WebSearch; specific field-level details for some features confirmed from secondary sources (Events Manager, MEC docs) where Eventin pages returned only CSS.

---

## Context: v1 Already Shipped

The following features are built and should NOT be re-researched or re-scoped:

- Core event CRUD (in-person, virtual, recurring)
- Free RSVP + capacity + waitlist
- Group RSVP restrictions + iCal export
- BuddyBoss group events tab, activity feed, member profile tabs, group invites

All v2.0 feature analysis below concerns only the new capabilities listed in PROJECT.md.

---

## Benchmark: Eventin Feature Tier Map

Based on verified research of themewinter.com/eventin/pricing/ (2026-03-17):

| Feature | Eventin Free | Eventin Pro |
|---------|-------------|-------------|
| Event categories + tags | Yes | — |
| Speaker pages + organizer profiles | Yes | — |
| Zoom + Google Meet integration | Yes | — |
| Session/schedule management | Yes | — |
| Basic WooCommerce ticketing | Yes | — |
| Email automation | Yes | — |
| Google Maps location display | No | Pro |
| Custom attendee fields | No | Pro |
| Attendee reporting + CSV export | No | Pro |
| Front-end event submission | No | Pro |
| QR code check-in | No | Pro |
| Countdown timer widget | No | Pro |
| External event redirect | No | Pro |
| RSVP module + invitations | No | Pro |
| PayPal / Stripe payment gateways | No | Pro |
| BuddyBoss integration | No | Pro |

**Confidence:** MEDIUM-HIGH — sourced from official pricing page.

---

## Feature Landscape

### Table Stakes for v2.0 (Users Expect These)

Features that Eventin-level plugins provide as standard. If v2.0 ships without these, the plugin feels incomplete compared to the benchmark.

| Feature | Why Expected | Complexity | v1 Dependency | Notes |
|---------|--------------|------------|----------------|-------|
| Event categories (hierarchical) | Every events plugin has taxonomy; used for filtering, listing, discovery | LOW | None | WordPress built-in taxonomy. Hierarchical (parent/child). Eventin uses free tier. Standard `register_taxonomy` with `hierarchical: true`. |
| Event tags (flat) | Complementary to categories; finer-grained labeling | LOW | None | Standard `register_taxonomy` with `hierarchical: false`. Both taxonomies should appear in REST API and admin filters. |
| Structured venue address fields | In-person events need city/state/zip/country individually for Maps embed | LOW-MEDIUM | Existing location fields | Current v1 likely stores raw address. Structured fields = separate meta: `_venue_address`, `_venue_city`, `_venue_state`, `_venue_zip`, `_venue_country`. |
| Google Maps embed on event page | Users expect a live map for in-person events | MEDIUM | Structured venue fields | Requires Google Maps Embed API (no key for basic embed; Static Maps API needs key). Pattern: geocode address on save, store lat/lng, render iframe or JS map. Eventin makes this Pro; we should include it. |
| Hybrid event type | Physical + virtual simultaneously — post-pandemic expectation | LOW | Existing event type field | Add `hybrid` to existing event type enum. UX: show both venue fields AND online meeting fields when hybrid is selected. |
| Online meeting details | Zoom/Meet links need ID, password, and platform label per event | LOW | Virtual event type | Fields: `_meeting_platform` (Zoom/Google Meet/Teams/Other), `_meeting_url`, `_meeting_id`, `_meeting_password`. Shown on event page after RSVP (not public). Eventin free includes Zoom native integration; we use field-based approach (simpler, no API dependency). |
| Sessions/Agenda (multi-session per event) | Any conference/multi-track event expects a schedule tab | MEDIUM | Event CRUD | Sessions are a separate CPT or repeatable meta group per event. Each session: title, date, start time, end time, speaker(s), location/room, description. Displayed as agenda on event single page, grouped by day. Eventin free includes this. |
| Speakers CPT | Events with sessions need speaker bios, photos, links | MEDIUM | Sessions | Separate CPT `bp_event_speaker`. Fields: name, role/title, company, bio, photo, social links (Twitter/X, LinkedIn, website). Assigned to sessions (not directly to events — event inherits speakers via sessions). Eventin free includes speaker pages. |
| FAQ section on event page | Organizers want to answer common questions without support overhead | LOW | None | Repeatable field group per event: question + answer pairs. Rendered as accordion/expand on event page. Eventin free includes this. |
| Countdown timer | Creates urgency; standard on event pages near the event date | LOW | None | Client-side JS countdown to event start. Display conditionally: only when event is in the future, hide after it starts. Eventin makes this Pro; we include it as standard. |

**Confidence:** HIGH for taxonomy, venue fields, hybrid type, online meeting fields. MEDIUM for sessions/speakers (pattern confirmed, Eventin field-level details unavailable from WebFetch — extrapolated from search results and WP event ecosystem patterns).

---

### Differentiators for v2.0

Features that set BuddyBoss Events apart even among Eventin-level plugins.

| Feature | Value Proposition | Complexity | v1 Dependency | Notes |
|---------|-------------------|------------|----------------|-------|
| Front-end event submission + organizer dashboard | Lets community members create events without WP admin access. Core to community-driven events | HIGH | Event CRUD, BuddyBoss roles | Full workflow: submission form → pending → admin approve/reject → email notifications. Organizer sees their events in a dashboard (shortcode or BuddyBoss profile tab). Eventin Pro only. We include it; it's a community feature, not just a plugin feature. |
| Approval workflow (admin moderation) | Admin controls what goes live — critical for community trust | MEDIUM | Front-end submission | New events from non-admin users → `post_status: pending`. Admin approves → published + email to organizer. Admin rejects → email to organizer with optional reason. Toggle in settings: auto-publish OR require approval. |
| Custom registration fields per event | Collect dietary needs, t-shirt size, session preferences, etc. — per-event customization | HIGH | RSVP system | Field types: text, textarea, dropdown/select, checkbox, radio. Configured per event in event edit screen. Stored as attendee meta on RSVP record. Shown on RSVP form. Eventin Pro only. |
| Event reports/analytics + CSV export | Organizers need attendance data, view counts, conversion metrics | MEDIUM | RSVP/attendee records | Per-event dashboard showing: total RSVPs, capacity utilization, waitlist count, custom field responses. CSV export of attendee list with field responses. Admin-level report across all events. Eventin Pro only. We include it. |
| External event link (redirect) | Some organizers run events on Eventbrite or Luma; need a "register here" external URL | LOW | None | Field: `_external_event_url`. When set, RSVP button redirects to external URL instead of native RSVP. Clear UX indicator ("You'll be taken to an external site"). Eventin Pro only. |
| BuddyBoss-native organizer dashboard | Rather than a shortcode page, surface the organizer dashboard inside BuddyBoss member profile nav | MEDIUM | Front-end submission, v1 profile tabs | Adds "Manage Events" sub-tab to the member's BuddyBoss profile (visible only to that member and admins). Lists events the member has submitted with status badges (Published, Pending, Rejected). No competitor does this — it's BuddyBoss-native. |

**Confidence:** MEDIUM — workflow patterns confirmed from MEC, Events Manager, EventPrime docs. BuddyBoss profile tab integration is our own design, no external reference.

---

### Anti-Features (Do Not Build in v2.0)

| Feature | Why Requested | Why Problematic | Alternative |
|---------|---------------|-----------------|-------------|
| Native Zoom / Google Meet API integration | "Auto-create meetings on event save" | Requires OAuth app registration per site, token refresh, API rate limits, OAuth callback URL config in WP — massive complexity for marginal gain | Store meeting URL/ID/password as plain fields. Organizer creates the meeting in Zoom/Meet and pastes the link. |
| Conditional logic in registration fields | "Show field X only if checkbox Y is checked" | Significant JS complexity; dependency management; testing surface explodes | Flat field list is sufficient for v2.0. Conditional logic is a v3 feature if demanded. |
| Multi-event speaker assignment (speaker pool management) | "Assign a speaker to many events at once" | Speaker–event N:M relationship is complex; a speaker CPT entry can already be reused across sessions | Speakers are a CPT; the same speaker record is assigned to multiple sessions naturally. No special UI needed. |
| AI-generated event content | "Auto-fill description, FAQ, agenda with AI" | Eventin Pro offers this. It's a nice-to-have, not a feature parity requirement. Complex to implement reliably. | Defer to v3; focus on the submission UX first. |
| Ticket PDF / QR code check-in | "Physical check-in at the door" | Already removed from scope. QR involves PDF generation library, QR encoding, scan interface. | Out of scope per PROJECT.md. |
| Event landing page builder | "Custom event page templates with drag-and-drop" | Eventin Pro includes this; very high complexity; competes with Elementor/Gutenberg | Use standard WP template system. Design is the theme's job. |
| Bulk event import via CSV | "Upload 50 events at once" | Import validation, duplicate detection, error reporting complexity | REST API for advanced bulk use cases. Manual creation covers 99% of community events. |

---

## Feature Dependencies

```
[EXISTING v1] Event CRUD
    └──required by──> Categories + Tags (taxonomy attaches to events CPT)
    └──required by──> Structured Venue Fields (extends existing location meta)
    └──required by──> Hybrid Event Type (extends existing event type enum)
    └──required by──> Sessions CPT (sessions belong to an event)
    └──required by──> Front-end Event Submission (submits a new event)
    └──required by──> FAQ Fields (meta on event post)
    └──required by──> Countdown Timer (reads event start date)
    └──required by──> External Event Link (meta on event post)
    └──required by──> Analytics/Reports (aggregates RSVP records per event)

[EXISTING v1] RSVP System
    └──required by──> Custom Registration Fields (fields appear on RSVP form)
    └──required by──> Analytics/Reports (reads RSVP records for counts/CSV)

[EXISTING v1] Virtual Event Type
    └──enhanced by──> Hybrid Event Type (adds physical venue alongside virtual meeting)
    └──enhanced by──> Online Meeting Details (structured fields for meeting info)

Structured Venue Fields
    └──required by──> Google Maps Embed (needs lat/lng from geocoded address)

Sessions CPT
    └──required by──> Speakers CPT (speakers are assigned to sessions)

Speakers CPT
    └──enhances──> Sessions CPT (sessions display speaker bios inline)

Front-end Event Submission
    └──required by──> Approval Workflow (submission triggers pending status)
    └──required by──> Organizer Dashboard (organizer views their submitted events)

Approval Workflow
    └──requires──> Email notification system (notify on approve/reject)

[EXISTING v1] BuddyBoss Profile Tabs
    └──enhanced by──> BuddyBoss Organizer Dashboard Tab (adds "Manage Events" sub-tab)
```

### Dependency Notes

- **Categories + Tags require Event CRUD:** Taxonomies must be registered against the events CPT before the CPT exists. Trivial dependency — handled in plugin init.
- **Google Maps requires Structured Venue Fields:** Cannot geocode without city/state/country components. Build venue fields first, then Maps embed.
- **Speakers require Sessions:** A speaker CPT without sessions has no assignment surface. Build Sessions first in the same phase or same ticket.
- **Custom Registration Fields require RSVP System:** Fields attach to the RSVP form and store as RSVP attendee meta. v1 RSVP must be stable before fields layer on top.
- **Analytics require RSVP records:** No meaningful reports without attendee data. Analytics is naturally last in the feature sequence.
- **Approval Workflow requires Front-end Submission:** The workflow is meaningless without the submission trigger. Build together.
- **BuddyBoss Organizer Dashboard requires v1 Profile Tabs:** Adds a new sub-tab to the existing member profile tab infrastructure. Safe extension.

---

## MVP Definition for v2.0

### Phase 4 — Taxonomy, Enriched Event Types, Venue + Map

Must-haves for content discoverability and event richness:

- [ ] Event categories (hierarchical) — table stakes; filtering depends on this
- [ ] Event tags (flat) — table stakes companion to categories
- [ ] Structured venue address fields (city/state/zip/country) — required for Maps
- [ ] Google Maps embed on event page — expected for in-person events
- [ ] Hybrid event type — extends existing type enum; low complexity
- [ ] Online meeting details fields (platform/URL/ID/password) — low complexity
- [ ] FAQ section per event — low complexity, high organizer value
- [ ] Countdown timer — low complexity, high engagement value
- [ ] External event link — low complexity, edge case but clean to include

### Phase 5 — Sessions + Speakers

Conference and multi-session events need a dedicated phase:

- [ ] Sessions CPT (agenda per event) — medium complexity
- [ ] Speakers CPT — medium complexity
- [ ] Session–speaker assignment — dependency resolution
- [ ] Session display on event single page (tabbed agenda) — template work

### Phase 6 — Front-end Submission + Organizer Dashboard + Approval

Community-generated content requires the most careful UX design:

- [ ] Front-end event submission form — high complexity
- [ ] Pending/Approved/Rejected status workflow — medium complexity
- [ ] Admin approve/reject UI + email notifications — medium complexity
- [ ] Organizer dashboard (BuddyBoss profile tab) — medium complexity
- [ ] Settings toggle: auto-publish vs require approval — low complexity

### Phase 7 — Custom Registration Fields

Extends v1 RSVP; should be stable before building on top:

- [ ] Field builder per event (text, textarea, dropdown, checkbox, radio) — high complexity
- [ ] Custom fields in RSVP form — depends on field builder
- [ ] Field responses stored as RSVP attendee meta — depends on field builder

### Phase 8 — Analytics + Reports

Naturally last; depends on all attendee data being clean:

- [ ] Per-event analytics dashboard (RSVPs, capacity, waitlist) — medium complexity
- [ ] Attendee CSV export with custom field responses — medium complexity
- [ ] Admin-level cross-event report — medium complexity

---

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| Event categories + tags | HIGH | LOW | P1 |
| Structured venue + Google Maps | HIGH | MEDIUM | P1 |
| Hybrid event type | MEDIUM | LOW | P1 |
| Online meeting details fields | MEDIUM | LOW | P1 |
| Sessions/Agenda CPT | HIGH | MEDIUM | P1 |
| Speakers CPT | HIGH | MEDIUM | P1 |
| Front-end submission | HIGH | HIGH | P1 |
| Approval workflow | HIGH | MEDIUM | P1 |
| Organizer dashboard (BuddyBoss tab) | HIGH | MEDIUM | P1 |
| Custom registration fields | HIGH | HIGH | P1 |
| Analytics + CSV export | MEDIUM | MEDIUM | P2 |
| FAQ section per event | MEDIUM | LOW | P1 |
| Countdown timer | MEDIUM | LOW | P2 |
| External event link | LOW | LOW | P2 |

**Priority key:**
- P1: Must have for v2.0 milestone completion (Eventin parity goal)
- P2: Include if time allows; defer to v2.1 if needed
- P3: Nice to have — not in this milestone

---

## Competitor Feature Analysis (v2.0 Scope Only)

| Feature | Eventin | MEC Pro | WP Event Manager | Our Approach |
|---------|---------|---------|-----------------|--------------|
| Categories + tags | Free | Free | Free | Free — WordPress taxonomy, same as all competitors |
| Structured venue + Maps | **Pro only** | Free | Add-on | Include free — Google Maps Embed API (no key needed for basic iframe) |
| Hybrid event type | Free | Free | Free | Free — enum extension, trivial |
| Online meeting fields | Free (Zoom native API) | Free | Free (URL field) | Free — plain fields, no API dependency |
| Sessions/Agenda | Free | Free | Add-on ($49) | Free — CPT-based |
| Speakers CPT | Free | Free | Add-on ($29) | Free — CPT-based |
| Front-end submission | **Pro only** | Pro | Free (core feature) | Include — community events require it |
| Approval workflow | **Pro only** | Pro | Free | Include — community moderation |
| Custom registration fields | **Pro only** | **Pro only** | Add-on | Include — differentiator vs free Eventin |
| Analytics + CSV export | **Pro only** | Pro | Add-on | Include — organizer need |
| FAQ per event | Free | Free | No | Include — low cost, high value |
| Countdown timer | **Pro only** | Pro | No | Include — low cost |
| External event link | **Pro only** | Pro | No | Include — low cost |

**Key insight:** Where Eventin gates features behind Pro, we include them. BuddyBoss Events is distributed as a premium BuddyBoss add-on — the "pro" tier IS the product. We are not building a freemium split.

---

## UX Behavior Reference: Key Complex Features

### Front-End Event Submission + Approval Workflow

**Standard pattern** (confirmed across Events Manager, MEC, EventPrime):

1. Admin places a shortcode (or BuddyBoss registers a profile tab page) that renders the submission form
2. Submission form mirrors the WP admin event creation fields: title, description, date/time, event type, venue fields, categories, tags, image
3. On submit: event created with `post_status: pending` (if approval required) or `publish` (if auto-publish setting enabled)
4. On pending: admin receives email notification with link to review in WP admin
5. Admin approves → post_status set to `publish` → organizer receives "your event was approved" email
6. Admin rejects → custom reject action → post_status stays `draft` or deleted → organizer receives "your event was not approved" email with optional reason field
7. Organizer dashboard: list of their events with status badge (Published / Pending / Rejected), edit and delete actions
8. BuddyBoss-specific addition: dashboard surfaced as "Manage Events" sub-tab on member profile (not a standalone page)

**Roles:** Only registered WP users can submit. Custom capability `submit_bp_events` controls who can access the form. Admin configures which WP user roles get this capability in Events settings panel.

### Custom Registration Fields

**Standard pattern** (confirmed across Eventin Pro, Events Manager, MEC Pro):

1. In the event edit screen, an "Registration Fields" meta box lists the event's custom fields
2. Admin/organizer clicks "Add Field" — chooses field type: Text, Textarea, Dropdown, Checkbox (multi), Radio (single choice)
3. Each field has: Label, Placeholder/Options (for dropdown/radio/checkbox), Required toggle
4. Fields are stored as serialized meta on the event post: `_registration_fields`
5. At RSVP time: form renders standard fields (name, email) + custom fields in order
6. On RSVP submit: field responses stored as meta on the RSVP/attendee record: `_attendee_field_responses`
7. In analytics/CSV export: each custom field becomes a column

**Field types for v2.0:** Text, Textarea, Dropdown (select), Checkbox group, Radio group. File upload deferred (requires storage handling).

### Sessions/Agenda

**Standard pattern** (confirmed from Eventin and WP Event Manager Speaker & Schedule add-on):

1. Sessions are a CPT (`bp_event_session`) with a relationship to the parent event (post meta: `_event_id`)
2. Each session: title, date (for multi-day events), start time, end time, description, location/room name, speaker(s) (multi-select from Speakers CPT)
3. Sessions displayed on event page as a "Schedule" or "Agenda" tab, grouped by day
4. Each session card shows: time range, title, speaker name(s) + photo thumbnails, room
5. Speaker click → opens speaker bio modal or links to speaker profile page

**Multi-day handling:** Sessions store their own date field — allows multi-day conference scheduling without separate events.

### Speakers CPT

**Standard pattern** (confirmed from Eventin search results, TEC docs):

Fields on Speaker CPT (`bp_event_speaker`):
- Name (post title)
- Role/Job Title (meta)
- Company name + URL (meta)
- Bio (post content or meta)
- Photo (featured image)
- Social links: Twitter/X, LinkedIn, website URL (meta)

Assignment: speaker is selected from the Speakers CPT when editing a session. Many-to-many: one speaker can speak at multiple sessions, one session can have multiple speakers.

Speaker profile page: auto-generated archive page at `/events/speakers/[slug]/` — shows bio, photo, social links, list of sessions they're speaking at.

---

## Sources

- Eventin pricing page (verified 2026-03-17): https://themewinter.com/eventin/pricing/
- Eventin features page (verified 2026-03-17): https://themewinter.com/eventin/features/
- Eventin front-end submission docs (verified 2026-03-17): https://support.themewinter.com/docs/plugins/plugin-docs/event/front-end-event-submission/
- Eventin speaker creation docs: https://support.themewinter.com/docs/plugins/plugin-docs/speakers-and-organizers/how-to-create-eventin-speaker/
- Eventin schedule docs: https://support.themewinter.com/docs/plugins/plugin-docs/event/event-schedule/
- MEC Frontend Submission docs (workflow reference): https://webnus.net/dox/modern-events-calendar/frontend-event-submission/
- Events Manager approval docs (workflow reference): https://wp-events-plugin.com/documentation/event-approval/
- Events Manager automated emails docs: https://wp-events-plugin.com/documentation/automated-emails/
- The Events Calendar virtual/hybrid events: https://theeventscalendar.com/knowledgebase/creating-a-virtual-event/
- Eventin hybrid events guide: https://themewinter.com/hybrid-events/

**Confidence assessment:**
- Taxonomy (categories/tags): HIGH — standard WordPress pattern, universally confirmed
- Venue fields + Google Maps: HIGH — pattern confirmed across Events Manager, TEC, Eventin
- Hybrid event type: HIGH — simple enum extension, confirmed by multiple plugins
- Online meeting fields: HIGH — field-based approach confirmed across WP ecosystem
- Sessions/Speakers CPT: MEDIUM — Eventin field-level details unavailable from WebFetch; field list extrapolated from search result text + WP Event Manager Speaker & Schedule documentation
- Front-end submission workflow: HIGH — confirmed from MEC, Events Manager, EventPrime docs; consistent pattern across all plugins
- Custom registration fields: MEDIUM — confirmed field types exist in Eventin Pro and Events Manager; specific Eventin implementation unavailable from WebFetch
- Analytics/CSV: MEDIUM — confirmed Eventin Pro includes attendee CSV; exact dashboard fields unknown
- FAQ, countdown, external link: HIGH — confirmed from Eventin features page (Eventin Pro for countdown/external link; free for FAQ)

---
*Feature research for: BuddyBoss Events Plugin v2.0*
*Researched: 2026-03-17*
