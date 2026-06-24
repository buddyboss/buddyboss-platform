<?php
/**
 * Recaptcha integration helpers.
 *
 * @since   BuddyBoss 2.5.60
 * @package BuddyBoss\Recaptcha
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns Recaptcha Integration url.
 *
 * @since BuddyBoss 2.5.60
 *
 * @param string $path Path to recaptcha integration.
 */
function bb_recaptcha_integration_url( $path = '' ) {
	return trailingslashit( buddypress()->plugin_url ) . 'bb-features/integrations/recaptcha/' . trim( $path, '/\\' );
}

/**
 * Retrieves the reCAPTCHA options.
 *
 * @since BuddyBoss 2.5.60
 *
 * @return array The reCAPTCHA options.
 */
function bb_recaptcha_options() {

	/**
	 * Filter allows modifying the reCAPTCHA options.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param array $recaptcha_options The reCAPTCHA options.
	 */
	return apply_filters( 'bb_recaptcha_options', bp_get_option( 'bb_recaptcha', array() ) );
}

/**
 * Retrieves the value of a specific reCAPTCHA setting.
 *
 * @since BuddyBoss 2.5.60
 *
 * @param string $key     The key of the setting to retrieve.
 * @param string $default Optional. The default value to return if the setting is not found.
 *                        Default is an empty string.
 *
 * @return mixed The value of the specified reCAPTCHA setting, or the default value if not found.
 */
function bb_recaptcha_setting( $key, $default = '' ) {
	$settings = bb_recaptcha_options();
	$retval   = $default;
	if ( isset( $settings[ $key ] ) ) {
		$retval = $settings[ $key ];
	}

	/**
	 * Filters recaptcha get settings.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param array $retval  Settings of recaptcha.
	 * @param mixed $key     Optional. Get setting by key.
	 * @param mixed $default Optional. Default value if value or setting not available.
	 */
	return apply_filters( 'bb_recaptcha_setting', $retval, $key, $default );
}

/**
 * Retrieves the selected reCAPTCHA version.
 *
 * @since BuddyBoss 2.5.60
 *
 * @return string The selected reCAPTCHA version.
 */
function bb_recaptcha_recaptcha_versions() {
	$recaptcha_version = bb_recaptcha_setting( 'recaptcha_version', 'recaptcha_v3' );

	/**
	 * Filters the selected reCAPTCHA version.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param string $recaptcha_version The selected reCAPTCHA version.
	 */
	return apply_filters( 'bb_recaptcha_recaptcha_versions', $recaptcha_version );
}

/**
 * Retrieves the selected reCAPTCHA v2 option.
 *
 * @since BuddyBoss 2.5.60
 *
 * @return string The selected v2 option.
 */
function bb_recaptcha_recaptcha_v2_option() {
	$recaptcha_v2_option = bb_recaptcha_setting( 'v2_option', 'v2_checkbox' );

	/**
	 * Filters the selected reCAPTCHA v2 option.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param string $recaptcha_v2_option The selected reCAPTCHA v2 option.
	 */
	return apply_filters( 'bb_recaptcha_recaptcha_v2_option', $recaptcha_v2_option );
}

/**
 * Retrieves the reCAPTCHA site key.
 *
 * @since BuddyBoss 2.5.60
 *
 * @return string The reCAPTCHA site key.
 */
function bb_recaptcha_site_key() {
	$site_key = bb_recaptcha_setting( 'site_key' );

	/**
	 * Filters the reCAPTCHA site key.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param string $site_key The reCAPTCHA site key.
	 */
	return apply_filters( 'bb_recaptcha_site_key', $site_key );
}

/**
 * Retrieves the reCAPTCHA secret key.
 *
 * @since BuddyBoss 2.5.60
 *
 * @return string The reCAPTCHA secret key.
 */
function bb_recaptcha_secret_key() {
	$secret_key = bb_recaptcha_setting( 'secret_key' );

	/**
	 * Filters the reCAPTCHA secret key.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param string $secret_key The reCAPTCHA secret key.
	 */
	return apply_filters( 'bb_recaptcha_secret_key', $secret_key );
}

/**
 * Retrieves the reCAPTCHA connection status.
 *
 * @since BuddyBoss 2.5.60
 *
 * @return string The reCAPTCHA connection status.
 */
function bb_recaptcha_connection_status() {
	$connection_status = bb_recaptcha_setting( 'connection_status' );

	/**
	 * Filters the reCAPTCHA connection status.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param string $secret_key The reCAPTCHA connection status.
	 */
	return apply_filters( 'bb_recaptcha_connection_status', $connection_status );
}

/**
 * Retrieves the reCAPTCHA score threshold.
 *
 * @since BuddyBoss 2.5.60
 *
 * @param float $default The default score threshold value. Default is 0.5.
 *
 * @return float The reCAPTCHA score threshold.
 */
function bb_recaptcha_score_threshold( $default = 0.5 ) {

	/**
	 * Filter allows modifying the reCAPTCHA score threshold.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param float $threshold The reCAPTCHA score threshold.
	 */
	return apply_filters( 'bb_recaptcha_score_threshold', bb_recaptcha_setting( 'score_threshold', $default ) );
}

/**
 * Retrieves the reCAPTCHA actions and their configurations.
 *
 * @since BuddyBoss 2.5.60
 *
 * @return array An associative array of reCAPTCHA actions and their configurations.
 */
function bb_recaptcha_actions() {
	$actions = array(
		'bb_login'         => array(
			'label'    => __( 'Login', 'buddyboss-platform' ),
			'disabled' => false,
			'enabled'  => bb_recaptcha_is_enabled( 'bb_login' ),
		),
		'bb_register'      => array(
			'label'    => __( 'Registration', 'buddyboss-platform' ),
			'disabled' => ! bp_enable_site_registration(),
			'enabled'  => bb_recaptcha_is_enabled( 'bb_register' ),
		),
		'bb_lost_password' => array(
			'label'    => __( 'Reset Password', 'buddyboss-platform' ),
			'disabled' => false,
			'enabled'  => bb_recaptcha_is_enabled( 'bb_lost_password' ),
		),
		'bb_activate'      => array(
			'label'    => __( 'Account Activation', 'buddyboss-platform' ),
			'disabled' => ! bp_enable_site_registration(),
			'enabled'  => bb_recaptcha_is_enabled( 'bb_activate' ),
		),
	);

	/**
	 * Filter hook allows modifying the reCAPTCHA actions and their configurations.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param array $actions An associative array of reCAPTCHA actions and their configurations.
	 */
	return apply_filters( 'bb_recaptcha_actions', $actions );
}

/**
 * Determines if reCAPTCHA is enabled for a specific action.
 *
 * @since BuddyBoss 2.5.60
 *
 * @param string $key The key of the action for which to check reCAPTCHA enabled status.
 *
 * @return bool True if reCAPTCHA is enabled for the action, false otherwise.
 */
function bb_recaptcha_is_enabled( $key ) {
	$enabled_keys = bb_recaptcha_setting( 'enabled_for', array() );
	$retval       = ! empty( $key ) && array_key_exists( $key, $enabled_keys ) && ! empty( $enabled_keys[ $key ] );

	/**
	 * Filters the enabled status of reCAPTCHA for a specific action.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param bool   $retval The current enabled status of reCAPTCHA for the specified action.
	 * @param string $key    The key of the action for which the enabled status is being determined.
	 */
	return (bool) apply_filters( 'bb_recaptcha_is_enabled', $retval, $key );
}

/**
 * Retrieves the reCAPTCHA bypass option.
 *
 * @since BuddyBoss 2.5.60
 *
 * @return bool The reCAPTCHA bypass option.
 */
function bb_recaptcha_allow_bypass_enable() {
	if ( ! bb_recaptcha_is_enabled( 'bb_login' ) ) {
		return false;
	}

	$allow_bypass = (bool) bb_recaptcha_setting( 'allow_bypass', false );

	/**
	 * Filters the reCAPTCHA bypass option.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param bool $allow_bypass The reCAPTCHA bypass option.
	 */
	return (bool) apply_filters( 'bb_recaptcha_allow_bypass_enable', $allow_bypass );
}

/**
 * Retrieves the list of supported reCAPTCHA languages.
 *
 * @since BuddyBoss 2.5.60
 *
 * @return array An associative array of supported reCAPTCHA languages.
 */
function bb_recaptcha_languages() {
	$languages = array(
		'ar'     => esc_html__( 'Arabic', 'buddyboss-platform' ),
		'af'     => esc_html__( 'Afrikaans', 'buddyboss-platform' ),
		'am'     => esc_html__( 'Amharic', 'buddyboss-platform' ),
		'hy'     => esc_html__( 'Armenian', 'buddyboss-platform' ),
		'az'     => esc_html__( 'Azerbaijani', 'buddyboss-platform' ),
		'eu'     => esc_html__( 'Basque', 'buddyboss-platform' ),
		'bn'     => esc_html__( 'Bengali', 'buddyboss-platform' ),
		'bg'     => esc_html__( 'Bulgarian', 'buddyboss-platform' ),
		'ca'     => esc_html__( 'Catalan', 'buddyboss-platform' ),
		'zh-HK'  => esc_html__( 'Chinese (Hong Kong)', 'buddyboss-platform' ),
		'zh-CN'  => esc_html__( 'Chinese (Simplified)', 'buddyboss-platform' ),
		'zh-TW'  => esc_html__( 'Chinese (Traditional)', 'buddyboss-platform' ),
		'hr'     => esc_html__( 'Croatian', 'buddyboss-platform' ),
		'cs'     => esc_html__( 'Czech', 'buddyboss-platform' ),
		'da'     => esc_html__( 'Danish', 'buddyboss-platform' ),
		'nl'     => esc_html__( 'Dutch', 'buddyboss-platform' ),
		'en-GB'  => esc_html__( 'English (UK)', 'buddyboss-platform' ),
		'en'     => esc_html__( 'English (US)', 'buddyboss-platform' ),
		'et'     => esc_html__( 'Estonian', 'buddyboss-platform' ),
		'fil'    => esc_html__( 'Filipino', 'buddyboss-platform' ),
		'fi'     => esc_html__( 'Finnish', 'buddyboss-platform' ),
		'fr'     => esc_html__( 'French', 'buddyboss-platform' ),
		'fr-CA'  => esc_html__( 'French (Canadian)', 'buddyboss-platform' ),
		'gl'     => esc_html__( 'Galician', 'buddyboss-platform' ),
		'ka'     => esc_html__( 'Georgian', 'buddyboss-platform' ),
		'de'     => esc_html__( 'German', 'buddyboss-platform' ),
		'de-AT'  => esc_html__( 'German (Austria)', 'buddyboss-platform' ),
		'de-CH'  => esc_html__( 'German (Switzerland)', 'buddyboss-platform' ),
		'el'     => esc_html__( 'Greek', 'buddyboss-platform' ),
		'gu'     => esc_html__( 'Gujarati', 'buddyboss-platform' ),
		'iw'     => esc_html__( 'Hebrew', 'buddyboss-platform' ),
		'hi'     => esc_html__( 'Hindi', 'buddyboss-platform' ),
		'hu'     => esc_html__( 'Hungarain', 'buddyboss-platform' ),
		'is'     => esc_html__( 'Icelandic', 'buddyboss-platform' ),
		'id'     => esc_html__( 'Indonesian', 'buddyboss-platform' ),
		'it'     => esc_html__( 'Italian', 'buddyboss-platform' ),
		'ja'     => esc_html__( 'Japanese', 'buddyboss-platform' ),
		'kn'     => esc_html__( 'Kannada', 'buddyboss-platform' ),
		'ko'     => esc_html__( 'Korean', 'buddyboss-platform' ),
		'lo'     => esc_html__( 'Laothian', 'buddyboss-platform' ),
		'lv'     => esc_html__( 'Latvian', 'buddyboss-platform' ),
		'lt'     => esc_html__( 'Lithuanian', 'buddyboss-platform' ),
		'ms'     => esc_html__( 'Malay', 'buddyboss-platform' ),
		'ml'     => esc_html__( 'Malayalam', 'buddyboss-platform' ),
		'mr'     => esc_html__( 'Marathi', 'buddyboss-platform' ),
		'mn'     => esc_html__( 'Mongolian', 'buddyboss-platform' ),
		'no'     => esc_html__( 'Norwegian', 'buddyboss-platform' ),
		'fa'     => esc_html__( 'Persian', 'buddyboss-platform' ),
		'pl'     => esc_html__( 'Polish', 'buddyboss-platform' ),
		'pt'     => esc_html__( 'Portuguese', 'buddyboss-platform' ),
		'pt-BR'  => esc_html__( 'Portuguese (Brazil)', 'buddyboss-platform' ),
		'pt-PT'  => esc_html__( 'Portuguese (Portugal)', 'buddyboss-platform' ),
		'ro'     => esc_html__( 'Romanian', 'buddyboss-platform' ),
		'ru'     => esc_html__( 'Russian', 'buddyboss-platform' ),
		'sr'     => esc_html__( 'Serbian', 'buddyboss-platform' ),
		'si'     => esc_html__( 'Sinhalese', 'buddyboss-platform' ),
		'sk'     => esc_html__( 'Slovak', 'buddyboss-platform' ),
		'sl'     => esc_html__( 'Slovenian', 'buddyboss-platform' ),
		'es'     => esc_html__( 'Spanish', 'buddyboss-platform' ),
		'es-419' => esc_html__( 'Spanish (Latin America)', 'buddyboss-platform' ),
		'sw'     => esc_html__( 'Swahili', 'buddyboss-platform' ),
		'sv'     => esc_html__( 'Swedish', 'buddyboss-platform' ),
		'ta'     => esc_html__( 'Tamil', 'buddyboss-platform' ),
		'te'     => esc_html__( 'Telugu', 'buddyboss-platform' ),
		'th'     => esc_html__( 'Thai', 'buddyboss-platform' ),
		'tr'     => esc_html__( 'Turkish', 'buddyboss-platform' ),
		'uk'     => esc_html__( 'Ukrainian', 'buddyboss-platform' ),
		'ur'     => esc_html__( 'Urdu', 'buddyboss-platform' ),
		'vi'     => esc_html__( 'Vietnamese', 'buddyboss-platform' ),
		'zu'     => esc_html__( 'Zulu', 'buddyboss-platform' ),
	);

	/**
	 * Filters the list of supported reCAPTCHA languages.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param array $languages An associative array of supported reCAPTCHA languages where the keys are
	 *                         language codes and the values are the corresponding language names.
	 */
	return apply_filters( 'bb_recaptcha_languages', $languages );
}

/**
 * Retrieves the reCAPTCHA conflict mode.
 *
 * @since BuddyBoss 2.5.60
 *
 * @return bool The reCAPTCHA conflict mode.
 */
function bb_recaptcha_conflict_mode() {
	$conflict_mode = (bool) bb_recaptcha_setting( 'conflict_mode', false );

	/**
	 * Filters the reCAPTCHA conflict mode.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param bool $conflict_mode The reCAPTCHA conflict mode.
	 */
	return (bool) apply_filters( 'bb_recaptcha_conflict_mode', $conflict_mode );
}

/**
 * Retrieves the selected reCAPTCHA v2 theme.
 *
 * @since BuddyBoss 2.5.60
 *
 * @return string The selected v2 theme.
 */
function bb_recaptcha_v2_theme() {
	$recaptcha_v2_theme = bb_recaptcha_setting( 'theme', 'light' );

	/**
	 * Filters the selected reCAPTCHA v2 theme.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param string $recaptcha_v2_theme The selected reCAPTCHA v2 theme..
	 */
	return apply_filters( 'bb_recaptcha_v2_theme', $recaptcha_v2_theme );
}

/**
 * Retrieves the selected reCAPTCHA v2 size.
 *
 * @since BuddyBoss 2.5.60
 *
 * @return string The selected v2 size.
 */
function bb_recaptcha_v2_size() {
	$recaptcha_v2_size = bb_recaptcha_setting( 'size', 'normal' );

	/**
	 * Filters the selected reCAPTCHA v2 size.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param string $recaptcha_v2_size The selected reCAPTCHA v2 size.
	 */
	return apply_filters( 'bb_recaptcha_v2_size', $recaptcha_v2_size );
}

/**
 * Retrieves the selected reCAPTCHA v2 badge.
 *
 * @since BuddyBoss 2.5.60
 *
 * @return string The selected v2 badge.
 */
function bb_recaptcha_v2_badge() {
	$recaptcha_v2_badge = bb_recaptcha_setting( 'badge_position', 'bottomright' );

	/**
	 * Filters the selected reCAPTCHA v2 badge.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param string $recaptcha_v2_badge The selected reCAPTCHA v2 badge.
	 */
	return apply_filters( 'bb_recaptcha_v2_badge', $recaptcha_v2_badge );
}

/**
 * Retrieve the Google reCAPTCHA API response.
 * This function sends a request to the Google reCAPTCHA API to verify the provided token.
 *
 * @since BuddyBoss 2.5.60
 *
 * @param string $secret_key The secret key for the Google reCAPTCHA.
 * @param string $token      The token to be verified by the Google reCAPTCHA API.
 *
 * @return array|bool Returns an associative array containing the API response if successful,
 *                     or false if there's an error or if the API response is invalid.
 */
function bb_get_google_recaptcha_api_response( $secret_key, $token ) {
	$get_data = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $token );
	if ( empty( $get_data ) ) {
		return false;
	}

	$response_code = wp_remote_retrieve_response_code( $get_data );

	// Check if the status code is 429 (Resource Exhausted).
	if ( 429 === $response_code ) {
		return true;
	}

	return json_decode( wp_remote_retrieve_body( $get_data ), true );
}

/**
 * Display the reCAPTCHA widget based on the configured settings.
 * This function checks the connection status with the reCAPTCHA service and displays the appropriate reCAPTCHA widget
 * based on the enabled version (v2 or v3) and the configured actions.
 *
 * @since BuddyBoss 2.5.60
 *
 * @param string $action Current action ( i.e - bb_login, bb_register etc. ). Default will be blank.
 *
 * @return void
 */
function bb_recaptcha_display( $action = '' ) {

	if ( '' === $action ) {
		return;
	}

	$verified = bb_recaptcha_connection_status();
	if ( ! empty( $verified ) && 'connected' === $verified ) {
		$site_key    = bb_recaptcha_site_key();
		$enabled_for = bb_recaptcha_recaptcha_versions();
		$lang        = bb_recaptcha_setting( 'language_code', 'en' );

		if ( 'bb_login' === $action && bb_recaptcha_allow_bypass_enable() ) {
			$get_url_string = bb_filter_input_string( INPUT_GET, 'bypass_captcha' );
			if ( ! empty( $get_url_string ) ) {
				$admin_bypass_text = bb_recaptcha_setting( 'bypass_text' );
				if ( $get_url_string === $admin_bypass_text ) {
					$get_url_string = base64_encode( $get_url_string );
					?>
					<input type="hidden" id="bb_recaptcha_login_bypass_id" name="bb_recaptcha_login_bypass" value="<?php echo esc_html( $get_url_string ); ?>"/>
					<?php
					// If you have bypass url then don't display recaptcha.
					return;
				}
			}
		}

		// If ip address excluded to non validate captcha then don't display recaptcha.
		if ( bb_recaptcha_allow_ip() ) {
			return;
		}
		// Recaptcha api url.
		$api_url    = 'https://www.google.com/recaptcha/api.js';
		$query_args = array();
		if ( 'en' !== $lang ) {
			$query_args['hl'] = $lang;
		}

		if ( 'recaptcha_v3' === $enabled_for ) {
			?>
			<input type="hidden" id="bb_recaptcha_response_id" name="g-recaptcha-response"/>
			<?php
			$query_args['render'] = $site_key;
			$api_url              = add_query_arg( $query_args, $api_url );
		} elseif ( 'recaptcha_v2' === $enabled_for ) {
			$query_args['render'] = 'explicit';
			$api_url              = add_query_arg( $query_args, $api_url );
			$v2_option            = bb_recaptcha_recaptcha_v2_option();
			$v2_class             = '';
			if ( 'v2_invisible_badge' === $v2_option ) {
				$badge_position = bb_recaptcha_v2_badge();
				if ( 'inline' === $badge_position ) {
					$v2_class = 'v2_invisible_badge';
				}
			}
			?>
			<div id="bb_recaptcha_v2_element" class="bb_recaptcha_v2_element_content <?php echo esc_attr( $v2_class ); ?>" data-sitekey="<?php echo $site_key; ?>"></div>
			<?php
		}

		if ( ! wp_script_is( 'bb-recaptcha-api', 'registered' ) ) {
			if ( 'recaptcha_v3' === $enabled_for ) {
				wp_register_script( 'bb-recaptcha-api', $api_url, false, buddypress()->version, false );
			} elseif ( 'recaptcha_v2' === $enabled_for ) {
				wp_register_script( 'bb-recaptcha-api', $api_url, false, buddypress()->version, true );
			}
		}
		$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$rtl_css = is_rtl() ? '-rtl' : '';
		wp_enqueue_style( 'bb-recaptcha', bb_recaptcha_integration_url( '/assets/css/bb-recaptcha' . $rtl_css . $min . '.css' ), false, buddypress()->version );

		wp_register_script(
			'bb-recaptcha',
			bb_recaptcha_integration_url( '/assets/js/bb-recaptcha' . $min . '.js' ),
			array(
				'jquery',
				'bb-recaptcha-api',
			),
			buddypress()->version
		);

		if ( bb_recaptcha_conflict_mode() ) {
			bb_recaptcha_remove_duplicate_scripts();
		}

		$enabled_for   = bb_recaptcha_recaptcha_versions();
		$localize_data = array(
			'selected_version' => $enabled_for,
			'site_key'         => bb_recaptcha_site_key(),
			'action'           => $action,
		);
		if ( 'recaptcha_v2' === $enabled_for ) {
			$localize_data['v2_option']         = bb_recaptcha_recaptcha_v2_option();
			$localize_data['v2_theme']          = bb_recaptcha_v2_theme();
			$localize_data['v2_size']           = bb_recaptcha_v2_size();
			$localize_data['v2_badge_position'] = bb_recaptcha_v2_badge();
		}

		wp_localize_script( 'bb-recaptcha', 'bbRecaptcha', array( 'data' => $localize_data ) );
	}
}

/**
 * Perform reCAPTCHA verification on the front end.
 * This function checks the submitted reCAPTCHA token and verifies it with the Google reCAPTCHA API.
 * It handles verification for both reCAPTCHA v2 and reCAPTCHA v3 based on the selected version.
 *
 * @since BuddyBoss 2.5.60
 *
 * @param string $action Current action for recaptcha.
 *
 * @return bool|WP_Error Returns true if reCAPTCHA verification is successful,
 *                       or a WP_Error object if verification fails.
 */
function bb_recaptcha_verification_front( $action = '' ) {
	$selected_version = bb_recaptcha_recaptcha_versions();
	$secret_key       = bb_recaptcha_secret_key();
	$score_threshold  = bb_recaptcha_score_threshold();

	if ( bb_recaptcha_allow_ip() ) {
		return true;
	}

	if ( ! isset( $_POST['g-recaptcha-response'] ) ) {
		return true;
	}

	$token_response = bb_filter_input_string( INPUT_POST, 'g-recaptcha-response' );

	$retval = array();
	if ( ! empty( $selected_version ) ) {
		if ( empty( $token_response ) ) {
			$error_message = apply_filters( 'bb_recaptcha_token_missing', __( 'Google reCAPTCHA token is missing.', 'buddyboss-platform' ) );

			$retval['error']['bb_recaptcha_token_missing'] = $error_message;
		} else {
			$response = bb_get_google_recaptcha_api_response( $secret_key, $token_response );

			// Handle other reCAPTCHA verification responses.
			if ( $response ) {
				// Response success doesn't empty then it will true.

				// Response success empty then verification fails.
				if (
					empty( $response['success'] ) ||
					(
						// Check selected action and response action. Also check score for version 3.
						'recaptcha_v3' === $selected_version &&
						(
							(
								isset( $response['action'] ) &&
								$response['action'] !== $action
							) ||
							(
								1 !== (int) $score_threshold &&
								isset( $response['score'] ) &&
								$response['score'] < $score_threshold
							)
						)
					)
				) {
					$error_message = apply_filters( 'bb_recaptcha_verification_failed', __( 'Verification failed please try again.', 'buddyboss-platform' ) );

					$retval['error']['bb_recaptcha_verification_failed'] = $error_message;
				}
			} else {
				$error_message = apply_filters( 'bb_recaptcha_empty_response', __( 'Could not get a response from the reCAPTCHA server.', 'buddyboss-platform' ) );

				$retval['error']['bb_recaptcha_empty_response'] = $error_message;
			}
		}
	}

	if ( ! empty( $retval['error'] ) ) {
		return new WP_Error(
			key( $retval['error'] ),
			current( $retval['error'] )
		);
	}

	return true;
}

/**
 * Check if the current user's IP is allowed to bypass reCAPTCHA verification.
 * This function checks if the current user's IP address is included in the list of allowed IPs
 * specified in the plugin settings. If the current IP is found in the list, reCAPTCHA verification
 * is bypassed for the user.
 *
 * @since BuddyBoss 2.5.60
 *
 * @return bool Returns true if the current user's IP is allowed to bypass reCAPTCHA verification, otherwise false.
 */
function bb_recaptcha_allow_ip() {
	$get_allowed_ip = bb_recaptcha_setting( 'exclude_ip' );
	if ( ! empty( $get_allowed_ip ) ) {
		$allowed_ips = explode( PHP_EOL, $get_allowed_ip );
		$current_ip  = bb_recaptcha_get_current_ip();
		if ( in_array( $current_ip, $allowed_ips, true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Get the current user's IP address.
 * This function retrieves the IP address of the current user by checking various HTTP headers
 * commonly used to forward client IP addresses. It prioritizes headers that are most reliable for
 * determining the actual client IP address. The function also validates the IP address to ensure
 * it is not a private or reserved IP address.
 *
 * @since BuddyBoss 2.5.60
 * @return string|false The current user's IP address if successfully retrieved and validated,
 *                      or false if the IP address cannot be determined or is invalid.
 */
function bb_recaptcha_get_current_ip() {
	$current_ip = false;
	if ( isset( $_SERVER ) ) {
		// In order of preference, with the best ones for this purpose first.
		$address_headers = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $address_headers as $header ) {
			if ( array_key_exists( $header, $_SERVER ) ) {
				$address_chain = explode( ',', $_SERVER[ $header ] );
				$current_ip    = trim( $address_chain[0] );

				if ( filter_var( $current_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
					return $current_ip;
				}
			}
		}
	}

	if ( ! $current_ip ) {
		return false;
	}

	$anon_ip = wp_privacy_anonymize_ip( $current_ip, true );

	if ( '0.0.0.0' === $anon_ip || '::' === $anon_ip ) {
		return false;
	}

	return $anon_ip;
}

/**
 * Removes duplicate reCAPTCHA scripts from the other plugins or WordPress.
 *
 * @since BuddyBoss 2.5.60
 *
 * @global WP_Scripts $wp_scripts WordPress script queue object.
 *
 * @return bool|void
 */
function bb_recaptcha_remove_duplicate_scripts() {
	global $wp_scripts;

	if ( ! is_object( $wp_scripts ) || empty( $wp_scripts ) ) {
		return false;
	}

	$urls = array( 'google.com/recaptcha', 'gstatic.com/recaptcha' );
	foreach ( $wp_scripts->queue as $handle ) {
		foreach ( $urls as $url ) {
			if (
				false !== strpos( $wp_scripts->registered[ $handle ]->src, $url ) &&
				'bb-recaptcha-api' !== $handle
			) {
				wp_dequeue_script( $handle );
				wp_deregister_script( $handle );
				break;
			}
		}
	}
}
