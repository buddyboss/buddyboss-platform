<?php
/**
 * BuddyBoss Performance MU loader.
 *
 * A performance component, Allow to cache BuddyBoss Platform REST API.
 *
 * @package BuddyBoss\Performance\MULoader
 * @since BuddyBoss 1.5.7
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'bp_performance_loaded' ) ) {
	/**
	 * Load Performance instance.
	 */
	function bp_performance_loaded() {
		if ( ! class_exists( 'BuddyBoss\Performance\Performance' ) ) {
			require_once dirname( __FILE__ ) . '/classes/class-performance.php';
			\BuddyBoss\Performance\Performance::instance();
		}
	}

	add_action( 'muplugins_loaded', 'bp_performance_loaded', 20 );
}

/**
 * Function will remove REST APIs endpoint.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $data              Result to send to the client.
 *                                                                            Usually a WP_REST_Response or WP_Error.
 * @param WP_REST_Request                                  $current_endpoint  Current endpoint
 *
 * @return WP_REST_Response $data
 */
if ( ! function_exists( 'rest_post_dispatch_cache_callback' ) ) {
	function rest_post_dispatch_cache_callback( $data, $current_endpoint ) {
		if ( ! is_user_logged_in() ) {
			// Here we can not access bb core functions so that's why we use wp functions here.
			$enable_private_rest_apis = get_option( 'bb-enable-private-rest-apis' );
			$exclude_endpoints        = get_option( 'bb-enable-private-rest-apis-public-content' );
			// Multisite check.
			if ( is_multisite() ) {
				$plugins  = get_site_option( 'active_sitewide_plugins' );
				$basename = 'buddyboss-platform/bp-loader.php';
				// plugin is network-activated; use main site ID instead.
				if ( isset( $plugins[ $basename ] ) ) {
					$current_site             = get_current_site();
					$root_blog_id             = $current_site->blog_id;
					$exclude_endpoints        = get_blog_option( $root_blog_id, 'bb-enable-private-rest-apis-public-content' );
					$enable_private_rest_apis = get_blog_option( $root_blog_id, 'bb-enable-private-rest-apis' );
				}
			}
			if (
				( function_exists( 'bbapp_is_private_app_enabled' ) && true === bbapp_is_private_app_enabled() ) ||
				( ! function_exists( 'bbapp_is_private_app_enabled' ) && $enable_private_rest_apis )
			) {
				return bb_restricate_rest_api_mu_cache( $data, $current_endpoint, $exclude_endpoints );
			}
		}

		return $data;
	}

	add_filter( 'rest_post_dispatch_cache', 'rest_post_dispatch_cache_callback', 10, 2 );
}

/**
 * Function will remove all endpoints as well as exclude specific endpoints which added in admin side.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $data              Result to send to the client.
 *                                                                            Usually a WP_REST_Response or WP_Error.
 * @param WP_REST_Request                                  $current_endpoint  Current endpoint
 * @param string                                           $exclude_endpoints List of endpoints which need to excluded
 *
 * @return WP_REST_Response $data
 */
if ( ! function_exists( 'bb_restricate_rest_api_mu_cache' ) ) {
	function bb_restricate_rest_api_mu_cache( $data, $current_endpoint, $exclude_endpoints ) {
		$exclude_required_endpoints = apply_filters( 'bb_exclude_endpoints_from_restriction', array(), $current_endpoint );
		// Allow some endpoints which is mandatory for app.
		if ( in_array( $current_endpoint, $exclude_required_endpoints, true ) ) {
			return $data;
		}
		if ( ! bb_is_allowed_endpoint_mu_cache( $current_endpoint, $exclude_endpoints ) ) {
			$error_message = esc_html__( 'Only authenticated users can access the REST API.', 'buddyboss' );
			$error         = new WP_Error( 'bb_rest_authorization_required', $error_message, array( 'status' => rest_authorization_required_code() ) );
			$data          = rest_ensure_response( $error );
		}

		return $data;
	}
}

/**
 * Function will check current REST APIs endpoint is allow or not.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $current_endpoint  Current endpoint.
 * @param string $exclude_endpoints List of endpoints which need to excluded
 *
 * @return bool true Return true if allow endpoint otherwise return false.
 */
if ( ! function_exists( 'bb_is_allowed_endpoint_mu_cache' ) ) {
	function bb_is_allowed_endpoint_mu_cache( $current_endpoint, $exclude_endpoints ) {
		if ( '' !== $exclude_endpoints ) {
			$exclude_arr_endpoints = preg_split( "/\r\n|\n|\r/", $exclude_endpoints );
			if ( ! empty( $exclude_arr_endpoints ) && is_array( $exclude_arr_endpoints ) ) {
				foreach ( $exclude_arr_endpoints as $endpoints ) {
					// Here we get current_endpoint like this - /wp/v2/users
					// so we need to explode exclude endpoint with wp-json/.
					if ( strpos( $endpoints, 'wp-json/' ) !== false ) {
						$endpoints = str_replace( 'wp-json/', '', $endpoints );
					}
					$current_endpoint_allowed = preg_match( '@^' . $endpoints . '$@i', $current_endpoint, $matches );
					if ( $current_endpoint_allowed ) {
						return true;
					}
				}
			}
		}
	}
}
