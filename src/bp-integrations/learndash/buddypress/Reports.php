<?php
/**
 * BuddyBoss LearnDash integration reports class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Buddypress;

use Buddyboss\LearndashIntegration\Buddypress\Generators\AllReportsGenerator;
use Buddyboss\LearndashIntegration\Buddypress\Generators\AssignmentsReportsGenerator;
use Buddyboss\LearndashIntegration\Buddypress\Generators\CoursesReportsGenerator;
use Buddyboss\LearndashIntegration\Buddypress\Generators\EssaysReportsGenerator;
use Buddyboss\LearndashIntegration\Buddypress\Generators\LessonsReportsGenerator;
use Buddyboss\LearndashIntegration\Buddypress\Generators\QuizzesReportsGenerator;
use Buddyboss\LearndashIntegration\Buddypress\Generators\TopicsReportsGenerator;
use Buddyboss\LearndashIntegration\Buddypress\ReportsGenerator;
use LDLMS_DB;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class for all reports related functions
 *
 * @since BuddyBoss 1.0.0
 */
class Reports {

	protected $isRealJoins = false;
	protected $defaults    = [
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
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		$this->args = apply_filters(
			'bp_ld_sync/reports_generator_args',
			wp_parse_args( $this->getRequest(), $this->defaults )
		);
		add_action( 'bp_ld_sync/init', [ $this, 'init' ] );
	}

	/**
	 * Get the request from $_POST, $_GET, or $_REQUEST with default fallback
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getRequest( $key = '*', $default = null, $type = null ) {
		if ( $type ) {
			return $key == '*' ? $$type : ( isset( $$type[ $key ] ) ? $$type[ $key ] : $default );
		}

		$merged = array_merge( $_GET, $_POST, $_REQUEST );
		return $key == '*' ? $merged : ( isset( $merged[ $key ] ) ? $merged[ $key ] : $default );
	}

	/**
	 * Add actions once integration is ready
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function init() {
		add_action( 'bp_enqueue_scripts', array( $this, 'registerReportsScript' ) );

		add_action( 'wp_head', array( $this, 'bp_ld_reports_inline_css' ) );

		// add plugable templates to report actions
		add_action( 'bp_ld_sync/reports', array( $this, 'showReportFilters' ), 10 );
		add_action( 'bp_ld_sync/reports', array( $this, 'showReportUserStats' ), 20 );
		add_action( 'bp_ld_sync/reports', array( $this, 'showReportCourseStats' ), 20 );
		add_action( 'bp_ld_sync/reports', array( $this, 'showReportTables' ), 30 );
		//add_action( 'bp_ld_sync/reports', array( $this, 'showReportExport' ), 40 );

		//add_filter('bp_ld_sync/reports_generator_params', [$this, 'forceOwnReportResults'], 99);
		//add_filter('bp_ld_sync/reports_generator_params', [$this, 'courseReportResults'], 99);

		add_filter( 'bp_ld_sync/report_filters', array( $this, 'removeCourseFilterIfOnlyOne' ) );
		add_filter( 'bp_ld_sync/report_filters', array( $this, 'removeUserFilterIfStudent' ) );

		add_filter( 'learndash_user_activity_query_fields', array( $this, 'reportAdditionalActivityFields' ), 10, 2 );
		add_filter( 'learndash_user_activity_query_tables', array( $this, 'reportAdditionalActivityTables' ), 10, 2 );
		add_filter( 'learndash_user_activity_query_where', array( $this, 'reportAdditionalActivityWheres' ), 10, 2 );
		add_filter( 'learndash_user_activity_query_where', array( $this, 'reportAdditionalActivityGroups' ), 15, 2 );

		add_filter( 'bp_ld_sync/report_columns', array( $this, 'removeUserColumnIfSelected' ), 10, 2 );
		add_filter( 'bp_ld_sync/report_columns', array( $this, 'removeCourseColumnIfSelected' ), 10, 2 );
		add_filter( 'bp_ld_sync/report_columns', array( $this, 'removePointsColumnIfNotAssigned' ), 10, 2 );

		add_action( 'bp_ld_sync/export_report_column', array( $this, 'export_report_column' ), 10, 2 );
	}

	/**
	 * Check if the given argument is passed from request
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function hasArg( $key ) {
		return isset( $this->args[ $key ] ) && ! is_null( $this->args[ $key ] );
	}

	public function bp_ld_reports_inline_css() {
		if ( function_exists( 'buddyboss_theme_get_option' ) ) {
			?>
			<style type="text/css">
			table.dataTable thead .sorting_desc, table.dataTable thead .sorting_asc {
				color: <?php echo buddyboss_theme_get_option( 'alternate_link_active' ); ?>;
			}
		</style>
			<?php
		} else {
			?>
			<style type="text/css">
			table.dataTable thead .sorting_desc, table.dataTable thead .sorting_asc {
				color: #000;
			}
		</style>
			<?php
		}
	}

	/**
	 * Add scripts when it's on the reports page
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function registerReportsScript() {
		if ( ! bp_is_groups_component() || ! bp_is_current_action( 'reports' ) ) {
			return;
		}

		wp_enqueue_script( 'bp-ld-reports-datatable', '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js', array( 'jquery' ), false, true );
		wp_enqueue_style( 'bp-ld-reports-datatable', '//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css', array(), false );

		wp_enqueue_script(
			'bp-ld-reports-progressbar',
			bp_learndash_url( $filePath = '/assets/scripts/bp-ld-reports-progressbar.js' ),
			[ 'jquery' ],
			filemtime( bp_learndash_path( $filePath ) ),
			true
		);

		wp_enqueue_script(
			'bp-ld-reports',
			bp_learndash_url( $filePath = '/assets/scripts/bp-learndash.js' ),
			[ 'jquery', 'bp-ld-reports-datatable' ],
			filemtime( bp_learndash_path( $filePath ) ),
			true
		);

		$per_page = bp_ld_sync( 'settings' )->get( 'reports.per_page', 20 );
		wp_localize_script(
			'bp-ld-reports',
			'BP_LD_REPORTS_DATA',
			array(
				'current_group' => groups_get_current_group()->id,
				'nonce'         => wp_create_nonce( 'bp_ld_report' ),
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'table_columns' => $this->getCurrentTableColumns(),
				'config'        => array(
					'perpage' => ( '' === $per_page ) ? 20 : $per_page,
				),
				'text'          => array(
					'processing'     => __( 'Loading&hellip;', 'buddyboss' ),
					'emptyTable'     => __( 'No result found&hellip;', 'buddyboss' ),
					'paginate_first' => __( 'First', 'buddyboss' ),
					'paginate_last'  => __( 'Last', 'buddyboss' ),
					'paginate_next'  => __( 'Next', 'buddyboss' ),
					'export_failed'  => __( 'Export failed, please refresh and try again.', 'buddyboss' ),
					'export_ready'   => __( 'Export is ready.', 'buddyboss' ),
				),
			)
		);

		wp_enqueue_style(
			'bp-ld-reports',
			bp_learndash_url( $filePath = '/assets/styles/bp-learndash.css' ),
			array(),
			filemtime( bp_learndash_path( $filePath ) )
		);
	}

	/**
	 * Output report filters html
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function showReportFilters() {
		$filters = $this->getReportFilters();
		require bp_locate_template( 'groups/single/reports-filters.php', false, false );
	}

	/**
	 * Add additional field sql statement
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function addAdditionalFields( $strFields ) {
		global $wpdb;
		$quizPostType = learndash_get_post_type_slug( 'quiz' );

		$fields = "
			users.ID as user_id,
			users.display_name as user_display_name,
			users.user_email as user_email,
			{$wpdb->posts}.ID as assignment_id,
			{$wpdb->posts}.post_title as assignment_title,
			{$wpdb->posts}.post_date_gmt as assignment_post_date,
			{$wpdb->posts}.post_modified_gmt as assignment_modify_date,
			(
				SELECT meta_value
				FROM {$wpdb->postmeta} as course_meta
				WHERE course_meta.post_id = {$wpdb->posts}.ID
				AND course_meta.meta_key = 'course_id'
			) as activity_course_id,
			(
				SELECT post_title
				FROM {$wpdb->posts} as courses
				WHERE activity_course_id = courses.ID
			) as activity_course_title
		";

		return $fields;
	}

	/**
	 * Add additional joins sql statement
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function addAdditionalJoins( $strJoins ) {
		global $wpdb;

		$strJoins .= "
			INNER JOIN {$wpdb->users} as users ON users.ID = {$wpdb->posts}.post_author
		";

		return $strJoins;
	}

	/**
	 * Add additional order sql statement
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function addAdditionalOrderBy( $strOrder ) {
		$strOrder = 'GREATEST(assignment_modify_date, assignment_post_date) DESC';

		if ( $this->hasArg( 'order' ) ) {
			$columns     = $this->columns();
			$columnIndex = $this->args['order'][0]['column'];
			$column      = $columns[ $this->args['columns'][ $columnIndex ]['name'] ];

			$strOrder = "{$column['order_key']} {$this->args['order'][0]['dir']}, {$strOrder}";
		}

		return $strOrder;
	}

	/**
	 * Output report user stats html
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function showReportUserStats() {
		 global $wpdb;

		if ( empty( $_GET['user'] ) ) {
			return;
		}

		$group       = groups_get_current_group();
		$user        = get_user_by( 'ID', $_GET['user'] );
		$courseId    = '';
		$points      = 0;
		$complete    = 0;
		$in_complete = 0;
		$total       = 0;
		$percentage  = 0;
		$count       = 0;
		$unmarked    = 0;
		$course      = '';

		if ( $this->hasArg( 'course' ) && ! $this->args['course'] ) {
			$courseIds = learndash_group_enrolled_courses( bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( $group->id ) );
		} else {
			$courseIds = array( $this->args['course'] );
			$courseId = $this->args['course'];
			$course = get_post( $courseId );
		}

		if ( $this->hasArg( 'step' ) && ! $this->args['step'] ) {
			$step = 'all';
		} else if ( 'all' === $this->args['step'] ) {
			$step = 'all';
		} elseif ( 'sfwd-topic' === $this->args['step'] ) {
			$step = 'topic';
		} elseif ( 'sfwd-lessons' === $this->args['step'] ) {
			$step = 'lesson';
		} elseif ( 'sfwd-quiz' === $this->args['step'] ) {
			$step = 'quiz';
		} elseif ( 'sfwd-assignment' === $this->args['step'] ) {
			$step = 'assignment';
		}

		foreach ( $courseIds as $course ) {
			$sql_str = $wpdb->prepare(
				'SELECT postmeta.post_id as post_id, postmeta.meta_value as points
			FROM ' . $wpdb->postmeta . " as postmeta 
			WHERE postmeta.post_id IN 
			(
				SELECT DISTINCT REPLACE(user_meta.meta_key, 'course_completed_', '') as course_id 
				FROM " . $wpdb->usermeta . " as user_meta 
				WHERE user_meta.meta_key LIKE %s 
					AND user_meta.user_id = %d and user_meta.meta_value != ''
			) 
			AND postmeta.meta_key=%s 
			AND postmeta.meta_value != ''", 'course_completed_%', $_GET['user'], 'course_points'
			);
			$course_points_results = $wpdb->get_results( $sql_str );
			if ( $course_points_results ) {
				foreach ( $course_points_results as $single_course ) {
					if ( (int) $single_course->post_id === (int) $course ) {
						$points = $points + (int) $single_course->points;
					}
				}
			}

			$data  = bp_ld_get_course_all_steps( $course, $_GET['user'], $step );
			$steps = $data['steps'];
			$total = $total + count( $steps );
			foreach ( $steps as $single ) {
				if ( !empty( $single['activity'] ) ) {
					$points     = $points + bpLdCoursePointsEarned( $single['activity'] );
					if ( isset( $single['activity']->activity_status )  && '1' === $single['activity']->activity_status ) {
						$complete = $complete + 1;
					} else {
						$in_complete = $in_complete + 1;
					}
				} else  {
					$in_complete = $in_complete + 1;
				}
			}
		}


		if ( $total > 0 ) {
			$percentage = intval( $complete * 100 / $total );
			$percentage = ( $percentage > 100 ) ? 100 : $percentage;
		} else {
			$percentage = 0;
		}



		require bp_locate_template( 'groups/single/reports-user-stats.php', false, false );
	}

	/**
	 * Format points earned if enabled
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function coursePointsEarned( $activity ) {

		$assignments = learndash_get_user_assignments( $activity->post_id, $activity->user_id );
		if ( ! empty( $assignments ) ) {
			foreach ( $assignments as $assignment ) {
				$assignment_points = learndash_get_points_awarded_array( $assignment->ID );
				if ( $assignment_points || learndash_is_assignment_approved_by_meta( $assignment->ID ) ) {
					if ( $assignment_points ) {
						return (int) $assignment_points['current'];
					}
				}
			}
		}

		$post_settings = learndash_get_setting( $activity->post_id );

		if ( isset( $activity->post_type ) && ( 'sfwd-topic' === $activity->post_type || 'sfwd-lessons' === $activity->post_type ) ) {

			if ( 0 === $activity->activity_status ) {
				return 0;
			}

			if ( isset( $post_settings['lesson_assignment_points_enabled'] ) && 'on' === $post_settings['lesson_assignment_points_enabled'] && isset( $post_settings['lesson_assignment_points_amount'] ) && $post_settings['lesson_assignment_points_amount'] > 0 ) {
				return (int) $post_settings['lesson_assignment_points_amount'];
			} else {
				return 0;
			}
		} elseif ( isset( $activity->post_type ) && 'sfwd-courses' === $activity->post_type ) {

			if ( 0 === $activity->activity_status ) {
				return 0;
			}

			if ( isset( $post_settings['course_points_enabled'] ) && 'on' === $post_settings['course_points_enabled'] && isset( $post_settings['course_points'] ) && $post_settings['course_points'] > 0 ) {
				return (int) $post_settings['course_points'];
			} else {
				return 0;
			}
		}
		return 0;
	}

	/**
	 * Remove post ids param from sql query
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $query_args
	 *
	 * @return array $query_args
	 */
	public function remove_post_ids_param( $query_args ) {
		if ( isset( $query_args['post_ids'] ) ) {
			unset( $query_args['post_ids'] );
		}

		return $query_args;

	}

	/**
	 * Output report course stats html
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function showReportCourseStats() {
		if ( ! empty( $_GET['user'] ) || empty( $_GET['course'] ) ) {
			return;
		}

		$course                = get_post( $_GET['course'] );
		$group                 = groups_get_current_group();
		$ldGroupId             = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( $group->id );
		$ldGroup               = get_post( $ldGroupId );
		$ldGroupUsers          = learndash_get_groups_users( $ldGroupId );
		$ldGroupUsersCompleted = array_filter(
			$ldGroupUsers,
			function( $user ) use ( $course ) {
				return learndash_course_completed( $user->ID, $course->ID );
			}
		);
		$courseHasPoints       = ! ! $coursePoints = get_post_meta( $course->ID, 'course_points', true );
		$averagePoints         = $courseHasPoints ? count( $ldGroupUsersCompleted ) * $coursePoints : 0;

		//require bp_locate_template( 'groups/single/reports-course-stats.php', false, false );
	}

	/**
	 * Output report results tables
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function showReportTables() {
		$generator               = $this->getCurrentGenerator();
		$completed_table_title   = $generator->completed_table_title ?: __( 'Completed', 'buddyboss' );
		$incompleted_table_title = $generator->incompleted_table_title ?: __( 'Incomplete', 'buddyboss' );
		if ( groups_is_user_mod( bp_loggedin_user_id(), bp_get_current_group_id() ) || groups_is_user_admin( bp_loggedin_user_id(), bp_get_current_group_id() ) || bp_current_user_can( 'bp_moderate' ) ) {
			if ( empty( $_REQUEST['course'] ) && empty( $_REQUEST['user'] ) ) {
				require bp_locate_template( 'groups/single/reports-tables-all.php', false, false );
			} elseif ( ! empty( $_REQUEST['course'] ) && is_string( $_REQUEST['course'] ) && empty( $_REQUEST['user'] ) ) {
				require bp_locate_template( 'groups/single/reports-tables-all.php', false, false );
			} elseif ( empty( $_REQUEST['course'] ) && ! empty( $_REQUEST['user'] ) && ! empty( $_REQUEST['step'] ) && 'all' === $_REQUEST['step'] ) {
				require bp_locate_template( 'groups/single/reports-single-user-all-courses.php', false, false );
			} elseif ( ! empty( $_REQUEST['course'] ) && ! empty( $_REQUEST['user'] ) && ! empty( $_REQUEST['step'] ) ) {
				require bp_locate_template( 'groups/single/reports-single-admin-single-courses.php', false, false );
			} elseif ( empty( $_REQUEST['course'] ) && ! empty( $_REQUEST['user'] ) && ! empty( $_REQUEST['step'] ) ) {
				require bp_locate_template( 'groups/single/reports-single-user-all-courses.php', false, false );
			} else {
				require bp_locate_template( 'groups/single/reports-tables.php', false, false );
			}
		} else {
			if ( empty( $_REQUEST['course'] ) && empty( $_REQUEST['step'] ) ) {
				require bp_locate_template( 'groups/single/reports-single-user-all-courses.php', false, false );
			} else {
				if ( isset( $_REQUEST['step'] ) && 'all' != $_REQUEST['step'] && isset( $_REQUEST['course'] ) && '' === $_REQUEST['course'] ) {
					require bp_locate_template( 'groups/single/reports-single-user-all-courses.php', false, false );
				} elseif ( isset( $_REQUEST['step'] ) && isset( $_REQUEST['course'] ) && '' === $_REQUEST['course'] && '' === $_REQUEST['step'] ) {
					require bp_locate_template( 'groups/single/reports-single-user-all-courses.php', false, false );
				} elseif ( isset( $_REQUEST['step'] ) && isset( $_REQUEST['course'] ) && '' === $_REQUEST['course'] && 'all' === $_REQUEST['step'] ) {
					require bp_locate_template( 'groups/single/reports-single-user-all-courses.php', false, false );
				} elseif ( isset( $_REQUEST['step'] ) && isset( $_REQUEST['course'] ) && '' !== $_REQUEST['course'] ) {
					require bp_locate_template( 'groups/single/reports-single-user-single-courses.php', false, false );
					//require bp_locate_template( 'groups/single/reports-tables.php', false, false );
				} else {
					require bp_locate_template( 'groups/single/reports-tables.php', false, false );
				}
			}
		}
	}

	/**
	 * Filter to sort the report by Course
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function courseReportResults( $params ) {
		if ( ! empty( $_REQUEST['course'] ) && is_string( $_REQUEST['course'] ) ) {
			$params['course_ids'] = absint( $_REQUEST['course'] );
		}
		return $params;
	}

	/**
	 * Only allow non admin/mod to view his own reports
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function forceOwnReportResults( $params ) {
		if ( ! $currentGroup = groups_get_current_group() ) {
			return $params;
		}

		$userId  = bp_loggedin_user_id();
		$groupId = $currentGroup->id;

		if ( groups_is_user_admin( $userId, $groupId ) || groups_is_user_mod( $userId, $groupId ) || is_user_admin() ) {
			return $params;
		}

		$params['user_ids'] = array( bp_loggedin_user_id() );
		return $params;
	}

	/**
	 * Output report export button html
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function showReportExport() {
		require bp_locate_template( 'groups/single/reports-export.php', false, false );
	}

	/**
	 * Remove the filter if only 1 option available
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function removeCourseFilterIfOnlyOne( $filters ) {
		if ( ! $currentGroup = groups_get_current_group() ) {
			return $filters;
		}

		if ( count( bp_learndash_get_group_courses( $currentGroup->id ) ) < 2 ) {
			unset( $filters['course'] );
		}

		return $filters;
	}

	/**
	 * Remove user filter is nond admin/mod are viewing reports tab
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function removeUserFilterIfStudent( $filters ) {
		if ( ! $currentGroup = groups_get_current_group() ) {
			return $filters;
		}

		// admin can always view
		if ( learndash_is_admin_user() ) {
			return $filters;
		}

		$userId  = bp_loggedin_user_id();
		$groupId = $currentGroup->id;

		if ( ! groups_is_user_admin( $userId, $groupId ) && ! groups_is_user_mod( $userId, $groupId ) ) {
			unset( $filters['user'] );
		}

		return $filters;
	}

	/**
	 * Sub filter for ld's activity sql query on fields
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function reportAdditionalActivityFields( $strFields, $queryArgs ) {
		return apply_filters( 'bp_ld_sync/reports/activity_fields', $strFields, $queryArgs );
	}

	/**
	 * Sub filter for ld's activity sql query on joins
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function reportAdditionalActivityTables( $strJoins, $queryArgs ) {
		// Learndash Bug https://screencast.com/t/iBajWvdt
		if ( ! $this->isRealJoins() ) {
			$this->isRealJoins = true;
			return $strJoins;
		}

		return apply_filters( 'bp_ld_sync/reports/activity_joins', $strJoins, $queryArgs );
	}

	/**
	 * Sub filter for ld's activity sql query on wheres
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function reportAdditionalActivityWheres( $strWheres, $queryArgs ) {
		return apply_filters( 'bp_ld_sync/reports/activity_wheres', $strWheres, $queryArgs );
	}

	/**
	 * Sub filter for ld's activity sql query on group_by
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function reportAdditionalActivityGroups( $strWheres, $queryArgs ) {
		return apply_filters( 'bp_ld_sync/reports/activity_groups', $strWheres, $queryArgs );
	}

	/**
	 * Remove the user column if a user is selected in the filter
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function removeUserColumnIfSelected( $columns, $args ) {
		if ( $args['user'] ) {
			unset( $columns['user'] );
		}

		return $columns;
	}

	/**
	 * Remove the course column if a course is selected in the filter
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function removeCourseColumnIfSelected( $columns, $args ) {
		if ( $args['course'] ) {
			unset( $columns['course'] );
		}

		return $columns;
	}

	/**
	 * Remove the points column if all courses doesn't have points
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function removePointsColumnIfNotAssigned( $columns, $args ) {
		$shouldRemove = false;

		if ( $args['course'] ) {
			$shouldRemove = '' === get_post_meta( $args['course'], 'course_points', true );
		} else {
			$groupCourses = bp_ld_sync( 'buddypress' )->courses->getGroupCourses( $args['group'] );
			$shouldRemove = array_sum(
				array_map(
					function( $course ) use ( $args ) {
						return get_post_meta( $args['course'], 'course_points', true ) ?: 0;
					},
					$groupCourses
				)
			) > 0;
		}

		if ( ! in_array( $args['step'], array( 'all', learndash_get_post_type_slug( 'course' ) ) ) ) {
			unset( $columns['points'] );
		}

		if ( $shouldRemove ) {
			unset( $columns['points'] );
		}

		return $columns;
	}

	/**
	 * Add status columns to export
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function export_report_column( $columns, $report_generator ) {
		if ( ! empty( $report_generator->args['step'] ) && in_array( $report_generator->args['step'], array( 'forum' ) ) ) {
			$columns['status'] = $report_generator->column( 'status' );
		}

		return $columns;
	}

	/**
	* Get available report generators
	*
	* @since BuddyBoss 1.0.0
	*/
	public function getGenerators() {
		return apply_filters(
			'bp_ld_sync/reports_generators',
			[
				'all'                                    => [
					'name'  => __( 'All Steps', 'buddyboss' ),
					'class' => '\Buddyboss\LearndashIntegration\Buddypress\Generators\AllReportsGenerator',
				],
				//              learndash_get_post_type_slug( 'course' ) => [
				//                  'name'  => __( 'Courses', 'buddyboss' ),
				//                  'class' => 'Buddyboss\LearndashIntegration\Buddypress\Generators\CoursesReportsGenerator',
				//              ],
				learndash_get_post_type_slug( 'lesson' ) => [
					'name'  => __( 'Lessons', 'buddyboss' ),
					'class' => 'Buddyboss\LearndashIntegration\Buddypress\Generators\LessonsReportsGenerator',
				],
				learndash_get_post_type_slug( 'topic' )  => [
					'name'  => __( 'Topics', 'buddyboss' ),
					'class' => 'Buddyboss\LearndashIntegration\Buddypress\Generators\TopicsReportsGenerator',
				],
				learndash_get_post_type_slug( 'quiz' )   => [
					'name'  => __( 'Quizzes', 'buddyboss' ),
					'class' => 'Buddyboss\LearndashIntegration\Buddypress\Generators\QuizzesReportsGenerator',
				],
				learndash_get_post_type_slug( 'assignment' ) => [
					'name'  => __( 'Assignments', 'buddyboss' ),
					'class' => 'Buddyboss\LearndashIntegration\Buddypress\Generators\AssignmentsReportsGenerator',
				],
			]
		);
	}

	/**
	 * Get available report filters
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function getReportFilters() {
		$filters = apply_filters(
			'bp_ld_sync/report_filters',
			[
				'user'   => [
					'name'     => __( 'Student', 'buddyboss' ),
					'position' => 10,
					'options'  => $this->getGroupUsersList(),
				],
				'course' => [
					'name'     => __( 'Course', 'buddyboss' ),
					'position' => 20,
					'options'  => $this->getGroupCoursesList(),
				],
				'step'   => [
					'name'     => __( 'Step', 'buddyboss' ),
					'position' => 30,
					'options'  => $this->getStepTypes(),
				],
			]
		);

		return wp_list_sort( $filters, 'position', 'ASC', true );
	}

	/**
	 * Get group's member list for filter
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function getGroupUsersList() {
		$generator = bp_ld_sync( 'buddypress' )->sync->generator( groups_get_current_group()->id );
		$members   = learndash_get_groups_users( $generator->getLdGroupId() );

		array_unshift(
			$members,
			(object) array(
				'ID'           => '',
				'display_name' => __( 'All Students', 'buddyboss' ),
			)
		);

		return wp_list_pluck( $members, 'display_name', 'ID' );
	}

	/**
	 * Get group's course list for filter
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function getGroupCoursesList() {
		$ldGroupId = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( groups_get_current_group()->id );
		$courseIds = learndash_group_enrolled_courses( $ldGroupId );

		/**
		 * Filter to update course lists
		 */
		$courses = array_map( 'get_post', apply_filters( 'bp_ld_learndash_group_enrolled_courses', $courseIds, $ldGroupId ) );

		array_unshift(
			$courses,
			(object) array(
				'ID'         => '',
				'post_title' => __( 'All Courses', 'buddyboss' ),
			)
		);

		return wp_list_pluck( $courses, 'post_title', 'ID' );
	}

	/**
	 * Get list of steps available from all generators
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function getStepTypes() {
		 return wp_list_pluck( $this->getGenerators(), 'name' );
	}

	/**
	 * Get the table columns on the current generator
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function getCurrentTableColumns() {
		return array_map( array( $this, 'getGeneratorColumns' ), $this->getGenerators() );
	}

	/**
	 * Get the class name for the current generator based on request value
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function getCurrentGenerator() {
		$step      = ( bp_ld_sync()->getRequest( 'step', 'all' ) ) ? bp_ld_sync()->getRequest( 'step', 'all' ) : 'all';
		$generator = $this->getGenerators()[ $step ];
		return new $generator['class']();
	}

	/**
	 * Convert generator columns to datatable js format by current generator
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function getGeneratorColumns( $generator ) {
		$columns = ( new $generator['class']() )->getColumns();

		return array_map( array( $this, 'standarlizeGeneratorColumns' ), $columns, array_keys( $columns ) );
	}

	/**
	 * Convert generator columns to datatable js format
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function standarlizeGeneratorColumns( $column, $key ) {
		return array(
			'title'     => $column['label'],
			'data'      => $key,
			'name'      => $key,
			'orderable' => $column['sortable'],
		);
	}

	/**
	 * Fix bug on LD where they typed the action name wrong
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function isRealJoins() {
		if ( in_array( current_filter(), array( 'learndash_user_activity_query_joins', 'learndash_user_activity_query_join' ) ) ) {
			return true;
		}

		return $this->isRealJoins;
	}
}
