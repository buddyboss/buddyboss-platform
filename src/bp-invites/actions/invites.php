<?php

/**
 * Send Invites: send invitations action handler
 *
 * @package BuddyBoss
 * @subpackage SettingsActions
 * @since BuddyPress 3.0.0
 */
function bp_member_invite_submit() {

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


	//print_r( $_POST );
	if ( empty( $_POST ) ) {
		bp_core_add_message( __( 'You didn\'t include any email addresses!', 'buddyboss' ), 'error' );
		bp_core_redirect( $bp->loggedin_user->domain . '/invites' );
		die();
	}

	$invite_correct_array = array();
	$invite_wrong_array = array();
	foreach ( $_POST['email'] as $key => $value ) {

		if ( '' !== $_POST['invitee'][$key][0] && '' !== $_POST['email'][$key][0] && is_email( $_POST['email'][$key][0] ) ) {
			$invite_correct_array[] = array(
				'name' => $_POST['invitee'][$key][0],
				'email' => $_POST['email'][$key][0],
			);
		} else {
			$invite_wrong_array[] = array(
				'name' => $_POST['invitee'][$key][0],
				'email' => $_POST['email'][$key][0],
			);
		}
	}

	foreach ( $invite_correct_array as $key => $value ) {

		if ( true === bp_disable_invite_member_email_subject() ) {
			$subject = bp_get_member_invites_wildcard_replace ( stripslashes( strip_tags( $_POST['bp_member_invites_custom_subject'] ) ) );
		} else {
			$subject = stripslashes( strip_tags( bp_get_member_invitation_subject() ) );
		}

		if ( true === bp_disable_invite_member_email_content() ) {
			$message = bp_get_member_invites_wildcard_replace( stripslashes( strip_tags( $_POST['bp_member_invites_custom_content'] ) ) );
		} else {
			$message = stripslashes( strip_tags( bp_get_member_invitation_message() ) );
		}

		$email = $value['email'];
		$name = $value['name'];
		$inviter_name = bp_core_get_user_displayname( bp_loggedin_user_id() );

		$message .= '

'.bp_get_member_invites_wildcard_replace( stripslashes( strip_tags( bp_get_invites_member_invite_url() ) ), $email );

		wp_mail( $email, $subject, $message );

		$insert_post_args = array(
			'post_author'	=> $bp->loggedin_user->id,
			'post_content'	=> $message,
			'post_title'	=> $subject,
			'post_status'	=> 'publish',
			'post_type'	=> bp_get_invite_post_type(),
		);

		if ( !$post_id = wp_insert_post( $insert_post_args ) )
			return false;

		// Save a blank bp_ia_accepted post_meta
		update_post_meta( $post_id, 'bp_member_invites_accepted', '' );
		update_post_meta( $post_id, '_bp_invitee_email', $email );
		update_post_meta( $post_id, '_bp_invitee_name', $name );
		update_post_meta( $post_id, '_bp_inviter_name', $inviter_name );
		update_post_meta( $post_id, '_bp_invitee_status', 'Revoke Invite' );
	}

	bp_core_redirect( bp_displayed_user_domain() . 'invites/sent-invites' );

}
add_action( 'bp_actions', 'bp_member_invite_submit' );
