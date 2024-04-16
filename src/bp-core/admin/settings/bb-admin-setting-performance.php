<?php
/**
 * Add admin performance settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 2.5.80
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main performance settings class.
 *
 * @since BuddyBoss 2.5.80
 */
class BB_Admin_Setting_Performance extends BP_Admin_Setting_tab {

	/**
	 * Initial method for this class.
	 *
	 * @since BuddyBoss 2.5.80
	 *
	 * @return void
	 */
	public function initialize() {
		$this->tab_label = esc_html__( 'Performance', 'buddyboss' );
		$this->tab_name  = 'bp-performance';
		$this->tab_order = 90;
	}

	public function settings_save() {

		// Get old values for cpt and check if it disabled then keep it and later will save it.
		$bb_activity_load_type = isset( $_POST['bb_activity_load_type'] ) ? sanitize_text_field( wp_unslash( $_POST['bb_activity_load_type'] ) ) : '';
		bp_update_option( 'bb_activity_load_type', $bb_activity_load_type );

		parent::settings_save();
	}

	/**
	 * Register setting fields
	 *
	 * @since BuddyBoss 2.5.80
	 *
	 * @return void
	 */
	public function register_fields() {
		$this->add_section( 'bb_performance_general', __( 'General', 'buddyboss' ), '', 'bb_admin_performance_general_setting_tutorial' );
		$this->add_field( 'bb_ajax_request_page_load', __( 'Page requests', 'buddyboss' ), 'bb_admin_performance_setting_general_callback', 'intval' );


		if ( bp_is_active( 'activity' ) ) {
			$this->add_section( 'bb_performance_activity', __( 'Activity', 'buddyboss' ), '', 'bb_admin_performance_activity_setting_tutorial' );
			$this->add_field( 'bb_load_activity_per_request', __( 'Activity loading', 'buddyboss' ), 'bb_admin_performance_setting_activity_callback', 'intval' );
		}

		/**
		 * Fires to register Performance tab settings fields and section.
		 *
		 * @since BuddyBoss 2.5.80
		 *
		 * @param Object $this BB_Admin_Setting_Performance.
		 */
		do_action( 'bb_admin_setting_performance_register_fields', $this );
	}
}

// Class instance.
return new BB_Admin_Setting_Performance();
