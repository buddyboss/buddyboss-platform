<?php
/**
 * BuddyBoss Admin Settings - Search Autocomplete Panel.
 *
 * Registers the Autocomplete Settings side panel sections and fields for the
 * Search feature in the Settings 2.0 registry.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Autocomplete Settings panel sections and fields.
 *
 * Called from bb-admin-settings-search.php after side panels are registered.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_search_register_autocomplete_fields() {

	// =========================================================================
	// SECTION: Autocomplete Settings.
	// =========================================================================
	bb_register_feature_section(
		'search',
		'autocomplete',
		'autocomplete_settings',
		array(
			'title'    => __( 'Autocomplete Settings', 'buddyboss-platform' ),
			'order'    => 10,
			'help_url' => '636186',
		)
	);

	// FIELD: Enable Autocomplete (Toggle).
	bb_register_feature_field(
		'search',
		'autocomplete',
		'autocomplete_settings',
		array(
			'name'              => 'bp_search_autocomplete',
			'label'             => __( 'Autocomplete', 'buddyboss-platform' ),
			'type'              => 'toggle',
			'description'       => __( 'Enable autocomplete dropdown when typing into search inputs.', 'buddyboss-platform' ),
			'default'           => absint( bp_get_option( 'bp_search_autocomplete', 1 ) ),
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// FIELD: Number of Results (Number).
	bb_register_feature_field(
		'search',
		'autocomplete',
		'autocomplete_settings',
		array(
			'name'              => 'bp_search_number_of_results',
			'label'             => __( 'Number of Results', 'buddyboss-platform' ),
			'type'              => 'number',
			'default'           => (int) bp_get_option( 'bp_search_number_of_results', 5 ),
			'sanitize_callback' => 'bb_search_sanitize_number_of_results',
			'suffix'            => __( 'results', 'buddyboss-platform' ),
			'order'             => 20,
		)
	);
}
