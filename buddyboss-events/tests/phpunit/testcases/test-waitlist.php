<?php
/**
 * Tests for waitlist notification broadcasts.
 *
 * Covers ATTN-01: cancelling a registered RSVP or increasing capacity
 * triggers bp_events_notify_waitlist.
 *
 * @package BuddyBoss\Events\Tests
 * @since   1.0.0
 */
class BP_Events_Test_Waitlist extends WP_UnitTestCase {

	/**
	 * Cancelling a registered RSVP calls bp_events_notify_waitlist.
	 *
	 * @covers ATTN-01
	 */
	public function test_cancel_rsvp_triggers_waitlist_notification() {
		$this->markTestIncomplete( 'TODO: implement after Plan 01 completes' );
	}

	/**
	 * Increasing event capacity calls bp_events_notify_waitlist.
	 *
	 * @covers ATTN-01
	 */
	public function test_capacity_increase_triggers_waitlist_notification() {
		$this->markTestIncomplete( 'TODO: implement after Plan 01 completes' );
	}
}
