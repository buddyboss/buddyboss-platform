<?php
/**
 * BuddyBoss API Request Handler
 *
 * @package BuddyBoss
 * @since 1.0.0
 */

namespace BuddyBoss\Core\Admin\Mothership\API;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use BuddyBoss\Core\Admin\Mothership\BB_Credentials;

/**
 * API Request class for handling HTTP requests to the mothership API.
 */
class BB_API_Request {

	/**
	 * API base URL.
	 *
	 * @var string
	 */
	private $api_base_url;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->api_base_url = $this->get_api_base_url();
	}

	/**
	 * Get the API base URL.
	 *
	 * @return string The API base URL.
	 */
	private function get_api_base_url() {
		// Check for environment/constant definition for local development.
		if ( defined( 'BUDDYBOSS_MOTHERSHIP_API_BASE_URL' ) ) {
			return BUDDYBOSS_MOTHERSHIP_API_BASE_URL;
		}

		// Default production API URL.
		return 'https://api.buddyboss.com/v1/';
	}

	/**
	 * Perform a GET request.
	 *
	 * @param string $endpoint The API endpoint.
	 * @param array  $params   Query parameters.
	 * @return BB_API_Response The response object.
	 */
	public function get( $endpoint, $params = array() ) {
		if ( ! empty( $params ) ) {
			$endpoint = add_query_arg( $params, $endpoint );
		}
		return $this->make_request( 'GET', $endpoint );
	}

	/**
	 * Perform a POST request.
	 *
	 * @param string $endpoint The API endpoint.
	 * @param array  $body     Request body.
	 * @return BB_API_Response The response object.
	 */
	public function post( $endpoint, $body = array() ) {
		return $this->make_request( 'POST', $endpoint, $body );
	}

	/**
	 * Perform a PATCH request.
	 *
	 * @param string $endpoint The API endpoint.
	 * @param array  $body     Request body.
	 * @return BB_API_Response The response object.
	 */
	public function patch( $endpoint, $body = array() ) {
		return $this->make_request( 'PATCH', $endpoint, $body );
	}

	/**
	 * Perform a PUT request.
	 *
	 * @param string $endpoint The API endpoint.
	 * @param array  $body     Request body.
	 * @return BB_API_Response The response object.
	 */
	public function put( $endpoint, $body = array() ) {
		return $this->make_request( 'PUT', $endpoint, $body );
	}

	/**
	 * Perform a DELETE request.
	 *
	 * @param string $endpoint The API endpoint.
	 * @param array  $body     Request body.
	 * @return BB_API_Response The response object.
	 */
	public function delete( $endpoint, $body = array() ) {
		return $this->make_request( 'DELETE', $endpoint, $body );
	}

	/**
	 * Make an HTTP request.
	 *
	 * @param string $method   HTTP method.
	 * @param string $endpoint API endpoint.
	 * @param array  $body     Request body.
	 * @return BB_API_Response The response object.
	 */
	private function make_request( $method, $endpoint, $body = array() ) {
		$url = $this->api_base_url . ltrim( $endpoint, '/' );

		$args = array(
			'method'  => $method,
			'headers' => $this->get_auth_headers(),
			'timeout' => 30,
		);

		if ( ! empty( $body ) ) {
			$args['body']                    = wp_json_encode( $body );
			$args['headers']['Content-Type'] = 'application/json; charset=utf-8';
			$args['headers']['Accept']       = 'application/json';
		}

		$response = wp_remote_request( $url, $args );

		return $this->handle_response( $response );
	}

	/**
	 * Get authentication headers.
	 *
	 * @return array Authentication headers.
	 */
	private function get_auth_headers() {
		$headers = array();

		$license_key = BB_Credentials::get_license_key();
		$domain      = BB_Credentials::get_activation_domain();

		if ( $license_key && $domain ) {
			$headers['Authorization'] = 'Basic ' . base64_encode( $domain . ':' . $license_key );
		}

		// Alternative authentication for admin operations.
		$email     = BB_Credentials::get_email();
		$api_token = BB_Credentials::get_api_token();

		if ( ! isset( $headers['Authorization'] ) && $email && $api_token ) {
			$headers['Authorization'] = 'Basic ' . base64_encode( $email . ':' . $api_token );
		}

		return $headers;
	}

	/**
	 * Handle the API response.
	 *
	 * @param array|WP_Error $response The response from wp_remote_request.
	 * @return BB_API_Response The response object.
	 */
	private function handle_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return new BB_API_Response( null, $response->get_error_message(), 0 );
		}

		$body          = wp_remote_retrieve_body( $response );
		$data          = json_decode( $body );
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code >= 200 && $response_code <= 299 ) {
			return new BB_API_Response( $data, null, $response_code );
		}

		// Handle error response.
		$error_message = isset( $data->message ) ? $data->message : 'Unknown error';
		
		if ( isset( $data->errors ) && is_object( $data->errors ) ) {
			$errors = array();
			foreach ( $data->errors as $field => $messages ) {
				$errors[] = $field . ': ' . implode( ', ', $messages );
			}
			if ( ! empty( $errors ) ) {
				$error_message .= ' - ' . implode( '; ', $errors );
			}
		}

		return new BB_API_Response( null, $error_message, $response_code );
	}
}

/**
 * API Response class.
 */
class BB_API_Response {

	/**
	 * Response data.
	 *
	 * @var mixed
	 */
	public $data;

	/**
	 * Error message.
	 *
	 * @var string
	 */
	public $error;

	/**
	 * HTTP status code.
	 *
	 * @var int
	 */
	public $status_code;

	/**
	 * Products array for addon responses.
	 *
	 * @var array
	 */
	public $products;

	/**
	 * Constructor.
	 *
	 * @param mixed  $data        Response data.
	 * @param string $error       Error message.
	 * @param int    $status_code HTTP status code.
	 */
	public function __construct( $data = null, $error = null, $status_code = 200 ) {
		$this->data        = $data;
		$this->error       = $error;
		$this->status_code = $status_code;

		// Handle products response.
		if ( $data && isset( $data->products ) ) {
			$this->products = $data->products;
		}
	}

	/**
	 * Check if response is an error.
	 *
	 * @return bool True if error, false otherwise.
	 */
	public function is_error() {
		return ! empty( $this->error ) || $this->status_code >= 400;
	}

	/**
	 * Get error message.
	 *
	 * @return string Error message.
	 */
	public function get_error_message() {
		return $this->error ?: '';
	}

	/**
	 * Get error code.
	 *
	 * @return int Error code.
	 */
	public function get_error_code() {
		return $this->status_code;
	}
}