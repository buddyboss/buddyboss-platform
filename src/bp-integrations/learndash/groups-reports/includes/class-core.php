<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LearnDash_BuddyPress_Groups_Reports' ) ) :
	/**
	 * LearnDash_BuddyPress_Groups_Reports Class
	 *
	 * @package LearnDash_BuddyPress_Groups_Reports
	 */
	final class LearnDash_BuddyPress_Groups_Reports {
		/**
		 * Holds the instance
		 *
		 * Ensures that only one instance of LearnDash_BuddyPress_Groups_Reports exists in memory at any one
		 * time and it also prevents needing to define globals all over the place.
		 *
		 * TL;DR This is a static property property that holds the singleton instance.
		 *
		 * @var object
		 * @static
		 */
		private static $instance;

		/*
		 * Notices (array)
		 *
		 * @var array
		 */
		public $notices = array();

		/**
		 * Get the instance and store the class inside it. This plugin utilises
		 * the PHP singleton design pattern.
		 *
		 * @static
		 * @staticvar array $instance
		 * @access    public
		 *
		 * @see       LearnDash_BuddyPress_Groups_Reports();
		 *
		 * @uses      LearnDash_BuddyPress_Groups_Reports::hooks() Setup hooks and actions.
		 * @uses      LearnDash_BuddyPress_Groups_Reports::includes() Loads all the classes.
		 * @uses      LearnDash_BuddyPress_Groups_Reports::licensing() Add LearnDash_BuddyPress_Groups_Reports License.
		 *
		 * @return object self::$instance Instance
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof LearnDash_BuddyPress_Groups_Reports ) ) {
				self::$instance = new LearnDash_BuddyPress_Groups_Reports();
				self::$instance->setup();
			}

			return self::$instance;
		}

		/**
		 * Setup LearnDash_BuddyPress_Groups_Reports.
		 *
		 * @access private
		 */
		private function setup() {
			$this->setup_constants();
			$this->check_settings();
			$this->init();
		}

		/**
		 * Setup constants.
		 *
		 * @access  private
		 */
		private function setup_constants() {
			if ( ! defined( 'LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_DIR' ) ) {
				define( 'LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_DIR', bp_learndash_path() . 'groups-reports' );
			}

			if ( ! defined( 'LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_URL' ) ) {
				define( 'LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_URL', bp_learndash_url() . 'groups-reports' );
			}
		}

		/**
		 * Init LearnDash_BuddyPress_Groups_Reports.
		 *
		 * Sets up hooks, licensing and includes files.
		 *
		 * @access public
		 *
		 * @return void
		 */
		public function init() {
			global $bp_learndash_requirement;

			if ( $bp_learndash_requirement && $bp_learndash_requirement->valid() ) {
				$this->hooks();
				$this->includes();
			}
		}


		public function check_settings() {
			if ( ! get_option( 'learndash_settings_buddypress_groups_report' ) ) {
				$groups_report = get_option( 'learndash_settings_buddypress_groups_reports' ) ?: ld_bp_groups_reports_default_value();
				delete_option( 'learndash_settings_buddypress_groups_reports' );
				update_option( 'learndash_settings_buddypress_groups_report', $groups_report );
			}
		}

		/**
		 * Hooks.
		 *
		 * @access public
		 */
		public function hooks() {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		}

		public function enqueue() {

			wp_register_script( 'ld-bp-courses-reports', LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_URL . '/assets/js/frontend.js', array( 'jquery' ) );
			// Localize the script with new data
			$translation_array = array(
				'export_csv_error' => __( 'Something when wrong kindly try after some time.', 'buddyboss' ),
			);
			wp_localize_script( 'ld-bp-courses-reports', 'ld_bp_courses_reports', $translation_array );
		}

		/**
		 * Reset the instance of the class
		 *
		 * @access public
		 */
		public static function reset() {
			self::$instance = null;
		}

		/**
		 * Includes.
		 *
		 * @access private
		 */
		private function includes() {
			require_once LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_DIR . '/includes/helpers.php';

			if ( ld_bp_groups_sync_get_settings( 'display_bp_group_cources' ) ) {
				require_once LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_DIR . '/includes/class-groups-reports.php';
			}
		}
	} //End LearnDash_BuddyPress_Groups_Reports Class.
endif;
