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
	}

	/**
	 * Get Blocked member's media ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $member_id member id.
	 *
	 * @return array
	 */
	public static function get_member_media_ids( $member_id ) {
		$media_ids = array();

		$medias = bp_media_get(
			array(
				'moderation_query' => false,
				'per_page'         => 0,
				'fields'           => 'ids',
				'user_id'          => $member_id,
			)
		);

		if ( ! empty( $medias['medias'] ) ) {
			$media_ids = $medias['medias'];
		}

		return $media_ids;
	}

	/**
	 * Get Blocked group's media ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $group_id group id.
	 *
	 * @return array
	 */
	public static function get_group_media_ids( $group_id ) {
		$media_ids = array();

		$medias = bp_media_get(
			array(
				'moderation_query' => false,
				'per_page'         => 0,
				'fields'           => 'ids',
				'group_id'         => $group_id,
			)
		);

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
	 *
	 * @return array Media IDs
	 */
	public static function get_media_ids_meta( $item_id, $function = 'get_post_meta' ) {
		$media_ids = array();

		if ( function_exists( $function ) ) {
			if ( ! empty( $item_id ) ) {
				$post_media = $function( $item_id, 'bp_media_ids', true );

				if ( empty( $post_media ) ){
					$post_media = BP_Media::get_activity_media_id( $item_id );
				}

				if ( ! empty( $post_media )  ){
					$media_ids  = wp_parse_id_list( $post_media );
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
		 * @param array $where Query to hide suspended user's media.
		 * @param array $class current class object.
		 */
		$where = apply_filters( 'bp_suspend_media_get_where_conditions', $where, $this );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['suspend_where'] = '( ' . implode( ' AND ', $where ) . ' )';
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

		$suspend_args = wp_parse_args(
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

		if ( $this->backgroup_diabled || ! empty( $args ) ) {
			$this->hide_related_content( $media_id, $hide_sitewide, $args );
		} else {
			$bp_background_updater->push_to_queue(
				array(
					'callback' => array( $this, 'hide_related_content' ),
					'args'     => array( $media_id, $hide_sitewide, $args ),
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

		$suspend_args = wp_parse_args(
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

		if ( $this->backgroup_diabled || ! empty( $args ) ) {
			$this->unhide_related_content( $media_id, $hide_sitewide, $force_all, $args );
		} else {
			$bp_background_updater->push_to_queue(
				array(
					'callback' => array( $this, 'unhide_related_content' ),
					'args'     => array( $media_id, $hide_sitewide, $force_all, $args ),
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
		return array();
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

		if ( empty( $suspended_record ) ) {
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
}
