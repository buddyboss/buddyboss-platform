<?php
/**
 * BuddyBoss Admin Settings 2.0 AJAX Handler
 *
 * Handles AJAX requests for the Settings 2.0 admin interface.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Settings_Ajax
 *
 * @since BuddyBoss 3.0.0
 */
class BB_Admin_Settings_Ajax {

	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'bb_admin_settings_2_0';

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function __construct() {
		$this->register_ajax_handlers();
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	private function register_ajax_handlers() {
		// Features.
		add_action( 'wp_ajax_bb_admin_get_features', array( $this, 'get_features' ) );
		add_action( 'wp_ajax_bb_admin_activate_feature', array( $this, 'activate_feature' ) );
		add_action( 'wp_ajax_bb_admin_deactivate_feature', array( $this, 'deactivate_feature' ) );

		// Search.
		add_action( 'wp_ajax_bb_admin_search_settings', array( $this, 'search_settings' ) );
	}

	/**
	 * Verify AJAX request.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return bool|void
	 */
	private function verify_request() {
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
	 * @since BuddyBoss 3.0.0
	 */
	public function get_features() {
		$this->verify_request();

		$features = array();

		if ( function_exists( 'bb_feature_registry' ) && function_exists( 'bb_icon_registry' ) ) {
			$registry      = bb_feature_registry();
			$icon_registry = bb_icon_registry();
			$registered    = $registry->get_features( array( 'status' => 'all' ) );

			// Get active components directly from option (bypasses bp_is_active() cache).
			$active_components = bp_get_option( 'bp-active-components', array() );

			foreach ( $registered as $feature_id => $feature ) {
				// Check active status from option directly to avoid bp_is_active() cache issues.
				$is_active = isset( $active_components[ $feature_id ] ) && ! empty( $active_components[ $feature_id ] );

				$formatted = array(
					'id'             => $feature_id,
					'label'          => $feature['label'] ?? $feature_id,
					'description'    => $feature['description'] ?? '',
					'category'       => $feature['category'] ?? 'community',
					'license_tier'   => $feature['license_tier'] ?? 'free',
					'status'         => $is_active ? 'active' : 'inactive',
					'available'      => $registry->is_feature_available( $feature_id ),
					'settings_route' => $feature['settings_route'] ?? '/settings/' . $feature_id,
				);

				// Format icon like REST API.
				if ( ! empty( $feature['icon'] ) ) {
					$formatted['icon'] = $icon_registry->get_icon_for_rest( $feature['icon'] );
				}

				$features[] = $formatted;
			}
		}

		wp_send_json_success( $features );
	}

	/**
	 * Activate a feature.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function activate_feature() {
		$this->verify_request();

		$feature_id = isset( $_POST['feature_id'] ) ? sanitize_text_field( wp_unslash( $_POST['feature_id'] ) ) : '';

		if ( empty( $feature_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature ID is required.', 'buddyboss' ) ) );
		}

		if ( ! function_exists( 'bb_feature_registry' ) || ! function_exists( 'bb_icon_registry' ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature registry not available.', 'buddyboss' ) ) );
		}

		$registry      = bb_feature_registry();
		$icon_registry = bb_icon_registry();
		$result        = $registry->activate_feature( $feature_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Get feature data (status will be overridden since bp_is_active() cache isn't updated yet).
		$feature   = $registry->get_feature( $feature_id );
		$formatted = $this->format_feature_for_response( $feature_id, $feature, $registry, $icon_registry );

		// Override status to 'active' since we just activated it successfully.
		$formatted['status'] = 'active';

		wp_send_json_success(
			array(
				'data'    => $formatted,
				'message' => sprintf(
					/* translators: %s: feature label */
					__( 'Feature "%s" activated successfully.', 'buddyboss' ),
					$feature['label']
				),
			)
		);
	}

	/**
	 * Deactivate a feature.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function deactivate_feature() {
		$this->verify_request();

		$feature_id = isset( $_POST['feature_id'] ) ? sanitize_text_field( wp_unslash( $_POST['feature_id'] ) ) : '';

		if ( empty( $feature_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature ID is required.', 'buddyboss' ) ) );
		}

		if ( ! function_exists( 'bb_feature_registry' ) || ! function_exists( 'bb_icon_registry' ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature registry not available.', 'buddyboss' ) ) );
		}

		$registry      = bb_feature_registry();
		$icon_registry = bb_icon_registry();
		$result        = $registry->deactivate_feature( $feature_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Get feature data (status will be overridden since bp_is_active() cache isn't updated yet).
		$feature   = $registry->get_feature( $feature_id );
		$formatted = $this->format_feature_for_response( $feature_id, $feature, $registry, $icon_registry );

		// Override status to 'inactive' since we just deactivated it successfully.
		$formatted['status'] = 'inactive';

		wp_send_json_success(
			array(
				'data'    => $formatted,
				'message' => sprintf(
					/* translators: %s: feature label */
					__( 'Feature "%s" deactivated successfully.', 'buddyboss' ),
					$feature['label']
				),
			)
		);
	}

	/**
	 * Format feature for response (matches REST API format).
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string              $feature_id    Feature ID.
	 * @param array               $feature       Feature data.
	 * @param BB_Feature_Registry $registry      Feature registry instance.
	 * @param BB_Icon_Registry    $icon_registry Icon registry instance.
	 * @return array Formatted feature data.
	 */
	private function format_feature_for_response( $feature_id, $feature, $registry, $icon_registry ) {
		$formatted = array(
			'id'             => $feature_id,
			'label'          => $feature['label'] ?? $feature_id,
			'description'    => $feature['description'] ?? '',
			'category'       => $feature['category'] ?? 'community',
			'license_tier'   => $feature['license_tier'] ?? 'free',
			'status'         => $registry->is_feature_active( $feature_id ) ? 'active' : 'inactive',
			'available'      => $registry->is_feature_available( $feature_id ),
			'settings_route' => $feature['settings_route'] ?? '/settings/' . $feature_id,
		);

		// Format icon.
		if ( ! empty( $feature['icon'] ) ) {
			$formatted['icon'] = $icon_registry->get_icon_for_rest( $feature['icon'] );
		}

		return $formatted;
	}

	/**
	 * Search settings.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function search_settings() {
		$this->verify_request();

		$query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';

		if ( strlen( $query ) < 2 ) {
			wp_send_json_success(
				array(
					'query'   => $query,
					'results' => array(),
					'count'   => 0,
				)
			);
		}

		$results = $this->perform_search( $query );

		wp_send_json_success(
			array(
				'query'   => $query,
				'results' => $results,
				'count'   => count( $results ),
			)
		);
	}

	/**
	 * Perform search across settings.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $query Search query.
	 * @return array Array of search results.
	 */
	private function perform_search( $query ) {
		if ( ! function_exists( 'bb_feature_registry' ) || ! function_exists( 'bb_icon_registry' ) ) {
			return array();
		}

		$registry      = bb_feature_registry();
		$icon_registry = bb_icon_registry();

		// Get search index (cached or build on the fly).
		$cache_key = 'bb_settings_search_index';
		$index     = get_transient( $cache_key );

		if ( false === $index ) {
			$index = $this->build_search_index();
			set_transient( $cache_key, $index, HOUR_IN_SECONDS );
		}

		// Search index.
		$query_lower = strtolower( $query );
		$matches     = array();

		foreach ( $index as $entry ) {
			$score = 0;

			// Check field label (highest priority).
			if ( isset( $entry['field_label'] ) && stripos( $entry['field_label'], $query_lower ) !== false ) {
				$score += 10;
			}

			// Check field description.
			if ( isset( $entry['field_description'] ) && stripos( $entry['field_description'], $query_lower ) !== false ) {
				$score += 5;
			}

			// Check section title.
			if ( isset( $entry['section_title'] ) && stripos( $entry['section_title'], $query_lower ) !== false ) {
				$score += 3;
			}

			// Check feature label.
			if ( isset( $entry['feature_label'] ) && stripos( $entry['feature_label'], $query_lower ) !== false ) {
				$score += 2;
			}

			// Check option name.
			if ( isset( $entry['field_name'] ) && stripos( $entry['field_name'], $query_lower ) !== false ) {
				$score += 1;
			}

			if ( $score > 0 ) {
				$entry['score'] = $score;
				$matches[]      = $entry;
			}
		}

		// Sort by score (descending).
		usort(
			$matches,
			function ( $a, $b ) {
				return ( $b['score'] ?? 0 ) - ( $a['score'] ?? 0 );
			}
		);

		// Format results for response.
		$formatted_results = array();
		foreach ( $matches as $match ) {
			$feature   = $registry->get_feature( $match['feature_id'] );
			$icon_data = null;

			if ( $feature && ! empty( $feature['icon'] ) ) {
				$icon_data = $icon_registry->get_icon_for_rest( $feature['icon'] );
			}

			$formatted_results[] = array(
				'feature_id'    => $match['feature_id'],
				'feature_label' => $match['feature_label'],
				'feature_icon'  => $icon_data,
				'section_id'    => $match['section_id'],
				'section_title' => $match['section_title'],
				'field_name'    => $match['field_name'],
				'field_label'   => $match['field_label'],
				'breadcrumb'    => $match['breadcrumb'],
				'route'         => '/settings/' . $match['feature_id'] . '/' . $match['section_id'],
			);
		}

		return $formatted_results;
	}

	/**
	 * Build search index from Feature Registry.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Search index.
	 */
	private function build_search_index() {
		$registry = bb_feature_registry();
		$index    = array();

		// Get all features.
		$features = $registry->get_features();

		foreach ( $features as $feature_id => $feature ) {
			// Get side panels for this feature.
			$side_panels = $registry->get_side_panels( $feature_id );

			foreach ( $side_panels as $side_panel_id => $side_panel ) {
				// Get sections for this side panel.
				$sections = $registry->get_sections( $feature_id, $side_panel_id );

				foreach ( $sections as $section_id => $section ) {
					// Get fields for this section.
					$fields = $registry->get_fields( $feature_id, $side_panel_id, $section_id );

					foreach ( $fields as $field_name => $field ) {
						// Build breadcrumb.
						$breadcrumb = sprintf(
							'%s → %s → %s',
							$feature['label'],
							$section['title'],
							$field['label']
						);

						$index[] = array(
							'feature_id'        => $feature_id,
							'feature_label'     => $feature['label'],
							'section_id'        => $section_id,
							'section_title'     => $section['title'],
							'field_name'        => $field_name,
							'field_label'       => $field['label'],
							'field_description' => $field['description'] ?? '',
							'breadcrumb'        => $breadcrumb,
						);
					}
				}
			}
		}

		return $index;
	}
}

// Initialize.
new BB_Admin_Settings_Ajax();
