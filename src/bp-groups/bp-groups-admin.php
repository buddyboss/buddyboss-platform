<?php
/**
 * BuddyBoss Groups component admin screen.
 *
 * Legacy Groups admin (listing, create, edit, delete) has been replaced
 * by Settings 2.0 React UI. This file now only registers the admin menu
 * item so that existing links (e.g. from LearnDash) still resolve.
 * Visits to ?page=bp-groups are redirected to Settings 2.0 by
 * {@see bb_redirect_legacy_settings_to_settings_2()}.
 *
 * @package BuddyBoss\Groups
 * @since BuddyPress 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register the Groups component in BuddyBoss admin menu.
 *
 * Kept so that existing links (LearnDash, third-party plugins) to
 * admin.php?page=bp-groups still resolve. The page callback is empty
 * because all visits are redirected to Settings 2.0 by
 * {@see bb_redirect_legacy_settings_to_settings_2()}.
 *
 * @since BuddyPress 1.7.0
 */
function bp_groups_add_admin_menu() {

	// Add our screen.
	$hook = add_submenu_page(
		'buddyboss-platform',
		__( 'Groups', 'buddyboss' ),
		__( 'Groups', 'buddyboss' ),
		'bp_moderate',
		'bp-groups',
		'bp_groups_admin'
	);

	// Redirect legacy page to Settings 2.0 on load, in case the redirect
	// in bb_redirect_legacy_settings_to_settings_2() fires after headers.
	if ( $hook ) {
		add_action(
			'load-' . $hook,
			function () {
				$settings_url = function_exists( 'bb_get_settings_url' ) ? bb_get_settings_url() : '';
				if ( ! empty( $settings_url ) ) {
					wp_safe_redirect( add_query_arg( 'tab', 'groups', $settings_url ) );
					exit;
				}
			}
		);
	}
}
add_action( bp_core_admin_hook(), 'bp_groups_add_admin_menu', 60 );

/**
 * Add groups component to custom menus array.
 *
 * This ensures that the Groups menu item appears in the proper order on the
 * main Dashboard menu.
 *
 * @since BuddyPress 1.7.0
 *
 * @param array $custom_menus Array of BP top-level menu items.
 *
 * @return array Menu item array, with Groups added.
 */
function bp_groups_admin_menu_order( $custom_menus = array() ) {
	array_push( $custom_menus, 'bp-groups' );
	return $custom_menus;
}
add_filter( 'bp_admin_menu_order', 'bp_groups_admin_menu_order' );
