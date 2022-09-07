<?php
/**
 * BuddyBoss Notification Filters.
 *
 * Apply WordPress defined filters to notification.
 *
 * @package BuddyBoss\Notifications\Filters
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Schedule an event on change notification settings.
 */
function bb_schedule_event_on_update_notification_settings() {

	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( true === bb_enabled_legacy_email_preference() || ! bp_current_user_can( 'bp_moderate' ) || ! isset( $_POST['time_delay_email_notification'] ) ) {
		return;
	}

	$get_delay_times = bb_get_delay_notification_times();

	$old_scheduled_time                  = (int) bp_get_option( 'time_delay_email_notification', '' );
	$new_scheduled_time                  = isset( $_POST['time_delay_email_notification'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['time_delay_email_notification'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$is_enabled_delay_notification_after = isset( $_POST['delay_email_notification'] ) ? sanitize_text_field( wp_unslash( $_POST['delay_email_notification'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if (
		! empty( $old_scheduled_time ) &&
		(
			( $is_enabled_delay_notification_after && $old_scheduled_time !== $new_scheduled_time ) ||
			( ! $is_enabled_delay_notification_after )
		)
	) {
		$result_key = array_search( (int) $old_scheduled_time, array_column( $get_delay_times, 'value' ), true );

		// Un-schedule the scheduled event.
		if ( isset( $get_delay_times[ $result_key ] ) ) {
			$timestamp = wp_next_scheduled( $get_delay_times[ $result_key ]['schedule_action'] );

			if ( $timestamp ) {
				$is_unschedule = wp_unschedule_event( $timestamp, $get_delay_times[ $result_key ]['schedule_action'] );
			}
		}
	}

	if ( $is_enabled_delay_notification_after ) {

		$new_schedule_key = array_search( (int) $new_scheduled_time, array_column( $get_delay_times, 'value' ), true );
		// Schedule an action if it's not already scheduled.
		if ( isset( $get_delay_times[ $new_schedule_key ] ) && ! wp_next_scheduled( $get_delay_times[ $new_schedule_key ]['schedule_action'] ) ) {
			wp_schedule_event( time() + (int) $get_delay_times[ $new_schedule_key ]['schedule_interval'], $get_delay_times[ $new_schedule_key ]['schedule_key'], $get_delay_times[ $new_schedule_key ]['schedule_action'] );
		}
	}

}
add_action( 'bp_init', 'bb_schedule_event_on_update_notification_settings', 2 );

/**
 * Add schedule to cron schedules.
 *
 * @since Buddyboss [BBVERSION]
 *
 * @param array $schedules Array of schedules for cron.
 *
 * @return array $schedules Array of schedules from cron.
 */
function bb_delay_notification_register_cron_schedule_time( $schedules = array() ) {

	$get_delay_times = bb_get_delay_notification_times();

	foreach ( $get_delay_times as $cron_schedule ) {
		$schedules[ $cron_schedule['schedule_key'] ] = array(
			'interval' => $cron_schedule['schedule_interval'],
			'display'  => $cron_schedule['schedule_display'],
		);
	}

	return $schedules;
}
add_filter( 'cron_schedules', 'bb_delay_notification_register_cron_schedule_time' ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected

function bb_delay_email_notification_scheduled_action_callback() {
	// Add logic for notification.
}
add_action( 'bb_delay_email_notification_scheduled_action', 'bb_delay_email_notification_scheduled_action_callback' );

