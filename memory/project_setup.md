---
name: Events Plugin Dev Setup
description: MAMP + git setup for buddyboss-events development
type: project
---

## Environment
- WordPress served by MAMP at http://localhost:8888/buddyboss-dev
- MAMP PHP: /Applications/MAMP/bin/php/php8.3.28/bin/php
- Git repo: /Users/tom/Local Sites/Events/buddyboss-events/ (events branch of github.com/buddyboss/buddyboss-platform)
- MAMP plugins dir: /Applications/MAMP/htdocs/buddyboss-dev/wp-content/plugins/

## Installed plugins (source of truth in /Users/tom/Local Sites/Events/Plugins/)
- buddyboss-platform (with vendor/ - DO NOT delete vendor when syncing)
- buddyboss-platform-pro
- buddyboss-theme
- buddyboss-theme-child-1
- buddyboss-gamification
- buddyboss-sharing

## Sync commands
After editing files in buddyboss-events repo, sync to MAMP:
```bash
rsync -av \
  "/Users/tom/Local Sites/Events/buddyboss-events/src/bp-events/" \
  "/Applications/MAMP/htdocs/buddyboss-dev/wp-content/plugins/buddyboss-platform/bp-events/" \
  --exclude ".DS_Store"
```

Also sync templates:
```bash
rsync -av \
  "/Users/tom/Local Sites/Events/buddyboss-events/src/bp-templates/bp-nouveau/readylaunch/events/" \
  "/Applications/MAMP/htdocs/buddyboss-dev/wp-content/plugins/buddyboss-platform/bp-templates/bp-nouveau/readylaunch/events/" \
  --exclude ".DS_Store"
```

Also sync admin settings:
```bash
rsync -av \
  "/Users/tom/Local Sites/Events/buddyboss-events/src/bp-core/admin/settings/bp-admin-setting-events.php" \
  "/Applications/MAMP/htdocs/buddyboss-dev/wp-content/plugins/buddyboss-platform/bp-core/admin/settings/bp-admin-setting-events.php"
```

NEVER run rsync --delete on buddyboss-platform as it will wipe the vendor/ directory.
