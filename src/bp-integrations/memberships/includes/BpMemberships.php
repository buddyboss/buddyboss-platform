<?php
namespace BuddyBoss\Memberships\Classes;

// Block direct requests
if (!defined('ABSPATH')) {
	die("Sorry, you can't access this directly - Security established");
}

define('BPMS_DEBUG', false);
define('LD_COURSE_SLUG', 'sfwd-courses');
define('MP_PRODUCT_SLUG', 'memberpressproduct');
define('WC_PRODUCT_SLUG', 'product');
define('APPBOSS_PROD_TYPE', 'app-product');
define('BPMS_URL', BP_PLUGIN_URL . '/src/bp-integrations/memberships');

use BuddyBoss\Memberships\Classes\MpHelper;
use BuddyBoss\Memberships\Classes\WcHelper;

class BpMemberships {

	private static $instance;

	public function __construct() {

		// NOTE : This must happen when LearnDash is loaded, NOT when wp_loaded
		add_action('learndash_init', array($this, 'onLearndashInit'));

		/**
		 * Available as hook, runs after a bpms(BuddyBoss Membership) plugin is loaded.
		 */
		do_action('bpms_loaded');
	}

	/**
	 * Will return only one(and one) instance of class
	 * @return {object} - The one and only true BpMemberships instance.
	 */
	public static function getInstance() {
		if (BPMS_DEBUG) {
			error_log("BpMemberships::getInstance()");
		}
		if (!isset(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Enqueue plugin scripts/styles
	 * @param  {string}      $hook_suffix - Refers to the hook suffix for the admin page
	 * @return {void}
	 */
	public static function addAdminScripts($hook_suffix) {
		if (BPMS_DEBUG) {
			error_log("addAdminScripts($hook_suffix)");
		}

		global $pagenow;

		$membershipProductSlugs = self::getMembershipProductSlugs();
		if (in_array($pagenow, array('post.php', 'post-new.php'))) {
			global $post;
			$postType = $post->post_type;

			if (in_array($postType, $membershipProductSlugs)) {

				// Select2 Js
				wp_enqueue_script('select2-js', BPMS_URL . '/assets/scripts/select2.min.js');

				// Select2 Css
				wp_enqueue_style('select2', BPMS_URL . '/assets/styles/select2.min.css');

				// Localize the script with new data
				$bpmsVars = array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'lms_course_slugs' => self::getLmsCourseSlugs(LD_COURSE_SLUG),
					'membership_product_slug' => $post->post_type,
					'p_id' => $post->ID,
				);

				// Custom
				wp_register_script('bpms-js', BPMS_URL . '/assets/scripts/bpms.js');
				wp_localize_script('bpms-js', 'bpmsVars', $bpmsVars);
				wp_enqueue_script('bpms-js');

				wp_enqueue_style('bpms', BPMS_URL . '/assets/styles/bpms.css');

			}
		}

	}

	/**
	 * Get all LearnDash courses
	 * @return {object} LearnDash courses
	 */
	public static function getLearndashCourses() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;
		$query = "SELECT posts.ID as 'id', CONCAT(posts.post_title, \" (ID=\" , posts.ID, \")\")  as 'text' FROM $wpdb->posts posts WHERE posts.post_type = 'sfwd-courses' AND posts.post_status = 'publish' ORDER BY posts.post_title";

		return $wpdb->get_results($query, OBJECT);
	}

	/**
	 * Get all LearnDash courses
	 * @return {object} LearnDash course
	 */
	public static function getAllLearndashCourses() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;
		$query = "SELECT posts.ID as 'id', posts.post_title as 'text' FROM $wpdb->posts posts WHERE posts.post_type = 'sfwd-courses' AND posts.post_status = 'publish' ORDER BY posts.post_title";

		$results = $wpdb->get_results($query, OBJECT);

		return $results;
	}

	/**
	 * Get all LearnDash coursesIds
	 * @return {object} LearnDash course
	 */
	public static function getAllLearndashCoursesIds() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;
		$query = "SELECT postmeta.post_id as post_id FROM " . $wpdb->postmeta . " as postmeta INNER JOIN " . $wpdb->posts . " as posts ON posts.ID = postmeta.post_id WHERE posts.post_status='publish' AND posts.post_type='sfwd-courses' AND postmeta.meta_key='_sfwd-courses' AND ( postmeta.meta_value REGEXP '\"sfwd-courses_course_price_type\";s:6:\"closed\";' )";
		$courseIds = $wpdb->get_col($query);

		return $courseIds;
	}

	/**
	 * Search LearnDash courses as Ajax
	 * @param  {string}   $_GET['search'] - Term searched from UI
	 * @return {JSON} Searched LearnDash course(s) if found
	 */
	public function searchCourses() {
		if (BPMS_DEBUG) {
			error_log("searchCourses()");
		}
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;

		$search = $_GET['search'];

		$query = "SELECT CONCAT(posts.ID, \":\" , posts.post_title, \"\") as 'id', CONCAT(posts.post_title, \"(ID:\" , posts.ID, \")\")  as 'text' FROM $wpdb->posts posts WHERE posts.post_type = 'sfwd-courses' AND posts.post_status = 'publish' AND posts.post_title LIKE \"%$search%\" OR posts.ID LIKE \"%$search%\" limit 2";

		$courses = $wpdb->get_results($query, OBJECT);
		$data = array();
		$data['results'] = $courses;
		wp_send_json_success($data, JSON_PRETTY_PRINT);
	}

	/**
	 * get All LearnDash courses for Ajax-call
	 *
	 * @return {JSON} All LearnDash courses
	 */
	public function getCoursesAsJson() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;

		$query = "SELECT posts.ID as 'id', CONCAT(posts.post_title, \"(ID:\" , posts.ID, \")\")  as 'text' FROM $wpdb->posts posts WHERE posts.post_type = 'sfwd-courses' AND posts.post_status = 'publish'";

		$courses = $wpdb->get_results($query, OBJECT);
		$data = array();
		$data['results'] = $courses;
		wp_send_json_success($data, JSON_PRETTY_PRINT);
	}

	/**
	 * get selected course for product
	 * @return {JSON} selected courses
	 */
	public static function getPreSavedCourses() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;
		$selectedCourses = array();
		if (isset($_GET['pid']) && isset($_GET['lms_course_slug']) && isset($_GET['membership_product_slug'])) {
			$productId = $_GET['pid'];
			$lmsCourseSlug = $_GET['lms_course_slug'];
			$membershipProductSlug = $_GET['membership_product_slug'];

			if ($lmsCourseSlug == LD_COURSE_SLUG) {
				$metaKey = '_bpms-' . $lmsCourseSlug . '-' . $membershipProductSlug . '-courses_attached';

				$getPreSavedCourses = unserialize(get_post_meta($productId, $metaKey, true));
				if (BPMS_DEBUG) {
					error_log(print_r($getPreSavedCourses, true));
				}

				// Eg : SELECT ID as 'id', post_title as 'text' FROM wp_posts WHERE ID IN ('1','39','35')
				$query = "SELECT ID as 'id', CONCAT(posts.post_title, \"(ID:\" , posts.ID, \")\")  as 'text' FROM $wpdb->posts posts WHERE posts.ID IN ('" . implode("','", $getPreSavedCourses) . "')";
			} else {
				// NOTE : Implementation for another LMS when required
			}

			$selectedCourses = $wpdb->get_results($query, OBJECT);
			$data = array();
			$data['results'] = $selectedCourses;
			wp_send_json_success($data, JSON_PRETTY_PRINT);
		} else {
			wp_send_json_error(array('error_msg' => 'Bad request since pid and meta_key is required'), JSON_PRETTY_PRINT);
		}

	}

	/**
	 * Search LearnDash groups as Ajax
	 * @param  {string}   $_GET['search'] - Term searched from UI
	 * @return {JSON} Searched LearnDash group(s) if found
	 */
	public static function searchGroups() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;
		$searchedCourses = array();
		if (isset($_GET['search']) && isset($_GET['lms_course_slug'])) {
			$search = $_GET['search'];
			$lmsCourseSlug = $_GET['lms_course_slug'];

			if ($lmsCourseSlug == LD_COURSE_SLUG) {
				$query = "SELECT CONCAT(posts.ID, \":\" , posts.post_title, \"\") as 'id', CONCAT(posts.post_title, \"(ID:\" , posts.ID, \")\")  as 'text' FROM $wpdb->posts posts WHERE posts.post_type = 'groups' AND posts.post_status = 'publish' AND posts.post_title LIKE \"%$search%\" OR posts.ID LIKE \"%$search%\" limit 2";
			}

		}

		$searchedCourses = $wpdb->get_results($query, OBJECT);
		$data = array();
		$data['results'] = $searchedCourses;
		wp_send_json_success($data, JSON_PRETTY_PRINT);
	}

	/**
	 * get All LearnDash groups for Ajax requests
	 * @return {JSON} All LearnDash groups
	 */
	public static function getGroupsAsJson() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;

		if (isset($_GET['lms_course_slug'])) {
			$lmsCourseSlug = $_GET['lms_course_slug'];
			if ($lmsCourseSlug == LD_COURSE_SLUG) {
				$query = "SELECT posts.ID as 'id', CONCAT(posts.post_title, \"(ID:\" , posts.ID, \")\")  as 'text' FROM $wpdb->posts posts WHERE posts.post_type = 'groups' AND posts.post_status = 'publish'";
			}

		}

		$groups = $wpdb->get_results($query, OBJECT);
		$data = array();
		$data['results'] = $groups;
		wp_send_json_success($data, JSON_PRETTY_PRINT);
	}

	/**
	 * get selected course for product
	 * @return {JSON} selected courses
	 */
	public static function getPreSavedGroups() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;
		$selectedGroups = array();
		if (isset($_GET['pid']) && isset($_GET['lms_course_slug']) && isset($_GET['membership_product_slug'])) {

			$productId = $_GET['pid'];
			$lmsCourseSlug = $_GET['lms_course_slug'];
			$membershipProductSlug = $_GET['membership_product_slug'];
			$metaKey = '_bpms-' . $lmsCourseSlug . '-' . $membershipProductSlug . '-groups_attached';

			if ($lmsCourseSlug == LD_COURSE_SLUG) {
				error_log("getPreSavedGroups(), $metaKey");

				$getPreSavedCourses = unserialize(get_post_meta($productId, $metaKey, true));
				if (BPMS_DEBUG) {
					error_log(print_r($getPreSavedCourses, true));
				}

				// Eg : SELECT ID as 'id', post_title as 'text' FROM wp_posts WHERE ID IN ('1','39','35')
				$query = "SELECT ID as 'id', CONCAT(posts.post_title, \"(ID:\" , posts.ID, \")\")  as 'text' FROM $wpdb->posts posts WHERE posts.ID IN ('" . implode("','", $getPreSavedCourses) . "')";
			} else {
				// NOTE : Implementation for another LMS when required
			}

			$selectedGroups = $wpdb->get_results($query, OBJECT);
			$data = array();
			$data['results'] = $selectedGroups;
			wp_send_json_success($data, JSON_PRETTY_PRINT);
		} else {
			wp_send_json_error(array('error_msg' => 'Bad request since pid and meta_key is required'), JSON_PRETTY_PRINT);
		}
	}

	/**
	 * Return All product events
	 */
	public static function getProductEvents($membershipProductSlugs = null) {

		$lmsCourseSlugs = self::getLmsCourseSlugs(LD_COURSE_SLUG);
		if ($membershipProductSlugs == null) {
			error_log("getProductEvents, membershipProductSlugs is null");
			$membershipProductSlugs = self::getMembershipProductSlugs();
		}

		$products = get_posts(array("post_type" => $membershipProductSlugs));

		$results = array();
		foreach ($products as $product) {

			foreach ($lmsCourseSlugs as $lmsCourseSlug) {

				$isEnabled = get_post_meta($product->ID, "_bpms-$lmsCourseSlug-$product->post_type-is_enabled", true);

				// Display only enabled ones
				if ($isEnabled) {
					$events = unserialize(get_post_meta($product->ID, '_bpms-events', true));
					foreach ($events as $eventIdentifier => $eventMeta) {
						$results[$eventIdentifier] = array('event_identifier' => $eventIdentifier, 'user_id' => $eventMeta['user_id'], 'course_attached' => $eventMeta['course_attached'], 'grant_access' => $eventMeta['grant_access'], 'product_id' => $product->ID, 'created_at' => $eventMeta['created_at'], 'updated_at' => $eventMeta['updated_at'], 'event_edit_url' => $eventMeta['event_edit_url']);
					}
				}
			}

		}

		if (BPMS_DEBUG) {
			error_log(print_r($results, true));
		}
		return $results;

	}

	/**
	 * Update BPMS enrolment(grant/revoke) for particular user
	 * @param {int} $userId - Wordpress's unique ID for user identification
	 * @param {array} $activeVendorTypes - Active membership vendors such as memberpressproduct(By memberpress), product(By WooCommerce)
	 * @return {void}
	 */
	public static function updateBpmsEnrollments($userId) {
		if (BPMS_DEBUG) {
			error_log("updateBpmsEnrollments(), userId is : $userId");
		}

		$accessList = array();
		$lmsCourseSlugs = self::getLmsCourseSlugs(LD_COURSE_SLUG);
		$products = get_posts(array("post_type" => self::getMembershipProductSlugs()));
		// NOTE : LMS Type(s), Eg (Slugs): Learndash
		foreach ($lmsCourseSlugs as $lmsCourseSlug) {

			//NOTE : Vendor Type(s), Eg (Slugs): Memberpress, WooCommerce
			foreach ($products as $product) {

				$isEnabled = get_post_meta($product->ID, "_bpms-$lmsCourseSlug-$product->post_type-is_enabled", true);
				$courseAccessMethod = get_post_meta($product->ID, "_bpms-$lmsCourseSlug-$product->post_type-course_access_method", true);

				if ($isEnabled) {

					$events = unserialize(get_post_meta($product->ID, '_bpms-events', true));

					if (is_array($events) && !empty($events)) {

						foreach ($events as $eventIdentifier => $eventMeta) {

							if ($eventMeta['user_id'] == $userId) {
								$coursesEnrolled = unserialize($eventMeta['course_attached']);

								if (is_array($coursesEnrolled) && !empty($coursesEnrolled)) {

									foreach ($coursesEnrolled as $courseId) {

										if (isset($accessList[$courseId])) {
											//NOTE : Change flag to true
											if ($eventMeta['grant_access']) {
												$accessList[$courseId] = true;
											}
										} else {
											//NOTE : Setting up first time value
											$accessList[$courseId] = $eventMeta['grant_access'] ? true : false;
										}

									}

								}

							}
						}
					}

				}

			}

			if (BPMS_DEBUG) {
				error_log("accessList is below:");
				error_log(print_r($accessList, true));
			}

			// Grant or Revoke based on grantFlag
			foreach ($accessList as $courseId => $grantFlag) {
				if ($lmsCourseSlug == LD_COURSE_SLUG) {
					$grantFlag ? ld_update_course_access($userId, $courseId, false) : ld_update_course_access($userId, $courseId, true);
				} else {
					// NOTE : Implementation for another LMS when required
				}

			}

		}

	}

	/**
	 * @param  {object}      $membershipObj
	 * @param  {string}      $membershipProductSlug - Product post type, eg : memberpressproduct, product
	 * @param  {boolean}     $grantAccess - Whether to grant/revoke access
	 * @return {void}
	 */
	public static function updateBpmsMembershipEvent($membershipObj, $membershipProductSlug, $grantAccess = true) {

		if (BPMS_DEBUG) {
			error_log("updateBpmsMembershipEvent(),productPostType :  $membershipProductSlug");
		}
		$lmsCourseSlugs = self::getLmsCourseSlugs(LD_COURSE_SLUG);
		// NOTE : LMS Type(s), Eg (Slugs): Learndash
		foreach ($lmsCourseSlugs as $lmsCourseSlug) {
			if ($membershipProductSlug == MP_PRODUCT_SLUG) {

				$isRecurring = MpHelper::mpIsRecurring($membershipObj);

				if ($isRecurring) {

					if (isset($membershipObj->subscr_id)) {
						//Existing but Recurring transaction
						$assignedId = $membershipObj->id;
					} else {
						//New but Recurring transaction
						$assignedId = $membershipObj->subscription_id;

					}
					$eventIdentifier = "$membershipProductSlug-RECURRING-$assignedId";
					$eventEditUrl = "admin.php?page=memberpress-subscriptions&action=edit&id=$assignedId";

				} else {
					$assignedId = $membershipObj->id;
					$eventIdentifier = "$membershipProductSlug-NON-RECURRING-$assignedId";
					$eventEditUrl = "admin.php?page=memberpress-trans&action=edit&id=$assignedId";

				}

				// NOTE : MUST do this to ignoring repeatition
				$events = unserialize(get_post_meta($membershipObj->product_id, '_bpms-events', true));
				if (isset($events[$eventIdentifier])) {
					if (BPMS_DEBUG) {
						error_log("Event EXISTS for this user, just update grant access");
						error_log("product_id : $membershipObj->product_id");
						error_log(print_r($events[$eventIdentifier], true));
					}

					$events[$eventIdentifier]['grant_access'] = $grantAccess;
					$events[$eventIdentifier]['updated_at'] = date('Y-m-d H:i:s');
				} else {
					if (BPMS_DEBUG) {
						error_log("Event DO NOT exists for this user : $membershipObj->user_id");
					}

					$courseAccessMethod = get_post_meta($membershipObj->product_id, "_bpms-$lmsCourseSlug-$membershipProductSlug-course_access_method", true);
					if (BPMS_DEBUG) {
						error_log("Course Access Method selected is : $courseAccessMethod");
					}

					if ($courseAccessMethod == 'SINGLE_COURSES') {
						$coursesAttached = unserialize(get_post_meta($membershipObj->product_id, "_bpms-$lmsCourseSlug-$membershipProductSlug-courses_attached", true));

					} else if ($courseAccessMethod == 'ALL_COURSES') {
						$coursesAttached = self::getLearndashAllCourses();
					} else if ($courseAccessMethod == 'LD_GROUPS') {
						$groupsAttached = unserialize(get_post_meta($membershipObj->product_id, "_bpms-$lmsCourseSlug-$membershipProductSlug-groups_attached", true));
						$coursesAttached = array();
						foreach ($groupsAttached as $groupId) {
							$ids = learndash_group_enrolled_courses($groupId);
							// NOTE : Array format is consistent with GUI
							$coursesAttached = array_merge($ids, $coursesAttached);
						}
					}
					// error_log(print_r($coursesAttached, true));
					$events[$eventIdentifier] = array(
						'user_id' => $membershipObj->user_id,
						'course_attached' => serialize(array_values($coursesAttached)),
						'grant_access' => $grantAccess,
						'created_at' => date('Y-m-d H:i:s'),
						'updated_at' => date('Y-m-d H:i:s'),
						'event_edit_url' => $eventEditUrl);

				}
				if (BPMS_DEBUG) {
					error_log("Events on Product:");
					error_log(print_r($events, true));
				}

				// Finally serialize and update
				update_post_meta($membershipObj->product_id, '_bpms-events', serialize($events));
				self::updateBpmsEnrollments($membershipObj->user_id);

			} else if ($membershipProductSlug == WC_PRODUCT_SLUG) {

				//@todo : Verify if subscription object is different than normal order
				$eventIdentifier = $membershipProductSlug . '-' . $membershipObj['order_id'];
				$eventEditUrl = "post.php?post=" . $membershipObj['order_id'] . "&action=edit";

				$events = unserialize(get_post_meta($membershipObj['product_id'], '_bpms-events', true));
				if (isset($events[$eventIdentifier])) {
					if (BPMS_DEBUG) {
						error_log("Event EXISTS for this user, just update grant access");
						error_log(print_r($events[$eventIdentifier], true));
					}

					$events[$eventIdentifier]['grant_access'] = $grantAccess;
					$events[$eventIdentifier]['updated_at'] = date('Y-m-d H:i:s');
				} else {
					$courseAccessMethod = get_post_meta($membershipObj['product_id'], "_bpms-$lmsCourseSlug-$membershipProductSlug-course_access_method", true);

					if (BPMS_DEBUG) {
						error_log("Event DO NOT exists for this user : " . $membershipObj['customer_id']);
						error_log("Course Access Method selected is : $courseAccessMethod");
					}

					if ($courseAccessMethod == 'SINGLE_COURSES') {
						$coursesAttached = unserialize(get_post_meta($membershipObj['product_id'], "_bpms-$lmsCourseSlug-$membershipProductSlug-courses_attached", true));

					} else if ($courseAccessMethod == 'ALL_COURSES') {
						$coursesAttached = self::getLearndashAllCourses();

					} else if ($courseAccessMethod == 'LD_GROUPS') {
						$groupsAttached = unserialize(get_post_meta($membershipObj['product_id'], "_bpms-$lmsCourseSlug-$membershipProductSlug-groups_attached", true));
						$coursesAttached = array();
						foreach ($groupsAttached as $groupId) {
							$ids = learndash_group_enrolled_courses($groupId);
							// NOTE : Array format is consistent with GUI
							$coursesAttached = array_merge($ids, $coursesAttached);
						}
					}
					if (BPMS_DEBUG) {
						error_log(print_r($coursesAttached, true));
					}
					$events[$eventIdentifier] = array('user_id' => $membershipObj['customer_id'], 'course_attached' => serialize(array_values($coursesAttached)), 'grant_access' => $grantAccess, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'event_edit_url' => $eventEditUrl);

				}
				if (BPMS_DEBUG) {
					error_log("Events on Product:");
					error_log(print_r($events, true));
				}

				// Finally serialize and update
				update_post_meta($membershipObj['product_id'], '_bpms-events', serialize($events));
				self::updateBpmsEnrollments($membershipObj['customer_id']);

			}
		}

	}

	/**
	 * Retrieve selected LMS such as sfwd-courses(Learndash) or lifter-courses(LifterLms)
	 * @return {array | null}
	 */
	public static function getLmsCourseSlugs($default = null) {

		//NOTE : For Flexibility to use key/value, Eg : $array['sfwd-courses'] = 'sfwd-courses'
		$integrationsEnabled[$default] = $default;

		if (bp_get_option('bp-learndash_enabled')) {
			$integrationsEnabled[LD_COURSE_SLUG] = LD_COURSE_SLUG;
		}

		return $integrationsEnabled;
	}

	/**
	 * Retrieve product slug of vendor-types such as memberpress(Memberpress) or product(WooCommerce) if they are ACTIVE/ENABLED/CHECKED
	 * @return {array | null}
	 */
	public static function getMembershipProductSlugs() {
		if (BPMS_DEBUG) {
			error_log("getMembershipProductSlugs()");
		}
		//NOTE - For Flexibility to use key/value, Eg : $array['product'] = 'product';
		$integrationsEnabled = null;

		if (bp_get_option('bp-memberpress_enabled')) {
			$integrationsEnabled[MP_PRODUCT_SLUG] = MP_PRODUCT_SLUG;
		}

		if (bp_get_option('bp-woocommerce_enabled')) {
			$integrationsEnabled[WC_PRODUCT_SLUG] = WC_PRODUCT_SLUG;
		}

		return $integrationsEnabled;
	}

	/**
	 * @param  {Object}      $membershipObj
	 * @param  {string}      $membershipProductSlug
	 * @param  {boolean}     $grantAccess
	 * @return {void}
	 */
	public static function bpmsUpdateMembershipAccess($membershipObj, $membershipProductSlug, $grantAccess = true) {
		if (BPMS_DEBUG) {
			error_log("bpmsUpdateMembershipAccess(), membershipProductSlug : $membershipProductSlug");
		}

		global $wpdb;
		$lmsCourseSlugs = self::getLmsCourseSlugs(LD_COURSE_SLUG);
		// NOTE : LMS Type(s), Eg (Slugs): Learndash
		foreach ($lmsCourseSlugs as $lmsCourseSlug) {
			if ($membershipProductSlug == MP_PRODUCT_SLUG) {

				$isEnabled = get_post_meta($membershipObj->product_id, "_bpms-$lmsCourseSlug-$membershipProductSlug-is_enabled", true);
				if ($isEnabled) {
					error_log("isEnabled");

					// NOTE : Update BBMS Event
					self::updateBpmsMembershipEvent($membershipObj, $membershipProductSlug, $grantAccess);
				} else {
					error_log("isDisabled");
				}
			} else if ($membershipProductSlug == WC_PRODUCT_SLUG) {
				$items = $membershipObj->get_items();

				foreach ($items as $key => $itemObj) {
					//NOTE : Manually passing order_id, customer_id
					$itemObj['order_id'] = $membershipObj->ID;
					$itemObj['customer_id'] = $membershipObj->customer_id;

					if (BPMS_DEBUG) {
						error_log("ProductId : " . $itemObj['product_id']);
						error_log("OrderId : " . $itemObj['order_id']);
						error_log("CustomerId : " . $itemObj['customer_id']);
					}

					$isEnabled = get_post_meta($itemObj['product_id'], "_bpms-$lmsCourseSlug-$membershipProductSlug-is_enabled", true);
					if ($isEnabled) {
						// NOTE : Update BBMS Event
						self::updateBpmsMembershipEvent($itemObj, $membershipProductSlug, $grantAccess);
					}

				}
			}
		}

	}

	/**
	 * Course access UI(selectbox) option(value and text)
	 * @return {array}
	 */
	public function getCourseOptions() {
		$options = array("SINGLE_COURSES" => "Single courses", "ALL_COURSES" => "All courses", "LD_GROUPS" => "LearnDash groups");
		return $options;
	}

	/**
	 * Get All learndash course which are 'closed'
	 * @param {boolean} $bypass_transient - Whether to bypass or reuse existing transient for quick retrieval
	 * @return {array}
	 */
	public static function getLearndashAllCourses() {

		global $wpdb;

		$sql_str = "SELECT postmeta.post_id as post_id FROM " . $wpdb->postmeta . " as postmeta INNER JOIN " . $wpdb->posts . " as posts ON posts.ID = postmeta.post_id WHERE posts.post_status='publish' AND posts.post_type='sfwd-courses' AND postmeta.meta_key='_sfwd-courses'";
		$course_ids = $wpdb->get_col($sql_str);

		return $course_ids;
	}

	/**
	 * Memberpress Hooks
	 * @return void
	 */
	public function mpHooks($classObj) {
		if (BPMS_DEBUG) {
			error_log("BpMemberships->mpHooks()");
		}

		add_action('mepr-product-options-tabs', array($classObj, 'mpTab'));
		add_action('mepr-product-options-pages', array($classObj, 'mpTabContent'));
		add_action('mepr-membership-save-meta', array($classObj, 'mpSaveProduct'));

		// Signup related hooks. NOTE : Type can be 'free', 'non-recurring' or 'recurring'
		add_action('mepr-signup', array($classObj, 'mpSignUp'));

		// Transaction related hooks
		add_action('mepr-txn-status-complete', array($classObj, 'mpTransactionUpdated'));
		add_action('mepr-txn-status-pending', array($classObj, 'mpTransactionUpdated'));
		add_action('mepr-txn-status-failed', array($classObj, 'mpTransactionUpdated'));
		add_action('mepr-txn-status-refunded', array($classObj, 'mpTransactionUpdated'));
		add_action('mepr-txn-status-confirmed', array($classObj, 'mpTransactionUpdated'));
		add_action('mepr-transaction-expired', array($classObj, 'mpTransactionUpdated'));

		// Subscription Related
		add_action('mepr_subscription_stored', array($classObj, 'mpTransactionUpdated'));
		add_action('mepr_subscription_saved', array($classObj, 'mpTransactionUpdated'));
		// add_action('mepr_subscription_stored', array($classObj, 'mpSubscriptionUpdated'));
		// add_action('mepr_subscription_saved', array($classObj, 'mpSubscriptionUpdated'));

		// More useful hooks when required
		// -----------------------------------------------------------------------------
		/* Related to sign-ups
		1) mepr-non-recurring-signup,
		2) mepr-free-signup
		3) mepr-recurring-signup

		Related to transactions
		1) mepr_subscription_transition_status

		Related to subscriptions
		1) mepr_subscription_transition_status
		2) mepr_subscription_status_created
		3) mepr_subscription_status_paused
		4) mepr_subscription_status_resumed
		5) mepr_subscription_status_stopped
		6) mepr_subscription_status_upgraded
		7) mepr_subscription_status_downgraded
		8) mepr_subscription_status_expired
		*/
	}

	/**
	 * WooCommerce Hooks
	 * @return void
	 */
	public function wcHooks($classObj) {
		if (BPMS_DEBUG) {
			error_log("BpMemberships->wcHooks()");
		}

		add_filter('woocommerce_product_data_tabs', array($classObj, 'wcTab'));
		add_action('woocommerce_product_data_panels', array($classObj, 'wcTabContent'));
		// On Save/Update
		add_action('save_post_product', array($classObj, 'wcProductUpdate'));

		// Order related hooks for WC
		add_action('woocommerce_order_status_pending_to_processing', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_pending_to_completed', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_processing_to_cancelled', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_pending_to_failed', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_pending_to_on-hold', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_failed_to_processing', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_failed_to_completed', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_failed_to_on-hold', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_cancelled_to_processing', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_cancelled_to_completed', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_cancelled_to_on-hold', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_on-hold_to_processing', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_on-hold_to_cancelled', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_on-hold_to_failed', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_completed', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_fully_refunded', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_partially_refunded', array($classObj, 'wcOrderUpdated'));

		add_action('woocommerce_order_status_pending', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_completed', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_processing', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_on-hold', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_completed', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_cancelled', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_refunded', array($classObj, 'wcOrderUpdated'));
		add_action('woocommerce_order_status_failed', array($classObj, 'wcOrderUpdated'));

		// Subscription related hooks for WC
		add_action('woocommerce_subscription_status_pending', array($classObj, 'wcSubscriptionUpdated'));
		add_action('woocommerce_subscription_status_cancelled', array($classObj, 'wcSubscriptionUpdated'));
		add_action('woocommerce_subscription_status_on-hold', array($classObj, 'wcSubscriptionUpdated'));
		add_action('woocommerce_subscription_status_expired', array($classObj, 'wcSubscriptionUpdated'));
		add_action('woocommerce_subscription_status_active', array($classObj, 'wcSubscriptionUpdated'));
		add_action('woocommerce_subscription_renewal_payment_complete', array($classObj, 'wcSubscriptionUpdated'));

		// More useful hooks when required
		// -----------------------------------------------------------------------------
		/*
			Related to orders
			1) woocommerce_order_status_pending_to_processing
			2) woocommerce_order_status_pending_to_completed
			3) woocommerce_order_status_processing_to_cancelled
			4) woocommerce_order_status_pending_to_failed
			5) woocommerce_order_status_pending_to_on-hold
			6) woocommerce_order_status_failed_to_processing
			7) woocommerce_order_status_failed_to_completed
			8) woocommerce_order_status_failed_to_on-hold
			9) woocommerce_order_status_cancelled_to_processing
			10) woocommerce_order_status_cancelled_to_completed
			11) woocommerce_order_status_cancelled_to_on-hold
			12) woocommerce_order_status_on-hold_to_processing
			13) woocommerce_order_status_on-hold_to_cancelled
			14) woocommerce_order_status_on-hold_to_failed
			15) woocommerce_order_status_completed
			16) woocommerce_order_fully_refunded
			17) woocommerce_order_partially_refunded
			18) woocommerce_payment_complete
			19) woocommerce_order_status_refunded

			Related to transactions
			1) woocommerce_order_details_after_order_table
			2) woocommerce_payment_complete
			3) woocommerce_new_order
			4) woocommerce_new_product

			Related to subscriptions
			1) woocommerce_checkout_init
			=> Force user to log in or create account if there is LD course in WC cart
			2) woocommerce_thankyou
			=> Auto complete course transaction

		*/

	}

	/**
	 * Invoked after Learndash is loaded
	 * @return {void}
	 */
	public static function onLearndashInit() {
		if (BPMS_DEBUG) {
			error_log("onLearndashInit()");
		}

		/* Add scripts for admin section for plugin */
		add_action('admin_enqueue_scripts', array($this, 'addAdminScripts'));

		// Memberpress-Learndash Integration
		// -----------------------------------------------------------------------------
		$isEnabled = bp_get_option('bp-memberpress_enabled');
		if ($isEnabled) {
			$this->mpHooks(MpHelper::getInstance());
		}

		// WooCommerce-Learndash Integration
		// -----------------------------------------------------------------------------
		$isEnabled = bp_get_option('bp-woocommerce_enabled');
		if ($isEnabled) {
			$this->wcHooks(WcHelper::getInstance());
		}

		// Ajax services, related to courses
		// -----------------------------------------------------------------------------
		add_action('wp_ajax_search_courses', array($this, 'searchCourses'));
		add_action('wp_ajax_get_courses', array($this, 'getCoursesAsJson'));
		add_action('wp_ajax_pre_saved_courses', array($this, 'getPreSavedCourses'));

		// Ajax services, related to groups
		// -----------------------------------------------------------------------------
		add_action('wp_ajax_search_groups', array($this, 'searchGroups'));
		add_action('wp_ajax_get_groups', array($this, 'getGroupsAsJson'));
		add_action('wp_ajax_pre_saved_groups', array($this, 'getPreSavedGroups'));

		$bpProductEvents = BpProductEvents::getInstance();
	}

}