<?php
/**
 * BuddyBoss Admin Settings - reCAPTCHA Callbacks.
 *
 * Sanitize, validate, and save callback functions for reCAPTCHA settings.
 * Handles the serialized bb_recaptcha option write-back from Settings 2.0.
 *
 * @package BuddyBoss\Features\Integrations\Recaptcha
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get the combined version value from legacy settings.
 *
 * Merges recaptcha_version + v2_option into a single value:
 * - recaptcha_v3
 * - recaptcha_v2_checkbox
 * - recaptcha_v2_invisible
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $settings The reCAPTCHA settings array.
 *
 * @return string Combined version value.
 */
function bb_recaptcha_admin_get_combined_version( $settings = array() ) {
	if ( empty( $settings ) ) {
		$settings = bb_recaptcha_options();
	}

	$version   = isset( $settings['recaptcha_version'] ) ? $settings['recaptcha_version'] : 'recaptcha_v3';
	$v2_option = isset( $settings['v2_option'] ) ? $settings['v2_option'] : 'v2_checkbox';

	if ( 'recaptcha_v2' === $version ) {
		if ( 'v2_invisible_badge' === $v2_option ) {
			return 'recaptcha_v2_invisible';
		}
		return 'recaptcha_v2_checkbox';
	}

	return 'recaptcha_v3';
}

/**
 * Get the version description text based on selected version.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $version The combined version value.
 *
 * @return string Description text.
 */
function bb_recaptcha_admin_get_version_description( $version ) {
	switch ( $version ) {
		case 'recaptcha_v2_checkbox':
			return __( 'reCAPTCHA v2 (Checkbox) validate request with the "I\'m not a robot" checkbox.', 'buddyboss' );
		case 'recaptcha_v2_invisible':
			return __( 'Shows invisible reCaptcha badge. It is invoked directly when the user clicks on an existing button on your site.', 'buddyboss' );
		case 'recaptcha_v3':
		default:
			return __( 'reCAPTCHA v3 runs silently in the background to detect spam and bot activity using a score system. No user action needed.', 'buddyboss' );
	}
}

/**
 * Split a combined version value into legacy recaptcha_version + v2_option.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $combined_version The combined version value.
 *
 * @return array Array with 'recaptcha_version' and 'v2_option' keys.
 */
function bb_recaptcha_split_combined_version( $combined_version ) {
	switch ( $combined_version ) {
		case 'recaptcha_v2_checkbox':
			return array(
				'recaptcha_version' => 'recaptcha_v2',
				'v2_option'         => 'v2_checkbox',
			);
		case 'recaptcha_v2_invisible':
			return array(
				'recaptcha_version' => 'recaptcha_v2',
				'v2_option'         => 'v2_invisible_badge',
			);
		case 'recaptcha_v3':
		default:
			return array(
				'recaptcha_version' => 'recaptcha_v3',
				'v2_option'         => 'v2_checkbox',
			);
	}
}

/**
 * Sanitize the combined version field.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized version value.
 */
function bb_recaptcha_sanitize_version( $value ) {
	$allowed = array( 'recaptcha_v3', 'recaptcha_v2_checkbox', 'recaptcha_v2_invisible' );

	if ( in_array( $value, $allowed, true ) ) {
		return $value;
	}

	return 'recaptcha_v3';
}

/**
 * Sanitize the score threshold value.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return float Sanitized score threshold.
 */
function bb_recaptcha_sanitize_score_threshold( $value ) {
	$value = floatval( $value );

	if ( $value < 0 ) {
		return 0;
	}

	if ( $value > 1 ) {
		return 1;
	}

	return $value;
}

/**
 * Sanitize the enabled_for toggle list value.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array Sanitized enabled_for array.
 */
function bb_recaptcha_sanitize_enabled_for( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$allowed_keys = array( 'bb_login', 'bb_register', 'bb_lost_password', 'bb_activate' );
	$sanitized    = array();

	foreach ( $allowed_keys as $key ) {
		$sanitized[ $key ] = isset( $value[ $key ] ) ? absint( $value[ $key ] ) : 0;
	}

	return $sanitized;
}

/**
 * Sanitize the bypass text value.
 *
 * Must be 6-10 characters. Returns empty string if invalid.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized bypass text.
 */
function bb_recaptcha_sanitize_bypass_text( $value ) {
	$value = sanitize_text_field( trim( $value ) );

	if ( ! empty( $value ) ) {
		$length = strlen( $value );
		if ( $length < 6 || $length > 10 ) {
			return '';
		}
	}

	return $value;
}

/**
 * Sanitize the theme value.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized theme value.
 */
function bb_recaptcha_sanitize_theme( $value ) {
	$allowed = array( 'light', 'dark' );
	return in_array( $value, $allowed, true ) ? $value : 'light';
}

/**
 * Sanitize the size value.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized size value.
 */
function bb_recaptcha_sanitize_size( $value ) {
	$allowed = array( 'normal', 'compact' );
	return in_array( $value, $allowed, true ) ? $value : 'normal';
}

/**
 * Sanitize the badge position value.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized badge position value.
 */
function bb_recaptcha_sanitize_badge_position( $value ) {
	$allowed = array( 'bottomright', 'bottomleft', 'inline' );
	return in_array( $value, $allowed, true ) ? $value : 'bottomright';
}

/**
 * Write reCAPTCHA settings back to the serialized bb_recaptcha option
 * after Settings 2.0 AJAX save.
 *
 * Settings 2.0 auto-save sends individual field values. This callback
 * collects them and writes back to the single serialized option that
 * all public API functions (bb_recaptcha_site_key(), etc.) read from.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings (JSON decoded).
 * @param array  $saved      Keys and values saved to options.
 */
function bb_recaptcha_write_back_serialized_option( $feature_id, $settings, $saved ) {

	// Only run for recaptcha feature.
	if ( 'recaptcha' !== $feature_id ) {
		return;
	}

	// Map of Settings 2.0 field names to serialized array keys.
	$field_map = array(
		'bb_recaptcha_version'         => 'version',      // Combined value — needs splitting.
		'bb_recaptcha_site_key'        => 'site_key',
		'bb_recaptcha_secret_key'      => 'secret_key',
		'bb_recaptcha_score_threshold' => 'score_threshold',
		'bb_recaptcha_enabled_for'     => 'enabled_for',
		'bb_recaptcha_allow_bypass'    => 'allow_bypass',
		'bb_recaptcha_bypass_text'     => 'bypass_text',
		'bb_recaptcha_language_code'   => 'language_code',
		'bb_recaptcha_conflict_mode'   => 'conflict_mode',
		'bb_recaptcha_exclude_ip'      => 'exclude_ip',
		'bb_recaptcha_theme'           => 'theme',
		'bb_recaptcha_size'            => 'size',
		'bb_recaptcha_badge_position'  => 'badge_position',
	);

	// Get current serialized settings.
	$recaptcha_settings = bb_recaptcha_options();
	if ( ! is_array( $recaptcha_settings ) ) {
		$recaptcha_settings = array();
	}

	$has_changes = false;

	foreach ( $field_map as $field_name => $setting_key ) {
		if ( ! array_key_exists( $field_name, $saved ) ) {
			continue;
		}

		$value = $saved[ $field_name ];

		// Handle version splitting: combined value -> two keys.
		if ( 'version' === $setting_key ) {
			$split = bb_recaptcha_split_combined_version( $value );
			$recaptcha_settings['recaptcha_version'] = $split['recaptcha_version'];
			$recaptcha_settings['v2_option']          = $split['v2_option'];
			$has_changes = true;
			continue;
		}

		$recaptcha_settings[ $setting_key ] = $value;
		$has_changes = true;
	}

	if ( ! $has_changes ) {
		return;
	}

	// Preserve connection status — it's managed by the verify AJAX endpoint, not auto-save.
	// If site_key or secret_key were cleared, reset connection status.
	if (
		( isset( $saved['bb_recaptcha_site_key'] ) && empty( $saved['bb_recaptcha_site_key'] ) ) ||
		( isset( $saved['bb_recaptcha_secret_key'] ) && empty( $saved['bb_recaptcha_secret_key'] ) )
	) {
		$recaptcha_settings['connection_status'] = 'not-connected';
	}

	// Bypass validation: if allow_bypass is off or bypass_text is empty, clear bypass.
	if ( empty( $recaptcha_settings['allow_bypass'] ) || empty( $recaptcha_settings['bypass_text'] ) ) {
		$recaptcha_settings['allow_bypass'] = false;
	}

	bp_update_option( 'bb_recaptcha', $recaptcha_settings );

	// Clean up individual options created by Settings 2.0 default per-field save.
	// These are not needed — all consumers read from the serialized array.
	foreach ( array_keys( $field_map ) as $field_name ) {
		if ( array_key_exists( $field_name, $saved ) ) {
			delete_option( $field_name );
		}
	}
}
add_action( 'bb_admin_save_feature_settings_after', 'bb_recaptcha_write_back_serialized_option', 10, 3 );

/**
 * Handle the Settings 2.0 reCAPTCHA verification AJAX request.
 *
 * This is the new endpoint used by the InputButtonField component.
 * It validates the reCAPTCHA token via Google API and updates
 * the connection status in the serialized option.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_recaptcha_verify_settings_2() {

	// Capability check.
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'Unauthorized.', 'buddyboss' ),
			)
		);
	}

	// Nonce check — uses the Settings 2.0 nonce.
	check_ajax_referer( 'bb_admin_settings', 'nonce' );

	$site_key   = isset( $_POST['bb_recaptcha_site_key'] ) ? sanitize_text_field( wp_unslash( $_POST['bb_recaptcha_site_key'] ) ) : '';
	$secret_key = isset( $_POST['bb_recaptcha_secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['bb_recaptcha_secret_key'] ) ) : '';
	$version    = isset( $_POST['bb_recaptcha_version'] ) ? sanitize_text_field( wp_unslash( $_POST['bb_recaptcha_version'] ) ) : 'recaptcha_v3';

	if ( empty( $site_key ) || empty( $secret_key ) ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'Please enter both Site Key and Secret Key.', 'buddyboss' ),
			)
		);
	}

	// Save keys to the serialized option first.
	$settings = bb_recaptcha_options();
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	$split = bb_recaptcha_split_combined_version( $version );
	$settings['site_key']          = $site_key;
	$settings['secret_key']        = $secret_key;
	$settings['recaptcha_version'] = $split['recaptcha_version'];
	$settings['v2_option']         = $split['v2_option'];

	// The React component will handle rendering the Google widget and sending the token.
	$captcha_response = isset( $_POST['captcha_response'] ) ? sanitize_text_field( wp_unslash( $_POST['captcha_response'] ) ) : '';

	$connection_status = 'not_connected';

	if ( ! empty( $captcha_response ) ) {
		$response = bb_get_google_recaptcha_api_response( $secret_key, $captcha_response );
		if ( $response && ! empty( $response['success'] ) ) {
			$connection_status = 'connected';
		}
	}

	$settings['connection_status'] = $connection_status;
	bp_update_option( 'bb_recaptcha', $settings );

	if ( 'connected' === $connection_status ) {
		wp_send_json_success(
			array(
				'is_connected'   => true,
				'button_label'   => __( 'Connected', 'buddyboss' ),
				'message'        => __( 'reCAPTCHA verification was successful.', 'buddyboss' ),
				'status'         => array(
					'type' => 'success',
					'text' => __( 'Connected', 'buddyboss' ),
				),
				'updated_fields' => array(
					'bb_recaptcha_site_key'   => $site_key,
					'bb_recaptcha_secret_key' => $secret_key,
				),
			)
		);
	}

	wp_send_json_error(
		array(
			'message'      => esc_html__( 'reCAPTCHA verification failed, please try again.', 'buddyboss' ),
			'is_connected' => false,
		)
	);
}
add_action( 'wp_ajax_bb_recaptcha_verify_settings_2', 'bb_recaptcha_verify_settings_2' );
