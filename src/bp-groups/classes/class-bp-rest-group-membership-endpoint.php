<?php
/**
 * BP REST: BP_REST_Group_Membership_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Group membership endpoints.
 *
 * Use /groups/{group_id}/members
 * Use /groups/{group_id}/members/{user_id}
 *
 * @since 0.1.0
 */
class BP_REST_Group_Membership_Endpoint extends WP_REST_Controller {

	/**
	 * Reuse some parts of the BP_REST_Groups_Endpoint class.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_REST_Groups_Endpoint
	 */
	protected $groups_endpoint;

	/**
	 * Reuse some parts of the BP_REST_Members_Endpoint class.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_REST_Members_Endpoint
	 */
	protected $members_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace        = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base        = buddypress()->groups->id;
		$this->groups_endpoint  = new BP_REST_Groups_Endpoint();
		$this->members_endpoint = new BP_REST_Members_Endpoint();
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<group_id>[\d]+)/members',
			array(
				'args'   => array(
					'group_id' => array(
						'description' => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
						'type'        => 'integer',
					),
				),
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
					'args'                => $this->get_endpoint_args_for_method( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<group_id>[\d]+)/members/(?P<user_id>[\d]+)',
			array(
				'args'   => array(
					'group_id' => array(
						'description' => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
						'type'        => 'integer',
					),
					'user_id'  => array(
						'description' => __( 'A unique numeric ID for the Group Member.', 'buddyboss' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_method( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_method( WP_REST_Server::DELETABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve group members.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/groups/:group_id/members/ Get Group Members
	 * @apiName        GetBBGroupsMembers
	 * @apiGroup       Groups
	 * @apiDescription Retrieve group Members.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} group_id A unique numeric ID for the Group.
	 * @apiParam {Number} [page=1] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {String} [search] Limit results to those matching a string.
	 * @apiParam {String=last_joined,first_joined,alphabetical,group_activity,group_role} [status=last_joined] Sort the order of results by the status of the group members.
	 * @apiParam {Array=admin,mod,member,banned} [roles] Ensure result set includes specific group roles.
	 * @apiParam {Array} [exclude] Ensure result set excludes specific member IDs.
	 * @apiParam {Boolean} [exclude_admins=true] Whether results should exclude group admins and mods.
	 * @apiParam {Boolean} [exclude_banned=true] Whether results should exclude banned group members.
	 * @apiParam {String=invite,invite-friends,invited,message} [scope] Limit result set to items with a specific scope.
	 */
	public function get_items( $request ) {
		$group = $this->groups_endpoint->get_group_object( $request['group_id'] );

		$args = array(
			'group_id'            => $group->id,
			'group_role'          => $request['roles'],
			'type'                => $request['status'],
			'per_page'            => $request['per_page'],
			'page'                => $request['page'],
			'search_terms'        => $request['search'],
			'exclude'             => $request['exclude'],
			'exclude_admins_mods' => (bool) $request['exclude_admins'],
			'exclude_banned'      => (bool) $request['exclude_banned'],
		);

		if ( empty( $args['exclude'] ) ) {
			$args['exclude'] = false;
		}

		if ( is_null( $args['search_terms'] ) ) {
			$args['search_terms'] = false;
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array $args Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_group_members_get_items_query_args', $args, $request );

		$retval = array();

		if ( ! empty( $request['scope'] ) && 'message' !== $request['scope'] ) {

			$group_potential_invites = $this->bp_rest_get_group_potential_invites( $group, $request );

			if ( is_wp_error( $group_potential_invites ) ) {
				return $group_potential_invites;
			} else {
				$args['user_ids'] = $group_potential_invites;
				unset( $args['search_terms'] );
				unset( $args['include'] );
				unset( $args['exclude'] );
			}

			$args['type'] = 'alphabetical';

			// Actually, query it.
			$member_query = new BP_User_Query( $args );
			$members      = array_values( $member_query->results );

			$retval = array();
			foreach ( $members as $member ) {
				$retval[] = $this->prepare_response_for_collection(
					$this->members_endpoint->prepare_item_for_response( $member, $request )
				);
			}

			$response = rest_ensure_response( $retval );
			$response = bp_rest_response_add_total_headers( $response, $member_query->total_users, $args['per_page'] );

		} else {

			if ( ! empty( $request['scope'] ) && 'message' === $request['scope'] ) {
				/**
				 * Action to apply hook for the member list based on group access.
				 */
				do_action( 'bb_rest_before_get_group_members', $args, $request );
			}

			// Get our members.
			$members = groups_get_group_members( $args );

			foreach ( $members['members'] as $member ) {
				$retval[] = $this->prepare_response_for_collection(
					$this->prepare_item_for_response( $member, $request )
				);
			}

			$response = rest_ensure_response( $retval );
			$response = bp_rest_response_add_total_headers( $response, $members['count'], $args['per_page'] );
		}

		/**
		 * Fires after a list of group members are fetched via the REST API.
		 *
		 * @param array $members Fetched group members.
		 * @param BP_Groups_Group $group The group object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_group_members_get_items', $members, $group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to group members.
	 *
	 * We are using the same permissions check done on group access.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = $this->groups_endpoint->get_item_permissions_check( $request );

		/**
		 * Filter the group members `get_items` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_members_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Add member to a group.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api {POST} /wp-json/buddyboss/v1/groups/:group_id/members Add Group Member
	 * @apiName AddBBGroupsMembers
	 * @apiGroup Groups
	 * @apiDescription Add Member to a group.
	 * @apiVersion 1.0.0
	 * @apiPermission LoggedInUser
	 * @apiParam {Number} group_id A unique numeric ID for the Group.
	 * @apiParam {String=admin,mod,member} [role=member] Group role to assign the user to.
	 * @apiParam {Number} user_id A unique numeric ID for the Member to add to the Group.
	 */
	public function create_item( $request ) {
		$user  = bp_rest_get_user( $request['user_id'] );
		$group = $this->groups_endpoint->get_group_object( $request['group_id'] );

		if (
			! $request['context'] ||
			'view' === $request['context'] ||
			'public' === $group->status
		) {
			if ( ! groups_join_group( $group->id, $user->ID ) ) {
				return new WP_Error(
					'bp_rest_group_member_failed_to_join',
					__( 'Could not join the group.', 'buddyboss' ),
					array(
						'status' => 500,
					)
				);
			}

			// Get the group member.
			$group_member = new BP_Groups_Member( $user->ID, $group->id );
		} else {
			$role         = $request['role'];
			$group_id     = $group->id;
			$group_member = new BP_Groups_Member( $user->ID, $group_id );

			// Add member to the group.
			$group_member->group_id      = $group_id;
			$group_member->user_id       = $user->ID;
			$group_member->is_admin      = 0;
			$group_member->date_modified = bp_core_current_time();
			$group_member->is_confirmed  = 1;
			$saved                       = $group_member->save();

			if ( ! $saved ) {
				return new WP_Error(
					'bp_rest_group_member_failed_to_join',
					__( 'Could not add member to the group.', 'buddyboss' ),
					array(
						'status' => 500,
					)
				);
			}

			// If new role set, promote it too.
			if ( $saved && 'member' !== $role ) {
				// Make sure to update the group role.
				if ( groups_promote_member( $user->ID, $group_id, $role ) ) {
					$group_member = new BP_Groups_Member( $user->ID, $group_id );
				}
			}
		}

		// Setting context.
		$request->set_param( 'context', 'edit' );

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $group_member, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a member is added to a group via the REST API.
		 *
		 * @param WP_User $user The user.
		 * @param BP_Groups_Member $group_member The group member object.
		 * @param BP_Groups_Group $group The group object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_group_members_create_item', $user, $group_member, $group, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to join a group.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to join a group.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() || bp_current_user_can( 'bp_moderate' ) ) {
			$retval = true;
			$user   = bp_rest_get_user( $request['user_id'] );
			$group  = $this->groups_endpoint->get_group_object( $request['group_id'] );

			if ( ! $user instanceof WP_User ) {
				$retval = new WP_Error(
					'bp_rest_group_member_invalid_id',
					__( 'Invalid group member ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( ! $group instanceof BP_Groups_Group ) {
				$retval = new WP_Error(
					'bp_rest_group_invalid_id',
					__( 'Invalid group ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( ! bp_current_user_can( 'bp_moderate' ) ) {

				$loggedin_user_id = bp_loggedin_user_id();

				// Users may only freely join public groups.
				if (
					! bp_current_user_can( 'groups_join_group', array( 'group_id' => $group->id ) )
					|| groups_is_user_member( $loggedin_user_id, $group->id ) // As soon as they are not already members.
					|| groups_is_user_banned( $loggedin_user_id, $group->id ) // And as soon as they are not banned from it.
					|| $loggedin_user_id !== $user->ID // You can only add yourself to a group.
				) {
					$retval = new WP_Error(
						'bp_rest_group_member_failed_to_join',
						__( 'Could not join the group.', 'buddyboss' ),
						array(
							'status' => 500,
						)
					);
				}
			}
		}

		/**
		 * Filter the group members `create_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_members_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Update user status on a group (add, remove, promote, demote or ban).
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api {PATCH} /wp-json/buddyboss/v1/groups/:group_id/members/:user_id Update Group Member
	 * @apiName UpdateBBGroupsMembers
	 * @apiGroup Groups
	 * @apiDescription Update user status on a group (add, remove, promote, demote or ban).
	 * @apiVersion 1.0.0
	 * @apiPermission LoggedInUser
	 * @apiParam {Number} group_id A unique numeric ID for the Group.
	 * @apiParam {Number} user_id A unique numeric ID for the Group Member.
	 * @apiParam {String=admin,mod,member} [role=member] Group role to assign the user to.
	 * @apiParam {String=promote,demote,ban,unban} [action=promote] Group role to assign the user to.
	 */
	public function update_item( $request ) {
		$user         = bp_rest_get_user( $request['user_id'] );
		$group        = $this->groups_endpoint->get_group_object( $request['group_id'] );
		$action       = $request['action'];
		$role         = $request['role'];
		$group_id     = $group->id;
		$group_member = new BP_Groups_Member( $user->ID, $group_id );

		/**
		 * Fires before the promotion of a user to a new status.
		 *
		 * @param int    $group_id ID of the group being promoted in.
		 * @param int    $user_id  ID of the user being promoted.
		 * @param string $status   New status being promoted to.
		 */
		do_action( "groups_{$action}_member", $group_id, $user->ID, $role );

		if ( 'promote' === $action ) {
			if ( ! $group_member->promote( $role ) ) {
				return new WP_Error(
					'bp_rest_group_member_failed_to_promote',
					__( 'Could not promote member.', 'buddyboss' ),
					array(
						'status' => 500,
					)
				);
			}
		} elseif ( 'demote' === $action && 'member' !== $role ) {
			if ( ! $group_member->promote( $role ) ) {
				return new WP_Error(
					'bp_rest_group_member_failed_to_demote',
					__( 'Could not demote member.', 'buddyboss' ),
					array(
						'status' => 500,
					)
				);
			}
		} elseif ( in_array( $action, array( 'demote', 'ban', 'unban' ), true ) ) {
			if ( ! $group_member->$action() ) {
				$messages = array(
					'demote' => __( 'Could not demote member from the group.', 'buddyboss' ),
					'ban'    => __( 'Could not ban member from the group.', 'buddyboss' ),
					'unban'  => __( 'Could not unban member from the group.', 'buddyboss' ),
				);

				return new WP_Error(
					'bp_rest_group_member_failed_to_' . $action,
					$messages[ $action ],
					array(
						'status' => 500,
					)
				);
			}
		}

		$after_action = array(
			'promote' => 'promoted',
			'demote'  => 'demoted',
			'ban'     => 'banned',
			'unban'   => 'unbanned',
		);

		/**
		 * Fires after a group member has been updated.
		 *
		 * @param int $user_id  ID of the user being updated.
		 * @param int $group_id ID of the group.
		 */
		do_action( "groups_{$after_action[$action]}_member", $user->ID, $group_id );

		// Setting context.
		$request->set_param( 'context', 'edit' );

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $group_member, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a group member is updated via the REST API.
		 *
		 * @param WP_User $user The updated member.
		 * @param BP_Groups_Member $group_member The group member object.
		 * @param BP_Groups_Group $group The group object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_group_members_update_item', $user, $group_member, $group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a group member.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$error = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to perform this action.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$retval = $error;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to make an update.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		} else {
			$user             = bp_rest_get_user( $request['user_id'] );
			$loggedin_user_id = bp_loggedin_user_id();

			if ( ! $user instanceof WP_User ) {
				$retval = new WP_Error(
					'bp_rest_group_member_invalid_id',
					__( 'Invalid group member ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} else {
				$group = $this->groups_endpoint->get_group_object( $request['group_id'] );

				if ( ! $group instanceof BP_Groups_Group ) {
					$retval = new WP_Error(
						'bp_rest_group_invalid_id',
						__( 'Invalid group ID.', 'buddyboss' ),
						array(
							'status' => 404,
						)
					);
				} elseif ( bp_current_user_can( 'bp_moderate' ) ) {
					$retval = true;
				} elseif ( in_array( $request['action'], array( 'ban', 'unban', 'promote', 'demote' ), true ) ) {
					if ( groups_is_user_admin( $loggedin_user_id, $group->id ) || groups_is_user_mod( $loggedin_user_id, $group->id ) ) {
						if ( $loggedin_user_id !== $user->ID ) {
							$retval = true;
						} else {
							$group_admins = groups_get_group_admins( $group->id );

							if ( 1 !== count( $group_admins ) ) {
								$retval = true;
							} else {
								$retval = $error;
							}
						}
					} else {
						$messages = array(
							'ban'     => __( 'Sorry, you are not allowed to ban this group member.', 'buddyboss' ),
							'unban'   => __( 'Sorry, you are not allowed to unban this group member.', 'buddyboss' ),
							'promote' => __( 'Sorry, you are not allowed to promote this group member.', 'buddyboss' ),
							'demote'  => __( 'Sorry, you are not allowed to demote this group member.', 'buddyboss' ),
						);

						$retval = new WP_Error(
							'bp_rest_group_member_cannot_' . $request['action'],
							$messages[ $request['action'] ],
							array(
								'status' => rest_authorization_required_code(),
							)
						);
					}
				}

				if ( ! is_wp_error( $retval ) && groups_is_user_invited( $user->ID, $group->id ) ) {
					$messages = array(
						'ban'     => __( 'Could not ban member from the group.', 'buddyboss' ),
						'unban'   => __( 'Could not unban member from the group.', 'buddyboss' ),
						'promote' => __( 'Could not promote member from the group.', 'buddyboss' ),
						'demote'  => __( 'Could not demote member from the group.', 'buddyboss' ),
					);

					$retval = new WP_Error(
						'bp_rest_group_member_cannot_' . $request['action'],
						$messages[ $request['action'] ],
						array(
							'status' => 500,
						)
					);
				}
			}
		}

		/**
		 * Filter the group members `update_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_members_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a group membership.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api {DELETE} /wp-json/buddyboss/v1/groups/:group_id/members/:user_id Delete Group Member
	 * @apiName DeleteBBGroupsMembers
	 * @apiGroup Groups
	 * @apiDescription Delete group membership
	 * @apiVersion 1.0.0
	 * @apiPermission LoggedInUser
	 * @apiParam {Number} group_id A unique numeric ID for the Group.
	 * @apiParam {Number} user_id A unique numeric ID for the Group Member.
	 */
	public function delete_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Get the Group member before it's removed.
		$member   = new BP_Groups_Member( $request['user_id'], $request['group_id'] );
		$previous = $this->prepare_item_for_response( $member, $request );

		/**
		 * Fires before the removal of a member from a group.
		 *
		 * @param int $group_id ID of the group being removed from.
		 * @param int $user_id  ID of the user being removed.
		 */
		do_action( 'groups_remove_member', $request['group_id'], $request['user_id'] );

		if ( ! $member->remove() ) {
			return new WP_Error(
				'bp_rest_group_member_failed_to_remove',
				__( 'Could not remove member from this group.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		/**
		 * Fires after a group member has been removed.
		 *
		 * @param int $user_id  ID of the user being updated.
		 * @param int $group_id ID of the group.
		 */
		do_action( 'groups_removed_member', $request['user_id'], $request['group_id'] );

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'removed'  => true,
				'previous' => $previous->get_data(),
			)
		);

		$user  = bp_rest_get_user( $request['user_id'] );
		$group = $this->groups_endpoint->get_group_object( $request['group_id'] );

		$response->add_links( $this->prepare_links( $user, $request ) );

		/**
		 * Fires after a group member is deleted via the REST API.
		 *
		 * @param WP_User $user The updated member.
		 * @param BP_Groups_Member $member The group member object.
		 * @param BP_Groups_Group $group The group object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_group_members_delete_item', $user, $member, $group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a group member.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$error  = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to perform this action.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
		$retval = $error;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to view a group membership.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		} else {
			$user             = bp_rest_get_user( $request['user_id'] );
			$loggedin_user_id = bp_loggedin_user_id();

			if ( ! $user instanceof WP_User ) {
				return new WP_Error(
					'bp_rest_group_member_invalid_id',
					__( 'Invalid group member ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} else {
				$group = $this->groups_endpoint->get_group_object( $request['group_id'] );

				if ( ! $group instanceof BP_Groups_Group ) {
					$retval = new WP_Error(
						'bp_rest_group_invalid_id',
						__( 'Invalid group ID.', 'buddyboss' ),
						array(
							'status' => 404,
						)
					);
				} elseif ( bp_current_user_can( 'bp_moderate' ) || ( $user->ID !== $loggedin_user_id && groups_is_user_admin( $loggedin_user_id, $group->id ) ) ) {
					$retval = true;
				} elseif ( $user->ID === $loggedin_user_id && ! groups_is_user_banned( $user->ID, $group->id ) ) {
					$group_admins = groups_get_group_admins( $group->id );

					// Special case for self-removal: don't allow if it'd leave a group with no admins.
					if ( in_array( $loggedin_user_id, wp_list_pluck( $group_admins, 'user_id' ), true ) ) {
						if ( 1 !== count( $group_admins ) ) {
							$retval = true;
						} else {
							$retval = $error;
						}
					} else {
						$retval = true;
					}
				}
			}
		}

		/**
		 * Filter the group members `delete_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_members_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares group member data for return as an object.
	 *
	 * @param BP_Groups_Member $group_member Group member object.
	 * @param WP_REST_Request  $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $group_member, $request ) {
		$user        = bp_rest_get_user( $group_member->user_id );
		$context     = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$member_data = $this->members_endpoint->user_data( $user, $request );

		$is_friends_connection = true;
		if ( bp_is_active( 'friends' ) && function_exists( 'bp_force_friendship_to_message' ) && bp_force_friendship_to_message() ) {
			if ( bp_is_active( 'messages' ) && ! bb_messages_allowed_messaging_without_connection( bp_loggedin_user_id() ) ) {
				if (
					! (
						bb_messages_allowed_messaging_without_connection( $group_member->user_id ) ||
						friends_check_friendship( bp_loggedin_user_id(), $group_member->user_id )
					)
				) {
					$is_friends_connection = false;
				}
			}
		}

		// Merge both info.
		$data = array_merge(
			$member_data,
			array(
				'is_mod'             => (bool) $group_member->is_mod,
				'is_admin'           => (bool) $group_member->is_admin,
				'is_banned'          => (bool) $group_member->is_banned,
				'is_confirmed'       => (bool) $group_member->is_confirmed,
				'date_modified'      => bp_rest_prepare_date_response( $group_member->date_modified ),
				'role'               => '',
				'plural_role'        => '',
				'send_group_message' => ( bp_is_active( 'messages' ) && bp_loggedin_user_id() && apply_filters( 'bb_user_can_send_group_message', true, $group_member->user_id, bp_loggedin_user_id() ) && $is_friends_connection ),
			)
		);

		// BuddyBoss Platform support.
		if ( function_exists( 'bp_get_user_group_role_title' ) && ! empty( $request['group_id'] ) ) {
			$data['role'] = bp_get_user_group_role_title( $group_member->user_id, $request['group_id'] );

			// BuddyPress support.
		} elseif ( function_exists( 'bp_groups_get_group_roles' ) && ! empty( $request['group_id'] ) ) {
			$group_role = bp_groups_get_group_roles();

			if ( groups_is_user_admin( $group_member->user_id, $request['group_id'] ) ) {
				$data['role'] = $group_role['admin']->name;
			} elseif ( groups_is_user_mod( $group_member->user_id, $request['group_id'] ) ) {
				$data['role'] = $group_role['mod']->name;
			} elseif ( groups_is_user_member( $group_member->user_id, $request['group_id'] ) ) {
				$data['role'] = $group_role['member']->name;
			}
		}

		if ( function_exists( 'get_group_role_label' ) && ! empty( $request['group_id'] ) ) {
			$data['plural_role'] = $this->groups_endpoint->bp_get_group_member_section_title( (int) $request['group_id'], (int) $group_member->user_id );
			if ( empty( $data['plural_role'] ) ) {
				$data['plural_role'] = $data['role'];
			}
		} else {
			$data['plural_role'] = $data['role'];
		}

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $user, $request ) );

		/**
		 * Filter a group member value returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request Request used to generate the response.
		 * @param BP_Groups_Member $group_member Group member object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_members_prepare_value', $response, $request, $group_member );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WP_User         $user    User object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	protected function prepare_links( $user, $request ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );
		$url  = $base . $user->ID;

		$group_id = ( ( isset( $request['group_id'] ) && ! empty( $request['group_id'] ) ) ? $request['group_id'] : 0 );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $url ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		if ( ! empty( $group_id ) ) {
			$links['group'] = array(
				'embeddable' => true,
				'href'       => rest_url( $this->namespace . '/' . buddypress()->groups->id . '/' . $group_id ),
			);
		}

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array $links The prepared links of the REST response.
		 * @param WP_User $user User object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_members_prepare_links', $links, $user );
	}

	/**
	 * GET arguments for the endpoint's CREATABLE, EDITABLE & DELETABLE methods.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 * @since 0.1.0
	 */
	public function get_endpoint_args_for_method( $method = WP_REST_Server::CREATABLE ) {
		$key  = 'get_item';
		$args = array(
			'context' => $this->get_context_param(
				array(
					'default' => 'edit',
				)
			),
		);

		if ( WP_REST_Server::CREATABLE === $method || WP_REST_Server::EDITABLE === $method ) {
			$group_roles = array_diff( array_keys( bp_groups_get_group_roles() ), array( 'banned' ) );

			$args['role'] = array(
				'description'       => __( 'Group role to assign the user to.', 'buddyboss' ),
				'default'           => 'member',
				'type'              => 'string',
				'enum'              => $group_roles,
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			);

			if ( WP_REST_Server::CREATABLE === $method ) {
				$key             = 'create_item';
				$schema          = $this->get_item_schema();
				$args['user_id'] = array_merge(
					$schema['properties']['id'],
					array(
						'description' => __( 'A unique numeric ID for the Member to add to the Group.', 'buddyboss' ),
						'default'     => bp_loggedin_user_id(),
						'required'    => true,
						'readonly'    => false,
					)
				);
			}

			if ( WP_REST_Server::EDITABLE === $method ) {
				$key            = 'update_item';
				$args['action'] = array(
					'description'       => __( 'Action used to update a group member.', 'buddyboss' ),
					'default'           => 'promote',
					'type'              => 'string',
					'enum'              => array( 'promote', 'demote', 'ban', 'unban' ),
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => 'rest_validate_request_arg',
				);
			}
		} elseif ( WP_REST_Server::DELETABLE === $method ) {
			$key = 'delete_item';
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array $args Query arguments.
		 * @param string $method HTTP method of the request.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( "bp_rest_group_members_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the group member schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {

		// Get schema from members.
		$schema = $this->members_endpoint->get_item_schema();

		// Set title to this endpoint.
		$schema['title'] = 'bp_group_members';

		$schema['properties']['is_mod'] = array(
			'context'     => array( 'view', 'edit' ),
			'description' => __( 'Whether the member is a group moderator.', 'buddyboss' ),
			'type'        => 'boolean',
		);

		$schema['properties']['is_banned'] = array(
			'context'     => array( 'view', 'edit' ),
			'description' => __( 'Whether the member has been banned from the group.', 'buddyboss' ),
			'type'        => 'boolean',
		);

		$schema['properties']['is_admin'] = array(
			'context'     => array( 'view', 'edit' ),
			'description' => __( 'Whether the member is a group administrator.', 'buddyboss' ),
			'type'        => 'boolean',
		);

		$schema['properties']['is_confirmed'] = array(
			'context'     => array( 'view', 'edit' ),
			'description' => __( 'Whether the membership of this user has been confirmed.', 'buddyboss' ),
			'type'        => 'boolean',
		);

		$schema['properties']['date_modified'] = array(
			'context'     => array( 'view', 'edit' ),
			'description' => __( "The date of the last time the membership of this user was modified, in the site's timezone.", 'buddyboss' ),
			'type'        => 'string',
			'format'      => 'date-time',
		);

		$schema['properties']['role'] = array(
			'context'     => array( 'view', 'edit' ),
			'description' => __( 'Current member\'s role label in the group.', 'buddyboss' ),
			'type'        => 'string',
			'readonly'    => true,
		);

		$schema['properties']['plural_role'] = array(
			'context'     => array( 'view', 'edit' ),
			'description' => __( 'Current member\'s role label in the plural form in the group.', 'buddyboss' ),
			'type'        => 'string',
			'readonly'    => true,
		);

		$schema['properties']['send_group_message'] = array(
			'context'     => array( 'view', 'edit' ),
			'description' => __( 'Current member can send group message or not.', 'buddyboss' ),
			'type'        => 'string',
			'readonly'    => true,
		);

		/**
		 * Filters the group membership schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_group_members_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for collections of group memberships.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';
		$statuses                     = array( 'last_joined', 'first_joined', 'alphabetical', 'group_role' );

		if ( bp_is_active( 'activity' ) ) {
			$statuses[] = 'group_activity';
		}

		$params['status'] = array(
			'description'       => __( 'Sort the order of results by the status of the group members.', 'buddyboss' ),
			'default'           => 'last_joined',
			'type'              => 'string',
			'enum'              => $statuses,
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['roles'] = array(
			'description'       => __( 'Ensure result set includes specific group roles.', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => array_keys( bp_groups_get_group_roles() ),
			),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude'] = array(
			'description'       => __( 'Ensure result set excludes specific member IDs.', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude_admins'] = array(
			'description'       => __( 'Whether results should exclude group admins and mods.', 'buddyboss' ),
			'default'           => true,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude_banned'] = array(
			'description'       => __( 'Whether results should exclude banned group members.', 'buddyboss' ),
			'default'           => true,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['scope'] = array(
			'description'       => __( 'Limit result set to items with a specific scope.', 'buddyboss' ),
			'type'              => 'string',
			'context'           => array( 'view' ),
			'enum'              => array( 'invite', 'invite-friends', 'invited', 'message' ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_group_members_collection_params', $params );
	}

	/**
	 * Get potential group invites.
	 * From: bp_nouveau_get_group_potential_invites()
	 *
	 * @param BP_Groups_Group $group   Fetched group.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array|void|WP_Error
	 */
	public function bp_rest_get_group_potential_invites( $group, $request ) {
		global $bp;
		$user_id = get_current_user_id();

		$args = array(
			'per_page'     => $request['per_page'],
			'page'         => $request['page'],
			'search_terms' => $request['search'],
			'group_id'     => $group->id,
		);

		// check if subgroup.
		$parent_group_id = $group->parent_id;

		if ( 'invite' === $request['scope'] ) {

			$group_type = bp_groups_get_group_type( $args['group_id'] );

			// Include profile type if in Group Types > E.g Team > Group Invites ( Meta Box ) specific profile type selected.
			if ( false !== $group_type && function_exists( 'bp_group_get_group_type_id' ) ) {
				$group_type_id             = bp_group_get_group_type_id( $group_type );
				$get_selected_member_types = get_post_meta( $group_type_id, '_bp_group_type_enabled_member_type_group_invites', true );
				if ( isset( $get_selected_member_types ) && ! empty( $get_selected_member_types ) ) {
					$args['member_type'] = implode( ',', $get_selected_member_types );
				}
			}

			// Include users ( Restrict group invites to only members of who already exists in parent group ) in BuddyBoss > Settings > Social Groups > Group Hierarchies.
			if ( function_exists( 'bp_enable_group_hierarchies' ) && true === bp_enable_group_hierarchies() ) {
				if ( true === bp_enable_group_restrict_invites() ) {
					$parent_group_id = bp_get_parent_group_id( $args['group_id'] );
					if ( $parent_group_id > 0 ) {
						$members_query   = groups_get_group_members(
							array(
								'group_id' => $parent_group_id,
							)
						);
						$members         = wp_list_pluck( $members_query['members'], 'ID' );
						$args['include'] = implode( ',', $members );

						if ( empty( $args['include'] ) ) {
							return new WP_Error(
								'bp_rest_group_invites_no_member_found_in_parent',
								__( 'No members found in parent group.', 'buddyboss' ),
								array(
									'status' => 202,
								)
							);
						}
					}
				}
			}

			// Exclude users if ( Restrict invites if user already in other same group type ) is checked.
			if ( false !== $group_type && function_exists( 'bp_group_get_group_type_id' ) ) {
				$group_type_id                         = bp_group_get_group_type_id( $group_type );
				$meta                                  = get_post_custom( $group_type_id );
				$get_restrict_invites_same_group_types = isset( $meta['_bp_group_type_restrict_invites_user_same_group_type'] ) ? intval( $meta['_bp_group_type_restrict_invites_user_same_group_type'][0] ) : 0;
				if ( 1 === $get_restrict_invites_same_group_types ) {
					$group_arr = bp_get_group_ids_by_group_types( $group_type );
					if ( isset( $group_arr ) && ! empty( $group_arr ) ) {
						$group_arr = wp_list_pluck( $group_arr, 'id' );
						$key       = array_search( $args['group_id'], $group_arr, true );
						if ( false !== $key ) {
							unset( $group_arr[ $key ] );
						}
						$member_arr = array();
						foreach ( $group_arr as $group_id ) {
							$members_query = groups_get_group_members(
								array(
									'group_id' => $group_id,
								)
							);
							$members_list  = wp_list_pluck( $members_query['members'], 'ID' );
							foreach ( $members_list as $id ) {
								$member_arr[] = $id;
							}
						}
						$member_arr = array_unique( $member_arr );
						if ( isset( $members ) && ! empty( $members ) ) {
							$members         = array_diff( $members, $member_arr );
							$args['include'] = implode( ',', $members );
						}
						$args['exclude'] = implode( ',', $member_arr );
					}
				}
			}
		}

		// Check role of current logged in user for this group.
		if ( ! bp_groups_user_can_send_invites( $args['group_id'] ) ) {
			return new WP_Error(
				'bp_rest_group_invites_cannot_get_items',
				__( 'You are not authorized to send invites to other users.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$bp->groups->invites_scope = 'members';

		if ( 'invite-friends' === $request['scope'] ) {
			$args['user_id']           = $user_id;
			$bp->groups->invites_scope = 'friends';
		}

		if ( 'invited' === $request['scope'] ) {

			if ( ! bp_group_has_invites(
				array(
					'user_id'  => 'any',
					'group_id' => $group->id,
				)
			) ) {

				if ( isset( $args ) && isset( $args['search_terms'] ) && '' !== $args['search_terms'] ) {

					// This message displays if you search in pending invites screen and if no results found in search.
					return new WP_Error(
						'bp_rest_group_invites_cannot_get_items',
						__( 'All members already received invitations.', 'buddyboss' ),
						array(
							'status' => 202,
						)
					);
				} else {
					// This message displays when pending invites screen doesn't have any users invitation.
					return new WP_Error(
						'bp_rest_group_invites_cannot_get_items',
						__( 'No pending group invitations found.', 'buddyboss' ),
						array(
							'status' => 202,
						)
					);
				}
			}

			$args['is_confirmed']      = false;
			$bp->groups->invites_scope = 'invited';
		}

		$args = apply_filters( 'groups_get_group_potential_invites_requests_args', $args );

		$potential_invites = bp_nouveau_get_group_potential_invites( $args );

		if ( ! empty( $potential_invites->users ) ) {
			$potential_invites->users = array_map( 'bp_nouveau_prepare_group_potential_invites_for_js', array_values( $potential_invites->users ) );
			$potential_invites->users = array_filter( $potential_invites->users );
			return wp_list_pluck( $potential_invites->users, 'id' );
		}
	}
}
