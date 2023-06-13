<?php
/**
 * BP REST: BP_REST_XProfile_Search_Form_Fields_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * XProfile Search Form Fields endpoints.
 *
 * Use /xprofile/search
 *
 * @since 0.1.0
 */
class BP_REST_XProfile_Search_Form_Fields_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->profile->id . '/search';
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

	}

	/**
	 * Retrieve XProfile search form fields.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/xprofile/search Get Search Form
	 * @apiName        GetBBxProfileSearchForm
	 * @apiGroup       Profile Fields
	 * @apiDescription Retrieve Advanced Search Form fields for Members Directory.
	 * @apiVersion     1.0.0
	 * @apiParam {Number} [form_id] ID of the profile search form.
	 */
	public function get_items( $request ) {

		if ( empty( $request['form_id'] ) ) {
			$args['form_id'] = bp_profile_search_main_form();
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array $args Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_xprofile_search_form_fields_get_items_query_args', $args, $request );

		// Actually, query it.
		$f = bp_profile_search_escaped_form_data( $args['form_id'] );

		if ( ! empty( $f->fields ) ) {
			foreach ( $f->fields as $k => $field ) {
				if ( ! empty( $field->options ) ) {
					$options = array();
					foreach ( $field->options as $key => $label ) {
						$options[ wp_specialchars_decode( $key, ENT_QUOTES ) ] = wp_specialchars_decode( $label, ENT_QUOTES );
					}
					$f->fields[ $k ]->options = $options;
				}
			}
		}

		$response = rest_ensure_response( $f );

		/**
		 * Fires after a list of field are fetched via the REST API.
		 *
		 * @param array $field_groups Fetched field groups.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_xprofile_search_form_fields_get_items', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to XProfile search form fields.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {

		/**
		 * Filter the XProfile fields `get_items` permissions check.
		 *
		 * @param bool $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_profile_search_main_form', ( function_exists( 'bp_disable_advanced_profile_search' ) ? ! bp_disable_advanced_profile_search() : false ), $request );
	}

	/**
	 * Get the XProfile field schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_xprofile_search_form_field',
			'type'       => 'object',
			'properties' => array(),
		);

		/**
		 * Filters the xprofile search form field schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_xprofile_search_form_field_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for the XProfile search form fields.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params = array(
			'form_id' => array(
				'description'       => __( 'ID of the profile search form.', 'buddyboss' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_xprofile_search_form_fields_collection_params', $params );
	}
}
