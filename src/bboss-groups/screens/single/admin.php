<?php
/**
 * Groups: Single group "Manage" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle the display of a group's Admin pages.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_groups_screen_group_admin() {
	if ( ! bboss_is_groups_component() || ! bboss_is_current_action( 'admin' ) ) {
		return false;
	}

	if ( bboss_action_variables() ) {
		return false;
	}

	bboss_core_redirect( bboss_get_group_permalink( bboss_groups_get_current_group() ) . 'admin/edit-details/' );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_groups_screen_group_admin' ) && ! function_exists( 'groups_screen_group_admin' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_screen_group_admin()}.
	 *
	 * @deprecated Use bboss_groups_screen_group_admin() instead.
	 */
	function groups_screen_group_admin() {
		return bboss_groups_screen_group_admin();
	}
}
// @bbwt-com-end compat-layer
