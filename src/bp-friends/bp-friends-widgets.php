<?php
/**
 * BuddyBoss Connections Widgets.
 *
 * @package BuddyBoss\Connections
 * @since BuddyPress 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register the friends widget.
 *
 * @since BuddyPress 1.9.0
 */
function bp_friends_register_widgets() {
	if ( ! bp_is_active( 'friends' ) ) {
		return;
	}

	// The Connections widget works only when looking at a displayed user,
	// and the concept of "displayed user" doesn't exist on non-root blogs,
	// so we don't register the widget there.
	if ( ! bp_is_root_blog() ) {
		return;
	}

	add_action(
		'widgets_init',
		function() {
			register_widget( 'BP_Core_Friends_Widget' );
		}
	);
}
add_action( 'bp_register_widgets', 'bp_friends_register_widgets' );

/** Widget AJAX ***************************************************************/

/**
 * Process AJAX pagination or filtering for the Connections widget.
 *
 * @since BuddyPress 1.9.0
 */
function bp_core_ajax_widget_friends() {

	check_ajax_referer( 'bp_core_widget_friends' );
	global $members_template;

	switch ( $_POST['filter'] ) {
		case 'newest-friends':
			$type = 'newest';
			break;

		case 'recently-active-friends':
			$type = 'active';
			break;

		case 'popular-friends':
			$type = 'popular';
			break;
	}
	
	$user_id     = bp_displayed_user_id();
	
	if ( ! $user_id ) {
		
		// If member widget is putted on other pages then will not get the bp_displayed_user_id so set the bp_loggedin_user_id to bp_displayed_user_id.
		$user_id     = bp_loggedin_user_id();
		
	}
	
	// If $user_id still blank then return.
	if ( ! $user_id ) {
		return;
	}
	$current_page 		= isset( $_POST['page'] ) ? ceil( (int) $_POST['page'] ) : 1;
	$members_args = array(
		'user_id'         => absint( $user_id ),
		'type'            => $type,
		'max'             => absint( $_POST['max-friends'] ),
		'page'            => absint( $current_page ),
		'populate_extras' => 1,
	);
	$contents = '';
	if ( bp_has_members( $members_args ) ) : 

		while ( bp_members() ) :
			bp_the_member();
			
			$contents .= '<li class="vcard">';
			$contents .= '<div class="item-avatar">';
			$contents .= '<a href="'. bp_get_member_permalink() .'">'. bp_get_member_avatar() .'</a>';
			$contents .= '</div>';

			$contents .= '<div class="item">';
			$contents .= '<div class="item-title fn"><a href="'. bp_get_member_permalink() .'">'. bp_get_member_name() .'</a></div>';
			if ( 'active' == $type ) :
				$contents .= '<div class="item-meta"><span class="activity" data-livestamp="'. bp_core_get_iso8601_date( bp_get_member_last_active( array( 'relative' => false ) ) ) .'">'. bp_get_member_last_active() .'</span></div>';
			elseif ( 'newest' == $type ) :
				$contents .= '<div class="item-meta"><span class="activity" data-livestamp="'. bp_core_get_iso8601_date( bp_get_member_registered( array( 'relative' => false ) ) ) .'">'. bp_get_member_registered() .'</span></div>';
			elseif ( bp_is_active( 'friends' ) ) :
				$contents .= '<div class="item-meta"><span class="activity">'. bp_get_member_total_friend_count() .'</span></div>';
			endif;
			$contents .= '</div>';
			$contents .= '</li>';
		endwhile; 
		$total_page_count 	= ceil( (int) $members_template->total_member_count / (int) $members_template->pag_num );
		$total_member_count = (int) $members_template->total_member_count;
		$next_page 			= ( $total_page_count !== $current_page) ?  $current_page + 1 : 0;

		$json_data['status']    			= true;
		$json_data['contents'] 				= $contents;
		$json_data['total_member_count']    = $total_member_count;
		$json_data['total_page_count']    	= $total_page_count;
		$json_data['current_page']    		= $current_page;
		$json_data['next_page']    			= $next_page;
	else : 
		$json_data['status']    = false;
		$contents .= '<li>'. _e( 'There were no members found, please try another filter.', 'buddyboss' ) .'</li>';
	endif;
	$json_data['contents'] 	= $contents;
	echo wp_send_json( $json_data );
	exit;
}
add_action( 'wp_ajax_widget_friends', 'bp_core_ajax_widget_friends' );
add_action( 'wp_ajax_nopriv_widget_friends', 'bp_core_ajax_widget_friends' );
