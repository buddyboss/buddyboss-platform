<?php
/**
 * Groups: Single group "Activity" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Handle the loading of a single group's activity.
 *
 * @since BuddyPress 2.4.0
 */
function groups_screen_group_activity() {

	if ( ! bp_is_single_item() ) {
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
	bp_core_load_template( apply_filters( 'groups_screen_group_activity', 'groups/single/activity' ) );
}
