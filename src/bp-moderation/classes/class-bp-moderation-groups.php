<?php
/**
 * BuddyBoss Moderation Groups Classes
 *
 * @package BuddyBoss\Moderation
 * @since   BuddyBoss 1.5.4
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
		parent::$Moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		/**
		 * Moderation code should not add for WordPress backend & IF component is not active
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || ! bp_is_active( 'groups' ) ) {
			return;
		}

		add_filter( 'bp_groups_get_join_sql', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_groups_get_where_conditions', array( $this, 'update_where_sql' ), 10 );

		add_filter( 'bp_group_search_join_sql', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_group_search_where_conditions', array( $this, 'update_where_sql' ), 10 );
	}

	/**
	 * Add Moderation content type.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array $content_types Supported Contents types
	 *
	 * @return mixed
	 */
	public function add_content_types( $content_types ) {
		$content_types[ self::$moderation_type ] = __( 'Groups', 'buddyboss' );

		return $content_types;
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
		$sql                = false;
		$hidden_members_ids = BP_Moderation_Members::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_members_ids ) ) {
			$sql = '( g.creator_id NOT IN ( ' . implode( ',', $hidden_members_ids ) . ' ) )';
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

	/**
	 * Get Content owner id.
	 *
	 * @param integer $group_id Group id
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $group_id ) {
		$group = new BP_Groups_Group( $group_id );

		return ( ! empty( $group->creator_id ) ) ? $group->creator_id : 0;
	}

	/**
	 * Get Content.
	 *
	 * @param integer $group_id Group id
	 *
	 * @return string
	 */
	public static function get_content_excerpt( $group_id ) {
		$group = new BP_Groups_Group( $group_id );

		return ( ! empty( $group->description ) ) ? $group->description : '';
	}

	/**
	 * Report content
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array $args Content data
	 *
	 * @return string
	 */
	public static function report( $args ) {
		return parent::report( $args );
	}

}
