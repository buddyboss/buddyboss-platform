<?php
/**
 * BuddyBoss Settings Functions
 *
 * @package BuddyBoss\Settings\Functions
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Update email notification settings for a specific user.
 *
 * @since BuddyPress 2.3.5
 *
 * @param int   $user_id  ID of the user whose settings are being updated.
 * @param array $settings Settings array.
 */
function bp_settings_update_notification_settings( $user_id, $settings ) {
	$user_id = (int) $user_id;

	$settings = bp_settings_sanitize_notification_settings( $settings );
	foreach ( $settings as $setting_key => $setting_value ) {

		if ( bb_enabled_legacy_email_preference() ) {
			if ( 'notification_membership_request_completed' === $setting_key && 'yes' === $setting_value ) {
				bp_update_user_meta( $user_id, 'bb_groups_request_accepted', 'yes' );
				bp_update_user_meta( $user_id, 'bb_groups_request_rejected', 'yes' );
			} elseif ( 'notification_membership_request_completed' === $setting_key && 'no' === $setting_value ) {
				bp_update_user_meta( $user_id, 'bb_groups_request_accepted', 'no' );
				bp_update_user_meta( $user_id, 'bb_groups_request_rejected', 'no' );
			} else {
				$all_keys = bb_get_prefences_key( 'legacy', $setting_key );
				bp_update_user_meta( $user_id, $all_keys, $setting_value );
			}
		} else {

			if (
				(
					'bb_groups_request_accepted' === $setting_key ||
					'bb_groups_request_rejected' === $setting_key
				) &&
				'yes' === $setting_value
			) {
				bp_update_user_meta( $user_id, 'notification_membership_request_completed', 'yes' );
			} elseif (
				'bb_groups_request_rejected' === $setting_key &&
				'yes' === $setting_value
			) {
				bp_update_user_meta( $user_id, 'notification_membership_request_completed', 'yes' );
			} elseif (
				'bb_groups_request_accepted' === $setting_key &&
				'yes' === $setting_value
			) {
				bp_update_user_meta( $user_id, 'notification_membership_request_completed', 'yes' );
			} elseif (
				(
					'bb_groups_request_accepted' === $setting_key ||
					'bb_groups_request_rejected' === $setting_key
				) &&
				isset( $settings['bb_groups_request_accepted'] ) &&
				'no' === $settings['bb_groups_request_accepted'] &&
				isset( $settings['bb_groups_request_rejected'] ) &&
				'no' === $settings['bb_groups_request_rejected']
			) {
				bp_update_user_meta( $user_id, 'notification_membership_request_completed', 'no' );
			} else {
				$all_keys = bb_get_prefences_key( 'modern', $setting_key );
				bp_update_user_meta( $user_id, $all_keys, $setting_value );
			}
		}

		bp_update_user_meta( $user_id, $setting_key, $setting_value );
	}
}

/**
 * Sanitize email notification settings as submitted by a user.
 *
 * @since BuddyPress 2.3.5
 *
 * @param array $settings Array of settings.
 * @return array Sanitized settings.
 */
function bp_settings_sanitize_notification_settings( $settings = array() ) {
	$sanitized_settings = array();

	if ( empty( $settings ) ) {
		return $sanitized_settings;
	}

	// Get registered notification keys.
	$registered_notification_settings = bp_settings_get_registered_notification_keys();

	/*
	 * We sanitize values for core notification keys.
	 *
	 * @todo use register_meta()
	 */
	$core_notification_settings = apply_filters(
		'bp_settings_core_notification_setting',
		array(
			'notification_messages_new_message',
			'notification_activity_new_mention',
			'notification_activity_new_reply',
			'notification_groups_invite',
			'notification_groups_group_updated',
			'notification_groups_admin_promotion',
			'notification_groups_membership_request',
			'notification_membership_request_completed',
			'notification_friends_friendship_request',
			'notification_friends_friendship_accepted',
		)
	);

	foreach ( (array) $settings as $key => $value ) {
		// Skip if not a registered setting.
		if ( ! in_array( $key, $registered_notification_settings, true ) ) {
			continue;
		}

		// Force core keys to 'yes' or 'no' values.
		if ( in_array( $key, $core_notification_settings, true ) ) {
			$value = 'yes' === $value ? 'yes' : 'no';
		}

		$sanitized_settings[ $key ] = $value;
	}

	return apply_filters( 'bp_settings_sanitize_notification_settings', $sanitized_settings );
}

/**
 * Build a dynamic whitelist of notification keys, based on what's hooked to 'bp_notification_settings'.
 *
 * @since BuddyPress 2.3.5
 *
 * @return array
 */
function bp_settings_get_registered_notification_keys() {

	ob_start();
	/**
	 * Fires at the start of the notification keys whitelisting.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'bp_notification_settings' );
	$screen = ob_get_clean();

	$matched = preg_match_all( '/<input[^>]+name="notifications\[([^\]]+)\]/', $screen, $matches );

	if ( $matched && isset( $matches[1] ) ) {
		$key_whitelist = $matches[1];
	} else {
		$key_whitelist = array();
	}

	return $key_whitelist;
}
