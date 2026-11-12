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
 * @since BuddyBoss 3.6.2 Only the inviter or a moderator may revoke an invite, with a valid nonce.
 */
function bboss_member_revoke_invite() {

	global $bp;

	if ( ! bboss_is_invites_component() ) {
		return;
	}

	if ( ! bboss_is_my_profile() ) {
		return;
	}

	if ( ! bboss_is_post_request() ) {
		return;
	}

	// Bail if not in settings.
	if ( ! bboss_is_invites_component() || ! bboss_is_current_action( 'revoke-invite' ) ) {
		return;
	}

	// 404 if there are any additional action variables attached
	if ( bboss_action_variables() ) {
		bboss_do_404();
		return;
	}

	if ( empty( $_POST ) ) {
		bboss_core_add_message( __( 'You didn\'t include any email addresses!', 'buddyboss' ), 'error' );
		bboss_core_redirect( $bp->loggedin_user->domain . '/invites/sent-invites' );
		die();
	}

	$post_id = filter_input( INPUT_POST, 'item_id', FILTER_VALIDATE_INT );
	$nonce   = bboss_filter_input_string( INPUT_POST, '_wpnonce' );
	if (
		! empty( $post_id ) &&
		! empty( $nonce ) &&
		wp_verify_nonce( $nonce, 'bb_invites_revoke' ) &&
		bboss_invites_user_can_revoke( $post_id )
	) {
		wp_delete_post( $post_id, true );
	}

	bboss_core_add_message( __( 'You didn\'t include any email addresses!', 'buddyboss' ), 'error' );
	bboss_core_redirect( bboss_displayed_user_domain() . 'invites/' );

}
add_action( 'bp_actions', 'bp_member_revoke_invite' );
