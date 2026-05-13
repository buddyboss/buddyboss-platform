<?php
/**
 * Legacy Meta-Box Bridge for the Groups edit modal.
 *
 * Auto-detects third-party plugins that register meta boxes on the legacy
 * groups admin screen (via BP_Group_Extension or plain add_meta_box()) and
 * surfaces their fields as native typed fields in the Settings 2.0 React
 * edit modal — without requiring the third-party plugin to migrate.
 *
 * Strategy:
 *   1. Hook bb_register_groups_meta_fields late (priority 999) — by then,
 *      bp_groups_admin_meta_boxes has already fired and $wp_meta_boxes
 *      contains every registered metabox.
 *   2. Walk $wp_meta_boxes; skip core/known/already-migrated boxes; capture
 *      each remaining box's HTML output via output buffering.
 *   3. Parse with DOMDocument; for every <input>/<select>/<textarea> with a
 *      safe name, register a typed field via bb_register_admin_meta_field().
 *      RegisteredMetaField.js renders it natively.
 *   4. On save, save_value puts each value back on $_POST so the third-party
 *      plugin's own save handler reads its expected POST keys. For
 *      BP_Group_Extension subclasses we also invoke settings_screen_save()
 *      manually (Platform deliberately removed that auto-call).
 *
 * Known limitations (documented; not bugs):
 *   - **Array-notation field names** (e.g. `name="meta[foo]"` /
 *     `name="things[]"`) are silently rejected. Bridging arrays correctly
 *     requires reassembly logic that's worse than telling the plugin author
 *     to migrate. Affected plugins must use `BB_Admin_Meta_Field_Registry`
 *     with `sanitize_callback` to preserve the array shape.
 *   - **Non-ASCII identifier names** are silently rejected (the regex
 *     allowlist requires `^[A-Za-z][A-Za-z0-9_\-]*$`). WordPress conventions
 *     don't use non-ASCII for input names, so this is safe in practice.
 *   - **JS-driven widgets**: a metabox's color picker, datepicker, media
 *     uploader, jQuery-UI autocomplete etc. requires the corresponding
 *     script/style enqueued on the React admin page. Common cases are
 *     auto-detected via HTML class markers and enqueued by
 *     `bb_legacy_groups_auto_enqueue_widget_scripts()` (see below). Plugin
 *     authors with custom JS deps can extend the marker→handle map via the
 *     `bb_legacy_meta_box_extra_scripts` filter, or enqueue their scripts
 *     directly on `admin_enqueue_scripts` for the `bb-settings` screen.
 *     Note: marker detection captures HTML with `null` group context (no
 *     specific group ID), since the page-load enqueue runs before the user
 *     picks a group to edit. A metabox that conditionally renders its
 *     widget markup ONLY when editing an existing group (e.g., gates the
 *     UI behind `if ( ! $group_id ) return;`) will be missed by
 *     auto-enqueue. The widget input still renders in the React modal,
 *     but its initialization JS won't be loaded. Workaround: plugin
 *     authors enqueue their scripts directly on `admin_enqueue_scripts`
 *     for the `bb-settings` screen, OR render their widget markers
 *     unconditionally so the page-load capture detects them.
 *   - **Custom AJAX submit endpoints**: plugins that bypass
 *     `bp_group_admin_edit_after` (post directly to their own AJAX handler)
 *     are not bridged on save. Migrate to the new registry's `save_value`
 *     callback for full Settings 2.0 support.
 *   - **File uploads** (`<input type="file">`): not supported. The Settings
 *     2.0 AJAX flow uses JSON, not multipart/form-data.
 *   - **Role-conditional / capability-conditional UI**: the parsed-structure
 *     transient cache is keyed by metabox ID + Platform version, NOT by
 *     viewer capabilities. If a metabox renders different inputs depending
 *     on the current user's role (e.g., a "super admin only" toggle hidden
 *     for editors), the first admin to load the page wins — the parsed
 *     structure cached then is served to every subsequent admin until the
 *     transient expires (1 hour) or a plugin lifecycle event clears it.
 *     The actual *values* are still per-group (rendered through the
 *     `get_value` closure on every request). Workaround: plugins that need
 *     role-conditional inputs should migrate to `BB_Admin_Meta_Field_Registry`
 *     and gate fields via `BB_Feature_Loader` / `current_user_can()` checks
 *     in their registration callback.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss 3.0.0
 */

defined( 'ABSPATH' ) || exit;

// Shared parser, capture-safety, and sanitize-resolver helpers used by every
// component bridge. require_once is idempotent across require sites.
require_once dirname( __DIR__ ) . '/legacy-meta-bridge-utils.php';

/**
 * Per-request state container.
 *
 * Holds:
 *   - bridged_slugs[]: metabox IDs the bridge surfaced fields for, used to
 *                      avoid double-saving in the extension save loop.
 *   - html_cache[]:    captured metabox HTML keyed by box_id+group_id
 *                      (avoids re-running third-party callbacks N+1 times).
 *
 * Note: parsed-DOMXPath caching used to live here too — it now lives inside
 * `bb_legacy_get_xpath()` (in the shared utils file) as a module-level
 * static, so all component bridges share one parse per HTML hash without
 * having to coordinate state.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return array Reference to mutable state array.
 */
function &bb_legacy_groups_bridge_state() {
	static $state = null;
	if ( null === $state ) {
		$state = array(
			'bridged_slugs' => array(),
			'html_cache'    => array(),
		);
	}
	return $state;
}

/**
 * Register bridge fields late, after core/Pro fields have registered.
 *
 * Defensive guards:
 *   - Capability check (component AJAX handler runs as authenticated admin
 *     in normal flow; this is belt-and-suspenders for any other entry path).
 *   - Recursion guard (prevents infinite loop if a captured callback itself
 *     triggers bb_register_groups_meta_fields).
 *
 * @since BuddyBoss 3.0.0
 *
 * @param BB_Admin_Meta_Field_Registry $registry  Registry instance.
 * @param string                       $component Component identifier ('groups').
 */
function bb_legacy_groups_meta_bridge_register( $registry, $component ) {
	if ( 'groups' !== $component ) {
		return;
	}

	// Recursion guard — bail out if a captured callback re-fires our hook.
	static $in_bridge = false;
	if ( $in_bridge ) {
		return;
	}

	// Capability check — defensive. Normal flow goes through the React groups
	// AJAX handler which already verifies manage_options; this is here in case
	// the action ever fires from REST/CLI/other context.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$in_bridge = true;
	try {
		bb_legacy_groups_meta_bridge_register_inner( $registry, $component );
	} finally {
		$in_bridge = false;
	}
}
add_action( 'bb_register_groups_meta_fields', 'bb_legacy_groups_meta_bridge_register', 999, 2 );

/**
 * Inner implementation, separated so the recursion guard wrapping is clean.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param BB_Admin_Meta_Field_Registry $registry  Registry instance.
 * @param string                       $component Component identifier.
 */
function bb_legacy_groups_meta_bridge_register_inner( $registry, $component ) {
	global $wp_meta_boxes;

	// Self-bootstrap. The React group GET handler (bb_admin_get_group) fires
	// bp_groups_admin_meta_boxes itself, so by the time this hook runs in
	// the GET path $wp_meta_boxes is already populated. The SAVE handler
	// (bb_admin_save_group) does NOT — without this guard the bridge would
	// silently bail on every save and third-party metabox values would
	// never flow through the bb_admin_after_save_group → settings_screen_save
	// pipeline. Fire the action here on first miss so registration is
	// idempotent across both AJAX paths. Output is buffered because some
	// legacy metaboxes echo CSS/JS at registration time.
	if ( did_action( 'bp_groups_admin_meta_boxes' ) === 0 ) {
		$had_screen = ( function_exists( 'get_current_screen' ) && null !== get_current_screen() );
		if ( ! $had_screen && function_exists( 'set_current_screen' ) ) {
			set_current_screen( 'toplevel_page_bp-groups' );
		}
		ob_start();
		try {
			do_action( 'bp_groups_admin_meta_boxes' );
		} catch ( Throwable $e ) {
			// Old extensions may fatal in AJAX context — swallow so save can continue.
			unset( $e );
		}
		ob_end_clean();
	}

	// Multi-candidate screen ID detection — the BP groups admin screen is
	// keyed by the menu hook name, which can vary if Platform / Pro / a
	// customer plugin reorganizes the admin menu.
	$screen = null;
	foreach ( (array) $wp_meta_boxes as $screen_id => $_ignored ) {
		if ( is_string( $screen_id ) && false !== stripos( $screen_id, 'bp-groups' ) ) {
			$screen = $screen_id;
			break;
		}
	}
	if ( null === $screen ) {
		return;
	}

	/**
	 * Filter the list of metabox IDs the legacy bridge should skip when
	 * scanning $wp_meta_boxes for the groups screen. Boxes whose IDs are in
	 * this list will not be surfaced as React fields — useful when Platform
	 * or Pro already registers the same UI through the canonical registry.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string[] $skip_box_ids Metabox IDs to skip.
	 */
	$skip_box_ids = (array) apply_filters(
		'bb_legacy_meta_box_bridge_skip_groups',
		array(
			'submitdiv',
			'bp_group_settings',
			'bp_group_members',
			'bp_group_type',
		)
	);

	// Auto-skip any box whose slug matches a tab already registered by core /
	// Pro. Catches BP_Group_Extension subclasses that have been migrated to
	// BB_Admin_Meta_Field_Registry (e.g., Pro Topics with slug='topics' and
	// tab='topics'). Without this, the bridge would surface a duplicate UI.
	$existing_tabs = array();
	foreach ( $registry->get_fields( $component ) as $field_id => $field ) {
		if ( ! empty( $field['tab'] ) ) {
			$existing_tabs[ $field['tab'] ] = true;
		}
	}

	$state = &bb_legacy_groups_bridge_state();
	$order = 5000;

	// Cast to array — `$wp_meta_boxes[ $screen ]` is normally a nested-array
	// structure populated by core's add_meta_box(), but defensive against a
	// third-party plugin that directly assigns a non-array to the screen key
	// (would TypeError on PHP 8.0+ otherwise). Mirrors the activity bridge
	// pattern at `bb_legacy_activity_meta_bridge_register_inner()`.
	foreach ( (array) $wp_meta_boxes[ $screen ] as $context => $priorities ) {
		foreach ( (array) $priorities as $boxes ) {
			foreach ( (array) $boxes as $box_id => $box ) {
				if ( ! is_array( $box ) || in_array( $box_id, $skip_box_ids, true ) || empty( $box['callback'] ) ) {
					continue;
				}
				if ( isset( $existing_tabs[ $box_id ] ) ) {
					continue;
				}

				bb_legacy_groups_bridge_box( $registry, $component, $box, $order );

				// Track this slug so the save loop knows we surfaced its fields
				// (used to avoid double-saving when bp_group_admin_edit_after
				// also calls into the same plugin).
				$state['bridged_slugs'][ $box_id ] = true;
			}
		}
	}
}

/**
 * Capture, parse, and register one metabox's inputs.
 *
 * Field-structure (names/types/labels/descriptions) is cached in a transient
 * keyed by box ID for HOUR_IN_SECONDS. Live values still come from the
 * registered get_value/save_value callbacks — only the parse step is cached.
 * Plugin lifecycle hooks invalidate the cache.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param BB_Admin_Meta_Field_Registry $registry  Registry instance.
 * @param string                       $component Component identifier.
 * @param array                        $box       Metabox descriptor from $wp_meta_boxes.
 * @param int                          $order     Order counter (passed by reference).
 */
function bb_legacy_groups_bridge_box( $registry, $component, $box, &$order ) {
	// Version the cache key with BP_PLATFORM_VERSION so a Platform upgrade that
	// changes parser behaviour or input descriptors invalidates stale entries
	// instead of returning the previous version's parse.
	$version   = defined( 'BP_PLATFORM_VERSION' ) ? BP_PLATFORM_VERSION : '0';
	$cache_key = 'bb_legacy_box_inputs_' . md5( $box['id'] . '|' . $version );
	$inputs    = get_transient( $cache_key );

	if ( ! is_array( $inputs ) ) {
		$html = bb_legacy_capture_box_html( $box, null );
		if ( ! $html ) {
			// Cache the negative result briefly so we don't re-capture every
			// request when a box's render is empty / errors out.
			set_transient( $cache_key, array(), 5 * MINUTE_IN_SECONDS );
			return;
		}

		$inputs = bb_legacy_parse_box_inputs( $html );
		set_transient( $cache_key, $inputs, HOUR_IN_SECONDS );
	}

	if ( empty( $inputs ) ) {
		return;
	}

	foreach ( $inputs as $input ) {
		if ( in_array( $input['type'], array( 'file', 'hidden', 'submit', 'button' ), true ) ) {
			continue;
		}

		// Block dangerous $_POST keys at registration so they never become
		// editable bridge fields (see bb_legacy_is_safe_post_key()).
		if ( ! bb_legacy_is_safe_post_key( $input['name'] ) ) {
			continue;
		}

		$field_id = sanitize_key( 'legacy_' . $box['id'] . '_' . $input['name'] );

		/**
		 * Filter the React modal tab a bridged legacy field should appear under.
		 * Defaults to 'details'. Plugin authors can route their fields to other
		 * tabs (e.g. 'settings', 'members') based on the field name, owning box
		 * ID, or parsed input descriptor.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $tab        Tab slug — defaults to 'details'.
		 * @param string $input_name $_POST key the field writes to.
		 * @param string $box_id     Owning metabox ID.
		 * @param array  $input      Parsed input descriptor (name, type, label, etc.).
		 */
		$tab = (string) apply_filters(
			'bb_legacy_meta_field_tab',
			'details',
			$input['name'],
			$box['id'],
			$input
		);

		$raw_label       = $input['label'] ? $input['label'] : $box['title'];
		$raw_description = isset( $input['description'] ) ? $input['description'] : '';

		$sanitize_cb = bb_legacy_resolve_sanitize_callback( $input['type'] );

		$args = array(
			// Labels are surfaced as plain-text in the React modal, descriptions
			// allow the same inline HTML wp_kses_post() permits (links, em, etc.).
			'label'             => sanitize_text_field( $raw_label ),
			'description'       => wp_kses_post( $raw_description ),
			'type'              => $input['type'],
			'order'             => $order++,
			'tab'               => $tab,
			'context'           => 'after',
			'sanitize_callback' => $sanitize_cb,
			'get_value'         => bb_legacy_make_get_value( $box, $input['name'], $input['type'] ),
			'save_value'        => function ( $group, $value ) use ( $input ) {
				// Defense in depth: verify safety again at save time in case
				// the registration-time check was bypassed.
				if ( ! bb_legacy_is_safe_post_key( $input['name'] ) ) {
					return;
				}
				// Don't clobber a key that's already set — if React (or any
				// other Settings 2.0 field) populated $_POST first, that
				// value is authoritative. Bridge only writes when the key
				// is genuinely missing.
				//
				// Nonce: this closure runs inside BB_Admin_Settings_Ajax::save()
				// which has already verified the bb_admin_settings nonce + the
				// manage_options capability via bb_verify_request() before any
				// save_value callback fires.
				// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
				if ( isset( $_POST[ $input['name'] ] ) ) {
					return;
				}
				// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
				$_POST[ $input['name'] ] = $value;
			},
		);

		if ( 'select' === $input['type'] || 'toggle_list' === $input['type'] ) {
			$args['get_options'] = function ( $group ) use ( $box, $input ) {
				$html = bb_legacy_capture_box_html( $box, $group );
				return bb_legacy_extract_select_options( $html, $input['name'] );
			};
		} elseif ( 'radio' === $input['type'] ) {
			$args['get_options'] = function ( $group ) use ( $box, $input ) {
				$html = bb_legacy_capture_box_html( $box, $group );
				return bb_legacy_extract_radio_options( $html, $input['name'] );
			};
		}

		$registry->register( $component, $field_id, $args );
	}
}

/**
 * Capture metabox callback HTML safely.
 *
 * Per-request memoized by (box_id, group_id). Wraps the callback in a
 * temporary wp_die handler so a buggy callback that calls die()/wp_die()
 * doesn't kill the whole AJAX response. Restores $_GET['gid'] on every
 * exit path.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param array                $box   Metabox descriptor.
 * @param BP_Groups_Group|null $group Group object for re-render, or null.
 * @return string Captured HTML or empty string on error.
 */
function bb_legacy_capture_box_html( $box, $group ) {
	if ( empty( $box['callback'] ) || empty( $box['id'] ) ) {
		return '';
	}

	$state     = &bb_legacy_groups_bridge_state();
	$group_id  = ( is_object( $group ) && isset( $group->id ) ) ? (int) $group->id : 0;
	$cache_key = $box['id'] . '|' . $group_id;
	if ( isset( $state['html_cache'][ $cache_key ] ) ) {
		return $state['html_cache'][ $cache_key ];
	}

	// $_GET['gid'] is read/swapped here so legacy metabox callbacks that
	// depend on it (e.g., groups_get_current_group()) see the correct group
	// during HTML capture. Restored in the `finally` block below.
	//
	// Nonce: this function only runs from inside the bb-settings React shell
	// — registration-time captures fire on admin_enqueue_scripts (admin-only),
	// and value/render-time captures run inside BB_Admin_Settings_Ajax callbacks
	// after bb_verify_request() has validated the bb_admin_settings nonce + the
	// manage_options capability.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
	$original_gid = isset( $_GET['gid'] ) ? sanitize_text_field( wp_unslash( $_GET['gid'] ) ) : null;
	if ( $group_id ) {
		// Only swap in a real group id — a fresh BP_Groups_Group( $missing_id )
		// leaves the object unhydrated (empty name/slug). Skip the swap in
		// that case so legacy callbacks don't hit groups_get_current_group()
		// with a phantom id and emit notices.
		$is_hydrated = is_object( $group ) && ( ! empty( $group->name ) || ! empty( $group->slug ) );
		if ( $is_hydrated ) {
			$_GET['gid'] = $group_id;
		} else {
			$group_id = 0;
		}
	}

	$result = '';
	try {
		$result = bb_legacy_with_wp_die_safety(
			function () use ( $box, $group ) {
				ob_start();
				try {
						call_user_func( $box['callback'], $group, $box );
				} catch ( Throwable $e ) {
					ob_end_clean();
					return '';
				}
				return ob_get_clean();
			}
		);
	} catch ( Throwable $e ) {
		// Includes the synthetic wp_die exception.
		$result = '';
	} finally {
		if ( null === $original_gid ) {
			unset( $_GET['gid'] );
		} else {
			$_GET['gid'] = $original_gid;
		}
	}

	if ( strlen( $result ) > BB_LEGACY_BRIDGE_MAX_HTML ) {
		// Reject suspiciously large output — defense against billion-laughs
		// / quadratic-blowup payloads from malicious metabox HTML.
		$result = '';
	}

	$state['html_cache'][ $cache_key ] = $result;
	return $result;
}

/**
 * Build a get_value closure that re-renders the metabox with the real group
 * (cached) and extracts THIS input's current value.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param array  $box  Metabox descriptor.
 * @param string $name Input name attribute.
 * @param string $type Detected field type.
 * @return callable get_value callback.
 */
function bb_legacy_make_get_value( $box, $name, $type ) {
	return function ( $group ) use ( $box, $name, $type ) {
		$html = bb_legacy_capture_box_html( $box, $group );
		if ( ! $html ) {
			return ( 'checkbox' === $type ) ? '0' : '';
		}
		return bb_legacy_extract_input_value( $html, $name, $type );
	};
}

/**
 * After the React save populates $_POST via the registry, manually call each
 * registered BP_Group_Extension's settings_screen_save() — Platform
 * deliberately removed the auto-call (see class-bp-group-extension.php).
 *
 * Skips extensions whose slug WASN'T bridged (those rely on their own
 * registration via bb_register_groups_meta_fields and don't need this
 * fallback) — avoids double-saving when an extension is also reachable via
 * the canonical registry path.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param int             $group_id Saved group ID.
 * @param BP_Groups_Group $group    Saved group object (unused — signature
 *                                  imposed by the bb_admin_after_save_group
 *                                  action).
 */
function bb_legacy_groups_run_extension_saves( $group_id, $group ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed,Generic.CodeAnalysis.UnusedFunctionParameter.Found
	$state         = &bb_legacy_groups_bridge_state();
	$bridged_slugs = $state['bridged_slugs'];

	if ( empty( $bridged_slugs ) ) {
		return;
	}

	foreach ( bb_legacy_get_registered_group_extensions() as $extension ) {
		if ( ! method_exists( $extension, 'is_screen_enabled' ) || ! $extension->is_screen_enabled( 'admin' ) ) {
			continue;
		}

		// Only invoke saves for extensions whose metabox the bridge surfaced —
		// extensions whose fields came in via the canonical registry already
		// have their saves handled by save_fields_data().
		$slug = isset( $extension->slug ) ? $extension->slug : null;
		if ( ! $slug || ! isset( $bridged_slugs[ $slug ] ) ) {
			continue;
		}

		try {
			// Hydrate group_id inside the try in case the property is
			// declared protected/private (assignment would throw Error).
			if ( property_exists( $extension, 'group_id' ) ) {
				$extension->group_id = (int) $group_id;
			}

			$callback = isset( $extension->screens['admin']['screen_save_callback'] )
				? $extension->screens['admin']['screen_save_callback']
				: array( $extension, 'admin_screen_save' );
			if ( ! is_callable( $callback ) ) {
				$callback = array( $extension, 'settings_screen_save' );
			}
			if ( ! is_callable( $callback ) ) {
				continue;
			}

			bb_legacy_with_wp_die_safety(
				function () use ( $callback, $group_id ) {
					call_user_func( $callback, $group_id );
				}
			);
		} catch ( Throwable $e ) {
			// Don't let a buggy third-party extension break the React save.
			// Log only class + file:line — never the message (may contain
			// secrets from poorly-written third-party code).
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Gated behind WP_DEBUG; intentional debug-only log.
				error_log(
					'bb_legacy_groups: extension save threw '
					. get_class( $e ) . ' at '
					. $e->getFile() . ':' . $e->getLine()
				);
			}
		}
	}
}
add_action( 'bb_admin_after_save_group', 'bb_legacy_groups_run_extension_saves', 5, 2 );

/**
 * Discover registered BP_Group_Extension instances by scanning listeners on
 * the bp_groups_admin_meta_boxes action.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return BP_Group_Extension[] List of registered extension instances.
 */
function bb_legacy_get_registered_group_extensions() {
	global $wp_filter;
	$out = array();

	if ( empty( $wp_filter['bp_groups_admin_meta_boxes'] ) ) {
		return $out;
	}

	$hook      = $wp_filter['bp_groups_admin_meta_boxes'];
	$callbacks = isset( $hook->callbacks ) ? $hook->callbacks : array();

	foreach ( $callbacks as $cbs ) {
		foreach ( (array) $cbs as $cb ) {
			$fn = isset( $cb['function'] ) ? $cb['function'] : null;
			if (
				is_array( $fn )
				&& isset( $fn[0], $fn[1] )
				&& $fn[0] instanceof BP_Group_Extension
				&& '_meta_box_display_callback' === $fn[1]
			) {
				$out[] = $fn[0];
			}
		}
	}

	return $out;
}

/**
 * Clear all bb_legacy_box_inputs_* transients on plugin lifecycle events
 * — installing/activating/deactivating/upgrading any plugin invalidates
 * the parsed-structure cache so newly-registered metaboxes are picked up
 * on the next request.
 *
 * When an external object cache (Redis, Memcached, etc.) is in use the
 * options table doesn't hold the transients, so a SQL DELETE is a no-op.
 * Fall back to enumerating known box IDs from $wp_meta_boxes and deleting
 * each transient individually, which routes through the object cache.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_legacy_groups_clear_bridge_cache() {
	if ( wp_using_ext_object_cache() ) {
		// Object-cache path: iterate known box IDs and delete per key.
		global $wp_meta_boxes;
		$version = defined( 'BP_PLATFORM_VERSION' ) ? BP_PLATFORM_VERSION : '0';
		$screens = isset( $wp_meta_boxes ) && is_array( $wp_meta_boxes ) ? $wp_meta_boxes : array();
		foreach ( $screens as $contexts ) {
			if ( ! is_array( $contexts ) ) {
				continue;
			}
			foreach ( $contexts as $priorities ) {
				if ( ! is_array( $priorities ) ) {
					continue;
				}
				foreach ( $priorities as $boxes ) {
					if ( ! is_array( $boxes ) ) {
						continue;
					}
					foreach ( $boxes as $box ) {
						if ( ! is_array( $box ) || empty( $box['id'] ) ) {
							continue;
						}
						delete_transient( 'bb_legacy_box_inputs_' . md5( $box['id'] . '|' . $version ) );
					}
				}
			}
		}
		return;
	}

	global $wpdb;
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query(
		"DELETE FROM {$wpdb->options}
		 WHERE option_name LIKE '_transient_bb_legacy_box_inputs_%'
		    OR option_name LIKE '_transient_timeout_bb_legacy_box_inputs_%'"
	);
	// phpcs:enable
}
add_action( 'activated_plugin', 'bb_legacy_groups_clear_bridge_cache' );
add_action( 'deactivated_plugin', 'bb_legacy_groups_clear_bridge_cache' );
add_action( 'upgrader_process_complete', 'bb_legacy_groups_clear_bridge_cache' );
add_action( 'switch_theme', 'bb_legacy_groups_clear_bridge_cache' );

/**
 * Auto-enqueue scripts that legacy metaboxes commonly depend on, so widgets
 * (color picker, media uploader, TinyMCE) work in the React modal.
 *
 * Runs on the bb-settings admin page enqueue hook. Walks $wp_meta_boxes
 * (populated by bp_groups_admin_meta_boxes which fires once per page load),
 * scans each captured box's HTML for known JS dependency markers, and
 * enqueues the matching core scripts/styles.
 *
 * Plugin authors with custom JS deps can hook the bb_legacy_meta_box_extra_scripts
 * filter to register their own marker → enqueue mapping.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_legacy_groups_auto_enqueue_widget_scripts() {
	static $did_enqueue = false;
	if ( $did_enqueue ) {
		return;
	}
	$did_enqueue = true;

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || false === strpos( (string) $screen->id, 'bb-settings' ) ) {
		return;
	}

	// Trigger metabox registration so $wp_meta_boxes is populated. The action
	// itself is idempotent at the BP_Group_Extension level — re-firing just
	// re-adds the metabox. The static guard above also prevents this hook
	// from running twice if admin_enqueue_scripts fires multiple times in
	// the same request (e.g., AJAX inside admin).
	if ( did_action( 'bp_groups_admin_meta_boxes' ) === 0 ) {
		ob_start();
		do_action( 'bp_groups_admin_meta_boxes' );
		ob_end_clean();
	}

	global $wp_meta_boxes;
	$screen_match = null;
	foreach ( (array) $wp_meta_boxes as $sid => $_ignored ) {
		if ( is_string( $sid ) && false !== stripos( $sid, 'bp-groups' ) ) {
			$screen_match = $sid;
			break;
		}
	}
	if ( ! $screen_match ) {
		return;
	}

	$combined_html = '';
	foreach ( (array) $wp_meta_boxes[ $screen_match ] as $context => $priorities ) {
		foreach ( (array) $priorities as $boxes ) {
			foreach ( (array) $boxes as $box ) {
				if ( ! is_array( $box ) || empty( $box['callback'] ) ) {
					continue;
				}
				$combined_html .= bb_legacy_capture_box_html( $box, null );
			}
		}
	}

	if ( '' === $combined_html ) {
		return;
	}

	/**
	 * Filter the marker → enqueue map used to auto-attach JS dependencies
	 * (color picker, media uploader, datepicker, TinyMCE, etc.) for legacy
	 * metaboxes rendered through the React modal. Each entry is keyed by
	 * a logical identifier and contains:
	 *   - 'detect'   array of substrings to look for in the captured HTML;
	 *   - 'scripts'  optional list of script handles to wp_enqueue_script();
	 *   - 'styles'   optional list of style handles to wp_enqueue_style();
	 *   - 'callback' optional callable run when at least one detect-string matches.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $markers Marker → enqueue mapping.
	 */
	$markers = (array) apply_filters(
		'bb_legacy_meta_box_extra_scripts',
		array(
			'wp-color-picker'        => array(
				'detect'  => array( 'wp-color-picker', 'colorpicker' ),
				'scripts' => array( 'wp-color-picker' ),
				'styles'  => array( 'wp-color-picker' ),
			),
			'wp-media'               => array(
				'detect'   => array( 'wp-media-button', 'media-uploader', 'data-media-uploader' ),
				'callback' => 'wp_enqueue_media',
			),
			'jquery-ui-datepicker'   => array(
				'detect'  => array( 'hasDatepicker', 'ui-datepicker', 'datepicker-input' ),
				'scripts' => array( 'jquery-ui-datepicker' ),
				'styles'  => array( 'jquery-ui-style' ),
			),
			'jquery-ui-autocomplete' => array(
				'detect'  => array( 'ui-autocomplete', 'autocomplete-input' ),
				'scripts' => array( 'jquery-ui-autocomplete' ),
			),
		)
	);

	foreach ( $markers as $entry ) {
		$detect = isset( $entry['detect'] ) ? (array) $entry['detect'] : array();
		$found  = false;
		foreach ( $detect as $needle ) {
			if ( false !== stripos( $combined_html, $needle ) ) {
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			continue;
		}

		if ( ! empty( $entry['callback'] ) && is_callable( $entry['callback'] ) ) {
			call_user_func( $entry['callback'] );
		}
		$scripts = isset( $entry['scripts'] ) ? (array) $entry['scripts'] : array();
		foreach ( $scripts as $handle ) {
			wp_enqueue_script( $handle );
		}
		$styles = isset( $entry['styles'] ) ? (array) $entry['styles'] : array();
		foreach ( $styles as $handle ) {
			wp_enqueue_style( $handle );
		}
	}
}
add_action( 'admin_enqueue_scripts', 'bb_legacy_groups_auto_enqueue_widget_scripts', 99 );
