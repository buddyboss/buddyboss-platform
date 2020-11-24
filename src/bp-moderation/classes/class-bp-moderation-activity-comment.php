<?php
/**
 * BuddyBoss Moderation Activity Comment Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
 *
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
		 * Moderation code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || ! bp_is_active( 'activity' ) || self::admin_bypass_check() ) {
			return;
		}

		// Search Component.
		add_filter( 'bp_activity_comments_search_join_sql', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_activity_comments_search_where_conditions', array( $this, 'update_where_sql' ), 10 );

		// Blocked template
		add_filter( 'bp_locate_template_names', array( $this, 'locate_blocked_template' ) );

		// Delete comment moderation data when actual comment is deleted.
		add_action( 'bp_activity_delete_comment', array( $this, 'delete_moderation_data' ), 10, 2 );
		add_action( 'bp_activity_deleted_activities', array( $this, 'delete_comment_moderation_data' ), 10 );
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int  $activity_comment_id activity id.
	 * @param bool $view_link           add view link
	 *
	 * @return string
	 */
	public static function get_content_excerpt( $activity_comment_id, $view_link = false ) {
		$activity = new BP_Activity_Activity( $activity_comment_id );

		$activity_content = ( ! empty( $activity->content ) ) ? $activity->content : '';

		if ( true === $view_link ) {
			$link = '<a href="' . esc_url( self::get_permalink( (int) $activity_comment_id ) ) . '">' . esc_html__( 'View',
					'buddyboss' ) . '</a>';;

			$activity_content = ( ! empty( $activity_content ) ) ? $activity_content . ' ' . $link : $link;
		}

		return $activity_content;
	}


	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $activity_id activity id.
	 *
	 * @return string
	 */
	public static function get_permalink( $activity_id ) {
		$url = bp_activity_get_permalink( $activity_id );

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

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['moderation_where'] = ' ( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions;
	}

	/**
	 * Get SQL for Exclude Blocked Members related activity comment
	 *
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
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
	 * Get blocked Activity's Comments ids related to blocked activity
	 * Note: Below link Not include direct blocked Activity comment
	 *
	 * @since BuddyBoss 2.0.0
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
	 * Update blocked comment template
	 *
	 * @since BuddyBoss 2.0.0
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

		if ( in_array( $activities_template->activity->current_comment->id, self::get_sitewide_hidden_ids(), true ) ||
		     bp_moderation_is_user_suspended( $activities_template->activity->current_comment->user_id, true ) ) {
			return 'activity/blocked-comment.php';
		}

		return $template_names;
	}

	/**
	 * Get All blocked Activity Comments ids.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @return array
	 */
	public static function get_sitewide_hidden_ids() {
		$hidden_all_activity_comment_ids = self::get_sitewide_hidden_item_ids( self::$moderation_type );

		$hidden_activity_comments_ids = self::get_sitewide_activity_comments_hidden_ids();
		if ( ! empty( $hidden_activity_comments_ids ) ) {
			$hidden_all_activity_comment_ids = array_merge( $hidden_all_activity_comment_ids,
				$hidden_activity_comments_ids );
		}

		return $hidden_all_activity_comment_ids;
	}

	/**
	 * Function to delete comment data on deleting the actual comment
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $activity_id activity id.
	 * @param int $comment_id  comment id.
	 */
	public function delete_moderation_data( $activity_id, $comment_id ) {
		if ( ! empty( $comment_id ) ) {
			$moderation_obj = new BP_Moderation( $comment_id, self::$moderation_type );
			if ( ! empty( $moderation_obj->id ) ) {
				$moderation_obj->delete( true );
			}
		}
	}

	/**
	 * Function to delete activity moderation data when actual activity is getting deleted.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $activity_deleted_ids activity ids array.
	 */
	public function delete_comment_moderation_data( $activity_deleted_ids ) {

		if ( ! empty( $activity_deleted_ids ) && is_array( $activity_deleted_ids ) ) {
			foreach ( $activity_deleted_ids as $activity_deleted_id ) {
				$moderation_obj = new BP_Moderation( $activity_deleted_id, self::$moderation_type );
				if ( ! empty( $moderation_obj->id ) ) {
					$moderation_obj->delete( true );
				}
			}
		}
	}
}
