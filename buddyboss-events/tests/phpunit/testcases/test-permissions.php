<?php

/**
 * Test stubs for event creation permissions.
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
	 * Test that only administrators can create events when the site setting
	 * restricts creation to admins.
	 *
	 * Covers ADMN-01 (admins-only mode): a subscriber-level user must be
	 * denied event creation capability when the setting is set to admins only.
	 */
	public function test_creation_permission_admins_only() {
		$this->markTestIncomplete( 'TODO: implement after Plan 04 completes' );
	}

	/**
	 * Test that all logged-in members can create events when the site setting
	 * permits it.
	 *
	 * Covers ADMN-01 (all-members mode): a subscriber-level user must be
	 * granted event creation capability when the setting allows all members.
	 */
	public function test_creation_permission_members() {
		$this->markTestIncomplete( 'TODO: implement after Plan 04 completes' );
	}
}
