<?php
/**
 * Activity: User's "Activity > Following" screen handler
 *
 * @package BuddyBoss\Activity\Screens
 * @since BuddyBoss 1.1.6
 */

/**
 * Load the 'My Following' activity page.
 *
 * @since BuddyBoss 1.1.6
 */
function bp_activity_screen_following() {
	if ( ! bp_is_activity_follow_active() ) {
		return false;
	}

	bp_update_is_item_admin( bp_current_user_can( 'bp_moderate' ), 'activity' );

	/**
	 * Fires right before the loading of the "My Following" screen template file.
	 *
	 * @since BuddyBoss 1.1.6
	 */
	do_action( 'bp_activity_screen_following' );

	/**
	 * Filters the template to load for the "My Following" screen.
	 *
	 * @since BuddyBoss 1.1.6
	 *
	 * @param string $template Path to the activity template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_activity_template_following_activity', 'members/single/home' ) );
}
