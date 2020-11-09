<?php
/**
 * Moderation: User's "Moderation" screen handler
 *
 * @package BuddyBoss\Moderation\Screens
 * @since BuddyBoss 2.0.0
 */

/**
 * Handle the loading of the My Groups page.
 *
 * @since BuddyBoss 2.0.0
 */
function bp_moderation_screen() {

	/**
	 * Fires before the loading of the My Groups page.
	 *
	 * @since BuddyBoss 2.0.0
	 */
	do_action( 'bp_moderation_screen' );

	/**
	 * Filters the template to load for the My Groups page.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $value Path to the My Groups page template to load.
	 */
	bp_core_load_template( apply_filters( 'moderation_template_content', 'members/single/home' ) );
}
