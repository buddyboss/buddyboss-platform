<?php
/**
 * BuddyBoss Admin Settings - Media Animated GIFs Panel.
 *
 * Registers sections and fields for the Animated GIFs side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Animated GIFs panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_media_register_gifs_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: GIF Settings
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'media',
		'animated_gifs',
		'gifs_settings',
		array(
			'title' => __( 'Animated GIFs', 'buddyboss' ),
			'order' => 10,
		)
	);

	// FIELD: GIPHY API Key.
	bb_register_feature_field(
		'media',
		'animated_gifs',
		'gifs_settings',
		array(
			'name'              => 'bp_media_gif_api_key',
			'label'             => __( 'GIPHY API Key', 'buddyboss' ),
			'description'       => __( 'Enter your GIPHY API key to enable animated GIF support. You can get a free API key at developers.giphy.com.', 'buddyboss' ),
			'type'              => 'text',
			'default'           => '',
			'sanitize_callback' => 'bb_media_sanitize_gif_api_key',
			'order'             => 10,
		)
	);

	// GIF toggles — conditional on activity being active.
	if ( bp_is_active( 'activity' ) ) {

		// FIELD: Profiles — gif support.
		bb_register_feature_field(
			'media',
			'animated_gifs',
			'gifs_settings',
			array(
				'name'              => 'bp_media_profiles_gif_support',
				'label'             => __( 'Profiles', 'buddyboss' ),
				'description'       => __( 'Enable animated GIF support in profiles and activity posts', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'order'             => 20,
			)
		);

		// FIELD: Groups — gif support.
		if ( bp_is_active( 'groups' ) ) {
			bb_register_feature_field(
				'media',
				'animated_gifs',
				'gifs_settings',
				array(
					'name'              => 'bp_media_groups_gif_support',
					'label'             => __( 'Groups', 'buddyboss' ),
					'description'       => __( 'Enable animated GIF support in groups', 'buddyboss' ),
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
					'label'             => __( 'Messages', 'buddyboss' ),
					'description'       => __( 'Enable animated GIF support in private messages', 'buddyboss' ),
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
					'label'             => __( 'Forums', 'buddyboss' ),
					'description'       => __( 'Enable animated GIF support in forum discussions and replies', 'buddyboss' ),
					'type'              => 'toggle',
					'default'           => 0,
					'sanitize_callback' => 'absint',
					'order'             => 50,
				)
			);
		}
	}
}
