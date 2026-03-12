<?php
/**
 * BuddyBoss Admin Settings - Notifications Callbacks.
 *
 * Sanitize callback functions for Notifications feature settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize on-screen notifications position setting.
 *
 * Accepts 'left' or 'right' values only.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized position value.
 */
function bb_notifications_sanitize_position( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'left', 'right' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'left';
	}

	return $value;
}

/**
 * Sanitize on-screen notifications visibility (auto-hide) setting.
 *
 * Accepts specific time values or 'never'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized visibility value.
 */
function bb_notifications_sanitize_visibility( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'never', '5', '10', '30', '60', '120', '180', '240', '300' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'never';
	}

	return $value;
}

/**
 * Sanitize delay email notification time setting.
 *
 * Validates against allowed cron delay times.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int Sanitized delay time in minutes.
 */
function bb_notifications_sanitize_delay_time( $value ) {
	$value = absint( $value );

	// Build allowed values from digest cron times.
	$allowed = array();
	if ( function_exists( 'bb_notification_get_digest_cron_times' ) ) {
		foreach ( bb_notification_get_digest_cron_times() as $time ) {
			$allowed[] = (int) $time['value'];
		}
	}

	// Fallback if function is unavailable.
	if ( empty( $allowed ) ) {
		$allowed = array( 5, 15, 30, 60, 180, 360, 720 );
	}

	if ( ! in_array( $value, $allowed, true ) ) {
		return 15;
	}

	return $value;
}

/**
 * No-op sanitize callback for notification types field.
 *
 * Notification types (bb_enabled_notification) are managed via a custom save
 * handler and should not be overwritten by the auto-save pipeline.
 * This callback returns the existing stored value so it is never clobbered.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The submitted value (ignored).
 *
 * @return array The existing stored value.
 */
function bb_notifications_sanitize_types_noop( $value ) {
	return bp_get_option( 'bb_enabled_notification', array() );
}

/**
 * Custom save handler for notification settings.
 *
 * Handles the complex notification types save logic, messaging notification
 * fields, and fires legacy hooks for backward compatibility.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID being saved.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bb_notifications_after_save_settings( $feature_id, $settings, $saved ) {
	if ( 'notifications' !== $feature_id ) {
		return;
	}

	// Handle notification types save (bb_enabled_notification).
	if ( isset( $settings['bb_enabled_notification'] ) && is_array( $settings['bb_enabled_notification'] ) ) {
		$enabled_notification = $settings['bb_enabled_notification'];

		// Filter out read-only preferences (maintain their defaults).
		$notification_preferences = bb_register_notification_preferences();
		$preferences              = array();
		if ( ! empty( $notification_preferences ) ) {
			foreach ( $notification_preferences as $group_data ) {
				if ( ! empty( $group_data['fields'] ) ) {
					$keys = array_filter(
						array_map(
							function ( $fields ) {
								if (
									isset( $fields['notification_read_only'] ) &&
									true === (bool) $fields['notification_read_only']
								) {
									return array(
										'key'     => $fields['key'],
										'default' => $fields['default'],
									);
								}
							},
							$group_data['fields']
						)
					);

					if ( ! empty( $keys ) ) {
						$preferences = array_merge( $keys, $preferences );
					}
				}
			}
		}

		if ( ! empty( $preferences ) ) {
			foreach ( $preferences as $preference ) {
				if ( isset( $preference['key'] ) && isset( $preference['default'] ) ) {
					if ( isset( $enabled_notification[ $preference['key'] ] ) && 'yes' === $preference['default'] ) {
						$enabled_notification[ $preference['key'] ]['main'] = $preference['default'];
					} else {
						unset( $enabled_notification[ $preference['key'] ] );
					}
				}
			}
		}

		bp_update_option( 'bb_enabled_notification', $enabled_notification );
	}

	/**
	 * Fires after notification settings are saved in Settings 2.0.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $settings Full submitted settings.
	 * @param array $saved    Keys and values saved by core.
	 */
	do_action( 'bb_notification_settings_after_save', $settings, $saved );
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_notifications_after_save_settings', 10, 3 );
