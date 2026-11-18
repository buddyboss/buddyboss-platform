<?php
/**
 * Two-Factor integration: load gate and feature toggle.
 *
 * @since BuddyBoss 3.6.0
 * @package BuddyBoss\Tests
 */

// Shared base class.
require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/includes/testcase-two-factor.php';

/**
 * Load gate, version floor and feature toggle.
 *
 * @group two-factor
 */
class BB_Tests_Two_Factor_Gate extends BB_Two_Factor_UnitTestCase {

	/**
	 * Raise the supported floor above any real release.
	 *
	 * @return string
	 */
	public static function min_version_high() {
		return '99.0.0';
	}

	/**
	 * Try to lower the floor below the built-in 0.16.0.
	 *
	 * @return string
	 */
	public static function min_version_low() {
		return '0.15.0';
	}

	/**
	 * Raise the floor to a real later release.
	 *
	 * @return string
	 */
	public static function min_version_raised() {
		return '0.18.0';
	}

	/**
	 * Plugin is active when listed in active plugins.
	 */
	public function test_plugin_is_active_when_listed_in_active_plugins() {
		$this->assertTrue( bb_two_factor_plugin_is_active() );
	}

	/**
	 * Plugin is not active when missing from active plugins.
	 */
	public function test_plugin_is_not_active_when_missing_from_active_plugins() {
		add_filter( 'pre_option_active_plugins', array( __CLASS__, 'active_plugins_without_two_factor' ), 20 );

		$this->assertFalse( bb_two_factor_plugin_is_active() );
		$this->assertFalse( bb_two_factor_is_supported() );
		$this->assertFalse( bb_two_factor_is_active() );
	}

	/**
	 * Plugin version reports loaded release.
	 */
	public function test_plugin_version_reports_loaded_release() {
		$this->assertSame( '0.17.0', bb_two_factor_plugin_version() );
	}

	/**
	 * Is supported true for loaded release.
	 */
	public function test_is_supported_true_for_loaded_release() {
		$this->assertTrue( bb_two_factor_is_supported() );
	}

	/**
	 * Is supported false when min version raised above loaded release.
	 */
	public function test_is_supported_false_when_min_version_raised_above_loaded_release() {
		add_filter( 'bb_two_factor_min_plugin_version', array( __CLASS__, 'min_version_high' ) );

		$this->assertFalse( bb_two_factor_is_supported() );
		$this->assertFalse( bb_two_factor_is_active() );
	}

	/**
	 * Min plugin version default is 0 16 0.
	 */
	public function test_min_plugin_version_default_is_0_16_0() {
		$this->assertSame( '0.16.0', bb_two_factor_min_plugin_version() );
	}

	/**
	 * L-1: the filter may only raise the floor.
	 */
	public function test_min_plugin_version_ignores_filter_below_floor() {
		add_filter( 'bb_two_factor_min_plugin_version', array( __CLASS__, 'min_version_low' ) );

		$this->assertSame( '0.16.0', bb_two_factor_min_plugin_version() );
	}

	/**
	 * Min plugin version ignores empty filter.
	 */
	public function test_min_plugin_version_ignores_empty_filter() {
		add_filter( 'bb_two_factor_min_plugin_version', '__return_empty_string' );

		$this->assertSame( '0.16.0', bb_two_factor_min_plugin_version() );
	}

	/**
	 * Min plugin version honours filter above floor.
	 */
	public function test_min_plugin_version_honours_filter_above_floor() {
		add_filter( 'bb_two_factor_min_plugin_version', array( __CLASS__, 'min_version_raised' ) );

		$this->assertSame( '0.18.0', bb_two_factor_min_plugin_version() );
	}

	/**
	 * L-1: the feature card's availability follows bb_two_factor_is_supported().
	 */
	public function test_feature_card_availability_callback_is_version_aware() {
		$feature = bb_feature_registry()->bb_get_feature( 'two-factor' );

		if ( empty( $feature ) ) {
			$this->markTestSkipped( 'The two-factor feature is not registered in this bootstrap.' );
		}

		$this->assertSame( 'bb_two_factor_is_supported', $feature['is_available_callback'] );
	}

	/**
	 * Feature is on when option key absent.
	 */
	public function test_feature_is_on_when_option_key_absent() {
		bp_update_option( 'bb-active-features', array( 'reactions' => 1 ) );

		$this->assertTrue( bb_two_factor_feature_is_on() );
	}

	/**
	 * Feature is on when option missing.
	 */
	public function test_feature_is_on_when_option_missing() {
		bp_delete_option( 'bb-active-features' );

		$this->assertTrue( bb_two_factor_feature_is_on() );
	}

	/**
	 * Feature is off when option key is zero.
	 */
	public function test_feature_is_off_when_option_key_is_zero() {
		bp_update_option( 'bb-active-features', array( 'two-factor' => 0 ) );

		$this->assertFalse( bb_two_factor_feature_is_on() );
		$this->assertFalse( bb_two_factor_is_active() );
	}

	/**
	 * Feature is on when option key is one.
	 */
	public function test_feature_is_on_when_option_key_is_one() {
		bp_update_option( 'bb-active-features', array( 'two-factor' => 1 ) );

		$this->assertTrue( bb_two_factor_feature_is_on() );
		$this->assertTrue( bb_two_factor_is_active() );
	}

	/**
	 * Is enabled filter turns feature off.
	 */
	public function test_is_enabled_filter_turns_feature_off() {
		bp_delete_option( 'bb-active-features' );
		add_filter( 'bb_two_factor_is_enabled', '__return_false' );

		$this->assertFalse( bb_two_factor_feature_is_on() );
	}

	/**
	 * Is enabled filter turns feature on.
	 */
	public function test_is_enabled_filter_turns_feature_on() {
		bp_update_option( 'bb-active-features', array( 'two-factor' => 0 ) );
		add_filter( 'bb_two_factor_is_enabled', '__return_true' );

		$this->assertTrue( bb_two_factor_feature_is_on() );
	}
}
