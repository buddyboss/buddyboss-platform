<?php
/**
 * BuddyBoss Suspend Group Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Suspend
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend Group.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Suspend_Group extends BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $type = 'groups';

	/**
	 * BP_Suspend_Group constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		$this->item_type = self::$type;

		// Manage hidden list.
		add_action( "bp_suspend_hide_{$this->item_type}", array( $this, 'manage_hidden_group' ), 10, 3 );
		add_action( "bp_suspend_unhide_{$this->item_type}", array( $this, 'manage_unhidden_group' ), 10, 4 );

		// Action to update group forum block list when associate with group.
		add_filter( 'update_group_metadata', array( $this, 'update_group_meta_before_block_list' ), 10, 5 );
		add_action( 'added_group_meta', array( $this, 'update_group_forum_block_list' ), 10, 4 );
		add_action( 'updated_group_meta', array( $this, 'update_group_forum_block_list' ), 10, 4 );

		// Add moderation data when group is added.
		add_action( 'groups_group_after_save', array( $this, 'sync_moderation_data_on_save' ), 10, 1 );

		// Delete moderation data when group is deleted.
		add_action( 'bp_groups_delete_group', array( $this, 'sync_moderation_data_on_delete' ), 10, 1 );

		/**
		 * Suspend code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		add_filter( 'bp_groups_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_groups_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		// group count.
		add_filter( 'bp_groups_get_join_count_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_groups_get_where_count_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		// invitation.
		add_filter( 'bp_invitations_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_invitations_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		// Search group.
		add_filter( 'bp_group_search_join_sql', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_group_search_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_groups_group_pre_validate', array( $this, 'restrict_single_item' ), 10, 2 );

		// Update the where condition for group subscriptions.
		add_filter( 'bb_subscriptions_get_where_conditions', array( $this, 'bb_subscriptions_group_where_conditions' ), 10, 2 );
	}

	/**
	 * Get Blocked member's group ids [ Check with group organiser ]
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $member_id member id.
	 *
	 * @return array
	 */
	public static function get_member_group_ids( $member_id ) {
		$group_ids = array();

		$user_groups = bp_get_user_groups(
			$member_id,
			array(
				'is_admin' => true,
			)
		);

		if ( ! empty( $user_groups ) ) {
			$group_ids = array_values( wp_list_pluck( $user_groups, 'group_id' ) );
		}

		return $group_ids;
	}

	/**
	 * Prepare group Join SQL query to filter blocked Group
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $join_sql Group Join sql.
	 * @param array  $args     Query arguments.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $args = array() ) {

		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $join_sql;
		}

		$action_name = current_filter();
		if ( 'bp_invitations_get_join_sql' === $action_name ) {
			$join_sql .= $this->exclude_joint_query( 'i.item_id' );
		} else {
			$join_sql .= $this->exclude_joint_query( 'g.id' );
		}

		/**
		 * Filters the hidden Group Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $join_sql Join sql query
		 * @param array $class    current class object.
		 */
		$join_sql = apply_filters( 'bp_suspend_group_get_join', $join_sql, $this );

		return $join_sql;
	}

	/**
	 * Prepare group Where SQL query to filter blocked Group
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $where_conditions Group Where sql.
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
		 * Filters the hidden group Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $where Query to hide suspended user's group.
		 * @param array $class current class object.
		 */
		$where = apply_filters( 'bp_suspend_group_get_where_conditions', $where, $this );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['suspend_where'] = '( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions;
	}

	/**
	 * Restrict Single item.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param boolean $restrict Check the item is valid or not.
	 * @param object  $group    Current group object.
	 *
	 * @return false
	 */
	public function restrict_single_item( $restrict, $group ) {

		$username_visible = isset( $_GET['username_visible'] ) ? sanitize_text_field( wp_unslash( $_GET['username_visible'] ) ) : false;

		if ( ! empty( $username_visible ) ) {
			return $restrict;
		}

		if ( BP_Core_Suspend::check_suspended_content( (int) $group->id, self::$type ) ) {
			return false;
		}

		return $restrict;
	}

	/**
	 * Hide related content of group
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $group_id      group id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param array    $args          parent args.
	 */
	public function manage_hidden_group( $group_id, $hide_sitewide, $args = array() ) {
		global $bb_background_updater;

		if ( empty( $group_id ) ) {
			return;
		}

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $group_id,
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

		$args['parent_id'] = ! empty( $args['parent_id'] ) ? $args['parent_id'] : $this->item_type . '_' . $group_id;

		if ( $this->background_disabled ) {
			$args['type'] = self::$type;
			$this->hide_related_content( $group_id, $hide_sitewide, $args );
		} else {
			$args['type'] = self::$type;
			$bb_background_updater->data(
				array(
					'type'              => $this->item_type,
					'group'             => $group_name,
					'data_id'           => $group_id,
					'secondary_data_id' => $args['parent_id'],
					'callback'          => array( $this, 'hide_related_content' ),
					'args'              => array( $group_id, $hide_sitewide, $args ),
				),
			);
			$bb_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Un-hide related content of group
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $group_id      group id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param int      $force_all     un-hide for all users.
	 * @param array    $args          parent args.
	 */
	public function manage_unhidden_group( $group_id, $hide_sitewide, $force_all, $args = array() ) {
		global $bb_background_updater;

		if ( empty( $group_id ) ) {
			return;
		}

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $group_id,
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
			$group_author_id = BP_Moderation_Groups::get_content_owner_id( $group_id );
			if ( isset( $suspend_args['blocked_user'] ) && in_array( $suspend_args['blocked_user'], $group_author_id, true ) ) {
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

		$args['parent_id'] = ! empty( $args['parent_id'] ) ? $args['parent_id'] : $this->item_type . '_' . $group_id;

		if ( $this->background_disabled ) {
			$args['type'] = self::$type;
			$this->unhide_related_content( $group_id, $hide_sitewide, $force_all, $args );
		} else {
			$args['type'] = self::$type;
			$bb_background_updater->data(
				array(
					'type'              => $this->item_type,
					'group'             => $group_name,
					'data_id'           => $group_id,
					'secondary_data_id' => $args['parent_id'],
					'callback'          => array( $this, 'unhide_related_content' ),
					'args'              => array( $group_id, $hide_sitewide, $force_all, $args ),
				),
			);
			$bb_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Get Activity's comment ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int   $group_id group id.
	 * @param array $args     parent args.
	 *
	 * @return array
	 */
	protected function get_related_contents( $group_id, $args = array() ) {
		$related_contents = array();
		$page             = ! empty( $args['page'] ) ? $args['page'] : - 1;

		if ( bp_is_active( 'forums' ) && $page < 2 ) {
			$related_contents[ BP_Suspend_Forum::$type ] = (array) bbp_get_group_forum_ids( $group_id );
		}

		if ( bp_is_active( 'activity' ) ) {
			$related_contents[ BP_Suspend_Activity::$type ] = BP_Suspend_Activity::get_group_activity_ids( $group_id, $page );
		}

		if ( bp_is_active( 'messages' ) && $page < 2 ) {
			$related_contents[ BP_Suspend_Message::$type ] = BP_Suspend_Message::get_group_message_thread_ids( $group_id );
		}

		if ( bp_is_active( 'document' ) ) {
			$related_contents[ BP_Suspend_Folder::$type ]   = BP_Suspend_Folder::get_group_folder_ids( $group_id, $page );
			$related_contents[ BP_Suspend_Document::$type ] = BP_Suspend_Document::get_group_document_ids( $group_id, $page );
		}

		if ( bp_is_active( 'media' ) ) {
			$related_contents[ BP_Suspend_Album::$type ] = BP_Suspend_Album::get_group_album_ids( $group_id, $page );
			$related_contents[ BP_Suspend_Media::$type ] = BP_Suspend_Media::get_group_media_ids( $group_id, $page );
		}

		if ( bp_is_active( 'video' ) ) {
			$related_contents[ BP_Suspend_Video::$type ] = BP_Suspend_Video::get_group_video_ids( $group_id, $page );
		}

		return $related_contents;
	}

	/**
	 * Update the suspend table to add new group created.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param BP_Groups_Group $group Current instance of the group item that was saved. Passed by reference.
	 */
	public function sync_moderation_data_on_save( $group ) {

		if ( empty( $group ) || empty( $group->id ) ) {
			return;
		}

		$sub_items     = bp_moderation_get_sub_items( $group->id, BP_Moderation_Groups::$moderation_type );
		$item_sub_id   = isset( $sub_items['id'] ) ? $sub_items['id'] : $group->id;
		$item_sub_type = isset( $sub_items['type'] ) ? $sub_items['type'] : BP_Moderation_Groups::$moderation_type;

		$suspended_record = BP_Core_Suspend::get_recode( $item_sub_id, $item_sub_type );

		if ( empty( $suspended_record ) ) {
			$suspended_record = BP_Core_Suspend::get_recode( $group->creator_id, BP_Moderation_Members::$moderation_type );
		}

		if ( empty( $suspended_record ) ) {
			return;
		}

		self::handle_new_suspend_entry( $suspended_record, $group->id, $group->creator_id );
	}

	/**
	 * Update the suspend table to delete the group.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param BP_Groups_Group $group Current instance of the group item being deleted. Passed by reference.
	 */
	public function sync_moderation_data_on_delete( $group ) {

		if ( empty( $group ) ) {
			return;
		}

		BP_Core_Suspend::delete_suspend( $group->id, $this->item_type );
	}

	/**
	 * Short-circuits updating metadata of a specific type.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param null|bool $check      Whether to allow updating metadata for the given type.
	 * @param int       $object_id  ID of the object metadata is for.
	 * @param string    $meta_key   Metadata key.
	 * @param mixed     $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param mixed     $prev_value Optional. Previous value to check before updating.
	 *                              If specified, only update existing metadata entries with
	 *                              this value. Otherwise, update all entries.
	 *
	 * @return null|bool
	 */
	public function update_group_meta_before_block_list( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		if ( 'forum_id' === $meta_key && bp_is_active( 'forums' ) ) {
			if ( empty( $prev_value ) ) {
				$prev_value = bbp_get_group_forum_ids( $object_id );
			}

			$forum_id     = (int) ( is_array( $prev_value ) ? current( $prev_value ) : $prev_value );
			$forum_author = get_post_field( 'post_author', $forum_id );
			remove_filter( 'query', 'bp_filter_metaid_column_name' );
			do_action(
				'bp_suspend_hide_' . BP_Suspend_Forum::$type,
				$forum_id,
				(bool) bp_moderation_is_user_suspended( $forum_author ),
				array(
					'blocked_user'     => $forum_author,
					'user_suspended'   => (bool) bp_moderation_is_user_suspended( $forum_author ),
					'force_bg_process' => true,
				)
			);
			add_filter( 'query', 'bp_filter_metaid_column_name' );
		}

		return $check;
	}

	/**
	 * Fires immediately before updating metadata of a specific type.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int    $meta_id     ID of the metadata entry to update.
	 * @param int    $object_id   ID of the object metadata is for.
	 * @param string $meta_key    Metadata key.
	 * @param mixed  $forum_id Metadata value. Serialized if non-scalar.
	 */
	public function update_group_forum_block_list( $meta_id, $object_id, $meta_key, $forum_id ) {
		if ( 'forum_id' !== $meta_key ) {
			return;
		}

		$forum_id = (int) ( is_array( $forum_id ) ? current( $forum_id ) : $forum_id );

		if ( empty( $forum_id ) || ! bp_is_active( 'forums' ) ) {
			return;
		}

		do_action(
			'bp_suspend_unhide_' . BP_Suspend_Forum::$type,
			$forum_id,
			0,
			false,
			array(
				'blocked_user'     => get_post_field( 'post_author', $forum_id ),
				'user_suspended'   => 0,
				'author_compare'   => true,
				'type'             => BP_Suspend_Forum::$type,
				'force_bg_process' => true,
			)
		);
	}

	/**
	 * Prepare subscription group where SQL query to filter blocked groups.
	 *
	 * @since BuddyBoss 2.2.8
	 *
	 * @param array $where_conditions Subscription where sql.
	 * @param array $r                Array of subscription arguments.
	 *
	 * @return mixed Where SQL
	 */
	public function bb_subscriptions_group_where_conditions( $where_conditions, $r ) {
		global $bp;

		if ( isset( $r['bypass_moderation'] ) && true === (bool) $r['bypass_moderation'] ) {
			return $where_conditions;
		}

		if ( ! empty( $r['type'] ) ) {
			if ( ! is_array( $r['type'] ) ) {
				$r['type'] = preg_split( '/[\s,]+/', $r['type'] );
			}
			$r['type'] = array_map( 'sanitize_title', $r['type'] );
		}

		if ( ! empty( $r['type'] ) && ! in_array( 'group', $r['type'], true ) ) {
			return $where_conditions;
		}

		// Get suspended where query for the forum subscription.
		$where                  = array();
		$where['suspend_where'] = 'user_suspended = 1';

		/**
		 * Filters the hidden forum where SQL statement.
		 *
		 * @since BuddyBoss 2.2.8
		 *
		 * @param array $where            Query to hide suspended groups.
		 * @param array $this             current class object.
		 * @param array $where_conditions Subscription where sql.
		 * @param array $r                Array of subscription arguments.
		 */
		$where = apply_filters( 'bb_subscriptions_suspend_group_get_where_conditions', $where, $this, $where_conditions, $r );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['suspend_group_where'] = "sc.item_id NOT IN ( SELECT item_id FROM {$bp->table_prefix}bp_suspend WHERE item_type = 'groups' AND ( " . implode( ' OR ', $where ) . ' ) )';
		}

		return $where_conditions;
	}
}
