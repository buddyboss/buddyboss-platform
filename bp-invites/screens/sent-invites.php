<?php
/**
 * Sent Invites: User's "Invites > Invite by Email" screen handler
 *
 * @package BuddyBoss\Invite\Screens
 * @since BuddyBoss 1.0.0
 *
 * @todo how is this page different from send-invites?
 */

/**
 * Handle the loading of a user's Invites > Invite by Email page.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_invites_screen_sent_invite() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	add_action( 'bp_template_content', 'bp_invites_sent_invite_screen' );

	/**
	 * Filters the template to load for a users Invites > Invite by Email page.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $value Path to a users Invites > Invite by Email page template.
	 */
	bp_core_load_template( apply_filters( 'bp_invites_screen_sent_invite', 'members/single/plugins' ) );
}

/**
 * Output members's Invites > Email Invite page.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_invites_sent_invite_screen() {
	bp_get_template_part( 'members/single/invites' );
}
