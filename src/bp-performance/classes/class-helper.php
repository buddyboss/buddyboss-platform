<?php
/**
 * BuddyBoss Performance helper.
 *
 * @package BuddyBoss\Performance\Helper
 */

namespace BuddyBoss\Performance;

/**
 * Cache Helper class.
 */
class Helper {

	/**
	 * Class instance.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Class instance.
	 *
	 * @return Helper
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
			self::$instance->initialize(); // run the hooks.
		}

		return self::$instance;
	}

	/**
	 * Initialisation of class.
	 */
	public function initialize() {
	}

	/**
	 * Get the settings.
	 *
	 * @param null   $setting_key Key name of settings.
	 * @param string $group       setting groups.
	 * @param bool   $default_value default value.
	 *
	 * @return mixed|null
	 */
	public function get_app_settings( $setting_key = null, $group = 'default', $default_value = true ) {
		// Currently, we are supporting only BuddyBoss App Settings.
		$settings = \BuddyBoss\Performance\Settings::get_settings( $group );

		if ( ! empty( $setting_key ) ) {
			return isset( $settings[ $setting_key ] ) ? $settings[ $setting_key ] : $default_value;
		} else {
			return $settings;
		}
	}

	/**
	 * Returns the response headers which must never be stored in, or replayed from, the API cache.
	 *
	 * Two families of headers are excluded:
	 *
	 * - Per-response state. A `Set-Cookie` belongs to the single response that issued it. Storing it
	 *   and replaying it on every cache hit hands a stale cookie to every later requester of the
	 *   same cache row, which breaks strict HTTP clients and leaks session state.
	 * - Transfer and connection headers. The body is re-encoded on every cache hit, so the original
	 *   `Content-Length` / `Content-Encoding` / `Content-MD5` and `Date` no longer describe it, and
	 *   `Connection` / `Keep-Alive` / `Transfer-Encoding` are hop-by-hop (RFC 9110 section 7.6.1).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array List of lower-cased header names.
	 */
	public function get_uncacheable_headers() {
		$disallow_headers = array(
			'X-App-Logged-In',
			'X-App-Unread-Notifications',
			'bbp-unread-messages',
			'Expires',
			'Cache-Control',
			// Per-response state, must never be replayed from a cached row.
			'Set-Cookie',
			// Transfer and connection headers, only valid for the original transfer.
			'Content-Length',
			'Content-Encoding',
			'Content-MD5',
			'Transfer-Encoding',
			'Connection',
			'Keep-Alive',
			'Date',
		);

		/**
		 * Filters the response headers which are not allowed inside the API cache.
		 *
		 * To hook this filter the code needs to be executed from mu-plugin level.
		 *
		 * The list is applied when a response is stored and again when a cached response is
		 * replayed, so headers added here are also stripped from rows that already exist.
		 *
		 * @since BuddyBoss 1.5.7
		 * @since BuddyBoss [BBVERSION] Moved to Helper::get_uncacheable_headers() and also applied
		 *                              when replaying a cached response.
		 *
		 * @param array $disallow_headers List of header names.
		 */
		$disallow_headers = apply_filters( 'rest_post_disprepare_header_cache', $disallow_headers );

		$uncacheable_headers = array();

		foreach ( (array) $disallow_headers as $disallow_header ) {
			if ( ! is_scalar( $disallow_header ) ) {
				continue;
			}

			$disallow_header = strtolower( trim( (string) $disallow_header ) );

			if ( '' !== $disallow_header ) {
				$uncacheable_headers[] = $disallow_header;
			}
		}

		return array_values( array_unique( $uncacheable_headers ) );
	}

	/**
	 * Splits a raw response header line into its name and value.
	 *
	 * `headers_list()` returns raw `Name: value` lines. A field value may legally contain colons
	 * (URLs in `Link`, the `expires=` date in `Set-Cookie`, JSON in `X-App-Language`), so the line
	 * is split on the first colon only. See RFC 9110 section 5.5.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $header_line Raw header line.
	 *
	 * @return array|false Array of array( name, value ), or false when the line is not a header.
	 */
	public function parse_header_line( $header_line ) {
		if ( ! is_string( $header_line ) || false === strpos( $header_line, ':' ) ) {
			return false;
		}

		$header_parts = explode( ':', $header_line, 2 );
		$header_name  = trim( $header_parts[0] );

		if ( '' === $header_name ) {
			return false;
		}

		return array( $header_name, trim( $header_parts[1] ) );
	}

	/**
	 * Checks whether a response header may be stored in, or replayed from, the API cache.
	 *
	 * The comparison is case-insensitive because HTTP field names are case-insensitive
	 * (RFC 9110 section 5.1) while integrations register deny-list entries in mixed case.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $header_name         Header name.
	 * @param array  $uncacheable_headers Optional. Pre-built deny list, as returned by
	 *                                    Helper::get_uncacheable_headers(). Pass it when checking
	 *                                    several headers in a row to avoid re-running the filter.
	 *                                    Default null.
	 *
	 * @return bool True when the header can be cached.
	 */
	public function is_header_cacheable( $header_name, $uncacheable_headers = null ) {
		if ( null === $uncacheable_headers ) {
			$uncacheable_headers = $this->get_uncacheable_headers();
		}

		$header_name = strtolower( trim( (string) $header_name ) );

		if ( '' === $header_name ) {
			return false;
		}

		return ! in_array( $header_name, (array) $uncacheable_headers, true );
	}

	/**
	 * Adds a header to a header collection without losing repeated field lines.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array  $headers      Collection of header name => value pairs.
	 * @param string $header_name  Header name.
	 * @param mixed  $header_value Header value.
	 * @param bool   $replace      Optional. Whether an already collected header of the same name is
	 *                             replaced, mirroring PHP's `header()`. When false the values are
	 *                             combined into one comma separated field value, which is how HTTP
	 *                             defines repeated field lines (RFC 9110 section 5.3). Default true.
	 *
	 * @return array Updated collection.
	 */
	public function add_header( $headers, $header_name, $header_value, $replace = true ) {
		$headers     = (array) $headers;
		$header_name = trim( (string) $header_name );

		if ( '' === $header_name ) {
			return $headers;
		}

		$header_value = is_array( $header_value ) ? implode( ', ', $header_value ) : (string) $header_value;
		$stored_name  = $header_name;

		foreach ( array_keys( $headers ) as $existing_name ) {
			if ( 0 === strcasecmp( (string) $existing_name, $header_name ) ) {
				$stored_name = $existing_name;
				break;
			}
		}

		if ( ! array_key_exists( $stored_name, $headers ) || $replace ) {
			$headers[ $stored_name ] = $header_value;

			return $headers;
		}

		$existing_value = $headers[ $stored_name ];
		$existing_value = is_array( $existing_value ) ? implode( ', ', $existing_value ) : (string) $existing_value;

		if ( '' === $existing_value ) {
			$headers[ $stored_name ] = $header_value;
		} elseif ( '' !== $header_value ) {
			$headers[ $stored_name ] = $existing_value . ', ' . $header_value;
		}

		return $headers;
	}
}
