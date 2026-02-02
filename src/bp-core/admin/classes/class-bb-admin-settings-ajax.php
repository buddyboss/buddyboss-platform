<?php
/**
 * BuddyBoss Admin Settings AJAX Handler
 *
 * Handles AJAX requests for the new admin settings interface.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core\Administration
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
		add_action( 'wp_ajax_bb_admin_toggle_feature', array( $this, 'bb_admin_toggle_feature' ) );
		add_action( 'wp_ajax_bb_admin_get_feature_settings', array( $this, 'bb_admin_get_feature_settings' ) );
		add_action( 'wp_ajax_bb_admin_save_feature_settings', array( $this, 'bb_admin_save_feature_settings' ) );
	}

	/**
	 * Get features.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_admin_get_features() {
		$this->bb_verify_request();

		$features = array();

		if ( function_exists( 'bb_feature_registry' ) && function_exists( 'bb_icon_registry' ) ) {
			$registry      = bb_feature_registry();
			$icon_registry = bb_icon_registry();
			$registered    = $registry->bb_get_features( array( 'status' => 'all' ) );

			// Get active features directly (bypasses bp_is_active() cache).
			// Primary storage: bb-active-features (single source of truth).
			$active_features = bp_get_option( 'bb-active-features', array() );
			// Fallback for migration: bp-active-components (legacy).
			$active_components = bp_get_option( 'bp-active-components', array() );

			foreach ( $registered as $feature_id => $feature ) {
				// Determine active status.
				// Priority: 1) bb-active-features, 2) bp-active-components (migration fallback).
				if ( isset( $active_features[ $feature_id ] ) ) {
					$is_active = ! empty( $active_features[ $feature_id ] );
				} elseif ( isset( $active_components[ $feature_id ] ) ) {
					// Migration fallback: use legacy component status.
					$is_active = ! empty( $active_components[ $feature_id ] );
				} else {
					// Not set in either - default to inactive.
					$is_active = false;
				}

				// For integrations, also check if the required plugin is available.
				$is_integration = ! empty( $feature['integration_id'] ) || 'integrations' === ( $feature['category'] ?? '' );
				if ( $is_integration ) {
					$is_available = $registry->bb_is_feature_available( $feature_id );
					if ( ! $is_available ) {
						$is_active = false;
					}
				}

				// settings_route: browser URL (query params); generated from feature_id. Frontend uses urlToRoute() for navigation.
				$formatted = array(
					'id'             => $feature_id,
					'label'          => $feature['label'] ?? $feature_id,
					'description'    => $feature['description'] ?? '',
					'category'       => $feature['category'] ?? 'community',
					'license_tier'   => $feature['license_tier'] ?? 'free',
					'status'         => $is_active ? 'active' : 'inactive',
					'available'      => $registry->bb_is_feature_available( $feature_id ),
					'settings_route' => function_exists( 'bb_get_feature_settings_url' ) ? bb_get_feature_settings_url( $feature_id ) : '',
				);

				// Format icon like REST API.
				if ( ! empty( $feature['icon'] ) ) {
					$formatted['icon'] = $icon_registry->bb_get_icon_for_rest( $feature['icon'] );
				}

				$features[] = $formatted;
			}
		}

		wp_send_json_success( $features );
	}

	/**
	 * Toggle a feature (activate or deactivate).
	 *
	 * Expects POST parameters:
	 * - feature_id: The feature ID.
	 * - status: Desired status ('active' or 'inactive').
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_admin_toggle_feature() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$feature_id = isset( $_POST['feature_id'] ) ? sanitize_text_field( wp_unslash( $_POST['feature_id'] ) ) : '';
		$status     = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $feature_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature ID is required.', 'buddyboss' ) ) );
		}

		if ( ! in_array( $status, array( 'active', 'inactive' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid status. Must be "active" or "inactive".', 'buddyboss' ) ) );
		}

		if ( ! function_exists( 'bb_feature_registry' ) || ! function_exists( 'bb_icon_registry' ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature registry not available.', 'buddyboss' ) ) );
		}

		$registry      = bb_feature_registry();
		$icon_registry = bb_icon_registry();
		$activate      = 'active' === $status;
		$result        = $activate
			? $registry->bb_activate_feature( $feature_id )
			: $registry->bb_deactivate_feature( $feature_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Get feature data (status will be overridden since bp_is_active() cache isn't updated yet).
		$feature   = $registry->bb_get_feature( $feature_id );
		$formatted = $this->bb_format_feature_for_response( $feature_id, $feature, $registry, $icon_registry );

		// Override status since bp_is_active() cache isn't updated yet.
		$formatted['status'] = $status;

		wp_send_json_success(
			array(
				'data'    => $formatted,
				'message' => sprintf(
					/* translators: 1: feature label, 2: activated/deactivated */
					__( 'Feature "%1$s" %2$s successfully.', 'buddyboss' ),
					$feature['label'],
					$activate ? __( 'activated', 'buddyboss' ) : __( 'deactivated', 'buddyboss' )
				),
			)
		);
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
	 * Get feature settings.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_admin_get_feature_settings() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$feature_id = isset( $_POST['feature_id'] ) ? sanitize_text_field( wp_unslash( $_POST['feature_id'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $feature_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature ID is required.', 'buddyboss' ) ) );
		}

		if ( ! function_exists( 'bb_feature_registry' ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature registry not available.', 'buddyboss' ) ) );
		}

		$registry = bb_feature_registry();

		// Get feature.
		$feature = $registry->bb_get_feature( $feature_id );
		if ( ! $feature ) {
			wp_send_json_error( array( 'message' => __( 'Feature not found.', 'buddyboss' ) ) );
		}

		// Get all fields for current values.
		$all_fields = $registry->bb_get_all_fields( $feature_id );

		// Get current values.
		$settings = array();
		foreach ( $all_fields as $field_name => $field ) {
			$settings[ $field['name'] ] = get_option( $field['name'], $field['default'] ?? '' );
		}

		// Get side panels.
		$side_panels = $registry->bb_get_side_panels( $feature_id );

		// Format side panels with their sections and fields.
		$formatted_side_panels = array();
		foreach ( $side_panels as $side_panel_id => $side_panel ) {
			// Get sections for this side panel.
			$sections = $registry->bb_get_sections( $feature_id, $side_panel_id );

			$formatted_sections = array();
			foreach ( $sections as $section_id => $section ) {
				// Get fields for this section.
				$section_fields = $registry->bb_get_fields( $feature_id, $side_panel_id, $section_id );

				$formatted_sections[] = array(
					'id'          => $section_id,
					'title'       => $section['title'],
					'description' => $section['description'] ?? '',
					'order'       => $section['order'] ?? 100,
					'fields'      => $this->bb_format_fields_for_response( $section_fields, $settings ),
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
		$nav_items = $registry->bb_get_nav_items( $feature_id );

		wp_send_json_success(
			array(
				'feature_id'  => $feature_id,
				'label'       => $feature['label'] ?? '',
				'description' => $feature['description'] ?? '',
				'icon'        => $feature['icon'] ?? null,
				'side_panels' => $formatted_side_panels,
				'navigation'  => array_values( $nav_items ),
				'settings'    => $settings,
			)
		);
	}

	/**
	 * Format feature for response (matches REST API format).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string              $feature_id    Feature ID.
	 * @param array               $feature       Feature data.
	 * @param BB_Feature_Registry $registry      Feature registry instance.
	 * @param BB_Icon_Registry    $icon_registry Icon registry instance.
	 * @return array Formatted feature data.
	 */
	private function bb_format_feature_for_response( $feature_id, $feature, $registry, $icon_registry ) {
		// settings_route: browser URL (query params); generated from feature_id. Frontend uses urlToRoute() for navigation.
		$formatted = array(
			'id'             => $feature_id,
			'label'          => $feature['label'] ?? $feature_id,
			'description'    => $feature['description'] ?? '',
			'category'       => $feature['category'] ?? 'community',
			'license_tier'   => $feature['license_tier'] ?? 'free',
			'status'         => $registry->bb_is_feature_active( $feature_id ) ? 'active' : 'inactive',
			'available'      => $registry->bb_is_feature_available( $feature_id ),
			'settings_route' => function_exists( 'bb_get_feature_settings_url' ) ? bb_get_feature_settings_url( $feature_id ) : '',
		);

		// Format icon.
		if ( ! empty( $feature['icon'] ) ) {
			$formatted['icon'] = $icon_registry->bb_get_icon_for_rest( $feature['icon'] );
		}

		return $formatted;
	}

	/**
	 * Format fields for response.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $fields Fields data.
	 * @param array $values Current field values.
	 *
	 * @return array Formatted fields data.
	 */
	private function bb_format_fields_for_response( $fields, $values = array() ) {
		$formatted = array();

		foreach ( $fields as $field_name => $field ) {
			// Get stored value or default.
			$field_value = isset( $values[ $field['name'] ] ) ? $values[ $field['name'] ] : ( $field['default'] ?? '' );

			/*
			 * Handle checkbox_list (stored as associative array { "key": "1", "key2": "0" }).
			 * @since BuddyBoss [BBVERSION]
			 */
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
						$val        = $stored_value[ $option_key ];
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
	 * Save feature settings.
	 *
	 * Receives all settings for a feature as JSON, applies registered
	 * sanitize callbacks, and persists to wp_options.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_admin_save_feature_settings() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$feature_id = isset( $_POST['feature_id'] ) ? sanitize_text_field( wp_unslash( $_POST['feature_id'] ) ) : '';
		$raw_json   = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $feature_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature ID is required.', 'buddyboss' ) ) );
		}

		if ( ! function_exists( 'bb_feature_registry' ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature registry not available.', 'buddyboss' ) ) );
		}

		$settings = json_decode( $raw_json, true );
		if ( ! is_array( $settings ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid settings data.', 'buddyboss' ) ) );
		}

		$registry   = bb_feature_registry();
		$all_fields = $registry->bb_get_all_fields( $feature_id );

		if ( empty( $all_fields ) ) {
			wp_send_json_error( array( 'message' => __( 'No fields registered for this feature.', 'buddyboss' ) ) );
		}

		$saved = array();

		foreach ( $all_fields as $field_key => $field ) {
			$name = $field['name'];

			// Only save settings that were submitted.
			if ( ! array_key_exists( $name, $settings ) ) {
				continue;
			}

			$value = $settings[ $name ];

			// Apply registered sanitize callback if present.
			if ( ! empty( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
				$value = call_user_func( $field['sanitize_callback'], $value );
			}

			update_option( $name, $value );
			$saved[ $name ] = $value;
		}

		wp_send_json_success(
			array(
				'message' => __( 'Settings saved successfully.', 'buddyboss' ),
				'saved'   => $saved,
			)
		);
	}
}

// Initialize.
new BB_Admin_Settings_Ajax();
