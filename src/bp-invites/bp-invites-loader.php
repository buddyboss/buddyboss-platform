<?php
/**
 * BuddyBoss Invites Loader.
 *
 * A invites component, Allow you users to send invites to non-members to join the network.
 *
 * @package BuddyBoss
 * @subpackage InvitesLoader
 * @since BuddyBoss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-invites component.
 *
 * @since BuddyPress 1.5.0
 */
function bp_setup_invites() {
	buddypress()->invites = new BP_Invites_Component();
}
add_action( 'bp_setup_components', 'bp_setup_invites', 6 );
