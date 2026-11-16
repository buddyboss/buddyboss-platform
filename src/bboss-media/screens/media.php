<?php
/**
 * Media: User's "Media" screen handler
 *
 * @package BuddyBoss\Media\Screens
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the Media screen.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_media_screen() {

	if ( bboss_action_variables() ) {
		bboss_do_404();
		return;
	}

	/**
	 * Fires right before the loading of the Media screen template file.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'media_screen' );

	/**
	 * Filters the template to load for the Media screen.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $template Path to the media template to load.
	 */
	bboss_core_load_template( apply_filters( 'media_template', 'members/single/home' ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_media_screen' ) && ! function_exists( 'media_screen' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_media_screen()}.
	 *
	 * @deprecated Use bboss_media_screen() instead.
	 */
	function media_screen() {
		return bboss_media_screen();
	}
}
// @bbwt-com-end compat-layer

