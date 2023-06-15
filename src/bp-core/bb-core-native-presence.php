<?php
/**
 * Native PHP to render the user presence.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 2.3.1
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

// Loaded files which is required.
require ABSPATH . WPINC . '/class-wp-textdomain-registry.php';
global $wp_textdomain_registry;
if ( ! $wp_textdomain_registry instanceof WP_Textdomain_Registry ) {
	$wp_textdomain_registry = new WP_Textdomain_Registry();
}

require ABSPATH . WPINC . '/class-wp-user-meta-session-tokens.php';
require ABSPATH . WPINC . '/class-wp-http-response.php';
require ABSPATH . WPINC . '/theme.php';
require ABSPATH . WPINC . '/l10n.php';

// Load rest api to validate the attributes.
require ABSPATH . WPINC . '/rest-api/class-wp-rest-request.php';
require ABSPATH . WPINC . '/rest-api/class-wp-rest-server.php';
require ABSPATH . WPINC . '/rest-api/class-wp-rest-response.php';
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

// File from the build version.
$buddyboss_lang = WP_PLUGIN_DIR . '/buddyboss-platform/languages/';
if ( ! is_dir( $buddyboss_lang ) ) {
	// File from the development version.
	$buddyboss_lang = WP_PLUGIN_DIR . '/buddyboss-platform/src/languages/';
}
if ( ! empty( $buddyboss_lang ) && function_exists( 'load_plugin_textdomain' ) ) {
	load_plugin_textdomain( 'buddyboss', false, $buddyboss_lang );
}

BB_Presence::instance()->bb_presence_mu_loader();

$loggedin_user_id = ! empty( get_current_user_id() ) ? get_current_user_id() : BB_Presence::instance()->bb_get_loggedin_user_id();

// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
$users = ( isset( $_POST['ids'] ) ? $_POST['ids'] : array() );

BB_Presence::instance()->bb_endpoint_render( $loggedin_user_id, $users );

exit;
