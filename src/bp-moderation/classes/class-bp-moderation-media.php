<?php
/**
 * BuddyBoss Moderation Media Classes
 *
 * @package BuddyBoss\Moderation
 *
 * @since   BuddyBoss 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Media.
 *
 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		/**
		 * Moderation code should not add for WordPress backend & IF component is not active
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || ! bp_is_active( 'media' ) ) {
			return;
		}

		$this->alias = $this->alias . 'm'; // m = media.

		/*add_filter( 'bp_media_get_join_sql', array( $this, 'update_join_sql' ), 10 );*/
		add_filter( 'bp_media_get_where_conditions', array( $this, 'update_where_sql' ), 10 );

		// Search Query.
		/*add_filter( 'bp_media_search_join_sql_photo', array( $this, 'update_join_sql' ), 10 );*/
		add_filter( 'bp_media_search_where_conditions_photo', array( $this, 'update_where_sql' ), 10 );
	}

	/**
	 * Add Moderation content type.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $content_types Supported Contents types.
	 *
	 * @return mixed
	 */
	public function add_content_types( $content_types ) {
		$content_types[ self::$moderation_type ] = __( 'Photos', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Prepare Media Join SQL query to filter blocked Media
	 *
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
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
			$forums_where = $this->exclude_forum_media_query();
			if ( ! empty( $forums_where ) ) {
				$where['forums_where'] = $forums_where;
			}
		}

		/**
		 * Exclude Blocked Messages Media
		 */
		if ( bp_is_active( 'messages' ) ) {
			$messages_where = $this->exclude_message_media_query();
			if ( ! empty( $messages_where ) ) {
				$where['messages_where'] = $messages_where;
			}
		}

		/**
		 * Filters the Media Moderation Where SQL statement.
		 *
		 * @since BuddyBoss 2.0.0
		 *
		 * @param array $where array of Media moderation where query.
		 */
		$where = apply_filters( 'bp_moderation_media_get_where_conditions', $where );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['moderation_where'] = '( ' . implode( ' AND ', $where ) . ' )';
		}

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
		$sql                         = false;
		$hidden_activity_ids         = BP_Moderation_Activity::get_sitewide_hidden_ids();
		$hidden_activity_comment_ids = BP_Moderation_Activity_Comment::get_sitewide_hidden_ids();
		$hidden_media_ids            = self::get_media_ids_meta( array_merge( $hidden_activity_ids, $hidden_activity_comment_ids ), 'bp_activity_get_meta' );
		if ( ! empty( $hidden_media_ids ) ) {
			$sql = '( m.id NOT IN ( ' . implode( ',', $hidden_media_ids ) . ' ) )';
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
	 * Get Exclude Blocked Message SQL
	 *
	 * @return string|bool
	 */
	private function exclude_message_media_query() {
		$sql                = false;
		$hidden_message_ids = BP_Moderation_Messages::get_sitewide_messages_hidden_ids();
		$hidden_media_ids   = self::get_media_ids_meta( $hidden_message_ids, 'bp_messages_get_meta' );
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
	 * @param array  $posts_ids Posts ids.
	 * @param string $function  Function Name to get meta.
	 *
	 * @return array Media IDs
	 */
	private static function get_media_ids_meta( $posts_ids, $function = 'get_post_meta' ) {
		$media_ids = array();

		if ( ! function_exists( $function ) ) {
			return $media_ids;
		}

		if ( ! empty( $posts_ids ) ) {
			foreach ( $posts_ids as $post_id ) {
				$post_media = $function( $post_id, 'bp_media_ids', true );
				$post_media = wp_parse_id_list( $post_media );
				if ( ! empty( $post_media ) ) {
					$media_ids = array_merge( $media_ids, $post_media );
				}
			}
		}

		return $media_ids;
	}

	/**
	 * Get Content owner id.
	 *
	 * @param integer $media_id Media id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $media_id ) {
		$media = new BP_Media( $media_id );

		return ( ! empty( $media->user_id ) ) ? $media->user_id : 0;
	}

	/**
	 * Get Content.
	 *
	 * @param integer $media_id Media id.
	 *
	 * @return string
	 */
	public static function get_content_excerpt( $media_id ) {
		$media = new BP_Media( $media_id );

		return ( ! empty( $media->title ) ) ? $media->title : '';
	}

	/**
	 * Report content
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $args Content data.
	 *
	 * @return string
	 */
	public static function report( $args ) {
		return parent::report( $args );
	}

}
