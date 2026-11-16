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
function bboss_register_buddyboss_app_integration() {
	require_once dirname( __FILE__ ) . '/bboss-buddyboss-app-integration.php';
	bboss()->integrations['buddyboss-app'] = new BBoss_App_Integration();
}
add_action( 'bp_setup_integrations', 'bp_register_buddyboss_app_integration' );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_register_buddyboss_app_integration' ) && ! function_exists( 'bp_register_buddyboss_app_integration' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_register_buddyboss_app_integration()}.
	 *
	 * @deprecated Use bboss_register_buddyboss_app_integration() instead.
	 */
	function bp_register_buddyboss_app_integration() {
		return bboss_register_buddyboss_app_integration();
	}
}
// @bbwt-com-end compat-layer
