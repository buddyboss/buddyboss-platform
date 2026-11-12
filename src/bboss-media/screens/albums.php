<?php
/**
 * Media: Single album screen handler
 *
 * @package BuddyBoss\Media\Screens
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load an individual album screen.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return false|null False on failure.
 */
function bboss_media_screen_single_album() {

	// Bail if not viewing a single album.
	if ( ! bboss_is_media_component() || ! bboss_is_single_album() ) {
		return false;
	}

	$album_id = (int) bboss_action_variable( 0 );

	if ( empty( $album_id ) || ! BBoss_Media_Album::album_exists( $album_id ) ) {
		if ( is_user_logged_in() ) {
			bboss_core_add_message( __( 'The album you tried to access is no longer available', 'buddyboss-platform' ), 'error' );
		}

		bboss_core_redirect( trailingslashit( bboss_displayed_user_domain() . bboss_get_media_slug() . '/albums' ) );
	}

	// No access.
	if ( ( ! bboss_albums_check_album_access( $album_id ) && ! bboss_is_my_profile() ) && ! bboss_current_user_can( 'bp_moderate' ) ) {
		bboss_core_add_message( __( 'You do not have access to that album.', 'buddyboss-platform' ), 'error' );
		bboss_core_redirect( trailingslashit( bboss_displayed_user_domain() . bboss_get_media_slug() . '/albums' ) );
	}

	/**
	 * Fires right before the loading of the single album view screen template file.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'media_screen_single_album' );

	/**
	 * Filters the template to load for the Single Album view screen.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $template Path to the album template to load.
	 */
	bboss_core_load_template( apply_filters( 'media_template_single_album', 'members/single/home' ) );
}
add_action( 'bp_screens', 'bboss_media_screen_single_album' );
