<?php
/**
 * BuddyBoss Admin Settings - Profile Types Panel.
 *
 * Registers sections and fields for the Profile Types side panel.
 * Legacy: BP_Admin_Setting_Xprofile::register_fields() — Profile Types section.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Profile Types panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_members_register_profile_types_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Profile Types Settings
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'members',
		'profile_types',
		'profile_types_settings',
		array(
			'title'       => __( 'Profile Type Settings', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Profile Types (enable/disable).
	// Legacy: bp_admin_setting_callback_member_type_enable_disable().
	bb_register_feature_field(
		'members',
		'profile_types',
		'profile_types_settings',
		array(
			'name'              => 'bp-member-type-enable-disable',
			'label'             => __( 'Profile Types', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Enable profile types', 'buddyboss' ),
			'help_text'         => __( 'When enabled, profile types allow you to assign unique profile fields and permissions to different member types.', 'buddyboss' ),
			'default'           => bp_member_type_enable_disable(),
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// FIELD: Display Profile Types (conditional — only when profile types enabled).
	// Legacy: bp_admin_setting_callback_member_type_display_on_profile().
	bb_register_feature_field(
		'members',
		'profile_types',
		'profile_types_settings',
		array(
			'name'              => 'bp-member-type-display-on-profile',
			'label'             => __( 'Display Profile Types', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Display profile type on member profiles', 'buddyboss' ),
			'default'           => bp_member_type_display_on_profile(),
			'sanitize_callback' => 'absint',
			'conditional'       => array(
				'field' => 'bp-member-type-enable-disable',
				'value' => true,
			),
			'order'             => 20,
		)
	);

	// FIELD: Default Profile Type (select — only when profile types enabled).
	// Legacy: bp_admin_setting_callback_member_type_default_on_registration().
	// Options are empty at registration time — bp_get_active_member_types() runs a
	// WP_Query that should not execute on every admin page load. Real options are
	// injected at AJAX time via bb_members_enrich_profile_type_options().
	bb_register_feature_field(
		'members',
		'profile_types',
		'profile_types_settings',
		array(
			'name'              => 'bp-member-type-default-on-registration',
			'label'             => __( 'Default Profile Type', 'buddyboss' ),
			'type'              => 'select',
			'help_text'         => sprintf(
				/* translators: %s: Repair Community link. */
				__( 'Set a default profile type for new users. Use <a href="%s">Repair Community</a> tools to assign it to existing users.', 'buddyboss' ),
				esc_url(
					add_query_arg(
						array(
							'page' => 'bp-tools',
							'tab'  => 'bp-tools',
							'tool' => 'bp-assign-member-type',
						),
						admin_url( 'admin.php' )
					)
				)
			),
			'default'           => bp_member_type_default_on_registration(),
			'sanitize_callback' => 'sanitize_key',
			'options'           => array(), // Populated at AJAX time by bb_members_enrich_profile_type_options().
			'conditional'       => array(
				'field' => 'bp-member-type-enable-disable',
				'value' => true,
			),
			'order'             => 30,
		)
	);

	/**
	 * Fires after Profile Types settings section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_members_settings_after_profile_types_fields' );
}
