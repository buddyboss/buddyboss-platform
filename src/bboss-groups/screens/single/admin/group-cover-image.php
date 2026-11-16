<?php
/**
 * Groups: Single group "Manage > Cover Photo" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle the display of a group's Change cover photo page.
 *
 * @since BuddyPress 2.4.0
 */
function bboss_groups_screen_group_admin_cover_image() {
	if ( 'group-cover-image' != bboss_get_group_current_admin_tab() ) {
		return false;
	}

	// If the logged-in user doesn't have permission or if cover photo uploads are disabled, then stop here.
	if ( ! bboss_is_item_admin() || ! bboss_group_use_cover_image_header() ) {
		return false;
	}

	/**
	 * Fires before the loading of the group Change cover photo page template.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param int $id ID of the group that is being displayed.
	 */
	do_action( 'groups_screen_group_admin_cover_image', bboss_get_current_group_id() );

	/**
	 * Filters the template to load for a group's Change cover photo page.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param string $value Path to a group's Change cover photo template.
	 */
	bboss_core_load_template( apply_filters( 'groups_template_group_admin_cover_image', 'groups/single/home' ) );
}
add_action( 'bp_screens', 'groups_screen_group_admin_cover_image' );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_groups_screen_group_admin_cover_image' ) && ! function_exists( 'groups_screen_group_admin_cover_image' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_screen_group_admin_cover_image()}.
	 *
	 * @deprecated Use bboss_groups_screen_group_admin_cover_image() instead.
	 */
	function groups_screen_group_admin_cover_image() {
		return bboss_groups_screen_group_admin_cover_image();
	}
}
// @bbwt-com-end compat-layer
