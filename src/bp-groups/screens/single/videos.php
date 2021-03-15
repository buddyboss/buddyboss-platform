<?php
/**
 * Groups: Single group "Videos" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyBoss 1.5.7
 */

/**
 * Handle the loading of a single group's videos.
 *
 * @since BuddyBoss 1.5.7
 */
function groups_screen_group_video() {

	if ( ! bp_is_single_item() ) {
		return false;
	}

	/**
	 * Fires before the loading of a single group's videos page.
	 *
	 * @since BuddyBoss 1.5.7
	 */
	do_action( 'groups_screen_group_video' );

	/**
	 * Filters the template to load for a single group's videos page.
	 *
	 * @since BuddyBoss 1.5.7
	 *
	 * @param string $value Path to a single group's template to load.
	 */
	bp_core_load_template( apply_filters( 'groups_screen_group_video', 'groups/single/home' ) );
}
