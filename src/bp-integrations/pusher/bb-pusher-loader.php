<?php
/**
 * BuddyBoss Pusher Integration Loader.
 *
 * @package BuddyBoss\Pusher
 *
 * @since BuddyBoss 2.1.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the BB Pusher integration.
 *
 * @since BuddyBoss 2.1.4
 */
function bboss_register_pusher_integration() {

	if (
		( function_exists( 'bb_platform_pro' ) && version_compare( bb_platform_pro()->version, bboss_pro_pusher_version(), '>=' ) ) ||
		class_exists( 'BBoss_Pusher_Integration' )
	) {
		return;
	}

	require_once dirname( __FILE__ ) . '/bb-pusher-integration.php';
	bboss()->integrations['pusher'] = new BBoss_Pusher_Integration();
}
add_action( 'bp_setup_integrations', 'bboss_register_pusher_integration', 20 );
