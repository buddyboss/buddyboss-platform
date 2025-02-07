<?php
/**
 * BuddyBoss Core Readylaunch.
 *
 * Handles the core functions related to the BB Readylaunch.
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register readyLaunch widgets.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_rl_register_widgets () {
	if ( bb_get_enabled_readylaunch() && function_exists( 'bp_get_following_ids' ) ) {
		$plugin_dir = BP_PLUGIN_DIR;
		if ( defined( 'BP_SOURCE_SUBDIRECTORY' ) && ! empty( constant( 'BP_SOURCE_SUBDIRECTORY' ) ) ) {
			$plugin_dir = $plugin_dir . 'src';
		}
		$widget_file = $plugin_dir . '/bp-core/classes/class-bb-core-follow-my-network-widget.php';
		if ( file_exists( $widget_file ) ) {
			require_once $widget_file;
			if ( class_exists( 'BB_Core_Follow_My_Network_Widget' ) ) {
				add_action(
					'widgets_init',
					function() {
						register_widget( 'BB_Core_Follow_My_Network_Widget' );
					}
				);

				// Do not allow the widget to admin area, only be used for readylaunch.
				add_action( 'widgets_init', function() {
					if ( is_admin() ) {
						unregister_widget( 'BB_Core_Follow_My_Network_Widget' );
					}
				}, 11 ); 
			}
		}
	}
}

add_action( 'bp_register_widgets', 'bb_rl_register_widgets' );

/**
 * Filter pre existing widgets.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_rl_modify_existing_widget_output( $instance, $widget, $args ) {
    ob_start(); // Start output buffering.
	$widget->widget( $args, $instance ); // Render the widget.
	$output = ob_get_clean(); // Get the output.

	// Match any div containing 'more-block' as one of its classes.
	if ( preg_match( '/(<div[^>]*\bmore-block\b[^>]*>.*?<\/div>)/s', $output, $matches ) ) {

		// Define URLs based on widget class.
		$updated_widget_urls = array(
			'BP_Core_Recently_Active_Widget' => esc_url( bp_get_members_directory_permalink() . '?bb-rl-members-order-by=active' ),
		);
	
		$widget_class = get_class( $widget );
		$more_block   = $matches[1];

		// Replace class "more-block" with "bb-rl-see-all".
		$updated_more_block = preg_replace( '/\bmore-block\b/', 'bb-rl-see-all', $more_block );

		if ( ! empty( $updated_widget_urls[ $widget_class ] ) ) {

			// Override the href inside the anchor tag.
			$updated_more_block = preg_replace( '/href="([^"]*)"/', 'href="' . esc_url( $updated_widget_urls[ $widget_class ] ) . '"', $updated_more_block );
		}

		// Remove old div and insert the updated one into the title.
		$output = str_replace( $more_block, '', $output );
		$output = preg_replace( '/(<h[1-6][^>]*>)(.*?)(<\/h[1-6]>)/s', '$1$2 ' . $updated_more_block . ' $3', $output, 1 );
	}

	echo $output; // Output modified widget.
	return false; // Prevent default rendering.

}
add_filter( 'widget_display_callback', 'bb_rl_modify_existing_widget_output', 10, 3 );
