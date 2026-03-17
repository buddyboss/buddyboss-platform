<?php
/**
 * BuddyBoss Email Templates Admin AJAX Handler
 *
 * Handles AJAX requests for Email Templates list operations
 * in the Settings 2.0 admin interface.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Email_Templates_Admin_Ajax
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Email_Templates_Admin_Ajax {

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
	 * Constructor.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		$this->bb_register_ajax_handlers();
	}

	/**
	 * Maximum items for bulk operations.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var int
	 */
	const BULK_CAP = 100;

	/**
	 * Register AJAX handlers.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function bb_register_ajax_handlers() {
		add_action( 'wp_ajax_bb_admin_get_email_templates', array( $this, 'get_email_templates' ) );
		add_action( 'wp_ajax_bb_admin_email_template_bulk_action', array( $this, 'email_template_bulk_action' ) );
	}

	/**
	 * Get email templates listing with pagination, search, and sorting.
	 *
	 * Uses WP_Query on the bp-email post type. Returns email templates
	 * with title, description (from taxonomy term), status, and date.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function get_email_templates() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$page         = isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : 1;
		$per_page     = isset( $_POST['per_page'] ) ? absint( wp_unslash( $_POST['per_page'] ) ) : 20;
		$search       = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
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
			'post_type'      => bp_get_email_post_type(),
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_status'    => 'any',
		);

		// Search by title.
		if ( ! empty( $search ) ) {
			$query_args['s'] = $search;
		}

		// Sort mapping.
		if ( 'oldest' === $sort ) {
			$query_args['orderby'] = 'date';
			$query_args['order']   = 'ASC';
		} else {
			$query_args['orderby'] = 'date';
			$query_args['order']   = 'DESC';
		}

		$query = new WP_Query( $query_args );
		$posts = $query->posts;
		$total = (int) $query->found_posts;

		// Pre-fetch email type taxonomy terms for all posts to avoid N+1 queries.
		$post_ids      = wp_list_pluck( $posts, 'ID' );
		$taxonomy      = bp_get_email_tax_type();
		$term_cache    = array();

		if ( ! empty( $post_ids ) ) {
			// Prime the term cache for all posts at once.
			update_object_term_cache( $post_ids, bp_get_email_post_type() );

			foreach ( $post_ids as $post_id ) {
				$terms = get_the_terms( $post_id, $taxonomy );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					$term_cache[ $post_id ] = $terms[0];
				}
			}
		}

		$items = array();
		foreach ( $posts as $email_post ) {
			$email_id    = $email_post->ID;
			$term        = isset( $term_cache[ $email_id ] ) ? $term_cache[ $email_id ] : null;
			$description = $term ? $term->description : '';
			$email_type  = $term ? $term->slug : '';

			$items[] = array(
				'id'          => $email_id,
				'title'       => $email_post->post_title,
				'description' => $description,
				'status'      => get_post_status( $email_id ),
				'date'        => get_the_date( 'j M, H:i:s', $email_id ),
				'edit_url'    => get_edit_post_link( $email_id, 'raw' ),
				'email_type'  => $email_type,
			);
		}

		$response = array(
			'items'       => $items,
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
		);

		// Include metadata on first request.
		if ( $include_meta ) {
			$response['add_new_url']   = admin_url( 'post-new.php?post_type=' . bp_get_email_post_type() );
			$response['bulk_actions']  = array(
				'trash' => __( 'Move to Trash', 'buddyboss' ),
			);
		}

		/**
		 * Filters the full response data for the admin email templates list AJAX endpoint.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $response Response data array.
		 */
		$response = apply_filters( 'bb_admin_get_email_templates_response', $response );

		wp_send_json_success( $response );
	}
	/**
	 * Perform bulk action on email templates.
	 *
	 * Supports 'trash' action matching the legacy WP list table behavior.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function email_template_bulk_action() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_admin_verify_ajax_request() above.
		$raw_ids   = isset( $_POST['email_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['email_ids'] ) ) : '';
		$do_action = isset( $_POST['do_action'] ) ? sanitize_key( wp_unslash( $_POST['do_action'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$allowed_actions = array( 'trash' );
		if ( ! in_array( $do_action, $allowed_actions, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid action.', 'buddyboss' ) ) );
		}

		if ( empty( $raw_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No email templates selected.', 'buddyboss' ) ) );
		}

		$email_ids = array_filter( array_map( 'absint', explode( ',', $raw_ids ) ) );

		if ( empty( $email_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No valid email template IDs provided.', 'buddyboss' ) ) );
		}

		// Cap bulk operations to prevent timeout.
		$email_ids = array_slice( $email_ids, 0, self::BULK_CAP );

		$email_post_type = bp_get_email_post_type();
		$processed       = 0;
		$failed          = 0;

		foreach ( $email_ids as $email_id ) {
			$post = get_post( $email_id );

			if ( ! $post || $email_post_type !== $post->post_type ) {
				++$failed;
				continue;
			}

			if ( 'trash' === $do_action ) {
				$result = wp_trash_post( $email_id );
				if ( $result ) {
					++$processed;
				} else {
					++$failed;
				}
			}
		}

		if ( $processed > 0 ) {
			$message = sprintf(
				/* translators: %d: Number of email templates processed. */
				_n(
					'%d email template moved to trash.',
					'%d email templates moved to trash.',
					$processed,
					'buddyboss'
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
			wp_send_json_error( array( 'message' => __( 'No email templates were processed.', 'buddyboss' ) ) );
		}
	}
}

// Initialize.
new BB_Email_Templates_Admin_Ajax();
