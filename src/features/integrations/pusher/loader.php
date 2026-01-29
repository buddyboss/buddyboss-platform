<?php
/**
 * Pusher Integration Loader
 *
 * Loads the Pusher integration when the feature is active.
 *
 * @package BuddyBoss\Features\Integrations\Pusher
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register and initialize the Pusher integration.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_pusher_integration_init() {
	// Check if pro plugin handles Pusher integration.
	if (
		( function_exists( 'bb_platform_pro' ) && version_compare( bb_platform_pro()->version, bb_pro_pusher_version(), '>=' ) ) ||
		class_exists( 'BB_Pusher_Integration' )
	) {
		return;
	}

	// Load the integration class.
	require_once __DIR__ . '/classes/class-pusher-integration.php';

	// Register with BuddyPress integrations system.
	buddypress()->integrations['pusher'] = new BB_Pusher_Integration();
}
add_action( 'bp_setup_integrations', 'bb_pusher_integration_init', 20 );
