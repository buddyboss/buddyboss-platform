<?php
/**
 * BuddyBoss REST API Groups Controller
 *
 * Handles REST API requests for Groups list and management.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Groups REST Controller Class
 *
 * @since BuddyBoss 3.0.0
 */
class BB_REST_Groups_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base  = 'groups';
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
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
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
							'description' => __( 'Group ID.', 'buddyboss' ),
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

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/members',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_members' ),
					'permission_callback' => array( $this, 'get_members_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'add_member' ),
					'permission_callback' => array( $this, 'add_member_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Check if user can view groups.
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
				__( 'Sorry, you are not allowed to view groups.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get groups.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$args = array(
			'per_page'         => isset( $request['per_page'] ) ? (int) $request['per_page'] : 20,
			'page'             => isset( $request['page'] ) ? (int) $request['page'] : 1,
			'search_terms'     => isset( $request['search'] ) ? sanitize_text_field( $request['search'] ) : '',
			'orderby'          => isset( $request['orderby'] ) ? sanitize_text_field( $request['orderby'] ) : 'date_created',
			'order'            => isset( $request['order'] ) ? strtoupper( sanitize_text_field( $request['order'] ) ) : 'DESC',
			'show_hidden'      => true, // Show hidden groups for admin.
			'populate_extras'  => true, // Populate extra data like member count.
		);

		// Filter by status if provided.
		if ( ! empty( $request['status'] ) ) {
			$args['status'] = array( sanitize_text_field( $request['status'] ) );
		} else {
			// Show all statuses for admin.
			$args['status'] = array( 'public', 'private', 'hidden' );
		}

		// Filter by type if provided.
		if ( isset( $request['type'] ) ) {
			$args['group_type'] = sanitize_text_field( $request['type'] );
		}

		// Filter by parent if provided (for hierarchies).
		if ( isset( $request['parent_id'] ) ) {
			$args['parent_id'] = (int) $request['parent_id'];
		}

		// Filter by user if provided.
		if ( isset( $request['user_id'] ) ) {
			$args['user_id'] = (int) $request['user_id'];
		}

		// Apply filters.
		$args = apply_filters( 'buddyboss_rest_groups_query_args', $args, $request );

		// Get groups.
		$groups = groups_get_groups( $args );

		$formatted_groups = array();
		if ( ! empty( $groups['groups'] ) ) {
			foreach ( $groups['groups'] as $group ) {
				$formatted_groups[] = $this->prepare_item_for_response( $group, $request );
			}
		}

		return BB_REST_Response::paginated(
			$formatted_groups,
			(int) $groups['total'],
			$args['page'],
			$args['per_page']
		);
	}

	/**
	 * Check if user can create groups.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to create groups.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Create group.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		// Validate required fields.
		if ( empty( $request['name'] ) ) {
			return BB_REST_Response::error( __( 'Group name is required.', 'buddyboss' ) );
		}

		$group_data = array(
			'name'         => sanitize_text_field( $request['name'] ),
			'description'  => isset( $request['description'] ) ? wp_kses_post( $request['description'] ) : '',
			'status'       => isset( $request['status'] ) ? sanitize_text_field( $request['status'] ) : 'public',
			'creator_id'   => get_current_user_id(),
		);

		// Set group type if provided.
		if ( isset( $request['group_type'] ) ) {
			$group_data['types'] = array( sanitize_text_field( $request['group_type'] ) );
		}

		// Set parent if provided (for hierarchies).
		if ( isset( $request['parent_id'] ) ) {
			$group_data['parent_id'] = (int) $request['parent_id'];
		}

		// Create group.
		$group_id = groups_create_group( $group_data );

		if ( ! $group_id ) {
			return BB_REST_Response::error( __( 'Failed to create group.', 'buddyboss' ) );
		}

		// Add members if provided.
		if ( isset( $request['invite_members'] ) && is_array( $request['invite_members'] ) ) {
			foreach ( $request['invite_members'] as $user_id ) {
				groups_join_group( $group_id, (int) $user_id );
			}
		}

		$group = groups_get_group( $group_id );

		return BB_REST_Response::success( $this->prepare_item_for_response( $group, $request ) );
	}

	/**
	 * Check if user can view group.
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
				__( 'Sorry, you are not allowed to view this group.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get single group.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$group_id = (int) $request['id'];
		$group    = groups_get_group( $group_id );

		if ( empty( $group->id ) ) {
			return BB_REST_Response::not_found( __( 'Group not found.', 'buddyboss' ) );
		}

		return BB_REST_Response::success( $this->prepare_item_for_response( $group, $request ) );
	}

	/**
	 * Check if user can update group.
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
				__( 'Sorry, you are not allowed to update groups.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Update group.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$group_id = (int) $request['id'];
		$group    = groups_get_group( $group_id );

		if ( empty( $group->id ) ) {
			return BB_REST_Response::not_found( __( 'Group not found.', 'buddyboss' ) );
		}

		$group_data = array();

		// Update name if provided.
		if ( isset( $request['name'] ) ) {
			$group_data['name'] = sanitize_text_field( $request['name'] );
		}

		// Update description if provided.
		if ( isset( $request['description'] ) ) {
			$group_data['description'] = wp_kses_post( $request['description'] );
		}

		// Update status if provided.
		if ( isset( $request['status'] ) ) {
			$group_data['status'] = sanitize_text_field( $request['status'] );
		}

		// Update group type if provided.
		if ( isset( $request['group_type'] ) ) {
			$group_data['types'] = array( sanitize_text_field( $request['group_type'] ) );
		}

		// Update parent if provided.
		if ( isset( $request['parent_id'] ) ) {
			$group_data['parent_id'] = (int) $request['parent_id'];
		}

		// Apply filters before saving.
		$group_data = apply_filters( 'buddyboss_rest_groups_update_item', $group_data, $request );

		// Update group.
		if ( ! empty( $group_data ) ) {
			$group_data['group_id'] = $group_id;
			if ( ! groups_create_group( $group_data ) ) {
				return BB_REST_Response::error( __( 'Failed to update group.', 'buddyboss' ) );
			}
		}

		$group = groups_get_group( $group_id );

		return BB_REST_Response::success( $this->prepare_item_for_response( $group, $request ) );
	}

	/**
	 * Check if user can delete group.
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
				__( 'Sorry, you are not allowed to delete groups.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Delete group.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$group_id = (int) $request['id'];
		$group    = groups_get_group( $group_id );

		if ( empty( $group->id ) ) {
			return BB_REST_Response::not_found( __( 'Group not found.', 'buddyboss' ) );
		}

		// Delete group.
		if ( ! groups_delete_group( $group_id ) ) {
			return BB_REST_Response::error( __( 'Failed to delete group.', 'buddyboss' ) );
		}

		return BB_REST_Response::success( array( 'deleted' => true, 'id' => $group_id ) );
	}

	/**
	 * Check if user can view group members.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function get_members_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to view group members.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get group members.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_members( $request ) {
		$group_id = (int) $request['id'];
		$group    = groups_get_group( $group_id );

		if ( empty( $group->id ) ) {
			return BB_REST_Response::not_found( __( 'Group not found.', 'buddyboss' ) );
		}

		$per_page = isset( $request['per_page'] ) ? (int) $request['per_page'] : 20;
		$page     = isset( $request['page'] ) ? (int) $request['page'] : 1;

		$members = groups_get_group_members(
			array(
				'group_id'            => $group_id,
				'per_page'            => $per_page,
				'page'                => $page,
				'exclude_admins_mods' => false,
			)
		);

		$formatted_members = array();
		if ( ! empty( $members['members'] ) ) {
			foreach ( $members['members'] as $member ) {
				$formatted_members[] = array(
					'id'           => $member->ID,
					'name'         => bp_core_get_user_displayname( $member->ID ),
					'avatar'       => bp_core_fetch_avatar(
						array(
							'item_id' => $member->ID,
							'type'    => 'thumb',
							'html'    => false,
						)
					),
					'is_admin'     => groups_is_user_admin( $group_id, $member->ID ),
					'is_mod'       => groups_is_user_mod( $group_id, $member->ID ),
					'date_joined'  => groups_get_membermeta( $member->ID, 'date_joined', true ),
				);
			}
		}

		return BB_REST_Response::paginated(
			$formatted_members,
			(int) $members['count'],
			$page,
			$per_page
		);
	}

	/**
	 * Check if user can add group members.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function add_member_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to add group members.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Add member to group.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function add_member( $request ) {
		$group_id = (int) $request['id'];
		$user_id  = isset( $request['user_id'] ) ? (int) $request['user_id'] : 0;

		if ( ! $user_id ) {
			return BB_REST_Response::error( __( 'User ID is required.', 'buddyboss' ) );
		}

		$group = groups_get_group( $group_id );

		if ( empty( $group->id ) ) {
			return BB_REST_Response::not_found( __( 'Group not found.', 'buddyboss' ) );
		}

		// Add member to group.
		if ( ! groups_join_group( $group_id, $user_id ) ) {
			return BB_REST_Response::error( __( 'Failed to add member to group.', 'buddyboss' ) );
		}

		return BB_REST_Response::success( array( 'added' => true, 'user_id' => $user_id ) );
	}

	/**
	 * Prepare group for response.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param BP_Groups_Group $group Group object.
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	public function prepare_item_for_response( $group, $request ) {
		// Get member count - it may be in different properties depending on how groups_get_groups() was called.
		$member_count = 0;
		if ( isset( $group->total_member_count ) ) {
			$member_count = (int) $group->total_member_count;
		} elseif ( function_exists( 'groups_get_total_member_count' ) ) {
			$member_count = (int) groups_get_total_member_count( $group->id );
		}

		// Get last activity.
		$last_activity = ! empty( $group->last_activity ) ? $group->last_activity : $group->date_created;

		$data = array(
			'id'                     => $group->id,
			'name'                   => $group->name,
			'slug'                   => $group->slug,
			'description'            => $group->description,
			'status'                 => $group->status,
			'type'                   => bp_groups_get_group_type( $group->id, false ),
			'parent_id'              => isset( $group->parent_id ) ? (int) $group->parent_id : 0,
			'member_count'           => $member_count,
			'avatar'                 => bp_core_fetch_avatar(
				array(
					'item_id' => $group->id,
					'object'  => 'group',
					'type'    => 'thumb',
					'html'    => false,
				)
			),
			'cover_image'            => bp_attachments_get_attachment(
				array(
					'item_id'    => $group->id,
					'object_dir' => 'groups',
					'type'       => 'cover-image',
				)
			),
			'date_created'           => $group->date_created,
			'date_created_formatted' => bp_core_time_since( $group->date_created ),
			'last_activity'          => $last_activity,
			'last_activity_formatted' => bp_core_time_since( $last_activity ),
			'permalink'              => bp_get_group_permalink( $group ),
		);

		// Apply filters.
		$data = apply_filters( 'buddyboss_rest_groups_prepare_item', $data, $group, $request );

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
				'default'           => 'date_created',
				'enum'              => array( 'date_created', 'name', 'last_activity', 'total_member_count' ),
			),
			'order'    => array(
				'description'       => __( 'Order direction.', 'buddyboss' ),
				'type'              => 'string',
				'default'           => 'DESC',
				'enum'              => array( 'ASC', 'DESC' ),
			),
			'status'   => array(
				'description'       => __( 'Filter by status (public, private, hidden).', 'buddyboss' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'type'     => array(
				'description'       => __( 'Filter by group type.', 'buddyboss' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'parent_id' => array(
				'description'       => __( 'Filter by parent group ID (for hierarchies).', 'buddyboss' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'user_id'  => array(
				'description'       => __( 'Filter by user ID (groups user is member of).', 'buddyboss' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
		);
	}
}
