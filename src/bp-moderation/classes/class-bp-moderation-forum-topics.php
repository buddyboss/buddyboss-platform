<?php
/**
 * BuddyBoss Moderation Forum Topics Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Forum Topics.
 *
 * @since BuddyBoss 2.0.0
 */
class BP_Moderation_Forum_Topics extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'forum_topic';

	/**
	 * BP_Moderation_Forum_Topics constructor.
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

		$this->alias = $this->alias . 'ft'; // ft: Forum Topic.

		add_filter( 'posts_join', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'posts_where', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_forum_topic_search_join_sql', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_forum_topic_search_where_sql', array( $this, 'update_where_sql' ), 10 );

		// button class.
		add_filter( 'bp_moderation_get_report_button_args', array( $this, 'update_button_args' ), 10, 3 );

		// delete topic moderation data when actual topic deleted.
		add_action( 'after_delete_post', array( $this, 'delete_moderation_data' ), 10, 2 );
	}

	/**
	 * Get blocked Topics that also include Blocked forum's topics
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @return array
	 */
	public static function get_sitewide_hidden_ids() {
		$hidden_topic_ids = self::get_sitewide_hidden_item_ids( self::$moderation_type );

		$hidden_forum_ids = BP_Moderation_Forums::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_forum_ids ) ) {
			$topics_query = new WP_Query(
				array(
					'fields'                 => 'ids',
					'post_type'              => bbp_get_topic_post_type(),
					'post_status'            => 'publish',
					'post_parent__in'        => $hidden_forum_ids,
					'posts_per_page'         => - 1,
					// Need to get all topics id of hidden forums.
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'suppress_filters'       => true,
				)
			);

			if ( $topics_query->have_posts() ) {
				$hidden_topic_ids = array_merge( $hidden_topic_ids, $topics_query->posts );
			}
		}

		return $hidden_topic_ids;
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param integer $topic_id Topic id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $topic_id ) {
		return get_post_field( 'post_author', $topic_id );
	}

	/**
	 * Get Content.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param integer $topic_id  Topic id.
	 * @param bool    $view_link add view link.
	 *
	 * @return string
	 */
	public static function get_content_excerpt( $topic_id, $view_link = false ) {
		$topic_content = get_post_field( 'post_content', $topic_id );

		if ( true === $view_link ) {
			$link = '<a href="' . esc_url( self::get_permalink( (int) $topic_id ) ) . '">' . esc_html__( 'View',
					'buddyboss' ) . '</a>';;

			$topic_content = ( ! empty( $topic_content ) ) ? $topic_content . ' ' . $link : $link;
		}

		return ( ! empty( $topic_content ) ) ? $topic_content : '';
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $topic_id topic id.
	 *
	 * @return string
	 */
	public static function get_permalink( $topic_id ) {
		$url = get_the_permalink( $topic_id );

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
		$content_types[ self::$moderation_type ] = __( 'Forum Discussion', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Prepare Forum Topics Join SQL query to filter blocked Forum Topics
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $join_sql Forum Topics Join sql.
	 * @param object $wp_query WP_Query object.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $wp_query = null ) {
		global $wpdb;

		$action_name = current_filter();

		if ( 'bp_forum_topic_search_join_sql' === $action_name ) {
			$join_sql .= $this->exclude_joint_query( 'p.ID' );
		} else {
			$topic_slug = bbp_get_topic_post_type();
			$post_types = wp_parse_slug_list( $wp_query->get( 'post_type' ) );
			if ( ! empty( $post_types ) && in_array( $topic_slug, $post_types, true ) ) {
				$join_sql .= $this->exclude_joint_query( "{$wpdb->posts}.ID" );
			}
		}

		return $join_sql;
	}

	/**
	 * Prepare Forum Topics Where SQL query to filter blocked Forum Topics
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $where_conditions Forum Topics Where sql.
	 * @param object $wp_query         WP_Query object.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions, $wp_query = null ) {

		$action_name = current_filter();

		if ( 'bp_forum_topic_search_where_sql' !== $action_name ) {
			$topic_slug = bbp_get_topic_post_type();
			$post_types = wp_parse_slug_list( $wp_query->get( 'post_type' ) );
			if ( empty( $post_types ) || ! in_array( $topic_slug, $post_types, true ) ) {
				return $where_conditions;
			}
		}

		$where                       = array();
		$where['forum_topics_where'] = $this->exclude_where_query();

		/**
		 * Exclude block member forum topics
		 */
		$members_where = $this->exclude_member_topic_query();
		if ( $members_where ) {
			$where['members_where'] = $members_where;
		}

		/**
		 * Exclude block Topic replies
		 */
		$forums_where = $this->exclude_forum_topic_query();
		if ( $forums_where ) {
			$where['forums_where'] = $forums_where;
		}

		/**
		 * Filters the Forum Topics Moderation Where SQL statement.
		 *
		 * @since BuddyBoss 2.0.0
		 *
		 * @param array $where array of Forum Topics moderation where query.
		 */
		$where = apply_filters( 'bp_moderation_forum_topics_get_where_conditions', $where );

		if ( ! empty( array_filter( $where ) ) ) {
			if ( 'bp_forum_topic_search_where_sql' === $action_name ) {
				$where_conditions['moderation_query'] = '( ' . implode( ' AND ', $where ) . ' )';
			} else {
				$where_conditions .= ' AND ( ' . implode( ' AND ', $where ) . ' )';
			}
		}

		return $where_conditions;
	}

	/**
	 * Function to modify the button class
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array  $button      Button args.
	 * @param string $item_type   Content type.
	 * @param string $is_reported Item reported.
	 *
	 * @return string
	 */
	public function update_button_args( $button, $item_type, $is_reported ) {

		if ( self::$moderation_type === $item_type ) {
			if ( $is_reported ) {
				$button['button_attr']['class'] = 'button item-button bp-secondary-action outline reported-content';
			} else {
				$button['button_attr']['class'] = 'button item-button bp-secondary-action outline report-content';
			}
		}

		return $button;
	}

	/**
	 * Get SQL for Exclude Blocked Members related topics
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @return string|bool
	 */
	private function exclude_member_topic_query() {
		global $wpdb;
		$sql                = false;
		$action_name        = current_filter();
		$hidden_members_ids = BP_Moderation_Members::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_members_ids ) ) {
			$topic_alias = ( 'bp_forum_topic_search_where_sql' === $action_name ) ? 'p' : $wpdb->posts;
			$sql         = "( {$topic_alias}.post_author NOT IN ( " . implode( ',', $hidden_members_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get SQL for Exclude Blocked forum related topics
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @return string|bool
	 */
	private function exclude_forum_topic_query() {
		global $wpdb;
		$sql              = false;
		$action_name      = current_filter();
		$hidden_forum_ids = BP_Moderation_Forums::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_forum_ids ) ) {
			$topic_alias = ( 'bp_forum_topic_search_where_sql' === $action_name ) ? 'p' : $wpdb->posts;
			$sql         = "( {$topic_alias}.post_parent NOT IN ( " . implode( ',', $hidden_forum_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Function to delete topic moderation data when actual topic is deleted
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int    $post_id post id being deleted.
	 * @param object $post    post data.
	 */
	public function delete_moderation_data( $post_id, $post ) {
		if ( ! empty( $post_id ) && ! empty( $post ) && bbp_get_topic_post_type() === $post->post_type ) {
			$moderation_obj = new BP_Moderation( $post_id, self::$moderation_type );
			if ( ! empty( $moderation_obj->id ) ) {
				$moderation_obj->delete( true );
			}
		}
	}
}
