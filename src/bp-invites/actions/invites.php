<?php
/**
 * Settings: Email address and password action handler
 *
 * @package BuddyBoss
 * @subpackage SettingsActions
 * @since BuddyPress 3.0.0
 */

function bp_member_invite_submit() {

	if ( ! bp_is_invites_component() ) {
		return;
	}

	if ( ! bp_is_my_profile() ) {
		return;
	}

	if ( ! bp_is_post_request() ) {
		return;
	}

	// Bail if no submit action.
	if ( ! isset( $_POST['member-invite-submit'] ) ) {
		return;
	}

	// Bail if not in settings.
	if ( ! bp_is_invites_component() || ! bp_is_current_action( 'send-invites' ) ) {
		return;
	}

	// 404 if there are any additional action variables attached
	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	// Nonce check.
	check_admin_referer('bp_member_invite_submit');


	print_r( $_POST );



}
add_action( 'bp_actions', 'bp_member_invite_submit' );
