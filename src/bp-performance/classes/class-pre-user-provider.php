<?php
/**
 * BuddyBoss Performance Pre user provider.
 *
 * @package BuddyBoss\Performance\Pre_User_Provider
 */

namespace BuddyBoss\Performance;

/**
 * User Provider class.
 */
class Pre_User_Provider {

	/**
	 * Class instance.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Class instance.
	 *
	 * @return Pre_User_Provider
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
			self::$instance->load();
		}

		return self::$instance;
	}

	/**
	 * Load the class.
	 */
	public function load() {
		add_filter( 'rest_cache_pre_current_user_id', array( $this, 'cookie_support' ), 1 );
		add_filter( 'rest_cache_pre_current_user_id', array( $this, 'bbapp_auth' ), 2 );
	}

	/**
	 * Get the Pre User ID from BuddyBoss APP JWT Token.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int|void
	 */
	public function bbapp_auth( $user_id ) {

		$header = $this->get_all_headers();

		$jwt_token = false;
		if ( ! empty( $header ) ) {
			foreach ( $header as $k => $v ) {
				if ( strtolower( $k ) === 'accesstoken' ) {
					$jwt_token = $v;
					break;
				}
			}
		}

		if ( $jwt_token ) {

			$token = explode( '.', $jwt_token );
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$token = (array) json_decode( base64_decode( $token[1] ) );

			if ( isset( $token['data'] ) && isset( $token['data']->user ) && isset( $token['data']->user->id ) ) {
				$user_id = $token['data']->user->id;

				// Check if there is any switch user.
				$switch_data = get_user_meta( $user_id, '_bbapp_jwt_switch_user', true );
				$switch_data = ( ! is_array( $switch_data ) ) ? array() : $switch_data;
				$jti         = ( isset( $token['jti'] ) ) ? $token['jti'] : false;

				// if switch user is found for current access token pass it.
				if ( $jti && isset( $switch_data[ $jti ] ) && is_numeric( $switch_data[ $jti ] ) ) {
					return (int) $switch_data[ $jti ];
				}

				// End Switch user logic's.

				return $user_id;
			}
		}

	}

	/**
	 * Get Pre User ID from WordPress Cookie.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int
	 */
	public function cookie_support( $user_id ) {
		$scheme          = apply_filters( 'auth_redirect_scheme', '' );
		$cookie_elements = $this->wp_parse_auth_cookie( '', $scheme );

		if ( $cookie_elements && isset( $cookie_elements['username'] ) ) {
			global $wpdb;

			// @todo: any idea to avoid this query ?
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$get_user = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE user_login=%s", $cookie_elements['username'] ) );

			if ( $get_user ) {
				return $get_user->ID;
			}
		}
	}

	/**
	 * Copied from wp-includes/pluggable.php.
	 *
	 * @see wp-includes/pluggable.php
	 *
	 * @param string $cookie Cookie value.
	 * @param string $scheme Schema.
	 *
	 * @return array|bool
	 */
	public function wp_parse_auth_cookie( $cookie = '', $scheme = '' ) {
		if ( empty( $cookie ) ) {

			// @see wp_cookie_constants()..
			$siteurl = get_site_option( 'siteurl' );
			if ( $siteurl ) {
				$cookie_hash = md5( $siteurl );
			} else {
				$cookie_hash = '';
			}

			// @see wp_cookie_constants()..
			if ( is_ssl() ) {
				$cookie_name = 'wordpress_sec_' . $cookie_hash;
				$scheme      = 'secure_auth';
			} else {
				$cookie_name = 'wordpress_' . $cookie_hash;
				$scheme      = 'auth';
			}

			if ( empty( $_COOKIE[ $cookie_name ] ) ) {
				return false;
			}

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$cookie = $_COOKIE[ $cookie_name ];
		}

		$cookie_elements = explode( '|', $cookie );
		if ( count( $cookie_elements ) !== 4 ) {
			return false;
		}

		list( $username, $expiration, $token, $hmac ) = $cookie_elements;

		return compact( 'username', 'expiration', 'token', 'hmac', 'scheme' );
	}

	/**
	 * Get Headers.
	 *
	 * @return array|false|string
	 */
	public function get_all_headers() {

		if ( function_exists( 'getallheaders' ) ) {
			return getallheaders();
		}

		if ( ! is_array( $_SERVER ) ) {
			return array();
		}

		$headers = array();
		foreach ( $_SERVER as $name => $value ) {
			if ( substr( $name, 0, 5 ) === 'HTTP_' ) {
				$headers[ str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $name, 5 ) ) ) ) ) ] = $value;
			}
		}

		return $headers;
	}
}
