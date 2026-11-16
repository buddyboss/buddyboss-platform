<?php
/**
 * Activity: User's "Activity > Following" screen handler
 *
 * @package BuddyBoss\Activity\Screens
 * @since BuddyBoss 1.1.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the 'My Following' activity page.
 *
 * @since BuddyBoss 1.1.6
 */
function bboss_activity_screen_following() {
	if ( ! bboss_is_activity_follow_active() ) {
		return false;
	}

	bboss_update_is_item_admin( bboss_current_user_can( 'bp_moderate' ), 'activity' );

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
	bboss_core_load_template( apply_filters( 'bp_activity_template_following_activity', 'members/single/home' ) );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_activity_screen_following' ) && ! function_exists( 'bp_activity_screen_following' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_screen_following()}.
	 *
	 * @deprecated Use bboss_activity_screen_following() instead.
	 */
	function bp_activity_screen_following() {
		return bboss_activity_screen_following();
	}
}
// @bbwt-com-end compat-layer
