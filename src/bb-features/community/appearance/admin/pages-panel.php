<?php
/**
 * Appearance → Pages side panel.
 *
 * Replaces the legacy `admin.php?page=bp-pages` screen. Registers two sections
 * under the `pages` side panel of the Appearance feature:
 *
 *  1. Component Pages   — one async_select dropdown per active component/
 *                         directory (members, groups, activity, media
 *                         variants, document, video, new_forums_page,
 *                         profile_dashboard).
 *  2. Registration Pages — register / terms / privacy / activate, gated on
 *                          the network's registration and custom-signup
 *                          configuration.
 *
 * Fields are registered lazily on `bb_admin_settings_before_get_feature` —
 * not on `bb_register_features` — because the set of directories depends on
 * BP component globals (`has_directory`, media-context flags, etc.) which
 * are populated *after* `bp_loaded@5`. Running the lookup on
 * `before_get_feature` guarantees every per-component option and helper is
 * safe to call.
 *
 * Storage is identical to the legacy screen:
 *   - `bp-pages` option (serialized array of component/static-page → post ID)
 *   - `_bbp_root_slug` / `_bbp_root_slug_custom_slug` for Forums (see
 *     `bb_appearance_pages_save_side_effects()` for the split logic).
 *
 * @package BuddyBoss\Features\Community\Appearance
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check whether the current blog's registration-pages section should render
 * and determine its title.
 *
 * Mirrors the three legacy branches in
 * `bp_core_admin_register_registration_page_fields()`:
 *   - hidden entirely when a custom-registration plugin owns signup
 *   - title flips to "Login Pages" when registration is off AND invites are off
 *   - otherwise titled "Registration Pages"
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array{ render:bool, title:string, description:string, show_register:bool } Flags and copy.
 */
function bb_appearance_get_registration_section_meta() {
	$meta = array(
		'render'        => true,
		'title'         => __( 'Registration Pages', 'buddyboss' ),
		'description'   => __( 'Associate a WordPress page with each of the following components.', 'buddyboss' ),
		'show_register' => true,
	);

	if ( function_exists( 'bp_allow_custom_registration' ) && bp_allow_custom_registration() ) {
		$meta['render'] = false;
		return $meta;
	}

	$reg_on     = function_exists( 'bp_enable_site_registration' ) && bp_enable_site_registration();
	$invites_on = function_exists( 'bp_is_active' ) && bp_is_active( 'invites' );

	if ( ! $reg_on && ! $invites_on ) {
		$meta['title']         = __( 'Login Pages', 'buddyboss' );
		$meta['show_register'] = false;
		$meta['description']   = __( 'Associate a WordPress page with the following Login sections.', 'buddyboss' );
	}

	return $meta;
}

/**
 * Build the static-page label for the Registration Pages section.
 *
 * Dynamic because the terms/privacy description copy depends on whether the
 * popup will appear on register+login or login-only. Also used by the save
 * whitelist via `bp_core_admin_get_static_pages()` — we only diverge on the
 * description text here.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $name Static page key (register / terms / privacy / activate).
 * @return string Field help text, or '' when no help text applies.
 */
function bb_appearance_get_static_page_description( $name ) {
	$popup_is_register_and_login = (
		( function_exists( 'bp_enable_site_registration' ) && bp_enable_site_registration() )
		|| ( function_exists( 'bp_is_active' ) && bp_is_active( 'invites' ) )
	);

	switch ( $name ) {
		case 'register':
			return __( 'New users fill out this form to register their accounts.', 'buddyboss' );
		case 'terms':
		case 'privacy':
			return $popup_is_register_and_login
				? __( 'If a page is added, its contents will display in a popup on the register and login forms.', 'buddyboss' )
				: __( 'If a page is added, its contents will display in a popup on the login form.', 'buddyboss' );
		case 'activate':
			return __( 'After registering, users are sent to this page to activate their accounts.', 'buddyboss' );
	}

	return '';
}

/**
 * Detect whether WPML is active AND the viewer is editing on a non-default
 * language. Legacy's Pages screen disabled its Save button in this state to
 * prevent the default-language-scoped `bp-pages` option being overwritten
 * with translated page IDs. Settings 2.0 auto-saves, so we use this signal
 * to (a) render a notice at the top of the panel and (b) block the save-
 * side handler from writing anything.
 *
 * Returns true ONLY when WPML is installed AND a non-default language is
 * currently selected. On non-WPML installs or when viewing in the default
 * language, returns false and the panel behaves normally.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True if saves should be blocked because WPML is on a non-default language.
 */
function bb_appearance_pages_is_wpml_non_default_language() {
	// WPML is only relevant when its main class is loaded — a common proxy
	// for "WPML is active" used throughout the BP codebase.
	if ( ! class_exists( 'SitePress' ) ) {
		return false;
	}
	if ( ! defined( 'ICL_LANGUAGE_CODE' ) ) {
		return false;
	}

	$wpml_options = get_option( 'icl_sitepress_settings' );
	if ( ! is_array( $wpml_options ) || empty( $wpml_options['default_language'] ) ) {
		return false;
	}

	return ICL_LANGUAGE_CODE !== $wpml_options['default_language'];
}

/**
 * Switch to the BuddyPress root blog if we're on a different blog, and return
 * a flag that tells the caller whether a subsequent `restore_current_blog()`
 * is required.
 *
 * Pages-panel code reads and writes posts that live on the root blog —
 * directory-page mappings, title lookups, permalinks, and page status checks
 * all target root-blog posts. `bp_get_option` / `bp_update_option` internally
 * route to the root blog, but WP post helpers (`get_the_title`,
 * `get_permalink`, `get_post_status`, `get_page_uri`) hit whichever blog is
 * current. On a sub-site admin request that's the sub-site — returning
 * sub-site pages instead of root-blog pages, or silently writing the forums
 * slug to the wrong blog's options during save.
 *
 * Centralised here so register/save/enrich paths all get the same treatment
 * without three near-identical copies of the `switch_to_blog` boilerplate.
 *
 * Returns `false` when no switch happened (single-site, already on root
 * blog, or `bp_get_root_blog_id()` unavailable) — callers use that signal to
 * decide whether to `restore_current_blog()`.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True if we switched (caller must restore), false otherwise.
 */
function bb_appearance_pages_switch_to_root_blog() {
	if ( ! is_multisite() || ! function_exists( 'bp_get_root_blog_id' ) ) {
		return false;
	}
	$root_blog_id = bp_get_root_blog_id();
	if ( ! $root_blog_id || get_current_blog_id() === $root_blog_id ) {
		return false;
	}
	switch_to_blog( $root_blog_id );
	return true;
}

/**
 * Paired teardown for `bb_appearance_pages_switch_to_root_blog()`.
 *
 * Always safe to call with whatever the switch helper returned — no-ops on
 * `false`, calls `restore_current_blog()` on `true`. Centralised so every
 * early-return site in the register / save / enrich paths gets the exact
 * same teardown call (one-liner) instead of open-coded `if ( $switched ) {
 * restore_current_blog(); }` copies at ~6 exit points that are easy to
 * forget.
 *
 * Missing any single exit point leaks a blog context onto the request —
 * downstream WP code runs against the wrong blog and `_doing_it_wrong()`
 * fires under WP_DEBUG. This helper exists specifically to make that
 * mistake mechanically impossible.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool $switched Whatever `bb_appearance_pages_switch_to_root_blog()` returned.
 * @return void
 */
function bb_appearance_pages_restore_root_blog( $switched ) {
	if ( $switched ) {
		restore_current_blog();
	}
}

/**
 * Register the Pages panel sections and fields when the feature is fetched.
 *
 * Hooked on `bb_admin_settings_before_get_feature` (fired by the AJAX
 * get/save handlers) rather than `bb_register_features` because the
 * directory-page dropdown set depends on BP component `has_directory`
 * globals which are only safe to read after `bp_setup_globals`. This mirrors
 * the sidebar-widget URL fix in `callbacks.php`.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id The feature being fetched / saved.
 * @return void
 */
function bb_appearance_register_pages_fields( $feature_id ) {
	if ( 'appearance' !== $feature_id ) {
		return;
	}
	if ( ! function_exists( 'bb_register_feature_section' ) || ! function_exists( 'bb_register_feature_field' ) ) {
		return;
	}
	if ( ! function_exists( 'bp_core_admin_get_directory_pages' ) || ! function_exists( 'bp_core_admin_get_static_pages' ) ) {
		return;
	}

	// Scope WP post lookups below (get_post_status for forums, etc.) to the
	// BP root blog. See `bb_appearance_pages_switch_to_root_blog()` docblock.
	$switched = bb_appearance_pages_switch_to_root_blog();

	// =========================================================================
	// SECTION: Component Pages
	// =========================================================================
	bb_register_feature_section(
		'appearance',
		'pages',
		'component_pages',
		array(
			'title'       => __( 'Component Pages', 'buddyboss' ),
			'description' => __( 'Associate a WordPress page with each of the following components.', 'buddyboss' ),
			'order'       => 10,
			// Reuses the legacy Pages-screen KB article ID (637148) until the
			// docs team delivers a dedicated Component-Pages article. Swap to
			// the new ID when available without touching anything else here.
			'help_url'    => '637148',
		)
	);

	// WPML non-default-language notice — rendered above everything else in the
	// panel when a translator is editing in a secondary language. Legacy
	// disabled its Save button in this state; we can't disable auto-save from
	// the server side without cooperation from the React form, so we (1) show
	// this notice to explain the situation and (2) block the save-side
	// handler from writing bp-pages (see `bb_appearance_pages_save_side_effects`).
	if ( bb_appearance_pages_is_wpml_non_default_language() ) {
		bb_register_feature_field(
			'appearance',
			'pages',
			'component_pages',
			array(
				'name'              => 'bb_appearance_pages_wpml_lock_notice',
				'label'             => '',
				'type'              => 'notice',
				'notice_type'       => 'warning',
				'description'       => __( 'Page mappings are managed in the default WPML language. Switch to your default language to modify these settings — changes made here will not be saved.', 'buddyboss' ),
				'sanitize_callback' => '__return_empty_string',
				'order'             => 1,
			)
		);
	}

	$existing_pages = bp_core_get_directory_page_ids();
	if ( ! is_array( $existing_pages ) ) {
		$existing_pages = array();
	}

	// Defensive (array) cast — `bp_core_admin_get_directory_pages()` ends with
	// `apply_filters( 'bp_directory_pages', … )` and a third-party filter
	// returning null / non-iterable would fatal a `foreach` on PHP 8+.
	$directory_pages = (array) bp_core_admin_get_directory_pages();
	$order           = 10;

	foreach ( $directory_pages as $name => $label ) {
		// `new_forums_page` reads its current value from
		// `_bbp_root_slug_custom_slug` instead of `bp-pages` — bbPress owns
		// the forum root slug. Everything else reads from `bp-pages` via the
		// cached `$existing_pages` map.
		if ( 'new_forums_page' === $name ) {
			$current_id = (int) bp_get_option( '_bbp_root_slug_custom_slug' );
			// Only surface the ID when the target post is published — otherwise
			// the dropdown would render a stale/trashed page as "selected".
			// Prime the single post first so get_post_status() is a cache hit
			// instead of a DB query (this fires on every admin request via
			// Settings 2.0 registration on bp_loaded:4).
			if ( $current_id ) {
				_prime_post_caches( array( $current_id ), false, false );
				if ( 'publish' !== get_post_status( $current_id ) ) {
					$current_id = 0;
				}
			}
		} else {
			$current_id = ! empty( $existing_pages[ $name ] ) ? (int) $existing_pages[ $name ] : 0;
		}

		bb_register_feature_field(
			'appearance',
			'pages',
			'component_pages',
			array(
				'name'              => 'bb_appearance_page_' . $name,
				'label'             => $label,
				'type'              => 'async_select',
				'async_action'      => 'bb_admin_search_pages_list',
				'placeholder'       => __( '— Select a page —', 'buddyboss' ),
				'description'       => bb_appearance_get_directory_page_description( $name ),
				'default'           => $current_id ? (string) $current_id : '',
				'sanitize_callback' => 'absint',
				// Consumed by `bb_admin_settings_format_field_data` →
				// `bb_appearance_enrich_page_field_data()` to attach View /
				// Create-Page affordances and the slug marker.
				'page_directory'    => $name,
				'order'             => $order,
			)
		);
		$order += 10;
	}

	// =========================================================================
	// SECTION: Registration Pages (conditional)
	// =========================================================================
	$reg_meta = bb_appearance_get_registration_section_meta();
	if ( ! $reg_meta['render'] ) {
		bb_appearance_pages_restore_root_blog( $switched );
		return;
	}

	bb_register_feature_section(
		'appearance',
		'pages',
		'registration_pages',
		array(
			'title'       => $reg_meta['title'],
			'description' => $reg_meta['description'],
			'order'       => 20,
			// KB article ID inherited from the legacy
			// `bb_registration_page_tutorial()` "View Tutorial" button. The
			// Settings 2.0 section header renders a `?` icon that opens this
			// article, keeping parity with the retired legacy tutorial link.
			'help_url'    => '62795',
		)
	);

	// Registration-disabled explanatory notice — parity with the legacy
	// `bp_core_admin_registration_pages_description()` else-branch. Shown as
	// the first row in the section when `bp_get_signup_allowed()` is false
	// so admins learn WHY the register/activate rows are hidden and where
	// to go to re-enable signup. Deep-links go to Settings 2.0 routes,
	// not legacy `?page=bp-settings&tab=…` URLs, so clicking lands directly
	// in the React admin without a 301 hop.
	$signup_allowed = function_exists( 'bp_get_signup_allowed' ) ? bp_get_signup_allowed() : false;
	if ( ! $signup_allowed ) {
		$general_settings_url = admin_url( 'admin.php?page=bb-settings&tab=registration' );

		$invite_text = '';
		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'invites' ) ) {
			$invites_url = admin_url( 'admin.php?page=bb-settings&tab=invites' );
			$invite_text = sprintf(
				/* translators: %s: Email Invites admin link. */
				__( 'Because %s is enabled, invited users will still be allowed to register new accounts.', 'buddyboss' ),
				'<a href="' . esc_url( $invites_url ) . '">' . esc_html__( 'Email Invites', 'buddyboss' ) . '</a>'
			);
		}

		$notice_description = sprintf(
			/* translators: 1: optional Email Invites sentence. 2: General Settings admin link. */
			__( 'Registration is currently disabled. %1$s To enable open registration, please click on the "Registration" checkbox in %2$s.', 'buddyboss' ),
			$invite_text,
			'<a href="' . esc_url( $general_settings_url ) . '">' . esc_html__( 'General Settings', 'buddyboss' ) . '</a>'
		);

		bb_register_feature_field(
			'appearance',
			'pages',
			'registration_pages',
			array(
				'name'              => 'bb_appearance_pages_registration_disabled_notice',
				'label'             => '',
				'type'              => 'notice',
				'notice_type'       => 'info',
				'description'       => $notice_description,
				'sanitize_callback' => '__return_empty_string',
				// Order 1 — renders ABOVE the dropdown rows. The static
				// page loop below starts at 10.
				'order'             => 1,
			)
		);
	}

	// Same defensive cast — filterable return, PHP 8 iterator contract.
	$static_pages = (array) bp_core_admin_get_static_pages();
	$order        = 10;

	foreach ( $static_pages as $name => $label ) {
		// Skip the legacy `button` synthetic entry. Legacy's dropdown
		// callback rendered a link-button row when the static_pages filter
		// returned an entry keyed `button` — a rare third-party extension
		// point (default Platform doesn't inject it). The new panel uses
		// async_select for every row; rendering a button entry as a
		// dropdown would be visibly wrong, so defensively skip it here.
		// If a third-party needs a button affordance they should register
		// their own feature field via the Settings 2.0 registry.
		if ( 'button' === $name ) {
			continue;
		}

		// Hide register + activate when registration is off AND invites are off —
		// only terms/privacy belong on a "Login Pages" section.
		if ( ! $reg_meta['show_register'] && in_array( $name, array( 'register', 'activate' ), true ) ) {
			continue;
		}

		$current_id = ! empty( $existing_pages[ $name ] ) ? (int) $existing_pages[ $name ] : 0;

		bb_register_feature_field(
			'appearance',
			'pages',
			'registration_pages',
			array(
				'name'              => 'bb_appearance_page_' . $name,
				'label'             => $label,
				'type'              => 'async_select',
				'async_action'      => 'bb_admin_search_pages_list',
				'placeholder'       => __( '— Select a page —', 'buddyboss' ),
				'description'       => bb_appearance_get_static_page_description( $name ),
				'default'           => $current_id ? (string) $current_id : '',
				'sanitize_callback' => 'absint',
				'page_directory'    => $name,
				'order'             => $order,
			)
		);
		$order += 10;
	}

	bb_appearance_pages_restore_root_blog( $switched );
}
add_action( 'bb_admin_settings_before_get_feature', 'bb_appearance_register_pages_fields' );

/**
 * Return the description copy for a Component Pages dropdown.
 *
 * Matches the legacy strings in `bp_core_admin_register_page_fields()` so
 * translations are reused. Returns '' for any directory that doesn't ship a
 * description (legacy behaviour — no paragraph under the dropdown).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $name Directory key from `bp_core_admin_get_directory_pages()`.
 * @return string Localized description, or '' when none applies.
 */
function bb_appearance_get_directory_page_description( $name ) {
	switch ( $name ) {
		case 'members':
			return __( 'This directory shows a listing of all members.', 'buddyboss' );
		case 'groups':
			return __( 'This directory shows a listing of all groups.', 'buddyboss' );
		case 'new_forums_page':
			return __( 'This directory shows a listing of all forums.', 'buddyboss' );
		case 'activity':
			return __( 'This directory shows all sitewide activity.', 'buddyboss' );
		case 'media':
			return __( 'This directory shows all photos uploaded by members.', 'buddyboss' );
		case 'document':
			return __( 'This directory shows all documents uploaded by members.', 'buddyboss' );
		case 'video':
			return __( 'This directory shows all video uploaded by members.', 'buddyboss' );
	}
	return '';
}

/**
 * Save handler for the Appearance → Pages panel.
 *
 * Runs on `bb_admin_save_feature_settings_after` for the `appearance` feature
 * and reconciles `$saved` against the legacy `bp-pages` option + the forums
 * root-slug options. Mirrors the write branches of
 * `bp_core_admin_maybe_save_pages_settings()`.
 *
 *   - `new_forums_page` → `_bbp_root_slug_custom_slug` (page ID) and
 *     `_bbp_root_slug` (`get_page_uri`) — NOT in `bp-pages`.
 *   - every other dropdown → staged into one array and written in a single
 *     `bp_core_update_directory_page_ids()` call so the cached option +
 *     downstream actions fire exactly once per save.
 *   - rewrite rules flushed on `shutdown` via a static idempotency guard —
 *     same pattern as `bb_appearance_apply_configuration()`.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature that was saved.
 * @param array  $settings   Raw submitted settings payload (unused).
 * @param array  $saved      Sanitized values written to options by the core
 *                           save loop.
 * @return void
 */
function bb_appearance_pages_save_side_effects( $feature_id, $settings, $saved ) {
	if ( 'appearance' !== $feature_id || ! is_array( $saved ) ) {
		return;
	}
	if ( ! function_exists( 'bp_core_admin_get_directory_pages' ) || ! function_exists( 'bp_core_admin_get_static_pages' ) ) {
		return;
	}

	// WPML guard — refuse to write `bp-pages` when the admin is editing in a
	// non-default language. The option is default-language-scoped and getting
	// overwritten with translated page IDs here would break permalinks across
	// the primary site. The panel renders an in-place notice explaining this
	// to the user (see `bb_appearance_register_pages_fields`); this is the
	// defensive server-side half of the same contract.
	if ( bb_appearance_pages_is_wpml_non_default_language() ) {
		return;
	}

	// Route post lookups below (get_page_uri for forums slug, etc.) at the
	// root blog so a sub-site admin can't inadvertently slug the forum root
	// against their own blog. See `bb_appearance_pages_switch_to_root_blog()`.
	$switched = bb_appearance_pages_switch_to_root_blog();

	// Defensive (array) casts — both helpers pass their return through
	// `apply_filters(…)`, and `array_merge()` fatals when fed a non-array
	// under PHP 8+. Keeps the save side robust against third-party filters
	// that drop one of the lists.
	$valid_pages = array_merge(
		(array) bp_core_admin_get_directory_pages(),
		(array) bp_core_admin_get_static_pages()
	);

	$directory_updates = array();
	$touched           = false;

	foreach ( $valid_pages as $directory_key => $label ) {
		$field_name = 'bb_appearance_page_' . $directory_key;
		if ( ! array_key_exists( $field_name, $saved ) ) {
			continue;
		}

		$value   = (int) $saved[ $field_name ];
		$touched = true;

		// Reject anything that isn't a published `page` post. The generic
		// save handler only `absint`s the submitted ID — without this guard
		// a hand-crafted payload could map a directory to an attachment,
		// draft, or trashed post and produce a broken frontend. Zero is
		// allowed as a valid "clear the mapping" value and falls through.
		if ( $value > 0 ) {
			$post = get_post( $value );
			if ( ! $post || 'page' !== $post->post_type || 'publish' !== $post->post_status ) {
				// Skip this field — do not write. Other submitted fields in
				// the same save continue normally.
				continue;
			}
		}

		// Forums routes to bbPress's own slug options — not the compound
		// `bp-pages` option — so bbPress's permalink rewriter stays in sync.
		if ( 'new_forums_page' === $directory_key ) {
			if ( $value <= 0 ) {
				bp_update_option( '_bbp_root_slug_custom_slug', '' );
			} else {
				$slug = get_page_uri( $value );
				if ( $slug ) {
					bp_update_option( '_bbp_root_slug', urldecode( $slug ) );
				}
				bp_update_option( '_bbp_root_slug_custom_slug', $value );
			}
			continue;
		}

		$directory_updates[ $directory_key ] = $value;

		// Clean up any stray per-field option row — the generic save handler
		// wrote `bb_appearance_page_{directory}` to its own row before this
		// action fired, but the canonical storage for these mappings is the
		// compound `bp-pages` option. Delete the per-field row so it doesn't
		// accumulate.
		//
		// `bp_delete_option` (i.e. `delete_blog_option` against the root blog)
		// targets the same blog that `bp_update_option` writes to. Plain
		// `delete_option` hits the CURRENT blog, which on a sub-site admin
		// request would leave orphaned rows on the root blog.
		//
		// Note: we deliberately do NOT mutate `$saved` itself here. This
		// action's `$saved` arg is passed by value — mutating it has no effect
		// on subsequent subscribers of the same hook invocation, so removing
		// the key locally would just be a silent no-op.
		bp_delete_option( $field_name );
	}

	if ( ! empty( $directory_updates ) ) {
		// Merge with the existing map so saving one row doesn't blow away the
		// others — the React form only submits changed fields via its
		// debounced auto-save.
		//
		// Pass 'all' explicitly: the default 'active' status strips inactive
		// components' page mappings from the return value, and array_merge()
		// then builds a blob without those entries. bp_core_update_directory_
		// page_ids() would write back the truncated blob — permanently wiping
		// the page IDs of any component that happens to be deactivated at
		// save time (visible when a customer toggles media off, saves any
		// appearance page field, and finds their media directory page lost).
		$current = bp_core_get_directory_page_ids( 'all' );
		if ( ! is_array( $current ) ) {
			$current = array();
		}
		bp_core_update_directory_page_ids( array_merge( $current, $directory_updates ) );
	}

	if ( ! $touched ) {
		bb_appearance_pages_restore_root_blog( $switched );
		return;
	}

	// Permalinks need a flush so the new directory slugs resolve. Deferred to
	// shutdown with a static guard so two-saves-in-one-request flush at most
	// once — same idempotency pattern as `bb_appearance_apply_configuration()`.
	//
	// Closure scopes its flush at the BP root blog explicitly. `shutdown`
	// runs after the save handler's own switch/restore has unwound, so the
	// current blog at closure-execution time is whatever WP was on when the
	// request started — on a sub-site admin save that's the sub-site, and
	// an unscoped `flush_rewrite_rules()` would flush the wrong blog's
	// rewrite rules. Using the helper keeps the switch optional (no-op on
	// single-site / already-root-blog).
	static $flush_scheduled = false;
	if ( ! $flush_scheduled ) {
		$flush_scheduled = true;
		add_action(
			'shutdown',
			static function () {
				$flush_switched = bb_appearance_pages_switch_to_root_blog();
				flush_rewrite_rules();
				bb_appearance_pages_restore_root_blog( $flush_switched );
			}
		);
	}

	bb_appearance_pages_restore_root_blog( $switched );
}
add_action( 'bb_admin_save_feature_settings_after', 'bb_appearance_pages_save_side_effects', 10, 3 );

/**
 * Ask the React admin to refetch the Appearance feature after a Pages save.
 *
 * The generic save response only ships updated `saved` values. Fields'
 * computed metadata — `page_view_url`, `initial_label`, the selected `value`
 * itself — is frozen from the initial GET and goes stale the moment the user
 * clears or swaps a directory page:
 *
 *   - cleared → View button keeps rendering because the stale `page_view_url`
 *     is still truthy, and the dropdown's async select still reads the old
 *     title from the stale `initial_label`.
 *   - swapped → same story, now pointing at the previous page.
 *
 * `refresh_panels` is the existing signal (see
 * `FeatureSettingsScreen.js:427`) that React listens for to invalidate its
 * feature cache and refetch. Piggy-backing on it avoids inventing a new
 * round-trip primitive for this single case; the refetched payload naturally
 * has fresh `bb_appearance_enrich_page_field_data()` output.
 *
 * Only signal when a Pages field was actually written — don't refetch on
 * unrelated Appearance saves (Site Layout, Branding, etc.).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $response_data Response payload being sent to React.
 * @param string $feature_id    Feature that was saved.
 * @param array  $settings      Raw submitted settings (unused).
 * @param array  $saved         Sanitized values written to options.
 * @return array Response with `refresh_panels` set when Pages fields changed.
 */
function bb_appearance_pages_flag_refresh_on_save( $response_data, $feature_id, $settings, $saved ) {
	if ( 'appearance' !== $feature_id || ! is_array( $saved ) ) {
		return $response_data;
	}

	$touched_any_pages_field = false;

	foreach ( $saved as $name => $value ) {
		if ( 0 !== strpos( (string) $name, 'bb_appearance_page_' ) ) {
			continue;
		}
		$touched_any_pages_field = true;

		// Coerce cleared page IDs back to '' in the response's `saved` map.
		//
		// The server save loop runs each dropdown's `sanitize_callback =>
		// 'absint'` against the user's submitted '' (cleared selection),
		// which yields 0 (int). React merges that 0 into the form state,
		// then re-renders the dropdown with `value={String(0)}` = '0' — a
		// truthy string that falls through to the async_select's
		// initialLabel-sync branch and flashes the stale pre-clear label
		// before the follow-up refetch settles the correct empty state.
		// Coercing to '' here keeps the wire shape consistent with the
		// cleared UI and sidesteps the flash entirely. Same play as the
		// `bb_rl_enabled` bool → string coercion in `callbacks.php`.
		if ( isset( $response_data['saved'][ $name ] ) && 0 === (int) $response_data['saved'][ $name ] ) {
			$response_data['saved'][ $name ] = '';
		}
	}

	if ( $touched_any_pages_field ) {
		$response_data['refresh_panels'] = true;
	}

	return $response_data;
}
add_filter( 'bb_admin_save_feature_settings_response', 'bb_appearance_pages_flag_refresh_on_save', 10, 4 );

/**
 * Enrich the Pages-panel field data on its way to React.
 *
 * Adds two pieces of UI metadata the React `async_select` wrapper uses to
 * render View / Create Page buttons next to each dropdown:
 *
 *   - `page_view_url`   — current `get_permalink()` when a page is selected.
 *   - `page_create_args` — AJAX action + directory slug + button label for
 *                          the Create-Page button when the dropdown is empty.
 *
 * Also resolves the stored ID to a `{ value, label }` pair so the field
 * renders its current selection immediately on page load without waiting for
 * the async search round-trip.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $field_data  Formatted field data being sent to React.
 * @param array  $field       Raw registration args.
 * @param string $feature_id  The feature being formatted.
 * @return array Enriched field data.
 */
function bb_appearance_enrich_page_field_data( $field_data, $field, $feature_id ) {
	if ( 'appearance' !== $feature_id ) {
		return $field_data;
	}
	if ( empty( $field['page_directory'] ) ) {
		return $field_data;
	}

	$directory = (string) $field['page_directory'];

	// Route post lookups (get_post_status, get_permalink, get_the_title) at
	// the root blog — on a sub-site admin request they'd otherwise hit the
	// sub-site posts table and return the wrong titles / permalinks for the
	// root-blog page IDs stored in `bp-pages`. See
	// `bb_appearance_pages_switch_to_root_blog()`.
	//
	// Request-scoped gate: the filter runs once per field in the panel and
	// the Pages panel ships 11 fields, so an unconditional switch / restore
	// pair here means 22 blog transitions per panel open on multisite. Hold
	// the switch open across all fields within the same request and restore
	// exactly once at shutdown. `shutdown` fires after `wp_send_json_*`
	// returns, so response timing is unaffected; downstream code in the same
	// AJAX handler doesn't depend on pre-switch blog context.
	static $request_switched = null;
	if ( null === $request_switched ) {
		$request_switched = bb_appearance_pages_switch_to_root_blog();
		if ( $request_switched ) {
			add_action(
				'shutdown',
				static function () {
					bb_appearance_pages_restore_root_blog( true );
				},
				0
			);
		}
	}

	// Current page ID lookup — mirrors registration-time logic but runs at
	// AJAX format time so the resolved title is always fresh.
	if ( 'new_forums_page' === $directory ) {
		$page_id = (int) bp_get_option( '_bbp_root_slug_custom_slug' );
		if ( $page_id && 'publish' !== get_post_status( $page_id ) ) {
			$page_id = 0;
		}
	} else {
		// Per-request static memo — this filter runs per field during an AJAX
		// get/save, and the Pages panel registers 11 fields. Without caching
		// we'd re-read the compound `bp-pages` option 11 times on every
		// feature fetch. Scoped to one request so saves in the same request
		// still see fresh data.
		static $cached_page_ids = null;
		if ( null === $cached_page_ids ) {
			$cached_page_ids = bp_core_get_directory_page_ids();
			if ( ! is_array( $cached_page_ids ) ) {
				$cached_page_ids = array();
			}
		}
		$page_id = ! empty( $cached_page_ids[ $directory ] ) ? (int) $cached_page_ids[ $directory ] : 0;
	}

	$field_data['value']         = $page_id ? (string) $page_id : '';
	$field_data['default']       = $field_data['value'];
	$field_data['page_view_url'] = $page_id ? esc_url_raw( get_permalink( $page_id ) ) : '';

	// Pre-resolved label so the async_select can render its current selection
	// at first paint without firing a per-field mount AJAX. With 11 dropdowns
	// on this panel the naive resolve path hit admin-ajax.php 11 times for
	// identical `selected_id` lookups — this collapses them to zero.
	/* translators: %d: WordPress page ID, used as a fallback when a page has no title. */
	$no_title_fallback = __( '(no title) #%d', 'buddyboss' );
	if ( $page_id ) {
		$title                       = get_the_title( $page_id );
		$field_data['initial_label'] = $title ? $title : sprintf( $no_title_fallback, $page_id );
	}

	$field_data['page_create_args'] = array(
		'action' => 'bb_admin_create_directory_page',
		'slug'   => $directory,
		'label'  => isset( $field['label'] ) ? (string) $field['label'] : '',
	);

	// No per-call restore — the request-scoped switch above stays open until
	// the shutdown hook runs, which happens exactly once per request.
	return $field_data;
}
add_filter( 'bb_admin_settings_format_field_data', 'bb_appearance_enrich_page_field_data', 20, 3 );
