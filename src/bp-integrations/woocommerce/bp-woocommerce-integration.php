<?php
/**
 * BuddyBoss WooCommerce Integration Class.
 *
 * @package BuddyBoss\WooCommerce
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Setup the bp woocommerce class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Woocommerce_Integration extends BP_Integration {

	public function __construct() {

		// Calling parent. Locate BP_Integration->start()
		$this->start(
			'woocommerce', // Internal identifier of integration.
			__('WooCommerce', 'buddyboss'), // Internal integration name.
			'woocommerce', //Path for includes.
			[
				'required_plugin' => 'woocommerce/woocommerce.php', //Params
			]
		);
	}

	/**
	 * WooCommerce Integration Tab
	 * @return {HTML} - renders html in bp-admin-woocommerce-tab.php
	 */
	public function setup_admin_integartion_tab() {
		require_once trailingslashit($this->path) . 'bp-admin-woocommerce-tab.php';

		new BP_Woocommerce_Admin_Integration_Tab(
			"bp-{$this->id}",
			$this->name,
			[
				'root_path' => $this->path,
				'root_url' => $this->url,
				'required_plugin' => $this->required_plugin,
			]
		);
	}

	/**
	 * WooCommerece includes additional files such as any library or functions or dependencies
	 * @return {file(s)} - execute php from included files
	 */
	public function includes($includes = array()) {
		// Calling Parent
		parent::includes([]);

	}

}
