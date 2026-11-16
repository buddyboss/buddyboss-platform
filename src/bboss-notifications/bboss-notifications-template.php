<?php
/**
 * BuddyPress Notifications Template Functions.
 *
 * @package BuddyBoss\Tonifications\Template
 * @since BuddyPress 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the notifications component slug.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_notifications_slug() {
	echo esc_attr( bboss_get_notifications_slug() );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_notifications_slug' ) && ! function_exists( 'bp_notifications_slug' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_notifications_slug()}.
	 *
	 * @deprecated Use bboss_notifications_slug() instead.
	 */
	function bp_notifications_slug() {
		return bboss_notifications_slug();
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the notifications component slug.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @return string Slug of the Notifications component.
	 */
function bboss_get_notifications_slug() {

	/**
	 * Filters the notifications component slug.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param string $slug Notifications component slug.
	 */
	return apply_filters( 'bp_get_notifications_slug', bboss()->notifications->slug );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_notifications_slug' ) && ! function_exists( 'bp_get_notifications_slug' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_notifications_slug()}.
	 *
	 * @deprecated Use bboss_get_notifications_slug() instead.
	 */
	function bp_get_notifications_slug() {
		return bboss_get_notifications_slug();
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the notifications permalink for a user.
 *
 * @since BuddyPress 1.9.0
 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bboss_notifications_permalink( $user_id = 0 ) {
	echo esc_url( bboss_get_notifications_permalink( $user_id ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_notifications_permalink' ) && ! function_exists( 'bp_notifications_permalink' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_notifications_permalink()}.
	 *
	 * @deprecated Use bboss_notifications_permalink() instead.
	 */
	function bp_notifications_permalink( $user_id = 0 ) {
		return bboss_notifications_permalink( $user_id );
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the notifications permalink.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param int $user_id The user ID.
	 * @return string Notifications permalink.
	 */
function bboss_get_notifications_permalink( $user_id = 0 ) {
	if ( 0 === $user_id ) {
		$user_id = bboss_loggedin_user_id();
		$domain  = bboss_loggedin_user_domain();
	} else {
		$domain = bboss_core_get_user_domain( (int) $user_id );
	}

	$retval = trailingslashit( $domain . bboss_get_notifications_slug() );

	/**
	 * Filters the notifications permalink.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param string $retval  Permalink for the notifications.
	 * @param int    $user_id The user ID.
	 */
	return apply_filters( 'bp_get_notifications_permalink', $retval, $user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_notifications_permalink' ) && ! function_exists( 'bp_get_notifications_permalink' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_notifications_permalink()}.
	 *
	 * @deprecated Use bboss_get_notifications_permalink() instead.
	 */
	function bp_get_notifications_permalink( $user_id = 0 ) {
		return bboss_get_notifications_permalink( $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the unread notifications permalink for a user.
 *
 * @since BuddyPress 1.9.0
 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bboss_notifications_unread_permalink( $user_id = 0 ) {
	echo esc_url( bboss_get_notifications_unread_permalink( $user_id ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_notifications_unread_permalink' ) && ! function_exists( 'bp_notifications_unread_permalink' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_notifications_unread_permalink()}.
	 *
	 * @deprecated Use bboss_notifications_unread_permalink() instead.
	 */
	function bp_notifications_unread_permalink( $user_id = 0 ) {
		return bboss_notifications_unread_permalink( $user_id );
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the unread notifications permalink.
	 *
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param int $user_id The user ID.
	 * @return string Unread notifications permalink.
	 */
function bboss_get_notifications_unread_permalink( $user_id = 0 ) {
	if ( 0 === $user_id ) {
		$user_id = bboss_loggedin_user_id();
		$domain  = bboss_loggedin_user_domain();
	} else {
		$domain = bboss_core_get_user_domain( (int) $user_id );
	}

	$retval = trailingslashit( $domain . bboss_get_notifications_slug() . '/unread' );

	/**
	 * Filters the unread notifications permalink.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param string $retval  Permalink for the unread notifications.
	 * @param int    $user_id The user ID.
	 */
	return apply_filters( 'bp_get_notifications_unread_permalink', $retval, $user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_notifications_unread_permalink' ) && ! function_exists( 'bp_get_notifications_unread_permalink' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_notifications_unread_permalink()}.
	 *
	 * @deprecated Use bboss_get_notifications_unread_permalink() instead.
	 */
	function bp_get_notifications_unread_permalink( $user_id = 0 ) {
		return bboss_get_notifications_unread_permalink( $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the read notifications permalink for a user.
 *
 * @since BuddyPress 1.9.0
 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bboss_notifications_read_permalink( $user_id = 0 ) {
	echo esc_url( bboss_get_notifications_read_permalink( $user_id ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_notifications_read_permalink' ) && ! function_exists( 'bp_notifications_read_permalink' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_notifications_read_permalink()}.
	 *
	 * @deprecated Use bboss_notifications_read_permalink() instead.
	 */
	function bp_notifications_read_permalink( $user_id = 0 ) {
		return bboss_notifications_read_permalink( $user_id );
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the read notifications permalink.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @return string Read notifications permalink.
	 */
function bboss_get_notifications_read_permalink( $user_id = 0 ) {
	if ( 0 === $user_id ) {
		$user_id = bboss_loggedin_user_id();
		$domain  = bboss_loggedin_user_domain();
	} else {
		$domain = bboss_core_get_user_domain( (int) $user_id );
	}

	$retval = trailingslashit( $domain . bboss_get_notifications_slug() . '/read' );

	/**
	 * Filters the read notifications permalink.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param string $retval  Permalink for the read notifications.
	 * @param int    $user_id The user ID.
	 */
	return apply_filters( 'bp_get_notifications_unread_permalink', $retval, $user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_notifications_read_permalink' ) && ! function_exists( 'bp_get_notifications_read_permalink' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_notifications_read_permalink()}.
	 *
	 * @deprecated Use bboss_get_notifications_read_permalink() instead.
	 */
	function bp_get_notifications_read_permalink( $user_id = 0 ) {
		return bboss_get_notifications_read_permalink( $user_id );
	}
}
// @bbwt-com-end compat-layer


/** The Loop ******************************************************************/

/**
 * Initialize the notifications loop.
 *
 * Based on the $args passed, bboss_has_notifications() populates
 * bboss()->notifications->query_loop global, enabling the use of BP
 * templates and template functions to display a list of notifications.
 *
 * @since BuddyPress 1.9.0
 *
 * @param array|string $args {
 *     Arguments for limiting the contents of the notifications loop. Can be
 *     passed as an associative array, or as a URL query string.
 *
 *     See {@link BBoss_Notifications_Notification::get()} for detailed
 *     information on the arguments.  In addition, also supports:
 *
 *     @type int    $max      Optional. Max items to display. Default: false.
 *     @type string $page_arg URL argument to use for pagination.
 *                            Default: 'npage'.
 * }
 * @return bool
 */
function bboss_has_notifications( $args = '' ) {

	// Get the default is_new argument.
	if ( bboss_is_current_action( 'unread' ) ) {
		$is_new = 1;
	} elseif ( bboss_is_current_action( 'read' ) ) {
		$is_new = 0;

		// Not on a notifications page? default to fetch new notifications.
	} else {
		$is_new = 1;
	}

	// Get the user ID.
	if ( bboss_displayed_user_id() ) {
		$user_id = bboss_displayed_user_id();
	} else {
		$user_id = bboss_loggedin_user_id();
	}

	// Set the component action (by default false to get all actions)
	$component_action = false;

	if ( isset( $_REQUEST['type'] ) ) {
		$component_action = sanitize_key( $_REQUEST['type'] );
	}

	// Set the search terms (by default an empty string to get all notifications)
	$search_terms = '';

	if ( isset( $_REQUEST['s'] ) ) {
		$search_terms = stripslashes( $_REQUEST['s'] );
	}

	// Parse the args.
	$r = bboss_parse_args(
		$args,
		array(
			'id'                => false,
			'user_id'           => $user_id,
			'secondary_item_id' => false,
			'component_name'    => bboss_notifications_get_registered_components(),
			'component_action'  => $component_action,
			'is_new'            => $is_new,
			'search_terms'      => $search_terms,
			'order_by'          => 'date_notified',
			'sort_order'        => 'DESC',
			'meta_query'        => false,
			'date_query'        => false,
			'page'              => 1,
			'per_page'          => 25,

			// These are additional arguments that are not available in
			// BP_Notifications_Notification::get().
			'max'               => false,
			'page_arg'          => 'npage',
		),
		'has_notifications'
	);

	// Get the notifications.
	$query_loop = new BBoss_Notifications_Template( $r );

	// Setup the global query loop.
	bboss()->notifications->query_loop = $query_loop;

	/**
	 * Filters whether or not the user has notifications to display.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 2.6.0 Added the `$r` parameter.
	 *
	 * @param bool                      $value      Whether or not there are notifications to display.
	 * @param BBoss_Notifications_Template $query_loop BBoss_Notifications_Template object instance.
	 * @param array                     $r          Array of arguments passed into the BBoss_Notifications_Template class.
	 */
	return apply_filters( 'bp_has_notifications', $query_loop->has_notifications(), $query_loop, $r );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_has_notifications' ) && ! function_exists( 'bp_has_notifications' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_has_notifications()}.
	 *
	 * @deprecated Use bboss_has_notifications() instead.
	 */
	function bp_has_notifications( $args = '' ) {
		return bboss_has_notifications( $args );
	}
}
// @bbwt-com-end compat-layer


/**
 * Get the notifications returned by the template loop.
 *
 * @since BuddyPress 1.9.0
 *
 * @return array List of notifications.
 */
function bboss_the_notifications() {
	return bboss()->notifications->query_loop->notifications();
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_the_notifications' ) && ! function_exists( 'bp_the_notifications' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_the_notifications()}.
	 *
	 * @deprecated Use bboss_the_notifications() instead.
	 */
	function bp_the_notifications() {
		return bboss_the_notifications();
	}
}
// @bbwt-com-end compat-layer


/**
 * Get the current notification object in the loop.
 *
 * @since BuddyPress 1.9.0
 *
 * @return object The current notification within the loop.
 */
function bboss_the_notification() {
	return bboss()->notifications->query_loop->the_notification();
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_the_notification' ) && ! function_exists( 'bp_the_notification' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_the_notification()}.
	 *
	 * @deprecated Use bboss_the_notification() instead.
	 */
	function bp_the_notification() {
		return bboss_the_notification();
	}
}
// @bbwt-com-end compat-layer


/** Loop Output ***************************************************************/

/**
 * Output the ID of the notification currently being iterated on.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_the_notification_id() {
	echo esc_attr( bboss_get_the_notification_id() );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_the_notification_id' ) && ! function_exists( 'bp_the_notification_id' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_the_notification_id()}.
	 *
	 * @deprecated Use bboss_the_notification_id() instead.
	 */
	function bp_the_notification_id() {
		return bboss_the_notification_id();
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the ID of the notification currently being iterated on.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @return int ID of the current notification.
	 */
function bboss_get_the_notification_id() {

	/**
	 * Filters the ID of the notification currently being iterated on.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param int $id ID of the notification being iterated on.
	 */
	return apply_filters( 'bp_get_the_notification_id', bboss()->notifications->query_loop->notification->id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_the_notification_id' ) && ! function_exists( 'bp_get_the_notification_id' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_the_notification_id()}.
	 *
	 * @deprecated Use bboss_get_the_notification_id() instead.
	 */
	function bp_get_the_notification_id() {
		return bboss_get_the_notification_id();
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the associated item ID of the notification currently being iterated on.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_the_notification_item_id() {
	echo esc_attr( bboss_get_the_notification_item_id() );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_the_notification_item_id' ) && ! function_exists( 'bp_the_notification_item_id' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_the_notification_item_id()}.
	 *
	 * @deprecated Use bboss_the_notification_item_id() instead.
	 */
	function bp_the_notification_item_id() {
		return bboss_the_notification_item_id();
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the associated item ID of the notification currently being iterated on.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @return int ID of the item associated with the current notification.
	 */
function bboss_get_the_notification_item_id() {

	/**
	 * Filters the associated item ID of the notification currently being iterated on.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param int $item_id ID of the associated item.
	 */
	return apply_filters( 'bp_get_the_notification_item_id', bboss()->notifications->query_loop->notification->item_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_the_notification_item_id' ) && ! function_exists( 'bp_get_the_notification_item_id' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_the_notification_item_id()}.
	 *
	 * @deprecated Use bboss_get_the_notification_item_id() instead.
	 */
	function bp_get_the_notification_item_id() {
		return bboss_get_the_notification_item_id();
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the secondary associated item ID of the notification currently being iterated on.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_the_notification_secondary_item_id() {
	echo esc_attr( bboss_get_the_notification_secondary_item_id() );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_the_notification_secondary_item_id' ) && ! function_exists( 'bp_the_notification_secondary_item_id' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_the_notification_secondary_item_id()}.
	 *
	 * @deprecated Use bboss_the_notification_secondary_item_id() instead.
	 */
	function bp_the_notification_secondary_item_id() {
		return bboss_the_notification_secondary_item_id();
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the secondary associated item ID of the notification currently being iterated on.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @return int ID of the secondary item associated with the current notification.
	 */
function bboss_get_the_notification_secondary_item_id() {

	/**
	 * Filters the secondary associated item ID of the notification currently being iterated on.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param int $secondary_item_id ID of the secondary associated item.
	 */
	return apply_filters( 'bp_get_the_notification_secondary_item_id', bboss()->notifications->query_loop->notification->secondary_item_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_the_notification_secondary_item_id' ) && ! function_exists( 'bp_get_the_notification_secondary_item_id' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_the_notification_secondary_item_id()}.
	 *
	 * @deprecated Use bboss_get_the_notification_secondary_item_id() instead.
	 */
	function bp_get_the_notification_secondary_item_id() {
		return bboss_get_the_notification_secondary_item_id();
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the name of the component associated with the notification currently being iterated on.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_the_notification_component_name() {
	echo esc_html( bboss_get_the_notification_component_name() );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_the_notification_component_name' ) && ! function_exists( 'bp_the_notification_component_name' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_the_notification_component_name()}.
	 *
	 * @deprecated Use bboss_the_notification_component_name() instead.
	 */
	function bp_the_notification_component_name() {
		return bboss_the_notification_component_name();
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the name of the component associated with the notification currently being iterated on.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @return int Name of the component associated with the current notification.
	 */
function bboss_get_the_notification_component_name() {

	/**
	 * Filters the name of the component associated with the notification currently being iterated on.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param int $component_name Name of the component associated with the current notification.
	 */
	return apply_filters( 'bp_get_the_notification_component_name', bboss()->notifications->query_loop->notification->component_name );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_the_notification_component_name' ) && ! function_exists( 'bp_get_the_notification_component_name' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_the_notification_component_name()}.
	 *
	 * @deprecated Use bboss_get_the_notification_component_name() instead.
	 */
	function bp_get_the_notification_component_name() {
		return bboss_get_the_notification_component_name();
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the name of the action associated with the notification currently being iterated on.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_the_notification_component_action() {
	echo esc_attr( bboss_get_the_notification_component_action() );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_the_notification_component_action' ) && ! function_exists( 'bp_the_notification_component_action' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_the_notification_component_action()}.
	 *
	 * @deprecated Use bboss_the_notification_component_action() instead.
	 */
	function bp_the_notification_component_action() {
		return bboss_the_notification_component_action();
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the name of the action associated with the notification currently being iterated on.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @return int Name of the action associated with the current notification.
	 */
function bboss_get_the_notification_component_action() {

	/**
	 * Filters the name of the action associated with the notification currently being iterated on.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param int $component_action Name of the action associated with the current notification.
	 */
	return apply_filters( 'bp_get_the_notification_component_action', bboss()->notifications->query_loop->notification->component_action );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_the_notification_component_action' ) && ! function_exists( 'bp_get_the_notification_component_action' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_the_notification_component_action()}.
	 *
	 * @deprecated Use bboss_get_the_notification_component_action() instead.
	 */
	function bp_get_the_notification_component_action() {
		return bboss_get_the_notification_component_action();
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the timestamp of the current notification.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_the_notification_date_notified() {
	echo esc_html( bboss_get_the_notification_date_notified() );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_the_notification_date_notified' ) && ! function_exists( 'bp_the_notification_date_notified' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_the_notification_date_notified()}.
	 *
	 * @deprecated Use bboss_the_notification_date_notified() instead.
	 */
	function bp_the_notification_date_notified() {
		return bboss_the_notification_date_notified();
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the timestamp of the current notification.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @return string Timestamp of the current notification.
	 */
function bboss_get_the_notification_date_notified() {

	/**
	 * Filters the timestamp of the current notification.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param string $date_notified Timestamp of the current notification.
	 */
	return apply_filters( 'bp_get_the_notification_date_notified', bboss()->notifications->query_loop->notification->date_notified );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_the_notification_date_notified' ) && ! function_exists( 'bp_get_the_notification_date_notified' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_the_notification_date_notified()}.
	 *
	 * @deprecated Use bboss_get_the_notification_date_notified() instead.
	 */
	function bp_get_the_notification_date_notified() {
		return bboss_get_the_notification_date_notified();
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the timestamp of the current notification.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_the_notification_time_since() {
	echo esc_html( bboss_get_the_notification_time_since() );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_the_notification_time_since' ) && ! function_exists( 'bp_the_notification_time_since' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_the_notification_time_since()}.
	 *
	 * @deprecated Use bboss_the_notification_time_since() instead.
	 */
	function bp_the_notification_time_since() {
		return bboss_the_notification_time_since();
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the timestamp of the current notification.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @return string Timestamp of the current notification.
	 */
function bboss_get_the_notification_time_since() {

	// Get the notified date.
	$date_notified = bboss_get_the_notification_date_notified();

	// Notified date has legitimate data.
	if ( '0000-00-00 00:00:00' !== $date_notified ) {
		$retval = bboss_core_time_since( $date_notified );

		// Notified date is empty, so return a fun string.
	} else {
		$retval = __( 'Date not found', 'buddyboss' );
	}

	/**
	 * Filters the time since value of the current notification.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param string $retval Time since value for current notification.
	 */
	return apply_filters( 'bp_get_the_notification_time_since', $retval );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_the_notification_time_since' ) && ! function_exists( 'bp_get_the_notification_time_since' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_the_notification_time_since()}.
	 *
	 * @deprecated Use bboss_get_the_notification_time_since() instead.
	 */
	function bp_get_the_notification_time_since() {
		return bboss_get_the_notification_time_since();
	}
}
// @bbwt-com-end compat-layer


/**
 * Output full-text description for a specific notification.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_the_notification_description() {
	echo wp_kses_post( bboss_get_the_notification_description() );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_the_notification_description' ) && ! function_exists( 'bp_the_notification_description' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_the_notification_description()}.
	 *
	 * @deprecated Use bboss_the_notification_description() instead.
	 */
	function bp_the_notification_description() {
		return bboss_the_notification_description();
	}
}
// @bbwt-com-end compat-layer


/**
 * Get full-text description for a specific notification.
 *
 * @param object $notification Notification object.
 *
 * @since BuddyPress 1.9.0
 *
 * @return string
 */
function bboss_get_the_notification_description( $notification = '' ) {
	$bp = bboss();

	if ( empty( $notification ) ) {
		$notification = $bp->notifications->query_loop->notification;
	}

	// Callback function exists.
	if ( isset( $bp->{ $notification->component_name }->notification_callback ) && is_callable( $bp->{ $notification->component_name }->notification_callback ) ) {
		$description = call_user_func( $bp->{ $notification->component_name }->notification_callback, $notification->component_action, $notification->item_id, $notification->secondary_item_id, 1, 'string', $notification->id, 'web' );

		$description = apply_filters(
			'bb_notifications_get_component_notification',
			$description,
			$notification->item_id,
			$notification->secondary_item_id,
			1,
			'string',
			$notification->component_action,
			$notification->component_name,
			$notification->id,
			'web'
		);

		// @deprecated format_notification_function - 1.5
	} elseif ( isset( $bp->{ $notification->component_name }->format_notification_function ) && function_exists( $bp->{ $notification->component_name }->format_notification_function ) ) {
		$description = call_user_func( $bp->{ $notification->component_name }->format_notification_function, $notification->component_action, $notification->item_id, $notification->secondary_item_id, 1 );

		// Allow non BuddyPress components to hook in.
	} else {

		/** This filter is documented in bboss-notifications/bboss-notifications-functions.php */
		$description = apply_filters_ref_array( 'bp_notifications_get_notifications_for_user', array( $notification->component_action, $notification->item_id, $notification->secondary_item_id, 1, 'string', $notification->component_action, $notification->component_name, $notification->id, 'web' ) );
	}

	/**
	 * Filters the full-text description for a specific notification.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 2.3.0 Added the `$notification` parameter.
	 *
	 * @param string $description  Full-text description for a specific notification.
	 * @param object $notification Notification object.
	 */
	return apply_filters( 'bp_get_the_notification_description', stripcslashes( $description ), $notification );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_the_notification_description' ) && ! function_exists( 'bp_get_the_notification_description' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_the_notification_description()}.
	 *
	 * @deprecated Use bboss_get_the_notification_description() instead.
	 */
	function bp_get_the_notification_description( $notification = '' ) {
		return bboss_get_the_notification_description( $notification );
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the mark read link for the current notification.
 *
 * @since BuddyPress 1.9.0
 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bboss_the_notification_mark_read_link( $user_id = 0 ) {
	echo wp_kses_post( bboss_get_the_notification_mark_read_link( $user_id ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_the_notification_mark_read_link' ) && ! function_exists( 'bp_the_notification_mark_read_link' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_the_notification_mark_read_link()}.
	 *
	 * @deprecated Use bboss_the_notification_mark_read_link() instead.
	 */
	function bp_the_notification_mark_read_link( $user_id = 0 ) {
		return bboss_the_notification_mark_read_link( $user_id );
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the mark read link for the current notification.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param int $user_id The user ID.
	 * @return string
	 */
function bboss_get_the_notification_mark_read_link( $user_id = 0 ) {
	// Set default user ID to use.
	$user_id = 0 === $user_id ? bboss_displayed_user_id() : $user_id;

	$retval = sprintf( '<a href="%1$s" class="mark-read primary">%2$s</a>', esc_url( bboss_get_the_notification_mark_read_url( $user_id ) ), __( 'Read', 'buddyboss' ) );

	/**
	 * Filters the mark read link for the current notification.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param string $retval  HTML for the mark read link for the current notification.
	 * @param int    $user_id The user ID.
	 */
	return apply_filters( 'bp_get_the_notification_mark_read_link', $retval, $user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_the_notification_mark_read_link' ) && ! function_exists( 'bp_get_the_notification_mark_read_link' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_the_notification_mark_read_link()}.
	 *
	 * @deprecated Use bboss_get_the_notification_mark_read_link() instead.
	 */
	function bp_get_the_notification_mark_read_link( $user_id = 0 ) {
		return bboss_get_the_notification_mark_read_link( $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the URL used for marking a single notification as read.
 *
 * Since this function directly outputs a URL, it is escaped.
 *
 * @since BuddyPress 2.1.0
 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bboss_the_notification_mark_read_url( $user_id = 0 ) {
	echo esc_url( bboss_get_the_notification_mark_read_url( $user_id ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_the_notification_mark_read_url' ) && ! function_exists( 'bp_the_notification_mark_read_url' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_the_notification_mark_read_url()}.
	 *
	 * @deprecated Use bboss_the_notification_mark_read_url() instead.
	 */
	function bp_the_notification_mark_read_url( $user_id = 0 ) {
		return bboss_the_notification_mark_read_url( $user_id );
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the URL used for marking a single notification as read.
	 *
	 * @since BuddyPress 2.1.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param int $user_id The user ID.
	 * @return string
	 */
function bboss_get_the_notification_mark_read_url( $user_id = 0 ) {

	// Get the notification ID.
	$id = bboss_get_the_notification_id();

	// Get the args to add to the URL.
	$args = array(
		'action'          => 'read',
		'notification_id' => $id,
	);

	// Set default user ID to use.
	$user_id = 0 === $user_id ? bboss_displayed_user_id() : $user_id;

	// Add the args to the URL.
	$url = add_query_arg( $args, bboss_get_notifications_unread_permalink( $user_id ) );

	// Add the nonce.
	$url = wp_nonce_url( $url, 'bp_notification_mark_read_' . $id );

	/**
	 * Filters the URL used for marking a single notification as read.
	 *
	 * @since BuddyPress 2.1.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param string $url     URL to use for marking the single notification as read.
	 * @param int    $user_id The user ID.
	 */
	return apply_filters( 'bp_get_the_notification_mark_read_url', $url, $user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_the_notification_mark_read_url' ) && ! function_exists( 'bp_get_the_notification_mark_read_url' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_the_notification_mark_read_url()}.
	 *
	 * @deprecated Use bboss_get_the_notification_mark_read_url() instead.
	 */
	function bp_get_the_notification_mark_read_url( $user_id = 0 ) {
		return bboss_get_the_notification_mark_read_url( $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the mark unread link for the current notification.
 *
 * @since BuddyPress 1.9.0
 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bboss_the_notification_mark_unread_link( $user_id = 0 ) {
	echo wp_kses_post( bboss_get_the_notification_mark_unread_link( $user_id ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_the_notification_mark_unread_link' ) && ! function_exists( 'bp_the_notification_mark_unread_link' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_the_notification_mark_unread_link()}.
	 *
	 * @deprecated Use bboss_the_notification_mark_unread_link() instead.
	 */
	function bp_the_notification_mark_unread_link( $user_id = 0 ) {
		return bboss_the_notification_mark_unread_link( $user_id );
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the mark unread link for the current notification.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param int $user_id The user ID.
	 * @return string
	 */
function bboss_get_the_notification_mark_unread_link( $user_id = 0 ) {
	// Set default user ID to use.
	$user_id = 0 === $user_id ? bboss_displayed_user_id() : $user_id;

	$retval = sprintf( '<a href="%1$s" class="mark-unread primary">%2$s</a>', esc_url( bboss_get_the_notification_mark_unread_url( $user_id ) ), __( 'Unread', 'buddyboss' ) );

	/**
	 * Filters the link used for marking a single notification as unread.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param string $retval  HTML for the mark unread link for the current notification.
	 * @param int    $user_id The user ID.
	 */
	return apply_filters( 'bp_get_the_notification_mark_unread_link', $retval, $user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_the_notification_mark_unread_link' ) && ! function_exists( 'bp_get_the_notification_mark_unread_link' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_the_notification_mark_unread_link()}.
	 *
	 * @deprecated Use bboss_get_the_notification_mark_unread_link() instead.
	 */
	function bp_get_the_notification_mark_unread_link( $user_id = 0 ) {
		return bboss_get_the_notification_mark_unread_link( $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the URL used for marking a single notification as unread.
 *
 * Since this function directly outputs a URL, it is escaped.
 *
 * @since BuddyPress 2.1.0
 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bboss_the_notification_mark_unread_url( $user_id = 0 ) {
	echo esc_url( bboss_get_the_notification_mark_unread_url( $user_id ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_the_notification_mark_unread_url' ) && ! function_exists( 'bp_the_notification_mark_unread_url' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_the_notification_mark_unread_url()}.
	 *
	 * @deprecated Use bboss_the_notification_mark_unread_url() instead.
	 */
	function bp_the_notification_mark_unread_url( $user_id = 0 ) {
		return bboss_the_notification_mark_unread_url( $user_id );
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the URL used for marking a single notification as unread.
	 *
	 * @since BuddyPress 2.1.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param int $user_id The user ID.
	 * @return string
	 */
function bboss_get_the_notification_mark_unread_url( $user_id = 0 ) {

	// Get the notification ID.
	$id = bboss_get_the_notification_id();

	// Get the args to add to the URL.
	$args = array(
		'action'          => 'unread',
		'notification_id' => $id,
	);

	// Set default user ID to use.
	$user_id = 0 === $user_id ? bboss_displayed_user_id() : $user_id;

	// Add the args to the URL.
	$url = add_query_arg( $args, bboss_get_notifications_read_permalink( $user_id ) );

	// Add the nonce.
	$url = wp_nonce_url( $url, 'bp_notification_mark_unread_' . $id );

	/**
	 * Filters the URL used for marking a single notification as unread.
	 *
	 * @since BuddyPress 2.1.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param string $url     URL to use for marking the single notification as unread.
	 * @param int    $user_id The user ID.
	 */
	return apply_filters( 'bp_get_the_notification_mark_unread_url', $url, $user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_the_notification_mark_unread_url' ) && ! function_exists( 'bp_get_the_notification_mark_unread_url' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_the_notification_mark_unread_url()}.
	 *
	 * @deprecated Use bboss_get_the_notification_mark_unread_url() instead.
	 */
	function bp_get_the_notification_mark_unread_url( $user_id = 0 ) {
		return bboss_get_the_notification_mark_unread_url( $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the mark link for the current notification.
 *
 * @since BuddyPress 1.9.0
 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bboss_the_notification_mark_link( $user_id = 0 ) {
	echo wp_kses_post( bboss_get_the_notification_mark_link( $user_id ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_the_notification_mark_link' ) && ! function_exists( 'bp_the_notification_mark_link' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_the_notification_mark_link()}.
	 *
	 * @deprecated Use bboss_the_notification_mark_link() instead.
	 */
	function bp_the_notification_mark_link( $user_id = 0 ) {
		return bboss_the_notification_mark_link( $user_id );
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the mark link for the current notification.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param int $user_id The user ID.
	 * @return string
	 */
function bboss_get_the_notification_mark_link( $user_id = 0 ) {
	// Set default user ID to use.
	$user_id = 0 === $user_id ? bboss_displayed_user_id() : $user_id;

	if ( bboss_is_current_action( 'read' ) ) {
		$retval = bboss_get_the_notification_mark_unread_link( $user_id );
	} else {
		$retval = bboss_get_the_notification_mark_read_link( $user_id );
	}

	/**
	 * Filters the mark link for the current notification.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param string $retval  The mark link for the current notification.
	 * @param int    $user_id The user ID.
	 */
	return apply_filters( 'bp_get_the_notification_mark_link', $retval, $user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_the_notification_mark_link' ) && ! function_exists( 'bp_get_the_notification_mark_link' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_the_notification_mark_link()}.
	 *
	 * @deprecated Use bboss_get_the_notification_mark_link() instead.
	 */
	function bp_get_the_notification_mark_link( $user_id = 0 ) {
		return bboss_get_the_notification_mark_link( $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the delete link for the current notification.
 *
 * @since BuddyPress 1.9.0
 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bboss_the_notification_delete_link( $user_id = 0 ) {
	echo wp_kses_post( bboss_get_the_notification_delete_link( $user_id ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_the_notification_delete_link' ) && ! function_exists( 'bp_the_notification_delete_link' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_the_notification_delete_link()}.
	 *
	 * @deprecated Use bboss_the_notification_delete_link() instead.
	 */
	function bp_the_notification_delete_link( $user_id = 0 ) {
		return bboss_the_notification_delete_link( $user_id );
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the delete link for the current notification.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param int $user_id The user ID.
	 * @return string
	 */
function bboss_get_the_notification_delete_link( $user_id = 0 ) {
	// Set default user ID to use.
	$user_id = 0 === $user_id ? bboss_displayed_user_id() : $user_id;

	$retval = sprintf( '<a href="%1$s" class="delete secondary confirm">%2$s</a>', esc_url( bboss_get_the_notification_delete_url( $user_id ) ), __( 'Delete', 'buddyboss' ) );

	/**
	 * Filters the delete link for the current notification.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param string $retval  HTML for the delete link for the current notification.
	 * @param int    $user_id The user ID.
	 */
	return apply_filters( 'bp_get_the_notification_delete_link', $retval, $user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_the_notification_delete_link' ) && ! function_exists( 'bp_get_the_notification_delete_link' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_the_notification_delete_link()}.
	 *
	 * @deprecated Use bboss_get_the_notification_delete_link() instead.
	 */
	function bp_get_the_notification_delete_link( $user_id = 0 ) {
		return bboss_get_the_notification_delete_link( $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the URL used for deleting a single notification.
 *
 * Since this function directly outputs a URL, it is escaped.
 *
 * @since BuddyPress 2.1.0
 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bboss_the_notification_delete_url( $user_id = 0 ) {
	echo esc_url( bboss_get_the_notification_delete_url( $user_id ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_the_notification_delete_url' ) && ! function_exists( 'bp_the_notification_delete_url' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_the_notification_delete_url()}.
	 *
	 * @deprecated Use bboss_the_notification_delete_url() instead.
	 */
	function bp_the_notification_delete_url( $user_id = 0 ) {
		return bboss_the_notification_delete_url( $user_id );
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the URL used for deleting a single notification.
	 *
	 * @since BuddyPress 2.1.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param int $user_id The user ID.
	 * @return string
	 */
function bboss_get_the_notification_delete_url( $user_id = 0 ) {
	// Set default user ID to use.
	$user_id = 0 === $user_id ? bboss_displayed_user_id() : $user_id;

	// URL to add nonce to.
	if ( bboss_is_current_action( 'unread' ) ) {
		$link = bboss_get_notifications_unread_permalink( $user_id );
	} elseif ( bboss_is_current_action( 'read' ) ) {
		$link = bboss_get_notifications_read_permalink( $user_id );
	}

	// Get the ID.
	$id = bboss_get_the_notification_id();

	// Get the args to add to the URL.
	$args = array(
		'action'          => 'delete',
		'notification_id' => $id,
	);

	// Add the args.
	$url = add_query_arg( $args, $link );

	// Add the nonce.
	$url = wp_nonce_url( $url, 'bp_notification_delete_' . $id );

	/**
	 * Filters the URL used for deleting a single notification.
	 *
	 * @since BuddyPress 2.1.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter.
	 *
	 * @param string $url     URL used for deleting a single notification.
	 * @param int    $user_id The user ID.
	 */
	return apply_filters( 'bp_get_the_notification_delete_url', $url, $user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_the_notification_delete_url' ) && ! function_exists( 'bp_get_the_notification_delete_url' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_the_notification_delete_url()}.
	 *
	 * @deprecated Use bboss_get_the_notification_delete_url() instead.
	 */
	function bp_get_the_notification_delete_url( $user_id = 0 ) {
		return bboss_get_the_notification_delete_url( $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the action links for the current notification.
 *
 * @since BuddyPress 1.9.0
 * @since BuddyPress 2.6.0 Added $user_id as a parameter to $args.
 *
 * @param array|string $args Array of arguments.
 */
function bboss_the_notification_action_links( $args = '' ) {
	echo wp_kses_post( bboss_get_the_notification_action_links( $args ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_the_notification_action_links' ) && ! function_exists( 'bp_the_notification_action_links' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_the_notification_action_links()}.
	 *
	 * @deprecated Use bboss_the_notification_action_links() instead.
	 */
	function bp_the_notification_action_links( $args = '' ) {
		return bboss_the_notification_action_links( $args );
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the action links for the current notification.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 2.6.0 Added $user_id as a parameter to $args.
	 *
	 * @param array|string $args {
	 *     @type string $before  HTML before the links.
	 *     @type string $after   HTML after the links.
	 *     @type string $sep     HTML between the links.
	 *     @type array  $links   Array of links to implode by 'sep'.
	 *     @type int    $user_id User ID to fetch action links for. Defaults to displayed user ID.
	 * }
	 * @return string HTML links for actions to take on single notifications.
	 */
function bboss_get_the_notification_action_links( $args = '' ) {
	// Set default user ID to use.
	$user_id = isset( $args['user_id'] ) ? $args['user_id'] : bboss_displayed_user_id();

	// Parse.
	$r = bboss_parse_args(
		$args,
		array(
			'before' => '',
			'after'  => '',
			'sep'    => ' | ',
			'links'  => array(
				bboss_get_the_notification_mark_link( $user_id ),
				bboss_get_the_notification_delete_link( $user_id ),
			),
		)
	);

	// Build the links.
	$retval = $r['before'] . implode( $r['sep'], $r['links'] ) . $r['after'];

	/**
	 * Filters the action links for the current notification.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 2.6.0 Added the `$r` parameter.
	 *
	 * @param string $retval HTML links for actions to take on single notifications.
	 * @param array  $r      Array of parsed arguments.
	 */
	return apply_filters( 'bp_get_the_notification_action_links', $retval, $r );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_the_notification_action_links' ) && ! function_exists( 'bp_get_the_notification_action_links' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_the_notification_action_links()}.
	 *
	 * @deprecated Use bboss_get_the_notification_action_links() instead.
	 */
	function bp_get_the_notification_action_links( $args = '' ) {
		return bboss_get_the_notification_action_links( $args );
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the pagination count for the current notification loop.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_notifications_pagination_count() {
	echo esc_html( bboss_get_notifications_pagination_count() );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_notifications_pagination_count' ) && ! function_exists( 'bp_notifications_pagination_count' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_notifications_pagination_count()}.
	 *
	 * @deprecated Use bboss_notifications_pagination_count() instead.
	 */
	function bp_notifications_pagination_count() {
		return bboss_notifications_pagination_count();
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the pagination count for the current notification loop.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @return string HTML for the pagination count.
	 */
function bboss_get_notifications_pagination_count() {
	$query_loop = bboss()->notifications->query_loop;
	$start_num  = intval( ( $query_loop->pag_page - 1 ) * $query_loop->pag_num ) + 1;
	$from_num   = bboss_core_number_format( $start_num );
	$to_num     = bboss_core_number_format( ( $start_num + ( $query_loop->pag_num - 1 ) > $query_loop->total_notification_count ) ? $query_loop->total_notification_count : $start_num + ( $query_loop->pag_num - 1 ) );
	$total      = bboss_core_number_format( $query_loop->total_notification_count );

	/* translators: 1: number, 2: number, 3: number. */
	$pag = sprintf( _n( 'Viewing 1 notification', 'Viewing %1$s - %2$s of %3$s notifications', $query_loop->total_notification_count, 'buddyboss' ), $from_num, $to_num, $total ); // phpcs:ignore WordPress.WP.I18n.MissingSingularPlaceholder -- Singular keeps its literal form to preserve existing translations (msgid unchanged); upstream BuddyPress pagination string.

	/**
	 * Filters the pagination count for the current notification loop.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param string $pag HTML for the pagination count.
	 */
	return apply_filters( 'bp_notifications_pagination_count', $pag );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_notifications_pagination_count' ) && ! function_exists( 'bp_get_notifications_pagination_count' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_notifications_pagination_count()}.
	 *
	 * @deprecated Use bboss_get_notifications_pagination_count() instead.
	 */
	function bp_get_notifications_pagination_count() {
		return bboss_get_notifications_pagination_count();
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the pagination links for the current notification loop.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_notifications_pagination_links() {
	echo wp_kses_post( bboss_get_notifications_pagination_links() );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_notifications_pagination_links' ) && ! function_exists( 'bp_notifications_pagination_links' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_notifications_pagination_links()}.
	 *
	 * @deprecated Use bboss_notifications_pagination_links() instead.
	 */
	function bp_notifications_pagination_links() {
		return bboss_notifications_pagination_links();
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the pagination links for the current notification loop.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @return string HTML for the pagination links.
	 */
function bboss_get_notifications_pagination_links() {

	/**
	 * Filters the pagination links for the current notification loop.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param string $pag_links HTML for the pagination links.
	 */
	return apply_filters( 'bp_get_notifications_pagination_links', bboss()->notifications->query_loop->pag_links );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_notifications_pagination_links' ) && ! function_exists( 'bp_get_notifications_pagination_links' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_notifications_pagination_links()}.
	 *
	 * @deprecated Use bboss_get_notifications_pagination_links() instead.
	 */
	function bp_get_notifications_pagination_links() {
		return bboss_get_notifications_pagination_links();
	}
}
// @bbwt-com-end compat-layer


/** Form Helpers **************************************************************/

/**
 * Output the form for changing the sort order of notifications.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_notifications_sort_order_form() {

	// Setup local variables.
	$orders   = array( 'DESC', 'ASC' );
	$selected = 'DESC';

	// Check for a custom sort_order.
	if ( ! empty( $_REQUEST['sort_order'] ) ) {
		if ( in_array( $_REQUEST['sort_order'], $orders ) ) {
			$selected = $_REQUEST['sort_order'];
		}
	} ?>

	<form action="" method="get" id="notifications-sort-order">
		<label for="notifications-sort-order-list"><?php esc_html_e( 'Order By:', 'buddyboss' ); ?></label>

		<select id="notifications-sort-order-list" name="sort_order" onchange="this.form.submit();">
			<option value="DESC" <?php selected( $selected, 'DESC' ); ?>><?php esc_html_e( 'Newest First', 'buddyboss' ); ?></option>
			<option value="ASC"  <?php selected( $selected, 'ASC' ); ?>><?php esc_html_e( 'Oldest First', 'buddyboss' ); ?></option>
		</select>

		<noscript>
			<input id="submit" type="submit" name="form-submit" class="submit" value="<?php esc_attr_e( 'Go', 'buddyboss' ); ?>" />
		</noscript>
	</form>

	<?php
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_notifications_sort_order_form' ) && ! function_exists( 'bp_notifications_sort_order_form' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_notifications_sort_order_form()}.
	 *
	 * @deprecated Use bboss_notifications_sort_order_form() instead.
	 */
	function bp_notifications_sort_order_form() {
		return bboss_notifications_sort_order_form();
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the dropdown for bulk management of notifications.
 *
 * @since BuddyPress 2.2.0
 */
function bboss_notifications_bulk_management_dropdown() {
	?>
	<label class="bp-screen-reader-text" for="notification-select">
	<?php
		/* translators: accessibility text */
		esc_html_e( 'Select Bulk Action', 'buddyboss' );
	?>
	</label>
	<select name="notification_bulk_action" id="notification-select">
		<option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'buddyboss' ); ?></option>

		<?php if ( bboss_is_current_action( 'unread' ) ) : ?>
			<option value="read"><?php esc_html_e( 'Mark read', 'buddyboss' ); ?></option>
		<?php elseif ( bboss_is_current_action( 'read' ) ) : ?>
			<option value="unread"><?php esc_html_e( 'Mark unread', 'buddyboss' ); ?></option>
		<?php endif; ?>
		<option value="delete"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></option>
	</select>
	<input type="submit" id="notification-bulk-manage" class="button action" value="<?php esc_attr_e( 'Apply', 'buddyboss' ); ?>">
	<?php
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_notifications_bulk_management_dropdown' ) && ! function_exists( 'bp_notifications_bulk_management_dropdown' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_notifications_bulk_management_dropdown()}.
	 *
	 * @deprecated Use bboss_notifications_bulk_management_dropdown() instead.
	 */
	function bp_notifications_bulk_management_dropdown() {
		return bboss_notifications_bulk_management_dropdown();
	}
}
// @bbwt-com-end compat-layer


/**
 * Set on-screen notification template.
 *
 * @since BuddyBoss 1.7.0
 *
 * @return void
 */
function bboss_on_screen_notification_template() {
	$is_on_screen_notification_enable = bboss_get_option( '_bp_on_screen_notifications_enable', 0 );

	if ( empty( $is_on_screen_notification_enable ) || ! is_user_logged_in() ) {
		return;
	}

	remove_filter( 'bp_notifications_get_registered_components', 'bb_notification_exclude_group_message_notification', 999, 1 );
	$args = array(
		'user_id'           => bboss_loggedin_user_id(),
		'is_new'            => true,
		'fields'            => 'id',
		'update_meta_cache' => false,
	);
	// Actually, query it.
	$user_unread_notification_ids = BBoss_Notifications_Notification::get( $args );
	add_filter( 'bp_notifications_get_registered_components', 'bb_notification_exclude_group_message_notification', 999, 1 );

	$position           = bboss_get_option( '_bp_on_screen_notifications_position', 'right' );
	$has_mobile_support = bboss_get_option( '_bp_on_screen_notifications_mobile_support', '0' );
	$browser_tab        = bboss_get_option( '_bp_on_screen_notifications_browser_tab', 0 );
	$visibility         = bboss_get_option( '_bp_on_screen_notifications_visibility', 0 );
	$enable             = bboss_get_option( '_bp_on_screen_notifications_enable', 0 );

	?>
	<div class="bb-onscreen-notification-enable <?php echo '1' === $has_mobile_support ? 'bb-onscreen-notification-enable-mobile-support' : ''; ?>">
		<div
			class="bb-onscreen-notification bb-position-<?php echo esc_attr( $position ); ?>"
			style="display: none;"
			data-title-tag=""
			data-flash-status="default_title"
			data-broser-tab="<?php echo esc_attr( $browser_tab ); ?>"
			data-visibility="<?php echo esc_attr( $visibility ); ?>"
			data-enable="<?php echo esc_attr( $enable ); ?>"
		>
			<ul
				class="notification-list bb-nouveau-list"
				data-removed-items="<?php echo esc_attr( wp_json_encode( $user_unread_notification_ids ) ); ?>"
				data-auto-removed-items="<?php echo esc_attr( wp_json_encode( array() ) ); ?>"
				data-border-items="<?php echo esc_attr( wp_json_encode( array() ) ); ?>"
				data-flash-items="<?php echo esc_attr( wp_json_encode( array() ) ); ?>"
				data-animated-items="<?php echo esc_attr( wp_json_encode( array() ) ); ?>"
			>
			</ul>
			<div class="bb-remove-all-notification">
				<a class="action-close primary">
					<span class="bb-for-desktop"><?php esc_html_e( 'Clear', 'buddyboss' ); ?></span>
					<span class="bb-for-mobile"><?php esc_html_e( 'Clear All', 'buddyboss' ); ?></span>
					<span class="dashicons dashicons-no" aria-hidden="true"></span>
				</a>
			</div>
		</div>
	</div>
	<?php
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_on_screen_notification_template' ) && ! function_exists( 'bb_on_screen_notification_template' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_on_screen_notification_template()}.
	 *
	 * @deprecated Use bboss_on_screen_notification_template() instead.
	 */
	function bb_on_screen_notification_template() {
		return bboss_on_screen_notification_template();
	}
}
// @bbwt-com-end compat-layer

// No-Screen notification template.
add_action( 'wp_footer', 'bb_on_screen_notification_template' );
