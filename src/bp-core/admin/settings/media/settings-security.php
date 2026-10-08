<?php
/**
 * BuddyBoss Admin Settings - Media Security & Performance Panel.
 *
 * Registers sections and fields for the Security & Performance side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Security & Performance panel sections and fields.
 *
 * Replicates the legacy Settings 1.0 behavior with server-side checks for
 * symbolic link support and direct access status.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_media_register_security_panel_fields() {

	// Determine server-side states for Symbolic Links field.
	$is_offloaded        = (bool) apply_filters( 'bb_media_offload_delivered', false );
	$is_symlink_disabled = function_exists( 'bb_check_server_disabled_symlink' ) && bb_check_server_disabled_symlink();

	// -------------------------------------------------------------------------
	// SECTION: Security Settings
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'media',
		'security_performance',
		'security_settings',
		array(
			'title' => __( 'Media Security & Performance', 'buddyboss-platform' ),
			'order' => 10,
		)
	);

	// FIELD: Symbolic Links toggle.
	bb_register_feature_field(
		'media',
		'security_performance',
		'security_settings',
		array(
			'name'              => 'bp_media_symlink_support',
			'label'             => __( 'Symbolic Links', 'buddyboss-platform' ),
			'description'       => __( 'Enable symbolic links', 'buddyboss-platform' ),
			'help_text'         => __( 'Symbolic links create secure "shortcuts" to media files uploaded by members, optimizing performance. If disabled, a fallback method will protect your files. If you experience media display issues, try turning off this option.', 'buddyboss-platform' ),
			'type'              => 'toggle',
			'default'           => 0,
			'sanitize_callback' => 'bb_media_sanitize_symlink_support',
			'disabled'          => $is_offloaded || $is_symlink_disabled,
			'group'             => 'symlink_settings',
			'order'             => 10,
		)
	);

	// FIELD: Symbolic Links status notice — auto-checks on mount and toggle change.
	bb_register_feature_field(
		'media',
		'security_performance',
		'security_settings',
		array(
			'name'              => 'bp_media_symlink_notice',
			'label'             => '',
			'type'              => 'status_check',
			'default'           => '',
			'ajax_action'       => 'bb_media_check_symlink_status',
			'watch_field'       => 'bp_media_symlink_support',
			'group'             => 'symlink_settings',
			'sanitize_callback' => '__return_empty_string',
			'order'             => 15,
		)
	);

	// FIELD: Direct Access — auto-check status (not a saveable option).
	// Runs the AJAX check on page load and when Symbolic Links toggle changes.
	bb_register_feature_field(
		'media',
		'security_performance',
		'security_settings',
		array(
			'name'              => 'bp_media_symlink_direct_access',
			'label'             => __( 'Direct Links', 'buddyboss-platform' ),
			'description'       => sprintf(
				/* translators: 1: Opening anchor tag for Media Permissions link, 2: Closing anchor tag. */
				__( 'If our plugin is unable to automatically block direct access to your media files and folders, please follow the steps in our %1$sMedia Permissions%2$s tutorial to configure your server.', 'buddyboss-platform' ),
				'<a href="https://www.buddyboss.com/resources/docs/components/media/media-permissions/" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
			'type'              => 'status_check',
			'default'           => '',
			'ajax_action'       => 'bb_media_check_direct_access',
			'watch_field'       => 'bp_media_symlink_support',
			'sanitize_callback' => '__return_empty_string',
			'order'             => 20,
		)
	);
}
