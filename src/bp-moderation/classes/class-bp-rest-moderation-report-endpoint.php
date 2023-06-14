<?php
/**
 * BP REST: BP_REST_Moderation_Report_Endpoint class
 *
 * @package BuddyBoss
 *
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Moderation Report endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Moderation_Report_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->moderation->id . '/report';
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
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve report form.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 *
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/moderation/report Get Report Form
	 * @apiName        GetBBReportForm
	 * @apiGroup       Moderation
	 * @apiDescription Retrieve Report Form
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 */
	public function get_items( $request ) {

		$fields = array(
			'type'        => 'radio',
			'name'        => 'report_category',
			'label'       => esc_html__( 'Report Content', 'buddyboss' ),
			'description' => '',
			'is_required' => true,
			'options'     => array(),
		);

		$count = 1;

		$reports_terms = get_terms( 'bpm_category', array( 'hide_empty' => false ) );
		if ( ! empty( $reports_terms ) ) {
			foreach ( $reports_terms as $reports_term ) {
				$show_when           = get_term_meta( $reports_term->term_id, 'bb_category_show_when_reporting', true );
				$fields['options'][] = array(
					'id'                => $reports_term->term_id,
					'type'              => 'option',
					'name'              => wp_specialchars_decode( $reports_term->name ),
					'description'       => wp_specialchars_decode( $reports_term->description ),
					'show_when'         => ! empty( $show_when ) ? $show_when : 'content',
					'is_default_option' => ( 1 === $count++ ),
					'value'             => esc_attr( $reports_term->term_id ),
				);
			}
		}

		$fields['options'][] = array(
			'id'                => '',
			'type'              => 'option',
			'name'              => esc_html__( 'Other', 'buddyboss' ),
			'description'       => '',
			'show_when'         => 'content_members',
			'is_default_option' => ( 1 === $count ),
			'value'             => 'other',
		);

		$retval[] = $fields;

		$retval[] = array(
			'type'        => 'text',
			'name'        => 'note',
			'label'       => '',
			'description' => '',
			'is_required' => false,
			'options'     => array(),
		);

		$retval[] = array(
			'type'        => 'hidden',
			'name'        => 'item_id',
			'label'       => '',
			'description' => '',
			'is_required' => true,
			'options'     => array(),
		);

		// Exclude block member types from report.
		$content_types = bp_moderation_content_types();
		if ( array_key_exists( 'user', $content_types ) ) {
			unset( $content_types['user'] );
		}

		$item_types = array();
		if ( ! empty( $content_types ) ) {
			foreach ( $content_types as $key => $name ) {
				$item_types[] = array(
					'id'                => $key,
					'type'              => 'option',
					'name'              => $name,
					'description'       => '',
					'is_default_option' => false,
					'value'             => $key,
				);
			}
		}

		$retval[] = array(
			'type'        => 'hidden',
			'name'        => 'item_type',
			'label'       => '',
			'description' => '',
			'is_required' => true,
			'options'     => $item_types,
		);

		$retval[] = array(
			'type'        => 'submit',
			'name'        => '',
			'label'       => esc_attr__( 'Send Report', 'buddyboss' ),
			'description' => '',
			'is_required' => false,
			'options'     => array(),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$retval  = $this->add_additional_fields_to_object( $retval, $request );
		$retval  = $this->filter_response_by_context( $retval, $context );

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of report form is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response    The response data.
		 * @param WP_REST_Request  $request     The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_moderation_report_get_items', $response, $request );

		return $response;

	}

	/**
	 * Check if a given request has access to report form items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to view the block members.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the report `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_moderation_report_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Create a moderation report.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 *
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/moderation/report Report a item
	 * @apiName        GetBBReportItem
	 * @apiGroup       Moderation
	 * @apiDescription Report a Item from components.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} item_id Unique identifier for the content to report.
	 * @apiParam {string=activity,activity_comment,groups,forum,forum_topic,forum_reply,document,media} item_type Component type to report.
	 * @apiParam {String} report_category Reasoned category for report.
	 * @apiParam {String} [note] User Notes for the other type of report.
	 */
	public function create_item( $request ) {
		$args = array(
			'category_id'  => ( isset( $request['report_category'] ) ? $request['report_category'] : '' ),
			'content_id'   => ( isset( $request['item_id'] ) ? $request['item_id'] : '' ),
			'content_type' => ( isset( $request['item_type'] ) ? $request['item_type'] : '' ),
			'note'         => ( isset( $request['note'] ) ? $request['note'] : '' ),
		);

		if ( 'other' === $args['category_id'] && empty( $args['note'] ) ) {
			return new WP_Error(
				'rest_missing_callback_param',
				__( 'Missing parameter(s): note', 'buddyboss' ),
				array(
					'status' => 400,
					'params' => array( 'note' ),
				)
			);
		}

		$args = apply_filters( 'bp_rest_moderation_report_pre_insert_value', $args, $request );

		$sub_items     = bp_moderation_get_sub_items( $args['content_id'], $args['content_type'] );
		$item_sub_id   = isset( $sub_items['id'] ) ? $sub_items['id'] : $args['content_id'];
		$item_sub_type = isset( $sub_items['type'] ) ? $sub_items['type'] : $args['content_type'];

		if ( empty( $item_sub_id ) || empty( $item_sub_type ) ) {
			return new WP_Error(
				'bp_rest_moderation_invalid_report',
				__( 'Sorry, Invalid item to report.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		if ( bp_moderation_report_exist( $item_sub_id, $item_sub_type ) ) {
			$message = ( BP_Moderation_Members::$moderation_type_report === $item_sub_type ) ?
			__( 'You have already reported this Member.', 'buddyboss' ) :
			__( 'Sorry, Already reported this item.', 'buddyboss' );
			return new WP_Error(
				'bp_rest_moderation_already_reported',
				$message,
				array(
					'status' => 400,
				)
			);
		}

		$args['content_id']   = $item_sub_id;
		$args['content_type'] = $item_sub_type;

		if ( BP_Moderation_Members::$moderation_type_report === $item_sub_type ) {
			$args['user_report'] = 1;
		}

		$report = bp_moderation_add( $args );

		if ( empty( $report->id ) || empty( $report->report_id ) ) {
			return new WP_Error(
				'bp_rest_moderation_report_error',
				__( 'Sorry, something goes wrong please try again.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		$retval = $this->prepare_item_for_response( $report, $request );

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after the moderation report item is created via the REST API.
		 *
		 * @param BP_Moderation    $report   The created moderation report.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_moderation_report_create_item', $report, $response, $request );

		return $response;

	}

	/**
	 * Checks if a given request has access to report a moderation.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to report a moderation.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;

			$content_type = $request['item_type'];

			if ( ! bp_moderation_user_can( (int) $request['item_id'], $content_type ) ) {
				$retval = new WP_Error(
					'bp_rest_invalid_item',
					__( 'Sorry, you are not allowed to report this item.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the moderation report `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_moderation_report_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares moderation data for return as an object.
	 *
	 * @param BP_Moderation   $report     The Moderation object.
	 * @param WP_REST_Request $request    Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $report, $request ) {
		$data = array(
			'id'            => $report->id,
			'report_id'     => $report->report_id,
			'blog_id'       => $report->blog_id,
			'user_id'       => $report->user_id,
			'item_id'       => $report->item_id,
			'item_type'     => $report->item_type,
			'content'       => $report->content,
			'category_id'   => $report->category_id,
			'date_created'  => $report->date_created,
			'last_updated'  => $report->last_updated,
			'hide_sitewide' => $report->hide_sitewide,
			'count'         => $report->count,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'edit';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $report, $request ) );

		/**
		 * Filter a moderation value returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param BP_Moderation    $report   The Moderation object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_moderation_report_prepare_value', $response, $request, $report );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param BP_Moderation   $report  The Moderation object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	protected function prepare_links( $report, $request ) {
		$report_links  = $this->prepare_report_link( $report->item_id, $report->item_type );
		$request_links = $this->prepare_report_link( $request['item_id'], $request['item_type'] );

		if ( 'media' === $request['item_type'] && bp_is_active( 'activity' ) ) {
			$media = new BP_Media( $request['item_id'] );
			if ( ! empty( $media->activity_id ) ) {
				$report_links = $this->prepare_report_link( $media->activity_id, BP_Suspend_Activity::$type );
			}
		} elseif ( 'document' === $request['item_type'] && bp_is_active( 'activity' ) ) {
			$document = new BP_Document( $request['item_id'] );
			if ( ! empty( $document->activity_id ) ) {
				$report_links = $this->prepare_report_link( $document->activity_id, BP_Suspend_Activity::$type );
			}
		} elseif ( 'video' === $request['item_type'] && bp_is_active( 'activity' ) ) {
			$video = new BP_Video( $request['item_id'] );
			if ( ! empty( $video->activity_id ) ) {
				$report_links = $this->prepare_report_link( $video->activity_id, BP_Suspend_Activity::$type );
			}
		} elseif ( BP_Moderation_Members::$moderation_type_report === $request['item_type'] ) {
			$report_links = $this->prepare_report_link( $request['item_id'], $request['item_type'] );
		}

		$links = array_merge( (array) $report_links, (array) $request_links );

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array         $links  The prepared links of the REST response.
		 * @param BP_Moderation $report The Moderation object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_report_prepare_links', $links, $report );
	}

	/**
	 * Prepare links for the report.
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Item type.
	 *
	 * @return array
	 */
	protected function prepare_report_link( $item_id, $item_type ) {
		$links = array();

		if ( empty( $item_id ) || empty( $item_type ) ) {
			return;
		}

		switch ( $item_type ) {
			case BP_Suspend_Activity::$type:
			case BP_Suspend_Activity_Comment::$type:
				$links['activity'] = array(
					'href'       => rest_url( '/' . $this->namespace . '/' . buddypress()->activity->id . '/' . $item_id ),
					'embeddable' => true,
				);
				break;
			case BP_Suspend_Group::$type:
				$links['group'] = array(
					'href'       => rest_url( '/' . $this->namespace . '/' . buddypress()->groups->id . '/' . $item_id ),
					'embeddable' => true,
				);
				break;
			case BP_Suspend_Forum::$type:
				$links['forum'] = array(
					'href'       => rest_url( '/' . $this->namespace . '/forums/' . $item_id ),
					'embeddable' => true,
				);
				break;
			case BP_Suspend_Forum_Topic::$type:
				$links['topic'] = array(
					'href'       => rest_url( '/' . $this->namespace . '/topics/' . $item_id ),
					'embeddable' => true,
				);
				break;
			case BP_Suspend_Forum_Reply::$type:
				$links['reply'] = array(
					'href'       => rest_url( '/' . $this->namespace . '/reply/' . $item_id ),
					'embeddable' => true,
				);
				break;
			case BP_Suspend_Media::$type:
				$links['media'] = array(
					'href'       => rest_url( '/' . $this->namespace . '/media/' . $item_id ),
					'embeddable' => true,
				);
				break;
			case BP_Suspend_Album::$type:
				$links['albums'] = array(
					'href'       => rest_url( '/' . $this->namespace . '/media/albums/' . $item_id ),
					'embeddable' => true,
				);
				break;
			case BP_Suspend_Document::$type:
				$links['document'] = array(
					'href'       => rest_url( '/' . $this->namespace . '/document/' . $item_id ),
					'embeddable' => true,
				);
				break;
			case BP_Suspend_Folder::$type:
				$links['folder'] = array(
					'href'       => rest_url( '/' . $this->namespace . '/document/folder/' . $item_id ),
					'embeddable' => true,
				);
				break;
			case BP_Suspend_Video::$type:
				$links['video'] = array(
					'href'       => rest_url( '/' . $this->namespace . '/video/' . $item_id ),
					'embeddable' => true,
				);
				break;
			case BP_Moderation_Members::$moderation_type_report:
				$links['member'] = array(
					'href'       => add_query_arg(
						array( 'username_visible' => 1 ),
						rest_url( bp_rest_get_user_url( $item_id ) )
					),
					'embeddable' => true,
				);
				break;
			case BP_Suspend_Comment::$type:
				$links['comment'] = array(
					'href'       => rest_url( '/wp/v2/comments/' . $item_id ),
					'embeddable' => true,
				);
				break;
		}

		return $links;
	}

	/**
	 * Edit the type of the some properties for the CREATABLE & EDITABLE methods.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 * @since 0.1.0
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = WP_REST_Controller::get_endpoint_args_for_item_schema( $method );
		$key  = 'create_item';

		if ( WP_REST_Server::CREATABLE === $method ) {
			$args['context']['enum']      = array( 'edit' );
			$params['context']['default'] = 'edit';

			$reports_terms   = get_terms(
				'bpm_category',
				array(
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);
			$reports_terms[] = 'other';

			$reports_terms = array_map( 'strval', $reports_terms );

			$args['report_category'] = array(
				'description'       => __( 'Report Content', 'buddyboss' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => $reports_terms,
			);

			$args['note'] = array(
				'description'       => __( 'User Notes for the other type of report.', 'buddyboss' ),
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['item_id'] = array(
				'description'       => __( 'Unique identifier for the content to report.', 'buddyboss' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			);

			// Exclude block member types from report.
			$content_types = bp_moderation_content_types();
			if ( array_key_exists( 'user', $content_types ) ) {
				unset( $content_types['user'] );
			}

			$args['item_type'] = array(
				'description'       => __( 'Component type to report.', 'buddyboss' ),
				'type'              => 'string',
				'required'          => true,
				'enum'              => array_keys( $content_types ),
				'sanitize_callback' => 'sanitize_key',
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
		return apply_filters( "bp_rest_moderation_report_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the query params for Moderation report collections.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';
		$params['context']['enum']    = array( 'view' );

		// Removing unused params.
		unset( $params['search'], $params['page'], $params['per_page'] );

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_moderation_report_collection_params', $params );
	}

	/**
	 * Get the plugin schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_moderation_report',
			'type'       => 'object',
			'properties' => array(
				'type'          => array(
					'context'     => array( 'view' ),
					'description' => __( 'Field type name.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'name'          => array(
					'context'     => array( 'view' ),
					'description' => __( 'Name of the field.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'label'         => array(
					'context'     => array( 'view' ),
					'description' => __( 'Label of the field.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'description'   => array(
					'context'     => array( 'view' ),
					'description' => __( 'Field explanation to understand the purpose.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'is_required'   => array(
					'context'     => array( 'view' ),
					'description' => __( 'Check the field if required or not.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'boolean',
				),
				'options'       => array(
					'context'     => array( 'view' ),
					'description' => __( 'Options set for the fields.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'object',
				),
				'id'            => array(
					'context'     => array( 'edit' ),
					'description' => __( 'A unique numeric ID for the moderation.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'report_id'     => array(
					'context'     => array( 'edit' ),
					'description' => __( 'A unique numeric ID for the moderation report.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'blog_id'       => array(
					'context'     => array( 'edit' ),
					'description' => __( 'Current Site ID.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'user_id'       => array(
					'context'     => array( 'edit' ),
					'description' => __( 'Reported user ID.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'item_id'       => array(
					'context'     => array( 'edit' ),
					'description' => __( 'Reported Item ID.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'item_type'     => array(
					'context'     => array( 'edit' ),
					'description' => __( 'Reported Item Type.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'content'       => array(
					'context'     => array( 'edit' ),
					'description' => __( 'The report description.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'category_id'   => array(
					'context'     => array( 'edit' ),
					'description' => __( 'Report Category ID.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'date_created'  => array(
					'context'     => array( 'edit' ),
					'description' => __( 'Report created date.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'last_updated'  => array(
					'context'     => array( 'edit' ),
					'description' => __( 'Report updated date.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'hide_sitewide' => array(
					'context'     => array( 'edit' ),
					'description' => __( 'Whether it is hidden of all or not.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'boolean',
				),
				'count'         => array(
					'context'     => array( 'edit' ),
					'description' => __( 'Number of time item was reported.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
			),
		);

		/**
		 * Filters the moderation report schema.
		 *
		 * @param string $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_moderation_report_schema', $this->add_additional_fields_schema( $schema ) );
	}

}
