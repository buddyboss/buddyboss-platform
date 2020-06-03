<?php
/**
 * Document: Single folder screen handler
 *
 * @package BuddyBoss\Document\Screens
 *
 * @since BuddyBoss 1.4.0
 */

/**
 * Load an individual folder screen.
 *
 * @since BuddyBoss 1.4.0
 *
 * @return false|null False on failure.
 */
function document_screen_single_folder() {

	// Bail if not viewing a single folder.
	if ( ! bp_is_document_component() || ! bp_is_single_folder() ) {
		return false;
	}

	$folder_id = (int) bp_action_variable( 0 );

	if ( empty( $folder_id ) || ! BP_Document_Folder::folder_exists( $folder_id ) ) {
		if ( is_user_logged_in() ) {
			bp_core_add_message( __( 'The folder you tried to access is no longer available', 'buddyboss' ), 'error' );
		}

		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_document_slug() . '/folders' ) );
	}

	// No access.
	if ( ( ! folders_check_folder_access( $folder_id ) && ! bp_is_my_profile() ) && ! bp_current_user_can( 'bp_moderate' ) ) {
		bp_core_add_message( __( 'You do not have access to that folder.', 'buddyboss' ), 'error' );
		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_document_slug() ) );
	}

	/**
	 * Fires right before the loading of the single folder view screen template file.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	do_action( 'document_screen_single_folder' );

	/**
	 * Filters the template to load for the Single Folder view screen.
	 *
	 * @since BuddyBoss 1.4.0
	 *
	 * @param string $template Path to the folder template to load.
	 */
	bp_core_load_template( apply_filters( 'document_template_single_folder', 'members/single/home' ) );
}
add_action( 'bp_screens', 'document_screen_single_folder' );
