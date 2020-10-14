<?php
/**
 * BuddyBoss Moderation Forum Topics Classes
 *
 * @since   BuddyBoss 1.5.4
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Forum Topics.
 *
 * @since BuddyBoss 1.5.4
 */
class BP_Moderation_Forum_Topics extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'forum_topic';

	/**
	 * BP_Moderation_Group constructor.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function __construct() {

		/**
		 * Moderation code should not add for WordPress backend & IF component is not active
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || ! bp_is_active( 'forums' ) ) {
			return;
		}

		$this->item_type = self::$moderation_type;
		$this->alias     = $this->alias . 'ft'; // ft: Forum Topic.

		add_filter( 'posts_join', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'posts_where', array( $this, 'update_where_sql' ), 10, 2 );
	}

	/**
	 * Prepare Forum Topics Join SQL query to filter blocked Forum Topics
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $join_sql Forum Topics Join sql.
	 * @param object $wp_query WP_Query object.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $wp_query ) {
		global $wpdb;

		$topicslug  = bbp_get_topic_post_type();
		$post_types = wp_parse_slug_list( $wp_query->get( 'post_type' ) );
		if ( ! empty( $post_types ) && in_array( $topicslug, $post_types, true ) ) {
			$join_sql .= $this->exclude_joint_query( "{$wpdb->posts}.ID" );
		}

		return $join_sql;
	}

	/**
	 * Prepare Forum Topics Where SQL query to filter blocked Forum Topics
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $where_conditions_str Forum Topics Where sql.
	 * @param object $wp_query             WP_Query object.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions_str, $wp_query ) {

		$topicslug  = bbp_get_topic_post_type();
		$post_types = wp_parse_slug_list( $wp_query->get( 'post_type' ) );

		if ( ! empty( $post_types ) && in_array( $topicslug, $post_types, true ) ) {
			$where                       = array();
			$where['forum_topics_where'] = $this->exclude_where_query();

			/**
			 * Exclude block member forum topics
			 */
			$members_where = $this->exclude_member_topic_query();
			if ( $members_where ) {
				$where['members_where'] = $members_where;
			}

			/**
			 * Exclude block Topic replies
			 */
			$forums_where = $this->exclude_forum_topic_query();
			if ( $forums_where ) {
				$where['forums_where'] = $forums_where;
			}

			/**
			 * Filters the Forum Topics Moderation Where SQL statement.
			 *
			 * @since BuddyBoss 1.5.4
			 *
			 * @param array $where array of Forum Topics moderation where query.
			 */
			$where = apply_filters( 'bp_moderation_forum_topics_get_where_conditions', $where );

			$where_conditions_str .= ' AND ( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions_str;
	}

	/**
	 * Get SQL for Exclude Blocked Members related topics
	 *
	 * @return string|bool
	 */
	private function exclude_member_topic_query() {
		global $wpdb;
		$sql              = false;
		$hidden_members_ids = BP_Moderation_Members::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_members_ids ) ) {
			$sql = "( {$wpdb->posts}.post_author NOT IN ( " . implode( ',', $hidden_members_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get SQL for Exclude Blocked forum related topics
	 *
	 * @return string|bool
	 */
	private function exclude_forum_topic_query() {
		global $wpdb;
		$sql              = false;
		$hidden_forum_ids = BP_Moderation_Forums::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_forum_ids ) ) {
			$sql = "( {$wpdb->posts}.post_parent NOT IN ( " . implode( ',', $hidden_forum_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get blocked Topics that also include Blocked forum's topics
	 *
	 * @return array
	 */
	public static function get_sitewide_hidden_ids() {
		$hidden_topic_ids = self::get_sitewide_hidden_item_ids( self::$moderation_type );

		$hidden_forum_ids = BP_Moderation_Forums::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_forum_ids ) ) {
			$topics_query = new WP_Query(
				array(
					'fields'                 => 'ids',
					'post_type'              => bbp_get_topic_post_type(),
					'post_status'            => 'publish',
					'post_parent__in'        => $hidden_forum_ids,
					'posts_per_page'         => - 1,
					// Need to get all topics id of hidden forums.
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'suppress_filters'       => true,
				)
			);

			if ( $topics_query->have_posts() ) {
				$hidden_topic_ids = array_merge( $hidden_topic_ids, $topics_query->posts );
			}
		}

		return $hidden_topic_ids;
	}

}
