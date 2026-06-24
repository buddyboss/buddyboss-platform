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
 * Register the Groups menu item — link points directly at Settings 2.0.
 *
 * Pushed onto `$submenu` directly (no `add_submenu_page` call) so the slug
 * stays unregistered — WP's menu renderer uses the URL verbatim as the
 * link's `href`. Direct visits to the legacy `?page=bp-groups` (LearnDash,
 * bookmarks, third-party links) are caught upstream by
 * `bb_redirect_bp_settings_before_permission_check()` at
 * `admin_menu @ PHP_INT_MAX` — fires before WP's permission check, so
 * unregistered slug visits don't 403.
 *
 * @since BuddyPress 1.7.0
 */
function bp_groups_add_admin_menu() {
	global $submenu;

	$settings_url = function_exists( 'bb_get_feature_settings_url' )
		? bb_get_feature_settings_url( 'groups', 'all_groups' )
		: admin_url( 'admin.php?page=bb-settings&tab=groups&panel=all_groups' );

	$submenu['buddyboss-platform'][] = array(
		__( 'Groups', 'buddyboss-platform' ),
		'bp_moderate',
		$settings_url,
	);
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
