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
			'BP_Core_Recently_Active_Widget' => esc_url( bp_get_members_directory_permalink() . '?bb-rl-order-by=active&bb-rl-scope=all' ),
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

/**
 * Open wrapper of repeater set - on View profile screen
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @global type $first_xpfield_in_repeater
 */
function bb_rl_view_profile_repeaters_print_group_html_start() {
	$group_id            = bp_get_the_profile_group_id();
	$is_repeater_enabled = 'on' == BP_XProfile_Group::get_group_meta( $group_id, 'is_repeater_enabled' ) ? true : false;
	if ( $is_repeater_enabled ) {
		global $repeater_set_being_displayed;

		$current_field_id   = bp_get_the_profile_field_id();
		$current_set_number = bp_xprofile_get_meta( $current_field_id, 'field', '_clone_number', true );

		if ( ! empty( $repeater_set_being_displayed ) && $repeater_set_being_displayed != $current_set_number ) {
			// End of previous set.
			echo "<div class='bb-rl-repeater-separator'></div>";
		}

		$repeater_set_being_displayed = $current_set_number;
	}
}
remove_action( 'bp_before_profile_field_item', 'bp_view_profile_repeaters_print_group_html_start' );
add_action( 'bp_before_profile_field_item', 'bb_rl_view_profile_repeaters_print_group_html_start' );

/**
 * Close wrapper of repeater set - on edit profile screen
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @global boolean $first_xpfield_in_repeater
 */
function bb_rl_view_profile_repeaters_print_group_html_end() {
	global $repeater_set_being_displayed;
	if ( ! empty( $repeater_set_being_displayed ) ) {

		// End of previous set.
		echo "<div class='bb-rl-repeater-separator'></div>";

		$repeater_set_being_displayed = false;
	}
}
remove_filter( 'bp_ps_field_before_query', 'bp_profile_repeaters_search_change_filter' );
add_filter( 'bp_ps_field_before_query', 'bb_rl_view_profile_repeaters_print_group_html_end' );


/**
 * Add social networks button to the member header area.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string
 */
function bb_rl_get_user_social_networks_urls( $user_id = null ) {
	$social_networks_id                 = bb_rl_get_user_social_networks_field_id();
	$is_enabled_header_social_networks  = bb_enabled_profile_header_layout_element( 'social-networks' ) && function_exists( 'bb_enabled_member_social_networks' ) && bb_enabled_member_social_networks();

	$html = '';

	$original_option_values = array();

	$user = ( $user_id !== null && $user_id > 0 ) ? $user_id : bp_displayed_user_id();

	if ( $social_networks_id > 0 ) {
		$providers = bp_xprofile_social_network_provider();

		$original_option_values = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $social_networks_id, $user ) );

		$social_settings_field   = xprofile_get_field( $social_networks_id, $user_id );
		$social_settings_options = $social_settings_field->get_children();

		if (
			isset( $original_option_values ) &&
			! empty( $original_option_values ) &&
			is_array( $original_option_values ) &&
			! empty( $social_settings_options )
		) {

			$original_option_values = array_intersect_key( $original_option_values, array_flip( array_column( $social_settings_options, 'name' ) ) );
			foreach ( $original_option_values as $key => $original_option_value ) {
				if ( '' !== $original_option_value ) {
					$key = bp_social_network_search_key( $key, $providers );
					$html .= '<span class="bb-rl-social ' . esc_attr( $providers[ $key ]->value ) . '"><a target="_blank" data-balloon-pos="up" data-balloon="' . esc_attr( $providers[ $key ]->name ) . '" href="' . esc_url( $original_option_value ) . '"><i class="bb-icons-rl-' . esc_attr( strtolower( $providers[ $key ]->value ) ) . '-logo"></i></a></span>';
				}
			}
		}
	}

	if ( $html !== '' ) {
		$level = xprofile_get_field_visibility_level( $social_networks_id, bp_displayed_user_id() );
		if ( 'friends' === $level && is_user_logged_in() ) {

			$member_friend_status = friends_check_friendship_status( bp_loggedin_user_id(), bp_displayed_user_id() );
			if ( 'is_friend' === $member_friend_status ) {
				$html = '<div class="social-networks-wrap">' . $html . '</div>';
			} else {
				$html = '';
			}
		} else {
			$html = '<div class="social-networks-wrap">' . $html . '</div>';
		}
	}

	return apply_filters( 'bb_rl_get_user_social_networks_urls', $html, $original_option_values, $social_networks_id );
}

/**
 * Get social network field id.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return int Social network xprofile field id.
 */
function bb_rl_get_user_social_networks_field_id() {
	global $wpdb, $bp;

	$social_networks_field = $wpdb->get_row( "SELECT a.id FROM {$bp->table_prefix}bp_xprofile_fields a WHERE parent_id = 0 AND type = 'socialnetworks' " );
	return ! empty( $social_networks_field->id ) ? $social_networks_field->id : 0;
}
