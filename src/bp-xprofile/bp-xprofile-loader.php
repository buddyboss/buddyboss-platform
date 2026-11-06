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
add_action( 'bp_setup_components', 'bboss_setup_xprofile', 2 );
