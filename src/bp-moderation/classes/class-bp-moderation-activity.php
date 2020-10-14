<?php
/**
 * BuddyBoss Moderation Activity Classes
 *
 * @since   BuddyBoss 1.5.4
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Activity.
 *
 * @since BuddyBoss 1.5.4
 */
class BP_Moderation_Activity extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'activity';

	/**
	 * BP_Moderation_Activity constructor.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function __construct() {

		/**
		 * Moderation code should not add for WordPress backend & IF component is not active
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || ! bp_is_active( 'activity' ) ) {
			return;
		}

		$this->item_type = self::$moderation_type;

		add_filter( 'bp_activity_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_activity_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
	}

	/**
	 * Prepare activity Join SQL query to filter blocked Activity
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $join_sql Activity Join sql.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql ) {
		$join_sql .= $this->exclude_joint_query( 'a.id' );

		return $join_sql;
	}

	/**
	 * Prepare activity Where SQL query to filter blocked Activity
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array $where_conditions Activity Where sql.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions ) {
		$where                   = array();
		$where['activity_where'] = $this->exclude_where_query();

		/**
		 * Exclude Blocked Member activity
		 */
		$members_where = $this->exclude_member_activity_query();
		if ( $members_where ) {
			$where['members_where'] = $members_where;
		}

		/**
		 * Exclude Blocked Groups activity
		 */
		if ( bp_is_active( 'groups' ) ) {
			$groups_where = $this->exclude_group_activity_query();
			if ( ! empty( $groups_where ) ) {
				$where['groups_where'] = $groups_where;
			}
		}

		/**
		 * Exclude Blocked Forums, Topics, Replies activity
		 */
		if ( bp_is_active( 'forums' ) ) {
			$forums_where = $this->exclude_forums_activity_query();
			if ( ! empty( $forums_where ) ) {
				$where['forums_where'] = $forums_where;
			}
		}

		/**
		 * Filters the activity Moderation Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param array $where array of activity moderation where query.
		 */
		$where = apply_filters( 'bp_moderation_activity_get_where_conditions', $where );

		$where_conditions['moderation_where'] = '( ' . implode( ' AND ', $where ) . ' )';

		return $where_conditions;
	}

	/**
	 * Get SQL for Exclude Blocked Groups related activity
	 *
	 * @return string|bool
	 */
	private function exclude_group_activity_query() {
		$sql              = false;
		$hidden_group_ids = BP_Moderation_Groups::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_group_ids ) ) {
			$sql = "( ( a.component = 'groups' AND a.item_id NOT IN ( " . implode( ',', $hidden_group_ids ) . " ) ) OR a.component != 'groups' )";
		}

		return $sql;
	}

	/**
	 * Get SQL for Exclude Blocked Members related activity
	 *
	 * @return string|bool
	 */
	private function exclude_member_activity_query() {
		$sql              = false;
		$hidden_members_ids = BP_Moderation_Members::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_members_ids ) ) {
			$sql = '( a.user_id NOT IN ( ' . implode( ',', $hidden_members_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get SQL for Exclude Blocked Forums, topic and replies related activity
	 *
	 * @return string|bool
	 */
	private function exclude_forums_activity_query() {
		$sql = false;

		$hidden_forums_ids        = BP_Moderation_Forums::get_sitewide_hidden_ids();
		$hidden_forum_topics_ids  = BP_Moderation_Forum_Topics::get_sitewide_hidden_ids();
		$hidden_forum_replies_ids = BP_Moderation_Forum_Replies::get_sitewide_hidden_ids();

		$hidden_ids = array_merge( $hidden_forums_ids, $hidden_forum_topics_ids, $hidden_forum_replies_ids );
		if ( ! empty( $hidden_ids ) ) {
			$sql = "( a.component !='bbpress' OR a.item_id NOT IN ( " . implode( ',', $hidden_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get blocked Activity ids
	 *
	 * @return array
	 */
	public static function get_sitewide_hidden_ids() {
		return self::get_sitewide_hidden_item_ids( self::$moderation_type );
	}
}
