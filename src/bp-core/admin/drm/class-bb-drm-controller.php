<?php
/**
 * BuddyBoss DRM Controller
 *
 * Main controller for DRM functionality.
 * Manages hooks, license activation/deactivation events, and DRM checks.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since BuddyBoss 2.16.0
 */

namespace BuddyBoss\Core\Admin\DRM;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * DRM Controller class.
 */
class BB_DRM_Controller {

	/**
	 * Initialize the DRM controller.
	 *
	 * @since BuddyBoss 2.16.0
	 */
	public static function init() {
		// Install/upgrade database tables.
		BB_DRM_Installer::install();

		$instance = new self();
		$instance->setup_hooks();
	}

	/**
	 * Get the dynamic plugin ID from Mothership connection.
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @return string The plugin ID.
	 */
	private function get_plugin_id() {
		// Check if Mothership loader is available.
		if ( ! class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Mothership_Loader' ) ) {
			// Fallback to PLATFORM_EDITION constant or default.
			return defined( 'PLATFORM_EDITION' ) ? PLATFORM_EDITION : 'buddyboss-platform';
		}

		try {
			// Use a singleton instance to avoid duplicate hook registration.
			$loader           = \BuddyBoss\Core\Admin\Mothership\BB_Mothership_Loader::instance();
			$plugin_connector = $loader->getContainer()->get( \BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection::class );
			return $plugin_connector->pluginId;
		} catch ( \Exception $e ) {
			// Fallback to PLATFORM_EDITION constant or default if container access fails.
			return defined( 'PLATFORM_EDITION' ) ? PLATFORM_EDITION : 'buddyboss-platform';
		}
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @since BuddyBoss 2.16.0
	 */
	private function setup_hooks() {
		// License status change hook from Mothership.
		// Get dynamic plugin ID from Mothership connection.
		$plugin_id = $this->get_plugin_id();

		// Listen to vendor's license_status_changed hook.
		// This hook is fired by LicenseManager::checkLicenseStatus() every 12 hours via cron.
		// It provides both activation (valid=true) and deactivation/expiration (valid=false) events.
		add_action( $plugin_id . '_license_status_changed', array( $this, 'drm_license_status_changed' ), 10, 2 );

		// Run DRM checks on admin_init.
		add_action( 'admin_init', array( $this, 'drm_init' ), 20 );

		// AJAX handlers for notice dismissal.
		add_action( 'wp_ajax_bb_dismiss_notice_drm', array( $this, 'drm_dismiss_notice' ) );

		// Site Health integration.
		add_filter( 'site_status_tests', array( $this, 'add_site_health_tests' ) );
	}

	/**
	 * Handle license status changes from Mothership.
	 * This is the primary hook fired by the vendor's LicenseManager.
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @param bool   $is_valid Whether the license is valid.
	 * @param object $status   The response object from Mothership API.
	 */
	public function drm_license_status_changed( $is_valid, $status ) {
		if ( $is_valid ) {
			// License is valid - clean up DRM state.
			$this->drm_license_activated();
		} else {
			// License is invalid/expired - initialize DRM.
			$this->drm_license_invalid_expired();
		}
	}

	/**
	 * Handle license activation/cleanup.
	 * Called internally by drm_license_status_changed() when license becomes valid.
	 *
	 * @since BuddyBoss 2.16.0
	 */
	private function drm_license_activated() {
		delete_option( 'bb_drm_no_license' );
		delete_option( 'bb_drm_invalid_license' );

		// Delete all DRM events from database.
		$no_license_event = BB_DRM_Event::latest( BB_DRM_Helper::NO_LICENSE_EVENT );
		if ( $no_license_event ) {
			$no_license_event->destroy();
		}

		$invalid_license_event = BB_DRM_Event::latest( BB_DRM_Helper::INVALID_LICENSE_EVENT );
		if ( $invalid_license_event ) {
			$invalid_license_event->destroy();
		}

		// Clean up old option-based events (for migration).
		delete_option( 'bb_drm_event_' . BB_DRM_Helper::NO_LICENSE_EVENT );
		delete_option( 'bb_drm_event_' . BB_DRM_Helper::INVALID_LICENSE_EVENT );

		// Delete all DRM-related in-plugin notifications (IPN).
		$notifications = new BB_Notifications();
		$notifications->dismiss_events( 'bb-drm' );
		$notifications->dismiss_events( 'bb-drm-consolidated' );

		// Also dismiss individual addon notifications.
		$notifications->dismiss_events( 'bb-drm-addon-' );
	}

	/**
	 * Handle license expiration/invalidation.
	 * Called internally by drm_license_status_changed() when license becomes invalid.
	 *
	 * @since BuddyBoss 2.16.0
	 */
	private function drm_license_invalid_expired() {
		$drm_invalid_license = get_option( 'bb_drm_invalid_license', false );

		if ( ! $drm_invalid_license ) {
			delete_option( 'bb_drm_no_license' );

			// Set invalid license flag.
			update_option( 'bb_drm_invalid_license', true );

			// Create event.
			$drm = new BB_DRM_Invalid();
			$drm->create_event();
		}
	}

	/**
	 * Initialize DRM checks.
	 *
	 * Runs Platform DRM checks for no-license and invalid-license scenarios.
	 * Sends emails, notifications, and admin notices based on license status.
	 * Add-ons manage their own lockout via BB_DRM_Registry.
	 *
	 * @since BuddyBoss 2.16.0
	 */
	public function drm_init() {
		// Check if Platform license is valid.
		if ( BB_DRM_Helper::is_valid() ) {
			// License is valid - no DRM checks needed.
			return;
		}

		// License is not valid - determine which DRM check to run.
		$has_license_key = BB_DRM_Helper::has_key();

		if ( ! $has_license_key ) {
			// No license key scenario.
			$drm_no_license = get_option( 'bb_drm_no_license', false );

			if ( $drm_no_license ) {
				// Event already exists, run DRM checks.
				$drm = new BB_DRM_NoKey();
				$drm->run();
			}
		} else {
			// Invalid/expired license key scenario.
			$drm_invalid_license = get_option( 'bb_drm_invalid_license', false );

			if ( $drm_invalid_license ) {
				// Event already exists, run DRM checks.
				$drm = new BB_DRM_Invalid();
				$drm->run();
			}
		}
	}

	/**
	 * Handle AJAX notice dismissal.
	 *
	 * Implements per-user dismissal tracking so each admin can dismiss notices independently.
	 *
	 * @since BuddyBoss 2.16.0
	 */
	public function drm_dismiss_notice() {
		check_ajax_referer( 'bb_dismiss_notice', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to do this.', 'buddyboss' ) );
		}

		if ( ! isset( $_POST['notice'] ) || ! is_string( $_POST['notice'] ) ) {
			wp_send_json_error( __( 'Invalid notice key.', 'buddyboss' ) );
		}

		$notice     = sanitize_key( $_POST['notice'] );
		$secret     = isset( $_POST['secret'] ) ? sanitize_key( $_POST['secret'] ) : '';
		$notice_key = BB_DRM_Helper::prepare_dismissable_notice_key( $notice );

		// Verify secret hash.
		$secret_parts = explode( '-', $secret );
		$notice_hash  = $secret_parts[0] ?? '';
		$event_hash   = $secret_parts[1] ?? '';

		if ( hash( 'sha256', $notice ) !== $notice_hash ) {
			wp_send_json_error( __( 'Invalid security hash.', 'buddyboss' ) );
		}

		// Find the event.
		$event      = null;
		$event_name = null;

		// Check Platform DRM events.
		if ( hash( 'sha256', BB_DRM_Helper::NO_LICENSE_EVENT ) === $event_hash ) {
			$event_name = BB_DRM_Helper::NO_LICENSE_EVENT;
		} elseif ( hash( 'sha256', BB_DRM_Helper::INVALID_LICENSE_EVENT ) === $event_hash ) {
			$event_name = BB_DRM_Helper::INVALID_LICENSE_EVENT;
		} else {
			// Check addon DRM events.
			// Event names are stored as hash, so we need to search all addon events.
			global $wpdb;
			$table_name = BB_DRM_Event::get_table_name();
			$events     = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DISTINCT event FROM {$table_name} WHERE event LIKE %s",
					'addon-%'
				)
			);

			foreach ( $events as $evt ) {
				if ( hash( 'sha256', $evt->event ) === $event_hash ) {
					$event_name = $evt->event;
					break;
				}
			}
		}

		if ( $event_name ) {
			// Get event from database.
			$event = BB_DRM_Event::latest( $event_name );

			if ( $event ) {
				// Use new per-user dismissal method.
				$result = BB_DRM_Helper::dismiss_notice_for_user( $event, $notice_key );

				if ( $result ) {
					wp_send_json_success( array( 'message' => __( 'Notice dismissed for 24 hours.', 'buddyboss' ) ) );
				} else {
					wp_send_json_error( __( 'Failed to dismiss notice.', 'buddyboss' ) );
				}
			}

			// Fallback to old option-based method for backwards compatibility.
			$event_data = get_option( 'bb_drm_event_' . $event_name, array() );
			if ( ! empty( $event_data ) ) {
				$data                = $event_data['data'] ?? array();
				$data[ $notice_key ] = time();
				$event_data['data']  = $data;
				update_option( 'bb_drm_event_' . $event_name, $event_data );
			}
		}

		wp_send_json_success();
	}

	/**
	 * Add DRM tests to Site Health.
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @param array $tests Site Health tests array.
	 * @return array Modified tests array.
	 */
	public function add_site_health_tests( $tests ) {
		$tests['direct']['buddyboss_license_status'] = array(
			'label' => __( 'BuddyBoss License Status', 'buddyboss' ),
			'test'  => array( $this, 'site_health_license_test' ),
		);

		return $tests;
	}

	/**
	 * Site Health test for license status.
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @return array Test result.
	 */
	public function site_health_license_test() {
		// Dev environment bypass.
		if ( BB_DRM_Helper::is_dev_environment() ) {
			return array(
				'label'       => __( 'License check bypassed (development environment)', 'buddyboss' ),
				'status'      => 'good',
				'badge'       => array(
					'label' => __( 'BuddyBoss', 'buddyboss' ),
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					__( 'License validation is automatically disabled on development environments.', 'buddyboss' )
				),
				'actions'     => '',
				'test'        => 'buddyboss_license_status',
			);
		}

		// Check if license is valid.
		if ( BB_DRM_Helper::is_valid() ) {
			return array(
				'label'       => __( 'BuddyBoss license is active and valid', 'buddyboss' ),
				'status'      => 'good',
				'badge'       => array(
					'label' => __( 'BuddyBoss', 'buddyboss' ),
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					__( 'Your BuddyBoss license is active and valid. You have full access to all features and updates.', 'buddyboss' )
				),
				'actions'     => '',
				'test'        => 'buddyboss_license_status',
			);
		}

		// No license found - Platform still works, just show recommendation.
		return array(
			'label'       => __( 'BuddyBoss license not activated', 'buddyboss' ),
			'status'      => 'recommended',
			'badge'       => array(
				'label' => __( 'BuddyBoss', 'buddyboss' ),
				'color' => 'blue',
			),
			'description' => sprintf(
				'<p>%s</p><p>%s</p>',
				__( 'No license key has been activated for BuddyBoss Platform. While Platform features will continue to work, add-on plugins require license activation.', 'buddyboss' ),
				__( 'Activate your license to manage add-on plugins and ensure access to updates and support.', 'buddyboss' )
			),
			'actions'     => sprintf(
				'<p><a href="%s" class="button button-primary">%s</a></p>',
				admin_url( 'admin.php?page=buddyboss-settings' ),
				__( 'Activate Your License', 'buddyboss' )
			),
			'test'        => 'buddyboss_license_status',
		);
	}
}
