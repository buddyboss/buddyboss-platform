<?php
/**
 * Groups: Single group "Albums" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Handle the loading of a single group's albums.
 *
 * @since BuddyPress 2.4.0
 */
function groups_screen_group_albums() {

	if ( ! bp_is_single_item() ) {
		return false;
	}

	/**
	 * Fires before the loading of a single group's activity page.
	 *
	 * @since BuddyPress 2.4.0
	 */
	do_action( 'groups_screen_group_albums' );

	/**
	 * Filters the template to load for a single group's activity page.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param string $value Path to a single group's template to load.
	 */
	bp_core_load_template( apply_filters( 'groups_screen_group_albums', 'groups/single/home' ) );
}
