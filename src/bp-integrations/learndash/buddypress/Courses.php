<?php
/**
 * BuddyBoss LearnDash integration courses class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Buddypress;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class for all courses related functions
 *
 * @since BuddyBoss 1.0.0
 */
class Courses {

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
		add_action( 'bp_ld_sync/courses_loop/after_title', array( $this, 'showUserProgress' ), 10 );
		add_action( 'bp_ld_sync/courses_loop/after_title', array( $this, 'showGroupProgress' ), 20 );
		add_action( 'bp_ld_sync/courses_loop/after_title', array( $this, 'showCourseButton' ), 30 );
	}

	/**
	 * Output user course progress html
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function showUserProgress() {
		if ( ! groups_is_user_member( bp_loggedin_user_id(), groups_get_current_group()->id ) ) {
			return;
		}

		if ( groups_is_user_admin( bp_loggedin_user_id(), groups_get_current_group()->id ) ) {
			return;
		}

		$label    = __( 'Your Progress', 'buddyboss' );
		$progress = $this->getUserCourseProgress();
		require bp_locate_template( 'groups/single/courses-progress.php', false, false );
	}

	/**
	 * Output group course progress html
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function showGroupProgress() {
		$label    = __( 'Group Progress', 'buddyboss' );
		$progress = $this->getGroupCourseProgress();
		require bp_locate_template( 'groups/single/courses-progress.php', false, false );
	}

	/**
	 * Output course link button html
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function showCourseButton() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$label = $this->getUserCourseViewButtonLabel( bp_loggedin_user_id(), get_the_ID() );
		require bp_locate_template( 'groups/single/courses-view-button.php', false, false );
	}

	/**
	 * Returns the courses belongs to the group
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getGroupCourses() {
		$group_id  = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( groups_get_current_group()->id );
		$courseIds = learndash_group_enrolled_courses( $group_id );

		/**
		 * Filter to update course lists
		 */
		return array_map( 'get_post', apply_filters( 'bp_ld_learndash_group_enrolled_courses', $courseIds, $group_id ) );
	}

	/**
	 * Get user's course progress
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getUserCourseProgress( $courseId = null, $userId = null ) {
		if ( ! $courseId ) {
			$courseId = get_the_ID();
		}

		if ( ! $userId ) {
			$userId = bp_loggedin_user_id();
		}

		$totalSteps = learndash_get_course_steps_count( $courseId );

		if ( $totalSteps == 0 ) {
			return 0;
		}

		$userSteps = learndash_course_get_completed_steps( $userId, $courseId );

		return round( $userSteps / $totalSteps * 100 );
	}

	/**
	 * Get gorup's course progress
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getGroupCourseProgress( $courseId = null ) {
		if ( ! $courseId ) {
			$courseId = get_the_ID();
		}

		$members    = groups_get_group_members();
		$totalSteps = learndash_get_course_steps_count( $courseId );

		if ( $members['count'] == 0 ) {
			return 0;
		}

		if ( $totalSteps == 0 ) {
			return 0;
		}

		$totalSteps  = learndash_get_course_steps_count( $courseId );
		$memberSteps = array_sum(
			array_map(
				function( $member ) use ( $courseId ) {
					return learndash_course_get_completed_steps( $member->id, $courseId );
				},
				$members['members']
			)
		);

		return round( $memberSteps / ( $members['count'] * $totalSteps ) * 100 );
	}

	/**
	 * Returns dynamic label text based on user's course progress
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getUserCourseViewButtonLabel( $userId, $courseId ) {
		$label  = __( 'Start Course', 'buddyboss' );
		$status = learndash_course_status( $courseId, $userId, true );

		if ( 'in-progress' === $status ) {
			$label = __( 'Continue', 'buddyboss' );
		}

		if ( 'completed' === $status ) {
			$label = __( 'View Course', 'buddyboss' );
		}

		return $label;
	}
}
