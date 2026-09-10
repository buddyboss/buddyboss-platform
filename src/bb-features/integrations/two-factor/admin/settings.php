<?php
/**
 * Two-Factor integration admin settings registration.
 *
 * Registers the side panel, sections and fields in the Feature Registry. Required
 * from bb-feature-config.php in admin, AJAX, REST and CLI contexts only, so the
 * panel exists even while the feature is switched off.
 *
 * Each setting is registered by the step that reads it, so a reviewer never meets a
 * control that does nothing: the plugin status section lands here, the "Show
 * Security tab" switch arrives with the tab, and the recovery-rule settings arrive
 * with the rule.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Features\Integrations\TwoFactor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$bb_two_factor_state = bb_two_factor_get_plugin_state();

// =========================================================================
// SIDE PANEL
// =========================================================================

bb_register_side_panel(
	'two-factor',
	'two_factor_settings',
	array(
		'title'      => __( 'Two-Factor Settings', 'buddyboss' ),
		'icon'       => array(
			'type'  => 'font',
			'class' => 'bb-icons-rl bb-icons-rl-shield-check',
		),
		'order'      => 10,
		'is_default' => true,
	)
);

// =========================================================================
// SECTION: plugin status
// =========================================================================

if ( 'active' === $bb_two_factor_state ) {
	$bb_two_factor_status = array(
		'type' => 'success',
		'text' => __( 'Ready', 'buddyboss' ),
	);
} else {
	$bb_two_factor_status = array(
		'type' => 'warning',
		'text' => __( 'Setup Required', 'buddyboss' ),
	);
}

bb_register_feature_section(
	'two-factor',
	'two_factor_settings',
	'two_factor_plugin',
	array(
		'title'       => __( 'Two Factor plugin', 'buddyboss' ),
		'description' => __( 'Two-factor authentication is provided by the Two Factor plugin from WordPress.org. BuddyBoss adds the member-facing screens on top of it.', 'buddyboss' ),
		'order'       => 10,
		'status'      => $bb_two_factor_status,
	)
);

if ( 'active' === $bb_two_factor_state ) {
	/*
	 * The plugin owns the site-wide list of which methods members may use. Link to
	 * its screen rather than duplicating the setting.
	 */
	bb_register_feature_field(
		'two-factor',
		'two_factor_settings',
		'two_factor_plugin',
		array(
			'name'              => 'bb_two_factor_manage_methods',
			'label'             => __( 'Available methods', 'buddyboss' ),
			'type'              => 'manage_link',
			'description'       => __( 'Choose which verification methods members can use. Leave email codes available: the plugin falls back to them when a member\'s other methods stop working.', 'buddyboss' ),
			'manage_url'        => admin_url( 'options-general.php?page=two-factor-settings' ),
			'manage_label'      => __( 'Manage methods', 'buddyboss' ),
			'sanitize_callback' => '__return_empty_string',
			'order'             => 10,
		)
	);

} else {

	if ( 'installed_inactive' === $bb_two_factor_state ) {
		$bb_two_factor_notice = sprintf(
			/* translators: %s: link to the Plugins screen. */
			__( 'The Two Factor plugin is installed but not active. %s to let members set up two-factor authentication.', 'buddyboss' ),
			'<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'Activate it', 'buddyboss' ) . '</a>'
		);
	} elseif ( 'unsupported_version' === $bb_two_factor_state ) {
		$bb_two_factor_notice = sprintf(
			/* translators: 1: minimum supported version, 2: link to the Plugins screen. */
			__( 'This integration needs Two Factor %1$s or newer. %2$s to continue.', 'buddyboss' ),
			esc_html( bb_two_factor_min_plugin_version() ),
			'<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'Update the plugin', 'buddyboss' ) . '</a>'
		);
	} else {
		$bb_two_factor_notice = sprintf(
			/* translators: %s: link to the plugin installer. */
			__( 'The Two Factor plugin is not installed. %s to let members set up two-factor authentication.', 'buddyboss' ),
			'<a href="' . esc_url( admin_url( 'plugin-install.php?s=two-factor&tab=search&type=term' ) ) . '">' . esc_html__( 'Install it', 'buddyboss' ) . '</a>'
		);
	}

	bb_register_feature_field(
		'two-factor',
		'two_factor_settings',
		'two_factor_plugin',
		array(
			'name'              => 'bb_two_factor_plugin_notice',
			'label'             => '',
			'type'              => 'notice',
			'notice_type'       => 'warning',
			'description'       => $bb_two_factor_notice,
			'sanitize_callback' => '__return_empty_string',
			'order'             => 10,
		)
	);
}

unset( $bb_two_factor_state, $bb_two_factor_status, $bb_two_factor_notice );
