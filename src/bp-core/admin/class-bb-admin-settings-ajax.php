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
		add_action( 'wp_ajax_bb_admin_get_feature_settings', array( $this, 'get_feature_settings' ) );
		add_action( 'wp_ajax_bb_admin_save_feature_settings', array( $this, 'save_feature_settings' ) );

		// Search.
		add_action( 'wp_ajax_bb_admin_search_settings', array( $this, 'search_settings' ) );

		// Platform settings (generic option getter/setter).
		add_action( 'wp_ajax_bb_admin_get_platform_settings', array( $this, 'get_platform_settings' ) );
		add_action( 'wp_ajax_bb_admin_save_platform_setting', array( $this, 'save_platform_setting' ) );
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
	 * Get feature settings.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function get_feature_settings() {
		$this->verify_request();

		$feature_id = isset( $_POST['feature_id'] ) ? sanitize_text_field( wp_unslash( $_POST['feature_id'] ) ) : '';

		if ( empty( $feature_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature ID is required.', 'buddyboss' ) ) );
		}

		if ( ! function_exists( 'bb_feature_registry' ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature registry not available.', 'buddyboss' ) ) );
		}

		$registry = bb_feature_registry();

		// Get feature.
		$feature = $registry->get_feature( $feature_id );
		if ( ! $feature ) {
			wp_send_json_error( array( 'message' => __( 'Feature not found.', 'buddyboss' ) ) );
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
			usort(
				$formatted_sections,
				function ( $a, $b ) {
					return ( $a['order'] ?? 100 ) - ( $b['order'] ?? 100 );
				}
			);

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
		usort(
			$formatted_side_panels,
			function ( $a, $b ) {
				return ( $a['order'] ?? 100 ) - ( $b['order'] ?? 100 );
			}
		);

		// Update settings with processed values from formatted fields.
		foreach ( $formatted_side_panels as $panel ) {
			foreach ( $panel['sections'] ?? array() as $section ) {
				foreach ( $section['fields'] ?? array() as $field ) {
					if ( isset( $field['name'] ) && isset( $field['value'] ) ) {
						$settings[ $field['name'] ] = $field['value'];
					}
				}
			}
		}

		// Get navigation items.
		$nav_items = $registry->get_nav_items( $feature_id );

		wp_send_json_success(
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
	 * Save feature settings (only changed values).
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function save_feature_settings() {
		$this->verify_request();

		$feature_id = isset( $_POST['feature_id'] ) ? sanitize_text_field( wp_unslash( $_POST['feature_id'] ) ) : '';
		$settings   = isset( $_POST['settings'] ) ? json_decode( wp_unslash( $_POST['settings'] ), true ) : array();

		if ( empty( $feature_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature ID is required.', 'buddyboss' ) ) );
		}

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			wp_send_json_error( array( 'message' => __( 'No settings to save.', 'buddyboss' ) ) );
		}

		if ( ! function_exists( 'bb_feature_registry' ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature registry not available.', 'buddyboss' ) ) );
		}

		$registry = bb_feature_registry();

		// Check feature exists.
		$feature = $registry->get_feature( $feature_id );
		if ( ! $feature ) {
			wp_send_json_error( array( 'message' => __( 'Feature not found.', 'buddyboss' ) ) );
		}

		// Get all fields for this feature to validate.
		$all_fields = $registry->get_all_fields( $feature_id );

		// Build a lookup map for quick access.
		$field_map = array();
		foreach ( $all_fields as $field ) {
			$field_map[ $field['name'] ] = $field;

			// Also add sub-fields for dimensions/child_render type.
			if ( isset( $field['fields'] ) && is_array( $field['fields'] ) ) {
				foreach ( $field['fields'] as $sub_field ) {
					$sub_field_entry = array(
						'name'              => $sub_field['name'],
						'label'             => $sub_field['label'] ?? '',
						'type'              => $sub_field['type'] ?? 'text',
						'default'           => $sub_field['default'] ?? '',
						'sanitize_callback' => 'number' === ( $sub_field['type'] ?? '' ) ? 'intval' : 'sanitize_text_field',
						'options'           => $sub_field['options'] ?? array(),
					);
					$field_map[ $sub_field['name'] ] = $sub_field_entry;
				}
			}
		}

		$validation_errors = array();
		$updated_fields    = array();

		foreach ( $settings as $field_name => $value ) {
			// Skip if field doesn't exist in registry.
			if ( ! isset( $field_map[ $field_name ] ) ) {
				continue;
			}

			$field = $field_map[ $field_name ];

			// Handle toggle_list fields with option_prefix.
			if ( 'toggle_list' === ( $field['type'] ?? '' ) && ! empty( $field['option_prefix'] ) && is_array( $value ) ) {
				$option_prefix = $field['option_prefix'];
				foreach ( $value as $toggle_key => $toggle_value ) {
					$option_name = $option_prefix . $toggle_key;
					$old_value   = get_option( $option_name, '' );
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

			// Handle checkbox_list fields (stored as associative array { "key": "1", "key2": "0" }).
			if ( 'checkbox_list' === ( $field['type'] ?? '' ) && is_array( $value ) ) {
				$old_value = get_option( $field_name, array() );
				// Convert toggle format to associative array format.
				$new_value = array();
				foreach ( $value as $toggle_key => $toggle_value ) {
					$new_value[ $toggle_key ] = ! empty( $toggle_value ) ? 1 : 0;
				}
				update_option( $field_name, $new_value );
				$updated_fields[ $field_name ] = $new_value;

				// Log change to history.
				if ( class_exists( 'BB_Settings_History' ) && $old_value !== $new_value ) {
					$history = BB_Settings_History::instance();
					$history->log_change( $feature_id, $field_name, $old_value, $new_value );
				}
				continue;
			}

			// Handle toggle_list_array fields.
			if ( 'toggle_list_array' === ( $field['type'] ?? '' ) && is_array( $value ) ) {
				$old_value      = get_option( $field_name, array() );
				$enabled_values = array();
				foreach ( $value as $toggle_key => $toggle_value ) {
					if ( ! empty( $toggle_value ) ) {
						$enabled_values[] = $toggle_key;
					}
				}
				update_option( $field_name, $enabled_values );
				$updated_fields[ $field_name ] = $enabled_values;

				// Log change to history.
				if ( class_exists( 'BB_Settings_History' ) && $old_value !== $enabled_values ) {
					$history = BB_Settings_History::instance();
					$history->log_change( $feature_id, $field_name, $old_value, $enabled_values );
				}
				continue;
			}

			// Sanitize value based on field type.
			if ( isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
				$value = call_user_func( $field['sanitize_callback'], $value );
			} elseif ( is_string( $value ) ) {
				$value = sanitize_text_field( $value );
			}

			// Get old value.
			$old_value = get_option( $field_name, $field['default'] ?? '' );

			// Update option.
			update_option( $field_name, $value );
			$updated_fields[ $field_name ] = $value;

			// Log change to history.
			if ( class_exists( 'BB_Settings_History' ) && $old_value !== $value ) {
				$history = BB_Settings_History::instance();
				$history->log_change( $feature_id, $field_name, $old_value, $value );
			}
		}

		// If validation errors, return error response.
		if ( ! empty( $validation_errors ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Validation failed.', 'buddyboss' ),
					'errors'  => $validation_errors,
				)
			);
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

		wp_send_json_success(
			array(
				'updated' => $updated_fields,
				'message' => __( 'Settings saved successfully.', 'buddyboss' ),
			)
		);
	}

	/**
	 * Format fields for response.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $fields Fields data.
	 * @param array $values Current field values.
	 * @return array Formatted fields data.
	 */
	private function format_fields_for_response( $fields, $values = array() ) {
		$formatted = array();

		foreach ( $fields as $field_name => $field ) {
			// Get stored value or default.
			$field_value = isset( $values[ $field['name'] ] ) ? $values[ $field['name'] ] : ( $field['default'] ?? '' );

			// Handle checkbox_list (stored as associative array { "key": "1", "key2": "0" }).
			if ( 'checkbox_list' === ( $field['type'] ?? '' ) ) {
				// Read directly from database without any defaults or filters.
				global $wpdb;
				$blog_id    = function_exists( 'bp_get_root_blog_id' ) ? bp_get_root_blog_id() : get_current_blog_id();
				$table_name = $wpdb->get_blog_prefix( $blog_id ) . 'options';
				$row        = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM {$table_name} WHERE option_name = %s", $field['name'] ) );
				
				$stored_value = array();
				if ( $row && ! empty( $row->option_value ) ) {
					$stored_value = maybe_unserialize( $row->option_value );
				}
				
				// If no stored value, use field defaults.
				if ( empty( $stored_value ) || ! is_array( $stored_value ) ) {
					$stored_value = $field['default'] ?? array();
				}
				
				// Convert to toggle format for React (key => 0/1).
				$toggle_values = array();
				foreach ( $field['options'] ?? array() as $option ) {
					$option_key = $option['value'];
					// Check if key exists and has truthy value (handle "1", 1, true, "0", 0, false).
					$is_enabled = false;
					if ( isset( $stored_value[ $option_key ] ) ) {
						$val = $stored_value[ $option_key ];
						$is_enabled = ( $val === 1 || $val === '1' || $val === true );
					}
					$toggle_values[ $option_key ] = $is_enabled ? 1 : 0;
				}
				$field_value = $toggle_values;
			}

			if ( 'toggle_list' === ( $field['type'] ?? '' ) && ! empty( $field['option_prefix'] ) ) {
				// Read each option as a separate WordPress option.
				$option_prefix = $field['option_prefix'];
				$toggle_values = array();
				foreach ( $field['options'] ?? array() as $option ) {
					$option_key   = $option['value'];
					$option_name  = $option_prefix . $option_key;
					$stored_value = get_option( $option_name, '' );
					// Check if the stored value matches the option key (means enabled).
					$toggle_values[ $option_key ] = ( $stored_value === $option_key ) ? 1 : 0;
				}
				$field_value = $toggle_values;
			}

			// Handle toggle_list_array (stored as array of enabled values).
			if ( isset( $field['type'] ) && 'toggle_list_array' === $field['type'] ) {
				// Build default array from options (all enabled by default).
				$default_array = array();
				foreach ( $field['options'] ?? array() as $option ) {
					$default_array[] = $option['value'];
				}

				// Get stored value with proper default.
				$stored_array = get_option( $field['name'], $default_array );

				// Handle edge cases: empty string, false, or non-array values.
				if ( empty( $stored_array ) || ! is_array( $stored_array ) ) {
					$stored_array = $default_array;
				}

				// Convert indexed array to lookup map for faster checking.
				$enabled_map = array_flip( $stored_array );

				$toggle_values = array();
				foreach ( $field['options'] ?? array() as $option ) {
					$option_key = $option['value'];
					// Check if the key exists in the enabled map.
					$toggle_values[ $option_key ] = isset( $enabled_map[ $option_key ] ) ? 1 : 0;
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
				// Option prefix for toggle_list fields.
				'option_prefix' => $field['option_prefix'] ?? null,
				// Nested field support.
				'parent_field'  => $field['parent_field'] ?? null,
				'parent_value'  => $field['parent_value'] ?? null,
				// Prefix/suffix text support.
				'prefix'        => $field['prefix'] ?? null,
				'suffix'        => $field['suffix'] ?? null,
				// Toggle label (displayed next to toggle switch).
				'toggle_label'  => $field['toggle_label'] ?? null,
				// Inline label for toggles (alias for toggle_label).
				'inline_label'  => $field['inline_label'] ?? $field['toggle_label'] ?? null,
				// Min/max for number fields.
				'min'           => $field['min'] ?? null,
				'max'           => $field['max'] ?? null,
				// Invert value for "disable" toggles shown as "enable".
				'invert_value'  => $field['invert_value'] ?? false,
			);

			// Add sub-fields for dimensions/child_render type.
			if ( isset( $field['fields'] ) && is_array( $field['fields'] ) ) {
				$sub_fields = array();
				foreach ( $field['fields'] as $sub_field ) {
					$sub_field_value = isset( $values[ $sub_field['name'] ] ) ? $values[ $sub_field['name'] ] : ( $sub_field['default'] ?? '' );
					$sub_fields[]    = array(
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
		usort(
			$formatted,
			function ( $a, $b ) {
				return ( $a['order'] ?? 100 ) - ( $b['order'] ?? 100 );
			}
		);

		return $formatted;
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

	/**
	 * Get platform settings.
	 *
	 * Returns specified platform settings (WordPress options).
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function get_platform_settings() {
		$this->verify_request();

		// Get the option names to retrieve - handle both array and comma-separated string.
		$options_raw = isset( $_POST['options'] ) ? wp_unslash( $_POST['options'] ) : '';

		if ( is_array( $options_raw ) ) {
			$option_names = array_map( 'sanitize_text_field', $options_raw );
		} elseif ( is_string( $options_raw ) && ! empty( $options_raw ) ) {
			// Handle comma-separated string.
			$option_names = array_map( 'trim', explode( ',', $options_raw ) );
			$option_names = array_map( 'sanitize_text_field', $option_names );
		} else {
			$option_names = array();
		}

		if ( empty( $option_names ) ) {
			wp_send_json_error( array( 'message' => __( 'No options specified.', 'buddyboss' ) ), 400 );
		}

		// Whitelist of allowed options.
		$allowed_options = array(
			'bp-disable-group-type-creation',
			'bp-enable-group-auto-join',
		);

		$settings = array();
		foreach ( $option_names as $option_name ) {
			// Only allow whitelisted options.
			if ( in_array( $option_name, $allowed_options, true ) ) {
				$settings[ $option_name ] = bp_get_option( $option_name, false );
			}
		}

		wp_send_json_success( array( 'platform' => $settings ) );
	}

	/**
	 * Save a platform setting.
	 *
	 * Saves a single platform setting (WordPress option).
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function save_platform_setting() {
		$this->verify_request();

		$option_name  = isset( $_POST['option_name'] ) ? sanitize_text_field( wp_unslash( $_POST['option_name'] ) ) : '';
		$option_value = isset( $_POST['option_value'] ) ? sanitize_text_field( wp_unslash( $_POST['option_value'] ) ) : '';

		if ( empty( $option_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Option name is required.', 'buddyboss' ) ), 400 );
		}

		// Whitelist of allowed options.
		$allowed_options = array(
			'bp-disable-group-type-creation',
			'bp-enable-group-auto-join',
		);

		if ( ! in_array( $option_name, $allowed_options, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Option not allowed.', 'buddyboss' ) ), 403 );
		}

		// Convert string 'true'/'false' to boolean for saving.
		if ( 'true' === $option_value || '1' === $option_value ) {
			$option_value = true;
		} elseif ( 'false' === $option_value || '0' === $option_value || '' === $option_value ) {
			$option_value = false;
		}

		bp_update_option( $option_name, $option_value );

		wp_send_json_success(
			array(
				'message' => __( 'Setting saved successfully.', 'buddyboss' ),
				'option'  => array(
					'name'  => $option_name,
					'value' => $option_value,
				),
			)
		);
	}
}

// Initialize.
new BB_Admin_Settings_Ajax();
