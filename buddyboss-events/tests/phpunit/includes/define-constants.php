<?php

/**
 * Define constants needed by the BuddyBoss Events test suite.
 */

/**
 * BP_TESTS_DIR — the tests/phpunit/ directory.
 * define-constants.php lives at tests/phpunit/includes/, so two levels up.
 */
if ( ! defined( 'BP_TESTS_DIR' ) ) {
	define( 'BP_TESTS_DIR', dirname( dirname( __FILE__ ) ) );
}

/**
 * BP_PLUGIN_DIR — the plugin root (buddyboss-events/).
 * define-constants.php is at tests/phpunit/includes/, so four levels up.
 */
if ( ! defined( 'BP_PLUGIN_DIR' ) ) {
	define( 'BP_PLUGIN_DIR', dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) );
}

/**
 * WP_TESTS_DIR — path to the WordPress PHPUnit test suite.
 * Honour the WP_TESTS_DIR environment variable; fall back to the standard
 * tmp location used by the WordPress test-suite installer script.
 */
if ( ! defined( 'WP_TESTS_DIR' ) ) {
	define( 'WP_TESTS_DIR', getenv( 'WP_TESTS_DIR' ) ?: '/tmp/wordpress-tests-lib' );
}
