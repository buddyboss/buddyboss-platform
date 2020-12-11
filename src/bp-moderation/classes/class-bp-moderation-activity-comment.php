<?php
/**
 * BuddyBoss Moderation Activity Comment Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
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

		// Check Component is disabled.
		if ( ! bp_is_active( 'activity' ) ) {
			return;
		}

		// Delete comment moderation data when actual comment is deleted.
		add_action( 'bp_activity_delete_comment', array( $this, 'sync_moderation_data_on_delete' ), 10, 2 );
		add_action( 'bp_activity_deleted_activities', array( $this, 'sync_comment_moderation_data_on_delete' ), 10 );

		/**
		 * Moderation code should not add for WordPress backend or IF Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() || ! bp_is_moderation_content_reporting_enable( 0, self::$moderation_type ) ) {
			return;
		}

		// Remove hidden/blocked users content.
		add_filter( 'bp_suspend_activity_comment_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_locate_template_names', array( $this, 'locate_blocked_template' ) );

		// button.
		add_filter( "bp_moderation_{$this->item_type}_button_sub_items", array( $this, 'update_button_sub_items' ) );

		add_filter( 'bp_activity_activity_pre_validate', array( $this, 'restrict_single_item' ), 10, 3 );
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
	 * Update where query Remove hidden/blocked user's Activities
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $where   Activity Where sql.
	 * @param object $suspend suspend object.
	 *
	 * @return array
	 */
	public function update_where_sql( $where, $suspend ) {
		$this->alias = $suspend->alias;

		$sql = $this->exclude_where_query();
		if ( ! empty( $sql ) ) {
			$where['moderation_where'] = $sql;
		}

		return $where;
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

	/**
	 * Function to modify button sub item
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $comment_id Comment id.
	 *
	 * @return array
	 */
	public function update_button_sub_items( $comment_id ) {
		$comment = new BP_Activity_Activity( $comment_id );

		if ( empty( $comment->id ) ) {
			return array();
		}

		$sub_items = array();
		$activity  = new BP_Activity_Activity( $comment->item_id );
		if ( ! empty( $activity->id ) && 'blogs' === $activity->component ) {
			$post_type = get_post_type( $activity->secondary_item_id );
			if ( ! empty( $post_type ) && 'post' === $post_type ) {
				$post_comment_id = bp_activity_get_meta( $comment->id, "bp_blogs_{$post_type}_comment_id", true );

				if ( ! empty( $post_comment_id ) ) {
					$sub_items['id']   = $post_comment_id;
					$sub_items['type'] = BP_Moderation_Comment::$moderation_type;
				}
			}
		}

		return $sub_items;
	}

	/**
	 * Validate the activity is valid or not.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param boolean $restrict Check the item is valid or not.
	 * @param object  $activity Current activity object.
	 *
	 * @return false
	 */
	public function restrict_single_item( $restrict, $activity ) {

		if ( 'activity_comment' === $activity->type && bp_moderation_is_content_hidden( (int) $activity->id, self::$moderation_type ) ) {
			return false;
		}

		return $restrict;
	}
}
