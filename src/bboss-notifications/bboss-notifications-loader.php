<?php
/**
 * BuddyPress Member Notifications Loader.
 *
 * Initializes the Notifications component.
 *
 * @package BuddyBoss\Notifications\Loader
 * @since BuddyPress 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-notifications component.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_setup_notifications() {
	bboss()->notifications = new BBoss_Notifications_Component();
}
add_action( 'bp_setup_components', 'bboss_setup_notifications', 6 );
