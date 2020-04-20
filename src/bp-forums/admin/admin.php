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
