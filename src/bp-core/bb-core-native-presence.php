<?php
/**
 * Native PHP to render the user presence.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

// Specify that we need a minimum from WP.
define( 'SHORTINIT', true );

$wp_load_path = explode( 'wp-content', __DIR__ );

// Loading the WordPress environment.
if ( ! empty( $wp_load_path ) && file_exists( current( $wp_load_path ) . 'wp-load.php' ) ) {
	require_once current( $wp_load_path ) . 'wp-load.php';

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
} elseif ( isset( $_SERVER['DOCUMENT_ROOT'] ) && file_exists( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' ) ) {

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
}

defined( 'ABSPATH' ) || exit;

// Require files used for cookie-based user authentication.
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

if ( isset( $_GET['direct_allow'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$return = array(
		'direct_allow' => true,
	);
	wp_send_json_success( $return );
}

// Include BB_Presence class.
require_once __DIR__ . '/classes/class-bb-presence.php';
add_action( 'set_current_user', array( BB_Presence::instance(), 'bb_is_set_current_user' ), 10 );
add_filter( 'rest_cache_pre_current_user_id', array( BB_Presence::instance(), 'bb_cookie_support' ), 10 );
add_filter( 'rest_cache_pre_current_user_id', array( BB_Presence::instance(), 'bb_jwt_auth_support' ), 10 );
//BB_Presence::instance()->bb_presence_mu_loader();
$loggedin_user_id = ! empty( get_current_user_id() ) ? get_current_user_id() : BB_Presence::instance()->bb_get_loggedin_user_id();

// If user not logged in then return.
if ( empty( $loggedin_user_id ) ) {
	return;
}

if ( isset( $_POST['ids'] ) && class_exists( 'BB_Presence' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

	BB_Presence::bb_update_last_activity( $loggedin_user_id );

	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$users         = $_POST['ids'];
	$presence_data = array();
	foreach ( array_unique( $users ) as $user_id ) {
		$presence_data[] = array(
			'id'     => $user_id,
			'status' => BB_Presence::bb_get_user_presence_mu_cache( $user_id ),
		);
	}

	return wp_send_json( $presence_data );
}
