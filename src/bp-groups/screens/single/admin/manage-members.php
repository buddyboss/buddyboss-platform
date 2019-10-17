<?php
/**
 * Groups: Single group "Manage > Members" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * This function handles actions related to member management on the group admin.
 *
 * @since BuddyPress 1.0.0
 */
function groups_screen_group_admin_manage_members() {

	if ( 'manage-members' != bp_get_group_current_admin_tab() ) {
		return false;
	}

	if ( ! bp_is_item_admin() ) {
		return false;
	}

	$bp = buddypress();

	if ( bp_action_variable( 1 ) && bp_action_variable( 2 ) && bp_action_variable( 3 ) ) {
		if ( bp_is_action_variable( 'promote', 1 ) && ( bp_is_action_variable( 'mod', 2 ) || bp_is_action_variable( 'admin', 2 ) ) && is_numeric( bp_action_variable( 3 ) ) ) {
			$user_id = bp_action_variable( 3 );
			$status  = bp_action_variable( 2 );

			// Check the nonce first.
			if ( ! check_admin_referer( 'groups_promote_member' ) ) {
				return false;
			}

			// Promote a user.
			if ( ! groups_promote_member( $user_id, $bp->groups->current_group->id, $status ) ) {
				bp_core_add_message( __( 'There was an error when promoting that user. Please try again.', 'buddyboss' ), 'error' );
			} else {
				bp_core_add_message( __( 'User promoted successfully', 'buddyboss' ) );
			}

			/**
			 * Fires before the redirect after a group member has been promoted.
			 *
			 * @since BuddyPress 1.0.0
			 *
			 * @param int $user_id ID of the user being promoted.
			 * @param int $id      ID of the group user is promoted within.
			 */
			do_action( 'groups_promoted_member', $user_id, $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/manage-members/' );
		}
	}

	if ( bp_action_variable( 1 ) && bp_action_variable( 2 ) ) {
		if ( bp_is_action_variable( 'demote', 1 ) && is_numeric( bp_action_variable( 2 ) ) ) {
			$user_id = bp_action_variable( 2 );

			// Check the nonce first.
			if ( ! check_admin_referer( 'groups_demote_member' ) ) {
				return false;
			}

			// Stop sole admins from abandoning their group.
			$group_admins = groups_get_group_admins( $bp->groups->current_group->id );
			if ( 1 == count( $group_admins ) && $group_admins[0]->user_id == $user_id ) {
				bp_core_add_message( __( 'This group must have at least one organizer', 'buddyboss' ), 'error' );
			}

			// Demote a user.
			elseif ( ! groups_demote_member( $user_id, $bp->groups->current_group->id ) ) {
				bp_core_add_message( __( 'There was an error when demoting that user. Please try again.', 'buddyboss' ), 'error' );
			} else {
				bp_core_add_message( __( 'User demoted successfully', 'buddyboss' ) );
			}

			/**
			 * Fires before the redirect after a group member has been demoted.
			 *
			 * @since BuddyPress 1.0.0
			 *
			 * @param int $user_id ID of the user being demoted.
			 * @param int $id      ID of the group user is demoted within.
			 */
			do_action( 'groups_demoted_member', $user_id, $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/manage-members/' );
		}

		if ( bp_is_action_variable( 'ban', 1 ) && is_numeric( bp_action_variable( 2 ) ) ) {
			$user_id = bp_action_variable( 2 );

			// Check the nonce first.
			if ( ! check_admin_referer( 'groups_ban_member' ) ) {
				return false;
			}

			// Ban a user.
			if ( ! groups_ban_member( $user_id, $bp->groups->current_group->id ) ) {
				bp_core_add_message( __( 'There was an error when banning that user. Please try again.', 'buddyboss' ), 'error' );
			} else {
				bp_core_add_message( __( 'User banned successfully', 'buddyboss' ) );
			}

			/**
			 * Fires before the redirect after a group member has been banned.
			 *
			 * @since BuddyPress 1.0.0
			 *
			 * @param int $user_id ID of the user being banned.
			 * @param int $id      ID of the group user is banned from.
			 */
			do_action( 'groups_banned_member', $user_id, $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/manage-members/' );
		}

		if ( bp_is_action_variable( 'unban', 1 ) && is_numeric( bp_action_variable( 2 ) ) ) {
			$user_id = bp_action_variable( 2 );

			// Check the nonce first.
			if ( ! check_admin_referer( 'groups_unban_member' ) ) {
				return false;
			}

			// Remove a ban for user.
			if ( ! groups_unban_member( $user_id, $bp->groups->current_group->id ) ) {
				bp_core_add_message( __( 'There was an error when unbanning that user. Please try again.', 'buddyboss' ), 'error' );
			} else {
				bp_core_add_message( __( 'User ban removed successfully', 'buddyboss' ) );
			}

			/**
			 * Fires before the redirect after a group member has been unbanned.
			 *
			 * @since BuddyPress 1.0.0
			 *
			 * @param int $user_id ID of the user being unbanned.
			 * @param int $id      ID of the group user is unbanned from.
			 */
			do_action( 'groups_unbanned_member', $user_id, $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/manage-members/' );
		}

		if ( bp_is_action_variable( 'remove', 1 ) && is_numeric( bp_action_variable( 2 ) ) ) {
			$user_id = bp_action_variable( 2 );

			// Check the nonce first.
			if ( ! check_admin_referer( 'groups_remove_member' ) ) {
				return false;
			}

			// Remove a user.
			if ( ! groups_remove_member( $user_id, $bp->groups->current_group->id ) ) {
				bp_core_add_message( __( 'There was an error removing that user from the group. Please try again.', 'buddyboss' ), 'error' );
			} else {
				bp_core_add_message( __( 'User removed successfully', 'buddyboss' ) );
			}

			/**
			 * Fires before the redirect after a group member has been removed.
			 *
			 * @since BuddyPress 1.2.6
			 *
			 * @param int $user_id ID of the user being removed.
			 * @param int $id      ID of the group the user is removed from.
			 */
			do_action( 'groups_removed_member', $user_id, $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/manage-members/' );
		}
	}

	/**
	 * Fires before the loading of a group's manage members template.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $id ID of the group whose manage members page is being displayed.
	 */
	do_action( 'groups_screen_group_admin_manage_members', $bp->groups->current_group->id );

	/**
	 * Filters the template to load for a group's manage members page.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value Path to a group's manage members template.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_admin_manage_members', 'groups/single/home' ) );
}
add_action( 'bp_screens', 'groups_screen_group_admin_manage_members' );
