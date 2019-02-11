<?php

namespace Buddyboss\LearndashIntegration\Buddypress\Generators;

use Buddyboss\LearndashIntegration\Library\ReportsGenerator;

class TopicsReportsGenerator extends ReportsGenerator
{
	public function __construct()
	{
		$this->completed_table_title = __('Completed Topics', 'buddyboss');
		$this->incompleted_table_title = __('Incomplete Topics', 'buddyboss');

		parent::__construct();
	}

	protected function columns()
	{
		return [
			'user_id'         => $this->column('user_id'),
			'user'            => $this->column('user'),
			'course_id'       => $this->column('course_id'),
			'course'          => $this->column('course'),
			'topic'           => [
				'label'     => __( 'Topic', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'post_title',
			],
			'start_date'      => $this->column('start_date'),
			'completion_date' => $this->column('completion_date'),
			'time_spent'      => $this->column('time_spent'),
		];
	}

	protected function formatData($activity)
	{
		return [
			'user_id'         => $activity->user_id,
			'user'            => $activity->user_display_name,
			'course_id'       => $activity->activity_course_id,
			'course'          => $activity->activity_course_title,
			'topic'           => $activity->post_title,
			'start_date'      => $activity->activity_started_formatted,
			'completion_date' => $this->completionDate($activity),
			'time_spent'      => $this->timeSpent($activity),
		];
	}
}
