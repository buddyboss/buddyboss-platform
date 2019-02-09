<?php
/**
 * BuddyBoss Core Component Widgets.
 *
 * @package BuddyBoss\Core
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register bp-core widgets.
 *
 * @since BuddyPress 1.0.0
 */
function bp_core_register_widgets() {
	add_action( 'widgets_init', function() { register_widget( 'BP_Core_Login_Widget' ); } );
}
add_action( 'bp_register_widgets', 'bp_core_register_widgets' );
