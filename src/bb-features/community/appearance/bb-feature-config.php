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
 * @since BuddyBoss 3.0.0
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
		'description'        => __( 'Configure ReadyLaunch layout, community pages, and site SEO settings to control your site\'s appearance and visibility.', 'buddyboss' ),
		'icon'               => array(
			'type'  => 'font',
			'class' => 'bb-icons-rl bb-icons-rl-palette',
		),
		'license_tier'       => 'free',
		'category'           => 'community',
		'standalone'         => true,
		'required'           => true, // Non-toggleable — the card renders without an interactive switch.
		'is_active_callback' => '__return_true', // Always-active. The Site Layout dropdown is the real ReadyLaunch kill-switch.
		'settings_route'     => '/settings/appearance',
		'order'              => 10,
	)
);

/**
 * Seed `bb_rl_enabled` to `true` on fresh installs so the site boots on
 * ReadyLaunch instead of the legacy WordPress-theme layout.
 *
 * `bb_is_readylaunch_enabled()` reads `bp_get_option( 'bb_rl_enabled', false )`,
 * so when the option key is genuinely missing (new install before the
 * onboarding wizard has run) the fallback was false and the user saw the
 * legacy theme on their very first page load. ReadyLaunch is now the
 * intended first-run experience, so we write the option explicitly at
 * install time.
 *
 * Hook fires exactly once, inside the `bp_is_install()` branch of
 * `bp_version_updater()` in `bp-core-update.php`. Upgrades and re-activations
 * never hit that branch, so existing admins who intentionally stayed on the
 * WordPress theme are unaffected.
 *
 * @since BuddyBoss 3.0.0
 */
add_action( 'bb_core_after_install', 'bb_appearance_set_readylaunch_default_on_install' );
function bb_appearance_set_readylaunch_default_on_install() {
	bp_update_option( 'bb_rl_enabled', true );
}

// Load stateless sanitizer / shape-normalizer helpers UNCONDITIONALLY. They are
// used by three entry points: Settings 2.0 admin save, onboarding wizard save,
// and the one-shot `bb_rl_migrate_settings()` version-update migration. The
// migration runs on `bp_admin_init` today (satisfies `is_admin()`), but any
// future non-admin caller (WP-CLI without `WP_CLI` defined, REST upgrader,
// etc.) must be able to reach these helpers too.
if ( file_exists( __DIR__ . '/includes/sanitizers.php' ) ) {
	require_once __DIR__ . '/includes/sanitizers.php';
}

// Load Settings 2.0 configuration (side panels, sections, fields) + admin-only
// callbacks. Gated on admin/AJAX/REST/CLI contexts — the registration block
// calls `wp_get_nav_menus()` and primes 14 options, none of which frontend
// requests need. Skipping on public requests trims startup work for visitors.
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
