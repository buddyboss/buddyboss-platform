<?php
/**
 * Tests for calendar export (iCal).
 *
 * Covers ATTN-02: REST GET /events/{id}/ical response body begins with BEGIN:VCALENDAR.
 *
 * @package BuddyBoss\Events\Tests
 * @since   1.0.0
 */
class BP_Events_Test_Calendar_Export extends WP_UnitTestCase {

	/**
	 * REST GET /events/{id}/ical response body begins with BEGIN:VCALENDAR.
	 *
	 * @covers ATTN-02
	 */
	public function test_ical_endpoint_returns_valid_ics() {
		$this->markTestIncomplete( 'TODO: implement after Plan 01 completes' );
	}
}
