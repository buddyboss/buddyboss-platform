<?php
/**
 * BuddyBoss REST API Activity Controller
 *
 * Handles REST API requests for Activity list and management.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Activity REST Controller Class
 *
 * @since BuddyBoss 3.0.0
 */
class BB_REST_Activity_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base  = 'activity';
	}

	/**
	 * Register routes.
	 *
	 * @since BuddyBoss 3.0.0
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
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => __( 'Activity ID.', 'buddyboss' ),
							'type'        => 'integer',
							'required'    => true,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Check if user can view activities.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to view activities.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get activities.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$args = array(
			'per_page'    => isset( $request['per_page'] ) ? (int) $request['per_page'] : 20,
			'page'        => isset( $request['page'] ) ? (int) $request['page'] : 1,
			'search_terms' => isset( $request['search'] ) ? sanitize_text_field( $request['search'] ) : '',
			'sort'        => isset( $request['orderby'] ) ? sanitize_text_field( $request['orderby'] ) : 'date',
			'order'       => isset( $request['order'] ) ? strtoupper( sanitize_text_field( $request['order'] ) ) : 'DESC',
		);

		// Filter by type if provided.
		if ( isset( $request['type'] ) ) {
			$args['filter']['action'] = sanitize_text_field( $request['type'] );
		}

		// Filter by user if provided.
		if ( isset( $request['user_id'] ) ) {
			$args['filter']['user_id'] = (int) $request['user_id'];
		}

		// Filter by component if provided.
		if ( isset( $request['component'] ) ) {
			$args['filter']['object'] = sanitize_text_field( $request['component'] );
		}

		// Filter by status (spam, hidden, etc.).
		if ( isset( $request['status'] ) ) {
			$args['filter']['status'] = sanitize_text_field( $request['status'] );
		}

		// Apply filters.
		$args = apply_filters( 'buddyboss_rest_activity_query_args', $args, $request );

		// Get activities.
		$activities = bp_activity_get( $args );

		$formatted_activities = array();
		if ( ! empty( $activities['activities'] ) ) {
			foreach ( $activities['activities'] as $activity ) {
				$formatted_activities[] = $this->prepare_item_for_response( $activity, $request );
			}
		}

		return BB_REST_Response::paginated(
			$formatted_activities,
			(int) $activities['total'],
			$args['page'],
			$args['per_page']
		);
	}

	/**
	 * Check if user can view activity.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to view this activity.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get single activity.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$activity_id = (int) $request['id'];
		$activity    = new BP_Activity_Activity( $activity_id );

		if ( empty( $activity->id ) ) {
			return BB_REST_Response::not_found( __( 'Activity not found.', 'buddyboss' ) );
		}

		return BB_REST_Response::success( $this->prepare_item_for_response( $activity, $request ) );
	}

	/**
	 * Check if user can update activity.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to update activities.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Update activity.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$activity_id = (int) $request['id'];
		$activity    = new BP_Activity_Activity( $activity_id );

		if ( empty( $activity->id ) ) {
			return BB_REST_Response::not_found( __( 'Activity not found.', 'buddyboss' ) );
		}

		// Update activity content if provided.
		if ( isset( $request['content'] ) ) {
			$activity->content = wp_kses_post( $request['content'] );
		}

		// Update activity status if provided.
		if ( isset( $request['is_spam'] ) ) {
			$activity->is_spam = (bool) $request['is_spam'];
		}

		// Apply filters before saving.
		$activity = apply_filters( 'buddyboss_rest_activity_update_item', $activity, $request );

		// Save activity.
		if ( ! $activity->save() ) {
			return BB_REST_Response::error( __( 'Failed to update activity.', 'buddyboss' ) );
		}

		return BB_REST_Response::success( $this->prepare_item_for_response( $activity, $request ) );
	}

	/**
	 * Check if user can delete activity.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to delete activities.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Delete activity.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$activity_id = (int) $request['id'];
		$activity    = new BP_Activity_Activity( $activity_id );

		if ( empty( $activity->id ) ) {
			return BB_REST_Response::not_found( __( 'Activity not found.', 'buddyboss' ) );
		}

		// Delete activity.
		if ( ! bp_activity_delete( array( 'id' => $activity_id ) ) ) {
			return BB_REST_Response::error( __( 'Failed to delete activity.', 'buddyboss' ) );
		}

		return BB_REST_Response::success( array( 'deleted' => true, 'id' => $activity_id ) );
	}

	/**
	 * Prepare activity for response.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param BP_Activity_Activity $activity Activity object.
	 * @param WP_REST_Request      $request Request object.
	 * @return array
	 */
	public function prepare_item_for_response( $activity, $request ) {
		$data = array(
			'id'           => $activity->id,
			'user_id'      => $activity->user_id,
			'user_name'    => bp_core_get_user_displayname( $activity->user_id ),
			'user_avatar'  => bp_core_fetch_avatar(
				array(
					'item_id' => $activity->user_id,
					'type'    => 'thumb',
					'html'    => false,
				)
			),
			'content'      => $activity->content,
			'component'    => $activity->component,
			'type'         => $activity->type,
			'action'       => $activity->action,
			'item_id'      => $activity->item_id,
			'secondary_item_id' => $activity->secondary_item_id,
			'date_recorded' => $activity->date_recorded,
			'date_recorded_formatted' => bp_core_time_since( $activity->date_recorded ),
			'is_spam'      => (bool) $activity->is_spam,
			'is_hidden'    => (bool) $activity->hide_sitewide,
			'comment_count' => (int) $activity->comment_count,
			'permalink'    => bp_activity_get_permalink( $activity->id ),
		);

		// Apply filters.
		$data = apply_filters( 'buddyboss_rest_activity_prepare_item', $data, $activity, $request );

		return $data;
	}

	/**
	 * Get collection parameters.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'buddyboss' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'buddyboss' ),
				'type'              => 'integer',
				'default'           => 20,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'search'   => array(
				'description'       => __( 'Search query string.', 'buddyboss' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'orderby'  => array(
				'description'       => __( 'Order by field.', 'buddyboss' ),
				'type'              => 'string',
				'default'           => 'date',
				'enum'              => array( 'date', 'id', 'user_id' ),
			),
			'order'    => array(
				'description'       => __( 'Order direction.', 'buddyboss' ),
				'type'              => 'string',
				'default'           => 'DESC',
				'enum'              => array( 'ASC', 'DESC' ),
			),
			'type'     => array(
				'description'       => __( 'Filter by activity type.', 'buddyboss' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'user_id'  => array(
				'description'       => __( 'Filter by user ID.', 'buddyboss' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'component' => array(
				'description'       => __( 'Filter by component.', 'buddyboss' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'status'   => array(
				'description'       => __( 'Filter by status (spam, hidden, etc.).', 'buddyboss' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}
}
