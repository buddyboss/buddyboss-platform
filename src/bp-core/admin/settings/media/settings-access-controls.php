<?php
/**
 * BuddyBoss Admin Settings - Media Access Controls Panel.
 *
 * Registers sections and fields for the Access Controls side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Access Controls panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_media_register_access_controls_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Access Controls Settings
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'media',
		'access_controls',
		'access_controls_settings',
		array(
			'title'       => __( 'Access Controls', 'buddyboss' ),
			'description' => __( 'These settings do not apply to administrators.', 'buddyboss' ),
			'order'       => 10,
		)
	);

	// FIELD: Upload Photos access.
	bb_register_feature_field(
		'media',
		'access_controls',
		'access_controls_settings',
		array(
			'name'              => 'bp_media_upload_photos_access',
			'label'             => __( 'Upload Photos', 'buddyboss' ),
			'description'       => __( 'Select who can upload photos.', 'buddyboss' ),
			'type'              => 'toggle_list',
			'options'           => bb_media_get_access_control_options(),
			'default'           => array(),
			'sanitize_callback' => 'bb_media_sanitize_access_controls',
			'order'             => 10,
		)
	);

	// FIELD: Upload Videos access.
	bb_register_feature_field(
		'media',
		'access_controls',
		'access_controls_settings',
		array(
			'name'              => 'bp_media_upload_videos_access',
			'label'             => __( 'Upload Videos', 'buddyboss' ),
			'description'       => __( 'Select who can upload videos.', 'buddyboss' ),
			'type'              => 'toggle_list',
			'options'           => bb_media_get_access_control_options(),
			'default'           => array(),
			'sanitize_callback' => 'bb_media_sanitize_access_controls',
			'order'             => 20,
		)
	);

	// FIELD: Upload Documents access.
	bb_register_feature_field(
		'media',
		'access_controls',
		'access_controls_settings',
		array(
			'name'              => 'bp_media_upload_documents_access',
			'label'             => __( 'Upload Documents', 'buddyboss' ),
			'description'       => __( 'Select who can upload documents.', 'buddyboss' ),
			'type'              => 'toggle_list',
			'options'           => bb_media_get_access_control_options(),
			'default'           => array(),
			'sanitize_callback' => 'bb_media_sanitize_access_controls',
			'order'             => 30,
		)
	);
}

/**
 * Build access control options for role/profile type toggle_list fields.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array Toggle list options for WordPress roles.
 */
function bb_media_get_access_control_options() {
	$wp_roles = wp_roles();
	$options   = array();

	foreach ( $wp_roles->roles as $role_key => $role ) {
		// Skip administrator — access controls don't apply to admins.
		if ( 'administrator' === $role_key ) {
			continue;
		}
		$options[] = array(
			'label' => translate_user_role( $role['name'] ),
			'value' => $role_key,
		);
	}

	return $options;
}
