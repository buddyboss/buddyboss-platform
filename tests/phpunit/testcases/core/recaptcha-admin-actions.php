<?php
/**
 * reCAPTCHA tests for admin-initiated actions.
 *
 * Covers PROD-10394: admin "Activate" on Users → Pending Signups and admin
 * "Send password reset" must not require a token the admin screens never
 * render, while front-end, anonymous admin-ajax and self-service requests
 * stay fail-closed.
 *
 * @group core
 * @group recaptcha
 */
class BB_Tests_Core_Recaptcha_Admin_Actions extends BP_UnitTestCase {

	/**
	 * Option value before the test.
	 *
	 * @var mixed
	 */
	protected $old_option;

	/**
	 * A connected v2 configuration with activation and lost password enabled.
	 *
	 * @var array
	 */
	protected $settings = array(
		'recaptcha_version' => 'recaptcha_v2',
		'v2_option'         => 'v2_checkbox',
		'site_key'          => 'test-site-key-10394',
		'secret_key'        => 'test-secret-key-10394',
		'connection_status' => 'connected',
		'enabled_for'       => array(
			'bb_lost_password' => 1,
			'bb_activate'      => 1,
		),
	);

	public function set_up() {
		parent::set_up();
		$this->old_option = bp_get_option( 'bb_recaptcha', array() );
		bp_update_option( 'bb_recaptcha', $this->settings );
		unset( $_POST['g-recaptcha-response'] );
	}

	public function tear_down() {
		bp_update_option( 'bb_recaptcha', $this->old_option );
		remove_filter( 'wp_doing_ajax', '__return_true' );
		set_current_screen( 'front' );
		parent::tear_down();
	}

	/**
	 * Run the lost password validator on a fresh error object.
	 *
	 * @param WP_User|false $user_data Account the reset is requested for.
	 * @return WP_Error
	 */
	protected function validate_lost_password( $user_data ) {
		return bb_recaptcha_validate_lost_password( new WP_Error(), $user_data );
	}

	public function test_activate_is_verified_on_the_front_end() {
		$result = bb_recaptcha_validate_activate( true );

		$this->assertWPError( $result );
		$this->assertSame( 'bb_recaptcha_token_missing', $result->get_error_code() );
	}

	public function test_activate_skips_verification_on_the_pending_signups_screen() {
		set_current_screen( 'users_page_bp-signups' );

		$this->assertTrue( bb_recaptcha_validate_activate( true ) );
	}

	public function test_activate_is_verified_on_admin_ajax() {
		set_current_screen( 'users_page_bp-signups' );
		add_filter( 'wp_doing_ajax', '__return_true' );

		$this->assertWPError( bb_recaptcha_validate_activate( true ) );
	}

	/**
	 * Create an administrator who can edit other users, on single site and multisite.
	 *
	 * @return int
	 */
	protected function create_admin() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		if ( is_multisite() ) {
			grant_super_admin( $admin );
		}

		return $admin;
	}

	public function test_lost_password_skips_verification_for_an_admin_resetting_another_user() {
		$admin  = $this->create_admin();
		$member = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		self::set_current_user( $admin );
		set_current_screen( 'users' );

		$errors = $this->validate_lost_password( get_userdata( $member ) );

		$this->assertFalse( $errors->has_errors() );
	}

	public function test_lost_password_is_verified_for_an_admin_on_the_front_end_form() {
		$admin  = $this->create_admin();
		$member = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		self::set_current_user( $admin );

		$errors = $this->validate_lost_password( get_userdata( $member ) );

		$this->assertContains( 'bb_recaptcha', $errors->get_error_codes() );
	}

	public function test_lost_password_is_verified_for_an_admin_resetting_their_own_account() {
		$admin = $this->create_admin();
		self::set_current_user( $admin );
		set_current_screen( 'users' );

		$errors = $this->validate_lost_password( get_userdata( $admin ) );

		$this->assertContains( 'bb_recaptcha', $errors->get_error_codes() );
	}

	public function test_lost_password_is_verified_for_a_member_without_edit_users() {
		$member = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$other  = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		self::set_current_user( $member );
		set_current_screen( 'profile' );

		$errors = $this->validate_lost_password( get_userdata( $other ) );

		$this->assertContains( 'bb_recaptcha', $errors->get_error_codes() );
	}

	public function test_lost_password_is_verified_for_a_logged_out_request() {
		$member = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		self::set_current_user( 0 );
		set_current_screen( 'users' );
		add_filter( 'wp_doing_ajax', '__return_true' );

		$errors = $this->validate_lost_password( get_userdata( $member ) );

		$this->assertContains( 'bb_recaptcha', $errors->get_error_codes() );
	}

	public function test_lost_password_is_verified_for_an_unknown_account() {
		$admin = $this->create_admin();
		self::set_current_user( $admin );
		set_current_screen( 'users' );

		$errors = $this->validate_lost_password( false );

		$this->assertContains( 'bb_recaptcha', $errors->get_error_codes() );
	}

	public function test_lost_password_hook_receives_the_user_data() {
		global $wp_filter;

		$callbacks = $wp_filter['lostpassword_post']->callbacks[10];

		$this->assertSame( 2, $callbacks['bb_recaptcha_validate_lost_password']['accepted_args'] );
	}
}
