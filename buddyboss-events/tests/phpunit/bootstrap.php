<?php

/**
 * Bootstrap file for the BuddyBoss Events PHPUnit test suite.
 *
 * Loads the WordPress test environment and the bp-events component
 * so all test cases run inside a real WordPress context.
 */

// 1. Load our constants (BP_TESTS_DIR, BP_PLUGIN_DIR, WP_TESTS_DIR).
require dirname( __FILE__ ) . '/includes/define-constants.php';

// 2. Guard: abort with a clear message when the WP test suite is not available.
if ( ! file_exists( WP_TESTS_DIR . '/includes/functions.php' ) ) {
	echo "The WordPress PHPUnit test suite could not be found.\n";
	echo 'Expected path: ' . WP_TESTS_DIR . "/includes/functions.php\n";
	echo "Set the WP_TESTS_DIR environment variable to the correct location.\n";
	exit( 1 );
}

// 3. Load WordPress test suite helpers.
require_once WP_TESTS_DIR . '/includes/functions.php';

// 4. Register a loader that activates the bp-events component.
function _bp_events_load_component() {
	$loader = BP_PLUGIN_DIR . '/src/bp-events/bp-events-loader.php';

	if ( file_exists( $loader ) ) {
		require $loader;
	}
}
tests_add_filter( 'muplugins_loaded', '_bp_events_load_component' );

// 5. Bootstrap WordPress.
require WP_TESTS_DIR . '/includes/bootstrap.php';
