<?php
/**
 * BuddyBoss Admin Settings - Profile Search Panel.
 *
 * Registers sections and fields for the Profile Search side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Profile Search panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
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
			'title'       => __( 'Profile Search', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Profile Search (enable/disable).
	bb_register_feature_field(
		'members',
		'profile_search',
		'profile_search',
		array(
			'name'              => 'bp-enable-profile-search',
			'label'             => __( 'Profile Search', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Enable advanced profile search on the Members page', 'buddyboss' ),
			'default'           => ! bp_disable_advanced_profile_search(),
			'sanitize_callback' => 'intval',
			'order'             => 10,
		)
	);

	/**
	 * Fires after Profile Search section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_members_settings_after_profile_search_fields' );
}
