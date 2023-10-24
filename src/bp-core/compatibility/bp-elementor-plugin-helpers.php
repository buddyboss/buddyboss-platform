<?php
/**
 * Added support for third party plugin Elementor.
 *
 * @package BuddyBoss
 *
 * @since BuddyBoss 2.1.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BB_Elementor_Plugin_Compatibility') ) {
	/**
	 * BB_Elementor_Plugin_Compatibility Class
	 *
	 * This class handles compatibility code for third party plugins used in conjunction with Platform
	 *
	 * @since BuddyBoss 2.1.2
	 */
	class BB_Elementor_Plugin_Compatibility {

		/**
		 * The single instance of the class.
		 *
		 * @since BuddyBoss 2.1.2
		 * @var self
		 *
		 */
		private static $instance = null;

		/**
		 * BB_Elementor_Plugin_Compatibility constructor.
		 *
		 * @since BuddyBoss 2.1.2
		 */
		public function __construct() {

			$this->compatibility_init();
		}

		/**
		 * Get the instance of this class.
		 *
		 * @since BuddyBoss 2.1.2
		 *
		 * @return Controller|null
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				$class_name     = __CLASS__;
				self::$instance = new $class_name();
			}

			return self::$instance;
		}

		/**
		 * Register the compatibility hook for the plugin
		 *
		 * @since BuddyBoss 2.1.2
		 *
		 * @return void
		 */
		public function compatibility_init() {

			add_action( 'bp_core_set_uri_globals', array( $this, 'elementor_library_preview_permalink' ), 10, 2 );
			add_action( 'bp_init', array( $this, 'maintenance_mode_template' ) );
			add_action( 'admin_menu', array( $this, 'remove_page_attributes_metabox_for_forum' ) );

			add_filter( 'bp_core_set_uri_show_on_front', array( $this, 'set_uri_elementor_show_on_front' ), 10, 3 );

		}

		/**
		 * Update the current component and action for elementor saved library preview link.
		 *
		 * @since BuddyBoss 2.1.2
		 *
		 * @param object $bp     BuddyPress object.
		 * @param array  $bp_uri Array of URI.
		 *
		 * @return void
		 */
		public function elementor_library_preview_permalink( $bp, $bp_uri ) {

			if ( isset( $_GET['elementor_library'] ) ) {
				$bp->current_component = '';
				$bp->current_action    = '';
			}

		}

		/**
		 * Prevent BB template redering and Redirect to the Elementor "Maintenance Mode" template.
		 *
		 * @since BuddyBoss 1.5.8
		 *
		 * @return void
		 */
		public function maintenance_mode_template() {
			if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
				return;
			}

			static $user = null;

			if ( isset( $_GET['elementor-preview'] ) && get_the_ID() === (int) $_GET['elementor-preview'] ) {
				return;
			}

			$is_login_page = apply_filters( 'elementor/maintenance_mode/is_login_page', false );

			if ( $is_login_page ) {
				return;
			}

			if ( null === $user ) {
				$user = wp_get_current_user();
			}

			$exclude_mode = get_option( 'elementor_maintenance_mode_exclude_mode' );

			if ( 'logged_in' === $exclude_mode && is_user_logged_in() ) {
				return;
			}

			if ( 'custom' === $exclude_mode ) {
				$exclude_roles = get_option( 'elementor_maintenance_mode_exclude_roles' );
				$user_roles    = $user->roles;

				if ( is_multisite() && is_super_admin() ) {
					$user_roles[] = 'super_admin';
				}

				$compare_roles = array_intersect( $user_roles, $exclude_roles );

				if ( ! empty( $compare_roles ) ) {
					return;
				}
			}

			$mode = get_option( 'elementor_maintenance_mode_mode' );

			if ( 'maintenance' === $mode || 'coming_soon' === $mode ) {
				remove_action( 'template_redirect', 'bp_template_redirect', 10 );
			}
		}

		/**
		 * Fix Elementor conflict for forum parent field.
		 * Remove the Page Attributes meta box from forum edit page
		 * since Element's page attributes parent field is conflicting with forum attributes patent field
		 *
		 * @since BuddyBoss 1.7.6
		 *
		 * @return void
		 */
		public function remove_page_attributes_metabox_for_forum() {
			// Check if elementor is exists.
			if ( class_exists( '\Elementor\Plugin' ) ) {
				// Remove the page attribute meta box for forum screen.
				remove_meta_box( 'pageparentdiv', 'forum', 'side' );
			}
		}

		/**
		 * Fix elementor editor issue while bp page set as front.
		 *
		 * @since BuddyBoss 1.5.0
		 *
		 * @param boolean $bool Boolean to return.
		 *
		 * @return boolean
		 */
		public function set_uri_elementor_show_on_front( $bool ) {
			if (
				isset( $_REQUEST['elementor-preview'] )
				|| (
					is_admin() &&
					isset( $_REQUEST['action'] )
					&& (
						'elementor' === $_REQUEST['action']
						|| 'elementor_ajax' === $_REQUEST['action']
					)
				)
			) {
				return false;
			}

			return $bool;
		}
	}
}

BB_Elementor_Plugin_Compatibility::instance();
