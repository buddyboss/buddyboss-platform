<?php
/**
 * @package WordPress
 * @subpackage BuddyPress for LearnDash
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BuddyPress_LearnDash_Plugin' ) ):
	/**
	 *
	 * BuddyPress for LearnDash Plugin Main Controller
	 * **************************************
	 *
	 *
	 */
	class BuddyPress_LearnDash_Plugin {
		/* Includes
		* ===================================================================
		*/

		/**
		 * Most WordPress/BuddyPress plugin have the includes in the function
		 * method that loads them, we like to keep them up here for easier
		 * access.
		 * @var array
		 */
		private $main_includes = array(
			'bp-learndash-loader',
			'bp-learndash-activity',
			'bp-learndash-functions',
			'bp-learndash-courses',
			'bp-learndash-template',
			'bp-learndash-groups',
			'bp-learndash-group-settings',
			'bp-learndash-group-experiences'
		);

		/**
		 * Admin includes
		 * @var array
		 */
		private $admin_includes = array(
			'bp-learndash-users-enrollment'
		);

		/**
		 * This options array is setup during class instantiation, holds
		 * default and saved options for the plugin.
		 *
		 * @var array
		 */
		public $options = array();

		/**
		 * Is BuddyPress installed and activated?
		 * @var boolean
		 */
		public $bp_enabled = false;

		/* Paths
		 * ===================================================================
		 */

		public $plugin_dir = '';
		public $plugin_url = '';
		public $assets_dir = '';
		public $assets_url = '';
		public $templates_dir = '';
		public $templates_url = '';
		private $data;

		/* Singleton
		 * ===================================================================
		 */

		/**
		 * Main BuddyPress for LearnDash Instance.
		 *
		 * BuddyPress for LearnDash is great
		 * Please load it only one time
		 * For this, we thank you
		 *
		 * Insures that only one instance of BuddyPress for LearnDash exists in memory at any
		 * one time. Also prevents needing to define globals all over the place.
		 *
		 * @since BuddyPress for LearnDash (1.0.0)
		 *
		 * @static object $instance
		 * @uses BuddyPress_LearnDash_Plugin::setup_globals() Setup the globals needed.
		 * @uses BuddyPress_LearnDash_Plugin::setup_actions() Setup the hooks and actions.
		 * @see  buddypress_learndash()
		 *
		 * @return BuddyPress for LearnDash The one true BuddyBoss.
		 */
		public static function instance() {
			// Store the instance locally to avoid private static replication
			static $instance = null;

			// Only run these methods if they haven't been run previously
			if ( null === $instance ) {
				$instance = new BuddyPress_LearnDash_Plugin;
				$instance->setup_globals();
				$instance->setup_actions();
			}

			// Always return the instance
			return $instance;
		}

		/* Magic Methods
		 * ===================================================================
		 */

		/**
		 * A dummy constructor to prevent BuddyPress for LearnDash from being loaded more than once.
		 *
		 * @since BuddyPress for LearnDash (1.0.0)
		 * @see BuddyPress_LearnDash_Plugin::instance()
		 * @see buddypress()
		 */
		private function __construct() { /* Do nothing here */
		}
		/* Plugin Specific, Setup Globals, Actions, Includes
		 * ===================================================================
		 */

		/**
		 * Setup BuddyPress for LearnDash plugin global variables.
		 *
		 * @since 1.0.0
		 * @access private
		 *
		 * @uses plugin_dir_path() To generate BuddyPress for LearnDash plugin path.
		 * @uses plugin_dir_url() To generate BuddyPress for LearnDash plugin url.
		 * @uses apply_filters() Calls various filters.
		 */
		private function setup_globals() {

//			$this->options = bp_ld_sync( 'settings' )->get( 'coursetab' );

			/** Versions ************************************************* */
			$this->version = BP_PLATFORM_VERSION;

			/** Paths***************************************************** */
			// BuddyPress for LearnDash root directory
			$this->plugin_dir = buddypress()->compatibility_dir . 'buddypress-learndash/';
			$this->plugin_url = buddypress()->compatibility_url . 'buddypress-learndash/';

			// Templates
			$this->templates_dir = $this->plugin_dir . 'templates';
			$this->templates_url = $this->plugin_url . 'templates';

			// Assets
			$this->assets_dir = $this->plugin_dir . 'assets';
			$this->assets_url = $this->plugin_url . 'assets';
		}

		/**
		 * Set up the default hooks and actions.
		 *
		 * @since BuddyPress for LearnDash (1.0.0)
		 * @access private
		 *
		 * @uses register_activation_hook() To register the activation hook.
		 * @uses register_deactivation_hook() To register the deactivation hook.
		 * @uses add_action() To add various actions.
		 */
		public function setup_actions() {

			if ( ! is_admin() && ! is_network_admin() ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'bp_learndash_enqueue_styles' ), 11 );
			}

			add_action( 'bp_init', array( $this, 'bp_learndash_add_group_course_extension' ), 10 );

			// Hook into BuddyPress init
			add_action( 'bp_loaded', array( $this, 'bp_loaded' ) );
			add_action( 'bp_init', array( $this, 'load_group_extension' ), 10 );

			//Admin setting page
			add_action( 'init', array( $this, 'bp_learndash_admin_settings' ) );
		}

		public function bp_learndash_enqueue_styles() {

			wp_enqueue_style( 'buddyboss', $this->assets_url . '/css/buddypress-learndash.min.css', array(), BP_PLATFORM_VERSION, 'all' );

		}

		/**
		 * Init buddypress learndash admin settings page
		 */
		public function bp_learndash_admin_settings() {
			// Admin
			if ( ( is_admin() || is_network_admin() ) && current_user_can( 'manage_options' ) ) {
				$this->load_admin();
			}
		}

		/**
		 * We require BuddyPress to run the main components, so we attach
		 * to the 'bp_loaded' action which BuddyPress calls after it's started
		 * up. This ensures any BuddyPress related code is only loaded
		 * when BuddyPress is active.
		 *
		 * @since BuddyPress for LearnDash (1.0.0)
		 * @access public
		 *
		 * @return void
		 */
		public function bp_loaded() {
			$this->load_main();
		}

		/* Load
		* ===================================================================
		*/

		/**
		 * Include required admin files.
		 *
		 * @since BuddyPress for LearnDash (1.0.0)
		 * @access private
		 *
		 * @uses $this->do_includes() Loads array of files in the include folder
		 */
		public function load_admin() {
			$this->do_includes( $this->admin_includes );
		}

		/**
		 * Include required files.
		 *
		 * @since BuddyPress for LearnDash (1.0.0)
		 * @access private
		 *
		 * @uses BuddyPress_LearnDash_Plugin::do_includes() Loads array of files in the include folder
		 */
		private function load_main() {
			$this->do_includes( $this->main_includes );

			BuddyPress_LearnDash_Loader::instance();
		}

		function load_group_extension() {
			if ( bp_is_active( 'groups' ) && current_user_can( 'edit_courses' ) ) {
				bp_register_group_extension( 'Group_Extension_Course_Settings' );
			}
		}

		/**
		 * Include required array of files in the includes directory
		 *
		 * @since BuddyPress for LearnDash (1.0.0)
		 *
		 * @uses require_once() Loads include file
		 */
		public function do_includes( $includes = array() ) {
			foreach ( (array) $includes as $include ) {
				require_once( $this->plugin_dir . '/' . $include . '.php' );
			}
		}

		/**
		 * Convenience function to access plugin options, returns false by default
		 *
		 * @since  BuddyPress for LearnDash (1.0.0)
		 *
		 * @param  string $key Option key
		 *
		 * @uses apply_filters() Filters option values with 'buddypress_learndash_option' &
		 *                       'buddypress_learndash_option_{$option_name}'
		 * @uses sprintf() Sanitizes option specific filter
		 *
		 * @return mixed Option value (false if none/default)
		 *
		 */
		public function option( $key ) {
			$key    = strtolower( $key );
			$option = isset( $this->options[ $key ] ) ? $this->options[ $key ] : null;

			// Apply filters on options as they're called for maximum
			// flexibility. Options are are also run through a filter on
			// class instatiation/load.
			// ------------------------
			// This filter is run for every option
			$option = apply_filters( 'buddypress_learndash_option', $option );

			// Option specific filter name is converted to lowercase
			$filter_name = sprintf( 'buddypress_learndash_option_%s', strtolower( $key ) );
			$option      = apply_filters( $filter_name, $option );

			return $option;
		}

		/**
		 * Load Group Course extension
		 */
		public function bp_learndash_add_group_course_extension() {
			if ( bp_is_active( 'groups' ) ) {
				bp_register_group_extension( 'GType_Course_Tab' );
			}
		}

	}

endif;

global $BUDDYPRESS_LEARNDASH;
$BUDDYPRESS_LEARNDASH = BuddyPress_LearnDash_Plugin::instance();

?>