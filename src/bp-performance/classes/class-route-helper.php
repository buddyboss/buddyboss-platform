<?php
/**
 * BuddyBoss Performance Route Helper.
 *
 * @package BuddyBoss\Performance\Route_Helper
 */

namespace BuddyBoss\Performance;

/**
 * Route Helper class.
 */
class Route_Helper {

	/**
	 * Class instance.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Class instance.
	 *
	 * @return Route_Helper
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Check the valid route.
	 *
	 * This function will prepare regex to replace :parameter with actual value
	 * Example - <parameter>
	 *
	 * @param string $pattern Pattern to check.
	 *
	 * @return bool|string
	 */
	public static function get_route_regex( $pattern ) {

		// Case-1) Invalid pattern.
		if ( preg_match( '/[^-<\/_{}()a-zA-Z\d]>/', $pattern ) ) {
			return false;
		}

		// STEP-1) Capture group for ":parameter".
		$param_chars          = '[a-zA-Z\_\-]+';
		$allowed_value_chars  = '[a-zA-Z0-9-]+';
		$allowed_value_number = '[0-9-]+';

		// Replace <id> to only numberic value.
		$pattern = preg_replace(
			'/<(id)>/', // Replace "<parameter>".
			'(?<$1>' . $allowed_value_number . ')', // with "(?<parameter>[0-9\_\-]+)".
			$pattern
		);

		// Replace the character exclude the <id>.
		$pattern = preg_replace(
			'/<((?!id)' . $param_chars . ')>/', // Replace "<parameter>".
			'(?<$1>' . $allowed_value_chars . ')', // with "(?<parameter>[a-zA-Z0-9\_\-]+)".
			$pattern
		);

		// HINT : To add start matching condition, add ^. Eg : "@^" . $pattern.

		// STEP-2) : Adding end matching condition.
		return '@' . $pattern . '$@D';
	}

	/**
	 * Fetch parameters passed into the endpoint.
	 *
	 * @param string $endpoint_pattern "bbpress/v1/topic/<id>".
	 * @param string $full_url         "http://localhost/wp-json/buddyboss-app/bbpress/v1/topic/488".
	 * @param string $parameter        "id".
	 *
	 * @return mixed|null
	 */
	public static function get_parameter_from_route( $endpoint_pattern, $full_url, $parameter = 'id' ) {

		$pattern_as_regex = self::get_route_regex( $endpoint_pattern );
		$success          = preg_match( $pattern_as_regex, $full_url, $matches );
		if ( $success ) {
			if ( is_array( $matches ) && isset( $matches[ $parameter ] ) ) {
				return $matches[ $parameter ];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	/**
	 * Is Route matched or not.
	 *
	 * @param string $endpoint_pattern "bbpress/v1/topic/<param>".
	 * @param string $current_endpoint "bbpress/v1/topic/488".
	 * @param string $request_method   "GET|POST".
	 *
	 * @return boolean
	 */
	public static function is_matched_from_route( $endpoint_pattern, $current_endpoint, $request_method = 'GET' ) {

		$pattern_as_regex = self::get_route_regex( $endpoint_pattern );
		$success          = preg_match( $pattern_as_regex, $current_endpoint, $matches );
		if ( $success ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			if ( is_array( $matches ) && $request_method === $_SERVER['REQUEST_METHOD'] ) {
				return true;
			} else {
				return false;
			}
		}
	}

}
