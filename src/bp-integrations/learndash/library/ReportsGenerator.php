<?php
/**
 * @todo add description
 * 
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */ 

namespace Buddyboss\LearndashIntegration\Library;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * @todo add title/description
 * 
 * @since BuddyBoss 1.0.0
 */
class ReportsGenerator
{
	public $completed_table_title;
	public $incompleted_table_title;

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

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct()
	{
		$this->args = apply_filters(
			'bp_ld_sync/reports_generator_args',
			wp_parse_args(bp_ld_sync()->getRequest(), $this->defaults)
		);

		if ($_POST) {
			$this->setupParams();

			$this->includeCourseTitle();
			$this->includeCourseTimeSpent();
		}
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getColumns()
	{
		$columns = array_map(function($column) {
			$column = apply_filters('bp_ld_sync/report_column', $column, $this->args);
			return apply_filters("bp_ld_sync/report_column/step={$this->args['step']}", $column, $this->args);
		}, $this->columns());

		$columns = apply_filters('bp_ld_sync/report_columns', $columns, $this->args);
		return apply_filters("bp_ld_sync/report_columns/step={$this->args['step']}", $columns, $this->args);
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getData()
	{
		$results = array_map([$this, 'formatData'], $this->results);

		if ($this->hasArg('display') && $this->args['display']) {
			$results = array_map([$this, 'formatDataForDisplay'], $results, $this->results);
		}

		if ($this->hasArg('export') && $this->args['export']) {
			$results = array_map([$this, 'formatDataForExport'], $results, $this->results);
		}

		$results = array_map(function($result) {
			$result = apply_filters('bp_ld_sync/report_data', $result, $this->args);
			return apply_filters("bp_ld_sync/report_data/step={$this->args['step']}", $result, $this->args);
		}, $results);

		$results = apply_filters('bp_ld_sync/report_datas', $results, $this->args);
		return apply_filters("bp_ld_sync/report_datas/step={$this->args['step']}", $results, $this->args);
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getPager()
	{
		return $this->pager;
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function fetch()
	{
		$this->activityQuery = learndash_reports_get_activity($this->params, $this->args['user']);
		// print_r($this->activityQuery);die();
		$this->results = $this->activityQuery['results'];
		$this->pager = $this->activityQuery['pager'];
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function export()
	{
		$hash    = $this->hasArg('hash')? $this->args['hash'] : md5(microtime());
		$exports = get_transient($hash) ?: [];
		$columns = apply_filters('bp_ld_sync/export_report_column', $this->columns(), $this, $this->args);
		$data = apply_filters('bp_ld_sync/export_report_data', $this->getData(), $this, $this->args);

    	foreach ($data as $result) {
    		$data = [];

    		foreach (array_keys($columns) as $key) {
    			$data[$key] = $result[$key];
    		}

    		$exports[] = $data;
    	}

		set_transient($hash, $exports, HOUR_IN_SECONDS);
		set_transient("{$hash}_info", [
			'filename' => 'learndash-report-export-group-' . $this->args['group'] . '.csv',
			'columns' => $columns
		], HOUR_IN_SECONDS);

		wp_send_json_success([
			'page'     => $this->args['start'] / $this->args['length'] + 1,
			'total'    => $this->pager['total_pages'],
			'has_more' => $this->pager['total_pages'] > $this->args['start'] / $this->args['length'] + 1,
			'url'      => add_query_arg([
				'hash'   => $hash,
				'action' => 'download_bp_ld_reports',
			], admin_url('admin-ajax.php')),
			'hash'     => $hash
		]);
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function columns()
	{
		return [];
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function formatData($activity)
	{
		return $activity;
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function formatDataForDisplay($data, $activity)
	{
		return wp_parse_args([
			'course' => sprintf(
				'<a href="%s" target="_blank">%s</a>',
				get_permalink($activity->activity_course_id),
				$activity->activity_course_title
			)
		], $data);
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function formatDataForExport($data, $activity)
	{
		return $data;
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function includeCourseTitle()
	{
		add_filter('bp_ld_sync/reports/activity_fields', [$this, 'addCourseTitleActivityFields'], 10, 2);
		add_filter('bp_ld_sync/reports/activity_joins', [$this, 'addCourseTitleActivityTables'], 10, 2);
		add_filter('bp_ld_sync/reports/activity_wheres', [$this, 'addCourseTitleActivityWhere'], 10, 2);
		add_filter('bp_ld_sync/reports/activity_groups', [$this, 'addCourseTitleActivityGroup'], 10, 2);
		add_filter('learndash_user_activity_query_str', [$this, 'maybeAddActivityGroupBy'], 10, 2);
	}


	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function addCourseTitleActivityFields($strFields, $queryArgs)
	{
		return $strFields .= ', courses.post_title as activity_course_title';
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function addCourseTitleActivityTables($strJoins, $queryArgs)
	{
		global $wpdb;

		return $strJoins .= " LEFT JOIN {$wpdb->posts} as courses ON courses.ID=ld_user_activity.course_id ";
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function addCourseTitleActivityWhere($strWheres, $queryArgs)
	{
		return $strWheres .= " AND activity_id IS NOT NULL ";
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function addCourseTitleActivityGroup($strWheres, $queryArgs)
	{
		return $strWheres .= " AND 2=2 "; // we gonna conditionaly replace this for group_by
		// return $strWheres .= " GROUP BY activity_id ";
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function maybeAddActivityGroupBy($strSql, $queryArgs)
	{
		return str_replace('AND 2=2', 'GROUP BY activity_id', $strSql);
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function includeCourseTimeSpent()
	{
		add_filter('bp_ld_sync/reports/activity_fields', [$this, 'addCourseTimeSpentActivityFields'], 10, 2);
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function excludeCourseTimeSpent()
	{
		remove_filter('bp_ld_sync/reports/activity_fields', [$this, 'addCourseTimeSpentActivityFields'], 10, 2);
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function addCourseTimeSpentActivityFields($strFields, $queryArgs)
	{
		return $strFields .= ', IF(activity_status = 1, activity_completed - activity_started, 0) as activity_time_spent';
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function column($name)
	{
		$builtInColumns = [
			'course_id' => [
				'label'     => __( 'Course ID', 'buddyboss' ),
				'sortable'  => false,
				'order_key' => '',
			],
			'course' => [
				'label'     => __( 'Course', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'activity_course_title',
			],
			'user_id' => [
				'label'     => __( 'User ID', 'buddyboss' ),
				'sortable'  => false,
				'order_key' => '',
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
			'status' => [
				'label'     => __( 'Status', 'buddyboss' ),
				'sortable'  => false,
				'order_key' => '',
			],
		];

		return $builtInColumns[$name];
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function setupParams()
	{
		// includes/ld-reports.php:882 learndash_reports_get_activity only allowd leader if group_id is passed
		// $this->params['group_ids'] = $this->args['group'];
		$ldGroupId = bp_ld_sync('buddypress')->sync->generator($this->args['group'])->getLdGroupId();

		if ($this->hasArg('date_format')) {
			$this->params['date_format'] = $this->args['date_format'] ?: 'Y-m-d';
		}


		// if ($this->hasArg('course')) {
			$this->params['course_ids'] = learndash_group_enrolled_courses($ldGroupId);
//		var_dump( learndash_group_enrolled_courses($ldGroupId) );
//		var_dump( $this->args['course'] );
		// }

		if ($this->hasArg('step')) {
			$this->params['post_types'] = $this->args['step'] == 'all'? $this->allSteps() : $this->args['step'];
		}

		// if ($this->hasArg('user')) {
			$this->params['user_ids'] = $this->args['user'] ?: learndash_get_groups_user_ids($ldGroupId);
		// }

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
// print_r($this->params);die();
		$this->params = apply_filters('bp_ld_sync/reports_generator_params', $this->params, $this->args);
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function hasArg($key)
	{
		return isset($this->args[$key]) && ! is_null($this->args[$key]);
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function allSteps()
	{
		global $learndash_post_types;
// var_dump(array_diff($learndash_post_types, ['groups']));
		return array_diff($learndash_post_types, ['groups']);
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
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

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function completionDate($activity)
	{
		return $activity->activity_completed? $activity->activity_completed_formatted : '-';
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function updatedDate($activity)
	{
		return $activity->activity_completed? '-' : $activity->activity_updated_formatted;
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function coursePointsEarned($activity)
	{
		if ($activity->activity_type !== 'course') {
			return '-';
		}

		return $activity->activity_status? get_post_meta($activity->activity_course_id, 'course_points', true) : '0';
	}
}
