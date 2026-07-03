<?php
/**
 * BuddyBoss Admin Settings AJAX Handler
 *
 * Handles AJAX requests for the new admin settings interface.
 *
 * @since   BuddyBoss 3.0.0
 * @package BuddyBoss\Core\Administration
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
	 * @since BuddyBoss 3.0.0
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'bb_admin_settings';

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function __construct() {
		$this->bb_register_ajax_handlers();
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	private function bb_register_ajax_handlers() {
		// Features.
		add_action( 'wp_ajax_bb_admin_get_features', array( $this, 'bb_admin_get_features' ) );
		add_action( 'wp_ajax_bb_admin_toggle_feature', array( $this, 'bb_admin_toggle_feature' ) );
		add_action( 'wp_ajax_bb_admin_get_feature_settings', array( $this, 'bb_admin_get_feature_settings' ) );
		add_action( 'wp_ajax_bb_admin_save_feature_settings', array( $this, 'bb_admin_save_feature_settings' ) );

		add_action( 'wp_ajax_bb_admin_search_settings', array( $this, 'bb_admin_search_settings' ) );
		add_action( 'wp_ajax_bb_admin_search_published_pages', array( $this, 'bb_admin_search_published_pages' ) );
		add_action( 'wp_ajax_bb_admin_search_pages_list', array( $this, 'bb_admin_search_pages_list' ) );
		add_action( 'wp_ajax_bb_admin_create_directory_page', array( $this, 'bb_admin_create_directory_page' ) );

		// Generic platform-setting read/write used by custom panel screens
		// (Profile Types, Profile Search, Group Types, etc.). Registered here
		// — not in BB_Admin_Groups_Ajax — because the consumers span features
		// beyond Groups and BB_Admin_Settings_Ajax is always loaded.
		add_action( 'wp_ajax_bb_admin_get_platform_settings', array( $this, 'bb_admin_get_platform_settings' ) );
		add_action( 'wp_ajax_bb_admin_save_platform_setting', array( $this, 'bb_admin_save_platform_setting' ) );

		// Support Access (Help tab) AJAX handlers live in BB_Support_Access,
		// which registers its own wp_ajax_* hooks. See class-bb-support-access.php.

		add_action( 'bb_admin_save_feature_settings_after', array( $this, 'bb_invalidate_search_index_after_save' ), 10, 3 );
	}

	/**
	 * Get platform settings (WordPress options) by allowlisted names.
	 *
	 * Generic helper for custom Settings 2.0 panels that persist a small set
	 * of WordPress options outside the standard feature-settings pipeline
	 * (Profile Types, Profile Search, Group Types). Each consumer must add
	 * its option keys (and a sanitize callback) to the allowlist via the
	 * `bb_admin_allowed_platform_settings` filter or by editing
	 * bb_admin_get_allowed_platform_options() below.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function bb_admin_get_platform_settings() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$options = isset( $_POST['options'] ) ? sanitize_text_field( wp_unslash( $_POST['options'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $options ) ) {
			wp_send_json_error( array( 'message' => __( 'Options parameter is required.', 'buddyboss-platform' ) ) );
		}

		$requested = array_map( 'sanitize_text_field', explode( ',', $options ) );
		$allowed   = $this->bb_admin_get_allowed_platform_options();
		$settings  = array();

		foreach ( $requested as $option_name ) {
			if ( array_key_exists( $option_name, $allowed ) ) {
				$settings[ $option_name ] = bp_get_option( $option_name, '' );
			}
		}

		wp_send_json_success( $settings );
	}

	/**
	 * Save a single platform setting (WordPress option).
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function bb_admin_save_platform_setting() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$option_name = isset( $_POST['option_name'] ) ? sanitize_text_field( wp_unslash( $_POST['option_name'] ) ) : '';
		$raw_value   = isset( $_POST['option_value'] ) ? wp_unslash( $_POST['option_value'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below per-option.
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $option_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Option name is required.', 'buddyboss-platform' ) ) );
		}

		$allowed = $this->bb_admin_get_allowed_platform_options();

		if ( ! array_key_exists( $option_name, $allowed ) ) {
			wp_send_json_error( array( 'message' => __( 'Option not allowed.', 'buddyboss-platform' ) ) );
		}

		// Apply per-option sanitize callback (defined in bb_admin_get_allowed_platform_options).
		$sanitize_fn  = $allowed[ $option_name ];
		$option_value = is_callable( $sanitize_fn ) ? call_user_func( $sanitize_fn, $raw_value ) : sanitize_text_field( $raw_value );

		bp_update_option( $option_name, $option_value );

		// Component-specific cache invalidation. Only fires when the relevant
		// component is active so the handler stays usable when, say, Groups
		// is deactivated but a Members option is being saved.
		if ( 'bp-disable-group-type-creation' === $option_name && bp_is_active( 'groups' ) ) {
			wp_cache_delete( 'bp_group_types', 'bp_groups' );
			wp_cache_delete( 'bb-group-type-label-css', 'bp_groups_group_type' );
		}

		wp_send_json_success(
			array( 'message' => __( 'Setting saved successfully.', 'buddyboss-platform' ) )
		);
	}

	/**
	 * Get allowed platform settings options map.
	 *
	 * Returns an associative array of option_name => sanitize_callback. The
	 * default list covers the options consumed by Settings 2.0 custom panel
	 * screens; third parties can extend via the
	 * `bb_admin_allowed_platform_settings` filter.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array
	 */
	private function bb_admin_get_allowed_platform_options() {
		/**
		 * Filters the allowed platform settings options for AJAX read/write.
		 *
		 * Keys are option names; values are the sanitize callback to use when saving.
		 * Use 'absint' for toggle/integer options, 'sanitize_text_field' for strings,
		 * 'sanitize_key' for slug-shaped values, etc.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param array $allowed_options Associative array of option_name => sanitize_callback.
		 */
		$options = apply_filters(
			'bb_admin_allowed_platform_settings',
			array(
				// Group Types panel.
				'bp-disable-group-type-creation'         => 'absint',
				'bp-enable-group-auto-join'              => 'absint',
				// Profile Types panel.
				'bp-member-type-enable-disable'          => 'absint',
				'bp-member-type-display-on-profile'      => 'absint',
				'bp-member-type-default-on-registration' => 'sanitize_key',
				// Profile Search panel.
				'bp-enable-profile-search'               => 'absint',
			)
		);

		// Strip any dangerous WordPress core options that a careless extension might add.
		return bb_filter_allowed_options( $options );
	}

	/**
	 * Get features.
	 *
	 * @since BuddyBoss 3.0.0
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
				// Skip hidden features (e.g. Emails — has settings page but no grid card).
				if ( ! empty( $feature['hidden'] ) ) {
					continue;
				}

				// Determine active status.
				// Priority: 1) is_active_callback (for features with custom logic),
				// 2) bb-active-features, 3) bp-active-components (migration fallback).
				if ( ! is_null( $feature['is_active_callback'] ) ) {
					$is_active = (bool) call_user_func( $feature['is_active_callback'] );
				} elseif ( isset( $active_features[ $feature_id ] ) ) {
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

				$settings_route = $this->bb_resolve_settings_route( $feature_id, $feature );

				$formatted = array(
					'id'             => $feature_id,
					'label'          => $feature['label'] ?? $feature_id,
					'description'    => $feature['description'] ?? '',
					'category'       => $feature['category'] ?? 'community',
					'license_tier'   => $feature['license_tier'] ?? 'free',
					'status'         => $is_active ? 'active' : 'inactive',
					'available'      => $is_available,
					'required'       => ! empty( $feature['required'] ),
					'settings_route' => $settings_route,
					// Carry the registration-time order into the response so
					// the placeholder merger (bb_admin_sort_features_response)
					// can interleave real cards with catalog placeholders by
					// a single comparable key. Default matches bb_register_feature().
					'order'          => isset( $feature['order'] ) ? (int) $feature['order'] : 100,
				);

				// Format icon like REST API.
				if ( ! empty( $feature['icon'] ) ) {
					$formatted['icon'] = $icon_registry->bb_get_icon_for_rest( $feature['icon'] );
				}

				// Optional confirmation modal payload for "turn the feature OFF"
				// — mirrors the field-level confirm_* convention used by
				// ConfirmToggleModal but at the feature card level. SettingsScreen
				// reads these to intercept the toggle handler when the admin
				// tries to disable the feature. All keys are passthrough; absent
				// keys mean "no confirmation required" (preserving the original
				// instant-toggle behavior for every feature that hasn't opted in).
				$this->bb_format_confirm_off_payload( $feature, $formatted );

				$features[] = $formatted;
			}
		}

		/**
		 * Filters the features array before sending the AJAX response.
		 *
		 * Allows placeholder features and third-party extensions to append
		 * feature objects to the Settings 2.0 feature list.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param array $features Array of formatted feature objects.
		 */
		wp_send_json_success( apply_filters( 'bb_admin_features_response', $features ) );
	}

	/**
	 * Toggle a feature (activate or deactivate).
	 *
	 * Expects POST parameters:
	 * - feature_id: The feature ID.
	 * - status: Desired status ('active' or 'inactive').
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function bb_admin_toggle_feature() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$feature_id = isset( $_POST['feature_id'] ) ? sanitize_text_field( wp_unslash( $_POST['feature_id'] ) ) : '';
		$status     = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $feature_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature ID is required.', 'buddyboss-platform' ) ) );
		}

		if ( ! in_array( $status, array( 'active', 'inactive' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid status. Must be "active" or "inactive".', 'buddyboss-platform' ) ) );
		}

		if ( ! function_exists( 'bb_feature_registry' ) || ! function_exists( 'bb_icon_registry' ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature registry not available.', 'buddyboss-platform' ) ) );
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
				__( 'Feature "%1$s" %2$s successfully.', 'buddyboss-platform' ),
				$feature['label'],
				$activate ? __( 'activated', 'buddyboss-platform' ) : __( 'deactivated', 'buddyboss-platform' )
			),
		);

		// Dependents are resolved in O(1) via the registry's reverse_deps index
		// (populated at registration time) — no per-request scan of every feature.
		$dependents = $registry->bb_get_reverse_deps( $feature_id );

		if ( ! empty( $dependents ) ) {
			if ( ! $activate ) {
				// Dependents are now unavailable — React greys out their cards.
				$response['deactivated_dependents'] = $dependents;
			} else {
				// Dependents become available again alongside this feature.
				$response['reactivatable_dependents'] = $dependents;
			}
		}

		wp_send_json_success( $response );
	}

	/**
	 * Verify AJAX request.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return bool|void
	 */
	private function bb_verify_request() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );
	}

	/**
	 * Get feature settings.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function bb_admin_get_feature_settings() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$feature_id = isset( $_POST['feature_id'] ) ? sanitize_text_field( wp_unslash( $_POST['feature_id'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $feature_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature ID is required.', 'buddyboss-platform' ) ) );
		}

		if ( ! function_exists( 'bb_feature_registry' ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature registry not available.', 'buddyboss-platform' ) ) );
		}

		$registry = bb_feature_registry();

		// Get feature.
		$feature = $registry->bb_get_feature( $feature_id );
		if ( ! $feature ) {
			wp_send_json_error( array( 'message' => __( 'Feature not found.', 'buddyboss-platform' ) ) );
		}

		/**
		 * Fires before feature settings are retrieved for the AJAX response.
		 *
		 * Allows late registration of fields that depend on data not available
		 * at the early `bb_register_features` hook (e.g., custom post types
		 * from third-party plugins that register on `init`).
		 *
		 * @since BuddyBoss 3.0.0
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
					'fields'      => $this->bb_format_fields_for_response( $section_fields, $settings, $feature_id, $side_panel_id, $section_id ),
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

					// Opt-in: when the section toggle is OFF, hide fields entirely instead
					// of dimming them. Only meaningful when section_toggle is also set.
					if ( ! empty( $section['hide_fields_when_off'] ) ) {
						$formatted_section['hide_fields_when_off'] = true;
					}
				}

				// Include status if set (e.g. Connected/Not Connected badges).
				if ( ! empty( $section['status'] ) && is_array( $section['status'] ) ) {
					$formatted_section['status'] = array(
						'type' => sanitize_key( $section['status']['type'] ?? 'info' ),
						'text' => sanitize_text_field( $section['status']['text'] ?? '' ),
					);
				}

				// Include pro_notice if set (e.g. UPGRADE PRO badge in section header).
				// Section-level badges intentionally do NOT trigger the field-upgrades
				// modal — only field-level pro badges open UpgradeModal in-page.
				// Section badges keep their original behavior: open `link_url` in a new
				// tab when set, otherwise render as a static label.
				if ( ! empty( $section['pro_notice'] ) && is_array( $section['pro_notice'] ) ) {
					$formatted_section['pro_notice'] = array(
						'show'       => ! empty( $section['pro_notice']['show'] ),
						'badge_text' => sanitize_text_field( $section['pro_notice']['badge_text'] ?? __( 'UPGRADE PRO', 'buddyboss-platform' ) ),
						'badge_icon' => sanitize_text_field( $section['pro_notice']['badge_icon'] ?? 'bb-icons-rl-crown-simple' ),
						'link_url'   => esc_url_raw( $section['pro_notice']['link_url'] ?? '' ),
					);
				}

				// Section-level help URL (renders a (?) icon in the section header).
				// May be either a full URL or a bare KB article ID like "636101".
				if ( ! empty( $section['help_url'] ) ) {
					$formatted_section['help_url'] = $this->bb_sanitize_help_url( $section['help_url'] );
				}

				$formatted_sections[] = $formatted_section;
			}

			// Sort sections by order.
			usort( $formatted_sections, 'bb_sort_by_order' );

			$formatted_side_panels[] = array(
				'id'           => $side_panel_id,
				'title'        => $side_panel['title'],
				'icon'         => $side_panel['icon'] ?? null,
				// Same dual-format handling as section help_url — may be a full
				// URL or a bare KB article ID. See bb_sanitize_help_url().
				'help_url'     => ! empty( $side_panel['help_url'] ) ? $this->bb_sanitize_help_url( $side_panel['help_url'] ) : '',
				'order'        => $side_panel['order'] ?? 100,
				'is_default'   => $side_panel['is_default'] ?? false,
				'divider'      => ! empty( $side_panel['divider'] ),
				// Optional conditional visibility based on a field value (Phase 5).
				// Shape: array( 'field' => 'fieldname', 'value' => mixed, 'operator' => '==' (default) | '!=' ).
				'conditional'  => ! empty( $side_panel['conditional'] ) && is_array( $side_panel['conditional'] ) ? $side_panel['conditional'] : null,
				// Link-out panels point at another feature's settings (e.g.
				// the Offload Media entry inside Media). `SideNavigation.js`
				// renders these as an `<a>` with a trailing up-right arrow
				// instead of the normal internal-nav button. Scrubbed through
				// `esc_url_raw` so a stored absolute URL can never smuggle a
				// non-allowlisted scheme into the rendered `href`.
				'external_url' => ! empty( $side_panel['external_url'] ) ? esc_url_raw( $side_panel['external_url'] ) : '',
				'sections'     => $formatted_sections,
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
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string              $feature_id    Feature ID.
	 * @param array               $feature       Feature data.
	 * @param BB_Feature_Registry $registry      Feature registry instance.
	 * @param BB_Icon_Registry    $icon_registry Icon registry instance.
	 * @return array Formatted feature data.
	 */
	private function bb_format_feature_for_response( $feature_id, $feature, $registry, $icon_registry ) {
		$settings_route = $this->bb_resolve_settings_route( $feature_id, $feature );

		$formatted = array(
			'id'             => $feature_id,
			'label'          => $feature['label'] ?? $feature_id,
			'description'    => $feature['description'] ?? '',
			'category'       => $feature['category'] ?? 'community',
			'license_tier'   => $feature['license_tier'] ?? 'free',
			'status'         => $registry->bb_is_feature_active( $feature_id ) ? 'active' : 'inactive',
			'available'      => $registry->bb_is_feature_available( $feature_id ),
			'required'       => ! empty( $feature['required'] ),
			'settings_route' => $settings_route,
		);

		// Format icon.
		if ( ! empty( $feature['icon'] ) ) {
			$formatted['icon'] = $icon_registry->bb_get_icon_for_rest( $feature['icon'] );
		}

		// Forward any opt-in confirm-on-disable payload to the React layer.
		// See SettingsScreen.js handleFeatureToggle for the consumer side.
		$this->bb_format_confirm_off_payload( $feature, $formatted );

		return $formatted;
	}

	/**
	 * Append the optional confirm-on-disable payload to a formatted feature
	 * response so the React feature card can show a confirmation modal
	 * before turning the feature off.
	 *
	 * Mirrors the field-level confirm_* convention used by
	 * `ConfirmToggleModal` (see SettingsForm.js), but uses a `confirm_off_*`
	 * prefix at the feature level so:
	 *   1. the feature card stays independent of any per-field confirms,
	 *   2. a future feature could add a separate `confirm_on_*` flow
	 *      without colliding with the disable confirmation, and
	 *   3. features that don't opt in (the default) keep their original
	 *      instant-toggle behavior — only `confirm_off_message` is required
	 *      to enable the modal; the rest fall back to sensible defaults
	 *      on the React side.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $feature   Raw feature registration data.
	 * @param array $formatted Formatted response array (modified in place).
	 *
	 * @return void
	 */
	private function bb_format_confirm_off_payload( $feature, &$formatted ) {
		if ( empty( $feature['confirm_off_message'] ) ) {
			return;
		}

		$formatted['confirm_off_message'] = wp_kses_post( $feature['confirm_off_message'] );

		// Opt-in flag: when true, ConfirmToggleModal renders the message as
		// DOMPurify-sanitised HTML instead of plain text. Defaults to false
		// so single-line callers stay text-only.
		if ( ! empty( $feature['confirm_off_message_is_html'] ) ) {
			$formatted['confirm_off_message_is_html'] = true;
		}

		if ( ! empty( $feature['confirm_off_title'] ) ) {
			$formatted['confirm_off_title'] = sanitize_text_field( $feature['confirm_off_title'] );
		}
		if ( ! empty( $feature['confirm_off_ok'] ) ) {
			$formatted['confirm_off_ok'] = sanitize_text_field( $feature['confirm_off_ok'] );
		}
		if ( ! empty( $feature['confirm_off_cancel'] ) ) {
			$formatted['confirm_off_cancel'] = sanitize_text_field( $feature['confirm_off_cancel'] );
		}
		if ( isset( $feature['confirm_off_destructive'] ) ) {
			$formatted['confirm_off_destructive'] = (bool) $feature['confirm_off_destructive'];
		}
	}

	/**
	 * Format fields for response.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array  $fields     Fields data.
	 * @param array  $values     Current field values.
	 * @param string $feature_id Feature ID (used for pro_notice computation).
	 * @param string $panel_id   Side panel ID (used for field-upgrades catalog lookup).
	 * @param string $section_id Section ID (used for field-upgrades catalog lookup).
	 *
	 * @return array Formatted fields data.
	 */
	private function bb_format_fields_for_response( $fields, $values = array(), $feature_id = '', $panel_id = '', $section_id = '' ) {
		$formatted = array();

		foreach ( $fields as $field_name => $field ) {
			// Get stored value or default.
			$field_value = isset( $values[ $field['name'] ] ) ? $values[ $field['name'] ] : ( $field['default'] ?? '' );

			/*
			 * Handle checkbox_list (stored as associative array { "key": "1", "key2": "0" }).
			 * @since BuddyBoss 3.0.0
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
			 * @since BuddyBoss 3.0.0
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
			// Guard against corrupted option (string instead of array) — PHP 8+ throws TypeError on array_map.
			if ( 'toggle_list' === ( $field['type'] ?? '' ) && ! is_array( $field_value ) ) {
				$field_value = array();
			}
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
				'name'                      => $field['name'],
				'label'                     => $field['label'],
				'type'                      => $field['type'] ?? 'text',
				'description'               => $field['description'] ?? '',
				'default'                   => $field['default'] ?? '',
				'options'                   => $field['options'] ?? array(),
				'conditional'               => $field['conditional'] ?? null,
				'pro_only'                  => $field['pro_only'] ?? false,
				'license_tier'              => $field['license_tier'] ?? 'free',
				'order'                     => $field['order'] ?? 100,
				'value'                     => $field_value,
				// Nested field support.
				'parent_field'              => $field['parent_field'] ?? null,
				'parent_value'              => $field['parent_value'] ?? null,
				// Prefix/suffix text support.
				'prefix'                    => $field['prefix'] ?? null,
				'suffix'                    => $field['suffix'] ?? null,
				// Min/max for number fields.
				'min'                       => $field['min'] ?? null,
				'max'                       => $field['max'] ?? null,
				// Invert value for "disable" toggles shown as "enable".
				'invert_value'              => $field['invert_value'] ?? false,
				// PRO notice badge data (for pro_only fields).
				'pro_notice'                => $field['pro_notice'] ?? null,
				// Notice type for notice fields (info, warning, error, success).
				'notice_type'               => $field['notice_type'] ?? null,
				// Inline controls embedded in description (replaces %s placeholders).
				'description_controls'      => $field['description_controls'] ?? null,
				// Help text displayed below description in lighter style.
				'help_text'                 => $field['help_text'] ?? null,
				// Disabled flag to prevent user interaction.
				'disabled'                  => ! empty( $field['disabled'] ),
				// Group for visual grouping of related fields.
				// Supports string (key only) or array with 'key' and optional 'label'.
				// Normalized to array format: { key: string, label: string|null }.
				'group'                     => $this->bb_normalize_field_group( $field['group'] ?? null ),
				// Confirmation message shown in a modal before toggling ON.
				'confirm_message'           => $field['confirm_message'] ?? null,
				// Optional overrides for confirm modal customization.
				'confirm_title'             => $field['confirm_title'] ?? null,
				'confirm_ok'                => $field['confirm_ok'] ?? null,
				'confirm_cancel'            => $field['confirm_cancel'] ?? null,
				'confirm_destructive'       => ! empty( $field['confirm_destructive'] ),
				// Allow adding new items (e.g., custom extensions).
				'allow_add'                 => ! empty( $field['allow_add'] ),
				'add_button_label'          => $field['add_button_label'] ?? null,
				// Full extension data for extension list fields.
				'extension_data'            => $field['extension_data'] ?? null,
				// Icon options for extension icon dropdown.
				'icon_options'              => $field['icon_options'] ?? null,
				// Manage link fields.
				'manage_url'                => ! empty( $field['manage_url'] ) ? esc_url_raw( $field['manage_url'] ) : null,
				'manage_label'              => $field['manage_label'] ?? null,
				'manage_icon'               => $field['manage_icon'] ?? null,
				// Input button fields (text input + action button, e.g. API key connect/disconnect).
				'placeholder'               => $field['placeholder'] ?? null,
				'button_label'              => $field['button_label'] ?? null,
				'button_only'               => ! empty( $field['button_only'] ),
				// Icon-only input button variant: renders the button as an icon control (no text).
				// 'icon' is an icon font class (e.g. "bb-icons-rl bb-icons-rl-arrow-clockwise").
				// 'icon_label' is used for aria-label / title on icon-only buttons.
				'icon_only'                 => ! empty( $field['icon_only'] ),
				'icon'                      => ! empty( $field['icon'] ) ? sanitize_text_field( $field['icon'] ) : null,
				'icon_label'                => ! empty( $field['icon_label'] ) ? sanitize_text_field( $field['icon_label'] ) : null,
				'button_url'                => ! empty( $field['button_url'] ) ? esc_url_raw( $field['button_url'] ) : null,
				'button_target'             => $field['button_target'] ?? null,
				// Empty state fields (centered card with icon + title + description + button).
				'empty_state_title'         => $field['empty_state_title'] ?? null,
				'empty_state_description'   => $field['empty_state_description'] ?? null,
				'related_fields'            => ! empty( $field['related_fields'] ) && is_array( $field['related_fields'] ) ? array_map( 'sanitize_key', $field['related_fields'] ) : null,
				// Per-option descriptions for select fields (description swaps on value change).
				// map_deep handles nested structures safely; each leaf string is kses-filtered.
				'option_descriptions'       => ! empty( $field['option_descriptions'] ) && is_array( $field['option_descriptions'] )
					? map_deep( $field['option_descriptions'], 'wp_kses_post' )
					: null,
				'is_connected'              => ! empty( $field['is_connected'] ),
				// Verify field configuration (modal title, icons, messages).
				// map_deep so nested config structures (e.g. button arrays) do not trip sanitize_text_field.
				'verify_config'             => ! empty( $field['verify_config'] ) && is_array( $field['verify_config'] )
					? map_deep( $field['verify_config'], 'sanitize_text_field' )
					: null,
				// Max length for text inputs.
				'maxlength'                 => $field['maxlength'] ?? null,
				// Status check fields (AJAX-triggered server-side checks, e.g. Direct Access).
				'ajax_action'               => ! empty( $field['ajax_action'] ) ? sanitize_key( $field['ajax_action'] ) : null,
				// Async select fields (searchable server-side select with AJAX loading).
				'async_action'              => ! empty( $field['async_action'] ) ? sanitize_key( $field['async_action'] ) : null,
				'watch_field'               => $field['watch_field'] ?? null,
				// Fetch fresh data via AJAX when specified fields change (e.g. refresh select options after credentials entered).
				'fetch_on_change'           => ! empty( $field['fetch_on_change'] ) && is_array( $field['fetch_on_change'] )
					? array(
						'fields'         => ! empty( $field['fetch_on_change']['fields'] ) ? array_map( 'sanitize_key', $field['fetch_on_change']['fields'] ) : array(),
						'require_all'    => ! empty( $field['fetch_on_change']['require_all'] ),
						'ajax_action'    => ! empty( $field['fetch_on_change']['ajax_action'] ) ? sanitize_key( $field['fetch_on_change']['ajax_action'] ) : '',
						'debounce'       => ! empty( $field['fetch_on_change']['debounce'] ) ? absint( $field['fetch_on_change']['debounce'] ) : 500,
						'loading_text'   => ! empty( $field['fetch_on_change']['loading_text'] ) ? sanitize_text_field( $field['fetch_on_change']['loading_text'] ) : '',
						'disable_fields' => ! empty( $field['fetch_on_change']['disable_fields'] ) ? array_map( 'sanitize_key', $field['fetch_on_change']['disable_fields'] ) : array(),
					)
					: null,
				// Layout: full-width fields render without the label column.
				'full_width'                => ! empty( $field['full_width'] ),
				// Custom CSS class(es) appended to the field wrapper div (space-separated).
				'field_class'               => ! empty( $field['field_class'] ) ? implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $field['field_class'] ) ) ) : null,
				// Group label for child fields (e.g., xProfile group names under Members).
				'child_group_label'         => $field['child_group_label'] ?? null,
				// When true, saving this field triggers a full feature refetch to update side panels.
				'refresh_panels'            => ! empty( $field['refresh_panels'] ),
				// SSO providers array (for sso_providers field type).
				'providers'                 => ! empty( $field['providers'] ) && is_array( $field['providers'] ) ? $field['providers'] : null,
				// Generic media-picker config (for media_picker field type — library_type, multiple, frame_title, etc.).
				'media_picker_config'       => ! empty( $field['media_picker_config'] ) && is_array( $field['media_picker_config'] ) ? $field['media_picker_config'] : null,
				// Predefined items for sortable_toggle_list (e.g. side menu items, footer menu items).
				'available_items'           => ! empty( $field['available_items'] ) && is_array( $field['available_items'] ) ? array_values( $field['available_items'] ) : null,
				// Generic editable-link-list config (add_label, modal_title_add, modal_title_edit).
				'editable_link_list_config' => ! empty( $field['editable_link_list_config'] ) && is_array( $field['editable_link_list_config'] ) ? $field['editable_link_list_config'] : null,
				// Optional secondary description rendered under the field label (Figma "left-column help").
				'label_description'         => isset( $field['label_description'] ) ? wp_kses_post( $field['label_description'] ) : null,
				// SEO/Social preview-card config (site_name, site_url, site_icon,
				// title_key, description_key, etc.). URL-shaped keys go through
				// esc_url_raw; other string keys get sanitize_text_field. Applying
				// sanitize_text_field to URLs would strip the protocol colon on
				// some WP sanitize versions.
				'preview_config'            => ! empty( $field['preview_config'] ) && is_array( $field['preview_config'] )
					? $this->bb_sanitize_preview_config( $field['preview_config'] )
					: null,
				// Available-tag reference list consumed by the `tags_reference` display-only field type.
				// Each row is { tag: string, description: string }. The tag is a plain
				// slug/token — wp_kses_post on it adds no value and could accept partial HTML;
				// sanitize_text_field is the right tool. The description is authored copy and
				// may legitimately contain `<code>` / `<strong>` etc., so it gets wp_kses_post.
				'tags'                      => ! empty( $field['tags'] ) && is_array( $field['tags'] )
					? array_map(
						static function ( $row ) {
							if ( ! is_array( $row ) ) {
								return $row;
							}
							$out = $row;
							if ( isset( $row['tag'] ) && is_string( $row['tag'] ) ) {
								$out['tag'] = sanitize_text_field( $row['tag'] );
							}
							if ( isset( $row['description'] ) && is_string( $row['description'] ) ) {
								$out['description'] = wp_kses_post( $row['description'] );
							}
							return $out;
						},
						$field['tags']
					)
					: null,
			);

			// access_control: populate access-control data via filter so Pro can inject types/options.
			if ( 'access_control' === ( $field['type'] ?? '' ) ) {
				/**
				 * Filters access-control field data for the Settings 2.0 React UI.
				 *
				 * Pro populates this with type dropdowns, saved selections, and
				 * initial toggle options so the component renders immediately.
				 *
				 * @since BuddyBoss 3.0.0
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
				$pro_notice               = bb_admin_settings_get_pro_notice( array( 'type' => $feature_id ) );
				$field_data['pro_notice'] = ! empty( $pro_notice['show'] ) ? $pro_notice : null;
			}

			// Force-OFF visual for gated pro_only fields. When pro_only is true AND
			// the gate is currently active (pro_notice.show === true — Pro inactive,
			// license invalid, Sharing not installed, etc.), override the rendered
			// value so the field appears in its default/off state regardless of what
			// is stored in the DB. The stored option is NEVER touched here — when
			// the gate lifts, the real value is read again on the next AJAX response.
			//
			// Visual treatment:
			//   - toggle:                       rendered as 0 (slider OFF)
			//   - select / radio / image_radio: rendered as the FIRST option's
			//                                   `value`. We deliberately ignore the
			//                                   registered `default` arg here because
			//                                   most callers populate it via a
			//                                   DB-reading helper (e.g.
			//                                   bb_get_member_directory_primary_action()
			//                                   or bb_get_group_cover_image_width()),
			//                                   which returns the user's previously-
			//                                   saved value rather than a true reset.
			//                                   First option = predictable "default
			//                                   option" UX for the gated state.
			//   - everything else:              rendered as the field's `default` arg
			//
			// Without this override an admin who had Pro previously enabled and is
			// now on a gated panel would see the "ON" toggle next to a "PRO" badge,
			// or a select dropdown showing their previously-chosen option — which
			// misleads since the feature is not actually functional.
			if (
				! empty( $field_data['pro_only'] ) &&
				! empty( $field_data['pro_notice']['show'] )
			) {
				$gated_type = $field_data['type'] ?? '';
				if ( 'toggle' === $gated_type ) {
					$field_data['value'] = 0;
				} elseif (
					in_array( $gated_type, array( 'select', 'radio', 'image_radio' ), true )
					&& ! empty( $field_data['options'] )
				) {
					$first_option        = reset( $field_data['options'] );
					$field_data['value'] = is_array( $first_option ) && isset( $first_option['value'] )
						? $first_option['value']
						: ( $field_data['default'] ?? '' );
				} else {
					$field_data['value'] = $field_data['default'] ?? '';
				}
			}

			// Enrich pro_notice with modal payload from the field-upgrades catalog.
			// Looked up most-specific-first: exact (feature, panel, section, field)
			// beats a section wildcard, which beats a panel wildcard, etc.
			//
			// Behavior on the React side:
			//   - catalog match     → play button opens UpgradeModal in-page
			//   - no catalog match  → play button opens pricing URL in a new tab
			// To make the no-match path consistent, we force `link_url` to the
			// generic pricing page whenever a `pro_only` field falls through
			// without a catalog hit. This replaces the older per-feature docs
			// URLs (e.g. reactions docs) for fields that don't yet have catalog
			// copy — marketing wants one consistent fallback across the admin.
			if (
				! empty( $field_data['pro_notice']['show'] ) &&
				function_exists( 'bb_get_field_upgrade_for' )
			) {
				$entry = bb_get_field_upgrade_for( $feature_id, $panel_id, $section_id, $field['name'] );
				if ( $entry ) {
					$field_data['pro_notice']['modal'] = bb_field_upgrade_to_modal_payload( $entry, $field['label'] ?? '' );
				} else {
					// No catalog entry — point the play button at the pricing page so
					// every pro_only field has a consistent upsell destination.
					$field_data['pro_notice']['link_url'] = 'https://www.buddyboss.com/pricing/';
				}
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
				 * @since BuddyBoss 3.0.0
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

				$field_data['upload_url'] = esc_url_raw( $upload_url );

				// Strip server-only keys before passing to frontend.
				unset( $upload_config['url_getter'] );
				$field_data['upload_config'] = $upload_config;
			}

			/**
			 * Filters the field data before it is returned.
			 *
			 * @since BuddyBoss 3.0.0
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
	 * @since BuddyBoss 3.0.0
	 */
	public function bb_admin_save_feature_settings() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$feature_id = isset( $_POST['feature_id'] ) ? sanitize_text_field( wp_unslash( $_POST['feature_id'] ) ) : '';
		$raw_json   = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON decoded and per-field sanitized below.
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! is_string( $raw_json ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid settings format.', 'buddyboss-platform' ) ) );
		}

		if ( empty( $feature_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature ID is required.', 'buddyboss-platform' ) ) );
		}

		if ( ! function_exists( 'bb_feature_registry' ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature registry not available.', 'buddyboss-platform' ) ) );
		}

		// Cap nesting depth at 8 — Settings 2.0 payloads never exceed
		// option => field => array-of-options shape (depth ~3). The default
		// 512 leaves room for parser-DoS payloads against this admin endpoint.
		try {
			$settings = json_decode( $raw_json, true, 8, JSON_THROW_ON_ERROR );
		} catch ( \JsonException $e ) {
			wp_send_json_error( array( 'message' => __( 'Invalid settings data.', 'buddyboss-platform' ) ) );
		}
		if ( ! is_array( $settings ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid settings data.', 'buddyboss-platform' ) ) );
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
		 * @since BuddyBoss 3.0.0
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
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $feature_id The feature being saved.
		 * @param array  $settings   Full submitted settings (JSON decoded).
		 */
		do_action( 'bb_admin_settings_before_save_feature', $feature_id, $settings );

		$all_fields = $registry->bb_get_all_fields( $feature_id );

		if ( empty( $all_fields ) ) {
			wp_send_json_error( array( 'message' => __( 'No fields registered for this feature.', 'buddyboss-platform' ) ) );
		}

		$saved = array();

		foreach ( $all_fields as $field_key => $field ) {
			$name = $field['name'];

			// Skip pro_only fields when Pro is not active — defense-in-depth
			// against crafted AJAX requests. The UI already disables these fields.
			if ( ! empty( $field['pro_only'] ) && ! function_exists( 'bb_platform_pro' ) ) {
				continue;
			}

			// Skip denylisted option names — defense-in-depth against a malicious
			// extension registering a field named 'siteurl', 'active_plugins', etc.
			if ( function_exists( 'bb_get_options_denylist' ) && in_array( $name, bb_get_options_denylist(), true ) ) {
				continue;
			}

			// Save the main field if it was submitted.
			if ( array_key_exists( $name, $settings ) ) {
				$value = $settings[ $name ];

				// Skip persistence for display-only fields that explicitly opt out
				// via `'sanitize_callback' => '__return_empty_string'`. These are
				// virtual fields the React change-tracker may include in the save
				// payload (notice / hidden / bb_verify_popup / static_text /
				// empty_state / reaction_notice / reaction_info / etc.), but they
				// hold no user data. Writing the empty string back creates zombie
				// autoloaded `_bb_*` / `_bb_om_*` rows in wp_options that bloat
				// `alloptions` and trigger downstream conditional bugs (e.g.
				// Offload Media hidden-flag conditionals flipping when the React
				// auto-save merges `response.data.saved` into UI state).
				if (
					! empty( $field['sanitize_callback'] ) &&
					is_string( $field['sanitize_callback'] ) &&
					'__return_empty_string' === $field['sanitize_callback']
				) {
					continue;
				}

				// Apply registered sanitize callback if present.
				//
				// Fallback to type-aware sanitization when the field author did
				// not supply one. `sanitize_text_field()` alone is not enough
				// for URL-shaped types — it strips tags but leaves
				// `javascript:` / `data:` schemes intact, so a value saved here
				// and later rendered as an `href`/`src` without `esc_url()`
				// becomes a stored-XSS sink. Narrow the type dispatch to cases
				// where the value shape is semantically URL-like; everything
				// else (including already-stringified primitives) keeps the
				// historical `sanitize_text_field()` behaviour so field
				// authors who relied on it don't see a silent data change.
				if ( ! empty( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
					$value = call_user_func( $field['sanitize_callback'], $value );
				} else {
					$field_type = isset( $field['type'] ) ? (string) $field['type'] : '';

					if ( is_string( $value ) && in_array( $field_type, array( 'url', 'permalink' ), true ) ) {
						// esc_url_raw strips disallowed schemes (default whitelist:
						// http, https, ftp, ftps, mailto, news, irc*, gopher,
						// nntp, feed, telnet, mms, rtsp, sms, svn, tel, fax,
						// xmpp, webcal, urn) so `javascript:alert(1)` becomes
						// empty. Storing an empty value is preferable to
						// round-tripping a hostile scheme.
						$value = esc_url_raw( $value );
					} elseif ( is_string( $value ) ) {
						$value = sanitize_text_field( $value );
					} elseif ( is_array( $value ) ) {
						$value = map_deep( $value, 'sanitize_text_field' );
					}
				}

				// toggle_list with option_prefix: persist each key to option_prefix + key (e.g. bp-feed-platform-{key}).
				if ( 'toggle_list' === ( $field['type'] ?? '' ) && ! empty( $field['option_prefix'] ) && is_array( $value ) ) {
					$prefix = $field['option_prefix'];
					foreach ( $value as $opt_key => $opt_val ) {
						$opt_name = $prefix . $opt_key;
						bp_update_option( $opt_name, absint( $opt_val ) );
					}
					// Cast to object so `json_encode()` always emits a map (`{}`) — even when
					// empty (all platforms toggled off). An empty PHP array encodes as `[]`,
					// which `SharePlatformsField.js` rejects via its
					// `typeof value === 'object' && !Array.isArray(value)` guard, causing the
					// UI to desync from the saved state until reload.
					$saved[ $name ] = (object) $value;
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
							// Numeric types and inline selects: use absint as default sanitizer.
							$control_value = absint( $control_value );
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
		 * @since BuddyBoss 3.0.0
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
			'message'        => __( 'Settings saved successfully.', 'buddyboss-platform' ),
			'saved'          => $saved,
			'refresh_panels' => $refresh_panels,
		);

		/**
		 * Filters the response data before sending success response for feature settings save.
		 * Allows plugins to add additional data to the response (e.g., migration_data for reactions).
		 *
		 * @since BuddyBoss 3.0.0
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
	 * @since BuddyBoss 3.0.0
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
	 * @since BuddyBoss 3.0.0
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
	 * @since BuddyBoss 3.0.0
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
		 * @since BuddyBoss 3.0.0
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
	 * @since BuddyBoss 3.0.0
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
	 * @since BuddyBoss 3.0.0
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
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $placeholders is built from count() only; values are bound via $uncached in prepare() on next line.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name IN ({$placeholders})",
				$uncached
			),
			OBJECT_K
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

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
	 * Resolve the settings route URL for a feature.
	 *
	 * Handles both default routes (/settings/{feature_id}) and custom routes
	 * that point to a different feature's panel (e.g., '/settings/notifications/onesignal').
	 * React's urlToRoute() converts the resulting query-param URL to a hash route.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID.
	 * @param array  $feature    Feature data array.
	 *
	 * @return string Settings route URL or empty string.
	 */
	private function bb_resolve_settings_route( $feature_id, $feature ) {
		if ( ! function_exists( 'bb_get_feature_settings_url' ) ) {
			return '';
		}

		// Toggle-only feature cards (e.g. Account Settings) intentionally
		// register `settings_route => ''` to declare they have no admin page.
		// Without this early return the resolver falls through to the default
		// `bb_get_feature_settings_url()` builder below, which would always
		// hand back a non-empty URL and force the React feature card to
		// render a "Settings" button that navigates to a blank panel.
		if (
			array_key_exists( 'settings_route', (array) $feature )
			&& '' === $feature['settings_route']
		) {
			return '';
		}

		// External settings route — add-on plugins with their own admin page.
		// These contain full URLs (http/https) or non-Settings-2.0 page params.
		if (
			! empty( $feature['settings_route'] ) &&
			0 === strpos( $feature['settings_route'], 'http' )
		) {
			return esc_url_raw( $feature['settings_route'] );
		}

		$settings_route = bb_get_feature_settings_url( $feature_id );

		// Custom settings_route points to a different feature's panel
		// (e.g., OneSignal '/settings/notifications/onesignal' → Notifications > OneSignal panel).
		if (
			! empty( $feature['settings_route'] ) &&
			'/settings/' . $feature_id !== $feature['settings_route']
		) {
			$parts          = array_values( array_filter( explode( '/', $feature['settings_route'] ) ) );
			$route_tab      = isset( $parts[1] ) ? $parts[1] : $feature_id;
			$route_pan      = isset( $parts[2] ) ? $parts[2] : '';
			$settings_route = bb_get_feature_settings_url( $route_tab, $route_pan );
		}

		return $settings_route;
	}

	/**
	 * Sanitize a `preview_config` array per-leaf.
	 *
	 * URL-looking string values go through `esc_url_raw`; other strings get
	 * `sanitize_text_field`; non-strings pass through. Detection is
	 * value-shape based (FILTER_VALIDATE_URL + protocol-relative `//` prefix)
	 * rather than key-suffix based so new URL-shaped keys (`_src`, `_href`,
	 * `_link`, `_path`, etc.) don't need to be whitelisted as they're added.
	 *
	 * Nested arrays get the same per-leaf treatment via recursion —
	 * `map_deep( ..., 'sanitize_text_field' )` would flatten nested URLs and
	 * strip the `://` protocol separator.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $config Raw preview_config array from field registration.
	 * @return array Sanitized config.
	 */
	private function bb_sanitize_preview_config( $config ) {
		$sanitized = array();
		foreach ( $config as $key => $value ) {
			if ( is_array( $value ) ) {
				$sanitized[ $key ] = $this->bb_sanitize_preview_config( $value );
			} elseif ( is_string( $value ) ) {
				$sanitized[ $key ] = $this->bb_sanitize_preview_value( $value );
			} else {
				$sanitized[ $key ] = $value;
			}
		}
		return $sanitized;
	}

	/**
	 * Decide between `esc_url_raw` and `sanitize_text_field` for a single
	 * preview-config string.
	 *
	 * Detection is an explicit scheme regex — `http(s)://` or protocol-relative
	 * `//` — instead of `FILTER_VALIDATE_URL`, which accepts obscure inputs
	 * like `javascript://comment%0Aalert(1)`. `esc_url_raw` downstream would
	 * neutralize that payload anyway, but a strict allowlist is defense in
	 * depth and keeps the intent obvious.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $value Raw string value.
	 * @return string Sanitized string.
	 */
	private function bb_sanitize_preview_value( $value ) {
		$trimmed = trim( $value );
		if ( '' === $trimmed ) {
			return sanitize_text_field( $value );
		}
		// Allowlist http/https/protocol-relative only.
		if ( preg_match( '#^(https?:)?//#i', $trimmed ) ) {
			return esc_url_raw( $trimmed );
		}
		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize a help_url value that may be either a full URL or a bare KB article ID.
	 *
	 * The Settings 2.0 React side accepts two `help_url` shapes from PHP feature
	 * registration: a full URL (e.g. `bp_get_admin_url( 'admin.php?page=bp-help&article=127197' )`)
	 * or a bare KB article ID (e.g. `'636101'`). Running `esc_url_raw()` on a
	 * bare numeric ID is wrong: WordPress's URL fixer prepends `http://`,
	 * producing `http://636101`. The React `fetchHelpContent()` resolver then
	 * passes that through unchanged, building the broken endpoint
	 * `https://buddyboss.com/wp-json/wp/v2/ht-kb/http://636101`.
	 *
	 * Routing: bare numeric IDs (digits only, optional surrounding whitespace)
	 * are passed through `sanitize_text_field()`. Anything else is treated as a
	 * URL and run through `esc_url_raw()`. The numeric branch covers today's
	 * registrations (`'636101'`, `'636152'`, `'636156'`, `'637448'`); slug-style
	 * IDs would need a separate branch and aren't used today.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param mixed $value Raw `help_url` value from feature/section registration.
	 * @return string Sanitized URL or bare ID, suitable for the React layer.
	 */
	private function bb_sanitize_help_url( $value ) {
		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			return '';
		}
		$trimmed = trim( (string) $value );
		if ( '' === $trimmed ) {
			return '';
		}
		// Bare numeric KB article ID — never treat as URL.
		if ( ctype_digit( $trimmed ) ) {
			return sanitize_text_field( $trimmed );
		}
		return esc_url_raw( $trimmed );
	}

	/**
	 * Normalize the field group parameter to a consistent array format.
	 *
	 * Accepts either a string (group key only, backward compatible) or an
	 * array with 'key' and optional 'label'. Always returns null or an
	 * array with 'key' and 'label' keys.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string|array|null $group Raw group value from field registration.
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
	 * @since BuddyBoss 3.0.0
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
					'posts_per_page'         => -1, // Bounded by tax_query — all matching email templates needed.
					'no_found_rows'          => true, // Skip COUNT(*) — not paginating.
					'update_post_meta_cache' => false, // Post meta not needed for URL resolution.
					'post_type'              => bp_get_email_post_type(),
					'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => bp_get_email_tax_type(),
							'field'    => 'slug',
							'terms'    => array_keys( $slug_term_ids ),
						),
					),
					'fields'                 => 'ids',
				)
			);

			// Prime the term cache for all email posts to avoid N+1 queries.
			update_object_term_cache( $email_posts, bp_get_email_post_type() );

			// Map each post to its email type slug(s). get_the_terms() reads the
			// primed object cache directly; wp_get_object_terms() with custom
			// fields bypasses that fast path and re-queries per post.
			$tax = bp_get_email_tax_type();
			foreach ( $email_posts as $post_id ) {
				$post_terms = get_the_terms( $post_id, $tax );
				if ( ! empty( $post_terms ) && ! is_wp_error( $post_terms ) ) {
					foreach ( $post_terms as $term ) {
						$slug = $term->slug;
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
						// Count templates that have a published email post (not just a term).
						$total_email_count = 0;
						foreach ( $registered_emails as $email_type ) {
							if ( ! empty( $slug_post_map[ $email_type ] ) ) {
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
								'admin.php?page=bb-settings&tab=emails&panel=all_emails&popup=yes'
							);
						}
					} else {
						// No registered email templates — provide URL to emails admin
						// so React can show "Missing Email Template" with a link.
						$email_template['url'] = get_admin_url(
							bp_get_root_blog_id(),
							'admin.php?page=bb-settings&tab=emails&panel=all_emails&popup=yes'
						);
					}

					// Get preference sub-types (email, web, app).
					$sub_types = array();
					if ( function_exists( 'bb_notification_preferences_types' ) ) {
						$options = bb_notification_preferences_types( $notification_field );
						if ( ! empty( $options ) ) {
							foreach ( $options as $key => $v ) {
								$parent_disabled = ! empty( $notification_field['notification_read_only'] ) && true === $notification_field['notification_read_only'];
								$is_disabled     = apply_filters( 'bb_is_' . $notification_field['key'] . '_' . $key . '_preference_type_disabled', $v['disabled'], $notification_field['key'], $key );
								$is_render       = apply_filters( 'bb_is_' . $notification_field['key'] . '_' . $key . '_preference_type_render', $v['is_render'], $notification_field['key'], $key );

								$sub_types[ $key ] = array(
									'label'      => $v['label'],
									'is_checked' => $v['is_checked'],
									'is_render'  => $is_render,
									'disabled'   => $is_disabled || $parent_disabled,
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
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $field_value The extension data array.
	 *
	 * @return array Flattened toggle values keyed by extension ID.
	 */
	private function bb_extract_extension_toggle_values( $field_value ) {
		// Defensive: a third-party extension might register a field with
		// malformed extension_data. Guard against non-iterable values before
		// the foreach and skip individual entries that aren't scalar or array.
		if ( ! is_array( $field_value ) ) {
			return array();
		}

		$toggle_values = array();
		foreach ( $field_value as $key => $ext ) {
			// Keys must be strings/ints — objects or arrays as keys are invalid.
			if ( ! is_string( $key ) && ! is_int( $key ) ) {
				continue;
			}

			if ( is_array( $ext ) ) {
				// Expected shape: [ 'is_active' => 0|1, 'extension' => ..., 'mime_type' => ... ].
				if ( array_key_exists( 'is_active', $ext ) ) {
					$toggle_values[ $key ] = absint( $ext['is_active'] );
				} else {
					// Malformed nested shape — default to disabled rather than erroring.
					$toggle_values[ $key ] = 0;
				}
			} elseif ( is_scalar( $ext ) ) {
				$toggle_values[ $key ] = absint( $ext );
			}
			// Objects/resources are silently skipped.
		}
		return $toggle_values;
	}

	/**
	 * AJAX handler: search published pages for async_select fields.
	 *
	 * Returns paginated {value, label} results with optional search term.
	 * Includes "Default" and "Custom URL" as the first two options.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function bb_admin_search_published_pages() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_verify_request().
		$term        = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
		$page        = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$selected_id = isset( $_POST['selected_id'] ) ? sanitize_text_field( wp_unslash( $_POST['selected_id'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$per_page = 20;

		// Build full options list: Default + Custom URL + published pages.
		$all_options = array(
			array(
				'value' => '',
				'label' => __( 'Default', 'buddyboss-platform' ),
			),
			array(
				'value' => '0',
				'label' => __( 'Custom URL', 'buddyboss-platform' ),
			),
		);

		if ( function_exists( 'bb_get_published_pages' ) ) {
			$published = bb_get_published_pages( true );
			if ( ! empty( $published ) ) {
				$all_options = array_merge( $all_options, $published );
			}
		}

		// If resolving a selected ID, return just that item.
		if ( '' !== $selected_id ) {
			foreach ( $all_options as $opt ) {
				if ( (string) $opt['value'] === (string) $selected_id ) {
					wp_send_json_success(
						array(
							'results'  => array( $opt ),
							'has_more' => false,
						)
					);
				}
			}
			// Fallback: return Default if not found.
			wp_send_json_success(
				array(
					'results'  => array( $all_options[0] ),
					'has_more' => false,
				)
			);
		}

		// Filter by search term.
		if ( '' !== $term ) {
			$term_lower  = strtolower( $term );
			$all_options = array_filter(
				$all_options,
				function ( $opt ) use ( $term_lower ) {
					return false !== strpos( strtolower( $opt['label'] ), $term_lower );
				}
			);
			$all_options = array_values( $all_options );
		}

		// Paginate.
		$total  = count( $all_options );
		$offset = ( $page - 1 ) * $per_page;
		$paged  = array_slice( $all_options, $offset, $per_page );

		wp_send_json_success(
			array(
				'results'  => $paged,
				'has_more' => ( $offset + $per_page ) < $total,
			)
		);
	}

	/**
	 * AJAX handler: search published pages for the Appearance → Pages directory
	 * dropdowns.
	 *
	 * Sibling of `bb_admin_search_published_pages()` but scoped to the
	 * page-directory-picker use case. The difference is the empty first option:
	 * this endpoint prepends a single `{ value: '', label: '— Select a page —' }`
	 * entry (mirrors legacy `wp_dropdown_pages( 'show_option_none' )`). The
	 * other endpoint prepends "Default" + "Custom URL" which are wrong shapes
	 * for the directory-page fields.
	 *
	 * Multisite: switches to the BP root blog before the page query so that on
	 * a sub-site admin screen the results still come from the community's root
	 * blog, matching what `bp_core_admin_get_directory_pages()` / the save
	 * handler operate on.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function bb_admin_search_pages_list() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_verify_request().
		$term        = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
		$page        = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$selected_id = isset( $_POST['selected_id'] ) ? absint( wp_unslash( $_POST['selected_id'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$per_page = 20;
		$switched = false;
		if ( is_multisite() && function_exists( 'bp_get_root_blog_id' ) ) {
			$root_blog_id = bp_get_root_blog_id();
			if ( $root_blog_id && get_current_blog_id() !== $root_blog_id ) {
				switch_to_blog( $root_blog_id );
				$switched = true;
			}
		}

		// Re-check the cap POST-switch using the same `bp_moderate` cap that
		// `bb_verify_request()` → `bb_admin_verify_ajax_request()` validated
		// against the request's origin blog. A sub-site admin who passed
		// there may not have equivalent rights on the root blog — and the
		// page list we're about to return is authored there. Using
		// `bp_current_user_can` keeps the cap check consistent with every
		// other endpoint on this class and honours BP's cap-mapping filters.
		if ( ! bp_current_user_can( 'bp_moderate' ) ) {
			if ( $switched ) {
				restore_current_blog();
			}
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions on the target site.', 'buddyboss-platform' ) ) );
		}

		$empty_option = array(
			'value' => '',
			'label' => __( '— Select a page —', 'buddyboss-platform' ),
		);

		// Selected-ID resolve path: bypass pagination + search so the dropdown
		// can render its initial label without a wildcard query. Used by the
		// async_select field on mount with the currently-stored page ID.
		if ( $selected_id > 0 ) {
			$post   = get_post( $selected_id );
			$result = $empty_option;
			if ( $post && 'page' === $post->post_type && 'publish' === $post->post_status ) {
				$result = array(
					'value' => (string) $post->ID,
					/* translators: %d: WordPress page ID, used as a fallback when a page has no title. */
					'label' => $post->post_title ? $post->post_title : sprintf( __( '(no title) #%d', 'buddyboss-platform' ), $post->ID ),
				);
			}
			if ( $switched ) {
				restore_current_blog();
			}
			wp_send_json_success(
				array(
					'results'  => array( $result ),
					'has_more' => false,
				)
			);
		}

		// Fetch $per_page + 1 rows with no_found_rows=true so WP_Query skips
		// SQL_CALC_FOUND_ROWS. On sites with many pages plus a LIKE '%term%'
		// match, SQL_CALC is expensive and the picker doesn't need the exact
		// total — just "is there a next page?". We derive has_more from the
		// overflow row.
		$query_args = array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page + 1,
			'paged'          => $page,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		);
		if ( '' !== $term ) {
			$query_args['s'] = $term;
		}

		$query    = new WP_Query( $query_args );
		$results  = array();
		$is_first = ( 1 === $page && '' === $term );

		if ( $is_first ) {
			$results[] = $empty_option;
		}

		$has_more = false;
		if ( $query->have_posts() ) {
			$page_ids = $query->posts;
			// If we got the extra overflow row, there IS a next page. Trim it
			// from the returned set so the picker shows exactly $per_page rows.
			if ( count( $page_ids ) > $per_page ) {
				$has_more = true;
				$page_ids = array_slice( $page_ids, 0, $per_page );
			}
			// fields=ids skips post-object hydration; prime once so get_the_title()
			// is a cache hit instead of one get_post() per page-row in the loop.
			// _prime_post_caches() is public since WP 6.1; guard for WP 6.0 compat.
			if ( function_exists( '_prime_post_caches' ) ) {
				_prime_post_caches( $page_ids, false, false );
			}
			foreach ( $page_ids as $page_id ) {
				$title     = get_the_title( $page_id );
				$results[] = array(
					'value' => (string) $page_id,
					/* translators: %d: WordPress page ID, used as a fallback when a page has no title. */
					'label' => $title ? $title : sprintf( __( '(no title) #%d', 'buddyboss-platform' ), $page_id ),
				);
			}
		}

		if ( $switched ) {
			restore_current_blog();
		}

		wp_send_json_success(
			array(
				'results'  => $results,
				'has_more' => $has_more,
			)
		);
	}

	/**
	 * AJAX handler: create a blank WordPress page and return its ID + title.
	 *
	 * Used by the "Create Page" button on the Appearance → Pages dropdowns.
	 * Creates a minimal `publish` page titled after the directory slug so the
	 * user doesn't have to leave the admin to set up a directory page.
	 *
	 * The legacy jQuery flow (`.create-background-page` click handler in
	 * `settings-page.js`) did the same thing — this endpoint replaces that
	 * request with a nonce-verified, cap-gated version callable from the
	 * React admin.
	 *
	 * Mirrors the multisite switching pattern used by the sibling search
	 * endpoint so the new page is created on the community's root blog even
	 * when a network admin is editing from a sub-site.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function bb_admin_create_directory_page() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_verify_request().
		$slug  = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( '' === $slug ) {
			wp_send_json_error( array( 'message' => __( 'Missing page slug.', 'buddyboss-platform' ) ) );
		}

		// Allow-list the slug against the set of directory keys that the
		// Pages panel actually renders a Create-Page button for. The button
		// is only ever clicked from one of those registered dropdowns; any
		// other `slug` value is a hand-crafted request and should be
		// rejected. Both helpers pass their returns through filters, so the
		// (array) cast keeps this defensive against third-party filters
		// returning non-arrays (PHP 8+ iterator contract).
		$allowed_slugs = array();
		if ( function_exists( 'bp_core_admin_get_directory_pages' ) ) {
			$allowed_slugs = array_merge( $allowed_slugs, array_keys( (array) bp_core_admin_get_directory_pages() ) );
		}
		if ( function_exists( 'bp_core_admin_get_static_pages' ) ) {
			$allowed_slugs = array_merge( $allowed_slugs, array_keys( (array) bp_core_admin_get_static_pages() ) );
		}
		if ( ! in_array( $slug, $allowed_slugs, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown directory slug.', 'buddyboss-platform' ) ) );
		}

		$switched = false;
		if ( is_multisite() && function_exists( 'bp_get_root_blog_id' ) ) {
			$root_blog_id = bp_get_root_blog_id();
			if ( $root_blog_id && get_current_blog_id() !== $root_blog_id ) {
				switch_to_blog( $root_blog_id );
				$switched = true;
			}
		}

		// Re-check the cap POST-switch using the same `bp_moderate` cap that
		// `bb_verify_request()` validated on the origin blog. Network admins
		// pass on both; a sub-site admin who could manage their own blog is
		// blocked here on the root blog, which is correct — only the
		// community owner should create directory pages. Using
		// `bp_current_user_can` honours BP's cap-mapping filters (e.g. role
		// plugins that remap `bp_moderate` for a custom role).
		if ( ! bp_current_user_can( 'bp_moderate' ) ) {
			if ( $switched ) {
				restore_current_blog();
			}
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions on the target site.', 'buddyboss-platform' ) ) );
		}

		$title = '' !== $label ? $label : ucfirst( str_replace( array( '-', '_' ), ' ', $slug ) );

		$post_id = wp_insert_post(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_title'     => $title,
				'post_content'   => '',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			if ( $switched ) {
				restore_current_blog();
			}
			wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
		}

		// `esc_url_raw` before serialising to JSON — defence in depth since the
		// URL is echoed back to an admin-origin React client.
		$permalink = esc_url_raw( get_permalink( $post_id ) );

		if ( $switched ) {
			restore_current_blog();
		}

		wp_send_json_success(
			array(
				'id'        => (int) $post_id,
				'title'     => $title,
				'permalink' => $permalink,
			)
		);
	}
}

// Initialize.
new BB_Admin_Settings_Ajax();
