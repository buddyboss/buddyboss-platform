<?php
/**
 * BuddyBoss Invites component admin screen.
 *
 * Legacy CPT list table code removed — migrated to Settings 2.0
 * via BBoss_Admin_Invites_Ajax and InvitesListScreen.js.
 *
 * @package BuddyBoss\Invites
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases-pre — pluggable functions (decision 0013): a site that defined the
// pre-rename name first keeps its override, and Platform's guarded declaration below stands down.
if ( function_exists( 'bb_invites_modify_posts_distinct_request' ) && ! function_exists( 'bboss_invites_modify_posts_distinct_request' ) ) {
	// Pluggable: bb_invites_modify_posts_distinct_request() was defined first (bp-custom.php, a plugin); bboss_invites_modify_posts_distinct_request() delegates to it.
	function bboss_invites_modify_posts_distinct_request( $distinct, $query ) {
		return bb_invites_modify_posts_distinct_request( $distinct, $query );
	}
}
if ( function_exists( 'bb_invites_modify_posts_join_request' ) && ! function_exists( 'bboss_invites_modify_posts_join_request' ) ) {
	// Pluggable: bb_invites_modify_posts_join_request() was defined first (bp-custom.php, a plugin); bboss_invites_modify_posts_join_request() delegates to it.
	function bboss_invites_modify_posts_join_request( $join, $query ) {
		return bb_invites_modify_posts_join_request( $join, $query );
	}
}
if ( function_exists( 'bb_invites_modify_posts_where_request' ) && ! function_exists( 'bboss_invites_modify_posts_where_request' ) ) {
	// Pluggable: bb_invites_modify_posts_where_request() was defined first (bp-custom.php, a plugin); bboss_invites_modify_posts_where_request() delegates to it.
	function bboss_invites_modify_posts_where_request( $where, $query ) {
		return bb_invites_modify_posts_where_request( $where, $query );
	}
}
if ( function_exists( 'bp_invite_add_column' ) && ! function_exists( 'bboss_invite_add_column' ) ) {
	// Pluggable: bp_invite_add_column() was defined first (bp-custom.php, a plugin); bboss_invite_add_column() delegates to it.
	function bboss_invite_add_column( $columns ) {
		return bp_invite_add_column( $columns );
	}
}
if ( function_exists( 'bp_invite_add_request_filter' ) && ! function_exists( 'bboss_invite_add_request_filter' ) ) {
	// Pluggable: bp_invite_add_request_filter() was defined first (bp-custom.php, a plugin); bboss_invite_add_request_filter() delegates to it.
	function bboss_invite_add_request_filter() {
		return bp_invite_add_request_filter();
	}
}
if ( function_exists( 'bp_invite_add_sortable_columns' ) && ! function_exists( 'bboss_invite_add_sortable_columns' ) ) {
	// Pluggable: bp_invite_add_sortable_columns() was defined first (bp-custom.php, a plugin); bboss_invite_add_sortable_columns() delegates to it.
	function bboss_invite_add_sortable_columns( $columns ) {
		return bp_invite_add_sortable_columns( $columns );
	}
}
if ( function_exists( 'bp_invite_bulk_action_notices' ) && ! function_exists( 'bboss_invite_bulk_action_notices' ) ) {
	// Pluggable: bp_invite_bulk_action_notices() was defined first (bp-custom.php, a plugin); bboss_invite_bulk_action_notices() delegates to it.
	function bboss_invite_bulk_action_notices() {
		return bp_invite_bulk_action_notices();
	}
}
if ( function_exists( 'bp_invite_hide_quick_edit' ) && ! function_exists( 'bboss_invite_hide_quick_edit' ) ) {
	// Pluggable: bp_invite_hide_quick_edit() was defined first (bp-custom.php, a plugin); bboss_invite_hide_quick_edit() delegates to it.
	function bboss_invite_hide_quick_edit( $actions, $post ) {
		return bp_invite_hide_quick_edit( $actions, $post );
	}
}
if ( function_exists( 'bp_invite_show_data' ) && ! function_exists( 'bboss_invite_show_data' ) ) {
	// Pluggable: bp_invite_show_data() was defined first (bp-custom.php, a plugin); bboss_invite_show_data() delegates to it.
	function bboss_invite_show_data( $column, $post_id ) {
		return bp_invite_show_data( $column, $post_id );
	}
}
if ( function_exists( 'bp_invite_sort_items' ) && ! function_exists( 'bboss_invite_sort_items' ) ) {
	// Pluggable: bp_invite_sort_items() was defined first (bp-custom.php, a plugin); bboss_invite_sort_items() delegates to it.
	function bboss_invite_sort_items( $qv ) {
		return bp_invite_sort_items( $qv );
	}
}
if ( function_exists( 'bp_invites_bulk_action_handler' ) && ! function_exists( 'bboss_invites_bulk_action_handler' ) ) {
	// Pluggable: bp_invites_bulk_action_handler() was defined first (bp-custom.php, a plugin); bboss_invites_bulk_action_handler() delegates to it.
	function bboss_invites_bulk_action_handler( $redirect, $doaction, $object_ids ) {
		return bp_invites_bulk_action_handler( $redirect, $doaction, $object_ids );
	}
}
if ( function_exists( 'bp_invites_js_bulk_admin_footer' ) && ! function_exists( 'bboss_invites_js_bulk_admin_footer' ) ) {
	// Pluggable: bp_invites_js_bulk_admin_footer() was defined first (bp-custom.php, a plugin); bboss_invites_js_bulk_admin_footer() delegates to it.
	function bboss_invites_js_bulk_admin_footer() {
		return bp_invites_js_bulk_admin_footer();
	}
}
if ( function_exists( 'bp_invites_remove_bulk_actions' ) && ! function_exists( 'bboss_invites_remove_bulk_actions' ) ) {
	// Pluggable: bp_invites_remove_bulk_actions() was defined first (bp-custom.php, a plugin); bboss_invites_remove_bulk_actions() delegates to it.
	function bboss_invites_remove_bulk_actions( $actions ) {
		return bp_invites_remove_bulk_actions( $actions );
	}
}
if ( function_exists( 'bp_register_invite_type_sections_filters_actions' ) && ! function_exists( 'bboss_register_invite_type_sections_filters_actions' ) ) {
	// Pluggable: bp_register_invite_type_sections_filters_actions() was defined first (bp-custom.php, a plugin); bboss_register_invite_type_sections_filters_actions() delegates to it.
	function bboss_register_invite_type_sections_filters_actions() {
		return bp_register_invite_type_sections_filters_actions();
	}
}
// @bbwt-com-end compat-layer

/**
 * Register the Invites component admin menu pointing to Settings 2.0.
 *
 * @since BuddyBoss 1.0.0
 * @since BuddyBoss 3.0.0 Updated to point to Settings 2.0 React list screen.
 */
function bboss_invites_add_admin_menu() {
	global $submenu;

	$settings_url = function_exists( 'bboss_get_feature_settings_url' )
		? bboss_get_feature_settings_url( 'invites', 'invites_list' )
		: admin_url( 'admin.php?page=bb-settings&tab=invites&panel=invites_list' );

	$submenu['buddyboss-platform'][] = array(
		__( 'Invites', 'buddyboss' ),
		'bp_moderate',
		$settings_url,
	);
}
add_action( bboss_core_admin_hook(), 'bp_invites_add_admin_menu', 65 );

/**
 * Register the Invites submenu for multisite network admin.
 *
 * @since BuddyBoss 1.0.0
 * @since BuddyBoss 3.0.0 Updated to point to Settings 2.0 React list screen.
 */
function bboss_invites_add_sub_menu_page_admin_menu() {

	if ( is_multisite() && bboss_is_network_activated() ) {
		$settings_url = function_exists( 'bboss_get_feature_settings_url' )
			? bboss_get_feature_settings_url( 'invites', 'invites_list' )
			: admin_url( 'admin.php?page=bb-settings&tab=invites&panel=invites_list' );

		add_submenu_page(
			'buddyboss-platform',
			__( 'Invites', 'buddyboss' ),
			__( 'Invites', 'buddyboss' ),
			'bp_moderate',
			$settings_url,
			''
		);
	}
}
add_action( 'admin_menu', 'bp_invites_add_sub_menu_page_admin_menu', 10 );

/**
 * Add Invites menu item to custom menus array.
 *
 * @since BuddyBoss 1.0.0
 * @since BuddyBoss 3.0.0 Updated to point to Settings 2.0 React list screen.
 *
 * @param array $custom_menus The list of top-level BP menu items.
 * @return array
 */
function bboss_invites_admin_menu_order( $custom_menus = array() ) {

	$settings_url = function_exists( 'bboss_get_feature_settings_url' )
		? bboss_get_feature_settings_url( 'invites', 'invites_list' )
		: admin_url( 'admin.php?page=bb-settings&tab=invites&panel=invites_list' );

	array_push( $custom_menus, $settings_url );

	// Network admin: also push the root site's Settings 2.0 URL (matches emails_admin_menu_order pattern).
	if ( is_network_admin() && bboss_is_network_activated() ) {
		array_push(
			$custom_menus,
			get_admin_url( bboss_get_root_blog_id(), 'admin.php?page=bb-settings&tab=invites&panel=invites_list' )
		);
	}

	return $custom_menus;
}
add_filter( 'bp_admin_menu_order', 'invites_admin_menu_order', 20 );

// ──────────────────────────────────────────────────────────────────────────────
// DEPRECATED FUNCTIONS — Legacy CPT list table (migrated to Settings 2.0).
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bboss_register_invite_type_sections_filters_actions' ) ) {
	/**
	 * Registers the invite admin action and filters.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 {@see BBoss_Admin_Invites_Ajax}.
	 */
	function bboss_register_invite_type_sections_filters_actions() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'BBoss_Admin_Invites_Ajax' );
	}
}

if ( ! function_exists( 'bboss_invite_add_column' ) ) {
	/**
	 * Add columns to invite list table.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	function bboss_invite_add_column( $columns ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
		return $columns;
	}
}

if ( ! function_exists( 'bboss_invite_show_data' ) ) {
	/**
	 * Display data by column and post id.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	function bboss_invite_show_data( $column, $post_id ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
	}
}

if ( ! function_exists( 'bboss_invite_add_sortable_columns' ) ) {
	/**
	 * Add sortable columns.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	function bboss_invite_add_sortable_columns( $columns ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
		return $columns;
	}
}

if ( ! function_exists( 'bboss_invite_add_request_filter' ) ) {
	/**
	 * Add filter to invite sort items.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0
	 */
	function bboss_invite_add_request_filter() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
	}
}

if ( ! function_exists( 'bboss_invite_sort_items' ) ) {
	/**
	 * Sort invite list.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0
	 *
	 * @param array $qv Query vars.
	 * @return array
	 */
	function bboss_invite_sort_items( $qv ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
		return $qv;
	}
}

if ( ! function_exists( 'bboss_invite_hide_quick_edit' ) ) {
	/**
	 * Hide quick edit link.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0
	 *
	 * @param array   $actions Row actions.
	 * @param WP_Post $post    Post object.
	 * @return array
	 */
	function bboss_invite_hide_quick_edit( $actions, $post ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
		return $actions;
	}
}

if ( ! function_exists( 'bboss_invites_remove_bulk_actions' ) ) {
	/**
	 * Remove bulk actions.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0
	 *
	 * @param array $actions Bulk actions.
	 * @return array
	 */
	function bboss_invites_remove_bulk_actions( $actions ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
		return $actions;
	}
}

if ( ! function_exists( 'bboss_invites_bulk_action_handler' ) ) {
	/**
	 * Bulk revoke handler.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0
	 *
	 * @param string $redirect   Redirect URL.
	 * @param string $doaction   Action name.
	 * @param array  $object_ids Post IDs.
	 * @return string
	 */
	function bboss_invites_bulk_action_handler( $redirect, $doaction, $object_ids ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
		return $redirect;
	}
}

if ( ! function_exists( 'bboss_invite_bulk_action_notices' ) ) {
	/**
	 * Bulk action admin notice.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0
	 */
	function bboss_invite_bulk_action_notices() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
	}
}

if ( ! function_exists( 'bboss_invites_js_bulk_admin_footer' ) ) {
	/**
	 * JS confirm for bulk revoke.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0
	 */
	function bboss_invites_js_bulk_admin_footer() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
	}
}

if ( ! function_exists( 'bboss_invites_modify_posts_distinct_request' ) ) {
	/**
	 * Modify posts distinct for invite search.
	 *
	 * @since BuddyBoss 2.2.4
	 * @deprecated BuddyBoss 3.0.0
	 *
	 * @param string   $distinct DISTINCT clause.
	 * @param WP_Query $query    Query instance.
	 * @return string
	 */
	function bboss_invites_modify_posts_distinct_request( $distinct, $query ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
		return $distinct;
	}
}

if ( ! function_exists( 'bboss_invites_modify_posts_join_request' ) ) {
	/**
	 * Modify posts join for invite search.
	 *
	 * @since BuddyBoss 2.2.4
	 * @deprecated BuddyBoss 3.0.0
	 *
	 * @param string   $join  JOIN clause.
	 * @param WP_Query $query Query instance.
	 * @return string
	 */
	function bboss_invites_modify_posts_join_request( $join, $query ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
		return $join;
	}
}

if ( ! function_exists( 'bboss_invites_modify_posts_where_request' ) ) {
	/**
	 * Modify posts where for invite search.
	 *
	 * @since BuddyBoss 2.2.4
	 * @deprecated BuddyBoss 3.0.0
	 *
	 * @param string   $where WHERE clause.
	 * @param WP_Query $query Query instance.
	 * @return string
	 */
	function bboss_invites_modify_posts_where_request( $where, $query ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
		return $where;
	}
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_invites_modify_posts_distinct_request' ) && ! function_exists( 'bb_invites_modify_posts_distinct_request' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invites_modify_posts_distinct_request()}.
	 *
	 * @deprecated Use bboss_invites_modify_posts_distinct_request() instead.
	 */
	function bb_invites_modify_posts_distinct_request( $distinct, $query ) {
		return bboss_invites_modify_posts_distinct_request( $distinct, $query );
	}
}
if ( function_exists( 'bboss_invites_modify_posts_join_request' ) && ! function_exists( 'bb_invites_modify_posts_join_request' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invites_modify_posts_join_request()}.
	 *
	 * @deprecated Use bboss_invites_modify_posts_join_request() instead.
	 */
	function bb_invites_modify_posts_join_request( $join, $query ) {
		return bboss_invites_modify_posts_join_request( $join, $query );
	}
}
if ( function_exists( 'bboss_invites_modify_posts_where_request' ) && ! function_exists( 'bb_invites_modify_posts_where_request' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invites_modify_posts_where_request()}.
	 *
	 * @deprecated Use bboss_invites_modify_posts_where_request() instead.
	 */
	function bb_invites_modify_posts_where_request( $where, $query ) {
		return bboss_invites_modify_posts_where_request( $where, $query );
	}
}
if ( function_exists( 'bboss_invite_add_column' ) && ! function_exists( 'bp_invite_add_column' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invite_add_column()}.
	 *
	 * @deprecated Use bboss_invite_add_column() instead.
	 */
	function bp_invite_add_column( $columns ) {
		return bboss_invite_add_column( $columns );
	}
}
if ( function_exists( 'bboss_invite_add_request_filter' ) && ! function_exists( 'bp_invite_add_request_filter' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invite_add_request_filter()}.
	 *
	 * @deprecated Use bboss_invite_add_request_filter() instead.
	 */
	function bp_invite_add_request_filter() {
		return bboss_invite_add_request_filter();
	}
}
if ( function_exists( 'bboss_invite_add_sortable_columns' ) && ! function_exists( 'bp_invite_add_sortable_columns' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invite_add_sortable_columns()}.
	 *
	 * @deprecated Use bboss_invite_add_sortable_columns() instead.
	 */
	function bp_invite_add_sortable_columns( $columns ) {
		return bboss_invite_add_sortable_columns( $columns );
	}
}
if ( function_exists( 'bboss_invite_bulk_action_notices' ) && ! function_exists( 'bp_invite_bulk_action_notices' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invite_bulk_action_notices()}.
	 *
	 * @deprecated Use bboss_invite_bulk_action_notices() instead.
	 */
	function bp_invite_bulk_action_notices() {
		return bboss_invite_bulk_action_notices();
	}
}
if ( function_exists( 'bboss_invite_hide_quick_edit' ) && ! function_exists( 'bp_invite_hide_quick_edit' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invite_hide_quick_edit()}.
	 *
	 * @deprecated Use bboss_invite_hide_quick_edit() instead.
	 */
	function bp_invite_hide_quick_edit( $actions, $post ) {
		return bboss_invite_hide_quick_edit( $actions, $post );
	}
}
if ( function_exists( 'bboss_invite_show_data' ) && ! function_exists( 'bp_invite_show_data' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invite_show_data()}.
	 *
	 * @deprecated Use bboss_invite_show_data() instead.
	 */
	function bp_invite_show_data( $column, $post_id ) {
		return bboss_invite_show_data( $column, $post_id );
	}
}
if ( function_exists( 'bboss_invite_sort_items' ) && ! function_exists( 'bp_invite_sort_items' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invite_sort_items()}.
	 *
	 * @deprecated Use bboss_invite_sort_items() instead.
	 */
	function bp_invite_sort_items( $qv ) {
		return bboss_invite_sort_items( $qv );
	}
}
if ( function_exists( 'bboss_invites_add_admin_menu' ) && ! function_exists( 'bp_invites_add_admin_menu' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invites_add_admin_menu()}.
	 *
	 * @deprecated Use bboss_invites_add_admin_menu() instead.
	 */
	function bp_invites_add_admin_menu() {
		return bboss_invites_add_admin_menu();
	}
}
if ( function_exists( 'bboss_invites_add_sub_menu_page_admin_menu' ) && ! function_exists( 'bp_invites_add_sub_menu_page_admin_menu' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invites_add_sub_menu_page_admin_menu()}.
	 *
	 * @deprecated Use bboss_invites_add_sub_menu_page_admin_menu() instead.
	 */
	function bp_invites_add_sub_menu_page_admin_menu() {
		return bboss_invites_add_sub_menu_page_admin_menu();
	}
}
if ( function_exists( 'bboss_invites_bulk_action_handler' ) && ! function_exists( 'bp_invites_bulk_action_handler' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invites_bulk_action_handler()}.
	 *
	 * @deprecated Use bboss_invites_bulk_action_handler() instead.
	 */
	function bp_invites_bulk_action_handler( $redirect, $doaction, $object_ids ) {
		return bboss_invites_bulk_action_handler( $redirect, $doaction, $object_ids );
	}
}
if ( function_exists( 'bboss_invites_js_bulk_admin_footer' ) && ! function_exists( 'bp_invites_js_bulk_admin_footer' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invites_js_bulk_admin_footer()}.
	 *
	 * @deprecated Use bboss_invites_js_bulk_admin_footer() instead.
	 */
	function bp_invites_js_bulk_admin_footer() {
		return bboss_invites_js_bulk_admin_footer();
	}
}
if ( function_exists( 'bboss_invites_remove_bulk_actions' ) && ! function_exists( 'bp_invites_remove_bulk_actions' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invites_remove_bulk_actions()}.
	 *
	 * @deprecated Use bboss_invites_remove_bulk_actions() instead.
	 */
	function bp_invites_remove_bulk_actions( $actions ) {
		return bboss_invites_remove_bulk_actions( $actions );
	}
}
if ( function_exists( 'bboss_register_invite_type_sections_filters_actions' ) && ! function_exists( 'bp_register_invite_type_sections_filters_actions' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_register_invite_type_sections_filters_actions()}.
	 *
	 * @deprecated Use bboss_register_invite_type_sections_filters_actions() instead.
	 */
	function bp_register_invite_type_sections_filters_actions() {
		return bboss_register_invite_type_sections_filters_actions();
	}
}
if ( function_exists( 'bboss_invites_admin_menu_order' ) && ! function_exists( 'invites_admin_menu_order' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invites_admin_menu_order()}.
	 *
	 * @deprecated Use bboss_invites_admin_menu_order() instead.
	 */
	function invites_admin_menu_order( $custom_menus = array() ) {
		return bboss_invites_admin_menu_order( $custom_menus );
	}
}
// @bbwt-com-end compat-layer
