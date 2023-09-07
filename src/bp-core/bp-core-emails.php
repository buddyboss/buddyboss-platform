<?php
/**
 * BuddyBoss Core Email Setup.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
/**
 * Setup the bp-core-email-tokens component.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_setup_core_email_tokens() {
	new BP_Email_Tokens();
}
add_action( 'bp_init', 'bp_setup_core_email_tokens', 0 );

/**
 * Set content type for all generic email notifications.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_email_set_content_type() {
	return 'text/html';
}

/**
 * Output template for email notifications.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_email_core_wp_get_template( $content = '', $user = false ) {
	ob_start();

	// Remove 'bp_replace_the_content' filter to prevent infinite loops.
	remove_filter( 'the_content', 'bp_replace_the_content' );

	set_query_var( 'email_content', $content );
	set_query_var( 'email_user', $user );
	bp_get_template_part( 'assets/emails/wp/email-template' );

	// Remove 'bp_replace_the_content' filter to prevent infinite loops.
	add_filter( 'the_content', 'bp_replace_the_content' );

	// Get the output buffer contents.
	$output = ob_get_clean();

	return $output;
}

/**
 * Function to load the instance of the class BP_Email_Queue.
 *
 * @since BuddyBoss 1.8.1
 *
 * @return null|BP_Email_Queue|void
 */
function bb_email_queue() {
	if ( class_exists( 'BP_Email_Queue' ) ) {
		return BP_Email_Queue::instance();
	}
}

/**
 * Function to check if bb_email_queue() and cron enabled
 *
 * @since BuddyBoss 1.8.1
 *
 * @return bool
 */
function bb_is_email_queue() {
	return function_exists( 'bb_email_queue' ) && class_exists( 'BB_Background_Updater' ) && apply_filters( 'bb_is_email_queue', ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) );
}

/**
 * Function to used for disabled email queue.
 *
 * @since BuddyBoss 1.8.3
 *
 * @return false
 */
function bb_disabled_email_queue() {
	return false;
}

/**
 * Check if there is enough recipients to use batch emails.
 *
 * @param array $recipients User IDs of recipients.
 *
 * @return bool
 *
 * @since BuddyBoss 1.8.1
 */
function bb_email_queue_has_min_count( $recipients ) {
	$min_recipients = false;
	$min_count      = bb_get_email_queue_min_count();

	if ( $min_count < count( (array) $recipients ) ) {
		$min_recipients = true;
	}

	return $min_recipients;
}

/**
 * Function to return minimum queue count to chunk large record.
 *
 * @since BuddyBoss 2.3.3
 *
 * @return int
 */
function bb_get_email_queue_min_count() {
	return (int) apply_filters( 'bb_email_queue_min_count', 20 );
}
