<?php
/**
 * BuddyBoss Moderation Activity Comment Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Activity Comment.
 *
 * @since BuddyBoss 1.5.6
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
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		/**
		 * Moderation code should not add for WordPress backend or IF Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		/**
		 * If moderation setting enabled for this content then it'll filter hidden content.
		 * And IF moderation setting enabled for member then it'll filter blocked user content.
		 */
		add_filter( 'bp_suspend_activity_comment_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
		add_filter( 'bp_locate_template_names', array( $this, 'locate_blocked_template' ) );

		add_filter( 'bp_activity_comment_content', array( $this, 'bb_activity_comment_remove_mention_link' ), 10, 1 );

		// Code after below condition should not execute if moderation setting for this content disabled.
		if ( ! bp_is_moderation_content_reporting_enable( 0, self::$moderation_type ) ) {
			return;
		}

		// Update report button.
		add_filter( "bp_moderation_{$this->item_type}_button_sub_items", array( $this, 'update_button_sub_items' ) );

		// Validate item before proceed.
		add_filter( "bp_moderation_{$this->item_type}_validate", array( $this, 'validate_single_item' ), 10, 2 );

		// Report button text.
		add_filter( "bb_moderation_{$this->item_type}_report_button_text", array( $this, 'report_button_text' ), 10, 2 );
		add_filter( "bb_moderation_{$this->item_type}_reported_button_text", array( $this, 'report_button_text' ), 10, 2 );

		// Report popup content type.
		add_filter( "bp_moderation_{$this->item_type}_report_content_type", array( $this, 'report_content_type' ), 10, 2 );
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 1.5.6
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
	 * @since BuddyBoss 1.5.6
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
	 * @since BuddyBoss 1.5.6
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
	 * Update where query Remove hidden/blocked user's Activities
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $where   Activity Where sql.
	 * @param object $suspend suspend object.
	 *
	 * @return array
	 */
	public function update_where_sql( $where, $suspend ) {
		$this->alias = $suspend->alias;

		// Allow to search hasblocked members activity comment.
		$blocked_user_query = true;
		if ( function_exists( 'bb_did_filter' ) && bb_did_filter( 'bp_activity_comments_search_where_conditions' ) ) {
			$blocked_user_query = false;
		}

		$sql = $this->exclude_where_query( $blocked_user_query );
		if ( ! empty( $sql ) ) {
			$where['moderation_where'] = $sql;
		}

		// Allow to search activity comment for current members which is added by isblocked member activity.
		if ( false === $blocked_user_query ) {

			// If isblocked/hasblocked members activity hide then all comment of that activities should not be searchable.
			$is_blocked_item_ids = $this->bb_is_blocked_activity_comment_ids();
			if ( ! empty( $is_blocked_item_ids ) ) {
				$where['moderation_where'] .= ' AND ( a.id NOT IN ( ' . implode( ",", $is_blocked_item_ids ) . ') )';
			}
		}

		return $where;
	}

	/**
	 * Update blocked comment template
	 *
	 * @since BuddyBoss 1.5.6
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

		if (
			// If isBlocked and comment is hidden then blocked comment template will call.
			$this->is_content_hidden( $activities_template->activity->current_comment->id ) ||
			bb_moderation_is_user_blocked_by( $activities_template->activity->current_comment->user_id )
		) {
			return 'activity/blocked-comment.php';
		}

		return $template_names;
	}

	/**
	 * Function to modify button sub item
	 *
	 * @since BuddyBoss 1.5.6
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
	 * Filter to check the activity is valid or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param bool   $retval  Check item is valid or not.
	 * @param string $item_id item id.
	 *
	 * @return bool
	 */
	public function validate_single_item( $retval, $item_id ) {
		if ( empty( $item_id ) ) {
			return $retval;
		}

		$activity = new BP_Activity_Activity( (int) $item_id );

		if ( empty( $activity ) || empty( $activity->id ) ) {
			return false;
		}

		return $retval;
	}

	/**
	 * Check content is hidden or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @return bool
	 */
	protected function is_content_hidden( $item_id ) {

		$author_id = self::get_content_owner_id( $item_id );

		if ( ( $this->is_member_blocking_enabled() && ! empty( $author_id ) && ! bp_moderation_is_user_suspended( $author_id ) && bp_moderation_is_user_blocked( $author_id ) ) ||
			 ( $this->is_reporting_enabled() && BP_Core_Suspend::check_hidden_content( $item_id, $this->item_type ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Function to change report button text.
	 *
	 * @since BuddyBoss 1.7.3
	 *
	 * @param string $button_text Button text.
	 * @param int    $item_id     Item id.
	 *
	 * @return string
	 */
	public function report_button_text( $button_text, $item_id ) {

		$comment = new BP_Activity_Activity( $item_id );

		if ( empty( $comment->id ) ) {
			return $button_text;
		}

		$button_text = esc_html__( 'Report Comment', 'buddyboss' );

		return $button_text;
	}

	/**
	 * Function to change report type.
	 *
	 * @since BuddyBoss 1.7.3
	 *
	 * @param string $content_type Button text.
	 * @param int    $item_id      Item id.
	 *
	 * @return string
	 */
	public function report_content_type( $content_type, $item_id ) {
		$comment = new BP_Activity_Activity( $item_id );

		if ( empty( $comment->id ) ) {
			return $content_type;
		}

		$content_type = esc_html__( 'Comment', 'buddyboss' );

		return $content_type;
	}

	/**
	 * Remove mentioned link from activity comment.
	 *
	 * @since BuddyBoss 2.2.7
	 *
	 * @param string $content Activity comment.
	 *
	 * @return string
	 */
	public function bb_activity_comment_remove_mention_link( $content ) {
		if ( empty( $content ) ) {
			return $content;
		}

		$content = bb_moderation_remove_mention_link( $content );

		return $content;
	}

	/**
	 * Function to get activity comment id of main parent activity id which is created by blocked members.
	 * If this activity is hidden then will store that activity comment id in array and return as $blocked_item_ids.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array
	 */
	public function bb_is_blocked_activity_comment_ids() {
		static $cache = array();
		static $parent_comment_author_cache = array();

		$cache_key = 'bb_is_blocked_activity_comment_ids';
		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		global $wpdb, $bp;
		$sql              = "SELECT DISTINCT a.id FROM {$bp->table_prefix}bp_activity a";
		$sql              .= " WHERE a.type = 'activity_comment'";
		$results          = $wpdb->get_col( $sql );
		$blocked_item_ids = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $item_id ) {
				// Fetch activity data based on comment id.
				$result                    = $this->bb_get_activity_data( $item_id );
				$current_comment_author_id = $result->current_comment_author_id;
				$activity_author_id        = $result->activity_author_id;
				$parent_activity_component = $result->parent_activity_component;

				// Fetch main parent comment id based on comment id.
				$parent_comment_id = $this->bb_get_main_parent_comment_id( $item_id );

				// Implement static cache.
				$parent_comment_author_cache_key = 'bb_parent_comment_author_id';
				if ( ! isset( $parent_comment_author_cache[ $parent_comment_author_cache_key ] ) ) {
					// SQL query to fetch parent comment author id.
					$parent_comment_author_id                                        = $wpdb->get_var(
						$wpdb->prepare( "SELECT user_id FROM {$wpdb->prefix}bp_activity WHERE id = %d", $parent_comment_id )
					);
					$parent_comment_author_cache[ $parent_comment_author_cache_key ] = $parent_comment_author_id;
				}
				$parent_comment_author_id = $parent_comment_author_cache[ $parent_comment_author_cache_key ];

				if (
					'groups' !== $parent_activity_component &&
					(
						! empty( $activity_author_id ) &&
						(
							bb_moderation_is_user_blocked_by( $activity_author_id ) ||
							bp_moderation_is_user_blocked( $activity_author_id ) ||
							bp_moderation_is_user_suspended( $activity_author_id )
						) ||
						(
							(int) get_current_user_id() !== (int) $activity_author_id &&
							(
								(
									! bb_moderation_is_user_blocked_by( $activity_author_id ) ||
									! bp_moderation_is_user_blocked( $activity_author_id ) ||
									! bp_moderation_is_user_suspended( $activity_author_id )
								) &&
								(
									! empty( $parent_comment_author_id ) &&
									(
										bp_moderation_is_user_blocked( $parent_comment_author_id ) ||
										bb_moderation_is_user_blocked_by( $parent_comment_author_id )
									)
								) ||
								(
									(
										! bp_moderation_is_user_blocked( $parent_comment_author_id ) ||
										! bb_moderation_is_user_blocked_by( $parent_comment_author_id )
									) &&
									(
										! empty( $current_comment_author_id ) &&
										bp_moderation_is_user_blocked( $current_comment_author_id ) ||
										bb_moderation_is_user_blocked_by( $current_comment_author_id )
									)
								)
							)
						)
					)
				) {
					$blocked_item_ids[] = $item_id;
				}
			}
		}
		$cache[ $cache_key ] = $blocked_item_ids;

		return $blocked_item_ids;
	}

	/**
	 * Fetch activity and current comment data based on specific comment id.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $comment_id Current comment id.
	 *
	 * @return mixed
	 */
	public function bb_get_activity_data( $comment_id ) {
		static $cache = array();

		$cache_key = 'bb_get_activity_data_' . $comment_id;
		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		global $wpdb, $bp;
		$query = $wpdb->prepare(
			"SELECT
			        a.id AS current_comment_id,
			        a.user_id AS current_comment_author_id,
			        CASE
			            WHEN a.item_id = 0 AND a.secondary_item_id = 0 THEN NULL
			            WHEN a.item_id = a.secondary_item_id THEN a.item_id
			            ELSE a2.item_id
			        END AS activity_id,
			        COALESCE(a3.user_id, a2.user_id) AS activity_author_id,
			        a4.component AS parent_activity_component
			    FROM
			        {$bp->table_prefix}bp_activity a
			        LEFT JOIN {$bp->table_prefix}bp_activity a2 ON a.secondary_item_id = a2.id
			        LEFT JOIN {$bp->table_prefix}bp_activity a3 ON a.item_id = a3.id
			        LEFT JOIN {$bp->table_prefix}bp_activity a4 ON (
			            CASE
			                WHEN a.item_id = 0 AND a.secondary_item_id = 0 THEN NULL
			                WHEN a.item_id = a.secondary_item_id THEN a.item_id
			                ELSE a2.item_id
			            END
			        ) = a4.id
			    WHERE
			        a.id = %d",
			$comment_id
		);
		$result = $wpdb->get_row( $query );

		$cache[ $cache_key ] = $result;

		return $result;
	}

	/**
	 * Fetch main parent comment id based on specific comment id.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $comment_id Current comment id.
	 *
	 * @return mixed
	 */
	public function bb_get_main_parent_comment_id( $comment_id ) {
		static $cache = array();

		$cache_key = 'bb_main_parent_comment_id_' . $comment_id;

		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		global $wpdb, $bp;
		$query  = $wpdb->prepare( "SELECT item_id, secondary_item_id FROM {$bp->table_prefix}bp_activity WHERE id = %d ", $comment_id );
		$result = $wpdb->get_row( $query );

		if ( empty( $result ) || $result->item_id === $result->secondary_item_id ) {
			$cache[ $cache_key ] = $comment_id;
			return $comment_id;
		} else {
			$parent_comment_id = $this->bb_get_main_parent_comment_id( $result->secondary_item_id );
			$cache[ $cache_key ] = $parent_comment_id;
			return $parent_comment_id;
		}
	}
}
