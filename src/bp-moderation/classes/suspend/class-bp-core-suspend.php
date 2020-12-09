<?php
/**
 * BP_Core_Suspend base class
 *
 * This class calls all other classes associated with Suspend functionality.
 *
 * @package BuddyBoss\Suspend
 * @since   BuddyBoss 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BP_Core_Suspend
 */
class BP_Core_Suspend {

	public function __construct() {
		$this->load_on_bp_dependency();
	}

	/**
	 * Function to load all the dependencies of Suspend classes.
	 *
	 * @since BuddyBoss 2.0.0
	 */
	function load_on_bp_dependency() {
		new BP_Suspend_Member();
		new BP_Suspend_Comment();

		if ( bp_is_active( 'activity' ) ) {
			new BP_Suspend_Activity();
			new BP_Suspend_Activity_Comment();
		}

		if ( bp_is_active( 'groups' ) ) {
			new BP_Suspend_Group();
		}

		if ( bp_is_active( 'forums' ) ) {
			new BP_Suspend_Forum();
			new BP_Suspend_Forum_Topic();
			new BP_Suspend_Forum_Reply();
		}

		if ( bp_is_active( 'document' ) ) {
			new BP_Suspend_Document();
		}

		if ( bp_is_active( 'media' ) ) {
			new BP_Suspend_Media();
		}
	}

	/**
	 * @param $args
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @return bool|int
	 */
	public static function add_suspend( $args ) {
		global $wpdb;
		$bp = buddypress();

		$table_name = "{$bp->table_prefix}bp_suspend";

		$args['blog_id'] = get_current_blog_id();

		$action_suspend = false;
		if ( isset( $args['action_suspend'] ) ) {
			$action_suspend = $args['action_suspend'];
			unset( $args['action_suspend'] );
		}

		$member = false;
		if ( ! empty( $args['blocked_user'] ) ) {
			$member = $args['blocked_user'];
			unset( $args['blocked_user'] );
		}

		$recode = self::get_recode( $args['item_id'], $args['item_type'] );
		if ( ! empty( $recode ) ) {
			$where = array(
				'item_id'   => $args['item_id'],
				'item_type' => $args['item_type'],
			);
			unset( $args['item_id'] );
			unset( $args['item_type'] );

			$flag = $wpdb->update( $table_name, $args, $where );
		} else {
			$flag = $wpdb->insert( $table_name, $args );
		}

		if ( ! empty( $member ) && empty( $action_suspend ) ) {
			self::add_suspend_details( array(
				'suspend_id' => ! empty( $recode ) ? $recode->id : $wpdb->insert_id,
				'user_id'    => $member,
			) );
		}

		return $flag;
	}

	/**
	 * @since BuddyBoss 2.0.0
	 *
	 * @param $item_id
	 * @param $item_type
	 *
	 * @return array|false|object|void
	 */
	public static function get_recode( $item_id, $item_type ) {
		global $wpdb;
		$bp = buddypress();

		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->table_prefix}bp_suspend s WHERE s.item_id = %d AND s.item_type = %s", $item_id, $item_type ) ); // phpcs:ignore

		return ! empty( $result ) ? $result : false;
	}

	/**
	 * @param $args
	 *
	 * @return bool|int
	 */
	public static function add_suspend_details( $args ) {
		global $wpdb;
		$bp = buddypress();

		$table_name = "{$bp->table_prefix}bp_suspend_details";


		return $wpdb->insert( $table_name, $args );
	}

	/**
	 * @since BuddyBoss 2.0.0
	 *
	 * @param $args
	 *
	 * @return bool|int
	 */
	public static function remove_suspend( $args ) {
		global $wpdb;
		$bp = buddypress();

		$table_name = "{$bp->table_prefix}bp_suspend";

		$action_suspend = false;
		if ( isset( $args['action_suspend'] ) ) {
			$action_suspend = $args['action_suspend'];
			unset( $args['action_suspend'] );
		}

		$member = false;
		if ( ! empty( $args['blocked_user'] ) ) {
			$member = $args['blocked_user'];
			unset( $args['blocked_user'] );
		}

		$recode = self::get_recode( $args['item_id'], $args['item_type'] );
		if ( ! empty( $recode ) ) {

			$where = array(
				'item_id'   => $args['item_id'],
				'item_type' => $args['item_type'],
			);
			unset( $args['item_id'] );
			unset( $args['item_type'] );

			if ( ! empty( $member ) && empty( $action_suspend ) ) {
				self::remove_suspend_details( array(
					'suspend_id' => $recode->id,
					'user_id'    => $member,
				) );
			}

			return $wpdb->update( $table_name, $args, $where );
		}

		return 1;
	}

	/**
	 * @since BuddyBoss 2.0.0
	 *
	 * @param $where
	 *
	 * @return bool|int
	 */
	public static function remove_suspend_details( $where ) {
		global $wpdb;
		$bp = buddypress();

		$table_name = "{$bp->table_prefix}bp_suspend_details";

		return $wpdb->delete( $table_name, $where );
	}

	/**
	 * @since BuddyBoss 2.0.0
	 *
	 * @param $item_id
	 * @param $item_type
	 *
	 * @return bool
	 */
	public static function check_hidden_content( $item_id, $item_type ) {
		global $wpdb;
		$bp = buddypress();

		$result = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->table_prefix}bp_suspend s WHERE s.item_id = %d AND s.item_type = %s AND ( hide_sitewide = 1 OR hide_parent = 1 )", $item_id, $item_type ) ); // phpcs:ignore

		return ! empty( $result );
	}

	/**
	 * @since BuddyBoss 2.0.0
	 *
	 * @param $item_id
	 * @param $item_type
	 *
	 * @return bool
	 */
	public static function check_suspended_content( $item_id, $item_type ) {
		global $wpdb;
		$bp = buddypress();

		$result = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->table_prefix}bp_suspend s WHERE s.item_id = %d AND s.item_type = %s AND user_suspended = 1", $item_id, $item_type ) ); // phpcs:ignore

		return ! empty( $result );
	}

	/**
	 * @since BuddyBoss 2.0.0
	 *
	 * @param $user_id
	 *
	 * @return bool
	 */
	public static function check_user_suspend( $user_id ) {
		global $wpdb;
		$bp = buddypress();

		$result = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->table_prefix}bp_suspend s WHERE s.item_id = %d AND s.item_type = %s AND user_suspended = 1", $user_id, BP_Suspend_Member::$type ) ); // phpcs:ignore

		return ! empty( $result );
	}
}
