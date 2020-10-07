<?php
/**
 * BuddyBoss Moderation Forum Replies Classes
 *
 * @package BuddyBoss\Moderation
 * @since BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Forum Replies.
 *
 * @since BuddyBoss 1.5.4
 */
class BP_Moderation_Forum_Replies extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'forum_reply';

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
		$this->alias     = $this->alias . 'fr'; // fr: Forum Reply.

		add_filter( 'posts_join', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'posts_where', array( $this, 'update_where_sql' ), 10, 2 );
	}

	/**
	 * Prepare Forum Replies Join SQL query to filter blocked Forum Replies
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $join_sql Forum Replies Join sql.
	 * @param object $wp_query WP_Query object.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $wp_query ) {
		global $wpdb;

		$forum_slug = bbp_get_reply_post_type();
		$post_types = wp_parse_slug_list( $wp_query->get( 'post_type' ) );
		if ( ! empty( $post_types ) && in_array( $forum_slug, $post_types, true ) ) {
			$join_sql .= $this->exclude_joint_query( "{$wpdb->posts}.ID" );
		}

		return $join_sql;
	}

	/**
	 * Prepare Forum Replies Where SQL query to filter blocked Forum Replies
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $where_conditions_str Forum Replies Where sql.
	 * @param object $wp_query WP_Query object.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions_str, $wp_query ) {

		$forum_slug = bbp_get_reply_post_type();
		$post_types = wp_parse_slug_list( $wp_query->get( 'post_type' ) );

		if ( ! empty( $post_types ) && in_array( $forum_slug, $post_types, true ) ) {
			$where                        = array();
			$where['forum_replies_where'] = $this->exclude_where_query();

			/**
			 * Exclude block member forum replies
			 */
			$members_where = $this->exclude_member_reply_query();
			if ( $members_where ) {
				$where['members_where'] = $members_where;
			}

			/**
			 * Exclude block Topic replies
			 */
			$topics_where = $this->exclude_topic_reply_query();
			if ( $topics_where ) {
				$where['topics_where'] = $topics_where;
			}

			/**
			 * Filters the Forum Replies Moderation Where SQL statement.
			 *
			 * @since BuddyBoss 1.5.4
			 *
			 * @param array $where array of Forum Replies moderation where query.
			 */
			$where = apply_filters( 'bp_moderation_forum_replies_get_where_conditions', $where );

			$where_conditions_str .= ' AND ( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions_str;
	}

	/**
	 * Get SQL for Exclude Blocked Members related replies
	 *
	 * @return string|bool
	 */
	private function exclude_member_reply_query() {
		global $wpdb;
		$sql              = false;
		$hidden_forum_ids = BP_Moderation_Members::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_forum_ids ) ) {
			$sql = "( {$wpdb->posts}.post_author NOT IN ( " . implode( ',', $hidden_forum_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get SQL for Exclude Blocked topic related replies
	 *
	 * @return string|bool
	 */
	private function exclude_topic_reply_query() {
		global $wpdb;
		$sql              = false;
		$hidden_topic_ids = BP_Moderation_Forum_Topics::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_topic_ids ) ) {
			$sql = "( {$wpdb->posts}.post_parent NOT IN ( " . implode( ',', $hidden_topic_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get blocked Replies that also include Blocked forum & topic replies
	 *
	 * @return array
	 */
	public static function get_sitewide_hidden_ids() {
		$hidden_reply_ids = self::get_sitewide_hidden_item_ids( self::$moderation_type );

		$hidden_topic_ids = BP_Moderation_Forum_Topics::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_topic_ids ) ) {
			$replies_query = new WP_Query(
				array(
					'fields'                 => 'ids',
					'post_type'              => bbp_get_reply_post_type(),
					'post_status'            => 'publish',
					'post_parent__in'        => $hidden_topic_ids,
					'posts_per_page'         => - 1,
					// Need to get all topics id of hidden forums.
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'suppress_filters'       => true,
				)
			);

			if ( $replies_query->have_posts() ) {
				$hidden_reply_ids = array_merge( $hidden_reply_ids, $replies_query->posts );
			}
		}

		return $hidden_reply_ids;
	}

}
