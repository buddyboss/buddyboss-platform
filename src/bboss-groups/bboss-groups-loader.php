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
function bboss_setup_groups() {
	bboss()->groups = new BBoss_Groups_Component();
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_groups' ) && ! function_exists( 'bp_setup_groups' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_groups()}.
	 *
	 * @deprecated Use bboss_setup_groups() instead.
	 */
	function bp_setup_groups() {
		return bboss_setup_groups();
	}
}
// @bbwt-com-end compat-layer

add_action( 'bp_setup_components', 'bp_setup_groups', 6 );
