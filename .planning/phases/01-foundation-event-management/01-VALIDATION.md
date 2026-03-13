---
phase: 1
slug: foundation-event-management
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-13
---

# Phase 1 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit (WordPress test suite pattern) |
| **Config file** | `buddyboss-events/phpunit.xml.dist` (Wave 0 creates) |
| **Quick run command** | `vendor/bin/phpunit tests/phpunit/test-event-crud.php -x` |
| **Full suite command** | `vendor/bin/phpunit` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run relevant test file (see Per-Task map below)
- **After every plan wave:** Run `vendor/bin/phpunit`
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** ~30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 1-W0-01 | W0 | 0 | EVNT-01,02,04 | unit | `vendor/bin/phpunit tests/phpunit/test-event-crud.php -x` | ❌ W0 | ⬜ pending |
| 1-W0-02 | W0 | 0 | EVNT-03 | unit | `vendor/bin/phpunit tests/phpunit/test-recurring.php -x` | ❌ W0 | ⬜ pending |
| 1-W0-03 | W0 | 0 | EVNT-05,06 | unit | `vendor/bin/phpunit tests/phpunit/test-calendar-privacy.php -x` | ❌ W0 | ⬜ pending |
| 1-W0-04 | W0 | 0 | ADMN-01 | unit | `vendor/bin/phpunit tests/phpunit/test-permissions.php -x` | ❌ W0 | ⬜ pending |
| 1-W0-05 | W0 | 0 | ADMN-02 | unit | `vendor/bin/phpunit tests/phpunit/test-admin.php -x` | ❌ W0 | ⬜ pending |
| 1-W0-06 | W0 | 0 | ADMN-04 | integration | `vendor/bin/phpunit tests/phpunit/test-moderation.php -x` | ❌ W0 | ⬜ pending |
| 1-01-01 | 01 | 1 | EVNT-01 | unit | `vendor/bin/phpunit tests/phpunit/test-event-crud.php::test_create_in_person_event -x` | ❌ W0 | ⬜ pending |
| 1-01-02 | 01 | 1 | EVNT-02 | unit | `vendor/bin/phpunit tests/phpunit/test-event-crud.php::test_create_virtual_event -x` | ❌ W0 | ⬜ pending |
| 1-01-03 | 01 | 1 | EVNT-04 | unit | `vendor/bin/phpunit tests/phpunit/test-event-crud.php::test_draft_not_in_published_query -x` | ❌ W0 | ⬜ pending |
| 1-02-01 | 02 | 1 | EVNT-03 | unit | `vendor/bin/phpunit tests/phpunit/test-recurring.php::test_publish_generates_occurrences -x` | ❌ W0 | ⬜ pending |
| 1-02-02 | 02 | 1 | EVNT-03 | unit | `vendor/bin/phpunit tests/phpunit/test-recurring.php::test_edit_single_occurrence -x` | ❌ W0 | ⬜ pending |
| 1-02-03 | 02 | 1 | EVNT-03 | unit | `vendor/bin/phpunit tests/phpunit/test-recurring.php::test_edit_this_and_following -x` | ❌ W0 | ⬜ pending |
| 1-03-01 | 03 | 1 | EVNT-05,06 | unit | `vendor/bin/phpunit tests/phpunit/test-calendar-privacy.php::test_group_event_excluded -x` | ❌ W0 | ⬜ pending |
| 1-03-02 | 03 | 1 | EVNT-06 | unit | `vendor/bin/phpunit tests/phpunit/test-calendar-privacy.php::test_private_group_never_visible -x` | ❌ W0 | ⬜ pending |
| 1-04-01 | 04 | 2 | ADMN-01 | unit | `vendor/bin/phpunit tests/phpunit/test-permissions.php::test_creation_permission -x` | ❌ W0 | ⬜ pending |
| 1-05-01 | 05 | 2 | ADMN-02 | unit | `vendor/bin/phpunit tests/phpunit/test-admin.php::test_approve_pending_event -x` | ❌ W0 | ⬜ pending |
| 1-06-01 | 06 | 2 | ADMN-04 | integration | `vendor/bin/phpunit tests/phpunit/test-moderation.php::test_event_report -x` | ❌ W0 | ⬜ pending |
| 1-07-01 | 07 | 2 | ADMN-03 | manual | N/A — WP admin context required | manual-only | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `buddyboss-events/tests/phpunit/bootstrap.php` — WordPress + BuddyBoss test bootstrap
- [ ] `buddyboss-events/phpunit.xml.dist` — PHPUnit config pointing to tests/phpunit/
- [ ] `buddyboss-events/tests/phpunit/test-event-crud.php` — stubs for EVNT-01, EVNT-02, EVNT-04
- [ ] `buddyboss-events/tests/phpunit/test-recurring.php` — stubs for EVNT-03 (3 tests)
- [ ] `buddyboss-events/tests/phpunit/test-calendar-privacy.php` — stubs for EVNT-05, EVNT-06
- [ ] `buddyboss-events/tests/phpunit/test-permissions.php` — stubs for ADMN-01
- [ ] `buddyboss-events/tests/phpunit/test-admin.php` — stubs for ADMN-02
- [ ] `buddyboss-events/tests/phpunit/test-moderation.php` — stubs for ADMN-04

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Admin revenue page returns event count data | ADMN-03 | Requires WP admin context, no headless runner configured | 1. Log in as admin. 2. Navigate to wp-admin → Events → Revenue. 3. Verify event count stat appears. |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
