<?php
/**
 * Group invitations class.
 *
 * @package BuddyBoss
 * @subpackage Core
 * @since BuddyBoss 1.3.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Group invitations class.
 *
 * An extension of the core Invitations class that adapts the
 * core logic to accommodate group invitation behavior.
 *
 * @since BuddyBoss 1.3.5
 */
class BP_Groups_Invitation_Manager extends BP_Invitation_Manager {
	/**
	 * Construct parameters.
	 *
	 * @since BuddyBoss 1.3.5
	 *
	 * @param array|string $args.
	 */
	public function __construct( $args = '' ) {
		parent::__construct();
	}

	/**
	 * This is where custom actions are added to run when notifications of an
	 * invitation or request need to be generated & sent.
	 *
	 * @since BuddyBoss 1.3.5
	 *
	 * @param int $id The ID of the invitation to mark as sent.
	 * @return bool True on success, false on failure.
	 */
	public function run_send_action( BP_Invitation $invitation ) {
		// Notify group admins of the pending request
		if ( 'request' === $invitation->type ) {
			$admins = groups_get_group_admins( $invitation->item_id );

			foreach ( $admins as $admin ) {
				groups_notification_new_membership_request( $invitation->user_id, $admin->user_id, $invitation->item_id, $invitation->id );
			}
			return true;

		// Notify the invitee of the invitation.
		} else {
			$group = groups_get_group( $invitation->item_id );
			groups_notification_group_invites( $group, $invitation->user_id, $invitation->inviter_id );
			return true;
		}
	}

	/**
	 * This is where custom actions are added to run when an invitation
	 * or request is accepted.
	 *
	 * @since BuddyBoss 1.3.5
	 *
	 * @param string $type Are we accepting an invitation or request?
	 * @param array  $r    Parameters that describe the invitation being accepted.
	 * @return bool True on success, false on failure.
	 */
	public function run_acceptance_action( $type, $r  ) {
		// If the user is already a member (because BP at one point allowed two invitations to
		// slip through), return early.
		if ( groups_is_user_member( $r['user_id'], $r['item_id'] ) ) {
			return true;
		}

		// Create the new membership.
		$member = new BP_Groups_Member( $r['user_id'], $r['item_id'] );

		if ( 'request' === $type ) {
			$member->accept_request();
		} else {
			$member->accept_invite();
		}

		if ( ! $member->save() ) {
			return false;
		}

		// Get the invitation.
		$r['type']                  = $type;
		$invites                    = groups_get_invites( $r );
		$current_invite             = new stdClass();
		$current_invite->id         = 0;
		$current_invite->inviter_id = 0;
		if ( $invites ) {
			$current_invite = current( $invites );
		}

		// Tracking group invitation accept.
		groups_update_membermeta( $member->id, 'membership_accept_date', bp_core_current_time() );

		if ( 'request' === $type ) {

			// Migrate the requested date from invite meta to group member meta.
			$requested_date = invitation_get_invitemeta( $current_invite->id, 'requested_date' );
			groups_update_membermeta( $member->id, 'membership_requested_date', $requested_date );

			/**
			 * Fires after a group membership request has been accepted.
			 *
			 * @since BuddyPress 1.0.0
			 *
			 * @param int  $user_id  ID of the user who accepted membership.
			 * @param int  $group_id ID of the group that was accepted membership to.
			 * @param bool $value    If membership was accepted.
			 */
			do_action( 'groups_membership_accepted', $r['user_id'], $r['item_id'], true );
		} else {

			// Migrate the invited date from invite meta to group member meta.
			$invited_date = invitation_get_invitemeta( $current_invite->id, 'invited_date' );
			groups_update_membermeta( $member->id, 'membership_invited_date', $invited_date );

			/**
			 * Fires after a user has accepted a group invite.
			 *
			 * @since BuddyPress 1.0.0
			 * @since BuddyPress 2.8.0 The $inviter_id arg was added.
			 *
			 * @param int $user_id    ID of the user who accepted the group invite.
			 * @param int $group_id   ID of the group being accepted to.
			 * @param int $inviter_id ID of the user who invited this user to the group.
			 */
			do_action( 'groups_accept_invite', $r['user_id'], $r['item_id'], $current_invite->inviter_id );
		}

		// Modify group meta.
		groups_update_groupmeta( $r['item_id'], 'last_activity', bp_core_current_time() );

		return true;
	}

	/**
	 * With group invitations, we don't need to keep the old record, so we delete rather than
	 * mark invitations as "accepted."
	 *
	 * @since BuddyBoss 1.3.5
	 *
	 * @see BP_Invitation::mark_accepted_by_data()
	 *      for a description of arguments.
	 *
	 * @param array $args.
	 */
	public function mark_accepted( $args ) {
		// Delete all existing invitations/requests to this group for this user.
		$this->delete( array(
			'user_id' => $args['user_id'],
			'item_id' => $args['item_id'],
			'type'    => 'all'
		) );
	}

	/**
	 * Should this invitation be created?
	 *
	 * @since BuddyBoss 1.3.5
	 *
	 * @param array $args.
	 * @return bool
	 */
	public function allow_invitation( $args ) {
		// Does the inviter have this capability?
		if ( ! bp_user_can( $args['inviter_id'], 'groups_send_invitation', array( 'group_id' => $args['item_id'] ) ) ) {
			return false;
		}

		// Is the invited user eligible to receive an invitation?
		if ( ! bp_user_can( $args['user_id'], 'groups_receive_invitation', array( 'group_id' => $args['item_id'] ) ) ) {
			return false;
		}

		// Prevent duplicated invitations.
		if ( groups_check_has_invite_from_user( $args['user_id'], $args['item_id'], $args['inviter_id'], 'all' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Should this request be created?
	 *
	 * @since BuddyBoss 1.3.5
	 *
	 * @param array $args.
	 * @return bool.
	 */
	public function allow_request( $args ) {
		// Does the requester have this capability? (Also checks for duplicates.)
		if ( ! bp_user_can( $args['user_id'], 'groups_request_membership', array( 'group_id' => $args['item_id'] ) ) ) {
			return false;
		}

		return true;
	}
}
