<?php
/**
 * BuddyBoss xProfile Component Widgets.
 *
 * Functions here registers Profile Completion Widget.
 *
 * @package BuddyBoss\XProfile
 * @since BuddyBoss 1.2.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register bp-profile widgets.
 *
 * @since BuddyBoss 1.2.5
 */
function bboss_xprofile_register_widgets() {

	/**
	 * Profile Completion Widget
	 */
	add_action(
		'widgets_init',
		function () {
			register_widget( 'BBoss_Xprofile_Profile_Completion_Widget' );
		}
	);

}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_xprofile_register_widgets' ) && ! function_exists( 'bp_xprofile_register_widgets' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_xprofile_register_widgets()}.
	 *
	 * @deprecated Use bboss_xprofile_register_widgets() instead.
	 */
	function bp_xprofile_register_widgets() {
		return bboss_xprofile_register_widgets();
	}
}
// @bbwt-com-end compat-layer


add_action( 'bp_register_widgets', 'bp_xprofile_register_widgets' );
