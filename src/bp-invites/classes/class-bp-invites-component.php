<?php
/**
 * BuddyBoss Invites Component Class.
 *
 * @package BuddyBoss\Invites\Loader
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates Invites component.
 *
 * @since BuddyBoss 1.0.0
 */
#[\AllowDynamicProperties]
class BP_Invites_Component extends BP_Component {


	/**
	 * Default invite extension.
	 *
	 * @since BuddyBoss 1.0.0
	 * @todo Is this used anywhere? Is this a duplicate of $default_extension?
	 * @var string
	 */
	var $default_component;

	/**
	 * Default invite extension.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var string
	 */
	public $default_extension;

	/**
	 * Illegal invite names/slugs.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var array
	 */
	public $forbidden_names;

	/**
	 * Start the invites component creation process.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		parent::start(
			'invites',
			__( 'Sent Invites', 'buddyboss' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 100,
				'search_query_arg'         => 'invites_search',
			)
		);

	}

	/**
	 * Include Invites component files.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @see BP_Component::includes() for a description of arguments.
	 *
	 * @param array $includes See BP_Component::includes() for a description.
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'filters',
			'template',
			'functions',
		);

		// Conditional includes.
		if ( bp_is_active( 'activity' ) ) {
			$includes[] = 'activity';
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
	 * @since BuddyBoss 1.0.0
	 */
	public function late_includes() {
		// Bail if PHPUnit is running.
		if ( defined( 'BP_TESTS_DIR' ) ) {
			return;
		}

		// Bail if not on Settings component.
		if ( ! bp_is_invites_component() ) {
			return;
		}

		$actions = array( 'invites', 'sent-invites', 'send-invites' );

		// Authenticated actions.
		if ( is_user_logged_in() ) {
			if ( ! bp_current_action() || bp_is_current_action( 'invites' ) ) {
				require $this->path . 'bp-invites/actions/invites.php';

				// Specific to post requests.
			} elseif ( bp_is_post_request() && in_array( bp_current_action(), $actions, true ) && file_exists( $this->path . 'bp-invites/actions/' . bp_current_action() . '.php' ) ) {
				require $this->path . 'bp-invites/actions/' . bp_current_action() . '.php';
			}

			if ( is_user_logged_in() &&
				 in_array( bp_current_action(), array( 'revoke-invite' ), true )
			) {
				require $this->path . 'bp-invites/actions/' . bp_current_action() . '.php';
			}

			if ( is_user_logged_in() &&
				 in_array( bp_current_action(), array( 'revoke-invite-admin' ), true )
			) {
				require $this->path . 'bp-invites/actions/' . bp_current_action() . '.php';
			}
		} else {
			bp_core_no_access();
			return;
		}

		// Screens - User profile integration.
		if ( bp_is_user() ) {
			require $this->path . 'bp-invites/screens/send-invites.php';

			// Sub-nav items.
			if ( in_array( bp_current_action(), $actions, true ) ) {
				require $this->path . 'bp-invites/screens/' . bp_current_action() . '.php';
			}
		}

	}

	/**
	 * Set up component global data.
	 *
	 * The BP_INVITES_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @see BP_Component::setup_globals() for a description of arguments.
	 *
	 * @param array $args See BP_Component::setup_globals() for a description.
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		// Define a slug, if necessary.
		if ( ! defined( 'BP_INVITES_SLUG' ) ) {
			define( 'BP_INVITES_SLUG', $this->id );
		}

		// All globals for invites component.
		// Note that global_tables is included in this array.
		$args = array(
			'slug'          => BP_INVITES_SLUG,
			'root_slug'     => isset( $bp->pages->invites->slug ) ? $bp->pages->invites->slug : BP_INVITES_SLUG,
			'has_directory' => false,
		);

		parent::setup_globals( $args );

		/* Single Invite Globals **********************************************/

	}

	/**
	 * Set up canonical stack for this component.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function setup_canonical_stack() {
		if ( ! bp_is_invites_component() ) {
			return;
		}

	}

	/**
	 * Set up component navigation.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @see BP_Component::setup_nav() for a description of arguments.
	 *
	 * @param array $main_nav Optional. See BP_Component::setup_nav() for description.
	 * @param array $sub_nav  Optional. See BP_Component::setup_nav() for description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		if ( is_user_logged_in() ) {
			// Determine user to use.
			if ( bp_displayed_user_domain() ) {
				$user_domain = bp_displayed_user_domain();
			} elseif ( bp_loggedin_user_domain() ) {
				$user_domain = bp_loggedin_user_domain();
			} else {
				return;
			}

			$nav_name     = __( 'Email Invites', 'buddyboss' );
			$slug         = bp_get_invites_slug();
			$access       = bp_core_can_edit_settings();
			$invites_link = trailingslashit( $user_domain . $slug );

			if ( bp_allow_user_to_send_invites() ) {

				// Add 'Send Invites' to the main navigation.
				$main_nav = array(
					'name'                    => $nav_name,
					'slug'                    => $slug,
					'position'                => 90,
					'screen_function'         => 'bp_invites_screen_send_invite',
					'default_subnav_slug'     => 'send-invites',
					'show_for_displayed_user' => $access,
					'item_css_id'             => $this->id,
				);

				// Add the Invite by Email nav item.
				$sub_nav[] = array(
					'name'            => __( 'Send Invites', 'buddyboss' ),
					'slug'            => 'send-invites',
					'parent_url'      => $invites_link,
					'parent_slug'     => $slug,
					'screen_function' => 'bp_invites_screen_send_invite',
					'user_has_access' => $access,
					'position'        => 10,
					'item_css_id'     => 'invites-send-invite',
				);

				// Add the Sent Invites nav item.
				$sub_nav[] = array(
					'name'            => __( 'Sent Invites', 'buddyboss' ),
					'slug'            => 'sent-invites',
					'parent_url'      => $invites_link,
					'parent_slug'     => $slug,
					'screen_function' => 'bp_invites_screen_sent_invite',
					'user_has_access' => $access,
					'position'        => 20,
					'item_css_id'     => 'invites-sent-invites',
				);

				parent::setup_nav( $main_nav, $sub_nav );

			}
		}
	}

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @see BP_Component::setup_nav() for a description of the $wp_admin_nav
	 *      parameter array.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar() for a description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() ) {

			if ( true === bp_allow_user_to_send_invites() ) {
				// Setup the logged in user variables.
				$invites_link = trailingslashit( bp_loggedin_user_domain() . bp_get_invites_slug() );

				$title = __( 'Email Invites', 'buddyboss' );

				// Add the "My Account" sub menus.
				$wp_admin_nav[] = array(
					'parent' => buddypress()->my_account_menu_id,
					'id'     => 'my-account-' . $this->id,
					'title'  => $title,
					'href'   => $invites_link,
				);

				// Invite by Email
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-invites',
					'title'    => __( 'Send Invites', 'buddyboss' ),
					'href'     => $invites_link,
					'position' => 10,
				);

				// Sent Invites
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-sent',
					'title'    => __( 'Sent Invites', 'buddyboss' ),
					'href'     => $invites_link . 'sent-invites',
					'position' => 20,
				);
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function setup_title() {

		if ( bp_is_invites_component() ) {
			$bp = buddypress();

		}

		parent::setup_title();
	}

	public function register_post_types() {

		// Register invite custom post type.
		register_post_type(
			bp_get_invite_post_type(),
			apply_filters(
				'bp_invite_post_type',
				array(
					'description'        => __( 'BuddyBoss Invites', 'buddyboss' ),
					'labels'             => bp_get_invite_post_type_labels(),
					'public'             => false,
					'publicly_queryable' => bp_current_user_can( 'bp_moderate' ),
					'query_var'          => false,
					'rewrite'            => false,
					'show_in_admin_bar'  => false,
					'show_in_menu'       => false,
					'map_meta_cap'       => true,
					'menu_icon'          => 'dashicons-email',
					// 'menu_position'      => 27,
					'show_in_rest'       => true,
					'capabilities'       => array(
						'create_posts' => 'do_not_allow', // false < WP 4.5, credit @Ewout
					),
					'show_ui'            => bp_current_user_can( 'bp_moderate' ),
					'supports'           => bp_get_invite_post_type_supports(),
				)
			)
		);

		parent::register_post_types();
	}

	/**
	 * Init the BuddyBoss REST API.
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for description.
	 *
	 * @since BuddyBoss 1.3.5
	 */
	public function rest_api_init( $controllers = array() ) {
		parent::rest_api_init( array( 'BP_REST_Invites_Endpoint' ) );
	}
}
