<?php
/**
 * Base test case for the Two-Factor integration.
 *
 * @since BuddyBoss [BBVERSION]
 * @package BuddyBoss\Tests
 */

/**
 * Skips when the Two Factor plugin or the integration's runtime files are not
 * loaded, which is the case under the default bootstrap. Run the suite through
 * tests/phpunit/two-factor.xml, which uses bootstrap-two-factor.php.
 *
 * @since BuddyBoss [BBVERSION]
 */
abstract class BB_Two_Factor_UnitTestCase extends BP_UnitTestCase {

	/**
	 * Skip unless the plugin and the integration are loaded.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'Two_Factor_Core' ) || ! function_exists( 'bb_two_factor_settings_admin_nav' ) ) {
			$this->markTestSkipped( 'The Two Factor plugin is not loaded. Run with tests/phpunit/two-factor.xml.' );
		}
	}

	/**
	 * Clean request globals the integration reads.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function tearDown(): void {
		unset(
			$_POST['_two_factor_enabled_providers'],
			$_REQUEST['redirect_to']
		);

		parent::tearDown();
	}

	/**
	 * Create a member with a fixed login so URLs can be asserted literally.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $login User login, also the nicename.
	 * @param string $role  Role.
	 * @return int User ID.
	 */
	protected function create_member( $login, $role = 'subscriber' ) {
		return self::factory()->user->create(
			array(
				'user_login'    => $login,
				'user_nicename' => $login,
				'user_email'    => $login . '@example.org',
				'role'          => $role,
			)
		);
	}

	/**
	 * Report a plugin list without Two Factor in it.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string[]
	 */
	public static function active_plugins_without_two_factor() {
		return array( 'akismet/akismet.php' );
	}
}
