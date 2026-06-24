<?php
/**
 * BuddyBoss Admin Settings - Media Animated GIFs Panel.
 *
 * Registers sections and fields for the Animated GIFs side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Animated GIFs panel sections and fields.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_media_register_gifs_panel_fields() {

	// Determine GIPHY connection status for section heading badge (admin only to avoid frontend HTTP calls).
	$is_giphy_connected = is_admin() && function_exists( 'bb_check_valid_giphy_api_key' ) && bb_check_valid_giphy_api_key();

	// -------------------------------------------------------------------------
	// SECTION: GIF Settings
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'media',
		'animated_gifs',
		'gifs_settings',
		array(
			'title'          => __( 'Animated GIFs', 'buddyboss-platform' ),
			'order'          => 10,
			'help_url'       => '636184',
			'section_toggle' => 'bb_media_gif_support',
			'status'         => array(
				'type' => $is_giphy_connected ? 'success' : 'warning',
				'text' => $is_giphy_connected
					? __( 'Connected', 'buddyboss-platform' )
					: __( 'Not Connected', 'buddyboss-platform' ),
			),
		)
	);

	// FIELD: GIPHY API Key.
	bb_register_feature_field(
		'media',
		'animated_gifs',
		'gifs_settings',
		array(
			'name'              => 'bp_media_gif_api_key',
			'label'             => __( 'GIPHY API Key', 'buddyboss-platform' ),
			'description'       => sprintf(
				/* translators: 1: Opening anchor tag for GIPHY account link, 2: Closing anchor tag. */
				__( 'To use this feature, sign in to your %1$sGIPHY account%2$s, create an app, and paste your API key in the field above.', 'buddyboss-platform' ),
				'<a href="https://developers.giphy.com/" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
			'type'              => 'input_button',
			'default'           => '',
			'sanitize_callback' => 'bb_media_sanitize_gif_api_key',
			'placeholder'       => __( 'Enter API key to connect', 'buddyboss-platform' ),
			'button_label'      => $is_giphy_connected
				? __( 'Disconnect', 'buddyboss-platform' )
				: __( 'Connect', 'buddyboss-platform' ),
			'is_connected'      => $is_giphy_connected,
			'order'             => 10,
		)
	);

	// FIELD: Profiles — gif support.
	bb_register_feature_field(
		'media',
		'animated_gifs',
		'gifs_settings',
		array(
			'name'              => 'bp_media_profiles_gif_support',
			'label'             => __( 'Profiles', 'buddyboss-platform' ),
			'description'       => bp_is_active( 'activity' )
				? __( 'Allow members to use animated GIFs in profiles and activity posts', 'buddyboss-platform' )
				: __( 'Allow members to use animated GIFs in profiles', 'buddyboss-platform' ),
			'type'              => 'toggle',
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'order'             => 20,
		)
	);

	// FIELD: Groups — gif support.
	if ( bp_is_active( 'groups' ) ) {
		$group_description = bb_media_get_group_context_description(
			__( 'Allow members to use animated GIFs in', 'buddyboss-platform' )
		);

		bb_register_feature_field(
			'media',
			'animated_gifs',
			'gifs_settings',
			array(
				'name'              => 'bp_media_groups_gif_support',
				'label'             => __( 'Groups', 'buddyboss-platform' ),
				'description'       => $group_description,
				'type'              => 'toggle',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'order'             => 30,
			)
		);
	}

	// FIELD: Messages — gif support.
	if ( bp_is_active( 'messages' ) ) {
		bb_register_feature_field(
			'media',
			'animated_gifs',
			'gifs_settings',
			array(
				'name'              => 'bp_media_messages_gif_support',
				'label'             => __( 'Messages', 'buddyboss-platform' ),
				'description'       => __( 'Allow members to use animated GIFs in private messages', 'buddyboss-platform' ),
				'type'              => 'toggle',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'order'             => 40,
			)
		);
	}

	// FIELD: Forums — gif support.
	if ( bp_is_active( 'forums' ) ) {
		bb_register_feature_field(
			'media',
			'animated_gifs',
			'gifs_settings',
			array(
				'name'              => 'bp_media_forums_gif_support',
				'label'             => __( 'Forums', 'buddyboss-platform' ),
				'description'       => __( 'Allow members to use animated GIFs in forum discussions and replies', 'buddyboss-platform' ),
				'type'              => 'toggle',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'order'             => 50,
			)
		);
	}
}
