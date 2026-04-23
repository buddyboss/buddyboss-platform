<?php
/**
 * BuddyBoss REST API Response Formatter
 *
 * Standard response formatting for all BuddyBoss REST API endpoints.
 * Ensures consistent response formats across all endpoints.
 *
 * @package BuddyBoss\Core\REST
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * REST API Response Formatter Class
 *
 * @since BuddyBoss 3.0.0
 */
class BB_REST_Response {

	/**
	 * Create a successful response.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param mixed $data Response data.
	 * @param array $meta Optional metadata.
	 * @return WP_REST_Response
	 */
	public static function success( $data, $meta = array() ) {
		$response_data = array(
			'success' => true,
			'data'    => $data,
		);

		// Add metadata if provided.
		if ( ! empty( $meta ) ) {
			$response_data['meta'] = array_merge(
				array(
					'timestamp' => current_time( 'c' ), // ISO 8601.
					'version'   => '1.0',
				),
				$meta
			);
		}

		return rest_ensure_response( $response_data );
	}

	/**
	 * Create a paginated response.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $data     Array of items for current page.
	 * @param int   $total    Total number of items across all pages.
	 * @param int   $page     Current page number.
	 * @param int   $per_page Items per page.
	 * @param array $args     Optional additional args (base_url, query_params).
	 * @return WP_REST_Response
	 */
	public static function paginated( $data, $total, $page, $per_page, $args = array() ) {
		$total_pages = (int) ceil( $total / $per_page );

		$response_data = array(
			'success'    => true,
			'data'       => $data,
			'pagination' => array(
				'page'        => (int) $page,
				'per_page'    => (int) $per_page,
				'total'       => (int) $total,
				'total_pages' => $total_pages,
			),
		);

		// Generate HATEOAS links.
		if ( ! empty( $args['base_url'] ) ) {
			$response_data['links'] = self::generate_pagination_links(
				$args['base_url'],
				$page,
				$per_page,
				$total_pages,
				$args['query_params'] ?? array()
			);
		}

		$response = rest_ensure_response( $response_data );

		// Add standard WordPress headers.
		$response->header( 'X-WP-Total', (int) $total );
		$response->header( 'X-WP-TotalPages', $total_pages );

		// Add Link header for next/prev.
		if ( $page < $total_pages ) {
			$next_url = add_query_arg( 'page', $page + 1, $args['base_url'] ?? '' );
			$response->header( 'Link', sprintf( '<%s>; rel="next"', $next_url ) );
		}

		return $response;
	}

	/**
	 * Create an error response.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param int    $status  HTTP status code.
	 * @param array  $errors  Field-specific errors.
	 * @param array  $details Additional error details.
	 * @return WP_Error
	 */
	public static function error( $code, $message, $status = 400, $errors = array(), $details = array() ) {
		$error_data = array(
			'status' => $status,
		);

		if ( ! empty( $errors ) ) {
			$error_data['errors'] = $errors;
		}

		if ( ! empty( $details ) ) {
			$error_data['details'] = $details;
		}

		return new WP_Error( $code, $message, $error_data );
	}

	/**
	 * Generate pagination links (HATEOAS).
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $base_url     Base URL for the endpoint.
	 * @param int    $page         Current page.
	 * @param int    $per_page     Items per page.
	 * @param int    $total_pages  Total pages.
	 * @param array  $query_params Additional query parameters.
	 * @return array Links array.
	 */
	private static function generate_pagination_links( $base_url, $page, $per_page, $total_pages, $query_params = array() ) {
		$links = array();

		$base_params = array_merge(
			$query_params,
			array( 'per_page' => $per_page )
		);

		// Self link.
		$links['self'] = add_query_arg(
			array_merge( $base_params, array( 'page' => $page ) ),
			$base_url
		);

		// First link.
		$links['first'] = add_query_arg(
			array_merge( $base_params, array( 'page' => 1 ) ),
			$base_url
		);

		// Previous link.
		$links['prev'] = $page > 1
			? add_query_arg( array_merge( $base_params, array( 'page' => $page - 1 ) ), $base_url )
			: null;

		// Next link.
		$links['next'] = $page < $total_pages
			? add_query_arg( array_merge( $base_params, array( 'page' => $page + 1 ) ), $base_url )
			: null;

		// Last link.
		$links['last'] = add_query_arg(
			array_merge( $base_params, array( 'page' => $total_pages ) ),
			$base_url
		);

		return $links;
	}

	/**
	 * Validate pagination parameters.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array Validated pagination params.
	 */
	public static function validate_pagination_params( $request ) {
		$page     = $request->get_param( 'page' ) ?? 1;
		$per_page = $request->get_param( 'per_page' ) ?? 20;

		// Sanitize and validate.
		$page     = max( 1, absint( $page ) );
		$per_page = max( 1, min( 100, absint( $per_page ) ) ); // Max 100 items per page.

		return array(
			'page'     => $page,
			'per_page' => $per_page,
			'offset'   => ( $page - 1 ) * $per_page,
		);
	}

	/**
	 * Format validation errors for response.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $validation_errors Array of field => error message.
	 * @return WP_Error
	 */
	public static function validation_error( $validation_errors ) {
		return self::error(
			'validation_error',
			__( 'Validation failed for one or more fields.', 'buddyboss' ),
			400,
			$validation_errors
		);
	}

	/**
	 * Format permission error.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $message Optional custom message.
	 * @return WP_Error
	 */
	public static function permission_error( $message = '' ) {
		if ( empty( $message ) ) {
			$message = __( 'Sorry, you are not allowed to do that.', 'buddyboss' );
		}

		return self::error(
			'rest_forbidden',
			$message,
			403
		);
	}

	/**
	 * Format not found error.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $resource_type Type of resource (e.g., 'feature', 'group').
	 * @param mixed  $identifier    Resource identifier.
	 * @return WP_Error
	 */
	public static function not_found( $resource_type, $identifier ) {
		return self::error(
			'rest_not_found',
			sprintf(
				/* translators: 1: resource type, 2: identifier */
				__( '%1$s with ID "%2$s" not found.', 'buddyboss' ),
				ucfirst( $resource_type ),
				$identifier
			),
			404
		);
	}

	/**
	 * Format rate limit error.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return WP_Error
	 */
	public static function rate_limit_error() {
		return self::error(
			'rest_too_many_requests',
			__( 'Too many requests. Please try again later.', 'buddyboss' ),
			429
		);
	}

	/**
	 * Format nonce verification error.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return WP_Error
	 */
	public static function nonce_error() {
		return self::error(
			'rest_invalid_nonce',
			__( 'Invalid security token.', 'buddyboss' ),
			403
		);
	}
}
