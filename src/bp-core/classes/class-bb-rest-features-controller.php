<?php
/**
 * BP REST: BB_REST_Features_Controller class
 *
 * @package BuddyBoss
 * @since BuddyBoss 3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Features endpoints.
 *
 * Handles the new hierarchy:
 * Feature → Side Panels → Sections → Fields
 *
 * @since BuddyBoss 3.0.0
 */
class BB_REST_Features_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'features';
	}

	/**
	 * Register the component features routes.
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
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9_-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => __( 'Feature ID.', 'buddyboss' ),
							'type'        => 'string',
							'required'    => true,
						),
						'embed' => array(
							'description' => __( 'Include related resources in response.', 'buddyboss' ),
							'type'        => 'boolean',
							'default'     => false,
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9_-]+)/activate',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'activate_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => __( 'Feature ID.', 'buddyboss' ),
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9_-]+)/deactivate',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'deactivate_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => __( 'Feature ID.', 'buddyboss' ),
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9_-]+)/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => __( 'Feature ID.', 'buddyboss' ),
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => __( 'Feature ID.', 'buddyboss' ),
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Retrieve features.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since BuddyBoss 3.0.0
	 */
	public function get_items( $request ) {
		$registry = bb_feature_registry();

		// Get filters.
		$status   = $request->get_param( 'status' );
		$category = $request->get_param( 'category' );
		$search   = $request->get_param( 'search' );

		// Build args.
		$args = array(
			'status'   => $status ? $status : 'all',
			'category' => $category ? $category : '',
			'search'   => $search ? $search : '',
		);

		// Get all features from registry.
		$all_features = $registry->get_features( $args );

		// Format features for response.
		$formatted_features = array();
		foreach ( $all_features as $feature_id => $feature ) {
			$formatted_features[] = $this->format_feature_for_response( $feature_id, $feature, false );
		}

		// Validate pagination params.
		$pagination = BB_REST_Response::validate_pagination_params( $request );

		// Get total count.
		$total = count( $formatted_features );

		// Apply pagination.
		$paged_features = array_slice(
			$formatted_features,
			$pagination['offset'],
			$pagination['per_page']
		);

		// Return paginated response.
		return BB_REST_Response::paginated(
			$paged_features,
			$total,
			$pagination['page'],
			$pagination['per_page'],
			array(
				'base_url'     => rest_url( $this->namespace . '/' . $this->rest_base ),
				'query_params' => array_filter( array(
					'status'   => $status,
					'category' => $category,
					'search'   => $search,
				) ),
			)
		);
	}

	/**
	 * Retrieve a single feature.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since BuddyBoss 3.0.0
	 */
	public function get_item( $request ) {
		$feature_id = $request->get_param( 'id' );
		$embed      = $request->get_param( 'embed' );

		$registry = bb_feature_registry();

		// Get feature from registry.
		$feature = $registry->get_feature( $feature_id );

		if ( ! $feature ) {
			return BB_REST_Response::not_found( 'feature', $feature_id );
		}

		// Check if feature is available (license check).
		if ( ! $registry->is_feature_available( $feature_id ) ) {
			return BB_REST_Response::error(
				'license_required',
				sprintf(
					/* translators: %s: feature label */
					__( 'Feature "%s" requires a valid license.', 'buddyboss' ),
					$feature['label']
				),
				403
			);
		}

		// Format feature for response.
		$formatted = $this->format_feature_for_response( $feature_id, $feature, $embed );

		// Return success response.
		return BB_REST_Response::success( $formatted );
	}

	/**
	 * Activate a feature.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since BuddyBoss 3.0.0
	 */
	public function activate_item( $request ) {
		$feature_id = $request->get_param( 'id' );

		// Verify nonce.
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return BB_REST_Response::nonce_error();
		}

		$registry = bb_feature_registry();

		// Activate feature.
		$result = $registry->activate_feature( $feature_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Get updated feature.
		$feature = $registry->get_feature( $feature_id );
		$formatted = $this->format_feature_for_response( $feature_id, $feature, false );

		return BB_REST_Response::success(
			$formatted,
			array(
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
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since BuddyBoss 3.0.0
	 */
	public function deactivate_item( $request ) {
		$feature_id = $request->get_param( 'id' );

		// Verify nonce.
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return BB_REST_Response::nonce_error();
		}

		$registry = bb_feature_registry();

		// Deactivate feature.
		$result = $registry->deactivate_feature( $feature_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Get updated feature.
		$feature = $registry->get_feature( $feature_id );
		$formatted = $this->format_feature_for_response( $feature_id, $feature, false );

		return BB_REST_Response::success(
			$formatted,
			array(
				'message' => sprintf(
					/* translators: %s: feature label */
					__( 'Feature "%s" deactivated successfully.', 'buddyboss' ),
					$feature['label']
				),
			)
		);
	}

	/**
	 * Get feature settings.
	 *
	 * Returns the full hierarchy: side_panels → sections → fields
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since BuddyBoss 3.0.0
	 */
	public function get_settings( $request ) {
		$feature_id = $request->get_param( 'id' );

		$registry = bb_feature_registry();

		// Get feature.
		$feature = $registry->get_feature( $feature_id );
		if ( ! $feature ) {
			return BB_REST_Response::not_found( 'feature', $feature_id );
		}

		// Get all fields for current values.
		$all_fields = $registry->get_all_fields( $feature_id );

		// Get current values.
		$settings = array();
		foreach ( $all_fields as $field_name => $field ) {
			$settings[ $field['name'] ] = get_option( $field['name'], $field['default'] ?? '' );
		}

		// Get side panels.
		$side_panels = $registry->get_side_panels( $feature_id );

		// Format side panels with their sections and fields.
		$formatted_side_panels = array();
		foreach ( $side_panels as $side_panel_id => $side_panel ) {
			// Get sections for this side panel.
			$sections = $registry->get_sections( $feature_id, $side_panel_id );

			$formatted_sections = array();
			foreach ( $sections as $section_id => $section ) {
				// Get fields for this section.
				$section_fields = $registry->get_fields( $feature_id, $side_panel_id, $section_id );

				$formatted_sections[] = array(
					'id'          => $section_id,
					'title'       => $section['title'],
					'description' => $section['description'] ?? '',
					'order'       => $section['order'] ?? 100,
					'fields'      => $this->format_fields_for_response( $section_fields, $settings ),
				);
			}

			// Sort sections by order.
			usort( $formatted_sections, function( $a, $b ) {
				return ( $a['order'] ?? 100 ) - ( $b['order'] ?? 100 );
			} );

			$formatted_side_panels[] = array(
				'id'         => $side_panel_id,
				'title'      => $side_panel['title'],
				'icon'       => $side_panel['icon'] ?? null,
				'help_url'   => $side_panel['help_url'] ?? '',
				'order'      => $side_panel['order'] ?? 100,
				'is_default' => $side_panel['is_default'] ?? false,
				'sections'   => $formatted_sections,
			);
		}

		// Sort side panels by order.
		usort( $formatted_side_panels, function( $a, $b ) {
			return ( $a['order'] ?? 100 ) - ( $b['order'] ?? 100 );
		} );

		// Get navigation items (links to non-settings screens like "All Activity").
		$nav_items = $registry->get_nav_items( $feature_id );

		return BB_REST_Response::success(
			array(
				'feature_id'  => $feature_id,
				'label'       => $feature['label'],
				'description' => $feature['description'] ?? '',
				'side_panels' => $formatted_side_panels,
				'navigation'  => array_values( $nav_items ),
				'settings'    => $settings,
			)
		);
	}

	/**
	 * Update feature settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since BuddyBoss 3.0.0
	 */
	public function update_settings( $request ) {
		$feature_id = $request->get_param( 'id' );
		$settings   = $request->get_json_params();

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return BB_REST_Response::permission_error();
		}

		// Verify nonce.
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return BB_REST_Response::nonce_error();
		}

		$registry = bb_feature_registry();

		// Check feature exists.
		$feature = $registry->get_feature( $feature_id );
		if ( ! $feature ) {
			return BB_REST_Response::not_found( 'feature', $feature_id );
		}

		// Validate each field.
		$validation_errors = array();
		$updated_fields    = array();

		// Get all fields for this feature.
		$all_fields = $registry->get_all_fields( $feature_id );

		// Build a lookup map for quick access.
		$field_map = array();
		foreach ( $all_fields as $field ) {
			$field_map[ $field['name'] ] = $field;

			// Also add sub-fields for dimensions/child_render type.
			if ( isset( $field['fields'] ) && is_array( $field['fields'] ) ) {
				foreach ( $field['fields'] as $sub_field ) {
					$sub_field_type = $sub_field['type'] ?? 'text';
					$sanitize_callback = 'sanitize_text_field';
					
					// Set appropriate sanitize callback based on type.
					if ( 'number' === $sub_field_type ) {
						$sanitize_callback = 'intval';
					}
					
					$sub_field_entry = array(
						'name'              => $sub_field['name'],
						'label'             => $sub_field['label'] ?? '',
						'type'              => $sub_field_type,
						'default'           => $sub_field['default'] ?? '',
						'sanitize_callback' => $sanitize_callback,
						'options'           => $sub_field['options'] ?? array(),
						'min'               => $sub_field['min'] ?? null,
						'max'               => $sub_field['max'] ?? null,
					);
					$field_map[ $sub_field['name'] ] = $sub_field_entry;
				}
			}
		}

		foreach ( $settings as $field_name => $value ) {
			// Find field definition.
			if ( ! isset( $field_map[ $field_name ] ) ) {
				$validation_errors[ $field_name ] = sprintf(
					/* translators: %s: field name */
					__( 'Field "%s" does not exist.', 'buddyboss' ),
					$field_name
				);
				continue;
			}

			$field = $field_map[ $field_name ];

			// Handle toggle_list fields with option_prefix (each toggle saved to separate option)
			if ( 'toggle_list' === ( $field['type'] ?? '' ) && ! empty( $field['option_prefix'] ) && is_array( $value ) ) {
				$option_prefix = $field['option_prefix'];
				foreach ( $value as $toggle_key => $toggle_value ) {
					$option_name = $option_prefix . $toggle_key;
					$old_value   = get_option( $option_name, '' );
					// Store the key value if enabled (1), empty string if disabled (0)
					$new_value   = $toggle_value ? $toggle_key : '';
					update_option( $option_name, $new_value );
					$updated_fields[ $option_name ] = $new_value;

					// Log change to history.
					if ( class_exists( 'BB_Settings_History' ) && $old_value !== $new_value ) {
						$history = BB_Settings_History::instance();
						$history->log_change( $feature_id, $option_name, $old_value, $new_value );
					}
				}
				continue;
			}

			// Sanitize.
			if ( isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
				$value = call_user_func( $field['sanitize_callback'], $value );
			}

			// Validate.
			if ( isset( $field['validate_callback'] ) && is_callable( $field['validate_callback'] ) ) {
				$validation = call_user_func( $field['validate_callback'], $value, $settings );
				if ( is_wp_error( $validation ) ) {
					$validation_errors[ $field_name ] = $validation->get_error_message();
					continue;
				}
			}

			// Get old value.
			$old_value = get_option( $field_name, $field['default'] ?? '' );

			// Update option.
			update_option( $field_name, $value );
			$updated_fields[ $field_name ] = $value;

			// Log change to history (if history class exists).
			if ( class_exists( 'BB_Settings_History' ) ) {
				$history = BB_Settings_History::instance();
				$history->log_change( $feature_id, $field_name, $old_value, $value );
			}
		}

		// If validation errors, return error response.
		if ( ! empty( $validation_errors ) ) {
			return BB_REST_Response::validation_error( $validation_errors );
		}

		// Clear caches.
		$registry->clear_feature_caches( $feature_id );

		/**
		 * Fired after feature settings are updated.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $feature_id     Feature ID.
		 * @param array  $updated_fields Updated field values.
		 */
		do_action( 'bb_feature_settings_updated', $feature_id, $updated_fields );

		// Return success response.
		return BB_REST_Response::success(
			array(
				'updated' => $updated_fields,
			),
			array(
				'message' => __( 'Settings updated successfully.', 'buddyboss' ),
			)
		);
	}

	/**
	 * Format feature for REST API response.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID.
	 * @param array  $feature    Feature data.
	 * @param bool   $embed      Whether to include related resources.
	 * @return array Formatted feature data.
	 */
	private function format_feature_for_response( $feature_id, $feature, $embed = false ) {
		$registry = bb_feature_registry();
		$icon_registry = bb_icon_registry();

		$formatted = array(
			'id'            => $feature_id,
			'label'         => $feature['label'],
			'description'   => $feature['description'] ?? '',
			'category'      => $feature['category'] ?? 'community',
			'license_tier'  => $feature['license_tier'] ?? 'free',
			'status'        => $registry->is_feature_active( $feature_id ) ? 'active' : 'inactive',
			'available'     => $registry->is_feature_available( $feature_id ),
			'settings_route' => $feature['settings_route'] ?? '/settings/' . $feature_id,
		);

		// Format icon.
		if ( ! empty( $feature['icon'] ) ) {
			$formatted['icon'] = $icon_registry->get_icon_for_rest( $feature['icon'] );
		}

		// Include side panels, sections, and fields if embed requested.
		if ( $embed ) {
			// Get all fields for current values.
			$all_fields = $registry->get_all_fields( $feature_id );
			$settings = array();
			foreach ( $all_fields as $field ) {
				$settings[ $field['name'] ] = get_option( $field['name'], $field['default'] ?? '' );
			}

			// Get side panels.
			$side_panels = $registry->get_side_panels( $feature_id );

			$formatted_side_panels = array();
			foreach ( $side_panels as $side_panel_id => $side_panel ) {
				$sections = $registry->get_sections( $feature_id, $side_panel_id );

				$formatted_sections = array();
				foreach ( $sections as $section_id => $section ) {
					$section_fields = $registry->get_fields( $feature_id, $side_panel_id, $section_id );
					$formatted_sections[] = array(
						'id'          => $section_id,
						'title'       => $section['title'],
						'description' => $section['description'] ?? '',
						'order'       => $section['order'] ?? 100,
						'fields'      => $this->format_fields_for_response( $section_fields, $settings ),
					);
				}

				$formatted_side_panels[] = array(
					'id'         => $side_panel_id,
					'title'      => $side_panel['title'],
					'icon'       => $side_panel['icon'] ?? null,
					'help_url'   => $side_panel['help_url'] ?? '',
					'order'      => $side_panel['order'] ?? 100,
					'is_default' => $side_panel['is_default'] ?? false,
					'sections'   => $formatted_sections,
				);
			}

			$formatted['side_panels'] = $formatted_side_panels;

			// Include navigation items.
			$nav_items = $registry->get_nav_items( $feature_id );
			$formatted['navigation'] = array_values( $nav_items );
		}

		return $formatted;
	}

	/**
	 * Format fields for REST API response.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $fields  Fields data.
	 * @param array $values  Current field values (optional).
	 * @return array Formatted fields data.
	 */
	private function format_fields_for_response( $fields, $values = array() ) {
		$formatted = array();

		foreach ( $fields as $field_name => $field ) {
			// Handle toggle_list with option_prefix (each option stored as separate option)
			$field_value = isset( $values[ $field['name'] ] ) ? $values[ $field['name'] ] : ( $field['default'] ?? '' );

			if ( 'toggle_list' === ( $field['type'] ?? '' ) && ! empty( $field['option_prefix'] ) ) {
				// Read each option as a separate WordPress option
				$option_prefix = $field['option_prefix'];
				$toggle_values = array();
				foreach ( $field['options'] ?? array() as $option ) {
					$option_key   = $option['value'];
					$option_name  = $option_prefix . $option_key;
					$stored_value = get_option( $option_name, '' );
					// Check if the stored value matches the option key (means enabled)
					$toggle_values[ $option_key ] = ( $stored_value === $option_key ) ? 1 : 0;
				}
				$field_value = $toggle_values;
			}

			$field_data = array(
				'name'          => $field['name'],
				'label'         => $field['label'],
				'type'          => $field['type'] ?? 'text',
				'description'   => $field['description'] ?? '',
				'default'       => $field['default'] ?? '',
				'options'       => $field['options'] ?? array(),
				'conditional'   => $field['conditional'] ?? null,
				'pro_only'      => $field['pro_only'] ?? false,
				'license_tier'  => $field['license_tier'] ?? 'free',
				'order'         => $field['order'] ?? 100,
				'value'         => $field_value,
				// Option prefix for toggle_list fields
				'option_prefix' => $field['option_prefix'] ?? null,
				// Nested field support
				'parent_field'  => $field['parent_field'] ?? null,
				'parent_value'  => $field['parent_value'] ?? null,
				// Prefix/suffix text support
				'prefix'        => $field['prefix'] ?? null,
				'suffix'        => $field['suffix'] ?? null,
				// Toggle label (displayed next to toggle switch)
				'toggle_label'  => $field['toggle_label'] ?? null,
				// Inline label for toggles (alias for toggle_label)
				'inline_label'  => $field['inline_label'] ?? $field['toggle_label'] ?? null,
				// Min/max for number fields
				'min'           => $field['min'] ?? null,
				'max'           => $field['max'] ?? null,
				// Invert value for "disable" toggles shown as "enable"
				'invert_value'  => $field['invert_value'] ?? false,
			);

			// Add sub-fields for dimensions/child_render type
			if ( isset( $field['fields'] ) && is_array( $field['fields'] ) ) {
				$sub_fields = array();
				foreach ( $field['fields'] as $sub_field ) {
					$sub_field_value = isset( $values[ $sub_field['name'] ] ) ? $values[ $sub_field['name'] ] : ( $sub_field['default'] ?? '' );
					$sub_fields[] = array(
						'name'    => $sub_field['name'],
						'label'   => $sub_field['label'] ?? '',
						'type'    => $sub_field['type'] ?? 'text',
						'default' => $sub_field['default'] ?? '',
						'value'   => $sub_field_value,
						'options' => $sub_field['options'] ?? array(),
						'suffix'  => $sub_field['suffix'] ?? null,
						'min'     => $sub_field['min'] ?? null,
						'max'     => $sub_field['max'] ?? null,
					);
				}
				$field_data['fields'] = $sub_fields;
			}

			$formatted[] = $field_data;
		}

		// Sort by order.
		usort( $formatted, function( $a, $b ) {
			return ( $a['order'] ?? 100 ) - ( $b['order'] ?? 100 );
		} );

		return $formatted;
	}

	/**
	 * Check if a given request has access to list features.
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

	/**
	 * Check if a given request has access to get a feature.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since BuddyBoss 3.0.0
	 */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to update a feature.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since BuddyBoss 3.0.0
	 */
	public function update_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 * @since BuddyBoss 3.0.0
	 */
	public function get_collection_params() {
		return array(
			'page' => array(
				'description'       => __( 'Page number for pagination.', 'buddyboss' ),
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'description'       => __( 'Number of items per page.', 'buddyboss' ),
				'type'              => 'integer',
				'default'           => 20,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
			),
			'status' => array(
				'description'       => __( 'Filter by feature status.', 'buddyboss' ),
				'type'              => 'string',
				'enum'              => array( 'active', 'inactive', 'all' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'category' => array(
				'description'       => __( 'Filter by category.', 'buddyboss' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'search' => array(
				'description'       => __( 'Search term to filter results.', 'buddyboss' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Get the public item schema.
	 *
	 * @return array
	 * @since BuddyBoss 3.0.0
	 */
	public function get_public_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'feature',
			'type'       => 'object',
			'properties' => array(
				'id'           => array(
					'description' => __( 'Feature ID.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'label'        => array(
					'description' => __( 'Feature label.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'description'  => array(
					'description' => __( 'Feature description.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'status'       => array(
					'description' => __( 'Feature status.', 'buddyboss' ),
					'type'        => 'string',
					'enum'        => array( 'active', 'inactive' ),
					'readonly'    => true,
				),
				'available'    => array(
					'description' => __( 'Whether feature is available (license check).', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			),
		);
	}
}
