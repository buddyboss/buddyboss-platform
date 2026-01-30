<?php
/**
 * BuddyBoss Admin Settings AJAX Handler
 *
 * Handles AJAX requests for the new admin settings interface.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Settings_Ajax
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Admin_Settings_Ajax {

	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'bb_admin_settings';

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		$this->bb_register_ajax_handlers();
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function bb_register_ajax_handlers() {
		// Features.
		add_action( 'wp_ajax_bb_admin_get_features', array( $this, 'bb_admin_get_features' ) );
	}

	/**
	 * Verify AJAX request.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool|void
	 */
	private function bb_verify_request() {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Security check failed.', 'buddyboss' ) ),
				403
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied.', 'buddyboss' ) ),
				403
			);
		}

		return true;
	}

	/**
	 * Get features.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_admin_get_features() {
		$this->bb_verify_request();

		$features = array();

		if ( function_exists( 'bb_feature_registry' ) ) {
			$registry      = bb_feature_registry();
			$icon_registry = array();
			$registered    = $registry->bb_get_features( array( 'status' => 'all' ) );

			// Get active features directly (bypasses bp_is_active() cache).
			// Primary storage: bb-active-features (single source of truth).
			$active_features = bp_get_option( 'bb-active-features', array() );
			// Fallback for migration: bp-active-components (legacy).
			$active_components = bp_get_option( 'bp-active-components', array() );

			foreach ( $registered as $feature_id => $feature ) {

				$formatted = array(
					'id'             => $feature_id,
					'label'          => $feature['label'] ?? $feature_id,
					'description'    => $feature['description'] ?? '',
					'category'       => $feature['category'] ?? 'community',
					'license_tier'   => $feature['license_tier'] ?? 'free',
					'status'         => 'active',
					'settings_route' => $feature['settings_route'] ?? '/settings/' . $feature_id,
				);

				$features[] = $formatted;
			}
		}

		wp_send_json_success( $features );
	}
}

// Initialize.
new BB_Admin_Settings_Ajax();
