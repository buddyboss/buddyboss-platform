<?php
/**
 * MemberPress integration admin tab
 * 
 * @package BuddyBoss\MemberPress
 * @since BuddyBoss 1.0.0
 */ 

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup memberpress integration admin tab class.
 * 
 * @since BuddyBoss 1.0.0
 */
class BP_Memberpress_Admin_Integration_Tab extends BP_Admin_Integration_tab {

	public function initialize() {
		$this->tab_order             = 20;
		$this->intro_template        = $this->root_path . '/templates/admin/integration-tab-intro.php';
	}

	public function settings_save() {
		// var_dump( $_POST );
	}

	public function register_fields() {
		$this->add_section(
			'memberpress-section',
			__( 'Section Heading', 'buddyboss' )
		);
	}
}
