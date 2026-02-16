<?php
/**
 * Add admin Activity settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main activity settings class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Admin_Setting_Activity extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Activity', 'buddyboss' );
		$this->tab_name  = 'bp-activity';
		$this->tab_order = 40;
	}

	public function is_active() {
		return bp_is_active( 'activity' );
	}

	public function register_fields() {

		$this->add_section( 'bp_activity', __( 'Activity Settings', 'buddyboss' ), '', 'bp_activity_settings_tutorial' );

		// Allow link preview.
		$this->add_field( '_bp_enable_activity_link_preview', __( 'Link Previews', 'buddyboss' ), 'bp_admin_setting_callback_enable_activity_link_preview', 'intval' );

		// Allow subscriptions setting.
		if ( is_plugin_active( 'akismet/akismet.php' ) && defined( 'AKISMET_VERSION' ) ) {
			// $this->add_field( '_bp_enable_akismet', __( 'Akismet', 'buddyboss' ), 'bp_admin_setting_callback_activity_akismet', 'intval' );
		}

		/**
		 * Fires to register Activity tab settings fields and section.
		 *
		 * @since BuddyBoss 1.2.6
		 *
		 * @param Object $this BP_Admin_Setting_Activity.
		 */
		do_action( 'bp_admin_setting_activity_register_fields', $this );
	}

}

return new BP_Admin_Setting_Activity();
