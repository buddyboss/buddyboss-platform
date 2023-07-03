<?php
/**
 * BuddyBoss Follow Classes.
 *
 * @package BuddyBoss\Activity
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * BuddyBoss Follow Component.
 *
 * Handles populating and saving follow relationships.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Activity_Follow {
	/**
	 * The follow ID.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var int
	 */
	public $id = 0;

	/**
	 * The user ID of the person we want to follow.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var int
	 */
	public $leader_id;

	/**
	 * The user ID of the person initiating the follow request.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var int
	 */
	public $follower_id;

	/**
	 * Constructor.
	 *
	 * @param int $leader_id The user ID of the user you want to follow.
	 * @param int $follower_id The user ID initiating the follow request.
	 */
	public function __construct( $leader_id = 0, $follower_id = 0 ) {
		if ( ! empty( $leader_id ) && ! empty( $follower_id ) ) {
			$this->leader_id   = (int) $leader_id;
			$this->follower_id = (int) $follower_id;
			$this->populate();
		}
	}

	/**
	 * Populate the object with data about the specific follow item.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function populate() {
		global $wpdb, $bp;

		$cache_key = $this->leader_id . '_' . $this->follower_id;
		$row       = bp_core_get_incremented_cache( $cache_key, 'bp_activity_follow' );

		if ( false === $row ) {
			// phpcs:ignore
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name_follow} WHERE leader_id = %d AND follower_id = %d", $this->leader_id, $this->follower_id ) );
			bp_core_set_incremented_cache( $cache_key, 'bp_activity_follow', $row );
		}

		if ( ! empty( $row ) ) {
			$this->id = $row->id;
		} else {
			$this->id = 0;
		}
	}

	/**
	 * Saves a follow relationship to the database.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function save() {
		global $wpdb, $bp;

		// do not use these filters
		// use the 'bp_follow_before_save' hook instead.
		$this->leader_id   = apply_filters( 'bp_follow_leader_id_before_save', $this->leader_id, $this->id );
		$this->follower_id = apply_filters( 'bp_follow_follower_id_before_save', $this->follower_id, $this->id );

		/**
		 * @todo add title/description
		 *
		 * @since BuddyBoss 1.0.0
		 */
		do_action_ref_array( 'bp_follow_before_save', array( &$this ) );

		// leader ID is required
		// this allows plugins to bail out of saving a follow relationship
		// use hooks above to redeclare 'leader_id' so it is empty if you need to bail
		if ( empty( $this->leader_id ) ) {
			return false;
		}

		// update existing entry
		if ( $this->id ) {
			$result = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->activity->table_name_follow} SET leader_id = %d, follower_id = %d WHERE id = %d", $this->leader_id, $this->follower_id, $this->id ) );

			// add new entry
		} else {
			$result   = $wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->activity->table_name_follow} ( leader_id, follower_id ) VALUES ( %d, %d )", $this->leader_id, $this->follower_id ) );
			$this->id = $wpdb->insert_id;
		}

		/**
		 * @todo add title/description
		 *
		 * @since BuddyBoss 1.0.0
		 */
		do_action_ref_array( 'bp_follow_after_save', array( &$this ) );

		return $result;
	}

	/**
	 * Deletes a follow relationship from the database.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function delete() {
		global $wpdb, $bp;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name_follow} WHERE id = %d", $this->id ) );
	}

	/** STATIC METHODS *****************************************************/

	/**
	 * Get the follower IDs for a given user.
	 *
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 2.3.70 Added support for query arguments.
	 *
	 * @param int   $user_id    The user ID.
	 * @param array $query_args Query arguments.
	 *
	 * @return array
	 */
	public static function get_followers( $user_id, $query_args = array() ) {
		global $bp, $wpdb;

		$defaults = array(
			'user_id' => $user_id,
		);

		$query_args = bp_parse_args( $query_args, $defaults );

		$sql['select'] = "SELECT u.follower_id FROM {$bp->activity->table_name_follow} u ";
		$sql['select'] = apply_filters( 'bp_user_query_join_sql', $sql['select'], 'follower_id' );

		$sql['where'][] = $wpdb->prepare( "leader_id = %d", $user_id );
		$sql['where']   = apply_filters( 'bp_user_query_where_sql', $sql['where'], 'follower_id' );

		$where_sql      = 'WHERE ' . join( ' AND ', $sql['where'] );
		$followers_sql  = "{$sql['select']} {$where_sql}";

		if ( ! empty( $query_args['page'] ) && ! empty( $query_args['per_page'] ) ) {
			$followers_sql .= $wpdb->prepare( ' LIMIT %d, %d', intval( ( $query_args['page'] - 1 ) * $query_args['per_page'] ), intval( $query_args['per_page'] ) );
		}

		$cached = bp_core_get_incremented_cache( $followers_sql, 'bp_activity_follow' );

		if ( false === $cached ) {
			$follower_ids = $wpdb->get_col( $followers_sql );
			bp_core_set_incremented_cache( $followers_sql, 'bp_activity_follow', $follower_ids );
		} else {
			$follower_ids = $cached;
		}

		return (array) $follower_ids;
	}

	/**
	 * Get the user IDs that a user is following.
	 *
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 2.3.70 Added support for query arguments.
	 *
	 * @param int   $user_id    The user ID to fetch.
	 * @param array $query_args Query arguments.
	 *
	 * @return array
	 */
	public static function get_following( $user_id, $query_args = array() ) {
		global $bp, $wpdb;

		$defaults = array(
			'user_id' => $user_id,
		);

		$query_args = bp_parse_args( $query_args, $defaults );

		$sql['select'] = "SELECT u.leader_id FROM {$bp->activity->table_name_follow} u ";
		$sql['select'] = apply_filters( 'bp_user_query_join_sql', $sql['select'], 'leader_id' );

		$sql['where'][] = $wpdb->prepare( "follower_id = %d", $user_id );
		$sql['where']   = apply_filters( 'bp_user_query_where_sql', $sql['where'], 'leader_id' );

		$where_sql      = 'WHERE ' . join( ' AND ', $sql['where'] );
		$following_sql  = "{$sql['select']} {$where_sql}";

		if ( ! empty( $query_args['page'] ) && ! empty( $query_args['per_page'] ) ) {
			$following_sql .= $wpdb->prepare( ' LIMIT %d, %d', intval( ( $query_args['page'] - 1 ) * $query_args['per_page'] ), intval( $query_args['per_page'] ) );
		}

		$cached = bp_core_get_incremented_cache( $following_sql, 'bp_activity_follow' );

		if ( false === $cached ) {
			$following_ids = $wpdb->get_col( $following_sql );
			bp_core_set_incremented_cache( $following_sql, 'bp_activity_follow', $following_ids );
		} else {
			$following_ids = $cached;
		}

		return (array) $following_ids;
	}

	/**
	 * Get the follower / following counts for a given user.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $user_id The user ID to fetch counts for.
	 * @return array
	 */
	public static function get_counts( $user_id ) {
		global $bp, $wpdb;

		$followers = wp_cache_get( 'bp_total_follower_for_user_' . $user_id, 'bp' );

		if ( false === $followers ) {
			$followers = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->activity->table_name_follow} WHERE leader_id = %d", $user_id ) );
			wp_cache_set( 'bp_total_follower_for_user_' . $user_id, $followers, 'bp' );
		}

		$following = wp_cache_get( 'bp_total_following_for_user_' . $user_id, 'bp' );

		if ( false === $following ) {
			$following = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->activity->table_name_follow} WHERE follower_id = %d", $user_id ) );
			wp_cache_set( 'bp_total_following_for_user_' . $user_id, $following, 'bp' );
		}

		return array(
			'followers' => (int) $followers,
			'following' => (int) $following,
		);
	}

	/**
	 * Bulk check the follow status for a user against a list of user IDs.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $leader_ids The user IDs to check the follow status for.
	 * @param int   $user_id The user ID to check against the list of leader IDs.
	 * @return array
	 */
	public static function bulk_check_follow_status( $leader_ids, $user_id = false ) {
		global $bp, $wpdb;

		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		if ( empty( $user_id ) ) {
			return false;
		}

		$leader_ids = implode( ',', wp_parse_id_list( (array) $leader_ids ) );

		return $wpdb->get_results( $wpdb->prepare( "SELECT leader_id, id FROM {$bp->activity->table_name_follow} WHERE follower_id = %d AND leader_id IN ($leader_ids)", $user_id ) );
	}

	/**
	 * Deletes all follow relationships for a given user.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $user_id The user ID
	 * @return array|bool array of ids deleted or false if nothing was deleted
	 */
	public static function delete_all_for_user( $user_id ) {
		global $bp, $wpdb;

		$ids = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT id FROM {$bp->activity->table_name_follow} WHERE leader_id = %d OR follower_id = %d", $user_id, $user_id ) );

		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name_follow} WHERE leader_id = %d OR follower_id = %d", $user_id, $user_id ) );

		// Bail if nothing was deleted.
		if ( empty( $deleted ) ) {
			return false;
		}

		return $ids;
	}
}
