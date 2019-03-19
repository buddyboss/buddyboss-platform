<?php
/**
 * AppBoss integration admin tab
 *
 * @package BuddyBoss\AppBoss
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Setup AppBoss integration admin tab class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Appboss_Admin_Integration_Tab extends BP_Admin_Integration_tab {
	protected $current_section;

	public function initialize() {
		$this->tab_order = 30;
		$this->intro_template = $this->root_path . '/templates/admin/integration-tab-intro.php';
	}

	public function settings_save() {
		$settings = $_REQUEST;

		/**
		 * After BuddyBoss Platform - Appboss Integration settings are saved
		 *
		 * @since BuddyBoss 1.0.0
		 */
		do_action('bp_appboss_fields_updated', $settings);
	}

	public function register_fields() {
		$this->add_section(
			$this->current_section,
			__('Section Heading', 'buddyboss')
		);
	}
}
