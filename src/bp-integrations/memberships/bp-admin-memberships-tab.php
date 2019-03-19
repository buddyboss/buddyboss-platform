<?php
/**
 * Memberships integration admin tab
 *
 * @package BuddyBoss\Memberships
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Setup Memberships integration admin tab class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Memberships_Admin_Integration_Tab extends BP_Admin_Integration_tab {

	protected $current_section;

	public function initialize() {
		$this->tab_order = 10;
		$this->intro_template = $this->root_path . '/templates/admin/integration-tab-intro.php';
		$this->current_section = 'bp-memberships-section';
	}

	public function settings_save() {
		$settings = $_REQUEST;

		//@See : bp-core-options.php->bp_update_option()

		/**
		 * After Memberships settings are saved
		 *
		 * @since BuddyBoss 1.0.0
		 */
		do_action('bp_memberships_fields_updated', $settings);
	}

	public function register_fields() {

		$this->add_section(
			$this->current_section, // Unique Identifier
			__('Global Settings', 'buddyboss') //Title
		);
	}
}