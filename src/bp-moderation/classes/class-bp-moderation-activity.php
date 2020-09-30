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
	public $item_type = 'activity';

	/**
	 * Activity ID field name with alias for Join sql conditions
	 *
	 * @var string
	 */
	protected $item_id_field = 'a.id';

	/**
	 * Activity User ID field name with alias for Join sql conditions
	 *
	 * @var string
	 */
	protected $user_id_field = 'a.user_id';

	/**
	 * BP_Moderation_Activity constructor.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function __construct() {
		add_filter( 'bp_activity_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_activity_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
	}

	/**
	 * Prepare activity Join SQL query to filter blocked Activity
	 *
	 * @param string $join_sql Activity Join sql
	 *
	 * @return string Join sql
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function update_join_sql( $join_sql ) {
		$join_sql .= $this->bp_moderation_exclude_joint_query();

		return $join_sql;
	}

	/**
	 * Prepare activity Where SQL query to filter blocked Activity
	 *
	 * @param array $where_conditions Activity Where sql
	 *
	 * @return mixed Where SQL
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function update_where_sql( $where_conditions ) {
		$where_conditions['moderation_where'] = $this->bp_moderation_exclude_where_query();

		return $where_conditions;
	}

}
