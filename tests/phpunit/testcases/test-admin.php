<?php

/**
 * Tests for event admin moderation actions.
 *
 * Covers requirement: ADMN-02 (admin approval — approving a pending event
 * sets its status to published).
 *
 * @package BuddyBoss\Events\Tests
 */

/**
 * Class BP_Events_Test_Admin
 *
 * @group bp-events
 * @group admin
 */
class BP_Events_Test_Admin extends WP_UnitTestCase {

	/**
	 * Admin user ID created for this test class.
	 *
	 * @var int
	 */
	protected static $admin_user_id;

	/**
	 * Create an admin user once for all tests in this class.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_user_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Set up: run as admin so bp_events_create_event() permission check passes.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::$admin_user_id );
	}

	/**
	 * Tear down: clear the current user.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Test that approving a pending event transitions its status to published.
	 *
	 * Covers ADMN-02: calling bp_events_update_event() with status='published'
	 * on a pending event must update its status and persist that change.
	 */
	public function test_approve_pending_event() {
		// Create a pending event directly (bypass moderation flag by setting status explicitly).
		$event_id = bp_events_create_event(
			array(
				'title'        => 'Pending Approval Event',
				'start_date'   => '2026-07-01 10:00:00',
				'end_date'     => '2026-07-01 11:00:00',
				'organizer_id' => self::$admin_user_id,
				'status'       => 'pending',
			)
		);

		$this->assertNotEmpty( $event_id, 'bp_events_create_event() should return a non-zero ID for a pending event.' );

		// Approve: update status to published.
		$result = bp_events_update_event( $event_id, array( 'status' => 'published' ) );

		$this->assertTrue( $result, 'bp_events_update_event() should return true on success.' );

		$event = bp_events_get_event( $event_id );

		$this->assertInstanceOf( 'BP_Event', $event, 'bp_events_get_event() should return a BP_Event object.' );
		$this->assertSame( 'published', $event->status, 'Approved event status should be published.' );
	}
}
