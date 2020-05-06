<?php
/**
 * BP REST: BP_REST_Members_Details_Endpoint class
 *
 * @package BuddyPress
 * @since 1.3.5
 */

defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Members Details endpoints.
 *
 * @since 1.3.5
 */
class BP_REST_Members_Details_Endpoint extends WP_REST_Users_Controller {

	/**
	 * Constructor.
	 *
	 * @since 1.3.5
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'members';
	}

	/**
	 * Register the component routes.
	 *
	 * @since 1.3.5
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
			'/' . $this->rest_base . '/detail',
			array(
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
	 * @since 1.3.5
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
		 * @since 1.3.5
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
	 * @since 1.3.5
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
		 * @since 1.3.5
		 */
		return apply_filters( 'bp_rest_members_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve member detail.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 1.3.5
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/members/detail Members Detail
	 * @apiName        GetBBMembersDetail
	 * @apiGroup       Members
	 * @apiDescription Retrieve Member detail tabs.
	 * @apiVersion     1.0.0
	 */
	public function get_item( $request ) {
		$retval = array();

		if ( function_exists( 'buddypress' ) ) {

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
						'show_for_displayed_user' => $nav['show_for_displayed_user'],
					);

					if ( $default_tab === $nav['slug'] ) {
						$tab['default'] = true;
					}

					$profile_tabs[] = apply_filters( 'bp_rest_profile_tab', $tab, $nav );
				}
			}
		}

		// Remove duplicate.
		$temp_arr     = array_unique( array_column( $profile_tabs, 'id' ) );
		$profile_tabs = array_intersect_key( $profile_tabs, $temp_arr );

		$retval['tabs'] = array_values( $profile_tabs );

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of members details is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 1.3.5
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
	 * @since 1.3.5
	 */
	public function get_item_permissions_check( $request ) {
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
		 * Filter the members detail permissions check.
		 *
		 * @param bool $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 1.3.5
		 */
		return apply_filters( 'bp_rest_members_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Get the members details schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 1.3.5
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

}
