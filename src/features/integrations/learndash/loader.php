<?php
/**
 * LearnDash Integration Loader
 *
 * Loads the LearnDash integration when the feature is active.
 *
 * @package BuddyBoss\Features\Integrations\LearnDash
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register and initialize the LearnDash integration.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_learndash_integration_init() {
	// Load the integration class
	require_once __DIR__ . '/classes/class-learndash-integration.php';

	// Register with BuddyPress integrations system
	buddypress()->integrations['learndash'] = new BP_Learndash_Integration();
}
add_action( 'bp_setup_integrations', 'bb_learndash_integration_init' );
