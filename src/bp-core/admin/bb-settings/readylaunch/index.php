<?php
/**
 * BuddyBoss Core React Admin Settings - ReadyLaunch
 *
 * @package BuddyBoss
 * @subpackage Core
 * @since BuddyBoss 2.9.00
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the BuddyBoss Readylaunch React Settings page.
 *
 * @since BuddyBoss 2.9.00
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
				<button class="bb-rl-header-actions-button" data-help-cat-id="5811" aria-label="<?php esc_attr_e( 'Help', 'buddyboss' ); ?>">
					<i class="bb-icons-rl-book-open"></i>
				</button>
			</div>
		</div>
		<div class="bb-rl-field-wrap" id="bb-rl-field-wrap"></div>
	</div>
	<div id="bb-rl-help-overlay" class="bb-rl-help-overlay" style="display: none;">
		<div class="bb-rl-help-overlay-header">
			<img alt="<?php echo esc_attr__( 'BuddyBoss', 'buddyboss' ); ?>" class="bb-branding-logo" src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/admin/BBLogo.png' ); ?>" />
			<button id="bb-rl-help-overlay-close" class="bb-rl-help-overlay-close" aria-label="<?php esc_attr_e( 'Close', 'buddyboss' ); ?>">
				<i class="bb-icons-rl-x-circle"></i>
			</button>
		</div>
		<div class="bb-rl-help-overlay-content-wrap">
			<div class="bb-rl-help-overlay-content">
				<div class="bb-rl-help-accordion">
					<!-- Accordion items will be loaded here -->
				</div>
				<div class="bb-rl-help-cards">
					<div class="bb-rl-help-card">
						<i class="bb-icons-rl-file-text"></i>
						<h3><?php echo esc_html__( 'View Documentation', 'buddyboss' ); ?></h3>
						<p><?php echo esc_html__( 'Browse documentation, reference material, and tutorials for BuddyBoss.', 'buddyboss' ); ?></p>
						<a href="https://www.buddyboss.com/docs/" target="_blank" rel="noopener noreferrer"  class="button"><?php echo esc_html__( 'View All Documentation', 'buddyboss' ); ?></a>
					</div>
					<div class="bb-rl-help-card">
						<i class="bb-icons-rl-lifebuoy"></i>
						<h3><?php echo esc_html__( 'Get Support', 'buddyboss' ); ?></h3>
						<p><?php echo esc_html__( 'Submit a ticket and our world class support team will be in touch soon.', 'buddyboss' ); ?></p>
						<a href="https://www.buddyboss.com/contact/" target="_blank" rel="noopener noreferrer" class="button button-primary"><?php echo esc_html__( 'Get Support', 'buddyboss' ); ?></a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Enqueue the BuddyBoss Readylaunch React Settings page styles and scripts.
 *
 * @since BuddyBoss 2.9.00
 *
 * @param string $admin_page The admin page.
 */
function bb_readylaunch_register_enqueue_style_script() {
	$asset_file = plugin_dir_path( __FILE__ ) . 'build/index.asset.php';

	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = include $asset_file;

	$dependencies = array_merge(
		$asset['dependencies'],
		array(
			'bp-admin',
		)
	);

	$min = bp_core_get_minified_asset_suffix();
	$rtl = is_rtl() ? '-rtl' : '';

	wp_register_script(
		'bb-readylaunch-admin-script',
		plugins_url( 'build/index.js', __FILE__ ),
		$dependencies,
		$asset['version'],
		array(
			'in_footer' => true,
		)
	);

	wp_register_style(
		'bb-readylaunch-admin-style',
		plugins_url( "build/styles/settings{$rtl}{$min}.css", __FILE__ ),
		array(),
		$asset['version']
	);

	// Enqueue the BB Icons CSS.
	wp_register_style(
		'bb-icons-rl-css',
		buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/icons/css/bb-icons-rl{$min}.css",
		array(),
		$asset['version']
	);
}
add_action( 'admin_enqueue_scripts', 'bb_readylaunch_register_enqueue_style_script', 5 );

/**
 * Enqueue the BuddyBoss Readylaunch React Settings page styles and scripts.
 *
 * @since BuddyBoss 2.9.00
 *
 * @param string $admin_page The admin page.
 */
function bb_readylaunch_settings_page_enqueue_style_script( $admin_page ) {
	if ( strpos( $admin_page, 'bb-readylaunch' ) === false ) {
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

	wp_enqueue_script( 'bb-readylaunch-admin-script' );

	wp_set_script_translations( 'bb-readylaunch-admin-script', 'buddyboss', buddypress()->plugin_dir . 'languages/' );

	wp_enqueue_style( 'bb-readylaunch-admin-style' );

	// Enqueue the BB Icons CSS.
	wp_enqueue_style( 'bb-icons-rl-css' );
}

add_action( 'bp_admin_enqueue_scripts', 'bb_readylaunch_settings_page_enqueue_style_script' );
