<?php
/**
 * Activity: Like action
 *
 * @package BuddyBoss\Activity\Actions\Like
 * @since BuddyPress 3.0.0
 */

/**
 * Mark activity as liked.
 *
 * @since BuddyPress 1.2.0
 *
 * @return bool False on failure.
 */
function bp_activity_action_mark_favorite() {
	if ( ! is_user_logged_in() || ! bp_is_activity_component() || ! bp_is_current_action( 'favorite' ) ) {
		return false;
	}

	if ( empty( $activity_id ) && bp_action_variable( 0 ) ) {
		$activity_id = (int) bp_action_variable( 0 );
	}

	// Not viewing a specific activity item.
	if ( empty( $activity_id ) ) {
		return false;
	}

	// Check the nonce.
	check_admin_referer( 'mark_favorite' );

	// Load up the activity item.
	$activity = new BP_Activity_Activity( $activity_id );

	if ( empty( $activity->id ) ) {
		return false;
	}

	if ( 'activity_comment' === $activity->type ) {
		$type     = 'activity_comment';
		$message  = __( 'Activity comment post saved.', 'buddyboss' );
		$redirect = wp_get_referer() . '#acomment-display-' . $activity_id;
	} else {
		$type     = 'activity';
		$message  = __( 'Activity post saved.', 'buddyboss' );
		$redirect = wp_get_referer() . '#activity-' . $activity_id;
	}

	$reacted = bp_activity_add_user_favorite(
		$activity_id,
		0,
		array(
			'type'       => $type,
			'error_type' => 'wp_error',
		)
	);
	if ( is_wp_error( $reacted ) ) {
		bp_core_add_message( $reacted->get_error_message(), 'error' );
	} else {
		bp_core_add_message( $message );
	}

	bp_core_redirect( $redirect );
}
add_action( 'bp_actions', 'bp_activity_action_mark_favorite' );
