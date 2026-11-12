<?php
/**
 * Activity: Single permalink screen handler
 *
 * @package BuddyBoss\Activity\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Catch and route requests for single activity item permalinks.
 *
 * @since BuddyPress 1.2.0
 *
 * @return bool False on failure.
 */
function bboss_activity_action_permalink_router() {
	// Not viewing activity.
	if ( ! bboss_is_activity_component() || ! bboss_is_current_action( 'p' ) ) {
		return false;
	}

	// No activity to display.
	if ( ! bboss_action_variable( 0 ) || ! is_numeric( bboss_action_variable( 0 ) ) ) {
		return false;
	}

	// Get the activity details.
	$activity = bboss_activity_get_specific(
		array(
			'activity_ids' => bboss_action_variable( 0 ),
			'show_hidden'  => true,
		)
	);

	// 404 if activity does not exist
	if ( empty( $activity['activities'][0] ) ) {
		bboss_do_404();
		return;
	} else {
		$activity = $activity['activities'][0];
	}

	// Do not redirect at default.
	$redirect = false;

	do_action( 'bb_activity_before_permalink_redirect_url', $activity );

	// Redirect based on the type of activity.
	if ( bboss_is_active( 'groups' ) && $activity->component == bboss()->groups->id ) {

		// Activity is a user update.
		if ( ! empty( $activity->user_id ) ) {
			$redirect = bboss_core_get_user_domain( $activity->user_id, $activity->user_nicename, $activity->user_login ) . bboss_get_activity_slug() . '/' . $activity->id . '/';

			// Activity is something else.
		} else {

			// Set redirect to group activity feed.
			if ( $group = bboss_groups_get_group( $activity->item_id ) ) {
				$redirect = bboss_get_group_permalink( $group ) . bboss_get_activity_slug() . '/' . $activity->id . '/';
			}
		}

		// Set redirect to users' activity feed.
	} elseif ( ! empty( $activity->user_id ) ) {
		$redirect = bboss_core_get_user_domain( $activity->user_id, $activity->user_nicename, $activity->user_login ) . bboss_get_activity_slug() . '/' . $activity->id . '/';
	}

	do_action( 'bb_activity_after_permalink_redirect_url', $activity );

	// check if edit activity link.
	if ( 'edit' === bboss_action_variable( 1 ) ) {
		$redirect = untrailingslashit( $redirect ) . '/edit';
	}

	// If set, add the original query string back onto the redirect URL.
	if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
		$query_frags = array();
		wp_parse_str( wp_unslash( $_SERVER['QUERY_STRING'] ), $query_frags ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- parsed into pairs that are urlencode_deep()'d before they go back onto the redirect URL.
		$redirect = add_query_arg( urlencode_deep( $query_frags ), $redirect );
	}

	/**
	 * Filter the intended redirect url before the redirect occurs for the single activity item.
	 *
	 * @since BuddyPress 1.2.2
	 *
	 * @param array $value Array with url to redirect to and activity related to the redirect.
	 */
	if ( ! $redirect = apply_filters_ref_array( 'bp_activity_permalink_redirect_url', array( $redirect, &$activity ) ) ) {
		bboss_core_redirect( bboss_get_root_domain() );
	}

	// Redirect to the actual activity permalink page.
	bboss_core_redirect( $redirect );
}
add_action( 'bp_actions', 'bboss_activity_action_permalink_router' );

/**
 * Load the page for a single activity item.
 *
 * @since BuddyPress 1.2.0
 *
 * @return bool|string Boolean on false or the template for a single activity item on success.
 */
function bboss_activity_screen_single_activity_permalink() {
	// No displayed user or not viewing activity component.
	if ( ! bboss_is_activity_component() ) {
		return false;
	}

	$action = bboss_current_action();
	if ( ! $action || ! is_numeric( $action ) ) {
		return false;
	}

	// Get the activity details.
	$activity = bboss_activity_get_specific(
		array(
			'activity_ids' => $action,
			'show_hidden'  => true,
			'spam'         => 'ham_only',
		)
	);

	// 404 if activity does not exist
	if ( empty( $activity['activities'][0] ) || ( bboss_action_variables() && ! bboss_is_activity_edit() ) ) {
		bboss_do_404();
		return;

	} else {
		$activity = $activity['activities'][0];
	}

	/**
	 * Check user access to the activity item.
	 *
	 * @since BuddyPress 3.0.0
	 */
	$has_access = bboss_activity_user_can_read( $activity );

	// If activity author does not match displayed user, block access.
	// More info:https://buddypress.trac.wordpress.org/ticket/7048#comment:28
	if ( true === $has_access && bboss_displayed_user_id() !== $activity->user_id ) {
		$has_access = false;
	}

	/**
	 * Fires before the loading of a single activity template file.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param BBoss_Activity_Activity $activity   Object representing the current activity item being displayed.
	 * @param bool                 $has_access Whether or not the current user has access to view activity.
	 */
	do_action( 'bp_activity_screen_single_activity_permalink', $activity, $has_access );

	// Access is specifically disallowed.
	if ( false === $has_access ) {
		// If not logged in, prompt for login.
		if ( ! is_user_logged_in() ) {
			bboss_core_no_access();

			// Redirect away.
		} else {
			bboss_core_add_message( __( 'You do not have access to this activity.', 'buddyboss' ), 'error' );
			bboss_core_redirect( bboss_loggedin_user_domain() );
		}
	}

	/**
	 * Filters the template to load for a single activity screen.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $template Path to the activity template to load.
	 */
	$template = apply_filters( 'bp_activity_template_profile_activity_permalink', 'members/single/activity/permalink' );

	// Load the template.
	bboss_core_load_template( $template );
}
add_action( 'bp_screens', 'bboss_activity_screen_single_activity_permalink' );
