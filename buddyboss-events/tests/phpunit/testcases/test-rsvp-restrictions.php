<?php
/**
 * Tests for RSVP group restrictions.
 *
 * Covers TKET-04: group-restricted events block non-members and allow members.
 *
 * @package BuddyBoss\Events\Tests
 * @since   1.0.0
 */
class BP_Events_Test_RSVP_Restrictions extends WP_UnitTestCase {

	/**
	 * Non-group-member cannot RSVP to a group-restricted event.
	 *
	 * @covers TKET-04
	 */
	public function test_rsvp_group_restriction_blocks_non_member() {
		$this->markTestIncomplete( 'TODO: implement after Plan 01 completes' );
	}

	/**
	 * Group member can RSVP to a group-restricted event.
	 *
	 * @covers TKET-04
	 */
	public function test_rsvp_group_restriction_allows_member() {
		$this->markTestIncomplete( 'TODO: implement after Plan 01 completes' );
	}
}
