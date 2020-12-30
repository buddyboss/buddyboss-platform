<?php

/**
 * Exit if accessed directly.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'bp_video_album_after_save', 'bp_video_update_video_privacy' );
add_action( 'delete_attachment', 'bp_video_delete_attachment_video', 0 );

// Activity.
add_action( 'bp_after_directory_activity_list', 'bp_video_add_theatre_template' );
add_action( 'bp_after_single_activity_content', 'bp_video_add_theatre_template' );
add_action( 'bp_after_member_activity_content', 'bp_video_add_theatre_template' );
add_action( 'bp_after_group_activity_content', 'bp_video_add_theatre_template' );
add_action( 'bp_after_single_activity', 'bp_video_add_theatre_template' );
add_action( 'bp_activity_entry_content', 'bp_video_activity_entry' );
add_action( 'bp_activity_after_comment_content', 'bp_video_activity_comment_entry' );
add_action( 'bp_activity_posted_update', 'bp_video_update_activity_video_meta', 10, 3 );
add_action( 'bp_groups_posted_update', 'bp_video_groups_activity_update_video_meta', 10, 4 );
add_action( 'bp_activity_comment_posted', 'bp_video_activity_comments_update_video_meta', 10, 3 );
add_action( 'bp_activity_comment_posted_notification_skipped', 'bp_video_activity_comments_update_video_meta', 10, 3 );
add_action( 'bp_activity_after_delete', 'bp_video_delete_activity_video' ); // Delete activity videos.
add_action( 'bp_activity_after_save', 'bp_video_activity_update_video_privacy', 2 );
add_filter( 'bp_activity_get_edit_data', 'bp_video_get_edit_activity_data' );

// Forums.
add_action( 'bbp_template_after_single_topic', 'bp_video_add_theatre_template' );
add_action( 'bbp_new_reply', 'bp_video_forums_new_post_video_save', 999 );
add_action( 'bbp_new_topic', 'bp_video_forums_new_post_video_save', 999 );
add_action( 'edit_post', 'bp_video_forums_new_post_video_save', 999 );

add_filter( 'bbp_get_reply_content', 'bp_video_forums_embed_attachments', 98, 2 );
add_filter( 'bbp_get_topic_content', 'bp_video_forums_embed_attachments', 98, 2 );

// Messages..
add_action( 'messages_message_sent', 'bp_video_attach_video_to_message' );
add_action( 'bp_messages_thread_after_delete', 'bp_video_messages_delete_attached_video', 10, 2 ); // Delete thread videos.
add_action( 'bp_messages_thread_messages_after_update', 'bp_video_user_messages_delete_attached_video', 10, 4 ); // Delete messages videos.
add_filter( 'bp_messages_message_validated_content', 'bp_video_message_validated_content', 10, 3 );

// Core tools.
add_filter( 'bp_repair_list', 'bp_video_add_admin_repair_items' );

// Download Video.
add_action( 'init', 'bp_video_download_url_file' );

add_action( 'bp_activity_after_email_content', 'bp_video_activity_after_email_content' );

add_image_size( 'bp-video-thumbnail', 400, 400 );
add_image_size( 'bp-activity-video-thumbnail', 1600, 1600 );

/**
 * Add video theatre template for activity pages
 */
function bp_video_add_theatre_template() {
	bp_get_template_part( 'video/theatre' );
}

/**
 * Get activity entry video to render on front end
 *
 * @BuddyBoss 1.0.0
 */
function bp_video_activity_entry() {
	global $video_template;

	if ( ( buddypress()->activity->id === bp_get_activity_object_name() && ! bp_is_profile_video_support_enabled() ) || ( bp_is_active( 'groups' ) && buddypress()->groups->id === bp_get_activity_object_name() && ! bp_is_group_video_support_enabled() ) ) {
		return false;
	}

	$video_ids = bp_activity_get_meta( bp_get_activity_id(), 'bp_video_ids', true );

	// Add Video to single activity page..
	$video_activity = bp_activity_get_meta( bp_get_activity_id(), 'bp_video_activity', true );
	if ( bp_is_single_activity() && ! empty( $video_activity ) && '1' == $video_activity && empty( $video_ids ) ) {
		$video_ids = BP_Video::get_activity_video_id( bp_get_activity_id() );
	} else {
		$video_ids = bp_activity_get_meta( bp_get_activity_id(), 'bp_video_ids', true );
	}

	if ( empty( $video_ids ) ) {
		return;
	}

	$args = array(
		'include'  => $video_ids,
		'order_by' => 'menu_order',
		'sort'     => 'ASC',
		'user_id'  => false,
	);

	if ( bp_is_active( 'groups' ) && buddypress()->groups->id === bp_get_activity_object_name() ) {
		if ( bp_is_group_video_support_enabled() ) {
			$args['privacy'] = array( 'grouponly' );
			if ( ! bp_is_group_albums_support_enabled() ) {
				$args['album_id'] = 'existing-video';
			}
		} else {
			$args['privacy']  = array( '0' );
			$args['album_id'] = 'existing-video';
		}
	} else {
		$args['privacy'] = bp_video_query_privacy( bp_get_activity_user_id(), 0, bp_get_activity_object_name() );
		if ( ! bp_is_profile_video_support_enabled() ) {
			$args['user_id'] = 'null';
		}
		if ( ! bp_is_profile_albums_support_enabled() ) {
			$args['album_id'] = 'existing-video';
		}
	}

	$is_forum_activity = false;
	if (
		bp_is_active( 'forums' )
		&& in_array( bp_get_activity_type(), array( 'bbp_forum_create', 'bbp_topic_create', 'bbp_reply_create' ), true )
		&& bp_is_forums_video_support_enabled()
	) {
		$is_forum_activity = true;
		$args['privacy'][] = 'forums';
	}

	if ( ! empty( $video_ids ) && bp_has_video( $args ) ) { ?>
		<div class="bb-activity-video-wrap
		<?php
		echo esc_attr( 'bb-video-length-' . $video_template->video_count );
			echo $video_template->video_count > 5 ? esc_attr( ' bb-video-length-more' ) : '';
			echo true === $is_forum_activity ? esc_attr( ' forums-video-wrap' ) : '';
		?>
			">
			<?php
			bp_get_template_part( 'video/add-video-thumbnail' );
			bp_get_template_part( 'video/video-move' );
			while ( bp_video() ) {
				bp_the_video();
				bp_get_template_part( 'video/activity-entry' );
			}
			?>
		</div>
		<?php
	}
}

/**
 * Append the video content to activity read more content
 *
 * @BuddyBoss 1.1.3
 *
 * @param $content
 * @param $activity
 *
 * @return string
 */
function bp_video_activity_append_video( $content, $activity ) {
	global $video_template;

	if ( ( buddypress()->activity->id === $activity->component && ! bp_is_profile_video_support_enabled() ) || ( bp_is_active( 'groups' ) && buddypress()->groups->id === $activity->component && ! bp_is_group_video_support_enabled() ) ) {
		return $content;
	}

	$video_ids = bp_activity_get_meta( $activity->id, 'bp_video_ids', true );

	if ( ! empty( $video_ids ) ) {

		$args = array(
			'include'  => $video_ids,
			'order_by' => 'menu_order',
			'sort'     => 'ASC',
		);

		if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $activity->component ) {
			if ( bp_is_group_video_support_enabled() ) {
				$args['privacy'] = array( 'grouponly' );
				if ( ! bp_is_group_albums_support_enabled() ) {
					$args['album_id'] = 'existing-video';
				}
			} else {
				$args['privacy']  = array( '0' );
				$args['album_id'] = 'existing-video';
			}
		} else {
			$args['privacy'] = bp_video_query_privacy( $activity->user_id, 0, $activity->component );
			if ( ! bp_is_profile_video_support_enabled() ) {
				$args['user_id'] = 'null';
			}
			if ( ! bp_is_profile_albums_support_enabled() ) {
				$args['album_id'] = 'existing-video';
			}
		}

		$is_forum_activity = false;
		if (
			bp_is_active( 'forums' )
			&& in_array( $activity->type, array( 'bbp_forum_create', 'bbp_topic_create', 'bbp_reply_create' ), true )
			&& bp_is_forums_video_support_enabled()
		) {
			$is_forum_activity = true;
			$args['privacy'][] = 'forums';
		}

		if ( bp_has_video( $args ) ) {
			?>
			<?php ob_start(); ?>
			<div class="bb-activity-video-wrap
			<?php
			echo 'bb-video-length-' . $video_template->video_count;
				echo $video_template->video_count > 5 ? ' bb-video-length-more' : '';
				echo true === $is_forum_activity ? ' forums-video-wrap' : '';
			?>
				">
				<?php
				bp_get_template_part( 'video/video-move' );
				while ( bp_video() ) {
					bp_the_video();
					bp_get_template_part( 'video/activity-entry' );
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
 * Get activity comment entry video to render on front end
 */
function bp_video_activity_comment_entry( $comment_id ) {
	global $video_template;

	$video_ids = bp_activity_get_meta( $comment_id, 'bp_video_ids', true );

	if ( empty( $video_ids ) ) {
		return;
	}

	$comment  = new BP_Activity_Activity( $comment_id );
	$activity = new BP_Activity_Activity( $comment->item_id );

	$args = array(
		'include'  => $video_ids,
		'order_by' => 'menu_order',
		'sort'     => 'ASC',
		'user_id'  => false,
	);

	if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $activity->component ) {
		if ( bp_is_group_video_support_enabled() ) {
			$args['privacy'] = array( 'grouponly' );
			if ( ! bp_is_group_albums_support_enabled() ) {
				$args['album_id'] = 'existing-video';
			}
		} else {
			$args['privacy']  = array( '0' );
			$args['album_id'] = 'existing-video';
		}
	} else {
		$args['privacy'] = bp_video_query_privacy( $activity->user_id, 0, $activity->component );
		if ( ! bp_is_profile_video_support_enabled() ) {
			$args['user_id'] = 'null';
		}
		if ( ! bp_is_profile_albums_support_enabled() ) {
			$args['album_id'] = 'existing-video';
		}
	}

	$is_forum_activity = false;
	if (
		bp_is_active( 'forums' )
		&& in_array( $activity->type, array( 'bbp_forum_create', 'bbp_topic_create', 'bbp_reply_create' ), true )
		&& bp_is_forums_video_support_enabled()
	) {
		$is_forum_activity = true;
		$args['privacy'][] = 'forums';
	}

	if ( ! empty( $video_ids ) && bp_has_video( $args ) ) {
		?>
		<div class="bb-activity-video-wrap
		<?php
		echo esc_attr( 'bb-video-length-' . $video_template->video_count );
		echo $video_template->video_count > 5 ? esc_attr( ' bb-video-length-more' ) : '';
		?>
		">
				<?php
				bp_get_template_part( 'video/video-move' );
				while ( bp_video() ) {
					bp_the_video();
					bp_get_template_part( 'video/activity-entry' );
				}
				?>
			</div>
			<?php
	}
}

/**
 * Update video for activity
 *
 * @param $content
 * @param $user_id
 * @param $activity_id
 *
 * @since BuddyBoss 1.6.0
 *
 * @return bool
 */
function bp_video_update_activity_video_meta( $content, $user_id, $activity_id ) {
	global $bp_activity_post_update, $bp_activity_post_update_id, $bp_activity_edit;

	$post_video   = filter_input( INPUT_POST, 'video', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	$post_edit    = filter_input( INPUT_POST, 'edit', FILTER_SANITIZE_STRING );
	$post_action  = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );
	$post_privacy = filter_input( INPUT_POST, 'privacy', FILTER_SANITIZE_STRING );

	if ( ! isset( $post_video ) || empty( $post_video ) ) {

		// delete video ids and meta for activity if empty video in request.
		if ( ! empty( $activity_id ) && $bp_activity_edit && isset( $post_edit ) ) {
			$old_video_ids = bp_activity_get_meta( $activity_id, 'bp_video_ids', true );

			if ( ! empty( $old_video_ids ) ) {
				// Delete video if not exists anymore in activity.
				$old_video_ids = explode( ',', $old_video_ids );
				if ( ! empty( $old_video_ids ) ) {
					foreach ( $old_video_ids as $video_id ) {
						bp_video_delete( array( 'id' => $video_id ), 'activity' );
					}
				}
				bp_activity_delete_meta( $activity_id, 'bp_video_ids' );
			}
		}
		return false;
	}

	$bp_activity_post_update    = true;
	$bp_activity_post_update_id = $activity_id;

	// Update activity comment attached document privacy with parent one.
	if ( ! empty( $activity_id ) && isset( $post_action ) && 'new_activity_comment' === $post_action ) {
		$parent_activity = new BP_Activity_Activity( $activity_id );
		if ( 'groups' === $parent_activity->component ) {
			$_POST['privacy'] = 'grouponly';
		} elseif ( ! empty( $parent_activity->privacy ) ) {
			$_POST['privacy'] = $parent_activity->privacy;
		}
	}

	remove_action( 'bp_activity_posted_update', 'bp_video_update_activity_video_meta', 10, 3 );
	remove_action( 'bp_groups_posted_update', 'bp_video_groups_activity_update_video_meta', 10, 4 );
	remove_action( 'bp_activity_comment_posted', 'bp_video_activity_comments_update_video_meta', 10, 3 );
	remove_action( 'bp_activity_comment_posted_notification_skipped', 'bp_video_activity_comments_update_video_meta', 10, 3 );

	$video_ids = bp_video_add_handler( $post_video, $post_privacy );

	add_action( 'bp_activity_posted_update', 'bp_video_update_activity_video_meta', 10, 3 );
	add_action( 'bp_groups_posted_update', 'bp_video_groups_activity_update_video_meta', 10, 4 );
	add_action( 'bp_activity_comment_posted', 'bp_video_activity_comments_update_video_meta', 10, 3 );
	add_action( 'bp_activity_comment_posted_notification_skipped', 'bp_video_activity_comments_update_video_meta', 10, 3 );

	// save video meta for activity.
	if ( ! empty( $activity_id ) ) {

		// Delete video if not exists in current video ids.
		if ( isset( $post_edit ) ) {
			$old_video_ids = bp_activity_get_meta( $activity_id, 'bp_video_ids', true );
			$old_video_ids = array_filter( explode( ',', $old_video_ids ) );

			if ( ! empty( $old_video_ids ) ) {

				// This is hack to update/delete parent activity if new video added in edit.
				bp_activity_update_meta( $activity_id, 'bp_video_ids', implode( ',', array_unique( array_merge( $video_ids, $old_video_ids ) ) ) );

				foreach ( $old_video_ids as $video_id ) {

					if ( ! in_array( $video_id, $video_ids, true ) ) {
						bp_video_delete( array( 'id' => $video_id ) );
					}
				}
			}
		}

		// update new video ids here in the activity meta.
		bp_activity_update_meta( $activity_id, 'bp_video_ids', implode( ',', $video_ids ) );
	}
}

/**
 * Update video for group activity
 *
 * @param $content
 * @param $user_id
 * @param $group_id
 * @param $activity_id
 *
 * @since BuddyBoss 1.6.0
 *
 * @return bool
 */
function bp_video_groups_activity_update_video_meta( $content, $user_id, $group_id, $activity_id ) {
	bp_video_update_activity_video_meta( $content, $user_id, $activity_id );
}

/**
 * Update video for activity comment
 *
 * @param $comment_id
 * @param $r
 * @param $activity
 *
 * @since BuddyBoss 1.6.0
 *
 * @return bool
 */
function bp_video_activity_comments_update_video_meta( $comment_id, $r, $activity ) {
	global $bp_new_activity_comment;
	$bp_new_activity_comment = $comment_id;
	bp_video_update_activity_video_meta( false, false, $comment_id );
}

/**
 * Delete video when related activity is deleted.
 *
 * @since BuddyBoss 1.6.0
 * @param $activities
 */
function bp_video_delete_activity_video( $activities ) {
	if ( ! empty( $activities ) ) {
		remove_action( 'bp_activity_after_delete', 'bp_video_delete_activity_video' );
		foreach ( $activities as $activity ) {
			$activity_id    = $activity->id;
			$video_activity = bp_activity_get_meta( $activity_id, 'bp_video_activity', true );
			if ( ! empty( $video_activity ) && '1' === $video_activity ) {
				bp_video_delete( array( 'activity_id' => $activity_id ) );
			}

			// get video ids attached to activity.
			$video_ids = bp_activity_get_meta( $activity_id, 'bp_video_ids', true );
			if ( ! empty( $video_ids ) ) {
				$video_ids = explode( ',', $video_ids );
				foreach ( $video_ids as $video_id ) {
					bp_video_delete( array( 'id' => $video_id ) );
				}
			}
		}
		add_action( 'bp_activity_after_delete', 'bp_video_delete_activity_video' );
	}
}

/**
 * Update video privacy according to album's privacy
 *
 * @since BuddyBoss 1.6.0
 * @param $album
 */
function bp_video_update_video_privacy( $album ) {

	if ( ! empty( $album->id ) ) {

		$privacy      = $album->privacy;
		$video_ids    = BP_Video::get_album_video_ids( $album->id );
		$activity_ids = array();

		if ( ! empty( $video_ids ) ) {
			foreach ( $video_ids as $video ) {
				$video_obj          = new BP_Video( $video );
				$video_obj->privacy = $privacy;
				$video_obj->save();

				$attachment_id    = $video_obj->attachment_id;
				$main_activity_id = get_post_meta( $attachment_id, 'bp_video_parent_activity_id', true );

				if ( ! empty( $main_activity_id ) ) {
					$activity_ids[] = $main_activity_id;
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
 * Save video when new topic or reply is saved
 *
 * @since BuddyBoss 1.6.0
 * @param $post_id
 */
function bp_video_forums_new_post_video_save( $post_id ) {

	if ( bp_is_forums_video_support_enabled() && ! empty( $_POST['bbp_video'] ) ) {

		// save activity id if it is saved in forums and enabled in platform settings.
		$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

		// save video.
		$videos = json_decode( stripslashes( $_POST['bbp_video'] ), true );

		// fetch currently uploaded video ids.
		$existing_video                = array();
		$existing_video_ids            = get_post_meta( $post_id, 'bp_video_ids', true );
		$existing_video_attachment_ids = array();
		if ( ! empty( $existing_video_ids ) ) {
			$existing_video_ids = explode( ',', $existing_video_ids );

			foreach ( $existing_video_ids as $existing_video_id ) {
				$existing_video[ $existing_video_id ] = new BP_Video( $existing_video_id );

				if ( ! empty( $existing_video[ $existing_video_id ]->attachment_id ) ) {
					$existing_video_attachment_ids[] = $existing_video[ $existing_video_id ]->attachment_id;
				}
			}
		}

		$video_ids = array();
		foreach ( $videos as $video ) {

			$title             = ! empty( $video['name'] ) ? $video['name'] : '';
			$attachment_id     = ! empty( $video['id'] ) ? $video['id'] : 0;
			$attached_video_id = ! empty( $video['video_id'] ) ? $video['video_id'] : 0;
			$album_id          = ! empty( $video['album_id'] ) ? $video['album_id'] : 0;
			$group_id          = ! empty( $video['group_id'] ) ? $video['group_id'] : 0;
			$menu_order        = ! empty( $video['menu_order'] ) ? $video['menu_order'] : 0;

			if ( ! empty( $existing_video_attachment_ids ) ) {
				$index = array_search( $attachment_id, $existing_video_attachment_ids );
				if ( ! empty( $attachment_id ) && $index !== false && ! empty( $existing_video[ $attached_video_id ] ) ) {

					$existing_video[ $attached_video_id ]->menu_order = $menu_order;
					$existing_video[ $attached_video_id ]->save();

					unset( $existing_video_ids[ $index ] );
					$video_ids[] = $attached_video_id;
					continue;
				}
			}

			$video_id = bp_video_add(
				array(
					'attachment_id' => $attachment_id,
					'title'         => $title,
					'album_id'      => $album_id,
					'group_id'      => $group_id,
					'privacy'       => 'forums',
					'error_type'    => 'wp_error',
				)
			);

			if ( ! is_wp_error( $video_id ) ) {

				if ( class_exists( 'FFMpeg\FFMpeg' ) ) {

					$thumbnails   = get_post_meta( $attachment_id, 'video_preview_thumbnails', true );
					$thumbnail_id = get_post_meta( $attachment_id, 'bp_video_preview_thumbnail_id', true );

					$video_arr = array(
						'id' => $attachment_id,
					);
					if ( ! $thumbnails && ! $thumbnail_id ) {
						global $bp_background_updater;

						$bp_background_updater->push_to_queue(
							array(
								'callback' => 'bp_video_background_create_thumbnail',
								'args'     => array( $video_id, $video_arr ),
							)
						);

						$bp_background_updater->save()->schedule_event();

					}
				}

				$video_ids[] = $video_id;

				// save video is saved in attachment.
				update_post_meta( $attachment_id, 'bp_video_saved', true );
			}
		}

		$video_ids = implode( ',', $video_ids );

		// Save all attachment ids in forums post meta.
		update_post_meta( $post_id, 'bp_video_ids', $video_ids );

		// save video meta for activity.
		if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
			bp_activity_update_meta( $main_activity_id, 'bp_video_ids', $video_ids );
		}

		// delete videos which were not saved or removed from form.
		if ( ! empty( $existing_video_ids ) ) {
			foreach ( $existing_video_ids as $video_id ) {
				bp_video_delete( array( 'id' => $video_id ) );
			}
		}
	}
}

/**
 * Embed topic or reply attachments in a post
 *
 * @since BuddyBoss 1.6.0
 * @param $content
 * @param $id
 *
 * @return string
 */
function bp_video_forums_embed_attachments( $content, $id ) {
	global $video_template;

	// Do not embed attachment in wp-admin area.
	if ( is_admin() || ! bp_is_forums_video_support_enabled() ) {
		return $content;
	}

	$video_ids = get_post_meta( $id, 'bp_video_ids', true );

	if ( ! empty( $video_ids ) && bp_has_video(
		array(
			'include'  => $video_ids,
			'order_by' => 'menu_order',
			'privacy'  => array( 'forums' ),
			'sort'     => 'ASC',
		)
	) ) {
			ob_start();
		?>
			<div class="bb-activity-media-wrap forums-video-wrap
		<?php
			echo 'bb-media-length-' . $video_template->video_count;
			echo $video_template->video_count > 5 ? ' bb-media-length-more' : '';
		?>
		">
				<?php
				bp_get_template_part( 'video/add-video-thumbnail' );
				while ( bp_video() ) {
					bp_the_video();
					bp_get_template_part( 'video/activity-entry' );
				}
				?>
			</div>
			<?php
			$content .= ob_get_clean();
	}

	return $content;
}

/**
 * save gif data for forum, topic, reply
 *
 * @since BuddyBoss 1.6.0
 * @param $post_id
 */
function bp_video_forums_save_gif_data( $post_id ) {

	// check if forums gif support enabled.
	if ( ! bp_is_forums_gif_support_enabled() ) {
		return;
	}

	if ( ! empty( $_POST['bbp_video_gif'] ) ) {

		// save activity id if it is saved in forums and enabled in platform settings.
		$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

		// save gif data.
		$gif_data = json_decode( stripslashes( $_POST['bbp_video_gif'] ), true );

		if ( ! empty( $gif_data['saved'] ) && $gif_data['saved'] ) {
			return;
		}

		$still = bp_video_sideload_attachment( $gif_data['images']['480w_still']['url'] );
		$mp4   = bp_video_sideload_attachment( $gif_data['images']['original_mp4']['mp4'] );

		$gdata = array(
			'still' => $still,
			'mp4'   => $mp4,
		);

		update_post_meta( $post_id, '_gif_data', $gdata );

		$gif_data['saved'] = true;

		update_post_meta( $post_id, '_gif_raw_data', $gif_data );

		// save video meta for forum.
		if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
			bp_activity_update_meta( $main_activity_id, '_gif_data', $gdata );
			bp_activity_update_meta( $main_activity_id, '_gif_raw_data', $gif_data );
		}
	} else {
		delete_post_meta( $post_id, '_gif_data' );
		delete_post_meta( $post_id, '_gif_raw_data' );
	}
}

/**
 * Attach video to the message object
 *
 * @since BuddyBoss 1.6.0
 * @param $message
 */
function bp_video_attach_video_to_message( &$message ) {

	if ( bp_is_messages_video_support_enabled() && ! empty( $message->id ) && ! empty( $_POST['video'] ) ) {
		remove_action( 'bp_video_add', 'bp_activity_video_add', 9 );
		remove_filter( 'bp_video_add_handler', 'bp_activity_create_parent_video_activity', 9 );

		$video_ids = bp_video_add_handler( $_POST['video'], 'message' );

		add_action( 'bp_video_add', 'bp_activity_video_add', 9 );
		add_filter( 'bp_video_add_handler', 'bp_activity_create_parent_video_activity', 9 );

		// save video meta for message..
		bp_messages_update_meta( $message->id, 'bp_video_ids', implode( ',', $video_ids ) );
	}
}

/**
 * Delete video attached to messages
 *
 * @since BuddyBoss 1.6.0
 * @param $thread_id
 * @param $message_ids
 */
function bp_video_messages_delete_attached_video( $thread_id, $message_ids ) {

	if ( ! empty( $message_ids ) ) {
		foreach ( $message_ids as $message_id ) {

			// get video ids attached to message.
			$video_ids = bp_messages_get_meta( $message_id, 'bp_video_ids', true );

			if ( ! empty( $video_ids ) ) {
				$video_ids = explode( ',', $video_ids );
				foreach ( $video_ids as $video_id ) {
					bp_video_delete( array( 'id' => $video_id ) );
				}
			}
		}
	}
}

/**
 * Delete video attached to messages
 *
 * @since BuddyBoss 1.6.0
 * @param $thread_id
 * @param $message_ids
 */
function bp_video_user_messages_delete_attached_video( $thread_id, $message_ids, $user_id, $update_message_ids ) {

	if ( ! empty( $update_message_ids ) ) {
		foreach ( $update_message_ids as $message_id ) {

			// get video ids attached to message..
			$video_ids = bp_messages_get_meta( $message_id, 'bp_video_ids', true );

			if ( ! empty( $video_ids ) ) {
				$video_ids = explode( ',', $video_ids );
				foreach ( $video_ids as $video_id ) {
					bp_video_delete( array( 'id' => $video_id ) );
				}
			}
		}
	}
}

/**
 * Validate message if video is not empty.
 *
 * @param bool         $validated_content Boolean from filter.
 * @param string       $content           Message content.
 * @param array|object $post              Request object.
 *
 * @return bool
 *
 * @since BuddyBoss 1.5.1
 */
function bp_video_message_validated_content( $validated_content, $content, $post ) {
	// check if video is enabled in messages or not and empty video in object request or not.
	if ( bp_is_messages_video_support_enabled() && ! empty( $post['video'] ) ) {
		$validated_content = true;
	}
	return $validated_content;
}

/**
 * Return activity gif embed HTML
 *
 * @since BuddyBoss 1.6.0
 *
 * @param $activity_id
 *
 * @return false|string|void
 */
function bp_video_activity_embed_gif_content( $activity_id ) {

	$gif_data = bp_activity_get_meta( $activity_id, '_gif_data', true );

	if ( empty( $gif_data ) ) {
		return;
	}

	$preview_url = wp_get_attachment_url( $gif_data['still'] );
	$video_url   = wp_get_attachment_url( $gif_data['mp4'] );
	$preview_url = $preview_url . '?' . wp_rand() . '=' . wp_rand();
	$video_url   = $video_url . '?' . wp_rand() . '=' . wp_rand();

	ob_start();
	?>
	<div class="activity-attached-gif-container">
		<div class="gif-image-container">
			<div class="gif-player">
				<video preload="auto" playsinline poster="<?php echo $preview_url; ?>" loop muted playsinline>
					<source src="<?php echo $video_url; ?>" type="video/mp4">
				</video>
				<a href="#" class="gif-play-button">
					<span class="bb-icon-play-thin"></span>
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
 * Delete video entries attached to the attachment
 *
 * @since BuddyBoss 1.2.0
 *
 * @param int $attachment_id ID of the attachment being deleted.
 */
function bp_video_delete_attachment_video( $attachment_id ) {
	global $wpdb;

	$bp = buddypress();

	$video = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->video->table_name} WHERE attachment_id = %d", $attachment_id ) );

	if ( ! $video ) {
		return false;
	}

	remove_action( 'delete_attachment', 'bp_video_delete_attachment_video', 0 );

	bp_video_delete( array( 'id' => $video->id ), 'attachment' );

	add_action( 'delete_attachment', 'bp_video_delete_attachment_video', 0 );
}

/**
 * Update video privacy when activity is updated.
 *
 * @since BuddyBoss 1.2.3
 *
 * @param BP_Activity_Activity $activity Activity object.
 */
function bp_video_activity_update_video_privacy( $activity ) {
	$video_ids = bp_activity_get_meta( $activity->id, 'bp_video_ids', true );

	if ( ! empty( $video_ids ) ) {
		$video_ids = explode( ',', $video_ids );

		foreach ( $video_ids as $video_id ) {
			$video = new BP_Video( $video_id );
			// Do not update the privacy if the video is added to forum.
			if ( ! in_array( $video->privacy, array( 'forums', 'message', 'media', 'document', 'grouponly', 'video' ), true ) ) {
				$video->privacy = $activity->privacy;
				$video->save();
			}
		}
	}
}

/**
 * Set up activity arguments for use with the 'video' scope.
 *
 * @since BuddyBoss 1.4.2
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array $retval
 */
function bp_activity_filter_video_scope( $retval = array(), $filter = array() ) {

	$retval = array(
		'relation' => 'AND',
		array(
			'column'  => 'privacy',
			'value'   => 'video',
			'compare' => '=',
		),
		array(
			'column' => 'hide_sitewide',
			'value'  => 1,
		),
	);

	return $retval;
}
add_filter( 'bp_activity_set_video_scope_args', 'bp_activity_filter_video_scope', 10, 2 );

/**
 * Add video repair list item.
 *
 * @param $repair_list
 *
 * @since BuddyBoss 1.4.4
 * @return array Repair list items.
 */
function bp_video_add_admin_repair_items( $repair_list ) {
	if ( bp_is_active( 'activity' ) ) {
		$repair_list[] = array(
			'bp-repair-video',
			__( 'Repair video on the site.', 'buddyboss' ),
			'bp_video_admin_repair_video',
		);
		$repair_list[] = array(
			'bp-video-forum-privacy-repair',
			__( 'Repair forum video privacy', 'buddyboss' ),
			'bp_video_forum_privacy_repair',
		);
	}
	return $repair_list;
}

/**
 * Repair BuddyBoss video.
 *
 * @since BuddyBoss 1.4.4
 */
function bp_video_admin_repair_video() {
	global $wpdb;
	$offset = isset( $_POST['offset'] ) ? (int) ( $_POST['offset'] ) : 0;
	$bp     = buddypress();

	$video_query = "SELECT id, activity_id FROM {$bp->video->table_name} WHERE activity_id != 0 LIMIT 50 OFFSET $offset ";
	$videos      = $wpdb->get_results( $video_query );  // phpcs:ignore

	if ( ! empty( $videos ) ) {
		foreach ( $videos as $video ) {
			if ( ! empty( $video->id ) && ! empty( $video->activity_id ) ) {
				$activity = new BP_Activity_Activity( $video->activity_id );
				if ( ! empty( $activity->id ) ) {
					if ( 'activity_comment' === $activity->type ) {
						$activity = new BP_Activity_Activity( $activity->item_id );
					}
					if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $activity->component ) {
						$update_query = "UPDATE {$bp->video->table_name} SET group_id=" . $activity->item_id . ", privacy='grouponly' WHERE id=" . $video->id . ' ';
						$wpdb->query( $update_query );  // phpcs:ignore
					}
					if ( 'video' === $activity->privacy ) {
						if ( ! empty( $activity->secondary_item_id ) ) {
							$video_activity = new BP_Activity_Activity( $activity->secondary_item_id );
							if ( ! empty( $video_activity->id ) ) {
								if ( 'activity_comment' === $video_activity->type ) {
									$video_activity = new BP_Activity_Activity( $video_activity->item_id );
								}
								if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $video_activity->component ) {
									$update_query = "UPDATE {$bp->video->table_name} SET group_id=" . $video_activity->item_id . ", privacy='grouponly' WHERE id=" . $video->id . ' ';
									$wpdb->query( $update_query );  // phpcs:ignore
									$activity->item_id   = $video_activity->item_id;
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
		$records_updated = sprintf( __( '%s video updated successfully.', 'buddyboss' ), number_format_i18n( $offset ) );  // phpcs:ignore

		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => $records_updated,
		);
	} else {
		return array(
			'status'  => 1,
			'message' => __( 'Video update complete!', 'buddyboss' ),
		);
	}
}

/**
 * Repair BuddyBoss video forums privacy.
 *
 * @since BuddyBoss 1.4.2
 */
function bp_video_forum_privacy_repair() {
	global $wpdb;

	$offset = filter_input( INPUT_POST, 'offset', FILTER_SANITIZE_NUMBER_INT );
	$bp     = buddypress();

	$squery  = "SELECT p.ID as post_id FROM {$wpdb->posts} p, {$wpdb->postmeta} pm WHERE p.ID = pm.post_id and p.post_type in ( 'forum', 'topic', 'reply' ) and pm.meta_key = 'bp_video_ids' and pm.meta_value != '' LIMIT 20 OFFSET $offset ";
	$records = $wpdb->get_col( $squery ); // phpcs:ignore
	if ( ! empty( $records ) ) {
		foreach ( $records as $record ) {
			if ( ! empty( $record ) ) {
				$video_ids = get_post_meta( $record, 'bp_video_ids', true );
				if ( $video_ids ) {
					$update_query = "UPDATE {$bp->video->table_name} SET `privacy`= 'forums' WHERE id in (" . $video_ids . ')';
					$wpdb->query( $update_query ); // phpcs:ignore
				}
			}
			$offset ++;
		}
		$records_updated = sprintf( __( '%s Forums video privacy updated successfully.', 'buddyboss' ), number_format_i18n( $offset ) ); // phpcs:ignore

		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => $records_updated,
		);
	} else {
		$statement = __( 'Forums video privacy updated %s', 'buddyboss' ); // phpcs:ignore

		return array(
			'status'  => 1,
			'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		);
	}
}


/**
 * Set up video arguments for use with the 'public' scope.
 *
 * @since BuddyBoss 1.1.9
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array
 */
function bp_video_filter_public_scope( $retval = array(), $filter = array() ) {

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	$privacy = array( 'public' );
	if ( is_user_logged_in() && bp_is_profile_video_support_enabled() ) {
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

	if ( ! bp_is_profile_video_support_enabled() && ! bp_is_profile_albums_support_enabled() ) {
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
			'column'  => 'title',
			'compare' => 'LIKE',
			'value'   => $filter['search_terms'],
		);
	}

	$retval = array(
		'relation' => 'OR',
		$args,
	);

	return $retval;
}
add_filter( 'bp_video_set_public_scope_args', 'bp_video_filter_public_scope', 10, 2 );

/**
 * Force download - this is the default method.
 *
 * @param string $file_path File path.
 * @param string $filename  File name.
 * @since BuddyBoss 1.4.1
 */
function bp_video_download_file_force( $file_path, $filename ) {
	$parsed_file_path = bp_video_parse_file_path( $file_path );
	$download_range   = bp_video_get_download_range( @filesize( $parsed_file_path['file_path'] ) ); // @codingStandardsIgnoreLine.

	bp_video_download_headers( $parsed_file_path['file_path'], $filename, $download_range );

	$start  = isset( $download_range['start'] ) ? $download_range['start'] : 0;
	$length = isset( $download_range['length'] ) ? $download_range['length'] : 0;
	if ( ! bp_video_readfile_chunked( $parsed_file_path['file_path'], $start, $length ) ) {
		if ( $parsed_file_path['remote_file'] ) {
			bp_video_download_file_redirect( $file_path );
		} else {
			bp_video_download_error( __( 'File not found', 'buddyboss' ) );
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
 * @since BuddyBoss 1.4.1
 */
function bp_video_download_error( $message, $title = '', $status = 404 ) {
	if ( ! strstr( $message, '<a ' ) ) {
		$message .= ' <a href="' . esc_url( site_url() ) . '" class="bp-video-forward">' . esc_html__( 'Go to video', 'buddyboss' ) . '</a>';
	}
	wp_die( $message, $title, array( 'response' => $status ) ); // phpcs:ignore
}

/**
 * Redirect to a file to start the download.
 *
 * @param string $file_path File path.
 * @param string $filename  File name.
 * @since BuddyBoss 1.4.1
 */
function bp_video_download_file_redirect( $file_path, $filename = '' ) {
	header( 'Location: ' . $file_path );
	exit;
}

/**
 * Read file chunked.
 *
 * Reads file in chunks so big downloads are possible without changing PHP.INI - http://codeigniter.com/wiki/Download_helper_for_large_files/.
 *
 * @param  string $file   File.
 * @param  int    $start  Byte offset/position of the beginning from which to read from the file.
 * @param  int    $length Length of the chunk to be read from the file in bytes, 0 means full file.
 * @return bool Success or fail
 * @since BuddyBoss 1.4.1
 */
function bp_video_readfile_chunked( $file, $start = 0, $length = 0 ) {
	if ( ! defined( 'BP_VIDEO_CHUNK_SIZE' ) ) {
		define( 'BP_VIDEO_CHUNK_SIZE', 1024 * 1024 );
	}
	$handle = @fopen( $file, 'r' ); // phpcs:ignore

	if ( false === $handle ) {
		return false;
	}

	if ( ! $length ) {
		$length = @filesize( $file ); // phpcs:ignore
	}

	$read_length = (int) BP_VIDEO_CHUNK_SIZE;

	if ( $length ) {
		$end = $start + $length - 1;

		@fseek( $handle, $start ); // phpcs:ignore
		$p = @ftell( $handle ); // phpcs:ignore

		while ( ! @feof( $handle ) && $p <= $end ) { // phpcs:ignore
			// Don't run past the end of file.
			if ( $p + $read_length > $end ) {
				$read_length = $end - $p + 1;
			}

			echo @fread( $handle, $read_length ); // phpcs:ignore
			$p = @ftell( $handle ); // phpcs:ignore

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

	return @fclose( $handle ); // phpcs:ignore
}

/**
 * Set headers for the download.
 *
 * @param string $file_path      File path.
 * @param string $filename       File name.
 * @param array  $download_range Array containing info about range download request (see {@see get_download_range} for structure).
 * @since BuddyBoss 1.4.1
 */
function bp_video_download_headers( $file_path, $filename, $download_range = array() ) {
	bp_video_check_server_config();
	bp_video_clean_buffers();
	bp_video_nocache_headers();

	header( 'X-Robots-Tag: noindex, nofollow', true );
	header( 'Content-Type: ' . bp_video_get_download_content_type( $file_path ) );
	header( 'Content-Description: File Transfer' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
	header( 'Content-Transfer-Encoding: binary' );

	$file_size = @filesize( $file_path ); // phpcs:ignore
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
 * @param int $limit Time limit.
 */
function bp_video_set_time_limit( $limit = 0 ) {
	if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) { // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
		@set_time_limit( $limit ); // @codingStandardsIgnoreLine
	}
}

/**
 * Check and set certain server config variables to ensure downloads work as intended.
 *
 * @since BuddyBoss 1.4.1
 */
function bp_video_check_server_config() {
	bp_video_set_time_limit( 0 );
	if ( function_exists( 'apache_setenv' ) ) {
		@apache_setenv( 'no-gzip', 1 ); // phpcs:ignore
	}
	@ini_set( 'zlib.output_compression', 'Off' ); // phpcs:ignore
	@session_write_close(); // phpcs:ignore
}

/**
 * Clean all output buffers.
 *
 * Can prevent errors, for example: transfer closed with 3 bytes remaining to read.
 *
 * @since BuddyBoss 1.4.1
 */
function bp_video_clean_buffers() {
	if ( ob_get_level() ) {
		$levels = ob_get_level();
		for ( $i = 0; $i < $levels; $i++ ) {
			@ob_end_clean(); // phpcs:ignore
		}
	} else {
		@ob_end_clean(); // phpcs:ignore
	}
}

/**
 * Set constants to prevent caching by some plugins.
 *
 * @param  mixed $return Value to return. Previously hooked into a filter.
 * @return mixed
 * @since BuddyBoss 1.4.1
 */
function bp_video_set_nocache_constants( $return = true ) {
	bp_video_maybe_define_constant( 'DONOTCACHEPAGE', true );
	bp_video_maybe_define_constant( 'DONOTCACHEOBJECT', true );
	bp_video_maybe_define_constant( 'DONOTCACHEDB', true );
	return $return;
}

/**
 * Define a constant if it is not already defined.
 *
 * @since BuddyBoss 1.4.1
 * @param string $name  Constant name.
 * @param mixed  $value Value.
 */
function bp_video_maybe_define_constant( $name, $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}

/**
 * Wrapper for nocache_headers which also disables page caching.
 *
 * @since BuddyBoss 1.4.1
 */
function bp_video_nocache_headers() {
	bp_video_set_nocache_constants();
	nocache_headers();
}

/**
 * Get content type of a download.
 *
 * @param  string $file_path File path.
 * @return string
 * @since BuddyBoss 1.4.1
 */
function bp_video_get_download_content_type( $file_path ) {
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
 * @param int $file_size Size of file in bytes.
 * @return array {
 *     Information about range download request: beginning and length of
 *     file chunk, whether the range is valid/supported and whether the request is a range request.
 *
 *     @type int  $start            Byte offset of the beginning of the range. Default 0.
 *     @type int  $length           Length of the requested file chunk in bytes. Optional.
 *     @type bool $is_range_valid   Whether the requested range is a valid and supported range.
 *     @type bool $is_range_request Whether the request is a range request.
 * }
 * @since BuddyBoss 1.4.1
 */
function bp_video_get_download_range( $file_size ) {
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
 * @param  string $file_path File path.
 * @return array
 * @since BuddyBoss 1.4.1
 */
function bp_video_parse_file_path( $file_path ) {
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
	} elseif ( ( ! isset( $parsed_file_path['scheme'] ) || ! in_array( $parsed_file_path['scheme'], array( 'http', 'https', 'ftp' ), true ) ) && isset( $parsed_file_path['path'] ) && file_exists( $parsed_file_path['path'] ) ) {
		$remote_file = false;
		$file_path   = $parsed_file_path['path'];
	}

	return array(
		'remote_file' => $remote_file,
		'file_path'   => $file_path,
	);
}

/**
 * Added text on the email when replied on the activity.
 *
 * @since BuddyBoss 1.4.7
 *
 * @param BP_Activity_Activity $activity Activity Object.
 */
function bp_video_activity_after_email_content( $activity ) {
	$video_ids = bp_activity_get_meta( $activity->id, 'bp_video_ids', true );

	if ( ! empty( $video_ids ) ) {
		$video_ids  = explode( ',', $video_ids );
		$video_text = sprintf(
			_n( '%s video', '%s videos', count( $video_ids ), 'buddyboss' ), // phpcs:ignore
			number_format_i18n( count( $video_ids ) )
		);
		$content    = sprintf(
			/* translator: 1. Activity link, 2. Activity video count */
			__( '<a href="%1$s" target="_blank">%2$s uploaded</a>', 'buddyboss' ), // phpcs:ignore
			bp_activity_get_permalink( $activity->id ),
			$video_text
		);
		echo wpautop( $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}


/**
 * Adds activity video data for the edit activity
 *
 * @param array $activity activity object.
 *
 * @return array $activity Returns the activity with video if video saved otherwise no video.
 *
 * @since BuddyBoss 1.5.0
 */
function bp_video_get_edit_activity_data( $activity ) {

	if ( ! empty( $activity['id'] ) ) {

		// Fetch video ids of activity.
		$video_ids = bp_activity_get_meta( $activity['id'], 'bp_video_ids', true );

		if ( ! empty( $video_ids ) ) {
			$activity['video'] = array();

			$video_ids = explode( ',', $video_ids );

			foreach ( $video_ids as $video_id ) {
				$video = new BP_Video( $video_id );

				$activity['video'][] = array(
					'id'          => $video_id,
					'vid_id'      => $video->attachment_id,
					'name'        => $video->title,
					'group_id'    => $video->group_id,
					'album_id'    => $video->album_id,
					'activity_id' => $video->activity_id,
					'type'        => 'video',
					'url'         => wp_get_attachment_url( $video->attachment_id ),
					'size'        => filesize( get_attached_file( ( $video->attachment_id ) ) ),
					'saved'       => true,
					'menu_order'  => $video->menu_order,
				);
			}
		}
	}

	return $activity;
}

/**
 * Protect downloads from ms-files.php in multisite.
 *
 * @param string $rewrite rewrite rules.
 * @return string
 * @since BuddyBoss 1.6.0
 */
function bp_video_protect_download_rewite_rules( $rewrite ) {
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
add_filter( 'mod_rewrite_rules', 'bp_video_protect_download_rewite_rules' );

/**
 * Function to create a protected directory for the videos.
 */
function bp_video_check_download_album_protection() {

	$upload_dir = wp_get_upload_dir();
	$files      = array(
		array(
			'base'    => $upload_dir['basedir'] . '/bb_medias',
			'file'    => 'index.html',
			'content' => '',
		),
		array(
			'base'    => $upload_dir['basedir'] . '/bb_medias',
			'file'    => '.htaccess',
			'content' => 'deny from all
# BEGIN BuddyBoss code execution protection
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
add_action( 'bp_init', 'bp_video_check_download_album_protection', 9999 );
