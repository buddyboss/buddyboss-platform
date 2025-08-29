<?php
/**
 * BuddyBoss Addons Manager
 *
 * @package BuddyBoss
 * @since 1.0.0
 */

namespace BuddyBoss\Core\Admin\Mothership\Manager;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector;
use BuddyBoss\Core\Admin\Mothership\BB_Credentials;
use BuddyBoss\Core\Admin\Mothership\API\BB_API_Request;

/**
 * The Addons Manager class fetches available add-ons and integrates with WP plugin installation.
 */
class BB_Addons_Manager {

	/**
	 * Load hooks for addons functionality.
	 */
	public static function load_hooks() {
		add_action( 'wp_ajax_bb_addon_activate', array( __CLASS__, 'ajax_addon_activate' ) );
		add_action( 'wp_ajax_bb_addon_deactivate', array( __CLASS__, 'ajax_addon_deactivate' ) );
		add_action( 'wp_ajax_bb_addon_install', array( __CLASS__, 'ajax_addon_install' ) );
		add_filter( 'site_transient_update_plugins', array( __CLASS__, 'addons_update_plugins' ) );
	}

	/**
	 * Update the plugins transient with available add-ons.
	 *
	 * @param object $transient The plugins transient.
	 * @return object Modified transient.
	 */
	public static function addons_update_plugins( $transient ) {
		$connector = BB_Plugin_Connector::get_instance();
		
		// Check if license is active.
		if ( ! $connector->get_license_key() || ! $connector->get_license_activation_status() ) {
			return $transient;
		}

		// Check if we should run the update check.
		$check_transient = get_transient( 'buddyboss_addons_update_check' );
		if ( false !== $check_transient ) {
			$products_cache = get_transient( 'buddyboss_addons_cache' );
			if ( $products_cache && isset( $products_cache->products ) ) {
				return self::get_transient_with_addons_updates( $products_cache->products, $transient );
			}
			return $transient;
		}

		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}

		$products = self::get_addons( false );

		if ( is_wp_error( $products ) ) {
			// Set transient to prevent repeated checks.
			set_transient( 'buddyboss_addons_update_check', true, 30 * MINUTE_IN_SECONDS );
			return $transient;
		}

		if ( ! isset( $products->products ) || ! is_array( $products->products ) ) {
			return $transient;
		}

		$transient = self::get_transient_with_addons_updates( $products->products, $transient );

		// Set transient to check every 30 minutes.
		set_transient( 'buddyboss_addons_update_check', true, 30 * MINUTE_IN_SECONDS );

		return $transient;
	}

	/**
	 * Get modified transient with addon updates.
	 *
	 * @param array  $products  The products array.
	 * @param object $transient The transient object.
	 * @return object Modified transient.
	 */
	private static function get_transient_with_addons_updates( $products, $transient ) {
		foreach ( $products as $product ) {
			if ( ! isset( $product->main_file ) || ! isset( $transient->checked[ $product->main_file ] ) ) {
				continue;
			}

			$latest_version = isset( $product->_embedded->{'version-latest'}->number ) 
				? $product->_embedded->{'version-latest'}->number 
				: '';
			$download_url = isset( $product->_embedded->{'version-latest'}->url ) 
				? $product->_embedded->{'version-latest'}->url 
				: '';

			$item = (object) array(
				'id'          => $product->main_file,
				'slug'        => $product->slug,
				'plugin'      => $product->main_file,
				'new_version' => $latest_version,
				'package'     => $download_url,
				'icons'       => array(
					'default' => isset( $product->image ) ? $product->image : '',
				),
			);

			if ( version_compare( $transient->checked[ $product->main_file ], $latest_version, '>=' ) ) {
				$transient->no_update[ $product->main_file ] = $item;
			} else {
				$transient->response[ $product->main_file ] = $item;
			}
		}

		return $transient;
	}

	/**
	 * AJAX handler to activate an addon.
	 */
	public static function ajax_addon_activate() {
		if ( ! isset( $_POST['plugin'] ) ) {
			wp_send_json_error( esc_html__( 'Bad request.', 'buddyboss' ) );
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( esc_html__( 'Sorry, you don\'t have permission to do this.', 'buddyboss' ) );
		}

		if ( ! check_ajax_referer( 'bb_addons_nonce', false, false ) ) {
			wp_send_json_error( esc_html__( 'Security check failed.', 'buddyboss' ) );
		}

		$result = activate_plugins( wp_unslash( $_POST['plugin'] ) );
		$type   = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : 'add-on';

		if ( is_wp_error( $result ) ) {
			$message = ( 'plugin' === $type )
				? esc_html__( 'Could not activate plugin. Please activate from the Plugins page manually.', 'buddyboss' )
				: esc_html__( 'Could not activate add-on. Please activate from the Plugins page manually.', 'buddyboss' );
			wp_send_json_error( $message );
		}

		$message = ( 'plugin' === $type )
			? esc_html__( 'Plugin activated.', 'buddyboss' )
			: esc_html__( 'Add-on activated.', 'buddyboss' );
		wp_send_json_success( $message );
	}

	/**
	 * AJAX handler to deactivate an addon.
	 */
	public static function ajax_addon_deactivate() {
		if ( ! isset( $_POST['plugin'] ) ) {
			wp_send_json_error( esc_html__( 'Bad request.', 'buddyboss' ) );
		}

		if ( ! current_user_can( 'deactivate_plugins' ) ) {
			wp_send_json_error( esc_html__( 'Sorry, you don\'t have permission to do this.', 'buddyboss' ) );
		}

		if ( ! check_ajax_referer( 'bb_addons_nonce', false, false ) ) {
			wp_send_json_error( esc_html__( 'Security check failed.', 'buddyboss' ) );
		}

		deactivate_plugins( wp_unslash( $_POST['plugin'] ) );
		$type = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : 'add-on';

		$message = ( 'plugin' === $type )
			? esc_html__( 'Plugin deactivated.', 'buddyboss' )
			: esc_html__( 'Add-on deactivated.', 'buddyboss' );
		wp_send_json_success( $message );
	}

	/**
	 * AJAX handler to install an addon.
	 */
	public static function ajax_addon_install() {
		if ( ! isset( $_POST['plugin'] ) ) {
			wp_send_json_error( esc_html__( 'Bad request.', 'buddyboss' ) );
		}

		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( esc_html__( 'Sorry, you don\'t have permission to do this.', 'buddyboss' ) );
		}

		if ( ! check_ajax_referer( 'bb_addons_nonce', false, false ) ) {
			wp_send_json_error( esc_html__( 'Security check failed.', 'buddyboss' ) );
		}

		$type = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : 'add-on';
		$error = ( 'plugin' === $type )
			? esc_html__( 'Could not install plugin. Please download and install manually.', 'buddyboss' )
			: esc_html__( 'Could not install add-on.', 'buddyboss' );

		// Set current screen.
		set_current_screen();

		$url = esc_url_raw( admin_url( 'admin.php' ) );
		$creds = request_filesystem_credentials( $url, '', false, false, null );

		// Check filesystem permissions.
		if ( false === $creds ) {
			wp_send_json_error( $error );
		}

		if ( ! WP_Filesystem( $creds ) ) {
			wp_send_json_error( $error );
		}

		// Install the plugin.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		// Disable translation updates during installation.
		remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );

		$installer = new \Plugin_Upgrader( new \BuddyBoss\Core\Admin\Mothership\Manager\BB_Addon_Install_Skin() );
		$plugin    = wp_unslash( $_POST['plugin'] );
		$installer->install( $plugin );

		// Flush cache.
		wp_cache_flush();

		if ( $installer->plugin_info() ) {
			$plugin_basename = $installer->plugin_info();

			// Activate the plugin.
			$activated = activate_plugin( $plugin_basename );

			if ( ! is_wp_error( $activated ) ) {
				wp_send_json_success( array(
					'message'   => ( 'plugin' === $type )
						? esc_html__( 'Plugin installed & activated.', 'buddyboss' )
						: esc_html__( 'Add-on installed & activated.', 'buddyboss' ),
					'activated' => true,
					'basename'  => $plugin_basename,
				));
			} else {
				wp_send_json_success( array(
					'message'   => ( 'plugin' === $type )
						? esc_html__( 'Plugin installed.', 'buddyboss' )
						: esc_html__( 'Add-on installed.', 'buddyboss' ),
					'activated' => false,
					'basename'  => $plugin_basename,
				));
			}
		}

		wp_send_json_error( $error );
	}

	/**
	 * Get addons from the API.
	 *
	 * @param bool $cached Whether to use cached data.
	 * @return object|WP_Error The addons response or error.
	 */
	public static function get_addons( $cached = false ) {
		if ( $cached ) {
			$addons = get_transient( 'buddyboss_addons_cache' );
			if ( false !== $addons ) {
				return $addons;
			}
		}

		$api      = new BB_API_Request();
		$response = $api->get( 'products', array( '_embed' => 'version-latest' ) );

		// Cache successful responses.
		if ( ! $response->is_error() ) {
			set_transient( 'buddyboss_addons_cache', $response, HOUR_IN_SECONDS );
		}

		return $response;
	}

	/**
	 * Generate HTML for addons display.
	 *
	 * @return string The HTML output.
	 */
	public static function generate_addons_html() {
		$connector = BB_Plugin_Connector::get_instance();
		
		if ( ! $connector->get_license_key() ) {
			return '<div class="notice notice-error"><p>' . 
				esc_html__( 'Please enter your license key to access add-ons.', 'buddyboss' ) . 
				'</p></div>';
		}

		// Refresh addons if requested.
		if ( isset( $_POST['bb_refresh_addons'] ) ) {
			delete_transient( 'buddyboss_addons_cache' );
		}

		$addons = self::get_addons( true );
		
		if ( is_wp_error( $addons ) || ( isset( $addons->error ) && $addons->error ) ) {
			$error_message = is_wp_error( $addons ) ? $addons->get_error_message() : $addons->error;
			return sprintf(
				'<div class="notice notice-error"><p>%s <strong>%s</strong></p></div>',
				esc_html__( 'There was an issue connecting with the API.', 'buddyboss' ),
				esc_html( $error_message )
			);
		}

		self::enqueue_assets();
		
		ob_start();
		$products = isset( $addons->products ) ? $addons->products : array();
		include dirname( __DIR__ ) . '/views/addons.php';
		return ob_get_clean();
	}

	/**
	 * Enqueue assets for addons display.
	 */
	public static function enqueue_assets() {
		$base_url = plugin_dir_url( dirname( __DIR__ ) . '/class-bb-mothership-loader.php' );
		
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_script( 
			'bb-addons-js', 
			$base_url . 'assets/js/addons.js', 
			array( 'jquery' ), 
			'1.0.0', 
			true 
		);
		wp_enqueue_style( 
			'bb-addons-css', 
			$base_url . 'assets/css/addons.css', 
			array(), 
			'1.0.0' 
		);
		
		wp_localize_script( 'bb-addons-js', 'BBAddons', array(
			'ajax_url'              => admin_url( 'admin-ajax.php' ),
			'nonce'                 => wp_create_nonce( 'bb_addons_nonce' ),
			'active'                => esc_html__( 'Active', 'buddyboss' ),
			'inactive'              => esc_html__( 'Inactive', 'buddyboss' ),
			'activate'              => esc_html__( 'Activate', 'buddyboss' ),
			'deactivate'            => esc_html__( 'Deactivate', 'buddyboss' ),
			'install_failed'        => esc_html__( 'Could not install add-on. Please download and install manually.', 'buddyboss' ),
			'plugin_install_failed' => esc_html__( 'Could not install plugin. Please download and install manually.', 'buddyboss' ),
		));
	}
}

/**
 * Custom skin for addon installation.
 */
if ( ! class_exists( 'WP_Upgrader_Skin' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';
}

class BB_Addon_Install_Skin extends \WP_Upgrader_Skin {
	
	/**
	 * Constructor.
	 *
	 * @param array $args Arguments.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );
	}

	/**
	 * Empty header function.
	 */
	public function header() {}

	/**
	 * Empty footer function.
	 */
	public function footer() {}

	/**
	 * Empty error function.
	 *
	 * @param string|WP_Error $errors Errors.
	 */
	public function error( $errors ) {}

	/**
	 * Empty feedback function.
	 *
	 * @param string $string Feedback string.
	 * @param mixed  ...$args Additional arguments.
	 */
	public function feedback( $string, ...$args ) {}
}