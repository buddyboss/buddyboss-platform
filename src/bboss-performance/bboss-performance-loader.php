<?php
/**
 * BuddyBoss Performance Loader.
 *
 * A performance component, Allow to cache BuddyBoss Platform REST API.
 *
 * @package BuddyBoss\Performance\Loader
 * @since BuddyBoss 1.5.7
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-performance component.
 *
 * @since BuddyBoss 1.5.7
 */
function bboss_setup_performance() {
	bboss()->performance = new BBoss_Performance_Component();
}
add_action( 'bp_setup_components', 'bp_setup_performance', 1 );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_performance' ) && ! function_exists( 'bp_setup_performance' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_performance()}.
	 *
	 * @deprecated Use bboss_setup_performance() instead.
	 */
	function bp_setup_performance() {
		return bboss_setup_performance();
	}
}
// @bbwt-com-end compat-layer
