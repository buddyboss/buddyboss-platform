<?php
/**
 * BuddyBoss Connections Feeds Loader.
 *
 * The friends component is for users to create relationships with each other.
 *
 * @package BuddyBoss\Connections
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-friends component.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_setup_friends() {
	bboss()->friends = new BBoss_Friends_Component();
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_friends' ) && ! function_exists( 'bp_setup_friends' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_friends()}.
	 *
	 * @deprecated Use bboss_setup_friends() instead.
	 */
	function bp_setup_friends() {
		return bboss_setup_friends();
	}
}
// @bbwt-com-end compat-layer

add_action( 'bp_setup_components', 'bp_setup_friends', 6 );
