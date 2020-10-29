<?php
/**
 * BuddyBoss Video Functions.
 *
 * Functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 *
 * @package BuddyBoss\Video\Functions
 * @since BuddyBoss 1.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Create and upload the video file
 *
 * @since BuddyBoss 1.6.0
 *
 * @return array|null|WP_Error|WP_Post
 */
function bp_video_upload() {
	/**
	 * Make sure user is logged in
	 */
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'not_logged_in', __( 'Please login in order to upload file video.', 'buddyboss' ), array( 'status' => 500 ) );
	}

	$attachment = bp_video_upload_handler();

	if ( is_wp_error( $attachment ) ) {
		return $attachment;
	}

	$name = $attachment->post_title;

	$thumb_nfo = wp_get_attachment_image_src( $attachment->ID );
	$url_nfo   = wp_get_attachment_image_src( $attachment->ID, 'full' );

	$url       = is_array( $url_nfo ) && ! empty( $url_nfo ) ? $url_nfo[0] : null;
	$thumb_nfo = is_array( $thumb_nfo ) && ! empty( $thumb_nfo ) ? $thumb_nfo[0] : null;

	$result = array(
		'id'    => (int) $attachment->ID,
		'thumb' => esc_url( $thumb_nfo ),
		'url'   => esc_url( $url ),
		'name'  => esc_attr( $name ),
	);

	return $result;
}

/**
 * Mine type for uploader allowed by buddyboss video for security reason
 *
 * @param  Array $mime_types carry mime information
 * @since BuddyBoss 1.6.0
 *
 * @return Array
 */
function bp_video_allowed_mimes( $mime_types ) {

	// Creating a new array will reset the allowed filetypes
	$mime_types = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'gif'          => 'image/gif',
		'png'          => 'image/png',
		'bmp'          => 'image/bmp',
	);

	return $mime_types;
}

/**
 * Video upload handler
 *
 * @param string $file_id
 *
 * @since BuddyBoss 1.6.0
 *
 * @return array|int|null|WP_Error|WP_Post
 */
function bp_video_upload_handler( $file_id = 'file' ) {

	/**
	 * Include required files
	 */

	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin' . '/includes/image.php';
		require_once ABSPATH . 'wp-admin' . '/includes/file.php';
		require_once ABSPATH . 'wp-admin' . '/includes/media.php';
	}

	if ( ! function_exists( 'video_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/admin.php';
	}

	add_image_size( 'bp-video-thumbnail', 400, 400 );
	add_image_size( 'bp-activity-video-thumbnail', 1600, 1600 );

	add_filter( 'upload_mimes', 'bp_video_allowed_mimes', 9, 1 );

	$aid = video_handle_upload(
		$file_id,
		0,
		array(),
		array(
			'test_form'            => false,
			'upload_error_strings' => array(
				false,
				__( 'The uploaded file exceeds ', 'buddyboss' ) . bp_video_file_upload_max_size(),
				__( 'The uploaded file was only partially uploaded.', 'buddyboss' ),
				__( 'No file was uploaded.', 'buddyboss' ),
				'',
				__( 'Missing a temporary folder.', 'buddyboss' ),
				__( 'Failed to write file to disk.', 'buddyboss' ),
				__( 'File upload stopped by extension.', 'buddyboss' ),
			),
		)
	);

	remove_image_size( 'bp-video-thumbnail' );
	remove_image_size( 'bp-activity-video-thumbnail' );

	// if has wp error then throw it.
	if ( is_wp_error( $aid ) ) {
		return $aid;
	}

	// Image rotation fix
	do_action( 'bp_video_attachment_uploaded', $aid );

	$attachment = get_post( $aid );

	if ( ! empty( $attachment ) ) {
		update_post_meta( $attachment->ID, 'bp_video_upload', true );
		update_post_meta( $attachment->ID, 'bp_video_saved', '0' );
		return $attachment;
	}

	return new WP_Error( 'error_uploading', __( 'Error while uploading video.', 'buddyboss' ), array( 'status' => 500 ) );

}

/**
 * Compress the image
 *
 * @param $source
 * @param $destination
 * @param int         $quality
 *
 * @since BuddyBoss 1.6.0
 *
 * @return mixed
 */
function bp_video_compress_image( $source, $destination, $quality = 90 ) {

	$info = @getimagesize( $source );

	if ( $info['mime'] == 'image/jpeg' ) {
		$image = @imagecreatefromjpeg( $source );
	} elseif ( $info['mime'] == 'image/gif' ) {
		$image = @imagecreatefromgif( $source );
	} elseif ( $info['mime'] == 'image/png' ) {
		$image = @imagecreatefrompng( $source );
	}

	@imagejpeg( $image, $destination, $quality );

	return $destination;
}

/**
 * Get file video upload max size
 *
 * @param bool $post_string
 *
 * @since BuddyBoss 1.6.0
 *
 * @return string
 */
function bp_video_file_upload_max_size() {

	/**
	 * Filters file video upload max limit.
	 *
	 * @param mixed $max_size video upload max limit.
	 *
	 * @since BuddyBoss 1.4.1
	 */
	return apply_filters( 'bp_video_file_upload_max_size', bp_video_allowed_upload_video_size() );
}

/**
 * Format file size units
 *
 * @param $bytes
 * @param bool  $post_string
 *
 * @since BuddyBoss 1.6.0
 *
 * @return string
 */
function bp_video_format_size_units( $bytes, $post_string = false, $type = 'bytes' ) {

	if ( $bytes > 0 ) {
		if ( 'GB' === $type && ! $post_string ) {
			return $bytes / 1073741824;
		} elseif ( 'MB' === $type && ! $post_string ) {
			return $bytes / 1048576;
		} elseif ( 'KB' === $type && ! $post_string ) {
			return $bytes / 1024;
		}
	}

	if ( $bytes >= 1073741824 ) {
		$bytes = ( $bytes / 1073741824 ) . ( $post_string ? ' GB' : '' );
	} elseif ( $bytes >= 1048576 ) {
		$bytes = ( $bytes / 1048576 ) . ( $post_string ? ' MB' : '' );
	} elseif ( $bytes >= 1024 ) {
		$bytes = ( $bytes / 1024 ) . ( $post_string ? ' KB' : '' );
	} elseif ( $bytes > 1 ) {
		$bytes = $bytes . ( $post_string ? ' bytes' : '' );
	} elseif ( $bytes == 1 ) {
		$bytes = $bytes . ( $post_string ? ' byte' : '' );
	} else {
		$bytes = '0' . ( $post_string ? ' bytes' : '' );
	}

	return $bytes;
}

/*
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 */

/**
 * Retrieve an video or videos.
 *
 * The bp_video_get() function shares all arguments with BP_Video::get().
 * The following is a list of bp_video_get() parameters that have different
 * default values from BP_Video::get() (value in parentheses is
 * the default for the bp_video_get()).
 *   - 'per_page' (false)
 *
 * @since BuddyBoss 1.6.0
 *
 * @see BP_Video::get() For more information on accepted arguments
 *      and the format of the returned value.
 *
 * @param array|string $args See BP_Video::get() for description.
 * @return array $video See BP_Video::get() for description.
 */
function bp_video_get( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'max'          => false,        // Maximum number of results to return.
			'fields'       => 'all',
			'page'         => 1,            // Page 1 without a per_page will result in no pagination.
			'per_page'     => false,        // results per page
			'sort'         => 'DESC',       // sort ASC or DESC
			'order_by'     => false,       // order by

			'scope'        => false,

			// want to limit the query.
			'user_id'      => false,
			'activity_id'  => false,
			'album_id'     => false,
			'group_id'     => false,
			'search_terms' => false,        // Pass search terms as a string
			'privacy'      => false,        // privacy of video
			'exclude'      => false,        // Comma-separated list of activity IDs to exclude.
			'count_total'  => false,
		),
		'video_get'
	);

	$video = BP_Video::get(
		array(
			'page'         => $r['page'],
			'per_page'     => $r['per_page'],
			'user_id'      => $r['user_id'],
			'activity_id'  => $r['activity_id'],
			'album_id'     => $r['album_id'],
			'group_id'     => $r['group_id'],
			'max'          => $r['max'],
			'sort'         => $r['sort'],
			'order_by'     => $r['order_by'],
			'search_terms' => $r['search_terms'],
			'scope'        => $r['scope'],
			'privacy'      => $r['privacy'],
			'exclude'      => $r['exclude'],
			'count_total'  => $r['count_total'],
			'fields'       => $r['fields'],
		)
	);

	/**
	 * Filters the requested video item(s).
	 *
	 * @since BuddyBoss 1.6.0
	 *
	 * @param BP_Video  $video Requested video object.
	 * @param array     $r     Arguments used for the video query.
	 */
	return apply_filters_ref_array( 'bp_video_get', array( &$video, &$r ) );
}

/**
 * Fetch specific video items.
 *
 * @since BuddyBoss 1.6.0
 *
 * @see BP_Video::get() For more information on accepted arguments.
 *
 * @param array|string $args {
 *     All arguments and defaults are shared with BP_Video::get(),
 *     except for the following:
 *     @type string|int|array Single video ID, comma-separated list of IDs,
 *                            or array of IDs.
 * }
 * @return array $activity See BP_Video::get() for description.
 */
function bp_video_get_specific( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'video_ids' => false,      // A single video_id or array of IDs.
			'max'       => false,      // Maximum number of results to return.
			'page'      => 1,          // Page 1 without a per_page will result in no pagination.
			'per_page'  => false,      // Results per page.
			'sort'      => 'DESC',     // Sort ASC or DESC.
			'order_by'  => false,     // Sort ASC or DESC.
			'privacy'   => false,     // privacy to filter.
			'album_id'  => false,     // Album ID.
			'user_id'   => false,     // User ID.
		),
		'video_get_specific'
	);

	$get_args = array(
		'in'       => $r['video_ids'],
		'max'      => $r['max'],
		'page'     => $r['page'],
		'per_page' => $r['per_page'],
		'sort'     => $r['sort'],
		'order_by' => $r['order_by'],
		'privacy'  => $r['privacy'],
		'album_id' => $r['album_id'],
		'user_id'  => $r['user_id'],
	);

	/**
	 * Filters the requested specific video item.
	 *
	 * @since BuddyBoss
	 *
	 * @param BP_Video      $video    Requested video object.
	 * @param array         $args     Original passed in arguments.
	 * @param array         $get_args Constructed arguments used with request.
	 */
	return apply_filters( 'bp_video_get_specific', BP_Video::get( $get_args ), $args, $get_args );
}

/**
 * Add an video item.
 *
 * @since BuddyBoss 1.6.0
 *
 * @param array|string $args {
 *     An array of arguments.
 *     @type int|bool $id                Pass an video ID to update an existing item, or
 *                                       false to create a new item. Default: false.
 *     @type int|bool $blog_id           ID of the blog Default: current blog id.
 *     @type int|bool $attchment_id      ID of the attachment Default: false
 *     @type int|bool $user_id           Optional. The ID of the user associated with the activity
 *                                       item. May be set to false or 0 if the item is not related
 *                                       to any user. Default: the ID of the currently logged-in user.
 *     @type string   $title             Optional. The title of the video item.

 *     @type int      $album_id          Optional. The ID of the associated album.
 *     @type int      $group_id          Optional. The ID of a associated group.
 *     @type int      $activity_id       Optional. The ID of a associated activity.
 *     @type string   $privacy           Optional. Privacy of the video Default: public
 *     @type int      $menu_order        Optional. Menu order the video Default: false
 *     @type string   $date_created      Optional. The GMT time, in Y-m-d h:i:s format, when
 *                                       the item was recorded. Defaults to the current time.
 *     @type string   $error_type        Optional. Error type. Either 'bool' or 'wp_error'. Default: 'bool'.
 * }
 * @return WP_Error|bool|int The ID of the video on success. False on error.
 */
function bp_video_add( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'id'            => false,                   // Pass an existing video ID to update an existing entry.
			'blog_id'       => get_current_blog_id(),   // Blog ID.
			'attachment_id' => false,                   // attachment id.
			'user_id'       => bp_loggedin_user_id(),   // user_id of the uploader.
			'title'         => '',                      // title of video being added.
			'album_id'      => false,                   // Optional: ID of the album.
			'group_id'      => false,                   // Optional: ID of the group.
			'activity_id'   => false,                   // The ID of activity.
			'privacy'       => 'public',                // Optional: privacy of the video e.g. public.
			'menu_order'    => 0,                       // Optional:  Menu order.
			'date_created'  => bp_core_current_time(),  // The GMT time that this video was recorded.
			'error_type'    => 'bool',
		),
		'video_add'
	);

	// Setup video to be added.
	$video                = new BP_Video( $r['id'] );
	$video->blog_id       = $r['blog_id'];
	$video->attachment_id = $r['attachment_id'];
	$video->user_id       = (int) $r['user_id'];
	$video->title         = $r['title'];
	$video->album_id      = (int) $r['album_id'];
	$video->group_id      = (int) $r['group_id'];
	$video->activity_id   = (int) $r['activity_id'];
	$video->privacy       = $r['privacy'];
	$video->menu_order    = $r['menu_order'];
	$video->date_created  = $r['date_created'];
	$video->error_type    = $r['error_type'];

	// groups document always have privacy to `grouponly`.
	if ( ! empty( $video->privacy ) && ( in_array( $video->privacy, array( 'forums', 'message' ), true ) ) ) {
		$video->privacy = $r['privacy'];
	} elseif ( ! empty( $video->group_id ) ) {
		$video->privacy = 'grouponly';
	} elseif ( ! empty( $video->album_id ) ) {
		$album = new BP_Video_Album( $video->album_id );
		if ( ! empty( $album ) ) {
			$video->privacy = $album->privacy;
		}
	}

	if ( isset( $_POST ) && isset( $_POST['action'] ) && 'groups_get_group_members_send_message' === $_POST['action'] ) {
		$video->privacy = 'message';
	}

	// save video.
	$save = $video->save();

	if ( 'wp_error' === $r['error_type'] && is_wp_error( $save ) ) {
		return $save;
	} elseif ( 'bool' === $r['error_type'] && false === $save ) {
		return false;
	}

	// video is saved for attachment.
	update_post_meta( $video->attachment_id, 'bp_video_saved', true );

	/**
	 * Fires at the end of the execution of adding a new video item, before returning the new video item ID.
	 *
	 * @since BuddyBoss 1.6.0
	 *
	 * @param object $video Video object.
	 */
	do_action( 'bp_video_add', $video );

	return $video->id;
}

/**
 * Video add handler function
 *
 * @since BuddyBoss 1.2.0
 *
 * @param array  $videos
 * @param string $privacy
 * @param string $content
 * @param int    $group_id
 * @param int    $album_id
 *
 * @return mixed|void
 */
function bp_video_add_handler( $videos = array(), $privacy = 'public', $content = '', $group_id = false, $album_id = false ) {
	global $bp_video_upload_count, $bp_video_upload_activity_content;
	$video_ids = array();

	$privacy = in_array( $privacy, array_keys( bp_video_get_visibility_levels() ) ) ? $privacy : 'public';

	if ( ! empty( $videos ) && is_array( $videos ) ) {

		// update count of video for later use.
		$bp_video_upload_count = count( $videos );

		// update the content of videos for later use.
		$bp_video_upload_activity_content = $content;

		// save  video.
		foreach ( $videos as $video ) {

			// Update video if existing
			if ( ! empty( $video['video_id'] ) ) {
				$bp_video = new BP_Video( $video['video_id'] );

				if ( ! empty( $bp_video->id ) ) {
					$video_id = bp_video_add(
						array(
							'id'            => $bp_video->id,
							'blog_id'       => $bp_video->blog_id,
							'attachment_id' => $bp_video->attachment_id,
							'user_id'       => $bp_video->user_id,
							'title'         => $bp_video->title,
							'album_id'      => ! empty( $video['album_id'] ) ? $video['album_id'] : $album_id,
							'group_id'      => ! empty( $video['group_id'] ) ? $video['group_id'] : $group_id,
							'activity_id'   => $bp_video->activity_id,
							'privacy'       => $bp_video->privacy,
							'menu_order'    => ! empty( $video['menu_order'] ) ? $video['menu_order'] : false,
							'date_created'  => $bp_video->date_created,
						)
					);
				}
			} else {

				$video_id = bp_video_add(
					array(
						'attachment_id' => $video['id'],
						'title'         => $video['name'],
						'album_id'      => ! empty( $video['album_id'] ) ? $video['album_id'] : $album_id,
						'group_id'      => ! empty( $video['group_id'] ) ? $video['group_id'] : $group_id,
						'menu_order'    => ! empty( $video['menu_order'] ) ? $video['menu_order'] : false,
						'privacy'       => ! empty( $video['privacy'] ) && in_array( $video['privacy'], array_merge( array_keys( bp_video_get_visibility_levels() ), array( 'message' ) ) ) ? $video['privacy'] : $privacy,
					)
				);
			}

			if ( $video_id ) {
				$video_ids[] = $video_id;
			}
		}
	}

	/**
	 * Fires at the end of the execution of adding saving a video item, before returning the new video items in ajax response.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param array $video_ids Video IDs.
	 * @param array $videos Array of video from POST object or in function parameter.
	 */
	return apply_filters( 'bp_video_add_handler', $video_ids, (array) $videos );
}

/**
 * Delete video.
 *
 * @since BuddyBoss 1.6.0
 * @since BuddyBoss 1.2.0
 *
 * @param array|string $args To delete specific video items, use
 *                           $args = array( 'id' => $ids ); Otherwise, to use
 *                           filters for item deletion, the argument format is
 *                           the same as BP_Video::get().
 *                           See that method for a description.
 * @param bool         $from Context of deletion from. ex. attachment, activity etc.
 *
 * @return bool|int The ID of the video on success. False on error.
 */
function bp_video_delete( $args = '', $from = false ) {

	// Pass one or more the of following variables to delete by those variables.
	$args = bp_parse_args(
		$args,
		array(
			'id'            => false,
			'blog_id'       => false,
			'attachment_id' => false,
			'user_id'       => false,
			'title'         => false,
			'album_id'      => false,
			'activity_id'   => false,
			'group_id'      => false,
			'privacy'       => false,
			'date_created'  => false,
		)
	);

	/**
	 * Fires before an video item proceeds to be deleted.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param array $args Array of arguments to be used with the video deletion.
	 */
	do_action( 'bp_before_video_delete', $args );

	$video_ids_deleted = BP_Video::delete( $args, $from );
	if ( empty( $video_ids_deleted ) ) {
		return false;
	}

	/**
	 * Fires after the video item has been deleted.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param array $args Array of arguments used with the video deletion.
	 */
	do_action( 'bp_video_delete', $args );

	/**
	 * Fires after the video item has been deleted.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param array $video_ids_deleted Array of affected video item IDs.
	 */
	do_action( 'bp_video_deleted_videos', $video_ids_deleted );

	return true;
}

/**
 * Completely remove a user's video data.
 *
 * @since BuddyBoss 1.2.0
 *
 * @param int $user_id ID of the user whose video is being deleted.
 * @return bool
 */
function bp_video_remove_all_user_data( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		return false;
	}

	// Clear the user's albums from the sitewide stream and clear their video tables.
	bp_video_album_delete( array( 'user_id' => $user_id ) );

	// Clear the user's video from the sitewide stream and clear their video tables.
	bp_video_delete( array( 'user_id' => $user_id ) );

	/**
	 * Fires after the removal of all of a user's video data.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param int $user_id ID of the user being deleted.
	 */
	do_action( 'bp_video_remove_all_user_data', $user_id );
}
add_action( 'wpmu_delete_user', 'bp_video_remove_all_user_data' );
add_action( 'delete_user', 'bp_video_remove_all_user_data' );

/**
 * Get video visibility levels out of the $bp global.
 *
 * @since BuddyBoss 1.2.3
 *
 * @return array
 */
function bp_video_get_visibility_levels() {

	/**
	 * Filters the video visibility levels out of the $bp global.
	 *
	 * @since BuddyBoss 1.2.3
	 *
	 * @param array $visibility_levels Array of visibility levels.
	 */
	return apply_filters( 'bp_video_get_visibility_levels', buddypress()->video->visibility_levels );
}

/**
 * Return the video activity.
 *
 * @param $activity_id
 * @since BuddyBoss 1.6.0
 *
 * @global object $video_template {@link BP_Video_Template}
 *
 * @return object|boolean The video activity object or false.
 */
function bp_video_get_video_activity( $activity_id ) {

	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	$result = bp_activity_get(
		array(
			'in' => $activity_id,
		)
	);

	if ( empty( $result['activities'][0] ) ) {
		return false;
	}

	/**
	 * Filters the video activity object being displayed.
	 *
	 * @since BuddyBoss 1.6.0
	 *
	 * @param object $activity The video activity.
	 */
	return apply_filters( 'bp_video_get_video_activity', $result['activities'][0] );
}

/**
 * Get the video count of a user.
 *
 * @since BuddyBoss 1.6.0
 *
 * @return int video count of the user.
 */
function bp_video_get_total_video_count() {

	add_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_personal_scope', 20 );
	bp_has_video( bp_ajax_querystring( 'video' ) );
	$count = $GLOBALS['video_template']->total_video_count;
	remove_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_personal_scope', 20 );

	/**
	 * Filters the total video count for a given user.
	 *
	 * @since BuddyBoss 1.6.0
	 *
	 * @param int $count Total video count for a given user.
	 */
	return apply_filters( 'bp_video_get_total_video_count', (int) $count );
}

/**
 * Get the groups video count of a given user.
 *
 * @return int video count of the user.
 * @since BuddyBoss .3.6
 */
function bp_video_get_user_total_group_video_count() {

	add_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_groups_scope', 20 );
	bp_has_video( bp_ajax_querystring( 'groups' ) );
	$count = $GLOBALS['video_template']->total_video_count;
	remove_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_groups_scope', 20 );

	/**
	 * Filters the total groups video count for a given user.
	 *
	 * @param int $count Total video count for a given user.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	return apply_filters( 'bp_video_get_user_total_group_video_count', (int) $count );
}

/**
 * Get the video count of a given group.
 *
 * @since BuddyBoss 1.6.0
 *
 * @param int $group_id ID of the group whose video are being counted.
 * @return int video count of the group.
 */
function bp_video_get_total_group_video_count( $group_id = 0 ) {
	if ( empty( $group_id ) && bp_get_current_group_id() ) {
		$group_id = bp_get_current_group_id();
	}

	$count = wp_cache_get( 'bp_total_video_for_group_' . $group_id, 'bp' );

	if ( false === $count ) {
		$count = BP_Video::total_group_video_count( $group_id );
		wp_cache_set( 'bp_total_video_for_group_' . $group_id, $count, 'bp' );
	}

	/**
	 * Filters the total video count for a given group.
	 *
	 * @since BuddyBoss 1.6.0
	 *
	 * @param int $count Total video count for a given group.
	 */
	return apply_filters( 'bp_video_get_total_group_video_count', (int) $count );
}

/**
 * Get the album count of a given group.
 *
 * @since BuddyBoss 1.2.0
 *
 * @param int $group_id ID of the group whose album are being counted.
 * @return int album count of the group.
 */
function bp_video_get_total_group_album_count( $group_id = 0 ) {
	if ( empty( $group_id ) && bp_get_current_group_id() ) {
		$group_id = bp_get_current_group_id();
	}

	$count = wp_cache_get( 'bp_total_album_for_group_' . $group_id, 'bp' );

	if ( false === $count ) {
		$count = BP_Video_Album::total_group_album_count( $group_id );
		wp_cache_set( 'bp_total_album_for_group_' . $group_id, $count, 'bp' );
	}

	/**
	 * Filters the total album count for a given group.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param int $count Total album count for a given group.
	 */
	return apply_filters( 'bp_video_get_total_group_album_count', (int) $count );
}

/**
 * Return the total video count in your BP instance.
 *
 * @since BuddyBoss 1.6.0
 *
 * @return int Video count.
 */
function bp_get_total_video_count() {

	add_filter( 'bp_ajax_querystring', 'bp_video_object_results_video_all_scope', 20 );
	bp_has_video( bp_ajax_querystring( 'video' ) );
	remove_filter( 'bp_ajax_querystring', 'bp_video_object_results_video_all_scope', 20 );
	$count = $GLOBALS['video_template']->total_video_count;

	/**
	 * Filters the total number of video.
	 *
	 * @since BuddyBoss 1.6.0
	 *
	 * @param int $count Total number of video.
	 */
	return apply_filters( 'bp_get_total_video_count', (int) $count );
}

/**
 * Video results all scope.
 *
 * @since BuddyBoss 1.1.9
 */
function bp_video_object_results_video_all_scope( $querystring ) {
	$querystring = wp_parse_args( $querystring );

	$querystring['scope'] = 'all';

	$querystring['page']        = 1;
	$querystring['per_page']    = 1;
	$querystring['user_id']     = 0;
	$querystring['count_total'] = true;
	return http_build_query( $querystring );
}


/**
 * Object template results video personal scope.
 *
 * @since BuddyBoss 1.6.0
 */
function bp_video_object_template_results_video_personal_scope( $querystring ) {
	$querystring = wp_parse_args( $querystring );

	$querystring['scope']       = 'personal';
	$querystring['page']        = 1;
	$querystring['per_page']    = '1';
	$querystring['user_id']     = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	$querystring['count_total'] = true;

	return http_build_query( $querystring );
}

/**
 * Object template results video groups scope.
 *
 * @since BuddyBoss 1.6.0
 */
function bp_video_object_template_results_video_groups_scope( $querystring ) {
	$querystring = wp_parse_args( $querystring );

	$querystring['scope']       = 'groups';
	$querystring['page']        = 1;
	$querystring['per_page']    = 1;
	$querystring['user_id']     = false;
	$querystring['count_total'] = true;

	return http_build_query( $querystring );
}

// ******************** Albums *********************/
/**
 * Retrieve an album or albums.
 *
 * The bp_video_album_get() function shares all arguments with BP_Video_Album::get().
 * The following is a list of bp_video_album_get() parameters that have different
 * default values from BP_Video_Album::get() (value in parentheses is
 * the default for the bp_video_album_get()).
 *   - 'per_page' (false)
 *
 * @since BuddyBoss 1.6.0
 *
 * @see BP_Video_Album::get() For more information on accepted arguments
 *      and the format of the returned value.
 *
 * @param array|string $args See BP_Video_Album::get() for description.
 * @return array $activity See BP_Video_Album::get() for description.
 */
function bp_video_album_get( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'max'          => false,                    // Maximum number of results to return.
			'fields'       => 'all',
			'page'         => 1,                        // Page 1 without a per_page will result in no pagination.
			'per_page'     => false,                    // results per page
			'sort'         => 'DESC',                   // sort ASC or DESC

			'search_terms' => false,           // Pass search terms as a string
			'exclude'      => false,           // Comma-separated list of activity IDs to exclude.
		// want to limit the query.
			'user_id'      => false,
			'group_id'     => false,
			'privacy'      => false,                    // privacy of album
			'count_total'  => false,
		),
		'album_get'
	);

	$album = BP_Video_Album::get(
		array(
			'page'         => $r['page'],
			'per_page'     => $r['per_page'],
			'user_id'      => $r['user_id'],
			'group_id'     => $r['group_id'],
			'privacy'      => $r['privacy'],
			'max'          => $r['max'],
			'sort'         => $r['sort'],
			'search_terms' => $r['search_terms'],
			'exclude'      => $r['exclude'],
			'count_total'  => $r['count_total'],
			'fields'       => $r['fields'],
		)
	);

	/**
	 * Filters the requested album item(s).
	 *
	 * @since BuddyBoss 1.6.0
	 *
	 * @param BP_Video  $album Requested video object.
	 * @param array     $r     Arguments used for the album query.
	 */
	return apply_filters_ref_array( 'bp_video_album_get', array( &$album, &$r ) );
}

/**
 * Fetch specific albums.
 *
 * @since BuddyBoss 1.6.0
 *
 * @see BP_Video_Album::get() For more information on accepted arguments.
 *
 * @param array|string $args {
 *     All arguments and defaults are shared with BP_Video_Album::get(),
 *     except for the following:
 *     @type string|int|array Single album ID, comma-separated list of IDs,
 *                            or array of IDs.
 * }
 * @return array $albums See BP_Video_Album::get() for description.
 */
function bp_video_album_get_specific( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'album_ids'         => false,      // A single album id or array of IDs.
			'max'               => false,      // Maximum number of results to return.
			'page'              => 1,          // Page 1 without a per_page will result in no pagination.
			'per_page'          => false,      // Results per page.
			'sort'              => 'DESC',     // Sort ASC or DESC
			'update_meta_cache' => true,
			'count_total'       => false,
		),
		'video_get_specific'
	);

	$get_args = array(
		'in'          => $r['album_ids'],
		'max'         => $r['max'],
		'page'        => $r['page'],
		'per_page'    => $r['per_page'],
		'sort'        => $r['sort'],
		'count_total' => $r['count_total'],
	);

	/**
	 * Filters the requested specific album item.
	 *
	 * @since BuddyBoss
	 *
	 * @param BP_Video      $album    Requested video object.
	 * @param array         $args     Original passed in arguments.
	 * @param array         $get_args Constructed arguments used with request.
	 */
	return apply_filters( 'bp_video_album_get_specific', BP_Video_Album::get( $get_args ), $args, $get_args );
}

/**
 * Add album item.
 *
 * @since BuddyBoss 1.6.0
 *
 * @param array|string $args {
 *     An array of arguments.
 *     @type int|bool $id                Pass an activity ID to update an existing item, or
 *                                       false to create a new item. Default: false.
 *     @type int|bool $user_id           Optional. The ID of the user associated with the album
 *                                       item. May be set to false or 0 if the item is not related
 *                                       to any user. Default: the ID of the currently logged-in user.
 *     @type int      $group_id          Optional. The ID of the associated group.
 *     @type string   $title             The title of album.
 *     @type string   $privacy           The privacy of album.
 *     @type string   $date_created      Optional. The GMT time, in Y-m-d h:i:s format, when
 *                                       the item was recorded. Defaults to the current time.
 *     @type string   $error_type        Optional. Error type. Either 'bool' or 'wp_error'. Default: 'bool'.
 * }
 * @return WP_Error|bool|int The ID of the album on success. False on error.
 */
function bp_video_album_add( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'id'           => false,                  // Pass an existing album ID to update an existing entry.
			'user_id'      => bp_loggedin_user_id(),                     // User ID
			'group_id'     => false,                  // attachment id.
			'title'        => '',                     // title of album being added.
			'privacy'      => 'public',                  // Optional: privacy of the video e.g. public.
			'date_created' => bp_core_current_time(), // The GMT time that this video was recorded
			'error_type'   => 'bool',
		),
		'album_add'
	);

	// Setup video to be added.
	$album               = new BP_Video_Album( $r['id'] );
	$album->user_id      = (int) $r['user_id'];
	$album->group_id     = (int) $r['group_id'];
	$album->title        = $r['title'];
	$album->privacy      = $r['privacy'];
	$album->date_created = $r['date_created'];
	$album->error_type   = $r['error_type'];

	if ( ! empty( $album->group_id ) ) {
		$album->privacy = 'grouponly';
	}

	$save = $album->save();

	if ( 'wp_error' === $r['error_type'] && is_wp_error( $save ) ) {
		return $save;
	} elseif ( 'bool' === $r['error_type'] && false === $save ) {
		return false;
	}

	/**
	 * Fires at the end of the execution of adding a new album item, before returning the new album item ID.
	 *
	 * @since BuddyBoss 1.6.0
	 *
	 * @param object $album Album object.
	 */
	do_action( 'bp_video_album_add', $album );

	return $album->id;
}

/**
 * Delete album item.
 *
 * @since BuddyBoss 1.6.0
 *
 * @param array|string $args To delete specific album items, use
 *                           $args = array( 'id' => $ids ); Otherwise, to use
 *                           filters for item deletion, the argument format is
 *                           the same as BP_Video_Album::get().
 *                           See that method for a description.
 *
 * @return bool True on Success. False on error.
 */
function bp_video_album_delete( $args ) {

	// Pass one or more the of following variables to delete by those variables.
	$args = bp_parse_args(
		$args,
		array(
			'id'           => false,
			'user_id'      => false,
			'group_id'     => false,
			'date_created' => false,
		)
	);

	/**
	 * Fires before an album item proceeds to be deleted.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param array $args Array of arguments to be used with the album deletion.
	 */
	do_action( 'bp_before_video_album_delete', $args );

	$album_ids_deleted = BP_Video_Album::delete( $args );
	if ( empty( $album_ids_deleted ) ) {
		return false;
	}

	/**
	 * Fires after the album item has been deleted.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param array $args Array of arguments used with the album deletion.
	 */
	do_action( 'bp_video_album_delete', $args );

	/**
	 * Fires after the album item has been deleted.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param array $album_ids_deleted Array of affected album item IDs.
	 */
	do_action( 'bp_video_albums_deleted_albums', $album_ids_deleted );

	return true;
}

/**
 * Fetch a single album object.
 *
 * When calling up a album object, you should always use this function instead
 * of instantiating BP_Video_Album directly, so that you will inherit cache
 * support and pass through the albums_get_album filter.
 *
 * @since BuddyBoss 1.6.0
 *
 * @param int $album_id ID of the album.
 * @return BP_Video_Album $album The album object.
 */
function albums_get_video_album( $album_id ) {

	$album = new BP_Video_Album( $album_id );

	/**
	 * Filters a single album object.
	 *
	 * @since BuddyBoss 1.6.0
	 *
	 * @param BP_Video_Album $album Single album object.
	 */
	return apply_filters( 'albums_get_video_album', $album );
}

/**
 * Check album access for current user or guest
 *
 * @since BuddyBoss 1.6.0
 * @param $album_id
 *
 * @return bool
 */
function albums_check_video_album_access( $album_id ) {

	$album = albums_get_video_album( $album_id );

	if ( ! empty( $album->group_id ) ) {
		return false;
	}

	if ( ! empty( $album->privacy ) ) {

		if ( 'public' == $album->privacy ) {
			return true;
		}

		if ( 'loggedin' == $album->privacy && is_user_logged_in() ) {
			return true;
		}

		if ( bp_is_active( 'friends' ) && is_user_logged_in() && 'friends' == $album->privacy && friends_check_friendship( get_current_user_id(), $album->user_id ) ) {
			return true;
		}

		if ( bp_is_my_profile() && $album->user_id == bp_loggedin_user_id() && 'onlyme' == $album->privacy ) {
			return true;
		}
	}

	return false;
}

/**
 * Delete orphaned attachments uploaded
 *
 * @since BuddyBoss 1.6.0
 */
function bp_video_delete_orphaned_attachments() {

	$orphaned_attachment_args = array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'fields'         => 'ids',
		'posts_per_page' => - 1,
		'meta_query'     => array(
			array(
				'key'     => 'bp_video_saved',
				'value'   => '0',
				'compare' => '=',
			),
		),
	);

	$orphaned_attachment_query = new WP_Query( $orphaned_attachment_args );

	if ( $orphaned_attachment_query->post_count > 0 ) {
		foreach ( $orphaned_attachment_query->posts as $a_id ) {
			wp_delete_attachment( $a_id, true );
		}
	}
}

/**
 * Download an image from the specified URL and attach it to a post.
 *
 * @since BuddyBoss 1.6.0
 *
 * @param string $file The URL of the image to download
 *
 * @return int|void
 */
function bp_video_sideload_attachment( $file ) {
	if ( empty( $file ) ) {
		return;
	}

	// Set variables for storage, fix file filename for query strings.
	preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png|svg|bmp|mp4)\b/i', $file, $matches );
	$file_array = array();

	if ( empty( $matches ) ) {
		return;
	}

	$file_array['name'] = basename( $matches[0] );

	// Download file to temp location.
	$file = preg_replace( '/^:*?\/\//', $protocol = strtolower( substr( $_SERVER['SERVER_PROTOCOL'], 0, strpos( $_SERVER['SERVER_PROTOCOL'], '/' ) ) ) . '://', $file );

	if ( ! function_exists( 'download_url' ) ) {
		require_once ABSPATH . 'wp-admin' . '/includes/image.php';
		require_once ABSPATH . 'wp-admin' . '/includes/file.php';
		require_once ABSPATH . 'wp-admin' . '/includes/media.php';
	}
	$file_array['tmp_name'] = download_url( $file );

	// If error storing temporarily, return the error.
	if ( is_wp_error( $file_array['tmp_name'] ) ) {
		return;
	}

	// Do the validation and storage stuff.
	$id = bp_video_handle_sideload( $file_array );

	// If error storing permanently, unlink.
	if ( is_wp_error( $id ) ) {
		return;
	}

	return $id;
}

/**
 * This handles a sideloaded file in the same way as an uploaded file is handled by {@link video_handle_upload()}
 *
 * @since BuddyBoss 1.6.0
 *
 * @param array $file_array Array similar to a {@link $_FILES} upload array
 * @param array $post_data  allows you to overwrite some of the attachment
 *
 * @return int|object The ID of the attachment or a WP_Error on failure
 */
function bp_video_handle_sideload( $file_array, $post_data = array() ) {

	$overrides = array( 'test_form' => false );

	$time = current_time( 'mysql' );
	if ( $post = get_post() ) {
		if ( substr( $post->post_date, 0, 4 ) > 0 ) {
			$time = $post->post_date;
		}
	}

	$file = wp_handle_sideload( $file_array, $overrides, $time );
	if ( isset( $file['error'] ) ) {
		return new WP_Error( 'upload_error', $file['error'] );
	}

	$url     = $file['url'];
	$type    = $file['type'];
	$file    = $file['file'];
	$title   = preg_replace( '/\.[^.]+$/', '', basename( $file ) );
	$content = '';

	// Use image exif/iptc data for title and caption defaults if possible.
	if ( $image_meta = @wp_read_image_metadata( $file ) ) {
		if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
			$title = $image_meta['title'];
		}
		if ( trim( $image_meta['caption'] ) ) {
			$content = $image_meta['caption'];
		}
	}

	if ( isset( $desc ) ) {
		$title = $desc;
	}

	// Construct the attachment array.
	$attachment = array_merge(
		array(
			'post_mime_type' => $type,
			'guid'           => $url,
			'post_title'     => $title,
			'post_content'   => $content,
		),
		$post_data
	);

	// This should never be set as it would then overwrite an existing attachment.
	if ( isset( $attachment['ID'] ) ) {
		unset( $attachment['ID'] );
	}

	// Save the attachment metadata
	$id = wp_insert_attachment( $attachment, $file );

	if ( ! is_wp_error( $id ) ) {
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
	}

	return $id;
}

/**
 * Import BuddyBoss Media plugin db tables into Media Component
 *
 * @since BuddyBoss 1.6.0
 */
function bp_video_import_buddyboss_video_tables() {
	global $wpdb;
	global $bp;

	$buddyboss_video_table        = $bp->table_prefix . 'buddyboss_media';
	$buddyboss_video_albums_table = $bp->table_prefix . 'buddyboss_video_albums';

	$total_video  = $wpdb->get_var( "SELECT COUNT(*) FROM {$buddyboss_video_table}" );
	$total_albums = $wpdb->get_var( "SELECT COUNT(*) FROM {$buddyboss_video_albums_table}" );

	update_option( 'bp_video_import_total_video', $total_video );
	update_option( 'bp_video_import_total_albums', $total_albums );

	$albums_done      = get_option( 'bp_video_import_albums_done', 0 );
	$run_albums_query = $albums_done != $total_albums;

	if ( $run_albums_query ) {

		$albums = $wpdb->get_results( "SELECT * FROM {$buddyboss_video_albums_table} LIMIT 100 OFFSET {$albums_done}" );

		$album_ids = get_option( 'bp_video_import_albums_ids', array() );

		if ( ! empty( $albums ) ) {

			$albums_count = (int) $albums_done;
			foreach ( $albums as $album ) {

				$user_id      = ! empty( $album->user_id ) ? $album->user_id : false;
				$group_id     = ! empty( $album->group_id ) ? $album->group_id : false;
				$title        = ! empty( $album->title ) ? $album->title : '';
				$date_created = ! empty( $album->date_created ) ? $album->date_created : bp_core_current_time();

				$album_args = array(
					'user_id'      => $user_id,
					'title'        => $title,
					'group_id'     => $group_id,
					'date_created' => $date_created,
				);

				if ( ! empty( $album->privacy ) ) {
					if ( 'private' == $album->privacy ) {
						$privacy = 'onlyme';
					} elseif ( 'members' == $album->privacy ) {
						$privacy = 'loggedin';
					} else {
						$privacy = $album->privacy;
					}
				} else {
					$privacy = 'public';
				}

				$album_args['privacy'] = $privacy;

				$album_id = bp_video_album_add( $album_args );

				if ( ! empty( $album_id ) ) {
					$album_ids[ $album_id ] = $album->id;
				}

				$albums_count ++;

				update_option( 'bp_video_import_albums_done', $albums_count );
			}
		}
		update_option( 'bp_video_import_albums_ids', $album_ids );
	}

	if ( ! $run_albums_query ) {

		$video_done         = get_option( 'bp_video_import_video_done', 0 );
		$album_ids          = get_option( 'bp_video_import_albums_ids', array() );
		$imported_video_ids = get_option( 'bp_video_import_video_ids', array() );

		$videos = $wpdb->get_results( "SELECT * FROM {$buddyboss_video_table} LIMIT 100 OFFSET {$video_done}" );

		if ( ! empty( $videos ) ) {

			$activity_ids = array();
			$video_done   = (int) $video_done;

			foreach ( $videos as $video ) {

				$attachment_id = ! empty( $video->video_id ) ? $video->video_id : false;
				$user_id       = ! empty( $video->video_author ) ? $video->video_author : false;
				$title         = ! empty( $video->video_title ) ? $video->video_title : '';
				$activity_id   = ! empty( $video->activity_id ) ? $video->activity_id : false;

				if ( ! empty( $activity_id ) ) {
					$activity_ids[ $activity_id ] = array();
				}

				$video_args = array(
					'attachment_id' => $attachment_id,
					'user_id'       => $user_id,
					'title'         => $title,
				);

				if ( ! empty( $video->album_id ) && ! empty( $album_ids ) ) {
					$album_id_key = array_search( $video->album_id, $album_ids );

					if ( ! empty( $album_id_key ) ) {
						$album_id = $album_id_key;

						$video_args['album_id'] = $album_id;
					}
				}

				if ( ! empty( $video->upload_date ) && '0000-00-00 00:00:00' != $video->upload_date ) {
					$date_created = $video->upload_date;
				} elseif ( ! empty( $video->upload_date ) && '0000-00-00 00:00:00' == $video->upload_date && ! empty( $attachment_id ) ) {
					$date_created = get_the_date( $attachment_id );
				} else {
					$date_created = bp_core_current_time();
				}

				$video_args['date_created'] = $date_created;

				if ( ! empty( $video->privacy ) ) {
					if ( 'private' == $video->privacy ) {
						$privacy = 'onlyme';
					} elseif ( 'members' == $video->privacy ) {
						$privacy = 'loggedin';
					} else {
						$privacy = $video->privacy;
					}
				} else {
					$privacy = 'public';
				}

				$video_args['privacy'] = $privacy;

				if ( bp_is_active( 'activity' ) ) {

					$activity_args = array(
						'user_id'       => $user_id,
						'recorded_time' => $date_created,
						'hide_sitewide' => true,
						'privacy'       => 'video',
						'type'          => 'activity_update',
						'component'     => buddypress()->activity->id,
					);

					if ( ! empty( $activity_id ) ) {

						$activity = new BP_Activity_Activity( $activity_id );

						if ( ! empty( $activity->id ) ) {

							$activity_args['recorded_time'] = $activity->date_recorded;

							if ( 'groups' == $activity->component ) {
								$video_args['group_id'] = $activity->item_id;

								$activity_args['component'] = buddypress()->groups->id;
								$activity_args['item_id']   = $activity->item_id;
							}
						}
					}

					// make an activity for the video
					$sub_activity_id = bp_activity_add( $activity_args );

					if ( $sub_activity_id ) {
						// update activity meta
						bp_activity_update_meta( $sub_activity_id, 'bp_video_activity', '1' );

						$video_args['activity_id'] = $sub_activity_id;
					}
				}

				$video_id = bp_video_add( $video_args );

				if ( ! empty( $video_id ) && ! empty( $video_args['activity_id'] ) ) {
					update_post_meta( $attachment_id, 'bp_video_activity_id', $video_args['activity_id'] );

					if ( ! empty( $activity_id ) ) {
						update_post_meta( $attachment_id, 'bp_video_parent_activity_id', $activity_id );

						if ( isset( $activity_ids[ $activity_id ] ) ) {
							$activity_ids[ $activity_id ][] = $video_id;
						}
					}

					$imported_video_ids[] = $video_id;
				}

				$video_done ++;

				update_option( 'bp_video_import_video_done', $video_done );
			}
			update_option( 'bp_video_import_video_ids', $imported_video_ids );

			if ( ! empty( $activity_ids ) && bp_is_active( 'activity' ) ) {
				foreach ( $activity_ids as $id => $activity_video ) {
					if ( ! empty( $activity_video ) ) {
						$video_ids = implode( ',', $activity_video );
						bp_activity_update_meta( $id, 'bp_video_ids', $video_ids );
					}
				}
			}
		}
	}
}

/**
 * Import forums video from BuddyBoss Media Plugin
 *
 * @since BuddyBoss 1.0.5
 */
function bp_video_import_buddyboss_forum_video() {

	$forums_done = get_option( 'bp_video_import_forums_done', 0 );

	$forums_video_query = new WP_Query(
		array(
			'post_type'      => bbp_get_forum_post_type(),
			'fields'         => 'ids',
			'posts_per_page' => 100,
			'offset'         => $forums_done,
			'meta_query'     => array(
				array(
					'key'     => 'bbm_bbpress_attachment_ids',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	if ( ! empty( $forums_video_query->found_posts ) ) {

		$imported_forum_video_ids = get_option( 'bp_video_import_forum_video_ids', array() );

		update_option( 'bp_video_import_forums_total', $forums_video_query->found_posts );

		if ( ! empty( $forums_video_query->posts ) ) {

			$forums_done = (int) $forums_done;
			foreach ( $forums_video_query->posts as $post_id ) {
				$attachment_ids = get_post_meta( $post_id, 'bbm_bbpress_attachment_ids', true );

				// save activity id if it is saved in forums and enabled in platform settings
				$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

				$video_ids = array();
				if ( ! empty( $attachment_ids ) ) {
					foreach ( $attachment_ids as $attachment_id ) {

						$title = get_the_title( $attachment_id );

						$video_id = bp_video_add(
							array(
								'attachment_id' => $attachment_id,
								'title'         => $title,
								'album_id'      => false,
								'group_id'      => false,
								'error_type'    => 'bool',
							)
						);

						if ( $video_id ) {
							$video_ids[] = $video_id;

							// save video is saved in attachment
							update_post_meta( $attachment_id, 'bp_video_saved', true );

							$imported_forum_video_ids[] = $video_id;
						}
					}

					update_option( 'bp_video_import_forum_video_ids', $imported_forum_video_ids );

					$video_ids = implode( ',', $video_ids );

					// Save all attachment ids in forums post meta
					update_post_meta( $post_id, 'bp_video_ids', $video_ids );

					// save video meta for activity
					if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
						bp_activity_update_meta( $main_activity_id, 'bp_video_ids', $video_ids );
					}
				}

				$forums_done ++;
				update_option( 'bp_video_import_forums_done', $forums_done );
			}
		}
	}
	wp_reset_postdata();
}

/**
 * Import topic video from BuddyBoss Media Plugin
 *
 * @since BuddyBoss 1.0.5
 */
function bp_video_import_buddyboss_topic_video() {

	$topics_done = get_option( 'bp_video_import_topics_done', 0 );

	$topics_video_query = new WP_Query(
		array(
			'post_type'      => bbp_get_topic_post_type(),
			'fields'         => 'ids',
			'posts_per_page' => 100,
			'offset'         => $topics_done,
			'meta_query'     => array(
				array(
					'key'     => 'bbm_bbpress_attachment_ids',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	if ( ! empty( $topics_video_query->found_posts ) ) {

		$imported_topic_video_ids = get_option( 'bp_video_import_topic_video_ids', array() );

		update_option( 'bp_video_import_topics_total', $topics_video_query->found_posts );

		if ( ! empty( $topics_video_query->posts ) ) {

			$topics_done = (int) $topics_done;
			foreach ( $topics_video_query->posts as $post_id ) {
				$attachment_ids = get_post_meta( $post_id, 'bbm_bbpress_attachment_ids', true );

				// save activity id if it is saved in forums and enabled in platform settings
				$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

				$video_ids = array();
				if ( ! empty( $attachment_ids ) ) {
					foreach ( $attachment_ids as $attachment_id ) {

						$title = get_the_title( $attachment_id );

						$video_id = bp_video_add(
							array(
								'attachment_id' => $attachment_id,
								'title'         => $title,
								'album_id'      => false,
								'group_id'      => false,
								'error_type'    => 'bool',
							)
						);

						if ( $video_id ) {
							$video_ids[] = $video_id;

							// save video is saved in attachment
							update_post_meta( $attachment_id, 'bp_video_saved', true );

							$imported_topic_video_ids[] = $video_id;
						}
					}

					update_option( 'bp_video_import_topic_video_ids', $imported_topic_video_ids );

					$video_ids = implode( ',', $video_ids );

					// Save all attachment ids in forums post meta
					update_post_meta( $post_id, 'bp_video_ids', $video_ids );

					// save video meta for activity
					if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
						bp_activity_update_meta( $main_activity_id, 'bp_video_ids', $video_ids );
					}
				}

				$topics_done ++;
				update_option( 'bp_video_import_topics_done', $topics_done );
			}
		}
	}
	wp_reset_postdata();
}

/**
 * Import reply video from BuddyBoss Media Plugin
 *
 * @since BuddyBoss 1.0.5
 */
function bp_video_import_buddyboss_reply_video() {

	$replies_done = get_option( 'bp_video_import_replies_done', 0 );

	$replies_video_query = new WP_Query(
		array(
			'post_type'      => bbp_get_reply_post_type(),
			'fields'         => 'ids',
			'posts_per_page' => 100,
			'offset'         => $replies_done,
			'meta_query'     => array(
				array(
					'key'     => 'bbm_bbpress_attachment_ids',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	if ( ! empty( $replies_video_query->found_posts ) ) {

		$imported_reply_video_ids = get_option( 'bp_video_import_reply_video_ids', array() );

		update_option( 'bp_video_import_replies_total', $replies_video_query->found_posts );

		if ( ! empty( $replies_video_query->posts ) ) {

			$replies_done = (int) $replies_done;
			foreach ( $replies_video_query->posts as $post_id ) {
				$attachment_ids = get_post_meta( $post_id, 'bbm_bbpress_attachment_ids', true );

				// save activity id if it is saved in forums and enabled in platform settings
				$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

				$video_ids = array();
				if ( ! empty( $attachment_ids ) ) {
					foreach ( $attachment_ids as $attachment_id ) {

						$title = get_the_title( $attachment_id );

						$video_id = bp_video_add(
							array(
								'attachment_id' => $attachment_id,
								'title'         => $title,
								'album_id'      => false,
								'group_id'      => false,
								'error_type'    => 'bool',
							)
						);

						if ( $video_id ) {
							$video_ids[] = $video_id;

							// save video is saved in attachment
							update_post_meta( $attachment_id, 'bp_video_saved', true );

							$imported_reply_video_ids[] = $video_id;
						}
					}

					update_option( 'bp_video_import_reply_video_ids', $imported_reply_video_ids );

					$video_ids = implode( ',', $video_ids );

					// Save all attachment ids in forums post meta
					update_post_meta( $post_id, 'bp_video_ids', $video_ids );

					// save video meta for activity
					if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
						bp_activity_update_meta( $main_activity_id, 'bp_video_ids', $video_ids );
					}
				}

				$replies_done ++;
				update_option( 'bp_video_import_replies_done', $replies_done );
			}
		}
	}
	wp_reset_postdata();
}

/**
 * Reset all video albums related data in tables
 *
 * @since BuddyBoss 1.0.5
 */
function bp_video_import_reset_video_albums() {
	global $wpdb;
	global $bp;

	$bp_video_table        = $bp->table_prefix . 'bp_media';
	$bp_video_albums_table = $bp->table_prefix . 'bp_video_albums';

	$album_ids = get_option( 'bp_video_import_albums_ids', array() );

	remove_action( 'bp_activity_after_delete', 'bp_video_delete_activity_video' );

	if ( ! empty( $album_ids ) ) {
		foreach ( $album_ids as $new_album_id => $old_album_id ) {

			if ( empty( $new_album_id ) ) {
				continue;
			}

			$album_obj = new BP_Video_Album( $new_album_id );

			if ( ! empty( $album_obj->id ) ) {
				$video_ids = BP_Video::get_album_video_ids( $album_obj->id );
				if ( ! empty( $video_ids ) ) {
					foreach ( $video_ids as $video ) {
						$video_obj = new BP_Video( $video );

						if ( ! empty( $video_obj->activity_id ) && bp_is_active( 'activity' ) ) {
							$activity = new BP_Activity_Activity( (int) $video_obj->activity_id );

							/** This action is documented in bp-activity/bp-activity-actions.php */
							do_action( 'bp_activity_before_action_delete_activity', $activity->id, $activity->user_id );

							// Deleting an activity comment.
							if ( 'activity_comment' == $activity->type ) {
								if ( bp_activity_delete_comment( $activity->item_id, $activity->id ) ) {
									/** This action is documented in bp-activity/bp-activity-actions.php */
									do_action( 'bp_activity_action_delete_activity', $activity->id, $activity->user_id );
								}

								// Deleting an activity.
							} else {
								if ( bp_activity_delete(
									array(
										'id'      => $activity->id,
										'user_id' => $activity->user_id,
									)
								) ) {
									/** This action is documented in bp-activity/bp-activity-actions.php */
									do_action( 'bp_activity_action_delete_activity', $activity->id, $activity->user_id );
								}
							}
						}
					}

					$video_ids = implode( ',', $video_ids );
					if ( ! empty( $video_ids ) ) {
						$wpdb->query( "DELETE FROM {$bp_video_table} WHERE id IN ({$video_ids});" );
					}
				}
			}

			$wpdb->query( "DELETE FROM {$bp_video_albums_table} WHERE id = {$album_obj->id};" );
		}
	}

	add_action( 'bp_activity_after_delete', 'bp_video_delete_activity_video' );

	update_option( 'bp_video_import_status', 'reset_video' );
}

/**
 * Reset all video related data in tables
 *
 * @since BuddyBoss 1.0.5
 */
function bp_video_import_reset_video() {
	global $wpdb;
	global $bp;

	$bp_video_table = $bp->table_prefix . 'bp_media';

	$videos = get_option( 'bp_video_import_video_ids', array() );

	remove_action( 'bp_activity_after_delete', 'bp_video_delete_activity_video' );

	if ( ! empty( $videos ) ) {
		$video_ids = array();
		foreach ( $videos as $video ) {

			if ( empty( $video ) ) {
				continue;
			}

			$video_obj = new BP_Video( $video );

			if ( ! empty( $video_obj->activity_id ) && bp_is_active( 'activity' ) ) {
				$activity = new BP_Activity_Activity( (int) $video_obj->activity_id );

				/** This action is documented in bp-activity/bp-activity-actions.php */
				do_action( 'bp_activity_before_action_delete_activity', $activity->id, $activity->user_id );

				// Deleting an activity comment.
				if ( 'activity_comment' == $activity->type ) {
					if ( bp_activity_delete_comment( $activity->item_id, $activity->id ) ) {
						/** This action is documented in bp-activity/bp-activity-actions.php */
						do_action( 'bp_activity_action_delete_activity', $activity->id, $activity->user_id );
					}

					// Deleting an activity.
				} else {
					if ( bp_activity_delete(
						array(
							'id'      => $activity->id,
							'user_id' => $activity->user_id,
						)
					) ) {
						/** This action is documented in bp-activity/bp-activity-actions.php */
						do_action( 'bp_activity_action_delete_activity', $activity->id, $activity->user_id );
					}
				}
			}

			if ( ! empty( $video_obj->id ) ) {
				$video_ids[] = $video_obj->id;
			}
		}

		$video_ids = implode( ',', $video_ids );
		if ( ! empty( $video_ids ) ) {
			$wpdb->query( "DELETE FROM {$bp_video_table} WHERE id IN ({$video_ids})" );
		}
	}

	add_action( 'bp_activity_after_delete', 'bp_video_delete_activity_video' );

	update_option( 'bp_video_import_status', 'reset_forum' );
}

/**
 * Reset all video related data in forums
 *
 * @since BuddyBoss 1.0.10
 */
function bp_video_import_reset_forum_video() {
	global $wpdb;
	global $bp;

	$bp_video_table = $bp->table_prefix . 'bp_media';

	$videos = get_option( 'bp_video_import_forum_video_ids', array() );

	if ( ! empty( $videos ) ) {

		$videos = implode( ',', $videos );
		$wpdb->query( "DELETE FROM {$bp_video_table} WHERE id IN ({$videos})" );
	}

	update_option( 'bp_video_import_status', 'reset_topic' );
}

/**
 * Reset all video related data in topics
 *
 * @since BuddyBoss 1.0.10
 */
function bp_video_import_reset_topic_video() {
	global $wpdb;
	global $bp;

	$bp_video_table = $bp->table_prefix . 'bp_media';

	$videos = get_option( 'bp_video_import_topic_video_ids', array() );

	if ( ! empty( $videos ) ) {

		$videos = implode( ',', $videos );
		$wpdb->query( "DELETE FROM {$bp_video_table} WHERE id IN ({$videos})" );
	}

	update_option( 'bp_video_import_status', 'reset_reply' );
}

/**
 * Reset all video related data in topics
 *
 * @since BuddyBoss 1.0.10
 */
function bp_video_import_reset_reply_video() {
	global $wpdb;
	global $bp;

	$bp_video_table = $bp->table_prefix . 'bp_media';

	$videos = get_option( 'bp_video_import_reply_video_ids', array() );

	if ( ! empty( $videos ) ) {

		$videos = implode( ',', $videos );
		$wpdb->query( "DELETE FROM {$bp_video_table} WHERE id IN ({$videos})" );
	}

	update_option( 'bp_video_import_status', 'reset_options' );
}

/**
 * Reset all options related to video import
 *
 * @since BuddyBoss 1.0.5
 */
function bp_video_import_reset_options() {
	update_option( 'bp_video_import_total_video', 0 );
	update_option( 'bp_video_import_total_albums', 0 );
	update_option( 'bp_video_import_albums_done', 0 );
	update_option( 'bp_video_import_video_done', 0 );
	update_option( 'bp_video_import_forums_done', 0 );
	update_option( 'bp_video_import_topics_done', 0 );
	update_option( 'bp_video_import_replies_done', 0 );
	update_option( 'bp_video_import_forums_total', 0 );
	update_option( 'bp_video_import_topics_total', 0 );
	update_option( 'bp_video_import_replies_total', 0 );
	delete_option( 'bp_video_import_reply_video_ids' );
	delete_option( 'bp_video_import_topic_video_ids' );
	delete_option( 'bp_video_import_forum_video_ids' );
	delete_option( 'bp_video_import_video_ids' );
	delete_option( 'bp_video_import_albums_ids' );

	update_option( 'bp_video_import_status', 'start' );
}

/**
 * AJAX function for video import status
 *
 * @since BuddyBoss 1.6.0
 */
function bp_video_import_status_request() {

	$import_status = get_option( 'bp_video_import_status' );

	if ( 'reset_albums' == $import_status ) {
		bp_video_import_reset_video_albums();
	} elseif ( 'reset_video' == $import_status ) {
		bp_video_import_reset_video();
	} elseif ( 'reset_forum' == $import_status ) {
		bp_video_import_reset_forum_video();
	} elseif ( 'reset_topic' == $import_status ) {
		bp_video_import_reset_topic_video();
	} elseif ( 'reset_reply' == $import_status ) {
		bp_video_import_reset_reply_video();
	} elseif ( 'reset_options' == $import_status ) {
		bp_video_import_reset_options();
	} elseif ( 'start' == $import_status ) {

		update_option( 'bp_video_import_status', 'importing' );

		bp_video_import_buddyboss_video_tables();
		bp_video_import_buddyboss_forum_video();
		bp_video_import_buddyboss_topic_video();
		bp_video_import_buddyboss_reply_video();
	} else {

		$total_video   = get_option( 'bp_video_import_total_video', 0 );
		$total_albums  = get_option( 'bp_video_import_total_albums', 0 );
		$albums_done   = get_option( 'bp_video_import_albums_done', 0 );
		$video_done    = get_option( 'bp_video_import_video_done', 0 );
		$forums_done   = get_option( 'bp_video_import_forums_done', 0 );
		$forums_total  = get_option( 'bp_video_import_forums_total', 0 );
		$topics_done   = get_option( 'bp_video_import_topics_done', 0 );
		$topics_total  = get_option( 'bp_video_import_topics_total', 0 );
		$replies_done  = get_option( 'bp_video_import_replies_done', 0 );
		$replies_total = get_option( 'bp_video_import_replies_total', 0 );

		$importing = false;
		if ( $albums_done != $total_albums || $video_done != $total_video ) {
			bp_video_import_buddyboss_video_tables();
			$importing = true;
		}

		if ( bp_is_active( 'forums' ) ) {
			if ( $forums_done != $forums_total ) {
				bp_video_import_buddyboss_forum_video();
				$importing = true;
			}

			if ( $topics_done != $topics_total ) {
				bp_video_import_buddyboss_topic_video();
				$importing = true;
			}

			if ( $replies_done != $replies_total ) {
				bp_video_import_buddyboss_reply_video();
				$importing = true;
			}
		}

		if ( ! $importing ) {
			update_option( 'bp_video_import_status', 'done' );
		} else {
			update_option( 'bp_video_import_status', 'importing' );
		}
	}

	$import_status = get_option( 'bp_video_import_status' );
	$total_video   = get_option( 'bp_video_import_total_video', 0 );
	$total_albums  = get_option( 'bp_video_import_total_albums', 0 );
	$albums_done   = get_option( 'bp_video_import_albums_done', 0 );
	$video_done    = get_option( 'bp_video_import_video_done', 0 );
	$forums_done   = get_option( 'bp_video_import_forums_done', 0 );
	$forums_total  = get_option( 'bp_video_import_forums_total', 0 );
	$topics_done   = get_option( 'bp_video_import_topics_done', 0 );
	$topics_total  = get_option( 'bp_video_import_topics_total', 0 );
	$replies_done  = get_option( 'bp_video_import_replies_done', 0 );
	$replies_total = get_option( 'bp_video_import_replies_total', 0 );

	wp_send_json_success(
		array(
			'total_video'   => $total_video,
			'total_albums'  => $total_albums,
			'albums_done'   => $albums_done,
			'video_done'    => $video_done,
			'forums_done'   => $forums_done,
			'topics_done'   => $topics_done,
			'replies_done'  => $replies_done,
			'forums_total'  => $forums_total,
			'topics_total'  => $topics_total,
			'replies_total' => $replies_total,
			'import_status' => $import_status,
			'success_msg'   => __( 'BuddyBoss Video data update is complete! Any previously uploaded member videos should display in their profiles now.', 'buddyboss' ),
			'error_msg'     => __( 'BuddyBoss Video data update is failing!', 'buddyboss' ),
		)
	);
}

/**
 * Function to add the content on top of video listing
 *
 * @since BuddyBoss 1.2.5
 */
function bp_video_directory_page_content() {

	$page_ids = bp_core_get_directory_page_ids();

	if ( ! empty( $page_ids['video'] ) ) {
		$video_page_content = get_post_field( 'post_content', $page_ids['video'] );
		echo apply_filters( 'the_content', $video_page_content );
	}
}

add_action( 'bp_before_directory_video', 'bp_video_directory_page_content' );

/**
 * Get video id for the attachment.
 *
 * @since BuddyBoss 1.3.5
 * @param integer $attachment_id
 *
 * @return array|bool
 */
function bp_get_attachment_video_id( $attachment_id = 0 ) {
	global $bp, $wpdb;

	if ( ! $attachment_id ) {
		return false;
	}

	$attachment_video_id = (int) $wpdb->get_var( "SELECT DISTINCT m.id FROM {$bp->video->table_name} m WHERE m.attachment_id = {$attachment_id}" );

	return $attachment_video_id;
}

/**
 * Get list of privacy based on user and group.
 *
 * @param int    $user_id  User ID.
 * @param int    $group_id Group ID.
 * @param string $scope    Scope query parameter.
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.0
 */
function bp_video_query_privacy( $user_id = 0, $group_id = 0, $scope = '' ) {

	$privacy = array( 'public' );

	if ( is_user_logged_in() ) {
		// User filtering.
		$user_id = (int) (
		empty( $user_id )
			? ( bp_displayed_user_id() ? bp_displayed_user_id() : false )
			: $user_id
		);

		$privacy[] = 'loggedin';

		if ( bp_is_my_profile() || $user_id === bp_loggedin_user_id() ) {
			$privacy[] = 'onlyme';

			if ( bp_is_active( 'friends' ) ) {
				$privacy[] = 'friends';
			}
		}

		if ( ! in_array( 'friends', $privacy ) && bp_is_active( 'friends' ) ) {

			// get the login user id.
			$current_user_id = bp_loggedin_user_id();

			// check if the login user is friends of the display user
			$is_friend = friends_check_friendship( $current_user_id, $user_id );

			/**
			 * check if the login user is friends of the display user
			 * OR check if the login user and the display user is the same
			 */
			if ( $is_friend ) {
				$privacy[] = 'friends';
			}
		}
	}

	if (
		bp_is_group()
		|| ( bp_is_active( 'groups' ) && ! empty( $group_id ) )
		|| ( ! empty( $scope ) && 'groups' === $scope )
	) {
		$privacy = array( 'grouponly' );
	}

	return apply_filters( 'bp_video_query_privacy', $privacy, $user_id, $group_id, $scope );
}

/**
 * Update activity video privacy based on activity.
 *
 * @param int    $activity_id Activity ID.
 * @param string $privacy     Privacy
 *
 * @since BuddyBoss 1.4.0
 */
function bp_video_update_activity_privacy( $activity_id = 0, $privacy = '' ) {
	global $wpdb, $bp;

	if ( empty( $activity_id ) || empty( $privacy ) ) {
		return;
	}

	// Update privacy for the video which are uploaded in activity.
	$video_ids = bp_activity_get_meta( $activity_id, 'bp_video_ids', true );
	if ( ! empty( $video_ids ) ) {
		$video_ids = explode( ',', $video_ids );
		if ( ! empty( $video_ids ) ) {
			foreach ( $video_ids as $id ) {
				$video = new BP_Video( $id );
				if ( ! empty( $video->id ) ) {
					$video->privacy = $privacy;
					$video->save();
				}
			}
		}
	}
}

/**
 * Get default scope for the video.
 *
 * @since BuddyBoss 1.4.4
 *
 * @param string $scope Default scope.
 *
 * @return string
 */
function bp_video_default_scope( $scope ) {

	$new_scope = array();

	$allowed_scopes = array( 'public' );
	if ( is_user_logged_in() && bp_is_active( 'friends' ) && bp_is_profile_video_support_enabled() ) {
		$allowed_scopes[] = 'friends';
	}

	if ( bp_is_active( 'groups' ) && bp_is_group_video_support_enabled() ) {
		$allowed_scopes[] = 'groups';
	}

	if ( is_user_logged_in() && bp_is_profile_video_support_enabled() ) {
		$allowed_scopes[] = 'personal';
	}

	if ( ( 'all' === $scope || empty( $scope ) ) && bp_is_video_directory() ) {

		$new_scope[] = 'public';

		if ( bp_is_active( 'friends' ) && bp_is_profile_video_support_enabled() ) {
			$new_scope[] = 'friends';
		}

		if ( bp_is_active( 'groups' ) && bp_is_group_video_support_enabled() ) {
			$new_scope[] = 'groups';
		}

		if ( is_user_logged_in() && bp_is_profile_video_support_enabled() ) {
			$new_scope[] = 'personal';
		}
	} elseif ( bp_is_user_video() && ( 'all' === $scope || empty( $scope ) ) && bp_is_profile_video_support_enabled() ) {
		$new_scope[] = 'personal';
	}

	if ( empty( $new_scope ) ) {
		$new_scope = (array) $scope;
	}

	// Remove duplicate scope if added.
	$new_scope = array_unique( $new_scope );

	// Remove all unwanted scope.
	$new_scope = array_intersect( $allowed_scopes, $new_scope );

	/**
	 * Filter to update default scope.
	 *
	 * @since BuddyBoss 1.4.4
	 */
	$new_scope = apply_filters( 'bp_video_default_scope', $new_scope );

	return implode( ',', $new_scope );

}

/**
 * Check user have a permission to manage the video.
 *
 * @param int $video_id
 * @param int $user_id
 * @param int $thread_id
 * @param int $message_id
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.4
 */
function bp_video_user_can_manage_video( $video_id = 0, $user_id = 0 ) {

	$can_manage   = false;
	$can_view     = false;
	$can_download = false;
	$can_add      = false;
	$video        = new BP_Video( $video_id );
	$data         = array();

	switch ( $video->privacy ) {

		case 'public':
			if ( $video->user_id === $user_id ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
				$can_add      = true;
			} elseif ( bp_current_user_can( 'bp_moderate' ) ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
				$can_add      = false;
			} else {
				$can_manage   = false;
				$can_view     = true;
				$can_download = true;
			}
			break;

		case 'grouponly':
			if ( bp_is_active( 'groups' ) ) {

				$manage   = groups_can_user_manage_video( $user_id, $video->group_id );
				$status   = bp_group_get_video_status( $video->group_id );
				$is_admin = groups_is_user_admin( $user_id, $video->group_id );
				$is_mod   = groups_is_user_mod( $user_id, $video->group_id );

				if ( $manage ) {
					if ( $video->user_id === $user_id ) {
						$can_manage = true;
						$can_add    = true;
					} elseif ( bp_current_user_can( 'bp_moderate' ) ) {
						$can_manage = true;
						$can_add    = false;
					} elseif ( 'members' == $status && ( $is_mod || $is_admin ) ) {
						$can_manage = true;
						$can_add    = false;
					} elseif ( 'mods' == $status && ( $is_mod || $is_admin ) ) {
						$can_manage = true;
						$can_add    = false;
					} elseif ( 'admins' == $status && $is_admin ) {
						$can_manage = true;
						$can_add    = false;
					}
					$can_view     = true;
					$can_download = true;
				} else {
					$the_group = groups_get_group( (int) $video->group_id );
					if ( $the_group->id > 0 && $the_group->user_has_access ) {
						$can_view     = true;
						$can_download = true;
					}
				}
			}

			break;

		case 'loggedin':
			if ( ! is_user_logged_in() ) {
				$can_manage   = false;
				$can_view     = false;
				$can_download = false;
				$can_add      = false;
			} elseif ( $video->user_id === $user_id ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
				$can_add      = true;
			} elseif ( bp_current_user_can( 'bp_moderate' ) ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
				$can_add      = false;
			} elseif ( bp_loggedin_user_id() === $user_id ) {
				$can_manage   = false;
				$can_view     = true;
				$can_download = true;
			}
			break;

		case 'friends':
			$is_friend = ( bp_is_active( 'friends' ) ) ? friends_check_friendship( $video->user_id, $user_id ) : false;
			if ( $video->user_id === $user_id ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
				$can_add      = true;
			} elseif ( bp_current_user_can( 'bp_moderate' ) ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
				$can_add      = false;
			} elseif ( $is_friend ) {
				$can_manage   = false;
				$can_view     = true;
				$can_download = true;
			}
			break;

		case 'forums':
			$args = array(
				'user_id'         => $user_id,
				'forum_id'        => bp_video_get_forum_id( $video_id ),
				'check_ancestors' => false,
			);

			$has_access = bbp_user_can_view_forum( $args );
			if ( $video->user_id === $user_id ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
				$can_add      = true;
			} elseif ( bp_current_user_can( 'bp_moderate' ) ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
				$can_add      = false;
			} elseif ( $has_access ) {
				if ( bp_current_user_can( 'bp_moderate' ) ) {
					$can_manage = true;
				}
				$can_view     = true;
				$can_download = true;
			}
			break;

		case 'message':
			$thread_id  = bp_video_get_thread_id( $video_id );
			$has_access = messages_check_thread_access( $thread_id, $user_id );
			if ( ! is_user_logged_in() ) {
				$can_manage   = false;
				$can_view     = false;
				$can_download = false;
			} elseif ( ! $thread_id ) {
				$can_manage   = false;
				$can_view     = false;
				$can_download = false;
			} elseif ( $video->user_id === $user_id ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
				$can_add      = true;
			} elseif ( bp_current_user_can( 'bp_moderate' ) ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
				$can_add      = false;
			} elseif ( $has_access > 0 ) {
				$can_manage   = false;
				$can_view     = true;
				$can_download = true;
			}
			break;

		case 'onlyme':
			if ( $video->user_id === $user_id ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
				$can_add      = true;
			} elseif ( bp_current_user_can( 'bp_moderate' ) ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
				$can_add      = false;
			}
			break;

	}

	$data['can_manage']   = $can_manage;
	$data['can_view']     = $can_view;
	$data['can_download'] = $can_download;
	$data['can_add']      = $can_add;

	return apply_filters( 'bp_video_user_can_manage_video', $data, $video_id, $user_id );
}

function bp_video_get_thread_id( $video_id ) {

	$thread_id = 0;

	if ( bp_is_active( 'messages' ) ) {
		$meta = array(
			array(
				'key'     => 'bp_video_ids',
				'value'   => $video_id,
				'compare' => 'LIKE',
			),
		);

		// Check if there is already previously individual group thread created.
		if ( bp_has_message_threads( array( 'meta_query' => $meta ) ) ) { // phpcs:ignore
			while ( bp_message_threads() ) {
				bp_message_thread();
				$thread_id = bp_get_message_thread_id();
				if ( $thread_id ) {
					break;
				}
			}
		}
	}
	return apply_filters( 'bp_video_get_thread_id', $thread_id, $video_id );

}

/**
 * Return download link of the video.
 *
 * @param $attachment_id
 * @param $video_id
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.4
 */
function bp_video_download_link( $attachment_id, $video_id ) {

	if ( empty( $attachment_id ) ) {
		return;
	}

	$link = site_url() . '/?attachment_id=' . $attachment_id . '&media_type=video&download_video_file=1' . '&media_file=' . $video_id;

	return apply_filters( 'bp_video_download_link', $link, $attachment_id );

}

/**
 * Check if user have a access to download the file. If not redirect to homepage.
 *
 * @since BuddyBoss 1.4.4
 */
function bp_video_download_url_file() {
	if ( isset( $_GET['attachment_id'] ) && isset( $_GET['download_video_file'] ) && isset( $_GET['video_file'] ) && isset( $_GET['video_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'folder' !== $_GET['video_type'] ) {
			$video_privacy = bp_video_user_can_manage_video( $_GET['video_file'], bp_loggedin_user_id() ); // phpcs:ignore WordPress.Security.NonceVerification

			$can_download_btn = ( true === (bool) $video_privacy['can_download'] ) ? true : false;
		}
		if ( $can_download_btn ) {
			bp_video_download_file( $_GET['attachment_id'], $_GET['video_type'] ); // phpcs:ignore WordPress.Security.NonceVerification
		} else {
			wp_safe_redirect( site_url() );
		}
	}
}

/**
 * Filter headers for IE to fix issues over SSL.
 *
 * IE bug prevents download via SSL when Cache Control and Pragma no-cache headers set.
 *
 * @param array $headers HTTP headers.
 * @return array
 */
function bp_video_ie_nocache_headers_fix( $headers ) {
	if ( is_ssl() && ! empty( $GLOBALS['is_IE'] ) ) {
		$headers['Cache-Control'] = 'private';
		unset( $headers['Pragma'] );
	}
	return $headers;
}

function bp_video_get_forum_id( $video_id ) {

	$forum_id           = 0;
	$forums_video_query = new WP_Query(
		array(
			'post_type'      => bbp_get_forum_post_type(),
			'fields'         => 'ids',
			'posts_per_page' => - 1,
			'meta_query'     => array(
				array(
					'key'     => 'bp_video_ids',
					'value'   => $video_id,
					'compare' => 'LIKE',
				),
			),
		)
	);

	if ( ! empty( $forums_video_query->found_posts ) && ! empty( $forums_video_query->posts ) ) {

		foreach ( $forums_video_query->posts as $post_id ) {
			$video_ids = get_post_meta( $post_id, 'bp_video_ids', true );

			if ( ! empty( $video_ids ) ) {
				$video_ids = explode( ',', $video_ids );
				if ( in_array( $video_id, $video_ids ) ) {
					$forum_id = $post_id;
					break;
				}
			}
		}
	}
	wp_reset_postdata();

	if ( ! $forum_id ) {
		$topics_video_query = new WP_Query(
			array(
				'post_type'      => bbp_get_topic_post_type(),
				'fields'         => 'ids',
				'posts_per_page' => - 1,
				'meta_query'     => array(
					array(
						'key'     => 'bp_video_ids',
						'value'   => $video_id,
						'compare' => 'LIKE',
					),
				),
			)
		);

		if ( ! empty( $topics_video_query->found_posts ) && ! empty( $topics_video_query->posts ) ) {

			foreach ( $topics_video_query->posts as $post_id ) {
				$video_ids = get_post_meta( $post_id, 'bp_video_ids', true );

				if ( ! empty( $video_ids ) ) {
					$video_ids = explode( ',', $video_ids );
					if ( in_array( $video_id, $video_ids ) ) {
						$forum_id = bbp_get_topic_forum_id( $post_id );
						break;
					}
				}
			}
		}
		wp_reset_postdata();
	}

	if ( ! $forum_id ) {
		$reply_video_query = new WP_Query(
			array(
				'post_type'      => bbp_get_reply_post_type(),
				'fields'         => 'ids',
				'posts_per_page' => - 1,
				'meta_query'     => array(
					array(
						'key'     => 'bp_video_ids',
						'value'   => $video_id,
						'compare' => 'LIKE',
					),
				),
			)
		);

		if ( ! empty( $reply_video_query->found_posts ) && ! empty( $reply_video_query->posts ) ) {

			foreach ( $reply_video_query->posts as $post_id ) {
				$video_ids = get_post_meta( $post_id, 'bp_video_ids', true );

				if ( ! empty( $video_ids ) ) {
					$video_ids = explode( ',', $video_ids );
					foreach ( $video_ids as $video_id ) {
						if ( in_array( $video_id, $video_ids ) ) {
							$forum_id = bbp_get_reply_forum_id( $post_id );
							break;
						}
					}
				}
			}
		}
		wp_reset_postdata();
	}

	return apply_filters( 'bp_video_get_forum_id', $forum_id, $video_id );

}

/**
 * Check user have a permission to manage the album.
 *
 * @param int $album_id
 * @param int $user_id
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.7
 */
function bp_video_user_can_manage_album( $album_id = 0, $user_id = 0 ) {

	$can_manage   = false;
	$can_view     = false;
	$can_download = false;
	$can_add      = false;
	$album        = new BP_Video_Album( $album_id );
	$data         = array();

	switch ( $album->privacy ) {

		case 'public':
			if ( $album->user_id === $user_id ) {
				$can_add      = true;
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
			} elseif ( bp_current_user_can( 'bp_moderate' ) ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
				$can_add      = false;
			} else {
				$can_manage   = false;
				$can_view     = true;
				$can_download = true;
			}
			break;

		case 'grouponly':
			if ( bp_is_active( 'groups' ) ) {

				$manage   = groups_can_user_manage_video( $user_id, $album->group_id );
				$status   = bp_group_get_video_status( $album->group_id );
				$is_admin = groups_is_user_admin( $user_id, $album->group_id );
				$is_mod   = groups_is_user_mod( $user_id, $album->group_id );
				if ( $manage ) {
					if ( $album->user_id === $user_id ) {
						$can_manage = true;
						$can_add    = true;
					} elseif ( bp_current_user_can( 'bp_moderate' ) ) {
						$can_manage = true;
						$can_add    = false;
					} elseif ( 'members' == $status && ( $is_mod || $is_admin ) ) {
						$can_manage = true;
						$can_add    = false;
					} elseif ( 'mods' == $status && ( $is_mod || $is_admin ) ) {
						$can_manage = true;
						$can_add    = false;
					} elseif ( 'admins' == $status && $is_admin ) {
						$can_manage = true;
						$can_add    = false;
					}
					$can_view     = true;
					$can_download = true;
				} else {
					$the_group = groups_get_group( absint( $album->group_id ) );
					if ( $the_group->id > 0 && $the_group->user_has_access ) {
						$can_view     = true;
						$can_download = true;
					}
				}
			}

			break;

		case 'loggedin':
			if ( ! is_user_logged_in() ) {
				$can_manage   = false;
				$can_view     = false;
				$can_download = false;
				$can_add      = false;
			} elseif ( $album->user_id === $user_id ) {
				$can_manage   = true;
				$can_add      = true;
				$can_view     = true;
				$can_download = true;
			} elseif ( bp_current_user_can( 'bp_moderate' ) ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
				$can_add      = false;
			} elseif ( bp_loggedin_user_id() === $user_id ) {
				$can_manage   = false;
				$can_view     = true;
				$can_download = true;
			}
			break;

		case 'friends':
			$is_friend = ( bp_is_active( 'friends' ) ) ? friends_check_friendship( $album->user_id, $user_id ) : false;
			if ( $album->user_id === $user_id ) {
				$can_manage   = true;
				$can_add      = true;
				$can_view     = true;
				$can_download = true;
			} elseif ( bp_current_user_can( 'bp_moderate' ) ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
				$can_add      = false;
			} elseif ( $is_friend ) {
				$can_manage   = false;
				$can_view     = true;
				$can_download = true;
			}
			break;

		case 'onlyme':
			if ( $album->user_id === $user_id ) {
				$can_manage   = true;
				$can_add      = true;
				$can_view     = true;
				$can_download = true;
			} elseif ( bp_current_user_can( 'bp_moderate' ) ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
				$can_add      = false;
			}
			break;

	}

	$data['can_manage']   = $can_manage;
	$data['can_view']     = $can_view;
	$data['can_download'] = $can_download;
	$data['can_add']      = $can_add;

	return apply_filters( 'bp_video_user_can_manage_album', $data, $album_id, $user_id );
}

/**
 * Return the extension of the attachment.
 *
 * @param $attachment_id
 *
 * @return mixed|string
 * @since BuddyBoss 1.4.0
 */
function bp_video_mime_type( $attachment_id ) {

	$type = get_post_mime_type( $attachment_id );

	return $type;

}

/**
 * Return the icon based on the extension.
 *
 * @param $extension
 * @param $attachment_id
 *
 * @return mixed|void
 * @since BuddyBoss 1.6.0
 */
function bp_video_svg_icon( $extension, $attachment_id = 0, $type = 'font' ) {

	if ( $attachment_id > 0 && '' !== $extension ) {
		$mime_type = bp_video_mime_type( $attachment_id );
		$existing_list = bp_video_extensions_list();
		$new_extension = '.' . $extension;
		$result_array = bp_video_multi_array_search( $existing_list, array(
			'extension' => $new_extension,
			'mime_type' => $mime_type
		) );
		if ( $result_array && isset( $result_array[0] ) && ! empty( $result_array[0] ) ) {
			$icon = $existing_list[ $result_array[0] ]['icon'];
			if ( '' !== $icon ) {

				// added svg icon support.
				if ( 'svg' === $type ) {
					$svg_icons = array_column( bp_video_svg_icon_list(), 'svg', 'icon' );
					$icon      = $svg_icons[ $icon ];
				}

				return apply_filters( 'bp_video_svg_icon', $icon, $extension );
			}
		}
	}

	$svg = array(
		'font' => '',
		'svg'  => ''
	);

	switch ( $extension ) {
		case '7z':
			$svg = array(
				'font' => 'bb-icon-file-7z',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-7z</title><path d="M13.728 0.032c0.992 0 1.984 0.384 2.72 1.056l0.16 0.16 6.272 6.496c0.672 0.672 1.056 1.6 1.12 2.528v17.76c0 2.144-1.696 3.904-3.808 4h-16.192c-2.144 0-3.904-1.664-4-3.808v-24.192c0-2.144 1.696-3.904 3.808-4h9.92zM13.728 2.048h-9.728c-1.056 0-1.92 0.8-1.984 1.824v24.16c0 1.056 0.8 1.92 1.824 2.016h16.16c1.056 0 1.92-0.832 1.984-1.856l0.032-0.16v-17.504c0-0.48-0.16-0.896-0.448-1.28l-0.128-0.128-6.272-6.464c-0.384-0.416-0.896-0.608-1.44-0.608zM16.992 14.528c0.576 0 1.024 0.448 1.024 0.992 0 0.512-0.416 0.96-0.896 0.992l-0.128 0.032v0.512l-1.984 0.992v1.184l1.984-0.992v-1.184h0.032v1.184h-0.032v0.832l-1.984 0.992v1.152l1.984-0.992v-1.152h0.032v1.152h-0.032v0.832l-1.984 0.992v1.184l1.984-0.992v-1.184h0.032v1.184h-0.032v2.304h0.128c0.48 0.064 0.896 0.48 0.896 0.992s-0.416 0.928-0.896 0.992h-1.984c-0.544 0-0.992-0.448-0.992-0.992 0-0.512 0.384-0.928 0.864-0.992v-0.704l-2.592 0.672c-0.096 0.032-0.192 0.032-0.256 0-0.064 0.032-0.096 0.032-0.16 0.032h-5.984c-0.576 0-1.024-0.448-1.024-1.024v-5.984c0-0.544 0.448-0.992 1.024-0.992h5.984c0.096 0 0.16 0 0.256 0.032h0.16l2.592 0.704v-0.768c-0.48-0.064-0.864-0.48-0.864-0.992s0.384-0.928 0.864-0.992h1.984zM12 17.536h-5.984v5.984h5.984v-5.984zM10.496 21.344c0.288 0 0.512 0.224 0.512 0.512s-0.224 0.512-0.512 0.512h-3.008c-0.256 0-0.48-0.224-0.48-0.512s0.224-0.512 0.48-0.512h3.008zM10.496 18.752c0.288 0 0.512 0.224 0.512 0.512 0 0.256-0.224 0.48-0.512 0.48h-3.008c-0.256 0-0.48-0.224-0.48-0.48 0-0.288 0.224-0.512 0.48-0.512h3.008z"></path></svg>'
			);
			break;
		case 'abw':
			$svg = array(
				'font' => 'bb-icon-file-abw',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-abw</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM15.2 15.008c1.28 0 2.304 1.024 2.304 2.304v0 7.392c0 1.248-1.024 2.304-2.304 2.304v0h-7.392c-1.28 0-2.304-1.056-2.304-2.304v0-7.392c0-1.28 1.024-2.304 2.304-2.304v0h7.392zM15.2 15.936h-7.392c-0.768 0-1.376 0.608-1.376 1.376v0 7.392c0 0.736 0.608 1.376 1.376 1.376v0h7.392c0.768 0 1.376-0.64 1.376-1.376v0-7.392c0-0.768-0.608-1.376-1.376-1.376v0zM9.056 22.176c0.544 0 0.864 0.256 0.864 0.704 0 0.288-0.192 0.576-0.48 0.608v0 0.032c0.352 0.032 0.64 0.32 0.64 0.672 0 0.48-0.384 0.8-0.992 0.8v0h-1.248v-2.816h1.216zM11.040 22.176l0.448 1.984h0.032l0.512-1.984h0.512l0.512 1.984h0.032l0.416-1.984h0.64l-0.768 2.816h-0.544l-0.544-1.856h-0.032l-0.512 1.856h-0.576l-0.736-2.816h0.608zM8.928 23.744h-0.512v0.8h0.544c0.32 0 0.512-0.128 0.512-0.416 0-0.256-0.192-0.384-0.544-0.384v0zM8.928 22.624h-0.512v0.736h0.448c0.32 0 0.48-0.128 0.48-0.384 0-0.224-0.16-0.352-0.416-0.352v0zM15.008 19.488c0.256 0 0.48 0.224 0.48 0.512 0 0.256-0.16 0.448-0.384 0.48l-0.096 0.032h-3.008c-0.288 0-0.512-0.224-0.512-0.512 0-0.256 0.192-0.448 0.416-0.48l0.096-0.032h3.008zM9.312 17.184l0.96 2.816h-0.64l-0.192-0.672h-0.992l-0.224 0.672h-0.608l0.992-2.816h0.704zM8.96 17.76h-0.032l-0.352 1.12h0.736l-0.352-1.12zM15.008 17.504c0.256 0 0.48 0.224 0.48 0.48s-0.16 0.48-0.384 0.512h-3.104c-0.288 0-0.512-0.224-0.512-0.512 0-0.224 0.192-0.448 0.416-0.48h3.104z"></path></svg>'
			);
			break;
		case 'ace':
			$svg = array(
				'font' => 'bb-icon-file-ace',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-ace</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM8.384 23.936v0.576h-1.472v0.768h1.408v0.544h-1.408v0.768h1.472v0.608h-2.144v-3.264h2.144zM17.536 25.216c0.256 0 0.48 0.224 0.48 0.48v0.928c0 0.256-0.224 0.448-0.48 0.448h-6.464c-0.256 0-0.448-0.192-0.448-0.448v-0.928c0-0.256 0.192-0.48 0.448-0.48h6.464zM17.536 21.536c0.256 0 0.48 0.192 0.48 0.448v0.928c0 0.256-0.224 0.48-0.48 0.48h-6.464c-0.256 0-0.448-0.224-0.448-0.48v-0.928c0-0.256 0.192-0.448 0.448-0.448h6.464zM7.616 19.872c0.768 0 1.376 0.512 1.408 1.216v0h-0.672c-0.064-0.352-0.352-0.608-0.736-0.608-0.512 0-0.832 0.416-0.832 1.12 0 0.672 0.32 1.088 0.832 1.088 0.384 0 0.672-0.224 0.736-0.576v0h0.672c-0.064 0.704-0.64 1.184-1.408 1.184-0.928 0-1.536-0.64-1.536-1.696s0.576-1.728 1.536-1.728zM17.536 17.856c0.256 0 0.48 0.192 0.48 0.448v0.928c0 0.256-0.224 0.448-0.48 0.448h-6.464c-0.256 0-0.448-0.192-0.448-0.448v-0.928c0-0.256 0.192-0.448 0.448-0.448h6.464zM7.936 16l1.12 3.264h-0.736l-0.256-0.8h-1.12l-0.256 0.8h-0.672l1.12-3.264h0.8zM7.52 16.672h-0.032l-0.416 1.28h0.864l-0.416-1.28z"></path></svg>'
			);
			break;
		case 'ai':
			$svg = array(
				'font' => 'bb-icon-file-ai',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-ai</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.504 25.056c0.288 0 0.512 0.224 0.512 0.512v0.928c0 0.288-0.224 0.512-0.512 0.512h-11.008c-0.256 0-0.48-0.224-0.48-0.512v-0.928c0-0.288 0.224-0.512 0.48-0.512h11.008zM9.792 16l2.176 6.144h-1.44l-0.48-1.472h-2.208l-0.48 1.472h-1.344l2.208-6.144h1.568zM14.368 16v6.144h-1.312v-6.144h1.312zM8.992 17.28h-0.064l-0.8 2.4h1.664l-0.8-2.4z"></path></svg>'
			);
			break;
		case 'apk':
			$svg = array(
				'font' => 'bb-icon-file-apk',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-apk</title><path d="M13.728 0c1.088 0 2.144 0.448 2.88 1.216v0l6.272 6.496c0.736 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 2.016h-9.728c-1.088 0-1.984 0.864-1.984 1.984v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.192-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.608-1.44-0.608v0zM17.408 21.696v2.976c0 1.248-0.96 2.24-2.144 2.336h-6.56c-1.216 0-2.208-0.96-2.304-2.176v-3.136h11.008zM16.48 22.624h-9.152v2.048c0 0.672 0.48 1.248 1.12 1.376l0.128 0.032h6.528c0.704 0 1.312-0.576 1.376-1.28v-2.176zM11.904 15.008c1.568 0 3.008 0.672 4 1.728l1.312-1.568c0.16-0.192 0.448-0.224 0.64-0.064s0.224 0.448 0.064 0.672l-1.44 1.728c0.544 0.8 0.864 1.76 0.896 2.784l0.032 0.288v0.48h-11.008v-0.48c0-1.152 0.352-2.24 0.928-3.104l-1.216-1.728c-0.16-0.224-0.128-0.512 0.096-0.64 0.16-0.16 0.416-0.128 0.576 0.032l0.064 0.064 1.088 1.504c0.992-1.056 2.4-1.696 3.968-1.696zM11.904 15.936c-1.44 0-2.72 0.672-3.552 1.696-0.032 0.096-0.096 0.16-0.16 0.224 0 0 0 0 0 0-0.384 0.544-0.672 1.184-0.8 1.856l-0.032 0.256-0.032 0.16h9.12v-0.16c-0.096-0.704-0.352-1.376-0.736-1.952l-0.128-0.224-0.032-0.032c-0.032 0-0.032-0.032-0.064-0.064-0.736-0.96-1.856-1.632-3.136-1.76h-0.448zM14.24 18.24c0.256 0 0.48 0.224 0.48 0.48s-0.224 0.448-0.48 0.448c-0.256 0-0.448-0.192-0.448-0.448s0.192-0.48 0.448-0.48zM9.664 18.24c0.256 0 0.48 0.224 0.48 0.48s-0.224 0.448-0.48 0.448c-0.256 0-0.448-0.192-0.448-0.448s0.192-0.48 0.448-0.48z"></path></svg>
				'
			);
			break;
		case 'css':
			$svg = array(
				'font' => 'bb-icon-file-css',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-css</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM7.808 20v0.832h-0.192c-0.48 0-0.64 0.192-0.64 0.736v0 0.896c0 0.544-0.352 0.896-1.024 0.96v0 0.128c0.672 0.064 1.024 0.416 1.024 0.96v0 0.928c0 0.512 0.16 0.736 0.64 0.736v0h0.192v0.832h-0.288c-1.152 0-1.632-0.448-1.632-1.472v0-0.736c0-0.544-0.224-0.768-0.896-0.768v0-1.088c0.672 0 0.896-0.224 0.896-0.768v0-0.736c0-1.024 0.48-1.44 1.632-1.44v0h0.288zM16.16 20c1.12 0 1.6 0.416 1.6 1.44v0 0.736c0 0.544 0.256 0.768 0.896 0.768v0 1.088c-0.64 0-0.896 0.224-0.896 0.768v0 0.736c0 1.024-0.48 1.472-1.6 1.472v0h-0.288v-0.832h0.16c0.48 0 0.672-0.224 0.672-0.736v0-0.928c0-0.544 0.352-0.896 0.992-0.96v0-0.128c-0.64-0.064-0.992-0.416-0.992-0.96v0-0.896c0-0.544-0.192-0.736-0.672-0.736v0h-0.16v-0.832h0.288zM9.664 24.608c0.416 0 0.672 0.256 0.672 0.64s-0.256 0.672-0.672 0.672c-0.416 0-0.672-0.288-0.672-0.672s0.256-0.64 0.672-0.64zM11.84 24.608c0.416 0 0.672 0.256 0.672 0.64s-0.256 0.672-0.672 0.672c-0.416 0-0.672-0.288-0.672-0.672s0.256-0.64 0.672-0.64zM13.984 24.608c0.416 0 0.672 0.256 0.672 0.64s-0.256 0.672-0.672 0.672c-0.416 0-0.672-0.288-0.672-0.672s0.256-0.64 0.672-0.64z"></path></svg>
				'
			);
			break;
		case 'csv':
			$svg = array(
				'font' => 'bb-icon-file-csv',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-csv</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016zM13.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016zM9.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-3.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h3.008zM17.504 20.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016zM13.504 20.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016zM7.424 16.992l0.512 1.312h0.064l0.64-1.312h1.344l-1.216 2.432 1.248 2.56h-1.376l-0.64-1.28h-0.064l-0.64 1.28h-1.28l1.152-2.432-1.152-2.56h1.408zM17.504 16.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016zM13.504 16.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016z"></path></svg>
				'
			);
			break;
		case 'doc':
			$svg = array(
				'font' => 'bb-icon-file-doc',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-doc</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16 13.984c1.376 0 2.496 1.12 2.496 2.528v0 8c0 1.376-1.12 2.496-2.496 2.496v0h-8c-1.376 0-2.496-1.12-2.496-2.496v0-8c0-1.408 1.12-2.528 2.496-2.528v0h8zM16 15.008h-8c-0.832 0-1.504 0.672-1.504 1.504v0 8c0 0.8 0.672 1.472 1.504 1.472v0h8c0.832 0 1.504-0.672 1.504-1.472v0-8c0-0.832-0.672-1.504-1.504-1.504v0zM10.944 24l1.056-3.744h0.064l1.056 3.744h1.12l1.504-5.632h-1.216l-0.896 3.968h-0.064l-1.024-3.968h-0.992l-1.024 3.968h-0.064l-0.896-3.968h-1.216l1.472 5.632h1.12z"></path></svg>
				'
			);
			break;
		case 'docm':
			$svg = array(
				'font' => 'bb-icon-file-docm',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-docm</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.024 14.4l-0.48 2.176v9.12c0 1.024-0.8 1.888-1.792 1.952v0h-7.84v-11.296c0-1.088 0.864-1.952 1.952-1.952v0h8.16zM15.776 15.392h-6.912c-0.544 0-0.96 0.448-0.96 0.96v0 10.304h6.688c0.48 0 0.896-0.384 0.96-0.864v0-9.376l0.224-1.024zM14.24 24.928c-0.032 0.928-0.032 1.824 0.576 2.24 0.096 0.096-0.096 0.256-0.576 0.48v0h-7.52c-1.12-0.032-1.12-1.312-1.088-2.72 1.856 0 7.008 0 8.384 0h0.224zM11.488 21.984c0.32 0 0.544 0.224 0.544 0.544s-0.224 0.544-0.544 0.544c-0.288 0-0.512-0.224-0.512-0.544s0.224-0.544 0.512-0.544zM11.904 17.344l-0.096 3.776h-0.64l-0.064-3.776h0.8zM16.864 14.4c1.472 0 1.248 2.016 1.28 2.784-0.48 0-1.792 0-2.592 0 0-0.736-0.16-2.784 1.312-2.784z"></path></svg>'
			);
			break;
		case 'docx':
			$svg = array(
				'font' => 'bb-icon-file-docx',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-docx</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM18.496 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-5.984c-0.288 0-0.512-0.224-0.512-0.512v-0.992c0-0.288 0.224-0.512 0.512-0.512h5.984zM12.928 15.008c0.096 0 0.192 0.064 0.192 0.192v0 3.616h1.696c0.064 0 0.096 0.032 0.128 0.064 0.096 0.064 0.096 0.192 0.032 0.256v0l-3.52 3.744c-0.032 0-0.032 0-0.032 0-0.192 0.192-0.512 0.192-0.704 0v0l-3.424-3.744c-0.032-0.032-0.032-0.064-0.032-0.128 0-0.096 0.096-0.192 0.192-0.192v0h1.632v-3.616c0-0.128 0.096-0.192 0.192-0.192v0h3.648z"></path></svg>
				'
			);
			break;
		case 'dotm':
			$svg = array(
				'font' => 'bb-icon-file-dotm',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-dotm</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-11.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h11.008zM12 15.008c2.496 0 4.512 2.016 4.512 4.48 0 2.496-2.016 4.512-4.512 4.512s-4.512-2.016-4.512-4.512c0-2.464 2.016-4.48 4.512-4.48zM10.656 18.4h-0.768l0.768 2.912h0.8l0.512-2.016h0.064l0.512 2.016h0.8l0.8-2.912h-0.768l-0.448 2.080h-0.032l-0.512-2.080h-0.736l-0.512 2.080h-0.064l-0.416-2.080z"></path></svg>
				'
			);
			break;
		case 'dotx':
			$svg = array(
				'font' => 'bb-icon-file-dotx',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-dotx</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM10.784 23.104c0.832 0 1.344 0.576 1.344 1.472 0 0.928-0.512 1.504-1.344 1.504s-1.376-0.576-1.376-1.504c0-0.896 0.544-1.472 1.376-1.472zM7.648 23.168c0.864 0 1.344 0.512 1.344 1.408s-0.48 1.408-1.344 1.408v0h-1.088v-2.816h1.088zM14.688 23.168v0.512h-0.832v2.304h-0.608v-2.304h-0.832v-0.512h2.272zM15.744 23.168l0.576 0.992h0.064l0.576-0.992h0.672l-0.928 1.408 0.896 1.408h-0.672l-0.608-0.928h-0.032l-0.608 0.928h-0.64l0.896-1.408-0.896-1.408h0.704zM10.784 23.616c-0.48 0-0.768 0.384-0.768 0.96 0 0.608 0.288 0.992 0.768 0.992 0.448 0 0.736-0.384 0.736-0.992 0-0.576-0.288-0.96-0.736-0.96zM7.552 23.68h-0.416v1.824h0.416c0.544 0 0.832-0.32 0.832-0.928 0-0.576-0.32-0.896-0.832-0.896v0zM17.504 19.008c0.288 0 0.512 0.224 0.512 0.48v1.024c0 0.256-0.224 0.48-0.512 0.48h-11.008c-0.256 0-0.48-0.224-0.48-0.48v-1.024c0-0.256 0.224-0.48 0.48-0.48h11.008zM13.504 15.008c0.288 0 0.512 0.224 0.512 0.48v1.024c0 0.256-0.224 0.48-0.512 0.48h-7.008c-0.256 0-0.48-0.224-0.48-0.48v-1.024c0-0.256 0.224-0.48 0.48-0.48h7.008z"></path></svg>
				'
			);
			break;
		case 'eps':
		case 'svg':
			$svg = array(
				'font' => 'bb-icon-file-svg',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-svg</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM13.088 15.648c0.16 0 0.288 0.128 0.32 0.288l0.032 0.064v0.64h3.808c0.128-0.192 0.32-0.352 0.576-0.384h0.096c0.416 0 0.768 0.32 0.768 0.736s-0.352 0.768-0.768 0.768c-0.288 0-0.544-0.16-0.672-0.416h-3.808v0.032c2.080 0.672 3.648 2.592 3.776 4.896h1.088c0.192 0 0.32 0.096 0.352 0.256v3.072c0 0.16-0.128 0.32-0.256 0.352h-3.072c-0.192 0-0.32-0.128-0.352-0.288v-3.072c0-0.16 0.096-0.288 0.256-0.32h0.992c-0.128-1.76-1.248-3.2-2.784-3.84v0.576c0 0.16-0.128 0.288-0.288 0.32l-0.064 0.032h-3.008c-0.16 0-0.32-0.128-0.352-0.288v-0.48c-1.376 0.672-2.368 2.048-2.496 3.68h1.088c0.16 0 0.288 0.096 0.352 0.256v3.072c0 0.16-0.128 0.32-0.288 0.352h-3.072c-0.16 0-0.32-0.128-0.352-0.288v-3.072c0-0.16 0.128-0.288 0.288-0.32h0.992c0.128-2.208 1.536-4.032 3.488-4.8v-0.128h-3.744c-0.096 0.224-0.288 0.352-0.544 0.384l-0.096 0.032c-0.416 0-0.768-0.352-0.768-0.768s0.352-0.736 0.768-0.736c0.288 0 0.544 0.16 0.64 0.384h3.744v-0.64c0-0.16 0.128-0.32 0.288-0.352h3.072zM7.968 22.944h-2.304v2.304h2.304v-2.304zM17.952 22.944h-2.304v2.304h2.304v-2.304zM12.736 16.352h-2.304v2.304h2.304v-2.304z"></path></svg>
				'
			);
			break;
		case 'gif':
			$svg = array(
				'font' => 'bb-icon-file-gif',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-gif</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM12 15.008c3.328 0 6.016 2.688 6.016 5.984 0 3.328-2.688 6.016-6.016 6.016s-5.984-2.688-5.984-6.016c0-3.296 2.656-5.984 5.984-5.984zM10.656 26.016c-0.032 0.16 0.064 0.32 0.224 0.352 0.32 0.064 0.672 0.096 1.024 0.096 0.16 0.032 0.288-0.096 0.288-0.256s-0.128-0.288-0.256-0.288c-0.32 0-0.64-0.032-0.96-0.096-0.128-0.032-0.288 0.064-0.32 0.192zM13.888 25.536c-0.288 0.128-0.608 0.224-0.896 0.288-0.16 0.032-0.256 0.16-0.224 0.32s0.16 0.256 0.32 0.224c0.352-0.064 0.672-0.16 1.024-0.32 0.128-0.032 0.192-0.192 0.128-0.352-0.064-0.128-0.224-0.224-0.352-0.16zM8.864 25.152c-0.096 0.128-0.064 0.288 0.064 0.384 0.288 0.192 0.608 0.352 0.928 0.512 0.128 0.064 0.32 0 0.352-0.16 0.064-0.128 0-0.288-0.128-0.352-0.288-0.128-0.576-0.288-0.832-0.448-0.128-0.096-0.32-0.064-0.384 0.064zM15.424 24.512c-0.224 0.224-0.448 0.416-0.736 0.608-0.128 0.064-0.16 0.256-0.064 0.384 0.064 0.128 0.256 0.16 0.384 0.064 0.288-0.192 0.576-0.416 0.832-0.64 0.096-0.128 0.096-0.288 0-0.416-0.128-0.096-0.288-0.096-0.416 0zM7.488 23.584c-0.128 0.064-0.192 0.256-0.096 0.384 0.192 0.288 0.416 0.576 0.64 0.832 0.128 0.096 0.288 0.096 0.416 0 0.096-0.096 0.096-0.288 0-0.384-0.224-0.256-0.416-0.48-0.576-0.768-0.096-0.128-0.256-0.16-0.384-0.064zM16.544 22.88c-0.128 0.288-0.256 0.576-0.448 0.832-0.096 0.128-0.064 0.32 0.064 0.416 0.128 0.064 0.32 0.032 0.416-0.096 0.192-0.288 0.352-0.608 0.48-0.928 0.064-0.128 0-0.288-0.16-0.352-0.128-0.064-0.288 0-0.352 0.128zM6.912 21.696l-0.064 0.032c-0.16 0.032-0.256 0.16-0.224 0.32 0.064 0.352 0.16 0.672 0.288 0.992 0.064 0.16 0.224 0.224 0.384 0.16 0.128-0.064 0.192-0.224 0.128-0.352-0.096-0.288-0.192-0.608-0.256-0.896-0.032-0.128-0.096-0.192-0.192-0.224l-0.064-0.032zM10.208 19.712c-0.832 0-1.344 0.576-1.344 1.472 0 0.928 0.512 1.472 1.344 1.472 0.768 0 1.248-0.448 1.248-1.216v-0.352h-1.184v0.448h0.608v0.032c0 0.352-0.256 0.576-0.64 0.576-0.48 0-0.768-0.352-0.768-0.96s0.288-0.96 0.736-0.96c0.32 0 0.576 0.16 0.64 0.448h0.608c-0.096-0.576-0.576-0.96-1.248-0.96zM12.608 19.776h-0.576v2.816h0.576v-2.816zM15.136 19.776h-1.824v2.816h0.576v-1.088h1.152v-0.48h-1.152v-0.736h1.248v-0.512zM17.216 20.768c-0.16 0-0.288 0.128-0.288 0.256 0 0.32-0.032 0.64-0.096 0.96-0.032 0.16 0.064 0.288 0.224 0.32 0.128 0.032 0.288-0.064 0.32-0.224 0.064-0.32 0.096-0.672 0.096-1.024 0-0.16-0.128-0.288-0.256-0.288zM6.656 19.84c-0.096 0.32-0.128 0.672-0.128 1.024 0 0.16 0.128 0.288 0.256 0.288 0.16 0 0.288-0.128 0.288-0.256 0.032-0.32 0.064-0.64 0.128-0.96 0.032-0.128-0.064-0.288-0.224-0.32s-0.288 0.064-0.32 0.224zM16.672 18.72c-0.128 0.064-0.192 0.224-0.128 0.352 0.128 0.288 0.192 0.608 0.288 0.896 0.032 0.16 0.16 0.256 0.32 0.224s0.256-0.16 0.224-0.32c-0.096-0.352-0.192-0.672-0.32-1.024-0.064-0.128-0.224-0.192-0.384-0.128zM7.488 17.888c-0.192 0.288-0.384 0.608-0.512 0.928-0.064 0.128 0 0.288 0.16 0.352 0.128 0.064 0.288 0 0.352-0.128 0.128-0.288 0.288-0.576 0.448-0.832 0.096-0.128 0.064-0.288-0.064-0.384s-0.288-0.064-0.384 0.064zM15.488 17.152c-0.096 0.096-0.096 0.288 0 0.384 0.224 0.224 0.416 0.48 0.608 0.736 0.096 0.128 0.256 0.16 0.384 0.064 0.128-0.064 0.16-0.256 0.064-0.384-0.192-0.288-0.416-0.576-0.64-0.8-0.128-0.128-0.288-0.128-0.416 0zM9.088 16.352c-0.32 0.192-0.576 0.416-0.832 0.672-0.128 0.096-0.128 0.256-0.032 0.384 0.128 0.096 0.288 0.128 0.416 0 0.224-0.192 0.48-0.416 0.736-0.576 0.128-0.064 0.16-0.256 0.096-0.384-0.096-0.128-0.256-0.16-0.384-0.096zM13.696 16.064c-0.064 0.16 0.032 0.32 0.16 0.384 0.288 0.128 0.576 0.256 0.832 0.448 0.128 0.064 0.32 0.032 0.384-0.096 0.096-0.128 0.064-0.288-0.064-0.384-0.288-0.192-0.608-0.352-0.928-0.48-0.16-0.064-0.32 0-0.384 0.128zM11.072 15.616h-0.064c-0.352 0.064-0.704 0.16-1.024 0.288-0.128 0.064-0.224 0.224-0.16 0.352 0.064 0.16 0.224 0.224 0.384 0.16 0.288-0.096 0.576-0.192 0.896-0.256 0.16-0.032 0.256-0.16 0.224-0.32-0.032-0.128-0.096-0.192-0.192-0.224h-0.064zM12 15.52c-0.16 0-0.288 0.128-0.288 0.288s0.128 0.256 0.288 0.256c0.32 0 0.64 0.032 0.928 0.096 0.16 0.032 0.32-0.064 0.352-0.224 0.032-0.128-0.064-0.288-0.224-0.32-0.352-0.064-0.704-0.096-1.056-0.096z"></path></svg>'
			);
			break;
		case 'gz':
		case 'gzip':
		case 'zip':
			$svg = array(
				'font' => 'bb-icon-file-zip',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-zip</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h2.976v-1.984h2.016v-1.92h-2.016v-2.080h2.016v2.016h1.984v2.048h-1.984v1.92h11.008c1.056 0 1.92-0.832 1.984-1.856l0.032-0.16v-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM10.976 21.952v2.048h-1.984v-2.048h1.984zM11.008 16c0.544 0 0.992 0.448 0.992 0.992v3.008c0 0.544-0.448 0.992-0.992 0.992h-4c-0.544 0-0.992-0.448-0.992-0.992v-3.008c0-0.544 0.448-0.992 0.992-0.992h4zM10.592 16.992h-3.2c-0.192 0-0.352 0.128-0.384 0.32v1.28c0 0.192 0.128 0.352 0.32 0.384l0.064 0.032h3.2c0.192 0 0.352-0.16 0.416-0.32v-1.28c0-0.224-0.192-0.416-0.416-0.416z"></path></svg>'
			);
			break;
		case 'hlam':
			$svg = array(
				'font' => 'bb-icon-file-hlam',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-hlam</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM18.016 24c0.256 0 0.48 0.224 0.48 0.512v0.992c0 0.256-0.224 0.48-0.48 0.48h-12c-0.288 0-0.512-0.224-0.512-0.48v-0.992c0-0.288 0.224-0.512 0.512-0.512h12zM14.56 15.648c0.128 0 0.224 0.064 0.288 0.192v0l0.704 1.216 0.672-1.216c0.064-0.096 0.16-0.16 0.256-0.192v0h1.824c0.288 0 0.448 0.288 0.32 0.512v0l-1.504 2.816 1.504 3.168c0.096 0.224-0.032 0.448-0.224 0.512v0h-1.856c-0.128 0-0.256-0.096-0.32-0.224v0l-0.672-1.472-0.672 1.472c-0.064 0.128-0.16 0.192-0.256 0.192v0l-0.064 0.032h-1.856c-0.256 0-0.416-0.288-0.288-0.544v0l1.824-3.136-1.664-2.784c-0.128-0.224 0-0.48 0.224-0.544v0h1.76zM11.008 20c0.256 0 0.48 0.224 0.48 0.512v0.992c0 0.256-0.224 0.48-0.48 0.48h-4.992c-0.288 0-0.512-0.224-0.512-0.48v-0.992c0-0.288 0.224-0.512 0.512-0.512h4.992zM14.336 16.352h-0.832l1.44 2.432c0.032 0.096 0.064 0.192 0.032 0.288v0l-0.032 0.064-1.632 2.816h1.024l0.896-1.984c0.128-0.256 0.448-0.288 0.608-0.096v0l0.032 0.096 0.896 1.984h0.992l-1.376-2.816c-0.032-0.096-0.032-0.16 0-0.256v0l0.032-0.064 1.312-2.464h-0.992l-0.864 1.6c-0.128 0.224-0.416 0.256-0.576 0.064v0l-0.032-0.064-0.928-1.6zM11.008 16c0.256 0 0.48 0.224 0.48 0.512v0.992c0 0.256-0.224 0.48-0.48 0.48h-4.992c-0.288 0-0.512-0.224-0.512-0.48v-0.992c0-0.288 0.224-0.512 0.512-0.512h4.992z"></path></svg>
				'
			);
			break;
		case 'hlsb':
			$svg = array(
				'font' => 'bb-icon-file-hlsb',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-hlsb</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016zM13.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016zM9.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-3.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h3.008zM17.504 20.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016zM13.504 20.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016zM7.424 17.984l0.512 1.312h0.064l0.64-1.312h1.344l-1.216 2.432 1.248 2.592h-1.376l-0.64-1.312h-0.064l-0.64 1.312h-1.28l1.152-2.464-1.152-2.56h1.408z"></path></svg>
				'
			);
			break;
		case 'hlsm':
			$svg = array(
				'font' => 'bb-icon-file-hlsm',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32"><title>file-hlsm</title><path d="M17.632 0.928c1.024 0 1.984 0.416 2.688 1.152v0l5.92 6.112c0.672 0.704 1.056 1.632 1.056 2.624v0 16.48c0 2.080-1.696 3.776-3.776 3.776v0h-15.040c-2.080 0-3.776-1.696-3.776-3.776v0-22.592c0-2.080 1.696-3.776 3.776-3.776v0zM17.632 2.816h-9.152c-1.056 0-1.888 0.864-1.888 1.888v0 22.592c0 1.024 0.832 1.888 1.888 1.888v0h15.040c1.056 0 1.888-0.864 1.888-1.888v0-16.48c0-0.48-0.192-0.96-0.512-1.312v0l-5.92-6.112c-0.352-0.352-0.832-0.576-1.344-0.576v0zM19.936 21.632l0.48 1.248h0.064l0.608-1.248h1.248l-1.12 2.304 1.152 2.4h-1.312l-0.576-1.216h-0.064l-0.608 1.216h-1.216l1.12-2.304-1.12-2.4h1.344zM17.184 24.48c0.256 0 0.448 0.192 0.448 0.448v0.96c0 0.256-0.192 0.448-0.448 0.448h-1.888c-0.256 0-0.48-0.192-0.48-0.448v-0.96c0-0.256 0.224-0.448 0.48-0.448h1.888zM13.408 24.48c0.256 0 0.48 0.192 0.48 0.448v0.96c0 0.256-0.224 0.448-0.48 0.448h-2.816c-0.256 0-0.48-0.192-0.48-0.448v-0.96c0-0.256 0.224-0.448 0.48-0.448h2.816zM17.184 20.704c0.256 0 0.448 0.224 0.448 0.48v0.928c0 0.256-0.192 0.48-0.448 0.48h-1.888c-0.256 0-0.48-0.224-0.48-0.48v-0.928c0-0.256 0.224-0.48 0.48-0.48h1.888zM13.408 20.704c0.256 0 0.48 0.224 0.48 0.48v0.928c0 0.256-0.224 0.48-0.48 0.48h-2.816c-0.256 0-0.48-0.224-0.48-0.48v-0.928c0-0.256 0.224-0.48 0.48-0.48h2.816zM20.928 16.928c0.288 0 0.48 0.224 0.48 0.48v0.928c0 0.288-0.192 0.48-0.48 0.48h-1.856c-0.288 0-0.48-0.192-0.48-0.48v-0.928c0-0.256 0.192-0.48 0.48-0.48h1.856zM17.184 16.928c0.256 0 0.448 0.224 0.448 0.48v0.928c0 0.288-0.192 0.48-0.448 0.48h-1.888c-0.256 0-0.48-0.192-0.48-0.48v-0.928c0-0.256 0.224-0.48 0.48-0.48h1.888zM13.408 16.928c0.256 0 0.48 0.224 0.48 0.48v0.928c0 0.288-0.224 0.48-0.48 0.48h-2.816c-0.256 0-0.48-0.192-0.48-0.48v-0.928c0-0.256 0.224-0.48 0.48-0.48h2.816z"></path></svg>
				'
			);
			break;
		case 'htm':
		case 'html':
			$svg = array(
				'font' => 'bb-icon-file-html',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-html</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.736 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM9.536 20.416c0.192 0.224 0.224 0.544 0.032 0.8l-0.032 0.064-2.112 2.048 2.112 2.112c0.192 0.224 0.224 0.544 0.032 0.768l-0.032 0.064c-0.224 0.224-0.544 0.256-0.768 0.064l-0.096-0.064-2.496-2.528c-0.224-0.192-0.224-0.544-0.064-0.768l0.064-0.064 2.528-2.496c0.224-0.224 0.608-0.224 0.832 0zM14.080 20.416c0.224-0.224 0.608-0.224 0.832 0v0l2.528 2.496 0.032 0.064c0.192 0.224 0.16 0.576-0.032 0.768v0l-2.592 2.592c-0.224 0.192-0.544 0.16-0.768-0.064v0l-0.064-0.064c-0.16-0.224-0.16-0.544 0.064-0.768v0l2.080-2.112-2.144-2.112c-0.16-0.256-0.16-0.576 0.064-0.8zM12.768 20.032c0.288 0.096 0.48 0.384 0.416 0.672l-0.032 0.064-1.664 5.28c-0.096 0.32-0.448 0.48-0.736 0.384s-0.448-0.384-0.416-0.672l0.032-0.064 1.664-5.28c0.096-0.32 0.448-0.48 0.736-0.384zM10.496 15.008c0.288 0 0.512 0.224 0.512 0.48v1.024c0 0.256-0.224 0.48-0.512 0.48h-4c-0.256 0-0.48-0.224-0.48-0.48v-1.024c0-0.256 0.224-0.48 0.48-0.48h4z"></path></svg>'
			);
			break;
		case 'ics':
			$svg = array(
				'font' => 'bb-icon-file-ics',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-ics</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM15.008 15.488c0.544 0 0.992 0.448 0.992 1.024v0.512c1.12 0 2.016 0.896 2.016 1.984v5.984c0 1.12-0.896 2.016-2.016 2.016h-8c-1.088 0-1.984-0.896-1.984-2.016v-5.984c0-1.056 0.8-1.92 1.824-1.984h0.16v-0.512c0-0.576 0.448-1.024 0.992-1.024s0.992 0.448 0.992 1.024v0.512h4v-0.512c0-0.576 0.448-1.024 1.024-1.024zM16.064 19.616h-7.968c-0.256 0-0.448 0.192-0.512 0.416v4.48c0 0.512 0.384 0.96 0.896 0.992l0.096 0.032h6.976c0.512 0 0.96-0.384 0.992-0.896l0.032-0.128v-4.384c0-0.288-0.224-0.512-0.512-0.512zM10.144 20.672c0.256 0 0.48 0.224 0.48 0.512v0.704c0 0.288-0.224 0.512-0.48 0.512h-0.832c-0.288 0-0.512-0.224-0.512-0.512v-0.704c0-0.288 0.224-0.512 0.512-0.512h0.832z"></path></svg>
				'
			);
			break;
		case 'ico':
			$svg = array(
				'font' => 'bb-icon-file-ico',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-ico</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16 16c1.12 0 2.016 0.896 2.016 1.984v7.008c0 1.12-0.896 2.016-2.016 2.016h-8c-1.088 0-1.984-0.896-1.984-2.016v-7.008c0-1.088 0.896-1.984 1.984-1.984h8zM13.504 17.984h-3.008c-0.256 0-0.48 0.224-0.48 0.512v0 0.544c0 0.256 0.224 0.48 0.48 0.48v0h0.768v3.968h-0.768c-0.256 0-0.48 0.192-0.48 0.48v0 0.544c0 0.256 0.224 0.48 0.48 0.48v0h3.008c0.288 0 0.512-0.224 0.512-0.48v0-0.544c0-0.288-0.224-0.48-0.512-0.48v0h-0.672v-3.968h0.672c0.288 0 0.512-0.224 0.512-0.48v0-0.544c0-0.288-0.224-0.512-0.512-0.512v0z"></path></svg>
				'
			);
			break;
		case 'ipa':
			$svg = array(
				'font' => 'bb-icon-file-ipa',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-ipa</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16 13.984c1.376 0 2.496 1.12 2.496 2.528v0 8c0 1.376-1.12 2.496-2.496 2.496v0h-8c-1.376 0-2.496-1.12-2.496-2.496v0-8c0-1.408 1.12-2.528 2.496-2.528v0h8zM16 15.008h-8c-0.832 0-1.504 0.672-1.504 1.504v0 8c0 0.8 0.672 1.472 1.504 1.472v0h8c0.832 0 1.504-0.672 1.504-1.472v0-8c0-0.832-0.672-1.504-1.504-1.504v0zM9.12 19.008v2.976h-0.608v-2.976h0.608zM11.104 19.008c0.608 0 1.056 0.416 1.056 1.024s-0.448 1.024-1.088 1.024v0h-0.576v0.928h-0.64v-2.976h1.248zM14.080 19.008l1.056 2.976h-0.704l-0.224-0.704h-1.056l-0.224 0.704h-0.608l1.024-2.976h0.736zM13.728 19.616h-0.064l-0.352 1.184h0.768l-0.352-1.184zM10.944 19.52h-0.448v1.024h0.448c0.352 0 0.576-0.16 0.576-0.512 0-0.32-0.224-0.512-0.576-0.512v0z"></path></svg>
				'
			);
			break;
		case 'js':
			$svg = array(
				'font' => 'bb-icon-file-js',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-js</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM13.504 16c1.088 0 1.984 0.896 1.984 1.984v7.008c0 1.12-0.896 2.016-1.984 2.016h-6.016c-1.088 0-1.984-0.896-1.984-2.016v-7.008c0-1.088 0.896-1.984 1.984-1.984h6.016zM15.904 13.12c1.312 0 2.4 1.056 2.496 2.336v6.496c0 1.152-0.896 2.112-2.048 2.176h-0.128v-0.992c0.608 0 1.12-0.48 1.184-1.088v-6.4c0-0.8-0.608-1.44-1.344-1.504h-5.568c-0.768 0-1.408 0.576-1.472 1.344l-0.032 0.16v0.032h-0.992v-0.032c0-1.344 1.024-2.432 2.336-2.496l0.16-0.032h5.408zM12.992 20.992h-5.088c-0.224 0.064-0.416 0.256-0.416 0.512 0 0.224 0.192 0.448 0.416 0.48h5.184c0.224-0.032 0.416-0.256 0.416-0.48 0-0.288-0.224-0.512-0.512-0.512zM12.992 19.008h-5.088c-0.224 0.032-0.416 0.256-0.416 0.48 0 0.256 0.192 0.448 0.416 0.512h5.184c0.224-0.064 0.416-0.256 0.416-0.512s-0.224-0.48-0.512-0.48z"></path></svg>
				'
			);
			break;
		case 'jar':
			$svg = array(
				'font' => 'bb-icon-file-jar',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-jar</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.696 25.504c-0.192 0.48-0.544 0.832-1.088 1.024-0.64 0.288-1.472 0.416-2.336 0.448-0.96 0.064-2.048 0.128-3.328 0-0.416-0.032-1.024-0.096-1.792-0.224-0.192-0.032-0.48-0.096-0.896-0.224 0.928 0.064 1.6 0.096 2.016 0.096 1.824 0 3.136-0.032 3.872-0.096 1.536-0.064 2.464-0.288 3.552-1.024zM8.256 24.544c-0.928 0.16-1.376 0.32-1.376 0.512 0 0.256 2.016 0.608 4.096 0.608 0.928 0 1.92-0.032 2.752-0.128 1.056-0.096 1.888-0.256 2.24-0.352 0.64-0.128 0.928-0.288 0.864-0.544 0.224 0.064 0.224 0.224 0.128 0.416s-0.48 0.544-1.312 0.768c-0.448 0.128-1.216 0.352-3.232 0.448-1.6 0.032-2.304 0.032-3.072 0-0.768-0.064-3.456-0.256-3.392-0.96 0.032-0.48 0.8-0.736 2.304-0.768zM9.632 23.36v0.032c-0.032 0.064-0.064 0.192 0.064 0.256 0.128 0.032 0.768 0.096 1.44 0.064 0.576-0.032 1.152-0.064 1.536-0.064 0.448-0.032 0.992-0.128 1.6-0.224-0.128 0.096-0.384 0.384-0.928 0.608-0.576 0.256-1.696 0.384-2.272 0.384s-2.176 0-2.176-0.544c0-0.512 0.736-0.512 0.736-0.512zM9.184 21.664c-0.224 0.192-0.256 0.32-0.096 0.416 0.192 0.16 0.64 0.224 1.984 0.224 0.928 0 2.048-0.128 3.456-0.384-0.192 0.192-0.384 0.352-0.608 0.448-0.768 0.384-1.792 0.576-2.912 0.576-2.176 0-2.72-0.48-2.656-0.8 0.032-0.192 0.288-0.352 0.832-0.48zM17.024 19.584c0.768 0.192 1.024 0.576 1.056 1.056 0.096 0.864-0.8 1.536-2.656 2.048 1.024-0.768 1.536-1.376 1.6-1.888s-0.416-0.832-1.376-1.024c0.512-0.224 0.96-0.288 1.376-0.192zM9.76 19.776c-0.928 0.256-1.376 0.448-1.376 0.576 0 0.224 1.152 0.352 2.656 0.352 0.992 0 2.336-0.096 4-0.352-0.8 0.768-2.144 1.12-4.064 1.088-2.88-0.064-3.808-0.48-3.744-0.992 0.032-0.384 0.864-0.608 2.528-0.672zM14.88 14.4c-0.32 0.256-0.64 0.48-0.928 0.704-0.416 0.352-1.088 0.832-1.088 1.504s0.576 0.704 0.8 1.632c0.16 0.608-0.192 1.216-1.024 1.856 0.224-0.288 0.288-0.8 0.16-1.216-0.16-0.416-1.248-0.864-0.896-2.272 0.096-0.352 0.512-0.832 0.832-1.088 0.512-0.384 1.216-0.768 2.144-1.12zM13.504 11.008c0.544 0.992 0.608 1.888 0.128 2.656-0.48 0.736-1.568 1.536-2.304 2.24-0.352 0.384-0.512 0.928-0.448 1.376 0.064 0.832 0.32 1.6 0.704 2.272-1.28-0.992-1.888-1.984-1.824-2.944 0.096-1.44 1.504-2.112 2.176-2.656s1.696-1.248 1.568-2.944z"></path></svg>
				'
			);
			break;
		case 'mp3':
			$svg = array(
				'font' => 'bb-icon-file-mp3',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-mp3</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16.96 16c0.128 0 0.288 0.032 0.384 0.128s0.16 0.224 0.16 0.352v0 7.744c0 0.96-0.672 1.824-1.664 2.048-0.96 0.224-1.984-0.192-2.464-1.056-0.448-0.832-0.288-1.888 0.448-2.528s1.856-0.736 2.688-0.224v0-3.040l-6.624 0.704v4.768c0 0.96-0.704 1.792-1.664 2.048-0.96 0.224-1.984-0.192-2.464-1.056-0.48-0.832-0.288-1.888 0.448-2.528s1.824-0.736 2.688-0.224v0-5.856c0-0.256 0.192-0.448 0.448-0.48v0z"></path></svg>'
			);
			break;
		case 'ods':
			$svg = array(
				'font' => 'bb-icon-file-ods',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-ods</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16 16c1.12 0 2.016 0.896 2.016 1.984v7.008c0 1.12-0.896 2.016-2.016 2.016h-8c-1.088 0-1.984-0.896-1.984-2.016v-7.008c0-1.088 0.896-1.984 1.984-1.984h8zM9.344 24.224h-1.856v0.768c0 0.256 0.192 0.448 0.416 0.512h1.44v-1.28zM12.352 24.224h-2.016v1.28h2.016v-1.28zM16.512 24.224h-3.168v1.28h2.656c0.256 0 0.448-0.192 0.48-0.416l0.032-0.096v-0.768zM9.344 22.048h-1.856v1.184h1.856v-1.184zM12.352 22.048h-2.016v1.184h2.016v-1.184zM16.512 22.048h-3.168v1.184h3.168v-1.184zM9.344 19.84h-1.856v1.184h1.856v-1.184zM12.352 19.84h-2.016v1.184h2.016v-1.184zM16.512 19.84h-3.168v1.184h3.168v-1.184zM9.344 17.504h-1.344c-0.256 0-0.448 0.16-0.48 0.416l-0.032 0.064v0.864h1.856v-1.344zM12.352 17.504h-2.016v1.344h2.016v-1.344z"></path></svg>
				'
			);
			break;
		case 'odt':
			$svg = array(
				'font' => 'bb-icon-file-odt',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-odt</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-11.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h11.008zM17.504 21.536c0.288 0 0.512 0.224 0.512 0.48v1.024c0 0.256-0.224 0.48-0.512 0.48h-11.008c-0.256 0-0.48-0.224-0.48-0.48v-1.024c0-0.256 0.224-0.48 0.48-0.48h11.008zM11.712 16.512c0.576 0.128 0.992 0.416 1.28 0.8-1.024-0.096-1.792 0.064-2.368 0.512-0.48 0.384-0.8 0.992-0.928 1.952 0 0.128-0.064 0.16-0.128 0.192s-0.192 0.032-0.288-0.096c-0.608-0.512-1.344-0.928-1.952-0.992-0.736-0.096-1.504 0.288-2.336 1.12 0.448-1.312 1.056-2.016 1.888-2.176 0.832-0.128 1.536 0.064 2.048 0.544 0.192-0.704 0.576-1.248 1.152-1.6 0.384-0.224 1.056-0.384 1.632-0.256zM11.136 14.016c0.384 0.096 0.672 0.288 0.896 0.544-0.704-0.128-1.28 0-1.76 0.352-0.352 0.256-0.512 0.768-0.576 1.312 0 0.096-0.032 0.128-0.096 0.128-0.032 0.032-0.128 0.032-0.192-0.032-0.416-0.384-0.928-0.672-1.376-0.704-0.512-0.032-1.056 0.224-1.632 0.768 0.32-0.896 0.736-1.376 1.344-1.472 0.576-0.096 1.056 0.032 1.408 0.384 0.128-0.48 0.416-0.832 0.832-1.088 0.256-0.16 0.736-0.256 1.152-0.192z"></path></svg>
				'
			);
			break;
		case 'pdf':
			$svg = array(
				'font' => 'bb-icon-file-pdf',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-pdf</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM11.456 12.992c0.864 0 1.44 0.48 1.44 2.016 0 0.576-0.064 1.216-0.256 2.208 0.608 1.152 1.44 2.304 2.208 3.168 0.576-0.064 1.056-0.16 1.536-0.16 1.44 0 2.112 0.8 2.112 1.632 0 0.8-0.768 1.728-1.984 1.728-0.704 0-1.472-0.352-2.336-1.088-1.312 0.256-2.72 0.736-3.968 1.216-0.704 1.44-1.696 3.296-3.008 3.296-0.96 0-1.696-0.896-1.696-1.728 0-1.088 1.056-2.048 3.264-3.072 0.704-1.472 1.408-3.264 1.824-4.832-0.48-0.96-0.736-1.792-0.736-2.496 0-1.184 0.544-1.888 1.6-1.888zM11.744 19.072c-0.256 0.896-0.64 1.824-0.96 2.688 0.8-0.256 1.6-0.544 2.4-0.736-0.512-0.608-0.96-1.248-1.44-1.952z"></path></svg>
				'
			);
			break;
		case 'png':
			$svg = array(
				'font' => 'bb-icon-file-png',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-png</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.12 0-2.016 0.896-2.016 2.016v0 24c0 1.12 0.896 2.016 2.016 2.016v0h16c1.12 0 1.984-0.896 1.984-2.016v0-17.504c0-0.512-0.192-1.024-0.544-1.408v0l-6.272-6.496c-0.384-0.384-0.896-0.608-1.44-0.608v0zM15.68 15.008c1.28 0 2.304 1.024 2.304 2.304v0 7.392c0 1.248-1.024 2.304-2.304 2.304v0h-7.36c-1.28 0-2.336-1.056-2.336-2.304v0-7.392c0-1.28 1.056-2.304 2.336-2.304v0h7.36zM9.152 21.376l-0.096 0.064-2.144 1.6v1.664c0 0.704 0.544 1.312 1.248 1.376h7.52c0.768 0 1.408-0.64 1.408-1.376v0-3.136c-0.896 0.416-2.080 0.992-3.52 1.728-0.448 0.224-0.992 0.192-1.408-0.064l-0.128-0.096-2.368-1.728c-0.16-0.096-0.352-0.096-0.512-0.032zM15.68 15.936h-7.36c-0.768 0-1.408 0.608-1.408 1.376v0 4.48c0.416-0.32 0.96-0.704 1.536-1.152 0.512-0.384 1.152-0.416 1.664-0.096l0.128 0.064 2.368 1.728c0.128 0.096 0.288 0.128 0.448 0.096l0.096-0.064 3.936-1.92v-3.136c0-0.736-0.576-1.312-1.248-1.376h-0.16zM13.376 17.792c0.672 0 1.248 0.544 1.248 1.248s-0.576 1.248-1.248 1.248c-0.704 0-1.248-0.544-1.248-1.248s0.544-1.248 1.248-1.248z"></path></svg>'
			);
			break;
		case 'psd':
			$svg = array(
				'font' => 'bb-icon-file-psd',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-psd</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM7.072 22.336l4.416 2.528c0.32 0.192 0.704 0.192 0.992 0.032l4.448-2.528 0.896 0.704c0.16 0.16 0.192 0.416 0.064 0.576-0.032 0.032-0.064 0.064-0.128 0.096l-5.536 3.2c-0.128 0.064-0.288 0.064-0.416 0.032l-0.096-0.032-5.44-3.2c-0.192-0.128-0.256-0.352-0.128-0.544 0-0.064 0.032-0.096 0.064-0.096l0.864-0.768zM7.104 20l4.416 2.56c0.32 0.16 0.704 0.16 0.992 0l4.448-2.528 0.896 0.736c0.16 0.128 0.192 0.384 0.064 0.544-0.032 0.064-0.064 0.096-0.128 0.128l-5.536 3.168c-0.128 0.064-0.288 0.096-0.448 0.032l-0.064-0.032-5.44-3.2c-0.192-0.096-0.256-0.352-0.128-0.544 0-0.032 0.032-0.064 0.064-0.096l0.864-0.768zM12.16 15.040l0.064 0.032 5.472 3.104c0.192 0.128 0.288 0.32 0.288 0.544 0 0.16-0.064 0.32-0.192 0.416l-0.096 0.064-5.44 3.104c-0.128 0.096-0.288 0.096-0.448 0.032l-0.064-0.032-5.44-3.104c-0.224-0.096-0.32-0.32-0.288-0.544 0-0.16 0.064-0.32 0.192-0.416l0.096-0.064 5.408-3.104c0.128-0.096 0.288-0.096 0.448-0.032zM11.968 16.256l-4.256 2.432 0.8 0.448 1.856-0.704c0.032 0 0.032 0 0.064 0 0.128-0.032 0.256 0.064 0.32 0.192v0.064l0.064 0.352 1.408-0.352c0.096-0.032 0.16 0 0.224 0 0.128 0.064 0.192 0.224 0.16 0.352l-0.032 0.064-0.896 1.824 0.32 0.192 4.288-2.432-4.32-2.432zM13.408 17.696c0 0.288-0.384 0.512-0.896 0.576-0.512 0.032-0.928-0.16-0.928-0.416-0.032-0.288 0.352-0.544 0.864-0.576s0.928 0.16 0.96 0.416z"></path></svg>'
			);
			break;
		case 'potm':
		case 'pptm':
			$svg = array(
				'font' => 'bb-icon-file-pptm',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-pptm</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.024 14.4c1.28 0.128 1.088 2.048 1.12 2.784-0.288 0-0.928 0-1.568 0l-0.032 8.512c0 1.024-0.8 1.888-1.792 1.952v0h-8.032c-1.056-0.032-1.12-1.216-1.088-2.56v-0.16c0.32 0 0.768 0 1.248 0l0.032-8.576c0-1.088 0.864-1.952 1.952-1.952v0h8.16zM7.904 24.928c2.272 0 5.152 0 6.112 0h0.224c-0.032 0.64-0.032 1.248 0.16 1.728h0.192c0.48 0 0.896-0.384 0.96-0.864v0-8.8c0-0.416 0-1.056 0.128-1.6h-6.816c-0.544 0-0.96 0.448-0.96 0.96v0zM12.16 16.992c1.056 0 1.536 0.64 1.536 1.536 0 0.832-0.448 1.632-1.376 1.696h-1.376v1.76h-0.928v-4.992h2.144zM10.944 17.856v1.504c1.024 0 1.792 0.096 1.792-0.768 0-0.832-0.512-0.736-1.792-0.736z"></path></svg>'
			);
			break;
		case 'potx':
		case 'pptx':
			$svg = array(
				'font' => 'bb-icon-file-pptx',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-pptx</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-11.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h11.008zM11.84 18.4l4.608 2.848c-0.992 1.376-2.624 2.24-4.448 2.24-2.048 0-3.872-1.12-4.8-2.816l-0.128-0.192 4.768-2.080zM12.224 12.512c2.944 0.096 5.28 2.528 5.28 5.472 0 0.864-0.192 1.632-0.512 2.368l-0.16 0.256-4.608-2.784v-5.312zM11.52 12.512v5.312l-4.704 2.016c-0.192-0.576-0.32-1.216-0.32-1.856 0-2.848 2.208-5.216 5.024-5.472z"></path></svg>'
			);
			break;
		case 'pps':
			$svg = array(
				'font' => 'bb-icon-file-pps',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-pps</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM18.592 16c0.224 0 0.416 0.192 0.416 0.384v1.216c0 0.224-0.192 0.384-0.416 0.384h-0.576v7.52c0 0.768-0.608 1.408-1.376 1.504h-9.152c-0.768 0-1.408-0.608-1.472-1.376v-7.648h-0.608c-0.224 0-0.416-0.16-0.416-0.384v-1.216c0-0.192 0.192-0.384 0.416-0.384h13.184zM16.992 17.984h-9.984v7.52c0 0.256 0.16 0.448 0.416 0.48l0.064 0.032h9.024c0.224 0 0.448-0.192 0.48-0.416v-7.616zM11.776 19.456v2.784h2.752c0 1.504-1.216 2.752-2.752 2.752s-2.784-1.248-2.784-2.752c0-1.536 1.248-2.784 2.784-2.784zM12.224 19.008c1.472 0 2.688 1.152 2.784 2.592v0.16h-2.784v-2.752z"></path></svg>
				'
			);
			break;
		case 'ppsx':
			$svg = array(
				'font' => 'bb-icon-file-ppsx',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-ppsx</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.472 26.4c0.288 0 0.544 0.256 0.544 0.608 0 0.288-0.224 0.544-0.48 0.576l-0.064 0.032h-10.912c-0.32 0-0.544-0.288-0.544-0.608s0.192-0.544 0.448-0.608h11.008zM17.472 23.904c0.288 0 0.544 0.256 0.544 0.608 0 0.288-0.224 0.544-0.48 0.576h-10.976c-0.32 0-0.544-0.256-0.544-0.576s0.192-0.544 0.448-0.608h11.008zM11.488 12.512c0 0.896 0 2.144 0 3.68v0.8c2.208 0 3.712 0 4.512 0 0 2.496-2.016 4.512-4.512 4.512-2.464 0-4.48-2.016-4.48-4.512 0-2.464 2.016-4.48 4.48-4.48zM12.512 11.488c2.464 0 4.48 2.016 4.48 4.512v0h-4.48z"></path></svg>
				'
			);
			break;
		case 'ppt':
			$svg = array(
				'font' => 'bb-icon-file-ppt',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-ppt</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM11.552 15.936v5.536h5.536c0 3.040-2.496 5.536-5.536 5.536-3.072 0-5.536-2.496-5.536-5.536 0-3.072 2.464-5.536 5.536-5.536zM12.448 15.008c3.008 0 5.44 2.368 5.536 5.312l0.032 0.224h-5.568v-5.536z"></path></svg>
				'
			);
			break;
		case 'rar':
			$svg = array(
				'font' => 'bb-icon-file-rar',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-rar</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM9.44 15.648l4.032 2.624c0.128 0.096 0.224 0.256 0.224 0.448v6.336l-3.072 1.28c-0.16 0.064-0.32 0.032-0.48-0.064l-4.608-3.040c-0.032-0.032-0.032-0.064-0.032-0.096 0.032-0.032 0.032-0.032 0.064-0.064l0.384-0.128v0.16l4.416 2.88c0.16-0.224 0.256-0.48 0.256-0.768 0-0.224-0.096-0.544-0.256-0.864v0l-4.832-3.2c-0.032-0.032-0.032-0.064-0.032-0.128 0.032 0 0.032 0 0.064-0.032l0.384-0.128v0.16l4.416 2.88c0.16-0.288 0.256-0.544 0.256-0.832 0-0.192-0.096-0.48-0.256-0.8v0l-4.832-3.2c-0.032-0.032-0.032-0.064-0.032-0.128 0.032 0 0.032 0 0.064-0.032l0.448-0.16c0 0.064 0 0.128 0 0.192l4.352 2.88c0.16-0.352 0.256-0.672 0.256-0.96 0-0.256-0.064-0.544-0.192-0.832-0.032-0.096-0.096-0.16-0.192-0.224l-4.256-2.656-0.32-0.192c-0.032-0.032-0.032-0.064-0.032-0.128 0.032 0 0.032-0.032 0.064-0.032l3.744-1.152zM13.28 14.528l4.448 2.496c0.352 0.256 0.512 0.64 0.512 1.12s-0.16 0.8-0.448 1.024l-0.064-0.064c0.352 0.256 0.512 0.64 0.512 1.12s-0.16 0.8-0.448 1.024l-0.064-0.064c0.352 0.256 0.512 0.64 0.512 1.152 0 0.48-0.16 0.832-0.512 1.024l-2.656 1.12v-6.336c0-0.192-0.096-0.352-0.256-0.448l-3.936-2.496 2.144-0.672c0.096-0.032 0.192-0.032 0.256 0z"></path></svg>
				'
			);
			break;
		case 'rtf':
			$svg = array(
				'font' => 'bb-icon-file-rtf',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-rtf</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM12.352 16.992v8h0.928v0.992h-3.616v-0.992h1.152v-1.984h-2.72l-0.256 0.32-1.184 1.664h0.928v0.992h-3.072v-0.992h0.896l5.696-8h1.248zM18.496 24c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.256-0.224 0.48-0.512 0.48h-4c-0.256 0-0.48-0.224-0.48-0.48v-0.992c0-0.288 0.224-0.512 0.48-0.512h4zM18.496 20.512c0.288 0 0.512 0.224 0.512 0.48v0.992c0 0.288-0.224 0.512-0.512 0.512h-4c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.256 0.224-0.48 0.48-0.48h4zM10.816 19.168l-2.016 2.816h2.016v-2.816zM18.496 16.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-4c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h4z"></path></svg>
				'
			);
			break;
		case 'rss':
			$svg = array(
				'font' => 'bb-icon-file-rss',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-rss</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM6.016 15.008c6.24 0 11.328 4.992 11.488 11.2v0.288h-2.016c0-5.152-4.096-9.344-9.216-9.504h-0.256v-1.984zM6.016 19.008c4.032 0 7.36 3.232 7.488 7.232v0.256h-2.016c0-2.976-2.336-5.376-5.28-5.504h-0.192v-1.984zM7.52 23.488c0.832 0 1.504 0.672 1.504 1.472 0 0.832-0.672 1.504-1.504 1.504s-1.504-0.672-1.504-1.504c0-0.8 0.672-1.472 1.504-1.472z"></path></svg>
				'
			);
			break;
		case 'sketch':
			$svg = array(
				'font' => 'bb-icon-file-sketch',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-sketch</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM15.584 13.504c0.16 0 0.32 0.064 0.416 0.192v0l3.232 4.32c0.16 0.192 0.16 0.448 0 0.608v0l-7.008 8.704c-0.192 0.224-0.576 0.224-0.768 0v0l-7.008-8.704c-0.128-0.16-0.128-0.416 0-0.608v0l3.264-4.32c0.096-0.128 0.256-0.192 0.416-0.192v0h7.456zM17.664 19.008h-11.616l5.792 7.2 5.824-7.2zM7.328 15.84l-1.6 2.144h1.6v-2.144zM11.872 15.648l-2.496 2.336h5.344l-2.848-2.336zM16.352 15.808v2.176h1.632l-1.632-2.176zM15.328 14.496h-6.976v3.104l3.168-2.976c0.16-0.128 0.384-0.16 0.576-0.064l0.096 0.064 3.136 2.592v-2.72z"></path></svg>
				'
			);
			break;
		case 'tar':
			$svg = array(
				'font' => 'bb-icon-file-tar',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-tar</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM15.936 16.512c0.544 0 1.024 0.32 1.216 0.8v0l0.736 1.824c0.064 0.16 0.128 0.352 0.128 0.544v0 6.304c0 0.832-0.672 1.504-1.504 1.504v0h-9.024c-0.8 0-1.472-0.672-1.472-1.504v0-6.304c0-0.192 0.032-0.352 0.064-0.512v0l0.672-1.824c0.192-0.512 0.672-0.832 1.216-0.832v0zM16.992 20h-4v0.704c0 0.224-0.16 0.448-0.416 0.48h-1.088c-0.256 0-0.48-0.224-0.48-0.48v0-0.704h-4v5.984c0 0.256 0.16 0.48 0.416 0.512h9.088c0.256 0 0.48-0.224 0.48-0.512v0-5.984zM11.008 17.504h-3.040c-0.128 0-0.224 0.064-0.288 0.192v0l-0.448 1.312h3.776v-1.504zM15.936 17.504h-2.944v1.504h3.776l-0.544-1.312c-0.032-0.096-0.128-0.16-0.224-0.192h-0.064z"></path></svg>
				'
			);
			break;
		case 'tif':
		case 'tiff':
		case 'jpg':
		case 'jpeg':
			$svg = array(
				'font' => 'bb-icon-file-jpg',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-jpg</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.736 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16 13.504c1.6 0 2.912 1.248 3.008 2.816v8.192c0 1.6-1.248 2.88-2.816 2.976h-8.192c-1.6 0-2.912-1.248-2.976-2.816l-0.032-0.16v-8c0-1.6 1.248-2.912 2.848-3.008h8.16zM16 14.496h-8c-1.056 0-1.92 0.832-1.984 1.856v8.16c0 0.064 0 0.096 0 0.16l2.624-2.432c0.384-0.384 1.024-0.352 1.408 0.032v0l1.376 1.504 3.328-3.84c0.352-0.416 0.992-0.448 1.408-0.096 0.032 0.032 0.064 0.064 0.096 0.096l1.76 1.92v-5.344c0-1.056-0.832-1.92-1.856-2.016h-0.16zM10.752 18.112c0.704 0 1.248 0.544 1.248 1.248 0 0.672-0.544 1.248-1.248 1.248s-1.248-0.576-1.248-1.248c0-0.704 0.544-1.248 1.248-1.248z"></path></svg>
				'
			);
			break;
		case 'txt':
			$svg = array(
				'font' => 'bb-icon-file-txt',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-txt</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM15.488 24c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.256-0.224 0.48-0.512 0.48h-8.992c-0.256 0-0.48-0.224-0.48-0.48v-0.992c0-0.288 0.224-0.512 0.48-0.512h8.992zM13.504 20.512c0.288 0 0.512 0.224 0.512 0.48v0.992c0 0.288-0.224 0.512-0.512 0.512h-7.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.256 0.224-0.48 0.48-0.48h7.008zM17.504 16.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-11.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h11.008z"></path></svg>'
			);
			break;
		case 'vcf':
			$svg = array(
				'font' => 'bb-icon-file-vcf',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-vcf</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16 13.504c1.376 0 2.496 1.12 2.496 2.496v0 8.992c0 1.376-1.12 2.496-2.496 2.496v0h-8c-1.376 0-2.496-1.12-2.496-2.496v0-8.992c0-1.376 1.12-2.496 2.496-2.496v0h8zM16 14.496h-8c-0.832 0-1.504 0.672-1.504 1.504v0 8.992c0 0.832 0.672 1.504 1.504 1.504v0h8c0.832 0 1.504-0.672 1.504-1.504v0-8.992c0-0.832-0.672-1.504-1.504-1.504v0zM14.56 24.16c0.256 0 0.48 0.224 0.48 0.48s-0.16 0.448-0.384 0.512h-5.088c-0.288 0-0.512-0.224-0.512-0.512 0-0.224 0.16-0.448 0.416-0.48h5.088zM14.624 20.64c0.192 0.128 0.352 0.352 0.384 0.576v1.568h-6.016v-1.472c0-0.256 0.16-0.512 0.384-0.672 1.568-1.024 3.68-1.024 5.248 0zM13.344 16.512c0.672 0.672 0.672 1.792 0 2.464-0.704 0.672-1.792 0.672-2.464 0-0.704-0.672-0.704-1.792 0-2.464 0.672-0.672 1.76-0.672 2.464 0z"></path></svg>
				'
			);
			break;
		case 'wav':
			$svg = array(
				'font' => 'bb-icon-file-wav',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-wav</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16 13.984c1.12 0 2.016 0.896 2.016 2.016v8c0 1.088-0.896 1.984-2.016 1.984h-8c-1.088 0-1.984-0.896-1.984-1.984v-8c0-1.12 0.896-2.016 1.984-2.016h8zM10.144 16.512c-0.256 0-0.448 0.16-0.48 0.384v5.6c0.032 0.224 0.224 0.384 0.48 0.384s0.448-0.16 0.512-0.384v-5.6c-0.064-0.224-0.256-0.384-0.512-0.384zM15.36 16.512c-0.256 0-0.448 0.16-0.512 0.384v5.6c0.064 0.224 0.256 0.384 0.512 0.384 0.224 0 0.448-0.16 0.48-0.384v-5.6c-0.032-0.224-0.256-0.384-0.48-0.384zM13.536 17.632c-0.256 0-0.448 0.16-0.512 0.416v3.584c0.064 0.256 0.256 0.416 0.512 0.416s0.448-0.16 0.48-0.416v-3.584c-0.032-0.256-0.224-0.416-0.48-0.416zM8.512 18.24c-0.256 0-0.448 0.16-0.512 0.416v2.56c0.064 0.224 0.256 0.416 0.512 0.416 0.224 0 0.448-0.192 0.48-0.416v-2.56c-0.032-0.256-0.256-0.416-0.48-0.416zM11.744 18.24c-0.224 0-0.448 0.16-0.48 0.416v2.56c0.032 0.224 0.256 0.416 0.48 0.416 0.256 0 0.448-0.192 0.512-0.416v-2.56c-0.064-0.256-0.256-0.416-0.512-0.416z"></path></svg>
				'
			);
			break;
		case 'xlam':
		case 'xls':
		case 'xlsb':
		case 'xlsm':
		case 'xlsx':
			$svg = array(
				'font' => 'bb-icon-file-xlsx',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-xlsx</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM15.488 13.984c1.408 0 2.528 1.12 2.528 2.528v0 8c0 1.376-1.12 2.496-2.528 2.496v0h-8c-1.376 0-2.496-1.12-2.496-2.496v0-8c0-1.408 1.12-2.528 2.496-2.528v0h8zM9.024 23.072h-3.008v1.44c0 0.768 0.576 1.408 1.344 1.472h1.664v-2.912zM16.992 23.072h-6.976v2.912h5.472c0.8 0 1.44-0.576 1.504-1.344v-1.568zM9.024 19.072h-3.008v3.008h3.008v-3.008zM16.992 19.072h-6.976v3.008h6.976v-3.008zM9.024 15.008h-1.536c-0.8 0-1.472 0.672-1.472 1.504v0 1.568h3.008v-3.072zM15.488 15.008h-5.472v3.072h6.976v-1.568c0-0.8-0.576-1.44-1.344-1.504h-0.16z"></path></svg>'
			);
			break;
		case 'xltm':
			$svg = array(
				'font' => 'bb-icon-file-xltm',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-xltm</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.504 24c0.288 0 0.512 0.224 0.512 0.512v1.984c0 0.288-0.224 0.512-0.512 0.512h-4c-0.288 0-0.512-0.224-0.512-0.512v-1.984c0-0.288 0.224-0.512 0.512-0.512h4zM10.496 24c0.288 0 0.512 0.224 0.512 0.512v1.984c0 0.288-0.224 0.512-0.512 0.512h-4c-0.256 0-0.48-0.224-0.48-0.512v-1.984c0-0.288 0.224-0.512 0.48-0.512h4zM17.504 19.008c0.288 0 0.512 0.224 0.512 0.48v2.016c0 0.256-0.224 0.48-0.512 0.48h-4c-0.288 0-0.512-0.224-0.512-0.48v-2.016c0-0.256 0.224-0.48 0.512-0.48h4zM7.424 16.992l0.512 1.312h0.064l0.64-1.312h1.344l-1.216 2.432 1.248 2.56h-1.376l-0.64-1.28h-0.064l-0.64 1.28h-1.28l1.152-2.432-1.152-2.56h1.408z"></path></svg>
				'
			);
			break;
		case 'xltx':
			$svg = array(
				'font' => 'bb-icon-file-xltx',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-xltx</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16 13.984c1.376 0 2.496 1.12 2.496 2.528v0 8c0 1.376-1.12 2.496-2.496 2.496v0h-8c-1.376 0-2.496-1.12-2.496-2.496v0-8c0-1.408 1.12-2.528 2.496-2.528v0h8zM16 15.008h-8c-0.832 0-1.504 0.672-1.504 1.504v0 8c0 0.8 0.672 1.472 1.504 1.472v0h8c0.832 0 1.504-0.672 1.504-1.472v0-8c0-0.832-0.672-1.504-1.504-1.504v0zM15.488 23.008c0.288 0 0.512 0.224 0.512 0.48s-0.16 0.448-0.416 0.512h-3.072c-0.288 0-0.512-0.224-0.512-0.512 0-0.224 0.192-0.448 0.416-0.48h3.072zM11.008 23.008c0.256 0 0.48 0.224 0.48 0.48s-0.16 0.448-0.384 0.512h-3.104c-0.288 0-0.512-0.224-0.512-0.512 0-0.224 0.192-0.448 0.416-0.48h3.104zM15.488 20.992c0.288 0 0.512 0.224 0.512 0.512 0 0.224-0.16 0.448-0.416 0.48h-3.072c-0.288 0-0.512-0.224-0.512-0.48s0.192-0.448 0.416-0.512h3.072zM8.928 16.512l0.512 1.28h0.064l0.64-1.28h1.344l-1.216 2.4 1.216 2.592h-1.344l-0.64-1.312h-0.064l-0.64 1.312h-1.28l1.152-2.464-1.184-2.528h1.44zM15.488 19.008c0.288 0 0.512 0.224 0.512 0.48s-0.16 0.448-0.416 0.512h-3.072c-0.288 0-0.512-0.224-0.512-0.512 0-0.224 0.192-0.448 0.416-0.48h3.072zM15.488 16.992c0.288 0 0.512 0.224 0.512 0.512 0 0.224-0.16 0.448-0.416 0.48h-3.072c-0.288 0-0.512-0.224-0.512-0.48s0.192-0.448 0.416-0.512h3.072z"></path></svg>
				'
			);
			break;
		case 'xml':
			$svg = array(
				'font' => 'bb-icon-file-xml',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-xml</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM12.384 15.008c0.416 0 0.768 0.352 0.768 0.8v0 0.224h2.72c0.96 0 1.728 0.704 1.824 1.632v6.496c0 0.96-0.832 1.76-1.824 1.76v0h-2.72v0.288c0 0 0 0.032 0 0.032v0.032c-0.032 0.448-0.448 0.768-0.864 0.704v0l-5.568-0.608c-0.416-0.032-0.704-0.384-0.704-0.8v0-9.152c0-0.416 0.288-0.736 0.704-0.8v0l5.568-0.608c0.032 0 0.064 0 0.096 0zM15.872 16.736h-2.72v1.28c1.344 0.288 2.368 1.472 2.368 2.912s-1.024 2.624-2.368 2.944v1.344h2.72c0.544 0 1.024-0.416 1.088-0.928v-6.496c0-0.576-0.48-1.056-1.088-1.056v0zM14.784 21.28h-1.632v1.856c0.864-0.224 1.504-0.96 1.632-1.856zM13.152 18.72v1.856h1.632c-0.128-0.864-0.768-1.6-1.632-1.856z"></path></svg>
				'
			);
			break;
		case 'yaml':
			$svg = array(
				'font' => 'bb-icon-file-yaml',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-yaml</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16.992 11.488c1.344 0 2.432 1.056 2.496 2.336v9.184c0 1.216-0.864 2.24-2.016 2.464-0.224 1.088-1.152 1.952-2.304 2.016h-8.16c-1.344 0-2.432-1.024-2.496-2.336v-9.152c0-1.216 0.864-2.24 2.016-2.464 0.224-1.12 1.152-1.952 2.304-2.048h8.16zM7.232 23.008h-0.48l0.736 1.312v0.8h0.448v-0.8l0.736-1.312h-0.48l-0.448 0.864h-0.032l-0.48-0.864zM9.888 23.008h-0.512l-0.736 2.112h0.448l0.16-0.512h0.736l0.16 0.512h0.48l-0.736-2.112zM11.52 23.008h-0.512v2.112h0.384v-1.408h0.032l0.544 1.28h0.288l0.544-1.28h0.032v1.408h0.416v-2.112h-0.544l-0.576 1.408h-0.032l-0.576-1.408zM14.176 23.008h-0.416v2.112h1.376v-0.384h-0.96v-1.728zM16.992 12.512h-8c-0.64 0-1.184 0.416-1.408 0.992h7.424c1.312 0 2.4 1.024 2.496 2.336v8.576c0.544-0.192 0.928-0.672 0.992-1.28v-9.152c0-0.768-0.576-1.408-1.344-1.472h-0.16zM9.632 23.424l0.256 0.832h-0.544l0.256-0.832h0.032zM15.008 14.496h-8c-0.8 0-1.44 0.608-1.504 1.344v5.152h11.008v-4.992c0-0.768-0.608-1.408-1.376-1.504h-0.128zM14.016 19.008c0.256 0 0.48 0.224 0.48 0.48s-0.16 0.448-0.416 0.512h-6.080c-0.288 0-0.512-0.224-0.512-0.512 0-0.224 0.192-0.448 0.416-0.48h6.112zM14.016 16.992c0.256 0 0.48 0.224 0.48 0.512 0 0.224-0.16 0.448-0.416 0.48h-6.080c-0.288 0-0.512-0.224-0.512-0.48s0.192-0.448 0.416-0.512h6.112z"></path></svg>
				'
			);
			break;
		case 'folder':
			$svg = array(
				'font' => 'bb-icon-folder-stacked',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="36" height="32" viewBox="0 0 36 32"><title>folder-stacked</title><path d="M14.912 0.64c1.44 0 2.784 0.512 3.872 1.472 0.512 0.448 1.184 0.736 1.888 0.8h9.344c3.136 0 5.664 2.464 5.824 5.568v19.744c0 1.984-1.6 3.552-3.552 3.552-0.128 0-0.224 0-0.352 0v0h-27.584c-2.336 0-4.224-1.792-4.352-4.096v-15.744c0-2.304 1.824-4.192 4.096-4.32h0.448v-2.624c0-2.336 1.824-4.224 4.096-4.352h6.272zM28.736 12.288c0-1.088-0.864-2.016-1.952-2.112h-22.432c-0.928 0-1.696 0.704-1.792 1.6v15.68c0 0.928 0.704 1.664 1.6 1.76h24.704c-0.064-0.224-0.096-0.512-0.128-0.768v-16.16zM14.912 3.2h-6.016c-0.928 0-1.696 0.704-1.76 1.6l-0.032 0.192v2.624h19.488c2.528 0 4.576 1.952 4.704 4.448v16.16c0 0.544 0.448 0.992 0.992 0.992 0.512 0 0.928-0.352 0.992-0.864v-19.616c0-1.728-1.344-3.136-3.040-3.264h-9.312c-1.312 0-2.592-0.448-3.616-1.248l-0.224-0.224c-0.544-0.448-1.216-0.736-1.92-0.8h-0.256z"></path></svg>
				'
			);
			break;
		case 'download':
			$svg = array(
				'font' => 'bb-icon-download',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32"><title>download</title><path d="M2.656 22.656v4c0 2.209 1.791 4 4 4v0h18.688c2.209 0 4-1.791 4-4v0-4c0-0.742-0.602-1.344-1.344-1.344s-1.344 0.602-1.344 1.344v0 4c0 0 0 0 0 0 0 0.731-0.584 1.326-1.31 1.344l-0.002 0h-18.688c-0.728-0.018-1.312-0.613-1.312-1.344 0-0 0-0 0-0v0-4c0-0.742-0.602-1.344-1.344-1.344s-1.344 0.602-1.344 1.344v0zM16 19.456l-4.384-4.416c-0.248-0.312-0.628-0.51-1.054-0.51-0.742 0-1.344 0.602-1.344 1.344 0 0.426 0.198 0.805 0.507 1.052l0.003 0.002 5.344 5.344c0.243 0.239 0.576 0.387 0.944 0.387s0.701-0.148 0.944-0.387l-0 0 5.312-5.344c0.181-0.227 0.29-0.518 0.29-0.834 0-0.742-0.602-1.344-1.344-1.344-0.316 0-0.607 0.109-0.837 0.292l0.003-0.002-4.384 4.416zM14.656 2.656v18.688c0 0.742 0.602 1.344 1.344 1.344s1.344-0.602 1.344-1.344v0-18.688c0-0.742-0.602-1.344-1.344-1.344s-1.344 0.602-1.344 1.344v0z"></path></svg>
				'
			);
			break;
		default:
			$svg = array(
				'font' => 'bb-icon-file',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-default</title><path d="M13.728 0h-9.728c-2.208 0-4 1.792-4 4v24c0 2.208 1.792 4 4 4h16c2.208 0 4-1.792 4-4v-17.504c0-1.056-0.416-2.048-1.12-2.784l-6.272-6.496c-0.768-0.768-1.792-1.216-2.88-1.216zM4 1.984h9.728c0.544 0 1.056 0.224 1.44 0.64l6.272 6.464c0.352 0.384 0.576 0.896 0.576 1.408v17.504c0 1.12-0.896 2.016-2.016 2.016h-16c-1.088 0-1.984-0.896-1.984-2.016v-24c0-1.12 0.896-2.016 1.984-2.016z"></path></svg>'
			);
	}

	return apply_filters( 'bp_document_svg_icon', $svg[$type], $extension );
}

/**
 * Return the icon list.
 *
 * @return mixed|void
 * @since BuddyBoss 1.6.0
 */
function bp_video_svg_icon_list() {

	$icons = array(
		'default_1'  => array(
			'icon'  => 'bb-icon-file',
			'title' => __( 'Default', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-default</title><path d="M13.728 0h-9.728c-2.208 0-4 1.792-4 4v24c0 2.208 1.792 4 4 4h16c2.208 0 4-1.792 4-4v-17.504c0-1.056-0.416-2.048-1.12-2.784l-6.272-6.496c-0.768-0.768-1.792-1.216-2.88-1.216zM4 1.984h9.728c0.544 0 1.056 0.224 1.44 0.64l6.272 6.464c0.352 0.384 0.576 0.896 0.576 1.408v17.504c0 1.12-0.896 2.016-2.016 2.016h-16c-1.088 0-1.984-0.896-1.984-2.016v-24c0-1.12 0.896-2.016 1.984-2.016z"></path></svg>'
		),
		'default_2'  => array(
			'icon'  => 'bb-icon-file-zip',
			'title' => __( 'Archive', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-zip</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h2.976v-1.984h2.016v-1.92h-2.016v-2.080h2.016v2.016h1.984v2.048h-1.984v1.92h11.008c1.056 0 1.92-0.832 1.984-1.856l0.032-0.16v-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM10.976 21.952v2.048h-1.984v-2.048h1.984zM11.008 16c0.544 0 0.992 0.448 0.992 0.992v3.008c0 0.544-0.448 0.992-0.992 0.992h-4c-0.544 0-0.992-0.448-0.992-0.992v-3.008c0-0.544 0.448-0.992 0.992-0.992h4zM10.592 16.992h-3.2c-0.192 0-0.352 0.128-0.384 0.32v1.28c0 0.192 0.128 0.352 0.32 0.384l0.064 0.032h3.2c0.192 0 0.352-0.16 0.416-0.32v-1.28c0-0.224-0.192-0.416-0.416-0.416z"></path></svg>'
		),
		'default_3'  => array(
			'icon'  => 'bb-icon-file-mp3',
			'title' => __( 'Audio', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-mp3</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16.96 16c0.128 0 0.288 0.032 0.384 0.128s0.16 0.224 0.16 0.352v0 7.744c0 0.96-0.672 1.824-1.664 2.048-0.96 0.224-1.984-0.192-2.464-1.056-0.448-0.832-0.288-1.888 0.448-2.528s1.856-0.736 2.688-0.224v0-3.040l-6.624 0.704v4.768c0 0.96-0.704 1.792-1.664 2.048-0.96 0.224-1.984-0.192-2.464-1.056-0.48-0.832-0.288-1.888 0.448-2.528s1.824-0.736 2.688-0.224v0-5.856c0-0.256 0.192-0.448 0.448-0.48v0z"></path></svg>'
		),
		'default_4'  => array(
			'icon'  => 'bb-icon-file-html',
			'title' => __( 'Code', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-html</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.736 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM9.536 20.416c0.192 0.224 0.224 0.544 0.032 0.8l-0.032 0.064-2.112 2.048 2.112 2.112c0.192 0.224 0.224 0.544 0.032 0.768l-0.032 0.064c-0.224 0.224-0.544 0.256-0.768 0.064l-0.096-0.064-2.496-2.528c-0.224-0.192-0.224-0.544-0.064-0.768l0.064-0.064 2.528-2.496c0.224-0.224 0.608-0.224 0.832 0zM14.080 20.416c0.224-0.224 0.608-0.224 0.832 0v0l2.528 2.496 0.032 0.064c0.192 0.224 0.16 0.576-0.032 0.768v0l-2.592 2.592c-0.224 0.192-0.544 0.16-0.768-0.064v0l-0.064-0.064c-0.16-0.224-0.16-0.544 0.064-0.768v0l2.080-2.112-2.144-2.112c-0.16-0.256-0.16-0.576 0.064-0.8zM12.768 20.032c0.288 0.096 0.48 0.384 0.416 0.672l-0.032 0.064-1.664 5.28c-0.096 0.32-0.448 0.48-0.736 0.384s-0.448-0.384-0.416-0.672l0.032-0.064 1.664-5.28c0.096-0.32 0.448-0.48 0.736-0.384zM10.496 15.008c0.288 0 0.512 0.224 0.512 0.48v1.024c0 0.256-0.224 0.48-0.512 0.48h-4c-0.256 0-0.48-0.224-0.48-0.48v-1.024c0-0.256 0.224-0.48 0.48-0.48h4z"></path></svg>'
		),
		'default_5'  => array(
			'icon'  => 'bb-icon-file-psd',
			'title' => __( 'Design', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-psd</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM7.072 22.336l4.416 2.528c0.32 0.192 0.704 0.192 0.992 0.032l4.448-2.528 0.896 0.704c0.16 0.16 0.192 0.416 0.064 0.576-0.032 0.032-0.064 0.064-0.128 0.096l-5.536 3.2c-0.128 0.064-0.288 0.064-0.416 0.032l-0.096-0.032-5.44-3.2c-0.192-0.128-0.256-0.352-0.128-0.544 0-0.064 0.032-0.096 0.064-0.096l0.864-0.768zM7.104 20l4.416 2.56c0.32 0.16 0.704 0.16 0.992 0l4.448-2.528 0.896 0.736c0.16 0.128 0.192 0.384 0.064 0.544-0.032 0.064-0.064 0.096-0.128 0.128l-5.536 3.168c-0.128 0.064-0.288 0.096-0.448 0.032l-0.064-0.032-5.44-3.2c-0.192-0.096-0.256-0.352-0.128-0.544 0-0.032 0.032-0.064 0.064-0.096l0.864-0.768zM12.16 15.040l0.064 0.032 5.472 3.104c0.192 0.128 0.288 0.32 0.288 0.544 0 0.16-0.064 0.32-0.192 0.416l-0.096 0.064-5.44 3.104c-0.128 0.096-0.288 0.096-0.448 0.032l-0.064-0.032-5.44-3.104c-0.224-0.096-0.32-0.32-0.288-0.544 0-0.16 0.064-0.32 0.192-0.416l0.096-0.064 5.408-3.104c0.128-0.096 0.288-0.096 0.448-0.032zM11.968 16.256l-4.256 2.432 0.8 0.448 1.856-0.704c0.032 0 0.032 0 0.064 0 0.128-0.032 0.256 0.064 0.32 0.192v0.064l0.064 0.352 1.408-0.352c0.096-0.032 0.16 0 0.224 0 0.128 0.064 0.192 0.224 0.16 0.352l-0.032 0.064-0.896 1.824 0.32 0.192 4.288-2.432-4.32-2.432zM13.408 17.696c0 0.288-0.384 0.512-0.896 0.576-0.512 0.032-0.928-0.16-0.928-0.416-0.032-0.288 0.352-0.544 0.864-0.576s0.928 0.16 0.96 0.416z"></path></svg>'
		),
		'default_6'  => array(
			'icon'  => 'bb-icon-file-png',
			'title' => __( 'Image', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-png</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.12 0-2.016 0.896-2.016 2.016v0 24c0 1.12 0.896 2.016 2.016 2.016v0h16c1.12 0 1.984-0.896 1.984-2.016v0-17.504c0-0.512-0.192-1.024-0.544-1.408v0l-6.272-6.496c-0.384-0.384-0.896-0.608-1.44-0.608v0zM15.68 15.008c1.28 0 2.304 1.024 2.304 2.304v0 7.392c0 1.248-1.024 2.304-2.304 2.304v0h-7.36c-1.28 0-2.336-1.056-2.336-2.304v0-7.392c0-1.28 1.056-2.304 2.336-2.304v0h7.36zM9.152 21.376l-0.096 0.064-2.144 1.6v1.664c0 0.704 0.544 1.312 1.248 1.376h7.52c0.768 0 1.408-0.64 1.408-1.376v0-3.136c-0.896 0.416-2.080 0.992-3.52 1.728-0.448 0.224-0.992 0.192-1.408-0.064l-0.128-0.096-2.368-1.728c-0.16-0.096-0.352-0.096-0.512-0.032zM15.68 15.936h-7.36c-0.768 0-1.408 0.608-1.408 1.376v0 4.48c0.416-0.32 0.96-0.704 1.536-1.152 0.512-0.384 1.152-0.416 1.664-0.096l0.128 0.064 2.368 1.728c0.128 0.096 0.288 0.128 0.448 0.096l0.096-0.064 3.936-1.92v-3.136c0-0.736-0.576-1.312-1.248-1.376h-0.16zM13.376 17.792c0.672 0 1.248 0.544 1.248 1.248s-0.576 1.248-1.248 1.248c-0.704 0-1.248-0.544-1.248-1.248s0.544-1.248 1.248-1.248z"></path></svg>'
		),
		'default_7'  => array(
			'icon'  => 'bb-icon-file-pptx',
			'title' => __( 'Presentation', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-pptx</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-11.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h11.008zM11.84 18.4l4.608 2.848c-0.992 1.376-2.624 2.24-4.448 2.24-2.048 0-3.872-1.12-4.8-2.816l-0.128-0.192 4.768-2.080zM12.224 12.512c2.944 0.096 5.28 2.528 5.28 5.472 0 0.864-0.192 1.632-0.512 2.368l-0.16 0.256-4.608-2.784v-5.312zM11.52 12.512v5.312l-4.704 2.016c-0.192-0.576-0.32-1.216-0.32-1.856 0-2.848 2.208-5.216 5.024-5.472z"></path></svg>'
		),
		'default_8'  => array(
			'icon'  => 'bb-icon-file-xlsx',
			'title' => __( 'Spreadsheet', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-xlsx</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM15.488 13.984c1.408 0 2.528 1.12 2.528 2.528v0 8c0 1.376-1.12 2.496-2.528 2.496v0h-8c-1.376 0-2.496-1.12-2.496-2.496v0-8c0-1.408 1.12-2.528 2.496-2.528v0h8zM9.024 23.072h-3.008v1.44c0 0.768 0.576 1.408 1.344 1.472h1.664v-2.912zM16.992 23.072h-6.976v2.912h5.472c0.8 0 1.44-0.576 1.504-1.344v-1.568zM9.024 19.072h-3.008v3.008h3.008v-3.008zM16.992 19.072h-6.976v3.008h6.976v-3.008zM9.024 15.008h-1.536c-0.8 0-1.472 0.672-1.472 1.504v0 1.568h3.008v-3.072zM15.488 15.008h-5.472v3.072h6.976v-1.568c0-0.8-0.576-1.44-1.344-1.504h-0.16z"></path></svg>'
		),
		'default_9'  => array(
			'icon'  => 'bb-icon-file-txt',
			'title' => __( 'Text', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-txt</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM15.488 24c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.256-0.224 0.48-0.512 0.48h-8.992c-0.256 0-0.48-0.224-0.48-0.48v-0.992c0-0.288 0.224-0.512 0.48-0.512h8.992zM13.504 20.512c0.288 0 0.512 0.224 0.512 0.48v0.992c0 0.288-0.224 0.512-0.512 0.512h-7.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.256 0.224-0.48 0.48-0.48h7.008zM17.504 16.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-11.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h11.008z"></path></svg>'
		),
		'default_10' => array(
			'icon'  => 'bb-icon-file-video',
			'title' => __( 'Video', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-video</title><path d="M13.728 0.096c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 2.080h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.088 0.896 1.984 1.984 1.984v0h16c1.12 0 2.016-0.896 2.016-1.984v0-17.504c0-0.544-0.224-1.024-0.576-1.408v0l-6.272-6.496c-0.384-0.384-0.896-0.608-1.44-0.608v0zM12.704 18.080c1.28 0 2.304 1.056 2.304 2.304v0l1.472-1.088c0.448-0.352 1.056-0.256 1.408 0.192 0.128 0.16 0.192 0.384 0.192 0.608v4.992c0 0.544-0.448 0.992-0.992 0.992-0.224 0-0.448-0.064-0.608-0.192l-1.472-1.088c0 1.248-1.056 2.304-2.304 2.304v0h-5.408c-1.248 0-2.304-1.056-2.304-2.336v0-4.384c0-1.248 1.056-2.304 2.304-2.304v0h5.408zM12.704 19.008h-5.408c-0.736 0-1.376 0.64-1.376 1.376v0 4.384c0 0.768 0.64 1.408 1.376 1.408v0h5.408c0.768 0 1.376-0.64 1.376-1.408v0-4.384c0-0.736-0.608-1.376-1.376-1.376v0zM17.088 20.096l-2.016 1.472v2.016l2.016 1.504v-4.992z"></path></svg>'
		),
	);

	return apply_filters( 'bp_video_svg_icon_list', $icons );
}

function bp_video_multi_array_search( $array, $search ) {

	// Create the result array.
	$result = array();

	// Iterate over each array element.
	foreach ( $array as $key => $value ) {

		// Iterate over each search condition.
		foreach ( $search as $k => $v ) {

			// If the array element does not meet the search condition then continue to the next element.
			if ( ! isset( $value[ $k ] ) || $value[ $k ] != $v ) {
				continue 2;
			}

		}

		// Add the array element's key to the result array.
		$result[] = $key;

	}

	// Return the result array.
	return $result;

}
