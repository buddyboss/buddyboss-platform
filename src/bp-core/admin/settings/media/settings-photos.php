<?php
/**
 * BuddyBoss Admin Settings - Media Photos Panel.
 *
 * Registers sections and fields for the Photos side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Photos panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_media_register_photos_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Photos Settings
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'media',
		'photos',
		'photos_settings',
		array(
			'title'          => __( 'Photos', 'buddyboss' ),
			'order'          => 10,
			'section_toggle' => 'bp_media_photos_support',
		)
	);

	// FIELD: Profiles — photos support.
	bb_register_feature_field(
		'media',
		'photos',
		'photos_settings',
		array(
			'name'              => 'bp_media_profile_media_support',
			'label'             => __( 'Profiles', 'buddyboss' ),
			'description'       => bp_is_active( 'activity' )
				? __( 'Allow members to upload photos in profiles and activity posts', 'buddyboss' )
				: __( 'Allow members to upload photos in profiles', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// FIELD: Profile Albums — conditional on profile media support.
	bb_register_feature_field(
		'media',
		'photos',
		'photos_settings',
		array(
			'name'              => 'bp_media_profile_albums_support',
			'label'             => '',
			'description'       => __( 'Enable albums in profiles', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'order'             => 20,
			'parent_field'      => 'bp_media_profile_media_support',
			'conditional'       => array(
				'field' => 'bp_media_profile_media_support',
				'value' => 1,
			),
		)
	);

	// FIELD: Groups — photos support (conditional on groups component).
	if ( bp_is_active( 'groups' ) ) {
		// Build description dynamically based on active components (mirrors legacy settings).
		$group_contexts = array( __( 'groups', 'buddyboss' ) );

		if ( bp_is_active( 'activity' ) ) {
			$group_contexts[] = __( 'activity posts', 'buddyboss' );
		}

		if ( bp_is_active( 'messages' ) && true === bp_disable_group_messages() ) {
			$group_contexts[] = __( 'messages', 'buddyboss' );
		}

		if ( bp_is_active( 'forums' ) ) {
			$group_contexts[] = __( 'forums', 'buddyboss' );
		}

		$group_description = bb_media_build_context_description(
			__( 'Allow members to upload photos in', 'buddyboss' ),
			$group_contexts
		);

		bb_register_feature_field(
			'media',
			'photos',
			'photos_settings',
			array(
				'name'              => 'bp_media_group_media_support',
				'label'             => __( 'Groups', 'buddyboss' ),
				'description'       => $group_description,
				'type'              => 'toggle',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'order'             => 30,
			)
		);

		// FIELD: Group Albums — conditional on group media support.
		bb_register_feature_field(
			'media',
			'photos',
			'photos_settings',
			array(
				'name'              => 'bp_media_group_albums_support',
				'label'             => '',
				'description'       => __( 'Enable albums in groups', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'order'             => 40,
				'parent_field'      => 'bp_media_group_media_support',
				'conditional'       => array(
					'field' => 'bp_media_group_media_support',
					'value' => 1,
				),
			)
		);
	}

	// FIELD: Messages — photos support.
	if ( bp_is_active( 'messages' ) ) {
		bb_register_feature_field(
			'media',
			'photos',
			'photos_settings',
			array(
				'name'              => 'bp_media_messages_media_support',
				'label'             => __( 'Messages', 'buddyboss' ),
				'description'       => __( 'Allow members to upload photos in private messages', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'order'             => 50,
			)
		);
	}

	// FIELD: Forums — photos support.
	if ( bp_is_active( 'forums' ) ) {
		bb_register_feature_field(
			'media',
			'photos',
			'photos_settings',
			array(
				'name'              => 'bp_media_forums_media_support',
				'label'             => __( 'Forums', 'buddyboss' ),
				'description'       => __( 'Allow members to upload photos in forum discussions and replies', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'order'             => 60,
			)
		);
	}

	// Get server max upload size for description text.
	$server_max_mb = function_exists( 'bp_media_format_size_units' )
		? (int) bp_media_format_size_units( bp_core_upload_max_size(), false, 'MB' )
		: (int) ( wp_max_upload_size() / ( 1024 * 1024 ) );

	// FIELD: Upload Size.
	bb_register_feature_field(
		'media',
		'photos',
		'photos_settings',
		array(
			'name'              => 'bp_media_allowed_size',
			'label'             => __( 'Upload Size', 'buddyboss' ),
			'description'       => sprintf(
				/* translators: %d: Server max upload size in MB */
				__( 'Set max file size for photo uploads, in megabytes. Your server\'s maximum upload size is %d MB.', 'buddyboss' ),
				$server_max_mb
			),
			'type'              => 'number',
			'default'           => $server_max_mb,
			'suffix'            => __( 'MB', 'buddyboss' ),
			'sanitize_callback' => 'bb_media_sanitize_upload_size',
			'order'             => 70,
		)
	);

	// FIELD: Upload Limit.
	bb_register_feature_field(
		'media',
		'photos',
		'photos_settings',
		array(
			'name'              => 'bp_media_allowed_per_batch',
			'label'             => __( 'Upload Limit', 'buddyboss' ),
			'description'       => __( 'Set max number of images that can be added to one activity post or photo upload.', 'buddyboss' ),
			'type'              => 'number',
			'default'           => 10,
			'suffix'            => __( 'per batch', 'buddyboss' ),
			'sanitize_callback' => 'bb_media_sanitize_upload_limit',
			'order'             => 80,
		)
	);
}
