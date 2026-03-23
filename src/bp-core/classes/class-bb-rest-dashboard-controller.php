<?php
/**
 * BuddyBoss REST API Dashboard Controller
 *
 * Handles REST API requests for Dashboard data.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Dashboard REST Controller Class
 *
 * @since BuddyBoss 3.0.0
 */
class BB_REST_Dashboard_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base  = 'dashboard';
	}

	/**
	 * Register routes.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/installs',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_installs' ),
					'permission_callback' => array( $this, 'get_installs_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/analytics',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_analytics' ),
					'permission_callback' => array( $this, 'get_analytics_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/scheduled-posts',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_scheduled_posts' ),
					'permission_callback' => array( $this, 'get_scheduled_posts_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/recommendations',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_recommendations' ),
					'permission_callback' => array( $this, 'get_recommendations_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Check if user can view installs.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function get_installs_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to view installs.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get BuddyBoss installs (Platform and Pro versions).
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_installs( $request ) {
		$installs = array(
			'platform' => array(
				'version'          => buddypress()->version,
				'update_available' => false,
			),
		);

		// Check if Platform Pro is installed.
		if ( defined( 'BP_PLATFORM_PRO_VERSION' ) ) {
			$installs['pro'] = array(
				'version'          => BP_PLATFORM_PRO_VERSION,
				'update_available' => false, // TODO: Check for updates via update API.
			);
		}

		// Check for updates (simplified - in production, use update API).
		$update_check = get_site_transient( 'bb_plugin_updates' );
		if ( false !== $update_check ) {
			if ( isset( $update_check['platform'] ) ) {
				$installs['platform']['update_available'] = version_compare( $update_check['platform'], buddypress()->version, '>' );
			}
			if ( isset( $update_check['pro'] ) && isset( $installs['pro'] ) ) {
				$installs['pro']['update_available'] = version_compare( $update_check['pro'], $installs['pro']['version'], '>' );
			}
		}

		return BB_REST_Response::success( $installs );
	}

	/**
	 * Check if user can view analytics.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function get_analytics_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to view analytics.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get community analytics data.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_analytics( $request ) {
		// Cache analytics for 5 minutes.
		$cache_key = 'bb_dashboard_analytics';
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return BB_REST_Response::success( $cached );
		}

		// Get total users.
		$total_users = count_users();
		$total_users_count = isset( $total_users['total_users'] ) ? (int) $total_users['total_users'] : 0;

		// Get active users (users who logged in within last 30 days).
		$active_users = $this->get_active_users_count( 30 );

		// Get new users this month.
		$new_users_this_month = $this->get_new_users_count( 'month' );

		// Get activity count.
		$activity_count = 0;
		if ( bp_is_active( 'activity' ) ) {
			$activity_count = (int) bp_activity_get(
				array(
					'count_total' => true,
					'per_page'    => 1,
				)
			)['total'];
		}

		// Get groups count.
		$groups_count = 0;
		if ( bp_is_active( 'groups' ) ) {
			$groups_count = (int) groups_get_groups(
				array(
					'count_total' => true,
					'per_page'    => 1,
				)
			)['total'];
		}

		$analytics = array(
			'total_users'         => $total_users_count,
			'active_users'        => $active_users,
			'new_users_this_month' => $new_users_this_month,
			'activity_count'      => $activity_count,
			'groups_count'        => $groups_count,
		);

		// Cache for 5 minutes.
		set_transient( $cache_key, $analytics, 5 * MINUTE_IN_SECONDS );

		return BB_REST_Response::success( $analytics );
	}

	/**
	 * Get active users count (users who logged in within specified days).
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param int $days Number of days.
	 * @return int
	 */
	private function get_active_users_count( $days = 30 ) {
		global $wpdb;

		$date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// Check if we're using activity meta or user meta for last activity.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT user_id) 
				FROM {$wpdb->usermeta} 
				WHERE meta_key = 'last_activity' 
				AND meta_value >= %s",
				$date
			)
		);

		return (int) $count;
	}

	/**
	 * Get new users count for a period.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $period 'month', 'week', or 'day'.
	 * @return int
	 */
	private function get_new_users_count( $period = 'month' ) {
		global $wpdb;

		$date_format = '';
		switch ( $period ) {
			case 'month':
				$date_format = date( 'Y-m-01 00:00:00' );
				break;
			case 'week':
				$date_format = date( 'Y-m-d 00:00:00', strtotime( '-7 days' ) );
				break;
			case 'day':
				$date_format = date( 'Y-m-d 00:00:00' );
				break;
		}

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) 
				FROM {$wpdb->users} 
				WHERE user_registered >= %s",
				$date_format
			)
		);

		return (int) $count;
	}

	/**
	 * Check if user can view scheduled posts.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function get_scheduled_posts_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to view scheduled posts.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get scheduled posts.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_scheduled_posts( $request ) {
		$per_page = isset( $request['per_page'] ) ? (int) $request['per_page'] : 5;
		$page     = isset( $request['page'] ) ? (int) $request['page'] : 1;

		$args = array(
			'post_status'    => 'future',
			'post_type'       => 'post',
			'posts_per_page'  => $per_page,
			'paged'           => $page,
			'orderby'         => 'date',
			'order'           => 'ASC',
		);

		$query = new WP_Query( $args );

		$posts = array();
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$posts[] = array(
					'id'             => $post->ID,
					'title'          => get_the_title( $post->ID ),
					'scheduled_date' => get_the_date( 'Y-m-d H:i:s', $post->ID ),
					'edit_link'      => get_edit_post_link( $post->ID, 'raw' ),
				);
			}
		}

		return BB_REST_Response::paginated(
			$posts,
			(int) $query->found_posts,
			$page,
			$per_page
		);
	}

	/**
	 * Check if user can view recommendations.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function get_recommendations_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to view recommendations.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get recommended plugins and integrations.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_recommendations( $request ) {
		// Cache recommendations for 1 hour.
		$cache_key = 'bb_dashboard_recommendations';
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return BB_REST_Response::success( $cached );
		}

		$recommendations = array(
			'plugins'      => array(),
			'integrations' => array(),
		);

		// Get recommended plugins (can be filtered).
		$recommended_plugins = apply_filters(
			'bb_dashboard_recommended_plugins',
			array(
				array(
					'id'          => 'buddyboss-platform-pro',
					'name'        => __( 'BuddyBoss Platform Pro', 'buddyboss' ),
					'description' => __( 'Unlock advanced features and integrations.', 'buddyboss' ),
					'url'         => 'https://www.buddyboss.com/platform/',
				),
				array(
					'id'          => 'buddyboss-gamification',
					'name'        => __( 'BuddyBoss Gamification', 'buddyboss' ),
					'description' => __( 'Add gamification features to your community.', 'buddyboss' ),
					'url'         => 'https://www.buddyboss.com/gamification/',
				),
			)
		);

		// Get recommended integrations (can be filtered).
		$recommended_integrations = apply_filters(
			'bb_dashboard_recommended_integrations',
			array(
				array(
					'id'          => 'learndash',
					'name'        => __( 'LearnDash Integration', 'buddyboss' ),
					'description' => __( 'Integrate LearnDash courses with your community.', 'buddyboss' ),
					'url'         => 'https://www.buddyboss.com/integrations/learndash/',
				),
				array(
					'id'          => 'woocommerce',
					'name'        => __( 'WooCommerce Integration', 'buddyboss' ),
					'description' => __( 'Connect WooCommerce with your community.', 'buddyboss' ),
					'url'         => 'https://www.buddyboss.com/integrations/woocommerce/',
				),
			)
		);

		$recommendations['plugins']      = $recommended_plugins;
		$recommendations['integrations'] = $recommended_integrations;

		// Cache for 1 hour.
		set_transient( $cache_key, $recommendations, HOUR_IN_SECONDS );

		return BB_REST_Response::success( $recommendations );
	}

	/**
	 * Get collection parameters.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'buddyboss' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'buddyboss' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}
}
