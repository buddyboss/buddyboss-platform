<?php
/**
 * BuddyBoss Suspend Forum Topic Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Suspend
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend Forum Topic.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Suspend_Forum_Topic extends BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $type = 'forum_topic';

	/**
	 * BP_Suspend_Forum_Topic constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		if ( ! bp_is_active( 'forums' ) ) {
			return;
		}

		$this->item_type = self::$type;

		// Manage hidden list.
		add_action( "bp_suspend_hide_{$this->item_type}", array( $this, 'manage_hidden_topic' ), 10, 3 );
		add_action( "bp_suspend_unhide_{$this->item_type}", array( $this, 'manage_unhidden_topic' ), 10, 4 );

		$topic_post_type = bbp_get_topic_post_type();
		// Add moderation data when topic is added.
		add_action( "save_post_{$topic_post_type}", array( $this, 'sync_moderation_data_on_save' ), 10, 2 );

		// Delete moderation data when actual topic deleted.
		add_action( 'delete_post', array( $this, 'sync_moderation_data_on_delete' ), 10 );

		/**
		 * Suspend code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		$this->alias = $this->alias . 'ft'; // ft = Forum Topic.

		add_filter( 'posts_join', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'posts_where', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_forum_topic_search_join_sql', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_forum_topic_search_where_sql', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bbp_get_topic', array( $this, 'restrict_single_item' ), 10, 2 );

		if ( bp_is_active( 'activity' ) ) {
			add_filter( 'bb_moderation_restrict_single_item_' . BP_Suspend_Activity::$type, array( $this, 'unbind_restrict_single_item' ), 10, 2 );
		}

		// Update the where condition for forum Subscriptions.
		add_filter( 'bb_subscriptions_get_where_conditions', array( $this, 'bb_subscriptions_topic_where_conditions' ), 11, 2 );
	}

	/**
	 * Get Blocked member's topic ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int    $member_id Member id.
	 * @param string $action    Action name to perform.
	 * @param int    $page      Number of page.
	 *
	 * @return array
	 */
	public static function get_member_topic_ids( $member_id, $action = '', $page = - 1 ) {
		$topic_ids = array();

		$args = array(
			'fields'                 => 'ids',
			'post_type'              => bbp_get_topic_post_type(),
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

		$topic_query = new WP_Query( $args );

		if ( $topic_query->have_posts() ) {
			$topic_ids = $topic_query->posts;
		}

		if ( 'hide' === $action && ! empty( $topic_ids ) ) {
			foreach ( $topic_ids as $k => $topic_id ) {
				if ( BP_Core_Suspend::check_suspended_content( $topic_id, self::$type, true ) ) {
					unset( $topic_ids[ $k ] );
				}
			}
		}

		return $topic_ids;
	}

	/**
	 * Get forum topics ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $forum_id forums id.
	 * @param int $page     Number of page.
	 *
	 * @return array
	 */
	public static function get_forum_topics_ids( $forum_id, $page = - 1 ) {
		$topic_ids = array();

		$args = array(
			'fields'                 => 'ids',
			'post_type'              => bbp_get_topic_post_type(),
			'post_status'            => 'publish',
			'post_parent'            => $forum_id,
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

		$topic_query = new WP_Query( $args );

		if ( $topic_query->have_posts() ) {
			$topic_ids = $topic_query->posts;
		}

		return $topic_ids;
	}

	/**
	 * Prepare forum topic Join SQL query to filter blocked Forum Topic
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $join_sql Forum Topic Join sql.
	 * @param object $wp_query WP_Query object.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $wp_query = null ) {
		global $wpdb;

		$action_name = current_filter();

		if ( 'bp_forum_topic_search_join_sql' === $action_name ) {
			$join_sql .= $this->exclude_joint_query( 'p.ID' );

			/**
			 * Filters the hidden Forum Topic Where SQL statement.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 * @param array $join_sql Join sql query
			 * @param array $class    current class object.
			 */
			$join_sql = apply_filters( 'bp_suspend_forum_topic_get_join', $join_sql, $this );

		} else {
			$topic_slug = bbp_get_topic_post_type();
			$post_types = wp_parse_slug_list( $wp_query->get( 'post_type' ) );
			if ( false === $wp_query->get( 'moderation_query' ) || ! empty( $post_types ) && in_array( $topic_slug, $post_types, true ) ) {
				$join_sql .= $this->exclude_joint_query( "{$wpdb->posts}.ID" );

				/**
				 * Filters the hidden Forum Topic Where SQL statement.
				 *
				 * @since BuddyBoss 1.5.6
				 *
				 * @param array $join_sql Join sql query
				 * @param array $class    current class object.
				 */
				$join_sql = apply_filters( 'bp_suspend_forum_topic_get_join', $join_sql, $this );
			}
		}

		return $join_sql;
	}

	/**
	 * Prepare forum topic Where SQL query to filter blocked Forum Topic
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array       $where_conditions Forum Topic Where sql.
	 * @param object|null $wp_query         WP_Query object.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions, $wp_query = null ) {

		$action_name = current_filter();

		if ( 'bp_forum_topic_search_where_sql' !== $action_name ) {
			$topic_slug = bbp_get_topic_post_type();
			$post_types = wp_parse_slug_list( $wp_query->get( 'post_type' ) );
			if ( false === $wp_query->get( 'moderation_query' ) || empty( $post_types ) || ! in_array( $topic_slug, $post_types, true ) ) {
				return $where_conditions;
			}
		}

		$where = array();
		// Remove suspended members topic from widget.
		if ( function_exists( 'bb_did_filter' ) && bb_did_filter( 'bbp_after_topic_widget_settings_parse_args' ) ) {
			$where['suspend_where'] = $this->exclude_where_query();
		}
		/**
		 * Filters the hidden forum topic Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $where Query to hide suspended user's forum topic.
		 * @param array $class current class object.
		 */
		$where = apply_filters( 'bp_suspend_forum_topic_get_where_conditions', $where, $this );

		if ( ! empty( array_filter( $where ) ) ) {
			if ( 'bp_forum_topic_search_where_sql' === $action_name ) {
				$where_conditions['suspend_where'] = '( ' . implode( ' AND ', $where ) . ' )';
			} else {
				$where_conditions .= ' AND ( ' . implode( ' AND ', $where ) . ' )';
			}
		}

		return $where_conditions;
	}

	/**
	 * Restrict Single item.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param object $post   Current topic object.
	 * @param string $output Optional. OBJECT, ARRAY_A, or ARRAY_N. Default = OBJECT.
	 *
	 * @return object|array|null
	 */
	public function restrict_single_item( $post, $output ) {

		$username_visible = isset( $_GET['username_visible'] ) ? sanitize_text_field( wp_unslash( $_GET['username_visible'] ) ) : false;

		if ( ! empty( $username_visible ) ) {
			return $post;
		}

		return $post;
	}

	/**
	 * Hide related content of forum topic
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $topic_id      forum topic id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param array    $args          parent args.
	 */
	public function manage_hidden_topic( $topic_id, $hide_sitewide, $args = array() ) {
		global $bb_background_updater;

		if ( empty( $topic_id ) ) {
			return;
		}

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $topic_id,
				'item_type' => self::$type,
			)
		);

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		$suspend_args = self::validate_keys( $suspend_args );

		$group_name_args = array_merge(
			$suspend_args,
			array(
				'custom_action' => 'hide',
			)
		);
		$group_name      = $this->bb_moderation_get_action_type( $group_name_args );

		BP_Core_Suspend::add_suspend( $suspend_args );

		$args['parent_id'] = ! empty( $args['parent_id'] ) ? $args['parent_id'] : $this->item_type . '_' . $topic_id;

		if ( $this->background_disabled ) {
			$this->hide_related_content( $topic_id, $hide_sitewide, $args );
		} else {
			$bb_background_updater->data(
				array(
					'type'              => $this->item_type,
					'group'             => $group_name,
					'data_id'           => $topic_id,
					'secondary_data_id' => $args['parent_id'],
					'callback'          => array( $this, 'hide_related_content' ),
					'args'              => array( $topic_id, $hide_sitewide, $args ),
				),
			);
			$bb_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Un-hide related content of topic
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $topic_id      topic id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param int      $force_all     un-hide for all users.
	 * @param array    $args          parent args.
	 */
	public function manage_unhidden_topic( $topic_id, $hide_sitewide, $force_all, $args = array() ) {
		global $bb_background_updater;

		if ( empty( $topic_id ) ) {
			return;
		}

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $topic_id,
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
			$topic_author_id = BP_Moderation_Forum_Topics::get_content_owner_id( $topic_id );
			if ( isset( $suspend_args['blocked_user'] ) && $topic_author_id === $suspend_args['blocked_user'] ) {
				unset( $suspend_args['blocked_user'] );
			}
		}

		$suspend_args = self::validate_keys( $suspend_args );

		$group_name_args = array_merge(
			$suspend_args,
			array(
				'custom_action' => 'unhide',
			)
		);
		$group_name      = $this->bb_moderation_get_action_type( $group_name_args );

		BP_Core_Suspend::remove_suspend( $suspend_args );

		$args['parent_id'] = ! empty( $args['parent_id'] ) ? $args['parent_id'] : $this->item_type . '_' . $topic_id;

		if ( $this->background_disabled ) {
			$this->unhide_related_content( $topic_id, $hide_sitewide, $force_all, $args );
		} else {
			$bb_background_updater->data(
				array(
					'type'              => $this->item_type,
					'group'             => $group_name,
					'data_id'           => $topic_id,
					'secondary_data_id' => $args['parent_id'],
					'callback'          => array( $this, 'unhide_related_content' ),
					'args'              => array( $topic_id, $hide_sitewide, $force_all, $args ),
				),
			);
			$bb_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Get Activity's comment ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int   $topic_id topic id.
	 * @param array $args     parent args.
	 *
	 * @return array
	 */
	protected function get_related_contents( $topic_id, $args = array() ) {
		$related_contents = array();
		$action           = ! empty( $args['action'] ) ? $args['action'] : '';
		$blocked_user     = ! empty( $args['blocked_user'] ) ? $args['blocked_user'] : '';
		$page             = ! empty( $args['page'] ) ? $args['page'] : - 1;

		if ( bp_is_active( 'forums' ) ) {
			$related_contents[ BP_Suspend_Forum_Reply::$type ] = BP_Suspend_Forum_Reply::get_topic_reply_replies( $topic_id, $page );
		}

		if ( bp_is_active( 'activity' ) && $page < 2 ) {
			$activity_id                                    = get_post_meta( $topic_id, '_bbp_activity_id', true );
			$related_contents[ BP_Suspend_Activity::$type ] = array( $activity_id );
		}

		if ( bp_is_active( 'document' ) && $page < 2 ) {
			$related_contents[ BP_Suspend_Document::$type ] = BP_Suspend_Document::get_document_ids_meta( $topic_id, 'get_post_meta', $action );
		}

		if ( bp_is_active( 'media' ) && $page < 2 ) {
			$related_contents[ BP_Suspend_Media::$type ] = BP_Suspend_Media::get_media_ids_meta( $topic_id, 'get_post_meta', $action );
		}

		if ( bp_is_active( 'video' ) && $page < 2 ) {
			$related_contents[ BP_Suspend_Video::$type ] = BP_Suspend_Video::get_video_ids_meta( $topic_id, 'get_post_meta', $action );
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

		$sub_items     = bp_moderation_get_sub_items( $post_id, BP_Moderation_Forum_Topics::$moderation_type );
		$item_sub_id   = isset( $sub_items['id'] ) ? $sub_items['id'] : $post_id;
		$item_sub_type = isset( $sub_items['type'] ) ? $sub_items['type'] : BP_Moderation_Forum_Topics::$moderation_type;

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
	 * Update the suspend table to delete a topic.
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

		if ( ! isset( $post->post_type ) || bbp_get_topic_post_type() !== $post->post_type ) {
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

		if ( empty( $restrict ) && ( did_action( 'bbp_delete_topic' ) || did_action( 'bbp_trash_topic' ) ) ) {
			$restrict = true;
		}

		return $restrict;
	}

	/**
	 * Prepare subscription topic Where SQL query to filter blocked Forum.
	 *
	 * @since BuddyBoss 2.2.6
	 *
	 * @param array $where_conditions Subscription topic Where sql.
	 * @param array $r                Array of subscription arguments.
	 *
	 * @return mixed Where SQL
	 */
	public function bb_subscriptions_topic_where_conditions( $where_conditions, $r ) {
		global $bp;

		if ( isset( $r['bypass_moderation'] ) && true === (bool) $r['bypass_moderation'] ) {
			return $where_conditions;
		}

		if ( ! empty( $r['type'] ) ) {
			if ( ! is_array( $r['type'] ) ) {
				$r['type'] = preg_split( '/[\s,]+/', $r['type'] );
			}
			$r['type'] = array_map( 'sanitize_title', $r['type'] );
		}

		if ( ! empty( $r['type'] ) && ! in_array( 'topic', $r['type'], true ) ) {
			return $where_conditions;
		}

		// Get suspended where query for the forum subscription.
		$where = array();

		/**
		 * Filters the hidden topic Where SQL statement.
		 *
		 * @since BuddyBoss 2.2.6
		 *
		 * @param array $where            Query to hide suspended user's topic.
		 * @param array $this             current class object.
		 * @param array $where_conditions Subscription topic Where sql.
		 * @param array $r                Array of subscription arguments.
		 */
		$where = apply_filters( 'bb_subscriptions_suspend_topic_get_where_conditions', $where, $this, $where_conditions, $r );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['suspend_topic_where'] = "sc.item_id NOT IN ( SELECT item_id FROM {$bp->table_prefix}bp_suspend WHERE item_type = 'forum_topic' AND ( " . implode( ' OR ', $where ) . ' ) )';
		}

		return $where_conditions;
	}
}
