<?php
/**
 * Groups: User's "Groups" screen handler
 *
 * @package BuddyBoss\Group\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Handle the loading of the My Groups page.
 *
 * @since BuddyPress 1.0.0
 */
function groups_screen_my_groups() {

	/**
	 * Fires before the loading of the My Groups page.
	 *
	 * @since BuddyPress 1.1.0
	 */
	do_action( 'groups_screen_my_groups' );

	/**
	 * Filters the template to load for the My Groups page.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value Path to the My Groups page template to load.
	 */
	bp_core_load_template( apply_filters( 'groups_template_my_groups', 'members/single/home' ) );
}
