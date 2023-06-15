<?php
/**
 * BP REST: BP_REST_Media_Details_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Media Details endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Media_Details_Endpoint extends WP_REST_Controller {

	/**
	 * BP_REST_Media_Endpoint Instance.
	 *
	 * @var BP_REST_Media_Endpoint
	 */
	protected $media_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'media';

		$this->media_endpoint = new BP_REST_Media_Endpoint();
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

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/move',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'move_items' ),
					'permission_callback' => array( $this, 'move_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema(),
				),
			)
		);
	}


	/**
	 * Retrieve Media details.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/media/details Media Details
	 * @apiName        GetBBMediaDetails
	 * @apiGroup       Media
	 * @apiDescription Retrieve Media details(includes tabs and privacy options)
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 */
	public function get_items( $request ) {
		$retval = array();

		$retval['tabs']    = $this->get_media_tabs();
		$retval['privacy'] = $this->get_media_privacy();

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of medias details is fetched via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Response $response  The response data.
		 * @param WP_REST_Request  $request   The request sent to the API.
		 */
		do_action( 'bp_rest_media_details_get_items', $response, $request );

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
		 * Filter the media details `get_items` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_media_details_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Move medias into albums.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since          0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/media/move Move Medias
	 * @apiName        MoveBBPhotos
	 * @apiGroup       Media
	 * @apiDescription Move Medias into the albums.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Array} media_ids Media specific IDs.
	 * @apiParam {Number} album_id A unique numeric ID for the Media Album.
	 * @apiParam {Number} [group_id] A unique numeric ID for the Group.
	 */
	public function move_items( $request ) {

		if ( empty( $request['media_ids'] ) ) {
			return new WP_Error(
				'bp_rest_no_media_found',
				__( 'Sorry, you are not allowed to move a Media item.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		$group_id = isset( $request['group_id'] ) && ! empty( $request['group_id'] ) ? $request['group_id'] : 0;
		$album_id = isset( $request['album_id'] ) && ! empty( $request['album_id'] ) ? $request['album_id'] : 0;

		if ( ! empty( $album_id ) ) {
			$album    = new BP_Media_Album( $album_id );
			$group_id = $album->group_id;
		}

		if (
			function_exists( 'bb_media_user_can_upload' ) &&
			! bb_media_user_can_upload( bp_loggedin_user_id(), (int) $group_id )
		) {
			return new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to move a media.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$media_ids = wp_parse_id_list( array_filter( $request['media_ids'] ) );

		$retval = array(
			'failed' => array(),
			'medias' => array(),
		);

		foreach ( $media_ids as $media_id ) {
			$media = new BP_Media( $media_id );
			if ( (int) $media->group_id !== (int) $group_id ) {
				$retval['failed'][ $media_id ] = new WP_Error(
					'bp_rest_invalid_move_with_album',
					__( 'Sorry, you are not allowed to move this media with album.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} else {
				$moved_media_id = bp_media_move_media_to_album( $media_id, $album_id, $group_id );
				if ( empty( (int) $moved_media_id ) || is_wp_error( $moved_media_id ) ) {
					$retval['failed'][ $media_id ] = new WP_Error(
						'bp_rest_invalid_move_with_album',
						__( 'Sorry, you are not allowed to move this media with album.', 'buddyboss' ),
						array(
							'status' => 404,
						)
					);
				} else {
					$media              = new BP_Media( $moved_media_id );
					$retval['medias'][] = $this->prepare_response_for_collection(
						$this->media_endpoint->prepare_item_for_response( $media, $request )
					);
				}
			}
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a Media is moved via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_media_move_items', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to create a media.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function move_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to create a media.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if (
			is_user_logged_in() &&
			(
				! function_exists( 'bb_media_user_can_upload' ) ||
				(
					function_exists( 'bb_media_user_can_upload' ) &&
					bb_media_user_can_upload( bp_loggedin_user_id(), $request->get_param( 'group_id' ) )
				)
			)
		) {
			$retval = true;

			if (
				isset( $request['group_id'] ) &&
				! empty( $request['group_id'] ) &&
				(
					! bp_is_active( 'groups' )
					|| ! groups_can_user_manage_media( bp_loggedin_user_id(), (int) $request['group_id'] )
					|| ! function_exists( 'bp_is_group_media_support_enabled' )
					|| ( function_exists( 'bp_is_group_media_support_enabled' ) && false === bp_is_group_media_support_enabled() )
				)
			) {
				$retval = new WP_Error(
					'bp_rest_invalid_permission',
					__( 'You don\'t have a permission to move a media inside this group.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			} elseif ( isset( $request['album_id'] ) && ! empty( $request['album_id'] ) ) {
				$parent_album = new BP_Media_Album( $request['album_id'] );
				if ( empty( $parent_album->id ) ) {
					$retval = new WP_Error(
						'bp_rest_invalid_album_id',
						__( 'Invalid Album ID.', 'buddyboss' ),
						array(
							'status' => 400,
						)
					);
				}

				$album_privacy = bb_media_user_can_access( $parent_album->id, 'album' );
				if ( true === $retval && true !== (bool) $album_privacy['can_move'] ) {
					$retval = new WP_Error(
						'bp_rest_invalid_permission',
						__( 'You don\'t have a permission to move a media inside this album.', 'buddyboss' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
				}
			}
		}

		/**
		 * Filter the Media `move_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_media_move_items_permissions_check', $retval, $request );
	}

	/**
	 * Select the item schema arguments needed for the CREATABLE methods.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 * @since 0.1.0
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = array();
		$key  = 'create';

		if ( WP_REST_Server::CREATABLE === $method ) {
			$args['media_ids'] = array(
				'description'       => __( 'Media specific IDs.', 'buddyboss' ),
				'default'           => array(),
				'type'              => 'array',
				'required'          => true,
				'items'             => array( 'type' => 'integer' ),
				'sanitize_callback' => 'wp_parse_id_list',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['group_id'] = array(
				'description'       => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['album_id'] = array(
				'description'       => __( 'A unique numeric ID for the Media Album.', 'buddyboss' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( "bp_rest_media_move_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the media details schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_media_details',
			'type'       => 'object',
			'properties' => array(
				'tabs'    => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Media directory tabs.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
					'items'       => array(
						'type' => 'array',
					),
				),
				'privacy' => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Media privacy.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the media details schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_media_details_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get Media Directory tabs.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_media_tabs() {
		$tabs = array();
		add_filter( 'bp_is_current_component', array( $this, 'bp_rest_is_current_component' ), 999, 2 );
		add_filter( 'bp_get_total_media_count', array( $this, 'bp_rest_get_total_media_count' ) );

		$tabs_items = function_exists( 'bp_nouveau_get_media_directory_nav_items' ) ? bp_nouveau_get_media_directory_nav_items() : array();

		remove_filter( 'bp_get_total_media_count', array( $this, 'bp_rest_get_total_media_count' ) );
		remove_filter( 'bp_is_current_component', array( $this, 'bp_rest_is_current_component' ), 999, 2 );

		if ( ! empty( $tabs_items ) ) {
			foreach ( $tabs_items as $key => $item ) {
				if ( 'group' === $key ) {
					$key = 'groups';
				}
				$tabs[ $key ]['title']    = $item['text'];
				$tabs[ $key ]['count']    = bp_core_number_format( $item['count'] );
				$tabs[ $key ]['position'] = $item['position'];
			}
		}

		return $tabs;
	}

	/**
	 * Get privacy for the media.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_media_privacy() {
		$privacy = apply_filters( 'bp_media_get_visibility_levels', buddypress()->media->visibility_levels );
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

	/**
	 * Return the total media count in your BP instance.
	 *
	 * @since 0.1.0
	 *
	 * @return int Media count.
	 */
	public function bp_rest_get_total_media_count() {
		$count = $GLOBALS['media_template']->total_media_count;

		/**
		 * Filters the total number of media.
		 *
		 * @since 0.1.0
		 *
		 * @param int $count Total number of media.
		 */
		return apply_filters( 'bp_rest_get_total_media_count', (int) $count );
	}

	/**
	 * Set Media Component while getting the photo count.
	 *
	 * @param boolean $is_current_component Check is valid component.
	 * @param string  $component            Current component name.
	 *
	 * @return boolean
	 */
	public function bp_rest_is_current_component( $is_current_component, $component ) {
		if ( 'media' !== $component ) {
			return $is_current_component;
		}

		return true;
	}

}
