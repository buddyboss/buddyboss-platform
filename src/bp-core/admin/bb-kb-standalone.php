<?php
/**
 * Standalone Knowledge Base modal — asset resolver + enqueue helper.
 *
 * Lets any BuddyBoss admin surface (Membership, Courses, …) mount the shared
 * KB modal. Consumers call bb_kb_enqueue_standalone() on their admin screens
 * and open it via window.bbKb.open({ rootCategory }). Per-product scope is
 * NOT stored here — it travels through open().
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve the standalone KB bundle asset descriptor.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array { url, path, version, deps }
 */
function bb_kb_standalone_asset() {
	$build_path = buddypress()->plugin_dir . 'bp-core/admin/bb-settings/kb-standalone/build/index.js';
	$asset_php  = buddypress()->plugin_dir . 'bp-core/admin/bb-settings/kb-standalone/build/index.asset.php';

	$deps    = array( 'wp-element', 'bb-admin-common' );
	$version = defined( 'BP_PLATFORM_VERSION' ) ? BP_PLATFORM_VERSION : '0';
	if ( file_exists( $asset_php ) ) {
		$asset   = include $asset_php;
		$deps    = isset( $asset['dependencies'] ) ? $asset['dependencies'] : $deps;
		$version = isset( $asset['version'] ) ? $asset['version'] : $version;
	}

	return array(
		'url'     => buddypress()->plugin_url . 'bp-core/admin/bb-settings/kb-standalone/build/index.js',
		'path'    => $build_path,
		'version' => $version,
		'deps'    => $deps,
	);
}

/**
 * Enqueue the standalone KB bundle + its stylesheet, localize site-level
 * config, and set script translations. Admin-only, idempotent.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $args Optional { logo_url }.
 * @return void
 */
function bb_kb_enqueue_standalone( $args = array() ) {
	// Frontend guard + AJAX guard.
	if ( ! is_admin() || wp_doing_ajax() ) {
		return;
	}
	// Idempotent — the handle already registered/enqueued this request.
	if ( wp_script_is( 'bb-kb-standalone', 'enqueued' ) ) {
		return;
	}

	$asset = bb_kb_standalone_asset();

	// Shared @bb/admin-common layer must be registered first (it is the external).
	// Normally bb_register_admin_common_assets() registers this handle at priority
	// 1 with the correct deps, so this block is a fallback. Read the real
	// dependency list from the common build manifest rather than under-declaring
	// it — a partial dep list would crash the modal on missing wp.i18n/wp.hooks.
	if ( ! wp_script_is( 'bb-admin-common', 'registered' ) && ! wp_script_is( 'bb-admin-common', 'enqueued' ) ) {
		$common_asset_php = buddypress()->plugin_dir . 'bp-core/admin/bb-settings/common/build/index.asset.php';
		$common_deps      = array( 'react', 'wp-element', 'wp-hooks', 'wp-html-entities', 'wp-i18n' );
		if ( file_exists( $common_asset_php ) ) {
			$common_asset = include $common_asset_php;
			if ( isset( $common_asset['dependencies'] ) ) {
				$common_deps = $common_asset['dependencies'];
			}
		}
		wp_register_script(
			'bb-admin-common',
			buddypress()->plugin_url . 'bp-core/admin/bb-settings/common/build/index.js',
			$common_deps,
			$asset['version'],
			true
		);
	}

	wp_enqueue_script( 'bb-kb-standalone', $asset['url'], $asset['deps'], $asset['version'], true );

	// KB styles (common build) + RTL. The Membership/Courses admin does not load
	// Settings 2.0 CSS, so the modal needs its stylesheet explicitly.
	wp_enqueue_style(
		'bb-kb-standalone',
		buddypress()->plugin_url . 'bp-core/admin/bb-settings/common/build/styles/common.css',
		array(),
		$asset['version']
	);
	if ( is_rtl() ) {
		wp_style_add_data( 'bb-kb-standalone', 'rtl', 'replace' );
	}

	// Icon font used by the modal close button (bb-icons-rl-x).
	if ( wp_style_is( 'bb-icons-rl-css', 'registered' ) ) {
		wp_enqueue_style( 'bb-icons-rl-css' );
	}

	// Translations so the modal strings render in the site locale.
	if ( function_exists( 'wp_set_script_translations' ) ) {
		wp_set_script_translations( 'bb-kb-standalone', 'buddyboss' );
	}

	// Site-level config — identical for every consumer (H3: namespace-correct).
	$namespace = function_exists( 'bp_rest_namespace' ) && function_exists( 'bp_rest_version' )
		? bp_rest_namespace() . '/' . bp_rest_version()
		: 'bb/v1';
	$logo_url  = isset( $args['logo_url'] ) ? $args['logo_url'] : '';

	wp_localize_script(
		'bb-kb-standalone',
		'bbKb',
		array(
			'apiUrl'  => esc_url_raw( rest_url( $namespace . '/' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'logoUrl' => esc_url_raw( $logo_url ),
		)
	);
}
