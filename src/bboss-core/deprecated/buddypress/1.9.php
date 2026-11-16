<?php
/**
 * BuddyPress Member Notifications
 *
 * Backwards compatibility functions and filters used for member notifications.
 * Use bp-notifications instead.
 *
 * @package BuddyBoss\Member Notifications
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add a notification for a specific user, from a specific component.
 *
 * @deprecated Deprecated since BuddyPress 1.9.0. Use
 *             bboss_notifications_add_notification() instead.
 *
 * @since BuddyPress 1.0.0
 * @param string       $item_id
 * @param int          $user_id
 * @param string       $component_name
 * @param string       $component_action
 * @param int          $secondary_item_id
 * @param false|string $date_notified
 * @param int          $is_new
 * @return int|boolean True on success, false on failure.
 */
function bboss_core_add_notification( $item_id, $user_id, $component_name, $component_action, $secondary_item_id = 0, $date_notified = false, $is_new = 1 ) {

	// Bail if notifications is not active
	if ( ! bboss_is_active( 'notifications' ) ) {
		return false;
	}

	// Trigger the deprecated function notice
	_deprecated_function( __FUNCTION__, '1.9', 'bp_notifications_add_notification()' );

	// Notifications must always have a time
	if ( false === $date_notified ) {
		$date_notified = bboss_core_current_time();
	}

	// Add the notification
	return bboss_notifications_add_notification(
		array(
			'item_id'           => $item_id,
			'user_id'           => $user_id,
			'component_name'    => $component_name,
			'component_action'  => $component_action,
			'secondary_item_id' => $secondary_item_id,
			'date_notified'     => $date_notified,
			'is_new'            => $is_new,
		)
	);
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_add_notification' ) && ! function_exists( 'bp_core_add_notification' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_add_notification()}.
	 *
	 * @deprecated Use bboss_core_add_notification() instead.
	 */
	function bp_core_add_notification( $item_id, $user_id, $component_name, $component_action, $secondary_item_id = 0, $date_notified = false, $is_new = 1 ) {
		return bboss_core_add_notification( $item_id, $user_id, $component_name, $component_action, $secondary_item_id, $date_notified, $is_new );
	}
}
// @bbwt-com-end compat-layer


/**
 * Delete a specific notification by its ID.
 *
 * @deprecated Deprecated since BuddyPress 1.9.0. Use
 *             bboss_notifications_delete_notification() instead.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $id ID of notification.
 * @return false|integer True on success, false on failure.
 */
function bboss_core_delete_notification( $id ) {

	// Bail if notifications is not active
	if ( ! bboss_is_active( 'notifications' ) ) {
		return false;
	}

	// Trigger the deprecated function notice
	_deprecated_function( __FUNCTION__, '1.9', 'bp_notifications_delete_notification()' );

	return BBoss_Notifications_Notification::delete_by_id( $id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_delete_notification' ) && ! function_exists( 'bp_core_delete_notification' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_delete_notification()}.
	 *
	 * @deprecated Use bboss_core_delete_notification() instead.
	 */
	function bp_core_delete_notification( $id ) {
		return bboss_core_delete_notification( $id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Get a specific notification by its ID.
 *
 * @deprecated Deprecated since BuddyPress 1.9.0. Use
 *             bboss_notifications_get_notification() instead.
 *
 * @since BuddyPress 1.0.0
 * @param int $id ID of notification.
 * @return false|BBoss_Core_Notification
 */
function bboss_core_get_notification( $id ) {

	// Bail if notifications is not active
	if ( ! bboss_is_active( 'notifications' ) ) {
		return false;
	}

	// Trigger the deprecated function notice
	_deprecated_function( __FUNCTION__, '1.9', 'bp_notifications_get_notification()' );

	return bboss_notifications_get_notification( $id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_get_notification' ) && ! function_exists( 'bp_core_get_notification' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_get_notification()}.
	 *
	 * @deprecated Use bboss_core_get_notification() instead.
	 */
	function bp_core_get_notification( $id ) {
		return bboss_core_get_notification( $id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Get notifications for a specific user.
 *
 * @deprecated Deprecated since BuddyPress 1.9.0. Use
 *             bboss_notifications_get_notifications_for_user() instead.
 *
 * @since BuddyPress 1.0.0
 * @param int    $user_id ID of user.
 * @param string $format
 * @return boolean Object or array on success, false on failure.
 */
function bboss_core_get_notifications_for_user( $user_id, $format = 'string' ) {

	// Bail if notifications is not active
	if ( ! bboss_is_active( 'notifications' ) ) {
		return false;
	}

	// Trigger the deprecated function notice
	_deprecated_function( __FUNCTION__, '1.9', 'bp_notifications_get_notifications_for_user()' );

	return bboss_notifications_get_notifications_for_user( $user_id, $format );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_get_notifications_for_user' ) && ! function_exists( 'bp_core_get_notifications_for_user' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_get_notifications_for_user()}.
	 *
	 * @deprecated Use bboss_core_get_notifications_for_user() instead.
	 */
	function bp_core_get_notifications_for_user( $user_id, $format = 'string' ) {
		return bboss_core_get_notifications_for_user( $user_id, $format );
	}
}
// @bbwt-com-end compat-layer


/** Delete ********************************************************************/

/**
 * Delete notifications for a user by type.
 *
 * Used when clearing out notifications for a specific component when the user
 * has visited that component.
 *
 * @deprecated Deprecated since BuddyPress 1.9.0. Use
 *             bboss_notifications_delete_notifications_by_type() instead.
 *
 * @since BuddyPress 1.0.0
 * @param int    $user_id
 * @param string $component_name
 * @param string $component_action
 * @return false|int True on success, false on failure.
 */
function bboss_core_delete_notifications_by_type( $user_id, $component_name, $component_action ) {

	// Bail if notifications is not active
	if ( ! bboss_is_active( 'notifications' ) ) {
		return false;
	}

	// Trigger the deprecated function notice
	_deprecated_function( __FUNCTION__, '1.9', 'bp_notifications_delete_notifications_by_type()' );

	return bboss_notifications_delete_notifications_by_type( $user_id, $component_name, $component_action );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_delete_notifications_by_type' ) && ! function_exists( 'bp_core_delete_notifications_by_type' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_delete_notifications_by_type()}.
	 *
	 * @deprecated Use bboss_core_delete_notifications_by_type() instead.
	 */
	function bp_core_delete_notifications_by_type( $user_id, $component_name, $component_action ) {
		return bboss_core_delete_notifications_by_type( $user_id, $component_name, $component_action );
	}
}
// @bbwt-com-end compat-layer


/**
 * Delete notifications for an item ID.
 *
 * Used when clearing out notifications for a specific component when the user
 * has visited that component.
 *
 * @deprecated Deprecated since BuddyPress 1.9.0. Use
 *             bboss_notifications_delete_notifications_by_item_id() instead.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int    $user_id
 * @param string $component_name
 * @param string $component_action
 * @return false|int True on success, false on failure.
 */
function bboss_core_delete_notifications_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id = false ) {

	// Bail if notifications is not active
	if ( ! bboss_is_active( 'notifications' ) ) {
		return false;
	}

	// Trigger the deprecated function notice
	_deprecated_function( __FUNCTION__, '1.9', 'bp_notifications_delete_notifications_by_item_id()' );

	return bboss_notifications_delete_notifications_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_delete_notifications_by_item_id' ) && ! function_exists( 'bp_core_delete_notifications_by_item_id' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_delete_notifications_by_item_id()}.
	 *
	 * @deprecated Use bboss_core_delete_notifications_by_item_id() instead.
	 */
	function bp_core_delete_notifications_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id = false ) {
		return bboss_core_delete_notifications_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Delete all notifications for by type.
 *
 * @deprecated Deprecated since BuddyPress 1.9.0. Use
 *             bboss_notifications_delete_all_notifications_by_type() instead.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int          $user_id
 * @param string       $component_name
 * @param false|string $component_action
 * @return boolean True on success, false on failure.
 */
function bboss_core_delete_all_notifications_by_type( $item_id, $component_name, $component_action = false, $secondary_item_id = false ) {

	// Bail if notifications is not active
	if ( ! bboss_is_active( 'notifications' ) ) {
		return false;
	}

	// Trigger the deprecated function notice
	_deprecated_function( __FUNCTION__, '1.9', 'bp_notifications_delete_all_notifications_by_type()' );

	bboss_notifications_delete_all_notifications_by_type( $item_id, $component_name, $component_action, $secondary_item_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_delete_all_notifications_by_type' ) && ! function_exists( 'bp_core_delete_all_notifications_by_type' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_delete_all_notifications_by_type()}.
	 *
	 * @deprecated Use bboss_core_delete_all_notifications_by_type() instead.
	 */
	function bp_core_delete_all_notifications_by_type( $item_id, $component_name, $component_action = false, $secondary_item_id = false ) {
		return bboss_core_delete_all_notifications_by_type( $item_id, $component_name, $component_action, $secondary_item_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Delete all notifications for a user.
 *
 * Used when clearing out all notifications for a user, when deleted or spammed
 *
 * @deprecated Deprecated since BuddyPress 1.9.0. Use
 *             bboss_notifications_delete_notifications_from_user() instead.
 *
 * @since BuddyPress 1.0.0
 * @param int    $user_id
 * @param string $component_name
 * @param string $component_action
 * @return false|int True on success, false on failure.
 */
function bboss_core_delete_notifications_from_user( $user_id, $component_name, $component_action ) {

	// Bail if notifications is not active
	if ( ! bboss_is_active( 'notifications' ) ) {
		return false;
	}

	// Trigger the deprecated function notice
	_deprecated_function( __FUNCTION__, '1.9', 'bp_notifications_delete_notifications_from_user()' );

	return bboss_notifications_delete_notifications_from_user( $user_id, $component_name, $component_action );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_delete_notifications_from_user' ) && ! function_exists( 'bp_core_delete_notifications_from_user' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_delete_notifications_from_user()}.
	 *
	 * @deprecated Use bboss_core_delete_notifications_from_user() instead.
	 */
	function bp_core_delete_notifications_from_user( $user_id, $component_name, $component_action ) {
		return bboss_core_delete_notifications_from_user( $user_id, $component_name, $component_action );
	}
}
// @bbwt-com-end compat-layer


/** Helpers *******************************************************************/

/**
 * Check if a user has access to a specific notification.
 *
 * Used before deleting a notification for a user.
 *
 * @deprecated Deprecated since BuddyPress 1.9.0. Use
 *             bboss_notifications_check_notification_access() instead.
 *
 * @since BuddyPress 1.0.0
 * @param int $user_id
 * @param int $notification_id
 * @return boolean True on success, false on failure.
 */
function bboss_core_check_notification_access( $user_id, $notification_id ) {

	// Bail if notifications is not active
	if ( ! bboss_is_active( 'notifications' ) ) {
		return false;
	}

	// Trigger the deprecated function notice
	_deprecated_function( __FUNCTION__, '1.9', 'bp_notifications_check_notification_access()' );

	return bboss_notifications_check_notification_access( $user_id, $notification_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_check_notification_access' ) && ! function_exists( 'bp_core_check_notification_access' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_check_notification_access()}.
	 *
	 * @deprecated Use bboss_core_check_notification_access() instead.
	 */
	function bp_core_check_notification_access( $user_id, $notification_id ) {
		return bboss_core_check_notification_access( $user_id, $notification_id );
	}
}
// @bbwt-com-end compat-layer

