<?php
/**
 * Groups: Single group activity permalink screen handler
 *
 * Note - This has never worked.
 * See {@link https://buddypress.trac.wordpress.org/ticket/2579}
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle the display of a single group activity item.
 *
 * @since BuddyPress 1.2.0
 */
function bboss_groups_screen_group_activity_permalink() {
	if ( ! bboss_is_groups_component() || ! bboss_is_active( 'activity' ) || ( bboss_is_active( 'activity' ) && ! bboss_is_current_action( bboss_get_activity_slug() ) ) || ! bboss_action_variable( 0 ) ) {
		return false;
	}

	bboss()->is_single_item = true;

	/** This filter is documented in bboss-groups/bp-groups-screens.php */
	bboss_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );
}
add_action( 'bp_screens', 'groups_screen_group_activity_permalink' );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_groups_screen_group_activity_permalink' ) && ! function_exists( 'groups_screen_group_activity_permalink' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_screen_group_activity_permalink()}.
	 *
	 * @deprecated Use bboss_groups_screen_group_activity_permalink() instead.
	 */
	function groups_screen_group_activity_permalink() {
		return bboss_groups_screen_group_activity_permalink();
	}
}
// @bbwt-com-end compat-layer
