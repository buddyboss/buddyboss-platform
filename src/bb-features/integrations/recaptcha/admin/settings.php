<?php
/**
 * BuddyBoss Admin Settings - reCAPTCHA Settings Registration.
 *
 * Registers side panels, sections, and fields for the reCAPTCHA
 * integration feature in the Feature Registry.
 *
 * @package BuddyBoss\Features\Integrations\Recaptcha
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Load callbacks file.
require_once __DIR__ . '/callbacks.php';

// =========================================================================
// SIDE PANEL
// =========================================================================

// Single side panel: reCAPTCHA Settings.
bb_register_side_panel(
	'recaptcha',
	'recaptcha_settings',
	array(
		'title'      => __( 'reCAPTCHA Settings', 'buddyboss' ),
		'icon'       => array(
			'type'  => 'font',
			'class' => 'bb-icons-rl bb-icons-rl-gear',
		),
		'help_url'   => bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => '127314',
				),
				'admin.php'
			)
		),
		'order'      => 10,
		'is_default' => true,
	)
);

// =========================================================================
// SECTION 1: reCAPTCHA (Connection)
// =========================================================================

$status      = 'warning';
$status_text = __( 'Not Connected', 'buddyboss' );
$verified    = bb_recaptcha_connection_status();
if ( ! empty( $verified ) && 'connected' === $verified ) {
	$status      = 'success';
	$status_text = __( 'Connected', 'buddyboss' );
}

bb_register_feature_section(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_connection',
	array(
		'title'       => __( 'reCAPTCHA', 'buddyboss' ),
		'description' => sprintf(
			/* translators: %s: reCAPTCHA API keys link. */
			__( 'Connect Google reCAPTCHA to protect your website from fraud, spam, and abuse. Enter your %s to connect your site.', 'buddyboss' ),
			'<a href="https://www.google.com/recaptcha/admin" target="_blank">' . esc_html__( 'reCAPTCHA API keys', 'buddyboss' ) . '</a>'
		),
		'order'       => 10,
		'status'      => array(
			'type' => $status,
			'text' => $status_text,
		),
	)
);

// Prepare combined version options.
$recaptcha_settings = bb_recaptcha_options();
$current_version    = bb_recaptcha_admin_get_combined_version( $recaptcha_settings );

// FIELD: Versions (combined select).
bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_connection',
	array(
		'name'              => 'bb_recaptcha_version',
		'label'             => __( 'Versions', 'buddyboss' ),
		'type'              => 'select',
		'options'           => array(
			array(
				'label' => __( 'reCAPTCHA v3 (Recommended)', 'buddyboss' ),
				'value' => 'recaptcha_v3',
			),
			array(
				'label' => __( 'reCAPTCHA v2 (Checkbox)', 'buddyboss' ),
				'value' => 'recaptcha_v2_checkbox',
			),
			array(
				'label' => __( 'reCAPTCHA v2 (Invisible Badge)', 'buddyboss' ),
				'value' => 'recaptcha_v2_invisible',
			),
		),
		'default'           => $current_version,
		'description'       => bb_recaptcha_admin_get_version_description( $current_version ),
		'sanitize_callback' => 'bb_recaptcha_sanitize_version',
		'order'             => 10,
	)
);

// FIELD: Site Key.
bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_connection',
	array(
		'name'              => 'bb_recaptcha_site_key',
		'label'             => __( 'Site Key', 'buddyboss' ),
		'type'              => 'password',
		'default'           => bb_recaptcha_site_key(),
		'placeholder'       => '',
		'sanitize_callback' => 'sanitize_text_field',
		'order'             => 20,
	)
);

// FIELD: Secret Key.
bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_connection',
	array(
		'name'              => 'bb_recaptcha_secret_key',
		'label'             => __( 'Secret Key', 'buddyboss' ),
		'type'              => 'password',
		'default'           => bb_recaptcha_secret_key(),
		'placeholder'       => '',
		'sanitize_callback' => 'sanitize_text_field',
		'order'             => 30,
	)
);

// FIELD: Verify button.
bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_connection',
	array(
		'name'              => 'bb_recaptcha_verify',
		'label'             => '',
		'type'              => 'recaptcha_verify',
		'is_connected'      => ( 'connected' === $verified ),
		'default'           => '',
		'sanitize_callback' => '__return_empty_string',
		'order'             => 40,
	)
);

// FIELD: Admin Console notice.
bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_connection',
	array(
		'name'              => 'bb_recaptcha_admin_console_notice',
		'label'             => '',
		'type'              => 'notice',
		'notice_type'       => 'info',
		'description'       => sprintf(
			/* translators: %s: Admin Console link. */
			__( 'Check reCAPTCHA %s for usage statistics and monitor its performance. Adjust settings if necessary to maintain security.', 'buddyboss' ),
			'<a href="https://www.google.com/recaptcha/admin" target="_blank">' . esc_html__( 'Admin Console', 'buddyboss' ) . '</a>'
		),
		'sanitize_callback' => '__return_empty_string',
		'order'             => 50,
	)
);

// =========================================================================
// SECTION 2: reCAPTCHA Settings
// =========================================================================

bb_register_feature_section(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_general',
	array(
		'title'       => __( 'reCAPTCHA Settings', 'buddyboss' ),
		'description' => '',
		'order'       => 20,
	)
);

// FIELD: Score Threshold (v3 only).
bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_general',
	array(
		'name'              => 'bb_recaptcha_score_threshold',
		'label'             => __( 'Score Threshold', 'buddyboss' ),
		'type'              => 'number',
		'default'           => (string) bb_recaptcha_score_threshold(),
		'description'       => __( 'reCAPTCHA v3 provides a score for every request seamlessly, without causing user friction. Input a risk score between 0.0 and 1.0 in the field above to evaluate the probability of being identified as a bot.', 'buddyboss' ),
		'sanitize_callback' => 'bb_recaptcha_sanitize_score_threshold',
		'order'             => 10,
		'step'              => '0.1',
		'min'               => '0',
		'max'               => '1',
		'conditional'       => array(
			'field' => 'bb_recaptcha_version',
			'value' => 'recaptcha_v3',
		),
	)
);

// FIELD: Enabled For (toggle list).
$actions         = bb_recaptcha_actions();
$enabled_options = array();
$enabled_default = array();
foreach ( $actions as $action_key => $action_data ) {
	$enabled_options[] = array(
		'label'    => $action_data['label'],
		'value'    => $action_key,
		'disabled' => $action_data['disabled'],
	);
	$enabled_default[ $action_key ] = $action_data['enabled'] ? 1 : 0;
}

bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_general',
	array(
		'name'              => 'bb_recaptcha_enabled_for',
		'label'             => __( 'Enabled for', 'buddyboss' ),
		'type'              => 'toggle_list',
		'options'           => $enabled_options,
		'default'           => $enabled_default,
		'description'       => sprintf(
			/* translators: %s: Enable Registration link. */
			__( 'Select the pages to include in the reCAPTCHA submission. Make sure to %s if both registration and account activation are disabled.', 'buddyboss' ),
			'<a href="' . esc_url( admin_url( 'admin.php?page=bp-pages' ) ) . '">' . esc_html__( 'Enable Registration', 'buddyboss' ) . '</a>'
		),
		'sanitize_callback' => 'bb_recaptcha_sanitize_enabled_for',
		'order'             => 20,
	)
);

// FIELD: Allow Bypass (checkbox + text input + copy URL).
$allow_bypass = bb_recaptcha_setting( 'allow_bypass', false );
$bypass_text  = bb_recaptcha_setting( 'bypass_text', '' );

bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_general',
	array(
		'name'              => 'bb_recaptcha_allow_bypass',
		'label'             => '',
		'type'              => 'recaptcha_bypass',
		'default'           => $allow_bypass ? 1 : 0,
		'sanitize_callback' => 'absint',
		'order'             => 30,
		'bypass_text'       => $bypass_text,
	)
);

// Hidden field for bypass text (saved alongside the checkbox).
bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_general',
	array(
		'name'              => 'bb_recaptcha_bypass_text',
		'label'             => '',
		'type'              => 'hidden',
		'default'           => $bypass_text,
		'sanitize_callback' => 'bb_recaptcha_sanitize_bypass_text',
		'order'             => 31,
	)
);

// FIELD: Language Code.
$languages       = bb_recaptcha_languages();
$language_options = array();
foreach ( $languages as $code => $label ) {
	$language_options[] = array(
		'label' => $label,
		'value' => $code,
	);
}

bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_general',
	array(
		'name'              => 'bb_recaptcha_language_code',
		'label'             => __( 'Language Code', 'buddyboss' ),
		'type'              => 'select',
		'options'           => $language_options,
		'default'           => bb_recaptcha_setting( 'language_code', 'en' ),
		'description'       => __( 'Select a language for reCAPTCHA when it is displayed.', 'buddyboss' ),
		'sanitize_callback' => 'sanitize_text_field',
		'order'             => 40,
	)
);

// FIELD: No-Conflict Mode.
bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_general',
	array(
		'name'              => 'bb_recaptcha_conflict_mode',
		'label'             => __( 'No-Conflict Mode', 'buddyboss' ),
		'type'              => 'toggle',
		'default'           => bb_recaptcha_conflict_mode() ? 1 : 0,
		'description'       => __( 'Allow no-conflict mode to prevent compatibility conflicts', 'buddyboss' ),
		'help_text'         => __( 'When checked, other instances of reCAPTCHA are forcefully removed to prevent conflicts. Only enable this option if your site is experiencing compatibility issues or if instructed to do so by support.', 'buddyboss' ),
		'sanitize_callback' => 'absint',
		'order'             => 50,
	)
);

// FIELD: Exclude by IP.
bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_general',
	array(
		'name'              => 'bb_recaptcha_exclude_ip',
		'label'             => __( 'Exclude by IP', 'buddyboss' ),
		'type'              => 'textarea',
		'default'           => bb_recaptcha_setting( 'exclude_ip', '' ),
		'placeholder'       => __( 'Enter IP addresses', 'buddyboss' ),
		'description'       => __( 'Enter the IP addresses that you want to skip from captcha submission. Enter one IP per line.', 'buddyboss' ),
		'sanitize_callback' => 'sanitize_textarea_field',
		'order'             => 60,
	)
);

// Admin Console notice for settings section.
bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_general',
	array(
		'name'              => 'bb_recaptcha_settings_console_notice',
		'label'             => '',
		'type'              => 'notice',
		'notice_type'       => 'info',
		'description'       => sprintf(
			/* translators: %s: Admin Console link. */
			__( 'Check reCAPTCHA %s for usage statistics and monitor its performance. Adjust settings if necessary to maintain security.', 'buddyboss' ),
			'<a href="https://www.google.com/recaptcha/admin" target="_blank">' . esc_html__( 'Admin Console', 'buddyboss' ) . '</a>'
		),
		'sanitize_callback' => '__return_empty_string',
		'order'             => 70,
	)
);

// =========================================================================
// SECTION 3: reCAPTCHA Design (v2 only)
// =========================================================================

bb_register_feature_section(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_design',
	array(
		'title'       => __( 'reCAPTCHA Design', 'buddyboss' ),
		'description' => '',
		'order'       => 30,
		'conditional' => array(
			'field'    => 'bb_recaptcha_version',
			'value'    => 'recaptcha_v3',
			'operator' => '!=',
		),
	)
);

// FIELD: Theme (v2 checkbox only).
bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_design',
	array(
		'name'              => 'bb_recaptcha_theme',
		'label'             => __( 'Theme', 'buddyboss' ),
		'type'              => 'image_radio',
		'options'           => array(
			array(
				'label' => __( 'Light', 'buddyboss' ),
				'value' => 'light',
				'image' => bb_recaptcha_integration_url( 'assets/images/reCAPTCHA-light.png' ),
			),
			array(
				'label' => __( 'Dark', 'buddyboss' ),
				'value' => 'dark',
				'image' => bb_recaptcha_integration_url( 'assets/images/reCAPTCHA-dark.png' ),
			),
		),
		'default'           => bb_recaptcha_v2_theme(),
		'sanitize_callback' => 'bb_recaptcha_sanitize_theme',
		'order'             => 10,
		'conditional'       => array(
			'field' => 'bb_recaptcha_version',
			'value' => 'recaptcha_v2_checkbox',
		),
	)
);

// FIELD: Size (v2 checkbox only).
bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_design',
	array(
		'name'              => 'bb_recaptcha_size',
		'label'             => __( 'Size', 'buddyboss' ),
		'type'              => 'image_radio',
		'options'           => array(
			array(
				'label' => __( 'Normal', 'buddyboss' ),
				'value' => 'normal',
				'image' => bb_recaptcha_integration_url( 'assets/images/reCAPTCHA-light.png' ),
			),
			array(
				'label' => __( 'Compact', 'buddyboss' ),
				'value' => 'compact',
				'image' => bb_recaptcha_integration_url( 'assets/images/reCAPTCHA-light-compact.png' ),
			),
		),
		'default'           => bb_recaptcha_v2_size(),
		'sanitize_callback' => 'bb_recaptcha_sanitize_size',
		'order'             => 20,
		'conditional'       => array(
			'field' => 'bb_recaptcha_version',
			'value' => 'recaptcha_v2_checkbox',
		),
	)
);

// FIELD: Badge Position (v2 invisible only).
bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_design',
	array(
		'name'              => 'bb_recaptcha_badge_position',
		'label'             => __( 'Badge Position', 'buddyboss' ),
		'type'              => 'image_radio',
		'options'           => array(
			array(
				'label' => __( 'Bottom Right', 'buddyboss' ),
				'value' => 'bottomright',
				'image' => bb_recaptcha_integration_url( 'assets/images/reCAPTCHA-bottom-right.png' ),
			),
			array(
				'label' => __( 'Bottom Left', 'buddyboss' ),
				'value' => 'bottomleft',
				'image' => bb_recaptcha_integration_url( 'assets/images/reCAPTCHA-bottom-left.png' ),
			),
			array(
				'label' => __( 'Inline', 'buddyboss' ),
				'value' => 'inline',
				'image' => bb_recaptcha_integration_url( 'assets/images/reCAPTCHA-inline.png' ),
			),
		),
		'default'           => bb_recaptcha_v2_badge(),
		'sanitize_callback' => 'bb_recaptcha_sanitize_badge_position',
		'order'             => 30,
		'conditional'       => array(
			'field' => 'bb_recaptcha_version',
			'value' => 'recaptcha_v2_invisible',
		),
	)
);

/**
 * Fires after all reCAPTCHA settings panels are registered.
 * Allows third-party extensions to add more panels or fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
do_action( 'bb_recaptcha_after_register_settings_fields' );
