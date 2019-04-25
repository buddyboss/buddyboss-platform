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
		'name'  => esc_attr( $name )
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

	//Creating a new array will reset the allowed filetypes
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
		require_once( ABSPATH . 'wp-admin' . '/includes/image.php' );
		require_once( ABSPATH . 'wp-admin' . '/includes/file.php' );
		require_once( ABSPATH . 'wp-admin' . '/includes/media.php' );
	}

	if ( ! function_exists( 'media_handle_upload' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/admin.php' );
	}

	add_image_size( 'bp-media-thumbnail', 400, 400 );
	add_image_size( 'bp-activity-media-thumbnail', 700, 700, true );

	add_filter( 'upload_mimes', 'bp_media_allowed_mimes', 9, 1 );

	$aid = media_handle_upload( $file_id, 0, array(), array(
		'test_form' => false,
		'upload_error_strings' => array(
			false,
			__( 'The uploaded file exceeds ', 'buddyboss' ) . bp_media_file_upload_max_size( true ),
			__( 'The uploaded file exceeds ', 'buddyboss' ) . bp_media_file_upload_max_size( true ),
			__( 'The uploaded file was only partially uploaded.', 'buddyboss' ),
			__( 'No file was uploaded.', 'buddyboss' ),
			'',
			__( 'Missing a temporary folder.', 'buddyboss' ),
			__( 'Failed to write file to disk.', 'buddyboss' ),
			__( 'File upload stopped by extension.', 'buddyboss' )
		)
	) );

	remove_image_size( 'bp-media-thumbnail' );
	remove_image_size( 'bp-activity-media-thumbnail' );

	// if has wp error then throw it.
	if ( is_wp_error( $aid ) ) {
		return $aid;
	}

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
 * @param int $quality
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
function bp_media_file_upload_max_size( $post_string = false ) {
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

	return bp_media_format_size_units( $max_size, $post_string );
}

/**
 * Format file size units
 *
 * @param $bytes
 * @param bool $post_string
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_media_format_size_units( $bytes, $post_string = false ) {
	if ( $bytes >= 1073741824 ) {
		$bytes = number_format( $bytes / 1073741824, 0 ) . ( $post_string ? ' GB' : '' );
	} elseif ( $bytes >= 1048576 ) {
		$bytes = number_format( $bytes / 1048576, 0 ) . ( $post_string ? ' MB' : '' );
	} elseif ( $bytes >= 1024 ) {
		$bytes = number_format( $bytes / 1024, 0 ) . ( $post_string ? ' KB' : '' );
	} elseif ( $bytes > 1 ) {
		$bytes = $bytes . ( $post_string ? ' bytes' : '' );
	} elseif ( $bytes == 1 ) {
		$bytes = $bytes . ( $post_string ? ' byte' : '' );
	} else {
		$bytes = '0'. ( $post_string ? ' bytes' : '' );
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

	$r = bp_parse_args( $args, array(
		'max'               => false,        // Maximum number of results to return.
		'fields'            => 'all',
		'page'              => 1,            // Page 1 without a per_page will result in no pagination.
		'per_page'          => false,        // results per page
		'sort'              => 'DESC',       // sort ASC or DESC
		'order_by'          => false,       // order by

		// want to limit the query.
		'user_id'           => false,
		'activity_id'       => false,
		'album_id'          => false,
		'group_id'          => false,
		'search_terms'      => false,        // Pass search terms as a string
		'privacy'           => false,        // privacy of media
		'exclude'           => false,        // Comma-separated list of activity IDs to exclude.
		'count_total'       => false,
	), 'media_get' );

	$media = BP_Media::get( array(
		'page'              => $r['page'],
		'per_page'          => $r['per_page'],
		'user_id'           => $r['user_id'],
		'activity_id'       => $r['activity_id'],
		'album_id'          => $r['album_id'],
		'group_id'          => $r['group_id'],
		'max'               => $r['max'],
		'sort'              => $r['sort'],
		'order_by'          => $r['order_by'],
		'search_terms'      => $r['search_terms'],
		'privacy'           => $r['privacy'],
		'exclude'           => $r['exclude'],
		'count_total'       => $r['count_total'],
		'fields'            => $r['fields'],
	) );

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

	$r = bp_parse_args( $args, array(
		'media_ids'         => false,      // A single media_id or array of IDs.
		'max'               => false,      // Maximum number of results to return.
		'page'              => 1,          // Page 1 without a per_page will result in no pagination.
		'per_page'          => false,      // Results per page.
		'sort'              => 'DESC',     // Sort ASC or DESC
		'order_by'          => false,     // Sort ASC or DESC
	), 'media_get_specific' );

	$get_args = array(
		'in'                => $r['media_ids'],
		'max'               => $r['max'],
		'page'              => $r['page'],
		'per_page'          => $r['per_page'],
		'sort'              => $r['sort'],
		'order_by'          => $r['order_by'],
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

	$r = bp_parse_args( $args, array(
		'id'            => false,                   // Pass an existing media ID to update an existing entry.
		'blog_id'       => get_current_blog_id(),   // Blog ID
		'attachment_id' => false,                   // attachment id.
		'user_id'       => bp_loggedin_user_id(),   // user_id of the uploader.
		'title'         => '',                      // title of media being added.
		'album_id'      => false,                   // Optional: ID of the album.
		'group_id'      => false,                   // Optional: ID of the group.
		'activity_id'   => false,                   // The ID of activity.
		'privacy'       => 'public',                // Optional: privacy of the media e.g. public.
		'menu_order'    => 0,                       // Optional:  Menu order.
		'date_created'  => bp_core_current_time(),  // The GMT time that this media was recorded
		'error_type'    => 'bool'
	), 'media_add' );

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

	// groups media always have privacy to `grouponly`
	if ( ! empty( $media->group_id ) ) {
		$media->privacy = 'grouponly';
	}

	$save = $media->save();

	if ( 'wp_error' === $r['error_type'] && is_wp_error( $save ) ) {
		return $save;
	} elseif ('bool' === $r['error_type'] && false === $save ) {
		return false;
	}

	/**
	 * Fires at the end of the execution of adding a new media item, before returning the new media item ID.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $r Array of parsed arguments for the media item being added.
	 */
	do_action( 'bp_media_add', $r );

	return $media->id;
}

/**
 * Delete media.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int $media_id ID of media
 *
 * @return bool|int The ID of the media on success. False on error.
 */
function bp_media_delete( $media_id ) {

	$delete = BP_Media::delete( array( 'id' => $media_id ) );

	if ( ! $delete ) {
		return false;
	}

	/**
	 * Fires at the end of the execution of delete media item
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $media_id ID of media
	 */
	do_action( 'bp_media_delete', $media_id );

	return $media_id;
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
    
	$result = bp_activity_get( array(
		'in' => $activity_id
	) );

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
 * Get the media count of a given user.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int $user_id ID of the user whose media are being counted.
 * @return int media count of the user.
 */
function bp_media_get_total_media_count( $user_id = 0 ) {
	if ( empty( $user_id ) )
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();

	$count = BP_Media::total_media_count( $user_id );
	if ( empty( $count ) )
		$count = 0;

	/**
	 * Filters the total media count for a given user.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $count Total media count for a given user.
	 */
	return apply_filters( 'bp_media_get_total_media_count', $count );
}

/**
 * Get the media count of a given group.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int $group_id ID of the user whose media are being counted.
 * @return int media count of the group.
 */
function bp_media_get_total_group_media_count( $group_id = 0 ) {
	if ( empty( $group_id ) && bp_get_current_group_id() ) {
		$group_id = bp_get_current_group_id();
	}

	$count = BP_Media::total_group_media_count( $group_id );
	if ( empty( $count ) )
		$count = 0;

	/**
	 * Filters the total media count for a given group.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $count Total media count for a given group.
	 */
	return apply_filters( 'bp_media_get_total_group_media_count', $count );
}

/**
 * Return the total media count in your BP instance.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return int Media count.
 */
function bp_get_total_media_count() {
	global $wpdb;

	$count = wp_cache_get( 'bp_total_media_count', 'bp' );

	$bp = buddypress();
	if ( false === $count ) {

		$privacy = array( 'public' );
		if ( is_user_logged_in() ) {
			$privacy[] = 'loggedin';
		}
		$privacy = "'" . implode( "', '", $privacy ) . "'";

		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$bp->media->table_name} WHERE privacy IN ({$privacy})" );
		wp_cache_set( 'bp_total_media_count', $count, 'bp' );
	}

	/**
	 * Filters the total number of media.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $count Total number of media.
	 */
	return apply_filters( 'bp_get_total_media_count', $count );
}

//******************** Albums *********************/
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

	$r = bp_parse_args( $args, array(
		'max'      => false,                    // Maximum number of results to return.
		'fields'   => 'all',
		'page'     => 1,                        // Page 1 without a per_page will result in no pagination.
		'per_page' => false,                    // results per page
		'sort'     => 'DESC',                   // sort ASC or DESC

		'search_terms'      => false,           // Pass search terms as a string
		'exclude'           => false,           // Comma-separated list of activity IDs to exclude.
		// want to limit the query.
		'user_id'  => false,
		'group_id' => false,
		'privacy'  => false,                    // privacy of album
		'count_total'       => false,
	), 'album_get' );

	$album = BP_Media_Album::get( array(
		'page'              => $r['page'],
		'per_page'          => $r['per_page'],
		'user_id'           => $r['user_id'],
		'group_id'          => $r['group_id'],
		'privacy'           => $r['privacy'],
		'max'               => $r['max'],
		'sort'              => $r['sort'],
		'search_terms'      => $r['search_terms'],
		'exclude'           => $r['exclude'],
		'count_total'       => $r['count_total'],
		'fields'            => $r['fields'],
	) );

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

	$r = bp_parse_args( $args, array(
		'album_ids'         => false,      // A single album id or array of IDs.
		'max'               => false,      // Maximum number of results to return.
		'page'              => 1,          // Page 1 without a per_page will result in no pagination.
		'per_page'          => false,      // Results per page.
		'sort'              => 'DESC',     // Sort ASC or DESC
		'update_meta_cache' => true,
	), 'media_get_specific' );

	$get_args = array(
		'in'                => $r['album_ids'],
		'max'               => $r['max'],
		'page'              => $r['page'],
		'per_page'          => $r['per_page'],
		'sort'              => $r['sort'],
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

	$r = bp_parse_args( $args, array(
		'id'           => false,                  // Pass an existing album ID to update an existing entry.
		'user_id'      => bp_loggedin_user_id(),                     // User ID
		'group_id'     => false,                  // attachment id.
		'title'        => '',                     // title of album being added.
		'privacy'      => 'public',                  // Optional: privacy of the media e.g. public.
		'date_created' => bp_core_current_time(), // The GMT time that this media was recorded
		'error_type'   => 'bool'
	), 'album_add' );

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
	} elseif ('bool' === $r['error_type'] && false === $save ) {
		return false;
	}

	/**
	 * Fires at the end of the execution of adding a new album item, before returning the new album item ID.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $r Array of parsed arguments for the album item being added.
	 */
	do_action( 'bp_album_add', $r );

	return $album->id;
}

/**
 * Delete album item.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int $album_id ID if album
 *
 * @return bool|int The ID of the album on success. False on error.
 */
function bp_album_delete( $album_id ) {

	$delete = BP_Media_Album::delete( array( 'id' => $album_id ) );

	if ( ! $delete ) {
	    return false;
    }

	/**
	 * Fires at the end of the execution of delete an album item
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $album_id ID of album
	 */
	do_action( 'bp_album_delete', $album_id );

	return $album_id;
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

        if ( is_user_logged_in() && 'friends' == $album->privacy && friends_check_friendship( get_current_user_id(), $album->user_id ) ) {
            return true;
        }

        if ( bp_is_my_profile() && $album->user_id == bp_loggedin_user_domain() && 'onlyme' == $album->privacy ) {
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
            wp_delete_post( $a_id, true );
        }
    }
}


//********************** Forums ***************************//

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

add_action( 'bbp_new_reply', 'bp_media_forums_new_post_media_save', 999 );
add_action( 'bbp_new_topic', 'bp_media_forums_new_post_media_save', 999 );
add_action( 'edit_post',     'bp_media_forums_new_post_media_save', 999 );

add_filter( 'bbp_get_reply_content', 'bp_media_forums_embed_attachments', 999, 2 );
add_filter( 'bbp_get_topic_content', 'bp_media_forums_embed_attachments', 999, 2 );

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