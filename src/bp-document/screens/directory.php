<?php
/**
 * Document: Directory screen handler
 *
 * @package BuddyBoss\Document\Screens
 * @since BuddyBoss 1.4.0
 */

/**
 * Load the Document directory.
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_screen_index() {
	if ( bp_is_document_directory() ) {
		bp_update_is_directory( true, 'document' );

		/**
		 * Fires right before the loading of the Document directory screen template file.
		 *
		 * @since BuddyBoss 1.4.0
		 */
		do_action( 'bp_document_screen_index' );

		/**
		 * Filters the template to load for the Document directory screen.
		 *
		 * @since BuddyBoss 1.4.0
		 *
		 * @param string $template Path to the document template to load.
		 */
		bp_core_load_template( apply_filters( 'bp_document_screen_index', 'folder/index' ) );
	}
}
add_action( 'bp_screens', 'bp_document_screen_index' );
