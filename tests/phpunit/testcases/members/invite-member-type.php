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
		bp_delete_option( 'bp-member-type-default-on-registration' );
		bp_delete_option( 'allow-custom-registration' );
		$GLOBALS['post'] = null;
		$_GET            = array();
		$_POST           = array();
		$_REQUEST        = array();
		remove_filter( 'invite_anyone_is_accept_invitation_page', '__return_true' );
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ), 1 );
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

	public function test_allowed_type_helper_rejects_invite_without_author() {
		bp_update_option( 'bp-disable-invite-member-type', 1 );
		update_post_meta( $this->inviter_type_id, '_bp_member_type_allowed_member_type_invite', array( $this->admin_type_id ) );

		$invite_id = $this->create_invite( 'inv-orphan@example.com', $this->admin_type );
		wp_update_post(
			array(
				'ID'          => $invite_id,
				'post_author' => 0,
			)
		);

		// The current user may choose the type, but they are not the inviter.
		$this->set_current_user( $this->inviter_id );
		$this->assertSame( '', bb_get_invite_allowed_member_type( $invite_id ) );
	}

	public function test_allowed_type_helper_rejects_non_invite_posts() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_bp_invitee_member_type', $this->plain_type );

		$this->assertSame( '', bb_get_invite_allowed_member_type( $post_id ) );
	}

	public function test_default_type_assignment_ignores_disallowed_stored_type() {
		bp_update_option( 'bp-member-type-default-on-registration', $this->plain_type );

		$invitee_id = $this->create_invitee_with_global_invite( 'inv-default@example.com' );
		bp_assign_default_member_type_to_activate_user( $invitee_id, '', array() );

		$this->assertFalse( user_can( $invitee_id, 'manage_options' ) );
		$this->assertSame( $this->plain_type, bp_get_member_type( $invitee_id ) );
	}

	public function test_default_type_assignment_applies_allowed_stored_type() {
		bp_update_option( 'bp-member-type-default-on-registration', $this->plain_type );
		$this->allow_admin_type();

		$invitee_id = $this->create_invitee_with_global_invite( 'inv-default-ok@example.com' );
		bp_assign_default_member_type_to_activate_user( $invitee_id, '', array() );

		$this->assertSame( $this->admin_type, bp_get_member_type( $invitee_id ) );
		$this->assertTrue( user_can( $invitee_id, 'manage_options' ) );
	}

	public function test_admin_default_type_assignment_ignores_disallowed_stored_type() {
		bp_update_option( 'bp-member-type-default-on-registration', $this->plain_type );

		$invitee_id = $this->create_invitee_with_global_invite( 'inv-admin-default@example.com' );
		bp_assign_default_member_type_to_activate_user_on_admin( $invitee_id );

		$this->assertFalse( user_can( $invitee_id, 'manage_options' ) );
		$this->assertNotSame( $this->admin_type, bp_get_member_type( $invitee_id ) );
	}

	public function test_admin_default_type_assignment_applies_allowed_stored_type() {
		bp_update_option( 'bp-member-type-default-on-registration', $this->plain_type );
		$this->allow_admin_type();

		$invitee_id = $this->create_invitee_with_global_invite( 'inv-admin-default-ok@example.com' );
		bp_assign_default_member_type_to_activate_user_on_admin( $invitee_id );

		$this->assertTrue( user_can( $invitee_id, 'manage_options' ) );
	}

	public function test_custom_registration_ignores_disallowed_stored_type() {
		bp_update_option( 'allow-custom-registration', 1 );

		$invitee_id = $this->create_invitee( 'inv-custom@example.com' );
		bp_invites_member_invite_mark_register_user( $invitee_id );

		$this->assertFalse( user_can( $invitee_id, 'manage_options' ) );
		$this->assertNotSame( $this->admin_type, bp_get_member_type( $invitee_id ) );
	}

	public function test_custom_registration_applies_allowed_stored_type() {
		bp_update_option( 'allow-custom-registration', 1 );
		$this->allow_admin_type();

		$invitee_id = $this->create_invitee( 'inv-custom-ok@example.com' );
		bp_invites_member_invite_mark_register_user( $invitee_id );

		$this->assertTrue( user_can( $invitee_id, 'manage_options' ) );
	}

	public function test_signup_prefill_ignores_disallowed_stored_type() {
		$this->assertStringNotContainsString( 'field_type_membertypes', $this->render_invite_register_message( 'inv-prefill@example.com' ) );
	}

	public function test_signup_prefill_uses_allowed_stored_type() {
		$this->allow_admin_type();

		$this->assertStringContainsString( 'field_type_membertypes', $this->render_invite_register_message( 'inv-prefill-ok@example.com' ) );
	}

	public function test_readylaunch_invite_drops_type_inviter_may_not_choose() {
		$response = $this->send_readylaunch_invite( 'inv-rl@example.com' );

		$this->assertTrue( $response['success'] );
		$this->assertSame( '', get_post_meta( $this->get_invite_id( 'inv-rl@example.com' ), '_bp_invitee_member_type', true ) );
	}

	public function test_readylaunch_invite_keeps_type_inviter_may_choose() {
		$this->allow_admin_type();

		$response = $this->send_readylaunch_invite( 'inv-rl-ok@example.com' );

		$this->assertTrue( $response['success'] );
		$this->assertSame( $this->admin_type, get_post_meta( $this->get_invite_id( 'inv-rl-ok@example.com' ), '_bp_invitee_member_type', true ) );
	}

	public function test_readylaunch_invite_requires_nonce() {
		$response = $this->send_readylaunch_invite( 'inv-rl-nonce@example.com', false );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 0, $this->get_invite_id( 'inv-rl-nonce@example.com' ) );
	}

	/**
	 * Route wp_die() to a throwing handler so AJAX callbacks can be run in process.
	 *
	 * @return array
	 */
	public function get_die_handler() {
		return array( $this, 'die_handler' );
	}

	/**
	 * Stop the AJAX callback without exiting the process.
	 *
	 * @param string|WP_Error $message wp_die() message.
	 *
	 * @throws WPAjaxDieContinueException Always.
	 */
	public function die_handler( $message ) {
		throw new WPAjaxDieContinueException( is_scalar( $message ) ? (string) $message : '' );
	}

	protected function allow_admin_type() {
		bp_update_option( 'bp-disable-invite-member-type', 1 );
		update_post_meta( $this->inviter_type_id, '_bp_member_type_allowed_member_type_invite', array( $this->admin_type_id ) );
	}

	protected function create_invitee( $email ) {
		$this->create_invite( $email, $this->admin_type );

		$this->set_current_user( 0 );

		return self::factory()->user->create(
			array(
				'role'       => 'subscriber',
				'user_email' => $email,
			)
		);
	}

	/**
	 * These paths read the invite through the global post, which the invites
	 * activation loop leaves on the last invite it visited.
	 */
	protected function create_invitee_with_global_invite( $email ) {
		$invitee_id      = $this->create_invitee( $email );
		$GLOBALS['post'] = get_post( $this->get_invite_id( $email ) );

		return $invitee_id;
	}

	protected function render_invite_register_message( $email ) {
		$this->create_invite( $email, $this->admin_type );
		$this->set_current_user( 0 );

		add_filter( 'invite_anyone_is_accept_invitation_page', '__return_true' );
		$_GET = array(
			'bp-invites' => 'accept-member-invitation',
			'email'      => rawurlencode( $email ),
			'inviter'    => base64_encode( (string) $this->inviter_id ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Same encoding as the invite link.
		);

		$signup             = isset( buddypress()->signup ) ? buddypress()->signup : null;
		buddypress()->signup = (object) array( 'step' => 'request-details' );

		ob_start();
		bp_invites_member_invite_register_screen_message();
		$output = ob_get_clean();

		buddypress()->signup = $signup;

		return $output;
	}

	protected function send_readylaunch_invite( $email, $with_nonce = true ) {
		if ( ! class_exists( 'BB_Readylaunch' ) ) {
			$this->markTestSkipped( 'ReadyLaunch is not loaded.' );
		}

		$this->set_current_user( $this->inviter_id );

		$nonce = $with_nonce ? wp_create_nonce( 'bb_rl_invite_form_action' ) : '';

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST                     = array(
			'bb_rl_invite_form_nonce' => $nonce,
			'bb-rl-invite-name'       => 'Invitee',
			'bb-rl-invite-email'      => $email,
			'bb-rl-invite-type'       => $this->admin_type,
		);
		$_REQUEST                  = $_POST;

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ), 1 );

		$error_level = error_reporting();
		error_reporting( $error_level & ~E_WARNING );

		ob_start();
		try {
			BB_Readylaunch::instance()->bb_rl_invite_form_callback();
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected: the callback ended via wp_send_json_*.
		} catch ( WPAjaxDieStopException $e ) {
			// Expected: the callback ended via wp_send_json_*.
		}
		$output = ob_get_clean();

		error_reporting( $error_level );
		unset( $_SERVER['REQUEST_METHOD'] );

		return json_decode( $output, true );
	}

	protected function get_invite_id( $email ) {
		$invite_ids = get_posts(
			array(
				'post_type'  => bp_get_invite_post_type(),
				'meta_key'   => '_bp_invitee_email',
				'meta_value' => $email,
				'fields'     => 'ids',
			)
		);

		return $invite_ids ? (int) $invite_ids[0] : 0;
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
