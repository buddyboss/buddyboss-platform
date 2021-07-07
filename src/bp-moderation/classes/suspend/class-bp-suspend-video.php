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
		//Disabling to fix the single photo count on user profile.
		//add_filter( 'bp_media_get_where_conditions', array( $this, 'update_where_media_sql' ), 10, 2 );

		// modify in group videos count for album.
		add_filter( 'bp_media_get_join_count_sql', array( $this, 'update_join_media_sql' ), 10, 2 );
		add_filter( 'bp_media_get_where_count_conditions', array( $this, 'update_where_media_sql' ), 10, 2 );

		add_filter( 'bp_media_search_join_sql_photo', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_media_search_where_conditions_photo', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_video_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_video_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_video_search_join_sql_video', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_video_search_where_conditions_video', array( $this, 'update_where_sql' ), 10, 2 );
	}

	/**
	 * Get Blocked member's video ids
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param int $member_id member id.
	 *
	 * @return array
	 */
	public static function get_member_video_ids( $member_id ) {
		$video_ids = array();

		$videos = bp_video_get(
			array(
				'moderation_query' => false,
				'per_page'         => 0,
				'fields'           => 'ids',
				'user_id'          => $member_id,
			)
		);

		if ( ! empty( $videos['videos'] ) ) {
			$video_ids = $videos['videos'];
		}

		return $video_ids;
	}

	/**
	 * Get Blocked group's video ids
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param int $group_id group id.
	 *
	 * @return array
	 */
	public static function get_group_video_ids( $group_id ) {
		$video_ids = array();

		$videos = bp_video_get(
			array(
				'moderation_query' => false,
				'per_page'         => 0,
				'fields'           => 'ids',
				'group_id'         => $group_id,
			)
		);

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
	 *
	 * @return array Video IDs
	 */
	public static function get_video_ids_meta( $item_id, $function = 'get_post_meta' ) {
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
		 * @param array $where Query to hide suspended user's video.
		 * @param array $class current class object.
		 */
		$where = apply_filters( 'bp_suspend_media_get_where_conditions', $where, $this );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['suspend_where'] = '( ' . implode( ' AND ', $where ) . ' )';
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
			$where_conditions['suspend_where'] = '( ' . implode( ' AND ', $where ) . ' )';
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
		global $bp_background_updater;

		$suspend_args = wp_parse_args(
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

		BP_Core_Suspend::add_suspend( $suspend_args );

		if ( $this->backgroup_diabled || ! empty( $args ) ) {
			$this->hide_related_content( $video_id, $hide_sitewide, $args );
		} else {
			$bp_background_updater->push_to_queue(
				array(
					'callback' => array( $this, 'hide_related_content' ),
					'args'     => array( $video_id, $hide_sitewide, $args ),
				)
			);
			$bp_background_updater->save()->schedule_event();
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
		global $bp_background_updater;

		$suspend_args = wp_parse_args(
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

		BP_Core_Suspend::remove_suspend( $suspend_args );

		if ( $this->backgroup_diabled || ! empty( $args ) ) {
			$this->unhide_related_content( $video_id, $hide_sitewide, $force_all, $args );
		} else {
			$bp_background_updater->push_to_queue(
				array(
					'callback' => array( $this, 'unhide_related_content' ),
					'args'     => array( $video_id, $hide_sitewide, $force_all, $args ),
				)
			);
			$bp_background_updater->save()->schedule_event();
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
		return array();
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

		if ( empty( $suspended_record ) ) {
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
}
