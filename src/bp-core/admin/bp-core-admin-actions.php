<?php
/**
 * BuddyPress Admin Actions.
 *
 * This file contains the actions that are used through-out BuddyPress Admin. They
 * are consolidated here to make searching for them easier, and to help developers
 * understand at a glance the order in which things occur.
 *
 * There are a few common places that additional actions can currently be found.
 *
 *  - BuddyPress: In {@link BuddyPress::setup_actions()} in BuddyPress.php
 *  - Admin: More in {@link bp_Admin::setup_actions()} in admin.php
 *
 * @package BuddyBoss\Admin
 * @since BuddyPress 2.3.0
 * @see bp-core-actions.php
 * @see bp-core-filters.php
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Attach BuddyPress to WordPress.
 *
 * BuddyPress uses its own internal actions to help aid in third-party plugin
 * development, and to limit the amount of potential future code changes when
 * updates to WordPress core occur.
 *
 * These actions exist to create the concept of 'plugin dependencies'. They
 * provide a safe way for plugins to execute code *only* when BuddyPress is
 * installed and activated, without needing to do complicated guesswork.
 *
 * For more information on how this works, see the 'Plugin Dependency' section
 * near the bottom of this file.
 *
 *          v--WordPress Actions       v--BuddyPress Sub-actions
 */
add_action( 'admin_menu', 'bp_admin_menu' );
add_action( 'admin_init', 'bp_admin_init' );
add_action( 'admin_head', 'bp_admin_head' );
add_action( 'admin_notices', 'bp_admin_notices' );
add_action( 'admin_enqueue_scripts', 'bp_admin_enqueue_scripts' );
add_action( 'customize_controls_enqueue_scripts', 'bp_admin_enqueue_scripts', 8 );
add_action( 'network_admin_menu', 'bp_admin_menu' );
add_action( 'custom_menu_order', 'bp_admin_custom_menu_order' );
add_action( 'menu_order', 'bp_admin_menu_order' );
add_action( 'wpmu_new_blog', 'bp_new_site', 10, 6 );

// Hook on to admin_init.
add_action( 'bp_admin_init', 'bp_setup_updater', 1000 );
add_action( 'bp_admin_init', 'bp_core_activation_notice', 1010 );
add_action( 'bp_admin_init', 'bp_register_importers' );
add_action( 'bp_admin_init', 'bp_register_admin_style' );
add_action( 'bp_admin_init', 'bp_register_admin_settings' );
add_action( 'bp_admin_init', 'bp_register_admin_integrations' );
add_action( 'bp_admin_init', 'bp_do_activation_redirect', 1 );
add_action( 'bp_admin_init', 'bp_check_for_legacy_theme' );
add_action( 'bp_admin_init', 'bb_redirect_legacy_settings_to_settings_2', 1 );

/**
 * Forward any request that still targets the legacy ?page=bp-settings URL
 * to the Settings 2.0 home at ?page=bb-settings.
 *
 * Two layers of coverage:
 *
 *  1. An `admin_init` priority-0 hook catches requests even when the user's
 *     permissions would pass the submenu check — fastest path, no page render.
 *
 *  2. A hidden registered submenu (`bb_register_legacy_bp_settings_redirect`)
 *     reserves the `bp-settings` slug and supplies a render callback that
 *     performs the redirect. This is what guarantees the redirect even if
 *     WordPress's submenu-access gate rejects the request before
 *     `admin_init` finishes (e.g., when user caps don't include our
 *     registered submenu's capability in some custom-role configurations,
 *     or when another plugin short-circuits `admin_init`).
 *
 * Together they ensure ?page=bp-settings NEVER lands on the "Sorry, you
 * are not allowed..." error page.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_force_redirect_bare_bp_settings() {
	if ( ! is_admin() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL inspection.
	$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL inspection.
	$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

	if ( 'bp-settings' === $page && empty( $tab ) ) {
		wp_safe_redirect( bp_get_admin_url( 'admin.php?page=bb-settings' ) );
		exit;
	}
}
add_action( 'admin_init', 'bb_force_redirect_bare_bp_settings', 0 );

/**
 * Get the legacy Settings 1.0 tab → Settings 2.0 URL mapping.
 *
 * Shared between the `admin_menu`-priority redirect and the later
 * `bp_admin_init` redirect so both entry points normalize tabs consistently.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return array Map of old tab slug => feature ID string OR array( 'tab' => ..., 'panel' => ... ).
 */
function bb_get_legacy_settings_tabs_mapping() {
	$legacy_tabs_mapping = array(
		'bp-reactions'     => 'reactions',
		'bp-activity'      => 'activity',
		'bp-groups'        => 'groups',
		'bp-xprofile'      => 'members',
		'bp-forums'        => 'forums',
		'bp-friends'       => array(
			'tab'   => 'members',
			'panel' => 'member_connection',
		),
		'bp-notifications' => 'notifications',
		'bp-media'         => 'media',
		'bp-video'         => array(
			'tab'   => 'media',
			'panel' => 'videos',
		),
		'bp-document'      => array(
			'tab'   => 'media',
			'panel' => 'documents',
		),
		'bp-messages'      => 'messages',
		'bp-search'        => 'search',
		'bp-invites'       => 'invites',
		'bp-registration'  => 'registration',
		'bp-general'       => 'advanced',
		'bp-advanced'      => 'advanced',
		'bp-moderation'    => 'moderation',
	);

	/** This filter is documented in src/bp-core/admin/bp-core-admin-actions.php */
	return apply_filters( 'bb_legacy_settings_tabs_mapping', $legacy_tabs_mapping );
}

/**
 * Apply a legacy tab mapping entry to a Settings 2.0 URL.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string       $target  Base admin URL to add tab/panel onto.
 * @param string|array $mapping Mapping entry: feature-id string or [ 'tab' => ..., 'panel' => ... ].
 * @return string URL with tab (and panel) query args applied.
 */
function bb_apply_legacy_tab_mapping_to_url( $target, $mapping ) {
	if ( is_array( $mapping ) ) {
		if ( ! empty( $mapping['tab'] ) ) {
			$target = add_query_arg( 'tab', $mapping['tab'], $target );
		}
		if ( ! empty( $mapping['panel'] ) ) {
			$target = add_query_arg( 'panel', $mapping['panel'], $target );
		}
	} else {
		$target = add_query_arg( 'tab', $mapping, $target );
	}
	return $target;
}

/**
 * Redirect legacy admin slugs to Settings 2.0 before WordPress's permission gate.
 *
 * WordPress calls `user_can_access_admin_page()` in `wp-admin/includes/menu.php`
 * at line ~371, which runs via `require wp-admin/menu.php` at line 163 of
 * `wp-admin/admin.php` — BEFORE `do_action('admin_init')` at line 180. So an
 * `admin_init`-priority redirect never fires for an unregistered submenu
 * slug; WP has already called `wp_die()` by then.
 *
 * Hooking `admin_menu` priority MAX catches the request after all menu
 * registrations but BEFORE `user_can_access_admin_page()` runs — the action
 * is dispatched at `includes/menu.php:161`, the permission check runs at
 * line 371 of the same file, so any hook attached to `admin_menu` sees the
 * request first.
 *
 * Covers two legacy slugs that were removed in Settings 2.0:
 *  - `bp-settings`     → `bb-settings`, with tab normalization via
 *    `bb_get_legacy_settings_tabs_mapping()` (e.g. `bp-activity` → `activity`).
 *  - `bp-integrations` → `bb-settings` with best-effort tab mapping via
 *    the `bb_legacy_integration_tabs_mapping` filter (Pro populates it
 *    with Zoom/OneSignal/etc. tab redirects).
 *
 * Extra query args (`download_mu_file`, plugin-specific flags, etc.) are
 * preserved on the target URL so deep-link flows — like BB App's MU-installer
 * download nonce — survive the redirect. Only `page` / `tab` / `panel` are
 * replaced; everything else passes through.
 *
 * This is the only hook point that reliably catches the "slug doesn't
 * exist" case before WP's 403 fires.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_redirect_bp_settings_before_permission_check() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL inspection.
	$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

	// Retired Settings 2.0 standalone admin pages whose submenu items now
	// register a Settings 2.0 URL as their slug (Groups / Moderation / Profile
	// Fields). The legacy slug `?page=bp-groups` etc. is no longer registered
	// with WP, so a direct visit would 403 at `user_can_access_admin_page()`
	// without this early forward. Sidebar link clicks land on the new URL
	// directly — this map only matters for direct old-URL hits
	// (LearnDash, bookmarks, third-party links).
	//
	// Extend this map when retiring further standalone admin screens whose
	// `add_submenu_page` registration has been switched to a URL slug.
	$retired_pages = array(
		'bp-groups'        => 'admin.php?page=bb-settings&tab=groups&panel=all_groups',
		'bp-moderation'    => 'admin.php?page=bb-settings&tab=moderation&panel=flagged_members',
		'bp-profile-setup' => 'admin.php?page=bb-settings&tab=members&panel=profile_fields',
	);
	if ( isset( $retired_pages[ $page ] ) ) {
		$target = bp_get_admin_url( $retired_pages[ $page ] );

		// Preserve any non-routing query args (deep-link flags, etc.).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL inspection.
		$reserved = array( 'page', 'tab', 'panel' );
		$extra_qs = array();
		foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL inspection.
			if ( in_array( $key, $reserved, true ) ) {
				continue;
			}
			if ( is_scalar( $value ) ) {
				$extra_qs[ sanitize_key( $key ) ] = sanitize_text_field( wp_unslash( $value ) );
			}
		}
		if ( ! empty( $extra_qs ) ) {
			$target = add_query_arg( $extra_qs, $target );
		}

		wp_safe_redirect( $target );
		exit;
	}

	// Other retired Settings 2.0 standalone admin pages whose submenu
	// registrations were removed entirely (no replacement add_submenu_page
	// call). Same 403-prevention rationale as the map above.
	if ( 'bp-pages' === $page ) {
		$bp_pages_target = bp_get_admin_url( 'admin.php?page=bb-settings&tab=appearance&panel=pages' );

		// Preserve any non-routing query args (e.g. `download_mu_file`,
		// Pro / third-party deep-link flags). Same treatment as the
		// bp-settings / bp-integrations branches below — this one just
		// exits early because the page/tab/panel shape is fixed.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL inspection.
		$reserved = array( 'page', 'tab', 'panel' );
		$extra_qs = array();
		foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL inspection.
			if ( in_array( $key, $reserved, true ) ) {
				continue;
			}
			if ( is_scalar( $value ) ) {
				$extra_qs[ sanitize_key( $key ) ] = sanitize_text_field( wp_unslash( $value ) );
			}
		}
		if ( ! empty( $extra_qs ) ) {
			$bp_pages_target = add_query_arg( $extra_qs, $bp_pages_target );
		}

		wp_safe_redirect( $bp_pages_target );
		exit;
	}

	// Redirect legacy ReadyLaunch standalone admin page
	// (`admin.php?page=bb-readylaunch`) to the Appearance feature in
	// Settings 2.0. Must be handled here on `admin_menu @ PHP_INT_MAX`
	// (not `bp_admin_init`) because the submenu was removed during the
	// Settings 2.0 migration — without this early redirect WP's
	// `user_can_access_admin_page()` check fires `wp_die()` before any
	// `admin_init`-priority hook can intercept.
	if ( 'bb-readylaunch' === $page ) {
		$rl_target = bp_get_admin_url( 'admin.php?page=bb-settings&tab=appearance' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL inspection.
		$reserved = array( 'page', 'tab', 'panel' );
		$extra_qs = array();
		foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL inspection.
			if ( in_array( $key, $reserved, true ) ) {
				continue;
			}
			if ( is_scalar( $value ) ) {
				$extra_qs[ sanitize_key( $key ) ] = sanitize_text_field( wp_unslash( $value ) );
			}
		}
		if ( ! empty( $extra_qs ) ) {
			$rl_target = add_query_arg( $extra_qs, $rl_target );
		}

		wp_safe_redirect( $rl_target );
		exit;
	}

	// `?page=bp-tools&tab=bp-tools-default-data` retired in BuddyBoss [BBVERSION]
	// — Default Data sub-tab was extracted to the buddyboss-tools plugin and
	// renders as a Settings 2.0 React panel. Redirect any deep links to the
	// new URL. Other ?page=bp-tools tabs (e.g. forum import) still render
	// the legacy page and migrate in Phase 3.
	//
	// @since BuddyBoss [BBVERSION]
	if ( 'bp-tools' === $page ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL inspection.
		$bp_tools_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		if ( 'bp-tools-default-data' === $bp_tools_tab ) {
			wp_safe_redirect(
				bp_get_admin_url( 'admin.php?page=bb-settings&tab=tools&panel=sample_data' )
			);
			exit;
		}
	}

	// Retired Repair Community + Repair Forums standalone admin pages:
	//   ?page=bp-repair-community  (standalone Repair Community page)
	//   ?page=bbp-repair           (standalone Forum Repair page)
	// Both redirect to the new Settings 2.0 Tools → Repair Platform panel.
	// NOTE: ?page=bp-tools (root) is NOT redirected here — that page still
	// hosts the legacy Forum Import sub-tab which migrates in Phase 3.
	//
	// @since BuddyBoss [BBVERSION]
	if ( in_array( $page, array( 'bp-repair-community', 'bbp-repair' ), true ) ) {
		$tools_target = bp_get_admin_url( 'admin.php?page=bb-settings&tab=tools&panel=repair_platform' );

		// Preserve any non-routing query args (deep-link flags, etc.).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL inspection.
		$reserved_qs = array( 'page', 'tab', 'panel' );
		$extra_qs    = array();
		foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL inspection.
			if ( in_array( $key, $reserved_qs, true ) ) {
				continue;
			}
			if ( is_scalar( $value ) ) {
				$extra_qs[ sanitize_key( $key ) ] = sanitize_text_field( wp_unslash( $value ) );
			}
		}
		if ( ! empty( $extra_qs ) ) {
			$tools_target = add_query_arg( $extra_qs, $tools_target );
		}

		wp_safe_redirect( $tools_target );
		exit;
	}

	if ( 'bp-settings' !== $page && 'bp-integrations' !== $page && 'bp-components' !== $page ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL inspection.
	$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

	$target = bp_get_admin_url( 'admin.php?page=bb-settings' );

	if ( 'bp-integrations' === $page && ! empty( $tab ) ) {
		/**
		 * Filter the legacy integration tabs mapping.
		 *
		 * Pro hooks this to add Zoom, OneSignal, and other integration tab redirects.
		 * Values can be a string (feature ID only) or array with 'tab' and 'panel'.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param array $legacy_integration_tabs Array of old integration tab name => new Settings 2.0 route.
		 */
		$legacy_integration_tabs = apply_filters( 'bb_legacy_integration_tabs_mapping', array() );

		if ( isset( $legacy_integration_tabs[ $tab ] ) ) {
			$target = bb_apply_legacy_tab_mapping_to_url( $target, $legacy_integration_tabs[ $tab ] );
		}
	} elseif ( 'bp-settings' === $page && ! empty( $tab ) ) {
		// Normalize legacy Settings 1.0 tab slugs (bp-activity → activity, etc.)
		// here so this single redirect lands the user directly on a valid
		// Settings 2.0 route. The post-redirect handler can't do it: once the
		// URL is already ?page=bb-settings it no longer matches its own guard.
		$legacy_tabs_mapping = bb_get_legacy_settings_tabs_mapping();

		if ( isset( $legacy_tabs_mapping[ $tab ] ) ) {
			$target = bb_apply_legacy_tab_mapping_to_url( $target, $legacy_tabs_mapping[ $tab ] );
		} else {
			// Unknown tab — preserve as-is so Pro / third-party add-ons that
			// registered custom tabs via the `bb_legacy_settings_tabs_mapping`
			// filter (but weren't loaded this request) aren't silently dropped.
			$target = add_query_arg( 'tab', $tab, $target );
		}
	}

	// Preserve any extra query args (e.g. download_mu_file, plugin-specific
	// flags) so deep-link flows survive the redirect. We only consume the keys
	// we already routed above — `page`, `tab`, and `panel` are owned by the
	// target URL.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL inspection.
	$reserved = array( 'page', 'tab', 'panel' );
	$extra_qs = array();
	foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL inspection.
		if ( in_array( $key, $reserved, true ) ) {
			continue;
		}
		if ( is_scalar( $value ) ) {
			$extra_qs[ sanitize_key( $key ) ] = sanitize_text_field( wp_unslash( $value ) );
		}
	}
	if ( ! empty( $extra_qs ) ) {
		$target = add_query_arg( $extra_qs, $target );
	}

	wp_safe_redirect( $target );
	exit;
}
add_action( 'admin_menu', 'bb_redirect_bp_settings_before_permission_check', PHP_INT_MAX );
add_action( 'network_admin_menu', 'bb_redirect_bp_settings_before_permission_check', PHP_INT_MAX );

// Show notice when Profile Avatars is BuddyBoss.
add_action( 'bp_admin_head', 'bb_discussion_page_show_notice_in_avatar_section' );

// Add a new separator.
add_action( 'bp_admin_menu', 'bp_admin_separator' );

// Check user nickname on backend user edit page.
add_action( 'user_profile_update_errors', 'bb_check_user_nickname', 10, 3 );

// Validate if email address is allowed or blacklisted.
add_action( 'user_profile_update_errors', 'bb_validate_restricted_email_on_registration', PHP_INT_MAX, 3 );
add_action( 'personal_options_update', 'bb_validate_restricted_email_on_profile_update', 1 ); // Edit the login user profile from backend.
add_action( 'edit_user_profile_update', 'bb_validate_restricted_email_on_profile_update', 1 ); // Edit other users profile from backend.

/**
 * When a new site is created in a multisite installation, run the activation
 * routine on that site.
 *
 * @since BuddyPress 1.7.0
 *
 * @param int    $blog_id ID of the blog being installed to.
 * @param int    $user_id ID of the user the install is for.
 * @param string $domain  Domain to use with the install.
 * @param string $path    Path to use with the install.
 * @param int    $site_id ID of the site being installed to.
 * @param array  $meta    Metadata to use with the site creation.
 */
function bp_new_site( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

	// Bail if plugin is not network activated.
	if ( ! is_plugin_active_for_network( buddypress()->basename ) ) {
		return;
	}

	// Switch to the new blog.
	switch_to_blog( $blog_id );

	/**
	 * Fires the activation routine for a new site created in a multisite installation.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param int    $blog_id ID of the blog being installed to.
	 * @param int    $user_id ID of the user the install is for.
	 * @param string $domain  Domain to use with the install.
	 * @param string $path    Path to use with the install.
	 * @param int    $site_id ID of the site being installed to.
	 * @param array  $meta    Metadata to use with the site creation.
	 */
	do_action( 'bp_new_site', $blog_id, $user_id, $domain, $path, $site_id, $meta );

	// Restore original blog.
	restore_current_blog();
}

/** Sub-Actions ***************************************************************/

/**
 * Piggy back admin_init action.
 *
 * @since BuddyPress 1.7.0
 */
function bp_admin_init() {

	/**
	 * Fires inside the bp_admin_init function.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_admin_init' );
}

/**
 * Piggy back admin_menu action.
 *
 * @since BuddyPress 1.7.0
 */
function bp_admin_menu() {

	/**
	 * Fires inside the bp_admin_menu function.
	 *
	 * @since BuddyPress 1.7.0
	 */
	do_action( 'bp_admin_menu' );
}

/**
 * Piggy back admin_head action.
 *
 * @since BuddyPress 1.7.0
 */
function bp_admin_head() {

	/**
	 * Fires inside the bp_admin_head function.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_admin_head' );
}

/**
 * Piggy back admin_notices action.
 *
 * @since BuddyPress 1.7.0
 */
function bp_admin_notices() {

	/**
	 * Fires inside the bp_admin_notices function.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_admin_notices' );
}

/**
 * Piggy back admin_enqueue_scripts action.
 *
 * @since BuddyPress 1.7.0
 *
 * @param string $hook_suffix The current admin page, passed to
 *                            'admin_enqueue_scripts'.
 */
function bp_admin_enqueue_scripts( $hook_suffix = '' ) {

	/**
	 * Fires inside the bp_admin_enqueue_scripts function.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param string $hook_suffix The current admin page, passed to admin_enqueue_scripts.
	 */
	do_action( 'bp_admin_enqueue_scripts', $hook_suffix );
}

/**
 * Dedicated action to register BuddyPress importers.
 *
 * @since BuddyPress 1.7.0
 */
function bp_register_importers() {

	/**
	 * Fires inside the bp_register_importers function.
	 *
	 * Used to register a BuddyPress importer.
	 *
	 * @since BuddyPress 1.7.0
	 */
	do_action( 'bp_register_importers' );
}

/**
 * Dedicated action to register admin styles.
 *
 * @since BuddyPress 1.7.0
 */
function bp_register_admin_style() {

	/**
	 * Fires inside the bp_register_admin_style function.
	 *
	 * @since BuddyPress 1.7.0
	 */
	do_action( 'bp_register_admin_style' );
}

/**
 * Dedicated action to register admin settings.
 *
 * @since BuddyPress 1.7.0
 */
function bp_register_admin_settings() {

	/**
	 * Fires inside the bp_register_admin_settings function.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_register_admin_settings' );
}

/**
 * Dedicated action to register admin integrations.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_register_admin_integrations() {

	/**
	 * Fires inside the bp_register_admin_integrations function.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_register_admin_integrations' );
}

/**
 * Check user nickname is already taken or not.
 *
 * @since BuddyBoss 1.6.0
 *
 * @param object $errors error object.
 * @param bool   $update updating user or adding user.
 * @param object $user   user data.
 */
function bb_check_user_nickname( &$errors, $update, &$user ) {
	global $wpdb;

	// Check user unique identifier exist.
	$check_exists = $wpdb->get_var( // phpcs:ignore
		$wpdb->prepare(
			"SELECT count(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
			'bb_profile_slug',
			$user->user_login
		)
	);

	if ( $check_exists > 0 ) {
		return $errors->add( 'invalid_nickname', __( 'Invalid Nickname', 'buddyboss' ), array( 'form-field' => 'nickname' ) );
	}

	$un_name = ( ! empty( $user->nickname ) ) ? $user->nickname : $user->user_login;

	$where = array(
		'meta_key = "nickname"',
		'meta_value = "' . $un_name . '"',
	);

	if ( ! empty( $user->ID ) ) {
		$where[] = 'user_id != ' . $user->ID;
	}

	$sql = sprintf(
		'SELECT count(*) FROM %s WHERE %s',
		$wpdb->usermeta,
		implode( ' AND ', $where )
	);

	if ( $wpdb->get_var( $sql ) > 0 ) {
		$errors->add( 'nickname_exists', __( '<strong>Error</strong>: Nickname already has been taken. Please try again.', 'buddyboss' ), array( 'form-field' => 'nickname' ) );
	}
}

/**
 * Wrapper function to check GIPHY key is valid or not.
 *
 * @since BuddyBoss 2.1.2
 */
function bb_admin_check_valid_giphy_key() {
	$response = array(
		'code'    => 403,
		'message' => esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' ),
	);

	$key = filter_input( INPUT_POST, 'key', FILTER_DEFAULT );

	if ( empty( $key ) ) {
		wp_send_json_error( $response );
	}

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	if ( ! bp_is_active( 'media' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['nonce'] ) ) {
		wp_send_json_error( $response );
	}

	// Use default nonce.
	$nonce = filter_input( INPUT_POST, 'nonce', FILTER_DEFAULT );
	$check = 'bb-giphy-connect';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$result = bb_check_valid_giphy_api_key( $key, true );

	if ( $result ) {
		wp_send_json_success( $result['response'] );
	}

	wp_send_json_error( $response );
}
add_action( 'wp_ajax_bb_admin_check_valid_giphy_key', 'bb_admin_check_valid_giphy_key' );

/**
 * Validate the email address allowed to register as per the registration restriction settings.
 *
 * @since BuddyBoss 2.4.11
 *
 * @param object $errors error object.
 * @param bool   $update updating user or adding user.
 * @param object $user   user data.
 *
 * @return object
 */
function bb_validate_restricted_email_on_registration( $errors, $update, $user ) {

	// Check if email address allowed.
	if ( ! bb_is_allowed_register_email_address( $user->user_email ) ) {
		if ( $update ) {
			$old_user_data = get_userdata( $user->ID );

			// `get_userdata()` returns false for non-existent users
			// (e.g. when the filter fires mid-creation before the user
			// row is committed, or for stale IDs from custom flows).
			// Treat that as "no previously-saved email to honour" and
			// fall through to the blacklist error.
			if ( $old_user_data && $old_user_data->user_email === $user->user_email ) {
				return $errors;
			}
		}
		$errors->add( 'bb_restricted_email', __( 'This email address or domain has been blacklisted. If you think you are seeing this in error, please contact the site administrator.', 'buddyboss' ), array( 'form-field' => 'email' ) );
	}

	return $errors;
}

/**
 * Validate & prevent email update and related email.
 *
 * @since BuddyBoss 2.4.11
 *
 * @param int $user_id User ID.
 */
function bb_validate_restricted_email_on_profile_update( $user_id ) {

	if (
		! empty( $_REQUEST['email'] ) && // phpcs:ignore
		! empty( $_REQUEST['action'] ) && // phpcs:ignore
		'update' === $_REQUEST['action'] // phpcs:ignore
	) {
		$email         = $_REQUEST['email']; // phpcs:ignore
		$old_user_data = get_userdata( $user_id );

		// `get_userdata()` returns false for non-existent users — bail
		// rather than fatal on `false->user_email`. The downstream
		// "prevent confirmation email" tweak is only meaningful when we
		// know there's a prior email to compare against, so falling
		// through is the correct behaviour for missing users too.
		if (
			$old_user_data &&
			$old_user_data->user_email !== $email &&
			! bb_is_allowed_register_email_address( $email )
		) {

			// Prevent email updates and related email.
			remove_action( 'personal_options_update', 'send_confirmation_on_profile_email' );
			add_filter( 'send_email_change_email', '__return_false', 0 );
		}
	}
}

/**
 * Function to display notice when settings data saved.
 *
 * @since BuddyBoss 2.4.40
 */
function bb_core_settings_saved_notice() {

	// Only handle notices on BuddyBoss pages.
	if ( ! isset( $_GET['page'] ) ) {
		return;
	}

	$page = sanitize_key( wp_unslash( $_GET['page'] ) );

	if ( ! in_array( $page, array( 'bp-settings', 'bp-pages', 'bp-integrations' ), true ) ) {
		return;
	}

	// Check if settings were updated.
	if ( isset( $_GET['updated'] ) || isset( $_GET['edited'] ) || isset( $_GET['added'] ) ) {
		$setting_message       = __( 'Settings saved successfully.', 'buddyboss' );
		$setting_updated       = isset( $_GET['updated'] ) ? sanitize_text_field( wp_unslash( $_GET['updated'] ) ) : '';
		$updated_transient_key = isset( $_GET['updated'] ) ? sanitize_key( wp_unslash( $_GET['updated'] ) ) : '';

		if ( 'emotion_deleted' === $setting_updated && ! empty( $updated_transient_key ) ) {
			$setting_message = get_transient( $updated_transient_key );
			delete_transient( $updated_transient_key );
		} elseif ( 'no_message' === $setting_updated ) {
			$setting_message = '';
		}

		if ( ! empty( $setting_message ) ) {
			add_settings_error(
				'general',
				'settings_updated',
				$setting_message,
				'updated'
			);
		}

		settings_errors( '' );
	}
}

add_action( 'bp_admin_notices', 'bb_core_settings_saved_notice', 1010 );

/**
 * Render the admin header for BuddyBoss related admin pages.
 *
 * @since BuddyBoss 2.14.0
 *
 * @return void
 */
function bb_render_admin_header() {
	$screen = get_current_screen();

	if (
		(
			! empty( $screen->base ) &&
			(
				false !== strpos( $screen->base, 'buddyboss' ) ||
				false !== strpos( $screen->base, 'bp_' ) ||
				false !== strpos( $screen->base, 'bb_' )
			) &&
			(
				! empty( $screen->id ) &&
				(
					// `buddyboss_page_bb-readylaunch` retired in 3.0.0 —
					// submenu deleted, URL redirects to Settings 2.0. Screen ID
					// no longer reachable so the check is dead weight.
					'buddyboss_page_bb-upgrade' !== $screen->id &&
					'buddyboss_page_bb-settings' !== $screen->id
				)
			)
		) ||
		(
			! empty( $screen->post_type ) &&
			(
				'buddyboss_fonts' === $screen->post_type ||
				'bp_ps_form' === $screen->post_type ||
				( function_exists( 'bp_groups_get_group_type_post_type' ) && bp_groups_get_group_type_post_type() === $screen->post_type ) ||
				( function_exists( 'bp_get_member_type_post_type' ) && bp_get_member_type_post_type() === $screen->post_type ) ||
				( function_exists( 'bbp_get_forum_post_type' ) && bbp_get_forum_post_type() === $screen->post_type ) ||
				( function_exists( 'bbp_get_topic_post_type' ) && bbp_get_topic_post_type() === $screen->post_type ) ||
				( function_exists( 'bbp_get_reply_post_type' ) && bbp_get_reply_post_type() === $screen->post_type )
			)
		) || (
			! empty( $screen->taxonomy ) &&
			(
				( function_exists( 'bbp_get_topic_tag_tax_id' ) && bbp_get_topic_tag_tax_id() === $screen->taxonomy )
			)
		)
	) {
		include __DIR__ . '/templates/header.php';
	}
}

add_action( 'in_admin_header', 'bb_render_admin_header', 999 );

/**
 * Redirect legacy Settings 1.0 tabs to Settings 2.0.
 *
 * When Settings 2.0 is active, redirect users accessing old
 * ?page=bp-settings&tab={old-tab} URLs and legacy CPT pages
 * (e.g., edit.php?post_type=bp-group-type) to the corresponding
 * Settings 2.0 feature pages.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_redirect_legacy_settings_to_settings_2() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Redirect only, no data modification.
	$page      = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	$tab       = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
	$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';

	// Always forward bp-settings → bb-settings (even before Settings 2.0 loads).
	// This runs unconditionally because the bp-settings submenu is no longer
	// registered; without this forward the user hits "you are not allowed"
	// when clicking an old bookmark. The tab-mapping logic further down
	// handles ?page=bp-settings&tab=... cases; here we only handle the bare
	// ?page=bp-settings hit with no tab.
	if ( 'bp-settings' === $page && empty( $tab ) ) {
		wp_safe_redirect( bp_get_admin_url( 'admin.php?page=bb-settings' ) );
		exit;
	}

	// Everything below is Settings 2.0-aware redirect logic (tab mappings,
	// CPT redirects, etc.). Skip when Settings 2.0 is not loaded so we don't
	// redirect into a non-existent page.
	if ( ! function_exists( 'bb_register_feature' ) ) {
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		return;
	}

	// Redirect legacy CPT single edit screens (post.php?post=ID&action=edit) to Settings 2.0.
	// These post types are now managed via React admin — the classic editor should not be accessible.
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	$action  = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	// Hoist component-active flags — read once at function entry rather than
	// re-evaluating `bp_is_active( ... )` at each branch below. `bp_is_active()`
	// is already cheap (in-memory hash lookup on `$bp->active_components`), but
	// consolidating here makes the gating obvious to the reader and removes
	// duplicate calls within this ~230-line function: groups 3x, members 3x,
	// forums 2x, invites 2x. Same value within the request — no stale-read risk.
	$groups_active  = bp_is_active( 'groups' );
	$members_active = bp_is_active( 'members' );
	$forums_active  = bp_is_active( 'forums' );
	$invites_active = bp_is_active( 'invites' );

	if ( $post_id > 0 && 'edit' === $action ) {
		$edit_post_type = get_post_type( $post_id );

		// Map of migrated CPT slugs to their Settings 2.0 redirect URLs.
		// Each entry is gated on its component being active *and* the
		// CPT-getter function being defined, because the getter is loaded
		// by the component's loader file — deactivating the component
		// leaves the function undefined and an unguarded call here would
		// fatal during the admin redirect.
		$cpt_redirects = array();

		// Emails CPT — part of bp-core, always available alongside Settings 2.0.
		if ( function_exists( 'bp_get_email_post_type' ) ) {
			$cpt_redirects[ bp_get_email_post_type() ] = 'admin.php?page=bb-settings&tab=emails&panel=all_emails';
		}

		// Invites CPT — requires the invites component.
		if ( $invites_active && function_exists( 'bp_get_invite_post_type' ) ) {
			$cpt_redirects[ bp_get_invite_post_type() ] = 'admin.php?page=bb-settings&tab=invites&panel=invites_list';
		}

		// Group Types CPT — requires the groups component.
		if ( $groups_active && function_exists( 'bp_groups_get_group_type_post_type' ) ) {
			$cpt_redirects[ bp_groups_get_group_type_post_type() ] = 'admin.php?page=bb-settings&tab=groups&panel=group_types';
		}

		// Profile (member) Types CPT — requires the members component.
		if ( $members_active && function_exists( 'bp_get_member_type_post_type' ) ) {
			$cpt_redirects[ bp_get_member_type_post_type() ] = 'admin.php?page=bb-settings&tab=members&panel=profile_types';
		}

		// Forum CPTs (only when forums component is active).
		if ( $forums_active ) {
			$cpt_redirects['forum'] = 'admin.php?page=bb-settings&tab=forums&panel=all_forums';
			$cpt_redirects['topic'] = 'admin.php?page=bb-settings&tab=forums&panel=discussions';
			$cpt_redirects['reply'] = 'admin.php?page=bb-settings&tab=forums&panel=replies';
		}

		/**
		 * Filters the CPT edit screen redirect map for Settings 2.0.
		 *
		 * Third-party plugins can add their own CPT redirects when they
		 * migrate admin screens to Settings 2.0.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param array $cpt_redirects Post type slug => Settings 2.0 URL path.
		 */
		$cpt_redirects = apply_filters( 'bb_legacy_cpt_edit_redirects', $cpt_redirects );

		if ( $edit_post_type && isset( $cpt_redirects[ $edit_post_type ] ) ) {
			wp_safe_redirect( bp_get_admin_url( $cpt_redirects[ $edit_post_type ] ) );
			exit;
		}
	}

	// Redirect legacy Group Types CPT page (edit.php?post_type=bp-group-type).
	// Only when groups component is active; otherwise the target panel is
	// not registered and we'd redirect into a 404'd settings tab.
	if ( 'bp-group-type' === $post_type && $groups_active ) {
		wp_safe_redirect( bp_get_admin_url( 'admin.php?page=bb-settings&tab=groups&panel=group_types' ) );
		exit;
	}

	// Redirect legacy Groups admin page (admin.php?page=bp-groups).
	if ( 'bp-groups' === $page && $groups_active ) {
		wp_safe_redirect( bp_get_admin_url( 'admin.php?page=bb-settings&tab=groups&panel=all_groups' ) );
		exit;
	}

	// Redirect legacy Profile Fields admin page (admin.php?page=bp-profile-setup).
	if ( 'bp-profile-setup' === $page && bp_is_active( 'xprofile' ) ) {
		wp_safe_redirect( bp_get_admin_url( 'admin.php?page=bb-settings&tab=members&panel=profile_fields' ) );
		exit;
	}

	// Redirect legacy Member Types CPT page (edit.php?post_type=bp-member-type).
	// `bp_get_member_type_post_type()` is loaded by the members component;
	// guard the call so a deactivated members component doesn't fatal here.
	if ( $members_active && function_exists( 'bp_get_member_type_post_type' ) && bp_get_member_type_post_type() === $post_type ) {
		wp_safe_redirect( bp_get_admin_url( 'admin.php?page=bb-settings&tab=members&panel=profile_types' ) );
		exit;
	}

	// Redirect legacy Profile Search CPT page (edit.php?post_type=bp_ps_form).
	if ( 'bp_ps_form' === $post_type ) {
		wp_safe_redirect( bp_get_admin_url( 'admin.php?page=bb-settings&tab=members&panel=profile_search' ) );
		exit;
	}

	// Redirect legacy Forum CPT pages to Settings 2.0.
	if ( $forums_active ) {
		// Check taxonomy first — edit-tags.php passes both taxonomy and post_type params.
		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
		if ( 'topic-tag' === $taxonomy ) {
			wp_safe_redirect( bp_get_admin_url( 'admin.php?page=bb-settings&tab=forums&panel=discussion_tags' ) );
			exit;
		}

		if ( 'forum' === $post_type ) {
			wp_safe_redirect( bp_get_admin_url( 'admin.php?page=bb-settings&tab=forums&panel=all_forums' ) );
			exit;
		}

		if ( 'topic' === $post_type ) {
			wp_safe_redirect( bp_get_admin_url( 'admin.php?page=bb-settings&tab=forums&panel=discussions' ) );
			exit;
		}

		if ( 'reply' === $post_type ) {
			wp_safe_redirect( bp_get_admin_url( 'admin.php?page=bb-settings&tab=forums&panel=replies' ) );
			exit;
		}
	}

	// Redirect legacy Email Invites CPT page (edit.php?post_type=bp-invite).
	// Mirrors the gating used in the `?action=edit` CPT map above: deactivating
	// the invites component removes the target Settings 2.0 panel, so without
	// the active check we'd redirect into a 404'd settings tab.
	if ( $invites_active && function_exists( 'bp_get_invite_post_type' ) && bp_get_invite_post_type() === $post_type ) {
		wp_safe_redirect( bp_get_admin_url( 'admin.php?page=bb-settings&tab=invites&panel=invites_list' ) );
		exit;
	}

	// Redirect legacy Email Templates CPT page (edit.php?post_type=bp-email).
	if ( function_exists( 'bp_get_email_post_type' ) && bp_get_email_post_type() === $post_type ) {
		wp_safe_redirect( bp_get_admin_url( 'admin.php?page=bb-settings&tab=emails&panel=all_emails' ) );
		exit;
	}

	// Note: legacy `?page=bb-readylaunch` redirect is handled earlier in
	// `bb_redirect_bp_settings_before_permission_check()` on
	// `admin_menu @ PHP_INT_MAX`, which fires before WP's permission check
	// — required because the standalone submenu was unregistered during the
	// Settings 2.0 migration. Any redirect at `bp_admin_init` priority would
	// be too late and the request would 403 before reaching this hook.

	// Redirect legacy integration tabs (bp-integrations page).
	if ( 'bp-integrations' === $page && ! empty( $tab ) ) {

		/**
		 * Filter the legacy integration tabs mapping.
		 *
		 * Pro hooks this to add Zoom, OneSignal, and other integration tab redirects.
		 * Values can be a string (feature ID only) or array with 'tab' and 'panel'.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param array $legacy_integration_tabs Array of old integration tab name => new Settings 2.0 route.
		 */
		$legacy_integration_tabs = apply_filters( 'bb_legacy_integration_tabs_mapping', array() );

		if ( isset( $legacy_integration_tabs[ $tab ] ) ) {
			$mapping  = $legacy_integration_tabs[ $tab ];
			$redirect = bp_get_admin_url( 'admin.php?page=bb-settings' );

			if ( is_array( $mapping ) ) {
				$redirect = add_query_arg( 'tab', $mapping['tab'], $redirect );
				if ( ! empty( $mapping['panel'] ) ) {
					$redirect = add_query_arg( 'panel', $mapping['panel'], $redirect );
				}
			} else {
				$redirect = add_query_arg( 'tab', $mapping, $redirect );
			}

			wp_safe_redirect( $redirect );
			exit;
		}
	}

	// Bare ?page=bp-settings (no tab) was already forwarded at the top of
	// this function, so by the time we reach here we only handle the
	// tab-mapping path. Note: in the usual request flow the earlier
	// `admin_menu`-priority redirect (`bb_redirect_bp_settings_before_permission_check`)
	// already normalized the tab before this point, so this branch is mainly
	// a safety net for callers that reach `bp_admin_init` with an unredirected
	// `?page=bp-settings&tab=...` URL (e.g., REST / WP-CLI admin-init callers).
	if ( 'bp-settings' !== $page || empty( $tab ) ) {
		return;
	}

	$legacy_tabs_mapping = bb_get_legacy_settings_tabs_mapping();

	if ( isset( $legacy_tabs_mapping[ $tab ] ) ) {
		$redirect = bb_apply_legacy_tab_mapping_to_url(
			bp_get_admin_url( 'admin.php?page=bb-settings' ),
			$legacy_tabs_mapping[ $tab ]
		);

		wp_safe_redirect( $redirect );
		exit;
	}
}
