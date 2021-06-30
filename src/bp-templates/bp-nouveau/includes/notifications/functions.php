<?php
/**
 * Notifications functions
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Scripts for the Notifications component
 *
 * @since BuddyPress 3.0.0
 *
 * @param  array  $scripts  The array of scripts to register
 * @return array  The same array with the specific notifications scripts.
 */
function bp_nouveau_notifications_register_scripts( $scripts = array() ) {

	if ( ! isset( $scripts['bp-nouveau'] ) ) {
		return $scripts;
	}

	return array_merge( $scripts, array(
		'bp-nouveau-notifications' => array(
			'file'         => 'js/buddypress-notifications%s.js',
			'dependencies' => array( 'bp-nouveau' ),
			'footer'       => true,
		),
	) );
}

/**
 * Enqueue the notifications scripts
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_notifications_enqueue_scripts() {

	if ( ! bp_is_user_notifications() ) {
		return;
	}

	wp_enqueue_script( 'bp-nouveau-notifications' );
}

/**
 * Init Notifications filters and fire a hook to let
 * plugins/components register their filters.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_notifications_init_filters() {
	if ( ! bp_is_user_notifications() ) {
		return;
	}

	bp_nouveau()->notifications->filters = array();

	/**
	 * Hook here to register your custom notification filters
	 *
	 * @since BuddyPress 3.0.0
	 */
	do_action( 'bp_nouveau_notifications_init_filters' );
}

/**
 * Register new filters for the notifications screens.
 *
 * @since BuddyPress 3.0.0
 *
 * @param  array  $args {
 *     Array of arguments.
 *
 *     @type string      $id         The unique string to identify your "component action". Required.
 *     @type string      $label      The human readable notification type. Required.
 *     @type int         $position   The position to output your filter. Optional.
 * }
 * @return bool True if the filter has been successfully registered. False otherwise.
 */
function bp_nouveau_notifications_register_filter( $args = array() ) {
	$bp_nouveau = bp_nouveau();

	$r = wp_parse_args( $args, array(
		'id'       => '',
		'label'    => '',
		'position' => 99,
	) );

	if ( empty( $r['id'] ) || empty( $r['label'] ) ) {
		return false;
	}

	if ( isset( $bp_nouveau->notifications->filters[ $r['id'] ] ) ) {
		return false;
	}

	$bp_nouveau->notifications->filters[ $r['id'] ] = $r;
	return true;
}

/**
 * Get one or all notifications filters.
 *
 * @since BuddyPress 3.0.0
 *
 * @param  string $id  The notificication component action to get the filter of.
 *                     Leave empty to get all notifications filters.
 * @return array|false All or a specific notifications parameters. False if no match are found.
 */
function bp_nouveau_notifications_get_filters( $id = '' ) {
	$bp_nouveau = bp_nouveau();

	// Get all filters
	if ( empty( $id ) ) {
		return $bp_nouveau->notifications->filters;

	// Get a specific filter
	} elseif ( ! empty( $id ) && isset( $bp_nouveau->notifications->filters[ $id ] ) ) {
		return $bp_nouveau->notifications->filters[ $id ];

	} else {
		return false;
	}
}

/**
 * Sort Notifications according to their position arguments.
 *
 * @since BuddyPress 3.0.0
 *
 * @param  array  $filters The notifications filters to order.
 * @return array  The sorted filters.
 */
function bp_nouveau_notifications_sort( $filters = array() ) {
	$sorted = array();

	if ( empty( $filters ) || ! is_array( $filters ) ) {
		return $sorted;
	}

	foreach ( $filters as $filter ) {
		$position = 99;

		if ( isset( $filter['position'] ) ) {
			$position = (int) $filter['position'];
		}

		// If position is already taken, move to the first next available
		if ( isset( $sorted[ $position ] ) ) {
			$sorted_keys = array_keys( $sorted );

			do {
				$position += 1;
			} while ( in_array( $position, $sorted_keys, true ) );
		}

		$sorted[ $position ] = $filter;
	}

	ksort( $sorted );
	return $sorted;
}

/**
 * Add a dashicon to Notifications action links
 *
 * @since BuddyPress 3.0.0
 *
 * @param  string $link        The action link.
 * @param  string $bp_tooltip  The data-bp-attribute of the link.
 * @param  string $aria_label  The aria-label attribute of the link.
 * @param  string $dashicon    The dashicon class.
 * @return string              Link Output.
 */
function bp_nouveau_notifications_dashiconified_link( $link = '', $bp_tooltip = '', $dashicon = '' ) {
	preg_match( '/<a\s[^>]*>(.*)<\/a>/siU', $link, $match );

	if ( ! empty( $match[0] ) && ! empty( $match[1] ) && ! empty( $dashicon ) && ! empty( $bp_tooltip ) ) {
		$link = str_replace(
			'>' . $match[1] . '<',
			sprintf(
				' class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="%1$s"><span class="dashicons %2$s" aria-hidden="true"></span><span class="bp-screen-reader-text">%3$s</span><',
				esc_attr( $bp_tooltip ),
				sanitize_html_class( $dashicon ),
				$match[1]
			),
			$match[0]
		);
	}

	return $link;
}

/**
 * Edit the Mark Unread action link to include a dashicon
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $link Optional. The Mark Unread action link.
 *
 * @return string Link Output.
 */
function bp_nouveau_notifications_mark_unread_link( $link = '' ) {
	return bp_nouveau_notifications_dashiconified_link(
		$link,
		__( 'Mark Unread', 'buddyboss' ),
		'dashicons-hidden'
	);
}

/**
 * Edit the Mark Read action link to include a dashicon
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $link Optional. The Mark Read action link.
 *
 * @return string Link Output.
 */
function bp_nouveau_notifications_mark_read_link( $link = '' ) {
	return bp_nouveau_notifications_dashiconified_link(
		$link,
		__( 'Mark Read', 'buddyboss' ),
		'dashicons-visibility'
	);
}

/**
 * Edit the Delete action link to include a dashicon
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $link Optional. The Delete action link.
 *
 * @return string Link Output.
 */
function bp_nouveau_notifications_delete_link( $link = '' ) {
	return bp_nouveau_notifications_dashiconified_link(
		$link,
		__( 'Delete', 'buddyboss' ),
		'dashicons-trash'
	);
}

/**
 * Get avatar for notification user.
 *
 * @since BuddyPress 1.7.0
 *
 * @return void
 */
function bb_notification_avatar() {
	$notification = buddypress()->notifications->query_loop->notification;
	$component    = $notification->component_name;

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
			} else {
				$item_id = $notification->item_id;
				$object  = 'user';
			}
			break;
	}

	if ( isset( $item_id, $object ) ) {

		if ( 'group' === $object ) {
			$group = new BP_Groups_Group( $item_id );
			$link  = bp_get_group_permalink( $group );
		} else {
			$user = new WP_User( $item_id );
			$link = bp_core_get_user_domain( $user->ID, $user->user_nicename, $user->user_login );
		}

		?>
		<a href="<?php echo esc_url( $link ); ?>">
			<?php
			echo bp_core_fetch_avatar(
				array(
					'item_id' => $item_id,
					'object'  => $object,
				)
			);
			?>
			<?php ( isset( $user ) ? bb_current_user_status( $user->ID ) : '' ); ?>
		</a>
		<?php
	}
}

/**
 * Current user online status.
 *
 * @since BuddyPress 1.7.0
 *
 * @param int $user_id User id.
 *
 * @return void
 */
function bb_current_user_status( $user_id ) {
	if ( bb_is_online_user( $user_id ) ) {
		echo '<span class="member-status online"></span>';
	}
}

/**
 * Current user online activity time. 
 *
 * @since BuddyPress 1.7.0
 *
 * @param int $user_id User id.
 *
 * @return string
 */
function bb_is_online_user( $user_id ) {

	if ( ! function_exists( 'bp_get_user_last_activity' ) ) {
		return;
	}

	$last_activity = strtotime( bp_get_user_last_activity( $user_id ) );

	if ( empty( $last_activity ) ) {
		return false;
	}

	// the activity timeframe is 5 minutes
	$activity_timeframe = 5 * MINUTE_IN_SECONDS;
	return ( time() - $last_activity <= $activity_timeframe );
}
