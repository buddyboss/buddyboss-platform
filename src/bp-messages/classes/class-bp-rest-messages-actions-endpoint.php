<?php
/**
 * BP REST: BP_REST_Messages_Actions_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Messages Actions endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Messages_Actions_Endpoint extends WP_REST_Controller {

	/**
	 * Reuse some parts of the BP_REST_Messages_Endpoint class.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_REST_Messages_Endpoint
	 */
	protected $message_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace         = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base         = buddypress()->messages->id;
		$this->message_endpoint = new BP_REST_Messages_Endpoint();
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/action/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id'     => array(
						'description' => __( 'ID of the Messages Thread.', 'buddyboss' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'action' => array(
						'description'       => __( 'Action name to perform on the message thread.', 'buddyboss' ),
						'type'              => 'string',
						'required'          => true,
						'enum'              => array(
							'delete_messages',
							'hide_thread',
							'unread',
						),
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => 'rest_validate_request_arg',
					),
					'value'  => array(
						'description'       => __( 'Value for the action on message thread.', 'buddyboss' ),
						'type'              => 'boolean',
						'sanitize_callback' => 'rest_sanitize_boolean',
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'action_items' ),
					'permission_callback' => array( $this, 'action_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this->message_endpoint, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Perform Action on the Message Thread.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/messages/action/:id Thread Action
	 * @apiName        GetBBThreadsAction
	 * @apiGroup       Messages
	 * @apiDescription Perform Action on the Message Thread.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id ID of the Messages Thread.
	 * @apiParam {String=delete_messages,hide_thread,unread} action Action name to perform on the message thread.
	 * @apiParam {Boolean} value Value for the action on message thread.
	 */
	public function action_items( $request ) {
		$action    = $request->get_param( 'action' );
		$value     = $request->get_param( 'value' );
		$thread_id = $request->get_param( 'id' );

		switch ( $action ) {
			case 'delete_messages':
				$retval = $this->rest_delete_messages( $thread_id, $value );
				break;
			case 'hide_thread':
				$retval = $this->rest_hide_thread( $thread_id, $value );
				break;
			case 'unread':
				$retval = $this->rest_unread_thread( $thread_id, $value );
				break;
		}

		if ( is_wp_error( $retval ) ) {
			return $retval;
		}

		// Clear recipients cache after update hidden property.
		wp_cache_delete( 'thread_recipients_' . $thread_id, 'bp_messages' );

		$thread = new BP_Messages_Thread( $thread_id );

		$retval = $this->prepare_response_for_collection(
			$this->message_endpoint->prepare_item_for_response( $thread, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a thread is fetched via the REST API.
		 *
		 * @param BP_Messages_Thread $thread  Thread object.
		 * @param WP_REST_Response   $retval  The response data.
		 * @param WP_REST_Request    $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_messages_action_items', $thread, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to thread items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function action_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to perform action on messages.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$thread = $this->message_endpoint->get_thread_object( $request['id'] );

		if ( is_user_logged_in() && ! empty( $thread->thread_id ) ) {
			$retval = true;
		}

		if ( true === $retval && bp_current_user_can( 'bp_moderate' ) ) {
			$retval = true;
		} else {
			$id = messages_check_thread_access( $thread->thread_id );
			if ( true === $retval && is_null( $id ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to see this thread.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}

			if ( true === $retval ) {
				$retval = true;
			}
		}

		/**
		 * Filter the messages `action_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_messages_action_items_permissions_check', $retval, $request );
	}

	/**
	 * Delete thread messages by logged in users.
	 *
	 * @param integer $thread_id ID of the Messages Thread.
	 * @param boolen  $value     Action value.
	 *
	 * @return bool|void
	 */
	protected function rest_delete_messages( $thread_id, $value ) {

		if ( empty( $value ) ) {
			return;
		}

		return messages_delete_thread( $thread_id );
	}

	/**
	 * Hide unhide message thread based on the logged in user.
	 * - from bp_nouveau_ajax_hide_thread();
	 *
	 * @param integer $thread_id ID of the Messages Thread.
	 * @param boolen  $value     Action value.
	 *
	 * @return bool|void
	 */
	protected function rest_hide_thread( $thread_id, $value ) {
		global $bp, $wpdb;

		if ( empty( $value ) ) {
			// phpcs:ignore
			$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_hidden = %d WHERE thread_id = %d AND user_id = %d", 0, (int) $thread_id, bp_loggedin_user_id() ) );

			/**
			 * Fires when messages thread was marked as read.
			 *
			 * @param int $thread_id The message thread ID.
			 * @param int $user_id   Logged in user ID.
			 */
			do_action( 'bb_messages_thread_unarchived', $thread_id, bp_loggedin_user_id() );
			return true;
		} elseif ( ! empty( $value ) ) {
			// phpcs:ignore
			$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_hidden = %d WHERE thread_id = %d AND user_id = %d", 1, (int) $thread_id, bp_loggedin_user_id() ) );

			/**
			 * Fires when messages thread was marked as read.
			 *
			 * @param int $thread_id The message thread ID.
			 * @param int $user_id   Logged in user ID.
			 */
			do_action( 'bb_messages_thread_archived', $thread_id, bp_loggedin_user_id() );
			return true;
		}

		return false;
	}

	/**
	 * Read/Unread message thread based on the logged in user.
	 * - from bp_nouveau_ajax_readunread_thread_messages();
	 *
	 * @param integer $thread_id ID of the Messages Thread.
	 * @param boolen  $value     Action value.
	 *
	 * @return bool|void
	 */
	protected function rest_unread_thread( $thread_id, $value ) {
		if ( empty( $value ) ) {
			messages_mark_thread_read( $thread_id );
			return true;
		} elseif ( ! empty( $value ) ) {
			messages_mark_thread_unread( $thread_id );
		}

		return false;
	}
}
