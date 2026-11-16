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
add_action( 'bp_setup_components', 'bp_setup_messages', 6 );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_messages' ) && ! function_exists( 'bp_setup_messages' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_messages()}.
	 *
	 * @deprecated Use bboss_setup_messages() instead.
	 */
	function bp_setup_messages() {
		return bboss_setup_messages();
	}
}
// @bbwt-com-end compat-layer
