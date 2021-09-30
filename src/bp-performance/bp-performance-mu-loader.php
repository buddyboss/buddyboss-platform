<?php
/**
 * BuddyBoss Performance MU loader.
 *
 * A performance component, Allow to cache BuddyBoss Platform REST API.
 *
 * @package BuddyBoss\Performance\MULoader
 * @since BuddyBoss 1.5.7
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'bp_performance_loaded' ) ) {
	/**
	 * Load Performance instance.
	 */
	function bp_performance_loaded() {
		if ( ! class_exists( 'BuddyBoss\Performance\Performance' ) ) {
			require_once dirname( __FILE__ ) . '/classes/class-performance.php';
			\BuddyBoss\Performance\Performance::instance();
		}
	}

	add_action( 'muplugins_loaded', 'bp_performance_loaded', 20 );
}
