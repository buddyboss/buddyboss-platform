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
 * Called from bboss-admin-settings-search.php after side panels are registered.
 *
 * @since BuddyBoss 3.0.0
 */
function bboss_search_register_autocomplete_fields() {

	// =========================================================================
	// SECTION: Autocomplete Settings.
	// =========================================================================
	bboss_register_feature_section(
		'search',
		'autocomplete',
		'autocomplete_settings',
		array(
			'title'    => __( 'Autocomplete Settings', 'buddyboss' ),
			'order'    => 10,
			'help_url' => '636186',
		)
	);

	// FIELD: Enable Autocomplete (Toggle).
	bboss_register_feature_field(
		'search',
		'autocomplete',
		'autocomplete_settings',
		array(
			'name'              => 'bp_search_autocomplete',
			'label'             => __( 'Autocomplete', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Enable autocomplete dropdown when typing into search inputs.', 'buddyboss' ),
			'default'           => absint( bboss_get_option( 'bp_search_autocomplete', 1 ) ),
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// FIELD: Number of Results (Number).
	bboss_register_feature_field(
		'search',
		'autocomplete',
		'autocomplete_settings',
		array(
			'name'              => 'bp_search_number_of_results',
			'label'             => __( 'Number of Results', 'buddyboss' ),
			'type'              => 'number',
			'default'           => (int) bboss_get_option( 'bp_search_number_of_results', 5 ),
			'sanitize_callback' => 'bboss_search_sanitize_number_of_results',
			'suffix'            => __( 'results', 'buddyboss' ),
			'order'             => 20,
		)
	);
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_search_register_autocomplete_fields' ) && ! function_exists( 'bb_search_register_autocomplete_fields' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_search_register_autocomplete_fields()}.
	 *
	 * @deprecated Use bboss_search_register_autocomplete_fields() instead.
	 */
	function bb_search_register_autocomplete_fields() {
		return bboss_search_register_autocomplete_fields();
	}
}
// @bbwt-com-end compat-layer

