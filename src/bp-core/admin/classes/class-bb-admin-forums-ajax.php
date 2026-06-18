<?php
/**
 * BuddyBoss Forums Admin AJAX Handler
 *
 * Handles AJAX requests for Forums CRUD operations
 * in the Settings 2.0 admin interface.
 *
 * @since   BuddyBoss 3.0.0
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Forums_Ajax
 *
 * @since BuddyBoss 3.0.0
 */
class BB_Admin_Forums_Ajax {

	/**
	 * Nonce action (shared with BB_Admin_Settings_Ajax).
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'bb_admin_settings';

	/**
	 * Maximum items per page for paginated endpoints.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @var int
	 */
	const PER_PAGE_CAP = 100;

	/**
	 * Maximum forums for bulk operations.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @var int
	 */
	const BULK_CAP = 100;

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function __construct() {
		$this->bb_register_ajax_handlers();

		// Invalidate admin status counts cache when forum data changes.
		$clear_counts = array( $this, 'bb_clear_status_counts_cache' );
		add_action( 'bbp_delete_forum', $clear_counts );
		add_action( 'bbp_deleted_forum', $clear_counts );
		add_action( 'bbp_trash_forum', $clear_counts );
		add_action( 'save_post_' . bbp_get_forum_post_type(), $clear_counts );
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	private function bb_register_ajax_handlers() {
		add_action( 'wp_ajax_bb_admin_get_forums', array( $this, 'get_forums' ) );
		add_action( 'wp_ajax_bb_admin_get_forum', array( $this, 'get_forum' ) );
		add_action( 'wp_ajax_bb_admin_create_forum', array( $this, 'create_forum' ) );
		add_action( 'wp_ajax_bb_admin_save_forum', array( $this, 'save_forum' ) );
		add_action( 'wp_ajax_bb_admin_delete_forum', array( $this, 'delete_forum' ) );
		add_action( 'wp_ajax_bb_admin_forum_bulk_action', array( $this, 'forum_bulk_action' ) );

		// Register forum autocomplete so it works even when the Groups component
		// is disabled. Uses priority 5 to fire before the Groups handler.
		add_action( 'wp_ajax_bb_admin_forum_autocomplete', array( $this, 'bb_forum_autocomplete' ), 5 );
	}

	/**
	 * Clear the admin forum status counts cache.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function bb_clear_status_counts_cache() {
		wp_cache_delete( 'bb_admin_forums_status_counts', 'bbpress' );
		// Bump a version key so every user's mine-count cache is invalidated
		// transparently (the read path keys on this version). Avoids the prior
		// behavior where only the saving user's mine-count was busted, leaving
		// other users' counts stale until their own next save.
		bp_update_option( 'bb_admin_forums_mine_count_version', (int) bp_get_option( 'bb_admin_forums_mine_count_version', 0 ) + 1 );
	}

	/**
	 * Get forums listing with pagination, filters, and sorting.
	 *
	 * Uses WP_Query on the forum post type. Applies legacy column header
	 * filter so third-party plugins that add custom columns continue to work.
	 *
	 * Hooks used:
	 * - `bbp_admin_forums_column_headers` (column definitions)
	 * - `bbp_admin_forums_column_data`    (custom column content)
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function get_forums() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$page         = isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : 1;
		$per_page     = isset( $_POST['per_page'] ) ? absint( wp_unslash( $_POST['per_page'] ) ) : 20;
		$search       = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$status       = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'all';
		$sort         = isset( $_POST['sort'] ) ? sanitize_key( wp_unslash( $_POST['sort'] ) ) : 'newest';
		$include_meta = isset( $_POST['include_meta'] ) ? absint( wp_unslash( $_POST['include_meta'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Validate status.
		$allowed_statuses = array( 'all', 'publish', 'private', 'hidden', 'mine' );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = 'all';
		}

		// Validate sort.
		$allowed_sorts = array( 'newest', 'oldest', 'highest_discussions', 'lowest_discussions', 'highest_replies', 'lowest_replies' );
		if ( ! in_array( $sort, $allowed_sorts, true ) ) {
			$sort = 'newest';
		}

		// Clamp per_page.
		$per_page = max( 1, min( self::PER_PAGE_CAP, $per_page ) );
		$page     = max( 1, $page );

		// Build WP_Query args.
		$query_args = array(
			'post_type'      => bbp_get_forum_post_type(),
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_status'    => array( 'publish', 'private', 'hidden' ),
		);

		// Status filter.
		if ( 'mine' === $status ) {
			$query_args['author'] = get_current_user_id();
		} elseif ( 'all' !== $status ) {
			$query_args['post_status'] = array( $status );
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
			case 'highest_discussions':
				$query_args['orderby']  = 'meta_value_num';
				$query_args['meta_key'] = '_bbp_total_topic_count'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for sorting by discussion count.
				$query_args['order']    = 'DESC';
				break;
			case 'lowest_discussions':
				$query_args['orderby']  = 'meta_value_num';
				$query_args['meta_key'] = '_bbp_total_topic_count'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for sorting by discussion count.
				$query_args['order']    = 'ASC';
				break;
			case 'highest_replies':
				$query_args['orderby']  = 'meta_value_num';
				$query_args['meta_key'] = '_bbp_total_reply_count'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for sorting by reply count.
				$query_args['order']    = 'DESC';
				break;
			case 'lowest_replies':
				$query_args['orderby']  = 'meta_value_num';
				$query_args['meta_key'] = '_bbp_total_reply_count'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for sorting by reply count.
				$query_args['order']    = 'ASC';
				break;
			case 'newest':
			default:
				$query_args['orderby'] = 'date';
				$query_args['order']   = 'DESC';
				break;
		}

		// Remove bbPress visibility filter that overrides post_status on forum queries.
		remove_action( 'pre_get_posts', 'bbp_pre_get_posts_normalize_forum_visibility', 4 );

		$query = new WP_Query( $query_args );

		// Restore bbPress visibility filter.
		add_action( 'pre_get_posts', 'bbp_pre_get_posts_normalize_forum_visibility', 4 );

		$posts = $query->posts;
		$total = (int) $query->found_posts;

		// Prime user cache for authors.
		if ( ! empty( $posts ) ) {
			$author_ids = array_unique( wp_list_pluck( $posts, 'post_author' ) );
			if ( ! empty( $author_ids ) ) {
				cache_users( $author_ids );
			}
		}

		// Get columns via the same filter as the legacy forums admin.
		$all_columns = apply_filters(
			'bbp_admin_forums_column_headers',
			array(
				'cb'                    => '<input type="checkbox" />',
				'title'                 => __( 'Forum', 'buddyboss' ),
				'bbp_forum_topic_count' => __( 'Discussions', 'buddyboss' ),
				'bbp_forum_reply_count' => __( 'Replies', 'buddyboss' ),
				'author'                => __( 'Creator', 'buddyboss' ),
				'bbp_forum_created'     => __( 'Created', 'buddyboss' ),
				'bbp_forum_freshness'   => __( 'Last Post', 'buddyboss' ),
			)
		);

		// Identify custom columns (added by third-party plugins).
		$core_columns   = array( 'cb', 'title', 'bbp_forum_topic_count', 'bbp_forum_reply_count', 'author', 'bbp_forum_created', 'bbp_forum_freshness' );
		$custom_columns = array();
		foreach ( $all_columns as $col_key => $col_label ) {
			if ( ! in_array( $col_key, $core_columns, true ) ) {
				$custom_columns[ $col_key ] = $col_label;
			}
		}

		// Visibility labels.
		$visibility_labels = array(
			'publish' => __( 'Public', 'buddyboss' ),
			'private' => __( 'Private', 'buddyboss' ),
			'hidden'  => __( 'Hidden', 'buddyboss' ),
		);

		// Prime attachment posts referenced by _thumbnail_id so the per-row
		// wp_get_attachment_url() inside the loop is a cache hit. Authors are
		// already primed above; WP_Query primed forum-post meta.
		if ( ! empty( $posts ) ) {
			$thumbnail_ids = array();
			foreach ( $posts as $forum ) {
				$tid = (int) get_post_thumbnail_id( $forum->ID );
				if ( $tid ) {
					$thumbnail_ids[] = $tid;
				}
			}
			// _prime_post_caches() is public since WP 6.1; guard for WP 6.0 compat.
			if ( ! empty( $thumbnail_ids ) && function_exists( '_prime_post_caches' ) ) {
				_prime_post_caches( array_values( array_unique( $thumbnail_ids ) ), false, false );
			}
		}

		// Buffer output to capture stray HTML from legacy filters.
		ob_start();

		$items = array();
		foreach ( $posts as $forum ) {
			$forum_id   = $forum->ID;
			$visibility = get_post_status( $forum_id );
			$author_id  = (int) $forum->post_author;
			$user       = get_userdata( $author_id );

			$last_active  = bbp_get_forum_last_active_time( $forum_id, false );
			$thumbnail_id = get_post_thumbnail_id( $forum_id );

			$item = array(
				'id'                   => $forum_id,
				'name'                 => $forum->post_title,
				'slug'                 => $forum->post_name,
				'description'          => wp_trim_words( wp_strip_all_tags( $forum->post_content ), 20 ),
				'visibility'           => $visibility,
				'visibility_label'     => isset( $visibility_labels[ $visibility ] ) ? $visibility_labels[ $visibility ] : $visibility,
				'forum_status'         => bbp_get_forum_status( $forum_id ),
				'forum_type'           => bbp_get_forum_type( $forum_id ),
				'discussions_count'    => (int) get_post_meta( $forum_id, '_bbp_total_topic_count', true ),
				'replies_count'        => (int) get_post_meta( $forum_id, '_bbp_total_reply_count', true ),
				'author_id'            => $author_id,
				'author_name'          => $user ? $user->display_name : '',
				'author_avatar'        => get_avatar_url( $author_id, array( 'size' => 32 ) ),
				'last_active'          => ! empty( $last_active ) ? $last_active : '',
				'date_created'         => $forum->post_date,
				'permalink'            => bbp_get_forum_permalink( $forum_id ),
				'parent_id'            => (int) $forum->post_parent,
				'order'                => (int) $forum->menu_order,
				'is_group_forum'       => ! empty( bbp_get_forum_group_ids( $forum_id ) ),
				'is_group_forum_child' => function_exists( 'bb_get_child_forum_group_ids' ) && ! empty( bb_get_child_forum_group_ids( $forum_id ) ),
				'featured_image'       => $thumbnail_id ? wp_get_attachment_url( $thumbnail_id ) : '',
			);

			// Render custom columns via legacy filter.
			if ( ! empty( $custom_columns ) ) {
				$item['custom_columns'] = array();
				foreach ( $custom_columns as $col_key => $col_label ) {
					ob_start();
					/**
					 * Fires for custom column data rendering in the forums admin list.
					 *
					 * @since bbPress (r2485)
					 * @since BuddyBoss 3.0.0 Added to Settings 2.0 AJAX.
					 *
					 * @param string $col_key  Column key.
					 * @param int    $forum_id Forum ID.
					 */
					do_action( 'bbp_admin_forums_column_data', $col_key, $forum_id );
					$item['custom_columns'][ $col_key ] = wp_kses_post( ob_get_clean() );
				}
			}

			$items[] = $item;
		}

		// End output buffer.
		ob_end_clean();

		$response = array(
			'forums'      => $items,
			'total'       => $total,
			'total_pages' => ceil( $total / $per_page ),
		);

		// Include metadata on first request.
		if ( $include_meta ) {
			$counts_map = $this->bb_get_status_counts();

			$count_all = array_sum( $counts_map );

			$response['views'] = array(
				'all'     => array(
					'label' => __( 'All', 'buddyboss' ),
					'count' => $count_all,
				),
				'publish' => array(
					'label' => __( 'Public', 'buddyboss' ),
					'count' => isset( $counts_map['publish'] ) ? $counts_map['publish'] : 0,
				),
				'private' => array(
					'label' => __( 'Private', 'buddyboss' ),
					'count' => isset( $counts_map['private'] ) ? $counts_map['private'] : 0,
				),
				'hidden'  => array(
					'label' => __( 'Hidden', 'buddyboss' ),
					'count' => isset( $counts_map['hidden'] ) ? $counts_map['hidden'] : 0,
				),
				'mine'    => array(
					'label' => __( 'Mine', 'buddyboss' ),
					'count' => $this->bb_get_mine_count(),
				),
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

			// Forum base slug for permalink preview in create/edit modals.
			$response['forum_base_slug'] = get_option( '_bbp_forum_slug', 'forum' );

			// Provide registered field definitions for the create modal.
			// Uses a stdClass with default values instead of WP_Post to avoid
			// unnecessary class constructor overhead. All get_value callbacks
			// guard against ID=0 to prevent DB queries on this dummy object.
			$response['create_fields'] = bb_admin_meta_field_registry()->get_fields_data(
				'forums',
				(object) array(
					'ID'           => 0,
					'post_title'   => '',
					'post_name'    => '',
					'post_content' => '',
					'post_status'  => 'publish',
					'post_parent'  => 0,
					'post_author'  => get_current_user_id(),
					'menu_order'   => 0,
					'post_type'    => bbp_get_forum_post_type(),
				)
			);
		}

		/**
		 * Filters the full response data for the admin forums list AJAX endpoint.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param array $response Response data array.
		 */
		$response = apply_filters( 'bb_admin_get_forums_response', $response );

		wp_send_json_success( $response );
	}

	/**
	 * Get a single forum for the edit modal.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function get_forum() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$forum_id = isset( $_POST['forum_id'] ) ? absint( wp_unslash( $_POST['forum_id'] ) ) : 0;

		if ( empty( $forum_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Forum ID is required.', 'buddyboss' ) ) );
		}

		$forum = get_post( $forum_id );
		if ( ! $forum || bbp_get_forum_post_type() !== $forum->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Forum not found.', 'buddyboss' ) ) );
		}

		$thumbnail_id = get_post_thumbnail_id( $forum_id );

		$data = array(
			'id'                   => (int) $forum->ID,
			'name'                 => $forum->post_title,
			'slug'                 => $forum->post_name,
			'description'          => $forum->post_content,
			'visibility'           => get_post_status( $forum_id ),
			'forum_status'         => bbp_get_forum_status( $forum_id ),
			'forum_type'           => bbp_get_forum_type( $forum_id ),
			'parent_id'            => (int) $forum->post_parent,
			'order'                => (int) $forum->menu_order,
			'is_group_forum'       => ! empty( bbp_get_forum_group_ids( $forum_id ) ),
			'is_group_forum_child' => function_exists( 'bb_get_child_forum_group_ids' ) && ! empty( bb_get_child_forum_group_ids( $forum_id ) ),
			'permalink'            => bbp_get_forum_permalink( $forum_id ),
			'featured_image'       => $thumbnail_id ? wp_get_attachment_url( $thumbnail_id ) : '',
			'featured_image_id'    => $thumbnail_id ? (int) $thumbnail_id : 0,
			'registered_fields'    => bb_admin_meta_field_registry()->get_fields_data( 'forums', $forum ),
		);

		/**
		 * Filters the response data for the admin single forum endpoint.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param array   $data  Response data array.
		 * @param WP_Post $forum The forum post object.
		 */
		wp_send_json_success( apply_filters( 'bb_admin_get_forum_response', $data, $forum ) );
	}

	/**
	 * Create a new forum.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function create_forum() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$name         = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$slug         = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$description  = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';
		$visibility   = isset( $_POST['visibility'] ) ? sanitize_key( wp_unslash( $_POST['visibility'] ) ) : 'publish';
		$forum_status = isset( $_POST['forum_status'] ) ? sanitize_key( wp_unslash( $_POST['forum_status'] ) ) : 'open';
		$forum_type   = isset( $_POST['forum_type'] ) ? sanitize_key( wp_unslash( $_POST['forum_type'] ) ) : 'forum';
		$parent_id    = isset( $_POST['parent_id'] ) ? absint( wp_unslash( $_POST['parent_id'] ) ) : 0;
		$order        = isset( $_POST['order'] ) ? absint( wp_unslash( $_POST['order'] ) ) : 0;
		$image_id     = isset( $_POST['image_id'] ) ? absint( wp_unslash( $_POST['image_id'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Forum name is required.', 'buddyboss' ) ) );
		}

		// Validate parent_id references an actual forum if provided.
		if ( ! empty( $parent_id ) ) {
			$parent_post = get_post( $parent_id );
			if ( ! $parent_post || bbp_get_forum_post_type() !== $parent_post->post_type ) {
				wp_send_json_error( array( 'message' => __( 'Invalid parent forum.', 'buddyboss' ) ) );
			}
		}

		// Validate visibility.
		$allowed_visibilities = array( 'publish', 'private', 'hidden' );
		if ( ! in_array( $visibility, $allowed_visibilities, true ) ) {
			$visibility = 'publish';
		}

		// Validate forum status (open/closed).
		$allowed_statuses = array( 'open', 'closed' );
		if ( ! in_array( $forum_status, $allowed_statuses, true ) ) {
			$forum_status = 'open';
		}

		// Validate forum type (forum/category).
		$allowed_types = array( 'forum', 'category' );
		if ( ! in_array( $forum_type, $allowed_types, true ) ) {
			$forum_type = 'forum';
		}

		// Use slug from name if not provided.
		if ( empty( $slug ) ) {
			$slug = sanitize_title( $name );
		}

		$forum_data = array(
			'post_title'   => $name,
			'post_content' => $description,
			'post_status'  => $visibility,
			'post_parent'  => $parent_id,
			'post_name'    => $slug,
			'menu_order'   => $order,
		);

		// Run "before" phase fields so legacy meta-bridge save_value callbacks
		// populate $_POST with third-party metabox values before bbp_insert_forum
		// fires save_post_forum (where third-party plugins read $_POST and persist
		// their post meta). Pass an empty stub object — native fields' save_value
		// callbacks ($forum->post_title = $value, etc.) would fatal on null in
		// PHP 8+; mutations on the stub are harmless because $forum_data is
		// built directly from $_POST below.
		bb_admin_meta_field_registry()->save_fields_data( 'forums', new \stdClass(), 'before' );

		$forum_id = bbp_insert_forum( $forum_data );

		if ( ! $forum_id ) {
			wp_send_json_error( array( 'message' => __( 'Failed to create forum.', 'buddyboss' ) ) );
		}

		// Set forum status (open/closed) using bbPress lifecycle functions.
		if ( 'closed' === $forum_status ) {
			bbp_close_forum( $forum_id );
		} else {
			bbp_open_forum( $forum_id );
		}

		// Handle featured image.
		if ( ! empty( $image_id ) ) {
			set_post_thumbnail( $forum_id, $image_id );
		}

		// Populate legacy $_POST keys expected by bbp_save_forum_extras() and third-party hooks.
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$_POST['bbp_forum_status']     = $forum_status;
		$_POST['bbp_forum_type']       = $forum_type;
		$_POST['bbp_forum_visibility'] = $visibility;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		/**
		 * Fires after a new forum is created in Settings 2.0 admin.
		 *
		 * This is the primary lifecycle hook that triggers count initialization
		 * via bbp_update_forum() registered in bp-forums/core/actions.php.
		 * Must fire before bbp_new_forum_post_extras for correct ordering.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param array $forum_args {
		 *     Array of forum arguments matching legacy bbp_new_forum_handler() signature.
		 *
		 *     @type int    $forum_id           Forum ID.
		 *     @type int    $post_parent        Parent forum ID.
		 *     @type int    $forum_author       Forum author user ID.
		 *     @type int    $last_topic_id      Last topic ID (0 for new forums).
		 *     @type int    $last_reply_id      Last reply ID (0 for new forums).
		 *     @type int    $last_active_id     Last active ID (0 for new forums).
		 *     @type int    $last_active_time   Last active time (0 for new forums).
		 *     @type string $last_active_status Last active status.
		 * }
		 */
		do_action(
			'bbp_new_forum',
			array(
				'forum_id'           => $forum_id,
				'post_parent'        => $parent_id,
				'forum_author'       => bbp_get_current_user_id(),
				'last_topic_id'      => 0,
				'last_reply_id'      => 0,
				'last_active_id'     => 0,
				'last_active_time'   => 0,
				'last_active_status' => bbp_get_public_status_id(),
			)
		);

		/**
		 * Fires after a new forum is created in Settings 2.0 admin.
		 *
		 * Mirrors the legacy bbp_new_forum_post_extras hook for third-party
		 * plugin compatibility.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param int $forum_id Forum ID.
		 */
		do_action( 'bbp_new_forum_post_extras', $forum_id );

		// Note: bbp_forum_attributes_metabox_save is NOT fired here because
		// bbp_save_forum_extras() is already triggered by bbp_new_forum_post_extras
		// (registered at bp-forums/core/actions.php:172). Firing both would cause
		// bbp_save_forum_extras() to run twice.

		// Save "after" phase extension fields (Pro/third-party) via meta field registry.
		$created_forum = get_post( $forum_id );
		if ( $created_forum ) {
			bb_admin_meta_field_registry()->save_fields_data( 'forums', $created_forum, 'after' );
		}

		// Clear status counts cache.
		$this->bb_clear_status_counts_cache();

		wp_send_json_success(
			array(
				'message'  => __( 'Forum created successfully.', 'buddyboss' ),
				'forum_id' => $forum_id,
			)
		);
	}

	/**
	 * Update an existing forum.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function save_forum() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$forum_id     = isset( $_POST['forum_id'] ) ? absint( wp_unslash( $_POST['forum_id'] ) ) : 0;
		$name         = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$slug         = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$description  = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';
		$visibility   = isset( $_POST['visibility'] ) ? sanitize_key( wp_unslash( $_POST['visibility'] ) ) : '';
		$forum_status = isset( $_POST['forum_status'] ) ? sanitize_key( wp_unslash( $_POST['forum_status'] ) ) : '';
		$forum_type   = isset( $_POST['forum_type'] ) ? sanitize_key( wp_unslash( $_POST['forum_type'] ) ) : '';
		$parent_id    = isset( $_POST['parent_id'] ) ? absint( wp_unslash( $_POST['parent_id'] ) ) : 0;
		$order        = isset( $_POST['order'] ) ? absint( wp_unslash( $_POST['order'] ) ) : null;
		$image_id     = isset( $_POST['image_id'] ) ? absint( wp_unslash( $_POST['image_id'] ) ) : 0;
		$remove_image = isset( $_POST['remove_image'] ) ? absint( wp_unslash( $_POST['remove_image'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $forum_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Forum ID is required.', 'buddyboss' ) ) );
		}

		$forum = get_post( $forum_id );
		if ( ! $forum || bbp_get_forum_post_type() !== $forum->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Forum not found.', 'buddyboss' ) ) );
		}

		// Build update args.
		$update_args = array(
			'ID' => $forum_id,
		);

		if ( ! empty( $name ) ) {
			$update_args['post_title'] = $name;
		}

		if ( ! empty( $slug ) ) {
			$update_args['post_name'] = $slug;
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

		// Prevent circular reference (self-parenting).
		if ( $parent_id === $forum_id ) {
			$parent_id = 0;
		}

		// Validate parent_id references an actual forum if provided.
		if ( ! empty( $parent_id ) ) {
			$parent_post = get_post( $parent_id );
			if ( ! $parent_post || bbp_get_forum_post_type() !== $parent_post->post_type ) {
				wp_send_json_error( array( 'message' => __( 'Invalid parent forum.', 'buddyboss' ) ) );
			}
		}

		// Capture old parent before update for count recalculation.
		$old_parent_id = (int) $forum->post_parent;

		$update_args['post_parent'] = $parent_id;

		// Update menu_order if provided.
		if ( null !== $order ) {
			$update_args['menu_order'] = $order;
		}

		// Run "before" phase fields so legacy meta-bridge save_value callbacks
		// populate $_POST with third-party metabox values before wp_update_post
		// fires save_post_forum (where third-party plugins read $_POST and persist
		// their post meta).
		bb_admin_meta_field_registry()->save_fields_data( 'forums', $forum, 'before' );

		$result = wp_update_post( $update_args, true );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to update forum. Please try again.', 'buddyboss' ) ) );
		}

		// Update forum status (open/closed) using bbPress lifecycle functions.
		$allowed_statuses = array( 'open', 'closed' );
		if ( ! empty( $forum_status ) && in_array( $forum_status, $allowed_statuses, true ) ) {
			if ( 'closed' === $forum_status ) {
				bbp_close_forum( $forum_id );
			} else {
				bbp_open_forum( $forum_id );
			}
		}

		// Handle featured image.
		if ( $remove_image ) {
			delete_post_thumbnail( $forum_id );
		} elseif ( ! empty( $image_id ) ) {
			set_post_thumbnail( $forum_id, $image_id );
		}

		// Propagate visibility to child forums for group forums.
		if ( ! empty( $visibility ) ) {
			$this->bb_propagate_group_forum_visibility( $forum_id );
		}

		// Recalculate counts when parent changes.
		if ( $parent_id !== $old_parent_id ) {
			bbp_update_forum( array( 'forum_id' => $forum_id ) );
			if ( ! empty( $old_parent_id ) ) {
				bbp_update_forum( array( 'forum_id' => $old_parent_id ) );
			}
			if ( ! empty( $parent_id ) ) {
				bbp_update_forum( array( 'forum_id' => $parent_id ) );
			}
		}

		// Validate forum type (forum/category).
		$allowed_types = array( 'forum', 'category' );
		if ( ! empty( $forum_type ) && ! in_array( $forum_type, $allowed_types, true ) ) {
			$forum_type = '';
		}

		// Populate legacy $_POST keys expected by bbp_save_forum_extras() and third-party hooks.
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		if ( ! empty( $forum_status ) ) {
			$_POST['bbp_forum_status'] = $forum_status;
		}
		$_POST['bbp_forum_type'] = ! empty( $forum_type ) ? $forum_type : bbp_get_forum_type( $forum_id );
		if ( ! empty( $visibility ) ) {
			$_POST['bbp_forum_visibility'] = $visibility;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		/**
		 * Fires after forum edit is complete in Settings 2.0 admin.
		 *
		 * This is the primary lifecycle hook that triggers count recalculation
		 * via bbp_update_forum() registered in bp-forums/core/actions.php.
		 * Must fire before bbp_edit_forum_post_extras for correct ordering.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param array $forum_args {
		 *     Array of forum arguments matching legacy bbp_edit_forum_handler() signature.
		 *
		 *     @type int    $forum_id           Forum ID.
		 *     @type int    $post_parent        Parent forum ID.
		 *     @type int    $forum_author       Forum author user ID.
		 *     @type int    $last_topic_id      Last topic ID (0 for recalculation).
		 *     @type int    $last_reply_id      Last reply ID (0 for recalculation).
		 *     @type int    $last_active_id     Last active ID (0 for recalculation).
		 *     @type int    $last_active_time   Last active time (0 for recalculation).
		 *     @type string $last_active_status Last active status.
		 * }
		 */
		do_action(
			'bbp_edit_forum',
			array(
				'forum_id'           => $forum_id,
				'post_parent'        => $parent_id,
				'forum_author'       => $forum->post_author,
				'last_topic_id'      => 0,
				'last_reply_id'      => 0,
				'last_active_id'     => 0,
				'last_active_time'   => 0,
				'last_active_status' => bbp_get_public_status_id(),
			)
		);

		/**
		 * Fires after forum edit is complete in Settings 2.0 admin.
		 *
		 * Mirrors the legacy bbp_edit_forum_post_extras hook for third-party
		 * plugin compatibility.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param int $forum_id Forum ID.
		 */
		do_action( 'bbp_edit_forum_post_extras', $forum_id );

		// Note: bbp_forum_attributes_metabox_save is NOT fired here because
		// bbp_save_forum_extras() is already triggered by bbp_edit_forum_post_extras
		// (registered at bp-forums/core/actions.php:174). Firing both would cause
		// bbp_save_forum_extras() to run twice.

		// Re-fetch the forum post after wp_update_post() so extension field callbacks
		// receive up-to-date data (post_status, post_parent, etc. may have changed).
		$forum = get_post( $forum_id );

		// Save "after" phase extension fields (Pro/third-party) via meta field registry.
		bb_admin_meta_field_registry()->save_fields_data( 'forums', $forum, 'after' );

		// Clear status counts cache.
		$this->bb_clear_status_counts_cache();

		wp_send_json_success(
			array(
				'message'  => __( 'Forum updated successfully.', 'buddyboss' ),
				'forum_id' => $forum_id,
			)
		);
	}

	/**
	 * Delete a single forum.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function delete_forum() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$forum_id = isset( $_POST['forum_id'] ) ? absint( wp_unslash( $_POST['forum_id'] ) ) : 0;

		if ( empty( $forum_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Forum ID is required.', 'buddyboss' ) ) );
		}

		$forum = get_post( $forum_id );
		if ( ! $forum || bbp_get_forum_post_type() !== $forum->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Forum not found.', 'buddyboss' ) ) );
		}

		// wp_delete_post() triggers the `delete_post` hook which calls bbp_delete_forum()
		// (registered at bp-forums/core/actions.php:155) for cleanup. No explicit call needed.
		$result = wp_delete_post( $forum_id, true );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete forum.', 'buddyboss' ) ) );
		}

		// Clear status counts cache.
		$this->bb_clear_status_counts_cache();

		wp_send_json_success(
			array( 'message' => __( 'Forum deleted successfully.', 'buddyboss' ) )
		);
	}

	/**
	 * Perform bulk action on forums.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function forum_bulk_action() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$raw_ids         = isset( $_POST['forum_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['forum_ids'] ) ) : '';
		$do_action       = isset( $_POST['do_action'] ) ? sanitize_key( wp_unslash( $_POST['do_action'] ) ) : '';
		$edit_status     = isset( $_POST['edit_status'] ) ? sanitize_key( wp_unslash( $_POST['edit_status'] ) ) : '';
		$edit_visibility = isset( $_POST['edit_visibility'] ) ? sanitize_key( wp_unslash( $_POST['edit_visibility'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$allowed_actions = array( 'edit', 'delete' );
		if ( ! in_array( $do_action, $allowed_actions, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid action.', 'buddyboss' ) ) );
		}

		if ( empty( $raw_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No forums selected.', 'buddyboss' ) ) );
		}

		$forum_ids = array_filter( array_map( 'absint', explode( ',', $raw_ids ) ) );

		if ( empty( $forum_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No valid forum IDs provided.', 'buddyboss' ) ) );
		}

		// Cap bulk operations to prevent timeout.
		$forum_ids = array_slice( $forum_ids, 0, self::BULK_CAP );

		$processed = 0;
		$failed    = 0;

		// Prime the post cache in a single query to prevent N+1 get_post() calls.
		// _prime_post_caches() is public since WP 6.1; guard for WP 6.0 compat.
		if ( function_exists( '_prime_post_caches' ) ) {
			_prime_post_caches( $forum_ids, true, false );
		}

		// Populate legacy $_POST keys before loop — status/visibility are constant, type must be per-forum.
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		if ( 'edit' === $do_action ) {
			if ( ! empty( $edit_status ) && 'no_change' !== $edit_status ) {
				$_POST['bbp_forum_status'] = $edit_status;
			}
			if ( ! empty( $edit_visibility ) && 'no_change' !== $edit_visibility ) {
				$_POST['bbp_forum_visibility'] = $edit_visibility;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Defer term counting to avoid recalculating after each individual item.
		wp_defer_term_counting( true );

		foreach ( $forum_ids as $forum_id ) {
			$forum = get_post( $forum_id );

			if ( ! $forum || bbp_get_forum_post_type() !== $forum->post_type ) {
				++$failed;
				continue;
			}

			if ( 'delete' === $do_action ) {
				// wp_delete_post() triggers bbp_delete_forum() via `delete_post` hook.
				$result = wp_delete_post( $forum_id, true );
				if ( $result ) {
					++$processed;
				} else {
					++$failed;
				}
			} elseif ( 'edit' === $do_action ) {
				$updated = false;

				// Preserve existing forum type per-forum so bbp_save_forum_extras() doesn't overwrite it.
				$_POST['bbp_forum_type'] = bbp_get_forum_type( $forum_id ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

				// Update status (open/closed) if provided and not "no change".
				if ( ! empty( $edit_status ) && 'no_change' !== $edit_status ) {
					if ( 'closed' === $edit_status ) {
						bbp_close_forum( $forum_id );
						$updated = true;
					} elseif ( 'open' === $edit_status ) {
						bbp_open_forum( $forum_id );
						$updated = true;
					}
				}

				// Update visibility if provided and not "no change".
				if ( ! empty( $edit_visibility ) && 'no_change' !== $edit_visibility ) {
					$allowed_visibilities = array( 'publish', 'private', 'hidden' );
					if ( in_array( $edit_visibility, $allowed_visibilities, true ) ) {
						$update_result = wp_update_post(
							array(
								'ID'          => $forum_id,
								'post_status' => $edit_visibility,
							),
							true
						);

						if ( is_wp_error( $update_result ) ) {
							++$failed;
							continue;
						}

						// Propagate visibility to child forums for group forums.
						$this->bb_propagate_group_forum_visibility( $forum_id );

						$updated = true;
					}
				}

				if ( $updated ) {
					/**
					 * Fires after a forum is bulk-edited in Settings 2.0 admin.
					 *
					 * Fires the primary lifecycle hook so that bbp_update_forum()
					 * runs count recalculation (registered at core/actions.php:169).
					 *
					 * @since BuddyBoss 3.0.0
					 *
					 * @param int $forum_id Forum ID.
					 */
					do_action(
						'bbp_edit_forum',
						array(
							'forum_id'           => $forum_id,
							'post_parent'        => $forum->post_parent,
							'forum_author'       => $forum->post_author,
							'last_topic_id'      => 0,
							'last_reply_id'      => 0,
							'last_active_id'     => 0,
							'last_active_time'   => 0,
							'last_active_status' => bbp_get_public_status_id(),
						)
					);
					do_action( 'bbp_edit_forum_post_extras', $forum_id );
					++$processed;
				} else {
					++$failed;
				}
			}
		}

		// Resume deferred term counting.
		wp_defer_term_counting( false );

		// Clear status counts cache.
		$this->bb_clear_status_counts_cache();

		if ( $processed > 0 ) {
			if ( 'edit' === $do_action ) {
				$message = sprintf(
					/* translators: %d: Number of forums processed. */
					_n(
						'%d forum updated successfully.',
						'%d forums updated successfully.',
						$processed,
						'buddyboss'
					),
					$processed
				);
			} else {
				$message = sprintf(
					/* translators: %d: Number of forums processed. */
					_n(
						'%d forum deleted successfully.',
						'%d forums deleted successfully.',
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
			wp_send_json_error( array( 'message' => __( 'No forums were processed.', 'buddyboss' ) ) );
		}
	}

	/**
	 * Get forum status counts with caching.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Associative array of status => count.
	 */
	private function bb_get_status_counts() {
		$cache_key  = 'bb_admin_forums_status_counts';
		$counts_map = wp_cache_get( $cache_key, 'bbpress' );

		if ( false === $counts_map ) {
			global $wpdb;
			$forum_post_type = bbp_get_forum_post_type();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom status counts for forums, not available via core API.
			$status_counts = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_status, COUNT(ID) AS cnt FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ('publish', 'private', 'hidden') GROUP BY post_status",
					$forum_post_type
				)
			);

			$counts_map = array();
			foreach ( $status_counts as $row ) {
				$counts_map[ $row->post_status ] = (int) $row->cnt;
			}
			wp_cache_set( $cache_key, $counts_map, 'bbpress', HOUR_IN_SECONDS );
		}

		return $counts_map;
	}

	/**
	 * Get count of forums authored by the current user.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return int Count of user's forums.
	 */
	private function bb_get_mine_count() {
		$user_id   = get_current_user_id();
		$version   = (int) bp_get_option( 'bb_admin_forums_mine_count_version', 0 );
		$cache_key = 'bb_admin_forums_mine_count_' . $user_id . '_v' . $version;
		$cached    = wp_cache_get( $cache_key, 'bbpress' );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		global $wpdb;
		$forum_post_type = bbp_get_forum_post_type();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom count query for current user's forums.
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = %s AND post_author = %d AND post_status IN ('publish', 'private', 'hidden')",
				$forum_post_type,
				$user_id
			)
		);

		wp_cache_set( $cache_key, $count, 'bbpress', HOUR_IN_SECONDS );

		return $count;
	}

	/**
	 * Forum autocomplete endpoint for async select fields.
	 *
	 * Returns forums matching a search term for use in Discussion/Reply
	 * create/edit modals. Registered here so it works independently of
	 * the Groups component being active.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function bb_forum_autocomplete() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) || ! function_exists( 'bbp_get_forum_post_type' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$term        = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
		$page        = isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : 1;
		$selected_id = isset( $_POST['selected_id'] ) ? absint( wp_unslash( $_POST['selected_id'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$page     = max( 1, $page );
		$per_page = 20;

		// When only selected_id is passed (initial load to resolve label), return just that forum.
		if ( $selected_id && empty( $term ) && 1 === $page ) {
			$forum = get_post( $selected_id );
			if ( $forum && bbp_get_forum_post_type() === $forum->post_type ) {
				wp_send_json_success(
					array(
						'results'  => array(
							array(
								'value' => (string) $forum->ID,
								'label' => $forum->post_title,
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
			'post_type'              => bbp_get_forum_post_type(),
			'posts_per_page'         => $per_page + 1,
			'paged'                  => $page,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'post_status'            => array( 'publish', 'private', 'hidden' ),
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		if ( ! empty( $term ) ) {
			$query_args['s'] = $term;
		}

		$forums = get_posts( $query_args );

		$has_more = count( $forums ) > $per_page;
		if ( $has_more ) {
			array_pop( $forums );
		}

		$results = array();
		foreach ( $forums as $forum ) {
			$results[] = array(
				'value' => (string) $forum->ID,
				'label' => $forum->post_title,
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
	 * Propagate visibility changes to child forums for group forums.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param int $forum_id Forum ID.
	 *
	 * @return void
	 */
	private function bb_propagate_group_forum_visibility( $forum_id ) {
		if ( ! function_exists( 'bb_get_all_nested_subforums' ) ) {
			return;
		}

		$forum_obj = bbp_get_forum( $forum_id );

		if ( ! empty( $forum_obj->post_parent ) ) {
			$ancestors = get_post_ancestors( $forum_id );
			if ( ! empty( $ancestors ) ) {
				$parent_forum = end( $ancestors );
			} else {
				$parent_forum = $forum_id;
			}
		} else {
			$parent_forum = $forum_id;
		}

		// Only propagate for group-attached forums.
		if ( empty( $parent_forum ) || empty( bbp_get_forum_group_ids( $parent_forum ) ) ) {
			return;
		}

		$child_forums = bb_get_all_nested_subforums( $parent_forum );
		if ( ! $child_forums ) {
			return;
		}

		$parent_status = get_post_status( $parent_forum );
		foreach ( $child_forums as $child_forum_id ) {
			if ( get_post_status( $child_forum_id ) !== $parent_status ) {
				switch ( $parent_status ) {
					case bbp_get_hidden_status_id():
						bbp_hide_forum( $child_forum_id );
						break;
					case bbp_get_private_status_id():
						bbp_privatize_forum( $child_forum_id );
						break;
					case bbp_get_public_status_id():
					default:
						bbp_publicize_forum( $child_forum_id );
						break;
				}
			}
		}
	}
}

// Initialize.
new BB_Admin_Forums_Ajax();
