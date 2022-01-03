<?php
/**
 * BP REST: BP_REST_Document_Settings_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Document Extensions endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Document_Settings_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace         = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base         = 'document';
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base.'/extensions',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
                    'permission_callback' => array( $this, 'get_extensions_permissions_check' ),
				),
			)
		);

	}

	/**
	 * Retrieve document extensions.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/document/extensions Get Extensions
	 * @apiGroup       Document
	 * @apiDescription Retrieve Extensions.
	 * @apiVersion     1.0.0
	 */
	public function get_settings( $request ) {

        $retval = bp_document_extensions_list();


		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of document's folder is fetched via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @param array            $folders  Fetched Folders.
		 */
		do_action( 'bp_rest_document_get_extensions', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to get all users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function get_extensions_permissions_check( $request ) {
		$retval = true;

		/**
		 * Filter the document details `get_items` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_document_get_extensions_permissions_check', $retval, $request );
	}

}