<?php
/**
 * Groups: Leave action
 *
 * @package BuddyBoss\Group\Actions
 * @since BuddyPress 3.0.0
 */

/**
 * Catch and process "Leave Group" button clicks.
 *
 * When a group member clicks on the "Leave Group" button from a group's page,
 * this function is run.
 *
 * Note: When leaving a group from the group directory, AJAX is used and
 * another function handles this. See {@link bp_legacy_theme_ajax_joinleave_group()}.
 *
 * @since BuddyPress 1.2.4
 *
 * @return bool
 */
function groups_action_leave_group() {
	if ( ! bp_is_single_item() || ! bp_is_groups_component() || ! bp_is_current_action( 'leave-group' ) ) {
		return false;
	}

	// Nonce check.
	if ( ! check_admin_referer( 'groups_leave_group' ) ) {
		return false;
	}

	// User wants to leave any group.
	if ( groups_is_user_member( bp_loggedin_user_id(), bp_get_current_group_id() ) ) {
		$bp = buddypress();

		// Stop sole admins from abandoning their group.
		$group_admins = groups_get_group_admins( bp_get_current_group_id() );

		if ( 1 == count( $group_admins ) && $group_admins[0]->user_id == bp_loggedin_user_id() ) {
			bp_core_add_message( __( 'This group must have at least one organizer.', 'buddyboss' ), 'error' );
		} elseif ( ! groups_leave_group( $bp->groups->current_group->id ) ) {
			bp_core_add_message( __( 'There was an error leaving the group.', 'buddyboss' ), 'error' );
		} else {
			bp_core_add_message( __( 'You left the group.', 'buddyboss' ) );
		}

		$group    = groups_get_current_group();
		$redirect = bp_get_group_permalink( $group );

		if ( ! $group->is_visible ) {
			$redirect = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() );
		}

		bp_core_redirect( $redirect );
	}

	/** This filter is documented in bp-groups/bp-groups-actions.php */
	bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );
}
add_action( 'bp_actions', 'groups_action_leave_group' );
