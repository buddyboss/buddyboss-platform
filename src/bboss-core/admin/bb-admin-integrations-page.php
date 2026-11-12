<?php
/**
 * BuddyBoss Integrations marketplace admin page.
 *
 * Renders the standalone React app for the BuddyBoss → Integrations submenu.
 * The app is its own webpack bundle (BUILD_TARGET=integrations) so it never
 * loads on the Settings page and vice-versa. Data is fetched from buddyboss.com
 * through the same-origin proxy (BBoss_REST_Integrations_Endpoint).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Render the Integrations marketplace page.
 *
 * Mirrors bboss_admin_settings_page(): resolves the build asset manifest, enqueues
 * the bundle + CSS, localizes the `bbIntegrationsData` object the React app reads
 * (apiUrl + nonce), and prints the mount container.
 *
 * @since BuddyBoss 3.1.0
 *
 * @return void
 */
function bboss_admin_integrations_page() {
	$build_dir = bboss()->plugin_dir . 'bboss-core/admin/bb-settings/integrations/build';
	$build_url = bboss()->plugin_url . 'bboss-core/admin/bb-settings/integrations/build';

	$asset_file = $build_dir . '/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		?>
		<div class="wrap">
			<div class="notice notice-error">
				<p>
					<?php
					esc_html_e(
						'BuddyBoss Integrations assets not found. Please run: npm run build:admin:integrations',
						'buddyboss-platform'
					);
					?>
				</p>
			</div>
		</div>
		<?php
		return;
	}

	// The integrations bundle hard-depends on the shared admin-common layer (global
	// header, Knowledge Base modal, sanitizer). If the common build is missing, the
	// bundle would load with an unresolvable 404 dependency and silently break —
	// surface an actionable notice instead, mirroring the bundle check above.
	$common_asset = bboss()->plugin_dir . 'bboss-core/admin/bb-settings/common/build/index.asset.php';
	if ( ! file_exists( $common_asset ) ) {
		?>
		<div class="wrap">
			<div class="notice notice-error">
				<p>
					<?php
					esc_html_e(
						'BuddyBoss shared admin assets not found. Please run: npm run build:admin:common',
						'buddyboss-platform'
					);
					?>
				</p>
			</div>
		</div>
		<?php
		return;
	}

	$asset = require $asset_file;
	$min   = bboss_core_get_minified_asset_suffix();

	// WordPress components style (Button, etc.) + BuddyBoss icon fonts.
	wp_enqueue_style( 'wp-components' );
	$bb_icon_version = function_exists( 'bboss_icon_font_map_data' ) ? bboss_icon_font_map_data( 'version' ) : bboss_get_version();
	wp_enqueue_style(
		'bb-icons-rl-css',
		bboss()->plugin_url . "bboss-templates/bboss-nouveau/readylaunch/icons/css/bb-icons-rl{$min}.css",
		array(),
		$bb_icon_version
	);

	// Enqueue the shared admin-common stylesheet (registered by
	// bb-admin-common-assets.php at admin_enqueue_scripts priority 1). The script
	// half is pulled in automatically via the dependency merge below, so it is not
	// enqueued explicitly here.
	if ( wp_style_is( 'bb-admin-common-style', 'registered' ) ) {
		wp_enqueue_style( 'bb-admin-common-style' );
	}

	// Merge bb-admin-common into the integrations bundle deps so WordPress enqueues
	// it (and guarantees load order) when the bundle is enqueued.
	$integrations_deps = array_unique( array_merge( $asset['dependencies'], array( 'bb-admin-common' ) ) );

	wp_enqueue_script(
		'bb-admin-integrations',
		$build_url . '/index.js',
		$integrations_deps,
		$asset['version'],
		true
	);

	// Resolve the admin CSS path. Unlike the Settings build (Grunt emits .min +
	// RTL variants), the integrations SCSS step emits a single, already-compressed
	// styles/admin.css. So prefer the $min-suffixed name if it exists, but fall
	// back to the plain admin.css — otherwise on production (SCRIPT_DEBUG off,
	// $min === '.min') the page would look for a non-existent admin.min.css and
	// load with no styles at all.
	$css_candidates = array(
		"/styles/admin{$min}.css",
		'/styles/admin.css',
		"/admin{$min}.css",
		'/admin.css',
	);
	foreach ( $css_candidates as $css_rel ) {
		$css_file = $build_dir . $css_rel;
		if ( file_exists( $css_file ) ) {
			$css_url = str_replace( bboss()->plugin_dir, bboss()->plugin_url, $css_file );
			wp_register_style( 'bb-admin-integrations', $css_url, array( 'wp-components' ), $asset['version'] );
			wp_style_add_data( 'bb-admin-integrations', 'rtl', 'replace' );
			// Only advertise the .min suffix when we actually matched a minified
			// file, so WP's RTL 'replace' doesn't derive a -rtl.min.css that the
			// integrations build never produces.
			if ( $min && false !== strpos( $css_rel, $min . '.css' ) ) {
				wp_style_add_data( 'bb-admin-integrations', 'suffix', $min );
			}
			wp_enqueue_style( 'bb-admin-integrations' );
			break;
		}
	}

	// The React app reads window.bbIntegrationsData — a distinct global name (not
	// the Settings app's bbAdminData) so the two standalone bundles stay isolated.
	// Keys: apiUrl (buddyboss/v1 REST root, for the integrations/proxy URL), nonce
	// (wp_rest), adminUrl, version (busts the client localStorage cache on upgrade),
	// logoUrl (shared header), and ajaxUrl + searchNonce + settingsUrl
	// (shared header's global "Search for settings" → Settings search AJAX).
	$api_namespace = function_exists( 'bboss_rest_namespace' ) && function_exists( 'bboss_rest_version' )
		? bboss_rest_namespace() . '/' . bboss_rest_version() . '/'
		: 'buddyboss/v1/';


	// "Works with" is fully driven by the buddyboss.com API — each integration's
	// acf.works_with is a { slug: { name, met } } map derived from its ACF
	// "works_with" checkbox (label + checked state). The client renders it directly,
	// so no site-side requirements list is needed here.
	wp_localize_script(
		'bb-admin-integrations',
		'bbIntegrationsData',
		array(
			'apiUrl'       => rest_url( $api_namespace ),
			'nonce'        => wp_create_nonce( 'wp_rest' ),
			'adminUrl'     => esc_url( admin_url() ),
			'version'      => defined( 'BBOSS_PLATFORM_VERSION' ) ? BBOSS_PLATFORM_VERSION : '0',
			'logoUrl'      => esc_url( bboss()->plugin_url . 'bboss-core/images/admin/BBLogo.png' ),
			// Gates client-side diagnostic console logging; off in production.
			'debug'        => defined( 'WP_DEBUG' ) && WP_DEBUG,
			// The shared header's global "Search for settings" box queries the
			// Settings search AJAX (bb_admin_search_settings, nonce action
			// bb_admin_settings); results deep-link into the Settings page.
			'ajaxUrl'      => esc_url( admin_url( 'admin-ajax.php' ) ),
			'searchNonce'  => wp_create_nonce( 'bb_admin_settings' ),
			'settingsUrl'  => esc_url( admin_url( 'admin.php?page=bb-settings' ) ),
		)
	);

	// Plugin install/activate state — only for users who can act. Lets the cards
	// render Install / Activate / Deactivate with no extra requests (the slug is
	// derived client-side from acf.plugin_link and looked up in this map). Built
	// fresh each load (never cached) so it always reflects reality after an action.
	// get_plugins() is cached per-request and is_plugin_active() reads the
	// autoloaded active_plugins option, so this is cheap and runs only on this page.
	if ( current_user_can( 'install_plugins' ) || current_user_can( 'activate_plugins' ) ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed_plugins = array();
		foreach ( get_plugins() as $plugin_file => $plugin_data ) {
			$plugin_slug = dirname( $plugin_file );
			if ( '.' === $plugin_slug ) {
				continue; // Single-file plugin (no folder) — not installable by slug.
			}
			$installed_plugins[ $plugin_slug ] = array(
				'file'   => $plugin_file,
				'active' => is_plugin_active( $plugin_file ),
			);
		}

		// Core wp.updates powers the client-side install flow (install-plugin action
		// + its own nonce + the filesystem-credentials modal).
		wp_enqueue_script( 'updates' );
		add_action( 'admin_footer', 'wp_print_request_filesystem_credentials_modal' );
		add_action( 'admin_footer', 'wp_print_admin_notice_templates' );

		wp_localize_script(
			'bb-admin-integrations',
			'bbIntegrationsPlugins',
			array(
				'installed'   => $installed_plugins,
				'canInstall'  => current_user_can( 'install_plugins' ),
				'canActivate' => current_user_can( 'activate_plugins' ),
				'nonce'       => wp_create_nonce( 'bb_integrations_plugin' ),
				'ajaxUrl'     => esc_url( admin_url( 'admin-ajax.php' ) ),
			)
		);
	}

	// Render the React mount, then fire bb_admin_header_actions OUTSIDE the React
	// tree (inside .wrap) so a header callback can render its root <div> + script
	// synchronously; the shared header relocates that live node into its slot.
	// Mirrors the Settings page.
	// The `bb-admin-app` class scopes the shared Knowledge Base modal styles
	// (defined under `.bb-admin-app` in the shared common CSS) so the modal is
	// styled here the same as on the Settings page.
	echo '<div class="wrap"><div id="bb-admin-integrations" class="bb-admin-app"></div>';
	do_action( 'bb_admin_header_actions' );
	echo '</div>';
}
