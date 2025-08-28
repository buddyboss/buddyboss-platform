<?php

use GroundLevel\Container\Contracts\StaticContainerAwareness;
use GroundLevel\Mothership\Manager\LicenseManager;
use GroundLevel\Mothership\Manager\AddonsManager;

/**
 * BuddyBoss Platform Mothership Hooks
 *
 * Handles all the hooks and filters for the mothership functionality
 */
class BB_Mothership_Hooks implements StaticContainerAwareness {

	/**
	 * Container instance
	 *
	 * @var \GroundLevel\Container\Container
	 */
	protected static $container;

	/**
	 * Add hooks
	 */
	public static function add_hooks() {
		// Admin hooks.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );

		// AJAX hooks.
		add_action( 'wp_ajax_bb_platform_activate_license', array( __CLASS__, 'ajax_activate_license' ) );
		add_action( 'wp_ajax_bb_platform_deactivate_license', array( __CLASS__, 'ajax_deactivate_license' ) );
		add_action( 'wp_ajax_bb_platform_install_addon', array( __CLASS__, 'ajax_install_addon' ) );
		add_action( 'wp_ajax_bb_platform_activate_addon', array( __CLASS__, 'ajax_activate_addon' ) );
		add_action( 'wp_ajax_bb_platform_deactivate_addon', array( __CLASS__, 'ajax_deactivate_addon' ) );

		// In-product notifications.
		add_action( 'admin_notices', array( __CLASS__, 'display_notifications' ) );

		// Update checks.
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'check_for_updates' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 10, 3 );
	}

	/**
	 * Enqueue admin scripts
	 */
	public static function enqueue_admin_scripts() {
		BB_Mothership_Admin::instance()->enqueue_admin_scripts();
		BB_Mothership_Addons::instance()->enqueue_admin_scripts();
	}

	/**
	 * AJAX: Activate license
	 */
	public static function ajax_activate_license() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action.', 'buddyboss' ) );
		}

		if ( ! wp_verify_nonce( $_POST['nonce'], 'bb_platform_mothership_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'buddyboss' ) );
		}

		$license_key = sanitize_text_field( $_POST['license_key'] );

		if ( empty( $license_key ) ) {
			wp_send_json_error( __( 'License key is required.', 'buddyboss' ) );
		}

		try {
			// Use static LicenseManager directly
			$result = LicenseManager::activateLicense( $license_key );

			if ( $result ) {
				wp_send_json_success(
					array(
						'message' => __( 'License activated successfully.', 'buddyboss' ),
						'status'  => 'active',
					)
				);
			} else {
				wp_send_json_error( __( 'Failed to activate license.', 'buddyboss' ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * AJAX: Deactivate license
	 */
	public static function ajax_deactivate_license() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action.', 'buddyboss' ) );
		}

		if ( ! wp_verify_nonce( $_POST['nonce'], 'bb_platform_mothership_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'buddyboss' ) );
		}

		try {
			// Use static LicenseManager directly.
			$result = LicenseManager::deactivateLicense();

			if ( $result ) {
				wp_send_json_success(
					array(
						'message' => __( 'License deactivated successfully.', 'buddyboss' ),
						'status'  => 'inactive',
					)
				);
			} else {
				wp_send_json_error( __( 'Failed to deactivate license.', 'buddyboss' ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * AJAX: Install addon
	 */
	public static function ajax_install_addon() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( __( 'You do not have permission to install plugins.', 'buddyboss' ) );
		}

		if ( ! wp_verify_nonce( $_POST['nonce'], 'bb_platform_mothership_addons_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'buddyboss' ) );
		}

		$addon_slug = sanitize_text_field( $_POST['addon_slug'] );

		if ( empty( $addon_slug ) ) {
			wp_send_json_error( __( 'Addon slug is required.', 'buddyboss' ) );
		}

		try {
			// Use the static method from AddonsManager.
			$result = AddonsManager::ajaxAddonInstall();

			if ( $result ) {
				wp_send_json_success(
					array(
						'message' => __( 'Addon installed successfully.', 'buddyboss' ),
						'status'  => 'installed',
					)
				);
			} else {
				wp_send_json_error( __( 'Failed to install addon.', 'buddyboss' ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * AJAX: Activate addon
	 */
	public static function ajax_activate_addon() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( __( 'You do not have permission to activate plugins.', 'buddyboss' ) );
		}

		if ( ! wp_verify_nonce( $_POST['nonce'], 'bb_platform_mothership_addons_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'buddyboss' ) );
		}

		$plugin_file = sanitize_text_field( $_POST['plugin_file'] );

		if ( empty( $plugin_file ) ) {
			wp_send_json_error( __( 'Plugin file is required.', 'buddyboss' ) );
		}

		$result = activate_plugin( $plugin_file );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		} else {
			wp_send_json_success(
				array(
					'message' => __( 'Addon activated successfully.', 'buddyboss' ),
					'status'  => 'active',
				)
			);
		}
	}

	/**
	 * AJAX: Deactivate addon
	 */
	public static function ajax_deactivate_addon() {
		if ( ! current_user_can( 'deactivate_plugins' ) ) {
			wp_send_json_error( __( 'You do not have permission to deactivate plugins.', 'buddyboss' ) );
		}

		if ( ! wp_verify_nonce( $_POST['nonce'], 'bb_platform_mothership_addons_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'buddyboss' ) );
		}

		$plugin_file = sanitize_text_field( $_POST['plugin_file'] );

		if ( empty( $plugin_file ) ) {
			wp_send_json_error( __( 'Plugin file is required.', 'buddyboss' ) );
		}

		deactivate_plugins( $plugin_file );

		wp_send_json_success(
			array(
				'message' => __( 'Addon deactivated successfully.', 'buddyboss' ),
				'status'  => 'inactive',
			)
		);
	}

	/**
	 * Display in-product notifications
	 */
	public static function display_notifications() {
		// Check if we should display notifications.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get notifications from mothership.
		try {
			// Use the static method from Products API.
			$notifications = \GroundLevel\Mothership\Api\Request\Products::getNotifications( 'buddyboss-platform' );

			if ( ! empty( $notifications ) ) {
				foreach ( $notifications as $notification ) {
					self::display_notification( $notification );
				}
			}
		} catch ( Exception $e ) {
			// Silently fail.
		}
	}

	/**
	 * Display a single notification
	 */
	private static function display_notification( $notification ) {
		$type        = isset( $notification['type'] ) ? $notification['type'] : 'info';
		$message     = isset( $notification['message'] ) ? $notification['message'] : '';
		$dismissible = isset( $notification['dismissible'] ) ? $notification['dismissible'] : true;

		if ( empty( $message ) ) {
			return;
		}

		$class = 'notice notice-' . $type;
		if ( $dismissible ) {
			$class .= ' is-dismissible';
		}

		printf(
			'<div class="%1$s"><p>%2$s</p></div>',
			esc_attr( $class ),
			wp_kses_post( $message )
		);
	}

	/**
	 * Check for updates
	 */
	public static function check_for_updates( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		try {
			// Use the static method from AddonsManager for updates.
			$updates = AddonsManager::addonsUpdatePlugins( $transient );

			if ( ! empty( $updates ) ) {
				foreach ( $updates as $update ) {
					$transient->response[ $update['plugin'] ] = $update;
				}
			}
		} catch ( Exception $e ) {
			// Silently fail.
		}

		return $transient;
	}

	/**
	 * Plugin info for updates
	 */
	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		try {
			$mothershipService = self::getContainer()->get( \GroundLevel\Mothership\Service::class );
			// Use the correct method from MothershipService.
			$plugin_info = $mothershipService->getPluginInfo( $args->slug );

			if ( $plugin_info ) {
				return $plugin_info;
			}
		} catch ( Exception $e ) {
			// Silently fail.
		}

		return $result;
	}

	/**
	 * Get the container instance
	 *
	 * @return \GroundLevel\Container\Container
	 */
	public static function getContainer(): \GroundLevel\Container\Container {
		return self::$container;
	}

	/**
	 * Set the container instance
	 *
	 * @param \GroundLevel\Container\Container $container
	 */
	public static function setContainer( \GroundLevel\Container\Container $container ): void {
		self::$container = $container;
	}
}
