<?php
/**
 * reCAPTCHA Integration Loader
 *
 * Loads the reCAPTCHA integration when the feature is active.
 *
 * @package BuddyBoss\Features\Integrations\Recaptcha
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register and initialize the reCAPTCHA integration.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_recaptcha_integration_init() {
	// Load the integration class.
	require_once __DIR__ . '/classes/class-bb-recaptcha-integration.php';

	// Register with BuddyPress integrations system.
	buddypress()->integrations['recaptcha'] = new BB_Recaptcha_Integration();
}
add_action( 'bp_setup_integrations', 'bb_recaptcha_integration_init', 20 );
