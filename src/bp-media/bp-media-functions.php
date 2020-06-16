<?php
/**
 * BuddyBoss Media Functions.
 *
 * Functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 *
 * @package BuddyBoss\Media\Functions
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Create and upload the media file
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array|null|WP_Error|WP_Post
 */
function bp_media_upload() {
	/**
	 * Make sure user is logged in
	 */
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'not_logged_in', __( 'Please login in order to upload file media.', 'buddyboss' ), array( 'status' => 500 ) );
	}

	$attachment = bp_media_upload_handler();

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
 * Mine type for uploader allowed by buddyboss media for security reason
 *
 * @param  Array $mime_types carry mime information
 * @since BuddyBoss 1.0.0
 *
 * @return Array
 */
function bp_media_allowed_mimes( $mime_types ) {

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
 * Media upload handler
 *
 * @param string $file_id
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array|int|null|WP_Error|WP_Post
 */
function bp_media_upload_handler( $file_id = 'file' ) {

	/**
	 * Include required files
	 */

	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin' . '/includes/image.php';
		require_once ABSPATH . 'wp-admin' . '/includes/file.php';
		require_once ABSPATH . 'wp-admin' . '/includes/media.php';
	}

	if ( ! function_exists( 'media_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/admin.php';
	}

	add_image_size( 'bp-media-thumbnail', 400, 400 );
	add_image_size( 'bp-activity-media-thumbnail', 1600, 1600 );

	add_filter( 'upload_mimes', 'bp_media_allowed_mimes', 9, 1 );

	$aid = media_handle_upload(
		$file_id,
		0,
		array(),
		array(
			'test_form'            => false,
			'upload_error_strings' => array(
				false,
				__( 'The uploaded file exceeds ', 'buddyboss' ) . bp_media_file_upload_max_size( true ),
				__( 'The uploaded file exceeds ', 'buddyboss' ) . bp_media_file_upload_max_size( true ),
				__( 'The uploaded file was only partially uploaded.', 'buddyboss' ),
				__( 'No file was uploaded.', 'buddyboss' ),
				'',
				__( 'Missing a temporary folder.', 'buddyboss' ),
				__( 'Failed to write file to disk.', 'buddyboss' ),
				__( 'File upload stopped by extension.', 'buddyboss' ),
			),
		)
	);

	remove_image_size( 'bp-media-thumbnail' );
	remove_image_size( 'bp-activity-media-thumbnail' );

	// if has wp error then throw it.
	if ( is_wp_error( $aid ) ) {
		return $aid;
	}

	// Image rotation fix
	do_action( 'bp_media_attachment_uploaded', $aid );

	$attachment = get_post( $aid );

	if ( ! empty( $attachment ) ) {
		update_post_meta( $attachment->ID, 'bp_media_upload', true );
		update_post_meta( $attachment->ID, 'bp_media_saved', '0' );
		return $attachment;
	}

	return new WP_Error( 'error_uploading', __( 'Error while uploading media.', 'buddyboss' ), array( 'status' => 500 ) );

}

/**
 * Compress the image
 *
 * @param $source
 * @param $destination
 * @param int         $quality
 *
 * @since BuddyBoss 1.0.0
 *
 * @return mixed
 */
function bp_media_compress_image( $source, $destination, $quality = 90 ) {

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
 * Get file media upload max size
 *
 * @param bool $post_string
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_media_file_upload_max_size( $post_string = false, $type = 'bytes' ) {
	static $max_size = - 1;

	if ( $max_size < 0 ) {
		// Start with post_max_size.
		$size = @ini_get( 'post_max_size' );
		$unit = preg_replace( '/[^bkmgtpezy]/i', '', $size ); // Remove the non-unit characters from the size.
		$size = preg_replace( '/[^0-9\.]/', '', $size ); // Remove the non-numeric characters from the size.
		if ( $unit ) {
			$post_max_size = round( $size * pow( 1024, stripos( 'bkmgtpezy', $unit[0] ) ) );
		} else {
			$post_max_size = round( $size );
		}

		if ( $post_max_size > 0 ) {
			$max_size = $post_max_size;
		}

		// If upload_max_size is less, then reduce. Except if upload_max_size is
		// zero, which indicates no limit.
		$size = @ini_get( 'upload_max_filesize' );
		$unit = preg_replace( '/[^bkmgtpezy]/i', '', $size ); // Remove the non-unit characters from the size.
		$size = preg_replace( '/[^0-9\.]/', '', $size ); // Remove the non-numeric characters from the size.
		if ( $unit ) {
			$upload_max = round( $size * pow( 1024, stripos( 'bkmgtpezy', $unit[0] ) ) );
		} else {
			$upload_max = round( $size );
		}
		if ( $upload_max > 0 && $upload_max < $max_size ) {
			$max_size = $upload_max;
		}
	}

	return apply_filters( 'bp_media_file_upload_max_size', bp_media_format_size_units( $max_size, $post_string, $type ) );
}

/**
 * Format file size units
 *
 * @param $bytes
 * @param bool  $post_string
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_media_format_size_units( $bytes, $post_string = false, $type = 'bytes' ) {

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
 * Retrieve an media or medias.
 *
 * The bp_media_get() function shares all arguments with BP_Media::get().
 * The following is a list of bp_media_get() parameters that have different
 * default values from BP_Media::get() (value in parentheses is
 * the default for the bp_media_get()).
 *   - 'per_page' (false)
 *
 * @since BuddyBoss 1.0.0
 *
 * @see BP_Media::get() For more information on accepted arguments
 *      and the format of the returned value.
 *
 * @param array|string $args See BP_Media::get() for description.
 * @return array $media See BP_Media::get() for description.
 */
function bp_media_get( $args = '' ) {

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
			'privacy'      => false,        // privacy of media
			'exclude'      => false,        // Comma-separated list of activity IDs to exclude.
			'count_total'  => false,
		),
		'media_get'
	);

	$media = BP_Media::get(
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
	 * Filters the requested media item(s).
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param BP_Media  $media Requested media object.
	 * @param array     $r     Arguments used for the media query.
	 */
	return apply_filters_ref_array( 'bp_media_get', array( &$media, &$r ) );
}

/**
 * Fetch specific media items.
 *
 * @since BuddyBoss 1.0.0
 *
 * @see BP_Media::get() For more information on accepted arguments.
 *
 * @param array|string $args {
 *     All arguments and defaults are shared with BP_Media::get(),
 *     except for the following:
 *     @type string|int|array Single media ID, comma-separated list of IDs,
 *                            or array of IDs.
 * }
 * @return array $activity See BP_Media::get() for description.
 */
function bp_media_get_specific( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'media_ids' => false,      // A single media_id or array of IDs.
			'max'       => false,      // Maximum number of results to return.
			'page'      => 1,          // Page 1 without a per_page will result in no pagination.
			'per_page'  => false,      // Results per page.
			'sort'      => 'DESC',     // Sort ASC or DESC.
			'order_by'  => false,     // Sort ASC or DESC.
			'privacy'   => false,     // privacy to filter.
		),
		'media_get_specific'
	);

	$get_args = array(
		'in'       => $r['media_ids'],
		'max'      => $r['max'],
		'page'     => $r['page'],
		'per_page' => $r['per_page'],
		'sort'     => $r['sort'],
		'order_by' => $r['order_by'],
		'privacy'  => $r['privacy'],
	);

	/**
	 * Filters the requested specific media item.
	 *
	 * @since BuddyBoss
	 *
	 * @param BP_Media      $media    Requested media object.
	 * @param array         $args     Original passed in arguments.
	 * @param array         $get_args Constructed arguments used with request.
	 */
	return apply_filters( 'bp_media_get_specific', BP_Media::get( $get_args ), $args, $get_args );
}

/**
 * Add an media item.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array|string $args {
 *     An array of arguments.
 *     @type int|bool $id                Pass an media ID to update an existing item, or
 *                                       false to create a new item. Default: false.
 *     @type int|bool $blog_id           ID of the blog Default: current blog id.
 *     @type int|bool $attchment_id      ID of the attachment Default: false
 *     @type int|bool $user_id           Optional. The ID of the user associated with the activity
 *                                       item. May be set to false or 0 if the item is not related
 *                                       to any user. Default: the ID of the currently logged-in user.
 *     @type string   $title             Optional. The title of the media item.

 *     @type int      $album_id          Optional. The ID of the associated album.
 *     @type int      $group_id          Optional. The ID of a associated group.
 *     @type int      $activity_id       Optional. The ID of a associated activity.
 *     @type string   $privacy           Optional. Privacy of the media Default: public
 *     @type int      $menu_order        Optional. Menu order the media Default: false
 *     @type string   $date_created      Optional. The GMT time, in Y-m-d h:i:s format, when
 *                                       the item was recorded. Defaults to the current time.
 *     @type string   $error_type        Optional. Error type. Either 'bool' or 'wp_error'. Default: 'bool'.
 * }
 * @return WP_Error|bool|int The ID of the media on success. False on error.
 */
function bp_media_add( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'id'            => false,                   // Pass an existing media ID to update an existing entry.
			'blog_id'       => get_current_blog_id(),   // Blog ID.
			'attachment_id' => false,                   // attachment id.
			'user_id'       => bp_loggedin_user_id(),   // user_id of the uploader.
			'title'         => '',                      // title of media being added.
			'album_id'      => false,                   // Optional: ID of the album.
			'group_id'      => false,                   // Optional: ID of the group.
			'activity_id'   => false,                   // The ID of activity.
			'privacy'       => 'public',                // Optional: privacy of the media e.g. public.
			'menu_order'    => 0,                       // Optional:  Menu order.
			'date_created'  => bp_core_current_time(),  // The GMT time that this media was recorded.
			'error_type'    => 'bool',
		),
		'media_add'
	);

	// Setup media to be added.
	$media                = new BP_Media( $r['id'] );
	$media->blog_id       = $r['blog_id'];
	$media->attachment_id = $r['attachment_id'];
	$media->user_id       = (int) $r['user_id'];
	$media->title         = $r['title'];
	$media->album_id      = (int) $r['album_id'];
	$media->group_id      = (int) $r['group_id'];
	$media->activity_id   = (int) $r['activity_id'];
	$media->privacy       = $r['privacy'];
	$media->menu_order    = $r['menu_order'];
	$media->date_created  = $r['date_created'];
	$media->error_type    = $r['error_type'];

	// groups document always have privacy to `grouponly`.
	if ( ! empty( $media->privacy ) && ( in_array( $media->privacy, array( 'forums', 'message' ), true ) ) ) {
		$media->privacy = $r['privacy'];
	} elseif ( ! empty( $media->group_id ) ) {
		$media->privacy = 'grouponly';
	} elseif ( ! empty( $media->folder_id ) ) {
		$album = new BP_Media_Album( $media->album_id );
		if ( ! empty( $album ) ) {
			$media->privacy = $album->privacy;
		}
	}

	if ( isset( $_POST ) && isset( $_POST['action'] ) && 'groups_get_group_members_send_message' === $_POST['action'] ) {
		$media->privacy ='message';
	}

	// save media.
	$save = $media->save();

	if ( 'wp_error' === $r['error_type'] && is_wp_error( $save ) ) {
		return $save;
	} elseif ( 'bool' === $r['error_type'] && false === $save ) {
		return false;
	}

	//media is saved for attachment.
	update_post_meta( $media->attachment_id, 'bp_media_saved', true );

	/**
	 * Fires at the end of the execution of adding a new media item, before returning the new media item ID.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param object $media Media object.
	 */
	do_action( 'bp_media_add', $media );

	return $media->id;
}

/**
 * Media add handler function
 *
 * @since BuddyBoss 1.2.0
 * @param array $medias
 *
 * @return mixed|void
 */
function bp_media_add_handler( $medias = array() ) {
	global $bp_media_upload_count;
	$media_ids = array();

	if ( empty( $medias ) && ! empty( $_POST['medias'] ) ) {
		$medias = $_POST['medias'];
	}

	$privacy = ! empty( $_POST['privacy'] ) && in_array( $_POST['privacy'], array_keys( bp_media_get_visibility_levels() ) ) ? $_POST['privacy'] : 'public';

	if ( ! empty( $medias ) && is_array( $medias ) ) {

		// update count of media for later use.
		$bp_media_upload_count = count( $medias );

		// save  media.
		foreach ( $medias as $media ) {

			$media_id = bp_media_add( array(
				'attachment_id' => $media['id'],
				'title'         => $media['name'],
				'album_id'      => ! empty( $media['album_id'] ) ? $media['album_id'] : false,
				'group_id'      => ! empty( $media['group_id'] ) ? $media['group_id'] : false,
				'privacy'       => ! empty( $media['privacy'] ) && in_array( $media['privacy'], array_merge( array_keys( bp_media_get_visibility_levels() ), array( 'message' ) ) ) ? $media['privacy'] : $privacy,
			) );

			if ( $media_id ) {
				$media_ids[] = $media_id;
			}
		}
	}

	/**
	 * Fires at the end of the execution of adding saving a media item, before returning the new media items in ajax response.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param array $media_ids Media IDs.
	 * @param array $medias Array of media from POST object or in function parameter.
	 */
	return apply_filters( 'bp_media_add_handler', $media_ids, (array) $medias );
}

/**
 * Delete media.
 *
 * @since BuddyBoss 1.0.0
 * @since BuddyBoss 1.2.0
 *
 * @param array|string $args To delete specific media items, use
 *                           $args = array( 'id' => $ids ); Otherwise, to use
 *                           filters for item deletion, the argument format is
 *                           the same as BP_Media::get().
 *                           See that method for a description.
 * @param bool $from Context of deletion from. ex. attachment, activity etc.
 *
 * @return bool|int The ID of the media on success. False on error.
 */
function bp_media_delete( $args = '', $from = false ) {

	// Pass one or more the of following variables to delete by those variables.
	$args = bp_parse_args( $args, array(
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
	) );

	/**
	 * Fires before an media item proceeds to be deleted.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param array $args Array of arguments to be used with the media deletion.
	 */
	do_action( 'bp_before_media_delete', $args );

	$media_ids_deleted = BP_Media::delete( $args, $from );
	if ( empty( $media_ids_deleted ) ) {
		return false;
	}

	/**
	 * Fires after the media item has been deleted.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param array $args Array of arguments used with the media deletion.
	 */
	do_action( 'bp_media_delete', $args );

	/**
	 * Fires after the media item has been deleted.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param array $media_ids_deleted Array of affected media item IDs.
	 */
	do_action( 'bp_media_deleted_medias', $media_ids_deleted );

	return true;
}

/**
 * Completely remove a user's media data.
 *
 * @since BuddyBoss 1.2.0
 *
 * @param int $user_id ID of the user whose media is being deleted.
 * @return bool
 */
function bp_media_remove_all_user_data( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		return false;
	}

	// Clear the user's albums from the sitewide stream and clear their media tables.
	bp_album_delete( array( 'user_id' => $user_id ) );

	// Clear the user's media from the sitewide stream and clear their media tables.
	bp_media_delete( array( 'user_id' => $user_id ) );

	/**
	 * Fires after the removal of all of a user's media data.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param int $user_id ID of the user being deleted.
	 */
	do_action( 'bp_media_remove_all_user_data', $user_id );
}
add_action( 'wpmu_delete_user',  'bp_media_remove_all_user_data' );
add_action( 'delete_user',       'bp_media_remove_all_user_data' );

/**
 * Get media visibility levels out of the $bp global.
 *
 * @since BuddyBoss 1.2.3
 *
 * @return array
 */
function bp_media_get_visibility_levels() {

	/**
	 * Filters the media visibility levels out of the $bp global.
	 *
	 * @since BuddyBoss 1.2.3
	 *
	 * @param array $visibility_levels Array of visibility levels.
	 */
	return apply_filters( 'bp_media_get_visibility_levels', buddypress()->media->visibility_levels );
}

/**
 * Return the media activity.
 *
 * @param $activity_id
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return object|boolean The media activity object or false.
 */
function bp_media_get_media_activity( $activity_id ) {

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
	 * Filters the media activity object being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param object $activity The media activity.
	 */
	return apply_filters( 'bp_media_get_media_activity', $result['activities'][0] );
}

/**
 * Get the media count of a user.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return int media count of the user.
 */
function bp_media_get_total_media_count() {

	add_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_personal_scope', 20 );
	bp_has_media( bp_ajax_querystring( 'media' ) );
	$count = $GLOBALS['media_template']->total_media_count;
	remove_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_personal_scope', 20 );

	/**
	 * Filters the total media count for a given user.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $count Total media count for a given user.
	 */
	return apply_filters( 'bp_media_get_total_media_count', (int) $count );
}

/**
 * Get the groups media count of a given user.
 *
 * @return int media count of the user.
 * @since BuddyBoss .3.6
 */
function bp_media_get_user_total_group_media_count() {

	add_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_groups_scope', 20 );
	bp_has_media( bp_ajax_querystring( 'groups' ) );
	$count = $GLOBALS['media_template']->total_media_count;
	remove_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_groups_scope', 20 );

	/**
	 * Filters the total groups media count for a given user.
	 *
	 * @param int $count Total media count for a given user.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	return apply_filters( 'bp_media_get_user_total_group_media_count', (int) $count );
}

/**
 * Get the media count of a given group.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int $group_id ID of the group whose media are being counted.
 * @return int media count of the group.
 */
function bp_media_get_total_group_media_count( $group_id = 0 ) {
	if ( empty( $group_id ) && bp_get_current_group_id() ) {
		$group_id = bp_get_current_group_id();
	}

	$count = wp_cache_get( 'bp_total_media_for_group_' . $group_id, 'bp' );

	if ( false === $count ) {
		$count = BP_Media::total_group_media_count( $group_id );
		wp_cache_set( 'bp_total_media_for_group_' . $group_id, $count, 'bp' );
	}

	/**
	 * Filters the total media count for a given group.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $count Total media count for a given group.
	 */
	return apply_filters( 'bp_media_get_total_group_media_count', (int) $count );
}

/**
 * Get the album count of a given group.
 *
 * @since BuddyBoss 1.2.0
 *
 * @param int $group_id ID of the group whose album are being counted.
 * @return int album count of the group.
 */
function bp_media_get_total_group_album_count( $group_id = 0 ) {
	if ( empty( $group_id ) && bp_get_current_group_id() ) {
		$group_id = bp_get_current_group_id();
	}

	$count = wp_cache_get( 'bp_total_album_for_group_' . $group_id, 'bp' );

	if ( false === $count ) {
		$count = BP_Media_Album::total_group_album_count( $group_id );
		wp_cache_set( 'bp_total_album_for_group_' . $group_id, $count, 'bp' );
	}

	/**
	 * Filters the total album count for a given group.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param int $count Total album count for a given group.
	 */
	return apply_filters( 'bp_media_get_total_group_album_count', (int) $count );
}

/**
 * Return the total media count in your BP instance.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return int Media count.
 */
function bp_get_total_media_count() {

	add_filter( 'bp_ajax_querystring', 'bp_media_object_results_media_all_scope', 20 );
	bp_has_media( bp_ajax_querystring( 'media' ) );
	remove_filter( 'bp_ajax_querystring', 'bp_media_object_results_media_all_scope', 20 );
	$count = $GLOBALS['media_template']->total_media_count;

	/**
	 * Filters the total number of media.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $count Total number of media.
	 */
	return apply_filters( 'bp_get_total_media_count', (int) $count );
}

/**
 * Media results all scope.
 *
 * @since BuddyBoss 1.1.9
 */
function bp_media_object_results_media_all_scope( $querystring ) {
	$querystring = wp_parse_args( $querystring );

	$querystring['scope'] = array();

	if ( bp_is_active( 'friends' ) ) {
		$querystring['scope'][] = 'friends';
	}

	if ( bp_is_active( 'groups' ) ) {
		$querystring['scope'][] = 'groups';
	}

	if ( is_user_logged_in() ) {
		$querystring['scope'][] = 'personal';
	}

	$querystring['page']        = 1;
	$querystring['per_page']    = 1;
	$querystring['user_id']     = 0;
	$querystring['count_total'] = true;
	return http_build_query( $querystring );
}


/**
 * Object template results media personal scope.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_object_template_results_media_personal_scope( $querystring ) {
	$querystring = wp_parse_args( $querystring );

	$querystring['scope']       = 'personal';
	$querystring['page']        = 1;
	$querystring['per_page']    = '1';
	$querystring['user_id']     = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	$querystring['count_total'] = true;

	return http_build_query( $querystring );
}

/**
 * Object template results media groups scope.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_object_template_results_media_groups_scope( $querystring ) {
	$querystring = wp_parse_args( $querystring );

	$querystring['scope']       = 'groups';
	$querystring['page']        = 1;
	$querystring['per_page']    = 1;
	$querystring['user_id']     = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	$querystring['count_total'] = true;

	return http_build_query( $querystring );
}

// ******************** Albums *********************/
/**
 * Retrieve an album or albums.
 *
 * The bp_album_get() function shares all arguments with BP_Media_Album::get().
 * The following is a list of bp_album_get() parameters that have different
 * default values from BP_Media_Album::get() (value in parentheses is
 * the default for the bp_album_get()).
 *   - 'per_page' (false)
 *
 * @since BuddyBoss 1.0.0
 *
 * @see BP_Media_Album::get() For more information on accepted arguments
 *      and the format of the returned value.
 *
 * @param array|string $args See BP_Media_Album::get() for description.
 * @return array $activity See BP_Media_Album::get() for description.
 */
function bp_album_get( $args = '' ) {

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

	$album = BP_Media_Album::get(
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
	 * @since BuddyBoss 1.0.0
	 *
	 * @param BP_Media  $album Requested media object.
	 * @param array     $r     Arguments used for the album query.
	 */
	return apply_filters_ref_array( 'bp_album_get', array( &$album, &$r ) );
}

/**
 * Fetch specific albums.
 *
 * @since BuddyBoss 1.0.0
 *
 * @see BP_Media_Album::get() For more information on accepted arguments.
 *
 * @param array|string $args {
 *     All arguments and defaults are shared with BP_Media_Album::get(),
 *     except for the following:
 *     @type string|int|array Single album ID, comma-separated list of IDs,
 *                            or array of IDs.
 * }
 * @return array $albums See BP_Media_Album::get() for description.
 */
function bp_album_get_specific( $args = '' ) {

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
		'media_get_specific'
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
	 * @param BP_Media      $album    Requested media object.
	 * @param array         $args     Original passed in arguments.
	 * @param array         $get_args Constructed arguments used with request.
	 */
	return apply_filters( 'bp_album_get_specific', BP_Media_Album::get( $get_args ), $args, $get_args );
}

/**
 * Add album item.
 *
 * @since BuddyBoss 1.0.0
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
function bp_album_add( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'id'           => false,                  // Pass an existing album ID to update an existing entry.
			'user_id'      => bp_loggedin_user_id(),                     // User ID
			'group_id'     => false,                  // attachment id.
			'title'        => '',                     // title of album being added.
			'privacy'      => 'public',                  // Optional: privacy of the media e.g. public.
			'date_created' => bp_core_current_time(), // The GMT time that this media was recorded
			'error_type'   => 'bool',
		),
		'album_add'
	);

	// Setup media to be added.
	$album               = new BP_Media_Album( $r['id'] );
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
	 * @since BuddyBoss 1.0.0
	 *
	 * @param object $album Album object.
	 */
	do_action( 'bp_album_add', $album );

	return $album->id;
}

/**
 * Delete album item.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array|string $args To delete specific album items, use
 *                           $args = array( 'id' => $ids ); Otherwise, to use
 *                           filters for item deletion, the argument format is
 *                           the same as BP_Media_Album::get().
 *                           See that method for a description.
 *
 * @return bool True on Success. False on error.
 */
function bp_album_delete( $args ) {

	// Pass one or more the of following variables to delete by those variables.
	$args = bp_parse_args( $args, array(
		'id'            => false,
		'user_id'       => false,
		'group_id'      => false,
		'date_created'  => false,
	) );

	/**
	 * Fires before an album item proceeds to be deleted.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param array $args Array of arguments to be used with the album deletion.
	 */
	do_action( 'bp_before_album_delete', $args );

	$album_ids_deleted = BP_Media_Album::delete( $args );
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
	do_action( 'bp_album_delete', $args );

	/**
	 * Fires after the album item has been deleted.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param array $album_ids_deleted Array of affected album item IDs.
	 */
	do_action( 'bp_albums_deleted_albums', $album_ids_deleted );

	return true;
}

/**
 * Fetch a single album object.
 *
 * When calling up a album object, you should always use this function instead
 * of instantiating BP_Media_Album directly, so that you will inherit cache
 * support and pass through the albums_get_album filter.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int $album_id ID of the album.
 * @return BP_Media_Album $album The album object.
 */
function albums_get_album( $album_id ) {

	$album = new BP_Media_Album( $album_id );

	/**
	 * Filters a single album object.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param BP_Media_Album $album Single album object.
	 */
	return apply_filters( 'albums_get_album', $album );
}

/**
 * Check album access for current user or guest
 *
 * @since BuddyBoss 1.0.0
 * @param $album_id
 *
 * @return bool
 */
function albums_check_album_access( $album_id ) {

	$album = albums_get_album( $album_id );

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
 * @since BuddyBoss 1.0.0
 */
function bp_media_delete_orphaned_attachments() {

	$orphaned_attachment_args = array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'fields'         => 'ids',
		'posts_per_page' => - 1,
		'meta_query'     => array(
			array(
				'key'     => 'bp_media_saved',
				'value'   => '0',
				'compare' => '=',
			),
		),
	);

	$orphaned_attachment_query = new WP_Query( $orphaned_attachment_args );

	if ( $orphaned_attachment_query->post_count > 0 ) {
		foreach( $orphaned_attachment_query->posts as $a_id ) {
			wp_delete_attachment( $a_id, true );
		}
	}
}

/**
 * Download an image from the specified URL and attach it to a post.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $file The URL of the image to download
 *
 * @return int|void
 */
function bp_media_sideload_attachment( $file ) {
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
	$id = bp_media_handle_sideload( $file_array );

	// If error storing permanently, unlink.
	if ( is_wp_error( $id ) ) {
		return;
	}

	return $id;
}

/**
 * This handles a sideloaded file in the same way as an uploaded file is handled by {@link media_handle_upload()}
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $file_array Array similar to a {@link $_FILES} upload array
 * @param array $post_data  allows you to overwrite some of the attachment
 *
 * @return int|object The ID of the attachment or a WP_Error on failure
 */
function bp_media_handle_sideload( $file_array, $post_data = array() ) {

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
 * @since BuddyBoss 1.0.0
 */
function bp_media_import_buddyboss_media_tables() {
	global $wpdb;
	global $bp;

	$buddyboss_media_table        = $bp->table_prefix . 'buddyboss_media';
	$buddyboss_media_albums_table = $bp->table_prefix . 'buddyboss_media_albums';

	$total_media  = $wpdb->get_var( "SELECT COUNT(*) FROM {$buddyboss_media_table}" );
	$total_albums = $wpdb->get_var( "SELECT COUNT(*) FROM {$buddyboss_media_albums_table}" );

	update_option( 'bp_media_import_total_media', $total_media );
	update_option( 'bp_media_import_total_albums', $total_albums );

	$albums_done      = get_option( 'bp_media_import_albums_done', 0 );
	$run_albums_query = $albums_done != $total_albums;

	if ( $run_albums_query ) {

		$albums = $wpdb->get_results( "SELECT * FROM {$buddyboss_media_albums_table} LIMIT 100 OFFSET {$albums_done}" );

		$album_ids = get_option( 'bp_media_import_albums_ids', array() );

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

				$album_id = bp_album_add( $album_args );

				if ( ! empty( $album_id ) ) {
					$album_ids[ $album_id ] = $album->id;
				}

				$albums_count ++;

				update_option( 'bp_media_import_albums_done', $albums_count );
			}
		}
		update_option( 'bp_media_import_albums_ids', $album_ids );
	}

	if ( ! $run_albums_query ) {

		$media_done         = get_option( 'bp_media_import_media_done', 0 );
		$album_ids          = get_option( 'bp_media_import_albums_ids', array() );
		$imported_media_ids = get_option( 'bp_media_import_media_ids', array() );

		$medias = $wpdb->get_results( "SELECT * FROM {$buddyboss_media_table} LIMIT 100 OFFSET {$media_done}" );

		if ( ! empty( $medias ) ) {

			$activity_ids = array();
			$media_done   = (int) $media_done;

			foreach ( $medias as $media ) {

				$attachment_id = ! empty( $media->media_id ) ? $media->media_id : false;
				$user_id       = ! empty( $media->media_author ) ? $media->media_author : false;
				$title         = ! empty( $media->media_title ) ? $media->media_title : '';
				$activity_id   = ! empty( $media->activity_id ) ? $media->activity_id : false;

				if ( ! empty( $activity_id ) ) {
					$activity_ids[ $activity_id ] = array();
				}

				$media_args = array(
					'attachment_id' => $attachment_id,
					'user_id'       => $user_id,
					'title'         => $title,
				);

				if ( ! empty( $media->album_id ) && ! empty( $album_ids ) ) {
					$album_id_key = array_search( $media->album_id, $album_ids );

					if ( ! empty( $album_id_key ) ) {
						$album_id = $album_id_key;

						$media_args['album_id'] = $album_id;
					}
				}

				if ( ! empty( $media->upload_date ) && '0000-00-00 00:00:00' != $media->upload_date ) {
					$date_created = $media->upload_date;
				} elseif ( ! empty( $media->upload_date ) && '0000-00-00 00:00:00' == $media->upload_date && ! empty( $attachment_id ) ) {
					$date_created = get_the_date( $attachment_id );
				} else {
					$date_created = bp_core_current_time();
				}

				$media_args['date_created'] = $date_created;

				if ( ! empty( $media->privacy ) ) {
					if ( 'private' == $media->privacy ) {
						$privacy = 'onlyme';
					} elseif ( 'members' == $media->privacy ) {
						$privacy = 'loggedin';
					} else {
						$privacy = $media->privacy;
					}
				} else {
					$privacy = 'public';
				}

				$media_args['privacy'] = $privacy;

				if ( bp_is_active( 'activity' ) ) {

					$activity_args = array(
						'user_id'       => $user_id,
						'recorded_time' => $date_created,
						'hide_sitewide' => true,
						'privacy'       => 'media',
						'type'          => 'activity_update',
						'component'     => buddypress()->activity->id,
					);

					if ( ! empty( $activity_id ) ) {

						$activity = new BP_Activity_Activity( $activity_id );

						if ( ! empty( $activity->id ) ) {

							$activity_args['recorded_time'] = $activity->date_recorded;

							if ( 'groups' == $activity->component ) {
								$media_args['group_id'] = $activity->item_id;

								$activity_args['component'] = buddypress()->groups->id;
								$activity_args['item_id']   = $activity->item_id;
							}
						}
					}

					// make an activity for the media
					$sub_activity_id = bp_activity_add( $activity_args );

					if ( $sub_activity_id ) {
						// update activity meta
						bp_activity_update_meta( $sub_activity_id, 'bp_media_activity', '1' );

						$media_args['activity_id'] = $sub_activity_id;
					}
				}

				$media_id = bp_media_add( $media_args );

				if ( ! empty( $media_id ) && ! empty( $media_args['activity_id'] ) ) {
					update_post_meta( $attachment_id, 'bp_media_activity_id', $media_args['activity_id'] );

					if ( ! empty( $activity_id ) ) {
						update_post_meta( $attachment_id, 'bp_media_parent_activity_id', $activity_id );

						if ( isset( $activity_ids[ $activity_id ] ) ) {
							$activity_ids[ $activity_id ][] = $media_id;
						}
					}

					$imported_media_ids[] = $media_id;
				}

				$media_done ++;

				update_option( 'bp_media_import_media_done', $media_done );
			}
			update_option( 'bp_media_import_media_ids', $imported_media_ids );

			if ( ! empty( $activity_ids ) && bp_is_active( 'activity' ) ) {
				foreach ( $activity_ids as $id => $activity_media ) {
					if ( ! empty( $activity_media ) ) {
						$media_ids = implode( ',', $activity_media );
						bp_activity_update_meta( $id, 'bp_media_ids', $media_ids );
					}
				}
			}
		}
	}
}

/**
 * Import forums media from BuddyBoss Media Plugin
 *
 * @since BuddyBoss 1.0.5
 */
function bp_media_import_buddyboss_forum_media() {

	$forums_done = get_option( 'bp_media_import_forums_done', 0 );

	$forums_media_query = new WP_Query(
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

	if ( ! empty( $forums_media_query->found_posts ) ) {

		$imported_forum_media_ids = get_option( 'bp_media_import_forum_media_ids', array() );

		update_option( 'bp_media_import_forums_total', $forums_media_query->found_posts );

		if ( ! empty( $forums_media_query->posts ) ) {

			$forums_done = (int) $forums_done;
			foreach ( $forums_media_query->posts as $post_id ) {
				$attachment_ids = get_post_meta( $post_id, 'bbm_bbpress_attachment_ids', true );

				// save activity id if it is saved in forums and enabled in platform settings
				$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

				$media_ids = array();
				if ( ! empty( $attachment_ids ) ) {
					foreach ( $attachment_ids as $attachment_id ) {

						$title = get_the_title( $attachment_id );

						$media_id = bp_media_add(
							array(
								'attachment_id' => $attachment_id,
								'title'         => $title,
								'album_id'      => false,
								'group_id'      => false,
								'error_type'    => 'bool',
							)
						);

						if ( $media_id ) {
							$media_ids[] = $media_id;

							// save media is saved in attachment
							update_post_meta( $attachment_id, 'bp_media_saved', true );

							$imported_forum_media_ids[] = $media_id;
						}
					}

					update_option( 'bp_media_import_forum_media_ids', $imported_forum_media_ids );

					$media_ids = implode( ',', $media_ids );

					// Save all attachment ids in forums post meta
					update_post_meta( $post_id, 'bp_media_ids', $media_ids );

					// save media meta for activity
					if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
						bp_activity_update_meta( $main_activity_id, 'bp_media_ids', $media_ids );
					}
				}

				$forums_done ++;
				update_option( 'bp_media_import_forums_done', $forums_done );
			}
		}
	}
	wp_reset_postdata();
}

/**
 * Import topic media from BuddyBoss Media Plugin
 *
 * @since BuddyBoss 1.0.5
 */
function bp_media_import_buddyboss_topic_media() {

	$topics_done = get_option( 'bp_media_import_topics_done', 0 );

	$topics_media_query = new WP_Query(
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

	if ( ! empty( $topics_media_query->found_posts ) ) {

		$imported_topic_media_ids = get_option( 'bp_media_import_topic_media_ids', array() );

		update_option( 'bp_media_import_topics_total', $topics_media_query->found_posts );

		if ( ! empty( $topics_media_query->posts ) ) {

			$topics_done = (int) $topics_done;
			foreach ( $topics_media_query->posts as $post_id ) {
				$attachment_ids = get_post_meta( $post_id, 'bbm_bbpress_attachment_ids', true );

				// save activity id if it is saved in forums and enabled in platform settings
				$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

				$media_ids = array();
				if ( ! empty( $attachment_ids ) ) {
					foreach ( $attachment_ids as $attachment_id ) {

						$title = get_the_title( $attachment_id );

						$media_id = bp_media_add(
							array(
								'attachment_id' => $attachment_id,
								'title'         => $title,
								'album_id'      => false,
								'group_id'      => false,
								'error_type'    => 'bool',
							)
						);

						if ( $media_id ) {
							$media_ids[] = $media_id;

							// save media is saved in attachment
							update_post_meta( $attachment_id, 'bp_media_saved', true );

							$imported_topic_media_ids[] = $media_id;
						}
					}

					update_option( 'bp_media_import_topic_media_ids', $imported_topic_media_ids );

					$media_ids = implode( ',', $media_ids );

					// Save all attachment ids in forums post meta
					update_post_meta( $post_id, 'bp_media_ids', $media_ids );

					// save media meta for activity
					if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
						bp_activity_update_meta( $main_activity_id, 'bp_media_ids', $media_ids );
					}
				}

				$topics_done ++;
				update_option( 'bp_media_import_topics_done', $topics_done );
			}
		}
	}
	wp_reset_postdata();
}

/**
 * Import reply media from BuddyBoss Media Plugin
 *
 * @since BuddyBoss 1.0.5
 */
function bp_media_import_buddyboss_reply_media() {

	$replies_done = get_option( 'bp_media_import_replies_done', 0 );

	$replies_media_query = new WP_Query(
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

	if ( ! empty( $replies_media_query->found_posts ) ) {

		$imported_reply_media_ids = get_option( 'bp_media_import_reply_media_ids', array() );

		update_option( 'bp_media_import_replies_total', $replies_media_query->found_posts );

		if ( ! empty( $replies_media_query->posts ) ) {

			$replies_done = (int) $replies_done;
			foreach ( $replies_media_query->posts as $post_id ) {
				$attachment_ids = get_post_meta( $post_id, 'bbm_bbpress_attachment_ids', true );

				// save activity id if it is saved in forums and enabled in platform settings
				$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

				$media_ids = array();
				if ( ! empty( $attachment_ids ) ) {
					foreach ( $attachment_ids as $attachment_id ) {

						$title = get_the_title( $attachment_id );

						$media_id = bp_media_add(
							array(
								'attachment_id' => $attachment_id,
								'title'         => $title,
								'album_id'      => false,
								'group_id'      => false,
								'error_type'    => 'bool',
							)
						);

						if ( $media_id ) {
							$media_ids[] = $media_id;

							// save media is saved in attachment
							update_post_meta( $attachment_id, 'bp_media_saved', true );

							$imported_reply_media_ids[] = $media_id;
						}
					}

					update_option( 'bp_media_import_reply_media_ids', $imported_reply_media_ids );

					$media_ids = implode( ',', $media_ids );

					// Save all attachment ids in forums post meta
					update_post_meta( $post_id, 'bp_media_ids', $media_ids );

					// save media meta for activity
					if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
						bp_activity_update_meta( $main_activity_id, 'bp_media_ids', $media_ids );
					}
				}

				$replies_done ++;
				update_option( 'bp_media_import_replies_done', $replies_done );
			}
		}
	}
	wp_reset_postdata();
}

/**
 * Reset all media albums related data in tables
 *
 * @since BuddyBoss 1.0.5
 */
function bp_media_import_reset_media_albums() {
	global $wpdb;
	global $bp;

	$bp_media_table        = $bp->table_prefix . 'bp_media';
	$bp_media_albums_table = $bp->table_prefix . 'bp_media_albums';

	$album_ids = get_option( 'bp_media_import_albums_ids', array() );

	remove_action( 'bp_activity_after_delete', 'bp_media_delete_activity_media' );

	if ( ! empty( $album_ids ) ) {
		foreach ( $album_ids as $new_album_id => $old_album_id ) {

			if ( empty( $new_album_id ) ) {
				continue;
			}

			$album_obj = new BP_Media_Album( $new_album_id );

			if ( ! empty( $album_obj->id ) ) {
				$media_ids = BP_Media::get_album_media_ids( $album_obj->id );
				if ( ! empty( $media_ids ) ) {
					foreach ( $media_ids as $media ) {
						$media_obj = new BP_Media( $media );

						if ( ! empty( $media_obj->activity_id ) && bp_is_active( 'activity' ) ) {
							$activity = new BP_Activity_Activity( (int) $media_obj->activity_id );

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

					$media_ids = implode( ',', $media_ids );
					if ( ! empty( $media_ids ) ) {
						$wpdb->query( "DELETE FROM {$bp_media_table} WHERE id IN ({$media_ids});" );
					}
				}
			}

			$wpdb->query( "DELETE FROM {$bp_media_albums_table} WHERE id = {$album_obj->id};" );
		}
	}

	add_action( 'bp_activity_after_delete', 'bp_media_delete_activity_media' );

	update_option( 'bp_media_import_status', 'reset_media' );
}

/**
 * Reset all media related data in tables
 *
 * @since BuddyBoss 1.0.5
 */
function bp_media_import_reset_media() {
	global $wpdb;
	global $bp;

	$bp_media_table = $bp->table_prefix . 'bp_media';

	$medias = get_option( 'bp_media_import_media_ids', array() );

	remove_action( 'bp_activity_after_delete', 'bp_media_delete_activity_media' );

	if ( ! empty( $medias ) ) {
		$media_ids = array();
		foreach ( $medias as $media ) {

			if ( empty( $media ) ) {
				continue;
			}

			$media_obj = new BP_Media( $media );

			if ( ! empty( $media_obj->activity_id ) && bp_is_active( 'activity' ) ) {
				$activity = new BP_Activity_Activity( (int) $media_obj->activity_id );

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

			if ( ! empty( $media_obj->id ) ) {
				$media_ids[] = $media_obj->id;
			}
		}

		$media_ids = implode( ',', $media_ids );
		if ( ! empty( $media_ids ) ) {
			$wpdb->query( "DELETE FROM {$bp_media_table} WHERE id IN ({$media_ids})" );
		}
	}

	add_action( 'bp_activity_after_delete', 'bp_media_delete_activity_media' );

	update_option( 'bp_media_import_status', 'reset_forum' );
}

/**
 * Reset all media related data in forums
 *
 * @since BuddyBoss 1.0.10
 */
function bp_media_import_reset_forum_media() {
	global $wpdb;
	global $bp;

	$bp_media_table = $bp->table_prefix . 'bp_media';

	$medias = get_option( 'bp_media_import_forum_media_ids', array() );

	if ( ! empty( $medias ) ) {

		$medias = implode( ',', $medias );
		$wpdb->query( "DELETE FROM {$bp_media_table} WHERE id IN ({$medias})" );
	}

	update_option( 'bp_media_import_status', 'reset_topic' );
}

/**
 * Reset all media related data in topics
 *
 * @since BuddyBoss 1.0.10
 */
function bp_media_import_reset_topic_media() {
	global $wpdb;
	global $bp;

	$bp_media_table = $bp->table_prefix . 'bp_media';

	$medias = get_option( 'bp_media_import_topic_media_ids', array() );

	if ( ! empty( $medias ) ) {

		$medias = implode( ',', $medias );
		$wpdb->query( "DELETE FROM {$bp_media_table} WHERE id IN ({$medias})" );
	}

	update_option( 'bp_media_import_status', 'reset_reply' );
}

/**
 * Reset all media related data in topics
 *
 * @since BuddyBoss 1.0.10
 */
function bp_media_import_reset_reply_media() {
	global $wpdb;
	global $bp;

	$bp_media_table = $bp->table_prefix . 'bp_media';

	$medias = get_option( 'bp_media_import_reply_media_ids', array() );

	if ( ! empty( $medias ) ) {

		$medias = implode( ',', $medias );
		$wpdb->query( "DELETE FROM {$bp_media_table} WHERE id IN ({$medias})" );
	}

	update_option( 'bp_media_import_status', 'reset_options' );
}

/**
 * Reset all options related to media import
 *
 * @since BuddyBoss 1.0.5
 */
function bp_media_import_reset_options() {
	update_option( 'bp_media_import_total_media', 0 );
	update_option( 'bp_media_import_total_albums', 0 );
	update_option( 'bp_media_import_albums_done', 0 );
	update_option( 'bp_media_import_media_done', 0 );
	update_option( 'bp_media_import_forums_done', 0 );
	update_option( 'bp_media_import_topics_done', 0 );
	update_option( 'bp_media_import_replies_done', 0 );
	update_option( 'bp_media_import_forums_total', 0 );
	update_option( 'bp_media_import_topics_total', 0 );
	update_option( 'bp_media_import_replies_total', 0 );
	delete_option( 'bp_media_import_reply_media_ids' );
	delete_option( 'bp_media_import_topic_media_ids' );
	delete_option( 'bp_media_import_forum_media_ids' );
	delete_option( 'bp_media_import_media_ids' );
	delete_option( 'bp_media_import_albums_ids' );

	update_option( 'bp_media_import_status', 'start' );
}

/**
 * AJAX function for media import status
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_import_status_request() {

	$import_status = get_option( 'bp_media_import_status' );

	if ( 'reset_albums' == $import_status ) {
		bp_media_import_reset_media_albums();
	} elseif ( 'reset_media' == $import_status ) {
		bp_media_import_reset_media();
	} elseif ( 'reset_forum' == $import_status ) {
		bp_media_import_reset_forum_media();
	} elseif ( 'reset_topic' == $import_status ) {
		bp_media_import_reset_topic_media();
	} elseif ( 'reset_reply' == $import_status ) {
		bp_media_import_reset_reply_media();
	} elseif ( 'reset_options' == $import_status ) {
		bp_media_import_reset_options();
	} elseif ( 'start' == $import_status ) {

		update_option( 'bp_media_import_status', 'importing' );

		bp_media_import_buddyboss_media_tables();
		bp_media_import_buddyboss_forum_media();
		bp_media_import_buddyboss_topic_media();
		bp_media_import_buddyboss_reply_media();
	} else {

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

		$importing = false;
		if ( $albums_done != $total_albums || $media_done != $total_media ) {
			bp_media_import_buddyboss_media_tables();
			$importing = true;
		}

		if ( bp_is_active( 'forums' ) ) {
			if ( $forums_done != $forums_total ) {
				bp_media_import_buddyboss_forum_media();
				$importing = true;
			}

			if ( $topics_done != $topics_total ) {
				bp_media_import_buddyboss_topic_media();
				$importing = true;
			}

			if ( $replies_done != $replies_total ) {
				bp_media_import_buddyboss_reply_media();
				$importing = true;
			}
		}

		if ( ! $importing ) {
			update_option( 'bp_media_import_status', 'done' );
		} else {
			update_option( 'bp_media_import_status', 'importing' );
		}
	}

	$import_status = get_option( 'bp_media_import_status' );
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

	wp_send_json_success(
		array(
			'total_media'   => $total_media,
			'total_albums'  => $total_albums,
			'albums_done'   => $albums_done,
			'media_done'    => $media_done,
			'forums_done'   => $forums_done,
			'topics_done'   => $topics_done,
			'replies_done'  => $replies_done,
			'forums_total'  => $forums_total,
			'topics_total'  => $topics_total,
			'replies_total' => $replies_total,
			'import_status' => $import_status,
			'success_msg'   => __( 'BuddyBoss Media data update is complete! Any previously uploaded member photos should display in their profiles now.', 'buddyboss' ),
			'error_msg'     => __( 'BuddyBoss Media data update is failing!', 'buddyboss' ),
		)
	);
}

/**
 * Function to add the content on top of media listing
 *
 * @since BuddyBoss 1.2.5
 */
function bp_media_directory_page_content() {

	$page_ids = bp_core_get_directory_page_ids();

	if ( ! empty( $page_ids['media'] ) ) {
		$media_page_content = get_post_field( 'post_content', $page_ids['media'] );
		echo apply_filters( 'the_content', $media_page_content );
	}
}

add_action( 'bp_before_directory_media', 'bp_media_directory_page_content' );

/**
 * Get media id for the attachment.
 *
 * @since BuddyBoss 1.3.5
 * @param integer $attachment_id
 *
 * @return array|bool
 */
function bp_get_attachment_media_id( $attachment_id = 0 ) {
	global $bp, $wpdb;

	if ( ! $attachment_id ) {
		return false;
	}

	$attachment_media_id = (int) $wpdb->get_var( "SELECT DISTINCT m.id FROM {$bp->media->table_name} m WHERE m.attachment_id = {$attachment_id}" );

	return $attachment_media_id;
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
function bp_media_query_privacy( $user_id = 0, $group_id = 0, $scope = '' ) {

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
		|| ( !empty( $scope ) && 'groups' === $scope )
	) {
		$privacy = array( 'grouponly' );
	}

	return apply_filters( 'bp_media_query_privacy', $privacy, $user_id, $group_id, $scope );
}

/**
 * Update activity media privacy based on activity.
 *
 * @param int    $activity_id Activity ID.
 * @param string $privacy     Privacy
 *
 * @since BuddyBoss 1.4.0
 */
function bp_media_update_activity_privacy( $activity_id = 0, $privacy = '' ) {
	global $wpdb, $bp;

	if ( empty( $activity_id ) || empty( $privacy ) ) {
		return;
	}

	// Update privacy for the media which are uploaded in activity.
	$media_ids = bp_activity_get_meta( $activity_id, 'bp_media_ids', true );
	if ( ! empty( $media_ids ) ) {
		$media_ids = explode( ',', $media_ids );
		if ( ! empty( $media_ids ) ) {
			foreach ( $media_ids as $id ) {
				$media = new BP_Media( $id );
				if ( ! empty( $media->id ) ) {
					$media->privacy = $privacy;
					$media->save();
				}
			}
		}
	}
}
