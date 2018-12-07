<?php
/**
 * Groups: User's "Groups > Invites" screen handler
 *
 * @package BuddyBoss
 * @subpackage GroupScreens
 * @since BuddyPress 3.0.0
 */

/**
 * Handle the loading of a user's Groups > Invites page.
 *
 * @since BuddyPress 1.0.0
 */
function bp_invites_screen_sent_invite() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	//do_action( 'bp_invites_screen_sent_invite' );

	/**
	 * Filters the template to load for a users Groups > Invites page.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value Path to a users Groups > Invites page template.
	 */
	bp_core_load_template( apply_filters( 'bp_invites_screen_sent_invite', 'members/single/invites/sent-invites' ) );
}
