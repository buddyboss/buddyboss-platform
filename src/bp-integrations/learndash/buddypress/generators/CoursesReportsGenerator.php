<?php
/**
 * BuddyBoss LearnDash integration courses reports generator.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Buddypress\Generators;

use Buddyboss\LearndashIntegration\Library\ReportsGenerator;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Extends report generator for courses reports
 *
 * @since BuddyBoss 1.0.0
 */
class CoursesReportsGenerator extends ReportsGenerator {

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		 $this->completed_table_title  = __( 'Completed Courses', 'buddyboss' );
		$this->incompleted_table_title = __( 'Incomplete Courses', 'buddyboss' );

		parent::__construct();
	}

	/**
	 * Returns the columns and their settings
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function columns() {
		if ( groups_is_user_mod( bp_loggedin_user_id(), groups_get_current_group()->id ) || groups_is_user_admin( bp_loggedin_user_id(), groups_get_current_group()->id ) || bp_current_user_can( 'bp_moderate' ) ) {
			return array(
				'user_id'         => $this->column( 'user_id' ),
				'user'            => $this->column( 'user' ),
				'course_id'       => $this->column( 'course_id' ),
				'course'          => $this->column( 'course' ),
				'start_date'      => $this->column( 'start_date' ),
				'completion_date' => $this->column( 'completion_date' ),
				'updated_date'    => $this->column( 'updated_date' ),
				'time_spent'      => $this->column( 'time_spent' ),
				'points'          => $this->column( 'points' ),
			);
		} else {
			return array(
				'user_id'         => $this->column( 'user_id' ),
				//'user'            => $this->column( 'user' ),
				'course_id'       => $this->column( 'course_id' ),
				'course'          => $this->column( 'course' ),
				'start_date'      => $this->column( 'start_date' ),
				'completion_date' => $this->column( 'completion_date' ),
				'updated_date'    => $this->column( 'updated_date' ),
				'time_spent'      => $this->column( 'time_spent' ),
				'points'          => $this->column( 'points' ),
			);
		}
	}

	/**
	 * Format the activity results for each column
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function formatData( $activity ) {
		if ( groups_is_user_mod( bp_loggedin_user_id(), groups_get_current_group()->id ) || groups_is_user_admin( bp_loggedin_user_id(), groups_get_current_group()->id ) || bp_current_user_can( 'bp_moderate' ) ) {
			return array(
				'user_id'         => $activity->user_id,
				'user'            => bp_core_get_user_displayname( $activity->user_id ),
				'course_id'       => $activity->activity_course_id,
				'course'          => $activity->activity_course_title,
				'start_date'      => date_i18n( bp_get_option( 'date_format' ),
					strtotime( $activity->activity_started_formatted ) ),
				'completion_date' => $this->completionDate( $activity ),
				'updated_date'    => $this->updatedDate( $activity ),
				'time_spent'      => $this->timeSpent( $activity ),
				'points'          => $this->coursePointsEarned( $activity ),
			);
		} else {
			return array(
				'user_id'         => $activity->user_id,
				//'user'            => bp_core_get_user_displayname( $activity->user_id ),
				'course_id'       => $activity->activity_course_id,
				'course'          => $activity->activity_course_title,
				'start_date'      => date_i18n( bp_get_option( 'date_format' ),
					strtotime( $activity->activity_started_formatted ) ),
				'completion_date' => $this->completionDate( $activity ),
				'updated_date'    => $this->updatedDate( $activity ),
				'time_spent'      => $this->timeSpent( $activity ),
				'points'          => $this->coursePointsEarned( $activity ),
			);
		}
	}
}
