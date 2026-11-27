<?php
/**
 * Activity: Reply action
 *
 * @package BuddyBoss\Activity\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Post new activity comment.
 *
 * @since BuddyPress 1.2.0
 *
 * @return bool False on failure.
 */
function bboss_activity_action_post_comment() {
	if ( ! is_user_logged_in() || ! bboss_is_activity_component() || ! bboss_is_current_action( 'reply' ) ) {
		return false;
	}

	// Check the nonce.
	check_admin_referer( 'new_activity_comment', '_wpnonce_new_activity_comment' );

	/**
	 * Filters the activity ID a comment will be in reply to.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $value ID of the activity being replied to.
	 */
	$activity_id = apply_filters( 'bp_activity_post_comment_activity_id', isset( $_POST['comment_form_id'] ) ? sanitize_text_field( wp_unslash( $_POST['comment_form_id'] ) ) : '' );

	/**
	 * Filters the comment content for a comment reply.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $value Comment content being posted.
	 */
	$content = apply_filters( 'bp_activity_post_comment_content', $_POST[ 'ac_input_' . $activity_id ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- comment content: an empty value is rejected below and it is kses-filtered on save by bp_activity_filter_kses() (bp_activity_content_before_save).

	if ( empty( $content ) ) {
		bboss_core_add_message( __( 'Please do not leave the comment area blank.', 'buddyboss' ), 'error' );
		bboss_core_redirect( wp_get_referer() . '#ac-form-' . $activity_id );
	}

	$comment_id = bboss_activity_new_comment(
		array(
			'content'     => $content,
			'activity_id' => $activity_id,
			'parent_id'   => false,
		)
	);

	if ( ! empty( $comment_id ) ) {
		bboss_core_add_message( __( 'Reply Posted!', 'buddyboss' ) );
	} else {
		bboss_core_add_message( __( 'There was an error posting that reply. Please try again.', 'buddyboss' ), 'error' );
	}

	bboss_core_redirect( wp_get_referer() . '#ac-form-' . $activity_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_activity_action_post_comment' ) && ! function_exists( 'bp_activity_action_post_comment' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_action_post_comment()}.
	 *
	 * @deprecated Use bboss_activity_action_post_comment() instead.
	 */
	function bp_activity_action_post_comment() {
		return bboss_activity_action_post_comment();
	}
}
// @bbwt-com-end compat-layer

add_action( 'bp_actions', 'bp_activity_action_post_comment' );
