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
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_schedule_event_on_update_notification_settings() {

	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( true === bb_enabled_legacy_email_preference() || ! bp_current_user_can( 'bp_moderate' ) || ! isset( $_POST['time_delay_email_notification'] ) ) {
		return;
	}

	$old_scheduled_time                  = bb_get_delay_email_notifications_time();
	$new_scheduled_time                  = (int) sanitize_text_field( wp_unslash( $_POST['time_delay_email_notification'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$is_enabled_delay_notification_after = isset( $_POST['delay_email_notification'] ) ? sanitize_text_field( wp_unslash( $_POST['delay_email_notification'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if (
		! empty( $old_scheduled_time ) &&
		(
			( $is_enabled_delay_notification_after && $old_scheduled_time !== $new_scheduled_time ) ||
			( ! $is_enabled_delay_notification_after )
		)
	) {
		$old_schedule_found = bb_get_delay_notification_time_by_minutes( $old_scheduled_time );
		// Un-schedule the scheduled event.
		if ( ! empty( $old_schedule_found ) ) {
			$timestamp = wp_next_scheduled( 'bb_digest_email_notifications_hook' );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'bb_digest_email_notifications_hook' );
			}
		}
	}

	if ( $is_enabled_delay_notification_after ) {

		$new_schedule_found = bb_get_delay_notification_time_by_minutes( $new_scheduled_time );
		// Schedule an action if it's not already scheduled.
		if ( ! empty( $new_schedule_found ) ) {
			bp_core_schedule_cron( 'digest_email_notifications', 'bb_digest_email_notifications', $new_schedule_found['schedule_key'] );
		}
	}
}
add_action( 'bp_init', 'bb_schedule_event_on_update_notification_settings', 2 );

/**
 * Prepare the email notification content.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_digest_email_notifications() {
	global $wpdb;

	if ( ! function_exists( 'bb_render_digest_messages_template' ) ) {
		return;
	}

	// Get all defined time.
	$db_delay_time = bb_get_delay_email_notifications_time();

	if ( ! empty( $db_delay_time ) ) {
		$get_delay_time_array = bb_get_delay_notification_time_by_minutes( $db_delay_time );

		if ( ! empty( $get_delay_time_array ) && $db_delay_time === $get_delay_time_array['value'] ) {

			$current_date = bp_core_current_time();
			$start_date   = wp_date( 'Y-m-d H:i:s', strtotime( $current_date . ' -' . $db_delay_time . ' minutes' ), new DateTimeZone( 'UTC' ) );

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT m.*, r.user_id, r.unread_count FROM `{$wpdb->prefix}bp_messages_messages` AS m LEFT JOIN `{$wpdb->prefix}bp_messages_recipients` AS r ON m.thread_id = r.thread_id WHERE m.date_sent >= %s AND m.date_sent <= %s AND r.unread_count > %d AND r.is_deleted = %d AND r.is_hidden = %d ORDER BY m.thread_id, m.id ASC",
					$start_date,
					$current_date,
					0,
					0,
					0
				)
			);

			$threads = array();
			if ( ! empty( $results ) ) {
				foreach ( $results as $unread_message ) {
					$threads[ $unread_message->thread_id ]['thread_id'] = $unread_message->thread_id;

					// Set messages.
					$threads[ $unread_message->thread_id ]['recipients'][ $unread_message->user_id ][] = array(
						'message_id'    => $unread_message->id,
						'sender_id'     => $unread_message->sender_id,
						'recipients_id' => $unread_message->user_id,
						'message'       => $unread_message->message,
						'subject'       => $unread_message->subject,
						'thread_id'     => $unread_message->thread_id,
					);

					if ( function_exists( 'bp_messages_update_meta' ) ) {
						// Save meta to sent unread digest email notifications.
						bp_messages_update_meta( $unread_message->id, 'bb_sent_digest_email', 'yes' );
					}
				}
			}

			if ( ! empty( $threads ) ) {
				foreach ( $threads as $thread ) {

					if ( empty( $thread['recipients'] ) ) {
						continue;
					}

					// check if it has enough recipients to use batch emails.
					$min_count_recipients = function_exists( 'bb_email_queue_has_min_count' ) && bb_email_queue_has_min_count( (array) $thread['recipients'] );

					if ( function_exists( 'bb_is_email_queue' ) && bb_is_email_queue() && $min_count_recipients ) {
						global $bb_email_background_updater;

						$chunk_recipient_array = array_chunk( $thread['recipients'], 10 );

						if ( ! empty( $chunk_recipient_array ) ) {
							foreach ( $chunk_recipient_array as $chunk_recipient ) {
								$bb_email_background_updater->data(
									array(
										array(
											'callback' => 'bb_render_digest_messages_template',
											'args'     => array(
												$chunk_recipient,
												$thread['thread_id'],
											),
										),
									)
								);
								$bb_email_background_updater->save();
							}
							$bb_email_background_updater->dispatch();
						}
					} else {
						bb_render_digest_messages_template( $thread['recipients'], $thread['thread_id'] );
					}
				}
			}
		}
	}
}
