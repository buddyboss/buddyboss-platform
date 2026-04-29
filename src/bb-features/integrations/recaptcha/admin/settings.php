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

// Per-option descriptions for version select (dynamic swap in React via option_descriptions).
$version_descriptions = bb_recaptcha_admin_get_version_description();

// FIELD: Versions (combined select).
bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_connection',
	array(
		'name'                => 'bb_recaptcha_version',
		'label'               => __( 'Versions', 'buddyboss' ),
		'type'                => 'select',
		'options'             => array(
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
		'default'             => $current_version,
		'description'         => $version_descriptions[ $current_version ] ?? $version_descriptions['recaptcha_v3'],
		'option_descriptions' => $version_descriptions,
		'sanitize_callback'   => 'bb_recaptcha_sanitize_version',
		'order'               => 10,
	)
);

// FIELD: API Keys (parent label for Site Key, Secret Key, and Verify).
bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_connection',
	array(
		'name'              => 'bb_recaptcha_api_keys',
		'label'             => __( 'API Keys', 'buddyboss' ),
		'type'              => 'hidden',
		'default'           => '',
		'sanitize_callback' => '__return_empty_string',
		'order'             => 20,
	)
);

// FIELD: Site Key (child of API Keys).
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
		'order'             => 21,
		'parent_field'      => 'bb_recaptcha_api_keys',
	)
);

// FIELD: Secret Key (child of API Keys).
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
		'order'             => 22,
		'parent_field'      => 'bb_recaptcha_api_keys',
	)
);

// FIELD: Verify button (child of API Keys).
bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_connection',
	array(
		'name'              => 'bb_recaptcha_verify',
		'label'             => '',
		'type'              => 'bb_verify_popup',
		'button_label'      => __( 'Verify', 'buddyboss' ),
		'ajax_action'       => 'bb_recaptcha_verify_settings_2',
		// API Keys are credential fields — persisted by the verify AJAX
		// handler, never by per-field auto-save. Version is intentionally
		// NOT a related field: it auto-saves on its own (legacy parity)
		// and is forwarded to the verify request via the
		// bb_admin_verify_field_before_ajax hook in recaptcha-verify-hooks.js.
		'related_fields'    => array(
			'bb_recaptcha_site_key',
			'bb_recaptcha_secret_key',
		),
		'is_connected'      => ( 'connected' === $verified ),
		'verify_config'     => array(
			'modal_title'     => __( 'Verify reCAPTCHA', 'buddyboss' ),
			'loading_message' => __( 'Verifying reCAPTCHA token', 'buddyboss' ),
		),
		'default'           => '',
		'sanitize_callback' => '__return_empty_string',
		'order'             => 23,
		'parent_field'      => 'bb_recaptcha_api_keys',
	)
);

// FIELD: Admin Console notice (plain description text per Figma).
bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_connection',
	array(
		'name'              => 'bb_recaptcha_admin_console_notice',
		'label'             => '',
		'type'              => 'notice',
		'notice_type'       => 'plain',
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
		// Disable the entire section while reCAPTCHA is not connected.
		// Driven by the hidden tracking field below; the verify AJAX flips
		// its value on connect/disconnect via the updated_fields response.
		'conditional' => array(
			'field'  => '_bb_recaptcha_is_connected',
			'value'  => '1',
			'action' => 'disable',
		),
	)
);

// Virtual field that tracks the connection state. Cannot key off
// site_key/secret_key alone because typing into those fields would flip
// the conditional before verification actually completes.
bb_register_feature_field(
	'recaptcha',
	'recaptcha_settings',
	'recaptcha_general',
	array(
		'name'              => '_bb_recaptcha_is_connected',
		'label'             => '',
		'type'              => 'hidden',
		'default'           => ( 'connected' === $verified ) ? '1' : '0',
		'sanitize_callback' => '__return_empty_string',
		'order'             => 1,
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
			'<a href="' . esc_url( admin_url( 'admin.php?page=bb-settings&tab=appearance&panel=pages' ) ) . '">' . esc_html__( 'Enable Registration', 'buddyboss' ) . '</a>'
		),
		'sanitize_callback' => 'bb_recaptcha_sanitize_enabled_for',
		'order'             => 20,
		// Group with bb_recaptcha_allow_bypass below so the divider between
		// them disappears (matches Figma — they read as one block).
		'group'             => array(
			'key' => 'recaptcha_enabled_group',
		),
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
		// Bypass URL is meaningful only when reCAPTCHA protects the Login
		// flow. Matches legacy: the row was rendered with 'bp-hide' when
		// bb_login was not in `enabled_for`, and bb_recaptcha_allow_bypass_enable()
		// returns false in the same case. Reads the toggle_list item via
		// dot-notation in the shared conditional util.
		'conditional'       => array(
			'field' => 'bb_recaptcha_enabled_for.bb_login',
			'value' => '1',
		),
		// Group with bb_recaptcha_enabled_for above so the divider between
		// them disappears (matches Figma — they read as one block).
		'group'             => array(
			'key' => 'recaptcha_enabled_group',
		),
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
		// Section is enabled only when (a) version is v2 (any flavor) AND
		// (b) reCAPTCHA is connected. The conditional system supports a
		// single action per rule, so hide-when-v3 + disable-when-not-
		// connected can't be expressed independently — both are folded
		// into a single 'disable' rule. Field-level conditionals on the
		// individual design fields keep them hidden when their specific v2
		// flavor isn't selected, so for v3 only the section header shows.
		'conditional' => array(
			'action'     => 'disable',
			'operator'   => 'AND',
			'conditions' => array(
				array(
					'field'    => 'bb_recaptcha_version',
					'value'    => 'recaptcha_v3',
					'operator' => '!=',
				),
				array(
					'field' => '_bb_recaptcha_is_connected',
					'value' => '1',
				),
			),
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
