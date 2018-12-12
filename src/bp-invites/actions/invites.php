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
	$query_string = array();
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

		$email = $value['email'];
		$name = $value['name'];
		$query_string[] = $email;
		$inviter_name = bp_core_get_user_displayname( bp_loggedin_user_id() );

		$message .= '

'.bp_get_member_invites_wildcard_replace( stripslashes( strip_tags( bp_get_invites_member_invite_url() ) ), $email );



		$inviter_name = bp_core_get_user_displayname( bp_loggedin_user_id() );
		$site_name    = get_bloginfo( 'name' );
		$inviter_url  = bp_loggedin_user_domain();

		$email_encode = urlencode( $email );

		// set post variable
		$_POST['custom_user_email'] = $email;

		$accept_link  = add_query_arg( array(
			'bp-invites' => 'accept-member-invitation',
			'email'    => $email_encode,
		), bp_get_root_domain() . '/' . bp_get_signup_slug() . '/' );
		$accept_link  = apply_filters( 'bp_member_invitation_accept_url', $accept_link );

		$args = array(
			'tokens' => array(
				'inviter.name' => $inviter_name,
				//'site.name'    => get_bloginfo('name'),
				//'site.url'     => site_url(),
				'invitee.url'  => $accept_link,
			),
		);

		// Send invitation email.
		bp_send_email( 'invites-member-invite', $email, $args );

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
		update_post_meta( $post_id, '_bp_invitee_status', 0 );
	}

	bp_core_redirect( bp_displayed_user_domain() . 'invites/sent-invites?email='.implode (", ", $query_string ) );

}
add_action( 'bp_actions', 'bp_member_invite_submit' );

/**
 * Filter for changing the subject based on the user typed.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param $subject
 * @param $email
 *
 * @return mixed|void
 */
function bp_invites_member_invite_filter_subject( $subject, $email ) {

	if ( isset( $_POST['bp_member_invites_custom_subject'] ) && '' !== $_POST['bp_member_invites_custom_subject'] && 'invites-member-invite' === $email->get( 'type' ) ) {
		$subject = bp_get_member_invites_wildcard_replace( $_POST['bp_member_invites_custom_subject'] );
	}
	return apply_filters( 'bp_invites_member_invite_filter_subject', $subject, $email );
}
add_filter( 'bp_email_set_subject', 'bp_invites_member_invite_filter_subject', 99, 2) ;

/**
 * Filter for changing the content based on the user typed.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param $subject
 * @param $email
 *
 * @return mixed|void
 */
function bp_invites_member_invite_filter_content( $content, $email ) {

	if ( isset( $_POST['bp_member_invites_custom_content'] ) && '' !== $_POST['bp_member_invites_custom_content'] && 'invites-member-invite' === $email->get( 'type' ) ) {
		$content = bp_get_member_invites_wildcard_replace ( wp_kses( $_POST['bp_member_invites_custom_content'], bp_invites_kses_allowed_tags() ), $_POST['custom_user_email'] );
	}
	return apply_filters( 'bp_invites_member_invite_filter_content', $content, $email );
}
add_filter( 'bp_email_set_content_html', 'bp_invites_member_invite_filter_content', 99, 2 );

/**
 * Filter for changing the content based on the user typed.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param $subject
 * @param $email
 *
 * @return mixed|void
 */
function bp_invites_member_invite_filter_content_plaintext( $content, $email ) {

	if ( isset( $_POST['bp_member_invites_custom_content'] ) && '' !== $_POST['bp_member_invites_custom_content'] && 'invites-member-invite' === $email->get( 'type' ) ) {
		$content = bp_get_member_invites_wildcard_replace( wp_kses( $_POST['bp_member_invites_custom_content'], bp_invites_kses_allowed_tags() ), $_POST['custom_user_email'] );
	}

	return apply_filters( 'bp_invites_member_invite_filter_content_plaintext', $content, $email );
}
add_filter( 'bp_email_set_content_plaintext', 'bp_invites_member_invite_filter_content_plaintext', 99, 2 );

/**
 * Function for allow the html to text area.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param $subject
 * @param $email
 *
 * @return array
 */
function bp_invites_kses_allowed_tags() {
	return apply_filters( 'bp_invites_kses_allowed_tags', array(

		// Links
		'a' => array(
			'href'     => array(),
			'title'    => array(),
			'rel'      => array(),
			'target'   => array()
		),

		// Quotes
		'blockquote'   => array(
			'cite'     => array()
		),

		// Code
		'code'         => array(),
		'pre'          => array(),

		// Formatting
		'em'           => array(),
		'strong'       => array(),
		'del'          => array(
			'datetime' => true,
		),

		// Lists
		'ul'           => array(),
		'ol'           => array(
			'start'    => true,
		),
		'li'           => array(),

		// Images
		'img'          => array(
			'src'      => true,
			'border'   => true,
			'alt'      => true,
			'height'   => true,
			'width'    => true,
		)
	) );
}
