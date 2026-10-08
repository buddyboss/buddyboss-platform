# PROD-9995 — Remove Video & Document from free Platform (Claude progress log)

> Work originated under ticket PROD-9826; the code now ships on branch **PROD-9995**
> (branched from `release`). PROD-9826 references below are historical context.

**Goal:** Make `src/bp-video/` and `src/bp-document/` physically deletable from the free
buddyboss-platform build with zero PHP fatals/warnings and zero JS/CSS breakage, and stop
the Media feature toggle from auto-activating the video & document components.
Video/Document become paid-only (delivered by paid plugin/build).

**Branch:** `PROD-9995` (from `release`) · Working dir: `wp-content/plugins/buddyboss-platform`
**Last checkpoint:** 2026-06-15

> **REST API split (2026-06-15):** The 6 `class-bp-rest-*-endpoint.php` guard changes were
> MOVED out of this plugin into **`buddyboss-platform-api`** (branch `PROD-9995`, under
> `includes/bp-{component}/classes/`), since the REST layer is owned by that plugin. Those 6
> files are reverted to `release` here. Platform now carries 51 files + CLAUDE-PROGRESS.md.
> Files moved: reply, topics, group-settings, media, members-details, moderation-report endpoints.
**Status:** ✅ COMPLETE & VERIFIED (incl. live browser walkthrough). Plugin (57 files) +
theme (2 files) guarded and decoupled. Smoke test PASSED in BOTH folders-removed and
baseline states — zero PHP fatals. Chrome-extension walkthrough of admin + frontend passed
with zero console errors after fixing one JS error (see "Browser verification" below). Only
the user's git/packaging action (actually deleting the two folders) and optional cosmetic
copy/menu edits remain.

---

## ⚠️ CURRENT ENVIRONMENT STATE (read first)

- **Dev environment RESTORED TO BASELINE.** `src/bp-video/` and `src/bp-document/` are back
  in place; `bp-active-components` has media+video+document all active; rewrite rules
  flushed; opcache reset. The site is fully functional as before this work started.
- The two folders were only ever *moved* to `/tmp/prod9826-backup/` for testing and have
  been moved back. The temp `claude_smoketest` user has been deleted.
- The code changes (56 plugin files + 2 theme files) are the deliverable and remain in the
  working tree (uncommitted — user commits). To produce the free build, the user deletes
  `src/bp-video/` + `src/bp-document/`; removal is verified safe (no fatals).
- To re-run the removal smoke test: move both folders aside (or `git rm`), reset opcache via
  web request, load pages. The scrub auto-removes them from `bp-active-components` on first load.

---

## Architecture findings (verified)

1. **Component loading** — `src/bp-core/classes/class-bp-core.php::load_components()`
   includes `bp-{component}/bp-{component}-loader.php` for each optional component ONLY
   when `bp_is_active( $component )` AND `file_exists( loader )`.
   → Component PHP never loads if folder is deleted. **Deleting folder ≈ deactivating
   component, PROVIDED the stale `bp-active-components` option entry is scrubbed.**

2. **`bp_is_active()`** (`src/bp-core/bp-core-template.php:2134`) reads
   `buddypress()->active_components`, populated in `load_components()` from the
   `bp-active-components` option. Scrubbing there fixes every downstream guard.

3. **Raw option readers that bypass `bp_is_active()`** (why we persist the scrub to DB):
   - `src/bp-performance/bp-performance-includes.php` → `Performance::mu_is_component_active()`
   - `BB_Feature_Registry::bb_is_feature_active()` migration fallback.

4. **Activation coupling (the media "super-feature")** —
   `src/bp-core/admin/bb-admin-settings-media.php`: `'components' => array( 'media', 'video', 'document' )`.
   `BB_Feature_Registry::bb_activate_feature()` writes ALL listed components into
   `bp-active-components`. No other coupling exists (legacy admin has none).

5. **Class autoloader** (`src/class-buddypress.php::autoload()`) bails silently when the
   class file is missing → `new BP_Video(...)` with folder deleted = "class not found" FATAL.

6. **Two failure shapes when a component is gone:**
   - **Symbol fatal** — calling a `bp_video_*`/`bp_document_*`/`bb_video_*`/`bb_document_*`
     function or `new BP_Video/BP_Document*` that no longer exists.
   - **Wrong-proxy guard** — historically media+video+document were always co-active, so
     hundreds of call sites used `bp_is_active( 'media' )` as a proxy. Those must become the
     correct `bp_is_active( 'video' )` / `bp_is_active( 'document' )`.

---

## Implementation — DONE (all `php -l` clean)

### Core decoupling & component visibility
| # | Change | File |
|---|--------|------|
| 1 | Scrub unavailable optional components from active list + persist option once | `src/bp-core/classes/class-bp-core.php` `load_components()` |
| 2 | Media feature activates ONLY `media` (dropped video/document from `components`) | `src/bp-core/admin/bb-admin-settings-media.php` |
| 3 | Gate Videos/Documents side panels + settings sub-files on `bp_is_active('video'/'document')` | `src/bp-core/admin/bb-admin-settings-media.php` |
| 7 | New helper `bb_is_component_directory_available()` + hide unavailable optional components from `bp_core_get_components()` | `src/bp-core/bp-core-functions.php` |

### Guards added across the codebase (56 plugin files changed total)
Done in 4 parallel audited passes (messages, forums, core, activity/media/templates), each
classifying every call site of the 578 removed function names + all `new BP_Video/BP_Document`
class uses, then guarding only the genuinely reachable-while-inactive ones. Highlights:

- **bp-media** — `bb_media_user_can_access()` early all-false bail for video/document/folder
  types; media REST endpoint delete + `prepare_item_for_response`; `bp-media-settings.php`
  size-format fallback.
- **bp-activity** — `bp_activity_has_media_activity_filter()` `BP_Video::` call moved under
  `elseif ( bp_is_active('video') )`; `bp_nouveau_activity_privacy()` 4 meta-driven blocks
  guarded; `class-bp-email-tokens.php` video/document email-token blocks guarded.
- **bp-forums** — `bbpress-functions.php` topic/reply/forum edit-screen media-localize loops
  (6 guards); `common/functions.php` delete handlers; REST topic/reply embed-filter
  `function_exists` wraps; `form-attachments.php` (both nouveau + readylaunch) extension
  proxies corrected to `bp_is_active('document'/'video')`.
- **bp-groups / bp-members / bp-search / bp-moderation** — group sub-nav (videos/documents),
  group settings REST tabs, member profile REST slug, search helper registration, moderation
  report `prepare_links` `new BP_Document/BP_Video`.
- **bp-messages** — thread/message rendering of legacy video/document attachments (functions
  + readylaunch files panel), AJAX excerpt builders.
- **bp-templates (nouveau + readylaunch shared templates)** — activity blocked-comment
  add_action of removed callbacks; media entry/loop/index/single-album; member & group
  single media/document/photos/albums; messages editor toolbar extension lists; search
  album loops; screen-dispatch guards in `includes/{groups,members}/template-tags.php`
  (URL-action predicates that would render video/document screens).
- **bp-core/deprecated/buddyboss/1.7.0.php** — `bp_document_get_preview_url` shim wrapped in
  `function_exists`.

### Verified-SAFE without edits (do NOT re-guard)
- Per-component nouveau includes (`buddypress-functions.php` gated `bp_is_active && file_exists`).
- Moderation content-type + suspend class instantiation (`bp-moderation-filters.php`,
  `class-bp-core-suspend.php`) — all gated by `bp_is_active('video'/'document')`.
- bp-performance integrations — gated + `file_exists`.
- Callbacks on `bp_video_add` / `bp_document_add(_handler)` — those hooks only fire from
  INSIDE the removed components, so the callbacks are dead code when the component is gone.
- `*_set_*_scope_args` filter callbacks in members/friends/groups filters — filters only
  applied inside `BP_Video::get()` / `BP_Document(_Folder)::get()`.
- Media-settings FFmpeg / direct-access AJAX — `function_exists`-guarded.
- `bb_core_symlink_generator()` / `bb_moderation_get_media_record_by_id()` — data-driven /
  raw SQL, no symbol calls.
- `includes/video/` and `includes/document/` template dirs + `buddypress|readylaunch/{video,document}/`
  template trees — only loaded/rendered via component-gated screens.

## Decisions made
- Persist the scrub to `bp-active-components` (one-time write when stale entries found) so
  raw-option readers agree with runtime state.
- Media feature ACTIVATION and DEACTIVATION now only touch `media`.
- Did NOT remove video/document from the optional components list when folders EXIST — paid
  builds keep working unchanged (visibility is filtered by directory presence).
- Graceful degradation everywhere: legacy video/document content simply doesn't render; no
  `wp_die`, no notice.

---

## Smoke test results (folders moved out)

PHP `php -l` clean on all 56 changed present files. opcache reset via web request each round.

**PASS (no fatals/warnings in debug.log beyond pre-existing baseline noise — see below):**
- Logged-out: `/`, `/members/`, `/groups/`, `/forums/`, a single forum, a single discussion
  (topic — exercises reply attachments template), `/register/`, `/wp-login.php` → all 200.
- Logged-in admin: `/wp-admin/`, `bb-settings` (Settings 2.0), dashboard, users, options → 200.
- `bp-active-components` auto-scrubbed video/document on first load. ✔
- Media feature toggle writes only `media` into `bp-active-components`. ✔ (requirement #4)
- Forums reply attachment template no longer fatals (was the first bug found & fixed:
  `form-attachments.php` used `bp_is_active('media')` proxy for
  `bp_document_get_allowed_extension()`/`bp_video_get_allowed_extension()`).

**Baseline noise (pre-existing, NOT caused by this work):**
- `_load_textdomain_just_in_time` notice for the `buddyboss` textdomain.
- `Alchemy\BinaryDriver\Configuration` return-type Deprecated notices (vendor).

---

## ✅ RESOLVED — theme fatal (buddyboss-theme, separate product)

The BuddyBoss **theme** called removed plugin functions unguarded — same wrong-proxy pattern
as the plugin (`bp_is_active('media')` guarding video/document calls). Fixed in 2 theme files
(option (a) from the original plan — minimal guards, since the free Platform runs with this
theme):

1. `wp-content/themes/buddyboss-theme/template-parts/header-profile-menu.php`
   - line ~482: document menu guard `bp_is_active('media')` → `bp_is_active('document')`
   - line ~510: video menu guard `bp_is_active('media')` → `bp_is_active('video')`
   (these blocks contain the unguarded `bp_get_document_slug()` / `bp_get_video_slug()`)
2. `wp-content/themes/buddyboss-theme/inc/theme/template-functions.php`
   (theme's clone of `bp_nouveau_activity_privacy()`)
   - wrapped the `$document_activity` block in `&& bp_is_active('document')`
   - wrapped the `bp_document_folder_activity` / `bp_document_ids` meta block in `bp_is_active('document')`
   (these contain `bp_document_get_root_parent_id()`, `new BP_Document`, `new BP_Document_Folder`)

Full theme sweep against the 578 removed function names + BP_Video/BP_Document class uses:
the only remaining hits are now inside these guards, already `bp_is_active('video')`-gated
(`elementor/widgets/bb-activity.php:611`), or `function_exists`-guarded
(`template-parts/unread-messages.php:426`). Verified: member profile page (which surfaced the
fatal) now returns 200 with folders removed; 404 page returns 404; zero fatals.

⚠️ Theme core files are overwritten on theme update. These 2 edits must be re-applied (or
shipped in the theme's own release) when buddyboss-theme is updated, OR the theme should ship
a video/document-free compatible build for the free tier. Flag for the theme team.

---

## Smoke test — FINAL (both states, theme fixes applied)

**Folders REMOVED** (`php -l` clean on all changed files; opcache reset via web request):
- option auto-scrubbed video/document ✓
- `/`, member profile (200), member `documents/` & `video/` URLs → 404 (graceful, not 500),
  activity, groups, forums, wp-admin Settings 2.0 → all load ✓
- **0 PHP fatals / undefined-function / undefined-method** in debug.log ✓
**Baseline RESTORED** (folders present, all components active): all pages 200, document tab
200, **0 fatals** ✓
Only baseline noise remains in both states: `_load_textdomain_just_in_time` notice +
Alchemy\BinaryDriver vendor `Deprecated` notices (pre-existing, unrelated).

Note: member `/media/` and `/video/` tab URLs return 404 even at full baseline (a member
profile-support setting on the test user) — pre-existing, NOT caused by this work.

## Browser verification (Chrome extension, folders removed, 2026-06-13)

Logged in as admin; walked admin + frontend with console-error monitoring on every page.

**Admin (backend):**
- Settings 2.0 features grid — loads, no errors.
- Settings 2.0 **Media page** — side-nav shows Photos / Emoji / Animated GIFs / Security &
  Performance / Access Controls — **no Videos or Documents panels** (change #3 confirmed
  visually). Photos panel fields all render. No console errors.

**Frontend:** Activity feed, Members directory, **member profile `/members/john/`** (the page
that previously 500-fataled — now 200, header profile menu fine, profile sub-nav shows Photos
but no Documents/Videos tabs), Photos directory (393 photos — media fully works, no
regression), Forums directory + single topic + **reply form** (`form-attachments.php` renders
photo/GIF/emoji, no video/document upload buttons), Groups directory + single group + group
post composer. All render correctly.
- `/documents/` (stale nav link) → clean styled **404 page**, not a 500. Graceful.

**JS error found & FIXED:** Opening the activity/group post form threw
`TypeError: Cannot read properties of undefined (reading 'group_video')` in
`buddypress-activity-post-form.js` (`updateMultiMediaOptions`). Root cause: `bbRlVideo =
BP_Nouveau.video` (and `bbRlDocument = BP_Nouveau.document`) are populated only by the
Video/Document component localizers (`includes/video|document/functions.php`), which don't run
when the component is inactive — leaving the globals `undefined`.
- **Fix (file #57):** `src/bp-templates/bp-nouveau/includes/functions.php` — new
  `bb_nouveau_inactive_media_localize_fallback()` hooked to `bp_core_get_js_strings` (priority
  99) localizes safe placeholder `video`/`document` objects (all feature flags `false`) when
  those components are inactive. One PHP change fixes both regular AND minified JS (nouveau +
  readylaunch) with no JS rebuild, since they all read `BP_Nouveau.video`/`.document`.
- Re-verified in browser (group + profile post forms): **zero console errors**; server
  debug.log: **zero PHP fatals** across the whole session.

⚠️ Cosmetic config (not a code bug): the site's primary nav still has a manually-configured
"Documents" menu link (the admin added it; "Videos" was never in the menu). It now 404s
gracefully. The site admin should remove that stale menu item — it's WP menu config, not code.

## All-combinations verification (2026-06-15) — works WITH and WITHOUT each folder

Confirmed scope with user: video + document are removable; **media (photos) always stays**.
Tested all 4 presence combinations of bp-video / bp-document:

| Combo | video | document | PHP fatals (pages incl. admin) | `BP_Nouveau.video` / `.document` |
|-------|-------|----------|-------------------------------|----------------------------------|
| A | present | present | 0 (PASS) | real / real |
| B | absent  | absent  | 0 (PASS) | fallback / fallback |
| C | absent  | present | 0 (PASS) | fallback / real |
| D | present | absent  | 0 (PASS) | real / fallback |

- **PHP**: harness hit 9 frontend + 2 admin URLs per combo (home, activity, members,
  member profile, groups, single group, forums, single topic, photos, wp-admin, Settings 2.0
  Media). No HTTP 500s; `debug.log` had 0 fatals/undefined in every combo.
- **JS**: parsed the actual `BP_Nouveau` localized object on an authenticated page in every
  combo — `BP_Nouveau.video` and `BP_Nouveau.document` are ALWAYS defined objects (real when
  the component is present, all-false fallback when absent). Since `bbRlVideo`/`bbRlDocument`
  derive from these, the "Cannot read properties of undefined" crash is impossible in any
  combination, and absent components correctly report their feature flags as `false` (UI hidden).
- The per-component design means mixed states (C/D) behave correctly: the present component
  uses its real data; the absent one uses the safe fallback.

Conclusion: buddyboss-platform runs cleanly with **either, both, or neither** of bp-video /
bp-document present. Media is unaffected throughout. Dev env left at baseline (both present,
all active); temp test users and harness artifacts removed.

## Remaining TODO (all optional / user actions)
- [ ] USER ACTION: actually delete `src/bp-video/` + `src/bp-document/` when building the free
      package (removal verified safe). This is a git/packaging action — Claude never commits.
- [ ] THEME UPDATE caveat: the 2 buddyboss-theme edits live in theme core files (overwritten on
      update) — re-apply or have the theme team ship them.
- [ ] Optional: JS console check in a real browser (curl can't catch JS errors) — load activity
      feed with folders removed. No PHP-side JS/CSS enqueue breakage found.
- [ ] Cosmetic (out of strict scope): media feature card description in `bb-admin-settings-media.php`
      still says "photos, videos, documents…"; Settings 2.0 React copy unchanged.

## How to resume after terminal close
1. `cd /Users/varni/ddev/bbplatform/wp-content/plugins/buddyboss-platform`
2. Read this file top-to-bottom (note CURRENT ENVIRONMENT STATE — folders are in /tmp).
3. `git status` / `git diff --stat` to see the 56 changed files (user commits manually — NEVER commit/push).
4. Continue with "Remaining TODO".
5. Site: `ddev` project, `bbplatform.ddev.site`; reference design site `buddybossmembership.ddev.site`.
6. Opcache: reset via web request — create `/opcache_reset.php` with `opcache_reset();`, fetch via curl, delete. CLI reset doesn't work in this ddev.
7. Reference data from the audit (regenerate if /tmp cleared): `/tmp/vd_functions.txt`
   (578 removed fn names), `/tmp/vd_usage_raw.txt`, `/tmp/vd_class_usage.txt`,
   `/tmp/theme_vd_calls.txt`.
