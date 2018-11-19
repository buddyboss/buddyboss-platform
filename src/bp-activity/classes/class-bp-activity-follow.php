<?php
/**
 * BuddyBoss Follow Classes.
 *
 * @package BuddyBoss
 * @subpackage FollowClasses
 * @since BuddyBoss 3.1.1

 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * BuddyBoss Follow Object.
 *
 * Handles populating and saving follow relationships.
 *
 * @since BuddyBoss 3.1.1
 */
class BP_Activity_Follow {
	/**
	 * The follow ID.
	 *
	 * @since BuddyBoss 3.1.1
	 * @var int
	 */
	public $id = 0;

	/**
	 * The user ID of the person we want to follow.
	 *
	 * @since BuddyBoss 3.1.1
	 * @var int
	 */
	public $leader_id;

	/**
	 * The user ID of the person initiating the follow request.
	 *
	 * @since BuddyBoss 3.1.1
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
	 * Populate method.
	 *
	 * Used in constructor.
	 *
	 * @since BuddyBoss 3.1.1
	 */
	protected function populate() {
		global $wpdb, $bp;

		if ( $follow_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->activity->table_name_follow} WHERE leader_id = %d AND follower_id = %d", $this->leader_id, $this->follower_id ) ) ) {
			$this->id = $follow_id;
		}
	}

	/**
	 * Saves a follow relationship into the database.
	 *
	 * @since BuddyBoss 3.1.1
	 */
	public function save() {
		global $wpdb, $bp;

		// do not use these filters
		// use the 'bp_follow_before_save' hook instead
		$this->leader_id   = apply_filters( 'bp_follow_leader_id_before_save',   $this->leader_id,   $this->id );
		$this->follower_id = apply_filters( 'bp_follow_follower_id_before_save', $this->follower_id, $this->id );

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
			$result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->activity->table_name_follow} ( leader_id, follower_id ) VALUES ( %d, %d )", $this->leader_id, $this->follower_id ) );
			$this->id = $wpdb->insert_id;
		}

		do_action_ref_array( 'bp_follow_after_save', array( &$this ) );

		return $result;
	}

	/**
	 * Deletes a follow relationship from the database.
	 *
	 * @since BuddyBoss 3.1.1
	 */
	public function delete() {
		global $wpdb, $bp;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name_follow} WHERE id = %d", $this->id ) );
	}

	/** STATIC METHODS *****************************************************/

	/**
	 * Get the follower IDs for a given user.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param int $user_id The user ID.
	 * @return array
	 */
	public static function get_followers( $user_id ) {
		global $bp, $wpdb;
		return $wpdb->get_col( $wpdb->prepare( "SELECT follower_id FROM {$bp->activity->table_name_follow} WHERE leader_id = %d", $user_id ) );
	}

	/**
	 * Get the user IDs that a user is following.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param int $user_id The user ID to fetch.
	 * @return array
	 */
	public static function get_following( $user_id ) {
		global $bp, $wpdb;
		return $wpdb->get_col( $wpdb->prepare( "SELECT leader_id FROM {$bp->activity->table_name_follow} WHERE follower_id = %d", $user_id ) );
	}

	/**
	 * Get the follower / following counts for a given user.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param int $user_id The user ID to fetch counts for.
	 * @return array
	 */
	public static function get_counts( $user_id ) {
		global $bp, $wpdb;

		$followers = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->activity->table_name_follow} WHERE leader_id = %d", $user_id ) );
		$following = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->activity->table_name_follow} WHERE follower_id = %d", $user_id ) );

		return array( 'followers' => $followers, 'following' => $following );
	}

	/**
	 * Bulk check the follow status for a user against a list of user IDs.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param array $leader_ids The user IDs to check the follow status for.
	 * @param int $user_id The user ID to check against the list of leader IDs.
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
	 * @since BuddyBoss 3.1.1
	 *
	 * @param int $user_id The user ID
	 */
	public static function delete_all_for_user( $user_id ) {
		global $bp, $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name_follow} WHERE leader_id = %d OR follower_id = %d", $user_id, $user_id ) );
	}
}
