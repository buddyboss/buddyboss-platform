<?php
/**
 * BuddyBoss Admin Settings - Media Security & Performance Panel.
 *
 * Registers sections and fields for the Security & Performance side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Security & Performance panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_media_register_security_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Security Settings
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'media',
		'security_performance',
		'security_settings',
		array(
			'title' => __( 'Security & Performance', 'buddyboss' ),
			'order' => 10,
		)
	);

	// FIELD: Symbolic Links.
	bb_register_feature_field(
		'media',
		'security_performance',
		'security_settings',
		array(
			'name'              => 'bp_media_symlink_support',
			'label'             => __( 'Symbolic Links', 'buddyboss' ),
			'description'       => __( 'Enable symbolic links. If you are having issues with media display, try disabling this option.', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// FIELD: Direct Access — read-only status display.
	bb_register_feature_field(
		'media',
		'security_performance',
		'security_settings',
		array(
			'name'              => 'bp_media_symlink_direct_access',
			'label'             => __( 'Direct Access', 'buddyboss' ),
			'description'       => __( 'Prevent direct access to media files by requiring authentication.', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'order'             => 20,
		)
	);
}
