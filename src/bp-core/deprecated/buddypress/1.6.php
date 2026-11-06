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

/**
 * bboss_admin_bar_root_site()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_admin_bar_root_site() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

/**
 * bboss_admin_bar_my_sites_menu()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_admin_bar_my_sites_menu() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

/**
 * bp_admin_bar_comments_menu( $wp_admin_bar = '' )
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_admin_bar_comments_menu( $wp_admin_bar = '' ) {
	_deprecated_function( __FUNCTION__, '1.6' );
}

/**
 * bboss_admin_bar_appearance_menu()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_admin_bar_appearance_menu() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

/**
 * bboss_admin_bar_updates_menu()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_admin_bar_updates_menu() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

/**
 * bboss_members_admin_bar_my_account_logout()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_members_admin_bar_my_account_logout() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

/**
 * bp_core_is_user_deleted( $user_id = 0 )
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_core_is_user_deleted( $user_id = 0 ) {
	_deprecated_function( __FUNCTION__, '1.6' );
	bboss_is_user_deleted( $user_id );
}

/**
 * bp_core_is_user_spammer( $user_id = 0 )
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_core_is_user_spammer( $user_id = 0 ) {
	_deprecated_function( __FUNCTION__, '1.6' );
	bboss_is_user_spammer( $user_id );
}


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

/**
 * bboss_core_add_ajax_hook()
 *
 * @deprecated BuddyPress 1.6.0
 * @deprecated No longer used. We do ajax properly now.
 */
function bboss_core_add_ajax_hook() {
	_deprecated_function( __FUNCTION__, '1.6', 'No longer used' );
}

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

/** Admin functions ***********************************************************/

/**
 * Loads admin panel styles and scripts.
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_core_add_admin_menu_styles() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

/** Activity functions ********************************************************/

/**
 * bboss_updates_register_activity_actions()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bboss_updates_register_activity_actions() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

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
