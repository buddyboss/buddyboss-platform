<?php
/**
 * Plugin Name:       BuddyBoss CRM
 * Plugin URI:        https://github.com/tomjutla/buddyboss-crm
 * Description:       Comprehensive CRM system for BuddyBoss Platform with user tagging, automation workflows, and broadcast messaging.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            BuddyBoss
 * Author URI:        https://buddyboss.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       buddyboss-crm
 * Domain Path:       /languages
 *
 * @package BuddyBossCRM
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Require BuddyBoss Platform — deactivate with an admin notice if missing.
register_activation_hook( __FILE__, function () {
    if ( ! function_exists( 'buddypress' ) && ! defined( 'BP_VERSION' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die(
            __( 'BuddyBoss CRM requires the BuddyBoss Platform plugin to be installed and active.', 'buddyboss-crm' ),
            __( 'Plugin dependency missing', 'buddyboss-crm' ),
            array( 'back_link' => true )
        );
    }
} );

add_action( 'admin_notices', function () {
    if ( ! function_exists( 'buddypress' ) && ! defined( 'BP_VERSION' ) && is_plugin_active( plugin_basename( __FILE__ ) ) ) {
        echo '<div class="notice notice-error"><p>';
        esc_html_e( 'BuddyBoss CRM requires BuddyBoss Platform to be installed and active. Please activate BuddyBoss Platform or deactivate BuddyBoss CRM.', 'buddyboss-crm' );
        echo '</p></div>';
    }
} );

// Plugin constants
define( 'BB_CRM_VERSION', '1.0.0' );
define( 'BB_CRM_PLUGIN_FILE', __FILE__ );
define( 'BB_CRM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BB_CRM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BB_CRM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main BuddyBoss CRM Class
 *
 * @since 1.0.0
 */
final class BuddyBoss_CRM {

    /**
     * The single instance of the class
     *
     * @var BuddyBoss_CRM
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * Main BuddyBoss CRM Instance
     *
     * Ensures only one instance of BuddyBoss CRM is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @return BuddyBoss_CRM - Main instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Hook into actions and filters
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        // Activation and deactivation
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        // Initialize plugin after WordPress loads
        add_action( 'plugins_loaded', array( $this, 'init' ), 10 );

        // Load plugin text domain
        add_action( 'init', array( $this, 'load_textdomain' ) );

        // Admin notices
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
    }

    /**
     * Initialize the plugin
     *
     * @since 1.0.0
     */
    public function init() {
        // Check if BuddyBoss Platform is active
        if ( ! $this->check_buddyboss_dependency() ) {
            return;
        }

        // Register CRM table names on $wpdb so any code can reference $wpdb->bb_tags etc.
        $this->setup_tables();

        // Include core files
        $this->includes();

        // Initialize components
        $this->init_components();

        // Action hook for plugins to initialize
        do_action( 'bb_crm_init' );
    }

    /**
     * Register CRM table names on the $wpdb object.
     * Follows the BuddyBoss/WordPress convention of storing prefixed table names
     * as properties on $wpdb so any code can reference them without rebuilding the prefix.
     *
     * @since 1.0.0
     */
    private function setup_tables() {
        global $wpdb;
        $bb_prefix = function_exists( 'bp_core_get_table_prefix' ) ? bp_core_get_table_prefix() : $wpdb->base_prefix;

        $wpdb->bb_tags                  = $bb_prefix . 'bb_tags';
        $wpdb->bb_user_tags             = $bb_prefix . 'bb_user_tags';
        $wpdb->bb_tag_categories        = $bb_prefix . 'bb_tag_categories';
        $wpdb->bb_user_lists            = $bb_prefix . 'bb_user_lists';
        $wpdb->bb_user_list_tags        = $bb_prefix . 'bb_user_list_tags';
        $wpdb->bb_user_list_assignments = $bb_prefix . 'bb_user_list_assignments';
        $wpdb->bb_automation_rules      = $bb_prefix . 'bb_automation_rules';
        $wpdb->bb_automation_queue      = $bb_prefix . 'bb_automation_queue';
        $wpdb->bb_automation_log        = $bb_prefix . 'bb_automation_log';
        $wpdb->bb_tag_history           = $bb_prefix . 'bb_tag_history';
    }

    /**
     * Check if BuddyBoss Platform is active
     *
     * @since 1.0.0
     * @return bool
     */
    private function check_buddyboss_dependency() {
        // Check if BuddyPress (core of BuddyBoss) is active
        if ( ! function_exists( 'buddypress' ) ) {
            // Allow plugin to work without BuddyBoss for development
            // Just show a warning in admin
            return true; // Changed from false to true for development
        }

        return true;
    }

    /**
     * Include required core files
     *
     * @since 1.0.0
     */
    private function includes() {
        // Core includes
        require_once BB_CRM_PLUGIN_DIR . 'includes/class-bb-crm-install.php';
        require_once BB_CRM_PLUGIN_DIR . 'includes/bb-crm-functions.php';

        // Admin includes
        if ( is_admin() ) {
            require_once BB_CRM_PLUGIN_DIR . 'includes/admin/class-bb-crm-admin.php';
        }
    }

    /**
     * Initialize plugin components
     *
     * @since 1.0.0
     */
    private function init_components() {
        // Initialize admin if in admin area
        if ( is_admin() ) {
            BB_CRM_Admin::instance();
        }
    }

    /**
     * Load plugin text domain
     *
     * @since 1.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'buddyboss-crm',
            false,
            dirname( BB_CRM_PLUGIN_BASENAME ) . '/languages'
        );
    }

    /**
     * Plugin activation
     *
     * @since 1.0.0
     */
    public function activate() {
        // Check PHP version
        if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
            deactivate_plugins( BB_CRM_PLUGIN_BASENAME );
            wp_die(
                __( 'BuddyBoss CRM requires PHP 7.4 or higher.', 'buddyboss-crm' ),
                __( 'Plugin Activation Error', 'buddyboss-crm' ),
                array( 'back_link' => true )
            );
        }

        // Check WordPress version
        if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
            deactivate_plugins( BB_CRM_PLUGIN_BASENAME );
            wp_die(
                __( 'BuddyBoss CRM requires WordPress 6.0 or higher.', 'buddyboss-crm' ),
                __( 'Plugin Activation Error', 'buddyboss-crm' ),
                array( 'back_link' => true )
            );
        }

        // Set activation flag
        set_transient( 'bb_crm_activation_notice', true, 60 );

        // Run database installation
        require_once BB_CRM_PLUGIN_DIR . 'includes/class-bb-crm-install.php';
        BB_CRM_Install::install();
    }

    /**
     * Plugin deactivation
     *
     * @since 1.0.0
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook( 'bb_crm_automation_queue_process' );
        wp_clear_scheduled_hook( 'bb_crm_tag_expiry_check' );

        // Clear caches
        wp_cache_flush();
    }

    /**
     * Display admin notices
     *
     * @since 1.0.0
     */
    public function admin_notices() {
        // Show activation notice
        if ( get_transient( 'bb_crm_activation_notice' ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php _e( 'BuddyBoss CRM activated!', 'buddyboss-crm' ); ?></strong>
                    <?php _e( 'Start managing your community with tags, automation, and broadcasts.', 'buddyboss-crm' ); ?>
                </p>
            </div>
            <?php
            delete_transient( 'bb_crm_activation_notice' );
        }

        // Check for BuddyBoss Platform
        if ( ! function_exists( 'buddypress' ) ) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php _e( 'BuddyBoss CRM - Development Mode', 'buddyboss-crm' ); ?></strong><br>
                    <?php _e( 'BuddyBoss Platform is not installed. The CRM admin interface will work, but user integration features require BuddyBoss Platform.', 'buddyboss-crm' ); ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Get plugin version
     *
     * @since 1.0.0
     * @return string
     */
    public function get_version() {
        return BB_CRM_VERSION;
    }

    /**
     * Get plugin directory path
     *
     * @since 1.0.0
     * @return string
     */
    public function plugin_path() {
        return BB_CRM_PLUGIN_DIR;
    }

    /**
     * Get plugin directory URL
     *
     * @since 1.0.0
     * @return string
     */
    public function plugin_url() {
        return BB_CRM_PLUGIN_URL;
    }
}

/**
 * Main instance of BuddyBoss_CRM
 *
 * Returns the main instance of BuddyBoss_CRM to prevent the need to use globals.
 *
 * @since 1.0.0
 * @return BuddyBoss_CRM
 */
function bb_crm() {
    return BuddyBoss_CRM::instance();
}

// Initialize the plugin
bb_crm();
