<?php
/**
 * BuddyBoss Admin Settings - Custom Group Tabs Panel.
 *
 * Registers the section and field for the Custom Group Tabs side panel under Social
 * Groups. Platform ships only the placeholder: it registers the
 * `bb_group_tabs` custom field type, and BuddyBoss Platform Pro renders the
 * management UI on the `bb_admin_settings_custom_field` filter. When Pro is
 * inactive, the Activation Required CTA is shown as a fallback.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Custom Group Tabs panel section and field.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_groups_register_custom_group_tabs_panel_fields() {

	// SECTION: Custom Group Tabs.
	bb_register_feature_section(
		'groups',
		'custom_group_tabs',
		'custom_group_tabs',
		array(
			'title'       => __( 'Custom Group Tabs', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Custom Group Tabs.
	bb_register_feature_field(
		'groups',
		'custom_group_tabs',
		'custom_group_tabs',
		array(
			'name'       => 'bb_group_tabs',
			'label'      => '',
			'type'       => 'bb_group_tabs',
			'full_width' => true,
			'order'      => 10,
		)
	);

	/**
	 * Fires after Custom Group Tabs section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_groups_settings_after_custom_group_tabs_fields' );
}
