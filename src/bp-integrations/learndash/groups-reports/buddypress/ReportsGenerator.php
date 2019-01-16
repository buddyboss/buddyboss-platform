<?php

namespace Buddyboss\LearndashIntegration\Buddypress;

class ReportsGenerator
{
	protected $activityQuery = null;
	protected $results  = [];
	protected $pager    = [];
	protected $params   = [];
	protected $defaults = [
		'user'        => null,
		'step'        => 'all',
		'course'      => null,
		'group'       => null,
		'completed'   => true,
		'order'       => null,
		'page'        => 1,
		'per_page'    => 10,
		'date_format' => 'Y-m-d',
	];

	public function __construct()
	{
		$this->args = wp_parse_args(bp_ld_sync()->getRequest(), $this->defaults);
		$this->setupParams();

		$this->includeCourseTitle();
		$this->includeCourseTimeSpent();
	}

	public function getColumns()
	{
		$columns = array_map(function($column) {
			$column = apply_filters('bp_ld_sync/report_column', $column, $this->args);
			return apply_filters("bp_ld_sync/report_column/step={$this->args['step']}", $column, $this->args);
		}, $this->columns());

		$columns = apply_filters('bp_ld_sync/report_columns', $columns, $this->args);
		return apply_filters("bp_ld_sync/report_columns/step={$this->args['step']}", $columns, $this->args);
	}

	public function getData()
	{
		$results = array_map([$this, 'formatData'], $this->results);

		$results = array_map(function($result) {
			$result = apply_filters('bp_ld_sync/report_data', $result, $this->args);
			return apply_filters("bp_ld_sync/report_data/step={$this->args['step']}", $result, $this->args);
		}, $results);

		$results = apply_filters('bp_ld_sync/report_datas', $results, $this->args);
		return apply_filters("bp_ld_sync/report_datas/step={$this->args['step']}", $results, $this->args);
	}

	public function getPager()
	{
		return $this->pager;
	}

	public function fetch()
	{
		$this->activityQuery = learndash_reports_get_activity($this->params, $this->args['user']);
		// print_r($this->activityQuery);die();
		$this->results = $this->activityQuery['results'];
		$this->pager = $this->activityQuery['pager'];
	}

	protected function columns()
	{
		return [];
	}

	protected function formatData($activity)
	{
		return $activity;
	}

	protected function includeCourseTitle()
	{
		add_filter('bp_ld_sync/reports/activity_fields', [$this, 'addCourseTitleActivityFields'], 10, 2);
		add_filter('bp_ld_sync/reports/activity_joins', [$this, 'addCourseTitleActivityTables'], 10, 2);
	}

	public function addCourseTitleActivityFields($strFields, $queryArgs)
	{
		return $strFields .= ', courses.post_title as activity_course_title';
	}

	public function addCourseTitleActivityTables($strJoins, $queryArgs)
	{
		global $wpdb;

		return $strJoins .= " LEFT JOIN {$wpdb->posts} as courses ON courses.ID=ld_user_activity.course_id ";
	}

	protected function includeCourseTimeSpent()
	{
		add_filter('bp_ld_sync/reports/activity_fields', [$this, 'addCourseTimeSpentActivityFields'], 10, 2);
	}

	protected function excludeCourseTimeSpent()
	{
		remove_filter('bp_ld_sync/reports/activity_fields', [$this, 'addCourseTimeSpentActivityFields'], 10, 2);
	}

	public function addCourseTimeSpentActivityFields($strFields, $queryArgs)
	{
		return $strFields .= ', IF(activity_status = 1, activity_completed - activity_started, 0) as activity_time_spent';
	}

	protected function column($name)
	{
		$builtInColumns = [
			'course' => [
				'label'     => __( 'Course', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'activity_course_title',
			],
			'user' => [
				'label'     => __( 'User', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'user_display_name',
			],
			'step' => [
				'label'     => __( 'Step', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'post_type',
			],
			'start_date' => [
				'label'     => __( 'Start Date', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'activity_started',
			],
			'completion_date' => [
				'label'     => __( 'Completion Date', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'activity_completed',
			],
			'updated_date' => [
				'label'     => __( 'Updated Date', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'activity_updated',
			],
			'time_spent' => [
				'label'     => __( 'Time Spent', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'activity_time_spent',
			],
			'points' => [
				'label'     => __( 'Points Earned', 'buddyboss' ),
				'sortable'  => false,
				'order_key' => '',
			],
		];

		return $builtInColumns[$name];
	}

	protected function setupParams()
	{
		$this->params['group_ids'] = $this->args['group'];

		if ($this->hasArg('date_format')) {
			$this->params['date_format'] = $this->args['date_format'] ?: 'Y-m-d';
		}

		if ($this->hasArg('course')) {
			$this->params['course_ids'] = ! $this->args['course']? '' : $this->args['course'];
		}

		if ($this->hasArg('step')) {
			$this->params['post_types'] = $this->args['step'] == 'all'? '' : $this->args['step'];
		}

		if ($this->hasArg('user')) {
			$this->params['user_ids'] = $this->args['user'] == 'all'? '' : $this->args['user'];
		}

		if ($this->hasArg('completed')) {
			$this->params['activity_status'] = $this->args['completed']? 'COMPLETED' : 'IN_PROGRESS';
		}

		if ($this->hasArg('order')) {
			$columns = $this->columns();
			$columnIndex = $this->args['order'][0]['column'];
			$column = $columns[$this->args['columns'][$columnIndex]['name']];

			$oldOrder = isset($this->params['orderby_order'])? ", {$this->params['orderby_order']}" : '';
			$this->params['orderby_order'] = "{$column['order_key']} {$this->args['order'][0]['dir']} {$oldOrder}";
		}

		if ($this->hasArg('start')) {
			$this->params['paged'] = $this->args['start'] / $this->args['length'] + 1;
		}

		if ($this->hasArg('length')) {
			$this->params['per_page'] = $this->args['length'];
		}

		$this->params = apply_filters('bp_ld_sync/reports_generator_args', $this->params, $this->args);
	}

	protected function hasArg($key)
	{
		return isset($this->args[$key]) && ! is_null($this->args[$key]);
	}

	protected function timeSpent($activity)
	{
		$seconds = intval($activity->activity_time_spent);

		if ($seconds < 60) {
			return sprintf('%ds', $seconds);
		}

		$minutes = floor($seconds/60);
		$seconds = $seconds % 60;

		if ($minutes < 60) {
			return sprintf(
				'%d%s',
				$minutes,
				_n('min', 'mins', $minutes, 'buddyboss')
			);
		}

		$hours = floor($minutes / 60 * 10) / 10;

		if ($hours < 24) {
			return sprintf(
				'%d %s',
				$hours,
				_n('hr', 'hrs', $hours, 'buddyboss')
			);
		}
	}

	protected function completionDate($activity)
	{
		return $activity->activity_completed? $activity->activity_completed_formatted : '-';
	}

	protected function updatedDate($activity)
	{
		return $activity->activity_completed? '-' : $activity->activity_updated_formatted;
	}

	protected function coursePointsEarned($activity)
	{
		if ($activity->activity_type !== 'course') {
			return '-';
		}

		return $activity->activity_status? get_post_meta($activity->activity_course_id, 'course_points', true) : '0';
	}
}
