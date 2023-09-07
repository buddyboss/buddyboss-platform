<?php
/**
 * BuddyBoss Groups Activity Functions.
 *
 * These functions handle the recording, deleting and formatting of activity
 * for the user and for this specific component.
 *
 * @package BuddyBoss\Groups\Activity
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register activity actions for the Groups component.
 *
 * @since BuddyPress 1.1.0
 *
 * @return false|null False on failure.
 * @todo if these are overridden do they need to be in the textdomain?
 */
function groups_register_activity_actions() {
	$bp = buddypress();

	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	bp_activity_set_action(
		$bp->groups->id,
		'activity_update',
		__( 'Posted a status update', 'buddyboss' ),
		'bp_groups_format_activity_action_activity_update',
		__( 'Updates', 'buddyboss' ),
		array( 'activity', 'group', 'member', 'member_groups' )
	);

	bp_activity_set_action(
		$bp->groups->id,
		'created_group',
		__( 'Created a group', 'buddyboss' ),
		'bp_groups_format_activity_action_created_group',
		__( 'New Groups', 'buddyboss' ),
		array( 'activity', 'member', 'member_groups' )
	);

	bp_activity_set_action(
		$bp->groups->id,
		'joined_group',
		__( 'Joined a group', 'buddyboss' ),
		'bp_groups_format_activity_action_joined_group',
		__( 'Group Memberships', 'buddyboss' ),
		array( 'activity', 'group', 'member', 'member_groups' )
	);

	bp_activity_set_action(
		$bp->groups->id,
		'group_details_updated',
		__( 'Group details edited', 'buddyboss' ),
		'bp_groups_format_activity_action_group_details_updated',
		__( 'Group Updates', 'buddyboss' ),
		array( 'activity', 'group', 'member', 'member_groups' )
	);

	/**
	 * Fires at end of registration of the default activity actions for the Groups component.
	 *
	 * @since BuddyPress 1.1.0
	 */
	do_action( 'groups_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'groups_register_activity_actions' );

/**
 * Get the group object the activity belongs to.
 *
 * @since BuddyBoss 1.3.5
 * @since BuddyPress 5.0.0
 *
 * @param integer $group_id The group ID the activity is linked to.
 * @return BP_Groups_Group  The group object the activity belongs to.
 */
function bp_groups_get_activity_group( $group_id = 0 ) {
	// If displaying a specific group, check the activity belongs to it.
	if ( bp_is_group() && bp_get_current_group_id() === (int) $group_id ) {
		$group = groups_get_current_group();

		// Otherwise get the group the activity belongs to.
	} else {
		$group = groups_get_group( $group_id );
	}

	return $group;
}

/**
 * Format 'created_group' activity actions.
 *
 * @since BuddyPress 2.0.0
 *
 * @param string $action   Static activity action.
 * @param object $activity Activity data object.
 * @return string
 */
function bp_groups_format_activity_action_created_group( $action, $activity ) {
	$user_link = bp_core_get_userlink( $activity->user_id );

	$group      = groups_get_group( $activity->item_id );
	$group_link = '<a href="' . esc_url( bp_get_group_permalink( $group ) ) . '">' . esc_html( $group->name ) . '</a>';

	$action = sprintf( __( '%1$s created the group %2$s', 'buddyboss' ), $user_link, $group_link );

	/**
	 * Filters the 'created_group' activity actions.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $action   The 'created_group' activity action.
	 * @param object $activity Activity data object.
	 */
	return apply_filters( 'groups_activity_created_group_action', $action, $activity );
}

/**
 * Format 'joined_group' activity actions.
 *
 * @since BuddyPress 2.0.0
 *
 * @param string $action   Static activity action.
 * @param object $activity Activity data object.
 * @return string
 */
function bp_groups_format_activity_action_joined_group( $action, $activity ) {
	$user_link = bp_core_get_userlink( $activity->user_id );

	$group      = groups_get_group( $activity->item_id );
	$group_link = '<a href="' . esc_url( bp_get_group_permalink( $group ) ) . '">' . esc_html( $group->name ) . '</a>';

	$action = sprintf( __( '%1$s joined the group %2$s', 'buddyboss' ), $user_link, $group_link );

	// Legacy filters (do not follow parameter patterns of other activity
	// action filters, and requires apply_filters_ref_array()).
	if ( has_filter( 'groups_activity_membership_accepted_action' ) ) {
		$action = apply_filters_ref_array( 'groups_activity_membership_accepted_action', array( $action, $user_link, &$group ) );
	}

	// Another legacy filter.
	if ( has_filter( 'groups_activity_accepted_invite_action' ) ) {
		$action = apply_filters_ref_array( 'groups_activity_accepted_invite_action', array( $action, $activity->user_id, &$group ) );
	}

	/**
	 * Filters the 'joined_group' activity actions.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param string $action   The 'joined_group' activity actions.
	 * @param object $activity Activity data object.
	 */
	return apply_filters( 'bp_groups_format_activity_action_joined_group', $action, $activity );
}

/**
 * Format groups 'activity_update' activity actions.
 *
 * @since BuddyBoss 1.1.9
 *
 * @param string $action   Static activity action.
 * @param object $activity Activity data object.
 * @return string
 */
function bp_groups_format_activity_action_activity_update( $action, $activity ) {
	$user_link = bp_core_get_userlink( $activity->user_id );

	$group      = groups_get_group( $activity->item_id );
	$group_link = '<a href="' . esc_url( bp_get_group_permalink( $group ) ) . '">' . bp_get_group_name( $group ) . '</a>';

	$action = sprintf( __( '%1$s posted an update in the group %2$s', 'buddyboss' ), $user_link, $group_link );

	/**
	 * Filters the groups 'activity_update' activity actions.
	 *
	 * @since BuddyBoss 1.1.9
	 *
	 * @param string $action   The groups 'activity_update' activity actions.
	 * @param object $activity Activity data object.
	 */
	return apply_filters( 'bp_groups_format_activity_action_activity_update', $action, $activity );
}

/**
 * Format 'group_details_updated' activity actions.
 *
 * @since BuddyPress 2.2.0
 *
 * @param  string $action   Static activity action.
 * @param  object $activity Activity data object.
 * @return string
 */
function bp_groups_format_activity_action_group_details_updated( $action, $activity ) {
	$user_link = bp_core_get_userlink( $activity->user_id );

	$group      = bp_groups_get_activity_group( $activity->item_id );
	$group_link = '<a href="' . esc_url( bp_get_group_permalink( $group ) ) . '">' . esc_html( $group->name ) . '</a>';

	/*
	 * Changed group details are stored in groupmeta, keyed by the activity
	 * timestamp. See {@link bp_groups_group_details_updated_add_activity()}.
	 */
	$changed = groups_get_groupmeta( $activity->item_id, 'updated_details_' . $activity->date_recorded );

	// No changed details were found, so use a generic message.
	if ( empty( $changed ) ) {
		$action = sprintf( __( '%1$s updated details for the group %2$s', 'buddyboss' ), $user_link, $group_link );

		// Name and description changed - to keep things short, don't describe changes in detail.
	} elseif ( isset( $changed['name'] ) && isset( $changed['description'] ) ) {
		$action = sprintf( __( '%1$s changed the name and description of the group %2$s', 'buddyboss' ), $user_link, $group_link );

		// Name only.
	} elseif ( isset( $changed['name']['old'] ) && isset( $changed['name']['new'] ) ) {
		$action = sprintf( __( '%1$s changed the name of the group %2$s from "%3$s" to "%4$s"', 'buddyboss' ), $user_link, $group_link, wp_strip_all_tags( $changed['name']['old'] ), wp_strip_all_tags( $changed['name']['new'] ) );

		// Description only.
	} elseif ( isset( $changed['description']['old'] ) && isset( $changed['description']['new'] ) ) {
		$action = sprintf( __( '%1$s changed the description of the group %2$s from "%3$s" to "%4$s"', 'buddyboss' ), $user_link, $group_link, wp_strip_all_tags( $changed['description']['old'] ), wp_strip_all_tags( $changed['description']['new'] ) );

	} elseif ( isset( $changed['slug']['old'] ) && isset( $changed['slug']['new'] ) ) {
		$action = sprintf( __( '%1$s changed the permalink of the group %2$s.', 'buddyboss' ), $user_link, $group_link );

	}

	/**
	 * Filters the 'group_details_updated' activity actions.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param string $action   The 'group_details_updated' activity actions.
	 * @param object $activity Activity data object.
	 */
	return apply_filters( 'bp_groups_format_activity_action_joined_group', $action, $activity );
}

/**
 * Fetch data related to groups at the beginning of an activity loop.
 *
 * This reduces database overhead during the activity loop.
 *
 * @since BuddyPress 2.0.0
 *
 * @param array $activities Array of activity items.
 * @return array
 */
function bp_groups_prefetch_activity_object_data( $activities ) {
	$group_ids = array();

	if ( empty( $activities ) ) {
		return $activities;
	}

	foreach ( $activities as $activity ) {
		if ( buddypress()->groups->id !== $activity->component ) {
			continue;
		}

		$group_ids[] = $activity->item_id;
	}

	if ( ! empty( $group_ids ) ) {

		// TEMPORARY - Once the 'populate_extras' issue is solved
		// in the groups component, we can do this with groups_get_groups()
		// rather than manually.
		$uncached_ids = array();
		foreach ( $group_ids as $group_id ) {
			if ( false === wp_cache_get( $group_id, 'bp_groups' ) ) {
				$uncached_ids[] = $group_id;
			}
		}

		if ( ! empty( $uncached_ids ) ) {
			global $wpdb;
			$bp               = buddypress();
			$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );
			$groups           = $wpdb->get_results( "SELECT * FROM {$bp->groups->table_name} WHERE id IN ({$uncached_ids_sql})" );
			foreach ( $groups as $group ) {
				wp_cache_set( $group->id, $group, 'bp_groups' );
			}
		}
	}

	return $activities;
}
add_filter( 'bp_activity_prefetch_object_data', 'bp_groups_prefetch_activity_object_data' );

/**
 * Set up activity arguments for use with the 'groups' scope.
 *
 * @since BuddyPress 2.2.0
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array
 */
function bp_groups_filter_activity_scope( $retval = array(), $filter = array() ) {

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	// Fetch public groups.
	$public_groups = groups_get_groups(
		array(
			'fields'   => 'ids',
			'status'   => 'public',
			'per_page' => - 1,
			'user_id'  => $user_id,
		)
	);
	if ( ! empty( $public_groups['groups'] ) ) {
		$public_groups = $public_groups['groups'];
	} else {
		$public_groups = array();
	}

	// Determine groups of user.
	$groups = groups_get_groups(
		array(
			'fields'      => 'ids',
			'per_page'    => - 1,
			'user_id'     => $user_id,
			'show_hidden' => true,
		)
	);

	if ( empty( $groups['groups'] ) ) {
		$groups = array( 'groups' => array() );
	}

	$groups = $groups['groups'];

	$private_group = array_diff( $groups, $public_groups );

	// Should we show all items regardless of sitewide visibility?
	$show_hidden = array();
	if ( ! empty( $user_id ) && ( $user_id !== bp_loggedin_user_id() ) && is_user_logged_in() ) {

		// Determine groups of user.
		$logged_in_user_groups = groups_get_user_groups( bp_loggedin_user_id() );
		if ( ! empty( $logged_in_user_groups['groups'] ) ) {
			$private_group = array_intersect( $private_group, $logged_in_user_groups['groups'] );
		} else {
			$show_hidden = array(
				'column' => 'hide_sitewide',
				'value'  => 0,
			);
		}
	} elseif ( ! is_user_logged_in() ) {
		$show_hidden = array(
			'column' => 'hide_sitewide',
			'value'  => 0,
		);
	}

	if ( empty( $public_groups ) ) {
		$public_groups = array( 0 );
	}

	if ( empty( $private_group ) ) {
		$private_group = array( 0 );
	}

	$data = array(
		'relation' => 'AND',
		array(
			'column' => 'component',
			'value'  => buddypress()->groups->id,
		),
		array(
			'relation' => 'OR',
			array(
				'column'  => 'item_id',
				'compare' => 'IN',
				'value'   => (array) $public_groups,
			),
			array(
				'column'  => 'item_id',
				'compare' => 'IN',
				'value'   => (array) $private_group,
			),
		),
		array(
			'column'  => 'privacy',
			'compare' => '=',
			'value'   => 'public',
		),
	);

	if ( bp_is_user() ) {
		$data[] = array(
			'column'  => 'user_id',
			'compare' => '=',
			'value'   => $user_id,
		);
	}

	$retval = array(
		'relation' => 'AND',
		$data,
		$show_hidden,
		// Overrides.
		'override' => array(
			'filter'      => array( 'user_id' => 0 ),
			'show_hidden' => true,
		),
	);

	return $retval;
}
add_filter( 'bp_activity_set_groups_scope_args', 'bp_groups_filter_activity_scope', 10, 2 );

/**
 * Record an activity item related to the Groups component.
 *
 * A wrapper for {@link bp_activity_add()} that provides some Groups-specific
 * defaults.
 *
 * @since BuddyPress 1.0.0
 *
 * @see bp_activity_add() for more detailed description of parameters and
 *      return values.
 *
 * @param array|string $args {
 *     An array of arguments for the new activity item. Accepts all parameters
 *     of {@link bp_activity_add()}. However, this wrapper provides some
 *     additional defaults, as described below:
 *     @type string $component     Default: the id of your Groups component
 *                                 (usually 'groups').
 *     @type bool   $hide_sitewide Default: True if the current group is not
 *                                 public, otherwise false.
 * }
 * @return WP_Error|bool|int See {@link bp_activity_add()}.
 */
function groups_record_activity( $args = '' ) {
	global $bp_activity_edit;

	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	// Set the default for hide_sitewide by checking the status of the group.
	$hide_sitewide = false;
	if ( ! empty( $args['item_id'] ) ) {
		$group = bp_groups_get_activity_group( $args['item_id'] );

		if ( isset( $group->status ) && 'public' != $group->status ) {
			$hide_sitewide = true;
		}
	}

	// Edit group activity args
	if ( ! empty( $args['id'] ) ) {
		$activity = new BP_Activity_Activity( $args['id'] );

		if ( ! empty( $activity->id ) ) {
			$bp_activity_edit = true;

			if ( ! bp_activity_user_can_edit( $activity ) ) {
				if ( 'wp_error' === $args['error_type'] ) {
					return new WP_Error( 'error', __( 'Allowed time for editing this activity is passed already, you can not edit now.', 'buddyboss' ) );
				} else {
					return false;
				}
			}

			$args = array(
				'id'                => $activity->id,
				'action'            => ! empty( $args['action'] ) ? $args['action'] : $activity->action,
				'content'           => ! empty( $args['content'] ) ? $args['content'] : '',
				'component'         => $activity->component,
				'type'              => $activity->type,
				'primary_link'      => $activity->primary_link,
				'user_id'           => $activity->user_id,
				'item_id'           => ! empty( $args['item_id'] ) ? $args['item_id'] : $activity->item_id,
				'secondary_item_id' => $activity->secondary_item_id,
				'recorded_time'     => $activity->date_recorded,
				'hide_sitewide'     => $activity->hide_sitewide,
				'is_spam'           => $activity->is_spam,
				'privacy'           => $activity->privacy,
				'error_type'        => ! empty( $args['error_type'] ) ? $args['error_type'] : $activity->error_type,
			);

			/**
			 * Addition from the BuddyBoss
			 * Add meta to ensure that this activity has been edited.
			 */
			bp_activity_update_meta( $activity->id, '_is_edited', bp_core_current_time() );

		}
	}

	$r = bp_parse_args(
		$args,
		array(
			'id'                => false,
			'user_id'           => bp_loggedin_user_id(),
			'action'            => '',
			'content'           => '',
			'primary_link'      => '',
			'component'         => buddypress()->groups->id,
			'type'              => false,
			'item_id'           => false,
			'secondary_item_id' => false,
			'recorded_time'     => bp_core_current_time(),
			'hide_sitewide'     => $hide_sitewide,
			'privacy'           => 'public',
			'error_type'        => 'bool',
		),
		'groups_record_activity'
	);

	return bp_activity_add( $r );
}

/**
 * Function used to determine if a user can comment on a group activity item.
 *
 * Used as a filter callback to 'bp_activity_can_comment'.
 *
 * @since BuddyPress 3.0.0
 *
 * @param  bool                      $retval   True if item can receive comments.
 * @param  null|BP_Activity_Activity $activity Null by default. Pass an activity object to check against that instead.
 * @return bool
 */
function bp_groups_filter_activity_can_comment( $retval, $activity = null ) {
	// Bail if item cannot receive comments or if no current user.
	if ( empty( $retval ) || ! is_user_logged_in() ) {
		return $retval;
	}

	// Use passed activity object, if available.
	if ( is_a( $activity, 'BP_Activity_Activity' ) ) {
		$component = $activity->component;
		$group_id  = $activity->item_id;

		// Use activity info from current activity item in the loop.
	} else {
		$component = bp_get_activity_object_name();
		$group_id  = bp_get_activity_item_id();
	}

	// If not a group activity item, bail.
	if ( 'groups' !== $component ) {
		return $retval;
	}

	// If current user is not a group member or is banned, user cannot comment.
	if ( ! bp_current_user_can( 'bp_moderate' ) &&
		( ! groups_is_user_member( bp_loggedin_user_id(), $group_id ) || groups_is_user_banned( bp_loggedin_user_id(), $group_id ) )
	) {
		$retval = false;
	}

	return $retval;
}
add_filter( 'bp_activity_can_comment', 'bp_groups_filter_activity_can_comment', 99, 1 );

/**
 * Function used to determine if a user can reply on a group activity comment.
 *
 * Used as a filter callback to 'bp_activity_can_comment_reply'.
 *
 * @since BuddyPress 3.0.0
 *
 * @param  bool        $retval  True if activity comment can be replied to.
 * @param  object|bool $comment Current activity comment object. If empty, parameter is boolean false.
 * @return bool
 */
function bp_groups_filter_activity_can_comment_reply( $retval, $comment ) {
	// Bail if no current user, if comment is empty or if retval is already empty.
	if ( ! is_user_logged_in() || empty( $comment ) || empty( $retval ) ) {
		return $retval;
	}

	// Grab parent activity item.
	$parent = new BP_Activity_Activity( $comment->item_id );

	// Check to see if user can reply to parent group activity item.
	return bp_groups_filter_activity_can_comment( $retval, $parent );
}
add_filter( 'bp_activity_can_comment_reply', 'bp_groups_filter_activity_can_comment_reply', 99, 2 );

/**
 * Add an activity feed item when a member joins a group.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $user_id  ID of the user joining the group.
 * @param int $group_id ID of the group.
 * @return false|null False on failure.
 */
function bp_groups_membership_accepted_add_activity( $user_id, $group_id ) {

	// Bail if Activity is not active.
	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	// Get the group so we can get it's name.
	$group = groups_get_group( $group_id );

	/**
	 * Filters the 'membership_accepted' activity actions.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $value    The 'membership_accepted' activity action.
	 * @param int    $user_id  ID of the user joining the group.
	 * @param int    $group_id ID of the group. Passed by reference.
	 */
	$action = apply_filters_ref_array( 'groups_activity_membership_accepted_action', array( sprintf( __( '%1$s joined the group %2$s', 'buddyboss' ), bp_core_get_userlink( $user_id ), '<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>' ), $user_id, &$group ) );

	// Record in activity feeds.
	groups_record_activity(
		array(
			'action'  => $action,
			'type'    => 'joined_group',
			'item_id' => $group_id,
			'user_id' => $user_id,
		)
	);
}
add_action( 'groups_membership_accepted', 'bp_groups_membership_accepted_add_activity', 10, 2 );

/**
 * Add an activity item when a group's details are updated.
 *
 * @since BuddyPress 2.2.0
 *
 * @param  int             $group_id       ID of the group.
 * @param  BP_Groups_Group $old_group      Group object before the details had been changed.
 * @param  bool            $notify_members True if the admin has opted to notify group members, otherwise false.
 * @return null|WP_Error|bool|int The ID of the activity on success. False on error.
 */
function bp_groups_group_details_updated_add_activity( $group_id, $old_group, $notify_members ) {

	// Bail if Activity is not active.
	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	if ( ! isset( $old_group->name ) || ! isset( $old_group->slug ) || ! isset( $old_group->description ) ) {
		return false;
	}

	// If the admin has opted not to notify members, don't post an activity item either.
	/*
	 * Commented cause If user not selected "Notify group members of these changes via email" option
	 * that time activity should show in the activity area and widget area
	 */
	// if ( empty( $notify_members ) ) {
	// return;
	// }

	$group = groups_get_group(
		array(
			'group_id' => $group_id,
		)
	);

	/*
	 * Store the changed data, which will be used to generate the activity
	 * action. Since we haven't yet created the activity item, we store the
	 * old group data in groupmeta, keyed by the timestamp that we'll put
	 * on the activity item.
	 */
	$changed = array();

	if ( $group->name !== $old_group->name ) {
		$changed['name'] = array(
			'old' => $old_group->name,
			'new' => $group->name,
		);
	}

	if ( $group->slug !== $old_group->slug ) {
		$changed['slug'] = array(
			'old' => $old_group->slug,
			'new' => $group->slug,
		);
	}

	if ( $group->description !== $old_group->description ) {
		$changed['description'] = array(
			'old' => $old_group->description,
			'new' => $group->description,
		);
	}

	// If there are no changes, don't post an activity item.
	if ( empty( $changed ) ) {
		return;
	}

	$time = bp_core_current_time();
	groups_update_groupmeta( $group_id, 'updated_details_' . $time, $changed );

	// Record in activity feeds.
	return groups_record_activity(
		array(
			'type'          => 'group_details_updated',
			'item_id'       => $group_id,
			'user_id'       => bp_loggedin_user_id(),
			'recorded_time' => $time,

		)
	);

}
add_action( 'groups_details_updated', 'bp_groups_group_details_updated_add_activity', 10, 3 );

/**
 * Delete all activity items related to a specific group.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $group_id ID of the group.
 */
function bp_groups_delete_group_delete_all_activity( $group_id ) {
	if ( bp_is_active( 'activity' ) ) {
		bp_activity_delete_by_item_id(
			array(
				'item_id'   => $group_id,
				'component' => buddypress()->groups->id,
			)
		);
	}
}
add_action( 'groups_delete_group', 'bp_groups_delete_group_delete_all_activity', 10 );

/**
 * Delete group member activity if they leave or are removed within 5 minutes of membership modification.
 *
 * If the user joined this group less than five minutes ago, remove the
 * joined_group activity so users cannot flood the activity feed by
 * joining/leaving the group in quick succession.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $group_id ID of the group.
 * @param int $user_id  ID of the user leaving the group.
 */
function bp_groups_leave_group_delete_recent_activity( $group_id, $user_id ) {

	// Bail if Activity component is not active.
	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

	// Get the member's group membership information.
	$membership = new BP_Groups_Member( $user_id, $group_id );

	// Check the time period, and maybe delete their recent group activity.
	if ( $membership->date_modified && time() <= strtotime( '+5 minutes', (int) strtotime( $membership->date_modified ) ) ) {
		bp_activity_delete(
			array(
				'component' => buddypress()->groups->id,
				'type'      => 'joined_group',
				'user_id'   => $user_id,
				'item_id'   => $group_id,
			)
		);
	}
}
add_action( 'groups_leave_group', 'bp_groups_leave_group_delete_recent_activity', 10, 2 );
add_action( 'groups_remove_member', 'bp_groups_leave_group_delete_recent_activity', 10, 2 );
add_action( 'groups_ban_member', 'bp_groups_leave_group_delete_recent_activity', 10, 2 );

/**
 * Function will append join query to display group lists in the activity feed.
 *
 * @since BuddyBoss 2.1.7
 *
 * @param string $sql From SQL statement.
 * @param array  $r   Array of parsed arguments for the get method.
 *
 * @return string
 */
function bb_groups_get_join_sql_for_activity( $sql, $r ) {
	$bp_prefix = bp_core_get_table_prefix();
	$sql      .= ' LEFT JOIN ' . $bp_prefix . 'bp_groups_groupmeta mt ON ( g.id = mt.group_id )';

	return $sql;
}

/**
 * Function will append where condition to display group lists in the activity feed.
 *
 * @since BuddyBoss 2.1.7
 *
 * @param array $where_conditions Where conditions SQL statement.
 * @param array $r                Array of parsed arguments for the get method.
 *
 * @return mixed
 */
function bb_groups_get_where_conditions_for_activity( $where_conditions, $r ) {
	$where_conditions['exclude_where'] = ' (
		mt.meta_key = "activity_feed_status" AND
		(
			( mt.meta_value = "mods" AND ( m.is_mod = "1" OR m.is_admin = "1" ) ) OR
			( mt.meta_value = "admins" AND m.is_admin = "1" ) OR
			( mt.meta_value = "members" )
		)
	)';

	return $where_conditions;
}
