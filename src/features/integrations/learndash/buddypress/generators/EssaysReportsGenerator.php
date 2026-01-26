<?php
/**
 * BuddyBoss LearnDash integration essay reports generator.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Buddypress\Generators;

use Buddyboss\LearndashIntegration\Library\ReportsGenerator;
use WP_Query;
use WpProQuiz_Model_QuestionMapper;
use WpProQuiz_Model_Question;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Extends report generator for essays reports
 *
 * @since BuddyBoss 1.0.0
 */
class EssaysReportsGenerator extends ReportsGenerator {

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		 $this->completed_table_title  = __( 'Marked Essays', 'buddyboss' );
		$this->incompleted_table_title = __( 'Unmarked Essays', 'buddyboss' );

		parent::__construct();
	}

	/**
	 * Custom fetcher to load the essays from database and setup the pagination
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function fetch() {
		$essayQuery = $this->getGroupEssays( $this->args );

		$this->results = $essayQuery->posts;
		$this->pager   = array(
			'total_items' => $essayQuery->found_posts,
			'per_page'    => $essayQuery->query_vars['posts_per_page'],
			'total_pages' => $essayQuery->max_num_pages,
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
			'quiz'            => array(
				'label'     => __( 'Quizzes', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'quiz_title',
			),
			'essay'           => array(
				'label'     => __( 'Essays', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'essay_title',
			),
			'completion_date' => array(
				'label'     => __( 'Graded Date', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'essay_modify_date',
			),
			'updated_date'    => array(
				'label'     => __( 'Completion Date', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'essay_post_date',
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
			'quiz'            => $activity->quiz_title,
			'essay'           => $activity->essay_title,
			'completion_date' => get_date_from_gmt( $activity->essay_modify_date, $this->args['date_format'] ),
			'updated_date'    => get_date_from_gmt( $activity->essay_post_date, $this->args['date_format'] ),
			'score'           => $this->getEssayScore( $activity ),
		);
	}

	/**
	 * Load all the essays from the courses belong to the group
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function getGroupEssays() {
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
			'post_type'      => learndash_get_post_type_slug( 'essays' ),
			'post_status'    => $this->args['completed'] ? 'graded' : 'not_graded',
			'meta_query'     => array(
				array(
					'key'   => 'course_id',
					'value' => $courseIds,
				),
			),
		);

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
			{$wpdb->posts}.ID as essay_id,
			{$wpdb->posts}.post_title as essay_title,
			{$wpdb->posts}.post_date_gmt as essay_post_date,
			{$wpdb->posts}.post_modified_gmt as essay_modify_date,
			{$wpdb->posts}.comment_count as essay_comment_count,
			(
				SELECT meta_value
				FROM {$wpdb->postmeta} as pro_quiz_meta
				WHERE pro_quiz_meta.post_id = {$wpdb->posts}.ID
				AND pro_quiz_meta.meta_key = 'quiz_pro_id'
			) as pro_quiz_id,
			(
				SELECT post_id
				FROM {$wpdb->postmeta} as quiz_meta
				INNER JOIN {$wpdb->posts} as qm_posts ON qm_posts.ID = quiz_meta.post_id
				WHERE quiz_meta.meta_key = 'quiz_pro_id'
				AND quiz_meta.meta_value = pro_quiz_id
				and qm_posts.post_type = '{$quizPostType}'
			) as quiz_id,
			(
				SELECT quizes.post_title
				FROM {$wpdb->posts} as quizes
				WHERE quiz_id = quizes.ID
			) as quiz_title,
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
			) as activity_course_title,
			IF ({$wpdb->posts}.post_status = 'graded', {$wpdb->posts}.post_modified, 0) as activity_completed
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
		$strOrder = 'GREATEST(essay_modify_date, essay_post_date) DESC';

		if ( $this->hasArg( 'order' ) ) {
			$columns     = $this->columns();
			$columnIndex = $this->args['order'][0]['column'];
			$column      = $columns[ $this->args['columns'][ $columnIndex ]['name'] ];

			$strOrder = "{$column['order_key']} {$this->args['order'][0]['dir']}, {$strOrder}";
		}

		return $strOrder;
	}

	/**
	 * Return the essay score if available
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function getEssayScore( $activity ) {
		if ( ! $activity->activity_completed ) {
			return '-';
		}

		$essayId    = $activity->essay_id;
		$essay      = get_post( $essayId );
		$quizId     = get_post_meta( $essayId, 'quiz_id', true );
		$questionId = get_post_meta( $essayId, 'question_id', true );

		$questionMapper = new WpProQuiz_Model_QuestionMapper();
		$question       = $questionMapper->fetchById( intval( $questionId ), null );

		if ( ! $question instanceof WpProQuiz_Model_Question ) {
			return '-';
		}

		$maxPoints     = $question->getPoints();
		$essayData     = learndash_get_submitted_essay_data( $quizId, $questionId, $essay );
		$currentPoints = $essayData['points_awarded'] ? intval( $essayData['points_awarded'] ) : 0;

		return sprintf(
			_x(
				'%1$s / %2$s',
				'placeholders: current points / maximum point',
				'buddyboss'
			),
			$currentPoints,
			$maxPoints
		);
	}
}
