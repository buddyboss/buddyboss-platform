<?php
/**
 * BuddyBoss Admin Settings - Media Access Controls Panel.
 *
 * Registers sections and fields for the Access Controls side panel.
 *
 * All access-control logic lives in this file so it can be easily
 * extended by Pro. Pro populates the actual data (types, options)
 * via PHP filters.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Access Controls panel sections and fields.
 *
 * Uses the `access_control` field type so Pro can populate dropdown
 * types (WordPress Role, Profile Type, Membership, etc.) via the
 * `bb_access_control_field_data` filter.
 *
 * Option keys match the legacy Pro option names for backward compatibility:
 * - bb-access-control-upload-media
 * - bb-access-control-upload-video
 * - bb-access-control-upload-document
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_media_register_access_controls_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Media Access.
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'media',
		'access_controls',
		'media_access_controls',
		array(
			'title' => __( 'Media Access', 'buddyboss' ),
			'order' => 10,
		)
	);

	// FIELD: Upload Photos access control.
	bb_register_feature_field(
		'media',
		'access_controls',
		'media_access_controls',
		array(
			'name'              => 'bb-access-control-upload-media',
			'label'             => __( 'Upload Photos', 'buddyboss' ),
			'type'              => 'access_control',
			'description'       => __( 'Select which members can upload photos based on:', 'buddyboss' ),
			'default'           => '',
			'pro_only'          => true,
			'order'             => 10,
			'sanitize_callback' => 'bb_sanitize_access_control_field',
		)
	);

	// FIELD: Upload Videos access control.
	bb_register_feature_field(
		'media',
		'access_controls',
		'media_access_controls',
		array(
			'name'              => 'bb-access-control-upload-video',
			'label'             => __( 'Upload Videos', 'buddyboss' ),
			'type'              => 'access_control',
			'description'       => __( 'Select which members can upload videos based on:', 'buddyboss' ),
			'default'           => '',
			'pro_only'          => true,
			'order'             => 20,
			'sanitize_callback' => 'bb_sanitize_access_control_field',
		)
	);

	// FIELD: Upload Documents access control.
	bb_register_feature_field(
		'media',
		'access_controls',
		'media_access_controls',
		array(
			'name'              => 'bb-access-control-upload-document',
			'label'             => __( 'Upload Documents', 'buddyboss' ),
			'type'              => 'access_control',
			'description'       => __( 'Select which members can upload documents based on:', 'buddyboss' ),
			'default'           => '',
			'pro_only'          => true,
			'order'             => 30,
			'sanitize_callback' => 'bb_sanitize_access_control_field',
		)
	);

	// FIELD: Admin notice (displayed once at the end of the section).
	bb_register_feature_field(
		'media',
		'access_controls',
		'media_access_controls',
		array(
			'name'        => 'bb-media-access-control-notice',
			'label'       => '',
			'type'        => 'notice',
			'description' => __( 'These settings do not apply to administrators.', 'buddyboss' ),
			'notice_type' => 'info',
			'order'       => 100,
		)
	);

	/**
	 * Fires after the core Media access-control fields are registered.
	 *
	 * Pro or third-party plugins can hook here to register additional
	 * access-control fields in the same side panel.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_media_access_control_after_register_fields' );
}
