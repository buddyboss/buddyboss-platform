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
 * Database interaction class for the BuddyBoss moderation Members.
 *
 * @since BuddyBoss 1.5.4
 */
class BP_Moderation_Members extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'user';

	/**
	 * BP_Moderation_Group constructor.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function __construct() {

		/**
		 * Moderation code should not add for WordPress backend
		 */
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		$this->item_type = self::$moderation_type;

		add_filter( 'bp_user_query_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_user_query_where_sql', array( $this, 'update_where_sql' ), 10 );
	}

	/**
	 * Prepare Members Join SQL query to filter blocked Members
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $join_sql Members sql.
	 * @param string $uid_name User ID field name
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $uid_name ) {
		$join_sql .= $this->exclude_joint_query( 'u.' . $uid_name );

		return $join_sql;
	}

	/**
	 * Prepare Members Where SQL query to filter blocked Members
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array $where_conditions Members where sql.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions ) {
		$where                = array();
		$where['users_where'] = $this->exclude_where_query();

		/**
		 * Filters the Members Moderation Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param array $where array of Members moderation where query.
		 */
		$where = apply_filters( 'bp_moderation_groups_get_where_conditions', $where );

		$where_conditions['moderation_where'] = '( ' . implode( ' AND ', $where ) . ' )';

		return $where_conditions;
	}

	/**
	 * Get blocked Member ids
	 *
	 * @return array
	 */
	public static function get_sitewide_hidden_ids() {
		return self::get_sitewide_hidden_item_ids( self::$moderation_type );
	}

}
