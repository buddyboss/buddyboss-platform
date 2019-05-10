<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'bp_media_album_after_save',                        'bp_media_update_media_privacy'                     );

// Activity
add_action( 'bp_after_activity_loop',                           'bp_media_add_theatre_template'                     );
add_action( 'bp_activity_entry_content',                        'bp_media_activity_entry'                           );
add_action( 'bp_activity_after_comment_content',                'bp_media_activity_comment_entry'                   );
add_action( 'bp_activity_posted_update',                        'bp_media_update_media_meta',               10, 3   );
add_action( 'bp_groups_posted_update',                          'bp_media_groups_update_media_meta',        10, 4   );
add_action( 'bp_activity_comment_posted',                       'bp_media_comments_update_media_meta',      10, 3   );
add_action( 'bp_activity_comment_posted_notification_skipped',  'bp_media_comments_update_media_meta',      10, 3   );
add_action( 'bp_activity_after_delete',                         'bp_media_delete_activity_media'                    );
add_filter( 'bp_get_activity_content_body',                     'bp_media_activity_embed_gif',              20, 2   );
add_action( 'bp_activity_after_comment_content',                'bp_media_comment_embed_gif',               20, 1   );
add_action( 'bp_activity_after_save',                           'bp_media_activity_save_gif_data',           2, 1   );

// Forums
add_action( 'bbp_template_after_single_topic',                  'bp_media_add_theatre_template'                     );
add_action( 'bbp_new_reply',                                    'bp_media_forums_new_post_media_save',     999      );
add_action( 'bbp_new_topic',                                    'bp_media_forums_new_post_media_save',     999      );
add_action( 'edit_post',                                        'bp_media_forums_new_post_media_save',     999      );

add_filter( 'bbp_get_reply_content',                            'bp_media_forums_embed_attachments',       999, 2   );
add_filter( 'bbp_get_topic_content',                            'bp_media_forums_embed_attachments',       999, 2   );

// Messages
add_action( 'messages_message_sent',                            'bp_media_attach_media_to_message'                  );
add_action( 'messages_message_sent',                            'bp_media_messages_save_gif_data'                   );

/**
 * Add media theatre template for activity pages
 */
function bp_media_add_theatre_template() {
	bp_get_template_part( 'media/theatre' );
}

/**
 * Get activity entry media to render on front end
 */
function bp_media_activity_entry() {
	global $media_template;
	$media_ids = bp_activity_get_meta( bp_get_activity_id(), 'bp_media_ids', true );

	if ( ! empty( $media_ids ) && bp_has_media( array( 'include' => $media_ids, 'order_by' => 'menu_order', 'sort' => 'ASC' ) ) ) { ?>
		<div class="bb-activity-media-wrap <?php echo 'bb-media-length-' . $media_template->media_count; echo $media_template->media_count > 5 ? 'bb-media-length-more' : ''; ?>"><?php
		while ( bp_media() ) {
			bp_the_media();
			bp_get_template_part( 'media/activity-entry' );
		} ?>
		</div><?php
	}
}

/**
 * Get activity comment entry media to render on front end
 */
function bp_media_activity_comment_entry( $comment_id ) {
	global $media_template;
	$media_ids = bp_activity_get_meta( $comment_id, 'bp_media_ids', true );

	if ( ! empty( $media_ids ) && bp_has_media( array( 'include' => $media_ids, 'order_by' => 'menu_order', 'sort' => 'ASC' ) ) ) { ?>
		<div class="bb-activity-media-wrap <?php echo 'bb-media-length-' . $media_template->media_count; echo $media_template->media_count > 5 ? 'bb-media-length-more' : ''; ?>"><?php
		while ( bp_media() ) {
			bp_the_media();
			bp_get_template_part( 'media/activity-entry' );
		} ?>
		</div><?php
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
function bp_media_update_media_meta( $content, $user_id, $activity_id ) {

	if ( ! isset( $_POST['media'] ) || empty( $_POST['media'] ) ) {
		return false;
	}

	$media_list = $_POST['media'];

	if ( ! empty( $media_list ) ) {
		$media_ids = array();
		foreach ( $media_list as $media_index => $media ) {

			// remove actions to avoid infinity loop
			remove_action( 'bp_activity_posted_update', 'bp_media_update_media_meta', 10, 3 );
			remove_action( 'bp_groups_posted_update', 'bp_media_groups_update_media_meta', 10, 4 );

			// make an activity for the media
			$a_id = bp_activity_post_update( array( 'hide_sitewide' => true ) );

			if ( $a_id ) {
				// update activity meta
				bp_activity_update_meta( $a_id, 'bp_media_activity', '1' );
			}

			add_action( 'bp_activity_posted_update', 'bp_media_update_media_meta', 10, 3 );
			add_action( 'bp_groups_posted_update', 'bp_media_groups_update_media_meta', 10, 4 );

			$title         = ! empty( $media['name'] ) ? $media['name'] : '&nbsp;';
			$album_id      = ! empty( $media['album_id'] ) ? $media['album_id'] : 0;
			$privacy       = ! empty( $media['privacy'] ) ? $media['privacy'] : 'public';
			$attachment_id = ! empty( $media['id'] ) ? $media['id'] : 0;
			$menu_order    = ! empty( $media['menu_order'] ) ? $media['menu_order'] : $media_index;

			$media_id = bp_media_add(
				array(
					'title'         => $title,
					'album_id'      => $album_id,
					'activity_id'   => $a_id,
					'privacy'       => $privacy,
					'attachment_id' => $attachment_id,
					'menu_order'    => $menu_order,
				)
			);

			if ( $media_id ) {
				$media_ids[] = $media_id;

				//save media is saved in attahchment
				update_post_meta( $attachment_id, 'bp_media_saved', true );

				//save media meta for activity
				if ( ! empty( $activity_id ) && ! empty( $attachment_id ) ) {
					update_post_meta( $attachment_id, 'bp_media_parent_activity_id', $activity_id );
					update_post_meta( $attachment_id, 'bp_media_activity_id', $a_id );
				}
			}
		}

		$media_ids = implode( ',', $media_ids );

		//save media meta for activity
		if ( ! empty( $activity_id ) ) {
			bp_activity_update_meta( $activity_id, 'bp_media_ids', $media_ids );
		}
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
function bp_media_groups_update_media_meta( $content, $user_id, $group_id, $activity_id ) {
	bp_media_update_media_meta( $content, $user_id, $activity_id );
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
function bp_media_comments_update_media_meta( $comment_id, $r, $activity ) {
	bp_media_update_media_meta( false, false, $comment_id );
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
			$activity_id = $activity->id;
			$media_activity = bp_activity_get_meta( $activity_id, 'bp_media_activity', true );
			if ( ! empty( $media_activity ) && '1' == $media_activity ) {
				$result = bp_media_get( array( 'activity_id' => $activity_id, 'fields' => 'ids' ) );
				if ( ! empty( $result['medias'] ) ) {
					foreach( $result['medias'] as $media_id ) {
						bp_media_delete( $media_id ); // delete media
					}
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
function bp_media_update_media_privacy( &$album ) {

	if ( ! empty( $album->id ) ) {

		$privacy   = $album->privacy;
		$media_ids = BP_Media::get_album_media_ids( $album->id );

		if ( ! empty( $media_ids ) ) {
			foreach( $media_ids as $media ) {
				$media_obj          = new BP_Media( $media );
				$media_obj->privacy = $privacy;
				$media_obj->save();
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

		// save activity id if it is saved in forums and enabled in platform settings
		$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

		// save media
		$medias = json_decode( stripslashes( $_POST['bbp_media'] ), true );
		$media_ids = array();
		foreach ( $medias as $media ) {

			$activity_id = false;
			// make an activity for the media
			if ( bp_is_active( 'activity' ) && ! empty( $main_activity_id ) ) {
				$activity_id = bp_activity_post_update( array( 'hide_sitewide' => true ) );

				if ( ! empty( $activity_id ) ) {
					bp_activity_update_meta( $activity_id, 'bp_media_activity', true );
				}
			}

			$title         = ! empty( $media['name'] ) ? $media['name'] : '';
			$attachment_id = ! empty( $media['id'] ) ? $media['id'] : 0;
			$album_id      = ! empty( $media['album_id'] ) ? $media['album_id'] : 0;

			$media_id = bp_media_add( array(
				'attachment_id' => $attachment_id,
				'title'         => $title,
				'activity_id'   => $activity_id,
				'album_id'      => $album_id,
				'error_type'    => 'wp_error'
			) );

			if ( ! is_wp_error( $media_id ) ) {
				$media_ids[] = $media_id;

				//save media is saved in attachment
				update_post_meta( $attachment_id, 'bp_media_saved', true );
			}
		}

		$media_ids = implode( ',', $media_ids );

		//save media meta for activity
		if ( ! empty( $main_activity_id ) ) {
			bp_activity_update_meta( $main_activity_id, 'bp_media_ids', $media_ids );
		}

		//Save all attachment ids in forums post meta
		update_post_meta( $post_id, 'bp_media_ids', $media_ids );
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

	// Do not embed attachment in wp-admin area
	if ( is_admin() ) {
		return $content;
	}

	$media_ids = get_post_meta( $id, 'bp_media_ids', true );

	if ( ! empty( $media_ids ) && bp_has_media( array( 'include' => $media_ids, 'order_by' => 'menu_order', 'sort' => 'ASC' ) ) ) {
		ob_start();
		?>
        <div class="bb-activity-media-wrap <?php echo 'bb-media-length-' . $media_template->media_count; echo $media_template->media_count > 5 ? 'bb-media-length-more' : ''; ?>"><?php
		while ( bp_media() ) {
			bp_the_media();
			bp_get_template_part( 'media/activity-entry' );
		} ?>
        </div><?php
		$content .= ob_get_clean();
	}

	return $content;
}

/**
 * Attach media to the message object
 *
 * @since BuddyBoss 1.0.0
 * @param $message
 */
function bp_media_attach_media_to_message( &$message ) {

	if ( bp_is_messages_media_support_enabled() && ! empty( $message->id ) && ! empty( $_POST['media'] ) ) {
		$media_list = $_POST['media'];
		$media_ids = array();

		foreach ( $media_list as $media_index => $media ) {
			$title         = ! empty( $media['name'] ) ? $media['name'] : '&nbsp;';
			$attachment_id = ! empty( $media['id'] ) ? $media['id'] : 0;

			$media_id = bp_media_add(
				array(
					'title'         => $title,
					'privacy'       => 'message',
					'attachment_id' => $attachment_id,
				)
			);

			if ( $media_id ) {
				$media_ids[] = $media_id;

				//save media is saved in attachment
				update_post_meta( $attachment_id, 'bp_media_saved', true );
			}
		}

		$media_ids = implode( ',', $media_ids );

		//save media meta for message
		bp_messages_update_meta( $message->id, 'bp_media_ids', $media_ids );
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

	if ( ! bp_is_gif_support_enabled() || empty( $_POST['gif_data'] ) ) {
		return;
	}

	$gif_data =  $_POST['gif_data'];

	$still = bp_media_sideload_attachment( $gif_data['images']['480w_still']['url'] );
	$mp4 = bp_media_sideload_attachment( $gif_data['images']['original_mp4']['mp4'] );

	bp_messages_update_meta( $message->id, '_gif_data', [
		'still' => $still,
		'mp4'   => $mp4,
	] );

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
	$video_url = wp_get_attachment_url( $gif_data['mp4'] );

	ob_start();
	?>
    <div class="activity-attached-gif-container">
        <div class="gif-image-container">
            <div class="gif-player">
                <video preload="auto" playsinline poster="<?php echo $preview_url ?>" loop muted playsinline>
                    <source src="<?php echo $video_url ?>" type="video/mp4">
                </video>
                <a href="#" class="gif-play-button">
                    <span class="dashicons dashicons-video-alt3"></span>
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

	$gif_content = bp_media_activity_embed_gif_content(  $activity->id );

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

	$gif_content = bp_media_activity_embed_gif_content(  $activity_id );

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

	$gif_data =  $_POST['gif_data'];

	$still = bp_media_sideload_attachment( $gif_data['images']['480w_still']['url'] );
	$mp4 = bp_media_sideload_attachment( $gif_data['images']['original_mp4']['mp4'] );

	bp_activity_update_meta( $activity->id, '_gif_data', [
		'still' => $still,
		'mp4'   => $mp4,
	] );

	bp_activity_update_meta( $activity->id, '_gif_raw_data', $gif_data );
}