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
 * @since   BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Per-request state container.
 *
 * Holds:
 *   - bridged_slugs[]:  metabox IDs the bridge surfaced fields for, used to
 *                       avoid double-saving in the extension save loop.
 *   - html_cache[]:     captured metabox HTML keyed by box_id+group_id
 *                       (avoids re-running third-party callbacks N+1 times).
 *   - xpath_cache[]:    parsed DOMXPath instances keyed by HTML hash.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array Reference to mutable state array.
 */
function &bb_legacy_groups_bridge_state() {
	static $state = null;
	if ( null === $state ) {
		$state = array(
			'bridged_slugs' => array(),
			'html_cache'    => array(),
			'xpath_cache'   => array(),
		);
	}
	return $state;
}

/**
 * Maximum HTML size we'll attempt to parse, defends against billion-laughs /
 * quadratic-blowup payloads from malicious metabox callbacks.
 */
defined( 'BB_LEGACY_BRIDGE_MAX_HTML' ) || define( 'BB_LEGACY_BRIDGE_MAX_HTML', 1024 * 1024 ); // 1 MB.

/**
 * Register bridge fields late, after core/Pro fields have registered.
 *
 * Defensive guards:
 *   - Capability check (component AJAX handler runs as authenticated admin
 *     in normal flow; this is belt-and-suspenders for any other entry path).
 *   - Recursion guard (prevents infinite loop if a captured callback itself
 *     triggers bb_register_groups_meta_fields).
 *
 * @since BuddyBoss [BBVERSION]
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
 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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
 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
		$args            = array(
			// Labels are surfaced as plain-text in the React modal, descriptions
			// allow the same inline HTML wp_kses_post() permits (links, em, etc.).
			'label'       => sanitize_text_field( $raw_label ),
			'description' => wp_kses_post( $raw_description ),
			'type'        => $input['type'],
			'order'       => $order++,
			'tab'         => $tab,
			'context'     => 'after',
			'get_value'   => bb_legacy_make_get_value( $box, $input['name'], $input['type'] ),
			'save_value'  => function ( $group, $value ) use ( $input ) {
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
 * Allowlist-by-denylist for $_POST keys we'll let third-party plugins drive.
 *
 * Blocks:
 *   - Reserved Platform/BP/WP/leading-underscore prefixes
 *   - Sensitive WP user-management keys (role, user_login, password, etc.)
 *   - Array-notation keys (`name="foo[]"`) — sanitize_key() can't represent
 *     these losslessly and they corrupt $_POST when reassembled
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $name HTML input name attribute.
 * @return bool True if the key is safe to write to $_POST.
 */
function bb_legacy_is_safe_post_key( $name ) {
	$name = (string) $name;
	if ( '' === $name ) {
		return false;
	}

	// Reject array notation (`things[]`) — handled separately if at all.
	if ( false !== strpos( $name, '[' ) || false !== strpos( $name, ']' ) ) {
		return false;
	}

	// Reject names that aren't pure ASCII identifier-ish characters.
	if ( ! preg_match( '/^[A-Za-z][A-Za-z0-9_\-]*$/', $name ) ) {
		return false;
	}

	$deny_prefixes = array(
		'_',          // WordPress private (e.g., _wpnonce, _wp_http_referer).
		'bb_admin_',  // BuddyBoss admin internal.
		'bp_admin_',  // BuddyPress admin internal.
		'wp_',        // WordPress core.
	);
	foreach ( $deny_prefixes as $prefix ) {
		if ( 0 === strncmp( $name, $prefix, strlen( $prefix ) ) ) {
			return false;
		}
	}

	$deny_exact = array(
		'action',
		'role',
		'roles',
		'user_login',
		'user_pass',
		'user_email',
		'user_registered',
		'pass1',
		'pass2',
		'password',
		'nonce',
	);
	if ( in_array( strtolower( $name ), $deny_exact, true ) ) {
		return false;
	}

	// Block any key that maps to a canonical BP_Groups_Group property or to a
	// well-known Platform group-save POST key. Derived dynamically so future
	// Platform additions don't require updating this file.
	if ( in_array( strtolower( $name ), bb_legacy_canonical_group_keys(), true ) ) {
		return false;
	}

	return true;
}

/**
 * Build the list of $_POST keys Platform's canonical group save handler
 * owns. The bridge will refuse to write to any of these to prevent a
 * third-party metabox from clobbering React's form values.
 *
 * Most keys are auto-discovered from BP_Groups_Group's public properties via
 * Reflection; a small set of additional keys (forum_id, etc.) used by
 * Platform's save handler but not stored as group properties is listed
 * explicitly. Plugin authors can extend the list via the
 * `bb_legacy_canonical_group_post_keys` filter.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string[] Lowercase canonical key names.
 */
function bb_legacy_canonical_group_keys() {
	static $keys = null;
	if ( null !== $keys ) {
		return $keys;
	}

	$keys = array();

	if ( class_exists( 'BP_Groups_Group' ) ) {
		try {
			$reflection = new ReflectionClass( 'BP_Groups_Group' );
			foreach ( $reflection->getProperties( ReflectionProperty::IS_PUBLIC ) as $prop ) {
				$keys[] = strtolower( $prop->getName() );
			}
		} catch ( ReflectionException $e ) {
			unset( $e ); // Fall through to extras below.
		}
	}

	// Extras — POST keys used by Platform's canonical groups save handler
	// (and listeners on bb_admin_after_save_group / bp_group_admin_edit_after)
	// that aren't BP_Groups_Group public properties.
	$extras = array(
		'forum_id',     // Forum association (saved separately to forum meta).
		'enable_forum', // Forum toggle on the React form.
		'group_id',     // Used in URL params and some internal hooks.
	);
	foreach ( $extras as $extra ) {
		if ( ! in_array( $extra, $keys, true ) ) {
			$keys[] = $extra;
		}
	}

	/**
	 * Filter the list of canonical group $_POST keys the legacy bridge will
	 * never overwrite. Plugin authors can add custom keys here if their save
	 * handler reads $_POST values that should be reserved for the React form.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string[] $keys Lowercase canonical key names.
	 */
	$keys = (array) apply_filters( 'bb_legacy_canonical_group_post_keys', $keys );

	return $keys;
}

/**
 * Capture metabox callback HTML safely.
 *
 * Per-request memoized by (box_id, group_id). Wraps the callback in a
 * temporary wp_die handler so a buggy callback that calls die()/wp_die()
 * doesn't kill the whole AJAX response. Restores $_GET['gid'] on every
 * exit path.
 *
 * @since BuddyBoss [BBVERSION]
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
 * Run a callable while wp_die() / die() / exit() inside it throws an
 * Exception instead of terminating the request. Restores filters on exit.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param callable $callback Callable to run.
 * @return mixed Return value of $callback.
 */
function bb_legacy_with_wp_die_safety( callable $callback ) {
	$throwing_handler = function ( $message = '' ) {
		// Sanitize before interpolating into the exception message — wp_die()
		// may be called with HTML, and the exception trace can be logged.
		$safe = is_string( $message ) ? wp_strip_all_tags( $message ) : '';
		throw new RuntimeException( 'Legacy bridge: wp_die intercepted (' . esc_html( $safe ) . ')' );
	};
	$installer        = function () use ( $throwing_handler ) {
		return $throwing_handler;
	};

	add_filter( 'wp_die_ajax_handler', $installer, 9999 );
	add_filter( 'wp_die_handler', $installer, 9999 );
	add_filter( 'wp_die_json_handler', $installer, 9999 );
	add_filter( 'wp_die_jsonp_handler', $installer, 9999 );
	add_filter( 'wp_die_xmlrpc_handler', $installer, 9999 );

	try {
		return $callback();
	} finally {
		remove_filter( 'wp_die_ajax_handler', $installer, 9999 );
		remove_filter( 'wp_die_handler', $installer, 9999 );
		remove_filter( 'wp_die_json_handler', $installer, 9999 );
		remove_filter( 'wp_die_jsonp_handler', $installer, 9999 );
		remove_filter( 'wp_die_xmlrpc_handler', $installer, 9999 );
	}
}

/**
 * Build a memoized DOMXPath for an HTML string.
 *
 * Hardened: LIBXML_NONET disables network access; LIBXML_NOENT disables
 * external entity expansion; libxml internal errors are buffered to keep
 * malformed third-party HTML from polluting WordPress's error stream.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $html HTML to parse.
 * @return DOMXPath|null XPath instance, or null if parsing failed.
 */
function bb_legacy_get_xpath( $html ) {
	if ( '' === (string) $html ) {
		return null;
	}
	if ( strlen( $html ) > BB_LEGACY_BRIDGE_MAX_HTML ) {
		return null;
	}

	$state = &bb_legacy_groups_bridge_state();
	$key   = md5( $html );
	if ( isset( $state['xpath_cache'][ $key ] ) ) {
		return $state['xpath_cache'][ $key ];
	}

	$doc = new DOMDocument();
	libxml_use_internal_errors( true );

	// PHP 7.x defense: explicitly disable external entity loading. On PHP 8.0+
	// this function is a deprecated no-op (the default became safer), so we
	// suppress any deprecation notice with @ and gate the restore on null.
	$prev_entity_loader = null;
	if ( function_exists( 'libxml_disable_entity_loader' ) ) {
		$prev_entity_loader = @libxml_disable_entity_loader( true ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,Generic.PHP.DeprecatedFunctions.Deprecated,PHPCompatibility.FunctionUse.RemovedFunctions,WordPress.PHP.NoSilencedErrors.Discouraged
	}

	$loaded = $doc->loadHTML(
		'<?xml encoding="UTF-8"?>' . $html,
		LIBXML_NONET | LIBXML_NOENT
	);
	libxml_clear_errors();

	if ( null !== $prev_entity_loader && function_exists( 'libxml_disable_entity_loader' ) ) {
		@libxml_disable_entity_loader( $prev_entity_loader ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,Generic.PHP.DeprecatedFunctions.Deprecated,PHPCompatibility.FunctionUse.RemovedFunctions,WordPress.PHP.NoSilencedErrors.Discouraged
	}

	if ( ! $loaded ) {
		$state['xpath_cache'][ $key ] = null;
		return null;
	}

	$xpath                        = new DOMXPath( $doc );
	$state['xpath_cache'][ $key ] = $xpath;
	return $xpath;
}

/**
 * Sanitize a string for safe interpolation into an XPath single-quoted
 * literal. Strips characters outside the allowed identifier set.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $value Untrusted value.
 * @return string Safe-to-interpolate identifier.
 */
function bb_legacy_xpath_safe( $value ) {
	return preg_replace( '/[^A-Za-z0-9_\-:.]/', '', (string) $value );
}

/**
 * Parse <input>/<select>/<textarea> tags out of captured HTML.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $html Captured metabox HTML.
 * @return array List of input descriptors.
 */
function bb_legacy_parse_box_inputs( $html ) {
	$xpath = bb_legacy_get_xpath( $html );
	if ( ! $xpath ) {
		return array();
	}

	$inputs       = array();
	$radio_groups = array();

	foreach ( $xpath->query( '//input | //select | //textarea' ) as $node ) {
		// @var DOMElement $node — type hint for DOMNodeList iteration.
		$name = $node->getAttribute( 'name' );
		if ( ! $name ) {
			continue;
		}
		// Skip well-known structural inputs at parse time too (defense in depth
		// — bb_legacy_is_safe_post_key() also rejects these).
		if ( in_array( $name, array( '_wpnonce', '_wp_http_referer', 'action' ), true ) ) {
			continue;
		}
		if ( 0 === strpos( $name, '_bp_group_' ) ) {
			continue;
		}

		$type = bb_legacy_detect_input_type( $node );
		if ( in_array( $type, array( 'submit', 'button' ), true ) ) {
			continue;
		}

		/**
		 * Filter the detected field type for a bridged legacy input. Plugin
		 * authors can override the auto-detected type when the parser's
		 * heuristic guesses wrong (e.g., a custom widget rendered as a hidden
		 * input that should surface as a textarea).
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string     $type Auto-detected field type.
		 * @param string     $name Input name attribute / $_POST key.
		 * @param DOMElement $node Parsed DOM node for the input.
		 */
		$type = (string) apply_filters( 'bb_legacy_meta_field_type', $type, $name, $node );

		if ( 'radio' === $type ) {
			if ( isset( $radio_groups[ $name ] ) ) {
				continue;
			}
			$radio_groups[ $name ] = true;
		}

		$inputs[] = array(
			'name'        => $name,
			'type'        => $type,
			'label'       => bb_legacy_find_label( $node, $xpath ),
			'description' => bb_legacy_find_description( $node, $xpath ),
		);
	}

	return $inputs;
}

/**
 * Detect a registry-compatible field type from a DOM input/select/textarea.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param DOMElement $node DOM node.
 * @return string Field type for BB_Admin_Meta_Field_Registry.
 */
function bb_legacy_detect_input_type( DOMElement $node ) {
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
	$tag = strtolower( $node->tagName );

	if ( 'select' === $tag ) {
		return $node->getAttribute( 'multiple' ) ? 'toggle_list' : 'select';
	}
	if ( 'textarea' === $tag ) {
		$class = $node->getAttribute( 'class' );
		if ( false !== stripos( $class, 'tinymce' ) || false !== stripos( $class, 'wp-editor-area' ) ) {
			return 'richtext';
		}
		return 'textarea';
	}

	$type_attr = $node->getAttribute( 'type' );
	$html_type = strtolower( '' !== $type_attr ? $type_attr : 'text' );
	$map       = array(
		'text'     => 'text',
		'number'   => 'number',
		'url'      => 'url',
		'email'    => 'text',
		'date'     => 'date',
		'time'     => 'time',
		'checkbox' => 'checkbox',
		'radio'    => 'radio',
		'file'     => 'file',
		'hidden'   => 'hidden',
		'submit'   => 'submit',
		'button'   => 'button',
	);
	return isset( $map[ $html_type ] ) ? $map[ $html_type ] : 'text';
}

/**
 * Find the label text for an input node.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param DOMElement $node  DOM node.
 * @param DOMXPath   $xpath XPath instance.
 * @return string Label text or empty.
 */
function bb_legacy_find_label( DOMElement $node, DOMXPath $xpath ) {
	$id = bb_legacy_xpath_safe( $node->getAttribute( 'id' ) );
	if ( $id ) {
		$labels = $xpath->query( "//label[@for='{$id}']" );
		if ( $labels->length ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
			return trim( $labels->item( 0 )->textContent );
		}
	}

	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
	$parent = $node->parentNode;
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase,WordPress.PHP.YodaConditions.NotYoda -- DOM API property.
	while ( $parent && $parent->nodeType === XML_ELEMENT_NODE ) {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
		if ( 'label' === strtolower( $parent->tagName ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
			return trim( $parent->textContent );
		}
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
		$parent = $parent->parentNode;
	}

	$th = $xpath->query( 'ancestor::tr/th[1]', $node );
	if ( $th->length ) {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
		return trim( $th->item( 0 )->textContent );
	}

	return '';
}

/**
 * Find the description text for an input node.
 *
 * Walks the DOM looking for a sibling/nearby <p class="description">
 * (WordPress admin convention) or <span class="description">.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param DOMElement $node  Input node.
 * @param DOMXPath   $xpath XPath instance.
 * @return string Description text or empty.
 */
function bb_legacy_find_description( DOMElement $node, DOMXPath $xpath ) {
	$desc = $xpath->query( "following-sibling::p[contains(concat(' ', normalize-space(@class), ' '), ' description ')][1]", $node );
	if ( $desc->length ) {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
		return trim( $desc->item( 0 )->textContent );
	}

	$desc = $xpath->query( "following-sibling::span[contains(concat(' ', normalize-space(@class), ' '), ' description ')][1]", $node );
	if ( $desc->length ) {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
		return trim( $desc->item( 0 )->textContent );
	}

	$desc = $xpath->query( "ancestor::*[1]//p[contains(concat(' ', normalize-space(@class), ' '), ' description ')][1]", $node );
	if ( $desc->length ) {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
		return trim( $desc->item( 0 )->textContent );
	}

	return '';
}

/**
 * Build a get_value closure that re-renders the metabox with the real group
 * (cached) and extracts THIS input's current value.
 *
 * @since BuddyBoss [BBVERSION]
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
 * Extract a single input's current value from re-rendered HTML.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $html Captured HTML.
 * @param string $name Input name.
 * @param string $type Field type.
 * @return string Current value.
 */
function bb_legacy_extract_input_value( $html, $name, $type ) {
	$xpath = bb_legacy_get_xpath( $html );
	if ( ! $xpath ) {
		return '';
	}
	$safe_name = bb_legacy_xpath_safe( $name );
	if ( '' === $safe_name ) {
		return '';
	}

	if ( 'textarea' === $type || 'richtext' === $type ) {
		$node = $xpath->query( "(//textarea[@name='{$safe_name}'])[1]" );
		if ( $node->length ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
			return $node->item( 0 )->textContent;
		}
	} elseif ( 'select' === $type ) {
		$node = $xpath->query( "(//select[@name='{$safe_name}']/option[@selected])[1]" );
		if ( $node->length ) {
			return $node->item( 0 )->getAttribute( 'value' );
		}
	} elseif ( 'checkbox' === $type ) {
		$node = $xpath->query( "(//input[@name='{$safe_name}' and @type='checkbox'])[1]" );
		return ( $node->length && $node->item( 0 )->hasAttribute( 'checked' ) ) ? '1' : '0';
	} elseif ( 'radio' === $type ) {
		$node = $xpath->query( "//input[@name='{$safe_name}' and @type='radio' and @checked]" );
		if ( $node->length ) {
			return $node->item( 0 )->getAttribute( 'value' );
		}
	} else {
		$node = $xpath->query( "(//input[@name='{$safe_name}'])[1]" );
		if ( $node->length ) {
			return $node->item( 0 )->getAttribute( 'value' );
		}
	}

	return '';
}

/**
 * Extract <option> list from a select.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $html Captured HTML.
 * @param string $name Select name.
 * @return array List of [ 'label', 'value' ] entries.
 */
function bb_legacy_extract_select_options( $html, $name ) {
	$xpath = bb_legacy_get_xpath( $html );
	if ( ! $xpath ) {
		return array();
	}
	$safe_name = bb_legacy_xpath_safe( $name );
	if ( '' === $safe_name ) {
		return array();
	}

	$out = array();
	foreach ( $xpath->query( "//select[@name='{$safe_name}']/option" ) as $opt ) {
		$out[] = array(
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
			'label' => trim( $opt->textContent ),
			'value' => $opt->getAttribute( 'value' ),
		);
	}
	return $out;
}

/**
 * Extract radio button options from a name group.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $html Captured HTML.
 * @param string $name Radio group name.
 * @return array List of [ 'label', 'value' ] entries.
 */
function bb_legacy_extract_radio_options( $html, $name ) {
	$xpath = bb_legacy_get_xpath( $html );
	if ( ! $xpath ) {
		return array();
	}
	$safe_name = bb_legacy_xpath_safe( $name );
	if ( '' === $safe_name ) {
		return array();
	}

	$out = array();
	foreach ( $xpath->query( "//input[@name='{$safe_name}' and @type='radio']" ) as $radio ) {
		$value = $radio->getAttribute( 'value' );
		$label = '';
		$id    = bb_legacy_xpath_safe( $radio->getAttribute( 'id' ) );
		if ( $id ) {
			$lbl = $xpath->query( "//label[@for='{$id}']" );
			if ( $lbl->length ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
				$label = trim( $lbl->item( 0 )->textContent );
			}
		}
		$out[] = array(
			'label' => '' !== $label ? $label : $value,
			'value' => $value,
		);
	}
	return $out;
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
 * @since BuddyBoss [BBVERSION]
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
 * @since BuddyBoss [BBVERSION]
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
 * @since BuddyBoss [BBVERSION]
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
 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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
