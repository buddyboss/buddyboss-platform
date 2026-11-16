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
function bboss_core_register_widgets() {
	add_action(
		'widgets_init',
		function() {
			register_widget( 'BBoss_Core_Login_Widget' );
		}
	);
	if ( is_multisite() ) {
		add_action(
			'widgets_init',
			function() {
				register_widget( 'BBoss_Core_Network_Posts_Widget' );
			}
		);
	}
	if ( function_exists( 'bboss_get_following_ids' ) && class_exists( 'BBoss_Core_Follow_Following_Widget' ) ) {
		add_action(
			'widgets_init',
			function() {
				register_widget( 'BBoss_Core_Follow_Following_Widget' );
			}
		);
	}

	if ( function_exists( 'bboss_get_following_ids' ) && class_exists( 'BBoss_Core_Follow_Follower_Widget' ) ) {
		add_action(
			'widgets_init',
			function() {
				register_widget( 'BBoss_Core_Follow_Follower_Widget' );
			}
		);
	}
}
add_action( 'bp_register_widgets', 'bp_core_register_widgets' );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_register_widgets' ) && ! function_exists( 'bp_core_register_widgets' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_register_widgets()}.
	 *
	 * @deprecated Use bboss_core_register_widgets() instead.
	 */
	function bp_core_register_widgets() {
		return bboss_core_register_widgets();
	}
}
// @bbwt-com-end compat-layer
