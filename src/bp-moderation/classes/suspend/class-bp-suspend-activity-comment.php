<?php
/**
 * BuddyBoss Suspend Activity Comment Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Suspend
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend Activity comment.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Suspend_Activity_Comment extends BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $type = 'activity_comment';

	/**
	 * BP_Suspend_Activity_Comment constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		$this->item_type = self::$type;

		// Manage hidden list.
		add_action( "bp_suspend_hide_{$this->item_type}", array( $this, 'manage_hidden_activity_comment' ), 10, 3 );
		add_action( "bp_suspend_unhide_{$this->item_type}", array( $this, 'manage_unhidden_activity_comment' ), 10, 4 );

		// Add moderation data when actual activity added.
		add_action( 'bp_activity_after_save', array( $this, 'sync_moderation_data_on_save' ), 10, 1 );

		// Delete moderation data when actual activity deleted.
		add_action( 'bp_activity_after_delete', array( $this, 'sync_moderation_data_on_delete' ), 10, 1 );

		/**
		 * Suspend code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		add_filter( 'bp_activity_comments_search_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_activity_comments_search_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_locate_template_names', array( $this, 'locate_blocked_template' ) );
	}

	/**
	 * Get Blocked member's activity ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $member_id member id.
	 *
	 * @return array
	 */
	public static function get_member_activity_comment_ids( $member_id ) {
		$activities_ids = array();

		$activities = BP_Activity_Activity::get(
			array(
				'moderation_query' => false,
				'per_page'         => 0,
				'fields'           => 'ids',
				'show_hidden'      => true,
				'display_comments' => true,
				'filter'           => array(
					'user_id' => $member_id,
					'action'  => 'activity_comment',
				),
			)
		);

		if ( ! empty( $activities['activities'] ) ) {
			$activities_ids = $activities['activities'];
		}

		return $activities_ids;
	}

	/**
	 * Prepare activity Join SQL query to filter blocked Activity
	 *
	 * @since BuddyBoss 1.5.6
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
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $join_sql Join sql query
		 * @param array $class    current class object.
		 */
		$join_sql = apply_filters( 'bp_suspend_activity_comment_get_join', $join_sql, $this );

		return $join_sql;
	}

	/**
	 * Prepare activity comment Where SQL query to filter blocked Activity
	 *
	 * @since BuddyBoss 1.5.6
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
		 * Filters the hidden activity comment Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $where Query to hide suspended user's activity comment.
		 * @param array $class current class object.
		 */
		$where = apply_filters( 'bp_suspend_activity_comment_get_where_conditions', $where, $this );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['suspend_where'] = '( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions;
	}

	/**
	 * Hide related content of activity comment
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $acomment_id   activity comment id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param array    $args          parent args.
	 */
	public function manage_hidden_activity_comment( $acomment_id, $hide_sitewide, $args = array() ) {
		global $bp_background_updater;

		$suspend_args = wp_parse_args(
			$args,
			array(
				'item_id'   => $acomment_id,
				'item_type' => self::$type,
			)
		);

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		$suspend_args = self::validate_keys( $suspend_args );

		BP_Core_Suspend::add_suspend( $suspend_args );

		if ( $this->backgroup_diabled || ! empty( $args ) ) {
			$this->hide_related_content( $acomment_id, $hide_sitewide, $args );
		} else {
			$bp_background_updater->push_to_queue(
				array(
					'callback' => array( $this, 'hide_related_content' ),
					'args'     => array( $acomment_id, $hide_sitewide, $args ),
				)
			);
			$bp_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Un-hide related content of activity
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $acomment_id   activity comment id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param int      $force_all     un-hide for all users.
	 * @param array    $args          parent args.
	 */
	public function manage_unhidden_activity_comment( $acomment_id, $hide_sitewide, $force_all, $args = array() ) {
		global $bp_background_updater;

		$suspend_args = wp_parse_args(
			$args,
			array(
				'item_id'   => $acomment_id,
				'item_type' => self::$type,
			)
		);

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		if (
			isset( $suspend_args['author_compare'] ) &&
			true === (bool) $suspend_args['author_compare'] &&
			isset( $suspend_args['type'] ) &&
			$suspend_args['type'] !== self::$type
		) {
			$ac_comment_user = BP_Moderation_Activity_Comment::get_content_owner_id( $acomment_id );
			if ( isset( $suspend_args['blocked_user'] ) && $ac_comment_user === $suspend_args['blocked_user'] ) {
				unset( $suspend_args['blocked_user'] );
			}
		}

		$suspend_args = self::validate_keys( $suspend_args );

		BP_Core_Suspend::remove_suspend( $suspend_args );

		if ( $this->backgroup_diabled || ! empty( $args ) ) {
			$this->unhide_related_content( $acomment_id, $hide_sitewide, $force_all, $args );
		} else {
			$bp_background_updater->push_to_queue(
				array(
					'callback' => array( $this, 'unhide_related_content' ),
					'args'     => array( $acomment_id, $hide_sitewide, $force_all, $args ),
				)
			);
			$bp_background_updater->save()->schedule_event();
		}
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

		if ( BP_Core_Suspend::check_suspended_content( $activities_template->activity->current_comment->id, self::$type ) ) {
			return 'activity/blocked-comment.php';
		}

		$author_id = BP_Moderation_Activity_Comment::get_content_owner_id( $activities_template->activity->current_comment->id );
		if ( bp_moderation_is_user_suspended( $author_id ) ) {
			return 'activity/blocked-comment.php';
		}

		return $template_names;
	}

	/**
	 * Get Activity's comment ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int   $acomment_id activity comment id.
	 * @param array $args        parent args.
	 *
	 * @return array
	 */
	protected function get_related_contents( $acomment_id, $args = array() ) {

		$related_contents = array();

		// related activity comment only hide if parent activity hide or comment's/parent activity's author blocked or suspended.
		if ( ! empty( $args ) && ( isset( $args['blocked_user'] ) || isset( $args['user_suspended'] ) || isset( $args['hide_parent'] ) ) ) {
			$related_contents[ self::$type ] = self::get_activity_comment_ids( $acomment_id );
		}

		if ( bp_is_active( 'document' ) ) {
			$related_contents[ BP_Suspend_Document::$type ] = BP_Suspend_Document::get_document_ids_meta( $acomment_id, 'bp_activity_get_meta' );
		}

		if ( bp_is_active( 'media' ) ) {
			$related_contents[ BP_Suspend_Media::$type ] = BP_Suspend_Media::get_media_ids_meta( $acomment_id, 'bp_activity_get_meta' );
		}

		return $related_contents;
	}

	/**
	 * Get Blocked activity's comment ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $activity_id Activity id.
	 *
	 * @return array
	 */
	public static function get_activity_comment_ids( $activity_id ) {

		$comments_ids = array();

		$activity_comments = BP_Activity_Activity::get_child_comments( $activity_id );

		if ( ! empty( $activity_comments ) ) {
			$comments_ids = wp_list_pluck( $activity_comments, 'id' );
		}

		return $comments_ids;
	}

	/**
	 * Update the suspend table to add new entries.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param BP_Activity_Activity $activity Current instance of activity item being saved. Passed by reference.
	 */
	public function sync_moderation_data_on_save( $activity ) {

		if ( empty( $activity ) || empty( $activity->id ) ) {
			return;
		}

		if ( 'activity_comment' !== $activity->type ) {
			return;
		}

		$sub_items     = bp_moderation_get_sub_items( $activity->id, BP_Moderation_Activity::$moderation_type );
		$item_sub_id   = isset( $sub_items['id'] ) ? $sub_items['id'] : $activity->item_id;
		$item_sub_type = isset( $sub_items['type'] ) ? $sub_items['type'] : BP_Moderation_Activity::$moderation_type;

		$suspended_record = BP_Core_Suspend::get_recode( $item_sub_id, $item_sub_type );

		if ( empty( $suspended_record ) ) {
			$suspended_record = BP_Core_Suspend::get_recode( $activity->user_id, BP_Moderation_Members::$moderation_type );
		}

		if ( empty( $suspended_record ) ) {
			return;
		}

		self::handle_new_suspend_entry( $suspended_record, $activity->id, $activity->user_id );
	}

	/**
	 * Update the suspend table to delete an activity.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $activities Array of activities.
	 */
	public function sync_moderation_data_on_delete( $activities ) {

		if ( empty( $activities ) ) {
			return;
		}

		if ( is_array( $activities ) ) {
			foreach ( $activities as $activity ) {

				if ( 'activity_comment' !== $activity->type ) {
					continue;
				}

				BP_Core_Suspend::delete_suspend( $activity->id, $this->item_type );
			}
		}

	}
}
