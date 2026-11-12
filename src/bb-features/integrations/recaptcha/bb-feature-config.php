<?php
/**
 * reCAPTCHA Integration Feature Configuration.
 *
 * Registers the reCAPTCHA integration in the BuddyBoss Feature Registry.
 * Auto-discovered by BBoss_Feature_Autoloader from bb-features/integrations/recaptcha/.
 *
 * @package BuddyBoss\Features\Integrations\Recaptcha
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register reCAPTCHA integration with Feature Registry.
 *
 * Uses bboss_register_integration() which forces category to 'integrations'
 * and wires the Integration Bridge for legacy BBoss_Integration sync.
 *
 * The card icon is a bundled font icon; the buddyboss.com build uses the
 * reCAPTCHA logo hosted on the BuddyBoss marketing bucket instead.
 */
$bb_recaptcha_icon = array(
	'type'  => 'font',
	'class' => 'bb-icons-rl bb-icons-rl-robot',
);

bboss_register_integration(
	'recaptcha',
	array(
		'label'              => __( 'reCAPTCHA', 'buddyboss' ),
		'description'        => __( 'Protect your community from spam and bot registrations with Google\'s invisible and checkbox verification.', 'buddyboss' ),
		'icon'               => $bb_recaptcha_icon,
		'license_tier'       => 'free',
		'standalone'         => true,
		'integration_id'     => 'recaptcha',
		'is_active_callback' => function () {
			// Respect Settings 2.0 feature toggle (bb-active-features).
			// Backward compat: if 'recaptcha' key not set, treat as enabled
			// (same as before this toggle existed).
			$active_features = bboss_get_option( 'bb-active-features', array() );
			if ( ! array_key_exists( 'recaptcha', $active_features ) ) {
				$is_active = true;
			} else {
				$is_active = ! empty( $active_features['recaptcha'] );
			}

			return (bool) apply_filters( 'bb_recaptcha_integration_is_active', $is_active );
		},
		'settings_route'     => '/settings/recaptcha',
		// First slot in the Integrations category. reCAPTCHA is not in the
		// S3 placeholder catalog (it's a free, built-in integration), so
		// picking a low order puts it at the top of the category regardless
		// of what the catalog does with other integrations.
		'order'              => 10,
	)
);

// Load Settings 2.0 configuration (side panels, sections, fields).
// This must be loaded here (not in a php_loader) so settings are registered
// even when the feature is inactive (needed to show the settings page).
if ( file_exists( __DIR__ . '/admin/settings.php' ) ) {
	require_once __DIR__ . '/admin/settings.php';
}
