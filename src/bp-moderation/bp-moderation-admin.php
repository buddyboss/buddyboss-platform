<?php
/**
 * BuddyBoss Moderation component admin screen.
 *
 * @package BuddyBoss\Moderation
 * @since BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Include WP's list table class.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Register the Moderation component admin screen.
 *
 * @since BuddyBoss 1.5.4
 */
function bp_moderation_add_admin_menu() {

	// Add our screen.
	$hook = add_submenu_page(
		'buddyboss-platform',
		__( 'Moderation', 'buddyboss' ),
		__( 'Moderation', 'buddyboss' ),
		'bp_moderate',
		'bp-moderation',
		'bp_moderation_admin'
	);

	// Hook into early actions to load custom CSS and our init handler.
	add_action( "load-$hook", 'bp_moderation_admin_load' );
}
add_action( bp_core_admin_hook(), 'bp_moderation_add_admin_menu', 100 );

/**
 * Add moderation component to custom menus array.
 *
 * Several BuddyPress components have top-level menu items in the Dashboard,
 * which all appear together in the middle of the Dashboard menu. This function
 * adds the Moderation page to the array of these menu items.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param array $custom_menus The list of top-level BP menu items.
 * @return array $custom_menus List of top-level BP menu items, with Moderation added.
 */
function bp_moderation_admin_menu_order( $custom_menus = array() ) {
	array_push( $custom_menus, 'bp-moderation' );
	return $custom_menus;
}
add_filter( 'bp_admin_menu_order', 'bp_moderation_admin_menu_order' );


/**
 * Set up the Moderation admin page.
 *
 * @since BuddyBoss 1.5.4
 */
function bp_moderation_admin_load() {

}
