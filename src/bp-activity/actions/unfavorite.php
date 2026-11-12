<?php
/**
 * Activity: Remove like
 *
 * @package BuddyBoss\Activity\Actions\Like
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Remove activity from likes.
 *
 * @since BuddyPress 1.2.0
 *
 * @return bool False on failure.
 * @todo is this still used?
 */
function bboss_activity_action_remove_favorite() {
	if ( ! is_user_logged_in() || ! bboss_is_activity_component() || ! bboss_is_current_action( 'unfavorite' ) ) {
		return false;
	}

	if ( empty( $activity_id ) && bboss_action_variable( 0 ) ) {
		$activity_id = (int) bboss_action_variable( 0 );
	}

	// Not viewing a specific activity item.
	if ( empty( $activity_id ) ) {
		return false;
	}

	// Check the nonce.
	check_admin_referer( 'unmark_favorite' );

	// Load up the activity item.
	$activity = new BBoss_Activity_Activity( $activity_id );

	if ( empty( $activity->id ) ) {
		return false;
	}

	if ( 'activity_comment' === $activity->type ) {
		$type     = 'activity_comment';
		$message  = __( 'Post comment unsaved.', 'buddyboss' );
		$redirect = wp_get_referer() . '#acomment-display-' . $activity_id;
	} else {
		$type     = 'activity';
		$message  = __( 'Post unsaved.', 'buddyboss' );
		$redirect = wp_get_referer() . '#activity-' . $activity_id;
	}

	$un_reacted = bboss_activity_remove_user_favorite(
		$activity_id,
		0,
		array(
			'type'       => $type,
			'error_type' => 'wp_error',
		)
	);

	if ( is_wp_error( $un_reacted ) ) {
		bboss_core_add_message( $un_reacted->get_error_message(), 'error' );
	} else {
		bboss_core_add_message( $message );
	}

	bboss_core_redirect( $redirect );
}
add_action( 'bp_actions', 'bboss_activity_action_remove_favorite' );
