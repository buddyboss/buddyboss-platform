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
			Header
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

    wp_enqueue_script(
        'bb-readylaunch-admin-script',
        plugins_url( 'build/index.js', __FILE__ ),
        $asset['dependencies'],
        $asset['version'],
        array(
            'in_footer' => true,
        )
    );
}

add_action( 'admin_enqueue_scripts', 'bb_readylaunch_settings_page_enqueue_style_script' );
