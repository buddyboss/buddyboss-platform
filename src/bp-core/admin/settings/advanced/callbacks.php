<?php
/**
 * BuddyBoss Admin Settings - Advanced Sanitize Callbacks.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize the telemetry reporting value.
 *
 * Ensures only valid telemetry modes are saved.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized telemetry mode.
 */
function bb_advanced_sanitize_telemetry_reporting( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'complete', 'anonymous', 'disable' );

	return in_array( $value, $allowed, true ) ? $value : 'anonymous';
}

/**
 * Sanitize the activity load type value.
 *
 * Ensures only valid autoload types are saved.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized activity load type.
 */
function bb_advanced_sanitize_activity_load_type( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array_keys(
		bp_parse_args(
			apply_filters( 'bb_performance_activity_autoload', array() ),
			array(
				'infinite'  => __( 'Infinite Scroll', 'buddyboss' ),
				'load_more' => __( 'Load More', 'buddyboss' ),
			)
		)
	);

	return in_array( $value, $allowed, true ) ? $value : 'infinite';
}

/**
 * Capture telemetry value before save for change detection.
 *
 * Stores the pre-save value in a static so the after-save handler
 * can compare old vs new (bp_get_option reads the already-saved value
 * in the after hook, making direct comparison impossible).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Submitted settings.
 *
 * @return void
 */
function bb_advanced_capture_telemetry_before_save( $feature_id, $settings ) {
	if ( 'advanced' !== $feature_id ) {
		return;
	}

	// Capture old value before the save loop overwrites it.
	bb_advanced_get_pre_save_telemetry( bp_get_option( 'bb_advanced_telemetry_reporting', 'anonymous' ) );
}
add_action( 'bb_admin_settings_before_save_feature', 'bb_advanced_capture_telemetry_before_save', 10, 2 );

/**
 * Store/retrieve the pre-save telemetry value.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string|null $set_value Value to store, or null to retrieve.
 *
 * @return string The stored telemetry value.
 */
function bb_advanced_get_pre_save_telemetry( $set_value = null ) {
	static $old_value = null;

	if ( null !== $set_value ) {
		$old_value = $set_value;
	}

	return $old_value;
}

/**
 * Handle telemetry mode change after save.
 *
 * When the telemetry mode changes to "complete", triggers an immediate
 * telemetry report. Matches legacy behavior in
 * BB_Admin_Setting_Performance::bb_admin_send_immediate_telemetry_on_complete().
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Submitted settings.
 * @param array  $saved      Saved settings (sanitized).
 *
 * @return void
 */
function bb_advanced_handle_telemetry_save( $feature_id, $settings, $saved ) {
	if ( 'advanced' !== $feature_id ) {
		return;
	}

	if ( ! isset( $saved['bb_advanced_telemetry_reporting'] ) ) {
		return;
	}

	$new_value = $saved['bb_advanced_telemetry_reporting'];
	$old_value = bb_advanced_get_pre_save_telemetry();

	// Dismiss telemetry notice if reporting status has changed.
	if ( null !== $old_value && $old_value !== $new_value ) {
		bp_update_option( 'bb_telemetry_notice_dismissed', 1 );
	}

	// Send immediate telemetry report when switching TO "complete" mode.
	if ( 'complete' === $new_value && ( null === $old_value || $old_value !== $new_value ) && class_exists( 'BB_Telemetry' ) ) {
		// Clear single scheduled cron.
		if ( wp_next_scheduled( 'bb_telemetry_report_single_cron_event' ) ) {
			wp_clear_scheduled_hook( 'bb_telemetry_report_single_cron_event' );
		}

		$bb_telemetry = BB_Telemetry::instance();
		$bb_telemetry->bb_send_telemetry_report_to_analytics();
	}
}
add_action( 'bb_admin_save_feature_settings_after', 'bb_advanced_handle_telemetry_save', 10, 3 );

/**
 * Sanitize the public content textarea values.
 *
 * Strips HTML tags and slashes, preserving newlines.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized value.
 */
function bb_advanced_sanitize_public_content( $value ) {
	return wp_strip_all_tags( stripslashes( $value ) );
}
