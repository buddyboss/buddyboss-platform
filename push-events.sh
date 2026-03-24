#!/usr/bin/env bash
# push-events.sh — sync buddyboss-events plugin files to origin/Events branch
# Usage: ./push-events.sh "optional commit message"
set -e

PLUGIN_SRC="/Users/tom/Local Sites/Events/buddyboss-events"
REPO="/Users/tom/Local Sites/Events"
WORKTREE="/tmp/bb-push-events"

cd "$REPO"

# Clean up any stale worktree
git worktree remove "$WORKTREE" --force 2>/dev/null || true

# Check out Events branch in a temp worktree
git worktree add "$WORKTREE" Events

# Sync only plugin source files — no planning, tooling, or DS_Store
rsync -a --delete \
  "$PLUGIN_SRC/src/bp-events/" \
  "$WORKTREE/src/bp-events/" \
  --exclude ".DS_Store"

rsync -a \
  "$PLUGIN_SRC/src/bp-core/admin/settings/bp-admin-setting-events.php" \
  "$WORKTREE/src/bp-core/admin/settings/"

rsync -a --delete \
  "$PLUGIN_SRC/src/bp-templates/bp-nouveau/readylaunch/events/" \
  "$WORKTREE/src/bp-templates/bp-nouveau/readylaunch/events/" \
  --exclude ".DS_Store"

rsync -a \
  "$PLUGIN_SRC/tests/phpunit/testcases/"test-*.php \
  "$WORKTREE/tests/phpunit/testcases/"

# Stage and push
cd "$WORKTREE"
git add \
  src/bp-events/ \
  src/bp-core/admin/settings/bp-admin-setting-events.php \
  src/bp-templates/bp-nouveau/readylaunch/events/ \
  tests/phpunit/testcases/

if git diff --cached --quiet; then
  echo "Nothing new to push — Events branch is already up to date."
else
  MSG="${1:-chore(bp-events): sync events component}"
  git commit -m "$MSG"
  git push origin Events
  echo "✓ Pushed to origin/Events"
fi

# Clean up worktree
cd "$REPO"
git worktree remove "$WORKTREE" --force
