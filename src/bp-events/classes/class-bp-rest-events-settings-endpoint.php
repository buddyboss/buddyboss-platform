<?php
/**
 * REST API: Events Settings Endpoint.
 *
 * @package BuddyBoss\Events\REST
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BP_REST_Events_Settings_Endpoint class.
 *
 * Handles /buddyboss/v1/events/settings routes.
 *
 * @since BuddyBoss Events 1.0.0
 */
class BP_REST_Events_Settings_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'events/settings';
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {

		// GET|PUT /events/settings.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
				),
			)
		);

		// GET|PUT /events/settings/commission (Phase 2 — registered now, implemented later).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/commission',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_commission_settings' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_commission_settings' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
				),
			)
		);

		// GET|PUT /events/settings/stripe (Phase 2 — registered now, implemented later).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/stripe',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_stripe_settings' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_stripe_settings' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
				),
			)
		);

		// GET|PUT /events/settings/permissions.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/permissions',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_permission_settings' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_permission_settings' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
				),
			)
		);

		// GET /events/admin/revenue.
		register_rest_route(
			$this->namespace,
			'/events/admin/revenue',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_revenue_summary' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
				),
			)
		);

		// GET /events/admin/revenue/events.
		register_rest_route(
			$this->namespace,
			'/events/admin/revenue/events',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_revenue_per_event' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
				),
			)
		);
	}

	/**
	 * GET /events/settings — return all plugin settings.
	 */
	public function get_settings( $request ) {
		return rest_ensure_response( $this->get_all_settings() );
	}

	/**
	 * PUT /events/settings — update general settings.
	 */
	public function update_settings( $request ) {
		$updatable = array(
			'bb_events_root_slug'               => 'sanitize_title',
			'bb_events_default_calendar_view'   => 'sanitize_text_field',
			'bb_events_public_group_site_calendar' => 'intval',
		);

		foreach ( $updatable as $key => $sanitize ) {
			if ( null !== $request->get_param( $key ) ) {
				bp_update_option( $key, call_user_func( $sanitize, $request->get_param( $key ) ) );
			}
		}

		return rest_ensure_response( $this->get_all_settings() );
	}

	/**
	 * GET /events/settings/permissions.
	 */
	public function get_permission_settings( $request ) {
		return rest_ensure_response( array(
			'creation_permission'  => bb_get_events_creation_permission(),
			'moderation_enabled'   => bp_events_moderation_enabled(),
		) );
	}

	/**
	 * PUT /events/settings/permissions.
	 */
	public function update_permission_settings( $request ) {
		$allowed_permissions = array( 'admins', 'organizers', 'members' );

		if ( null !== $request->get_param( 'creation_permission' ) ) {
			$value = sanitize_text_field( $request->get_param( 'creation_permission' ) );
			if ( in_array( $value, $allowed_permissions, true ) ) {
				bp_update_option( 'bb_events_creation_permission', $value );
			}
		}

		if ( null !== $request->get_param( 'moderation_enabled' ) ) {
			bp_update_option( 'bb_events_moderation_enabled', (int) $request->get_param( 'moderation_enabled' ) );
		}

		return $this->get_permission_settings( $request );
	}

	/**
	 * GET /events/settings/commission — Phase 2 placeholder.
	 */
	public function get_commission_settings( $request ) {
		return rest_ensure_response( array(
			'free'     => bp_get_option( 'bb_events_commission_free', 10 ),
			'pro'      => bp_get_option( 'bb_events_commission_pro', 7 ),
			'plus'     => bp_get_option( 'bb_events_commission_plus', 4 ),
			'ultimate' => bp_get_option( 'bb_events_commission_ultimate', 2 ),
			'note'     => __( 'Commission rates are applied automatically via Stripe Connect application fees.', 'buddyboss' ),
		) );
	}

	/**
	 * PUT /events/settings/commission — Phase 2 placeholder.
	 */
	public function update_commission_settings( $request ) {
		$tiers = array( 'free', 'pro', 'plus', 'ultimate' );

		foreach ( $tiers as $tier ) {
			if ( null !== $request->get_param( $tier ) ) {
				$rate = absint( $request->get_param( $tier ) );
				$rate = min( 100, max( 0, $rate ) );
				bp_update_option( "bb_events_commission_{$tier}", $rate );
			}
		}

		return $this->get_commission_settings( $request );
	}

	/**
	 * GET /events/settings/stripe — Phase 2 placeholder.
	 */
	public function get_stripe_settings( $request ) {
		$secret_key = bp_get_option( 'bb_events_stripe_secret_key', '' );

		return rest_ensure_response( array(
			'has_secret_key'    => ! empty( $secret_key ),
			'secret_key_masked' => ! empty( $secret_key ) ? 'sk_***' . substr( $secret_key, -4 ) : '',
			'has_webhook_secret' => ! empty( bp_get_option( 'bb_events_stripe_webhook_secret', '' ) ),
			'mode'              => strpos( $secret_key, 'sk_test_' ) === 0 ? 'test' : 'live',
		) );
	}

	/**
	 * PUT /events/settings/stripe — Phase 2 placeholder.
	 */
	public function update_stripe_settings( $request ) {
		if ( null !== $request->get_param( 'secret_key' ) ) {
			bp_update_option( 'bb_events_stripe_secret_key', sanitize_text_field( $request->get_param( 'secret_key' ) ) );
		}

		if ( null !== $request->get_param( 'webhook_secret' ) ) {
			bp_update_option( 'bb_events_stripe_webhook_secret', sanitize_text_field( $request->get_param( 'webhook_secret' ) ) );
		}

		return $this->get_stripe_settings( $request );
	}

	/**
	 * GET /events/admin/revenue — Phase 2 placeholder.
	 */
	public function get_revenue_summary( $request ) {
		return rest_ensure_response( array(
			'total_sales'      => 0,
			'total_commission' => 0,
			'total_events'     => 0,
			'note'             => __( 'Revenue data will be available once Stripe Connect is configured in Phase 2.', 'buddyboss' ),
		) );
	}

	/**
	 * GET /events/admin/revenue/events — Phase 2 placeholder.
	 */
	public function get_revenue_per_event( $request ) {
		return rest_ensure_response( array(
			'events' => array(),
			'note'   => __( 'Per-event revenue data will be available in Phase 2.', 'buddyboss' ),
		) );
	}

	/** Helpers ****************************************************************/

	/**
	 * Get all settings as an array.
	 *
	 * @return array
	 */
	private function get_all_settings() {
		return array(
			'general'    => array(
				'root_slug'                     => bp_get_events_root_slug(),
				'default_calendar_view'         => bb_get_events_default_calendar_view(),
				'public_group_site_calendar'    => bb_events_allow_public_group_site_calendar(),
			),
			'permissions' => array(
				'creation_permission'           => bb_get_events_creation_permission(),
				'moderation_enabled'            => bp_events_moderation_enabled(),
			),
			'stripe'     => array(
				'configured'                    => ! empty( bp_get_option( 'bb_events_stripe_secret_key', '' ) ),
			),
		);
	}

	/** Permission check *******************************************************/

	/**
	 * Admin-only permission check.
	 */
	public function admin_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'bp_rest_authorization_required',
				__( 'You must be an administrator to access events settings.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}
}
