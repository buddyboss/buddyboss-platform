---
phase: 2
slug: payments-ticketing
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-14
---

# Phase 2 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit (existing — `phpunit.xml.dist`) |
| **Config file** | `buddyboss-events/phpunit.xml.dist` |
| **Quick run command** | `cd /Users/tom/Local\ Sites/Events/buddyboss-events && vendor/bin/phpunit --filter test_rsvp` |
| **Full suite command** | `cd /Users/tom/Local\ Sites/Events/buddyboss-events && vendor/bin/phpunit` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `vendor/bin/phpunit --filter test_rsvp`
- **After every plan wave:** Run `vendor/bin/phpunit` (full suite)
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** ~30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 2-00-01 | 00 | 0 | TKET-02 | unit | `phpunit --filter test_rsvp_creates_registered_row` | ❌ W0 | ⬜ pending |
| 2-00-02 | 00 | 0 | TKET-02 | unit | `phpunit --filter test_rsvp_at_capacity_creates_waitlist_row` | ❌ W0 | ⬜ pending |
| 2-00-03 | 00 | 0 | TKET-02 | unit | `phpunit --filter test_cancel_rsvp_removes_row` | ❌ W0 | ⬜ pending |
| 2-00-04 | 00 | 0 | TKET-04 | unit | `phpunit --filter test_rsvp_group_restriction_blocks_non_member` | ❌ W0 | ⬜ pending |
| 2-00-05 | 00 | 0 | TKET-04 | unit | `phpunit --filter test_rsvp_group_restriction_allows_member` | ❌ W0 | ⬜ pending |
| 2-00-06 | 00 | 0 | ATTN-01 | unit | `phpunit --filter test_cancel_rsvp_triggers_waitlist_notification` | ❌ W0 | ⬜ pending |
| 2-00-07 | 00 | 0 | ATTN-01 | unit | `phpunit --filter test_capacity_increase_triggers_waitlist_notification` | ❌ W0 | ⬜ pending |
| 2-00-08 | 00 | 0 | ATTN-02 | unit | `phpunit --filter test_ical_endpoint_returns_valid_ics` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/phpunit/testcases/test-rsvp.php` — stubs for TKET-02 (registered/waitlisted rows, cancel)
- [ ] `tests/phpunit/testcases/test-rsvp-restrictions.php` — stubs for TKET-04 (group restriction)
- [ ] `tests/phpunit/testcases/test-waitlist.php` — stubs for ATTN-01 (notification broadcast triggers)
- [ ] `tests/phpunit/testcases/test-calendar-export.php` — stubs for ATTN-02 (iCal content validation)

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| RSVP button changes to "Attending ✓" in-page | TKET-02 | Browser UI interaction | Log in, visit event page, click RSVP — button must change label without page reload |
| "Join Waitlist" appears when event at capacity | ATTN-01 | Requires capacity boundary condition | Set capacity to current attendee count, visit event as new user — button must show "Join Waitlist" |
| Waitlist broadcast email arrives after cancellation | ATTN-01 | Email delivery | Cancel an RSVP on a full event with waitlisted users — all waitlisted users receive email |
| BuddyBoss notification bell shows waitlist alert | ATTN-01 | BuddyBoss notification UI | Cancel RSVP triggering waitlist — logged-in waitlisted user sees notification in BB bell |
| Non-member RSVP button is disabled with message | TKET-04 | Browser UI + group membership | Visit group-restricted event as non-group-member — button disabled, message visible |
| iCal download delivers valid .ics file | ATTN-02 | File download verification | Click iCal link on event page — browser downloads .ics file; open in calendar app |
| Google Calendar link opens correct pre-filled event | ATTN-02 | External service redirect | Click Google Calendar link — opens Google Calendar with event pre-filled |
| Organizer attendee panel shows list + remove controls | TKET-02 | Organizer-role UI | Visit own event as organizer — see attendee panel with remove buttons |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
