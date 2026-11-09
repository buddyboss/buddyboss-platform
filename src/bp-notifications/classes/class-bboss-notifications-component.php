<?php
/**
 * BuddyPress Member Notifications Loader.
 *
 * Initializes the Notifications component.
 *
 * @package BuddyBoss\Notifications\Loader
 * @since BuddyPress 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Extends the component class to set up the Notifications component.
 */
#[\AllowDynamicProperties]
class BBoss_Notifications_Component extends BBoss_Component {

	/**
	 * Start the notifications component creation process.
	 *
	 * @since BuddyPress 1.9.0
	 */
	public function __construct() {
		parent::start(
			'notifications',
			'Notifications',
			bboss()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 30,
			)
		);
	}

	/**
	 * Include notifications component files.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @see BBoss_Component::includes() for a description of arguments.
	 *
	 * @param array $includes See BBoss_Component::includes() for a description.
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'adminbar',
			'template',
			'functions',
			'cache',
		);

		parent::includes( $includes );
	}

	/**
	 * Late includes method.
	 *
	 * Only load up certain code when on specific pages.
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function late_includes() {
		// Bail if PHPUnit is running.
		if ( defined( 'BP_TESTS_DIR' ) ) {
			return;
		}

		// Bail if not on a notifications page or logged in.
		if ( ! bboss_is_user_notifications() || ! is_user_logged_in() ) {
			return;
		}

		// Actions.
		if ( bboss_is_post_request() ) {
			require $this->path . 'bp-notifications/actions/bulk-manage.php';
		} elseif ( bboss_is_get_request() ) {
			require $this->path . 'bp-notifications/actions/delete.php';
		}

		// Screens.
		require $this->path . 'bp-notifications/screens/unread.php';
		if ( bboss_is_current_action( 'read' ) ) {
			require $this->path . 'bp-notifications/screens/read.php';
		}
	}

	/**
	 * Set up component global data.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @see BBoss_Component::setup_globals() for a description of arguments.
	 *
	 * @param array $args See BBoss_Component::setup_globals() for a description.
	 */
	public function setup_globals( $args = array() ) {
		$bp = bboss();

		// Define a slug, if necessary.
		if ( ! defined( 'BBOSS_NOTIFICATIONS_SLUG' ) ) {
			define( 'BBOSS_NOTIFICATIONS_SLUG', $this->id );
		}

		// Global tables for the notifications component.
		$global_tables = array(
			'table_name'      => $bp->table_prefix . 'bp_notifications',
			'table_name_meta' => $bp->table_prefix . 'bp_notifications_meta',
		);

		// Metadata tables for notifications component.
		$meta_tables = array(
			'notification' => $bp->table_prefix . 'bp_notifications_meta',
		);

		// All globals for the notifications component.
		// Note that global_tables is included in this array.
		$args = array(
			'slug'          => BBOSS_NOTIFICATIONS_SLUG,
			'has_directory' => false,
			'search_string' => __( 'Search Notifications...', 'buddyboss' ),
			'global_tables' => $global_tables,
			'meta_tables'   => $meta_tables,
		);

		parent::setup_globals( $args );
	}

	/**
	 * Set up component navigation.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @see BBoss_Component::setup_nav() for a description of arguments.
	 *
	 * @param array $main_nav Optional. See BBoss_Component::setup_nav() for
	 *                        description.
	 * @param array $sub_nav  Optional. See BBoss_Component::setup_nav() for
	 *                        description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		// Determine user to use.
		if ( bboss_displayed_user_domain() ) {
			$user_domain = bboss_displayed_user_domain();
		} elseif ( bboss_loggedin_user_domain() ) {
			$user_domain = bboss_loggedin_user_domain();
		} else {
			return;
		}

		$access             = bboss_core_can_edit_settings();
		$slug               = bboss_get_notifications_slug();
		$notifications_link = trailingslashit( $user_domain . $slug );

		// Only grab count if we're on a user page and current user has access.
		if ( bboss_is_user() && bboss_user_has_access() ) {
			$count    = bboss_notifications_get_unread_notification_count( bboss_displayed_user_id() );
			$class    = ( 0 === $count ) ? 'no-count' : 'count';
			$nav_name = __( 'Notifications', 'buddyboss' );
			$nav_name .= sprintf(
				' <span class="%s">%s</span>',
				esc_attr( $class ),
				bboss_core_number_format( $count )
			);
		} else {
			$nav_name = __( 'Notifications', 'buddyboss' );
		}

		// Add 'Notifications' to the main navigation.
		$main_nav = array(
			'name'                    => $nav_name,
			'slug'                    => $slug,
			'position'                => 30,
			'show_for_displayed_user' => $access,
			'screen_function'         => 'bboss_notifications_screen_unread',
			'default_subnav_slug'     => 'unread',
			'item_css_id'             => $this->id,
		);

		// Add the subnav items to the notifications nav item.
		$sub_nav[] = array(
			'name'            => __( 'Unread', 'buddyboss' ),
			'slug'            => 'unread',
			'parent_url'      => $notifications_link,
			'parent_slug'     => $slug,
			'screen_function' => 'bboss_notifications_screen_unread',
			'position'        => 10,
			'item_css_id'     => 'notifications-my-notifications',
			'user_has_access' => $access,
		);

		$sub_nav[] = array(
			'name'            => __( 'Read', 'buddyboss' ),
			'slug'            => 'read',
			'parent_url'      => $notifications_link,
			'parent_slug'     => $slug,
			'screen_function' => 'bboss_notifications_screen_read',
			'position'        => 20,
			'user_has_access' => $access,
		);

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @see BBoss_Component::setup_nav() for a description of the $wp_admin_nav
	 *      parameter array.
	 *
	 * @param array $wp_admin_nav See BBoss_Component::setup_admin_bar() for a
	 *                            description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables.
			$notifications_link = trailingslashit( bboss_loggedin_user_domain() . bboss_get_notifications_slug() );

			$title  = __( 'Notifications', 'buddyboss' );
			$unread = __( 'Unread', 'buddyboss' );

			// Add the "My Account" sub menus.
			$wp_admin_nav[] = array(
				'parent' => bboss()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => $title,
				'href'   => $notifications_link,
			);

			// Unread.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-unread',
				'title'    => $unread,
				'href'     => $notifications_link,
				'position' => 10,
			);

			// Read.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-read',
				'title'    => __( 'Read', 'buddyboss' ),
				'href'     => trailingslashit( $notifications_link . 'read' ),
				'position' => 20,
			);
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>.
	 *
	 * @since BuddyPress 1.9.0
	 */
	public function setup_title() {

		// Adjust title.
		if ( bboss_is_notifications_component() ) {
			$bp = bboss();

			if ( bboss_is_my_profile() ) {
				$bp->bp_options_title = __( 'Notifications', 'buddyboss' );
			} else {
				$bp->bp_options_avatar = bboss_core_fetch_avatar(
					array(
						'item_id' => bboss_displayed_user_id(),
						'type'    => 'thumb',
						/* translators: %s: member display name. */
						'alt'     => sprintf( __( 'Profile photo of %s', 'buddyboss' ), bboss_get_displayed_user_fullname() ),
					)
				);
				$bp->bp_options_title  = bboss_get_displayed_user_fullname();
			}
		}

		parent::setup_title();
	}

	/**
	 * Setup cache groups.
	 *
	 * @since BuddyPress 2.2.0
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups(
			array(
				'bp_notifications',
				'notification_meta',
				'bp_notifications_unread_count',
				'bp_notifications_read_count',
				'bp_notifications_grouped_notifications',
			)
		);

		parent::setup_cache_groups();
	}

	/**
	 * Init the BuddyBoss REST API.
	 *
	 * @param array $controllers Optional. See BBoss_Component::rest_api_init() for description.
	 *
	 * @since BuddyBoss 1.3.5
	 */
	public function rest_api_init( $controllers = array() ) {
		parent::rest_api_init( array( 'BBoss_REST_Notifications_Endpoint' ) );
	}

	/**
	 * Register the Notifications Blocks.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $blocks Optional. See BBoss_Component::blocks_init() for description.
	 */
	public function blocks_init( $blocks = array() ) {
		parent::blocks_init( array() );
	}

}
