<?php
/**
 * BuddyBoss Moderation Groups Classes
 *
 * @package BuddyBoss\Moderation
 * @since BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Groups.
 *
 * @since BuddyBoss 1.5.4
 */
class BP_Moderation_Groups extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'groups';

	/**
	 * BP_Moderation_Group constructor.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function __construct() {

		/**
		 * Moderation code should not add for WordPress backend & IF component is not active
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || ! bp_is_active( 'groups' ) ) {
			return;
		}

		$this->item_type = self::$moderation_type;

		add_filter( 'bp_groups_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_groups_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
	}

	/**
	 * Prepare Groups Join SQL query to filter blocked Groups
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $join_sql Groups Join sql.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql ) {
		$join_sql .= $this->exclude_joint_query( 'g.id' );

		return $join_sql;
	}

	/**
	 * Prepare Groups Where SQL query to filter blocked Groups
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array $where_conditions Groups Where sql.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions ) {
		$where                 = array();
		$where['groups_where'] = $this->exclude_where_query();

		/**
		 * Exclude block member activity
		 */
		$members_where = $this->exclude_member_group_query();
		if ( $members_where ) {
			$where['members_where'] = $members_where;
		}

		/**
		 * Filters the groups Moderation Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param array $where array of groups moderation where query.
		 */
		$where = apply_filters( 'bp_moderation_groups_get_where_conditions', $where );

		$where_conditions['moderation_where'] = '( ' . implode( ' AND ', $where ) . ' )';

		return $where_conditions;
	}

	/**
	 * Get Exclude Blocked Members SQL
	 *
	 * @return string|bool
	 */
	private function exclude_member_group_query() {
		$sql              = false;
		$hidden_group_ids = BP_Moderation_Members::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_group_ids ) ) {
			$sql = '( g.creator_id NOT IN ( ' . implode( ',', $hidden_group_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get blocked Groups ids
	 *
	 * @return array
	 */
	public static function get_sitewide_hidden_ids() {
		return self::get_sitewide_hidden_item_ids( self::$moderation_type );
	}

}
