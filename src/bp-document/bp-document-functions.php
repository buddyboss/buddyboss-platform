<?php
/**
 * BuddyBoss Document Functions.
 * Functions are where all the magic happens in BuddyBoss. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 *
 * @package BuddyBoss\Document\Functions
 * @since   BuddyBoss 1.3.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Meta *********************************************************************/

/**
 * Delete a meta entry from the DB for an document feed item.
 *
 * @since BuddyBoss 1.3.6
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int    $document_id ID of the document item whose metadata is being deleted.
 * @param string $meta_key    Optional. The key of the metadata being deleted. If
 *                            omitted, all metadata associated with the document
 *                            item will be deleted.
 * @param string $meta_value  Optional. If present, the metadata will only be
 *                            deleted if the meta_value matches this parameter.
 * @param bool   $delete_all  Optional. If true, delete matching metadata entries
 *                            for all objects, ignoring the specified object_id. Otherwise,
 *                            only delete matching metadata entries for the specified
 *                            document item. Default: false.
 * @return bool True on success, false on failure.
 */
function bp_document_delete_meta( $document_id, $meta_key = '', $meta_value = '', $delete_all = false ) {

	// Legacy - if no meta_key is passed, delete all for the item.
	if ( empty( $meta_key ) ) {
		$all_meta = bp_document_get_meta( $document_id );
		$keys     = ! empty( $all_meta ) ? array_keys( $all_meta ) : array();

		// With no meta_key, ignore $delete_all.
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	$retval = true;

	add_filter( 'query', 'bp_filter_metaid_column_name' );
	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'document', $document_id, $key, $meta_value, $delete_all );
	}
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get metadata for a given document item.
 *
 * @since BuddyBoss 1.3.6
 *
 * @param int    $document_id ID of the document item whose metadata is being requested.
 * @param string $meta_key    Optional. If present, only the metadata matching
 *                            that meta key will be returned. Otherwise, all metadata for the
 *                            document item will be fetched.
 * @param bool   $single      Optional. If true, return only the first value of the
 *                            specified meta_key. This parameter has no effect if meta_key is not
 *                            specified. Default: true.
 * @return mixed The meta value(s) being requested.
 */
function bp_document_get_meta( $document_id = 0, $meta_key = '', $single = true ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = get_metadata( 'document', $document_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	/**
	 * Filters the metadata for a specified document item.
	 *
	 * @since BuddyBoss 1.3.6
	 *
	 * @param mixed  $retval      The meta values for the document item.
	 * @param int    $document_id ID of the document item.
	 * @param string $meta_key    Meta key for the value being requested.
	 * @param bool   $single      Whether to return one matched meta key row or all.
	 */
	return apply_filters( 'bp_document_get_meta', $retval, $document_id, $meta_key, $single );
}

/**
 * Update a piece of document meta.
 *
 * @since BuddyBoss 1.3.6
 *
 * @param int    $document_id ID of the document item whose metadata is being updated.
 * @param string $meta_key    Key of the metadata being updated.
 * @param mixed  $meta_value  Value to be set.
 * @param mixed  $prev_value  Optional. If specified, only update existing metadata entries
 *                            with the specified value. Otherwise, update all entries.
 * @return bool|int Returns false on failure. On successful update of existing
 *                  metadata, returns true. On successful creation of new metadata,
 *                  returns the integer ID of the new metadata row.
 */
function bp_document_update_meta( $document_id, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = update_metadata( 'document', $document_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of document metadata.
 *
 * @since BuddyBoss 1.3.6
 *
 * @param int    $document_id ID of the document item.
 * @param string $meta_key    Metadata key.
 * @param mixed  $meta_value  Metadata value.
 * @param bool   $unique      Optional. Whether to enforce a single metadata value for the
 *                            given key. If true, and the object already has a value for
 *                            the key, no change will be made. Default: false.
 * @return int|bool The meta ID on successful update, false on failure.
 */
function bp_document_add_meta( $document_id, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = add_metadata( 'document', $document_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Delete a meta entry from the DB for an document folder feed item.
 *
 * @since BuddyBoss 1.3.6
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int    $folder_id ID of the document folder item whose metadata is being deleted.
 * @param string $meta_key    Optional. The key of the metadata being deleted. If
 *                            omitted, all metadata associated with the document
 *                            item will be deleted.
 * @param string $meta_value  Optional. If present, the metadata will only be
 *                            deleted if the meta_value matches this parameter.
 * @param bool   $delete_all  Optional. If true, delete matching metadata entries
 *                            for all objects, ignoring the specified object_id. Otherwise,
 *                            only delete matching metadata entries for the specified
 *                            document folder item. Default: false.
 * @return bool True on success, false on failure.
 */
function bp_document_folder_delete_meta( $folder_id, $meta_key = '', $meta_value = '', $delete_all = false ) {

	// Legacy - if no meta_key is passed, delete all for the item.
	if ( empty( $meta_key ) ) {
		$all_meta = bp_document_folder_get_meta( $folder_id );
		$keys     = ! empty( $all_meta ) ? array_keys( $all_meta ) : array();

		// With no meta_key, ignore $delete_all.
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	$retval = true;

	add_filter( 'query', 'bp_filter_metaid_column_name' );
	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'document_folder', $folder_id, $key, $meta_value, $delete_all );
	}
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get metadata for a given document folder item.
 *
 * @since BuddyBoss 1.3.6
 *
 * @param int    $folder_id ID of the document folder item whose metadata is being requested.
 * @param string $meta_key    Optional. If present, only the metadata matching
 *                            that meta key will be returned. Otherwise, all metadata for the
 *                            document folder item will be fetched.
 * @param bool   $single      Optional. If true, return only the first value of the
 *                            specified meta_key. This parameter has no effect if meta_key is not
 *                            specified. Default: true.
 * @return mixed The meta value(s) being requested.
 */
function bp_document_folder_get_meta( $folder_id = 0, $meta_key = '', $single = true ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = get_metadata( 'document_folder', $folder_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	/**
	 * Filters the metadata for a specified document folder item.
	 *
	 * @since BuddyBoss 1.3.6
	 *
	 * @param mixed  $retval      The meta values for the document folder item.
	 * @param int    $folder_id ID of the document folder item.
	 * @param string $meta_key    Meta key for the value being requested.
	 * @param bool   $single      Whether to return one matched meta key row or all.
	 */
	return apply_filters( 'bp_document_folder_get_meta', $retval, $folder_id, $meta_key, $single );
}

/**
 * Update a piece of document folder meta.
 *
 * @since BuddyBoss 1.3.6
 *
 * @param int    $folder_id ID of the document folder item whose metadata is being updated.
 * @param string $meta_key    Key of the metadata being updated.
 * @param mixed  $meta_value  Value to be set.
 * @param mixed  $prev_value  Optional. If specified, only update existing metadata entries
 *                            with the specified value. Otherwise, update all entries.
 * @return bool|int Returns false on failure. On successful update of existing
 *                  metadata, returns true. On successful creation of new metadata,
 *                  returns the integer ID of the new metadata row.
 */
function bp_document_folder_update_meta( $folder_id, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = update_metadata( 'document_folder', $folder_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of document folder metadata.
 *
 * @since BuddyBoss 1.3.6
 *
 * @param int    $folder_id ID of the document folder item.
 * @param string $meta_key    Metadata key.
 * @param mixed  $meta_value  Metadata value.
 * @param bool   $unique      Optional. Whether to enforce a single metadata value for the
 *                            given key. If true, and the object already has a value for
 *                            the key, no change will be made. Default: false.
 * @return int|bool The meta ID on successful update, false on failure.
 */
function bp_document_folder_add_meta( $folder_id, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = add_metadata( 'document_folder', $folder_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get file document upload max size
 *
 * @param bool $post_string
 *
 * @return string
 * @since BuddyBoss 1.3.6
 */
function bp_document_file_upload_max_size( $post_string = false, $type = 'bytes' ) {
	static $max_size = - 1;

	if ( $max_size < 0 ) {
		// Start with post_max_size.
		$size = @ini_get( 'post_max_size' );
		$unit = preg_replace( '/[^bkmgtpezy]/i', '', $size ); // Remove the non-unit characters from the size.
		$size = preg_replace( '/[^0-9\.]/', '', $size );      // Remove the non-numeric characters from the size.
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
		$size = preg_replace( '/[^0-9\.]/', '', $size );      // Remove the non-numeric characters from the size.
		if ( $unit ) {
			$upload_max = round( $size * pow( 1024, stripos( 'bkmgtpezy', $unit[0] ) ) );
		} else {
			$upload_max = round( $size );
		}
		if ( $upload_max > 0 && $upload_max < $max_size ) {
			$max_size = $upload_max;
		}
	}

	return bp_document_format_size_units( $max_size, $post_string, $type );
}

/**
 * Format file size units.
 *
 * @param           $bytes
 * @param bool   $post_string
 * @param string $type
 *
 * @return string
 * @since BuddyBoss 1.3.6
 */
function bp_document_format_size_units( $bytes, $post_string = false, $type = 'bytes' ) {

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
 * Retrieve an document or documents.
 * The bp_document_get() function shares all arguments with BP_Document::get().
 * The following is a list of bp_document_get() parameters that have different
 * default values from BP_Document::get() (value in parentheses is
 * the default for the bp_document_get()).
 *   - 'per_page' (false)
 *
 * @param array|string $args See BP_Document::get() for description.
 *
 * @return array $document See BP_Document::get() for description.
 * @since BuddyBoss 1.3.6
 * @see   BP_Document::get() For more information on accepted arguments
 *        and the format of the returned value.
 */
function bp_document_get( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'max'            => false,        // Maximum number of results to return.
			'fields'         => 'all',
			'page'           => 1,            // Page 1 without a per_page will result in no pagination.
			'per_page'       => false,        // results per page.
			'sort'           => 'DESC',       // sort ASC or DESC.
			'order_by'       => false,        // order by.
			'scope'          => false,

			// want to limit the query.
			'user_id'        => false,
			'activity_id'    => false,
			'folder_id'      => false,
			'group_id'       => false,
			'search_terms'   => false,        // Pass search terms as a string.
			'privacy'        => false,        // privacy of document.
			'exclude'        => false,        // Comma-separated list of activity IDs to exclude.
			'count_total'    => false,
			'user_directory' => true,

			'meta_query'     => false         // Filter by activity meta. See WP_Meta_Query for format
		),
		'document_get'
	);

	$document = BP_Document::documents(
		array(
			'page'           => $r['page'],
			'per_page'       => $r['per_page'],
			'user_id'        => $r['user_id'],
			'activity_id'    => $r['activity_id'],
			'folder_id'      => $r['folder_id'],
			'group_id'       => $r['group_id'],
			'meta_query'        => $r['meta_query'],
			'max'            => $r['max'],
			'sort'           => $r['sort'],
			'order_by'       => $r['order_by'],
			'search_terms'   => $r['search_terms'],
			'scope'          => $r['scope'],
			'privacy'        => $r['privacy'],
			'exclude'        => $r['exclude'],
			'count_total'    => $r['count_total'],
			'fields'         => $r['fields'],
			'user_directory' => $r['user_directory'],
		)
	);

	/**
	 * Filters the requested document item(s).
	 *
	 * @param BP_Document $document Requested document object.
	 * @param array       $r        Arguments used for the document query.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	return apply_filters_ref_array( 'bp_document_get', array( &$document, &$r ) );
}

/**
 * Fetch specific document items.
 *
 * @param array|string $args {
 *                           All arguments and defaults are shared with BP_Document::get(),
 *                           except for the following:
 *
 * @type string|int|array Single document ID, comma-separated list of IDs,
 *                            or array of IDs.
 * }
 * @return array $activity See BP_Document::get() for description.
 * @since BuddyBoss 1.3.6
 * @see   BP_Document::get() For more information on accepted arguments.
 */
function bp_document_get_specific( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'document_ids' => false,      // A single document_id or array of IDs.
			'max'          => false,      // Maximum number of results to return.
			'page'         => 1,          // Page 1 without a per_page will result in no pagination.
			'per_page'     => false,      // Results per page.
			'sort'         => 'DESC',     // Sort ASC or DESC.
			'order_by'     => false,      // Sort ASC or DESC.
			'folder_id'    => false,      // Sort ASC or DESC.
			'folder'       => false,
		),
		'document_get_specific'
	);

	$get_args = array(
		'in'        => $r['document_ids'],
		'max'       => $r['max'],
		'page'      => $r['page'],
		'per_page'  => $r['per_page'],
		'sort'      => $r['sort'],
		'order_by'  => $r['order_by'],
		'folder_id' => $r['folder_id'],
		'folder'    => $r['folder'],
	);

	/**
	 * Filters the requested specific document item.
	 *
	 * @param BP_Document $document Requested document object.
	 * @param array       $args     Original passed in arguments.
	 * @param array       $get_args Constructed arguments used with request.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	return apply_filters( 'bp_document_get_specific', BP_Document::get( $get_args ), $args, $get_args );
}

/**
 * Add an document item.
 *
 * @param array|string $args         {
 *                                   An array of arguments.
 *
 * @type int|bool      $id           Pass an document ID to update an existing item, or
 *                                       false to create a new item. Default: false.
 * @type int|bool      $blog_id      ID of the blog Default: current blog id.
 * @type int|bool      $attchment_id ID of the attachment Default: false
 * @type int|bool      $user_id      Optional. The ID of the user associated with the activity
 *                                       item. May be set to false or 0 if the item is not related
 *                                       to any user. Default: the ID of the currently logged-in user.
 * @type string        $title        Optional. The title of the document item.
 * @type int           $folder_id    Optional. The ID of the associated folder.
 * @type int           $group_id     Optional. The ID of a associated group.
 * @type int           $activity_id  Optional. The ID of a associated activity.
 * @type string        $privacy      Optional. Privacy of the document Default: public
 * @type int           $menu_order   Optional. Menu order the document Default: false
 * @type string        $date_created Optional. The GMT time, in Y-m-d h:i:s format, when
 *                                       the item was recorded. Defaults to the current time.
 * @type string        $error_type   Optional. Error type. Either 'bool' or 'wp_error'. Default: 'bool'.
 * }
 * @return WP_Error|bool|int The ID of the document on success. False on error.
 * @since BuddyBoss 1.3.6
 */
function bp_document_add( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'id'            => false,                   // Pass an existing document ID to update an existing entry.
			'blog_id'       => get_current_blog_id(),   // Blog ID.
			'attachment_id' => false,                   // attachment id.
			'user_id'       => bp_loggedin_user_id(),   // user_id of the uploader.
			'title'         => '',                      // title of document being added.
			'folder_id'     => false,                   // Optional: ID of the folder.
			'group_id'      => false,                   // Optional: ID of the group.
			'activity_id'   => false,                   // The ID of activity.
			'privacy'       => 'public',                // Optional: privacy of the document e.g. public.
			'menu_order'    => 0,                       // Optional:  Menu order.
			'date_created'  => bp_core_current_time(),  // The GMT time that this document was recorded.
			'date_modified' => bp_core_current_time(),  // The GMT time that this document was modified.
			'error_type'    => 'bool',
		),
		'document_add'
	);

	// Setup document to be added.
	$document                = new BP_Document( $r['id'] );
	$document->blog_id       = $r['blog_id'];
	$document->attachment_id = $r['attachment_id'];
	$document->user_id       = (int) $r['user_id'];
	$document->title         = $r['title'];
	$document->folder_id     = (int) $r['folder_id'];
	$document->group_id      = (int) $r['group_id'];
	$document->activity_id   = (int) $r['activity_id'];
	$document->privacy       = $r['privacy'];
	$document->menu_order    = $r['menu_order'];
	$document->date_created  = $r['date_created'];
	$document->date_modified = $r['date_modified'];
	$document->error_type    = $r['error_type'];

	// groups document always have privacy to `grouponly`.
	if ( ! empty( $document->privacy ) && ( in_array( $document->privacy, array( 'forums', 'message' ), true ) ) ) {
		$document->privacy = $r['privacy'];
	} elseif ( ! empty( $document->group_id ) ) {
		$document->privacy = 'grouponly';
	} elseif ( ! empty( $document->folder_id ) ) {
		$folder = new BP_Document_Folder( $document->folder_id );
		if ( ! empty( $folder ) ) {
			$document->privacy = $folder->privacy;
		}
	}

	if ( isset( $_POST ) && isset( $_POST['action'] ) && 'groups_get_group_members_send_message' === $_POST['action'] ) {
		$document->privacy = 'message';
	}

	// save document.
	$save = $document->save();

	if ( 'wp_error' === $r['error_type'] && is_wp_error( $save ) ) {
		return $save;
	} elseif ( 'bool' === $r['error_type'] && false === $save ) {
		return false;
	}

	// document is saved for attachment.
	update_post_meta( $document->attachment_id, 'bp_document_saved', true );

	/**
	 * Fires at the end of the execution of adding a new document item, before returning the new document item ID.
	 *
	 * @param object $document document object.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	do_action( 'bp_document_add', $document );

	return $document->id;
}

/**
 * Document add handler function
 *
 * @param array $documents
 *
 * @return mixed|void
 * @since BuddyBoss 1.3.6
 */
function bp_document_add_handler( $documents = array() ) {
	global $bp_document_upload_count;
	$document_ids = array();

	if ( empty( $documents ) && ! empty( $_POST['document'] ) ) {
		$documents = $_POST['document'];
	}

	$privacy = ! empty( $_POST['privacy'] ) && in_array( $_POST['privacy'], array_keys( bp_document_get_visibility_levels() ) ) ? $_POST['privacy'] : 'public';

	if ( ! empty( $documents ) && is_array( $documents ) ) {

		// update count of documents for later use.
		$bp_document_upload_count = count( $documents );

		// save  document.
		foreach ( $documents as $document ) {

			$attachment_data = get_post( $document['id'] );
			$file            = get_attached_file( $document['id'] );
			$file_type       = wp_check_filetype( $file );
			$file_name       = basename( $file );

			$document_id = bp_document_add(
				array(
					'attachment_id' => $document['id'],
					'title'         => $document['name'],
					'folder_id'     => ! empty( $document['folder_id'] ) ? $document['folder_id'] : false,
					'group_id'      => ! empty( $document['group_id'] ) ? $document['group_id'] : false,
					'privacy'       => ! empty( $document['privacy'] ) && in_array( $document['privacy'], array_merge( array_keys( bp_document_get_visibility_levels() ), array( 'message' ) ) ) ? $document['privacy'] : $privacy,
					'error_type'    => 'wp_error',
				)
			);

			if ( ! empty( $document_id ) && ! is_wp_error( $document_id ) ) {
				bp_document_update_meta( $document_id, 'file_name', $file_name );
				bp_document_update_meta( $document_id, 'extension', '.' . $file_type['ext'] );
			}

			if ( $document_id ) {
				$document_ids[] = $document_id;
			}
		}
	}

	/**
	 * Fires at the end of the execution of adding saving a document item, before returning the new document items in ajax response.
	 *
	 * @param array $document_ids document IDs.
	 * @param array $documents    Array of document from POST object or in function parameter.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	return apply_filters( 'bp_document_add_handler', $document_ids, (array) $documents );
}

/**
 * Delete document.
 *
 * @param array|string $args To delete specific document items, use
 *                           $args = array( 'id' => $ids ); Otherwise, to use
 *                           filters for item deletion, the argument format is
 *                           the same as BP_Document::get().
 *                           See that method for a description.
 * @param bool         $from Context of deletion from. ex. attachment, activity etc.
 *
 * @return bool|int The ID of the document on success. False on error.
 * @since BuddyBoss 1.3.6
 */
function bp_document_delete( $args = '', $from = false ) {

	// Pass one or more the of following variables to delete by those variables.
	$args = bp_parse_args(
		$args,
		array(
			'id'            => false,
			'blog_id'       => false,
			'attachment_id' => false,
			'user_id'       => false,
			'title'         => false,
			'folder_id'     => false,
			'activity_id'   => false,
			'group_id'      => false,
			'privacy'       => false,
			'date_created'  => false,
		)
	);

	/**
	 * Fires before an document item proceeds to be deleted.
	 *
	 * @param array $args Array of arguments to be used with the document deletion.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	do_action( 'bp_before_document_delete', $args );

	$document_ids_deleted = BP_Document::delete( $args, $from );
	if ( empty( $document_ids_deleted ) ) {
		return false;
	}

	/**
	 * Fires after the document item has been deleted.
	 *
	 * @param array $args Array of arguments used with the document deletion.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	do_action( 'bp_document_delete', $args );

	/**
	 * Fires after the document item has been deleted.
	 *
	 * @param array $document_ids_deleted Array of affected document item IDs.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	do_action( 'bp_document_deleted_documents', $document_ids_deleted );

	return true;
}

/**
 * Completely remove a user's document data.
 *
 * @param int $user_id ID of the user whose document is being deleted.
 *
 * @return bool
 * @since BuddyBoss 1.3.6
 */
function bp_document_remove_all_user_data( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		return false;
	}

	// Clear the user's folders from the sitewide stream and clear their document tables.
	bp_folder_delete( array( 'user_id' => $user_id ) );

	// Clear the user's document from the sitewide stream and clear their document tables.
	bp_document_delete( array( 'user_id' => $user_id ) );

	/**
	 * Fires after the removal of all of a user's document data.
	 *
	 * @param int $user_id ID of the user being deleted.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	do_action( 'bp_document_remove_all_user_data', $user_id );
}

add_action( 'wpmu_delete_user', 'bp_document_remove_all_user_data' );
add_action( 'delete_user', 'bp_document_remove_all_user_data' );

/**
 * Return the document activity.
 *
 * @param         $activity_id
 *
 * @return object|boolean The document activity object or false.
 * @global object $document_template {@link BP_Document_Template}
 * @since BuddyBoss 1.3.6
 */
function bp_document_get_document_activity( $activity_id ) {

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
	 * Filters the document activity object being displayed.
	 *
	 * @param object $activity The document activity.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	return apply_filters( 'bp_document_get_document_activity', $result['activities'][0] );
}

/**
 * Get the document count of a given user.
 *
 * @param int $user_id ID of the user whose document are being counted.
 *
 * @return int document count of the user.
 * @since BuddyBoss 1.3.6
 */
function bp_document_get_total_document_count( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	$count = wp_cache_get( 'bp_total_document_for_user_' . $user_id, 'bp' );

	if ( false === $count ) {
		$count = BP_Document::total_document_count( $user_id );
		wp_cache_set( 'bp_total_document_for_user_' . $user_id, $count, 'bp' );
	}

	/**
	 * Filters the total document count for a given user.
	 *
	 * @param int $count Total document count for a given user.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	return apply_filters( 'bp_document_get_total_document_count', (int) $count );
}

/**
 * Get the document count of a given group.
 *
 * @param int $group_id ID of the group whose document are being counted.
 *
 * @return int document count of the group.
 * @since BuddyBoss 1.3.6
 */
function bp_document_get_total_group_document_count( $group_id = 0 ) {
	if ( empty( $group_id ) && bp_get_current_group_id() ) {
		$group_id = bp_get_current_group_id();
	}

	$count = wp_cache_get( 'bp_total_document_for_group_' . $group_id, 'bp' );

	if ( false === $count ) {
		$count = BP_Document::total_group_document_count( $group_id );
		wp_cache_set( 'bp_total_document_for_group_' . $group_id, $count, 'bp' );
	}

	/**
	 * Filters the total document count for a given group.
	 *
	 * @param int $count Total document count for a given group.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	return apply_filters( 'bp_document_get_total_group_document_count', (int) $count );
}

/**
 * Get the folder count of a given group.
 *
 * @param int $group_id ID of the group whose folder are being counted.
 *
 * @return int folder count of the group.
 * @since BuddyBoss 1.3.6
 */
function bp_document_get_total_group_folder_count( $group_id = 0 ) {
	if ( empty( $group_id ) && bp_get_current_group_id() ) {
		$group_id = bp_get_current_group_id();
	}

	$count = wp_cache_get( 'bp_total_folder_for_group_' . $group_id, 'bp' );

	if ( false === $count ) {
		$count = BP_Document_Folder::total_group_folder_count( $group_id );
		wp_cache_set( 'bp_total_folder_for_group_' . $group_id, $count, 'bp' );
	}

	/**
	 * Filters the total folder count for a given group.
	 *
	 * @param int $count Total folder count for a given group.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	return apply_filters( 'bp_document_get_total_group_folder_count', (int) $count );
}

/**
 * Return the total document count in your BP instance.
 *
 * @return int document count.
 * @since BuddyBoss 1.3.6
 */
function bp_get_total_document_count() {

	add_filter( 'bp_ajax_querystring', 'bp_document_object_results_document_all_scope', 20 );
	bp_has_document( bp_ajax_querystring( 'document' ) );
	remove_filter( 'bp_ajax_querystring', 'bp_document_object_results_document_all_scope', 20 );
	$count = $GLOBALS['document_template']->total_document_count;

	/**
	 * Filters the total number of document.
	 *
	 * @param int $count Total number of document.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	return apply_filters( 'bp_get_total_document_count', (int) $count );
}

/**
 * document results all scope.
 *
 * @since BuddyBoss 1.3.6
 */
function bp_document_object_results_document_all_scope( $querystring ) {
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

	$querystring['user_id']     = 0;
	$querystring['count_total'] = true;

	return http_build_query( $querystring );
}

// ******************** Folders *********************/
/**
 * Retrieve an folder or folders.
 * The bp_folder_get() function shares all arguments with BP_Document_Folder::get().
 * The following is a list of bp_folder_get() parameters that have different
 * default values from BP_Document_Folder::get() (value in parentheses is
 * the default for the bp_folder_get()).
 *   - 'per_page' (false)
 *
 * @param array|string $args See BP_Document_Folder::get() for description.
 *
 * @return array $activity See BP_Document_Folder::get() for description.
 * @since BuddyBoss 1.3.6
 * @see   BP_Document_Folder::get() For more information on accepted arguments
 *        and the format of the returned value.
 */
function bp_folder_get( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'max'          => false,                    // Maximum number of results to return.
			'fields'       => 'all',
			'page'         => 1,                        // Page 1 without a per_page will result in no pagination.
			'per_page'     => false,                    // results per page.
			'sort'         => 'DESC',                   // sort ASC or DESC.

			'search_terms' => false,           // Pass search terms as a string.
			'exclude'      => false,           // Comma-separated list of activity IDs to exclude.
			// want to limit the query.
			'user_id'      => false,
			'group_id'     => false,
			'privacy'      => false,                    // privacy of folder.
			'count_total'  => false,
		),
		'folder_get'
	);

	$folder = BP_Document_Folder::get(
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
	 * Filters the requested folder item(s).
	 *
	 * @param BP_Document $folder Requested document object.
	 * @param array       $r      Arguments used for the folder query.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	return apply_filters_ref_array( 'bp_folder_get', array( &$folder, &$r ) );
}

/**
 * Fetch specific folders.
 *
 * @param array|string $args {
 *                           All arguments and defaults are shared with BP_Document_Folder::get(),
 *                           except for the following:
 *
 * @type string|int|array Single folder ID, comma-separated list of IDs,
 *                            or array of IDs.
 * }
 * @return array $folders See BP_Document_Folder::get() for description.
 * @since BuddyBoss 1.3.6
 * @see   BP_Document_Folder::get() For more information on accepted arguments.
 */
function bp_folder_get_specific( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'folder_ids'        => false,      // A single folder id or array of IDs.
			'max'               => false,      // Maximum number of results to return.
			'page'              => 1,          // Page 1 without a per_page will result in no pagination.
			'per_page'          => false,      // Results per page.
			'sort'              => 'DESC',     // Sort ASC or DESC.
			'update_meta_cache' => true,
			'count_total'       => false,
		),
		'document_get_specific'
	);

	$get_args = array(
		'in'          => $r['folder_ids'],
		'max'         => $r['max'],
		'page'        => $r['page'],
		'per_page'    => $r['per_page'],
		'sort'        => $r['sort'],
		'count_total' => $r['count_total'],
	);

	/**
	 * Filters the requested specific folder item.
	 *
	 * @param BP_Document $folder   Requested document object.
	 * @param array       $args     Original passed in arguments.
	 * @param array       $get_args Constructed arguments used with request.
	 *
	 * @since BuddyBoss
	 */
	return apply_filters( 'bp_folder_get_specific', BP_Document_Folder::get( $get_args ), $args, $get_args );
}

/**
 * Add folder item.
 *
 * @param array|string $args         {
 *                                   An array of arguments.
 *
 * @type int|bool      $id           Pass an activity ID to update an existing item, or
 *                                       false to create a new item. Default: false.
 * @type int|bool      $user_id      Optional. The ID of the user associated with the folder
 *                                       item. May be set to false or 0 if the item is not related
 *                                       to any user. Default: the ID of the currently logged-in user.
 * @type int           $group_id     Optional. The ID of the associated group.
 * @type string        $title        The title of folder.
 * @type string        $privacy      The privacy of folder.
 * @type string        $date_created Optional. The GMT time, in Y-m-d h:i:s format, when
 *                                       the item was recorded. Defaults to the current time.
 * @type string        $error_type   Optional. Error type. Either 'bool' or 'wp_error'. Default: 'bool'.
 * }
 * @return WP_Error|bool|int The ID of the folder on success. False on error.
 * @since BuddyBoss 1.3.6
 */
function bp_folder_add( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'id'            => false,
			// Pass an existing folder ID to update an existing entry.
			'user_id'       => bp_loggedin_user_id(),
			// User ID.
			'group_id'      => false,
			// attachment id.
			'title'         => '',
			// title of folder being added.
			'privacy'       => 'public',
			// Optional: privacy of the document e.g. public.
			'date_created'  => bp_core_current_time(),
			'date_modified' => bp_core_current_time(),
			// The GMT time that this document was recorded.
			'error_type'    => 'bool',
			'parent'        => 0,
		),
		'folder_add'
	);

	// Setup document to be added.
	$folder                = new BP_Document_Folder( $r['id'] );
	$folder->user_id       = (int) $r['user_id'];
	$folder->group_id      = (int) $r['group_id'];
	$folder->title         = $r['title'];
	$folder->privacy       = $r['privacy'];
	$folder->date_created  = $r['date_created'];
	$folder->date_modified = $r['date_modified'];
	$folder->error_type    = $r['error_type'];
	$folder->parent        = $r['parent'];

	if ( ! empty( $folder->group_id ) ) {
		$folder->privacy = 'grouponly';
	}

	$save = $folder->save();

	if ( 'wp_error' === $r['error_type'] && is_wp_error( $save ) ) {
		return $save;
	} elseif ( 'bool' === $r['error_type'] && false === $save ) {
		return false;
	}

	/**
	 * Fires at the end of the execution of adding a new folder item, before returning the new folder item ID.
	 *
	 * @param object $folder folder object.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	do_action( 'bp_folder_add', $folder );

	return $folder->id;
}

/**
 * Delete folder item.
 *
 * @param array|string $args To delete specific folder items, use
 *                           $args = array( 'id' => $ids ); Otherwise, to use
 *                           filters for item deletion, the argument format is
 *                           the same as BP_Document_Folder::get().
 *                           See that method for a description.
 *
 * @return bool True on Success. False on error.
 * @since BuddyBoss 1.3.6
 */
function bp_folder_delete( $args ) {

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
	 * Fires before an folder item proceeds to be deleted.
	 *
	 * @param array $args Array of arguments to be used with the folder deletion.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	do_action( 'bp_before_folder_delete', $args );

	$folder_ids_deleted = BP_Document_Folder::delete( $args );
	if ( empty( $folder_ids_deleted ) ) {
		return false;
	}

	/**
	 * Fires after the folder item has been deleted.
	 *
	 * @param array $args Array of arguments used with the folder deletion.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	do_action( 'bp_folder_delete', $args );

	/**
	 * Fires after the folder item has been deleted.
	 *
	 * @param array $folder_ids_deleted Array of affected folder item IDs.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	do_action( 'bp_folders_deleted_folders', $folder_ids_deleted );

	return true;
}

/**
 * Fetch a single folder object.
 * When calling up a folder object, you should always use this function instead
 * of instantiating BP_Document_Folder directly, so that you will inherit cache
 * support and pass through the folders_get_folder filter.
 *
 * @param int $folder_id ID of the folder.
 *
 * @return BP_Document_Folder $folder The folder object.
 * @since BuddyBoss 1.3.6
 */
function folders_get_folder( $folder_id ) {

	$folder = new BP_Document_Folder( $folder_id );

	/**
	 * Filters a single folder object.
	 *
	 * @param BP_Document_Folder $folder Single folder object.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	return apply_filters( 'folders_get_folder', $folder );
}

/**
 * Check folder access for current user or guest
 *
 * @param $folder_id
 *
 * @return bool
 * @since BuddyBoss 1.3.6
 */
function folders_check_folder_access( $folder_id ) {

	$folder = folders_get_folder( $folder_id );

	if ( ! empty( $folder->group_id ) ) {
		return false;
	}

	if ( ! empty( $folder->privacy ) ) {

		if ( 'public' === $folder->privacy ) {
			return true;
		}

		if ( 'loggedin' === $folder->privacy && is_user_logged_in() ) {
			return true;
		}

		if ( is_user_logged_in() && 'friends' === $folder->privacy && friends_check_friendship( get_current_user_id(), $folder->user_id ) ) {
			return true;
		}

		if ( bp_is_my_profile() && $folder->user_id === bp_loggedin_user_domain() && 'onlyme' === $folder->privacy ) {
			return true;
		}
	}

	return false;
}

/**
 * Delete orphaned attachments uploaded
 *
 * @since BuddyBoss 1.3.6
 */
function bp_document_delete_orphaned_attachments() {

	$orphaned_attachment_args = array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'fields'         => 'ids',
		'posts_per_page' => - 1,
		'meta_query'     => array(
			array(
				'key'     => 'bp_document_saved',
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
 * @since BuddyBoss 1.3.6
 */
function bp_document_sideload_attachment( $file ) {
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
	$id = bp_document_handle_sideload( $file_array );

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
 * @since BuddyBoss 1.3.6
 */
function bp_document_handle_sideload( $file_array, $post_data = array() ) {

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
 * Create and upload the document file.
 *
 * @return array|null|WP_Error|WP_Post
 * @since BuddyBoss 1.3.6
 */
function bp_document_upload() {
	/**
	 * Make sure user is logged in.
	 */
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'not_logged_in', __( 'Please login in order to upload file document.', 'buddyboss' ), array( 'status' => 500 ) );
	}

	$attachment = bp_document_upload_handler();

	if ( is_wp_error( $attachment ) ) {
		return $attachment;
	}

	$name = $attachment->post_name;

	$result = array(
		'id'   => (int) $attachment->ID,
		'url'  => esc_url( $attachment->guid ),
		'name' => esc_attr( pathinfo( basename ( get_attached_file( (int) $attachment->ID ) ), PATHINFO_FILENAME ) ),
		'type' => esc_attr( 'document' ),
	);

	return $result;
}

/**
 * document upload handler
 *
 * @param string $file_id
 *
 * @return array|int|null|WP_Error|WP_Post
 * @since BuddyBoss 1.3.6
 */
function bp_document_upload_handler( $file_id = 'file' ) {

	/**
	 * Include required files.
	 */

	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin' . '/includes/image.php';
		require_once ABSPATH . 'wp-admin' . '/includes/file.php';
		require_once ABSPATH . 'wp-admin' . '/includes/media.php';
	}

	if ( ! function_exists( 'media_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/admin.php';
	}

	add_filter( 'upload_mimes', 'bp_document_allowed_mimes', 9999999, 1 );

	$aid = media_handle_upload(
		$file_id,
		0,
		array(),
		array(
			'test_form'            => false,
			'upload_error_strings' => array(
				false,
				__( 'The uploaded file exceeds ', 'buddyboss' ) . bp_document_file_upload_max_size( true ),
				__( 'The uploaded file exceeds ', 'buddyboss' ) . bp_document_file_upload_max_size( true ),
				__( 'The uploaded file was only partially uploaded.', 'buddyboss' ),
				__( 'No file was uploaded.', 'buddyboss' ),
				'',
				__( 'Missing a temporary folder.', 'buddyboss' ),
				__( 'Failed to write file to disk.', 'buddyboss' ),
				__( 'File upload stopped by extension.', 'buddyboss' ),
			),
		)
	);

	// if has wp error then throw it.
	if ( is_wp_error( $aid ) ) {
		return $aid;
	}

	$attachment = get_post( $aid );

	if ( ! empty( $attachment ) ) {
		update_post_meta( $attachment->ID, 'bp_document_upload', true );
		update_post_meta( $attachment->ID, 'bp_document_saved', '0' );

		return $attachment;
	}

	return new WP_Error( 'error_uploading', __( 'Error while uploading document.', 'buddyboss' ), array( 'status' => 500 ) );

}

/**
 * Mine type for uploader allowed by buddyboss document for security reason.
 *
 * @param Array $existing_mimes carry mime information.
 *
 * @return Array
 * @since BuddyBoss 1.3.6
 */
function bp_document_allowed_mimes( $existing_mimes = array() ) {

	if ( bp_is_active( 'media' ) ) {
		$existing_mimes = array();
		$all_extensions = bp_document_extensions_list();
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
 * Return the extension of the attachment.
 *
 * @param $attachment_id
 *
 * @return mixed|string
 * @since BuddyBoss 1.3.6
 */
function bp_document_extension( $attachment_id ) {

	$file_url  = wp_get_attachment_url( $attachment_id );
	$file_type = wp_check_filetype( $file_url );
	$extension = trim( $file_type['ext'] );

	if ( '' === $extension ) {
		$file      = pathinfo( $file_url );
		$extension = $file['extension'];
	}

	return $extension;

}

/**
 * Return the icon based on the extension.
 *
 * @param $extension
 *
 * @return mixed|void
 * @since BuddyBoss 1.3.6
 */
function bp_document_svg_icon( $extension ) {

	$svg = '';

	switch ( $extension ) {
		case 'css':
			$svg = 'bb-icon-code';
			break;
		case 'csv':
			$svg = 'bb-icon-file-csv';
			break;
		case 'doc':
			$svg = 'bb-icon-doc';
			break;
		case 'docm':
			$svg = 'bb-icon-doc';
			break;
		case 'docx':
			$svg = 'bb-icon-doc';
			break;
		case 'dotx':
			$svg = 'bb-icon-doc';
			break;
		case 'gzip':
			$svg = 'bb-icon-zip';
			break;
		case 'ace':
			$svg = 'bb-icon-zip';
			break;
		case '7z':
			$svg = 'bb-icon-zip';
			break;
		case 'htm':
			$svg = 'bb-icon-code';
			break;
		case 'html':
			$svg = 'bb-icon-code';
			break;
		case 'ics':
			$svg = 'bb-icon-file-ics';
			break;
		case 'ico':
			$svg = 'bb-icon-file-ico';
			break;
		case 'js':
			$svg = 'bb-icon-code';
			break;
		case 'jar':
			$svg = 'bb-icon-file-jar';
			break;
		case 'mp3':
			$svg = 'bb-icon-mp3';
			break;
		case 'ods':
			$svg = 'bb-icon-file-ods';
			break;
		case 'odt':
			$svg = 'bb-icon-file-odt';
			break;
		case 'pdf':
			$svg = 'bb-icon-pdf';
			break;
		case 'psd':
			$svg = 'bb-icon-file-psd';
			break;
		case 'potm':
			$svg = 'bb-icon-file-pptm';
			break;
		case 'potx':
			$svg = 'bb-icon-file-pptx';
			break;
		case 'pps':
			$svg = 'bb-icon-file-pps';
			break;
		case 'ppsx':
			$svg = 'bb-icon-file-ppsx';
			break;
		case 'ppt':
			$svg = 'bb-icon-file-ppt';
			break;
		case 'pptm':
			$svg = 'bb-icon-file-pptm';
			break;
		case 'pptx':
			$svg = 'bb-icon-file-pptx';
			break;
		case 'rar':
			$svg = 'bb-icon-file-rar';
			break;
		case 'rtf':
			$svg = 'bb-icon-file-rtf';
			break;
		case 'tar':
			$svg = 'bb-icon-file-tar';
			break;
		case 'txt':
			$svg = 'bb-icon-file-txt';
			break;
		case 'wav':
			$svg = 'bb-icon-mp3';
			break;
		case 'xls':
			$svg = 'bb-icon-file-xls';
			break;
		case 'xlsm':
			$svg = 'bb-icon-file-xls';
			break;
		case 'xlsx':
			$svg = 'bb-icon-file-xls';
			break;
		case 'xltm':
			$svg = 'bb-icon-file-xls';
			break;
		case 'zip':
			$svg = 'bb-icon-zip';
			break;
		case 'folder':
			$svg = 'bb-icon-folder-stacked';
			break;
		case 'download':
			$svg = 'bb-icon-download';
			break;
		default:
			$svg = 'bb-icon-file';
	}

	return apply_filters( 'bp_document_svg_icon', $svg, $extension );
}

/**
 * Return the breadcrumbs.
 *
 * @param int $user_id
 * @param int $group_id
 *
 * @return string
 * @since BuddyBoss 1.3.6
 */
function bp_document_user_document_folder_tree_view_li_html( $user_id = 0, $group_id = 0 ) {

	global $wpdb, $bp;

	$document_folder_table = $bp->document->table_name_folder;

	if ( 0 === $group_id ) {
		$group_id = ( function_exists( 'bp_get_current_group_id' ) ) ? bp_get_current_group_id() : 0;
	}

	if ( $group_id > 0 ) {
		$documents_folder_query = $wpdb->prepare( "SELECT * FROM {$document_folder_table} WHERE group_id = %d ORDER BY id DESC", $group_id );
	} else {
		$documents_folder_query = $wpdb->prepare( "SELECT * FROM {$document_folder_table} WHERE user_id = %d AND group_id = %d ORDER BY id DESC", $user_id, $group_id );
	}

	$data = $wpdb->get_results( $documents_folder_query, ARRAY_A ); // db call ok; no-cache ok;

	// Build array of item references:
	foreach ( $data as $key => &$item ) {
		$itemsByReference[ $item['id'] ] = &$item;
		// Children array:
		$itemsByReference[ $item['id'] ]['children'] = array();
		// Empty data class (so that json_encode adds "data: {}" )
		$itemsByReference[ $item['id'] ]['data'] = new StdClass();
	}

	// Set items as children of the relevant parent item.
	foreach ( $data as $key => &$item ) {
		if ( $item['parent'] && isset( $itemsByReference[ $item['parent'] ] ) ) {
			$itemsByReference [ $item['parent'] ]['children'][] = &$item;
		}
	}

	// Remove items that were added to parents elsewhere:
	foreach ( $data as $key => &$item ) {
		if ( $item['parent'] && isset( $itemsByReference[ $item['parent'] ] ) ) {
			unset( $data[ $key ] );
		}
	}

	return bp_document_folder_recursive_li_list( $data, false );

}

/**
 * This function will give the breadcrumbs ul li html.
 *
 * @param      $array
 * @param bool  $first
 *
 * @return string
 * @since BuddyBoss 1.3.6
 */
function bp_document_folder_recursive_li_list( $array, $first = false ) {

	// Base case: an empty array produces no list.
	if ( empty( $array ) ) {
		return '';
	}

	// Recursive Step: make a list with child lists.
	if ( $first ) {
		$output = '<ul class="">';
	} else {
		$output = '<ul class="location-folder-list">';
	}

	foreach ( $array as $item ) {
		$output .= '<li data-id="' . $item['id'] . '"><span id="' . $item['id'] . '" data-id="' . $item['id'] . '">' . stripslashes( $item['title'] ) . '</span>' . bp_document_folder_recursive_li_list( $item['children'], true ) . '</li>';
	}
	$output .= '</ul>';

	return $output;
}

/**
 * This function will give the breadcrumbs html.
 *
 * @param $folder_id
 *
 * @return string
 * @since BuddyBoss 1.3.6
 */
function bp_document_folder_bradcrumb( $folder_id ) {

	global $wpdb, $bp;

	$document_folder_table  = $bp->document->table_name_folder;
	$documents_folder_query = $wpdb->prepare(
		"SELECT c.*
    FROM (
        SELECT
            @r AS _id,
            (SELECT @r := parent FROM {$document_folder_table} WHERE id = _id) AS parent,
            @l := @l + 1 AS level
        FROM
            (SELECT @r := %d, @l := 0) vars, {$document_folder_table} m
        WHERE @r <> 0) d
    JOIN {$document_folder_table} c
    ON d._id = c.id",
		$folder_id
	);
	$data                   = $wpdb->get_results( $documents_folder_query, ARRAY_A ); // db call ok; no-cache ok;
	$html                   = '';

	if ( ! empty( $data ) ) {
		$data  = array_reverse( $data );
		$html .= '<ul class="document-breadcrumb">';
		if ( bp_is_group() && bp_is_group_single() ) {
			$group = groups_get_group( array( 'group_id' => bp_get_current_group_id() ) );
			$link  = bp_get_group_permalink( $group ) . bp_get_document_root_slug();
			$html .= '<li><a href=" ' . $link . ' "> ' . __( 'Documents', 'buddyboss' ) . '</a></li>';
		} else {
			$link  = bp_displayed_user_domain() . bp_get_document_root_slug();
			$html .= '<li><a href=" ' . $link . ' "> ' . __( 'Documents', 'buddyboss' ) . '</a></li>';
		}

		if ( count( $data ) > 3 ) {
			$html .= '<li>' . __( '...', 'buddyboss' ) . '</li>';
			$data  = array_slice( $data, - 3 );
		}
		foreach ( $data as $element ) {
			$link     = '';
			$group_id = (int) $element['group_id'];
			if ( 0 === $group_id ) {
				$link = bp_displayed_user_domain() . bp_get_document_root_slug() . '/folders/' . $element['id'];
			} else {
				$group = groups_get_group( array( 'group_id' => $group_id ) );
				$link  = bp_get_group_permalink( $group ) . bp_get_document_root_slug() . '/folders/' . $element['id'];
			}
			$html .= '<li> <a href=" ' . $link . ' ">' . stripslashes( $element['title'] ) . '</a></li>';
		}
		$html .= '</ul>';
	}

	return $html;

}

/**
 * This function will document into the folder.
 *
 * @param int $document_id
 * @param int $folder_id
 * @param int $group_id
 *
 * @return bool|int
 * @since BuddyBoss 1.3.6
 */
function bp_document_move_document_to_folder( $document_id = 0, $folder_id = 0, $group_id = 0 ) {

	global $wpdb, $bp;

	if ( 0 === $document_id ) {
		return false;
	}

	$destination_privacy = 'loggedin';
	if ( $group_id > 0 ) {
		$destination_privacy = 'grouponly';
	} elseif ( $folder_id > 0 ) {
		$destination_folder  = BP_Document_Folder::get_folder_data( array( $folder_id ) );
		$destination_privacy = $destination_folder[0]->privacy;

		// Update modify date for destination folder.
		$destination_folder_update                 = new BP_Document_Folder( $folder_id );
		$destination_folder_update->date_modified  = bp_core_current_time();
		$destination_folder_update->save();
	}

	$document                = new BP_Document( $document_id );
	$document->folder_id     = $folder_id;
	$document->group_id      = $group_id;
	$document->date_modified = bp_core_current_time();
	$document->save();

	// Update document activity privacy.
	if ( ! $group_id ) {
		if ( ! empty( $document ) && ! empty( $document->attachment_id ) ) {
			$post_attachment = $document->attachment_id;
			$activity_id     = get_post_meta( $post_attachment, 'bp_document_parent_activity_id', true );
			if ( ! empty( $activity_id ) ) {
				$activity = new BP_Activity_Activity( (int) $activity_id );
				if ( bp_activity_user_can_delete( $activity ) ) {
					$activity->privacy = $destination_privacy;
					$activity->save();
				}
			}
		}
	}
	return $document_id;
}

/**
 * Get document visibility levels out of the $bp global.
 *
 * @return array
 * @since BuddyBoss 1.3.6
 */
function bp_document_get_visibility_levels() {

	/**
	 * Filters the document visibility levels out of the $bp global.
	 *
	 * @param array $visibility_levels Array of visibility levels.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	return apply_filters( 'bp_document_get_visibility_levels', buddypress()->document->visibility_levels );
}

/**
 * This function will rename the document name.
 *
 * @param int    $document_id
 * @param int    $attachment_document_id
 * @param string $title
 *
 * @return bool|int
 * @since BuddyBoss 1.3.6
 */
function bp_document_rename_file( $document_id = 0, $attachment_document_id = 0, $title = '', $backend = false ) {

	global $wpdb, $bp;

	if ( 0 === $document_id && '' === $title ) {
		return false;
	}

	$file_name = $title;

	$query = $wpdb->prepare( "UPDATE {$bp->document->table_name} SET title = %s, date_modified = %s WHERE id = %d AND attachment_id = %d", $title, bp_core_current_time(), $document_id, $attachment_document_id );
	$query = $wpdb->query( $query ); // db call ok; no-cache ok;

	bp_document_update_meta( $document_id, 'file_name', $file_name );

	if ( false === $query ) {
		return false;
	}

	// Do not update title if already updated from backend.
	if ( false === $backend ) {
		$post = get_post( $attachment_document_id, ARRAY_A );
		if ( isset( $title ) ) {
			$post['post_title'] = $title;
			wp_update_post( $post );
		}
	}

	// Rename filename based on the title.
	$file          = get_attached_file( $attachment_document_id );
	$path          = pathinfo( $file );
	$new_file_name = $file_name;
	$new_file_name = wp_unique_filename( $path['dirname'], $new_file_name . '.' . $path['extension'] );
	$new_file      = $path['dirname'] . '/' . $new_file_name;

	rename( $file, $new_file );
	update_attached_file( $attachment_document_id, $new_file );

	$extension              = '.' . $path['extension'];
	$title                  = basename( $new_file, $extension );
	$rename_file_name_query = $wpdb->prepare( "UPDATE {$bp->document->table_name} SET title = %s, date_modified = %s WHERE id = %d AND attachment_id = %d", $title, bp_core_current_time(), $document_id, $attachment_document_id );
	$rename_file_name_query = $wpdb->query( $rename_file_name_query ); // db call ok; no-cache ok;

	bp_document_update_meta( $document_id, 'file_name', $new_file_name );
	bp_document_update_meta( $document_id, 'extension', $extension );

	$response = apply_filters(
		'bp_document_rename_file',
		array(
			'document_id'            => $document_id,
			'attachment_document_id' => $attachment_document_id,
			'title'                  => $title,
			'backendn'               => $backend,
		)
	);

	return $response;
}

/**
 * This function will rename the folder name.
 *
 * @param int    $folder_id
 * @param string $title
 * @param string $privacy
 *
 * @return bool|int
 * @since BuddyBoss 1.3.6
 */
function bp_document_rename_folder( $folder_id = 0, $title = '', $privacy = '' ) {

	global $wpdb, $bp;

	if ( 0 === $folder_id && '' === $title ) {
		return false;
	}

	$q = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->document->table_name_folder} SET title = %s, date_modified = %s WHERE id = %d", $title, bp_core_current_time(), $folder_id ) ); // db call ok; no-cache ok;

	bp_document_update_privacy( $folder_id, $privacy, 'folder' );

	if ( false === $q ) {
		return false;
	}

	return $folder_id;
}

/**
 * This function will rename the folder name.
 *
 * @param int $folder_id
 *
 * @return bool|int
 * @since BuddyBoss 1.3.6
 */
function bp_document_update_folder_modified_date( $folder_id = 0 ) {

	global $wpdb, $bp;

	if ( 0 === $folder_id ) {
		return false;
	}

	$q = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->document->table_name_folder} SET date_modified = %s WHERE id = %d", bp_core_current_time(), $folder_id ) ); // db call ok; no-cache ok;

	if ( false === $q ) {
		return false;
	}

	return $folder_id;
}

/**
 * This function will move folder to another destination folder id.
 *
 * @param $folder_id
 * @param $destination_folder_id
 *
 * @return bool
 * @since BuddyBoss 1.3.6
 */
function bp_document_move_folder_to_folder( $folder_id, $destination_folder_id, $group_id = 0 ) {

	global $wpdb, $bp;

	if ( '' === $folder_id || '' === $destination_folder_id ) {
		return false;
	}

	$destination_privacy = 'loggedin';
	if ( $group_id > 0 ) {
		$destination_privacy = 'grouponly';
	} elseif ( $destination_folder_id > 0 ) {
		$destination_folder  = BP_Document_Folder::get_folder_data( array( $destination_folder_id ) );
		$destination_privacy = $destination_folder[0]->privacy;

		// Update modify date for destination folder.
		$destination_folder_update                 = new BP_Document_Folder( $destination_folder_id );
		$destination_folder_update->date_modified  = bp_core_current_time();
		$destination_folder_update->save();
	}

	// Update main parent folder.
	$folder                 = new BP_Document_Folder( $folder_id );
	$folder->privacy        = $destination_privacy;
	$folder->parent         = $destination_folder_id;
	$folder->date_modified  = bp_core_current_time();
	$folder->save();

	// Get all the documents of main folder.
	$document_ids = bp_document_get_folder_document_ids( $folder_id );
	if ( ! empty( $document_ids ) ) {
		foreach ( $document_ids as $id ) {
			// Update privacy of the document.
			$query_update_document = $wpdb->prepare( "UPDATE {$bp->document->table_name} SET privacy = %s WHERE id = %d", $destination_privacy, $id ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$query                 = $wpdb->query( $query_update_document );

			// Update document activity privacy.
			$document = new BP_Document( $id );
			if ( ! empty( $document ) && ! empty( $document->attachment_id ) ) {
				$post_attachment = $document->attachment_id;
				$activity_id     = get_post_meta( $post_attachment, 'bp_document_parent_activity_id', true );
				if ( ! empty( $activity_id ) ) {
					$activity = new BP_Activity_Activity( (int) $activity_id );
					if ( bp_activity_user_can_delete( $activity ) ) {
						$activity->privacy = $destination_privacy;
						$activity->save();
					}
				}
			}
		}
	}

	// Update privacy for all child folders.
	$get_children = bp_document_get_folder_children( $folder_id );

	foreach ( $get_children as $child ) {
		$query_update_child = $wpdb->prepare( "UPDATE {$bp->document->table_name_folder} SET privacy = %s WHERE id = %d", $destination_privacy, $child ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$query              = $wpdb->query( $query_update_child );

		// Get all the documents of particular folder.
		$document_ids = bp_document_get_folder_document_ids( $child );

		if ( ! empty( $document_ids ) ) {
			foreach ( $document_ids as $id ) {

				// Update privacy of the document.
				$query_update_document = $wpdb->prepare( "UPDATE {$bp->document->table_name} SET privacy = %s WHERE id = %d", $destination_privacy, $id ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$query                 = $wpdb->query( $query_update_document );

				// Update document activity privacy.
				$document = new BP_Document( $id );
				if ( ! empty( $document ) && ! empty( $document->attachment_id ) ) {
					$post_attachment = $document->attachment_id;
					$activity_id     = get_post_meta( $post_attachment, 'bp_document_parent_activity_id', true );
					if ( ! empty( $activity_id ) ) {
						$activity = new BP_Activity_Activity( (int) $activity_id );
						if ( bp_activity_user_can_delete( $activity ) ) {
							$activity->privacy = $destination_privacy;
							$activity->save();
						}
					}
				}
			}
		}
	}

	return true;
}

/**
 * Update document privacy with nested level.
 *
 * @param int    $document_id Document/Folder ID.
 * @param string $privacy     Privacy term to update.
 * @param string $type        Current type for the document.
 *
 * @return bool
 * @since BuddyBoss 1.3.6
 */
function bp_document_update_privacy( $document_id = 0, $privacy = '', $type = 'folder' ) {

	global $wpdb, $bp;

	if ( '' === $document_id || '' === $privacy ) {
		return false;
	}

	if ( 'folder' === $type ) {
		$q = $wpdb->prepare( "UPDATE {$bp->document->table_name_folder} SET privacy = %s, date_modified = %s WHERE id = %d", $privacy, bp_core_current_time(), $document_id );  // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$wpdb->query( $q );

		// Get main folder's child folders.
		$get_children = bp_document_get_folder_children( $document_id );
		if ( ! empty( $get_children ) ) {
			foreach ( $get_children as $child ) {
				$query_child_privacy = $wpdb->prepare( "UPDATE {$bp->document->table_name_folder} SET privacy = %s WHERE id = %d", $privacy, $child ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$wpdb->query( $query_child_privacy );

				// Get current folder's documents.
				$child_document_ids = bp_document_get_folder_document_ids( $child );
				if ( ! empty( $child_document_ids ) ) {
					foreach ( $child_document_ids as $child_document_id ) {
						$child_document_query = $wpdb->prepare( "UPDATE {$bp->document->table_name} SET privacy = %s WHERE id = %d", $privacy, $child_document_id );
						$wpdb->query( $child_document_query );

						$document = new BP_Document( $child_document_id );
						if ( ! empty( $document ) && ! empty( $document->attachment_id ) ) {
							$post_attachment = $document->attachment_id;
							$activity_id     = get_post_meta( $post_attachment, 'bp_document_parent_activity_id', true );
							if ( ! empty( $activity_id ) ) {
								$activity = new BP_Activity_Activity( (int) $activity_id );
								if ( bp_activity_user_can_delete( $activity ) ) {
									$activity->privacy = $privacy;
									$activity->save();
								}
							}
						}
					}
				}
			}
		}

		// Get main folder's documents.
		$get_document_ids = bp_document_get_folder_document_ids( $document_id );
		if ( ! empty( $get_document_ids ) ) {
			foreach ( $get_document_ids as $document_id ) {
				$document_query = $wpdb->prepare( "UPDATE {$bp->document->table_name} SET privacy = %s WHERE id = %d", $privacy, $document_id );
				$wpdb->query( $document_query );

				$document = new BP_Document( $document_id );
				if ( ! empty( $document ) && ! empty( $document->attachment_id ) ) {
					$post_attachment = $document->attachment_id;
					$activity_id     = get_post_meta( $post_attachment, 'bp_document_parent_activity_id', true );
					if ( ! empty( $activity_id ) ) {
						$activity = new BP_Activity_Activity( (int) $activity_id );
						if ( bp_activity_user_can_delete( $activity ) ) {
							$activity->privacy = $privacy;
							$activity->save();
						}
					}
				}
			}
		}
	} else {
		$document_query = $wpdb->prepare( "UPDATE {$bp->document->table_name} SET privacy = %s, date_modified = %s WHERE id = %d", $privacy, bp_core_current_time(), $document_id );
		$wpdb->query( $document_query );

		$document = new BP_Document( $document_id );
		if ( ! empty( $document ) && ! empty( $document->attachment_id ) ) {
			$post_attachment = $document->attachment_id;
			$activity_id     = get_post_meta( $post_attachment, 'bp_document_parent_activity_id', true );
			if ( ! empty( $activity_id ) ) {
				$activity = new BP_Activity_Activity( (int) $activity_id );
				if ( bp_activity_user_can_delete( $activity ) ) {
					$activity->privacy = $privacy;
					$activity->save();
				}
			}
		}
	}
}

/**
 * Return all the documents ids of the folder.
 *
 * @param $folder_id
 *
 * @return array
 * @since BuddyBoss 1.3.6
 */
function bp_document_get_folder_document_ids( $folder_id ) {
	global $wpdb, $bp;

	return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$bp->document->table_name} WHERE folder_id = %d", $folder_id ) ) );
}

/**
 * Return download link of the document.
 *
 * @param $attachment_id
 * @param $document_id
 *
 * @return mixed|void
 * @since BuddyBoss 1.3.6
 */
function bp_document_download_link( $attachment_id, $document_id ) {

	if ( empty( $attachment_id ) ) {
		return;
	}

	$link = get_the_permalink( $attachment_id ) . '?attachment_id=' . $attachment_id . '&document_type=document&download_document_file=1' . '&document_file=' . $document_id;

	return apply_filters( 'bp_document_download_link', $link, $attachment_id );

}

/**
 * Return download link of the folder.
 *
 * @param $folder_id
 *
 * @return mixed|void
 * @since BuddyBoss 1.3.6
 */
function bp_document_folder_download_link( $folder_id ) {

	if ( empty( $folder_id ) ) {
		return;
	}

	$link = bp_get_folder_link( $folder_id ) . '?attachment_id=' . $folder_id . '&document_type=folder&download_document_file=1' . '&document_file=' . $folder_id;

	return apply_filters( 'bp_document_folder_download_link', $link, $folder_id );

}

/**
 * Check user have a permission to manage the folder.
 *
 * @param int $folder_id
 * @param int $user_id
 *
 * @return mixed|void
 * @since BuddyBoss 1.3.6
 */
function bp_document_user_can_manage_folder( $folder_id = 0, $user_id = 0 ) {

	$can_manage   = false;
	$can_view     = false;
	$can_download = true;
	$folder       = new BP_Document_Folder( $folder_id );
	$data         = array();

	switch ( $folder->privacy ) {

		case 'public':
			if ( $folder->user_id === $user_id ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
			} else {
				$can_manage   = false;
				$can_view     = true;
				$can_download = true;
			}
			break;

		case 'grouponly':
			if ( bp_is_active( 'groups' ) ) {

				$manage = groups_can_user_manage_document( $user_id, $folder->group_id );

				if ( $manage ) {
					$can_manage   = true;
					$can_view     = true;
					$can_download = true;
				} else {
					$the_group = groups_get_group( absint( $folder->group_id ) );
					if ( $the_group->id > 0 && $the_group->user_has_access ) {
						$can_view     = true;
						$can_download = true;
					}
				}
			}

			break;

		case 'loggedin':
			if ( $folder->user_id === $user_id ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
			} elseif ( bp_loggedin_user_id() === $user_id ) {
				$can_manage   = false;
				$can_view     = true;
				$can_download = true;
			}
			break;

		case 'friends':
			$is_friend = friends_check_friendship( $folder->user_id, $user_id );
			if ( $folder->user_id === $user_id ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
			} elseif ( $is_friend ) {
				$can_manage   = false;
				$can_view     = true;
				$can_download = true;
			}
			break;

		case 'onlyme':
			if ( $folder->user_id === $user_id ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
			}
			break;

	}

	$data['can_manage']   = $can_manage;
	$data['can_view']     = $can_view;
	$data['can_download'] = $can_download;

	return apply_filters( 'bp_document_user_can_manage_folder', $data, $folder_id, $user_id );
}

/**
 * Check user have a permission to manage the document.
 *
 * @param int $document_id
 * @param int $user_id
 *
 * @return mixed|void
 * @since BuddyBoss 1.3.6
 */
function bp_document_user_can_manage_document( $document_id = 0, $user_id = 0 ) {

	$can_manage   = false;
	$can_view     = false;
	$can_download = false;
	$document     = new BP_Document( $document_id );
	$data         = array();

	switch ( $document->privacy ) {

		case 'public':
			if ( $document->user_id === $user_id ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
			} else {
				$can_manage   = false;
				$can_view     = true;
				$can_download = true;
			}
			break;

		case 'grouponly':
			if ( bp_is_active( 'groups' ) ) {

				$manage = groups_can_user_manage_document( $user_id, $document->group_id );

				if ( $manage ) {
					$can_manage   = true;
					$can_view     = true;
					$can_download = true;
				} else {
					$the_group = groups_get_group( absint( $document->group_id ) );
					if ( $the_group->id > 0 && $the_group->user_has_access ) {
						$can_view     = true;
						$can_download = true;
					}
				}
			}

			break;

		case 'loggedin':
			if ( $document->user_id === $user_id ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
			} elseif ( bp_loggedin_user_id() === $user_id ) {
				$can_manage   = false;
				$can_view     = true;
				$can_download = true;
			}
			break;

		case 'friends':
			$is_friend = friends_check_friendship( $document->user_id, $user_id );
			if ( $document->user_id === $user_id ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
			} elseif ( $is_friend ) {
				$can_manage   = false;
				$can_view     = true;
				$can_download = true;
			}
			break;

		case 'forums':
			$args       = array(
				'user_id'         => $user_id,
				'forum_id'        => bp_document_get_meta( $document_id, 'forum_id', true ),
				'check_ancestors' => false,
			);
			$has_access = bbp_user_can_view_forum( $args );
			if ( $document->user_id === $user_id ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
			} elseif ( $has_access ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
			}
			break;

		case 'message':
			$thread_id = bp_document_get_meta( $document_id, 'thread_id', true );
			$has_access = messages_check_thread_access( $thread_id, $user_id );
			if ( $document->user_id === $user_id ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
			} elseif ( $has_access > 0 ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
			}
			break;

		case 'onlyme':
			if ( $document->user_id === $user_id ) {
				$can_manage   = true;
				$can_view     = true;
				$can_download = true;
			}
			break;

	}

	$data['can_manage']   = $can_manage;
	$data['can_view']     = $can_view;
	$data['can_download'] = $can_download;

	return apply_filters( 'bp_document_user_can_manage_folder', $data, $document_id, $user_id );
}

/**
 * Return all the allowed document extensions.
 *
 * @return array
 * @since BuddyBoss 1.3.6
 */
function bp_document_get_allowed_extension() {
	$extensions     = array();
	$all_extensions = bp_document_extensions_list();
	foreach ( $all_extensions as $extension ) {
		if ( isset( $extension['is_active'] ) && true === (bool) $extension['is_active'] ) {
			$extensions[] = $extension['extension'];
		}
	}

	return apply_filters( 'bp_document_get_allowed_extension', $extensions );
}

/**
 * Return all the document ids inside folder.
 *
 * @param $folder_id
 *
 * @return array|object|null
 * @since BuddyBoss 1.3.6
 */
function bp_document_get_folder_attachment_ids( $folder_id ) {

	global $bp, $wpdb;

	$table = $bp->document->table_name;

	$documents_attachment_query = $wpdb->prepare( "SELECT attachment_id FROM {$table} WHERE folder_id = %d", $folder_id );
	$data                       = $wpdb->get_results( $documents_attachment_query ); // db call ok; no-cache ok;

	return $data;

}

/**
 * Return all the children folder of the given folder.
 *
 * @param $folder_id
 *
 * @return array
 * @since BuddyBoss 1.3.6
 */
function bp_document_get_folder_children( $folder_id ) {

	global $bp, $wpdb;

	$table = $bp->document->table_name_folder;

	// error_log( $wpdb->prepare( "SELECT id FROM {$table} WHERE FIND_IN_SET(id,(SELECT GROUP_CONCAT(lv SEPARATOR ',') FROM ( SELECT @pv:=(SELECT GROUP_CONCAT(id SEPARATOR ',') FROM {$table} WHERE parent IN (@pv)) AS lv FROM {$table} JOIN (SELECT @pv:=%d)tmp WHERE parent IN (@pv)) a))", $folder_id ) );
	return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE FIND_IN_SET(id,(SELECT GROUP_CONCAT(lv SEPARATOR ',') FROM ( SELECT @pv:=(SELECT GROUP_CONCAT(id SEPARATOR ',') FROM {$table} WHERE parent IN (@pv)) AS lv FROM {$table} JOIN (SELECT @pv:=%d)tmp WHERE parent IN (@pv)) a))", $folder_id ) ) );

}

/**
 * Return root parent of the given child folder.
 *
 * @param $child_id
 *
 * @return string|null
 * @since BuddyBoss 1.3.6
 */
function bp_document_get_root_parent_id( $child_id ) {

	global $bp, $wpdb;

	$table     = $bp->document->table_name_folder;
	$parent_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT f.id
FROM (
    SELECT @id AS _id, (SELECT @id := parent FROM {$table} WHERE id = _id)
    FROM (SELECT @id := %d) tmp1
    JOIN {$table} ON @id <> 0
    ) tmp2
JOIN {$table} f ON tmp2._id = f.id
WHERE f.parent = 0",
			$child_id
		)
	);

	return $parent_id;
}
