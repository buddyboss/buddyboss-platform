<?php
/**
 * Compatibility Integration Loader
 *
 * Loads the BuddyPress Compatibility integration when the feature is active.
 *
 * @package BuddyBoss\Features\Integrations\Compatibility
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register and initialize the Compatibility integration.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_compatibility_integration_init() {
	// Load helper functions.
	require_once __DIR__ . '/includes/functions.php';

	// Load the integration class.
	require_once __DIR__ . '/classes/class-compatibility-integration.php';

	// Register with BuddyPress integrations system.
	buddypress()->integrations['compatibility'] = new BP_Compatibility_Integration();
}
add_action( 'bp_setup_integrations', 'bb_compatibility_integration_init' );
