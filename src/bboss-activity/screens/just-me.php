<?php
/**
 * Activity: User's "Activity" screen handler
 *
 * @package BuddyBoss\Activity\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the 'My Activity' page.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_activity_screen_my_activity() {

	/**
	 * Fires right before the loading of the "My Activity" screen template file.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'bp_activity_screen_my_activity' );

	/**
	 * Filters the template to load for the "My Activity" screen.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $template Path to the activity template to load.
	 */
	bboss_core_load_template( apply_filters( 'bp_activity_template_my_activity', 'members/single/home' ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_activity_screen_my_activity' ) && ! function_exists( 'bp_activity_screen_my_activity' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_screen_my_activity()}.
	 *
	 * @deprecated Use bboss_activity_screen_my_activity() instead.
	 */
	function bp_activity_screen_my_activity() {
		return bboss_activity_screen_my_activity();
	}
}
// @bbwt-com-end compat-layer

