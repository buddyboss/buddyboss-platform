<?php
/**
 * BuddyBoss Admin Settings - Media Videos Panel.
 *
 * Registers sections and fields for the Videos side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Videos panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_media_register_videos_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Videos Settings
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'media',
		'videos',
		'videos_settings',
		array(
			'title'          => __( 'Videos', 'buddyboss' ),
			'order'          => 10,
			'help_url'       => '636178',
			'section_toggle' => 'bb_media_videos_support',
		)
	);

	// FIELD: FFmpeg status notice — auto-checks server FFmpeg availability on mount.
	bb_register_feature_field(
		'media',
		'videos',
		'videos_settings',
		array(
			'name'              => 'bp_video_ffmpeg_notice',
			'label'             => '',
			'type'              => 'status_check',
			'default'           => '',
			'ajax_action'       => 'bb_media_check_ffmpeg_status',
			'full_width'        => true,
			'sanitize_callback' => '__return_empty_string',
			'order'             => 1,
		)
	);

	// FIELD: Profiles — video support.
	bb_register_feature_field(
		'media',
		'videos',
		'videos_settings',
		array(
			'name'              => 'bp_video_profile_video_support',
			'label'             => __( 'Profiles', 'buddyboss' ),
			'description'       => bp_is_active( 'activity' )
				? __( 'Allow members to upload videos in profiles and activity posts', 'buddyboss' )
				: __( 'Allow members to upload videos in profiles', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// FIELD: Groups — video support.
	if ( bp_is_active( 'groups' ) ) {
		$group_description = bb_media_get_group_context_description(
			__( 'Allow members to upload videos in', 'buddyboss' )
		);

		bb_register_feature_field(
			'media',
			'videos',
			'videos_settings',
			array(
				'name'              => 'bp_video_group_video_support',
				'label'             => __( 'Groups', 'buddyboss' ),
				'description'       => $group_description,
				'type'              => 'toggle',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'order'             => 20,
			)
		);
	}

	// FIELD: Messages — video support.
	if ( bp_is_active( 'messages' ) ) {
		bb_register_feature_field(
			'media',
			'videos',
			'videos_settings',
			array(
				'name'              => 'bp_video_messages_video_support',
				'label'             => __( 'Messages', 'buddyboss' ),
				'description'       => __( 'Allow members to upload videos in private messages', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'order'             => 30,
			)
		);
	}

	// FIELD: Forums — video support.
	if ( bp_is_active( 'forums' ) ) {
		bb_register_feature_field(
			'media',
			'videos',
			'videos_settings',
			array(
				'name'              => 'bp_video_forums_video_support',
				'label'             => __( 'Forums', 'buddyboss' ),
				'description'       => __( 'Allow members to upload videos in forum discussions and replies', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'order'             => 40,
			)
		);
	}

	// Get server max upload size for description text.
	$server_max_mb = bb_media_get_server_max_upload_size();

	// FIELD: Upload Size (video).
	bb_register_feature_field(
		'media',
		'videos',
		'videos_settings',
		array(
			'name'              => 'bp_video_allowed_size',
			'label'             => __( 'Upload Size', 'buddyboss' ),
			'description'       => sprintf(
				/* translators: %d: Server max upload size in MB */
				__( 'Set max file size for video uploads, in megabytes. Your server\'s maximum upload size is %d MB.', 'buddyboss' ),
				$server_max_mb
			),
			'type'              => 'number',
			'default'           => $server_max_mb,
			'suffix'            => __( 'MB', 'buddyboss' ),
			'sanitize_callback' => 'bb_media_sanitize_upload_size',
			'order'             => 50,
		)
	);

	// FIELD: Upload Limit (video).
	bb_register_feature_field(
		'media',
		'videos',
		'videos_settings',
		array(
			'name'              => 'bp_video_allowed_per_batch',
			'label'             => __( 'Upload Limit', 'buddyboss' ),
			'description'       => __( 'Set max number of videos that can be added to one activity post or video upload.', 'buddyboss' ),
			'type'              => 'number',
			'default'           => 10,
			'suffix'            => __( 'per batch', 'buddyboss' ),
			'sanitize_callback' => 'bb_media_sanitize_upload_limit',
			'order'             => 60,
		)
	);

	// FIELD: File Extensions (video).
	bb_register_feature_field(
		'media',
		'videos',
		'videos_settings',
		array(
			'name'              => 'bp_video_extensions_support',
			'label'             => __( 'File Extensions', 'buddyboss' ),
			'description'       => __( 'Enable the video file types that users are allowed to upload. For best performance and compatibility, we recommend MP4 and WebM.', 'buddyboss' ),
			'type'              => 'toggle_list',
			'options'           => bb_media_get_video_extension_options(),
			'default'           => array(),
			'sanitize_callback' => 'bb_media_sanitize_video_extensions',
			'allow_add'         => true,
			'add_button_label'  => __( 'Add Extension', 'buddyboss' ),
			'extension_data'    => bb_media_get_video_extension_data(),
			'order'             => 70,
		)
	);
}

/**
 * Build video extension options for toggle_list field.
 *
 * Reads directly from the option to avoid calling template-layer functions
 * (bp_video_allowed_video_type) that may not be loaded yet during feature registration.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array Toggle list options from stored or default video extensions.
 */
function bb_media_get_video_extension_options() {
	return bb_media_get_extension_options( 'bp_video_extensions_support', true );
}

/**
 * Get full video extension data for the extension list field.
 *
 * Returns the raw extension array so the React UI can display extension
 * details and support adding new custom extensions.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array Full extension data keyed by extension ID (e.g., bb_vid_0).
 */
function bb_media_get_video_extension_data() {
	return bb_media_get_extension_data( 'bp_video_extensions_support' );
}

