<?php
/**
 * BuddyBoss Recaptcha Integration Loader.
 *
 * @package BuddyBoss\Recaptcha
 *
 * @since BuddyBoss 2.5.60
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the BB Recaptcha integration.
 *
 * @since BuddyBoss 2.5.60
 */
function bboss_register_recaptcha_integration() {
	require_once dirname( __FILE__ ) . '/classes/class-bboss-recaptcha-integration.php';
	bboss()->integrations['recaptcha'] = new BBoss_Recaptcha_Integration();
}
add_action( 'bp_setup_integrations', 'bb_register_recaptcha_integration', 20 );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_register_recaptcha_integration' ) && ! function_exists( 'bb_register_recaptcha_integration' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_register_recaptcha_integration()}.
	 *
	 * @deprecated Use bboss_register_recaptcha_integration() instead.
	 */
	function bb_register_recaptcha_integration() {
		return bboss_register_recaptcha_integration();
	}
}
// @bbwt-com-end compat-layer
