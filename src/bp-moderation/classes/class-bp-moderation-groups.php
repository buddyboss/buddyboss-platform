<?php
/**
 * BuddyBoss Moderation Groups Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Groups.
 *
 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {
		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		/**
		 * Moderation code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || ! bp_is_active( 'groups' ) || self::admin_bypass_check() ) {
			return;
		}

		add_filter( 'bp_groups_get_join_sql', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_groups_get_where_conditions', array( $this, 'update_where_sql' ), 10 );

		add_filter( 'bp_group_search_join_sql', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_group_search_where_conditions', array( $this, 'update_where_sql' ), 10 );

		// Delete group moderation data when group is deleted.
		add_action( 'groups_delete_group', array( $this, 'delete_moderation_data' ), 10 );
	}

	/**
	 * Get blocked Groups ids
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @return array
	 */
	public static function get_sitewide_hidden_ids() {
		return self::get_sitewide_hidden_item_ids( self::$moderation_type );
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param integer $group_id Group id.
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
	 * @since BuddyBoss 2.0.0
	 *
	 * @param integer $group_id  Group id.
	 * @param bool    $view_link add view link
	 *
	 * @return string
	 */
	public static function get_content_excerpt( $group_id, $view_link = false ) {
		$group             = new BP_Groups_Group( $group_id );
		$group_description = ( ! empty( $group->description ) ) ? $group->description : '';

		if ( true === $view_link ) {
			$link = '<a href="' . esc_url( self::get_permalink( (int) $group_id ) ) . '">' . esc_html__( 'View',
					'buddyboss' ) . '</a>';;

			$group_description = ( ! empty( $group_description ) ) ? $group_description . ' ' . $link : $link;
		}

		return $group_description;
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $group_id group id.
	 *
	 * @return string
	 */
	public static function get_permalink( $group_id ) {
		$group = new BP_Groups_Group( $group_id );

		$url = trailingslashit( bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $group->slug . '/' );

		return add_query_arg( array( 'modbypass' => 1 ), $url );
	}

	/**
	 * Report content
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $args Content data.
	 *
	 * @return string
	 */
	public static function report( $args ) {
		return parent::report( $args );
	}

	/**
	 * Add Moderation content type.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $content_types Supported Contents types.
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
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
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
		 * @since BuddyBoss 2.0.0
		 *
		 * @param array $where array of groups moderation where query.
		 */
		$where = apply_filters( 'bp_moderation_groups_get_where_conditions', $where );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['moderation_where'] = '( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions;
	}

	/**
	 * Get Exclude Blocked Members SQL
	 *
	 * @since BuddyBoss 2.0.0
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
	 * Function to delete group moderation data when actual group is deleted
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $group_id group if
	 */
	public function delete_moderation_data( $group_id ) {
		if ( ! empty( $group_id ) ) {
			$moderation_obj = new BP_Moderation( $group_id, self::$moderation_type );
			if ( ! empty( $moderation_obj->id ) ) {
				$moderation_obj->delete( true );
			}
		}
	}
}
