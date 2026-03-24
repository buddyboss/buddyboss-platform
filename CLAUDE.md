# BuddyBoss Events — Claude Instructions

## Environment

- **WordPress is served by MAMP**, not Docker.
- Plugin files must be synced to MAMP after every edit.
- MAMP plugins directory: `/Applications/MAMP/htdocs/buddyboss-dev/wp-content/plugins/`

## MANDATORY: Auto-sync after every file change

After editing ANY file in the plugin directory, ALWAYS run the sync command immediately — do not wait to be asked.

### Plugin sync command

**buddyboss-events (syncs to buddyboss-events in MAMP):**
```bash
rsync -av --delete \
  "/Users/tom/Local Sites/Events/buddyboss-events/" \
  "/Applications/MAMP/htdocs/buddyboss-dev/wp-content/plugins/buddyboss-events/" \
  --exclude ".git" --exclude ".DS_Store" --exclude "node_modules"
```

## Plugin locations

| Plugin | Source directory |
|--------|-----------------|
| BuddyBoss Events | `/Users/tom/Local Sites/Events/buddyboss-events/` |

## Git

- Remote: `https://github.com/buddyboss/buddyboss-platform`
- Branch: `events`
- New events component: `src/bp-events/` (to be created)

## WordPress database

- MAMP MySQL runs on port 8889 (default MAMP port)
- WP config at: `/Applications/MAMP/htdocs/buddyboss-dev/wp-config.php`

## BuddyBoss Design System & Development Docs (Contextium)

When building any UI or backend feature, read the relevant cached Contextium reference docs first. Extract content with:
```bash
cat ".contextium-cache/<filename>.json" | python3 -c "import json,sys; d=json.load(sys.stdin); print(d['data']['content'])"
```

| File | When to use |
|------|------------|
| `.contextium-cache/01-foundations_md.json` | Colors, typography, spacing tokens — use for ALL UI work |
| `.contextium-cache/02-components_md.json` | Buttons, cards, badges, inputs, modals — use for ALL UI work |
| `.contextium-cache/03-settings-pages_md.json` | Settings page layout patterns |
| `.contextium-cache/04-builder-pages_md.json` | Builder/wizard page patterns |
| `.contextium-cache/05-list-pages_md.json` | List/directory page patterns |
| `.contextium-cache/06-groups-pages_md.json` | Group page patterns |
| `.contextium-cache/database_custom_tables.json` | Custom table schema conventions |
| `.contextium-cache/database_meta_patterns.json` | Meta API patterns — use for any eventmeta work |
| `.contextium-cache/database_migrations.json` | Migration/install patterns |
| `.contextium-cache/database_query_patterns.json` | Query builder patterns |
| `.contextium-cache/rest_api_endpoints.json` | REST endpoint conventions |
| `.contextium-cache/notifications_events.json` | Notification/event hook patterns |
| `.contextium-cache/feature_index.json` | Index of all existing BuddyBoss features |

**Rule:** All PHP, CSS, and JS must follow the BuddyBoss 2.0 design system (foundations + components docs). Never use hardcoded hex colors or arbitrary spacing — use the CSS custom properties defined in the foundations doc.
