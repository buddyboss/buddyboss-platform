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
	}

	/**
	 * Handle license activation.
	 */
	public function drm_license_activated() {
		delete_option( 'bb_drm_no_license' );
		delete_option( 'bb_drm_invalid_license' );

		// Delete all DRM events.
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

		// Find and update the event.
		$event = null;
		if ( hash( 'sha256', BB_DRM_Helper::NO_LICENSE_EVENT ) === $event_hash ) {
			$event_name = BB_DRM_Helper::NO_LICENSE_EVENT;
		} elseif ( hash( 'sha256', BB_DRM_Helper::INVALID_LICENSE_EVENT ) === $event_hash ) {
			$event_name = BB_DRM_Helper::INVALID_LICENSE_EVENT;
		}

		if ( isset( $event_name ) ) {
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
}
