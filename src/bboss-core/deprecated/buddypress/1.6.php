<?php
/**
 * Deprecated Functions
 *
 * @package BuddyBoss\Core
 * @deprecated BuddyPress 1.6.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Toolbar functions *********************************************************/

/**
 * bboss_admin_bar_remove_wp_menus()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_admin_bar_remove_wp_menus() {
	_deprecated_function( __FUNCTION__, '1.6' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_admin_bar_remove_wp_menus' ) && ! function_exists( 'bp_admin_bar_remove_wp_menus' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_bar_remove_wp_menus()}.
	 *
	 * @deprecated Use bboss_admin_bar_remove_wp_menus() instead.
	 */
	function bp_admin_bar_remove_wp_menus() {
		return bboss_admin_bar_remove_wp_menus();
	}
}
// @bbwt-com-end compat-layer


/**
 * bboss_admin_bar_root_site()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_admin_bar_root_site() {
	_deprecated_function( __FUNCTION__, '1.6' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_admin_bar_root_site' ) && ! function_exists( 'bp_admin_bar_root_site' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_bar_root_site()}.
	 *
	 * @deprecated Use bboss_admin_bar_root_site() instead.
	 */
	function bp_admin_bar_root_site() {
		return bboss_admin_bar_root_site();
	}
}
// @bbwt-com-end compat-layer


/**
 * bboss_admin_bar_my_sites_menu()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_admin_bar_my_sites_menu() {
	_deprecated_function( __FUNCTION__, '1.6' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_admin_bar_my_sites_menu' ) && ! function_exists( 'bp_admin_bar_my_sites_menu' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_bar_my_sites_menu()}.
	 *
	 * @deprecated Use bboss_admin_bar_my_sites_menu() instead.
	 */
	function bp_admin_bar_my_sites_menu() {
		return bboss_admin_bar_my_sites_menu();
	}
}
// @bbwt-com-end compat-layer


/**
 * bp_admin_bar_comments_menu( $wp_admin_bar = '' )
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_admin_bar_comments_menu( $wp_admin_bar = '' ) {
	_deprecated_function( __FUNCTION__, '1.6' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_admin_bar_comments_menu' ) && ! function_exists( 'bp_admin_bar_comments_menu' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_bar_comments_menu()}.
	 *
	 * @deprecated Use bboss_admin_bar_comments_menu() instead.
	 */
	function bp_admin_bar_comments_menu( $wp_admin_bar = '' ) {
		return bboss_admin_bar_comments_menu( $wp_admin_bar );
	}
}
// @bbwt-com-end compat-layer


/**
 * bboss_admin_bar_appearance_menu()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_admin_bar_appearance_menu() {
	_deprecated_function( __FUNCTION__, '1.6' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_admin_bar_appearance_menu' ) && ! function_exists( 'bp_admin_bar_appearance_menu' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_bar_appearance_menu()}.
	 *
	 * @deprecated Use bboss_admin_bar_appearance_menu() instead.
	 */
	function bp_admin_bar_appearance_menu() {
		return bboss_admin_bar_appearance_menu();
	}
}
// @bbwt-com-end compat-layer


/**
 * bboss_admin_bar_updates_menu()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_admin_bar_updates_menu() {
	_deprecated_function( __FUNCTION__, '1.6' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_admin_bar_updates_menu' ) && ! function_exists( 'bp_admin_bar_updates_menu' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_bar_updates_menu()}.
	 *
	 * @deprecated Use bboss_admin_bar_updates_menu() instead.
	 */
	function bp_admin_bar_updates_menu() {
		return bboss_admin_bar_updates_menu();
	}
}
// @bbwt-com-end compat-layer


/**
 * bboss_members_admin_bar_my_account_logout()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_members_admin_bar_my_account_logout() {
	_deprecated_function( __FUNCTION__, '1.6' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_members_admin_bar_my_account_logout' ) && ! function_exists( 'bp_members_admin_bar_my_account_logout' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_members_admin_bar_my_account_logout()}.
	 *
	 * @deprecated Use bboss_members_admin_bar_my_account_logout() instead.
	 */
	function bp_members_admin_bar_my_account_logout() {
		return bboss_members_admin_bar_my_account_logout();
	}
}
// @bbwt-com-end compat-layer


/**
 * bp_core_is_user_deleted( $user_id = 0 )
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_core_is_user_deleted( $user_id = 0 ) {
	_deprecated_function( __FUNCTION__, '1.6' );
	bboss_is_user_deleted( $user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_is_user_deleted' ) && ! function_exists( 'bp_core_is_user_deleted' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_is_user_deleted()}.
	 *
	 * @deprecated Use bboss_core_is_user_deleted() instead.
	 */
	function bp_core_is_user_deleted( $user_id = 0 ) {
		return bboss_core_is_user_deleted( $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * bp_core_is_user_spammer( $user_id = 0 )
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_core_is_user_spammer( $user_id = 0 ) {
	_deprecated_function( __FUNCTION__, '1.6' );
	bboss_is_user_spammer( $user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_is_user_spammer' ) && ! function_exists( 'bp_core_is_user_spammer' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_is_user_spammer()}.
	 *
	 * @deprecated Use bboss_core_is_user_spammer() instead.
	 */
	function bp_core_is_user_spammer( $user_id = 0 ) {
		return bboss_core_is_user_spammer( $user_id );
	}
}
// @bbwt-com-end compat-layer



/**
 * Blogs functions
 */

/**
 * bp_blogs_manage_comment( $comment_id, $comment_status )
 *
 * @deprecated BuddyPress 1.6.0
 * @deprecated No longer used; see bboss_activity_transition_post_type_comment_status()
 */
function bboss_blogs_manage_comment( $comment_id, $comment_status ) {
	_deprecated_function( __FUNCTION__, '1.6', 'No longer used' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_blogs_manage_comment' ) && ! function_exists( 'bp_blogs_manage_comment' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_blogs_manage_comment()}.
	 *
	 * @deprecated Use bboss_blogs_manage_comment() instead.
	 */
	function bp_blogs_manage_comment( $comment_id, $comment_status ) {
		return bboss_blogs_manage_comment( $comment_id, $comment_status );
	}
}
// @bbwt-com-end compat-layer


/**
 * Core functions
 */

/**
 * bboss_core_add_admin_menu()
 *
 * @deprecated BuddyPress 1.6.0
 * @deprecated No longer used; see BBoss_Admin::admin_menus()
 */
function bboss_core_add_admin_menu() {
	_deprecated_function( __FUNCTION__, '1.6', 'No longer used' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_add_admin_menu' ) && ! function_exists( 'bp_core_add_admin_menu' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_add_admin_menu()}.
	 *
	 * @deprecated Use bboss_core_add_admin_menu() instead.
	 */
	function bp_core_add_admin_menu() {
		return bboss_core_add_admin_menu();
	}
}
// @bbwt-com-end compat-layer


/**
 * bboss_core_add_ajax_hook()
 *
 * @deprecated BuddyPress 1.6.0
 * @deprecated No longer used. We do ajax properly now.
 */
function bboss_core_add_ajax_hook() {
	_deprecated_function( __FUNCTION__, '1.6', 'No longer used' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_add_ajax_hook' ) && ! function_exists( 'bp_core_add_ajax_hook' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_add_ajax_hook()}.
	 *
	 * @deprecated Use bboss_core_add_ajax_hook() instead.
	 */
	function bp_core_add_ajax_hook() {
		return bboss_core_add_ajax_hook();
	}
}
// @bbwt-com-end compat-layer


/**
 * Connections functions
 */

/**
 * Displays Connections header tabs
 *
 * @deprecated BuddyPress 1.6.0
 * @deprecated No longer used
 */
function bboss_friends_header_tabs() {
	_deprecated_function( __FUNCTION__, '1.6', 'Since BuddyPress 1.2, BP has not supported ordering of friend lists by URL parameters.' );
	?>

	<li
	<?php
	if ( ! bboss_action_variable( 0 ) || bboss_is_action_variable( 'recently-active', 0 ) ) :
		?>
		 class="current"<?php endif; ?>><a href="<?php echo esc_url( trailingslashit( bboss_displayed_user_domain() . bboss_get_friends_slug() . '/my-friends/recently-active' ) ); ?>"><?php esc_html_e( 'Recently Active', 'buddyboss' ); ?></a></li>
	<li
	<?php
	if ( bboss_is_action_variable( 'newest', 0 ) ) :
		?>
		 class="current"<?php endif; ?>><a href="<?php echo esc_url( trailingslashit( bboss_displayed_user_domain() . bboss_get_friends_slug() . '/my-friends/newest' ) ); ?>"><?php esc_html_e( 'Newest', 'buddyboss' ); ?></a></li>
	<li
	<?php
	if ( bboss_is_action_variable( 'alphabetically', 0 ) ) :
		?>
		 class="current"<?php endif; ?>><a href="<?php echo esc_url( trailingslashit( bboss_displayed_user_domain() . bboss_get_friends_slug() . '/my-friends/alphabetically' ) ); ?>"><?php esc_html_e( 'Alphabetically', 'buddyboss' ); ?></a></li>

	<?php
	do_action( 'friends_header_tabs' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_friends_header_tabs' ) && ! function_exists( 'bp_friends_header_tabs' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_friends_header_tabs()}.
	 *
	 * @deprecated Use bboss_friends_header_tabs() instead.
	 */
	function bp_friends_header_tabs() {
		return bboss_friends_header_tabs();
	}
}
// @bbwt-com-end compat-layer


/**
 * Filters the title for the Connections component
 *
 * @deprecated BuddyPress 1.6.0
 * @deprecated No longer used
 */
function bboss_friends_filter_title() {
	_deprecated_function( __FUNCTION__, '1.6', 'Since BuddyPress 1.2, BP has not supported ordering of friend lists by URL parameters.' );

	$current_filter = bboss_action_variable( 0 );

	switch ( $current_filter ) {
		case 'recently-active':
		default:
			esc_html_e( 'Recently Active', 'buddyboss' );
			break;
		case 'newest':
			esc_html_e( 'Newest', 'buddyboss' );
			break;
		case 'alphabetically':
			esc_html_e( 'Alphabetically', 'buddyboss' );
			break;
	}
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_friends_filter_title' ) && ! function_exists( 'bp_friends_filter_title' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_friends_filter_title()}.
	 *
	 * @deprecated Use bboss_friends_filter_title() instead.
	 */
	function bp_friends_filter_title() {
		return bboss_friends_filter_title();
	}
}
// @bbwt-com-end compat-layer



/** Groups functions **********************************************************/

/**
 * groups_check_group_exists( $group_slug )
 *
 * @deprecated BuddyPress 1.6.0
 * @deprecated Renamed to bboss_groups_get_id() for greater consistency
 */
function bboss_groups_check_group_exists( $group_slug ) {
	_deprecated_function( __FUNCTION__, '1.6', 'groups_get_id()' );
	return bboss_groups_get_id( $group_slug );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_groups_check_group_exists' ) && ! function_exists( 'groups_check_group_exists' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_check_group_exists()}.
	 *
	 * @deprecated Use bboss_groups_check_group_exists() instead.
	 */
	function groups_check_group_exists( $group_slug ) {
		return bboss_groups_check_group_exists( $group_slug );
	}
}
// @bbwt-com-end compat-layer


/** Admin functions ***********************************************************/

/**
 * Loads admin panel styles and scripts.
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_core_add_admin_menu_styles() {
	_deprecated_function( __FUNCTION__, '1.6' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_add_admin_menu_styles' ) && ! function_exists( 'bp_core_add_admin_menu_styles' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_add_admin_menu_styles()}.
	 *
	 * @deprecated Use bboss_core_add_admin_menu_styles() instead.
	 */
	function bp_core_add_admin_menu_styles() {
		return bboss_core_add_admin_menu_styles();
	}
}
// @bbwt-com-end compat-layer


/** Activity functions ********************************************************/

/**
 * bboss_updates_register_activity_actions()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_updates_register_activity_actions() {
	_deprecated_function( __FUNCTION__, '1.6' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_updates_register_activity_actions' ) && ! function_exists( 'updates_register_activity_actions' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_updates_register_activity_actions()}.
	 *
	 * @deprecated Use bboss_updates_register_activity_actions() instead.
	 */
	function updates_register_activity_actions() {
		return bboss_updates_register_activity_actions();
	}
}
// @bbwt-com-end compat-layer


/**
 * Sets the "From" address in emails sent
 *
 * @deprecated BuddyPress 1.6.0
 * @return string email address
 */
function bboss_core_email_from_address_filter() {
	_deprecated_function( __FUNCTION__, '1.6' );

	$domain = (array) explode( '/', site_url() );
	return apply_filters( 'bp_core_email_from_address_filter', 'noreply@' . $domain[2] );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_email_from_address_filter' ) && ! function_exists( 'bp_core_email_from_address_filter' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_email_from_address_filter()}.
	 *
	 * @deprecated Use bboss_core_email_from_address_filter() instead.
	 */
	function bp_core_email_from_address_filter() {
		return bboss_core_email_from_address_filter();
	}
}
// @bbwt-com-end compat-layer

