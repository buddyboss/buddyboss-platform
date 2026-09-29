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

	// SSO (F-1): the plugin's own wp_login challenge must stay in place.

	/**
	 * Nothing hooked on sso before wp login.
	 */
	public function test_nothing_hooked_on_sso_before_wp_login() {
		$this->assertFalse( has_action( 'bb_sso_before_wp_login' ) );
	}

	/**
	 * Sso skip functions are gone.
	 */
	public function test_sso_skip_functions_are_gone() {
		$this->assertFalse( function_exists( 'bb_two_factor_skip_sso_challenge' ) );
		$this->assertFalse( function_exists( 'bb_two_factor_capture_sso_session' ) );
		$this->assertFalse( function_exists( 'bb_two_factor_sso_session' ) );
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
	 * Nothing from platform removes the wp login challenge on sso hook.
	 */
	public function test_nothing_from_platform_removes_the_wp_login_challenge_on_sso_hook() {
		do_action( 'bb_sso_before_wp_login', get_userdata( $this->create_member( 'tfa_r2_test_sso' ) ) );

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
