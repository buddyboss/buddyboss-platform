<?php
/**
 * BuddyBoss App Integration Loader
 *
 * Loads the BuddyBoss App integration when the feature is active.
 *
 * @package BuddyBoss\Features\Integrations\BuddyBossApp
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register and initialize the BuddyBoss App integration.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_buddyboss_app_integration_init() {
	// Load the integration class.
	require_once __DIR__ . '/classes/class-buddyboss-app-integration.php';

	// Register with BuddyPress integrations system.
	buddypress()->integrations['buddyboss-app'] = new BP_App_Integration();
}
add_action( 'bp_setup_integrations', 'bb_buddyboss_app_integration_init' );
