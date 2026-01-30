<?php
/**
 * BuddyBoss Admin Settings Page.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register admin menu pages for Settings.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_settings_register_menu() {
	// Add "Settings" as a separate submenu item under BuddyBoss.
	// This keeps the old bp-components intact for comparison.
	add_submenu_page(
		'buddyboss-platform',
		__( 'Settings 2.0', 'buddyboss' ),
		__( 'Settings 2.0', 'buddyboss' ),
		'manage_options',
		'bb-settings',
		'bb_admin_settings_page'
	);
}
add_action( 'admin_menu', 'bb_admin_settings_register_menu', 999 ); // Late priority.


/**
 * Render the New Settings page.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_settings_page() {
	// Get build directory.
	$build_dir = buddypress()->plugin_dir . 'bp-core/admin/bb-settings/settings-2.0/build';
	$build_url = buddypress()->plugin_url . 'bp-core/admin/bb-settings/settings-2.0/build';

	// Load asset file.
	$asset_file = $build_dir . '/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		?>
		<div class="wrap">
			<div class="notice notice-error">
				<p>
					<?php
					esc_html_e(
						'BuddyBoss Admin Settings 2.0 assets not found. Please run: npm run build:admin:settings-2.0',
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

	// Enqueue WordPress components style explicitly (needed for ToggleControl, Button, etc.).
	wp_enqueue_style( 'wp-components' );

	// Enqueue BuddyBoss icons CSS.
	$min             = bp_core_get_minified_asset_suffix();
	$bb_icon_version = function_exists( 'bb_icon_font_map_data' ) ? bb_icon_font_map_data( 'version' ) : bp_get_version();
	wp_enqueue_style(
		'bb-icons',
		buddypress()->plugin_url . "bp-templates/bp-nouveau/icons/css/bb-icons{$min}.css",
		array(),
		$bb_icon_version
	);
	wp_enqueue_style(
		'bb-icons-rl-css',
		buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/icons/css/bb-icons-rl{$min}.css",
		array(),
		$bb_icon_version
	);

	// Enqueue WordPress editor for TinyMCE support in Activity Edit modal.
	wp_enqueue_editor();

	// Enqueue scripts and styles.
	wp_enqueue_script(
		'bb-admin-settings-2-0',
		$build_url . '/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	// Check if CSS file exists (try multiple possible locations).
	$css_paths = array(
		$build_dir . '/styles/admin.css',
		$build_dir . '/admin.css',
	);

	foreach ( $css_paths as $css_file ) {
		if ( file_exists( $css_file ) ) {
			$css_url = str_replace( buddypress()->plugin_dir, buddypress()->plugin_url, $css_file );
			wp_enqueue_style(
				'bb-admin-settings-2-0',
				$css_url,
				array( 'wp-components' ), // Add wp-components as dependency.
				$asset['version']
			);
			break;
		}
	}

	$registry      = bb_feature_registry();
	$all_features  = $registry->bb_get_features( array( 'status' => 'all' ) );
	$feature_count = count( $all_features );

	// Localize script with admin data.
	wp_localize_script(
		'bb-admin-settings-2-0',
		'bbAdminData',
		array(
			'apiUrl'      => rest_url( bp_rest_namespace() . '/' . bp_rest_version() . '/' ),
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'ajaxNonce'   => wp_create_nonce( 'bb_admin_settings' ),
			'logoUrl'     => buddypress()->plugin_url . 'bp-core/images/admin/BBLogo.png',
			'currentUser' => array(
				'id'   => get_current_user_id(),
				'name' => wp_get_current_user()->display_name,
			),
			'debug'       => array(
				'featureCount' => $feature_count,
				'featureIds'   => array_keys( $all_features ),
			),
		)
	);

	// Render mount point.
	?>
	<div class="wrap bb-admin-settings-2-0-wrap">
		<div id="bb-admin-settings-2-0"></div>
	</div>
	<?php
}
