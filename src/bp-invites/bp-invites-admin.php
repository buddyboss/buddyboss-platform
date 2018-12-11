<?php
/**
 * BuddyBoss Invites component admin screen.
 *
 * Props to WordPress core for the Comments admin screen, and its contextual
 * help text, on which this implementation is heavily based.
 *
 * @package BuddyBoss
 * @subpackage Invites
 * @since BuddyBoss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Include WP's list table class.
if ( !class_exists( 'WP_List_Table' ) ) require( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );


// Hook for register the invite admin action and filters.
add_action( 'bp_loaded', 'bp_register_invite_type_sections_filters_actions' );

/**
 * Function for register the invite admin action and filters.
 *
 * @since BuddyBoss 3.1.1
 *
 */
function bp_register_invite_type_sections_filters_actions() {

	//add column
	add_filter( 'manage_' . bp_get_invite_post_type() . '_posts_columns', 'bp_invite_add_column' );

	// action for adding a sortable column name.
	add_action( 'manage_' . bp_get_invite_post_type() . '_posts_custom_column', 'bp_invite_show_data', 10, 2 );

	//sortable columns
	add_filter( 'manage_edit-' . bp_get_invite_post_type() . '_sortable_columns', 'bp_invite_add_sortable_columns' );

	//hide quick edit link on the custom post type list screen
	add_filter( 'post_row_actions', 'bp_invite_hide_quick_edit', 10, 2 );

}

/**
 * Add new columns to the post type list screen.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param type $columns
 * @return type
 */
function bp_invite_add_column( $columns ) {

	$columns['inviter'] = __( 'Inviter', 'buddyboss' );
	$columns['invitee_name'] = __( 'Invitee Name', 'buddyboss' );
	$columns['invitee_email'] = __( 'Invitee Email', 'buddyboss' );
	$columns['date_invited'] = __( 'Date Invited', 'buddyboss' );
	$columns['status'] = __( 'Status', 'buddyboss' );

	unset( $columns['date'] );
	unset( $columns['title'] );

	return $columns;
}

/**
 * display data of columns.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param $column
 * @param $post_id
 */
function bp_invite_show_data( $column, $post_id  ) {

	switch( $column ) {

		case 'inviter':
			$author_id = get_post_field ('post_author', $post_id );
			$inviter_link = bp_core_get_user_domain( $author_id );
			$inviter_name = bp_core_get_user_displayname( $author_id );
			printf(
				__( '<a href="%s">%s</a>', 'buddyboss' ),
				esc_url( $inviter_link ), $inviter_name
			);

			break;

		case 'invitee_name':

			echo __( get_post_meta( $post_id, '_bp_invitee_name', true ), 'buddyboss' );

			break;

		case 'invitee_email':

			echo __( get_post_meta( $post_id, '_bp_invitee_email', true ), 'buddyboss' );

			break;

		case 'date_invited':

			$date = get_the_date( '',$post_id );
			echo __( $date, 'buddyboss' );

			break;

		case 'status':
			$title = ( '1' === get_post_meta( $post_id, '_bp_invitee_status', true ) ) ? __( 'Registered', 'buddyboss' ) : __( 'Revoke Invite', 'buddyboss' );
			printf(
				__( '<a href="javascript:void(0);">%s</a>', 'buddyboss' ),
				 $title
			);

			break;

	}

}

/**
 * Function for setting up a column on admin view on invite post type.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param $columns
 *
 * @return array
 */
function bp_invite_add_sortable_columns( $columns ) {

	$columns['inviter']       = 'inviter';
	$columns['invitee_name']  = 'invitee_name';
	$columns['invitee_email'] = 'invitee_email';
	$columns['date_invited']  = 'date_invited';
	$columns['status']        = 'status';

	return $columns;
}

/**
 * Function adding a filter to invite sort items.
 *
 * @since BuddyBoss 3.1.1
 *
 */
function bp_invite_add_request_filter() {

	add_filter( 'request', 'bp_invite_sort_items' );

}

/**
 * Sort list of invite post types.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param type $qv
 * @return string
 */
function bp_invite_sort_items( $qv ) {

	if( ! isset( $qv['post_type'] ) || $qv['post_type'] != bp_get_invite_post_type() )
		return $qv;

	if( ! isset( $qv['orderby'] ) )
		return $qv;

	switch( $qv['orderby'] ) {

		case 'inviter':

			$qv['meta_key'] = '_bp_invites_inviter_name';
			$qv['orderby'] = 'meta_value';

			break;

		case 'invitee_name':

			$qv['meta_key'] = '_bp_invites_invitee_name';
			$qv['orderby'] = 'meta_value';

			break;

		case 'invitee_email':

			$qv['meta_key'] = '_bp_invites_invitee_email';
			$qv['orderby'] = 'meta_value';

			break;

		case 'date_invited':

			$qv['meta_key'] = '_bp_invites_date_invited';
			$qv['orderby'] = 'meta_value_num';

			break;

		case 'status':

			$qv['meta_key'] = '_bp_invites_status';
			$qv['orderby'] = 'meta_value_num';

			break;

	}

	return $qv;
}

/**
 * Hide quick edit link.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param type $actions
 * @param type $post
 * @return type
 */
function bp_invite_hide_quick_edit( $actions, $post ) {

	if ( empty( $post ) ) {
		global $post;
	}

	if ( bp_get_invite_post_type() == $post->post_type )
		unset( $actions['inline hide-if-no-js'] );

	return $actions;
}
