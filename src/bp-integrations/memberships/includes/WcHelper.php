<?php
namespace BuddyBoss\Memberships\Classes;

use BuddyBoss\Memberships\Classes\BbmsView;
use BuddyBoss\Memberships\Classes\BpMemberships;

class WcHelper {
	private static $instance;

	public function __construct() {
		// @See getInstance();
	}

	/**
	 * Will return instance of WcHelper(Woocommerce Helper) class
	 * @return object - Singleton
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Output tab for LearnDash on WooCommerce product edit screen
	 *
	 * @param  {array}  $tabs WooCommerce product tabs
	 * @return  {array}  $tabs Updated WooCommerce product tabs
	 */
	public static function wcLearndashTab($tabs = array()) {

		$tabs['learndash'] = array(
			'label' => __('Learndash', 'woocommerce'),
			'target' => 'learndash_product_data',
			'class' => array(),
			'priority' => 100,
		);

		return $tabs;
	}

	/**
	 * Output tab content for LearnDash on WooCommerce product edit screen
	 * @return  {HTML} - Renders tab content
	 */
	public static function wcLearndashTabContent() {

		global $post;

		// $lmsType = BpMemberships::getLmsTypesSelected(LD_POST_TYPE);
		$lmsType = LD_POST_TYPE;
		$membershipType = WC_POST_TYPE;

		$allCourses = BpMemberships::getLearndashCourses();
		$isEnabled = get_post_meta($post->ID, "_bbms-$lmsType-$membershipType-is_enabled", true);
		$courseAccessMethod = get_post_meta($post->ID, "_bbms-$lmsType-$membershipType-course_access_method", true);
		$coursesEnrolled = unserialize(get_post_meta($post->ID, "_bbms-$lmsType-$membershipType-courses_enrolled", true));
		$themeName = wp_get_theme();
		$allowFromPricebox = get_post_meta($post->ID, "_bbms-$lmsType-$membershipType-allow_from_pricebox", true);
		$buttonText = get_post_meta($post->ID, "_bbms-$lmsType-$membershipType-purchase_button_text", true);
		$buttonOrder = get_post_meta($post->ID, "_bbms-$lmsType-$membershipType-purchase_button_order", true);
		$pId = $post->ID; //Required for ajax-call
		$accessMethods = BpMemberships::getCourseOptions();
		BbmsView::render('woocommerce/wc-tab-content', get_defined_vars());
	}

	/**
	 * @param  {object}  $wcObj WooCommerce order (Updated) information
	 * @return  {void}
	 */
	public static function wcOrderUpdated($wcObj) {
		if (BBMS_DEBUG) {
			error_log("wcOrderUpdated() injected, order is updated at this point");
			// error_log(print_r($wcObj, true));
		}

		$status = $wcObj->status;
		error_log("Status is : $status");
	}

	/**
	 * @param  {object}  $wcObj WooCommerce subscription (Updated) information
	 * @return  {void}
	 */
	public static function wcSubscriptionUpdated($wcObj) {
		if (BBMS_DEBUG) {
			error_log("wcSubscriptionUpdated() injected, subscription is updated at this point");
			// error_log(print_r($wcObj, true));
			// error_log(print_r($wcObj->id, true));
		}
	}

	/**
	 * Save LearnDash meta for WooCommerce product object
	 * @return {void}
	 */
	public static function wcProductUpdate($productId) {
		if (BBMS_DEBUG) {
			error_log("wcProductUpdate(), $productId");
		}

		$lmsType = BpMemberships::getLmsTypesSelected(LD_POST_TYPE);
		$membershipType = WC_POST_TYPE;

		$isEnabled = $_POST["bbms-$lmsType-$membershipType-is_enabled"];
		update_post_meta($productId, "_bbms-$lmsType-$membershipType-is_enabled", $isEnabled);

		if ($isEnabled) {

			$courseAccessMethod = $_POST["bbms-$lmsType-$membershipType-course_access_method"];
			update_post_meta($productId, "_bbms-$lmsType-$membershipType-course_access_method", $courseAccessMethod);

			if ($courseAccessMethod == 'SINGLE_COURSES') {
				$newCourses = array_filter($_POST["bbms-$lmsType-$membershipType-courses_enrolled"]);
				update_post_meta($productId, "_bbms-$lmsType-$membershipType-courses_enrolled", serialize(array_values($newCourses)));
			} else if ($courseAccessMethod == 'ALL_COURSES') {

				if (BBMS_DEBUG) {
					error_log("ALL_COURSES selected");
				}

				$allClosedCourses = BpMemberships::getLearndashClosedCourses();
			} else if ($courseAccessMethod == 'LD_GROUPS') {
				if (BBMS_DEBUG) {
					error_log("LD_GROUPS selected");
				}

				$newGroups = array_filter($_POST["bbms-$lmsType-$membershipType-groups_attached"]);
				// error_log(print_r($newGroups, true));

				update_post_meta($productId, "_bbms-$lmsType-$membershipType-groups_attached", serialize(array_values($newGroups)));

			}
			// Update Allow From PriceBox
			$allowFromPricebox = $_POST["bbms-$lmsType-$membershipType-allow_from_pricebox"];
			update_post_meta($productId, "_bbms-$lmsType-$membershipType-allow_from_pricebox", $allowFromPricebox);

			if ($allowFromPricebox) {
				$buttonText = $_POST["bbms-$lmsType-$membershipType-purchase_button_text"];
				$buttonOrder = $_POST["bbms-$lmsType-$membershipType-purchase_button_order"];

				update_post_meta($productId, "_bbms-$lmsType-$membershipType-purchase_button_text", $buttonText);
				update_post_meta($productId, "_bbms-$lmsType-$membershipType-purchase_button_order", $buttonOrder);

			}

		}
	}

}
