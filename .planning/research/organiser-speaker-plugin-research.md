# Research: Organiser & Speaker Patterns in WordPress Event Plugins

**Researched:** 2026-03-23
**Domain:** WordPress event plugin UX — organiser and speaker data models, admin screens, frontend display
**Relevant Phase:** Phase 6 (Sessions + Speakers) and Phase 7 (Organizer Dashboard)
**Overall Confidence:** HIGH for The Events Calendar core organiser; MEDIUM for TEC speaker (premium add-on); MEDIUM for Eventin (docs site renders CSS-only, cross-verified via multiple secondary sources)

---

## Summary

Two plugins were investigated in depth: **The Events Calendar** (free + Pro + Event Schedule Manager add-on) and **Eventin** (by Themewinter, free + Pro).

Both treat Organiser and Speaker as distinct concerns with separate data models. Organisers are typically event-level metadata — who is running the event — while Speakers are person-profiles linked to sessions inside an event. The key architectural split is:

- **TEC** uses a `tribe_organizer` Custom Post Type for organisers (core, free). Speakers are not a CPT in core TEC — they appear only via the premium **Event Schedule Manager** add-on (a separate paid product), where they become a CPT linked to sessions rather than directly to events. Third-party addons (Events Speakers & Sponsors) add a more complete speaker CPT with dedicated event-page display.

- **Eventin** provides both Speaker and Organiser as dedicated CPTs in a unified Speakers & Organizers module. Speakers have a richer field set than Organisers. Both are assigned directly to events (not sessions). Speaker display on the event page is a named block/widget; organisers display similarly. Multiple pre-built display templates exist for the speaker list.

The BuddyBoss Events plugin (this project) aligns more with the Eventin pattern: both speakers and organisers as proper CPTs, assignable to events and/or sessions, with profile pages. However, Phase 6 (Sessions + Speakers) specifically scopes speakers to sessions, which matches TEC's Event Schedule Manager model more closely.

**Primary recommendation:** Model the Speaker CPT after Eventin's field set (richest, most complete). Model the Organiser CPT after TEC's `tribe_organizer` fields plus photo support (which TEC lacks by default). For frontend display, use Eventin's card-grid pattern as the reference.

---

## Plugin 1: The Events Calendar (TEC)

### 1.1 Organiser — Data Model

**CPT slug:** `tribe_organizer`
**Registration class:** `Tribe__Events__Organizer`
**Meta prefix:** `_Organizer`
**Event link meta key:** `_EventOrganizerID` (stored on the event post)

**Stored post meta fields:**

| Meta Key | Description | Required |
|----------|-------------|----------|
| `_OrganizerOrganizer` | Organiser name / title | Yes (essentially the post title) |
| `_OrganizerPhone` | Telephone number | No |
| `_OrganizerEmail` | Email address | No |
| `_OrganizerWebsite` | Website URL (full URI preferred) | No |
| `_OrganizerOrigin` | How the record was created (e.g. `"events-calendar"`) | Auto |

**Notable omissions in core (free):**
- No photo/featured image support in the default organiser section on the event page — photo is technically stored as `post_thumbnail` but does NOT render by default in the organiser details block; adding it requires a custom action hook (`tribe_get_organizer_details`)
- No bio/description field in the meta sidebar — description lives in the main `post_content` editor area (standard WP post editor)
- No social media links

**Multiple organisers per event:** Supported. The `_EventOrganizerID` can hold an array of IDs. Events can have zero, one, or many organisers.

**Confidence:** HIGH — verified via official API docs and post meta cheatsheet.

### 1.2 Organiser — Admin UX

- Navigation: **Events → Organizers → Add New** (dedicated admin menu item)
- Also creatable inline from the event edit screen — a meta box labelled "Organizers" appears in the sidebar; users can type to search existing organisers or click "Add New Organizer" to open an inline creation form without leaving the event editor
- The inline form exposes: Name, Phone, Website, Email — matching the CPT fields
- Organiser CPT has its own list table at `wp-admin/edit.php?post_type=tribe_organizer`
- Full WP editor for the organiser description (post content)

**Confidence:** HIGH — verified via official knowledgebase docs.

### 1.3 Organiser — Frontend Display

**On the single event page:**
- An "Organizer" section renders below the event details by default (in both Classic and Block editor modes)
- Displays: organiser name (linked to organiser archive page), phone, email (with show/hide privacy controls), website
- Photo does NOT display by default — requires custom code
- Layout: a simple definition-list style block, not a card. No avatar/image.
- Privacy controls (Events > Settings > Display > Organizers): separate toggles to hide phone and email on event singles, organiser singles, or both

**Organiser archive page:**
- URL pattern: `[site]/organizer/[organizer-slug]/`
- Shows organiser name, description, contact details, and a list of all events associated with that organiser
- No grid card layout — it is a standard archive page

**Confidence:** HIGH — verified via official knowledgebase (Venue and Organizer Pages article) and support forum discussions.

### 1.4 Speaker — Data Model (Event Schedule Manager add-on)

**Tier:** Premium add-on (Event Schedule Manager — separate paid product, does NOT require Events Calendar Pro). Works standalone.

**CPT architecture:** Speaker is a CPT linked to **Sessions**, not directly to Events. The data hierarchy is: Event → Sessions → Speakers.

**Speaker fields:**
| Field | Storage |
|-------|---------|
| Full Name | `post_title` |
| First Name | post meta |
| Last Name | post meta |
| Professional Title | post meta |
| Organisation / Company | post meta |
| Biography | `post_content` (WP editor) |
| Profile Photo | `post_thumbnail` (featured image) |
| Social media links (multiple) | post meta |
| Speaker Group | Taxonomy (`tribe_speaker_group`) |

**Session assignment:** When creating sessions in the Schedule Manager, users can either type a speaker name inline (freetext, no CPT record created) or search/select from existing CPT speaker records. CPT speakers show their photo, bio, and linked sessions on their profile page; inline-only speakers show only their name.

**Confidence:** MEDIUM — derived from official TEC knowledgebase (Event Schedule Manager / Speakers article). CPT slug and meta keys not confirmed in official API docs; meta key names are inferred from the class naming convention.

### 1.5 Speaker — Frontend Display (Event Schedule Manager)

- Speakers from the CPT render with photo, bio, and a list of the sessions they appear in
- A `[tec_speakers]` shortcode renders a speaker listing on any page
- Speaker Group feature allows grouping speakers into subgroups within the same event schedule
- Single speaker pages function as profile pages showing sessions they appear in
- A defined page URL can be set in settings to act as the "speakers directory"

On the event page itself: speakers appear within the session/schedule block, not as a standalone section. There is no independent "Speakers" tab on the event page in TEC's default layout.

**Confidence:** MEDIUM — derived from official TEC knowledgebase for Event Schedule Manager.

### 1.6 Key TEC Observations for BuddyBoss Events

- TEC treats organiser as event-level metadata (many-to-one or many-to-many)
- TEC treats speaker as session-level metadata — speakers are discovered through the schedule, not through a top-level event section
- The organiser photo gap is a well-known limitation and commonly worked around with custom code
- TEC's organiser CPT is architecturally clean: CPT + post meta + event link meta key. This is a pattern worth replicating directly.

---

## Plugin 2: Eventin (Themewinter)

### 2.1 Organiser — Data Model

**CPT:** Confirmed exists; exact slug not exposed in official docs (docs site renders CSS-only). The slug is configurable from Settings → General Settings → Slug Settings, suggesting the default is something like `etn-organizer` based on Eventin's `etn-` prefix convention for all its post types.

**Organiser fields (confirmed from multiple secondary sources):**

| Field | Notes |
|-------|-------|
| Full Name | Required |
| Role | e.g. "Event Director" |
| Email Address | Must be unique |
| Company Name | |
| Company URL | |
| Short Bio | |
| Profile Photo (Speaker Logo) | Featured image slot |
| Company Logo | Separate image field |
| Social Links | Multiple social platforms |

**Note:** Eventin's "Organizer" and "Speaker" entities appear to share the same field schema — the admin form for both appears identical or very similar. The distinction is semantic (role labelling), not structural.

**Assignment to event:** From the event edit screen → a dedicated Speaker/Organizer meta section allows selecting existing CPT records. Multiple speakers and organisers can be assigned per event.

**Import/Export:** Speakers and Organisers can be bulk-imported/exported via JSON or CSV — useful for large conferences.

**Version note:** As of v4.0.17 (December 2024), Eventin added the ability to assign the speaker/organiser role to existing WordPress users, linking CPT records to user accounts.

**Confidence:** MEDIUM — field list confirmed by two independent secondary sources cross-referencing the same doc page content. CPT slug is an inference.

### 2.2 Speaker — Data Model

Speakers in Eventin are a first-class CPT. They are richer than organisers in the way docs describe them, though the underlying field schema appears identical.

**Speaker fields (confirmed):**

| Field | Notes |
|-------|-------|
| Speaker Full Name | Required |
| Role | e.g. "Keynote Speaker" |
| Job Title | More specific than Role |
| Speaker Group | Taxonomy for categorising speakers |
| Speaker Category | Separate taxonomy (also `etn_speaker_category`) |
| Email Address | Must be unique |
| Company Name | |
| Company URL | |
| Speaker Bio | Full biography text |
| Speaker Logo / Profile Photo | Featured image |
| Company Logo | Separate image field |
| Social Links | Multiple platforms |

**Speaker Group taxonomy:** `etn_speaker_group` — used to group speakers (e.g. "Keynotes", "Workshop Leads") for filtered display. This is a flat taxonomy for grouping, not a hierarchy.

**Speaker Category taxonomy:** `etn_speaker_category` — separate from group; used for broader categorisation.

**Confidence:** MEDIUM-HIGH — field list confirmed across multiple sources including WordPress.org plugin page, review articles, and admin UX descriptions.

### 2.3 Admin UX — Creating Speakers/Organisers

- Navigation: **Eventin → Speakers** (or Organizers) → **Add New**
- Both speaker and organiser use the same-style admin form
- The admin screen includes all fields listed above in a custom meta box layout (not standard WP editor meta)
- Speaker list view: tabular list of all speakers with name, photo thumbnail, role, group
- Speaker can be assigned to events from the **event edit screen** in a dedicated "Speaker" section — dropdown/search to pick from existing records
- Multiple speakers per event: yes
- Inline creation from event screen: unclear from available docs (TEC does this, Eventin may not)

**Confidence:** MEDIUM — consistent across multiple sources.

### 2.4 Speaker/Organiser — Frontend Display

**On the single event page:**
- Dedicated "Speakers" section/block renders below event details
- Displays speaker cards in a **grid layout**: photo, name, job title, social links
- Card dimensions/columns are configurable
- The section is a named Gutenberg block ("Event Speaker" block) and also available as an Elementor widget
- Organiser block ("Event Organizer") renders similarly, typically alongside the speakers section or in the event sidebar

**Speaker list shortcode:** `[etn_speaker]` (inferred from naming convention; documented parameter names include `etn_speaker_col`, `style`, `cat_id`, `order`, `orderby`, `limit`)

**Template override:** The Speaker template can be overridden at the theme level. The hook `etn_single_speaker_content_body` controls single speaker page assembly, indicating there are clear extension points.

**Speaker profile page:**
- Dedicated single page at `[site]/etn-speaker/[slug]/` (slug configurable)
- Shows: large photo, name, role, bio, company details, social links
- Lists events/sessions the speaker is associated with

**Display styles:** Multiple pre-built "skins" for the speaker list widget — at minimum a card grid and a list/row style. The documentation mentions "5+ speaker widgets" in Pro.

**Eventin Template Builder:** The Template Builder (drag-and-drop page builder integration) allows embedding a speaker section anywhere on a custom event landing page. You select from preset speaker layout templates rather than building from scratch.

**Confidence:** MEDIUM — layout described across multiple review sources and official feature pages. Exact CSS grid classes and column counts unconfirmed.

### 2.5 Key Eventin Observations for BuddyBoss Events

- Eventin treats speakers and organisers symmetrically at the data layer (same fields)
- The semantic distinction (speaker vs. organiser) is through role labelling, not a different data schema
- Speaker Group taxonomy is a useful pattern for filtering large speaker lists by sub-group
- Eventin's speaker card grid on the event page is the clearest reference pattern for BuddyBoss Events Phase 6
- The Gutenberg block model (one block = speaker list section on event) is clean and reusable

---

## Comparative Analysis

### Data Model Comparison

| Attribute | TEC Organiser | TEC Speaker (ESM) | Eventin Organiser | Eventin Speaker |
|-----------|--------------|-------------------|-------------------|-----------------|
| Storage | CPT (`tribe_organizer`) | CPT (ESM add-on) | CPT (etn-organizer) | CPT (etn-speaker) |
| Event link | `_EventOrganizerID` on event post | Via Session CPT | Direct on event post | Direct on event post |
| Name | `_OrganizerOrganizer` | `post_title` | `post_title` | `post_title` |
| Phone | `_OrganizerPhone` | None | Not confirmed | Not confirmed |
| Email | `_OrganizerEmail` | None | Yes (unique) | Yes (unique) |
| Website | `_OrganizerWebsite` | None | Company URL | Company URL |
| Bio | `post_content` | `post_content` | Short Bio field | Speaker Bio field |
| Photo | `post_thumbnail` (not rendered by default) | `post_thumbnail` (rendered) | Profile Photo | Profile Photo |
| Social links | None (core) | Yes | Yes | Yes |
| Role/Title | None | Job Title, Org | Role field | Role + Job Title |
| Taxonomy | None | Speaker Group | None confirmed | Speaker Group + Category |
| Tier | Free (core) | Paid add-on | Free (core) | Free/Pro |

### Admin UX Comparison

| Pattern | TEC | Eventin |
|---------|-----|---------|
| Standalone CPT admin menu | Yes (Events → Organizers) | Yes (Eventin → Speakers/Organizers) |
| Inline creation from event edit screen | Yes (organiser only) | Unclear |
| Assign multiple per event | Yes (organiser) | Yes (both) |
| Bulk import/export | No native | Yes (JSON + CSV) |
| User account linking | No native | Yes (v4.0.17+) |

### Frontend Display Comparison

| Pattern | TEC Organiser | TEC Speaker | Eventin Organiser | Eventin Speaker |
|---------|--------------|-------------|-------------------|-----------------|
| Location on event page | Sidebar/below details | Within session/schedule | Dedicated section block | Dedicated section block |
| Layout | Plain text list | Within schedule row | Card (photo + details) | Card grid |
| Photo shown | No (custom code needed) | Yes | Yes | Yes |
| Social links | No | Yes | Yes | Yes |
| Dedicated profile page | Yes (archive) | Yes | Yes | Yes |
| Profile page URL | `/organizer/[slug]/` | Configurable | `/etn-organizer/[slug]/` | `/etn-speaker/[slug]/` |
| Lists linked events | Yes | Lists sessions | Yes | Yes |
| Shortcode | Via extension | `[tec_speakers]` | Yes | `[etn_speaker]` |
| Gutenberg block | Yes (Event Organizer block) | Within Schedule block | Yes | Yes |

---

## Patterns Worth Replicating

### 1. Organiser as a Linked CPT (TEC pattern)
The `tribe_organizer` CPT linked to events via `_EventOrganizerID` on the event post is clean, reusable, and well-established. For BuddyBoss Events, the equivalent would be `bb_organizer` (or reuse the existing user/member system for organisers instead of a separate CPT — worth deliberating in Phase 7).

### 2. Speaker Photo + Role Card Grid (Eventin pattern)
Eventin's speaker card grid (photo + name + role/title + social links) is the dominant pattern in modern event plugins and should be the reference for Phase 6's frontend display.

### 3. Speaker Group Taxonomy (Eventin pattern)
The `etn_speaker_group` taxonomy for grouping speakers is elegant — it allows filtering "Keynotes vs Panel Speakers vs Workshop Leaders" without creating separate CPTs. Directly applicable to Phase 6.

### 4. Speaker Photo Does NOT Require Custom Code
Both TEC (when using ESM) and Eventin render speaker photos natively. Only TEC's free organiser CPT has the "photo exists but doesn't render" gap. BuddyBoss Events should render organiser photos by default.

### 5. Session-Level Speaker Assignment (TEC Event Schedule Manager pattern)
TEC's model of Speaker → Session → Event (rather than Speaker → Event directly) is the correct model for Phase 6 which explicitly scopes sessions. Eventin skips sessions and assigns speakers directly to events, which is simpler but less structured for multi-track conferences.

### 6. Privacy Controls for Organiser Contact Details
TEC's per-field show/hide toggles for organiser phone and email (at Settings level) are a thoughtful privacy UX pattern. Worth implementing for BuddyBoss Events organisers.

### 7. Inline Organiser Creation from Event Edit Screen (TEC pattern)
Being able to create a new organiser without leaving the event creation wizard is excellent UX. TEC does this well. Directly relevant to Phase 7's organiser dashboard.

---

## Anti-Patterns to Avoid

- **Storing organiser as freetext on the event** (no CPT): Common in simpler plugins. Loses reusability, profile pages, and the ability to see all events by organiser.
- **Photo exists but doesn't render** (TEC default organiser): Don't replicate this gap. Always render the photo.
- **Speaker tied directly to event without session intermediary** (Eventin pattern): Acceptable for simple events but loses structural clarity for multi-track conferences. Phase 6 specifically requires session-level assignment.
- **Conflating Organiser and Speaker into one data model**: They serve different roles and will likely have different display contexts. Keep them as separate CPTs even if their field schemas overlap.

---

## Recommended Field Sets for BuddyBoss Events

### bb_organizer CPT (Phase 7)

| Field | Storage | Source |
|-------|---------|--------|
| Name | `post_title` | TEC + Eventin |
| Phone | `_bb_organizer_phone` | TEC |
| Email | `_bb_organizer_email` | TEC + Eventin |
| Website | `_bb_organizer_website` | TEC + Eventin |
| Bio | `post_content` | TEC + Eventin |
| Profile Photo | `post_thumbnail` | Eventin (TEC has it, doesn't render) |
| Social Links | `_bb_organizer_social` (serialised array) | Eventin |
| Organisation Name | `_bb_organizer_org` | Eventin |
| WP User Link | `_bb_organizer_user_id` | Eventin v4.0.17 pattern |

### bb_speaker CPT (Phase 6)

| Field | Storage | Source |
|-------|---------|--------|
| Full Name | `post_title` | TEC ESM + Eventin |
| Job Title | `_bb_speaker_title` | TEC ESM + Eventin |
| Organisation | `_bb_speaker_org` | TEC ESM + Eventin |
| Bio | `post_content` | TEC ESM + Eventin |
| Profile Photo | `post_thumbnail` | TEC ESM + Eventin |
| Social Links | `_bb_speaker_social` (serialised array) | Eventin |
| Speaker Group | Taxonomy `bb_speaker_group` | Eventin |
| WP User Link | `_bb_speaker_user_id` | Eventin pattern |

**Session link:** Speaker-to-session relationship stored in `bb_event_sessions` junction table as already planned in Phase 6 (`bb_event_session_speakers`).

---

## Open Questions

1. **Organiser = WP User or Separate CPT?**
   - What we know: TEC uses a fully separate CPT with no WP user link by default. Eventin added user linking in late 2024 as a v4 feature.
   - What's unclear: For BuddyBoss Events, the event "organiser" is likely the BuddyBoss member who created the event — should they be a CPT record or just a `_EventOrganizerUserID` meta key pointing to a WP user?
   - Recommendation: For Phase 7, start with a `_EventOrganizerUserID` meta key pointing to a WP user (simpler, no CPT needed for single-organiser events). Introduce a CPT only if multi-organiser co-hosting is required.

2. **Speaker Profile Pages — Separate CPT Archive or BuddyBoss Profile Tab?**
   - What we know: Both TEC and Eventin use CPT archive pages for speaker profiles.
   - What's unclear: In BuddyBoss, speakers are likely also members. Their "speaker profile" might live as a BuddyBoss profile tab rather than a CPT archive page.
   - Recommendation: Investigate in Phase 6 whether `bb_speaker` should link to a WP user and render the profile on the BuddyBoss member profile, or maintain a separate CPT profile page.

3. **Eventin CPT Slugs**
   - The Eventin docs site renders CSS-only for all content pages, preventing direct field list extraction. All Eventin findings in this document are derived from secondary sources (review articles, WordPress.org description, admin UX descriptions, changelog entries).
   - Confidence impact: MEDIUM (not LOW) because multiple independent secondary sources agree on the field list.

---

## Sources

### Primary (HIGH confidence)
- [TEC Organizer Class API Docs](https://docs.theeventscalendar.com/reference/classes/tribe__events__organizer/) — CPT slug, meta prefix, valid field keys
- [TEC WordPress Post Meta Data Cheatsheet](https://theeventscalendar.com/knowledgebase/events-calendar-pro-wordpress-post-meta-data/) — all `_Organizer*` meta keys
- [TEC Venue and Organizer Pages Knowledgebase](https://theeventscalendar.com/knowledgebase/venue-and-organizer-pages/) — frontend display, URL structure, admin UX
- [TEC Event Schedule Manager — Speakers](https://theeventscalendar.com/knowledgebase/speakers/) — speaker CPT, fields, session assignment model
- [TEC Linked Post Types](https://theeventscalendar.com/knowledgebase/linked-post-types/) — architecture for linked CPTs, inline creation pattern
- [TEC Template Files](https://theeventscalendar.com/knowledgebase/calendar-template-files-v2/) — organiser template file structure

### Secondary (MEDIUM confidence)
- [Eventin WordPress.org Plugin Page](https://wordpress.org/plugins/wp-event-solution/) — Gutenberg blocks list, feature overview
- [Eventin Features Page](https://themewinter.com/eventin/features/) — speaker/organiser management overview
- [Eventin How to Create Event Landing Page](https://themewinter.com/how-to-create-event-landing-page/) — Template Builder speaker section description
- [Events Speakers & Sponsors Addon (TEC)](https://eventscalendaraddons.com/plugin/events-speakers-and-sponsors/) — speaker field set for TEC ecosystem
- [Modern Events Calendar Advanced Speaker Addon](https://webnus.net/dox/modern-events-calendar/advanced-speaker-addon/) — speaker addon patterns (different plugin, pattern reference)
- [TEC Support Forum — Organizer Image](https://theeventscalendar.com/support/forums/topic/add-organizer-image-to-organizer-details-in-single-event/) — confirms photo gap in default organiser display

### Tertiary (LOW confidence — web summaries, not direct page content)
- Multiple WebSearch result summaries describing Eventin speaker field list — consistent across sources but doc pages themselves were CSS-only
- [Eventin Speaker & Organizer Intro Doc](https://support.themewinter.com/docs/plugins/plugin-docs/speakers-and-organizers/eventin-speaker-organizer/) — page confirmed to exist, content rendered as CSS only

---

## Metadata

**Confidence breakdown:**
- TEC Organiser data model: HIGH — direct API docs
- TEC Speaker data model: MEDIUM — official knowledgebase but feature-level description only
- Eventin field lists: MEDIUM — consistent across multiple secondary sources, primary docs inaccessible
- Frontend display patterns: MEDIUM — described in reviews/feature pages but no direct template inspection
- Recommended field sets: MEDIUM — derived from synthesis, not from a single authoritative spec

**Research date:** 2026-03-23
**Valid until:** 2026-09-23 (stable plugin conventions; check if Eventin releases major version)
