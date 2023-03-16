<?php
/**
 * Plugin Name: BuddyBoss Presence API
 * Description: Must-Use plugin for BuddyBoss platform presence API.
 * Version: 1.0.0
 * Author: BuddyBoss
 * Author URI:  https://www.buddyboss.com
 *
 * @package BuddyBoss\Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . '/wp-admin/includes/plugin.php';
}

// File from the build version.
$buddyboss_presence = WP_PLUGIN_DIR . '/buddyboss-platform/bp-core/classes/class-bb-presence.php';

// File from the development version.
$buddyboss_presence_dev = WP_PLUGIN_DIR . '/buddyboss-platform/src/bp-core/classes/class-bb-presence.php';

if ( is_plugin_active( 'buddyboss-platform/bp-loader.php' ) ) {
	if ( file_exists( $buddyboss_presence ) ) {
		/**
		 * Included File.
		 */
		require_once $buddyboss_presence;
	} elseif ( file_exists( $buddyboss_presence_dev ) ) {
		/**
		 * Included File.
		 */
		require_once $buddyboss_presence_dev;
	}
}
