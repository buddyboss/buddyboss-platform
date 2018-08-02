<?php
/**
 * The BuddyBoss Platform.
 *
 * BuddyBoss is social networking software with a twist.
 *
 * @package BuddyBoss
 * @subpackage Main
 * @since BuddyPress 1.0.0
 */

/**
 * Plugin Name: BuddyBoss Platform
 * Plugin URI:  https://buddyboss.com/
 * Description: The BuddyBoss Platform adds community features to WordPress. Member Profiles, Activity Feeds, Direct Messaging, Notifications, and more!
 * Author:      BuddyBoss
 * Author URI:  https://buddyboss.com/
 * Version:     3.1.1
 * Text Domain: buddyboss
 * Domain Path: /bp-languages/
 * License:     GPLv2 or later (license.txt)
 */

/**
 * This files should always remain compatible with the minimum version of
 * PHP supported by WordPress.
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Make sure buddypress isn't activated.
 * 
 * We're not using is_plugin_active... functions b/c you need to include the 
 * /wp-admin/includes/plugin.php file in order to use that function.
 */

$is_bp_active = false;
$bp_plugin_file = 'buddypress/bp-loader.php';

$is_bb_active = false;
$bb_plugin_file = 'bbpress/bbpress.php';

if ( is_multisite() ) {
    // get network-activated plugins
    $plugins = get_site_option( 'active_sitewide_plugins' );

    if ( isset( $plugins[ $bp_plugin_file ] ) ) {
        $is_bp_active = true;
    }

    if ( isset( $plugins[ $bb_plugin_file ] ) ) {
        $is_bb_active = true;
    }
}

if ( !$is_bp_active ) {
    // get activated plugins
    $plugins = get_option( 'active_plugins' );

    if ( in_array( $bp_plugin_file, $plugins ) ) {
        $is_bp_active = true;
    }

    if ( in_array( $bb_plugin_file, $plugins ) ) {
        $is_bb_active = true;
    }
}

if ( $is_bp_active ) {
    
    /**
     * Displays an admin notice when buddypress plugin is also active.
     * 
     * @since BuddyPress 1.0.0
     * @return void
     */
    function bp_duplicate_buddypress_notice() {
        if ( !current_user_can( 'activate_plugins' ) ) {
            return;
        }
        
        $plugins_url = is_network_admin() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );
        $link_plugins = sprintf( "<a href='%s'>%s</a>", $plugins_url, __( 'deactivate', 'buddyboss' ) );
        ?>

        <div id="message" class="error notice">
            <p><strong><?php esc_html_e( 'BuddyBoss Platform is disabled.', 'buddyboss' ); ?></strong></p>
            <p><?php printf( esc_html__( 'The BuddyBoss Platform can\'t work while BuddyPress plugin is active. Please %s BuddyPress to re-enable BuddyBoss Platform.', 'buddyboss' ), $link_plugins ); ?></p>
        </div>

        <?php 
    }
    
    /**
     * You can't have buddypress and buddyboss, both active at the same time!
     */
    add_action( 'admin_notices',            'bp_duplicate_buddypress_notice' );
	add_action( 'network_admin_notices',    'bp_duplicate_buddypress_notice' );
    
}

if ( $is_bb_active ) {
    
    /**
     * Displays an admin notice when bbpress plugin is also active.
     * 
     * @since BuddyPress 1.0.0
     * @return void
     */
    function bp_duplicate_bbpress_notice() {
        if ( !current_user_can( 'activate_plugins' ) ) {
            return;
        }
        
        $plugins_url = is_network_admin() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );
        $link_plugins = sprintf( "<a href='%s'>%s</a>", $plugins_url, __( 'deactivate', 'buddyboss' ) );
        ?>

        <div id="message" class="error notice">
            <p><strong><?php esc_html_e( 'BuddyBoss Platform is disabled.', 'buddyboss' ); ?></strong></p>
            <p><?php printf( esc_html__( 'The BuddyBoss Platform can\'t work while bbPress plugin is active. Please %s bbPress to re-enable BuddyBoss Platform.', 'buddyboss' ), $link_plugins ); ?></p>
        </div>

        <?php 
    }
    
    /**
     * You can't have buddypress and buddyboss, both active at the same time!
     */
    add_action( 'admin_notices',            'bp_duplicate_bbpress_notice' );
	add_action( 'network_admin_notices',    'bp_duplicate_bbpress_notice' );
    
}

/**
 * Buddyboss platform's code is already loaded when buddypress is being activated. And buddypress doesn't have function checks to avoid 
 * duplicate function declarations. This leads to a fatal error.
 * To avoid that, we prevent buddypress from activating and show a nicer notice, instead of the dirty fatal error.
 */
if ( !function_exists( 'bp_prevent_activating_buddypress' ) ) {

    add_action( 'admin_init', 'bp_prevent_activating_buddypress' );

    /**
     * Check if the current request is to activate buddypress plugins and redirect if so.
     * 
     * @since BuddyPress 1.0.0
     * 
     * @global string $pagenow
     */
    function bp_prevent_activating_buddypress () {
        global $pagenow;

        if ( $pagenow == 'plugins.php' ) {

            if ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'activate' && isset( $_GET[ 'plugin' ] ) ) {
                
                if ( $_GET[ 'plugin' ] == 'buddypress/bp-loader.php' ) {
                    wp_redirect( self_admin_url( 'plugins.php?bp_prevent_activating_buddypress=1' ), 301 );
                    exit;
                }
                
            }

            if ( isset( $_GET[ 'bp_prevent_activating_buddypress' ] ) ) {
                add_action( 'admin_notices',            'bp_prevented_activating_buddypress_notice' );
                add_action( 'network_admin_notices',    'bp_prevented_activating_buddypress_notice' );
            }
        }
    }

    /**
     * Show a notice that an attempt to activate buddypres plugin was blocked.
     * 
     * @since BuddyPress 1.0.0
     */
    function bp_prevented_activating_buddypress_notice () {
        ?>

        <div id="message" class="error notice">
            <p><strong><?php esc_html_e( 'BuddyPress can\'t be activated.', 'buddyboss' ); ?></strong></p>
            <p><?php _e( 'The BuddyBoss Platform can\'t work while BuddyPress plugin is active. Please deactivate BuddyBoss Platform first, if you wish to activate BuddyPress.', 'buddyboss' ); ?></p>
        </div>

        <?php
    }

}

if ( !$is_bp_active && !$is_bb_active ) {


    // Required PHP version.
    define( 'BP_REQUIRED_PHP_VERSION', '5.3.0' );

    /**
     * The main function responsible for returning the one true BuddyBoss Instance to functions everywhere.
     *
     * Use this function like you would a global variable, except without needing
     * to declare the global.
     *
     * Example: <?php $bp = buddypress(); ?>
     *
     * @return BuddyBoss|null The one true BuddyBoss Instance.
     */
    function buddypress() {
        return BuddyPress::instance();
    }

    /**
     * Adds an admin notice to installations that don't meet BP's minimum PHP requirement.
     *
     * @since BuddyPress 2.8.0
     */
    function bp_php_requirements_notice() {
        if ( ! current_user_can( 'update_core' ) ) {
            return;
        }

        ?>

        <div id="message" class="error notice">
            <p><strong><?php esc_html_e( 'Your site does not support BuddyBoss.', 'buddyboss' ); ?></strong></p>
            <?php /* translators: 1: current PHP version, 2: required PHP version */ ?>
            <p><?php printf( esc_html__( 'Your site is currently running PHP version %1$s, while BuddyBoss requires version %2$s or greater.', 'buddyboss' ), esc_html( phpversion() ), esc_html( BP_REQUIRED_PHP_VERSION ) ); ?> <?php printf( __( 'See <a href="%s">the Codex guide</a> for more information.', 'buddyboss' ), 'https://codex.buddypress.org/getting-started/buddypress-2-8-will-require-php-5-3/' ); ?></p>
            <p><?php esc_html_e( 'Please update your server or deactivate BuddyBoss.', 'buddyboss' ); ?></p>
        </div>

        <?php
    }

    if ( version_compare( phpversion(), BP_REQUIRED_PHP_VERSION, '<' ) ) {
        add_action( 'admin_notices', 'bp_php_requirements_notice' );
        add_action( 'network_admin_notices', 'bp_php_requirements_notice' );
        return;
    } else {
        require dirname( __FILE__ ) . '/class-buddypress.php';

        /*
         * Hook BuddyBoss early onto the 'plugins_loaded' action.
         *
         * This gives all other plugins the chance to load before BuddyBoss,
         * to get their actions, filters, and overrides setup without
         * BuddyBoss being in the way.
         */
        if ( defined( 'BUDDYPRESS_LATE_LOAD' ) ) {
            add_action( 'plugins_loaded', 'buddypress', (int) BUDDYPRESS_LATE_LOAD );

        // "And now here's something we hope you'll really like!"
        } else {
            $GLOBALS['bp'] = buddypress();
        }
    }

}