<?php
/**
 * Two-Factor integration feature configuration.
 *
 * Registers the integration in the BuddyBoss Feature Registry. Auto-discovered by
 * BB_Feature_Autoloader from bb-features/integrations/two-factor/, so this file runs
 * on every request and must stay cheap.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Features\Integrations\TwoFactor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/*
 * The activation callbacks below are resolved as early as bp_loaded, and the
 * integration's own includes() runs at bp_include — both before feature discovery
 * would otherwise load these files. Requiring them here keeps every consumer
 * working regardless of which runs first.
 */
require_once __DIR__ . '/includes/compat.php';
require_once __DIR__ . '/bb-two-factor-functions.php';

bb_register_integration(
	'two-factor',
	array(
		'label'                   => __( 'Two-Factor Authentication', 'buddyboss' ),
		'description'             => __( 'Let members secure their account with an authenticator app, email codes or recovery codes, managed from their Account page.', 'buddyboss' ),
		'icon'                    => array(
			'type'  => 'font',
			'class' => 'bb-icons-rl bb-icons-rl-shield-check',
		),
		'license_tier'            => 'free',
		'standalone'              => true,
		'integration_id'          => 'two-factor',
		'settings_route'          => '/settings/two-factor',
		'order'                   => 20,

		/*
		 * Availability reports whether the Two Factor plugin is present. A false value
		 * greys the card, disables its toggle and its Settings button, and forces the
		 * feature inactive - which is the intended message when the plugin is missing.
		 */
		'is_available_callback'   => 'bb_two_factor_plugin_is_active',
		'is_active_callback'      => 'bb_two_factor_feature_is_on',

		'confirm_off_title'       => __( 'Disable Two-Factor Authentication?', 'buddyboss' ),
		'confirm_off_message'     => __( 'Members will stop being asked for a second factor when they sign in, and the Security tab will be hidden. Authenticator apps and recovery codes are kept, so re-enabling later restores them.', 'buddyboss' ),
		'confirm_off_destructive' => true,
	)
);

/*
 * Settings registration is admin-side work, and it must happen even while the
 * feature is switched off so the panel can still be opened. Loading it from a
 * feature loader would not satisfy that, so it is required here instead - gated to
 * the contexts that can actually render or serve the panel.
 */
if (
	is_admin() ||
	wp_doing_ajax() ||
	( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
	( defined( 'WP_CLI' ) && WP_CLI )
) {
	require_once __DIR__ . '/admin/settings.php';
}
