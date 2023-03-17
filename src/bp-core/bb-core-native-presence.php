<?php
// specify that we need a minimum from WP
//define( 'SHORTINIT', true );

// Loading the WordPress environment
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );

if ( ! is_user_logged_in() ) {
	return;
}


if ( isset( $_GET['direct_allow'] ) ) {
	$return = array(
		'direct_allow' => true,
	);
	wp_send_json_success( $return );
}

// Include BB_Presence class.
include_once __DIR__ . '/classes/class-bb-presence.php';

if ( isset( $_POST['ids'] ) ) {

	BB_Presence::bb_update_last_activity( get_current_user_id() );

	$users         = $_POST['ids'];
	$presence_data = array();
	foreach ( array_unique( $users ) as $user_id ) {
		$presence_data[] = array(
			'id'     => $user_id,
			'status' => BB_Presence::bb_is_online_user_mu_cache( $user_id ),
		);
	}

	return wp_send_json( $presence_data );
}
