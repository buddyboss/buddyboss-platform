<?php
/**
 * BuddyPress Members Admin
 *
 * @package BuddyBoss\Members\Admin
 * @since BuddyPress 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Members_Admin' ) ) :

	/**
	 * Load Members admin area.
	 *
	 * @since BuddyPress 2.0.0
	 */
	#[\AllowDynamicProperties]
	class BP_Members_Admin {

		/** Directory *************************************************************/

		/**
		 * Path to the BP Members Admin directory.
		 *
		 * @var string $admin_dir
		 */
		public $admin_dir = '';

		/** URLs ******************************************************************/

		/**
		 * URL to the BP Members Admin directory.
		 *
		 * @var string $admin_url
		 */
		public $admin_url = '';

		/**
		 * URL to the BP Members Admin CSS directory.
		 *
		 * @var string $css_url
		 */
		public $css_url = '';

		/**
		 * URL to the BP Members Admin JS directory.
		 *
		 * @var string
		 */
		public $js_url = '';

		/** Other *****************************************************************/

		/**
		 * Screen id for edit user's profile page.
		 *
		 * @var string
		 */
		public $user_page = '';

		/**
		 * Setup BP Members Admin.
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @return BP_Members_Admin
		 */
		public static function register_members_admin() {
			if ( ! is_admin() ) {
				return;
			}

			$bp = buddypress();

			if ( empty( $bp->members->admin ) ) {
				$bp->members->admin = new self();
			}

			return $bp->members->admin;
		}

		/**
		 * Constructor method.
		 *
		 * @since BuddyPress 2.0.0
		 */
		public function __construct() {
			$this->setup_globals();
			$this->setup_actions();
		}

		/**
		 * Set admin-related globals.
		 *
		 * @since BuddyPress 2.0.0
		 */
		private function setup_globals() {
			$bp = buddypress();

			// Paths and URLs
			$this->admin_dir = trailingslashit( $bp->plugin_dir . 'bp-members/admin' ); // Admin path.
			$this->admin_url = trailingslashit( $bp->plugin_url . 'bp-members/admin' ); // Admin URL.
			$this->css_url   = trailingslashit( $this->admin_url . 'css' ); // Admin CSS URL.
			$this->js_url    = trailingslashit( $this->admin_url . 'js' ); // Admin CSS URL.

			// Capability depends on config.
			$this->capability = bp_core_do_network_admin() ? 'manage_network_users' : 'edit_users';

			// The Edit Profile Screen id.
			$this->user_page = '';

			// The Show Profile Screen id.
			$this->user_profile = is_network_admin() ? 'users' : 'profile';

			// The current user id.
			$this->current_user_id = get_current_user_id();

			// The user id being edited.
			$this->user_id = 0;

			// Is a member editing their own profile.
			$this->is_self_profile = false;

			// The screen ids to load specific css for.
			$this->screen_id = array();

			// The stats metabox default position.
			$this->stats_metabox = new StdClass();

			// BuddyPress edit user's profile args.
			$this->edit_profile_args = array( 'page' => 'bp-profile-edit' );
			$this->edit_profile_url  = '';
			$this->edit_url          = '';

			// Data specific to signups.
			$this->users_page   = '';
			$this->signups_page = '';
			$this->users_url    = bp_get_admin_url( 'users.php' );
			$this->users_screen = bp_core_do_network_admin() ? 'users-network' : 'users';

			// Specific config: BuddyPress is not network activated.
			$this->subsite_activated = (bool) is_multisite() && ! bp_is_network_activated();

			// When BuddyPress is not network activated, only Super Admin can moderate signups.
			if ( ! empty( $this->subsite_activated ) ) {
				$this->capability = 'manage_network_users';
			}
		}

		/**
		 * Set admin-related actions and filters.
		 *
		 * @since BuddyPress 2.0.0
		 */
		private function setup_actions() {

			/** Extended Profile *************************************************
			 */

			// Enqueue all admin JS and CSS.
			add_action( 'bp_admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			// Add some page specific output to the <head>.
			add_action( 'bp_admin_head', array( $this, 'admin_head' ), 999 );

			// Add menu item to all users menu.
			add_action( 'admin_menu', array( $this, 'admin_menus' ), 5 );
			add_action( 'network_admin_menu', array( $this, 'admin_menus' ), 5 );
			add_action( 'user_admin_menu', array( $this, 'user_profile_menu' ), 5 );

			// Create the Profile Navigation (Profile/Extended Profile).
			add_action( 'edit_user_profile', array( $this, 'profile_nav' ), 99, 1 );
			add_action( 'show_user_profile', array( $this, 'profile_nav' ), 99, 1 );

			// Editing users of a specific site.
			add_action( 'admin_head-site-users.php', array( $this, 'profile_admin_head' ) );

			// Add a row action to users listing.
			if ( bp_core_do_network_admin() ) {
				add_filter( 'ms_user_row_actions', array( $this, 'row_actions' ), 10, 2 );
				add_action( 'admin_init', array( $this, 'add_edit_profile_url_filter' ) );
				add_action( 'wp_after_admin_bar_render', array( $this, 'remove_edit_profile_url_filter' ) );
			}

			// Add user row actions for single site.
			add_filter( 'user_row_actions', array( $this, 'row_actions' ), 10, 2 );

			// Process changes to profile type.
			add_action( 'bp_members_admin_load', array( $this, 'process_member_type_update' ) );

			// Process changes to user suspend
			add_action( 'bp_members_admin_load', array( $this, 'process_user_suspend_update' ) );

			// Set Cookie to reset the previous layout stored in browser storage.
			add_action( 'update_option_bp-profile-layout-format', array( $this, 'bp_profile_layout_update_option' ), 10, 2 );
			add_action( 'update_option_bp-profile-layout-default-format', array( $this, 'bp_profile_layout_update_option' ), 10, 2 );

			// Set Cookie to reset the previous layout stored in browser storage.
			add_action( 'update_option_bp-group-layout-format', array( $this, 'bp_group_layout_update_option' ), 10, 2 );
			add_action( 'update_option_bp-group-layout-default-format', array( $this, 'bp_group_layout_update_option' ), 10, 2 );

			/** Signups **********************************************************
			 */

			if ( is_admin() ) {

				// Filter non multisite user query to remove sign-up users.
				if ( ! is_multisite() ) {
					add_action( 'pre_user_query', array( $this, 'remove_signups_from_user_query' ), 10, 1 );
				}

				// Reorganise the views navigation in users.php and signups page.
				if ( current_user_can( $this->capability ) ) {
					$user_screen = $this->users_screen;

					/**
					 * Users screen on multiblog is users, but signups
					 * need to be managed in the network for this case
					 */
					if ( bp_is_network_activated() && bp_is_multiblog_mode() && false === strpos( $user_screen, '-network' ) ) {
						$user_screen .= '-network';
					}

					add_filter( "views_{$user_screen}", array( $this, 'signup_filter_view' ), 10, 1 );
					add_filter( 'set-screen-option', array( $this, 'signup_screen_options' ), 10, 3 );
				}

				// Registration is turned on.
				add_action( 'update_site_option_registration', array( $this, 'multisite_registration_on' ), 10, 2 );
				add_action( 'update_option_users_can_register', array( $this, 'single_site_registration_on' ), 10, 2 );
				add_action( 'update_option_bp-enable-site-registration', array( $this, 'bp_registration_update_option' ), 10, 2 );
				add_action( 'update_site_option_registration', array( $this, 'bp_registration_update_site_option' ), 10, 2 );
				add_action( 'admin_init', array( $this, 'bp_update_registration_update_option' ), 10 );
			}

			/** Users List - Members Types ***************************************
			 */

			if ( is_admin() && bp_get_member_types() ) {

				// Add "Change type" <select> to WP admin users list table and process bulk members type changes.
				add_action( 'restrict_manage_users', array( $this, 'users_table_output_type_change_select' ) );
				add_action( 'load-users.php', array( $this, 'users_table_process_bulk_type_change' ) );

				// Add the profile type column to the WP admin users list table.
				add_filter( 'manage_users_columns', array( $this, 'users_table_add_type_column' ) );
				add_filter( 'manage_users_custom_column', array( $this, 'users_table_populate_type_cell' ), 10, 3 );

				// Filter WP admin users list table to include users of the specified type.
				add_filter( 'pre_get_users', array( $this, 'users_table_filter_by_type' ) );
			}
		}

		/**
		 * Update the BuddyBoss > Settings > General > Registrations options based on the Multi Site registration settings.
		 *
		 * @since BuddyBoss 1.0.9
		 *
		 * @param string $old_value
		 * @param string $value
		 */
		public function bp_registration_update_site_option( $old_value, $value ) {

			if ( is_multisite() ) {
				$check_current_wp = $value;
				$check_current_bp = (bool) bp_enable_site_registration();
				if ( ( 'all' === $check_current_wp || 'user' === $check_current_wp ) && false === $check_current_bp ) {
					bp_update_option( 'bp-enable-site-registration', 1 );
				} elseif ( ( 'none' === $check_current_wp || 'registration' === $check_current_wp ) && true === $check_current_bp ) {
					bp_update_option( 'bp-enable-site-registration', 1 );
				}
			}

		}

		/**
		 * Update site registrations options based on the WordPress settings on plugin activate.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param string $old_value
		 * @param string $value
		 */
		function bp_update_registration_update_option() {

			if ( ! is_multisite() ) {
				$check_current_wp = (bool) bp_get_option( 'users_can_register', 0 );
				$check_current_bp = (bool) bp_enable_site_registration();
				if ( true === $check_current_wp && false === $check_current_bp ) {
					bp_update_option( 'bp-enable-site-registration', 1 );
				} elseif ( false === $check_current_wp && true === $check_current_bp ) {
					update_option( 'users_can_register', 1 );
					bp_update_option( 'bp-enable-site-registration', 1 );
				}
			} else {
				$check_current_wp = get_site_option( 'registration', 'none' );
				$check_current_bp = (bool) bp_enable_site_registration();
				if ( ( 'all' === $check_current_wp || 'user' === $check_current_wp ) && false === $check_current_bp ) {
					bp_update_option( 'bp-enable-site-registration', 1 );
				} elseif ( ( 'none' === $check_current_wp || 'registration' === $check_current_wp ) && true === $check_current_bp ) {
					bp_update_option( 'bp-enable-site-registration', 1 );
				}
			}

		}

		/**
		 * Update site registrations options based on the BuddyBoss general registration settings.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param string $old_value
		 * @param string $value
		 */
		public function bp_registration_update_option( $old_value, $value ) {
			if ( 1 === $value ) {

				if ( ! is_multisite() ) {
					update_option( 'users_can_register', 1 );
				} else {
					// Get Multi Site Registration Settings.
					$check_current_wp = get_site_option( 'registration', 'none' );

					// Update the multi site registration options only if default registration is disabled.
					if ( 'none' === $check_current_wp || 'registration' === $check_current_wp || false === $check_current_wp ) {
						// Set multi site registration to "User accounts may be registered"
						update_site_option( 'registration', 'user' );
					}
				}
			} else {

				if ( '' !== $value ) {
					if ( ! is_multisite() ) {
						update_option( 'users_can_register', 0 );
					} else {
						update_site_option( 'registration', 'none' );
					}
				}
			}
		}

		/**
		 * Create registration pages when multisite user registration is turned on.
		 *
		 * @since BuddyPress 2.7.0
		 *
		 * @param string $option_name Current option name; value is always 'registration'.
		 * @param string $value
		 */
		public function multisite_registration_on( $option_name, $value ) {
			if ( 'user' === $value || 'all' === $value ) {
				bp_core_add_page_mappings(
					array(
						'register' => 1,
						'activate' => 1,
					)
				);

				// Update site registrations options based on the BuddyBoss general registration settings.
				bp_update_option( 'bp-enable-site-registration', 1 );

			} else {

				// Update site registrations options based on the BuddyBoss general registration settings.
				bp_update_option( 'bp-enable-site-registration', 0 );

			}
		}

		/**
		 * Create registration pages when single site registration is turned on.
		 *
		 * @since BuddyPress 2.7.0
		 *
		 * @param string $old_value
		 * @param string $value
		 */
		public function single_site_registration_on( $old_value, $value ) {
			// Single site.
			if ( ! is_multisite() && ! empty( $value ) ) {
				bp_core_add_page_mappings(
					array(
						'register' => 1,
						'activate' => 1,
					)
				);

				// Update site registrations options based on the BuddyBoss general registration settings.
				bp_update_option( 'bp-enable-site-registration', 1 );

			} else {

				// Update site registrations options based on the BuddyBoss general registration settings.
				bp_update_option( 'bp-enable-site-registration', 0 );

			}
		}

		/**
		 * Get the user ID.
		 *
		 * Look for $_GET['user_id']. If anything else, force the user ID to the
		 * current user's ID so they aren't left without a user to edit.
		 *
		 * @since BuddyPress 2.1.0
		 *
		 * @return int
		 */
		private function get_user_id() {
			if ( ! empty( $this->user_id ) ) {
				return $this->user_id;
			}

			$this->user_id = (int) get_current_user_id();

			// We'll need a user ID when not on self profile.
			if ( ! empty( $_GET['user_id'] ) ) {
				$this->user_id = (int) $_GET['user_id'];
			}

			return $this->user_id;
		}

		/**
		 * Can the current user edit the one displayed.
		 *
		 * Self profile editing / or bp_moderate check.
		 * This might be replaced by more granular capabilities
		 * in the future.
		 *
		 * @since BuddyPress 2.1.0
		 *
		 * @param int $user_id ID of the user being checked for edit ability.
		 *
		 * @return bool
		 */
		private function member_can_edit( $user_id = 0 ) {
			$retval = false;

			// Bail if no user ID was passed.
			if ( empty( $user_id ) ) {
				return $retval;
			}

			// Member can edit if they are viewing their own profile.
			if ( $this->current_user_id === $user_id ) {
				$retval = true;

				// Trust the 'bp_moderate' capability.
			} else {
				$retval = bp_current_user_can( 'bp_moderate' );
			}

			return $retval;
		}

		/**
		 * Get admin notice when saving a user or member profile.
		 *
		 * @since BuddyPress 2.1.0
		 *
		 * @return array
		 */
		private function get_user_notice() {

			// Setup empty notice for return value.
			$notice = array();

			// Updates.
			if ( ! empty( $_REQUEST['updated'] ) ) {
				switch ( $_REQUEST['updated'] ) {
					case 'avatar':
						$notice = array(
							'class'   => 'updated',
							'message' => __( 'Profile photo was deleted.', 'buddyboss' ),
						);
						break;
					case 'ham':
						$notice = array(
							'class'   => 'updated',
							'message' => __( 'User removed as spammer.', 'buddyboss' ),
						);
						break;
					case 'spam':
						$notice = array(
							'class'   => 'updated',
							'message' => __( 'User marked as spammer. Spam users are visible only to site admins.', 'buddyboss' ),
						);
						break;
					case 1:
						$notice = array(
							'class'   => 'updated',
							'message' => __( 'Profile updated.', 'buddyboss' ),
						);
						break;
				}
			}

			// Errors.
			if ( ! empty( $_REQUEST['error'] ) ) {
				switch ( $_REQUEST['error'] ) {
					case 'avatar':
						$notice = array(
							'class'   => 'error',
							'message' => __( 'There was a problem deleting that profile photo. Please try again.', 'buddyboss' ),
						);
						break;
					case 'ham':
						$notice = array(
							'class'   => 'error',
							'message' => __( 'User could not be removed as spammer.', 'buddyboss' ),
						);
						break;
					case 'spam':
						$notice = array(
							'class'   => 'error',
							'message' => __( 'User could not be marked as spammer.', 'buddyboss' ),
						);
						break;
					case 1:
						$notice = array(
							'class'   => 'error',
							'message' => __( 'An error occurred while trying to update the profile.', 'buddyboss' ),
						);
						break;
					case 2:
						$notice = array(
							'class'   => 'error',
							'message' => __( 'Your changes have not been saved. Please fill in all required fields, and save your changes again.', 'buddyboss' ),
						);
						break;
					case 3:
						$notice = array(
							'class'   => 'error',
							'message' => __( 'There was a problem updating some of your profile information. Please try again.', 'buddyboss' ),
						);
						break;
					case 4:
						$notice = array(
							'class'   => 'error',
							'message' => stripcslashes( $_REQUEST['message'] ),
						);
						break;
				}
			}

			return $notice;
		}

		/**
		 * Create the /user/ admin Profile submenus for all members.
		 *
		 * @since BuddyPress 2.1.0
		 */
		public function user_profile_menu() {

			// Setup the hooks array.
			$hooks = array();

			// Add the faux "Edit Profile" submenu page.
			$hooks['user'] = $this->user_page = add_submenu_page(
				'profile.php',
				__( 'Edit Profile', 'buddyboss' ),
				__( 'Edit Profile', 'buddyboss' ),
				'exist',
				'bp-profile-edit',
				array( $this, 'user_admin' )
			);

			// Setup the screen ID's.
			$this->screen_id = array(
				$this->user_page . '-user',
				$this->user_profile . '-user',
			);

			// Loop through new hooks and add method actions.
			foreach ( $hooks as $key => $hook ) {
				add_action( "load-{$hook}", array( $this, $key . '_admin_load' ) );
			}

			// Add the profile_admin_head method to proper admin_head actions.
			add_action( "admin_head-{$this->user_page}", array( $this, 'profile_admin_head' ) );
			add_action( 'admin_head-profile.php', array( $this, 'profile_admin_head' ) );
		}

		/**
		 * Create the All Users / Profile > Edit Profile and All Users Signups submenus.
		 *
		 * @since BuddyPress 2.0.0
		 */
		public function admin_menus() {

			// Setup the hooks array.
			$hooks = array();

			// Manage user's profile.
			$hooks['user'] = $this->user_page = add_submenu_page(
				$this->user_profile . '.php',
				__( 'Edit Profile', 'buddyboss' ),
				__( 'Edit Profile', 'buddyboss' ),
				'read',
				'bp-profile-edit',
				array( $this, 'user_admin' )
			);

			// Only show sign-ups where they belong.
			if ( ( ! bp_is_network_activated() && ! is_network_admin() ) || ( is_network_admin() && bp_is_network_activated() ) ) {

				// Manage signups.
				$hooks['signups'] = $this->signups_page = add_users_page(
					__( 'Pending Signups', 'buddyboss' ),
					__( 'Pending Signups', 'buddyboss' ),
					$this->capability,
					'bp-signups',
					array( $this, 'signups_admin' )
				);
			}

			$edit_page        = 'user-edit';
			$profile_page     = 'profile';
			$this->users_page = 'users';

			// Self profile check is needed for this pages.
			$page_head = array(
				$edit_page . '.php',
				$profile_page . '.php',
				$this->user_page,
				$this->users_page . '.php',
			);

			// Append '-network' to each array item if in network admin.
			if ( is_network_admin() ) {
				$edit_page          .= '-network';
				$profile_page       .= '-network';
				$this->user_page    .= '-network';
				$this->users_page   .= '-network';
				$this->signups_page .= '-network';
			}

			// Setup the screen ID's.
			$this->screen_id = array(
				$edit_page,
				$this->user_page,
				$profile_page,
			);

			// Loop through new hooks and add method actions.
			foreach ( $hooks as $key => $hook ) {
				add_action( "load-{$hook}", array( $this, $key . '_admin_load' ) );
			}

			// Add the profile_admin_head method to proper admin_head actions.
			foreach ( $page_head as $head ) {
				add_action( "admin_head-{$head}", array( $this, 'profile_admin_head' ) );
			}
		}

		/**
		 * Highlight the Users menu if on Edit Profile and check if on the user's admin profile.
		 *
		 * @since BuddyPress 2.1.0
		 */
		public function profile_admin_head() {
			global $submenu_file, $parent_file;

			// Is the user editing their own profile?
			if ( is_user_admin() || ( defined( 'IS_PROFILE_PAGE' ) && IS_PROFILE_PAGE ) ) {
				$this->is_self_profile = true;

				// Is the user attempting to edit their own profile.
			} elseif ( isset( $_GET['user_id'] ) || ( isset( $_GET['page'] ) && ( 'bp-profile-edit' === $_GET['page'] ) ) ) {
				$this->is_self_profile = (bool) ( $this->get_user_id() === $this->current_user_id );
			}

			// Force the parent file to users.php to open the correct top level menu
			// but only if not editing a site via the network site editing page.
			if ( 'sites.php' !== $parent_file ) {
				$parent_file  = 'users.php';
				$submenu_file = 'users.php';
			}

			// Editing your own profile, so recheck some vars.
			if ( true === $this->is_self_profile ) {

				// Use profile.php as the edit page.
				$edit_page = 'profile.php';

				// Set profile.php as the parent & sub files to correct the menu nav.
				if ( is_blog_admin() || is_user_admin() ) {
					$parent_file  = 'profile.php';
					$submenu_file = 'profile.php';
				}

				// Not editing yourself, so use user-edit.php.
			} else {
				$edit_page = 'user-edit.php';
			}

			if ( is_user_admin() ) {
				$this->edit_profile_url = add_query_arg( $this->edit_profile_args, user_admin_url( 'profile.php' ) );
				$this->edit_url         = user_admin_url( 'profile.php' );

			} elseif ( is_blog_admin() ) {
				$this->edit_profile_url = add_query_arg( $this->edit_profile_args, admin_url( 'users.php' ) );
				$this->edit_url         = admin_url( $edit_page );

			} elseif ( is_network_admin() ) {
				$this->edit_profile_url = add_query_arg( $this->edit_profile_args, network_admin_url( 'users.php' ) );
				$this->edit_url         = network_admin_url( $edit_page );
			}
		}

		/**
		 * Remove the Edit Profile page.
		 *
		 * We add these pages in order to integrate with WP's Users panel, but
		 * we want them to show up as a row action of the WP panel, not as separate
		 * subnav items under the Users menu.
		 *
		 * @since BuddyPress 2.0.0
		 */
		public function admin_head() {
			remove_submenu_page( 'users.php', 'bp-profile-edit' );
			remove_submenu_page( 'profile.php', 'bp-profile-edit' );
		}

		/** Community Profile *****************************************************/

		/**
		 * Add some specific styling to the Edit User and Edit User's Profile page.
		 *
		 * @since BuddyPress 2.0.0
		 */
		public function enqueue_scripts() {
			if ( ! in_array( get_current_screen()->id, $this->screen_id ) ) {
				return;
			}

			$min = bp_core_get_minified_asset_suffix();
			$css = $this->css_url . "admin{$min}.css";

			/**
			 * Filters the CSS URL to enqueue in the Members admin area.
			 *
			 * @since BuddyPress 2.0.0
			 *
			 * @param string $css URL to the CSS admin file to load.
			 */
			$css = apply_filters( 'bp_members_admin_css', $css );

			wp_enqueue_style( 'bp-members-css', $css, array(), bp_get_version() );

			wp_style_add_data( 'bp-members-css', 'rtl', true );
			if ( $min ) {
				wp_style_add_data( 'bp-members-css', 'suffix', $min );
			}

			// Only load JavaScript for BuddyPress profile.
			if ( get_current_screen()->id == $this->user_page ) {
				$js = $this->js_url . "admin{$min}.js";

				/**
				 * Filters the JS URL to enqueue in the Members admin area.
				 *
				 * @since BuddyPress 2.0.0
				 *
				 * @param string $js URL to the JavaScript admin file to load.
				 */
				$js = apply_filters( 'bp_members_admin_js', $js );
				wp_enqueue_script( 'bp-members-js', $js, array( 'jquery' ), bp_get_version(), true );
			}

			/**
			 * Fires after all of the members JavaScript and CSS are enqueued.
			 *
			 * @since BuddyPress 2.0.0
			 *
			 * @param string $id        ID of the current screen.
			 * @param array  $screen_id Array of allowed screens to add scripts and styles to.
			 */
			do_action( 'bp_members_admin_enqueue_scripts', get_current_screen()->id, $this->screen_id );
		}

		/**
		 * Create the Profile navigation in Edit User & Edit Profile pages.
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @param object|null $user   User to create profile navigation for.
		 * @param string      $active Which profile to highlight.
		 *
		 * @return string|null
		 */
		public function profile_nav( $user = null, $active = 'WordPress' ) {

			// Bail if no user ID exists here.
			if ( empty( $user->ID ) ) {
				return;
			}

			// Add the user ID to query arguments when not editing yourself.
			if ( false === $this->is_self_profile ) {
				$query_args = array( 'user_id' => $user->ID );
			} else {
				$query_args = array();
			}

			// Conditionally add a referer if it exists in the existing request.
			if ( ! empty( $_REQUEST['wp_http_referer'] ) ) {
				$wp_http_referer               = wp_unslash( $_REQUEST['wp_http_referer'] );
				$wp_http_referer               = wp_validate_redirect( esc_url_raw( $wp_http_referer ) );
				$query_args['wp_http_referer'] = urlencode( $wp_http_referer );
			}

			// Setup the two distinct "edit" URL's.
			$community_url = add_query_arg( $query_args, $this->edit_profile_url );
			$wordpress_url = add_query_arg( $query_args, $this->edit_url );

			$bp_active = false;
			$wp_active = false;

			if ( 'WordPress' === $active ) {
				$wp_active = ' nav-tab-active';
			}

			if ( 'BuddyPress' === $active ) {
				$bp_active = ' nav-tab-active';
			} ?>

		<h2 id="profile-nav" class="nav-tab-wrapper">
			<?php
			/**
			 * In configs where BuddyPress is not network activated, as regular
			 * admins do not have the capacity to edit other users, we must add
			 * this check.
			 */
			if ( current_user_can( 'edit_user', $user->ID ) ) :
				?>

				<a class="nav-tab<?php echo esc_attr( $wp_active ); ?>" href="<?php echo esc_url( $wordpress_url ); ?>"><?php _e( 'Profile', 'buddyboss' ); ?></a>

			<?php endif; ?>

			<a class="nav-tab<?php echo esc_attr( $bp_active ); ?>" href="<?php echo esc_url( $community_url ); ?>"><?php _e( 'Extended Profile', 'buddyboss' ); ?></a>

			<?php
			/**
			 * Add more menu tabs from the user's profile.
			 *
			 * @since BuddyBoss 1.7.8
			 *
			 * @param object|null $user   User to create profile navigation for.
			 * @param string      $active Which profile to highlight.
			 */
			do_action( 'bb_members_admin_user_profile_nav', $user, $active );
			?>
		</h2>

			<?php
		}

		/**
		 * Set up the user's profile admin page.
		 *
		 * Loaded before the page is rendered, this function does all initial
		 * setup, including: processing form requests, registering contextual
		 * help, and setting up screen options.
		 *
		 * @since BuddyPress 2.0.0
		 */
		public function user_admin_load() {

			// Get the user ID.
			$user_id = $this->get_user_id();

			// Can current user edit this profile?
			if ( ! $this->member_can_edit( $user_id ) ) {
				wp_die( __( 'You cannot edit the requested user.', 'buddyboss' ) );
			}

			// Build redirection URL.
			$redirect_to = remove_query_arg( array( 'action', 'error', 'updated', 'spam', 'ham', 'delete_avatar' ), $_SERVER['REQUEST_URI'] );
			$doaction    = ! empty( $_REQUEST['action'] ) ? $_REQUEST['action'] : false;

			if ( ! empty( $_REQUEST['user_status'] ) ) {
				$spam = (bool) ( 'spam' === $_REQUEST['user_status'] );

				if ( $spam !== bp_is_user_spammer( $user_id ) ) {
					$doaction = $_REQUEST['user_status'];
				}
			}

			/**
			 * Fires at the start of the signups admin load.
			 *
			 * @since BuddyPress 2.0.0
			 *
			 * @param string $doaction Current bulk action being processed.
			 * @param array  $_REQUEST Current $_REQUEST global.
			 */
			do_action_ref_array( 'bp_members_admin_load', array( $doaction, $_REQUEST ) );

			/**
			 * Filters the allowed actions for use in the user admin page.
			 *
			 * @since BuddyPress 2.0.0
			 *
			 * @param array $value Array of allowed actions to use.
			 */
			$allowed_actions = apply_filters( 'bp_members_admin_allowed_actions', array( 'update', 'delete_avatar', 'spam', 'ham' ) );

			// Prepare the display of the Community Profile screen.
			if ( ! in_array( $doaction, $allowed_actions ) ) {
				add_screen_option(
					'layout_columns',
					array(
						'default' => 2,
						'max'     => 2,
					)
				);

				get_current_screen()->add_help_tab(
					array(
						'id'      => 'bp-profile-edit-overview',
						'title'   => __( 'Overview', 'buddyboss' ),
						'content' =>
						'<p>' . __( 'This is the admin view of a user\'s profile.', 'buddyboss' ) . '</p>' .
						'<p>' . __( 'In the main column, you can edit the fields of the user\'s extended profile.', 'buddyboss' ) . '</p>' .
						'<p>' . __( 'In the right-hand column, you can update the user\'s status, delete the user\'s avatar, and view recent statistics.', 'buddyboss' ) . '</p>',
					)
				);

				// Help panel - sidebar links.
				get_current_screen()->set_help_sidebar(
					'<p><strong>' . __( 'For more information:', 'buddyboss' ) . '</strong></p>' .
					'<p>' . __( '<a href="https://www.buddyboss.com/resources/">Documentation</a>', 'buddyboss' ) . '</p>'
				);

				// Register metaboxes for the edit screen.
				add_meta_box(
					'submitdiv',
					__( 'Status', 'buddyboss' ),
					array( $this, 'user_admin_status_metabox' ),
					get_current_screen()->id,
					'side',
					'core'
				);

				// In case xprofile is not active.
				$this->stats_metabox->context  = 'normal';
				$this->stats_metabox->priority = 'core';

				/**
				 * Fires before loading the profile fields if component is active.
				 *
				 * Plugins should not use this hook, please use 'bp_members_admin_user_metaboxes' instead.
				 *
				 * @since BuddyPress 2.0.0
				 *
				 * @param int    $user_id       Current user ID for the screen.
				 * @param string $id            Current screen ID.
				 * @param object $stats_metabox Object holding position data for use with the stats metabox.
				 */
				do_action_ref_array( 'bp_members_admin_xprofile_metabox', array( $user_id, get_current_screen()->id, $this->stats_metabox ) );

				// If xProfile is inactive, difficult to know what's profile we're on.
				if ( 'normal' === $this->stats_metabox->context ) {
					$display_name = bp_core_get_user_displayname( $user_id );
				} else {
					$display_name = __( 'Member', 'buddyboss' );
				}

				// User Stat metabox.
				add_meta_box(
					'bp_members_admin_user_stats',
					sprintf( __( "%s's Stats", 'buddyboss' ), $display_name ),
					array( $this, 'user_admin_stats_metabox' ),
					get_current_screen()->id,
					sanitize_key( $this->stats_metabox->context ),
					sanitize_key( $this->stats_metabox->priority )
				);

				// profile type metabox. Only added if profile types have been registered.
				$member_types = bp_get_member_types();
				if ( ! empty( $member_types ) ) {
					add_meta_box(
						'bp_members_admin_member_type',
						__( 'Profile Type', 'buddyboss' ),
						array( $this, 'user_admin_member_type_metabox' ),
						get_current_screen()->id,
						'side',
						'core'
					);
				}

				/**
				 * Fires at the end of the Community Profile screen.
				 *
				 * Plugins can restrict metabox to "bp_moderate" admins by checking if
				 * the first argument ($this->is_self_profile) is false in their callback.
				 * They can also restrict their metabox to self profile editing
				 * by setting it to true.
				 *
				 * @since BuddyPress 2.0.0
				 *
				 * @param bool $is_self_profile Whether or not it is the current user's profile.
				 * @param int  $user_id         Current user ID.
				 */
				do_action( 'bp_members_admin_user_metaboxes', $this->is_self_profile, $user_id );

				// Enqueue JavaScript files.
				wp_enqueue_script( 'postbox' );
				wp_enqueue_script( 'dashboard' );

				// Spam or Ham user.
			} elseif ( in_array( $doaction, array( 'spam', 'ham' ) ) && empty( $this->is_self_profile ) ) {

				check_admin_referer( 'edit-bp-profile_' . $user_id );

				if ( bp_core_process_spammer_status( $user_id, $doaction ) ) {
					$redirect_to = add_query_arg( 'updated', $doaction, $redirect_to );
				} else {
					$redirect_to = add_query_arg( 'error', $doaction, $redirect_to );
				}

				bp_core_redirect( $redirect_to );

				// Update other stuff once above ones are done.
			} else {
				$this->redirect = $redirect_to;

				/**
				 * Fires at end of user profile admin load if doaction does not match any available actions.
				 *
				 * @since BuddyPress 2.0.0
				 *
				 * @param string $doaction Current bulk action being processed.
				 * @param int    $user_id  Current user ID.
				 * @param array  $_REQUEST Current $_REQUEST global.
				 * @param string $redirect Determined redirect url to send user to.
				 */
				do_action_ref_array( 'bp_members_admin_update_user', array( $doaction, $user_id, $_REQUEST, $this->redirect ) );

				bp_core_redirect( $this->redirect );
			}
		}

		/**
		 * Display the user's profile.
		 *
		 * @since BuddyPress 2.0.0
		 */
		public function user_admin() {

			if ( ! bp_current_user_can( 'bp_moderate' ) && empty( $this->is_self_profile ) ) {
				die( '-1' );
			}

			// Get the user ID.
			$user_id = $this->get_user_id();
			$user    = get_user_to_edit( $user_id );

			// Construct title.
			if ( true === $this->is_self_profile ) {
				$title = __( 'Profile', 'buddyboss' );
			} else {
				$title = __( 'Edit User', 'buddyboss' );
			}

			// Construct URL for form.
			$request_url     = remove_query_arg( array( 'action', 'error', 'updated', 'spam', 'ham' ), $_SERVER['REQUEST_URI'] );
			$form_action_url = add_query_arg( 'action', 'update', $request_url );
			$wp_http_referer = false;
			if ( ! empty( $_REQUEST['wp_http_referer'] ) ) {
				$wp_http_referer = wp_unslash( $_REQUEST['wp_http_referer'] );
				$wp_http_referer = remove_query_arg( array( 'action', 'updated' ), $wp_http_referer );
				$wp_http_referer = wp_validate_redirect( esc_url_raw( $wp_http_referer ) );
			}

			// Prepare notice for admin.
			$notice = $this->get_user_notice();

			if ( ! empty( $notice ) ) :
				?>

			<div
				<?php
				if ( 'updated' === $notice['class'] ) :
					?>
				id="message" <?php endif; ?>class="<?php echo esc_attr( $notice['class'] ); ?>">

				<p><?php echo esc_html( $notice['message'] ); ?></p>

					<?php if ( ! empty( $wp_http_referer ) && ( 'updated' === $notice['class'] ) ) : ?>

					<p><a href="<?php echo esc_url( $wp_http_referer ); ?>"><?php esc_html_e( '&larr; Back to Users', 'buddyboss' ); ?></a></p>

				<?php endif; ?>

			</div>

			<?php endif; ?>

		<div class="wrap" id="community-profile-page">
				<?php if ( version_compare( $GLOBALS['wp_version'], '4.8', '>=' ) ) : ?>

				<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>

					<?php if ( empty( $this->is_self_profile ) ) : ?>

						<?php if ( current_user_can( 'create_users' ) ) : ?>

						<a href="user-new.php" class="page-title-action"><?php echo esc_html__( 'Add New', 'buddyboss' ); ?></a>

					<?php elseif ( is_multisite() && current_user_can( 'promote_users' ) ) : ?>

						<a href="user-new.php" class="page-title-action"><?php echo esc_html__( 'Add Existing', 'buddyboss' ); ?></a>

					<?php endif; ?>

				<?php endif; ?>

				<hr class="wp-header-end">

			<?php else : ?>

				<h1><?php echo esc_html( $title ); ?>

					<?php if ( empty( $this->is_self_profile ) ) : ?>

						<?php if ( current_user_can( 'create_users' ) ) : ?>

							<a href="user-new.php" class="add-new-h2"><?php echo esc_html__( 'Add New', 'buddyboss' ); ?></a>

						<?php elseif ( is_multisite() && current_user_can( 'promote_users' ) ) : ?>

							<a href="user-new.php" class="add-new-h2"><?php echo esc_html__( 'Add Existing', 'buddyboss' ); ?></a>

						<?php endif; ?>

					<?php endif; ?>
				</h1>

			<?php endif; ?>

				<?php
				if ( ! empty( $user ) ) :

					$this->profile_nav( $user, 'BuddyPress' );
					?>

				<form action="<?php echo esc_url( $form_action_url ); ?>" id="your-profile" method="post">
					<div id="poststuff">

						<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">

							<div id="postbox-container-1" class="postbox-container">
								<?php do_meta_boxes( get_current_screen()->id, 'side', $user ); ?>
							</div>

							<div id="postbox-container-2" class="postbox-container">
								<?php do_meta_boxes( get_current_screen()->id, 'normal', $user ); ?>
								<?php do_meta_boxes( get_current_screen()->id, 'advanced', $user ); ?>
							</div>
						</div><!-- #post-body -->

					</div><!-- #poststuff -->

					<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
					<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
					<?php wp_nonce_field( 'edit-bp-profile_' . $user->ID ); ?>

				</form>

				<?php else : ?>

				<p>
					<?php
					printf(
						'%1$s <a href="%2$s">%3$s</a>',
						__( 'No user found with this ID.', 'buddyboss' ),
						esc_url( bp_get_admin_url( 'users.php' ) ),
						__( 'Go back and try again.', 'buddyboss' )
					);
					?>
				</p>

			<?php endif; ?>

		</div><!-- .wrap -->
			<?php
		}

		/**
		 * Render the Status metabox for user's profile screen.
		 *
		 * Actions are:
		 * - Update profile fields if xProfile component is active
		 * - Spam/Unspam user
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @param WP_User|null $user The WP_User object to be edited.
		 */
		public function user_admin_status_metabox( $user = null ) {

			// Bail if no user id or if the user has not activated their account yet.
			if ( empty( $user->ID ) ) {
				return;
			}

			// Bail if user has not been activated yet (how did you get here?).
			if ( isset( $user->user_status ) && ( 2 == $user->user_status ) ) :
				?>

			<p class="not-activated"><?php esc_html_e( 'User account has not yet been activated', 'buddyboss' ); ?></p><br/>

				<?php
				return;

			endif;
			?>

		<div class="submitbox" id="submitcomment">
			<div id="minor-publishing">
				<div id="misc-publishing-actions">
					<?php

					// Get the spam status once here to compare against below.
					$is_spammer = bp_is_user_spammer( $user->ID );

					/**
					 * In configs where BuddyPress is not network activated,
					 * regular admins cannot mark a user as a spammer on front
					 * end. This prevent them to do it in the back end.
					 *
					 * Also prevent admins from marking themselves or other
					 * admins as spammers.
					 */
					if ( ( empty( $this->is_self_profile ) && ( ! in_array( $user->user_login, get_super_admins() ) ) && empty( $this->subsite_activated ) ) || ( ! empty( $this->subsite_activated ) && current_user_can( 'manage_network_users' ) ) ) :
						?>

						<div class="misc-pub-section" id="comment-status-radio">
							<label class="approved"><input type="radio" name="user_status" value="ham" <?php checked( $is_spammer, false ); ?>><?php esc_html_e( 'Active', 'buddyboss' ); ?></label><br />

							<?php
							if ( bp_is_active( 'moderation' ) ) {
								$is_suspend = bp_moderation_is_user_suspended( $user->ID );
								?>
								<label class="suspend"><input type="radio" name="user_status" id="user_status" value="suspend" <?php checked( $is_suspend, true ); ?>><?php esc_html_e( 'Suspend', 'buddyboss' ); ?></label>
								<?php
							}
							?>
						</div>

					<?php endif; ?>

					<div class="misc-pub-section curtime misc-pub-section-last">
							<?php

							// Translators: Publish box date format, see http://php.net/date.
							$date = date_i18n( bp_core_date_format( true ), strtotime( $user->user_registered ) );
							?>
						<span id="timestamp"><?php printf( __( 'Registered on: %s', 'buddyboss' ), '<strong>' . $date . '</strong>' ); ?></span>
					</div>
				</div> <!-- #misc-publishing-actions -->

				<div class="clear"></div>
			</div><!-- #minor-publishing -->

			<div id="major-publishing-actions">

				<div id="publishing-action">
					<a class="button bp-view-profile" href="<?php echo esc_url( bp_core_get_user_domain( $user->ID ) ); ?>" target="_blank"><?php esc_html_e( 'View Profile', 'buddyboss' ); ?></a>
						<?php submit_button( esc_html__( 'Update Profile', 'buddyboss' ), 'primary', 'save', false ); ?>
				</div>
				<div class="clear"></div>
			</div><!-- #major-publishing-actions -->

		</div><!-- #submitcomment -->

			<?php
		}

		/**
		 * Render the fallback metabox in case a user has been marked as a spammer.
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @param WP_User|null $user The WP_User object to be edited.
		 */
		public function user_admin_spammer_metabox( $user = null ) {
			?>
			<p><?php printf( __( '%s has been marked as a spammer. All BuddyBoss data associated with the user has been removed.', 'buddyboss' ), esc_html( bp_core_get_user_displayname( $user->ID ) ) ); ?></p>
			<?php
		}

		/**
		 * Render the fallback metabox in case a user has been marked as a suspended.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param WP_User|null $user The WP_User object to be edited.
		 */
		public function user_admin_suspended_metabox( $user = null ) {
			?>
			<p><?php printf( __( '%s has been marked as a suspended. All BuddyBoss data associated with the user has been removed.', 'buddyboss' ), esc_html( bp_core_get_user_displayname( $user->ID ) ) ); ?></p>
			<?php
		}

		/**
		 * Render the Stats metabox to moderate inappropriate images.
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @param WP_User|null $user The WP_User object to be edited.
		 */
		public function user_admin_stats_metabox( $user = null ) {

			// Bail if no user ID.
			if ( empty( $user->ID ) ) {
				return;
			}

			// If account is not activated last activity is the time user registered.
			if ( isset( $user->user_status ) && 2 == $user->user_status ) {
				$last_active = $user->user_registered;

				// Account is activated, getting user's last activity.
			} else {
				$last_active = bp_get_user_last_activity( $user->ID );
			}

			$date = date_i18n( bp_core_date_format( true ), strtotime( $last_active ) );
			?>

		<ul>
			<li class="bp-members-profile-stats"><?php printf( __( 'Last active: %1$s', 'buddyboss' ), '<strong>' . $date . '</strong>' ); ?></li>

				<?php
				// Loading other stats only if user has activated their account.
				if ( empty( $user->user_status ) ) {

					/**
					 * Fires in the user stats metabox if the user has activated their account.
					 *
					 * @since BuddyPress 2.0.0
					 *
					 * @param array  $value Array holding the user ID.
					 * @param object $user  Current displayed user object.
					 */
					do_action( 'bp_members_admin_user_stats', array( 'user_id' => $user->ID ), $user );
				}
				?>
		</ul>

			<?php
		}

		/**
		 * Render the profile type metabox.
		 *
		 * @since BuddyPress 2.2.0
		 *
		 * @param WP_User|null $user The WP_User object to be edited.
		 */
		public function user_admin_member_type_metabox( $user = null ) {

			// Bail if no user ID.
			if ( empty( $user->ID ) ) {
				return;
			}

			$types        = bp_get_member_types( array(), 'objects' );
			$current_type = bp_get_member_type( $user->ID );
			?>

		<label for="bp-members-profile-member-type" class="screen-reader-text">
			<?php
			/* translators: accessibility text */
			esc_html_e( 'Select profile type', 'buddyboss' );
			?>
		</label>
		<select name="bp-members-profile-member-type" id="bp-members-profile-member-type">
			<option value="" <?php selected( '', $current_type ); ?>>
											 <?php
												/* translators: no option picked in select box */
												esc_attr_e( '----', 'buddyboss' );
												?>
			</option>
			<?php foreach ( $types as $type ) : ?>
				<option value="<?php echo esc_attr( $type->name ); ?>" <?php selected( $type->name, $current_type ); ?>><?php echo esc_html( $type->labels['singular_name'] ); ?></option>
			<?php endforeach; ?>
		</select>

			<?php

			wp_nonce_field( 'bp-member-type-change-' . $user->ID, 'bp-member-type-nonce' );
		}

		/**
		 * Process changes from the profile type metabox.
		 *
		 * @since BuddyPress 2.2.0
		 */
		public function process_member_type_update() {
			if ( ! isset( $_POST['bp-member-type-nonce'] ) || ! isset( $_POST['bp-members-profile-member-type'] ) ) {
				return;
			}

			$user_id = $this->get_user_id();

			check_admin_referer( 'bp-member-type-change-' . $user_id, 'bp-member-type-nonce' );

			// Permission check.
			if ( ! bp_current_user_can( 'bp_moderate' ) && $user_id != bp_loggedin_user_id() ) {
				return;
			}

			// profile type string must either reference a valid profile type, or be empty.
			$member_type = stripslashes( $_POST['bp-members-profile-member-type'] );
			if ( ! empty( $member_type ) && ! bp_get_member_type_object( $member_type ) ) {
				return;
			}

			if ( '' !== $member_type ) {

				// Get post id of selected profile type.
				$post_id = bp_member_type_post_by_type( $member_type );

				// Get selected profile type role.
				$selected_member_type_wp_roles = get_post_meta( $post_id, '_bp_member_type_wp_roles', true );

				if ( $user_id === get_current_user_id() ) {

					if ( isset( $selected_member_type_wp_roles[0] ) && 'administrator' !== $selected_member_type_wp_roles[0] ) {

						if ( empty( $selected_member_type_wp_roles ) ) {

							/*
							 * If an invalid profile type is passed, someone's doing something
							 * fishy with the POST request, so we can fail silently.
							 */
							if ( bp_set_member_type( $user_id, $member_type ) ) {
								// @todo Success messages can't be posted because other stuff happens on the page load.
							}
						} else {
							$bp_error_message_string = __( 'You cannot assign yourself to this profile type as doing so would remove your Administrator role and lock you out of the WordPress admin. You first need to associate this profile type to the Administrator role, and then you can assign it to yourself.', 'buddyboss' );
							$error_message           = apply_filters( 'bp_invalid_role_selection_extended_profile', $bp_error_message_string );
							// Define the settings error to display
							add_settings_error(
								'bp-invalid-role-selection-extended-profile',
								'bp-invalid-role-selection-extended-profile',
								$error_message,
								'error'
							);
							set_transient( 'bp_invalid_role_selection_extended_profile', get_settings_errors(), 30 );

							return;
						}
					} else {

						if ( isset( $selected_member_type_wp_roles[0] ) && 'none' !== $selected_member_type_wp_roles[0] ) {

							$bp_current_user = new WP_User( get_current_user_id() );

							// Remove role
							$bp_current_user->remove_role( $bp_current_user->roles[0] );

							// Add role
							$bp_current_user->add_role( $selected_member_type_wp_roles[0] );
						}
					}
				} else {

					if ( isset( $selected_member_type_wp_roles[0] ) && 'none' !== $selected_member_type_wp_roles[0] ) {
						$bp_user = new WP_User( $user_id );

						// Remove role
						$bp_user->remove_role( $bp_user->roles[0] );

						// Add role
						$bp_user->add_role( $selected_member_type_wp_roles[0] );
					}
				}
			}

			/*
			 * If an invalid profile type is passed, someone's doing something
			 * fishy with the POST request, so we can fail silently.
			 */
			if ( bp_set_member_type( $user_id, $member_type ) ) {
				// @todo Success messages can't be posted because other stuff happens on the page load.
			}

			if ( function_exists( 'bp_get_xprofile_member_type_field_id' ) && bp_get_xprofile_member_type_field_id() > 0 ) {
				$id = (int) bp_member_type_post_by_type( $member_type );
				if ( $id > 0 ) {
					xprofile_set_field_data( bp_get_xprofile_member_type_field_id(), $user_id, $id );
				}
				if ( '' === $member_type ) {
					xprofile_set_field_data( bp_get_xprofile_member_type_field_id(), $user_id, '' );
				}
			}

		}


		/**
		 * Process changes from the profile type metabox.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param string $doaction
		 */
		public function process_user_suspend_update( $doaction = '' ) {

			if ( 'update' === $doaction ) {

				$user_id = $this->get_user_id();

				check_admin_referer( 'edit-bp-profile_' . $user_id );

				// Permission check.
				if ( ! bp_current_user_can( 'bp_moderate' ) && $user_id != bp_loggedin_user_id() ) {
					return;
				}

				// profile type string must either reference a valid profile type, or be empty.
				$is_suspend = isset( $_POST['user_status'] ) ? stripslashes( $_POST['user_status'] ) : '';

				if ( ! empty( $is_suspend ) && 'suspend' === $is_suspend ) {
					BP_Suspend_Member::suspend_user( $user_id );
				} elseif ( bp_is_active( 'moderation' ) && bp_moderation_is_user_suspended( $user_id ) ) {
					BP_Suspend_Member::unsuspend_user( $user_id );
				}
			}
		}

		/**
		 * Add a link to Profile in Users listing row actions.
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @param array|string $actions WordPress row actions (edit, delete).
		 * @param object|null  $user    The object for the user row.
		 *
		 * @return null|string|array Merged actions.
		 */
		public function row_actions( $actions = '', $user = null ) {

			// Bail if no user ID.
			if ( empty( $user->ID ) ) {
				return;
			}

			// Setup args array.
			$args = array();

			// Add the user ID if it's not for the current user.
			if ( $user->ID !== $this->current_user_id ) {
				$args['user_id'] = $user->ID;
			}

			// Add the referer.
			$wp_http_referer         = wp_unslash( $_SERVER['REQUEST_URI'] );
			$wp_http_referer         = wp_validate_redirect( esc_url_raw( $wp_http_referer ) );
			$args['wp_http_referer'] = urlencode( $wp_http_referer );

			// Add the "Extended" link if the current user can edit this user.
			if ( current_user_can( 'edit_user', $user->ID ) || bp_current_user_can( 'bp_moderate' ) ) {

				// Add query args and setup the Extended link.
				$edit_profile      = add_query_arg( $args, $this->edit_profile_url );
				$edit_profile_link = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $edit_profile ), esc_html__( 'Extended', 'buddyboss' ) );

				/**
				 * Check the edit action is available
				 * and preserve the order edit | profile | remove/delete.
				 */
				if ( ! empty( $actions['edit'] ) ) {
					$edit_action = $actions['edit'];
					unset( $actions['edit'] );

					$new_edit_actions = array(
						'edit'         => $edit_action,
						'edit-profile' => $edit_profile_link,
					);

					// If not available simply add the edit profile action.
				} else {
					$new_edit_actions = array( 'edit-profile' => $edit_profile_link );
				}

				$actions = array_merge( $new_edit_actions, $actions );
			}

			return $actions;
		}

		/**
		 * Add a filter to edit profile url in WP Admin Bar.
		 *
		 * @since BuddyPress 2.1.0
		 */
		public function add_edit_profile_url_filter() {
			add_filter( 'bp_members_edit_profile_url', array( $this, 'filter_adminbar_profile_link' ), 10, 3 );
		}

		/**
		 * Filter the profile url.
		 *
		 * @since BuddyPress 2.1.0
		 *
		 * @param string $profile_link Profile Link for admin bar.
		 * @param string $url          Profile URL.
		 * @param int    $user_id      User ID.
		 *
		 * @return string
		 */
		public function filter_adminbar_profile_link( $profile_link = '', $url = '', $user_id = 0 ) {
			if ( ! is_super_admin( $user_id ) && is_admin() ) {
				$profile_link = user_admin_url( 'profile.php' );
			}
			return $profile_link;
		}

		/**
		 * Remove the filter to edit profile url in WP Admin Bar.
		 *
		 * @since BuddyPress 2.1.0
		 */
		public function remove_edit_profile_url_filter() {
			remove_filter( 'bp_members_edit_profile_url', array( $this, 'filter_adminbar_profile_link' ), 10 );
		}

		/** Signups Management ****************************************************/

		/**
		 * Display the admin preferences about signups pagination.
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @param int    $value     Value for signup option.
		 * @param string $option    Value for the option key.
		 * @param int    $new_value Value for the saved option.
		 *
		 * @return int The pagination preferences.
		 */
		public function signup_screen_options( $value = 0, $option = '', $new_value = 0 ) {
			if ( 'users_page_bp_signups_network_per_page' != $option && 'users_page_bp_signups_per_page' != $option ) {
				return $value;
			}

			// Per page.
			$new_value = (int) $new_value;
			if ( $new_value < 1 || $new_value > 999 ) {
				return $value;
			}

			return $new_value;
		}

		/**
		 * Make sure no signups will show in users list.
		 *
		 * This is needed to handle signups that may have not been activated
		 * before the 2.0.0 upgrade.
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @param WP_User_Query|null $query The users query.
		 *
		 * @return WP_User_Query|null The users query without the signups.
		 */
		public function remove_signups_from_user_query( $query = null ) {
			global $wpdb;

			// Bail if this is an ajax request.
			if ( defined( 'DOING_AJAX' ) ) {
				return;
			}

			// Bail if updating BuddyPress.
			if ( bp_is_update() ) {
				return;
			}

			// Bail if there is no current admin screen.
			if ( ! function_exists( 'get_current_screen' ) || ! get_current_screen() ) {
				return;
			}

			// Get current screen.
			$current_screen = get_current_screen();

			// Bail if not on a users page.
			if ( ! isset( $current_screen->id ) || $this->users_page !== $current_screen->id ) {
				return;
			}

			// Bail if already querying by an existing role.
			if ( ! empty( $query->query_vars['role'] ) ) {
				return;
			}

			$query->query_where .= " AND {$wpdb->users}.user_status != 2";
		}

		/**
		 * Filter the WP Users List Table views to include 'bp-signups'.
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @param array $views WP List Table views.
		 *
		 * @return array The views with the signup view added.
		 */
		public function signup_filter_view( $views = array() ) {
			global $role;

			// Remove the 'current' class from All if we're on the signups view.
			if ( 'registered' === $role ) {
				$views['all'] = str_replace( 'class="current"', '', $views['all'] );
				$class        = 'current';
			} else {
				$class = '';
			}

			$signups = BP_Signup::count_signups();

			if ( is_network_admin() ) {
				$base_url = network_admin_url( 'users.php' );
			} else {
				$base_url = bp_get_admin_url( 'users.php' );
			}

			$url  = add_query_arg( 'page', 'bp-signups', $base_url );
			$text = sprintf( __( 'Pending %s', 'buddyboss' ), '<span class="count">(' . bp_core_number_format( $signups ) . ')</span>' );

			$views['registered'] = sprintf( '<a href="%1$s" class="%2$s">%3$s</a>', esc_url( $url ), $class, $text );

			return $views;
		}

		/**
		 * Load the Signup WP Users List table.
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @param string $class    The name of the class to use.
		 * @param string $required The parent class.
		 *
		 * @return WP_List_Table|null The List table.
		 */
		public static function get_list_table_class( $class = '', $required = '' ) {
			if ( empty( $class ) ) {
				return;
			}

			if ( ! empty( $required ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-' . $required . '-list-table.php';
			}

			return new $class();
		}

		/**
		 * Set up the signups admin page.
		 *
		 * Loaded before the page is rendered, this function does all initial
		 * setup, including: processing form requests, registering contextual
		 * help, and setting up screen options.
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @global $bp_members_signup_list_table
		 */
		public function signups_admin_load() {
			global $bp_members_signup_list_table;

			// Build redirection URL.
			$redirect_to = remove_query_arg( array( 'action', 'error', 'updated', 'activated', 'notactivated', 'deleted', 'notdeleted', 'resent', 'notresent', 'do_delete', 'do_resend', 'do_activate', '_wpnonce', 'signup_ids' ), $_SERVER['REQUEST_URI'] );
			$doaction    = bp_admin_list_table_current_bulk_action();

			/**
			 * Fires at the start of the signups admin load.
			 *
			 * @since BuddyPress 2.0.0
			 *
			 * @param string $doaction Current bulk action being processed.
			 * @param array  $_REQUEST Current $_REQUEST global.
			 */
			do_action( 'bp_signups_admin_load', $doaction, $_REQUEST );

			/**
			 * Filters the allowed actions for use in the user signups admin page.
			 *
			 * @since BuddyPress 2.0.0
			 *
			 * @param array $value Array of allowed actions to use.
			 */
			$allowed_actions = apply_filters( 'bp_signups_admin_allowed_actions', array( 'do_delete', 'do_activate', 'do_resend' ) );

			// Prepare the display of the Community Profile screen.
			if ( ! in_array( $doaction, $allowed_actions ) || ( -1 == $doaction ) ) {

				if ( is_network_admin() ) {
					$bp_members_signup_list_table = self::get_list_table_class( 'BP_Members_MS_List_Table', 'ms-users' );
				} else {
					$bp_members_signup_list_table = self::get_list_table_class( 'BP_Members_List_Table', 'users' );
				}

				// The per_page screen option.
				add_screen_option( 'per_page', array( 'label' => __( 'Pending Accounts', 'buddyboss' ) ) );

				get_current_screen()->add_help_tab(
					array(
						'id'      => 'bp-signups-overview',
						'title'   => __( 'Overview', 'buddyboss' ),
						'content' =>
						'<p>' . __( 'This is the administration screen for pending accounts on your site.', 'buddyboss' ) . '</p>' .
						'<p>' . __( 'From the screen options, you can customize the displayed columns and the pagination of this screen.', 'buddyboss' ) . '</p>' .
						'<p>' . __( 'You can reorder the list of your pending accounts by clicking on the Username, Email or Registered column headers.', 'buddyboss' ) . '</p>' .
						'<p>' . __( 'Using the search form, you can find pending accounts more easily. The Username and Email fields will be included in the search.', 'buddyboss' ) . '</p>',
					)
				);

				get_current_screen()->add_help_tab(
					array(
						'id'      => 'bp-signups-actions',
						'title'   => __( 'Actions', 'buddyboss' ),
						'content' =>
						'<p>' . __( 'Hovering over a row in the pending accounts list will display action links that allow you to manage pending accounts. You can perform the following actions:', 'buddyboss' ) . '</p>' .
						'<ul><li>' . __( '"Email" takes you to the confirmation screen before being able to send the activation link to the desired pending account. You can only send the activation email once per day.', 'buddyboss' ) . '</li>' .
						'<li>' . __( '"Delete" allows you to delete a pending account from your site. You will be asked to confirm this deletion.', 'buddyboss' ) . '</li></ul>' .
						'<p>' . __( 'By clicking on a Username you will be able to activate a pending account from the confirmation screen.', 'buddyboss' ) . '</p>' .
						'<p>' . __( 'Bulk actions allow you to perform these 3 actions for the selected rows.', 'buddyboss' ) . '</p>',
					)
				);

				// Help panel - sidebar links.
				get_current_screen()->set_help_sidebar(
					'<p><strong>' . __( 'For more information:', 'buddyboss' ) . '</strong></p>' .
					'<p>' . __( '<a href="https://www.buddyboss.com/resources/">Documentation</a>', 'buddyboss' ) . '</p>'
				);

				// Add accessible hidden headings and text for the Pending Users screen.
				get_current_screen()->set_screen_reader_content(
					array(
						/* translators: accessibility text */
						'heading_views'      => __( 'Filter users list', 'buddyboss' ),
						/* translators: accessibility text */
						'heading_pagination' => __( 'Pending users list navigation', 'buddyboss' ),
						/* translators: accessibility text */
						'heading_list'       => __( 'Pending users list', 'buddyboss' ),
					)
				);

			} else {
				if ( ! empty( $_REQUEST['signup_ids'] ) ) {
					$signups = wp_parse_id_list( $_REQUEST['signup_ids'] );
				}

				// Handle resent activation links.
				if ( 'do_resend' == $doaction ) {

					// Nonce check.
					check_admin_referer( 'signups_resend' );

					$resent = BP_Signup::resend( $signups );

					if ( empty( $resent ) ) {
						$redirect_to = add_query_arg( 'error', $doaction, $redirect_to );
					} else {
						$query_arg = array( 'updated' => 'resent' );

						if ( ! empty( $resent['resent'] ) ) {
							$query_arg['resent'] = count( $resent['resent'] );
						}

						if ( ! empty( $resent['errors'] ) ) {
							$query_arg['notsent'] = count( $resent['errors'] );
							set_transient( '_bp_admin_signups_errors', $resent['errors'], 30 );
						}

						$redirect_to = add_query_arg( $query_arg, $redirect_to );
					}

					bp_core_redirect( $redirect_to );

					// Handle activated accounts.
				} elseif ( 'do_activate' == $doaction ) {

					// Nonce check.
					check_admin_referer( 'signups_activate' );

					$activated = BP_Signup::activate( $signups );

					if ( empty( $activated ) ) {
						$redirect_to = add_query_arg( 'error', $doaction, $redirect_to );
					} else {
						$query_arg = array( 'updated' => 'activated' );

						if ( ! empty( $activated['activated'] ) ) {
							$query_arg['activated'] = count( $activated['activated'] );
						}

						if ( ! empty( $activated['errors'] ) ) {
							$query_arg['notactivated'] = count( $activated['errors'] );
							set_transient( '_bp_admin_signups_errors', $activated['errors'], 30 );
						}

						$redirect_to = add_query_arg( $query_arg, $redirect_to );
					}

					bp_core_redirect( $redirect_to );

					// Handle sign-ups delete.
				} elseif ( 'do_delete' == $doaction ) {

					// Nonce check.
					check_admin_referer( 'signups_delete' );

					$deleted = BP_Signup::delete( $signups );

					if ( empty( $deleted ) ) {
						$redirect_to = add_query_arg( 'error', $doaction, $redirect_to );
					} else {
						$query_arg = array( 'updated' => 'deleted' );

						if ( ! empty( $deleted['deleted'] ) ) {
							$query_arg['deleted'] = count( $deleted['deleted'] );
						}

						if ( ! empty( $deleted['errors'] ) ) {
							$query_arg['notdeleted'] = count( $deleted['errors'] );
							set_transient( '_bp_admin_signups_errors', $deleted['errors'], 30 );
						}

						$redirect_to = add_query_arg( $query_arg, $redirect_to );
					}

					bp_core_redirect( $redirect_to );

					// Plugins can update other stuff from here.
				} else {
					$this->redirect = $redirect_to;

					/**
					 * Fires at end of signups admin load if doaction does not match any actions.
					 *
					 * @since BuddyPress 2.0.0
					 *
					 * @param string $doaction Current bulk action being processed.
					 * @param array  $_REQUEST Current $_REQUEST global.
					 * @param string $redirect Determined redirect url to send user to.
					 */
					do_action( 'bp_members_admin_update_signups', $doaction, $_REQUEST, $this->redirect );

					bp_core_redirect( $this->redirect );
				}
			}
		}

		/**
		 * Display any activation errors.
		 *
		 * @since BuddyPress 2.0.0
		 */
		public function signups_display_errors() {

			// Look for sign-up errors.
			$errors = get_transient( '_bp_admin_signups_errors' );

			// Bail if no activation errors.
			if ( empty( $errors ) ) {
				return;
			}

			// Loop through errors and display them.
			foreach ( $errors as $error ) :
				?>

			<li><?php echo esc_html( $error[0] ); ?>: <?php echo esc_html( $error[1] ); ?></li>

				<?php
		endforeach;

			// Delete the redirect transient.
			delete_transient( '_bp_admin_signups_errors' );
		}

		/**
		 * Get admin notice when viewing the sign-up page.
		 *
		 * @since BuddyPress 2.1.0
		 *
		 * @return array
		 */
		private function get_signup_notice() {

			// Setup empty notice for return value.
			$notice = array();

			// Updates.
			if ( ! empty( $_REQUEST['updated'] ) ) {
				switch ( $_REQUEST['updated'] ) {
					case 'resent':
						$notice = array(
							'class'   => 'updated',
							'message' => '',
						);

						if ( ! empty( $_REQUEST['resent'] ) ) {
							$notice['message'] .= sprintf(
								_n(
									'%s activation email successfully sent! ',
									'%s activation emails successfully sent! ',
									absint( $_REQUEST['resent'] ),
									'buddyboss'
								),
								bp_core_number_format( absint( $_REQUEST['resent'] ) )
							);
						}

						if ( ! empty( $_REQUEST['notsent'] ) ) {
							$notice['message'] .= sprintf(
								_n(
									'%s activation email was not sent.',
									'%s activation emails were not sent.',
									absint( $_REQUEST['notsent'] ),
									'buddyboss'
								),
								bp_core_number_format( absint( $_REQUEST['notsent'] ) )
							);

							if ( empty( $_REQUEST['resent'] ) ) {
								$notice['class'] = 'error';
							}
						}

						break;

					case 'activated':
						$notice = array(
							'class'   => 'updated',
							'message' => '',
						);

						if ( ! empty( $_REQUEST['activated'] ) ) {
							$notice['message'] .= sprintf(
								_n(
									'%s account successfully activated! ',
									'%s accounts successfully activated! ',
									absint( $_REQUEST['activated'] ),
									'buddyboss'
								),
								bp_core_number_format( absint( $_REQUEST['activated'] ) )
							);
						}

						if ( ! empty( $_REQUEST['notactivated'] ) ) {
							$notice['message'] .= sprintf(
								_n(
									'%s account was not activated.',
									'%s accounts were not activated.',
									absint( $_REQUEST['notactivated'] ),
									'buddyboss'
								),
								bp_core_number_format( absint( $_REQUEST['notactivated'] ) )
							);

							if ( empty( $_REQUEST['activated'] ) ) {
								$notice['class'] = 'error';
							}
						}

						break;

					case 'deleted':
						$notice = array(
							'class'   => 'updated',
							'message' => '',
						);

						if ( ! empty( $_REQUEST['deleted'] ) ) {
							$notice['message'] .= sprintf(
								_n(
									'%s sign-up successfully deleted!',
									'%s sign-ups successfully deleted!',
									absint( $_REQUEST['deleted'] ),
									'buddyboss'
								),
								bp_core_number_format( absint( $_REQUEST['deleted'] ) )
							);
						}

						if ( ! empty( $_REQUEST['notdeleted'] ) ) {
							$notice['message'] .= sprintf(
								_n(
									'%s sign-up was not deleted.',
									'%s sign-ups were not deleted.',
									absint( $_REQUEST['notdeleted'] ),
									'buddyboss'
								),
								bp_core_number_format( absint( $_REQUEST['notdeleted'] ) )
							);

							if ( empty( $_REQUEST['deleted'] ) ) {
								$notice['class'] = 'error';
							}
						}

						break;
				}
			}

			// Errors.
			if ( ! empty( $_REQUEST['error'] ) ) {
				switch ( $_REQUEST['error'] ) {
					case 'do_resend':
						$notice = array(
							'class'   => 'error',
							'message' => esc_html__( 'There was a problem sending the activation emails. Please try again.', 'buddyboss' ),
						);
						break;

					case 'do_activate':
						$notice = array(
							'class'   => 'error',
							'message' => esc_html__( 'There was a problem activating accounts. Please try again.', 'buddyboss' ),
						);
						break;

					case 'do_delete':
						$notice = array(
							'class'   => 'error',
							'message' => esc_html__( 'There was a problem deleting sign-ups. Please try again.', 'buddyboss' ),
						);
						break;
				}
			}

			return $notice;
		}

		/**
		 * Signups admin page router.
		 *
		 * Depending on the context, display
		 * - the list of signups,
		 * - or the delete confirmation screen,
		 * - or the activate confirmation screen,
		 * - or the "resend" email confirmation screen.
		 *
		 * Also prepare the admin notices.
		 *
		 * @since BuddyPress 2.0.0
		 */
		public function signups_admin() {
			$doaction = bp_admin_list_table_current_bulk_action();

			// Prepare notices for admin.
			$notice = $this->get_signup_notice();

			// Display notices.
			if ( ! empty( $notice ) ) :
				if ( 'updated' === $notice['class'] ) :
					?>

				<div id="message" class="<?php echo esc_attr( $notice['class'] ); ?>">

				<?php else : ?>

				<div class="<?php echo esc_attr( $notice['class'] ); ?>">

			<?php endif; ?>

				<p><?php echo $notice['message']; ?></p>

					<?php if ( ! empty( $_REQUEST['notactivated'] ) || ! empty( $_REQUEST['notdeleted'] ) || ! empty( $_REQUEST['notsent'] ) ) : ?>

					<ul><?php $this->signups_display_errors(); ?></ul>

				<?php endif; ?>

			</div>

				<?php
		endif;

			// Show the proper screen.
			switch ( $doaction ) {
				case 'activate':
				case 'delete':
				case 'resend':
					$this->signups_admin_manage( $doaction );
					break;

				default:
					$this->signups_admin_index();
					break;

			}
		}

		/**
		 * This is the list of the Pending accounts (signups).
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @global $plugin_page
		 * @global $bp_members_signup_list_table
		 */
		public function signups_admin_index() {
			global $plugin_page, $bp_members_signup_list_table;

			$usersearch = ! empty( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : '';

			// Prepare the group items for display.
			$bp_members_signup_list_table->prepare_items();

			if ( is_network_admin() ) {
				$form_url = network_admin_url( 'users.php' );
			} else {
				$form_url = bp_get_admin_url( 'users.php' );
			}

			$form_url = add_query_arg(
				array(
					'page' => 'bp-signups',
				),
				$form_url
			);

			$search_form_url = remove_query_arg(
				array(
					'action',
					'deleted',
					'notdeleted',
					'error',
					'updated',
					'delete',
					'activate',
					'activated',
					'notactivated',
					'resend',
					'resent',
					'notresent',
					'do_delete',
					'do_activate',
					'do_resend',
					'action2',
					'_wpnonce',
					'signup_ids',
				),
				$_SERVER['REQUEST_URI']
			);

			?>

		<div class="wrap">
			<?php if ( version_compare( $GLOBALS['wp_version'], '4.8', '>=' ) ) : ?>

				<h1 class="wp-heading-inline"><?php _e( 'Users', 'buddyboss' ); ?></h1>

				<?php if ( current_user_can( 'create_users' ) ) : ?>

					<a href="user-new.php" class="page-title-action"><?php echo esc_html__( 'Add New', 'buddyboss' ); ?></a>

				<?php elseif ( is_multisite() && current_user_can( 'promote_users' ) ) : ?>

					<a href="user-new.php" class="page-title-action"><?php echo esc_html__( 'Add Existing', 'buddyboss' ); ?></a>

					<?php
				endif;

				if ( $usersearch ) {
					printf( '<span class="subtitle">' . __( 'Search results for "%s"', 'buddyboss' ) . '</span>', esc_html( $usersearch ) );
				}
				?>

				<hr class="wp-header-end">

			<?php else : ?>

				<h1><?php _e( 'Users', 'buddyboss' ); ?>

					<?php if ( current_user_can( 'create_users' ) ) : ?>

						<a href="user-new.php" class="add-new-h2"><?php echo esc_html__( 'Add New', 'buddyboss' ); ?></a>

					<?php elseif ( is_multisite() && current_user_can( 'promote_users' ) ) : ?>

						<a href="user-new.php" class="add-new-h2"><?php echo esc_html__( 'Add Existing', 'buddyboss' ); ?></a>

						<?php
					endif;

					if ( $usersearch ) {
						printf( '<span class="subtitle">' . __( 'Search results for "%s"', 'buddyboss' ) . '</span>', esc_html( $usersearch ) );
					}

					?>
				</h1>

			<?php endif; ?>

				<?php // Display each signups on its own row. ?>
				<?php $bp_members_signup_list_table->views(); ?>

			<form id="bp-signups-search-form" action="<?php echo esc_url( $search_form_url ); ?>">
				<input type="hidden" name="page" value="<?php echo esc_attr( $plugin_page ); ?>" />
					<?php $bp_members_signup_list_table->search_box( __( 'Search Pending Users', 'buddyboss' ), 'bp-signups' ); ?>
			</form>

			<form id="bp-signups-form" action="<?php echo esc_url( $form_url ); ?>" method="post">
					<?php $bp_members_signup_list_table->display(); ?>
			</form>
		</div>
			<?php
		}

		/**
		 * This is the confirmation screen for actions.
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @param string $action Delete, activate, or resend activation link.
		 *
		 * @return null|false
		 */
		public function signups_admin_manage( $action = '' ) {
			if ( ! current_user_can( $this->capability ) || empty( $action ) ) {
				die( '-1' );
			}

			// Get the user IDs from the URL.
			$ids = false;
			if ( ! empty( $_POST['allsignups'] ) ) {
				$ids = wp_parse_id_list( $_POST['allsignups'] );
			} elseif ( ! empty( $_GET['signup_id'] ) ) {
				$ids = absint( $_GET['signup_id'] );
			}

			if ( empty( $ids ) ) {
				return false;
			}

			// Query for signups, and filter out those IDs that don't
			// correspond to an actual signup.
			$signups_query = BP_Signup::get(
				array(
					'include' => $ids,
				)
			);

			$signups    = $signups_query['signups'];
			$signup_ids = wp_list_pluck( $signups, 'signup_id' );

			// Set up strings.
			switch ( $action ) {
				case 'delete':
					$header_text = __( 'Delete Pending Accounts', 'buddyboss' );
					if ( 1 == count( $signup_ids ) ) {
						$helper_text = __( 'You are about to delete the following account:', 'buddyboss' );
					} else {
						$helper_text = __( 'You are about to delete the following accounts:', 'buddyboss' );
					}
					break;

				case 'activate':
					$header_text = __( 'Activate Pending Accounts', 'buddyboss' );
					if ( 1 == count( $signup_ids ) ) {
						$helper_text = __( 'You are about to activate the following account:', 'buddyboss' );
					} else {
						$helper_text = __( 'You are about to activate the following accounts:', 'buddyboss' );
					}
					break;

				case 'resend':
					$header_text = __( 'Resend Activation Emails', 'buddyboss' );
					if ( 1 == count( $signup_ids ) ) {
						$helper_text = __( 'You are about to resend an activation email to the following account:', 'buddyboss' );
					} else {
						$helper_text = __( 'You are about to resend an activation email to the following accounts:', 'buddyboss' );
					}
					break;
			}

			// These arguments are added to all URLs.
			$url_args = array( 'page' => 'bp-signups' );

			// These arguments are only added when performing an action.
			$action_args = array(
				'action'     => 'do_' . $action,
				'signup_ids' => implode( ',', $signup_ids ),
			);

			if ( is_network_admin() ) {
				$base_url = network_admin_url( 'users.php' );
			} else {
				$base_url = bp_get_admin_url( 'users.php' );
			}

			$cancel_url = add_query_arg( $url_args, $base_url );
			$action_url = wp_nonce_url(
				add_query_arg(
					array_merge( $url_args, $action_args ),
					$base_url
				),
				'signups_' . $action
			);

			// Prefetch registration field data.
			$fdata = array();
			if ( 'activate' === $action && bp_is_active( 'xprofile' ) ) {
				$field_groups = bp_xprofile_get_groups(
					array(
						'exclude_fields'    => 1,
						'update_meta_cache' => false,
						'fetch_fields'      => true,
					)
				);

				foreach ( $field_groups as $fg ) {
					foreach ( $fg->fields as $f ) {
						$fdata[ $f->id ] = $f->name;
					}
				}
			}

			?>

		<div class="wrap">
			<h1><?php echo esc_html( $header_text ); ?></h1>
			<p><?php echo esc_html( $helper_text ); ?></p>

			<ol class="bp-signups-list">
			<?php
			foreach ( $signups as $signup ) :
				$last_notified     = mysql2date( 'Y/m/d g:i:s a', $signup->date_sent );
				$profile_field_ids = array();

				// Get all xprofile field IDs except field 1.
				if ( ! empty( $signup->meta['profile_field_ids'] ) ) {
					$profile_field_ids = array_flip( explode( ',', $signup->meta['profile_field_ids'] ) );
					unset( $profile_field_ids[1] );
				}
				?>

				<li>
					<strong><?php echo esc_html( $signup->user_login ); ?></strong>

					<?php if ( 'activate' == $action ) : ?>
						<table class="wp-list-table widefat fixed striped">
							<tbody>
								<tr>
									<td class="column-fields"><?php esc_html_e( 'Display Name', 'buddyboss' ); ?></td>
									<td><?php echo esc_html( $signup->user_name ); ?></td>
								</tr>

								<tr>
									<td class="column-fields"><?php esc_html_e( 'Email', 'buddyboss' ); ?></td>
									<td><?php echo sanitize_email( $signup->user_email ); ?></td>
								</tr>

								<?php
								if ( bp_is_active( 'xprofile' ) && ! empty( $profile_field_ids ) ) :
									foreach ( $profile_field_ids as $pid => $noop ) :
										$field_value = isset( $signup->meta[ "field_{$pid}" ] ) ? $signup->meta[ "field_{$pid}" ] : '';
										if ( function_exists( 'bp_get_xprofile_member_type_field_id' ) && bp_get_xprofile_member_type_field_id() > 0 && bp_get_xprofile_member_type_field_id() === (int) $pid ) {
											$member_type_name = get_post_meta( $field_value, '_bp_member_type_label_singular_name', true );
											?>
											<tr>
												<td class="column-fields"><?php echo esc_html( $fdata[ $pid ] ); ?></td>
												<td><?php echo esc_html( $member_type_name ); ?></td>
											</tr>
											<?php
										} elseif ( function_exists( 'bp_get_xprofile_gender_type_field_id' ) && bp_get_xprofile_gender_type_field_id() > 0 && bp_get_xprofile_gender_type_field_id() === (int) $pid ) {
											$split_string = explode( '_', $field_value, 2 );
											$field_value  = $split_string[1];
											?>
											<tr>
												<td class="column-fields"><?php echo esc_html( $fdata[ $pid ] ); ?></td>
												<td><?php echo esc_html( $field_value ); ?></td>
											</tr>
											<?php
										} else {
											?>
											<tr>
												<td class="column-fields"><?php echo esc_html( $fdata[ $pid ] ); ?></td>
												<td><?php echo $this->format_xprofile_field_for_display( $field_value ); ?></td>
											</tr>
											<?php
										}
									endforeach;
								endif;
								?>
							</tbody>
						</table>
					<?php endif; ?>

					<?php if ( 'resend' == $action ) : ?>

						<p class="description">
							<?php printf( esc_html__( 'Last notified: %s', 'buddyboss' ), $last_notified ); ?>

							<?php if ( ! empty( $signup->recently_sent ) ) : ?>

								<span class="attention wp-ui-text-notification"> <?php esc_html_e( '(less than 24 hours ago)', 'buddyboss' ); ?></span>

							<?php endif; ?>
						</p>

					<?php endif; ?>

				</li>

			<?php endforeach; ?>
			</ol>

				<?php if ( 'delete' === $action ) : ?>

				<p><strong><?php esc_html_e( 'This action cannot be undone.', 'buddyboss' ); ?></strong></p>

			<?php endif; ?>

			<a class="button-primary" href="<?php echo esc_url( $action_url ); ?>"><?php esc_html_e( 'Confirm', 'buddyboss' ); ?></a>
			<a class="button" href="<?php echo esc_url( $cancel_url ); ?>"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
		</div>

			<?php
		}

		/** Users List Management ****************************************************/

		/**
		 * Display a dropdown to bulk change the profile type of selected user(s).
		 *
		 * @since BuddyPress 2.7.0
		 *
		 * @param string $which Where this dropdown is displayed - top or bottom.
		 */
		public function users_table_output_type_change_select( $which = 'top' ) {

			// Bail if current user cannot promote users.
			if ( ! bp_current_user_can( 'promote_users' ) ) {
				return;
			}

			// `$which` is only passed in WordPress 4.6+. Avoid duplicating controls in earlier versions.
			static $displayed = false;
			if ( version_compare( bp_get_major_wp_version(), '4.6', '<' ) && $displayed ) {
				return;
			}
			$displayed = true;

			$id_name = 'bottom' === $which ? 'bp_change_type2' : 'bp_change_type';

			$types = bp_get_member_types( array(), 'objects' );
			?>

		<label class="screen-reader-text" for="<?php echo $id_name; ?>"><?php _e( 'Change profile type to&hellip;', 'buddyboss' ); ?></label>
		<select name="<?php echo $id_name; ?>" id="<?php echo $id_name; ?>" style="display:inline-block;float:none;">
			<option value=""><?php _e( 'Change profile type to&hellip;', 'buddyboss' ); ?></option>

				<?php foreach ( $types as $type ) : ?>

				<option value="<?php echo esc_attr( $type->name ); ?>"><?php echo esc_html( $type->labels['singular_name'] ); ?></option>

			<?php endforeach; ?>

			<option value="remove_member_type"><?php _e( 'No Profile Type', 'buddyboss' ); ?></option>

		</select>
			<?php
			wp_nonce_field( 'bp-bulk-users-change-type-' . bp_loggedin_user_id(), 'bp-bulk-users-change-type-nonce' );
			submit_button( __( 'Change', 'buddyboss' ), 'button', 'bp_change_member_type', false );
		}

		/**
		 * Process bulk profile type change submission from the WP admin users list table.
		 *
		 * @since BuddyPress 2.7.0
		 */
		public function users_table_process_bulk_type_change() {
			// Output the admin notice.
			$this->users_type_change_notice();

			// Flag for custom error message.
			$bp_error_message = false;

			// Bail if no users are specified or if this isn't a BuddyPress action.
			if ( empty( $_REQUEST['users'] )
			|| ( empty( $_REQUEST['bp_change_type'] ) && empty( $_REQUEST['bp_change_type2'] ) )
			|| empty( $_REQUEST['bp_change_member_type'] )
			) {
				return;
			}

			// Bail if nonce check fails.
			check_admin_referer( 'bp-bulk-users-change-type-' . bp_loggedin_user_id(), 'bp-bulk-users-change-type-nonce' );

			// Bail if current user cannot promote users.
			if ( ! bp_current_user_can( 'promote_users' ) ) {
				return;
			}

			$new_type = '';
			if ( ! empty( $_REQUEST['bp_change_type2'] ) ) {
				$new_type = sanitize_text_field( $_REQUEST['bp_change_type2'] );
			} elseif ( ! empty( $_REQUEST['bp_change_type'] ) ) {
				$new_type = sanitize_text_field( $_REQUEST['bp_change_type'] );
			}

			// Check that the selected type actually exists.
			if ( 'remove_member_type' != $new_type && null === bp_get_member_type_object( $new_type ) ) {
				$error = true;
			} else {
				// Run through user ids.
				$error = false;
				foreach ( (array) $_REQUEST['users'] as $user_id ) {
					$user_id = (int) $user_id;

					// Get the old profile type to check against.
					$member_type = bp_get_member_type( $user_id );

					if ( 'remove_member_type' === $new_type ) {
						// Remove the current profile type, if there's one to remove.
						if ( $member_type ) {
							$removed = bp_remove_member_type( $user_id, $member_type );
							if ( false === $removed || is_wp_error( $removed ) ) {
								$error = true;
							} else {
								if ( function_exists( 'bp_get_xprofile_member_type_field_id' ) && bp_get_xprofile_member_type_field_id() > 0 ) {
									xprofile_set_field_data( bp_get_xprofile_member_type_field_id(), $user_id, '' );
								}
							}
						}
					} else {

						if ( get_current_user_id() === $user_id ) {

							// Set the new profile type.
							if ( $new_type !== $member_type ) {

								// Get post id of selected profile type.
								$post_id = bp_member_type_post_by_type( $new_type );

								// Get selected profile type role.
								$selected_member_type_wp_roles = get_post_meta( $post_id, '_bp_member_type_wp_roles', true );
								if ( empty( $selected_member_type_wp_roles ) ) {
									$set = bp_set_member_type( $user_id, $new_type );
									if ( false === $set || is_wp_error( $set ) ) {
										$error = true;
									} else {
										if ( function_exists( 'bp_get_xprofile_member_type_field_id' ) && bp_get_xprofile_member_type_field_id() > 0 ) {
											xprofile_set_field_data( bp_get_xprofile_member_type_field_id(), $user_id, bp_member_type_post_by_type( $new_type ) );
										}
									}
								} else {
									if ( isset( $selected_member_type_wp_roles[0] ) && 'administrator' !== $selected_member_type_wp_roles[0] ) {
										$bp_error_message = true;
										$error            = true;
									} else {
										$set = bp_set_member_type( $user_id, $new_type );
										if ( false === $set || is_wp_error( $set ) ) {
											$error = true;
										} else {
											if ( function_exists( 'bp_get_xprofile_member_type_field_id' ) && bp_get_xprofile_member_type_field_id() > 0 ) {
												xprofile_set_field_data( bp_get_xprofile_member_type_field_id(), $user_id, bp_member_type_post_by_type( $new_type ) );
											}
										}
									}
								}
							}
						} else {

							// Set the new profile type.
							if ( $new_type !== $member_type ) {
								$set = bp_set_member_type( $user_id, $new_type );
								if ( false === $set || is_wp_error( $set ) ) {
									$error = true;
								} else {
									if ( function_exists( 'bp_get_xprofile_member_type_field_id' ) && bp_get_xprofile_member_type_field_id() > 0 ) {
										xprofile_set_field_data( bp_get_xprofile_member_type_field_id(), $user_id, bp_member_type_post_by_type( $new_type ) );
									}
								}
							}
						}

						/**
						 * For add the new profile type roles
						 */
						if ( ! $error ) {
							// Get post id of selected profile type.
							$post_id = bp_member_type_post_by_type( $new_type );

							// Get selected profile type role.
							$selected_member_type_wp_roles = get_post_meta( $post_id, '_bp_member_type_wp_roles', true );
							$member_type_role              = sanitize_text_field( $selected_member_type_wp_roles[0] );
							$member_type_role              = isset( $member_type_role ) ? $member_type_role : '';

							if ( get_current_user_id() === $user_id ) {

								if ( isset( $member_type_role ) && 'administrator' !== $member_type_role ) {

									if ( empty( $selected_member_type_wp_roles ) ) {
										/*
										 * If an invalid profile type is passed, someone's doing something
										 * fishy with the POST request, so we can fail silently.
										 */
										if ( bp_set_member_type( $user_id, $member_type ) ) {
											// @todo Success messages can't be posted because other stuff happens on the page load.
										}
									} else {
										$bp_error_message_string = __( 'You cannot assign yourself to this profile type as doing so would remove your Administrator role and lock you out of the WordPress admin. You first need to associate this profile type to the Administrator role, and then you can assign it to yourself.', 'buddyboss' );
										$error_message           = apply_filters( 'bp_invalid_role_selection_extended_profile', $bp_error_message_string );
										// Define the settings error to display.
										add_settings_error(
											'bp-invalid-role-selection-extended-profile',
											'bp-invalid-role-selection-extended-profile',
											$error_message,
											'error'
										);
										set_transient( 'bp_invalid_role_selection_extended_profile', get_settings_errors(), 30 );

										return;
									}
								} else {

									if ( isset( $member_type_role ) && 'none' !== $member_type_role ) {

										$bp_current_user = new WP_User( get_current_user_id() );

										foreach ( $bp_current_user->roles as $role ) {
											// Remove role.
											$bp_current_user->remove_role( $role );
										}

										// Add role.
										$bp_current_user->add_role( $member_type_role );
									}
								}
							} else {

								if ( isset( $member_type_role ) && 'none' !== $member_type_role ) {

									// Remove the old role.
									$bp_user = new WP_User( $user_id );

									foreach ( $bp_user->roles as $role ) {
										// Remove role.
										$bp_user->remove_role( $role );
									}

									$bp_user->add_role( $member_type_role );
								}
							}
						}
					}
				}
			}

			// If there were any errors, show the error message.
			if ( $error ) {

				if ( true === $bp_error_message ) {
					$redirect = add_query_arg( array( 'updated' => 'member-type-change-owner-error' ), wp_get_referer() );
				} else {
					$redirect = add_query_arg( array( 'updated' => 'member-type-change-error' ), wp_get_referer() );
				}
			} else {
				$redirect = add_query_arg( array( 'updated' => 'member-type-change-success' ), wp_get_referer() );
			}

			wp_safe_redirect( $redirect );
			exit();
		}

		/**
		 * Display an admin notice upon profile type bulk update.
		 *
		 * @since BuddyPress 2.7.0
		 */
		public function users_type_change_notice() {
			$updated = isset( $_REQUEST['updated'] ) ? $_REQUEST['updated'] : false;

			// Display feedback.
			if ( $updated && in_array( $updated, array( 'member-type-change-error', 'member-type-change-success', 'member-type-change-owner-error' ), true ) ) {

				if ( 'member-type-change-error' === $updated ) {
					$notice = __( 'There was an error while changing the profile type. Please try again.', 'buddyboss' );
					$type   = 'error';
				} elseif ( 'member-type-change-owner-error' === $updated ) {
					$notice = __( 'You cannot assign yourself to this profile type as doing so would remove your Administrator role and lock you out of the WordPress admin. You first need to associate this profile type to the Administrator role, and then you can assign it to yourself.', 'buddyboss' );
					$type   = 'error';
				} else {
					$notice = __( 'Profile type was changed successfully.', 'buddyboss' );
					$type   = 'updated';
				}

				bp_core_add_admin_notice( $notice, $type );
			}
		}

		/**
		 * Add profile type column to the WordPress admin users list table.
		 *
		 * @since BuddyPress 2.7.0
		 *
		 * @param array $columns Users table columns.
		 *
		 * @return array $columns
		 */
		public function users_table_add_type_column( $columns = array() ) {
			$columns[ bp_get_member_type_tax_name() ] = __( 'Profile Type', 'buddyboss' );

			return $columns;
		}

		/**
		 * Return member's type for display in the WP admin users list table.
		 *
		 * @since BuddyPress 2.7.0
		 *
		 * @param string $retval
		 * @param string $column_name
		 * @param int    $user_id
		 *
		 * @return string profile type as a link to filter all users.
		 */
		public function users_table_populate_type_cell( $retval = '', $column_name = '', $user_id = 0 ) {
			// Only looking for profile type column.
			if ( bp_get_member_type_tax_name() !== $column_name ) {
				return $retval;
			}

			// Get the profile type.
			$type = bp_get_member_type( $user_id );

			// Output the profile type link.
			if ( $type_obj = bp_get_member_type_object( $type ) ) {
				$url    = add_query_arg( array( 'bp-member-type' => urlencode( $type ) ) );
				$retval = '<a href="' . esc_url( $url ) . '">' . esc_html( $type_obj->labels['singular_name'] ) . '</a>';
			}

			return $retval;
		}

		/**
		 * Filter WP Admin users list table to include users of the specified type.
		 *
		 * @param WP_Query $query
		 *
		 * @since BuddyPress 2.7.0
		 */
		public function users_table_filter_by_type( $query ) {
			global $pagenow;

			if ( is_admin() && 'users.php' === $pagenow && ! empty( $_REQUEST['bp-member-type'] ) ) {
				$type_slug = sanitize_text_field( $_REQUEST['bp-member-type'] );

				// Check that the type is registered.
				if ( null == bp_get_member_type_object( $type_slug ) ) {
					return;
				}

				// Get the list of users that are assigned to this profile type.
				$type = bp_get_term_by( 'slug', $type_slug, bp_get_member_type_tax_name() );

				if ( empty( $type->term_id ) ) {
					return;
				}

				$user_ids = bp_get_objects_in_term( $type->term_id, bp_get_member_type_tax_name() );

				if ( $user_ids && ! is_wp_error( $user_ids ) ) {
					$query->set( 'include', (array) $user_ids );
				}
			}
		}

		/**
		 * Formats a signup's xprofile field data for display.
		 *
		 * Operates recursively on arrays, which are then imploded with commas.
		 *
		 * @since BuddyPress 2.8.0
		 *
		 * @param string|array $value Field value.
		 *
		 * @return string
		 */
		protected function format_xprofile_field_for_display( $value ) {
			if ( is_array( $value ) ) {
				$value = array_map( array( $this, 'format_xprofile_field_for_display' ), $value );
				$value = implode( ', ', $value );
			} else {
				$value = stripslashes( $value );
				$value = esc_html( $value );
			}

			return $value;
		}

		/**
		 * Set Cookie to reset the previous layout stored in browser storage.
		 *
		 * @since BuddyPress 1.2.0
		 */
		public function bp_profile_layout_update_option( $old_value, $new_value ) {
			if ( $old_value !== $new_value ) {
				setcookie( 'reset_member', '1', time() + ( 86400 * 30 ), '/' );
			}
		}

		/**
		 * Set Cookie to reset the previous layout stored in browser storage.
		 *
		 * @since BuddyPress 1.2.0
		 */
		public function bp_group_layout_update_option( $old_value, $new_value ) {
			if ( $old_value !== $new_value ) {
				setcookie( 'reset_group', '1', time() + ( 86400 * 30 ), '/' );
			}
		}
	}
endif; // End class_exists check.
