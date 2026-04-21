<?php
/**
 * Appearance Feature Configuration.
 *
 * Registers the Appearance feature in the BuddyBoss Feature Registry.
 * Appearance consolidates ReadyLaunch admin settings and Site SEO (Sharing plugin)
 * into a single feature with four side panels: General, Branding, Menus, Site SEO.
 *
 * Appearance is always-active: it does NOT expose a card-level toggle and does NOT
 * register an `is_active_callback`. The Site Layout dropdown (`bb_rl_enabled`) inside
 * the General panel is the real ReadyLaunch kill-switch. This matches the Registration
 * and Invites feature patterns in Settings 2.0.
 *
 * @package BuddyBoss\Features\Community\Appearance
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Appearance feature with Feature Registry.
 *
 * This makes the Appearance feature appear as a feature card (without a toggle)
 * in the new admin settings grid.
 */
bb_register_feature(
	'appearance',
	array(
		'label'              => __( 'Appearance', 'buddyboss' ),
		'description'        => __( 'Configure site branding, navigation, template pages and SEO.', 'buddyboss' ),
		'icon'               => array(
			'type'  => 'font',
			'class' => 'bb-icons-rl bb-icons-rl-palette',
		),
		'license_tier'       => 'free',
		'category'           => 'community',
		'standalone'         => true,
		'required'           => true, // Non-toggleable — the card renders without an interactive switch.
		'is_active_callback' => '__return_true', // Always-active. The Site Layout dropdown is the real ReadyLaunch kill-switch.
		'php_loader'         => function () {
			require_once __DIR__ . '/loader.php';
		},
		'settings_route'     => '/settings/appearance',
		'order'              => 10,
	)
);

// Load Settings 2.0 configuration (side panels, sections, fields).
// Loaded here (not in loader.php) so settings register even when the feature has no
// sub-feature activation state — matches the Reactions reference implementation.
if ( file_exists( __DIR__ . '/admin/settings.php' ) ) {
	require_once __DIR__ . '/admin/settings.php';
}
