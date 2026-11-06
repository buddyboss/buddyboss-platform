<?php
/**
 * Groups: Directory screen handler
 *
 * @package BuddyBoss\Group\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle the display of the Groups directory index.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_groups_directory_groups_setup() {
	if ( bboss_is_groups_directory() ) {
		bboss_update_is_directory( true, 'groups' );

		/**
		 * Fires before the loading of the Groups directory index.
		 *
		 * @since BuddyPress 1.1.0
		 */
		do_action( 'groups_directory_groups_setup' );

		/**
		 * Filters the template to load for the Groups directory index.
		 *
		 * @since BuddyPress 1.0.0
		 *
		 * @param string $value Path to the groups directory index template to load.
		 */
		bboss_core_load_template( apply_filters( 'groups_template_directory_groups', 'groups/index' ) );
	}
}
add_action( 'bp_screens', 'bboss_groups_directory_groups_setup', 2 );
