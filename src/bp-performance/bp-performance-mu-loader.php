<?php
/**
 * BuddyBoss Performance MU loader.
 *
 * A performance component, Allow to cache BuddyBoss Platform REST API.
 *
 * @package BuddyBoss\Performance\MULoader
 * @since BuddyBoss 1.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'muplugins_loaded', 'bp_performance_loaded' );
if ( ! function_exists( 'bp_performance_loaded' ) ) {
	/**
	 * Load Performance instance.
	 */
	function bp_performance_loaded() {
		require_once dirname( __FILE__ ) . '/classes/class-performance.php';
		$performance = \BuddyBoss\Performance\Performance::instance();
		$performance->start();
	}
}
