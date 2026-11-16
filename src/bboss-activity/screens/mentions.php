<?php
/**
 * Activity: User's "Activity > Mentions" screen handler
 *
 * @package BuddyBoss\Activity\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the 'Mentions' activity page.
 *
 * @since BuddyPress 1.2.0
 */
function bboss_activity_screen_mentions() {
	bboss_update_is_item_admin( bboss_current_user_can( 'bp_moderate' ), 'activity' );

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
	bboss_core_load_template( apply_filters( 'bp_activity_template_mention_activity', 'members/single/home' ) );
}

/**
 * Reset the logged-in user's new mentions data when he visits his mentions screen.
 *
 * @since BuddyPress 1.5.0
 */
function bboss_activity_reset_my_new_mentions() {
	if ( bboss_is_my_profile() ) {
		bboss_activity_clear_new_mentions( bboss_loggedin_user_id() );
	}
}
add_action( 'bp_activity_screen_mentions', 'bp_activity_reset_my_new_mentions' );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_activity_reset_my_new_mentions' ) && ! function_exists( 'bp_activity_reset_my_new_mentions' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_reset_my_new_mentions()}.
	 *
	 * @deprecated Use bboss_activity_reset_my_new_mentions() instead.
	 */
	function bp_activity_reset_my_new_mentions() {
		return bboss_activity_reset_my_new_mentions();
	}
}
if ( function_exists( 'bboss_activity_screen_mentions' ) && ! function_exists( 'bp_activity_screen_mentions' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_screen_mentions()}.
	 *
	 * @deprecated Use bboss_activity_screen_mentions() instead.
	 */
	function bp_activity_screen_mentions() {
		return bboss_activity_screen_mentions();
	}
}
// @bbwt-com-end compat-layer
