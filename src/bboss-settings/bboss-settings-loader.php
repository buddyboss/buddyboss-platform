<?php
/**
 * BuddyBoss Settings Loader.
 *
 * @package BuddyBoss\Settings\Loader
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-settings component.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_setup_settings() {
	bboss()->settings = new BBoss_Settings_Component();
}
add_action( 'bp_setup_components', 'bp_setup_settings', 6 );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_settings' ) && ! function_exists( 'bp_setup_settings' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_settings()}.
	 *
	 * @deprecated Use bboss_setup_settings() instead.
	 */
	function bp_setup_settings() {
		return bboss_setup_settings();
	}
}
// @bbwt-com-end compat-layer
