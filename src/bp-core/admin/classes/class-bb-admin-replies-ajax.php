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
	 * Verify AJAX request (capability + nonce).
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function bb_verify_request() {
		if ( ! bp_current_user_can( 'bp_moderate' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied.', 'buddyboss' ) ),
				403
			);
		}

		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Security check failed.', 'buddyboss' ) ),
				403
			);
		}
	}

	/**
	 * Clear the admin replies forum counts cache.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_clear_forum_counts_cache() {
		wp_cache_delete( 'bb_admin_replies_forum_counts', 'bbpress' );
	}

	/**
	 * Get replies listing with pagination, filters, and sorting.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function get_replies() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
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
			'post_status'    => array( 'publish', 'private', 'hidden', bbp_get_spam_status_id() ),
		);

		// Forum filter.
		if ( ! empty( $forum_id ) ) {
			$query_args['meta_key']   = '_bbp_forum_id'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query_args['meta_value'] = $forum_id; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
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

		// Get columns via the same filter as the legacy replies admin.
		$all_columns = apply_filters(
			'bbp_admin_replies_column_headers',
			array(
				'cb'                => '<input type="checkbox" />',
				'title'             => __( 'Reply', 'buddyboss' ),
				'bbp_reply_forum'   => __( 'Forum', 'buddyboss' ),
				'bbp_reply_topic'   => __( 'Discussion', 'buddyboss' ),
				'bbp_reply_created' => __( 'Created', 'buddyboss' ),
			)
		);

		// Identify custom columns (added by third-party plugins).
		$core_columns   = array( 'cb', 'title', 'bbp_reply_forum', 'bbp_reply_topic', 'bbp_reply_created' );
		$custom_columns = array();
		foreach ( $all_columns as $col_key => $col_label ) {
			if ( ! in_array( $col_key, $core_columns, true ) ) {
				$custom_columns[ $col_key ] = $col_label;
			}
		}

		// Buffer output.
		ob_start();

		$items = array();
		foreach ( $posts as $reply ) {
			$reply_id  = $reply->ID;
			$author_id = (int) $reply->post_author;
			$user      = get_userdata( $author_id );

			$reply_forum_id = (int) get_post_meta( $reply_id, '_bbp_forum_id', true );
			$reply_topic_id = (int) get_post_meta( $reply_id, '_bbp_topic_id', true );

			// Generate content excerpt (max 100 chars).
			$content_raw = wp_strip_all_tags( $reply->post_content );
			$content_excerpt = mb_strlen( $content_raw ) > 100
				? mb_substr( $content_raw, 0, 100 ) . '...'
				: $content_raw;

			$reply_status = get_post_status( $reply_id );
			$is_spam      = bbp_get_spam_status_id() === $reply_status;

			$item = array(
				'id'              => $reply_id,
				'content'         => $content_excerpt,
				'content_raw'     => $reply->post_content,
				'forum_id'        => $reply_forum_id,
				'forum_name'      => $reply_forum_id ? get_the_title( $reply_forum_id ) : '',
				'topic_id'        => $reply_topic_id,
				'topic_title'     => $reply_topic_id ? get_the_title( $reply_topic_id ) : '',
				'author_id'       => $author_id,
				'author_name'     => $user ? $user->display_name : '',
				'author_avatar'   => get_avatar_url( $author_id, array( 'size' => 32 ) ),
				'permalink'       => bbp_get_reply_url( $reply_id ),
				'post_status'     => $reply_status,
				'is_spam'         => $is_spam,
				'reply_to'        => (int) get_post_meta( $reply_id, '_bbp_reply_to', true ),
				'created_date'    => get_the_date( '', $reply_id ),
				'created_time'    => get_the_time( '', $reply_id ),
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
					$item['custom_columns'][ $col_key ] = ob_get_clean();
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
				'delete' => __( 'Delete', 'buddyboss' ),
				'spam'   => __( 'Mark as Spam', 'buddyboss' ),
			);

			$columns = array();
			foreach ( $all_columns as $key => $label ) {
				if ( 'cb' !== $key ) {
					$columns[ $key ] = $label;
				}
			}

			$response['meta'] = array(
				'views'        => array(
					'all'    => $total,
					'forums' => $forum_counts,
				),
				'bulk_actions' => $bulk_actions,
				'columns'      => $columns,
			);
		}

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

		$reply_type = bbp_get_reply_post_type();
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
			if ( ! empty( $forum_ids ) ) {
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
		$this->bb_verify_request();

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
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

		wp_send_json_success(
			array(
				'id'          => $reply_id,
				'content'     => $reply->post_content,
				'forum_id'    => $forum_id,
				'forum_name'  => $forum_id ? get_the_title( $forum_id ) : '',
				'topic_id'    => $topic_id,
				'topic_title' => $topic_id ? get_the_title( $topic_id ) : '',
				'reply_to'    => $reply_to,
				'post_status' => get_post_status( $reply_id ),
				'permalink'   => bbp_get_reply_url( $reply_id ),
			)
		);
	}

	/**
	 * Create a new reply.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function create_reply() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$content    = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$forum_id   = isset( $_POST['forum_id'] ) ? absint( wp_unslash( $_POST['forum_id'] ) ) : 0;
		$topic_id   = isset( $_POST['topic_id'] ) ? absint( wp_unslash( $_POST['topic_id'] ) ) : 0;
		$reply_to   = isset( $_POST['reply_to'] ) ? absint( wp_unslash( $_POST['reply_to'] ) ) : 0;
		$visibility = isset( $_POST['visibility'] ) ? sanitize_key( wp_unslash( $_POST['visibility'] ) ) : 'publish';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $content ) ) {
			wp_send_json_error( array( 'message' => __( 'Reply content is required.', 'buddyboss' ) ) );
		}

		if ( empty( $topic_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Discussion is required.', 'buddyboss' ) ) );
		}

		// Validate visibility.
		$allowed_visibilities = array( 'publish', 'private', 'hidden' );
		if ( ! in_array( $visibility, $allowed_visibilities, true ) ) {
			$visibility = 'publish';
		}

		// Auto-detect forum_id from topic if not provided.
		if ( empty( $forum_id ) && ! empty( $topic_id ) ) {
			$forum_id = (int) get_post_meta( $topic_id, '_bbp_forum_id', true );
		}

		$reply_data = array(
			'post_content' => $content,
			'post_status'  => $visibility,
			'post_parent'  => $topic_id,
		);

		$reply_meta = array(
			'forum_id' => $forum_id,
			'topic_id' => $topic_id,
		);

		$reply_id = bbp_insert_reply( $reply_data, $reply_meta );

		if ( ! $reply_id || is_wp_error( $reply_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to create reply.', 'buddyboss' ) ) );
		}

		// Handle reply-to threading.
		if ( ! empty( $reply_to ) ) {
			update_post_meta( $reply_id, '_bbp_reply_to', $reply_to );
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
		$this->bb_verify_request();

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$reply_id   = isset( $_POST['reply_id'] ) ? absint( wp_unslash( $_POST['reply_id'] ) ) : 0;
		$content    = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$forum_id   = isset( $_POST['forum_id'] ) ? absint( wp_unslash( $_POST['forum_id'] ) ) : 0;
		$topic_id   = isset( $_POST['topic_id'] ) ? absint( wp_unslash( $_POST['topic_id'] ) ) : 0;
		$reply_to   = isset( $_POST['reply_to'] ) ? absint( wp_unslash( $_POST['reply_to'] ) ) : 0;
		$visibility = isset( $_POST['visibility'] ) ? sanitize_key( wp_unslash( $_POST['visibility'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $reply_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Reply ID is required.', 'buddyboss' ) ) );
		}

		$reply = get_post( $reply_id );
		if ( ! $reply || bbp_get_reply_post_type() !== $reply->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Reply not found.', 'buddyboss' ) ) );
		}

		// Validate visibility.
		$allowed_visibilities = array( 'publish', 'private', 'hidden' );
		if ( ! empty( $visibility ) && ! in_array( $visibility, $allowed_visibilities, true ) ) {
			$visibility = 'publish';
		}

		// Update post data.
		$update_args = array(
			'ID' => $reply_id,
		);

		if ( ! empty( $content ) ) {
			$update_args['post_content'] = $content;
		}

		if ( ! empty( $visibility ) ) {
			$update_args['post_status'] = $visibility;
		}

		if ( ! empty( $topic_id ) ) {
			$update_args['post_parent'] = $topic_id;
		}

		$result = wp_update_post( $update_args, true );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Update meta.
		if ( ! empty( $forum_id ) ) {
			update_post_meta( $reply_id, '_bbp_forum_id', $forum_id );
		}

		if ( ! empty( $topic_id ) ) {
			update_post_meta( $reply_id, '_bbp_topic_id', $topic_id );
		}

		// Update reply-to threading.
		if ( $reply_to > 0 ) {
			update_post_meta( $reply_id, '_bbp_reply_to', $reply_to );
		} else {
			delete_post_meta( $reply_id, '_bbp_reply_to' );
		}

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
		$this->bb_verify_request();

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$reply_id = isset( $_POST['reply_id'] ) ? absint( wp_unslash( $_POST['reply_id'] ) ) : 0;

		if ( empty( $reply_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Reply ID is required.', 'buddyboss' ) ) );
		}

		$reply = get_post( $reply_id );
		if ( ! $reply || bbp_get_reply_post_type() !== $reply->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Reply not found.', 'buddyboss' ) ) );
		}

		wp_delete_post( $reply_id, true );

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
	 * Supports 'delete' and 'spam' actions.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function reply_bulk_action() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$reply_ids_raw = isset( $_POST['reply_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['reply_ids'] ) ) : '';
		$action        = isset( $_POST['do_action'] ) ? sanitize_key( wp_unslash( $_POST['do_action'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $reply_ids_raw ) || empty( $action ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid bulk action parameters.', 'buddyboss' ) ) );
		}

		// Validate action.
		$allowed_actions = array( 'delete', 'spam' );
		if ( ! in_array( $action, $allowed_actions, true ) ) {
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

		foreach ( $reply_ids as $rid ) {
			$reply = get_post( $rid );
			if ( ! $reply || $reply_pt !== $reply->post_type ) {
				++$failed;
				continue;
			}

			if ( 'delete' === $action ) {
				$result = wp_delete_post( $rid, true );
				if ( $result ) {
					++$processed;
				} else {
					++$failed;
				}
			} elseif ( 'spam' === $action ) {
				$is_spam = bbp_get_spam_status_id() === get_post_status( $rid );
				if ( $is_spam ) {
					bbp_unspam_reply( $rid );
				} else {
					bbp_spam_reply( $rid );
				}
				++$processed;
			}
		}

		$this->bb_clear_forum_counts_cache();

		wp_send_json_success(
			array(
				'processed' => $processed,
				'failed'    => $failed,
				'message'   => sprintf(
					/* translators: %d: number of replies processed. */
					__( '%d replies processed.', 'buddyboss' ),
					$processed
				),
			)
		);
	}

	/**
	 * Autocomplete endpoint for discussions (topics).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function discussion_autocomplete() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
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
			$query_args['meta_key']   = '_bbp_forum_id'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query_args['meta_value'] = $forum_id; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
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
		$this->bb_verify_request();

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
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
