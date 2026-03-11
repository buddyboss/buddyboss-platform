<?php
/**
 * BuddyBoss Discussions (Topics) Admin AJAX Handler
 *
 * Handles AJAX requests for Discussion CRUD operations
 * in the Settings 2.0 admin interface.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Topics_Ajax
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Admin_Topics_Ajax {

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
	 * Maximum topics for bulk operations.
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

		// Invalidate admin forum counts cache when topic data changes.
		$clear_counts = array( $this, 'bb_clear_forum_counts_cache' );
		add_action( 'bbp_delete_topic', $clear_counts );
		add_action( 'bbp_deleted_topic', $clear_counts );
		add_action( 'bbp_trash_topic', $clear_counts );
		add_action( 'save_post_' . bbp_get_topic_post_type(), $clear_counts );
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function bb_register_ajax_handlers() {
		add_action( 'wp_ajax_bb_admin_get_discussions', array( $this, 'get_discussions' ) );
		add_action( 'wp_ajax_bb_admin_get_discussion', array( $this, 'get_discussion' ) );
		add_action( 'wp_ajax_bb_admin_create_discussion', array( $this, 'create_discussion' ) );
		add_action( 'wp_ajax_bb_admin_save_discussion', array( $this, 'save_discussion' ) );
		add_action( 'wp_ajax_bb_admin_delete_discussion', array( $this, 'delete_discussion' ) );
		add_action( 'wp_ajax_bb_admin_discussion_bulk_action', array( $this, 'discussion_bulk_action' ) );
		add_action( 'wp_ajax_bb_admin_topic_tag_autocomplete', array( $this, 'topic_tag_autocomplete' ) );
	}

	/**
	 * Verify AJAX request (capability + nonce).
	 *
	 * Capability is checked first because it is cheaper and avoids
	 * consuming a nonce check for unauthorized users.
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
	 * Clear the admin discussion forum counts cache.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_clear_forum_counts_cache() {
		wp_cache_delete( 'bb_admin_discussions_forum_counts', 'bbpress' );
	}

	/**
	 * Get discussions listing with pagination, filters, and sorting.
	 *
	 * Uses WP_Query on the topic post type. Applies legacy column header
	 * filter so third-party plugins that add custom columns continue to work.
	 *
	 * Hooks used:
	 * - `bbp_admin_topics_column_headers` (column definitions)
	 * - `bbp_admin_topics_column_data`    (custom column content)
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function get_discussions() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$page         = isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : 1;
		$per_page     = isset( $_POST['per_page'] ) ? absint( wp_unslash( $_POST['per_page'] ) ) : 20;
		$search       = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$forum_id     = isset( $_POST['forum_id'] ) ? absint( wp_unslash( $_POST['forum_id'] ) ) : 0;
		$tag_id       = isset( $_POST['tag_id'] ) ? absint( wp_unslash( $_POST['tag_id'] ) ) : 0;
		$sort         = isset( $_POST['sort'] ) ? sanitize_key( wp_unslash( $_POST['sort'] ) ) : 'newest';
		$include_meta = isset( $_POST['include_meta'] ) ? absint( wp_unslash( $_POST['include_meta'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Validate sort.
		$allowed_sorts = array( 'newest', 'oldest', 'highest_replies', 'lowest_replies', 'highest_members', 'lowest_members' );
		if ( ! in_array( $sort, $allowed_sorts, true ) ) {
			$sort = 'newest';
		}

		// Clamp per_page.
		$per_page = max( 1, min( self::PER_PAGE_CAP, $per_page ) );
		$page     = max( 1, $page );

		// Build WP_Query args.
		$query_args = array(
			'post_type'      => bbp_get_topic_post_type(),
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_status'    => array( 'publish', 'closed', 'private', 'hidden' ),
		);

		// Forum filter.
		if ( ! empty( $forum_id ) ) {
			$query_args['meta_key']   = '_bbp_forum_id'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for forum filtering.
			$query_args['meta_value'] = $forum_id; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Required for forum filtering.
		}

		// Topic tag filter.
		if ( ! empty( $tag_id ) ) {
			$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for tag filtering.
				array(
					'taxonomy' => bbp_get_topic_tag_tax_id(),
					'field'    => 'term_id',
					'terms'    => $tag_id,
				),
			);
		}

		// Search.
		if ( ! empty( $search ) ) {
			$query_args['s'] = $search;
		}

		// Sort mapping.
		switch ( $sort ) {
			case 'oldest':
				$query_args['orderby'] = 'date';
				$query_args['order']   = 'ASC';
				break;
			case 'highest_replies':
				$query_args['orderby']  = 'meta_value_num';
				$query_args['meta_key'] = '_bbp_reply_count'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for sorting by reply count.
				$query_args['order']    = 'DESC';
				break;
			case 'lowest_replies':
				$query_args['orderby']  = 'meta_value_num';
				$query_args['meta_key'] = '_bbp_reply_count'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for sorting by reply count.
				$query_args['order']    = 'ASC';
				break;
			case 'highest_members':
				$query_args['orderby']  = 'meta_value_num';
				$query_args['meta_key'] = '_bbp_voice_count'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for sorting by member count.
				$query_args['order']    = 'DESC';
				break;
			case 'lowest_members':
				$query_args['orderby']  = 'meta_value_num';
				$query_args['meta_key'] = '_bbp_voice_count'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for sorting by member count.
				$query_args['order']    = 'ASC';
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

		// Get columns via the same filter as the legacy topics admin.
		$all_columns = apply_filters(
			'bbp_admin_topics_column_headers',
			array(
				'cb'                     => '<input type="checkbox" />',
				'title'                  => __( 'Discussion', 'buddyboss' ),
				'bbp_topic_forum'        => __( 'Forum', 'buddyboss' ),
				'bbp_topic_reply_count'  => __( 'Replies', 'buddyboss' ),
				'bbp_topic_voice_count'  => __( 'Members', 'buddyboss' ),
				'bbp_topic_freshness'    => __( 'Last Post', 'buddyboss' ),
			)
		);

		// Identify custom columns (added by third-party plugins).
		$core_columns   = array( 'cb', 'title', 'bbp_topic_forum', 'bbp_topic_reply_count', 'bbp_topic_voice_count', 'bbp_topic_freshness' );
		$custom_columns = array();
		foreach ( $all_columns as $col_key => $col_label ) {
			if ( ! in_array( $col_key, $core_columns, true ) ) {
				$custom_columns[ $col_key ] = $col_label;
			}
		}

		// Buffer output to capture stray HTML from legacy filters.
		ob_start();

		$items = array();
		foreach ( $posts as $topic ) {
			$topic_id  = $topic->ID;
			$author_id = (int) $topic->post_author;
			$user      = get_userdata( $author_id );
			$topic_forum_id = (int) get_post_meta( $topic_id, '_bbp_forum_id', true );

			// Get topic tags.
			$tag_tax_id = bbp_get_topic_tag_tax_id();
			$tags       = array();
			if ( ! empty( $tag_tax_id ) ) {
				$topic_tags = get_the_terms( $topic_id, $tag_tax_id );
				if ( ! empty( $topic_tags ) && ! is_wp_error( $topic_tags ) ) {
					foreach ( $topic_tags as $tag ) {
						$tags[] = array(
							'id'   => $tag->term_id,
							'name' => $tag->name,
							'slug' => $tag->slug,
						);
					}
				}
			}

			$last_active = get_post_meta( $topic_id, '_bbp_last_active_time', true );

			$item = array(
				'id'           => $topic_id,
				'title'        => $topic->post_title,
				'slug'         => $topic->post_name,
				'description'  => $topic->post_content,
				'forum_id'     => $topic_forum_id,
				'forum_name'   => $topic_forum_id ? get_the_title( $topic_forum_id ) : '',
				'reply_count'  => (int) get_post_meta( $topic_id, '_bbp_reply_count', true ),
				'voice_count'  => (int) get_post_meta( $topic_id, '_bbp_voice_count', true ),
				'last_active'  => ! empty( $last_active ) ? bbp_get_time_since( strtotime( $last_active ) ) : '',
				'author_id'    => $author_id,
				'author_name'  => $user ? $user->display_name : '',
				'author_avatar' => get_avatar_url( $author_id, array( 'size' => 32 ) ),
				'permalink'    => bbp_get_topic_permalink( $topic_id ),
				'post_status'  => get_post_status( $topic_id ),
				'topic_status' => bbp_is_topic_closed( $topic_id ) ? 'closed' : 'open',
				'is_sticky'    => bbp_is_topic_sticky( $topic_id ),
				'is_super_sticky' => bbp_is_topic_super_sticky( $topic_id ),
				'tags'         => $tags,
			);

			// Render custom columns via legacy filter.
			if ( ! empty( $custom_columns ) ) {
				$item['custom_columns'] = array();
				foreach ( $custom_columns as $col_key => $col_label ) {
					ob_start();
					/**
					 * Fires for custom column data rendering in the topics admin list.
					 *
					 * @since bbPress (r2485)
					 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX.
					 *
					 * @param string $col_key  Column key.
					 * @param int    $topic_id Topic ID.
					 */
					do_action( 'bbp_admin_topics_column_data', $col_key, $topic_id );
					$item['custom_columns'][ $col_key ] = wp_kses_post( ob_get_clean() );
				}
			}

			$items[] = $item;
		}

		// End output buffer.
		ob_end_clean();

		$response = array(
			'discussions' => $items,
			'total'       => $total,
		);

		// Include metadata on first request.
		if ( $include_meta ) {
			$forum_counts = $this->bb_get_forum_counts();

			$count_all = 0;
			$forums    = array();
			foreach ( $forum_counts as $fid => $count ) {
				$count_all += $count;
				$forums[]   = array(
					'id'    => (int) $fid,
					'name'  => get_the_title( $fid ),
					'count' => $count,
				);
			}

			$response['views'] = array(
				'all'    => $count_all,
				'forums' => $forums,
			);

			$response['bulk_actions'] = array(
				'bulk_edit'   => __( 'Edit', 'buddyboss' ),
				'bulk_delete' => __( 'Delete', 'buddyboss' ),
			);

			// Return column definitions (excluding cb).
			$columns_response = array();
			foreach ( $all_columns as $col_key => $col_label ) {
				if ( 'cb' === $col_key ) {
					continue;
				}
				$columns_response[ $col_key ] = $col_label;
			}
			$response['columns'] = $columns_response;
		}

		/**
		 * Filters the full response data for the admin discussions list AJAX endpoint.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $response Response data array.
		 */
		$response = apply_filters( 'bb_admin_get_discussions_response', $response );

		wp_send_json_success( $response );
	}

	/**
	 * Get a single discussion for the edit modal.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function get_discussion() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$topic_id = isset( $_POST['topic_id'] ) ? absint( wp_unslash( $_POST['topic_id'] ) ) : 0;

		if ( empty( $topic_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Discussion ID is required.', 'buddyboss' ) ) );
		}

		$topic = get_post( $topic_id );
		if ( ! $topic || bbp_get_topic_post_type() !== $topic->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Discussion not found.', 'buddyboss' ) ) );
		}

		$topic_forum_id = (int) get_post_meta( $topic_id, '_bbp_forum_id', true );

		// Get topic tags.
		$tag_tax_id = bbp_get_topic_tag_tax_id();
		$tags       = array();
		$tag_names  = array();
		if ( ! empty( $tag_tax_id ) ) {
			$topic_tags = get_the_terms( $topic_id, $tag_tax_id );
			if ( ! empty( $topic_tags ) && ! is_wp_error( $topic_tags ) ) {
				foreach ( $topic_tags as $tag ) {
					$tags[]      = array(
						'id'   => $tag->term_id,
						'name' => $tag->name,
						'slug' => $tag->slug,
					);
					$tag_names[] = $tag->name;
				}
			}
		}

		// Determine type.
		$type = 'normal';
		if ( bbp_is_topic_super_sticky( $topic_id ) ) {
			$type = 'super_sticky';
		} elseif ( bbp_is_topic_sticky( $topic_id ) ) {
			$type = 'sticky';
		}

		$data = array(
			'id'           => (int) $topic->ID,
			'title'        => $topic->post_title,
			'description'  => $topic->post_content,
			'forum_id'     => $topic_forum_id,
			'forum_name'   => $topic_forum_id ? get_the_title( $topic_forum_id ) : '',
			'type'         => $type,
			'post_status'  => get_post_status( $topic_id ),
			'topic_status' => bbp_is_topic_closed( $topic_id ) ? 'closed' : 'open',
			'permalink'    => bbp_get_topic_permalink( $topic_id ),
			'tags'         => $tags,
			'tag_names'    => implode( ', ', $tag_names ),
		);

		/**
		 * Filters the response data for the admin single discussion endpoint.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array   $data  Response data array.
		 * @param WP_Post $topic The topic post object.
		 */
		wp_send_json_success( apply_filters( 'bb_admin_get_discussion_response', $data, $topic ) );
	}

	/**
	 * Create a new discussion (topic).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function create_discussion() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$title        = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$description  = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';
		$forum_id     = isset( $_POST['forum_id'] ) ? absint( wp_unslash( $_POST['forum_id'] ) ) : 0;
		$type         = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'normal';
		$topic_status = isset( $_POST['topic_status'] ) ? sanitize_key( wp_unslash( $_POST['topic_status'] ) ) : 'open';
		$visibility   = isset( $_POST['visibility'] ) ? sanitize_key( wp_unslash( $_POST['visibility'] ) ) : 'publish';
		$tags_raw     = isset( $_POST['tags'] ) ? sanitize_text_field( wp_unslash( $_POST['tags'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $title ) ) {
			wp_send_json_error( array( 'message' => __( 'Discussion title is required.', 'buddyboss' ) ) );
		}

		if ( empty( $forum_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Forum is required.', 'buddyboss' ) ) );
		}

		// Validate visibility.
		$allowed_visibilities = array( 'publish', 'private', 'hidden' );
		if ( ! in_array( $visibility, $allowed_visibilities, true ) ) {
			$visibility = 'publish';
		}

		// Validate topic status.
		$allowed_statuses = array( 'open', 'closed' );
		if ( ! in_array( $topic_status, $allowed_statuses, true ) ) {
			$topic_status = 'open';
		}

		// Validate type.
		$allowed_types = array( 'normal', 'sticky', 'super_sticky' );
		if ( ! in_array( $type, $allowed_types, true ) ) {
			$type = 'normal';
		}

		$topic_data = array(
			'post_title'   => $title,
			'post_content' => $description,
			'post_status'  => $visibility,
			'post_parent'  => $forum_id,
		);

		$topic_meta = array(
			'forum_id' => $forum_id,
		);

		$topic_id = bbp_insert_topic( $topic_data, $topic_meta );

		if ( ! $topic_id || is_wp_error( $topic_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to create discussion.', 'buddyboss' ) ) );
		}

		// Handle sticky type.
		if ( 'sticky' === $type ) {
			bbp_stick_topic( $topic_id );
		} elseif ( 'super_sticky' === $type ) {
			bbp_stick_topic( $topic_id, true );
		}

		// Handle topic status (open/closed).
		if ( 'closed' === $topic_status ) {
			bbp_close_topic( $topic_id );
		}

		// Handle tags.
		if ( ! empty( $tags_raw ) ) {
			$tag_names = array_map( 'trim', explode( ',', $tags_raw ) );
			$tag_names = array_filter( $tag_names );
			if ( ! empty( $tag_names ) ) {
				wp_set_object_terms( $topic_id, $tag_names, bbp_get_topic_tag_tax_id() );
			}
		}

		// Clear forum counts cache.
		$this->bb_clear_forum_counts_cache();

		wp_send_json_success(
			array(
				'message'  => __( 'Discussion created successfully.', 'buddyboss' ),
				'topic_id' => $topic_id,
			)
		);
	}

	/**
	 * Update an existing discussion (topic).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function save_discussion() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$topic_id     = isset( $_POST['topic_id'] ) ? absint( wp_unslash( $_POST['topic_id'] ) ) : 0;
		$title        = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$description  = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';
		$forum_id     = isset( $_POST['forum_id'] ) ? absint( wp_unslash( $_POST['forum_id'] ) ) : 0;
		$type         = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$topic_status = isset( $_POST['topic_status'] ) ? sanitize_key( wp_unslash( $_POST['topic_status'] ) ) : '';
		$visibility   = isset( $_POST['visibility'] ) ? sanitize_key( wp_unslash( $_POST['visibility'] ) ) : '';
		$tags_raw     = isset( $_POST['tags'] ) ? sanitize_text_field( wp_unslash( $_POST['tags'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $topic_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Discussion ID is required.', 'buddyboss' ) ) );
		}

		$topic = get_post( $topic_id );
		if ( ! $topic || bbp_get_topic_post_type() !== $topic->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Discussion not found.', 'buddyboss' ) ) );
		}

		// Build update args.
		$update_args = array(
			'ID' => $topic_id,
		);

		if ( ! empty( $title ) ) {
			$update_args['post_title'] = $title;
		}

		// Allow empty description to clear it.
		if ( isset( $_POST['description'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
			$update_args['post_content'] = $description;
		}

		// Validate and set visibility.
		if ( ! empty( $visibility ) ) {
			$allowed_visibilities = array( 'publish', 'private', 'hidden' );
			if ( in_array( $visibility, $allowed_visibilities, true ) ) {
				$update_args['post_status'] = $visibility;
			}
		}

		// Update forum if changed.
		if ( ! empty( $forum_id ) ) {
			$update_args['post_parent'] = $forum_id;
			update_post_meta( $topic_id, '_bbp_forum_id', $forum_id );
		}

		$result = wp_update_post( $update_args, true );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Handle sticky type.
		if ( ! empty( $type ) ) {
			$allowed_types = array( 'normal', 'sticky', 'super_sticky' );
			if ( in_array( $type, $allowed_types, true ) ) {
				// First unstick, then re-stick if needed.
				bbp_unstick_topic( $topic_id );
				if ( 'sticky' === $type ) {
					bbp_stick_topic( $topic_id );
				} elseif ( 'super_sticky' === $type ) {
					bbp_stick_topic( $topic_id, true );
				}
			}
		}

		// Handle topic status (open/closed).
		if ( ! empty( $topic_status ) ) {
			$allowed_statuses = array( 'open', 'closed' );
			if ( in_array( $topic_status, $allowed_statuses, true ) ) {
				if ( 'closed' === $topic_status ) {
					bbp_close_topic( $topic_id );
				} else {
					bbp_open_topic( $topic_id );
				}
			}
		}

		// Handle tags.
		if ( isset( $_POST['tags'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
			$tag_names = array_map( 'trim', explode( ',', $tags_raw ) );
			$tag_names = array_filter( $tag_names );
			wp_set_object_terms( $topic_id, $tag_names, bbp_get_topic_tag_tax_id() );
		}

		// Clear forum counts cache.
		$this->bb_clear_forum_counts_cache();

		wp_send_json_success(
			array(
				'message'  => __( 'Discussion updated successfully.', 'buddyboss' ),
				'topic_id' => $topic_id,
			)
		);
	}

	/**
	 * Delete a single discussion (topic).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function delete_discussion() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$topic_id = isset( $_POST['topic_id'] ) ? absint( wp_unslash( $_POST['topic_id'] ) ) : 0;

		if ( empty( $topic_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Discussion ID is required.', 'buddyboss' ) ) );
		}

		$topic = get_post( $topic_id );
		if ( ! $topic || bbp_get_topic_post_type() !== $topic->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Discussion not found.', 'buddyboss' ) ) );
		}

		// Fire bbPress pre-delete hook for cleanup.
		bbp_delete_topic( $topic_id );

		$result = wp_delete_post( $topic_id, true );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete discussion.', 'buddyboss' ) ) );
		}

		// Clear forum counts cache.
		$this->bb_clear_forum_counts_cache();

		wp_send_json_success(
			array( 'message' => __( 'Discussion deleted successfully.', 'buddyboss' ) )
		);
	}

	/**
	 * Perform bulk action on discussions (topics).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function discussion_bulk_action() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$raw_ids    = isset( $_POST['topic_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['topic_ids'] ) ) : '';
		$do_action  = isset( $_POST['do_action'] ) ? sanitize_key( wp_unslash( $_POST['do_action'] ) ) : '';
		$edit_type  = isset( $_POST['edit_type'] ) ? sanitize_key( wp_unslash( $_POST['edit_type'] ) ) : '';
		$edit_visibility = isset( $_POST['edit_visibility'] ) ? sanitize_key( wp_unslash( $_POST['edit_visibility'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$allowed_actions = array( 'delete', 'edit' );
		if ( ! in_array( $do_action, $allowed_actions, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid action.', 'buddyboss' ) ) );
		}

		if ( empty( $raw_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No discussions selected.', 'buddyboss' ) ) );
		}

		$topic_ids = array_filter( array_map( 'absint', explode( ',', $raw_ids ) ) );

		if ( empty( $topic_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No valid discussion IDs provided.', 'buddyboss' ) ) );
		}

		// Cap bulk operations to prevent timeout.
		$topic_ids = array_slice( $topic_ids, 0, self::BULK_CAP );

		$processed = 0;
		$failed    = 0;

		foreach ( $topic_ids as $topic_id ) {
			$topic = get_post( $topic_id );
			if ( ! $topic || bbp_get_topic_post_type() !== $topic->post_type ) {
				++$failed;
				continue;
			}

			if ( 'delete' === $do_action ) {
				// Fire bbPress pre-delete hook for cleanup.
				bbp_delete_topic( $topic_id );
				$result = wp_delete_post( $topic_id, true );
				if ( $result ) {
					++$processed;
				} else {
					++$failed;
				}
			} elseif ( 'edit' === $do_action ) {
				$updated = false;

				// Update type if provided and not "no change".
				if ( ! empty( $edit_type ) && 'no_change' !== $edit_type ) {
					$allowed_types = array( 'normal', 'sticky', 'super_sticky' );
					if ( in_array( $edit_type, $allowed_types, true ) ) {
						bbp_unstick_topic( $topic_id );
						if ( 'sticky' === $edit_type ) {
							bbp_stick_topic( $topic_id );
						} elseif ( 'super_sticky' === $edit_type ) {
							bbp_stick_topic( $topic_id, true );
						}
						$updated = true;
					}
				}

				// Update visibility if provided and not "no change".
				if ( ! empty( $edit_visibility ) && 'no_change' !== $edit_visibility ) {
					$allowed_visibilities = array( 'publish', 'private', 'hidden' );
					if ( in_array( $edit_visibility, $allowed_visibilities, true ) ) {
						wp_update_post(
							array(
								'ID'          => $topic_id,
								'post_status' => $edit_visibility,
							)
						);
						$updated = true;
					}
				}

				if ( $updated ) {
					++$processed;
				} else {
					++$failed;
				}
			}
		}

		// Clear forum counts cache.
		$this->bb_clear_forum_counts_cache();

		if ( $processed > 0 ) {
			if ( 'delete' === $do_action ) {
				$message = sprintf(
					/* translators: %d: Number of discussions processed. */
					_n(
						'%d discussion deleted successfully.',
						'%d discussions deleted successfully.',
						$processed,
						'buddyboss'
					),
					$processed
				);
			} else {
				$message = sprintf(
					/* translators: %d: Number of discussions processed. */
					_n(
						'%d discussion updated successfully.',
						'%d discussions updated successfully.',
						$processed,
						'buddyboss'
					),
					$processed
				);
			}

			wp_send_json_success(
				array(
					'message'   => $message,
					'processed' => $processed,
					'failed'    => $failed,
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'No discussions were processed.', 'buddyboss' ) ) );
		}
	}

	/**
	 * Autocomplete endpoint for topic tags.
	 *
	 * Searches the topic-tag taxonomy and returns matching terms.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function topic_tag_autocomplete() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		if ( empty( $search ) ) {
			wp_send_json_success( array( 'tags' => array() ) );
		}

		$tag_tax_id = bbp_get_topic_tag_tax_id();
		if ( empty( $tag_tax_id ) ) {
			wp_send_json_success( array( 'tags' => array() ) );
		}

		$term_query = new WP_Term_Query(
			array(
				'taxonomy'   => $tag_tax_id,
				'search'     => $search,
				'hide_empty' => false,
				'number'     => 20,
			)
		);

		$results = array();
		if ( ! empty( $term_query->terms ) && ! is_wp_error( $term_query->terms ) ) {
			foreach ( $term_query->terms as $term ) {
				$results[] = array(
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			}
		}

		wp_send_json_success( array( 'tags' => $results ) );
	}

	/**
	 * Get discussion counts per forum with caching.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array Associative array of forum_id => count.
	 */
	private function bb_get_forum_counts() {
		$cache_key  = 'bb_admin_discussions_forum_counts';
		$counts_map = wp_cache_get( $cache_key, 'bbpress' );

		if ( false === $counts_map ) {
			global $wpdb;
			$topic_post_type = bbp_get_topic_post_type();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom forum counts for topics, not available via core API.
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT pm.meta_value AS forum_id, COUNT(p.ID) AS cnt
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_bbp_forum_id'
					WHERE p.post_type = %s AND p.post_status IN ('publish', 'closed', 'private', 'hidden')
					GROUP BY pm.meta_value",
					$topic_post_type
				)
			);

			$counts_map = array();
			foreach ( $results as $row ) {
				if ( ! empty( $row->forum_id ) ) {
					$counts_map[ $row->forum_id ] = (int) $row->cnt;
				}
			}
			wp_cache_set( $cache_key, $counts_map, 'bbpress' );
		}

		return $counts_map;
	}
}

// Initialize.
new BB_Admin_Topics_Ajax();
