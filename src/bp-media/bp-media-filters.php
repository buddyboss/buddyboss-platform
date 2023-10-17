<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'bp_media_album_after_save', 'bp_media_update_media_privacy' );
add_action( 'delete_attachment', 'bp_media_delete_attachment_media', 0 );

// Activity.
add_action( 'bp_after_directory_activity_list', 'bp_media_add_theatre_template' );
add_action( 'bp_after_single_activity_content', 'bp_media_add_theatre_template' );
add_action( 'bp_after_member_activity_content', 'bp_media_add_theatre_template' );
add_action( 'bp_after_group_activity_content', 'bp_media_add_theatre_template' );
add_action( 'bp_after_single_activity', 'bp_media_add_theatre_template' );
add_action( 'bp_activity_entry_content', 'bp_media_activity_entry' );
add_action( 'bp_activity_after_comment_content', 'bp_media_activity_comment_entry' );
add_action( 'bp_activity_posted_update', 'bp_media_update_activity_media_meta', 10, 3 );
add_action( 'bp_groups_posted_update', 'bp_media_groups_activity_update_media_meta', 10, 4 );
add_action( 'bp_activity_comment_posted', 'bp_media_activity_comments_update_media_meta', 10, 3 );
add_action( 'bp_activity_comment_posted_notification_skipped', 'bp_media_activity_comments_update_media_meta', 10, 3 );
add_action( 'bp_activity_after_delete', 'bp_media_delete_activity_media' ); // Delete activity medias.
add_action( 'bp_activity_after_delete', 'bp_media_delete_activity_gif' ); // Delete activity gif.
add_action( 'bp_activity_entry_content', 'bp_media_activity_embed_gif' );
add_action( 'bp_activity_after_comment_content', 'bp_media_comment_embed_gif', 20, 1 );
add_action( 'bp_activity_after_save', 'bp_media_activity_save_gif_data', 2, 1 );
add_action( 'bp_activity_after_save', 'bp_media_activity_update_media_privacy', 2 );
add_filter( 'bp_activity_get_edit_data', 'bp_media_get_edit_activity_data' );

// Forums.
add_action( 'bbp_template_after_single_topic', 'bp_media_add_theatre_template' );
add_action( 'bbp_new_reply', 'bp_media_forums_new_post_media_save', 999 );
add_action( 'bbp_new_topic', 'bp_media_forums_new_post_media_save', 999 );
add_action( 'edit_post', 'bp_media_forums_new_post_media_save', 999 );
add_action( 'bbp_new_reply', 'bp_media_forums_save_gif_data', 999 );
add_action( 'bbp_new_topic', 'bp_media_forums_save_gif_data', 999 );
add_action( 'edit_post', 'bp_media_forums_save_gif_data', 999 );

add_filter( 'bbp_get_reply_content', 'bp_media_forums_embed_attachments', 98, 2 );
add_filter( 'bbp_get_topic_content', 'bp_media_forums_embed_attachments', 98, 2 );
add_filter( 'bbp_get_reply_content', 'bp_media_forums_embed_gif', 98, 2 );
add_filter( 'bbp_get_topic_content', 'bp_media_forums_embed_gif', 98, 2 );

// Messages..
add_action( 'messages_message_sent', 'bp_media_attach_media_to_message' );
add_action( 'messages_message_sent', 'bp_media_messages_save_gif_data' );
add_action( 'bp_messages_thread_after_delete', 'bp_media_messages_delete_attached_media', 10, 2 ); // Delete thread medias.
add_action( 'bp_messages_thread_messages_after_update', 'bp_media_user_messages_delete_attached_media', 10, 4 ); // Delete messages medias.
add_action( 'bp_messages_thread_after_delete', 'bp_media_messages_delete_gif_data', 10, 2 ); // Delete thread gifs.
add_action( 'bp_messages_thread_messages_after_update', 'bp_media_user_messages_delete_attached_gif', 10, 4 ); // Delete messages gifs.
// add_action( 'bp_messages_thread_after_delete', 'bp_group_messages_delete_meta', 10, 2 );.
add_filter( 'bp_messages_message_validated_content', 'bp_media_message_validated_content', 20, 3 );
add_filter( 'bp_messages_message_validated_content', 'bp_media_gif_message_validated_content', 20, 3 );

// Core tools.
add_filter( 'bp_core_get_tools_settings_admin_tabs', 'bp_media_get_tools_media_settings_admin_tabs', 20, 1 );
add_action( 'bp_core_activation_notice', 'bp_media_activation_notice' );
add_action( 'wp_ajax_bp_media_import_status_request', 'bp_media_import_status_request' );
add_filter( 'bp_repair_list', 'bp_media_add_admin_repair_items' );

// Download Media.
add_action( 'init', 'bp_media_download_url_file' );

add_filter( 'bp_search_label_search_type', 'bp_media_search_label_search' );

add_filter( 'bp_get_activity_entry_css_class', 'bp_media_activity_entry_css_class' );

// Delete symlinks for media when before saved.
add_action( 'bp_media_before_save', 'bp_media_delete_symlinks' );

// Create symlinks for media when saved.
add_action( 'bp_media_after_save', 'bp_media_create_symlinks' );

// Clear media symlinks on delete.
add_action( 'bp_media_before_delete', 'bp_media_clear_media_symlinks_on_delete', 10 );

// Filter attachments in the query to filter media and documents.
add_filter( 'posts_join', 'bp_media_filter_attachments_query_posts_join', 10, 2 );
add_filter( 'posts_where', 'bp_media_filter_attachments_query_posts_where', 10, 2 );

add_filter( 'bp_get_activity_entry_css_class', 'bp_video_activity_entry_css_class' );

add_action( 'bp_add_rewrite_rules', 'bb_setup_media_preview' );
add_filter( 'query_vars', 'bb_setup_query_media_preview' );
add_action( 'template_include', 'bb_setup_template_for_media_preview', PHP_INT_MAX );

// Setup rewrite rule to access attachment media.
add_action( 'bp_add_rewrite_rules', 'bb_setup_attachment_media_preview' );
add_filter( 'query_vars', 'bb_setup_attachment_media_preview_query' );
add_action( 'template_include', 'bb_setup_attachment_media_preview_template', PHP_INT_MAX );

add_filter( 'bb_activity_comment_get_edit_data', 'bp_media_get_edit_activity_data' );
/**
 * Add Media items for search
 */
function bp_media_search_label_search( $type ) {

	if ( 'albums' === $type ) {
		$type = __( 'Albums', 'buddyboss' );
	} elseif ( 'photos' === $type ) {
		$type = __( 'Photos', 'buddyboss' );
	}

	return $type;
}

/**
 * Add media theatre template for activity pages
 */
function bp_media_add_theatre_template() {
	bp_get_template_part( 'media/theatre' );
}

/**
 * Get activity entry media to render on front end
 *
 * @BuddyBoss 1.0.0
 */
function bp_media_activity_entry() {
	global $media_template;

	if ( ( buddypress()->activity->id === bp_get_activity_object_name() && ! bp_is_profile_media_support_enabled() ) || ( bp_is_active( 'groups' ) && buddypress()->groups->id === bp_get_activity_object_name() && ! bp_is_group_media_support_enabled() ) ) {
		return false;
	}

	$media_ids = bp_activity_get_meta( bp_get_activity_id(), 'bp_media_ids', true );

	// Add Media to single activity page..
	$media_activity = bp_activity_get_meta( bp_get_activity_id(), 'bp_media_activity', true );
	if ( bp_is_single_activity() && ! empty( $media_activity ) && '1' == $media_activity && empty( $media_ids ) ) {
		$media_ids = BP_Media::get_activity_media_id( bp_get_activity_id() );
	} else {
		$media_ids = bp_activity_get_meta( bp_get_activity_id(), 'bp_media_ids', true );
	}

	if ( empty( $media_ids ) ) {
		return;
	}

	$args = array(
		'include'  => $media_ids,
		'order_by' => 'menu_order',
		'sort'     => 'ASC',
		'user_id'  => false,
		'per_page' => 0,
	);

	if ( bp_is_active( 'groups' ) && buddypress()->groups->id === bp_get_activity_object_name() ) {
		if ( bp_is_group_media_support_enabled() ) {
			$args['privacy'] = array( 'grouponly' );
			if ( ! bp_is_group_albums_support_enabled() ) {
				$args['album_id'] = 'existing-media';
			}
		} else {
			$args['privacy']  = array( '0' );
			$args['album_id'] = 'existing-media';
		}
	} else {
		$args['privacy'] = bp_media_query_privacy( bp_get_activity_user_id(), 0, bp_get_activity_object_name() );
		if ( ! bp_is_profile_media_support_enabled() ) {
			$args['user_id'] = 'null';
		}
		if ( ! bp_is_profile_albums_support_enabled() ) {
			$args['album_id'] = 'existing-media';
		}
	}

	$is_forum_activity = false;
	if (
			bp_is_active( 'forums' )
			&& in_array(
				bp_get_activity_type(),
				array(
					'bbp_forum_create',
					'bbp_topic_create',
					'bbp_reply_create',
				),
				true
			)
			&& bp_is_forums_media_support_enabled()
	) {
		$is_forum_activity = true;
		$args['privacy'][] = 'forums';
	}

	/**
	 * If the content has been changed by these filters bb_moderation_has_blocked_message,
	 * bb_moderation_is_blocked_message, bb_moderation_is_suspended_message then
	 * it will hide media content which is created by blocked/blocked/suspended member.
	 */
	$hide_forum_activity = function_exists( 'bb_moderation_to_hide_forum_activity' ) ? bb_moderation_to_hide_forum_activity( bp_get_activity_id() ) : false;

	if ( true === $hide_forum_activity ) {
		return;
	}

	if ( ! empty( $media_ids ) && bp_has_media( $args ) ) { ?>
		<div class="bb-activity-media-wrap
		<?php
		echo esc_attr( 'bb-media-length-' . $media_template->media_count );
		echo $media_template->media_count > 5 ? esc_attr( ' bb-media-length-more' ) : '';
		echo true === $is_forum_activity ? esc_attr( ' forums-media-wrap' ) : '';
		?>
		">
			<?php
			bp_get_template_part( 'media/media-move' );
			while ( bp_media() ) {
				bp_the_media();
				bp_get_template_part( 'media/activity-entry' );
			}
			?>
		</div>
		<?php
	}
}

/**
 * Append the media content to activity read more content
 *
 * @BuddyBoss 1.1.3
 *
 * @param $content
 * @param $activity
 *
 * @return string
 */
function bp_media_activity_append_media( $content, $activity ) {
	global $media_template;

	if ( ( buddypress()->activity->id === $activity->component && ! bp_is_profile_media_support_enabled() ) || ( bp_is_active( 'groups' ) && buddypress()->groups->id === $activity->component && ! bp_is_group_media_support_enabled() ) ) {
		return $content;
	}

	$media_ids = bp_activity_get_meta( $activity->id, 'bp_media_ids', true );

	if ( ! empty( $media_ids ) ) {

		$args = array(
			'include'  => $media_ids,
			'order_by' => 'menu_order',
			'sort'     => 'ASC',
			'per_page' => 0,
		);

		if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $activity->component ) {
			if ( bp_is_group_media_support_enabled() ) {
				if ( ! bp_is_group_albums_support_enabled() ) {
					$args['album_id'] = 'existing-media';
				}
			} else {
				$args['privacy']  = array( '0' );
				$args['album_id'] = 'existing-media';
			}
		} else {
			$args['privacy'] = bp_media_query_privacy( $activity->user_id, $group_id, $activity->component );

			if ( 'activity_comment' === $activity->type ) {
				$args['privacy'][] = 'comment';
			}

			if ( ! bp_is_profile_media_support_enabled() ) {
				$args['user_id'] = 'null';
			}
			if ( ! bp_is_profile_albums_support_enabled() ) {
				$args['album_id'] = 'existing-media';
			}
		}

		$is_forum_activity = false;
		if (
				bp_is_active( 'forums' )
				&& in_array(
					$activity->type,
					array(
						'bbp_forum_create',
						'bbp_topic_create',
						'bbp_reply_create',
					),
					true
				)
				&& bp_is_forums_media_support_enabled()
		) {
			$is_forum_activity = true;
			$args['privacy'][] = 'forums';
		}

		if ( bp_has_media( $args ) ) {

			ob_start();
			?>
			<div class="bb-activity-media-wrap
			<?php
			echo 'bb-media-length-' . esc_attr( $media_template->media_count );
			echo $media_template->media_count > 5 ? ' bb-media-length-more' : '';
			echo true === $is_forum_activity ? ' forums-media-wrap' : '';
			?>
			">
				<?php
				bp_get_template_part( 'media/media-move' );
				while ( bp_media() ) {
					bp_the_media();
					bp_get_template_part( 'media/activity-entry' );
				}
				?>
			</div>
			<?php
			$content .= ob_get_clean();
		}
	}

	return $content;
}

/**
 * Get activity comment entry media to render on front end
 */
function bp_media_activity_comment_entry( $comment_id ) {
	global $media_template;

	$media_ids = bp_activity_get_meta( $comment_id, 'bp_media_ids', true );

	if ( empty( $media_ids ) ) {
		return;
	}

	$comment  = new BP_Activity_Activity( $comment_id );
	$activity = new BP_Activity_Activity( $comment->item_id );

	$args = array(
		'include'  => $media_ids,
		'order_by' => 'menu_order',
		'sort'     => 'ASC',
		'user_id'  => false,
		'privacy'  => array(),
		'per_page' => 0,
	);

	if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $activity->component ) {
		if ( bp_is_group_media_support_enabled() ) {
			$args['privacy'][] = 'comment';
			$args['privacy'][] = 'grouponly';
			if ( ! bp_is_group_albums_support_enabled() ) {
				$args['album_id'] = 'existing-media';
			}
		} else {
			$args['privacy']  = array( '0' );
			$args['album_id'] = 'existing-media';
		}
	} else {
		$args['privacy'] = bp_media_query_privacy( $activity->user_id, 0, $activity->component );
		if ( ! bp_is_profile_media_support_enabled() ) {
			$args['user_id'] = 'null';
		}
		if ( ! bp_is_profile_albums_support_enabled() ) {
			$args['album_id'] = 'existing-media';
		}
	}

	$args['privacy'][] = 'comment';
	if ( ! isset( $args['album_id'] ) ) {
		$args['album_id'] = 'existing-media';
	}

	$is_forum_activity = false;
	if (
			bp_is_active( 'forums' )
			&& in_array( $activity->type, array( 'bbp_forum_create', 'bbp_topic_create', 'bbp_reply_create' ), true )
			&& bp_is_forums_media_support_enabled()
	) {
		$is_forum_activity = true;
		$args['privacy'][] = 'forums';
	}

	$args['privacy'] = array_unique( $args['privacy'] );

	if ( ! empty( $media_ids ) && bp_has_media( $args ) ) {
		?>
		<div class="bb-activity-media-wrap
		<?php
		echo esc_attr( 'bb-media-length-' . $media_template->media_count );
		echo $media_template->media_count > 5 ? esc_attr( ' bb-media-length-more' ) : '';
		?>
		">
				<?php
				bp_get_template_part( 'media/media-move' );
				while ( bp_media() ) {
					bp_the_media();
					bp_get_template_part( 'media/activity-entry' );
				}
				?>
			</div>
			<?php
	}
}

/**
 * Update media for activity
 *
 * @param $content
 * @param $user_id
 * @param $activity_id
 *
 * @since BuddyBoss 1.0.0
 *
 * @return bool
 */
function bp_media_update_activity_media_meta( $content, $user_id, $activity_id ) {
	global $bp_activity_post_update, $bp_activity_post_update_id, $bp_activity_edit, $bb_activity_comment_edit;

	$medias           = filter_input( INPUT_POST, 'media', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	$medias           = ! empty( $medias ) ? $medias : array();
	$actions          = bb_filter_input_string( INPUT_POST, 'action' );
	$moderated_medias = bp_activity_get_meta( $activity_id, 'bp_media_ids', true );

	if ( ! empty( $medias ) ) {
		$media_order = array_column( $medias, 'menu_order' );
		array_multisort( $media_order, SORT_ASC, $medias );
	}

	if ( bp_is_active( 'moderation' ) && ! empty( $moderated_medias ) ) {
		$moderated_medias = explode( ',', $moderated_medias );
		foreach ( $moderated_medias as $media_id ) {
			if ( bp_moderation_is_content_hidden( $media_id, BP_Moderation_Media::$moderation_type ) ) {
				$bp_media               = new BP_Media( $media_id );
				$medias[]['media_id']   = $media_id;
				$medias[]['album_id']   = $bp_media->album_id;
				$medias[]['group_id']   = $bp_media->group_id;
				$medias[]['menu_order'] = $bp_media->menu_order;
			}
		}
	}

	if ( ! isset( $medias ) || empty( $medias ) ) {

		// delete media ids and meta for activity if empty media in request.
		if (
			! empty( $activity_id ) &&
			(
				(
					$bp_activity_edit && isset( $_POST['edit'] )
				) ||
				(
					$bb_activity_comment_edit && isset( $_POST['edit_comment'] )
				)
			)
		) {
			$old_media_ids = bp_activity_get_meta( $activity_id, 'bp_media_ids', true );

			if ( ! empty( $old_media_ids ) ) {
				// Delete media if not exists anymore in activity.
				$old_media_ids = explode( ',', $old_media_ids );
				if ( ! empty( $old_media_ids ) ) {
					foreach ( $old_media_ids as $media_id ) {
						bp_media_delete( array( 'id' => $media_id ), 'activity' );
					}
				}
				bp_activity_delete_meta( $activity_id, 'bp_media_ids' );

				// Delete media meta from activity for activity comment.
				if ( $bb_activity_comment_edit ) {
					bp_activity_delete_meta( $activity_id, 'bp_media_id' );
					bp_activity_delete_meta( $activity_id, 'bp_media_activity' );
				}
			}
		}

		return false;
	}

	$bp_activity_post_update    = true;
	$bp_activity_post_update_id = $activity_id;

	// Update activity comment attached document privacy with parent one.
	if ( ! empty( $activity_id ) && isset( $actions ) && 'new_activity_comment' === $actions ) {
		$parent_activity = new BP_Activity_Activity( $activity_id );
		if ( 'groups' === $parent_activity->component ) {
			$_POST['privacy'] = 'grouponly';
		} elseif ( ! empty( $parent_activity->privacy ) ) {
			$_POST['privacy'] = $parent_activity->privacy;
		}
	}

	remove_action( 'bp_activity_posted_update', 'bp_media_update_activity_media_meta', 10, 3 );
	remove_action( 'bp_groups_posted_update', 'bp_media_groups_activity_update_media_meta', 10, 4 );
	remove_action( 'bp_activity_comment_posted', 'bp_media_activity_comments_update_media_meta', 10, 3 );
	remove_action( 'bp_activity_comment_posted_notification_skipped', 'bp_media_activity_comments_update_media_meta', 10, 3 );

	$media_ids = bp_media_add_handler( $medias, $_POST['privacy'] );

	add_action( 'bp_activity_posted_update', 'bp_media_update_activity_media_meta', 10, 3 );
	add_action( 'bp_groups_posted_update', 'bp_media_groups_activity_update_media_meta', 10, 4 );
	add_action( 'bp_activity_comment_posted', 'bp_media_activity_comments_update_media_meta', 10, 3 );
	add_action( 'bp_activity_comment_posted_notification_skipped', 'bp_media_activity_comments_update_media_meta', 10, 3 );

	// save media meta for activity.
	if ( ! empty( $activity_id ) ) {

		// Delete media if not exists in current media ids.
		if ( isset( $_POST['edit'] ) || isset( $_POST['edit_comment'] ) ) {
			$old_media_ids = bp_activity_get_meta( $activity_id, 'bp_media_ids', true );
			$old_media_ids = explode( ',', $old_media_ids );

			if ( ! empty( $old_media_ids ) ) {

				foreach ( $old_media_ids as $media_id ) {
					if ( bp_is_active( 'moderation' ) && bp_moderation_is_content_hidden( $media_id, BP_Moderation_Media::$moderation_type ) && ! in_array( $media_id, $media_ids ) ) {
						$media_ids[] = $media_id;
					}
					if ( ! in_array( $media_id, $media_ids ) ) {
						bp_media_delete( array( 'id' => $media_id ), 'activity' );
					}
				}

				// This is hack to update/delete parent activity if new media added in edit.
				bp_activity_update_meta( $activity_id, 'bp_media_ids', implode( ',', array_unique( array_merge( $media_ids, $old_media_ids ) ) ) );
			}
		}

		// update new media ids here in the activity meta.
		bp_activity_update_meta( $activity_id, 'bp_media_ids', implode( ',', $media_ids ) );
	}
}

/**
 * Update media for group activity
 *
 * @param $content
 * @param $user_id
 * @param $group_id
 * @param $activity_id
 *
 * @since BuddyBoss 1.0.0
 *
 * @return bool
 */
function bp_media_groups_activity_update_media_meta( $content, $user_id, $group_id, $activity_id ) {
	bp_media_update_activity_media_meta( $content, $user_id, $activity_id );
}

/**
 * Update media for activity comment
 *
 * @param $comment_id
 * @param $r
 * @param $activity
 *
 * @since BuddyBoss 1.0.0
 *
 * @return bool
 */
function bp_media_activity_comments_update_media_meta( $comment_id, $r, $activity ) {
	global $bp_new_activity_comment;
	$bp_new_activity_comment = $comment_id;
	bp_media_update_activity_media_meta( false, false, $comment_id );
}

/**
 * Delete media when related activity is deleted.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $activities
 */
function bp_media_delete_activity_media( $activities ) {
	if ( ! empty( $activities ) ) {
		remove_action( 'bp_activity_after_delete', 'bp_media_delete_activity_media' );
		foreach ( $activities as $activity ) {

			// Do not delete attached media, if the activity belongs to a forum topic/reply.
			// Attached media could still be used inside that component.
			if (
				! empty( $activity->type ) &&
				in_array( $activity->type, array( 'bbp_reply_create', 'bbp_topic_create' ), true )
			) {
				continue;
			}

			$activity_id    = $activity->id;
			$media_activity = bp_activity_get_meta( $activity_id, 'bp_media_activity', true );
			if ( ! empty( $media_activity ) && '1' == $media_activity ) {
				bp_media_delete( array( 'activity_id' => $activity_id ) );
			}

			// get media ids attached to activity.
			$media_ids = bp_activity_get_meta( $activity_id, 'bp_media_ids', true );
			if ( ! empty( $media_ids ) ) {
				$media_ids = explode( ',', $media_ids );
				foreach ( $media_ids as $media_id ) {
					bp_media_delete( array( 'id' => $media_id ) );
				}
			}
		}
		add_action( 'bp_activity_after_delete', 'bp_media_delete_activity_media' );
	}
}

/**
 * Delete media gif when related activity is deleted.
 *
 * @since BuddyBoss 1.4.9
 *
 * @param array $activities Array of activities.
 */
function bp_media_delete_activity_gif( $activities ) {
	if ( ! empty( $activities ) ) {
		foreach ( $activities as $activity ) {
			$activity_id  = $activity->id;
			$activity_gif = bp_activity_get_meta( $activity_id, '_gif_data', true );

			if ( ! empty( $activity_gif ) ) {
				if ( ! empty( $activity_gif['still'] ) && is_int( $activity_gif['still'] ) ) {
					wp_delete_attachment( (int) $activity_gif['still'], true );
				}

				if ( ! empty( $activity_gif['mp4'] ) && is_int( $activity_gif['mp4'] ) ) {
					wp_delete_attachment( (int) $activity_gif['mp4'], true );
				}
			}
		}
	}
}

/**
 * Update media privacy according to album's privacy
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $album
 */
function bp_media_update_media_privacy( $album ) {

	if ( ! empty( $album->id ) ) {

		$privacy      = $album->privacy;
		$media_ids    = BP_Media::get_album_media_ids( $album->id );
		$activity_ids = array();

		if ( ! empty( $media_ids ) ) {
			foreach ( $media_ids as $media ) {
				$media_obj          = new BP_Media( $media );
				$media_obj->privacy = $privacy;
				$media_obj->save();

				$attachment_id          = $media_obj->attachment_id;
				$main_activity_id       = get_post_meta( $attachment_id, 'bp_media_parent_activity_id', true );
				$video_main_activity_id = get_post_meta( $attachment_id, 'bp_video_parent_activity_id', true );

				if ( ! empty( $main_activity_id ) ) {
					$activity_ids[] = $main_activity_id;
				}

				if ( ! empty( $video_main_activity_id ) ) {
					$activity_ids[] = $video_main_activity_id;
				}
			}
		}

		if ( ! empty( $activity_ids ) ) {
			$activity_ids = array_unique( $activity_ids );

			foreach ( $activity_ids as $activity_id ) {
				$activity = new BP_Activity_Activity( $activity_id );

				if ( ! empty( $activity ) ) {
					$activity->privacy = $privacy;
					$activity->save();
				}
			}
		}
	}
}

/**
 * Save media when new topic or reply is saved
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post_id
 */
function bp_media_forums_new_post_media_save( $post_id ) {

	if ( ! empty( $_POST['bbp_media'] ) ) {

		// save activity id if it is saved in forums and enabled in platform settings.
		$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

		// save media.
		$medias = json_decode( stripslashes( $_POST['bbp_media'] ), true );

		if ( ! empty( $medias ) ) {
			$media_order = array_column( $medias, 'menu_order' );
			array_multisort( $media_order, SORT_ASC, $medias );
		}

		// fetch currently uploaded media ids.
		$existing_media                = array();
		$existing_media_ids            = get_post_meta( $post_id, 'bp_media_ids', true );
		$existing_media_attachment_ids = array();
		if ( ! empty( $existing_media_ids ) ) {
			$existing_media_ids = explode( ',', $existing_media_ids );

			foreach ( $existing_media_ids as $existing_media_id ) {
				$existing_media[ $existing_media_id ] = new BP_Media( $existing_media_id );

				if ( ! empty( $existing_media[ $existing_media_id ]->attachment_id ) ) {
					$existing_media_attachment_ids[] = $existing_media[ $existing_media_id ]->attachment_id;
				}
			}
		}

		$media_ids = array();
		foreach ( $medias as $media ) {

			$title             = ! empty( $media['name'] ) ? $media['name'] : '';
			$attachment_id     = ! empty( $media['id'] ) ? $media['id'] : 0;
			$attached_media_id = ! empty( $media['media_id'] ) ? $media['media_id'] : 0;
			$album_id          = ! empty( $media['album_id'] ) ? $media['album_id'] : 0;
			$group_id          = ! empty( $media['group_id'] ) ? $media['group_id'] : 0;
			$menu_order        = ! empty( $media['menu_order'] ) ? $media['menu_order'] : 0;

			if ( ! empty( $existing_media_attachment_ids ) ) {
				$index = array_search( $attachment_id, $existing_media_attachment_ids );
				if ( ! empty( $attachment_id ) && $index !== false && ! empty( $existing_media[ $attached_media_id ] ) ) {

					$existing_media[ $attached_media_id ]->menu_order = $menu_order;
					$existing_media[ $attached_media_id ]->save();

					unset( $existing_media_ids[ $index ] );
					$media_ids[] = $attached_media_id;
					continue;
				}
			}

			$media_id = bp_media_add(
				array(
					'attachment_id' => $attachment_id,
					'title'         => $title,
					'album_id'      => $album_id,
					'group_id'      => $group_id,
					'privacy'       => 'forums',
					'error_type'    => 'wp_error',
				)
			);

			if ( ! is_wp_error( $media_id ) ) {
				$media_ids[] = $media_id;

				// save media is saved in attachment.
				update_post_meta( $attachment_id, 'bp_media_saved', true );
			}
		}

		$media_ids = implode( ',', $media_ids );

		// Save all attachment ids in forums post meta.
		update_post_meta( $post_id, 'bp_media_ids', $media_ids );

		// save media meta for activity.
		if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
			if ( ! empty( $media_ids ) ) {
				bp_activity_update_meta( $main_activity_id, 'bp_media_ids', $media_ids );
			} else {
				bp_activity_delete_meta( $main_activity_id, 'bp_media_ids' );
			}
		}

		// delete medias which were not saved or removed from form.
		if ( ! empty( $existing_media_ids ) ) {
			foreach ( $existing_media_ids as $media_id ) {
				bp_media_delete( array( 'id' => $media_id ) );
			}
		}
	}
}

/**
 * Embed topic or reply attachments in a post
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $content
 * @param $id
 *
 * @return string
 */
function bp_media_forums_embed_attachments( $content, $id ) {
	global $media_template;

	// Do not embed attachment in wp-admin area.
	if ( is_admin() ) {
		return $content;
	}

	$forum_id = 0;

	// Get current forum ID.
	if ( bbp_get_reply_post_type() === get_post_type( $id ) ) {
		$forum_id = bbp_get_reply_forum_id( $id );
	} elseif ( bbp_get_topic_post_type() === get_post_type( $id ) ) {
		$forum_id = bbp_get_topic_forum_id( $id );
	} elseif ( bbp_get_forum_post_type() === get_post_type( $id ) ) {
		$forum_id = $id;
	}

	$group_ids = bbp_get_forum_group_ids( $forum_id );
	$group_id  = ( ! empty( $group_ids ) ? current( $group_ids ) : 0 );

	if (
		(
			(
				empty( $group_id ) ||
				(
					! empty( $group_id ) &&
					! bp_is_active( 'groups' )
				)
			) &&
			! bp_is_forums_media_support_enabled()
		) ||
		(
			bp_is_active( 'groups' ) &&
			! empty( $group_id ) &&
			! bp_is_group_media_support_enabled()
		)
	) {
		return $content;
	}

	$media_ids = get_post_meta( $id, 'bp_media_ids', true );

	if ( ! empty( $media_ids ) && bp_has_media(
		array(
			'include'  => $media_ids,
			'order_by' => 'menu_order',
			'privacy'  => array( 'forums' ),
			'sort'     => 'ASC',
			'per_page' => 0,
		)
	) ) {
		ob_start();
		?>
		<div class="bb-activity-media-wrap forums-media-wrap
		<?php
		echo 'bb-media-length-' . $media_template->media_count;
		echo $media_template->media_count > 5 ? ' bb-media-length-more' : '';
		?>
		">
			<?php
			while ( bp_media() ) {
				bp_the_media();
				bp_get_template_part( 'media/activity-entry' );
			}
			?>
		</div>
		<?php
		$content .= ob_get_clean();
	}

	return $content;
}

/**
 * Embed topic or reply gif in a post
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $content
 * @param $id
 *
 * @return string
 */
function bp_media_forums_embed_gif( $content, $id ) {

	// Do not embed attachment in wp-admin area.
	if ( is_admin() ) {
		return $content;
	}

	$forum_id = 0;

	// Get current forum ID.
	if ( bbp_get_reply_post_type() === get_post_type( $id ) ) {
		$forum_id = bbp_get_reply_forum_id( $id );
	} elseif ( bbp_get_topic_post_type() === get_post_type( $id ) ) {
		$forum_id = bbp_get_topic_forum_id( $id );
	} elseif ( bbp_get_forum_post_type() === get_post_type( $id ) ) {
		$forum_id = $id;
	}

	$group_ids = bbp_get_forum_group_ids( $forum_id );
	$group_id  = ( ! empty( $group_ids ) ? current( $group_ids ) : 0 );

	if (
		(
			(
				empty( $group_id ) ||
				(
					! empty( $group_id ) &&
					! bp_is_active( 'groups' )
				)
			) &&
			! bp_is_forums_gif_support_enabled()
		) ||
		(
			bp_is_active( 'groups' ) &&
			! empty( $group_id ) &&
			! bp_is_groups_gif_support_enabled()
		)
	) {
		return $content;
	}

	$gif_data = get_post_meta( $id, '_gif_data', true );

	if ( empty( $gif_data ) ) {
		return $content;
	}

	$preview_url = ( is_int( $gif_data['still'] ) ) ? wp_get_attachment_url( $gif_data['still'] ) : $gif_data['still'];
	$video_url   = ( is_int( $gif_data['mp4'] ) ) ? wp_get_attachment_url( $gif_data['mp4'] ) : $gif_data['mp4'];

	ob_start();
	?>
	<div class="activity-attached-gif-container">
		<div class="gif-image-container">
			<div class="gif-player">
				<video preload="auto" playsinline poster="<?php echo $preview_url; ?>" loop muted>
					<source src="<?php echo $video_url; ?>" type="video/mp4">
				</video>
				<a href="#" class="gif-play-button">
					<span class="bb-icon-bl bb-icon-play"></span>
				</a>
				<span class="gif-icon"></span>
			</div>
		</div>
	</div>
	<?php
	$content .= ob_get_clean();

	return $content;
}

/**
 * save gif data for forum, topic, reply
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post_id
 */
function bp_media_forums_save_gif_data( $post_id ) {

	if ( ! empty( $_POST['bbp_media_gif'] ) ) {

		// save activity id if it is saved in forums and enabled in platform settings.
		$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

		// save gif data.
		$gif_data = json_decode( stripslashes( $_POST['bbp_media_gif'] ), true );

		if ( ! empty( $gif_data['saved'] ) && $gif_data['saved'] ) {
			return;
		}

		$still = $gif_data['images']['480w_still']['url'];
		$mp4   = $gif_data['images']['original_mp4']['mp4'];

		$gdata = array(
			'still' => $still,
			'mp4'   => $mp4,
		);

		update_post_meta( $post_id, '_gif_data', $gdata );

		$gif_data['saved'] = true;

		update_post_meta( $post_id, '_gif_raw_data', $gif_data );

		// save media meta for forum.
		if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
			bp_activity_update_meta( $main_activity_id, '_gif_data', $gdata );
			bp_activity_update_meta( $main_activity_id, '_gif_raw_data', $gif_data );
		}
	} else {
		delete_post_meta( $post_id, '_gif_data' );
		delete_post_meta( $post_id, '_gif_raw_data' );

		// Delete activity meta as well.
		$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );
		if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
			bp_activity_delete_meta( $main_activity_id, '_gif_data' );
			bp_activity_delete_meta( $main_activity_id, '_gif_raw_data' );
		}
	}
}

/**
 * Attach media to the message object
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $message
 */
function bp_media_attach_media_to_message( &$message ) {
	$group_id = ! empty( $_POST['group'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['group'] ) ) : 0;

	if (
		bb_user_has_access_upload_media( $group_id, $message->sender_id, 0, $message->thread_id, 'message' ) &&
		! empty( $message->id ) &&
		(
			! empty( $_POST['media'] ) ||
			! empty( $_POST['bp_media_ids'] )
		)
	) {
		$media_attachments = array();

		if ( ! empty( $_POST['media'] ) ) {
			$media_attachments = $_POST['media'];
		} else if ( ! empty( $_POST['bp_media_ids'] ) ) {
			$media_attachments = $_POST['bp_media_ids'];
		}

		$media_ids = array();

		if ( ! empty( $media_attachments ) ) {
			foreach ( $media_attachments as $attachment ) {

				$attachment_id = ( is_array( $attachment ) && ! empty( $attachment['id'] ) ) ? $attachment['id'] : $attachment;

				// Get media_id from the attachment ID.
				$media_id = get_post_meta( $attachment_id, 'bp_media_id', true );

				if ( ! empty( $media_id ) ) {
					$media_ids[] = $media_id;

					// Attach already created media.
					$media             = new BP_Media( $media_id );
					$media->privacy    = 'message';
					$media->message_id = $message->id;
					$media->save();

					update_post_meta( $media->attachment_id, 'bp_media_saved', true );
					update_post_meta( $media->attachment_id, 'thread_id', $message->thread_id );
				}
			}

			if ( ! empty( $media_ids ) ) {
				bp_messages_update_meta( $message->id, 'bp_media_ids', implode( ',', $media_ids ) );
			}
		}
	}
}

/**
 * Delete media attached to messages
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $thread_id
 * @param $message_ids
 */
function bp_media_messages_delete_attached_media( $thread_id, $message_ids ) {

	if ( ! empty( $message_ids ) ) {
		foreach ( $message_ids as $message_id ) {

			// get media ids attached to message.
			$media_ids = bp_messages_get_meta( $message_id, 'bp_media_ids', true );

			if ( ! empty( $media_ids ) ) {
				$media_ids = explode( ',', $media_ids );
				foreach ( $media_ids as $media_id ) {
					bp_media_delete( array( 'id' => $media_id ) );
				}
			}
		}
	}
}

/**
 * Delete media attached to messages
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $thread_id
 * @param $message_ids
 */
function bp_media_user_messages_delete_attached_media( $thread_id, $message_ids, $user_id, $update_message_ids ) {

	if ( ! empty( $update_message_ids ) ) {
		foreach ( $update_message_ids as $message_id ) {

			// get media ids attached to message..
			$media_ids = bp_messages_get_meta( $message_id, 'bp_media_ids', true );

			if ( ! empty( $media_ids ) ) {
				$media_ids = explode( ',', $media_ids );
				foreach ( $media_ids as $media_id ) {
					bp_media_delete( array( 'id' => $media_id ) );
				}
			}
		}
	}
}

/**
 * Delete gif attached to thread messages.
 *
 * @since BuddyBoss 1.2.9
 *
 * @param int   $thread_id   ID of the thread being deleted.
 * @param array $message_ids IDs of messages being deleted.
 */
function bp_media_messages_delete_gif_data( $thread_id, $message_ids ) {

	if ( ! empty( $message_ids ) ) {
		foreach ( $message_ids as $message_id ) {
			$message_gif = bp_messages_get_meta( $message_id, '_gif_data', true );

			if ( ! empty( $message_gif ) ) {
				if ( ! empty( $message_gif['still'] ) && is_int( $message_gif['still'] ) ) {
					wp_delete_attachment( (int) $message_gif['still'], true );
				}

				if ( ! empty( $message_gif['mp4'] ) && is_int( $message_gif['mp4'] ) ) {
					wp_delete_attachment( (int) $message_gif['mp4'], true );
				}
			}

			bp_messages_delete_meta( $message_id, '_gif_data' );
			bp_messages_delete_meta( $message_id, '_gif_raw_data' );
		}
	}
}

/**
 * Delete gif attached to messages.
 *
 * @since BuddyBoss 1.4.9
 *
 * @param int   $thread_id          ID of the thread being deleted.
 * @param array $message_ids        IDs of messages being deleted.
 * @param int   $user_id            ID of the user the threads messages update for.
 * @param array $update_message_ids IDs of messages being updated.
 */
function bp_media_user_messages_delete_attached_gif( $thread_id, $message_ids, $user_id, $update_message_ids ) {

	if ( ! empty( $update_message_ids ) ) {
		foreach ( $update_message_ids as $message_id ) {
			$message_gif = bp_messages_get_meta( $message_id, '_gif_data', true );

			if ( ! empty( $message_gif ) ) {
				if ( ! empty( $message_gif['still'] ) && is_int( $message_gif['still'] ) ) {
					wp_delete_attachment( (int) $message_gif['still'], true );
				}

				if ( ! empty( $message_gif['mp4'] ) && is_int( $message_gif['mp4'] ) ) {
					wp_delete_attachment( (int) $message_gif['mp4'], true );
				}
			}

			bp_messages_delete_meta( $message_id, '_gif_data' );
			bp_messages_delete_meta( $message_id, '_gif_raw_data' );
		}
	}
}

/**
 * Save gif data into messages meta key "_gif_data"
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $message
 */
function bp_media_messages_save_gif_data( &$message ) {
	$group_id = ! empty( $_POST['group'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['group'] ) ) : 0;

	if (
		bb_user_has_access_upload_gif( $group_id, $message->sender_id, 0, $message->thread_id, 'message' ) &&
		! empty( $message->id ) &&
		! empty( $_POST['gif_data'] )
	) {
		$gif_data = $_POST['gif_data'];

		$still = $gif_data['images']['480w_still']['url'];
		$mp4   = $gif_data['images']['original_mp4']['mp4'];

		bp_messages_update_meta(
			$message->id,
			'_gif_data',
			array(
				'still' => $still,
				'mp4'   => $mp4,
			)
		);

		bp_messages_update_meta( $message->id, '_gif_raw_data', $gif_data );
	}
}

/**
 * Validate message if media is not empty.
 *
 * @since BuddyBoss 2.0.4
 *
 * @param bool         $validated_content True if message is valid, false otherwise.
 * @param string       $content           Message content.
 * @param array|object $post              Request object.
 *
 * @return bool
 */
function bp_media_message_validated_content( $validated_content, $content, $post ) {
	$group_id  = ! empty( $post['group'] ) ? (int) sanitize_text_field( wp_unslash( $post['group'] ) ) : 0;
	$thread_id = ! empty( $post['thread_id'] ) ? (int) sanitize_text_field( wp_unslash( $post['thread_id'] ) ) : 0;

	if (
		! bb_user_has_access_upload_media( $group_id, bp_loggedin_user_id(), 0, $thread_id, 'message' ) ||
		! isset( $post['media'] )
	) {
		return (bool) $validated_content;
	}

	return (bool) ! empty( $post['media'] );
}

/**
 * Validate message if media is not empty.
 *
 * @since BuddyBoss 2.0.4
 *
 * @param bool         $validated_content True if message is valid, false otherwise.
 * @param string       $content           Message content.
 * @param array|object $post              Request object.
 *
 * @return bool
 */
function bp_media_gif_message_validated_content( $validated_content, $content, $post ) {
	$group_id  = ! empty( $post['group'] ) ? (int) sanitize_text_field( wp_unslash( $post['group'] ) ) : 0;
	$thread_id = ! empty( $post['thread_id'] ) ? (int) sanitize_text_field( wp_unslash( $post['thread_id'] ) ) : 0;

	if (
		! bb_user_has_access_upload_gif( $group_id, bp_loggedin_user_id(), 0, $thread_id, 'message' ) ||
		! isset( $post['gif_data'] )
	) {
		return (bool) $validated_content;
	}

	return (bool) ! empty( $post['gif_data'] );
}

/**
 * Return activity gif embed HTML.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int $activity_id Activity id.
 *
 * @return false|string|void
 */
function bp_media_activity_embed_gif_content( $activity_id ) {

	$gif_data = bp_activity_get_meta( $activity_id, '_gif_data', true );

	if ( empty( $gif_data ) ) {
		return;
	}

	$preview_url = ( is_int( $gif_data['still'] ) ) ? wp_get_attachment_url( $gif_data['still'] ) : $gif_data['still'];
	$video_url   = ( is_int( $gif_data['mp4'] ) ) ? wp_get_attachment_url( $gif_data['mp4'] ) : $gif_data['mp4'];
	$preview_url = $preview_url . '?' . wp_rand() . '=' . wp_rand();
	$video_url   = $video_url . '?' . wp_rand() . '=' . wp_rand();

	/**
	 * If the content has been changed by these filters bb_moderation_has_blocked_message,
	 * bb_moderation_is_blocked_message, bb_moderation_is_suspended_message then
	 * it will hide gif content which is created by blocked/blocked/suspended member.
	 */
	$hide_forum_activity = function_exists( 'bb_moderation_to_hide_forum_activity' ) ? bb_moderation_to_hide_forum_activity( $activity_id ) : false;

	if ( true === $hide_forum_activity ) {
		return;
	}

	ob_start();
	?>
	<div class="activity-attached-gif-container">
		<div class="gif-image-container">
			<div class="gif-player">
				<video preload="auto" playsinline poster="<?php echo $preview_url; ?>" loop muted>
					<source src="<?php echo $video_url; ?>" type="video/mp4">
				</video>
				<a href="#" class="gif-play-button">
					<span class="bb-icon-bl bb-icon-play"></span>
				</a>
				<span class="gif-icon"></span>
			</div>
		</div>
	</div>
	<?php
	$content = ob_get_clean();

	return $content;
}

/**
 * Embed gif in activity content.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_media_activity_embed_gif() {

	// check if profile and groups activity gif support enabled.
	if ( ( buddypress()->activity->id === bp_get_activity_object_name() && ! bp_is_profiles_gif_support_enabled() ) || ( bp_is_active( 'groups' ) && buddypress()->groups->id === bp_get_activity_object_name() && ! bp_is_groups_gif_support_enabled() ) ) {
		return false;
	}

	echo bp_media_activity_embed_gif_content( bp_get_activity_id() );
}

/**
 * Embed gif in activity comment content.
 *
 * @param int $comment_id Comment id for the activity.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_media_comment_embed_gif( $comment_id ) {
	global $activities_template;

	// check if profile and groups comments gif support enabled.
	if ( ! empty( $activities_template ) ) {
		$parent_activity_id = $activities_template->activity->current_comment->item_id;
	} else {
		$comment            = new BP_Activity_Activity( $comment_id );
		$parent_activity_id = $comment->item_id;
	}

	$parent_activity = new BP_Activity_Activity( $parent_activity_id );
	$component       = $parent_activity->component;

	if ( ( buddypress()->activity->id === $component && ! bp_is_profiles_gif_support_enabled() ) || ( bp_is_active( 'groups' ) && buddypress()->groups->id === $component && ! bp_is_groups_gif_support_enabled() ) ) {
		return false;
	}

	$gif_content = bp_media_activity_embed_gif_content( $comment_id );

	if ( ! empty( $gif_content ) ) {
		echo $gif_content;
	}
}

/**
 * Save gif data into activity meta key "_gif_attachment_id"
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $activity
 */
function bp_media_activity_save_gif_data( $activity ) {
	global $bp_activity_edit, $bb_activity_comment_edit;

	if ( !
		(
			( $bp_activity_edit && isset( $_POST['edit'] ) ) ||
			( $bb_activity_comment_edit && isset( $_POST['edit_comment'] ) )
		) &&
		empty( $_POST['gif_data'] )
	) {
		return;
	}

	$gif_data     = ! empty( $_POST['gif_data'] ) ? $_POST['gif_data'] : array();
	$gif_old_data = bp_activity_get_meta( $activity->id, '_gif_data', true );

	// if edit activity/comment, then delete attachment and clear activity meta.
	$is_delete_gif = false;
	if ( $bp_activity_edit && isset( $_POST['edit'] ) && empty( $gif_data ) && isset( $_POST['id'] ) && $activity->id === intval( $_POST['id'] ) ) {
		$is_delete_gif = true;
	} elseif ( $bb_activity_comment_edit && isset( $_POST['edit_comment'] ) && empty( $gif_data ) ) {
		$is_delete_gif = true;
	}

	// if edit activity then delete attachment and clear activity meta.
	if ( $is_delete_gif ) {
		if ( ! empty( $gif_old_data ) ) {
			wp_delete_attachment( $gif_old_data['still'], true );
			wp_delete_attachment( $gif_old_data['mp4'], true );
		}

		bp_activity_delete_meta( $activity->id, '_gif_data' );
		bp_activity_delete_meta( $activity->id, '_gif_raw_data' );
	}

	if ( ! empty( $gif_data ) && ! isset( $gif_data['bp_gif_current_data'] ) ) {
				$still = $gif_data['images']['480w_still']['url'];
		$mp4           = $gif_data['images']['original_mp4']['mp4'];

		bp_activity_update_meta(
			$activity->id,
			'_gif_data',
			array(
				'still' => $still,
				'mp4'   => $mp4,
			)
		);

		bp_activity_update_meta( $activity->id, '_gif_raw_data', $gif_data );
	}
}

function bp_media_get_tools_media_settings_admin_tabs( $tabs ) {

	$tabs[] = array(
		'href' => bp_get_admin_url(
			add_query_arg(
				array(
					'page' => 'bp-media-import',
					'tab'  => 'bp-media-import',
				),
				'admin.php'
			)
		),
		'name' => __( 'Import Media', 'buddyboss' ),
		'slug' => 'bp-media-import',
	);

	return $tabs;
}

/**
 * Add Import Media admin menu in tools
 *
 * @since BuddyPress 3.0.0
 */
function bp_media_import_admin_menu() {

	add_submenu_page(
		'buddyboss-platform',
		__( 'Import Media', 'buddyboss' ),
		__( 'Import Media', 'buddyboss' ),
		'manage_options',
		'bp-media-import',
		'bp_media_import_submenu_page'
	);

}

add_action( bp_core_admin_hook(), 'bp_media_import_admin_menu' );

/**
 * Import Media menu page
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_import_submenu_page() {
	global $wpdb;
	global $bp;

	$bp_media_import_status = get_option( 'bp_media_import_status' );

	$check                        = false;
	$buddyboss_media_table        = $bp->table_prefix . 'buddyboss_media';
	$buddyboss_media_albums_table = $bp->table_prefix . 'buddyboss_media_albums';
	if ( empty( $wpdb->get_results( "SHOW TABLES LIKE '{$buddyboss_media_table}' ;" ) ) || empty( $wpdb->get_results( "SHOW TABLES LIKE '{$buddyboss_media_albums_table}' ;" ) ) ) {
		$check = true;
	}

	$is_updating = false;
	if ( isset( $_POST['bp-media-import-submit'] ) && ! $check ) {
		if ( 'done' != $bp_media_import_status || isset( $_POST['bp-media-re-run-import'] ) ) {
			update_option( 'bp_media_import_status', 'reset_albums' );
			$is_updating = true;
		}
	}

	if ( in_array(
		$bp_media_import_status,
		array(
			'importing',
			'start',
			'reset_albums',
			'reset_media',
			'reset_forum',
			'reset_topic',
			'reset_reply',
			'reset_options',
		)
	) ) {
		$is_updating = true;
	}

	?>
	<div class="wrap">
		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Tools', 'buddyboss' ) ); ?></h2>
		<div class="nav-settings-subsubsub">
			<ul class="subsubsub">
				<?php bp_core_tools_settings_admin_tabs(); ?>
			</ul>
		</div>
	</div>
	<div class="wrap">
		<div class="bp-admin-card section-bp-member-type-import">
			<div class="boss-import-area">
				<form id="bp-member-type-import-form" method="post" action="">
					<div class="import-panel-content">
						<h2>
							<?php
							$meta_icon = bb_admin_icons( 'bp-member-type-import' );
							if ( ! empty( $meta_icon ) ) {
								echo '<i class="' . esc_attr( $meta_icon ) . '"></i>';
							}
							esc_html_e( 'Import Media', 'buddyboss' );
							?>
						</h2>

						<?php
						if ( $check ) {
							?>
							<p><?php _e( 'BuddyBoss Media plugin database tables do not exist, meaning you have nothing to import.', 'buddyboss' ); ?></p>
							<?php
						} elseif ( $is_updating ) {
							$total_media   = get_option( 'bp_media_import_total_media', 0 );
							$total_albums  = get_option( 'bp_media_import_total_albums', 0 );
							$albums_done   = get_option( 'bp_media_import_albums_done', 0 );
							$media_done    = get_option( 'bp_media_import_media_done', 0 );
							$forums_done   = get_option( 'bp_media_import_forums_done', 0 );
							$forums_total  = get_option( 'bp_media_import_forums_total', 0 );
							$topics_done   = get_option( 'bp_media_import_topics_done', 0 );
							$topics_total  = get_option( 'bp_media_import_topics_total', 0 );
							$replies_done  = get_option( 'bp_media_import_replies_done', 0 );
							$replies_total = get_option( 'bp_media_import_replies_total', 0 );
							$albums_ids    = get_option( 'bp_media_import_albums_ids', array() );
							$media_ids     = get_option( 'bp_media_import_media_ids', array() );
							?>
							<p>
								<?php esc_html_e( 'Your database is being updated in the background.', 'buddyboss' ); ?>
							</p>
							<label style="display: none;"
								   id="bp-media-resetting"><strong><?php echo __( 'Migration in progress', 'buddyboss' ) . '...'; ?></strong></label>
							<table class="form-table">
								<tr>
									<th scope="row"><?php _e( 'Albums', 'buddyboss' ); ?></th>
									<td>
										<span id="bp-media-import-albums-done"><?php echo $albums_done; ?></span> <?php _e( 'out of', 'buddyboss' ); ?>
										<span id="bp-media-import-albums-total"><?php echo $total_albums; ?></span></td>
								</tr>
								<tr>
									<th scope="row"><?php _e( 'Media', 'buddyboss' ); ?></th>
									<td>
										<span id="bp-media-import-media-done"><?php echo $media_done; ?></span> <?php _e( 'out of', 'buddyboss' ); ?>
										<span id="bp-media-import-media-total"><?php echo $total_media; ?></span></td>
								</tr>
								<tr>
									<th scope="row"><?php _e( 'Forums', 'buddyboss' ); ?></th>
									<td>
										<span id="bp-media-import-forums-done"><?php echo $forums_done; ?></span> <?php _e( 'out of', 'buddyboss' ); ?>
										<span id="bp-media-import-media-total"><?php echo $forums_total; ?></span></td>
								</tr>
								<tr>
									<th scope="row"><?php _e( 'Discussions', 'buddyboss' ); ?></th>
									<td>
										<span id="bp-media-import-forums-done"><?php echo $topics_done; ?></span> <?php _e( 'out of', 'buddyboss' ); ?>
										<span id="bp-media-import-media-total"><?php echo $topics_total; ?></span></td>
								</tr>
								<tr>
									<th scope="row"><?php _e( 'Replies', 'buddyboss' ); ?></th>
									<td>
										<span id="bp-media-import-forums-done"><?php echo $replies_done; ?></span> <?php _e( 'out of', 'buddyboss' ); ?>
										<span id="bp-media-import-media-total"><?php echo $replies_total; ?></span></td>
								</tr>
							</table>
							<p>
								<label id="bp-media-import-msg"></label>
							</p>
							<input type="hidden" value="bp-media-import-updating" id="bp-media-import-updating"/>
							<?php if ( ! empty( $albums_ids ) || ! empty( $media_ids ) ) { ?>
								<input type="hidden" value="1" name="bp-media-re-run-import"
									   id="bp-media-re-run-import"/>
								<input type="submit" style="display: none;"
									   value="<?php _e( 'Re-Run Migration', 'buddyboss' ); ?>"
									   id="bp-media-import-submit" name="bp-media-import-submit"
									   class="button-primary"/>
								<?php
							}
						} elseif ( 'done' == $bp_media_import_status ) {
							$albums_ids = get_option( 'bp_media_import_albums_ids', array() );
							$media_ids  = get_option( 'bp_media_import_media_ids', array() );
							?>
							<p><?php _e( 'BuddyBoss Media data update is complete! Any previously uploaded member photos should display in their profiles now.', 'buddyboss' ); ?></p>

							<?php if ( ! empty( $albums_ids ) || ! empty( $media_ids ) ) { ?>
								<input type="hidden" value="1" name="bp-media-re-run-import"
									   id="bp-media-re-run-import"/>
								<input type="submit" value="<?php _e( 'Re-Run Migration', 'buddyboss' ); ?>"
									   id="bp-media-import-submit" name="bp-media-import-submit"
									   class="button-primary"/>
								<?php
							}
						} else {
							?>
							<p><?php _e( 'Import your existing members photo uploads, if you were previously using <a href="https://www.buddyboss.com/product/buddyboss-media/">BuddyBoss Media</a> with BuddyPress. Click "Run Migration" below to migrate your old photos into the new Media component.', 'buddyboss' ); ?></p>
							<input type="submit" value="<?php _e( 'Run Migration', 'buddyboss' ); ?>"
								   id="bp-media-import-submit" name="bp-media-import-submit" class="button-primary"/>
						<?php } ?>
					</div>
				</form>
			</div>
		</div>
	</div>
	<br/>

	<?php
}

/**
 * Hook to display admin notices when media component is active
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_activation_notice() {
	global $wpdb;
	global $bp;

	if ( ! empty( $_GET['page'] ) && 'bp-media-import' === $_GET['page'] ) {
		return;
	}

	$bp_media_import_status = get_option( 'bp_media_import_status' );

	if ( 'done' != $bp_media_import_status ) {

		$buddyboss_media_table        = $bp->table_prefix . 'buddyboss_media';
		$buddyboss_media_albums_table = $bp->table_prefix . 'buddyboss_media_albums';

		if ( ! empty( $wpdb->get_results( "SHOW TABLES LIKE '{$buddyboss_media_table}' ;" ) ) && ! empty( $wpdb->get_results( "SHOW TABLES LIKE '{$buddyboss_media_albums_table}' ;" ) ) ) {

			$admin_url = bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-media-import',
						'tab'  => 'bp-media-import',
					),
					'admin.php'
				)
			);
			$notice    = sprintf(
				'%1$s <a href="%2$s">%3$s</a>',
				__( 'We have found some media uploaded from the <strong>BuddyBoss Media</strong></strong> plugin, which is not compatible with BuddyBoss Platform as it has its own media component. You should  import the media into BuddyBoss Platform, and then remove the BuddyBoss Media plugin if you are still using it.', 'buddyboss' ),
				esc_url( $admin_url ),
				__( 'Import Media', 'buddyboss' )
			);

			bp_core_add_admin_notice( $notice );
		}
	}
}

/**
 * Delete media entries attached to the attachment
 *
 * @since BuddyBoss 1.2.0
 *
 * @param int $attachment_id ID of the attachment being deleted.
 */
function bp_media_delete_attachment_media( $attachment_id ) {
	global $wpdb;

	$bp = buddypress();

	$media = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->media->table_name} WHERE attachment_id = %d", $attachment_id ) );

	if ( ! $media ) {
		return false;
	}

	remove_action( 'delete_attachment', 'bp_media_delete_attachment_media', 0 );

	bp_media_delete( array( 'id' => $media->id ), 'attachment' );

	add_action( 'delete_attachment', 'bp_media_delete_attachment_media', 0 );
}

/**
 * Clear a user's symlinks media when attachment media delete.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param array $medias DB results of media items.
 */
function bp_media_clear_media_symlinks_on_delete( $medias ) {
	if ( ! empty( $medias[0] ) ) {
		foreach ( (array) $medias as $deleted_media ) {
			if ( isset( $deleted_media->id ) ) {
				bp_media_delete_symlinks( $deleted_media->id );
			}
		}
	}
}

/**
 * Update media privacy when activity is updated.
 *
 * @since BuddyBoss 1.2.3
 *
 * @param BP_Activity_Activity $activity Activity object.
 */
function bp_media_activity_update_media_privacy( $activity ) {
	$media_ids = bp_activity_get_meta( $activity->id, 'bp_media_ids', true );

	if ( ! empty( $media_ids ) ) {
		$media_ids = explode( ',', $media_ids );

		foreach ( $media_ids as $media_id ) {
			$media = new BP_Media( $media_id );
			// Do not update the privacy if the media is added to forum.
			if (
				! in_array( $media->privacy, array( 'forums', 'message', 'media', 'document', 'grouponly', 'video' ), true ) &&
				'comment' !== $media->privacy &&
				! empty( $media->blog_id )
			) {
				$media->privacy = $activity->privacy;
				$media->save();
			}
		}
	}
}

/**
 * Remove the meta if thread is deleted.
 *
 * @since BuddyBoss 1.2.9
 *
 * @param $thread_id
 * @param $message_ids
 */
function bp_group_messages_delete_meta( $thread_id, $message_ids ) {

	if ( false === bp_disable_group_messages() ) {
		return;
	}

	if ( ! empty( $message_ids ) ) {
		foreach ( $message_ids as $message_id ) {
			if ( bp_loggedin_user_id() === messages_get_message_sender( $message_id ) ) {
				bp_messages_delete_meta( $message_id );
			}
		}
	}

}

/**
 * Set up activity arguments for use with the 'media' scope.
 *
 * @since BuddyBoss 1.4.2
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 *
 * @return array $retval
 */
function bp_activity_filter_media_scope( $retval = array(), $filter = array() ) {

	$retval = array(
		'relation' => 'AND',
		array(
			'column'  => 'privacy',
			'value'   => 'media',
			'compare' => '=',
		),
		array(
			'column' => 'hide_sitewide',
			'value'  => 1,
		),
	);

	return $retval;
}

add_filter( 'bp_activity_set_media_scope_args', 'bp_activity_filter_media_scope', 10, 2 );

/**
 * Add media repair list item.
 *
 * @param $repair_list
 *
 * @since BuddyBoss 1.4.4
 * @return array Repair list items.
 */
function bp_media_add_admin_repair_items( $repair_list ) {
	if ( bp_is_active( 'activity' ) ) {
		$repair_list[] = array(
			'bp-repair-media',
			esc_html__( 'Repair media', 'buddyboss' ),
			'bp_media_admin_repair_media',
		);
		$repair_list[] = array(
			'bp-media-forum-privacy-repair',
			esc_html__( 'Repair forum media privacy', 'buddyboss' ),
			'bp_media_forum_privacy_repair',
		);
		$repair_list[] = array(
			'bp-media-message-repair',
			esc_html__( 'Repair messages media', 'buddyboss' ),
			'bp_media_message_privacy_repair',
		);
	}

	return $repair_list;
}

/**
 * Repair BuddyBoss messages media.
 *
 * @since BuddyBoss 1.5.5
 */
function bp_media_message_privacy_repair() {
	global $wpdb;
	$offset = isset( $_POST['offset'] ) ? (int) ( $_POST['offset'] ) : 0;
	$bp     = buddypress();

	$media_query = "SELECT id FROM {$bp->media->table_name} WHERE privacy = 'message' AND type = 'photo' LIMIT 20 OFFSET $offset ";
	$medias      = $wpdb->get_results( $media_query );

	if ( ! empty( $medias ) ) {
		foreach ( $medias as $media ) {
			if ( ! empty( $media->id ) ) {
				$media_obj              = new BP_Media( $media->id );
				$media_obj->album_id    = 0;
				$media_obj->group_id    = 0;
				$media_obj->activity_id = 0;
				$media_obj->privacy     = 'message';
				$media_obj->save();
			}
			$offset ++;
		}
		$records_updated = sprintf( __( '%s messages updated successfully.', 'buddyboss' ), bp_core_number_format( $offset ) );

		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => $records_updated,
		);
	} else {
		return array(
			'status'  => 1,
			'message' => __( 'Repairing messages media &hellip; Complete!', 'buddyboss' ),
		);
	}
}

/**
 * Repair BuddyBoss media.
 *
 * @since BuddyBoss 1.4.4
 */
function bp_media_admin_repair_media() {
	global $wpdb;
	$offset = isset( $_POST['offset'] ) ? (int) ( $_POST['offset'] ) : 0;
	$bp     = buddypress();

	$media_query = "SELECT id, activity_id FROM {$bp->media->table_name} WHERE activity_id != 0 AND type = 'photo' LIMIT 50 OFFSET $offset ";
	$medias      = $wpdb->get_results( $media_query );

	if ( ! empty( $medias ) ) {
		foreach ( $medias as $media ) {
			if ( ! empty( $media->id ) && ! empty( $media->activity_id ) ) {
				$activity = new BP_Activity_Activity( $media->activity_id );
				if ( ! empty( $activity->id ) ) {
					if ( 'activity_comment' === $activity->type ) {
						$activity = new BP_Activity_Activity( $activity->item_id );
					}
					if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $activity->component ) {
						$update_query = "UPDATE {$bp->media->table_name} SET group_id=" . $activity->item_id . ", privacy='grouponly' WHERE id=" . $media->id . ' ';
						$wpdb->query( $update_query );
					}
					if ( 'media' === $activity->privacy ) {
						if ( ! empty( $activity->secondary_item_id ) ) {
							$media_activity = new BP_Activity_Activity( $activity->secondary_item_id );
							if ( ! empty( $media_activity->id ) ) {
								if ( 'activity_comment' === $media_activity->type ) {
									$media_activity = new BP_Activity_Activity( $media_activity->item_id );
								}
								if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $media_activity->component ) {
									$update_query = "UPDATE {$bp->media->table_name} SET group_id=" . $media_activity->item_id . ", privacy='grouponly' WHERE id=" . $media->id . ' ';
									$wpdb->query( $update_query );
									$activity->item_id   = $media_activity->item_id;
									$activity->component = buddypress()->groups->id;
								}
							}
						}
						$activity->hide_sitewide = true;
						$activity->save();
					}
				}
			}
			$offset ++;
		}
		$records_updated = sprintf( __( '%s media updated successfully.', 'buddyboss' ), bp_core_number_format( $offset ) );

		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => $records_updated,
		);
	} else {
		return array(
			'status'  => 1,
			'message' => __( 'Repairing media &hellip; Complete!', 'buddyboss' ),
		);
	}
}

/**
 * Repair BuddyBoss media forums privacy.
 *
 * @since BuddyBoss 1.4.2
 */
function bp_media_forum_privacy_repair() {
	global $wpdb;
	$offset = isset( $_POST['offset'] ) ? (int) ( $_POST['offset'] ) : 0;
	$bp     = buddypress();

	$squery  = "SELECT p.ID as post_id FROM {$wpdb->posts} p, {$wpdb->postmeta} pm WHERE p.ID = pm.post_id and p.post_type in ( 'forum', 'topic', 'reply' ) and pm.meta_key = 'bp_media_ids' and pm.meta_value != '' LIMIT 20 OFFSET $offset ";
	$records = $wpdb->get_col( $squery );
	if ( ! empty( $records ) ) {
		foreach ( $records as $record ) {
			if ( ! empty( $record ) ) {
				$media_ids = get_post_meta( $record, 'bp_media_ids', true );
				if ( $media_ids ) {
					$update_query = "UPDATE {$bp->media->table_name} SET `privacy`= 'forums' WHERE id in (" . $media_ids . ')';
					$wpdb->query( $update_query );
				}
			}
			$offset ++;
		}
		$records_updated = sprintf( __( '%s forums media privacy updated successfully.', 'buddyboss' ), bp_core_number_format( $offset ) );

		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => $records_updated,
		);
	} else {
		$statement = __( 'Repair forum media privacy &hellip; %s', 'buddyboss' );

		return array(
			'status'  => 1,
			'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		);
	}
}


/**
 * Set up media arguments for use with the 'public' scope.
 *
 * @since BuddyBoss 1.1.9
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 *
 * @return array
 */
function bp_media_filter_public_scope( $retval = array(), $filter = array() ) {

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
				? bp_displayed_user_id()
				: bp_loggedin_user_id();
	}

	$privacy = array( 'public' );
	if ( is_user_logged_in() && bp_is_profile_media_support_enabled() ) {
		$privacy[] = 'loggedin';
	}

	$args = array(
		'relation' => 'AND',
		array(
			'column'  => 'privacy',
			'compare' => 'IN',
			'value'   => $privacy,
		),
	);

	if ( ! bp_is_profile_media_support_enabled() && ! bp_is_profile_albums_support_enabled() ) {
		$args[] = array(
			'column'  => 'user_id',
			'compare' => '=',
			'value'   => '0',
		);
	}

	if ( ! bp_is_profile_albums_support_enabled() ) {
		$args[] = array(
			'column'  => 'album_id',
			'compare' => '=',
			'value'   => '0',
		);
	}

	if ( ! empty( $filter['search_terms'] ) ) {
		$args[] = array(
			'relation' => 'OR',
			array(
				'column'  => 'title',
				'compare' => 'LIKE',
				'value'   => $filter['search_terms'],
			),
			array(
				'column'  => 'description',
				'compare' => 'LIKE',
				'value'   => $filter['search_terms'],
			)
		);
	}

	$retval = array(
		'relation' => 'OR',
		$args,
	);

	return $retval;
}

add_filter( 'bp_media_set_public_scope_args', 'bp_media_filter_public_scope', 10, 2 );

/**
 * Force download - this is the default method.
 *
 * @param string $file_path File path.
 * @param string $filename  File name.
 *
 * @since BuddyBoss 1.4.1
 */
function bp_media_download_file_force( $file_path, $filename ) {
	$parsed_file_path = bp_media_parse_file_path( $file_path );
	$download_range   = bp_media_get_download_range( @filesize( $parsed_file_path['file_path'] ) ); // @codingStandardsIgnoreLine.

	bp_media_download_headers( $parsed_file_path['file_path'], $filename, $download_range );

	$start  = isset( $download_range['start'] ) ? $download_range['start'] : 0;
	$length = isset( $download_range['length'] ) ? $download_range['length'] : 0;
	if ( ! bp_media_readfile_chunked( $parsed_file_path['file_path'], $start, $length ) ) {
		if ( $parsed_file_path['remote_file'] ) {
			bp_media_download_file_redirect( $file_path );
		} else {
			bp_media_download_error( __( 'File not found', 'buddyboss' ) );
		}
	}

	exit;
}

/**
 * Die with an error message if the download fails.
 *
 * @param string  $message Error message.
 * @param string  $title   Error title.
 * @param integer $status  Error status.
 *
 * @since BuddyBoss 1.4.1
 */
function bp_media_download_error( $message, $title = '', $status = 404 ) {
	if ( ! strstr( $message, '<a ' ) ) {
		$message .= ' <a href="' . esc_url( site_url() ) . '" class="bp-media-forward">' . esc_html__( 'Go to media', 'buddyboss' ) . '</a>';
	}
	wp_die( $message, $title, array( 'response' => $status ) ); // WPCS: XSS ok.
}

/**
 * Redirect to a file to start the download.
 *
 * @param string $file_path File path.
 * @param string $filename  File name.
 *
 * @since BuddyBoss 1.4.1
 */
function bp_media_download_file_redirect( $file_path, $filename = '' ) {
	header( 'Location: ' . $file_path );
	exit;
}

/**
 * Read file chunked.
 *
 * Reads file in chunks so big downloads are possible without changing PHP.INI -
 * http://codeigniter.com/wiki/Download_helper_for_large_files/.
 *
 * @param string $file   File.
 * @param int    $start  Byte offset/position of the beginning from which to read from the file.
 * @param int    $length Length of the chunk to be read from the file in bytes, 0 means full file.
 *
 * @return bool Success or fail
 * @since BuddyBoss 1.4.1
 */
function bp_media_readfile_chunked( $file, $start = 0, $length = 0 ) {
	if ( ! defined( 'BP_MEDIA_CHUNK_SIZE' ) ) {
		define( 'BP_MEDIA_CHUNK_SIZE', 1024 * 1024 );
	}
	$handle = @fopen( $file, 'r' ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen

	if ( false === $handle ) {
		return false;
	}

	if ( ! $length ) {
		$length = @filesize( $file ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
	}

	$read_length = (int) BP_MEDIA_CHUNK_SIZE;

	if ( $length ) {
		$end = $start + $length - 1;

		@fseek( $handle, $start ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
		$p = @ftell( $handle ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged

		while ( ! @feof( $handle ) && $p <= $end ) { // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
			// Don't run past the end of file.
			if ( $p + $read_length > $end ) {
				$read_length = $end - $p + 1;
			}

			echo @fread( $handle, $read_length ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.XSS.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions.file_system_read_fread
			$p = @ftell( $handle ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged

			if ( ob_get_length() ) {
				ob_flush();
				flush();
			}
		}
	} else {
		while ( ! @feof( $handle ) ) { // @codingStandardsIgnoreLine.
			echo @fread( $handle, $read_length ); // @codingStandardsIgnoreLine.
			if ( ob_get_length() ) {
				ob_flush();
				flush();
			}
		}
	}

	return @fclose( $handle ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fclose
}

/**
 * Set headers for the download.
 *
 * @param string $file_path      File path.
 * @param string $filename       File name.
 * @param array  $download_range Array containing info about range download request (see {@see get_download_range} for
 *                               structure).
 *
 * @since BuddyBoss 1.4.1
 */
function bp_media_download_headers( $file_path, $filename, $download_range = array() ) {
	bp_media_check_server_config();
	bp_media_clean_buffers();
	bp_media_nocache_headers();

	header( 'X-Robots-Tag: noindex, nofollow', true );
	header( 'Content-Type: ' . bp_media_get_download_content_type( $file_path ) );
	header( 'Content-Description: File Transfer' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
	header( 'Content-Transfer-Encoding: binary' );

	$file_size = @filesize( $file_path ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
	if ( ! $file_size ) {
		return;
	}

	if ( isset( $download_range['is_range_request'] ) && true === $download_range['is_range_request'] ) {
		if ( false === $download_range['is_range_valid'] ) {
			header( 'HTTP/1.1 416 Requested Range Not Satisfiable' );
			header( 'Content-Range: bytes 0-' . ( $file_size - 1 ) . '/' . $file_size );
			exit;
		}

		$start  = $download_range['start'];
		$end    = $download_range['start'] + $download_range['length'] - 1;
		$length = $download_range['length'];

		header( 'HTTP/1.1 206 Partial Content' );
		header( "Accept-Ranges: 0-$file_size" );
		header( "Content-Range: bytes $start-$end/$file_size" );
		header( "Content-Length: $length" );
	} else {
		header( 'Content-Length: ' . $file_size );
	}
}

/**
 * Wrapper for set_time_limit to see if it is enabled.
 *
 * @since BuddyBoss 1.4.1
 *
 * @param int $limit Time limit.
 */
function bp_media_set_time_limit( $limit = 0 ) {
	if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) ) { // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
		@set_time_limit( $limit ); // @codingStandardsIgnoreLine
	}
}

/**
 * Check and set certain server config variables to ensure downloads work as intended.
 *
 * @since BuddyBoss 1.4.1
 */
function bp_media_check_server_config() {
	bp_media_set_time_limit( 0 );
	if ( function_exists( 'apache_setenv' ) ) {
		@apache_setenv( 'no-gzip', 1 ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_apache_setenv
	}
	@ini_set( 'zlib.output_compression', 'Off' ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_ini_set
	@session_write_close(); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.VIP.SessionFunctionsUsage.session_session_write_close
}

/**
 * Clean all output buffers.
 *
 * Can prevent errors, for example: transfer closed with 3 bytes remaining to read.
 *
 * @since BuddyBoss 1.4.1
 */
function bp_media_clean_buffers() {
	if ( ob_get_level() ) {
		$levels = ob_get_level();
		for ( $i = 0; $i < $levels; $i ++ ) {
			@ob_end_clean(); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
		}
	} else {
		@ob_end_clean(); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
	}
}

/**
 * Set constants to prevent caching by some plugins.
 *
 * @param mixed $return Value to return. Previously hooked into a filter.
 *
 * @return mixed
 * @since BuddyBoss 1.4.1
 */
function bp_media_set_nocache_constants( $return = true ) {
	bp_media_maybe_define_constant( 'DONOTCACHEPAGE', true );
	bp_media_maybe_define_constant( 'DONOTCACHEOBJECT', true );
	bp_media_maybe_define_constant( 'DONOTCACHEDB', true );

	return $return;
}

/**
 * Define a constant if it is not already defined.
 *
 * @since BuddyBoss 1.4.1
 *
 * @param string $name  Constant name.
 * @param mixed  $value Value.
 */
function bp_media_maybe_define_constant( $name, $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}

/**
 * Wrapper for nocache_headers which also disables page caching.
 *
 * @since BuddyBoss 1.4.1
 */
function bp_media_nocache_headers() {
	bp_media_set_nocache_constants();
	nocache_headers();
}

/**
 * Get content type of a download.
 *
 * @param string $file_path File path.
 *
 * @return string
 * @since BuddyBoss 1.4.1
 */
function bp_media_get_download_content_type( $file_path ) {
	$file_extension = strtolower( substr( strrchr( $file_path, '.' ), 1 ) );
	$ctype          = 'application/force-download';

	foreach ( get_allowed_mime_types() as $mime => $type ) {
		$mimes = explode( '|', $mime );
		if ( in_array( $file_extension, $mimes, true ) ) {
			$ctype = $type;
			break;
		}
	}

	return $ctype;
}

/**
 * Parse the HTTP_RANGE request from iOS devices.
 * Does not support multi-range requests.
 *
 * @param int $file_size        Size of file in bytes.
 *
 * @return array {
 *     Information about range download request: beginning and length of
 *     file chunk, whether the range is valid/supported and whether the request is a range request.
 *
 * @type int  $start            Byte offset of the beginning of the range. Default 0.
 * @type int  $length           Length of the requested file chunk in bytes. Optional.
 * @type bool $is_range_valid   Whether the requested range is a valid and supported range.
 * @type bool $is_range_request Whether the request is a range request.
 * }
 * @since BuddyBoss 1.4.1
 */
function bp_media_get_download_range( $file_size ) {
	$start          = 0;
	$download_range = array(
		'start'            => $start,
		'is_range_valid'   => false,
		'is_range_request' => false,
	);

	if ( ! $file_size ) {
		return $download_range;
	}

	$end                      = $file_size - 1;
	$download_range['length'] = $file_size;

	if ( isset( $_SERVER['HTTP_RANGE'] ) ) { // @codingStandardsIgnoreLine.
		$http_range                         = sanitize_text_field( wp_unslash( $_SERVER['HTTP_RANGE'] ) ); // WPCS: input var ok.
		$download_range['is_range_request'] = true;

		$c_start = $start;
		$c_end   = $end;
		// Extract the range string.
		list( , $range ) = explode( '=', $http_range, 2 );
		// Make sure the client hasn't sent us a multibyte range.
		if ( strpos( $range, ',' ) !== false ) {
			return $download_range;
		}

		/*
		 * If the range starts with an '-' we start from the beginning.
		 * If not, we forward the file pointer
		 * and make sure to get the end byte if specified.
		 */
		if ( '-' === $range[0] ) {
			// The n-number of the last bytes is requested.
			$c_start = $file_size - substr( $range, 1 );
		} else {
			$range   = explode( '-', $range );
			$c_start = ( isset( $range[0] ) && is_numeric( $range[0] ) ) ? (int) $range[0] : 0;
			$c_end   = ( isset( $range[1] ) && is_numeric( $range[1] ) ) ? (int) $range[1] : $file_size;
		}

		/*
		 * Check the range and make sure it's treated according to the specs: http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html.
		 * End bytes can not be larger than $end.
		 */
		$c_end = ( $c_end > $end ) ? $end : $c_end;
		// Validate the requested range and return an error if it's not correct.
		if ( $c_start > $c_end || $c_start > $file_size - 1 || $c_end >= $file_size ) {
			return $download_range;
		}
		$start  = $c_start;
		$end    = $c_end;
		$length = $end - $start + 1;

		$download_range['start']          = $start;
		$download_range['length']         = $length;
		$download_range['is_range_valid'] = true;
	}

	return $download_range;
}

/**
 * Parse file path and see if its remote or local.
 *
 * @param string $file_path File path.
 *
 * @return array
 * @since BuddyBoss 1.4.1
 */
function bp_media_parse_file_path( $file_path ) {
	$wp_uploads     = wp_upload_dir();
	$wp_uploads_dir = $wp_uploads['basedir'];
	$wp_uploads_url = $wp_uploads['baseurl'];

	/**
	 * Replace uploads dir, site url etc with absolute counterparts if we can.
	 * Note the str_replace on site_url is on purpose, so if https is forced
	 * via filters we can still do the string replacement on a HTTP file.
	 */
	$replacements = array(
		$wp_uploads_url                  => $wp_uploads_dir,
		network_site_url( '/', 'https' ) => ABSPATH,
		str_replace( 'https:', 'http:', network_site_url( '/', 'http' ) ) => ABSPATH,
		site_url( '/', 'https' )         => ABSPATH,
		str_replace( 'https:', 'http:', site_url( '/', 'http' ) ) => ABSPATH,
	);

	$file_path        = str_replace( array_keys( $replacements ), array_values( $replacements ), $file_path );
	$parsed_file_path = wp_parse_url( $file_path );
	$remote_file      = true;

	// Paths that begin with '//' are always remote URLs.
	if ( '//' === substr( $file_path, 0, 2 ) ) {
		return array(
			'remote_file' => true,
			'file_path'   => is_ssl() ? 'https:' . $file_path : 'http:' . $file_path,
		);
	}

	// See if path needs an abspath prepended to work.
	if ( file_exists( ABSPATH . $file_path ) ) {
		$remote_file = false;
		$file_path   = ABSPATH . $file_path;

	} elseif ( '/wp-content' === substr( $file_path, 0, 11 ) ) {
		$remote_file = false;
		$file_path   = realpath( WP_CONTENT_DIR . substr( $file_path, 11 ) );

		// Check if we have an absolute path.
	} elseif ( ( ! isset( $parsed_file_path['scheme'] ) || ! in_array(
		$parsed_file_path['scheme'],
		array(
			'http',
			'https',
			'ftp',
		),
		true
	) ) && isset( $parsed_file_path['path'] ) && file_exists( $parsed_file_path['path'] ) ) {
		$remote_file = false;
		$file_path   = $parsed_file_path['path'];
	}

	return array(
		'remote_file' => $remote_file,
		'file_path'   => $file_path,
	);
}

/**
 * Adds activity media data for the edit activity
 *
 * @param array $activity Activity data.
 *
 * @return array $activity Returns the activity with media if media saved otherwise no media.
 *
 * @since BuddyBoss 1.5.0
 */
function bp_media_get_edit_activity_data( $activity ) {

	if ( ! empty( $activity['id'] ) ) {

		// Fetch media ids of activity.
		$media_ids = bp_activity_get_meta( $activity['id'], 'bp_media_ids', true );
		$media_id  = bp_activity_get_meta( $activity['id'], 'bp_media_id', true );

		if ( ! empty( $media_id ) && ! empty( $media_ids ) ) {
			$media_ids = $media_ids . ',' . $media_id;
		} elseif ( ! empty( $media_id ) && empty( $media_ids ) ) {
			$media_ids = $media_id;
		}

		if ( ! empty( $media_ids ) ) {
			$activity['media'] = array();

			$media_ids = explode( ',', $media_ids );
			$media_ids = array_unique( $media_ids );
			$album_id  = 0;

			foreach ( $media_ids as $media_id ) {

				if ( bp_is_active( 'moderation' ) && bp_moderation_is_content_hidden( $media_id, BP_Moderation_Media::$moderation_type ) ) {
					continue;
				}

				$media = new BP_Media( $media_id );

				if ( empty( $media->id ) ) {
					continue;
				}

				$activity['media'][] = array(
					'id'            => $media_id,
					'attachment_id' => $media->attachment_id,
					'thumb'         => bp_media_get_preview_image_url( $media->id, $media->attachment_id, 'bb-media-activity-image' ),
					'url'           => bp_media_get_preview_image_url( $media->id, $media->attachment_id, 'bb-media-photos-popup-image' ),
					'name'          => $media->title,
					'group_id'      => $media->group_id,
					'album_id'      => $media->album_id,
					'activity_id'   => $media->activity_id,
					'saved'         => true,
					'menu_order'    => $media->menu_order,
				);

				if ( 0 === $album_id && $media->album_id > 0 ) {
					$album_id                     = $media->album_id;
					$activity['can_edit_privacy'] = false;
				}
			}
		}

		// Fetch gif data for the activity.
		$gif_data = bp_activity_get_meta( $activity['id'], '_gif_data', true );

		if ( ! empty( $gif_data ) ) {
			$gif_raw_data                        = (array) bp_activity_get_meta( $activity['id'], '_gif_raw_data', true );
			$gif_raw_data['bp_gif_current_data'] = '1';

			$activity['gif'] = $gif_raw_data;
		}

		$activity['profile_media'] = bp_is_profile_media_support_enabled() && bb_media_user_can_upload( bp_loggedin_user_id(), 0 );
		$activity['group_media']   = bp_is_group_media_support_enabled() && bb_media_user_can_upload( bp_loggedin_user_id(), ( bp_is_active( 'groups' ) && 'groups' === $activity['object'] ? $activity['item_id'] : 0 ) );
	}

	return $activity;
}

/**
 * Added activity entry class for media.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param string $class class.
 *
 * @return string
 */
function bp_media_activity_entry_css_class( $class ) {

	if ( bp_is_active( 'media' ) && bp_is_active( 'activity' ) ) {

		$media_ids = bp_activity_get_meta( bp_get_activity_id(), 'bp_media_ids', true );
		if ( ! empty( $media_ids ) ) {
			$class .= ' media-activity-wrap';
		}
	}

	return $class;

}

/**
 * Protect downloads from ms-files.php in multisite.
 *
 * @param string $rewrite rewrite rules.
 * @return string
 * @since BuddyBoss 1.7.0
 */
function bp_media_protect_download_rewite_rules( $rewrite ) {
	if ( ! is_multisite() ) {
		return $rewrite;
	}

	$rule  = "\n# Media Rules - Protect Files from ms-files.php\n\n";
	$rule .= "<IfModule mod_rewrite.c>\n";
	$rule .= "RewriteEngine On\n";
	$rule .= "RewriteCond %{QUERY_STRING} file=media_uploads/ [NC]\n";
	$rule .= "RewriteRule /ms-files.php$ - [F]\n";
	$rule .= "</IfModule>\n\n";

	return $rule . $rewrite;
}
add_filter( 'mod_rewrite_rules', 'bp_media_protect_download_rewite_rules' );

/**
 * Function will protect the download album.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_media_check_download_album_protection() {

	$upload_dir = wp_get_upload_dir();
	$files      = array(
		array(
			'base'    => $upload_dir['basedir'] . '/bb_medias',
			'file'    => 'index.html',
			'content' => '',
		),
		array(
			'base'    => $upload_dir['basedir'] . '/bb-platform-previews',
			'file'    => 'index.html',
			'content' => '',
		),
		array(
			'base'    => $upload_dir['basedir'] . '/bb-platform-previews/' . md5( 'bb-media' ),
			'file'    => 'index.html',
			'content' => '',
		),
		array(
			'base'    => $upload_dir['basedir'] . '/bb-platform-previews/' . md5( 'bb-videos' ),
			'file'    => 'index.html',
			'content' => '',
		),
		array(
			'base'    => $upload_dir['basedir'] . '/bb-platform-previews/' . md5( 'bb-documents' ),
			'file'    => 'index.html',
			'content' => '',
		),
		array(
			'base'    => $upload_dir['basedir'] . '/bb-platform-previews',
			'file'    => 'index.html',
			'content' => '',
		),
		array(
			'base'    => $upload_dir['basedir'] . '/bb_medias',
			'file'    => '.htaccess',
			'content' => '# Apache 2.2
<IfModule !mod_authz_core.c>
	Order Deny,Allow
	Deny from all
</IfModule>

# Apache 2.4
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>
# BEGIN BuddyBoss code execution protection
<IfModule mod_rewrite.c>
RewriteRule ^.*$ - [F,L,NC]
</IfModule>
<IfModule mod_php5.c>
php_flag engine 0
</IfModule>
<IfModule mod_php7.c>
php_flag engine 0
</IfModule>
AddHandler cgi-script .php .phtml .php3 .pl .py .jsp .asp .htm .shtml .sh .cgi
Options -ExecCGI
# END BuddyBoss code execution protection',
		),
	);

	foreach ( $files as $file ) {
		if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
			$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'wb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen
			if ( $file_handle ) {
				fwrite( $file_handle, $file['content'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
				fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			}
		}
	}
}
add_action( 'bp_init', 'bp_media_check_download_album_protection', 9999 );

/**
 * Filter attachments query posts join sql.
 * Filters all photos/documents.
 *
 * @param string $join     Join statement query.
 * @param object $wp_query WP_Query object.
 *
 * @return mixed|string
 * @since BuddyBoss 1.7.0
 */
function bp_media_filter_attachments_query_posts_join( $join, $wp_query ) {
	global $wpdb;
	if ( 'attachment' === $wp_query->query_vars['post_type'] ) {
		$join .= " LEFT JOIN {$wpdb->postmeta} AS bb_mt1 ON ({$wpdb->posts}.ID = bb_mt1.post_id AND bb_mt1.meta_key = 'bp_media_upload' )  LEFT JOIN {$wpdb->postmeta} AS bb_mt2 ON ({$wpdb->posts}.ID = bb_mt2.post_id AND bb_mt2.meta_key = 'bp_document_upload' )  LEFT JOIN {$wpdb->postmeta} AS bb_mt3 ON ({$wpdb->posts}.ID = bb_mt3.post_id AND bb_mt3.meta_key = 'bp_video_upload' )";
	}

	return $join;
}

/**
 * Filter attachments query posts where sql.
 * Filters all photos/documents.
 *
 * @param string $where    Where statement query.
 * @param object $wp_query WP_Query object.
 *
 * @return mixed|string
 * @since BuddyBoss 1.7.0
 */
function bp_media_filter_attachments_query_posts_where( $where, $wp_query ) {
	global $wpdb;
	if ( 'attachment' === $wp_query->query_vars['post_type'] ) {
		$where .= " AND ( ( bb_mt1.post_id IS NULL AND bb_mt2.post_id IS NULL AND bb_mt3.post_id IS NULL ) OR ( {$wpdb->posts}.post_parent != 0 ) )";
	}

	return $where;
}

/**
 * Added activity entry class for Video.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param string $class class.
 *
 * @return string
 */
function bp_video_activity_entry_css_class( $class ) {

	if ( bp_is_active( 'video' ) && bp_is_active( 'activity' ) ) {

		$video_ids = bp_activity_get_meta( bp_get_activity_id(), 'bp_video_ids', true );
		if ( ! empty( $video_ids ) ) {
			$class .= ' video-activity-wrap';
		}
	}

	return $class;

}

/**
 * Add rewrite rule to setup media preview.
 *
 * @since BuddyBoss 1.7.2
 */
function bb_setup_media_preview() {
	add_rewrite_rule( 'bb-media-preview/([^/]+)/([^/]+)/?$', 'index.php?bb-media-preview=$matches[1]&id1=$matches[2]', 'top' );
	add_rewrite_rule( 'bb-media-preview/([^/]+)/([^/]+)/([^/]+)/?$', 'index.php?bb-media-preview=$matches[1]&id1=$matches[2]&size=$matches[3]', 'top' );
	add_rewrite_rule( 'bb-media-preview/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$', 'index.php?bb-media-preview=$matches[1]&id1=$matches[2]&size=$matches[3]&receiver=$matches[4]', 'top' );
}

/**
 * Setup query variable for media preview.
 *
 * @param array $query_vars Array of query variables.
 *
 * @return array
 *
 * @since BuddyBoss 1.7.2
 */
function bb_setup_query_media_preview( $query_vars ) {
	$query_vars[] = 'bb-media-preview';
	$query_vars[] = 'id1';
	$query_vars[] = 'size';
	$query_vars[] = 'receiver';

	return $query_vars;
}

/**
 * Setup template for the media preview.
 *
 * @since BuddyBoss 1.7.2
 *
 * @param string $template Template path to include.
 *
 * @return string
 */
function bb_setup_template_for_media_preview( $template ) {
	if ( get_query_var( 'bb-media-preview' ) === false || empty( get_query_var( 'bb-media-preview' ) ) ) {
		return $template;
	}

	/**
	 * Hooks to perform any action before the template load.
	 *
	 * @since BuddyBoss 1.7.2
	 */
	do_action( 'bb_setup_template_for_media_preview' );

	return trailingslashit( buddypress()->plugin_dir ) . 'bp-templates/bp-nouveau/includes/media/preview.php';
}

/**
 * Embed gif in single activity content.
 *
 * @since BuddyBoss 2.0.2
 *
 * @param string $content  content.
 * @param object $activity Activity object.
 *
 * @return string
 */
function bp_media_activity_append_gif( $content, $activity ) {

	// check if profile and groups activity gif support enabled.
	if ( ( buddypress()->activity->id === $activity->component && ! bp_is_profiles_gif_support_enabled() ) || ( bp_is_active( 'groups' ) && buddypress()->groups->id === $activity->component && ! bp_is_groups_gif_support_enabled() ) ) {
		return $content;
	}

	return $content . bp_media_activity_embed_gif_content( $activity->id );
}


/**
 * Add rewrite rule to setup attachment media preview.
 *
 * @since BuddyBoss 2.0.4
 */
function bb_setup_attachment_media_preview() {
	add_rewrite_rule( 'bb-attachment-media-preview/([^/]+)/?$', 'index.php?media-attachment-id=$matches[1]', 'top' );
	add_rewrite_rule( 'bb-attachment-media-preview/([^/]+)/([^/]+)/?$', 'index.php?media-attachment-id=$matches[1]&size=$matches[2]', 'top' );
	add_rewrite_rule( 'bb-attachment-media-preview/([^/]+)/([^/]+)/([^/]+)/?$', 'index.php?media-attachment-id=$matches[1]&size=$matches[2]&media-thread-id=$matches[3]', 'top' );
}

/**
 * Setup query variable for attachment media preview.
 *
 * @since BuddyBoss 2.0.4
 *
 * @param array $query_vars Array of query variables.
 *
 * @return array
 */
function bb_setup_attachment_media_preview_query( $query_vars ) {
	$query_vars[] = 'media-attachment-id';
	$query_vars[] = 'size';

	if ( bp_is_active( 'messages' ) ) {
		$query_vars[] = 'media-thread-id';
	}

	return $query_vars;
}

/**
 * Setup template for the attachment media preview.
 *
 * @since BuddyBoss 2.0.4
 *
 * @param string $template Template path to include.
 *
 * @return string
 */
function bb_setup_attachment_media_preview_template( $template ) {

	if ( ! empty( get_query_var( 'media-attachment-id' ) ) ) {
		/**
		 * Hooks to perform any action before the template load.
		 *
		 * @since BuddyBoss 2.0.4
		 */
		do_action( 'bb_setup_attachment_media_preview_template' );

		return trailingslashit( buddypress()->plugin_dir ) . 'bp-templates/bp-nouveau/includes/media/attachment.php';
	}

	return $template;
}


/**
 * Enable media preview without trailing slash.
 *
 * @since BuddyBoss 2.3.2
 *
 * @param string $redirect_url URL to render.
 *
 * @return mixed|string
 */
function bb_media_remove_specific_trailing_slash( $redirect_url ) {
	if (
		strpos( $redirect_url, 'bb-attachment-media-preview' ) !== false ||
		strpos( $redirect_url, 'bb-media-preview' ) !== false
	) {
		$redirect_url = untrailingslashit( $redirect_url );
	}
	return $redirect_url;
}
add_filter( 'redirect_canonical', 'bb_media_remove_specific_trailing_slash', 9999 );

/**
 * Put photo attachment as media.
 *
 * @since BuddyBoss 2.3.60
 *
 * @param WP_Post $attachment Attachment Post object.
 *
 * @return mixed
 */
function bb_messages_media_save( $attachment ) {
	$thread_id            = ! empty( $_POST['thread_id'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['thread_id'] ) ) : 0;
	$group_id             = ! empty( $_POST['group_id'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['group_id'] ) ) : 0;
	$component            = ! empty( $_POST['component'] ) ? sanitize_text_field( wp_unslash( $_POST['component'] ) ) : '';
	$is_message_component = ( bp_is_group_messages() || bp_is_messages_component() || ( ! empty( $component ) && 'messages' === $component ) );

	if ( empty( $group_id ) && bp_is_group_messages() ) {
		$group = groups_get_current_group();
		if ( ! empty( $group ) ) {
			$group_id = $group->id;
		}
	}

	if (
		$is_message_component &&
		bb_user_has_access_upload_media( $group_id, bp_loggedin_user_id(), 0, $thread_id, 'message' ) &&
		! empty( $attachment )
	) {

		$medias[] = array(
			'id'      => $attachment->ID,
			'name'    => $attachment->post_title,
			'privacy' => 'message',
		);

		remove_action( 'bp_media_add', 'bp_activity_media_add', 9 );
		remove_filter( 'bp_media_add_handler', 'bp_activity_create_parent_media_activity', 9 );

		$media_ids = bp_media_add_handler( $medias, 'message' );

		if ( ! is_wp_error( $media_ids ) ) {
			// Message not actually sent.
			update_post_meta( $attachment->ID, 'bp_media_saved', 0 );

			$thread_id = 0;
			if ( ! empty( $_POST['thread_id'] ) ) {
				$thread_id = absint( $_POST['thread_id'] );
			}

			// Message not actually sent.
			update_post_meta( $attachment->ID, 'thread_id', $thread_id );
		}

		add_action( 'bp_media_add', 'bp_activity_media_add', 9 );
		add_filter( 'bp_media_add_handler', 'bp_activity_create_parent_media_activity', 9 );

		return $media_ids;

	}

	return false;
}

add_action( 'bb_media_upload', 'bb_messages_media_save' );
