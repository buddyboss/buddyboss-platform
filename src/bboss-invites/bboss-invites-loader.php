<?php
/**
 * BuddyBoss Invites Loader.
 *
 * A invites component, Allow your users to send email invites to non-members to join the network.
 *
 * @package BuddyBoss\Invites\Loader
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-invites component.
 *
 * @since BuddyBoss 1.0.0
 */
function bboss_setup_invites() {
	bboss()->invites = new BBoss_Invites_Component();
}
add_action( 'bp_setup_components', 'bp_setup_invites', 6 );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_invites' ) && ! function_exists( 'bp_setup_invites' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_invites()}.
	 *
	 * @deprecated Use bboss_setup_invites() instead.
	 */
	function bp_setup_invites() {
		return bboss_setup_invites();
	}
}
// @bbwt-com-end compat-layer
