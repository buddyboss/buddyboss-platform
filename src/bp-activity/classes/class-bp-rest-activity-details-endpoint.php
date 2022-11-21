<?php
/**
 * BP REST: BP_REST_Activity_Details_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Activity endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Activity_Details_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->activity->id;
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
	}


	/**
	 * Retrieve activity details.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api {GET} /wp-json/buddyboss/v1/activity/details Activity details
	 * @apiName GetBBActivitiesDetails
	 * @apiGroup Activity
	 * @apiDescription Retrieve activity details (includes nav, filters and post_in)
	 * @apiVersion 1.0.0
	 */
	public function get_items( $request ) {

		$retval = array();

		$retval['nav']     = $this->get_activities_tabs();
		$retval['filters'] = $this->get_activities_filters();
		$retval['post_in'] = $this->get_activities_post_in();

		if ( function_exists( 'bp_activity_get_visibility_levels' ) ) {
			$retval['privacy'] = bp_activity_get_visibility_levels();
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of activity details is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_activity_details_get_items', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to activity items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_component_required',
			__( 'Sorry, Activity component was not enabled.', 'buddyboss' ),
			array(
				'status' => '404',
			)
		);

		if ( bp_is_active( 'activity' ) ) {
			$retval = true;
		}

		/**
		 * Filter the activity details permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_activity_details_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Get the plugin schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_activity_details',
			'type'       => 'object',
			'properties' => array(
				'nav'     => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Activity directory tabs.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
					'items'       => array(
						'type' => 'array',
					),
				),
				'filters' => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Activity Filter options', 'buddyboss' ),
					'type'        => 'array',
					'readonly'    => true,
				),
				'post_in' => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Activity contains from.', 'buddyboss' ),
					'type'        => 'array',
					'readonly'    => true,
				),
			),
		);

		if ( function_exists( 'bp_activity_get_visibility_levels' ) ) {
			$schema['properties']['privacy'] = array(
				'context'     => array( 'embed', 'view' ),
				'description' => __( 'Activity Privacy.', 'buddyboss' ),
				'type'        => 'array',
				'readonly'    => true,
			);
		}

		/**
		 * Filters the activity details schema.
		 *
		 * @param string $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_activity_details_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get list of activity tabs.
	 *
	 * @return array
	 */
	public function get_activities_tabs() {
		$nav_items = function_exists( 'bp_nouveau_get_activity_directory_nav_items' ) ? bp_nouveau_get_activity_directory_nav_items() : $this->bp_rest_legacy_get_activity_directory_nav_items();
		$nav       = array();

		if ( ! empty( $nav_items ) ) {
			foreach ( $nav_items as $key => $item ) {
				$nav[ $key ]['title']    = $item['text'];
				$nav[ $key ]['position'] = $item['position'];
			}
		}

		return $nav;
	}

	/**
	 * Get list of filters supported for activity component.
	 *
	 * @return array
	 */
	public function get_activities_filters() {

		// BuddyPress Docs @https://wordpress.org/plugins/buddypress-docs/ .
		if ( function_exists( 'bp_docs_load_activity_filter_options' ) ) {
			bp_docs_load_activity_filter_options();
		}

		// BuddyPress legacy template support added.
		if ( function_exists( 'bp_nouveau_get_activity_filters' ) ) {
			$activity_filters = bp_nouveau_get_activity_filters();
		} else {
			$activity_filters = $this->bp_rest_legacy_get_activity_filters();
		}

		$filters = array( '-1' => __( '-- Everything --', 'buddyboss' ) ) + $activity_filters;
		return $filters;
	}

	/**
	 * Get Activity Post in details.
	 *
	 * @return array
	 */
	public function get_activities_post_in() {
		$post_in    = array();
		$post_in[0] = __( 'My Profile', 'buddyboss' );

		if ( bp_is_active( 'groups' ) ) {
			$args   = array(
				'user_id' => get_current_user_id(),
				'type'    => 'alphabetical',
			);
			$groups = groups_get_groups( $args );

			if ( ! empty( $groups ) && ! empty( $groups['groups'] ) ) {
				foreach ( $groups['groups'] as $group ) {
					$post_in[ $group->id ] = $group->name;
				}
			}
		}

		return $post_in;
	}

	/**
	 * Legacy template activity directory navigation support added.
	 *
	 * @return mixed|void
	 */
	public function bp_rest_legacy_get_activity_directory_nav_items() {
		$nav_items = array();

		$nav_items['all'] = array(
			'text'     => __( 'All Members', 'buddyboss' ),
			'position' => 5,
		);

		if ( is_user_logged_in() ) {

			if ( bp_is_active( 'friends' ) && bp_get_total_friend_count( bp_loggedin_user_id() ) ) {
				$nav_items['friends'] = array(
					'text'     => __( 'My Friends', 'buddyboss' ),
					'position' => 15,
				);
			}

			if ( bp_is_active( 'groups' ) && bp_get_total_group_count_for_user( bp_loggedin_user_id() ) ) {
				$nav_items['groups'] = array(
					'text'     => __( 'My Groups', 'buddyboss' ),
					'position' => 25,
				);
			}

			if ( bp_get_total_favorite_count_for_user( bp_loggedin_user_id() ) ) {
				$nav_items['favorites'] = array(
					'text'     => __( 'My Favorites', 'buddyboss' ),
					'position' => 35,
				);
			}

			if ( bp_activity_do_mentions() ) {
				if ( bp_get_total_mention_count_for_user( bp_loggedin_user_id() ) ) {
					$nav_items['mentions'] = array(
						'text'     => __( 'Mentions', 'buddyboss' ),
						'position' => 45,
					);
				}
			}
		}

		return apply_filters( 'bp_rest_legacy_get_activity_directory_nav_items', $nav_items );
	}

	/**
	 * Legacy template activity directory filter support added.
	 *
	 * @return mixed
	 */
	public function bp_rest_legacy_get_activity_filters() {
		$filters_data = bp_get_activity_show_filters();
		$filters      = array();

		preg_match_all( '/<option value="(.*?)"\s*>(.*?)<\/option>/', $filters_data, $matches );

		if ( ! empty( $matches[1] ) && ! empty( $matches[2] ) ) {
			foreach ( $matches[1] as $ik => $key_action ) {
				if ( ! empty( $matches[2][ $ik ] ) && ! isset( $filters[ $key_action ] ) ) {
					$filters[ $key_action ] = $matches[2][ $ik ];
				}
			}
		}

		return apply_filters( 'bp_rest_legacy_get_activity_filters', $filters );
	}
}
