<?php
/**
 * BuddyBoss Admin Settings — Appearance Feature Admin Callbacks.
 *
 * Admin-only callbacks for the Appearance feature:
 *   - `bb_appearance_apply_configuration()` — shared side-effects (component
 *     activation, registration enablement, schema upgrade) called from both
 *     admin save and onboarding wizard completion.
 *   - `bb_appearance_on_settings_saved()` — hook on `bb_admin_save_feature_settings_after`.
 *   - `bb_appearance_normalize_field_data()` — hook on `bb_admin_settings_format_field_data`.
 *
 * Pure sanitize / shape-normalize helpers live in
 * `includes/sanitizers.php` — loaded unconditionally so the version-update
 * migration and any non-admin consumer can call them without relying on the
 * admin-context gate.
 *
 * @package BuddyBoss\Features\Community\Appearance
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Seed default widget shapes for the three ReadyLaunch right-sidebar options.
 *
 * Writes `bb_rl_activity_sidebars`, `bb_rl_member_profile_sidebars`, and
 * `bb_rl_groups_sidebars` to `wp_options` only when each is missing. Existing
 * admin choices are preserved.
 *
 * Defaults and condition logic match the legacy
 * `bb_rest_readylaunch_platform_settings()` seed exactly. That legacy seed
 * fired on the `bp_rest_platform_settings` filter — i.e. when the legacy
 * admin React app fetched its settings via REST. The new-code analog is the
 * `bb_admin_settings_format_field_data` filter (see
 * `bb_appearance_normalize_field_data()` below), which fires when the
 * Settings 2.0 React app fetches per-field settings via the
 * `bb_admin_get_feature_settings` AJAX handler. Both fire only on admin
 * settings load — not on the frontend, not on every request — preserving
 * the legacy "seed once when the admin first opens settings" behaviour.
 *
 * Member-profile defaults are conditional on the friends and activity-follow
 * features being active, mirroring the legacy seed (no point seeding
 * `connections` if the friends component is disabled — the widget would
 * short-circuit at render time anyway).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_appearance_seed_default_sidebars() {
	if ( false === bp_get_option( 'bb_rl_activity_sidebars', false ) ) {
		bp_update_option(
			'bb_rl_activity_sidebars',
			array(
				'complete_profile'  => true,
				'latest_updates'    => true,
				'recent_blog_posts' => true,
				'active_members'    => true,
			)
		);
	}

	if ( false === bp_get_option( 'bb_rl_member_profile_sidebars', false ) ) {
		$member_sidebar = array( 'complete_profile' => true );
		if ( bp_is_active( 'friends' ) ) {
			$member_sidebar['connections'] = true;
		}
		if ( bp_is_active( 'activity' ) && function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active() ) {
			$member_sidebar['my_network'] = true;
		}
		bp_update_option( 'bb_rl_member_profile_sidebars', $member_sidebar );
	}

	if ( false === bp_get_option( 'bb_rl_groups_sidebars', false ) ) {
		bp_update_option(
			'bb_rl_groups_sidebars',
			array(
				'about_group'   => true,
				'group_members' => true,
			)
		);
	}
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

	// Seed default widget shapes for the three RL sidebar options when
	// ReadyLaunch is enabled and the option does not yet exist in `wp_options`.
	//
	// Why this is needed: the React Settings 2.0 Appearance panel renders the
	// per-widget toggles as visually-on by default when the user flips
	// Layout → ReadyLaunch, but the auto-save payload only includes fields the
	// user actually interacts with. So flipping just the layout switch persists
	// `bb_rl_enabled = true` without ever writing the three sidebar option
	// arrays. The frontend `right-sidebar.php` template then reads each option
	// as `array()` (the `bp_get_option` default), every `! empty()` widget
	// guard fails, and the right sidebar renders nothing.
	//
	// Legacy parity: `bb_rest_readylaunch_platform_settings()` performed the
	// same `false === bp_get_option(...)` seed on first read of the legacy
	// admin REST endpoint. That endpoint and its seed were removed in the
	// Settings 2.0 migration; this restores the behaviour at save time where
	// the React UI now drives ReadyLaunch enablement.
	//
	// Idempotent: each option is written exactly once, only when it is missing.
	// Existing per-widget choices made by the admin are never clobbered because
	// the `false === bp_get_option(...)` guard fails the moment the option
	// exists at all (even if all widgets are toggled off and the array is
	// empty — that is still an explicit user choice and we leave it alone).
	//
	// We read `bb_is_readylaunch_enabled()` from the DB rather than relying on
	// `$settings['bb_rl_enabled']` because partial saves (e.g., toggling only a
	// side-menu item) don't include the layout flag in their payload. Existing
	// sites that flipped layouts before this seed shipped will self-heal on
	// the next Appearance save without requiring the admin to re-flip layouts.
	if ( function_exists( 'bb_is_readylaunch_enabled' ) && bb_is_readylaunch_enabled() ) {
		bb_appearance_seed_default_sidebars();
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
			// Without rollback, a half-written state leaves Settings 2.0 React
			// (reads bb-active-features) and legacy bp_is_active() (reads
			// bp-active-components) permanently disagreeing until the next save.
			$components_before = $active_components;
			foreach ( $newly_activated as $component_key ) {
				unset( $components_before[ $component_key ] );
			}
			$features_before = bp_get_option( 'bb-active-features', array() );
			$features_before = is_array( $features_before ) ? $features_before : array();

			// Treat each write independently. bp_update_option returns false in
			// two distinct cases: real write failure, or value-unchanged no-op.
			// Distinguish via a follow-up read against the intended value.
			// !== is safe here — both arrays are built from the same iteration
			// path with the same key order, so PHP's strict comparison matches
			// the serialized-comparison update_option performs internally.
			$components_changed = ( $components_before !== $active_components );
			$features_changed   = ( $features_before !== $active_features );

			$components_ok = ! $components_changed
				|| bp_update_option( 'bp-active-components', $active_components )
				|| bp_get_option( 'bp-active-components', array() ) === $active_components;
			$features_ok   = ! $features_changed
				|| bp_update_option( 'bb-active-features', $active_features )
				|| bp_get_option( 'bb-active-features', array() ) === $active_features;

			if ( $components_ok && ! $features_ok ) {
				// Features write failed — roll back the components write so the
				// two options stay in sync.
				bp_update_option( 'bp-active-components', $components_before );
			} elseif ( ! $components_ok && $features_ok ) {
				// Components write failed but features wrote — roll back the
				// features write to match. (Both options share the alloptions
				// backend so single-side failure is rare in practice, but the
				// symmetry keeps invariants honest.)
				bp_update_option( 'bb-active-features', $features_before );
			}
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
	//
	// On multisite we switch to the BuddyPress root blog before writing so a
	// network admin editing community settings from a sub-site admin updates
	// the community's site title, not the sub-site's. Falls back gracefully
	// on single-site where `switch_to_blog()` / `restore_current_blog()`
	// become no-ops.
	if ( isset( $settings['blogname'] ) && current_user_can( 'manage_options' ) ) {
		$new_blogname = sanitize_text_field( (string) $settings['blogname'] );
		if ( '' !== $new_blogname ) {
			$switched = false;
			if ( is_multisite() && function_exists( 'bp_get_root_blog_id' ) ) {
				$root_blog_id = bp_get_root_blog_id();
				if ( $root_blog_id && get_current_blog_id() !== $root_blog_id ) {
					switch_to_blog( $root_blog_id );
					$switched = true;
				}
			}
			// Re-check capability POST-switch — `current_user_can()` evaluates
			// against the CURRENT blog, so the pre-switch check confirmed the
			// caller could manage the sub-site they came from but says nothing
			// about their authority on the root blog. Network admins pass both;
			// a sub-site admin is blocked here, which is correct.
			if ( current_user_can( 'manage_options' ) ) {
				update_option( 'blogname', $new_blogname );
			}
			if ( $switched ) {
				restore_current_blog();
			}
		}
	}

	bb_appearance_apply_configuration( is_array( $saved ) ? $saved : array() );
}
add_action( 'bb_admin_save_feature_settings_after', 'bb_appearance_on_settings_saved', 10, 3 );

/**
 * AJAX handler — activate the BuddyBoss Theme from the Appearance welcome
 * banner's "Activate Theme" button.
 *
 * Standard WP theme-switch flow without a page navigation: verifies the
 * settings nonce, the `switch_themes` capability, and the theme's
 * install/health state, then delegates to core `switch_theme()` which fires
 * the canonical `switch_theme` / `after_switch_theme` actions plus updates
 * the `template` and `stylesheet` options.
 *
 * Output is buffered around the core call because hooks on `switch_theme`
 * (older plugins, custom theme setup callbacks) occasionally echo, which
 * would corrupt the JSON response. The buffer cleanup runs in a `finally`
 * so a `Throwable` from a hook still leaves the buffer balanced — note
 * this only catches stray echo, not hook fatals (a real fatal still kills
 * the request before the JSON body is written; `wp_send_json_*` does its
 * own buffering for that case).
 *
 * Error responses use HTTP 200 + `success: false` (not `wp_send_json_error`
 * with explicit 4xx/5xx status codes) so the structured payload —
 * including the stable `error_code` — flows through `ajaxFetch`'s `.then`
 * branch on the React side. Sending 403/404/500 would route the same data
 * through the shared `ajaxFetch` error wrapper which only re-throws
 * `body.data.message`, dropping `error_code`. Nonce failure still
 * auto-403s via `check_ajax_referer`'s internal `wp_die`, but that's a
 * WP-contract case the caller already handles in its `.catch`.
 *
 * Returns `success: true` even when the theme is already active so the
 * React side can converge on the same end-state regardless of which
 * admin tab activated the theme.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void Sends JSON and exits.
 */
function bb_appearance_ajax_activate_buddyboss_theme() {
	// Same nonce the rest of the Settings 2.0 AJAX surface uses.
	check_ajax_referer( 'bb_admin_settings', 'nonce' );

	if ( ! current_user_can( 'switch_themes' ) ) {
		wp_send_json_error(
			array(
				'error_code' => 'cannot_switch_themes',
				'message'    => __( 'You do not have permission to switch themes.', 'buddyboss' ),
			)
		);
	}

	$stylesheet = 'buddyboss-theme';
	$theme      = wp_get_theme( $stylesheet );

	if ( ! $theme->exists() ) {
		wp_send_json_error(
			array(
				'error_code' => 'not_installed',
				'message'    => __( 'BuddyBoss Theme is not installed.', 'buddyboss' ),
			)
		);
	}

	// `errors()` catches missing style.css, broken parent reference, etc. —
	// cases where the theme directory exists but switch_theme() would land
	// the site on a broken front-end.
	$theme_errors = $theme->errors();
	if ( $theme_errors ) {
		wp_send_json_error(
			array(
				'error_code' => 'theme_errors',
				'message'    => sprintf(
					/* translators: %s: theme error message. */
					__( 'BuddyBoss Theme cannot be activated: %s', 'buddyboss' ),
					$theme_errors->get_error_message()
				),
			)
		);
	}

	if ( $stylesheet === get_template() ) {
		wp_send_json_success(
			array(
				'active_theme'   => $stylesheet,
				'already_active' => true,
				'message'        => __( 'BuddyBoss Theme is already active.', 'buddyboss' ),
			)
		);
	}

	ob_start();
	try {
		switch_theme( $stylesheet );
	} finally {
		ob_end_clean();
	}

	wp_send_json_success(
		array(
			'active_theme'   => $stylesheet,
			'already_active' => false,
			'message'        => __( 'BuddyBoss Theme activated.', 'buddyboss' ),
		)
	);
}
add_action( 'wp_ajax_bb_admin_activate_buddyboss_theme', 'bb_appearance_ajax_activate_buddyboss_theme' );

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
 * migration (`bb_rl_migrate_settings()`) cleans any legacy sequential data in
 * the DB, so read-side normalization is no longer needed.
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

	$name = $field['name'] ?? '';

	if ( 'bb_rl_enabled' === $name ) {
		$field_data['value']   = ! empty( $field_data['value'] ) && '0' !== $field_data['value'] ? '1' : '0';
		$field_data['default'] = ! empty( $field_data['default'] ) && '0' !== $field_data['default'] ? '1' : '0';
	}

	// Inject inline frontend links into the Template Sidebar Widgets copy at
	// AJAX format time. At field-registration time (`bb_register_features` →
	// `bp_loaded@5`) the BP component globals that compute these URLs —
	// `$bp->members->slug`, `$bp->groups->table_name`, `$bp->loggedin_user->id`
	// — are not yet populated, so a URL built there would be malformed (e.g.
	// `https://site//user/`) or trigger a DB error. This filter runs inside the
	// AJAX handler where every component has finished `setup_globals()`.
	$sidebar_fields = array( 'bb_rl_activity_sidebars', 'bb_rl_member_profile_sidebars', 'bb_rl_groups_sidebars' );
	if ( in_array( $name, $sidebar_fields, true ) ) {
		// Legacy parity seed (`bb_rest_readylaunch_platform_settings`): when
		// the admin first opens the Appearance settings panel and any of the
		// three sidebar options is missing from `wp_options`, write its default
		// shape and refresh the `$field_data['value']` so the React UI shows
		// the freshly-seeded toggles as enabled instead of empty. Mirrors the
		// legacy behaviour where `bp_rest_platform_settings` did both the seed
		// and the $settings mutation in a single pass; new code uses the
		// per-field `bb_admin_settings_format_field_data` filter as the
		// symmetric hook.
		if ( false === bp_get_option( $name, false ) ) {
			bb_appearance_seed_default_sidebars();
			$seeded_value = bp_get_option( $name, array() );
			if ( ! empty( $seeded_value ) ) {
				$field_data['value'] = $seeded_value;
			}
		}

		$rebuilt = bb_appearance_build_sidebar_description( $name );
		if ( '' !== $rebuilt ) {
			$field_data['label_description'] = $rebuilt;
		}
	}

	return $field_data;
}
add_filter( 'bb_admin_settings_format_field_data', 'bb_appearance_normalize_field_data', 10, 3 );

/**
 * Resolve a frontend permalink for one of the three sidebar-widget fields.
 *
 * Split out so `bb_appearance_build_sidebar_description()` reads as pure
 * templating. Returns '' when the component is inactive or the viewer has
 * nothing to link to — caller treats that as "keep the unlinked copy".
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $field_name      Sidebar-widget field slug.
 * @param int    $current_user_id Logged-in user ID (WP-core value — BP's
 *                                `loggedin_user` global may not be set yet).
 * @return string Frontend URL, or '' when one couldn't be resolved.
 */
function bb_appearance_resolve_sidebar_url( $field_name, $current_user_id ) {
	switch ( $field_name ) {
		case 'bb_rl_activity_sidebars':
			if ( ! bp_is_active( 'activity' ) ) {
				return '';
			}
			// Prefer the canonical page permalink — avoids a 301 hop when the
			// activity directory page is also the site's `page_on_front`.
			$page_id = function_exists( 'bp_core_get_directory_page_id' ) ? bp_core_get_directory_page_id( 'activity' ) : 0;
			$url     = $page_id ? get_permalink( $page_id ) : '';
			return $url ? $url : ( function_exists( 'bp_get_activity_directory_permalink' ) ? bp_get_activity_directory_permalink() : '' );

		case 'bb_rl_member_profile_sidebars':
			if ( $current_user_id && function_exists( 'bp_core_get_user_domain' ) ) {
				$url = bp_core_get_user_domain( $current_user_id );
				if ( ! empty( $url ) ) {
					return $url;
				}
			}
			return function_exists( 'bp_get_members_directory_permalink' ) ? bp_get_members_directory_permalink() : '';

		case 'bb_rl_groups_sidebars':
			if ( ! bp_is_active( 'groups' ) ) {
				return '';
			}
			if ( $current_user_id && function_exists( 'groups_get_groups' ) && function_exists( 'bp_get_group_permalink' ) ) {
				$found = groups_get_groups(
					array(
						'user_id'  => $current_user_id,
						'type'     => 'active',
						'per_page' => 1,
					)
				);
				if ( ! empty( $found['groups'][0] ) ) {
					return bp_get_group_permalink( $found['groups'][0] );
				}
			}
			return function_exists( 'bp_get_groups_directory_permalink' ) ? bp_get_groups_directory_permalink() : '';
	}

	return '';
}

/**
 * Get the translatable sprintf template for one of the three sidebar-widget
 * fields.
 *
 * Single source of truth for the copy — used by both the field registration
 * (with empty anchor placeholders → plain text) and the AJAX-time linked
 * rendering (with real `<a>` tags). Keeping the template in one place stops
 * the .pot extractor from collecting two near-identical strings per field.
 *
 * The `%1$s` / `%2$s` placeholders wrap the inline link text. Passing empty
 * strings for them yields `'Enable or disable widgets to appear on the
 * activity feed.'`; passing `<a href="...">` / `</a>` yields the linked form.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $field_name One of `bb_rl_activity_sidebars`,
 *                           `bb_rl_member_profile_sidebars`,
 *                           `bb_rl_groups_sidebars`.
 * @return string Sprintf template, or '' for an unknown field name.
 */
function bb_appearance_get_sidebar_description_template( $field_name ) {
	switch ( $field_name ) {
		case 'bb_rl_activity_sidebars':
			/* translators: %1$s: opening anchor tag, %2$s: closing anchor tag. */
			return __( 'Enable or disable widgets to appear on the %1$sactivity feed%2$s.', 'buddyboss' );

		case 'bb_rl_member_profile_sidebars':
			/* translators: %1$s: opening anchor tag, %2$s: closing anchor tag. */
			return __( 'Enable or disable widgets to appear on the %1$smember profile%2$s.', 'buddyboss' );

		case 'bb_rl_groups_sidebars':
			/* translators: %1$s: opening anchor tag, %2$s: closing anchor tag. */
			return __( 'Enable or disable widgets to appear on the %1$sgroup single%2$s page.', 'buddyboss' );
	}

	return '';
}

/**
 * Render the sidebar-widget description with optional inline link.
 *
 * Passing empty strings for `$open` / `$close` produces the plain unlinked
 * copy used at field-registration time. Passing real `<a>` tags produces the
 * linked copy injected at AJAX format time. Returns '' when `$field_name` is
 * unknown so callers can fall through cleanly.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $field_name Sidebar-widget field slug.
 * @param string $open       Opening anchor tag (or '' for plain copy).
 * @param string $close      Closing anchor tag (or '' for plain copy).
 * @return string Finished description HTML / plain copy, or ''.
 */
function bb_appearance_render_sidebar_description( $field_name, $open = '', $close = '' ) {
	$template = bb_appearance_get_sidebar_description_template( $field_name );
	return '' === $template ? '' : sprintf( $template, $open, $close );
}

/**
 * Build the linked `label_description` for a sidebar-widget field, or '' to
 * keep the registration-time plain copy.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $field_name Sidebar-widget field slug.
 * @return string Sprintf'd HTML with `<a>` link, or ''.
 */
function bb_appearance_build_sidebar_description( $field_name ) {
	$url = bb_appearance_resolve_sidebar_url( $field_name, get_current_user_id() );
	if ( '' === $url ) {
		return '';
	}

	return bb_appearance_render_sidebar_description(
		$field_name,
		'<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">',
		'</a>'
	);
}

/**
 * Coerce `bb_rl_enabled` in the save-response payload to the dropdown string shape.
 *
 * `bb_appearance_sanitize_layout()` deliberately stores `bb_rl_enabled` as a PHP
 * boolean so legacy `bb_is_readylaunch_enabled()` consumers keep working. The
 * generic save handler echoes the sanitized value back in `response.saved`,
 * which the React form then merges into its state. A raw boolean `false` never
 * matches the SelectControl's string options (`'1'` / `'0'`) under strict
 * equality, so the dropdown silently falls back to the first option
 * (ReadyLaunch) — the user sees their WordPress-Theme choice revert visually
 * even though the database is correct.
 *
 * This mirrors the display-side `bb_appearance_normalize_field_data()` filter
 * but runs on the save-response path, keeping the stored shape (bool) and the
 * wire shape (string) consistent on both read and write.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $response_data Response payload returned by the save AJAX handler.
 * @param string $feature_id    Feature being saved.
 * @param array  $settings      Raw submitted settings (unused).
 * @param array  $saved         Sanitized values written to options (unused — we read from $response_data).
 * @return array Response with `saved.bb_rl_enabled` coerced to `'1'` / `'0'`.
 */
function bb_appearance_normalize_save_response( $response_data, $feature_id, $settings, $saved ) {
	if ( 'appearance' !== $feature_id ) {
		return $response_data;
	}

	if ( isset( $response_data['saved'] ) && is_array( $response_data['saved'] ) && array_key_exists( 'bb_rl_enabled', $response_data['saved'] ) ) {
		$response_data['saved']['bb_rl_enabled'] = ! empty( $response_data['saved']['bb_rl_enabled'] ) ? '1' : '0';
	}

	return $response_data;
}
add_filter( 'bb_admin_save_feature_settings_response', 'bb_appearance_normalize_save_response', 10, 4 );
