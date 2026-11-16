<?php
/**
 * Sent Invites: User's "Invites > Invite by Email" screen handler
 *
 * @package BuddyBoss\Invite\Screens
 * @since BuddyBoss 1.0.0
 *
 * @todo how is this page different from send-invites?
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle the loading of a user's Invites > Invite by Email page.
 *
 * @since BuddyBoss 1.0.0
 */
function bboss_invites_screen_sent_invite() {

	if ( bboss_action_variables() ) {
		bboss_do_404();
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
	bboss_core_load_template( apply_filters( 'bp_invites_screen_sent_invite', 'members/single/plugins' ) );
}

/**
 * Output members's Invites > Email Invite page.
 *
 * @since BuddyBoss 1.0.0
 */
function bboss_invites_sent_invite_screen() {
	bboss_get_template_part( 'members/single/invites' );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_invites_screen_sent_invite' ) && ! function_exists( 'bp_invites_screen_sent_invite' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invites_screen_sent_invite()}.
	 *
	 * @deprecated Use bboss_invites_screen_sent_invite() instead.
	 */
	function bp_invites_screen_sent_invite() {
		return bboss_invites_screen_sent_invite();
	}
}
if ( function_exists( 'bboss_invites_sent_invite_screen' ) && ! function_exists( 'bp_invites_sent_invite_screen' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invites_sent_invite_screen()}.
	 *
	 * @deprecated Use bboss_invites_sent_invite_screen() instead.
	 */
	function bp_invites_sent_invite_screen() {
		return bboss_invites_sent_invite_screen();
	}
}
// @bbwt-com-end compat-layer
