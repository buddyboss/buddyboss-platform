<?php
/**
 * Groups: Single group "Manage > Delete" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Handle the display of the Delete Group page.
 *
 * @since BuddyPress 1.0.0
 */
function groups_screen_group_admin_delete_group() {

	if ( 'delete-group' != bp_get_group_current_admin_tab() ) {
		return false;
	}

	if ( ! bp_is_item_admin() && ! bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	$bp = buddypress();

	if ( isset( $_REQUEST['delete-group-button'] ) && isset( $_REQUEST['delete-group-understand'] ) ) {

		// Check the nonce first.
		if ( ! check_admin_referer( 'groups_delete_group' ) ) {
			return false;
		}

		/**
		 * Fires before the deletion of a group from the Delete Group page.
		 *
		 * @since BuddyPress 1.5.0
		 *
		 * @param int $id ID of the group being deleted.
		 */
		do_action( 'groups_before_group_deleted', $bp->groups->current_group->id );

		// Delete group forum
		if ( isset( $_REQUEST['delete-group-forum-understand'] ) ) {
			$forum_ids = bbp_get_group_forum_ids( $bp->groups->current_group->id );
			foreach ( $forum_ids as $forum_id ) {
				wp_delete_post( $forum_id, true );
			}
		}

		// Group admin has deleted the group, now do it.
		if ( ! groups_delete_group( $bp->groups->current_group->id ) ) {
			bp_core_add_message( __( 'There was an error deleting the group. Please try again.', 'buddyboss' ), 'error' );
		} else {
			bp_core_add_message( __( 'The group was deleted successfully.', 'buddyboss' ) );

			/**
			 * Fires after the deletion of a group from the Delete Group page.
			 *
			 * @since BuddyPress 1.0.0
			 *
			 * @param int $id ID of the group being deleted.
			 */
			do_action( 'groups_group_deleted', $bp->groups->current_group->id );

			bp_core_redirect( trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) );
		}

		bp_core_redirect( trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) );
	}

	/**
	 * Fires before the loading of the Delete Group page template.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $id ID of the group that is being displayed.
	 */
	do_action( 'groups_screen_group_admin_delete_group', $bp->groups->current_group->id );

	/**
	 * Filters the template to load for the Delete Group page.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value Path to the Delete Group template.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_admin_delete_group', 'groups/single/home' ) );
}
add_action( 'bp_screens', 'groups_screen_group_admin_delete_group' );
