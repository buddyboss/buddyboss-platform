<?php
/**
 * WP Fusion compatibility module for the Settings 2.0 legacy-meta bridge.
 *
 * Makes WP Fusion's post/CPT access-control metabox fully usable inside the
 * Settings 2.0 edit modals (forums, topics, replies, and any other bridged
 * CPT). WP Fusion is not bridged with any plugin-specific code in the engine —
 * everything plugin-specific lives here and registers through the engine's
 * generic extension filters:
 *
 *   - `bb_legacy_ajax_select_resolvers` — teaches the bridge how to search and
 *     label WP Fusion's two select2/AJAX widgets (tag pickers, page redirect).
 *   - `bb_legacy_field_overrides`       — replicates the metabox's conditional
 *     gating (fields that WP Fusion disables until "Users must be logged in"
 *     is checked) and refines a couple of field types.
 *
 * Loaded once from the bottom of legacy-meta-bridge-utils.php.
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
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss [BBVERSION]
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
 * @since BuddyBoss [BBVERSION]
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
 * @since BuddyBoss [BBVERSION]
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
 * @since BuddyBoss [BBVERSION]
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
 * @since BuddyBoss [BBVERSION]
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
