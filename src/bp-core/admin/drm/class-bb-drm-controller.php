<?php
/**
 * BuddyBoss DRM Controller
 *
 * Main controller for DRM functionality.
 * Manages hooks, license activation/deactivation events, and DRM checks.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since 3.0.0
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
	 */
	public static function init() {
		// Install/upgrade database tables.
		BB_DRM_Installer::install();

		// Initialize lockout system.
		BB_DRM_Lockout::init();

		$instance = new self();
		$instance->setup_hooks();
	}

	/**
	 * Setup WordPress hooks.
	 */
	private function setup_hooks() {
		// License activation/deactivation hooks.
		$plugin_id = defined( 'PLATFORM_EDITION' ) ? PLATFORM_EDITION : 'buddyboss-platform';
		add_action( $plugin_id . '_license_activated', array( $this, 'drm_license_activated' ) );
		add_action( $plugin_id . '_license_deactivated', array( $this, 'drm_license_deactivated' ) );
		add_action( $plugin_id . '_license_expired', array( $this, 'drm_license_invalid_expired' ) );
		add_action( $plugin_id . '_license_invalidated', array( $this, 'drm_license_invalid_expired' ) );

		// Run DRM checks on admin_init.
		add_action( 'admin_init', array( $this, 'drm_init' ), 20 );

		// AJAX handlers for notice dismissal.
		add_action( 'wp_ajax_bb_dismiss_notice_drm', array( $this, 'drm_dismiss_notice' ) );

		// Site Health integration.
		add_filter( 'site_status_tests', array( $this, 'add_site_health_tests' ) );
	}

	/**
	 * Handle license activation.
	 */
	public function drm_license_activated() {
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

		// Delete DRM notices.
		$notifications = new BB_Notifications();
		$notifications->dismiss_events( 'bb-drm' );
	}

	/**
	 * Handle license deactivation.
	 */
	public function drm_license_deactivated() {
		$drm_no_license = get_option( 'bb_drm_no_license', false );

		if ( ! $drm_no_license ) {
			delete_option( 'bb_drm_invalid_license' );

			// Set no license flag.
			update_option( 'bb_drm_no_license', true );

			// Create event.
			$drm = new BB_DRM_NoKey();
			$drm->create_event();
		}
	}

	/**
	 * Handle license expiration/invalidation.
	 */
	public function drm_license_invalid_expired() {
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
	 */
	public function drm_init() {
		if ( ! is_admin() ) {
			return;
		}

		// Check if license is valid.
		if ( BB_DRM_Helper::is_valid() ) {
			return;
		}

		$drm_no_license      = get_option( 'bb_drm_no_license', false );
		$drm_invalid_license = get_option( 'bb_drm_invalid_license', false );

		if ( $drm_no_license ) {
			$drm = new BB_DRM_NoKey();
			$drm->run();
		} elseif ( $drm_invalid_license ) {
			$drm = new BB_DRM_Invalid();
			$drm->run();
		}
	}

	/**
	 * Handle AJAX notice dismissal.
	 *
	 * Implements per-user dismissal tracking so each admin can dismiss notices independently.
	 */
	public function drm_dismiss_notice() {
		check_ajax_referer( 'bb_dismiss_notice', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to do this.', 'buddyboss' ) );
		}

		if ( ! isset( $_POST['notice'] ) || ! is_string( $_POST['notice'] ) ) {
			wp_send_json_error( __( 'Invalid notice key.', 'buddyboss' ) );
		}

		$notice    = sanitize_key( $_POST['notice'] );
		$secret    = isset( $_POST['secret'] ) ? sanitize_key( $_POST['secret'] ) : '';
		$notice_key = BB_DRM_Helper::prepare_dismissable_notice_key( $notice );

		// Verify secret hash.
		$secret_parts = explode( '-', $secret );
		$notice_hash  = $secret_parts[0] ?? '';
		$event_hash   = $secret_parts[1] ?? '';

		if ( $notice_hash !== hash( 'sha256', $notice ) ) {
			wp_send_json_error( __( 'Invalid security hash.', 'buddyboss' ) );
		}

		// Find the event.
		$event      = null;
		$event_name = null;

		if ( hash( 'sha256', BB_DRM_Helper::NO_LICENSE_EVENT ) === $event_hash ) {
			$event_name = BB_DRM_Helper::NO_LICENSE_EVENT;
		} elseif ( hash( 'sha256', BB_DRM_Helper::INVALID_LICENSE_EVENT ) === $event_hash ) {
			$event_name = BB_DRM_Helper::INVALID_LICENSE_EVENT;
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
				$data = $event_data['data'] ?? array();
				$data[ $notice_key ] = time();
				$event_data['data'] = $data;
				update_option( 'bb_drm_event_' . $event_name, $event_data );
			}
		}

		wp_send_json_success();
	}

	/**
	 * Add DRM tests to Site Health.
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

		// Check for DRM events.
		$drm_no_license      = get_option( 'bb_drm_no_license', false );
		$drm_invalid_license = get_option( 'bb_drm_invalid_license', false );

		if ( $drm_no_license ) {
			$event = BB_DRM_Event::latest( BB_DRM_Helper::NO_LICENSE_EVENT );
			if ( $event ) {
				$days = BB_DRM_Helper::days_elapsed( $event->created_at );
				return $this->get_no_license_site_health_result( $days );
			}
		}

		if ( $drm_invalid_license ) {
			$event = BB_DRM_Event::latest( BB_DRM_Helper::INVALID_LICENSE_EVENT );
			if ( $event ) {
				$days = BB_DRM_Helper::days_elapsed( $event->created_at );
				return $this->get_invalid_license_site_health_result( $days );
			}
		}

		// No license found.
		return array(
			'label'       => __( 'No BuddyBoss license found', 'buddyboss' ),
			'status'      => 'critical',
			'badge'       => array(
				'label' => __( 'BuddyBoss', 'buddyboss' ),
				'color' => 'red',
			),
			'description' => sprintf(
				'<p>%s</p>',
				__( 'No license key has been activated for BuddyBoss Platform. Please activate your license to ensure continued access to updates and support.', 'buddyboss' )
			),
			'actions'     => sprintf(
				'<p><a href="%s" class="button button-primary">%s</a></p>',
				admin_url( 'admin.php?page=buddyboss-settings' ),
				__( 'Activate License', 'buddyboss' )
			),
			'test'        => 'buddyboss_license_status',
		);
	}

	/**
	 * Get Site Health result for no license scenario.
	 *
	 * @param int $days Days elapsed since event.
	 * @return array Site Health test result.
	 */
	private function get_no_license_site_health_result( $days ) {
		$grace_days_left = max( 0, 30 - $days );

		if ( $days >= 30 ) {
			// Locked.
			return array(
				'label'       => __( 'BuddyBoss Platform is locked (no license)', 'buddyboss' ),
				'status'      => 'critical',
				'badge'       => array(
					'label' => __( 'BuddyBoss', 'buddyboss' ),
					'color' => 'red',
				),
				'description' => sprintf(
					'<p>%s</p><p>%s</p>',
					__( 'Your BuddyBoss Platform installation is currently locked due to missing license activation.', 'buddyboss' ),
					__( 'Please activate a valid license key immediately to restore functionality.', 'buddyboss' )
				),
				'actions'     => sprintf(
					'<p><a href="%s" class="button button-primary">%s</a> <a href="%s" target="_blank" class="button">%s</a></p>',
					admin_url( 'admin.php?page=buddyboss-settings' ),
					__( 'Activate License', 'buddyboss' ),
					'https://www.buddyboss.com/pricing/',
					__( 'Purchase License', 'buddyboss' )
				),
				'test'        => 'buddyboss_license_status',
			);
		} elseif ( $days >= 21 ) {
			// Medium warning.
			return array(
				'label'       => sprintf(
					/* translators: %d: days remaining */
					__( 'BuddyBoss license warning (%d days until lockout)', 'buddyboss' ),
					$grace_days_left
				),
				'status'      => 'critical',
				'badge'       => array(
					'label' => __( 'BuddyBoss', 'buddyboss' ),
					'color' => 'orange',
				),
				'description' => sprintf(
					'<p>%s</p>',
					sprintf(
						/* translators: %d: days remaining */
						__( 'Your BuddyBoss Platform will be locked in %d days if a license is not activated.', 'buddyboss' ),
						$grace_days_left
					)
				),
				'actions'     => sprintf(
					'<p><a href="%s" class="button button-primary">%s</a></p>',
					admin_url( 'admin.php?page=buddyboss-settings' ),
					__( 'Activate License', 'buddyboss' )
				),
				'test'        => 'buddyboss_license_status',
			);
		} else {
			// Low warning.
			return array(
				'label'       => sprintf(
					/* translators: %d: days remaining */
					__( 'BuddyBoss license needed (%d days remaining)', 'buddyboss' ),
					$grace_days_left
				),
				'status'      => 'recommended',
				'badge'       => array(
					'label' => __( 'BuddyBoss', 'buddyboss' ),
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					sprintf(
						/* translators: %d: days remaining */
						__( 'Please activate your BuddyBoss license within %d days to avoid service disruption.', 'buddyboss' ),
						$grace_days_left
					)
				),
				'actions'     => sprintf(
					'<p><a href="%s" class="button button-primary">%s</a></p>',
					admin_url( 'admin.php?page=buddyboss-settings' ),
					__( 'Activate License', 'buddyboss' )
				),
				'test'        => 'buddyboss_license_status',
			);
		}
	}

	/**
	 * Get Site Health result for invalid license scenario.
	 *
	 * @param int $days Days elapsed since event.
	 * @return array Site Health test result.
	 */
	private function get_invalid_license_site_health_result( $days ) {
		$grace_days_left = max( 0, 21 - $days );

		if ( $days >= 21 ) {
			// Locked.
			return array(
				'label'       => __( 'BuddyBoss Platform is locked (invalid license)', 'buddyboss' ),
				'status'      => 'critical',
				'badge'       => array(
					'label' => __( 'BuddyBoss', 'buddyboss' ),
					'color' => 'red',
				),
				'description' => sprintf(
					'<p>%s</p><p>%s</p>',
					__( 'Your BuddyBoss Platform installation is currently locked due to an invalid or expired license.', 'buddyboss' ),
					__( 'Please renew or update your license key immediately to restore functionality.', 'buddyboss' )
				),
				'actions'     => sprintf(
					'<p><a href="%s" class="button button-primary">%s</a> <a href="%s" target="_blank" class="button">%s</a></p>',
					admin_url( 'admin.php?page=buddyboss-settings' ),
					__( 'Update License', 'buddyboss' ),
					'https://www.buddyboss.com/my-account/',
					__( 'Renew License', 'buddyboss' )
				),
				'test'        => 'buddyboss_license_status',
			);
		} elseif ( $days >= 7 ) {
			// Medium warning.
			return array(
				'label'       => sprintf(
					/* translators: %d: days remaining */
					__( 'Invalid BuddyBoss license (%d days until lockout)', 'buddyboss' ),
					$grace_days_left
				),
				'status'      => 'critical',
				'badge'       => array(
					'label' => __( 'BuddyBoss', 'buddyboss' ),
					'color' => 'orange',
				),
				'description' => sprintf(
					'<p>%s</p>',
					sprintf(
						/* translators: %d: days remaining */
						__( 'Your BuddyBoss license is invalid or expired. Platform will be locked in %d days if not resolved.', 'buddyboss' ),
						$grace_days_left
					)
				),
				'actions'     => sprintf(
					'<p><a href="%s" target="_blank" class="button button-primary">%s</a> <a href="%s" class="button">%s</a></p>',
					'https://www.buddyboss.com/my-account/',
					__( 'Renew License', 'buddyboss' ),
					admin_url( 'admin.php?page=buddyboss-settings' ),
					__( 'Update License Key', 'buddyboss' )
				),
				'test'        => 'buddyboss_license_status',
			);
		} else {
			// Initial invalid state (< 7 days).
			return array(
				'label'       => __( 'BuddyBoss license is invalid or expired', 'buddyboss' ),
				'status'      => 'recommended',
				'badge'       => array(
					'label' => __( 'BuddyBoss', 'buddyboss' ),
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					sprintf(
						/* translators: %d: days remaining */
						__( 'Your license key is invalid or has expired. Please renew within %d days to avoid service disruption.', 'buddyboss' ),
						$grace_days_left
					)
				),
				'actions'     => sprintf(
					'<p><a href="%s" target="_blank" class="button button-primary">%s</a></p>',
					'https://www.buddyboss.com/my-account/',
					__( 'Renew License', 'buddyboss' )
				),
				'test'        => 'buddyboss_license_status',
			);
		}
	}
}
