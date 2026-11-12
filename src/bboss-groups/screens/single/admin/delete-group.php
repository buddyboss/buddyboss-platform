<?php
/**
 * Groups: Single group "Manage > Delete" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle the display of the Delete Group page.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_groups_screen_group_admin_delete_group() {

	if ( 'delete-group' != bboss_get_group_current_admin_tab() ) {
		return false;
	}

	if ( ! bboss_is_item_admin() && ! bboss_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	$bp = bboss();

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
			$forum_ids = bboss_bbp_get_group_forum_ids( $bp->groups->current_group->id );
			foreach ( $forum_ids as $forum_id ) {
				wp_delete_post( $forum_id, true );
			}
		}

		// Group admin has deleted the group, now do it.
		if ( ! bboss_groups_delete_group( $bp->groups->current_group->id ) ) {
			bboss_core_add_message( __( 'There was an error deleting the group. Please try again.', 'buddyboss' ), 'error' );
		} else {
			bboss_core_add_message( __( 'The group was deleted successfully.', 'buddyboss' ) );

			/**
			 * Fires after the deletion of a group from the Delete Group page.
			 *
			 * @since BuddyPress 1.0.0
			 *
			 * @param int $id ID of the group being deleted.
			 */
			do_action( 'groups_group_deleted', $bp->groups->current_group->id );

			bboss_core_redirect( trailingslashit( bboss_loggedin_user_domain() . bboss_get_groups_slug() ) );
		}

		bboss_core_redirect( trailingslashit( bboss_loggedin_user_domain() . bboss_get_groups_slug() ) );
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
	bboss_core_load_template( apply_filters( 'groups_template_group_admin_delete_group', 'groups/single/home' ) );
}
add_action( 'bp_screens', 'bboss_groups_screen_group_admin_delete_group' );
