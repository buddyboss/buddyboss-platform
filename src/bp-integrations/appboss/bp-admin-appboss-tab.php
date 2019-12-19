<?php
/**
 * AppBoss integration admin tab
 *
 * @package BuddyBoss\AppBoss
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup AppBoss integration admin tab class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Appboss_Admin_Integration_Tab extends BP_Admin_Integration_tab {
	protected $current_section;

	public function initialize() {
		$this->tab_order       = 10;
		$this->intro_template  = $this->root_path . '/templates/admin/integration-tab-intro.php';
		$this->current_section = 'bp_appboss-integration';
	}

	public function form_html() {
		$this->intro_template = $this->root_path . '/templates/admin/integration-tab-intro.php';
		if ( is_file( $this->intro_template ) ) {
			require $this->intro_template;
		}
	}
}
