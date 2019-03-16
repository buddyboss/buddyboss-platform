<?php
/**
 * BuddyBoss MemberPress Integration Class.
 *
 * @package BuddyBoss\MemberPress
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Setup the bp MemberPress class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Memberpress_Integration extends BP_Integration {

	public function __construct() {

		// Calling parent. Locate BP_Integration->start()
		$this->start(
			'memberpress', // Internal identifier of integration.
			__('MemberPress', 'buddyboss'), // Internal integration name.
			'memberpress', //Path for includes.
			[
				'required_plugin' => 'memberpress/memberpress.php', //Params
			]
		);
	}

	/**
	 * Memberpress Integration Tab
	 * @return {HTML} - renders html in bp-admin-memberpress-tab.php
	 */
	public function setup_admin_integartion_tab() {
		require_once trailingslashit($this->path) . 'bp-admin-memberpress-tab.php';

		new BP_Memberpress_Admin_Integration_Tab(
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
	 * Memberpress includes additional files such as any library or functions or dependencies
	 * @return {file(s)} - execute php from included files
	 */
	public function includes($includes = array()) {
		// Calling Parent
		parent::includes([
			'../../../vendor/autoload.php',
		]);

		$mpHelper = new BuddyBoss\Integrations\MemberPress\Helpers\MpHelper();
		$this->mpHooks($mpHelper);
	}

	/**
	 * Memberpress Hooks
	 * @return void
	 */
	public function mpHooks($classObj) {

		add_action('mepr-product-options-tabs', array($classObj, 'mpLearndashTab'));
		add_action('mepr-product-options-pages', array($classObj, 'mpLearndashTabContent'));
		add_action('mepr-membership-save-meta', array($classObj, 'mpSaveProduct'));

		// Signup type can be 'free', 'non-recurring' or 'recurring'
		// add_action('mepr-non-recurring-signup', array($classObj, 'mpSignUp'));
		// add_action('mepr-free-signup', array($classObj, 'mpSignUp'));
		// add_action('mepr-recurring-signup', array($classObj, 'mpSignUp'));
		// add_action('mepr-signup', array($classObj, 'mpSignUp'));
		// Transaction Related
		// add_action('mepr-txn-status-complete', array($classObj, 'mpTransactionUpdated'));
		// add_action('mepr-txn-status-pending', array($classObj, 'mpTransactionUpdated'));
		// add_action('mepr-txn-status-failed', array($classObj, 'mpTransactionUpdated'));
		// add_action('mepr-txn-status-refunded', array($classObj, 'mpTransactionUpdated'));
		// add_action('mepr-txn-status-confirmed', array($classObj, 'mpTransactionUpdated'));
		// add_action('mepr-transaction-expired', array($classObj, 'mpTransactionUpdated'));

		// Subscription Related
		// add_action(array('mepr_subscription_stored', 'mepr_subscription_saved'), array($classObj, 'mpSubscriptionUpdated'));
		// add_action('mepr_subscription_saved', array($classObj, 'mpSubscriptionUpdated'));
		// add_action('mepr_subscription_transition_status', array($classObj, 'mpSubscriptionTransitionStatus'));
		// add_action('mepr_subscription_status_created', array($classObj, 'mpSubscriptionTransitionStatus'));
		// add_action('mepr_subscription_status_paused', array($classObj, 'mpSubscriptionTransitionStatus'));
		// add_action('mepr_subscription_status_resumed', array($classObj, 'mpSubscriptionTransitionStatus'));
		// add_action('mepr_subscription_status_stopped', array($classObj, 'mpSubscriptionTransitionStatus'));
		// add_action('mepr_subscription_status_upgraded', array($classObj, 'mpSubscriptionTransitionStatus'));
		// add_action('mepr_subscription_status_downgraded', array($classObj, 'mpSubscriptionTransitionStatus'));
		// add_action('mepr_subscription_status_expired', array($classObj, 'mpSubscriptionTransitionStatus'));

	}

}
