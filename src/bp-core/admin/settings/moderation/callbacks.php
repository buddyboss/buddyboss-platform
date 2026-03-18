<?php
/**
 * BuddyBoss Admin Settings - Moderation Callbacks.
 *
 * Sanitize and render callback functions for Moderation feature settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize the auto-suspend threshold value.
 *
 * Ensures the value is a positive integer (minimum 1).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int The sanitized threshold value.
 */
function bb_moderation_sanitize_auto_suspend_threshold( $value ) {
	$value = absint( $value );

	return max( 1, $value );
}

/**
 * Sanitize the auto-hide threshold value for content reporting.
 *
 * Ensures the value is a positive integer between 1 and 99.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int The sanitized threshold value.
 */
function bb_moderation_sanitize_auto_hide_threshold( $value ) {
	$value = absint( $value );

	return max( 1, min( 99, $value ) );
}

/**
 * Sync individual content reporting field values back to legacy serialized arrays.
 *
 * Settings 2.0 stores each content type's reporting/auto-hide/threshold as individual
 * options (e.g., bpm_reporting_content_reporting_activity). The legacy system reads from
 * serialized arrays (bpm_reporting_content_reporting, bpm_reporting_auto_hide,
 * bpm_reporting_auto_hide_threshold). This hook keeps both in sync.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Submitted settings.
 * @param array  $saved      Saved option keys/values.
 */
function bb_moderation_sync_content_reporting_to_legacy( $feature_id, $settings, $saved ) {
	if ( 'moderation' !== $feature_id ) {
		return;
	}

	$content_types = bp_moderation_content_types();
	unset( $content_types[ BP_Moderation_Members::$moderation_type ] );
	unset( $content_types[ BP_Moderation_Members::$moderation_type_report ] );

	$reporting_array  = (array) get_option( 'bpm_reporting_content_reporting', array() );
	$auto_hide_array  = (array) get_option( 'bpm_reporting_auto_hide', array() );
	$threshold_array  = (array) get_option( 'bpm_reporting_auto_hide_threshold', array() );
	$arrays_changed   = false;

	foreach ( $content_types as $slug => $type_label ) {

		// Sync content reporting toggle.
		$reporting_key = 'bpm_reporting_content_reporting_' . $slug;
		if ( array_key_exists( $reporting_key, $saved ) ) {
			$reporting_array[ $slug ] = absint( $saved[ $reporting_key ] );
			$arrays_changed           = true;
		}

		// Sync auto-hide toggle.
		$auto_hide_key = 'bpm_reporting_auto_hide_' . $slug;
		if ( array_key_exists( $auto_hide_key, $saved ) ) {
			$auto_hide_array[ $slug ] = absint( $saved[ $auto_hide_key ] );
			$arrays_changed           = true;
		}

		// Sync auto-hide threshold.
		$threshold_key = 'bpm_reporting_auto_hide_threshold_' . $slug;
		if ( array_key_exists( $threshold_key, $saved ) ) {
			$threshold_array[ $slug ] = absint( $saved[ $threshold_key ] );
			$arrays_changed           = true;
		}
	}

	if ( $arrays_changed ) {
		update_option( 'bpm_reporting_content_reporting', $reporting_array );
		update_option( 'bpm_reporting_auto_hide', $auto_hide_array );
		update_option( 'bpm_reporting_auto_hide_threshold', $threshold_array );
	}
}
add_action( 'bb_admin_save_feature_settings_after', 'bb_moderation_sync_content_reporting_to_legacy', 10, 3 );
