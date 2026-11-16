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
add_action( 'bp_screens', 'groups_directory_groups_setup', 2 );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_groups_directory_groups_setup' ) && ! function_exists( 'groups_directory_groups_setup' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_directory_groups_setup()}.
	 *
	 * @deprecated Use bboss_groups_directory_groups_setup() instead.
	 */
	function groups_directory_groups_setup() {
		return bboss_groups_directory_groups_setup();
	}
}
// @bbwt-com-end compat-layer
