<?php
/**
 * BuddyPress Core React Admin Settings - ReadyLaunch
 *
 * @package BuddyPress
 * @subpackage Core
 * @since [BBVERSION]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the BuddyPress Readylaunch React Settings page.
 *
 * @since [BBVERSION]
 *
 * @return void
 */
function bb_readylaunch_settings_page_html() {
	?>
	<div class="wrap" id="bb-readylaunch-settings">
		<div class="bb-rl-tab-header">
            <div class="advance-brand">
                <img alt="" class="upgrade-brand" src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/admin/credits-buddyboss.png' ); ?>" />
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


function bb_readylaunch_settings_page_enqueue_style_script( $admin_page ) {
    if ( 'buddyboss_page_bb-readylaunch' !== $admin_page ) {
        return;
    }

    $asset_file = plugin_dir_path( __FILE__ ) . 'build/index.asset.php';

    if ( ! file_exists( $asset_file ) ) {
        return;
    }

    $asset = include $asset_file;

	// Enqueue WordPress media scripts and styles
	wp_enqueue_media();

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
    
    // Enqueue the BB Icons CSS
    wp_enqueue_style(
        'bb-icons-rl-css',
        plugins_url( 'src/styles/icons/bb-icons-rl.css', __FILE__ ),
        array(),
        $asset['version']
    );
}

add_action( 'admin_enqueue_scripts', 'bb_readylaunch_settings_page_enqueue_style_script' );
