<?php
/**
 * BuddyBoss Moderation Activity Classes
 *
 * @package BuddyBoss\Moderation
 * @since BuddyBoss 1.5.4
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

		$this->item_type = self::$moderation_type;

		add_filter( 'bp_activity_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_activity_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
	}

	/**
	 * Prepare activity Join SQL query to filter blocked Activity
	 *
	 * @param string $join_sql Activity Join sql.
	 *
	 * @return string Join sql
	 *
	 * @since BuddyBoss 1.5.4
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
		 * Exclude Blocked Groups activity
		 */
		if ( bp_is_active( 'groups' ) ) {
			$groups_where = $this->exclude_group_activity_query();
			if ( ! empty( $groups_where ) ) {
				$where['groups_where'] = $groups_where;
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
	 * Get Exclude Blocked Groups SQL
	 *
	 * @return string|void
	 */
	private function exclude_group_activity_query() {
		global $wpdb;
		$sql              = '';
		$hidden_group_ids = $this->get_sitewide_hidden_item_ids( BP_Moderation_Groups::$moderation_type );
		if ( ! empty( $hidden_group_ids ) ) {
			$sql = "( ( a.component = 'groups' AND a.item_id NOT IN ( " . implode( ',', $hidden_group_ids ) . " ) ) OR a.component != 'groups' )";
		}

		return $sql;
	}

}
