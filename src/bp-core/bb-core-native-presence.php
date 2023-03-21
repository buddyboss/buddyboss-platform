<?php
/**
 * Native PHP to render the user presence.
 *
 * @package BuddyBoss
 *
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

// If user not logged in then return.
if ( ! is_user_logged_in() ) {
	return;
}

// Include BB_Presence class.
require_once __DIR__ . '/classes/class-bb-presence.php';

if ( isset( $_POST['ids'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

	BB_Presence::bb_update_last_activity( get_current_user_id() );

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
