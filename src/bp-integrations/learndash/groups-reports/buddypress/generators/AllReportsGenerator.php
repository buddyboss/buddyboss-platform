<?php

namespace Buddyboss\LearndashIntegration\Buddypress\Generators;

use Buddyboss\LearndashIntegration\Buddypress\ReportsGenerator;

class AllReportsGenerator extends ReportsGenerator
{
	protected function columns()
	{
		return [
			'user'            => $this->column('user'),
			'course'          => $this->column('course'),
			'step'            => $this->column('step'),
			'start_date'      => $this->column('start_date'),
			'completion_date' => $this->column('completion_date'),
			'updated_date'    => $this->column('updated_date'),
			'time_spent'      => $this->column('time_spent'),
			'points'          => $this->column('points'),
		];
	}

	protected function formatData($activity)
	{
		// print_r($activity);
		return [
			'user'            => $activity->user_display_name,
			'course'          => $activity->activity_course_title,
			'step'            => $this->activityStepLabel($activity),
			'start_date'      => $activity->activity_started_formatted,
			'completion_date' => $this->completionDate($activity),
			'updated_date'    => $this->updatedDate($activity),
			'time_spent'      => $this->timeSpent($activity),
			'points'          => $this->coursePointsEarned($activity)
		];
	}

	protected function activityStepLabel($activity)
	{
		return get_post_type_object(learndash_get_post_type_slug($activity->activity_type))->labels->singular_name;
	}
}
