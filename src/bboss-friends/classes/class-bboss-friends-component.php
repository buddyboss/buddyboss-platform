<?php
/**
 * BuddyBoss Connections Feeds Loader.
 *
 * The friends component is for users to create relationships with each other.
 *
 * @package BuddyBoss\Connections
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Defines the BuddyBoss Connections Component.
 *
 * @since BuddyPress 1.5.0
 */
#[\AllowDynamicProperties]
class BBoss_Friends_Component extends BBoss_Component {

	/**
	 * Start the friends component creation process.
	 *
	 * @since BuddyPress 1.5.0
	 */
	public function __construct() {
		parent::start(
			'friends',
			'Connections',
			bboss()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 60,
			)
		);
	}

	/**
	 * Include bp-friends files.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @see BBoss_Component::includes() for description of parameters.
	 *
	 * @param array $includes See {@link BBoss_Component::includes()}.
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'cache',
			'filters',
			'template',
			'functions',
			'widgets',
		);

		// Conditional includes.
		if ( bboss_is_active( 'activity' ) ) {
			$includes[] = 'activity';
		}
		if ( bboss_is_active( 'notifications' ) ) {
			$includes[] = 'notifications';
		}

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

		// Connections.
		if ( bboss_is_user_friends() ) {
			// Authenticated actions.
			if ( is_user_logged_in() &&
				in_array( bboss_current_action(), array( 'add-friend', 'remove-friend' ), true )
			) {
				require $this->path . 'bboss-friends/actions/' . bboss_current_action() . '.php';
			}

			// User nav.
			require $this->path . 'bboss-friends/screens/my-friends.php';
			if ( is_user_logged_in() && bboss_is_user_friend_requests() ) {
				require $this->path . 'bboss-friends/screens/requests.php';
			}

			if ( is_user_logged_in() ) {
				require $this->path . 'bboss-friends/screens/mutual-friends.php';
			}
		}
	}

	/**
	 * Set up bp-friends global settings.
	 *
	 * The BP_FRIENDS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @see BBoss_Component::setup_globals() for description of parameters.
	 *
	 * @param array $args See {@link BBoss_Component::setup_globals()}.
	 */
	public function setup_globals( $args = array() ) {
		$bp = bboss();

		// Deprecated. Do not use.
		// Defined conditionally to support unit tests.
		if ( ! defined( 'BBOSS_FRIENDS_DB_VERSION' ) ) {
			define( 'BBOSS_FRIENDS_DB_VERSION', '1800' );
		}

		// Define a slug, if necessary.
		if ( ! defined( 'BBOSS_FRIENDS_SLUG' ) ) {
			define( 'BBOSS_FRIENDS_SLUG', $this->id );
		}

		// Global tables for the friends component.
		$global_tables = array(
			'table_name'      => $bp->table_prefix . 'bp_friends',
			'table_name_meta' => $bp->table_prefix . 'bp_friends_meta',
		);

		// All globals for the friends component.
		// Note that global_tables is included in this array.
		$args = array(
			'slug'                  => BBOSS_FRIENDS_SLUG,
			'has_directory'         => false,
			'search_string'         => __( 'Search Connections&hellip;', 'buddyboss' ),
			'notification_callback' => 'bboss_friends_format_notifications',
			'global_tables'         => $global_tables,
		);

		parent::setup_globals( $args );
	}

	/**
	 * Set up component navigation.
	 *
	 * @since BuddyPress 1.5.0
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

		$access        = bboss_core_can_edit_settings();
		$slug          = bboss_get_friends_slug();
		$friends_link  = trailingslashit( $user_domain . $slug );
		$main_nav_name = __( 'Connections', 'buddyboss' );

		$main_nav = array(
			'name'                => $main_nav_name,
			'slug'                => $slug,
			'position'            => 60,
			'screen_function'     => 'bboss_friends_screen_my_friends',
			'default_subnav_slug' => 'my-friends',
			'item_css_id'         => $this->id,
		);

		// Add the subnav items to the friends nav item.
		$sub_nav[] = array(
			'name'            => ! bboss_is_my_profile() ? __( 'All Connections', 'buddyboss' ) : __( 'My Connections', 'buddyboss' ),
			'slug'            => 'my-friends',
			'parent_url'      => $friends_link,
			'parent_slug'     => $slug,
			'screen_function' => 'bboss_friends_screen_my_friends',
			'position'        => 10,
			'item_css_id'     => 'friends-my-friends',
		);

		if ( bboss_get_mutual_friendships() ) {
			$sub_nav[] = array(
				'name'            => __( 'Mutual Connections', 'buddyboss' ),
				'slug'            => 'mutual',
				'parent_url'      => $friends_link,
				'parent_slug'     => $slug,
				'screen_function' => 'friends_screen_mutual_friends',
				'position'        => 20,
				'user_has_access' => ! bboss_is_my_profile() && bboss_loggedin_user_id(),
			);
		}

		$sub_nav[] = array(
			'name'            => __( 'Requests', 'buddyboss' ),
			'slug'            => 'requests',
			'parent_url'      => $friends_link,
			'parent_slug'     => $slug,
			'screen_function' => 'bboss_friends_screen_requests',
			'position'        => 30,
			'user_has_access' => $access,
		);

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up bp-friends integration with the WordPress admin bar.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @see BBoss_Component::setup_admin_bar() for a description of arguments.
	 *
	 * @param array $wp_admin_nav See BBoss_Component::setup_admin_bar()
	 *                            for description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables.
			$friends_link = trailingslashit( bboss_loggedin_user_domain() . bboss_get_friends_slug() );

			// Pending friend requests.
			$count = count( bboss_friends_get_friendship_request_user_ids( bboss_loggedin_user_id() ) );
			if ( ! empty( $count ) ) {
				$title = sprintf(
					/* translators: %s: Pending friend request count for the current user */
					__( 'Connections %s', 'buddyboss' ),
					'<span class="count">' . bboss_core_number_format( $count ) . '</span>'
				);
				$pending = sprintf(
					/* translators: %s: Pending friend request count for the current user */
					__( 'Pending Requests %s', 'buddyboss' ),
					'<span class="count">' . bboss_core_number_format( $count ) . '</span>'
				);
			} else {
				$title   = __( 'Connections', 'buddyboss' );
				$pending = __( 'No Pending Requests', 'buddyboss' );
			}

			// Add the "My Account" sub menus.
			$wp_admin_nav[] = array(
				'parent' => bboss()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => $title,
				'href'   => $friends_link,
			);

			// My Connections.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-friendships',
				'title'    => __( 'My Connections', 'buddyboss' ),
				'href'     => $friends_link,
				'position' => 10,
			);

			// Requests.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-requests',
				'title'    => $pending,
				'href'     => trailingslashit( $friends_link . 'requests' ),
				'position' => 20,
			);
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>.
	 *
	 * @since BuddyPress 1.5.0
	 */
	public function setup_title() {

		// Adjust title.
		if ( bboss_is_friends_component() ) {
			$bp = bboss();

			if ( bboss_is_my_profile() ) {
				$bp->bp_options_title = __( 'Connections', 'buddyboss' );
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
				'bp_friends_requests',
				'bp_friends_friendships', // Individual friendship objects are cached here by ID.
				'bp_friends_friendships_for_user', // All friendship IDs for a single user.
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
		parent::rest_api_init( array(
			'BBoss_REST_Friends_Endpoint',
		) );
	}

	/**
	 * Register the Friends Blocks.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $blocks Optional. See BBoss_Component::blocks_init() for description.
	 */
	public function blocks_init( $blocks = array() ) {
		parent::blocks_init( array() );
	}
}
