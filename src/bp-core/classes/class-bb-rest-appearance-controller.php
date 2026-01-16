<?php
/**
 * BuddyBoss Appearance Settings REST Controller
 *
 * Handles appearance settings for the new admin UI.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API controller for appearance settings.
 *
 * @since BuddyBoss 3.0.0
 */
class BB_REST_Appearance_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'settings/appearance';
	}

	/**
	 * Register the routes.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function register_routes() {
		// GET and POST for appearance settings
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_items' ),
					'permission_callback' => array( $this, 'update_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Check if user has permission to read appearance settings.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check if user has permission to update appearance settings.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function update_items_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get appearance settings.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$appearance_settings = bp_get_option( 'bp_nouveau_appearance', array() );

		// Set defaults if not set
		$defaults = array(
			'group_nav_display' => 0,
			'group_nav_order'   => array(),
			'group_nav_hide'    => array(),
			'group_default_tab' => 'members',
			'user_nav_display'  => 0,
			'user_nav_order'    => array(),
			'user_nav_hide'     => array(),
		);

		$settings = wp_parse_args( $appearance_settings, $defaults );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $settings,
			),
			200
		);
	}

	/**
	 * Update appearance settings.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_items( $request ) {
		$params = $request->get_params();

		// Get current settings
		$current_settings = bp_get_option( 'bp_nouveau_appearance', array() );

		// Update only the settings that were passed
		$allowed_settings = array(
			'group_nav_display',
			'group_nav_order',
			'group_nav_hide',
			'group_default_tab',
			'user_nav_display',
			'user_nav_order',
			'user_nav_hide',
		);

		foreach ( $allowed_settings as $setting_key ) {
			if ( isset( $params[ $setting_key ] ) ) {
				$current_settings[ $setting_key ] = $this->sanitize_setting( $setting_key, $params[ $setting_key ] );
			}
		}

		// Save settings
		$updated = bp_update_option( 'bp_nouveau_appearance', $current_settings );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $current_settings,
			),
			200
		);
	}

	/**
	 * Sanitize a setting value based on its key.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Setting value.
	 * @return mixed Sanitized value.
	 */
	private function sanitize_setting( $key, $value ) {
		switch ( $key ) {
			case 'group_nav_display':
			case 'user_nav_display':
				return absint( $value );

			case 'group_nav_order':
			case 'user_nav_order':
			case 'group_nav_hide':
			case 'user_nav_hide':
				if ( is_array( $value ) ) {
					return array_map( 'sanitize_key', $value );
				}
				return array();

			case 'group_default_tab':
				return sanitize_key( $value );

			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Get the item schema.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'appearance_settings',
			'type'       => 'object',
			'properties' => array(
				'group_nav_display' => array(
					'type'        => 'integer',
					'description' => __( 'Display group navigation vertically (1) or horizontally (0).', 'buddyboss' ),
					'default'     => 0,
				),
				'group_nav_order'   => array(
					'type'        => 'array',
					'description' => __( 'Order of group navigation items.', 'buddyboss' ),
					'items'       => array(
						'type' => 'string',
					),
				),
				'group_nav_hide'    => array(
					'type'        => 'array',
					'description' => __( 'Hidden group navigation items.', 'buddyboss' ),
					'items'       => array(
						'type' => 'string',
					),
				),
				'group_default_tab' => array(
					'type'        => 'string',
					'description' => __( 'Default group navigation tab.', 'buddyboss' ),
					'default'     => 'members',
				),
			),
		);
	}
}
