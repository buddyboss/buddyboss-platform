<?php
/**
 * BuddyBoss LearnDash integration quizzes reports generator.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Buddypress\Generators;

use Buddyboss\LearndashIntegration\Library\ReportsGenerator;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Extends report generator for lessons reports
 *
 * @since BuddyBoss 1.0.0
 */
class LessonsReportsGenerator extends ReportsGenerator {

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		 $this->completed_table_title  = __( 'Completed Lessons', 'buddyboss' );
		$this->incompleted_table_title = __( 'Incomplete Lessons', 'buddyboss' );

		parent::__construct();
	}

	/**
	 * Returns the columns and their settings
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function columns() {
		return array(
			'user_id'         => $this->column( 'user_id' ),
			'user'            => $this->column( 'user' ),
			'course_id'       => $this->column( 'course_id' ),
			'course'          => $this->column( 'course' ),
			'lesson'          => array(
				'label'     => __( 'Lesson', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'post_title',
			),
			'start_date'      => $this->column( 'start_date' ),
			'completion_date' => $this->column( 'completion_date' ),
			'updated_date'    => $this->column( 'updated_date' ),
			'time_spent'      => $this->column( 'time_spent' ),
		);
	}

	/**
	 * Format the activity results for each column
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function formatData( $activity ) {
		return array(
			'user_id'         => $activity->user_id,
			'user'            => $activity->user_display_name,
			'course_id'       => $activity->activity_course_id,
			'course'          => $activity->activity_course_title,
			'lesson'          => $activity->post_title,
			'start_date'      => $activity->activity_started_formatted,
			'completion_date' => $this->completionDate( $activity ),
			'updated_date'    => $this->updatedDate( $activity ),
			'time_spent'      => $this->timeSpent( $activity ),
		);
	}

	/**
	 * Overwrite results value for export
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function formatDataForExport( $data, $activity ) {
		$data['status'] = empty( $activity->activity_completed ) ? $this->incompleted_table_title : $this->completed_table_title;

		return $data;
	}

	/**
	 * Overwrite results value for display
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function formatDataForDisplay( $data, $activity ) {
		$data = bp_parse_args(
			array(
				'lesson' => sprintf(
					'<a href="%s" target="_blank">%s</a>',
					get_permalink( $activity->post_id ),
					$activity->post_title
				),
			),
			$data
		);

		return parent::formatDataForDisplay( $data, $activity );
	}
}
