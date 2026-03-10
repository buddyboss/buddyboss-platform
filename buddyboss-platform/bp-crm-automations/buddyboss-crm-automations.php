<?php
/**
 * Plugin Name:       BuddyBoss CRM Automations
 * Plugin URI:        https://github.com/tomjutla/buddyboss-crm
 * Description:       Automation workflows add-on for BuddyBoss CRM. Requires BuddyBoss CRM (core).
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Tom Jutla
 * License:           GPL v2 or later
 * Text Domain:       buddyboss-crm-automations
 *
 * @package BuddyBossCRMAutomations
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BB_CRM_AUTO_VERSION',       '1.1.9' );
define( 'BB_CRM_AUTO_PLUGIN_FILE',   __FILE__ );
define( 'BB_CRM_AUTO_PLUGIN_DIR',    plugin_dir_path( __FILE__ ) );
define( 'BB_CRM_AUTO_PLUGIN_URL',    plugin_dir_url( __FILE__ ) );
define( 'BB_CRM_AUTO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class.
 */
final class BuddyBoss_CRM_Automations {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ), 20 );
		add_filter( 'bb_crm_capabilities', array( $this, 'register_capability' ) );
	}

	public function init() {
		if ( ! function_exists( 'bb_crm' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_missing_core' ) );
			return;
		}
		$this->load();
	}

	private function load() {
		static $loaded = false;
		if ( $loaded ) return;
		$loaded = true;

		require_once BB_CRM_AUTO_PLUGIN_DIR . 'includes/class-bb-crm-auto-install.php';
		require_once BB_CRM_AUTO_PLUGIN_DIR . 'includes/class-bb-crm-auto-engine.php';
		require_once BB_CRM_AUTO_PLUGIN_DIR . 'includes/class-bb-crm-auto-triggers.php';
		require_once BB_CRM_AUTO_PLUGIN_DIR . 'includes/class-bb-crm-auto-actions.php';
		require_once BB_CRM_AUTO_PLUGIN_DIR . 'includes/class-bb-crm-auto-conditions.php';
		require_once BB_CRM_AUTO_PLUGIN_DIR . 'includes/bb-crm-auto-functions.php';

		if ( is_admin() ) {
			require_once BB_CRM_AUTO_PLUGIN_DIR . 'admin/class-bb-crm-auto-admin.php';
			BB_CRM_Auto_Admin::instance();
		}

		BB_CRM_Auto_Engine::instance();

		// Run install/upgrade when plugin version changes (creates new tables/columns).
		if ( get_option( 'bb_crm_auto_db_version' ) !== BB_CRM_Auto_Install::DB_VERSION ) {
			BB_CRM_Auto_Install::install();
		}

		// Trigger files call __() in their static init() at file bottom.
		// Defer to init so translations are available before those calls run.
		add_action( 'init', array( $this, 'load_triggers' ), 1 );

		// Open-tracking endpoint.
		add_action( 'init', array( $this, 'handle_open_tracking' ), 1 );

		do_action( 'bb_crm_auto_loaded' );
	}

	public function load_triggers() {
		load_plugin_textdomain( 'buddyboss-crm-automations', false, dirname( BB_CRM_AUTO_PLUGIN_BASENAME ) . '/languages' );

		require_once BB_CRM_AUTO_PLUGIN_DIR . 'includes/triggers/class-bb-crm-user-triggers.php';
		require_once BB_CRM_AUTO_PLUGIN_DIR . 'includes/triggers/class-bb-crm-group-triggers.php';
		require_once BB_CRM_AUTO_PLUGIN_DIR . 'includes/triggers/class-bb-crm-activity-triggers.php';
		require_once BB_CRM_AUTO_PLUGIN_DIR . 'includes/triggers/class-bb-crm-gamification-triggers.php';
		require_once BB_CRM_AUTO_PLUGIN_DIR . 'includes/triggers/class-bb-crm-profile-triggers.php';
		require_once BB_CRM_AUTO_PLUGIN_DIR . 'includes/triggers/class-bb-crm-tag-triggers.php';
	}

	public function register_capability( $caps ) {
		$caps['automations'] = true;
		return $caps;
	}

	public function activate() {
		if ( ! function_exists( 'bb_crm' ) ) {
			deactivate_plugins( BB_CRM_AUTO_PLUGIN_BASENAME );
			wp_die( __( 'BuddyBoss CRM Automations requires BuddyBoss CRM to be active.', 'buddyboss-crm-automations' ) );
		}
		require_once BB_CRM_AUTO_PLUGIN_DIR . 'includes/class-bb-crm-auto-install.php';
		BB_CRM_Auto_Install::install();

		if ( ! wp_next_scheduled( 'bb_crm_auto_process_queue' ) ) {
			wp_schedule_event( time(), 'every_five_minutes', 'bb_crm_auto_process_queue' );
		}
	}

	public function handle_open_tracking() {
		if ( empty( $_GET['bb_camp_open'] ) ) return;
		$token = sanitize_text_field( wp_unslash( $_GET['bb_camp_open'] ) );
		if ( strlen( $token ) !== 64 || ! ctype_xdigit( $token ) ) return;

		global $wpdb;
		$table = $wpdb->prefix . 'bp_crm_email_opens';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare(
			"UPDATE `{$table}` SET opened_at = %s WHERE token = %s AND opened_at IS NULL",
			current_time( 'mysql' ), $token
		) );

		// Serve 1x1 transparent GIF.
		header( 'Content-Type: image/gif' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\xff\xff\xff\x00\x00\x00\x21\xf9\x04\x00\x00\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3b";
		exit;
	}

	public function deactivate() {
		wp_clear_scheduled_hook( 'bb_crm_auto_process_queue' );
	}

	public function notice_missing_core() {
		echo '<div class="notice notice-error"><p><strong>BuddyBoss CRM Automations</strong> requires the BuddyBoss CRM plugin to be active.</p></div>';
	}
}

// Register 5-minute cron interval.
add_filter( 'cron_schedules', function( $schedules ) {
	if ( ! isset( $schedules['every_five_minutes'] ) ) {
		$schedules['every_five_minutes'] = array(
			'interval' => 300,
			'display'  => 'Every 5 Minutes',
		);
	}
	return $schedules;
} );

function bb_crm_auto() {
	return BuddyBoss_CRM_Automations::instance();
}
bb_crm_auto();
