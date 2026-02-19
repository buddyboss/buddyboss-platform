<?php
/**
 * Reactions Feature Loader.
 *
 * Loads the Reactions feature runtime code when the feature is active.
 * This file is only included by BB_Feature_Loader when bb_is_feature_active('reactions') is true,
 * so no code here runs when the feature is disabled from the admin.
 *
 * Timing: This file is loaded during bp_loaded (priority 5) via the feature loader chain:
 *   bp_loaded:4 → bb_admin_settings_init → bb_discover_features → bb-feature-config.php
 *   bp_loaded:5 → BB_Feature_Registry::bb_init → bb_after_register_features
 *   bb_after_register_features:10 → BB_Feature_Loader::bb_load_active_features → php_loader → this file
 *
 * At this point bp_setup_components (priority 2) has already fired, so bp_is_active() works.
 * We load classes directly here since bp_loaded:5 has already started.
 *
 * @package BuddyBoss\Features\Community\Reactions
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load reactions core classes and functions immediately.
 *
 * This runs inline (not deferred) because the feature loader already gates this file
 * behind bb_is_feature_active('reactions'). We only need the additional activity check
 * for the sub-feature level (individual content type toggles).
 */
if ( bp_is_active( 'activity' ) && bp_is_activity_like_active() ) {

	$bb_reactions_feature_dir = __DIR__;

	// Load class definitions.
	if ( file_exists( $bb_reactions_feature_dir . '/classes/class-bb-reaction.php' ) ) {
		require_once $bb_reactions_feature_dir . '/classes/class-bb-reaction.php';
	}

	// Only load REST endpoint class when the API plugin is not active (it provides its own copy).
	if ( function_exists( 'bp_rest_in_buddypress' ) && bp_rest_in_buddypress() && file_exists( $bb_reactions_feature_dir . '/classes/class-bb-rest-reactions-endpoint.php' ) ) {
		require_once $bb_reactions_feature_dir . '/classes/class-bb-rest-reactions-endpoint.php';
	}

	// Load reaction functions (AJAX handlers, template helpers, etc.).
	if ( file_exists( $bb_reactions_feature_dir . '/bb-activity-reactions.php' ) ) {
		require_once $bb_reactions_feature_dir . '/bb-activity-reactions.php';
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

	// Register Reactions REST API endpoint (only when API plugin is not active).
	add_action(
		'bp_rest_api_init',
		function () {
			if ( function_exists( 'bp_rest_in_buddypress' ) && bp_rest_in_buddypress() && class_exists( 'BB_REST_Reactions_Endpoint' ) ) {
				$controller = new BB_REST_Reactions_Endpoint();
				$controller->register_routes();
			}
		}
	);
}
