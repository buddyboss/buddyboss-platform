<?php

/**
 * Email Invite: Submit Actions and Filters
 *
 * @package BuddyBoss\Invite\Actions
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

 /**
  * Member submit email invite.
  *
  * @since BuddyBoss 1.0.0
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
	check_admin_referer( 'bp_member_invite_submit' );

	if ( empty( $_POST ) ) {
		bp_core_add_message( __( 'You didn\'t include any email addresses!', 'buddyboss' ), 'error' );
		bp_core_redirect( $bp->loggedin_user->domain . '/invites' );
		die();
	}

	$invite_correct_array    = array();
	$invite_wrong_array      = array();
	$invite_exists_array     = array();
	$invite_restricted_array = array();
	$duplicate_email_inputs  = array();

	foreach ( $_POST['email'] as $key => $value ) {

		// Ignore duplicate email input.
		if ( in_array( $_POST['email'][ $key ][0], $duplicate_email_inputs, true ) ) {
			continue;
		}
		$duplicate_email_inputs[] = strtolower( trim( $_POST['email'][ $key ][0] ) );

		if ( '' !== $_POST['invitee'][ $key ][0] && '' !== $_POST['email'][ $key ][0] && is_email( $_POST['email'][ $key ][0] ) ) {
			if ( email_exists( (string) $_POST['email'][ $key ][0] ) ) {
				$invite_exists_array[] = $_POST['email'][ $key ][0];
			} elseif ( bb_is_allowed_register_email_address( $_POST['email'][ $key ][0] ) ) {
				$invite_correct_array[] = array(
					'name'        => $_POST['invitee'][ $key ][0],
					'email'       => $_POST['email'][ $key ][0],
					'member_type' => ( isset( $_POST['member-type'][ $key ][0] ) && ! empty( $_POST['member-type'][ $key ][0] ) ) ? $_POST['member-type'][ $key ][0] : '',
				);
			} else {
				$invite_restricted_array[] = $_POST['email'][ $key ][0];
			}
		} else {
			$invite_wrong_array[] = array(
				'name'        => $_POST['invitee'][ $key ][0],
				'email'       => $_POST['email'][ $key ][0],
				'member_type' => ( isset( $_POST['member-type'][ $key ][0] ) && ! empty( $_POST['member-type'][ $key ][0] ) ) ? $_POST['member-type'][ $key ][0] : '',
			);
		}
	}
	$query_string = array();

	// check if it has enough recipients to use batch emails.
	$min_count_recipients = function_exists( 'bb_email_queue_has_min_count' ) && bb_email_queue_has_min_count( $invite_correct_array );

	foreach ( $invite_correct_array as $key => $value ) {

		if ( true === bp_disable_invite_member_email_subject() ) {
			$subject = stripslashes( strip_tags( $_POST['bp_member_invites_custom_subject'] ) );
		} else {
			$subject = stripslashes( strip_tags( bp_get_member_invitation_subject() ) );
		}

		if ( true === bp_disable_invite_member_email_content() ) {
			$message = stripslashes( strip_tags( $_POST['bp_member_invites_custom_content'] ) );
		} else {
			$message = stripslashes( strip_tags( bp_get_member_invitation_message() ) );
		}

		$email          = $value['email'];
		$name           = $value['name'];
		$member_type    = $value['member_type'];
		$query_string[] = $email;
		$inviter_name   = bp_core_get_user_displayname( bp_loggedin_user_id() );

		$message .= '

' . bp_get_member_invites_wildcard_replace( stripslashes( strip_tags( bp_get_invites_member_invite_url() ) ), $email );

		$inviter_name = bp_core_get_user_displayname( bp_loggedin_user_id() );
		$site_name    = get_bloginfo( 'name' );
		$inviter_url  = bp_loggedin_user_domain();

		$email_encode = urlencode( $email );

		// set post variable
		$_POST['custom_user_email'] = $email;

		// Set both variable which will use in email.
		$_POST['custom_user_name']   = $name;
		$_POST['custom_user_avatar'] = apply_filters( 'bp_sent_invite_email_avatar', bb_attachments_get_default_profile_group_avatar_image( array( 'object' => 'user' ) ) );

		$accept_link = add_query_arg(
			array(
				'bp-invites' => 'accept-member-invitation',
				'email'      => $email_encode,
				'inviter'    => base64_encode( bp_loggedin_user_id() ),
			),
			bp_get_root_domain() . '/' . bp_get_signup_slug() . '/'
		);
		$accept_link = apply_filters( 'bp_member_invitation_accept_url', $accept_link );
		$args        = array(
			'tokens' => array(
				'inviter.name' => $inviter_name,
				'inviter.url' => $inviter_url,
				'invitee.url'  => $accept_link,
			),
		);

		/**
		 * Remove Recipients avatar and name
		 *
		 * T:1602 - https://trello.com/c/p2VKGMHs/1602-recipients-name-and-avatar-should-not-be-showing-on-email-invite-template
		 */
		add_filter( 'bp_email_get_salutation', '__return_false' );
		// Send invitation email.
		if ( function_exists( 'bb_is_email_queue' ) && bb_is_email_queue() && $min_count_recipients ) {
			bb_email_queue()->add_record( 'invites-member-invite', $email, $args );
			// call email background process.
			bb_email_queue()->bb_email_background_process();
		} else {
			bp_send_email( 'invites-member-invite', $email, $args );
		}

		$insert_post_args = array(
			'post_author'  => $bp->loggedin_user->id,
			'post_content' => $message,
			'post_title'   => $subject,
			'post_status'  => 'publish',
			'post_type'    => bp_get_invite_post_type(),
		);

		if ( ! $post_id = wp_insert_post( $insert_post_args ) ) {
			return false;
		}

		// Save a blank bp_ia_accepted post_meta
		update_post_meta( $post_id, 'bp_member_invites_accepted', '' );
		update_post_meta( $post_id, '_bp_invitee_email', $email );
		update_post_meta( $post_id, '_bp_invitee_name', $name );
		update_post_meta( $post_id, '_bp_inviter_name', $inviter_name );
		update_post_meta( $post_id, '_bp_invitee_status', 0 );
		update_post_meta( $post_id, '_bp_invitee_member_type', $member_type );

		$user_id = bp_loggedin_user_id();

		/**
		 * Fires after a member invitation sent to invitee.
		 *
		 * @param int $user_id Inviter user id.
		 * @param int $post_id Invitation id.
		 *
		 * @since BuddyBoss 1.4.7
		 */
		do_action( 'bp_member_invite_submit', $user_id, $post_id );
	}

	$failed_invite = wp_list_pluck( array_filter( $invite_wrong_array ), 'email' );
	bp_core_redirect( bp_displayed_user_domain() . 'invites/sent-invites?email=' . urlencode( implode( ', ', $query_string ) ) . '&exists=' . urlencode( implode( ', ', $invite_exists_array ) ) . '&restricted=' . urlencode( implode( ', ', $invite_restricted_array ) ) . '&failed=' . urlencode( implode( ',', array_filter( $failed_invite ) ) ) );

}
add_action( 'bp_actions', 'bp_member_invite_submit' );

/**
 * Changes the subject based on the user typed.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $subject
 * @param email  $email
 *
 * @return mixed
 */
function bp_invites_member_invite_filter_subject( $subject, $email ) {

	if ( isset( $_POST['bp_member_invites_custom_subject'] ) && '' !== $_POST['bp_member_invites_custom_subject'] && 'invites-member-invite' === $email->get( 'type' ) ) {
		$subject = bp_get_member_invites_wildcard_replace( $_POST['bp_member_invites_custom_subject'] );
	}
	return apply_filters( 'bp_invites_member_invite_filter_subject', $subject, $email );
}
add_filter( 'bp_email_set_subject', 'bp_invites_member_invite_filter_subject', 99, 2 );

/**
 * Changes the content based on the user type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $subject
 * @param $email
 *
 * @return mixed
 */
function bp_invites_member_invite_filter_content( $content, $email ) {

	if ( isset( $_POST['bp_member_invites_custom_content'] ) && '' !== $_POST['bp_member_invites_custom_content'] && 'invites-member-invite' === $email->get( 'type' ) ) {
		$content = bp_get_member_invites_wildcard_replace( wp_kses( stripslashes( $_POST['bp_member_invites_custom_content'] ), bp_invites_kses_allowed_tags() ), $_POST['custom_user_email'] );
	}

	if ( 'invites-member-invite' === $email->get( 'type' ) ) {
		$content .= '<br>' .
		            bp_get_member_invites_wildcard_replace(
			            wp_kses(
				            sprintf( __( 'To accept this invitation, please <a href="%s">click here</a>.', 'buddyboss' ), '{{invitee.url}}' ),
				            bp_invites_kses_allowed_tags()
			            )
		            );
	}
	return apply_filters( 'bp_invites_member_invite_filter_content', $content, $email );
}
add_filter( 'bp_email_set_content_html', 'bp_invites_member_invite_filter_content', 99, 2 );

/**
 * Changes the content based on the user type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $subject
 * @param $email
 *
 * @return mixed
 */
function bp_invites_member_invite_filter_content_plaintext( $content, $email ) {

	if ( isset( $_POST['bp_member_invites_custom_content'] ) && '' !== $_POST['bp_member_invites_custom_content'] && 'invites-member-invite' === $email->get( 'type' ) ) {
		$content = bp_get_member_invites_wildcard_replace( wp_kses( stripslashes( $_POST['bp_member_invites_custom_content'] ), bp_invites_kses_allowed_tags() ), $_POST['custom_user_email'] );
	}

	if ( 'invites-member-invite' === $email->get( 'type' ) ) {
		$content .= '<br>' . bp_get_member_invites_wildcard_replace( wp_kses( 'You have been invited by {{inviter.name}} to join the [{{{site.name}}}] community.', bp_invites_kses_allowed_tags() ) );
	}

	return apply_filters( 'bp_invites_member_invite_filter_content_plaintext', $content, $email );
}
add_filter( 'bp_email_set_content_plaintext', 'bp_invites_member_invite_filter_content_plaintext', 99, 2 );

/**
 * Passes the invite user avatar.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $avatar
 * @param $data
 *
 * @return \http\Url
 */
function bp_invites_member_invite_set_email_avatar( $avatar, $data ) {

	if ( isset( $_POST['custom_user_avatar'] ) && '' === $avatar && '' !== $_POST['custom_user_avatar'] ) {
		$avatar = esc_url( $_POST['custom_user_avatar'] );
	}
	return apply_filters( 'bp_invites_member_invite_set_email_avatar', $avatar, $data );
}
add_filter( 'bp_email_recipient_get_avatar', 'bp_invites_member_invite_set_email_avatar', 99, 2 );

/**
 * Passes the invite user name.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $name
 * @param $data
 *
 * @return string
 */
function bp_invites_member_invite_set_email_user_name( $name, $data ) {

	if ( isset( $_POST['custom_user_name'] ) && '' === $name && '' !== $_POST['custom_user_name'] ) {
		$name = esc_html( $_POST['custom_user_name'] );
	}
	return apply_filters( 'bp_invites_member_invite_set_email_user_name', $name, $data );
}
add_filter( 'bp_email_recipient_get_name', 'bp_invites_member_invite_set_email_user_name', 99, 2 );

/**
 * Allows html within the invite email content.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $subject
 * @param $email
 *
 * @return array
 */
function bp_invites_kses_allowed_tags() {
	return apply_filters(
		'bp_invites_kses_allowed_tags',
		array(

			// Links
			'a'          => array(
				'href'   => array(),
				'title'  => array(),
				'rel'    => array(),
				'target' => array(),
			),

			// Quotes
			'blockquote' => array(
				'cite' => array(),
			),

			// Code
			'code'       => array(),
			'pre'        => array(),

			// Formatting
			'em'         => array(),
			'strong'     => array(),
			'del'        => array(
				'datetime' => true,
			),

			// Lists
			'ul'         => array(),
			'ol'         => array(
				'start' => true,
			),
			'li'         => array(),

			// Images
			'img'        => array(
				'src'    => true,
				'border' => true,
				'alt'    => true,
				'height' => true,
				'width'  => true,
			),
		)
	);
}
