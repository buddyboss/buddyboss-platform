<?php
/**
 * Activity: User's "Activity > Mentions" screen handler
 *
 * @package BuddyBoss\Activity\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Load the 'Mentions' activity page.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_screen_mentions() {
	bp_update_is_item_admin( bp_current_user_can( 'bp_moderate' ), 'activity' );

	/**
	 * Fires right before the loading of the "Mentions" screen template file.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_activity_screen_mentions' );

	/**
	 * Filters the template to load for the "Mentions" screen.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $template Path to the activity template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_activity_template_mention_activity', 'members/single/home' ) );
}

/**
 * Reset the logged-in user's new mentions data when he visits his mentions screen.
 *
 * @since BuddyPress 1.5.0
 */
function bp_activity_reset_my_new_mentions() {
	if ( bp_is_my_profile() ) {
		bp_activity_clear_new_mentions( bp_loggedin_user_id() );
	}
}
add_action( 'bp_activity_screen_mentions', 'bp_activity_reset_my_new_mentions' );
