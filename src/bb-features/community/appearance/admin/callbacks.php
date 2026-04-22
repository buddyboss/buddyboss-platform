<?php
/**
 * BuddyBoss Admin Settings — Appearance Feature Callbacks.
 *
 * Sanitize callbacks for Appearance feature settings. Each function is shaped as a
 * standard WordPress field sanitize callback — accepts a single value and returns
 * the sanitized value. The onboarding wizard's BB_ReadyLaunch_Onboarding class
 * delegates to these so the Settings 2.0 admin and the onboarding wizard share a
 * single source of truth for sanitization rules.
 *
 * @package BuddyBoss\Features\Community\Appearance
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize the Site Layout dropdown value to a boolean.
 *
 * The `bb_rl_enabled` option is a boolean end-to-end so `bb_is_readylaunch_enabled()`
 * (which casts via `(bool)`) keeps working. String values ("1"/"0"/"true") all coerce
 * correctly via the (int) round-trip.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value Submitted value.
 * @return bool Sanitized boolean.
 */
function bb_appearance_sanitize_layout( $value ) {
	if ( is_bool( $value ) ) {
		return $value;
	}
	if ( is_string( $value ) ) {
		$value = strtolower( trim( $value ) );
		if ( in_array( $value, array( 'readylaunch', 'true', '1', 'yes', 'on' ), true ) ) {
			return true;
		}
		if ( in_array( $value, array( 'wordpress_theme', 'false', '0', 'no', 'off', '' ), true ) ) {
			return false;
		}
	}
	return (bool) (int) $value;
}

/**
 * Sanitize the ReadyLaunch theme mode (light / dark / choice).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value Submitted value.
 * @return string Sanitized mode ('light' on invalid input).
 */
function bb_appearance_sanitize_theme_mode( $value ) {
	$allowed = array( 'light', 'dark', 'choice' );
	return in_array( $value, $allowed, true ) ? $value : 'light';
}

/**
 * Sanitize a ReadyLaunch primary color with an optional fallback default.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed  $value   Submitted value.
 * @param string $default Default hex color if sanitization fails.
 * @return string Sanitized hex color.
 */
function bb_appearance_sanitize_color( $value, $default = '#3E34FF' ) {
	$sanitized = sanitize_hex_color( $value );
	return $sanitized ? $sanitized : $default;
}

/**
 * Sanitize a ReadyLaunch logo media field.
 *
 * Accepts either the current object shape (id/url/alt/title) or the legacy integer
 * ID. Returns null when the input is empty/invalid.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value Submitted value.
 * @return array|int|null Sanitized media object, legacy integer ID, or null.
 */
function bb_appearance_sanitize_media( $value ) {
	if ( is_array( $value ) && isset( $value['id'] ) ) {
		$id = intval( $value['id'] );

		// Verify the submitted URL actually belongs to the attachment ID — guards
		// against an admin crafting a payload with an arbitrary off-site URL
		// attached to a legitimate attachment ID. On mismatch, trust the ID and
		// re-derive the URL via WordPress's own resolver.
		$submitted_url = isset( $value['url'] ) ? esc_url_raw( $value['url'] ) : '';
		$canonical_url = $id > 0 ? wp_get_attachment_url( $id ) : '';
		if ( $canonical_url && $submitted_url !== $canonical_url ) {
			$submitted_url = $canonical_url;
		}

		return array(
			'id'    => $id,
			'url'   => $submitted_url,
			'alt'   => isset( $value['alt'] ) ? sanitize_text_field( $value['alt'] ) : '',
			'title' => isset( $value['title'] ) ? sanitize_text_field( $value['title'] ) : '',
		);
	}
	if ( is_numeric( $value ) ) {
		return intval( $value );
	}
	return null;
}

/**
 * Sanitize the Template Pages selector into the legacy `bb_rl_enabled_pages` shape.
 *
 * Accepts either:
 * - Sequential list of page keys (onboarding form shape): `array( 'registration', 'courses' )`
 * - Associative map (Settings 2.0 checkbox_group shape): `array( 'registration' => true, 'courses' => false )`
 *
 * Returns the canonical object-map shape consumed by BB_Readylaunch at
 * `class-bb-readylaunch.php:3073`.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value Submitted value.
 * @return array{registration: bool, courses: bool} Sanitized map.
 */
function bb_appearance_sanitize_enabled_pages( $value ) {
	$map = array(
		'registration' => false,
		'courses'      => false,
	);

	if ( ! is_array( $value ) ) {
		return $map;
	}

	// Sequential list of selected keys.
	if ( array_values( $value ) === $value ) {
		foreach ( $map as $page_key => $unused ) {
			$map[ $page_key ] = in_array( $page_key, $value, true );
		}
		return $map;
	}

	// Associative map — respect each key's truthiness.
	foreach ( $map as $page_key => $unused ) {
		$map[ $page_key ] = ! empty( $value[ $page_key ] );
	}
	return $map;
}

/**
 * Sanitize a sidebar toggle map (activity / member profile / groups).
 *
 * Accepts either:
 * - Sequential list of widget items with `{id, enabled}` (onboarding shape)
 * - Associative map of `widget_id => bool` (Settings 2.0 + legacy admin + templates)
 *
 * Returns the canonical object-map shape consumed by templates at
 * `bp-templates/bp-nouveau/readylaunch/sidebar/right-sidebar.php:31-75`.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value Submitted value.
 * @return array<string, bool> Sanitized map.
 */
function bb_appearance_sanitize_sidebar_map( $value ) {
	$map = array();

	if ( ! is_array( $value ) ) {
		return $map;
	}

	// Sequential list (onboarding `draggable` field shape).
	if ( array_values( $value ) === $value && isset( $value[0] ) && is_array( $value[0] ) ) {
		foreach ( $value as $item ) {
			if ( ! is_array( $item ) || empty( $item['id'] ) ) {
				continue;
			}
			$map[ sanitize_key( $item['id'] ) ] = ! empty( $item['enabled'] );
		}
		return $map;
	}

	// Associative map.
	foreach ( $value as $widget_id => $enabled ) {
		$map[ sanitize_key( $widget_id ) ] = ! empty( $enabled );
	}
	return $map;
}

/**
 * Sanitize the `bb_rl_side_menu` field.
 *
 * Accepts either:
 * - Sequential list of menu items with `{id, enabled, order, icon}` (onboarding shape)
 * - Associative map of `id => {enabled, order, icon}` (Settings 2.0 shape)
 *
 * Returns the canonical associative map consumed by ReadyLaunch templates.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value Submitted value.
 * @return array<string, array{enabled: bool, order: int, icon: string}> Sanitized map.
 */
function bb_appearance_sanitize_side_menu( $value ) {
	$map = array();

	if ( ! is_array( $value ) ) {
		return $map;
	}

	// Sequential list shape from the onboarding draggable control.
	if ( array_values( $value ) === $value && isset( $value[0] ) && is_array( $value[0] ) ) {
		foreach ( $value as $item ) {
			if ( ! is_array( $item ) || empty( $item['id'] ) ) {
				continue;
			}
			$map[ sanitize_key( $item['id'] ) ] = array(
				'enabled' => isset( $item['enabled'] ) ? (bool) $item['enabled'] : true,
				'order'   => isset( $item['order'] ) ? (int) $item['order'] : 0,
				'icon'    => isset( $item['icon'] ) ? sanitize_text_field( $item['icon'] ) : '',
			);
		}
		return $map;
	}

	// Associative-map shape.
	foreach ( $value as $id => $config ) {
		if ( ! is_array( $config ) ) {
			continue;
		}
		$map[ sanitize_key( $id ) ] = array(
			'enabled' => isset( $config['enabled'] ) ? (bool) $config['enabled'] : true,
			'order'   => isset( $config['order'] ) ? (int) $config['order'] : 0,
			'icon'    => isset( $config['icon'] ) ? sanitize_text_field( $config['icon'] ) : '',
		);
	}
	return $map;
}

/**
 * Sanitize the `bb_rl_custom_links` field.
 *
 * Always returns a sequential list of link objects with `id`, `title`, `url`, and
 * `isEditing => false` (matches legacy format).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value Submitted value.
 * @return array<int, array{id: string, title: string, url: string, isEditing: false}> Sanitized links.
 */
function bb_appearance_sanitize_custom_links( $value ) {
	$links = array();

	if ( ! is_array( $value ) ) {
		return $links;
	}

	foreach ( $value as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$links[] = array(
			'id'        => isset( $item['id'] ) ? sanitize_text_field( $item['id'] ) : '',
			'title'     => isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '',
			'url'       => isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '',
			'isEditing' => false,
		);
	}

	return $links;
}

/**
 * Apply ReadyLaunch configuration side-effects.
 *
 * Mirrors the component activation, registration-page enablement, follow-feature
 * enablement, and DB schema upgrade that the onboarding wizard runs after a user
 * completes the flow. Invoked from two entry points:
 *
 *   1. `BB_ReadyLaunch_Onboarding::apply_readylaunch_configuration()` (wizard completion).
 *   2. `bb_admin_save_feature_settings_after` for the `appearance` feature (Settings 2.0 auto-save).
 *
 * Without this symmetry, toggling "Groups" in the Appearance → Menus → Side menu
 * would silently leave the Groups BP component inactive — the menu item would
 * render and 404. Same failure mode for Forums, Messages, Notifications, Friends
 * (via Connections), and Activity (via My Network / Activity Feed).
 *
 * Idempotent: re-running with the same `$settings` is a no-op because each branch
 * guards against the target state already existing (e.g., `empty( $active_components[...] )`).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $settings Sanitized bb_rl_* + blogname settings to apply.
 * @return void
 */
function bb_appearance_apply_configuration( $settings ) {
	if ( empty( $settings ) || ! is_array( $settings ) ) {
		return;
	}

	// Force-enable registration when the registration template page is on.
	if ( ! empty( $settings['bb_rl_enabled_pages'] ) && is_array( $settings['bb_rl_enabled_pages'] ) ) {
		$pages = $settings['bb_rl_enabled_pages'];
		if (
			! empty( $pages['registration'] ) &&
			function_exists( 'bp_enable_site_registration' ) &&
			! bp_enable_site_registration( false ) &&
			function_exists( 'bp_allow_custom_registration' ) &&
			! bp_allow_custom_registration()
		) {
			bp_update_option( 'bp-enable-site-registration', true );
			bp_update_option( 'allow-custom-registration', 0 );
		}
	}

	// Collect the set of components this save implies should be active. Writes
	// below are coalesced so we pay the schema-upgrade / page-mapping cost at
	// most once per save — not once per triggering field.
	$components_to_activate = array();
	$active_components      = bp_get_option( 'bp-active-components', array() );
	$newly_activated        = array();

	// Side-menu ↔ component map (matches onboarding's map verbatim).
	if ( ! empty( $settings['bb_rl_side_menu'] ) && is_array( $settings['bb_rl_side_menu'] ) ) {
		$menu_component_map = array(
			'activity_feed' => 'activity',
			'groups'        => 'groups',
			'forums'        => 'forums',
			'messages'      => 'messages',
			'notifications' => 'notifications',
		);
		$side_menu          = $settings['bb_rl_side_menu'];
		foreach ( $menu_component_map as $menu_id => $component_key ) {
			if ( ! empty( $side_menu[ $menu_id ]['enabled'] ) ) {
				$components_to_activate[ $component_key ] = true;
			}
		}
	}

	// Sidebar-widget ↔ component map.
	if ( ! empty( $settings['bb_rl_activity_sidebars'] ) ) {
		$components_to_activate['activity'] = true;
	}
	if ( ! empty( $settings['bb_rl_groups_sidebars'] ) ) {
		$components_to_activate['groups'] = true;
	}
	if ( ! empty( $settings['bb_rl_member_profile_sidebars'] ) && is_array( $settings['bb_rl_member_profile_sidebars'] ) ) {
		$profile_sidebars = $settings['bb_rl_member_profile_sidebars'];
		// Accept either the sequential list (onboarding draggable shape) or the
		// Settings 2.0 associative map (`my_network => true`).
		$has_my_network  = in_array( 'my_network', $profile_sidebars, true ) || ! empty( $profile_sidebars['my_network'] );
		$has_connections = in_array( 'connections', $profile_sidebars, true ) || ! empty( $profile_sidebars['connections'] );

		if ( $has_my_network ) {
			$components_to_activate['activity'] = true;
			bp_update_option( '_bp_enable_activity_follow', true );
		}
		if ( $has_connections ) {
			$components_to_activate['friends'] = true;
		}
	}

	if ( ! empty( $components_to_activate ) ) {
		// Dual-write to bp-active-components (legacy `bp_is_active()` reads) AND
		// bb-active-features (Settings 2.0 React reads via `bb_is_feature_active()`).
		// Pattern mirrors `BB_Feature_Registry::bb_activate_feature()` — we can't
		// just call that because (a) `friends` is not a registered feature, and
		// (b) calling it per-component would run schema/page/flush N times.
		$active_features = bp_get_option( 'bb-active-features', array() );
		$registry        = function_exists( 'bb_feature_registry' ) ? bb_feature_registry() : null;

		foreach ( array_keys( $components_to_activate ) as $component_key ) {
			if ( empty( $active_components[ $component_key ] ) ) {
				$active_components[ $component_key ] = 1;
				$newly_activated[]                   = $component_key;
			}
			// Only mark in bb-active-features when the component ID is itself a
			// registered Settings 2.0 feature (activity / groups / forums /
			// messages / notifications). `friends` isn't — its UI lives under
			// the `members` feature. For those, the registry's migration
			// fallback reads bp-active-components when the feature is absent.
			if ( $registry && method_exists( $registry, 'bb_get_feature' ) && null !== $registry->bb_get_feature( $component_key ) ) {
				$active_features[ $component_key ] = 1;
			}
		}

		if ( ! empty( $newly_activated ) ) {
			bp_update_option( 'bp-active-components', $active_components );
			bp_update_option( 'bb-active-features', $active_features );
		}
	}

	// Upgrade the DB schema + wire directory pages for any newly-activated
	// component. Both helpers are idempotent — running them when nothing
	// changed is safe but wasteful, so gate on `$newly_activated`.
	if ( ! empty( $newly_activated ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		if ( defined( 'BP_PLUGIN_DIR' ) ) {
			$schema_path = BP_PLUGIN_DIR . '/bp-core/admin/bp-core-admin-schema.php';
			if ( file_exists( $schema_path ) ) {
				require_once $schema_path;
			}
		}
		if ( function_exists( 'bp_core_install' ) ) {
			bp_core_install( $active_components );
		}
		if ( function_exists( 'bp_core_add_page_mappings' ) ) {
			bp_core_add_page_mappings( $active_components );
		}

		// Clear the feature-registry caches for each newly-activated component.
		if ( isset( $registry ) && $registry && method_exists( $registry, 'bb_clear_feature_caches' ) ) {
			foreach ( $newly_activated as $component_key ) {
				$registry->bb_clear_feature_caches( $component_key );
			}
		}

		// Schedule a single rewrite-rule flush on shutdown so the new components'
		// permalinks take effect on the next request. Static idempotency guard
		// prevents multiple flushes if this function is called more than once
		// in a single request (e.g. a feature toggle followed by a settings save).
		// Mirrors `BB_Feature_Registry::bb_schedule_rewrite_flush()` which is
		// private — we intentionally re-inline the shutdown-flush pattern here.
		static $bb_rl_rewrite_flush_scheduled = false;
		if ( ! $bb_rl_rewrite_flush_scheduled ) {
			$bb_rl_rewrite_flush_scheduled = true;
			add_action(
				'shutdown',
				static function () {
					flush_rewrite_rules();
				}
			);
		}

		// Mirror `bb_feature_activated` so Pro / third-party hooks that listen
		// for registry-level activation still fire.
		foreach ( $newly_activated as $component_key ) {
			/** This action is documented in class-bb-feature-registry.php */
			do_action( 'bb_feature_activated', $component_key );
		}
	}

	/**
	 * Fires after ReadyLaunch configuration has been applied.
	 *
	 * @since BuddyBoss 2.10.0 (moved from BB_ReadyLaunch_Onboarding in [BBVERSION])
	 *
	 * @param array $settings The final configuration settings.
	 */
	do_action( 'bb_rl_configuration_applied', $settings );
}

/**
 * Invoke the Appearance configuration side-effects after a Settings 2.0 save.
 *
 * Hooks `bb_admin_save_feature_settings_after` and delegates to
 * `bb_appearance_apply_configuration()` so the component-activation + schema
 * upgrade logic runs exactly once per save — keeping the Settings 2.0 and
 * onboarding code paths in lockstep.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id The feature that was saved.
 * @param array  $settings   Raw settings payload (unused — we prefer sanitized).
 * @param array  $saved      Sanitized values written to options.
 * @return void
 */
function bb_appearance_on_settings_saved( $feature_id, $settings, $saved ) {
	if ( 'appearance' !== $feature_id ) {
		return;
	}

	// `blogname` is the WordPress site title. Our Settings 2.0 save handler
	// blocks it via `bb_get_options_denylist()` (it's there to prevent
	// third-party extensions writing to protected WP options). The Appearance
	// feature legitimately needs to write blogname — save it directly here
	// after a capability re-check.
	if ( isset( $settings['blogname'] ) && current_user_can( 'manage_options' ) ) {
		$new_blogname = sanitize_text_field( (string) $settings['blogname'] );
		if ( '' !== $new_blogname ) {
			update_option( 'blogname', $new_blogname );
		}
	}

	bb_appearance_apply_configuration( is_array( $saved ) ? $saved : array() );
}
add_action( 'bb_admin_save_feature_settings_after', 'bb_appearance_on_settings_saved', 10, 3 );

/**
 * Coerce `bb_rl_enabled` for the Site Layout SelectControl.
 *
 * The option is stored as a boolean (all legacy consumers use truthy checks),
 * but the Settings 2.0 `select` field compares against the string options
 * `'1'` / `'0'`. Without this filter React renders the dropdown blank on
 * sites where the option is already set.
 *
 * This is a display-side coercion only — the save-side `bb_appearance_sanitize_layout`
 * callback coerces back to bool before write, so the stored shape never changes.
 *
 * Sidebar / side-menu / enabled_pages shape normalization used to live here
 * too, but those shapes are now canonicalized at write time in
 * `BB_ReadyLaunch_Onboarding::sanitize_final_settings()` and a one-shot
 * migration (`bb_update_to_3_0_1`) cleans any legacy sequential data in the
 * DB, so read-side normalization is no longer needed.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $field_data Formatted field data being sent to React.
 * @param array  $field      Raw field registration args.
 * @param string $feature_id The feature ID being formatted.
 * @return array Field data with normalized `value` / `default`.
 */
function bb_appearance_normalize_field_data( $field_data, $field, $feature_id ) {
	if ( 'appearance' !== $feature_id ) {
		return $field_data;
	}

	if ( 'bb_rl_enabled' === ( $field['name'] ?? '' ) ) {
		$field_data['value']   = ! empty( $field_data['value'] ) && '0' !== $field_data['value'] ? '1' : '0';
		$field_data['default'] = ! empty( $field_data['default'] ) && '0' !== $field_data['default'] ? '1' : '0';
	}

	return $field_data;
}
add_filter( 'bb_admin_settings_format_field_data', 'bb_appearance_normalize_field_data', 10, 3 );

/**
 * Convert a sequential list of enabled keys (legacy onboarding shape) to an
 * associative `{key: 1}` map (Settings 2.0 shape). Pass-through for values
 * that are already map-shaped.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value Value to normalize.
 * @return array|mixed Associative map, or the original value if not an array.
 */
function bb_appearance_normalize_list_to_map( $value ) {
	if ( ! is_array( $value ) || empty( $value ) ) {
		return is_array( $value ) ? $value : array();
	}

	// Already associative — pass through unchanged.
	if ( array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) {
		return $value;
	}

	// Sequential list of enabled keys — flatten to { key => 1 } map.
	$map = array();
	foreach ( $value as $enabled_key ) {
		if ( is_string( $enabled_key ) || is_numeric( $enabled_key ) ) {
			$map[ (string) $enabled_key ] = 1;
		}
	}
	return $map;
}

/**
 * Normalize `bb_rl_side_menu` to the canonical map-of-items shape.
 *
 * Legacy onboarding stored this as a sequential list of items —
 * `[ { id: 'activity_feed', enabled: true, order: 0, icon: 'pulse' }, ... ]`.
 * Settings 2.0's `SortableToggleList` component expects a map keyed by id —
 * `{ activity_feed: { enabled, order, icon }, ... }`. Pass-through when the
 * value is already map-shaped.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value Stored value (either shape).
 * @return array Map-of-items shape.
 */
function bb_appearance_normalize_side_menu_shape( $value ) {
	if ( ! is_array( $value ) || empty( $value ) ) {
		return is_array( $value ) ? $value : array();
	}

	// Sequential list of item objects — each row has an `id` key. Flip to map.
	if (
		array_keys( $value ) === range( 0, count( $value ) - 1 ) &&
		isset( $value[0] ) &&
		is_array( $value[0] ) &&
		isset( $value[0]['id'] )
	) {
		$map = array();
		foreach ( $value as $index => $item ) {
			if ( ! is_array( $item ) || empty( $item['id'] ) ) {
				continue;
			}
			$map[ (string) $item['id'] ] = array(
				'enabled' => isset( $item['enabled'] ) ? (bool) $item['enabled'] : true,
				'order'   => isset( $item['order'] ) ? (int) $item['order'] : (int) $index,
				'icon'    => isset( $item['icon'] ) ? (string) $item['icon'] : '',
			);
		}
		return $map;
	}

	// Already map-shaped — pass through unchanged.
	return $value;
}
