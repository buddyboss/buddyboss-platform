<?php
/**
 * BuddyPress XProfile Loader.
 *
 * An extended profile component for users. This allows site admins to create
 * groups of fields for users to enter information about themselves.
 *
 * @package BuddyBoss\XProfile\Classes
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates our XProfile component.
 *
 * @since BuddyPress 1.5.0
 */
#[\AllowDynamicProperties]
class BP_XProfile_Component extends BP_Component {

	/**
	 * Profile field types.
	 *
	 * @since BuddyPress 1.5.0
	 * @var array
	 */
	public $field_types;

	/**
	 * The acceptable visibility levels for xprofile fields.
	 *
	 * @see bp_xprofile_get_visibility_levels()
	 *
	 * @since BuddyPress 1.6.0
	 * @var array
	 */
	public $visibility_levels = array();

	/**
	 * Start the xprofile component creation process.
	 *
	 * @since BuddyPress 1.5.0
	 */
	public function __construct() {
		parent::start(
			'xprofile',
			'Profile Fields',
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 20,
			)
		);

		$this->setup_hooks();
	}

	/**
	 * Include files.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param array $includes Array of files to include.
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'cssjs',
			'cache',
			'caps',
			'filters',
			'template',
			'functions',
			'repeaters',
			'widgets',
		);

		// Conditional includes.
		if ( bp_is_active( 'activity' ) ) {
			$includes[] = 'activity';
		}
		if ( bp_is_active( 'notifications' ) ) {
			$includes[] = 'notifications';
		}
		if ( bp_is_active( 'settings' ) ) {
			$includes[] = 'settings';
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

		// Bail if not on a user page.
		if ( ! bp_is_user() ) {
			return;
		}

		// User nav.
		if ( bp_is_profile_component() ) {
			require $this->path . 'bp-xprofile/screens/public.php';

			// Action - Delete avatar.
			if ( is_user_logged_in() && bp_is_user_change_avatar() && bp_is_action_variable( 'delete-avatar', 0 ) ) {
				require $this->path . 'bp-xprofile/actions/delete-avatar.php';
			}

			// Sub-nav items.
			if ( is_user_logged_in() &&
				in_array( bp_current_action(), array( 'edit', 'change-avatar', 'change-cover-image' ), true )
			) {
				require $this->path . 'bp-xprofile/screens/' . bp_current_action() . '.php';
			}
		}

		// Settings.
		if ( is_user_logged_in() && bp_is_user_settings_profile() ) {
			require $this->path . 'bp-xprofile/screens/settings-profile.php';
		}
	}

	/**
	 * Setup globals.
	 *
	 * The BP_XPROFILE_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param array $args Array of globals to set up.
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		// Define a slug, if necessary.
		if ( ! defined( 'BP_XPROFILE_SLUG' ) ) {
			define( 'BP_XPROFILE_SLUG', 'profile' );
		}

		// Assign the base group and fullname field names to constants
		// to use in SQL statements.
		// Defined conditionally to accommodate unit tests.
		if ( ! defined( 'BP_XPROFILE_BASE_GROUP_NAME' ) ) {
			define( 'BP_XPROFILE_BASE_GROUP_NAME', stripslashes( bp_core_get_root_option( 'avatar_default' ) ) );
		}

		if ( ! defined( 'BP_XPROFILE_FULLNAME_FIELD_NAME' ) ) {
			define( 'BP_XPROFILE_FULLNAME_FIELD_NAME', stripslashes( bp_core_get_root_option( 'bp-xprofile-fullname-field-name' ) ) );
		}

		/**
		 * Filters the supported field type IDs.
		 *
		 * @since BuddyPress 1.1.0
		 *
		 * @param array $value Array of IDs for the supported field types.
		 */
		$this->field_types = apply_filters( 'xprofile_field_types', array_keys( bp_xprofile_get_field_types() ) );

		// 'option' is a special case. It is not a top-level field, so
		// does not have an associated BP_XProfile_Field_Type class,
		// but it must be whitelisted.
		$this->field_types[] = 'option';

		// Register the visibility levels. See bp_xprofile_get_visibility_levels() to filter.
		$this->visibility_levels = array(
			'public'     => array(
				'id'    => 'public',
				'label' => __( 'Public', 'buddyboss' ),
			),
			'loggedin'   => array(
				'id'    => 'loggedin',
				'label' => __( 'All Members', 'buddyboss' ),
			),
		);

		if ( bp_is_active( 'friends' ) ) {
			$this->visibility_levels['friends'] = array(
				'id'    => 'friends',
				'label' => __( 'My Connections', 'buddyboss' ),
			);
		}

		$this->visibility_levels['adminsonly'] = array(
			'id'    => 'adminsonly',
			'label' => __( 'Only Me', 'buddyboss' ),
		);

		// Tables.
		$global_tables = array(
			'table_name_data'       => $bp->table_prefix . 'bp_xprofile_data',
			'table_name_visibility' => $bp->table_prefix . 'bb_xprofile_visibility',
			'table_name_groups'     => $bp->table_prefix . 'bp_xprofile_groups',
			'table_name_fields'     => $bp->table_prefix . 'bp_xprofile_fields',
			'table_name_meta'       => $bp->table_prefix . 'bp_xprofile_meta',
		);

		$meta_tables = array(
			'xprofile_group' => $bp->table_prefix . 'bp_xprofile_meta',
			'xprofile_field' => $bp->table_prefix . 'bp_xprofile_meta',
			'xprofile_data'  => $bp->table_prefix . 'bp_xprofile_meta',
		);

		$globals = array(
			'slug'                  => BP_XPROFILE_SLUG,
			'has_directory'         => false,
			'notification_callback' => 'xprofile_format_notifications',
			'global_tables'         => $global_tables,
			'meta_tables'           => $meta_tables,
		);

		parent::setup_globals( $globals );
	}

	/**
	 * Set up navigation.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 *
	 * @param array $main_nav Array of main nav items to set up.
	 * @param array $sub_nav  Array of sub nav items to set up.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		// Determine user to use.
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		$access       = bp_core_can_edit_settings();
		$slug         = bp_get_profile_slug();
		$profile_link = trailingslashit( $user_domain . $slug );

		// Add 'Profile' to the main navigation.
		$main_nav = array(
			'name'                => __( 'Profile', 'buddyboss' ),
			'slug'                => $slug,
			'position'            => 20,
			'screen_function'     => 'xprofile_screen_display_profile',
			'default_subnav_slug' => 'public',
			'item_css_id'         => $this->id,
		);

		// Add the subnav items to the profile.
		$sub_nav[] = array(
			'name'            => __( 'View', 'buddyboss' ),
			'slug'            => 'public',
			'parent_url'      => $profile_link,
			'parent_slug'     => $slug,
			'screen_function' => 'xprofile_screen_display_profile',
			'position'        => 10,
		);

		// Edit Profile.
		$sub_nav[] = array(
			'name'            => __( 'Edit', 'buddyboss' ),
			'slug'            => 'edit',
			'parent_url'      => $profile_link,
			'parent_slug'     => $slug,
			'screen_function' => 'xprofile_screen_edit_profile',
			'position'        => 20,
			'user_has_access' => $access,
		);

		// Change Avatar.
		if ( buddypress()->avatar->show_avatars && ! bp_disable_avatar_uploads() ) {
			$sub_nav[] = array(
				'name'            => __( 'Profile Photo', 'buddyboss' ),
				'slug'            => 'change-avatar',
				'parent_url'      => $profile_link,
				'parent_slug'     => $slug,
				'screen_function' => 'xprofile_screen_change_avatar',
				'position'        => 30,
				'user_has_access' => $access,
			);
		}

		// Change cover photo.
		if ( bp_displayed_user_use_cover_image_header() ) {
			$sub_nav[] = array(
				'name'            => __( 'Cover Photo', 'buddyboss' ),
				'slug'            => 'change-cover-image',
				'parent_url'      => $profile_link,
				'parent_slug'     => $slug,
				'screen_function' => 'xprofile_screen_change_cover_image',
				'position'        => 40,
				'user_has_access' => $access,
			);
		}

		// The Settings > Profile nav item can only be set up after
		// the Settings component has run its own nav routine.
		add_action( 'bp_settings_setup_nav', array( $this, 'setup_settings_nav' ) );

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the Settings > Profile nav item.
	 *
	 * Loaded in a separate method because the Settings component may not
	 * be loaded in time for BP_XProfile_Component::setup_nav().
	 *
	 * @since BuddyPress 2.1.0
	 */
	public function setup_settings_nav() {
		if ( ! bp_is_active( 'settings' ) ) {
			return;
		}

		// Determine user to use.
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		// Get the settings slug.
		$settings_slug = bp_get_settings_slug();

		bp_core_new_subnav_item(
			array(
				'name'            => __( 'Privacy', 'buddyboss' ),
				'slug'            => 'profile',
				'parent_url'      => trailingslashit( $user_domain . $settings_slug ),
				'parent_slug'     => $settings_slug,
				'screen_function' => 'bp_xprofile_screen_settings',
				'position'        => 30,
				'user_has_access' => bp_core_can_edit_settings(),
			),
			'members'
		);
	}

	/**
	 * Set up the Admin Bar.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param array $wp_admin_nav Admin Bar items.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() ) {

			// Profile link.
			$profile_link = trailingslashit( bp_loggedin_user_domain() . bp_get_profile_slug() );

			// Add the "Profile" sub menu.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'Profile', 'buddyboss' ),
				'href'   => $profile_link,
			);

			// View Profile.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-public',
				'title'    => __( 'View', 'buddyboss' ),
				'href'     => $profile_link,
				'position' => 10,
			);

			// Edit Profile.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-edit',
				'title'    => __( 'Edit', 'buddyboss' ),
				'href'     => trailingslashit( $profile_link . 'edit' ),
				'position' => 20,
			);

			// Edit Avatar.
			if ( ! bp_disable_avatar_uploads() && buddypress()->avatar->show_avatars ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-change-avatar',
					'title'    => __( 'Profile Photo', 'buddyboss' ),
					'href'     => trailingslashit( $profile_link . 'change-avatar' ),
					'position' => 30,
				);
			}

			if ( bp_displayed_user_use_cover_image_header() ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-change-cover-image',
					'title'    => __( 'Cover Photo', 'buddyboss' ),
					'href'     => trailingslashit( $profile_link . 'change-cover-image' ),
					'position' => 40,
				);
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Add custom hooks.
	 *
	 * @since BuddyPress 2.0.0
	 */
	public function setup_hooks() {
		add_filter( 'bp_settings_admin_nav', array( $this, 'setup_settings_admin_nav' ), 2 );
	}

	/**
	 * Sets up the title for pages and <title>.
	 *
	 * @since BuddyPress 1.5.0
	 */
	public function setup_title() {

		if ( bp_is_profile_component() ) {
			$bp = buddypress();

			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'My Profile', 'buddyboss' );
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar(
					array(
						'item_id' => bp_displayed_user_id(),
						'type'    => 'thumb',
						'alt'     => sprintf( __( 'Profile photo of %s', 'buddyboss' ), bp_get_displayed_user_fullname() ),
					)
				);
				$bp->bp_options_title  = bp_get_displayed_user_fullname();
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
				'bp_xprofile',
				'bp_xprofile_data',
				'bp_xprofile_fields',
				'bp_xprofile_groups',
				'xprofile_meta',
				'field_children_options',
			)
		);

		parent::setup_cache_groups();
	}

	/**
	 * Adds "Settings > Profile" subnav item under the "Settings" adminbar menu.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param array $wp_admin_nav The settings adminbar nav array.
	 * @return array
	 */
	public function setup_settings_admin_nav( $wp_admin_nav ) {

		// Setup the logged in user variables.
		$settings_link = trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() );

		// Add the "Profile" subnav item.
		$wp_admin_nav[] = array(
			'parent'   => 'my-account-' . buddypress()->settings->id,
			'id'       => 'my-account-' . buddypress()->settings->id . '-profile',
			'title'    => __( 'Privacy', 'buddyboss' ),
			'href'     => trailingslashit( $settings_link . 'profile' ),
			'position' => 30,
		);

		return $wp_admin_nav;
	}

	/**
	 * Init the BuddyBoss REST API.
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for description.
	 *
	 * @since BuddyBoss 1.3.5
	 */
	public function rest_api_init( $controllers = array() ) {
		parent::rest_api_init( array(
			'BP_REST_XProfile_Fields_Endpoint',
			'BP_REST_XProfile_Field_Groups_Endpoint',
			'BP_REST_XProfile_Data_Endpoint',
			'BP_REST_XProfile_Update_Endpoint',
			'BP_REST_XProfile_Repeater_Endpoint',
			'BP_REST_XProfile_Search_Form_Fields_Endpoint',
			'BP_REST_XProfile_Types_Endpoint',
		) );
	}

	/**
	 * Register the xProfile Blocks.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $blocks Optional. See BP_Component::blocks_init() for description.
	 */
	public function blocks_init( $blocks = array() ) {
		parent::blocks_init( array() );
	}
}
