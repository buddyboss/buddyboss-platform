<?php
/**
 * BuddyBoss LearnDash integration hooks class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Learndash;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class adds additional missing hooks from Learndash
 *
 * @since BuddyBoss 1.0.0
 */
class Hooks {

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		 add_action( 'bp_ld_sync/init', array( $this, 'init' ) );
	}

	/**
	 * Add actions once integration is ready
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function init() {
		// add some helpful missing hooks
		add_action( 'ld_group_postdata_updated', array( $this, 'groupUpdated' ) );
		add_action( 'before_delete_post', array( $this, 'groupDeleting' ) );

		// backward compet, we check the meta instead of using hook (hook not consistant)
		add_action( 'update_user_meta', array( $this, 'checkLearndashGroupUpdateMeta' ), 10, 4 );
		add_action( 'added_user_meta', array( $this, 'checkLearndashGroupUpdateMeta' ), 10, 4 );
		add_action( 'deleted_user_meta', array( $this, 'checkLearndashGroupDeleteMeta' ), 10, 4 );

		add_action( 'update_post_meta', array( $this, 'checkLearndashCourseUpdateMeta' ), 10, 4 );
		add_action( 'added_post_meta', array( $this, 'checkLearndashCourseUpdateMeta' ), 10, 4 );
		add_action( 'deleted_post_meta', array( $this, 'checkLearndashCourseDeleteMeta' ), 10, 4 );
		add_filter( 'bp_ps_field_can_filter', array( $this, 'checkLearndashCourseFilter' ), 10, 2 );
	}

	/**
	 * Skip the leardash course field from member search when course price type or course mode is 'open'.
	 *
	 * @since BuddyBoss 1.6.0
	 *
	 * @param bool  $has_access Default activity for leardash course search field.
	 * @param mixed $field      Field attributes for learndash course.
	 *
	 * @uses learndash_get_setting() Get learndash individual course settings.
	 *
	 * @return bool
	 */
	public function checkLearndashCourseFilter( $has_access, $field ) {
		if ( is_object( $field ) && property_exists( $field, 'code' ) && property_exists( $field, 'value' ) && 'field_learndash_courses' === $field->code ) {
			$course_settings = learndash_get_setting( $field->value );

			// Skip the leardash course field from member search when course price type or course mode is 'open'.
			if ( isset( $course_settings['course_price_type'] ) && 'open' === $course_settings['course_price_type'] ) {
				return false;
			}
		}

		return $has_access;
	}

	/**
	 * Sub action when ld gorup is created
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function groupUpdated( $groupId ) {
		do_action( 'bp_ld_sync/learndash_group_updated', $groupId );
	}

	/**
	 * Sub action before ld gorup is deleted
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function groupDeleting( $groupId ) {
		global $wpdb;

		$post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID = %d", $groupId ) );

		if ( $post->post_type != 'groups' ) {
			return false;
		}

		do_action( 'bp_ld_sync/learndash_group_deleting', $groupId );
		add_action( 'delete_post', array( $this, 'groupDeleted' ) );
	}

	/**
	 * Sub action after ld gorup is deleted
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function groupDeleted( $groupId ) {
		remove_action( 'delete_post', array( $this, 'groupDeleted' ) );
		do_action( 'bp_ld_sync/learndash_group_deleted', $groupId );
	}

	/**
	 * Sub actions when admin or user is added to ld group
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function checkLearndashGroupUpdateMeta( $metaId, $userId, $metaKey, $metaValue ) {
		if ( $this->isLearndashLeaderMeta( $metaKey ) ) {
			$groupId = $this->getLeardashMetaGroupId( $metaKey );
			return do_action( 'bp_ld_sync/learndash_group_admin_added', $groupId, $userId );
		}

		if ( $this->isLearndashUserMeta( $metaKey ) ) {
			$groupId = $this->getLeardashMetaGroupId( $metaKey );
			return do_action( 'bp_ld_sync/learndash_group_user_added', $groupId, $userId );
		}
	}

	/**
	 * Sub actions when admin or user is removed from ld group
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function checkLearndashGroupDeleteMeta( $metaId, $userId, $metaKey, $metaValue ) {
		if ( $this->isLearndashLeaderMeta( $metaKey ) ) {
			$groupId = $this->getLeardashMetaGroupId( $metaKey );
			return do_action( 'bp_ld_sync/learndash_group_admin_removed', $groupId, $userId );
		}

		if ( $this->isLearndashUserMeta( $metaKey ) ) {
			$groupId = $this->getLeardashMetaGroupId( $metaKey );
			return do_action( 'bp_ld_sync/learndash_group_user_removed', $groupId, $userId );
		}
	}

	/**
	 * sub action when a course is added to ld group
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function checkLearndashCourseUpdateMeta( $metaId, $groupId, $metaKey, $metaValue ) {
		if ( $this->isLearndashCourseMeta( $metaKey ) ) {
			$courseId = $this->getLeardashMetaGroupId( $metaKey );
			return do_action( 'bp_ld_sync/learndash_group_course_added', $groupId, $courseId );
		}
	}

	/**
	 * Sub action when a course is deleted from ld group
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function checkLearndashCourseDeleteMeta( $metaId, $groupId, $metaKey, $metaValue ) {
		if ( $this->isLearndashCourseMeta( $metaKey ) ) {
			$courseId = $this->getLeardashMetaGroupId( $metaKey );
			return do_action( 'bp_ld_sync/learndash_group_course_deleted', $groupId, $userId );
		}
	}

	/**
	 * If the key is a ld leader meta key
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function isLearndashLeaderMeta( $key ) {
		return strpos( $key, 'learndash_group_leaders_' ) === 0;
	}

	/**
	 * If the key is a ld user meta key
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function isLearndashUserMeta( $key ) {
		return strpos( $key, 'learndash_group_users_' ) === 0;
	}

	/**
	 * If the key is a ld course meta key
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function isLearndashCourseMeta( $key ) {
		return strpos( $key, 'learndash_group_enrolled_' ) === 0;
	}

	/**
	 * Get the gorup id from the meta key
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function getLeardashMetaGroupId( $key ) {
		$segments = explode( '_', $key );
		return array_pop( $segments );
	}
}
