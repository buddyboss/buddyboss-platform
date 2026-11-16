<?php
/**
 * BuddyPress XProfile Loader.
 *
 * An extended profile component for users. This allows site admins to create
 * groups of fields for users to enter information about themselves.
 *
 * @package BuddyBoss\XProfile
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-xprofile component.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_setup_xprofile() {
	$bp = bboss();

	if ( ! isset( $bp->profile->id ) ) {
		$bp->profile = new BBoss_XProfile_Component();
	}
}
add_action( 'bp_setup_components', 'bp_setup_xprofile', 2 );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_xprofile' ) && ! function_exists( 'bp_setup_xprofile' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_xprofile()}.
	 *
	 * @deprecated Use bboss_setup_xprofile() instead.
	 */
	function bp_setup_xprofile() {
		return bboss_setup_xprofile();
	}
}
// @bbwt-com-end compat-layer
