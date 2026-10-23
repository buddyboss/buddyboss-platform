<?php
/**
 * PHPUnit bootstrap for the Two-Factor integration tests.
 *
 * The default bootstrap loads BuddyBoss Platform only. The tests under
 * tests/phpunit/testcases/integrations/two-factor/ need the Two Factor plugin
 * loaded and reported as active before Platform boots, so that
 * bb_two_factor_plugin_is_active() is true and the integration's actions and
 * filters files are included.
 *
 * Usage: tests/phpunit/two-factor.xml points its `bootstrap` attribute here.
 * When the plugin cannot be found the tests skip themselves.
 *
 * Plugin location, first match wins:
 *  1. BB_TWO_FACTOR_TESTS_PLUGIN_FILE environment variable (absolute path).
 *  2. A `two-factor/` folder beside this plugin's folder (a normal site checkout).
 *
 * @since BuddyBoss 3.6.0
 * @package BuddyBoss\Tests
 */

/*
 * includes/define-constants.php defines its constants unconditionally and is
 * required again by bootstrap.php, so the WP tests directory is resolved here
 * the same way without defining anything.
 */
if ( false !== getenv( 'WP_TESTS_DIR' ) ) {
	$_bb_two_factor_tests_wp_dir = getenv( 'WP_TESTS_DIR' );
} elseif ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	$_bb_two_factor_tests_wp_dir = getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit';
} else {
	$_bb_two_factor_tests_wp_dir = dirname( dirname( dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) ) ) . '/tests/phpunit';
}

if ( ! file_exists( $_bb_two_factor_tests_wp_dir . '/includes/functions.php' ) ) {
	die( "The WordPress PHPUnit test suite could not be found.\n" );
}

require_once $_bb_two_factor_tests_wp_dir . '/includes/functions.php';

if ( ! defined( 'BB_TWO_FACTOR_TESTS_BASENAME' ) ) {
	define( 'BB_TWO_FACTOR_TESTS_BASENAME', 'two-factor/two-factor.php' );
}

/**
 * Resolve the Two Factor plugin main file.
 *
 * @since BuddyBoss 3.6.0
 *
 * @return string Absolute path, or an empty string when the plugin is absent.
 */
function _bb_two_factor_tests_plugin_file() {
	$candidates = array();

	$env = getenv( 'BB_TWO_FACTOR_TESTS_PLUGIN_FILE' );
	if ( false !== $env && '' !== $env ) {
		$candidates[] = $env;
	}

	// tests/phpunit/ -> tests/ -> buddyboss-platform/ -> plugins/.
	$candidates[] = dirname( dirname( dirname( __DIR__ ) ) ) . '/' . BB_TWO_FACTOR_TESTS_BASENAME;

	foreach ( $candidates as $candidate ) {
		if ( file_exists( $candidate ) ) {
			return $candidate;
		}
	}

	return '';
}

/**
 * Report the Two Factor plugin as active for this site.
 *
 * Both bb_two_factor_plugin_is_active() and BP_Integration::is_activated() read
 * the `active_plugins` option. A test may stack a later filter to flip it back.
 *
 * @since BuddyBoss 3.6.0
 *
 * @return string[]
 */
function _bb_two_factor_tests_active_plugins() {
	return array( BB_TWO_FACTOR_TESTS_BASENAME );
}

/**
 * Load the Two Factor plugin before Platform boots.
 *
 * If the test install's own plugins directory holds a copy, WordPress loads that
 * one from `active_plugins`; requiring a second copy would redeclare its classes.
 *
 * @since BuddyBoss 3.6.0
 */
function _bb_two_factor_tests_load_plugin() {
	if ( defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/' . BB_TWO_FACTOR_TESTS_BASENAME ) ) {
		return;
	}

	$file = _bb_two_factor_tests_plugin_file();

	if ( '' === $file ) {
		return;
	}

	require_once $file;
}

/**
 * Include the integration's runtime files if the integration did not.
 *
 * BB_Two_Factor_Integration::includes() loads them at bp_include only when the
 * Integration Bridge reports the integration activated, which depends on
 * options a fresh test install does not have. The files are guarded by
 * require_once, so this is a no-op when the real path already ran.
 *
 * @since BuddyBoss 3.6.0
 */
function _bb_two_factor_tests_include_integration() {
	if ( ! class_exists( 'Two_Factor_Core' ) || ! function_exists( 'bb_two_factor_is_active' ) || ! bb_two_factor_is_active() ) {
		return;
	}

	$dir = trailingslashit( buddypress()->plugin_dir ) . 'bb-features/integrations/two-factor/';

	require_once $dir . 'bb-two-factor-actions.php';
	require_once $dir . 'bb-two-factor-filters.php';
}

if ( '' !== _bb_two_factor_tests_plugin_file() ) {
	tests_add_filter( 'pre_option_active_plugins', '_bb_two_factor_tests_active_plugins' );
	tests_add_filter( 'muplugins_loaded', '_bb_two_factor_tests_load_plugin', 9 );
	tests_add_filter( 'bp_include', '_bb_two_factor_tests_include_integration', 99 );
}

require __DIR__ . '/bootstrap.php';

// Last resort in case bp_include already fired before the filter was attached.
_bb_two_factor_tests_include_integration();
