<?php
/**
 * Add admin Credit settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main credits class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Admin_Setting_Credit extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Credits', 'buddyboss' );
		$this->tab_name  = 'bp-credit';
		$this->tab_order = 100;
	}

	public function is_tab_visible() {
		return true;
	}

	public function form_html() {
		require_once trailingslashit( buddypress()->plugin_dir . 'bp-core/admin/templates' ) . '/credit-screen.php';
	}
}

return new BP_Admin_Setting_Credit();
