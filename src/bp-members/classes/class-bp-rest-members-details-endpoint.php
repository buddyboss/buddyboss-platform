<?php
/**
 * BP REST: BP_REST_Members_Details_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Members Details endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Members_Details_Endpoint extends WP_REST_Users_Controller {

	/**
	 * Current Users ID.
	 *
	 * @var integer Member ID.
	 */
	protected $user_id;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'members';
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
			'/' . $this->rest_base . '/(?P<id>[\d]+)/detail',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'A unique numeric ID for the member.', 'buddyboss' ),
						'type'        => 'integer',
						'required'    => 'true',
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

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/profile-dropdown',
			array(
				'args'   => array(
					'default' => array(
						'description' => __( 'Whichever menu you have to retrieve.', 'buddyboss' ),
						'type'        => 'boolean',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_profile_dropdown_items' ),
					'permission_callback' => array( $this, 'get_profile_dropdown_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve members details.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/members/details Members Details
	 * @apiName        GetBBMembersDetails
	 * @apiGroup       Members
	 * @apiDescription Retrieve Members details(includes tabs and order_options)
	 * @apiVersion     1.0.0
	 */
	public function get_items( $request ) {
		$retval = array();

		$retval['tabs']          = $this->get_members_tabs();
		$retval['order_options'] = function_exists( 'bp_nouveau_get_component_filters' ) ? bp_nouveau_get_component_filters( '', 'members' ) : $this->bp_rest_legacy_get_members_component_filters();

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of members details is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_members_details_get_items', $response, $request );

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
		$retval = new WP_Error(
			'bp_rest_component_required',
			__( 'Sorry, Members component was not enabled.', 'buddyboss' ),
			array(
				'status' => '404',
			)
		);

		if ( bp_is_active( 'members' ) ) {
			$retval = true;
		}

		/**
		 * Filter the members details permissions check.
		 *
		 * @param bool $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_members_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve member detail.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/members/:id/detail Members Detail
	 * @apiName        GetBBMembersDetail
	 * @apiGroup       Members
	 * @apiDescription Retrieve Member detail tabs.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam       {Number} id A unique numeric ID for the member.
	 */
	public function get_item( $request ) {
		$retval = array();
		global $bp;
		$tmp_bp = $bp;

		$logged_user_id = get_current_user_id();

		$current_user_id = $request->get_param( 'id' );
		if ( empty( $current_user_id ) ) {
			$current_user_id = bp_loggedin_user_id();
		}

		$this->user_id = $current_user_id;

		if ( empty( $current_user_id ) ) {
			return new WP_Error(
				'bp_rest_component_required',
				__( 'Sorry, Invalid member ID.', 'buddyboss' ),
				array(
					'status' => '404',
				)
			);
		}

		$user = bp_rest_get_user( $current_user_id );

		if ( ! $user instanceof WP_User ) {
			return new WP_Error(
				'bp_rest_member_invalid_id',
				__( 'Invalid member ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$url = bp_core_get_user_domain( $current_user_id );

		$tempurl = ( ! empty( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );

		/**
		 * Member navigation tabs creation start
		 *
		 * Member Navigation tab only setup on member page request so for fetch member's tabs we need set member url as `REQUEST_URI` forcefully and
		 * Once our job done switch back to original url.
		 * With below process BuddyPress state might be change so we need to rest it once our job done.
		 *
		 * After set Member url forcefully we need to re-execute core hook which load component and setup tabs for given group.
		 */
		$_SERVER['REQUEST_URI'] = $url;

		bp_core_set_uri_globals();

		add_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );
		add_filter( 'bp_is_current_component', array( $this, 'bp_rest_is_current_component' ), 999, 2 );
		add_filter( 'bp_core_create_nav_link', array( $this, 'bp_rest_core_create_nav_link' ), 999 );

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
		do_action( 'bp_rest_member_detail' );

		do_action( 'bp_init' );

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

		$_SERVER['REQUEST_URI'] = $tempurl;

		$profile_tabs = array();
		$default_tab  = 'profile';
		$navs         = buddypress()->members->nav;

		// if it's nouveau then let it order the tabs.
		if ( function_exists( 'bp_nouveau_set_nav_item_order' ) ) {
			bp_nouveau_set_nav_item_order( $navs, bp_nouveau_get_appearance_settings( 'user_nav_order' ) );
		}

		if ( function_exists( 'bp_nouveau_get_appearance_settings' ) ) {
			$tab         = bp_nouveau_get_appearance_settings( 'user_default_tab' );
			$default_tab = bp_is_active( $tab ) ? $tab : $default_tab;
		}

		$id_map = array( 'profile' => 'xprofile' );

		$args = array();

		if ( ! ( ! empty( $logged_user_id ) && (int) $logged_user_id === (int) $current_user_id ) ) {
			$args = array( 'show_for_displayed_user' => true );
		}

		if ( ! empty( $navs->get_primary( $args ) ) ) {
			foreach ( $navs->get_primary( $args ) as $nav ) {

				$name = $nav['name'];
				$id   = $nav['slug'];
				$link = $nav['link'];

				$hidden_tabs = bp_nouveau_get_appearance_settings( 'user_nav_hide' );
				if ( is_array( $hidden_tabs )
					&& ! empty( $hidden_tabs )
					&& in_array( $id, $hidden_tabs, true )
				) {
					continue;
				}

				// remove the notification numbers.
				$name = preg_replace( '/^(.*)(<(.*)<\/(.*)>)/', '$1', $name );
				$name = trim( $name );

				if ( isset( $id_map[ $id ] ) ) {
					$id = $id_map[ $id ];
				}

				$request->set_param( 'user_nav', $navs );

				$tab = array(
					'id'                      => ( 'activity' === $id ? 'activities' : $id ), // Needs this slug to suppport: hide_in_app in app.
					'title'                   => $name,
					'default'                 => false,
					'link'                    => $link,
					'count'                   => ( $this->bp_rest_nav_has_count( $nav ) ? $this->bp_rest_get_nav_count( $nav ) : '' ),
					'show_for_displayed_user' => $nav['show_for_displayed_user'],
					'children'                => array(),
				);

				if ( $default_tab === $nav['slug'] ) {
					$tab['default'] = true;
				}

				$nav_sub = $navs->get_secondary(
					array(
						'parent_slug'     => $id,
						'user_has_access' => true,
					)
				);

				if ( ! empty( $nav_sub ) ) {
					foreach ( $nav_sub as $s_nav ) {
						$sub_name = preg_replace( '/^(.*)(<(.*)<\/(.*)>)/', '$1', $s_nav['name'] );
						$sub_name = trim( $sub_name );
						$sub_nav  = array(
							'id'              => $s_nav['slug'],
							'title'           => $sub_name,
							'link'            => $s_nav['link'],
							'count'           => ( $this->bp_rest_nav_has_count( $s_nav ) ? $this->bp_rest_get_nav_count( $s_nav ) : '' ),
							'position'        => $s_nav['position'],
							'user_has_access' => $s_nav['user_has_access'],
							'children'        => $this->get_secondary_nav_menu( $s_nav, $request ),
						);

						$tab['children'][] = $sub_nav;
					}
				}

				$profile_tabs[] = apply_filters( 'bp_rest_profile_tab', $tab, $nav );
			}
		}

		$retval['tabs'] = array_values( $profile_tabs );

		remove_filter( 'bp_core_create_nav_link', array( $this, 'bp_rest_core_create_nav_link' ), 999 );
		remove_filter( 'bp_is_current_component', array( $this, 'bp_rest_is_current_component' ), 999, 2 );
		remove_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

		$bp = $tmp_bp;
		unset( $tmp_bp );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of members details is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_members_detail_get_items', $response, $request );

		return $response;
	}

	/**
	 * Prepares sub nav menu data for return as an array.
	 *
	 * @param object          $nav     Navigation object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array
	 */
	public function get_secondary_nav_menu( $nav, $request ) {
		$child_nav   = array();
		$user_nav    = $request->get_param( 'user_nav' );
		$parent_slug = $nav->parent_slug . '_' . $nav->slug;
		$nav_sub     = $user_nav->get_secondary(
			array(
				'parent_slug'     => $parent_slug,
				'user_has_access' => true,
			)
		);

		if ( ! empty( $nav_sub ) ) {
			foreach ( $nav_sub as $s_nav ) {
				$sub_name    = preg_replace( '/^(.*)(<(.*)<\/(.*)>)/', '$1', $s_nav['name'] );
				$sub_name    = trim( $sub_name );
				$child_nav[] = array(
					'id'              => $s_nav['slug'],
					'title'           => $sub_name,
					'link'            => $s_nav['link'],
					'count'           => ( $this->bp_rest_nav_has_count( $s_nav ) ? $this->bp_rest_get_nav_count( $s_nav ) : '' ),
					'position'        => $s_nav['position'],
					'user_has_access' => $s_nav['user_has_access'],
					'children'        => $this->get_secondary_nav_menu( $s_nav, $request ),
				);
			}
		}

		return $child_nav;
	}

	/**
	 * Checks if a given request has access to get all users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
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

		if ( true === $retval && ! bp_is_active( 'members' ) ) {
			$retval = new WP_Error(
				'bp_rest_component_required',
				__( 'Sorry, Members component was not enabled.', 'buddyboss' ),
				array(
					'status' => '404',
				)
			);
		}

		/**
		 * Filter the members detail permissions check.
		 *
		 * @param bool $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_members_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve profile dropdown.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/members/profile-dropdown Profile Dropdown
	 * @apiName        GetBBMembersProfileDropdown
	 * @apiGroup       Members
	 * @apiDescription Retrieve Member Profile Dropdown.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 */
	public function get_profile_dropdown_items( $request ) {

		$default = $request->get_param( 'default' );

		$navigations = $this->get_profile_navigation( $default );

		$retval = array();
		if ( ! empty( $navigations ) ) {
			foreach ( $navigations as $navigation ) {
				$retval[] = $this->prepare_response_for_collection(
					$this->prepare_item_for_response( (object) $navigation, $request )
				);
			}
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of members details is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_members_detail_get_items', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to get profile dropdown.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function get_profile_dropdown_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, Restrict access to only logged-in members.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the members profile dropdown permissions check.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @param bool            $retval  Returned value.
		 */
		return apply_filters( 'bp_rest_member_get_profile_dropdown_items_permissions_check', $retval, $request );
	}

	/**
	 * Prepares navigation data for return as an object.
	 *
	 * @since 0.1.0
	 *
	 * @param array           $navigation Navigation data.
	 * @param WP_REST_Request $request    Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $navigation, $request ) {
		$id     = ( ! empty( $navigation->post_name ) ? $navigation->post_name : $navigation->ID );
		$id_map = array(
			'activity' => 'activities',
			'profile'  => 'xprofile',
		);

		if ( isset( $id_map[ $id ] ) ) {
			$id = $id_map[ $id ];
		}

		$data = array(
			'id'       => $id,
			'name'     => $navigation->title,
			'url'      => $navigation->url,
			'count'    => isset( $navigation->count ) ? $navigation->count : '',
			'children' => array(),
		);

		if ( ! empty( $navigation->children ) ) {
			$data['children'] = $this->prepare_child_navigation( (object) $navigation->children, $request );
		}

		$data = $this->add_additional_fields_to_object( $data, $request );

		$response = rest_ensure_response( $data );

		/**
		 * Filter an navigation value returned from the API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Request  $request    Request used to generate the response.
		 * @param object           $navigation The navigation object.
		 *
		 * @param WP_REST_Response $response   The response data.
		 */
		return apply_filters( 'bp_rest_profile_dropdown_prepare_value', $response, $request, $navigation );
	}

	/**
	 * Prepare children navigation.
	 *
	 * @since 0.1.0
	 *
	 * @param object          $child_navigation Child Navigation.
	 * @param WP_REST_Request $request          Full details about the request.
	 *
	 * @return array           An array of child Navigation.
	 */
	protected function prepare_child_navigation( $child_navigation, $request ) {
		$data = array();

		if ( empty( $child_navigation ) ) {
			return $data;
		}

		foreach ( $child_navigation as $child ) {
			$data[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( (object) $child, $request )
			);
		}

		/**
		 * Filter sub navigation returned from the API.
		 *
		 * @since 0.1.0
		 *
		 * @param object          $child_navigation Child navigation.
		 * @param WP_REST_Request $request          Request used to generate the response.
		 *
		 * @param array           $data             An array of sub navigation.
		 */
		return apply_filters( 'bp_rest_profile_dropdown_prepare_children', $data, $child_navigation, $request );
	}

	/**
	 * Get the members details schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_members_details',
			'type'       => 'object',
			'properties' => array(
				'tabs'          => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Members directory tabs.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
					'items'       => array(
						'type' => 'array',
					),
				),
				'order_options' => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Members order by options.', 'buddyboss' ),
					'type'        => 'array',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the members details schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_members_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get Members tabs.
	 *
	 * @return array
	 */
	public function get_members_tabs() {
		$tabs = array();

		$tabs_items = function_exists( 'bp_nouveau_get_members_directory_nav_items' ) ? bp_nouveau_get_members_directory_nav_items() : $this->bp_rest_legacy_get_members_directory_nav_items();

		if ( ! empty( $tabs_items ) ) {
			foreach ( $tabs_items as $key => $item ) {
				$tabs[ $key ]['title']    = $item['text'];
				$tabs[ $key ]['position'] = $item['position'];
				$tabs[ $key ]['count']    = bp_core_number_format( $item['count'] );
			}
		}

		return $tabs;
	}

	/**
	 * Set current and display user with current user.
	 *
	 * @param int $user_id The user id.
	 *
	 * @return int
	 */
	public function bp_rest_get_displayed_user( $user_id ) {
		return ( ! empty( $this->user_id ) ? $this->user_id : bp_loggedin_user_id() );
	}

	/**
	 * Retrieve the count attribute for the current nav item.
	 * - from bp_nouveau_get_nav_count();
	 *
	 * @param array $nav Navigation array.
	 *
	 * @return int The count attribute for the nav item.
	 */
	protected function bp_rest_get_nav_count( $nav ) {
		$count = 0;

		if ( ! empty( $nav['primary'] ) ) {
			$span = strpos( $nav['name'], '<span' );

			// Grab count out of the <span> element.
			if ( false !== $span ) {
				$count_start = strpos( $nav['name'], '>', $span ) + 1;
				$count_end   = strpos( $nav['name'], '<', $count_start );
				$count       = (int) substr( $nav['name'], $count_start, $count_end - $count_start );
			}
		}

		/**
		 * Filter to edit the count attribute for the nav item.
		 *
		 * @param int   $count    The count attribute for the nav item.
		 * @param array $nav_item The current nav item array.
		 */
		return (int) apply_filters( 'bp_rest_nouveau_get_nav_count', bp_core_number_format( $count ), $nav );
	}

	/**
	 * Checks if the nav item has a count attribute.
	 * - from bp_nouveau_nav_has_count();
	 *
	 * @param array $nav Navigation array.
	 *
	 * @return bool
	 */
	public function bp_rest_nav_has_count( $nav ) {
		$count = false;

		if ( ! empty( $nav['primary'] ) ) {
			$count = (bool) strpos( $nav['name'], '="count"' );
		}

		/**
		 * Filter to edit whether the nav has a count attribute.
		 *
		 * @param bool  $value    True if the nav has a count attribute. False otherwise
		 * @param array $nav_item The current nav item array.
		 */
		return (bool) apply_filters( 'bp_rest_nouveau_nav_has_count', false !== $count, $nav );
	}

	/**
	 * Legacy template members directory navigation support added.
	 *
	 * @return mixed|void
	 */
	public function bp_rest_legacy_get_members_directory_nav_items() {
		$nav_items = array();

		$nav_items['all'] = array(
			'text'     => __( 'All Members', 'buddyboss' ),
			'position' => 5,
			'count'    => bp_core_number_format( bp_get_total_member_count() ),
		);

		if ( is_user_logged_in() ) {
			if ( bp_is_active( 'friends' ) && bp_get_total_friend_count( bp_loggedin_user_id() ) ) {
				$nav_items['friends'] = array(
					'text'     => __( 'My Friends', 'buddyboss' ),
					'position' => 15,
					'count'    => bp_core_number_format( bp_get_total_friend_count( bp_loggedin_user_id() ) ),
				);
			}
		}

		return apply_filters( 'bp_rest_legacy_get_members_directory_nav_items', $nav_items );
	}

	/**
	 * Legacy template members directory filter support added.
	 *
	 * @return mixed
	 */
	public function bp_rest_legacy_get_members_component_filters() {

		$filters_data = array();

		$filters_data['active'] = __( 'Last Active', 'buddyboss' );
		$filters_data['newest'] = __( 'Newest Registered', 'buddyboss' );
		if ( is_user_logged_in() ) {
			if ( bp_is_active( 'xprofile' ) ) {
				$filters_data['alphabetical'] = __( 'Alphabetical', 'buddyboss' );
			}
		}

		return apply_filters( 'bp_rest_legacy_get_members_component_filters', $filters_data );
	}

	/**
	 * Get the profile dropdown navigation based on the current user.
	 *
	 * @param boolean $default which menu you have to retrieve.
	 *
	 * @return array|mixed|null
	 */
	public function get_profile_navigation( $default ) {

		if ( function_exists( 'bp_is_active' ) && ! empty( $default ) ) {
			if ( has_nav_menu( 'header-my-account' ) ) {
				$menu = wp_nav_menu(
					array(
						'theme_location' => 'header-my-account',
						'echo'           => false,
						'fallback_cb'    => '__return_false',
					)
				);
				if ( ! empty( $menu ) ) {
					$locations  = get_nav_menu_locations();
					$menu       = wp_get_nav_menu_object( $locations['header-my-account'] );
					$menu_items = wp_get_nav_menu_items( $menu->term_id );
					return $this->bp_rest_build_tree( $menu_items, 0 );
				} else {
					return $this->bp_rest_default_menu();
				}
			} else {
				return $this->bp_rest_default_menu();
			}
		} else {
			return $this->bp_rest_default_menu();
		}
	}

	/**
	 * Get default dropdown navigation.
	 *
	 * @return object
	 */
	protected function bp_rest_default_menu() {
		$items = array();

		if ( bp_is_active( 'xprofile' ) ) {
			$profile_link  = trailingslashit( bp_loggedin_user_domain() . bp_get_profile_slug() );
			$item_xprofile = array(
				'ID'       => 'profile',
				'title'    => __( 'Profile', 'buddyboss' ),
				'url'      => esc_url( $profile_link ),
				'count'    => '',
				'children' => array(
					array(
						'ID'    => 'view',
						'title' => __( 'View', 'buddyboss' ),
						'url'   => esc_url( $profile_link ),
						'count' => '',
					),
					array(
						'ID'    => 'edit',
						'title' => __( 'Edit', 'buddyboss' ),
						'url'   => esc_url( $profile_link ),
						'count' => '',
					),
				),
			);

			if ( buddypress()->avatar->show_avatars ) {
				$item_xprofile['children'][] = array(
					'ID'    => 'profile-photo',
					'title' => __( 'Profile Photo', 'buddyboss' ),
					'url'   => esc_url( trailingslashit( $profile_link . 'change-avatar' ) ),
					'count' => '',
				);
			}

			if ( function_exists( 'bp_displayed_user_use_cover_image_header' ) && bp_displayed_user_use_cover_image_header() ) {
				$item_xprofile['children'][] = array(
					'ID'    => 'cover-photo',
					'title' => __( 'Cover Photo', 'buddyboss' ),
					'url'   => esc_url( trailingslashit( $profile_link . 'change-cover-image' ) ),
					'count' => '',
				);
			}

			$items[] = $item_xprofile;
		}

		if ( bp_is_active( 'settings' ) ) {
			$settings_link = trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() );
			$item_settings = array(
				'ID'       => bp_get_settings_slug(),
				'title'    => __( 'Account', 'buddyboss' ),
				'url'      => esc_url( $settings_link ),
				'count'    => '',
				'children' => array(),
			);

			$item_settings['children'][] = array(
				'ID'    => 'general',
				'title' => __( 'Login Information', 'buddyboss' ),
				'url'   => esc_url( $settings_link ),
				'count' => '',
			);

			if ( has_action( 'bp_notification_settings' ) ) {
				$title = esc_html__( 'Email Preferences', 'buddyboss' );
				if ( function_exists( 'bb_core_notification_preferences_data' ) ) {
					$data  = bb_core_notification_preferences_data();
					$title = esc_html( $data['menu_title'] );
				}
				$item_settings['children'][] = array(
					'ID'    => 'notifications',
					'title' => $title,
					'url'   => esc_url( trailingslashit( $settings_link . 'notifications' ) ),
					'count' => '',
				);
			}

			$item_settings['children'][] = array(
				'ID'    => 'profile',
				'title' => __( 'Privacy', 'buddyboss' ),
				'url'   => esc_url( trailingslashit( $settings_link . 'profile' ) ),
				'count' => '',
			);

			if ( bp_core_can_edit_settings() ) {
				$item_settings['children'][] = array(
					'ID'    => 'invites',
					'title' => __( 'Group Invites', 'buddyboss' ),
					'url'   => esc_url( trailingslashit( $settings_link . 'invites' ) ),
					'count' => '',
				);
			}

			$item_settings['children'][] = array(
				'ID'    => 'export',
				'title' => __( 'Export Data', 'buddyboss' ),
				'url'   => esc_url( trailingslashit( $settings_link . 'export' ) ),
				'count' => '',
			);

			if ( ! bp_current_user_can( 'bp_moderate' ) && ! bp_core_get_root_option( 'bp-disable-account-deletion' ) ) {
				$item_settings['children'][] = array(
					'ID'    => 'delete-account',
					'title' => __( 'Delete Account', 'buddyboss' ),
					'url'   => esc_url( trailingslashit( $settings_link . 'delete-account' ) ),
					'count' => '',
				);
			}

			$items[] = $item_settings;
		}

		if ( bp_is_active( 'activity' ) ) {
			$activity_link = trailingslashit( bp_loggedin_user_domain() . bp_get_activity_slug() );
			$item_activity = array(
				'ID'       => 'activities',
				'title'    => __( 'Timeline', 'buddyboss' ),
				'url'      => esc_url( $activity_link ),
				'count'    => '',
				'children' => array(),
			);

			$item_activity['children'][] = array(
				'ID'    => 'activities',
				'title' => function_exists( 'bp_is_activity_tabs_active' ) && bp_is_activity_tabs_active() ? __( 'Personal', 'buddyboss' ) : __( 'Posts', 'buddyboss' ),
				'url'   => esc_url( $activity_link ),
				'count' => '',
			);

			if ( function_exists( 'bp_is_activity_tabs_active' )
				&& bp_is_activity_tabs_active()
			) {

				if ( bp_is_activity_like_active() ) {
					$item_activity['children'][] = array(
						'ID'    => 'favorites',
						'title' => __( 'Likes', 'buddyboss' ),
						'url'   => esc_url( trailingslashit( $activity_link . 'favorites' ) ),
						'count' => '',
					);
				}

				if ( bp_is_active( 'friends' ) ) {
					$item_activity['children'][] = array(
						'ID'    => 'friends',
						'title' => __( 'Connections', 'buddyboss' ),
						'url'   => esc_url( trailingslashit( $activity_link . 'friends' ) ),
						'count' => '',
					);
				}

				if ( bp_is_active( 'groups' ) ) {
					$item_activity['children'][] = array(
						'ID'    => 'groups',
						'title' => __( 'Groups', 'buddyboss' ),
						'url'   => esc_url( trailingslashit( $activity_link . 'groups' ) ),
						'count' => '',
					);
				}

				if ( bp_activity_do_mentions() ) {
					$item_activity['children'][] = array(
						'ID'    => 'mentions',
						'title' => __( 'Mentions', 'buddyboss' ),
						'url'   => esc_url( trailingslashit( $activity_link . 'mentions' ) ),
						'count' => '',
					);
				}

				if ( bp_is_activity_follow_active() ) {
					$item_activity['children'][] = array(
						'ID'    => 'following',
						'title' => __( 'Following', 'buddyboss' ),
						'url'   => esc_url( trailingslashit( $activity_link . 'following' ) ),
						'count' => '',
					);
				}
			}
			$items[] = $item_activity;
		}

		if ( bp_is_active( 'notifications' ) ) {
			// Setup the logged in user variables.
			$notifications_link = trailingslashit( bp_loggedin_user_domain() . bp_get_notifications_slug() );

			// Pending notification requests.
			$count             = bp_notifications_get_unread_notification_count( bp_loggedin_user_id() );
			$item_notification = array(
				'ID'       => 'notifications',
				'title'    => __( 'Notifications', 'buddyboss' ),
				'url'      => esc_url( $notifications_link ),
				'count'    => bp_core_number_format( $count ),
				'children' => array(
					array(
						'ID'    => 'unread',
						'title' => __( 'Unread', 'buddyboss' ),
						'url'   => esc_url( $notifications_link ),
						'count' => bp_core_number_format( $count ),
					),
					array(
						'ID'    => 'read',
						'title' => __( 'Read', 'buddyboss' ),
						'url'   => esc_url( trailingslashit( $notifications_link . 'read' ) ),
						'count' => '',
					),
				),
			);

			$items[] = $item_notification;
		}

		if ( bp_is_active( 'messages' ) ) {
			// Setup the logged in user variables.
			$messages_link = trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() );

			// Unread message count.
			$count = messages_get_unread_count( bp_loggedin_user_id() );

			$item_messages = array(
				'ID'       => 'messages',
				'title'    => __( 'Messages', 'buddyboss' ),
				'url'      => esc_url( $messages_link ),
				'count'    => bp_core_number_format( $count ),
				'children' => array(
					array(
						'ID'    => 'inbox',
						'title' => __( 'Messages', 'buddyboss' ),
						'url'   => esc_url( $messages_link ),
						'count' => bp_core_number_format( $count ),
					),
					array(
						'ID'    => 'compose',
						'title' => __( 'New Message', 'buddyboss' ),
						'url'   => esc_url( trailingslashit( $messages_link . 'compose' ) ),
						'count' => '',
					),
				),
			);

			$items[] = $item_messages;
		}

		if ( bp_is_active( 'friends' ) ) {
			// Setup the logged in user variables.
			$friends_link = trailingslashit( bp_loggedin_user_domain() . bp_get_friends_slug() );

			// Pending friend requests.
			$count = count( friends_get_friendship_request_user_ids( bp_loggedin_user_id() ) );

			$item_friends = array(
				'ID'       => 'friends',
				'title'    => __( 'Connections', 'buddyboss' ),
				'url'      => esc_url( $friends_link ),
				'count'    => bp_core_number_format( $count ),
				'children' => array(
					array(
						'ID'    => 'my-friends',
						'title' => __( 'My Connections', 'buddyboss' ),
						'url'   => esc_url( $friends_link ),
						'count' => '',
					),
					array(
						'ID'    => 'requests',
						'title' => ( ! empty( $count ) ? __( 'Pending Requests', 'buddyboss' ) : __( 'No Pending Requests', 'buddyboss' ) ),
						'url'   => esc_url( trailingslashit( $friends_link . 'requests' ) ),
						'count' => bp_core_number_format( $count ),
					),
				),
			);

			$items[] = $item_friends;
		}

		if ( bp_is_active( 'groups' ) ) {
			// Setup the logged in user variables.
			$groups_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() );

			// Pending group invites.
			$count = groups_get_invite_count_for_user();

			$item_groups = array(
				'ID'       => 'groups',
				'title'    => __( 'Groups', 'buddyboss' ),
				'url'      => esc_url( $groups_link ),
				'count'    => bp_core_number_format( $count ),
				'children' => array(
					array(
						'ID'    => 'my-groups',
						'title' => __( 'My Groups', 'buddyboss' ),
						'url'   => esc_url( $groups_link ),
						'count' => '',
					),
					array(
						'ID'    => 'invites',
						'title' => ( ! empty( $count ) ? __( 'Pending Invites', 'buddyboss' ) : __( 'No Pending Invites', 'buddyboss' ) ),
						'url'   => esc_url( trailingslashit( $groups_link . 'invites' ) ),
						'count' => bp_core_number_format( $count ),
					),
				),
			);

			if ( bp_user_can_create_groups() ) {
				$item_groups['children'][] = array(
					'ID'    => 'create-group',
					'title' => __( 'Create Group', 'buddyboss' ),
					'url'   => esc_url( trailingslashit( bp_get_groups_directory_permalink() . 'create' ) ),
					'count' => '',
				);
			}

			$items[] = $item_groups;

		}

		if ( function_exists( 'bp_ld_sync' ) && bp_ld_sync()->settings->get( 'course.courses_visibility' ) ) {
			$slug        = apply_filters( 'bp_learndash_profile_courses_slug', \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'courses' ) );
			$course_link = trailingslashit( bp_loggedin_user_domain() . $slug );
			$name        = \LearnDash_Custom_Label::get_label( 'courses' );

			$item_courses = array(
				'ID'       => $slug,
				'title'    => $name,
				'url'      => esc_url( $course_link ),
				'count'    => '',
				'children' => array(
					array(
						'ID'    => 'my-courses',
						'title' => sprintf(
							/* translators: My Courses */
							__( 'My %s', 'buddyboss' ),
							$name
						),
						'url'   => esc_url( $course_link ),
						'count' => '',
					),
				),
			);

			$items[] = $item_courses;
		}

		if ( bp_is_active( 'forums' ) ) {
			$user_domain = bp_loggedin_user_domain();
			$forums_link = trailingslashit( $user_domain . bbp_get_root_slug() );

			$item_forums = array(
				'ID'       => 'forums',
				'title'    => __( 'Forums', 'buddyboss' ),
				'url'      => esc_url( $forums_link ),
				'count'    => '',
				'children' => array(
					array(
						'ID'    => 'topics',
						'title' => __( 'My Discussions', 'buddyboss' ),
						'url'   => esc_url( trailingslashit( $forums_link . bbp_get_topic_archive_slug() ) ),
						'count' => '',
					),
					array(
						'ID'    => 'replies',
						'title' => __( 'My Replies', 'buddyboss' ),
						'url'   => esc_url( trailingslashit( $forums_link . bbp_get_reply_archive_slug() ) ),
						'count' => '',
					),
				),
			);

			if ( function_exists( 'bbp_is_favorites_active' ) && bbp_is_favorites_active() ) {
				$item_forums['children'][] = array(
					'ID'    => 'favorite',
					'title' => __( 'My Favorites', 'buddyboss' ),
					'url'   => esc_url( trailingslashit( $forums_link . bbp_get_user_favorites_slug() ) ),
					'count' => '',
				);
			}

			$items[] = $item_forums;
		}

		if ( bp_is_active( 'media' ) && function_exists( 'bp_is_profile_media_support_enabled' ) && bp_is_profile_media_support_enabled() ) {
			// Setup the logged in user variables.
			$media_link = trailingslashit( bp_loggedin_user_domain() . bp_get_media_slug() );

			$item_media = array(
				'ID'       => 'photos',
				'title'    => __( 'Photos', 'buddyboss' ),
				'url'      => esc_url( $media_link ),
				'count'    => '',
				'children' => array(
					array(
						'ID'    => 'my-media',
						'title' => __( 'My Photos', 'buddyboss' ),
						'url'   => esc_url( $media_link ),
						'count' => '',
					),
				),
			);

			if ( function_exists( 'bp_is_profile_albums_support_enabled' ) && bp_is_profile_albums_support_enabled() ) {
				$item_media['children'][] = array(
					'ID'    => 'albums',
					'title' => __( 'My Albums', 'buddyboss' ),
					'url'   => esc_url( trailingslashit( $media_link . 'albums' ) ),
					'count' => '',
				);
			}

			$items[] = $item_media;
		}

		if ( bp_is_active( 'media' ) && function_exists( 'bp_is_profile_document_support_enabled' ) && bp_is_profile_document_support_enabled() ) {
			// Setup the logged in user variables.
			$document_link = trailingslashit( bp_loggedin_user_domain() . bp_get_document_slug() );

			$item_documents = array(
				'ID'       => 'documents',
				'title'    => __( 'Documents', 'buddyboss' ),
				'url'      => esc_url( $document_link ),
				'count'    => '',
				'children' => array(
					array(
						'ID'    => 'my-document',
						'title' => __( 'My Documents', 'buddyboss' ),
						'url'   => esc_url( $document_link ),
						'count' => '',
					),
				),
			);
			$items[]        = $item_documents;
		}

		if ( bp_is_active( 'invites' ) && function_exists( 'bp_allow_user_to_send_invites' ) && true === bp_allow_user_to_send_invites() ) {
			// Setup the logged in user variables.
			$invites_link = trailingslashit( bp_loggedin_user_domain() . bp_get_invites_slug() );
			$item_invites = array(
				'ID'       => 'invites',
				'title'    => __( 'Email Invites', 'buddyboss' ),
				'url'      => esc_url( $invites_link ),
				'count'    => '',
				'children' => array(
					array(
						'ID'    => 'send-invites',
						'title' => __( 'Send Invites', 'buddyboss' ),
						'url'   => esc_url( $invites_link ),
						'count' => '',
					),
					array(
						'ID'    => 'sent-invites',
						'title' => __( 'Sent Invites', 'buddyboss' ),
						'url'   => esc_url( trailingslashit( $invites_link . 'sent-invites' ) ),
						'count' => '',
					),
				),
			);
			$items[]      = $item_invites;
		}

		$items[] = array(
			'ID'    => 'log-out',
			'title' => __( 'Log Out', 'buddyboss' ),
			'url'   => esc_url( wp_logout_url() ),
			'count' => '',
		);

	     // phpcs:ignore
	     $items = json_decode( wp_json_encode( $items ), false );

		return $items;
	}

	/**
	 * Recursive function to create child level elements.
	 *
	 * @param array $elements  Array elements.
	 * @param int   $parent_id Parent element id.
	 *
	 * @return array
	 */
	protected function bp_rest_build_tree( array &$elements, $parent_id = 0 ) {
		$branch = array();
		foreach ( $elements as &$element ) {
			if ( (int) $element->menu_item_parent === (int) $parent_id ) {
				$children = $this->bp_rest_build_tree( $elements, $element->ID );
				if ( $children ) {
					$element->children = $children;
				}

				$branch[ $element->ID ] = $element;
				unset( $element );
			}
		}

		return $branch;
	}

	/**
	 * Unset group component while using this
	 * - added for phpunit fix.
	 *
	 * @param boolean $is_current_component Check is valid component.
	 * @param string  $component            Current component name.
	 *
	 * @return boolean
	 */
	public function bp_rest_is_current_component( $is_current_component, $component ) {
		if ( 'groups' !== $component ) {
			return $is_current_component;
		}
		return false;
	}

	/**
	 * Function to change the user domain for profile tabs.
	 *
	 * @param array $menu_args Menu arguments
	 *
	 * @return mixed
	 */
	public function bp_rest_core_create_nav_link( $menu_args ) {

		$user_domain = '';

		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		}

		if ( ! empty( $user_domain ) ) {
			$menu_args['link'] = trailingslashit( $user_domain . $menu_args['slug'] );
		}

		return $menu_args;
	}

}
