<?php
/**
 * BuddyPress Member Notifications Functions.
 *
 * Functions and filters used in the Notifications component.
 *
 * @package BuddyBoss\Notifications\Functions
 * @since BuddyPress 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Add a notification for a specific user, from a specific component.
 *
 * @since BuddyPress 1.9.0
 *
 * @param array $args {
 *     Array of arguments describing the notification. All are optional.
 *     @type int    $user_id           ID of the user to associate the notification with.
 *     @type int    $item_id           ID of the item to associate the notification with.
 *     @type int    $secondary_item_id ID of the secondary item to associate the
 *                                     notification with.
 *     @type string $component_name    Name of the component to associate the
 *                                     notification with.
 *     @type string $component_action  Name of the action to associate the
 *                                     notification with.
 *     @type string $date_notified     Timestamp for the notification.
 * }
 * @return int|bool ID of the newly created notification on success, false
 *                  on failure.
 */
function bp_notifications_add_notification( $args = array() ) {

	$r = bp_parse_args(
		$args,
		array(
			'user_id'           => 0,
			'item_id'           => 0,
			'secondary_item_id' => 0,
			'component_name'    => '',
			'component_action'  => '',
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
			'allow_duplicate'   => false,
		),
		'notifications_add_notification'
	);

	// Check for existing duplicate notifications.
	if ( ! $r['allow_duplicate'] ) {
		// Date_notified, allow_duplicate don't count toward
		// duplicate status.
		$existing = BP_Notifications_Notification::get(
			array(
				'user_id'           => $r['user_id'],
				'item_id'           => $r['item_id'],
				'secondary_item_id' => $r['secondary_item_id'],
				'component_name'    => $r['component_name'],
				'component_action'  => $r['component_action'],
				'is_new'            => $r['is_new'],
			)
		);

		if ( ! empty( $existing ) ) {
			return false;
		}
	}

	// Setup the new notification.
	$notification                    = new BP_Notifications_Notification();
	$notification->user_id           = $r['user_id'];
	$notification->item_id           = $r['item_id'];
	$notification->secondary_item_id = $r['secondary_item_id'];
	$notification->component_name    = $r['component_name'];
	$notification->component_action  = $r['component_action'];
	$notification->date_notified     = $r['date_notified'];
	$notification->is_new            = $r['is_new'];

	// Save the new notification.
	return $notification->save();
}

/**
 * Get a specific notification by its ID.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $id ID of the notification.
 * @return BP_Notifications_Notification Notification object for ID specified.
 */
function bp_notifications_get_notification( $id ) {
	return new BP_Notifications_Notification( $id );
}

/**
 * Delete a specific notification by its ID.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $id ID of the notification to delete.
 * @return false|int True on success, false on failure.
 */
function bp_notifications_delete_notification( $id ) {
	if ( ! bp_notifications_check_notification_access( bp_displayed_user_id(), $id ) ) {
		return false;
	}

	return BP_Notifications_Notification::delete( array( 'id' => $id ) );
}

/**
 * Mark notification read/unread for a user by ID.
 *
 * Used when clearing out notifications for a specific notification item.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int      $id     ID of the notification.
 * @param int|bool $is_new 0 for read, 1 for unread.
 * @return false|int True on success, false on failure.
 */
function bp_notifications_mark_notification( $id, $is_new = false ) {
	if ( ! bp_notifications_check_notification_access( bp_displayed_user_id(), $id ) ) {
		return false;
	}

	return BP_Notifications_Notification::update(
		array( 'is_new' => $is_new ),
		array( 'id' => $id )
	);
}

/**
 * Get all notifications for a user and cache them.
 *
 * @since BuddyPress 2.1.0
 *
 * @param int $user_id ID of the user whose notifications are being fetched.
 * @return array $notifications Array of notifications for user.
 */
function bp_notifications_get_all_notifications_for_user( $user_id = 0 ) {

	// Default to displayed user if no ID is passed.
	if ( empty( $user_id ) ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	// Get notifications out of the cache, or query if necessary.
	$notifications = wp_cache_get( 'all_for_user_' . $user_id, 'bp_notifications' );
	if ( false === $notifications ) {
		$notifications = BP_Notifications_Notification::get(
			array(
				'user_id' => $user_id,
			)
		);
		wp_cache_set( 'all_for_user_' . $user_id, $notifications, 'bp_notifications' );
	}

	/**
	 * Filters all notifications for a user.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param array $notifications Array of notifications for user.
	 * @param int   $user_id       ID of the user being fetched.
	 */
	return apply_filters( 'bp_notifications_get_all_notifications_for_user', $notifications, $user_id );
}

/**
 * Get a user's unread notifications, grouped by component and action.
 *
 * This function returns a list of notifications collapsed by component + action.
 * See BP_Notifications_Notification::get_grouped_notifications_for_user() for
 * more details.
 *
 * @since BuddyPress 3.0.0
 *
 * @param int $user_id ID of the user whose notifications are being fetched.
 * @return array $notifications
 */
function bp_notifications_get_grouped_notifications_for_user( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	$notifications = wp_cache_get( $user_id, 'bp_notifications_grouped_notifications' );
	if ( false === $notifications ) {
		$notifications = BP_Notifications_Notification::get_grouped_notifications_for_user( $user_id );
		wp_cache_set( $user_id, $notifications, 'bp_notifications_grouped_notifications' );
	}

	return $notifications;
}

/**
 * Get notifications for a specific user.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int    $user_id ID of the user whose notifications are being fetched.
 * @param string $format  Format of the returned values. 'string' returns HTML,
 *                        while 'object' returns a structured object for parsing.
 * @return mixed Object or array on success, false on failure.
 */
function bp_notifications_get_notifications_for_user( $user_id, $format = 'string' ) {
	$bp = buddypress();

	$notifications = bp_notifications_get_grouped_notifications_for_user( $user_id );

	// Calculate a renderable output for each notification type.
	foreach ( $notifications as $notification_item ) {
		$renderable = bb_notification_get_renderable_notifications( $notification_item, $format, 'web' );
	}

	// If renderable is empty array, set to false.
	if ( empty( $renderable ) ) {
		$renderable = false;
	}

	/**
	 * Filters the final array of notifications to be displayed for a user.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param array|bool $renderable Array of notifications to render or false if no notifications.
	 * @param int        $user_id    ID of the user whose notifications are being displayed.
	 * @param string     $format     Display format requested for the notifications.
	 */
	return apply_filters( 'bp_core_get_notifications_for_user', $renderable, $user_id, $format );
}

/** Delete ********************************************************************/

/**
 * Delete notifications for a user by type.
 *
 * Used when clearing out notifications for a specific component when the user
 * has visited that component.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int    $user_id          ID of the user whose notifications are being deleted.
 * @param string $component_name   Name of the associated component.
 * @param string $component_action Name of the associated action.
 * @return int|false True on success, false on failure.
 */
function bp_notifications_delete_notifications_by_type( $user_id, $component_name, $component_action ) {
	return BP_Notifications_Notification::delete(
		array(
			'user_id'          => $user_id,
			'component_name'   => $component_name,
			'component_action' => $component_action,
		)
	);
}

/**
 * Delete notifications for an item ID.
 *
 * Used when clearing out notifications for a specific component when the user
 * has visited that component.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int      $user_id           ID of the user whose notifications are being deleted.
 * @param int      $item_id           ID of the associated item.
 * @param string   $component_name    Name of the associated component.
 * @param string   $component_action  Name of the associated action.
 * @param int|bool $secondary_item_id ID of the secondary associated item.
 * @return int|false True on success, false on failure.
 */
function bp_notifications_delete_notifications_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id = false ) {
	return BP_Notifications_Notification::delete(
		array(
			'user_id'           => $user_id,
			'item_id'           => $item_id,
			'secondary_item_id' => $secondary_item_id,
			'component_name'    => $component_name,
			'component_action'  => $component_action,
		)
	);
}

/**
 * Delete all notifications by type.
 *
 * Used when clearing out notifications for an entire component.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int         $item_id           ID of the user whose notifications are being deleted.
 * @param string      $component_name    Name of the associated component.
 * @param string|bool $component_action  Optional. Name of the associated action.
 * @param int|bool    $secondary_item_id Optional. ID of the secondary associated item.
 * @return int|false True on success, false on failure.
 */
function bp_notifications_delete_all_notifications_by_type( $item_id, $component_name, $component_action = false, $secondary_item_id = false ) {
	return BP_Notifications_Notification::delete(
		array(
			'item_id'           => $item_id,
			'secondary_item_id' => $secondary_item_id,
			'component_name'    => $component_name,
			'component_action'  => $component_action,
		)
	);
}

/**
 * Delete all notifications from a user.
 *
 * Used when clearing out all notifications for a user, when deleted or spammed.
 *
 * @todo This function assumes that items with the user_id in the item_id slot
 *       are associated with that user. However, this will only be true with
 *       certain components (such as Connections). Use with caution!
 *
 * @since BuddyPress 1.9.0
 *
 * @param int    $user_id          ID of the user whose associated items are being deleted.
 * @param string $component_name   Name of the associated component.
 * @param string $component_action Name of the associated action.
 * @return int|false True on success, false on failure.
 */
function bp_notifications_delete_notifications_from_user( $user_id, $component_name, $component_action ) {
	return BP_Notifications_Notification::delete(
		array(
			'item_id'          => $user_id,
			'component_name'   => $component_name,
			'component_action' => $component_action,
		)
	);
}

/**
 * Delete a user's notifications when the user is deleted.
 *
 * @since BuddyPress 2.5.0
 *
 * @param int $user_id ID of the user who is about to be deleted.
 * @return int|false The number of rows deleted, or false on error.
 */
function bp_notifications_delete_notifications_on_user_delete( $user_id ) {
	return BP_Notifications_Notification::delete(
		array(
			'user_id'           => $user_id,
			'item_id'           => false,
			'secondary_item_id' => false,
			'component_action'  => false,
			'component_name'    => false,
		)
	);
}
add_action( 'wpmu_delete_user', 'bp_notifications_delete_notifications_on_user_delete' );
add_action( 'delete_user', 'bp_notifications_delete_notifications_on_user_delete' );

/** Mark **********************************************************************/

/**
 * Mark notifications read/unread for a user by type.
 *
 * Used when clearing out notifications for a specific component when the user
 * has visited that component.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int      $user_id          ID of the user whose notifications are being deleted.
 * @param string   $component_name   Name of the associated component.
 * @param string   $component_action Name of the associated action.
 * @param int|bool $is_new           0 for read, 1 for unread.
 * @return int|false True on success, false on failure.
 */
function bp_notifications_mark_notifications_by_type( $user_id, $component_name, $component_action, $is_new = false ) {
	return BP_Notifications_Notification::update(
		array(
			'is_new' => $is_new,
		),
		array(
			'user_id'          => $user_id,
			'component_name'   => $component_name,
			'component_action' => $component_action,
		)
	);
}

/**
 * Mark notifications read/unread for an item ID.
 *
 * Used when clearing out notifications for a specific component when the user
 * has visited that component.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int      $user_id           ID of the user whose notifications are being deleted.
 * @param int      $item_id           ID of the associated item.
 * @param string   $component_name    Name of the associated component.
 * @param string   $component_action  Name of the associated action.
 * @param int|bool $secondary_item_id ID of the secondary associated item.
 * @param int|bool $is_new            0 for read, 1 for unread.
 * @return int|false True on success, false on failure.
 */
function bp_notifications_mark_notifications_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id = false, $is_new = false ) {
	return BP_Notifications_Notification::update(
		array(
			'is_new' => $is_new,
		),
		array(
			'user_id'           => $user_id,
			'item_id'           => $item_id,
			'secondary_item_id' => $secondary_item_id,
			'component_name'    => $component_name,
			'component_action'  => $component_action,
		)
	);
}

/**
 * Mark all notifications read/unread by type.
 *
 * Used when clearing out notifications for an entire component.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int         $item_id           ID of the user whose notifications are being deleted.
 * @param string      $component_name    Name of the associated component.
 * @param string|bool $component_action  Optional. Name of the associated action.
 * @param int|bool    $secondary_item_id Optional. ID of the secondary associated item.
 * @param int|bool    $is_new            0 for read, 1 for unread.
 * @return int|false True on success, false on failure.
 */
function bp_notifications_mark_all_notifications_by_type( $item_id, $component_name, $component_action = false, $secondary_item_id = false, $is_new = false ) {
	return BP_Notifications_Notification::update(
		array(
			'is_new' => $is_new,
		),
		array(
			'item_id'           => $item_id,
			'secondary_item_id' => $secondary_item_id,
			'component_name'    => $component_name,
			'component_action'  => $component_action,
		)
	);
}

/**
 * Mark all notifications read/unread from a user.
 *
 * Used when clearing out all notifications for a user, when deleted or spammed.
 *
 * @todo This function assumes that items with the user_id in the item_id slot
 *       are associated with that user. However, this will only be true with
 *       certain components (such as Connections). Use with caution!
 *
 * @since BuddyPress 1.9.0
 *
 * @param int      $user_id          ID of the user whose associated items are being deleted.
 * @param string   $component_name   Name of the associated component.
 * @param string   $component_action Name of the associated action.
 * @param int|bool $is_new           0 for read, 1 for unread.
 * @return int|false True on success, false on failure.
 */
function bp_notifications_mark_notifications_from_user( $user_id, $component_name, $component_action, $is_new = false ) {
	return BP_Notifications_Notification::update(
		array(
			'is_new' => $is_new,
		),
		array(
			'item_id'          => $user_id,
			'component_name'   => $component_name,
			'component_action' => $component_action,
		)
	);
}

/** Helpers *******************************************************************/

/**
 * Check if a user has access to a specific notification.
 *
 * Used before deleting a notification for a user.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $user_id         ID of the user being checked.
 * @param int $notification_id ID of the notification being checked.
 * @return bool True if the notification belongs to the user, otherwise false.
 */
function bp_notifications_check_notification_access( $user_id, $notification_id ) {
	return (bool) BP_Notifications_Notification::check_access( $user_id, $notification_id );
}

/**
 * Get a count of unread notification items for a user.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $user_id ID of the user whose unread notifications are being
 *                     counted.
 * @return int Unread notification count.
 */
function bp_notifications_get_unread_notification_count( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	$count = wp_cache_get( $user_id, 'bp_notifications_unread_count' );
	if ( false === $count ) {
		$count = BP_Notifications_Notification::get_total_count(
			array(
				'user_id' => $user_id,
				'is_new'  => true,
			)
		);
		wp_cache_set( $user_id, $count, 'bp_notifications_unread_count' );
	}

	/**
	 * Filters the count of unread notification items for a user.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 2.7.0 Added user ID parameter.
	 *
	 * @param int $count   Count of unread notification items for a user.
	 * @param int $user_id User ID for notifications count.
	 */
	return apply_filters( 'bp_notifications_get_total_notification_count', (int) $count, $user_id );
}

/**
 * Return an array of component names that are currently active and have
 * registered Notifications callbacks.
 *
 * @since BuddyPress 1.9.1
 *
 * @see http://buddypress.trac.wordpress.org/ticket/5300
 *
 * @return array $component_names Array of registered components.
 */
function bp_notifications_get_registered_components() {

	// Load BuddyPress.
	$bp = buddypress();

	// Setup return value.
	$component_names = array();

	// Get the active components.
	$active_components = array_keys( $bp->active_components );

	// Loop through components, look for callbacks, add to return value.
	foreach ( $active_components as $component ) {
		if ( ! empty( $bp->$component->notification_callback ) ) {
			$component_names[] = $component;
		}
		// The extended profile component is identified in the active_components array as 'xprofile'.
		// However, the extended profile child object has the key 'profile' in the $bp object.
		if ( 'xprofile' == $component && ! empty( $bp->profile->notification_callback ) ) {
			$component_names[] = $component;
		}
	}

	/**
	 * Filters active components with registered notifications callbacks.
	 *
	 * @since BuddyPress 1.9.1
	 *
	 * @param array $component_names   Array of registered component names.
	 * @param array $active_components Array of active components.
	 */
	return apply_filters( 'bp_notifications_get_registered_components', $component_names, $active_components );
}

/**
 * Catch and route the 'settings' notifications screen.
 *
 * This is currently unused.
 *
 * @since BuddyPress 1.9.0
 */
function bp_notifications_screen_settings() {}

/** Meta **********************************************************************/

/**
 * Delete a meta entry from the DB for a notification item.
 *
 * @since BuddyPress 2.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int    $notification_id ID of the notification item whose metadata is being deleted.
 * @param string $meta_key        Optional. The key of the metadata being deleted. If
 *                                omitted, all metadata associated with the notification
 *                                item will be deleted.
 * @param string $meta_value      Optional. If present, the metadata will only be
 *                                deleted if the meta_value matches this parameter.
 * @param bool   $delete_all      Optional. If true, delete matching metadata entries
 *                                for all objects, ignoring the specified object_id. Otherwise,
 *                                only delete matching metadata entries for the specified
 *                                notification item. Default: false.
 * @return bool                   True on success, false on failure.
 */
function bp_notifications_delete_meta( $notification_id, $meta_key = '', $meta_value = '', $delete_all = false ) {

	// Legacy - if no meta_key is passed, delete all for the item.
	if ( empty( $meta_key ) ) {
		$all_meta = bp_notifications_get_meta( $notification_id );
		$keys     = ! empty( $all_meta )
			? array_keys( $all_meta )
			: array();

		// With no meta_key, ignore $delete_all.
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	$retval = true;

	add_filter( 'query', 'bp_filter_metaid_column_name' );
	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'notification', $notification_id, $key, $meta_value, $delete_all );
	}
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get metadata for a given notification item.
 *
 * @since BuddyPress 2.3.0
 *
 * @param int    $notification_id ID of the notification item whose metadata is being requested.
 * @param string $meta_key        Optional. If present, only the metadata matching
 *                                that meta key will be returned. Otherwise, all metadata for the
 *                                notification item will be fetched.
 * @param bool   $single          Optional. If true, return only the first value of the
 *                                specified meta_key. This parameter has no effect if meta_key is not
 *                                specified. Default: true.
 * @return mixed                  The meta value(s) being requested.
 */
function bp_notifications_get_meta( $notification_id = 0, $meta_key = '', $single = true ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = get_metadata( 'notification', $notification_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	/**
	 * Filters the metadata for a specified notification item.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param mixed  $retval          The meta values for the notification item.
	 * @param int    $notification_id ID of the notification item.
	 * @param string $meta_key        Meta key for the value being requested.
	 * @param bool   $single          Whether to return one matched meta key row or all.
	 */
	return apply_filters( 'bp_notifications_get_meta', $retval, $notification_id, $meta_key, $single );
}

/**
 * Update a piece of notification meta.
 *
 * @since BuddyPress 1.2.0
 *
 * @param  int    $notification_id ID of the notification item whose metadata is being
 *                                 updated.
 * @param  string $meta_key        Key of the metadata being updated.
 * @param  mixed  $meta_value      Value to be set.
 * @param  mixed  $prev_value      Optional. If specified, only update existing
 *                                 metadata entries with the specified value.
 *                                 Otherwise, update all entries.
 * @return bool|int                Returns false on failure. On successful
 *                                 update of existing metadata, returns true. On
 *                                 successful creation of new metadata,  returns
 *                                 the integer ID of the new metadata row.
 */
function bp_notifications_update_meta( $notification_id, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = update_metadata( 'notification', $notification_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of notification metadata.
 *
 * @since BuddyPress 2.3.0
 *
 * @param int    $notification_id ID of the notification item.
 * @param string $meta_key        Metadata key.
 * @param mixed  $meta_value      Metadata value.
 * @param bool   $unique          Optional. Whether to enforce a single metadata value
 *                                for the given key. If true, and the object already has a value for
 *                                the key, no change will be made. Default: false.
 * @return int|bool               The meta ID on successful update, false on failure.
 */
function bp_notifications_add_meta( $notification_id, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = add_metadata( 'notification', $notification_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get on-screen notification content.
 *
 * @since BuddyBoss 1.7.0
 *
 * @return void
 */
function bb_heartbeat_on_screen_notifications( $response = array(), $data = array() ) {
	$is_on_screen_notification_enable = bp_get_option( '_bp_on_screen_notifications_enable', 0 );

	if ( empty( $is_on_screen_notification_enable ) ) {
		return $response;
	}

	if ( bp_loggedin_user_id() && bp_is_active( 'notifications' ) ) {
		ob_start();
		bp_get_template_part( 'notifications/on-screen' );
		$response['on_screen_notifications'] = ob_get_clean();
		$response['total_notifications']     = bp_notifications_get_unread_notification_count( bp_loggedin_user_id() );
	}

	return $response;
}
// Heartbeat receive for on-screen notification.
add_filter( 'heartbeat_received', 'bb_heartbeat_on_screen_notifications', 11, 2 );
add_filter( 'heartbeat_nopriv_received', 'bb_heartbeat_on_screen_notifications', 11, 2 );

/**
 * Function to verify that notifications background process enabled.
 *
 * @since BuddyBoss 1.9.0
 *
 * @return bool
 */
function bb_notifications_background_enabled() {
	return class_exists( 'BB_Background_Updater' ) && apply_filters( 'bb_notifications_background_enabled', ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) );
}

/**
 * Add notification using background process.
 *
 * @since BuddyBoss 1.9.0
 *
 * @param array  $user_ids          User ids.
 * @param int    $item_id           Item id.
 * @param int    $secondary_item_id Secondary item id.
 * @param string $component_name    Notification component name.
 * @param string $component_action  Notification component action.
 * @param string $date_notified     Notification date.
 * @param bool   $is_new            Setup the notification is unread or read.
 * @param int    $sender_id         Sender user id.
 * @param int    $group_id          Group id.
 */
function bb_add_background_notifications( $user_ids, $item_id, $secondary_item_id, $component_name, $component_action, $date_notified = '', $is_new = true, $sender_id = 0, $group_id = 0 ) {
	if (
		empty( $user_ids ) ||
		empty( $item_id ) ||
		empty( $secondary_item_id ) ||
		empty( $component_name ) ||
		empty( $component_action )
	) {
		return;
	}

	if ( empty( $date_notified ) ) {
		$date_notified = bp_core_current_time();
	}

	foreach ( $user_ids as $user_id ) {

		if ( empty( $user_id ) ) {
			continue;
		}

		// Check the sender is blocked by/blocked/suspended recipient or not.
		if (
			! empty( $sender_id ) &&
			function_exists( 'bb_moderation_allowed_specific_notification' ) &&
			bb_moderation_allowed_specific_notification(
				array(
					'type'              => $component_name,
					'group_id'          => $group_id,
					'recipient_user_id' => $user_id,
				)
			)
		) {
			continue;
		}

		bp_notifications_add_notification(
			array(
				'user_id'           => $user_id,
				'item_id'           => $item_id,
				'secondary_item_id' => $secondary_item_id,
				'component_name'    => $component_name,
				'component_action'  => $component_action,
				'date_notified'     => $date_notified,
				'is_new'            => (int) $is_new,
			)
		);
	}

}

/**
 * Check and re-start the background process if queue is not empty.
 *
 * @since BuddyBoss 1.9.0
 */
function bb_notifications_handle_cron_health_check() {
	global $bb_notifications_background_updater;
	if ( $bb_notifications_background_updater->is_updating() ) {
		$bb_notifications_background_updater->handle_cron_healthcheck();
	}
}

add_action( 'bb_init_notifications_background_updater', 'bb_notifications_handle_cron_health_check' );

/**
 * Add only notifications which are user selected.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param string $querystring Query String.
 * @param string $object      Object.
 *
 * @return mixed|string
 */
function bb_notifications_on_screen_notifications_add( $querystring, $object ) {

	if ( 'notifications' !== $object ) {
		return $querystring;
	}

	$querystring            = bp_parse_args( $querystring );
	$querystring['is_new']  = 1;
	$querystring['user_id'] = get_current_user_id();

	// $heartbeat_settings = apply_filters( 'heartbeat_settings', array() );
	// $global_pulse       = 30;
	// if ( ! empty( $heartbeat_settings['interval'] ) ) {
	// $global_pulse = is_numeric( $heartbeat_settings['interval'] ) ? absint( $heartbeat_settings['interval'] ) : 30;
	// }
	// $date_limit                = gmdate( 'Y-m-d H:i:s', strtotime( "-$global_pulse seconds" ) );
	// $querystring['date_query'] = array(
	// array(
	// 'after' => $date_limit,
	// ),
	// );

	if ( bb_enabled_legacy_email_preference() ) {
		return http_build_query( $querystring );
	}

	$excluded_user_component_actions = bb_disabled_notification_actions_by_user( get_current_user_id() );

	if ( ! empty( $excluded_user_component_actions ) ) {
		$excluded_action                = bb_notification_excluded_component_actions();
		$querystring['excluded_action'] = array_unique( array_merge( $excluded_action, $excluded_user_component_actions ) );
	}

	return http_build_query( $querystring );
}

/**
 * Excluded disabled notification actions by user.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param int    $user_id Current user id.
 * @param string $type    Type of notification preference. email, web or app.
 *
 * @return array
 */
function bb_disabled_notification_actions_by_user( $user_id = 0, $type = 'web' ) {
	if ( empty( $user_id ) || bb_enabled_legacy_email_preference() ) {
		return array();
	}

	// All preferences registered.
	$preferences = bb_register_notification_preferences();

	// Saved notification from backend default settings.
	$enabled_all_notification = bp_get_option( 'bb_enabled_notification', array() );

	// Enabled default notification from preferences.
	$all_notifications = array();

	// Enabled default notification from backend.
	$settings_by_admin = array();

	if ( empty( $preferences ) ) {
		return;
	}

	$preferences = array_column( $preferences, 'fields', null );
	foreach ( $preferences as $key => $val ) {
		$all_notifications = array_merge( $all_notifications, $val );
	}

	$all_notifications = array_map(
		function ( $n ) use ( $type ) {
			if (
				! empty( $n['notifications'] ) &&
				in_array( $type, array( 'web', 'app' ), true )
			) {
				$n['key'] = $n['key'] . '_' . $type;

				return $n;
			} elseif (
				! empty( $n['email_types'] ) &&
				'email' === $type
			) {
				$n['key'] = $n['key'] . '_' . $type;

				return $n;
			}

		},
		$all_notifications
	);

	$all_actions = array_column( array_filter( $all_notifications ), 'notifications', 'key' );

	if ( empty( $all_actions ) ) {
		return;
	}

	foreach ( $all_actions as $key => $val ) {
		$all_actions[ $key ] = array_column( array_filter( $val ), 'component_action' );
	}

	$admin_excluded_actions = array();
	$all_notifications      = array_column( array_filter( $all_notifications ), 'default', 'key' );

	if ( ! empty( $enabled_all_notification ) ) {
		foreach ( $enabled_all_notification as $key => $types ) {
			if ( isset( $types['main'] ) && 'no' === $types['main'] ) {
				$admin_excluded_actions = array_merge( $admin_excluded_actions, $all_actions[ $key . '_' . $type ] );
			}
			if ( isset( $types[ $type ] ) ) {
				$settings_by_admin[ $key . '_' . $type ] = $types[ $type ];
			}
		}
	}

	$notifications          = bp_parse_args( $settings_by_admin, $all_notifications );
	$excluded_actions       = array();
	$notifications_type_key = 'enable_notification';
	if ( in_array( $type, array( 'web', 'app' ), true ) ) {
		$notifications_type_key = $notifications_type_key . '_' . $type;
	}

	foreach ( $notifications as $key => $val ) {
		$user_val = get_user_meta( $user_id, $key, true );
		if ( $user_val ) {
			$notifications[ $key ] = $user_val;
		}

		if ( 'no' === $notifications[ $key ] && isset( $all_actions[ $key ] ) ) {
			$excluded_actions = array_merge( $excluded_actions, $all_actions[ $key ] );
		}

		// Add in excluded action if the settings is disabled from frontend top bar Enable Notification option.
		if ( 'no' === bp_get_user_meta( $user_id, $notifications_type_key, true ) ) {
			$excluded_actions = array_merge( $excluded_actions, $all_actions[ $key ] );
		}
	}

	// Add global disabled notification from admin.
	if ( ! empty( $admin_excluded_actions ) ) {
		$excluded_actions = array_merge( $excluded_actions, $admin_excluded_actions );
	}

	$excluded_actions = array_unique( $excluded_actions );

	return $excluded_actions;
}

/**
 * Exclude the messages notifications.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param array $component_names Component names.
 *
 * @return array Return the component name.
 */
function bb_notification_exclude_group_message_notification( $component_names ) {

	if ( ! bp_is_active( 'messages' ) ) {
		return $component_names;
	}

	$hide_message_notification = bb_hide_messages_from_notification_enabled();

	if (
		function_exists( 'bb_enabled_legacy_email_preference' ) &&
		! bb_enabled_legacy_email_preference() &&
		true === $hide_message_notification &&
		in_array( 'messages', $component_names, true )
	) {
		unset( $component_names[ array_search( 'messages', $component_names, true ) ] );
	}

	return $component_names;

}
// Hide messages notifications from the notifications list.
add_filter( 'bp_notifications_get_registered_components', 'bb_notification_exclude_group_message_notification', 999, 1 );

/**
 * Check the notification is legacy or modern.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param int $notification_id Notification ID.
 *
 * @return bool True if the notification is legacy otherwise false.
 */
function bb_notifications_is_legacy_notification( $notification_id = 0 ) {

	/**
	 * Filters the notification is legacy or modern.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @param bool $is_legacy_notification The notification is legacy or modern.
	 * @param int  $notification_id        Notification ID.
	 */
	return (bool) apply_filters( 'bb_notifications_is_legacy_notification', (bool) ! bp_notifications_get_meta( $notification_id, 'is_modern', true ), $notification_id );
}

/**
 * Get avatar for notification user.
 *
 * @since BuddyBoss 1.7.0
 *
 * @return void
 */
function bb_notification_avatar() {
	$notification     = buddypress()->notifications->query_loop->notification;
	$component        = $notification->component_name;
	$component_action = $notification->component_action;

	switch ( $component ) {
		case 'groups':
			if ( ! empty( $notification->item_id ) ) {
				$item_id = $notification->item_id;
				$object  = 'group';
			}
			break;
		case 'follow':
		case 'friends':
			if ( ! empty( $notification->item_id ) ) {
				$item_id = $notification->item_id;
				$object  = 'user';
			}
			break;
		case has_action( 'bb_notification_avatar_' . $component ):
			do_action( 'bb_notification_avatar_' . $component );
			break;
		default:
			if ( ! empty( $notification->secondary_item_id ) ) {
				$item_id = $notification->secondary_item_id;
				$object  = 'user';
			} elseif ( ! empty( $notification->item_id ) ) {
				$item_id = $notification->item_id;
				$object  = 'user';
			} else {
				$item_id = 0;
				$object  = 'notification';
			}
			break;
	}

	switch ( $component_action ) {
		case 'bb_groups_new_request':
		case 'bb_groups_subscribed_discussion':
		case 'bb_groups_subscribed_activity':
			if ( ! empty( $notification->secondary_item_id ) ) {
				$item_id = $notification->secondary_item_id;
				$object  = 'user';
			}
			break;
	}

	if ( isset( $item_id, $object ) ) {

		if ( 'notification' === $object ) {
			bb_get_default_notification_avatar( 'thumb', $notification );
			// Get the small icon for the notification which will print beside the avatar.
			echo wp_kses_post( bb_notification_small_icon( $component_action, true, $notification ) );
		} else {
			if ( 'group' === $object ) {
				$group = new BP_Groups_Group( $item_id );
				$link  = bp_get_group_permalink( $group );
			} else {
				$user = new WP_User( $item_id );
				$link = bp_core_get_user_domain( $user->ID, $user->user_nicename, $user->user_login );
			}

			$moderation_class = isset( $user ) && function_exists( 'bp_moderation_is_user_suspended' ) && bp_moderation_is_user_suspended( $user->ID ) ? 'bp-user-suspended' : '';
			$moderation_class = isset( $user ) && function_exists( 'bp_moderation_is_user_blocked' ) && bp_moderation_is_user_blocked( $user->ID ) ? $moderation_class . ' bp-user-blocked' : $moderation_class;
			?>
			<a href="<?php echo ! empty( $link ) ? esc_url( $link ) : ''; ?>" class="<?php echo esc_attr( $moderation_class ); ?>">
				<?php
				echo bp_core_fetch_avatar(
					array(
						'item_id' => $item_id,
						'object'  => $object,
					)
				);

				// Get the small icon for the notification which will print beside the avatar.
				$notification_icon = bb_notification_small_icon( $component_action, true, $notification );
				if ( ! empty( $notification_icon ) ) {
					echo wp_kses_post( $notification_icon );
				}
				?>
				<?php ( isset( $user ) ? bb_user_presence_html( $user->ID ) : '' ); ?>
			</a>
			<?php
		}
	}
}

/**
 * Get avatar url for notification user.
 *
 * @since BuddyBoss 2.0.4
 *
 * @param object $notification Notification object.
 *
 * @return false|mixed|string|void
 */
function bb_notification_avatar_url( $notification = '' ) {
	if ( empty( $notification ) ) {
		$notification = buddypress()->notifications->query_loop->notification;
	}

	$component        = $notification->component_name;
	$component_action = $notification->component_action;

	switch ( $component ) {
		case 'groups':
			if ( ! empty( $notification->item_id ) ) {
				$item_id = $notification->item_id;
				$object  = 'group';
			}
			break;
		case 'follow':
		case 'friends':
			if ( ! empty( $notification->item_id ) ) {
				$item_id = $notification->item_id;
				$object  = 'user';
			}
			break;
		case has_action( 'bb_notification_avatar_' . $component ):
			do_action( 'bb_notification_avatar_' . $component );
			break;
		default:
			if ( ! empty( $notification->secondary_item_id ) ) {
				$item_id = $notification->secondary_item_id;
				$object  = 'user';
			} elseif ( ! empty( $notification->item_id ) ) {
				$item_id = $notification->item_id;
				$object  = 'user';
			} else {
				$item_id = 0;
				$object  = 'notification';
			}
			break;
	}

	switch ( $component_action ) {
		case 'bb_groups_new_request':
			if ( ! empty( $notification->secondary_item_id ) ) {
				$item_id = $notification->secondary_item_id;
				$object  = 'user';
			}
			break;
	}

	$image_url = '';

	if ( isset( $item_id, $object ) ) {

		add_filter( 'bp_core_gravatar_url_args', 'bb_notification_avatar_url_args' );

		if ( 'notification' === $object ) {
			$image_url = bb_get_notification_avatar_url( 'thumb' );
		} else {
			$image_url = bp_core_fetch_avatar(
				array(
					'item_id' => $item_id,
					'object'  => $object,
					'html'    => false,
				)
			);
		}

		remove_filter( 'bp_core_gravatar_url_args', 'bb_notification_avatar_url_args' );
	}

	return apply_filters( 'bb_notification_avatar_url', str_replace( '&#038;', '&', $image_url ), $notification );
}

/**
 * Get Default Avatar for notification.
 *
 * @since BuddyBoss 2.0.2
 *
 * @param string        $size         Size of the notification icon, 'full' or 'thumb'.
 * @param object|string $notification Notification object.
 *
 * @return void
 */
function bb_get_default_notification_avatar( $size = 'full', $notification = '' ) {
	if ( ! in_array( $size, array( 'thumb', 'full' ), true ) ) {
		$size = 'full';
	}

	$image_url = bb_get_notification_avatar_url( $size );

	printf(
		'<img src="%1$s" class="avatar photo %2$s" width="%3$s" height="%3$s" alt="%4$s">',
		esc_url( $image_url ),
		esc_attr( ( 'thumb' === $size ? 'avatar-150' : 'avatar-300 ' ) ),
		esc_attr( ( 'thumb' === $size ? '150' : '300 ' ) ),
		esc_attr__( 'Notification Icon', 'buddyboss' )
	);
}

/**
 * Get Default notification avatar URL.
 *
 * @since BuddyBoss 2.0.2
 *
 * @param string $size Size of the notification icon, 'full' or 'thumb'.
 *
 * @return mixed|void
 */
function bb_get_notification_avatar_url( $size = 'full' ) {

	$bb_avatar_filename = 'notification.png';
	if ( 'full' !== $size ) {
		$bb_avatar_filename = 'notification-50.png';
	}

	/**
	 * Filters default BuddyBoss notification avatar URL.
	 *
	 * @since BuddyBoss 2.0.2
	 *
	 * @param string $value Default BuddyBoss notification avatar URL.
	 * @param string $size  This parameter specifies whether you'd like the 'full' or 'thumb' avatar.
	 */
	return apply_filters( 'bb_get_buddyboss_avatar_avatar_url', esc_url( buddypress()->plugin_url . 'bp-core/images/' . $bb_avatar_filename ), $size );
}

/**
 * Get the small icon for the notification which will print beside the avatar.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param string $component_action Component Action.
 * @param bool   $html             Whether to get only class or with i tag.
 * @param object $notification     Notification object.
 *
 * @return mixed|string|void
 */
function bb_notification_small_icon( $component_action, $html = true, $notification = false ) {

	$all_registered_notifications = bb_register_notifications();

	if ( empty( $component_action ) || empty( $all_registered_notifications ) ) {
		return;
	}

	$icons = array_column( $all_registered_notifications, 'icon_class', 'component_action' );

	if ( isset( $icons[ $component_action ] ) && ! empty( $icons[ $component_action ] ) ) {
		$icon_class = bb_get_notification_conditional_icon( $notification );
		if ( empty( $icon_class ) ) {
			$icon_class = $icons[ $component_action ];
		}
		if ( $html ) {
			return '<i class=" ' . esc_attr( $icon_class ) . '"></i>';
		} else {
			return $icon_class;
		}
	}

	return;

}

/**
 * Get the small icon for the notification which will print beside the avatar.
 *
 * @since BuddyBoss 2.0.2
 *
 * @param object $notification Notification object.
 *
 * @return string $icon_class Icon class.
 */
function bb_get_notification_conditional_icon( $notification ) {

	$icon_class = '';

	if ( empty( $notification ) || empty( $notification->component_action ) ) {
		return $icon_class;
	}

	switch ( $notification->component_action ) {
		case 'bb_new_mention':
			$notification_type = bp_notifications_get_meta( $notification->id, 'type', true );
			if ( ! empty( $notification->component_name ) && 'core' === $notification->component_name ) {
				$icon_class = 'bb-icon-f bb-icon-comment-square-dots';
			} elseif ( 'activity_comment' === $notification_type ) {
				$icon_class = 'bb-icon-f bb-icon-comment-activity';
			} elseif ( 'activity_post' === $notification_type ) {
				$icon_class = 'bb-icon-f bb-icon-activity';
			} elseif ( 'forum_reply' === $notification_type ) {
				$icon_class = 'bb-icon-f bb-icon-reply';
			} elseif ( 'post_comment' === $notification_type ) {
				$icon_class = 'bb-icon-f bb-icon-comment-square-dots';
			} elseif ( 'forum_topic' === $notification_type ) {
				$icon_class = 'bb-icon-f bb-icon-comment-square';
			}
			break;
		case 'bb_groups_new_message':
		case 'bb_messages_new':
			$item_id = $notification->item_id;
			// Get message thread ID.
			$message      = new BP_Messages_Message( $item_id );
			$media_ids    = bp_messages_get_meta( $item_id, 'bp_media_ids', true );
			$document_ids = bp_messages_get_meta( $item_id, 'bp_document_ids', true );
			$video_ids    = bp_messages_get_meta( $item_id, 'bp_video_ids', true );
			$gif_data     = bp_messages_get_meta( $item_id, '_gif_data', true );
			$excerpt      = wp_strip_all_tags( $message->message );

			if ( '&nbsp;' === $excerpt ) {
				$excerpt = '';
			} else {
				$excerpt = '"' . bp_create_excerpt(
					$excerpt,
					50,
					array(
						'ending' => __( '&hellip;', 'buddyboss' ),
					)
				) . '"';

				$excerpt = str_replace( '&hellip;"', '&hellip;', $excerpt );
				$excerpt = str_replace( '""', '', $excerpt );
			}

			if ( ! empty( $excerpt ) ) {
				$icon_class = 'bb-icon-f bb-icon-comment-square';
			} elseif ( $media_ids ) {
				$icon_class = 'bb-icon-f bb-icon-image';
			} elseif ( $document_ids ) {
				$icon_class = 'bb-icon-f bb-icon-file-doc';
			} elseif ( $video_ids ) {
				$icon_class = 'bb-icon-f bb-icon-video';
			} elseif ( ! empty( $gif_data ) ) {
				$icon_class = 'bb-icon-f bb-icon-gif';
			} else {
				$icon_class = 'bb-icon-f bb-icon-comment-square';
			}

			break;
		case 'bb_activity_following_post':
		case 'bb_groups_subscribed_activity':
			$item_id      = $notification->item_id;
			$activity     = new BP_Activity_Activity( $item_id );
			$media_ids    = bp_activity_get_meta( $item_id, 'bp_media_ids', true );
			$document_ids = bp_activity_get_meta( $item_id, 'bp_document_ids', true );
			$video_ids    = bp_activity_get_meta( $item_id, 'bp_video_ids', true );
			$gif_data     = bp_activity_get_meta( $item_id, '_gif_data', true );
			$excerpt      = wp_strip_all_tags( $activity->content );

			if ( '&nbsp;' === $excerpt ) {
				$excerpt = '';
			} else {
				$excerpt = '"' . bp_create_excerpt(
					$excerpt,
					50,
					array(
						'ending' => __( '&hellip;', 'buddyboss' ),
					)
				) . '"';

				$excerpt = str_replace( '&hellip;"', '&hellip;', $excerpt );
				$excerpt = str_replace( '""', '', $excerpt );
			}

			if ( ! empty( $excerpt ) ) {
				$icon_class = 'bb-icon-f bb-icon-activity';
			} elseif ( $media_ids ) {
				$icon_class = 'bb-icon-f bb-icon-image';
			} elseif ( $document_ids ) {
				$icon_class = 'bb-icon-f bb-icon-file-doc';
			} elseif ( $video_ids ) {
				$icon_class = 'bb-icon-f bb-icon-video';
			} elseif ( ! empty( $gif_data ) ) {
				$icon_class = 'bb-icon-f bb-icon-activity';
			} else {
				$icon_class = 'bb-icon-f bb-icon-activity';
			}
			break;

	}

	return apply_filters( 'bb_get_notification_conditional_icon', $icon_class, $notification );

}

/**
 * Function to remove the size and rating to get the gravatar browser notification avatar.
 *
 * @since BuddyBoss 2.0.4
 *
 * @param array $args Array of arguments.
 *
 * @return mixed
 */
function bb_notification_avatar_url_args( $args ) {
	unset( $args['s'], $args['r'] );
	return $args;
}

/**
 * Notification renderable.
 *
 * @since BuddyBoss 2.0.5
 *
 * @param object $notification_item Notification item.
 * @param string $format            Format of the notification.
 * @param string $screen            Screen of the notification.
 *
 * @return mixed|void
 */
function bb_notification_get_renderable_notifications( $notification_item, $format = 'string', $screen = 'web' ) {

	$bp = buddypress();

	if ( empty( $notification_item ) || ! is_object( $notification_item ) ) {
		return;
	}

	if ( ! isset( $notification_item->total_count ) ) {
		$notification_item->total_count = 1;
	}

	$component_name = $notification_item->component_name;
	// We prefer that extended profile component-related notifications use
	// the component_name of 'xprofile'. However, the extended profile child
	// object in the $bp object is keyed as 'profile', which is where we need
	// to look for the registered notification callback.
	if ( 'xprofile' == $notification_item->component_name ) {
		$component_name = 'profile';
	}

	// Callback function exists.
	if ( isset( $bp->{$component_name}->notification_callback ) && is_callable( $bp->{$component_name}->notification_callback ) ) {

		// Function should return an object.
		if ( 'object' === $format ) {

			// Retrieve the content of the notification using the callback.
			$content = call_user_func( $bp->{$component_name}->notification_callback, $notification_item->component_action, $notification_item->item_id, $notification_item->secondary_item_id, $notification_item->total_count, 'array', $notification_item->id, $screen );

			$content = apply_filters(
				'bb_notifications_get_component_notification',
				$content,
				$notification_item->item_id,
				$notification_item->secondary_item_id,
				$notification_item->total_count,
				'array',
				$notification_item->component_action,
				$component_name,
				$notification_item->id,
				$screen
			);

			// Create the object to be returned.
			$notification_object = $notification_item;

			// Minimal backpat with non-compatible notification
			// callback functions.
			if ( is_string( $content ) ) {
				$notification_object->content = $content;
				$notification_object->href    = bp_loggedin_user_domain();
			} else {
				$notification_object->content = ( isset( $content ) && isset( $content['text'] ) ? $content['text'] : '' );
				$notification_object->href    = ( isset( $content ) && isset( $content['link'] ) ? $content['link'] : '' );
				$notification_object->title   = ( isset( $content ) && isset( $content['title'] ) ? $content['title'] : '' );
				$notification_object->image   = ( isset( $content ) && isset( $content['image'] ) ? $content['image'] : '' );
			}

			$renderable = $notification_object;

			// Return an array of content strings.
		} else {

			$content = call_user_func( $bp->{$component_name}->notification_callback, $notification_item->component_action, $notification_item->item_id, $notification_item->secondary_item_id, $notification_item->total_count, 'string', $notification_item->id, $screen );

			$content = apply_filters(
				'bb_notifications_get_component_notification',
				$content,
				$notification_item->item_id,
				$notification_item->secondary_item_id,
				$notification_item->total_count,
				'string',
				$notification_item->component_action,
				$component_name,
				$notification_item->id,
				$screen
			);

			$renderable = $content;
		}

		// @deprecated format_notification_function - 1.5
	} elseif ( isset( $bp->{$component_name}->format_notification_function ) && function_exists( $bp->{$component_name}->format_notification_function ) ) {
		$renderable = call_user_func( $bp->{$component_name}->notification_callback, $notification_item->component_action, $notification_item->item_id, $notification_item->secondary_item_id, $notification_item->total_count );

		// Allow non BuddyPress components to hook in.
	} else {

		// The array to reference with apply_filters_ref_array().
		$ref_array = array(
			$notification_item->component_action,
			$notification_item->item_id,
			$notification_item->secondary_item_id,
			$notification_item->total_count,
			$format,
			$notification_item->component_action, // Duplicated so plugins can check the canonical action name.
			$component_name,
			$notification_item->id,
			$screen,
		);

		// Function should return an object.
		if ( 'object' === $format ) {

			/**
			 * Filters the notification content for notifications created by plugins.
			 * If your plugin extends the {@link BP_Component} class, you should use the
			 * 'notification_callback' parameter in your extended
			 * {@link BP_Component::setup_globals()} method instead.
			 *
			 * @since BuddyPress 1.9.0
			 * @since BuddyPress 2.6.0 Added $component_action_name, $component_name, $id as parameters.
			 *
			 * @param string $content               Component action. Deprecated. Do not do checks against this! Use
			 *                                      the 6th parameter instead - $component_action_name.
			 * @param int    $item_id               Notification item ID.
			 * @param int    $secondary_item_id     Notification secondary item ID.
			 * @param int    $total_items           Number of notifications with the same action.
			 * @param string $format                Format of return. Either 'string' or 'object'.
			 * @param string $component_action_name Canonical notification action.
			 * @param string $component_name        Notification component ID.
			 * @param int    $id                    Notification ID.
			 *
			 * @return string|array If $format is 'string', return a string of the notification content.
			 *                      If $format is 'object', return an array formatted like:
			 *                      array( 'text' => 'CONTENT', 'link' => 'LINK' )
			 */
			$content = apply_filters_ref_array( 'bp_notifications_get_notifications_for_user', $ref_array );

			// Create the object to be returned.
			$notification_object = $notification_item;

			// Minimal backpat with non-compatible notification
			// callback functions.
			if ( is_string( $content ) ) {
				$notification_object->content = $content;
				$notification_object->href    = bp_loggedin_user_domain();
			} else {
				$notification_object->content = $content['text'];
				$notification_object->href    = $content['link'];
				$notification_object->title   = ( isset( $content['title'] ) ? $content['title'] : '' );
				$notification_object->image   = ( isset( $content['image'] ) ? $content['image'] : '' );
			}

			$renderable = $notification_object;

			// Return an array of content strings.
		} else {

			/** This filters is documented in bp-notifications/bp-notifications-functions.php */
			$renderable = apply_filters_ref_array( 'bp_notifications_get_notifications_for_user', $ref_array );
		}
	}

	/**
	 * Filter will return notifications data which will render.
	 *
	 * @since BuddyBoss 2.2.7
	 *
	 * @param mixed  $renderable        Notifications data which will render.
	 * @param string $notification_item Notifications item.
	 * @param string $format            Format of the notification.
	 * @param string $screen            Screen of the notification.
	 */
	return apply_filters( 'bb_notification_get_renderable_notifications', $renderable, $notification_item, $format, $screen );
}


/**
 * Function to check the Hide messages from notifications is enabled or not.
 *
 * @since BuddyBoss 2.1.4
 *
 * @return bool
 */
function bb_hide_messages_from_notification_enabled() {
	return (bool) apply_filters( 'bb_hide_messages_from_notification_enabled', bp_get_option( 'hide_message_notification', 1 ) );
}

/**
 * Function to check the Delay email notifications for new messages is enabled or not.
 *
 * @since BuddyBoss 2.1.4
 *
 * @return bool
 */
function bb_delay_email_notifications_enabled() {
	return (bool) apply_filters( 'bb_delay_email_notifications_enabled', bp_get_option( 'delay_email_notification', 1 ) );
}

/**
 * Function to check the Delay email notifications for new messages is enabled with pusher or not.
 *
 * @since BuddyBoss 2.1.4
 *
 * @return bool
 */
function bb_check_delay_email_notification() {
	return (bool) ( false === bb_enabled_legacy_email_preference() && bb_delay_email_notifications_enabled() );
}

/**
 * Function to check current user it offline for sending the push notification.
 *
 * @since BuddyBoss 2.2
 *
 * @param int   $user_id User ID.
 * @param array $args    Argument related to the push notification
 *
 * @return bool
 */
function bb_can_send_push_notification( $user_id, $args = array() ) {
	// Parse args.
	$r = bp_parse_args(
		$args,
		array(
			'skip_active_user' => false,
		)
	);

	$presence_time = (int) apply_filters( 'bb_push_notification_presence_time', bb_presence_interval() + bb_presence_time_span() );
	$user_presence = bb_is_online_user( $user_id, $presence_time );

	if ( true === $user_presence && true === $r['skip_active_user'] ) {
		return false;
	}

	return true;
}

/**
 * Update notification meta on after save.
 *
 * @since BuddyBoss 2.2.3
 *
 * @param BP_Notifications_Notification $notification Notification object.
 */
function bb_notification_after_save_meta( $notification ) {
	if (
		! empty( $notification->id ) &&
		! empty( $notification->component_action )
	) {
		$usernames = array();
		if (
			bp_is_active( 'activity' ) &&
			in_array(
				$notification->component_action,
				array(
					'bb_activity_following_post',
					'bb_groups_subscribed_activity',
					'bb_activity_comment',
				),
				true
			)
		) {
			$activity = new BP_Activity_Activity( $notification->item_id );

			if ( ! empty( $activity ) && empty( $activity->content ) && function_exists( 'bp_blogs_activity_comment_content_with_read_more' ) ) {
				add_filter( 'bp_blogs_activity_comment_content_with_read_more', '__return_false' );
				$activity->content = bp_blogs_activity_comment_content_with_read_more( '', $activity );
				add_filter( 'bp_blogs_activity_comment_content_with_read_more', '__return_true' );
			}

			$usernames = ! empty( $activity ) && ! empty( $activity->content ) && bp_activity_do_mentions() ? bp_activity_find_mentions( $activity->content ) : array();
		} elseif (
			'core' === $notification->component_name &&
			in_array(
				$notification->component_action,
				array(
					'bb_posts_new_comment_reply',
				),
				true
			)
		) {
			$comment   = get_comment( $notification->item_id );
			$usernames = ! empty( $comment ) && ! empty( $comment->comment_content ) ? bp_find_mentions_by_at_sign( array(), $comment->comment_content ) : array();
		} elseif (
			bp_is_active( 'forums' ) &&
			in_array(
				$notification->component_action,
				array(
					'bb_groups_subscribed_discussion',
					'bb_forums_subscribed_reply',
					'bb_forums_subscribed_discussion',
					'bbp_new_reply',
				),
				true
			)
		) {
			$content = '';
			if (
				'bb_groups_subscribed_discussion' === $notification->component_action ||
				'bb_forums_subscribed_discussion' === $notification->component_action
			) {
				$content = bbp_kses_data( bbp_get_topic_content( $notification->item_id ) );
			}
			if (
				'bb_forums_subscribed_reply' === $notification->component_action ||
				'bbp_new_reply' === $notification->component_action
			) {
				$content = bbp_kses_data( bbp_get_reply_content( $notification->item_id ) );
			}
			$usernames = ! empty( $content ) ? bp_find_mentions_by_at_sign( array(), $content ) : array();
		}

		if ( ! empty( $usernames ) ) {
			$user_id     = $notification->user_id;
			$mention_web = false;
			$mention_app = false;
			if ( isset( $usernames[ $user_id ] ) ) {
				$mention_web = bb_web_notification_enabled() && true === bb_is_notification_enabled( $user_id, 'bb_new_mention', 'web' );
				$mention_app = bb_app_notification_enabled() && true === bb_is_notification_enabled( $user_id, 'bb_new_mention', 'app' );
			}

			bp_notifications_update_meta( $notification->id, 'not_send_app', $mention_app );
			bp_notifications_update_meta( $notification->id, 'not_send_web', $mention_web );
		}
	}
}

/**
 * Manage App push notifation base on mention.
 *
 * @since BuddyBoss 2.2.3
 *
 * @param string $content           Component action.
 * @param string $component_name    Notification component ID.
 * @param string $component_action  Canonical notification action.
 * @param int    $item_id           Notification item ID.
 * @param int    $secondary_item_id Notification secondary item ID.
 * @param int    $notification_id   Notification ID.
 * @param string $format            Format of return. Either 'string' or 'object'.
 * @param string $screen            Notification Screen type.
 *
 * @return array|mixed
 */
function bb_notification_manage_app_push_notification( $content, $component_name, $component_action, $item_id, $secondary_item_id, $notification_id, $format = 'object', $screen = 'web' ) {
	if (
		'app_push' !== $screen ||
		empty( $notification_id ) ||
		empty( $component_action ) ||
		! in_array(
			$component_action,
			array(
				'bb_activity_following_post',
				'bb_groups_subscribed_activity',
				'bb_groups_subscribed_discussion',
				'bb_forums_subscribed_reply',
				'bb_forums_subscribed_discussion',
				'bbp_new_reply',
			),
			true
		)
	) {
		return $content;
	}

	if ( true === (bool) bp_notifications_get_meta( $notification_id, 'not_send_app', true ) ) {
		return array();
	}

	return $content;
}

add_filter( 'bbapp_get_notification_output', 'bb_notification_manage_app_push_notification', 999, 8 );

/**
 * Added where condition to exclude not send onscreen notification.
 *
 * @since BuddyBoss 2.2.3
 *
 * @param string $where_sql Notifications Where sql.
 * @param string $tbl_alias Table alias.
 * @param object $r         Query arguments.
 *
 * @return string
 */
function bb_notifications_on_screen_get_where_conditions( $where_sql, $tbl_alias, $r ) {
	global $bp;
	$where_sql .= " AND {$tbl_alias}.id NOT IN ( SELECT DISTINCT notification_id from {$bp->notifications->table_name_meta} WHERE meta_key = 'not_send_web' and meta_value = 1 )";

	return $where_sql;
}

/**
 * Function to check if notification triggered by blocked/blocked by/suspended/deleted member
 * then this notification will only for read purpose.
 *
 * @since BuddyBoss 2.2.7
 *
 * @param object $notification Notification item.
 *
 * @return bool
 */
function bb_notification_is_read_only( $notification ) {
	static $cache = array();
	// item_id is the user_id and secondary_item_id is the friend_id for the below component action.
	$allowed_component_action = array(
		'bb_connections_request_accepted',
		'bb_connections_new_request',
	);

	if ( empty( $notification->id ) ) {
		return false;
	}

	$cache_key = 'bb_notification_is_read_only_' . $notification->id;

	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	$retval = ! empty( $notification ) &&
	(
		(
				! in_array( $notification->component_action, $allowed_component_action, true ) &&
				! empty( $notification->secondary_item_id ) &&
				bp_is_user_inactive( $notification->secondary_item_id )
		) ||
		(
				in_array( $notification->component_action, $allowed_component_action, true ) &&
				! empty( $notification->item_id ) &&
				bp_is_user_inactive( $notification->item_id )
		) ||
		(
			bp_is_active( 'moderation' ) &&
			(
				(
					! in_array( $notification->component_action, $allowed_component_action, true ) &&
					! empty( $notification->secondary_item_id ) &&
					bb_moderation_moderated_user_ids( $notification->secondary_item_id )
				) ||
				(
					in_array( $notification->component_action, $allowed_component_action, true ) &&
					! empty( $notification->item_id ) &&
					bb_moderation_moderated_user_ids( $notification->item_id )
				) ||
				(
					! empty( $notification->user_id ) &&
					bb_moderation_moderated_user_ids( $notification->user_id )
				)
			)
		)
	);

	$retval = (bool) apply_filters( 'bb_notification_is_read_only', $retval, $notification );

	$cache[ $cache_key ] = $retval;

	return $retval;
}

/**
 * Function will remove link from notification description.
 *
 * @since BuddyBoss 2.2.7
 *
 * @param mixed  $renderable   Notification details.
 * @param object $notification Notification item.
 * @param string $format       Format of the notification.
 * @param string $screen       Screen of the notification.
 *
 * @return string|string[]|null
 */
function bb_notification_get_renderable_notifications_callback( $renderable, $notification, $format, $screen ) {

	if ( true === bb_notification_is_read_only( $notification ) ) {
		if ( 'string' === $format ) {
			$renderable = preg_replace( '#<a.*?>([^>]*)</a>#i', '$1', $renderable );
		} elseif ( 'object' === $format || 'array' === $format ) {
			if ( is_object( $renderable ) ) {
				$renderable->href = '';
			} else {
				$renderable['href'] = '';
			}
		}
	}

	return $renderable;
}
add_filter( 'bb_notification_get_renderable_notifications', 'bb_notification_get_renderable_notifications_callback', 99, 4 );

/**
 * Function will remove link from notification description.
 *
 * @since BuddyBoss 2.2.7
 *
 * @param mixed  $description  Notification details.
 * @param object $notification Notification item.
 *
 * @return string|string[]|null
 */
function bb_get_the_notification_description_callback( $description, $notification ) {

	if ( true === bb_notification_is_read_only( $notification ) ) {
		$description = preg_replace( '#<a.*?>([^>]*)</a>#i', '$1', $description );
	}

	return $description;

}
add_filter( 'bp_get_the_notification_description', 'bb_get_the_notification_description_callback', 99, 2 );

/**
 * Function will forcely read notification which triggered by blocked/blocked by/suspended member.
 *
 * @since BuddyBoss 2.2.7
 */
function bb_notification_read_for_moderated_members() {
	$current_user_id = bp_loggedin_user_id();

	if ( ! $current_user_id ) {
		return;
	}

	$read_notification_migration = bp_get_user_meta( $current_user_id, 'bb_read_notification_migration', true );

	if ( $read_notification_migration ) {
		return;
	}

	global $bp, $wpdb;
	$select_sql  = "SELECT id FROM ( SELECT DISTINCT id FROM {$bp->notifications->table_name}";
	$select_sql .= ' WHERE is_new = 1 AND user_id = ' . bp_loggedin_user_id();

	$select_sql_where = array();
	$all_users        = ( function_exists( 'bb_moderation_moderated_user_ids' ) ? bb_moderation_moderated_user_ids() : array() );

	if ( ! empty( $all_users ) ) {
		$select_sql_where[] = 'secondary_item_id IN ( ' . implode( ',', $all_users ) . ' )';
		$select_sql        .= " AND component_action IN ( 'bb_connections_request_accepted', 'bb_connections_new_request' ) AND item_id IN ( " . implode( ',', $all_users ) . ' )';
	}
	$select_sql_where[] = "secondary_item_id NOT IN ( SELECT DISTINCT ID from {$wpdb->users} )";

	$select_sql .= ' AND ( ' . implode( ' OR ', $select_sql_where ) . ' ) ) AS notifications';

	$update_query = "UPDATE {$bp->notifications->table_name} SET `is_new` = 0 WHERE id IN ({$select_sql})";
	$wpdb->query( $update_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	// Clear notifications cache.
	if (
		function_exists( 'wp_cache_flush_group' ) &&
		function_exists( 'wp_cache_supports' ) &&
		wp_cache_supports( 'flush_group' )
	) {
		wp_cache_flush_group( 'bp-notifications' );
	} else {
		wp_cache_flush();
	}

	bp_update_user_meta( $current_user_id, 'bb_read_notification_migration', true );
}

add_action( 'bp_init', 'bb_notification_read_for_moderated_members', 9 );

/**
 * Function to allow specific notification for forum/activity/message on notification screen.
 *
 * @since BuddyBoss 2.2.7
 *
 * @param bool   $retval       Return true or false.
 * @param object $notification Notification Item.
 *
 * @return bool
 */
function bb_notification_linkable_specific_notification( $retval, $notification ) {
	if (
		(
			'forums' !== $notification->component_name ||
			'activity' !== $notification->component_name ||
			'messages' !== $notification->component_name ||
			'groups' !== $notification->component_name
		) &&
		! in_array(
			$notification->component_action,
			array(
				'bb_forums_subscribed_discussion',
				'bb_forums_subscribed_reply',
				'bb_activity_comment',
				'bb_groups_new_message',
				'bb_groups_subscribed_discussion',
				'bb_groups_new_request',
			),
			true
		)
	) {
		return $retval;
	}

	$group_id  = 0;
	$author_id = 0;

	if ( bp_is_active( 'forums' ) ) {
		$forum_id = 0;
		if ( 'bb_forums_subscribed_reply' === $notification->component_action ) {
			$forum_id  = bbp_get_reply_forum_id( $notification->item_id );
			$topic_id  = bbp_get_reply_topic_id( $notification->item_id );
			$author_id = bbp_get_topic_author_id( $topic_id );
		} elseif (
			'bb_forums_subscribed_discussion' === $notification->component_action ||
			'bb_groups_subscribed_discussion' === $notification->component_action
		) {
			$forum_id = bbp_get_topic_forum_id( $notification->item_id );
		}
		$group_id = bbp_get_forum_group_ids( $forum_id );
		$group_id = ! empty( $group_id ) ? current( $group_id ) : 0;
	}

	if ( bp_is_active( 'activity' ) && 'bb_activity_comment' === $notification->component_action ) {
		$current_activity = new BP_Activity_Activity( $notification->item_id );
		if ( ! empty( $current_activity->id ) ) {
			$original_activity = new BP_Activity_Activity( $current_activity->item_id );
			$group_id          = 'groups' === $original_activity->component ? $original_activity->item_id : '';
		}
	}

	if ( bp_is_active( 'messages' ) && 'bb_groups_new_message' === $notification->component_action ) {
		$group_id = bp_messages_get_meta( $notification->item_id, 'group_id', true );
	}

	if ( bp_is_active( 'groups' ) && 'bb_groups_new_request' === $notification->component_action ) {
		$group_id = $notification->item_id;
	}
	if (
		bp_is_active( 'moderation' ) &&
		(
			// Will check for recipient user.
			bb_moderation_is_user_blocked_by( $notification->secondary_item_id ) ||
			(
				// Will check for loggedin user.
				empty( $group_id ) &&
				! empty( $author_id ) &&
				bp_moderation_is_user_blocked( $notification->secondary_item_id ) &&
				(int) $notification->user_id === (int) $author_id
			)
		) ||
		(
			! empty( $group_id ) &&
			bp_is_active( 'groups' ) &&
			(
				groups_is_user_admin( $notification->user_id, $group_id ) ||
				groups_is_user_mod( $notification->user_id, $group_id )
			)
		)
	) {
		return false;
	}

	return $retval;
}
add_filter( 'bb_notification_is_read_only', 'bb_notification_linkable_specific_notification', 10, 2 );

/**
 * Function to exclude the notification actions.
 *
 * @since BuddyBoss 2.2.9
 *
 * @return array
 */
function bb_notification_excluded_component_actions() {
	$actions = array();

	if ( ! bp_is_active( 'activity' ) ) {
		$actions[] = 'bb_groups_subscribed_activity';
	}

	if ( ! bp_is_active( 'forums' ) ) {
		$actions[] = 'bb_groups_subscribed_discussion';
	}

	return apply_filters( 'bb_notification_excluded_component_actions', $actions );
}

/**
 * Function to add the notification for post new comment reply.
 *
 * @since BuddyBoss 2.3.50
 *
 * @param int   $comment_id   The comment ID.
 * @param int   $commenter_id Commenter ID.
 * @param array $commentdata  Comment data.
 */
function bb_post_new_comment_reply_add_notification( $comment_id, $commenter_id, $commentdata ) {

	// Specify the Notification type.
	$component_action = 'bb_posts_new_comment_reply';
	$parent_comment   = get_comment( $commentdata['comment_parent'] );

	add_filter( 'bp_notification_after_save', 'bb_notification_after_save_meta', 5, 1 );
	bp_notifications_add_notification(
		array(
			'user_id'           => (int) $parent_comment->user_id,
			'item_id'           => $comment_id,
			'secondary_item_id' => $commenter_id,
			'component_name'    => 'core',
			'component_action'  => $component_action,
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
		)
	);
	remove_filter( 'bp_notification_after_save', 'bb_notification_after_save_meta', 5, 1 );

}
add_action( 'bb_post_new_comment_reply_notification', 'bb_post_new_comment_reply_add_notification', 10, 3 );
