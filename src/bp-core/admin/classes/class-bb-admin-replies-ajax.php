<?php
/**
 * BuddyBoss Replies Admin AJAX Handler
 *
 * Handles AJAX requests for Reply CRUD operations, discussion autocomplete,
 * and reply autocomplete in the Settings 2.0 admin interface.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Replies_Ajax
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Admin_Replies_Ajax {

	/**
	 * Nonce action (shared with BB_Admin_Settings_Ajax).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'bb_admin_settings';

	/**
	 * Maximum items per page for paginated endpoints.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var int
	 */
	const PER_PAGE_CAP = 100;

	/**
	 * Maximum replies for bulk operations.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var int
	 */
	const BULK_CAP = 100;

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		$this->bb_register_ajax_handlers();

		// Invalidate admin forum counts cache when reply data changes.
		$clear_counts = array( $this, 'bb_clear_forum_counts_cache' );
		add_action( 'bbp_delete_reply', $clear_counts );
		add_action( 'bbp_deleted_reply', $clear_counts );
		add_action( 'bbp_trash_reply', $clear_counts );
		add_action( 'bbp_spam_reply', $clear_counts );
		add_action( 'bbp_unspam_reply', $clear_counts );
		add_action( 'save_post_' . bbp_get_reply_post_type(), $clear_counts );
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function bb_register_ajax_handlers() {
		add_action( 'wp_ajax_bb_admin_get_replies', array( $this, 'get_replies' ) );
		add_action( 'wp_ajax_bb_admin_get_reply', array( $this, 'get_reply' ) );
		add_action( 'wp_ajax_bb_admin_create_reply', array( $this, 'create_reply' ) );
		add_action( 'wp_ajax_bb_admin_save_reply', array( $this, 'save_reply' ) );
		add_action( 'wp_ajax_bb_admin_delete_reply', array( $this, 'delete_reply' ) );
		add_action( 'wp_ajax_bb_admin_reply_bulk_action', array( $this, 'reply_bulk_action' ) );
		add_action( 'wp_ajax_bb_admin_discussion_autocomplete', array( $this, 'discussion_autocomplete' ) );
		add_action( 'wp_ajax_bb_admin_reply_autocomplete', array( $this, 'reply_autocomplete' ) );
	}

	/**
	 * Clear the admin replies forum counts cache.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_clear_forum_counts_cache() {
		wp_cache_delete( 'bb_admin_replies_forum_counts', 'bbpress' );

		// Also clear forums and discussions caches since reply changes
		// affect forum/topic aggregate counts.
		wp_cache_delete( 'bb_admin_forums_status_counts', 'bbpress' );
		wp_cache_delete( 'bb_admin_discussions_forum_counts', 'bbpress' );

		// Bump the forums mine-count version so every user's per-user mine-count
		// cache is invalidated transparently. Keys are
		// `bb_admin_forums_mine_count_{user}_v{version}` — bumping the version
		// makes all old keys unreachable. Mirrors BB_Admin_Forums_Ajax::bb_clear_status_counts_cache().
		bp_update_option( 'bb_admin_forums_mine_count_version', (int) bp_get_option( 'bb_admin_forums_mine_count_version', 0 ) + 1 );
	}

	/**
	 * Get replies listing with pagination, filters, and sorting.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function get_replies() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$page         = isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : 1;
		$per_page     = isset( $_POST['per_page'] ) ? absint( wp_unslash( $_POST['per_page'] ) ) : 20;
		$search       = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$forum_id     = isset( $_POST['forum_id'] ) ? absint( wp_unslash( $_POST['forum_id'] ) ) : 0;
		$sort         = isset( $_POST['sort'] ) ? sanitize_key( wp_unslash( $_POST['sort'] ) ) : 'newest';
		$include_meta = isset( $_POST['include_meta'] ) ? absint( wp_unslash( $_POST['include_meta'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Validate sort.
		$allowed_sorts = array( 'newest', 'oldest' );
		if ( ! in_array( $sort, $allowed_sorts, true ) ) {
			$sort = 'newest';
		}

		// Clamp per_page.
		$per_page = max( 1, min( self::PER_PAGE_CAP, $per_page ) );
		$page     = max( 1, $page );

		// Build WP_Query args.
		$query_args = array(
			'post_type'      => bbp_get_reply_post_type(),
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_status'    => array( 'publish', 'private', 'hidden', 'spam', 'future', 'pending', 'draft' ),
		);

		// Forum filter.
		if ( ! empty( $forum_id ) ) {
			$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_bbp_forum_id',
					'value'   => $forum_id,
					'type'    => 'NUMERIC',
					'compare' => '=',
				),
			);
		}

		// Search.
		if ( ! empty( $search ) ) {
			$query_args['s'] = $search;
		}

		// Sort.
		switch ( $sort ) {
			case 'oldest':
				$query_args['orderby'] = 'date';
				$query_args['order']   = 'ASC';
				break;
			case 'newest':
			default:
				$query_args['orderby'] = 'date';
				$query_args['order']   = 'DESC';
				break;
		}

		$query = new WP_Query( $query_args );
		$posts = $query->posts;
		$total = (int) $query->found_posts;

		// Prime user cache for authors.
		if ( ! empty( $posts ) ) {
			$author_ids = array_unique( wp_list_pluck( $posts, 'post_author' ) );
			if ( ! empty( $author_ids ) ) {
				cache_users( $author_ids );
			}
		}

		// Prime parent post caches (forum + topic) to prevent N+1 queries in the loop.
		// WP_Query already primes post meta cache, so get_post_meta() calls below are cache-served.
		if ( ! empty( $posts ) ) {
			$parent_ids = array();
			foreach ( $posts as $reply ) {
				$reply_forum_id = (int) get_post_meta( $reply->ID, '_bbp_forum_id', true );
				$reply_topic_id = (int) get_post_meta( $reply->ID, '_bbp_topic_id', true );
				if ( $reply_forum_id ) {
					$parent_ids[] = $reply_forum_id;
				}
				if ( $reply_topic_id ) {
					$parent_ids[] = $reply_topic_id;
				}
			}

			$parent_ids = array_unique( array_filter( $parent_ids ) );
			if ( ! empty( $parent_ids ) && function_exists( '_prime_post_caches' ) ) {
				_prime_post_caches( $parent_ids, false, false );
			}
		}

		// Get columns via the same filter as the legacy replies admin.
		$all_columns = apply_filters(
			'bbp_admin_replies_column_headers',
			array(
				'cb'                => '<input type="checkbox" />',
				'title'             => __( 'Reply', 'buddyboss' ),
				'bbp_reply_forum'   => __( 'Forum', 'buddyboss' ),
				'bbp_reply_topic'   => __( 'Discussion', 'buddyboss' ),
				'bbp_reply_author'  => __( 'Author', 'buddyboss' ),
				'bbp_reply_created' => __( 'Created', 'buddyboss' ),
			)
		);

		// Identify custom columns (added by third-party plugins).
		$core_columns   = array( 'cb', 'title', 'bbp_reply_forum', 'bbp_reply_topic', 'bbp_reply_author', 'bbp_reply_created' );
		$custom_columns = array();
		foreach ( $all_columns as $col_key => $col_label ) {
			if ( ! in_array( $col_key, $core_columns, true ) ) {
				$custom_columns[ $col_key ] = $col_label;
			}
		}

		// Buffer output.
		ob_start();

		$status_labels_map = array(
			'future'  => __( 'Scheduled', 'buddyboss' ),
			'pending' => __( 'Pending Review', 'buddyboss' ),
			'draft'   => __( 'Draft', 'buddyboss' ),
			'spam'    => __( 'Spam', 'buddyboss' ),
		);

		$items = array();
		foreach ( $posts as $reply ) {
			$reply_id  = $reply->ID;
			$author_id = (int) $reply->post_author;
			$user      = get_userdata( $author_id );

			$reply_forum_id = (int) get_post_meta( $reply_id, '_bbp_forum_id', true );
			$reply_topic_id = (int) get_post_meta( $reply_id, '_bbp_topic_id', true );

			// Generate content excerpt (max 100 chars).
			$content_raw     = wp_strip_all_tags( $reply->post_content );
			$content_excerpt = mb_strlen( $content_raw ) > 100
				? mb_substr( $content_raw, 0, 100 ) . '...'
				: $content_raw;

			$reply_status = get_post_status( $reply_id );
			$is_spam      = bbp_get_spam_status_id() === $reply_status;

			// Build status label for non-published items.
			$status_label = '';
			if ( isset( $status_labels_map[ $reply_status ] ) ) {
				$status_label = $status_labels_map[ $reply_status ];
			}

			$item = array(
				'id'            => $reply_id,
				'content'       => $content_excerpt,
				'status_label'  => $status_label,
				'forum_id'      => $reply_forum_id,
				'forum_name'    => $reply_forum_id ? get_the_title( $reply_forum_id ) : '',
				'topic_id'      => $reply_topic_id,
				'topic_title'   => $reply_topic_id ? get_the_title( $reply_topic_id ) : '',
				'author_id'     => $author_id,
				'author_name'   => $user ? $user->display_name : '',
				'author_avatar' => get_avatar_url( $author_id, array( 'size' => 32 ) ),
				'permalink'     => bbp_get_reply_url( $reply_id ),
				'post_status'   => $reply_status,
				'is_spam'       => $is_spam,
				'reply_to'      => (int) get_post_meta( $reply_id, '_bbp_reply_to', true ),
				'created_date'  => get_the_date( 'j M', $reply_id ),
				'created_time'  => get_the_time( 'H:i:s', $reply_id ),
			);

			// Render custom columns via legacy filter.
			if ( ! empty( $custom_columns ) ) {
				$item['custom_columns'] = array();
				foreach ( $custom_columns as $col_key => $col_label ) {
					ob_start();
					/**
					 * Fires for custom column data rendering in the replies admin list.
					 *
					 * @since bbPress (r2577)
					 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX.
					 *
					 * @param string $col_key  Column key.
					 * @param int    $reply_id Reply ID.
					 */
					do_action( 'bbp_admin_replies_column_data', $col_key, $reply_id );
					$item['custom_columns'][ $col_key ] = wp_kses_post( ob_get_clean() );
				}
			}

			$items[] = $item;
		}

		ob_end_clean();

		$response = array(
			'replies'     => $items,
			'total'       => $total,
			'total_pages' => ceil( $total / $per_page ),
		);

		// Include meta on first request.
		if ( $include_meta ) {
			$forum_counts = $this->bb_get_forum_counts_for_replies();

			$bulk_actions = array(
				'bulk_edit'   => __( 'Edit', 'buddyboss' ),
				'bulk_delete' => __( 'Delete', 'buddyboss' ),
			);

			$columns = array();
			foreach ( $all_columns as $key => $label ) {
				if ( 'cb' !== $key ) {
					$columns[ $key ] = $label;
				}
			}

			// Compute the true "all" count from forum counts (unfiltered total).
			$all_count = 0;
			foreach ( $forum_counts as $fc ) {
				$all_count += $fc['count'];
			}

			$response['views'] = array(
				'all'    => $all_count,
				'forums' => $forum_counts,
			);

			$response['bulk_actions'] = $bulk_actions;
			$response['columns']      = $columns;

			// Provide registered field definitions for the create modal.
			$response['create_fields'] = bb_admin_meta_field_registry()->get_fields_data(
				'replies',
				(object) array(
					'ID'           => 0,
					'post_content' => '',
					'post_status'  => 'publish',
					'post_parent'  => 0,
					'post_author'  => get_current_user_id(),
					'post_type'    => bbp_get_reply_post_type(),
				)
			);
		}

		/**
		 * Filters the full response data for the admin replies list AJAX endpoint.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $response Response data array.
		 */
		$response = apply_filters( 'bb_admin_get_replies_response', $response );

		wp_send_json_success( $response );
	}

	/**
	 * Get forum counts (number of replies per forum) for the reply view filter.
	 *
	 * Uses cached results with the same cache group as discussions.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array Array of forum objects with id, name, count.
	 */
	private function bb_get_forum_counts_for_replies() {
		$cached = wp_cache_get( 'bb_admin_replies_forum_counts', 'bbpress' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$reply_type  = bbp_get_reply_post_type();
		$spam_status = bbp_get_spam_status_id();

		// Get forum counts from reply meta.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.meta_value AS forum_id, COUNT(*) AS cnt
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key = '_bbp_forum_id'
				AND p.post_type = %s
				AND p.post_status IN ('publish','private','hidden',%s)
				GROUP BY pm.meta_value
				ORDER BY cnt DESC",
				$reply_type,
				$spam_status
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		$forums = array();
		if ( ! empty( $results ) ) {
			$forum_ids = wp_list_pluck( $results, 'forum_id' );

			// Prime post cache.
			if ( ! empty( $forum_ids ) && function_exists( '_prime_post_caches' ) ) {
				_prime_post_caches( $forum_ids, false, false );
			}

			$count_map = array();
			foreach ( $results as $row ) {
				$count_map[ $row->forum_id ] = (int) $row->cnt;
			}

			foreach ( $forum_ids as $fid ) {
				$forums[] = array(
					'id'    => (int) $fid,
					'name'  => get_the_title( $fid ),
					'count' => isset( $count_map[ $fid ] ) ? $count_map[ $fid ] : 0,
				);
			}
		}

		wp_cache_set( 'bb_admin_replies_forum_counts', $forums, 'bbpress', HOUR_IN_SECONDS );

		return $forums;
	}

	/**
	 * Get a single reply with full data for editing.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function get_reply() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$reply_id = isset( $_POST['reply_id'] ) ? absint( wp_unslash( $_POST['reply_id'] ) ) : 0;

		if ( empty( $reply_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Reply ID is required.', 'buddyboss' ) ) );
		}

		$reply = get_post( $reply_id );

		if ( ! $reply || bbp_get_reply_post_type() !== $reply->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Reply not found.', 'buddyboss' ) ) );
		}

		$forum_id = (int) get_post_meta( $reply_id, '_bbp_forum_id', true );
		$topic_id = (int) get_post_meta( $reply_id, '_bbp_topic_id', true );
		$reply_to = (int) get_post_meta( $reply_id, '_bbp_reply_to', true );

		$data = array(
			'id'                => $reply_id,
			'content'           => $reply->post_content,
			'forum_id'          => $forum_id,
			'forum_name'        => $forum_id ? get_the_title( $forum_id ) : '',
			'topic_id'          => $topic_id,
			'topic_title'       => $topic_id ? get_the_title( $topic_id ) : '',
			'reply_to'          => $reply_to,
			'post_status'       => get_post_status( $reply_id ),
			'permalink'         => bbp_get_reply_url( $reply_id ),
			'registered_fields' => bb_admin_meta_field_registry()->get_fields_data( 'replies', $reply ),
		);

		/**
		 * Filters the response data for the admin single reply endpoint.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array   $data  Response data array.
		 * @param WP_Post $reply The reply post object.
		 */
		wp_send_json_success( apply_filters( 'bb_admin_get_reply_response', $data, $reply ) );
	}

	/**
	 * Create a new reply.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function create_reply() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$content       = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$forum_id      = isset( $_POST['forum_id'] ) ? absint( wp_unslash( $_POST['forum_id'] ) ) : 0;
		$topic_id      = isset( $_POST['topic_id'] ) ? absint( wp_unslash( $_POST['topic_id'] ) ) : 0;
		$reply_to      = isset( $_POST['reply_to'] ) ? absint( wp_unslash( $_POST['reply_to'] ) ) : 0;
		$visibility    = isset( $_POST['visibility'] ) ? sanitize_key( wp_unslash( $_POST['visibility'] ) ) : 'publish';
		$post_password = isset( $_POST['post_password'] ) ? sanitize_text_field( wp_unslash( $_POST['post_password'] ) ) : '';
		$reply_status  = isset( $_POST['reply_status'] ) ? sanitize_key( wp_unslash( $_POST['reply_status'] ) ) : 'publish';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $content ) ) {
			wp_send_json_error( array( 'message' => __( 'Reply content is required.', 'buddyboss' ) ) );
		}

		if ( empty( $topic_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Discussion is required.', 'buddyboss' ) ) );
		}

		// Validate that topic_id references an actual topic post type.
		$topic_post = get_post( $topic_id );
		if ( ! $topic_post || bbp_get_topic_post_type() !== $topic_post->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Invalid discussion.', 'buddyboss' ) ) );
		}

		// Validate visibility.
		$allowed_visibilities = array( 'publish', 'private', 'hidden', 'password' );
		if ( ! in_array( $visibility, $allowed_visibilities, true ) ) {
			$visibility = 'publish';
		}

		// Auto-detect forum_id from topic if not provided.
		if ( empty( $forum_id ) && ! empty( $topic_id ) ) {
			$forum_id = (int) get_post_meta( $topic_id, '_bbp_forum_id', true );
		}

		// Validate reply-to threading reference.
		if ( ! empty( $reply_to ) ) {
			$reply_to = bbp_validate_reply_to( $reply_to );
		}

		// Handle scheduling (publish_mode=schedule with date + time).
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$publish_mode  = isset( $_POST['publish_mode'] ) ? sanitize_key( wp_unslash( $_POST['publish_mode'] ) ) : 'immediately';
		$schedule_date = isset( $_POST['schedule_date'] ) ? sanitize_text_field( wp_unslash( $_POST['schedule_date'] ) ) : '';
		$schedule_time = isset( $_POST['schedule_time'] ) ? sanitize_text_field( wp_unslash( $_POST['schedule_time'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Validate date format (YYYY-MM-DD) and time format (HH:MM).
		if ( ! empty( $schedule_date ) && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $schedule_date ) ) {
			$schedule_date = '';
		}
		if ( ! empty( $schedule_time ) && ! preg_match( '/^\d{2}:\d{2}$/', $schedule_time ) ) {
			$schedule_time = '';
		}

		// Fall back to public if password is empty (matches WP core behavior).
		if ( 'password' === $visibility && empty( $post_password ) ) {
			$visibility = 'publish';
		}

		// Resolve post_status from reply_status and visibility.
		// Draft/Pending take priority — visibility only applies to Published replies.
		if ( in_array( $reply_status, array( 'pending', 'draft' ), true ) ) {
			$resolved_status   = $reply_status;
			$resolved_password = '';
		} elseif ( 'password' === $visibility ) {
			$resolved_status   = 'publish';
			$resolved_password = $post_password;
		} else {
			$resolved_status   = $visibility;
			$resolved_password = '';
		}

		$reply_data = array(
			'post_content'  => $content,
			'post_status'   => $resolved_status,
			'post_parent'   => $topic_id,
			'post_password' => $resolved_password,
		);

		// If scheduling, set post_date and change status to 'future'.
		if ( 'schedule' === $publish_mode && ! empty( $schedule_date ) ) {
			$time_part                      = ! empty( $schedule_time ) ? $schedule_time . ':00' : '00:00:00';
			$scheduled_datetime             = $schedule_date . ' ' . $time_part;
			$reply_data['post_date']        = $scheduled_datetime;
			$reply_data['post_date_gmt']    = get_gmt_from_date( $scheduled_datetime );
			$reply_data['post_status']      = 'future';
		}

		$reply_meta = array(
			'forum_id' => $forum_id,
			'topic_id' => $topic_id,
		);

		// Run "before" phase fields so legacy meta-bridge save_value callbacks
		// populate $_POST with third-party metabox values before bbp_insert_reply
		// fires save_post_reply (where third-party plugins read $_POST and persist
		// their post meta). Pass an empty stub object — native fields' save_value
		// callbacks would fatal on null in PHP 8+; mutations on the stub are
		// harmless because $reply_data is built directly from $_POST below.
		bb_admin_meta_field_registry()->save_fields_data( 'replies', new \stdClass(), 'before' );

		$reply_id = bbp_insert_reply( $reply_data, $reply_meta );

		if ( ! $reply_id || is_wp_error( $reply_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to create reply.', 'buddyboss' ) ) );
		}

		// Handle reply-to threading.
		if ( ! empty( $reply_to ) ) {
			update_post_meta( $reply_id, '_bbp_reply_to', $reply_to );
		}

		/**
		 * Fires after a new reply is created in Settings 2.0 admin.
		 *
		 * This hook triggers activity stream entries, notifications, and
		 * subscriber emails (same as frontend reply creation).
		 *
		 * @since bbPress (r2574)
		 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX.
		 *
		 * @param int   $reply_id       Reply ID.
		 * @param int   $topic_id       Topic ID.
		 * @param int   $forum_id       Forum ID.
		 * @param array $anonymous_data Anonymous user data (empty for admin).
		 * @param int   $reply_author   Reply author user ID.
		 * @param bool  $is_edit        Whether this is an edit (false for new).
		 * @param int   $reply_to       Reply-to ID for threaded replies.
		 */
		do_action( 'bbp_new_reply', $reply_id, $topic_id, $forum_id, array(), bbp_get_current_user_id(), false, $reply_to );

		/**
		 * Fires after a new reply post extras in Settings 2.0 admin.
		 *
		 * Mirrors the legacy bbp_new_reply_post_extras hook for third-party
		 * plugin compatibility.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $reply_id Reply ID.
		 */
		do_action( 'bbp_new_reply_post_extras', $reply_id );

		/**
		 * Fires after reply attributes are set during creation in Settings 2.0 admin.
		 *
		 * In legacy bbPress, this hook fired on both create and edit via save_post.
		 * Ensures third-party plugins that set custom reply attributes on creation
		 * continue to work.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $reply_id Reply ID.
		 * @param int $topic_id Topic ID.
		 * @param int $forum_id Forum ID.
		 * @param int $reply_to Reply-to ID for threading.
		 */
		do_action( 'bbp_reply_attributes_metabox_save', $reply_id, $topic_id, $forum_id, $reply_to );

		/**
		 * Fires after reply author is set during creation in Settings 2.0 admin.
		 *
		 * In legacy bbPress, this hook fired on both create and edit via save_post.
		 * Ensures third-party plugins that set custom author data on creation
		 * continue to work.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int   $reply_id       Reply ID.
		 * @param array $anonymous_data Empty array (admin users are not anonymous).
		 */
		do_action( 'bbp_author_metabox_save', $reply_id, array() );

		// Save "after" phase extension fields (Pro/third-party) via meta field registry.
		$created_reply = get_post( $reply_id );
		if ( $created_reply ) {
			bb_admin_meta_field_registry()->save_fields_data( 'replies', $created_reply, 'after' );
		}

		$this->bb_clear_forum_counts_cache();

		wp_send_json_success(
			array(
				'reply_id' => $reply_id,
				'message'  => __( 'Reply created successfully.', 'buddyboss' ),
			)
		);
	}

	/**
	 * Save (update) an existing reply.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function save_reply() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$reply_id      = isset( $_POST['reply_id'] ) ? absint( wp_unslash( $_POST['reply_id'] ) ) : 0;
		$content       = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$forum_id      = isset( $_POST['forum_id'] ) ? absint( wp_unslash( $_POST['forum_id'] ) ) : 0;
		$topic_id      = isset( $_POST['topic_id'] ) ? absint( wp_unslash( $_POST['topic_id'] ) ) : 0;
		$reply_to      = isset( $_POST['reply_to'] ) ? absint( wp_unslash( $_POST['reply_to'] ) ) : 0;
		$visibility    = isset( $_POST['visibility'] ) ? sanitize_key( wp_unslash( $_POST['visibility'] ) ) : '';
		$post_password = isset( $_POST['post_password'] ) ? sanitize_text_field( wp_unslash( $_POST['post_password'] ) ) : '';
		$reply_status  = isset( $_POST['reply_status'] ) ? sanitize_key( wp_unslash( $_POST['reply_status'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $reply_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Reply ID is required.', 'buddyboss' ) ) );
		}

		$reply = get_post( $reply_id );
		if ( ! $reply || bbp_get_reply_post_type() !== $reply->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Reply not found.', 'buddyboss' ) ) );
		}

		// Validate visibility.
		$allowed_visibilities = array( 'publish', 'private', 'hidden', 'password' );
		if ( ! empty( $visibility ) && ! in_array( $visibility, $allowed_visibilities, true ) ) {
			$visibility = 'publish';
		}

		// Handle scheduling (publish_mode=schedule with date + time).
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$publish_mode  = isset( $_POST['publish_mode'] ) ? sanitize_key( wp_unslash( $_POST['publish_mode'] ) ) : '';
		$schedule_date = isset( $_POST['schedule_date'] ) ? sanitize_text_field( wp_unslash( $_POST['schedule_date'] ) ) : '';
		$schedule_time = isset( $_POST['schedule_time'] ) ? sanitize_text_field( wp_unslash( $_POST['schedule_time'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Validate date format (YYYY-MM-DD) and time format (HH:MM).
		if ( ! empty( $schedule_date ) && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $schedule_date ) ) {
			$schedule_date = '';
		}
		if ( ! empty( $schedule_time ) && ! preg_match( '/^\d{2}:\d{2}$/', $schedule_time ) ) {
			$schedule_time = '';
		}

		// Capture old parent IDs before update for count recalculation.
		$old_topic_id = (int) bbp_get_reply_topic_id( $reply_id );
		$old_forum_id = (int) bbp_get_reply_forum_id( $reply_id );

		// Update post data.
		$update_args = array(
			'ID' => $reply_id,
		);

		// Apply scheduling to update args.
		if ( 'schedule' === $publish_mode && ! empty( $schedule_date ) ) {
			$time_part                      = ! empty( $schedule_time ) ? $schedule_time . ':00' : '00:00:00';
			$scheduled_datetime             = $schedule_date . ' ' . $time_part;
			$update_args['post_date']       = $scheduled_datetime;
			$update_args['post_date_gmt']   = get_gmt_from_date( $scheduled_datetime );
			$update_args['post_status']     = 'future';
			$update_args['edit_date']       = true;
		} elseif ( 'immediately' === $publish_mode && 'future' === get_post_status( $reply_id ) ) {
			// Switching from scheduled back to immediately — publish now.
			$update_args['post_date']       = current_time( 'mysql' );
			$update_args['post_date_gmt']   = current_time( 'mysql', true );
			$update_args['edit_date']       = true;

			// Draft/Pending from reply_status take priority over visibility.
			if ( ! empty( $reply_status ) && in_array( $reply_status, array( 'pending', 'draft' ), true ) ) {
				$update_args['post_status']   = $reply_status;
				$update_args['post_password'] = '';
			} elseif ( 'password' === $visibility ) {
				$update_args['post_status']   = 'publish';
				$update_args['post_password'] = $post_password;
			} else {
				$update_args['post_status'] = ! empty( $visibility ) ? $visibility : 'publish';
			}
		}

		// Allow empty content to clear it (matches save_discussion() pattern).
		if ( isset( $_POST['content'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
			$update_args['post_content'] = $content;
		}

		// Only set post_status if scheduling hasn't already set it to 'future'.
		if ( ! isset( $update_args['post_status'] ) ) {

			// Draft/Pending from reply_status take priority over visibility.
			if ( ! empty( $reply_status ) && in_array( $reply_status, array( 'pending', 'draft' ), true ) ) {
				$update_args['post_status']   = $reply_status;
				$update_args['post_password'] = '';
			} elseif ( ! empty( $visibility ) ) {
				if ( 'password' === $visibility ) {
					$update_args['post_status']   = 'publish';
					$update_args['post_password'] = $post_password;
				} else {
					$update_args['post_status']   = $visibility;
					$update_args['post_password'] = '';
				}
			}
		}

		// Validate topic_id references an actual topic if provided.
		if ( ! empty( $topic_id ) ) {
			$topic_post = get_post( $topic_id );
			if ( ! $topic_post || bbp_get_topic_post_type() !== $topic_post->post_type ) {
				wp_send_json_error( array( 'message' => __( 'Invalid discussion.', 'buddyboss' ) ) );
			}
			$update_args['post_parent'] = $topic_id;
		}

		// Validate forum_id references an actual forum if provided.
		if ( ! empty( $forum_id ) ) {
			$forum_post = get_post( $forum_id );
			if ( ! $forum_post || bbp_get_forum_post_type() !== $forum_post->post_type ) {
				wp_send_json_error( array( 'message' => __( 'Invalid forum.', 'buddyboss' ) ) );
			}
		}

		// Run "before" phase fields so legacy meta-bridge save_value callbacks
		// populate $_POST with third-party metabox values before wp_update_post
		// fires save_post_reply (where third-party plugins read $_POST and persist
		// their post meta).
		bb_admin_meta_field_registry()->save_fields_data( 'replies', $reply, 'before' );

		$result = wp_update_post( $update_args, true );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to update reply. Please try again.', 'buddyboss' ) ) );
		}

		// Update meta.
		if ( ! empty( $forum_id ) ) {
			update_post_meta( $reply_id, '_bbp_forum_id', $forum_id );
		}

		if ( ! empty( $topic_id ) ) {
			update_post_meta( $reply_id, '_bbp_topic_id', $topic_id );
		}

		// Update reply-to threading with validation (only when explicitly sent).
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		if ( isset( $_POST['reply_to'] ) ) {
			if ( $reply_to > 0 ) {
				$reply_to = bbp_validate_reply_to( $reply_to );
				update_post_meta( $reply_id, '_bbp_reply_to', $reply_to );
			} else {
				delete_post_meta( $reply_id, '_bbp_reply_to' );
			}
		}

		// Recalculate topic/forum counts when parent changes.
		if ( ! empty( $topic_id ) && $topic_id !== $old_topic_id ) {
			if ( ! empty( $old_topic_id ) ) {
				bbp_update_topic( array( 'topic_id' => $old_topic_id ) );
			}
			bbp_update_topic( array( 'topic_id' => $topic_id ) );
		}
		if ( ! empty( $forum_id ) && $forum_id !== $old_forum_id ) {
			if ( ! empty( $old_forum_id ) ) {
				bbp_update_forum( array( 'forum_id' => $old_forum_id ) );
			}
			bbp_update_forum( array( 'forum_id' => $forum_id ) );
		}

		// Resolve actual IDs for hooks — use submitted values or fall back to current meta.
		// This prevents passing 0 to hook listeners when IDs were not in the request.
		$hook_topic_id = ! empty( $topic_id ) ? $topic_id : (int) bbp_get_reply_topic_id( $reply_id );
		$hook_forum_id = ! empty( $forum_id ) ? $forum_id : (int) bbp_get_reply_forum_id( $reply_id );

		/**
		 * Fires after reply edit is complete in Settings 2.0 admin.
		 *
		 * This is the primary lifecycle hook that triggers reply metadata updates
		 * via bbp_update_reply() registered in bp-forums/core/actions.php.
		 * Must fire before bbp_edit_reply_post_extras for correct ordering.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int   $reply_id       Reply ID.
		 * @param int   $topic_id       Topic ID.
		 * @param int   $forum_id       Forum ID.
		 * @param array $anonymous_data Empty array (admin users are not anonymous).
		 * @param int   $reply_author   Reply author user ID.
		 * @param bool  $is_edit        Whether this is an edit (always true here).
		 * @param int   $reply_to       Reply-to ID for threading.
		 */
		do_action( 'bbp_edit_reply', $reply_id, $hook_topic_id, $hook_forum_id, array(), (int) $reply->post_author, true, $reply_to );

		/**
		 * Fires after reply edit is complete in Settings 2.0 admin.
		 *
		 * Mirrors the legacy bbp_edit_reply_post_extras hook for third-party
		 * plugin compatibility.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $reply_id Reply ID.
		 */
		do_action( 'bbp_edit_reply_post_extras', $reply_id );

		/**
		 * Fires after reply attributes are saved in Settings 2.0 admin.
		 *
		 * Mirrors the legacy bbp_reply_attributes_metabox_save hook.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $reply_id Reply ID.
		 * @param int $topic_id Topic ID.
		 * @param int $forum_id Forum ID.
		 * @param int $reply_to Reply-to ID for threading.
		 */
		do_action( 'bbp_reply_attributes_metabox_save', $reply_id, $hook_topic_id, $hook_forum_id, $reply_to );

		/**
		 * Fires after reply author is saved in Settings 2.0 admin.
		 *
		 * Mirrors the legacy bbp_author_metabox_save hook.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int   $reply_id       Reply ID.
		 * @param array $anonymous_data Empty array (admin users are not anonymous).
		 */
		do_action( 'bbp_author_metabox_save', $reply_id, array() );

		// Re-fetch the reply after wp_update_post() so extension field callbacks receive up-to-date data.
		$reply = get_post( $reply_id );

		// Save "after" phase extension fields (Pro/third-party) via meta field registry.
		bb_admin_meta_field_registry()->save_fields_data( 'replies', $reply, 'after' );

		$this->bb_clear_forum_counts_cache();

		wp_send_json_success(
			array(
				'reply_id' => $reply_id,
				'message'  => __( 'Reply updated successfully.', 'buddyboss' ),
			)
		);
	}

	/**
	 * Delete a reply.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function delete_reply() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$reply_id = isset( $_POST['reply_id'] ) ? absint( wp_unslash( $_POST['reply_id'] ) ) : 0;

		if ( empty( $reply_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Reply ID is required.', 'buddyboss' ) ) );
		}

		$reply = get_post( $reply_id );
		if ( ! $reply || bbp_get_reply_post_type() !== $reply->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Reply not found.', 'buddyboss' ) ) );
		}

		// wp_delete_post() triggers bbp_delete_reply() via `delete_post` hook
		// (registered at bp-forums/core/actions.php:184). No explicit call needed.
		$result = wp_delete_post( $reply_id, true );
		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete reply.', 'buddyboss' ) ) );
		}

		$this->bb_clear_forum_counts_cache();

		wp_send_json_success(
			array(
				'message' => __( 'Reply deleted successfully.', 'buddyboss' ),
			)
		);
	}

	/**
	 * Perform bulk action on replies.
	 *
	 * Supports 'delete', 'spam', and 'edit' actions.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function reply_bulk_action() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$reply_ids_raw   = isset( $_POST['reply_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['reply_ids'] ) ) : '';
		$do_action       = isset( $_POST['do_action'] ) ? sanitize_key( wp_unslash( $_POST['do_action'] ) ) : '';
		$edit_visibility = isset( $_POST['edit_visibility'] ) ? sanitize_key( wp_unslash( $_POST['edit_visibility'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $reply_ids_raw ) || empty( $do_action ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid bulk action parameters.', 'buddyboss' ) ) );
		}

		// Validate action.
		$allowed_actions = array( 'delete', 'spam', 'edit' );
		if ( ! in_array( $do_action, $allowed_actions, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid bulk action.', 'buddyboss' ) ) );
		}

		$reply_ids = array_map( 'absint', array_filter( explode( ',', $reply_ids_raw ) ) );
		if ( empty( $reply_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No replies selected.', 'buddyboss' ) ) );
		}

		// Cap bulk operations.
		$reply_ids = array_slice( $reply_ids, 0, self::BULK_CAP );

		$processed = 0;
		$failed    = 0;
		$reply_pt  = bbp_get_reply_post_type();

		// Prime the post cache in a single query to prevent N+1 get_post() calls.
		// _prime_post_caches() is public since WP 6.1; guard for WP 6.0 compat.
		if ( function_exists( '_prime_post_caches' ) ) {
			_prime_post_caches( $reply_ids, true, false );
		}

		// Defer term counting to avoid recalculating after each individual item.
		wp_defer_term_counting( true );

		foreach ( $reply_ids as $rid ) {
			$reply = get_post( $rid );
			if ( ! $reply || $reply_pt !== $reply->post_type ) {
				++$failed;
				continue;
			}

			if ( 'delete' === $do_action ) {
				// wp_delete_post() triggers bbp_delete_reply() via `delete_post` hook.
				$result = wp_delete_post( $rid, true );
				if ( $result ) {
					++$processed;
				} else {
					++$failed;
				}
			} elseif ( 'spam' === $do_action ) {
				$is_spam = bbp_get_spam_status_id() === get_post_status( $rid );
				if ( $is_spam ) {
					bbp_unspam_reply( $rid );
				} else {
					bbp_spam_reply( $rid );
				}
				++$processed;
			} elseif ( 'edit' === $do_action ) {
				$updated = false;

				if ( ! empty( $edit_visibility ) && 'no_change' !== $edit_visibility ) {
					$allowed_visibilities = array( 'publish', 'private', 'hidden' );
					if ( in_array( $edit_visibility, $allowed_visibilities, true ) ) {
						$update_result = wp_update_post(
							array(
								'ID'            => $rid,
								'post_status'   => $edit_visibility,
								'post_password' => '',
							),
							true
						);

						if ( is_wp_error( $update_result ) ) {
							++$failed;
							continue;
						}

						$updated = true;
					}
				}

				if ( $updated ) {
					$topic_id = bbp_get_reply_topic_id( $rid );
					$forum_id = bbp_get_reply_forum_id( $rid );

					/**
					 * Fires after a reply is bulk-edited in Settings 2.0 admin.
					 *
					 * Fires the primary lifecycle hook so that bbp_update_reply()
					 * runs count recalculation (registered at core/actions.php:178).
					 *
					 * @since BuddyBoss [BBVERSION]
					 *
					 * @param int $rid Reply ID.
					 */
					do_action(
						'bbp_edit_reply',
						$rid,
						$topic_id,
						$forum_id,
						array(),
						get_post_field( 'post_author', $rid ),
						true,
						bbp_get_reply_to( $rid )
					);
					do_action( 'bbp_edit_reply_post_extras', $rid );
					++$processed;
				} else {
					++$failed;
				}
			}
		}

		// Resume deferred term counting.
		wp_defer_term_counting( false );

		$this->bb_clear_forum_counts_cache();

		if ( 0 === $processed ) {
			wp_send_json_error(
				array(
					'message' => __( 'No replies were processed.', 'buddyboss' ),
				)
			);
		}

		if ( 'edit' === $do_action ) {
			wp_send_json_success(
				array(
					'processed' => $processed,
					'failed'    => $failed,
					'message'   => sprintf(
						/* translators: %d: number of replies updated. */
						_n( '%d reply updated.', '%d replies updated.', $processed, 'buddyboss' ),
						$processed
					),
				)
			);
		} else {
			wp_send_json_success(
				array(
					'processed' => $processed,
					'failed'    => $failed,
					'message'   => sprintf(
						/* translators: %d: number of replies processed. */
						_n( '%d reply processed.', '%d replies processed.', $processed, 'buddyboss' ),
						$processed
					),
				)
			);
		}
	}

	/**
	 * Autocomplete endpoint for discussions (topics).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function discussion_autocomplete() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$term        = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
		$page        = isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : 1;
		$selected_id = isset( $_POST['selected_id'] ) ? absint( wp_unslash( $_POST['selected_id'] ) ) : 0;
		$forum_id    = isset( $_POST['forum_id'] ) ? absint( wp_unslash( $_POST['forum_id'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$page     = max( 1, $page );
		$per_page = 20;

		// Resolve selected_id on initial load.
		if ( $selected_id && empty( $term ) && 1 === $page ) {
			$topic = get_post( $selected_id );
			if ( $topic && bbp_get_topic_post_type() === $topic->post_type ) {
				wp_send_json_success(
					array(
						'results'  => array(
							array(
								'value' => (string) $topic->ID,
								'label' => $topic->post_title,
							),
						),
						'has_more' => false,
					)
				);
			}
			wp_send_json_success(
				array(
					'results'  => array(),
					'has_more' => false,
				)
			);
		}

		$query_args = array(
			'post_type'              => bbp_get_topic_post_type(),
			'posts_per_page'         => $per_page + 1,
			'paged'                  => $page,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'post_status'            => array( 'publish', 'closed', 'private', 'hidden' ),
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		if ( ! empty( $term ) ) {
			$query_args['s'] = $term;
		}

		// Filter by forum.
		if ( ! empty( $forum_id ) ) {
			$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for forum filtering.
				array(
					'key'   => '_bbp_forum_id',
					'value' => $forum_id,
				),
			);
		}

		$topics = get_posts( $query_args );

		$has_more = count( $topics ) > $per_page;
		if ( $has_more ) {
			array_pop( $topics );
		}

		$results = array();
		foreach ( $topics as $topic ) {
			$results[] = array(
				'value' => (string) $topic->ID,
				'label' => $topic->post_title,
			);
		}

		wp_send_json_success(
			array(
				'results'  => $results,
				'has_more' => $has_more,
			)
		);
	}

	/**
	 * Autocomplete endpoint for replies.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function reply_autocomplete() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$term        = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
		$page        = isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : 1;
		$selected_id = isset( $_POST['selected_id'] ) ? absint( wp_unslash( $_POST['selected_id'] ) ) : 0;
		$topic_id    = isset( $_POST['topic_id'] ) ? absint( wp_unslash( $_POST['topic_id'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$page     = max( 1, $page );
		$per_page = 20;

		// Resolve selected_id on initial load.
		if ( $selected_id && empty( $term ) && 1 === $page ) {
			$reply = get_post( $selected_id );
			if ( $reply && bbp_get_reply_post_type() === $reply->post_type ) {
				$content_raw = wp_strip_all_tags( $reply->post_content );
				$label       = mb_strlen( $content_raw ) > 80
					? mb_substr( $content_raw, 0, 80 ) . '...'
					: $content_raw;

				wp_send_json_success(
					array(
						'results'  => array(
							array(
								'value' => (string) $reply->ID,
								'label' => $label,
							),
						),
						'has_more' => false,
					)
				);
			}
			wp_send_json_success(
				array(
					'results'  => array(),
					'has_more' => false,
				)
			);
		}

		$query_args = array(
			'post_type'              => bbp_get_reply_post_type(),
			'posts_per_page'         => $per_page + 1,
			'paged'                  => $page,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'post_status'            => array( 'publish', 'private', 'hidden' ),
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		);

		if ( ! empty( $term ) ) {
			$query_args['s'] = $term;
		}

		// Filter by topic.
		if ( ! empty( $topic_id ) ) {
			$query_args['post_parent'] = $topic_id;
		}

		$replies = get_posts( $query_args );

		$has_more = count( $replies ) > $per_page;
		if ( $has_more ) {
			array_pop( $replies );
		}

		$results = array();
		foreach ( $replies as $reply ) {
			$content_raw = wp_strip_all_tags( $reply->post_content );
			$label       = mb_strlen( $content_raw ) > 80
				? mb_substr( $content_raw, 0, 80 ) . '...'
				: $content_raw;

			$results[] = array(
				'value' => (string) $reply->ID,
				'label' => $label,
			);
		}

		wp_send_json_success(
			array(
				'results'  => $results,
				'has_more' => $has_more,
			)
		);
	}
}

new BB_Admin_Replies_Ajax();
