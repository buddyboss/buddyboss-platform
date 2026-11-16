<?php
/**
 * Activity: User's "Activity > Favorites" screen handler
 *
 * @package BuddyBoss\Activity\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the 'Favorites' activity page.
 *
 * @since BuddyPress 1.2.0
 */
function bboss_activity_screen_favorites() {
	bboss_update_is_item_admin( bboss_current_user_can( 'bp_moderate' ), 'activity' );

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
	bboss_core_load_template( apply_filters( 'bp_activity_template_favorite_activity', 'members/single/home' ) );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_activity_screen_favorites' ) && ! function_exists( 'bp_activity_screen_favorites' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_screen_favorites()}.
	 *
	 * @deprecated Use bboss_activity_screen_favorites() instead.
	 */
	function bp_activity_screen_favorites() {
		return bboss_activity_screen_favorites();
	}
}
// @bbwt-com-end compat-layer
