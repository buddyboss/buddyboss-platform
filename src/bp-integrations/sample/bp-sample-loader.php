<?php
/**
 * BuddyBoss Sample Integration Loader.
 *
 * @package BuddyBoss\Sample
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp sample integration.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_register_sample_integration() {
	require_once dirname( __FILE__ ) . '/bp-sample-integration.php';
	buddypress()->integrations['sample'] = new BP_Sample_Integration;
}
add_action( 'bp_setup_integrations', 'bp_register_sample_integration' );
