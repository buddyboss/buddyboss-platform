<?php
/**
 * Groups: User's "Groups > Invites" screen handler
 *
 * @package BuddyBoss\Group\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle the loading of a user's Groups > Invites page.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_groups_screen_group_invites() {
	$group_id = (int) bboss_action_variable( 1 );

	if ( bboss_is_action_variable( 'accept' ) && is_numeric( $group_id ) ) {
		// Check the nonce.
		if ( ! check_admin_referer( 'groups_accept_invite' ) ) {
			return false;
		}

		if ( ! bboss_groups_accept_invite( bboss_loggedin_user_id(), $group_id ) ) {
			bboss_core_add_message( __( 'Group invite could not be accepted', 'buddyboss' ), 'error' );
		} else {
			// Record this in activity feeds.
			$group = bboss_groups_get_group( $group_id );

			/* translators: %s: group link. */
			bboss_core_add_message( sprintf( __( 'Group invite accepted. Visit %s.', 'buddyboss' ), bboss_get_group_link( $group ) ) );

			if ( bboss_is_active( 'activity' ) ) {
				bboss_groups_record_activity(
					array(
						'type'    => 'joined_group',
						'item_id' => $group->id,
					)
				);
			}
		}

		if ( isset( $_GET['redirect_to'] ) ) {
			$redirect_to = urldecode( $_GET['redirect_to'] );
		} else {
			$redirect_to = trailingslashit( bboss_loggedin_user_domain() . bboss_get_groups_slug() . '/' . bboss_current_action() );
		}

		bboss_core_redirect( $redirect_to );

	} elseif ( bboss_is_action_variable( 'reject' ) && is_numeric( $group_id ) ) {
		// Check the nonce.
		if ( ! check_admin_referer( 'groups_reject_invite' ) ) {
			return false;
		}

		if ( ! bboss_groups_reject_invite( bboss_loggedin_user_id(), $group_id ) ) {
			bboss_core_add_message( __( 'Group invite could not be rejected', 'buddyboss' ), 'error' );
		} else {
			bboss_core_add_message( __( 'Group invite rejected', 'buddyboss' ) );
		}

		if ( isset( $_GET['redirect_to'] ) ) {
			$redirect_to = urldecode( $_GET['redirect_to'] );
		} else {
			$redirect_to = trailingslashit( bboss_loggedin_user_domain() . bboss_get_groups_slug() . '/' . bboss_current_action() );
		}

		bboss_core_redirect( $redirect_to );
	}

	/**
	 * Fires before the loading of a users Groups > Invites template.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $group_id ID of the group being displayed
	 */
	do_action( 'groups_screen_group_invites', $group_id );

	/**
	 * Filters the template to load for a users Groups > Invites page.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value Path to a users Groups > Invites page template.
	 */
	bboss_core_load_template( apply_filters( 'groups_template_group_invites', 'members/single/home' ) );
}
