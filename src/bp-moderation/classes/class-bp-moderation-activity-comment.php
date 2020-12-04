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

		// Delete comment moderation data when actual comment is deleted.
		add_action( 'bp_activity_delete_comment', array( $this, 'sync_moderation_data_on_delete' ), 10, 2 );
		add_action( 'bp_activity_deleted_activities', array( $this, 'sync_comment_moderation_data_on_delete' ), 10 );

		// Check Component is disabled
		if ( ! bp_is_active( 'activity' ) ){
			return;
		}

		/**
		 * Moderation code should not add for WordPress backend or IF Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		// Remove hidden/blocked users content
		add_filter( 'bp_suspend_activity_comment_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_locate_template_names', array( $this, 'locate_blocked_template' ) );


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
	 * Hide Moderated content
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $args Content data.
	 *
	 * @return BP_Moderation|WP_Error
	 */
	public static function hide( $args ) {
		return parent::hide( $args );
	}

	/**
	 * Unhide Moderated content
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $args Content data.
	 *
	 * @return BP_Moderation|WP_Error
	 */
	public static function unhide( $args ) {
		return parent::unhide( $args );
	}

	/**
	 * Delete Moderated report
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $args Content data.
	 *
	 * @return BP_Moderation|WP_Error
	 */
	public static function delete( $args ) {
		return parent::delete( $args );
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
	 * Add Moderation content type.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $content_types Supported Contents types.
	 *
	 * @return mixed
	 */
	public function add_content_types( $content_types ) {
		$content_types[ self::$moderation_type ] = __( 'Activity Comment', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Function to delete comment data on deleting the actual comment
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $activity_id activity id.
	 * @param int $comment_id  comment id.
	 */
	public function sync_moderation_data_on_delete( $activity_id, $comment_id ) {
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
	public function sync_comment_moderation_data_on_delete( $activity_deleted_ids ) {

		if ( ! empty( $activity_deleted_ids ) && is_array( $activity_deleted_ids ) ) {
			foreach ( $activity_deleted_ids as $activity_deleted_id ) {
				$moderation_obj = new BP_Moderation( $activity_deleted_id, self::$moderation_type );
				if ( ! empty( $moderation_obj->id ) ) {
					$moderation_obj->delete( true );
				}
			}
		}
	}

	/**
	 * Update join query to Remove hidden/blocked Activity's Comments
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $join_sql Activity's Comment Join sql.
	 * @param object $suspend  suspend object
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $suspend ) {
		$this->alias = $suspend->alias;
		$join_sql    .= $this->exclude_joint_query();

		return $join_sql;
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
			if ( ! is_array( $template_names ) || ! in_array( 'activity/comment.php', $template_names, true ) ) {
				return $template_names;
			}
		}

		if ( BP_Core_Suspend::check_hidden_content( $activities_template->activity->current_comment->id, self::$moderation_type ) ) {
			return 'activity/blocked-comment.php';
		}

		return $template_names;
	}
}
