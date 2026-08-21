<?php
/**
 * WP Fusion compatibility module for the Settings 2.0 legacy-meta bridge.
 *
 * Makes WP Fusion's post/CPT access-control metabox fully usable inside the
 * Settings 2.0 edit modals (forums, topics, replies, and any other bridged
 * CPT), AND registers WP Fusion's BuddyPress **group** metabox
 * ("WP Fusion - Group Settings") directly against `BB_Admin_Meta_Field_Registry`
 * so it appears in the Settings 2.0 group edit modal. Two different mechanisms
 * for two different reasons — see the group section below for why the CPT
 * approach (generic auto-bridge + resolver filters) doesn't work for groups.
 *
 * --- CPT metabox (`wpf-meta`) -----------------------------------------------
 *
 * Bridged automatically by the generic engine (CPT path); this module only
 * teaches it WP Fusion-specific behaviour via two extension filters:
 *
 *   - `bb_legacy_ajax_select_resolvers` — teaches the bridge how to search and
 *     label WP Fusion's two select2/AJAX widgets (tag pickers, page redirect).
 *   - `bb_legacy_field_overrides`       — replicates the metabox's conditional
 *     gating (fields that WP Fusion disables until "Users must be logged in"
 *     is checked) and refines a couple of field types.
 *
 * WP Fusion field inventory (name => bridged behaviour):
 *   wpf-settings[lock_content]      checkbox — the gate; enables the rest
 *   wpf-settings[lock_posts]        checkbox — gated
 *   wpf-settings[hide_term]         checkbox — gated
 *   wpf-settings[allow_tags][]      tag multiselect (ajax_multiselect) — gated
 *   wpf-settings[allow_tags_all][]  tag multiselect — gated
 *   wpf-settings[allow_tags_not][]  tag multiselect — gated
 *   wpf-settings[redirect]          page search (async_select) — gated
 *   wpf-settings[redirect_url]      URL text — gated
 *   wpf-settings[check_tags]        checkbox ("Refresh access if denied") — gated
 *   wpf-settings[apply_tags][]      tag multiselect — independent (apply on view)
 *   wpf-settings[apply_delay]       number (ms) — independent
 *   wpf-settings[message]           textarea (custom restricted message) — independent
 *
 * --- Group metabox (`wpf-buddypress-meta`) ----------------------------------
 *
 * WP Fusion's group box (`WPF_BuddyPress::add_meta_box_groups()` /
 * `meta_box_callback_groups()` in `includes/integrations/class-buddypress.php`)
 * renders three `<select multiple class="select4-wpf-tags" name="wpf-settings-
 * buddypress[<key>][]">` tag pickers — Apply Tags, Link with Tag, Link with Tag
 * - Group Organizer — all three leaves of ONE groupmeta array stored under the
 * single key `wpf-settings-buddypress`. Because every input name is
 * array-notation, the generic groups auto-bridge
 * (`groups/legacy-meta-bridge.php`) silently rejects all three via
 * `bb_legacy_is_safe_post_key()` (see that file's "Known limitations" —
 * array-shape reassembly only exists on the CPT path, not the groups path).
 * So — same reasoning as `compat/bp-auto-group-join.php` — the fields are
 * registered manually here via `bb_register_groups_meta_fields`, reusing the
 * `wpf_tags` resolver already registered above for the CPT path (same CRM tag
 * list, same create-tag flow) so both surfaces search/label/create identically.
 *
 * Field inventory (all three read/write the single `wpf-settings-buddypress`
 * groupmeta array; PHP key in that array => modal field):
 *   apply_tags     Apply Tags                        (multi, unlimited)
 *   tag_link       Link with Tag                     (single tag; see note below)
 *   organizer_tag  Link with Tag - Group Organizer    (single tag; see note below)
 *
 * Single-tag note: the classic select2 enforces its "pick one" limit purely
 * client-side (`data-limit="1"` read by WP Fusion's own admin JS).
 * `AjaxMultiSelectField` (the React component `ajax_multiselect` renders) has
 * no such cap, so `tag_link`/`organizer_tag` sanitize down to at most one
 * element server-side (`bb_legacy_wpf_sanitize_group_tag_ids()`) — a user
 * could transiently see more than one chip selected before saving, but only
 * the most-recently-added tag persists.
 *
 * Loaded once from the bottom of legacy-meta-bridge-utils.php, gated by the
 * same `WP_FUSION_VERSION`/`WPF_VERSION`/`wp_fusion()` detection used for the
 * CPT half — both halves live in one file because they're one plugin.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register WP Fusion's AJAX-select resolvers with the bridge.
 *
 * - `wpf_tags`     : the tag pickers (`<select multiple class="select4-wpf-tags">`),
 *                    bridged as a searchable `ajax_multiselect`. Searches the
 *                    synced tag list; labels resolve via `wpf_get_tag_label()`.
 * - `wpf_redirect` : the page redirect picker (`<select class="select4-select-page">`),
 *                    bridged as `async_select`. Searches published posts/pages;
 *                    a saved numeric value resolves to its title, a URL shows
 *                    verbatim.
 *
 * @since BuddyBoss 3.1.0
 *
 * @param array $resolvers Existing resolver map.
 * @return array Resolver map with WP Fusion entries added.
 */
function bb_legacy_wpf_register_ajax_resolvers( $resolvers ) {
	$resolvers['wpf_tags'] = array(
		'match'         => 'select4-wpf-tags',
		'placeholder'   => __( 'Select tags', 'buddyboss' ),
		'search'        => function ( $query, $page ) {
			unset( $page ); // Tag lists are small — returned unpaginated.
			if ( ! function_exists( 'wp_fusion' ) || empty( wp_fusion()->settings ) ) {
				return array();
			}
			$tags = wp_fusion()->settings->get_available_tags_flat();
			if ( ! is_array( $tags ) ) {
				return array();
			}
			$out = array();
			foreach ( $tags as $id => $label ) {
				if ( '' !== $query && false === stripos( (string) $label, $query ) ) {
					continue;
				}
				$out[] = array(
					'value' => (string) $id,
					'label' => (string) $label,
				);
			}
			return $out;
		},
		'resolve_label' => function ( $value ) {
			return function_exists( 'wpf_get_tag_label' ) ? (string) wpf_get_tag_label( $value ) : (string) $value;
		},
	);

	// Mirror WP Fusion's metabox: a "Create <term>" row is offered only when the
	// active CRM can accept new tags — `add_tags` (typed string becomes the id)
	// or `add_tags_api` (CRM mints the id). CRMs supporting neither stay
	// search-only, exactly as the classic select2 does (it shows "resync"
	// instead of a create option).
	if ( bb_legacy_wpf_crm_supports_tag_create() ) {
		$resolvers['wpf_tags']['allow_create']  = true;
		$resolvers['wpf_tags']['create_action'] = 'bb_legacy_wpf_create_tag';
		/* translators: %s: the tag name the admin typed. */
		$resolvers['wpf_tags']['create_label'] = __( 'Create "%s"', 'buddyboss' );
	}

	// Shared between this resolver's `search` and `has_more` closures so the page
	// count from the single WP_Query is reused without a second query — and
	// without leaning on request-global ($GLOBALS) state to pass it between them.
	$redirect_max_pages = 1;

	$resolvers['wpf_redirect'] = array(
		'match'         => 'select4-select-page',
		'placeholder'   => __( 'Select a page', 'buddyboss' ),
		'search'        => function ( $query, $page ) use ( &$redirect_max_pages ) {
			$wp_query = new WP_Query(
				array(
					'post_type'              => 'any',
					'post_status'            => 'publish',
					'posts_per_page'         => 20,
					'paged'                  => max( 1, (int) $page ),
					's'                      => $query,
					'orderby'                => 'title',
					'order'                  => 'ASC',
					'no_found_rows'          => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);
			$out = array();
			foreach ( $wp_query->posts as $post ) {
				$out[] = array(
					'value' => (string) $post->ID,
					'label' => $post->post_title,
				);
			}
			// Stash the page count for has_more without a second query.
			$redirect_max_pages = (int) $wp_query->max_num_pages;
			return $out;
		},
		'has_more'      => function ( $query, $page, $matches ) use ( &$redirect_max_pages ) {
			unset( $query, $matches ); // Paging decided by WP_Query max pages.
			return (int) $page < ( $redirect_max_pages > 0 ? $redirect_max_pages : 1 );
		},
		'resolve_label' => function ( $value ) {
			// WP Fusion stores a post ID or a raw URL.
			return is_numeric( $value ) ? (string) get_the_title( (int) $value ) : (string) $value;
		},
	);

	return $resolvers;
}
add_filter( 'bb_legacy_ajax_select_resolvers', 'bb_legacy_wpf_register_ajax_resolvers' );

/**
 * Replicate WP Fusion's metabox conditional gating in the Settings 2.0 modal.
 *
 * In the classic metabox a `data-unlock` attribute on the "Users must be logged
 * in" checkbox (`wpf-settings[lock_content]`) enables the access-restriction
 * fields; until it is checked they render disabled. Captured as static HTML the
 * bridge sees them as ordinary always-enabled inputs, so we re-attach that gate
 * here: each dependent field gets a `conditional` of
 * `{ field: lock_content, action: 'disable', value: truthy }` so it greys out
 * (but stays present, and its value still persists) when the gate is off.
 *
 * Also forces `redirect_url` to the `url` field type so it renders/validates as
 * a URL rather than plain text.
 *
 * Only applies to the WP Fusion metabox (`wpf-meta`); other metaboxes pass
 * through untouched.
 *
 * @since BuddyBoss 3.1.0
 *
 * @param array  $overrides Existing per-field overrides (keyed by raw $_POST name).
 * @param string $box_id    The metabox id being bridged.
 * @param string $post_type The post type being edited.
 * @return array Overrides with WP Fusion gating added when relevant.
 */
function bb_legacy_wpf_field_overrides( $overrides, $box_id, $post_type ) {
	unset( $post_type ); // WP Fusion's metabox is identical across post types.

	// WP Fusion registers its box as `wpf-meta` on every public post type.
	if ( 'wpf-meta' !== $box_id ) {
		return $overrides;
	}

	// The gate. Dependent fields disable (grey out) until this is checked.
	$gate = array(
		'field'  => 'wpf-settings[lock_content]',
		'value'  => true,
		'action' => 'disable',
	);

	// Fields the POST metabox disables while lock_content is unchecked — exactly
	// the two named in its data-unlock attribute
	// (WP_Fusion_Admin_Interfaces::restrict_content_checkbox):
	//   data-unlock="wpf-settings-allow_tags wpf-settings-allow_tags_all".
	// "Required tags (not)" and "Redirect if access is denied" render always-enabled
	// in the post metabox, so they are deliberately NOT gated here. (lock_posts /
	// hide_term / redirect_url belong to the taxonomy-term metabox, never the post
	// metabox.)
	$gated = array(
		'wpf-settings[allow_tags][]',
		'wpf-settings[allow_tags_all][]',
	);

	foreach ( $gated as $name ) {
		if ( ! isset( $overrides[ $name ] ) || ! is_array( $overrides[ $name ] ) ) {
			$overrides[ $name ] = array();
		}
		$overrides[ $name ]['conditional'] = $gate;
	}

	// "Refresh access if denied" (check_tags) is gated differently in WP Fusion: it
	// is disabled until at least one required tag is selected — i.e. while BOTH
	// allow_tags AND allow_tags_all are empty — independent of lock_content
	// (wpf-admin.js:685-701; mirrored server-side in force_check_tags_checkbox()).
	// Expressed as a multi-field "any non-empty" disable conditional so the modal
	// greys it out under exactly the same condition the classic metabox does.
	if ( ! isset( $overrides['wpf-settings[check_tags]'] ) || ! is_array( $overrides['wpf-settings[check_tags]'] ) ) {
		$overrides['wpf-settings[check_tags]'] = array();
	}
	$overrides['wpf-settings[check_tags]']['conditional'] = array(
		'fields'  => array(
			'wpf-settings[allow_tags][]',
			'wpf-settings[allow_tags_all][]',
		),
		'compare' => 'any_non_empty',
		'action'  => 'disable',
	);

	// The "Or enter a URL" field is a plain text input in the metabox markup;
	// render it as a URL field in the modal.
	if ( ! isset( $overrides['wpf-settings[redirect_url]'] ) || ! is_array( $overrides['wpf-settings[redirect_url]'] ) ) {
		$overrides['wpf-settings[redirect_url]'] = array();
	}
	$overrides['wpf-settings[redirect_url]']['type'] = 'url';

	return $overrides;
}
add_filter( 'bb_legacy_field_overrides', 'bb_legacy_wpf_field_overrides', 10, 3 );

/**
 * Whether the active CRM can accept new tags created from the UI.
 *
 * Mirrors WP Fusion's own gating: `add_tags` (the typed string is the tag id,
 * created in the CRM lazily on first apply) or `add_tags_api` (the CRM mints an
 * id immediately via add_tag()). Anything else is search-only.
 *
 * @since BuddyBoss 3.1.0
 *
 * @return bool True when tag creation is supported.
 */
function bb_legacy_wpf_crm_supports_tag_create() {
	if ( ! function_exists( 'wp_fusion' ) || empty( wp_fusion()->crm ) ) {
		return false;
	}
	$supports = isset( wp_fusion()->crm->supports ) ? (array) wp_fusion()->crm->supports : array();
	return in_array( 'add_tags', $supports, true ) || in_array( 'add_tags_api', $supports, true );
}

/**
 * AJAX: create a new WP Fusion tag from a typed name and return its {value,label}.
 *
 * Replicates WP Fusion's classic-metabox "type + Enter creates the tag" flow
 * (its REST `update_available_tags`): for `add_tags_api` CRMs the id is minted
 * via `add_tag()`; for `add_tags` CRMs the typed string IS the id and the tag
 * materialises in the CRM when first applied to a contact. Either way the name
 * is appended to the local `available_tags` option so it shows in later
 * searches. Consumed by AjaxMultiSelectField's create row (it sends `term` and
 * the `bb_admin_settings` nonce via ajaxFetch, and reads `data.value`/`data.label`).
 *
 * Auth: `bp_moderate` + the `bb_admin_settings` nonce — same boundary as the
 * search shim.
 *
 * @since BuddyBoss 3.1.0
 *
 * @return void
 */
function bb_legacy_wpf_create_tag() {
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		wp_send_json_error( array( 'message' => __( 'Unauthorized', 'buddyboss' ) ), 403 );
	}

	check_ajax_referer( 'bb_admin_settings', 'nonce' );

	if ( ! bb_legacy_wpf_crm_supports_tag_create() ) {
		wp_send_json_error( array( 'message' => __( 'This CRM does not support creating tags.', 'buddyboss' ) ), 400 );
	}

	$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
	$term = trim( $term );
	if ( '' === $term ) {
		wp_send_json_error( array( 'message' => __( 'Tag name is empty.', 'buddyboss' ) ), 400 );
	}

	$crm      = wp_fusion()->crm;
	$supports = isset( $crm->supports ) ? (array) $crm->supports : array();
	$tag_id   = $term;

	// API-backed CRMs mint the id; bail if the CRM rejects the create.
	if ( in_array( 'add_tags_api', $supports, true ) && method_exists( $crm, 'add_tag' ) ) {
		$created = $crm->add_tag( $term );
		if ( is_wp_error( $created ) ) {
			// Never surface the raw WP_Error message — CRM drivers can leak
			// API endpoint paths, tokens substrings, or debug data. Log the
			// error code only, gated behind WP_DEBUG, and return a generic
			// message to the React UI.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Gated behind WP_DEBUG; intentional debug-only log.
				error_log( 'bb_legacy_wpf_create_tag: ' . $created->get_error_code() );
			}
			wp_send_json_error( array( 'message' => __( 'Failed to create tag.', 'buddyboss' ) ), 400 );
		}
		$tag_id = (string) $created;
	}

	// Register the name locally so it appears in future tag searches (matches
	// WP Fusion's update_available_tags). Keyed by id for add_tags_api, or by
	// the string itself for add_tags. The wpf_get_option / wpf_update_option
	// helpers are pluggable in WP Fusion — guard against them being missing
	// (e.g. partial-load states, future refactors) so the endpoint degrades
	// gracefully rather than fatally on PHP 8.x. The CRM-side write above has
	// already succeeded at this point; only the local index update is skipped.
	if ( function_exists( 'wpf_get_option' ) && function_exists( 'wpf_update_option' ) ) {
		$available = (array) wpf_get_option( 'available_tags', array() );
		if ( ! isset( $available[ $tag_id ] ) && ! in_array( $term, $available, true ) ) {
			$available[ $tag_id ] = $term;
			wpf_update_option( 'available_tags', $available );
		}
	}

	wp_send_json_success(
		array(
			'value' => (string) $tag_id,
			'label' => $term,
		)
	);
}
add_action( 'wp_ajax_bb_legacy_wpf_create_tag', 'bb_legacy_wpf_create_tag' );

/**
 * Tell the groups auto-bridge to skip WP Fusion's group metabox.
 *
 * The auto-bridge would otherwise still walk `wpf-buddypress-meta` and find
 * zero safe inputs (every leaf is array-notation — see file docblock), which
 * is harmless but wasteful (an HTML capture + parse for nothing every hour).
 * Skipping it outright also keeps a would-be-empty "WP Fusion - Group
 * Settings" section from ever appearing if the reject logic's behaviour
 * changes in the future.
 *
 * @since BuddyBoss 3.1.0
 *
 * @param string[] $skip Metabox ids the auto-bridge should ignore.
 * @return string[] Updated list.
 */
function bb_legacy_wpf_skip_group_auto_bridge( $skip ) {
	$skip   = (array) $skip;
	$skip[] = 'wpf-buddypress-meta';
	return $skip;
}
add_filter( 'bb_legacy_meta_box_bridge_skip_groups', 'bb_legacy_wpf_skip_group_auto_bridge' );

/**
 * Read the WP Fusion group settings array for a group, merged over defaults
 * so every key is always present regardless of what's actually stored.
 *
 * Mirrors the default-merge in `WPF_BuddyPress::meta_box_callback_groups()`.
 *
 * @since BuddyBoss 3.1.0
 *
 * @param int $group_id Group ID.
 * @return array Map with 'apply_tags', 'tag_link', 'organizer_tag' keys, each a flat array of tag ids.
 */
function bb_legacy_wpf_group_settings( $group_id ) {
	$defaults = array(
		'apply_tags'    => array(),
		'tag_link'      => array(),
		'organizer_tag' => array(),
	);

	$group_id = (int) $group_id;
	if ( $group_id <= 0 ) {
		return $defaults;
	}

	$stored = groups_get_groupmeta( $group_id, 'wpf-settings-buddypress' );

	return is_array( $stored ) ? array_merge( $defaults, $stored ) : $defaults;
}

/**
 * Write one leaf of the WP Fusion group settings array back to the single
 * `wpf-settings-buddypress` groupmeta key, preserving whatever sibling leaves
 * are already stored (each of the 3 fields calls this independently during
 * the same save request — read-modify-write per call is safe because
 * `save_fields_data()` iterates fields synchronously within one request).
 *
 * @since BuddyBoss 3.1.0
 *
 * @param int    $group_id Group ID.
 * @param string $key      One of 'apply_tags', 'tag_link', 'organizer_tag'.
 * @param array  $value    Sanitized flat array of tag ids.
 */
function bb_legacy_wpf_save_group_setting( $group_id, $key, $value ) {
	$group_id = (int) $group_id;
	if ( $group_id <= 0 ) {
		return;
	}

	$settings         = bb_legacy_wpf_group_settings( $group_id );
	$settings[ $key ] = array_values( (array) $value );

	// Match the legacy handler's own empty-settings cleanup
	// (`WPF_BuddyPress::save_groups_data()`): once every leaf is empty, delete
	// the row entirely instead of leaving `a:3:{...}` of empty arrays behind.
	if ( empty( array_filter( $settings ) ) ) {
		groups_delete_groupmeta( $group_id, 'wpf-settings-buddypress' );
		return;
	}

	groups_update_groupmeta( $group_id, 'wpf-settings-buddypress', $settings );
}

/**
 * Sanitize a tag-id array from `AjaxMultiSelectField`.
 *
 * CRM tag ids aren't always numeric (e.g. `add_tags` CRMs use the typed
 * string itself as the id), so this only trims/de-dupes/drops-empties rather
 * than forcing `absint()`. `$max` mirrors the classic select2's `data-limit`
 * for the two single-tag fields (see file docblock "Single-tag note") — when
 * set, only the last `$max` entries are kept, matching a user replacing their
 * one selection with a newer pick rather than being blocked from picking at
 * all once one tag is chosen.
 *
 * @since BuddyBoss 3.1.0
 *
 * @param mixed    $raw Raw value from `registered_field_*` POST.
 * @param int|null $max Optional cap on the number of ids kept.
 * @return string[] Sanitized, unique tag ids.
 */
function bb_legacy_wpf_sanitize_group_tag_ids( $raw, $max = null ) {
	if ( ! is_array( $raw ) ) {
		$raw = ( '' === $raw || null === $raw ) ? array() : array( $raw );
	}

	$out = array();
	foreach ( $raw as $value ) {
		if ( ! is_scalar( $value ) ) {
			continue;
		}
		$value = trim( (string) sanitize_text_field( $value ) );
		if ( '' !== $value ) {
			$out[] = $value;
		}
	}

	$out = array_values( array_unique( $out ) );

	if ( null !== $max && $max > 0 && count( $out ) > $max ) {
		$out = array_slice( $out, -$max );
	}

	return $out;
}

/**
 * Build the `get_extra_data` payload shared by all three tag-picker fields —
 * identical shape to what the CPT bridge's `ajax_multiselect` fields receive
 * (see `legacy-meta-bridge-utils.php`), so `AjaxMultiSelectField` behaves the
 * same way on both surfaces.
 *
 * @since BuddyBoss 3.1.0
 *
 * @param string[] $saved Currently saved tag ids for this field.
 * @return array Extra data for the `ajax_multiselect` field type.
 */
function bb_legacy_wpf_group_tag_extra_data( $saved ) {
	$extra = array(
		'ajax_action'        => 'bb_legacy_ajax_select_search',
		'ajax_nonce'         => wp_create_nonce( 'bb_admin_settings' ),
		'nonce_param'        => 'nonce',
		'search_placeholder' => __( 'Select tags', 'buddyboss' ),
		'string_ids'         => true,
		'resolver'           => 'wpf_tags',
		'selected_items'     => array(),
	);

	if ( bb_legacy_wpf_crm_supports_tag_create() ) {
		$extra['allow_create']  = true;
		$extra['create_action'] = 'bb_legacy_wpf_create_tag';
		/* translators: %s: the tag name the admin typed. */
		$extra['create_label'] = __( 'Create "%s"', 'buddyboss' );
	}

	foreach ( (array) $saved as $tag_id ) {
		$extra['selected_items'][] = array(
			'value' => (string) $tag_id,
			'label' => function_exists( 'wpf_get_tag_label' ) ? (string) wpf_get_tag_label( $tag_id ) : (string) $tag_id,
		);
	}

	return $extra;
}

/**
 * Register the 3 WP Fusion group fields directly on the registry.
 *
 * All three share `field_group => 'wpf-buddypress-meta'` so the modal renders
 * them together under one "WP Fusion - Group Settings" heading, matching the
 * classic metabox's title verbatim (`WPF_BuddyPress::add_meta_box_groups()`).
 *
 * Order numbers start at 6100 — clear of core (order 100) and after
 * `bp-auto-group-join`'s 6000-6030 block, so if both plugins are active their
 * sections appear in a stable, predictable sequence.
 *
 * @since BuddyBoss 3.1.0
 *
 * @param BB_Admin_Meta_Field_Registry $registry  Registry instance.
 * @param string                       $component Component identifier.
 */
function bb_legacy_wpf_register_group_fields( $registry, $component ) {
	if ( 'groups' !== $component ) {
		return;
	}

	if ( ! function_exists( 'groups_get_groupmeta' ) ) {
		return;
	}

	$group_label = 'WP Fusion - Group Settings';

	// Anchor to the modal's `details` tab — same default the groups auto-bridge
	// uses (`bb_legacy_meta_field_tab`), and the tab GroupEditModal always
	// renders, so a field never gets orphaned under an unknown tab key.
	$tab = 'details';

	$registry->register(
		$component,
		'wpf_group_apply_tags',
		array(
			'label'             => __( 'Apply Tags', 'buddyboss' ),
			'description'       => __( 'Select tags to apply when a user joins this group.', 'buddyboss' ),
			'type'              => 'ajax_multiselect',
			'order'             => 6100,
			'context'           => 'after',
			'tab'               => $tab,
			'field_group'       => 'wpf-buddypress-meta',
			'field_group_label' => $group_label,
			'sanitize_callback' => 'bb_legacy_wpf_sanitize_group_tag_ids',
			'get_value'         => function ( $group ) {
				$group_id = is_object( $group ) && isset( $group->id ) ? (int) $group->id : 0;
				return bb_legacy_wpf_group_settings( $group_id )['apply_tags'];
			},
			'get_extra_data'    => function ( $group ) {
				$group_id = is_object( $group ) && isset( $group->id ) ? (int) $group->id : 0;
				return bb_legacy_wpf_group_tag_extra_data( bb_legacy_wpf_group_settings( $group_id )['apply_tags'] );
			},
			'save_value'        => function ( $group, $value ) {
				$group_id = is_object( $group ) && isset( $group->id ) ? (int) $group->id : 0;
				bb_legacy_wpf_save_group_setting( $group_id, 'apply_tags', $value );
			},
		)
	);

	$registry->register(
		$component,
		'wpf_group_tag_link',
		array(
			'label'             => __( 'Link with Tag', 'buddyboss' ),
			'description'       => __( 'Select a tag to link with this group. When the tag is applied, the user will automatically be enrolled. When the tag is removed the user will be unenrolled.', 'buddyboss' )
				. ' ' . __( 'Warning: users can choose to leave a social group. If a user leaves this group, the linked tag will be removed. For this reason it\'s recommended not to link this tag to anything else (for example a course).', 'buddyboss' ),
			'type'              => 'ajax_multiselect',
			'order'             => 6110,
			'context'           => 'after',
			'tab'               => $tab,
			'field_group'       => 'wpf-buddypress-meta',
			'field_group_label' => $group_label,
			'sanitize_callback' => function ( $raw ) {
				return bb_legacy_wpf_sanitize_group_tag_ids( $raw, 1 );
			},
			'get_value'         => function ( $group ) {
				$group_id = is_object( $group ) && isset( $group->id ) ? (int) $group->id : 0;
				return bb_legacy_wpf_group_settings( $group_id )['tag_link'];
			},
			'get_extra_data'    => function ( $group ) {
				$group_id = is_object( $group ) && isset( $group->id ) ? (int) $group->id : 0;
				return bb_legacy_wpf_group_tag_extra_data( bb_legacy_wpf_group_settings( $group_id )['tag_link'] );
			},
			'save_value'        => function ( $group, $value ) {
				$group_id = is_object( $group ) && isset( $group->id ) ? (int) $group->id : 0;
				bb_legacy_wpf_save_group_setting( $group_id, 'tag_link', $value );
			},
		)
	);

	$registry->register(
		$component,
		'wpf_group_organizer_tag',
		array(
			'label'             => __( 'Link with Tag - Group Organizer', 'buddyboss' ),
			'description'       => __( 'When the linked tag is applied, the user will automatically be added to the group and promoted to organizer. When the tag is removed, the user will be demoted from organizer to regular group member.', 'buddyboss' ),
			'type'              => 'ajax_multiselect',
			'order'             => 6120,
			'context'           => 'after',
			'tab'               => $tab,
			'field_group'       => 'wpf-buddypress-meta',
			'field_group_label' => $group_label,
			'sanitize_callback' => function ( $raw ) {
				return bb_legacy_wpf_sanitize_group_tag_ids( $raw, 1 );
			},
			'get_value'         => function ( $group ) {
				$group_id = is_object( $group ) && isset( $group->id ) ? (int) $group->id : 0;
				return bb_legacy_wpf_group_settings( $group_id )['organizer_tag'];
			},
			'get_extra_data'    => function ( $group ) {
				$group_id = is_object( $group ) && isset( $group->id ) ? (int) $group->id : 0;
				return bb_legacy_wpf_group_tag_extra_data( bb_legacy_wpf_group_settings( $group_id )['organizer_tag'] );
			},
			'save_value'        => function ( $group, $value ) {
				$group_id = is_object( $group ) && isset( $group->id ) ? (int) $group->id : 0;
				bb_legacy_wpf_save_group_setting( $group_id, 'organizer_tag', $value );
			},
		)
	);
}
add_action( 'bb_register_groups_meta_fields', 'bb_legacy_wpf_register_group_fields', 1000, 2 );
