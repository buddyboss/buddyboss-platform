<?php
/**
 * BuddyBoss Core Loader.
 *
 * Core contains the commonly used functions, classes, and APIs.
 *
 * @package BuddyBoss\Core
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-core component.
 *
 * @since BuddyPress 1.6.0
 */
function bp_setup_core() {
	buddypress()->core = new BP_Core();

	// Load Admin Settings 2.0 initialization early.
	if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-init.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-init.php';
	}

	// Load the debug-asset fetcher: when WP_DEBUG && SCRIPT_DEBUG are both on,
	// the unminified counterparts of paired `.min.{js,css}` assets are
	// downloaded from the production branch at runtime so devs see readable
	// source. The shipped zip carries only the minified pair files, keeping
	// the customer download small.
	if ( file_exists( buddypress()->plugin_dir . 'bp-core/bb-debug-asset-fetcher-loader.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/bb-debug-asset-fetcher-loader.php';
	}

	// Load the S3 image offloader: rewrites local Platform image URLs in the
	// final HTML output to an external S3 bucket so image bytes are served
	// from S3/CDN instead of the WordPress host. Cheap when disabled.
	if ( file_exists( buddypress()->plugin_dir . 'bp-core/bb-s3-image-offload-loader.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/bb-s3-image-offload-loader.php';
	}
}
add_action( 'bp_loaded', 'bp_setup_core', 0 );
