<?php
/**
 * BP REST: BB_REST_Forums_Link_Preview_Endpoint class
 *
 * @package BuddyBoss
 * @since 2.3.60
 */

defined( 'ABSPATH' ) || exit;

/**
 * Forums link preview endpoints.
 *
 * @since 2.3.60
 */
class BB_REST_Forums_Link_Preview_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 2.3.60
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'forums';
	}

	/**
	 * Register the component routes.
	 *
	 * @since 2.3.60
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/link-preview',
			array(
				'args'   => array(
					'url' => array(
						'description' => __( 'URL for the generate link preview.', 'buddyboss' ),
						'type'        => 'string',
						'required'    => true,
					),
				),
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
	 * Retrieve Forums link preview.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 2.3.60
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/forums/link-preview Link Preview
	 * @apiName        GetBBForumsLinkPreview
	 * @apiGroup       Forums
	 * @apiDescription Retrieve link preview Forums.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {String} url URL for the generate link preview.
	 */
	public function get_items( $request ) {

		$url = $request->get_param( 'url' );

		$parsed_url = wp_parse_url( $url );
		if ( ! $parsed_url || empty( $parsed_url['host'] ) ) {
			$url = 'http://' . $url;
		}

		if ( ! wp_http_validate_url( $url ) ) {
			return new WP_Error(
				'bp_rest_invalid_url',
				__( 'Sorry, URL is not valid.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		$retval = array(
			'title'       => '',
			'description' => '',
			'images'      => '',
			'error'       => '',
			'wp_embed'    => '',
		);

		// Get URL parsed data.
		$parse_url_data = ( function_exists( 'bp_core_parse_url' ) ? bp_core_parse_url( $url ) : '' );

		// If empty data then send error.
		if ( empty( $parse_url_data ) ) {
			return new WP_Error(
				'bp_rest_unknown_error',
				__( 'There was a problem generating a link preview.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		$retval = array_merge( $retval, $parse_url_data );

		$retval   = $this->add_additional_fields_to_object( $retval, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after the forum link preview is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 2.3.60
		 */
		do_action( 'bb_rest_forums_link_preview_get_items', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to forum link preview items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 2.3.60
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_component_required',
			__( 'Sorry, Forums component was not enabled.', 'buddyboss' ),
			array(
				'status' => '404',
			)
		);

		if ( bp_is_active( 'forums' ) ) {
			$retval = true;
		}

		if ( true === $retval && ! ( is_user_logged_in() || bbp_allow_anonymous() ) ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to generate link preview in the forums.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( true === $retval && function_exists( 'bbp_use_autoembed' ) && true !== bbp_use_autoembed() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, Link Previews is disabled.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the forum link preview permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 2.3.60
		 */
		return apply_filters( 'bb_rest_forums_link_preview_get_items_permissions_check', $retval, $request );
	}


	/**
	 * Get the plugin schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 2.3.60
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bb_forums_link_preview',
			'type'       => 'object',
			'properties' => array(
				'title'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Title for the link preview.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'description' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Description or HTML to generate the link preview.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'images'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Image URLs for the preview.', 'buddyboss' ),
					'type'        => 'array',
					'readonly'    => true,
					'items'       => array(
						'type' => 'string',
					),
				),
				'error'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'If any errors to retrieving a the preview data.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'wp_embed'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the URL is wp embed or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the forum link preview schema.
		 *
		 * @param string $schema The endpoint schema.
		 */
		return apply_filters( 'bb_rest_forums_link_preview_schema', $this->add_additional_fields_schema( $schema ) );
	}

}
