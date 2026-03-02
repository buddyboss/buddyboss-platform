<?php

require( dirname( __FILE__ ) . '/includes/define-constants.php' );

if ( ! file_exists( WP_TESTS_DIR . '/includes/functions.php' ) ) {
	die( "The WordPress PHPUnit test suite could not be found.\n" );
}

require_once WP_TESTS_DIR . '/includes/functions.php';

function _install_and_load_buddypress() {
	$active_plugins = get_option( 'active_plugins' ) ?: [];
	$plugin_dir = dirname( dirname( dirname( BP_TESTS_DIR ) ) );

	// activate learndash
	if ( getenv( 'TEST_LEARNDASH' ) && file_exists( $plugin_dir . '/sfwd-lms/sfwd_lms.php' ) ) {
		update_option('active_plugins', bp_parse_args( [
			'sfwd-lms/sfwd_lms.php'
		] , $active_plugins ) );
	}

	require BP_TESTS_DIR . '/includes/loader.php';
}
tests_add_filter( 'muplugins_loaded', '_install_and_load_buddypress' );

require WP_TESTS_DIR . '/includes/bootstrap.php';

// Load the BP-specific testing tools
require BP_TESTS_DIR . '/includes/testcase.php';
require BP_TESTS_DIR . '/includes/testcase-emails.php';
