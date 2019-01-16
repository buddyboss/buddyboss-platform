<?php

namespace Buddyboss\LearndashIntegration\Buddypress\Generators;

use Buddyboss\LearndashIntegration\Buddypress\ReportsGenerator;
use WP_Query;
use WpProQuiz_Model_QuestionMapper;
use WpProQuiz_Model_Question;

class EssaysReportsGenerator extends ReportsGenerator
{
	public function fetch()
	{
		$essayQuery = $this->getGroupEssays($this->args);
// print_r($essayQuery->request);die();
		$this->results = $essayQuery->posts;
		$this->pager = [
			'total_items' => $essayQuery->found_posts,
			'per_page'    => $essayQuery->query_vars['posts_per_page'],
			'total_pages' => $essayQuery->max_num_pages
		];
	}

	protected function columns()
	{
		return [
			'user'            => $this->column('user'),
			'course'          => $this->column('course'),
			'quiz'            => [
				'label'     => __( 'Quiz', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'quiz_title',
			],
			'essay'            => [
				'label'     => __( 'Essay', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'essay_title',
			],
			'completion_date' => [
				'label'     => __( 'Graded Date', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'essay_modify_date',
			],
			'updated_date' => [
				'label'     => __( 'Completion Date', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'essay_post_date',
			],
			'score'           => [
				'label'     => __( 'Score', 'buddyboss' ),
				'sortable'  => false,
				'order_key' => '',
			],
		];
	}

	protected function formatData($activity)
	{
		return [
			'user'            => $activity->user_display_name,
			'course'          => $activity->activity_course_title,
			'quiz'            => $activity->quiz_title,
			'essay'           => $activity->essay_title,
			'completion_date' => get_date_from_gmt($activity->essay_modify_date, $this->args['date_format']),
			'updated_date'    => get_date_from_gmt($activity->essay_post_date, $this->args['date_format']),
			'score'           => $this->getEssayScore($activity)
		];
	}

	protected function getGroupEssays()
	{
		if ($this->hasArg('course') && ! $this->args['course']) {
			$courseIds = learndash_group_enrolled_courses(
				bp_ld_sync('buddypress')->helpers->getLearndashGroupId($this->args['group'])
			);
		} else {
			$courseIds = [$this->args['course']];
		}

		$args = [
			'posts_per_page' => $this->args['length'],
			'page'           => $this->args['start'] / $this->args['length'] + 1,
			'post_type'      => learndash_get_post_type_slug('essays'),
			'post_status' => $this->args['completed']? 'graded' : 'not_graded',
			'meta_query' => [
				[
					'key' => 'course_id',
					'value' => $courseIds
				]
			]
		];

		if ($this->hasArg('user') && $this->args['user']) {
			$args['author'] = $this->args['user'];
		}

		$this->registerQueryHooks();
		$query = new WP_Query($args);
		$this->unregisterQueryHooks();

		return $query;
	}

	protected function registerQueryHooks()
	{
		add_filter('posts_fields', [$this, 'addAdditionalFields']);
		add_filter('posts_join_paged', [$this, 'addAdditionalJoins']);
		add_filter('posts_orderby', [$this, 'addAdditionalOrderBy']);
	}

	protected function unregisterQueryHooks()
	{
		remove_filter('posts_fields', [$this, 'addAdditionalFields']);
		remove_filter('posts_join_paged', [$this, 'addAdditionalJoins']);
		remove_filter('posts_orderby', [$this, 'addAdditionalOrderBy']);
	}

	public function addAdditionalFields($strFields)
	{
		global $wpdb;
		$quizPostType = learndash_get_post_type_slug('quiz');

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

	public function addAdditionalJoins($strJoins)
	{
		global $wpdb;

		$strJoins .= "
			INNER JOIN {$wpdb->users} as users ON users.ID = {$wpdb->posts}.post_author
		";

		return $strJoins;
	}

	public function addAdditionalOrderBy($strOrder)
	{
		$strOrder = 'GREATEST(essay_modify_date, essay_post_date) DESC';

		if ($this->hasArg('order')) {
			$columns = $this->columns();
			$columnIndex = $this->args['order'][0]['column'];
			$column = $columns[$this->args['columns'][$columnIndex]['name']];

			$strOrder = "{$column['order_key']} {$this->args['order'][0]['dir']}, {$strOrder}";
		}

		return $strOrder;
	}

	protected function getEssayScore($activity)
	{
		if (! $activity->activity_completed) {
			return '-';
		}

		$essayId    = $activity->essay_id;
		$essay      = get_post($essayId);
		$quizId     = get_post_meta($essayId, 'quiz_id', true);
		$questionId = get_post_meta($essayId, 'question_id', true);

		$questionMapper = new WpProQuiz_Model_QuestionMapper();
		$question       = $questionMapper->fetchById(intval($questionId), null);

		if ( ! $question instanceof WpProQuiz_Model_Question ) {
			return '-';
		}

		$maxPoints = $question->getPoints();
		$essayData = learndash_get_submitted_essay_data($quizId, $questionId, $essay);
		$currentPoints = $essayData['points_awarded']? intval($essayData['points_awarded']) : 0;

		return sprintf(
			_x(
				'%1$s / %2$d',
				'placeholders: input points / maximum point for essay',
				'learndash'
			), $currentPoints, $maxPoints
		);
	}
}
