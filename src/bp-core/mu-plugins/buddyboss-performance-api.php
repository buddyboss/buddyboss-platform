<?php
/**
 * Plugin Name: BuddyBoss Performance API
 * Description: Must-Use plugin for BuddyBoss Platform to improve the performance of requests.
 * Version: 1.0.0
 * Author: BuddyBoss
 * Author URI:  https://www.buddyboss.com
 *
 * @package BuddyBoss\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
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

		if ( class_exists( 'BB_Presence' ) ) {
			$class_instance = BB_Presence::instance();
			add_action( 'muplugins_loaded', array( $class_instance, 'bb_presence_mu_loader' ), 10 );
		}
	} elseif ( file_exists( $buddyboss_presence_dev ) ) {
		/**
		 * Included File.
		 */
		require_once $buddyboss_presence_dev;

		if ( class_exists( 'BB_Presence' ) ) {
			$class_instance = BB_Presence::instance();
			add_action( 'muplugins_loaded', array( $class_instance, 'bb_presence_mu_loader' ), 10 );
		}
	}
}
