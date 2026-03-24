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
		global $wpdb;
		$bp = buddypress();

		// Create users.
		$inviter_id = self::factory()->user->create();
		$invitee_id = self::factory()->user->create();

		// Create a group and add invitee as a member.
		$group_id = groups_create_group( array(
			'creator_id' => $inviter_id,
			'name'        => 'Test Group for Invite',
			'slug'        => 'test-group-invite-' . uniqid(),
			'status'      => 'public',
		) );
		groups_join_group( $group_id, $invitee_id );

		// Create a group event.
		$event_id = bp_events_create_event( array(
			'title'        => 'Test Group Event',
			'organizer_id' => $inviter_id,
			'group_id'     => $group_id,
			'start_date'   => '2025-12-01 10:00:00',
			'end_date'     => '2025-12-01 11:00:00',
			'status'       => 'published',
		) );

		$this->assertNotEmpty( $event_id, 'Event should be created' );

		// Invite the group member.
		$result = bp_events_invite_member( $event_id, $invitee_id, $inviter_id );

		$this->assertTrue( $result, 'bp_events_invite_member should return true for a group member' );

		// Verify row exists in the invites table.
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$bp->events->table_name_invites} WHERE event_id = %d AND invitee_id = %d",
			$event_id,
			$invitee_id
		) );

		$this->assertNotNull( $row, 'Invite row should exist in wp_bp_event_invites' );
		$this->assertEquals( 'pending', $row->status, 'Invite status should be pending' );
		$this->assertEquals( $inviter_id, (int) $row->inviter_id, 'Inviter ID should be set correctly' );
	}

	/**
	 * Test that inviting a user not in the event's group returns WP_Error.
	 *
	 * Covers BB-03: invite blocked for non-member.
	 */
	public function test_invite_non_member_blocked() {
		// Create users.
		$inviter_id     = self::factory()->user->create();
		$non_member_id  = self::factory()->user->create();

		// Create a group — non_member is NOT added to it.
		$group_id = groups_create_group( array(
			'creator_id' => $inviter_id,
			'name'        => 'Test Group No Non-Member',
			'slug'        => 'test-group-no-non-member-' . uniqid(),
			'status'      => 'public',
		) );

		// Create a group event.
		$event_id = bp_events_create_event( array(
			'title'        => 'Test Group Event Non-Member',
			'organizer_id' => $inviter_id,
			'group_id'     => $group_id,
			'start_date'   => '2025-12-01 10:00:00',
			'end_date'     => '2025-12-01 11:00:00',
			'status'       => 'published',
		) );

		$this->assertNotEmpty( $event_id, 'Event should be created' );

		// Attempt to invite a non-member.
		$result = bp_events_invite_member( $event_id, $non_member_id, $inviter_id );

		$this->assertWPError( $result, 'bp_events_invite_member should return WP_Error for a non-member' );
		$this->assertEquals(
			'bp_events_invite_not_member',
			$result->get_error_code(),
			'Error code should be bp_events_invite_not_member'
		);
	}
}
