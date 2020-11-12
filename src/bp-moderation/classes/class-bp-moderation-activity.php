<?php
/**
 * BuddyBoss Moderation Activity Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Activity.
 *
 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		/**
		 * Moderation code should not add for WordPress backend & IF component is not active
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || ! bp_is_active( 'activity' ) ) {
			return;
		}

		add_filter( 'bp_activity_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_activity_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		// Search Query.
		add_filter( 'bp_activity_search_join_sql', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_activity_search_where_conditions', array( $this, 'update_where_sql' ), 10 );
	}

	/**
	 * Get blocked Activity ids including Blocked group and Forum/topic/reply related activity.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @return array
	 */
	public static function get_sitewide_hidden_ids() {
		$hidden_activity_id = self::get_sitewide_hidden_item_ids( self::$moderation_type );

		if ( bp_is_active( 'groups' ) ) {
			$hidden_group_activity_ids = self::get_hidden_group_activity_ids();
			$hidden_activity_id        = array_merge( $hidden_activity_id, $hidden_group_activity_ids );
		}

		if ( bp_is_active( 'forums' ) ) {
			$hidden_forum_activity_ids = self::get_hidden_forum_activity_ids();
			$hidden_activity_id        = array_merge( $hidden_activity_id, $hidden_forum_activity_ids );
		}

		return $hidden_activity_id;
	}

	/**
	 * Get Blocked group's activity ids
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @return array
	 */
	private static function get_hidden_group_activity_ids() {
		$hidden_group_activity_ids = array();

		if ( bp_is_active( 'forums' ) ) {
			$hidden_group_ids = BP_Moderation_Groups::get_sitewide_hidden_ids();
			if ( ! empty( $hidden_group_ids ) ) {
				$activities = BP_Activity_Activity::get(
					array(
						'moderation_query' => false,
						'per_page'         => 0,
						'fields'           => 'ids',
						'show_hidden'      => true,
						'filter'           => array(
							'primary_id' => $hidden_group_ids,
							'object'     => 'groups',
						),
					)
				);

				if ( ! empty( $activities['activities'] ) ) {
					$hidden_group_activity_ids = $activities['activities'];
				}
			}
		}

		return $hidden_group_activity_ids;
	}

	/**
	 * Get Blocked forum's activity ids
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @return array
	 */
	private static function get_hidden_forum_activity_ids() {
		$hidden_forum_activity_ids = array();

		if ( bp_is_active( 'groups' ) ) {
			$hidden_forums_ids        = BP_Moderation_Forums::get_sitewide_hidden_ids();
			$hidden_forum_topics_ids  = BP_Moderation_Forum_Topics::get_sitewide_hidden_ids();
			$hidden_forum_replies_ids = BP_Moderation_Forum_Replies::get_sitewide_hidden_ids();

			$hidden_ids = array_merge( $hidden_forums_ids, $hidden_forum_topics_ids, $hidden_forum_replies_ids );
			if ( ! empty( $hidden_ids ) ) {

				$activities = BP_Activity_Activity::get(
					array(
						'moderation_query' => false,
						'per_page'         => 0,
						'fields'           => 'ids',
						'show_hidden'      => true,
						'filter'           => array(
							'primary_id' => $hidden_ids,
							'object'     => 'bbpress',
						),
					)
				);

				if ( ! empty( $activities['activities'] ) ) {
					$hidden_forum_activity_ids = $activities['activities'];
				}
			}
		}

		return $hidden_forum_activity_ids;
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param integer $activity_comment_id Activity Comment id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $activity_comment_id ) {
		$activity = new BP_Activity_Activity( $activity_comment_id );

		return ( ! empty( $activity->user_id ) ) ? $activity->user_id : 0;
	}

	/**
	 * Get Content.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $activity_comment_id activity id.
	 *
	 * @return string
	 */
	public static function get_content_excerpt( $activity_comment_id ) {
		$activity = new BP_Activity_Activity( $activity_comment_id );

		return ( ! empty( $activity->content ) ) ? $activity->content : '';
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
		$content_types[ self::$moderation_type ] = __( 'Activity', 'buddyboss' );

		return $content_types;
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
		 * @since BuddyBoss 2.0.0
		 *
		 * @param array $where array of activity moderation where query.
		 */
		$where = apply_filters( 'bp_moderation_activity_get_where_conditions', $where );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['moderation_where'] = '( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions;
	}

	/**
	 * Get SQL for Exclude Blocked Members related activity
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @return string|bool
	 */
	private function exclude_member_activity_query() {
		$sql                = false;
		$hidden_members_ids = BP_Moderation_Members::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_members_ids ) ) {
			$sql = '( a.user_id NOT IN ( ' . implode( ',', $hidden_members_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get SQL for Exclude Blocked Groups related activity
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @return string|bool
	 */
	private function exclude_group_activity_query() {
		$sql              = false;
		$hidden_group_ids = BP_Moderation_Groups::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_group_ids ) ) {
			$sql = "( a.component != 'groups' OR a.item_id NOT IN ( " . implode( ',', $hidden_group_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get SQL for Exclude Blocked Forums, topic and replies related activity
	 *
	 * @since BuddyBoss 2.0.0
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
			$sql = "( a.component != 'bbpress' OR a.item_id NOT IN ( " . implode( ',', $hidden_ids ) . ' ) )';
		}

		return $sql;
	}
}
