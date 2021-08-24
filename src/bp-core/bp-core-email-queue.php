<?php
/**
 * BuddyPress email queue.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 1.7.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Function to use BP_Email_Queue class instance
 *
 * @return BP_Email_Queue|void
 *
 * @since BuddyBoss 1.7.6
 */
function bp_email_queue() {
	if ( class_exists( 'BP_Email_Queue' ) ) {
		global $bp_email_queue;
		$bp_email_queue = new BP_Email_Queue();

		return $bp_email_queue;
	}
}

/**
 * Email queue class init.
 *
 * @since BuddyBoss 1.7.6
 */
function bp_email_queue_init() {
	bp_email_queue();
}

add_action( 'bp_init', 'bp_email_queue_init' );




