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
	}

	/**
	 * Retrieve members details.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api {GET} /wp-json/buddyboss/v1/members/details Members Details
	 * @apiName GetBBMembersDetails
	 * @apiGroup Members
	 * @apiDescription Retrieve Members details(includes tabs and order_options)
	 * @apiVersion 1.0.0
	 */
	public function get_items( $request ) {
		$retval = array();

		$retval['tabs']          = $this->get_members_tabs();
		$retval['order_options'] = bp_nouveau_get_component_filters( '', 'members' );

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
		$retval = true;

		if ( ! bp_is_active( 'members' ) ) {
			$retval = new WP_Error(
				'bp_rest_component_required',
				__( 'Sorry, Members component was not enabled.', 'buddyboss' ),
				array(
					'status' => '404',
				)
			);
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
	 * @apiParam {Number} id A unique numeric ID for the member.
	 */
	public function get_item( $request ) {
		$retval = array();
		global $bp;

		$current_user_id = $request->get_param( 'id' );
		$this->user_id   = $current_user_id;
		if ( empty( $current_user_id ) ) {
			$current_user_id = bp_loggedin_user_id();
		}

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

		add_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );
		bp_setup_nav();
		remove_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

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

		$id_map = array(
			'activity' => 'activities',
			'profile'  => 'xprofile',
		);

		if ( ! empty( $navs->get_primary() ) ) {
			foreach ( $navs->get_primary() as $nav ) {

				$name = $nav['name'];
				$id   = $nav['slug'];

				// remove the notification numbers.
				$name = preg_replace( '/^(.*)(<(.*)<\/(.*)>)/', '$1', $name );
				$name = trim( $name );

				if ( isset( $id_map[ $id ] ) ) {
					$id = $id_map[ $id ];
				}

				$tab = array(
					'id'                      => $id,
					'title'                   => $name,
					'default'                 => false,
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
							'count'           => ( $this->bp_rest_nav_has_count( $s_nav ) ? $this->bp_rest_get_nav_count( $s_nav ) : '' ),
							'position'        => $s_nav['position'],
							'user_has_access' => $s_nav['user_has_access'],
						);

						$tab['children'][] = $sub_nav;
					}
				}

				$profile_tabs[] = apply_filters( 'bp_rest_profile_tab', $tab, $nav );
			}
		}

		$retval['tabs'] = array_values( $profile_tabs );

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
	 * Checks if a given request has access to get all users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function get_item_permissions_check( $request ) {
		$retval = true;

		if ( function_exists( 'bp_enable_private_network' ) && true !== bp_enable_private_network() && ! is_user_logged_in() ) {
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

		$tabs_items = bp_nouveau_get_members_directory_nav_items();

		if ( ! empty( $tabs_items ) ) {
			foreach ( $tabs_items as $key => $item ) {
				$tabs[ $key ]['title']    = $item['text'];
				$tabs[ $key ]['position'] = $item['position'];
				$tabs[ $key ]['count']    = $item['count'];
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
		return (int) apply_filters( 'bp_rest_nouveau_get_nav_count', $count, $nav );
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

}
