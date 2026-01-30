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
			return bp_is_active( 'activity' );
		},
	)
);

// Load reactions core when activity is active.
// These must always be available (regardless of feature toggle) because
// BP_Activity_Template depends on bb_activity_get_user_reacted_item_ids().
// add_action(
// 	'bp_loaded',
// 	function () {
// 		if ( ! bp_is_active( 'activity' ) ) {
// 			return;
// 		}

// 		// Note: includes/functions.php contains backward compat wrappers that already
// 		// exist in bp-core-functions.php, so we don't load it to avoid redeclaration.

// 		// Load class definitions.
// 		require_once __DIR__ . '/classes/class-bb-reaction.php';
// 		require_once __DIR__ . '/classes/class-bb-rest-reactions-endpoint.php';
// 		require_once __DIR__ . '/includes/activity-integration.php';

// 		// Initialize the reaction system on 'init' to ensure WordPress is fully loaded.
// 		add_action(
// 			'init',
// 			function () {
// 				if ( function_exists( 'bb_load_reaction' ) ) {
// 					bb_load_reaction();
// 				}
// 			},
// 			5
// 		);
// 	},
// 	5
// );

// Load Settings 2.0 configuration (side panels, sections, fields).
// This must be loaded here (not in loader.php) so settings are registered
// even when the feature is inactive (needed to show the settings page).
if ( file_exists( __DIR__ . '/admin/settings.php' ) ) {
	require_once __DIR__ . '/admin/settings.php';
}
