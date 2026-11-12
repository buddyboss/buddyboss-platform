<?php
/**
 * BuddyPress Member Loader.
 *
 * @package BuddyBoss\Members
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Defines the BuddyPress Members Component.
 *
 * @since BuddyPress 1.5.0
 */
#[\AllowDynamicProperties]
class BBoss_Members_Component extends BBoss_Component {

	/**
	 * Profile types.
	 *
	 * @see bboss_register_member_type()
	 *
	 * @since BuddyPress 2.2.0
	 * @var array
	 */
	public $types = array();

	/**
	 * Start the members component creation process.
	 *
	 * @since BuddyPress 1.5.0
	 */
	public function __construct() {
		parent::start(
			'members',
			'Members',
			bboss()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 10,
				'search_query_arg'         => 'members_search',
			)
		);
	}

	/**
	 * Include bp-members files.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @see BBoss_Component::includes() for description of parameters.
	 *
	 * @param array $includes See {@link BBoss_Component::includes()}.
	 */
	public function includes( $includes = array() ) {

		// Always include these files.
		$includes = array(
			'filters',
			'template',
			'adminbar',
			'functions',
			'widgets',
			'cache',
		);

		if ( bboss_is_active( 'activity' ) ) {
			$includes[] = 'activity';
		}

		// Include these only if in admin.
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

		// Members.
		if ( bboss_is_members_component() ) {
			// Actions - Random member handler.
			if ( isset( $_GET['random-member'] ) ) {
				require $this->path . 'bp-members/actions/random.php';
			}

			// Screens - Directory.
			if ( bboss_is_members_directory() ) {
				require $this->path . 'bp-members/screens/directory.php';
			}
		}

		// Members - User main nav screen.
		if ( bboss_is_user() ) {
			require $this->path . 'bp-members/screens/profile.php';
		}

		// Members - Theme compatibility.
		if ( bboss_is_members_component() || bboss_is_user() ) {
			new BBoss_Members_Theme_Compat();
		}

		// Registration / Activation.
		if ( bboss_is_register_page() || bboss_is_activation_page() ) {
			if ( bboss_is_register_page() ) {
				require $this->path . 'bp-members/screens/register.php';
			} else {
				require $this->path . 'bp-members/screens/activate.php';
			}

			// Theme compatibility.
			new BBoss_Registration_Theme_Compat();
		}
	}

	/**
	 * Set up bp-members global settings.
	 *
	 * The BP_MEMBERS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @see BBoss_Component::setup_globals() for description of parameters.
	 *
	 * @param array $args See {@link BBoss_Component::setup_globals()}.
	 */
	public function setup_globals( $args = array() ) {
		global $wpdb;

		$bp = bboss();

		/** Component Globals ************************************************
		 */

		// Define a slug, as a fallback for backpat.
		if ( ! defined( 'BBOSS_MEMBERS_SLUG' ) ) {
			define( 'BBOSS_MEMBERS_SLUG', $this->id );
		}

		// Fetch the default directory title.
		$default_directory_titles = bboss_core_get_directory_page_default_titles();
		$default_directory_title  = $default_directory_titles[ $this->id ];

		// Override any passed args.
		$args = array(
			'slug'            => BBOSS_MEMBERS_SLUG,
			'root_slug'       => isset( $bp->pages->members->slug ) ? $bp->pages->members->slug : BBOSS_MEMBERS_SLUG,
			'has_directory'   => true,
			'directory_title' => isset( $bp->pages->members->title ) ? $bp->pages->members->title : $default_directory_title,
			'search_string'   => __( 'Search Members&hellip;', 'buddyboss' ),
			'global_tables'   => array(
				'table_name_last_activity' => bboss_core_get_table_prefix() . 'bp_activity',
				'table_name_signups'       => $wpdb->base_prefix . 'signups', // Signups is a global WordPress table.
			),
		);

		parent::setup_globals( $args );

		/** Logged in user ***************************************************
		 */

		// The core userdata of the user who is currently logged in.
		$bp->loggedin_user->userdata = bboss_core_get_core_userdata( bboss_loggedin_user_id() );

		// Fetch the full name for the logged in user.
		$bp->loggedin_user->fullname = isset( $bp->loggedin_user->userdata->display_name ) ? $bp->loggedin_user->userdata->display_name : '';

		// Hits the DB on single WP installs so get this separately.
		$bp->loggedin_user->is_super_admin = $bp->loggedin_user->is_site_admin = is_super_admin( bboss_loggedin_user_id() );

		// The domain for the user currently logged in. eg: http://example.com/members/andy.
		$bp->loggedin_user->domain = bboss_core_get_user_domain( bboss_loggedin_user_id() );

		/** Displayed user ***************************************************
		 */

		// The core userdata of the user who is currently being displayed.
		$bp->displayed_user->userdata = bboss_core_get_core_userdata( bboss_displayed_user_id() );

		// Fetch the full name displayed user.
		$bp->displayed_user->fullname = isset( $bp->displayed_user->userdata->display_name ) ? $bp->displayed_user->userdata->display_name : '';

		// The domain for the user currently being displayed.
		$bp->displayed_user->domain = bboss_core_get_user_domain( bboss_displayed_user_id() );

		// Initialize the nav for the members component.
		$this->nav = new BBoss_Core_Nav();

		// If A user is displayed, check if there is a front template
		if ( bboss_get_displayed_user() ) {
			$bp->displayed_user->front_template = bboss_displayed_user_get_front_template();
		}

		/** Signup ***********************************************************
		 */

		$bp->signup = new stdClass();

		/** Profiles Fallback ************************************************
		 */

		// Only stub the profile globals when no xProfile component was loaded.
		// xProfile is a required component, but bp_is_active( 'xprofile' ) is
		// false on the first request after activation (nothing is stored in
		// bp-active-components yet), and replacing the loaded component here
		// would drop its table names.
		if ( ! bboss_is_active( 'xprofile' ) && ! isset( $bp->profile->id ) ) {
			$bp->profile       = new stdClass();
			$bp->profile->slug = 'profile';
			$bp->profile->id   = 'profile';
		}
	}

	/**
	 * Set up canonical stack for this component.
	 *
	 * @since BuddyPress 2.1.0
	 */
	public function setup_canonical_stack() {
		$bp = bboss();

		/** Default Profile Component ****************************************
		 */
		if ( bboss_displayed_user_has_front_template() && bboss_is_my_profile() ) {
			$bp->default_component = 'front';
		} elseif ( defined( 'BP_DEFAULT_COMPONENT' ) && BP_DEFAULT_COMPONENT ) {
			$bp->default_component = BP_DEFAULT_COMPONENT;
		} elseif ( bboss_is_active( 'xprofile' ) ) {
			$bp->default_component = ( 'xprofile' === $bp->profile->id ) ? 'profile' : $bp->profile->id;
		} elseif ( bboss_is_active( 'activity' ) && isset( $bp->pages->activity ) ) {
			$bp->default_component = bboss_get_activity_slug();
		} else {
			$bp->default_component = ( 'xprofile' === $bp->profile->id ) ? 'profile' : $bp->profile->id;
		}

		// Get the default tab based on the customizer settings.
		$customizer_option = 'user_default_tab';
		$default_tab       = '';

		if ( function_exists( 'bboss_nouveau_get_temporary_setting' ) && function_exists( 'bboss_nouveau_get_appearance_settings' ) ) {
			$default_tab = bboss_nouveau_get_temporary_setting( $customizer_option, bboss_nouveau_get_appearance_settings( $customizer_option ) );
		}
		$default_tab = bboss_is_active( $default_tab ) ? $default_tab : 'profile';

		/**
		 * - Enable Photos as default user profile page.
		 */
		if ( 'media' === $default_tab ) {
			$default_tab = 'photos';
		}

		if ( 'document' === $default_tab ) {
			$default_tab = 'documents';
		}

		if ( 'video' === $default_tab ) {
			$default_tab = 'videos';
		}

		// Check if the default tab is hidden by admin in Navigation Order settings.
		if ( function_exists( 'bboss_nouveau_get_appearance_settings' ) ) {
			$hidden_tabs = bboss_nouveau_get_appearance_settings( 'user_nav_hide' );
			if ( is_array( $hidden_tabs ) && in_array( $default_tab, $hidden_tabs, true ) ) {
				// Fall back to the first visible tab in saved order, or 'profile' as last resort.
				$default_tab = 'profile';
				$nav_order   = bboss_nouveau_get_appearance_settings( 'user_nav_order' );
				if ( is_array( $nav_order ) ) {
					foreach ( $nav_order as $slug ) {
						if ( ! in_array( $slug, $hidden_tabs, true ) ) {
							$default_tab = $slug;
							break;
						}
					}
				}
			}
		}

		$bp->default_component = apply_filters( 'bp_member_default_component', ( '' === $default_tab ) ? $bp->default_component : $default_tab );

		/** Canonical Component Stack ****************************************
		 */

		if ( bboss_displayed_user_id() ) {
			$bp->canonical_stack['base_url'] = bboss_displayed_user_domain();

			if ( bboss_current_component() ) {
				$bp->canonical_stack['component'] = bboss_current_component();
			}

			if ( bboss_current_action() ) {
				$bp->canonical_stack['action'] = bboss_current_action();
			}

			if ( ! empty( $bp->action_variables ) ) {
				$bp->canonical_stack['action_variables'] = bboss_action_variables();
			}

			// Looking at the single member root/home, so assume the default.
			if ( ! bboss_current_component() ) {
				$bp->current_component = $bp->default_component;

				// The canonical URL will not contain the default component.
			} elseif ( bboss_is_current_component( $bp->default_component ) && ! bboss_current_action() ) {
				unset( $bp->canonical_stack['component'] );
			}

			// If we're on a spammer's profile page, only users with the 'bp_moderate' cap
			// can view subpages on the spammer's profile.
			//
			// users without the cap trying to access a spammer's subnav page will get
			// redirected to the root of the spammer's profile page.  this occurs by
			// by removing the component in the canonical stack.
			if ( bboss_is_user_spammer( bboss_displayed_user_id() ) && ! bboss_current_user_can( 'bp_moderate' ) ) {
				unset( $bp->canonical_stack['component'] );
			}

			// If we're on a suspender's profile page, only users with the 'bp_moderate' cap
			// can view subpages on the spammer's profile.
			//
			// users without the cap trying to access a spammer's subnav page will get
			// redirected to the root of the suspender's profile page.  this occurs by
			// by removing the component in the canonical stack.
			if (
				bboss_is_active( 'moderation' ) &&
				bboss_moderation_is_user_suspended( bboss_displayed_user_id() ) &&
				! bboss_current_user_can( 'bp_moderate' ) &&
				! bboss_is_single_activity()
			) {
				unset( $bp->canonical_stack['component'] );
			}
		}
	}

	/**
	 * Set up fall-back component navigation if XProfile is inactive.
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

		// Don't set up navigation if there's no member.
		if ( ! is_user_logged_in() && ! bboss_is_user() ) {
			return;
		}

		$is_xprofile_active = bboss_is_active( 'xprofile' );

		// Bail if XProfile component is active and there's no custom front page for the user.
		if ( ! bboss_displayed_user_has_front_template() && $is_xprofile_active ) {
			return;
		}

		// Determine user to use.
		if ( bboss_displayed_user_domain() ) {
			$user_domain = bboss_displayed_user_domain();
		} elseif ( bboss_loggedin_user_domain() ) {
			$user_domain = bboss_loggedin_user_domain();
		} else {
			return;
		}

		// Set slug to profile in case the xProfile component is not active
		$slug = bboss_get_profile_slug();

		// Defaults to empty navs
		$this->main_nav = array();
		$this->sub_nav  = array();

		if ( ! $is_xprofile_active ) {
			$this->main_nav = array(
				'name'                => __( 'Profile', 'buddyboss' ),
				'slug'                => $slug,
				'position'            => 10,
				'screen_function'     => 'bboss_members_screen_display_profile',
				'default_subnav_slug' => 'public',
				'item_css_id'         => bboss()->profile->id,
			);
		}

		/**
		 * Setup the subnav items for the member profile.
		 *
		 * This is required in case there's a custom front or in case the xprofile component
		 * is not active.
		 */
		$this->sub_nav = array(
			'name'            => __( 'View', 'buddyboss' ),
			'slug'            => 'public',
			'parent_url'      => trailingslashit( $user_domain . $slug ),
			'parent_slug'     => $slug,
			'screen_function' => 'bboss_members_screen_display_profile',
			'position'        => 10,
		);

		/**
		 * If there's a front template the members component nav
		 * will be there to display the user's front page.
		 */
		if ( bboss_displayed_user_has_front_template() ) {
			$main_nav = array(
				'name'                => __( 'Dashboard', 'buddyboss' ),
				'slug'                => 'front',
				'position'            => 5,
				'screen_function'     => 'bboss_members_screen_display_profile',
				'default_subnav_slug' => 'public',
			);

			// We need a dummy subnav for the front page to load.
			$front_subnav                = $this->sub_nav;
			$front_subnav['parent_slug'] = 'front';

			// In case the subnav is displayed in the front template
			$front_subnav['parent_url'] = trailingslashit( $user_domain . 'front' );

			// Set the subnav
			$sub_nav[] = $front_subnav;

			/**
			 * If the profile component is not active, we need to create a new
			 * nav to display the WordPress profile.
			 */
			if ( ! $is_xprofile_active ) {
				add_action( 'bp_members_setup_nav', array( $this, 'setup_profile_nav' ) );
			}

			/**
			 * If there's no front template and xProfile is not active, the members
			 * component nav will be there to display the WordPress profile
			 */
		} else {
			$main_nav  = $this->main_nav;
			$sub_nav[] = $this->sub_nav;
		}

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up a profile nav in case the xProfile
	 * component is not active and a front template is
	 * used.
	 *
	 * @since BuddyPress 2.6.0
	 */
	public function setup_profile_nav() {
		if ( empty( $this->main_nav ) || empty( $this->sub_nav ) ) {
			return;
		}

		// Add the main nav
		bboss_core_new_nav_item( $this->main_nav, 'members' );

		// Add the sub nav item.
		bboss_core_new_subnav_item( $this->sub_nav, 'members' );
	}

	/**
	 * Set up the title for pages and <title>.
	 *
	 * @since BuddyPress 1.5.0
	 */
	public function setup_title() {
		$bp = bboss();

		if ( bboss_is_my_profile() ) {
			$bp->bp_options_title = __( 'You', 'buddyboss' );
		} elseif ( bboss_is_user() ) {
			$bp->bp_options_title  = bboss_get_displayed_user_fullname();
			$bp->bp_options_avatar = bboss_core_fetch_avatar(
				array(
					'item_id' => bboss_displayed_user_id(),
					'type'    => 'thumb',
					/* translators: %s: displayed user name. */
					'alt'     => sprintf( __( 'Profile photo of %s', 'buddyboss' ), $bp->bp_options_title ),
				)
			);
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
				'bp_last_activity',
				'bp_member_type',
				'bp_member_member_type',
				'bp_member',
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
		$controllers = array(
			/**
			 * As the Members component is always loaded,
			 * let's register the Components endpoint here.
			 */
			'BBoss_REST_Components_Endpoint',
			'BBoss_REST_Settings_Endpoint',
			'BBoss_REST_Members_Endpoint',
			'BBoss_REST_Members_Permissions_Endpoint',
			'BBoss_REST_Members_Actions_Endpoint',
			'BBoss_REST_Members_Details_Endpoint',
			'BBoss_REST_Attachments_Member_Avatar_Endpoint',
			'BBoss_REST_Subscriptions_Endpoint',
		);

		if ( function_exists( 'bboss_core_get_suggestions' ) ) {
			$controllers[] = 'BBoss_REST_Mention_Endpoint';
		}

		if ( bboss_is_active( 'members', 'cover_image' ) || bboss_is_active( 'xprofile', 'cover_image' ) ) {
			$controllers[] = 'BBoss_REST_Attachments_Member_Cover_Endpoint';
		}

		if ( bboss_get_signup_allowed() ) {
			$controllers[] = 'BBoss_REST_Signup_Endpoint';
		}

		parent::rest_api_init( $controllers );
	}

	/**
	 * Register the Members Blocks.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $blocks Optional. See BBoss_Component::blocks_init() for description.
	 */
	public function blocks_init( $blocks = array() ) {
		parent::blocks_init( array() );
	}
}
