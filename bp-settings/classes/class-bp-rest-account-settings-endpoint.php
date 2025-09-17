<?php
/**
 * BP REST: BP_REST_Account_Settings_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Account Settings endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Account_Settings_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'account-settings';
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
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve Account Settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/account-settings Account Settings
	 * @apiName        GetBBAccountSettings
	 * @apiGroup       Account Settings
	 * @apiDescription Retrieve account settings tabs.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 */
	public function get_items( $request ) {

		$retval = array();
		$navs   = array();

		global $bp;
		$tmp_bp = $bp;

		// Setup Navigation for non admin users.
		add_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );
		add_filter( 'bp_is_current_component', array( $this, 'bp_rest_is_current_component' ), 999, 2 );
		bp_setup_nav();
		remove_filter( 'bp_is_current_component', array( $this, 'bp_rest_is_current_component' ), 999, 2 );
		remove_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

		if ( ! has_action( 'bp_notification_settings' ) ) {
			bp_core_remove_subnav_item( BP_SETTINGS_SLUG, 'notifications' );
		}

		$user_nav = buddypress()->members->nav;
		if ( ! empty( $user_nav ) ) {
			$navs = $user_nav->get_secondary(
				array(
					'parent_slug'     => 'settings',
					'user_has_access' => true,
				)
			);
		}

		$request->set_param( 'user_nav', $user_nav );

		// if it's nouveau then let it order the tabs.
		if ( function_exists( 'bp_nouveau_set_nav_item_order' ) ) {
			bp_nouveau_set_nav_item_order( $navs, bp_nouveau_get_appearance_settings( 'user_nav_order' ) );
		}

		if ( ! empty( $navs ) ) {
			foreach ( $navs as $nav ) {
				$retval[] = $this->prepare_response_for_collection(
					$this->prepare_item_for_response( $nav, $request )
				);
			}
		}

		$bp = $tmp_bp;

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after account settings are fetched via the REST API.
		 *
		 * @param array            $navs     Fetched Navigations.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_account_settings_get_items', $navs, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to account settings.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to see the account settings.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the account settings `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_account_settings_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Prepares account settings data for return as an object.
	 *
	 * @param object          $nav     Navigation object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $nav, $request ) {
		$data = array(
			'name'     => wp_specialchars_decode( $nav->name, ENT_QUOTES ),
			'slug'     => $nav->slug,
			'position' => $nav->position,
			'children' => array(),
			'link'     => $nav->link,
		);

		$parent_slug   = $nav->parent_slug . '_' . $nav->slug;
		$user_nav      = $request->get_param( 'user_nav' );
		$secondary_nav = $user_nav->get_secondary(
			array(
				'parent_slug'     => $parent_slug,
				'user_has_access' => true,
			)
		);

		if ( ! empty( $secondary_nav ) ) {
			if ( ! empty( $secondary_nav ) ) {
				foreach ( $secondary_nav as $child_nav ) {
					$data['children'][] = $this->prepare_response_for_collection(
						$this->prepare_item_for_response( $child_nav, $request )
					);
				}
			}
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// @todo add prepare_links
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $data['slug'] ) );

		/**
		 * Filter a notification value returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param object           $nav      Navigation object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_account_setting_prepare_value', $response, $request, $nav );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param string $nav_slug Navigation slug.
	 * @return array Links for the given group.
	 */
	protected function prepare_links( $nav_slug ) {
		$base = '/' . $this->namespace . '/' . $this->rest_base;

		if ( 'subscriptions' === $nav_slug ) {
			$base     = '/' . $this->namespace;
			$nav_slug = 'subscription-types';
		}

		$links = array(
			'options' => array(
				'embeddable' => true,
				'href'       => rest_url( trailingslashit( $base ) . $nav_slug ),
			),
		);

		return $links;
	}

	/**
	 * Get the Account Settings schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_account_settings',
			'type'       => 'object',
			'properties' => array(
				'name'     => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique name for the setting navigation.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'slug'     => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The URL-friendly name for the navigation', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'position' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The position of the current navigation item.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'children' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Child navigation items.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'array',
				),
				'link'     => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique link for the navigation.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
			),
		);

		/**
		 * Filters the Account Settings schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_account_settings_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for the Account Settings collections.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		// Remove the search argument.
		unset( $params['search'] );
		unset( $params['page'] );
		unset( $params['per_page'] );

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_account_settings_collection_params', $params );
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

	/**
	 * Unset group component while using this.
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
}
