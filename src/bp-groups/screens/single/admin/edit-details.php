<?php
/**
 * Groups: Single group "Manage > Details" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Handle the display of a group's admin/edit-details page.
 *
 * @since BuddyPress 1.0.0
 */
function groups_screen_group_admin_edit_details() {

	if ( 'edit-details' != bp_get_group_current_admin_tab() ) {
		return false;
	}

	if ( bp_is_item_admin() ) {

		$bp = buddypress();

		// If the edit form has been submitted, save the edited details.
		if ( isset( $_POST['save'] ) ) {
			// Check the nonce.
			if ( ! check_admin_referer( 'groups_edit_group_details' ) ) {
				return false;
			}

			$group_notify_members = isset( $_POST['group-notify-members'] ) ? (int) $_POST['group-notify-members'] : 0;

			// Name and description are required and may not be empty.
			if ( empty( $_POST['group-name'] ) ) {
				bp_core_add_message( __( 'Groups must have a name and a description. Please try again.', 'buddyboss' ), 'error' );
			} elseif ( ! groups_edit_base_group_details(
				array(
					'group_id'       => $_POST['group-id'],
					'name'           => $_POST['group-name'],
					'slug'           => null, // @TODO: Add to settings pane? If yes, editable by site admin only, or allow group admins to do this?
					'description'    => $_POST['group-desc'],
					'notify_members' => $group_notify_members,
				)
			) ) {
				bp_core_add_message( __( 'There was an error updating group details. Please try again.', 'buddyboss' ), 'error' );
			} else {
				bp_core_add_message( __( 'Group details were successfully updated.', 'buddyboss' ) );
			}

			/**
			 * Fires before the redirect if a group details has been edited and saved.
			 *
			 * @since BuddyPress 1.0.0
			 *
			 * @param int $id ID of the group that was edited.
			 */
			do_action( 'groups_group_details_edited', $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/edit-details/' );
		}

		/**
		 * Fires before the loading of the group admin/edit-details page template.
		 *
		 * @since BuddyPress 1.0.0
		 *
		 * @param int $id ID of the group that is being displayed.
		 */
		do_action( 'groups_screen_group_admin_edit_details', $bp->groups->current_group->id );

		/**
		 * Filters the template to load for a group's admin/edit-details page.
		 *
		 * @since BuddyPress 1.0.0
		 *
		 * @param string $value Path to a group's admin/edit-details template.
		 */
		bp_core_load_template( apply_filters( 'groups_template_group_admin', 'groups/single/home' ) );
	}
}
add_action( 'bp_screens', 'groups_screen_group_admin_edit_details' );
