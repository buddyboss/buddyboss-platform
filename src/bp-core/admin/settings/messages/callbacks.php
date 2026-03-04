<?php
/**
 * BuddyBoss Admin Settings - Messages Callbacks.
 *
 * Sanitize and render callback functions for Messages feature settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize the delay email notification time value.
 *
 * Validates that the submitted value is one of the allowed cron time values.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int The sanitized delay time in minutes.
 */
function bb_messages_sanitize_delay_time( $value ) {
	$value       = absint( $value );
	$delay_times = bb_notification_get_digest_cron_times();
	$allowed     = wp_list_pluck( $delay_times, 'value' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 15; // Default to 15 minutes.
	}

	return $value;
}
