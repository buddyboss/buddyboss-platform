<?php
/**
 * Plugin Name: BuddyBoss Platform
 * Plugin URI:  https://buddyboss.com/
 * Description: The BuddyBoss Platform adds community features to WordPress. Member Profiles, Activity Feeds, Direct Messaging, Notifications, and more!
 * Author:      BuddyBoss
 * Author URI:  https://buddyboss.com/
 * Version:     1.5.3
 * Text Domain: buddyboss
 * Domain Path: /bp-languages/
 * License:     GPLv2 or later (license.txt)
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Assume you want to load from build
$bp_loader = dirname( __FILE__ ) . '/src/bp-loader.php';
$subdir    = 'src';

if ( ! defined( 'BP_SOURCE_SUBDIRECTORY' ) ) {
	// Set source subdirectory
	define( 'BP_SOURCE_SUBDIRECTORY', $subdir );
}

// Define overrides - only applicable to those running trunk
if ( ! defined( 'BP_PLUGIN_DIR' ) ) {
	define( 'BP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'BP_PLUGIN_URL' ) ) {
	// Be nice to symlinked directories
	define( 'BP_PLUGIN_URL', plugins_url( trailingslashit( basename( constant( 'BP_PLUGIN_DIR' ) ) ) ) );
}

// Include BuddyBoss Platform
include( $bp_loader );

// Unset the loader, since it's loaded in global scope
unset( $bp_loader );

add_action( 'wp_footer', function(){
	?>
    <a href="#jk-modal" class="button item-button bp-secondary-action report-activity" data-bp-nonce="1123">
        <span class="bp-screen-reader-text">Report</span>
        <span class="report-label">Report</span>
    </a>
	<div id="jk-modal" class="registration-popup bb-modal mfp-hide">
		<h2>
            <?php
            esc_html_e( 'Report Content', 'buddyboss');
            ?>
        </h2>
        <div class="bb-report-type">
            
        </div>
		<button title="Close (Esc)" type="button" class="mfp-close">×</button>
	</div>
<?php
} );