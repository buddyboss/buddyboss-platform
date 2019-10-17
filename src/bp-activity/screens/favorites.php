<?php
/**
 * Activity: User's "Activity > Favorites" screen handler
 *
 * @package BuddyBoss\Activity\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Load the 'Favorites' activity page.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_screen_favorites() {
	bp_update_is_item_admin( bp_current_user_can( 'bp_moderate' ), 'activity' );

	/**
	 * Fires right before the loading of the "Favorites" screen template file.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_activity_screen_favorites' );

	/**
	 * Filters the template to load for the "Favorites" screen.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $template Path to the activity template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_activity_template_favorite_activity', 'members/single/home' ) );
}
