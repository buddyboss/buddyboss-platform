<?php
/**
 * BuddyBoss AppBoss Integration Loader.
 *
 * @package BuddyBoss\AppBoss
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp appboss integration.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_register_appboss_integration() {
	require_once dirname( __FILE__ ) . '/bp-appboss-integration.php';
	buddypress()->integrations['appboss'] = new BP_Appboss_Integration();
}
add_action( 'bp_setup_integrations', 'bp_register_appboss_integration' );
