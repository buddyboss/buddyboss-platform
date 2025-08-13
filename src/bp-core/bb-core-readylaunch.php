<?php
/**
 * BuddyBoss Core ReadyLaunch.
 *
 * Handles the core functions related to the BB ReadyLaunch.
 *
 * @package BuddyBoss\Core\ReadyLaunch
 * @since BuddyBoss 2.9.00
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register a widget for ReadyLaunch
 *
 * @since BuddyBoss 2.9.00
 *
 * @param string $widget_class Widget class name.
 * @param string $widget_file Widget file path.
 *
 * @return void
 */
function bb_rl_register_single_widget( $widget_class, $widget_file ) {
	if ( ! file_exists( $widget_file ) ) {
		return;
	}

	require_once $widget_file;

	if ( ! class_exists( $widget_class ) ) {
		return;
	}

	// Register widget.
	add_action(
		'widgets_init',
		function () use ( $widget_class ) {
			register_widget( $widget_class );
		}
	);

	// Unregister widget from the admin area.
	add_action(
		'widgets_init',
		function () use ( $widget_class ) {
			if ( is_admin() ) {
				unregister_widget( $widget_class );
			}
		},
		11
	);
}

/**
 * Register ReadyLaunch widgets
 *
 * @since BuddyBoss 2.9.00
 */
function bb_rl_register_widgets() {
	if ( ! bb_is_readylaunch_enabled() ) {
		return;
	}

	$plugin_dir = BP_PLUGIN_DIR;
	if ( defined( 'BP_SOURCE_SUBDIRECTORY' ) && ! empty( constant( 'BP_SOURCE_SUBDIRECTORY' ) ) ) {
		$plugin_dir = $plugin_dir . 'src';
	}

	// Follow My Network Widget.
	if ( function_exists( 'bp_get_following_ids' ) ) {
		bb_rl_register_single_widget(
			'BB_Core_Follow_My_Network_Widget',
			$plugin_dir . '/bp-core/classes/class-bb-core-follow-my-network-widget.php'
		);
	}

	// Recent Blog Posts Widget.
	bb_rl_register_single_widget(
		'BB_Recent_Blog_Posts_Widget',
		$plugin_dir . '/bp-core/classes/class-bb-recent-blog-posts-widget.php'
	);

	// About Group Widget.
	bb_rl_register_single_widget(
		'BB_Group_About_Widget',
		$plugin_dir . '/bp-core/classes/class-bb-group-about-widget.php'
	);

	// Group Members Widget.
	bb_rl_register_single_widget(
		'BB_Group_Members_Widget',
		$plugin_dir . '/bp-core/classes/class-bb-group-members-widget.php'
	);

	// Connections Widget.
	if ( function_exists( 'friends_get_friend_user_ids' ) ) {
		bb_rl_register_single_widget(
			'BB_Core_Connections_Widget',
			$plugin_dir . '/bp-core/classes/class-bb-core-connections-widget.php'
		);
	}
}

add_action( 'bp_register_widgets', 'bb_rl_register_widgets' );

/**
 * Filter pre-existing widgets.
 *
 * @since BuddyBoss 2.9.00
 *
 * @param array     $instance Widget instance.
 * @param WP_Widget $widget   Widget object.
 * @param array     $args     Widget arguments.
 *
 * @return bool
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

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is properly escaped above
	echo $output; // Output modified widget.
	return false; // Prevent default rendering.
}
add_filter( 'widget_display_callback', 'bb_rl_modify_existing_widget_output', 10, 3 );

/**
 * Open the wrapper of the repeater set - on the View profile screen
 *
 * @since BuddyBoss 2.9.00
 */
function bb_rl_view_profile_repeaters_print_group_html_start() {
	$group_id            = bp_get_the_profile_group_id();
	$is_repeater_enabled = 'on' === BP_XProfile_Group::get_group_meta( $group_id, 'is_repeater_enabled' ) ? true : false;
	if ( $is_repeater_enabled ) {
		global $repeater_set_being_displayed;

		$current_field_id   = bp_get_the_profile_field_id();
		$current_set_number = bp_xprofile_get_meta( $current_field_id, 'field', '_clone_number', true );

		if ( ! empty( $repeater_set_being_displayed ) && $repeater_set_being_displayed !== $current_set_number ) {
			// End of previous set.
			echo "<div class='bb-rl-repeater-separator'></div>";
		}

		$repeater_set_being_displayed = $current_set_number;
	}
}
remove_action( 'bp_before_profile_field_item', 'bp_view_profile_repeaters_print_group_html_start' );
add_action( 'bp_before_profile_field_item', 'bb_rl_view_profile_repeaters_print_group_html_start' );

/**
 * Add social networks button to the member header area.
 *
 * @since BuddyBoss 2.9.00
 *
 * @param int|null $user_id User ID.
 * @return string
 */
function bb_rl_get_user_social_networks_urls( $user_id = null ) {
	$social_networks_id = bb_rl_get_user_social_networks_field_id();

	$html = '';

	$original_option_values = array();

	$user = ( null !== $user_id && $user_id > 0 ) ? $user_id : bp_displayed_user_id();

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
					$key   = bp_social_network_search_key( $key, $providers );
					$html .= '<span class="bb-rl-social ' . esc_attr( $providers[ $key ]->value ) . '"><a target="_blank" data-balloon-pos="up" data-balloon="' . esc_attr( $providers[ $key ]->name ) . '" href="' . esc_url( $original_option_value ) . '"><i class="bb-icons-rl-' . esc_attr( strtolower( $providers[ $key ]->value ) ) . '-logo"></i></a></span>';
				}
			}
		}
	}

	if ( '' !== $html ) {
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
 * Get social network field ID.
 *
 * @since BuddyBoss 2.9.00
 *
 * @return int Social network xProfile field id.
 */
function bb_rl_get_user_social_networks_field_id() {
	global $wpdb, $bp;

	// Check cache first.
	$cache_key = 'bb_rl_social_networks_field_id';
	$field_id  = wp_cache_get( $cache_key );

	if ( false === $field_id ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query to get the social networks field ID.
		$social_networks_field = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT a.id FROM {$wpdb->base_prefix}bp_xprofile_fields a WHERE parent_id = %d AND type = %s",
				0,
				'socialnetworks'
			)
		);
		$field_id              = ! empty( $social_networks_field->id ) ? $social_networks_field->id : 0;

		// Cache the result.
		wp_cache_set( $cache_key, $field_id, '', HOUR_IN_SECONDS );
	}

	return $field_id;
}

/**
 * Clear the cached social networks field ID.
 *
 * @since BuddyBoss 2.9.00
 */
function bb_rl_clear_social_networks_field_cache() {
	wp_cache_delete( 'bb_rl_social_networks_field_id' );
}

/**
 * Clear social networks field cache when field type changes.
 *
 * @since BuddyBoss 2.9.00
 *
 * @param BP_XProfile_Field $field The field object being saved.
 */
function bb_rl_clear_cache_on_field_type_change( $field = null ) {
	// Clear cache if this is a socialnetworks field or if we can't determine the type.
	if ( ! empty( $field->type ) && 'socialnetworks' === $field->type ) {
		bb_rl_clear_social_networks_field_cache();
	}
}

/**
 * Add bb-rl-suggestions to mention selectors
 *
 * @since BuddyBoss 2.9.00
 *
 * @param array $options Mentions options.
 * @return array Modified mentions options.
 */
function bb_rl_add_mentions_selectors( $options ) {
	if ( ! empty( $options['selectors'] ) && is_array( $options['selectors'] ) ) {
		$options['selectors'][] = '.bb-rl-suggestions';
	}
	return $options;
}
add_filter( 'bp_at_mention_js_options', 'bb_rl_add_mentions_selectors' );

// Hook cache clearing to XProfile field changes.
add_action( 'xprofile_field_after_save', 'bb_rl_clear_cache_on_field_type_change' );
add_action( 'xprofile_field_before_delete', 'bb_rl_clear_social_networks_field_cache' );
add_action( 'xprofile_fields_saved_field', 'bb_rl_clear_social_networks_field_cache' );
add_action( 'bp_xprofile_admin_new_field', 'bb_rl_clear_social_networks_field_cache' );
add_action( 'bp_xprofile_admin_edit_field', 'bb_rl_clear_social_networks_field_cache' );

/**
 * Get course category names for a specific course.
 *
 * @since BuddyBoss 2.9.00
 *
 * @param int $course_id Course ID.
 *
 * @return string Course category names separated by comma.
 */
function bb_rl_mpcs_get_course_category_names( $course_id ) {
	$categories = get_the_terms( $course_id, 'mpcs-course-categories' );

	if ( is_wp_error( $categories ) || empty( $categories ) ) {
		return '';
	}

	return implode( ', ', wp_list_pluck( $categories, 'name' ) );
}

if ( ! function_exists( 'bb_rl_get_normalized_file_type' ) ) {
	/**
	 * Get normalized file type from MIME type.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param string $file_type MIME type.
	 *
	 * @return string Normalized file type or empty string if invalid.
	 */
	function bb_rl_get_normalized_file_type( $file_type ) {
		if ( ! $file_type ) {
			return '';
		}

		$parts   = explode( '/', $file_type );
		$subtype = isset( $parts[1] ) ? strtolower( $parts[1] ) : '';

		// Basic normalization for common file types.
		$normalized = array(
			// Images.
			'jpeg' => 'jpeg',
			'jpg'  => 'jpeg',
			'png'  => 'png',
			'gif'  => 'gif',
			'bmp'  => 'bmp',
			'webp' => 'webp',
			'svg'  => 'svg',
			'tiff' => 'tiff',
			'ico'  => 'ico',

			// Video.
			'mp4'  => 'mp4',
			'mov'  => 'mov',
			'mkv'  => 'mkv',
			'avi'  => 'avi',
			'wmv'  => 'wmv',
			'ogg'  => 'ogg',
			'flv'  => 'flv',
			'3gp'  => '3gp',
			'webm' => 'webm',

			// Audio.
			'aac'  => 'aac',
			'flac' => 'flac',
			'midi' => 'midi',
			'wav'  => 'wav',
			'wma'  => 'wma',
			'aiff' => 'aiff',
			'mp3'  => 'mp3',
			'm4a'  => 'm4a',

			// Documents.
			'pdf'  => 'pdf',
			'doc'  => 'doc',
			'docx' => 'docx',
			'xls'  => 'xls',
			'xlsx' => 'xlsx',
			'ppt'  => 'ppt',
			'pptx' => 'pptx',
			'txt'  => 'txt',
			'rtf'  => 'rtf',
			'csv'  => 'csv',
			'xml'  => 'xml',
			'html' => 'html',
			'zip'  => 'zip',
			'rar'  => 'rar',
			'7z'   => '7z',
		);

		return isset( $normalized[ $subtype ] ) ? $normalized[ $subtype ] : $subtype;
	}
}
