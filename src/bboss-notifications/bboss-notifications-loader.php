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
add_action( 'bp_setup_components', 'bp_setup_notifications', 6 );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_notifications' ) && ! function_exists( 'bp_setup_notifications' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_notifications()}.
	 *
	 * @deprecated Use bboss_setup_notifications() instead.
	 */
	function bp_setup_notifications() {
		return bboss_setup_notifications();
	}
}
// @bbwt-com-end compat-layer
