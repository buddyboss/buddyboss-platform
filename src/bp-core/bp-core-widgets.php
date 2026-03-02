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
	add_action(
		'widgets_init',
		function() {
			register_widget( 'BP_Core_Login_Widget' );
		}
	);
	if ( is_multisite() ) {
		add_action(
			'widgets_init',
			function() {
				register_widget( 'BP_Core_Network_Posts_Widget' );
			}
		);
	}
	if ( function_exists( 'bp_get_following_ids' ) && class_exists( 'BP_Core_Follow_Following_Widget' ) ) {
		add_action(
			'widgets_init',
			function() {
				register_widget( 'BP_Core_Follow_Following_Widget' );
			}
		);
	}

	if ( function_exists( 'bp_get_following_ids' ) && class_exists( 'BP_Core_Follow_Follower_Widget' ) ) {
		add_action(
			'widgets_init',
			function() {
				register_widget( 'BP_Core_Follow_Follower_Widget' );
			}
		);
	}
}
add_action( 'bp_register_widgets', 'bp_core_register_widgets' );
