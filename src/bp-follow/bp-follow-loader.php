<?php
/**
 * BuddyBoss Follow Loader.
 *
 * The follow component is for users to create relationships with each other.
 *
 * @package BuddyBoss
 * @subpackage Follow
 * @since BuddyBoss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-follow component.
 *
 * @since BuddyBoss 3.1.1
 */
function bp_setup_follow() {
	buddypress()->follow = new BP_Follow_Component();
}
add_action( 'bp_setup_components', 'bp_setup_follow', 6 );
