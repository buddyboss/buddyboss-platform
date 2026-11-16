<?php
/**
 * Sent Invites: User's "Sent Invites" screen handler
 *
 * @package BuddyBoss\Invite\Screens
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle the loading of the Invites > Sent Invites page.
 *
 * @since BuddyBoss 1.0.0
 */
function bboss_invites_screen_send_invite() {

	if ( bboss_action_variables() ) {
		bboss_do_404();
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
	bboss_core_load_template( apply_filters( 'bp_invites_screen_send_invite', 'members/single/plugins' ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_invites_screen_send_invite' ) && ! function_exists( 'bp_invites_screen_send_invite' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invites_screen_send_invite()}.
	 *
	 * @deprecated Use bboss_invites_screen_send_invite() instead.
	 */
	function bp_invites_screen_send_invite() {
		return bboss_invites_screen_send_invite();
	}
}
// @bbwt-com-end compat-layer


/**
 * Output members's Invites > Sent Invite page.
 *
 * @since BuddyBoss 1.0.0
 */
function bboss_invites_send_invite_screen() {
	bboss_get_template_part( 'members/single/invites' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_invites_send_invite_screen' ) && ! function_exists( 'bp_invites_send_invite_screen' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invites_send_invite_screen()}.
	 *
	 * @deprecated Use bboss_invites_send_invite_screen() instead.
	 */
	function bp_invites_send_invite_screen() {
		return bboss_invites_send_invite_screen();
	}
}
// @bbwt-com-end compat-layer

