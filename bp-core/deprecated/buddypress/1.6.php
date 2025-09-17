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
 * bp_admin_bar_remove_wp_menus()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bp_admin_bar_remove_wp_menus() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

/**
 * bp_admin_bar_root_site()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bp_admin_bar_root_site() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

/**
 * bp_admin_bar_my_sites_menu()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bp_admin_bar_my_sites_menu() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

/**
 * bp_admin_bar_comments_menu( $wp_admin_bar = '' )
 *
 * @deprecated BuddyPress 1.6.0
 */
function bp_admin_bar_comments_menu( $wp_admin_bar = '' ) {
	_deprecated_function( __FUNCTION__, '1.6' );
}

/**
 * bp_admin_bar_appearance_menu()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bp_admin_bar_appearance_menu() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

/**
 * bp_admin_bar_updates_menu()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bp_admin_bar_updates_menu() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

/**
 * bp_members_admin_bar_my_account_logout()
 *
 * @deprecated BuddyPress 1.6.0
 */
function bp_members_admin_bar_my_account_logout() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

/**
 * bp_core_is_user_deleted( $user_id = 0 )
 *
 * @deprecated BuddyPress 1.6.0
 */
function bp_core_is_user_deleted( $user_id = 0 ) {
	_deprecated_function( __FUNCTION__, '1.6' );
	bp_is_user_deleted( $user_id );
}

/**
 * bp_core_is_user_spammer( $user_id = 0 )
 *
 * @deprecated BuddyPress 1.6.0
 */
function bp_core_is_user_spammer( $user_id = 0 ) {
	_deprecated_function( __FUNCTION__, '1.6' );
	bp_is_user_spammer( $user_id );
}


/**
 * Blogs functions
 */

/**
 * bp_blogs_manage_comment( $comment_id, $comment_status )
 *
 * @deprecated BuddyPress 1.6.0
 * @deprecated No longer used; see bp_activity_transition_post_type_comment_status()
 */
function bp_blogs_manage_comment( $comment_id, $comment_status ) {
	_deprecated_function( __FUNCTION__, '1.6', 'No longer used' );
}

/**
 * Core functions
 */

/**
 * bp_core_add_admin_menu()
 *
 * @deprecated BuddyPress 1.6.0
 * @deprecated No longer used; see BP_Admin::admin_menus()
 */
function bp_core_add_admin_menu() {
	_deprecated_function( __FUNCTION__, '1.6', 'No longer used' );
}

/**
 * bp_core_add_ajax_hook()
 *
 * @deprecated BuddyPress 1.6.0
 * @deprecated No longer used. We do ajax properly now.
 */
function bp_core_add_ajax_hook() {
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
function bp_friends_header_tabs() {
	_deprecated_function( __FUNCTION__, '1.6', 'Since BuddyPress 1.2, BP has not supported ordering of friend lists by URL parameters.' );
	?>

	<li
	<?php
	if ( ! bp_action_variable( 0 ) || bp_is_action_variable( 'recently-active', 0 ) ) :
		?>
		 class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_friends_slug() . '/my-friends/recently-active' ); ?>"><?php _e( 'Recently Active', 'buddyboss' ); ?></a></li>
	<li
	<?php
	if ( bp_is_action_variable( 'newest', 0 ) ) :
		?>
		 class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_friends_slug() . '/my-friends/newest' ); ?>"><?php _e( 'Newest', 'buddyboss' ); ?></a></li>
	<li
	<?php
	if ( bp_is_action_variable( 'alphabetically', 0 ) ) :
		?>
		 class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_friends_slug() . '/my-friends/alphabetically' ); ?>"><?php _e( 'Alphabetically', 'buddyboss' ); ?></a></li>

	<?php
	do_action( 'friends_header_tabs' );
}

/**
 * Filters the title for the Connections component
 *
 * @deprecated BuddyPress 1.6.0
 * @deprecated No longer used
 */
function bp_friends_filter_title() {
	_deprecated_function( __FUNCTION__, '1.6', 'Since BuddyPress 1.2, BP has not supported ordering of friend lists by URL parameters.' );

	$current_filter = bp_action_variable( 0 );

	switch ( $current_filter ) {
		case 'recently-active':
		default:
			_e( 'Recently Active', 'buddyboss' );
			break;
		case 'newest':
			_e( 'Newest', 'buddyboss' );
			break;
		case 'alphabetically':
			_e( 'Alphabetically', 'buddyboss' );
			break;
	}
}


/** Groups functions **********************************************************/

/**
 * groups_check_group_exists( $group_slug )
 *
 * @deprecated BuddyPress 1.6.0
 * @deprecated Renamed to groups_get_id() for greater consistency
 */
function groups_check_group_exists( $group_slug ) {
	_deprecated_function( __FUNCTION__, '1.6', 'groups_get_id()' );
	return groups_get_id( $group_slug );
}

/** Admin functions ***********************************************************/

/**
 * Loads admin panel styles and scripts.
 *
 * @deprecated BuddyPress 1.6.0
 */
function bp_core_add_admin_menu_styles() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

/** Activity functions ********************************************************/

/**
 * updates_register_activity_actions()
 *
 * @deprecated BuddyPress 1.6.0
 */
function updates_register_activity_actions() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

/**
 * Sets the "From" address in emails sent
 *
 * @deprecated BuddyPress 1.6.0
 * @return string email address
 */
function bp_core_email_from_address_filter() {
	_deprecated_function( __FUNCTION__, '1.6' );

	$domain = (array) explode( '/', site_url() );
	return apply_filters( 'bp_core_email_from_address_filter', 'noreply@' . $domain[2] );
}
