<?php
/**
 * BuddyBoss Email Invites Admin AJAX Handler
 *
 * Handles AJAX requests for the Email Invites list screen
 * in the Settings 2.0 admin interface.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Invites_Ajax
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Admin_Invites_Ajax {

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
	 * Maximum IDs for bulk operations.
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
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	private function bb_register_ajax_handlers() {
		add_action( 'wp_ajax_bb_admin_get_invites', array( $this, 'bb_admin_get_invites' ) );
		add_action( 'wp_ajax_bb_admin_invites_bulk_action', array( $this, 'bb_admin_invites_bulk_action' ) );
	}

	/**
	 * Verify AJAX request (nonce + capability).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	private function bb_verify_request() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );
	}

	/**
	 * Get paginated list of email invites.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function bb_admin_get_invites() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'invites' ) ) {
			wp_send_json_error( array( 'message' => __( 'Email Invites component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$page         = isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : 1;
		$per_page     = isset( $_POST['per_page'] ) ? absint( wp_unslash( $_POST['per_page'] ) ) : 20;
		$search       = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$sort         = isset( $_POST['sort'] ) ? sanitize_text_field( wp_unslash( $_POST['sort'] ) ) : 'newest';
		$filter       = isset( $_POST['filter'] ) ? sanitize_text_field( wp_unslash( $_POST['filter'] ) ) : 'all';
		$include_meta = isset( $_POST['include_meta'] ) ? absint( wp_unslash( $_POST['include_meta'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Validate sort.
		$allowed_sorts = array( 'newest', 'oldest' );
		if ( ! in_array( $sort, $allowed_sorts, true ) ) {
			$sort = 'newest';
		}

		// Validate filter.
		$allowed_filters = array( 'all', 'mine', 'published' );
		if ( ! in_array( $filter, $allowed_filters, true ) ) {
			$filter = 'all';
		}

		// Clamp per_page.
		$per_page = max( 1, min( self::PER_PAGE_CAP, $per_page ) );
		$page     = max( 1, $page );

		$post_type = bp_get_invite_post_type();

		// Build WP_Query args.
		$query_args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'newest' === $sort ? 'DESC' : 'ASC',
		);

		// Filter: mine — show only current user's invites.
		if ( 'mine' === $filter ) {
			$query_args['author'] = get_current_user_id();
		}

		// Search by invitee name or email via meta query.
		if ( ! empty( $search ) ) {
			$query_args['bb_invite_search'] = $search;
			add_filter( 'posts_join', array( $this, 'bb_search_join' ), 10, 2 );
			add_filter( 'posts_where', array( $this, 'bb_search_where' ), 10, 2 );
			add_filter( 'posts_distinct', array( $this, 'bb_search_distinct' ), 10, 2 );
		}

		$query = new WP_Query( $query_args );

		// Remove search filters.
		if ( ! empty( $search ) ) {
			remove_filter( 'posts_join', array( $this, 'bb_search_join' ), 10 );
			remove_filter( 'posts_where', array( $this, 'bb_search_where' ), 10 );
			remove_filter( 'posts_distinct', array( $this, 'bb_search_distinct' ), 10 );
		}

		$posts = $query->posts;
		$total = (int) $query->found_posts;

		// Batch prime post meta to avoid N+1.
		if ( ! empty( $posts ) ) {
			$post_ids = wp_list_pluck( $posts, 'ID' );
			update_postmeta_cache( $post_ids );
		}

		// Build items.
		$items = array();
		foreach ( $posts as $post ) {
			$author_id     = (int) $post->post_author;
			$invitee_name  = get_post_meta( $post->ID, '_bp_invitee_name', true );
			$invitee_email = get_post_meta( $post->ID, '_bp_invitee_email', true );
			$status_val    = get_post_meta( $post->ID, '_bp_invitee_status', true );
			$is_registered = '1' === $status_val;

			$items[] = array(
				'id'              => $post->ID,
				'sender_id'      => $author_id,
				'sender_name'    => bp_core_get_user_displayname( $author_id ),
				'sender_avatar'  => bp_core_fetch_avatar(
					array(
						'item_id' => $author_id,
						'type'    => 'thumb',
						'width'   => 40,
						'height'  => 40,
						'html'    => false,
					)
				),
				'sender_url'     => bp_core_get_user_domain( $author_id ),
				'recipient_name' => $invitee_name ? $invitee_name : '',
				'recipient_email' => $invitee_email ? $invitee_email : '',
				'status'         => $is_registered ? 'registered' : 'pending',
				'status_label'   => $is_registered
					? __( 'Approved', 'buddyboss' )
					: __( 'Pending', 'buddyboss' ),
				'date_invited'   => get_the_date( 'd M Y H:i', $post ),
				'can_revoke'     => ! $is_registered,
				'view_url'       => bp_core_get_user_domain( $author_id ),
				'edit_url'       => admin_url( 'user-edit.php?user_id=' . $author_id ),
			);
		}

		$response = array(
			'items' => $items,
			'total' => $total,
		);

		// Include columns, bulk actions, and views on first load (include_meta = 1).
		if ( 1 === $include_meta ) {
			$response['columns'] = array(
				'sender'          => __( 'Sender', 'buddyboss' ),
				'recipient_name'  => __( 'Recipient', 'buddyboss' ),
				'recipient_email' => __( 'Recipient Email', 'buddyboss' ),
				'status'          => __( 'Status', 'buddyboss' ),
				'date_invited'    => __( 'Date Invited', 'buddyboss' ),
			);

			$response['bulk_actions'] = array(
				'revoke' => __( 'Revoke Invite', 'buddyboss' ),
			);

			// View counts for filter dropdown.
			$response['views'] = $this->bb_get_invite_views();
		}

		/**
		 * Filters the invites list response before sending.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $response Response data.
		 * @param array $query_args WP_Query arguments used.
		 */
		$response = apply_filters( 'bb_admin_get_invites_response', $response, $query_args );

		wp_send_json_success( $response );
	}

	/**
	 * Handle bulk action on invites (revoke).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function bb_admin_invites_bulk_action() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'invites' ) ) {
			wp_send_json_error( array( 'message' => __( 'Email Invites component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$action = isset( $_POST['do_action'] ) ? sanitize_key( wp_unslash( $_POST['do_action'] ) ) : '';
		$ids    = isset( $_POST['invite_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['invite_ids'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $action ) || empty( $ids ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'buddyboss' ) ) );
		}

		$id_array = array_map( 'absint', array_filter( explode( ',', $ids ) ) );
		if ( empty( $id_array ) ) {
			wp_send_json_error( array( 'message' => __( 'No invites selected.', 'buddyboss' ) ) );
		}

		// Cap to prevent abuse.
		$id_array = array_slice( $id_array, 0, self::BULK_CAP );

		$post_type = bp_get_invite_post_type();

		if ( 'revoke' === $action ) {
			$revoked = 0;
			foreach ( $id_array as $post_id ) {
				$post = get_post( $post_id );
				if ( $post && $post_type === $post->post_type ) {
					wp_delete_post( $post_id, true );
					++$revoked;
				}
			}

			wp_send_json_success(
				array(
					'message' => sprintf(
						/* translators: %d: number of invites revoked. */
						_n(
							'%d invite has been revoked.',
							'%d invites have been revoked.',
							$revoked,
							'buddyboss'
						),
						$revoked
					),
					'revoked' => $revoked,
				)
			);
		}

		wp_send_json_error( array( 'message' => __( 'Unknown action.', 'buddyboss' ) ) );
	}

	/**
	 * Get view counts for the filter dropdown.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array View counts keyed by filter slug.
	 */
	private function bb_get_invite_views() {
		$post_type = bp_get_invite_post_type();

		// Total count.
		$all_count = (int) wp_count_posts( $post_type )->publish;

		// My invites count.
		$mine_args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'author'         => get_current_user_id(),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
		);
		$mine_query = new WP_Query( $mine_args );
		$mine_count = (int) $mine_query->found_posts;

		return array(
			'all'       => array(
				'label' => __( 'All', 'buddyboss' ),
				'count' => $all_count,
			),
			'mine'      => array(
				'label' => __( 'Mine', 'buddyboss' ),
				'count' => $mine_count,
			),
			'published' => array(
				'label' => __( 'Published', 'buddyboss' ),
				'count' => $all_count,
			),
		);
	}

	/**
	 * Add DISTINCT to invite search query.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string   $distinct The DISTINCT clause.
	 * @param WP_Query $query    The WP_Query instance.
	 *
	 * @return string Modified DISTINCT clause.
	 */
	public function bb_search_distinct( $distinct, $query ) {
		if ( ! empty( $query->query_vars['bb_invite_search'] ) ) {
			$distinct = 'DISTINCT';
		}

		return $distinct;
	}

	/**
	 * Add JOIN for invite meta search.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string   $join  The JOIN clause.
	 * @param WP_Query $query The WP_Query instance.
	 *
	 * @return string Modified JOIN clause.
	 */
	public function bb_search_join( $join, $query ) {
		global $wpdb;

		if ( ! empty( $query->query_vars['bb_invite_search'] ) ) {
			$join .= " INNER JOIN {$wpdb->postmeta} AS bb_inv_meta ON ( {$wpdb->posts}.ID = bb_inv_meta.post_id )";
		}

		return $join;
	}

	/**
	 * Add WHERE clause for invite meta search.
	 *
	 * Searches invitee name and email meta fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string   $where The WHERE clause.
	 * @param WP_Query $query The WP_Query instance.
	 *
	 * @return string Modified WHERE clause.
	 */
	public function bb_search_where( $where, $query ) {
		global $wpdb;

		$search_term = $query->query_vars['bb_invite_search'] ?? '';
		if ( empty( $search_term ) ) {
			return $where;
		}

		$like = '%' . $wpdb->esc_like( $search_term ) . '%';

		$name_condition  = $wpdb->prepare(
			"( bb_inv_meta.meta_key = '_bp_invitee_name' AND bb_inv_meta.meta_value LIKE %s )",
			$like
		);
		$email_condition = $wpdb->prepare(
			"( bb_inv_meta.meta_key = '_bp_invitee_email' AND bb_inv_meta.meta_value LIKE %s )",
			$like
		);

		$where .= " AND ( {$name_condition} OR {$email_condition} )";

		return $where;
	}
}
