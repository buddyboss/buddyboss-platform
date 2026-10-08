<?php
/**
 * BuddyBoss Admin Settings - Profile Search Panel.
 *
 * Registers sections and fields for the Profile Search side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Profile Search panel sections and fields.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_members_register_profile_search_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Profile Search
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'members',
		'profile_search',
		'profile_search',
		array(
			'title'       => __( 'Profile Search', 'buddyboss-platform' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Profile Search (enable/disable).
	// Note: No `invert_value` needed. The DB key `bp-enable-profile-search` stores 1 = enabled (positive).
	// Legacy getter `bp_disable_advanced_profile_search()` inverts on read, but the DB value is positive.
	bb_register_feature_field(
		'members',
		'profile_search',
		'profile_search',
		array(
			'name'              => 'bp-enable-profile-search',
			'label'             => __( 'Profile Search', 'buddyboss-platform' ),
			'type'              => 'toggle',
			'description'       => __( 'Enable profile search', 'buddyboss-platform' ),
			'help_text'         => __( 'When enabled, advanced profile search will be available in the members directory.', 'buddyboss-platform' ),
			'default'           => ! bp_disable_advanced_profile_search(),
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	/**
	 * Fires after Profile Search section fields are registered.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_members_settings_after_profile_search_fields' );
}
