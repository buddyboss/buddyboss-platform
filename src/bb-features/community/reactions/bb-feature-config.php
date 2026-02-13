<?php
/**
 * Reactions Feature Configuration.
 *
 * Registers the Reactions feature in the BuddyBoss Feature Registry.
 *
 * @package BuddyBoss\Features\Community\Reactions
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Reactions feature with Feature Registry.
 *
 * This makes the Reactions feature appear as a feature card in the new admin settings
 * and enables conditional loading based on feature activation state.
 */
bb_register_feature(
	'reactions',
	array(
		'label'              => __( 'Like & Reactions', 'buddyboss' ),
		'description'        => __( 'Allow community members to interact by liking or selecting from a variety of emotions.', 'buddyboss' ),
		'icon'               => array(
			'type'  => 'font',
			'class' => 'bb-icons-rl bb-icons-rl-thumbs-up',
		),
		'license_tier'       => 'free',
		'category'           => 'community',
		'standalone'         => true,
		'php_loader'         => function () {
			// Note: Core functions are loaded separately to ensure availability.
			// This loader only loads UI/admin components when feature is active.
			require_once __DIR__ . '/loader.php';
		},
		'settings_route'     => '/settings/reactions',
		'order'              => 120,
		'is_active_callback' => function () {
			// Reactions depend on activity component being active.
			if ( ! bp_is_active( 'activity' ) ) {
				return false;
			}
			// Respect Settings 2.0 feature toggle (bb-active-features).
			// Backward compat: if 'reactions' key not set, treat as enabled (same as before this toggle existed).
			$active_features = bp_get_option( 'bb-active-features', array() );
			if ( ! array_key_exists( 'reactions', $active_features ) ) {
				return true;
			}
			return ! empty( $active_features['reactions'] );
		},
	)
);

// Load reactions core when activity and like/reactions feature are active.
add_action(
	'bp_loaded',
	function () {
		if ( ! bp_is_active( 'activity' ) || ! bp_is_activity_like_active() ) {
			return;
		}

		// Load class definitions.
		if ( file_exists( __DIR__ . '/classes/class-bb-reaction.php' ) ) {
			require_once __DIR__ . '/classes/class-bb-reaction.php';
		}

		// Only load REST endpoint class when the API plugin is not active (it provides its own copy).
		if ( function_exists( 'bp_rest_in_buddypress' ) && bp_rest_in_buddypress() && file_exists( __DIR__ . '/classes/class-bb-rest-reactions-endpoint.php' ) ) {
			require_once __DIR__ . '/classes/class-bb-rest-reactions-endpoint.php';
		}

		// Load reaction functions (AJAX handlers, template helpers, etc.).
		if ( file_exists( __DIR__ . '/bb-activity-reactions.php' ) ) {
			require_once __DIR__ . '/bb-activity-reactions.php';
		}

		// Initialize the reaction system on 'init' to ensure WordPress is fully loaded.
		add_action(
			'init',
			function () {
				if ( function_exists( 'bb_load_reaction' ) ) {
					bb_load_reaction();
				}
			},
			5
		);
	},
	5
);

// Register Reactions REST API endpoint (only when API plugin is not active).
add_action(
	'bp_rest_api_init',
	function () {
		if ( bp_rest_in_buddypress() && class_exists( 'BB_REST_Reactions_Endpoint' ) ) {
			$controller = new BB_REST_Reactions_Endpoint();
			$controller->register_routes();
		}
	}
);

// Load Settings 2.0 configuration (side panels, sections, fields).
// This must be loaded here (not in loader.php) so settings are registered
// even when the feature is inactive (needed to show the settings page).
if ( file_exists( __DIR__ . '/admin/settings.php' ) ) {
	require_once __DIR__ . '/admin/settings.php';
}
