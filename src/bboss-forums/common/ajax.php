<?php

/**
 * Forums Common AJAX Functions
 *
 * Common AJAX functions are ones that are used to setup and/or use during
 * Forums specific, theme-side  AJAX requests.
 *
 * @package BuddyBoss\Ajax
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Output the URL to use for theme-side Forums AJAX requests
 *
 * @since bbPress (r4543)
 *
 * @uses bboss_bbp_get_ajax_url() To get the URL to use for AJAX requests
 */
function bboss_bbp_ajax_url() {
	echo esc_url( bboss_bbp_get_ajax_url() );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_ajax_url' ) && ! function_exists( 'bbp_ajax_url' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_ajax_url()}.
	 *
	 * @deprecated Use bboss_bbp_ajax_url() instead.
	 */
	function bbp_ajax_url() {
		return bboss_bbp_ajax_url();
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Return the URL to use for theme-side Forums AJAX requests
	 *
	 * @since bbPress (r4543)
	 *
	 * @global WP $wp
	 * @return string
	 */
function bboss_bbp_get_ajax_url() {
	global $wp;

	$ssl      = bboss_bbp_get_url_scheme();
	$url      = trailingslashit( $wp->request );
	$base_url = home_url( $url, $ssl );
	$ajaxurl  = add_query_arg( array( 'bbp-ajax' => 'true' ), $base_url );

	return apply_filters( 'bbp_get_ajax_url', $ajaxurl );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_get_ajax_url' ) && ! function_exists( 'bbp_get_ajax_url' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_get_ajax_url()}.
	 *
	 * @deprecated Use bboss_bbp_get_ajax_url() instead.
	 */
	function bbp_get_ajax_url() {
		return bboss_bbp_get_ajax_url();
	}
}
// @bbwt-com-end compat-layer


/**
 * Is this a Forums AJAX request?
 *
 * @since bbPress (r4543)
 *
 * @return bool Looking for bbp-ajax
 */
function bboss_bbp_is_ajax() {
	return (bool) ( ( isset( $_GET['bbp-ajax'] ) || isset( $_POST['bbp-ajax'] ) ) && ! empty( $_REQUEST['action'] ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_is_ajax' ) && ! function_exists( 'bbp_is_ajax' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_is_ajax()}.
	 *
	 * @deprecated Use bboss_bbp_is_ajax() instead.
	 */
	function bbp_is_ajax() {
		return bboss_bbp_is_ajax();
	}
}
// @bbwt-com-end compat-layer


/**
 * Hooked to the 'bbp_template_redirect' action, this is Forums' custom
 * theme-side ajax handler.
 *
 * @since bbPress (r4543)
 *
 * @return If not a Forums ajax request
 */
function bboss_bbp_do_ajax() {

	// Bail if not an ajax request
	if ( ! bboss_bbp_is_ajax() ) {
		return;
	}

	// Set WordPress core ajax constant
	if ( ! defined( 'DOING_AJAX' ) ) {
		define( 'DOING_AJAX', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress core constant, set for this theme-side AJAX request.
	}

	// Set the header content type
	@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );

	// Disable content sniffing in browsers that support it
	send_nosniff_header();

	// Perform custom Forums ajax
	do_action( 'bbp_ajax_' . $_REQUEST['action'] );

	// All done
	die( '0' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_do_ajax' ) && ! function_exists( 'bbp_do_ajax' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_do_ajax()}.
	 *
	 * @deprecated Use bboss_bbp_do_ajax() instead.
	 */
	function bbp_do_ajax() {
		return bboss_bbp_do_ajax();
	}
}
// @bbwt-com-end compat-layer


/**
 * Helper method to return JSON response for the ajax calls
 *
 * @since bbPress (r4542)
 *
 * @param bool   $success
 * @param string $content
 * @param array  $extras
 */
function bboss_bbp_ajax_response( $success = false, $content = '', $status = -1, $extras = array() ) {

	// Set status to 200 if setting response as successful.
	global $redirect_url;
	if ( ( true === $success ) && ( -1 === $status ) ) {
		$status = 200;
	}

	// Setup the response array.
	$response = array(
		'success' => $success,
		'status'  => $status,
		'content' => $content,
	);

	// Merge extra response parameters in.
	if ( ! empty( $extras ) && is_array( $extras ) ) {
		$response = array_merge( $response, $extras );
	}

	if ( ! empty( $redirect_url ) ) {
		$response['redirect_url'] = $redirect_url;
	}

	// Send back the JSON.
	wp_send_json( $response, $status );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_ajax_response' ) && ! function_exists( 'bbp_ajax_response' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_ajax_response()}.
	 *
	 * @deprecated Use bboss_bbp_ajax_response() instead.
	 */
	function bbp_ajax_response( $success = false, $content = '', $status = -1, $extras = array() ) {
		return bboss_bbp_ajax_response( $success, $content, $status, $extras );
	}
}
// @bbwt-com-end compat-layer

