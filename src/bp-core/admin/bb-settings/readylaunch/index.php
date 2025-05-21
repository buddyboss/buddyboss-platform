<?php
/**
 * BuddyBoss Core React Admin Settings - ReadyLaunch
 *
 * @package BuddyBoss
 * @subpackage Core
 * @since BuddyBoss [BBVERSION]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the BuddyBoss Readylaunch React Settings page.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_readylaunch_settings_page_html() {
	?>
	<div class="wrap bb-rl-settings" id="bb-readylaunch-settings">
		<div class="bb-rl-tab-header">
			<div class="bb-branding-header">
				<img alt="" class="bb-branding-logo" src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/admin/BBLogo.png' ); ?>" />
			</div>
			<div class="bb-rl-header-actions">
				<button class="bb-rl-header-actions-button">
					<i class="bb-icons-rl-bell"></i>
					<span class="bb-rl-header-button-count"><?php esc_html_e( '2', 'buddyboss' ); ?></span>
				</button>
				<button class="bb-rl-header-actions-button">
					<i class="bb-icons-rl-book-open"></i>
				</button>
			</div>
		</div>
		<div class="bb-rl-field-wrap" id="bb-rl-field-wrap">
			<?php
				printf(
					'<div class="bb-readylaunch-settings__loading">%s</div>',
					esc_html__( 'Loading…', 'buddyboss' )
				);
			?>
		</div>
	</div>
	<?php
}

/**
 * Enqueue the BuddyBoss Readylaunch React Settings page styles and scripts.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $admin_page The admin page.
 */
function bb_readylaunch_settings_page_enqueue_style_script( $admin_page ) {
	if ( 'buddyboss_page_bb-readylaunch' !== $admin_page ) {
		return;
	}

	$asset_file = plugin_dir_path( __FILE__ ) . 'build/index.asset.php';

	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = include $asset_file;

	// Enqueue WordPress media scripts and styles.
	wp_enqueue_media();

	// Enqueue WordPress components styles for Gutenberg blocks.
	wp_enqueue_style( 'wp-components' );

	wp_enqueue_script(
		'bb-readylaunch-admin-script',
		plugins_url( 'build/index.js', __FILE__ ),
		$asset['dependencies'],
		$asset['version'],
		array(
			'in_footer' => true,
		)
	);

	wp_enqueue_style(
		'bb-readylaunch-admin-style',
		plugins_url( 'build/index.css', __FILE__ ),
		array(),
		$asset['version']
	);

	// Enqueue the BB Icons CSS.
	wp_enqueue_style(
		'bb-icons-rl-css',
		buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/icons/css/bb-icons-rl.css',
		array(),
		$asset['version']
	);
}

add_action( 'admin_enqueue_scripts', 'bb_readylaunch_settings_page_enqueue_style_script' );
