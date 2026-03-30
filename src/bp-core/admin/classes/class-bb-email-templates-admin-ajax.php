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
		add_action( 'wp_ajax_bb_admin_get_email_template', array( $this, 'bb_admin_get_email_template' ) );
		add_action( 'wp_ajax_bb_admin_save_email_template', array( $this, 'bb_admin_save_email_template' ) );
		add_action( 'wp_ajax_bb_admin_delete_email_templates', array( $this, 'bb_admin_delete_email_templates' ) );
		add_action( 'wp_ajax_bb_admin_bulk_edit_email_templates', array( $this, 'bb_admin_bulk_edit_email_templates' ) );
		add_action( 'wp_ajax_bb_admin_get_email_situations', array( $this, 'bb_admin_get_email_situations' ) );
		add_action( 'wp_ajax_bb_admin_get_email_meta_keys', array( $this, 'bb_admin_get_email_meta_keys' ) );
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
		$page          = isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : 1;
		$per_page      = isset( $_POST['per_page'] ) ? absint( wp_unslash( $_POST['per_page'] ) ) : 20;
		$search        = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$sort          = isset( $_POST['sort'] ) ? sanitize_key( wp_unslash( $_POST['sort'] ) ) : 'newest';
		$status_filter = isset( $_POST['status_filter'] ) ? sanitize_key( wp_unslash( $_POST['status_filter'] ) ) : 'all';
		$include_meta  = isset( $_POST['include_meta'] ) ? absint( wp_unslash( $_POST['include_meta'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Validate sort.
		$allowed_sorts = array( 'newest', 'oldest', 'last_modified' );
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

		// Apply status filter.
		$allowed_filters = array( 'publish', 'draft', 'pending', 'private', 'future' );
		if ( 'all' !== $status_filter && in_array( $status_filter, $allowed_filters, true ) ) {
			$query_args['post_status'] = $status_filter;
		}

		// Search by title.
		if ( ! empty( $search ) ) {
			$query_args['s'] = $search;
		}

		// Sort mapping.
		if ( 'oldest' === $sort ) {
			$query_args['orderby'] = 'date';
			$query_args['order']   = 'ASC';
		} elseif ( 'last_modified' === $sort ) {
			$query_args['orderby'] = 'modified';
			$query_args['order']   = 'DESC';
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

		// Get columns via filter so third-party plugins (WPML, Polylang, etc.) can add custom columns.
		$all_columns = apply_filters(
			'bb_admin_email_templates_column_headers',
			array(
				'cb'          => '<input type="checkbox" />',
				'title'       => __( 'Title', 'buddyboss' ),
				'description' => __( 'Situations', 'buddyboss' ),
				'date'        => __( 'Published', 'buddyboss' ),
			)
		);

		// Identify custom columns (added by third-party plugins).
		$core_columns   = array( 'cb', 'title', 'description', 'date' );
		$custom_columns = array();
		foreach ( $all_columns as $col_key => $col_label ) {
			if ( ! in_array( $col_key, $core_columns, true ) ) {
				$custom_columns[ $col_key ] = $col_label;
			}
		}

		// Buffer output to capture stray HTML from legacy filters.
		ob_start();

		$status_labels_map = array(
			'future'  => __( 'Scheduled', 'buddyboss' ),
			'pending' => __( 'Pending Review', 'buddyboss' ),
			'draft'   => __( 'Draft', 'buddyboss' ),
			'private' => __( 'Private', 'buddyboss' ),
		);

		$items = array();
		foreach ( $posts as $email_post ) {
			$email_id    = $email_post->ID;
			$term        = isset( $term_cache[ $email_id ] ) ? $term_cache[ $email_id ] : null;
			$description = $term ? $term->description : '';
			$email_type  = $term ? $term->slug : '';

			// Build status label for non-published items.
			$email_post_status = $email_post->post_status;
			$status_label      = '';
			if ( isset( $status_labels_map[ $email_post_status ] ) ) {
				$status_label = $status_labels_map[ $email_post_status ];
			}

			$item = array(
				'id'           => $email_id,
				'title'        => $email_post->post_title,
				'description'  => $description,
				'status'       => $email_post_status,
				'status_label' => $status_label,
				'post_status'  => $email_post_status,
				'date'         => get_the_date( 'j M, H:i:s', $email_id ),
				'email_type'   => $email_type,
				'permalink'    => get_permalink( $email_id ),
			);

			// Render custom columns via filter (e.g., WPML language flags).
			if ( ! empty( $custom_columns ) ) {
				$item['custom_columns'] = array();
				foreach ( $custom_columns as $col_key => $col_label ) {
					ob_start();
					/**
					 * Fires for custom column data rendering in the email templates admin list.
					 *
					 * Third-party plugins (WPML, Polylang, etc.) can use this to add
					 * translation flags, status icons, or other column content.
					 *
					 * @since BuddyBoss [BBVERSION]
					 *
					 * @param string  $col_key    Column key.
					 * @param int     $email_id   Email template post ID.
					 * @param WP_Post $email_post Email template post object.
					 */
					do_action( 'bb_admin_email_templates_column_data', $col_key, $email_id, $email_post );
					$item['custom_columns'][ $col_key ] = wp_kses_post( ob_get_clean() );
				}
			}

			$items[] = $item;
		}

		// End output buffer.
		ob_end_clean();

		// Status counts for filter dropdown.
		$email_post_type_name = bp_get_email_post_type();
		$status_counts        = wp_count_posts( $email_post_type_name );

		$response = array(
			'items'       => $items,
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'views'       => array(
				'all'     => ( isset( $status_counts->publish ) ? (int) $status_counts->publish : 0 )
							+ ( isset( $status_counts->draft ) ? (int) $status_counts->draft : 0 )
							+ ( isset( $status_counts->pending ) ? (int) $status_counts->pending : 0 )
							+ ( isset( $status_counts->future ) ? (int) $status_counts->future : 0 )
							+ ( isset( $status_counts->private ) ? (int) $status_counts->private : 0 ),
				'publish' => isset( $status_counts->publish ) ? (int) $status_counts->publish : 0,
				'draft'   => isset( $status_counts->draft ) ? (int) $status_counts->draft : 0,
			),
		);

		// Include metadata on first request.
		if ( $include_meta ) {
			$response['bulk_actions']  = array(
				'trash' => __( 'Move to Trash', 'buddyboss' ),
			);

			// Provide registered field definitions for the create modal.
			$response['create_fields'] = bb_admin_meta_field_registry()->get_fields_data(
				'emails',
				(object) array(
					'ID'            => 0,
					'post_title'    => '',
					'post_content'  => '',
					'post_excerpt'  => '',
					'post_status'   => 'publish',
					'post_password' => '',
					'post_date'     => '',
					'post_date_gmt' => '',
				)
			);

			// Return column definitions (excluding cb) so React can render custom column headers.
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

		// Prime post cache to avoid N+1 queries in the loop.
		if ( function_exists( '_prime_post_caches' ) ) {
			_prime_post_caches( $email_ids, false, false );
		}

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

	/**
	 * Get a single email template for the edit modal.
	 *
	 * Uses BB_Admin_Meta_Field_Registry to provide registered_fields,
	 * following the same pattern as Groups and Activity edit modals.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function bb_admin_get_email_template() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$email_id = isset( $_POST['email_id'] ) ? absint( wp_unslash( $_POST['email_id'] ) ) : 0;

		if ( empty( $email_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid email template ID.', 'buddyboss' ) ) );
		}

		$post = get_post( $email_id );
		if ( ! $post || bp_get_email_post_type() !== $post->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Email template not found.', 'buddyboss' ) ) );
		}

		// Get custom meta (exclude internal WP/BP meta keys).
		$all_meta    = get_post_meta( $email_id );
		$custom_meta = array();
		foreach ( $all_meta as $key => $values ) {
			if ( 0 === strpos( $key, '_' ) || 'bp_email_preheader' === $key ) {
				continue;
			}
			$custom_meta[] = array(
				'key'   => $key,
				'value' => $values[0],
			);
		}

		$response = array(
			'id'                => $email_id,
			'registered_fields' => bb_admin_meta_field_registry()->get_fields_data( 'emails', $post ),
			'custom_meta'       => $custom_meta,
		);

		/**
		 * Filters the single email template response.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array   $response Response data.
		 * @param WP_Post $post     Email template post object.
		 */
		$response = apply_filters( 'bb_admin_get_email_template_response', $response, $post );

		wp_send_json_success( $response );
	}

	/**
	 * Create or update an email template.
	 *
	 * Uses BB_Admin_Meta_Field_Registry for field handling, following
	 * the same pattern as Groups and Activity save handlers.
	 * Uses wp_insert_post / wp_update_post to preserve save_post_bp-email hook.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function bb_admin_save_email_template() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$email_id = isset( $_POST['email_id'] ) ? absint( wp_unslash( $_POST['email_id'] ) ) : 0;
		$raw_meta = isset( $_POST['custom_meta'] ) ? wp_unslash( $_POST['custom_meta'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per-field below.
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$registry       = bb_admin_meta_field_registry();
		$email_post_type = bp_get_email_post_type();

		if ( $email_id > 0 ) {
			// Update existing — load post as object for registry to modify.
			$post = get_post( $email_id );
			if ( ! $post || $email_post_type !== $post->post_type ) {
				wp_send_json_error( array( 'message' => __( 'Email template not found.', 'buddyboss' ) ) );
			}
		} else {
			// Create new — build a stub post object for registry to populate.
			$post                = new stdClass();
			$post->ID            = 0;
			$post->post_type     = $email_post_type;
			$post->post_title    = '';
			$post->post_content  = '';
			$post->post_excerpt  = '';
			$post->post_status   = 'publish';
			$post->post_password = '';
			$post->post_date     = '';
			$post->post_date_gmt = '';
		}

		// Apply "before" phase — sets properties on $post object from $_POST[registered_field_*].
		$registry->save_fields_data( 'emails', $post, 'before' );

		// Validate title.
		if ( empty( $post->post_title ) ) {
			wp_send_json_error( array( 'message' => __( 'Title is required.', 'buddyboss' ) ) );
		}

		// Build post data array for wp_insert_post / wp_update_post.
		$post_data = array(
			'post_type'     => $email_post_type,
			'post_title'    => $post->post_title,
			'post_content'  => $post->post_content,
			'post_excerpt'  => $post->post_excerpt,
			'post_status'   => $post->post_status,
			'post_password' => $post->post_password,
		);

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

		if ( 'schedule' === $publish_mode && ! empty( $schedule_date ) ) {
			$time_part                  = ! empty( $schedule_time ) ? $schedule_time . ':00' : '00:00:00';
			$scheduled_datetime         = $schedule_date . ' ' . $time_part;
			$post_data['post_date']     = $scheduled_datetime;
			$post_data['post_date_gmt'] = get_gmt_from_date( $scheduled_datetime );
			$post_data['post_status']   = 'future';
		} elseif ( 'immediately' === $publish_mode && $email_id > 0 && 'future' === get_post_status( $email_id ) ) {
			// Switching from scheduled back to immediately — publish now.
			$post_data['post_date']     = current_time( 'mysql' );
			$post_data['post_date_gmt'] = current_time( 'mysql', true );
			$post_data['edit_date']     = true;
		}

		if ( $email_id > 0 ) {
			$post_data['ID'] = $email_id;
			$result          = wp_update_post( $post_data, true );
		} else {
			$result = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$saved_id   = absint( $result );
		$saved_post = get_post( $saved_id );

		if ( ! $saved_post ) {
			wp_send_json_error( array( 'message' => __( 'Failed to retrieve saved email template.', 'buddyboss' ) ) );
		}

		// Apply "after" phase — saves taxonomy terms, post meta, etc.
		$registry->save_fields_data( 'emails', $saved_post, 'after' );

		// Handle custom meta — save new/updated, delete removed.
		$existing_meta = get_post_meta( $saved_id );
		$new_keys      = array();

		if ( is_array( $raw_meta ) ) {
			foreach ( $raw_meta as $meta_item ) {
				if ( ! is_array( $meta_item ) || empty( $meta_item['key'] ) ) {
					continue;
				}
				$meta_key   = sanitize_key( $meta_item['key'] );
				$meta_value = sanitize_text_field( isset( $meta_item['value'] ) ? $meta_item['value'] : '' );

				if ( 0 === strpos( $meta_key, '_' ) || 'bp_email_preheader' === $meta_key ) {
					continue;
				}

				update_post_meta( $saved_id, $meta_key, $meta_value );
				$new_keys[] = $meta_key;
			}
		}

		// Delete removed custom meta.
		foreach ( $existing_meta as $key => $values ) {
			if ( 0 === strpos( $key, '_' ) || 'bp_email_preheader' === $key ) {
				continue;
			}
			if ( ! in_array( $key, $new_keys, true ) ) {
				delete_post_meta( $saved_id, $key );
			}
		}

		/**
		 * Fires after an email template is saved via Settings 2.0.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int      $saved_id  Saved post ID.
		 * @param WP_Post  $saved_post Saved post object.
		 */
		do_action( 'bb_admin_save_email_template_after', $saved_id, $saved_post );

		$message = $email_id > 0
			? __( 'Email template updated successfully.', 'buddyboss' )
			: __( 'Email template created successfully.', 'buddyboss' );

		wp_send_json_success(
			array(
				'message' => $message,
				'post_id' => $saved_id,
			)
		);
	}

	/**
	 * Permanently delete email templates.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function bb_admin_delete_email_templates() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$raw_ids = isset( $_POST['email_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['email_ids'] ) ) : '';

		if ( empty( $raw_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No email templates selected.', 'buddyboss' ) ) );
		}

		$email_ids       = array_filter( array_map( 'absint', explode( ',', $raw_ids ) ) );
		$email_ids       = array_slice( $email_ids, 0, self::BULK_CAP );
		$email_post_type = bp_get_email_post_type();
		$deleted         = 0;

		// Prime post cache to avoid N+1 queries in the loop.
		if ( function_exists( '_prime_post_caches' ) ) {
			_prime_post_caches( $email_ids, false, false );
		}

		foreach ( $email_ids as $email_id ) {
			$post = get_post( $email_id );
			if ( ! $post || $email_post_type !== $post->post_type ) {
				continue;
			}

			$result = wp_delete_post( $email_id, true );
			if ( $result ) {
				++$deleted;
			}
		}

		if ( 0 === $deleted ) {
			wp_send_json_error( array( 'message' => __( 'No email templates were deleted.', 'buddyboss' ) ) );
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: Number of email templates deleted. */
					_n(
						'%d email template permanently deleted.',
						'%d email templates permanently deleted.',
						$deleted,
						'buddyboss'
					),
					$deleted
				),
				'deleted' => $deleted,
			)
		);
	}

	/**
	 * Bulk edit email templates (status and/or situation).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function bb_admin_bulk_edit_email_templates() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$raw_ids    = isset( $_POST['email_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['email_ids'] ) ) : '';
		$new_status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$email_type = isset( $_POST['email_type'] ) ? sanitize_key( wp_unslash( $_POST['email_type'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $raw_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No email templates selected.', 'buddyboss' ) ) );
		}

		$email_ids       = array_filter( array_map( 'absint', explode( ',', $raw_ids ) ) );
		$email_ids       = array_slice( $email_ids, 0, self::BULK_CAP );
		$email_post_type = bp_get_email_post_type();

		// Validate status if provided.
		$allowed_statuses = array( 'publish', 'draft', 'pending', 'private' );
		if ( ! empty( $new_status ) && ! in_array( $new_status, $allowed_statuses, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid status.', 'buddyboss' ) ) );
		}

		$updated = 0;

		// Prime post cache to avoid N+1 queries in the loop.
		if ( function_exists( '_prime_post_caches' ) ) {
			_prime_post_caches( $email_ids, false, false );
		}

		foreach ( $email_ids as $email_id ) {
			$post = get_post( $email_id );
			if ( ! $post || $email_post_type !== $post->post_type ) {
				continue;
			}

			// Update status if provided.
			$has_update = false;
			if ( ! empty( $new_status ) ) {
				$result = wp_update_post(
					array(
						'ID'          => $email_id,
						'post_status' => $new_status,
					),
					true
				);
				if ( ! is_wp_error( $result ) ) {
					$has_update = true;
				}
			}

			// Update situation if provided.
			if ( ! empty( $email_type ) ) {
				$term_result = wp_set_object_terms( $email_id, $email_type, bp_get_email_tax_type() );
				if ( ! is_wp_error( $term_result ) ) {
					$has_update = true;
				}
			}

			if ( $has_update ) {
				++$updated;
			}
		}

		if ( 0 === $updated ) {
			wp_send_json_error( array( 'message' => __( 'No email templates were updated.', 'buddyboss' ) ) );
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: Number of email templates updated. */
					_n(
						'%d email template updated.',
						'%d email templates updated.',
						$updated,
						'buddyboss'
					),
					$updated
				),
				'updated' => $updated,
			)
		);
	}

	/**
	 * Get all email situations (taxonomy terms) grouped by category.
	 *
	 * Categories are derived from term slug prefixes.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function bb_admin_get_email_situations() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		$taxonomy = bp_get_email_tax_type();
		$terms    = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			wp_send_json_error( array( 'message' => $terms->get_error_message() ) );
		}

		// Category mapping by slug prefix.
		$prefix_map = array(
			'activity'      => 'activity',
			'groups'        => 'groups_discussions',
			'group-message' => 'groups_discussions',
			'bbp'           => 'groups_discussions',
			'messages'      => 'messages',
			'friends'       => 'connections',
			'core-user'     => 'account',
			'settings'      => 'account',
			'invites'       => 'account',
			'content'       => 'account',
			'user'          => 'account',
		);

		$category_labels = array(
			'activity'            => __( 'Activity', 'buddyboss' ),
			'groups_discussions'  => __( 'Groups & Discussions', 'buddyboss' ),
			'messages'            => __( 'Messages', 'buddyboss' ),
			'connections'         => __( 'Connections', 'buddyboss' ),
			'account'             => __( 'Account', 'buddyboss' ),
		);

		$grouped = array();
		foreach ( $category_labels as $cat_key => $cat_label ) {
			$grouped[ $cat_key ] = array(
				'label' => $cat_label,
				'terms' => array(),
			);
		}

		foreach ( $terms as $term ) {
			$category = 'account'; // Default fallback.

			// Match longest prefix first (e.g., 'group-message' before 'group').
			$matched_len = 0;
			foreach ( $prefix_map as $prefix => $cat ) {
				if ( 0 === strpos( $term->slug, $prefix ) && strlen( $prefix ) > $matched_len ) {
					$category    = $cat;
					$matched_len = strlen( $prefix );
				}
			}

			$grouped[ $category ]['terms'][] = array(
				'slug'        => $term->slug,
				'description' => $term->description,
			);
		}

		// Remove empty categories.
		foreach ( $grouped as $cat_key => $cat_data ) {
			if ( empty( $cat_data['terms'] ) ) {
				unset( $grouped[ $cat_key ] );
			}
		}

		/**
		 * Filters the email situations response.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $grouped Grouped situations by category.
		 */
		$grouped = apply_filters( 'bb_admin_email_situations_response', $grouped );

		wp_send_json_success( $grouped );
	}

	/**
	 * Get distinct post meta keys used across bp-email posts.
	 *
	 * Returns a filtered, sorted list for the custom field name autocomplete.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function bb_admin_get_email_meta_keys() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );

		global $wpdb;

		// Get distinct public meta keys across all posts (matching legacy Custom Fields metabox behavior).
		// Excludes internal WP meta (prefixed with _) and known BP meta.
		$keys = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT meta_key
				FROM {$wpdb->postmeta}
				WHERE meta_key NOT LIKE %s
				AND meta_key != 'bp_email_preheader'
				ORDER BY meta_key ASC
				LIMIT 200",
				$wpdb->esc_like( '_' ) . '%'
			)
		);

		wp_send_json_success( ! empty( $keys ) ? $keys : array() );
	}
}

// Initialize.
new BB_Email_Templates_Admin_Ajax();
