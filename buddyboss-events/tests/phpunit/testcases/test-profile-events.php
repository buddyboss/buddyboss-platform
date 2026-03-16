<?php

/**
 * Tests for BuddyBoss Profile events integration.
 *
 * Covers requirement: BB-04 (attending query, hosting query).
 *
 * @package BuddyBoss\Events\Tests
 */

/**
 * Class BP_Events_Test_Profile_Events
 *
 * @group bp-events
 * @group profile-events
 */
class BP_Events_Test_Profile_Events extends WP_UnitTestCase {

	/**
	 * Test that bp_events_get_events(['user_id' => X]) returns events where X has an attendee row.
	 *
	 * Covers BB-04: attending tab returns events user RSVP'd to.
	 */
	public function test_attending_tab_returns_events_user_rsvpd_to() {
		$this->markTestIncomplete( 'TODO: implement after Plan 03-04 completes' );
	}

	/**
	 * Test that bp_events_get_events(['organizer_id' => X]) returns events where X is organizer.
	 *
	 * Covers BB-04: hosting tab returns events user created.
	 */
	public function test_hosting_tab_returns_events_user_created() {
		$this->markTestIncomplete( 'TODO: implement after Plan 03-04 completes' );
	}
}
