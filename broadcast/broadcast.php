<?php
/**
 * Plugin Name:  BuddyBoss Broadcast
 * Plugin URI:   https://github.com/tomjutla/broadcast
 * Description:  Targeted announcements inside BuddyBoss.
 * Version:      1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:       BuddyBoss
 * Author URI:   https://buddyboss.com/
 * Text Domain:  broadcast
 * Requires Plugins: buddyboss-platform
 */

defined( 'ABSPATH' ) || exit;

define( 'BROADCAST_VERSION',      '1.0.0' );
define( 'BROADCAST_CAMP_VERSION', '1.0.0' );
define( 'BROADCAST_FILE',     __FILE__ );
define( 'BROADCAST_DIR',      plugin_dir_path( __FILE__ ) );
define( 'BROADCAST_URL',      plugin_dir_url( __FILE__ ) );
define( 'BROADCAST_BASENAME', plugin_basename( __FILE__ ) );

// --- Activation: dependency guard + DB setup ---
register_activation_hook( __FILE__, function () {
    if ( ! defined( 'BP_PLATFORM_VERSION' ) || ! function_exists( 'buddypress' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die(
            __( 'Broadcast requires BuddyBoss Platform to be installed and active.', 'broadcast' ),
            __( 'Missing dependency', 'broadcast' ),
            array( 'back_link' => true )
        );
    }
    require_once BROADCAST_DIR . 'includes/class-broadcast-install.php';
    Broadcast_Install::install();
} );

// --- Deactivation ---
register_deactivation_hook( __FILE__, function () {
    flush_rewrite_rules();
} );

// --- Uninstall ---
register_uninstall_hook( __FILE__, 'broadcast_uninstall' );
function broadcast_uninstall() {
    require_once plugin_dir_path( __FILE__ ) . 'uninstall.php';
}

// Action Scheduler — must be loaded before any AS function calls.
require_once BROADCAST_DIR . 'vendor/woocommerce/action-scheduler/action-scheduler.php';

// --- Runtime init ---
add_action( 'plugins_loaded', function () {
    if ( ! defined( 'BP_PLATFORM_VERSION' ) || ! function_exists( 'buddypress' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            printf(
                '<strong>%s</strong> %s',
                esc_html__( 'Broadcast', 'broadcast' ),
                esc_html__( 'requires BuddyBoss Platform to be installed and active. Please activate BuddyBoss Platform.', 'broadcast' )
            );
            echo '</p></div>';
        } );
        return;
    }
    require_once BROADCAST_DIR . 'includes/class-broadcast.php';
    Broadcast::instance();
}, 20 );
