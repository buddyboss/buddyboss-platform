<?php
/**
 * BuddyBoss Discussion Tags Admin AJAX Handler
 *
 * Handles AJAX requests for Discussion Tag (topic-tag taxonomy)
 * CRUD operations in the Settings 2.0 admin interface.
 *
 * @since   BuddyBoss 3.0.0
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Topic_Tags_Ajax
 *
 * @since BuddyBoss 3.0.0
 */
class BB_Admin_Topic_Tags_Ajax {

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
	 * Maximum tags for bulk operations.
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
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	private function bb_register_ajax_handlers() {
		add_action( 'wp_ajax_bb_admin_get_topic_tags', array( $this, 'get_topic_tags' ) );
		add_action( 'wp_ajax_bb_admin_get_topic_tag', array( $this, 'get_topic_tag' ) );
		add_action( 'wp_ajax_bb_admin_create_topic_tag', array( $this, 'create_topic_tag' ) );
		add_action( 'wp_ajax_bb_admin_save_topic_tag', array( $this, 'save_topic_tag' ) );
		add_action( 'wp_ajax_bb_admin_delete_topic_tag', array( $this, 'delete_topic_tag' ) );
		add_action( 'wp_ajax_bb_admin_topic_tag_bulk_action', array( $this, 'topic_tag_bulk_action' ) );
	}

	/**
	 * Get discussion tags listing with pagination and search.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function get_topic_tags() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss-platform' ) ) );
		}

		$tag_tax_id = bbp_get_topic_tag_tax_id();
		if ( empty( $tag_tax_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Topic tags are not available.', 'buddyboss-platform' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$page         = isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : 1;
		$per_page     = isset( $_POST['per_page'] ) ? absint( wp_unslash( $_POST['per_page'] ) ) : 20;
		$search       = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$include_meta = isset( $_POST['include_meta'] ) ? absint( wp_unslash( $_POST['include_meta'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Clamp per_page.
		$per_page = max( 1, min( self::PER_PAGE_CAP, $per_page ) );
		$page     = max( 1, $page );
		$offset   = ( $page - 1 ) * $per_page;

		// Build term query args.
		$query_args = array(
			'taxonomy'   => $tag_tax_id,
			'hide_empty' => false,
			'number'     => $per_page,
			'offset'     => $offset,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		if ( ! empty( $search ) ) {
			$query_args['search'] = $search;
		}

		$term_query = new WP_Term_Query( $query_args );
		$terms      = ! empty( $term_query->terms ) ? $term_query->terms : array();

		// Get total count.
		$count_args = array(
			'taxonomy'   => $tag_tax_id,
			'hide_empty' => false,
			'fields'     => 'count',
		);
		if ( ! empty( $search ) ) {
			$count_args['search'] = $search;
		}
		$total = (int) wp_count_terms( $count_args );

		$items = array();
		foreach ( $terms as $term ) {
			$permalink = get_term_link( $term, $tag_tax_id );

			$items[] = array(
				'id'          => $term->term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'description' => $term->description,
				'count'       => (int) $term->count,
				'permalink'   => ! is_wp_error( $permalink ) ? $permalink : '',
			);
		}

		$response = array(
			'tags'        => $items,
			'total'       => $total,
			'total_pages' => ceil( $total / $per_page ),
		);

		// Include metadata on first request.
		if ( $include_meta ) {
			// Total count without search filter.
			$all_count = (int) wp_count_terms(
				array(
					'taxonomy'   => $tag_tax_id,
					'hide_empty' => false,
					'fields'     => 'count',
				)
			);

			$response['views'] = array(
				'all' => $all_count,
			);

			$response['bulk_actions'] = array(
				'delete' => __( 'Delete', 'buddyboss-platform' ),
			);

			$response['columns'] = array(
				'name'  => __( 'Tag', 'buddyboss-platform' ),
				'slug'  => __( 'Slug', 'buddyboss-platform' ),
				'count' => __( 'Count', 'buddyboss-platform' ),
			);
		}

		/**
		 * Filters the full response data for the admin topic tags list AJAX endpoint.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param array $response Response data array.
		 */
		$response = apply_filters( 'bb_admin_get_topic_tags_response', $response );

		wp_send_json_success( $response );
	}

	/**
	 * Get a single discussion tag for the edit modal.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function get_topic_tag() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss-platform' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$term_id = isset( $_POST['term_id'] ) ? absint( wp_unslash( $_POST['term_id'] ) ) : 0;

		if ( empty( $term_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Tag ID is required.', 'buddyboss-platform' ) ) );
		}

		$tag_tax_id = bbp_get_topic_tag_tax_id();
		$term       = get_term( $term_id, $tag_tax_id );

		if ( ! $term || is_wp_error( $term ) ) {
			wp_send_json_error( array( 'message' => __( 'Tag not found.', 'buddyboss-platform' ) ) );
		}

		$permalink = get_term_link( $term, $tag_tax_id );

		$data = array(
			'id'          => $term->term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'description' => $term->description,
			'count'       => (int) $term->count,
			'permalink'   => ! is_wp_error( $permalink ) ? $permalink : '',
		);

		/**
		 * Filters the response data for the admin single topic tag endpoint.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param array   $data Response data array.
		 * @param WP_Term $term The term object.
		 */
		wp_send_json_success( apply_filters( 'bb_admin_get_topic_tag_response', $data, $term ) );
	}

	/**
	 * Create a new discussion tag.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function create_topic_tag() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss-platform' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$slug        = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Tag name is required.', 'buddyboss-platform' ) ) );
		}

		$tag_tax_id = bbp_get_topic_tag_tax_id();

		$args = array();
		if ( ! empty( $slug ) ) {
			$args['slug'] = $slug;
		}
		if ( ! empty( $description ) ) {
			$args['description'] = $description;
		}

		$result = wp_insert_term( $name, $tag_tax_id, $args );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => wp_strip_all_tags( $result->get_error_message() ) ) );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Tag created successfully.', 'buddyboss-platform' ),
				'term_id' => $result['term_id'],
			)
		);
	}

	/**
	 * Update an existing discussion tag.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function save_topic_tag() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss-platform' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$term_id     = isset( $_POST['term_id'] ) ? absint( wp_unslash( $_POST['term_id'] ) ) : 0;
		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$slug        = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $term_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Tag ID is required.', 'buddyboss-platform' ) ) );
		}

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Tag name is required.', 'buddyboss-platform' ) ) );
		}

		$tag_tax_id = bbp_get_topic_tag_tax_id();
		$term       = get_term( $term_id, $tag_tax_id );

		if ( ! $term || is_wp_error( $term ) ) {
			wp_send_json_error( array( 'message' => __( 'Tag not found.', 'buddyboss-platform' ) ) );
		}

		$args = array(
			'name'        => $name,
			'description' => $description,
		);

		if ( ! empty( $slug ) ) {
			$args['slug'] = $slug;
		}

		$result = wp_update_term( $term_id, $tag_tax_id, $args );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => wp_strip_all_tags( $result->get_error_message() ) ) );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Tag updated successfully.', 'buddyboss-platform' ),
				'term_id' => $result['term_id'],
			)
		);
	}

	/**
	 * Delete a single discussion tag.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function delete_topic_tag() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss-platform' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$term_id = isset( $_POST['term_id'] ) ? absint( wp_unslash( $_POST['term_id'] ) ) : 0;

		if ( empty( $term_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Tag ID is required.', 'buddyboss-platform' ) ) );
		}

		$tag_tax_id = bbp_get_topic_tag_tax_id();
		$term       = get_term( $term_id, $tag_tax_id );

		if ( ! $term || is_wp_error( $term ) ) {
			wp_send_json_error( array( 'message' => __( 'Tag not found.', 'buddyboss-platform' ) ) );
		}

		$result = wp_delete_term( $term_id, $tag_tax_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => wp_strip_all_tags( $result->get_error_message() ) ) );
		}

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete tag.', 'buddyboss-platform' ) ) );
		}

		wp_send_json_success(
			array( 'message' => __( 'Tag deleted successfully.', 'buddyboss-platform' ) )
		);
	}

	/**
	 * Perform bulk action on discussion tags.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function topic_tag_bulk_action() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss-platform' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$raw_ids   = isset( $_POST['term_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['term_ids'] ) ) : '';
		$do_action = isset( $_POST['do_action'] ) ? sanitize_key( wp_unslash( $_POST['do_action'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( 'delete' !== $do_action ) {
			wp_send_json_error( array( 'message' => __( 'Invalid action.', 'buddyboss-platform' ) ) );
		}

		if ( empty( $raw_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No tags selected.', 'buddyboss-platform' ) ) );
		}

		$term_ids = array_filter( array_map( 'absint', explode( ',', $raw_ids ) ) );

		if ( empty( $term_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No valid tag IDs provided.', 'buddyboss-platform' ) ) );
		}

		// Cap bulk operations.
		$term_ids = array_slice( $term_ids, 0, self::BULK_CAP );

		$tag_tax_id = bbp_get_topic_tag_tax_id();
		$processed  = 0;
		$failed     = 0;

		// Prime the term cache in a single query to prevent N+1 get_term() calls.
		// _prime_term_caches() is available since WP 6.2; guard for WP 6.0 compat.
		if ( function_exists( '_prime_term_caches' ) ) {
			_prime_term_caches( $term_ids );
		}

		foreach ( $term_ids as $term_id ) {
			$term = get_term( $term_id, $tag_tax_id );
			if ( ! $term || is_wp_error( $term ) ) {
				++$failed;
				continue;
			}

			$result = wp_delete_term( $term_id, $tag_tax_id );
			if ( $result && ! is_wp_error( $result ) ) {
				++$processed;
			} else {
				++$failed;
			}
		}

		if ( $processed > 0 ) {
			$message = sprintf(
				/* translators: %d: Number of tags deleted. */
				_n(
					'%d tag deleted successfully.',
					'%d tags deleted successfully.',
					$processed,
					'buddyboss-platform'
				),
				$processed
			);

			wp_send_json_success(
				array(
					'message'   => $message,
					'processed' => $processed,
					'failed'    => $failed,
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'No tags were deleted.', 'buddyboss-platform' ) ) );
		}
	}
}

// Initialize.
new BB_Admin_Topic_Tags_Ajax();
