<?php
/**
 * Document: User's "Document" screen handler
 *
 * @package BuddyBoss\Document\Screens
 * @since BuddyBoss 1.4.0
 */

/**
 * Load the Document screen.
 *
 * @since BuddyBoss 1.4.0
 */
function document_screen() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	/**
	 * Fires right before the loading of the Document screen template file.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	do_action( 'document_screen' );

	/**
	 * Filters the template to load for the Document screen.
	 *
	 * @since BuddyBoss 1.4.0
	 *
	 * @param string $template Path to the document template to load.
	 */
	bp_core_load_template( apply_filters( 'document_template', 'members/single/home' ) );
}
