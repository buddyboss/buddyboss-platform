<?php
/**
 * BuddyBoss Suspend Message Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Suspend
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend Member.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Suspend_Message extends BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $type = 'message_thread';

	/**
	 * BP_Suspend_Member constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		$this->item_type = self::$type;

		// Manage hidden list.
		add_action( "bp_suspend_hide_{$this->item_type}", array( $this, 'manage_hidden_message' ), 10, 3 );
		add_action( "bp_suspend_unhide_{$this->item_type}", array( $this, 'manage_unhidden_message' ), 10, 4 );

		// Delete user moderation data when actual user is deleted.
		add_action( 'bp_messages_message_delete_thread', array( $this, 'sync_moderation_data_on_delete' ), 10, 1 );

		/**
		 * Suspend code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( ( is_admin() ) && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		add_filter( 'bp_messages_recipient_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_messages_recipient_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
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
	public static function get_group_message_thread_ids( $group_id ) {
		$mthread_ids = array();

		$mthread_id = groups_get_groupmeta( $group_id, 'group_message_thread', true );
		if ( ! empty( $mthread_id ) ) {
			$mthread_ids = array( $mthread_id );
		}

		if ( ! empty( $messages['messages'] ) ) {
			$mthread_ids = $messages['messages'];
		}

		return $mthread_ids;
	}

	/**
	 * Prepare message Join SQL query to filter blocked message
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $join_sql Folder Join sql.
	 * @param array  $args     Query arguments.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $args = array() ) {

		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $join_sql;
		}

		$action_name = current_filter();

		if ( 'bp_messages_recipient_get_join_sql' === $action_name ) {
			$join_sql .= $this->exclude_joint_query( 'r.thread_id' );
		} else {
			$join_sql .= $this->exclude_joint_query( 'm.thread_id' );
		}

		/**
		 * Filters the hidden message Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $join_sql Join sql query
		 * @param array $class    Current class object.
		 */
		$join_sql = apply_filters( 'bp_suspend_message_get_join', $join_sql, $this );

		return $join_sql;
	}

	/**
	 * Prepare message Where SQL query to filter blocked message
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $where_conditions Folder Where sql.
	 * @param array $args             Query arguments.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions, $args = array() ) {
		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $where_conditions;
		}

		$action_name = current_filter();

		$where                  = array();
		$where['suspend_where'] = $this->exclude_where_query();

		/**
		 * Filters the hidden message Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $where Query to hide suspended user's folder.
		 * @param array $class current class object.
		 */
		$where = apply_filters( 'bp_suspend_message_get_where_conditions', $where, $this );

		if ( ! empty( array_filter( $where ) ) ) {
			if ( 'bp_messages_recipient_get_where_conditions' === $action_name ) {
				$where_conditions .= 'AND ( ' . implode( ' AND ', $where ) . ' )';
			} else {
					$where_conditions['suspend_where'] = '( ' . implode( ' AND ', $where ) . ' )';
			}
		}

		return $where_conditions;
	}

	/**
	 * Hide related content of message
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $message_id    message thread id.
	 * @param int|null $hide_sitewide Item hidden sitewide or user specific.
	 * @param array    $args          Parent args.
	 */
	public function manage_hidden_message( $message_id, $hide_sitewide, $args = array() ) {
		global $bp_background_updater;

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $message_id,
				'item_type' => self::$type,
			)
		);

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		$suspend_args = self::validate_keys( $suspend_args );

		BP_Core_Suspend::add_suspend( $suspend_args );

		if ( $this->background_disabled ) {
			$this->hide_related_content( $message_id, $hide_sitewide, $args );
		} else {
			$bp_background_updater->data(
				array(
					array(
						'callback' => array( $this, 'hide_related_content' ),
						'args'     => array( $message_id, $hide_sitewide, $args ),
					),
				)
			);
			$bp_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Un-hide related content of message
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $message_id     message thread id.
	 * @param int|null $hide_sitewide Item hidden sitewide or user specific.
	 * @param int      $force_all     Un-hide for all users.
	 * @param array    $args          Parent args.
	 */
	public function manage_unhidden_message( $message_id, $hide_sitewide, $force_all, $args = array() ) {
		global $bp_background_updater;

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $message_id,
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
			$thread_author_id = BP_Moderation_Message::get_content_owner_id( $message_id );
			if ( isset( $suspend_args['blocked_user'] ) && $thread_author_id === $suspend_args['blocked_user'] ) {
				unset( $suspend_args['blocked_user'] );
			}
		}

		$suspend_args = self::validate_keys( $suspend_args );

		BP_Core_Suspend::remove_suspend( $suspend_args );

		if ( $this->background_disabled ) {
			$this->unhide_related_content( $message_id, $hide_sitewide, $force_all, $args );
		} else {
			$bp_background_updater->data(
				array(
					array(
						'callback' => array( $this, 'unhide_related_content' ),
						'args'     => array( $message_id, $hide_sitewide, $force_all, $args ),
					),
				)
			);
			$bp_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Get Related data
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int   $member_id member id.
	 * @param array $args      parent args.
	 *
	 * @return array
	 */
	protected function get_related_contents( $member_id, $args = array() ) {
		return array();
	}

	/**
	 * Delete moderation data when actual message thread is deleted
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $thread_id deleted thread id.
	 */
	public function sync_moderation_data_on_delete( $thread_id ) {

		if ( empty( $thread_id ) ) {
			return;
		}

		BP_Core_Suspend::delete_suspend( $thread_id, $this->item_type );
	}
}
