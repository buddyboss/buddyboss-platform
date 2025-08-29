<?php
/**
 * BuddyBoss Mothership Loader
 *
 * @package BuddyBoss
 * @since 1.0.0
 */

namespace BuddyBoss\Core\Admin\Mothership;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Include required files.
require_once __DIR__ . '/class-bb-plugin-connector.php';
require_once __DIR__ . '/class-bb-license-page.php';
require_once __DIR__ . '/class-bb-addons-page.php';
require_once __DIR__ . '/manager/class-bb-license-manager.php';
require_once __DIR__ . '/manager/class-bb-addons-manager.php';
require_once __DIR__ . '/api/class-bb-api-request.php';

use BuddyBoss\Core\Admin\Mothership\Manager\BB_License_Manager;
use BuddyBoss\Core\Admin\Mothership\Manager\BB_Addons_Manager;

/**
 * Main loader class for BuddyBoss Mothership functionality.
 */
class BB_Mothership_Loader {

	/**
	 * Instance of this class.
	 *
	 * @var BB_Mothership_Loader
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return BB_Mothership_Loader
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Setup hooks.
	 */
	private function setup_hooks() {
		// Admin menu hooks.
		add_action( 'admin_menu', array( $this, 'register_admin_pages' ), 99 );
		
		// License controller.
		add_action( 'admin_init', array( 'BuddyBoss\Core\Admin\Mothership\Manager\BB_License_Manager', 'controller' ), 20 );
		
		// Addons hooks.
		BB_Addons_Manager::load_hooks();
		
		// Schedule license check events.
		BB_License_Manager::schedule_events( 'buddyboss' );
		
		// Handle license status changes.
		add_action( 'buddyboss_license_status_changed', array( $this, 'handle_license_status_change' ), 10, 2 );
		
		// For local development - disable SSL verification if needed.
		if ( defined( 'BUDDYBOSS_DISABLE_SSL_VERIFY' ) && BUDDYBOSS_DISABLE_SSL_VERIFY ) {
			add_filter( 'https_ssl_verify', '__return_false' );
		}
	}

	/**
	 * Register admin pages.
	 */
	public function register_admin_pages() {
		// Only show to users with manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Register License page.
		BB_License_Page::register();
		
		// Register Addons page.
		BB_Addons_Page::register();
	}

	/**
	 * Handle license status changes.
	 *
	 * @param bool  $is_active License active status.
	 * @param mixed $response  API response.
	 */
	public function handle_license_status_change( $is_active, $response ) {
		$connector = BB_Plugin_Connector::get_instance();
		
		if ( ! $is_active ) {
			// License is no longer active.
			$connector->update_license_activation_status( false );
			
			// Clear cached data.
			delete_transient( 'buddyboss_addons_cache' );
			delete_transient( 'buddyboss_addons_update_check' );
			
			// Log the deactivation.
			error_log( 'BuddyBoss license deactivated: ' . print_r( $response, true ) );
		} else {
			// License is active - ensure status is updated.
			$connector->update_license_activation_status( true );
		}
	}

	/**
	 * Initialize the mothership functionality.
	 *
	 * This should be called from the main plugin file.
	 */
	public static function init() {
		self::get_instance();
	}
}