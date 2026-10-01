<?php
/**
 * Two-Factor integration: SSO hook regression and the Security sub-nav.
 *
 * @since BuddyBoss [BBVERSION]
 * @package BuddyBoss\Tests
 */

// Shared base class.
require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/includes/testcase-two-factor.php';

/**
 * SSO hook regression and the Security sub-nav.
 *
 * @group two-factor
 */
class BB_Tests_Two_Factor_Hooks_And_Nav extends BB_Two_Factor_UnitTestCase {

	// Social Login: no second factor at sign-in, but the session is never marked as verified.

	/**
	 * The skip is hooked on the Social Login action.
	 */
	public function test_social_login_skip_is_hooked() {
		$this->assertSame( 10, has_action( 'bb_sso_before_wp_login', 'bb_two_factor_skip_social_login_challenge' ) );
	}

	/**
	 * The round-1 session capture and marker writer stay removed.
	 */
	public function test_session_marker_helpers_are_gone() {
		$this->assertFalse( function_exists( 'bb_two_factor_skip_sso_challenge' ) );
		$this->assertFalse( function_exists( 'bb_two_factor_capture_sso_session' ) );
		$this->assertFalse( function_exists( 'bb_two_factor_sso_session' ) );
	}

	/**
	 * A Social Login sign-in detaches the plugin's challenge for that request.
	 */
	public function test_social_login_detaches_the_wp_login_challenge() {
		self::set_current_user( $this->create_member( 'tfa_r2_test_sso_skip' ) );

		do_action( 'bb_sso_before_wp_login' );

		$this->assertFalse( has_action( 'wp_login', array( 'Two_Factor_Core', 'wp_login' ) ) );
	}

	/**
	 * A Social Login sign-in does not mark the session as having passed two-factor.
	 */
	public function test_social_login_does_not_mark_session_verified() {
		$member = $this->create_member( 'tfa_r2_test_sso_marker' );
		self::set_current_user( $member );
		update_user_meta( $member, Two_Factor_Core::ENABLED_PROVIDERS_USER_META_KEY, array( 'Two_Factor_Email' ) );

		$manager = WP_Session_Tokens::get_instance( $member );
		$token   = $manager->create( time() + HOUR_IN_SECONDS );

		do_action( 'set_logged_in_cookie', '', time() + HOUR_IN_SECONDS, time() + HOUR_IN_SECONDS, $member, 'logged_in', $token );
		do_action( 'bb_sso_before_wp_login' );

		$session = $manager->get( $token );

		$this->assertIsArray( $session );
		$this->assertArrayNotHasKey( 'two-factor-login', $session );
	}

	/**
	 * Returning false from the filter keeps the challenge on social sign-ins.
	 */
	public function test_filter_false_keeps_the_challenge_on_social_login() {
		add_filter( 'bb_two_factor_skip_on_social_login', '__return_false' );

		do_action( 'bb_sso_before_wp_login' );

		$this->assertSame( PHP_INT_MAX, has_action( 'wp_login', array( 'Two_Factor_Core', 'wp_login' ) ) );
	}

	/**
	 * With the feature switched off the plugin is left alone.
	 */
	public function test_feature_off_keeps_the_challenge_on_social_login() {
		add_filter( 'bb_two_factor_is_enabled', '__return_false' );

		do_action( 'bb_sso_before_wp_login' );

		$this->assertSame( PHP_INT_MAX, has_action( 'wp_login', array( 'Two_Factor_Core', 'wp_login' ) ) );
	}

	/**
	 * Sso session capture not on set logged in cookie.
	 */
	public function test_sso_session_capture_not_on_set_logged_in_cookie() {
		$this->assertFalse( has_action( 'set_logged_in_cookie', 'bb_two_factor_capture_sso_session' ) );
	}

	/**
	 * Plugin cookie collector still on set logged in cookie.
	 */
	public function test_plugin_cookie_collector_still_on_set_logged_in_cookie() {
		$this->assertSame( 10, has_action( 'set_logged_in_cookie', array( 'Two_Factor_Core', 'collect_auth_cookie_tokens' ) ) );
	}

	/**
	 * Plugin wp login challenge hooked at php int max.
	 */
	public function test_plugin_wp_login_challenge_hooked_at_php_int_max() {
		$this->assertSame( PHP_INT_MAX, has_action( 'wp_login', array( 'Two_Factor_Core', 'wp_login' ) ) );
	}

	/**
	 * A password sign-in never fires the Social Login action, so its challenge stays.
	 */
	public function test_password_login_keeps_the_challenge() {
		self::set_current_user( $this->create_member( 'tfa_r2_test_password' ) );

		do_action( 'set_logged_in_cookie', '', time() + HOUR_IN_SECONDS, time() + HOUR_IN_SECONDS, get_current_user_id(), 'logged_in', 'token' );

		$this->assertSame( PHP_INT_MAX, has_action( 'wp_login', array( 'Two_Factor_Core', 'wp_login' ) ) );
	}

	// Security sub-nav.

	/**
	 * Find the Security sub-nav item for the displayed member.
	 *
	 * @return object|false
	 */
	protected function get_security_subnav() {
		$items = buddypress()->members->nav->get_secondary(
			array(
				'parent_slug' => bp_get_settings_slug(),
				'slug'        => 'security',
			),
			false
		);

		return $items ? reset( $items ) : false;
	}

	/**
	 * Load a member URL.
	 *
	 * Calling go_to() re-runs init, which re-registers Platform's ReadyLaunch header
	 * block and raises an incorrect-usage notice unrelated to this feature.
	 *
	 * @param string $url URL to load.
	 */
	protected function go_to_member_url( $url ) {
		$this->setExpectedIncorrectUsage( 'WP_Block_Type_Registry::register' );

		$this->go_to( $url );
	}

	/**
	 * Setup nav is hooked on settings setup nav.
	 */
	public function test_setup_nav_is_hooked_on_settings_setup_nav() {
		$this->assertSame( 10, has_action( 'bp_settings_setup_nav', 'bb_two_factor_setup_nav' ) );
	}

	/**
	 * Security subnav on own profile.
	 */
	public function test_security_subnav_on_own_profile() {
		$u = $this->create_member( 'tfa_r2_test_nav_own' );
		self::set_current_user( $u );

		$this->go_to_member_url( bp_core_get_user_domain( $u ) . 'settings/' );

		$item = $this->get_security_subnav();

		$this->assertNotFalse( $item );
		$this->assertSame( 'Security', $item->name );
		$this->assertSame( 15, $item->position );
		$this->assertTrue( $item->user_has_access );
		$this->assertSame( 'bb_two_factor_screen_security', $item->screen_function );
		$this->assertSame( 'http://example.org/members/tfa_r2_test_nav_own/settings/security/', $item->link );
	}

	/**
	 * Security subnav absent or locked for another member.
	 */
	public function test_security_subnav_absent_or_locked_for_another_member() {
		$owner  = $this->create_member( 'tfa_r2_test_nav_owner' );
		$viewer = $this->create_member( 'tfa_r2_test_nav_viewer' );
		self::set_current_user( $viewer );

		$this->go_to_member_url( bp_core_get_user_domain( $owner ) . 'settings/' );

		$item = $this->get_security_subnav();

		$this->assertTrue( false === $item || false === $item->user_has_access );
	}

	/**
	 * Security subnav absent or locked for admin on member.
	 */
	public function test_security_subnav_absent_or_locked_for_admin_on_member() {
		$owner = $this->create_member( 'tfa_r2_test_nav_owner2' );
		$admin = $this->create_member( 'tfa_r2_test_nav_admin', 'administrator' );
		self::set_current_user( $admin );

		$this->go_to_member_url( bp_core_get_user_domain( $owner ) . 'settings/' );

		$item = $this->get_security_subnav();

		$this->assertTrue( false === $item || false === $item->user_has_access );
	}

	/**
	 * Security subnav absent when feature off.
	 */
	public function test_security_subnav_absent_when_feature_off() {
		bp_update_option( 'bb-active-features', array( 'two-factor' => 0 ) );

		$u = $this->create_member( 'tfa_r2_test_nav_feature_off' );
		self::set_current_user( $u );

		$this->go_to_member_url( bp_core_get_user_domain( $u ) . 'settings/' );

		$this->assertFalse( $this->get_security_subnav() );
	}
}
