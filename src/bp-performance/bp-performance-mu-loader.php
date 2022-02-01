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
 * @since BuddyBoss 1.8.6
 *
 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $data              Result to send to the client.
 *                                                                            Usually a WP_REST_Response or WP_Error.
 * @param WP_REST_Request                                  $current_endpoint  Current endpoint
 *
 * @return WP_REST_Response $data
 */
if ( ! function_exists( 'rest_post_dispatch_cache_callback' ) ) {
	function rest_post_dispatch_cache_callback( $data, $user_id, $current_endpoint ) {
		if ( 0 === (int) $user_id ) {

			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}

			$bb_platform_active = is_plugin_active( 'buddyboss-platform/bp-loader.php' );
			$bb_app_active      = is_plugin_active( 'buddyboss-app/buddyboss-app.php' );

			$apps_settings            = array();
			$enable_private_rest_apis = false;
			$exclude_endpoints        = '';

			if ( $bb_app_active ) {
				$apps_settings = get_option( 'bbapp_settings', array() );
			}

			if ( $bb_platform_active ) {
				$enable_private_rest_apis = get_option( 'bb-enable-private-rest-apis', false );
				$exclude_endpoints        = get_option( 'bb-enable-private-rest-apis-public-content', '' );
			}

			if ( is_multisite() ) {
				$bb_platform_active_network = is_plugin_active_for_network( 'buddyboss-platform/bp-loader.php' );
				$bb_app_active_network      = is_plugin_active_for_network( 'buddyboss-app/buddyboss-app.php' );

				if ( $bb_app_active_network ) {
					$apps_settings = get_blog_option( get_current_network_id(), 'bbapp_settings', array() );
				}

				if ( $bb_platform_active_network ) {
					$enable_private_rest_apis = get_blog_option( get_current_network_id(), 'bb-enable-private-rest-apis', false );
					$exclude_endpoints        = get_blog_option( get_current_network_id(), 'bb-enable-private-rest-apis-public-content', '' );
				}
			}
			if (
				true === (bool) $enable_private_rest_apis && // BB private rest api is enabled.
				(
					(
						! empty( $apps_settings ) && // buddyboss-app is active.
						! empty( $apps_settings['private_app.enabled'] ) // private app is enabled.
					) ||
					empty( $apps_settings ) // buddyboss-app disabled.
				)
			) {
				return bb_restricate_rest_api_mu_cache( $data, $current_endpoint, $exclude_endpoints );
			}
		}

		return $data;
	}

	add_filter( 'rest_get_cache', 'rest_post_dispatch_cache_callback', 10, 3 );
}

/**
 * Function will remove all endpoints as well as exclude specific endpoints which added in admin side.
 *
 * @since BuddyBoss 1.8.6
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
		// Add mandatory endpoint here for app which you want to exclude from restriction.
		// ex: /buddyboss-app/auth/v1/jwt/token.
		$default_exclude_endpoint = array(
			'/buddyboss/v1/signup/form',
			'/buddyboss/v1/signup/(?P<id>[\w-]+)',
			'/buddyboss/v1/signup/activate/(?P<id>[\w-]+)',
			'/buddyboss/v1/settings',
			'/buddyboss/v1/signup',
		);
		$exclude_required_endpoints = apply_filters( 'bb_exclude_endpoints_from_restriction', $default_exclude_endpoint, $current_endpoint );
		// Allow some endpoints which is mandatory for app.
		if ( ! empty( $exclude_required_endpoints ) && in_array( $current_endpoint, $exclude_required_endpoints, true ) ) {
			return $data;
		}

		$get_site_url = get_site_url();
		if ( is_multisite() ) {
			$get_site_url = get_site_url( get_current_network_id() );
		}
		$current_endpoint = trailingslashit( $get_site_url ) . 'wp-json' . $current_endpoint;

		if ( ! bb_is_allowed_endpoint_mu_cache( $current_endpoint, $exclude_endpoints, $get_site_url ) ) {
			$data = false;
		}

		return $data;
	}
}

/**
 * Function will check current REST APIs endpoint is allow or not.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param string $current_endpoint  Current endpoint.
 * @param string $exclude_endpoints List of endpoints which need to excluded
 * @param string $get_site_url      Current site url
 *
 * @return bool true Return true if allow endpoint otherwise return false.
 */
if ( ! function_exists( 'bb_is_allowed_endpoint_mu_cache' ) ) {
	function bb_is_allowed_endpoint_mu_cache( $current_endpoint, $exclude_endpoints, $get_site_url ) {
		$exploded_endpoint = (array) explode( 'wp-json', $current_endpoint );
		if ( '' !== $exclude_endpoints ) {
			$exclude_arr_endpoints = preg_split( "/\r\n|\n|\r/", $exclude_endpoints );
			if ( ! empty( $exclude_arr_endpoints ) && is_array( $exclude_arr_endpoints ) ) {
				foreach ( $exclude_arr_endpoints as $endpoints ) {
					if ( ! empty( $endpoints ) ) {
						$endpoints = untrailingslashit( trim( $endpoints ) );
						if ( strpos( $current_endpoint, $endpoints ) !== false ) {
							return true;
						} else {
							if ( strpos( $endpoints, $get_site_url ) !== false ) {
								$endpoints = str_replace( trailingslashit( $get_site_url ), '', $endpoints );
							}
							if ( strpos( $endpoints, 'wp-json' ) !== false ) {
								$endpoints = str_replace( 'wp-json', '', $endpoints );
							}
							$endpoints                = str_replace( '//', '/', $endpoints );
							$endpoints                = str_replace( '///', '/', $endpoints );
							$endpoints                = '/' . ltrim( $endpoints, '/' );
							$current_endpoint_allowed = preg_match( '@' . $endpoints . '$@i', end( $exploded_endpoint ), $matches );
							if ( $current_endpoint_allowed ) {
								return true;
							}
						}
					}
				}
			}
		}
	}
}
