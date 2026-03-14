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
	 * Event ID used across tests.
	 *
	 * @var int
	 */
	protected static $event_id;

	/**
	 * Group ID used across tests.
	 *
	 * @var int
	 */
	protected static $group_id;

	/**
	 * User ID that is a member of the group.
	 *
	 * @var int
	 */
	protected static $member_id;

	/**
	 * User ID that is NOT a member of the group.
	 *
	 * @var int
	 */
	protected static $non_member_id;

	/**
	 * Set up shared fixtures for the test class.
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		// Create two test users.
		self::$member_id     = self::factory()->user->create();
		self::$non_member_id = self::factory()->user->create();

		// Create an organiser user and a test event.
		$organiser_id    = self::factory()->user->create();
		self::$event_id  = bp_events_create_event( array(
			'title'        => 'Group-Restricted Test Event',
			'description'  => 'Test event for group restriction.',
			'organizer_id' => $organiser_id,
			'start_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '+1 day' ) ),
			'end_date'     => gmdate( 'Y-m-d H:i:s', strtotime( '+1 day +2 hours' ) ),
			'timezone'     => 'UTC',
			'status'       => 'published',
		) );

		// Create a BuddyBoss/BuddyPress group.
		self::$group_id = groups_create_group( array(
			'creator_id' => $organiser_id,
			'name'       => 'Test Restriction Group',
			'slug'       => 'test-restriction-group-' . time(),
			'status'     => 'public',
		) );

		// Add member_id as a confirmed group member.
		groups_join_group( self::$group_id, self::$member_id );
	}

	/**
	 * Non-group-member cannot RSVP to a group-restricted event.
	 *
	 * @covers TKET-04
	 */
	public function test_rsvp_group_restriction_blocks_non_member() {
		// Set the group restriction meta on the event.
		bp_events_update_meta( self::$event_id, 'rsvp_group_id', self::$group_id );

		// Act as the non-member.
		wp_set_current_user( self::$non_member_id );

		$result = bp_events_user_can_rsvp( self::$event_id, self::$non_member_id );

		// Must return a WP_Error with the correct code.
		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'bp_rest_events_rsvp_restricted', $result->get_error_code() );
	}

	/**
	 * Group member can RSVP to a group-restricted event.
	 *
	 * @covers TKET-04
	 */
	public function test_rsvp_group_restriction_allows_member() {
		// Ensure the group restriction meta is set.
		bp_events_update_meta( self::$event_id, 'rsvp_group_id', self::$group_id );

		// Act as the confirmed group member.
		wp_set_current_user( self::$member_id );

		$result = bp_events_user_can_rsvp( self::$event_id, self::$member_id );

		// Must return true (not a WP_Error).
		$this->assertTrue( $result );
	}
}
