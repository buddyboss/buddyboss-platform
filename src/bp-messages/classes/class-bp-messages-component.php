<?php
/**
 * BuddyBoss Messages Loader.
 *
 * A private messages component, for users to send messages to each other.
 *
 * @package BuddyBoss\Messages\Loader
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Implementation of BBoss_Component for the Messages component.
 *
 * @since BuddyPress 1.5.0
 */
#[\AllowDynamicProperties]
class BBoss_Messages_Component extends BBoss_Component {

	/**
	 * If this is true, the Message autocomplete will return friends only, unless
	 * this is set to false, in which any matching users will be returned.
	 *
	 * @since BuddyPress 1.5.0
	 * @var bool
	 */
	public $autocomplete_all;

	/**
	 * Start the messages component creation process.
	 *
	 * @since BuddyPress 1.5.0
	 */
	public function __construct() {
		parent::start(
			'messages',
			'Private Messages',
			bboss()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 50,
				'features'                 => array( 'star' ),
			)
		);
	}

	/**
	 * Include files.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param array $includes See {BBoss_Component::includes()} for details.
	 */
	public function includes( $includes = array() ) {

		// Files to include.
		$includes = array(
			'cssjs',
			'cache',
			'filters',
			'template',
			'functions',
			'widgets',
		);

		// Conditional includes.
		if ( bboss_is_active( 'notifications' ) ) {
			$includes[] = 'notifications';
		}
		if ( bboss_is_active( $this->id, 'star' ) ) {
			$includes[] = 'star';
		}
		if ( is_admin() ) {
			$includes[] = 'admin';
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

		if ( bboss_is_messages_component() ) {
			// Authenticated actions for archived threads.
			if ( is_user_logged_in() && 'archived' === bboss_current_action() && bboss_action_variable( 0 ) && 'view' === bboss_action_variable( 0 ) ) {
				require $this->path . 'bp-messages/screens/' . bboss_current_action() . '-' . bboss_action_variable( 0 ) . '.php';
			} elseif ( 'archived' === bboss_current_action() ) {
				require $this->path . 'bp-messages/actions/' . bboss_current_action() . '.php';
			}

			// Authenticated actions.
			if ( is_user_logged_in() &&
				in_array( bboss_current_action(), array( 'compose', 'notices', 'view' ), true )
			) {
				require $this->path . 'bp-messages/actions/' . bboss_current_action() . '.php';
			}

			// Authenticated action variables.
			if ( is_user_logged_in() && bboss_action_variable( 0 ) &&
				in_array( bboss_action_variable( 0 ), array( 'delete', 'read', 'unread', 'bulk-manage', 'bulk-delete' ), true )
			) {
				require $this->path . 'bp-messages/actions/' . bboss_action_variable( 0 ) . '.php';
			}

			// Authenticated actions - Star.
			if ( is_user_logged_in() && bboss_is_active( $this->id, 'star' ) ) {
				// Single action.
				if ( in_array( bboss_current_action(), array( 'star', 'unstar' ), true ) ) {
					require $this->path . 'bp-messages/actions/star.php';
				}

				// Bulk-manage.
				if ( bboss_is_action_variable( 'bulk-manage' ) ) {
					require $this->path . 'bp-messages/actions/bulk-manage-star.php';
				}
			}

			// Screens - User profile integration.
			if ( bboss_is_user() ) {
				require $this->path . 'bp-messages/screens/inbox.php';

				/*
				 * Nav items.
				 *
				 * 'view' is not a registered nav item, but we add a screen handler manually.
				 */
				if ( bboss_is_user_messages() && in_array( bboss_current_action(), array( 'compose', 'notices', 'view' ), true ) ) {
					require $this->path . 'bp-messages/screens/' . bboss_current_action() . '.php';
				}

				// Nav item - Starred.
				if ( bboss_is_active( $this->id, 'star' ) && bboss_is_current_action( bboss_get_messages_starred_slug() ) ) {
					require $this->path . 'bp-messages/screens/starred.php';
				}
			}
		}
	}

	/**
	 * Set up globals for the Messages component.
	 *
	 * The BP_MESSAGES_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param array $args Not used.
	 */
	public function setup_globals( $args = array() ) {
		$bp = bboss();

		// Define a slug, if necessary.
		if ( ! defined( 'BBOSS_MESSAGES_SLUG' ) ) {
			define( 'BBOSS_MESSAGES_SLUG', $this->id );
		}

		// Global tables for messaging component.
		$global_tables = array(
			'table_name_notices'    => $bp->table_prefix . 'bp_messages_notices',
			'table_name_messages'   => $bp->table_prefix . 'bp_messages_messages',
			'table_name_recipients' => $bp->table_prefix . 'bp_messages_recipients',
			'table_name_meta'       => $bp->table_prefix . 'bp_messages_meta',
		);

		// Metadata tables for messaging component.
		$meta_tables = array(
			'message' => $bp->table_prefix . 'bp_messages_meta',
		);

		$this->autocomplete_all = defined( 'BP_MESSAGES_AUTOCOMPLETE_ALL' );

		// All globals for messaging component.
		// Note that global_tables is included in this array.
		parent::setup_globals(
			array(
				'slug'                  => BBOSS_MESSAGES_SLUG,
				'has_directory'         => false,
				'notification_callback' => 'bboss_messages_format_notifications',
				'search_string'         => __( 'Search Messages&hellip;', 'buddyboss-platform' ),
				'global_tables'         => $global_tables,
				'meta_tables'           => $meta_tables,
			)
		);
	}

	/**
	 * Set up navigation for user pages.
	 *
	 * @param array $main_nav See {BBoss_Component::setup_nav()} for details.
	 * @param array $sub_nav  See {BBoss_Component::setup_nav()} for details.
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
		$slug          = bboss_get_messages_slug();
		$messages_link = trailingslashit( $user_domain . $slug );

		// Only grab count if we're on a user page and current user has access.
		if ( bboss_is_user() && bboss_user_has_access() ) {
			$count     = bboss_get_total_unread_messages_count( bboss_displayed_user_id() );
			$class     = ( 0 === $count ) ? 'no-count' : 'count';
			$nav_name  = __( 'Messages', 'buddyboss-platform' );
			$nav_name .= sprintf(
				' <span class="%s">%s</span>',
				esc_attr( $class ),
				bboss_core_number_format( $count )
			);
		} else {
			$nav_name = __( 'Messages', 'buddyboss-platform' );
		}

		// Add 'Messages' to the main navigation.
		$main_nav = array(
			'name'                    => $nav_name,
			'slug'                    => $slug,
			'position'                => 50,
			'show_for_displayed_user' => $access,
			'screen_function'         => 'bboss_messages_screen_inbox',
			'default_subnav_slug'     => 'inbox',
			'item_css_id'             => $this->id,
		);

		// Add the subnav items to the profile.
		$sub_nav[] = array(
			'name'            => __( 'Messages', 'buddyboss-platform' ),
			'slug'            => 'inbox',
			'parent_url'      => $messages_link,
			'parent_slug'     => $slug,
			'screen_function' => 'bboss_messages_screen_inbox',
			'position'        => 10,
			'user_has_access' => $access,
		);

		// Show certain screens only if the current user is the displayed user.
		if ( bboss_is_my_profile() ) {

			if ( bboss_is_user_messages() && bboss_is_active( 'notifications' ) ) {
				// Show "Actions" on the logged-in user's profile only.
				$sub_nav[] = array(
					'name'            => __( 'Actions', 'buddyboss-platform' ),
					'slug'            => 'compose-action',
					'parent_url'      => 'javascript:void(0);',
					'parent_slug'     => $slug,
					'screen_function' => 'messages_screen_compose_action',
					'position'        => 29,
					'user_has_access' => $access,
				);
			}

			// Show "Compose" on the logged-in user's profile only.
			$sub_nav[] = array(
				'name'            => __( 'New Message', 'buddyboss-platform' ),
				'slug'            => 'compose',
				'parent_url'      => $messages_link,
				'parent_slug'     => $slug,
				'screen_function' => 'bboss_messages_screen_compose',
				'position'        => 30,
				'user_has_access' => $access,
			);

			// Show "Archived" on the logged-in user's profile only.
			$sub_nav[] = array(
				'name'            => __( 'Archived', 'buddyboss-platform' ),
				'slug'            => 'archived',
				'parent_url'      => $messages_link,
				'parent_slug'     => $slug,
				'screen_function' => 'messages_screen_archived',
				'position'        => 35,
				'user_has_access' => $access,
			);

			/*
			 * Show "Notices" on the logged-in user's profile only
			 * and then only if the user can create notices.
			 */
			if ( bboss_current_user_can( 'bp_moderate' ) ) {
				$sub_nav[] = array(
					'name'            => __( 'Notices', 'buddyboss-platform' ),
					'slug'            => 'notices',
					'parent_url'      => $messages_link,
					'parent_slug'     => $slug,
					'screen_function' => 'bboss_messages_screen_notices',
					'position'        => 90,
					'user_has_access' => true,
				);
			}
		}

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the Toolbar.
	 *
	 * @param array $wp_admin_nav See {BBoss_Component::setup_admin_bar()} for details.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables.
			$messages_link = trailingslashit( bboss_loggedin_user_domain() . bboss_get_messages_slug() );

			// Unread message count.
			$count = bboss_messages_get_unread_count( bboss_loggedin_user_id() );
			if ( ! empty( $count ) ) {
				$title = sprintf(
					/* translators: %s: Unread message count for the current user */
					__( 'Messages %s', 'buddyboss-platform' ),
					'<span class="count">' . bboss_core_number_format( $count ) . '</span>'
				);
				$inbox = sprintf(
					/* translators: %s: Unread message count for the current user */
					__( 'Messages %s', 'buddyboss-platform' ),
					'<span class="count">' . bboss_core_number_format( $count ) . '</span>'
				);
			} else {
				$title = __( 'Messages', 'buddyboss-platform' );
				$inbox = __( 'Messages', 'buddyboss-platform' );
			}

			// Add main Messages menu.
			$wp_admin_nav[] = array(
				'parent' => bboss()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => $title,
				'href'   => $messages_link,
			);

			// Inbox.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-inbox',
				'title'    => $inbox,
				'href'     => $messages_link,
				'position' => 10,
			);

			// Compose Message.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-compose',
				'title'    => __( 'New Message', 'buddyboss-platform' ),
				'href'     => trailingslashit( $messages_link . 'compose' ),
				'position' => 30,
			);

			// Site Wide Notices.
			if ( bboss_current_user_can( 'bp_moderate' ) ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-notices',
					'title'    => __( 'Site Notices', 'buddyboss-platform' ),
					'href'     => trailingslashit( $messages_link . 'notices' ),
					'position' => 90,
				);
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>.
	 */
	public function setup_title() {

		if ( bboss_is_messages_component() ) {
			$bp = bboss();

			if ( bboss_is_my_profile() ) {
				$bp->bp_options_title = __( 'My Messages', 'buddyboss-platform' );
			} else {
				$bp->bp_options_avatar = bboss_core_fetch_avatar(
					array(
						'item_id' => bboss_displayed_user_id(),
						'type'    => 'thumb',
						/* translators: %s: member display name. */
						'alt'     => sprintf( __( 'Profile photo of %s', 'buddyboss-platform' ), bboss_get_displayed_user_fullname() ),
					)
				);
				$bp->bp_options_title  = bboss_get_displayed_user_fullname();
			}
		}

		parent::setup_title();
	}

	/**
	 * Setup cache groups
	 *
	 * @since BuddyPress 2.2.0
	 * @since BuddyBoss 3.6.1 Added the bb_messages_disabled_group_threads global cache group.
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups(
			array(
				'bp_messages',
				'bp_messages_threads',
				'bp_messages_unread_count',
				'message_meta',
				'bb_messages_disabled_group_threads',
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
		parent::rest_api_init(
			array(
				'BBoss_REST_Messages_Endpoint',
				'BBoss_REST_Group_Messages_Endpoint',
				'BBoss_REST_Messages_Actions_Endpoint',
			)
		);
	}

	/**
	 * Register the Messages Blocks.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $blocks Optional. See BBoss_Component::blocks_init() for description.
	 */
	public function blocks_init( $blocks = array() ) {
		parent::blocks_init( array() );
	}
}
