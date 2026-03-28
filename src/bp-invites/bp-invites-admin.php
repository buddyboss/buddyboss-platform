<?php
/**
 * BuddyBoss Invites component admin screen.
 *
 * Legacy CPT list table code removed — migrated to Settings 2.0
 * via BB_Admin_Invites_Ajax and InvitesListScreen.js.
 *
 * @package BuddyBoss\Invites
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register the Invites component admin menu pointing to Settings 2.0.
 *
 * @since BuddyBoss 1.0.0
 * @since BuddyBoss [BBVERSION] Updated to point to Settings 2.0 React list screen.
 */
function bp_invites_add_admin_menu() {
	global $submenu;

	$settings_url = function_exists( 'bb_get_feature_settings_url' )
		? bb_get_feature_settings_url( 'invites', 'invites_list' )
		: admin_url( 'admin.php?page=bb-settings&tab=invites&panel=invites_list' );

	$submenu['buddyboss-platform'][] = array(
		__( 'Invites', 'buddyboss' ),
		'bp_moderate',
		$settings_url,
	);
}
add_action( bp_core_admin_hook(), 'bp_invites_add_admin_menu', 65 );

/**
 * Register the Invites submenu for multisite network admin.
 *
 * @since BuddyBoss 1.0.0
 * @since BuddyBoss [BBVERSION] Updated to point to Settings 2.0 React list screen.
 */
function bp_invites_add_sub_menu_page_admin_menu() {

	if ( is_multisite() && bp_is_network_activated() ) {
		$settings_url = function_exists( 'bb_get_feature_settings_url' )
			? bb_get_feature_settings_url( 'invites', 'invites_list' )
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
 * @since BuddyBoss [BBVERSION] Updated to point to Settings 2.0 React list screen.
 *
 * @param array $custom_menus The list of top-level BP menu items.
 * @return array
 */
function invites_admin_menu_order( $custom_menus = array() ) {

	$settings_url = function_exists( 'bb_get_feature_settings_url' )
		? bb_get_feature_settings_url( 'invites', 'invites_list' )
		: admin_url( 'admin.php?page=bb-settings&tab=invites&panel=invites_list' );

	array_push( $custom_menus, $settings_url );

	return $custom_menus;
}
add_filter( 'bp_admin_menu_order', 'invites_admin_menu_order', 20 );

// ──────────────────────────────────────────────────────────────────────────────
// DEPRECATED FUNCTIONS — Legacy CPT list table (migrated to Settings 2.0).
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bp_register_invite_type_sections_filters_actions' ) ) {
	/**
	 * Registers the invite admin action and filters.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 {@see BB_Admin_Invites_Ajax}.
	 */
	function bp_register_invite_type_sections_filters_actions() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'BB_Admin_Invites_Ajax' );
	}
}

if ( ! function_exists( 'bp_invite_add_column' ) ) {
	/**
	 * Add columns to invite list table.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION]
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	function bp_invite_add_column( $columns ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]' );
		return $columns;
	}
}

if ( ! function_exists( 'bp_invite_show_data' ) ) {
	/**
	 * Display data by column and post id.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION]
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	function bp_invite_show_data( $column, $post_id ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]' );
	}
}

if ( ! function_exists( 'bp_invite_add_sortable_columns' ) ) {
	/**
	 * Add sortable columns.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION]
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	function bp_invite_add_sortable_columns( $columns ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]' );
		return $columns;
	}
}

if ( ! function_exists( 'bp_invite_add_request_filter' ) ) {
	/**
	 * Add filter to invite sort items.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION]
	 */
	function bp_invite_add_request_filter() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]' );
	}
}

if ( ! function_exists( 'bp_invite_sort_items' ) ) {
	/**
	 * Sort invite list.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION]
	 *
	 * @param array $qv Query vars.
	 * @return array
	 */
	function bp_invite_sort_items( $qv ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]' );
		return $qv;
	}
}

if ( ! function_exists( 'bp_invite_hide_quick_edit' ) ) {
	/**
	 * Hide quick edit link.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION]
	 *
	 * @param array   $actions Row actions.
	 * @param WP_Post $post    Post object.
	 * @return array
	 */
	function bp_invite_hide_quick_edit( $actions, $post ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]' );
		return $actions;
	}
}

if ( ! function_exists( 'bp_invites_remove_bulk_actions' ) ) {
	/**
	 * Remove bulk actions.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION]
	 *
	 * @param array $actions Bulk actions.
	 * @return array
	 */
	function bp_invites_remove_bulk_actions( $actions ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]' );
		return $actions;
	}
}

if ( ! function_exists( 'bp_invites_bulk_action_handler' ) ) {
	/**
	 * Bulk revoke handler.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION]
	 *
	 * @param string $redirect   Redirect URL.
	 * @param string $doaction   Action name.
	 * @param array  $object_ids Post IDs.
	 * @return string
	 */
	function bp_invites_bulk_action_handler( $redirect, $doaction, $object_ids ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]' );
		return $redirect;
	}
}

if ( ! function_exists( 'bp_invite_bulk_action_notices' ) ) {
	/**
	 * Bulk action admin notice.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION]
	 */
	function bp_invite_bulk_action_notices() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]' );
	}
}

if ( ! function_exists( 'bp_invites_js_bulk_admin_footer' ) ) {
	/**
	 * JS confirm for bulk revoke.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION]
	 */
	function bp_invites_js_bulk_admin_footer() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]' );
	}
}

if ( ! function_exists( 'bb_invites_modify_posts_distinct_request' ) ) {
	/**
	 * Modify posts distinct for invite search.
	 *
	 * @since BuddyBoss 2.2.4
	 * @deprecated BuddyBoss [BBVERSION]
	 *
	 * @param string   $distinct DISTINCT clause.
	 * @param WP_Query $query    Query instance.
	 * @return string
	 */
	function bb_invites_modify_posts_distinct_request( $distinct, $query ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]' );
		return $distinct;
	}
}

if ( ! function_exists( 'bb_invites_modify_posts_join_request' ) ) {
	/**
	 * Modify posts join for invite search.
	 *
	 * @since BuddyBoss 2.2.4
	 * @deprecated BuddyBoss [BBVERSION]
	 *
	 * @param string   $join  JOIN clause.
	 * @param WP_Query $query Query instance.
	 * @return string
	 */
	function bb_invites_modify_posts_join_request( $join, $query ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]' );
		return $join;
	}
}

if ( ! function_exists( 'bb_invites_modify_posts_where_request' ) ) {
	/**
	 * Modify posts where for invite search.
	 *
	 * @since BuddyBoss 2.2.4
	 * @deprecated BuddyBoss [BBVERSION]
	 *
	 * @param string   $where WHERE clause.
	 * @param WP_Query $query Query instance.
	 * @return string
	 */
	function bb_invites_modify_posts_where_request( $where, $query ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]' );
		return $where;
	}
}
