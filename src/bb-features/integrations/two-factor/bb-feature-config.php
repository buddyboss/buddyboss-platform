<?php
/**
 * Two-Factor integration feature configuration.
 *
 * Registers the integration in the BuddyBoss Feature Registry. Auto-discovered by
 * BB_Feature_Autoloader from bb-features/integrations/two-factor/, so this file
 * runs on every request and must stay cheap.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Features\Integrations\TwoFactor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/*
 * Required here as well as from the loader: the activation callbacks resolve at
 * bp_loaded and includes() runs at bp_include, both before feature discovery.
 */
require_once __DIR__ . '/includes/compat.php';
require_once __DIR__ . '/bb-two-factor-functions.php';

bb_register_integration(
	'two-factor',
	array(
		'label'                   => __( 'Two-Factor Authentication', 'buddyboss' ),
		'description'             => __( 'Add a second layer of login security with authenticator apps, email codes, or one-time recovery codes.', 'buddyboss' ),
		'icon'                    => array(
			'type'  => 'font',
			'class' => 'bb-icons-rl bb-icons-rl-shield-check',
		),
		'license_tier'            => 'free',
		'standalone'              => true,
		'integration_id'          => 'two-factor',

		// Absolute URL: the features endpoint passes it through unchanged and the
		// card's Settings button opens the Two Factor plugin's own screen.
		'settings_route'          => admin_url( 'options-general.php?page=two-factor-settings' ),
		'order'                   => 20,

		// Removes the card from the features grid outright. A false
		// is_available_callback alone would only render it greyed out.
		'hidden'                  => ! bb_two_factor_plugin_is_active(),

		'is_available_callback'   => 'bb_two_factor_plugin_is_active',
		'is_active_callback'      => 'bb_two_factor_feature_is_on',

		'confirm_off_title'       => __( 'Disable Two-Factor Authentication?', 'buddyboss' ),
		'confirm_off_message'     => __( 'Members will stop being asked for a second factor when they sign in, and the Security tab will be hidden. Authenticator apps and recovery codes are kept, so re-enabling later restores them.', 'buddyboss' ),
		'confirm_off_destructive' => true,
	)
);
