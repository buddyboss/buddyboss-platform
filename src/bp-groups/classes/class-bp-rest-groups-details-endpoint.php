<?php
/**
 * BP REST: BP_REST_Groups_Details_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Groups Details endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Groups_Details_Endpoint extends WP_REST_Controller {

	/**
	 * BP_REST_Groups_Endpoint Instance.
	 *
	 * @var BP_REST_Groups_Endpoint
	 */
	protected $groups_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace       = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base       = buddypress()->groups->id;
		$this->groups_endpoint = new BP_REST_Groups_Endpoint();
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
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/detail',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve groups details.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error List of groups object data.
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/groups/details Groups Details
	 * @apiName        GetBBGroupsDetails
	 * @apiGroup       Groups
	 * @apiDescription Retrieve groups details(includes tabs and order_options)
	 * @apiVersion     1.0.0
	 *
	 * @apiParam {String=active,popular,newest,alphabetical} [type] Reorder group by type.
	 */
	public function get_items( $request ) {
		$retval = array();

		$retval['tabs']          = $this->get_groups_tabs( $request );
		$retval['order_options'] = function_exists( 'bp_nouveau_get_component_filters' ) ? bp_nouveau_get_component_filters( 'group', 'groups' ) : $this->bp_rest_legacy_get_group_component_filters();

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of groups details is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_groups_get_items', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to group details.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_component_required',
			__( 'Sorry, Groups component was not enabled.', 'buddyboss' ),
			array(
				'status' => '404',
			)
		);

		if ( bp_is_active( 'groups' ) ) {
			$retval = true;
		}

		/**
		 *  Filter the group details permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_details_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve groups detail.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error List of groups object data.
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/groups/:id/detail Group Detail
	 * @apiName        GetBBGroupsDetail
	 * @apiGroup       Groups
	 * @apiDescription Retrieve groups detail tabs.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} id A unique numeric ID for the Group.
	 */
	public function get_item( $request ) {

		global $bp;

		$retval = array();
		$group  = $this->groups_endpoint->get_group_object( $request );

		if ( empty( $group->id ) ) {
			$retval = new WP_Error(
				'bp_rest_group_invalid_id',
				__( 'Invalid group ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		/**
		 * Store temporary variable
		 */
		$url     = ( '/' . bp_get_groups_root_slug() . '/' . bp_get_group_slug( $group ) );
		$tempurl = ( ! empty( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );
		$tmp_bp  = $bp;

		/**
		 * Group navigation tabs creation start
		 *
		 * Groups Navigation tab only setup on group page request so for fetch group tabs we need set group url as `REQUEST_URI` forcefully and
		 * Once our job done switch back to original url.
		 * With below process BuddyPress state might be change so we need to rest it once our job done.
		 *
		 * After set Group url forcefully we need to re-execute core hook which load component and setup tabs for given group.
		 */
		$_SERVER['REQUEST_URI'] = $url;

		// Fixes for the phpunit.
		add_filter( 'bp_loggedin_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );
		add_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

		remove_action( 'bp_init', 'bb_moderation_load', 1 );
		remove_action( 'bp_init', 'bp_register_taxonomies', 2 );
		remove_action( 'bp_init', 'bp_register_post_types', 2 );
		remove_action( 'bp_init', 'bp_setup_title', 8 );
		remove_action( 'bp_init', 'bp_core_load_admin_bar_css', 12 );
		remove_action( 'bp_init', 'bp_add_rewrite_tags', 20 );
		remove_action( 'bp_init', 'bp_add_rewrite_rules', 30 );
		remove_action( 'bp_init', 'bp_add_permastructs', 40 );
		remove_action( 'bp_init', 'bp_init_background_updater', 50 );
		if ( function_exists( 'bb_init_email_background_updater' ) ) {
			remove_action( 'bp_init', 'bb_init_email_background_updater', 51 );
		}
		if ( function_exists( 'bb_init_notifications_background_updater' ) ) {
			remove_action( 'bp_init', 'bb_init_notifications_background_updater', 52 );
		}
		remove_all_actions( 'bp_actions' );

		/**
		 * Remove other hooks if needed.
		 */
		do_action( 'bp_rest_group_detail' );

		do_action( 'bp_init' );
		// phpcs:ignore
		do_action( 'bp_ld_sync/init' ); // We should remove when platform load learndash extention on bp_init.
		do_action( 'bp_actions' );

		add_action( 'bp_init', 'bb_moderation_load', 1 );
		add_action( 'bp_init', 'bp_register_taxonomies', 2 );
		add_action( 'bp_init', 'bp_register_post_types', 2 );
		add_action( 'bp_init', 'bp_setup_title', 8 );
		add_action( 'bp_init', 'bp_core_load_admin_bar_css', 12 );
		add_action( 'bp_init', 'bp_add_rewrite_tags', 20 );
		add_action( 'bp_init', 'bp_add_rewrite_rules', 30 );
		add_action( 'bp_init', 'bp_add_permastructs', 40 );
		add_action( 'bp_init', 'bp_init_background_updater', 50 );
		if ( function_exists( 'bb_init_email_background_updater' ) ) {
			add_action( 'bp_init', 'bb_init_email_background_updater', 51 );
		}
		if ( function_exists( 'bb_init_notifications_background_updater' ) ) {
			add_action( 'bp_init', 'bb_init_notifications_background_updater', 52 );
		}

		$group_slug = $group->slug;

		$group_nav = buddypress()->groups->nav;

		// if it's nouveau then let it order the tabs.
		if ( function_exists( 'bp_nouveau_set_nav_item_order' ) ) {
			bp_nouveau_set_nav_item_order( $group_nav, bp_nouveau_get_appearance_settings( 'group_nav_order' ), $group_slug );
		}

		$navigation  = array();
		$default_tab = 'members';

		if ( function_exists( 'bp_nouveau_get_appearance_settings' ) ) {
			$default_tab = bp_nouveau_get_appearance_settings( 'group_default_tab' );
		}

		$nav_items = array();
		// Check if get_secondary method is exists.
		if ( ! empty( $group_nav ) && method_exists( $group_nav, 'get_secondary' ) ) {
			$nav_items = $group_nav->get_secondary(
				array(
					'parent_slug'     => $group_slug,
					'user_has_access' => true,
				)
			);
		}

		if ( ! empty( $nav_items ) ) {
			foreach ( $nav_items as $nav ) {
				$nav = $nav->getArrayCopy();

				if ( 'public' !== $group->status && 'courses' === $nav['slug'] && ( ! groups_is_user_member( bp_loggedin_user_id(), $group->id ) && ! bp_current_user_can( 'bp_moderate' ) ) ) {
					continue;
				}

				$name = $nav['name'];
				$id   = $nav['slug'];

				// remove the count numbers.
				$name = preg_replace( '/^(.*)(<(.*)<\/(.*)>)/', '$1', $name );
				$name = trim( $name );

				$tab = array(
					'id'              => $id,
					'title'           => $name,
					'count'           => $this->bp_rest_get_nav_count( $group, $nav ),
					'position'        => $nav['position'],
					'default'         => false,
					'user_has_access' => $nav['user_has_access'],
					'link'            => $nav['link'],
					'children'        => '',
				);

				if ( $default_tab === $nav['slug'] ) {
					$tab['default'] = true;
				}

				$parent_slug = $group_slug;

				if ( 'admin' === $nav['slug'] ) {
					$parent_slug .= '_manage';
				} elseif ( 'invite' === $nav['slug'] ) {
					$parent_slug .= '_invite';
				} elseif ( 'photos' === $nav['slug'] ) {
					$parent_slug .= '_media';
				} elseif ( 'members' === $nav['slug'] ) {
					$parent_slug .= '_members';
				} elseif ( 'messages' === $nav['slug'] ) {
					$parent_slug .= '_messages';
				}

				$sub_navs = array();

				if ( $group_slug !== $parent_slug ) {
					$sub_items = $group_nav->get_secondary(
						array(
							'parent_slug'     => $parent_slug,
							'user_has_access' => true,
						)
					);

					if ( ! empty( $sub_items ) ) {
						foreach ( $sub_items as $sub_nav ) {
							$sub_nav = $sub_nav->getArrayCopy();

							$sub_name = $sub_nav['name'];
							$sub_id   = $sub_nav['slug'];

							// remove the count numbers.
							$sub_name = preg_replace( '/^(.*)(<(.*)<\/(.*)>)/', '$1', $sub_name );
							$sub_name = trim( $sub_name );

							$sub_navs[] = array(
								'id'              => $sub_id,
								'title'           => $sub_name,
								'count'           => $this->bp_rest_get_nav_count( $group, $sub_nav ),
								'position'        => $sub_nav['position'],
								'default'         => false,
								'user_has_access' => $sub_nav['user_has_access'],
								'link'            => $sub_nav['link'],
								'children'        => '',
							);
						}
					}
				}

				$tab['children'] = $sub_navs;
				$navigation[]    = apply_filters( 'bp_rest_group_tab_' . $id, $tab, $nav );
			}
		}

		$retval['tabs'] = apply_filters( 'bp_rest_group_tabs', $navigation );

		// Fixes for the phpunit.
		remove_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );
		remove_filter( 'bp_loggedin_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

		/**
		 * Group navigation tabs creation End
		 *
		 * Switching back to original `REQUEST_URI` and BuddyPress stat.
		 */
		$_SERVER['REQUEST_URI'] = $tempurl;
		$bp                     = $tmp_bp;

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of groups details is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_groups_get_item', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to group details.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_item_permissions_check( $request ) {
		$retval = true;

		if ( function_exists( 'bp_rest_enable_private_network' ) && true === bp_rest_enable_private_network() && ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, Restrict access to only logged-in members.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( true === $retval && ! bp_is_active( 'groups' ) ) {
			$retval = new WP_Error(
				'bp_rest_component_required',
				__( 'Sorry, Groups component was not enabled.', 'buddyboss' ),
				array(
					'status' => '404',
				)
			);
		}

		$group = $this->groups_endpoint->get_group_object( $request );
		if ( true === $retval && empty( $group->id ) ) {
			$retval = new WP_Error(
				'bp_rest_group_invalid_id',
				__( 'Invalid group ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( true === $retval && ! is_user_logged_in() && ( 'private' === bp_get_group_status( $group ) || 'hidden' === bp_get_group_status( $group ) ) ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to view group tabs.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 *  Filter the group detail permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_details_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Get the group details schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_groups_details',
			'type'       => 'object',
			'properties' => array(
				'tabs'          => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Groups directory tabs.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
					'items'       => array(
						'type' => 'array',
					),
				),
				'order_options' => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Groups order by options.', 'buddyboss' ),
					'type'        => 'array',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the group details schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_group_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for collections of plugins.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params = array(
			'type' => array(
				'description'       => __( 'Filter by.. active(Last Active), popular(Most Members), newest(Newly Created), alphabetical(Alphabetical)', 'buddyboss' ),
				'type'              => 'string',
				'default'           => 'active',
				'enum'              => array( 'active', 'popular', 'newest', 'alphabetical' ),
				'validate_callback' => 'rest_validate_request_arg',
			),
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_groups_details_collection_params', $params );
	}

	/**
	 * Get Groups tabs.
	 *
	 * @param WP_REST_Request $request The request sent to the API.
	 *
	 * @return array
	 */
	public function get_groups_tabs( $request ) {
		$type = $request->get_param( 'type' );
		$tabs = array();

		$tabs_items = function_exists( 'bp_nouveau_get_groups_directory_nav_items' ) ? bp_nouveau_get_groups_directory_nav_items() : $this->bp_rest_legacy_get_groups_directory_nav_items();

		if ( ! empty( $tabs_items ) ) {
			foreach ( $tabs_items as $key => $item ) {
				$tabs[ $key ]['title']    = $item['text'];
				$tabs[ $key ]['position'] = $item['position'];
				$tabs[ $key ]['count']    = $this->get_group_tab_count( $item['slug'], $type );
			}
		}

		return $tabs;
	}

	/**
	 * Legacy template group directory navigation support added.
	 *
	 * @return mixed|void
	 */
	public function bp_rest_legacy_get_groups_directory_nav_items() {
		$nav_items = array();

		$nav_items['all'] = array(
			'text'     => __( 'All Groups', 'buddyboss' ),
			'slug'     => 'all',
			'count'    => bp_get_total_group_count(),
			'position' => 5,
		);

		if ( is_user_logged_in() ) {

			$my_groups_count = bp_get_total_group_count_for_user( bp_loggedin_user_id() );

			// If the user has groups create a nav item.
			if ( $my_groups_count ) {
				$nav_items['personal'] = array(
					'text'     => __( 'My Groups', 'buddyboss' ),
					'slug'     => 'personal', // slug is used because BP_Core_Nav requires it, but it's the scope.
					'count'    => $my_groups_count,
					'position' => 15,
				);
			}

			// If the user can create groups, add the create nav.
			if ( bp_user_can_create_groups() ) {
				$nav_items['create'] = array(
					'text'     => __( 'Create a Group', 'buddyboss' ),
					'slug'     => 'create', // slug is used because BP_Core_Nav requires it, but it's the scope.
					'count'    => false,
					'position' => 999,
				);
			}
		}

		return apply_filters( 'bp_rest_legacy_get_groups_directory_nav_items', $nav_items );
	}

	/**
	 * Get group count for the tab.
	 *
	 * @param sting  $slug Group tab object slug.
	 * @param string $type Active, newest, alphabetical, random, popular.
	 *
	 * @return int
	 */
	protected function get_group_tab_count( $slug, $type ) {
		$count   = 0;
		$user_id = ( ! empty( get_current_user_id() ) ? get_current_user_id() : false );
		switch ( $slug ) {
			case 'all':
				$args = array( 'type' => $type );
				if ( is_user_logged_in() ) {
					$args['show_hidden'] = true;
				}
				$groups = groups_get_groups( $args );
				if ( ! empty( $groups ) && isset( $groups['total'] ) ) {
					$count = $groups['total'];
				}
				break;
			case 'personal':
				$groups = groups_get_groups(
					array(
						'type'        => $type,
						'user_id'     => $user_id,
						'show_hidden' => true,
					)
				);
				if ( ! empty( $groups ) && isset( $groups['total'] ) ) {
					$count = $groups['total'];
				}
				break;
		}

		return bp_core_number_format( $count );
	}

	/**
	 * Retrieve the count attribute for the current nav item.
	 *
	 * @param BP_Groups_Group $group Optional. Group object. Default: current group in loop.
	 * @param array           $nav   Navigation array.
	 *
	 * @return int The count attribute for the nav item.
	 */
	protected function bp_rest_get_nav_count( $group, $nav ) {
		$nav_item = $nav['slug'];

		if ( 'members' === $nav_item || 'all-members' === $nav_item ) {
			$count = $group->total_member_count;
		} elseif ( 'subgroups' === $nav_item ) {
			$count = count( bp_get_descendent_groups( $group->id, bp_loggedin_user_id() ) );
		} elseif ( bp_is_active( 'media' ) && bp_is_group_media_support_enabled() && 'photos' === $nav_item && function_exists( 'bp_is_group_media_support_enabled' ) ) {
			$count = bp_media_get_total_group_media_count( $group->id );
		} elseif ( bp_is_active( 'media' ) && bp_is_group_albums_support_enabled() && 'albums' === $nav_item && function_exists( 'bp_is_group_albums_support_enabled' ) ) {
			$count = bp_media_get_total_group_album_count( $group->id );
		} elseif ( 'leaders' === $nav_item ) {
			$admins = groups_get_group_admins( $group->id );
			$mods   = groups_get_group_mods( $group->id );
			$count  = count( $admins ) + count( $mods );
		} elseif ( bp_is_active( 'video' ) && bp_is_group_video_support_enabled() && 'videos' === $nav_item ) {
			$count = bp_video_get_total_group_video_count( $group->id );
		}

		if ( ! isset( $count ) ) {
			return false;
		}

		return bp_core_number_format( $count );
	}

	/**
	 * Legacy template group directory filter support added.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function bp_rest_legacy_get_group_component_filters() {
		$filters_data = array();

		$filters_data['active']       = __( 'Last Active', 'buddyboss' );
		$filters_data['popular']      = __( 'Most Members', 'buddyboss' );
		$filters_data['newest']       = __( 'Newly Created', 'buddyboss' );
		$filters_data['alphabetical'] = __( 'Alphabetical', 'buddyboss' );

		return apply_filters( 'bp_rest_legacy_get_group_component_filters', $filters_data );
	}

	/**
	 * Set current and display user with current user.
	 *
	 * @param int $user_id The user id.
	 *
	 * @return int
	 */
	public function bp_rest_get_displayed_user( $user_id ) {
		return get_current_user_id();
	}
}

