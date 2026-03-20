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
	 * Clear the admin discussion forum counts cache.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_clear_forum_counts_cache() {
		wp_cache_delete( 'bb_admin_discussions_forum_counts', 'bbpress' );

		// Also clear the forums list status counts cache since topic changes
		// affect forum-level aggregate counts (_bbp_total_topic_count).
		wp_cache_delete( 'bb_admin_forums_status_counts', 'bbpress' );

		// Clear the per-user "Mine" count cache for the forums list screen,
		// since topic changes affect forum topic counts.
		wp_cache_delete( 'bb_admin_forums_mine_count_' . get_current_user_id(), 'bbpress' );
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
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
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
			'post_status'    => array( 'publish', 'closed', 'private', 'hidden', 'spam' ),
		);

		// Forum filter — use meta_query to avoid conflict with meta-based sorting.
		if ( ! empty( $forum_id ) ) {
			$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for forum filtering.
				'forum_filter' => array(
					'key'   => '_bbp_forum_id',
					'value' => $forum_id,
				),
			);
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

		// Sort mapping — uses meta_query named clauses to coexist with forum filter.
		$needs_meta_sort = false;
		$sort_meta_key   = '';

		switch ( $sort ) {
			case 'oldest':
				$query_args['orderby'] = 'date';
				$query_args['order']   = 'ASC';
				break;
			case 'highest_replies':
				$needs_meta_sort     = true;
				$sort_meta_key       = '_bbp_reply_count';
				$query_args['order'] = 'DESC';
				break;
			case 'lowest_replies':
				$needs_meta_sort     = true;
				$sort_meta_key       = '_bbp_reply_count';
				$query_args['order'] = 'ASC';
				break;
			case 'highest_members':
				$needs_meta_sort     = true;
				$sort_meta_key       = '_bbp_voice_count';
				$query_args['order'] = 'DESC';
				break;
			case 'lowest_members':
				$needs_meta_sort     = true;
				$sort_meta_key       = '_bbp_voice_count';
				$query_args['order'] = 'ASC';
				break;
			case 'newest':
			default:
				$query_args['orderby'] = 'date';
				$query_args['order']   = 'DESC';
				break;
		}

		// When sorting by meta, add a named clause and order by it.
		if ( $needs_meta_sort ) {
			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for meta-based sorting.
			}
			$query_args['meta_query']['sort_clause'] = array(
				'key'  => $sort_meta_key,
				'type' => 'NUMERIC',
			);
			$query_args['orderby']                   = 'sort_clause';
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

		// Prime parent forum post caches to prevent N+1 queries on get_the_title() in the loop.
		// WP_Query already primes post meta cache, so get_post_meta() calls below are cache-served.
		if ( ! empty( $posts ) ) {
			$forum_ids = array();
			foreach ( $posts as $topic ) {
				$fid = (int) get_post_meta( $topic->ID, '_bbp_forum_id', true );
				if ( $fid ) {
					$forum_ids[] = $fid;
				}
			}
			$forum_ids = array_unique( array_filter( $forum_ids ) );
			if ( ! empty( $forum_ids ) ) {
				_prime_post_caches( $forum_ids, false, false );
			}
		}

		// Get columns via the same filter as the legacy topics admin.
		$all_columns = apply_filters(
			'bbp_admin_topics_column_headers',
			array(
				'cb'                    => '<input type="checkbox" />',
				'title'                 => __( 'Discussion', 'buddyboss' ),
				'bbp_topic_author'      => __( 'Author', 'buddyboss' ),
				'bbp_topic_forum'       => __( 'Forum', 'buddyboss' ),
				'bbp_topic_reply_count' => __( 'Replies', 'buddyboss' ),
				'bbp_topic_voice_count' => __( 'Members', 'buddyboss' ),
				'bbp_topic_freshness'   => __( 'Last Post', 'buddyboss' ),
			)
		);

		// Identify custom columns (added by third-party plugins).
		$core_columns   = array( 'cb', 'title', 'bbp_topic_author', 'bbp_topic_forum', 'bbp_topic_reply_count', 'bbp_topic_voice_count', 'bbp_topic_freshness' );
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
			$topic_id       = $topic->ID;
			$author_id      = (int) $topic->post_author;
			$user           = get_userdata( $author_id );
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
				'id'              => $topic_id,
				'title'           => $topic->post_title,
				'slug'            => $topic->post_name,
				'description'     => $topic->post_content,
				'forum_id'        => $topic_forum_id,
				'forum_name'      => $topic_forum_id ? get_the_title( $topic_forum_id ) : '',
				'reply_count'     => (int) get_post_meta( $topic_id, '_bbp_reply_count', true ),
				'voice_count'     => (int) get_post_meta( $topic_id, '_bbp_voice_count', true ),
				'last_active'     => ! empty( $last_active ) ? bbp_get_time_since( strtotime( $last_active ) ) : '',
				'author_id'       => $author_id,
				'author_name'     => $user ? $user->display_name : '',
				'author_avatar'   => get_avatar_url( $author_id, array( 'size' => 32 ) ),
				'permalink'       => bbp_get_topic_permalink( $topic_id ),
				'post_status'     => get_post_status( $topic_id ),
				'topic_status'    => bbp_is_topic_closed( $topic_id ) ? 'closed' : 'open',
				'is_sticky'       => bbp_is_topic_sticky( $topic_id ),
				'is_super_sticky' => bbp_is_topic_super_sticky( $topic_id ),
				'tags'            => $tags,
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
			'total_pages' => ceil( $total / $per_page ),
		);

		// Include metadata on first request.
		if ( $include_meta ) {
			$forum_counts = $this->bb_get_forum_counts();

			// Prime post cache in batch to avoid N+1 queries from get_the_title().
			$forum_ids = array_keys( $forum_counts );
			if ( ! empty( $forum_ids ) ) {
				_prime_post_caches( $forum_ids, false, false );
			}

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
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
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
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
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
			// Super-sticky is not allowed for group forum topics.
			if ( function_exists( 'bb_is_group_forum_topic' ) && bb_is_group_forum_topic( $topic_id ) ) {
				bbp_stick_topic( $topic_id );
			} else {
				bbp_stick_topic( $topic_id, true );
			}
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

		/**
		 * Fires after a new topic is created in Settings 2.0 admin.
		 *
		 * This hook triggers activity stream entries, notifications, and
		 * other core integrations (same as frontend topic creation).
		 *
		 * @since bbPress (r2160)
		 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX.
		 *
		 * @param int   $topic_id       Topic ID.
		 * @param int   $forum_id       Forum ID.
		 * @param array $anonymous_data Anonymous user data (empty for admin).
		 * @param int   $topic_author   Topic author user ID.
		 */
		do_action( 'bbp_new_topic', $topic_id, $forum_id, array(), bbp_get_current_user_id() );

		/**
		 * Fires after a new topic post extras in Settings 2.0 admin.
		 *
		 * Mirrors the legacy bbp_new_topic_post_extras hook for third-party
		 * plugin compatibility.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $topic_id Topic ID.
		 */
		do_action( 'bbp_new_topic_post_extras', $topic_id );

		/**
		 * Fires after topic attributes are set during creation in Settings 2.0 admin.
		 *
		 * In legacy bbPress, this hook fired on both create and edit via save_post.
		 * Ensures third-party plugins that set custom topic attributes on creation
		 * continue to work.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $topic_id Topic ID.
		 * @param int $forum_id Forum ID.
		 */
		do_action( 'bbp_topic_attributes_metabox_save', $topic_id, $forum_id );

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
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
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

		// Capture old forum ID before update for count recalculation.
		$old_forum_id = (int) bbp_get_topic_forum_id( $topic_id );

		// Update forum if changed.
		if ( ! empty( $forum_id ) ) {
			$update_args['post_parent'] = $forum_id;
			update_post_meta( $topic_id, '_bbp_forum_id', $forum_id );
		}

		$result = wp_update_post( $update_args, true );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => html_entity_decode( $result->get_error_message(), ENT_QUOTES, 'UTF-8' ) ) );
		}

		// Recalculate forum counts when forum changes.
		if ( ! empty( $forum_id ) && $forum_id !== $old_forum_id ) {
			// Remove sticky from old forum if topic was sticky there.
			if ( ! empty( $old_forum_id ) ) {
				$old_stickies = bbp_get_stickies( $old_forum_id );
				if ( ! empty( $old_stickies ) ) {
					$updated_stickies = array_diff( $old_stickies, array( $topic_id ) );
					if ( $updated_stickies !== $old_stickies ) {
						if ( empty( $updated_stickies ) ) {
							delete_post_meta( $old_forum_id, '_bbp_sticky_topics' );
						} else {
							update_post_meta( $old_forum_id, '_bbp_sticky_topics', array_values( $updated_stickies ) );
						}
					}
				}
				bbp_update_forum( array( 'forum_id' => $old_forum_id ) );
			}

			// Batch-update _bbp_forum_id meta on all replies belonging to this topic.
			$reply_ids = bbp_get_all_child_ids( $topic_id, bbp_get_reply_post_type() );
			if ( ! empty( $reply_ids ) ) {
				global $wpdb;

				$reply_ids    = array_map( 'absint', $reply_ids );
				$placeholders = implode( ',', array_fill( 0, count( $reply_ids ), '%d' ) );

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Batch update required for performance; caches cleared below.
				$wpdb->query(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $placeholders is safe (generated from array_fill with %d).
						"UPDATE {$wpdb->postmeta} SET meta_value = %d WHERE meta_key = '_bbp_forum_id' AND post_id IN ({$placeholders})",
						array_merge( array( $forum_id ), $reply_ids )
					)
				);

				// Clear post meta caches for affected replies.
				foreach ( $reply_ids as $reply_id ) {
					wp_cache_delete( $reply_id, 'post_meta' );
				}
			}

			bbp_update_forum( array( 'forum_id' => $forum_id ) );
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
					// Super-sticky is not allowed for group forum topics.
					if ( function_exists( 'bb_is_group_forum_topic' ) && bb_is_group_forum_topic( $topic_id ) ) {
						bbp_stick_topic( $topic_id );
					} else {
						bbp_stick_topic( $topic_id, true );
					}
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

		// Resolve the actual forum_id for hooks — use submitted value or fall back to current meta.
		// This prevents passing 0 to hook listeners when forum_id was not in the request.
		$hook_forum_id = ! empty( $forum_id ) ? $forum_id : (int) bbp_get_topic_forum_id( $topic_id );

		/**
		 * Fires after topic edit is complete in Settings 2.0 admin.
		 *
		 * This is the primary lifecycle hook that triggers topic metadata updates
		 * via bbp_update_topic() registered in bp-forums/core/actions.php.
		 * Must fire before bbp_edit_topic_post_extras for correct ordering.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int   $topic_id       Topic ID.
		 * @param int   $forum_id       Forum ID.
		 * @param array $anonymous_data Empty array (admin users are not anonymous).
		 * @param int   $topic_author   Topic author user ID.
		 * @param bool  $is_edit        Whether this is an edit (always true here).
		 */
		do_action( 'bbp_edit_topic', $topic_id, $hook_forum_id, array(), (int) $topic->post_author, true );

		/**
		 * Fires after topic edit is complete in Settings 2.0 admin.
		 *
		 * Mirrors the legacy bbp_edit_topic_post_extras hook for third-party
		 * plugin compatibility.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $topic_id Topic ID.
		 */
		do_action( 'bbp_edit_topic_post_extras', $topic_id );

		/**
		 * Fires after topic attributes are saved in Settings 2.0 admin.
		 *
		 * Mirrors the legacy bbp_topic_attributes_metabox_save hook.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $topic_id Topic ID.
		 * @param int $forum_id Forum ID.
		 */
		do_action( 'bbp_topic_attributes_metabox_save', $topic_id, $hook_forum_id );

		/**
		 * Fires after topic author is saved in Settings 2.0 admin.
		 *
		 * Mirrors the legacy bbp_author_metabox_save hook.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int   $topic_id       Topic ID.
		 * @param array $anonymous_data Empty array (admin users are not anonymous).
		 */
		do_action( 'bbp_author_metabox_save', $topic_id, array() );

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
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$topic_id = isset( $_POST['topic_id'] ) ? absint( wp_unslash( $_POST['topic_id'] ) ) : 0;

		if ( empty( $topic_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Discussion ID is required.', 'buddyboss' ) ) );
		}

		$topic = get_post( $topic_id );
		if ( ! $topic || bbp_get_topic_post_type() !== $topic->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Discussion not found.', 'buddyboss' ) ) );
		}

		// wp_delete_post() triggers bbp_delete_topic() via `delete_post` hook
		// (registered at bp-forums/core/actions.php:206). No explicit call needed.
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
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$raw_ids         = isset( $_POST['topic_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['topic_ids'] ) ) : '';
		$do_action       = isset( $_POST['do_action'] ) ? sanitize_key( wp_unslash( $_POST['do_action'] ) ) : '';
		$edit_type       = isset( $_POST['edit_type'] ) ? sanitize_key( wp_unslash( $_POST['edit_type'] ) ) : '';
		$edit_status     = isset( $_POST['edit_status'] ) ? sanitize_key( wp_unslash( $_POST['edit_status'] ) ) : '';
		$edit_visibility = isset( $_POST['edit_visibility'] ) ? sanitize_key( wp_unslash( $_POST['edit_visibility'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$allowed_actions = array( 'delete', 'edit', 'spam' );
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

		// Prime the post cache in a single query to prevent N+1 get_post() calls.
		// _prime_post_caches() is public since WP 6.1; guard for WP 6.0 compat.
		if ( function_exists( '_prime_post_caches' ) ) {
			_prime_post_caches( $topic_ids, false, false );
		}

		foreach ( $topic_ids as $topic_id ) {
			$topic = get_post( $topic_id );
			if ( ! $topic || bbp_get_topic_post_type() !== $topic->post_type ) {
				++$failed;
				continue;
			}

			if ( 'delete' === $do_action ) {
				// wp_delete_post() triggers bbp_delete_topic() via `delete_post` hook.
				$result = wp_delete_post( $topic_id, true );
				if ( $result ) {
					++$processed;
				} else {
					++$failed;
				}
			} elseif ( 'spam' === $do_action ) {
				// Toggle spam status: spam -> unspam, anything else -> spam.
				$current_status = get_post_status( $topic_id );
				if ( bbp_get_spam_status_id() === $current_status ) {
					bbp_unspam_topic( $topic_id );
				} else {
					bbp_spam_topic( $topic_id );
				}
				++$processed;
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
							// Super-sticky is not allowed for group forum topics.
							if ( function_exists( 'bb_is_group_forum_topic' ) && bb_is_group_forum_topic( $topic_id ) ) {
								bbp_stick_topic( $topic_id );
							} else {
								bbp_stick_topic( $topic_id, true );
							}
						}
						$updated = true;
					}
				}

				// Update visibility BEFORE status so that close/open doesn't get
				// overwritten by a subsequent post_status change.
				if ( ! empty( $edit_visibility ) && 'no_change' !== $edit_visibility ) {
					$allowed_visibilities = array( 'publish', 'private', 'hidden' );
					if ( in_array( $edit_visibility, $allowed_visibilities, true ) ) {
						$update_result = wp_update_post(
							array(
								'ID'          => $topic_id,
								'post_status' => $edit_visibility,
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

				// Update status (open/closed/spam) if provided and not "no change".
				// Runs after visibility so that bbp_close_topic()'s post_status
				// change is the final write and is not overridden.
				if ( ! empty( $edit_status ) && 'no_change' !== $edit_status ) {
					$current_status = bbp_get_topic_status( $topic_id );
					if ( 'closed' === $edit_status && 'closed' !== $current_status ) {
						bbp_close_topic( $topic_id );
						$updated = true;
					} elseif ( 'open' === $edit_status && 'closed' === $current_status ) {
						bbp_open_topic( $topic_id );
						$updated = true;
					} elseif ( 'spam' === $edit_status && bbp_get_spam_status_id() !== get_post_status( $topic_id ) ) {
						bbp_spam_topic( $topic_id );
						$updated = true;
					}
				}

				if ( $updated ) {
					$forum_id = bbp_get_topic_forum_id( $topic_id );

					/**
					 * Fires after a discussion is bulk-edited in Settings 2.0 admin.
					 *
					 * Fires the primary lifecycle hook so that bbp_update_topic()
					 * runs count recalculation (registered at core/actions.php:193).
					 *
					 * @since BuddyBoss [BBVERSION]
					 *
					 * @param int $topic_id Topic ID.
					 */
					do_action(
						'bbp_edit_topic',
						$topic_id,
						$forum_id,
						array(),
						$topic->post_author,
						true
					);
					do_action( 'bbp_edit_topic_post_extras', $topic_id );
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
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
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
					WHERE p.post_type = %s AND p.post_status IN ('publish', 'closed', 'private', 'hidden', 'spam')
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
			wp_cache_set( $cache_key, $counts_map, 'bbpress', HOUR_IN_SECONDS );
		}

		return $counts_map;
	}
}

// Initialize.
new BB_Admin_Topics_Ajax();
