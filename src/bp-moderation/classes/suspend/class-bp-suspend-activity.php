<?php
/**
 * BuddyBoss Suspend Activity Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Suspend
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend Activity.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Suspend_Activity extends BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $type = 'activity';

	/**
	 * BP_Suspend_Activity constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		$this->item_type = self::$type;

		// Manage hidden list.
		add_action( "bp_suspend_hide_{$this->item_type}", array( $this, 'manage_hidden_activity' ), 10, 3 );
		add_action( "bp_suspend_unhide_{$this->item_type}", array( $this, 'manage_unhidden_activity' ), 10, 4 );

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

		add_filter( 'bp_activity_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_activity_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_activity_search_join_sql', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_activity_search_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_activity_activity_pre_validate', array( $this, 'restrict_single_item' ), 10, 2 );

		add_action( 'bb_moderation_before_get_related_' . $this->item_type, array( $this, 'remove_pre_validate_check' ) );
		add_action( 'bb_moderation_after_get_related_' . $this->item_type, array( $this, 'add_pre_validate_check' ) );
	}

	/**
	 * Get Blocked member's activity ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int    $member_id member id.
	 * @param string $action    Action name to perform.
	 * @param int    $page      Number of page.
	 *
	 * @return array
	 */
	public static function get_member_activity_ids( $member_id, $action = '', $page = - 1 ) {
		$activities_ids = array();

		$args = array(
			'moderation_query' => false,
			'per_page'         => 0,
			'fields'           => 'ids',
			'show_hidden'      => true,
			'filter'           => array(
				'user_id' => $member_id,
			),
		);

		if ( $page > 0 ) {
			$args['per_page'] = self::$item_per_page;
			$args['page']     = $page;
		}

		$activities = BP_Activity_Activity::get( $args );

		if ( ! empty( $activities['activities'] ) ) {
			$activities_ids = $activities['activities'];
		}

		if ( 'hide' === $action && ! empty( $activities_ids ) ) {
			foreach ( $activities_ids as $k => $activity_id ) {
				if ( BP_Core_Suspend::check_suspended_content( $activity_id, self::$type, true ) ) {
					unset( $activities_ids[ $k ] );
				}
			}
		}

		return $activities_ids;
	}

	/**
	 * Get Blocked group's activity ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $group_id group id.
	 * @param int $page     Number of page.
	 *
	 * @return array
	 */
	public static function get_group_activity_ids( $group_id, $page = - 1 ) {
		$activities_ids = array();

		$args = array(
			'moderation_query' => false,
			'per_page'         => 0,
			'fields'           => 'ids',
			'show_hidden'      => true,
			'filter'           => array(
				'primary_id' => $group_id,
				'object'     => 'groups',
			),
		);

		if ( $page > 0 ) {
			$args['per_page'] = self::$item_per_page;
			$args['page']     = $page;
		}

		$activities = BP_Activity_Activity::get( $args );

		if ( ! empty( $activities['activities'] ) ) {
			$activities_ids = $activities['activities'];
		}

		return $activities_ids;
	}

	/**
	 * Get forum activities ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $post_id post id.
	 * @param int $page    Number of page.
	 *
	 * @return array
	 */
	public static function get_bbpress_activity_ids( $post_id, $page = - 1 ) {
		$activities_ids = array();

		$args = array(
			'moderation_query' => false,
			'per_page'         => 0,
			'fields'           => 'ids',
			'show_hidden'      => true,
			'filter_query'     => array(
				'relation' => 'or',
				'bbpress'  => array(
					array(
						'column' => 'item_id',
						'value'  => $post_id,
					),
					array(
						'column' => 'component',
						'value'  => 'bbpress',
					),
				),
				'group'    => array(
					array(
						'column' => 'secondary_item_id',
						'value'  => $post_id,
					),
					array(
						'column' => 'component',
						'value'  => 'groups',
					),
				),
			),
		);

		if ( $page > 0 ) {
			$args['per_page'] = self::$item_per_page;
			$args['page']     = $page;
		}

		$activities = BP_Activity_Activity::get( $args );

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
		$join_sql = apply_filters( 'bp_suspend_activity_get_join', $join_sql, $this );

		return $join_sql;
	}

	/**
	 * Prepare activity Where SQL query to filter blocked Activity
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
		 * Filters the hidden activity Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $where Query to hide suspended user's activity.
		 * @param array $class current class object.
		 */
		$where = apply_filters( 'bp_suspend_activity_get_where_conditions', $where, $this );

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
	 * @param object  $activity Current activity object.
	 *
	 * @return false
	 */
	public function restrict_single_item( $restrict, $activity ) {

		if ( apply_filters( 'bb_moderation_restrict_single_item_' . self::$type, false, $activity ) ) {
			return $restrict;
		}

		$username_visible = isset( $_GET['username_visible'] ) ? sanitize_text_field( wp_unslash( $_GET['username_visible'] ) ) : false;

		if ( ! empty( $username_visible ) ) {
			return $restrict;
		}

		if (
			'activity_comment' !== $activity->type &&
			BP_Core_Suspend::check_suspended_content( (int) $activity->id, self::$type ) &&
			(
				// Allow comment to group activity.
				! bp_is_active( 'groups' ) ||
				'groups' !== $activity->component
			)
		) {
			return false;
		}

		return $restrict;
	}

	/**
	 * Hide related content of activity
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $activity_id   activity id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param array    $args          parent args.
	 */
	public function manage_hidden_activity( $activity_id, $hide_sitewide, $args = array() ) {
		global $bp_background_updater;

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $activity_id,
				'item_type' => self::$type,
			)
		);

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		$suspend_args = self::validate_keys( $suspend_args );

		BP_Core_Suspend::add_suspend( $suspend_args );

		if ( $this->background_disabled ) {
			$this->hide_related_content( $activity_id, $hide_sitewide, $args );
		} else {
			$bp_background_updater->data(
				array(
					array(
						'callback' => array( $this, 'hide_related_content' ),
						'args'     => array( $activity_id, $hide_sitewide, $args ),
					),
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
	 * @param int      $activity_id   activity id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param int      $force_all     un-hide for all users.
	 * @param array    $args          parent args.
	 */
	public function manage_unhidden_activity( $activity_id, $hide_sitewide, $force_all, $args = array() ) {
		global $bp_background_updater;

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $activity_id,
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
			$activity_user = BP_Moderation_Activity::get_content_owner_id( $activity_id );
			if ( isset( $suspend_args['blocked_user'] ) && $activity_user === $suspend_args['blocked_user'] ) {
				unset( $suspend_args['blocked_user'] );
			}
		}

		$suspend_args = self::validate_keys( $suspend_args );

		BP_Core_Suspend::remove_suspend( $suspend_args );

		if ( $this->background_disabled ) {
			$this->unhide_related_content( $activity_id, $hide_sitewide, $force_all, $args );
		} else {
			$bp_background_updater->data(
				array(
					array(
						'callback' => array( $this, 'unhide_related_content' ),
						'args'     => array( $activity_id, $hide_sitewide, $force_all, $args ),
					),
				)
			);
			$bp_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Get Activity's comment ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int   $activity_id activity id.
	 * @param array $args        parent args.
	 *
	 * @return array
	 */
	protected function get_related_contents( $activity_id, $args = array() ) {
		$action       = ! empty( $args['action'] ) ? $args['action'] : '';
		$blocked_user = ! empty( $args['blocked_user'] ) ? $args['blocked_user'] : '';
		$page         = ! empty( $args['page'] ) ? $args['page'] : - 1;

		if ( $page > 1 ) {
			return array();
		}

		$related_contents = array(
			BP_Suspend_Activity_Comment::$type => BP_Suspend_Activity_Comment::get_activity_comment_ids( $activity_id ),
		);

		if ( bp_is_active( 'document' ) ) {
			$related_contents[ BP_Suspend_Document::$type ] = BP_Suspend_Document::get_document_ids_meta( $activity_id, 'bp_activity_get_meta', $action );
		}

		if ( bp_is_active( 'media' ) ) {
			$related_contents[ BP_Suspend_Media::$type ] = BP_Suspend_Media::get_media_ids_meta( $activity_id, 'bp_activity_get_meta', $action );
		}

		if ( bp_is_active( 'video' ) ) {
			$related_contents[ BP_Suspend_Video::$type ] = BP_Suspend_Video::get_video_ids_meta( $activity_id, 'bp_activity_get_meta', $action );
		}

		return $related_contents;
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

		if ( 'activity_comment' === $activity->type ) {
			return;
		}

		$sub_items     = bp_moderation_get_sub_items( $activity->id, BP_Moderation_Activity::$moderation_type );
		$item_sub_id   = isset( $sub_items['id'] ) ? $sub_items['id'] : $activity->id;
		$item_sub_type = isset( $sub_items['type'] ) ? $sub_items['type'] : BP_Moderation_Activity::$moderation_type;

		if ( 'groups' === $activity->component ) {
			$item_sub_id   = $activity->item_id;
			$item_sub_type = BP_Moderation_Groups::$moderation_type;
		}

		$suspended_record = BP_Core_Suspend::get_recode( $item_sub_id, $item_sub_type );

		if ( empty( $suspended_record ) ) {
			$suspended_record = BP_Core_Suspend::get_recode( $activity->user_id, BP_Moderation_Members::$moderation_type );
		}

		if ( empty( $suspended_record ) || bp_moderation_is_content_hidden( $activity->id, self::$type ) ) {
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

				if ( 'activity_comment' === $activity->type ) {
					continue;
				}

				/**
				 * Fires before activity suspend record delete.
				 *
				 * @since BuddyBoss 1.7.5
				 *
				 * @param object $activity_data activity data.
				 */

				do_action( 'bb_moderation_' . $this->item_type . '_before_delete_suspend', $activity );

				BP_Core_Suspend::delete_suspend( $activity->id, $this->item_type );
			}
		}
	}

	/**
	 * Remove Pre-validate check.
	 *
	 * @since BuddyBoss 1.7.5
	 */
	public function remove_pre_validate_check() {
		remove_filter( 'bp_activity_activity_pre_validate', array( $this, 'restrict_single_item' ), 10 );
	}

	/**
	 * Add Pre-validate check.
	 *
	 * @since BuddyBoss 1.7.5
	 */
	public function add_pre_validate_check() {
		add_filter( 'bp_activity_activity_pre_validate', array( $this, 'restrict_single_item' ), 10, 2 );
	}
}
