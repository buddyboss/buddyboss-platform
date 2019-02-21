<?php

namespace Buddyboss\LearndashIntegration\Buddypress;

use Buddyboss\LearndashIntegration\Buddypress\Generators\AllReportsGenerator;
use Buddyboss\LearndashIntegration\Buddypress\Generators\AssignmentsReportsGenerator;
use Buddyboss\LearndashIntegration\Buddypress\Generators\CoursesReportsGenerator;
use Buddyboss\LearndashIntegration\Buddypress\Generators\EssaysReportsGenerator;
use Buddyboss\LearndashIntegration\Buddypress\Generators\LessonsReportsGenerator;
use Buddyboss\LearndashIntegration\Buddypress\Generators\QuizzesReportsGenerator;
use Buddyboss\LearndashIntegration\Buddypress\Generators\TopicsReportsGenerator;

class Reports
{
	protected $isRealJoins = false;

	public function __construct()
	{
		add_action('bp_ld_sync/init', [$this, 'init']);
	}

	public function init()
	{
		if (! $this->reportTabIsVisible()) {
			return;
		}

		add_action('bp_enqueue_scripts', [$this, 'registerReportsScript']);
		add_filter('bp_ld_sync/group_tab_subnavs', [$this, 'addReportSubMenu']);
		add_action('bp_ld_sync/reports', [$this, 'showReportFilters'], 10);
		add_action('bp_ld_sync/reports', [$this, 'showReportUserStats'], 20);
		add_action('bp_ld_sync/reports', [$this, 'showReportCourseStats'], 20);
		add_action('bp_ld_sync/reports', [$this, 'showReportTables'], 30);
		add_action('bp_ld_sync/reports', [$this, 'showReportExport'], 40);

		add_filter('learndash_user_activity_query_fields', [$this, 'reportAdditionalActivityFields'], 10, 2);
		add_filter('learndash_user_activity_query_tables', [$this, 'reportAdditionalActivityTables'], 10, 2);
		add_filter('learndash_user_activity_query_where', [$this, 'reportAdditionalActivityWheres'], 10, 2);
		add_filter('learndash_user_activity_query_where', [$this, 'reportAdditionalActivityGroups'], 15, 2);

		add_filter('bp_ld_sync/report_columns', [$this, 'removeUserColumnIfSelected'], 10, 2);
		add_filter('bp_ld_sync/report_columns', [$this, 'removeCourseColumnIfSelected'], 10, 2);
		add_filter('bp_ld_sync/report_columns', [$this, 'removePointsColumnIfNotAssigned'], 10, 2);
	}

	public function registerReportsScript()
	{
		if (! bp_is_groups_component() || ! bp_is_action_variable('reports')) {
			return;
		}

		wp_enqueue_script('bp-ld-reports-datatable', '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js', ['jquery'], false, true);
		wp_enqueue_style('bp-ld-reports-datatable', '//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css', [], false);

		wp_enqueue_script(
			'bp-ld-reports',
			bp_learndash_url($filePath = '/assets/scripts/bp-learndash.js'),
			['jquery', 'bp-ld-reports-datatable'],
			filemtime(bp_learndash_path($filePath)),
			true
		);

		wp_localize_script('bp-ld-reports', 'BP_LD_REPORTS_DATA', [
			'current_group' => groups_get_current_group()->id,
			'nonce'         => wp_create_nonce('bp_ld_report'),
			'ajax_url'      => admin_url('admin-ajax.php'),
			'table_columns' => $this->getCurrentTableColumns(),
			'config' => [
				'perpage' => bp_ld_sync('settings')->get('reports.per_page', 20)
			],
			'text' => [
				'processing'     => __('Loading&hellip;', 'buddyboss'),
				'emptyTable'     => __('No result found&hellip;', 'buddyboss'),
				'paginate_first' => __('First', 'buddyboss'),
				'paginate_last'  => __('Last', 'buddyboss'),
				'paginate_next'  => __('Next', 'buddyboss'),
				'export_failed'  => __('Export failed, please refresh and try again.', 'buddyboss'),
				'export_ready'   => __('Export is ready.', 'buddyboss'),
			]
		]);

		wp_enqueue_style(
			'bp-ld-reports',
			bp_learndash_url($filePath = '/assets/styles/bp-learndash.css'),
			[],
			filemtime(bp_learndash_path($filePath))
		);
	}

	public function addReportSubMenu($subMenus)
	{
		$subMenus['reports'] = [
			'name'     => __('Reports', 'buddyboss'),
			'slug'     => 'reports',
			'position' => 20
		];

		return $subMenus;
	}

	public function showReportFilters()
	{
		$filters = $this->getReportFilters();
		require bp_locate_template('groups/single/courses-reports-filters.php', false, false);
	}

	public function showReportUserStats()
	{
		if (empty($_GET['user'])) {
			return;
		}


		$courseId = bp_ld_sync()->getRequest('course');
		$course   = $courseId? get_post($courseId) : null;
		$group    = groups_get_current_group();
		$user     = get_user_by('ID', $_GET['user']);

		require bp_locate_template('groups/single/courses-reports-user-stats.php', false, false);
	}

	public function showReportCourseStats()
	{
		if (! empty($_GET['user']) || empty($_GET['course'])) {
			return;
		}

		$course       = get_post($_GET['course']);
		$group        = groups_get_current_group();
		$ldGroupId    = bp_ld_sync('buddypress')->helpers->getLearndashGroupId($group->id);
		$ldGroup      = get_post($ldGroupId);
		$ldGroupUsers = learndash_get_groups_users($ldGroupId);
		$ldGroupUsersCompleted = array_filter($ldGroupUsers, function($user) use ($course) {
			return learndash_course_completed($user->ID, $course->ID);
		});
		$courseHasPoints = !! $coursePoints = get_post_meta($course->ID, 'course_points', true);
		$averagePoints = $courseHasPoints? count($ldGroupUsersCompleted) * $coursePoints : 0;

		require bp_locate_template('groups/single/courses-reports-course-stats.php', false, false);
	}

	public function showReportTables()
	{
		$generator = $this->getCurrentGenerator();
		$completed_table_title = $generator->completed_table_title ?: __('Completed', 'buddyboss');
		$incompleted_table_title = $generator->incompleted_table_title ?: __('Incomplete', 'buddyboss');
		require bp_locate_template('groups/single/courses-reports-tables.php', false, false);
	}

	public function showReportExport()
	{
		require bp_locate_template('groups/single/courses-reports-export.php', false, false);
	}

	public function reportAdditionalActivityFields($strFields, $queryArgs)
	{
		return apply_filters('bp_ld_sync/reports/activity_fields', $strFields, $queryArgs);
	}

	public function reportAdditionalActivityTables($strJoins, $queryArgs)
	{
		// Learndash Bug https://screencast.com/t/iBajWvdt
		if (! $this->isRealJoins()) {
			$this->isRealJoins = true;
			return $strJoins;
		}

		return apply_filters('bp_ld_sync/reports/activity_joins', $strJoins, $queryArgs);
	}

	public function reportAdditionalActivityWheres($strWheres, $queryArgs)
	{
		return apply_filters('bp_ld_sync/reports/activity_wheres', $strWheres, $queryArgs);
	}

	public function reportAdditionalActivityGroups($strWheres, $queryArgs)
	{
		return apply_filters('bp_ld_sync/reports/activity_groups', $strWheres, $queryArgs);
	}

	public function removeUserColumnIfSelected($columns, $args)
	{
		if ($args['user']) {
			unset($columns['user']);
		}

		return $columns;
	}

	public function removeCourseColumnIfSelected($columns, $args)
	{
		if ($args['course']) {
			unset($columns['course']);
		}

		return $columns;
	}

	public function removePointsColumnIfNotAssigned($columns, $args)
	{
		$shouldRemove = false;

		if ($args['course']) {
			$shouldRemove = '' === get_post_meta($args['course'], 'course_points', true);
		} else {
			$groupCourses = bp_ld_sync('buddypress')->courses->getGroupCourses($args['group']);
			$shouldRemove = array_sum(array_map(function($course) use ($args) {
				return get_post_meta($args['course'], 'course_points', true) ?: 0;
			}, $groupCourses)) > 0;
		}

		if (! in_array($args['step'], ['all', learndash_get_post_type_slug('course')])) {
			unset($columns['points']);
		}

		if ($shouldRemove) {
			unset($columns['points']);
		}

		return $columns;
	}

	public function getGenerators()
	{
		return apply_filters('bp_ld_sync/reports_generators', [
			'all' => [
				'name'  => __('All Steps', 'buddyboss'),
				'class' => AllReportsGenerator::class
			],
			learndash_get_post_type_slug('course') => [
				'name'  => __('Courses', 'buddyboss'),
				'class' => CoursesReportsGenerator::class
			],
			learndash_get_post_type_slug('lesson') => [
				'name'  => __('Lessons', 'buddyboss'),
				'class' => LessonsReportsGenerator::class
			],
			learndash_get_post_type_slug('topic') => [
				'name'  => __('Topics', 'buddyboss'),
				'class' => TopicsReportsGenerator::class
			],
			learndash_get_post_type_slug('quiz') => [
				'name'  => __('Quizzes', 'buddyboss'),
				'class' => QuizzesReportsGenerator::class
			],
			learndash_get_post_type_slug('essays') => [
				'name'  => __('Essays', 'buddyboss'),
				'class' => EssaysReportsGenerator::class
			],
			learndash_get_post_type_slug('assignment') => [
				'name'  => __('Assignments', 'buddyboss'),
				'class' => AssignmentsReportsGenerator::class
			],
		]);
	}

	protected function reportTabIsVisible()
	{
		if (! bp_ld_sync('settings')->get('reports.enabled')) {
			return false;
		}

		if (! $currentGroup = groups_get_current_group()) {
			return false;
		}

		// admin can always view
		if (learndash_is_admin_user()) {
			return true;
		}

		foreach (bp_ld_sync('settings')->get('reports.access', []) as $type) {
			$function = "groups_is_user_{$type}";
			if (function_exists($function) && call_user_func_array($function, [bp_loggedin_user_id(), $currentGroup->id])) {
				return true;
			}
		}

		return false;
	}

	protected function getReportFilters()
	{
    	$filters = apply_filters('bp_ld_sync/report_filters', [
    		'user' => [
				'name'     => __('User', 'buddyboss'),
				'position' => 10,
				'options'  => $this->getGroupUsersList()
    		],
    		'course' => [
				'name'     => __('Course', 'buddyboss'),
				'position' => 20,
				'options'  => $this->getGroupCoursesList()
    		],
    		'step' => [
				'name'     => __('Step', 'buddyboss'),
				'position' => 30,
				'options'  => $this->getStepTypes()
    		],
    	]);

    	return wp_list_sort($filters, 'position', 'ASC', true);
	}

	protected function getGroupUsersList()
	{
		$generator = bp_ld_sync('buddypress')->sync->generator(groups_get_current_group()->id);
		$members = learndash_get_groups_users($generator->getLdGroupId());

		array_unshift($members, (object) [
			'ID' => '',
			'display_name' => __('All Students', 'buddyboss')
		]);

		return wp_list_pluck($members, 'display_name', 'ID');
	}

	protected function getGroupCoursesList() {
		$ldGroupId = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( groups_get_current_group()->id );
		$courseIds = learndash_group_enrolled_courses( $ldGroupId );

		/**
		 * Filter to update course lists
		 */
		$courses = array_map( 'get_post', apply_filters( 'bp_ld_learndash_group_enrolled_courses', $courseIds, $ldGroupId ) );

		array_unshift( $courses, (object) [
			'ID'         => '',
			'post_title' => __( 'All Courses', 'buddyboss' )
		] );

		return wp_list_pluck( $courses, 'post_title', 'ID' );
	}

	protected function getStepTypes()
	{
		return wp_list_pluck($this->getGenerators(), 'name');
	}

	protected function getCurrentTableColumns()
	{
		return array_map([$this, 'getGeneratorColumns'], $this->getGenerators());
	}

	protected function getCurrentGenerator()
	{
		$step = bp_ld_sync()->getRequest('step', 'all');
		$generator = $this->getGenerators()[$step];
		return new $generator['class'];
	}

	protected function getGeneratorColumns($generator)
	{
		$columns = (new $generator['class'])->getColumns();

		return array_map([$this, 'standarlizeGeneratorColumns'], $columns, array_keys($columns));
	}

	protected function standarlizeGeneratorColumns($column, $key)
	{
		return [
			'title'     => $column['label'],
			'data'      => $key,
			'name'      => $key,
			'orderable' => $column['sortable']
		];
	}

	protected function isRealJoins()
	{
		if (in_array(current_filter(), ['learndash_user_activity_query_joins', 'learndash_user_activity_query_join'])) {
			return true;
		}

		return $this->isRealJoins;
	}
}
