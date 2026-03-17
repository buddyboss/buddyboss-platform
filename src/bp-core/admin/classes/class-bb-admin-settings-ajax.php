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

				// Compute availability once (used below for integration check and response).
				$is_available = $registry->bb_is_feature_available( $feature_id );

				// For integrations, also check if the required plugin is available.
				$is_integration = ! empty( $feature['integration_id'] ) || 'integrations' === ( $feature['category'] ?? '' );
				if ( $is_integration ) {
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
					'available'      => $is_available,
					'required'       => ! empty( $feature['required'] ),
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

		$response = array(
			'data'    => $formatted,
			'message' => sprintf(
				/* translators: 1: feature label, 2: activated/deactivated */
				__( 'Feature "%1$s" %2$s successfully.', 'buddyboss' ),
				$feature['label'],
				$activate ? __( 'activated', 'buddyboss' ) : __( 'deactivated', 'buddyboss' )
			),
		);

		// Include ALL dependents that are now unavailable so React can grey out their cards.
		// This covers both dependents that were just cascade-deactivated AND those already inactive.
		if ( ! $activate ) {
			$unavailable_dependents = array();
			foreach ( $registry->bb_get_features() as $fid => $f ) {
				if ( ! empty( $f['depends_on'] ) && in_array( $feature_id, $f['depends_on'], true ) ) {
					$unavailable_dependents[] = $fid;
				}
			}
			if ( ! empty( $unavailable_dependents ) ) {
				$response['deactivated_dependents'] = $unavailable_dependents;
			}
		}

		// When activating a feature, notify React about dependents that become available again.
		if ( $activate ) {
			$reactivatable = array();
			foreach ( $registry->bb_get_features() as $fid => $f ) {
				if ( ! empty( $f['depends_on'] ) && in_array( $feature_id, $f['depends_on'], true ) ) {
					$reactivatable[] = $fid;
				}
			}
			if ( ! empty( $reactivatable ) ) {
				$response['reactivatable_dependents'] = $reactivatable;
			}
		}

		wp_send_json_success( $response );
	}

	/**
	 * Verify AJAX request.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool|void
	 */
	private function bb_verify_request() {
		if ( ! bp_current_user_can( 'bp_moderate' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied.', 'buddyboss' ) ),
				403
			);
		}

		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Security check failed.', 'buddyboss' ) ),
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

		// Batch-prime the WP object cache for all option names to avoid N+1 get_option() queries.
		$option_names = array();
		foreach ( $all_fields as $field ) {
			$option_names[] = $field['name'];

			// Also collect toggle_list option_prefix options.
			if ( 'toggle_list' === ( $field['type'] ?? '' ) && ! empty( $field['option_prefix'] ) ) {
				foreach ( $field['options'] ?? array() as $option ) {
					$option_names[] = $field['option_prefix'] . $option['value'];
				}
			}
		}

		if ( ! empty( $option_names ) ) {
			$this->bb_prime_option_caches( $option_names );
		}

		// Get current values (now served from object cache, not individual DB queries). BuddyBoss stores options via bp_get_option/bp_update_option (root blog on multisite).
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
					$opt_name       = $prefix . $key;
					$stored         = bp_get_option( $opt_name, 1 );
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

				$formatted_section = array(
					'id'          => $section_id,
					'title'       => $section['title'],
					'description' => wp_kses_post( $section['description'] ?? '' ),
					'order'       => $section['order'] ?? 100,
					'fields'      => $this->bb_format_fields_for_response( $section_fields, $settings, $feature_id ),
				);

				// Include conditional if set.
				if ( ! empty( $section['conditional'] ) ) {
					$formatted_section['conditional'] = $section['conditional'];
				}

				// Include section_toggle if set.
				if ( ! empty( $section['section_toggle'] ) ) {
					$formatted_section['section_toggle'] = $section['section_toggle'];
					// Ensure the toggle option is loaded into settings.
					$toggle_name = $section['section_toggle'];
					if ( ! isset( $settings[ $toggle_name ] ) ) {
						$settings[ $toggle_name ] = bp_get_option( $toggle_name, 1 );
					}
				}

				// Include status if set (e.g. Connected/Not Connected badges).
				if ( ! empty( $section['status'] ) && is_array( $section['status'] ) ) {
					$formatted_section['status'] = array(
						'type' => sanitize_key( $section['status']['type'] ?? 'info' ),
						'text' => sanitize_text_field( $section['status']['text'] ?? '' ),
					);
				}

				$formatted_sections[] = $formatted_section;
			}

			// Sort sections by order.
			usort( $formatted_sections, 'bb_sort_by_order' );

			$formatted_side_panels[] = array(
				'id'         => $side_panel_id,
				'title'      => $side_panel['title'],
				'icon'       => $side_panel['icon'] ?? null,
				'help_url'   => $side_panel['help_url'] ?? '',
				'order'      => $side_panel['order'] ?? 100,
				'is_default' => $side_panel['is_default'] ?? false,
				'divider'    => ! empty( $side_panel['divider'] ),
				'sections'   => $formatted_sections,
			);
		}

		// Sort side panels by order.
		usort( $formatted_side_panels, 'bb_sort_by_order' );

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
			'required'       => ! empty( $feature['required'] ),
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
				// Iterate stored_value keys first to preserve user-saved order (drag-and-drop).
				$toggle_values = array();
				foreach ( $stored_value as $option_key => $val ) {
					$is_enabled                   = ( 1 === $val || '1' === $val || true === $val );
					$toggle_values[ $option_key ] = $is_enabled ? 1 : 0;
				}

				// Append any options not yet in stored_value (e.g., newly added options).
				foreach ( $field['options'] ?? array() as $option ) {
					$option_key = $option['value'];
					if ( ! isset( $toggle_values[ $option_key ] ) ) {
						$toggle_values[ $option_key ] = 0;
					}
				}
				$field_value = $toggle_values;
			}

			/*
			 * Handle share_platforms (stored as indexed array of enabled platform slugs).
			 * Legacy format: ['messenger', 'facebook', 'twitter'] — only checked platforms.
			 * Converts to { messenger: 1, facebook: 1, twitter: 1, linkedin: 0 } for React.
			 *
			 * @since BuddyBoss [BBVERSION]
			 */
			if ( 'share_platforms' === ( $field['type'] ?? '' ) ) {
				$blog_id      = function_exists( 'bp_get_root_blog_id' ) ? bp_get_root_blog_id() : get_current_blog_id();
				$stored_value = get_blog_option( $blog_id, $field['name'], null );

				// Only use field defaults when option doesn't exist at all.
				if ( null === $stored_value ) {
					$stored_value = $field['default'] ?? array();
				}

				if ( ! is_array( $stored_value ) ) {
					$stored_value = array();
				}

				// Convert an indexed array of enabled slugs to key => 0/1 for React.
				$toggle_values = array();
				foreach ( $field['options'] ?? array() as $option ) {
					$option_key                   = $option['value'];
					$toggle_values[ $option_key ] = in_array( $option_key, $stored_value, true ) ? 1 : 0;
				}
				$field_value = $toggle_values;
			}

			// Single toggle/checkbox: normalize to 0|1 so React's !!value shows off when stored is 0 (not string "0").
			if ( in_array( $field['type'] ?? '', array( 'toggle', 'checkbox' ), true ) ) {
				$field_value = absint( $field_value );
			}

			// toggle_list: normalize to int 0|1 for JS.
			// For extension_data fields (e.g., video extensions), extract is_active from nested arrays.
			if ( 'toggle_list' === ( $field['type'] ?? '' ) && is_array( $field_value ) ) {
				if ( ! empty( $field['extension_data'] ) ) {
					$field_value = $this->bb_extract_extension_toggle_values( $field_value );
				} else {
					$field_value = array_map( 'absint', $field_value );
				}
			}

			// document_extensions: extract is_active from nested arrays (same pattern as toggle_list with extension_data).
			if ( 'document_extensions' === ( $field['type'] ?? '' ) && is_array( $field_value ) ) {
				$field_value = $this->bb_extract_extension_toggle_values( $field_value );
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
						$control_default                                = $control['default'] ?? '';
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
				'name'                 => $field['name'],
				'label'                => $field['label'],
				'type'                 => $field['type'] ?? 'text',
				'description'          => $field['description'] ?? '',
				'default'              => $field['default'] ?? '',
				'options'              => $field['options'] ?? array(),
				'conditional'          => $field['conditional'] ?? null,
				'pro_only'             => $field['pro_only'] ?? false,
				'license_tier'         => $field['license_tier'] ?? 'free',
				'order'                => $field['order'] ?? 100,
				'value'                => $field_value,
				// Nested field support.
				'parent_field'         => $field['parent_field'] ?? null,
				'parent_value'         => $field['parent_value'] ?? null,
				// Prefix/suffix text support.
				'prefix'               => $field['prefix'] ?? null,
				'suffix'               => $field['suffix'] ?? null,
				// Min/max for number fields.
				'min'                  => $field['min'] ?? null,
				'max'                  => $field['max'] ?? null,
				// Invert value for "disable" toggles shown as "enable".
				'invert_value'         => $field['invert_value'] ?? false,
				// PRO notice badge data (for pro_only fields).
				'pro_notice'           => $field['pro_notice'] ?? null,
				// Notice type for notice fields (info, warning, error, success).
				'notice_type'          => $field['notice_type'] ?? null,
				// Inline controls embedded in description (replaces %s placeholders).
				'description_controls' => $field['description_controls'] ?? null,
				// Help text displayed below description in lighter style.
				'help_text'            => $field['help_text'] ?? null,
				// Disabled flag to prevent user interaction.
				'disabled'             => ! empty( $field['disabled'] ),
				// Group for visual grouping of related fields.
				// Supports string (key only) or array with 'key' and optional 'label'.
				// Normalized to array format: { key: string, label: string|null }.
				'group'                => $this->bb_normalize_field_group( $field['group'] ?? null ),
				// Confirmation message shown in a modal before toggling ON.
				'confirm_message'      => $field['confirm_message'] ?? null,
				// Optional overrides for confirm modal customization.
				'confirm_title'        => $field['confirm_title'] ?? null,
				'confirm_ok'           => $field['confirm_ok'] ?? null,
				'confirm_cancel'       => $field['confirm_cancel'] ?? null,
				'confirm_destructive'  => ! empty( $field['confirm_destructive'] ),
				// Allow adding new items (e.g., custom extensions).
				'allow_add'            => ! empty( $field['allow_add'] ),
				'add_button_label'     => $field['add_button_label'] ?? null,
				// Full extension data for extension list fields.
				'extension_data'       => $field['extension_data'] ?? null,
				// Icon options for extension icon dropdown.
				'icon_options'         => $field['icon_options'] ?? null,
				// Manage link fields.
				'manage_url'           => ! empty( $field['manage_url'] ) ? esc_url( $field['manage_url'] ) : null,
				'manage_label'         => $field['manage_label'] ?? null,
				'manage_icon'          => $field['manage_icon'] ?? null,
				// Input button fields (text input + action button, e.g. API key connect/disconnect).
				'placeholder'          => $field['placeholder'] ?? null,
				'button_label'         => $field['button_label'] ?? null,
				'is_connected'         => ! empty( $field['is_connected'] ),
				// Max length for text inputs.
				'maxlength'            => $field['maxlength'] ?? null,
				// Status check fields (AJAX-triggered server-side checks, e.g. Direct Access).
				'ajax_action'          => ! empty( $field['ajax_action'] ) ? sanitize_key( $field['ajax_action'] ) : null,
				'watch_field'          => $field['watch_field'] ?? null,
				// Layout: full-width fields render without the label column.
				'full_width'           => ! empty( $field['full_width'] ),
				// Group label for child fields (e.g., xProfile group names under Members).
				'child_group_label'    => $field['child_group_label'] ?? null,
				// When true, saving this field triggers a full feature refetch to update side panels.
				'refresh_panels'       => ! empty( $field['refresh_panels'] ),
			);

			// access_control: populate access-control data via filter so Pro can inject types/options.
			if ( 'access_control' === ( $field['type'] ?? '' ) ) {
				/**
				 * Filters access-control field data for the Settings 2.0 React UI.
				 *
				 * Pro populates this with type dropdowns, saved selections, and
				 * initial toggle options so the component renders immediately.
				 *
				 * @since BuddyBoss [BBVERSION]
				 *
				 * @param array  $ac_data    Default empty access-control data.
				 * @param string $field_name The field option name (e.g. 'bb-access-control-upload-media').
				 * @param string $feature_id Feature ID (e.g. 'media').
				 */
				$field_data['access_control_data'] = apply_filters( 'bb_access_control_field_data', array(), $field['name'], $feature_id );
			}

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

			// Inject upload_config and resolved upload_url for image_radio/image_upload fields with upload support.
			// Only whitelisted keys are sent to the frontend — url_getter stays server-side.
			$upload_types = array( 'image_radio', 'image_upload' );
			if ( in_array( ( $field_data['type'] ?? '' ), $upload_types, true ) && ! empty( $field['upload_config'] ) ) {
				$upload_config = $field['upload_config'];

				// Resolve the current upload URL using the registered getter function.
				// Strict allowlist — only functions explicitly known to return image URLs.
				$allowed_url_getters = array(
					'bb_get_default_custom_upload_group_avatar',
					'bb_get_default_custom_upload_group_cover',
				);

				/**
				 * Filters the allowed url_getter functions for image upload fields.
				 *
				 * @since BuddyBoss [BBVERSION]
				 *
				 * @param array $allowed_url_getters List of allowed function names.
				 */
				$allowed_url_getters = apply_filters( 'bb_admin_settings_allowed_url_getters', $allowed_url_getters );

				$url_getter = $upload_config['url_getter'] ?? '';
				$upload_url = '';
				if (
					! empty( $url_getter ) &&
					is_string( $url_getter ) &&
					in_array( $url_getter, $allowed_url_getters, true ) &&
					function_exists( $url_getter )
				) {
					$upload_url = call_user_func( $url_getter );
				}

				$field_data['upload_url'] = esc_url( $upload_url );

				// Strip server-only keys before passing to frontend.
				unset( $upload_config['url_getter'] );
				$field_data['upload_config'] = $upload_config;
			}

			/**
			 * Filters the field data before it is returned.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array  $field_data  Formatted field data.
			 * @param array  $field       Original field data.
			 * @param string $feature_id  Feature ID (e.g. 'activity', 'groups').
			 *
			 * @return array|void Formatted field data or void if no changes are needed.
			 */
			$field_data = apply_filters( 'bb_admin_settings_format_field_data', $field_data, $field, $feature_id );

			// Sanitize description and help_text for safe use with dangerouslySetInnerHTML (XSS prevention).
			if ( isset( $field_data['description'] ) && is_string( $field_data['description'] ) ) {
				$field_data['description'] = wp_kses_post( $field_data['description'] );
			}
			if ( isset( $field_data['help_text'] ) && is_string( $field_data['help_text'] ) ) {
				$field_data['help_text'] = wp_kses_post( $field_data['help_text'] );
			}

			// Attach notification_groups data for notification_types fields.
			// Built lazily here (not during registration) because bb_register_notification_preferences()
			// depends on component hooks that haven't fired yet at bb_register_features time.
			if ( 'notification_types' === ( $field['type'] ?? '' ) && function_exists( 'bb_register_notification_preferences' ) ) {
				$field_data['notification_groups'] = $this->bb_build_notification_groups();
			}

			// Attach topic data and nonces for topic_list fields.
			if ( 'topic_list' === ( $field['type'] ?? '' ) && function_exists( 'bb_topics_manager_instance' ) ) {
				$topics_manager = bb_topics_manager_instance();
				$topics_data    = array();
				if ( $topics_manager ) {
					$result      = $topics_manager->bb_get_topics(
						array(
							'item_id'   => 0,
							'item_type' => 'activity',
							'per_page'  => 200,
						)
					);
					$topics_data = ! empty( $result['topics'] ) ? $result['topics'] : array();
				}
				$field_data['topics_data']  = $topics_data;
				$field_data['nonces']       = array(
					'add'    => wp_create_nonce( 'bb_add_topic' ),
					'delete' => wp_create_nonce( 'bb_delete_topic' ),
					'order'  => wp_create_nonce( 'bb_update_topics_order' ),
				);
				$field_data['topics_limit'] = $topics_manager ? $topics_manager->bb_topics_limit() : 20;
			}

			$formatted[] = $field_data;
		}

		// Sort by order.
		usort( $formatted, 'bb_sort_by_order' );

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
		$raw_json   = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON decoded and per-field sanitized below.
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! is_string( $raw_json ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid settings format.', 'buddyboss' ) ) );
		}

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
		 * Note: This hook fires in both GET (read) and SAVE contexts. Use
		 * `bb_admin_settings_before_save_feature` for save-only logic.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $feature_id The feature being loaded.
		 */
		do_action( 'bb_admin_settings_before_get_feature', $feature_id );

		/**
		 * Fires before feature settings are saved, in the save AJAX handler only.
		 *
		 * Use this hook to capture pre-save state or perform any logic that
		 * must run exactly once during a save request, before option writes occur.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $feature_id The feature being saved.
		 * @param array  $settings   Full submitted settings (JSON decoded).
		 */
		do_action( 'bb_admin_settings_before_save_feature', $feature_id, $settings );

		$all_fields = $registry->bb_get_all_fields( $feature_id );

		if ( empty( $all_fields ) ) {
			wp_send_json_error( array( 'message' => __( 'No fields registered for this feature.', 'buddyboss' ) ) );
		}

		$saved = array();

		foreach ( $all_fields as $field_key => $field ) {
			$name = $field['name'];

			// Skip pro_only fields when Pro is not active — defense-in-depth
			// against crafted AJAX requests. The UI already disables these fields.
			if ( ! empty( $field['pro_only'] ) && ! function_exists( 'bb_platform_pro' ) ) {
				continue;
			}

			// Save the main field if it was submitted.
			if ( array_key_exists( $name, $settings ) ) {
				$value = $settings[ $name ];

				// Apply registered sanitize callback if present (fallback to type-based sanitization).
				if ( ! empty( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
					$value = call_user_func( $field['sanitize_callback'], $value );
				} elseif ( is_string( $value ) ) {
					$value = sanitize_text_field( $value );
				} elseif ( is_array( $value ) ) {
					$value = map_deep( $value, 'sanitize_text_field' );
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
			}

			// Handle description_controls: save each control's value alongside the main field.
			// Runs even when the parent field is not submitted (e.g. only the inline select changed).
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

		// Save section_toggle options (not registered as fields, but submitted from the section header toggle).
		$side_panels = $registry->bb_get_side_panels( $feature_id );
		foreach ( $side_panels as $sp_id => $sp ) {
			$sections = $registry->bb_get_sections( $feature_id, $sp_id );
			foreach ( $sections as $sec_id => $sec ) {
				if ( ! empty( $sec['section_toggle'] ) && array_key_exists( $sec['section_toggle'], $settings ) ) {
					$toggle_name  = $sec['section_toggle'];
					$toggle_value = absint( $settings[ $toggle_name ] );
					bp_update_option( $toggle_name, $toggle_value );
					$saved[ $toggle_name ] = $toggle_value;
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

		// Check if any saved field requires a panel refresh (e.g. Discussion Tags toggle).
		$refresh_panels = false;
		foreach ( $all_fields as $field ) {
			if ( ! empty( $field['refresh_panels'] ) && array_key_exists( $field['name'], $saved ) ) {
				$refresh_panels = true;
				break;
			}
		}

		$response_data = array(
			'message'        => __( 'Settings saved successfully.', 'buddyboss' ),
			'saved'          => $saved,
			'refresh_panels' => $refresh_panels,
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
			// Acquire a simple lock to prevent thundering herd on concurrent rebuilds.
			$lock_key = $cache_key . '_lock';
			if ( get_transient( $lock_key ) ) {
				// Another request is building the index — return empty results.
				// The index will be available on the next search request.
				return array();
			}
			set_transient( $lock_key, 1, 30 ); // 30-second lock.

			$index = $this->bb_build_search_index();
			set_transient( $cache_key, $index, HOUR_IN_SECONDS );

			delete_transient( $lock_key );
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

	/**
	 * Prime the WordPress object cache for multiple option names in a single query.
	 *
	 * This avoids N+1 get_option() queries by fetching all needed options at once
	 * and populating the object cache so subsequent get_option() calls are served from memory.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $option_names Array of option names to prime.
	 */
	private function bb_prime_option_caches( $option_names ) {
		// Use WP 6.4+ built-in batch priming if available.
		if ( function_exists( 'wp_prime_option_caches' ) ) {
			wp_prime_option_caches( $option_names );
			return;
		}

		// Fallback for WP < 6.4: manually prime the cache.
		// Check alloptions (autoloaded) and notoptions (known missing) before querying.
		$alloptions = wp_load_alloptions();
		$notoptions = wp_cache_get( 'notoptions', 'options' );
		if ( ! is_array( $notoptions ) ) {
			$notoptions = array();
		}

		$uncached = array();
		foreach ( $option_names as $name ) {
			if ( ! isset( $alloptions[ $name ] ) && ! isset( $notoptions[ $name ] ) && false === wp_cache_get( $name, 'options' ) ) {
				$uncached[] = $name;
			}
		}

		if ( empty( $uncached ) ) {
			return;
		}

		global $wpdb;

		$placeholders = implode( ',', array_fill( 0, count( $uncached ), '%s' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Placeholders are dynamically generated.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name IN ({$placeholders})",
				$uncached
			),
			OBJECT_K
		);

		// Prime cache for found options with raw (serialized) values.
		// WP core expects raw values in the options cache; get_option() handles unserializing.
		foreach ( $results as $option_name => $row ) {
			wp_cache_set( $option_name, $row->option_value, 'options' );
		}

		// Prime notoptions cache for missing options (matches WP 6.4+ behavior).
		// WP uses the 'notoptions' cache key to track options that don't exist in the DB.
		$update_notoptions = false;
		foreach ( $uncached as $option_name ) {
			if ( ! isset( $results[ $option_name ] ) && ! isset( $notoptions[ $option_name ] ) ) {
				$notoptions[ $option_name ] = true;
				$update_notoptions          = true;
			}
		}
		if ( $update_notoptions ) {
			wp_cache_set( 'notoptions', $notoptions, 'options' );
		}
	}

	/**
	 * Normalize the field group parameter to a consistent array format.
	 *
	 * Accepts either a string (group key only, backward compatible) or an
	 * array with 'key' and optional 'label'. Always returns null or an
	 * array with 'key' and 'label' keys.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string|array|null $group Raw group value from field registration.
	 *
	 * @return array|null Normalized group array or null.
	 */
	private function bb_normalize_field_group( $group ) {
		if ( empty( $group ) ) {
			return null;
		}

		// String format (backward compatible): treat as key only.
		if ( is_string( $group ) ) {
			return array(
				'key'   => $group,
				'label' => null,
			);
		}

		// Array format: ensure both keys exist.
		if ( is_array( $group ) && ! empty( $group['key'] ) ) {
			return array(
				'key'    => $group['key'],
				'label'  => $group['label'] ?? null,
				'inline' => ! empty( $group['inline'] ),
			);
		}

		return null;
	}

	/**
	 * Build notification groups data for the notification_types custom field.
	 *
	 * Called lazily at AJAX time because bb_register_notification_preferences()
	 * depends on component hooks that haven't fired yet during bb_register_features.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array Notification groups with fields, sub-types, and email template info.
	 */
	private function bb_build_notification_groups() {
		$all_notifications    = bb_register_notification_preferences();
		$enabled_notification = bp_get_option( 'bb_enabled_notification', array() );
		$notification_groups  = array();

		if ( empty( $all_notifications ) ) {
			return $notification_groups;
		}

		// Batch-collect all email template slugs across every field to avoid N+1 queries.
		$all_email_slugs = array();
		foreach ( $all_notifications as $field_group ) {
			if ( empty( $field_group['fields'] ) ) {
				continue;
			}
			foreach ( $field_group['fields'] as $notification_field ) {
				$slugs = bb_register_notification_email_templates( $notification_field['key'] );
				if ( ! empty( $slugs ) ) {
					$all_email_slugs = array_merge( $all_email_slugs, $slugs );
				}
			}
		}
		$all_email_slugs = array_unique( $all_email_slugs );

		// Single batched term query: slug → count mapping.
		$slug_term_counts = array();
		$slug_term_ids    = array();
		if ( ! empty( $all_email_slugs ) ) {
			$terms = get_terms(
				array(
					'taxonomy'   => bp_get_email_tax_type(),
					'slug'       => $all_email_slugs,
					'hide_empty' => false,
				)
			);

			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$slug_term_counts[ $term->slug ] = 1;
					$slug_term_ids[ $term->slug ]    = $term->term_id;
				}
			}
		}

		// Single batched post query: get all email posts for existing term slugs.
		$slug_post_map = array();
		if ( ! empty( $slug_term_ids ) ) {
			$email_posts = get_posts(
				array(
					'posts_per_page' => 200,
					'post_type'      => bp_get_email_post_type(),
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => bp_get_email_tax_type(),
							'field'    => 'slug',
							'terms'    => array_keys( $slug_term_ids ),
						),
					),
					'fields'         => 'ids',
				)
			);

			// Prime the term cache for all email posts to avoid N+1 queries.
			update_object_term_cache( $email_posts, bp_get_email_post_type() );

			// Map each post to its email type slug(s).
			foreach ( $email_posts as $post_id ) {
				$post_terms = wp_get_object_terms( $post_id, bp_get_email_tax_type(), array( 'fields' => 'slugs' ) );
				if ( ! is_wp_error( $post_terms ) ) {
					foreach ( $post_terms as $slug ) {
						if ( ! isset( $slug_post_map[ $slug ] ) ) {
							$slug_post_map[ $slug ] = array();
						}
						$slug_post_map[ $slug ][] = $post_id;
					}
				}
			}
		}

		foreach ( $all_notifications as $group_key => $field_group ) {
			$group_data = array(
				'key'         => $group_key,
				'admin_label' => isset( $field_group['admin_label'] ) ? $field_group['admin_label'] : '',
				'fields'      => array(),
			);

			if ( ! empty( $field_group['fields'] ) ) {
				foreach ( $field_group['fields'] as $notification_field ) {
					$checked = isset( $notification_field['default'] ) && 'yes' === $notification_field['default'];

					if ( array_key_exists( $notification_field['key'], $enabled_notification ) ) {
						$checked = isset( $enabled_notification[ $notification_field['key'] ]['main'] ) && 'yes' === $enabled_notification[ $notification_field['key'] ]['main'];
					}

					// Get email template info using pre-fetched batch data.
					$registered_emails = bb_register_notification_email_templates( $notification_field['key'] );
					$email_template    = array(
						'has_templates' => ! empty( $registered_emails ),
						'count'         => count( $registered_emails ),
					);

					if ( ! empty( $registered_emails ) ) {
						$total_email_count = 0;
						foreach ( $registered_emails as $email_type ) {
							if ( isset( $slug_term_counts[ $email_type ] ) ) {
								++$total_email_count;
							}
						}

						$email_template['existing_count'] = $total_email_count;
						$email_template['missing']        = count( $registered_emails ) > $total_email_count;

						if ( ! $email_template['missing'] ) {
							// Use pre-fetched post map for single-template edit links.
							if ( 1 === count( $registered_emails ) ) {
								$single_slug = current( $registered_emails );
								if ( ! empty( $slug_post_map[ $single_slug ] ) ) {
									$email_template['url'] = get_edit_post_link( $slug_post_map[ $single_slug ][0], 'raw' );
								}
							}

							if ( empty( $email_template['url'] ) ) {
								$email_template['url'] = add_query_arg(
									array(
										'post_type' => bp_get_email_post_type(),
										'taxonomy'  => bp_get_email_tax_type(),
										'terms'     => implode( ',', $registered_emails ),
									),
									admin_url( 'edit.php' )
								);
							}
						} else {
							$email_template['url'] = get_admin_url(
								bp_get_root_blog_id(),
								'edit.php?post_type=' . bp_get_email_post_type() . '&popup=yes'
							);
						}
					}

					// Get preference sub-types (email, web, app).
					$sub_types = array();
					if ( function_exists( 'bb_notification_preferences_types' ) ) {
						$options = bb_notification_preferences_types( $notification_field );
						if ( ! empty( $options ) ) {
							foreach ( $options as $key => $v ) {
								$parent_disabled = ! empty( $notification_field['notification_read_only'] ) && true === $notification_field['notification_read_only'];
								$is_disabled     = apply_filters( 'bb_is_' . $notification_field['key'] . '_' . $key . '_preference_enabled', ! $checked );
								$is_render       = apply_filters( 'bb_is_' . $notification_field['key'] . '_' . $key . '_preference_type_render', $v['is_render'], $notification_field['key'], $key );

								$sub_types[ $key ] = array(
									'label'      => $v['label'],
									'is_checked' => $v['is_checked'],
									'is_render'  => $is_render,
									'disabled'   => $is_disabled && $parent_disabled,
								);
							}
						}
					}

					$group_data['fields'][] = array(
						'key'            => $notification_field['key'],
						'label'          => ! empty( $notification_field['admin_label'] ) ? $notification_field['admin_label'] : $notification_field['label'],
						'checked'        => $checked,
						'read_only'      => ! empty( $notification_field['notification_read_only'] ),
						'tooltip'        => ! empty( $notification_field['notification_tooltip_text'] ) ? $notification_field['notification_tooltip_text'] : '',
						'email_template' => $email_template,
						'sub_types'      => $sub_types,
					);
				}
			}

			$notification_groups[] = $group_data;
		}

		return $notification_groups;
	}

	/**
	 * Extract is_active toggle values from extension data arrays.
	 *
	 * Normalizes nested extension arrays (which contain is_active, extension,
	 * mime_type, etc.) into a flat key => 0|1 mapping for the React toggle list.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $field_value The extension data array.
	 *
	 * @return array Flattened toggle values keyed by extension ID.
	 */
	private function bb_extract_extension_toggle_values( $field_value ) {
		$toggle_values = array();
		foreach ( $field_value as $key => $ext ) {
			if ( is_array( $ext ) && isset( $ext['is_active'] ) ) {
				$toggle_values[ $key ] = absint( $ext['is_active'] );
			} else {
				$toggle_values[ $key ] = absint( $ext );
			}
		}
		return $toggle_values;
	}
}

// Initialize.
new BB_Admin_Settings_Ajax();
