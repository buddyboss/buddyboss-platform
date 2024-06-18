<?php
/**
 * BuddyBoss Suspend Video Classes
 *
 * @since   BuddyBoss 1.7.0
 * @package BuddyBoss\Suspend
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend Video.
 *
 * @since BuddyBoss 1.7.0
 */
class BP_Suspend_Video extends BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $type = 'video';

	/**
	 * BP_Suspend_Video constructor.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function __construct() {

		$this->item_type = self::$type;

		// Manage hidden list.
		add_action( "bp_suspend_hide_{$this->item_type}", array( $this, 'manage_hidden_video' ), 10, 3 );
		add_action( "bp_suspend_unhide_{$this->item_type}", array( $this, 'manage_unhidden_video' ), 10, 4 );

		// Add moderation data when video is added.
		add_action( 'bp_video_after_save', array( $this, 'sync_moderation_data_on_save' ), 10, 1 );

		// Delete moderation data when video is deleted.
		add_action( 'bp_video_after_delete', array( $this, 'sync_moderation_data_on_delete' ), 10, 1 );

		/**
		 * Suspend code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		$this->alias = $this->alias . 'v'; // v = Video.

		// modify in videos count for album.
		add_filter( 'bp_media_get_join_sql', array( $this, 'update_join_media_sql' ), 10, 2 );
		add_filter( 'bp_media_get_where_conditions', array( $this, 'update_where_media_sql' ), 10, 2 );

		// modify in group videos count for album.
		add_filter( 'bp_media_get_join_count_sql', array( $this, 'update_join_media_sql' ), 10, 2 );
		add_filter( 'bp_media_get_where_count_conditions', array( $this, 'update_where_media_sql' ), 10, 2 );

		add_filter( 'bp_video_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_video_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_video_search_join_sql_video', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_video_search_where_conditions_video', array( $this, 'update_where_sql' ), 10, 2 );

		if ( bp_is_active( 'activity' ) ) {
			add_filter( 'bb_moderation_restrict_single_item_' . BP_Suspend_Activity::$type, array( $this, 'unbind_restrict_single_item' ), 10, 1 );
			add_action( 'bb_moderation_' . BP_Suspend_Activity::$type . '_before_delete_suspend', array( $this, 'update_suspend_data_on_activity_delete' ) );
			add_action( 'bb_moderation_' . BP_Suspend_Activity_Comment::$type . '_before_delete_suspend', array( $this, 'update_suspend_data_on_activity_delete' ) );
		}
	}

	/**
	 * Get Blocked member's video ids
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param int    $member_id member id.
	 * @param string $action    Action name to perform.
	 * @param int    $page      Number of page.
	 *
	 * @return array
	 */
	public static function get_member_video_ids( $member_id, $action = '', $page = - 1 ) {
		$video_ids = array();

		$args = array(
			'moderation_query' => false,
			'per_page'         => 0,
			'fields'           => 'ids',
			'user_id'          => $member_id,
		);

		if ( $page > 0 ) {
			$args['per_page'] = self::$item_per_page;
			$args['page']     = $page;
		}

		$videos = bp_video_get( $args );

		if ( ! empty( $videos['videos'] ) ) {
			$video_ids = $videos['videos'];
		}

		if ( 'hide' === $action && ! empty( $video_ids ) ) {
			foreach ( $video_ids as $k => $video_id ) {
				if ( BP_Core_Suspend::check_suspended_content( $video_id, self::$type, true ) ) {
					unset( $video_ids[ $k ] );
				}
			}
		}

		return $video_ids;
	}

	/**
	 * Get Blocked group's video ids
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param int $group_id group id.
	 * @param int $page     Number of page.
	 *
	 * @return array
	 */
	public static function get_group_video_ids( $group_id, $page = - 1 ) {
		$video_ids = array();

		$args = array(
			'moderation_query' => false,
			'per_page'         => 0,
			'fields'           => 'ids',
			'group_id'         => $group_id,
		);

		if ( $page > 0 ) {
			$args['per_page'] = self::$item_per_page;
			$args['page']     = $page;
		}

		$videos = bp_video_get( $args );

		if ( ! empty( $videos['videos'] ) ) {
			$video_ids = $videos['videos'];
		}

		return $video_ids;
	}

	/**
	 * Get Video ids of blocked item [ Forums/topics/replies/activity etc ] from meta
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param int    $item_id  item id.
	 * @param string $function Function Name to get meta.
	 * @param string $action   Action name to perform.
	 *
	 * @return array Video IDs
	 */
	public static function get_video_ids_meta( $item_id, $function = 'get_post_meta', $action = '' ) {
		$video_ids = array();

		if ( function_exists( $function ) ) {
			if ( ! empty( $item_id ) ) {
				$post_video = $function( $item_id, 'bp_video_ids', true );

				if ( empty( $post_video ) ) {
					$post_video = BP_Video::get_activity_video_id( $item_id );
				}

				if ( ! empty( $post_video ) ) {
					$video_ids = wp_parse_id_list( $post_video );
				}
			}
		}

		if ( 'hide' === $action && ! empty( $video_ids ) ) {
			foreach ( $video_ids as $k => $video_id ) {
				if ( BP_Core_Suspend::check_hidden_content( $video_id, self::$type, true ) ) {
					unset( $video_ids[ $k ] );
				}
			}
		}

		if ( 'unhide' === $action && ! empty( $video_ids ) ) {
			foreach ( $video_ids as $k => $video_id ) {
				if ( self::is_content_reported_hidden( $video_id, self::$type ) ) {
					unset( $video_ids[ $k ] );
				}
			}
		}

		return $video_ids;
	}

	/**
	 * Prepare video Join SQL query to filter blocked Video for Album only.
	 *
	 * @since BuddyBoss 1.7.0.1
	 *
	 * @param string $join_sql Video Join sql.
	 * @param array  $args     Query arguments.
	 *
	 * @return string Join sql
	 */
	public function update_join_media_sql( $join_sql, $args = array() ) {

		if ( empty( $args['album_id'] ) ) {
			return $join_sql;
		}

		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $join_sql;
		}

		$join_sql .= $this->exclude_joint_query( 'm.id' );

		/**
		 * Filters the hidden Video Where SQL statement.
		 *
		 * @since BuddyBoss 1.7.0.1
		 *
		 * @param array $join_sql Join sql query
		 * @param array $class    current class object.
		 */
		$join_sql = apply_filters( 'bb_suspend_media_get_join', $join_sql, $this );

		return $join_sql;
	}

	/**
	 * Prepare video Join SQL query to filter blocked Video
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param string $join_sql Video Join sql.
	 * @param array  $args     Query arguments.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $args = array() ) {

		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $join_sql;
		}

		$join_sql .= $this->exclude_joint_query( 'm.id' );

		/**
		 * Filters the hidden Video Where SQL statement.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param array $join_sql Join sql query
		 * @param array $class    current class object.
		 */
		$join_sql = apply_filters( 'bp_suspend_video_get_join', $join_sql, $this );

		return $join_sql;
	}

	/**
	 * Prepare video Where SQL query to filter blocked Video for Album only.
	 *
	 * @since BuddyBoss 1.7.0.1
	 *
	 * @param array $where_conditions Video Where sql.
	 * @param array $args             Query arguments.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_media_sql( $where_conditions, $args = array() ) {
		if ( empty( $args['album_id'] ) ) {
			return $where_conditions;
		}

		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $where_conditions;
		}

		$where                  = array();
		$where['suspend_where'] = $this->exclude_where_query();

		/**
		 * Filters the hidden video Where SQL statement.
		 *
		 * @since BuddyBoss 1.7.0.1
		 *
		 * @since BuddyBoss 2.3.80
		 * Introduce new params $args as Media args.
		 *
		 * @param array $where Query to hide suspended user's video.
		 * @param array $class current class object.
		 * @param array $args  Media args.
		 */
		$where = apply_filters( 'bp_suspend_media_get_where_conditions', $where, $this, $args );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['video_suspend_where'] = '( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions;
	}

	/**
	 * Prepare video Where SQL query to filter blocked Video
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param array $where_conditions Video Where sql.
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
		 * Filters the hidden video Where SQL statement.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param array $where Query to hide suspended user's video.
		 * @param array $class current class object.
		 */
		$where = apply_filters( 'bp_suspend_video_get_where_conditions', $where, $this );

		if ( ! empty( array_filter( $where ) ) ) {
			$exclude_group_sql = '';
			// Allow group medias from blocked/suspended users.
			if ( bp_is_active( 'groups' ) ) {
				$exclude_group_sql = ' OR m.privacy = "grouponly" ';
			}
			$exclude_group_sql .= ' OR ( m.privacy = "comment" OR m.privacy = "forums" ) ';

			$where_conditions['suspend_where'] = '( ( ' . implode( ' AND ', $where ) . ' ) ' . $exclude_group_sql . ' )';
		}

		return $where_conditions;
	}

	/**
	 * Hide related content of video
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param int      $video_id      video id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param array    $args          parent args.
	 */
	public function manage_hidden_video( $video_id, $hide_sitewide, $args = array() ) {
		global $bb_background_updater;

		if ( empty( $video_id ) ) {
			return;
		}

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $video_id,
				'item_type' => self::$type,
			)
		);

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		$suspend_args = self::validate_keys( $suspend_args );

		$group_name_args = array_merge(
			$suspend_args,
			array(
				'custom_action' => 'hide',
			)
		);
		$group_name      = $this->bb_moderation_get_action_type( $group_name_args );

		BP_Core_Suspend::add_suspend( $suspend_args );

		$args['parent_id'] = ! empty( $args['parent_id'] ) ? $args['parent_id'] : $this->item_type . '_' . $video_id;

		if ( $this->background_disabled ) {
			$this->hide_related_content( $video_id, $hide_sitewide, $args );
		} else {
			$bb_background_updater->data(
				array(
					'type'              => $this->item_type,
					'group'             => $group_name,
					'data_id'           => $video_id,
					'secondary_data_id' => $args['parent_id'],
					'callback'          => array( $this, 'hide_related_content' ),
					'args'              => array( $video_id, $hide_sitewide, $args ),
				),
			);
			$bb_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Un-hide related content of video
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param int      $video_id      video id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param int      $force_all     un-hide for all users.
	 * @param array    $args          parent args.
	 */
	public function manage_unhidden_video( $video_id, $hide_sitewide, $force_all, $args = array() ) {
		global $bb_background_updater;

		if ( empty( $video_id ) ) {
			return;
		}

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $video_id,
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
			$video_author_id = BP_Moderation_Video::get_content_owner_id( $video_id );
			if ( isset( $suspend_args['blocked_user'] ) && $video_author_id === $suspend_args['blocked_user'] ) {
				unset( $suspend_args['blocked_user'] );
			}
		}

		$suspend_args = self::validate_keys( $suspend_args );

		$group_name_args = array_merge(
			$suspend_args,
			array(
				'custom_action' => 'unhide',
			)
		);
		$group_name      = $this->bb_moderation_get_action_type( $group_name_args );

		BP_Core_Suspend::remove_suspend( $suspend_args );

		$args['parent_id'] = ! empty( $args['parent_id'] ) ? $args['parent_id'] : $this->item_type . '_' . $video_id;

		if ( $this->background_disabled ) {
			$this->unhide_related_content( $video_id, $hide_sitewide, $force_all, $args );
		} else {
			$bb_background_updater->data(
				array(
					'type'              => $this->item_type,
					'group'             => $group_name,
					'data_id'           => $video_id,
					'secondary_data_id' => $args['parent_id'],
					'callback'          => array( $this, 'unhide_related_content' ),
					'args'              => array( $video_id, $hide_sitewide, $force_all, $args ),
				),
			);
			$bb_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Get Video's comment ids
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param int   $video_id Video id.
	 * @param array $args     parent args.
	 *
	 * @return array
	 */
	protected function get_related_contents( $video_id, $args = array() ) {
		$action           = ! empty( $args['action'] ) ? $args['action'] : '';
		$blocked_user     = ! empty( $args['blocked_user'] ) ? $args['blocked_user'] : '';
		$page             = ! empty( $args['page'] ) ? $args['page'] : - 1;
		$related_contents = array();

		if ( $page > 1 ) {
			return $related_contents;
		}

		$video = new BP_Video( $video_id );

		if ( bp_is_active( 'activity' ) && ! empty( $video ) && ! empty( $video->activity_id ) ) {

			/**
			 * Remove pre-validate check.
			 *
			 * @since BuddyBoss 1.7.5
			 */
			do_action( 'bb_moderation_before_get_related_' . BP_Suspend_Activity::$type );

			$related_contents[ BP_Suspend_Activity_Comment::$type ] = BP_Suspend_Activity_Comment::get_activity_comment_ids( $video->activity_id );

			$activity = new BP_Activity_Activity( $video->activity_id );

			if ( ! empty( $activity ) && ! empty( $activity->type ) ) {
				if ( 'activity_comment' === $activity->type ) {
					$related_contents[ BP_Suspend_Activity_Comment::$type ][] = $activity->id;
				} else {
					$related_contents[ BP_Suspend_Activity::$type ][] = $activity->id;
				}
			}

			if ( 'hide' === $action && ! empty( $video->attachment_id ) ) {
				$attachment_id = $video->attachment_id;

				$parent_activity_id = get_post_meta( $attachment_id, 'bp_video_parent_activity_id', true );

				if ( ! empty( $parent_activity_id ) ) {
					$parent_activity  = new BP_Activity_Activity( $parent_activity_id );
					$parent_video_ids = self::get_video_ids_meta( $parent_activity_id, 'bp_activity_get_meta', $action );

					if ( empty( $parent_video_ids ) && ! empty( $parent_activity ) && ! empty( $parent_activity->type ) && empty( wp_strip_all_tags( $parent_activity->content ) ) ) {
						if ( 'activity_comment' === $parent_activity->type ) {
							$related_contents[ BP_Suspend_Activity_Comment::$type ][] = $parent_activity->id;
						} else {
							$related_contents[ BP_Suspend_Activity::$type ][] = $parent_activity->id;
						}
					}
				}
			}

			if ( 'unhide' === $action && ! empty( $video->attachment_id ) ) {
				$attachment_id      = $video->attachment_id;
				$parent_activity_id = get_post_meta( $attachment_id, 'bp_video_parent_activity_id', true );
				if ( ! empty( $parent_activity_id ) ) {
					$parent_activity = new BP_Activity_Activity( $parent_activity_id );
					if (
						! empty( $parent_activity ) &&
						! empty( $parent_activity->type )
					) {
						if ( 'activity_comment' === $parent_activity->type ) {
							$related_contents[ BP_Suspend_Activity_Comment::$type ][] = $parent_activity->id;
						} else {
							$related_contents[ BP_Suspend_Activity::$type ][] = $parent_activity->id;
						}
					}
				}
			}

			/**
			 * Added pre-validate check.
			 *
			 * @since BuddyBoss 1.7.5
			 */
			do_action( 'bb_moderation_after_get_related_' . BP_Suspend_Activity::$type );
		}

		return $related_contents;
	}

	/**
	 * Update the suspend table to add new entries.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param BP_Video $video Current instance of video item being saved. Passed by reference.
	 */
	public function sync_moderation_data_on_save( $video ) {

		if ( empty( $video ) || empty( $video->id ) ) {
			return;
		}

		$sub_items     = bp_moderation_get_sub_items( $video->id, BP_Moderation_Video::$moderation_type );
		$item_sub_id   = isset( $sub_items['id'] ) ? $sub_items['id'] : $video->id;
		$item_sub_type = isset( $sub_items['type'] ) ? $sub_items['type'] : BP_Moderation_Video::$moderation_type;

		$suspended_record = BP_Core_Suspend::get_recode( $item_sub_id, $item_sub_type );

		if ( empty( $suspended_record ) ) {
			$suspended_record = BP_Core_Suspend::get_recode( $video->user_id, BP_Moderation_Members::$moderation_type );
		}

		if ( empty( $suspended_record ) || bp_moderation_is_content_hidden( $video->id, self::$type ) ) {
			return;
		}

		self::handle_new_suspend_entry( $suspended_record, $video->id, $video->user_id );
	}

	/**
	 * Update the suspend table to delete the group.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param array $videos Array of video.
	 */
	public function sync_moderation_data_on_delete( $videos ) {

		if ( empty( $videos ) ) {
			return;
		}

		foreach ( $videos as $video ) {
			BP_Core_Suspend::delete_suspend( $video->id, $this->item_type );
		}
	}

	/**
	 * Function to un-restrict activity data while deleting the activity.
	 *
	 * @since BuddyBoss 1.7.5
	 *
	 * @param boolean $restrict restrict single item or not.
	 *
	 * @return false
	 */
	public function unbind_restrict_single_item( $restrict ) {

		if ( empty( $restrict ) && did_action( 'bp_video_after_delete' ) ) {
			$restrict = true;
		}

		return $restrict;
	}

	/**
	 * Function to update suspend record on activity delete.
	 *
	 * @since BuddyBoss 1.7.5
	 *
	 * @param object $activity_data activity data.
	 */
	public function update_suspend_data_on_activity_delete( $activity_data ) {
		$secondary_item_id = ! empty( $activity_data->secondary_item_id ) ? $activity_data->secondary_item_id : 0;

		if ( empty( $secondary_item_id ) ) {
			return;
		}

		$videos = bp_activity_get_meta( $secondary_item_id, 'bp_video_ids', true );
		$videos = ! empty( $videos ) ? explode( ',', $videos ) : array();

		if ( ! empty( $videos ) && 1 === count( $videos ) ) {
			foreach ( $videos as $video ) {
				if ( bp_moderation_is_content_hidden( $video, $this->item_type ) && bp_is_active( 'activity' ) ) {
					BP_Core_Suspend::add_suspend(
						array(
							'item_id'     => $secondary_item_id,
							'item_type'   => BP_Suspend_Activity::$type,
							'hide_parent' => 1,
						)
					);
				}
			}
		}
	}
}
