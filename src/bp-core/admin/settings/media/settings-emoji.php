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
			'title'          => __( 'Emoji', 'buddyboss' ),
			'order'          => 10,
			'help_url'       => '636182',
			'section_toggle' => 'bb_media_emoji_support',
		)
	);

	// FIELD: Profiles — emoji support.
	bb_register_feature_field(
		'media',
		'emoji',
		'emoji_settings',
		array(
			'name'              => 'bp_media_profiles_emoji_support',
			'label'             => __( 'Profiles', 'buddyboss' ),
			'description'       => bp_is_active( 'activity' )
				? __( 'Allow members to use emoji in profiles and activity posts', 'buddyboss' )
				: __( 'Allow members to use emoji in profiles', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// FIELD: Groups — emoji support.
	if ( bp_is_active( 'groups' ) ) {
		$group_description = bb_media_get_group_context_description(
			__( 'Allow members to use emoji in', 'buddyboss' )
		);

		bb_register_feature_field(
			'media',
			'emoji',
			'emoji_settings',
			array(
				'name'              => 'bp_media_groups_emoji_support',
				'label'             => __( 'Groups', 'buddyboss' ),
				'description'       => $group_description,
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
				'description'       => __( 'Allow members to use emoji in private messages', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'order'             => 30,
			)
		);
	}

	// FIELD: Forums — emoji support.
	if ( bp_is_active( 'forums' ) ) {
		bb_register_feature_field(
			'media',
			'emoji',
			'emoji_settings',
			array(
				'name'              => 'bp_media_forums_emoji_support',
				'label'             => __( 'Forums', 'buddyboss' ),
				'description'       => __( 'Allow members to use emoji in forum discussions and replies', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'order'             => 40,
			)
		);
	}
}
