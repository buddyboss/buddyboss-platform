<?php
/**
 * Shared admin-common layer asset registration.
 *
 * Registers the `bb-admin-common` script handle (exposes window.bbAdminCommon)
 * and the `bb-admin-common-style` style handle. App pages enqueue these as
 * dependencies so the layer ships once across all admin React apps.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the shared admin-common script + style handles.
 *
 * Reads the generated index.asset.php to pick up the version hash and
 * dependency list from @wordpress/dependency-extraction-webpack-plugin.
 * Consumer pages (e.g. the Integrations page) enqueue these handles so the
 * layer ships once and is never duplicated across bundles.
 *
 * @since BuddyBoss 3.1.0
 *
 * @return void
 */
function bb_register_admin_common_assets() {
	$build_dir = buddypress()->plugin_dir . 'bp-core/admin/bb-settings/common/build';
	$build_url = buddypress()->plugin_url . 'bp-core/admin/bb-settings/common/build';

	$asset_file = $build_dir . '/index.asset.php';
	$asset      = file_exists( $asset_file )
		? require $asset_file
		: array(
			'dependencies' => array(),
			'version'      => '0',
		);

	$deps = $asset['dependencies'];

	wp_register_script(
		'bb-admin-common',
		$build_url . '/index.js',
		$deps,
		$asset['version'],
		true
	);

	// The shipped zip carries only the minified CSS (the unminified `common.css`
	// is stripped from the build), so register the `.min.css` regardless of
	// SCRIPT_DEBUG — the same convention as the Settings 2.0 admin stylesheet.
	// Checking/registering the unminified file here instead left the handle
	// unregistered on a shipped build, so consumers that guard on
	// wp_style_is( 'bb-admin-common-style', 'registered' ) silently dropped the
	// shared header/common styles and the admin design broke.
	if ( file_exists( $build_dir . '/styles/common.min.css' ) ) {
		wp_register_style(
			'bb-admin-common-style',
			$build_url . '/styles/common.min.css',
			array(),
			$asset['version']
		);

		// RTL sites load `common-rtl.min.css` (matches the build output naming).
		wp_style_add_data( 'bb-admin-common-style', 'rtl', 'replace' );
		wp_style_add_data( 'bb-admin-common-style', 'suffix', '.min' );
	}
}
add_action( 'admin_enqueue_scripts', 'bb_register_admin_common_assets', 1 );
