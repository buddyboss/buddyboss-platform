<?php
/**
 * reCAPTCHA Integration Feature Configuration.
 *
 * Registers the reCAPTCHA integration in the BuddyBoss Feature Registry.
 * Auto-discovered by BB_Feature_Autoloader from bb-features/integrations/recaptcha/.
 *
 * @package BuddyBoss\Features\Integrations\Recaptcha
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register reCAPTCHA integration with Feature Registry.
 *
 * Uses bb_register_integration() which forces category to 'integrations'
 * and wires the Integration Bridge for legacy BP_Integration sync.
 */
bb_register_integration(
	'recaptcha',
	array(
		'label'              => __( 'reCAPTCHA', 'buddyboss' ),
		'description'        => __( 'Connect Google reCAPTCHA to protect your website from fraud, spam, and abuse.', 'buddyboss' ),
		'icon'               => array(
			'type'  => 'font',
			'class' => 'bb-icons-rl bb-icons-rl-shield-check',
		),
		'license_tier'       => 'free',
		'standalone'         => true,
		'integration_id'     => 'recaptcha',
		'is_active_callback' => function () {
			return (bool) apply_filters( 'bb_recaptcha_integration_is_active', true );
		},
		'settings_route'     => '/settings/recaptcha',
		'order'              => 210,
	)
);

// Load Settings 2.0 configuration (side panels, sections, fields).
// This must be loaded here (not in a php_loader) so settings are registered
// even when the feature is inactive (needed to show the settings page).
if ( file_exists( __DIR__ . '/admin/settings.php' ) ) {
	require_once __DIR__ . '/admin/settings.php';
}
