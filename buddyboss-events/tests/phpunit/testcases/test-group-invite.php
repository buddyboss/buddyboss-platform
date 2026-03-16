<?php

/**
 * Tests for BuddyBoss Group invite integration.
 *
 * Covers requirement: BB-03 (invite row written, invite blocked for non-member).
 *
 * @package BuddyBoss\Events\Tests
 */

/**
 * Class BP_Events_Test_Group_Invite
 *
 * @group bp-events
 * @group group-invite
 */
class BP_Events_Test_Group_Invite extends WP_UnitTestCase {

	/**
	 * Test that bp_events_invite_member inserts a row into wp_bp_event_invites with status='pending'.
	 *
	 * Covers BB-03: invite row written for a group member.
	 */
	public function test_invite_group_member_writes_invite_row() {
		$this->markTestIncomplete( 'TODO: implement after Plan 03-03 completes' );
	}

	/**
	 * Test that inviting a user not in the event's group returns WP_Error.
	 *
	 * Covers BB-03: invite blocked for non-member.
	 */
	public function test_invite_non_member_blocked() {
		$this->markTestIncomplete( 'TODO: implement after Plan 03-03 completes' );
	}
}
