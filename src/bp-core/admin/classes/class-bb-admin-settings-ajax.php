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

		add_action( 'wp_ajax_bb_admin_search_settings', array( $this, 'bb_admin_search_settings' ) );

		add_action( 'bb_admin_save_feature_settings_after', array( $this, 'bb_invalidate_search_index_after_save' ), 10, 3 );
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

		// Invalidate search index so it rebuilds with updated feature status.
		delete_transient( 'bb_settings_search_index' );

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

		/**
		 * Fires before feature settings are retrieved for the AJAX response.
		 *
		 * Allows late registration of fields that depend on data not available
		 * at the early `bb_register_features` hook (e.g., custom post types
		 * from third-party plugins that register on `init`).
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $feature_id The feature being loaded.
		 */
		do_action( 'bb_admin_settings_before_get_feature', $feature_id );

		// Get all fields for current values.
		$all_fields = $registry->bb_get_all_fields( $feature_id );

		// Get current values. BuddyBoss stores options via bp_get_option/bp_update_option (root blog on multisite).
		$settings = array();
		foreach ( $all_fields as $field_name => $field ) {
			// toggle_list with option_prefix: each option is stored as a separate key (e.g. bp-feed-platform-{key}).
			if ( 'toggle_list' === ( $field['type'] ?? '' ) && ! empty( $field['option_prefix'] ) && ! empty( $field['options'] ) ) {
				$prefix = $field['option_prefix'];
				$truthy = isset( $field['option_value_truthy'] ) ? (array) $field['option_value_truthy'] : array( 1, '1', true );
				$mapped = array();
				foreach ( $field['options'] as $option ) {
					$key = $option['value'] ?? '';
					if ( '' === $key ) {
						continue;
					}
					$opt_name = $prefix . $key;
					$stored   = bp_get_option( $opt_name, 1 );
					$mapped[ $key ] = in_array( $stored, $truthy, true ) ? 1 : 0;
				}
				$settings[ $field['name'] ] = $mapped;
			} else {
				$settings[ $field['name'] ] = bp_get_option( $field['name'], $field['default'] ?? '' );
			}
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
					'description' => wp_kses_post( $section['description'] ?? '' ),
					'order'       => $section['order'] ?? 100,
					'fields'      => $this->bb_format_fields_for_response( $section_fields, $settings, $feature_id ),
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
	 * @param array  $fields     Fields data.
	 * @param array  $values     Current field values.
	 * @param string $feature_id Feature ID (used for pro_notice computation).
	 *
	 * @return array Formatted fields data.
	 */
	private function bb_format_fields_for_response( $fields, $values = array(), $feature_id = '' ) {
		$formatted = array();

		foreach ( $fields as $field_name => $field ) {
			// Get stored value or default.
			$field_value = isset( $values[ $field['name'] ] ) ? $values[ $field['name'] ] : ( $field['default'] ?? '' );

			/*
			 * Handle checkbox_list (stored as associative array { "key": "1", "key2": "0" }).
			 * @since BuddyBoss [BBVERSION]
			 */
			if ( 'checkbox_list' === ( $field['type'] ?? '' ) ) {
				$blog_id      = function_exists( 'bp_get_root_blog_id' ) ? bp_get_root_blog_id() : get_current_blog_id();
				$stored_value = get_blog_option( $blog_id, $field['name'], array() );

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
						$is_enabled = ( 1 === $val || '1' === $val || true === $val );
					}
					$toggle_values[ $option_key ] = $is_enabled ? 1 : 0;
				}
				$field_value = $toggle_values;
			}

			// Single toggle/checkbox: normalize to 0|1 so React's !!value shows off when stored is 0 (not string "0").
			if ( in_array( $field['type'] ?? '', array( 'toggle', 'checkbox' ), true ) ) {
				$field_value = absint( $field_value );
			}

			// toggle_list: value from $values (or filter); normalize to int 0|1 for JS.
			if ( 'toggle_list' === ( $field['type'] ?? '' ) && is_array( $field_value ) ) {
				$field_value = array_map( 'absint', $field_value );
			}

			// Handle description_controls: read each control's value from DB (same storage as main options).
			// Each control maps to one %s placeholder in the description string (in order).
			// Use sequential %s placeholders — %1$s/%2$s are NOT supported by the frontend split logic.
			if ( ! empty( $field['description_controls'] ) && is_array( $field['description_controls'] ) ) {
				foreach ( $field['description_controls'] as $idx => $control ) {
					// 'self' type means the control uses the field's own name/options/value.
					if ( 'self' === ( $control['type'] ?? '' ) ) {
						continue;
					}
					if ( ! empty( $control['name'] ) ) {
						$control_default = $control['default'] ?? '';
						$field['description_controls'][ $idx ]['value'] = bp_get_option( $control['name'], $control_default );
					}
				}
			}

			// Handle toggle_list_array (stored as array of enabled values).
			if ( isset( $field['type'] ) && 'toggle_list_array' === $field['type'] ) {
				// Build default array from options (all enabled by default).
				$default_array = array();
				foreach ( $field['options'] ?? array() as $option ) {
					$default_array[] = $option['value'];
				}

				// Get stored value with proper default (same storage as legacy).
				$stored_array = bp_get_option( $field['name'], $default_array );

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
				// PRO notice badge data (for pro_only fields).
				'pro_notice'    => $field['pro_notice'] ?? null,
				// Notice type for notice fields (info, warning, error, success).
				'notice_type'           => $field['notice_type'] ?? null,
				// Inline controls embedded in description (replaces %s placeholders).
				'description_controls'  => $field['description_controls'] ?? null,
				// Help text displayed below description in lighter style.
				'help_text'             => $field['help_text'] ?? null,
				// Group ID for visual grouping of related fields (removes borders between them).
				'group'                 => $field['group'] ?? null,
			);

			// Auto-compute pro_notice for pro_only fields when not set at registration time.
			// Registration runs early (bb_register_features) before admin functions are loaded,
			// so pro_notice is computed here at AJAX time when all functions are available.
			if (
				! empty( $field_data['pro_only'] ) &&
				empty( $field_data['pro_notice'] ) &&
				function_exists( 'bb_admin_settings_get_pro_notice' )
			) {
				$pro_notice               = bb_admin_settings_get_pro_notice( $feature_id );
				$field_data['pro_notice'] = ! empty( $pro_notice['show'] ) ? $pro_notice : null;
			}

			/**
			 * Filters the field data before it is returned.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $field_data Formatted field data.
			 * @param array $field      Original field data.
			 *
			 * @return array|void Formatted field data or void if no changes are needed.
			 */
			$field_data = apply_filters( 'bb_admin_settings_format_field_data', $field_data, $field );

			// Sanitize description and help_text for safe use with dangerouslySetInnerHTML (XSS prevention).
			if ( isset( $field_data['description'] ) && is_string( $field_data['description'] ) ) {
				$field_data['description'] = wp_kses_post( $field_data['description'] );
			}
			if ( isset( $field_data['help_text'] ) && is_string( $field_data['help_text'] ) ) {
				$field_data['help_text'] = wp_kses_post( $field_data['help_text'] );
			}

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

		$registry = bb_feature_registry();

		/**
		 * Fires before feature settings are retrieved for the AJAX response.
		 *
		 * Allows late registration of fields that depend on data not available
		 * at the early `bb_register_features` hook (e.g., custom post types
		 * from third-party plugins that register on `init`).
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $feature_id The feature being loaded.
		 */
		do_action( 'bb_admin_settings_before_get_feature', $feature_id );

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

			// toggle_list with option_prefix: persist each key to option_prefix + key (e.g. bp-feed-platform-{key}).
			if ( 'toggle_list' === ( $field['type'] ?? '' ) && ! empty( $field['option_prefix'] ) && is_array( $value ) ) {
				$prefix = $field['option_prefix'];
				foreach ( $value as $opt_key => $opt_val ) {
					$opt_name = $prefix . $opt_key;
					bp_update_option( $opt_name, absint( $opt_val ) );
				}
				$saved[ $name ] = $value;
			} else {
				// BuddyBoss stores options via bp_update_option (same storage as legacy).
				bp_update_option( $name, $value );
				$saved[ $name ] = $value;
			}

			// Handle description_controls: save each control's value alongside the main field.
			// Each control maps to one %s placeholder in description (in order). Use %s, not %1$s/%2$s.
			if ( ! empty( $field['description_controls'] ) && is_array( $field['description_controls'] ) ) {
				foreach ( $field['description_controls'] as $control ) {
					// 'self' type uses the field's own name — already saved above.
					if ( 'self' === ( $control['type'] ?? '' ) || empty( $control['name'] ) ) {
						continue;
					}
					$control_name = $control['name'];
					if ( array_key_exists( $control_name, $settings ) ) {
						$control_value = $settings[ $control_name ];
						$control_type  = $control['type'] ?? 'text';

						// Apply control-specific sanitize callback if registered.
						if ( ! empty( $control['sanitize_callback'] ) && is_callable( $control['sanitize_callback'] ) ) {
							$control_value = call_user_func( $control['sanitize_callback'], $control_value );
						} elseif ( 'number' === $control_type || 'select' === $control_type ) {
							// Numeric types: use intval as default sanitizer.
							$control_value = intval( $control_value );
						} else {
							$control_value = sanitize_text_field( $control_value );
						}
						bp_update_option( $control_name, $control_value );
						$saved[ $control_name ] = $control_value;
					}
				}
			}
		}

		/**
		 * Fires after feature settings have been saved (same flow as Readylaunch: core save then feature-specific apply).
		 * Use this to persist feature-specific data (e.g. reaction items, button config) that are not simple options.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $feature_id Feature ID (e.g. 'reactions').
		 * @param array  $settings   Full submitted settings (JSON decoded).
		 * @param array  $saved      Keys and values saved to options by core.
		 */
		do_action( 'bb_admin_save_feature_settings_after', $feature_id, $settings, $saved );

		$response_data = array(
			'message' => __( 'Settings saved successfully.', 'buddyboss' ),
			'saved'   => $saved,
		);

		/**
		 * Filters the response data before sending success response for feature settings save.
		 * Allows plugins to add additional data to the response (e.g., migration_data for reactions).
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array  $response_data Response data to be sent.
		 * @param string $feature_id    Feature ID (e.g. 'reactions').
		 * @param array  $settings      Full submitted settings.
		 * @param array  $saved         Keys and values saved to options.
		 */
		$response_data = apply_filters( 'bb_admin_save_feature_settings_response', $response_data, $feature_id, $settings, $saved );

		wp_send_json_success( $response_data );
	}

	/**
	 * Search settings.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_admin_search_settings() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in bb_verify_request().
		$query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( strlen( $query ) < 2 ) {
			wp_send_json_success(
				array(
					'query'   => $query,
					'results' => array(),
					'count'   => 0,
				)
			);
		}

		$results = $this->bb_perform_search( $query );

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
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $query Search query.
	 *
	 * @return array Array of search results.
	 */
	private function bb_perform_search( $query ) {
		if ( ! function_exists( 'bb_feature_registry' ) || ! function_exists( 'bb_icon_registry' ) ) {
			return array();
		}

		$registry      = bb_feature_registry();
		$icon_registry = bb_icon_registry();

		// Get search index (cached or build on the fly).
		$cache_key = 'bb_settings_search_index';
		$index     = get_transient( $cache_key );

		if ( false === $index ) {
			$index = $this->bb_build_search_index();
			set_transient( $cache_key, $index, HOUR_IN_SECONDS );
		}

		// Search index.
		$matches = array();

		foreach ( $index as $entry ) {
			$score = 0;

			// Check field label (the highest priority).
			if ( isset( $entry['field_label'] ) && stripos( $entry['field_label'], $query ) !== false ) {
				$score += 10;
			}

			// Check field description.
			if ( isset( $entry['field_description'] ) && stripos( $entry['field_description'], $query ) !== false ) {
				$score += 5;
			}

			// Check section title.
			if ( isset( $entry['section_title'] ) && stripos( $entry['section_title'], $query ) !== false ) {
				$score += 3;
			}

			// Check the feature label.
			if ( isset( $entry['feature_label'] ) && stripos( $entry['feature_label'], $query ) !== false ) {
				$score += 2;
			}

			// Check the option name.
			if ( isset( $entry['field_name'] ) && stripos( $entry['field_name'], $query ) !== false ) {
				++$score;
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
			$feature   = $registry->bb_get_feature( $match['feature_id'] );
			$icon_data = null;

			if ( $feature && ! empty( $feature['icon'] ) ) {
				$icon_data = $icon_registry->bb_get_icon_for_rest( $feature['icon'] );
			}

			$formatted_results[] = array(
				'feature_id'    => $match['feature_id'],
				'feature_label' => $match['feature_label'],
				'feature_icon'  => $icon_data,
				'side_panel_id' => $match['side_panel_id'],
				'section_id'    => $match['section_id'],
				'section_title' => $match['section_title'],
				'field_name'    => $match['field_name'],
				'field_label'   => $match['field_label'],
				'breadcrumb'    => $match['breadcrumb'],
				'route'         => '/settings/' . $match['feature_id'] . '/' . $match['side_panel_id'],
			);
		}

		return $formatted_results;
	}

	/**
	 * Build search index from Feature Registry.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array Search index.
	 */
	private function bb_build_search_index() {
		$registry = bb_feature_registry();
		$index    = array();

		// Non-interactive field types to exclude from search (notices, status displays, info blocks).
		$excluded_types = array( 'notice', 'info', 'status', 'reaction_migration', 'reaction_notice', 'reaction_info', 'html' );

		/**
		 * Filters the field types excluded from the settings search index.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $excluded_types Field types to exclude.
		 */
		$excluded_types = apply_filters( 'bb_admin_search_excluded_field_types', $excluded_types );

		// Get all features.
		$features = $registry->bb_get_features();

		foreach ( $features as $feature_id => $feature ) {
			// Get side panels for this feature.
			$side_panels = $registry->bb_get_side_panels( $feature_id );

			foreach ( $side_panels as $side_panel_id => $side_panel ) {
				// Get sections for this side panel.
				$sections = $registry->bb_get_sections( $feature_id, $side_panel_id );

				foreach ( $sections as $section_id => $section ) {
					// Get fields for this section.
					$fields = $registry->bb_get_fields( $feature_id, $side_panel_id, $section_id );

					foreach ( $fields as $field_name => $field ) {
						// Skip non-interactive field types.
						if ( ! empty( $field['type'] ) && in_array( $field['type'], $excluded_types, true ) ) {
							continue;
						}

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
							'side_panel_id'     => $side_panel_id,
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
	 * Invalidate settings search index after feature settings are saved.
	 * Hooked to bb_admin_save_feature_settings_after so the next search rebuilds the index.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $feature_id Feature ID.
	 * @param array  $settings   Submitted settings.
	 * @param array  $saved      Saved keys and values.
	 */
	public function bb_invalidate_search_index_after_save( $feature_id, $settings, $saved ) {
		delete_transient( 'bb_settings_search_index' );
	}
}

// Initialize.
new BB_Admin_Settings_Ajax();
