<?php
/**
 * Groups: Create action
 *
 * @package BuddyBoss\Group\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Catch and process group creation form submissions.
 *
 * @since BuddyPress 1.2.0
 *
 * @return bool
 */
function bboss_groups_action_create_group() {

	// If we're not at domain.org/groups/create/ then return false.
	if ( ! bboss_is_groups_component() || ! bboss_is_current_action( 'create' ) ) {
		return false;
	}

	if ( ! is_user_logged_in() ) {
		return false;
	}

	if ( ! bboss_user_can_create_groups() ) {
		bboss_core_add_message( __( 'Sorry, you are not allowed to create groups.', 'buddyboss' ), 'error' );
		bboss_core_redirect( bboss_get_groups_directory_permalink() );
	}

	$bp = bboss();

	// Make sure creation steps are in the right order.
	bboss_groups_action_sort_creation_steps();

	// If no current step is set, reset everything so we can start a fresh group creation.
	$bp->groups->current_create_step = bboss_action_variable( 1 );
	if ( ! bboss_get_groups_current_create_step() ) {
		unset( $bp->groups->current_create_step );
		unset( $bp->groups->completed_create_steps );

		setcookie( 'bp_new_group_id', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
		setcookie( 'bp_completed_create_steps', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );

		$reset_steps = true;
		$keys        = array_keys( $bp->groups->group_creation_steps );
		bboss_core_redirect( trailingslashit( bboss_get_groups_directory_permalink() . 'create/step/' . array_shift( $keys ) ) );
	}

	// If this is a creation step that is not recognized, just redirect them back to the first screen.
	if ( bboss_get_groups_current_create_step() && empty( $bp->groups->group_creation_steps[ bboss_get_groups_current_create_step() ] ) ) {
		bboss_core_add_message( __( 'There was an error saving group details. Please try again.', 'buddyboss' ), 'error' );
		bboss_core_redirect( trailingslashit( bboss_get_groups_directory_permalink() . 'create' ) );
	}

	// Fetch the currently completed steps variable.
	if ( isset( $_COOKIE['bp_completed_create_steps'] ) && ! isset( $reset_steps ) ) {
		$completed_create_steps             = json_decode( base64_decode( wp_unslash( $_COOKIE['bp_completed_create_steps'] ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$bp->groups->completed_create_steps = is_array( $completed_create_steps ) ? map_deep( $completed_create_steps, 'sanitize_text_field' ) : array();
	}

	// Set the ID of the new group, if it has already been created in a previous step.
	if ( bboss_get_new_group_id() ) {
		$bp->groups->current_group = bboss_groups_get_group( $bp->groups->new_group_id );

		// Only allow the group creator to continue to edit the new group.
		if ( ! bboss_is_group_creator( $bp->groups->current_group, bboss_loggedin_user_id() ) ) {
			bboss_core_add_message( __( 'Only the group organizer may continue editing this group.', 'buddyboss' ), 'error' );
			bboss_core_redirect( trailingslashit( bboss_get_groups_directory_permalink() . 'create' ) );
		}
	}

	// If the save, upload or skip button is hit, lets calculate what we need to save.
	if ( isset( $_POST['save'] ) ) {

		// Check the nonce.
		check_admin_referer( 'groups_create_save_' . bboss_get_groups_current_create_step() );

		if ( 'group-details' == bboss_get_groups_current_create_step() ) {
			if ( empty( $_POST['group-name'] ) || ! strlen( trim( $_POST['group-name'] ) ) ) {
				bboss_core_add_message( __( 'Please fill in all of the required fields', 'buddyboss' ), 'error' );
				bboss_core_redirect( trailingslashit( bboss_get_groups_directory_permalink() . 'create/step/' . bboss_get_groups_current_create_step() ) );
			}

			$new_group_id = isset( $bp->groups->new_group_id ) ? $bp->groups->new_group_id : 0;

			if ( ! $bp->groups->new_group_id = bboss_groups_create_group(
				array(
					'group_id'     => $new_group_id,
					'name'         => $_POST['group-name'],
					'description'  => $_POST['group-desc'],
					'slug'         => bboss_groups_check_slug( sanitize_title( wp_unslash( $_POST['group-name'] ) ) ),
					'date_created' => bboss_core_current_time(),
					'status'       => 'public',
				)
			) ) {
				bboss_core_add_message( __( 'There was an error saving group details. Please try again.', 'buddyboss' ), 'error' );
				bboss_core_redirect( trailingslashit( bboss_get_groups_directory_permalink() . 'create/step/' . bboss_get_groups_current_create_step() ) );
			}
		}

		if ( 'group-settings' == bboss_get_groups_current_create_step() ) {
			$group_status       = 'public';
			$group_enable_forum = 1;

			if ( ! isset( $_POST['group-show-forum'] ) ) {
				$group_enable_forum = 0;
			}

			if ( 'private' == $_POST['group-status'] ) {
				$group_status = 'private';
			} elseif ( 'hidden' == $_POST['group-status'] ) {
				$group_status = 'hidden';
			}

			$parent_id = ( isset( $_POST['bp-groups-parent'] ) ) ? $_POST['bp-groups-parent'] : 0;

			if ( ! $bp->groups->new_group_id = bboss_groups_create_group(
				array(
					'group_id'     => $bp->groups->new_group_id,
					'status'       => $group_status,
					'enable_forum' => $group_enable_forum,
					'parent_id'    => $parent_id,
				)
			) ) {
				bboss_core_add_message( __( 'There was an error saving group details. Please try again.', 'buddyboss' ), 'error' );
				bboss_core_redirect( trailingslashit( bboss_get_groups_directory_permalink() . 'create/step/' . bboss_get_groups_current_create_step() ) );
			}

			// Save group types.
			if ( ! empty( $_POST['group-types'] ) ) {
				bboss_groups_set_group_type( $bp->groups->new_group_id, $_POST['group-types'] );
			}

			/**
			 * Filters the allowed invite statuses.
			 *
			 * @since BuddyPress 1.5.0
			 *
			 * @param array $value Array of statuses allowed.
			 *                     Possible values are 'members,
			 *                     'mods', and 'admins'.
			 */
			$allowed_invite_status = bboss_groups_get_settings_status( 'invite' );
			$invite_status         = ! empty( $_POST['group-invite-status'] ) && in_array( $_POST['group-invite-status'], (array) $allowed_invite_status ) ? $_POST['group-invite-status'] : bboss_groups_settings_default_fallback( 'invite_status', current( $allowed_invite_status ) );

			bboss_groups_update_groupmeta( $bp->groups->new_group_id, 'invite_status', $invite_status );

			/**
			 * Filters the allowed activity feed statuses.
			 *
			 * @since BuddyBoss 1.0.0
			 *
			 * @param array $value Array of statuses allowed.
			 *                     Possible values are 'members,
			 *                     'mods', and 'admins'.
			 */
			$allowed_activity_feed_status = bboss_groups_get_settings_status( 'activity_feed' );
			$activity_feed_status         = ! empty( $_POST['group-activity-feed-status'] ) && in_array( $_POST['group-activity-feed-status'], (array) $allowed_activity_feed_status ) ? $_POST['group-activity-feed-status'] : bboss_groups_settings_default_fallback( 'activity_feed_status', current( $allowed_activity_feed_status ) );

			bboss_groups_update_groupmeta( $bp->groups->new_group_id, 'activity_feed_status', $activity_feed_status );

			/**
			* Filters the allowed media statuses.
			 *
			 * @since BuddyBoss 1.0.0
			 *
			 * @param array $value Array of statuses allowed.
			 *                     Possible values are 'members,
			 *                     'mods', and 'admins'.
			 */
			$allowed_media_status = bboss_groups_get_settings_status( 'media' );
			$media_status         = ! empty( $_POST['group-media-status'] ) && in_array( $_POST['group-media-status'], (array) $allowed_media_status ) ? $_POST['group-media-status'] : bboss_groups_settings_default_fallback( 'media_status', current( $allowed_media_status ) );

			bboss_groups_update_groupmeta( $bp->groups->new_group_id, 'media_status', $media_status );

			/**
			 * Filters the allowed document statuses.
			 *
			 * @since BuddyBoss 1.0.0
			 *
			 * @param array $value Array of statuses allowed.
			 *                     Possible values are 'members,
			 *                     'mods', and 'admins'.
			 */
			$allowed_document_status = bboss_groups_get_settings_status( 'document' );
			$document_status         = ! empty( $_POST['group-document-status'] ) && in_array( $_POST['group-document-status'], (array) $allowed_document_status ) ? $_POST['group-document-status'] : bboss_groups_settings_default_fallback( 'document_status', current( $allowed_document_status ) );

			bboss_groups_update_groupmeta( $bp->groups->new_group_id, 'document_status', $document_status );

			/**
			 * Filters the allowed video statuses.
			 *
			 * @since BuddyBoss 1.7.0
			 *
			 * @param array $value Array of statuses allowed.
			 *                     Possible values are 'members,
			 *                     'mods', and 'admins'.
			 */
			$allowed_video_status    = bboss_groups_get_settings_status( 'video' );
			$post_group_video_status = bboss_filter_input_string( INPUT_POST, 'group-video-status' );
			$video_status            = ! empty( $post_group_video_status ) && in_array( $post_group_video_status, (array) $allowed_video_status, true ) ? $post_group_video_status : bboss_groups_settings_default_fallback( 'video_status', current( $allowed_video_status ) );

			bboss_groups_update_groupmeta( $bp->groups->new_group_id, 'video_status', $video_status );

			/**
			 * Filters the allowed album statuses.
			 *
			 * @since BuddyBoss 1.0.0
			 *
			 * @param array $value Array of statuses allowed.
			 *                     Possible values are 'members,
			 *                     'mods', and 'admins'.
			 */
			$allowed_album_status = bboss_groups_get_settings_status( 'album' );
			$album_status         = ! empty( $_POST['group-album-status'] ) && in_array( $_POST['group-album-status'], (array) $allowed_album_status ) ? $_POST['group-album-status'] : bboss_groups_settings_default_fallback( 'album_status', current( $allowed_album_status ) );

			bboss_groups_update_groupmeta( $bp->groups->new_group_id, 'album_status', $album_status );

			$allowed_message_status = bboss_groups_get_settings_status( 'message' );
			$message_status         = isset( $_POST['group-message-status'] ) && in_array( $_POST['group-message-status'], (array) $allowed_message_status ) ? $_POST['group-message-status'] : bboss_groups_settings_default_fallback( 'message_status', current( $allowed_message_status ) );

			bboss_groups_update_groupmeta( $bp->groups->new_group_id, 'message_status', $message_status );

		}

		if ( 'group-invites' === bboss_get_groups_current_create_step() ) {
			if ( ! empty( $_POST['friends'] ) ) {
				foreach ( (array) $_POST['friends'] as $friend ) {
					bboss_groups_invite_user(
						array(
							'user_id'  => (int) $friend,
							'group_id' => $bp->groups->new_group_id,
						)
					);
				}
			}
			bboss_groups_send_invites( array( 'group_id' => $bp->groups->new_group_id ) );
		}

		/**
		 * Fires before finalization of group creation and cookies are set.
		 *
		 * This hook is a variable hook dependent on the current step
		 * in the creation process.
		 *
		 * @since BuddyPress 1.1.0
		 */
		do_action( 'groups_create_group_step_save_' . bboss_get_groups_current_create_step() );

		/**
		 * Fires after the group creation step is completed.
		 *
		 * Mostly for clearing cache on a generic action name.
		 *
		 * @since BuddyPress 1.1.0
		 */
		do_action( 'groups_create_group_step_complete' );

		/**
		 * Once we have successfully saved the details for this step of the creation process
		 * we need to add the current step to the array of completed steps, then update the cookies
		 * holding the information
		 */
		$completed_create_steps = isset( $bp->groups->completed_create_steps ) ? $bp->groups->completed_create_steps : array();
		if ( ! in_array( bboss_get_groups_current_create_step(), $completed_create_steps ) ) {
			$bp->groups->completed_create_steps[] = bboss_get_groups_current_create_step();
		}

		// Reset cookie info.
		setcookie( 'bp_new_group_id', $bp->groups->new_group_id, time() + 60 * 60 * 24, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
		setcookie( 'bp_completed_create_steps', base64_encode( json_encode( $bp->groups->completed_create_steps ) ), time() + 60 * 60 * 24, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );

		// If we have completed all steps and hit done on the final step we
		// can redirect to the completed group.
		$keys = array_keys( $bp->groups->group_creation_steps );
		if ( count( $bp->groups->completed_create_steps ) == count( $keys ) && bboss_get_groups_current_create_step() == array_pop( $keys ) ) {
			unset( $bp->groups->current_create_step );
			unset( $bp->groups->completed_create_steps );

			setcookie( 'bp_new_group_id', false, time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
			setcookie( 'bp_completed_create_steps', false, time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );

			// Once we completed all steps, record the group creation in the activity feed.
			if ( bboss_is_active( 'activity' ) ) {
				bboss_groups_record_activity(
					array(
						'type'    => 'created_group',
						'item_id' => $bp->groups->new_group_id,
					)
				);
			}

			/**
			 * Fires after the group has been successfully created.
			 *
			 * @since BuddyPress 1.1.0
			 *
			 * @param int $new_group_id ID of the newly created group.
			 */
			do_action( 'groups_group_create_complete', $bp->groups->new_group_id );

			bboss_core_redirect( bboss_get_group_permalink( $bp->groups->current_group ) );
		} else {
			/**
			 * Since we don't know what the next step is going to be (any plugin can insert steps)
			 * we need to loop the step array and fetch the next step that way.
			 */
			foreach ( $keys as $key ) {
				if ( $key == bboss_get_groups_current_create_step() ) {
					$next = 1;
					continue;
				}

				if ( isset( $next ) ) {
					$next_step = $key;
					break;
				}
			}

			bboss_core_redirect( trailingslashit( bboss_get_groups_directory_permalink() . 'create/step/' . $next_step ) );
		}
	}

	// Remove invitations.
	if ( 'group-invites' === bboss_get_groups_current_create_step() && ! empty( $_REQUEST['user_id'] ) && is_numeric( $_REQUEST['user_id'] ) ) {
		if ( ! check_admin_referer( 'groups_invite_uninvite_user' ) ) {
			return false;
		}

		$message = __( 'Invite successfully withdrawn', 'buddyboss' );
		$error   = false;

		if ( ! bboss_groups_uninvite_user( (int) $_REQUEST['user_id'], $bp->groups->new_group_id ) ) {
			$message = __( 'There was an error withdrawing the invite', 'buddyboss' );
			$error   = 'error';
		}

		bboss_core_add_message( $message, $error );
		bboss_core_redirect( trailingslashit( bboss_get_groups_directory_permalink() . 'create/step/group-invites' ) );
	}

	// Group avatar is handled separately.
	if ( 'group-avatar' == bboss_get_groups_current_create_step() && isset( $_POST['upload'] ) ) {
		if ( ! isset( $bp->avatar_admin ) ) {
			$bp->avatar_admin = new stdClass();
		}

		if ( ! empty( $_FILES ) && isset( $_POST['upload'] ) ) {
			// Normally we would check a nonce here, but the group save nonce is used instead.
			// Pass the file to the avatar upload handler.
			if ( bboss_core_avatar_handle_upload( $_FILES, 'bboss_groups_avatar_upload_dir' ) ) {
				$bp->avatar_admin->step = 'crop-image';

				// Make sure we include the jQuery jCrop file for image cropping.
				add_action( 'wp_print_scripts', 'bp_core_add_jquery_cropper' );
			}
		}

		// If the image cropping is done, crop the image and save a full/thumb version.
		if ( isset( $_POST['avatar-crop-submit'] ) && isset( $_POST['upload'] ) ) {

			// Normally we would check a nonce here, but the group save nonce is used instead.
			$args = array(
				'object'        => 'group',
				'avatar_dir'    => 'group-avatars',
				'item_id'       => $bp->groups->current_group->id,
				'original_file' => $_POST['image_src'],
				'crop_x'        => $_POST['x'],
				'crop_y'        => $_POST['y'],
				'crop_w'        => $_POST['w'],
				'crop_h'        => $_POST['h'],
			);

			if ( ! bboss_core_avatar_handle_crop( $args ) ) {
				bboss_core_add_message( __( 'There was an error with the group profile photo, please try again.', 'buddyboss' ), 'error' );
			} else {
				/**
				 * Fires after a group avatar is uploaded.
				 *
				 * @since BuddyPress 2.8.0
				 *
				 * @param int    $group_id ID of the group.
				 * @param string $type     Avatar type. 'crop' or 'full'.
				 * @param array  $args     Array of parameters passed to the avatar handler.
				 */
				do_action( 'groups_avatar_uploaded', bboss_get_current_group_id(), 'crop', $args );

				bboss_core_add_message( __( 'The group profile photo was uploaded successfully.', 'buddyboss' ) );
			}
		}
	}

	/**
	 * Filters the template to load for the group creation screen.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value Path to the group creation template to load.
	 */
	bboss_core_load_template( apply_filters( 'groups_template_create_group', 'groups/create' ) );
}
add_action( 'bp_actions', 'groups_action_create_group' );

/**
 * Sort the group creation steps.
 *
 * @since BuddyPress 1.1.0
 *
 * @return false|null False on failure.
 */
function bboss_groups_action_sort_creation_steps() {

	if ( ! bboss_is_groups_component() || ! bboss_is_current_action( 'create' ) ) {
		return false;
	}

	$bp = bboss();

	if ( ! is_array( $bp->groups->group_creation_steps ) ) {
		return false;
	}

	foreach ( (array) $bp->groups->group_creation_steps as $slug => $step ) {
		while ( ! empty( $temp[ $step['position'] ] ) ) {
			$step['position']++;
		}

		$temp[ $step['position'] ] = array(
			'name' => $step['name'],
			'slug' => $slug,
		);
	}

	// Sort the steps by their position key.
	ksort( $temp );
	unset( $bp->groups->group_creation_steps );

	foreach ( (array) $temp as $position => $step ) {
		$bp->groups->group_creation_steps[ $step['slug'] ] = array(
			'name'     => $step['name'],
			'position' => $position,
		);
	}

	/**
	 * Fires after group creation sets have been sorted.
	 *
	 * @since BuddyPress 2.3.0
	 */
	do_action( 'groups_action_sort_creation_steps' );
}
