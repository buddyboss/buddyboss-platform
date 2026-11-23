<?php
/**
 * Tells the plugin loader whether this copy of BuddyBoss Platform must stay unloaded.
 *
 * Two copies of Platform cannot run in one request: they declare the same functions
 * and classes. The edition distributed from buddyboss.com is named "BuddyBoss
 * Platform" and carries licensing and add-on compatibility, so it takes precedence.
 * A copy under any other name stays unloaded while that edition is active, and no
 * copy loads a second time in a request.
 *
 * The loader reads this file before it declares anything, so this file must not
 * declare a function, a class or a constant, and must not load another file.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True when this copy must not load in the current request.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return ( static function () {
	// A copy of Platform is already running in this request, for example when a
	// second edition is being activated.
	if ( defined( 'BBOSS_PLATFORM_LOADED_FROM' ) ) {
		return true;
	}

	// This copy's plugin file: one level up in a release package, two in a source checkout.
	$own_file = dirname( __DIR__ ) . '/bp-loader.php';
	if ( 'src' === basename( dirname( __DIR__ ) ) && is_file( dirname( dirname( __DIR__ ) ) . '/bp-loader.php' ) ) {
		$own_file = dirname( dirname( __DIR__ ) ) . '/bp-loader.php';
	}
	$own_plugin = plugin_basename( $own_file );

	$active = (array) get_option( 'active_plugins', array() );
	if ( is_multisite() ) {
		$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
	}

	// The buddyboss.com edition is recognised by its plugin name, whatever its folder is
	// called. Only another active `bp-loader.php` is read, so a site without one pays nothing.
	$edition_name = 'BuddyBoss Platform';
	$found        = false;
	foreach ( $active as $plugin ) {
		if ( ! is_string( $plugin ) || $plugin === $own_plugin || 'bp-loader.php' !== basename( $plugin ) ) {
			continue;
		}

		// An entry whose file is gone is about to be deactivated by WordPress.
		$file = WP_PLUGIN_DIR . '/' . $plugin;
		if ( ! is_file( $file ) ) {
			continue;
		}

		$data = get_file_data( $file, array( 'name' => 'Plugin Name' ) );
		if ( $edition_name === $data['name'] ) {
			$found = true;
			break;
		}
	}

	if ( ! $found ) {
		return false;
	}

	// This copy stays unloaded unless it is that edition itself.
	$data = get_file_data( $own_file, array( 'name' => 'Plugin Name' ) );

	return $edition_name !== $data['name'];
} )();
