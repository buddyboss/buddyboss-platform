<?php
/**
 * BuddyBoss Integrations marketplace admin page.
 *
 * Renders the standalone React app for the BuddyBoss → Integrations submenu.
 * The app is its own webpack bundle (BUILD_TARGET=integrations) so it never
 * loads on the Settings page and vice-versa. Data is fetched from buddyboss.com
 * through the same-origin proxy (BB_REST_Integrations_Endpoint).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Render the Integrations marketplace page.
 *
 * Mirrors bb_admin_settings_page(): resolves the build asset manifest, enqueues
 * the bundle + CSS, localizes the same `bbAdminData` object the React app reads
 * (apiUrl + nonce), and prints the mount container.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_admin_integrations_page() {
	$build_dir = buddypress()->plugin_dir . 'bp-core/admin/bb-settings/integrations/build';
	$build_url = buddypress()->plugin_url . 'bp-core/admin/bb-settings/integrations/build';

	$asset_file = $build_dir . '/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		?>
		<div class="wrap">
			<div class="notice notice-error">
				<p>
					<?php
					esc_html_e(
						'BuddyBoss Integrations assets not found. Please run: npm run build:admin:integrations',
						'buddyboss'
					);
					?>
				</p>
			</div>
		</div>
		<?php
		return;
	}

	$asset = require $asset_file;
	$min   = bp_core_get_minified_asset_suffix();

	// WordPress components style (Button, etc.) + BuddyBoss icon fonts.
	wp_enqueue_style( 'wp-components' );
	$bb_icon_version = function_exists( 'bb_icon_font_map_data' ) ? bb_icon_font_map_data( 'version' ) : bp_get_version();
	wp_enqueue_style(
		'bb-icons-rl-css',
		buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/icons/css/bb-icons-rl{$min}.css",
		array(),
		$bb_icon_version
	);

	wp_enqueue_script(
		'bb-admin-integrations',
		$build_url . '/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	// Resolve the LTR admin CSS path (RTL is auto-derived). The integrations
	// build nests CSS under /styles/, matching the settings target layout.
	$css_candidates = array(
		"/styles/admin{$min}.css",
		"/admin{$min}.css",
	);
	foreach ( $css_candidates as $css_rel ) {
		$css_file = $build_dir . $css_rel;
		if ( file_exists( $css_file ) ) {
			$css_url = str_replace( buddypress()->plugin_dir, buddypress()->plugin_url, $css_file );
			wp_register_style( 'bb-admin-integrations', $css_url, array( 'wp-components' ), $asset['version'] );
			wp_style_add_data( 'bb-admin-integrations', 'rtl', 'replace' );
			if ( $min ) {
				wp_style_add_data( 'bb-admin-integrations', 'suffix', $min );
			}
			wp_enqueue_style( 'bb-admin-integrations' );
			break;
		}
	}

	// The React app reads window.bbIntegrationsData.{apiUrl,nonce,version}. A
	// distinct global name (not the Settings app's bbAdminData) keeps the two
	// standalone bundles isolated. apiUrl points at the buddyboss/v1 REST root so
	// the client can build the integrations/proxy URL; version is used to bust
	// the client localStorage cache on plugin upgrade.
	$api_namespace = function_exists( 'bp_rest_namespace' ) && function_exists( 'bp_rest_version' )
		? bp_rest_namespace() . '/' . bp_rest_version() . '/'
		: 'buddyboss/v1/';
	wp_localize_script(
		'bb-admin-integrations',
		'bbIntegrationsData',
		array(
			'apiUrl'   => rest_url( $api_namespace ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'adminUrl' => esc_url( admin_url() ),
			'version'  => defined( 'BP_PLATFORM_VERSION' ) ? BP_PLATFORM_VERSION : '0',
		)
	);

	echo '<div class="wrap"><div id="bb-admin-integrations"></div></div>';
}
