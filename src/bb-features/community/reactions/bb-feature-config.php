<?php
/**
 * Reactions Feature Configuration.
 *
 * Registers the Reactions feature in the BuddyBoss Feature Registry.
 *
 * @package BuddyBoss\Features\Community\Reactions
 * @since BuddyBoss 3.0.0
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
		'depends_on'         => array( 'activity' ),
		'php_loader'         => function () {
			require_once __DIR__ . '/loader.php';
		},
		'settings_route'     => '/settings/reactions',
		'order'              => 100,
		'is_active_callback' => function () {
			static $result = null;
			if ( null !== $result ) {
				return $result;
			}

			// Reactions depend on activity component being active.
			if ( ! bp_is_active( 'activity' ) ) {
				$result = false;
				return $result;
			}

			// Respect Settings 2.0 feature toggle (bb-active-features).
			// Backward compat: if 'reactions' key not set, treat as enabled (same as before this toggle existed).
			$active_features = bp_get_option( 'bb-active-features', array() );
			if ( ! array_key_exists( 'reactions', $active_features ) ) {
				$result = true;
				return $result;
			}

			$result = ! empty( $active_features['reactions'] );
			return $result;
		},
	)
);

// Load Settings 2.0 configuration (side panels, sections, fields).
// This must be loaded here (not in loader.php) so settings are registered
// even when the feature is inactive (needed to show the settings page).
//
// Gate on admin/AJAX/REST/CLI contexts — Settings 2.0 registration is
// admin-only work. Skipping it on frontend requests keeps the feature's
// boot cost off public page loads.
if (
	file_exists( __DIR__ . '/admin/settings.php' ) &&
	(
		is_admin() ||
		wp_doing_ajax() ||
		( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
		( defined( 'WP_CLI' ) && WP_CLI )
	)
) {
	require_once __DIR__ . '/admin/settings.php';
}
