<?php
/**
 * BuddyBoss Moderation Functions.
 *
 * Functions for the Moderation component.
 *
 * @package BuddyBoss\Moderation
 * @since   BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Moderation Core functions
 */

/**
 * Retrieve an Moderation reports.
 *
 * The bp_moderation_get() function shares all arguments with
 * BP_Moderation::get().
 *
 * @since BuddyBoss 1.5.4
 *
 * @param array|string $args See BP_Moderation::get() for description.
 *
 * @return array $moderation See BP_Moderation::get() for description.
 * @see   BP_Moderation::get() For more information on accepted arguments
 *        and the format of the returned value.
 */
function bp_moderation_get( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'max'               => false,
			// Maximum number of results to return.
			'user_id'           => false,
			// Filter moderation reported by particular user.
			'fields'            => 'all',
			'page'              => 1,
			// Page 1 without a per_page will result in no pagination.
			'per_page'          => false,
			// results per page.
			'sort'              => 'DESC',
			'order_by'          => 'last_updated',
			// sort ASC or DESC.
			// Phpcs:ignore
			'meta_query'        => false,
			// Filter by moderation meta. See WP_Meta_Query for format.
			'date_query'        => false,
			// Filter by date. See first parameter of WP_Date_Query for format.
			'filter_query'      => false,
			'exclude'           => false,
			// Comma-separated list of moderation IDs to exclude.
			'in'                => false,
			// Comma-separated list or array of moderation IDs to which you
			// want to limit the query.
			'exclude_types'     => false,
			// Comma-separated list of moderation item types to exclude.
			'in_types'          => false,
			// Comma-separated list or array of moderation item types to which you
			// want to limit the query.
			'update_meta_cache' => true,
			'display_reporters' => false,
			'count_total'       => false,

			/**
			 * Pass filters as an array -- all filter items can be multiple values comma separated:
			 * array(
			 *     'item_id'       => false, // Item ID to filter on eg. Activity ID, Groups ID, User ID etc.
			 *     'hide_sitewide' => false, // filter by hidden items e.g. 0, 1.
			 *     'blog_id'       => false, // Blog ID to filter on.
			 * );
			 */
			'filter'            => array(),
		),
		'moderation_get'
	);

	$moderation = BP_Moderation::get(
		array(
			'page'              => $r['page'],
			'per_page'          => $r['per_page'],
			'user_id'           => $r['user_id'],
			'max'               => $r['max'],
			'sort'              => $r['sort'],
			'order_by'          => $r['order_by'],
			'meta_query'        => $r['meta_query'], // Phpcs:ignore
			'date_query'        => $r['date_query'],
			'filter_query'      => $r['filter_query'],
			'filter'            => $r['filter'],
			'exclude_types'     => $r['exclude_types'],
			'in_types'          => $r['in_types'],
			'exclude'           => $r['exclude'],
			'in'                => $r['in'],
			'update_meta_cache' => $r['update_meta_cache'],
			'display_reporters' => $r['display_reporters'],
			'count_total'       => $r['count_total'],
			'fields'            => $r['fields'],
		)
	);

	/**
	 * Filters the requested moderation item(s).
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array         $r          Arguments used for the moderation query.
	 *
	 * @param BP_Moderation $moderation Requested moderation object.
	 */
	return apply_filters_ref_array(
		'bp_moderation_get',
		array(
			&$moderation,
			&$r,
		)
	);
}

/**
 * Retrieve sitewide hidden items ids of particular item type.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param string $type         Moderation items type.
 * @param bool   $user_include Include item which report by current user even if it's not hidden.
 *
 * @return array $moderation See BP_Moderation::get() for description.
 */
function bp_moderation_get_sitewide_hidden_item_ids( $type, $user_include = false ) {

	$args = array(
		'in_types'          => $type,
		'update_meta_cache' => false,
		'filter_query'      => array(
			array(
				'column' => 'hide_sitewide',
				'value'  => 1
			),
		)
	);

	if ( $user_include ) {
		$args['filter_query']['relation'] = 'OR';
		$args['filter_query'][]           = array(
			'column' => 'user_id',
			'value'  => get_current_user_id(),
		);
	}

	return bp_moderation_get( $args );
}

/**
 * Function to get the moderation content types.
 *
 * @since BuddyBoss 1.5.4
 *
 * @return mixed|void
 */
function bp_moderation_content_types() {
	return apply_filters( 'bp_moderation_content_types', array() );
}

/**
 * Function get content owner id.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param int    $moderation_item_id   content id.
 * @param string $moderation_item_type content type.
 *
 * @return int
 */
function bp_moderation_get_content_owner_id( $moderation_item_id, $moderation_item_type ) {

	$user_id = 0;
	$class   = BP_Moderation_Abstract::get_class( $moderation_item_type );

	if ( method_exists( $class, 'get_content_owner_id' ) ) {
		$user_id = $class::get_content_owner_id( $moderation_item_id );
	}

	return (int) $user_id;
}

/**
 * Function to get the content based on type.
 *
 * @param int    $moderation_item_id   moderation id to get the content.
 * @param string $moderation_item_type item type
 *
 * @return string
 */
function bp_moderation_get_content_excerpt( $moderation_item_id, $moderation_item_type ) {

	$content_excerpt = '';
	$class           = BP_Moderation_Abstract::get_class( $moderation_item_type );

	if ( method_exists( $class, 'get_content_excerpt' ) ) {
		$content_excerpt = $class::get_content_excerpt( $moderation_item_id );
	}

	return $content_excerpt;
}

/**
 * Function to get specific moderation content type.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param string $key content type key.
 *
 * @return mixed|void
 */
function bp_moderation_get_content_type( $key ) {

	$content_types = bp_moderation_content_types();

	return apply_filters( 'bp_moderation_get_content_type', key_exists( $key, $content_types ) ? $content_types[ $key ] : '' );
}

/**
 * Function to get Report button
 *
 * @param array $args button args
 * @param bool  $html Should return button html or not.
 *
 * @return string|array
 */
function bp_moderation_get_report_button( $args, $html = true ) {

	$item_id              = $args['button_attr']['data-bp-content-id'];
	$item_type            = $args['button_attr']['data-bp-content-type'];
	$button_text          = __( 'Report', 'buddyboss' );
	$reported_button_text = __( 'Reported', 'buddyboss' );

	// Check moderation setting enabled or not
	if ( BP_Moderation_Members::$moderation_type === $item_type ) {
		$button_text          = __( 'Block', 'buddyboss' );
		$reported_button_text = __( 'Blocked', 'buddyboss' );
		if ( ! bp_is_moderation_member_blocking_enable( 0 ) ) {
			return ! empty( $html ) ? '' : array();
		}
	} else {
		if ( ! bp_is_moderation_content_reporting_enable( 0, $item_type ) ) {
			return ! empty( $html ) ? '' : array();
		}
	}

	// Hide if content is created by current user
	if ( bp_loggedin_user_id() === bp_moderation_get_content_owner_id( $item_id, $item_type ) ) {
		return ! empty( $html ) ? '' : array();
	}

	$args['button_attr'] = wp_parse_args( $args['button_attr'], array(
		'id'                   => 'report-content-' . $args['button_attr']['data-bp-content-type'] . '-' . $args['button_attr']['data-bp-content-id'],
		'href'                 => '#content-report',
		'class'                => 'button item-button bp-secondary-action report-content',
		'data-bp-content-id'   => '',
		'data-bp-content-type' => '',
		'data-bp-nonce'        => wp_create_nonce( 'bp-moderation-content' ),
	) );

	$button = wp_parse_args( $args, array(
		'link_text' => sprintf( '<span class="bp-screen-reader-text">%s</span><span class="report-label">%s</span>', esc_html( $button_text ), esc_html__( $button_text ) ),
	) );

	$is_reported = bp_moderation_report_exist( $item_id, $item_type );

	if ( $is_reported ) {
		$button['link_text']            = sprintf( '<span class="bp-screen-reader-text">%s</span><span class="report-label">%s</span>', esc_html( $reported_button_text ), esc_html( $reported_button_text ) );
		$button['button_attr']['class'] = 'button item-button bp-secondary-action reported-content';
		unset( $button['button_attr']['href'] );
		unset( $button['button_attr']['data-bp-content-id'] );
		unset( $button['button_attr']['data-bp-content-type'] );
		unset( $button['button_attr']['data-bp-nonce'] );
	}

	if ( ! empty( $html ) ) {
		if ( $is_reported ) {
			$button = sprintf( '<a id="%s" class="%s" >%s</a>', esc_attr( $button['button_attr']['id'] ), esc_attr( $button['button_attr']['class'] ), wp_kses_post( $button['link_text'] ) );
		} else {
			$button = sprintf( '<a href="%s" id="%s" class="%s" data-bp-content-id="%s" data-bp-content-type="%s" data-bp-nonce="%s">%s</a>', esc_url( $button['button_attr']['href'] ), esc_attr( $button['button_attr']['id'] ), esc_attr( $button['button_attr']['class'] ), esc_attr( $button['button_attr']['data-bp-content-id'] ), esc_attr( $button['button_attr']['data-bp-content-type'] ), esc_attr( $button['button_attr']['data-bp-nonce'] ), wp_kses_post( $button['link_text'] ) );
		}
	}

	return apply_filters( 'bp_moderation_get_report_button', $button, $args, $html );
}

/**
 * Function to Report content.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param array $args Report args.
 *
 * @return bool
 */
function bp_moderation_add( $args = array() ) {
	$response = false;

	if ( ! empty( $args['content_id'] ) && ! empty( $args['content_type'] ) ) {
		$class = BP_Moderation_Abstract::get_class( $args['content_type'] );

		if ( method_exists( $class, 'report' ) ) {
			$response = $class::report( $args );
		}
	}

	return $response;
}

/**
 * Function to Check content Reported by current usr or not.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param int    $item_id   Item id
 * @param string $item_type Item type
 *
 * @return bool
 */
function bp_moderation_report_exist( $item_id, $item_type ) {
	$response = false;

	if ( ! empty( $item_id ) && ! empty( $item_type ) ) {
		$moderation = new BP_Moderation( $item_id, $item_type );
		$response   = ( ! empty( $moderation->id ) && ! empty( $moderation->report_id ) );
	}

	return $response;
}

/**
 * Function to unblock user from frontend.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param int    $item_id   Item id
 * @param string $item_type Item type
 * @param bool   $force_all Should delete all reported entry
 *
 * @return false|int
 */
function bp_moderation_delete_reported_item( $item_id, $item_type, $force_all = false ) {
	$moderation_obj = new BP_Moderation( $item_id, $item_type );

	return $moderation_obj->delete( $force_all );
}

/**
 * Function to hide unhide moderation request.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param int    $item_id   Item id
 * @param string $item_type Item type
 * @param string $action    hide/unhide action.
 *
 * @return bool
 */
function bp_moderation_hide_unhide_request( $item_id, $item_type, $action ) {
	$moderation_obj = new BP_Moderation( $item_id, $item_type );

	if ( 'hide' === $action ) {
		$moderation_obj->hide_sitewide = 1;
	} elseif ( 'unhide' === $action ) {
		$moderation_obj->hide_sitewide = 0;
		$moderation_obj->delete();
	}

	return $moderation_obj->save();
}

/**
 * Check whether a user has been marked as a suspended.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param int $user_id The ID for the user.
 *
 * @return bool True if suspended, otherwise false.
 */
function bp_moderation_is_user_suspended( $user_id ) {
	$hidden_members_ids = BP_Moderation_Members::get_sitewide_hidden_ids( false );
	if ( in_array( $user_id, $hidden_members_ids, true ) ) {
		return true;
	}

	return false;
}

/** Meta *********************************************************************/

/**
 * Delete a meta entry from the DB for an moderation item.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param int    $moderation_id ID of the moderation item whose metadata is being deleted.
 * @param string $meta_key      Optional. The key of the metadata being deleted. If
 *                              omitted, all metadata associated with the moderation
 *                              item will be deleted.
 * @param string $meta_value    Optional. If present, the metadata will only be
 *                              deleted if the meta_value matches this parameter.
 * @param bool   $delete_all    Optional. If true, delete matching metadata entries
 *                              for all objects, ignoring the specified object_id. Otherwise,
 *                              only delete matching metadata entries for the specified
 *                              moderation item. Default: false.
 *
 * @return bool True on success, false on failure.
 * @global wpdb  $wpdb          WordPress database abstraction object.
 *
 */
function bp_moderation_delete_meta( $moderation_id, $meta_key = '', $meta_value = '', $delete_all = false ) {

	// Legacy - if no meta_key is passed, delete all for the item.
	if ( empty( $meta_key ) ) {
		$all_meta = bp_moderation_get_meta( $moderation_id );
		$keys     = ! empty( $all_meta ) ? array_keys( $all_meta ) : array();

		// With no meta_key, ignore $delete_all.
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	$retval = true;

	add_filter( 'query', 'bp_filter_metaid_column_name' );
	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'moderation', $moderation_id, $key, $meta_value, $delete_all );
	}
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get metadata for a given moderation item.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param int    $moderation_id ID of the moderation item whose metadata is being requested.
 * @param string $meta_key      Optional. If present, only the metadata matching
 *                              that meta key will be returned. Otherwise, all metadata for the
 *                              moderation item will be fetched.
 * @param bool   $single        Optional. If true, return only the first value of the
 *                              specified meta_key. This parameter has no effect if meta_key is not
 *                              specified. Default: true.
 *
 * @return mixed The meta value(s) being requested.
 */
function bp_moderation_get_meta( $moderation_id = 0, $meta_key = '', $single = true ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = get_metadata( 'moderation', $moderation_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	/**
	 * Filters the metadata for a specified moderation item.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param mixed  $retval        The meta values for the moderation item.
	 * @param int    $moderation_id ID of the moderation item.
	 * @param string $meta_key      Meta key for the value being requested.
	 * @param bool   $single        Whether to return one matched meta key row or all.
	 */
	return apply_filters( 'bp_moderation_get_meta', $retval, $moderation_id, $meta_key, $single );
}

/**
 * Update a piece of moderation meta.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param int    $moderation_id ID of the moderation item whose metadata is being updated.
 * @param string $meta_key      Key of the metadata being updated.
 * @param mixed  $meta_value    Value to be set.
 * @param mixed  $prev_value    Optional. If specified, only update existing metadata entries
 *                              with the specified value. Otherwise, update all entries.
 *
 * @return bool|int Returns false on failure. On successful update of existing
 *                  metadata, returns true. On successful creation of new metadata,
 *                  returns the integer ID of the new metadata row.
 */
function bp_moderation_update_meta( $moderation_id, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = update_metadata( 'moderation', $moderation_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of moderation metadata.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param int    $moderation_id ID of the moderation item.
 * @param string $meta_key      Metadata key.
 * @param mixed  $meta_value    Metadata value.
 * @param bool   $unique        Optional. Whether to enforce a single metadata value for the
 *                              given key. If true, and the object already has a value for
 *                              the key, no change will be made. Default: false.
 *
 * @return int|bool The meta ID on successful update, false on failure.
 */
function bp_moderation_add_meta( $moderation_id, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = add_metadata( 'moderation', $moderation_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}
