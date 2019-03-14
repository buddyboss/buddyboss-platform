<?php
/**
 * BuddyBoss WooCommerce Integration Class.
 *
 * @package BuddyBoss\WooCommerce
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp woocommerce class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Woocommerce_Integration extends BP_Integration {

	public function __construct() {
		$this->start(
			'woocommerce',
			__( 'WooCommerce', 'buddyboss' ),
			'woocommerce',
			[
				'required_plugin' => ' '
			]
		);
	}

	public function setup_admin_integartion_tab() {
		require_once trailingslashit( $this->path ) . 'bp-admin-woocommerce-tab.php';

		new BP_Woocommerce_Admin_Integration_Tab(
			"bp-{$this->id}",
			$this->name,
			[
				'root_path' => $this->path,
				'root_url'  => $this->url,
				'required_plugin' => $this->required_plugin,
			]
		);
	}

	public function includes( $includes = array() ) {
		parent::includes([
			'functions',
			'core/Core.php',
		]);
	}
}
