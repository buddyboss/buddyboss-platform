<?php
/**
 * Groups: Single group "Send Invites" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle the display of a group's Send Invites page.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_groups_screen_group_invite() {

	if ( ! bboss_is_single_item() ) {
		return false;
	}

	$bp = bboss();

	if ( bboss_is_action_variable( 'send', 0 ) ) {

		if ( ! check_admin_referer( 'groups_send_invites', '_wpnonce_send_invites' ) ) {
			return false;
		}

		if ( ! empty( $_POST['friends'] ) ) {
			foreach ( (array) $_POST['friends'] as $friend ) {
				bboss_groups_invite_user(
					array(
						'user_id'  => $friend,
						'group_id' => $bp->groups->current_group->id,
					)
				);
			}
		}

		// Send the invites.
		bboss_groups_send_invites( array( 'group_id' => $bp->groups->current_group->id ) );
		bboss_core_add_message( __( 'Group invites sent.', 'buddyboss-platform' ) );

		/**
		 * Fires after the sending of a group invite inside the group's Send Invites page.
		 *
		 * @since BuddyPress 1.0.0
		 *
		 * @param int $id ID of the group whose members are being displayed.
		 */
		do_action( 'groups_screen_group_invite', $bp->groups->current_group->id );
		bboss_core_redirect( bboss_get_group_permalink( $bp->groups->current_group ) );

	} elseif ( ! bboss_action_variable( 0 ) ) {

		if ( false === bboss_get_group_current_invite_tab() && 'invite' === bboss_current_action() ) {
			bboss_core_redirect( bboss_get_group_permalink( bboss_groups_get_current_group() ) . 'invite/send-invites/' );
		}

		add_action( 'bp_template_content', 'bboss_groups_send_invite_screen_content' );

		/**
		 * Filters the template to load for a group's Send Invites page.
		 *
		 * @param string $value Path to a group's Send Invites template.
		 *
		 * @since BuddyPress 1.0.0
		 */
		bboss_core_load_template( apply_filters( 'groups_template_group_invite', 'groups/single/home' ) );

	} elseif ( in_array( bboss_get_group_current_invite_tab(), array( 'invite', 'send-invites', 'pending-invites' ), true ) ) {

		if ( false === bboss_get_group_current_invite_tab() && 'invite' === bboss_current_action() ) {
			bboss_core_redirect( bboss_get_group_permalink( bboss_groups_get_current_group() ) . 'invite/send-invites/' );
		}

		add_action( 'bp_template_content', 'bboss_groups_send_invite_screen_content' );

		/**
		 * Filters the template to load for a group's Send Invites page.
		 *
		 * @param string $value Path to a group's Send Invites template.
		 *
		 * @since BuddyPress 1.0.0
		 */
		bboss_core_load_template( apply_filters( 'groups_template_group_invite', 'groups/single/home' ) );
	} else {
		bboss_do_404();
	}
}

function bboss_groups_send_invite_screen_content() {
	bboss_get_template_part( 'groups/single/invite' );
}

/**
 * Process group invitation removal requests.
 *
 * Note that this function is only used when JS is disabled. Normally, clicking
 * Remove Invite removes the invitation via AJAX.
 *
 * @since BuddyPress 2.0.0
 */
function bboss_groups_remove_group_invite() {
	if ( ! bboss_is_group_invites() ) {
		return;
	}

	if ( ! bboss_is_action_variable( 'remove', 0 ) || ! is_numeric( bboss_action_variable( 1 ) ) ) {
		return;
	}

	if ( ! check_admin_referer( 'groups_invite_uninvite_user' ) ) {
		return false;
	}

	$friend_id = intval( bboss_action_variable( 1 ) );
	$group_id  = bboss_get_current_group_id();
	$message   = __( 'Invite successfully removed', 'buddyboss-platform' );
	$redirect  = wp_get_referer();
	$error     = false;

	if ( ! bboss_groups_user_can_send_invites( $group_id ) ) {
		$message = __( 'You are not allowed to send or remove invites', 'buddyboss-platform' );
		$error   = 'error';
	} elseif ( bboss_groups_check_for_membership_request( $friend_id, $group_id ) ) {
		$message = __( 'The member requested to join the group', 'buddyboss-platform' );
		$error   = 'error';
	} elseif ( ! bboss_groups_uninvite_user( $friend_id, $group_id ) ) {
		$message = __( 'There was an error removing the invite', 'buddyboss-platform' );
		$error   = 'error';
	}

	bboss_core_add_message( $message, $error );
	bboss_core_redirect( $redirect );
}
add_action( 'bp_screens', 'bboss_groups_remove_group_invite' );
