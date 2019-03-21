<?php
namespace BuddyBoss\Memberships\Classes;

// Block direct requests
if (!defined('ABSPATH')) {
	die("Sorry, you can't access this directly - Security established");
}

define('BPMS_DEBUG', true);
define('LD_POST_TYPE', 'sfwd-courses');
define('MP_POST_TYPE', 'memberpressproduct');
define('WC_POST_TYPE', 'product');
define('APPBOSS_PROD_TYPE', 'app-product');
define('BPMS_URL', BP_PLUGIN_URL . '/src/bp-integrations/memberships');

class BpMemberships {

	private static $instance;

	public function __construct() {

		add_action('wp_loaded', array($this, 'onWpLoaded'));

		/**
		 * Available as hook, runs after a bbms(BuddyBoss Membership) plugin is loaded.
		 */
		do_action('bbms_loaded');
	}

	/**
	 * Will return instance of bbms(BuddyBoss Membership) class
	 * @return object - Singleton
	 */
	public static function getInstance() {

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

		global $pagenow;

		$vendorTypes = self::getVendorTypesSelected();
		if (in_array($pagenow, array('post.php', 'post-new.php'))) {
			global $post;
			$postType = $post->post_type;

			if (in_array($postType, $vendorTypes)) {

				if (BPMS_DEBUG) {
					error_log("addAdminScripts, see vendorTypes below");
					error_log(print_r($vendorTypes, true));
				}

				// Select2 Js
				wp_enqueue_script('select2-js', BPMS_URL . '/assets/scripts/select2.min.js');

				// Select2 Css
				wp_enqueue_style('select2', BPMS_URL . '/assets/styles/select2.min.css');

				// Localize the script with new data
				$bpmsVars = array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'lms_types' => self::getLmsTypesSelected(LD_POST_TYPE),
					'membership_type' => $post->post_type,
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
	public function searchLearndashCourses() {
		error_log("searchLearndashCourses()");
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
	public function getLearndashCoursesAsJson() {
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
	public static function selectedCourses() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;

		if (isset($_GET['pid']) && isset($_GET['meta_key'])) {
			$productId = $_GET['pid'];
			$metaKey = $_GET['meta_key'];

			$selectedCourses = unserialize(get_post_meta($productId, $metaKey, true));
			if (BPMS_DEBUG) {
				error_log(print_r($selectedCourses, true));
			}

			// Eg : SELECT ID as 'id', post_title as 'text' FROM wp_posts WHERE ID IN ('1','39','35')
			$query = "SELECT ID as 'id', CONCAT(posts.post_title, \"(ID:\" , posts.ID, \")\")  as 'text' FROM $wpdb->posts posts WHERE posts.ID IN ('" . implode("','", $selectedCourses) . "')";

			$selected = $wpdb->get_results($query, OBJECT);
			$data = array();
			$data['results'] = $selected;
			wp_send_json_success($data, JSON_PRETTY_PRINT);
		} else {
			wp_send_json_error(array('error_msg' => 'Bad request since pid and meta_key is required'), JSON_PRETTY_PRINT);
		}

	}

	/**
	 * Get value if set else return default
	 * @param  {string}   $_GET['search'] - Term searched from UI
	 * @return {JSON} Searched LearnDash group(s) if found
	 */
	public static function getParam($key, $default) {
		if (isset($_GET[$key])) {
			return $_GET[$key];
		} else {
			return $default;
		}
	}

	/**
	 * Search LearnDash groups as Ajax
	 * @param  {string}   $_GET['search'] - Term searched from UI
	 * @return {JSON} Searched LearnDash group(s) if found
	 */
	public static function searchLearndashGroups() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;

		$search = $_GET['search'];

		$query = "SELECT CONCAT(posts.ID, \":\" , posts.post_title, \"\") as 'id', CONCAT(posts.post_title, \"(ID:\" , posts.ID, \")\")  as 'text' FROM $wpdb->posts posts WHERE posts.post_type = 'groups' AND posts.post_status = 'publish' AND posts.post_title LIKE \"%$search%\" OR posts.ID LIKE \"%$search%\" limit 2";

		$courses = $wpdb->get_results($query, OBJECT);
		$data = array();
		$data['results'] = $courses;
		wp_send_json_success($data, JSON_PRETTY_PRINT);
	}

	/**
	 * get All LearnDash groups for Ajax-call
	 * @return {JSON} All LearnDash groups
	 */
	public static function getLearndashGroupsAsJson() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;

		$query = "SELECT posts.ID as 'id', CONCAT(posts.post_title, \"(ID:\" , posts.ID, \")\")  as 'text' FROM $wpdb->posts posts WHERE posts.post_type = 'groups' AND posts.post_status = 'publish'";

		$courses = $wpdb->get_results($query, OBJECT);
		$data = array();
		$data['results'] = $courses;
		wp_send_json_success($data, JSON_PRETTY_PRINT);
	}

	/**
	 * get selected course for product
	 * @return {JSON} selected courses
	 */
	public static function selectedGroups() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;

		if (isset($_GET['pid']) && isset($_GET['meta_key'])) {

			$productId = $_GET['pid'];
			$metaKey = $_GET['meta_key'];

			$selectedCourses = unserialize(get_post_meta($productId, $metaKey, true));
			if (BPMS_DEBUG) {
				error_log(print_r($selectedCourses, true));
			}

			// Eg : SELECT ID as 'id', post_title as 'text' FROM wp_posts WHERE ID IN ('1','39','35')
			$query = "SELECT ID as 'id', CONCAT(posts.post_title, \"(ID:\" , posts.ID, \")\")  as 'text' FROM $wpdb->posts posts WHERE posts.ID IN ('" . implode("','", $selectedCourses) . "')";

			$selected = $wpdb->get_results($query, OBJECT);
			$data = array();
			$data['results'] = $selected;
			wp_send_json_success($data, JSON_PRETTY_PRINT);
		} else {
			wp_send_json_error(array('error_msg' => 'Bad request since pid and meta_key is required'), JSON_PRETTY_PRINT);
		}
	}

	/**
	 * Runs after a users list of courses is been updated
	 * @param  {string}      $userId
	 * @param  {string}      $courseId
	 * @param  {string}      $courseAccessList - This many users have access to above course($courseId); Eg : 1,12,13
	 * @param {boolean}     $remove
	 * @return {void}
	 */
	public static function learndashUpdateCourseAccess($userId, $courseId, $courseAccessList, $remove) {

		if (BPMS_DEBUG) {
			error_log("learndashUpdateCourseAccess()");
			error_log("userId : $userId");
			error_log("courseId : $courseId");
			error_log("remove : $remove");
			error_log(print_r($courseAccessList, true));
		}

	}

	/**
	 * Runs when Learndash course is been added/updated.
	 * @param  {int}      $postId Post ID.
	 * @param  {WP_Post Object}      $post Post object.
	 * @param  {bool}      $update - Whether this is an existing post being updated or not.
	 * @return {void}
	 */
	public static function learndashCourseAdded($postId, $post, $update) {
		if (BPMS_DEBUG) {
			error_log("learndashCourseAdded()");
		}

		// Only if it is NEW post
		if (!$update) {
		}
	}

	/**
	 * Runs when Learndash group is been added/updated.
	 * @param  {int}      $postId Post ID.
	 * @param  {WP_Post Object}      $post Post object.
	 * @param  {bool}      $update - Whether this is an existing post being updated or not.
	 * @return {void}
	 * @todo : This hook is triggered twice, seems like learndash is trigger save_post_groups as well
	 */
	public static function learndashGroupUpdated($postId, $post, $update) {
		if (BPMS_DEBUG) {
			error_log("learndashGroupUpdated(), update is :  $update");
		}

		// Don't save revisions and autosaves.
		if (wp_is_post_revision($postId) || wp_is_post_autosave($postId) || 'product' !== $post->post_type || !current_user_can('edit_post', $postId)) {
			return $postId;
		}

		// Only if it is UPDATE post
		if ($update) {
		}
	}

	/**
	 * Return All product events
	 */
	public static function getProductEvents($vendorTypes = null) {

		$lmsTypes = self::getLmsTypesSelected(LD_POST_TYPE);
		if ($vendorTypes == null) {
			error_log("getProductEvents, vendorTypes is null");
			$vendorTypes = self::getVendorTypesSelected();
		}

		$products = get_posts(array("post_type" => $vendorTypes));

		$count = count($products);
		error_log("Count in getProductEvents is : $count");
		$results = array();
		foreach ($products as $product) {

			foreach ($lmsTypes as $lmsType) {

				$isEnabled = get_post_meta($product->ID, "_bpms-$lmsType-$product->post_type-is_enabled", true);

				// Display only enabled ones
				if ($isEnabled) {
					$events = unserialize(get_post_meta($product->ID, '_bpms-events', true));
					foreach ($events as $eventIdentifier => $eventMeta) {
						$results[$eventIdentifier] = array('event_identifier' => $eventIdentifier, 'user_id' => $eventMeta['user_id'], 'course_attached' => $eventMeta['course_attached'], 'grant_access' => $eventMeta['grant_access'], 'product_id' => $product->ID, 'created_at' => $eventMeta['created_at'], 'updated_at' => $eventMeta['updated_at']);
					}
				}
			}

		}

		error_log(print_r($results, true));
		return $results;

	}

	/**
	 * Update BBMS enrolment(grant/revoke) for particular user
	 * @param {int} $userId - Wordpress's unique ID for user identification
	 * @param {array} $activeVendorTypes - Active membership vendors such as memberpressproduct(By memberpress), product(By WooCommerce)
	 * @return {void}
	 */
	public static function updateBbmsEnrollments($userId) {

		if (BPMS_DEBUG) {
			error_log("updateBbmsEnrollments() for userId : $userId");
		}
		$accessList = array();
		$lmsTypes = self::getLmsTypesSelected(LD_POST_TYPE);
		$products = get_posts(array("post_type" => self::getVendorTypesSelected()));
		$count = count($products);
		error_log("Count is : $count");
		//NOTE : Vendor Type(s), Eg (Slugs): Memberpress, WooCommerce
		foreach ($products as $product) {
			error_log("productId : $product->ID");

			// NOTE : LMS Type(s), Eg (Slugs): Learndash
			foreach ($lmsTypes as $lmsSlug) {
				$isEnabled = get_post_meta($product->ID, "_bpms-$lmsSlug-$product->post_type-is_enabled", true);
				$courseAccessMethod = get_post_meta($product->ID, "_bpms-$lmsSlug-$product->post_type-course_access_method", true);

				if ($isEnabled) {

					$events = unserialize(get_post_meta($product->ID, '_bpms-events', true));

					if (is_array($events) && !empty($events)) {

						foreach ($events as $eventIdentifier => $eventMeta) {
							// error_log("after foreach");

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

		}

		error_log("accessList is below:");
		error_log(print_r($accessList, true));

		// Grant or Revoke based on grantFlag
		foreach ($accessList as $courseId => $grantFlag) {
			if (self::getLmsTypesSelected(LD_POST_TYPE) == LD_POST_TYPE) {
				$grantFlag ? ld_update_course_access($userId, $courseId, false) : ld_update_course_access($userId, $courseId, true);
			}

		}

	}

	/**
	 * @param  {object}      $vendorObj
	 * @param  {string}      $vendorType - Product post type, eg : memberpressproduct, product
	 * @param  {boolean}     $grantAccess - Whether to grant/revoke access
	 * @return {void}
	 */
	public static function updateBbmsEvent($vendorObj, $vendorType, $grantAccess = true) {

		if (BPMS_DEBUG) {
			error_log("updateBbmsEvent(),productPostType :  $vendorType");
		}
		$lmsType = self::getLmsTypesSelected(LD_POST_TYPE);

		if ($vendorType == MP_POST_TYPE) {

			if ($vendorObj->subscription_id == 0) {
				$eventIdentifier = $vendorType . '-' . $vendorObj->id;
			} else {
				$eventIdentifier = $vendorType . '-' . $vendorObj->subscription_id;
			}

			$events = unserialize(get_post_meta($vendorObj->product_id, '_bpms-events', true));
			if (isset($events[$eventIdentifier])) {
				error_log("Event EXISTS for this user, just update grant access");
				error_log("product_id : $vendorObj->product_id");
				error_log(print_r($events[$eventIdentifier], true));

				$events[$eventIdentifier]['grant_access'] = $grantAccess;
				$events[$eventIdentifier]['updated_at'] = date('Y-m-d H:i:s');
			} else {
				error_log("Event DO NOT for this user : $vendorObj->user_id");

				$courseAccessMethod = get_post_meta($vendorObj->product_id, "_bpms-$lmsType-$vendorType-course_access_method", true);
				if ($courseAccessMethod == 'SINGLE_COURSES') {
					if (BPMS_DEBUG) {
						error_log("SINGLE_COURSES selected");
					}
					$coursesAttached = unserialize(get_post_meta($vendorObj->product_id, "_bpms-$lmsType-$vendorType-courses_enrolled", true));

				} else if ($courseAccessMethod == 'ALL_COURSES') {
					if (BPMS_DEBUG) {
						error_log("ALL_COURSES selected");
					}
					$coursesAttached = self::getLearndashClosedCourses();

				} else if ($courseAccessMethod == 'LD_GROUPS') {

					if (BPMS_DEBUG) {
						error_log("LD_GROUPS selected");
					}
					$groupsAttached = unserialize(get_post_meta($vendorObj->product_id, "_bpms-$lmsType-$vendorType-groups_attached", true));
					$coursesAttached = array();
					foreach ($groupsAttached as $groupId) {
						$ids = learndash_group_enrolled_courses($groupId);
						// NOTE : Array format is consistent with GUI
						$coursesAttached = array_merge($ids, $coursesAttached);
					}
				}
				// error_log(print_r($coursesAttached, true));
				// error_log("BEFORE Saving");
				$events[$eventIdentifier] = array('user_id' => $vendorObj->user_id, 'course_attached' => serialize(array_values($coursesAttached)), 'grant_access' => $grantAccess, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'));

			}
			error_log("Events on Product:");
			error_log(print_r($events, true));

			// Finally serialize and update
			update_post_meta($vendorObj->product_id, '_bpms-events', serialize($events));
			self::updateBbmsEnrollments($vendorObj->user_id);

		} else if ($vendorType == WC_POST_TYPE) {

			//@todo : Verify if subscription object is different than normal order
			$eventIdentifier = $vendorType . '-' . $vendorObj['order_id'];

			$events = unserialize(get_post_meta($vendorObj['product_id'], '_bpms-events', true));
			if (isset($events[$eventIdentifier])) {
				error_log("Event EXISTS for this user, just update grant access");
				error_log(print_r($events[$eventIdentifier], true));

				$events[$eventIdentifier]['grant_access'] = $grantAccess;
				$events[$eventIdentifier]['updated_at'] = date('Y-m-d H:i:s');
			} else {
				error_log("Event DO NOT for this user : " . $vendorObj['customer_id']);

				$courseAccessMethod = get_post_meta($vendorObj['product_id'], "_bpms-$lmsType-$vendorType-course_access_method", true);
				if ($courseAccessMethod == 'SINGLE_COURSES') {
					if (BPMS_DEBUG) {
						error_log("SINGLE_COURSES selected");
					}
					$coursesAttached = unserialize(get_post_meta($vendorObj['product_id'], "_bpms-$lmsType-$vendorType-courses_enrolled", true));

				} else if ($courseAccessMethod == 'ALL_COURSES') {
					if (BPMS_DEBUG) {
						error_log("ALL_COURSES selected");
					}
					$coursesAttached = self::getLearndashClosedCourses();

				} else if ($courseAccessMethod == 'LD_GROUPS') {

					if (BPMS_DEBUG) {
						error_log("LD_GROUPS selected");
					}
					$groupsAttached = unserialize(get_post_meta($vendorObj['product_id'], "_bpms-$lmsType-$vendorType-groups_attached", true));
					$coursesAttached = array();
					foreach ($groupsAttached as $groupId) {
						$ids = learndash_group_enrolled_courses($groupId);
						// NOTE : Array format is consistent with GUI
						$coursesAttached = array_merge($ids, $coursesAttached);
					}
				}
				// error_log(print_r($coursesAttached, true));
				// error_log("BEFORE Saving");
				$events[$eventIdentifier] = array('user_id' => $vendorObj['customer_id'], 'course_attached' => serialize(array_values($coursesAttached)), 'grant_access' => $grantAccess, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'));

			}
			error_log("Events on Product:");
			error_log(print_r($events, true));

			// Finally serialize and update
			update_post_meta($vendorObj['product_id'], '_bpms-events', serialize($events));
			self::updateBbmsEnrollments($vendorObj['customer_id']);

		}

	}

	/**
	 * Retrieve selected LMS such as sfwd-courses(Learndash) or lifter-courses(LifterLms)
	 * @return {array | null}
	 */
	public static function getLmsTypesSelected($default = null) {

		//For Flexibility to use key/value, Eg : $array['sfwd-courses'] = 'sfwd-courses'
		$integrationsEnabled[$default] = $default;

		if (bp_get_option('bp-learndash_enabled')) {
			$integrationsEnabled[LD_POST_TYPE] = LD_POST_TYPE;
		}
		error_log("under getLmsTypesSelected;");
		error_log(print_r($integrationsEnabled, true));

		return $integrationsEnabled;
	}

	/**
	 * Retrieve vendorTypes such as memberpress(Memberpress) or product(WooCommerce)
	 * @return {array | null}
	 */
	public static function getVendorTypesSelected() {

		//For Flexibility to use key/value, Eg : $array['product'] = 'product';

		$integrationsEnabled = null;

		if (bp_get_option('bp-memberpress_enabled')) {
			$integrationsEnabled[MP_POST_TYPE] = MP_POST_TYPE;
		}

		if (bp_get_option('bp-woocommerce_enabled')) {
			$integrationsEnabled[WC_POST_TYPE] = WC_POST_TYPE;
		}

		error_log("getVendorTypesSelected : See below");
		error_log(print_r($integrationsEnabled, true));
		return $integrationsEnabled;
	}

	/**
	 * @param  {Object}      $vendorObj
	 * @param  {string}      $vendorType
	 * @param  {boolean}     $grantAccess
	 * @return {void}
	 */
	public static function bbmsUpdateMembershipAccess($vendorObj, $vendorType, $grantAccess = true) {
		if (BPMS_DEBUG) {
			error_log("grant_access is $grantAccess");
		}

		global $wpdb;
		$lmsType = self::getLmsTypesSelected(LD_POST_TYPE);

		if ($vendorType == MP_POST_TYPE) {

			$isEnabled = get_post_meta($vendorObj->product_id, "_bpms-$lmsType-$vendorType-is_enabled", true);
			if ($isEnabled) {
				// NOTE : Update BBMS Event
				self::updateBbmsEvent($vendorObj, $vendorType, $grantAccess);
			}
		} else if ($vendorType == WC_POST_TYPE) {
			$items = $vendorObj->get_items();

			foreach ($items as $key => $itemObj) {
				//NOTE : Manually passing order_id, customer_id
				$itemObj['order_id'] = $vendorObj->ID;
				$itemObj['customer_id'] = $vendorObj->customer_id;

				error_log($itemObj['product_id']);
				error_log($itemObj['order_id']);
				error_log($itemObj['customer_id']);

				$isEnabled = get_post_meta($itemObj['product_id'], "_bpms-$lmsType-$vendorType-is_enabled", true);
				if ($isEnabled) {
					// NOTE : Update BBMS Event
					self::updateBbmsEvent($itemObj, $vendorType, $grantAccess);
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
	public static function getLearndashClosedCourses($bypass_transient = false) {

		global $wpdb;

		$transient_key = "bbms_learndash_closed_courses";

		if (!$bypass_transient) {
			$courses_ids_transient = learndash_get_valid_transient($transient_key);
		} else {
			$courses_ids_transient = false;
		}

		if ($courses_ids_transient === false) {

			$sql_str = "SELECT postmeta.post_id as post_id FROM " . $wpdb->postmeta . " as postmeta INNER JOIN " . $wpdb->posts . " as posts ON posts.ID = postmeta.post_id WHERE posts.post_status='publish' AND posts.post_type='sfwd-courses' AND postmeta.meta_key='_sfwd-courses' AND ( postmeta.meta_value REGEXP '\"sfwd-courses_course_price_type\";s:6:\"closed\";' )";
			$course_ids = $wpdb->get_col($sql_str);

			set_transient($transient_key, $course_ids, MINUTE_IN_SECONDS);

		} else {
			$course_ids = $courses_ids_transient;
		}

		return $course_ids;
	}

	/**
	 * Memberpress Hooks
	 * @return void
	 */
	public function mpHooks($classObj) {
		error_log("mpHooks()");

		add_action('mepr-product-options-tabs', array($classObj, 'mpLearndashTab'));
		add_action('mepr-product-options-pages', array($classObj, 'mpTabContent'));
		add_action('mepr-membership-save-meta', array($classObj, 'mpSaveProduct'));

		// Signup type can be 'free', 'non-recurring' or 'recurring'
		add_action('mepr-non-recurring-signup', array($classObj, 'mpSignUp'));
		add_action('mepr-free-signup', array($classObj, 'mpSignUp'));
		add_action('mepr-recurring-signup', array($classObj, 'mpSignUp'));
		add_action('mepr-signup', array($classObj, 'mpSignUp'));
		// Transaction Related
		add_action('mepr-txn-status-complete', array($classObj, 'mpTransactionUpdated'));
		add_action('mepr-txn-status-pending', array($classObj, 'mpTransactionUpdated'));
		add_action('mepr-txn-status-failed', array($classObj, 'mpTransactionUpdated'));
		add_action('mepr-txn-status-refunded', array($classObj, 'mpTransactionUpdated'));
		add_action('mepr-txn-status-confirmed', array($classObj, 'mpTransactionUpdated'));
		add_action('mepr-transaction-expired', array($classObj, 'mpTransactionUpdated'));

		// Subscription Related
		// add_action(array('mepr_subscription_stored', 'mepr_subscription_saved'), array($classObj, 'mpSubscriptionUpdated'));
		add_action('mepr_subscription_saved', array($classObj, 'mpSubscriptionUpdated'));
		// add_action('mepr_subscription_transition_status', array($classObj, 'mpSubscriptionTransitionStatus'));
		// add_action('mepr_subscription_status_created', array($classObj, 'mpSubscriptionTransitionStatus'));
		// add_action('mepr_subscription_status_paused', array($classObj, 'mpSubscriptionTransitionStatus'));
		// add_action('mepr_subscription_status_resumed', array($classObj, 'mpSubscriptionTransitionStatus'));
		// add_action('mepr_subscription_status_stopped', array($classObj, 'mpSubscriptionTransitionStatus'));
		// add_action('mepr_subscription_status_upgraded', array($classObj, 'mpSubscriptionTransitionStatus'));
		// add_action('mepr_subscription_status_downgraded', array($classObj, 'mpSubscriptionTransitionStatus'));
		// add_action('mepr_subscription_status_expired', array($classObj, 'mpSubscriptionTransitionStatus'));

	}

	/**
	 * WooCommerce Hooks
	 * @return void
	 */
	public function wcHooks($classObj) {
		error_log("wcHooks()");

		add_filter('woocommerce_product_data_tabs', array($classObj, 'wcTab'));
		add_action('woocommerce_product_data_panels', array($classObj, 'wcTabContent'));
		// On Save/Update
		add_action('save_post_product', array($classObj, 'wcProductUpdate'));

		// Transaction Related
		// add_action('woocommerce_order_details_after_order_table', array($classObj, 'wcOrderUpdated'));
		// add_action('woocommerce_payment_complete', array($classObj, 'wcOrderUpdated'));
		// add_action('woocommerce_new_order', array($classObj, 'wcOrderUpdated'));
		// add_action('woocommerce_new_product', array($classObj, 'wcVerify'));
		// add_action('woocommerce_new_product', array($classObj, 'wcVerify'));

		// Order hooks for WC

		add_action('woocommerce_order_status_pending_to_processing', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_status_pending_to_completed', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_status_processing_to_cancelled', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_status_pending_to_failed', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_status_pending_to_on-hold', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_status_failed_to_processing', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_status_failed_to_completed', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_status_failed_to_on-hold', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_status_cancelled_to_processing', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_status_cancelled_to_completed', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_status_cancelled_to_on-hold', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_status_on-hold_to_processing', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_status_on-hold_to_cancelled', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_status_on-hold_to_failed', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_status_completed', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_fully_refunded', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_partially_refunded', array($classObj, 'wcOrderUpdated'), 10, 1);

		add_action('woocommerce_order_status_pending', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_status_completed', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_status_processing', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_status_on-hold', array($classObj, 'wcOrderUpdated'), 10, 1);
		add_action('woocommerce_order_status_completed', array($classObj, 'wcOrderUpdated'), 10, 1);

		// add_action('woocommerce_payment_complete', array($classObj, 'wcOrderUpdated'), 10, 1);
		// add_action('woocommerce_order_status_refunded', array($classObj, 'wcOrderUpdated'), 10, 1);

		// Subscription hooks for WC
		add_action('woocommerce_subscription_status_cancelled', array($classObj, 'wcSubscriptionUpdated'), 10, 1);
		add_action('woocommerce_subscription_status_on-hold', array($classObj, 'wcSubscriptionUpdated'), 10, 1);
		add_action('woocommerce_subscription_status_expired', array($classObj, 'wcSubscriptionUpdated'), 10, 1);
		add_action('woocommerce_subscription_status_active', array($classObj, 'wcSubscriptionUpdated'), 10, 1);
		add_action('woocommerce_subscription_renewal_payment_complete', array($classObj, 'wcSubscriptionUpdated'), 10, 1);

		// Other hooks for WC subscription

		// // Force user to log in or create account if there is LD course in WC cart
		// add_action( 'woocommerce_checkout_init', array( __CLASS__, 'force_login' ), 10, 1 );

		// // Auto complete course transaction
		// add_action( 'woocommerce_thankyou', array( __CLASS__, 'auto_complete_transaction' ) );

	}

	/**
	 * 'bp_integrations_learndash_fields_updated' is executed in BP_LearnDash_Admin_Integration_Tab->settings_saved()
	 * @param {array} $bpSettings - Buddyboss Platform settings
	 * @return {void}
	 */
	public static function onBpIntegrationsLearndashFieldsUpdated($bpSettings) {
		if (BPMS_DEBUG) {
			error_log("onBpIntegrationsLearndashFieldsUpdated()");
			error_log(print_r($bpSettings, true));
		}

		//register learndash settings
		register_setting('bbms_learndash_integration', '_bpms-remove-user-on-course-unattached');
		register_setting('bbms_learndash_integration', '_bpms-remove-user-on-membership-deletion');

		//register bbms settings
		// register_setting('bpms-settings', '_bpms-remove-user-on-course-unattached');
		// register_setting('bpms-settings', '_bpms-remove-user-on-membership-deletion');
	}

	/**
	 * 'bp_integrations_learndash_field_added' is executed in BP_LearnDash_Admin_Integration_Tab->register_fields()
	 * @return {void}
	 */
	public static function onBpIntegrationsLearndashFieldAdded($key, $classObj) {
		if (BPMS_DEBUG) {
			error_log("onBpIntegrationsLearndashFieldAdded()");
		}

		if ($key == 'learndash') {

			$classObj->add_section(
				'bbms_learndash_integration',
				__('LearnDash to Memberships', 'buddyboss'),
				[$this, 'bbms_learndash_integration']
			);

			$classObj->add_checkbox_field(
				'_bpms-remove-user-on-course-unattached',
				__('On Course unattached', 'buddyboss'),
				[
					'input_text' => __("Remove User if course unattached from Product. Unattached don't mean course removed from site", 'bbms'), 'buddyboss']);

			$classObj->add_checkbox_field(
				'_bpms-remove-user-on-membership-deletion',
				__('On membership deletion', 'buddyboss'),
				[
					'input_text' => __("Remove User if membership deleted from site.", 'bbms'), 'buddyboss']);

		}

	}

	/**
	 * After wp is loaded
	 * @return {void}
	 */
	public static function onWpLoaded() {
		if (BPMS_DEBUG) {
			error_log("onWpLoaded()");
		}

		$bpProductEvents = BpProductEvents::get_instance();

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

		// Trigger filters/action after Learndash is loaded
		add_action('learndash_init', array($this, 'onLearndashInit'));

		// Ajax services, related to courses
		// -----------------------------------------------------------------------------
		add_action('wp_ajax_search_courses', array($this, 'searchLearndashCourses'));
		add_action('wp_ajax_get_courses', array($this, 'getLearndashCoursesAsJson'));
		add_action('wp_ajax_selected_courses', array($this, 'selectedCourses'));

		// Ajax services, related to groups
		// -----------------------------------------------------------------------------
		add_action('wp_ajax_search_groups', array($this, 'searchLearndashGroups'));
		add_action('wp_ajax_get_groups', array($this, 'getLearndashGroupsAsJson'));
		add_action('wp_ajax_selected_groups', array($this, 'selectedGroups'));

	}

	/**
	 * Invoked after Learndash is loaded
	 * @return {void}
	 */
	public function onLearndashInit() {
		if (BPMS_DEBUG) {
			error_log("onLearndashInit()");
		}

		// Learndash Hooks
		// -----------------------------------------------------------------------------
		if (self::getLmsTypesSelected(LD_POST_TYPE) == LD_POST_TYPE) {
			add_action('save_post_sfwd-courses', array($this, 'learndashCourseAdded'), 4, 99);
			add_action('save_post_groups', array($this, 'learndashGroupUpdated'), 88, 3);
			// add_action('learndash_update_course_access', array($this, 'learndashUpdateCourseAccess'), 4, 99);
			// Remove course increment record if a course unenrolled manually
			// add_action( 'learndash_update_course_access', array( $this, 'remove_access_increment_count' ), 10, 4 );
		}
	}

}