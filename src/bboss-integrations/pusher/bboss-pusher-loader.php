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

	require_once dirname( __FILE__ ) . '/bboss-pusher-integration.php';
	bboss()->integrations['pusher'] = new BBoss_Pusher_Integration();
}
add_action( 'bp_setup_integrations', 'bb_register_pusher_integration', 20 );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_register_pusher_integration' ) && ! function_exists( 'bb_register_pusher_integration' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_register_pusher_integration()}.
	 *
	 * @deprecated Use bboss_register_pusher_integration() instead.
	 */
	function bb_register_pusher_integration() {
		return bboss_register_pusher_integration();
	}
}
// @bbwt-com-end compat-layer
