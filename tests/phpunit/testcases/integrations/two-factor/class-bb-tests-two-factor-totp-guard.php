<?php
/**
 * Two-Factor integration: the authenticator "Verify" REST call and the recovery rule.
 *
 * @since BuddyBoss 3.6.0
 * @package BuddyBoss\Tests
 */

// Shared base class.
require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/includes/testcase-two-factor.php';

/**
 * Guard on POST two-factor/1.0/totp (V-1).
 *
 * The plugin's setup script sends `enable_provider: true`, which would turn the
 * authenticator on outside the Security tab save. These tests go through the
 * real REST server so the filter, the plugin's permission check and its handler
 * all run in the same order as a browser request.
 *
 * @group two-factor
 */
class BB_Tests_Two_Factor_Totp_Guard extends BB_Two_Factor_UnitTestCase {

	/**
	 * Member under test.
	 *
	 * @var int
	 */
	protected $member;

	/**
	 * Create and log in the member under test, and register only the plugin's authenticator routes.
	 *
	 * A fresh REST server is booted with nothing but those routes on rest_api_init;
	 * the full BuddyBoss route set is not needed here. Hooks are restored by the
	 * test framework after each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->member = $this->create_member( 'tfa_r2_test_guard' );
		self::set_current_user( $this->member );

		remove_all_actions( 'rest_api_init' );
		add_action( 'rest_api_init', array( Two_Factor_Totp::get_instance(), 'register_rest_routes' ) );

		$GLOBALS['wp_rest_server'] = null;
		rest_get_server();
	}

	/**
	 * Drop the stripped-down REST server.
	 */
	public function tearDown(): void {
		$GLOBALS['wp_rest_server'] = null;

		parent::tearDown();
	}

	/**
	 * Send the request the plugin's setup script sends on "Verify".
	 *
	 * @param int $user_id Account being set up.
	 * @return WP_REST_Response
	 */
	protected function verify( $user_id ) {
		$key     = Two_Factor_Totp::generate_key();
		$request = new WP_REST_Request( 'POST', '/two-factor/1.0/totp' );
		$request->set_body_params(
			array(
				'user_id'         => $user_id,
				'key'             => $key,
				'code'            => Two_Factor_Totp::calc_totp( $key ),
				'enable_provider' => true,
			)
		);

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Enabled provider keys straight from user meta.
	 *
	 * @param int $user_id User.
	 * @return array
	 */
	protected function stored_enabled( $user_id ) {
		return array_values( (array) get_user_meta( $user_id, Two_Factor_Core::ENABLED_PROVIDERS_USER_META_KEY, true ) );
	}

	/**
	 * Site-level provider list without Recovery Codes.
	 *
	 * @return string[]
	 */
	public static function site_providers_without_backup_codes() {
		return array( 'Two_Factor_Email', 'Two_Factor_Totp' );
	}

	/**
	 * The guard is registered on the REST pre-dispatch filter.
	 */
	public function test_guard_is_hooked() {
		$this->assertSame( 10, has_filter( 'rest_request_before_callbacks', 'bb_two_factor_guard_totp_enable' ) );
	}

	/**
	 * No recovery method: Verify stores the secret but leaves the authenticator off.
	 */
	public function test_verify_without_recovery_method_keeps_totp_off() {
		$response = $this->verify( $this->member );

		$this->assertSame( 200, $response->get_status() );
		$this->assertNotEmpty( get_user_meta( $this->member, Two_Factor_Totp::SECRET_META_KEY, true ) );
		$this->assertSame( array(), array_filter( $this->stored_enabled( $this->member ) ) );
	}

	/**
	 * Control: without the guard the same request turns the authenticator on.
	 */
	public function test_control_without_guard_verify_enables_totp() {
		remove_filter( 'rest_request_before_callbacks', 'bb_two_factor_guard_totp_enable', 10 );

		$response = $this->verify( $this->member );

		add_filter( 'rest_request_before_callbacks', 'bb_two_factor_guard_totp_enable', 10, 3 );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( 'Two_Factor_Totp' ), $this->stored_enabled( $this->member ) );
	}

	/**
	 * Correct order: generate codes, Verify, then Save turns both on through the rule.
	 */
	public function test_generate_codes_then_verify_leaves_totp_for_save() {
		Two_Factor_Backup_Codes::get_instance()->generate_codes( get_userdata( $this->member ) );

		$this->verify( $this->member );

		$this->assertSame( array(), array_filter( $this->stored_enabled( $this->member ) ) );
		$this->assertTrue( bb_two_factor_check_recovery_method( $this->member, array( 'Two_Factor_Totp', 'Two_Factor_Backup_Codes' ) ) );
	}

	/**
	 * Recovery Codes already enabled and generated: the request is left as sent.
	 */
	public function test_guard_leaves_request_when_recovery_method_exists() {
		Two_Factor_Backup_Codes::get_instance()->generate_codes( get_userdata( $this->member ) );
		update_user_meta( $this->member, Two_Factor_Core::ENABLED_PROVIDERS_USER_META_KEY, array( 'Two_Factor_Backup_Codes' ) );

		$request = new WP_REST_Request( 'POST', '/two-factor/1.0/totp' );
		$request->set_param( 'user_id', $this->member );
		$request->set_param( 'enable_provider', true );

		bb_two_factor_guard_totp_enable( null, array(), $request );

		$this->assertTrue( $request->get_param( 'enable_provider' ) );
	}

	/**
	 * An admin setting up another user is left to the plugin.
	 */
	public function test_admin_on_another_user_is_not_guarded() {
		$admin = $this->create_member( 'tfa_r2_test_guard_admin', 'administrator' );
		self::set_current_user( $admin );

		$response = $this->verify( $this->member );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( 'Two_Factor_Totp' ), $this->stored_enabled( $this->member ) );
	}

	/**
	 * Rule switched off: the plugin's behaviour is unchanged.
	 */
	public function test_rule_filtered_off_lets_verify_enable_totp() {
		add_filter( 'bb_two_factor_require_recovery_method', '__return_false' );

		$this->verify( $this->member );

		$this->assertSame( array( 'Two_Factor_Totp' ), $this->stored_enabled( $this->member ) );
	}

	/**
	 * Recovery Codes not offered by the site (V-2): Verify turns the authenticator on.
	 */
	public function test_verify_enables_totp_when_site_does_not_offer_backup_codes() {
		add_filter( 'pre_option_two_factor_enabled_providers', array( __CLASS__, 'site_providers_without_backup_codes' ) );

		$this->verify( $this->member );

		$this->assertSame( array( 'Two_Factor_Totp' ), $this->stored_enabled( $this->member ) );
	}

	/**
	 * Other methods and requests that do not ask to enable are untouched.
	 */
	public function test_guard_ignores_other_requests() {
		$delete = new WP_REST_Request( 'DELETE', '/two-factor/1.0/totp' );
		$delete->set_param( 'user_id', $this->member );
		$delete->set_param( 'enable_provider', true );
		bb_two_factor_guard_totp_enable( null, array(), $delete );

		$codes = new WP_REST_Request( 'POST', '/two-factor/1.0/generate-backup-codes' );
		$codes->set_param( 'user_id', $this->member );
		$codes->set_param( 'enable_provider', true );
		bb_two_factor_guard_totp_enable( null, array(), $codes );

		$this->assertTrue( $delete->get_param( 'enable_provider' ) );
		$this->assertTrue( $codes->get_param( 'enable_provider' ) );
	}

	/**
	 * A response already decided by an earlier filter is passed through unchanged.
	 */
	public function test_guard_passes_through_an_existing_response() {
		$error   = new WP_Error( 'earlier', 'Decided earlier.' );
		$request = new WP_REST_Request( 'POST', '/two-factor/1.0/totp' );
		$request->set_param( 'user_id', $this->member );
		$request->set_param( 'enable_provider', true );

		$this->assertSame( $error, bb_two_factor_guard_totp_enable( $error, array(), $request ) );
		$this->assertTrue( $request->get_param( 'enable_provider' ) );
	}
}
