<?php
/**
 * Sent Invites: User's "Sent Invites" screen handler
 *
 * @package BuddyBoss\Invite\Screens
 * @since BuddyBoss 1.0.0
 */

/**
 * Handle the loading of the Invites > Sent Invites page.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_invites_screen_send_invite() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	add_action( 'bp_template_content', 'bp_invites_send_invite_screen' );

	/**
	 * Filters the template to load for the Invites > Sent Invites page.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $value Path to the Invites > Sent Invites page template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_invites_screen_send_invite', 'members/single/plugins' ) );
}

/**
 * Output members's Invites > Sent Invite page.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_invites_send_invite_screen() {
	bp_get_template_part( 'members/single/invites' );
}
