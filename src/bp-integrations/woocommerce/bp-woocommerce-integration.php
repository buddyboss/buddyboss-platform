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

		// $wcHelper = new BuddyBoss\Integrations\Woocommerce\Helpers\WcHelper();
		// $this->wcHooks($wcHelper);

	}

	// /**
	//  * WooCommerce Hooks
	//  * @return void
	//  */
	// public function wcHooks($classObj) {
	// 	add_filter('woocommerce_product_data_tabs', array($classObj, 'wcLearndashTab'));
	// 	add_action('woocommerce_product_data_panels', array($classObj, 'wcLearndashTabContent'));
	// 	// On Save/Update
	// 	add_action('save_post_product', array($classObj, 'wcProductUpdate'));

	// 	// Transaction Related
	// 	// add_action('woocommerce_order_details_after_order_table', array($classObj, 'wcOrderUpdated'));
	// 	// add_action('woocommerce_payment_complete', array($classObj, 'wcOrderUpdated'));
	// 	// add_action('woocommerce_new_order', array($classObj, 'wcOrderUpdated'));
	// 	// add_action('woocommerce_new_product', array($classObj, 'wcVerify'));
	// 	// add_action('woocommerce_new_product', array($classObj, 'wcVerify'));

	// 	// Order hooks for WC

	// 	add_action('woocommerce_order_status_pending_to_processing', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_status_pending_to_completed', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_status_processing_to_cancelled', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_status_pending_to_failed', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_status_pending_to_on-hold', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_status_failed_to_processing', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_status_failed_to_completed', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_status_failed_to_on-hold', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_status_cancelled_to_processing', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_status_cancelled_to_completed', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_status_cancelled_to_on-hold', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_status_on-hold_to_processing', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_status_on-hold_to_cancelled', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_status_on-hold_to_failed', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_status_completed', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_fully_refunded', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_partially_refunded', array($classObj, 'wcOrderUpdated'), 10, 1);

	// 	add_action('woocommerce_order_status_pending', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_status_completed', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_status_processing', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_status_on-hold', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	add_action('woocommerce_order_status_completed', array($classObj, 'wcOrderUpdated'), 10, 1);

	// 	// add_action('woocommerce_payment_complete', array($classObj, 'wcOrderUpdated'), 10, 1);
	// 	// add_action('woocommerce_order_status_refunded', array($classObj, 'wcOrderUpdated'), 10, 1);

	// 	// Subscription hooks for WC
	// 	add_action('woocommerce_subscription_status_cancelled', array($classObj, 'wcSubscriptionUpdated'), 10, 1);
	// 	add_action('woocommerce_subscription_status_on-hold', array($classObj, 'wcSubscriptionUpdated'), 10, 1);
	// 	add_action('woocommerce_subscription_status_expired', array($classObj, 'wcSubscriptionUpdated'), 10, 1);
	// 	add_action('woocommerce_subscription_status_active', array($classObj, 'wcSubscriptionUpdated'), 10, 1);
	// 	add_action('woocommerce_subscription_renewal_payment_complete', array($classObj, 'wcSubscriptionUpdated'), 10, 1);

	// 	// Other hooks for WC subscription

	// 	// // Force user to log in or create account if there is LD course in WC cart
	// 	// add_action( 'woocommerce_checkout_init', array( __CLASS__, 'force_login' ), 10, 1 );

	// 	// // Auto complete course transaction
	// 	// add_action( 'woocommerce_thankyou', array( __CLASS__, 'auto_complete_transaction' ) );

	// }

}
