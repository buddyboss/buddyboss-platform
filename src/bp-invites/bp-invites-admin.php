<?php
/**
 * BuddyBoss Groups component admin screen.
 *
 * Props to WordPress core for the Comments admin screen, and its contextual
 * help text, on which this implementation is heavily based.
 *
 * @package BuddyBoss
 * @subpackage Groups
 * @since BuddyPress 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Include WP's list table class.
if ( !class_exists( 'WP_List_Table' ) ) require( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );


// Hook for register the group type admin action and filters.
add_action( 'bp_loaded', 'bp_register_invite_type_sections_filters_actions' );

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

			echo '<code>'. get_post_meta( $post_id, '_bp_group_type_label_singular_name', true ).'</code>';
			break;

		case 'invitee_name':

			if( get_post_meta( $post_id, '_bp_group_type_enable_filter', true ) )
				echo __( 'Show', 'buddyboss' );
			else
				echo __( 'Hide', 'buddyboss' );

			break;

		case 'invitee_email':

			if( get_post_meta( $post_id, '_bp_group_type_enable_remove', true ) )
				echo __( 'Hide', 'buddyboss' );
			else
				echo __( 'Show', 'buddyboss' );

			break;

		case 'date_invited':

			$group_key = bp_get_group_type_key( $post_id );
			$group_type_url = admin_url().'admin.php?page=bp-groups&bp-group-type='.$group_key;
			printf(
				__( '<a href="%s">%s</a>', 'buddyboss' ),
				esc_url( $group_type_url ), bp_get_total_count_by_group_types( $group_key )
			);

			break;

		case 'status':

			$group_key = bp_get_group_type_key( $post_id );
			$group_type_url = admin_url().'admin.php?page=bp-groups&bp-group-type='.$group_key;
			printf(
				__( '<a href="%s">%s</a>', 'buddyboss' ),
				esc_url( $group_type_url ), bp_get_total_count_by_group_types( $group_key )
			);

			break;

	}

}

/**
 * Function for setting up a column on admin view on group type post type.
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
 * Function adding a filter to group type sort items.
 *
 * @since BuddyBoss 3.1.1
 *
 */
function bp_invite_add_request_filter() {

	add_filter( 'request', 'bp_invite_sort_items' );

}

/**
 * Sort list of group type post types.
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
			$qv['orderby'] = 'meta_value_num';

			break;

		case 'invitee_email':

			$qv['meta_key'] = '_bp_invites_invitee_email';
			$qv['orderby'] = 'meta_value_num';

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
