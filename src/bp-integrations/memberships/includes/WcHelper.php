<?php
namespace BuddyBoss\Memberships\Classes;

use BuddyBoss\Memberships\Classes\BpMemberships;
use BuddyBoss\Memberships\Classes\BpmsView;

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
	public static function wcTab($tabs = array()) {
		if (BPMS_DEBUG) {
			error_log("WcHelper::wcTab");
		}
		$lmsTypes = BpMemberships::getLmsTypesSelected(LD_POST_TYPE);
		// NOTE : LMS Type(s), Eg (Slugs): Learndash
		foreach ($lmsTypes as $key => $lmsType) {
			if ($lmsType == LD_POST_TYPE) {

				$tabs[$lmsType] = array(
					'label' => __('Learndash', 'woocommerce'),
					'target' => 'learndash_product_data',
					'class' => array(),
					'priority' => 100,
				);
			} else {
				// NOTE : Implementation for another LMS when required
			}
		}

		return $tabs;
	}

	/**
	 * Output tab content for LearnDash on WooCommerce product edit screen
	 * @return  {HTML} - Renders tab content
	 */
	public static function wcTabContent() {

		if (BPMS_DEBUG) {
			error_log("wcTabContent()");
		}
		global $post;

		$lmsTypes = BpMemberships::getLmsTypesSelected(LD_POST_TYPE);
		$membershipType = WC_POST_TYPE;
		// NOTE : LMS Type(s), Eg (Slugs): Learndash

		foreach ($lmsTypes as $key => $lmsType) {
			if ($lmsType == LD_POST_TYPE) {
				// NOTE : Implementation for Learndash LMS

				$allCourses = BpMemberships::getLearndashCourses();
				$isEnabled = get_post_meta($post->ID, "_bpms-$lmsType-$membershipType-is_enabled", true);
				$courseAccessMethod = get_post_meta($post->ID, "_bpms-$lmsType-$membershipType-course_access_method", true);
				$coursesEnrolled = unserialize(get_post_meta($post->ID, "_bpms-$lmsType-$membershipType-courses_enrolled", true));
				$themeName = wp_get_theme();
				$allowFromPricebox = get_post_meta($post->ID, "_bpms-$lmsType-$membershipType-allow_from_pricebox", true);
				$buttonText = get_post_meta($post->ID, "_bpms-$lmsType-$membershipType-purchase_button_text", true);
				$buttonOrder = get_post_meta($post->ID, "_bpms-$lmsType-$membershipType-purchase_button_order", true);
				$pId = $post->ID; //Required for ajax-call
				$accessMethods = BpMemberships::getCourseOptions();
				$groups = learndash_get_groups();
				BpmsView::render('woocommerce/tab-content', get_defined_vars());
			} else {
				// NOTE : Implementation for another LMS when required
			}
		}

	}

	/**
	 * @param  {int}  $orderId WooCommerce order Id
	 * @return  {void}
	 */
	public static function wcOrderUpdated($orderId) {
		$wcObj = wc_get_order($orderId);

		if (BPMS_DEBUG) {
			error_log("wcOrderUpdated() injected, order is updated at this point");
			// error_log(print_r($wcObj, true));
		}

		$status = $wcObj->status;
		error_log("Status is : $status");

		$grantValues = array('completed');
		$revokeValues = array('pending', 'processing', 'on-hold', 'cancelled');

		if (in_array($status, $revokeValues)) {
			// revoke access
			BpMemberships::bbmsUpdateMembershipAccess($wcObj, WC_POST_TYPE, false);
		} else if (in_array($status, $grantValues)) {
			// grant access
			BpMemberships::bbmsUpdateMembershipAccess($wcObj, WC_POST_TYPE, true);
		}}

	/**
	 * @param  {object}  $wcObj WooCommerce subscription (Updated) information
	 * @return  {void}
	 */
	public static function wcSubscriptionUpdated($wcObj) {
		if (BPMS_DEBUG) {
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
		if (BPMS_DEBUG) {
			error_log("wcProductUpdate(), $productId");
		}

		$lmsTypes = BpMemberships::getLmsTypesSelected(LD_POST_TYPE);
		$membershipType = WC_POST_TYPE;
		// NOTE : LMS Type(s), Eg (Slugs): Learndash

		foreach ($lmsTypes as $lmsType) {
			if ($lmsType == LD_POST_TYPE) {
				// NOTE : Implementation for Learndash LMS

				$isEnabled = $_REQUEST["bpms-$lmsType-$membershipType-is_enabled"];
				update_post_meta($productId, "_bpms-$lmsType-$membershipType-is_enabled", $isEnabled);

				if ($isEnabled) {

					$courseAccessMethod = $_REQUEST["bpms-$lmsType-$membershipType-course_access_method"];
					update_post_meta($productId, "_bpms-$lmsType-$membershipType-course_access_method", $courseAccessMethod);

					if (BPMS_DEBUG) {
						error_log("Course Access Method selected :$courseAccessMethod");
					}

					if ($courseAccessMethod == 'SINGLE_COURSES') {
						$newCourses = array_filter($_REQUEST["bpms-$lmsType-$membershipType-courses_enrolled"]);
						update_post_meta($productId, "_bpms-$lmsType-$membershipType-courses_enrolled", serialize(array_values($newCourses)));
					} else if ($courseAccessMethod == 'ALL_COURSES') {

						$allClosedCourses = BpMemberships::getLearndashClosedCourses();
					} else if ($courseAccessMethod == 'LD_GROUPS') {

						$newGroups = array_filter($_REQUEST["bpms-$lmsType-$membershipType-groups_attached"]);
						// error_log(print_r($newGroups, true));

						update_post_meta($productId, "_bpms-$lmsType-$membershipType-groups_attached", serialize(array_values($newGroups)));

					}
					// Update Allow From PriceBox
					$allowFromPricebox = $_REQUEST["bpms-$lmsType-$membershipType-allow_from_pricebox"];
					update_post_meta($productId, "_bpms-$lmsType-$membershipType-allow_from_pricebox", $allowFromPricebox);

					if ($allowFromPricebox) {
						$buttonText = $_REQUEST["bpms-$lmsType-$membershipType-purchase_button_text"];
						$buttonOrder = $_REQUEST["bpms-$lmsType-$membershipType-purchase_button_order"];

						update_post_meta($productId, "_bpms-$lmsType-$membershipType-purchase_button_text", $buttonText);
						update_post_meta($productId, "_bpms-$lmsType-$membershipType-purchase_button_order", $buttonOrder);
					}
				}

			} else {
				// NOTE : Implementation for another LMS when required
			}
		}
	}
}
