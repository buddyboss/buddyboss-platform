<?php
/**
 * BuddyBoss xProfile Component Widgets.
 *
 * @package BuddyBoss\XProfile\Classes
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register bp-profile widgets.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_xprofile_register_widgets() {
	
	/**
	 * Profile Completion Widget
	 */
	add_action(
		'widgets_init',
		function() {
			register_widget( 'BP_Xprofile_Profile_Completion_Widget' );
		}
	);
	
}
add_action( 'bp_register_widgets', 'bp_xprofile_register_widgets' );
