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
	$server_max_mb = bb_media_get_server_max_upload_size();

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
			'description'       => __( 'Manage which file extensions are allowed to be uploaded.', 'buddyboss' ),
			'type'              => 'document_extensions',
			'manage_label'      => __( 'Manage', 'buddyboss' ),
			'manage_icon'       => 'bb-icons-rl bb-icons-rl-gear',
			'options'           => bb_media_get_document_extension_options(),
			'extension_data'    => bb_media_get_document_extension_data(),
			'icon_options'      => bb_media_get_extension_icon_options(),
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
	return bb_media_get_extension_options( 'bp_document_extensions_support' );
}

/**
 * Get full document extension data for the document extensions field.
 *
 * Returns the raw extension array so the React UI can display extension
 * details and support adding/removing custom extensions.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array Full extension data keyed by extension ID (e.g., bb_doc_1).
 */
function bb_media_get_document_extension_data() {
	return bb_media_get_extension_data( 'bp_document_extensions_support' );
}

/**
 * Get icon options for the document extension icon dropdown.
 *
 * Uses the existing `bb_document_icon_class` filter to map icons. When ReadyLaunch
 * is enabled, `BB_Readylaunch` hooks this filter to convert `bb-icon-*` to `bb-icons-rl-*`.
 * The filter is only hooked on the old document settings page by default, so this
 * function ensures it is also applied for Settings 2.0 by temporarily adding the hook.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array Array of icon option objects { value, label, icon_class }.
 */
function bb_media_get_extension_icon_options() {
	$is_rl = function_exists( 'bb_is_readylaunch_enabled' ) && bb_is_readylaunch_enabled();

	// Ensure the bb_document_icon_class filter is hooked for Settings 2.0 page.
	// By default BB_Readylaunch only hooks it on the old bp-document settings tab.
	$added_filter = false;
	if ( $is_rl && class_exists( 'BB_Readylaunch' ) && ! has_filter( 'bb_document_icon_class' ) ) {
		add_filter( 'bb_document_icon_class', array( BB_Readylaunch::instance(), 'bb_readylaunch_document_icon_class' ) );
		$added_filter = true;
	}

	if ( ! function_exists( 'bp_document_svg_icon_list' ) ) {
		// Fallback icon set when the document component isn't loaded yet.
		$fallback_icons = array(
			'bb-icon-file'       => __( 'File', 'buddyboss' ),
			'bb-icon-file-zip'   => __( 'Zip', 'buddyboss' ),
			'bb-icon-file-mp3'   => __( 'Mp3', 'buddyboss' ),
			'bb-icon-file-html'  => __( 'Html', 'buddyboss' ),
			'bb-icon-file-psd'   => __( 'Psd', 'buddyboss' ),
			'bb-icon-file-png'   => __( 'Png', 'buddyboss' ),
			'bb-icon-file-pptx'  => __( 'Pptx', 'buddyboss' ),
			'bb-icon-file-xlsx'  => __( 'Xlsx', 'buddyboss' ),
			'bb-icon-file-txt'   => __( 'Txt', 'buddyboss' ),
			'bb-icon-file-video' => __( 'Video', 'buddyboss' ),
		);

		$options = array();
		foreach ( $fallback_icons as $value => $label ) {
			$options[] = array(
				'value'      => $value,
				'label'      => $label,
				'icon_class' => bb_media_format_icon_class( $value ),
			);
		}

		if ( $added_filter ) {
			remove_filter( 'bb_document_icon_class', array( BB_Readylaunch::instance(), 'bb_readylaunch_document_icon_class' ) );
		}

		return $options;
	}

	$icons   = bp_document_svg_icon_list();
	$options = array();

	foreach ( $icons as $icon ) {
		$options[] = array(
			'value'      => $icon['icon'],
			'label'      => $icon['title'],
			'icon_class' => bb_media_format_icon_class( $icon['icon'] ),
		);
	}

	if ( $added_filter ) {
		remove_filter( 'bb_document_icon_class', array( BB_Readylaunch::instance(), 'bb_readylaunch_document_icon_class' ) );
	}

	return $options;
}
