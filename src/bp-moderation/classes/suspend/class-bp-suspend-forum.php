<?php
/**
 * BuddyBoss Suspend Forum Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Suspend
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend Forum.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Suspend_Forum extends BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $type = 'forum';

	/**
	 * BP_Suspend_Forum constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		if ( ! bp_is_active( 'forums' ) ) {
			return;
		}

		$this->item_type = self::$type;

		// Manage hidden list.
		add_action( "bp_suspend_hide_{$this->item_type}", array( $this, 'manage_hidden_forum' ), 10, 3 );
		add_action( "bp_suspend_unhide_{$this->item_type}", array( $this, 'manage_unhidden_forum' ), 10, 4 );

		$forum_post_type = bbp_get_forum_post_type();
		// Add moderation data when forum is added.
		add_action( "save_post_{$forum_post_type}", array( $this, 'sync_moderation_data_on_save' ), 10, 2 );

		// Delete moderation data when actual forum deleted.
		add_action( 'delete_post', array( $this, 'sync_moderation_data_on_delete' ), 10 );

		/**
		 * Suspend code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		$this->alias = $this->alias . 'f'; // f = Forum.

		add_filter( 'posts_join', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'posts_where', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_forums_search_join_sql', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_forums_search_where_sql', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bbp_get_forum', array( $this, 'restrict_single_item' ), 10, 2 );

		if ( bp_is_active( 'activity' ) ) {
			add_filter( 'bb_moderation_restrict_single_item_' . BP_Suspend_Activity::$type, array( $this, 'unbind_restrict_single_item' ), 10, 2 );
		}

		// Update the where condition for forum Subscriptions.
		add_filter( 'bb_subscriptions_get_where_conditions', array( $this, 'bb_subscriptions_forum_where_conditions' ), 10, 2 );
	}

	/**
	 * Get Blocked member's forum ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int    $member_id Member id.
	 * @param string $action    Action name to perform.
	 * @param int    $page      Number of page.
	 *
	 * @return array
	 */
	public static function get_member_forum_ids( $member_id, $action = '', $page = - 1 ) {
		$forum_ids = array();

		$args = array(
			'fields'                 => 'ids',
			'post_type'              => bbp_get_forum_post_type(),
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

		$forum_query = new WP_Query( $args );

		if ( $forum_query->have_posts() ) {
			$forum_ids = $forum_query->posts;
		}

		if ( 'hide' === $action && ! empty( $forum_ids ) ) {
			foreach ( $forum_ids as $k => $form_id ) {
				if ( BP_Core_Suspend::check_suspended_content( $form_id, self::$type, true ) ) {
					unset( $forum_ids[ $k ] );
				}
			}
		}

		if ( 'unhide' === $action && ! empty( $forum_ids ) ) {
			foreach ( $forum_ids as $k => $form_id ) {
				if ( self::is_content_reported_hidden( $form_id, self::$type ) ) {
					unset( $forum_ids[ $k ] );
				}
			}
		}

		return $forum_ids;
	}

	/**
	 * Prepare forum Join SQL query to filter blocked Forum
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $join_sql Forum Join sql.
	 * @param object $wp_query WP_Query object.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $wp_query = null ) {
		global $wpdb;

		$action_name = current_filter();

		if ( 'bp_forums_search_join_sql' === $action_name ) {
			$join_sql .= $this->exclude_joint_query( 'p.ID' );

			/**
			 * Filters the hidden forum Where SQL statement.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 * @param array $join_sql Join sql query
			 * @param array $class    current class object.
			 */
			$join_sql = apply_filters( 'bp_suspend_forum_get_join', $join_sql, $this );

		} else {
			$forum_slug = bbp_get_forum_post_type();
			$post_types = wp_parse_slug_list( $wp_query->get( 'post_type' ) );
			if ( false === $wp_query->get( 'moderation_query' ) || ! empty( $post_types ) && in_array( $forum_slug, $post_types, true ) ) {
				$join_sql .= $this->exclude_joint_query( "{$wpdb->posts}.ID" );

				/**
				 * Filters the hidden forum Where SQL statement.
				 *
				 * @since BuddyBoss 1.5.6
				 *
				 * @param array $join_sql Join sql query
				 * @param array $class    current class object.
				 */
				$join_sql = apply_filters( 'bp_suspend_forum_get_join', $join_sql, $this );
			}
		}

		return $join_sql;
	}

	/**
	 * Prepare forum Where SQL query to filter blocked Forum
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array       $where_conditions Forum Where sql.
	 * @param object|null $wp_query         WP_Query object.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions, $wp_query = null ) {
		$action_name = current_filter();

		if ( 'bp_forums_search_where_sql' !== $action_name ) {
			$forum_slug = bbp_get_forum_post_type();
			$post_types = wp_parse_slug_list( $wp_query->get( 'post_type' ) );
			if ( false === $wp_query->get( 'moderation_query' ) || empty( $post_types ) || ! in_array( $forum_slug, $post_types, true ) ) {
				return $where_conditions;
			}
		}

		$where = array();
		// Remove suspended members forum from widget.
		if ( function_exists( 'bb_did_filter' ) && bb_did_filter( 'bbp_after_forum_widget_settings_parse_args' ) ) {
			$where['suspend_where'] = $this->exclude_where_query();
		}
		/**
		 * Filters the hidden forum Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $where Query to hide suspended user's forum.
		 * @param array $class current class object.
		 */
		$where = apply_filters( 'bp_suspend_forum_get_where_conditions', $where, $this );

		if ( ! empty( array_filter( $where ) ) ) {
			if ( 'bp_forums_search_where_sql' === $action_name ) {
				$where_conditions['suspend_where'] = '( ' . implode( ' AND ', $where ) . ' )';
			} else {
				$where_conditions .= ' AND ( ' . implode( ' AND ', $where ) . ' )';
			}
		}

		return $where_conditions;
	}

	/**
	 * Restrict Single item
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param object $post   Current forum object.
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
	 * Hide related content of forum
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $forum_id      forum id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param array    $args          parent args.
	 */
	public function manage_hidden_forum( $forum_id, $hide_sitewide, $args = array() ) {
		global $bb_background_updater;

		if ( empty( $forum_id ) ) {
			return;
		}

		// if Group forums then return.
		$group_ids = bbp_get_forum_group_ids( $forum_id );
		if ( ! empty( $group_ids ) && ( ! isset( $args['type'] ) || empty( $args['type'] ) ) ) {
			return;
		}

		$force_bg_process = false;
		if ( isset( $args['force_bg_process'] ) ) {
			$force_bg_process = (bool) $args['force_bg_process'];
			unset( $args['force_bg_process'] );
		}

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $forum_id,
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

		$args['parent_id'] = ! empty( $args['parent_id'] ) ? $args['parent_id'] : $this->item_type . '_' . $forum_id;

		if ( $this->background_disabled || ! $force_bg_process ) {
			$this->hide_related_content( $forum_id, $hide_sitewide, $args );
		} else {
			$bb_background_updater->data(
				array(
					'type'              => $this->item_type,
					'group'             => $group_name,
					'data_id'           => $forum_id,
					'secondary_data_id' => $args['parent_id'],
					'callback'          => array( $this, 'hide_related_content' ),
					'args'              => array( $forum_id, $hide_sitewide, $args ),
				),
			);
			$bb_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Un-hide related content of forum
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $forum_id forum id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param int      $force_all     un-hide for all users.
	 * @param array    $args          parent args.
	 */
	public function manage_unhidden_forum( $forum_id, $hide_sitewide, $force_all, $args = array() ) {
		global $bb_background_updater;

		if ( empty( $forum_id ) ) {
			return;
		}

		$force_bg_process = false;
		if ( isset( $args['force_bg_process'] ) ) {
			$force_bg_process = (bool) $args['force_bg_process'];
			unset( $args['force_bg_process'] );
		}

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $forum_id,
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
			$forum_author_id = BP_Moderation_Forums::get_content_owner_id( $forum_id );
			if ( isset( $suspend_args['blocked_user'] ) && $forum_author_id === $suspend_args['blocked_user'] ) {
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

		$args['parent_id'] = ! empty( $args['parent_id'] ) ? $args['parent_id'] : $this->item_type . '_' . $forum_id;

		if ( $this->background_disabled || ! $force_bg_process ) {
			$this->unhide_related_content( $forum_id, $hide_sitewide, $force_all, $args );
		} else {
			$bb_background_updater->data(
				array(
					'type'              => $this->item_type,
					'group'             => $group_name,
					'data_id'           => $forum_id,
					'secondary_data_id' => $args['parent_id'],
					'callback'          => array( $this, 'unhide_related_content' ),
					'args'              => array( $forum_id, $hide_sitewide, $force_all, $args ),
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
	 * @param int   $forum_id forum id.
	 * @param array $args     parent args.
	 *
	 * @return array
	 */
	protected function get_related_contents( $forum_id, $args = array() ) {
		$related_contents = array();
		$action           = ! empty( $args['action'] ) ? $args['action'] : '';
		$blocked_user     = ! empty( $args['blocked_user'] ) ? $args['blocked_user'] : '';
		$page             = ! empty( $args['page'] ) ? $args['page'] : - 1;

		if ( bp_is_active( 'forums' ) ) {
			$related_contents[ BP_Suspend_Forum_Topic::$type ] = BP_Suspend_Forum_Topic::get_forum_topics_ids( $forum_id, $page );
		}

		if ( bp_is_active( 'activity' ) ) {
			$related_contents[ BP_Suspend_Activity::$type ] = BP_Suspend_Activity::get_bbpress_activity_ids( $forum_id, $page );
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

		$sub_items     = bp_moderation_get_sub_items( $post_id, BP_Moderation_Forums::$moderation_type );
		$item_sub_id   = isset( $sub_items['id'] ) ? $sub_items['id'] : $post_id;
		$item_sub_type = isset( $sub_items['type'] ) ? $sub_items['type'] : BP_Moderation_Forums::$moderation_type;

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
	 * Update the suspend table to delete a forum.
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

		if ( ! isset( $post->post_type ) || bbp_get_forum_post_type() !== $post->post_type ) {
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

		if ( empty( $restrict ) && ( did_action( 'bbp_delete_forum' ) || did_action( 'bbp_trash_forum' ) ) ) {
			$restrict = true;
		}

		return $restrict;
	}

	/**
	 * Prepare subscription forum Where SQL query to filter blocked Forum.
	 *
	 * @since BuddyBoss 2.2.6
	 *
	 * @param array $where_conditions Subscription Where sql.
	 * @param array $r                Array of subscription arguments.
	 *
	 * @return mixed Where SQL
	 */
	public function bb_subscriptions_forum_where_conditions( $where_conditions, $r ) {
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

		if ( ! empty( $r['type'] ) && ! in_array( 'forum', $r['type'], true ) ) {
			return $where_conditions;
		}

		// Get suspended where query for the forum subscription.
		$where = array();

		/**
		 * Filters the hidden forum Where SQL statement.
		 *
		 * @since BuddyBoss 2.2.6
		 *
		 * @param array $where            Query to hide suspended user's forum.
		 * @param array $this             current class object.
		 * @param array $where_conditions Subscription Where sql.
		 * @param array $r                Array of subscription arguments.
		 */
		$where = apply_filters( 'bb_subscriptions_suspend_forum_get_where_conditions', $where, $this, $where_conditions, $r );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['suspend_forum_where'] = "sc.item_id NOT IN ( SELECT item_id FROM {$bp->table_prefix}bp_suspend WHERE item_type = 'forum' AND ( " . implode( ' OR ', $where ) . ' ) )';
		}

		return $where_conditions;
	}
}
