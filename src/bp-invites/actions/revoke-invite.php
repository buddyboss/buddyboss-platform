<?php

/**
 * Email Invite: Revoke Actions
 *
 * @package BuddyBoss\Invite\Actions
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

 /**
  * Member revoke email invite.
  *
  * @since BuddyBoss 1.0.0
  */
function bp_member_revoke_invite() {

	global $bp;

	if ( ! bp_is_invites_component() ) {
		return;
	}

	if ( ! bp_is_my_profile() ) {
		return;
	}

	if ( ! bp_is_post_request() ) {
		return;
	}

	// Bail if not in settings.
	if ( ! bp_is_invites_component() || ! bp_is_current_action( 'revoke-invite' ) ) {
		return;
	}

	// 404 if there are any additional action variables attached
	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	if ( empty( $_POST ) ) {
		bp_core_add_message( __( 'You didn\'t include any email addresses!', 'buddyboss-platform' ), 'error' );
		bp_core_redirect( $bp->loggedin_user->domain . '/invites/sent-invites' );
		die();
	}

	$post_id = filter_input( INPUT_POST, 'item_id', FILTER_VALIDATE_INT );
	if ( ! empty( $post_id ) ) {
		$invite = get_post( $post_id );

		// Object-level authorization: only delete a post that is actually an
		// invite AND owned by the current (logged-in, own-profile) user. Without
		// this an authenticated member could force-delete ANY post by id.
		if (
			$invite instanceof WP_Post
			&& bp_get_invite_post_type() === $invite->post_type
			&& bp_loggedin_user_id() === (int) $invite->post_author
		) {
			wp_delete_post( $post_id, true );
		}
	}

	bp_core_add_message( __( 'You didn\'t include any email addresses!', 'buddyboss-platform' ), 'error' );
	bp_core_redirect( bp_displayed_user_domain() . 'invites/' );

}
add_action( 'bp_actions', 'bp_member_revoke_invite' );
