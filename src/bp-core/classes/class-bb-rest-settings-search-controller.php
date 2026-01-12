<?php
/**
 * BP REST: BB_REST_Settings_Search_Controller class
 *
 * @package BuddyBoss
 * @since BuddyBoss 3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings Search endpoints.
 *
 * @since BuddyBoss 3.0.0
 */
class BB_REST_Settings_Search_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'settings/search';
	}

	/**
	 * Register the settings search routes.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'query' => array(
							'description'       => __( 'Search query (minimum 2 characters).', 'buddyboss' ),
							'type'              => 'string',
							'required'          => true,
							'minLength'         => 2,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/**
	 * Search settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since BuddyBoss 3.0.0
	 */
	public function get_items( $request ) {
		// Check rate limit (60 requests per minute).
		$rate_limiter = new BB_REST_Rate_Limiter();
		$rate_check   = $rate_limiter->check_rate_limit( 'settings_search', 60 );

		if ( is_wp_error( $rate_check ) ) {
			return $rate_check; // Return 429 error.
		}

		$query = $request->get_param( 'query' );

		// Validate query.
		if ( empty( $query ) || strlen( $query ) < 2 ) {
			return BB_REST_Response::error(
				'invalid_query',
				__( 'Search query must be at least 2 characters.', 'buddyboss' ),
				400
			);
		}

		// Get search index.
		$results = $this->search_settings( $query );

		// Return results.
		return BB_REST_Response::success(
			array(
				'query'   => $query,
				'results' => $results,
				'count'   => count( $results ),
			),
			array(
				'cached' => false,
			)
		);
	}

	/**
	 * Search settings across all features.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $query Search query.
	 * @return array Array of matching settings.
	 */
	private function search_settings( $query ) {
		$registry = bb_feature_registry();
		$icon_registry = bb_icon_registry();

		// Get search index (cached or build on the fly).
		$cache_key = 'bb_settings_search_index';
		$index = get_transient( $cache_key );

		if ( false === $index ) {
			$index = $this->build_search_index();
			set_transient( $cache_key, $index, HOUR_IN_SECONDS );
		}

		// Search index.
		$query_lower = strtolower( $query );
		$results = array();

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
				$results[] = $entry;
			}
		}

		// Sort by score (descending).
		usort( $results, function( $a, $b ) {
			return ( $b['score'] ?? 0 ) - ( $a['score'] ?? 0 );
		} );

		// Format results for response.
		$formatted_results = array();
		foreach ( $results as $result ) {
			$feature = $registry->get_feature( $result['feature_id'] );
			$icon_data = null;

			if ( $feature && ! empty( $feature['icon'] ) ) {
				$icon_data = $icon_registry->get_icon_for_rest( $feature['icon'] );
			}

			$formatted_results[] = array(
				'feature_id'    => $result['feature_id'],
				'feature_label' => $result['feature_label'],
				'feature_icon'  => $icon_data,
				'section_id'    => $result['section_id'],
				'section_title' => $result['section_title'],
				'field_name'    => $result['field_name'],
				'field_label'   => $result['field_label'],
				'breadcrumb'    => $result['breadcrumb'],
				'route'         => '/settings/' . $result['feature_id'] . '/' . $result['section_id'],
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
		$index = array();

		// Get all features.
		$features = $registry->get_features();

		foreach ( $features as $feature_id => $feature ) {
			// Get sections.
			$sections = $registry->get_sections( $feature_id );

			foreach ( $sections as $section_id => $section ) {
				// Get fields.
				$fields = $registry->get_fields( $feature_id, $section_id );

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

		return $index;
	}

	/**
	 * Check if a given request has access to search settings.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since BuddyBoss 3.0.0
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return BB_REST_Response::permission_error();
		}

		return true;
	}
}

/**
 * Rate Limiter Class (simple implementation).
 *
 * @since BuddyBoss 3.0.0
 */
class BB_REST_Rate_Limiter {

	/**
	 * Check rate limit for an endpoint.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $endpoint Endpoint identifier.
	 * @param int    $limit    Maximum requests per minute.
	 * @return bool|WP_Error True if allowed, WP_Error if rate limited.
	 */
	public function check_rate_limit( $endpoint, $limit = 60 ) {
		$user_id = get_current_user_id();
		$ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		$key     = "bb_rate_limit_{$endpoint}_{$user_id}_{$ip}";

		$count = get_transient( $key );

		if ( false === $count ) {
			$count = 0;
		}

		if ( $count >= $limit ) {
			return BB_REST_Response::rate_limit_error();
		}

		// Increment count.
		$count++;
		set_transient( $key, $count, MINUTE_IN_SECONDS );

		return true;
	}
}
