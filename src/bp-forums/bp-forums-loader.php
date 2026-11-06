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
