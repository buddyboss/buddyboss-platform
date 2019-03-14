<?php
/**
 * BuddyBoss MemberPress Integration Loader.
 *
 * @package BuddyBoss\Memberpress
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp memberpress integration.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_register_memberpress_integration() {
	require_once dirname( __FILE__ ) . '/bp-memberpress-integration.php';
	buddypress()->integrations['memberpress'] = new BP_Memberpress_Integration;
}
add_action( 'bp_setup_integrations', 'bp_register_memberpress_integration' );
