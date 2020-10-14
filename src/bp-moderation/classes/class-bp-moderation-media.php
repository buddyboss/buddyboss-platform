<?php
/**
 * BuddyBoss Moderation Media Classes
 *
 * @package BuddyBoss\Moderation
 * @since BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Media.
 *
 * @since BuddyBoss 1.5.4
 */
class BP_Moderation_Media extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'media';

	/**
	 * BP_Moderation_Group constructor.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function __construct() {

		/**
		 * Moderation code should not add for WordPress backend & IF component is not active
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || ! bp_is_active( 'media' ) ) {
			return;
		}
		$this->alias     = $this->alias . 'm'; // m = media.
		$this->item_type = self::$moderation_type;

		/*add_filter( 'bp_media_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );*/
		add_filter( 'bp_media_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
	}

	/**
	 * Prepare Media Join SQL query to filter blocked Media
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $join_sql Media Join sql.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql ) {
		$join_sql .= $this->exclude_joint_query( 'm.id' );

		return $join_sql;
	}

	/**
	 * Prepare Media Where SQL query to filter blocked Media
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array $where_conditions Media Where sql.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions ) {
		$where = array();
		// $where['media_where'] = $this->exclude_where_query();

		/**
		 * Exclude block member Media
		 */
		$members_where = $this->exclude_member_media_query();
		if ( $members_where ) {
			$where['members_where'] = $members_where;
		}

		/**
		 * Exclude block activity Media
		 */
		if ( bp_is_active( 'activity' ) ) {
			$members_where = $this->exclude_activity_media_query();
			if ( $members_where ) {
				$where['activity_where'] = $members_where;
			}
		}

		/**
		 * Exclude Blocked Groups Media
		 */
		if ( bp_is_active( 'groups' ) ) {
			$groups_where = $this->exclude_group_media_query();
			if ( ! empty( $groups_where ) ) {
				$where['groups_where'] = $groups_where;
			}
		}

		/**
		 * Exclude Blocked Forum/Topic/Reply Media
		 */
		if ( bp_is_active( 'forums' ) ) {
			$groups_where = $this->exclude_forum_media_query();
			if ( ! empty( $groups_where ) ) {
				$where['forums_where'] = $groups_where;
			}
		}

		/**
		 * Filters the Media Moderation Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param array $where array of Media moderation where query.
		 */
		$where = apply_filters( 'bp_moderation_media_get_where_conditions', $where );

		$where_conditions['moderation_where'] = '( ' . implode( ' AND ', $where ) . ' )';

		return $where_conditions;
	}

	/**
	 * Get Exclude Blocked Members SQL
	 *
	 * @return string|bool
	 */
	private function exclude_member_media_query() {
		$sql                = false;
		$hidden_members_ids = BP_Moderation_Members::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_members_ids ) ) {
			$sql = '( m.user_id NOT IN ( ' . implode( ',', $hidden_members_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get Exclude Blocked Activity SQL
	 *
	 * @return string|bool
	 */
	private function exclude_activity_media_query() {
		$sql                 = false;
		$hidden_activity_ids = BP_Moderation_Activity::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_activity_ids ) ) {
			$sql = '( m.activity_id NOT IN ( ' . implode( ',', $hidden_activity_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get Exclude Blocked Group SQL
	 *
	 * @return string|bool
	 */
	private function exclude_group_media_query() {
		$sql              = false;
		$hidden_group_ids = BP_Moderation_Groups::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_group_ids ) ) {
			$sql = '( m.group_id NOT IN ( ' . implode( ',', $hidden_group_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get Exclude Blocked forum/Topics/Replies SQL
	 *
	 * @return string|bool
	 */
	private function exclude_forum_media_query() {
		$sql              = false;
		$hidden_topic_ids = BP_Moderation_Forum_Topics::get_sitewide_hidden_ids();
		$hidden_reply_ids = BP_Moderation_Forum_Replies::get_sitewide_hidden_ids();
		$hidden_media_ids = self::get_media_ids_meta( array_merge( $hidden_topic_ids, $hidden_reply_ids ) );
		if ( ! empty( $hidden_media_ids ) ) {
			$sql = '( m.id NOT IN ( ' . implode( ',', $hidden_media_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get blocked Media ids
	 *
	 * @return array
	 */
	public static function get_sitewide_hidden_ids() {
		return self::get_sitewide_hidden_item_ids( self::$moderation_type );
	}

	/**
	 * Get media ids of blocked posts [ Forums/topics/replies ] from meta
	 *
	 * @param array $posts_ids Posts ids.
	 *
	 * @return array Media IDs
	 */
	private static function get_media_ids_meta( $posts_ids ) {
		$media_ids = array();
		if ( ! empty( $posts_ids ) ) {
			foreach ( $posts_ids as $post_id ) {
				$post_media = get_post_meta( $post_id, 'bp_media_ids', true );
				$post_media = wp_parse_id_list( $post_media );
				if ( ! empty( $post_media ) ) {
					$media_ids = array_merge( $media_ids, $post_media );
				}
			}
		}
		return $media_ids;
	}

}
