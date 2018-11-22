<?php
/**
 * Plugin Name:  LearnDash BuddyPress Groups Reports
 * Plugin URI:   https://www.buddyboss.com/
 * Description:  Automatically add Groups Reports to the Group that are being add by LearnDash BuddyPress Groups Sync.
 * Version:      1.0.0
 * Author:       BuddyBoss
 * Author URI:   https://www.buddyboss.com/
 * Text Domain:  ld_bp_groups_reports
 * License:      GPL3
 * License URI:  https://www.gnu.org/licenses/gpl-3.0.html
 */

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
			self::$instance->setup_constants();

			register_activation_hook( __FILE__, array( $this, 'activation' ) );

			add_action( 'plugins_loaded', array( $this, 'init' ), 1000 );
			add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );
		}

		/**
		 * Setup constants.
		 *
		 * @access  private
		 */
		private function setup_constants() {

			if ( ! defined( 'LEARNDASH_BUDDYPRESS_GROUP_REPORTS_VERSION' ) ) {
				define( 'LEARNDASH_BUDDYPRESS_GROUP_REPORTS_VERSION', '1.0.0' );
			}

			if ( ! defined( 'LEARNDASH_BUDDYPRESS_GROUP_REPORTS_SLUG' ) ) {
				define( 'LEARNDASH_BUDDYPRESS_GROUP_REPORTS_SLUG', 'learndash-buddypress-groups-reports' );
			}

			if ( ! defined( 'LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_FILE' ) ) {
				define( 'LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_FILE', __FILE__ );
			}

			if ( ! defined( 'LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_DIR' ) ) {
				define( 'LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_DIR', dirname( LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_FILE ) );
			}

			if ( ! defined( 'LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_URL' ) ) {
				define( 'LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_URL', plugin_dir_url( LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_FILE ) );
			}

			if ( ! defined( 'LEARNDASH_BUDDYPRESS_GROUP_REPORTS_BASENAME' ) ) {
				define( 'LEARNDASH_BUDDYPRESS_GROUP_REPORTS_BASENAME', plugin_basename( LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_FILE ) );
			}

			if ( ! defined( 'LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_DIR_PATH' ) ) {
				define( 'LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_DIR_PATH', plugin_dir_path( LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_FILE ) );
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

			if ( $this->get_environment_warning() && function_exists( 'ld_bp_groups_sync' ) && ld_bp_groups_sync()->requirement->valid() ) {
				self::$instance->hooks();
				self::$instance->includes();
			}
		}


		public function activation() {

			require_once LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_DIR . '/includes/helpers.php';

			$groups_reports = get_option( 'learndash_settings_buddypress_groups_reports' );
			if ( empty( $groups_reports ) ) {
				update_option( 'learndash_settings_buddypress_groups_reports', ld_bp_groups_reports_default_value() );
			}
		}

		/**
		 * Hooks.
		 *
		 * @access public
		 */
		public function hooks() {

			$this->load_text_domain();

			add_filter( 'plugin_action_links_' . LEARNDASH_BUDDYPRESS_GROUP_REPORTS_BASENAME, array(
				$this,
				'action_links'
			), 10, 2 );


			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		}

		public function enqueue() {

			wp_register_script( 'ld-bp-courses-reports', LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_URL . 'assests/js/frountend.js', array( 'jquery' ) );
			// Localize the script with new data
			$translation_array = array(
				'export_csv_error' => __( 'Something when wrong kindly try after some time.', 'ld_bp_groups_reports' ),
			);
			wp_localize_script( 'ld-bp-courses-reports', 'ld_bp_courses_reports', $translation_array );
		}

		/**
		 * Check plugin for LearnDash_BuddyPress_Groups_Sync environment.
		 *
		 * @access public
		 *
		 * @return bool
		 */
		public function get_environment_warning() {
			// Flag to check whether plugin file is loaded or not.
			$is_working = true;
			// Verify dependency cases.
			if ( ! class_exists( 'LearnDash_BuddyPress_Groups_Sync' ) ) {
				// Show admin notice.
				$this->add_admin_notice( 'prompt_ld_bp_groups_reports_incompatible', 'error', sprintf( __( '<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">LearnDash BuddyPress Groups Sync</a> plugin installed and activated for LearnDash BuddyPress Groups Reports to activate.', 'ld_bp_groups_reports' ), 'https://buddybboss.com' ) );
				$is_working = false;
			}

			return $is_working;
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

			require_once LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_DIR . '/includes/class-learndash.php';

			require_once LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_DIR . '/includes/settings/class-learndash-settings-page.php';

			require_once LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_DIR . '/includes/class-groups-reports.php';
		}

		/**
		 * Load Plugin Text Domain
		 *
		 * Looks for the plugin translation files in certain directories and loads
		 * them to allow the plugin to be localised
		 *
		 * @access public
		 *
		 * @return bool True on success, false on failure.
		 */
		public function load_text_domain() {
			// Traditional WordPress plugin locale filter.
			$locale = apply_filters( 'plugin_locale', get_locale(), 'ld_bp_groups_reports' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'ld_bp_groups_reports', $locale );
			// Setup paths to current locale file.
			$mofile_local = trailingslashit( LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_DIR_PATH . 'languages' ) . $mofile;
			if ( file_exists( $mofile_local ) ) {
				// Look in the /wp-content/plugins/learndash-buddypress-groups-reports/languages/ folder.
				load_textdomain( 'ld_bp_groups_reports', $mofile_local );
			} else {
				// Load the default language files.
				load_plugin_textdomain( 'ld_bp_groups_reports', false, trailingslashit( LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_DIR_PATH . 'languages' ) );
			}

			return false;
		}

		/**
		 * Adding additional setting page link along plugin's action link.
		 *
		 * @access  public
		 *
		 * @param   array $actions get all actions.
		 *
		 * @return  array       return new action array
		 */
		public function action_links( $actions ) {

			$actions[] = sprintf( '<a href="%s">%s</a>', add_query_arg( 'page', 'learndash_lms_settings_buddypress_groups_reports', admin_url( 'admin.php' ) ), __( 'Settings', 'ld_bp_groups_reports' ) );

			return $actions;
		}

		/**
		 * Allow this class and other classes to add notices.
		 *
		 * @param $slug
		 * @param $class
		 * @param $message
		 */
		public function add_admin_notice( $slug, $class, $message ) {
			$this->notices[ $slug ] = array(
				'class'   => $class,
				'message' => $message,
			);
		}

		/**
		 * Display admin notices.
		 */
		public function admin_notices() {
			$allowed_tags = array(
				'a'      => array(
					'href'  => array(),
					'title' => array(),
					'class' => array(),
					'id'    => array(),
				),
				'br'     => array(),
				'em'     => array(),
				'span'   => array(
					'class' => array(),
				),
				'strong' => array(),
			);
			foreach ( (array) $this->notices as $notice_key => $notice ) {
				echo "<div class='" . esc_attr( $notice['class'] ) . "'><p>";
				echo wp_kses( $notice['message'], $allowed_tags );
				echo '</p></div>';
			}
		}
	} //End LearnDash_BuddyPress_Groups_Reports Class.
endif;
/**
 * Loads a single instance of LearnDash_BuddyPress_Groups_Reports.
 *
 * This follows the PHP singleton design pattern.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @example <?php $learndash_bbudypress_groups_reports = learndash_bbudypress_groups_reports(); ?>
 *
 * @see     LearnDash_BuddyPress_Groups_Reports::get_instance()
 *
 * @return object LearnDash_BuddyPress_Groups_Reports Returns an instance of the  class
 */
function learndash_bbudypress_groups_reports() {
	return LearnDash_BuddyPress_Groups_Reports::get_instance();
}

learndash_bbudypress_groups_reports();