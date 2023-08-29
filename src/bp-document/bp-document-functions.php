<?php
/**
 * BuddyBoss Document Functions.
 * Functions are where all the magic happens in BuddyBoss. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 *
 * @since   BuddyBoss 1.4.0
 * @package BuddyBoss\Document\Functions
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Meta *********************************************************************/

/**
 * Delete a meta entry from the DB for an document feed item.
 *
 * @since BuddyBoss 1.4.0
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
 *
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
 * @since BuddyBoss 1.4.0
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
	 * @since BuddyBoss 1.4.0
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
 * @since BuddyBoss 1.4.0
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
 * @since BuddyBoss 1.4.0
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
 * @since BuddyBoss 1.4.0
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
		$retval = delete_metadata( 'folder', $folder_id, $key, $meta_value, $delete_all );
	}
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get metadata for a given document folder item.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param int    $folder_id   ID of the document folder item whose metadata is being requested.
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
	$retval = get_metadata( 'folder', $folder_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	/**
	 * Filters the metadata for a specified document folder item.
	 *
	 * @since BuddyBoss 1.4.0
	 *
	 * @param mixed  $retval    The meta values for the document folder item.
	 * @param int    $folder_id ID of the document folder item.
	 * @param string $meta_key  Meta key for the value being requested.
	 * @param bool   $single    Whether to return one matched meta key row or all.
	 */
	return apply_filters( 'bp_document_folder_get_meta', $retval, $folder_id, $meta_key, $single );
}

/**
 * Update a piece of document folder meta.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param int    $folder_id   ID of the document folder item whose metadata is being updated.
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
	$retval = update_metadata( 'folder', $folder_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of document folder metadata.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param int    $folder_id   ID of the document folder item.
 * @param string $meta_key    Metadata key.
 * @param mixed  $meta_value  Metadata value.
 * @param bool   $unique      Optional. Whether to enforce a single metadata value for the
 *                            given key. If true, and the object already has a value for
 *                            the key, no change will be made. Default: false.
 * @return int|bool The meta ID on successful update, false on failure.
 */
function bp_document_folder_add_meta( $folder_id, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = add_metadata( 'folder', $folder_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get file document upload max size
 *
 * @param bool $post_string
 *
 * @return string
 * @since BuddyBoss 1.4.0
 */
function bp_document_file_upload_max_size() {

	/**
	 * Filters doucment file upload max limit.
	 *
	 * @param mixed $max_size document upload max limit.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	return apply_filters( 'bp_document_file_upload_max_size', bp_media_allowed_upload_document_size() );
}

/**
 * Format file size units.
 *
 * @param           $bytes
 * @param bool   $post_string
 * @param string $type
 *
 * @return string
 * @since BuddyBoss 1.4.0
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
 * @since BuddyBoss 1.4.0
 * @see   BP_Document::get() For more information on accepted arguments
 *        and the format of the returned value.
 */
function bp_document_get( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'max'                 => false,        // Maximum number of results to return.
			'fields'              => 'all',
			'page'                => 1,            // Page 1 without a per_page will result in no pagination.
			'per_page'            => false,        // results per page.
			'sort'                => 'DESC',       // sort ASC or DESC.
			'order_by'            => false,        // order by.
			'scope'               => false,

			// want to limit the query.
			'user_id'             => false,
			'activity_id'         => false,
			'folder_id'           => false,
			'group_id'            => false,
			'search_terms'        => false,        // Pass search terms as a string.
			'privacy'             => false,        // privacy of document.
			'exclude'             => false,        // Comma-separated list of activity IDs to exclude.
			'count_total'         => false,
			'user_directory'      => true,

			'meta_query_document' => false,         // Filter by activity meta. See WP_Meta_Query for format.
			'meta_query_folder'   => false,          // Filter by activity meta. See WP_Meta_Query for format.
			'moderation_query'    => true,         // Filter for exclude moderation query.
		),
		'document_get'
	);

	$document = BP_Document::documents(
		array(
			'page'                => $r['page'],
			'per_page'            => $r['per_page'],
			'user_id'             => $r['user_id'],
			'activity_id'         => $r['activity_id'],
			'folder_id'           => $r['folder_id'],
			'group_id'            => $r['group_id'],
			'max'                 => $r['max'],
			'sort'                => $r['sort'],
			'order_by'            => $r['order_by'],
			'search_terms'        => $r['search_terms'],
			'scope'               => $r['scope'],
			'privacy'             => $r['privacy'],
			'exclude'             => $r['exclude'],
			'count_total'         => $r['count_total'],
			'fields'              => $r['fields'],
			'user_directory'      => $r['user_directory'],
			'meta_query_document' => $r['meta_query_document'],
			'meta_query_folder'   => $r['meta_query_folder'],
			'moderation_query'    => $r['moderation_query'],
		)
	);

	/**
	 * Filters the requested document item(s).
	 *
	 * @param BP_Document $document Requested document object.
	 * @param array       $r        Arguments used for the document query.
	 *
	 * @since BuddyBoss 1.4.0
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
 * @since BuddyBoss 1.4.0
 * @see   BP_Document::get() For more information on accepted arguments.
 */
function bp_document_get_specific( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'document_ids'     => false,      // A single document_id or array of IDs.
			'max'              => false,      // Maximum number of results to return.
			'page'             => 1,          // Page 1 without a per_page will result in no pagination.
			'per_page'         => false,      // Results per page.
			'sort'             => 'DESC',     // Sort ASC or DESC.
			'order_by'         => false,      // Sort ASC or DESC.
			'folder_id'        => false,      // Sort ASC or DESC.
			'folder'           => false,
			'meta_query'       => false,
			'privacy'          => false,      // privacy to filter.
			'moderation_query' => true,
		),
		'document_get_specific'
	);

	$get_args = array(
		'in'               => $r['document_ids'],
		'max'              => $r['max'],
		'page'             => $r['page'],
		'per_page'         => $r['per_page'],
		'sort'             => $r['sort'],
		'order_by'         => $r['order_by'],
		'folder_id'        => $r['folder_id'],
		'folder'           => $r['folder'],
		'privacy'          => $r['privacy'],      // privacy to filter.
		'meta_query'       => $r['meta_query'],
		'moderation_query' => $r['moderation_query'],
	);

	/**
	 * Filters the requested specific document item.
	 *
	 * @param BP_Document $document Requested document object.
	 * @param array       $args     Original passed in arguments.
	 * @param array       $get_args Constructed arguments used with request.
	 *
	 * @since BuddyBoss 1.4.0
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
 * @since BuddyBoss 1.4.0
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
			'message_id'    => false,                   // The ID of message.
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
	$document->message_id    = (int) $r['message_id'];
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
	update_post_meta( $document->attachment_id, 'bp_document_id', $document->id );

	/**
	 * Fires at the end of the execution of adding a new document item, before returning the new document item ID.
	 *
	 * @param object $document document object.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	do_action( 'bp_document_add', $document );

	return $document->id;
}

/**
 * Document add handler function
 *
 * @param array  $documents
 * @param string $privacy
 * @param string $content
 * @param int    $group_id
 * @param int    $folder_id
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.0
 */
function bp_document_add_handler( $documents = array(), $privacy = 'public', $content = '', $group_id = false, $folder_id = false ) {
	global $bp_document_upload_count, $bp_document_upload_activity_content;

	$document_ids = array();
	$privacy      = in_array( $privacy, array_keys( bp_document_get_visibility_levels() ) ) ? $privacy : 'public';
	$document_id  = 0;

	if ( ! empty( $documents ) && is_array( $documents ) ) {

		// update count of documents for later use.
		$bp_document_upload_count = count( $documents );

		// update the content of medias for later use.
		$bp_document_upload_activity_content = $content;

		// save document.
		foreach ( $documents as $document ) {

			// Update document if existing.
			if ( ! empty( $document['document_id'] ) ) {

				$bp_document = new BP_Document( $document['document_id'] );

				if ( ! empty( $bp_document->id ) ) {
					$document_id = bp_document_add(
						array(
							'id'            => $bp_document->id,
							'blog_id'       => $bp_document->blog_id,
							'attachment_id' => $bp_document->attachment_id,
							'user_id'       => $bp_document->user_id,
							'title'         => $bp_document->title,
							'folder_id'     => ! empty( $document['folder_id'] ) ? $document['folder_id'] : $folder_id,
							'group_id'      => ! empty( $document['group_id'] ) ? $document['group_id'] : $group_id,
							'activity_id'   => $bp_document->activity_id,
							'message_id'    => $bp_document->message_id,
							'privacy'       => $bp_document->privacy,
							'menu_order'    => ! empty( $document['menu_order'] ) ? $document['menu_order'] : false,
							'date_modified' => bp_core_current_time(),
						)
					);

					$file      = get_attached_file( $bp_document->attachment_id );
					$file_type = wp_check_filetype( $file );
					$file_name = basename( $file );

					if ( ! empty( $document_id ) && ! is_wp_error( $document_id ) ) {
						bp_document_update_meta( $document_id, 'file_name', $file_name );
						bp_document_update_meta( $document_id, 'extension', '.' . $file_type['ext'] );
					}
				}
			} else {
				$file      = get_attached_file( $document['id'] );
				$file_type = wp_check_filetype( $file );
				$file_name = basename( $file );

				$document_id = bp_document_add(
					array(
						'attachment_id' => $document['id'],
						'title'         => $document['name'],
						'folder_id'     => ! empty( $document['folder_id'] ) ? $document['folder_id'] : $folder_id,
						'group_id'      => ! empty( $document['group_id'] ) ? $document['group_id'] : $group_id,
						'privacy'       => ! empty( $document['privacy'] ) && in_array( $document['privacy'], array_merge( array_keys( bp_document_get_visibility_levels() ), array( 'message' ) ) ) ? $document['privacy'] : $privacy,
						'menu_order'    => ! empty( $document['menu_order'] ) ? $document['menu_order'] : 0,
					)
				);

				if ( ! empty( $document_id ) && ! is_wp_error( $document_id ) ) {
					bp_document_update_meta( $document_id, 'file_name', $file_name );
					bp_document_update_meta( $document_id, 'extension', '.' . $file_type['ext'] );

					// save document is saved in attachment.
					update_post_meta( $document['id'], 'bp_document_saved', true );
				}
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
	 * @since BuddyBoss 1.4.0
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
 * @since BuddyBoss 1.4.0
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
	 * @since BuddyBoss 1.4.0
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
	 * @since BuddyBoss 1.4.0
	 */
	do_action( 'bp_document_delete', $args );

	/**
	 * Fires after the document item has been deleted.
	 *
	 * @param array $document_ids_deleted Array of affected document item IDs.
	 *
	 * @since BuddyBoss 1.4.0
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
 * @since BuddyBoss 1.4.0
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
	 * @since BuddyBoss 1.4.0
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
 * @since BuddyBoss 1.4.0
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
	 * @since BuddyBoss 1.4.0
	 */
	return apply_filters( 'bp_document_get_document_activity', $result['activities'][0] );
}

/**
 * Get the document count of a given user.
 *
 * @param int $user_id ID of the user whose document are being counted.
 *
 * @return int document count of the user.
 * @since BuddyBoss 1.4.0
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
	 * @since BuddyBoss 1.4.0
	 */
	return apply_filters( 'bp_document_get_total_document_count', (int) $count );
}

/**
 * Get the document count of a given group.
 *
 * @param int $group_id ID of the group whose document are being counted.
 *
 * @return int document count of the group.
 * @since BuddyBoss 1.4.0
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
	 * @since BuddyBoss 1.4.0
	 */
	return apply_filters( 'bp_document_get_total_group_document_count', (int) $count );
}

/**
 * Get the folder count of a given group.
 *
 * @param int $group_id ID of the group whose folder are being counted.
 *
 * @return int folder count of the group.
 * @since BuddyBoss 1.4.0
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
	 * @since BuddyBoss 1.4.0
	 */
	return apply_filters( 'bp_document_get_total_group_folder_count', (int) $count );
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
 * @since BuddyBoss 1.4.0
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

			'search_terms' => false,                    // Pass search terms as a string.
			'exclude'      => false,                    // Comma-separated list of folder IDs to exclude.
			'include'      => false,                    // Comma-separated list of folder IDs to include.
			'parent'       => null,                    // Parent folder ID.
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
			'in'           => $r['include'],
			'parent'       => $r['parent'],
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
	 * @since BuddyBoss 1.4.0
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
 * @since BuddyBoss 1.4.0
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
 * @since BuddyBoss 1.4.0
 */
function bp_folder_add( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'id'            => false, // Pass an existing folder ID to update an existing entry.
			'user_id'       => bp_loggedin_user_id(),   // User ID.
			'blog_id'       => get_current_blog_id(),   // Blog ID.
			'group_id'      => false,
			'title'         => '',  // title of folder being added.
			'privacy'       => 'public',    // Optional: privacy of the document e.g. public.
			'date_created'  => bp_core_current_time(),  // The GMT time that this document was recorded.
			'date_modified' => bp_core_current_time(),  // The GMT time that this document was updated.
			'error_type'    => 'bool',
			'parent'        => 0,
		),
		'folder_add'
	);

	// Setup document to be added.
	$folder                = new BP_Document_Folder( $r['id'] );
	$folder->user_id       = (int) $r['user_id'];
	$folder->group_id      = (int) $r['group_id'];
	$folder->blog_id       = (int) $r['blog_id'];
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
	 * @since BuddyBoss 1.4.0
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
 * @since BuddyBoss 1.4.0
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
	 * @since BuddyBoss 1.4.0
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
	 * @since BuddyBoss 1.4.0
	 */
	do_action( 'bp_folder_delete', $args );

	/**
	 * Fires after the folder item has been deleted.
	 *
	 * @param array $folder_ids_deleted Array of affected folder item IDs.
	 *
	 * @since BuddyBoss 1.4.0
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
 * @since BuddyBoss 1.4.0
 */
function folders_get_folder( $folder_id ) {

	$folder = new BP_Document_Folder( $folder_id );

	/**
	 * Filters a single folder object.
	 *
	 * @param BP_Document_Folder $folder Single folder object.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	return apply_filters( 'folders_get_folder', $folder );
}

/**
 * Check folder access for current user or guest
 *
 * @param $folder_id
 *
 * @return bool
 * @since BuddyBoss 1.4.0
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
 * @since BuddyBoss 1.4.0
 */
function bp_document_delete_orphaned_attachments() {

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
				'key'   => 'bp_document_saved',
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

	$document_wp_query = new WP_query( $args );
	if ( 0 < $document_wp_query->found_posts ) {
		foreach ( $document_wp_query->posts as $post_id ) {
			wp_delete_attachment( $post_id, true );
		}
	}

	wp_reset_postdata();
	wp_reset_query();

	add_filter( 'posts_join', 'bp_media_filter_attachments_query_posts_join', 10, 2 );
	add_filter( 'posts_where', 'bp_media_filter_attachments_query_posts_where', 10, 2 );

	bb_document_remove_orphaned_download();
}

/**
 * Download an image from the specified URL and attach it to a post.
 *
 * @param string $file The URL of the image to download
 *
 * @return int|void
 * @since BuddyBoss 1.4.0
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
 * @since BuddyBoss 1.4.0
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

	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
	}

	if ( ! is_wp_error( $id ) ) {
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
	}

	return $id;
}

/**
 * Create and upload the document file.
 *
 * @return array|null|WP_Error|WP_Post
 * @since BuddyBoss 1.4.0
 */
function bp_document_upload() {
	/**
	 * Make sure user is logged in.
	 */
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'not_logged_in', __( 'Please login in order to upload file document.', 'buddyboss' ), array( 'status' => 500 ) );
	}

	do_action( 'bb_before_document_upload_handler' );

	$attachment = bp_document_upload_handler();

	do_action( 'bb_after_document_upload_handler' );

	if ( is_wp_error( $attachment ) ) {
		return $attachment;
	}

	do_action( 'bb_document_upload', $attachment );

	// get saved document id.
	$document_id = (int) get_post_meta( $attachment->ID, 'bp_document_id', true );

	// Generate document attachment preview link.
	$attachment_id   = 'forbidden_' . $attachment->ID;
	$attachment_url  = home_url( '/' ) . 'bb-attachment-document-preview/' . base64_encode( $attachment_id );
	$attachment_file = get_attached_file( $attachment->ID );
	$attachment_size = is_file( $attachment_file ) ? bp_document_size_format( filesize( get_attached_file( $attachment->ID ) ) ) : 0;

	if (
		! empty( $document_id ) &&
		(
			bp_is_group_messages() ||
			bp_is_messages_component() ||
			(
				! empty( $_POST['component'] ) &&
				'messages' === $_POST['component']
			)
		)
	) {
		$attachment_url = bp_document_get_preview_url( $document_id, $attachment->ID );
		$extension      = bp_document_extension( $attachment->ID );

		if ( in_array( $extension, bp_get_document_preview_video_extensions(), true ) ) {
			$attachment_url = bb_document_video_get_symlink( $document_id, true );
		}

		if ( empty( $attachment_url ) ) {
			$attachment_url = bp_document_get_preview_url( $document_id, $attachment->ID );
		}

	}

	if ( 0 === $attachment_size ) {

		if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		}

		$file_system_direct = new WP_Filesystem_Direct( false );
		$attachment_size    = bp_document_size_format( $file_system_direct->size( $attachment_file ) );
	}

	$extension = bp_document_extension( $attachment->ID );
	$svg_icon  = bp_document_svg_icon( $extension, $attachment->ID );

	$result = array(
		'id'                => (int) $attachment->ID,
		'url'               => esc_url( untrailingslashit( $attachment_url ) ),
		'name'              => esc_attr( pathinfo( basename( $attachment_file ), PATHINFO_FILENAME ) ),
		'full_name'         => esc_attr( basename( $attachment_file ) ),
		'type'              => esc_attr( 'document' ),
		'size'              => $attachment_size,
		'extension'         => $extension,
		'svg_icon'          => $svg_icon,
		'svg_icon_download' => bp_document_svg_icon( 'download' ),
		'text'              => bp_document_mirror_text( $attachment->ID ),
	);

	return $result;
}

/**
 * Document upload handler.
 *
 * @param string $file_id Index of the `$_FILES` array that the file was sent. Required.
 *
 * @return array|int|null|WP_Error|WP_Post
 * @since BuddyBoss 1.4.0
 */
function bp_document_upload_handler( $file_id = 'file' ) {

	/**
	 * Include required files.
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
	bb_document_add_upload_filters();

	// Register image sizes.
	bb_document_register_image_sizes();

	$aid = media_handle_upload(
		$file_id,
		0,
		array(),
		array(
			'test_form'            => false,
			'upload_error_strings' => array(
				false,
				__( 'The uploaded file exceeds ', 'buddyboss' ) . bp_document_file_upload_max_size(),
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
	bb_document_deregister_image_sizes();

	// Remove upload filters.
	bb_document_remove_upload_filters();

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
 * @since BuddyBoss 1.4.0
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
 * @param int $attachment_id Attachment id.
 *
 * @return mixed|string
 * @since BuddyBoss 1.4.0
 */
function bp_document_extension( $attachment_id ) {

	$file_url  = wp_get_attachment_url( $attachment_id );
	$file_type = wp_check_filetype( $file_url );
	$extension = trim( $file_type['ext'] );

	if ( '' === $extension ) {
		$file      = pathinfo( $file_url );
		$extension = ( isset( $file['extension'] ) ) ? $file['extension'] : '';
	}

	return strtok( $extension, '?' );

}

/**
 * Return the extension of the attachment.
 *
 * @param int $attachment_id Attachment id.
 *
 * @return mixed|string
 * @since BuddyBoss 1.4.0
 */
function bp_document_mime_type( $attachment_id ) {

	$type = get_post_mime_type( $attachment_id );

	return $type;

}

function bp_document_multi_array_search( $array, $search ) {

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

/**
 * Return the icon based on the extension.
 *
 * @param $extension
 * @param $attachment_id
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.0
 */
function bp_document_svg_icon( $extension, $attachment_id = 0, $type = 'font' ) {

	if ( $attachment_id > 0 && '' !== $extension ) {
		$mime_type     = bp_document_mime_type( $attachment_id );
		$existing_list = bp_document_extensions_list();
		$new_extension = '.' . $extension;
		$result_array  = bp_document_multi_array_search(
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
					$svg_icons = array_column( bp_document_svg_icon_list(), 'svg', 'icon' );
					$icon      = isset( $svg_icons[ $icon ] ) ? $svg_icons[ $icon ] : '';
				}

				return apply_filters( 'bp_document_svg_icon', $icon, $extension );
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
				'font' => 'bb-icon-file-archive',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-7z</title><path d="M13.728 0.032c0.992 0 1.984 0.384 2.72 1.056l0.16 0.16 6.272 6.496c0.672 0.672 1.056 1.6 1.12 2.528v17.76c0 2.144-1.696 3.904-3.808 4h-16.192c-2.144 0-3.904-1.664-4-3.808v-24.192c0-2.144 1.696-3.904 3.808-4h9.92zM13.728 2.048h-9.728c-1.056 0-1.92 0.8-1.984 1.824v24.16c0 1.056 0.8 1.92 1.824 2.016h16.16c1.056 0 1.92-0.832 1.984-1.856l0.032-0.16v-17.504c0-0.48-0.16-0.896-0.448-1.28l-0.128-0.128-6.272-6.464c-0.384-0.416-0.896-0.608-1.44-0.608zM16.992 14.528c0.576 0 1.024 0.448 1.024 0.992 0 0.512-0.416 0.96-0.896 0.992l-0.128 0.032v0.512l-1.984 0.992v1.184l1.984-0.992v-1.184h0.032v1.184h-0.032v0.832l-1.984 0.992v1.152l1.984-0.992v-1.152h0.032v1.152h-0.032v0.832l-1.984 0.992v1.184l1.984-0.992v-1.184h0.032v1.184h-0.032v2.304h0.128c0.48 0.064 0.896 0.48 0.896 0.992s-0.416 0.928-0.896 0.992h-1.984c-0.544 0-0.992-0.448-0.992-0.992 0-0.512 0.384-0.928 0.864-0.992v-0.704l-2.592 0.672c-0.096 0.032-0.192 0.032-0.256 0-0.064 0.032-0.096 0.032-0.16 0.032h-5.984c-0.576 0-1.024-0.448-1.024-1.024v-5.984c0-0.544 0.448-0.992 1.024-0.992h5.984c0.096 0 0.16 0 0.256 0.032h0.16l2.592 0.704v-0.768c-0.48-0.064-0.864-0.48-0.864-0.992s0.384-0.928 0.864-0.992h1.984zM12 17.536h-5.984v5.984h5.984v-5.984zM10.496 21.344c0.288 0 0.512 0.224 0.512 0.512s-0.224 0.512-0.512 0.512h-3.008c-0.256 0-0.48-0.224-0.48-0.512s0.224-0.512 0.48-0.512h3.008zM10.496 18.752c0.288 0 0.512 0.224 0.512 0.512 0 0.256-0.224 0.48-0.512 0.48h-3.008c-0.256 0-0.48-0.224-0.48-0.48 0-0.288 0.224-0.512 0.48-0.512h3.008z"></path></svg>',
			);
			break;
		case 'abw':
			$svg = array(
				'font' => 'bb-icon-file-abw',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-abw</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM15.2 15.008c1.28 0 2.304 1.024 2.304 2.304v0 7.392c0 1.248-1.024 2.304-2.304 2.304v0h-7.392c-1.28 0-2.304-1.056-2.304-2.304v0-7.392c0-1.28 1.024-2.304 2.304-2.304v0h7.392zM15.2 15.936h-7.392c-0.768 0-1.376 0.608-1.376 1.376v0 7.392c0 0.736 0.608 1.376 1.376 1.376v0h7.392c0.768 0 1.376-0.64 1.376-1.376v0-7.392c0-0.768-0.608-1.376-1.376-1.376v0zM9.056 22.176c0.544 0 0.864 0.256 0.864 0.704 0 0.288-0.192 0.576-0.48 0.608v0 0.032c0.352 0.032 0.64 0.32 0.64 0.672 0 0.48-0.384 0.8-0.992 0.8v0h-1.248v-2.816h1.216zM11.040 22.176l0.448 1.984h0.032l0.512-1.984h0.512l0.512 1.984h0.032l0.416-1.984h0.64l-0.768 2.816h-0.544l-0.544-1.856h-0.032l-0.512 1.856h-0.576l-0.736-2.816h0.608zM8.928 23.744h-0.512v0.8h0.544c0.32 0 0.512-0.128 0.512-0.416 0-0.256-0.192-0.384-0.544-0.384v0zM8.928 22.624h-0.512v0.736h0.448c0.32 0 0.48-0.128 0.48-0.384 0-0.224-0.16-0.352-0.416-0.352v0zM15.008 19.488c0.256 0 0.48 0.224 0.48 0.512 0 0.256-0.16 0.448-0.384 0.48l-0.096 0.032h-3.008c-0.288 0-0.512-0.224-0.512-0.512 0-0.256 0.192-0.448 0.416-0.48l0.096-0.032h3.008zM9.312 17.184l0.96 2.816h-0.64l-0.192-0.672h-0.992l-0.224 0.672h-0.608l0.992-2.816h0.704zM8.96 17.76h-0.032l-0.352 1.12h0.736l-0.352-1.12zM15.008 17.504c0.256 0 0.48 0.224 0.48 0.48s-0.16 0.48-0.384 0.512h-3.104c-0.288 0-0.512-0.224-0.512-0.512 0-0.224 0.192-0.448 0.416-0.48h3.104z"></path></svg>',
			);
			break;
		case 'ace':
			$svg = array(
				'font' => 'bb-icon-file-ace',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-ace</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM8.384 23.936v0.576h-1.472v0.768h1.408v0.544h-1.408v0.768h1.472v0.608h-2.144v-3.264h2.144zM17.536 25.216c0.256 0 0.48 0.224 0.48 0.48v0.928c0 0.256-0.224 0.448-0.48 0.448h-6.464c-0.256 0-0.448-0.192-0.448-0.448v-0.928c0-0.256 0.192-0.48 0.448-0.48h6.464zM17.536 21.536c0.256 0 0.48 0.192 0.48 0.448v0.928c0 0.256-0.224 0.48-0.48 0.48h-6.464c-0.256 0-0.448-0.224-0.448-0.48v-0.928c0-0.256 0.192-0.448 0.448-0.448h6.464zM7.616 19.872c0.768 0 1.376 0.512 1.408 1.216v0h-0.672c-0.064-0.352-0.352-0.608-0.736-0.608-0.512 0-0.832 0.416-0.832 1.12 0 0.672 0.32 1.088 0.832 1.088 0.384 0 0.672-0.224 0.736-0.576v0h0.672c-0.064 0.704-0.64 1.184-1.408 1.184-0.928 0-1.536-0.64-1.536-1.696s0.576-1.728 1.536-1.728zM17.536 17.856c0.256 0 0.48 0.192 0.48 0.448v0.928c0 0.256-0.224 0.448-0.48 0.448h-6.464c-0.256 0-0.448-0.192-0.448-0.448v-0.928c0-0.256 0.192-0.448 0.448-0.448h6.464zM7.936 16l1.12 3.264h-0.736l-0.256-0.8h-1.12l-0.256 0.8h-0.672l1.12-3.264h0.8zM7.52 16.672h-0.032l-0.416 1.28h0.864l-0.416-1.28z"></path></svg>',
			);
			break;
		case 'ai':
			$svg = array(
				'font' => 'bb-icon-file-ai',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-ai</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.504 25.056c0.288 0 0.512 0.224 0.512 0.512v0.928c0 0.288-0.224 0.512-0.512 0.512h-11.008c-0.256 0-0.48-0.224-0.48-0.512v-0.928c0-0.288 0.224-0.512 0.48-0.512h11.008zM9.792 16l2.176 6.144h-1.44l-0.48-1.472h-2.208l-0.48 1.472h-1.344l2.208-6.144h1.568zM14.368 16v6.144h-1.312v-6.144h1.312zM8.992 17.28h-0.064l-0.8 2.4h1.664l-0.8-2.4z"></path></svg>',
			);
			break;
		case 'apk':
			$svg = array(
				'font' => 'bb-icon-file-apk',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-apk</title><path d="M13.728 0c1.088 0 2.144 0.448 2.88 1.216v0l6.272 6.496c0.736 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 2.016h-9.728c-1.088 0-1.984 0.864-1.984 1.984v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.192-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.608-1.44-0.608v0zM17.408 21.696v2.976c0 1.248-0.96 2.24-2.144 2.336h-6.56c-1.216 0-2.208-0.96-2.304-2.176v-3.136h11.008zM16.48 22.624h-9.152v2.048c0 0.672 0.48 1.248 1.12 1.376l0.128 0.032h6.528c0.704 0 1.312-0.576 1.376-1.28v-2.176zM11.904 15.008c1.568 0 3.008 0.672 4 1.728l1.312-1.568c0.16-0.192 0.448-0.224 0.64-0.064s0.224 0.448 0.064 0.672l-1.44 1.728c0.544 0.8 0.864 1.76 0.896 2.784l0.032 0.288v0.48h-11.008v-0.48c0-1.152 0.352-2.24 0.928-3.104l-1.216-1.728c-0.16-0.224-0.128-0.512 0.096-0.64 0.16-0.16 0.416-0.128 0.576 0.032l0.064 0.064 1.088 1.504c0.992-1.056 2.4-1.696 3.968-1.696zM11.904 15.936c-1.44 0-2.72 0.672-3.552 1.696-0.032 0.096-0.096 0.16-0.16 0.224 0 0 0 0 0 0-0.384 0.544-0.672 1.184-0.8 1.856l-0.032 0.256-0.032 0.16h9.12v-0.16c-0.096-0.704-0.352-1.376-0.736-1.952l-0.128-0.224-0.032-0.032c-0.032 0-0.032-0.032-0.064-0.064-0.736-0.96-1.856-1.632-3.136-1.76h-0.448zM14.24 18.24c0.256 0 0.48 0.224 0.48 0.48s-0.224 0.448-0.48 0.448c-0.256 0-0.448-0.192-0.448-0.448s0.192-0.48 0.448-0.48zM9.664 18.24c0.256 0 0.48 0.224 0.48 0.48s-0.224 0.448-0.48 0.448c-0.256 0-0.448-0.192-0.448-0.448s0.192-0.48 0.448-0.48z"></path></svg>',
			);
			break;
		case 'css':
			$svg = array(
				'font' => 'bb-icon-file-css',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-css</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM7.808 20v0.832h-0.192c-0.48 0-0.64 0.192-0.64 0.736v0 0.896c0 0.544-0.352 0.896-1.024 0.96v0 0.128c0.672 0.064 1.024 0.416 1.024 0.96v0 0.928c0 0.512 0.16 0.736 0.64 0.736v0h0.192v0.832h-0.288c-1.152 0-1.632-0.448-1.632-1.472v0-0.736c0-0.544-0.224-0.768-0.896-0.768v0-1.088c0.672 0 0.896-0.224 0.896-0.768v0-0.736c0-1.024 0.48-1.44 1.632-1.44v0h0.288zM16.16 20c1.12 0 1.6 0.416 1.6 1.44v0 0.736c0 0.544 0.256 0.768 0.896 0.768v0 1.088c-0.64 0-0.896 0.224-0.896 0.768v0 0.736c0 1.024-0.48 1.472-1.6 1.472v0h-0.288v-0.832h0.16c0.48 0 0.672-0.224 0.672-0.736v0-0.928c0-0.544 0.352-0.896 0.992-0.96v0-0.128c-0.64-0.064-0.992-0.416-0.992-0.96v0-0.896c0-0.544-0.192-0.736-0.672-0.736v0h-0.16v-0.832h0.288zM9.664 24.608c0.416 0 0.672 0.256 0.672 0.64s-0.256 0.672-0.672 0.672c-0.416 0-0.672-0.288-0.672-0.672s0.256-0.64 0.672-0.64zM11.84 24.608c0.416 0 0.672 0.256 0.672 0.64s-0.256 0.672-0.672 0.672c-0.416 0-0.672-0.288-0.672-0.672s0.256-0.64 0.672-0.64zM13.984 24.608c0.416 0 0.672 0.256 0.672 0.64s-0.256 0.672-0.672 0.672c-0.416 0-0.672-0.288-0.672-0.672s0.256-0.64 0.672-0.64z"></path></svg>',
			);
			break;
		case 'csv':
			$svg = array(
				'font' => 'bb-icon-file-csv',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-csv</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016zM13.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016zM9.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-3.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h3.008zM17.504 20.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016zM13.504 20.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016zM7.424 16.992l0.512 1.312h0.064l0.64-1.312h1.344l-1.216 2.432 1.248 2.56h-1.376l-0.64-1.28h-0.064l-0.64 1.28h-1.28l1.152-2.432-1.152-2.56h1.408zM17.504 16.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016zM13.504 16.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016z"></path></svg>',
			);
			break;
		case 'doc':
			$svg = array(
				'font' => 'bb-icon-file-doc',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-doc</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16 13.984c1.376 0 2.496 1.12 2.496 2.528v0 8c0 1.376-1.12 2.496-2.496 2.496v0h-8c-1.376 0-2.496-1.12-2.496-2.496v0-8c0-1.408 1.12-2.528 2.496-2.528v0h8zM16 15.008h-8c-0.832 0-1.504 0.672-1.504 1.504v0 8c0 0.8 0.672 1.472 1.504 1.472v0h8c0.832 0 1.504-0.672 1.504-1.472v0-8c0-0.832-0.672-1.504-1.504-1.504v0zM10.944 24l1.056-3.744h0.064l1.056 3.744h1.12l1.504-5.632h-1.216l-0.896 3.968h-0.064l-1.024-3.968h-0.992l-1.024 3.968h-0.064l-0.896-3.968h-1.216l1.472 5.632h1.12z"></path></svg>',
			);
			break;
		case 'docm':
			$svg = array(
				'font' => 'bb-icon-file-docm',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-docm</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.024 14.4l-0.48 2.176v9.12c0 1.024-0.8 1.888-1.792 1.952v0h-7.84v-11.296c0-1.088 0.864-1.952 1.952-1.952v0h8.16zM15.776 15.392h-6.912c-0.544 0-0.96 0.448-0.96 0.96v0 10.304h6.688c0.48 0 0.896-0.384 0.96-0.864v0-9.376l0.224-1.024zM14.24 24.928c-0.032 0.928-0.032 1.824 0.576 2.24 0.096 0.096-0.096 0.256-0.576 0.48v0h-7.52c-1.12-0.032-1.12-1.312-1.088-2.72 1.856 0 7.008 0 8.384 0h0.224zM11.488 21.984c0.32 0 0.544 0.224 0.544 0.544s-0.224 0.544-0.544 0.544c-0.288 0-0.512-0.224-0.512-0.544s0.224-0.544 0.512-0.544zM11.904 17.344l-0.096 3.776h-0.64l-0.064-3.776h0.8zM16.864 14.4c1.472 0 1.248 2.016 1.28 2.784-0.48 0-1.792 0-2.592 0 0-0.736-0.16-2.784 1.312-2.784z"></path></svg>',
			);
			break;
		case 'docx':
			$svg = array(
				'font' => 'bb-icon-file-docx',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-docx</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM18.496 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-5.984c-0.288 0-0.512-0.224-0.512-0.512v-0.992c0-0.288 0.224-0.512 0.512-0.512h5.984zM12.928 15.008c0.096 0 0.192 0.064 0.192 0.192v0 3.616h1.696c0.064 0 0.096 0.032 0.128 0.064 0.096 0.064 0.096 0.192 0.032 0.256v0l-3.52 3.744c-0.032 0-0.032 0-0.032 0-0.192 0.192-0.512 0.192-0.704 0v0l-3.424-3.744c-0.032-0.032-0.032-0.064-0.032-0.128 0-0.096 0.096-0.192 0.192-0.192v0h1.632v-3.616c0-0.128 0.096-0.192 0.192-0.192v0h3.648z"></path></svg>',
			);
			break;
		case 'dotm':
			$svg = array(
				'font' => 'bb-icon-file-dotm',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-dotm</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-11.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h11.008zM12 15.008c2.496 0 4.512 2.016 4.512 4.48 0 2.496-2.016 4.512-4.512 4.512s-4.512-2.016-4.512-4.512c0-2.464 2.016-4.48 4.512-4.48zM10.656 18.4h-0.768l0.768 2.912h0.8l0.512-2.016h0.064l0.512 2.016h0.8l0.8-2.912h-0.768l-0.448 2.080h-0.032l-0.512-2.080h-0.736l-0.512 2.080h-0.064l-0.416-2.080z"></path></svg>',
			);
			break;
		case 'dotx':
			$svg = array(
				'font' => 'bb-icon-file-dotx',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-dotx</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM10.784 23.104c0.832 0 1.344 0.576 1.344 1.472 0 0.928-0.512 1.504-1.344 1.504s-1.376-0.576-1.376-1.504c0-0.896 0.544-1.472 1.376-1.472zM7.648 23.168c0.864 0 1.344 0.512 1.344 1.408s-0.48 1.408-1.344 1.408v0h-1.088v-2.816h1.088zM14.688 23.168v0.512h-0.832v2.304h-0.608v-2.304h-0.832v-0.512h2.272zM15.744 23.168l0.576 0.992h0.064l0.576-0.992h0.672l-0.928 1.408 0.896 1.408h-0.672l-0.608-0.928h-0.032l-0.608 0.928h-0.64l0.896-1.408-0.896-1.408h0.704zM10.784 23.616c-0.48 0-0.768 0.384-0.768 0.96 0 0.608 0.288 0.992 0.768 0.992 0.448 0 0.736-0.384 0.736-0.992 0-0.576-0.288-0.96-0.736-0.96zM7.552 23.68h-0.416v1.824h0.416c0.544 0 0.832-0.32 0.832-0.928 0-0.576-0.32-0.896-0.832-0.896v0zM17.504 19.008c0.288 0 0.512 0.224 0.512 0.48v1.024c0 0.256-0.224 0.48-0.512 0.48h-11.008c-0.256 0-0.48-0.224-0.48-0.48v-1.024c0-0.256 0.224-0.48 0.48-0.48h11.008zM13.504 15.008c0.288 0 0.512 0.224 0.512 0.48v1.024c0 0.256-0.224 0.48-0.512 0.48h-7.008c-0.256 0-0.48-0.224-0.48-0.48v-1.024c0-0.256 0.224-0.48 0.48-0.48h7.008z"></path></svg>',
			);
			break;
		case 'eps':
		case 'svg':
			$svg = array(
				'font' => 'bb-icon-file-svg',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-svg</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM13.088 15.648c0.16 0 0.288 0.128 0.32 0.288l0.032 0.064v0.64h3.808c0.128-0.192 0.32-0.352 0.576-0.384h0.096c0.416 0 0.768 0.32 0.768 0.736s-0.352 0.768-0.768 0.768c-0.288 0-0.544-0.16-0.672-0.416h-3.808v0.032c2.080 0.672 3.648 2.592 3.776 4.896h1.088c0.192 0 0.32 0.096 0.352 0.256v3.072c0 0.16-0.128 0.32-0.256 0.352h-3.072c-0.192 0-0.32-0.128-0.352-0.288v-3.072c0-0.16 0.096-0.288 0.256-0.32h0.992c-0.128-1.76-1.248-3.2-2.784-3.84v0.576c0 0.16-0.128 0.288-0.288 0.32l-0.064 0.032h-3.008c-0.16 0-0.32-0.128-0.352-0.288v-0.48c-1.376 0.672-2.368 2.048-2.496 3.68h1.088c0.16 0 0.288 0.096 0.352 0.256v3.072c0 0.16-0.128 0.32-0.288 0.352h-3.072c-0.16 0-0.32-0.128-0.352-0.288v-3.072c0-0.16 0.128-0.288 0.288-0.32h0.992c0.128-2.208 1.536-4.032 3.488-4.8v-0.128h-3.744c-0.096 0.224-0.288 0.352-0.544 0.384l-0.096 0.032c-0.416 0-0.768-0.352-0.768-0.768s0.352-0.736 0.768-0.736c0.288 0 0.544 0.16 0.64 0.384h3.744v-0.64c0-0.16 0.128-0.32 0.288-0.352h3.072zM7.968 22.944h-2.304v2.304h2.304v-2.304zM17.952 22.944h-2.304v2.304h2.304v-2.304zM12.736 16.352h-2.304v2.304h2.304v-2.304z"></path></svg>',
			);
			break;
		case 'gif':
			$svg = array(
				'font' => 'bb-icon-file-gif',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-gif</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM12 15.008c3.328 0 6.016 2.688 6.016 5.984 0 3.328-2.688 6.016-6.016 6.016s-5.984-2.688-5.984-6.016c0-3.296 2.656-5.984 5.984-5.984zM10.656 26.016c-0.032 0.16 0.064 0.32 0.224 0.352 0.32 0.064 0.672 0.096 1.024 0.096 0.16 0.032 0.288-0.096 0.288-0.256s-0.128-0.288-0.256-0.288c-0.32 0-0.64-0.032-0.96-0.096-0.128-0.032-0.288 0.064-0.32 0.192zM13.888 25.536c-0.288 0.128-0.608 0.224-0.896 0.288-0.16 0.032-0.256 0.16-0.224 0.32s0.16 0.256 0.32 0.224c0.352-0.064 0.672-0.16 1.024-0.32 0.128-0.032 0.192-0.192 0.128-0.352-0.064-0.128-0.224-0.224-0.352-0.16zM8.864 25.152c-0.096 0.128-0.064 0.288 0.064 0.384 0.288 0.192 0.608 0.352 0.928 0.512 0.128 0.064 0.32 0 0.352-0.16 0.064-0.128 0-0.288-0.128-0.352-0.288-0.128-0.576-0.288-0.832-0.448-0.128-0.096-0.32-0.064-0.384 0.064zM15.424 24.512c-0.224 0.224-0.448 0.416-0.736 0.608-0.128 0.064-0.16 0.256-0.064 0.384 0.064 0.128 0.256 0.16 0.384 0.064 0.288-0.192 0.576-0.416 0.832-0.64 0.096-0.128 0.096-0.288 0-0.416-0.128-0.096-0.288-0.096-0.416 0zM7.488 23.584c-0.128 0.064-0.192 0.256-0.096 0.384 0.192 0.288 0.416 0.576 0.64 0.832 0.128 0.096 0.288 0.096 0.416 0 0.096-0.096 0.096-0.288 0-0.384-0.224-0.256-0.416-0.48-0.576-0.768-0.096-0.128-0.256-0.16-0.384-0.064zM16.544 22.88c-0.128 0.288-0.256 0.576-0.448 0.832-0.096 0.128-0.064 0.32 0.064 0.416 0.128 0.064 0.32 0.032 0.416-0.096 0.192-0.288 0.352-0.608 0.48-0.928 0.064-0.128 0-0.288-0.16-0.352-0.128-0.064-0.288 0-0.352 0.128zM6.912 21.696l-0.064 0.032c-0.16 0.032-0.256 0.16-0.224 0.32 0.064 0.352 0.16 0.672 0.288 0.992 0.064 0.16 0.224 0.224 0.384 0.16 0.128-0.064 0.192-0.224 0.128-0.352-0.096-0.288-0.192-0.608-0.256-0.896-0.032-0.128-0.096-0.192-0.192-0.224l-0.064-0.032zM10.208 19.712c-0.832 0-1.344 0.576-1.344 1.472 0 0.928 0.512 1.472 1.344 1.472 0.768 0 1.248-0.448 1.248-1.216v-0.352h-1.184v0.448h0.608v0.032c0 0.352-0.256 0.576-0.64 0.576-0.48 0-0.768-0.352-0.768-0.96s0.288-0.96 0.736-0.96c0.32 0 0.576 0.16 0.64 0.448h0.608c-0.096-0.576-0.576-0.96-1.248-0.96zM12.608 19.776h-0.576v2.816h0.576v-2.816zM15.136 19.776h-1.824v2.816h0.576v-1.088h1.152v-0.48h-1.152v-0.736h1.248v-0.512zM17.216 20.768c-0.16 0-0.288 0.128-0.288 0.256 0 0.32-0.032 0.64-0.096 0.96-0.032 0.16 0.064 0.288 0.224 0.32 0.128 0.032 0.288-0.064 0.32-0.224 0.064-0.32 0.096-0.672 0.096-1.024 0-0.16-0.128-0.288-0.256-0.288zM6.656 19.84c-0.096 0.32-0.128 0.672-0.128 1.024 0 0.16 0.128 0.288 0.256 0.288 0.16 0 0.288-0.128 0.288-0.256 0.032-0.32 0.064-0.64 0.128-0.96 0.032-0.128-0.064-0.288-0.224-0.32s-0.288 0.064-0.32 0.224zM16.672 18.72c-0.128 0.064-0.192 0.224-0.128 0.352 0.128 0.288 0.192 0.608 0.288 0.896 0.032 0.16 0.16 0.256 0.32 0.224s0.256-0.16 0.224-0.32c-0.096-0.352-0.192-0.672-0.32-1.024-0.064-0.128-0.224-0.192-0.384-0.128zM7.488 17.888c-0.192 0.288-0.384 0.608-0.512 0.928-0.064 0.128 0 0.288 0.16 0.352 0.128 0.064 0.288 0 0.352-0.128 0.128-0.288 0.288-0.576 0.448-0.832 0.096-0.128 0.064-0.288-0.064-0.384s-0.288-0.064-0.384 0.064zM15.488 17.152c-0.096 0.096-0.096 0.288 0 0.384 0.224 0.224 0.416 0.48 0.608 0.736 0.096 0.128 0.256 0.16 0.384 0.064 0.128-0.064 0.16-0.256 0.064-0.384-0.192-0.288-0.416-0.576-0.64-0.8-0.128-0.128-0.288-0.128-0.416 0zM9.088 16.352c-0.32 0.192-0.576 0.416-0.832 0.672-0.128 0.096-0.128 0.256-0.032 0.384 0.128 0.096 0.288 0.128 0.416 0 0.224-0.192 0.48-0.416 0.736-0.576 0.128-0.064 0.16-0.256 0.096-0.384-0.096-0.128-0.256-0.16-0.384-0.096zM13.696 16.064c-0.064 0.16 0.032 0.32 0.16 0.384 0.288 0.128 0.576 0.256 0.832 0.448 0.128 0.064 0.32 0.032 0.384-0.096 0.096-0.128 0.064-0.288-0.064-0.384-0.288-0.192-0.608-0.352-0.928-0.48-0.16-0.064-0.32 0-0.384 0.128zM11.072 15.616h-0.064c-0.352 0.064-0.704 0.16-1.024 0.288-0.128 0.064-0.224 0.224-0.16 0.352 0.064 0.16 0.224 0.224 0.384 0.16 0.288-0.096 0.576-0.192 0.896-0.256 0.16-0.032 0.256-0.16 0.224-0.32-0.032-0.128-0.096-0.192-0.192-0.224h-0.064zM12 15.52c-0.16 0-0.288 0.128-0.288 0.288s0.128 0.256 0.288 0.256c0.32 0 0.64 0.032 0.928 0.096 0.16 0.032 0.32-0.064 0.352-0.224 0.032-0.128-0.064-0.288-0.224-0.32-0.352-0.064-0.704-0.096-1.056-0.096z"></path></svg>',
			);
			break;
		case 'gz':
		case 'gzip':
		case 'zip':
			$svg = array(
				'font' => 'bb-icon-file-archive',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-zip</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h2.976v-1.984h2.016v-1.92h-2.016v-2.080h2.016v2.016h1.984v2.048h-1.984v1.92h11.008c1.056 0 1.92-0.832 1.984-1.856l0.032-0.16v-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM10.976 21.952v2.048h-1.984v-2.048h1.984zM11.008 16c0.544 0 0.992 0.448 0.992 0.992v3.008c0 0.544-0.448 0.992-0.992 0.992h-4c-0.544 0-0.992-0.448-0.992-0.992v-3.008c0-0.544 0.448-0.992 0.992-0.992h4zM10.592 16.992h-3.2c-0.192 0-0.352 0.128-0.384 0.32v1.28c0 0.192 0.128 0.352 0.32 0.384l0.064 0.032h3.2c0.192 0 0.352-0.16 0.416-0.32v-1.28c0-0.224-0.192-0.416-0.416-0.416z"></path></svg>',
			);
			break;
		case 'hlam':
			$svg = array(
				'font' => 'bb-icon-file-excel',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-hlam</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM18.016 24c0.256 0 0.48 0.224 0.48 0.512v0.992c0 0.256-0.224 0.48-0.48 0.48h-12c-0.288 0-0.512-0.224-0.512-0.48v-0.992c0-0.288 0.224-0.512 0.512-0.512h12zM14.56 15.648c0.128 0 0.224 0.064 0.288 0.192v0l0.704 1.216 0.672-1.216c0.064-0.096 0.16-0.16 0.256-0.192v0h1.824c0.288 0 0.448 0.288 0.32 0.512v0l-1.504 2.816 1.504 3.168c0.096 0.224-0.032 0.448-0.224 0.512v0h-1.856c-0.128 0-0.256-0.096-0.32-0.224v0l-0.672-1.472-0.672 1.472c-0.064 0.128-0.16 0.192-0.256 0.192v0l-0.064 0.032h-1.856c-0.256 0-0.416-0.288-0.288-0.544v0l1.824-3.136-1.664-2.784c-0.128-0.224 0-0.48 0.224-0.544v0h1.76zM11.008 20c0.256 0 0.48 0.224 0.48 0.512v0.992c0 0.256-0.224 0.48-0.48 0.48h-4.992c-0.288 0-0.512-0.224-0.512-0.48v-0.992c0-0.288 0.224-0.512 0.512-0.512h4.992zM14.336 16.352h-0.832l1.44 2.432c0.032 0.096 0.064 0.192 0.032 0.288v0l-0.032 0.064-1.632 2.816h1.024l0.896-1.984c0.128-0.256 0.448-0.288 0.608-0.096v0l0.032 0.096 0.896 1.984h0.992l-1.376-2.816c-0.032-0.096-0.032-0.16 0-0.256v0l0.032-0.064 1.312-2.464h-0.992l-0.864 1.6c-0.128 0.224-0.416 0.256-0.576 0.064v0l-0.032-0.064-0.928-1.6zM11.008 16c0.256 0 0.48 0.224 0.48 0.512v0.992c0 0.256-0.224 0.48-0.48 0.48h-4.992c-0.288 0-0.512-0.224-0.512-0.48v-0.992c0-0.288 0.224-0.512 0.512-0.512h4.992z"></path></svg>',
			);
			break;
		case 'hlsb':
			$svg = array(
				'font' => 'bb-icon-file-excel',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-hlsb</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016zM13.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016zM9.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-3.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h3.008zM17.504 20.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016zM13.504 20.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-2.016c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h2.016zM7.424 17.984l0.512 1.312h0.064l0.64-1.312h1.344l-1.216 2.432 1.248 2.592h-1.376l-0.64-1.312h-0.064l-0.64 1.312h-1.28l1.152-2.464-1.152-2.56h1.408z"></path></svg>',
			);
			break;
		case 'hlsm':
			$svg = array(
				'font' => 'bb-icon-file-excel',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32"><title>file-hlsm</title><path d="M17.632 0.928c1.024 0 1.984 0.416 2.688 1.152v0l5.92 6.112c0.672 0.704 1.056 1.632 1.056 2.624v0 16.48c0 2.080-1.696 3.776-3.776 3.776v0h-15.040c-2.080 0-3.776-1.696-3.776-3.776v0-22.592c0-2.080 1.696-3.776 3.776-3.776v0zM17.632 2.816h-9.152c-1.056 0-1.888 0.864-1.888 1.888v0 22.592c0 1.024 0.832 1.888 1.888 1.888v0h15.040c1.056 0 1.888-0.864 1.888-1.888v0-16.48c0-0.48-0.192-0.96-0.512-1.312v0l-5.92-6.112c-0.352-0.352-0.832-0.576-1.344-0.576v0zM19.936 21.632l0.48 1.248h0.064l0.608-1.248h1.248l-1.12 2.304 1.152 2.4h-1.312l-0.576-1.216h-0.064l-0.608 1.216h-1.216l1.12-2.304-1.12-2.4h1.344zM17.184 24.48c0.256 0 0.448 0.192 0.448 0.448v0.96c0 0.256-0.192 0.448-0.448 0.448h-1.888c-0.256 0-0.48-0.192-0.48-0.448v-0.96c0-0.256 0.224-0.448 0.48-0.448h1.888zM13.408 24.48c0.256 0 0.48 0.192 0.48 0.448v0.96c0 0.256-0.224 0.448-0.48 0.448h-2.816c-0.256 0-0.48-0.192-0.48-0.448v-0.96c0-0.256 0.224-0.448 0.48-0.448h2.816zM17.184 20.704c0.256 0 0.448 0.224 0.448 0.48v0.928c0 0.256-0.192 0.48-0.448 0.48h-1.888c-0.256 0-0.48-0.224-0.48-0.48v-0.928c0-0.256 0.224-0.48 0.48-0.48h1.888zM13.408 20.704c0.256 0 0.48 0.224 0.48 0.48v0.928c0 0.256-0.224 0.48-0.48 0.48h-2.816c-0.256 0-0.48-0.224-0.48-0.48v-0.928c0-0.256 0.224-0.48 0.48-0.48h2.816zM20.928 16.928c0.288 0 0.48 0.224 0.48 0.48v0.928c0 0.288-0.192 0.48-0.48 0.48h-1.856c-0.288 0-0.48-0.192-0.48-0.48v-0.928c0-0.256 0.192-0.48 0.48-0.48h1.856zM17.184 16.928c0.256 0 0.448 0.224 0.448 0.48v0.928c0 0.288-0.192 0.48-0.448 0.48h-1.888c-0.256 0-0.48-0.192-0.48-0.48v-0.928c0-0.256 0.224-0.48 0.48-0.48h1.888zM13.408 16.928c0.256 0 0.48 0.224 0.48 0.48v0.928c0 0.288-0.224 0.48-0.48 0.48h-2.816c-0.256 0-0.48-0.192-0.48-0.48v-0.928c0-0.256 0.224-0.48 0.48-0.48h2.816z"></path></svg>',
			);
			break;
		case 'htm':
		case 'html':
			$svg = array(
				'font' => 'bb-icon-file-code',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-html</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.736 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM9.536 20.416c0.192 0.224 0.224 0.544 0.032 0.8l-0.032 0.064-2.112 2.048 2.112 2.112c0.192 0.224 0.224 0.544 0.032 0.768l-0.032 0.064c-0.224 0.224-0.544 0.256-0.768 0.064l-0.096-0.064-2.496-2.528c-0.224-0.192-0.224-0.544-0.064-0.768l0.064-0.064 2.528-2.496c0.224-0.224 0.608-0.224 0.832 0zM14.080 20.416c0.224-0.224 0.608-0.224 0.832 0v0l2.528 2.496 0.032 0.064c0.192 0.224 0.16 0.576-0.032 0.768v0l-2.592 2.592c-0.224 0.192-0.544 0.16-0.768-0.064v0l-0.064-0.064c-0.16-0.224-0.16-0.544 0.064-0.768v0l2.080-2.112-2.144-2.112c-0.16-0.256-0.16-0.576 0.064-0.8zM12.768 20.032c0.288 0.096 0.48 0.384 0.416 0.672l-0.032 0.064-1.664 5.28c-0.096 0.32-0.448 0.48-0.736 0.384s-0.448-0.384-0.416-0.672l0.032-0.064 1.664-5.28c0.096-0.32 0.448-0.48 0.736-0.384zM10.496 15.008c0.288 0 0.512 0.224 0.512 0.48v1.024c0 0.256-0.224 0.48-0.512 0.48h-4c-0.256 0-0.48-0.224-0.48-0.48v-1.024c0-0.256 0.224-0.48 0.48-0.48h4z"></path></svg>',
			);
			break;
		case 'ics':
			$svg = array(
				'font' => 'bb-icon-file-code',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-ics</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM15.008 15.488c0.544 0 0.992 0.448 0.992 1.024v0.512c1.12 0 2.016 0.896 2.016 1.984v5.984c0 1.12-0.896 2.016-2.016 2.016h-8c-1.088 0-1.984-0.896-1.984-2.016v-5.984c0-1.056 0.8-1.92 1.824-1.984h0.16v-0.512c0-0.576 0.448-1.024 0.992-1.024s0.992 0.448 0.992 1.024v0.512h4v-0.512c0-0.576 0.448-1.024 1.024-1.024zM16.064 19.616h-7.968c-0.256 0-0.448 0.192-0.512 0.416v4.48c0 0.512 0.384 0.96 0.896 0.992l0.096 0.032h6.976c0.512 0 0.96-0.384 0.992-0.896l0.032-0.128v-4.384c0-0.288-0.224-0.512-0.512-0.512zM10.144 20.672c0.256 0 0.48 0.224 0.48 0.512v0.704c0 0.288-0.224 0.512-0.48 0.512h-0.832c-0.288 0-0.512-0.224-0.512-0.512v-0.704c0-0.288 0.224-0.512 0.512-0.512h0.832z"></path></svg>',
			);
			break;
		case 'ico':
			$svg = array(
				'font' => 'bb-icon-file-image',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-ico</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16 16c1.12 0 2.016 0.896 2.016 1.984v7.008c0 1.12-0.896 2.016-2.016 2.016h-8c-1.088 0-1.984-0.896-1.984-2.016v-7.008c0-1.088 0.896-1.984 1.984-1.984h8zM13.504 17.984h-3.008c-0.256 0-0.48 0.224-0.48 0.512v0 0.544c0 0.256 0.224 0.48 0.48 0.48v0h0.768v3.968h-0.768c-0.256 0-0.48 0.192-0.48 0.48v0 0.544c0 0.256 0.224 0.48 0.48 0.48v0h3.008c0.288 0 0.512-0.224 0.512-0.48v0-0.544c0-0.288-0.224-0.48-0.512-0.48v0h-0.672v-3.968h0.672c0.288 0 0.512-0.224 0.512-0.48v0-0.544c0-0.288-0.224-0.512-0.512-0.512v0z"></path></svg>',
			);
			break;
		case 'ipa':
			$svg = array(
				'font' => 'bb-icon-file-mobile',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-ipa</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16 13.984c1.376 0 2.496 1.12 2.496 2.528v0 8c0 1.376-1.12 2.496-2.496 2.496v0h-8c-1.376 0-2.496-1.12-2.496-2.496v0-8c0-1.408 1.12-2.528 2.496-2.528v0h8zM16 15.008h-8c-0.832 0-1.504 0.672-1.504 1.504v0 8c0 0.8 0.672 1.472 1.504 1.472v0h8c0.832 0 1.504-0.672 1.504-1.472v0-8c0-0.832-0.672-1.504-1.504-1.504v0zM9.12 19.008v2.976h-0.608v-2.976h0.608zM11.104 19.008c0.608 0 1.056 0.416 1.056 1.024s-0.448 1.024-1.088 1.024v0h-0.576v0.928h-0.64v-2.976h1.248zM14.080 19.008l1.056 2.976h-0.704l-0.224-0.704h-1.056l-0.224 0.704h-0.608l1.024-2.976h0.736zM13.728 19.616h-0.064l-0.352 1.184h0.768l-0.352-1.184zM10.944 19.52h-0.448v1.024h0.448c0.352 0 0.576-0.16 0.576-0.512 0-0.32-0.224-0.512-0.576-0.512v0z"></path></svg>',
			);
			break;
		case 'js':
			$svg = array(
				'font' => 'bb-icon-file-code',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-js</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM13.504 16c1.088 0 1.984 0.896 1.984 1.984v7.008c0 1.12-0.896 2.016-1.984 2.016h-6.016c-1.088 0-1.984-0.896-1.984-2.016v-7.008c0-1.088 0.896-1.984 1.984-1.984h6.016zM15.904 13.12c1.312 0 2.4 1.056 2.496 2.336v6.496c0 1.152-0.896 2.112-2.048 2.176h-0.128v-0.992c0.608 0 1.12-0.48 1.184-1.088v-6.4c0-0.8-0.608-1.44-1.344-1.504h-5.568c-0.768 0-1.408 0.576-1.472 1.344l-0.032 0.16v0.032h-0.992v-0.032c0-1.344 1.024-2.432 2.336-2.496l0.16-0.032h5.408zM12.992 20.992h-5.088c-0.224 0.064-0.416 0.256-0.416 0.512 0 0.224 0.192 0.448 0.416 0.48h5.184c0.224-0.032 0.416-0.256 0.416-0.48 0-0.288-0.224-0.512-0.512-0.512zM12.992 19.008h-5.088c-0.224 0.032-0.416 0.256-0.416 0.48 0 0.256 0.192 0.448 0.416 0.512h5.184c0.224-0.064 0.416-0.256 0.416-0.512s-0.224-0.48-0.512-0.48z"></path></svg>',
			);
			break;
		case 'jar':
			$svg = array(
				'font' => 'bb-icon-file-code',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-jar</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.696 25.504c-0.192 0.48-0.544 0.832-1.088 1.024-0.64 0.288-1.472 0.416-2.336 0.448-0.96 0.064-2.048 0.128-3.328 0-0.416-0.032-1.024-0.096-1.792-0.224-0.192-0.032-0.48-0.096-0.896-0.224 0.928 0.064 1.6 0.096 2.016 0.096 1.824 0 3.136-0.032 3.872-0.096 1.536-0.064 2.464-0.288 3.552-1.024zM8.256 24.544c-0.928 0.16-1.376 0.32-1.376 0.512 0 0.256 2.016 0.608 4.096 0.608 0.928 0 1.92-0.032 2.752-0.128 1.056-0.096 1.888-0.256 2.24-0.352 0.64-0.128 0.928-0.288 0.864-0.544 0.224 0.064 0.224 0.224 0.128 0.416s-0.48 0.544-1.312 0.768c-0.448 0.128-1.216 0.352-3.232 0.448-1.6 0.032-2.304 0.032-3.072 0-0.768-0.064-3.456-0.256-3.392-0.96 0.032-0.48 0.8-0.736 2.304-0.768zM9.632 23.36v0.032c-0.032 0.064-0.064 0.192 0.064 0.256 0.128 0.032 0.768 0.096 1.44 0.064 0.576-0.032 1.152-0.064 1.536-0.064 0.448-0.032 0.992-0.128 1.6-0.224-0.128 0.096-0.384 0.384-0.928 0.608-0.576 0.256-1.696 0.384-2.272 0.384s-2.176 0-2.176-0.544c0-0.512 0.736-0.512 0.736-0.512zM9.184 21.664c-0.224 0.192-0.256 0.32-0.096 0.416 0.192 0.16 0.64 0.224 1.984 0.224 0.928 0 2.048-0.128 3.456-0.384-0.192 0.192-0.384 0.352-0.608 0.448-0.768 0.384-1.792 0.576-2.912 0.576-2.176 0-2.72-0.48-2.656-0.8 0.032-0.192 0.288-0.352 0.832-0.48zM17.024 19.584c0.768 0.192 1.024 0.576 1.056 1.056 0.096 0.864-0.8 1.536-2.656 2.048 1.024-0.768 1.536-1.376 1.6-1.888s-0.416-0.832-1.376-1.024c0.512-0.224 0.96-0.288 1.376-0.192zM9.76 19.776c-0.928 0.256-1.376 0.448-1.376 0.576 0 0.224 1.152 0.352 2.656 0.352 0.992 0 2.336-0.096 4-0.352-0.8 0.768-2.144 1.12-4.064 1.088-2.88-0.064-3.808-0.48-3.744-0.992 0.032-0.384 0.864-0.608 2.528-0.672zM14.88 14.4c-0.32 0.256-0.64 0.48-0.928 0.704-0.416 0.352-1.088 0.832-1.088 1.504s0.576 0.704 0.8 1.632c0.16 0.608-0.192 1.216-1.024 1.856 0.224-0.288 0.288-0.8 0.16-1.216-0.16-0.416-1.248-0.864-0.896-2.272 0.096-0.352 0.512-0.832 0.832-1.088 0.512-0.384 1.216-0.768 2.144-1.12zM13.504 11.008c0.544 0.992 0.608 1.888 0.128 2.656-0.48 0.736-1.568 1.536-2.304 2.24-0.352 0.384-0.512 0.928-0.448 1.376 0.064 0.832 0.32 1.6 0.704 2.272-1.28-0.992-1.888-1.984-1.824-2.944 0.096-1.44 1.504-2.112 2.176-2.656s1.696-1.248 1.568-2.944z"></path></svg>',
			);
			break;
		case 'mp3':
			$svg = array(
				'font' => 'bb-icon-file-audio',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-mp3</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16.96 16c0.128 0 0.288 0.032 0.384 0.128s0.16 0.224 0.16 0.352v0 7.744c0 0.96-0.672 1.824-1.664 2.048-0.96 0.224-1.984-0.192-2.464-1.056-0.448-0.832-0.288-1.888 0.448-2.528s1.856-0.736 2.688-0.224v0-3.040l-6.624 0.704v4.768c0 0.96-0.704 1.792-1.664 2.048-0.96 0.224-1.984-0.192-2.464-1.056-0.48-0.832-0.288-1.888 0.448-2.528s1.824-0.736 2.688-0.224v0-5.856c0-0.256 0.192-0.448 0.448-0.48v0z"></path></svg>',
			);
			break;
		case 'ods':
			$svg = array(
				'font' => 'bb-icon-file-spreadsheet',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-ods</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16 16c1.12 0 2.016 0.896 2.016 1.984v7.008c0 1.12-0.896 2.016-2.016 2.016h-8c-1.088 0-1.984-0.896-1.984-2.016v-7.008c0-1.088 0.896-1.984 1.984-1.984h8zM9.344 24.224h-1.856v0.768c0 0.256 0.192 0.448 0.416 0.512h1.44v-1.28zM12.352 24.224h-2.016v1.28h2.016v-1.28zM16.512 24.224h-3.168v1.28h2.656c0.256 0 0.448-0.192 0.48-0.416l0.032-0.096v-0.768zM9.344 22.048h-1.856v1.184h1.856v-1.184zM12.352 22.048h-2.016v1.184h2.016v-1.184zM16.512 22.048h-3.168v1.184h3.168v-1.184zM9.344 19.84h-1.856v1.184h1.856v-1.184zM12.352 19.84h-2.016v1.184h2.016v-1.184zM16.512 19.84h-3.168v1.184h3.168v-1.184zM9.344 17.504h-1.344c-0.256 0-0.448 0.16-0.48 0.416l-0.032 0.064v0.864h1.856v-1.344zM12.352 17.504h-2.016v1.344h2.016v-1.344z"></path></svg>',
			);
			break;
		case 'odt':
			$svg = array(
				'font' => 'bb-icon-file-spreadsheet',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-odt</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-11.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h11.008zM17.504 21.536c0.288 0 0.512 0.224 0.512 0.48v1.024c0 0.256-0.224 0.48-0.512 0.48h-11.008c-0.256 0-0.48-0.224-0.48-0.48v-1.024c0-0.256 0.224-0.48 0.48-0.48h11.008zM11.712 16.512c0.576 0.128 0.992 0.416 1.28 0.8-1.024-0.096-1.792 0.064-2.368 0.512-0.48 0.384-0.8 0.992-0.928 1.952 0 0.128-0.064 0.16-0.128 0.192s-0.192 0.032-0.288-0.096c-0.608-0.512-1.344-0.928-1.952-0.992-0.736-0.096-1.504 0.288-2.336 1.12 0.448-1.312 1.056-2.016 1.888-2.176 0.832-0.128 1.536 0.064 2.048 0.544 0.192-0.704 0.576-1.248 1.152-1.6 0.384-0.224 1.056-0.384 1.632-0.256zM11.136 14.016c0.384 0.096 0.672 0.288 0.896 0.544-0.704-0.128-1.28 0-1.76 0.352-0.352 0.256-0.512 0.768-0.576 1.312 0 0.096-0.032 0.128-0.096 0.128-0.032 0.032-0.128 0.032-0.192-0.032-0.416-0.384-0.928-0.672-1.376-0.704-0.512-0.032-1.056 0.224-1.632 0.768 0.32-0.896 0.736-1.376 1.344-1.472 0.576-0.096 1.056 0.032 1.408 0.384 0.128-0.48 0.416-0.832 0.832-1.088 0.256-0.16 0.736-0.256 1.152-0.192z"></path></svg>',
			);
			break;
		case 'pdf':
			$svg = array(
				'font' => 'bb-icon-file-pdf',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-pdf</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM11.456 12.992c0.864 0 1.44 0.48 1.44 2.016 0 0.576-0.064 1.216-0.256 2.208 0.608 1.152 1.44 2.304 2.208 3.168 0.576-0.064 1.056-0.16 1.536-0.16 1.44 0 2.112 0.8 2.112 1.632 0 0.8-0.768 1.728-1.984 1.728-0.704 0-1.472-0.352-2.336-1.088-1.312 0.256-2.72 0.736-3.968 1.216-0.704 1.44-1.696 3.296-3.008 3.296-0.96 0-1.696-0.896-1.696-1.728 0-1.088 1.056-2.048 3.264-3.072 0.704-1.472 1.408-3.264 1.824-4.832-0.48-0.96-0.736-1.792-0.736-2.496 0-1.184 0.544-1.888 1.6-1.888zM11.744 19.072c-0.256 0.896-0.64 1.824-0.96 2.688 0.8-0.256 1.6-0.544 2.4-0.736-0.512-0.608-0.96-1.248-1.44-1.952z"></path></svg>',
			);
			break;
		case 'png':
			$svg = array(
				'font' => 'bb-icon-file-image',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-png</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.12 0-2.016 0.896-2.016 2.016v0 24c0 1.12 0.896 2.016 2.016 2.016v0h16c1.12 0 1.984-0.896 1.984-2.016v0-17.504c0-0.512-0.192-1.024-0.544-1.408v0l-6.272-6.496c-0.384-0.384-0.896-0.608-1.44-0.608v0zM15.68 15.008c1.28 0 2.304 1.024 2.304 2.304v0 7.392c0 1.248-1.024 2.304-2.304 2.304v0h-7.36c-1.28 0-2.336-1.056-2.336-2.304v0-7.392c0-1.28 1.056-2.304 2.336-2.304v0h7.36zM9.152 21.376l-0.096 0.064-2.144 1.6v1.664c0 0.704 0.544 1.312 1.248 1.376h7.52c0.768 0 1.408-0.64 1.408-1.376v0-3.136c-0.896 0.416-2.080 0.992-3.52 1.728-0.448 0.224-0.992 0.192-1.408-0.064l-0.128-0.096-2.368-1.728c-0.16-0.096-0.352-0.096-0.512-0.032zM15.68 15.936h-7.36c-0.768 0-1.408 0.608-1.408 1.376v0 4.48c0.416-0.32 0.96-0.704 1.536-1.152 0.512-0.384 1.152-0.416 1.664-0.096l0.128 0.064 2.368 1.728c0.128 0.096 0.288 0.128 0.448 0.096l0.096-0.064 3.936-1.92v-3.136c0-0.736-0.576-1.312-1.248-1.376h-0.16zM13.376 17.792c0.672 0 1.248 0.544 1.248 1.248s-0.576 1.248-1.248 1.248c-0.704 0-1.248-0.544-1.248-1.248s0.544-1.248 1.248-1.248z"></path></svg>',
			);
			break;
		case 'psd':
			$svg = array(
				'font' => 'bb-icon-file-vector',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-psd</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM7.072 22.336l4.416 2.528c0.32 0.192 0.704 0.192 0.992 0.032l4.448-2.528 0.896 0.704c0.16 0.16 0.192 0.416 0.064 0.576-0.032 0.032-0.064 0.064-0.128 0.096l-5.536 3.2c-0.128 0.064-0.288 0.064-0.416 0.032l-0.096-0.032-5.44-3.2c-0.192-0.128-0.256-0.352-0.128-0.544 0-0.064 0.032-0.096 0.064-0.096l0.864-0.768zM7.104 20l4.416 2.56c0.32 0.16 0.704 0.16 0.992 0l4.448-2.528 0.896 0.736c0.16 0.128 0.192 0.384 0.064 0.544-0.032 0.064-0.064 0.096-0.128 0.128l-5.536 3.168c-0.128 0.064-0.288 0.096-0.448 0.032l-0.064-0.032-5.44-3.2c-0.192-0.096-0.256-0.352-0.128-0.544 0-0.032 0.032-0.064 0.064-0.096l0.864-0.768zM12.16 15.040l0.064 0.032 5.472 3.104c0.192 0.128 0.288 0.32 0.288 0.544 0 0.16-0.064 0.32-0.192 0.416l-0.096 0.064-5.44 3.104c-0.128 0.096-0.288 0.096-0.448 0.032l-0.064-0.032-5.44-3.104c-0.224-0.096-0.32-0.32-0.288-0.544 0-0.16 0.064-0.32 0.192-0.416l0.096-0.064 5.408-3.104c0.128-0.096 0.288-0.096 0.448-0.032zM11.968 16.256l-4.256 2.432 0.8 0.448 1.856-0.704c0.032 0 0.032 0 0.064 0 0.128-0.032 0.256 0.064 0.32 0.192v0.064l0.064 0.352 1.408-0.352c0.096-0.032 0.16 0 0.224 0 0.128 0.064 0.192 0.224 0.16 0.352l-0.032 0.064-0.896 1.824 0.32 0.192 4.288-2.432-4.32-2.432zM13.408 17.696c0 0.288-0.384 0.512-0.896 0.576-0.512 0.032-0.928-0.16-0.928-0.416-0.032-0.288 0.352-0.544 0.864-0.576s0.928 0.16 0.96 0.416z"></path></svg>',
			);
			break;
		case 'potm':
		case 'pptm':
			$svg = array(
				'font' => 'bb-icon-file-pptm',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-pptm</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.024 14.4c1.28 0.128 1.088 2.048 1.12 2.784-0.288 0-0.928 0-1.568 0l-0.032 8.512c0 1.024-0.8 1.888-1.792 1.952v0h-8.032c-1.056-0.032-1.12-1.216-1.088-2.56v-0.16c0.32 0 0.768 0 1.248 0l0.032-8.576c0-1.088 0.864-1.952 1.952-1.952v0h8.16zM7.904 24.928c2.272 0 5.152 0 6.112 0h0.224c-0.032 0.64-0.032 1.248 0.16 1.728h0.192c0.48 0 0.896-0.384 0.96-0.864v0-8.8c0-0.416 0-1.056 0.128-1.6h-6.816c-0.544 0-0.96 0.448-0.96 0.96v0zM12.16 16.992c1.056 0 1.536 0.64 1.536 1.536 0 0.832-0.448 1.632-1.376 1.696h-1.376v1.76h-0.928v-4.992h2.144zM10.944 17.856v1.504c1.024 0 1.792 0.096 1.792-0.768 0-0.832-0.512-0.736-1.792-0.736z"></path></svg>',
			);
			break;
		case 'potx':
		case 'pptx':
			$svg = array(
				'font' => 'bb-icon-file-pptx',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-pptx</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.504 24.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-11.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h11.008zM11.84 18.4l4.608 2.848c-0.992 1.376-2.624 2.24-4.448 2.24-2.048 0-3.872-1.12-4.8-2.816l-0.128-0.192 4.768-2.080zM12.224 12.512c2.944 0.096 5.28 2.528 5.28 5.472 0 0.864-0.192 1.632-0.512 2.368l-0.16 0.256-4.608-2.784v-5.312zM11.52 12.512v5.312l-4.704 2.016c-0.192-0.576-0.32-1.216-0.32-1.856 0-2.848 2.208-5.216 5.024-5.472z"></path></svg>',
			);
			break;
		case 'pps':
			$svg = array(
				'font' => 'bb-icon-file-pps',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-pps</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM18.592 16c0.224 0 0.416 0.192 0.416 0.384v1.216c0 0.224-0.192 0.384-0.416 0.384h-0.576v7.52c0 0.768-0.608 1.408-1.376 1.504h-9.152c-0.768 0-1.408-0.608-1.472-1.376v-7.648h-0.608c-0.224 0-0.416-0.16-0.416-0.384v-1.216c0-0.192 0.192-0.384 0.416-0.384h13.184zM16.992 17.984h-9.984v7.52c0 0.256 0.16 0.448 0.416 0.48l0.064 0.032h9.024c0.224 0 0.448-0.192 0.48-0.416v-7.616zM11.776 19.456v2.784h2.752c0 1.504-1.216 2.752-2.752 2.752s-2.784-1.248-2.784-2.752c0-1.536 1.248-2.784 2.784-2.784zM12.224 19.008c1.472 0 2.688 1.152 2.784 2.592v0.16h-2.784v-2.752z"></path></svg>',
			);
			break;
		case 'ppsx':
			$svg = array(
				'font' => 'bb-icon-file-ppsx',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-ppsx</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.472 26.4c0.288 0 0.544 0.256 0.544 0.608 0 0.288-0.224 0.544-0.48 0.576l-0.064 0.032h-10.912c-0.32 0-0.544-0.288-0.544-0.608s0.192-0.544 0.448-0.608h11.008zM17.472 23.904c0.288 0 0.544 0.256 0.544 0.608 0 0.288-0.224 0.544-0.48 0.576h-10.976c-0.32 0-0.544-0.256-0.544-0.576s0.192-0.544 0.448-0.608h11.008zM11.488 12.512c0 0.896 0 2.144 0 3.68v0.8c2.208 0 3.712 0 4.512 0 0 2.496-2.016 4.512-4.512 4.512-2.464 0-4.48-2.016-4.48-4.512 0-2.464 2.016-4.48 4.48-4.48zM12.512 11.488c2.464 0 4.48 2.016 4.48 4.512v0h-4.48z"></path></svg>',
			);
			break;
		case 'ppt':
			$svg = array(
				'font' => 'bb-icon-file-ppt',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-ppt</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM11.552 15.936v5.536h5.536c0 3.040-2.496 5.536-5.536 5.536-3.072 0-5.536-2.496-5.536-5.536 0-3.072 2.464-5.536 5.536-5.536zM12.448 15.008c3.008 0 5.44 2.368 5.536 5.312l0.032 0.224h-5.568v-5.536z"></path></svg>',
			);
			break;
		case 'rar':
			$svg = array(
				'font' => 'bb-icon-file-rar',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-rar</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM9.44 15.648l4.032 2.624c0.128 0.096 0.224 0.256 0.224 0.448v6.336l-3.072 1.28c-0.16 0.064-0.32 0.032-0.48-0.064l-4.608-3.040c-0.032-0.032-0.032-0.064-0.032-0.096 0.032-0.032 0.032-0.032 0.064-0.064l0.384-0.128v0.16l4.416 2.88c0.16-0.224 0.256-0.48 0.256-0.768 0-0.224-0.096-0.544-0.256-0.864v0l-4.832-3.2c-0.032-0.032-0.032-0.064-0.032-0.128 0.032 0 0.032 0 0.064-0.032l0.384-0.128v0.16l4.416 2.88c0.16-0.288 0.256-0.544 0.256-0.832 0-0.192-0.096-0.48-0.256-0.8v0l-4.832-3.2c-0.032-0.032-0.032-0.064-0.032-0.128 0.032 0 0.032 0 0.064-0.032l0.448-0.16c0 0.064 0 0.128 0 0.192l4.352 2.88c0.16-0.352 0.256-0.672 0.256-0.96 0-0.256-0.064-0.544-0.192-0.832-0.032-0.096-0.096-0.16-0.192-0.224l-4.256-2.656-0.32-0.192c-0.032-0.032-0.032-0.064-0.032-0.128 0.032 0 0.032-0.032 0.064-0.032l3.744-1.152zM13.28 14.528l4.448 2.496c0.352 0.256 0.512 0.64 0.512 1.12s-0.16 0.8-0.448 1.024l-0.064-0.064c0.352 0.256 0.512 0.64 0.512 1.12s-0.16 0.8-0.448 1.024l-0.064-0.064c0.352 0.256 0.512 0.64 0.512 1.152 0 0.48-0.16 0.832-0.512 1.024l-2.656 1.12v-6.336c0-0.192-0.096-0.352-0.256-0.448l-3.936-2.496 2.144-0.672c0.096-0.032 0.192-0.032 0.256 0z"></path></svg>',
			);
			break;
		case 'rtf':
			$svg = array(
				'font' => 'bb-icon-file-rtf',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-rtf</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM12.352 16.992v8h0.928v0.992h-3.616v-0.992h1.152v-1.984h-2.72l-0.256 0.32-1.184 1.664h0.928v0.992h-3.072v-0.992h0.896l5.696-8h1.248zM18.496 24c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.256-0.224 0.48-0.512 0.48h-4c-0.256 0-0.48-0.224-0.48-0.48v-0.992c0-0.288 0.224-0.512 0.48-0.512h4zM18.496 20.512c0.288 0 0.512 0.224 0.512 0.48v0.992c0 0.288-0.224 0.512-0.512 0.512h-4c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.256 0.224-0.48 0.48-0.48h4zM10.816 19.168l-2.016 2.816h2.016v-2.816zM18.496 16.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-4c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h4z"></path></svg>',
			);
			break;
		case 'rss':
			$svg = array(
				'font' => 'bb-icon-file-rss',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-rss</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM6.016 15.008c6.24 0 11.328 4.992 11.488 11.2v0.288h-2.016c0-5.152-4.096-9.344-9.216-9.504h-0.256v-1.984zM6.016 19.008c4.032 0 7.36 3.232 7.488 7.232v0.256h-2.016c0-2.976-2.336-5.376-5.28-5.504h-0.192v-1.984zM7.52 23.488c0.832 0 1.504 0.672 1.504 1.472 0 0.832-0.672 1.504-1.504 1.504s-1.504-0.672-1.504-1.504c0-0.8 0.672-1.472 1.504-1.472z"></path></svg>',
			);
			break;
		case 'sketch':
			$svg = array(
				'font' => 'bb-icon-file-sketch',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-sketch</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM15.584 13.504c0.16 0 0.32 0.064 0.416 0.192v0l3.232 4.32c0.16 0.192 0.16 0.448 0 0.608v0l-7.008 8.704c-0.192 0.224-0.576 0.224-0.768 0v0l-7.008-8.704c-0.128-0.16-0.128-0.416 0-0.608v0l3.264-4.32c0.096-0.128 0.256-0.192 0.416-0.192v0h7.456zM17.664 19.008h-11.616l5.792 7.2 5.824-7.2zM7.328 15.84l-1.6 2.144h1.6v-2.144zM11.872 15.648l-2.496 2.336h5.344l-2.848-2.336zM16.352 15.808v2.176h1.632l-1.632-2.176zM15.328 14.496h-6.976v3.104l3.168-2.976c0.16-0.128 0.384-0.16 0.576-0.064l0.096 0.064 3.136 2.592v-2.72z"></path></svg>',
			);
			break;
		case 'tar':
			$svg = array(
				'font' => 'bb-icon-file-tar',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-tar</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM15.936 16.512c0.544 0 1.024 0.32 1.216 0.8v0l0.736 1.824c0.064 0.16 0.128 0.352 0.128 0.544v0 6.304c0 0.832-0.672 1.504-1.504 1.504v0h-9.024c-0.8 0-1.472-0.672-1.472-1.504v0-6.304c0-0.192 0.032-0.352 0.064-0.512v0l0.672-1.824c0.192-0.512 0.672-0.832 1.216-0.832v0zM16.992 20h-4v0.704c0 0.224-0.16 0.448-0.416 0.48h-1.088c-0.256 0-0.48-0.224-0.48-0.48v0-0.704h-4v5.984c0 0.256 0.16 0.48 0.416 0.512h9.088c0.256 0 0.48-0.224 0.48-0.512v0-5.984zM11.008 17.504h-3.040c-0.128 0-0.224 0.064-0.288 0.192v0l-0.448 1.312h3.776v-1.504zM15.936 17.504h-2.944v1.504h3.776l-0.544-1.312c-0.032-0.096-0.128-0.16-0.224-0.192h-0.064z"></path></svg>',
			);
			break;
		case 'tif':
		case 'tiff':
		case 'jpg':
		case 'jpeg':
			$svg = array(
				'font' => 'bb-icon-file-image',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-jpg</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.736 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16 13.504c1.6 0 2.912 1.248 3.008 2.816v8.192c0 1.6-1.248 2.88-2.816 2.976h-8.192c-1.6 0-2.912-1.248-2.976-2.816l-0.032-0.16v-8c0-1.6 1.248-2.912 2.848-3.008h8.16zM16 14.496h-8c-1.056 0-1.92 0.832-1.984 1.856v8.16c0 0.064 0 0.096 0 0.16l2.624-2.432c0.384-0.384 1.024-0.352 1.408 0.032v0l1.376 1.504 3.328-3.84c0.352-0.416 0.992-0.448 1.408-0.096 0.032 0.032 0.064 0.064 0.096 0.096l1.76 1.92v-5.344c0-1.056-0.832-1.92-1.856-2.016h-0.16zM10.752 18.112c0.704 0 1.248 0.544 1.248 1.248 0 0.672-0.544 1.248-1.248 1.248s-1.248-0.576-1.248-1.248c0-0.704 0.544-1.248 1.248-1.248z"></path></svg>',
			);
			break;
		case 'txt':
			$svg = array(
				'font' => 'bb-icon-file-txt',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-txt</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM15.488 24c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.256-0.224 0.48-0.512 0.48h-8.992c-0.256 0-0.48-0.224-0.48-0.48v-0.992c0-0.288 0.224-0.512 0.48-0.512h8.992zM13.504 20.512c0.288 0 0.512 0.224 0.512 0.48v0.992c0 0.288-0.224 0.512-0.512 0.512h-7.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.256 0.224-0.48 0.48-0.48h7.008zM17.504 16.992c0.288 0 0.512 0.224 0.512 0.512v0.992c0 0.288-0.224 0.512-0.512 0.512h-11.008c-0.256 0-0.48-0.224-0.48-0.512v-0.992c0-0.288 0.224-0.512 0.48-0.512h11.008z"></path></svg>',
			);
			break;
		case 'vcf':
			$svg = array(
				'font' => 'bb-icon-file-vcf',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-vcf</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16 13.504c1.376 0 2.496 1.12 2.496 2.496v0 8.992c0 1.376-1.12 2.496-2.496 2.496v0h-8c-1.376 0-2.496-1.12-2.496-2.496v0-8.992c0-1.376 1.12-2.496 2.496-2.496v0h8zM16 14.496h-8c-0.832 0-1.504 0.672-1.504 1.504v0 8.992c0 0.832 0.672 1.504 1.504 1.504v0h8c0.832 0 1.504-0.672 1.504-1.504v0-8.992c0-0.832-0.672-1.504-1.504-1.504v0zM14.56 24.16c0.256 0 0.48 0.224 0.48 0.48s-0.16 0.448-0.384 0.512h-5.088c-0.288 0-0.512-0.224-0.512-0.512 0-0.224 0.16-0.448 0.416-0.48h5.088zM14.624 20.64c0.192 0.128 0.352 0.352 0.384 0.576v1.568h-6.016v-1.472c0-0.256 0.16-0.512 0.384-0.672 1.568-1.024 3.68-1.024 5.248 0zM13.344 16.512c0.672 0.672 0.672 1.792 0 2.464-0.704 0.672-1.792 0.672-2.464 0-0.704-0.672-0.704-1.792 0-2.464 0.672-0.672 1.76-0.672 2.464 0z"></path></svg>',
			);
			break;
		case 'wav':
			$svg = array(
				'font' => 'bb-icon-file-wav',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-wav</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16 13.984c1.12 0 2.016 0.896 2.016 2.016v8c0 1.088-0.896 1.984-2.016 1.984h-8c-1.088 0-1.984-0.896-1.984-1.984v-8c0-1.12 0.896-2.016 1.984-2.016h8zM10.144 16.512c-0.256 0-0.448 0.16-0.48 0.384v5.6c0.032 0.224 0.224 0.384 0.48 0.384s0.448-0.16 0.512-0.384v-5.6c-0.064-0.224-0.256-0.384-0.512-0.384zM15.36 16.512c-0.256 0-0.448 0.16-0.512 0.384v5.6c0.064 0.224 0.256 0.384 0.512 0.384 0.224 0 0.448-0.16 0.48-0.384v-5.6c-0.032-0.224-0.256-0.384-0.48-0.384zM13.536 17.632c-0.256 0-0.448 0.16-0.512 0.416v3.584c0.064 0.256 0.256 0.416 0.512 0.416s0.448-0.16 0.48-0.416v-3.584c-0.032-0.256-0.224-0.416-0.48-0.416zM8.512 18.24c-0.256 0-0.448 0.16-0.512 0.416v2.56c0.064 0.224 0.256 0.416 0.512 0.416 0.224 0 0.448-0.192 0.48-0.416v-2.56c-0.032-0.256-0.256-0.416-0.48-0.416zM11.744 18.24c-0.224 0-0.448 0.16-0.48 0.416v2.56c0.032 0.224 0.256 0.416 0.48 0.416 0.256 0 0.448-0.192 0.512-0.416v-2.56c-0.064-0.256-0.256-0.416-0.512-0.416z"></path></svg>',
			);
			break;
		case 'xlam':
		case 'xls':
		case 'xlsb':
		case 'xlsm':
		case 'xlsx':
			$svg = array(
				'font' => 'bb-icon-file-xlsx',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-xlsx</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM15.488 13.984c1.408 0 2.528 1.12 2.528 2.528v0 8c0 1.376-1.12 2.496-2.528 2.496v0h-8c-1.376 0-2.496-1.12-2.496-2.496v0-8c0-1.408 1.12-2.528 2.496-2.528v0h8zM9.024 23.072h-3.008v1.44c0 0.768 0.576 1.408 1.344 1.472h1.664v-2.912zM16.992 23.072h-6.976v2.912h5.472c0.8 0 1.44-0.576 1.504-1.344v-1.568zM9.024 19.072h-3.008v3.008h3.008v-3.008zM16.992 19.072h-6.976v3.008h6.976v-3.008zM9.024 15.008h-1.536c-0.8 0-1.472 0.672-1.472 1.504v0 1.568h3.008v-3.072zM15.488 15.008h-5.472v3.072h6.976v-1.568c0-0.8-0.576-1.44-1.344-1.504h-0.16z"></path></svg>',
			);
			break;
		case 'xltm':
			$svg = array(
				'font' => 'bb-icon-file-xltm',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-xltm</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM17.504 24c0.288 0 0.512 0.224 0.512 0.512v1.984c0 0.288-0.224 0.512-0.512 0.512h-4c-0.288 0-0.512-0.224-0.512-0.512v-1.984c0-0.288 0.224-0.512 0.512-0.512h4zM10.496 24c0.288 0 0.512 0.224 0.512 0.512v1.984c0 0.288-0.224 0.512-0.512 0.512h-4c-0.256 0-0.48-0.224-0.48-0.512v-1.984c0-0.288 0.224-0.512 0.48-0.512h4zM17.504 19.008c0.288 0 0.512 0.224 0.512 0.48v2.016c0 0.256-0.224 0.48-0.512 0.48h-4c-0.288 0-0.512-0.224-0.512-0.48v-2.016c0-0.256 0.224-0.48 0.512-0.48h4zM7.424 16.992l0.512 1.312h0.064l0.64-1.312h1.344l-1.216 2.432 1.248 2.56h-1.376l-0.64-1.28h-0.064l-0.64 1.28h-1.28l1.152-2.432-1.152-2.56h1.408z"></path></svg>',
			);
			break;
		case 'xltx':
			$svg = array(
				'font' => 'bb-icon-file-xltx',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-xltx</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16 13.984c1.376 0 2.496 1.12 2.496 2.528v0 8c0 1.376-1.12 2.496-2.496 2.496v0h-8c-1.376 0-2.496-1.12-2.496-2.496v0-8c0-1.408 1.12-2.528 2.496-2.528v0h8zM16 15.008h-8c-0.832 0-1.504 0.672-1.504 1.504v0 8c0 0.8 0.672 1.472 1.504 1.472v0h8c0.832 0 1.504-0.672 1.504-1.472v0-8c0-0.832-0.672-1.504-1.504-1.504v0zM15.488 23.008c0.288 0 0.512 0.224 0.512 0.48s-0.16 0.448-0.416 0.512h-3.072c-0.288 0-0.512-0.224-0.512-0.512 0-0.224 0.192-0.448 0.416-0.48h3.072zM11.008 23.008c0.256 0 0.48 0.224 0.48 0.48s-0.16 0.448-0.384 0.512h-3.104c-0.288 0-0.512-0.224-0.512-0.512 0-0.224 0.192-0.448 0.416-0.48h3.104zM15.488 20.992c0.288 0 0.512 0.224 0.512 0.512 0 0.224-0.16 0.448-0.416 0.48h-3.072c-0.288 0-0.512-0.224-0.512-0.48s0.192-0.448 0.416-0.512h3.072zM8.928 16.512l0.512 1.28h0.064l0.64-1.28h1.344l-1.216 2.4 1.216 2.592h-1.344l-0.64-1.312h-0.064l-0.64 1.312h-1.28l1.152-2.464-1.184-2.528h1.44zM15.488 19.008c0.288 0 0.512 0.224 0.512 0.48s-0.16 0.448-0.416 0.512h-3.072c-0.288 0-0.512-0.224-0.512-0.512 0-0.224 0.192-0.448 0.416-0.48h3.072zM15.488 16.992c0.288 0 0.512 0.224 0.512 0.512 0 0.224-0.16 0.448-0.416 0.48h-3.072c-0.288 0-0.512-0.224-0.512-0.48s0.192-0.448 0.416-0.512h3.072z"></path></svg>',
			);
			break;
		case 'xml':
			$svg = array(
				'font' => 'bb-icon-file-xml',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-xml</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM12.384 15.008c0.416 0 0.768 0.352 0.768 0.8v0 0.224h2.72c0.96 0 1.728 0.704 1.824 1.632v6.496c0 0.96-0.832 1.76-1.824 1.76v0h-2.72v0.288c0 0 0 0.032 0 0.032v0.032c-0.032 0.448-0.448 0.768-0.864 0.704v0l-5.568-0.608c-0.416-0.032-0.704-0.384-0.704-0.8v0-9.152c0-0.416 0.288-0.736 0.704-0.8v0l5.568-0.608c0.032 0 0.064 0 0.096 0zM15.872 16.736h-2.72v1.28c1.344 0.288 2.368 1.472 2.368 2.912s-1.024 2.624-2.368 2.944v1.344h2.72c0.544 0 1.024-0.416 1.088-0.928v-6.496c0-0.576-0.48-1.056-1.088-1.056v0zM14.784 21.28h-1.632v1.856c0.864-0.224 1.504-0.96 1.632-1.856zM13.152 18.72v1.856h1.632c-0.128-0.864-0.768-1.6-1.632-1.856z"></path></svg>',
			);
			break;
		case 'yaml':
			$svg = array(
				'font' => 'bb-icon-file-yaml',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-yaml</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16.992 11.488c1.344 0 2.432 1.056 2.496 2.336v9.184c0 1.216-0.864 2.24-2.016 2.464-0.224 1.088-1.152 1.952-2.304 2.016h-8.16c-1.344 0-2.432-1.024-2.496-2.336v-9.152c0-1.216 0.864-2.24 2.016-2.464 0.224-1.12 1.152-1.952 2.304-2.048h8.16zM7.232 23.008h-0.48l0.736 1.312v0.8h0.448v-0.8l0.736-1.312h-0.48l-0.448 0.864h-0.032l-0.48-0.864zM9.888 23.008h-0.512l-0.736 2.112h0.448l0.16-0.512h0.736l0.16 0.512h0.48l-0.736-2.112zM11.52 23.008h-0.512v2.112h0.384v-1.408h0.032l0.544 1.28h0.288l0.544-1.28h0.032v1.408h0.416v-2.112h-0.544l-0.576 1.408h-0.032l-0.576-1.408zM14.176 23.008h-0.416v2.112h1.376v-0.384h-0.96v-1.728zM16.992 12.512h-8c-0.64 0-1.184 0.416-1.408 0.992h7.424c1.312 0 2.4 1.024 2.496 2.336v8.576c0.544-0.192 0.928-0.672 0.992-1.28v-9.152c0-0.768-0.576-1.408-1.344-1.472h-0.16zM9.632 23.424l0.256 0.832h-0.544l0.256-0.832h0.032zM15.008 14.496h-8c-0.8 0-1.44 0.608-1.504 1.344v5.152h11.008v-4.992c0-0.768-0.608-1.408-1.376-1.504h-0.128zM14.016 19.008c0.256 0 0.48 0.224 0.48 0.48s-0.16 0.448-0.416 0.512h-6.080c-0.288 0-0.512-0.224-0.512-0.512 0-0.224 0.192-0.448 0.416-0.48h6.112zM14.016 16.992c0.256 0 0.48 0.224 0.48 0.512 0 0.224-0.16 0.448-0.416 0.48h-6.080c-0.288 0-0.512-0.224-0.512-0.48s0.192-0.448 0.416-0.512h6.112z"></path></svg>',
			);
			break;
		case 'mp4':
		case 'webm':
		case 'ogg':
		case 'mov':
			$svg = array(
				'font' => 'bb-icon-file-video',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-video</title><path d="M13.728 0.096c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.704 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 2.080h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.088 0.896 1.984 1.984 1.984v0h16c1.12 0 2.016-0.896 2.016-1.984v0-17.504c0-0.544-0.224-1.024-0.576-1.408v0l-6.272-6.496c-0.384-0.384-0.896-0.608-1.44-0.608v0zM12.704 18.080c1.28 0 2.304 1.056 2.304 2.304v0l1.472-1.088c0.448-0.352 1.056-0.256 1.408 0.192 0.128 0.16 0.192 0.384 0.192 0.608v4.992c0 0.544-0.448 0.992-0.992 0.992-0.224 0-0.448-0.064-0.608-0.192l-1.472-1.088c0 1.248-1.056 2.304-2.304 2.304v0h-5.408c-1.248 0-2.304-1.056-2.304-2.336v0-4.384c0-1.248 1.056-2.304 2.304-2.304v0h5.408zM12.704 19.008h-5.408c-0.736 0-1.376 0.64-1.376 1.376v0 4.384c0 0.768 0.64 1.408 1.376 1.408v0h5.408c0.768 0 1.376-0.64 1.376-1.408v0-4.384c0-0.736-0.608-1.376-1.376-1.376v0zM17.088 20.096l-2.016 1.472v2.016l2.016 1.504v-4.992z"></path></svg>',
			);
			break;
		case 'folder':
			$svg = array(
				'font' => 'bb-icon-folder-stacked',
				'svg'  => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="36" height="32" viewBox="0 0 36 32"><title>folder-stacked</title><path d="M14.912 0.64c1.44 0 2.784 0.512 3.872 1.472 0.512 0.448 1.184 0.736 1.888 0.8h9.344c3.136 0 5.664 2.464 5.824 5.568v19.744c0 1.984-1.6 3.552-3.552 3.552-0.128 0-0.224 0-0.352 0v0h-27.584c-2.336 0-4.224-1.792-4.352-4.096v-15.744c0-2.304 1.824-4.192 4.096-4.32h0.448v-2.624c0-2.336 1.824-4.224 4.096-4.352h6.272zM28.736 12.288c0-1.088-0.864-2.016-1.952-2.112h-22.432c-0.928 0-1.696 0.704-1.792 1.6v15.68c0 0.928 0.704 1.664 1.6 1.76h24.704c-0.064-0.224-0.096-0.512-0.128-0.768v-16.16zM14.912 3.2h-6.016c-0.928 0-1.696 0.704-1.76 1.6l-0.032 0.192v2.624h19.488c2.528 0 4.576 1.952 4.704 4.448v16.16c0 0.544 0.448 0.992 0.992 0.992 0.512 0 0.928-0.352 0.992-0.864v-19.616c0-1.728-1.344-3.136-3.040-3.264h-9.312c-1.312 0-2.592-0.448-3.616-1.248l-0.224-0.224c-0.544-0.448-1.216-0.736-1.92-0.8h-0.256z"></path></svg>',
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

	return apply_filters( 'bp_document_svg_icon', $svg[ $type ], $extension );
}

/**
 * Return the icon list.
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.0
 */
function bp_document_svg_icon_list() {

	$icons = array(
		'default_1'  => array(
			'icon'  => 'bb-icon-file',
			'title' => __( 'Default', 'buddyboss' ),
			'svg'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-default</title><path d="M13.728 0h-9.728c-2.208 0-4 1.792-4 4v24c0 2.208 1.792 4 4 4h16c2.208 0 4-1.792 4-4v-17.504c0-1.056-0.416-2.048-1.12-2.784l-6.272-6.496c-0.768-0.768-1.792-1.216-2.88-1.216zM4 1.984h9.728c0.544 0 1.056 0.224 1.44 0.64l6.272 6.464c0.352 0.384 0.576 0.896 0.576 1.408v17.504c0 1.12-0.896 2.016-2.016 2.016h-16c-1.088 0-1.984-0.896-1.984-2.016v-24c0-1.12 0.896-2.016 1.984-2.016z"></path></svg>',
		),
		'default_2'  => array(
			'icon'  => 'bb-icon-file-zip',
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

	return apply_filters( 'bp_document_svg_icon_list', $icons );
}

/**
 * Return the breadcrumbs.
 *
 * @param int $user_id
 * @param int $group_id
 *
 * @return string
 * @since BuddyBoss 1.4.0
 */
function bp_document_user_document_folder_tree_view_li_html( $user_id = 0, $group_id = 0 ) {

	global $wpdb, $bp;

	$document_folder_table = $bp->document->table_name_folder;

	$type = bb_filter_input_string( INPUT_GET, 'type' );
	$id   = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );

	if ( 0 === $group_id ) {
		$group_id = ( function_exists( 'bp_get_current_group_id' ) ) ? bp_get_current_group_id() : 0;
	}

	if ( 'group' === $type && ! $group_id ) {
		$group_id               = $id;
		$documents_folder_query = $wpdb->prepare( "SELECT * FROM {$document_folder_table} WHERE group_id = %d ORDER BY id DESC", $group_id );
	} elseif ( 'group' === $type && $group_id ) {
		$documents_folder_query = $wpdb->prepare( "SELECT * FROM {$document_folder_table} WHERE group_id = %d ORDER BY id DESC", $group_id );
	} else {
		$documents_folder_query = $wpdb->prepare( "SELECT * FROM {$document_folder_table} WHERE user_id = %d AND group_id = %d ORDER BY id DESC", $user_id, $group_id );
	}

	$data = $wpdb->get_results( $documents_folder_query, ARRAY_A ); // db call ok; no-cache ok;

	// Build array of item references:
	foreach ( $data as $key => &$item ) {
		$itemsByReference[ $item['id'] ] = &$item;
		// Children array.
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
 * @since BuddyBoss 1.4.0
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
		$output .= '<li data-id="' . esc_attr( $item['id'] ) . '" data-privacy="' . esc_attr( $item['privacy'] ) . '"><span id="' . esc_attr( $item['id'] ) . '" data-id="' . esc_attr( $item['id'] ) . '">' . stripslashes( $item['title'] ) . '</span>' . bp_document_folder_recursive_li_list( $item['children'], true ) . '</li>';
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
 * @since BuddyBoss 1.4.0
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
    ON d._id = c.id ORDER BY d.level ASC",
		$folder_id
	);

	$data = $wpdb->get_results( $documents_folder_query, ARRAY_A ); // db call ok; no-cache ok;
	$html = '';

	if ( ! empty( $data ) ) {
		$data  = array_reverse( $data );
		$html .= '<ul class="document-breadcrumb">';
		if ( bp_is_group() && bp_is_group_single() ) {
			$group = groups_get_group( array( 'group_id' => bp_get_current_group_id() ) );
			$link  = bp_get_group_permalink( $group ) . bp_get_document_slug();
			$html .= '<li><a href=" ' . $link . ' "> ' . __( 'Documents', 'buddyboss' ) . '</a></li>';
		} else {
			$link  = bp_displayed_user_domain() . bp_get_document_slug();
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
				$link = bp_displayed_user_domain() . bp_get_document_slug() . '/folders/' . $element['id'];
			} else {
				$group = groups_get_group( array( 'group_id' => $group_id ) );
				$link  = bp_get_group_permalink( $group ) . bp_get_document_slug() . '/folders/' . $element['id'];
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
 * @since BuddyBoss 1.4.0
 */
function bp_document_move_document_to_folder( $document_id = 0, $folder_id = 0, $group_id = 0 ) {

	global $wpdb, $bp;

	if ( 0 === $document_id ) {
		return false;
	}

	if ( (int) $document_id > 0 ) {
		$has_access = bp_document_user_can_edit( $document_id );
		if ( ! $has_access ) {
			return false;
		}
	}

	if ( (int) $folder_id > 0 ) {
		$has_access = bp_folder_user_can_edit( $folder_id );
		if ( ! $has_access ) {
			return false;
		}
	}

	if ( ! $group_id ) {
		$get_document = new BP_Document( $document_id );
		if ( $get_document->group_id > 0 ) {
			$group_id = $get_document->group_id;
		}
	}

	if ( $group_id > 0 ) {
		$destination_privacy = 'public';
	} elseif ( $folder_id > 0 ) {
		$destination_folder = BP_Document_Folder::get_folder_data( array( $folder_id ) );
		$destination_folder = ( ! empty( $destination_folder ) ) ? current( $destination_folder ) : array();
		if ( empty( $destination_folder ) ) {
			return false;
		}
		$destination_privacy = $destination_folder->privacy;
		// Update modify date for destination folder.
		$destination_folder_update                = new BP_Document_Folder( $folder_id );
		$destination_folder_update->date_modified = bp_core_current_time();
		$destination_folder_update->save();
	} else {
		// Keep the destination privacy same as the previous privacy.
		$document_object     = new BP_Document( $document_id );
		$destination_privacy = $document_object->privacy;
	}

	if ( empty( $destination_privacy ) ) {
		$destination_privacy = 'loggedin';
	}

	$document                = new BP_Document( $document_id );
	$document->folder_id     = $folder_id;
	$document->group_id      = $group_id;
	$document->date_modified = bp_core_current_time();
	$document->privacy       = ( $group_id > 0 ) ? 'grouponly' : $destination_privacy;
	$document->menu_order    = 0;
	$document->save();

	// Update document activity privacy.
	if ( ! empty( $document ) && ! empty( $document->attachment_id ) ) {

		$document_attachment = $document->attachment_id;
		$parent_activity_id  = get_post_meta( $document_attachment, 'bp_document_parent_activity_id', true );

		// If found need to make this activity to main activity.
		$child_activity_id = get_post_meta( $document_attachment, 'bp_document_activity_id', true );

		if ( bp_is_active( 'activity' ) ) {

			// Single document upload.
			if ( empty( $child_activity_id ) ) {
				$activity = new BP_Activity_Activity( (int) $parent_activity_id );
				// Update activity data.
				if ( bp_activity_user_can_delete( $activity ) ) {
					// Make the activity document own.
					$status                      = bp_is_active( 'groups' ) ? bp_get_group_status( groups_get_group( $activity->item_id ) ) : '';
					$activity->hide_sitewide     = ( 'groups' === $activity->component && ( 'hidden' === $status || 'private' === $status ) ) ? 1 : 0;
					$activity->secondary_item_id = 0;
					$activity->privacy           = $destination_privacy;
					$activity->save();
				}

				bp_activity_delete_meta( (int) $parent_activity_id, 'bp_document_folder_activity' );
				if ( $folder_id > 0 ) {
					// Update to moved album id.
					bp_activity_update_meta( (int) $parent_activity_id, 'bp_document_folder_activity', (int) $folder_id );
				}

				// We have to change child activity privacy when we move the document while at a time multiple document uploaded.
			} else {

				$parent_activity_document_ids = bp_activity_get_meta( $parent_activity_id, 'bp_document_ids', true );

				// Get the parent activity.
				$parent_activity = new BP_Activity_Activity( (int) $parent_activity_id );

				if ( bp_activity_user_can_delete( $parent_activity ) && ! empty( $parent_activity_document_ids ) ) {
					$parent_activity_document_ids = explode( ',', $parent_activity_document_ids );

					// Do the changes if only one media is attached to a activity.
					if ( 1 === count( $parent_activity_document_ids ) ) {

						// Get the document object.
						$document = new BP_Document( $document_id );

						// Need to delete child activity.
						$need_delete = $document->activity_id;

						$document_album = (int) $document->album_id;

						// Update document activity id to parent activity id.
						$document->activity_id  = $parent_activity_id;
						$document->date_created = bp_core_current_time();
						$document->save();

						bp_activity_update_meta( $parent_activity_id, 'bp_document_ids', $document_id );

						// Update attachment meta.
						delete_post_meta( $document->attachment_id, 'bp_document_activity_id' );
						update_post_meta( $document->attachment_id, 'bp_document_parent_activity_id', $parent_activity_id );
						update_post_meta( $document->attachment_id, 'bp_document_upload', 1 );
						update_post_meta( $document->attachment_id, 'bp_document_saved', 1 );

						bp_activity_delete_meta( $parent_activity_id, 'bp_document_folder_activity' );
						if ( $document_album > 0 ) {
							bp_activity_update_meta( $parent_activity_id, 'bp_document_folder_activity', $document_album );
						}

						// Update the activity meta first otherwise it will delete the document.
						bp_activity_update_meta( $need_delete, 'bp_document_ids', '' );

						// Delete child activity no need anymore because assigned all the data to parent activity.
						bp_activity_delete( array( 'id' => $need_delete ) );

						// Update parent activity privacy to destination privacy.
						$parent_activity->privacy = $destination_privacy;
						$parent_activity->save();

					} elseif ( count( $parent_activity_document_ids ) > 1 ) {

						// Get the child activity.
						$activity = new BP_Activity_Activity( (int) $child_activity_id );

						// Update activity data.
						if ( bp_activity_user_can_delete( $activity ) ) {

							// Make the activity document own.
							$status                      = bp_is_active( 'groups' ) ? bp_get_group_status( groups_get_group( $activity->item_id ) ) : '';
							$activity->hide_sitewide     = ( 'groups' === $activity->component && ( 'hidden' === $status || 'private' === $status ) ) ? 1 : 0;
							$activity->secondary_item_id = 0;
							$activity->privacy           = $destination_privacy;
							$activity->save();

							bp_activity_update_meta( (int) $child_activity_id, 'bp_document_ids', $document_id );

							// Update attachment meta.
							delete_post_meta( $document_attachment, 'bp_document_activity_id' );
							update_post_meta( $document_attachment, 'bp_document_parent_activity_id', $child_activity_id );
							update_post_meta( $document_attachment, 'bp_document_upload', 1 );
							update_post_meta( $document_attachment, 'bp_document_saved', 1 );

							// Make the child activity as parent activity.
							bp_activity_delete_meta( $child_activity_id, 'bp_document_activity' );

							bp_activity_delete_meta( (int) $child_activity_id, 'bp_document_folder_activity' );
							if ( $folder_id > 0 ) {
								bp_activity_update_meta( (int) $child_activity_id, 'bp_document_folder_activity', (int) $folder_id );
							}

							// Remove the document id from the parent activity meta.
							$key = array_search( $document_id, $parent_activity_document_ids );
							if ( false !== $key ) {
								unset( $parent_activity_document_ids[ $key ] );
							}

							// Update the activity meta.
							if ( ! empty( $parent_activity_document_ids ) ) {
								$activity_document_ids = implode( ',', $parent_activity_document_ids );
								bp_activity_update_meta( $parent_activity_id, 'bp_document_ids', $activity_document_ids );
							} else {
								bp_activity_update_meta( $parent_activity_id, 'bp_document_ids', '' );
							}
						}
					}
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
 * @since BuddyBoss 1.4.0
 */
function bp_document_get_visibility_levels() {

	/**
	 * Filters the document visibility levels out of the $bp global.
	 *
	 * @param array $visibility_levels Array of visibility levels.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	return apply_filters( 'bp_document_get_visibility_levels', buddypress()->document->visibility_levels );
}

/**
 * Handles the actual rename process
 *
 * @param [type] $post_id
 * @return void
 */
function bp_document_get_file_parts( $post_id ) {
	preg_match( '~([^/]+)\.([^\.]+)$~', get_attached_file( $post_id ), $file_parts ); // extract current filename and extension
	return array(
		'filename'  => $file_parts[1],
		'extension' => $file_parts[2],
	);
}

/**
 * Returns the attachment URL and sizes URLs, in case of an image
 *
 * @param [type] $attachment_id
 * @return void
 */
function bp_document_get_attachment_urls( $attachment_id ) {
	$urls = array( wp_get_attachment_url( $attachment_id ) );
	if ( wp_attachment_is_image( $attachment_id ) ) {
		foreach ( get_intermediate_image_sizes() as $size ) {
			$image  = wp_get_attachment_image_src( $attachment_id, $size );
			$urls[] = $image[0];
		}
	}

	return array_unique( $urls );
}

/**
 * Convert filename to post title
 *
 * @param [type] $filename
 * @return void
 */
function bp_document_filename_to_title( $filename ) {
	// return ucwords( preg_replace('~[^a-zA-Z0-9]~', ' ', $filename) );
	return $filename;
}

/**
 * Unserializes a variable until reaching a non-serialized value
 *
 * @param [type] $var
 * @return void
 */
function bp_document_unserialize_deep( $var ) {
	while ( is_serialized( $var ) ) {
		$var = @unserialize( $var );
	}

	return $var;
}

/**
 * Replace the media url and fix serialization if necessary
 *
 * @param [type] $subj
 * @param [type] $searches
 * @param [type] $replaces
 * @return void
 */
function bp_document_replace_media_urls( $subj, &$searches, &$replaces ) {
	$subj = is_object( $subj ) ? clone $subj : $subj;

	if ( ! is_scalar( $subj ) && is_countable( $subj ) && count( $subj ) ) {
		foreach ( $subj as &$item ) {
			$item = bp_document_replace_media_urls( $item, $searches, $replaces );
		}
	} else {
		$subj = is_string( $subj ) ? str_replace( $searches, $replaces, $subj ) : $subj;
	}

	return $subj;
}

/**
 * Get all options
 *
 * @return void
 */
function bp_document_get_all_options() {
	return $GLOBALS['wpdb']->get_results( "SELECT option_name as name, option_value as value FROM {$GLOBALS['wpdb']->options}", ARRAY_A );
}

/**
 * This function will rename the document name.
 *
 * @param int    $document_id
 * @param int    $attachment_document_id
 * @param string $title
 *
 * @return bool|int
 * @since BuddyBoss 1.4.0
 */
function bp_document_rename_file( $document_id = 0, $attachment_document_id = 0, $title = '', $backend = false ) {

	global $wpdb, $bp;

	if ( 0 === $document_id && '' === $title ) {
		return false;
	}

	if ( (int) $document_id > 0 ) {
		$has_access = bp_document_user_can_edit( $document_id );
		if ( ! $has_access ) {
			return false;
		}
	}

	$file_name    = $title;
	$new_filename = $title;

	// Variables.
	$post                     = get_post( $attachment_document_id );
	$file_parts               = bp_document_get_file_parts( $attachment_document_id );
	$old_filename             = $file_parts['filename'];
	$new_filename_unsanitized = $new_filename;

	// sanitizing file name (using sanitize_title because sanitize_file_name doesn't remove accents).
	$new_filename = sanitize_file_name( $new_filename );

	$file_abs_path     = get_attached_file( $post->ID );
	$file_abs_dir      = dirname( $file_abs_path );
	$new_file_abs_path = preg_replace( '~[^/]+$~', $new_filename . '.' . $file_parts['extension'], $file_abs_path );

	$file_rel_path     = get_post_meta( $post->ID, '_wp_attached_file', 1 );
	$new_file_rel_path = preg_replace( '~[^/]+$~', $new_filename . '.' . $file_parts['extension'], $file_rel_path );

	$uploads_path = wp_upload_dir();
	$uploads_path = $uploads_path['basedir'];

	// attachment miniatures.
	$searches = bp_document_get_attachment_urls( $attachment_document_id );

	// Validations.
	if ( ! $post ) {
		return __( 'Post with ID ' . $attachment_document_id . ' does not exist!', 'buddyboss' );
	}
	if ( $post && $post->post_type != 'attachment' ) {
		return __( 'Post with ID ' . $attachment_document_id . ' is not an attachment!', 'buddyboss' );
	}
	if ( ! $new_filename ) {
		return __( 'The document name is empty!', 'buddyboss' );
	}
	if ( $new_filename != sanitize_file_name( $new_filename ) ) {
		return __( 'Bad characters or invalid document name!', 'buddyboss' );
	}
	if ( file_exists( $new_file_abs_path ) ) {
		return __( 'A file with that name already exists in the containing folder!', 'buddyboss' );
	}
	if ( ! is_writable( $file_abs_dir ) ) {
		return __( 'The document containing directory is not writable!', 'buddyboss' );
	}

	$my_post = array(
		'ID'         => $post->ID,
		'post_title' => bp_document_filename_to_title( $new_filename_unsanitized ),
		'guid'       => preg_replace( '~[^/]+$~', $new_filename . '.' . $file_parts['extension'], $post->guid ),
	);

	$post_id = wp_update_post( $my_post );

	// Change attachment post metas & rename files.
	foreach ( get_intermediate_image_sizes() as $size ) {
		$size_data = image_get_intermediate_size( $attachment_document_id, $size );
		$attachment_path = ! empty( $size_data['path'] ) ? $uploads_path . DIRECTORY_SEPARATOR . $size_data['path'] : '';
		if ( ! empty( $attachment_path ) && file_exists( $attachment_path ) ) {
			@unlink( $attachment_path );
		}
	}

	if ( ! @rename( $file_abs_path, $new_file_abs_path ) ) {
		return __( 'File renaming error!', 'buddyboss' );
	}

	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
	}
	update_post_meta( $attachment_document_id, '_wp_attached_file', $new_file_rel_path );
	wp_update_attachment_metadata( $attachment_document_id, wp_generate_attachment_metadata( $attachment_document_id, $new_file_abs_path ) );

	// Replace the old with the new media link in the content of all posts and metas.
	$replaces = bp_document_get_attachment_urls( $attachment_document_id );

	$i          = 0;
	$post_types = get_post_types();
	unset( $post_types['attachment'] );

	while ( $posts = get_posts(
		array(
			'post_type'              => $post_types,
			'post_status'            => 'any',
			'numberposts'            => 100,
			'offset'                 => $i * 100,
			'update_post_term_cache' => false,
		)
	) ) {
		foreach ( $posts as $post ) {
			// Updating post content if necessary.
			$new_post                 = array( 'ID' => $post->ID );
			$new_post['post_content'] = str_replace( '\\', '\\\\', $post->post_content );
			$new_post['post_content'] = str_replace( $searches, $replaces, $new_post['post_content'] );
			if ( $new_post['post_content'] != $post->post_content ) {
				wp_update_post( $new_post );
			}

			// Updating post metas if necessary.
			$metas = get_post_meta( $post->ID );
			foreach ( $metas as $key => $meta ) {
				$meta[0]  = bp_document_unserialize_deep( $meta[0] );
				$new_meta = bp_document_replace_media_urls( $meta[0], $searches, $replaces );
				if ( $new_meta != $meta[0] ) {
					update_post_meta( $post->ID, $key, $new_meta, $meta[0] );
				}
			}
		}

		$i++;
	}

	// Updating options if necessary.
	$options = bp_document_get_all_options();
	foreach ( $options as $option ) {
		$option['value'] = bp_document_unserialize_deep( $option['value'] );
		$new_option      = bp_document_replace_media_urls( $option['value'], $searches, $replaces );
		if ( $new_option != $option['value'] ) {
			update_option( $option['name'], $new_option );
		}
	}

	$document = new BP_Document( $document_id );
	if ( empty( $document->id ) ) {
		return false;
	}

	$document->title         = $new_filename;
	$document->date_modified = bp_core_current_time();
	$document->attachment_id = $attachment_document_id;
	$document->save();

	bp_document_update_meta( $document_id, 'file_name', $new_filename );

	$response = apply_filters(
		'bp_document_rename_file',
		array(
			'document_id'            => $document_id,
			'attachment_document_id' => $attachment_document_id,
			'title'                  => $new_filename,
		)
	);

	if ( file_exists( $file_abs_path ) ) {
		@unlink( $file_abs_path );
	}

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
 * @since BuddyBoss 1.4.0
 */
function bp_document_rename_folder( $folder_id = 0, $title = '', $privacy = '' ) {

	global $wpdb, $bp;

	if ( 0 === $folder_id && '' === $title ) {
		return false;
	}

	if ( (int) $folder_id > 0 ) {
		$has_access = bp_folder_user_can_edit( $folder_id );
		if ( ! $has_access ) {
			return false;
		}
	}

	if ( strpbrk( $title, '\\/?%*:|"<>' ) !== false ) {
		return false;
	}

	$title = wp_strip_all_tags( $title );

	$folder = new BP_Document_Folder( $folder_id );
	if ( empty( $folder->id ) ) {
		return false;
	}

	$folder->title         = $title;
	$folder->date_modified = bp_core_current_time();
	$folder->save();

	bp_document_update_privacy( $folder_id, $privacy, 'folder' );

	return $folder_id;
}

/**
 * This function will rename the folder name.
 *
 * @param int $folder_id
 *
 * @return bool|int
 * @since BuddyBoss 1.4.0
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
 * @param $group_id
 *
 * @return bool
 * @since BuddyBoss 1.4.0
 */
function bp_document_move_folder_to_folder( $folder_id, $destination_folder_id, $group_id = 0 ) {

	global $wpdb, $bp;

	if ( '' === $folder_id || '' === $destination_folder_id ) {
		return false;
	}

	if ( (int) $folder_id > 0 ) {
		$has_access = bp_folder_user_can_edit( $folder_id );
		if ( ! $has_access ) {
			return false;
		}
	}

	if ( (int) $destination_folder_id > 0 ) {
		$has_destination_access = bp_folder_user_can_edit( $destination_folder_id );
		if ( ! $has_destination_access ) {
			return false;
		}
	}

	if ( ! $group_id ) {
		$get_folder = new BP_Document_Folder( $folder_id );
		if ( $get_folder->group_id > 0 ) {
			$group_id = $get_folder->group_id;
		}
	}

	$destination_privacy = 'loggedin';
	if ( $group_id > 0 ) {
		$destination_privacy = 'grouponly';
	} elseif ( $destination_folder_id > 0 ) {
		$destination_folder  = BP_Document_Folder::get_folder_data( array( $destination_folder_id ) );
		$destination_privacy = $destination_folder[0]->privacy;

		// Update modify date for destination folder.
		$destination_folder_update                = new BP_Document_Folder( $destination_folder_id );
		$destination_folder_update->date_modified = bp_core_current_time();
		$destination_folder_update->save();
	}

	// Update main parent folder.
	$update_folder                = new BP_Document_Folder( $folder_id );
	$update_folder->privacy       = $destination_privacy;
	$update_folder->parent        = $destination_folder_id;
	$update_folder->date_modified = bp_core_current_time();
	$update_folder->save();

	// Get all the documents of main folder.
	$document_ids = bp_document_get_folder_document_ids( $folder_id );
	if ( ! empty( $document_ids ) ) {
		foreach ( $document_ids as $id ) {
			// Update privacy of the document.
			$up_document          = new BP_Document( $id );
			$up_document->privacy = $destination_privacy;
			$up_document->save();

			// Update document activity privacy.
			$document = new BP_Document( $id );
			if ( ! empty( $document ) && ! empty( $document->attachment_id ) ) {
				$post_attachment = $document->attachment_id;
				$activity_id     = get_post_meta( $post_attachment, 'bp_document_parent_activity_id', true );
				if ( ! empty( $activity_id ) && bp_is_active( 'activity' ) ) {
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
		$update_folder          = new BP_Document_Folder( $child );
		$update_folder->privacy = $destination_privacy;
		$update_folder->save();

		// Get all the documents of particular folder.
		$document_ids = bp_document_get_folder_document_ids( $child );

		if ( ! empty( $document_ids ) ) {
			foreach ( $document_ids as $id ) {

				// Update privacy of the document.
				$up_document          = new BP_Document( $id );
				$up_document->privacy = $destination_privacy;
				$up_document->save();

				// Update document activity privacy.
				$document = new BP_Document( $id );
				if ( ! empty( $document ) && ! empty( $document->attachment_id ) ) {
					$post_attachment = $document->attachment_id;
					$activity_id     = get_post_meta( $post_attachment, 'bp_document_parent_activity_id', true );
					if ( ! empty( $activity_id ) && bp_is_active( 'activity' ) ) {
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
 * @since BuddyBoss 1.4.0
 */
function bp_document_update_privacy( $document_id = 0, $privacy = '', $type = 'folder' ) {

	global $wpdb, $bp;

	if ( '' === $document_id || '' === $privacy ) {
		return false;
	}

	if ( ! is_user_logged_in() ) {
		return false;
	}

	if ( 'folder' === $type ) {

		if ( (int) $document_id > 0 ) {
			$has_access = bp_folder_user_can_edit( $document_id );
			if ( ! $has_access ) {
				return false;
			}
		}

		$update_folder                = new BP_Document_Folder( $document_id );
		$update_folder->privacy       = $privacy;
		$update_folder->date_modified = bp_core_current_time();
		$update_folder->save();

		// Get main folder's child folders.
		$get_children = bp_document_get_folder_children( $document_id );
		if ( ! empty( $get_children ) ) {
			foreach ( $get_children as $child ) {

				$update_folder          = new BP_Document_Folder( $child );
				$update_folder->privacy = $privacy;
				$update_folder->save();

				// Get current folder's documents.
				$child_document_ids = bp_document_get_folder_document_ids( $child );
				if ( ! empty( $child_document_ids ) ) {
					foreach ( $child_document_ids as $child_document_id ) {

						$update_child_document          = new BP_Document( $child_document_id );
						$update_child_document->privacy = $privacy;
						$update_child_document->save();

						$document = new BP_Document( $child_document_id );
						if ( ! empty( $document ) && ! empty( $document->attachment_id ) ) {
							$post_attachment = $document->attachment_id;
							$activity_id     = get_post_meta( $post_attachment, 'bp_document_parent_activity_id', true );
							if ( ! empty( $activity_id ) && bp_is_active( 'activity' ) ) {
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
				$update_document          = new BP_Document( $document_id );
				$update_document->privacy = $privacy;
				$update_document->save();

				$document = new BP_Document( $document_id );
				if ( ! empty( $document ) && ! empty( $document->attachment_id ) ) {
					$post_attachment = $document->attachment_id;
					$activity_id     = get_post_meta( $post_attachment, 'bp_document_parent_activity_id', true );
					if ( ! empty( $activity_id ) && bp_is_active( 'activity' ) ) {
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

		if ( (int) $document_id > 0 ) {
			$has_access = bp_document_user_can_edit( $document_id );
			if ( ! $has_access ) {
				return false;
			}
		}

		$update_document                = new BP_Document( $document_id );
		$update_document->privacy       = $privacy;
		$update_document->date_modified = bp_core_current_time();
		$update_document->save();

		$document = new BP_Document( $document_id );
		if ( ! empty( $document ) && ! empty( $document->attachment_id ) ) {
			$post_attachment = $document->attachment_id;
			$activity_id     = get_post_meta( $post_attachment, 'bp_document_parent_activity_id', true );
			if ( bp_is_active( 'activity' ) && ! empty( $activity_id ) ) {
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
 * @param int $folder_id Folder ID.
 *
 * @return array
 * @since BuddyBoss 1.4.0
 */
function bp_document_get_folder_document_ids( $folder_id ) {
	global $wpdb, $bp;
	static $cache = array();

	if ( ! isset( $cache[ $folder_id ] ) ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result              = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$bp->document->table_name} WHERE folder_id = %d", $folder_id ) ) );
		$cache[ $folder_id ] = $result;
	} else {
		$result = $cache[ $folder_id ];
	}

	return $result;
}

/**
 * Return download link of the document.
 *
 * @param int $attachment_id Attachment ID.
 * @param int $document_id   Document ID.
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.0
 */
function bp_document_download_link( $attachment_id, $document_id ) {

	if ( empty( $attachment_id ) ) {
		return;
	}

	$link = site_url() . '/?attachment=' . $attachment_id . '&document_type=document&download_document_file=1' . '&document_file=' . $document_id;

	return apply_filters( 'bp_document_download_link', $link, $attachment_id );

}

/**
 * Return download link of the folder.
 *
 * @param $folder_id
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.0
 */
function bp_document_folder_download_link( $folder_id ) {

	if ( empty( $folder_id ) ) {
		return;
	}

	$link = site_url() . '/?attachment=' . $folder_id . '&document_type=folder&download_document_file=1&document_file=' . $folder_id;

	return apply_filters( 'bp_document_folder_download_link', $link, $folder_id );

}


/**
 * Return all the allowed document extensions.
 *
 * @return array
 * @since BuddyBoss 1.4.0
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
 * @param int $folder_id Folder ID.
 *
 * @return array|object|null
 * @since BuddyBoss 1.4.0
 */
function bp_document_get_folder_attachment_ids( $folder_id ) {
	global $bp, $wpdb;
	static $cache = array();

	$table = $bp->document->table_name;

	if ( ! isset( $cache[ $folder_id ] ) ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$documents_attachment_query = $wpdb->prepare( "SELECT attachment_id FROM {$table} WHERE folder_id = %d", $folder_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$data                = $wpdb->get_results( $documents_attachment_query );
		$cache[ $folder_id ] = $data;
	} else {
		$data = $cache[ $folder_id ];
	}

	return $data;

}

/**
 * Return all the children folder of the given folder.
 *
 * @param int $folder_id Folder ID.
 *
 * @return array
 * @since BuddyBoss 1.4.0
 */
function bp_document_get_folder_children( $folder_id ) {
	global $bp, $wpdb;
	static $cache = array();

	$table = $bp->document->table_name_folder;

	if ( ! isset( $cache[ $folder_id ] ) ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query = $wpdb->prepare( "SELECT id FROM `{$table}` WHERE FIND_IN_SET(`id`, ( SELECT GROUP_CONCAT(Level SEPARATOR ',') FROM ( SELECT @Ids := ( SELECT GROUP_CONCAT(`id` SEPARATOR ',') FROM `{$table}` WHERE FIND_IN_SET(`parent`, @Ids) ) Level FROM `{$table}` JOIN (SELECT @Ids := %d) temp1 ) temp2 ))", $folder_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$data                = array_map( 'intval', $wpdb->get_col( $query ) );
		$cache[ $folder_id ] = $data;
	} else {
		$data = $cache[ $folder_id ];
	}

	return $data;
}

/**
 * Return root folder of the given user.
 *
 * @param int $user_id User ID.
 *
 * @return array
 * @since BuddyBoss 1.4.0
 */
function bp_document_get_user_root_folders( $user_id ) {
	global $bp, $wpdb;
	$table = $bp->document->table_name_folder;
	return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE user_id = %d", $user_id ) ) );
}

/**
 * Return root folder of the given group.
 *
 * @param $group_id
 *
 * @return array
 * @since BuddyBoss 1.4.0
 */
function bp_document_get_group_root_folders( $group_id ) {
	global $bp, $wpdb;
	$table = $bp->document->table_name_folder;
	return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE group_id = %d", $group_id ) ) );
}

/**
 * Return root parent of the given child folder.
 *
 * @param $child_id
 *
 * @return string|null
 * @since BuddyBoss 1.4.0
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
    JOIN {$table} ON id <> 0
    ) tmp2
JOIN {$table} f ON tmp2._id = f.id
WHERE f.parent = 0",
			$child_id
		)
	);

	return $parent_id;
}

/**
 * Update activity document privacy based on activity.
 *
 * @param int    $activity_id Activity ID.
 * @param string $privacy     Privacy
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_update_activity_privacy( $activity_id = 0, $privacy = '' ) {
	global $wpdb, $bp;

	if ( empty( $activity_id ) || empty( $privacy ) ) {
		return;
	}

	// Update privacy for the documents which are uploaded in root of the documents.
	$document_ids = bp_activity_get_meta( $activity_id, 'bp_document_ids', true );
	if ( ! empty( $document_ids ) ) {
		$document_ids = explode( ',', $document_ids );
		if ( ! empty( $document_ids ) ) {
			foreach ( $document_ids as $id ) {
				$document = new BP_Document( $id );
				if ( empty( $document->folder_id ) ) {
					// Update privacy of the document.
					$up_document          = new BP_Document( $id );
					$up_document->privacy = $privacy;
					$up_document->save();
				}
			}
		}
	}
}

/**
 * Set bb_documents folder for the document upload directory.
 *
 * @param $pathdata
 *
 * @return mixed
 * @since BuddyBoss 1.4.1
 */
function bp_document_upload_dir( $pathdata ) {
	if ( isset( $_POST['action'] ) && 'document_document_upload' === $_POST['action'] ) { // WPCS: CSRF ok, input var ok.

		if ( empty( $pathdata['subdir'] ) ) {
			$pathdata['path']   = $pathdata['path'] . '/bb_documents';
			$pathdata['url']    = $pathdata['url'] . '/bb_documents';
			$pathdata['subdir'] = '/bb_documents';
		} else {
			$new_subdir = '/bb_documents' . $pathdata['subdir'];

			$pathdata['path']   = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['path'] );
			$pathdata['url']    = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['url'] );
			$pathdata['subdir'] = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['subdir'] );
		}
	}
	return $pathdata;
}

/**
 * Set bb_documents folder for the document upload directory.
 *
 * @param $pathdata
 *
 * @return mixed
 * @since BuddyBoss 1.4.1
 */
function bp_document_upload_dir_script( $pathdata ) {

	if ( empty( $pathdata['subdir'] ) ) {
		$pathdata['path']   = $pathdata['path'] . '/bb_documents';
		$pathdata['url']    = $pathdata['url'] . '/bb_documents';
		$pathdata['subdir'] = '/bb_documents';
	} else {
		$new_subdir = '/bb_documents' . $pathdata['subdir'];

		$pathdata['path']   = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['path'] );
		$pathdata['url']    = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['url'] );
		$pathdata['subdir'] = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['subdir'] );
	}
	return $pathdata;
}

/**
 * Filter headers for IE to fix issues over SSL.
 *
 * IE bug prevents download via SSL when Cache Control and Pragma no-cache headers set.
 *
 * @param array $headers HTTP headers.
 * @return array
 */
function bp_document_ie_nocache_headers_fix( $headers ) {
	if ( is_ssl() && ! empty( $GLOBALS['is_IE'] ) ) {
		$headers['Cache-Control'] = 'private';
		unset( $headers['Pragma'] );
	}
	return $headers;
}

/**
 * Get default scope for the document.
 *
 * @since BuddyBoss 1.4.4
 *
 * @param string $scope Default scope.
 *
 * @return string
 */
function bp_document_default_scope( $scope = 'all' ) {
	$new_scope = array();

	$allowed_scopes = array( 'public', 'all' );
	if ( is_user_logged_in() && bp_is_active( 'friends' ) && bp_is_profile_document_support_enabled() ) {
		$allowed_scopes[] = 'friends';
	}

	if ( bp_is_active( 'groups' ) && bp_is_group_document_support_enabled() ) {
		$allowed_scopes[] = 'groups';
	}

	if ( ( is_user_logged_in() || bp_is_user_document() ) && bp_is_profile_document_support_enabled() ) {
		$allowed_scopes[] = 'personal';
	}

	if ( ( 'all' === $scope || empty( $scope ) ) && bp_is_document_directory() ) {
		$new_scope[] = 'public';

		if ( is_user_logged_in() && bp_is_active( 'friends' ) && bp_is_profile_document_support_enabled() ) {
			$new_scope[] = 'friends';
		}

		if ( bp_is_active( 'groups' ) && bp_is_group_document_support_enabled() ) {
			$new_scope[] = 'groups';
		}

		if ( is_user_logged_in() && bp_is_profile_document_support_enabled() ) {
			$new_scope[] = 'personal';
		}
	} elseif ( bp_is_user_document() && ( 'all' === $scope || empty( $scope ) ) ) {
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
	 * @since BuddyBoss 1.4.4
	 */
	$new_scope = apply_filters( 'bp_document_default_scope', $new_scope );

	return implode( ',', $new_scope );

}

/**
 * Convert number of bytes largest unit bytes will fit into.
 *
 * It is easier to read 1 KB than 1024 bytes and 1 MB than 1048576 bytes. Converts
 * number of bytes to human readable number by taking the number of that unit
 * that the bytes will go into it. Supports TB value.
 *
 * Please note that integers in PHP are limited to 32 bits, unless they are on
 * 64 bit architecture, then they have 64 bit size. If you need to place the
 * larger size then what PHP integer type will hold, then use a string. It will
 * be converted to a double, which should always have 64 bit length.
 *
 * Technically the correct unit names for powers of 1024 are KiB, MiB etc.
 *
 * @since BuddyBoss 1.4.7
 *
 * @param int|string $bytes    Number of bytes. Note max integer size for integers.
 * @param int        $decimals Optional. Precision of number of decimal places. Default 0.
 * @return string|false False on failure. Number string on success.
 */
function bp_document_size_format( $bytes, $decimals = 0 ) {
	$quant = array(
		/* translators: Memory unit for terabyte. */
		_x( 'TB', 'memory unit', 'buddyboss' ) => TB_IN_BYTES,
		/* translators: Memory unit for gigabyte. */
		_x( 'GB', 'memory unit', 'buddyboss' ) => GB_IN_BYTES,
		/* translators: Memory unit for megabyte. */
		_x( 'MB', 'memory unit', 'buddyboss' ) => MB_IN_BYTES,
		/* translators: Memory unit for kilobyte. */
		_x( 'KB', 'memory unit', 'buddyboss' ) => KB_IN_BYTES,
		/* translators: Memory unit for byte. */
		_x( 'B', 'memory unit', 'buddyboss' )  => 1,
	);

	if ( 0 === $bytes ) {
		/* translators: Memory unit for byte. */
		return bp_core_number_format( 0, $decimals ) . ' ' . _x( 'B', 'memory unit', 'buddyboss' );
	}

	foreach ( $quant as $unit => $mag ) {
		if ( doubleval( $bytes ) >= $mag ) {
			return bp_core_number_format( $bytes / $mag, $decimals ) . ' ' . $unit;
		}
	}

	return false;
}

/**
 * Get document id for the attachment.
 *
 * @since BuddyBoss 1.5.5
 *
 * @param integer $attachment_id Attachment ID.
 *
 * @return array|bool
 */
function bp_get_attachment_document_id( $attachment_id = 0 ) {
	global $bp, $wpdb;

	if ( ! $attachment_id ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$attachment_document_id = (int) $wpdb->get_var( "SELECT DISTINCT d.id FROM {$bp->document->table_name} d WHERE d.attachment_id = {$attachment_id}" );

	return $attachment_document_id;
}

/**
 * Check given document is activity comment document.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param object|int $document document object or id of the document.
 *
 * @return bool
 */
function bp_document_is_activity_comment_document( $document ) {

	$is_comment_document = false;
	if ( is_object( $document ) ) {
		$document_activity_id = $document->activity_id;
	} else {
		$document             = new BP_Document( $document );
		$document_activity_id = $document->activity_id;
	}

	if ( bp_is_active( 'activity' ) ) {
		$activity = new BP_Activity_Activity( $document_activity_id );

		if ( $activity ) {
			if ( 'activity_comment' === $activity->type ) {
				$is_comment_document = true;
			}
			if ( $activity->secondary_item_id ) {
				$load_parent_activity = new BP_Activity_Activity( $activity->secondary_item_id );
				if ( $load_parent_activity ) {
					if ( 'activity_comment' === $load_parent_activity->type ) {
						$is_comment_document = true;
					}
				}
			}
		}
	} elseif ( $document_activity_id ) {
		$is_comment_document = true;
	}

	return $is_comment_document;

}

/**
 * Function to get document report link
 *
 * @since BuddyBoss 1.5.6
 *
 * @param array $args button arguments.
 *
 * @return mixed|void
 */
function bp_document_get_report_link( $args = array() ) {

	if ( ! bp_is_active( 'moderation' ) || ! is_user_logged_in() ) {
		return false;
	}

	$report_btn = bp_moderation_get_report_button(
		array(
			'id'                => 'document_report',
			'component'         => 'moderation',
			'must_be_logged_in' => true,
			'button_attr'       => array(
				'data-bp-content-id'   => ! empty( $args['id'] ) ? $args['id'] : 0,
				'data-bp-content-type' => BP_Moderation_Document::$moderation_type,
			),
		),
		true
	);

	return apply_filters( 'bp_document_get_report_link', $report_btn, $args );
}

/**
 * Whether user can show the document upload button.
 *
 * @param int $user_id  given user id.
 * @param int $group_id given group id.
 *
 * @since BuddyBoss 1.5.7
 *
 * @return bool
 */
function bb_document_user_can_upload( $user_id = 0, $group_id = 0 ) {

	if ( ( empty( $user_id ) && empty( $group_id ) ) || empty( $user_id ) ) {
		return false;
	}

	if ( ! empty( $group_id ) && bp_is_group_document_support_enabled() ) {
		return groups_can_user_manage_document( $user_id, $group_id );
	}

	if ( bp_is_profile_document_support_enabled() && bb_user_can_create_document() ) {
		return true;
	}

	return false;
}

/**
 * Get the frorum id based on document.
 *
 * @param int|string $document_id document id.
 *
 * @return mixed|void
 *
 * @since BuddyBoss 1.7.0
 */
function bp_document_get_forum_id( $document_id ) {

	if ( ! bp_is_active( 'forums' ) ) {
		return;
	}

	$forum_id              = 0;
	$forums_document_query = new WP_Query(
		array(
			'post_type'      => bbp_get_forum_post_type(),
			'fields'         => 'ids',
			'posts_per_page' => - 1,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'bp_document_ids',
					'value'   => $document_id,
					'compare' => 'LIKE',
				),
			),
		)
	);

	if ( ! empty( $forums_document_query->found_posts ) && ! empty( $forums_document_query->posts ) ) {
		foreach ( $forums_document_query->posts as $post_id ) {
			$document_ids = get_post_meta( $post_id, 'bp_document_ids', true );
			if ( ! empty( $document_ids ) ) {
				$document_ids = explode( ',', $document_ids );
				if ( in_array( $document_id, $document_ids ) ) { // phpcs:ignore
					$forum_id = $post_id;
					break;
				}
			}
		}
	}

	if ( ! $forum_id ) {
		$topics_document_query = new WP_Query(
			array(
				'post_type'      => bbp_get_topic_post_type(),
				'fields'         => 'ids',
				'posts_per_page' => - 1,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'bp_document_ids',
						'value'   => $document_id,
						'compare' => 'LIKE',
					),
				),
			)
		);

		if ( ! empty( $topics_document_query->found_posts ) && ! empty( $topics_document_query->posts ) ) {

			foreach ( $topics_document_query->posts as $post_id ) {
				$document_ids = get_post_meta( $post_id, 'bp_document_ids', true );

				if ( ! empty( $document_ids ) ) {
					$document_ids = explode( ',', $document_ids );
					if ( in_array( $document_id, $document_ids ) ) { // phpcs:ignore
						$forum_id = bbp_get_topic_forum_id( $post_id );
						break;
					}
				}
			}
		}
	}

	if ( ! $forum_id ) {
		$reply_document_query = new WP_Query(
			array(
				'post_type'      => bbp_get_reply_post_type(),
				'fields'         => 'ids',
				'posts_per_page' => - 1,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'bp_document_ids',
						'value'   => $document_id,
						'compare' => 'LIKE',
					),
				),
			)
		);

		if ( ! empty( $reply_document_query->found_posts ) && ! empty( $reply_document_query->posts ) ) {

			foreach ( $reply_document_query->posts as $post_id ) {
				$document_ids = get_post_meta( $post_id, 'bp_document_ids', true );

				if ( ! empty( $document_ids ) ) {
					$document_ids = explode( ',', $document_ids );
					foreach ( $document_ids as $document_id ) {
						if ( in_array( $document_id, $document_ids ) ) { // phpcs:ignore
							$forum_id = bbp_get_reply_forum_id( $post_id );
							break;
						}
					}
				}
			}
		}
	}

	return apply_filters( 'bp_document_get_forum_id', $forum_id, $document_id );
}

/**
 * Return the thread id if document belongs to message.
 *
 * @param int|string $document_id document id to fetch the thread id.
 *
 * @return mixed|void
 *
 * @since BuddyBoss 1.7.0
 */
function bp_document_get_thread_id( $document_id ) {

	$thread_id = 0;

	if ( bp_is_active( 'messages' ) ) {
		$meta = array(
			array(
				'key'     => 'bp_document_ids',
				'value'   => $document_id,
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
			$document_object = new BP_Document( $document_id );
			if ( ! empty( $document_object->attachment_id ) ) {
				$thread_id = get_post_meta( $document_object->attachment_id, 'thread_id', true );
			}
		}
	}

	return apply_filters( 'bp_document_get_thread_id', $thread_id, $document_id );
}

/**
 * Return the document symlink path.
 *
 * @return string The symlink path.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_document_symlink_path() {

	$upload_dir = wp_upload_dir();
	$upload_dir = $upload_dir['basedir'];

	$platform_previews_path = $upload_dir . '/bb-platform-previews';
	if ( ! is_dir( $platform_previews_path ) ) {
		wp_mkdir_p( $platform_previews_path );
		chmod( $platform_previews_path, 0755 );
	}

	$document_symlinks_path = $platform_previews_path . '/' . md5( 'bb-documents' );
	if ( ! is_dir( $document_symlinks_path ) ) {
		wp_mkdir_p( $document_symlinks_path );
		chmod( $document_symlinks_path, 0755 );
	}

	return $document_symlinks_path;
}

/**
 * Delete document previews/symlinks.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_document_delete_document_previews() {
	$upload_dir = wp_upload_dir();
	$upload_dir = $upload_dir['basedir'];

	$parent_directory_name = $upload_dir . '/bb-platform-previews';
	if ( ! is_dir( $parent_directory_name ) ) {
		return;
	}

	$inner_directory_name = $parent_directory_name . '/' . md5( 'bb-documents' );
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
 * Create symlink for a document.
 *
 * @param object $document BP_Document Object.
 * @param string $size     Size of images.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_document_create_symlinks( $document, $size = '' ) {

	// Check if document is id of document, create document object.
	if ( ! $document instanceof BP_Document && is_int( $document ) ) {
		$document = new BP_Document( $document );
	}

	// Return if no document found.
	if ( empty( $document ) ) {
		return;
	}

	/**
	 * Filter here to allow/disallow document symlinks.
	 *
	 * @param bool   $do_symlink             Default true.
	 * @param int    $document_id            Document id
	 * @param int    $document_attachment_id Document attachment id.
	 * @param string $size                   Size.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	$do_symlink = apply_filters( 'bb_document_do_symlink', true, $document->id, $document->attachment_id, 'medium' );

	if ( $do_symlink ) {

		// Get documents previews symlink directory path.
		$document_symlinks_path = bp_document_symlink_path();
		$attachment_id          = $document->attachment_id;
		$extension              = bp_document_extension( $attachment_id );
		$attached_file          = get_attached_file( $attachment_id );
		$privacy                = $document->privacy;

		if ( in_array( $extension, bp_get_document_preview_music_extensions(), true ) ) {
			$attachment_path = $document_symlinks_path . '/' . md5( $document->id . $attachment_id . $privacy );
			if ( $document->group_id > 0 && bp_is_active( 'groups' ) ) {
				$group_object    = groups_get_group( $document->group_id );
				$group_status    = bp_get_group_status( $group_object );
				$attachment_path = $document_symlinks_path . '/' . md5( $document->id . $attachment_id . $group_status . $privacy );
			}
			if ( ! empty( $attached_file ) && file_exists( $attached_file ) && is_file( $attached_file ) && ! is_dir( $attached_file ) && ! file_exists( $attachment_path ) ) {
				if ( ! is_link( $attachment_path ) ) {
					// Generate Document Thumb Symlink.
					bb_core_symlink_generator( 'document', $document, $size, array(), $attached_file, $attachment_path );
				}
			}
		}

		if ( in_array( $extension, bp_get_document_preview_doc_extensions(), true ) ) {
			if ( '' !== $size ) {

				$attachment_path = $document_symlinks_path . '/' . md5( $document->id . $attachment_id . $privacy . $size );
				if ( $document->group_id > 0 && bp_is_active( 'groups' ) ) {
					$group_object    = groups_get_group( $document->group_id );
					$group_status    = bp_get_group_status( $group_object );
					$attachment_path = $document_symlinks_path . '/' . md5( $document->id . $attachment_id . $group_status . $privacy . $size );
				}

				$file = image_get_intermediate_size( $attachment_id, $size );

				if ( false === $file && 'pdf' !== $extension ) {
					bb_document_regenerate_attachment_thumbnails( $attachment_id );
					$file = image_get_intermediate_size( $attachment_id, $size );
				} elseif ( false === $file && 'pdf' === $extension ) {
					bp_document_generate_document_previews( $attachment_id );
					$file = image_get_intermediate_size( $attachment_id, $size );
				}

				// If the given size is not found then use the full image.
				if ( false === $file ) {
					$file = image_get_intermediate_size( $attachment_id, 'full' );
				}

				if ( false === $file ) {
					$file = image_get_intermediate_size( $attachment_id, 'original' );
				}

				if ( false === $file ) {
					$file = image_get_intermediate_size( $attachment_id, 'thumbnail' );
				}

				$attached_file_info = pathinfo( $attached_file );
				$file_path          = '';

				if ( $file && ! empty( $file['file'] ) && ! empty( $attached_file_info['dirname'] ) ) {
					$file_path = $attached_file_info['dirname'];
					$file_path = $file_path . '/' . $file['file'];
				}

				if ( $file && ! empty( $file_path ) && file_exists( $file_path ) && is_file( $file_path ) && ! is_dir( $file_path ) && ! file_exists( $attachment_path ) ) {
					if ( ! is_link( $attachment_path ) ) {

						// Generate Document Thumb Symlink.
						bb_core_symlink_generator( 'document', $document, $size, $file, $file_path, $attachment_path );
					}
				} elseif ( wp_get_attachment_image_src( $attachment_id ) ) {
					$output_file_src = get_attached_file( $attachment_id );
					if ( ! empty( $output_file_src ) ) {
						// Regenerate attachment thumbnails.
						if ( ! file_exists( $output_file_src ) ) {
							bb_document_regenerate_attachment_thumbnails( $attachment_id );
							$file = image_get_intermediate_size( $attachment_id, $size );
						}

						// Check if file exists.
						if ( file_exists( $output_file_src ) && is_file( $output_file_src ) && ! is_dir( $output_file_src ) && ! file_exists( $attachment_path ) ) {
							if ( ! is_link( $attachment_path ) ) {

								// Generate Document Thumb Symlink.
								bb_core_symlink_generator( 'document', $document, $size, $file, $output_file_src, $attachment_path );
							}
						}
					}
				}
			}
		}

		$file_url = wp_get_attachment_url( $attachment_id );
		$filetype = wp_check_filetype( $file_url );
		if ( ! empty( $filetype ) && strstr( $filetype['type'], 'video/' ) ) {
			bb_document_video_get_symlink( $document );
		}
	}
}

/**
 * Delete symlink for a document.
 *
 * @param object $document BP_Document Object.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_document_delete_symlinks( $document ) {
	// Check if document is id of document, create document object.
	if ( $document instanceof BP_Document ) {
		$document_id = $document->id;
	} elseif ( is_int( $document ) ) {
		$document_id = $document;
	}

	$old_document = new BP_Document( $document_id );

	// Return if no document found.
	if ( empty( $old_document ) ) {
		return;
	}

	// Get documents previews symlink directory path.
	$document_symlinks_path = bp_document_symlink_path();
	$attachment_id          = $old_document->attachment_id;

	$privacy         = $old_document->privacy;
	$attachment_path = $document_symlinks_path . '/' . md5( $old_document->id . $attachment_id . $privacy . 'medium' );
	if ( $old_document->group_id > 0 && bp_is_active( 'groups' ) ) {
		$group_object    = groups_get_group( $old_document->group_id );
		$group_status    = bp_get_group_status( $group_object );
		$attachment_path = $document_symlinks_path . '/' . md5( $old_document->id . $attachment_id . $group_status . $privacy . 'medium' );
	}

	if ( file_exists( $attachment_path ) ) {
		unlink( $attachment_path );
	}

	$attachment_path = $document_symlinks_path . '/' . md5( $old_document->id . $attachment_id . $privacy . 'large' );
	if ( $old_document->group_id > 0 && bp_is_active( 'groups' ) ) {
		$group_object    = groups_get_group( $old_document->group_id );
		$group_status    = bp_get_group_status( $group_object );
		$attachment_path = $document_symlinks_path . '/' . md5( $old_document->id . $attachment_id . $group_status . $privacy . 'large' );
	}

	if ( file_exists( $attachment_path ) ) {
		unlink( $attachment_path );
	}

	$attachment_path = $document_symlinks_path . '/' . md5( $old_document->id . $attachment_id . $privacy . 'full' );
	if ( $old_document->group_id > 0 && bp_is_active( 'groups' ) ) {
		$group_object    = groups_get_group( $old_document->group_id );
		$group_status    = bp_get_group_status( $group_object );
		$attachment_path = $document_symlinks_path . '/' . md5( $old_document->id . $attachment_id . $group_status . $privacy . 'full' );
	}

	if ( file_exists( $attachment_path ) ) {
		unlink( $attachment_path );
	}

	$image_sizes = bb_document_get_image_sizes();
	if ( ! empty( $image_sizes ) ) {
		foreach ( $image_sizes as $name => $image_size ) {
			if ( ! empty( $image_size['width'] ) && ! empty( $image_size['height'] ) && 0 < (int) $image_size['width'] && 0 < $image_size['height'] ) {
				$attachment_path = $document_symlinks_path . '/' . md5( $old_document->id . $attachment_id . $privacy . sanitize_key( $name ) );
				if ( $old_document->group_id > 0 && bp_is_active( 'groups' ) ) {
					$group_object    = groups_get_group( $old_document->group_id );
					$group_status    = bp_get_group_status( $group_object );
					$attachment_path = $document_symlinks_path . '/' . md5( $old_document->id . $attachment_id . $group_status . $privacy . sanitize_key( $name ) );
				}

				// If rename the file then preview doesn't exist but symbolic is available in the folder. So, checked the file is not empty then remove it from symbolic.
				if ( ! empty( $attachment_path ) ) {
					@unlink( $attachment_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				}
			}
		}
	}

	$preview_attachment_path = $document_symlinks_path . '/' . md5( $old_document->id . $attachment_id . $old_document->privacy );
	if ( $old_document->group_id > 0 && bp_is_active( 'groups' ) ) {
		$group_object            = groups_get_group( $old_document->group_id );
		$group_status            = bp_get_group_status( $group_object );
		$preview_attachment_path = $document_symlinks_path . '/' . md5( $old_document->id . $attachment_id . $group_status . $old_document->privacy );
	}

	if ( file_exists( $preview_attachment_path ) ) {
		unlink( $preview_attachment_path );
	}
}

/**
 * Called on 'wp_image_editors' action.
 * Adds Ghostscript `BP_GOPP_Image_Editor_GS` class to head of image editors list.
 *
 * @param array $image_editors image editors.
 *
 * @return array
 */
function bp_document_include_wp_image_editors( $image_editors ) {
	if ( ! in_array( 'BP_GOPP_Image_Editor_GS', $image_editors, true ) ) {
		if ( ! class_exists( 'BP_GOPP_Image_Editor_GS' ) ) {
			if ( ! class_exists( 'WP_Image_Editor' ) ) {
				require ABSPATH . WPINC . '/class-wp-image-editor.php';
			}
			require trailingslashit( dirname( __FILE__ ) ) . '/classes/class-bp-gopp-image-editor-gs.php';
		}
		array_unshift( $image_editors, 'BP_GOPP_Image_Editor_GS' );
	}
	return $image_editors;
}

/**
 * Remove all temp directory.
 *
 * @param string $dir directory to remove.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_document_remove_temp_directory( $dir ) {
	if ( is_dir( $dir ) ) {
		$objects = scandir( $dir );
		foreach ( $objects as $object ) {
			if ( '.' !== $object && '..' !== $object ) {
				if ( filetype( $dir . '/' . $object ) === 'dir' ) {
					bp_document_remove_temp_directory( $dir . '/' . $object );
				} else {
					unlink( $dir . '/' . $object );
				}
			}
		}
		reset( $objects );
		rmdir( $dir );
	}
}

/**
 * Generate the document preview.
 *
 * @param int $attachment_id attachment id.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_document_generate_document_previews( $attachment_id ) {

	$is_preview_generated = get_post_meta( $attachment_id, 'document_preview_generated', true );

	if ( empty( $is_preview_generated ) ) {
		$extension = bp_document_extension( $attachment_id );
		if ( in_array( $extension, bp_get_document_preview_code_extensions(), true ) ) {
			bp_document_generate_code_previews( $attachment_id );
		}
	}

	$extension                   = bp_document_extension( $attachment_id );
	$activity_file               = image_get_intermediate_size( $attachment_id, 'bb-document-pdf-preview-activity-image' );
	$image_pdf_popup_file        = image_get_intermediate_size( $attachment_id, 'bb-document-pdf-image-popup-image' );
	$preview_image_activity_file = image_get_intermediate_size( $attachment_id, 'bb-document-image-preview-activity-image' );

	if ( 'pdf' === $extension && ( ! $activity_file || ! $image_pdf_popup_file || ! $preview_image_activity_file ) ) {

		// Add upload filters.
		bb_document_add_upload_filters();

		add_filter( 'wp_image_editors', 'bp_document_wp_image_editors' );

		// Register fallback intermediate image sizes.
		bb_document_add_fallback_intermediate_image_sizes();

		// Register image sizes.
		bb_document_register_image_sizes();

		bp_document_pdf_previews( array( $attachment_id ), true );

		// Deregister image sizes.
		bb_document_deregister_image_sizes();

		// Register fallback intermediate image sizes.
		bb_document_remove_fallback_intermediate_image_sizes();

		remove_filter( 'wp_image_editors', 'bp_document_wp_image_editors' );

		// Remove upload filters.
		bb_document_remove_upload_filters();
	}
}

/**
 * Helper to set the max_execution_time.
 *
 * @param int $time_limit Time limit.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_document_set_time_limits( $time_limit ) {
	$max_execution_time = ini_get( 'max_execution_time' );
	if ( $max_execution_time && $time_limit > $max_execution_time ) {
		return @set_time_limit( $time_limit ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}
	return null;
}

/**
 * Does the actual PDF preview regenerate.
 *
 * @param array $ids             Attachment ids.
 * @param bool  $check_mime_type Whether to check mime type or not.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_document_pdf_previews( $ids, $check_mime_type = false ) {

	$cnt          = 0;
	$num_updates  = 0;
	$num_fails    = 0;
	$attempt_time = 0;

	if ( $ids ) {

		$attempt_time = microtime( true );
		$cnt          = count( $ids );

		bp_document_set_time_limits( max( $cnt * 20, 300 ) );

		foreach ( $ids as $idx => $id ) {
			if ( $check_mime_type && 'application/pdf' !== get_post_mime_type( $id ) ) {
				continue;
			}
			$file = get_attached_file( $id );

			if ( false === $file || '' === $file ) {
				$num_fails++;
			} else {
				// Get current metadata if any.
				$old_value = get_metadata( 'post', $id, '_wp_attachment_metadata' );
				if ( $old_value && ( ! is_array( $old_value ) || 1 !== count( $old_value ) ) ) {
					$old_value = null;
				}

				// Remove old intermediate thumbnails if any.
				if ( $old_value && ! empty( $old_value[0]['sizes'] ) && is_array( $old_value[0]['sizes'] ) ) {
					$dirname = dirname( $file ) . '/';
					foreach ( $old_value[0]['sizes'] as $sizeinfo ) {
						// Check whether pre WP 4.7.3 lacking PDF marker and if so don't delete so as not to break links to thumbnails in content.
						if ( false !== strpos( $sizeinfo['file'], '-pdf' ) ) {
							@unlink( $dirname . $sizeinfo['file'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
						}
					}
				}

				if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
					require_once ABSPATH . 'wp-admin/includes/image.php';
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
				}

				// Generate new intermediate thumbnails.
				$meta = wp_generate_attachment_metadata( $id, $file );

				if ( ! $meta ) {
					$num_fails++;
				} else {
					// wp_update_attachment_metadata() returns false if nothing to update so check first.
					if ( ( $old_value && $old_value[0] === $meta ) || false !== wp_update_attachment_metadata( $id, $meta ) ) {
						$num_updates++;
					} else {
						$num_fails++;
					}
				}
			}
		}
		$attempt_time = round( microtime( true ) - $attempt_time, 1 );
	}
	return array( $cnt, $num_updates, $num_fails, $attempt_time );
}

/**
 * Generate the document code preview.
 *
 * @param int $attachment_id Attachment id.
 *
 * @return false
 *
 * @since BuddyBoss 1.7.0
 */
function bp_document_generate_code_previews( $attachment_id ) {
	$extension = bp_document_extension( $attachment_id );
	if ( ! in_array( $extension, bp_get_document_preview_code_extensions(), true ) ) {
		return false;
	}

	$absolute_path = get_attached_file( $attachment_id );
	if ( '' !== $absolute_path && '' !== basename( $absolute_path ) && strstr( $absolute_path, 'bb_documents/' ) ) {
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['basedir'];

		// Create temp folder.
		$upload_dir     = $upload_dir . '/preview-image-folder-' . time();
		$preview_folder = $upload_dir;
		// If folder not exists then create.
		if ( ! is_dir( $upload_dir ) ) {

			// Create temp folder.
			wp_mkdir_p( $upload_dir );
			chmod( $upload_dir, 0777 );

			// Create given main parent folder.
			$preview_folder = $upload_dir;
			wp_mkdir_p( $preview_folder );

			$file_name     = basename( $absolute_path );
			$extension_pos = strrpos( $file_name, '.' ); // find position of the last dot, so where the extension starts.
			$thumb         = substr( $file_name, 0, $extension_pos ) . '_thumb' . substr( $file_name, $extension_pos );
			copy( $absolute_path, $preview_folder . '/' . $thumb );

		}

		$files      = scandir( $preview_folder );
		$first_file = $preview_folder . '/' . $files[2];
		bp_document_chmod_r( $preview_folder );

		$image_data  = file_get_contents( $first_file );
		$words       = 1000;
		$mirror_text = strlen( $image_data ) > $words ? substr( $image_data, 0, $words ) . '...' : $image_data;
		update_post_meta( $attachment_id, 'document_preview_mirror_text', $mirror_text );
		update_post_meta( $attachment_id, 'document_preview_generated', 'yes' );
		bp_document_remove_temp_directory( $preview_folder );
	}
}

/**
 * Get the extension descriptions.
 *
 * @param string $extension File extension.
 *
 * @return mixed|string
 *
 * @since BuddyBoss 1.7.0
 */
function bp_document_get_extension_description( $extension ) {
	$extension_lists       = bp_document_extensions_list();
	$extension_description = '';

	if ( ! empty( $extension_lists ) ) {
		$extension_lists = array_column( $extension_lists, 'description', 'extension' );
		$extension_name  = '.' . $extension;
		if ( ! empty( $extension_lists ) && ! empty( $extension ) && array_key_exists( $extension_name, $extension_lists ) ) {
			$extension_description = $extension_lists[ $extension_name ];
		}
	}

	return $extension_description;
}

/**
 * Return the preview url of the file.
 *
 * @param int    $document_id   Document ID.
 * @param int    $attachment_id Attachment ID.
 * @param string $size          Size of preview.
 * @param bool   $generate      Generate Symlink or not.
 *
 * @return mixed|void
 *
 * @since BuddyBoss 1.7.0
 */
function bp_document_get_preview_url( $document_id, $attachment_id, $size = 'bb-document-image-preview-activity-image', $generate = true ) {
	$attachment_url = '';
	$extension      = bp_document_extension( $attachment_id );

	/**
	 * Filter here to allow/disallow document symlinks.
	 *
	 * @param bool   $do_symlink    Default true.
	 * @param int    $document_id   Document id
	 * @param int    $attachment_id Document attachment id.
	 * @param string $size          Size.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	$do_symlink = apply_filters( 'bb_document_do_symlink', true, $document_id, $attachment_id, $size );

	if ( $do_symlink ) {

		if ( in_array( $extension, bp_get_document_preview_doc_extensions(), true ) && bb_enable_symlinks() ) {
			$document = new BP_Document( $document_id );

			$upload_directory       = wp_get_upload_dir();
			$document_symlinks_path = bp_document_symlink_path();

			$preview_attachment_path = $document_symlinks_path . '/' . md5( $document_id . $attachment_id . $document->privacy . $size );
			if ( $document->group_id > 0 && bp_is_active( 'groups' ) ) {
				$group_object            = groups_get_group( $document->group_id );
				$group_status            = bp_get_group_status( $group_object );
				$preview_attachment_path = $document_symlinks_path . '/' . md5( $document->id . $attachment_id . $group_status . $document->privacy . $size );
			}

			if ( ! file_exists( $preview_attachment_path ) && $generate ) {
				bp_document_create_symlinks( $document, $size );
			}
			if ( ! file_exists( $preview_attachment_path ) ) {
				$attachment_url = '';
			} else {
				$attachment_url = str_replace( $upload_directory['basedir'], $upload_directory['baseurl'], $preview_attachment_path );
			}

			/**
			 * Filter for the after thumb symlink generate.
			 *
			 * @param string $attachment_url Attachment URL.
			 * @param object $document       Document Object.
			 *
			 * @since BuddyBoss 1.7.0.1
			 */
			$attachment_url = apply_filters( 'bb_document_after_get_preview_url_symlink', $attachment_url, $document );

		} elseif ( in_array( $extension, bp_get_document_preview_doc_extensions(), true ) && ! bb_enable_symlinks() ) {

			$file          = image_get_intermediate_size( $attachment_id, $size );
			$attached_file = get_attached_file( $attachment_id );

			if ( false === $file && 'pdf' !== $extension ) {
				bb_document_regenerate_attachment_thumbnails( $attachment_id );
				$file = image_get_intermediate_size( $attachment_id, $size );
			} elseif ( false === $file && 'pdf' === $extension ) {
				bp_document_generate_document_previews( $attachment_id );
				$file = image_get_intermediate_size( $attachment_id, $size );
			}

			// If the given size is not found then use the full image.
			if ( false === $file ) {
				$file = image_get_intermediate_size( $attachment_id, 'full' );
			}

			if ( false === $file ) {
				$file = image_get_intermediate_size( $attachment_id, 'original' );
			}

			if ( false === $file ) {
				$file = image_get_intermediate_size( $attachment_id, 'thumbnail' );
			}

			$attached_file_info = pathinfo( $attached_file );
			$file_path          = '';

			if ( $file && ! empty( $file['file'] ) && ! empty( $attached_file_info['dirname'] ) ) {
				$file_path = $attached_file_info['dirname'];
				$file_path = $file_path . '/' . $file['file'];
			}

			if ( $file && ! empty( $file['file'] ) && ! file_exists( $file_path ) ) {
				if ( 'pdf' !== $extension ) {
					// Regenerate attachment thumbnails.
					bb_document_regenerate_attachment_thumbnails( $attachment_id );
					$file      = image_get_intermediate_size( $attachment_id, $size );
					$file_path = $file_path . '/' . $file['file'];
				} else {
					bp_document_generate_document_previews( $attachment_id );
					$file      = image_get_intermediate_size( $attachment_id, $size );
					$file_path = $file_path . '/' . $file['file'];
				}
			}

			if ( $file && ! empty( $file_path ) && file_exists( $file_path ) && is_file( $file_path ) && ! is_dir( $file_path ) ) {

				$document_id    = 'forbidden_' . $document_id;
				$attachment_id  = 'forbidden_' . $attachment_id;
				$attachment_url = home_url( '/' ) . 'bb-document-preview/' . base64_encode( $attachment_id ) . '/' . base64_encode( $document_id ) . '/' . $size;

			} elseif ( wp_get_attachment_image_src( $attachment_id ) ) {
				$output_file_src = get_attached_file( $attachment_id );
				if ( ! empty( $output_file_src ) ) {
					// Regenerate attachment thumbnails.
					if ( ! file_exists( $output_file_src ) ) {
						bb_document_regenerate_attachment_thumbnails( $attachment_id );
					}

					// Check if file exists.
					if ( file_exists( $output_file_src ) && is_file( $output_file_src ) && ! is_dir( $output_file_src ) ) {
						$document_id    = 'forbidden_' . $document_id;
						$attachment_id  = 'forbidden_' . $attachment_id;
						$attachment_url = home_url( '/' ) . 'bb-document-preview/' . base64_encode( $attachment_id ) . '/' . base64_encode( $document_id );
					}
				}
			}
		}

		if ( in_array( $extension, bp_get_document_preview_music_extensions(), true ) && bb_enable_symlinks() ) {
			$document                = new BP_Document( $document_id );
			$upload_directory        = wp_get_upload_dir();
			$document_symlinks_path  = bp_document_symlink_path();
			$preview_attachment_path = $document_symlinks_path . '/' . md5( $document_id . $attachment_id . $document->privacy );
			if ( $document->group_id > 0 && bp_is_active( 'groups' ) ) {
				$group_object            = groups_get_group( $document->group_id );
				$group_status            = bp_get_group_status( $group_object );
				$preview_attachment_path = $document_symlinks_path . '/' . md5( $document_id . $attachment_id . $group_status . $document->privacy );
			}
			if ( ! file_exists( $preview_attachment_path ) && $generate ) {
				bp_document_create_symlinks( $document, '' );
			}
			$attachment_url = str_replace( $upload_directory['basedir'], $upload_directory['baseurl'], $preview_attachment_path );
		} elseif ( in_array( $extension, bp_get_document_preview_music_extensions(), true ) && ! bb_enable_symlinks() ) {
			$passed_attachment_id = $attachment_id;
			$document_id          = 'forbidden_' . $document_id;
			$attachment_id        = 'forbidden_' . $attachment_id;
			$output_file_src      = get_attached_file( $passed_attachment_id );
			if ( ! empty( $attachment_id ) && ! empty( $document_id ) && file_exists( $output_file_src ) ) {
				$attachment_url = home_url( '/' ) . 'bb-document-player/' . base64_encode( $attachment_id ) . '/' . base64_encode( $document_id );
			}
		}
	}

	$attachment_url = ! empty( $attachment_url ) && ! bb_enable_symlinks() ? untrailingslashit( $attachment_url ) : $attachment_url;

	/**
	 * Filter url here to audio url.
	 *
	 * @param string $attachment_url Url.
	 * @param int    $document_id    Document id.
	 * @param string $extension      Extension.
	 * @param string $size           Size.
	 * @param int    $attachment_id  Attachment id.
	 * @param bool   $do_symlink     display symlink or not.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_document_get_preview_url', $attachment_url, $document_id, $extension, $size, $attachment_id, $do_symlink );
}

/**
 * Give recursive file permission.
 *
 * @param string $path File path.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_document_chmod_r( $path ) {
	$dir = new DirectoryIterator( $path );
	foreach ( $dir as $item ) {
		chmod( $item->getPathname(), 0777 );
		if ( $item->isDir() && ! $item->isDot() ) {
			bp_document_chmod_r( $item->getPathname() );
		}
	}
}

/**
 * Return the preview text for the document files.
 *
 * @param int $attachment_id Attachment id.
 *
 * @return false|mixed|string
 * @since BuddyBoss 1.4.1
 */
function bp_document_mirror_text( $attachment_id ) {
	$mirror_text = '';

	$extension = bp_document_extension( $attachment_id );
	if ( isset( $extension ) && ! empty( $extension ) && in_array( $extension, bp_get_document_preview_code_extensions(), true ) ) {
		$words = 8000;
		$more  = '...';
		$text  = get_post_meta( $attachment_id, 'document_preview_mirror_text', true );
		if ( $text ) {
			$mirror_text = strlen( $text ) > $words ? substr( $text, 0, $words ) . '...' : $text;
		} else {
			if ( file_exists( get_attached_file( $attachment_id ) ) ) {
				$image_data  = file_get_contents( get_attached_file( $attachment_id ) );
				$words       = 10000;
				$mirror_text = strlen( $image_data ) > $words ? substr( $image_data, 0, $words ) . '...' : $image_data;
				update_post_meta( $attachment_id, 'document_preview_mirror_text', $mirror_text );
			}
		}
	}

	return $mirror_text;
}

/**
 * Return the audio url of the file.
 *
 * @param int    $document_id   Document id.
 * @param int    $attachment_id Attachment id.
 * @param string $extension     File extension name.
 *
 * @return mixed|void
 *
 * @since BuddyBoss 1.7.0
 */
function bp_document_get_preview_audio_url( $document_id, $attachment_id, $extension ) {

	$attachment_url = bp_document_get_preview_url( $document_id, $attachment_id, '', true );

	/**
	 * Filter url here to audio url.
	 *
	 * @param string $attachment_url Url.
	 * @param int    $document_id    Document id.
	 * @param string $extension      File extension name.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_document_get_preview_audio_url', $attachment_url, $document_id, $extension );
}

/**
 * Called on 'wp_image_editors' action.
 * Adds Ghostscript `BP_GOPP_Image_Editor_GS` class to head of image editors list.
 *
 * @param $image_editors
 *
 * @since BuddyBoss 1.7.0
 */
function bp_document_wp_image_editors( $image_editors ) {
	if ( ! in_array( 'BP_GOPP_Image_Editor_GS', $image_editors, true ) ) {
		bp_document_load_gopp_image_editor_gs();
		array_unshift( $image_editors, 'BP_GOPP_Image_Editor_GS' );
	}

	return $image_editors;
}

/**
 * Helper to load BP_GOPP_Image_Editor_GS class.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_document_load_gopp_image_editor_gs() {

	if ( ! class_exists( 'BP_GOPP_Image_Editor_GS' ) ) {
		if ( ! class_exists( 'WP_Image_Editor' ) ) {
			require ABSPATH . WPINC . '/class-wp-image-editor.php';
		}
		require trailingslashit( dirname( __FILE__ ) ) . 'classes/class-bp-gopp-image-editor-gs.php';
	}
}

/**
 * Create symlink for a document video.
 *
 * @param object $document BP_Document Object.
 * @param bool   $generate Generate Symlink or not.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_document_video_get_symlink( $document, $generate = true ) {

	// Check if document is id of document, create document object.
	if ( ! $document instanceof BP_Document && is_int( $document ) ) {
		$document = new BP_Document( $document );
	}

	// Return if no document found.
	if ( empty( $document ) ) {
		return;
	}

	$attachment_url = '';
	$document_id    = $document->id;
	$attachment_id  = $document->attachment_id;

	/**
	 * Filter here to allow/disallow document symlinks.
	 *
	 * @param bool   $do_symlink    Default true.
	 * @param int    $document_id   Document id
	 * @param int    $attachment_id Document attachment id.
	 * @param string $size          Size.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	$do_symlink = apply_filters( 'bb_document_do_symlink', true, $document_id, $attachment_id, '' );

	if ( $do_symlink ) {

		$existing_mimes = array();
		$all_extensions = bp_document_extensions_list();

		foreach ( $all_extensions as $extension ) {
			if ( isset( $extension['is_active'] ) && true === (bool) $extension['is_active'] ) {
				$extension_name                      = ltrim( $extension['extension'], '.' );
				$existing_mimes[ "$extension_name" ] = $extension['mime_type'];
			}
		}

		$document_mime_type = bp_document_mime_type( $attachment_id );

		if ( ! in_array( $document_mime_type, $existing_mimes, true ) ) {
			return;
		}

		$attached_file = get_attached_file( $attachment_id );
		$filetype      = wp_check_filetype( $attached_file );
		if ( ! strstr( $filetype['type'], 'video/' ) ) {
			return;
		}

		if ( bb_enable_symlinks() ) {

			if ( bb_check_ios_device() ) {
				$attachment_url = home_url( '/' ) . 'bb-document-player/' . base64_encode( 'forbidden_' . $attachment_id ) . '/' . base64_encode( 'forbidden_' . $document_id );
			} else {

				// Get videos previews symlink directory path.
				$video_symlinks_path = bp_document_symlink_path();
				$privacy             = $document->privacy;
				$upload_directory    = wp_get_upload_dir();
				$attachment_path     = $video_symlinks_path . '/' . md5( $document->id . $attachment_id . $privacy );

				if ( function_exists( 'mime_content_type' ) ) {
					$mime = mime_content_type( $attached_file );
				} elseif ( class_exists( 'finfo' ) ) {
					$finfo = new finfo();

					if ( is_resource( $finfo ) === true ) {
						$mime = $finfo->file( $attached_file, FILEINFO_MIME_TYPE );
					}
				} else {
					$filetype = wp_check_filetype( $attached_file );
					$mime     = $filetype['type'];
				}

				if ( strstr( $mime, 'video/' ) ) {
					if ( ! empty( $attached_file ) && file_exists( $attached_file ) && is_file( $attached_file ) && ! is_dir( $attached_file ) && ! file_exists( $attachment_path ) ) {
						if ( ! is_link( $attachment_path ) && ! file_exists( $attachment_path ) ) {
							$get_existing = get_post_meta( $document->attachment_id, 'bb_video_symlinks_arr', true );
							if ( ! $get_existing ) {
								update_post_meta( $document->attachment_id, 'bb_video_symlinks_arr', array( $attachment_path ) );
							} else {
								$get_existing[] = array_push( $get_existing, $attachment_path );
								update_post_meta( $document->attachment_id, 'bb_video_symlinks_arr', $get_existing );
							}

							if ( $generate ) {
								// Generate Document Video Symlink.
								bb_core_symlink_generator( 'document_video', $document, '', array(), $attached_file, $attachment_path );
							}
						}
					}

					$attachment_url = str_replace( $upload_directory['basedir'], $upload_directory['baseurl'], $attachment_path );

					/**
					 * Filter for the after document video symlink generate.
					 *
					 * @param string $attachment_url Attachment URL.
					 * @param object $document       Document Object.
					 *
					 * @since BuddyBoss 1.7.0.1
					 */
					$attachment_url = apply_filters( 'bb_document_after_video_get_symlink', $attachment_url, $document );
				}
			}
		} else {
			$attachment_url = home_url( '/' ) . 'bb-document-player/' . base64_encode( 'forbidden_' . $attachment_id ) . '/' . base64_encode( 'forbidden_' . $document_id );
		}
	}

	/**
	 * Filter here to video symlink url.
	 *
	 * @param string $attachment_url Symlink url.
	 * @param int    $document_id    Document id.
	 * @param int    $attachment_id  Attachment id.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bb_document_video_get_symlink', $attachment_url, $document_id, $attachment_id );
}

/**
 * Get document image sizes to register.
 *
 * @return array Image sizes.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_document_get_image_sizes() {
	$image_sizes = array(
		'bb-document-pdf-preview-activity-image'   => array(
			'height' => 600,
			'width'  => 608,
		),
		'bb-document-pdf-image-popup-image'        => array(
			'height' => 900,
			'width'  => 1500,
		),
		'bb-document-image-preview-activity-image' => array(
			'height' => 250,
			'width'  => 600,
		),
	);

	return (array) apply_filters( 'bb_document_get_image_sizes', $image_sizes );
}

/**
 * Register media image sizes.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_document_register_image_sizes() {
	$image_sizes = bb_document_get_image_sizes();
	if ( ! empty( $image_sizes ) ) {
		foreach ( $image_sizes as $name => $image_size ) {
			if ( ! empty( $image_size['width'] ) && ! empty( $image_size['height'] ) && 0 < (int) $image_size['width'] && 0 < $image_size['height'] ) {
				add_image_size( sanitize_key( $name ), $image_size['width'], $image_size['height'] );
			}
		}
	}
}

/**
 * Regenerate media attachment thumbnails
 *
 * @param int $attachment_id Attachment ID.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_document_regenerate_attachment_thumbnails( $attachment_id ) {

	// Add upload filters.
	bb_document_add_upload_filters();

	// Register image sizes.
	bb_document_register_image_sizes();

	// Regenerate attachment thumbnails.
	bp_core_regenerate_attachment_thumbnails( $attachment_id );

	// Remove upload filters.
	bb_document_remove_upload_filters();

	// Deregister image sizes.
	bb_document_deregister_image_sizes();
}

/**
 * Add document upload filters.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_document_add_upload_filters() {
	add_filter( 'upload_dir', 'bp_document_upload_dir' );
	add_filter( 'intermediate_image_sizes_advanced', 'bb_document_remove_default_image_sizes' );
	add_filter( 'upload_mimes', 'bp_document_allowed_mimes', 9, 1 );
	add_filter( 'big_image_size_threshold', '__return_false' );
}

/**
 * Remove document upload filters.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_document_remove_upload_filters() {
	remove_filter( 'upload_dir', 'bp_document_upload_dir' );
	remove_filter( 'intermediate_image_sizes_advanced', 'bb_document_remove_default_image_sizes' );
	remove_filter( 'upload_mimes', 'bp_document_allowed_mimes' );
	remove_filter( 'big_image_size_threshold', '__return_false' );
}

/**
 * This will remove the default image sizes registered.
 *
 * @param array $sizes Image sizes registered.
 *
 * @return array Empty array.
 * @since BuddyBoss 1.5.7
 */
function bb_document_remove_default_image_sizes( $sizes ) {
	if ( isset( $sizes['bb-document-pdf-preview-activity-image'] ) && isset( $sizes['bb-document-pdf-image-popup-image'] ) && isset( $sizes['bb-document-image-preview-activity-image'] ) ) {
		return array(
			'bb-document-pdf-preview-activity-image'   => $sizes['bb-document-pdf-preview-activity-image'],
			'bb-document-pdf-image-popup-image'        => $sizes['bb-document-pdf-image-popup-image'],
			'bb-document-image-preview-activity-image' => $sizes['bb-document-image-preview-activity-image'],
		);
	}

	return array();
}

/**
 * Deregister document image sizes.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_document_deregister_image_sizes() {
	$image_sizes = bb_document_get_image_sizes();

	if ( ! empty( $image_sizes ) ) {
		foreach ( $image_sizes as $name => $image_size ) {
			remove_image_size( sanitize_key( $name ) );
		}
	}
}

/**
 * Add the fallback image sizes for PDF.
 *
 * @param array $fallback_sizes Fallback sizes.
 * @param array $metadata       Meta data array.
 *
 * @return mixed|void
 *
 * @since BuddyBoss 1.7.0
 */
function bb_document_fallback_intermediate_image_sizes( $fallback_sizes, $metadata ) {
	$image_sizes = bb_document_get_image_sizes();
	if ( ! empty( $image_sizes ) ) {
		foreach ( $image_sizes as $name => $image_size ) {
			$fallback_sizes[] = sanitize_key( $name );
		}
	}
	return apply_filters( 'bb_document_fallback_intermediate_image_sizes', $fallback_sizes, $metadata );
}

/**
 * Add the PDF fallback filter.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_document_add_fallback_intermediate_image_sizes() {
	add_filter( 'fallback_intermediate_image_sizes', 'bb_document_fallback_intermediate_image_sizes', 9999, 2 );
}

/**
 * Remove the PDF fallback filter.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_document_remove_fallback_intermediate_image_sizes() {
	remove_filter( 'fallback_intermediate_image_sizes', 'bb_document_fallback_intermediate_image_sizes' );
}

/**
 * Return the document attachment.
 *
 * @param int|object $document Document id OR a document object.
 *
 * @return stdClass attachment object.
 */
function bb_get_document_attachments( $document ) {

	// Check if document is id of document, create document object.
	if ( ! $document instanceof BP_Document && is_int( $document ) ) {
		$document = new BP_Document( $document );
	}

	/**
	 * Filter here to allow/disallow document symlinks.
	 *
	 * @param bool   $do_symlink    Default true.
	 * @param int    $document_id   Document id
	 * @param int    $attachment_id Document attachment id.
	 * @param string $size          Size.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	$do_symlink      = apply_filters( 'bb_document_do_symlink', true, $document->id, $document->attachment_id, 'bb-document-pdf-image-popup-image' );
	$attachment_data = new stdClass();

	if ( $do_symlink ) {

		// fetch attachment data.
		$large_pdf_image_popup = bp_document_get_preview_url( $document->id, $document->attachment_id, 'bb-document-pdf-image-popup-image' );
		$activity_thumb_pdf    = bp_document_get_preview_url( $document->id, $document->attachment_id, 'bb-document-pdf-preview-activity-image' );
		$activity_thumb_image  = bp_document_get_preview_url( $document->id, $document->attachment_id, 'bb-document-image-preview-activity-image' );
		$video_symlink         = bb_document_video_get_symlink( $document );

		$attachment_data->full               = $large_pdf_image_popup;
		$attachment_data->thumb              = $activity_thumb_image;
		$attachment_data->activity_thumb     = $activity_thumb_image;
		$attachment_data->activity_thumb_pdf = $activity_thumb_pdf;
		$attachment_data->video_symlink      = $video_symlink;
	}

	return $attachment_data;
}

/**
 * A simple function that uses mtime to delete files older than a given age (in seconds)
 * Very handy to rotate backup or log files, for example...
 *
 * @return array|void the list of deleted files.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_document_delete_older_symlinks() {

	if ( ! bb_enable_symlinks() ) {
		return;
	}

	// Get documents previews symlink directory path.
	$dir     = bp_document_symlink_path();
	$max_age = apply_filters( 'bb_document_delete_older_symlinks_time', 3600 * 24 * 15 ); // Delete the file older than 15 day.
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
		if ( $file === '.' || $file === '..' ) {
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
		do_action( 'bb_document_delete_older_symlinks' );
	}

	return $list;
}
bp_core_schedule_cron( 'bb_document_deleter_older_symlink', 'bb_document_delete_older_symlinks', 'bb_schedule_15days' );


/**
 * Get list of privacy based on user and group.
 *
 * @param int    $user_id  User ID.
 * @param int    $group_id Group ID.
 * @param string $scope    Scope query parameter.
 *
 * @return mixed|void
 *
 * @since BuddyBoss 1.7.8
 */
function bp_document_query_privacy( $user_id = 0, $group_id = 0, $scope = '' ) {

	$privacy = array( 'public' );

	if ( is_user_logged_in() ) {
		// User filtering.
		$user_id = (int) ( empty( $user_id ) ? ( bp_displayed_user_id() ? bp_displayed_user_id() : false ) : $user_id );

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

	return apply_filters( 'bp_document_query_privacy', $privacy, $user_id, $group_id, $scope );
}

/**
 * Function to delete the temporary download folder which create while downloding the document directory.
 *
 * @since BuddyBoss 2.3.80
 *
 * @return void
 */
function bb_document_remove_orphaned_download() {
	if ( ! class_exists( '\WP_Filesystem_Direct' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
	}

	$wp_files_system = new \WP_Filesystem_Direct( array() );

	$six_hours_ago = time() - ( HOUR_IN_SECONDS * 6 ); // Get the timestamp 6 hours ago.
	$uploadDir     = wp_upload_dir(); // Get the path to the upload directory.
	$dir           = $uploadDir['basedir']; // Get the base directory path.

	// Get all the subdirectories in the upload directory.
	$folders = glob( $dir . '/*', GLOB_ONLYDIR );

	foreach ( $folders as $folder ) {
		$folder_timestamp = filemtime( $folder );

		// If the folder is older than 6 hours and contains "-download-folder-" in the name, print it.
		if ( $folder_timestamp < $six_hours_ago && stristr( $folder, '-download-folder-' ) !== false ) {
			$wp_files_system->delete( $folder, true );
		}
	}
}
