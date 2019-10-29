<?php

/**
 * Email Invite: Admin Revoke Actions
 *
 * @package BuddyBoss\Invite\Actions
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

 /**
  * Admin revoke email invite.
  *
  * @since BuddyBoss 1.0.0
  */
function bp_member_revoke_invite_admin() {

	global $bp;

	if ( ! bp_is_invites_component() ) {
		return;
	}

	if ( ! bp_is_my_profile() ) {
		return;
	}

	if ( ! bp_is_get_request() ) {
		return;
	}

	// Bail if not in settings.
	if ( ! bp_is_invites_component() || ! bp_is_current_action( 'revoke-invite-admin' ) ) {
		return;
	}

	// 404 if there are any additional action variables attached
	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	$redirect = filter_input( INPUT_GET, 'redirect', FILTER_VALIDATE_URL );

	if ( empty( $_GET ) ) {
		bp_core_add_message( __( 'You didn\'t include any email addresses!', 'buddyboss' ), 'error' );
		bp_core_redirect( $redirect );
		die();
	}

	$post_id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
	if ( isset( $post_id ) && '' !== $post_id ) {
		wp_delete_post( $post_id, true );
	}

	bp_core_redirect( $redirect );

}
add_action( 'bp_actions', 'bp_member_revoke_invite_admin' );
