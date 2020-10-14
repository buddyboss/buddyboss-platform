<?php
/**
 * BuddyBoss Moderation Forums Classes
 *
 * @package BuddyBoss\Moderation
 * @since BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Forums.
 *
 * @since BuddyBoss 1.5.4
 */
class BP_Moderation_Forums extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'forum';

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
		$this->alias     = $this->alias . 'f'; // f = Forum.

		add_filter( 'posts_join', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'posts_where', array( $this, 'update_where_sql' ), 10, 2 );
	}

	/**
	 * Prepare Forums Join SQL query to filter blocked Forums
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $join_sql Forums Join sql.
	 * @param object $wp_query WP_Query object.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $wp_query ) {
		global $wpdb;

		$forum_slug = bbp_get_forum_post_type();
		$post_types = wp_parse_slug_list( $wp_query->get( 'post_type' ) );
		if ( ! empty( $post_types ) && in_array( $forum_slug, $post_types, true ) ) {
			$join_sql .= $this->exclude_joint_query( "{$wpdb->posts}.ID" );
		}

		return $join_sql;
	}

	/**
	 * Prepare Forums Where SQL query to filter blocked Forums
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $where_conditions_str Forums Where sql.
	 * @param object $wp_query WP_Query object.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions_str, $wp_query ) {

		$forum_slug = bbp_get_forum_post_type();
		$post_types = wp_parse_slug_list( $wp_query->get( 'post_type' ) );

		if ( ! empty( $post_types ) && in_array( $forum_slug, $post_types, true ) ) {
			$where                 = array();
			$where['forums_where'] = $this->exclude_where_query();

			/**
			 * Exclude block member forums
			 */
			$members_where = $this->exclude_member_forum_query();
			if ( $members_where ) {
				$where['members_where'] = $members_where;
			}

			/**
			 * Filters the Forums Moderation Where SQL statement.
			 *
			 * @since BuddyBoss 1.5.4
			 *
			 * @param array $where array of Forums moderation where query.
			 */
			$where = apply_filters( 'bp_moderation_forums_get_where_conditions', $where );

			$where_conditions_str .= ' AND ( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions_str;
	}

	/**
	 * Get SQL for Exclude Blocked Members related forums
	 *
	 * @return string|bool
	 */
	private function exclude_member_forum_query() {
		global $wpdb;
		$sql              = false;
		$hidden_members_ids = BP_Moderation_Members::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_members_ids ) ) {
			$sql = "( {$wpdb->posts}.post_author NOT IN ( " . implode( ',', $hidden_members_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get Blocked Forums ids
	 *
	 * @return array
	 */
	public static function get_sitewide_hidden_ids() {
		return self::get_sitewide_hidden_item_ids( self::$moderation_type );
	}

}
