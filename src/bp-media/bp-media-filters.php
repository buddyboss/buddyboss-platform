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
add_action( 'bp_activity_after_delete', 'bp_media_delete_activity_media' );
add_filter( 'bp_get_activity_content_body', 'bp_media_activity_embed_gif', 20, 2 );
add_action( 'bp_activity_after_comment_content', 'bp_media_comment_embed_gif', 20, 1 );
add_action( 'bp_activity_after_save', 'bp_media_activity_save_gif_data', 2, 1 );
add_action( 'bp_activity_after_save', 'bp_media_activity_update_media_privacy', 2 );

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
add_action( 'messages_message_sent', 'bp_media_messages_save_group_data' );
add_action( 'bp_messages_thread_after_delete', 'bp_media_messages_delete_attached_media', 10, 2 );
add_action( 'bp_messages_thread_messages_after_update', 'bp_media_user_messages_delete_attached_media', 10, 4 );
add_action( 'bp_messages_thread_after_delete', 'bp_media_messages_delete_gif_data', 10, 2 );
// add_action( 'bp_messages_thread_after_delete', 'bp_group_messages_delete_meta', 10, 2 );.

// Core tools.
add_filter( 'bp_core_get_tools_settings_admin_tabs', 'bp_media_get_tools_media_settings_admin_tabs', 20, 1 );
add_action( 'bp_core_activation_notice', 'bp_media_activation_notice' );
add_action( 'wp_ajax_bp_media_import_status_request', 'bp_media_import_status_request' );

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
	);

	$args['privacy'] = bp_media_query_privacy( bp_get_activity_user_id(), 0, bp_get_activity_object_name() );
	$is_forum_activity = false;
	if (
		bp_is_active( 'forums' )
		&& in_array( bp_get_activity_type(), array( 'bbp_forum_create', 'bbp_topic_create', 'bbp_reply_create' ), true )
	) {
		$is_forum_activity = true;
		$args['privacy'][] = 'forums';
	}

	if ( ! empty( $media_ids ) && bp_has_media( $args ) ) { ?>
		<div class="bb-activity-media-wrap <?php echo esc_attr( 'bb-media-length-' . $media_template->media_count );
			echo $media_template->media_count > 5 ? esc_attr( ' bb-media-length-more' ) : '';
			echo true === $is_forum_activity ? esc_attr( ' forums-media-wrap' ) : ''; ?>">
			<?php
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

	$media_ids = bp_activity_get_meta( $activity->id, 'bp_media_ids', true );

	if ( ! empty( $media_ids ) ) {

		$args = array(
			'include'  => $media_ids,
			'order_by' => 'menu_order',
			'sort'     => 'ASC',
		);

		$args['privacy'] = bp_media_query_privacy( $activity->user_id, 0, $activity->component );

		$is_forum_activity = false;
		if (
			bp_is_active( 'forums' )
			&& in_array( $activity->type, array( 'bbp_forum_create', 'bbp_topic_create', 'bbp_reply_create' ), true )
		) {
			$is_forum_activity = true;
			$args['privacy'][] = 'forums';
		}

		if ( bp_has_media( $args ) ) {
			?>
			<?php ob_start(); ?>
			<div class="bb-activity-media-wrap <?php echo 'bb-media-length-' . $media_template->media_count;
				echo $media_template->media_count > 5 ? ' bb-media-length-more' : '';
				echo true === $is_forum_activity ? ' forums-media-wrap' : ''; ?>">
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
	);

	$args['privacy'] = bp_media_query_privacy( $activity->user_id, 0, $activity->component );

	if ( ! empty( $media_ids ) && bp_has_media( $args ) ) {
		?>
		<div class="bb-activity-media-wrap
		<?php
		echo esc_attr( 'bb-media-length-' . $media_template->media_count );
		echo $media_template->media_count > 5 ? esc_attr( ' bb-media-length-more' ) : '';
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

	if ( ! isset( $_POST['media'] ) || empty( $_POST['media'] ) ) {
		return false;
	}

	$_POST['medias']             = $_POST['media'];
	$_POST['bp_activity_update'] = true;
	$_POST['bp_activity_id']     = $activity_id;

	// Update activity comment attached document privacy with parent one.
	if ( ! empty( $activity_id ) && isset( $_POST['action'] ) && $_POST['action'] === 'new_activity_comment' ) {
		$parent_activity = new BP_Activity_Activity( $activity_id );
		if ( $parent_activity->component === 'groups' ) {
			$_POST['privacy'] = 'grouponly';
		} elseif ( ! empty( $parent_activity->privacy ) ) {
			$_POST['privacy'] = $parent_activity->privacy;
		}
	}

	remove_action( 'bp_activity_posted_update', 'bp_media_update_activity_media_meta', 10, 3 );
	remove_action( 'bp_groups_posted_update', 'bp_media_groups_activity_update_media_meta', 10, 4 );
	remove_action( 'bp_activity_comment_posted', 'bp_media_activity_comments_update_media_meta', 10, 3 );
	remove_action( 'bp_activity_comment_posted_notification_skipped', 'bp_media_activity_comments_update_media_meta', 10, 3 );

	$media_ids = bp_media_add_handler();

	add_action( 'bp_activity_posted_update', 'bp_media_update_activity_media_meta', 10, 3 );
	add_action( 'bp_groups_posted_update', 'bp_media_groups_activity_update_media_meta', 10, 4 );
	add_action( 'bp_activity_comment_posted', 'bp_media_activity_comments_update_media_meta', 10, 3 );
	add_action( 'bp_activity_comment_posted_notification_skipped', 'bp_media_activity_comments_update_media_meta', 10, 3 );

	// save media meta for activity.
	if ( ! empty( $activity_id ) ) {
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
	$bp_new_activity_comment = true;
	bp_media_update_activity_media_meta( false, false, $comment_id );
}

/**
 * Delete media when related activity is deleted.
 *
 * @since BuddyBoss 1.0.0
 * @param $activities
 */
function bp_media_delete_activity_media( $activities ) {
	if ( ! empty( $activities ) ) {
		remove_action( 'bp_activity_after_delete', 'bp_media_delete_activity_media' );
		foreach ( $activities as $activity ) {
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
 * Update media privacy according to album's privacy
 *
 * @since BuddyBoss 1.0.0
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

				$attachment_id    = $media_obj->attachment_id;
				$main_activity_id = get_post_meta( $attachment_id, 'bp_media_parent_activity_id', true );

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
 * Save media when new topic or reply is saved
 *
 * @since BuddyBoss 1.0.0
 * @param $post_id
 */
function bp_media_forums_new_post_media_save( $post_id ) {

	if ( ! empty( $_POST['bbp_media'] ) ) {

		// save activity id if it is saved in forums and enabled in platform settings.
		$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

		// save media.
		$medias = json_decode( stripslashes( $_POST['bbp_media'] ), true );

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
			bp_activity_update_meta( $main_activity_id, 'bp_media_ids', $media_ids );
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

	$media_ids = get_post_meta( $id, 'bp_media_ids', true );

	if ( ! empty( $media_ids ) && bp_has_media(
		array(
			'include'  => $media_ids,
			'order_by' => 'menu_order',
			'privacy'  => array( 'forums' ),
			'sort'     => 'ASC',
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
 * @param $content
 * @param $id
 *
 * @return string
 */
function bp_media_forums_embed_gif( $content, $id ) {
	$gif_data = get_post_meta( $id, '_gif_data', true );

	if ( empty( $gif_data ) ) {
		return $content;
	}

	$preview_url = wp_get_attachment_url( $gif_data['still'] );
	$video_url   = wp_get_attachment_url( $gif_data['mp4'] );

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
	$content .= ob_get_clean();

	return $content;
}

/**
 * save gif data for forum, topic, reply
 *
 * @since BuddyBoss 1.0.0
 * @param $post_id
 */
function bp_media_forums_save_gif_data( $post_id ) {

	if ( ! bp_is_forums_gif_support_enabled() ) {
		return;
	}

	if ( ! empty( $_POST['bbp_media_gif'] ) ) {

		// save activity id if it is saved in forums and enabled in platform settings.
		$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

		// save gif data.
		$gif_data = json_decode( stripslashes( $_POST['bbp_media_gif'] ), true );

		if ( ! empty( $gif_data['saved'] ) && $gif_data['saved'] ) {
			return;
		}

		$still = bp_media_sideload_attachment( $gif_data['images']['480w_still']['url'] );
		$mp4   = bp_media_sideload_attachment( $gif_data['images']['original_mp4']['mp4'] );

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
	}
}

/**
 * Attach media to the message object
 *
 * @since BuddyBoss 1.0.0
 * @param $message
 */
function bp_media_attach_media_to_message( &$message ) {

	if ( bp_is_messages_media_support_enabled() && ! empty( $message->id ) && ! empty( $_POST['media'] ) ) {
		remove_action( 'bp_media_add', 'bp_activity_media_add', 9 );
		remove_filter( 'bp_media_add_handler', 'bp_activity_create_parent_media_activity', 9 );

		$media_ids = bp_media_add_handler( $_POST['media'] );

		add_action( 'bp_media_add', 'bp_activity_media_add', 9 );
		add_filter( 'bp_media_add_handler', 'bp_activity_create_parent_media_activity', 9 );

		// save media meta for message..
		bp_messages_update_meta( $message->id, 'bp_media_ids', implode( ',', $media_ids ) );
	}
}

/**
 * Delete media attached to messages
 *
 * @since BuddyBoss 1.0.0
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
 * Delete gif attached to messages
 *
 * @since BuddyBoss 1.2.9
 * @param $thread_id
 * @param $message_ids
 */
function bp_media_messages_delete_gif_data( $thread_id, $message_ids ) {

	if ( ! empty( $message_ids ) ) {
		foreach ( $message_ids as $message_id ) {
			bp_messages_update_meta( $message_id, '_gif_data', '' );
			bp_messages_update_meta( $message_id, '_gif_raw_data', '' );

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

	if ( ! bp_is_messages_gif_support_enabled() || empty( $_POST['gif_data'] ) ) {
		return;
	}

	$gif_data = $_POST['gif_data'];

	$still = bp_media_sideload_attachment( $gif_data['images']['480w_still']['url'] );
	$mp4   = bp_media_sideload_attachment( $gif_data['images']['original_mp4']['mp4'] );

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

/**
 * Return activity gif embed HTML
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $activity_id
 *
 * @return false|string|void
 */
function bp_media_activity_embed_gif_content( $activity_id ) {

	$gif_data = bp_activity_get_meta( $activity_id, '_gif_data', true );

	if ( empty( $gif_data ) ) {
		return;
	}

	$preview_url = wp_get_attachment_url( $gif_data['still'] );
	$video_url   = wp_get_attachment_url( $gif_data['mp4'] );

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
 * Embed gif in activity content
 *
 * @param $content
 * @param $activity
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_media_activity_embed_gif( $content, $activity ) {

	$gif_content = bp_media_activity_embed_gif_content( $activity->id );

	if ( ! empty( $gif_content ) ) {
		$content .= $gif_content;
	}

	return $content;
}

/**
 * Embed gif in activity comment content
 *
 * @param $content
 * @param $activity
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_media_comment_embed_gif( $activity_id ) {

	$gif_content = bp_media_activity_embed_gif_content( $activity_id );

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

	if ( empty( $_POST['gif_data'] ) ) {
		return;
	}

	$gif_data = $_POST['gif_data'];

	$still = bp_media_sideload_attachment( $gif_data['images']['480w_still']['url'] );
	$mp4   = bp_media_sideload_attachment( $gif_data['images']['original_mp4']['mp4'] );

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

function bp_media_get_tools_media_settings_admin_tabs( $tabs ) {

	$tabs[] = array(
		'href' => get_admin_url(
			'',
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

	if ( in_array( $bp_media_import_status, array( 'importing', 'start', 'reset_albums', 'reset_media', 'reset_forum', 'reset_topic', 'reset_reply', 'reset_options' ) ) ) {
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
						<h2><?php _e( 'Import Media', 'buddyboss' ); ?></h2>

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
							<label style="display: none;" id="bp-media-resetting"><strong><?php echo __( 'Migration in progress', 'buddyboss' ) . '...'; ?></strong></label>
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
								<input type="hidden" value="1" name="bp-media-re-run-import" id="bp-media-re-run-import"/>
								<input type="submit" style="display: none;" value="<?php _e( 'Re-Run Migration', 'buddyboss' ); ?>" id="bp-media-import-submit" name="bp-media-import-submit" class="button-primary"/>
								<?php
}
						} elseif ( 'done' == $bp_media_import_status ) {
							$albums_ids = get_option( 'bp_media_import_albums_ids', array() );
							$media_ids  = get_option( 'bp_media_import_media_ids', array() );
							?>
							<p><?php _e( 'BuddyBoss Media data update is complete! Any previously uploaded member photos should display in their profiles now.', 'buddyboss' ); ?></p>

							<?php if ( ! empty( $albums_ids ) || ! empty( $media_ids ) ) { ?>
								<input type="hidden" value="1" name="bp-media-re-run-import" id="bp-media-re-run-import"/>
								<input type="submit" value="<?php _e( 'Re-Run Migration', 'buddyboss' ); ?>" id="bp-media-import-submit" name="bp-media-import-submit" class="button-primary"/>
								<?php
}
						} else {
							?>
							<p><?php _e( 'Import your existing members photo uploads, if you were previously using <a href="https://www.buddyboss.com/product/buddyboss-media/">BuddyBoss Media</a> with BuddyPress. Click "Run Migration" below to migrate your old photos into the new Media component.', 'buddyboss' ); ?></p>
							<input type="submit" value="<?php _e( 'Run Migration', 'buddyboss' ); ?>" id="bp-media-import-submit" name="bp-media-import-submit" class="button-primary"/>
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
			if ( ! in_array( $media->privacy, array( 'forums', 'message', 'media', 'document', 'grouponly') ) ) {
				$media->privacy = $activity->privacy;
				$media->save();
			}

		}
	}
}

/**
 * Save group message meta.
 *
 * @since BuddyBoss 1.2.9
 *
 * @param $message
 */
function bp_media_messages_save_group_data( &$message ) {

	if ( false === bp_disable_group_messages() ) {
		return;
	}

	$group                   = ( isset( $_POST ) && isset( $_POST['group'] ) && '' !== $_POST['group'] ) ? trim( $_POST['group'] ) : ''; // Group id.
	$message_users           = ( isset( $_POST ) && isset( $_POST['users'] ) && '' !== $_POST['users'] ) ? trim( $_POST['users'] ) : ''; // all - individual.
	$message_type            = ( isset( $_POST ) && isset( $_POST['type'] ) && '' !== $_POST['type'] ) ? trim( $_POST['type'] ) : ''; // open - private.
	$message_meta_users_list = ( isset( $_POST ) && isset( $_POST['message_meta_users_list'] ) && '' !== $_POST['message_meta_users_list'] ) ? trim( $_POST['message_meta_users_list'] ) : ''; // users list.
	$thread_type             = ( isset( $_POST ) && isset( $_POST['message_thread_type'] ) && '' !== $_POST['message_thread_type'] ) ? trim( $_POST['message_thread_type'] ) : ''; // new - reply.

	if ( '' === $message_meta_users_list && isset( $group ) && '' !== $group ) {
		$args = array(
			'per_page'            => 99999999999999,
			'group'               => $group,
			'exclude'             => array( bp_loggedin_user_id() ),
			'exclude_admins_mods' => false,
		);

		$group_members           = groups_get_group_members( $args );
		$members                 = wp_list_pluck( $group_members['members'], 'ID' );
		$message_meta_users_list = implode( ',', $members );
	}

	if ( isset( $group ) && '' !== $group ) {
		$thread_key = 'group_message_thread_id_' . $message->thread_id;
		bp_messages_update_meta( $message->id, 'group_id', $group );
		bp_messages_update_meta( $message->id, 'group_message_users', $message_users );
		bp_messages_update_meta( $message->id, 'group_message_type', $message_type );
		bp_messages_update_meta( $message->id, 'group_message_thread_type', $thread_type );
		bp_messages_update_meta( $message->id, 'group_message_fresh', 'yes' );
		bp_messages_update_meta( $message->id, $thread_key, $group );
		bp_messages_update_meta( $message->id, 'message_from', 'group' );
		bp_messages_update_meta( $message->id, 'message_sender', bp_loggedin_user_id() );
		bp_messages_update_meta( $message->id, 'message_users_ids', $message_meta_users_list );
		bp_messages_update_meta( $message->id, 'group_message_thread_id', $message->thread_id );
	} else {

		$args = array(
			'thread_id' => $message->thread_id,
			'per_page'  => 99999999999999,
		);

		if ( bp_thread_has_messages( $args ) ) {
			while ( bp_thread_messages() ) :
				bp_thread_the_message();

				$message_id    = bp_get_the_thread_message_id();
				$group         = bp_messages_get_meta( $message_id, 'group_id', true );
				$message_users = bp_messages_get_meta( $message_id, 'group_message_users', true );
				$message_type  = bp_messages_get_meta( $message_id, 'group_message_type', true );
				$thread_type   = bp_messages_get_meta( $message_id, 'group_message_thread_type', true );

				if ( $group ) {
					break;
				}
			endwhile;
		}

		if ( $group ) {

			$thread_key = 'group_message_thread_id_' . $message->thread_id;
			bp_messages_update_meta( $message->id, 'group_id', $group );
			bp_messages_update_meta( $message->id, 'group_message_users', $message_users );
			bp_messages_update_meta( $message->id, 'group_message_type', $message_type );
			bp_messages_update_meta( $message->id, 'group_message_thread_type', $thread_type );
			bp_messages_update_meta( $message->id, $thread_key, $group );
			bp_messages_update_meta( $message->id, 'message_sender', bp_loggedin_user_id() );
			bp_messages_update_meta( $message->id, 'message_from', 'personal' );
			bp_messages_update_meta( $message->id, 'group_message_thread_id', $message->thread_id );
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
