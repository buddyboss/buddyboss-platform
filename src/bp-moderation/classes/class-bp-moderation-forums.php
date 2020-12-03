<?php
/**
 * BuddyBoss Moderation Forums Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Forums.
 *
 * @since BuddyBoss 2.0.0
 */
class BP_Moderation_Forums extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'forum';

	/**
	 * BP_Moderation_Forums constructor.
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		/**
		 * Moderation code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || ! bp_is_active( 'forums' ) || self::admin_bypass_check() ) {
			return;
		}

		$this->alias = $this->alias . 'f'; // f = Forum.

		add_filter( 'posts_join', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'posts_where', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_forums_search_join_sql', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_forums_search_where_sql', array( $this, 'update_where_sql' ), 10 );

		// delete forum moderation data when actual forum deleted.
		add_action( 'after_delete_post', array( $this, 'delete_moderation_data' ), 10, 2 );
	}

	/**
	 * Get Blocked Forums ids
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @return array
	 */
	public static function get_sitewide_hidden_ids() {
		return self::get_sitewide_hidden_item_ids( self::$moderation_type );
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param integer $forum_id Forum id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $forum_id ) {
		return get_post_field( 'post_author', $forum_id );
	}

	/**
	 * Get Content.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param integer $forum_id  Forum id.
	 * @param bool    $view_link add view link
	 *
	 * @return string
	 */
	public static function get_content_excerpt( $forum_id, $view_link = false ) {
		$forum_content = get_post_field( 'post_content', $forum_id );

		if ( true === $view_link ) {
			$link = '<a href="' . esc_url( self::get_permalink( (int) $forum_id ) ) . '">' . esc_html__( 'View',
					'buddyboss' ) . '</a>';

			$forum_content = ( ! empty( $forum_content ) ) ? $forum_content . ' ' . $link : $link;
		}

		return $forum_content;
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $forum_id forum id.
	 *
	 * @return string
	 */
	public static function get_permalink( $forum_id ) {
		$url = get_the_permalink( $forum_id );

		return add_query_arg( array( 'modbypass' => 1 ), $url );
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
		$content_types[ self::$moderation_type ] = __( 'Forum', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Prepare Forums Join SQL query to filter blocked Forums
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $join_sql Forums Join sql.
	 * @param object $wp_query WP_Query object.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $wp_query = null ) {
		global $wpdb;

		$action_name = current_filter();

		if ( 'bp_forums_search_join_sql' === $action_name ) {
			$join_sql .= $this->exclude_joint_query( 'p.ID' );
		} else {
			$forum_slug = bbp_get_forum_post_type();
			$post_types = wp_parse_slug_list( $wp_query->get( 'post_type' ) );
			if ( ! empty( $post_types ) && in_array( $forum_slug, $post_types, true ) ) {
				$join_sql .= $this->exclude_joint_query( "{$wpdb->posts}.ID" );
			}
		}

		return $join_sql;
	}

	/**
	 * Prepare Forums Where SQL query to filter blocked Forums
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $where_conditions Forums Where sql.
	 * @param object $wp_query         WP_Query object.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions, $wp_query = null ) {

		$action_name = current_filter();

		if ( 'bp_forums_search_where_sql' !== $action_name ) {
			$forum_slug = bbp_get_forum_post_type();
			$post_types = wp_parse_slug_list( $wp_query->get( 'post_type' ) );
			if ( empty( $post_types ) || ! in_array( $forum_slug, $post_types, true ) ) {
				return $where_conditions;
			}
		}

		$where                 = array();
		$where['forums_where'] = $this->exclude_where_query();

		/**
		 * Exclude block member forums
		 */
		$members_where = $this->exclude_member_forum_query();
		if ( $members_where ) {
			$where['members_where'] = $members_where;
		}

		/**
		 * Filters the Forums Moderation Where SQL statement.
		 *
		 * @since BuddyBoss 2.0.0
		 *
		 * @param array $where array of Forums moderation where query.
		 */
		$where = apply_filters( 'bp_moderation_forums_get_where_conditions', $where );

		if ( ! empty( array_filter( $where ) ) ) {
			if ( 'bp_forums_search_where_sql' === $action_name ) {
				$where_conditions['moderation_query'] = '( ' . implode( ' AND ', $where ) . ' )';
			} else {
				$where_conditions .= ' AND ( ' . implode( ' AND ', $where ) . ' )';
			}
		}

		return $where_conditions;
	}

	/**
	 * Get SQL for Exclude Blocked Members related forums
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @return string|bool
	 */
	private function exclude_member_forum_query() {
		global $wpdb;
		$sql                = false;
		$action_name        = current_filter();
		$hidden_members_ids = BP_Moderation_Members::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_members_ids ) ) {
			$forum_alias = ( 'bp_forums_search_where_sql' === $action_name ) ? 'p' : $wpdb->posts;
			$sql         = "( {$forum_alias}.post_author NOT IN ( " . implode( ',', $hidden_members_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Function to delete forum moderation data when actual forum is deleted
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int    $post_id post id being deleted.
	 * @param object $post    post data.
	 */
	public function delete_moderation_data( $post_id, $post ) {
		if ( ! empty( $post_id ) && ! empty( $post ) && bbp_get_forum_post_type() === $post->post_type ) {
			$moderation_obj = new BP_Moderation( $post_id, self::$moderation_type );
			if ( ! empty( $moderation_obj->id ) ) {
				$moderation_obj->delete( true );
			}
		}
	}
}
