<?php
/**
 * Groups: Single group "Home" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle the loading of a single group's page.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_groups_screen_group_home() {

	if ( ! bboss_is_single_item() ) {
		return false;
	}

	/**
	 * Fires before the loading of a single group's page.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'groups_screen_group_home' );

	/**
	 * Filters the template to load for a single group's page.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value Path to a single group's template to load.
	 */
	bboss_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_groups_screen_group_home' ) && ! function_exists( 'groups_screen_group_home' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_screen_group_home()}.
	 *
	 * @deprecated Use bboss_groups_screen_group_home() instead.
	 */
	function groups_screen_group_home() {
		return bboss_groups_screen_group_home();
	}
}
// @bbwt-com-end compat-layer
