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
add_action( 'bp_setup_components', 'bp_setup_activity', 6 );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_activity' ) && ! function_exists( 'bp_setup_activity' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_activity()}.
	 *
	 * @deprecated Use bboss_setup_activity() instead.
	 */
	function bp_setup_activity() {
		return bboss_setup_activity();
	}
}
// @bbwt-com-end compat-layer
