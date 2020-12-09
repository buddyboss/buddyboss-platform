<?php
/**
 * BuddyBoss Suspend Activity Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Suspend
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend Activity.
 *
 * @since BuddyBoss 2.0.0
 */
class BP_Suspend_Activity extends BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $type = 'activity';

	/**
	 * BP_Moderation_Activity constructor.
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {

		$this->item_type = self::$type;

		//Manage hidden list
		add_action( "bp_suspend_hide_{$this->item_type}", array( $this, 'manage_hidden_activity' ), 10, 3 );
		add_action( "bp_suspend_unhide_{$this->item_type}", array( $this, 'manage_unhidden_activity' ), 10, 4 );

		/**
		 * Suspend code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		add_filter( 'bp_activity_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_activity_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_activity_search_join_sql', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_activity_search_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
	}

	/**
	 * Get Blocked member's activity ids
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $member_id member id.
	 *
	 * @return array
	 */
	public static function get_member_activity_ids( $member_id ) {
		$activities_ids = array();

		$activities = BP_Activity_Activity::get( array(
			'moderation_query' => false,
			'per_page'         => 0,
			'fields'           => 'ids',
			'show_hidden'      => true,
			'filter'           => array(
				'user_id' => $member_id,
			),
		) );

		if ( ! empty( $activities['activities'] ) ) {
			$activities_ids = $activities['activities'];
		}

		return $activities_ids;
	}

	/**
	 * Get Blocked group's activity ids
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $group_id group id.
	 *
	 * @return array
	 */
	public static function get_group_activity_ids( $group_id ) {
		$activities_ids = array();

		$activities = BP_Activity_Activity::get( array(
			'moderation_query' => false,
			'per_page'         => 0,
			'fields'           => 'ids',
			'show_hidden'      => true,
			'filter'           => array(
				'primary_id' => $group_id,
				'object'     => 'groups',
			),
		) );

		if ( ! empty( $activities['activities'] ) ) {
			$activities_ids = $activities['activities'];
		}

		return $activities_ids;
	}

	/**
	 * Get forum activities ids
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $post_id post id
	 *
	 * @return array
	 */
	public static function get_bbpress_activity_ids( $post_id ) {
		$activities_ids = array();

		$activities = BP_Activity_Activity::get( array(
			'moderation_query' => false,
			'per_page'         => 0,
			'fields'           => 'ids',
			'show_hidden'      => true,
			'filter'           => array(
				'primary_id' => $post_id,
				'object'     => 'bbpress',
			),
		) );

		if ( ! empty( $activities['activities'] ) ) {
			$activities_ids = $activities['activities'];
		}

		return $activities_ids;
	}

	/**
	 * Prepare activity Join SQL query to filter blocked Activity
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $join_sql Activity Join sql.
	 * @param array  $args     Query arguments.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $args = array() ) {

		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $join_sql;
		}

		$join_sql .= $this->exclude_joint_query( 'a.id' );

		/**
		 * Filters the hidden activity Where SQL statement.
		 *
		 * @since BuddyBoss 2.0.0
		 *
		 * @param array $join_sql Join sql query
		 * @param array $class    current class object.
		 */
		$join_sql = apply_filters( 'bp_suspend_activity_get_join', $join_sql, $this );

		return $join_sql;
	}

	/**
	 * Prepare activity Where SQL query to filter blocked Activity
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $where_conditions Activity Where sql.
	 * @param array $args             Query arguments.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions, $args = array() ) {
		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $where_conditions;
		}

		$where                  = array();
		$where['suspend_where'] = $this->exclude_where_query();

		/**
		 * Filters the hidden activity Where SQL statement.
		 *
		 * @since BuddyBoss 2.0.0
		 *
		 * @param array $where Query to hide suspended user's activity.
		 * @param array $class current class object.
		 */
		$where = apply_filters( 'bp_suspend_activity_get_where_conditions', $where, $this );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['suspend_where'] = '( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions;
	}

	/**
	 * Hide related content of activity
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int      $activity_id   activity id
	 * @param int|null $hide_sitewide item hidden sitewide or user specific
	 * @param array    $args          parent args
	 */
	public function manage_hidden_activity( $activity_id, $hide_sitewide, $args = array() ) {
		$suspend_args = wp_parse_args( $args, array(
			'item_id'   => $activity_id,
			'item_type' => BP_Suspend_Activity::$type,
		) );

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		BP_Core_Suspend::add_suspend( $suspend_args );
		$this->hide_related_content( $activity_id, $hide_sitewide, $args );
	}

	/**
	 * Un-hide related content of activity
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int      $activity_id   activity id
	 * @param int|null $hide_sitewide item hidden sitewide or user specific
	 * @param int      $force_all     un-hide for all users
	 * @param array    $args          parent args
	 */
	public function manage_unhidden_activity( $activity_id, $hide_sitewide, $force_all, $args = array() ) {

		$suspend_args = wp_parse_args( $args, array(
			'item_id'   => $activity_id,
			'item_type' => BP_Suspend_Activity::$type,
		) );

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		BP_Core_Suspend::remove_suspend( $suspend_args );
		$this->unhide_related_content( $activity_id, $hide_sitewide, $force_all, $args );
	}

	/**
	 * Get Activity's comment ids
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $activity_id activity id
	 *
	 * @return array
	 */
	protected function get_related_contents( $activity_id ) {

		$related_contents = array(
			BP_Suspend_Activity_Comment::$type => BP_Suspend_Activity_Comment::get_activity_comment_ids( $activity_id ),
		);

		if ( bp_is_active( 'document' ) ) {
			$related_contents[ BP_Suspend_Document::$type ] = BP_Suspend_Document::get_document_ids_meta( $activity_id, 'bp_activity_get_meta' );
		}

		if ( bp_is_active( 'media' ) ) {
			$related_contents[ BP_Suspend_Media::$type ] = BP_Suspend_Media::get_media_ids_meta( $activity_id, 'bp_activity_get_meta' );
		}

		return $related_contents;
	}
}
