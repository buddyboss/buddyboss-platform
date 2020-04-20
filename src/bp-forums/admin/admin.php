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
