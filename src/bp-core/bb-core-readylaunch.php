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
			}
		}
	}
}

add_action( 'bp_register_widgets', 'bb_rl_register_widgets' );

/**
 * Register the readyLaunch sidebar.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_rl_register_sidebar() {
	$sidebar_id = 'bb-readylaunch-members-sidebar';
	register_sidebar(
		array(
			'name'          => __( 'BB ReadyLaunch™ Members Sidebar', 'buddyboss' ),
			'id'            => $sidebar_id,
			'description'   => __( 'Add widgets here to appear in the right sidebar on ReadyLaunch pages. This sidebar is used to display additional content or tools specific to ReadyLaunch.', 'buddyboss' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'bb_rl_register_sidebar' );

/**
 * Hide readylaunch sidebar to be viewing in admin area.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_rl_modify_members_sidebars_widgets( $sidebars_widgets ) {
	if ( is_admin() ) {
		unset( $sidebars_widgets['bb-readylaunch-members-sidebar'] ); // Remove all widgets from specific sidebar
	}
	return $sidebars_widgets;
}
add_filter( 'sidebars_widgets', 'bb_rl_modify_members_sidebars_widgets', 11 ); // Runs after widgets_init

/**
 * Manually add readylaunch sidebar widgets.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_rl_manually_add_members_sidebar_widgets() {
	// Get the sidebar ID.
	$sidebar_id         = 'bb-readylaunch-members-sidebar'; // Use the correct sidebar ID.
	$widget_id          = 'bb_core_follow_my_network_widget'; // The widget's registered ID.
	$instance_id        = '999';
	$widget_instance_id = $widget_id . '-' . $instance_id;
	$sidebars_widgets   = get_option( 'sidebars_widgets', array() );

	// Ensure it's an array, even if the option exists but is empty.
	if ( ! is_array( $sidebars_widgets ) ) {
		$sidebars_widgets = array();
	}

	// The widget details
	$widget_instance = array(
		'widget_id'   => $widget_id ,
		'instance_id' => $instance_id, // Use the correct instance ID.
	);

	$widget_settings = array(
        'max_users'      => 15,
        'member_default' => 'followers',
    );

	// Save widget settings to the options table
	$option_name      = 'widget_' . $widget_id; // Key for this widget settings.
	$current_settings = get_option( $option_name, array() );

	// Ensure it's an array, even if the option exists but is empty.
	if ( ! is_array( $current_settings ) ) {
		$current_settings = array();
	}

	// Add the widget settings to the existing ones (or create new if not present).
	if ( ! array_key_exists( $widget_instance_id, $current_settings ) ) {
		$current_settings[ $instance_id ] = $widget_settings;
	}

	// Save the updated widget settings back to the options table.
	update_option( $option_name, $current_settings );

	// Remove from inactive.
	if ( isset( $sidebars_widgets[ 'wp_inactive_widgets' ] ) && ( $key = array_search( $widget_instance_id, $sidebars_widgets[ 'wp_inactive_widgets' ] ) ) !== false ) {
		unset( $sidebars_widgets[ 'wp_inactive_widgets' ][ $key ] );
	}

	if ( ! isset( $sidebars_widgets[ $sidebar_id ] ) ) {
		$sidebars_widgets[ $sidebar_id ] = array( $widget_instance_id );

		// Add widget instance to sidebar.
		wp_set_sidebars_widgets( $sidebars_widgets );
	} elseif ( ! in_array( $widget_instance_id, $sidebars_widgets[ $sidebar_id ], true ) ) {
		$sidebars_widgets[ $sidebar_id ][] = $widget_instance_id;

		// Add widget instance to sidebar.
		wp_set_sidebars_widgets( $sidebars_widgets );
	}
}
add_action( 'widgets_init', 'bb_rl_manually_add_members_sidebar_widgets', 99 );