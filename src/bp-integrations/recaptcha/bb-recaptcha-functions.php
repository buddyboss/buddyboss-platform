<?php
/**
 * Recaptcha integration helpers
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Recaptcha
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns Recaptcha Integration url.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $path Path to recaptcha integration.
 */
function bb_recaptcha_integration_url( $path = '' ) {
	return trailingslashit( buddypress()->integration_url ) . 'recaptcha/' . trim( $path, '/\\' );
}

function bb_recaptcha_score_threshold( $default = 6 ) {
	return (int) apply_filters( 'bb_recaptcha_score_threshold', bp_get_option( 'bb_recaptcha_score_threshold', $default ) );
}

function bb_recaptcha_options() {
	return apply_filters( 'bb_recaptcha_options', bp_get_option( 'bb_recaptcha', array() ) );
}

function bb_recaptcha_setting( $key, $default = '' ) {
	$settings = bb_recaptcha_options();
	$retval   = $default;
	if ( isset( $settings[ $key ] ) ) {
		$retval = $settings[ $key ];
	}

	/**
	 * Filters TutorLMS get settings.
	 *
	 * @since 2.4.40
	 *
	 * @param array  $retval  Settings of tutorlms.
	 * @param string $key     Optional. Get setting by key.
	 * @param string $default Optional. Default value if value or setting not available.
	 */
	return apply_filters( 'bb_recaptcha_setting', $retval, $key, $default );
}

function bb_recaptcha_actions() {
	$actions = array(
		'bb_login'         => array(
			'label'    => __( 'Login', 'buddyboss' ),
			'disabled' => false,
			'enabled'  => bb_recaptcha_is_enabled( 'bb_login' ),
		),
		'bb_register'      => array(
			'label'    => __( 'Registration', 'buddyboss' ),
			'disabled' => ! bp_enable_site_registration(),
			'enabled'  => bb_recaptcha_is_enabled( 'bb_register' ),
		),
		'bb_lost_password' => array(
			'label'    => __( 'Reset Password', 'buddyboss' ),
			'disabled' => false,
			'enabled'  => bb_recaptcha_is_enabled( 'bb_lost_password' ),
		),
		'bb_activate'      => array(
			'label'    => __( 'Account Activation', 'buddyboss' ),
			'disabled' => ! bp_enable_site_registration(),
			'enabled'  => bb_recaptcha_is_enabled( 'bb_activate' ),
		),
	);

	return apply_filters( 'bb_recaptcha_actions', $actions );
}


function bb_recaptcha_is_enabled( $key ) {
	$enabled_keys = bb_recaptcha_setting( 'enabled_for', array() );
	$retval = ! empty( $key ) && array_key_exists( $key, $enabled_keys ) && ! empty( $enabled_keys[ $key ] );

	return (bool) apply_filters( 'bb_recaptcha_is_enabled', $retval, $key );
}

function bb_recaptcha_languages() {
	$languages = array(
		'ar'     => esc_html__( 'Arabic', 'buddyboss' ),
		'af'     => esc_html__( 'Afrikaans', 'buddyboss' ),
		'am'     => esc_html__( 'Amharic', 'buddyboss' ),
		'hy'     => esc_html__( 'Armenian', 'buddyboss' ),
		'az'     => esc_html__( 'Azerbaijani', 'buddyboss' ),
		'eu'     => esc_html__( 'Basque', 'buddyboss' ),
		'bn'     => esc_html__( 'Bengali', 'buddyboss' ),
		'bg'     => esc_html__( 'Bulgarian', 'buddyboss' ),
		'ca'     => esc_html__( 'Catalan', 'buddyboss' ),
		'zh-HK'  => esc_html__( 'Chinese (Hong Kong)', 'buddyboss' ),
		'zh-CN'  => esc_html__( 'Chinese (Simplified)', 'buddyboss' ),
		'zh-TW'  => esc_html__( 'Chinese (Traditional)', 'buddyboss' ),
		'hr'     => esc_html__( 'Croatian', 'buddyboss' ),
		'cs'     => esc_html__( 'Czech', 'buddyboss' ),
		'da'     => esc_html__( 'Danish', 'buddyboss' ),
		'nl'     => esc_html__( 'Dutch', 'buddyboss' ),
		'en-GB'  => esc_html__( 'English (UK)', 'buddyboss' ),
		'en'     => esc_html__( 'English (US)', 'buddyboss' ),
		'et'     => esc_html__( 'Estonian', 'buddyboss' ),
		'fil'    => esc_html__( 'Filipino', 'buddyboss' ),
		'fi'     => esc_html__( 'Finnish', 'buddyboss' ),
		'fr'     => esc_html__( 'French', 'buddyboss' ),
		'fr-CA'  => esc_html__( 'French (Canadian)', 'buddyboss' ),
		'gl'     => esc_html__( 'Galician', 'buddyboss' ),
		'ka'     => esc_html__( 'Georgian', 'buddyboss' ),
		'de'     => esc_html__( 'German', 'buddyboss' ),
		'de-AT'  => esc_html__( 'German (Austria)', 'buddyboss' ),
		'de-CH'  => esc_html__( 'German (Switzerland)', 'buddyboss' ),
		'el'     => esc_html__( 'Greek', 'buddyboss' ),
		'gu'     => esc_html__( 'Gujarati', 'buddyboss' ),
		'iw'     => esc_html__( 'Hebrew', 'buddyboss' ),
		'hi'     => esc_html__( 'Hindi', 'buddyboss' ),
		'hu'     => esc_html__( 'Hungarain', 'buddyboss' ),
		'is'     => esc_html__( 'Icelandic', 'buddyboss' ),
		'id'     => esc_html__( 'Indonesian', 'buddyboss' ),
		'it'     => esc_html__( 'Italian', 'buddyboss' ),
		'ja'     => esc_html__( 'Japanese', 'buddyboss' ),
		'kn'     => esc_html__( 'Kannada', 'buddyboss' ),
		'ko'     => esc_html__( 'Korean', 'buddyboss' ),
		'lo'     => esc_html__( 'Laothian', 'buddyboss' ),
		'lv'     => esc_html__( 'Latvian', 'buddyboss' ),
		'lt'     => esc_html__( 'Lithuanian', 'buddyboss' ),
		'ms'     => esc_html__( 'Malay', 'buddyboss' ),
		'ml'     => esc_html__( 'Malayalam', 'buddyboss' ),
		'mr'     => esc_html__( 'Marathi', 'buddyboss' ),
		'mn'     => esc_html__( 'Mongolian', 'buddyboss' ),
		'no'     => esc_html__( 'Norwegian', 'buddyboss' ),
		'fa'     => esc_html__( 'Persian', 'buddyboss' ),
		'pl'     => esc_html__( 'Polish', 'buddyboss' ),
		'pt'     => esc_html__( 'Portuguese', 'buddyboss' ),
		'pt-BR'  => esc_html__( 'Portuguese (Brazil)', 'buddyboss' ),
		'pt-PT'  => esc_html__( 'Portuguese (Portugal)', 'buddyboss' ),
		'ro'     => esc_html__( 'Romanian', 'buddyboss' ),
		'ru'     => esc_html__( 'Russian', 'buddyboss' ),
		'sr'     => esc_html__( 'Serbian', 'buddyboss' ),
		'si'     => esc_html__( 'Sinhalese', 'buddyboss' ),
		'sk'     => esc_html__( 'Slovak', 'buddyboss' ),
		'sl'     => esc_html__( 'Slovenian', 'buddyboss' ),
		'es'     => esc_html__( 'Spanish', 'buddyboss' ),
		'es-419' => esc_html__( 'Spanish (Latin America)', 'buddyboss' ),
		'sw'     => esc_html__( 'Swahili', 'buddyboss' ),
		'sv'     => esc_html__( 'Swedish', 'buddyboss' ),
		'ta'     => esc_html__( 'Tamil', 'buddyboss' ),
		'te'     => esc_html__( 'Telugu', 'buddyboss' ),
		'th'     => esc_html__( 'Thai', 'buddyboss' ),
		'tr'     => esc_html__( 'Turkish', 'buddyboss' ),
		'uk'     => esc_html__( 'Ukrainian', 'buddyboss' ),
		'ur'     => esc_html__( 'Urdu', 'buddyboss' ),
		'vi'     => esc_html__( 'Vietnamese', 'buddyboss' ),
		'zu'     => esc_html__( 'Zulu', 'buddyboss' ),
	);

	return apply_filters( 'bb_recaptcha_languages', $languages );
}

/**
 * Retrieves the selected reCAPTCHA version.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string The selected reCAPTCHA version.
 */
function bb_recaptcha_recaptcha_versions() {
	$recaptcha_version = bb_recaptcha_setting( 'recaptcha_version', 'recaptcha_v3' );

	/**
	 * Filters the selected reCAPTCHA version.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $recaptcha_version The selected reCAPTCHA version.
	 *
	 * @return string The filtered reCAPTCHA version.
	 */
	return apply_filters( 'bb_recaptcha_recaptcha_versions', $recaptcha_version );
}

/**
 * Retrieves the selected reCAPTCHA v2 option.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string The selected v2 option.
 */
function bb_recaptcha_recaptcha_v2_option() {
	$recaptcha_v2_option = bb_recaptcha_setting( 'v2_option', 'v2_checkbox' );

	/**
	 * Filters the selected reCAPTCHA v2 option.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $recaptcha_v2_option The selected reCAPTCHA v2 option.
	 *
	 * @return string The filtered reCAPTCHA v2 option.
	 */
	return apply_filters( 'bb_recaptcha_recaptcha_v2_option', $recaptcha_v2_option );
}

/**
 * Retrieves the selected reCAPTCHA v2 theme.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string The selected v2 theme.
 */
function bb_recaptcha_v2_theme() {
	$recaptcha_v2_theme = bb_recaptcha_setting( 'theme', 'light' );

	/**
	 * Filters the selected reCAPTCHA v2 theme.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $recaptcha_v2_theme The selected reCAPTCHA v2 theme.
	 *
	 * @return string The filtered reCAPTCHA v2 theme.
	 */
	return apply_filters( 'bb_recaptcha_v2_theme', $recaptcha_v2_theme );
}

/**
 * Retrieves the selected reCAPTCHA v2 size.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string The selected v2 size.
 */
function bb_recaptcha_v2_size() {
	$recaptcha_v2_size = bb_recaptcha_setting( 'size', 'normal' );

	/**
	 * Filters the selected reCAPTCHA v2 size.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $recaptcha_v2_size The selected reCAPTCHA v2 size.
	 *
	 * @return string The filtered reCAPTCHA v2 size.
	 */
	return apply_filters( 'bb_recaptcha_v2_size', $recaptcha_v2_size );
}

/**
 * Retrieves the selected reCAPTCHA v2 badge.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string The selected v2 badge.
 */
function bb_recaptcha_v2_badge() {
	$recaptcha_v2_badge = bb_recaptcha_setting( 'badge_position', 'bottomright' );

	/**
	 * Filters the selected reCAPTCHA v2 badge.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $recaptcha_v2_badge The selected reCAPTCHA v2 badge.
	 *
	 * @return string The filtered reCAPTCHA v2 badge.
	 */
	return apply_filters( 'bb_recaptcha_v2_badge', $recaptcha_v2_badge );
}

/**
 * Retrieves the reCAPTCHA site key.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string The reCAPTCHA site key.
 */
function bb_recaptcha_site_key() {
	$site_key = bb_recaptcha_setting( 'site_key', '' );

	/**
	 * Filters the reCAPTCHA site key.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $site_key The reCAPTCHA site key.
	 *
	 * @return string The filtered reCAPTCHA site key.
	 */
	return apply_filters( 'bb_recaptcha_site_key', $site_key );
}

/**
 * Retrieves the reCAPTCHA secret key.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string The reCAPTCHA secret key.
 */
function bb_recaptcha_secret_key() {
	$secret_key = bb_recaptcha_setting( 'secret_key', '' );

	/**
	 * Filters the reCAPTCHA secret key.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $secret_key The reCAPTCHA secret key.
	 *
	 * @return string The filtered reCAPTCHA secret key.
	 */
	return apply_filters( 'bb_recaptcha_secret_key', $secret_key );
}

/**
 * Retrieves the reCAPTCHA connection status.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string The reCAPTCHA connection status.
 */
function bb_recaptcha_connection_status() {
	$connection_status = bb_recaptcha_setting( 'connection_status', '' );

	/**
	 * Filters the reCAPTCHA connection status.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $secret_key The reCAPTCHA connection status.
	 *
	 * @return string The filtered reCAPTCHA connection status.
	 */
	return apply_filters( 'bb_recaptcha_connection_status', $connection_status );
}

/**
 * Retrieve the Google reCAPTCHA API response.
 * This function sends a request to the Google reCAPTCHA API to verify the provided token.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $secret_key The secret key for the Google reCAPTCHA.
 * @param string $token      The token to be verified by the Google reCAPTCHA API.
 *
 * @return array|false Returns an associative array containing the API response if successful,
 *                     or false if there's an error or if the API response is invalid.
 */
function bb_get_google_recaptcha_api_response( $secret_key, $token ) {
	$get_data = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $token );
	$response = json_decode( wp_remote_retrieve_body( $get_data ), true );

	return $response;
}

/**
 * Display the reCAPTCHA widget based on the configured settings.
 * This function checks the connection status with the reCAPTCHA service and displays the appropriate reCAPTCHA widget
 * based on the enabled version (v2 or v3) and the configured actions.
 *
 * @since BuddyBoss [BBVERSION]
 * @return void
 */
function bb_recaptcha_display() {
	$verified = bb_recaptcha_connection_status();
	if ( ! empty( $verified ) && 'connected' === $verified ) {
		$site_key    = bb_recaptcha_site_key();
		$enabled_for = bb_recaptcha_recaptcha_versions();
		$actions     = bb_recaptcha_actions();
		$lang        = bb_recaptcha_setting( 'language_code', 'en' );

		// Recaptcha api url.
		$api_url    = 'https://www.google.com/recaptcha/api.js';
		$query_args = array();
		if ( 'en' !== $lang ) {
			$query_args['hl'] = $lang;
		}
		if ( 'recaptcha_v3' === $enabled_for ) {
			?>
			<input type="hidden" id="bb_recaptcha_login_v3" name="g-recaptcha-response"/>
			<?php
			$query_args['render'] = $site_key;
			$api_url              = add_query_arg( $query_args, $api_url );
		} elseif ( 'recaptcha_v2' === $enabled_for ) {
			$query_args['render'] = 'explicit';
			$api_url              = add_query_arg( $query_args, $api_url );
			?>
			<div id="bb_recaptcha_login_v2" class="bb_recaptcha_login_v2_content" data-sitekey="<?php echo $site_key; ?>"></div>
			<?php
		}
		if ( ! wp_script_is( 'bb-recaptcha-api', 'registered' ) ) {
			if ( 'recaptcha_v3' === $enabled_for ) {
				wp_register_script( 'bb-recaptcha-api', $api_url, false, buddypress()->version, false );
			}
			if ( 'recaptcha_v2' === $enabled_for ) {
				wp_register_script( 'bb-recaptcha-api', $api_url, false, buddypress()->version, true );
			}
			add_action( 'wp_footer', 'bb_recaptcha_add_scripts' );
			if (
				$actions['bb_login']['enabled'] ||
				$actions['bb_register']['enabled'] ||
				$actions['bb_lost_password']['enabled']
			) {
				add_action( 'login_footer', 'bb_recaptcha_add_scripts_login_footer' );
			}
		}
		$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$rtl_css = is_rtl() ? '-rtl' : '';
		wp_enqueue_style( 'bb-recaptcha', bb_recaptcha_integration_url( '/assets/css/bb-recaptcha' . $rtl_css . $min . '.css' ), false, buddypress()->version );
	}
}

/**
 * Enqueue scripts and localize data for reCAPTCHA in the login footer.
 * This function enqueues the necessary JavaScript file for reCAPTCHA integration and localizes data
 * to be used by the JavaScript code. The localized data includes information about the selected reCAPTCHA version,
 * site key, and actions enabled for reCAPTCHA verification.
 * For reCAPTCHA v2, additional configuration options such as the theme, size, and badge position are also included.
 *
 * @since BuddyBoss [BBVERSION]
 * @return void
 */
function bb_recaptcha_add_scripts_login_footer() {
	$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	wp_enqueue_script(
		'bb-recaptcha',
		bb_recaptcha_integration_url( '/assets/js/bb-recaptcha' . $min . '.js' ),
		array(
			'jquery',
			'bb-recaptcha-api',
		),
		buddypress()->version
	);

	$enabled_for   = bb_recaptcha_recaptcha_versions();
	$localize_data = array(
		'selected_version' => $enabled_for,
		'site_key'         => bb_recaptcha_site_key(),
		'actions'          => bb_recaptcha_actions(),
	);
	if ( 'recaptcha_v2' === $enabled_for ) {
		$localize_data['v2_option']         = bb_recaptcha_recaptcha_v2_option();
		$localize_data['v2_theme']          = bb_recaptcha_v2_theme();
		$localize_data['v2_size']           = bb_recaptcha_v2_size();
		$localize_data['v2_badge_position'] = bb_recaptcha_v2_badge();
	}

	wp_localize_script( 'bb-recaptcha', 'bbRecaptcha', array( 'data' => $localize_data ) );
}

/**
 * Perform reCAPTCHA verification on the front end.
 * This function checks the submitted reCAPTCHA token and verifies it with the Google reCAPTCHA API.
 * It handles verification for both reCAPTCHA v2 and reCAPTCHA v3 based on the selected version.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return true|WP_Error Returns true if reCAPTCHA verification is successful,
 *                       or a WP_Error object if verification fails.
 */
function bb_recaptcha_verification_front() {
	$selected_version = bb_recaptcha_recaptcha_versions();
	$secret_key       = bb_recaptcha_secret_key();

	if ( bb_recaptcha_allow_ip() ) {
		return true;
	}

	$token_response = bb_filter_input_string( INPUT_POST, 'g-recaptcha-response' );

	if ( 'recaptcha_v3' === $selected_version ) {
		if ( empty( $token_response ) ) {
			return new WP_Error(
				'bb_recaptcha_v3_not_submitted',
				__( "<strong>ERROR</strong>: reCAPTCHA verification failed.<br />Please try again.", 'buddyboss' )
			);
		} else {
			$response = bb_get_google_recaptcha_api_response( $secret_key, $token_response );
			if ( ! empty( $response ) && $response['success'] ) {
				$score_threshold = bb_recaptcha_setting( 'score_threshold', 6 );
				if ( $response['score'] < $score_threshold ) {
					return new WP_Error(
						'bb_recaptcha_v3_failed',
						__( "<strong>ERROR</strong>: reCAPTCHA verification failed.<br />Please try again.", 'buddyboss' )
					);
				}
				return true;
			} else {
				return new WP_Error(
					'bb_recaptcha_v3_failed',
					__( "<strong>ERROR</strong>: reCAPTCHA verification failed.<br />Please try again.", 'buddyboss' )
				);
			}
		}
	}
	if ( 'recaptcha_v2' === $selected_version ) {
		if ( empty( $token_response ) ) {
			return new WP_Error(
				'bb_recaptcha_v2_not_submitted',
				__( "<strong>ERROR</strong>: reCAPTCHA verification failed.<br />Please try again.", 'buddyboss' )
			);
		} else {
			$response = bb_get_google_recaptcha_api_response( $secret_key, $token_response );
			if ( ! empty( $response ) && $response['success'] ) {
				return true;
			} else {
				return new WP_Error(
					'bb_recaptcha_v2_failed',
					__( "<strong>ERROR</strong>: reCAPTCHA verification failed.<br />Please try again.", 'buddyboss' )
				);
			}
		}
	}

	return true;
}

/**
 * Check if the current user's IP is allowed to bypass reCAPTCHA verification.
 * This function checks if the current user's IP address is included in the list of allowed IPs
 * specified in the plugin settings. If the current IP is found in the list, reCAPTCHA verification
 * is bypassed for the user.
 *
 * @since BuddyBoss [BBVERSION]
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
 * @since BuddyBoss [BBVERSION]
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
