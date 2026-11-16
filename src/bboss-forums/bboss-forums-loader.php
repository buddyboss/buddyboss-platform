<?php
/**
 * BuddyBoss Forum Loader.
 *
 * The forums component allow groups to have discusstion..
 *
 * @package BuddyBoss\Forum
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-forums component.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_setup_forums() {
	bboss()->forums = bboss_forums()->extend->buddypress = new BBoss_Forums_Component();
}
add_action( 'bp_setup_components', 'bp_setup_forums', 6 );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_forums' ) && ! function_exists( 'bp_setup_forums' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_forums()}.
	 *
	 * @deprecated Use bboss_setup_forums() instead.
	 */
	function bp_setup_forums() {
		return bboss_setup_forums();
	}
}
// @bbwt-com-end compat-layer
