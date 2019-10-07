<?php
/**
 * Activity: User's "Activity > Connections" screen handler
 *
 * @package BuddyBoss\Activity\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Load the 'My Connections' activity page.
 *
 * @since BuddyPress 1.0.0
 */
function bp_activity_screen_friends() {
	if ( ! bp_is_active( 'friends' ) ) {
		return false;
	}

	bp_update_is_item_admin( bp_current_user_can( 'bp_moderate' ), 'activity' );

	/**
	 * Fires right before the loading of the "My Connections" screen template file.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_activity_screen_friends' );

	/**
	 * Filters the template to load for the "My Connections" screen.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $template Path to the activity template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_activity_template_friends_activity', 'members/single/home' ) );
}
