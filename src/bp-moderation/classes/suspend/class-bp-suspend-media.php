<?php
/**
 * BuddyBoss Suspend Media Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Suspend
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend Media.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Suspend_Media extends BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $type = 'media';

	/**
	 * BP_Suspend_Media constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		$this->item_type = self::$type;

		// Manage hidden list.
		add_action( "bp_suspend_hide_{$this->item_type}", array( $this, 'manage_hidden_media' ), 10, 3 );
		add_action( "bp_suspend_unhide_{$this->item_type}", array( $this, 'manage_unhidden_media' ), 10, 4 );

		// Add moderation data when media is added.
		add_action( 'bp_media_after_save', array( $this, 'sync_moderation_data_on_save' ), 10, 1 );

		// Delete moderation data when media is deleted.
		add_action( 'bp_media_after_delete', array( $this, 'sync_moderation_data_on_delete' ), 10, 1 );

		/**
		 * Suspend code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		add_filter( 'bp_media_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_media_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		// modify in group photos count.
		add_filter( 'bp_media_get_join_count_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_media_get_where_count_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_media_search_join_sql_photo', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_media_search_where_conditions_photo', array( $this, 'update_where_sql' ), 10, 2 );

		if ( bp_is_active( 'activity' ) ) {
			add_filter( 'bb_moderation_restrict_single_item_' . BP_Suspend_Activity::$type, array( $this, 'unbind_restrict_single_item' ), 10, 1 );
			add_action( 'bb_moderation_' . BP_Suspend_Activity::$type . '_before_delete_suspend', array( $this, 'update_suspend_data_on_activity_delete' ) );
			add_action( 'bb_moderation_' . BP_Suspend_Activity_Comment::$type . '_before_delete_suspend', array( $this, 'update_suspend_data_on_activity_delete' ) );
		}
	}

	/**
	 * Get Blocked member's media ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int    $member_id Member id.
	 * @param string $action    Action name to perform.
	 * @param int    $page      Number of items per page.
	 *
	 * @return array
	 */
	public static function get_member_media_ids( $member_id, $action = '', $page = - 1 ) {
		$media_ids = array();

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

		$medias = bp_media_get( $args );

		if ( ! empty( $medias['medias'] ) ) {
			$media_ids = $medias['medias'];
		}

		if ( 'hide' === $action && ! empty( $media_ids ) ) {
			foreach ( $media_ids as $k => $media_id ) {
				if ( BP_Core_Suspend::check_suspended_content( $media_id, self::$type, true ) ) {
					unset( $media_ids[ $k ] );
				}
			}
		}

		return $media_ids;
	}

	/**
	 * Get Blocked group's media ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $group_id group id.
	 * @param int $page     Number of items per page.
	 *
	 * @return array
	 */
	public static function get_group_media_ids( $group_id, $page = - 1 ) {
		$media_ids = array();

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

		$medias = bp_media_get( $args );

		if ( ! empty( $medias['medias'] ) ) {
			$media_ids = $medias['medias'];
		}

		return $media_ids;
	}

	/**
	 * Get Media ids of blocked item [ Forums/topics/replies/activity etc ] from meta
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int    $item_id  item id.
	 * @param string $function Function Name to get meta.
	 * @param string $action   Action name to perform.
	 *
	 * @return array Media IDs
	 */
	public static function get_media_ids_meta( $item_id, $function = 'get_post_meta', $action = '' ) {
		$media_ids = array();

		if ( function_exists( $function ) ) {
			if ( ! empty( $item_id ) ) {
				$post_media = $function( $item_id, 'bp_media_ids', true );

				if ( empty( $post_media ) ) {
					$post_media = BP_Media::get_activity_media_id( $item_id );
				}

				if ( ! empty( $post_media ) ) {
					$media_ids = wp_parse_id_list( $post_media );
				}
			}
		}

		if ( 'hide' === $action && ! empty( $media_ids ) ) {
			foreach ( $media_ids as $k => $media_id ) {
				if ( BP_Core_Suspend::check_hidden_content( $media_id, self::$type, true ) ) {
					unset( $media_ids[ $k ] );
				}
			}
		}

		if ( 'unhide' === $action && ! empty( $media_ids ) ) {
			foreach ( $media_ids as $k => $media_id ) {
				if ( self::is_content_reported_hidden( $media_id, self::$type ) ) {
					unset( $media_ids[ $k ] );
				}
			}
		}

		return $media_ids;
	}

	/**
	 * Prepare media Join SQL query to filter blocked Media
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $join_sql Media Join sql.
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
		 * Filters the hidden Media Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $join_sql Join sql query
		 * @param array $class    current class object.
		 */
		$join_sql = apply_filters( 'bp_suspend_media_get_join', $join_sql, $this );

		return $join_sql;
	}

	/**
	 * Prepare media Where SQL query to filter blocked Media
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $where_conditions Media Where sql.
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
		 * Filters the hidden media Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @since BuddyBoss 2.3.80
		 * Introduce new params $args as Media args.
		 *
		 * @param array $where Query to hide suspended user's media.
		 * @param array $class current class object.
		 * @param array $args  Media args.
		 */
		$where = apply_filters( 'bp_suspend_media_get_where_conditions', $where, $this, $args );

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
	 * Hide related content of media
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $media_id      media id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param array    $args          parent args.
	 */
	public function manage_hidden_media( $media_id, $hide_sitewide, $args = array() ) {
		global $bp_background_updater;

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $media_id,
				'item_type' => self::$type,
			)
		);

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		$suspend_args = self::validate_keys( $suspend_args );

		BP_Core_Suspend::add_suspend( $suspend_args );

		if ( $this->background_disabled ) {
			$this->hide_related_content( $media_id, $hide_sitewide, $args );
		} else {
			$bp_background_updater->data(
				array(
					array(
						'callback' => array( $this, 'hide_related_content' ),
						'args'     => array( $media_id, $hide_sitewide, $args ),
					),
				)
			);
			$bp_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Un-hide related content of media
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $media_id      media id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param int      $force_all     un-hide for all users.
	 * @param array    $args          parent args.
	 */
	public function manage_unhidden_media( $media_id, $hide_sitewide, $force_all, $args = array() ) {
		global $bp_background_updater;

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $media_id,
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
			$media_author_id = BP_Moderation_Media::get_content_owner_id( $media_id );
			if ( isset( $suspend_args['blocked_user'] ) && $media_author_id === $suspend_args['blocked_user'] ) {
				unset( $suspend_args['blocked_user'] );
			}
		}

		$suspend_args = self::validate_keys( $suspend_args );

		BP_Core_Suspend::remove_suspend( $suspend_args );

		if ( $this->background_disabled ) {
			$this->unhide_related_content( $media_id, $hide_sitewide, $force_all, $args );
		} else {
			$bp_background_updater->data(
				array(
					array(
						'callback' => array( $this, 'unhide_related_content' ),
						'args'     => array( $media_id, $hide_sitewide, $force_all, $args ),
					),
				)
			);
			$bp_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Get Media's comment ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int   $media_id Media id.
	 * @param array $args     parent args.
	 *
	 * @return array
	 */
	protected function get_related_contents( $media_id, $args = array() ) {
		$action           = ! empty( $args['action'] ) ? $args['action'] : '';
		$blocked_user     = ! empty( $args['blocked_user'] ) ? $args['blocked_user'] : '';
		$page             = ! empty( $args['page'] ) ? $args['page'] : - 1;
		$related_contents = array();

		if ( $page > 1 ) {
			return $related_contents;
		}

		$media = new BP_Media( $media_id );

		if (
			bp_is_active( 'activity' ) &&
			! empty( $media ) &&
			! empty( $media->activity_id )
		) {

			/**
			 * Remove pre-validate check.
			 *
			 * @since BuddyBoss 1.7.5
			 */
			do_action( 'bb_moderation_before_get_related_' . BP_Suspend_Activity::$type );

			$related_contents[ BP_Suspend_Activity_Comment::$type ] = BP_Suspend_Activity_Comment::get_activity_comment_ids( $media->activity_id );

			$activity = new BP_Activity_Activity( $media->activity_id );

			if ( ! empty( $activity ) && ! empty( $activity->type ) ) {
				if ( 'activity_comment' === $activity->type ) {
					$related_contents[ BP_Suspend_Activity_Comment::$type ][] = $activity->id;
				} else {
					$related_contents[ BP_Suspend_Activity::$type ][] = $activity->id;
				}
			}

			if ( 'hide' === $action && ! empty( $media->attachment_id ) ) {
				$attachment_id = $media->attachment_id;

				$parent_activity_id = get_post_meta( $attachment_id, 'bp_media_parent_activity_id', true );
				if ( ! empty( $parent_activity_id ) ) {
					$parent_activity  = new BP_Activity_Activity( $parent_activity_id );
					$parent_media_ids = self::get_media_ids_meta( $parent_activity_id, 'bp_activity_get_meta', $action );

					if (
						empty( $parent_media_ids ) &&
						! empty( $parent_activity ) &&
						! empty( $parent_activity->type ) &&
						empty( wp_strip_all_tags( $parent_activity->content ) )
					) {
						if ( 'activity_comment' === $parent_activity->type ) {
							$related_contents[ BP_Suspend_Activity_Comment::$type ][] = $parent_activity->id;
						} else {
							$related_contents[ BP_Suspend_Activity::$type ][] = $parent_activity->id;
						}
					}
				}
			}

			if ( 'unhide' === $action && ! empty( $media->attachment_id ) ) {
				$attachment_id      = $media->attachment_id;
				$parent_activity_id = get_post_meta( $attachment_id, 'bp_media_parent_activity_id', true );
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
	 * @since BuddyBoss 1.5.6
	 *
	 * @param BP_Media $media Current instance of media item being saved. Passed by reference.
	 */
	public function sync_moderation_data_on_save( $media ) {

		if ( empty( $media ) || empty( $media->id ) ) {
			return;
		}

		$sub_items     = bp_moderation_get_sub_items( $media->id, BP_Moderation_Media::$moderation_type );
		$item_sub_id   = isset( $sub_items['id'] ) ? $sub_items['id'] : $media->id;
		$item_sub_type = isset( $sub_items['type'] ) ? $sub_items['type'] : BP_Moderation_Media::$moderation_type;

		$suspended_record = BP_Core_Suspend::get_recode( $item_sub_id, $item_sub_type );

		if ( empty( $suspended_record ) ) {
			$suspended_record = BP_Core_Suspend::get_recode( $media->user_id, BP_Moderation_Members::$moderation_type );
		}

		if ( empty( $suspended_record ) || bp_moderation_is_content_hidden( $media->id, self::$type ) ) {
			return;
		}

		self::handle_new_suspend_entry( $suspended_record, $media->id, $media->user_id );
	}

	/**
	 * Update the suspend table to delete the group.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $medias Array of media.
	 */
	public function sync_moderation_data_on_delete( $medias ) {

		if ( empty( $medias ) ) {
			return;
		}

		foreach ( $medias as $media ) {
			BP_Core_Suspend::delete_suspend( $media->id, $this->item_type );
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

		if ( empty( $restrict ) && did_action( 'bp_media_after_delete' ) ) {
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

		$medias = bp_activity_get_meta( $secondary_item_id, 'bp_media_ids', true );
		$medias = ! empty( $medias ) ? explode( ',', $medias ) : array();

		if ( ! empty( $medias ) && 1 === count( $medias ) ) {
			foreach ( $medias as $media ) {
				if ( bp_moderation_is_content_hidden( $media, $this->item_type ) && bp_is_active( 'activity' ) ) {
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
