---
name: MAMP Development Notes
description: Important notes about the MAMP setup for this project
type: feedback
---

- NEVER use rsync --delete on buddyboss-platform — it wipes the vendor/ directory which contains private BuddyBoss Composer packages that cannot be rebuilt without SSH access
- Restart MAMP servers if getting 502 errors (PHP-FPM crashes)
- MAMP MySQL port: 8889
- WordPress site URL: http://localhost:8888/buddyboss-dev
- The events branch IS the buddyboss-platform codebase — we add bp-events/ component to it
- Always sync BOTH the bp-events/ component AND the readylaunch templates AND the admin settings file after editing
