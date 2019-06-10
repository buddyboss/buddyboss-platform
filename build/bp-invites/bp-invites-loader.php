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
function bp_setup_invites() {
	buddypress()->invites = new BP_Invites_Component();
}
add_action( 'bp_setup_components', 'bp_setup_invites', 6 );
