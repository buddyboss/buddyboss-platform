<?php
/**
 * BuddyBoss Moderation Document Classes
 *
 * @package BuddyBoss\Moderation
 * @since BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Document.
 *
 * @since BuddyBoss 1.5.4
 */
class BP_Moderation_Document extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'document';

	/**
	 * BP_Moderation_Group constructor.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function __construct() {

		/**
		 * Moderation code should not add for WordPress backend & IF component is not active
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || ! bp_is_active( 'document' ) ) {
			return;
		}
		$this->item_type = self::$moderation_type;

		/*add_filter( 'bp_document_get_join_sql', array( $this, 'update_join_sql' ), 10 );*/
		add_filter( 'bp_document_get_where_conditions_document', array( $this, 'update_where_sql' ), 10 );
	}

	/**
	 * Prepare Document Join SQL query to filter blocked Document
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $join_sql Document Join sql.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql ) {
		$join_sql .= $this->exclude_joint_query( 'd.id' );

		return $join_sql;
	}

	/**
	 * Prepare Document Where SQL query to filter blocked Document
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array $where_conditions Document Where sql.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions ) {
		$where = array();
		// $where['document_where'] = $this->exclude_where_query();

		/**
		 * Exclude block member Document
		 */
		$members_where = $this->exclude_member_document_query();
		if ( $members_where ) {
			$where['members_where'] = $members_where;
		}

		/**
		 * Exclude block activity Document
		 */
		if ( bp_is_active( 'activity' ) ) {
			$members_where = $this->exclude_activity_document_query();
			if ( $members_where ) {
				$where['activity_where'] = $members_where;
			}
		}

		/**
		 * Exclude Blocked Groups Document
		 */
		if ( bp_is_active( 'groups' ) ) {
			$groups_where = $this->exclude_group_document_query();
			if ( ! empty( $groups_where ) ) {
				$where['groups_where'] = $groups_where;
			}
		}

		/**
		 * Exclude Blocked Forum/Topic/Reply Document
		 */
		if ( bp_is_active( 'forums' ) ) {
			$forums_where = $this->exclude_forum_document_query();
			if ( ! empty( $forums_where ) ) {
				$where['forums_where'] = $forums_where;
			}
		}

		/**
		 * Exclude Blocked Messages Media
		 */
		if ( bp_is_active( 'messages' ) ) {
			$messages_where = $this->exclude_message_document_query();
			if ( ! empty( $messages_where ) ) {
				$where['messages_where'] = $messages_where;
			}
		}

		/**
		 * Filters the Document Moderation Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param array $where array of Document moderation where query.
		 */
		$where = apply_filters( 'bp_moderation_document_get_where_conditions', $where );

		$where_conditions['moderation_where'] = '( ' . implode( ' AND ', $where ) . ' )';

		return $where_conditions;
	}

	/**
	 * Get Exclude Blocked Members SQL
	 *
	 * @return string|bool
	 */
	private function exclude_member_document_query() {
		$sql                = false;
		$hidden_members_ids = BP_Moderation_Members::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_members_ids ) ) {
			$sql = '( d.user_id NOT IN ( ' . implode( ',', $hidden_members_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get Exclude Blocked Activity SQL
	 *
	 * @return string|bool
	 */
	private function exclude_activity_document_query() {
		$sql                 = false;
		$hidden_activity_ids = BP_Moderation_Activity::get_sitewide_hidden_ids();
		$hidden_document_ids = self::get_Document_ids_meta( $hidden_activity_ids, 'bp_activity_get_meta' );
		if ( ! empty( $hidden_document_ids ) ) {
			$sql = '( d.id NOT IN ( ' . implode( ',', $hidden_document_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get Exclude Blocked Group SQL
	 *
	 * @return string|bool
	 */
	private function exclude_group_document_query() {
		$sql              = false;
		$hidden_group_ids = BP_Moderation_Groups::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_group_ids ) ) {
			$sql = '( d.group_id NOT IN ( ' . implode( ',', $hidden_group_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get Exclude Blocked forum/Topics/Replies SQL
	 *
	 * @return string|bool
	 */
	private function exclude_forum_document_query() {
		$sql                 = false;
		$hidden_topic_ids    = BP_Moderation_Forum_Topics::get_sitewide_hidden_ids();
		$hidden_reply_ids    = BP_Moderation_Forum_Replies::get_sitewide_hidden_ids();
		$hidden_document_ids = self::get_Document_ids_meta( array_merge( $hidden_topic_ids, $hidden_reply_ids ) );
		if ( ! empty( $hidden_document_ids ) ) {
			$sql = '( d.id NOT IN ( ' . implode( ',', $hidden_document_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get Exclude Blocked Message SQL
	 *
	 * @return string|bool
	 */
	private function exclude_message_document_query() {
		$sql              = false;
		$hidden_message_ids = BP_Moderation_Messages::get_sitewide_messages_hidden_ids();
		$hidden_document_ids = self::get_Document_ids_meta( $hidden_message_ids, 'bp_messages_get_meta' );
		if ( ! empty( $hidden_document_ids ) ) {
			$sql = '( d.id NOT IN ( ' . implode( ',', $hidden_document_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get blocked Document ids
	 *
	 * @return array
	 */
	public static function get_sitewide_hidden_ids() {
		return self::get_sitewide_hidden_item_ids( self::$moderation_type );
	}

	/**
	 * Get Document ids of blocked posts [ Forums/topics/replies ] from meta
	 *
	 * @param array  $posts_ids Posts ids.
	 * @param string $function Function Name to get meta.
	 *
	 * @return array Document IDs
	 */
	private static function get_document_ids_meta( $posts_ids, $function = 'get_post_meta' ) {
		$document_ids = array();

		if ( ! function_exists( $function ) ) {
			return $document_ids;
		}

		if ( ! empty( $posts_ids ) ) {
			foreach ( $posts_ids as $post_id ) {
				$post_document = $function( $post_id, 'bp_document_ids', true );
				$post_document = wp_parse_id_list( $post_document );
				if ( ! empty( $post_document ) ) {
					$document_ids = array_merge( $document_ids, $post_document );
				}
			}
		}
		return $document_ids;
	}

}
