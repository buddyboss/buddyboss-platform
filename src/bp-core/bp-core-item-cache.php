<?php
/**
 * BuddyBoss Item Cache.
 *
 * Core contains the commonly used functions, classes, and APIs.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

function bb_insert_cache_item( $item_id, $item_type, $content ) {
	global $wpdb;
	$active_components = bp_get_option( 'bp-active-components', array() );
	if ( ! isset( $active_components[ $item_type ] ) || empty( $active_components[ $item_type ] ) ) {
		return false;
	}

	// insert code into to store into DB.
	$wpdb->insert(
		$wpdb->prefix . 'bb_item_cache',
		array(
			'user_id'      => get_current_user_id(),
			'item_id'      => $item_id,
			'item_type'    => $item_type,
			'content'      => $content,
			'date_created' => bp_core_current_time(),
		)
	);
}

function bb_fetch_cache_item( $item_id, $item_type, $user_id = false ) {
	global $wpdb;
	$active_components = bp_get_option( 'bp-active-components', array() );
	if ( ! isset( $active_components[ $item_type ] ) || empty( $active_components[ $item_type ] ) ) {
		return false;
	}

	$query = $wpdb->prepare(
		"SELECT content from {$wpdb->prefix}bb_item_cache WHERE item_id = %d AND item_type = %s",
		$item_id,
		$item_type
	);

	if ( false !== $user_id ) {
		$query .= $wpdb->prepare( ' AND user_id = %d', (int) $user_id );
	}

	$data = $wpdb->get_col( $query );

	return ! empty( $data ) ? current( $data ) : '';
}

function bb_delete_cache_item( $item_id, $item_type, $user_id = false ) {
	global $wpdb;
	$active_components = bp_get_option( 'bp-active-components', array() );
	if ( ! isset( $active_components[ $item_type ] ) || empty( $active_components[ $item_type ] ) ) {
		return false;
	}

	$params = array(
		'item_id'   => $item_id,
		'item_type' => $item_type,
	);

	if ( false !== $user_id ) {
		$params['user_id'] = $user_id;
	}

	return $wpdb->delete(
		$wpdb->prefix . 'bb_item_cache',
		$params
	);
}
