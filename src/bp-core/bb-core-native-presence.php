<?php
// specify that we need a minimum from WP
define( 'SHORTINIT', true );

// Loading the WordPress environment
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );

if ( isset( $_GET['direct_allow'] ) ) {
	$return = array(
		'direct_allow' => true,
	);
	wp_send_json_success( $return );
}
