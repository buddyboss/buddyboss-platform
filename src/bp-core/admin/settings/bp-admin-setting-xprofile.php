<?php
/**
 * Add admin Profiles settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main Profile Settings class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Admin_Setting_Xprofile extends BP_Admin_Setting_tab {

	/**
	 * Initialize class.
	 */
	public function initialize() {
		$this->tab_label = __( 'Profiles', 'buddyboss' );
		$this->tab_name  = 'bp-xprofile';
		$this->tab_order = 10;

		$this->active_tab = bp_core_get_admin_active_tab();
	}

	/**
	 * Save options.
	 */
	public function settings_save() {
		parent::settings_save();
	}

	/**
	 * Register setting fields.
	 */
	public function register_fields() {

		/**
		 * Fires to register xProfile tab settings fields and section.
		 *
		 * @since BuddyBoss 1.2.6
		 *
		 * @param Object $this BP_Admin_Setting_Xprofile.
		 */
		do_action( 'bp_admin_setting_xprofile_register_fields', $this );
	}

}

return new BP_Admin_Setting_Xprofile();
