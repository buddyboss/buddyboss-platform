<?php
/**
 * Legacy General Settings tab.
 *
 * Fields migrated to Settings 2.0 Advanced feature. This class is kept ONLY
 * to fire the `bp_admin_setting_general_register_fields` action, which the
 * BuddyBoss Sharing plugin hooks into for Site SEO settings.
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Advanced feature instead.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * General Settings class (stub).
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION]
 */
class BP_Admin_Setting_General extends BP_Admin_Setting_tab {

	/**
	 * Initialize.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function initialize() {
		$this->tab_label = __( 'General', 'buddyboss' );
		$this->tab_name  = 'bp-general';
		$this->tab_order = 0;
	}

	/**
	 * Register fields.
	 *
	 * All fields removed — migrated to Settings 2.0 Advanced feature.
	 * Only the extensibility hook is preserved for third-party plugins.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION]
	 */
	public function register_fields() {

		/**
		 * Fires to register General tab settings fields and section.
		 *
		 * @since BuddyBoss 1.2.6
		 *
		 * @param Object $this BP_Admin_Setting_General.
		 */
		do_action( 'bp_admin_setting_general_register_fields', $this );
	}
}

return new BP_Admin_Setting_General();
