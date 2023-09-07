<?php
/**
 * BP REST: BP_REST_Document_Details_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Document Details endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Document_Details_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'document';
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/details',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}


	/**
	 * Retrieve documents details.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/document/details Document Details
	 * @apiName        GetBBDocumentDetails
	 * @apiGroup       Document
	 * @apiDescription Retrieve Document details(includes tabs and privacy options)
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 */
	public function get_items( $request ) {
		$retval = array();

		$retval['tabs']    = $this->get_documents_tabs();
		$retval['privacy'] = $this->get_documents_privacy();

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of documents details is fetched via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Response $response  The response data.
		 * @param WP_REST_Request  $request   The request sent to the API.
		 */
		do_action( 'bp_rest_document_details_get_items', $response, $request );

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
	public function get_items_permissions_check( $request ) {
		$retval = true;

		/**
		 * Filter the document details `get_items` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_document_details_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Get the document details schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_document_details',
			'type'       => 'object',
			'properties' => array(
				'tabs'    => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Documents directory tabs.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
					'items'       => array(
						'type' => 'array',
					),
				),
				'privacy' => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Document privacy.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the document details schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_document_details_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get Documents tabs.
	 *
	 * @return array
	 */
	public function get_documents_tabs() {
		$tabs = array();

		$tabs_items = function_exists( 'bp_nouveau_get_document_directory_nav_items' ) ? bp_nouveau_get_document_directory_nav_items() : array();

		if ( ! empty( $tabs_items ) ) {
			foreach ( $tabs_items as $key => $item ) {
				$key = $item['slug'];

				$tabs[ $key ]['title']    = $item['text'];
				$tabs[ $key ]['position'] = $item['position'];
			}
		}

		return $tabs;
	}

	/**
	 * Get privacy for the documents.
	 *
	 * @return array
	 */
	public function get_documents_privacy() {
		$privacy = apply_filters( 'bp_document_get_visibility_levels', buddypress()->document->visibility_levels );
		$retval  = array();

		if ( ! empty( $privacy ) ) {
			foreach ( $privacy as $key => $value ) {
				if ( 'grouponly' === $key ) {
					continue;
				}

				$retval[ $key ] = $value;
			}
		}

		return $retval;
	}

}
