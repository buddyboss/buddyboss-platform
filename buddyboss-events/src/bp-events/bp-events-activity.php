<?php
/**
 * BuddyBoss Events Activity Integration.
 *
 * Registers event activity action types and posts activity items to
 * BuddyBoss feeds when events are created and when members RSVP.
 * Enforces group privacy via hide_sitewide and a custom can_read filter.
 *
 * Scope: event_created and event_rsvp activity types only.
 * Ticket purchase activity is out of scope for Phase 3 (paid ticketing
 * not yet implemented in Phase 2).
 *
 * @package BuddyBoss\Events\Activity
 * @since BuddyBoss Events 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register event activity action types with BuddyBoss activity component.
 *
 * Hooked to bp_register_activity_actions so types are available before
 * any activity item is posted.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_register_activity_actions() {
	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

	$bp = buddypress();

	bp_activity_set_action(
		$bp->events->id,
		'event_created',
		__( 'Created an event', 'buddyboss' ),
		'bp_events_format_action_event_created',
		__( 'Events', 'buddyboss' ),
		array( 'activity', 'member', 'group', 'member_groups' )
	);

	bp_activity_set_action(
		$bp->events->id,
		'event_rsvp',
		__( 'RSVPed to an event', 'buddyboss' ),
		'bp_events_format_action_event_rsvp',
		__( 'Events', 'buddyboss' ),
		array( 'activity', 'member', 'group', 'member_groups' )
	);
}
add_action( 'bp_register_activity_actions', 'bp_events_register_activity_actions' );

/**
 * Format activity action string for event_created items.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param string               $action   Existing action string (unused).
 * @param BP_Activity_Activity $activity Activity object.
 * @return string Formatted action string.
 */
function bp_events_format_action_event_created( $action, $activity ) {
	$user_link  = bp_core_get_userlink( $activity->user_id );
	$event      = bp_events_get_event( $activity->secondary_item_id );
	$event_link = $event
		? '<a href="' . esc_url( bp_get_event_permalink( $event ) ) . '">' . esc_html( $event->title ) . '</a>'
		: esc_html__( 'an event', 'buddyboss' );

	/* translators: 1: user link, 2: event link */
	return sprintf( __( '%1$s created the event %2$s', 'buddyboss' ), $user_link, $event_link );
}

/**
 * Format activity action string for event_rsvp items.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param string               $action   Existing action string (unused).
 * @param BP_Activity_Activity $activity Activity object.
 * @return string Formatted action string.
 */
function bp_events_format_action_event_rsvp( $action, $activity ) {
	$user_link  = bp_core_get_userlink( $activity->user_id );
	$event      = bp_events_get_event( $activity->secondary_item_id );
	$event_link = $event
		? '<a href="' . esc_url( bp_get_event_permalink( $event ) ) . '">' . esc_html( $event->title ) . '</a>'
		: esc_html__( 'an event', 'buddyboss' );

	/* translators: 1: user link, 2: event link */
	return sprintf( __( '%1$s RSVPed to the event %2$s', 'buddyboss' ), $user_link, $event_link );
}

/**
 * Post an activity item when a new event is created.
 *
 * Fires on bp_events_after_event_save. Differentiates create vs update by
 * checking that date_created equals date_modified (both are set to
 * bp_core_current_time() on initial INSERT; only date_modified changes on UPDATE).
 *
 * Only posts activity for published events. Sets hide_sitewide=true for
 * events belonging to private or hidden groups.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param BP_Event $event The saved event object.
 */
function bp_events_post_activity_on_create( BP_Event $event ) {
	if ( ! bp_is_active( 'activity' ) || empty( $event->id ) ) {
		return;
	}

	// Only on initial creation — date_created equals date_modified on INSERT.
	if ( $event->date_created !== $event->date_modified ) {
		return;
	}

	// Only post for published events.
	if ( 'published' !== $event->status ) {
		return;
	}

	$hide_sitewide = false;
	if ( ! empty( $event->group_id ) ) {
		$group         = groups_get_group( (int) $event->group_id );
		$hide_sitewide = ( $group && 'public' !== $group->status );
	}

	bp_activity_add(
		array(
			'user_id'           => $event->organizer_id,
			'component'         => buddypress()->events->id,
			'type'              => 'event_created',
			'item_id'           => ! empty( $event->group_id ) ? (int) $event->group_id : (int) $event->id,
			'secondary_item_id' => (int) $event->id,
			'primary_link'      => bp_get_event_permalink( $event ),
			'hide_sitewide'     => $hide_sitewide,
		)
	);
}
add_action( 'bp_events_after_event_save', 'bp_events_post_activity_on_create', 10, 1 );

/**
 * Post an activity item when a member RSVPs to an event.
 *
 * Fires on bp_events_rsvp_saved. Only posts for confirmed (registered) RSVPs,
 * not waitlist entries. Sets hide_sitewide=true for private/hidden group events.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int    $event_id Event ID.
 * @param int    $user_id  User ID.
 * @param string $status   'registered' or 'waitlisted'.
 */
function bp_events_post_activity_on_rsvp( $event_id, $user_id, $status ) {
	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

	// Only post for confirmed attendance, not waitlist.
	if ( 'registered' !== $status ) {
		return;
	}

	$event = bp_events_get_event( $event_id );
	if ( ! $event || 'published' !== $event->status ) {
		return;
	}

	$hide_sitewide = false;
	if ( ! empty( $event->group_id ) ) {
		$group         = groups_get_group( (int) $event->group_id );
		$hide_sitewide = ( $group && 'public' !== $group->status );
	}

	bp_activity_add(
		array(
			'user_id'           => $user_id,
			'component'         => buddypress()->events->id,
			'type'              => 'event_rsvp',
			'item_id'           => ! empty( $event->group_id ) ? (int) $event->group_id : (int) $event->id,
			'secondary_item_id' => (int) $event->id,
			'primary_link'      => bp_get_event_permalink( $event ),
			'hide_sitewide'     => $hide_sitewide,
		)
	);
}
add_action( 'bp_events_rsvp_saved', 'bp_events_post_activity_on_rsvp', 10, 3 );

/**
 * Enforce group privacy for events activity items.
 *
 * bp_activity_user_can_read() only auto-enforces privacy when component
 * equals buddypress()->groups->id. Since our items use component=events,
 * we add our own filter.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param bool                 $retval   Whether the user can read the activity.
 * @param int                  $user_id  The user ID.
 * @param BP_Activity_Activity $activity The activity object.
 * @return bool
 */
function bp_events_activity_can_read( $retval, $user_id, $activity ) {
	if ( buddypress()->events->id !== $activity->component ) {
		return $retval;
	}

	// If secondary_item_id is an event in a private/hidden group, enforce membership.
	$event = bp_events_get_event( $activity->secondary_item_id );
	if ( ! $event || empty( $event->group_id ) ) {
		return $retval;
	}

	$group = groups_get_group( (int) $event->group_id );
	if ( ! $group || 'public' === $group->status ) {
		return $retval;
	}

	// Private or hidden group — user must be a member.
	if ( ! groups_is_user_member( $user_id, (int) $event->group_id ) ) {
		return false;
	}

	return $retval;
}
add_filter( 'bp_activity_user_can_read', 'bp_events_activity_can_read', 10, 3 );
