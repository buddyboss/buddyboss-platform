<?php

/**
 * Test stubs for calendar privacy controls.
 *
 * Covers requirements: EVNT-05 (per-group opt-out from site calendar),
 * EVNT-06 (private/hidden group events never visible in site calendar).
 *
 * @package BuddyBoss\Events\Tests
 */

/**
 * Class BP_Events_Test_Calendar_Privacy
 *
 * @group bp-events
 * @group calendar-privacy
 */
class BP_Events_Test_Calendar_Privacy extends WP_UnitTestCase {

	/**
	 * Test that a group event is excluded from the site calendar when the
	 * per-group calendar setting is off.
	 *
	 * Covers EVNT-05: when a group admin disables the calendar opt-in for
	 * their group, that group's events must not appear in the site-wide
	 * calendar query.
	 */
	public function test_group_event_excluded_when_setting_off() {
		$this->markTestIncomplete( 'TODO: implement after Plan 03 completes' );
	}

	/**
	 * Test that events from private or hidden groups never appear in the
	 * site-wide calendar, regardless of group settings.
	 *
	 * Covers EVNT-06: events belonging to private or hidden groups must
	 * always be excluded from site calendar queries.
	 */
	public function test_private_group_never_visible() {
		$this->markTestIncomplete( 'TODO: implement after Plan 03 completes' );
	}
}
