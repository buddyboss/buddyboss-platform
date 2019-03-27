<?php
namespace BuddyBoss\Memberships\Classes;

use BuddyBoss\Memberships\Classes\BpMemberships;
use BuddyBoss\Memberships\Classes\BpmsView;

class MpHelper {

	private static $instance;

	public function __construct() {
		// @See getInstance();
	}

	/**
	 * Will return instance of MpHelper(Memberpress Helper) class
	 * @return object - Singleton
	 */
	public static function getInstance() {
		if (BPMS_DEBUG) {
			error_log("MpHelper::getInstance()");
		}

		if (!isset(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Injecting memberpess tabs lists on MemberPress membership edit screen
	 * @return {void}
	 */
	public static function mpTab() {

		if (BPMS_DEBUG) {
			error_log("MpHelper::mpTab()");
		}

		$lmsCourseSlugs = BpMemberships::getLmsCourseSlugs(LD_COURSE_SLUG);
		// NOTE : LMS Type(s), Eg (Slugs): Learndash
		foreach ($lmsCourseSlugs as $key => $lmsCourseSlug) {
			if ($lmsCourseSlug == LD_COURSE_SLUG) {
				BpmsView::render('memberpress/tab', get_defined_vars());
			} else {
				// NOTE : Implementation for another LMS when required
			}
		}
	}

	/**
	 * Output tab content for LearnDash tab on MemberPress membership edit screen
	 *
	 * @param  {array}  $product MemberPress product information
	 * @return  {void}
	 */
	public static function mpTabContent($product) {
		if (BPMS_DEBUG) {
			error_log("MpHelper::mpTabContent()");
		}

		$lmsCourseSlugs = BpMemberships::getLmsCourseSlugs(LD_COURSE_SLUG);
		$membershipProductType = MP_PRODUCT_SLUG;
		$themeName = wp_get_theme();
		// NOTE : LMS Type(s), Eg (Slugs): Learndash
		foreach ($lmsCourseSlugs as $lmsCourseSlug) {
			if ($lmsCourseSlug == LD_COURSE_SLUG) {

				// NOTE : Implementation for Learndash LMS
				$allCourses = BpMemberships::getLearndashCourses();
				$isEnabled = get_post_meta($product->rec->ID, "_bpms-$lmsCourseSlug-$membershipProductType-is_enabled", true);
				$courseAccessMethod = get_post_meta($product->rec->ID, "_bpms-$lmsCourseSlug-$membershipProductType-course_access_method", true);
				$coursesEnrolled = unserialize(get_post_meta($product->rec->ID, "_bpms-$lmsCourseSlug-$membershipProductType-courses_attached", true));
				$allowFromPricebox = get_post_meta($product->rec->ID, "_bpms-$lmsCourseSlug-$membershipProductType-allow_from_pricebox", true);
				$buttonText = get_post_meta($product->rec->ID, "_bpms-$lmsCourseSlug-$membershipProductType-purchase_button_text", true);
				$buttonOrder = get_post_meta($product->rec->ID, "_bpms-$lmsCourseSlug-$membershipProductType-purchase_button_order", true);
				$pId = $product->rec->ID; //Required for ajax-call
				$accessMethods = BpMemberships::getCourseOptions();
				$groups = learndash_get_groups();
				BpmsView::render('memberpress/tab-content', get_defined_vars());

			} else {
				// NOTE : Implementation for another LMS when required
			}

		}
	}

	/**
	 * @param  {object}  $transaction MemberPress transaction (Updated) information
	 * @return  {void}
	 * @see Possible status: 'pending','active','confirmed','suspended','cancelled' AND  'pending','complete','failed','refunded'
	 */
	public static function mpTransactionUpdated($meprObj) {
		if (BPMS_DEBUG) {
			error_log("MpHelper::mpTransactionUpdated() injected, transaction is updated at this point");
			// error_log(print_r($meprObj, true));
		}

		$isRecurring = self::mpIsRecurring($meprObj);
		$grantStatus = self::mpIdentifyGrantStatus($meprObj);
		$revokeStatus = self::mpIdentifyRevokeStatus($meprObj);

		$status = $meprObj->status;
		if (BPMS_DEBUG) {
			error_log("MpHelper->mpTransactionUpdated, Status : $status, isRecurring : $isRecurring");
		}

		error_log("MpHelper->mpTransactionUpdated, Status : $status, isRecurring : $isRecurring");

		if (in_array($status, $revokeStatus)) {
			// revoke access
			BpMemberships::bpmsUpdateMembershipAccess($meprObj, MP_PRODUCT_SLUG, false);
		} else if (in_array($status, $grantStatus)) {
			// grant access
			BpMemberships::bpmsUpdateMembershipAccess($meprObj, MP_PRODUCT_SLUG, true);
		}

	}

	/**
	 * @param  {object}  $meprObj MemberPress Transaction(Including Subscription) Object
	 * @return  {array} Grant status based on transaction type(Eg : Recurring or Non-Recurring)
	 * @see Possible status: 'pending','active','confirmed','suspended','cancelled' AND  'pending','complete','failed','refunded'
	 */
	public static function mpIdentifyGrantStatus($meprObj) {
		$isRecurring = self::mpIsRecurring($meprObj);
		// NOTE : Configs for NON-RECURRING Transaction
		$grantStatus = array('complete');

		if ($isRecurring) {
			// NOTE : Configs for RECURRING Transaction
			$grantStatus = array('active', 'confirmed');
		}

		return $grantStatus;

	}

	/**
	 * @param  {object}  $meprObj MemberPress Transaction(Including Subscription) Object
	 * @return  {array} Revoke status based on transaction type(Eg : Recurring or Non-Recurring)
	 * @see Possible status: 'pending','active','confirmed','suspended','cancelled' AND  'pending','complete','failed','refunded'
	 */
	public static function mpIdentifyRevokeStatus($meprObj) {
		$isRecurring = self::mpIsRecurring($meprObj);
		// NOTE : Configs for NON-RECURRING Transaction
		$revokeStatus = array('failed', 'refunded');

		if ($isRecurring) {
			// NOTE : Configs for RECURRING Transaction
			$revokeStatus = array('pending', 'suspended', 'cancelled');
		}

		return $revokeStatus;

	}

	/**
	 * @param  {object}  $meprObj MemberPress Transaction(Including Subscription) Object
	 * @return  {boolean} Whether the Transaction is recurring(including old or new)
	 */
	public static function mpIsRecurring($meprObj) {
		$isRecurring = false;
		// Case-1) New but Recurring transaction
		if (isset($meprObj->subscription_id) && $meprObj->subscription_id != 0) {
			$isRecurring = true;
		}

		// Case-2) Existing but Recurring transaction
		if (isset($meprObj->subscr_id)) {
			$isRecurring = true;
		}

		return $isRecurring;

	}

	/**
	 * @param  {object}  $meprObj MemberPress Transaction(Including Subscription) Object
	 * @return  {boolean} Whether the Transaction is existing or new
	 */
	public static function mpIsExisting($meprObj) {
		$isExisting = false;

		if (isset($meprObj->subscr_id)) {
			$isExisting = true;
		}

		return $isExisting;

	}

	/**
	 * @param  {string}  $oldStatus - Status such as 'created', 'paused', 'resumed', 'stopped', 'upgraded', 'downgraded', 'expired'
	 * @param  {string}  $newStatus - Status such as 'created', 'paused', 'resumed', 'stopped', 'upgraded', 'downgraded', 'expired'
	 * @param  {object}  $meprObj MemberPress Subscription information
	 * @return  {void}
	 * @see Possible status :  'pending','active','confirmed','cancelled' AND  'complete','suspended','failed','refunded'
	 */
	public static function mpSubscriptionTransitionStatus($oldStatus, $newStatus, $meprObj) {
		if (BPMS_DEBUG) {
			error_log("MpHelper::mpSubscriptionTransitionStatus() injected, old status : $oldStatus, new status : $newStatus");
			// error_log(print_r($meprObj, true));
		}

		$isRecurring = self::mpIsRecurring($meprObj);
		$grantStatus = self::mpIdentifyGrantStatus($meprObj);
		$revokeStatus = self::mpIdentifyRevokeStatus($meprObj);

		$status = $meprObj->status;

		if (BPMS_DEBUG) {
			error_log("MpHelper->mpSubscriptionTransitionStatus, Status : $status, isRecurring : $isRecurring");
		}

		if (in_array($status, $revokeStatus)) {
			// revoke access
			BpMemberships::bpmsUpdateMembershipAccess($meprObj, MP_PRODUCT_SLUG, false);
		} else if (in_array($status, $grantStatus)) {
			// grant access
			BpMemberships::bpmsUpdateMembershipAccess($meprObj, MP_PRODUCT_SLUG, true);
		}

	}

	/**
	 * @param {object}  $transaction MemberPress Transaction information
	 * @return {void}
	 */
	public static function mpSignUp($meprObj) {
		if (BPMS_DEBUG) {
			error_log("MpHelper::mpSignup() injected");
		}

		self::mpTransactionUpdated($meprObj);
	}

	/**
	 * Save LearnDash meta for MemberPress membership post object
	 * @param {array}  $product MemberPress product information
	 * @return {void}
	 */
	public static function mpSaveProduct($product) {
		if (BPMS_DEBUG) {
			error_log("MpHelper::mpSaveProduct()");
			// error_log(print_r($product, true));
		}
		$lmsCourseSlugs = BpMemberships::getLmsCourseSlugs(LD_COURSE_SLUG);
		$membershipProductType = MP_PRODUCT_SLUG;
		// NOTE : LMS Type(s), Eg (Slugs): Learndash
		foreach ($lmsCourseSlugs as $lmsCourseSlug) {
			if ($lmsCourseSlug == LD_COURSE_SLUG) {
				// NOTE : Implementation for Learndash LMS
				$isEnabled = $_REQUEST["bpms-$lmsCourseSlug-$membershipProductType-is_enabled"];
				update_post_meta($product->rec->ID, "_bpms-$lmsCourseSlug-$membershipProductType-is_enabled", $isEnabled);

				if ($isEnabled) {

					$courseAccessMethod = $_REQUEST["bpms-$lmsCourseSlug-$membershipProductType-course_access_method"];
					update_post_meta($product->rec->ID, "_bpms-$lmsCourseSlug-$membershipProductType-course_access_method", $courseAccessMethod);

					if (BPMS_DEBUG) {
						error_log("Course Access Method selected :$courseAccessMethod");
					}

					if ($courseAccessMethod == 'SINGLE_COURSES') {

						$newCourses = array_filter($_REQUEST["bpms-$lmsCourseSlug-$membershipProductType-courses_attached"]);

						update_post_meta($product->rec->ID, "_bpms-$lmsCourseSlug-$membershipProductType-courses_attached", serialize(array_values($newCourses)));
					} else if ($courseAccessMethod == 'ALL_COURSES') {

						// NOTE : Array format is consistent with GUI
						$allClosedCourses = BpMemberships::getLearndashAllCourses();
					} else if ($courseAccessMethod == 'LD_GROUPS') {

						$newGroups = array_filter($_REQUEST["bpms-$lmsCourseSlug-$membershipProductType-groups_attached"]);
						update_post_meta($product->rec->ID, "_bpms-$lmsCourseSlug-$membershipProductType-groups_attached", serialize(array_values($newGroups)));
					}

					// Update Allow From PriceBox
					$allowFromPricebox = $_REQUEST["bpms-$lmsCourseSlug-$membershipProductType-allow_from_pricebox"];
					update_post_meta($product->rec->ID, "_bpms-$lmsCourseSlug-$membershipProductType-allow_from_pricebox", $allowFromPricebox);

					if ($allowFromPricebox) {
						$buttonText = $_REQUEST["bpms-$lmsCourseSlug-$membershipProductType-purchase_button_text"];
						$buttonOrder = $_REQUEST["bpms-$lmsCourseSlug-$membershipProductType-purchase_button_order"];

						update_post_meta($product->rec->ID, "_bpms-$lmsCourseSlug-$membershipProductType-purchase_button_text", $buttonText);
						update_post_meta($product->rec->ID, "_bpms-$lmsCourseSlug-$membershipProductType-purchase_button_order", $buttonOrder);
					}

				}

			} else {
				// NOTE : Implementation for another LMS when required
			}
		}

	}

}
