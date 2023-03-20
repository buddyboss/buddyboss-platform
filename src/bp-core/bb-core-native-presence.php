<?php
// specify that we need a minimum from WP
define( 'SHORTINIT', true );

// Loading the WordPress environment
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );

// Require files used for cookie-based user authentication
require ABSPATH . WPINC . '/pluggable.php';
require ABSPATH . WPINC . '/kses.php';
require ABSPATH . WPINC . '/user.php';
require ABSPATH . WPINC . '/capabilities.php';
require ABSPATH . WPINC . '/class-wp-role.php';
require ABSPATH . WPINC . '/class-wp-roles.php';
require ABSPATH . WPINC . '/class-wp-user.php';
require ABSPATH . WPINC . '/class-wp-session-tokens.php';
require ABSPATH . WPINC . '/class-wp-user-meta-session-tokens.php';
require ABSPATH . WPINC . '/l10n.php';
require ABSPATH . WPINC . '/rest-api.php';
/**
 * 'WP_PLUGIN_URL' and others are used by: wp_cookie_constants()
 */
wp_plugin_directory_constants();

/**
 * 'ADMIN_COOKIE_PATH' and others are used by: wp_set_auth_cookie()
 */
if ( is_multisite() ) {
	ms_cookie_constants();
}

/**
 * 'SECURE_AUTH_COOKIE' and others are used by: wp_parse_auth_cookie()
 */
wp_cookie_constants();

/**
 * Sets: 'FORCE_SSL_ADMIN' and 'FORCE_SSL_LOGIN'
 */
wp_ssl_constants();


// If user not logged in then return.
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
			'status' => BB_Presence::bb_get_user_presence( $user_id ),
		);
	}

	return wp_send_json( $presence_data );
}
