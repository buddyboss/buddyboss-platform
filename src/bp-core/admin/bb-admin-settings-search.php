<?php
/**
 * BuddyBoss Admin Settings - Search Feature Registration.
 *
 * Registers the Search feature in the Feature Registry and loads
 * all Search settings (side panels, sections, fields).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Standard post types to register eagerly in the Pages & Posts panel.
 *
 * These are always available at bb_register_features time.
 *
 * @since BuddyBoss 3.0.0
 */
if ( ! defined( 'BB_SEARCH_STANDARD_POST_TYPES' ) ) {
	define( 'BB_SEARCH_STANDARD_POST_TYPES', array( 'post', 'page', 'attachment' ) );
}

/**
 * Post types to skip entirely (handled by Network Search panel).
 *
 * @since BuddyBoss 3.0.0
 */
if ( ! defined( 'BB_SEARCH_EXCLUDED_POST_TYPES' ) ) {
	define( 'BB_SEARCH_EXCLUDED_POST_TYPES', array( 'forum', 'topic', 'reply' ) );
}

/**
 * Register Search feature and settings in Feature Registry.
 *
 * Registers the feature, side panels, and delegates field registration
 * to panel-specific functions.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_admin_settings_register_search_feature() {

	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================

	bb_register_feature(
		'search',
		array(
			'label'              => __( 'Network Search', 'buddyboss' ),
			'description'        => __( 'Allow members to search the entire site, and manage what media and post types can be found in the sites network search.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-magnifying-glass',
			),
			'license_tier'       => 'free',
			'category'           => 'community',
			'standalone'         => true,
			'is_active_callback' => function () {
				return bp_is_active( 'search' );
			},
			'settings_route'     => '/settings/search',
			'order'              => 50,
		)
	);

	// When search is disabled, only the feature card is needed (so admin can re-enable).
	// Side panels, sections, and fields depend on search functions that aren't loaded.
	if ( ! bp_is_active( 'search' ) ) {
		return;
	}

	// Load settings sub-files only when search is active.
	require_once __DIR__ . '/settings/search/callbacks.php';
	require_once __DIR__ . '/settings/search/settings-network-search.php';
	require_once __DIR__ . '/settings/search/settings-pages-posts.php';
	require_once __DIR__ . '/settings/search/settings-autocomplete.php';

	// =========================================================================
	// SIDE PANELS
	// =========================================================================

	// Side Panel 1: Network Search (default).
	bb_register_side_panel(
		'search',
		'network_search',
		array(
			'title'      => __( 'Network Search', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-list-magnifying-glass',
			),
			'order'      => 10,
			'is_default' => true,
		)
	);

	// Side Panel 2: Pages and Posts Search.
	bb_register_side_panel(
		'search',
		'pages_posts',
		array(
			'title' => __( 'Pages and Posts Search', 'buddyboss' ),
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-file-magnifying-glass',
			),
			'order' => 20,
		)
	);

	// Side Panel 3: Autocomplete Settings.
	bb_register_side_panel(
		'search',
		'autocomplete',
		array(
			'title'    => __( 'Autocomplete Settings', 'buddyboss' ),
			'icon'     => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-spinner-gap',
			),
			'help_url' => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62840,
					),
					'admin.php'
				)
			),
			'order'    => 30,
		)
	);

	// =========================================================================
	// PANEL FIELDS
	// =========================================================================

	// Panel 1: Network Search.
	bb_search_register_network_search_fields();

	// Panel 2: Pages and Posts Search.
	bb_search_register_pages_posts_fields();

	// Panel 3: Autocomplete Settings.
	bb_search_register_autocomplete_fields();

	/**
	 * Fires after all Search settings panels are registered.
	 * Allows third-party extensions to add more panels or fields.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_search_after_register_settings_fields' );
}

add_action( 'bb_register_features', 'bb_admin_settings_register_search_feature', 20 );
