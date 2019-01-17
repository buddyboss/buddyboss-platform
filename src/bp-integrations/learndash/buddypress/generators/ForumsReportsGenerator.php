<?php

namespace Buddyboss\LearndashIntegration\Buddypress\Generators;

use Buddyboss\LearndashIntegration\Buddypress\ReportsGenerator;
use WP_Query;

class ForumsReportsGenerator extends ReportsGenerator
{
	public function __construct()
	{
		$this->completed_table_title = __('Answered Discusssions', 'buddyboss');
		$this->incompleted_table_title = __('Unanswered Discusssions', 'buddyboss');

		parent::__construct();
	}

	public function fetch()
	{
		$topicQuery = $this->getGroupForumTopics($this->args);
// print_r($topicQuery->request);die();
		$this->results = $topicQuery->posts;
		$this->pager = [
			'total_items' => $topicQuery->found_posts,
			'per_page'    => $topicQuery->query_vars['posts_per_page'],
			'total_pages' => $topicQuery->max_num_pages
		];
	}

	protected function columns()
	{
		return [
			'user_id'         => $this->column('user_id'),
			'user'  => $this->column('user'),
			'topic' => [
				'label'     => __( 'Forum Topic', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'topic_title',
			],
			'reply'     => [
				'label'     => __( 'Reply', 'buddyboss' ),
				'sortable'  => false,
				'order_key' => '',
			],
			'post_date' => [
				'label'     => __( 'Date Posted', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'post_date_gmt',
			],
		];
	}

	protected function formatData($activity)
	{
		return [
			'user_id'         => $activity->user_id,
			'user'      => $activity->user_display_name,
			'topic'     => $activity->topic_title,
			'reply'     => wp_trim_words($activity->last_reply_content, 15, '...'),
			'post_date' => get_date_from_gmt($activity->topic_post_date, $this->args['date_format']),
		];
	}

	protected function getGroupForumTopics()
	{
		$args = [
			'posts_per_page' => $this->args['length'],
			'page'           => $this->args['start'] / $this->args['length'] + 1,
			'post_type'      => bbp_get_topic_post_type(),
			'post_status' => 'publish'
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
		add_filter('posts_where', [$this, 'addAdditionalWhere'], 99);
		add_filter('posts_orderby', [$this, 'addAdditionalOrderBy']);
	}

	protected function unregisterQueryHooks()
	{
		remove_filter('posts_fields', [$this, 'addAdditionalFields']);
		remove_filter('posts_join_paged', [$this, 'addAdditionalJoins']);
		remove_filter('posts_where', [$this, 'addAdditionalWhere'], 99);
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
			{$wpdb->posts}.ID as topic_id,
			{$wpdb->posts}.post_title as topic_title,
			{$wpdb->posts}.post_date_gmt as topic_post_date,
			(
				SELECT meta_value
				FROM {$wpdb->postmeta} as topic_meta
				WHERE topic_meta.post_id = {$wpdb->posts}.ID
				AND topic_meta.meta_key = '_bbp_last_reply_id'
			) as last_reply_id,
			(
				SELECT post_content
				FROM {$wpdb->posts} as replies
				WHERE last_reply_id = replies.ID
			) as last_reply_content
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

	public function addAdditionalWhere($strWhere)
	{
		$compare = $this->args['completed']? 'IS NOT' : 'IS';

		$strWhere .= "
			HAVING last_reply_id {$compare} NULL
		";

		return $strWhere;
	}

	public function addAdditionalOrderBy($strOrder)
	{
		$strOrder = 'topic_post_date DESC';

		if ($this->hasArg('order')) {
			$columns     = $this->columns();
			$columnIndex = $this->args['order'][0]['column'];
			$column      = $columns[$this->args['columns'][$columnIndex]['name']];

			$strOrder    = "{$column['order_key']} {$this->args['order'][0]['dir']}, {$strOrder}";
		}

		return $strOrder;
	}
}
