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
add_action( 'bp_setup_components', 'bboss_setup_members', 1 );
