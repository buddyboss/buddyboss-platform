<?php
/**
 * Two-Factor integration: admin-bar entry and Dummy provider filter.
 *
 * @since BuddyBoss 3.6.0
 * @package BuddyBoss\Tests
 */

// Shared base class.
require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/includes/testcase-two-factor.php';

/**
 * Admin-bar Security entry and Dummy provider filter.
 *
 * @group two-factor
 */
class BB_Tests_Two_Factor_Filters extends BB_Two_Factor_UnitTestCase {

	/**
	 * Restore the screen after admin-context tests.
	 */
	public function tearDown(): void {
		set_current_screen( 'front' );

		parent::tearDown();
	}

	/**
	 * Admin nav filter is hooked at 15.
	 */
	public function test_admin_nav_filter_is_hooked_at_15() {
		$this->assertSame( 15, has_filter( 'bp_settings_admin_nav', 'bb_two_factor_settings_admin_nav' ) );
	}

	/**
	 * F-2: BuddyBoss Theme passes '' when a Profile Dropdown menu is assigned.
	 */
	public function test_admin_nav_passes_empty_string_through() {
		$u = $this->create_member( 'tfa_r2_test_nav' );
		self::set_current_user( $u );

		$this->assertSame( '', bb_two_factor_settings_admin_nav( '' ) );
	}

	/**
	 * F-2: the real theme callback chain, not only the function in isolation.
	 */
	public function test_admin_nav_filter_chain_with_theme_empty_string_does_not_fatal() {
		$u = $this->create_member( 'tfa_r2_test_nav_chain' );
		self::set_current_user( $u );

		add_filter( 'bp_settings_admin_nav', '__return_empty_string', 10 );

		$this->assertSame( '', apply_filters( 'bp_settings_admin_nav', array() ) );
	}

	/**
	 * Admin nav appends security for logged in member.
	 */
	public function test_admin_nav_appends_security_for_logged_in_member() {
		$u = $this->create_member( 'tfa_r2_test_nav_member' );
		self::set_current_user( $u );

		$general = array(
			'parent' => 'my-account-settings',
			'id'     => 'my-account-settings-general',
			'title'  => 'Login Information',
		);

		$nav = bb_two_factor_settings_admin_nav( array( $general ) );

		$this->assertCount( 2, $nav );
		$this->assertSame( $general, $nav[0] );
		$this->assertSame(
			array(
				'parent'   => 'my-account-settings',
				'id'       => 'my-account-settings-security',
				'title'    => 'Security',
				'href'     => 'http://example.org/members/tfa_r2_test_nav_member/settings/security/',
				'position' => 15,
			),
			$nav[1]
		);
	}

	/**
	 * Admin nav unchanged for logged out visitor.
	 */
	public function test_admin_nav_unchanged_for_logged_out_visitor() {
		self::set_current_user( 0 );

		$input = array( array( 'id' => 'my-account-settings-general' ) );

		$this->assertSame( $input, bb_two_factor_settings_admin_nav( $input ) );
	}

	/**
	 * Admin nav unchanged when feature off.
	 */
	public function test_admin_nav_unchanged_when_feature_off() {
		$u = $this->create_member( 'tfa_r2_test_nav_off' );
		self::set_current_user( $u );
		bp_update_option( 'bb-active-features', array( 'two-factor' => 0 ) );

		$input = array( array( 'id' => 'my-account-settings-general' ) );

		$this->assertSame( $input, bb_two_factor_settings_admin_nav( $input ) );
	}

	/**
	 * Dummy provider filter is hooked.
	 */
	public function test_dummy_provider_filter_is_hooked() {
		$this->assertSame( 10, has_filter( 'two_factor_providers_for_user', 'bb_two_factor_hide_dummy_provider' ) );
	}

	/**
	 * Dummy provider removed off admin and others kept.
	 */
	public function test_dummy_provider_removed_off_admin_and_others_kept() {
		$providers = array(
			'Two_Factor_Email'        => 'email',
			'Two_Factor_Dummy'        => 'dummy',
			'Two_Factor_Backup_Codes' => 'backup',
		);

		$this->assertSame(
			array(
				'Two_Factor_Email'        => 'email',
				'Two_Factor_Backup_Codes' => 'backup',
			),
			bb_two_factor_hide_dummy_provider( $providers )
		);
	}

	/**
	 * Dummy provider kept on real admin screen.
	 */
	public function test_dummy_provider_kept_on_real_admin_screen() {
		set_current_screen( 'profile' );

		$providers = array(
			'Two_Factor_Email' => 'email',
			'Two_Factor_Dummy' => 'dummy',
		);

		$this->assertTrue( is_admin() );
		$this->assertSame( $providers, bb_two_factor_hide_dummy_provider( $providers ) );
	}

	/**
	 * Dummy provider removed on admin ajax.
	 */
	public function test_dummy_provider_removed_on_admin_ajax() {
		set_current_screen( 'profile' );
		add_filter( 'wp_doing_ajax', '__return_true' );

		$providers = array(
			'Two_Factor_Email' => 'email',
			'Two_Factor_Dummy' => 'dummy',
		);

		$this->assertSame( array( 'Two_Factor_Email' => 'email' ), bb_two_factor_hide_dummy_provider( $providers ) );
	}

	/**
	 * Dummy provider filter passes non array through.
	 */
	public function test_dummy_provider_filter_passes_non_array_through() {
		$this->assertSame( 'not-an-array', bb_two_factor_hide_dummy_provider( 'not-an-array' ) );
		$this->assertNull( bb_two_factor_hide_dummy_provider( null ) );
	}
}
