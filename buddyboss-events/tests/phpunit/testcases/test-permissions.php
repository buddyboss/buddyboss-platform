<?php

/**
 * Tests for event creation permissions.
 *
 * Covers requirement: ADMN-01 (who can create events — admins-only vs all
 * members, controlled by a site setting).
 *
 * @package BuddyBoss\Events\Tests
 */

/**
 * Class BP_Events_Test_Permissions
 *
 * @group bp-events
 * @group permissions
 */
class BP_Events_Test_Permissions extends WP_UnitTestCase {

	/**
	 * A non-admin (subscriber) user ID used across permission tests.
	 *
	 * @var int
	 */
	protected static $subscriber_user_id;

	/**
	 * An admin user ID used across permission tests.
	 *
	 * @var int
	 */
	protected static $admin_user_id;

	/**
	 * Create reusable users once for all tests in this class.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$subscriber_user_id = $factory->user->create( array( 'role' => 'subscriber' ) );
		self::$admin_user_id      = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Tear down: restore the creation permission option to its default.
	 */
	public function tearDown(): void {
		bp_delete_option( 'bb_events_creation_permission' );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Test that only administrators can create events when the site setting
	 * restricts creation to admins.
	 *
	 * Covers ADMN-01 (admins-only mode): a subscriber-level user must be
	 * denied event creation capability when the setting is set to admins only.
	 */
	public function test_creation_permission_admins_only() {
		bp_update_option( 'bb_events_creation_permission', 'admins' );

		wp_set_current_user( self::$subscriber_user_id );
		$can_create = bp_events_user_can_create( self::$subscriber_user_id );

		$this->assertFalse( $can_create, 'Subscriber must not be able to create events in admins-only mode.' );
	}

	/**
	 * Test that all logged-in members can create events when the site setting
	 * permits it.
	 *
	 * Covers ADMN-01 (all-members mode): a subscriber-level user must be
	 * granted event creation capability when the setting allows all members.
	 */
	public function test_creation_permission_members() {
		bp_update_option( 'bb_events_creation_permission', 'members' );

		wp_set_current_user( self::$subscriber_user_id );
		$can_create = bp_events_user_can_create( self::$subscriber_user_id );

		$this->assertTrue( $can_create, 'Subscriber must be able to create events when permission is set to members.' );
	}
}
