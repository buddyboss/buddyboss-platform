<?php
/**
 * BuddyBoss Admin Settings - Media Documents Panel.
 *
 * Registers sections and fields for the Documents side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Documents panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_media_register_documents_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Documents Settings
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'media',
		'documents',
		'documents_settings',
		array(
			'title'          => __( 'Documents', 'buddyboss' ),
			'order'          => 10,
			'section_toggle' => 'bp_media_documents_support',
		)
	);

	// FIELD: Profiles — document support.
	bb_register_feature_field(
		'media',
		'documents',
		'documents_settings',
		array(
			'name'              => 'bp_media_profile_document_support',
			'label'             => __( 'Profiles', 'buddyboss' ),
			'description'       => bp_is_active( 'activity' )
				? __( 'Allow members to upload documents in profiles and activity posts', 'buddyboss' )
				: __( 'Allow members to upload documents in profiles', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// FIELD: Groups — document support.
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
			__( 'Allow members to upload documents in', 'buddyboss' ),
			$group_contexts
		);

		bb_register_feature_field(
			'media',
			'documents',
			'documents_settings',
			array(
				'name'              => 'bp_media_group_document_support',
				'label'             => __( 'Groups', 'buddyboss' ),
				'description'       => $group_description,
				'type'              => 'toggle',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'order'             => 20,
			)
		);
	}

	// FIELD: Messages — document support.
	if ( bp_is_active( 'messages' ) ) {
		bb_register_feature_field(
			'media',
			'documents',
			'documents_settings',
			array(
				'name'              => 'bp_media_messages_document_support',
				'label'             => __( 'Messages', 'buddyboss' ),
				'description'       => __( 'Allow members to upload documents in private messages', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'order'             => 30,
			)
		);
	}

	// FIELD: Forums — document support.
	if ( bp_is_active( 'forums' ) ) {
		bb_register_feature_field(
			'media',
			'documents',
			'documents_settings',
			array(
				'name'              => 'bp_media_forums_document_support',
				'label'             => __( 'Forums', 'buddyboss' ),
				'description'       => __( 'Allow members to upload documents in forum discussions and replies', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'order'             => 40,
			)
		);
	}

	// Get server max upload size for description text.
	$server_max_mb = function_exists( 'bp_media_format_size_units' )
		? (int) bp_media_format_size_units( bp_core_upload_max_size(), false, 'MB' )
		: (int) ( wp_max_upload_size() / ( 1024 * 1024 ) );

	// FIELD: Upload Size (document).
	bb_register_feature_field(
		'media',
		'documents',
		'documents_settings',
		array(
			'name'              => 'bp_document_allowed_size',
			'label'             => __( 'Upload Size', 'buddyboss' ),
			'description'       => sprintf(
				/* translators: %d: Server max upload size in MB */
				__( 'Set max file size for document uploads, in megabytes. Your server\'s maximum upload size is %d MB.', 'buddyboss' ),
				$server_max_mb
			),
			'type'              => 'number',
			'default'           => 100,
			'suffix'            => __( 'MB', 'buddyboss' ),
			'sanitize_callback' => 'bb_media_sanitize_upload_size',
			'order'             => 50,
		)
	);

	// FIELD: Upload Limit (document).
	bb_register_feature_field(
		'media',
		'documents',
		'documents_settings',
		array(
			'name'              => 'bp_document_allowed_per_batch',
			'label'             => __( 'Upload Limit', 'buddyboss' ),
			'description'       => __( 'Set max number of documents that can be added to one activity post or document upload.', 'buddyboss' ),
			'type'              => 'number',
			'default'           => 10,
			'suffix'            => __( 'per batch', 'buddyboss' ),
			'sanitize_callback' => 'bb_media_sanitize_upload_limit',
			'order'             => 60,
		)
	);

	// FIELD: File Extensions (document).
	bb_register_feature_field(
		'media',
		'documents',
		'documents_settings',
		array(
			'name'              => 'bp_document_extensions_support',
			'label'             => __( 'File Extensions', 'buddyboss' ),
			'description'       => __( 'Select which document file extensions are allowed for upload.', 'buddyboss' ),
			'type'              => 'toggle_list',
			'options'           => bb_media_get_document_extension_options(),
			'default'           => array(),
			'sanitize_callback' => 'bb_media_sanitize_extensions',
			'order'             => 70,
		)
	);
}

/**
 * Build document extension options for toggle_list field.
 *
 * Reads directly from the option to avoid calling template-layer functions
 * (bp_media_allowed_document_type) that may not be loaded yet during feature registration.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array Toggle list options from stored or default document extensions.
 */
function bb_media_get_document_extension_options() {
	$extensions = bp_get_option( 'bp_document_extensions_support', array() );

	$options = array();
	foreach ( $extensions as $key => $ext ) {
		if ( ! is_array( $ext ) || empty( $ext['extension'] ) ) {
			continue;
		}
		$options[] = array(
			'label' => ! empty( $ext['description'] ) ? $ext['extension'] . ' (' . $ext['description'] . ')' : $ext['extension'],
			'value' => $key,
		);
	}

	return $options;
}
