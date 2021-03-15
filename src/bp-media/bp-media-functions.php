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
 * @since   BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Create and upload the media file
 *
 * @return array|null|WP_Error|WP_Post
 * @since BuddyBoss 1.0.0
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
 * @param Array $mime_types carry mime information
 *
 * @return Array
 * @since BuddyBoss 1.0.0
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
 * @return array|int|null|WP_Error|WP_Post
 * @since BuddyBoss 1.0.0
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
 * @param     $source
 * @param     $destination
 * @param int         $quality
 *
 * @return mixed
 * @since BuddyBoss 1.0.0
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
 * @return string
 * @since BuddyBoss 1.0.0
 */
function bp_media_file_upload_max_size() {

	/**
	 * Filters file media upload max limit.
	 *
	 * @param mixed $max_size media upload max limit.
	 *
	 * @since BuddyBoss 1.4.1
	 */
	return apply_filters( 'bp_media_file_upload_max_size', bp_media_allowed_upload_media_size() );
}

/**
 * Format file size units
 *
 * @param      $bytes
 * @param bool  $post_string
 *
 * @return string
 * @since BuddyBoss 1.0.0
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
 * @param array|string $args See BP_Media::get() for description.
 *
 * @return array $media See BP_Media::get() for description.
 * @since BuddyBoss 1.0.0
 *
 * @see   BP_Media::get() For more information on accepted arguments
 *      and the format of the returned value.
 */
function bp_media_get( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'max'              => false,        // Maximum number of results to return.
			'fields'           => 'all',
			'page'             => 1,            // Page 1 without a per_page will result in no pagination.
			'per_page'         => false,        // results per page
			'sort'             => 'DESC',       // sort ASC or DESC
			'order_by'         => false,        // order by

			'scope'            => false,

			// want to limit the query.
			'user_id'          => false,
			'activity_id'      => false,
			'album_id'         => false,
			'group_id'         => false,
			'search_terms'     => false,        // Pass search terms as a string
			'privacy'          => false,        // privacy of media
			'exclude'          => false,        // Comma-separated list of activity IDs to exclude.
			'count_total'      => false,
			'moderation_query' => true,         // Filter for exclude moderation query
		),
		'media_get'
	);

	$media = BP_Media::get(
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
			'count_total'      => $r['count_total'],
			'fields'           => $r['fields'],
			'moderation_query' => $r['moderation_query'],
		)
	);

	/**
	 * Filters the requested media item(s).
	 *
	 * @param BP_Media $media Requested media object.
	 * @param array    $r     Arguments used for the media query.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	return apply_filters_ref_array( 'bp_media_get', array( &$media, &$r ) );
}

/**
 * Fetch specific media items.
 *
 * @param array|string $args {
 *                           All arguments and defaults are shared with BP_Media::get(),
 *                           except for the following:
 *
 * @type string|int|array Single media ID, comma-separated list of IDs,
 *                            or array of IDs.
 * }
 * @return array $activity See BP_Media::get() for description.
 * @since BuddyBoss 1.0.0
 *
 * @see   BP_Media::get() For more information on accepted arguments.
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
			'order_by'  => false,      // Sort ASC or DESC.
			'privacy'   => false,      // privacy to filter.
			'album_id'  => false,      // Album ID.
			'user_id'   => false,      // User ID.
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
		'album_id' => $r['album_id'],
		'user_id'  => $r['user_id'],
	);

	/**
	 * Filters the requested specific media item.
	 *
	 * @param BP_Media $media    Requested media object.
	 * @param array    $args     Original passed in arguments.
	 * @param array    $get_args Constructed arguments used with request.
	 *
	 * @since BuddyBoss
	 */
	return apply_filters( 'bp_media_get_specific', BP_Media::get( $get_args ), $args, $get_args );
}

/**
 * Add an media item.
 *
 * @param array|string $args         {
 *                                   An array of arguments.
 *
 * @type int|bool      $id           Pass an media ID to update an existing item, or
 *                                       false to create a new item. Default: false.
 * @type int|bool      $blog_id      ID of the blog Default: current blog id.
 * @type int|bool      $attchment_id ID of the attachment Default: false
 * @type int|bool      $user_id      Optional. The ID of the user associated with the activity
 *                                       item. May be set to false or 0 if the item is not related
 *                                       to any user. Default: the ID of the currently logged-in user.
 * @type string        $title        Optional. The title of the media item.
 * @type int           $album_id     Optional. The ID of the associated album.
 * @type int           $group_id     Optional. The ID of a associated group.
 * @type int           $activity_id  Optional. The ID of a associated activity.
 * @type string        $privacy      Optional. Privacy of the media Default: public
 * @type int           $menu_order   Optional. Menu order the media Default: false
 * @type string        $date_created Optional. The GMT time, in Y-m-d h:i:s format, when
 *                                       the item was recorded. Defaults to the current time.
 * @type string        $error_type   Optional. Error type. Either 'bool' or 'wp_error'. Default: 'bool'.
 * }
 * @return WP_Error|bool|int The ID of the media on success. False on error.
 * @since BuddyBoss 1.0.0
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

	// groups media always have privacy to `grouponly`.
	if ( ! empty( $media->privacy ) && ( in_array( $media->privacy, array( 'forums', 'message' ), true ) ) ) {
		$media->privacy = $r['privacy'];
	} elseif ( ! empty( $media->group_id ) ) {
		$media->privacy = 'grouponly';
	} elseif ( ! empty( $media->album_id ) ) {
		$album = new BP_Media_Album( $media->album_id );
		if ( ! empty( $album ) ) {
			$media->privacy = $album->privacy;
		}
	}

	if ( isset( $_POST ) && isset( $_POST['action'] ) && 'groups_get_group_members_send_message' === $_POST['action'] ) {
		$media->privacy = 'message';
	}

	// save media.
	$save = $media->save();

	if ( 'wp_error' === $r['error_type'] && is_wp_error( $save ) ) {
		return $save;
	} elseif ( 'bool' === $r['error_type'] && false === $save ) {
		return false;
	}

	// media is saved for attachment.
	update_post_meta( $media->attachment_id, 'bp_media_saved', true );

	/**
	 * Fires at the end of the execution of adding a new media item, before returning the new media item ID.
	 *
	 * @param object $media Media object.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_media_add', $media );

	return $media->id;
}

/**
 * Media add handler function
 *
 * @param array  $medias
 * @param string $privacy
 * @param string $content
 * @param int    $group_id
 * @param int    $album_id
 *
 * @return mixed|void
 * @since BuddyBoss 1.2.0
 */
function bp_media_add_handler( $medias = array(), $privacy = 'public', $content = '', $group_id = false, $album_id = false ) {
	global $bp_media_upload_count, $bp_media_upload_activity_content;
	$media_ids = array();

	$privacy = in_array( $privacy, array_keys( bp_media_get_visibility_levels() ) ) ? $privacy : 'public';

	if ( ! empty( $medias ) && is_array( $medias ) ) {

		// update count of media for later use.
		$bp_media_upload_count = count( $medias );

		// update the content of medias for later use.
		$bp_media_upload_activity_content = $content;

		// save  media.
		foreach ( $medias as $media ) {

			// Update media if existing
			if ( ! empty( $media['media_id'] ) ) {
				$bp_media = new BP_Media( $media['media_id'] );

				if ( ! empty( $bp_media->id ) ) {
					$media_id = bp_media_add(
						array(
							'id'            => $bp_media->id,
							'blog_id'       => $bp_media->blog_id,
							'attachment_id' => $bp_media->attachment_id,
							'user_id'       => $bp_media->user_id,
							'title'         => $bp_media->title,
							'album_id'      => ! empty( $media['album_id'] ) ? $media['album_id'] : $album_id,
							'group_id'      => ! empty( $media['group_id'] ) ? $media['group_id'] : $group_id,
							'activity_id'   => $bp_media->activity_id,
							'privacy'       => $bp_media->privacy,
							'menu_order'    => ! empty( $media['menu_order'] ) ? $media['menu_order'] : false,
							'date_created'  => $bp_media->date_created,
						)
					);
				}
			} else {

				$media_id = bp_media_add(
					array(
						'attachment_id' => $media['id'],
						'title'         => $media['name'],
						'album_id'      => ! empty( $media['album_id'] ) ? $media['album_id'] : $album_id,
						'group_id'      => ! empty( $media['group_id'] ) ? $media['group_id'] : $group_id,
						'menu_order'    => ! empty( $media['menu_order'] ) ? $media['menu_order'] : false,
						'privacy'       => ! empty( $media['privacy'] ) && in_array( $media['privacy'], array_merge( array_keys( bp_media_get_visibility_levels() ), array( 'message' ) ) ) ? $media['privacy'] : $privacy,
					)
				);
			}

			if ( $media_id ) {
				$media_ids[] = $media_id;
			}
		}
	}

	/**
	 * Fires at the end of the execution of adding saving a media item, before returning the new media items in ajax response.
	 *
	 * @param array $media_ids Media IDs.
	 * @param array $medias    Array of media from POST object or in function parameter.
	 *
	 * @since BuddyBoss 1.2.0
	 */
	return apply_filters( 'bp_media_add_handler', $media_ids, (array) $medias );
}

/**
 * Delete media.
 *
 * @param array|string $args To delete specific media items, use
 *                           $args = array( 'id' => $ids ); Otherwise, to use
 *                           filters for item deletion, the argument format is
 *                           the same as BP_Media::get().
 *                           See that method for a description.
 * @param bool         $from Context of deletion from. ex. attachment, activity etc.
 *
 * @return bool|int The ID of the media on success. False on error.
 * @since BuddyBoss 1.2.0
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_delete( $args = '', $from = false ) {

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
	 * Fires before an media item proceeds to be deleted.
	 *
	 * @param array $args Array of arguments to be used with the media deletion.
	 *
	 * @since BuddyBoss 1.2.0
	 */
	do_action( 'bp_before_media_delete', $args );

	$media_ids_deleted = BP_Media::delete( $args, $from );
	if ( empty( $media_ids_deleted ) ) {
		return false;
	}

	/**
	 * Fires after the media item has been deleted.
	 *
	 * @param array $args Array of arguments used with the media deletion.
	 *
	 * @since BuddyBoss 1.2.0
	 */
	do_action( 'bp_media_delete', $args );

	/**
	 * Fires after the media item has been deleted.
	 *
	 * @param array $media_ids_deleted Array of affected media item IDs.
	 *
	 * @since BuddyBoss 1.2.0
	 */
	do_action( 'bp_media_deleted_medias', $media_ids_deleted );

	return true;
}

/**
 * Completely remove a user's media data.
 *
 * @param int $user_id ID of the user whose media is being deleted.
 *
 * @return bool
 * @since BuddyBoss 1.2.0
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
	 * @param int $user_id ID of the user being deleted.
	 *
	 * @since BuddyBoss 1.2.0
	 */
	do_action( 'bp_media_remove_all_user_data', $user_id );
}

add_action( 'wpmu_delete_user', 'bp_media_remove_all_user_data' );
add_action( 'delete_user', 'bp_media_remove_all_user_data' );

/**
 * Get media visibility levels out of the $bp global.
 *
 * @return array
 * @since BuddyBoss 1.2.3
 */
function bp_media_get_visibility_levels() {

	/**
	 * Filters the media visibility levels out of the $bp global.
	 *
	 * @param array $visibility_levels Array of visibility levels.
	 *
	 * @since BuddyBoss 1.2.3
	 */
	return apply_filters( 'bp_media_get_visibility_levels', buddypress()->media->visibility_levels );
}

/**
 * Return the media activity.
 *
 * @param         $activity_id
 *
 * @return object|boolean The media activity object or false.
 * @global object $media_template {@link BP_Media_Template}
 *
 * @since BuddyBoss 1.0.0
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
	 * @param object $activity The media activity.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	return apply_filters( 'bp_media_get_media_activity', $result['activities'][0] );
}

/**
 * Get the media count of a user.
 *
 * @return int media count of the user.
 * @since BuddyBoss 1.0.0
 */
function bp_media_get_total_media_count() {

	add_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_personal_scope', 20 );
	bp_has_media( bp_ajax_querystring( 'media' ) );
	$count = $GLOBALS['media_template']->total_media_count;
	remove_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_personal_scope', 20 );

	/**
	 * Filters the total media count for a given user.
	 *
	 * @param int $count Total media count for a given user.
	 *
	 * @since BuddyBoss 1.0.0
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
 * @param int $group_id ID of the group whose media are being counted.
 *
 * @return int media count of the group.
 * @since BuddyBoss 1.0.0
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
	 * @param int $count Total media count for a given group.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	return apply_filters( 'bp_media_get_total_group_media_count', (int) $count );
}

/**
 * Get the album count of a given group.
 *
 * @param int $group_id ID of the group whose album are being counted.
 *
 * @return int album count of the group.
 * @since BuddyBoss 1.2.0
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
	 * @param int $count Total album count for a given group.
	 *
	 * @since BuddyBoss 1.2.0
	 */
	return apply_filters( 'bp_media_get_total_group_album_count', (int) $count );
}

/**
 * Return the total media count in your BP instance.
 *
 * @return int Media count.
 * @since BuddyBoss 1.0.0
 */
function bp_get_total_media_count() {

	add_filter( 'bp_ajax_querystring', 'bp_media_object_results_media_all_scope', 20 );
	bp_has_media( bp_ajax_querystring( 'media' ) );
	remove_filter( 'bp_ajax_querystring', 'bp_media_object_results_media_all_scope', 20 );
	$count = $GLOBALS['media_template']->total_media_count;

	/**
	 * Filters the total number of media.
	 *
	 * @param int $count Total number of media.
	 *
	 * @since BuddyBoss 1.0.0
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

	$querystring['scope'] = 'all';

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
	$querystring['user_id']     = false;
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
 * @param array|string $args See BP_Media_Album::get() for description.
 *
 * @return array $activity See BP_Media_Album::get() for description.
 * @since BuddyBoss 1.0.0
 *
 * @see   BP_Media_Album::get() For more information on accepted arguments
 *      and the format of the returned value.
 */
function bp_album_get( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'max'              => false,            // Maximum number of results to return.
			'fields'           => 'all',
			'page'             => 1,                // Page 1 without a per_page will result in no pagination.
			'per_page'         => false,            // results per page.
			'sort'             => 'DESC',           // sort ASC or DESC.
			'search_terms'     => false,            // Pass search terms as a string.
			'exclude'          => false,            // Comma-separated list of activity IDs to exclude.
			// want to limit the query.
			'user_id'          => false,
			'group_id'         => false,
			'privacy'          => false,            // privacy of album.
			'count_total'      => false,
			'moderation_query' => true,             // Filter for exclude moderation query.
		),
		'album_get'
	);

	$album = BP_Media_Album::get(
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
	 * @param BP_Media $album Requested media object.
	 * @param array    $r     Arguments used for the album query.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	return apply_filters_ref_array( 'bp_album_get', array( &$album, &$r ) );
}

/**
 * Fetch specific albums.
 *
 * @param array|string $args {
 *                           All arguments and defaults are shared with BP_Media_Album::get(),
 *                           except for the following:
 *
 * @type string|int|array Single album ID, comma-separated list of IDs,
 *                            or array of IDs.
 * }
 * @return array $albums See BP_Media_Album::get() for description.
 * @since BuddyBoss 1.0.0
 *
 * @see   BP_Media_Album::get() For more information on accepted arguments.
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
	 * @param BP_Media $album    Requested media object.
	 * @param array    $args     Original passed in arguments.
	 * @param array    $get_args Constructed arguments used with request.
	 *
	 * @since BuddyBoss
	 */
	return apply_filters( 'bp_album_get_specific', BP_Media_Album::get( $get_args ), $args, $get_args );
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
 * @since BuddyBoss 1.0.0
 */
function bp_album_add( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'id'           => false,                  // Pass an existing album ID to update an existing entry.
			'user_id'      => bp_loggedin_user_id(),  // User ID.
			'group_id'     => false,                  // attachment id.
			'title'        => '',                     // title of album being added.
			'privacy'      => 'public',               // Optional: privacy of the media e.g. public.
			'date_created' => bp_core_current_time(), // The GMT time that this media was recorded.
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
	 * @param object $album Album object.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_album_add', $album );

	return $album->id;
}

/**
 * Delete album item.
 *
 * @param array|string $args To delete specific album items, use
 *                           $args = array( 'id' => $ids ); Otherwise, to use
 *                           filters for item deletion, the argument format is
 *                           the same as BP_Media_Album::get().
 *                           See that method for a description.
 *
 * @return bool True on Success. False on error.
 * @since BuddyBoss 1.0.0
 */
function bp_album_delete( $args ) {

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
	 * @since BuddyBoss 1.2.0
	 */
	do_action( 'bp_before_album_delete', $args );

	$album_ids_deleted = BP_Media_Album::delete( $args );
	if ( empty( $album_ids_deleted ) ) {
		return false;
	}

	/**
	 * Fires after the album item has been deleted.
	 *
	 * @param array $args Array of arguments used with the album deletion.
	 *
	 * @since BuddyBoss 1.2.0
	 */
	do_action( 'bp_album_delete', $args );

	/**
	 * Fires after the album item has been deleted.
	 *
	 * @param array $album_ids_deleted Array of affected album item IDs.
	 *
	 * @since BuddyBoss 1.2.0
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
 * @param int $album_id ID of the album.
 *
 * @return BP_Media_Album $album The album object.
 * @since BuddyBoss 1.0.0
 */
function albums_get_album( $album_id ) {

	$album = new BP_Media_Album( $album_id );

	/**
	 * Filters a single album object.
	 *
	 * @param BP_Media_Album $album Single album object.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	return apply_filters( 'albums_get_album', $album );
}

/**
 * Check album access for current user or guest
 *
 * @param $album_id
 *
 * @return bool
 * @since BuddyBoss 1.0.0
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
		foreach ( $orphaned_attachment_query->posts as $a_id ) {
			wp_delete_attachment( $a_id, true );
		}
	}
}

/**
 * Download an image from the specified URL and attach it to a post.
 *
 * @param string $file The URL of the image to download
 *
 * @return int|void
 * @since BuddyBoss 1.0.0
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
 * @param array $file_array Array similar to a {@link $_FILES} upload array
 * @param array $post_data  allows you to overwrite some of the attachment
 *
 * @return int|object The ID of the attachment or a WP_Error on failure
 * @since BuddyBoss 1.0.0
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
							if ( 'activity_comment' === $activity->type ) {
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
				if ( 'activity_comment' === $activity->type ) {
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
 * @param integer $attachment_id
 *
 * @return array|bool
 * @since BuddyBoss 1.3.5
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
		|| ( ! empty( $scope ) && 'groups' === $scope )
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

/**
 * Get default scope for the media.
 *
 * @param string $scope Default scope.
 *
 * @return string
 * @since BuddyBoss 1.4.4
 */
function bp_media_default_scope( $scope ) {

	$new_scope = array();

	$allowed_scopes = array( 'public' );
	if ( is_user_logged_in() && bp_is_active( 'friends' ) && bp_is_profile_media_support_enabled() ) {
		$allowed_scopes[] = 'friends';
	}

	if ( bp_is_active( 'groups' ) && bp_is_group_media_support_enabled() ) {
		$allowed_scopes[] = 'groups';
	}

	if ( ( is_user_logged_in() || bp_is_user() ) && bp_is_profile_media_support_enabled() ) {
		$allowed_scopes[] = 'personal';
	}

	if ( ( 'all' === $scope || empty( $scope ) ) && bp_is_media_directory() ) {
		$new_scope[] = 'public';

		if ( bp_is_active( 'friends' ) && bp_is_profile_media_support_enabled() ) {
			$new_scope[] = 'friends';
		}

		if ( bp_is_active( 'groups' ) && bp_is_group_media_support_enabled() ) {
			$new_scope[] = 'groups';
		}

		if ( is_user_logged_in() && bp_is_profile_media_support_enabled() ) {
			$new_scope[] = 'personal';
		}
	} elseif ( bp_is_user_media() && ( 'all' === $scope || empty( $scope ) ) && bp_is_profile_media_support_enabled() ) {
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
	$new_scope = apply_filters( 'bp_media_default_scope', $new_scope );

	return implode( ',', $new_scope );

}

/**
 * Check user have a permission to manage the media.
 *
 * @param int $media_id
 * @param int $user_id
 * @param int $thread_id
 * @param int $message_id
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.4
 */
function bp_media_user_can_manage_media( $media_id = 0, $user_id = 0 ) {

	$can_manage   = false;
	$can_view     = false;
	$can_download = false;
	$can_add      = false;
	$media        = new BP_Media( $media_id );
	$data         = array();

	switch ( $media->privacy ) {

		case 'public':
			if ( $media->user_id === $user_id ) {
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

				$manage   = groups_can_user_manage_media( $user_id, $media->group_id );
				$status   = bp_group_get_media_status( $media->group_id );
				$is_admin = groups_is_user_admin( $user_id, $media->group_id );
				$is_mod   = groups_is_user_mod( $user_id, $media->group_id );

				if ( $manage ) {
					if ( $media->user_id === $user_id ) {
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
					$the_group = groups_get_group( (int) $media->group_id );
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
			} elseif ( $media->user_id === $user_id ) {
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
			$is_friend = ( bp_is_active( 'friends' ) ) ? friends_check_friendship( $media->user_id, $user_id ) : false;
			if ( $media->user_id === $user_id ) {
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
				'forum_id'        => bp_media_get_forum_id( $media_id ),
				'check_ancestors' => false,
			);

			$has_access = bbp_user_can_view_forum( $args );
			if ( $media->user_id === $user_id ) {
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
			$thread_id  = bp_media_get_thread_id( $media_id );
			$has_access = messages_check_thread_access( $thread_id, $user_id );
			if ( ! is_user_logged_in() ) {
				$can_manage   = false;
				$can_view     = false;
				$can_download = false;
			} elseif ( ! $thread_id ) {
				$can_manage   = false;
				$can_view     = false;
				$can_download = false;
			} elseif ( $media->user_id === $user_id ) {
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
			if ( $media->user_id === $user_id ) {
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

	return apply_filters( 'bp_media_user_can_manage_media', $data, $media_id, $user_id );
}

function bp_media_get_thread_id( $media_id ) {

	$thread_id = 0;

	if ( bp_is_active( 'messages' ) ) {
		$meta = array(
			array(
				'key'     => 'bp_media_ids',
				'value'   => $media_id,
				'compare' => 'LIKE',
			),
		);

		// Check if there is already previously individual group thread created.
		if( bp_has_message_threads( array( 'meta_query' => $meta ) ) ) { // phpcs:ignore
			while ( bp_message_threads() ) {
				bp_message_thread();
				$thread_id = bp_get_message_thread_id();
				if ( $thread_id ) {
					break;
				}
			}
		}
	}

	return apply_filters( 'bp_media_get_thread_id', $thread_id, $media_id );

}

/**
 * Return download link of the media.
 *
 * @param $attachment_id
 * @param $media_id
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.4
 */
function bp_media_download_link( $attachment_id, $media_id ) {

	if ( empty( $attachment_id ) ) {
		return;
	}

	$link = site_url() . '/?attachment_id=' . $attachment_id . '&media_type=media&download_media_file=1' . '&media_file=' . $media_id;

	return apply_filters( 'bp_media_download_link', $link, $attachment_id );

}

/**
 * Check if user have a access to download the file. If not redirect to homepage.
 *
 * @since BuddyBoss 1.4.4
 */
function bp_media_download_url_file() {
	if ( isset( $_GET['attachment_id'] ) && isset( $_GET['download_media_file'] ) && isset( $_GET['media_file'] ) && isset( $_GET['media_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'folder' !== $_GET['media_type'] ) {
			$media_privacy = bp_media_user_can_manage_media( $_GET['media_file'], bp_loggedin_user_id() ); // phpcs:ignore WordPress.Security.NonceVerification

			$can_download_btn = ( true === (bool) $media_privacy['can_download'] ) ? true : false;
		}
		if ( $can_download_btn ) {
			bp_media_download_file( $_GET['attachment_id'], $_GET['media_type'] ); // phpcs:ignore WordPress.Security.NonceVerification
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
function bp_media_ie_nocache_headers_fix( $headers ) {
	if ( is_ssl() && ! empty( $GLOBALS['is_IE'] ) ) {
		$headers['Cache-Control'] = 'private';
		unset( $headers['Pragma'] );
	}

	return $headers;
}

function bp_media_get_forum_id( $media_id ) {

	$forum_id           = 0;
	$forums_media_query = new WP_Query(
		array(
			'post_type'      => bbp_get_forum_post_type(),
			'fields'         => 'ids',
			'posts_per_page' => - 1,
			'meta_query'     => array(
				array(
					'key'     => 'bp_media_ids',
					'value'   => $media_id,
					'compare' => 'LIKE',
				),
			),
		)
	);

	if ( ! empty( $forums_media_query->found_posts ) && ! empty( $forums_media_query->posts ) ) {

		foreach ( $forums_media_query->posts as $post_id ) {
			$media_ids = get_post_meta( $post_id, 'bp_media_ids', true );

			if ( ! empty( $media_ids ) ) {
				$media_ids = explode( ',', $media_ids );
				if ( in_array( $media_id, $media_ids ) ) {
					$forum_id = $post_id;
					break;
				}
			}
		}
	}
	wp_reset_postdata();

	if ( ! $forum_id ) {
		$topics_media_query = new WP_Query(
			array(
				'post_type'      => bbp_get_topic_post_type(),
				'fields'         => 'ids',
				'posts_per_page' => - 1,
				'meta_query'     => array(
					array(
						'key'     => 'bp_media_ids',
						'value'   => $media_id,
						'compare' => 'LIKE',
					),
				),
			)
		);

		if ( ! empty( $topics_media_query->found_posts ) && ! empty( $topics_media_query->posts ) ) {

			foreach ( $topics_media_query->posts as $post_id ) {
				$media_ids = get_post_meta( $post_id, 'bp_media_ids', true );

				if ( ! empty( $media_ids ) ) {
					$media_ids = explode( ',', $media_ids );
					if ( in_array( $media_id, $media_ids ) ) {
						$forum_id = bbp_get_topic_forum_id( $post_id );
						break;
					}
				}
			}
		}
		wp_reset_postdata();
	}

	if ( ! $forum_id ) {
		$reply_media_query = new WP_Query(
			array(
				'post_type'      => bbp_get_reply_post_type(),
				'fields'         => 'ids',
				'posts_per_page' => - 1,
				'meta_query'     => array(
					array(
						'key'     => 'bp_media_ids',
						'value'   => $media_id,
						'compare' => 'LIKE',
					),
				),
			)
		);

		if ( ! empty( $reply_media_query->found_posts ) && ! empty( $reply_media_query->posts ) ) {

			foreach ( $reply_media_query->posts as $post_id ) {
				$media_ids = get_post_meta( $post_id, 'bp_media_ids', true );

				if ( ! empty( $media_ids ) ) {
					$media_ids = explode( ',', $media_ids );
					foreach ( $media_ids as $media_id ) {
						if ( in_array( $media_id, $media_ids ) ) {
							$forum_id = bbp_get_reply_forum_id( $post_id );
							break;
						}
					}
				}
			}
		}
		wp_reset_postdata();
	}

	return apply_filters( 'bp_media_get_forum_id', $forum_id, $media_id );

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
function bp_media_user_can_manage_album( $album_id = 0, $user_id = 0 ) {

	$can_manage   = false;
	$can_view     = false;
	$can_download = false;
	$can_add      = false;
	$album        = new BP_Media_Album( $album_id );
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

				$manage   = groups_can_user_manage_media( $user_id, $album->group_id );
				$status   = bp_group_get_media_status( $album->group_id );
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

	return apply_filters( 'bp_media_user_can_manage_album', $data, $album_id, $user_id );
}


/**
 * Return the breadcrumbs.
 *
 * @param int $user_id
 * @param int $group_id
 *
 * @return string
 * @since BuddyBoss 1.5.6
 */
function bp_media_user_media_album_tree_view_li_html( $user_id = 0, $group_id = 0 ) {

	global $wpdb, $bp;

	$media_album_table = $bp->media->table_name_albums;

	if ( 0 === $group_id ) {
		$group_id = ( function_exists( 'bp_get_current_group_id' ) ) ? bp_get_current_group_id() : 0;
	}

	$media_album_query = $wpdb->prepare( "SELECT * FROM {$media_album_table} WHERE user_id = %d AND group_id = %d ORDER BY id DESC", $user_id, $group_id );

	// db call ok; no-cache ok;
	$data = $wpdb->get_results( $media_album_query, ARRAY_A );

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
			$items_by_reference[ $item['parent'] ]['children'][] = &$item;
		}
	}

	// Remove items that were added to parents elsewhere.
	foreach ( $data as $key => &$item ) {
		if ( isset( $item['parent'] ) && $item['parent'] && isset( $items_by_reference[ $item['parent'] ] ) ) {
			unset( $data[ $key ] );
		}
	}

	return bp_media_album_recursive_li_list( $data, false );

}

/**
 * This function will give the breadcrumbs ul li html.
 *
 * @param      $array
 * @param bool  $first
 *
 * @return string
 * @since BuddyBoss 1.5.6
 */
function bp_media_album_recursive_li_list( $array, $first = false ) {

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
		$output .= '<li data-id="' . esc_attr( $item['id'] ) . '" data-privacy="' . esc_attr( $item['privacy'] ) . '"><span id="' . esc_attr( $item['id'] ) . '" data-id="' . esc_attr( $item['id'] ) . '">' . stripslashes( $item['title'] ) . '</span>' . bp_media_album_recursive_li_list( $item['children'], true ) . '</li>';
	}
	$output .= '</ul>';

	return $output;
}

/**
 * This function will media into the album.
 *
 * @param int $media_id media id.
 * @param int $album_id album id.
 * @param int $group_id group id.
 *
 * @return bool|int
 * @since BuddyBoss 1.5.6
 */
function bp_media_move_media_to_album( $media_id = 0, $album_id = 0, $group_id = 0 ) {

	global $wpdb, $bp;

	if ( 0 === $media_id ) {
		return false;
	}

	if ( (int) $media_id > 0 ) {
		$has_access = bp_media_user_can_edit( $media_id );
		if ( ! $has_access ) {
			return false;
		}
	}

	if ( (int) $album_id > 0 ) {
		$has_access = bp_album_user_can_edit( $album_id );
		if ( ! $has_access ) {
			return false;
		}
	}

	if ( ! $group_id ) {
		$get_media = new BP_Media( $media_id );
		if ( $get_media->group_id > 0 ) {
			$group_id = $get_media->group_id;
		}
	}

	if ( $group_id > 0 ) {
		$destination_privacy = 'public';
	} elseif ( $album_id > 0 ) {
		$destination_album = BP_Media_Album::get_album_data( array( $album_id ) );
		$destination_album = ( ! empty( $destination_album ) ) ? current( $destination_album ) : array();
		if ( empty( $destination_album ) ) {
			return false;
		}
		$destination_privacy = $destination_album->privacy;
		// Update modify date for destination album.
		$destination_album_update               = new BP_Media_Album( $album_id );
		$destination_album_update->date_created = bp_core_current_time();
		$destination_album_update->save();
	} else {
		// Keep the destination privacy same as the previous privacy.
		$media_object        = new BP_Media( $media_id );
		$destination_privacy = $media_object->privacy;
	}

	if ( empty( $destination_privacy ) ) {
		$destination_privacy = 'loggedin';
	}

	$media               = new BP_Media( $media_id );
	$media->album_id     = $album_id;
	$media->group_id     = $group_id;
	$media->date_created = bp_core_current_time();
	$media->privacy      = ( $group_id > 0 ) ? 'grouponly' : $destination_privacy;
	$media->menu_order   = 0;
	$media->save();

	// Update media activity privacy.
	if ( ! empty( $media ) && ! empty( $media->attachment_id ) ) {

		$media_attachment   = $media->attachment_id;
		$parent_activity_id = get_post_meta( $media_attachment, 'bp_media_parent_activity_id', true );

		// If found need to make this activity to main activity.
		$child_activity_id = get_post_meta( $media_attachment, 'bp_media_activity_id', true );

		if ( bp_is_active( 'activity' ) ) {

			// Single media upload.
			if ( empty( $child_activity_id ) ) {
				$activity = new BP_Activity_Activity( (int) $parent_activity_id );
				// Update activity data.
				if ( bp_activity_user_can_delete( $activity ) ) {
					// Make the activity media own.
					$activity->hide_sitewide     = 0;
					$activity->secondary_item_id = 0;
					$activity->privacy           = $destination_privacy;
					$activity->save();
				}

				// Delete the meta if uploaded to root so no need bp_media_album_activity meta.
				bp_activity_delete_meta( (int) $parent_activity_id, 'bp_media_album_activity' );

				if ( $album_id > 0 ) {
					// Update to moved album id.
					bp_activity_update_meta( (int) $parent_activity_id, 'bp_media_album_activity', (int) $album_id );
				}

				// We have to change child activity privacy when we move the media while at a time multiple media uploaded.
			} else {

				$parent_activity_media_ids = bp_activity_get_meta( $parent_activity_id, 'bp_media_ids', true );

				// Get the parent activity.
				$parent_activity = new BP_Activity_Activity( (int) $parent_activity_id );

				if ( bp_activity_user_can_delete( $parent_activity ) && ! empty( $parent_activity_media_ids ) ) {
					$parent_activity_media_ids = explode( ',', $parent_activity_media_ids );

					// Do the changes if only one media is attached to a activity.
					if ( 1 === count( $parent_activity_media_ids ) ) {

						// Get the media object.
						$media = new BP_Media( $media_id );

						// Need to delete child activity.
						$need_delete = $media->activity_id;

						$media_album = (int) $media->album_id;

						// Update media activity id to parent activity id.
						$media->activity_id  = $parent_activity_id;
						$media->date_created = bp_core_current_time();
						$media->save();

						bp_activity_update_meta( $parent_activity_id, 'bp_media_ids', $media_id );

						// Update attachment meta.
						delete_post_meta( $media->attachment_id, 'bp_media_activity_id' );
						update_post_meta( $media->attachment_id, 'bp_media_parent_activity_id', $parent_activity_id );
						update_post_meta( $media->attachment_id, 'bp_media_upload', 1 );
						update_post_meta( $media->attachment_id, 'bp_media_saved', 1 );

						bp_activity_delete_meta( $parent_activity_id, 'bp_media_album_activity' );
						if ( $media_album > 0 ) {
							bp_activity_update_meta( $parent_activity_id, 'bp_media_album_activity', $media_album );
						}

						// Update the activity meta first otherwise it will delete the media.
						bp_activity_update_meta( $need_delete, 'bp_media_ids', '' );

						// Delete child activity no need anymore because assigned all the data to parent activity.
						bp_activity_delete( array( 'id' => $need_delete ) );

						// Update parent activity privacy to destination privacy.
						$parent_activity->privacy = $destination_privacy;
						$parent_activity->save();

					} elseif ( count( $parent_activity_media_ids ) > 1 ) {

						// Get the child activity.
						$activity = new BP_Activity_Activity( (int) $child_activity_id );

						// Update activity data.
						if ( bp_activity_user_can_delete( $activity ) ) {

							// Make the activity media own.
							$activity->hide_sitewide     = 0;
							$activity->secondary_item_id = 0;
							$activity->privacy           = $destination_privacy;
							$activity->save();

							bp_activity_update_meta( (int) $child_activity_id, 'bp_media_ids', $media_id );

							// Update attachment meta.
							delete_post_meta( $media_attachment, 'bp_media_activity_id' );
							update_post_meta( $media_attachment, 'bp_media_parent_activity_id', $child_activity_id );
							update_post_meta( $media_attachment, 'bp_media_upload', 1 );
							update_post_meta( $media_attachment, 'bp_media_saved', 1 );

							// Make the child activity as parent activity.
							bp_activity_delete_meta( $child_activity_id, 'bp_media_activity' );

							bp_activity_delete_meta( (int) $child_activity_id, 'bp_media_album_activity' );
							if ( $album_id > 0 ) {
								bp_activity_update_meta( (int) $child_activity_id, 'bp_media_album_activity', (int) $album_id );
							}

							// Remove the media id from the parent activity meta.
							$key = array_search( $media_id, $parent_activity_media_ids );
							if ( false !== $key ) {
								unset( $parent_activity_media_ids[ $key ] );
							}

							// Update the activity meta.
							if ( ! empty( $parent_activity_media_ids ) ) {
								$activity_media_ids = implode( ',', $parent_activity_media_ids );
								bp_activity_update_meta( $parent_activity_id, 'bp_media_ids', $activity_media_ids );
							} else {
								bp_activity_update_meta( $parent_activity_id, 'bp_media_ids', '' );
							}
						}
					}
				}
			}
		}
	}

	return $media_id;
}

/**
 * Get the activity media.
 *
 * @param int $activity_id activity id.
 *
 * @return array|void
 * @since BuddyBoss 1.5.6
 */
function bp_media_get_activity_media( $activity_id ) {

	$media_content      = '';
	$media_activity_ids = '';
	$response           = array();
	if ( bp_is_active( 'activity' ) && ! empty( $activity_id ) ) {

		$media_activity_ids = bp_activity_get_meta( $activity_id, 'bp_media_ids', true );

		global $media_template;
		// Add Media to single activity page..
		$media_activity = bp_activity_get_meta( $activity_id, 'bp_media_activity', true );
		if ( bp_is_single_activity() && ! empty( $media_activity ) && '1' === $media_activity && empty( $media_activity_ids ) ) {
			$media_ids = BP_Media::get_activity_media_id( $activity_id );
		} else {
			$media_ids = bp_activity_get_meta( $activity_id, 'bp_media_ids', true );
		}

		if ( empty( $media_ids ) ) {
			return;
		}

		$args = array(
			'include'  => $media_ids,
			'order_by' => 'menu_order',
			'sort'     => 'ASC',
			'user_id'  => false,
		);

		$activity = new BP_Activity_Activity( (int) $activity_id );
		if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $activity->component ) {
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
			$args['privacy'] = bp_media_query_privacy( $activity->user_id, 0, $activity->component );
			if ( ! bp_is_profile_media_support_enabled() ) {
				$args['user_id'] = 'null';
			}
			if ( ! bp_is_profile_albums_support_enabled() ) {
				$args['album_id'] = 'existing-media';
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
		) && bp_is_forums_media_support_enabled() ) {
			$is_forum_activity = true;
			$args['privacy'][] = 'forums';
		}

		if ( bp_has_media( $args ) ) {

			ob_start();
			?>
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
			$media_content = ob_get_contents();
			ob_end_clean();
		}
	}

	$response['content']            = $media_content;
	$response['media_activity_ids'] = $media_activity_ids;

	return $response;
}

/**
 * Check given photo is activity comment photo.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param $photo
 *
 * @return bool
 */
function bp_media_is_activity_comment_photo( $photo ) {

	$is_comment_photo = false;
	if ( is_object( $photo ) ) {
		$photo_activity_id = $photo->activity_id;
	} else {
		$photo             = new BP_Media( $photo );
		$photo_activity_id = $photo->activity_id;
	}

	if ( bp_is_active( 'activity' ) ) {
		$activity = new BP_Activity_Activity( $photo_activity_id );

		if ( $activity ) {
			if ( $activity->secondary_item_id ) {
				$load_parent_activity = new BP_Activity_Activity( $activity->secondary_item_id );
				if ( $load_parent_activity ) {
					if ( 'activity_comment' === $load_parent_activity->type ) {
						$is_comment_photo = true;
					}
				}
			}
		}
	} elseif ( $photo_activity_id ) {
		$is_comment_photo = true;
	}
	return $is_comment_photo;

}

/**
 * Function to get media report link
 *
 * @since BuddyBoss 1.5.6
 *
 * @param array $args button arguments.
 *
 * @return mixed|void
 */
function bp_media_get_report_link( $args = array() ) {

	if ( ! bp_is_active( 'moderation' ) || ! is_user_logged_in() ) {
		return false;
	}

	$report_btn = bp_moderation_get_report_button(
		array(
			'id'                => 'media_report',
			'component'         => 'moderation',
			'must_be_logged_in' => true,
			'button_attr'       => array(
				'data-bp-content-id'   => ! empty( $args['id'] ) ? $args['id'] : 0,
				'data-bp-content-type' => BP_Moderation_Media::$moderation_type,
			),
		),
		true
	);

	return apply_filters( 'bp_media_get_report_link', $report_btn, $args );
}

/**
 * Whether user can show the media upload button.
 *
 * @param int $user_id  given user id.
 * @param int $group_id given group id.
 *
 * @since BuddyBoss 1.5.7
 *
 * @return bool
 */
function bb_media_user_can_upload( $user_id = 0, $group_id = 0 ) {

	if ( ( empty( $user_id ) && empty( $group_id ) ) || empty( $user_id ) ) {
		return false;
	}

	if ( ! empty( $group_id ) && bp_is_group_media_support_enabled() ) {
		return groups_can_user_manage_media( $user_id, $group_id );
	}

	if ( bp_is_profile_media_support_enabled() && bb_user_can_create_media() ) {
		return true;
	}

	return false;
}
