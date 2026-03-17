---
phase: 4
slug: meta-api-foundation-taxonomy
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-17
---

# Phase 4 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit (configured from Phase 1) |
| **Config file** | `buddyboss-events/phpunit.xml.dist` |
| **Quick run command** | `php -l src/bp-events/bp-events-functions.php` (syntax check per modified file) |
| **Full suite command** | `phpunit --configuration buddyboss-events/phpunit.xml.dist` |
| **Estimated runtime** | ~10 seconds (syntax checks); ~60s full suite |

---

## Sampling Rate

- **After every task commit:** Run `php -l {modified_file}` — syntax verification
- **After every plan wave:** Run full PHPUnit suite (or `php -l` on all modified files if WP test suite unavailable)
- **Before `/gsd:verify-work`:** Full suite must be green (or all stubs with `markTestIncomplete`)
- **Max feedback latency:** ~10 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 4-00-01 | 00 | 0 | META-API | unit | `phpunit --filter test_bp_event_meta_roundtrip` | ❌ Wave 0 | ⬜ pending |
| 4-00-02 | 00 | 0 | META-API | unit | `phpunit --filter test_bp_event_update_meta` | ❌ Wave 0 | ⬜ pending |
| 4-00-03 | 00 | 0 | TAX-01 | unit | `phpunit --filter test_event_category_assignment` | ❌ Wave 0 | ⬜ pending |
| 4-00-04 | 00 | 0 | TAX-01 | integration | `phpunit --filter test_get_events_category_filter` | ❌ Wave 0 | ⬜ pending |
| 4-00-05 | 00 | 0 | TAX-02 | unit | `phpunit --filter test_event_tag_assignment` | ❌ Wave 0 | ⬜ pending |
| 4-00-06 | 00 | 0 | TAX-03 | integration | `phpunit --filter test_taxonomy_archive_privacy` | ❌ Wave 0 | ⬜ pending |
| 4-00-07 | 00 | 0 | TAX-03 | unit | `phpunit --filter test_privacy_filter_excludes_private` | ❌ Wave 0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `buddyboss-events/tests/phpunit/testcases/test-bp-event-meta.php` — stubs for META-API roundtrip, update, delete
- [ ] `buddyboss-events/tests/phpunit/testcases/test-bp-event-taxonomy.php` — stubs for TAX-01, TAX-02 category/tag assignment
- [ ] `buddyboss-events/tests/phpunit/testcases/test-bp-event-taxonomy-privacy.php` — stubs for TAX-03 privacy filter

*Existing `tests/phpunit/bootstrap.php` should cover events meta type registration — verify during Wave 0.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Category archive never shows private group events to logged-out visitor | TAX-03 | Requires live browser session + BuddyBoss group privacy state | 1. Create event in private group, assign to a category. 2. Log out. 3. Visit `/event-category/[slug]/`. 4. Confirm event is absent. |
| Category icon displays on event directory filter | TAX-01 | Requires visual browser verification of icon rendering | 1. Create category with icon in WP admin. 2. Visit event directory. 3. Confirm icon appears in category filter dropdown. |
| Tag autocomplete works in event creation wizard | TAX-02 | Requires JS interaction in browser | 1. Start creating event. 2. Navigate to category/tag step. 3. Type partial tag name. 4. Confirm autocomplete suggestions appear. |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING test file references
- [ ] No watch-mode flags
- [ ] Feedback latency < 10s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
