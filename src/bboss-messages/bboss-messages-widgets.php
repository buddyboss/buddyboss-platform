<?php
/**
 * BuddyBoss Messages Widgets.
 *
 * @package BuddyBoss\Messages
 * @since BuddyPress 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register widgets for the Messages component.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_messages_register_widgets() {
	add_action(
		'widgets_init',
		function() {
			register_widget( 'BBoss_Messages_Sitewide_Notices_Widget' );
		}
	);
}
add_action( 'bp_register_widgets', 'bp_messages_register_widgets' );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_messages_register_widgets' ) && ! function_exists( 'bp_messages_register_widgets' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_messages_register_widgets()}.
	 *
	 * @deprecated Use bboss_messages_register_widgets() instead.
	 */
	function bp_messages_register_widgets() {
		return bboss_messages_register_widgets();
	}
}
// @bbwt-com-end compat-layer
