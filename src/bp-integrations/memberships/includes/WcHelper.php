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
		$lmsCourseSlugs = BpMemberships::getLmsCourseSlugs(LD_COURSE_SLUG);
		// NOTE : LMS Type(s), Eg (Slugs): Learndash
		foreach ($lmsCourseSlugs as $key => $lmsCourseSlug) {
			if ($lmsCourseSlug == LD_COURSE_SLUG) {

				$tabs[$lmsCourseSlug] = array(
					'label' => __('LearnDash', 'buddyboss'),
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

		$lmsCourseSlugs = BpMemberships::getLmsCourseSlugs(LD_COURSE_SLUG);
		$membershipProductType = WC_PRODUCT_SLUG;
		// NOTE : LMS Type(s), Eg (Slugs): Learndash

		foreach ($lmsCourseSlugs as $key => $lmsCourseSlug) {
			if ($lmsCourseSlug == LD_COURSE_SLUG) {
				// NOTE : Implementation for Learndash LMS

				$allCourses = BpMemberships::getLearndashCourses();
				$isEnabled = get_post_meta($post->ID, "_bpms-$lmsCourseSlug-$membershipProductType-is_enabled", true);
				$courseAccessMethod = get_post_meta($post->ID, "_bpms-$lmsCourseSlug-$membershipProductType-course_access_method", true);
				$coursesEnrolled = unserialize(get_post_meta($post->ID, "_bpms-$lmsCourseSlug-$membershipProductType-courses_attached", true));
				$themeName = wp_get_theme();
				$allowFromPricebox = get_post_meta($post->ID, "_bpms-$lmsCourseSlug-$membershipProductType-allow_from_pricebox", true);
				$buttonText = get_post_meta($post->ID, "_bpms-$lmsCourseSlug-$membershipProductType-purchase_button_text", true);
				$buttonOrder = get_post_meta($post->ID, "_bpms-$lmsCourseSlug-$membershipProductType-purchase_button_order", true);
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
			error_log(print_r($wcObj, true));
		}
		$isRecurring = self::wcIsRecurring($wcObj);
		$grantStatus = self::wcIdentifyGrantStatus($wcObj);
		$revokeStatus = self::wcIdentifyRevokeStatus($wcObj);

		$status = $wcObj->status;
		if (BPMS_DEBUG) {
			error_log("WcHelper->wcOrderUpdated, Status : $status, isRecurring : $isRecurring");
		}

		error_log("WcHelper->wcOrderUpdated, Status : $status, isRecurring : $isRecurring");

		if (in_array($status, $revokeStatus)) {
			// revoke access
			BpMemberships::bpmsUpdateMembershipAccess($wcObj, WC_PRODUCT_SLUG, false);
		} else if (in_array($status, $grantStatus)) {
			// grant access
			BpMemberships::bpmsUpdateMembershipAccess($wcObj, WC_PRODUCT_SLUG, true);
		}
	}

	/**
	 * @param  {object}  $wcObj WooCommerce subscription (Updated) information
	 * @return  {void}
	 */
	public static function wcSubscriptionUpdated($wcObj) {
		if (BPMS_DEBUG) {
			error_log("wcSubscriptionUpdated() injected, subscription is updated at this point");
			// error_log(print_r($wcObj->id, true));
		}

		$isRecurring = self::wcIsRecurring($wcObj);
		$grantStatus = self::wcIdentifyGrantStatus($wcObj);
		$revokeStatus = self::wcIdentifyRevokeStatus($wcObj);

		$status = $wcObj->status;
		if (BPMS_DEBUG) {
			error_log("WcHelper->wcSubscriptionUpdated, Status : $status, isRecurring : $isRecurring");
		}

		if (in_array($status, $revokeStatus)) {
			// revoke access
			BpMemberships::bpmsUpdateMembershipAccess($wcObj, WC_PRODUCT_SLUG, false);
		} else if (in_array($status, $grantStatus)) {
			// grant access
			BpMemberships::bpmsUpdateMembershipAccess($wcObj, WC_PRODUCT_SLUG, true);
		}
	}

	/**
	 * @param  {object}  $wcObj WooCommerce Order (Recurring | Non-Recurring) Object
	 * @return  {boolean} Whether the order is recurring(including old or new)
	 * @todo  Find differentiator between Recurring | Non-Recurring
	 */
	public static function wcIsRecurring($wcObj) {

		$isRecurring = false;
		if (isset($wcObj->order_type)) {
			$isRecurring = $wcObj->order_type == 'shop_subscription' ? true : false;
		}

		return $isRecurring;

	}

	/**
	 * @param  {object}  $wcObj WooCommerce Order(Recurring | Non-Recurring) Object
	 * @return  {array} Grant status based on order type (Eg : Recurring or Non-Recurring)
	 * @see Possible status: 'pending','active','on-hold','expired','failed' AND  'pending','completed','processing','on-hold','cancelled','refunded','failed'
	 */
	public static function wcIdentifyGrantStatus($wcObj) {
		$isRecurring = self::wcIsRecurring($wcObj);
		// NOTE : Configs for NON-RECURRING Transaction
		$grantStatus = array('completed');

		if ($isRecurring) {
			// NOTE : Configs for RECURRING Transaction
			$grantStatus = array('active');
		}

		return $grantStatus;

	}

	/**
	 * @param  {object}  $wcObj WooCommerce Order(Recurring | Non-Recurring) Object
	 * @return  {array} Grant status based on order type (Eg : Recurring or Non-Recurring)
	 * @see Possible status: 'pending','active','on-hold','expired','failed' AND  'pending','completed','processing','on-hold','cancelled','refunded','failed'
	 */
	public static function wcIdentifyRevokeStatus($wcObj) {
		$isRecurring = self::wcIsRecurring($wcObj);
		// NOTE : Configs for NON-RECURRING Transaction
		$revokeStatus = array('pending', 'processing', 'on-hold', 'cancelled', 'refunded', 'failed');

		if ($isRecurring) {
			// NOTE : Configs for RECURRING Transaction
			$revokeStatus = array('pending', 'on-hold', 'expired', 'failed');
		}

		return $revokeStatus;
	}

	/**
	 * Save LearnDash meta for WooCommerce product object
	 * @return {void}
	 */
	public static function wcProductUpdate($productId) {
		if (BPMS_DEBUG) {
			error_log("wcProductUpdate(), $productId");
		}

		$lmsCourseSlugs = BpMemberships::getLmsCourseSlugs(LD_COURSE_SLUG);
		$membershipProductType = WC_PRODUCT_SLUG;
		// NOTE : LMS Type(s), Eg (Slugs): Learndash

		foreach ($lmsCourseSlugs as $lmsCourseSlug) {
			if ($lmsCourseSlug == LD_COURSE_SLUG) {
				// NOTE : Implementation for Learndash LMS

				$isEnabled = $_REQUEST["bpms-$lmsCourseSlug-$membershipProductType-is_enabled"];
				update_post_meta($productId, "_bpms-$lmsCourseSlug-$membershipProductType-is_enabled", $isEnabled);

				if ($isEnabled) {

					$courseAccessMethod = $_REQUEST["bpms-$lmsCourseSlug-$membershipProductType-course_access_method"];
					update_post_meta($productId, "_bpms-$lmsCourseSlug-$membershipProductType-course_access_method", $courseAccessMethod);

					if (BPMS_DEBUG) {
						error_log("Course Access Method selected :$courseAccessMethod");
					}

					if ($courseAccessMethod == 'SINGLE_COURSES') {
						$newCourses = array_filter($_REQUEST["bpms-$lmsCourseSlug-$membershipProductType-courses_attached"]);
						update_post_meta($productId, "_bpms-$lmsCourseSlug-$membershipProductType-courses_attached", serialize(array_values($newCourses)));
					} else if ($courseAccessMethod == 'ALL_COURSES') {

						$allClosedCourses = BpMemberships::getLearndashAllCourses();
					} else if ($courseAccessMethod == 'LD_GROUPS') {

						$newGroups = array_filter($_REQUEST["bpms-$lmsCourseSlug-$membershipProductType-groups_attached"]);
						// error_log(print_r($newGroups, true));

						update_post_meta($productId, "_bpms-$lmsCourseSlug-$membershipProductType-groups_attached", serialize(array_values($newGroups)));

					}
					// Update Allow From PriceBox
					$allowFromPricebox = $_REQUEST["bpms-$lmsCourseSlug-$membershipProductType-allow_from_pricebox"];
					update_post_meta($productId, "_bpms-$lmsCourseSlug-$membershipProductType-allow_from_pricebox", $allowFromPricebox);

					if ($allowFromPricebox) {
						$buttonText = $_REQUEST["bpms-$lmsCourseSlug-$membershipProductType-purchase_button_text"];
						$buttonOrder = $_REQUEST["bpms-$lmsCourseSlug-$membershipProductType-purchase_button_order"];

						update_post_meta($productId, "_bpms-$lmsCourseSlug-$membershipProductType-purchase_button_text", $buttonText);
						update_post_meta($productId, "_bpms-$lmsCourseSlug-$membershipProductType-purchase_button_order", $buttonOrder);
					}
				}

			} else {
				// NOTE : Implementation for another LMS when required
			}
		}
	}
}
