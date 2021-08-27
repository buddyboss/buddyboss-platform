<?php
/**
 * Groups: Single group "Manage > Settings" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Handle the display of a group's admin/group-settings page.
 *
 * @since BuddyPress 1.0.0
 */
function groups_screen_group_admin_settings() {

	if ( 'group-settings' != bp_get_group_current_admin_tab() ) {
		return false;
	}

	if ( ! bp_is_item_admin() ) {
		return false;
	}

	$bp = buddypress();

	// If the edit form has been submitted, save the edited details.
	if ( isset( $_POST['save'] ) ) {
		$enable_forum = ( isset( $_POST['group-show-forum'] ) ) ? 1 : 0;
		$parent_id    = ( isset( $_POST['bp-groups-parent'] ) ) ? $_POST['bp-groups-parent'] : 0;

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_status = apply_filters( 'groups_allowed_status', array( 'public', 'private', 'hidden' ) );
		$status         = ( in_array( $_POST['group-status'], (array) $allowed_status ) ) ? $_POST['group-status'] : 'public';

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_invite_status = apply_filters( 'groups_allowed_invite_status', array( 'members', 'mods', 'admins' ) );
		$invite_status         = isset( $_POST['group-invite-status'] ) && in_array( $_POST['group-invite-status'], (array) $allowed_invite_status ) ? $_POST['group-invite-status'] : 'members';

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_activity_feed_status = apply_filters( 'groups_allowed_activity_feed_status', array( 'members', 'mods', 'admins' ) );
		$activity_feed_status         = isset( $_POST['group-activity-feed-status'] ) && in_array( $_POST['group-activity-feed-status'], (array) $allowed_activity_feed_status ) ? $_POST['group-activity-feed-status'] : 'members';

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_media_status = apply_filters( 'groups_allowed_media_status', array( 'members', 'mods', 'admins' ) );
		$media_status         = isset( $_POST['group-media-status'] ) && in_array( $_POST['group-media-status'], (array) $allowed_media_status ) ? $_POST['group-media-status'] : 'members';

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_document_status = apply_filters( 'groups_allowed_document_status', array( 'members', 'mods', 'admins' ) );
		$document_status         = isset( $_POST['group-document-status'] ) && in_array( $_POST['group-document-status'], (array) $allowed_document_status ) ? $_POST['group-document-status'] : 'members';

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_video_status    = apply_filters( 'groups_allowed_video_status', array( 'members', 'mods', 'admins' ) );
		$post_group_video_status = filter_input( INPUT_POST, 'group-video-status', FILTER_SANITIZE_STRING );
		$video_status            = ! empty( $post_group_video_status ) && in_array( $post_group_video_status, (array) $allowed_video_status, true ) ? $post_group_video_status : 'members';

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_album_status = apply_filters( 'groups_allowed_album_status', array( 'members', 'mods', 'admins' ) );
		$album_status         = isset( $_POST['group-album-status'] ) && in_array( $_POST['group-album-status'], (array) $allowed_album_status ) ? $_POST['group-album-status'] : 'members';

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_message_status = apply_filters( 'groups_allowed_message_status', array( 'mods', 'admins', 'members' ) );
		$message_status         = isset( $_POST['group-message-status'] ) && in_array( $_POST['group-message-status'], (array) $allowed_message_status ) ? $_POST['group-message-status'] : 'mods';


		// Check the nonce.
		if ( ! check_admin_referer( 'groups_edit_group_settings' ) ) {
			return false;
		}

		/*
		 * Save group types.
		 *
		 * Ensure we keep types that have 'show_in_create_screen' set to false.
		 */
		$current_types = bp_groups_get_group_type( bp_get_current_group_id(), false );
		$current_types = array_intersect( bp_groups_get_group_types( array( 'show_in_create_screen' => false ) ), (array) $current_types );
		if ( isset( $_POST['group-types'] ) ) {
			$current_types = array_merge( $current_types, $_POST['group-types'] );

			// Set group types.
			bp_groups_set_group_type( bp_get_current_group_id(), $current_types );

			// Group types disabled, so this means we want to wipe out all group types.
		} elseif ( false === bp_disable_group_type_creation() ) {
			/*
			 * Passing a blank string will wipe out all types for the group.
			 *
			 * Ensure we keep types that have 'show_in_create_screen' set to false.
			 */
			$current_types = empty( $current_types ) ? '' : $current_types;

			// Set group types.
			bp_groups_set_group_type( bp_get_current_group_id(), $current_types );
		}

		if ( ! groups_edit_group_settings( $_POST['group-id'], $enable_forum, $status, $invite_status, $activity_feed_status, $parent_id, $media_status, $document_status, $video_status, $album_status, $message_status ) ) {
			bp_core_add_message( __( 'There was an error updating group settings. Please try again.', 'buddyboss' ), 'error' );
		} else {
			bp_core_add_message( __( 'Group settings were successfully updated.', 'buddyboss' ) );
		}

		/**
		 * Fires before the redirect if a group settings has been edited and saved.
		 *
		 * @since BuddyPress 1.0.0
		 *
		 * @param int $id ID of the group that was edited.
		 */
		do_action( 'groups_group_settings_edited', $bp->groups->current_group->id );

		bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/group-settings/' );
	}

	/**
	 * Fires before the loading of the group admin/group-settings page template.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $id ID of the group that is being displayed.
	 */
	do_action( 'groups_screen_group_admin_settings', $bp->groups->current_group->id );

	/**
	 * Filters the template to load for a group's admin/group-settings page.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value Path to a group's admin/group-settings template.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_admin_settings', 'groups/single/home' ) );
}
add_action( 'bp_screens', 'groups_screen_group_admin_settings' );
