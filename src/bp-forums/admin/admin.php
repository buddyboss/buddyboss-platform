<?php

/**
 * Main Forums Admin Class
 *
 * @package BuddyBoss\Administration
 * @since bbPress (r2464)
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BBP_Admin' ) ) :
	/**
	 * Loads Forums plugin admin area
	 *
	 * @since bbPress (r2464)
	 */
	#[\AllowDynamicProperties]
	class BBP_Admin {

		/** Directory *************************************************************/

		/**
		 * @var string Path to the Forums admin directory
		 */
		public $admin_dir = '';

		/** URLs ******************************************************************/

		/**
		 * @var string URL to the Forums admin directory
		 */
		public $admin_url = '';

		/**
		 * @var string URL to the Forums images directory
		 */
		public $images_url = '';

		/**
		 * @var string URL to the Forums admin styles directory
		 */
		public $styles_url = '';

		/**
		 * @var string URL to the Forums admin js directory
		 */
		public $js_url = '';

		/** Capability ************************************************************/

		/**
		 * @var bool Minimum capability to access Tools and Settings
		 */
		public $minimum_capability = 'keep_gate';

		/** Separator *************************************************************/

		/**
		 * @var bool Whether or not to add an extra top level menu separator
		 */
		public $show_separator = false;

		/** Functions *************************************************************/

		/**
		 * The main Forums admin loader
		 *
		 * @since bbPress (r2515)
		 *
		 * @uses BBP_Admin::setup_globals() Setup the globals needed
		 * @uses BBP_Admin::includes() Include the required files
		 * @uses BBP_Admin::setup_actions() Setup the hooks and actions
		 */
		public function __construct() {
			$this->setup_globals();
			$this->includes();
			$this->setup_actions();
		}

		/**
		 * Admin globals
		 *
		 * @since bbPress (r2646)
		 * @access private
		 */
		private function setup_globals() {
			$bbp              = bbpress();
			$this->admin_dir  = trailingslashit( $bbp->includes_dir . 'admin' ); // Admin path
			$this->admin_url  = trailingslashit( $bbp->includes_url . 'admin' ); // Admin url
			$this->images_url = trailingslashit( $this->admin_url . 'images' ); // Admin images URL
			$this->styles_url = trailingslashit( $this->admin_url . 'styles' ); // Admin styles URL
			$this->js_url     = trailingslashit( $this->admin_url . 'js' ); // Admin js URL
		}

		/**
		 * Include required files
		 *
		 * @since bbPress (r2646)
		 * @access private
		 */
		private function includes() {
			require $this->admin_dir . 'tools.php';
			require $this->admin_dir . 'converter.php';
			require $this->admin_dir . 'settings.php';
			require $this->admin_dir . 'functions.php';
			require $this->admin_dir . 'metaboxes.php';
			require $this->admin_dir . 'forums.php';
			require $this->admin_dir . 'topics.php';
			require $this->admin_dir . 'replies.php';
			require $this->admin_dir . 'users.php';
		}

		/**
		 * Setup the admin hooks, actions and filters
		 *
		 * @since bbPress (r2646)
		 * @access private
		 *
		 * @uses add_action() To add various actions
		 * @uses add_filter() To add various filters
		 */
		private function setup_actions() {

			// Bail to prevent interfering with the deactivation process
			if ( bbp_is_deactivation() ) {
				return;
			}

			/** General Actions */

			add_action( 'bbp_admin_menu', array( $this, 'admin_menus' ) ); // Add menu item to settings menu
			add_action( 'bbp_admin_head', array( $this, 'admin_head' ) ); // Add some general styling to the admin area
			add_action( 'bbp_admin_notices', array( $this, 'activation_notice' ) ); // Add notice if not using a Forums theme
			add_action( 'bbp_register_admin_style', array( $this, 'register_admin_style' ) ); // Add green admin style
			add_action( 'bbp_register_admin_scripts',  array( $this, 'register_admin_scripts'  ) ); // Add admin scripts
			add_action( 'bbp_activation', array( $this, 'new_install' ) ); // Add menu item to settings menu
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' )     ); // Add enqueued CSS
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) ); // Add enqueued JS
			add_action( 'wp_dashboard_setup', array( $this, 'dashboard_widget_right_now' ) ); // Forums 'Right now' Dashboard widget
			add_action( 'admin_bar_menu', array( $this, 'admin_bar_about_link' ), 15 ); // Add a link to Forums about page to the admin bar

			/** Ajax */

			// No _nopriv_ equivalent - users must be logged in
			add_action( 'wp_ajax_bbp_suggest_topic', array( $this, 'suggest_topic' ) );
			add_action( 'wp_ajax_bbp_suggest_reply', array( $this, 'suggest_reply' ) );
			add_action( 'wp_ajax_bbp_suggest_user', array( $this, 'suggest_user' ) );

			/** Filters */

			// Modify Forums' admin links
			add_filter( 'plugin_action_links', array( $this, 'modify_plugin_action_links' ), 10, 2 );

			// Map settings capabilities
			add_filter( 'bbp_map_meta_caps', array( $this, 'map_settings_meta_caps' ), 10, 4 );

			// Hide the theme compat package selection
			add_filter( 'bbp_admin_get_settings_sections', array( $this, 'hide_theme_compat_packages' ) );

			// Allow keymasters to save forums settings
			add_filter( 'option_page_capability_bbpress', array( $this, 'option_page_capability_bbpress' ) );

			// Remove "Comments" & "Discussion" metabox from bbp_get_reply_post_type() custom post type.
			add_action( 'admin_init', array( $this, 'bbp_remove_comments_discussion_meta_boxes' ), 9999 );

			/** Network Admin */

			// Add menu item to settings menu
			add_action( 'network_admin_menu', array( $this, 'network_admin_menus' ) );

			/** Dependencies */

			// Allow plugins to modify these actions
			do_action_ref_array( 'bbp_admin_loaded', array( &$this ) );

		}

		/**
		 * Remove "Comments" & "Discussion" metabox from bbp_get_reply_post_type() custom post type.
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function bbp_remove_comments_discussion_meta_boxes() {
			remove_meta_box( 'commentstatusdiv', bbp_get_reply_post_type(), 'normal' );
			remove_meta_box( 'commentsdiv', bbp_get_reply_post_type(), 'normal' );
		}

		/**
		 * Add the admin menus
		 *
		 * @since bbPress (r2646)
		 *
		 * @uses add_management_page() To add the Recount page in Tools section
		 * @uses add_options_page() To add the Forums settings page in Settings
		 *                           section
		 */
		public function admin_menus() {

			$hooks = array();

			// These are later removed in admin_head
			if ( ! is_network_admin() && ! bp_is_network_activated() ) {
				if ( current_user_can( 'bbp_tools_page' ) ) {
					if ( current_user_can( 'bbp_tools_repair_page' ) ) {
						$hooks[] = add_submenu_page(
							'buddyboss-platform',
							__( 'Repair Forums', 'buddyboss' ),
							__( 'Forum Repair', 'buddyboss' ),
							$this->minimum_capability,
							'bbp-repair',
							'bbp_admin_repair'
						);
					}

					if ( current_user_can( 'bbp_tools_import_page' ) ) {
						$hooks[] = add_submenu_page(
							'buddyboss-platform',
							__( 'Import Forums', 'buddyboss' ),
							__( 'Forum Import', 'buddyboss' ),
							$this->minimum_capability,
							'bbp-converter',
							'bbp_converter_settings'
						);
					}

					if ( current_user_can( 'bbp_tools_reset_page' ) ) {
						//				$hooks[] = add_submenu_page(
						//					'buddyboss-platform',
						//					__( 'Reset Forums', 'buddyboss' ),
						//					__( 'Forum Reset', 'buddyboss' ),
						//					$this->minimum_capability,
						//					'bbp-reset',
						//					'bbp_admin_reset'
						//				);
					}

					// Fudge the highlighted subnav item when on a Forums admin page
					foreach ( $hooks as $hook ) {
						add_action( "admin_head-$hook", 'bbp_tools_modify_menu_highlight' );
					}

				}
			}
			// Bail if plugin is not network activated
			if ( ! is_plugin_active_for_network( bbpress()->basename ) )
				return;

			add_submenu_page(
				'index.php',
				__( 'Update Forums', 'buddyboss' ),
				__( 'Update Forums', 'buddyboss' ),
				'manage_network',
				'bbp-update',
				array( $this, 'update_screen' )
			);
		}

		/**
		 * Add the network admin menus
		 *
		 * @since bbPress (r3689)
		 * @uses add_submenu_page() To add the Update Forums page in Updates
		 */
		public function network_admin_menus() {

			// Bail if plugin is not network activated
			if ( ! is_plugin_active_for_network( bbpress()->basename ) ) {
				return;
			}

			add_submenu_page(
				'upgrade.php',
				__( 'Update Forums', 'buddyboss' ),
				__( 'Update Forums', 'buddyboss' ),
				'manage_network',
				'bbpress-update',
				array( $this, 'network_update_screen' )
			);
		}

		/**
		 * If this is a new installation, create some initial forum content.
		 *
		 * @since bbPress (r3767)
		 * @return type
		 */
		public static function new_install() {
			if ( ! bbp_is_install() ) {
				return;
			}

			bbp_create_initial_content();
		}

		/**
		 * Maps settings capabilities
		 *
		 * @since bbPress (r4242)
		 *
		 * @param array  $caps Capabilities for meta capability
		 * @param string $cap Capability name
		 * @param int    $user_id User id
		 * @param mixed  $args Arguments
		 * @uses get_post() To get the post
		 * @uses apply_filters() Calls 'bbp_map_meta_caps' with caps, cap, user id and
		 *                        args
		 * @return array Actual capabilities for meta capability
		 */
		public static function map_settings_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

			// What capability is being checked?
			switch ( $cap ) {

				// BuddyBoss
				case 'bbp_settings_buddypress':
					if ( ( is_plugin_active( 'buddyboss-platform/bp-loader.php' ) && defined( 'BP_PLATFORM_VERSION' ) && bp_is_root_blog() ) && is_super_admin() ) {
						$caps = array( bbpress()->admin->minimum_capability );
					} else {
						$caps = array( 'do_not_allow' );
					}

					break;

				// Akismet
				case 'bbp_settings_akismet':
					if ( ( is_plugin_active( 'akismet/akismet.php' ) && defined( 'AKISMET_VERSION' ) ) && is_super_admin() ) {
						$caps = array( bbpress()->admin->minimum_capability );
					} else {
						$caps = array( 'do_not_allow' );
					}

					break;

				// Forums
				case 'bbp_about_page': // About and Credits
				case 'bbp_tools_page': // Tools Page
				case 'bbp_tools_repair_page': // Tools - Repair Page
				case 'bbp_tools_import_page': // Tools - Import Page
				case 'bbp_tools_reset_page': // Tools - Reset Page
				case 'bbp_settings_page': // Settings Page
				case 'bbp_settings_users': // Settings - Users
				case 'bbp_settings_features': // Settings - Features
				case 'bbp_settings_theme_compat': // Settings - Theme compat
				case 'bbp_settings_root_slugs': // Settings - Root slugs
				case 'bbp_settings_single_slugs': // Settings - Single slugs
				case 'bbp_settings_user_slugs': // Settings - User slugs
				case 'bbp_settings_per_page': // Settings - Per page
				case 'bbp_settings_per_rss_page': // Settings - Per RSS page
					$caps = array( bbpress()->admin->minimum_capability );
					break;
			}

			return apply_filters( 'bbp_map_settings_meta_caps', $caps, $cap, $user_id, $args );
		}

		/**
		 * Register the importers
		 *
		 * @since bbPress (r2737)
		 *
		 * @uses apply_filters() Calls 'bbp_importer_path' filter to allow plugins
		 *                        to customize the importer script locations.
		 */
		public function register_importers() {

			// Leave if we're not in the import section
			if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
				return;
			}

			// Load Importer API
			require_once ABSPATH . 'wp-admin/includes/import.php';

			// Load our importers
			$importers = apply_filters( 'bbp_importers', array( 'bbpress' ) );

			// Loop through included importers
			foreach ( $importers as $importer ) {

				// Allow custom importer directory
				$import_dir = apply_filters( 'bbp_importer_path', $this->admin_dir . 'importers', $importer );

				// Compile the importer path
				$import_file = trailingslashit( $import_dir ) . $importer . '.php';

				// If the file exists, include it
				if ( file_exists( $import_file ) ) {
					require $import_file;
				}
			}
		}

		/**
		 * Admin area activation notice
		 *
		 * Shows a nag message in admin area about the theme not supporting Forums
		 *
		 * @since bbPress (r2743)
		 *
		 * @uses current_user_can() To check notice should be displayed.
		 */
		public function activation_notice() {
			// @todo - something fun
		}

		/**
		 * Add Settings link to plugins area
		 *
		 * @since bbPress (r2737)
		 *
		 * @param array  $links Links array in which we would prepend our link
		 * @param string $file Current plugin basename
		 * @return array Processed links
		 */
		public static function modify_plugin_action_links( $links, $file ) {

			// Return normal links if not Forums
			if ( plugin_basename( bbpress()->file ) !== $file ) {
				return $links;
			}

			// New links to merge into existing links
			$new_links = array();

			// Settings page link
			if ( current_user_can( 'bbp_settings_page' ) ) {
				$new_links['settings'] = '<a href="' . esc_url( add_query_arg( array( 'page' => 'bbpress' ), admin_url( 'options-general.php' ) ) ) . '">' . esc_html__( 'Settings', 'buddyboss' ) . '</a>';
			}

			// About page link
			if ( current_user_can( 'bbp_about_page' ) ) {
				$new_links['about'] = '<a href="' . esc_url( add_query_arg( array( 'page' => 'bbp-about' ), admin_url( 'index.php' ) ) ) . '">' . esc_html__( 'About', 'buddyboss' ) . '</a>';
			}

			// Add a few links to the existing links array
			return array_merge( $links, $new_links );
		}

		/**
		 * Add the 'Right now in Forums' dashboard widget
		 *
		 * @since bbPress (r2770)
		 *
		 * @uses wp_add_dashboard_widget() To add the dashboard widget
		 */
		public static function dashboard_widget_right_now() {
			wp_add_dashboard_widget( 'bbp-dashboard-right-now', __( 'Right Now in Forums', 'buddyboss' ), 'bbp_dashboard_widget_right_now' );
		}

		/**
		 * Add a link to about popup for BuddyBoss in the admin bar.
		 *
		 * @since bbPress (r5136)
		 *
		 * @param WP_Admin_Bar $wp_admin_bar
		 */
		public function admin_bar_about_link( $wp_admin_bar ) {
			if ( is_user_logged_in() ) {
				$wp_admin_bar->add_menu(
					array(
						'parent' => 'wp-logo',
						'id'     => 'bbp-about',
						'title'  => esc_html__( 'About BuddyBoss', 'buddyboss' ),
						'href'   => esc_url( bp_get_admin_url( '?hello=buddyboss' ) ),
					)
				);
			}
		}

		/**
		 * Enqueue any admin scripts we might need
		 *
		 * @since bbPress (r5224)
		 */
		public function enqueue_styles() {
			wp_enqueue_style( 'bbp-admin-css' );
		}

		/**
		 * Enqueue any admin scripts we might need
		 *
		 * @since bbPress (r4260)
		 */
		public function enqueue_scripts() {
			wp_enqueue_script( 'suggest' );

			// Get the version to use for JS
			$version = bp_get_version();

			// Post type checker (only topics and replies)
			if ( 'post' === get_current_screen()->base ) {
				switch ( get_current_screen()->post_type ) {
					case bbp_get_reply_post_type():
					case bbp_get_topic_post_type():
						// Enqueue the common JS
						wp_enqueue_script( 'bbp-admin-common-js' );

						// Topics admin
						if ( bbp_get_topic_post_type() === get_current_screen()->post_type ) {
							wp_enqueue_script( 'bbp-admin-topics-js' );

							// Replies admin
						} elseif ( bbp_get_reply_post_type() === get_current_screen()->post_type ) {
							wp_enqueue_script( 'bbp-admin-replies-js' );
							$localize_array = array(
								'loading_text' => __( 'Loading', 'buddyboss' ),
							);
							wp_localize_script( 'bbp-admin-replies-js', 'replies_data', $localize_array );
						}

						break;
				}
			}

			wp_register_script( 'bbp-converter', $this->js_url . 'converter.js', array( 'jquery' ), $version );
		}

		/**
		 * Remove the individual recount and converter menus.
		 * They are grouped together by h2 tabs
		 *
		 * @since bbPress (r2464)
		 *
		 * @uses remove_submenu_page() To remove menu items with alternat navigation
		 */
		public function admin_head() {
			remove_submenu_page( 'admin.php', 'bbp-repair' );
			remove_submenu_page( 'admin.php', 'bbp-converter' );
			remove_submenu_page( 'admin.php', 'bbp-reset' );
			remove_submenu_page( 'index.php', 'bbp-about' );
			remove_submenu_page( 'index.php', 'bbp-credits' );
		}

		/**
		 * Registers the Forums admin color scheme
		 *
		 * Because wp-content can exist outside of the WordPress root there is no
		 * way to be certain what the relative path of the admin images is.
		 * We are including the two most common configurations here, just in case.
		 *
		 * @since bbPress (r2521)
		 *
		 * @uses wp_admin_css_color() To register the color scheme
		 */
		public function register_admin_style() {

			// RTL and/or minified
			$suffix = is_rtl() ? '-rtl' : '';
			// $suffix .= defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_register_style(
				'bbp-admin-css',
				$this->styles_url . 'admin.min.css',
				array( 'dashicons' ),
				bp_get_version()
			);

			// Mint
			wp_admin_css_color(
				'bbp-mint',
				esc_html__( 'Mint', 'buddyboss' ),
				$this->styles_url . 'mint' . $suffix . '.css',
				array( '#4f6d59', '#33834e', '#5FB37C', '#81c498' ),
				array(
					'base'    => '#f1f3f2',
					'focus'   => '#fff',
					'current' => '#fff',
				)
			);

			// Evergreen
			wp_admin_css_color(
				'bbp-evergreen',
				esc_html__( 'Evergreen', 'buddyboss' ),
				$this->styles_url . 'evergreen' . $suffix . '.css',
				array( '#324d3a', '#446950', '#56b274', '#324d3a' ),
				array(
					'base'    => '#f1f3f2',
					'focus'   => '#fff',
					'current' => '#fff',
				)
			);

			// Bail if already using the fresh color scheme
			if ( 'fresh' === get_user_option( 'admin_color' ) ) {
				return;
			}

			// Force 'colors-fresh' dependency
			global $wp_styles;
			$wp_styles->registered['colors']->deps[] = 'colors-fresh';
		}

		/**
		 * Registers the bbPress admin color schemes
		 *
		 * Because wp-content can exist outside of the WordPress root there is no
		 * way to be certain what the relative path of the admin images is.
		 * We are including the two most common configurations here, just in case.
		 *
		 * @since 2.6.0 bbPress (r2521)
		 */
		public function register_admin_scripts() {
			// Get the version to use for JS.
			$version = bp_get_version();

			// Header JS.
			wp_register_script( 'bbp-admin-common-js', $this->js_url . 'common.js', array( 'jquery' ), $version );
			wp_register_script( 'bbp-admin-topics-js', $this->js_url . 'topics.js', array( 'jquery' ), $version );
			wp_register_script( 'bbp-admin-replies-js', $this->js_url . 'replies.js', array( 'jquery' ), $version );
			wp_register_script( 'bbp-converter', $this->js_url . 'converter.js', array( 'jquery', 'postbox', 'dashboard' ), $version );
		}

		/**
		 * Hide theme compat package selection if only 1 package is registered
		 *
		 * @since bbPress (r4315)
		 *
		 * @param array $sections Forums settings sections
		 * @return array
		 */
		public function hide_theme_compat_packages( $sections = array() ) {
			if ( count( bbpress()->theme_compat->packages ) <= 1 ) {
				unset( $sections['bbp_settings_theme_compat'] );
			}

			return $sections;
		}

		/**
		 * Allow keymaster role to save Forums settings
		 *
		 * @since bbPress (r4678)
		 *
		 * @param string $capability
		 * @return string Return 'keep_gate' capability
		 */
		public function option_page_capability_bbpress( $capability = 'manage_options' ) {
			$capability = 'keep_gate';
			return $capability;
		}

		/** Ajax ******************************************************************/

		/**
		 * Ajax action for facilitating the discussion auto-suggest
		 *
		 * @since bbPress (r4261)
		 *
		 * @uses get_posts()
		 * @uses bbp_get_topic_post_type()
		 * @uses bbp_get_topic_id()
		 * @uses bbp_get_topic_title()
		 */
		public function suggest_topic() {

			$html = '<option value="0">' . esc_html__( '-- Select Discussion --', 'buddyboss' ) . '</option>';
			$posts = get_posts(
				array(
					's'                      => ! empty( $_REQUEST['q'] ) ? bbp_db()->esc_like( $_REQUEST['q'] ) : '',
					'post_type'              => bbp_get_topic_post_type(),
					'post_status'            => 'publish',
					'post_parent'            => $_POST['post_parent'],
					'numberposts'            => - 1,
					'orderby'                => 'title',
					'order'                  => 'ASC',
					'walker'                 => '',
					'suppress_filters'       => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			add_filter( 'list_pages', 'bbp_reply_attributes_meta_box_discussion_reply_title', 99, 2 );
			$html .= walk_page_dropdown_tree( $posts, 0 );
			remove_filter( 'list_pages', 'bbp_reply_attributes_meta_box_discussion_reply_title', 99, 2 );

			echo $html;
			wp_die();
		}

		/**
		 * Ajax action for facilitating the reply auto-suggest
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @uses get_posts()
		 * @uses bbp_get_topic_post_type()
		 * @uses bbp_get_topic_id()
		 * @uses bbp_get_topic_title()
		 */
		public function suggest_reply() {

			$html = '<option value="0">' . esc_html__( '-- Select Reply --', 'buddyboss' ) . '</option>';

			$posts = get_posts(
				array(
					'post_type'              => bbp_get_reply_post_type(),
					'post_status'            => 'publish',
					'post_parent'            => $_POST['post_parent'],
					'numberposts'            => - 1,
					'orderby'                => 'title',
					'order'                  => 'ASC',
					'walker'                 => '',
					'suppress_filters'       => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			add_filter( 'list_pages', 'bbp_reply_attributes_meta_box_discussion_reply_title', 99, 2 );
			$html .= walk_page_dropdown_tree( $posts, 0 );
			remove_filter( 'list_pages', 'bbp_reply_attributes_meta_box_discussion_reply_title', 99, 2 );

			echo $html;
			wp_die();
		}

		/**
		 * Ajax action for facilitating the topic and reply author auto-suggest
		 *
		 * @since bbPress (r5014)
		 */
		public function suggest_user() {

			// Bail early if no request
			if ( empty( $_REQUEST['q'] ) ) {
				wp_die( '0' );
			}

			// Bail if user cannot moderate - only moderators can change authorship
			if ( ! current_user_can( 'moderate' ) ) {
				wp_die( '0' );
			}

			// Check the ajax nonce
			check_ajax_referer( 'bbp_suggest_user_nonce' );

			// Try to get some users
			$users_query = new WP_User_Query(
				array(
					'search'         => '*' . bbp_db()->esc_like( $_REQUEST['q'] ) . '*',
					'fields'         => array( 'ID', 'user_nicename' ),
					'search_columns' => array( 'ID', 'user_nicename', 'user_email' ),
					'orderby'        => 'ID',
				)
			);

			// If we found some users, loop through and display them
			if ( ! empty( $users_query->results ) ) {
				foreach ( (array) $users_query->results as $user ) {
					printf( esc_html__( '%1$s - %2$s', 'buddyboss' ), bbp_get_user_id( $user->ID ), bbp_get_user_nicename( $user->ID, array( 'force' => $user->user_nicename ) ) . "\n" );
				}
			}
			die();
		}

		/** Updaters **************************************************************/

		/**
		 * Update all forums across all sites
		 *
		 * @since bbPress (r3689)
		 *
		 * @uses get_blog_option()
		 * @uses wp_remote_get()
		 */
		public static function update_screen() {

			// Get action
			$action = isset( $_GET['action'] ) ? $_GET['action'] : ''; ?>

			<div class="wrap">
				<div id="icon-edit" class="icon32 icon32-posts-topic"><br /></div>
				<h2><?php esc_html_e( 'Update Forum', 'buddyboss' ); ?></h2>

				<?php

				// Taking action
				switch ( $action ) {
					case 'bbp-update':
						// Run the full updater
						bbp_version_updater();
						?>

						<p><?php esc_html_e( 'All done!', 'buddyboss' ); ?></p>
						<a class="button" href="index.php?page=bbp-update"><?php esc_html_e( 'Go Back', 'buddyboss' ); ?></a>

						<?php

						break;

					case 'show':
					default:
						?>

						<p><?php esc_html_e( 'You can update your forum through this page. Hit the link below to update.', 'buddyboss' ); ?></p>
						<p><a class="button" href="index.php?page=bbp-update&amp;action=bbp-update"><?php esc_html_e( 'Update Forum', 'buddyboss' ); ?></a></p>

						<?php
						break;

				}
				?>

			</div>
			<?php
		}

		/**
		 * Update all forums across all sites
		 *
		 * @since bbPress (r3689)
		 *
		 * @uses get_blog_option()
		 * @uses wp_remote_get()
		 */
		public static function network_update_screen() {
			$bbp_db = bbp_db();

			// Get action
			$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
			?>

			<div class="wrap">
				<div id="icon-edit" class="icon32 icon32-posts-topic"><br /></div>
				<h2><?php esc_html_e( 'Update Forums', 'buddyboss' ); ?></h2>

				<?php

				// Taking action
				switch ( $action ) {
				case 'bbpress-update':
					// Site counter
					$n = isset( $_GET['n'] ) ? intval( $_GET['n'] ) : 0;

					// Get blogs 5 at a time
					$blogs = $bbp_db->get_results( "SELECT * FROM {$bbp_db->blogs} WHERE site_id = '{$bbp_db->siteid}' AND spam = '0' AND deleted = '0' AND archived = '0' ORDER BY registered DESC LIMIT {$n}, 5", ARRAY_A );

					// No blogs so all done!
				if ( empty( $blogs ) ) :
					?>

					<p><?php esc_html_e( 'All done!', 'buddyboss' ); ?></p>
					<a class="button" href="update-core.php?page=bbpress-update"><?php esc_html_e( 'Go Back', 'buddyboss' ); ?></a>

				<?php

				// Still have sites to loop through
				else :
				?>

					<ul>

						<?php
						foreach ( (array) $blogs as $details ) :

							$siteurl = get_blog_option( $details['blog_id'], 'siteurl' );
							?>

							<li><?php echo $siteurl; ?></li>

							<?php

							// Get the response of the Forums update on this site
							$response = wp_remote_get(
								trailingslashit( $siteurl ) . 'wp-admin/index.php?page=bbp-update&action=bbp-update',
								array(
									'timeout'     => 30,
									'httpversion' => '1.1',
								)
							);

							// Site errored out, no response?
							if ( is_wp_error( $response ) ) {
								wp_die( sprintf( __( 'Warning! Problem updating %1$s. Your server may not be able to connect to sites running on it. Error message: <em>%2$s</em>', 'buddyboss' ), $siteurl, $response->get_error_message() ) );
							}

							// Switch to the new blog
							switch_to_blog( $details['blog_id'] );

							$basename = bbpress()->basename;

							// Run the updater on this site
							if ( is_plugin_active_for_network( $basename ) || is_plugin_active( $basename ) ) {
								bbp_version_updater();
							}

							// restore original blog
							restore_current_blog();

							// Do some actions to allow plugins to do things too
							do_action( 'after_bbpress_upgrade', $response );
							do_action( 'bbp_upgrade_site', $details['blog_id'] );

						endforeach;
						?>

					</ul>

					<p>
						<?php esc_html_e( 'If your browser doesn\'t start loading the next page automatically, click this link:', 'buddyboss' ); ?>
						<a class="button" href="update-core.php?page=bbpress-update&amp;action=bbpress-update&amp;n=<?php echo ( $n + 5 ); ?>"><?php esc_html_e( 'Next Forums', 'buddyboss' ); ?></a>
					</p>
					<script type='text/javascript'>
                        <!--
                        function nextpage() {
                            location.href = 'update-core.php?page=bbpress-update&action=bbpress-update&n=<?php echo ( $n + 5 ); ?>';
                        }
                        setTimeout( 'nextpage()', 250 );
                        //-->
					</script>
				<?php

				endif;

				break;

				case 'show':
				default:
				?>

					<p><?php esc_html_e( 'You can update all the forums on your network through this page. It works by calling the update script of each site automatically. Hit the link below to update.', 'buddyboss' ); ?></p>
					<p><a class="button" href="update-core.php?page=bbpress-update&amp;action=bbpress-update"><?php esc_html_e( 'Update Forums', 'buddyboss' ); ?></a></p>

					<?php
					break;

				}
				?>

			</div>
			<?php
		}
	}
endif; // class_exists check

/**
 * Setup Forums Admin
 *
 * @since bbPress (r2596)
 *
 * @uses BBP_Admin
 */
function bbp_admin() {
	bbpress()->admin = new BBP_Admin();

	bbpress()->admin->converter = new BBP_Converter();
}
