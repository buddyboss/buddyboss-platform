<?php
/**
 * Moderation: User's "Moderation" screen handler
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation\Screens
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle the loading of the My Groups page.
 *
 * @since BuddyBoss 1.5.6
 */
function bboss_moderation_screen() {

	/**
	 * Fires before the loading of the My Groups page.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	do_action( 'bp_moderation_screen' );

	/**
	 * Filters the template to load for the My Groups page.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $value Path to the My Groups page template to load.
	 */
	bboss_core_load_template( apply_filters( 'moderation_template_content', 'members/single/home' ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_screen' ) && ! function_exists( 'bp_moderation_screen' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_screen()}.
	 *
	 * @deprecated Use bboss_moderation_screen() instead.
	 */
	function bp_moderation_screen() {
		return bboss_moderation_screen();
	}
}
// @bbwt-com-end compat-layer

