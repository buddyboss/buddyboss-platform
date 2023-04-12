<?php
/**
 * BuddyBoss Suspend Forum Reply Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Suspend
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend Forum Reply.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Suspend_Forum_Reply extends BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $type = 'forum_reply';

	/**
	 * BP_Suspend_Forum_Reply constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		if ( ! bp_is_active( 'forums' ) ) {
			return;
		}

		$this->item_type = self::$type;

		// Manage hidden list.
		add_action( "bp_suspend_hide_{$this->item_type}", array( $this, 'manage_hidden_reply' ), 10, 3 );
		add_action( "bp_suspend_unhide_{$this->item_type}", array( $this, 'manage_unhidden_reply' ), 10, 4 );

		$reply_post_type = bbp_get_reply_post_type();
		// Add moderation data when reply is added.
		add_action( "save_post_{$reply_post_type}", array( $this, 'sync_moderation_data_on_save' ), 10, 2 );

		// Delete moderation data when actual reply deleted.
		add_action( 'delete_post', array( $this, 'sync_moderation_data_on_delete' ), 10 );

		/**
		 * Suspend code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		$this->alias = $this->alias . 'fr'; // fr = Forum Reply.

		add_filter( 'posts_join', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'posts_where', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_forum_reply_search_join_sql', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_forum_reply_search_where_sql', array( $this, 'update_where_sql' ), 10, 2 );

		// Blocked template.
		add_filter( 'bbp_locate_template_names', array( $this, 'locate_blocked_template' ) );

		if ( bp_is_active( 'activity' ) ) {
			add_filter( 'bb_moderation_restrict_single_item_' . BP_Suspend_Activity::$type, array( $this, 'unbind_restrict_single_item' ), 10, 2 );
		}
	}

	/**
	 * Get Blocked member's reply ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int    $member_id Member id.
	 * @param string $action    Action name to perform.
	 * @param int    $page      Number of page.
	 *
	 * @return array
	 */
	public static function get_member_reply_ids( $member_id, $action = '', $page = - 1 ) {
		$reply_ids = array();

		$args = array(
			'fields'                 => 'ids',
			'post_type'              => bbp_get_reply_post_type(),
			'post_status'            => 'publish',
			'author'                 => $member_id,
			'posts_per_page'         => - 1,
			// Need to get all topics id of hidden forums.
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'suppress_filters'       => true,
		);

		if ( $page > 0 ) {
			$args['posts_per_page'] = self::$item_per_page;
			$args['paged']          = $page;
		}

		$reply_query = new WP_Query( $args );

		if ( $reply_query->have_posts() ) {
			$reply_ids = $reply_query->posts;
		}

		if ( 'hide' === $action && ! empty( $reply_ids ) ) {
			foreach ( $reply_ids as $k => $reply_id ) {
				if ( BP_Core_Suspend::check_suspended_content( $reply_id, self::$type, true ) ) {
					unset( $reply_ids[ $k ] );
				}
			}
		}

		return $reply_ids;
	}

	/**
	 * Get forum topic/reply's child replies ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $parent_id topic/reply id.
	 * @param int $page      Number of page.
	 *
	 * @return array
	 */
	public static function get_topic_reply_replies( $parent_id, $page = - 1 ) {
		$reply_ids = array();

		$args = array(
			'fields'                 => 'ids',
			'post_type'              => bbp_get_reply_post_type(),
			'post_status'            => 'publish',
			'post_parent'            => $parent_id,
			'posts_per_page'         => - 1,
			// Need to get all topics id of hidden forums.
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'suppress_filters'       => true,
		);

		if ( $page > 0 ) {
			$args['posts_per_page'] = self::$item_per_page;
			$args['paged']          = $page;
		}

		$reply_query = new WP_Query( $args );

		if ( $reply_query->have_posts() ) {
			$reply_ids = $reply_query->posts;
		}

		return $reply_ids;
	}

	/**
	 * Prepare forum reply Join SQL query to filter blocked Forum Reply
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $join_sql Forum Reply Join sql.
	 * @param object $wp_query WP_Query object.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $wp_query = null ) {
		global $wpdb;

		$action_name = current_filter();

		if ( 'bp_forum_reply_search_join_sql' === $action_name ) {
			$join_sql .= $this->exclude_joint_query( 'p.ID' );

			/**
			 * Filters the hidden Forum Reply Where SQL statement.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 * @param array $join_sql Join sql query
			 * @param array $class    current class object.
			 */
			$join_sql = apply_filters( 'bp_suspend_forum_reply_get_join', $join_sql, $this );

		} else {
			$reply_slug = bbp_get_reply_post_type();
			$post_types = wp_parse_slug_list( $wp_query->get( 'post_type' ) );
			if ( false === $wp_query->get( 'moderation_query' ) || ! empty( $post_types ) && in_array( $reply_slug, $post_types, true ) ) {
				$join_sql .= $this->exclude_joint_query( "{$wpdb->posts}.ID" );

				/**
				 * Filters the hidden Forum Reply Where SQL statement.
				 *
				 * @since BuddyBoss 1.5.6
				 *
				 * @param array $join_sql Join sql query
				 * @param array $class    current class object.
				 */
				$join_sql = apply_filters( 'bp_suspend_forum_reply_get_join', $join_sql, $this );
			}
		}

		return $join_sql;
	}

	/**
	 * Prepare forum reply Where SQL query to filter blocked Forum Reply
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array       $where_conditions Forum Reply Where sql.
	 * @param object|null $wp_query         WP_Query object.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions, $wp_query = null ) {

		$action_name = current_filter();

		if ( 'bp_forum_reply_search_where_sql' !== $action_name ) {
			$reply_slug = bbp_get_reply_post_type();
			$post_types = wp_parse_slug_list( $wp_query->get( 'post_type' ) );
			if ( false === $wp_query->get( 'moderation_query' ) || empty( $post_types ) || ! in_array( $reply_slug, $post_types, true ) ) {
				return $where_conditions;
			}
		}

		$where                  = array();
		// Remove suspended members reply from widget.
		if ( function_exists( 'bb_did_filter' ) && bb_did_filter( 'bbp_after_replies_widget_settings_parse_args' ) ) {
			$where['suspend_where'] = $this->exclude_where_query();
		}

		/**
		 * Filters the hidden forum reply Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $where Query to hide suspended user's forum reply.
		 * @param array $class current class object.
		 */
		$where = apply_filters( 'bp_suspend_forum_reply_get_where_conditions', $where, $this );

		if ( ! empty( array_filter( $where ) ) ) {
			if ( 'bp_forum_reply_search_where_sql' === $action_name ) {
				$where_conditions['suspend_where'] = '( ' . implode( ' AND ', $where ) . ' )';
			} else {
				$where_conditions .= ' AND ( ' . implode( ' AND ', $where ) . ' )';
			}
		}

		return $where_conditions;
	}

	/**
	 * Hide related content of forum reply
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $reply_id      forum reply id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param array    $args          parent args.
	 */
	public function manage_hidden_reply( $reply_id, $hide_sitewide, $args = array() ) {
		global $bp_background_updater;

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $reply_id,
				'item_type' => self::$type,
			)
		);

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		$suspend_args = self::validate_keys( $suspend_args );

		BP_Core_Suspend::add_suspend( $suspend_args );

		if ( $this->background_disabled ) {
			$this->hide_related_content( $reply_id, $hide_sitewide, $args );
		} else {
			$bp_background_updater->data(
				array(
					array(
						'callback' => array( $this, 'hide_related_content' ),
						'args'     => array( $reply_id, $hide_sitewide, $args ),
					),
				)
			);
			$bp_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Un-hide related content of reply
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $reply_id      reply id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param int      $force_all     un-hide for all users.
	 * @param array    $args          parent args.
	 */
	public function manage_unhidden_reply( $reply_id, $hide_sitewide, $force_all, $args = array() ) {
		global $bp_background_updater;

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $reply_id,
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
			$reply_author_id = BP_Moderation_Forum_Replies::get_content_owner_id( $reply_id );
			if ( isset( $suspend_args['blocked_user'] ) && $reply_author_id === $suspend_args['blocked_user'] ) {
				unset( $suspend_args['blocked_user'] );
			}
		}

		$suspend_args = self::validate_keys( $suspend_args );

		BP_Core_Suspend::remove_suspend( $suspend_args );

		if ( $this->background_disabled ) {
			$this->unhide_related_content( $reply_id, $hide_sitewide, $force_all, $args );
		} else {
			$bp_background_updater->data(
				array(
					array(
						'callback' => array( $this, 'unhide_related_content' ),
						'args'     => array( $reply_id, $hide_sitewide, $force_all, $args ),
					),
				)
			);
			$bp_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Update blocked comment template
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $template_names Template name.
	 *
	 * @return string
	 */
	public function locate_blocked_template( $template_names ) {

		if ( 'loop-single-reply.php' !== $template_names ) {
			if ( ! is_array( $template_names ) || ! in_array( 'loop-single-reply.php', $template_names, true ) ) {
				return $template_names;
			}
		}

		$reply_id = bbp_get_reply_id();

		if ( BP_Core_Suspend::check_suspended_content( $reply_id, self::$type, true ) ) {
			return 'loop-blocked-single-reply.php';
		}

		$author_id = BP_Moderation_Forum_Replies::get_content_owner_id( $reply_id );
		if ( bp_moderation_is_user_suspended( $author_id ) ) {
			return 'loop-blocked-single-reply.php';
		}

		return $template_names;
	}

	/**
	 * Get Activity's comment ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int   $reply_id reply id.
	 * @param array $args     parent args.
	 *
	 * @return array
	 */
	protected function get_related_contents( $reply_id, $args = array() ) {
		$related_contents = array();
		$action           = ! empty( $args['action'] ) ? $args['action'] : '';
		$blocked_user     = ! empty( $args['blocked_user'] ) ? $args['blocked_user'] : '';
		$page             = ! empty( $args['page'] ) ? $args['page'] : - 1;

		// related activity comment only hide if parent activity hide or comment's/parent activity's author blocked or suspended.
		if ( ! empty( $args ) && ( isset( $args['blocked_user'] ) || isset( $args['user_suspended'] ) || isset( $args['hide_parent'] ) ) ) {
			$related_contents[ self::$type ] = self::get_topic_reply_replies( $reply_id, $page );
		}

		if ( bp_is_active( 'activity' ) && $page < 2 ) {
			$activity_id                                    = get_post_meta( $reply_id, '_bbp_activity_id', true );
			$related_contents[ BP_Suspend_Activity::$type ] = array( $activity_id );
		}

		if ( bp_is_active( 'document' ) && $page < 2 ) {
			$related_contents[ BP_Suspend_Document::$type ] = BP_Suspend_Document::get_document_ids_meta( $reply_id, 'get_post_meta', $action );
		}

		if ( bp_is_active( 'media' ) && $page < 2 ) {
			$related_contents[ BP_Suspend_Media::$type ] = BP_Suspend_Media::get_media_ids_meta( $reply_id, 'get_post_meta', $action );
		}

		if ( bp_is_active( 'video' ) && $page < 2 ) {
			$related_contents[ BP_Suspend_Video::$type ] = BP_Suspend_Video::get_video_ids_meta( $reply_id, 'get_post_meta', $action );
		}

		return $related_contents;
	}

	/**
	 * Update the suspend table to add new entries.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function sync_moderation_data_on_save( $post_id, $post ) {

		if ( empty( $post_id ) || empty( $post->ID ) ) {
			return;
		}

		$sub_items     = bp_moderation_get_sub_items( $post_id, BP_Moderation_Forum_Replies::$moderation_type );
		$item_sub_id   = isset( $sub_items['id'] ) ? $sub_items['id'] : $post_id;
		$item_sub_type = isset( $sub_items['type'] ) ? $sub_items['type'] : BP_Moderation_Forum_Replies::$moderation_type;

		$suspended_record = BP_Core_Suspend::get_recode( $item_sub_id, $item_sub_type );

		if ( empty( $suspended_record ) ) {
			$suspended_record = BP_Core_Suspend::get_recode( $post->post_author, BP_Moderation_Members::$moderation_type );
		}

		if ( empty( $suspended_record ) ) {
			return;
		}

		self::handle_new_suspend_entry( $suspended_record, $post_id, $post->post_author );
	}

	/**
	 * Update the suspend table to delete a reply.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $post_id Post ID.
	 */
	public function sync_moderation_data_on_delete( $post_id ) {

		if ( empty( $post_id ) ) {
			return;
		}

		$post = get_post( $post_id );

		if ( ! isset( $post->post_type ) || bbp_get_reply_post_type() !== $post->post_type ) {
			return;
		}

		BP_Core_Suspend::delete_suspend( $post_id, $this->item_type );
	}

	/**
	 * Function to un-restrict activity data while deleting the activity.
	 *
	 * @since BuddyBoss 1.7.5
	 *
	 * @param boolean $restrict restrict single item or not.
	 *
	 * @return false
	 */
	public function unbind_restrict_single_item( $restrict ) {

		if ( empty( $restrict ) && ( did_action( 'bbp_delete_reply' ) || did_action( 'bbp_trash_reply' ) ) ) {
			$restrict = true;
		}

		return $restrict;
	}
}
