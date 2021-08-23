<?php
/**
 * BP REST: BP_REST_Media_Details_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Media Details endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Media_Details_Endpoint extends WP_REST_Controller {

	/**
	 * BP_REST_Media_Endpoint Instance.
	 *
	 * @var BP_REST_Media_Endpoint
	 */
	protected $media_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'media';

		$this->media_endpoint = new BP_REST_Media_Endpoint();
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
	 * Retrieve Media details.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/media/details Media Details
	 * @apiName        GetBBMediaDetails
	 * @apiGroup       Media
	 * @apiDescription Retrieve Media details(includes tabs and privacy options)
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 */
	public function get_items( $request ) {
		$retval = array();

		$retval['tabs']    = $this->get_media_tabs();
		$retval['privacy'] = $this->get_media_privacy();

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of medias details is fetched via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Response $response  The response data.
		 * @param WP_REST_Request  $request   The request sent to the API.
		 */
		do_action( 'bp_rest_media_details_get_items', $response, $request );

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

		/**
		 * Filter the media details `get_items` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_media_details_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Get the media details schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_media_details',
			'type'       => 'object',
			'properties' => array(
				'tabs'    => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Media directory tabs.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
					'items'       => array(
						'type' => 'array',
					),
				),
				'privacy' => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Media privacy.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the media details schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_media_details_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get Media Directory tabs.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_media_tabs() {
		$tabs = array();
		add_filter( 'bp_is_current_component', array( $this, 'bp_rest_is_current_component' ), 999, 2 );
		add_filter( 'bp_get_total_media_count', array( $this, 'bp_rest_get_total_media_count' ) );

		$tabs_items = function_exists( 'bp_nouveau_get_media_directory_nav_items' ) ? bp_nouveau_get_media_directory_nav_items() : array();

		remove_filter( 'bp_get_total_media_count', array( $this, 'bp_rest_get_total_media_count' ) );
		remove_filter( 'bp_is_current_component', array( $this, 'bp_rest_is_current_component' ), 999, 2 );

		if ( ! empty( $tabs_items ) ) {
			foreach ( $tabs_items as $key => $item ) {
				if ( 'group' === $key ) {
					$key = 'groups';
				}
				$tabs[ $key ]['title']    = $item['text'];
				$tabs[ $key ]['count']    = $item['count'];
				$tabs[ $key ]['position'] = $item['position'];
			}
		}

		return $tabs;
	}

	/**
	 * Get privacy for the media.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_media_privacy() {
		$privacy = buddypress()->media->visibility_levels;
		$retval  = array();

		if ( ! empty( $privacy ) ) {
			foreach ( $privacy as $key => $value ) {
				if ( 'grouponly' === $key ) {
					continue;
				}

				$retval[ $key ] = $value;
			}
		}

		return $retval;
	}

	/**
	 * Return the total media count in your BP instance.
	 *
	 * @since 0.1.0
	 *
	 * @return int Media count.
	 */
	public function bp_rest_get_total_media_count() {
		$count = $GLOBALS['media_template']->total_media_count;

		/**
		 * Filters the total number of media.
		 *
		 * @since 0.1.0
		 *
		 * @param int $count Total number of media.
		 */
		return apply_filters( 'bp_rest_get_total_media_count', (int) $count );
	}

	/**
	 * Set Media Component while getting the photo count.
	 *
	 * @param boolean $is_current_component Check is valid component.
	 * @param string  $component            Current component name.
	 *
	 * @return boolean
	 */
	public function bp_rest_is_current_component( $is_current_component, $component ) {
		if ( 'media' !== $component ) {
			return $is_current_component;
		}

		return true;
	}

}
