<?php
/**
 * BP_Core_Suspend base class
 *
 * This class calls all other classes associated with Suspend functionality.
 *
 * @package BuddyBoss\Suspend
 * @since   BuddyBoss 1.5.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BP_Core_Suspend
 */
class BP_Core_Suspend {

	/**
	 * Core function
	 */

	/**
	 * BP_Core_Suspend constructor.
	 *
	 * @since   BuddyBoss 1.5.6
	 */
	public function __construct() {
		$this->load_on_bp_dependency();
	}

	/**
	 * Function to load all the dependencies of Suspend classes.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function load_on_bp_dependency() {
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
			new BP_Suspend_Folder();
			new BP_Suspend_Document();
		}

		if ( bp_is_active( 'media' ) ) {
			new BP_Suspend_Album();
			new BP_Suspend_Media();
		}

		if ( bp_is_active( 'video' ) ) {
			new BP_Suspend_Video();
		}

		if ( bp_is_active( 'messages' ) ) {
			new BP_Suspend_Message();
		}
	}

	/**
	 * Function to add suspend entry
	 *
	 * @param array $args suspend arguments.
	 *
	 * @since BuddyBoss 1.5.6
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

		$members = false;

		if ( isset( $args['blocked_user'] ) ) {
			$members = $args['blocked_user'];
			unset( $args['blocked_user'] );
		}

		/**
		 * Hook fire before item suspended
		 *
		 * @since BuddyBoss 1.6.2
		 *
		 * @param array $args Item data.
		 */
		do_action( 'bb_suspend_before_add_suspend', $args );

		$recode = self::get_recode( $args['item_id'], $args['item_type'] );
		if ( ! empty( $recode ) ) {
			$where = array(
				'item_id'   => $args['item_id'],
				'item_type' => $args['item_type'],
			);

			$wpdb->update( $table_name, $args, $where ); // phpcs:ignore
		} else {
			$wpdb->insert( $table_name, $args ); // phpcs:ignore
		}

		if ( ! empty( $members ) && empty( $action_suspend ) ) {
			$members = (array) $members;
			foreach ( $members as $member ) {
				self::add_suspend_details(
					array(
						'suspend_id' => ! empty( $recode ) ? $recode->id : $wpdb->insert_id,
						'user_id'    => $member,
					)
				);
			}
		}

		/**
		 * Hook fire when item suspended
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param int $item_id item id.
		 */
		do_action( "bp_suspend_{$args['item_type']}_suspended", $args['item_id'] );

		return ! empty( $recode ) ? $recode->id : $wpdb->insert_id;
	}

	/**
	 * Function to get suspend entry
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int    $item_id   item id.
	 * @param string $item_type item type.
	 *
	 * @return array|false|object|void
	 */
	public static function get_recode( $item_id, $item_type ) {
		global $wpdb;
		$bp = buddypress();

		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->table_prefix}bp_suspend s WHERE s.item_id = %d AND s.item_type = %s limit 1", $item_id, $item_type ) ); // phpcs:ignore

		return ! empty( $result ) ? $result : false;
	}

	/**
	 * Get Suspend details entry
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $suspend_id suspend id.
	 *
	 * @return array
	 */
	public static function get_suspend_detail( $suspend_id ) {
		global $wpdb;
		$bp = buddypress();

		$table_name = "{$bp->table_prefix}bp_suspend_details";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT user_id FROM  {$table_name} WHERE suspend_id = %d", $suspend_id );
		return $wpdb->get_col( $sql ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Function to add suspend details entry.
	 *
	 * @param array $args suspend arguments.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @return bool|int
	 */
	public static function add_suspend_details( $args ) {
		global $wpdb;
		$bp = buddypress();

		$table_name = "{$bp->table_prefix}bp_suspend_details";

		if ( ! empty( $args['suspend_id'] ) || ! empty( $args['user_id'] ) ) {
			if ( ! self::get_recode_details( $args['suspend_id'], $args['user_id'] ) ) {
				return $wpdb->insert( $table_name, $args ); // phpcs:ignore
			}
		}
	}

	/**
	 * Suspend Details Funcations
	 */

	/**
	 * Function to get suspend entry
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $suspend_id Suspend id.
	 * @param int $user_id    User id.
	 *
	 * @return array|false|object|void
	 */
	public static function get_recode_details( $suspend_id, $user_id ) {
		global $wpdb;
		$bp = buddypress();

		$result = $wpdb->get_var( $wpdb->prepare( "SELECT sd.id FROM {$bp->table_prefix}bp_suspend_details sd WHERE sd.suspend_id = %d AND sd.user_id = %d limit 1", (int) $suspend_id, (int) $user_id ) ); // phpcs:ignore

		return ! empty( $result );
	}

	/**
	 * Remove Suspend user/content entry.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $args suspend arguments.
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

		/**
		 * Hook fire before item unsuspended
		 *
		 * @since BuddyBoss 1.6.2
		 *
		 * @param array $args item id.
		 */
		do_action( 'bb_suspend_before_remove_suspend', $args );

		$recode = self::get_recode( $args['item_id'], $args['item_type'] );
		if ( ! empty( $recode ) ) {

			$where = array(
				'item_id'   => $args['item_id'],
				'item_type' => $args['item_type'],
			);

			if ( ! empty( $member ) && empty( $action_suspend ) ) {
				self::remove_suspend_details(
					array(
						'suspend_id' => $recode->id,
						'user_id'    => $member,
					)
				);
			}

			$flag = $wpdb->update( $table_name, $args, $where ); // phpcs:ignore

			// Remove suspend record if item is not hidden.
			self::maybe_delete( $where['item_id'], $where['item_type'] );

			/**
			 * Hook fire when item unsuspended
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 * @param int $item_id item id.
			 */
			do_action( "bp_suspend_{$args['item_type']}_unsuspended", $args['item_id'] );

			return $flag;
		}

		return 1;
	}

	/**
	 * Remove Suspend details entry
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $where arguments.
	 *
	 * @return bool|int
	 */
	public static function remove_suspend_details( $where ) {
		global $wpdb;
		$bp = buddypress();

		$table_name = "{$bp->table_prefix}bp_suspend_details";

		return $wpdb->delete( $table_name, $where ); // phpcs:ignore
	}

	/**
	 * Function to delete suspend recode if entry is empty.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int    $item_id   item id.
	 * @param string $item_type item type.
	 *
	 * @return bool
	 */
	public static function maybe_delete( $item_id, $item_type ) {
		$recode = self::get_recode( $item_id, $item_type );

		if ( ! empty( $recode ) ) {
			$hide_sitewide  = (int) $recode->hide_sitewide;
			$hide_parent    = (int) $recode->hide_parent;
			$user_suspended = (int) $recode->user_suspended;
			$reported       = (int) $recode->reported;

			if ( empty( $hide_sitewide ) && empty( $hide_parent ) && empty( $user_suspended ) && empty( $reported ) ) {
				$exist = self::check_suspend_details_exist( $recode->id );
				if ( empty( $exist ) ) {
					self::delete_suspend( $item_id, $item_type );
				}
			}
		}
	}

	/**
	 * Check whether suspend details exist or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $suspend_id suspend id.
	 *
	 * @return bool
	 */
	public static function check_suspend_details_exist( $suspend_id ) {
		global $wpdb;
		$bp = buddypress();

		return $wpdb->get_var( $wpdb->prepare( "SELECT sd.id FROM {$bp->table_prefix}bp_suspend_details sd WHERE sd.suspend_id=%d limit 1", (int) $suspend_id ) ); // phpcs:ignore
	}

	/**
	 * Conditional function
	 */

	/**
	 * Function to check whether content is hide or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int    $item_id   item id.
	 * @param string $item_type item type.
	 *
	 * @return bool
	 */
	public static function check_hidden_content( $item_id, $item_type, $force = false ) {
		global $wpdb;
		$bp        = buddypress();
		$cache_key = 'bb_check_hidden_content_' . $item_type . '_' . $item_id;
		$result    = wp_cache_get( $cache_key, 'bb' );

		if ( false === $result || true === $force ) {
			$result = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->moderation->table_name} s WHERE s.item_id = %d AND s.item_type = %s AND ( hide_sitewide = 1 OR hide_parent = 1 )", $item_id, $item_type ) ); // phpcs:ignore
			wp_cache_set( $cache_key, $result, 'bb' );
		}

		return ! empty( $result );
	}

	/**
	 * Function to check whether content is related to blocked member.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int    $item_id   item id.
	 * @param string $item_type item type.
	 *
	 * @return bool
	 */
	public static function check_blocked_content( $item_id, $item_type ) {
		global $wpdb;
		$bp = buddypress();

		$hidden_users_ids = bp_moderation_get_hidden_user_ids();
		if ( ! empty( $hidden_users_ids ) ) {
			$result = $wpdb->get_var( $wpdb->prepare( "SELECT s.id FROM {$bp->table_prefix}bp_suspend s INNER JOIN {$bp->table_prefix}bp_suspend_details sd ON ( s.id = sd.suspend_id AND s.item_id = %d AND s.item_type = %s  ) WHERE `user_id` IN (" . implode( ',', $hidden_users_ids ) . ') limit 1', (int) $item_id, $item_type ) ); // phpcs:ignore

			return ! empty( $result );
		}

		return false;
	}

	/**
	 * Function to check whether content is hide as suspend user's content or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int    $item_id   item id.
	 * @param string $item_type item type.
	 * @param bool   $force     bypass caching or not.
	 *
	 * @return bool
	 */
	public static function check_suspended_content( $item_id, $item_type, $force = false ) {
		global $wpdb;
		$bp        = buddypress();
		$cache_key = 'bb_check_suspended_content_' . $item_type . '_' . $item_id;
		$result    = wp_cache_get( $cache_key, 'bb' );

		if ( false === $result || true === $force ) {
			$result = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->moderation->table_name} s WHERE s.item_id = %d AND s.item_type = %s AND user_suspended = 1", $item_id, $item_type ) ); // phpcs:ignore
			wp_cache_set( $cache_key, $result, 'bb' );
		}

		return ! empty( $result );
	}

	/**
	 * Function to check whether user is suspend or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $user_id user id.
	 *
	 * @return bool
	 */
	public static function check_user_suspend( $user_id ) {
		global $wpdb;
		$bp        = buddypress();
		$cache_key = 'bb_check_user_suspend_user_' . $user_id;
		$result    = wp_cache_get( $cache_key, 'bb' );

		if ( false === $result ) {
			$result = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->moderation->table_name} s WHERE s.item_id = %d AND s.item_type = %s AND user_suspended = 1", $user_id, BP_Suspend_Member::$type ) ); // phpcs:ignore
			wp_cache_set( $cache_key, $result, 'bb' );
		}

		return ! empty( $result );
	}

	/**
	 * Delete Suspend user/content entry.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int    $item_id   item id.
	 * @param string $item_type item type.
	 */
	public static function delete_suspend( $item_id, $item_type ) {
		global $wpdb;
		$bp = buddypress();

		$table_name = "{$bp->table_prefix}bp_suspend";

		$recode = self::get_recode( $item_id, $item_type );

		if ( ! empty( $recode ) ) {

			self::remove_suspend_details(
				array(
					'suspend_id' => $recode->id,
				)
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->delete(
				$table_name,
				array(
					'item_id'   => $item_id,
					'item_type' => $item_type,
				)
			);

			/**
			 * Hook to fire after the suspend record delete.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 * @param object $recode Suspended record object.
			 */
			do_action( 'suspend_after_delete', $recode );
		}
	}
}
