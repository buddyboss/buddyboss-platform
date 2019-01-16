<?php

namespace Buddyboss\LearndashIntegration\Buddypress\Generators;

use Buddyboss\LearndashIntegration\Buddypress\ReportsGenerator;

class LessonsReportsGenerator extends ReportsGenerator
{
	protected function columns()
	{
		return [
			'user'            => $this->column('user'),
			'course'          => $this->column('course'),
			'lesson'          => [
				'label'     => __( 'Lesson', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'post_title',
			],
			'start_date'      => $this->column('start_date'),
			'completion_date' => $this->column('completion_date'),
			'updated_date'    => $this->column('updated_date'),
			'time_spent'      => $this->column('time_spent'),
		];
	}

	protected function formatData($activity)
	{
		return [
			'user'            => $activity->user_display_name,
			'course'          => $activity->activity_course_title,
			'lesson'          => $activity->post_title,
			'start_date'      => $activity->activity_started_formatted,
			'completion_date' => $this->completionDate($activity),
			'updated_date'    => $this->updatedDate($activity),
			'time_spent'      => $this->timeSpent($activity),
		];
	}
}
