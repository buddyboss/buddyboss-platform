<?php
/**
 * Legacy Meta-Box Bridge for the Activity edit modal.
 *
 * Auto-detects third-party plugins that register meta boxes on the legacy
 * activity admin edit screen (via plain `add_meta_box()` on the
 * `buddyboss_page_bp-activity` screen) and surfaces their fields as native
 * typed fields in the Settings 2.0 React activity edit modal — without
 * requiring the third-party plugin to migrate.
 *
 * Strategy mirrors the groups bridge:
 *   1. Hook bb_register_activity_meta_fields late (priority 999) — by then,
 *      the activity admin AJAX handler (BB_Activity_Admin_Ajax::bb_admin_get_activity)
 *      has already fired `bp_activity_admin_meta_boxes`, populating $wp_meta_boxes.
 *   2. Walk $wp_meta_boxes; skip core/known/already-migrated boxes; capture
 *      each remaining box's HTML output via output buffering.
 *   3. Parse with DOMDocument; for every <input>/<select>/<textarea> with a
 *      safe name, register a typed field via the registry. RegisteredMetaField.js
 *      renders it natively.
 *   4. On save, save_value puts each value back on $_POST. The activity AJAX
 *      save handler then fires `bp_activity_admin_edit_after`, where any
 *      third-party plugin's hook reads $_POST and persists the data.
 *
 * Differences from the groups bridge:
 *   - No `BP_Group_Extension`-style class-based extension model exists for
 *     activity, so there's no settings_screen_save() to replay manually.
 *     Plugins save via `bp_activity_admin_edit_after`, which the activity
 *     AJAX save handler fires automatically after the bridge's save_value
 *     closures have populated $_POST.
 *   - Activity uses `$_GET['aid']` instead of `$_GET['gid']` for the legacy
 *     admin context, so capture has to swap the right key.
 *   - Canonical keys are derived from `BP_Activity_Activity` properties,
 *     not `BP_Groups_Group`.
 *
 * Known limitations are inherited from the groups bridge — array-notation
 * names, file uploads, custom AJAX submit endpoints, role-conditional UI,
 * and JS-driven widgets all behave the same way described in the groups
 * bridge header. See `groups/legacy-meta-bridge.php` for the canonical
 * documentation of those caveats.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss 3.0.0
 */

defined( 'ABSPATH' ) || exit;

// Shared parser, capture-safety, and sanitize-resolver helpers used by every
// component bridge. require_once is idempotent across require sites.
require_once dirname( __DIR__ ) . '/legacy-meta-bridge-utils.php';

/**
 * Per-request state container for the activity bridge.
 *
 * Holds:
 *   - html_cache[]: captured metabox HTML keyed by box_id+activity_id.
 *
 * Note: there is no `bridged_slugs` slot here (the groups bridge keeps one
 * for its extension-save replay loop in `bb_legacy_groups_run_extension_saves`).
 * Activity has no equivalent replay step — third-party metabox plugins
 * persist via `bp_activity_admin_edit_after`, fired automatically by the
 * activity AJAX save handler — so the slot would be dead state.
 *
 * The parsed-XPath cache is module-level (a `static $cache` inside
 * `bb_legacy_get_xpath()` in legacy-meta-bridge-utils.php), so every
 * component bridge transparently shares it within a single request.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return array Reference to mutable state array.
 */
function &bb_legacy_activity_bridge_state() {
	static $state = null;
	if ( null === $state ) {
		$state = array(
			'html_cache' => array(),
		);
	}
	return $state;
}

/**
 * Register bridge fields late, after core/Pro fields have registered.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param BB_Admin_Meta_Field_Registry $registry  Registry instance.
 * @param string                       $component Component identifier ('activity').
 */
function bb_legacy_activity_meta_bridge_register( $registry, $component ) {
	if ( 'activity' !== $component ) {
		return;
	}

	// Recursion guard — bail out if a captured callback re-fires our hook.
	static $in_bridge = false;
	if ( $in_bridge ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$in_bridge = true;
	try {
		bb_legacy_activity_meta_bridge_register_inner( $registry, $component );
	} finally {
		$in_bridge = false;
	}
}
add_action( 'bb_register_activity_meta_fields', 'bb_legacy_activity_meta_bridge_register', 999, 2 );

/**
 * Inner implementation, separated so the recursion guard wrapping is clean.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param BB_Admin_Meta_Field_Registry $registry  Registry instance.
 * @param string                       $component Component identifier.
 */
function bb_legacy_activity_meta_bridge_register_inner( $registry, $component ) {
	global $wp_meta_boxes;

	// Self-bootstrap. The activity GET handler (bb_admin_get_activity) fires
	// bp_activity_admin_meta_boxes itself, so by the time this hook runs in
	// the GET path $wp_meta_boxes is already populated. The SAVE handler
	// (bb_admin_save_activity) does NOT — without this guard the bridge
	// would silently bail on every save and third-party metabox values
	// would never flow through bp_activity_admin_edit_after to the
	// plugin's own save handler. Fire the action here on first miss so
	// registration is idempotent across both AJAX paths.
	if ( did_action( 'bp_activity_admin_meta_boxes' ) === 0 ) {
		$had_screen = ( function_exists( 'get_current_screen' ) && null !== get_current_screen() );
		if ( ! $had_screen && function_exists( 'set_current_screen' ) ) {
			set_current_screen( 'buddyboss_page_bp-activity' );
		}
		ob_start();
		try {
			do_action( 'bp_activity_admin_meta_boxes' );
		} catch ( Throwable $e ) {
			unset( $e );
		}
		ob_end_clean();
	}

	// Detect screen — `bp-activity` matches both `buddyboss_page_bp-activity`
	// and any network-admin variant.
	$screen = null;
	foreach ( (array) $wp_meta_boxes as $screen_id => $_ignored ) {
		if ( is_string( $screen_id ) && false !== stripos( $screen_id, 'bp-activity' ) ) {
			$screen = $screen_id;
			break;
		}
	}
	if ( null === $screen ) {
		return;
	}

	// Skip list — boxes Platform/Pro register through the canonical registry.
	/**
	 * Filter the list of activity metabox IDs the legacy bridge should skip.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string[] $skip_box_ids Metabox IDs to skip.
	 */
	$skip_box_ids = (array) apply_filters(
		'bb_legacy_meta_box_bridge_skip_activity',
		array(
			'submitdiv',
			'bp_activity_link',
			'bp_activity_type',
			'bp_activity_userid',
			'bp_activity_itemids',
			'bp_activity_topic',
		)
	);

	// Auto-skip any box whose ID matches a field already registered through
	// the canonical registry — avoids duplicate UI when a plugin has migrated
	// some fields but still ships a legacy metabox alongside them.
	$existing_ids = array();
	if ( method_exists( $registry, 'get_fields' ) ) {
		foreach ( (array) $registry->get_fields( $component ) as $field ) {
			if ( ! empty( $field['id'] ) ) {
				$existing_ids[ 'legacy_' . $field['id'] ] = true;
			}
		}
	}

	$contexts = isset( $wp_meta_boxes[ $screen ] ) ? $wp_meta_boxes[ $screen ] : array();
	$order    = 1000;
	foreach ( (array) $contexts as $context => $priorities ) {
		foreach ( (array) $priorities as $priority => $boxes ) {
			foreach ( (array) $boxes as $box_id => $box ) {
				if ( ! is_array( $box ) || empty( $box['callback'] ) ) {
					continue;
				}
				if ( in_array( $box_id, $skip_box_ids, true ) ) {
					continue;
				}
				bb_legacy_activity_bridge_box( $registry, $component, $box, $order, $existing_ids );
			}
		}
	}
}

/**
 * Capture, parse, and register a single activity metabox.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param BB_Admin_Meta_Field_Registry $registry     Registry instance.
 * @param string                       $component    Component identifier.
 * @param array                        $box          Metabox descriptor from $wp_meta_boxes.
 * @param int                          $order        Order counter (passed by reference).
 * @param array                        $existing_ids Field IDs already registered via canonical path.
 */
function bb_legacy_activity_bridge_box( $registry, $component, $box, &$order, $existing_ids = array() ) {
	$version   = defined( 'BP_PLATFORM_VERSION' ) ? BP_PLATFORM_VERSION : '0';
	$cache_key = 'bb_legacy_activity_box_inputs_' . md5( $box['id'] . '|' . $version );
	$inputs    = get_transient( $cache_key );

	if ( ! is_array( $inputs ) ) {
		$html = bb_legacy_activity_capture_box_html( $box, null );
		if ( ! $html ) {
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

		if ( ! bb_legacy_is_safe_activity_post_key( $input['name'] ) ) {
			continue;
		}

		$field_id = sanitize_key( 'legacy_' . $box['id'] . '_' . $input['name'] );
		if ( isset( $existing_ids[ $field_id ] ) ) {
			continue;
		}

		/**
		 * Filter the React modal tab a bridged legacy activity field should
		 * appear under. Defaults to 'details'.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $tab        Tab slug — defaults to 'details'.
		 * @param string $input_name $_POST key the field writes to.
		 * @param string $box_id     Owning metabox ID.
		 * @param array  $input      Parsed input descriptor.
		 */
		$tab = (string) apply_filters(
			'bb_legacy_activity_meta_field_tab',
			'details',
			$input['name'],
			$box['id'],
			$input
		);

		$raw_label       = $input['label'] ? $input['label'] : $box['title'];
		$raw_description = isset( $input['description'] ) ? $input['description'] : '';

		$sanitize_cb = bb_legacy_resolve_sanitize_callback( $input['type'] );

		$args = array(
			'label'             => sanitize_text_field( $raw_label ),
			'description'       => wp_kses_post( $raw_description ),
			'type'              => $input['type'],
			'order'             => $order++,
			'tab'               => $tab,
			'context'           => 'after',
			'save_phase'        => 'before',
			'sanitize_callback' => $sanitize_cb,
			'get_value'         => bb_legacy_activity_make_get_value( $box, $input['name'], $input['type'] ),
			'save_value'        => function ( $activity, $value ) use ( $input ) {
				if ( ! bb_legacy_is_safe_activity_post_key( $input['name'] ) ) {
					return;
				}
				// Don't clobber a key already populated by a canonical Settings 2.0
				// field — only write when the slot is genuinely empty so the
				// React form always wins over the legacy bridge.
				// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
				if ( isset( $_POST[ $input['name'] ] ) ) {
					return;
				}
				// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
				$_POST[ $input['name'] ] = $value;
			},
		);

		if ( 'select' === $input['type'] || 'toggle_list' === $input['type'] ) {
			$args['get_options'] = function ( $activity ) use ( $box, $input ) {
				$html = bb_legacy_activity_capture_box_html( $box, $activity );
				return bb_legacy_extract_select_options( $html, $input['name'] );
			};
		} elseif ( 'radio' === $input['type'] ) {
			$args['get_options'] = function ( $activity ) use ( $box, $input ) {
				$html = bb_legacy_activity_capture_box_html( $box, $activity );
				return bb_legacy_extract_radio_options( $html, $input['name'] );
			};
		}

		$registry->register( $component, $field_id, $args );
	}
}

/**
 * $_POST safety check for activity bridge fields.
 *
 * Layers on top of the generic checks done in bb_legacy_is_safe_post_key()
 * by also rejecting BP_Activity_Activity property names so a malicious
 * metabox can't overwrite core activity columns through the bridge.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $name $_POST key.
 * @return bool True if the key is safe to write to $_POST via the bridge.
 */
function bb_legacy_is_safe_activity_post_key( $name ) {
	if ( ! is_string( $name ) || '' === $name ) {
		return false;
	}

	// Generic structural denylist (prefixes _, bb_admin_, bp_admin_, wp_,
	// names like _wpnonce, action, role, pass1 — and the canonical-group
	// keys, which we don't care about here but are harmless).
	if ( ! bb_legacy_is_safe_post_key( $name ) ) {
		return false;
	}

	// Also block activity-canonical property names so the bridge can't
	// shadow the registry's core activity fields.
	$canonical = bb_legacy_canonical_activity_keys();
	return ! in_array( strtolower( $name ), $canonical, true );
}

/**
 * Build the canonical-keys list for activities.
 *
 * Uses Reflection on `BP_Activity_Activity` so the list stays in sync with
 * any new column added to the activity object. Cached per-request.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return string[] Lowercase activity property names.
 */
function bb_legacy_canonical_activity_keys() {
	static $keys = null;
	if ( null !== $keys ) {
		return $keys;
	}
	$keys = array();

	if ( class_exists( 'BP_Activity_Activity' ) && class_exists( 'ReflectionClass' ) ) {
		try {
			$reflection = new ReflectionClass( 'BP_Activity_Activity' );
			foreach ( $reflection->getProperties( ReflectionProperty::IS_PUBLIC ) as $prop ) {
				$keys[] = strtolower( $prop->getName() );
			}
		} catch ( ReflectionException $e ) {
			unset( $e ); // Fall through to extras below.
		}
	}

	// Extras — keys the activity admin save reads directly from $_POST that
	// aren't BP_Activity_Activity public properties. Keep in lockstep with
	// BB_Activity_Admin_Ajax::bb_admin_save_activity().
	$extras = array(
		'activity_id',
		'action_text',
		'link_url',
		'link_embed',
	);
	foreach ( $extras as $extra ) {
		$keys[] = strtolower( $extra );
	}

	$keys = array_values( array_unique( $keys ) );

	/**
	 * Filter the list of canonical activity $_POST keys the bridge will never
	 * overwrite. Plugin authors can add custom keys here if their save handler
	 * reads $_POST values that should be reserved for the React form.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string[] $keys Lowercase canonical key names.
	 */
	$keys = (array) apply_filters( 'bb_legacy_canonical_activity_post_keys', $keys );

	return $keys;
}

/**
 * Capture metabox callback HTML safely for activity context.
 *
 * Per-request memoized by (box_id, activity_id). Wraps the callback in the
 * shared wp_die-safety wrapper so a buggy metabox can't kill the AJAX
 * response. Swaps `$_GET['aid']` to the captured activity's ID so legacy
 * metabox callbacks reading the request param see the right activity.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param array                     $box      Metabox descriptor.
 * @param BP_Activity_Activity|null $activity Activity to capture against, or null.
 * @return string Captured HTML, or '' on error / oversize.
 */
function bb_legacy_activity_capture_box_html( $box, $activity ) {
	if ( empty( $box['callback'] ) || empty( $box['id'] ) ) {
		return '';
	}

	$state       = &bb_legacy_activity_bridge_state();
	$activity_id = ( is_object( $activity ) && isset( $activity->id ) ) ? (int) $activity->id : 0;
	$cache_key   = $box['id'] . '|' . $activity_id;
	if ( isset( $state['html_cache'][ $cache_key ] ) ) {
		return $state['html_cache'][ $cache_key ];
	}

	// Only swap $_GET['aid'] if the activity object looks hydrated. A fresh
	// BP_Activity_Activity( $missing_id ) leaves component empty.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
	$original_aid = isset( $_GET['aid'] ) ? sanitize_text_field( wp_unslash( $_GET['aid'] ) ) : null;
	$is_hydrated  = is_object( $activity ) && ! empty( $activity->component );
	if ( $activity_id && $is_hydrated ) {
		$_GET['aid'] = $activity_id;
	} else {
		$activity_id = 0;
	}

	$result = '';
	try {
		$result = bb_legacy_with_wp_die_safety(
			function () use ( $box, $activity ) {
				ob_start();
				try {
					call_user_func( $box['callback'], $activity, $box );
				} catch ( Throwable $e ) {
					ob_end_clean();
					return '';
				}
				return ob_get_clean();
			}
		);
	} catch ( Throwable $e ) {
		$result = '';
	} finally {
		if ( null === $original_aid ) {
			unset( $_GET['aid'] );
		} else {
			$_GET['aid'] = $original_aid;
		}
	}

	if ( strlen( $result ) > BB_LEGACY_BRIDGE_MAX_HTML ) {
		$result = '';
	}

	$state['html_cache'][ $cache_key ] = $result;
	return $result;
}

/**
 * Get_value closure factory for activity bridge fields.
 *
 * Re-renders the metabox with the real activity (cached) and extracts the
 * specific input's current value.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param array  $box  Metabox descriptor.
 * @param string $name Input name.
 * @param string $type Detected field type.
 * @return callable get_value callback.
 */
function bb_legacy_activity_make_get_value( $box, $name, $type ) {
	return function ( $activity ) use ( $box, $name, $type ) {
		$html = bb_legacy_activity_capture_box_html( $box, $activity );
		return bb_legacy_extract_input_value( $html, $name, $type );
	};
}

/**
 * Clear all bb_legacy_activity_box_inputs_* transients on plugin lifecycle
 * events. Mirrors the groups bridge's cleanup helper. Object-cache aware.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_legacy_activity_clear_bridge_cache() {
	if ( wp_using_ext_object_cache() ) {
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
						delete_transient( 'bb_legacy_activity_box_inputs_' . md5( $box['id'] . '|' . $version ) );
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
		 WHERE option_name LIKE '_transient_bb_legacy_activity_box_inputs_%'
		    OR option_name LIKE '_transient_timeout_bb_legacy_activity_box_inputs_%'"
	);
	// phpcs:enable
}
add_action( 'activated_plugin', 'bb_legacy_activity_clear_bridge_cache' );
add_action( 'deactivated_plugin', 'bb_legacy_activity_clear_bridge_cache' );
add_action( 'upgrader_process_complete', 'bb_legacy_activity_clear_bridge_cache' );
add_action( 'switch_theme', 'bb_legacy_activity_clear_bridge_cache' );

/**
 * Auto-enqueue scripts that legacy activity metaboxes commonly depend on
 * (color picker, media uploader, datepicker, TinyMCE), so widgets work
 * inside the React activity edit modal.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_legacy_activity_auto_enqueue_widget_scripts() {
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
	// itself is idempotent — re-firing just re-adds the metaboxes.
	if ( did_action( 'bp_activity_admin_meta_boxes' ) === 0 ) {
		ob_start();
		do_action( 'bp_activity_admin_meta_boxes' );
		ob_end_clean();
	}

	global $wp_meta_boxes;
	$screen_match = null;
	foreach ( (array) $wp_meta_boxes as $sid => $_ignored ) {
		if ( is_string( $sid ) && false !== stripos( $sid, 'bp-activity' ) ) {
			$screen_match = $sid;
			break;
		}
	}
	if ( ! $screen_match ) {
		return;
	}

	$combined_html = '';
	$contexts      = isset( $wp_meta_boxes[ $screen_match ] ) ? $wp_meta_boxes[ $screen_match ] : array();
	foreach ( (array) $contexts as $priorities ) {
		foreach ( (array) $priorities as $boxes ) {
			foreach ( (array) $boxes as $box ) {
				if ( ! is_array( $box ) || empty( $box['callback'] ) || empty( $box['id'] ) ) {
					continue;
				}
				$combined_html .= bb_legacy_activity_capture_box_html( $box, null );
			}
		}
	}

	if ( '' === $combined_html ) {
		return;
	}

	/**
	 * Re-uses the same marker→enqueue map as the groups bridge — share
	 * one filter so plugin authors don't have to register twice.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $markers Marker → enqueue mapping.
	 */
	$markers = (array) apply_filters(
		'bb_legacy_meta_box_extra_scripts',
		array(
			'wp-color-picker'      => array(
				'detect'  => array( 'wp-color-picker', 'colorpicker' ),
				'scripts' => array( 'wp-color-picker' ),
				'styles'  => array( 'wp-color-picker' ),
			),
			'wp-media'             => array(
				'detect'   => array( 'wp-media-button', 'media-uploader', 'data-media-uploader' ),
				'callback' => 'wp_enqueue_media',
			),
			'jquery-ui-datepicker' => array(
				'detect'  => array( 'hasDatepicker', 'ui-datepicker', 'datepicker-input' ),
				'scripts' => array( 'jquery-ui-datepicker' ),
				'styles'  => array( 'jquery-ui-style' ),
			),
			'tinymce'              => array(
				'detect'   => array( 'tinymce', 'wp-editor-area', 'wp-editor-wrap' ),
				'callback' => function () {
					if ( function_exists( 'wp_enqueue_editor' ) ) {
						wp_enqueue_editor();
					}
				},
			),
		)
	);

	foreach ( $markers as $entry ) {
		if ( empty( $entry['detect'] ) || ! is_array( $entry['detect'] ) ) {
			continue;
		}
		$matched = false;
		foreach ( $entry['detect'] as $needle ) {
			if ( is_string( $needle ) && false !== stripos( $combined_html, $needle ) ) {
				$matched = true;
				break;
			}
		}
		if ( ! $matched ) {
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
add_action( 'admin_enqueue_scripts', 'bb_legacy_activity_auto_enqueue_widget_scripts', 99 );
