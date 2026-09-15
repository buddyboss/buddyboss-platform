<?php
/**
 * Two-Factor integration loader.
 *
 * Included by BP_Core::load_integrations() via the bp_integrations whitelist, so it
 * must keep this exact filename.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Features\Integrations\TwoFactor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/*
 * bp_include runs before feature discovery loads bb-feature-config.php, and the
 * Bridge evaluates is_activated() at bp_loaded @3. Both need the public API.
 */
require_once __DIR__ . '/includes/compat.php';
require_once __DIR__ . '/bb-two-factor-functions.php';

/**
 * Let the Settings 2.0 feature toggle gate BP_Integration::is_activated().
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_two_factor_register_managed_integration() {
	if ( function_exists( 'bb_integration_bridge' ) ) {
		bb_integration_bridge()->register_managed_integration( 'two-factor', 'two-factor' );
	}
}
add_action( 'bb_integration_bridge_init', 'bb_two_factor_register_managed_integration' );

/**
 * Set up the Two-Factor integration object.
 *
 * Priority 20 matches reCAPTCHA: after bp_setup_components, inside bp_setup_integrations.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_register_two_factor_integration() {
	require_once __DIR__ . '/classes/class-bb-two-factor-integration.php';
	buddypress()->integrations['two-factor'] = new BB_Two_Factor_Integration();
}
add_action( 'bp_setup_integrations', 'bb_register_two_factor_integration', 20 );
