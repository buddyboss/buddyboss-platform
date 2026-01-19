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
			'/' . $this->rest_base . '/types',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_types' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
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
	 * Get activity types.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_types( $request ) {
		$types = array();

		// Get all registered activity actions.
		if ( function_exists( 'bp_activity_get_actions' ) ) {
			$actions = bp_activity_get_actions();
			
			foreach ( $actions as $component => $component_actions ) {
				foreach ( $component_actions as $action_key => $action_data ) {
					$types[] = array(
						'key'       => $action_key,
						'label'     => $action_data['value'],
						'component' => $component,
					);
				}
			}
		}

		// Remove mis-named activity type from before BP 1.6.
		$types = array_filter( $types, function( $type ) {
			return $type['key'] !== 'friends_register_activity_action';
		} );

		// Sort by label.
		usort( $types, function( $a, $b ) {
			return strcasecmp( $a['label'], $b['label'] );
		} );

		return BB_REST_Response::success( array_values( $types ) );
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
		$per_page = isset( $request['per_page'] ) ? (int) $request['per_page'] : 20;
		$page     = isset( $request['page'] ) ? (int) $request['page'] : 1;

		// Determine spam filter - match old implementation behavior
		$spam = 'ham_only'; // Default to non-spam activities
		if ( ! empty( $request['spam'] ) ) {
			$spam_param = sanitize_text_field( $request['spam'] );
			if ( 'spam' === $spam_param || 'spam_only' === $spam_param ) {
				$spam = 'spam_only';
			} elseif ( 'all' === $spam_param ) {
				$spam = 'all';
			}
		}

		$args = array(
			'per_page'         => $per_page,
			'page'             => $page,
			'sort'             => isset( $request['order'] ) ? strtoupper( sanitize_text_field( $request['order'] ) ) : 'DESC',
			'count_total'      => 'count_query', // Match old implementation
			'show_hidden'      => true, // Show all activities including hidden ones for admin
			'spam'             => $spam,
			'display_comments' => 'stream', // Match old implementation - include activity comments
			'status'           => false, // Show all statuses (published, scheduled, draft) for admin
			'privacy'          => false, // Show all privacy levels for admin
			'scope'            => false, // No scope restriction for admin
		);

		// Search terms
		if ( ! empty( $request['search'] ) ) {
			$args['search_terms'] = sanitize_text_field( $request['search'] );
		}

		// Filter by type/action if provided.
		if ( ! empty( $request['type'] ) ) {
			$args['filter'] = array(
				'action' => sanitize_text_field( $request['type'] ),
			);
		}

		// Filter by user if provided.
		if ( ! empty( $request['user_id'] ) ) {
			if ( ! isset( $args['filter'] ) ) {
				$args['filter'] = array();
			}
			$args['filter']['user_id'] = (int) $request['user_id'];
		}

		// Filter by component if provided.
		if ( ! empty( $request['component'] ) ) {
			if ( ! isset( $args['filter'] ) ) {
				$args['filter'] = array();
			}
			$args['filter']['object'] = sanitize_text_field( $request['component'] );
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

		// Get total count
		$total = isset( $activities['total'] ) ? (int) $activities['total'] : count( $formatted_activities );

		return BB_REST_Response::paginated(
			$formatted_activities,
			$total,
			$page,
			$per_page
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

		// Update activity action if provided.
		if ( isset( $request['action'] ) ) {
			$activity->action = wp_kses_post( $request['action'] );
		}

		// Update activity title if provided.
		if ( isset( $request['title'] ) ) {
			// Title is stored in post_title property
			if ( property_exists( $activity, 'post_title' ) ) {
				$activity->post_title = sanitize_text_field( $request['title'] );
			}
		}

		// Update activity content if provided.
		if ( isset( $request['content'] ) ) {
			$activity->content = wp_kses_post( $request['content'] );
		}

		// Update primary link if provided.
		if ( isset( $request['primary_link'] ) ) {
			$activity->primary_link = esc_url_raw( $request['primary_link'] );
		}

		// Update activity type if provided.
		if ( isset( $request['type'] ) ) {
			$activity->type = sanitize_text_field( $request['type'] );
		}

		// Update user ID if provided.
		if ( isset( $request['user_id'] ) ) {
			$activity->user_id = absint( $request['user_id'] );
		}

		// Update item ID if provided.
		if ( isset( $request['item_id'] ) ) {
			$activity->item_id = absint( $request['item_id'] );
		}

		// Update secondary item ID if provided.
		if ( isset( $request['secondary_item_id'] ) ) {
			$activity->secondary_item_id = absint( $request['secondary_item_id'] );
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
		// Get user data
		$user_id   = (int) $activity->user_id;
		$user_data = get_userdata( $user_id );
		
		// Get user display name - try multiple methods
		$user_name = '';
		if ( $user_data ) {
			$user_name = $user_data->display_name;
		}
		if ( empty( $user_name ) && function_exists( 'bp_core_get_user_displayname' ) ) {
			$user_name = bp_core_get_user_displayname( $user_id );
		}
		if ( empty( $user_name ) && $user_data ) {
			$user_name = $user_data->user_login;
		}

		// Get user avatar - try multiple methods
		$user_avatar = '';
		
		// Method 1: Use bp_core_fetch_avatar with html=false to get URL
		if ( function_exists( 'bp_core_fetch_avatar' ) ) {
			$avatar_result = bp_core_fetch_avatar(
				array(
					'item_id' => $user_id,
					'object'  => 'user',
					'type'    => 'thumb',
					'html'    => false,
				)
			);
			
			// Check if it returned a URL (string starting with http)
			if ( ! empty( $avatar_result ) && is_string( $avatar_result ) ) {
				if ( strpos( $avatar_result, 'http' ) === 0 ) {
					$user_avatar = $avatar_result;
				} elseif ( strpos( $avatar_result, '<img' ) !== false ) {
					// If it returned HTML despite html=false, extract the src
					preg_match( '/src=["\']([^"\']+)["\']/', $avatar_result, $matches );
					if ( ! empty( $matches[1] ) ) {
						$user_avatar = $matches[1];
					}
				}
			}
		}
		
		// Method 2: Fallback to get_avatar_url
		if ( empty( $user_avatar ) ) {
			$user_avatar = get_avatar_url( $user_id, array( 'size' => 50 ) );
		}
		
		// Method 3: Fallback using user email
		if ( empty( $user_avatar ) && $user_data && ! empty( $user_data->user_email ) ) {
			$user_avatar = get_avatar_url( $user_data->user_email, array( 'size' => 50 ) );
		}

		// Parse the action text (strip HTML and extract the action description)
		$action_text = '';
		if ( ! empty( $activity->action ) ) {
			// Strip HTML and get plain text action
			$action_text = wp_strip_all_tags( $activity->action );
			// Remove the user name from the beginning if present
			if ( ! empty( $user_name ) && strpos( $action_text, $user_name ) === 0 ) {
				$action_text = trim( substr( $action_text, strlen( $user_name ) ) );
			}
		}

		// Get group name if this is a group activity
		$group_name = '';
		if ( 'groups' === $activity->component && ! empty( $activity->item_id ) && function_exists( 'groups_get_group' ) ) {
			$group = groups_get_group( $activity->item_id );
			if ( ! empty( $group->name ) ) {
				$group_name = $group->name;
			}
		}

		// Format date
		$date_formatted = '';
		if ( ! empty( $activity->date_recorded ) ) {
			if ( function_exists( 'bp_core_time_since' ) ) {
				$date_formatted = bp_core_time_since( $activity->date_recorded );
			} else {
				$date_formatted = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $activity->date_recorded ) );
			}
		}

		$data = array(
			'id'                      => (int) $activity->id,
			'user_id'                 => $user_id,
			'user_name'               => $user_name,
			'user_avatar'             => $user_avatar,
			'user_link'               => function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $user_id ) : get_author_posts_url( $user_id ),
			'title'                   => isset( $activity->post_title ) ? $activity->post_title : '',
			'content'                 => $activity->content,
			'primary_link'            => isset( $activity->primary_link ) ? $activity->primary_link : '',
			'component'               => $activity->component,
			'type'                    => $activity->type,
			'action'                  => $activity->action,
			'action_text'             => $action_text,
			'group_name'              => $group_name,
			'item_id'                 => (int) $activity->item_id,
			'secondary_item_id'       => (int) $activity->secondary_item_id,
			'date_recorded'           => $activity->date_recorded,
			'date_recorded_formatted' => $date_formatted,
			'is_spam'                 => (bool) $activity->is_spam,
			'hide_sitewide'           => (bool) $activity->hide_sitewide,
			'comment_count'           => isset( $activity->comment_count ) ? (int) $activity->comment_count : 0,
			'permalink'               => function_exists( 'bp_activity_get_permalink' ) ? bp_activity_get_permalink( $activity->id ) : '',
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
				'sanitize_callback' => 'sanitize_text_field',
			),
			'order'    => array(
				'description'       => __( 'Order direction.', 'buddyboss' ),
				'type'              => 'string',
				'default'           => 'desc',
				'enum'              => array( 'asc', 'desc', 'ASC', 'DESC' ),
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
