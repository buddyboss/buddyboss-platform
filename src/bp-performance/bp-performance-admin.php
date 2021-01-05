<?php
/**
 * BuddyBoss Performance component admin screen.
 *
 * @since   BuddyBoss 1.6.0
 * @package BuddyBoss\Performance
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register the Performance component admin screen.
 *
 * @since BuddyBoss 1.6.0
 */
function bp_performance_add_admin_menu() {

	// Add our screen.
	$hook = add_submenu_page(
		'buddyboss-platform',
		esc_html__( 'API Caching', 'buddyboss' ),
		esc_html__( 'API Caching', 'buddyboss' ),
		'bp_performance',
		'bp-performance',
		'bp_performance_admin'
	);
}

add_action( bp_core_admin_hook(), 'bp_performance_add_admin_menu', 100 );

/**
 * Add performance component to custom menus array.
 *
 * Several BuddyPress components have top-level menu items in the Dashboard,
 * which all appear together in the middle of the Dashboard menu. This function
 * adds the Performance page to the array of these menu items.
 *
 * @since BuddyBoss 1.6.0
 *
 * @param array $custom_menus The list of top-level BP menu items.
 *
 * @return array $custom_menus List of top-level BP menu items, with Performance added.
 */
function bp_performance_admin_menu_order( $custom_menus = array() ) {
	array_push( $custom_menus, 'bp-performance' );

	return $custom_menus;
}

add_filter( 'bp_admin_menu_order', 'bp_performance_admin_menu_order' );
