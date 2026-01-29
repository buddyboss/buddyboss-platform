<?php
/**
 * reCAPTCHA Integration Feature Configuration
 *
 * Registers the reCAPTCHA integration as a feature in the BuddyBoss Feature Registry.
 *
 * @package BuddyBoss\Features\Integrations\Recaptcha
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register reCAPTCHA integration with Feature Registry.
 *
 * This makes the reCAPTCHA integration appear as a feature card in Settings 2.0
 * and enables conditional loading based on feature activation state.
 */
bb_register_integration(
	'recaptcha',
	array(
		'label'                 => __( 'reCAPTCHA', 'buddyboss' ),
		'description'           => __( 'Add Google reCAPTCHA verification to registration, login, and other forms to prevent spam and bot submissions. Supports both reCAPTCHA v2 and v3.', 'buddyboss' ),
		'icon'                  => array(
			'type'  => 'font',
			'class' => 'bb-icons-rl bb-icons-rl-shield-check',
		),
		'license_tier'          => 'free',
		'required_plugin_const' => '', // No external plugin required.
		'php_loader'            => function() {
			require_once __DIR__ . '/loader.php';
		},
		'settings_route'        => '/settings/recaptcha',
		'order'                 => 230,
	)
);
