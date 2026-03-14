<?php
/**
 * BuddyBoss Events Component Class.
 *
 * @package BuddyBoss\Events\Loader
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates the Events component.
 *
 * @since BuddyBoss Events 1.0.0
 */
#[\AllowDynamicProperties]
class BP_Events_Component extends BP_Component {

	/**
	 * The event currently being viewed.
	 *
	 * @var BP_Event|null
	 */
	public $current_event = null;

	/**
	 * Start the events component.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	public function __construct() {
		parent::start(
			'events',
			__( 'Events', 'buddyboss' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 80,
				'search_query_arg'         => 'events_search',
			)
		);
	}

	/**
	 * Include Events component files.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'functions',
			'filters',
			'template',
			'cache',
		);

		if ( bp_is_active( 'activity' ) ) {
			$includes[] = 'activity';
		}

		if ( is_admin() ) {
			$includes[] = 'admin';
		}

		parent::includes( $includes );
	}

	/**
	 * Late includes — only load on specific pages.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	public function late_includes() {
		if ( bp_is_current_component( 'events' ) ) {
			if ( bp_is_action_variable( 'create', 0 ) ) {
				require $this->path . 'bp-events/screens/create.php';
			} elseif ( bp_is_single_item() ) {
				require $this->path . 'bp-events/screens/single/home.php';
				require $this->path . 'bp-events/screens/single/edit.php';
			} else {
				require $this->path . 'bp-events/screens/directory.php';
			}
		}
	}

	/**
	 * Set up globals.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	public function setup_globals( $args = array() ) {
		global $wpdb;

		$bp = buddypress();

		// Tables.
		$this->table_name           = $wpdb->prefix . 'bp_events';
		$this->table_name_meta      = $wpdb->prefix . 'bp_eventmeta';
		$this->table_name_attendees = $wpdb->prefix . 'bp_event_attendees';
		$this->table_name_invites   = $wpdb->prefix . 'bp_event_invites';

		$global_tables = array(
			'table_name'           => $this->table_name,
			'table_name_meta'      => $this->table_name_meta,
			'table_name_attendees' => $this->table_name_attendees,
			'table_name_invites'   => $this->table_name_invites,
		);

		parent::setup_globals(
			array(
				'slug'                  => bp_get_events_root_slug(),
				'root_slug'             => isset( $bp->pages->events->slug ) ? $bp->pages->events->slug : 'events',
				'has_directory'         => true,
				'directory_title'       => __( 'Events', 'buddyboss' ),
				'search_string'         => __( 'Search Events...', 'buddyboss' ),
				'global_tables'         => $global_tables,
				'notification_callback' => 'bp_events_format_notifications',
			)
		);
	}

	/**
	 * Set up component navigation.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {
		$main_nav = array(
			'name'                => __( 'Events', 'buddyboss' ),
			'slug'                => $this->slug,
			'position'            => 80,
			'screen_function'     => 'bp_events_screen_my_events',
			'default_subnav_slug' => 'attending',
			'item_css_id'         => $this->id,
		);

		$events_link = trailingslashit( bp_loggedin_user_domain() . $this->slug );

		$sub_nav[] = array(
			'name'            => __( 'Attending', 'buddyboss' ),
			'slug'            => 'attending',
			'parent_url'      => $events_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'bp_events_screen_attending',
			'position'        => 10,
		);

		$sub_nav[] = array(
			'name'            => __( 'Hosting', 'buddyboss' ),
			'slug'            => 'hosting',
			'parent_url'      => $events_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'bp_events_screen_hosting',
			'position'        => 20,
		);

		// Payout dashboard — only shown to connected Stripe users (Phase 2).
		$sub_nav[] = array(
			'name'            => __( 'Payouts', 'buddyboss' ),
			'slug'            => 'payouts',
			'parent_url'      => $events_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'bp_events_screen_payouts',
			'position'        => 30,
			'user_has_access' => bp_is_my_profile(),
		);

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up Admin Bar menu items.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	public function setup_adminbar_nav( $wp_admin_nav = array() ) {
		$wp_admin_nav[] = array(
			'parent' => buddypress()->my_account_menu_id,
			'id'     => 'my-account-events',
			'title'  => __( 'Events', 'buddyboss' ),
			'href'   => trailingslashit( bp_loggedin_user_domain() . $this->slug ),
		);

		$wp_admin_nav[] = array(
			'parent' => 'my-account-events',
			'id'     => 'my-account-events-attending',
			'title'  => __( 'Attending', 'buddyboss' ),
			'href'   => trailingslashit( bp_loggedin_user_domain() . $this->slug . '/attending' ),
		);

		$wp_admin_nav[] = array(
			'parent' => 'my-account-events',
			'id'     => 'my-account-events-hosting',
			'title'  => __( 'Hosting', 'buddyboss' ),
			'href'   => trailingslashit( bp_loggedin_user_domain() . $this->slug . '/hosting' ),
		);

		parent::setup_adminbar_nav( $wp_admin_nav );
	}

	/**
	 * Register REST API controllers.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	public function rest_api_init( $controllers = array() ) {
		$controllers = array(
			'BP_REST_Events_Endpoint',
			'BP_REST_Events_Settings_Endpoint',
		);

		parent::rest_api_init( $controllers );
	}

	/**
	 * Register WP CLI commands.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	public function register_wp_cli_commands() {}
}
