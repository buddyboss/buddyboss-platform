<?php
/**
 * BuddyBoss Core Gdpr Loader.
 *
 * Core contains the commonly used functions, classes, and APIs.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-core component.
 *
 * @since BuddyBoss 1.0.0
 */
function bboss_setup_gdpr() {
	new BBoss_Core_Gdpr();
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_gdpr' ) && ! function_exists( 'bp_setup_gdpr' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_gdpr()}.
	 *
	 * @deprecated Use bboss_setup_gdpr() instead.
	 */
	function bp_setup_gdpr() {
		return bboss_setup_gdpr();
	}
}
// @bbwt-com-end compat-layer

add_action( 'bp_loaded', 'bp_setup_gdpr', 0 );
