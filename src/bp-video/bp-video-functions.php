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
 * @since   BuddyBoss 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Create and upload the video file
 *
 * @return array|null|WP_Error|WP_Post
 * @since BuddyBoss 1.7.0
 */
function bp_video_upload() {
	/**
	 * Make sure user is logged in
	 */
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'not_logged_in', __( 'Please login in order to upload file video.', 'buddyboss' ), array( 'status' => 500 ) );
	}

	/**
	 * Hook before video upload.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bb_before_video_upload_handler' );

	$attachment = bp_video_upload_handler();

	/**
	 * Hook after video upload.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bb_after_video_upload_handler' );

	if ( is_wp_error( $attachment ) ) {
		return $attachment;
	}

	/**
	 * Hook video upload.
	 *
	 * @param mixed $attachment attachment
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bb_video_upload', $attachment );

	// get saved video id.
	$video_id = (int) get_post_meta( $attachment->ID, 'bp_video_id', true );

	$name = $attachment->post_title;

	// Generate video attachment preview link.
	$attachment_id     = 'forbidden_' . $attachment->ID;
	$attachment_url    = home_url( '/' ) . 'bb-attachment-video-preview/' . base64_encode( $attachment_id );
	$video_message_url = ( isset( $_POST ) && isset( $_POST['thread_id'] ) ? home_url( '/' ) . 'bb-attachment-video-preview/' . base64_encode( $attachment_id ) . '/' . base64_encode( 'thread_' . $_POST['thread_id'] ) : '' );

	$file_url = wp_get_attachment_url( $attachment->ID );
	$filetype = wp_check_filetype( $file_url );
	$ext      = $filetype['ext'];
	if ( empty( $ext ) ) {
		$path = parse_url( $file_url, PHP_URL_PATH );
		$ext  = pathinfo( basename( $path ), PATHINFO_EXTENSION );
	}
	// https://stackoverflow.com/questions/40995987/how-to-play-mov-files-in-video-tag/40999234#40999234.
	// https://stackoverflow.com/a/44858204.
	if ( in_array( $ext, array( 'mov', 'm4v' ), true ) ) {
		$ext = 'mp4';
	}

	if (
		! empty( $video_id ) &&
		(
			bp_is_group_messages() ||
			bp_is_messages_component() ||
			(
				! empty( $_POST['component'] ) &&
				'messages' === $_POST['component']
			)
		)
	) {
		$attachment_url    = bb_video_get_symlink( $video_id );
		$video_message_url = $attachment_url;
	}

	$result = array(
		'id'          => (int) $attachment->ID,
		'thumb'       => '',
		'url'         => esc_url( untrailingslashit( $attachment_url ) ),
		'name'        => esc_attr( $name ),
		'ext'         => esc_attr( $ext ),
		'vid_msg_url' => esc_url( untrailingslashit( $video_message_url ) ),
	);

	return $result;
}

/**
 * Video thumbnail upload handler
 *
 * @param string $file_id file.
 *
 * @return array|int|null|WP_Error|WP_Post
 * @since BuddyBoss 1.7.0
 */
function bp_video_thumbnail_upload_handler( $file_id = 'file' ) {

	/**
	 * Include required files
	 */

	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
	}

	if ( ! function_exists( 'media_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/admin.php';
	}

	// Add upload filters.
	bb_video_add_thumb_image_add_upload_filters();

	// Register image sizes.
	bb_video_register_image_sizes();

	/**
	 * Hook before video thumbnail upload.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bb_before_bp_video_thumbnail_upload_handler' );

	$aid = media_handle_upload(
		$file_id,
		0,
		array(),
		array(
			'test_form'            => false,
			'upload_error_strings' => array(
				false,
				__( 'The uploaded file exceeds ', 'buddyboss' ) . bp_media_file_upload_max_size(),
				__( 'The uploaded file was only partially uploaded.', 'buddyboss' ),
				__( 'No file was uploaded.', 'buddyboss' ),
				'',
				__( 'Missing a temporary folder.', 'buddyboss' ),
				__( 'Failed to write file to disk.', 'buddyboss' ),
				__( 'File upload stopped by extension.', 'buddyboss' ),
			),
		)
	);

	// Deregister image sizes.
	bb_video_deregister_image_sizes();

	// Remove upload filters.
	bb_video_add_thumb_image_remove_upload_filters();

	/**
	 * Hook after video thumbnail upload.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bb_after_bp_video_thumbnail_upload_handler' );

	// if has wp error then throw it.
	if ( is_wp_error( $aid ) ) {
		return $aid;
	}

	/**
	 * Hook video thumbnail attachment uploaded.
	 *
	 * @param mixed $aid attachment id
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bp_video_thumbnail_attachment_uploaded', $aid );

	$attachment = get_post( $aid );

	if ( ! empty( $attachment ) ) {
		update_post_meta( $attachment->ID, 'bp_video_thumbnail_upload', true );
		update_post_meta( $attachment->ID, 'bp_video_thumbnail_saved', '0' );

		return $attachment;
	}

	return new WP_Error( 'error_uploading', __( 'Error while uploading thumbnail.', 'buddyboss' ), array( 'status' => 500 ) );

}

/**
 * Create and upload the video thumbnail file
 *
 * @return array|null|WP_Error|WP_Post
 * @since BuddyBoss 1.7.0
 */
function bp_video_thumbnail_upload() {
	/**
	 * Make sure user is logged in
	 */
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'not_logged_in', __( 'Please login in order to upload file media.', 'buddyboss' ), array( 'status' => 500 ) );
	}

	$attachment = bp_video_thumbnail_upload_handler();

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
 * @param Array $existing_mimes carry mime information.
 *
 * @return Array $existing_mimes allowed mime types.
 * @since BuddyBoss 1.7.0
 */
function bp_video_allowed_mimes( $existing_mimes = array() ) {

	if ( bp_is_active( 'media' ) ) {
		$existing_mimes = array();
		$all_extensions = bp_video_extensions_list();
		foreach ( $all_extensions as $extension ) {
			if ( isset( $extension['is_active'] ) && true === (bool) $extension['is_active'] ) {
				$extension_name                      = ltrim( $extension['extension'], '.' );
				$existing_mimes[ "$extension_name" ] = $extension['mime_type'];
			}
		}
	}

	return $existing_mimes;
}

/**
 * Video upload handler
 *
 * @param string $file_id file.
 *
 * @return array|int|null|WP_Error|WP_Post
 * @since BuddyBoss 1.7.0
 */
function bp_video_upload_handler( $file_id = 'file' ) {

	/**
	 * Include required files
	 */

	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
	}

	if ( ! function_exists( 'media_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/admin.php';
	}

	// Add upload filters.
	bb_video_add_upload_filters();

	$aid = media_handle_upload(
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

	// Remove upload filters.
	bb_video_remove_upload_filters();

	// if has wp error then throw it.
	if ( is_wp_error( $aid ) ) {
		return $aid;
	}

	/**
	 * Hook before video attachment uploaded.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bp_video_attachment_uploaded', $aid );

	$attachment = get_post( $aid );

	if ( ! empty( $attachment ) ) {
		update_post_meta( $attachment->ID, 'bp_video_upload', 1 );
		update_post_meta( $attachment->ID, 'bp_video_saved', '0' );

		return $attachment;
	}

	return new WP_Error( 'error_uploading', __( 'Error while uploading video.', 'buddyboss' ), array( 'status' => 500 ) );

}

/**
 * Compress the image
 *
 * @param  string $source source path.
 * @param  string $destination destination path.
 * @param int    $quality quality.
 *
 * @return mixed
 * @since BuddyBoss 1.7.0
 */
function bp_video_compress_image( $source, $destination, $quality = 90 ) {

	$info = @getimagesize( $source ); // phpcs:ignore

	if ( 'image/jpeg' === $info['mime'] ) {
		$image = @imagecreatefromjpeg( $source ); // phpcs:ignore
	} elseif ( 'image/gif' === $info['mime'] ) {
		$image = @imagecreatefromgif( $source ); // phpcs:ignore
	} elseif ( 'image/png' === $info['mime'] ) {
		$image = @imagecreatefrompng( $source ); // phpcs:ignore
	}

	@imagejpeg( $image, $destination, $quality ); // phpcs:ignore

	return $destination;
}

/**
 * Get file video upload max size
 *
 * @return string
 * @since BuddyBoss 1.7.0
 */
function bp_video_file_upload_max_size() {

	/**
	 * Filters file video upload max limit.
	 *
	 * @param mixed $max_size video upload max limit.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_file_upload_max_size', bp_video_allowed_upload_video_size() );
}

/**
 * Format file size units
 *
 * @param   int|float $bytes total bytes.
 * @param bool      $post_string true or not.
 * @param string    $type unit types.
 *
 * @return string
 * @since BuddyBoss 1.7.0
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
	} elseif ( 1 === $bytes ) {
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
 * @param array|string $args See BP_Video::get() for description.
 *
 * @return array $video See BP_Video::get() for description.
 * @since BuddyBoss 1.7.0
 *
 * @see   BP_Video::get() For more information on accepted arguments
 *      and the format of the returned value.
 */
function bp_video_get( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'max'              => false,        // Maximum number of results to return.
			'fields'           => 'all',
			'page'             => 1,            // Page 1 without a per_page will result in no pagination.
			'per_page'         => false,        // results per page.
			'sort'             => 'DESC',       // sort ASC or DESC.
			'order_by'         => false,        // order by.

			'scope'            => false,        // public, friends, groups, personal.

			// want to limit the query.
			'user_id'          => false,
			'activity_id'      => false,
			'album_id'         => false,
			'group_id'         => false,
			'search_terms'     => false,        // Pass search terms as a string.
			'privacy'          => false,        // Privacy of video - public, loggedin, onlyme, friends, grouponly, message.
			'exclude'          => false,        // Comma-separated list of IDs to exclude.
			'in'               => false,        // Comma-separated list of IDs to include.
			'moderation_query' => true,         // Filter to include moderation query.
			'count_total'      => false,        // Whether to count the total number of items in the query.
		),
		'video_get'
	);

	$video = BP_Video::get(
		array(
			'page'             => $r['page'],
			'per_page'         => $r['per_page'],
			'user_id'          => $r['user_id'],
			'activity_id'      => $r['activity_id'],
			'album_id'         => $r['album_id'],
			'group_id'         => $r['group_id'],
			'max'              => $r['max'],
			'sort'             => $r['sort'],
			'order_by'         => $r['order_by'],
			'search_terms'     => $r['search_terms'],
			'scope'            => $r['scope'],
			'privacy'          => $r['privacy'],
			'exclude'          => $r['exclude'],
			'in'               => ! empty( $r['include'] ) ? $r['include'] : $r['in'],
			'count_total'      => $r['count_total'],
			'fields'           => $r['fields'],
			'moderation_query' => $r['moderation_query'],
		)
	);

	/**
	 * Filters the requested video item(s).
	 *
	 * @param BP_Video $video Requested video object.
	 * @param array    $r     Arguments used for the video query.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters_ref_array( 'bp_video_get', array( &$video, &$r ) );
}

/**
 * Fetch specific video items.
 *
 * @param array|string $args {
 *                           All arguments and defaults are shared with BP_Video::get(),
 *                           except for the following:
 *
 * @type string|int|array Single video ID, comma-separated list of IDs,
 *                            or array of IDs.
 * }
 * @return array $activity See BP_Video::get() for description.
 * @since BuddyBoss 1.7.0
 *
 * @see   BP_Video::get() For more information on accepted arguments.
 */
function bp_video_get_specific( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'video_ids'        => false,      // A single video_id or array of IDs.
			'max'              => false,      // Maximum number of results to return.
			'page'             => 1,          // Page 1 without a per_page will result in no pagination.
			'per_page'         => false,      // Results per page.
			'sort'             => 'DESC',     // Sort ASC or DESC.
			'order_by'         => false,      // Sort ASC or DESC.
			'privacy'          => false,      // privacy to filter.
			'album_id'         => false,      // Album ID.
			'user_id'          => false,      // User ID.
			'moderation_query' => true,
		),
		'video_get_specific'
	);

	$get_args = array(
		'in'               => $r['video_ids'],
		'max'              => $r['max'],
		'page'             => $r['page'],
		'per_page'         => $r['per_page'],
		'sort'             => $r['sort'],
		'order_by'         => $r['order_by'],
		'privacy'          => $r['privacy'],
		'album_id'         => $r['album_id'],
		'user_id'          => $r['user_id'],
		'moderation_query' => $r['moderation_query'],
	);

	/**
	 * Filters the requested specific video item.
	 *
	 * @param BP_Video $video    Requested video object.
	 * @param array    $args     Original passed in arguments.
	 * @param array    $get_args Constructed arguments used with request.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_get_specific', BP_Video::get( $get_args ), $args, $get_args );
}

/**
 * Add an video item.
 *
 * @param array|string $args         {
 *                                   An array of arguments.
 *
 * @type int|bool      $id           Pass an video ID to update an existing item, or
 *                                       false to create a new item. Default: false.
 * @type int|bool      $blog_id      ID of the blog Default: current blog id.
 * @type int|bool      $attchment_id ID of the attachment Default: false
 * @type int|bool      $user_id      Optional. The ID of the user associated with the activity
 *                                       item. May be set to false or 0 if the item is not related
 *                                       to any user. Default: the ID of the currently logged-in user.
 * @type string        $title        Optional. The title of the video item.
 * @type int           $album_id     Optional. The ID of the associated album.
 * @type int           $group_id     Optional. The ID of a associated group.
 * @type int           $activity_id  Optional. The ID of a associated activity.
 * @type string        $privacy      Optional. Privacy of the video Default: public
 * @type int           $menu_order   Optional. Menu order the video Default: false
 * @type string        $date_created Optional. The GMT time, in Y-m-d h:i:s format, when
 *                                       the item was recorded. Defaults to the current time.
 * @type string        $error_type   Optional. Error type. Either 'bool' or 'wp_error'. Default: 'bool'.
 * }
 * @return WP_Error|bool|int The ID of the video on success. False on error.
 * @since BuddyBoss 1.7.0
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
			'description'   => '',                      // description of video being added.
			'album_id'      => false,                   // Optional: ID of the album.
			'group_id'      => false,                   // Optional: ID of the group.
			'activity_id'   => false,                   // The ID of activity.
			'message_id'    => false,                   // The ID of message.
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
	$video->description   = wp_filter_nohtml_kses( $r['description'] );
	$video->album_id      = (int) $r['album_id'];
	$video->group_id      = (int) $r['group_id'];
	$video->activity_id   = (int) $r['activity_id'];
	$video->message_id    = (int) $r['message_id'];
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

	$action = bb_filter_input_string( INPUT_POST, 'action' );
	if ( isset( $action ) && 'groups_get_group_members_send_message' === $action ) {
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
	update_post_meta( $video->attachment_id, 'bp_video_id', $video->id );

	/**
	 * Fires at the end of the execution of adding a new video item, before returning the new video item ID.
	 *
	 * @param object $video Video object.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bp_video_add', $video );

	return $video->id;
}

/**
 * Video add handler function
 *
 * @param array  $videos videos array.
 * @param string $privacy privacy.
 * @param string $content content.
 * @param int    $group_id group id.
 * @param int    $album_id album id.
 *
 * @return mixed|void
 * @since BuddyBoss 1.7.0
 */
function bp_video_add_handler( $videos = array(), $privacy = 'public', $content = '', $group_id = false, $album_id = false ) {
	global $bp_video_upload_count, $bp_video_upload_activity_content;
	$video_ids = array();

	$privacy = in_array( $privacy, array_keys( bp_video_get_visibility_levels() ), true ) ? $privacy : 'public';

	if ( ! empty( $videos ) && is_array( $videos ) ) {

		// update count of video for later use.
		$bp_video_upload_count = count( $videos );

		// update the content of videos for later use.
		$bp_video_upload_activity_content = $content;

		// save  video.
		foreach ( $videos as $video ) {

			// Update video if existing.
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
							'message_id'    => $bp_video->message_id,
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
						'privacy'       => ! empty( $video['privacy'] ) && in_array( $video['privacy'], array_merge( array_keys( bp_video_get_visibility_levels() ), array( 'message' ) ), true ) ? $video['privacy'] : $privacy,
					)
				);

				if ( ! empty( $video['js_preview'] ) && ! empty( $video_id ) ) {
					bp_video_preview_image_by_js( $video );
				}

				if ( ! empty( $video_id ) ) {
					bp_video_add_generate_thumb_background_process( $video_id );
				}
			}

			if ( $video_id ) {
				$video_ids[] = $video_id;
			}

		}
	}

	/**
	 * Fires at the end of the execution of adding saving a video item, before returning the new video items in ajax response.
	 *
	 * @param array $video_ids Video IDs.
	 * @param array $videos    Array of video from POST object or in function parameter.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_add_handler', $video_ids, (array) $videos );
}

/**
 * Set the Preview image came via JS.
 *
 * @param array $video video array.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_preview_image_by_js( $video ) {

	/**
	 * Hook before video js preview image.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bb_before_video_preview_image_by_js' );

	// Get Upload directory.
	$upload     = wp_upload_dir();
	$upload_dir = $upload['basedir'];
	$upload_dir = $upload_dir . '/' . $video['id'] . '-video-thumbnail-' . time();

	// If folder not exists then create.
	if ( ! is_dir( $upload_dir ) ) {

		// Create temp folder.
		wp_mkdir_p( $upload_dir );
		chmod( $upload_dir, 0777 );

	}

	// Add upload filters.
	bb_video_image_add_upload_filters();

	// Register image sizes.
	bb_video_register_image_sizes();

	$str         = wp_rand();
	$unique_file = md5( $str );
	$image_name  = preg_replace( '/\\.[^.\\s]{3,4}$/', '', $unique_file );
	$thumbnail   = $upload_dir . '/' . $image_name . '.jpg';
	$file_name   = $image_name . '.jpg';

	$thumbnail = bp_video_base64_to_jpeg( $video['js_preview'], $thumbnail );

	if ( file_exists( $thumbnail ) ) {
		$upload_file = wp_upload_bits( $file_name, null, file_get_contents( $thumbnail ) ); // phpcs:ignore
		if ( ! $upload_file['error'] ) {
			$wp_filetype = wp_check_filetype( $file_name, null );
			$attachment  = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title'     => sanitize_file_name( $image_name ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			);

			$preview_attachment_id = wp_insert_attachment( $attachment, $upload_file['file'] );
			if ( ! is_wp_error( $preview_attachment_id ) ) {
				if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
					require_once ABSPATH . 'wp-admin/includes/image.php';
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
				}
				$attach_data = wp_generate_attachment_metadata( $preview_attachment_id, $upload_file['file'] );
				wp_update_attachment_metadata( $preview_attachment_id, $attach_data );
				update_post_meta( $preview_attachment_id, 'is_video_preview_image', true );
				update_post_meta( $preview_attachment_id, 'video_id', $video['id'] );
				update_post_meta( $preview_attachment_id, 'bp_video_upload', 1 );

				update_post_meta( $video['id'], 'bp_video_preview_thumbnail_id', $preview_attachment_id );
				$auto_generated_thumbnails = get_post_meta( $video['id'], 'video_preview_thumbnails', true );
				$default_images            = isset( $auto_generated_thumbnails['default_images'] ) && ! empty( $auto_generated_thumbnails['default_images'] ) ? $auto_generated_thumbnails['default_images'] : array();
				$default_images            = array_merge( $default_images, array( $preview_attachment_id ) );
				$thumbnail_images          = array(
					'default_images' => $default_images,
					'custom_image'   => isset( $auto_generated_thumbnails['custom_image'] ) && ! empty( $auto_generated_thumbnails['custom_image'] ) ? $auto_generated_thumbnails['custom_image'] : array(),
				);
				update_post_meta( $video['id'], 'video_preview_thumbnails', $thumbnail_images );
			}
		}
	}

	// Remove upload filters.
	bb_video_image_remove_upload_filters();

	// Deregister image sizes.
	bb_video_deregister_image_sizes();

	bp_core_remove_temp_directory( $upload_dir );

	/**
	 * Hook after video js preview image.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bb_after_video_preview_image_by_js' );

}

/**
 * Put the video in background process to create thumbnails.
 *
 * @param int $video_id Video id.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_add_generate_thumb_background_process( $video_id ) {

	if ( class_exists( 'FFMpeg\FFMpeg' ) ) {
		global $bb_background_updater;
		$ffmpeg = bb_video_check_is_ffmpeg_binary();

		if ( ! empty( $ffmpeg->error ) && ! empty( trim( $ffmpeg->error ) ) ) {
			return;
		}

		$video = new BP_Video( $video_id );

		if ( ! empty( $video->privacy ) && in_array(
			$video->privacy,
			array(
				'forums',
				'comment',
				'message',
			),
			true
		) ) {
			return;
		}

		/**
		 * Hook for before background process create.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param BP_Video $video Video details.
		 */
		do_action( 'bp_video_before_background_process_create', $video );

		$is_auto_generated_thumbnails = get_post_meta( $video->attachment_id, 'video_preview_thumbnails', true );
		$is_default_images            = isset( $is_auto_generated_thumbnails['default_images'] ) && ! empty( $is_auto_generated_thumbnails['default_images'] ) ? $is_auto_generated_thumbnails['default_images'] : array();

		if ( count( $is_default_images ) <= 1 ) {

			$bb_background_updater->push_to_queue(
				array(
					'type'     => 'video',
					'group'    => 'video_thumbnail',
					'data_id'  => $video_id,
					'priority' => 5,
					'callback' => 'bp_video_background_create_thumbnail',
					'args'     => array( $video ),
				)
			);

			$bb_background_updater->save()->schedule_event();

		}

		/**
		 * Hook for After background process create.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param BP_Video $video video detail.
		 */
		do_action( 'bp_video_after_background_process_create', $video );

	}
}

/**
 * Convert base64 to image.
 *
 * @param string $base64_string base64 string.
 * @param string $output_file   path to store the file.
 *
 * @return string image path.
 * @since BuddyBoss 1.7.0
 */
function bp_video_base64_to_jpeg( $base64_string, $output_file ) {
	// open the output file for writing.
	$ifp = fopen( $output_file, 'wb' ); // phpcs:ignore

	// split the string on commas
	// $data[ 0 ] == "data:image/png;base64"
	// $data[ 1 ] == <actual base64 string>.
	$data = explode( ',', $base64_string );

	// we could add validation here with ensuring count( $data ) > 1.
	fwrite( $ifp, base64_decode( $data[1] ) ); // phpcs:ignore

	// clean up the file resource.
	fclose( $ifp ); // phpcs:ignore

	return $output_file;
}

/**
 * Generate the video thumbnail.
 *
 * @param BP_Video $video data of video.
 */
function bp_video_background_create_thumbnail( $video ) {

	$error = '';
	global $bp_background_updater;

	if ( ! class_exists( 'FFMpeg\FFMpeg' ) ) {
		return;
	} elseif ( class_exists( 'FFMpeg\FFMpeg' ) ) {
		$ffmpeg = bb_video_check_is_ffmpeg_binary();
		if ( ! empty( trim( $ffmpeg->error ) ) ) {
			return;
		}
	}

	$video_attachment_id = $video->attachment_id;

	if ( empty( trim( $ffmpeg->error ) ) ) {

		try {

			/**
			 * Hook before video thumbnail create in background.
			 *
			 * @since BuddyBoss 1.7.0
			 */
			do_action( 'bb_try_before_video_background_create_thumbnail', $video );

			$ff_probe = bb_video_check_is_ffprobe_binary();
			$duration = 0;

			if (
				! empty( $ff_probe->ffprob->streams( get_attached_file( $video_attachment_id ) )->videos() ) &&
				! empty( $ff_probe->ffprob->streams( get_attached_file( $video_attachment_id ) )->videos()->first() )
			) {
				$duration = $ff_probe->ffprob->streams( get_attached_file( $video_attachment_id ) )->videos()->first()->get( 'duration' );
			}

			$is_auto_generated_thumbnails = get_post_meta( $video->attachment_id, 'video_preview_thumbnails', true );
			$is_default_images            = isset( $is_auto_generated_thumbnails['default_images'] ) && ! empty( $is_auto_generated_thumbnails['default_images'] ) ? $is_auto_generated_thumbnails['default_images'] : array();

			if ( ! empty( $duration ) && count( $is_default_images ) <= 1 ) {

				/**
				 * Hook for before background thumbnail create.
				 *
				 * @since BuddyBoss 1.7.0
				 *
				 * @param BP_Video $video Video object.
				 */
				do_action( 'bp_video_before_background_create_thumbnail', $video );

				// Update video attachment meta.
				update_post_meta( $video_attachment_id, 'duration', $duration );

				// Generate 2 random images for video cover.
				$numbers = range( 1, (int) $duration );
				shuffle( $numbers );
				$random_seconds = array_slice( $numbers, 0, 2 );

				// Get Upload directory.
				$upload     = wp_upload_dir();
				$upload_dir = $upload['basedir'];
				$upload_dir = $upload_dir . '/' . $video_attachment_id . '-video-thumbnail-' . time();

				// If folder not exists then create.
				if ( ! is_dir( $upload_dir ) ) {

					// Create temp folder.
					wp_mkdir_p( $upload_dir );
					chmod( $upload_dir, 0777 );

				}

				/**
				 * Hook for before background thumbnail create.
				 *
				 * @since BuddyBoss 1.7.0
				 *
				 * @param BP_Video $video Video object.
				 */
				do_action( 'bb_video_before_preview_generate' );

				// Add upload filters.
				bb_video_image_add_upload_filters();

				// Register image sizes.
				bb_video_register_image_sizes();

				$thumbnail_list = array();
				foreach ( $random_seconds as $second ) {

					$str          = wp_rand();
					$unique_file  = md5( $str );
					$image_name   = preg_replace( '/\\.[^.\\s]{3,4}$/', '', $unique_file );
					$thumbnail    = $upload_dir . '/' . $image_name . '.jpg';
					$file_name    = $image_name . '.jpg';
					$thumb_ffmpeg = bb_video_check_is_ffmpeg_binary();
					$video_thumb  = $thumb_ffmpeg->ffmpeg->open( get_attached_file( $video_attachment_id ) );
					$thumb_frame  = $video_thumb->frame( FFMpeg\Coordinate\TimeCode::fromSeconds( $second ) );

					$error = '';
					try {
						$saved = $thumb_frame->save( $thumbnail );
					} catch ( Exception $saved ) {
						$error = 'error';
					}

					unset( $thumb_ffmpeg );
					unset( $video_thumb );
					unset( $thumb_frame );

					if ( file_exists( $thumbnail ) ) {
						$upload_file = wp_upload_bits( $file_name, null, file_get_contents( $thumbnail ) ); // phpcs:ignore
						if ( ! $upload_file['error'] ) {
							$wp_filetype = wp_check_filetype( $file_name, null );
							$attachment  = array(
								'post_mime_type' => $wp_filetype['type'],
								'post_title'     => sanitize_file_name( $image_name ),
								'post_content'   => '',
								'post_status'    => 'inherit',
							);

							$preview_attachment_id = wp_insert_attachment( $attachment, $upload_file['file'] );

							if ( ! is_wp_error( $preview_attachment_id ) ) {
								if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
									require_once ABSPATH . 'wp-admin/includes/image.php';
									require_once ABSPATH . 'wp-admin/includes/file.php';
									require_once ABSPATH . 'wp-admin/includes/media.php';
								}
								$attach_data = wp_generate_attachment_metadata( $preview_attachment_id, $upload_file['file'] );
								wp_update_attachment_metadata( $preview_attachment_id, $attach_data );
								$thumbnail_list[] = $preview_attachment_id;
								update_post_meta( $preview_attachment_id, 'is_video_preview_image', true );
								update_post_meta( $preview_attachment_id, 'video_id', $video->id );
								update_post_meta( $preview_attachment_id, 'bp_video_upload', 1 );
							}
						}
					}
				}

				// Remove upload filters.
				bb_video_image_remove_upload_filters();

				// Deregister image sizes.
				bb_video_deregister_image_sizes();

				bp_core_remove_temp_directory( $upload_dir );

				if ( is_array( $thumbnail_list ) && ! empty( $thumbnail_list ) ) {

					$thumbnail_images = get_post_meta( $video_attachment_id, 'video_preview_thumbnails', true );
					if ( isset( $thumbnail_images['default_images'] ) && ! empty( $thumbnail_images['default_images'] ) ) {
						$thumbnail_list = array_merge( $thumbnail_images['default_images'], $thumbnail_list );
					}
					$updated_thumbnail_images = array(
						'default_images' => $thumbnail_list,
						'custom_image'   => ( isset( $thumbnail_images['custom_image'] ) && ! empty( $thumbnail_images['custom_image'] ) ) ? $thumbnail_images['custom_image'] : '',
					);
					update_post_meta( $video_attachment_id, 'video_preview_thumbnails', $updated_thumbnail_images );
					$get_existing = get_post_meta( $video_attachment_id, 'bp_video_preview_thumbnail_id', true );
					if ( ! $get_existing ) {
						update_post_meta( $video_attachment_id, 'bp_video_preview_thumbnail_id', current( $thumbnail_list ) );
					}
					update_post_meta( $video_attachment_id, 'bb_ffmpeg_preview_generated', 'yes' );
				}

				/**
				 * Hook for After preview generate.
				 *
				 * @since BuddyBoss 1.7.0
				 */
				do_action( 'bb_video_after_preview_generate' );

				/**
				 * Hook for After background thumbnail create.
				 *
				 * @since BuddyBoss 1.7.0
				 *
				 * @param BP_Video $video_id Video object.
				 */
				do_action( 'bp_video_after_background_create_thumbnail', $video );
			} else {
				update_post_meta( $video_attachment_id, 'bb_ffmpeg_preview_generated', ( ! empty( $is_default_images ) ? 'yes' : 'no' ) );
			}

			/**
			 * Hook for After video background thumbnail create.
			 *
			 * @since BuddyBoss 1.7.0
			 *
			 * @param BP_Video $video_id Video object.
			 */
			do_action( 'bb_try_after_video_background_create_thumbnail', $video );

		} catch ( Exception $ex ) {
			update_post_meta( $video_attachment_id, 'bb_ffmpeg_preview_generated', 'no' );
		}
	} else {
		update_post_meta( $video_attachment_id, 'bb_ffmpeg_preview_generated', 'no' );
	}
}

/**
 * Delete video.
 *
 * @param array|string $args To delete specific video items, use
 *                           $args = array( 'id' => $ids ); Otherwise, to use
 *                           filters for item deletion, the argument format is
 *                           the same as BP_Video::get().
 *                           See that method for a description.
 * @param bool         $from Context of deletion from. ex. attachment, activity etc.
 *
 * @return bool|int The ID of the video on success. False on error.
 * @since BuddyBoss 1.7.0
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
	 * @param array $args Array of arguments to be used with the video deletion.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bp_before_video_delete', $args );

	$video_ids_deleted = BP_Video::delete( $args, $from );
	if ( empty( $video_ids_deleted ) ) {
		return false;
	}

	/**
	 * Fires after the video item has been deleted.
	 *
	 * @param array $args Array of arguments used with the video deletion.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bp_video_delete', $args );

	/**
	 * Fires after the video item has been deleted.
	 *
	 * @param array $video_ids_deleted Array of affected video item IDs.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bp_video_deleted_videos', $video_ids_deleted );

	return true;
}

/**
 * Completely remove a user's video data.
 *
 * @param int $user_id ID of the user whose video is being deleted.
 *
 * @return bool
 * @since BuddyBoss 1.7.0
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
	 * @param int $user_id ID of the user being deleted.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bp_video_remove_all_user_data', $user_id );
}

add_action( 'wpmu_delete_user', 'bp_video_remove_all_user_data' );
add_action( 'delete_user', 'bp_video_remove_all_user_data' );

/**
 * Get video visibility levels out of the $bp global.
 *
 * @return array
 * @since BuddyBoss 1.7.0
 */
function bp_video_get_visibility_levels() {

	/**
	 * Filters the video visibility levels out of the $bp global.
	 *
	 * @param array $visibility_levels Array of visibility levels.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_get_visibility_levels', buddypress()->video->visibility_levels );
}

/**
 * Return the video activity.
 *
 * @param  int $activity_id activity id.
 *
 * @return object|boolean The video activity object or false.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
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
	 * @param object $activity The video activity.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_get_video_activity', $result['activities'][0] );
}

/**
 * Get the video count of a user.
 *
 * @return int video count of the user.
 * @since BuddyBoss 1.7.0
 */
function bp_video_get_total_video_count() {

	add_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_personal_scope', 20 );
	bp_has_video( bp_ajax_querystring( 'video' ) . '&count_total=true&fields=ids' );
	$count = $GLOBALS['video_template']->total_video_count;
	remove_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_personal_scope', 20 );

	/**
	 * Filters the total video count for a given user.
	 *
	 * @param int $count Total video count for a given user.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_get_total_video_count', (int) $count );
}

/**
 * Get the groups video count of a given user.
 *
 * @return int video count of the user.
 * @since BuddyBoss 1.7.0
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
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_get_user_total_group_video_count', (int) $count );
}

/**
 * Get the video count of a given group.
 *
 * @param int $group_id ID of the group whose video are being counted.
 *
 * @return int video count of the group.
 * @since BuddyBoss 1.7.0
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
	 * @param int $count Total video count for a given group.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_get_total_group_video_count', (int) $count );
}

/**
 * Get the album count of a given group.
 *
 * @param int $group_id ID of the group whose album are being counted.
 *
 * @return int album count of the group.
 * @since BuddyBoss 1.7.0
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
	 * @param int $count Total album count for a given group.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_get_total_group_album_count', (int) $count );
}

/**
 * Return the total video count in your BP instance.
 *
 * @return int Video count.
 * @since BuddyBoss 1.7.0
 */
function bp_get_total_video_count() {

	add_filter( 'bp_ajax_querystring', 'bp_video_object_results_video_all_scope', 20 );
	bp_has_video( bp_ajax_querystring( 'video' ) );
	remove_filter( 'bp_ajax_querystring', 'bp_video_object_results_video_all_scope', 20 );
	$count = $GLOBALS['video_template']->total_video_count;

	/**
	 * Filters the total number of video.
	 *
	 * @param int $count Total number of video.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_total_video_count', (int) $count );
}

/**
 * Video results all scope.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param string $querystring query string.
 *
 * @return string
 */
function bp_video_object_results_video_all_scope( $querystring ) {
	$querystring = bp_parse_args( $querystring );

	$querystring['scope']       = 'all';
	$querystring['page']        = 1;
	$querystring['per_page']    = 1;
	$querystring['user_id']     = 0;
	$querystring['count_total'] = true;

	return http_build_query( $querystring );
}


/**
 * Object template results video personal scope.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param string $querystring query string.
 *
 * @return string
 */
function bp_video_object_template_results_video_personal_scope( $querystring ) {
	$querystring = bp_parse_args( $querystring );

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
 * @since BuddyBoss 1.7.0
 *
 * @param string $querystring query string.
 *
 * @return string
 */
function bp_video_object_template_results_video_groups_scope( $querystring ) {
	$querystring = bp_parse_args( $querystring );

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
 * @param array|string $args See BP_Video_Album::get() for description.
 *
 * @return array $activity See BP_Video_Album::get() for description.
 * @since BuddyBoss 1.7.0
 *
 * @see   BP_Video_Album::get() For more information on accepted arguments
 *      and the format of the returned value.
 */
function bp_video_album_get( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'max'              => false,           // Maximum number of results to return.
			'fields'           => 'all',
			'page'             => 1,               // Page 1 without a per_page will result in no pagination.
			'per_page'         => false,           // results per page.
			'sort'             => 'DESC',          // sort ASC or DESC.

			'search_terms'     => false,           // Pass search terms as a string.
			'exclude'          => false,           // Comma-separated list of activity IDs to exclude.
			// want to limit the query.
			'user_id'          => false,
			'group_id'         => false,
			'privacy'          => false,           // privacy of album.
			'moderation_query' => true,            // Filter to include moderation query.
			'count_total'      => false,
		),
		'video_album_get'
	);

	$album = BP_Video_Album::get(
		array(
			'page'             => $r['page'],
			'per_page'         => $r['per_page'],
			'user_id'          => $r['user_id'],
			'group_id'         => $r['group_id'],
			'privacy'          => $r['privacy'],
			'max'              => $r['max'],
			'sort'             => $r['sort'],
			'search_terms'     => $r['search_terms'],
			'exclude'          => $r['exclude'],
			'count_total'      => $r['count_total'],
			'fields'           => $r['fields'],
			'moderation_query' => $r['moderation_query'],
		)
	);

	/**
	 * Filters the requested album item(s).
	 *
	 * @param BP_Video $album Requested video object.
	 * @param array    $r     Arguments used for the album query.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters_ref_array( 'bp_video_album_get', array( &$album, &$r ) );
}

/**
 * Fetch specific albums.
 *
 * @param array|string $args {
 *                           All arguments and defaults are shared with BP_Video_Album::get(),
 *                           except for the following:
 *
 * @type string|int|array Single album ID, comma-separated list of IDs,
 *                            or array of IDs.
 * }
 * @return array $albums See BP_Video_Album::get() for description.
 * @since BuddyBoss 1.7.0
 *
 * @see   BP_Video_Album::get() For more information on accepted arguments.
 */
function bp_video_album_get_specific( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'album_ids'         => false,      // A single album id or array of IDs.
			'max'               => false,      // Maximum number of results to return.
			'page'              => 1,          // Page 1 without a per_page will result in no pagination.
			'per_page'          => false,      // Results per page.
			'sort'              => 'DESC',     // Sort ASC or DESC.
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
	 * @param BP_Video $album    Requested video object.
	 * @param array    $args     Original passed in arguments.
	 * @param array    $get_args Constructed arguments used with request.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_album_get_specific', BP_Video_Album::get( $get_args ), $args, $get_args );
}

/**
 * Add album item.
 *
 * @param array|string $args         {
 *                                   An array of arguments.
 *
 * @type int|bool      $id           Pass an activity ID to update an existing item, or
 *                                       false to create a new item. Default: false.
 * @type int|bool      $user_id      Optional. The ID of the user associated with the album
 *                                       item. May be set to false or 0 if the item is not related
 *                                       to any user. Default: the ID of the currently logged-in user.
 * @type int           $group_id     Optional. The ID of the associated group.
 * @type string        $title        The title of album.
 * @type string        $privacy      The privacy of album.
 * @type string        $date_created Optional. The GMT time, in Y-m-d h:i:s format, when
 *                                       the item was recorded. Defaults to the current time.
 * @type string        $error_type   Optional. Error type. Either 'bool' or 'wp_error'. Default: 'bool'.
 * }
 * @return WP_Error|bool|int The ID of the album on success. False on error.
 * @since BuddyBoss 1.7.0
 */
function bp_video_album_add( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'id'           => false,
			// Pass an existing album ID to update an existing entry.
			'user_id'      => bp_loggedin_user_id(),
			// User ID.
			'group_id'     => false,
			// attachment id.
			'title'        => '',
			// title of album being added.
			'privacy'      => 'public',
			// Optional: privacy of the video e.g. public.
			'date_created' => bp_core_current_time(),
			// The GMT time that this video was recorded.
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
	 * @param object $album Album object.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bp_video_album_add', $album );

	return $album->id;
}

/**
 * Delete album item.
 *
 * @param array|string $args To delete specific album items, use
 *                           $args = array( 'id' => $ids ); Otherwise, to use
 *                           filters for item deletion, the argument format is
 *                           the same as BP_Video_Album::get().
 *                           See that method for a description.
 *
 * @return bool True on Success. False on error.
 * @since BuddyBoss 1.7.0
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
	 * @param array $args Array of arguments to be used with the album deletion.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bp_before_video_album_delete', $args );

	$album_ids_deleted = BP_Video_Album::delete( $args );
	if ( empty( $album_ids_deleted ) ) {
		return false;
	}

	/**
	 * Fires after the album item has been deleted.
	 *
	 * @param array $args Array of arguments used with the album deletion.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bp_video_album_delete', $args );

	/**
	 * Fires after the album item has been deleted.
	 *
	 * @param array $album_ids_deleted Array of affected album item IDs.
	 *
	 * @since BuddyBoss 1.7.0
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
 * @param int $album_id ID of the album.
 *
 * @return BP_Video_Album $album The album object.
 * @since BuddyBoss 1.7.0
 */
function albums_get_video_album( $album_id ) {

	$album = new BP_Video_Album( $album_id );

	/**
	 * Filters a single album object.
	 *
	 * @param BP_Video_Album $album Single album object.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'albums_get_video_album', $album );
}

/**
 * Check album access for current user or guest
 *
 * @param int $album_id album id.
 *
 * @return bool
 * @since BuddyBoss 1.7.0
 */
function albums_check_video_album_access( $album_id ) {

	$album = albums_get_video_album( $album_id );

	if ( ! empty( $album->group_id ) ) {
		return false;
	}

	if ( ! empty( $album->privacy ) ) {

		if ( 'public' === $album->privacy ) {
			return true;
		}

		if ( 'loggedin' === $album->privacy && is_user_logged_in() ) {
			return true;
		}

		if ( bp_is_active( 'friends' ) && is_user_logged_in() && 'friends' === $album->privacy && friends_check_friendship( get_current_user_id(), $album->user_id ) ) {
			return true;
		}

		if ( bp_is_my_profile() && bp_loggedin_user_id() === (int) $album->user_id && 'onlyme' === $album->privacy ) {
			return true;
		}
	}

	return false;
}

/**
 * Delete orphaned attachments uploaded
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_delete_orphaned_attachments() {

	remove_filter( 'posts_join', 'bp_media_filter_attachments_query_posts_join', 10 );
	remove_filter( 'posts_where', 'bp_media_filter_attachments_query_posts_where', 10 );

	/**
	 * Removed the WP_Query because it's conflicting with other plugins which hare using non-standard way using the
	 * pre_get_posts & ajax_query_attachments_args hook & filter and it's getting all the media ids and it will remove
	 * all the media from Media Library.
	 *
	 * @since BuddyBoss 1.7.6
	 */
	$args = array(
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'post_status'    => 'inherit',
		'post_type'      => 'attachment',
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'key'   => 'bp_video_saved',
				'value' => '0',
			),
			array(
				'key'     => 'bb_media_draft',
				'compare' => 'NOT EXISTS',
				'value'   => '',
			),
		),
		'date_query'     => array(
			array(
				'column' => 'post_date_gmt',
				'before' => '6 hours ago',
			),
		),
	);

	$video_wp_query = new WP_query( $args );
	if ( 0 < $video_wp_query->found_posts ) {
		foreach ( $video_wp_query->posts as $post_id ) {
			wp_delete_attachment( $post_id, true );
		}
	}

	wp_reset_postdata();
	wp_reset_query();

	add_filter( 'posts_join', 'bp_media_filter_attachments_query_posts_join', 10, 2 );
	add_filter( 'posts_where', 'bp_media_filter_attachments_query_posts_where', 10, 2 );
}

/**
 * Download an image from the specified URL and attach it to a post.
 *
 * @param string $file The URL of the image to download.
 *
 * @return int|void
 * @since BuddyBoss 1.7.0
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
	$file = preg_replace( '/^:*?\/\//', $protocol = strtolower( substr( $_SERVER['SERVER_PROTOCOL'], 0, strpos( $_SERVER['SERVER_PROTOCOL'], '/' ) ) ) . '://', $file ); // phpcs:ignore

	if ( ! function_exists( 'download_url' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
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
 * This handles a sideloaded file in the same way as an uploaded file is handled by {@link media_handle_upload()}
 *
 * @param array $file_array Array similar to a {@link $_FILES} upload array.
 * @param array $post_data  allows you to overwrite some of the attachment.
 *
 * @return int|object The ID of the attachment or a WP_Error on failure
 * @since BuddyBoss 1.7.0
 */
function bp_video_handle_sideload( $file_array, $post_data = array() ) {

	$overrides = array( 'test_form' => false );

	$time = current_time( 'mysql' );
	$post = get_post();
	if ( $post ) {
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
	if ( $image_meta = @wp_read_image_metadata( $file ) ) { // phpcs:ignore
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

	// Save the attachment metadata.
	$id = wp_insert_attachment( $attachment, $file );

	if ( ! is_wp_error( $id ) ) {
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
	}

	return $id;
}

/**
 * Function to add the content on top of video listing
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_directory_page_content() {

	$page_ids = bp_core_get_directory_page_ids();

	if ( ! empty( $page_ids['video'] ) ) {
		$video_page_content = get_post_field( 'post_content', $page_ids['video'] );
		echo apply_filters( 'the_content', $video_page_content ); // phpcs:ignore
	}
}

add_action( 'bp_before_directory_video', 'bp_video_directory_page_content' );

/**
 * Get video id for the attachment.
 *
 * @param int $attachment_id attachment id.
 *
 * @return array|bool
 * @since BuddyBoss 1.7.0
 */
function bp_get_attachment_video_id( $attachment_id = 0 ) {
	global $bp, $wpdb;

	if ( ! $attachment_id ) {
		return false;
	}

	$attachment_video_id = (int) $wpdb->get_var( "SELECT DISTINCT m.id FROM {$bp->video->table_name} m WHERE m.attachment_id = {$attachment_id}" ); //phpcs:ignore

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
 * @since BuddyBoss 1.7.0
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

		if ( bp_is_my_profile() || bp_loggedin_user_id() === (int) $user_id ) {
			$privacy[] = 'onlyme';

			if ( bp_is_active( 'friends' ) ) {
				$privacy[] = 'friends';
			}
		}

		if ( ! in_array( 'friends', $privacy ) && bp_is_active( 'friends' ) ) { // phpcs:ignore

			// get the login user id.
			$current_user_id = bp_loggedin_user_id();

			// check if the login user is friends of the display user.
			$is_friend = friends_check_friendship( $current_user_id, $user_id );

			/**
			 * Check if the login user is friends of the display user
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

	/**
	 * Filter to video query privacy.
	 *
	 * @param array  $privacy  proivacy array
	 * @param int    $user_id  user id
	 * @param int    $group_id group id
	 * @param string $scope    scope
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_query_privacy', $privacy, $user_id, $group_id, $scope );
}

/**
 * Update activity video privacy based on activity.
 *
 * @param int    $activity_id Activity ID.
 * @param string $privacy     Privacy.
 *
 * @since BuddyBoss 1.7.0
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
 * @param string $scope Default scope.
 *
 * @return string
 * @since BuddyBoss 1.7.0
 */
function bp_video_default_scope( $scope = 'all' ) {

	$new_scope = array();

	$allowed_scopes = array( 'public', 'all' );
	if ( is_user_logged_in() && bp_is_active( 'friends' ) && bp_is_profile_video_support_enabled() ) {
		$allowed_scopes[] = 'friends';
	}

	if ( bp_is_active( 'groups' ) && bp_is_group_video_support_enabled() ) {
		$allowed_scopes[] = 'groups';
	}

	if ( ( is_user_logged_in() || bp_is_user_video() ) && bp_is_profile_video_support_enabled() ) {
		$allowed_scopes[] = 'personal';
	}

	if ( ( 'all' === $scope || empty( $scope ) ) && bp_is_video_directory() ) {

		$new_scope[] = 'public';

		if ( is_user_logged_in() && bp_is_active( 'friends' ) && bp_is_profile_video_support_enabled() ) {
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
	} elseif ( bp_is_active( 'groups' ) && bp_is_group() && ( 'all' === $scope || empty( $scope ) ) ) {
		$new_scope[] = 'groups';
	}

	if ( empty( $new_scope ) ) {
		$new_scope = (array) ( ! is_array( $scope ) ? explode( ',', trim( $scope ) ) : $scope );
	}

	// Remove duplicate scope if added.
	$new_scope = array_unique( $new_scope );

	// Remove all unwanted scope.
	$new_scope = array_intersect( $allowed_scopes, $new_scope );

	/**
	 * Filter to update default scope.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	$new_scope = apply_filters( 'bp_video_default_scope', $new_scope );

	return implode( ',', $new_scope );

}

/**
 * Get the thread id based on video.
 *
 * @param string|int $video_id video id.
 *
 * @return mixed|void
 */
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

		if ( empty( $thread_id ) ) {
			$video_object = new BP_Video( $video_id );
			if ( ! empty( $video_object->attachment_id ) ) {
				$thread_id = get_post_meta( $video_object->attachment_id, 'thread_id', true );
			}
		}
	}

	/**
	 * Filter to get video thread id.
	 *
	 * @param int $thread_id  user id
	 * @param int $video_id   video id
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_get_thread_id', $thread_id, $video_id );

}

/**
 * Return download link of the video.
 *
 * @param int $attachment_id attachment id.
 * @param int $video_id video id.
 *
 * @return mixed|void
 * @since BuddyBoss 1.7.0
 */
function bp_video_download_link( $attachment_id, $video_id ) {

	if ( empty( $attachment_id ) ) {
		return;
	}

	$link = site_url() . '/?attachment_id=' . $attachment_id . '&video_type=video&download_video_file=1&video_file=' . $video_id;

	/**
	 * Filter to get video download link.
	 *
	 * @param string $link  download link
	 * @param int    $attachment_id   atttachment id
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_download_link', $link, $attachment_id );

}

/**
 * Check if user have a access to download the file. If not redirect to homepage.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_download_url_file() {
	$attachment_id       = bb_filter_input_string( INPUT_GET, 'attachment_id' );
	$download_video_file = bb_filter_input_string( INPUT_GET, 'download_video_file' );
	$video_file          = bb_filter_input_string( INPUT_GET, 'video_file' );
	$video_type          = bb_filter_input_string( INPUT_GET, 'video_type' );
	$can_download_btn    = false;

	if ( isset( $attachment_id ) && isset( $download_video_file ) && isset( $video_file ) && isset( $video_type ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'album' !== $video_type ) {
			$video_privacy    = bb_media_user_can_access( $video_file, 'video', $attachment_id ); // phpcs:ignore WordPress.Security.NonceVerification
			$can_download_btn = true === (bool) $video_privacy['can_download'];
		}
		if ( $can_download_btn ) {
			bp_video_download_file( $attachment_id, $video_type ); // phpcs:ignore WordPress.Security.NonceVerification
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
 *
 * @return array
 */
function bp_video_ie_nocache_headers_fix( $headers ) {
	if ( is_ssl() && ! empty( $GLOBALS['is_IE'] ) ) {
		$headers['Cache-Control'] = 'private';
		unset( $headers['Pragma'] );
	}

	return $headers;
}

/**
 * Get the frorum id based on video.
 *
 * @param int|string $video_id video id.
 *
 * @return mixed|void
 */
function bp_video_get_forum_id( $video_id ) {

	if ( ! bp_is_active( 'forums' ) ) {
		return;
	}

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
				if ( in_array( $video_id, $video_ids ) ) { // phpcs:ignore
					$forum_id = $post_id;
					break;
				}
			}
		}
	}

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
					if ( in_array( $video_id, $video_ids ) ) { // phpcs:ignore
						$forum_id = bbp_get_topic_forum_id( $post_id );
						break;
					}
				}
			}
		}
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
						if ( in_array( $video_id, $video_ids ) ) { // phpcs:ignore
							$forum_id = bbp_get_reply_forum_id( $post_id );
							break;
						}
					}
				}
			}
		}
	}

	/**
	 * Filter to get video forum id.
	 *
	 * @param int $forum_id  forum id
	 * @param int $video_id  video id
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_get_forum_id', $forum_id, $video_id );

}


/**
 * Return the extension of the attachment.
 *
 * @param int $attachment_id attachment id.
 *
 * @return mixed|string
 * @since BuddyBoss 1.7.0
 */
function bp_video_mime_type( $attachment_id ) {

	$type = get_post_mime_type( $attachment_id );

	return $type;

}

/**
 * Return the icon based on the extension.
 *
 * @param string $extension extension.
 * @param int    $attachment_id attachment id.
 * @param string $type font.
 *
 * @return mixed|void
 * @since BuddyBoss 1.7.0
 */
function bp_video_svg_icon( $extension, $attachment_id = 0, $type = 'font' ) {

	if ( $attachment_id > 0 && '' !== $extension ) {
		$mime_type     = bp_video_mime_type( $attachment_id );
		$existing_list = bp_video_extensions_list();
		$new_extension = '.' . $extension;
		$result_array  = bp_video_multi_array_search(
			$existing_list,
			array(
				'extension' => $new_extension,
				'mime_type' => $mime_type,
			)
		);
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
		'svg'  => '',
	);

	switch ( $extension ) {
		case '7z':
			$svg = array(
				'font' => 'bb-icon-file-7z',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-7z</title><path d="M13.728 0.032c0.992 0 1.984 0.384 2.72 1.056l0.16 0.16 6.272 6.496c0.672 0.672 1.056 1.6 1.12 2.528v17.76c0 2.144-1.696 3.904-3.808 4h-16.192c-2.144 0-3.904-1.664-4-3.808v-24.192c0-2.144 1.696-3.904 3.808-4h9.92zM13.728 2.048h-9.728c-1.056 0-1.92 0.8-1.984 1.824v24.16c0 1.056 0.8 1.92 1.824 2.016h16.16c1.056 0 1.92-0.832 1.984-1.856l0.032-0.16v-17.504c0-0.48-0.16-0.896-0.448-1.28l-0.128-0.128-6.272-6.464c-0.384-0.416-0.896-0.608-1.44-0.608zM16.992 14.528c0.576 0 1.024 0.448 1.024 0.992 0 0.512-0.416 0.96-0.896 0.992l-0.128 0.032v0.512l-1.984 0.992v1.184l1.984-0.992v-1.184h0.032v1.184h-0.032v0.832l-1.984 0.992v1.152l1.984-0.992v-1.152h0.032v1.152h-0.032v0.832l-1.984 0.992v1.184l1.984-0.992v-1.184h0.032v1.184h-0.032v2.304h0.128c0.48 0.064 0.896 0.48 0.896 0.992s-0.416 0.928-0.896 0.992h-1.984c-0.544 0-0.992-0.448-0.992-0.992 0-0.512 0.384-0.928 0.864-0.992v-0.704l-2.592 0.672c-0.096 0.032-0.192 0.032-0.256 0-0.064 0.032-0.096 0.032-0.16 0.032h-5.984c-0.576 0-1.024-0.448-1.024-1.024v-5.984c0-0.544 0.448-0.992 1.024-0.992h5.984c0.096 0 0.16 0 0.256 0.032h0.16l2.592 0.704v-0.768c-0.48-0.064-0.864-0.48-0.864-0.992s0.384-0.928 0.864-0.992h1.984zM12 17.536h-5.984v5.984h5.984v-5.984zM10.496 21.344c0.288 0 0.512 0.224 0.512 0.512s-0.224 0.512-0.512 0.512h-3.008c-0.256 0-0.48-0.224-0.48-0.512s0.224-0.512 0.48-0.512h3.008zM10.496 18.752c0.288 0 0.512 0.224 0.512 0.512 0 0.256-0.224 0.48-0.512 0.48h-3.008c-0.256 0-0.48-0.224-0.48-0.48 0-0.288 0.224-0.512 0.48-0.512h3.008z"></path></svg>',
			);
			break;
		case 'download':
			$svg = array(
				'font' => 'bb-icon-download',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32"><title>download</title><path d="M2.656 22.656v4c0 2.209 1.791 4 4 4v0h18.688c2.209 0 4-1.791 4-4v0-4c0-0.742-0.602-1.344-1.344-1.344s-1.344 0.602-1.344 1.344v0 4c0 0 0 0 0 0 0 0.731-0.584 1.326-1.31 1.344l-0.002 0h-18.688c-0.728-0.018-1.312-0.613-1.312-1.344 0-0 0-0 0-0v0-4c0-0.742-0.602-1.344-1.344-1.344s-1.344 0.602-1.344 1.344v0zM16 19.456l-4.384-4.416c-0.248-0.312-0.628-0.51-1.054-0.51-0.742 0-1.344 0.602-1.344 1.344 0 0.426 0.198 0.805 0.507 1.052l0.003 0.002 5.344 5.344c0.243 0.239 0.576 0.387 0.944 0.387s0.701-0.148 0.944-0.387l-0 0 5.312-5.344c0.181-0.227 0.29-0.518 0.29-0.834 0-0.742-0.602-1.344-1.344-1.344-0.316 0-0.607 0.109-0.837 0.292l0.003-0.002-4.384 4.416zM14.656 2.656v18.688c0 0.742 0.602 1.344 1.344 1.344s1.344-0.602 1.344-1.344v0-18.688c0-0.742-0.602-1.344-1.344-1.344s-1.344 0.602-1.344 1.344v0z"></path></svg>',
			);
			break;
		default:
			$svg = array(
				'font' => 'bb-icon-file',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-default</title><path d="M13.728 0h-9.728c-2.208 0-4 1.792-4 4v24c0 2.208 1.792 4 4 4h16c2.208 0 4-1.792 4-4v-17.504c0-1.056-0.416-2.048-1.12-2.784l-6.272-6.496c-0.768-0.768-1.792-1.216-2.88-1.216zM4 1.984h9.728c0.544 0 1.056 0.224 1.44 0.64l6.272 6.464c0.352 0.384 0.576 0.896 0.576 1.408v17.504c0 1.12-0.896 2.016-2.016 2.016h-16c-1.088 0-1.984-0.896-1.984-2.016v-24c0-1.12 0.896-2.016 1.984-2.016z"></path></svg>',
			);
	}

	/**
	 * Filter to get video svg icon.
	 *
	 * @param string $sbg_type  svg type
	 * @param string $extension file extension
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_svg_icon', $svg[ $type ], $extension );
}

/**
 * Return the icon list.
 *
 * @return mixed|void
 * @since BuddyBoss 1.7.0
 */
function bp_video_svg_icon_list() {

	$icons = array(
		'default_1'  => array(
			'icon'  => 'bb-icon-file',
			'title' => __( 'Default', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-default</title><path d="M13.728 0h-9.728c-2.208 0-4 1.792-4 4v24c0 2.208 1.792 4 4 4h16c2.208 0 4-1.792 4-4v-17.504c0-1.056-0.416-2.048-1.12-2.784l-6.272-6.496c-0.768-0.768-1.792-1.216-2.88-1.216zM4 1.984h9.728c0.544 0 1.056 0.224 1.44 0.64l6.272 6.464c0.352 0.384 0.576 0.896 0.576 1.408v17.504c0 1.12-0.896 2.016-2.016 2.016h-16c-1.088 0-1.984-0.896-1.984-2.016v-24c0-1.12 0.896-2.016 1.984-2.016z"></path></svg>',
		),
		'default_2'  => array(
			'icon'  => 'bb-icon-file-archive',
			'title' => __( 'Archive', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-zip</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h2.976v-1.984h2.016v-1.92h-2.016v-2.080h2.016v2.016h1.984v2.048h-1.984v1.92h11.008c1.056 0 1.92-0.832 1.984-1.856l0.032-0.16v-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM10.976 21.952v2.048h-1.984v-2.048h1.984zM11.008 16c0.544 0 0.992 0.448 0.992 0.992v3.008c0 0.544-0.448 0.992-0.992 0.992h-4c-0.544 0-0.992-0.448-0.992-0.992v-3.008c0-0.544 0.448-0.992 0.992-0.992h4zM10.592 16.992h-3.2c-0.192 0-0.352 0.128-0.384 0.32v1.28c0 0.192 0.128 0.352 0.32 0.384l0.064 0.032h3.2c0.192 0 0.352-0.16 0.416-0.32v-1.28c0-0.224-0.192-0.416-0.416-0.416z"></path></svg>',
		),
		'default_3'  => array(
			'icon'  => 'bb-icon-file-mp3',
			'title' => __( 'Audio', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-mp3</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16.96 16c0.128 0 0.288 0.032 0.384 0.128s0.16 0.224 0.16 0.352v0 7.744c0 0.96-0.672 1.824-1.664 2.048-0.96 0.224-1.984-0.192-2.464-1.056-0.448-0.832-0.288-1.888 0.448-2.528s1.856-0.736 2.688-0.224v0-3.040l-6.624 0.704v4.768c0 0.96-0.704 1.792-1.664 2.048-0.96 0.224-1.984-0.192-2.464-1.056-0.48-0.832-0.288-1.888 0.448-2.528s1.824-0.736 2.688-0.224v0-5.856c0-0.256 0.192-0.448 0.448-0.48v0z"></path></svg>',
		),
		'default_4'  => array(
			'icon'  => 'bb-icon-file-html',
			'title' => __( 'Code', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-html</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.736 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM9.536 20.416c0.192 0.224 0.224 0.544 0.032 0.8l-0.032 0.064-2.112 2.048 2.112 2.112c0.192 0.224 0.224 0.544 0.032 0.768l-0.032 0.064c-0.224 0.224-0.544 0.256-0.768 0.064l-0.096-0.064-2.496-2.528c-0.224-0.192-0.224-0.544-0.064-0.768l0.064-0.064 2.528-2.496c0.224-0.224 0.608-0.224 0.832 0zM14.080 20.416c0.224-0.224 0.608-0.224 0.832 0v0l2.528 2.496 0.032 0.064c0.192 0.224 0.16 0.576-0.032 0.768v0l-2.592 2.592c-0.224 0.192-0.544 0.16-0.768-0.064v0l-0.064-0.064c-0.16-0.224-0.16-0.544 0.064-0.768v0l2.080-2.112-2.144-2.112c-0.16-0.256-0.16-0.576 0.064-0.8zM12.768 20.032c0.288 0.096 0.48 0.384 0.416 0.672l-0.032 0.064-1.664 5.28c-0.096 0.32-0.448 0.48-0.736 0.384s-0.448-0.384-0.416-0.672l0.032-0.064 1.664-5.28c0.096-0.32 0.448-0.48 0.736-0.384zM10.496 15.008c0.288 0 0.512 0.224 0.512 0.48v1.024c0 0.256-0.224 0.48-0.512 0.48h-4c-0.256 0-0.48-0.224-0.48-0.48v-1.024c0-0.256 0.224-0.48 0.48-0.48h4z"></path></svg>',
		),
		'default_5'  => array(
			'icon'  => 'bb-icon-file-psd',
			'title' => __( 'Design', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-psd</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM7.072 22.336l4.416 2.528c0.32 0.192 0.704 0.192 0.992 0.032l4.448-2.528 0.896 0.704c0.16 0.16 0.192 0.416 0.064 0.576-0.032 0.032-0.064 0.064-0.128 0.096l-5.536 3.2c-0.128 0.064-0.288 0.064-0.416 0.032l-0.096-0.032-5.44-3.2c-0.192-0.128-0.256-0.352-0.128-0.544 0-0.064 0.032-0.096 0.064-0.096l0.864-0.768zM7.104 20l4.416 2.56c0.32 0.16 0.704 0.16 0.992 0l4.448-2.528 0.896 0.736c0.16 0.128 0.192 0.384 0.064 0.544-0.032 0.064-0.064 0.096-0.128 0.128l-5.536 3.168c-0.128 0.064-0.288 0.096-0.448 0.032l-0.064-0.032-5.44-3.2c-0.192-0.096-0.256-0.352-0.128-0.544 0-0.032 0.032-0.064 0.064-0.096l0.864-0.768zM12.16 15.040l0.064 0.032 5.472 3.104c0.192 0.128 0.288 0.32 0.288 0.544 0 0.16-0.064 0.32-0.192 0.416l-0.096 0.064-5.44 3.104c-0.128 0.096-0.288 0.096-0.448 0.032l-0.064-0.032-5.44-3.104c-0.224-0.096-0.32-0.32-0.288-0.544 0-0.16 0.064-0.32 0.192-0.416l0.096-0.064 5.408-3.104c0.128-0.096 0.288-0.096 0.448-0.032zM11.968 16.256l-4.256 2.432 0.8 0.448 1.856-0.704c0.032 0 0.032 0 0.064 0 0.128-0.032 0.256 0.064 0.32 0.192v0.064l0.064 0.352 1.408-0.352c0.096-0.032 0.16 0 0.224 0 0.128 0.064 0.192 0.224 0.16 0.352l-0.032 0.064-0.896 1.824 0.32 0.192 4.288-2.432-4.32-2.432zM13.408 17.696c0 0.288-0.384 0.512-0.896 0.576-0.512 0.032-0.928-0.16-0.928-0.416-0.032-0.288 0.352-0.544 0.864-0.576s0.928 0.16 0.96 0.416z"></path></svg>',
		),
		'default_6'  => array(
			'icon'  => 'bb-icon-file-png',
			'title' => __( 'Image', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-png</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.12 0-2.016 0.896-2.016 2.016v0 24c0 1.12 0.896 2.016 2.016 2.016v0h16c1.12 0 1.984-0.896 1.984-2.016v0-17.504c0-0.512-0.192-1.024-0.544-1.408v0l-6.272-6.496c-0.384-0.384-0.896-0.608-1.44-0.608v0zM15.68 15.008c1.28 0 2.304 1.024 2.304 2.304v0 7.392c0 1.248-1.024 2.304-2.304 2.304v0h-7.36c-1.28 0-2.336-1.056-2.336-2.304v0-7.392c0-1.28 1.056-2.304 2.336-2.304v0h7.36zM9.152 21.376l-0.096 0.064-2.144 1.6v1.664c0 0.704 0.544 1.312 1.248 1.376h7.52c0.768 0 1.408-0.64 1.408-1.376v0-3.136c-0.896 0.416-2.080 0.992-3.52 1.728-0.448 0.224-0.992 0.192-1.408-0.064l-0.128-0.096-2.368-1.728c-0.16-0.096-0.352-0.096-0.512-0.032zM15.68 15.936h-7.36c-0.768 0-1.408 0.608-1.408 1.376v0 4.48c0.416-0.32 0.96-0.704 1.536-1.152 0.512-0.384 1.152-0.416 1.664-0.096l0.128 0.064 2.368 1.728c0.128 0.096 0.288 0.128 0.448 0.096l0.096-0.064 3.936-1.92v-3.136c0-0.736-0.576-1.312-1.248-1.376h-0.16zM13.376 17.792c0.672 0 1.248 0.544 1.248 1.248s-0.576 1.248-1.248 1.248c-0.704 0-1.248-0.544-1.248-1.248s0.544-1.248 1.248-1.248z"></path></svg>',
		),
		'default_7'  => array(
			'icon'  => 'bb-icon-file-pptx',
			'title' => __( 'Presentation', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-pptx</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-11.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h11.008zM11.84 18.4l4.608 2.848c-0.992 1.376-2.624 2.24-4.448 2.24-2.048 0-3.872-1.12-4.8-2.816l-0.128-0.192 4.768-2.080zM12.224 12.512c2.944 0.096 5.28 2.528 5.28 5.472 0 0.864-0.192 1.632-0.512 2.368l-0.16 0.256-4.608-2.784v-5.312zM11.52 12.512v5.312l-4.704 2.016c-0.192-0.576-0.32-1.216-0.32-1.856 0-2.848 2.208-5.216 5.024-5.472z"></path></svg>',
		),
		'default_8'  => array(
			'icon'  => 'bb-icon-file-xlsx',
			'title' => __( 'Spreadsheet', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-xlsx</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM15.488 13.984c1.408 0 2.528 1.12 2.528 2.528v0 8c0 1.376-1.12 2.496-2.528 2.496v0h-8c-1.376 0-2.496-1.12-2.496-2.496v0-8c0-1.408 1.12-2.528 2.496-2.528v0h8zM9.024 23.072h-3.008v1.44c0 0.768 0.576 1.408 1.344 1.472h1.664v-2.912zM16.992 23.072h-6.976v2.912h5.472c0.8 0 1.44-0.576 1.504-1.344v-1.568zM9.024 19.072h-3.008v3.008h3.008v-3.008zM16.992 19.072h-6.976v3.008h6.976v-3.008zM9.024 15.008h-1.536c-0.8 0-1.472 0.672-1.472 1.504v0 1.568h3.008v-3.072zM15.488 15.008h-5.472v3.072h6.976v-1.568c0-0.8-0.576-1.44-1.344-1.504h-0.16z"></path></svg>',
		),
		'default_9'  => array(
			'icon'  => 'bb-icon-file-txt',
			'title' => __( 'Text', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-txt</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM15.488 24c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.256-0.224 0.48-0.512 0.48h-8.992c-0.256 0-0.48-0.224-0.48-0.48v-0.992c0-0.288 0.224-0.512 0.48-0.512h8.992zM13.504 20.512c0.288 0 0.512 0.224 0.512 0.48v0.992c0 0.288-0.224 0.512-0.512 0.512h-7.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.256 0.224-0.48 0.48-0.48h7.008zM17.504 16.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-11.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h11.008z"></path></svg>',
		),
		'default_10' => array(
			'icon'  => 'bb-icon-file-video',
			'title' => __( 'Video', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-video</title><path d="M13.728 0.096c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 2.080h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.088 0.896 1.984 1.984 1.984v0h16c1.12 0 2.016-0.896 2.016-1.984v0-17.504c0-0.544-0.224-1.024-0.576-1.408v0l-6.272-6.496c-0.384-0.384-0.896-0.608-1.44-0.608v0zM12.704 18.080c1.28 0 2.304 1.056 2.304 2.304v0l1.472-1.088c0.448-0.352 1.056-0.256 1.408 0.192 0.128 0.16 0.192 0.384 0.192 0.608v4.992c0 0.544-0.448 0.992-0.992 0.992-0.224 0-0.448-0.064-0.608-0.192l-1.472-1.088c0 1.248-1.056 2.304-2.304 2.304v0h-5.408c-1.248 0-2.304-1.056-2.304-2.336v0-4.384c0-1.248 1.056-2.304 2.304-2.304v0h5.408zM12.704 19.008h-5.408c-0.736 0-1.376 0.64-1.376 1.376v0 4.384c0 0.768 0.64 1.408 1.376 1.408v0h5.408c0.768 0 1.376-0.64 1.376-1.408v0-4.384c0-0.736-0.608-1.376-1.376-1.376v0zM17.088 20.096l-2.016 1.472v2.016l2.016 1.504v-4.992z"></path></svg>',
		),
	);

	/**
	 * Filter to get svg icon lists.
	 *
	 * @param array $icons  svg icon list
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_svg_icon_list', $icons );
}

/**
 * Search from the array.
 *
 * @param array $array  array to be search.
 * @param array $search array to search.
 *
 * @return array
 */
function bp_video_multi_array_search( $array, $search ) {

	// Create the result array.
	$result = array();

	// Iterate over each array element.
	foreach ( $array as $key => $value ) {

		// Iterate over each search condition.
		foreach ( $search as $k => $v ) {

			// If the array element does not meet the search condition then continue to the next element.
			if ( ! isset( $value[ $k ] ) || $value[ $k ] !== $v ) {
				continue 2;
			}
		}

		// Add the array element's key to the result array.
		$result[] = $key;

	}

	// Return the result array.
	return $result;

}

/**
 * Return all the allowed video extensions.
 *
 * @return array
 * @since BuddyBoss 1.7.0
 */
function bp_video_get_allowed_extension() {
	$extensions     = array();
	$all_extensions = bp_video_extensions_list();
	foreach ( $all_extensions as $extension ) {
		if ( isset( $extension['is_active'] ) && true === (bool) $extension['is_active'] ) {
			$extensions[] = $extension['extension'];
		}
	}

	/**
	 * Filter to get allowed extension.
	 *
	 * @param array $extensions  extensions list
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_get_allowed_extension', $extensions );
}

/**
 * Return the breadcrumbs.
 *
 * @param int $user_id user id.
 * @param int $group_id group id.
 *
 * @return string
 * @since BuddyBoss 1.7.0
 */
function bp_video_user_video_album_tree_view_li_html( $user_id = 0, $group_id = 0 ) {

	global $wpdb, $bp;

	$video_album_table = $bp->video->table_name_albums;

	$cache_key = 'bp_video_user_video_album_' . $user_id . '_' . $group_id;
	$data      = wp_cache_get( $cache_key, 'bp_video_album' );

	if ( false === $data ) {
		if ( 0 === $group_id ) {
			$group_id = ( function_exists( 'bp_get_current_group_id' ) ) ? bp_get_current_group_id() : 0;
		}

		if ( $group_id > 0 ) {
			$video_album_query = $wpdb->prepare( "SELECT * FROM {$video_album_table} WHERE group_id = %d ORDER BY id DESC", $group_id ); // phpcs:ignore
		} else {
			$video_album_query = $wpdb->prepare( "SELECT * FROM {$video_album_table} WHERE user_id = %d AND group_id = %d ORDER BY id DESC", $user_id, $group_id ); // phpcs:ignore
		}

		$data = $wpdb->get_results( $video_album_query, ARRAY_A ); // phpcs:ignore
		wp_cache_set( $cache_key, $data, 'bp_video_album' );
	}

	// Build array of item references.
	foreach ( $data as $key => &$item ) {
		$items_by_reference[ $item['id'] ] = &$item;
		// Children array.
		$items_by_reference[ $item['id'] ]['children'] = array();
		// Empty data class (so that json_encode adds "data: {}" ).
		$items_by_reference[ $item['id'] ]['data'] = new StdClass();
	}

	// Set items as children of the relevant parent item.
	foreach ( $data as $key => &$item ) {
		if ( isset( $item['parent'] ) && $item['parent'] && isset( $items_by_reference[ $item['parent'] ] ) ) {
			$items_by_reference [ $item['parent'] ]['children'][] = &$item;
		}
	}

	// Remove items that were added to parents elsewhere.
	foreach ( $data as $key => &$item ) {
		if ( isset( $item['parent'] ) && $item['parent'] && isset( $items_by_reference[ $item['parent'] ] ) ) {
			unset( $data[ $key ] );
		}
	}

	return bp_video_album_recursive_li_list( $data, false );

}

/**
 * This function will give the breadcrumbs ul li html.
 *
 * @param array $array  list array.
 * @param bool  $first is first li.
 *
 * @return string
 * @since BuddyBoss 1.7.0
 */
function bp_video_album_recursive_li_list( $array, $first = false ) {

	// Base case: an empty array produces no list.
	if ( empty( $array ) ) {
		return '';
	}

	// Recursive Step: make a list with child lists.
	if ( $first ) {
		$output = '<ul class="">';
	} else {
		$output = '<ul class="location-album-list">';
	}

	foreach ( $array as $item ) {
		$output .= '<li data-id="' . $item['id'] . '" data-privacy="' . $item['privacy'] . '"><span id="' . $item['id'] . '" data-id="' . $item['id'] . '">' . stripslashes( $item['title'] ) . '</span>' . bp_video_album_recursive_li_list( $item['children'], true ) . '</li>';
	}
	$output .= '</ul>';

	return $output;
}

/**
 * This function will video into the album.
 *
 * @param int $video_id video id.
 * @param int $album_id album id.
 * @param int $group_id group id.
 *
 * @return bool|int
 * @since BuddyBoss 1.7.0
 */
function bp_video_move_video_to_album( $video_id = 0, $album_id = 0, $group_id = 0 ) {

	if ( 0 === $video_id ) {
		return false;
	}

	if ( (int) $video_id > 0 ) {
		$has_access = bp_video_user_can_edit( $video_id );
		if ( ! $has_access ) {
			return false;
		}
	}

	if ( (int) $album_id > 0 ) {
		$has_access = bp_video_album_user_can_edit( $album_id );
		if ( ! $has_access ) {
			return false;
		}
	}

	if ( ! $group_id ) {
		$get_video = new BP_Video( $video_id );
		if ( $get_video->group_id > 0 ) {
			$group_id = $get_video->group_id;
		}
	}

	if ( $group_id > 0 ) {
		$destination_privacy = 'public';
	} elseif ( $album_id > 0 ) {
		$destination_album = BP_Video_Album::get_album_data( array( $album_id ) );
		$destination_album = ( ! empty( $destination_album ) ) ? current( $destination_album ) : array();
		if ( empty( $destination_album ) ) {
			return false;
		}
		$destination_privacy = $destination_album->privacy;
		// Update modify date for destination album.
		$destination_album_update               = new BP_Video_Album( $album_id );
		$destination_album_update->date_created = bp_core_current_time();
		$destination_album_update->save();
	} else {
		// Keep the destination privacy same as the previous privacy.
		$video_object        = new BP_Video( $video_id );
		$destination_privacy = $video_object->privacy;
	}

	if ( empty( $destination_privacy ) ) {
		$destination_privacy = 'loggedin';
	}

	$video               = new BP_Video( $video_id );
	$video->album_id     = $album_id;
	$video->group_id     = $group_id;
	$video->date_created = bp_core_current_time();
	$video->privacy      = ( $group_id > 0 ) ? 'grouponly' : $destination_privacy;
	$video->menu_order   = 0;
	$video->save();

	// Update video activity privacy.
	if ( ! empty( $video ) && ! empty( $video->attachment_id ) ) {

		$video_attachment   = $video->attachment_id;
		$parent_activity_id = get_post_meta( $video_attachment, 'bp_video_parent_activity_id', true );

		// If found need to make this activity to main activity.
		$child_activity_id = get_post_meta( $video_attachment, 'bp_video_activity_id', true );

		if ( bp_is_active( 'activity' ) ) {

			// Single video upload.
			if ( empty( $child_activity_id ) ) {
				$activity = new BP_Activity_Activity( (int) $parent_activity_id );
				// Update activity data.
				if ( bp_activity_user_can_delete( $activity ) ) {
					// Make the activity video own.
					$status                      = bp_is_active( 'groups' ) ? bp_get_group_status( groups_get_group( $activity->item_id ) ) : '';
					$activity->hide_sitewide     = ( 'groups' === $activity->component && ( 'hidden' === $status || 'private' === $status ) ) ? 1 : $activity->hide_sitewide;
					$activity->secondary_item_id = 0;
					$activity->privacy           = $destination_privacy;
					$activity->save();
				}

				// Delete the meta if uploaded to root so no need bp_video_album_activity meta.
				bp_activity_delete_meta( (int) $parent_activity_id, 'bp_video_album_activity' );

				if ( $album_id > 0 ) {
					// Update to moved album id.
					bp_activity_update_meta( (int) $parent_activity_id, 'bp_video_album_activity', (int) $album_id );
				}

				// We have to change child activity privacy when we move the video while at a time multiple video uploaded.
			} else {

				$parent_activity_video_ids = bp_activity_get_meta( $parent_activity_id, 'bp_video_ids', true );

				// Get the parent activity.
				$parent_activity = new BP_Activity_Activity( (int) $parent_activity_id );

				if ( bp_activity_user_can_delete( $parent_activity ) && ! empty( $parent_activity_video_ids ) ) {
					$parent_activity_video_ids = explode( ',', $parent_activity_video_ids );

					// Do the changes if only one video is attached to a activity.
					if ( 1 === count( $parent_activity_video_ids ) ) {

						// Get the video object.
						$video = new BP_Video( $video_id );

						// Need to delete child activity.
						$need_delete = $video->activity_id;

						$video_album = (int) $video->album_id;

						// Update video activity id to parent activity id.
						$video->activity_id  = $parent_activity_id;
						$video->date_created = bp_core_current_time();
						$video->save();

						bp_activity_update_meta( $parent_activity_id, 'bp_video_ids', $video_id );

						// Update attachment meta.
						delete_post_meta( $video->attachment_id, 'bp_video_activity_id' );
						update_post_meta( $video->attachment_id, 'bp_video_parent_activity_id', $parent_activity_id );
						update_post_meta( $video->attachment_id, 'bp_video_upload', 1 );
						update_post_meta( $video->attachment_id, 'bp_video_saved', 1 );

						bp_activity_delete_meta( $parent_activity_id, 'bp_video_album_activity' );
						if ( $video_album > 0 ) {
							bp_activity_update_meta( $parent_activity_id, 'bp_video_album_activity', $video_album );
						}

						// Update the activity meta first otherwise it will delete the video.
						bp_activity_update_meta( $need_delete, 'bp_video_ids', '' );

						// Delete child activity no need anymore because assigned all the data to parent activity.
						bp_activity_delete( array( 'id' => $need_delete ) );

						// Update parent activity privacy to destination privacy.
						$parent_activity->privacy = $destination_privacy;
						$parent_activity->save();

					} elseif ( count( $parent_activity_video_ids ) > 1 ) {

						// Get the child activity.
						$activity = new BP_Activity_Activity( (int) $child_activity_id );

						// Update activity data.
						if ( bp_activity_user_can_delete( $activity ) ) {

							// Make the activity video own.
							$status                      = bp_is_active( 'groups' ) ? bp_get_group_status( groups_get_group( $activity->item_id ) ) : '';
							$activity->hide_sitewide     = ( 'groups' === $activity->component && ( 'hidden' === $status || 'private' === $status ) ) ? 1 : 0;
							$activity->secondary_item_id = 0;
							$activity->privacy           = $destination_privacy;
							$activity->save();

							bp_activity_update_meta( (int) $child_activity_id, 'bp_video_ids', $video_id );

							// Update attachment meta.
							delete_post_meta( $video_attachment, 'bp_video_activity_id' );
							update_post_meta( $video_attachment, 'bp_video_parent_activity_id', $child_activity_id );
							update_post_meta( $video_attachment, 'bp_video_upload', 1 );
							update_post_meta( $video_attachment, 'bp_video_saved', 1 );

							// Make the child activity as parent activity.
							bp_activity_delete_meta( $child_activity_id, 'bp_video_activity' );

							bp_activity_delete_meta( (int) $child_activity_id, 'bp_video_album_activity' );
							if ( $album_id > 0 ) {
								bp_activity_update_meta( (int) $child_activity_id, 'bp_video_album_activity', (int) $album_id );
							}

							// Remove the video id from the parent activity meta.
							$key = array_search( $video_id, $parent_activity_video_ids ); // phpcs:ignore
							if ( false !== $key ) {
								unset( $parent_activity_video_ids[ $key ] );
							}

							// Update the activity meta.
							if ( ! empty( $parent_activity_video_ids ) ) {
								$activity_video_ids = implode( ',', $parent_activity_video_ids );
								bp_activity_update_meta( $parent_activity_id, 'bp_video_ids', $activity_video_ids );
							} else {
								bp_activity_update_meta( $parent_activity_id, 'bp_video_ids', '' );
							}
						}
					}
				}
			}
		}
	}

	return $video_id;
}

/**
 * Get video based on activity id.
 *
 * @param int $activity_id activity id.
 *
 * @return array|void
 */
function bp_video_get_activity_video( $activity_id ) {

	$video_content      = '';
	$video_activity_ids = '';
	$response           = array();
	if ( bp_is_active( 'activity' ) && ! empty( $activity_id ) ) {

		$video_activity_ids = bp_activity_get_meta( $activity_id, 'bp_video_ids', true );

		global $video_template;
		// Add Video to single activity page..
		$video_activity = bp_activity_get_meta( $activity_id, 'bp_video_activity', true );
		if ( bp_is_single_activity() && ! empty( $video_activity ) && '1' === $video_activity && empty( $video_activity_ids ) ) {
			$video_ids = BP_Video::get_activity_video_id( $activity_id );
		} else {
			$video_ids = bp_activity_get_meta( $activity_id, 'bp_video_ids', true );
		}

		if ( empty( $video_ids ) ) {
			return;
		}

		$args = array(
			'include'  => $video_ids,
			'order_by' => 'menu_order',
			'sort'     => 'ASC',
			'user_id'  => false,
			'per_page' => 0,
		);

		$activity = new BP_Activity_Activity( (int) $activity_id );
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
		if ( bp_is_active( 'forums' ) && in_array(
			$activity->type,
			array(
				'bbp_forum_create',
				'bbp_topic_create',
				'bbp_reply_create',
			),
			true
		) && bp_is_forums_video_support_enabled() ) {
			$is_forum_activity = true;
			$args['privacy'][] = 'forums';
		}

		if ( bp_has_video( $args ) ) {

			ob_start();
			?>
			<div class="bb-activity-video-wrap
			<?php
			echo esc_attr( 'bb-video-length-' . $video_template->video_count );
			echo $video_template->video_count > 5 ? esc_attr( ' bb-video-length-more' ) : '';
			echo true === $is_forum_activity ? esc_attr( ' forums-video-wrap' ) : '';
			?>
			">
				<?php
				bp_get_template_part( 'video/video-move' );
				bp_get_template_part( 'video/add-video-thumbnail' );
				while ( bp_video() ) {
					bp_the_video();
					bp_get_template_part( 'video/activity-entry' );
				}
				?>
			</div>
			<?php
			$video_content = ob_get_contents();
			ob_end_clean();
		}
	}

	$response['content']            = $video_content;
	$response['video_activity_ids'] = $video_activity_ids;

	return $response;
}

/**
 * Set bb_videos folder for the video upload directory.
 *
 * @param array $pathdata upload path.
 *
 * @return mixed
 * @since BuddyBoss 1.7.0
 */
function bp_video_upload_dir( $pathdata ) {

	$action = bb_filter_input_string( INPUT_POST, 'action' );

	if ( isset( $action ) && ( 'video_upload' === $action || 'video_thumbnail_upload' === $action ) ) { // WPCS: CSRF ok, input var ok.
		if ( empty( $pathdata['subdir'] ) ) {
			$pathdata['path']   = $pathdata['path'] . '/bb_videos';
			$pathdata['url']    = $pathdata['url'] . '/bb_videos';
			$pathdata['subdir'] = '/bb_videos';
		} else {
			$new_subdir         = '/bb_videos' . $pathdata['subdir'];
			$pathdata['path']   = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['path'] );
			$pathdata['url']    = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['url'] );
			$pathdata['subdir'] = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['subdir'] );
		}
	}

	return $pathdata;
}

/**
 * Set bb_videos folder for the video upload directory.
 *
 * @param array $pathdata upload path.
 *
 * @return mixed
 * @since BuddyBoss 1.7.0
 */
function bp_video_upload_dir_script( $pathdata ) {

	if ( empty( $pathdata['subdir'] ) ) {
		$pathdata['path']   = $pathdata['path'] . '/bb_videos';
		$pathdata['url']    = $pathdata['url'] . '/bb_videos';
		$pathdata['subdir'] = '/bb_videos';
	} else {
		$new_subdir         = '/bb_videos' . $pathdata['subdir'];
		$pathdata['path']   = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['path'] );
		$pathdata['url']    = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['url'] );
		$pathdata['subdir'] = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['subdir'] );
	}

	return $pathdata;
}

/**
 * Check given video is activity comment video.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param int|object $video video id or object.
 *
 * @return bool
 */
function bp_video_is_activity_comment_video( $video ) {

	$is_comment_video = false;

	if ( is_object( $video ) ) {
		$video_activity_id = $video->activity_id;
	} else {
		$video             = new BP_Video( $video );
		$video_activity_id = $video->activity_id;
	}

	if ( bp_is_active( 'activity' ) ) {
		$activity = new BP_Activity_Activity( $video_activity_id );

		if ( $activity ) {
			if ( 'activity_comment' === $activity->type ) {
				$is_comment_video = true;
			}
			if ( $activity->secondary_item_id ) {
				$load_parent_activity = new BP_Activity_Activity( $activity->secondary_item_id );
				if ( $load_parent_activity ) {
					if ( 'activity_comment' === $load_parent_activity->type ) {
						$is_comment_video = true;
					}
				}
			}
		}
	} elseif ( $video_activity_id ) {
		$is_comment_video = true;
	}

	return $is_comment_video;
}

/**
 * Return download link of the album.
 *
 * @param int $album_id album id.
 *
 * @return mixed|void
 * @since BuddyBoss 1.7.0
 */
function bp_video_album_download_link( $album_id ) {

	if ( empty( $album_id ) ) {
		return;
	}

	$link = site_url() . '/?attachment=' . $album_id . '&video_type=album&download_video_file=1&video_file=' . $album_id;

	/**
	 * Filter to get video album download link.
	 *
	 * @param string $link  downlod link
	 * @param int    $album_id  album id
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_album_download_link', $link, $album_id );
}

/**
 * Function to get video report link
 *
 * @since BuddyBoss 1.7.0
 *
 * @param array $args button arguments.
 *
 * @return mixed|void
 */
function bp_video_get_report_link( $args = array() ) {

	if ( ! bp_is_active( 'moderation' ) || ! is_user_logged_in() ) {
		return false;
	}

	$report_btn = bp_moderation_get_report_button(
		array(
			'id'                => 'video_report',
			'component'         => 'moderation',
			'must_be_logged_in' => true,
			'button_attr'       => array(
				'data-bp-content-id'   => ! empty( $args['id'] ) ? $args['id'] : 0,
				'data-bp-content-type' => BP_Moderation_Video::$moderation_type,
			),
		),
		true
	);

	/**
	 * Filter to return video report link
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param string $report_btn button.
	 * @param array $args button arguments.
	 */
	return apply_filters( 'bp_video_get_report_link', $report_btn, $args );
}

/**
 * Check if FFMPEG installed.
 */
function bb_video_is_ffmpeg_installed() {

	if ( ! class_exists( 'FFMpeg\FFMpeg' ) ) {
		return false;
	} elseif ( class_exists( 'FFMpeg\FFMpeg' ) ) {
		$ffmpeg = bb_video_check_is_ffmpeg_binary();
		if ( ! empty( $ffmpeg->error ) && ! empty( trim( $ffmpeg->error ) ) ) {
			return false;
		} else {
			return true;
		}
	}

	return false;
}

/**
 * Function to get video attachments
 *
 * @since BuddyBoss 1.7.0
 *
 * @param int $video_attachment_id video attachment id.
 * @param int $video_id            video id.
 *
 * @return mixed
 */
function bp_video_get_attachments( $video_attachment_id, $video_id = 0 ) {

	$attachment_urls           = array();
	$auto_generated_thumbnails = get_post_meta( $video_attachment_id, 'video_preview_thumbnails', true );
	$preview_thumbnail_id      = get_post_meta( $video_attachment_id, 'bp_video_preview_thumbnail_id', true );
	if ( $auto_generated_thumbnails ) {
		$auto_generated_thumbnails_arr = isset( $auto_generated_thumbnails['default_images'] ) && ! empty( $auto_generated_thumbnails['default_images'] ) ? $auto_generated_thumbnails['default_images'] : array();
		if ( $auto_generated_thumbnails_arr ) {
			foreach ( $auto_generated_thumbnails_arr as $auto_generated_thumbnail ) {
				$attachment_urls['default_images'][] = array(
					'id'  => $auto_generated_thumbnail,
					'url' => wp_get_attachment_image_url( $auto_generated_thumbnail, 'full' ),
				);
			}
		}
	}

	if ( $preview_thumbnail_id ) {
		$attachment_urls['selected_id'] = array(
			'id'  => $preview_thumbnail_id,
			'url' => wp_get_attachment_image_url( $preview_thumbnail_id, 'full' ),
		);
	}

	if ( isset( $auto_generated_thumbnails['custom_image'] ) && ! empty( $auto_generated_thumbnails['custom_image'] ) ) {
		$id                         = ( $video_id ) ? $video_id : bp_get_video_id();
		$video                      = new BP_Video( $id );
		$attachment_urls['preview'] = array(
			'id'            => $id,
			'attachment_id' => $auto_generated_thumbnails['custom_image'],
			'thumb'         => wp_get_attachment_image_url( $auto_generated_thumbnails['custom_image'], 'bp-media-thumbnail' ),
			'url'           => wp_get_attachment_image_url( $auto_generated_thumbnails['custom_image'], 'full' ),
			'name'          => $video->title,
			'saved'         => true,
			'dropzone'      => true,
		);
	}

	return $attachment_urls;
}

/**
 * Whether user can show the document upload button.
 *
 * @param int $user_id  given user id.
 * @param int $group_id given group id.
 *
 * @since BuddyBoss 1.7.0
 *
 * @return bool
 */
function bb_video_user_can_upload( $user_id = 0, $group_id = 0 ) {

	if ( ( empty( $user_id ) && empty( $group_id ) ) || empty( $user_id ) ) {
		return false;
	}

	if ( ! empty( $group_id ) && bp_is_group_video_support_enabled() ) {
		return groups_can_user_manage_video( $user_id, $group_id );
	}

	if ( bp_is_profile_video_support_enabled() && bb_user_can_create_video() ) {
		return true;
	}

	return false;
}

/**
 * Return the video symlink path.
 *
 * @return string The symlink path.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_symlink_path() {

	$upload_dir = wp_upload_dir();
	$upload_dir = $upload_dir['basedir'];

	$platform_previews_path = $upload_dir . '/bb-platform-previews';
	if ( ! is_dir( $platform_previews_path ) ) {
		wp_mkdir_p( $platform_previews_path );
		chmod( $platform_previews_path, 0755 );
	}

	$video_symlinks_path = $platform_previews_path . '/' . md5( 'bb-videos' );
	if ( ! is_dir( $video_symlinks_path ) ) {
		wp_mkdir_p( $video_symlinks_path );
		chmod( $video_symlinks_path, 0755 );
	}

	return $video_symlinks_path;
}

/**
 * Delete video previews/symlinks.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_delete_video_previews() {
	$upload_dir = wp_upload_dir();
	$upload_dir = $upload_dir['basedir'];

	$parent_directory_name = $upload_dir . '/bb-platform-previews';
	if ( ! is_dir( $parent_directory_name ) ) {
		return;
	}

	$inner_directory_name = $parent_directory_name . '/' . md5( 'bb-videos' );
	if ( ! is_dir( $inner_directory_name ) ) {
		return;
	}

	$dir          = opendir( $inner_directory_name );
	$five_minutes = strtotime( '-5 minutes' );
	while ( false != ( $file = readdir( $dir ) ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition, WordPress.PHP.StrictComparisons.LooseComparison
		if ( file_exists( $inner_directory_name . '/' . $file ) && is_writable( $inner_directory_name . '/' . $file ) && filemtime( $inner_directory_name . '/' . $file ) < $five_minutes ) {
			@unlink( $inner_directory_name . '/' . $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}
	closedir( $dir );
}

/**
 * Return the preview url of the file.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param int    $video_id      Video ID.
 * @param int    $attachment_id Attachment ID.
 * @param string $size          Size of preview.
 * @param bool   $generate      Generate Symlink or not.
 * @param int    $receiver_id   Receiver user ID.
 *
 * @return mixed|void
 */
function bb_video_get_thumb_url( $video_id, $attachment_id, $size = 'bb-video-activity-image', $generate = true, $receiver_id = 0 ) {

	$attachment_url = '';

	/**
	 * Filter here to allow/disallow video thumb symlinks.
	 *
	 * @param bool   $do_symlink    Default true.
	 * @param int    $video_id      Video id
	 * @param int    $attachment_id Document attachment id.
	 * @param string $size          Size.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	$do_symlink = apply_filters( 'bb_video_create_thumb_symlinks', true, $video_id, $attachment_id, $size );

	if ( $do_symlink ) {

		$video = new BP_Video( $video_id );
		if ( bb_enable_symlinks() ) {
			$attachment_url = bb_video_get_attachment_symlink( $video, $attachment_id, $size, $generate );
		} else {
			$video_id       = 'forbidden_' . $video_id;
			$attachment_id  = 'forbidden_' . $attachment_id;
			$attachment_url = home_url( '/' ) . 'bb-video-thumb-preview/' . base64_encode( $attachment_id ) . '/' . base64_encode( $video_id ) . '/' . $size;

			if ( 0 < $receiver_id ) {
				$attachment_url = $attachment_url . '/' . base64_encode( 'receiver_' . $receiver_id );
			}
		}

		if ( empty( $attachment_url ) ) {
			$attachment_url = bb_get_video_default_placeholder_image();
		}
	} else {
		$attachment_url = wp_get_attachment_url( $attachment_id );
	}

	$attachment_url = ! empty( $attachment_url ) && ! bb_enable_symlinks() ? untrailingslashit( $attachment_url ) : $attachment_url;

	/**
	 * Filter to get video thumb url.
	 *
	 * @param int    $attachment_url attachment link
	 * @param int    $video_id       video id
	 * @param string $size           size
	 * @param int    $attachment_id  attachment id
	 * @param bool   $do_symlink     symlink used or not
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bb_video_get_thumb_url', $attachment_url, $video_id, $size, $attachment_id, $do_symlink );
}

/**
 * Return the video thumbnail attachment id.
 *
 * @param int $video_attachment_id Video attachment id.
 *
 * @return int|mixed|string return the video attachment id.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_get_video_thumb_id( $video_attachment_id ) {

	$get_video_thumb_ids = get_post_meta( $video_attachment_id, 'video_preview_thumbnails', true );
	$get_video_thumb_id  = get_post_meta( $video_attachment_id, 'bp_video_preview_thumbnail_id', true );

	if ( $get_video_thumb_id ) {
		return $get_video_thumb_id;
	} elseif ( $get_video_thumb_ids ) {
		$get_video_thumb_ids_arr = isset( $get_video_thumb_ids['default_images'] ) ? $get_video_thumb_ids['default_images'] : array();
		if ( $get_video_thumb_ids_arr ) {
			return current( $get_video_thumb_ids_arr );
		}
		if ( isset( $get_video_thumb_ids['custom_image'] ) && ! empty( $get_video_thumb_ids['custom_image'] ) ) {
			return $get_video_thumb_ids['custom_image'];
		}
	}

	return 0;
}

/**
 * Return the video auto generated preview ids.
 *
 * @param int $video_attachment_id video attachment id.
 *
 * @return array attachment ids array.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_get_auto_generated_preview_ids( $video_attachment_id ) {

	$get_video_thumb_ids = get_post_meta( $video_attachment_id, 'video_preview_thumbnails', true );
	if ( ! $get_video_thumb_ids ) {
		return array();
	}

	$default_images = isset( $get_video_thumb_ids['default_images'] ) && ! empty( $get_video_thumb_ids['default_images'] ) ? $get_video_thumb_ids['default_images'] : array();

	return $default_images;
}

/**
 * Return the default placeholder image.
 *
 * @return string Return the default video placeholder image.
 */
function bb_get_video_default_placeholder_image() {
	return buddypress()->plugin_url . 'bp-templates/bp-nouveau/images/video-placeholder.jpg';
}

/**
 * Regenerate video poster attachment thumbnails
 *
 * @param int $attachment_id Attachment ID.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_regenerate_attachment_thumbnails( $attachment_id ) {

	// Add upload filters.
	bb_video_image_add_upload_filters();

	// Register image sizes.
	bb_video_register_image_sizes();

	// Regenerate attachment thumbnails.
	bp_core_regenerate_attachment_thumbnails( $attachment_id );

	// Remove upload filters.
	bb_video_image_remove_upload_filters();

	// Deregister image sizes.
	bb_video_deregister_image_sizes();
}

/**
 * Delete the symlink for given thumb id.
 *
 * @param int|object $video           video id or video object.
 * @param int        $delete_thumb_id thumb id to delete.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_delete_thumb_symlink( $video, $delete_thumb_id ) {

	// Check if video is id of video, create video object.
	if ( $video instanceof BP_Video ) {
		$video_id = $video->id;
	} elseif ( is_int( $video ) ) {
		$video_id = $video;
	} elseif ( is_string( $video ) ) {
		$video_id = $video;
	}

	if ( empty( $video_id ) ) {
		return;
	}

	// Get video previews symlink directory path.
	$video_symlinks_path = bb_video_symlink_path();
	$video               = new BP_Video( $video_id );

	// Delete the thumb symlink.
	$privacy         = $video->privacy;
	$attachment_path = $video_symlinks_path . '/' . md5( $video->id . $delete_thumb_id . $privacy );
	if ( $video->group_id > 0 && bp_is_active( 'groups' ) ) {
		$group_object    = groups_get_group( $video->group_id );
		$group_status    = bp_get_group_status( $group_object );
		$attachment_path = $video_symlinks_path . '/' . md5( $video->id . $delete_thumb_id . $group_status . $privacy );
	}

	if ( file_exists( $attachment_path ) || is_link( $attachment_path ) ) {
		unlink( $attachment_path );
	}

	$attachment_path = $video_symlinks_path . '/' . md5( $video->id . $delete_thumb_id . $privacy . 'medium' );
	if ( $video->group_id > 0 && bp_is_active( 'groups' ) ) {
		$group_object    = groups_get_group( $video->group_id );
		$group_status    = bp_get_group_status( $group_object );
		$attachment_path = $video_symlinks_path . '/' . md5( $video->id . $delete_thumb_id . $group_status . $privacy . 'medium' );
	}

	if ( file_exists( $attachment_path ) || is_link( $attachment_path ) ) {
		unlink( $attachment_path );
	}

	$attachment_path = $video_symlinks_path . '/' . md5( $video->id . $delete_thumb_id . $privacy . 'large' );
	if ( $video->group_id > 0 && bp_is_active( 'groups' ) ) {
		$group_object    = groups_get_group( $video->group_id );
		$group_status    = bp_get_group_status( $group_object );
		$attachment_path = $video_symlinks_path . '/' . md5( $video->id . $delete_thumb_id . $group_status . $privacy . 'large' );
	}

	if ( file_exists( $attachment_path ) || is_link( $attachment_path ) ) {
		unlink( $attachment_path );
	}

	$attachment_path = $video_symlinks_path . '/' . md5( $video->id . $delete_thumb_id . $privacy . 'full' );
	if ( $video->group_id > 0 && bp_is_active( 'groups' ) ) {
		$group_object    = groups_get_group( $video->group_id );
		$group_status    = bp_get_group_status( $group_object );
		$attachment_path = $video_symlinks_path . '/' . md5( $video->id . $delete_thumb_id . $group_status . $privacy . 'full' );
	}

	if ( file_exists( $attachment_path ) || is_link( $attachment_path ) ) {
		unlink( $attachment_path );
	}

	$image_sizes = bb_video_get_image_sizes();
	if ( ! empty( $image_sizes ) ) {
		foreach ( $image_sizes as $name => $image_size ) {
			if ( ! empty( $image_size['width'] ) && ! empty( $image_size['height'] ) && 0 < (int) $image_size['width'] && 0 < $image_size['height'] ) {
				$attachment_path = $video_symlinks_path . '/' . md5( $video->id . $delete_thumb_id . $privacy . sanitize_key( $name ) );
				if ( $video->group_id > 0 && bp_is_active( 'groups' ) ) {
					$group_object    = groups_get_group( $video->group_id );
					$group_status    = bp_get_group_status( $group_object );
					$attachment_path = $video_symlinks_path . '/' . md5( $video->id . $delete_thumb_id . $group_status . $privacy . sanitize_key( $name ) );
				}
				if ( file_exists( $attachment_path ) ) {
					unlink( $attachment_path );
				}
			}
		}
	}
}

/**
 * Delete symlink for a video.
 *
 * @param object $video BP_Video Object.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_delete_symlinks( $video ) {
	// Check if video is id of video, create video object.
	if ( $video instanceof BP_Video ) {
		$video_id = $video->id;
	} elseif ( is_int( $video ) ) {
		$video_id = $video;
	}

	$old_video = new BP_Video( $video_id );

	// Return if no video found.
	if ( empty( $old_video ) ) {
		return;
	}

	// Get video previews symlink directory path.
	$video_symlinks_path = bb_video_symlink_path();
	$attachment_id       = $old_video->attachment_id;

	// Delete the video symlink.
	$privacy         = $old_video->privacy;
	$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $attachment_id . $privacy );
	if ( $old_video->group_id > 0 && bp_is_active( 'groups' ) ) {
		$group_object    = groups_get_group( $old_video->group_id );
		$group_status    = bp_get_group_status( $group_object );
		$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $attachment_id . $group_status . $privacy );
	}

	if ( file_exists( $attachment_path ) ) {
		unlink( $attachment_path );
	}

	// Remove symlinks that is created randomly.
	$get_existings = get_post_meta( $old_video->attachment_id, 'bb_video_symlinks_arr', true );
	if ( $get_existings ) {
		foreach ( $get_existings as $symlink ) {
			if ( file_exists( $symlink ) ) {
				unlink( $symlink );
			}
		}
	}

	// Delete the video main preview link.
	$attachment_id   = bb_get_video_thumb_id( $old_video->attachment_id );
	$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $attachment_id . $privacy . 'medium' );
	if ( $old_video->group_id > 0 && bp_is_active( 'groups' ) ) {
		$group_object    = groups_get_group( $old_video->group_id );
		$group_status    = bp_get_group_status( $group_object );
		$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $attachment_id . $group_status . $privacy . 'medium' );
	}

	if ( file_exists( $attachment_path ) ) {
		unlink( $attachment_path );
	}

	$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $attachment_id . $privacy . 'large' );
	if ( $old_video->group_id > 0 && bp_is_active( 'groups' ) ) {
		$group_object    = groups_get_group( $old_video->group_id );
		$group_status    = bp_get_group_status( $group_object );
		$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $attachment_id . $group_status . $privacy . 'large' );
	}

	if ( file_exists( $attachment_path ) ) {
		unlink( $attachment_path );
	}

	$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $attachment_id . $privacy . 'full' );
	if ( $old_video->group_id > 0 && bp_is_active( 'groups' ) ) {
		$group_object    = groups_get_group( $old_video->group_id );
		$group_status    = bp_get_group_status( $group_object );
		$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $attachment_id . $group_status . $privacy . 'full' );
	}
	if ( file_exists( $attachment_path ) ) {
		unlink( $attachment_path );
	}

	$image_sizes = bb_video_get_image_sizes();
	if ( ! empty( $image_sizes ) ) {
		foreach ( $image_sizes as $name => $image_size ) {
			if ( ! empty( $image_size['width'] ) && ! empty( $image_size['height'] ) && 0 < (int) $image_size['width'] && 0 < $image_size['height'] ) {
				$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $attachment_id . $privacy . sanitize_key( $name ) );
				if ( $old_video->group_id > 0 && bp_is_active( 'groups' ) ) {
					$group_object    = groups_get_group( $old_video->group_id );
					$group_status    = bp_get_group_status( $group_object );
					$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $attachment_id . $group_status . $privacy . sanitize_key( $name ) );
				}
				if ( file_exists( $attachment_path ) ) {
					unlink( $attachment_path );
				}
			}
		}
	}

	// Delete the extra preview images symlink.
	$extra_preview_ids = bb_video_get_auto_generated_preview_ids( $old_video->attachment_id );
	if ( ! empty( $extra_preview_ids ) ) {
		foreach ( $extra_preview_ids as $attachment_id ) {

			$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $attachment_id . $privacy . 'medium' );
			if ( $old_video->group_id > 0 && bp_is_active( 'groups' ) ) {
				$group_object    = groups_get_group( $old_video->group_id );
				$group_status    = bp_get_group_status( $group_object );
				$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $attachment_id . $group_status . $privacy . 'medium' );
			}

			if ( file_exists( $attachment_path ) ) {
				unlink( $attachment_path );
			}

			$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $attachment_id . $privacy . 'large' );
			if ( $old_video->group_id > 0 && bp_is_active( 'groups' ) ) {
				$group_object    = groups_get_group( $old_video->group_id );
				$group_status    = bp_get_group_status( $group_object );
				$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $attachment_id . $group_status . $privacy . 'large' );
			}

			if ( file_exists( $attachment_path ) ) {
				unlink( $attachment_path );
			}

			$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $attachment_id . $privacy . 'full' );
			if ( $old_video->group_id > 0 && bp_is_active( 'groups' ) ) {
				$group_object    = groups_get_group( $old_video->group_id );
				$group_status    = bp_get_group_status( $group_object );
				$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $attachment_id . $group_status . $privacy . 'full' );
			}
			if ( file_exists( $attachment_path ) ) {
				unlink( $attachment_path );
			}

			if ( ! empty( $image_sizes ) ) {
				foreach ( $image_sizes as $name => $image_size ) {
					if ( ! empty( $image_size['width'] ) && ! empty( $image_size['height'] ) && 0 < (int) $image_size['width'] && 0 < $image_size['height'] ) {
						$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $attachment_id . $privacy . sanitize_key( $name ) );

						if ( $old_video->group_id > 0 && bp_is_active( 'groups' ) ) {
							$group_object    = groups_get_group( $old_video->group_id );
							$group_status    = bp_get_group_status( $group_object );
							$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $attachment_id . $group_status . $privacy . sanitize_key( $name ) );
						}

						if ( file_exists( $attachment_path ) ) {
							unlink( $attachment_path );
						}
					}
				}
			}
		}
	}

	$get_video_thumb_id = get_post_meta( $attachment_id, 'bp_video_preview_thumbnail_id', true );
	if ( ! empty( $image_sizes ) ) {
		foreach ( $image_sizes as $name => $image_size ) {
			if ( ! empty( $image_size['width'] ) && ! empty( $image_size['height'] ) && 0 < (int) $image_size['width'] && 0 < $image_size['height'] ) {
				$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $get_video_thumb_id . $privacy . sanitize_key( $name ) );

				if ( $old_video->group_id > 0 && bp_is_active( 'groups' ) ) {
					$group_object    = groups_get_group( $old_video->group_id );
					$group_status    = bp_get_group_status( $group_object );
					$attachment_path = $video_symlinks_path . '/' . md5( $old_video->id . $get_video_thumb_id . $group_status . $privacy . sanitize_key( $name ) );
				}

				if ( file_exists( $attachment_path ) ) {
					unlink( $attachment_path );
				}
			}
		}
	}
}

/**
 * Create symlink for a video.
 *
 * @param object $video BP_Video Object.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_create_symlinks( $video ) {
	// Check if video is id of video, create video object.
	if ( ! $video instanceof BP_Video && is_int( $video ) ) {
		$video = new BP_Video( $video );
	}

	// Return if no video found.
	if ( empty( $video ) ) {
		return;
	}

	/**
	 * Filter here to allow/disallow video symlinks.
	 *
	 * @param bool   $do_symlink    Default true.
	 * @param int    $video_id      Video id
	 * @param int    $attachment_id Document attachment id.
	 * @param string $size          Size.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	$do_symlink = apply_filters( 'bb_video_do_symlink', true, $video->id, $video->attachment_id, '' );

	if ( $do_symlink ) {
		bb_video_get_symlink( $video );
	}
}

/**
 * Create symlink for a video.
 *
 * @param object $video    BP_Video Object.
 * @param bool   $generate Generate Symlink or not.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_get_symlink( $video, $generate = true ) {
	// Check if video is id of video, create video object.
	if ( ! $video instanceof BP_Video && is_int( $video ) ) {
		$video = new BP_Video( $video );
	}

	// Return if no video found.
	if ( empty( $video ) ) {
		return;
	}

	$attachment_url = '';
	$video_id       = $video->id;
	$attachment_id  = $video->attachment_id;

	/**
	 * Filter here to allow/disallow video symlinks.
	 *
	 * @param bool   $do_symlink    Default true.
	 * @param int    $video_id      Video id
	 * @param int    $attachment_id Video attachment id.
	 * @param string $size          Size.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	$do_symlink = apply_filters( 'bb_video_do_symlink', true, $video_id, $attachment_id, '' );

	if ( $do_symlink ) {

		if ( ! bb_enable_symlinks() || bb_check_ios_device() ) {
			$video_id        = 'forbidden_' . $video->id;
			$attachment_id   = 'forbidden_' . $video->attachment_id;
			$output_file_src = get_attached_file( $video->attachment_id );
			if ( ! empty( $attachment_id ) && ! empty( $video_id ) && file_exists( $output_file_src ) ) {
				$attachment_url = home_url( '/' ) . 'bb-video-preview/' . base64_encode( $attachment_id ) . '/' . base64_encode( $video_id );
			}
		} else {
			// Get videos previews symlink directory path.
			$video_symlinks_path = bb_video_symlink_path();
			$attached_file       = get_attached_file( $attachment_id );
			$privacy             = $video->privacy;
			$upload_directory    = wp_get_upload_dir();
			$time                = time();
			$attachment_path     = $video_symlinks_path . '/' . md5( $video->id . $attachment_id . $privacy . $time );

			if ( $video->group_id > 0 && bp_is_active( 'groups' ) ) {
				$group_object    = groups_get_group( $video->group_id );
				$group_status    = bp_get_group_status( $group_object );
				$attachment_path = $video_symlinks_path . '/' . md5( $video->id . $attachment_id . $group_status . $privacy . $time );
			}

			if ( ! empty( $attached_file ) && file_exists( $attached_file ) && is_file( $attached_file ) && ! is_dir( $attached_file ) && ! file_exists( $attachment_path ) ) {
				if ( ! is_link( $attachment_path ) && ! file_exists( $attachment_path ) ) {
					$get_existing = get_post_meta( $video->attachment_id, 'bb_video_symlinks_arr', true );
					if ( ! $get_existing ) {
						update_post_meta( $video->attachment_id, 'bb_video_symlinks_arr', array( $attachment_path ) );
					} else {
						$get_existing[] = array_push( $get_existing, $attachment_path );
						update_post_meta( $video->attachment_id, 'bb_video_symlinks_arr', $get_existing );
					}

					if ( $generate ) {
						// Generate Video Symlink.
						bb_core_symlink_generator( 'video', $video, $time, array(), $attached_file, $attachment_path );
					}
				}
			}

			$attachment_url = bb_core_symlink_absolute_path( $attachment_path, $upload_directory );

			/**
			 * Filter for the after thumb symlink generate.
			 *
			 * @param string $attachment_url Attachment URL.
			 * @param object $video          Video Object.
			 *
			 * @since BuddyBoss 1.7.0.1
			 */
			$attachment_url = apply_filters( 'bb_video_after_get_symlink', $attachment_url, $video );
		}
	}

	/**
	 * Filter for the video symlink url.
	 *
	 * @param string $attachment_url Attachment URL.
	 * @param int    $video_id       Video id.
	 * @param int    $attachment_id  Attachment id.
	 * @param bool   $do_symlink     Symlink used or not.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bb_video_get_symlink', $attachment_url, $video_id, $attachment_id, $do_symlink );
}

/**
 * A simple function that uses mtime to delete files older than a given age (in seconds)
 * Very handy to rotate backup or log files, for example...
 *
 * @return array|void the list of deleted files
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_delete_older_symlinks() {

	if ( ! bb_enable_symlinks() ) {
		return;
	}

	// Get videos previews symlink directory path.
	$dir     = bb_video_symlink_path();
	$max_age = apply_filters( 'bb_video_delete_older_symlinks_time', 3600 * 24 * 15 ); // Delete the file older than 15 day.
	$list    = array();
	$limit   = time() - $max_age;
	$dir     = realpath( $dir );

	if ( ! is_dir( $dir ) ) {
		return;
	}

	$dh = opendir( $dir );
	if ( false === $dh ) {
		return;
	}

	while ( ( $file = readdir( $dh ) ) !== false ) {
		if ( '.' === $file || '..' === $file ) {
			continue;
		}

		$file      = $dir . '/' . $file;
		$file_time = lstat( $file );
		$file_time = isset( $file_time['ctime'] ) ? (int) $file_time['ctime'] : filemtime( $file );

		if ( file_exists( $file ) && $file_time < $limit ) {
			$list[] = $file;
			unlink( $file );
		}
	}
	closedir( $dh );

	if ( ! empty( $list ) ) {
		/**
		 * Hook after delete older symlinks.
		 *
		 * @since BuddyBoss 1.7.0
		 */
		do_action( 'bb_video_delete_older_symlinks' );
	}

	return $list;

}
bp_core_schedule_cron( 'bb_video_deleter_older_symlink', 'bb_video_delete_older_symlinks', 'bb_schedule_15days' );

/**
 * Function to get video attachments symlinks.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param int $video_attachment_id video attachment id.
 * @param int $video_id            video id.
 *
 * @return mixed
 */
function bb_video_get_attachments_symlinks( $video_attachment_id, $video_id = 0 ) {

	$attachment_urls           = array();
	$video                     = new BP_Video( $video_id );
	$auto_generated_thumbnails = get_post_meta( $video_attachment_id, 'video_preview_thumbnails', true );
	$preview_thumbnail_id      = get_post_meta( $video_attachment_id, 'bp_video_preview_thumbnail_id', true );

	if ( $auto_generated_thumbnails ) {
		$auto_generated_thumbnails_arr = isset( $auto_generated_thumbnails['default_images'] ) && ! empty( $auto_generated_thumbnails['default_images'] ) ? $auto_generated_thumbnails['default_images'] : array();
		if ( $auto_generated_thumbnails_arr ) {
			foreach ( $auto_generated_thumbnails_arr as $auto_generated_thumbnail ) {
				$attachment_urls['default_images'][] = array(
					'id'  => $auto_generated_thumbnail,
					'url' => bb_video_get_attachment_symlink( $video, $auto_generated_thumbnail, 'bb-video-profile-album-add-thumbnail-directory-poster-image' ),
				);
			}
		} else {
			$is_ffmpeg_preview_generated = get_post_meta( $video_attachment_id, 'bb_ffmpeg_preview_generated', true );
			if ( 'no' === $is_ffmpeg_preview_generated ) {
				$attachment_urls['default_images'][] = array();
			}
		}
	} else {
		$is_ffmpeg_preview_generated = get_post_meta( $video_attachment_id, 'bb_ffmpeg_preview_generated', true );
		if ( 'no' === $is_ffmpeg_preview_generated ) {
			$attachment_urls['default_images'][] = array();
		}
	}

	if ( $preview_thumbnail_id ) {
		$attachment_urls['selected_id'] = array(
			'id'  => $preview_thumbnail_id,
			'url' => bb_video_get_attachment_symlink( $video, $preview_thumbnail_id, 'bb-video-profile-album-add-thumbnail-directory-poster-image' ),
		);
	}

	if ( isset( $auto_generated_thumbnails['custom_image'] ) && ! empty( $auto_generated_thumbnails['custom_image'] ) ) {

		$id                         = ( $video_id ) ? $video_id : bp_get_video_id();
		$video                      = new BP_Video( $id );
		$url                        = bb_video_get_attachment_symlink( $video, $auto_generated_thumbnails['custom_image'], 'bb-video-profile-album-add-thumbnail-directory-poster-image' );
		$attachment_urls['preview'] = array(
			'id'            => $id,
			'attachment_id' => $auto_generated_thumbnails['custom_image'],
			'thumb'         => $url,
			'url'           => $url,
			'name'          => $video->title,
			'saved'         => true,
			'dropzone'      => true,
		);
	}

	return $attachment_urls;
}

/**
 * Use the ffmpeg binary if constant is defined.
 *
 * @return \FFMpeg\FFMpeg
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_check_is_ffmpeg_binary() {

	$retval = array(
		'ffmpeg' => null,
		'error'  => null,
	);

	if ( class_exists( 'FFMpeg\FFMpeg' ) ) {
		try {
			if ( defined( 'BB_FFMPEG_BINARY_PATH' ) && defined( 'BB_FFPROBE_BINARY_PATH' ) ) {
				$retval['ffmpeg'] = FFMpeg\FFMpeg::create(
					array(
						'ffmpeg.binaries'  => BB_FFMPEG_BINARY_PATH,
						'ffprobe.binaries' => BB_FFPROBE_BINARY_PATH,
						'timeout'          => 3600, // The timeout for the underlying process.
						'ffmpeg.threads'   => 12,   // The number of threads that FFMpeg should use.
					)
				);

			} else {
				$retval['ffmpeg'] = FFMpeg\FFMpeg::create();
			}
		} catch ( Exception $e ) {
			$retval['error'] = $e->getMessage();
		}
	} else {
		$retval['error'] = __( 'FFMpeg\FFMpeg class not found', 'buddyboss' );
	}

	return (object) $retval;
}

/**
 * Use the ffprobe binary if constant is defined.
 *
 * @return \FFMpeg\FFProbe
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_check_is_ffprobe_binary() {

	$retval = array(
		'ffprob' => null,
		'error'  => null,
	);

	if ( class_exists( 'FFMpeg\FFMpeg' ) && class_exists( 'FFMpeg\FFProbe' ) ) {
		try {
			if ( defined( 'BB_FFMPEG_BINARY_PATH' ) && defined( 'BB_FFPROBE_BINARY_PATH' ) ) {
				$retval['ffprob'] = FFMpeg\FFProbe::create(
					array(
						'ffmpeg.binaries'  => BB_FFMPEG_BINARY_PATH,
						'ffprobe.binaries' => BB_FFPROBE_BINARY_PATH,
						'timeout'          => 3600, // The timeout for the underlying process.
						'ffmpeg.threads'   => 12,   // The number of threads that FFMpeg should use.
					)
				);

			} else {
				$retval['ffprob'] = FFMpeg\FFProbe::create();
			}
		} catch ( Exception $e ) {
			$retval['error'] = $e->getMessage();
		}
	} else {
		$retval['error'] = __( 'FFMpeg\FFProbe class not found', 'buddyboss' );
	}

	return (object) $retval;
}

/**
 * Get video thumbnail image sizes to register.
 *
 * @return array Image sizes.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_get_image_sizes() {
	$image_sizes = array(
		'bb-video-profile-album-add-thumbnail-directory-poster-image' => array(
			'height' => 267,
			'width'  => 400,
		),
		'bb-video-poster-popup-image' => array(
			'height' => 900,
			'width'  => 1500,
		),
		'bb-video-activity-image'     => array(
			'height' => 400,
			'width'  => 640,
		),
	);

	/**
	 * Filter here to video image sizes.
	 *
	 * @param array $image_sizes Image sizes.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (array) apply_filters( 'bb_video_get_image_sizes', $image_sizes );
}

/**
 * Add video upload filters.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_add_upload_filters() {
	add_filter( 'upload_dir', 'bp_video_upload_dir' );
	add_filter( 'upload_mimes', 'bp_video_allowed_mimes', 9, 1 );
}

/**
 * Remove video upload filters.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_remove_upload_filters() {
	remove_filter( 'upload_dir', 'bp_video_upload_dir' );
	remove_filter( 'upload_mimes', 'bp_video_allowed_mimes' );
}

/**
 * Add video upload filters.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_image_add_upload_filters() {
	add_filter( 'upload_dir', 'bp_video_upload_dir_script' );
	add_filter( 'intermediate_image_sizes_advanced', 'bb_video_remove_default_image_sizes' );
	add_filter( 'upload_mimes', 'bb_video_thumb_cover_allowed_mimes', 9, 1 );
	add_filter( 'big_image_size_threshold', '__return_false' );
}

/**
 * Remove video upload filters.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_image_remove_upload_filters() {
	remove_filter( 'upload_dir', 'bp_video_upload_dir_script' );
	remove_filter( 'intermediate_image_sizes_advanced', 'bb_video_remove_default_image_sizes' );
	remove_filter( 'upload_mimes', 'bb_video_thumb_cover_allowed_mimes' );
	remove_filter( 'big_image_size_threshold', '__return_false' );
}

/**
 * This will remove the default image sizes registered.
 *
 * @param array $sizes Image sizes registered.
 *
 * @return array Empty array.
 * @since BuddyBoss 1.7.0
 */
function bb_video_remove_default_image_sizes( $sizes ) {

	if ( isset( $sizes['bb-video-profile-album-add-thumbnail-directory-poster-image'] ) && isset( $sizes['bb-video-poster-popup-image'] ) && isset( $sizes['bb-video-activity-image'] ) ) {
		return array(
			'bb-video-profile-album-add-thumbnail-directory-poster-image' => $sizes['bb-video-profile-album-add-thumbnail-directory-poster-image'],
			'bb-video-poster-popup-image' => $sizes['bb-video-poster-popup-image'],
			'bb-video-activity-image'     => $sizes['bb-video-activity-image'],
		);
	}

	return array();
}

/**
 * Mine type for uploader allowed by buddyboss video for security reason.
 *
 * @param Array $mime_types Mime type information.
 *
 * @return Array
 * @since BuddyBoss 1.7.0
 */
function bb_video_thumb_cover_allowed_mimes( $mime_types ) {

	// Creating a new array will reset the allowed filetypes.
	$mime_types = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'bmp'          => 'image/bmp',
	);

	return $mime_types;
}

/**
 * Register video image sizes.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_register_image_sizes() {
	$image_sizes = bb_video_get_image_sizes();
	if ( ! empty( $image_sizes ) ) {
		foreach ( $image_sizes as $name => $image_size ) {
			if ( ! empty( $image_size['width'] ) && ! empty( $image_size['height'] ) && 0 < (int) $image_size['width'] && 0 < $image_size['height'] ) {
				add_image_size( sanitize_key( $name ), $image_size['width'], $image_size['height'] );
			}
		}
	}
}

/**
 * Deregister video image sizes.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_deregister_image_sizes() {
	$image_sizes = bb_video_get_image_sizes();
	if ( ! empty( $image_sizes ) ) {
		foreach ( $image_sizes as $name => $image_size ) {
			remove_image_size( sanitize_key( $name ) );
		}
	}
}

/**
 * Add video upload filters.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_add_thumb_image_add_upload_filters() {
	add_filter( 'upload_dir', 'bp_video_upload_dir' );
	add_filter( 'intermediate_image_sizes_advanced', 'bb_video_remove_default_image_sizes' );
	add_filter( 'upload_mimes', 'bb_video_thumb_cover_allowed_mimes', 9, 1 );
	add_filter( 'big_image_size_threshold', '__return_false' );
}

/**
 * Remove video upload filters.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_add_thumb_image_remove_upload_filters() {
	remove_filter( 'upload_dir', 'bp_video_upload_dir' );
	remove_filter( 'intermediate_image_sizes_advanced', 'bb_video_remove_default_image_sizes' );
	remove_filter( 'upload_mimes', 'bb_video_thumb_cover_allowed_mimes' );
	remove_filter( 'big_image_size_threshold', '__return_false' );
}

/**
 * Return the video attachment symlink.
 *
 * @param int|object $video         Video id or a video object.
 * @param int        $attachment_id Attachment id.
 * @param string     $size          Size to get symlink.
 * @param bool       $generate      Generate Symlink or not.
 *
 * @return array|false|string|string[]|void
 *
 * @since BuddyBoss 1.7.0
 */
function bb_video_get_attachment_symlink( $video, $attachment_id, $size, $generate = true ) {

	// Check if video is id of video, create video object.
	if ( ! $video instanceof BP_Video && is_int( $video ) ) {
		$video = new BP_Video( $video );
	}

	// Return if no video found.
	if ( empty( $video ) || empty( $attachment_id ) || empty( $size ) ) {
		return;
	}

	/**
	 * Filter here to allow/disallow video symlinks.
	 *
	 * @param bool   $do_symlink    Default true.
	 * @param int    $video_id      Video id
	 * @param int    $attachment_id Document attachment id.
	 * @param string $size          Size.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	$do_symlink = apply_filters( 'bb_video_create_thumb_symlinks', true, $video->id, $attachment_id, $size );

	if ( $do_symlink ) {

		if ( ! bb_enable_symlinks() ) {
			$video_id       = 'forbidden_' . $video->id;
			$attachment_id  = 'forbidden_' . $attachment_id;
			$attachment_url = untrailingslashit( home_url( '/' ) . 'bb-video-thumb-preview/' . base64_encode( $attachment_id ) . '/' . base64_encode( $video_id ) . '/' . $size );

		} else {

			$upload_dir = wp_upload_dir();
			$upload_dir = $upload_dir['basedir'];

			// Get video previews symlink directory path.
			$video_symlinks_path = bb_video_symlink_path();
			$privacy             = $video->privacy;
			$output_file_src     = '';
			$attachment_path     = $video_symlinks_path . '/' . md5( $video->id . $attachment_id . $privacy . $size );

			if ( $video->group_id > 0 && bp_is_active( 'groups' ) ) {
				$group_object    = groups_get_group( $video->group_id );
				$group_status    = bp_get_group_status( $group_object );
				$attachment_path = $video_symlinks_path . '/' . md5( $video->id . $attachment_id . $group_status . $privacy . $size );
			}

			$file = image_get_intermediate_size( $attachment_id, $size );

			if ( $file && ! empty( $file['path'] ) ) {
				$output_file_src = $upload_dir . '/' . $file['path'];

				// Regenerate attachment thumbnails.
				if ( ! file_exists( $output_file_src ) ) {
					bp_video_regenerate_attachment_thumbnails( $attachment_id );
				}
			} elseif ( ! $file ) {

				bp_video_regenerate_attachment_thumbnails( $attachment_id );

				$file = image_get_intermediate_size( $attachment_id, $size );

				if ( $file && ! empty( $file['path'] ) ) {

					$output_file_src = $upload_dir . '/' . $file['path'];

				} elseif ( wp_get_attachment_image_src( $attachment_id ) ) {

					$output_file_src = get_attached_file( $attachment_id );

					// Regenerate attachment thumbnails.
					if ( ! file_exists( $output_file_src ) ) {
						bp_video_regenerate_attachment_thumbnails( $attachment_id );
						$file = image_get_intermediate_size( $attachment_id, $size );
					}
				}
			} elseif ( wp_get_attachment_image_src( $attachment_id ) ) {

				$output_file_src = get_attached_file( $attachment_id );

				// Regenerate attachment thumbnails.
				if ( ! file_exists( $output_file_src ) ) {
					bp_video_regenerate_attachment_thumbnails( $attachment_id );
					$file = image_get_intermediate_size( $attachment_id, $size );
				}
			}

			// Override the video attachment id to given thumbnail id.
			$video->attachment_id = $attachment_id;

			if ( $generate ) {
				// Generate Video Thumb Symlink.
				bb_core_symlink_generator( 'video_thumb', $video, $size, $file, $output_file_src, $attachment_path );
			}

			$upload_directory = wp_get_upload_dir();
			$attachment_url   = bb_core_symlink_absolute_path( $attachment_path, $upload_directory );

			/**
			 * Filter for the after thumb symlink generate.
			 *
			 * @param string $attachment_url Attachment URL.
			 * @param object $video          Video Object.
			 *
			 * @since BuddyBoss 1.7.0.1
			 */
			$attachment_url = apply_filters( 'bb_video_after_get_attachment_symlink', $attachment_url, $video );
		}
	} else {
		$attachment_url = wp_get_attachment_url( $attachment_id );
	}

	return $attachment_url;
}
