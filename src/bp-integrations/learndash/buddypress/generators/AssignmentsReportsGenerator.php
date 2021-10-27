<?php
/**
 * BuddyBoss LearnDash integration assignment reports generator.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Buddypress\Generators;

use Buddyboss\LearndashIntegration\Library\ReportsGenerator;
use WP_Query;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Extends report generator for assignments reports
 *
 * @since BuddyBoss 1.0.0
 */
class AssignmentsReportsGenerator extends ReportsGenerator {

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		 $this->completed_table_title  = __( 'Marked Assignments', 'buddyboss' );
		$this->incompleted_table_title = __( 'Unmarked Assignments', 'buddyboss' );

		parent::__construct();
	}

	/**
	 * Custom fetcher to load the assignments from database and setup the pagination
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function fetch() {
		$assignmentQuery = $this->getGroupAssignments( $this->args );
		// print_r($assignmentQuery->request);die();
		$this->results = $assignmentQuery->posts;
		$this->pager   = array(
			'total_items' => $assignmentQuery->found_posts,
			'per_page'    => $assignmentQuery->query_vars['posts_per_page'],
			'total_pages' => $assignmentQuery->max_num_pages,
		);
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
			'assignment'      => array(
				'label'     => __( 'Assignment', 'buddyboss' ),
				'sortable'  => false,
				'order_key' => '',
			),
			'completion_date' => array(
				'label'     => __( 'Graded Date', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'assignment_modify_date',
			),
			'updated_date'    => array(
				'label'     => __( 'Uploaded Date', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'assignment_post_date',
			),
			'score'           => array(
				'label'     => __( 'Score', 'buddyboss' ),
				'sortable'  => false,
				'order_key' => '',
			),
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
			'assignment'      => $activity->assignment_title,
			'completion_date' => get_date_from_gmt( $activity->assignment_modify_date, $this->args['date_format'] ),
			'updated_date'    => get_date_from_gmt( $activity->assignment_post_date, $this->args['date_format'] ),
			'score'           => $this->getAssignmentScore( $activity ),
		);
	}

	/**
	 * Load all the assignments from the courses belong to the group
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function getGroupAssignments() {
		if ( $this->hasArg( 'course' ) && ! $this->args['course'] ) {
			$courseIds = learndash_group_enrolled_courses(
				bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( $this->args['group'] )
			);
		} else {
			$courseIds = array( $this->args['course'] );
		}

		$args = array(
			'posts_per_page' => $this->args['length'],
			'page'           => $this->args['start'] / $this->args['length'] + 1,
			'post_type'      => learndash_get_post_type_slug( 'assignment' ),
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'   => 'course_id',
					'value' => $courseIds,
				),
			),
		);

		if ( $this->args['completed'] ) {
			$args['meta_query'][] = array(
				'key'   => 'approval_status',
				'value' => 1,
			);
		} else {
			$args['meta_query'][] = array(
				'key'     => 'approval_status',
				'compare' => 'NOT EXISTS',
			);
		}

		if ( $this->hasArg( 'user' ) && $this->args['user'] ) {
			$args['author'] = $this->args['user'];
		}

		$this->registerQueryHooks();
		$query = new WP_Query( $args );
		$this->unregisterQueryHooks();

		return $query;
	}

	/**
	 * Add additional sql statement to fetch data
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function registerQueryHooks() {
		add_filter( 'posts_fields', array( $this, 'addAdditionalFields' ) );
		add_filter( 'posts_join_paged', array( $this, 'addAdditionalJoins' ) );
		add_filter( 'posts_orderby', array( $this, 'addAdditionalOrderBy' ) );
	}

	/**
	 * Remove additional sql statement to fetch data
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function unregisterQueryHooks() {
		 remove_filter( 'posts_fields', array( $this, 'addAdditionalFields' ) );
		remove_filter( 'posts_join_paged', array( $this, 'addAdditionalJoins' ) );
		remove_filter( 'posts_orderby', array( $this, 'addAdditionalOrderBy' ) );
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
	 * Return the assignment score if available
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function getAssignmentScore( $activity ) {
		$postId = $activity->assignment_id;

		if ( ! get_post_meta( $postId, 'approval_status', true ) ) {
			return '-';
		}

		$assignmentSettingId = intval( get_post_meta( $postId, 'lesson_id', true ) );

		if ( empty( $assignmentSettingId ) ) {
			return '-';
		}

		$maxPoints = learndash_get_setting( $assignmentSettingId, 'lesson_assignment_points_amount' );

		return sprintf(
			_x(
				'%1$s / %2$s',
				'placeholders: current points / maximum point',
				'buddyboss'
			),
			get_post_meta( $postId, 'points', true ),
			$maxPoints
		);
	}
}
