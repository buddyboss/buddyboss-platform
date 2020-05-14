<?php
/**
 * BP REST: BP_REST_Attachments_Blog_Avatar_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Blog avatar endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Attachments_Blog_Avatar_Endpoint extends WP_REST_Controller {

	use BP_REST_Attachments;

	/**
	 * Reuse some parts of the BP_REST_Blogs_Endpoint class.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_REST_Blogs_Endpoint
	 */
	protected $blogs_endpoint;

	/**
	 * This variable is used to query for the requested blog only once.
	 * It is set during the permission check methods.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_Blogs_Blog
	 */
	protected $blog;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace      = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base      = buddypress()->blogs->id;
		$this->blogs_endpoint = new BP_REST_Blogs_Endpoint();
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/avatar',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'A unique numeric ID for the blog.', 'buddyboss' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $this->get_item_collection_params(),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Fetch an existing blog avatar.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		if ( empty( $this->blog->admin_user_id ) ) {
			return new WP_Error(
				'bp_rest_blog_avatar_get_item_user_failed',
				__( 'There was a problem confirming the blog\'s user admin is valid.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$admin_user_admin = (int) $this->blog->admin_user_id;

		$args = array();
		foreach ( array( 'full', 'thumb' ) as $type ) {
			$args[ $type ] = bp_get_blog_avatar(
				array(
					'type'          => $type,
					'blog_id'       => $request['id'],
					'admin_user_id' => $admin_user_admin,
					'html'          => (bool) $request['html'],
					'alt'           => $request['alt'],
					'no_grav'       => (bool) $request['no_user_gravatar'],
				)
			);
		}

		// Get the avatar object.
		$avatar = $this->get_avatar_object( $args );

		if ( ! $avatar->full && ! $avatar->thumb ) {
			return new WP_Error(
				'bp_rest_attachments_blog_avatar_no_image',
				__( 'Sorry, there was a problem fetching the blog avatar.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$retval = array(
			$this->prepare_response_for_collection(
				$this->prepare_item_for_response( $avatar, $request )
			),
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a blog avatar is fetched via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param stdClass          $avatar   The avatar object.
		 * @param WP_REST_Response  $response The response data.
		 * @param WP_REST_Request   $request  The request sent to the API.
		 */
		do_action( 'bp_rest_attachments_blog_avatar_get_item', $avatar, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to get a blog avatar.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		$retval     = true;
		$this->blog = $this->blogs_endpoint->get_blog_object( $request['id'] );

		if ( true === $retval && ! is_object( $this->blog ) ) {
			$retval = new WP_Error(
				'bp_rest_blog_invalid_id',
				__( 'Invalid group ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( true === $retval && ! buddypress()->avatar->show_avatars ) {
			$retval = new WP_Error(
				'bp_rest_attachments_blog_avatar_disabled',
				__( 'Sorry, blog avatar is disabled.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		/**
		 * Filter the blog avatar `get_item` permissions check.
		 *
		 * @since 0.1.0
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_attachments_blog_avatar_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares avatar data to return as an object.
	 *
	 * @since 0.1.0
	 *
	 * @param stdClass        $avatar  Avatar object.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $avatar, $request ) {
		$data = array(
			'full'  => $avatar->full,
			'thumb' => $avatar->thumb,
		);

		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		/**
		 * Filter a blog avatar value returned from the API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Response  $response Response.
		 * @param WP_REST_Request   $request  Request used to generate the response.
		 * @param object            $avatar   Avatar object.
		 */
		return apply_filters( 'bp_rest_attachments_blog_avatar_prepare_value', $response, $request, $avatar );
	}

	/**
	 * Get the blog avatar schema, conforming to JSON Schema.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_attachments_blog_avatar',
			'type'       => 'object',
			'properties' => array(
				'full'  => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Full size of the image file.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				),
				'thumb' => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Thumb size of the image file.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the blog avatar schema.
		 *
		 * @param string $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_attachments_blog_avatar_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for the `get_item`.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_item_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		// Removing unused params.
		unset( $params['search'], $params['page'], $params['per_page'] );

		$params['html'] = array(
			'description'       => __( 'Whether to return an <img> HTML element, vs a raw URL to an avatar.', 'buddyboss' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['alt'] = array(
			'description'       => __( 'The alt attribute for the <img> element.', 'buddyboss' ),
			'default'           => '',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['no_user_gravatar'] = array(
			'description'       => __( 'Whether to disable the default Gravatar Admin user fallback.', 'buddyboss' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the item collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_attachments_blog_avatar_collection_params', $params );
	}
}
