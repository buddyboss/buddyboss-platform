<?php
/**
 * Groups: Single group "Documents" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyBoss 1.2.5
 */

/**
 * Handle the loading of a single group's documents.
 *
 * @since BuddyBoss 1.2.5
 */
function groups_screen_group_document() {

	if ( ! bp_is_single_item() ) {
		return false;
	}

	/**
	 * Fires before the loading of a single group's documents page.
	 *
	 * @since BuddyBoss 1.2.5
	 */
	do_action( 'groups_screen_group_document' );

	/**
	 * Filters the template to load for a single group's documents page.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param string $value Path to a single group's template to load.
	 */
	bp_core_load_template( apply_filters( 'groups_screen_group_document', 'groups/single/home' ) );
}
