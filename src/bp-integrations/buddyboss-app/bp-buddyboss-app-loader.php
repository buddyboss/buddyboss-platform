<?php
/**
 * BuddyBoss App Integration Loader.
 *
 * @package BuddyBoss\App
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp app integration.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_register_buddyboss_app_integration() {
	require_once dirname( __FILE__ ) . '/bp-buddyboss-app-integration.php';
	buddypress()->integrations['buddyboss-app'] = new BP_App_Integration();
}
add_action( 'bp_setup_integrations', 'bp_register_buddyboss_app_integration' );
