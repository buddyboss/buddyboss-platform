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
		$this->tab_order = 10;
		$this->intro_template = $this->root_path . '/templates/admin/integration-tab-intro.php';
		$this->current_section = 'bp_appboss-integration';
	}

	public function settings_save() {
		$settings = $_REQUEST;

		//@See : bp-core-options.php->bp_update_option()
		bp_update_option('bp-appboss_enabled', $settings['bp-appboss_enabled']);
	}

	public function register_fields() {
		$this->add_section(
			$this->current_section,
			__('AppBoss Settings', 'buddyboss')
		);
	}

	/**
	 * Case-1: AppBoss plugin is not active but form_html would still be executed
	 * Case-2: AppBoss plugin is active
	 */
	public function form_html() {

		if (!is_plugin_active($this->required_plugin)) {

			$this->intro_template = $this->root_path . '/templates/admin/integration-tab-intro.php';
			if (is_file($this->intro_template)) {
				require $this->intro_template;
			}

		} else {
			//NOTE : AppBoss is active, so display form
			parent::form_html();
			echo "(AppBoss is activated)";
		}

	}
}
