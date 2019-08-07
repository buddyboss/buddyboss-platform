<?php
/**
 * BuddyBoss Groups Loader.
 *
 * A groups component, for users to group themselves together. Includes a
 * robust sub-component API that allows Groups to be extended.
 * Comes preconfigured with an activity feed, discussion forums, and settings.
 *
 * @package BuddyBoss\Groups\Loader
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-groups component.
 *
 * @since BuddyPress 1.5.0
 */
function bp_setup_groups() {
	buddypress()->groups = new BP_Groups_Component();
}
add_action( 'bp_setup_components', 'bp_setup_groups', 6 );
