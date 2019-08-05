<?php
/**
 * BuddyBoss Compatibility Integration Loader.
 *
 * @since BuddyBoss 1.1.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp compatibility integration.
 *
 * @since BuddyBoss 1.1.5
 */
function bp_register_compatibility_integration() {
	require_once dirname( __FILE__ ) . '/bp-compatibility-integration.php';
	buddypress()->integrations['compatibility'] = new BP_Compatibility_Integration;
}
add_action( 'bp_setup_integrations', 'bp_register_compatibility_integration' );
