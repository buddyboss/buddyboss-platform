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

// Load Settings 2.0 configuration (side panels, sections, fields).
// This must be loaded here (not in loader.php) so settings are registered
// even when the feature is inactive (needed to show the settings page).
if ( file_exists( __DIR__ . '/admin/settings.php' ) ) {
	require_once __DIR__ . '/admin/settings.php';
}
