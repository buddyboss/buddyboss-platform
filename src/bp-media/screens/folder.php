<?php
/**
 * Media: Single album screen handler
 *
 * @package BuddyBoss\Media\Screens
 * @since BuddyBoss 1.0.0
 */

/**
 * Load an individual album screen.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return false|null False on failure.
 */
function media_screen_single_document_folder() {

	$album_id = (int) bp_action_variable( 0 );

	if ( empty( $album_id ) || ! BP_Media_Album::album_exists( $album_id, 'document' ) ) {
		if ( is_user_logged_in() ) {
			bp_core_add_message( __( 'The folder you tried to access is no longer available', 'buddyboss' ), 'error' );
		}

		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_document_slug() ) );
	}

	// No access.
	if ( ( ! albums_check_album_access( $album_id ) && ! bp_is_my_profile() ) && ! bp_current_user_can( 'bp_moderate' ) ) {
		bp_core_add_message( __( 'You do not have access to that folder.', 'buddyboss' ), 'error' );
		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_document_slug() ) );
	}

	/**
	 * Fires right before the loading of the single album view screen template file.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'media_screen_single_document_folder' );

	if ( 'folder' === bp_current_action() ) {
		add_action( 'bp_template_content', 'bp_media_documents_single_folder_screen' );
	}

	/**
	 * Filters the template to load for the Single Album view screen.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $template Path to the album template to load.
	 */
	bp_core_load_template( apply_filters( 'media_template_single_document_folder', 'members/single/home' ) );
}
add_action( 'bp_screens', 'media_screen_single_document_folder' );

function bp_media_documents_single_folder_screen() {
	bp_get_template_part( 'media/single-folder' );
}
