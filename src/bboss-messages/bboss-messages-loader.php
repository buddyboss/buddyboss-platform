<?php
/**
 * BuddyBoss Messages Loader.
 *
 * A private messages component, for users to send messages to each other.
 *
 * @package BuddyBoss\Messages\Loader
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-messages component.
 *
 * @since BuddyPress 1.5.0
 */
function bboss_setup_messages() {
	bboss()->messages = new BBoss_Messages_Component();
}
add_action( 'bp_setup_components', 'bboss_setup_messages', 6 );
