<?php
/**
 * BuddyBoss Admin Settings - Media Emoji Panel.
 *
 * Registers sections and fields for the Emoji side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Emoji panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_media_register_emoji_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Emoji Settings
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'media',
		'emoji',
		'emoji_settings',
		array(
			'title' => __( 'Emoji', 'buddyboss' ),
			'order' => 10,
		)
	);

	// FIELD: Profiles — emoji support.
	if ( bp_is_active( 'activity' ) ) {
		bb_register_feature_field(
			'media',
			'emoji',
			'emoji_settings',
			array(
				'name'              => 'bp_media_profiles_emoji_support',
				'label'             => __( 'Profiles', 'buddyboss' ),
				'description'       => __( 'Enable emoji support in profiles and activity posts', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'order'             => 10,
			)
		);
	}

	// FIELD: Groups — emoji support.
	if ( bp_is_active( 'groups' ) && bp_is_active( 'activity' ) ) {
		bb_register_feature_field(
			'media',
			'emoji',
			'emoji_settings',
			array(
				'name'              => 'bp_media_groups_emoji_support',
				'label'             => __( 'Groups', 'buddyboss' ),
				'description'       => __( 'Enable emoji support in groups', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'order'             => 20,
			)
		);
	}

	// FIELD: Messages — emoji support.
	if ( bp_is_active( 'messages' ) ) {
		bb_register_feature_field(
			'media',
			'emoji',
			'emoji_settings',
			array(
				'name'              => 'bp_media_messages_emoji_support',
				'label'             => __( 'Messages', 'buddyboss' ),
				'description'       => __( 'Enable emoji support in private messages', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'order'             => 30,
			)
		);
	}

	// FIELD: Forums — emoji support.
	if ( bp_is_active( 'forums' ) && bp_is_active( 'activity' ) ) {
		bb_register_feature_field(
			'media',
			'emoji',
			'emoji_settings',
			array(
				'name'              => 'bp_media_forums_emoji_support',
				'label'             => __( 'Forums', 'buddyboss' ),
				'description'       => __( 'Enable emoji support in forum discussions and replies', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'order'             => 40,
			)
		);
	}
}
