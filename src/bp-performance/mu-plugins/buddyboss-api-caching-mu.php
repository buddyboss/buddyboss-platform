<?php
/**
 * Plugin Name: BuddyBoss API Caching
 * Description: Must-Use plugin for BuddyBoss App to enable API Caching.
 * Version: 1.0.0
 * Author: BuddyBoss
 * Author URI:  https://www.buddyboss.com
 *
 * @package BuddyBoss\Performance
 */

/**
 * Load the class from Bundle BuddyBoss APP.
 */
$include = WP_PLUGIN_DIR . '/buddyboss-app/include/PluginMuLoad.php';
// Cache [ performance ].
if ( file_exists( $include ) ) {
	/**
	 * Included File.
	 *
	 * @var string $include file path.
	 */
	require_once $include;
}

/**
 * Load the class from Bundle BuddyBoss APP.
 */
// File for the build version.
$buddyboss_performance     = WP_PLUGIN_DIR . '/buddyboss-platform/bp-performance/bp-performance-mu-loader.php';

// File for the development version.
$buddyboss_performance_dev = WP_PLUGIN_DIR . '/buddyboss-platform/src/bp-performance/bp-performance-mu-loader.php';

// Cache [ performance ].
if ( file_exists( $buddyboss_performance ) ) {
	/**
	 * Included File.
	 *
	 * @var string $buddyboss_performance file path.
	 */
	require_once $buddyboss_performance;
} elseif ( file_exists( $buddyboss_performance_dev ) ) {
	/**
	 * Included File.
	 *
	 * @var string $buddyboss_performance_dev file path.
	 */
	require_once $buddyboss_performance_dev;
}


