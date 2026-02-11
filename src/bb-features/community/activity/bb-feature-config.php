<?php
/**
 * Activity Feature Configuration.
 *
 * Registers the Activity feature in the BuddyBoss Feature Registry.
 *
 * @package BuddyBoss\Features\Community\Activity
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Activity feature with Feature Registry.
 *
 * This makes the Activity feature appear as a feature card in the new admin settings
 * and enables conditional loading based on feature activation state.
 */
bb_register_feature(
	'activity',
	array(
		'label'              => __( 'Activity Feeds', 'buddyboss' ),
		'description'        => __( 'Provide global, personal, and group activity feeds that support threaded commenting, direct posting, @mentions, and email notifications.', 'buddyboss' ),
		'icon'               => array(
			'type'  => 'font',
			'class' => 'bb-icons-rl bb-icons-rl-lightning',
		),
		'license_tier'       => 'free',
		'category'           => 'community',
		'standalone'         => true,
		'php_loader'         => function () {
			require_once __DIR__ . '/loader.php';
		},
		'settings_route'     => '/settings/activity',
		'order'              => 40,
		'is_active_callback' => function () {
			// Activity depends on an activity component being active.
			if ( ! bp_is_active( 'activity' ) ) {
				return false;
			}

			// Respect Settings 2.0 feature toggle (bb-active-features).
			// Backward compat: if 'activity' key not set, treat as enabled (same as before this toggle existed).
			$active_features = bp_get_option( 'bb-active-features', array() );
			if ( ! array_key_exists( 'activity', $active_features ) ) {
				return true;
			}

			return ! empty( $active_features['activity'] );
		},
	)
);

// Load Settings 2.0 configuration (side panels, sections, fields).
// This must be loaded here (not in loader.php) so settings are registered
// even when the feature is inactive (needed to show the settings page).
if ( file_exists( __DIR__ . '/admin/settings.php' ) ) {
	require_once __DIR__ . '/admin/settings.php';
}
