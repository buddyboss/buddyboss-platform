<?php
/**
 * Groups: User's "Groups" screen handler
 *
 * @package BuddyBoss
 * @subpackage GroupScreens
 * @since BuddyPress 3.0.0
 */

/**
 * Handle the loading of the My Groups page.
 *
 * @since BuddyPress 1.0.0
 */
function bp_invites_screen_send_invite() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	//do_action( 'invites_screen_send_invite' );

	/**
	 * Filters the template to load for the My Groups page.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value Path to the My Groups page template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_invites_screen_send_invite', 'members/single/invites/send-invites' ) );
}
