<?php
/**
 * Video: Single album screen handler
 *
 * @package BuddyBoss\Video\Screens
 * @since   BuddyBoss 1.7.0
 */

/**
 * Load an individual album screen.
 *
 * @since BuddyBoss 1.7.0
 *
 * @return false|null False on failure.
 */
function video_screen_single_album() {

	// Bail if not viewing a single album.
	if ( ! bp_is_video_component() || ! bp_is_single_album() ) {
		return false;
	}

	$album_id = (int) bp_action_variable( 0 );

	if ( empty( $album_id ) || ! BP_Video_Album::album_exists( $album_id ) ) {
		if ( is_user_logged_in() ) {
			bp_core_add_message( __( 'The album you tried to access is no longer available', 'buddyboss' ), 'error' );
		}

		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_media_slug() . '/albums' ) );
	}

	// No access.
	if ( ( ! albums_check_video_album_access( $album_id ) && ! bp_is_my_profile() ) && ! bp_current_user_can( 'bp_moderate' ) ) {
		bp_core_add_message( __( 'You do not have access to that album.', 'buddyboss' ), 'error' );
		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_media_slug() . '/albums' ) );
	}

	/**
	 * Fires right before the loading of the single album view screen template file.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'video_screen_single_album' );

	/**
	 * Filters the template to load for the Single Album view screen.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param string $template Path to the album template to load.
	 */
	bp_core_load_template( apply_filters( 'video_template_single_album', 'members/single/home' ) );
}

add_action( 'bp_screens', 'video_screen_single_album' );
