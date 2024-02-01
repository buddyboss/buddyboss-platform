<?php
/**
 * Add admin performance settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main performance settings class.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Admin_Setting_Performance extends BP_Admin_Setting_tab {

	/**
	 * Initial method for this class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function initialize() {
		$this->tab_label = esc_html__( 'Performance', 'buddyboss' );
		$this->tab_name  = 'bp-performance';
		$this->tab_order = 90;
	}

	/**
	 * Register setting fields
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function register_fields() {
		$this->add_section( 'bb_general', __( 'General', 'buddyboss' ), '', 'bb_admin_performance_general_setting_tutorial' );
		$this->add_field( 'bb_ajax_request_page_load', __( 'Page requests', 'buddyboss' ), 'bb_admin_performance_setting_general_callback', 'intval' );


		$this->add_section( 'bb_activity', __( 'Activity', 'buddyboss' ), '', 'bb_admin_performance_activity_setting_tutorial' );
		$this->add_field( 'bb_load_activity_per_request', __( 'Activity loading', 'buddyboss' ), 'bb_admin_performance_setting_activity_callback', 'intval' );

		/**
		 * Fires to register Performance tab settings fields and section.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param Object $this BB_Admin_Setting_Performance.
		 */
		do_action( 'bp_admin_setting_performance_register_fields', $this );
	}
}

// Class instance.
return new BB_Admin_Setting_Performance();
