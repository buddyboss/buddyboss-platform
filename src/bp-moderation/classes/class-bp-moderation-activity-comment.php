<?php
/**
 * BuddyBoss Moderation Activity Comment Classes
 *
 * @package BuddyBoss\Moderation
 *
 * @since BuddyBoss 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Activity Comment.
 *
 * @since BuddyBoss 2.0.0
 */
class BP_Moderation_Activity_Comment extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'activity_comment';

	/**
	 * BP_Moderation_Activity_Comment constructor.
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

		// Search Component.
		add_filter( 'bp_activity_comments_search_join_sql', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_activity_comments_search_where_conditions', array( $this, 'update_where_sql' ), 10 );

		add_filter( 'bp_locate_template_names', array( $this, 'locate_blocked_template' ) );
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
		$content_types[ self::$moderation_type ] = __( 'Activity Comments', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Prepare activity Comment Join SQL query to filter blocked Activity Comment
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $join_sql Activity Comment Join sql.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql ) {
		$join_sql .= $this->exclude_joint_query( 'a.id' );

		return $join_sql;
	}

	/**
	 * Prepare activity Comment Where SQL query to filter blocked Activity Comment
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $where_conditions Activity Comment Where sql.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions ) {
		$where                           = array();
		$where['activity_comment_where'] = $this->exclude_where_query();

		/**
		 * Exclude Blocked Member activity Comment [ it'll Show placeholder for blocked content ]
		 * Activity comment should be hidden if it's search query.
		 */
		$members_where = $this->exclude_member_activity_comment_query();
		if ( $members_where ) {
			$where['members_where'] = $members_where;
		}

		/**
		 * Exclude Blocked activity's activity Comment
		 */
		$activity_where = $this->exclude_activity_activity_comment_query();
		if ( $activity_where ) {
			$where['activity_where'] = $activity_where;
		}

		/**
		 * Filters the activity comment Moderation Where SQL statement.
		 *
		 * @since BuddyBoss 2.0.0
		 *
		 * @param array $where array of activity comment moderation where query.
		 */
		$where = apply_filters( 'bp_moderation_activity_comment_get_where_conditions', $where );

		$where_conditions['moderation_where'] = ' ( ' . implode( ' AND ', $where ) . ' )';

		return $where_conditions;
	}

	/**
	 * Get SQL for Exclude Blocked Members related activity comment
	 *
	 * @return string|bool
	 */
	private function exclude_member_activity_comment_query() {
		$sql                = false;
		$hidden_members_ids = BP_Moderation_Members::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_members_ids ) ) {
			$sql = '( a.user_id NOT IN ( ' . implode( ',', $hidden_members_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get SQL for Exclude Blocked Activity related activity comment
	 *
	 * @return string|bool
	 */
	private function exclude_activity_activity_comment_query() {
		$sql                         = false;
		$hidden_activity_ids         = BP_Moderation_Activity::get_sitewide_hidden_ids();
		$hidden_activity_comment_ids = self::get_sitewide_activity_comments_hidden_ids();
		if ( ! empty( $hidden_activity_ids ) ) {
			$sql = '( a.item_id NOT IN ( ' . implode( ',', $hidden_activity_ids ) . ' ) ';
			if ( ! empty( $hidden_activity_comment_ids ) ) {
				$sql .= ' AND a.secondary_item_id NOT IN ( ' . implode( ',', $hidden_activity_comment_ids ) . ' ) ';
			}
			$sql .= ')';
		}

		return $sql;
	}

	/**
	 * Update blocked comment template
	 *
	 * @param string $template_names Template name.
	 *
	 * @return string
	 */
	public function locate_blocked_template( $template_names ) {
		global $activities_template;

		if ( 'activity/comment.php' !== $template_names ) {
			return $template_names;
		}

		if ( in_array( $activities_template->activity->current_comment->id, self::get_sitewide_hidden_ids(), true ) ) {
			return 'activity/blocked-comment.php';
		}

		if ( bp_is_moderation_member_blocking_enable( 0 ) && in_array( $activities_template->activity->current_comment->user_id, BP_Moderation_Members::get_sitewide_hidden_ids(), true ) ) {
			return 'activity/blocked-user-comment.php';
		}

		return $template_names;
	}

	/**
	 * Get All blocked Activity Comments ids.
	 *
	 * @return array
	 */
	public static function get_sitewide_hidden_ids() {
		$hidden_all_activity_comment_ids = self::get_sitewide_hidden_item_ids( self::$moderation_type );

		$hidden_activity_comments_ids = self::get_sitewide_activity_comments_hidden_ids();
		if ( ! empty( $hidden_activity_comments_ids ) ) {
			$hidden_all_activity_comment_ids = array_merge( $hidden_all_activity_comment_ids, $hidden_activity_comments_ids );
		}

		return $hidden_all_activity_comment_ids;
	}

	/**
	 * Get blocked Activity's Comments ids related to blocked activity
	 * Note: Below link Not include direct blocked Activity comment
	 *
	 * @return array
	 */
	public static function get_sitewide_activity_comments_hidden_ids() {
		$hidden_activity_comment_ids = array();

		$hidden_activity_ids = BP_Moderation_Activity::get_sitewide_hidden_ids();
		foreach ( $hidden_activity_ids as $hidden_activity_id ) {
			$activity_comments     = BP_Activity_Activity::get_child_comments( $hidden_activity_id );
			$activity_comments_ids = wp_list_pluck( $activity_comments, 'id' );
			if ( ! empty( $activity_comments_ids ) ) {
				$hidden_activity_ids         = array_merge( $hidden_activity_ids, $activity_comments_ids );
				$hidden_activity_comment_ids = array_merge( $hidden_activity_comment_ids, $activity_comments_ids );
			}
		}

		return $hidden_activity_comment_ids;
	}

	/**
	 * Get Content owner id.
	 *
	 * @param integer $activity_id Activity id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $activity_id ) {
		$activity = new BP_Activity_Activity( $activity_id );

		return ( ! empty( $activity->user_id ) ) ? $activity->user_id : 0;
	}

	/**
	 * Get Content.
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
}
