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
 * Load the class from Bundle.
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

