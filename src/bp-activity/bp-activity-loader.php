<?php
/**
 * BuddyBoss Activity Feeds Loader.
 *
 * An activity feed component, for users, groups, and site tracking.
 *
 * @package BuddyBoss\Activity
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-activity component.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_setup_activity() {
	bboss()->activity = new BBoss_Activity_Component();
}
add_action( 'bp_setup_components', 'bboss_setup_activity', 6 );
