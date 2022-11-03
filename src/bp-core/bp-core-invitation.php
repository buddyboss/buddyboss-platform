<?php
/**
 * Set up invite meta table to global variable.
 *
 * @since BuddyBoss 2.1.4
 */
function bb_invite_setup_globals() {
	global $wpdb;

	$wpdb->{'invitemeta'} = $wpdb->prefix . 'bp_invitations_invitemeta';
}
add_action( 'bp_setup_globals', 'bb_invite_setup_globals', 10 );

/**
 * Delete metadata for an invitation.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int         $invite_id  ID of the invitation.
 * @param string|bool $meta_key   The key of the row to delete.
 * @param string|bool $meta_value Optional. Metadata value. If specified, only delete
 *                                metadata entries with this value.
 * @param bool        $delete_all Optional. If true, delete matching metadata entries
 *                                for all invitations. Otherwise, only delete matching
 *                                metadata entries for the specified invitation.
 *                                Default: false.
 *
 * @return bool True on success, false on failure.
 */
function invitation_delete_invitemeta( $invite_id, $meta_key = false, $meta_value = false, $delete_all = false ) {
	global $wpdb;

	// Legacy - if no meta_key is passed, delete all for the item.
	if ( empty( $meta_key ) ) {
		$table_name = buddypress()->table_prefix . 'bp_invitations_invitemeta';
		$sql        = "SELECT meta_key FROM {$table_name} WHERE invite_id = %d";
		$query      = $wpdb->prepare( $sql, $invite_id );
		$keys       = $wpdb->get_col( $query );

		// With no meta_key, ignore $delete_all.
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	add_filter( 'query', 'bp_filter_metaid_column_name' );

	$retval = true;
	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'invite', $invite_id, $key, $meta_value, $delete_all );
	}

	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get a piece of invitation metadata.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int    $invite_id ID of the invitation.
 * @param string $meta_key  Metadata key.
 * @param bool   $single    Optional. If true, return only the first value of the
 *                          specified meta_key. This parameter has no effect if
 *                          meta_key is empty.
 *
 * @return mixed Metadata value.
 */
function invitation_get_invitemeta( $invite_id, $meta_key = '', $single = true ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = get_metadata( 'invite', $invite_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Update a piece of invitation metadata.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int    $invite_id  ID of the invitation.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Value to store.
 * @param mixed  $prev_value Optional. If specified, only update existing
 *                           metadata entries with the specified value.
 *                           Otherwise, update all entries.
 *
 * @return bool|int $retval Returns false on failure. On successful update of existing
 *                          metadata, returns true. On successful creation of new metadata,
 *                          returns the integer ID of the new metadata row.
 */
function invitation_update_invitemeta( $invite_id, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = update_metadata( 'invite', $invite_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of invitation metadata.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int    $invite_id  ID of the invitation.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value.
 * @param bool   $unique     Optional. Whether to enforce a single metadata value
 *                           for the given key. If true, and the object already
 *                           has a value for the key, no change will be made.
 *                           Default: false.
 *
 * @return int|bool The meta ID on successful update, false on failure.
 */
function invitation_add_invitemeta( $invite_id, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = add_metadata( 'invite', $invite_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}
