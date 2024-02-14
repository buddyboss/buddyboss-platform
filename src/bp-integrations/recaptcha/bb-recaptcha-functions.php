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
