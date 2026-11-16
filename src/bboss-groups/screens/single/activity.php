<?php
/**
 * Groups: Single group "Activity" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle the loading of a single group's activity.
 *
 * @since BuddyPress 2.4.0
 */
function bboss_groups_screen_group_activity() {

	if ( ! bboss_is_single_item() ) {
		return false;
	}

	/**
	 * Fires before the loading of a single group's activity page.
	 *
	 * @since BuddyPress 2.4.0
	 */
	do_action( 'groups_screen_group_activity' );

	/**
	 * Filters the template to load for a single group's activity page.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param string $value Path to a single group's template to load.
	 */
	bboss_core_load_template( apply_filters( 'groups_screen_group_activity', 'groups/single/activity' ) );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_groups_screen_group_activity' ) && ! function_exists( 'groups_screen_group_activity' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_screen_group_activity()}.
	 *
	 * @deprecated Use bboss_groups_screen_group_activity() instead.
	 */
	function groups_screen_group_activity() {
		return bboss_groups_screen_group_activity();
	}
}
// @bbwt-com-end compat-layer
