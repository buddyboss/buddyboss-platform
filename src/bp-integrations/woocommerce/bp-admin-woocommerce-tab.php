<?php
/**
 * WooCommerce integration admin tab
 * 
 * @package BuddyBoss\WooCommerce
 * @since BuddyBoss 1.0.0
 */ 

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup WooCommerce integration admin tab class.
 * 
 * @since BuddyBoss 1.0.0
 */
class BP_Woocommerce_Admin_Integration_Tab extends BP_Admin_Integration_tab {

	public function initialize() {
		$this->tab_order             = 15;
		$this->intro_template        = $this->root_path . '/templates/admin/integration-tab-intro.php';
	}

	public function settings_save() {
		// var_dump( $_POST );
	}

	public function register_fields() {
		$this->add_section(
			'woocommerce-section',
			__( 'Section Heading', 'buddyboss' )
		);
	}
}
