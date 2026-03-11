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

/**
 * Reschedule the digest email cron event after Settings 2.0 saves
 * the delay notification options.
 *
 * The legacy function bb_schedule_event_on_update_notification_settings()
 * reads from $_POST which is not set in Settings 2.0 AJAX context.
 * This callback replicates the cron reschedule logic using the saved
 * option values from the database.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings (JSON decoded).
 * @param array  $saved      Keys and values saved to options.
 */
function bb_messages_reschedule_cron_after_save( $feature_id, $settings, $saved ) {

	// Only run for messages feature.
	if ( 'messages' !== $feature_id ) {
		return;
	}

	// Only run when delay-related fields were actually saved.
	if ( ! isset( $saved['delay_email_notification'] ) && ! isset( $saved['time_delay_email_notification'] ) ) {
		return;
	}

	// Skip when using legacy email preferences — the legacy handler manages cron.
	if ( true === bb_enabled_legacy_email_preference() ) {
		return;
	}

	$is_enabled = (bool) bp_get_option( 'delay_email_notification', 1 );
	$new_time   = absint( bp_get_option( 'time_delay_email_notification', 15 ) );
	$old_time   = bb_get_delay_email_notifications_time();

	// Un-schedule the current event if delay was disabled or the time changed.
	if (
		! empty( $old_time ) &&
		( ! $is_enabled || $old_time !== $new_time )
	) {
		$old_schedule = bb_get_delay_notification_time_by_minutes( $old_time );
		if ( ! empty( $old_schedule ) ) {
			$timestamp = wp_next_scheduled( 'bb_digest_email_notifications_hook' );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'bb_digest_email_notifications_hook' );
			}
		}
	}

	// Schedule the new event if delay is enabled.
	if ( $is_enabled ) {
		$new_schedule = bb_get_delay_notification_time_by_minutes( $new_time );
		if ( ! empty( $new_schedule ) ) {
			bp_core_schedule_cron( 'digest_email_notifications', 'bb_digest_message_email_notifications', $new_schedule['schedule_key'] );
		}
	}
}
add_action( 'bb_admin_save_feature_settings_after', 'bb_messages_reschedule_cron_after_save', 10, 3 );
