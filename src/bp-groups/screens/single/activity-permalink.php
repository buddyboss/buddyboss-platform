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

/**
 * Handle the display of a single group activity item.
 *
 * @since BuddyPress 1.2.0
 */
function groups_screen_group_activity_permalink() {
	if ( ! bp_is_groups_component() || ! bp_is_active( 'activity' ) || ( bp_is_active( 'activity' ) && ! bp_is_current_action( bp_get_activity_slug() ) ) || ! bp_action_variable( 0 ) ) {
		return false;
	}

	buddypress()->is_single_item = true;

	/** This filter is documented in bp-groups/bp-groups-screens.php */
	bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );
}
add_action( 'bp_screens', 'groups_screen_group_activity_permalink' );
