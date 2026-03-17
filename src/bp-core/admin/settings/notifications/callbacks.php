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
		return 'right';
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
 * Sanitize notification types (bb_enabled_notification).
 *
 * Validates the submitted notification types array: sanitizes keys,
 * enforces yes/no values, whitelists against registered preferences,
 * and preserves read-only preference defaults.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The submitted value.
 *
 * @return array Sanitized notification types.
 */
function bb_notifications_sanitize_types( $value ) {
	if ( ! is_array( $value ) ) {
		return bp_get_option( 'bb_enabled_notification', array() );
	}

	// Sanitize: rebuild with sanitized keys, only 'yes' or 'no' values.
	$allowed_values       = array( 'yes', 'no' );
	$enabled_notification = array();
	foreach ( $value as $key => $sub_values ) {
		if ( ! is_string( $key ) || ! is_array( $sub_values ) ) {
			continue;
		}

		$safe_key                          = sanitize_key( $key );
		$enabled_notification[ $safe_key ] = array();

		foreach ( $sub_values as $sub_key => $val ) {
			$val = sanitize_text_field( $val );
			if ( ! in_array( $val, $allowed_values, true ) ) {
				$val = 'no';
			}
			$enabled_notification[ $safe_key ][ sanitize_key( $sub_key ) ] = $val;
		}
	}

	// Whitelist: discard keys not registered in notification preferences.
	$notification_preferences = bb_register_notification_preferences();
	$registered_keys          = array();
	if ( ! empty( $notification_preferences ) ) {
		foreach ( $notification_preferences as $group_data ) {
			if ( ! empty( $group_data['fields'] ) ) {
				foreach ( $group_data['fields'] as $field ) {
					if ( ! empty( $field['key'] ) ) {
						$registered_keys[] = $field['key'];
					}
				}
			}
		}
	}

	if ( ! empty( $registered_keys ) ) {
		$enabled_notification = array_intersect_key( $enabled_notification, array_flip( $registered_keys ) );
	}

	// Filter out read-only preferences (maintain their defaults).
	$preferences = array();
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

	return $enabled_notification;
}
