<?php

/**
 * Profile types stored on invites are only applied when the inviter may choose them.
 *
 * @group members
 * @group invites
 * @group member_types
 */
class BP_Tests_Members_Invite_Member_Type extends BP_UnitTestCase {

	protected $admin_type_id;
	protected $plain_type_id;
	protected $inviter_type_id;
	protected $inviter_id;
	protected $admin_type;
	protected $plain_type;

	public function set_up() {
		parent::set_up();

		// Unique keys: bp_member_type_post_by_type() caches post IDs per key for the whole run.
		$suffix           = wp_generate_password( 6, false );
		$this->admin_type = 'inv_admin_' . strtolower( $suffix );
		$this->plain_type = 'inv_plain_' . strtolower( $suffix );
		$inviter_type     = 'inv_inviter_' . strtolower( $suffix );

		bp_update_option( 'bp-member-type-enable-disable', 1 );
		bp_update_option( 'bp-disable-invite-member-type', 0 );

		$this->admin_type_id   = $this->create_member_type( $this->admin_type, array( 'administrator' ) );
		$this->plain_type_id   = $this->create_member_type( $this->plain_type, array( 'none' ) );
		$this->inviter_type_id = $this->create_member_type( $inviter_type, array( 'none' ) );

		$this->inviter_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		bp_set_member_type( $this->inviter_id, $inviter_type );
	}

	public function tear_down() {
		bp_delete_option( 'bp-disable-invite-member-type' );
		parent::tear_down();
	}

	public function test_activation_ignores_stored_type_when_selection_is_disabled() {
		$invitee_id = $this->invite_and_activate( 'inv-off@example.com', $this->admin_type );

		$this->assertFalse( user_can( $invitee_id, 'manage_options' ) );
		$this->assertNotSame( $this->admin_type, bp_get_member_type( $invitee_id ) );
	}

	public function test_activation_ignores_stored_type_outside_inviter_allowlist() {
		bp_update_option( 'bp-disable-invite-member-type', 1 );
		update_post_meta( $this->inviter_type_id, '_bp_member_type_allowed_member_type_invite', array( $this->plain_type_id ) );

		$invitee_id = $this->invite_and_activate( 'inv-outside@example.com', $this->admin_type );

		$this->assertFalse( user_can( $invitee_id, 'manage_options' ) );
		$this->assertNotSame( $this->admin_type, bp_get_member_type( $invitee_id ) );
	}

	public function test_activation_applies_type_the_inviter_may_choose() {
		bp_update_option( 'bp-disable-invite-member-type', 1 );
		update_post_meta( $this->inviter_type_id, '_bp_member_type_allowed_member_type_invite', array( $this->admin_type_id ) );

		$invitee_id = $this->invite_and_activate( 'inv-allowed@example.com', $this->admin_type );

		$this->assertSame( $this->admin_type, bp_get_member_type( $invitee_id ) );
		$this->assertTrue( user_can( $invitee_id, 'manage_options' ) );
	}

	public function test_allowed_type_is_checked_against_inviter_not_current_user() {
		bp_update_option( 'bp-disable-invite-member-type', 1 );
		update_post_meta( $this->inviter_type_id, '_bp_member_type_allowed_member_type_invite', array( $this->plain_type_id ) );

		$invite_id = $this->create_invite( 'inv-viewer@example.com', $this->plain_type );

		// Logged out, as during email activation.
		$this->set_current_user( 0 );
		$this->assertSame( $this->plain_type, bb_get_invite_allowed_member_type( $invite_id ) );
	}

	public function test_allowed_type_helper_rejects_non_invite_posts() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_bp_invitee_member_type', $this->plain_type );

		$this->assertSame( '', bb_get_invite_allowed_member_type( $post_id ) );
	}

	protected function create_member_type( $key, $roles ) {
		$type_id = self::factory()->post->create(
			array(
				'post_type'   => bp_get_member_type_post_type(),
				'post_status' => 'publish',
			)
		);
		update_post_meta( $type_id, '_bp_member_type_key', $key );
		update_post_meta( $type_id, '_bp_member_type_wp_roles', $roles );
		update_post_meta( $type_id, '_bp_member_type_enable_invite', 1 );
		bp_register_member_type( $key );

		return $type_id;
	}

	protected function create_invite( $email, $member_type ) {
		$invite_id = self::factory()->post->create(
			array(
				'post_type'   => bp_get_invite_post_type(),
				'post_status' => 'publish',
				'post_author' => $this->inviter_id,
			)
		);
		update_post_meta( $invite_id, '_bp_invitee_email', $email );
		update_post_meta( $invite_id, '_bp_invitee_status', 0 );
		update_post_meta( $invite_id, '_bp_invitee_member_type', $member_type );

		return $invite_id;
	}

	protected function invite_and_activate( $email, $member_type ) {
		$this->create_invite( $email, $member_type );

		$invitee_id = self::factory()->user->create(
			array(
				'role'       => 'subscriber',
				'user_email' => $email,
			)
		);

		$this->set_current_user( 0 );
		bp_invites_member_invite_activate_user( $invitee_id, '', array() );

		return $invitee_id;
	}
}
