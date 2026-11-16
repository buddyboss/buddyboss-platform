<?php
/**
 * BuddyPress Member Loader.
 *
 * @package BuddyBoss\Members
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-members component.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_setup_members() {
	bboss()->members = new BBoss_Members_Component();
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_members' ) && ! function_exists( 'bp_setup_members' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_members()}.
	 *
	 * @deprecated Use bboss_setup_members() instead.
	 */
	function bp_setup_members() {
		return bboss_setup_members();
	}
}
// @bbwt-com-end compat-layer

add_action( 'bp_setup_components', 'bp_setup_members', 1 );
