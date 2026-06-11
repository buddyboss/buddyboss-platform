<?php
/**
 * BP Auto Group Join compatibility module for the Settings 2.0 legacy-meta bridge.
 *
 * Makes the "Auto-Join Users to this Group" metabox (registered by the
 * `bp-auto-group-join` plugin via `bp_groups_admin_meta_boxes` / `add_meta_box`)
 * fully usable inside the Settings 2.0 group edit modal.
 *
 * Why a manual compat (and not the auto-bridge):
 *   - The metabox renders two `<input type="checkbox" name="aj_*_mt[]">` field
 *     groups for member-type selection. The groups auto-bridge rejects any
 *     name containing `[`/`]` (see `bb_legacy_is_safe_post_key()`); array-shape
 *     reassembly only exists on the CPT path, not the groups path.
 *   - The plugin's save handler (`bp_groups_admin_load` in
 *     `bp-auto-group-join-admin-class.php`) reads `$_POST` with its own nonce
 *     (`bpagj_group_mb_nonce`) and ALSO triggers a real DB write side-effect:
 *     selecting "All existing members" or a member-type set dispatches
 *     `bp_auto_group_join_all_members()` which enrolls users right then. The
 *     bridge's React save never runs that nonce path, so the side-effect must
 *     be replayed manually on `bb_admin_after_save_group` using the final
 *     saved meta values.
 *
 * Implementation:
 *   - Register the 4 fields directly on `BB_Admin_Meta_Field_Registry` via
 *     `bb_register_groups_meta_fields`.
 *   - Tell the auto-bridge to skip `bp_group_auto_join_member` so its radios
 *     don't get bridged a second time.
 *   - The two member-type `checkbox_list` fields use a `conditional` so React
 *     hides them until the matching parent radio is set to `bp_member_types`,
 *     mirroring the metabox's inline `display:none` toggling.
 *   - The two member-type fields are only registered when the plugin's
 *     `ajg_bmt_support` option is "on", mirroring the legacy screen which
 *     hides the entire "Select by member type" radio option in that case.
 *   - On save, replay the legacy side-effect: re-read the final meta, and if
 *     existing-users policy is `all_members` or `bp_member_types`, call
 *     `bp_auto_group_join_all_members()` exactly as the legacy handler did.
 *
 * Loaded once from `legacy-meta-bridge-utils.php`, gated by the plugin's
 * `BP_AUTO_GROUP_JOIN_PLUGIN_VERSION` constant.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Whether the BMT (member type) sub-fields should be exposed.
 *
 * Mirrors the metabox's gate: it conditionally renders the entire
 * "Select by member type" radio option only when the plugin's
 * `ajg_bmt_support` option is set to "on". When the option is off (or the
 * plugin singleton isn't reachable yet), the bridge omits the member-type
 * fields entirely — matching what an admin sees on the legacy screen.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True when BMT-driven member-type fields should be registered.
 */
function bb_legacy_bpagj_member_types_enabled() {
	if ( ! function_exists( 'bp_auto_group_join' ) ) {
		return false;
	}

	$plugin = bp_auto_group_join();
	if ( ! is_object( $plugin ) || ! method_exists( $plugin, 'option' ) ) {
		return false;
	}

	return 'on' === $plugin->option( 'ajg_bmt_support' );
}

/**
 * Return the registered member type slugs => labels, or an empty array when
 * none are registered or the BP members component isn't active.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array Map of slug => label.
 */
function bb_legacy_bpagj_member_types() {
	if ( ! function_exists( 'bp_get_member_types' ) ) {
		return array();
	}

	$types = bp_get_member_types( array(), 'objects' );
	if ( ! is_array( $types ) ) {
		return array();
	}

	$out = array();
	foreach ( $types as $slug => $type ) {
		// `objects` mode returns stdClass entries whose `labels` is itself an
		// ARRAY (not an object) — keyed `name` / `singular_name`. Prefer the
		// plural `name` (matches the metabox markup at bp-auto-group-join's
		// admin-class.php where `bp_get_member_types()` flat-mode keys map to
		// human names directly), fall back to `singular_name`, then the slug.
		$labels = isset( $type->labels ) && is_array( $type->labels ) ? $type->labels : array();
		$label  = '';
		foreach ( array( 'name', 'singular_name' ) as $key ) {
			if ( isset( $labels[ $key ] ) && '' !== (string) $labels[ $key ] ) {
				$label = (string) $labels[ $key ];
				break;
			}
		}
		if ( '' === $label ) {
			$label = (string) $slug;
		}
		$out[ (string) $slug ] = $label;
	}

	return $out;
}

/**
 * Build the member type options array used by both `checkbox_list` fields.
 * Shaped the way `BB_Admin_Meta_Field_Registry` expects (`label`/`value`
 * pairs) and stable-ordered so React's React state diff is predictable.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array
 */
function bb_legacy_bpagj_member_type_options() {
	$options = array();
	foreach ( bb_legacy_bpagj_member_types() as $slug => $label ) {
		$options[] = array(
			'value' => (string) $slug,
			'label' => $label,
		);
	}
	return $options;
}

/**
 * Sanitize a member-type multi-select POST value.
 *
 * Accepts an array of slugs or a flat string (defensive — React sends an
 * array, but a misbehaving caller might submit a comma-joined string).
 * Each slug is run through `sanitize_key` and constrained to the set of
 * currently-registered member types — this prevents arbitrary strings from
 * being persisted as a member-type slug, while still letting admins clear
 * the field by submitting an empty array.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $raw Raw POST value.
 * @return array Sanitized slug list.
 */
function bb_legacy_bpagj_sanitize_member_types( $raw ) {
	// Routes both shapes (object map from `toggle_list`, flat array from a
	// direct caller) through the same converter so the saved meta is always
	// the legacy flat list of selected slugs.
	return bb_legacy_bpagj_toggle_list_to_meta( $raw );
}

/**
 * Convert the legacy flat-array meta shape (`['slug_a', 'slug_b']`) into the
 * object map (`{ slug_a: 1, slug_b: 1 }`) that React's `toggle_list` field
 * expects. Unknown stored slugs are dropped to keep stale data out of the UI.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $stored Raw groupmeta value.
 * @return array Map of slug => 1.
 */
function bb_legacy_bpagj_meta_to_toggle_list( $stored ) {
	if ( ! is_array( $stored ) ) {
		return array();
	}
	$allowed = array_keys( bb_legacy_bpagj_member_types() );
	$out     = array();
	foreach ( $stored as $slug ) {
		$slug = sanitize_key( (string) $slug );
		if ( '' !== $slug && in_array( $slug, $allowed, true ) ) {
			$out[ $slug ] = 1;
		}
	}
	return $out;
}

/**
 * Convert the React `toggle_list` value (`{ slug: 1|0 }`) back into the flat
 * array of "on" slugs the legacy plugin reads from groupmeta. Accepts a flat
 * array too in case a caller already pre-flattened.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $raw React-side value.
 * @return array Flat list of selected slugs.
 */
function bb_legacy_bpagj_toggle_list_to_meta( $raw ) {
	if ( is_string( $raw ) ) {
		$raw = '' === $raw ? array() : explode( ',', $raw );
	}
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$allowed = array_keys( bb_legacy_bpagj_member_types() );
	$out     = array();

	// Object shape: { slug => 0|1 }.
	$is_assoc = array_keys( $raw ) !== range( 0, count( $raw ) - 1 );
	if ( $is_assoc ) {
		foreach ( $raw as $slug => $on ) {
			$slug = sanitize_key( (string) $slug );
			if ( '' === $slug || ! in_array( $slug, $allowed, true ) ) {
				continue;
			}
			// `'0'`/`0`/false all mean off — drop them; everything else means on.
			if ( '0' === (string) $on || false === $on || 0 === $on ) {
				continue;
			}
			$out[] = $slug;
		}
	} else {
		// Indexed array shape: keep entries that are valid slugs.
		foreach ( $raw as $value ) {
			$slug = sanitize_key( (string) $value );
			if ( '' !== $slug && in_array( $slug, $allowed, true ) ) {
				$out[] = $slug;
			}
		}
	}

	return array_values( array_unique( $out ) );
}

/**
 * Sanitize a policy radio value ("none" / "all_members" / "bp_member_types").
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $raw Raw POST value.
 * @return string One of the allowed values; defaults to '' for "no opinion".
 */
function bb_legacy_bpagj_sanitize_policy( $raw ) {
	$value   = is_scalar( $raw ) ? sanitize_text_field( (string) $raw ) : '';
	$allowed = array( 'none', 'all_members', 'bp_member_types' );
	return in_array( $value, $allowed, true ) ? $value : '';
}

/**
 * Tell the groups auto-bridge to skip the auto-join metabox — its fields are
 * registered manually below, and re-bridging them would surface duplicate
 * radios.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string[] $skip Metabox ids the auto-bridge should ignore.
 * @return string[] Updated list.
 */
function bb_legacy_bpagj_skip_auto_bridge( $skip ) {
	$skip   = (array) $skip;
	$skip[] = 'bp_group_auto_join_member';
	return $skip;
}
add_filter( 'bb_legacy_meta_box_bridge_skip_groups', 'bb_legacy_bpagj_skip_auto_bridge' );

/**
 * Register the 4 auto-join fields directly on the registry.
 *
 * Hierarchy mirrors the metabox layout:
 *   - "Join New Registrations" → aj_new_registrations (radio)
 *     + aj_new_registrations_mt (checkbox_list, BMT-gated, conditional)
 *   - "Join Existing Members"  → aj_existing_users (radio)
 *     + aj_existing_users_mt (checkbox_list, BMT-gated, conditional)
 *
 * Order numbers start at 6000 — the auto-bridge starts at 5000 and increments
 * per parsed input, so 6000 reliably places these after any other plugin's
 * bridged metabox fields. The classic editor placed the auto-join box near
 * the bottom of the group admin screen; we replicate that visually.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param BB_Admin_Meta_Field_Registry $registry  Registry instance.
 * @param string                       $component Component identifier.
 */
function bb_legacy_bpagj_register_fields( $registry, $component ) {
	if ( 'groups' !== $component ) {
		return;
	}

	$policy_options          = array(
		array(
			'value' => 'all_members',
			'label' => __( 'All new registrations', 'buddyboss' ),
		),
		array(
			'value' => 'bp_member_types',
			'label' => __( 'Select by member type', 'buddyboss' ),
		),
		array(
			'value' => 'none',
			'label' => __( 'None', 'buddyboss' ),
		),
	);
	$existing_policy_options = $policy_options;
	// First option label differs between the two radios on the legacy screen.
	$existing_policy_options[0]['label'] = __( 'All existing members', 'buddyboss' );

	$bmt_enabled  = bb_legacy_bpagj_member_types_enabled();
	$mt_options   = bb_legacy_bpagj_member_type_options();
	$has_mt_terms = ! empty( $mt_options );

	// Drop the "Select by member type" radio entry when BMT support is off,
	// so the React UI shows the same two options the legacy screen does in
	// that mode.
	if ( ! $bmt_enabled ) {
		$policy_options          = array( $policy_options[0], $policy_options[2] );
		$existing_policy_options = array( $existing_policy_options[0], $existing_policy_options[2] );
	}

	$group_label = __( 'Auto-Join Users to this Group', 'buddyboss' );

	// Anchor every field to the modal's `details` tab. The auto-bridge default
	// for groups is `details` (see `groups/legacy-meta-bridge.php` —
	// `apply_filters( 'bb_legacy_meta_field_tab', 'details', … )`); the React
	// GroupEditModal filters its content per-tab and orphans fields whose
	// `tab` value doesn't match a known tab key, so this is mandatory.
	$tab = (string) apply_filters( 'bb_legacy_bpagj_field_tab', 'details' );

	$registry->register(
		$component,
		'bpagj_aj_new_registrations',
		array(
			'label'             => __( 'Join New Registrations', 'buddyboss' ),
			'type'              => 'radio',
			'order'             => 6000,
			'context'           => 'after',
			'tab'               => $tab,
			'field_group'       => 'bp_group_auto_join_member',
			'field_group_label' => $group_label,
			'sanitize_callback' => 'bb_legacy_bpagj_sanitize_policy',
			'get_value'         => function ( $group ) {
				$group_id = is_object( $group ) && isset( $group->id ) ? (int) $group->id : 0;
				if ( ! $group_id ) {
					return '';
				}
				$value = groups_get_groupmeta( $group_id, 'aj_new_registrations', true );
				return '' !== $value ? (string) $value : 'none';
			},
			'get_options'       => function () use ( $policy_options ) {
				return $policy_options;
			},
			'save_value'        => function ( $group, $value ) {
				$group_id = is_object( $group ) && isset( $group->id ) ? (int) $group->id : 0;
				if ( ! $group_id ) {
					return;
				}
				if ( '' === (string) $value || 'none' === $value ) {
					groups_delete_groupmeta( $group_id, 'aj_new_registrations' );
					groups_delete_groupmeta( $group_id, 'aj_new_registrations_mt' );
					return;
				}
				groups_update_groupmeta( $group_id, 'aj_new_registrations', $value );
				// Honor the legacy clear-on-policy-switch contract.
				if ( 'bp_member_types' !== $value ) {
					groups_delete_groupmeta( $group_id, 'aj_new_registrations_mt' );
				}
			},
		)
	);

	if ( $bmt_enabled && $has_mt_terms ) {
		$registry->register(
			$component,
			'bpagj_aj_new_registrations_mt',
			array(
				'label'             => __( 'Member types for new registrations', 'buddyboss' ),
				'description'       => __( 'Choose which member types are auto-joined to this group when they register.', 'buddyboss' ),
				'type'              => 'toggle_list',
				'order'             => 6010,
				'context'           => 'after',
				'tab'               => $tab,
				'field_group'       => 'bp_group_auto_join_member',
				'field_group_label' => $group_label,
				'sanitize_callback' => 'bb_legacy_bpagj_sanitize_member_types',
				'conditional'       => array(
					'field' => 'bpagj_aj_new_registrations',
					'value' => 'bp_member_types',
				),
				'get_value'         => function ( $group ) {
					$group_id = is_object( $group ) && isset( $group->id ) ? (int) $group->id : 0;
					if ( ! $group_id ) {
						return array();
					}
					return bb_legacy_bpagj_meta_to_toggle_list(
						groups_get_groupmeta( $group_id, 'aj_new_registrations_mt', true )
					);
				},
				'get_options'       => function () use ( $mt_options ) {
					return $mt_options;
				},
				'save_value'        => function ( $group, $value ) {
					// `$value` is already passed through `sanitize_callback` (our
					// `bb_legacy_bpagj_sanitize_member_types`), so by the time it
					// arrives here it is a clean flat array of allowed slugs.
					$group_id = is_object( $group ) && isset( $group->id ) ? (int) $group->id : 0;
					if ( ! $group_id ) {
						return;
					}
					if ( empty( $value ) ) {
						groups_delete_groupmeta( $group_id, 'aj_new_registrations_mt' );
						return;
					}
					groups_update_groupmeta( $group_id, 'aj_new_registrations_mt', $value );
				},
			)
		);
	}

	$registry->register(
		$component,
		'bpagj_aj_existing_users',
		array(
			'label'             => __( 'Join Existing Members', 'buddyboss' ),
			'description'       => __( 'Selecting "All existing members" or a set of member types will enroll matching users into this group on save.', 'buddyboss' ),
			'type'              => 'radio',
			'order'             => 6020,
			'context'           => 'after',
			'tab'               => $tab,
			'field_group'       => 'bp_group_auto_join_member',
			'field_group_label' => $group_label,
			'sanitize_callback' => 'bb_legacy_bpagj_sanitize_policy',
			'get_value'         => function ( $group ) {
				$group_id = is_object( $group ) && isset( $group->id ) ? (int) $group->id : 0;
				if ( ! $group_id ) {
					return '';
				}
				$value = groups_get_groupmeta( $group_id, 'aj_existing_users', true );
				return '' !== $value ? (string) $value : 'none';
			},
			'get_options'       => function () use ( $existing_policy_options ) {
				return $existing_policy_options;
			},
			'save_value'        => function ( $group, $value ) {
				$group_id = is_object( $group ) && isset( $group->id ) ? (int) $group->id : 0;
				if ( ! $group_id ) {
					return;
				}
				if ( '' === (string) $value || 'none' === $value ) {
					groups_delete_groupmeta( $group_id, 'aj_existing_users' );
					groups_delete_groupmeta( $group_id, 'aj_existing_users_mt' );
					return;
				}
				groups_update_groupmeta( $group_id, 'aj_existing_users', $value );
				if ( 'bp_member_types' !== $value ) {
					groups_delete_groupmeta( $group_id, 'aj_existing_users_mt' );
				}
				// NOTE: the actual enrollment dispatch happens on
				// `bb_admin_after_save_group` so it runs once after BOTH
				// the policy and the member-type list are persisted —
				// running it here would race against the sibling field's
				// save_value and could enroll using a stale member-type set.
			},
		)
	);

	if ( $bmt_enabled && $has_mt_terms ) {
		$registry->register(
			$component,
			'bpagj_aj_existing_users_mt',
			array(
				'label'             => __( 'Member types for existing members', 'buddyboss' ),
				'description'       => __( 'Choose which member types are enrolled into this group on save.', 'buddyboss' ),
				'type'              => 'toggle_list',
				'order'             => 6030,
				'context'           => 'after',
				'tab'               => $tab,
				'field_group'       => 'bp_group_auto_join_member',
				'field_group_label' => $group_label,
				'sanitize_callback' => 'bb_legacy_bpagj_sanitize_member_types',
				'conditional'       => array(
					'field' => 'bpagj_aj_existing_users',
					'value' => 'bp_member_types',
				),
				'get_value'         => function ( $group ) {
					$group_id = is_object( $group ) && isset( $group->id ) ? (int) $group->id : 0;
					if ( ! $group_id ) {
						return array();
					}
					return bb_legacy_bpagj_meta_to_toggle_list(
						groups_get_groupmeta( $group_id, 'aj_existing_users_mt', true )
					);
				},
				'get_options'       => function () use ( $mt_options ) {
					return $mt_options;
				},
				'save_value'        => function ( $group, $value ) {
					// `$value` is already passed through `sanitize_callback`.
					$group_id = is_object( $group ) && isset( $group->id ) ? (int) $group->id : 0;
					if ( ! $group_id ) {
						return;
					}
					if ( empty( $value ) ) {
						groups_delete_groupmeta( $group_id, 'aj_existing_users_mt' );
						return;
					}
					groups_update_groupmeta( $group_id, 'aj_existing_users_mt', $value );
				},
			)
		);
	}
}
add_action( 'bb_register_groups_meta_fields', 'bb_legacy_bpagj_register_fields', 1000, 2 );

/**
 * Replay the legacy enrollment side-effect after the React save commits.
 *
 * The classic metabox dispatches `bp_auto_group_join_all_members()` directly
 * inside its save handler when the "Existing Members" policy is set to
 * `all_members` or `bp_member_types`. Our `save_value` closures only persist
 * the meta — the actual user enrollment happens here, once both the policy
 * radio and the member-type set are committed to the database.
 *
 * Runs at a late priority so it follows other `bb_admin_after_save_group`
 * listeners (e.g. the legacy bridge's extension-save replay at priority 5).
 *
 * Idempotency: `bp_auto_group_join_all_members()` checks each candidate's
 * existing `BP_Groups_Member` row before joining, so re-running it on a
 * group edit that didn't change the auto-join settings is a no-op for
 * already-enrolled users.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int             $group_id Saved group ID.
 * @param BP_Groups_Group $group    Saved group object (unused).
 */
function bb_legacy_bpagj_dispatch_existing_users_enrollment( $group_id, $group ) {
	unset( $group );

	$group_id = (int) $group_id;
	if ( $group_id <= 0 ) {
		return;
	}
	if ( ! function_exists( 'bp_auto_group_join_all_members' ) ) {
		return;
	}

	$policy = (string) groups_get_groupmeta( $group_id, 'aj_existing_users', true );
	if ( 'all_members' === $policy ) {
		bp_auto_group_join_all_members( $group_id );
		return;
	}

	if ( 'bp_member_types' === $policy ) {
		$types = groups_get_groupmeta( $group_id, 'aj_existing_users_mt', true );
		$types = is_array( $types ) ? array_values( array_filter( array_map( 'strval', $types ) ) ) : array();
		if ( ! empty( $types ) ) {
			bp_auto_group_join_all_members( $group_id, $types );
		}
	}
}
add_action( 'bb_admin_after_save_group', 'bb_legacy_bpagj_dispatch_existing_users_enrollment', 50, 2 );
