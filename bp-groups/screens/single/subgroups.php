<?php
/**
 * Groups: Single group "Subgroups" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyBoss 1.0.0
 */

/**
 * Handle the loading of a single group's subgroups.
 *
 * @since BuddyBoss 1.0.0
 */
function groups_screen_group_subgroups() {

	if ( ! bp_is_single_item() ) {
		return false;
	}

	/**
	 * Fires before the loading of a single group's subgroups page.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'groups_screen_group_subgroups' );

	/**
	 * Filters the template to load for a single group's subgroups page.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $value Path to a single group's template to load.
	 */
	bp_core_load_template( apply_filters( 'groups_screen_group_subgroups', 'groups/single/home' ) );

}
