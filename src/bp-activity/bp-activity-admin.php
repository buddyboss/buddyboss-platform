<?php
/**
 * BuddyBoss Activity component admin screen.
 *
 * Props to WordPress core for the Comments admin screen, and its contextual
 * help text, on which this implementation is heavily based.
 *
 * @package BuddyBoss\Activity
 * @since BuddyPress 1.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get flattened array of all registered activity actions.
 *
 * Format is [activity_type] => Pretty name for activity type.
 *
 * @since BuddyPress 2.0.0
 *
 * @return array $actions
 */
function bp_activity_admin_get_activity_actions() {
	$actions = array();

	// Walk through the registered actions, and build an array of actions/values.
	foreach ( bp_activity_get_actions() as $action ) {
		$action = array_values( (array) $action );

		for ( $i = 0, $i_count = count( $action ); $i < $i_count; $i++ ) {
			$actions[ $action[ $i ]['key'] ] = $action[ $i ]['value'];
		}
	}

	// This was a mis-named activity type from before BP 1.6.
	unset( $actions['friends_register_activity_action'] );

	// Sort array by the human-readable value.
	natsort( $actions );

	return $actions;
}
