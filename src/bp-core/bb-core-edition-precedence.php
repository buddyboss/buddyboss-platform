<?php
/**
 * Makes this edition of BuddyBoss Platform take precedence over another copy.
 *
 * Two copies of Platform cannot run in one request: they declare the same functions
 * and classes. This edition carries licensing and add-on compatibility, so when it
 * finds another copy already running it deactivates that copy and stays out for the
 * rest of the request. From the next request on, this edition runs alone.
 *
 * The loader reads this file before it declares anything, so this file must not
 * declare a function, a class or a constant.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True when this copy must not load in the current request.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return ( static function () {
	// This edition is already running in this request.
	if ( defined( 'BB_PLATFORM_LOADED_FROM' ) ) {
		return true;
	}

	// No other copy of Platform has loaded.
	if ( ! function_exists( 'bp_core_active_plugin_is' ) ) {
		return false;
	}

	// Another copy loaded first. Find its plugin entry among the files of this request.
	$loaded = array();
	foreach ( get_included_files() as $file ) {
		if ( 'bp-loader.php' === basename( $file ) ) {
			$loaded[ wp_normalize_path( $file ) ] = true;
		}
	}

	$network = is_multisite() ? array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) : array();
	$active  = array_merge( $network, (array) get_option( 'active_plugins', array() ) );
	$other   = array();
	foreach ( $active as $plugin ) {
		if ( ! is_string( $plugin ) || 'bp-loader.php' !== basename( $plugin ) ) {
			continue;
		}
		if ( ! isset( $loaded[ wp_normalize_path( WP_PLUGIN_DIR . '/' . $plugin ) ] ) ) {
			continue;
		}
		if ( bp_core_active_plugin_is( $plugin, 'BuddyPress' ) ) {
			continue;
		}
		$other[] = $plugin;
	}

	if ( ! empty( $other ) ) {
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Silent: Platform itself stays active, so its deactivation routine must not run.
		deactivate_plugins( array_unique( $other ), true );
	}

	return true;
} )();
