<?php
/**
 * BuddyBoss Admin Settings - Custom Profile Tabs Panel.
 *
 * Registers the section and field for the Custom Profile Tabs side panel under Member
 * Profiles. Platform ships only the placeholder: it registers the
 * `bb_profile_tabs` custom field type, and BuddyBoss Platform Pro renders the
 * management UI on the `bb_admin_settings_custom_field` filter. When Pro is
 * inactive, the Activation Required CTA is shown as a fallback.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Custom Profile Tabs panel section and field.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_members_register_custom_profile_tabs_panel_fields() {

	// SECTION: Custom Profile Tabs.
	bb_register_feature_section(
		'members',
		'custom_profile_tabs',
		'custom_profile_tabs',
		array(
			'title'       => __( 'Custom Profile Tabs', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Custom Profile Tabs.
	bb_register_feature_field(
		'members',
		'custom_profile_tabs',
		'custom_profile_tabs',
		array(
			'name'       => 'bb_profile_tabs',
			'label'      => '',
			'type'       => 'bb_profile_tabs',
			'full_width' => true,
			'order'      => 10,
		)
	);

	/**
	 * Fires after Custom Profile Tabs section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_members_settings_after_custom_profile_tabs_fields' );
}
