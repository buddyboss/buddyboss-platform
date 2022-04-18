<?php
/**
 * BP REST: BP_REST_Mention_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Mention_Endpoint extends WP_REST_Controller {

	/**
	 * Variable to store the argument data.
	 *
	 * @var array of arguments.
	 */
	protected $data_args;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'mention';
	}

	/**
	 * Register the mention routes.
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
	 * Retrieve members to mention.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/mention Mention Member
	 * @apiName        GetBBMention
	 * @apiGroup       Components
	 * @apiDescription Retrieve member which you want to mention in Activity OR Forum topic and reply.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {String} term Members @name suggestions.
	 * @apiParam {Boolean} [only_friends] Limit result set to Friends only.
	 * @apiParam {Number} [group_id] A unique numeric ID for the Group. Limit result set to the group.
	 */
	public function get_items( $request ) {

		$args = array(
			'term' => sanitize_text_field( $request['term'] ),
			'type' => 'members',
		);

		if ( ! empty( $request['only_friends'] ) ) {
			$args['only_friends'] = absint( $request['only_friends'] );
		} elseif (
			bp_is_active( 'friends' )
			&& isset( $args['term'] )
			&& empty( $args['term'] )
			&& empty( trim( $args['term'] ) )
		) {
			$args['only_friends'] = isset( $request['only_friends'] ) ? absint( $request['only_friends'] ) : true;
		} elseif (
			bp_is_active( 'messages' )
			&& bp_is_active( 'friends' )
			&& function_exists( 'bp_force_friendship_to_message' )
			&& bp_force_friendship_to_message()
		) {
			$args['only_friends'] = true;
		}

		if ( ! empty( $request['group_id'] ) ) {
			$args['group_id'] = absint( $request['group_id'] );
			$this->data_args  = $args;
			add_filter( 'bp_groups_member_suggestions_validate_args', array( $this, 'validate_member_suggestions' ), 10, 1 );
		} else {
			$this->data_args = $args;
			add_filter( 'bp_members_suggestions_validate_args', array( $this, 'validate_member_suggestions' ), 10, 1 );
		}

		$results = bp_core_get_suggestions( $args );

		if ( ! empty( $request['group_id'] ) ) {
			remove_filter( 'bp_groups_member_suggestions_validate_args', array( $this, 'validate_member_suggestions' ), 10, 1 );
		} else {
			remove_filter( 'bp_members_suggestions_validate_args', array( $this, 'validate_member_suggestions' ), 10, 1 );
		}

		if ( is_wp_error( $results ) ) {
			return new WP_Error(
				'bp_rest_unknown_error',
				$results->get_error_message(),
				array(
					'status' => 400,
				)
			);
		}

		$retval = array();

		foreach ( $results as $member ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $member, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a component is updated via the REST API.
		 *
		 * @param array            $results  Component info.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_mention_items', $results, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to list mentions.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you do not have access to list mentions.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the mention `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_mention_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Prepares group data for return as an object.
	 *
	 * @param BP_Groups_Group $item    Group object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $item, $request ) {

		$data = array(
			'id'            => $item->user_id,
			'display_id'    => $item->ID,
			'user_nicename' => $item->user_nicename,
			'name'          => $item->name,
			'image'         => $item->image,
		);

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item ) );

		/**
		 * Filter a group value returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param BP_Groups_Group  $item     Group object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_member_prepare_value', $response, $request, $item );
	}

	/**
	 * Prepares links for the user request.
	 *
	 * @param object $user User object.
	 *
	 * @return array Links for the given user.
	 */
	protected function prepare_links( $user ) {
		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '%s/%s/%d', $this->namespace, 'members', $user->user_id ) ),
			),
		);

		return $links;
	}

	/**
	 * Get the settings schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_mention',
			'type'       => 'object',
			'properties' => array(
				'id'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the Member.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'display_id'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Member\'s mention name.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'user_nicename' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Member\'s nicename.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'name'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Member\'s display name.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'image'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Member\'s avatar image.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
			),
		);

		/**
		 * Filters the mention schema.
		 *
		 * @param string $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_mention_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params = array(
			'term'         => array(
				'description'       => __( 'Members @name suggestions.', 'buddyboss' ),
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'only_friends' => array(
				'description'       => __( 'Limit result set to Friends only.', 'buddyboss' ),
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'group_id'     => array(
				'description'       => __( 'A unique numeric ID for the Group. Limit result set to the group.', 'buddyboss' ),
				'type'              => 'integer',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_mention_collection_params', $params );
	}

	/**
	 * Validation status for the member suggestion service query.
	 *
	 * @since 1.7.8
	 *
	 * @param bool|WP_Error $valid Results of validation check.
	 *
	 * @return bool|WP_Error
	 */
	public function validate_member_suggestions( $valid ) {
		$args = $this->data_args;

		if (
			is_wp_error( $valid ) &&
			$valid->get_error_code() === 'missing_parameter' &&
			bp_is_active( 'friends' ) &&
			! empty( $args ) &&
			isset( $args['term'] ) &&
			empty( trim( $args['term'] ) ) &&
			! empty( $args['only_friends'] )
		) {
			return true;
		}

		return $valid;
	}
}
