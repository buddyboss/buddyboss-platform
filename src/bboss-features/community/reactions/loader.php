<?php
/**
 * Reactions Feature Loader.
 *
 * Loads the Reactions feature runtime code when the feature is active.
 * This file is only included by BBoss_Feature_Loader when bb_is_feature_active('reactions') is true,
 * so no code here runs when the feature is disabled from the admin.
 *
 * Timing: This file is loaded during bp_loaded (priority 5) via the feature loader chain:
 *   bp_loaded:4 → bb_admin_settings_init → bb_discover_features → bboss-feature-config.php
 *   bp_loaded:5 → BBoss_Feature_Registry::bb_init → bb_after_register_features
 *   bb_after_register_features:10 → BBoss_Feature_Loader::bb_load_active_features → php_loader → this file
 *
 * At this point bp_setup_components (priority 2) has already fired, so bboss_is_active() works.
 * We load classes directly here since bp_loaded:5 has already started.
 *
 * @package BuddyBoss\Features\Community\Reactions
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load reactions core classes and functions immediately.
 *
 * This runs inline (not deferred) because the feature loader already gates this file
 * behind bb_is_feature_active('reactions'). We only need the additional activity check
 * for the sub-feature level (individual content type toggles).
 *
 * Gate on EITHER content-type toggle (posts OR comments). bboss_is_activity_like_active()
 * reflects only the posts toggle, so relying on it would unload the entire reaction
 * runtime (BBoss_Reaction class, AJAX handlers, REST routes) whenever post reactions are
 * disabled — even if comment reactions are still enabled. That broke comment reactions
 * and the reactions listing ("Reactions are not available.").
 */
if (
	bboss_is_active( 'activity' ) &&
	( bboss_is_reaction_activity_posts_enabled() || bboss_is_reaction_activity_comments_enabled() )
) {

	$bb_reactions_feature_dir = __DIR__;

	// Load class definitions.
	if ( file_exists( $bb_reactions_feature_dir . '/classes/class-bboss-reaction.php' ) ) {
		require_once $bb_reactions_feature_dir . '/classes/class-bboss-reaction.php';
	}

	// Only load REST endpoint class when REST is bundled in Platform (separate API plugin provides its own copy).
	if ( function_exists( 'bboss_rest_in_buddypress' ) && bboss_rest_in_buddypress() && file_exists( $bb_reactions_feature_dir . '/classes/class-bboss-rest-reactions-endpoint.php' ) ) {
		require_once $bb_reactions_feature_dir . '/classes/class-bboss-rest-reactions-endpoint.php';
	}

	// Load reaction functions (AJAX handlers, template helpers, etc.).
	if ( file_exists( $bb_reactions_feature_dir . '/bboss-activity-reactions.php' ) ) {
		require_once $bb_reactions_feature_dir . '/bboss-activity-reactions.php';
	}

	// Initialize the reaction system on 'init' to ensure WordPress is fully loaded.
	add_action( 'init', 'bboss_reactions_init_load', 5 );

	// Register Reactions REST API endpoint (only when REST is bundled in Platform).
	add_action( 'bp_rest_api_init', 'bboss_reactions_register_rest_routes' );
}

/**
 * Initialize the reactions system.
 *
 * Named function (not anonymous closure) so it can be removed via remove_action()
 * by Pro or third-party plugins when needed.
 *
 * @since BuddyBoss 3.0.0
 */
function bboss_reactions_init_load() {
	if ( function_exists( 'bboss_load_reaction' ) ) {
		bboss_load_reaction();
	}
}

/**
 * Register the Reactions REST API endpoint.
 *
 * Only registers when REST is bundled in Platform (separate API plugin provides its own copy).
 * Named function (not anonymous closure) so it can be removed via remove_action()
 * by Pro or third-party plugins when needed.
 *
 * @since BuddyBoss 3.0.0
 */
function bboss_reactions_register_rest_routes() {
	if ( function_exists( 'bboss_rest_in_buddypress' ) && bboss_rest_in_buddypress() && class_exists( 'BBoss_REST_Reactions_Endpoint' ) ) {
		$controller = new BBoss_REST_Reactions_Endpoint();
		$controller->register_routes();
	}
}
