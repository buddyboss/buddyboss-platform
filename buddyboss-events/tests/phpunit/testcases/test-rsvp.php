<?php
/**
 * Tests for RSVP functionality.
 *
 * Covers TKET-02: RSVP below capacity, at capacity (waitlist), and cancel.
 *
 * @package BuddyBoss\Events\Tests
 * @since   1.0.0
 */
class BP_Events_Test_RSVP extends WP_UnitTestCase {

	/**
	 * RSVP below capacity writes status='registered'.
	 *
	 * @covers TKET-02
	 */
	public function test_rsvp_creates_registered_row() {
		$this->markTestIncomplete( 'TODO: implement after Plan 01 completes' );
	}

	/**
	 * RSVP when event is at capacity writes status='waitlisted'.
	 *
	 * @covers TKET-02
	 */
	public function test_rsvp_at_capacity_creates_waitlist_row() {
		$this->markTestIncomplete( 'TODO: implement after Plan 01 completes' );
	}

	/**
	 * Cancelling an RSVP deletes the attendee row.
	 *
	 * @covers TKET-02
	 */
	public function test_cancel_rsvp_removes_row() {
		$this->markTestIncomplete( 'TODO: implement after Plan 01 completes' );
	}
}
