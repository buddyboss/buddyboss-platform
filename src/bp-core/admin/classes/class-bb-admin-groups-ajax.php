<?php
/**
 * BuddyBoss Groups Admin AJAX Handler
 *
 * Handles AJAX requests for Group Types CRUD and platform settings
 * in the Settings 2.0 admin interface.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Groups_Ajax
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Admin_Groups_Ajax {

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
	 * Maximum groups for bulk operations.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var int
	 */
	const BULK_CAP = 100;

	/**
	 * Maximum items for non-paginated list queries (group types, global topics, etc.).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var int
	 */
	const LIST_CAP = 500;

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		$this->bb_register_ajax_handlers();

		// Invalidate admin status counts cache when group data changes.
		$clear_counts = array( $this, 'bb_clear_status_counts_cache' );
		add_action( 'groups_delete_group', $clear_counts );
		add_action( 'groups_create_group', $clear_counts );
		add_action( 'groups_settings_updated', $clear_counts );

		// Fire the deprecated hook only on relevant admin pages (non-AJAX) to
		// preserve backward compatibility with plugins hooking bp_groups_admin_load.
		// The legacy hook only fired on the groups admin screen, so we guard it
		// to avoid unexpected side effects on unrelated admin pages.
		if ( ! wp_doing_ajax() ) {
			$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'bp-groups' === $page || 'bb-settings' === $page ) {
				/**
				 * Fires when the Groups admin page is loaded. Deprecated in Settings 2.0.
				 *
				 * Settings 2.0 uses AJAX-driven React UI — there is no direct replacement
				 * for this page-load hook. Plugins that registered metaboxes via this hook
				 * should use {@see 'bp_groups_admin_meta_boxes'} instead.
				 *
				 * @since BuddyPress 1.7.0
				 * @deprecated BuddyBoss [BBVERSION] No direct replacement. Use AJAX-based hooks instead.
				 */
				do_action_deprecated( 'bp_groups_admin_load', array( '' ), 'BuddyBoss [BBVERSION]' );
			}
		}
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function bb_register_ajax_handlers() {
		add_action( 'wp_ajax_bb_admin_get_group_types', array( $this, 'get_group_types' ) );
		add_action( 'wp_ajax_bb_admin_create_group_type', array( $this, 'create_group_type' ) );
		add_action( 'wp_ajax_bb_admin_update_group_type', array( $this, 'update_group_type' ) );
		add_action( 'wp_ajax_bb_admin_delete_group_type', array( $this, 'delete_group_type' ) );
		add_action( 'wp_ajax_bb_admin_get_platform_settings', array( $this, 'get_platform_settings' ) );
		add_action( 'wp_ajax_bb_admin_save_platform_setting', array( $this, 'save_platform_setting' ) );
		add_action( 'wp_ajax_bb_admin_get_groups', array( $this, 'get_groups' ) );
		add_action( 'wp_ajax_bb_admin_delete_group', array( $this, 'delete_group' ) );
		add_action( 'wp_ajax_bb_admin_group_bulk_action', array( $this, 'group_bulk_action' ) );
		add_action( 'wp_ajax_bb_admin_create_group', array( $this, 'create_group' ) );
		add_action( 'wp_ajax_bb_admin_get_group', array( $this, 'get_group' ) );
		add_action( 'wp_ajax_bb_admin_save_group', array( $this, 'save_group' ) );
		add_action( 'wp_ajax_bb_admin_get_group_members', array( $this, 'get_group_members' ) );
		add_action( 'wp_ajax_bb_admin_update_group_member', array( $this, 'update_group_member' ) );
		add_action( 'wp_ajax_bb_admin_member_autocomplete', array( $this, 'member_autocomplete' ) );
		add_action( 'wp_ajax_bb_admin_forum_autocomplete', array( $this, 'forum_autocomplete' ) );
		add_action( 'wp_ajax_bb_admin_get_group_topics', array( $this, 'get_group_topics' ) );
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
	 * Check whether a group has at least one admin other than the given user.
	 *
	 * Used to prevent removal or demotion of the last group administrator,
	 * mirroring the legacy bp-groups-admin.php no_admins guard.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $group_id Group ID.
	 * @param int $exclude_user_id User ID to exclude from the admin count.
	 *
	 * @return bool True if at least one other admin exists, false if not.
	 */
	private function bb_group_has_other_admins( $group_id, $exclude_user_id ) {
		$admins = groups_get_group_admins( $group_id );
		foreach ( $admins as $admin ) {
			if ( (int) $admin->user_id !== (int) $exclude_user_id ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get allowed platform settings options map.
	 *
	 * Returns an associative array of option_name => sanitize_callback.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array
	 */
	private function bb_get_allowed_platform_options() {
		/**
		 * Filters the allowed platform settings options for AJAX read/write.
		 *
		 * Keys are option names; values are the sanitize callback to use when saving.
		 * Use 'absint' for toggle/integer options, 'sanitize_text_field' for strings, etc.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $allowed_options Associative array of option_name => sanitize_callback.
		 */
		$options = apply_filters(
			'bb_admin_allowed_platform_settings',
			array(
				'bp-disable-group-type-creation' => 'absint',
				'bp-enable-group-auto-join'      => 'absint',
			)
		);

		// Strip any dangerous WordPress core options that a careless extension might add.
		return bb_filter_allowed_options( $options );
	}

	/**
	 * Get all group types with meta.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function get_group_types() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'groups' ) ) {
			wp_send_json_error( array( 'message' => __( 'Groups component is not active.', 'buddyboss' ) ) );
		}

		$post_type = bp_groups_get_group_type_post_type();

		$posts = get_posts(
			array(
				'post_type'              => $post_type,
				'posts_per_page'         => self::LIST_CAP, // Sanity cap — UI cannot usefully display more.
				'post_status'            => array( 'publish', 'private' ),
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,  // Skip COUNT(*) — we don't paginate here.
				'update_post_meta_cache' => true,  // Batch-load post meta in one query.
				'update_post_term_cache' => false, // Term cache not needed for group types list.
			)
		);

		// Get all group type counts in a single query instead of N identical queries.
		global $wpdb;
		$count_rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.slug, tt.count FROM {$wpdb->term_taxonomy} tt LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id WHERE tt.taxonomy = %s",
				'bp_group_type'
			)
		);
		$group_counts = array();
		foreach ( $count_rows as $row ) {
			$group_counts[ $row->slug ] = (int) $row->count;
		}

		$group_types = array();

		foreach ( $posts as $post ) {
			$post_id  = $post->ID;
			$type_key = get_post_meta( $post_id, '_bp_group_type_key', true );

			$group_types[] = array(
				'id'                  => $post_id,
				'name'                => $type_key,
				'post_title'          => $post->post_title,
				'singular_label'      => get_post_meta( $post_id, '_bp_group_type_label_singular_name', true ),
				'plural_label'        => get_post_meta( $post_id, '_bp_group_type_label_name', true ),
				'groups_count'        => isset( $group_counts[ $type_key ] ) ? (int) $group_counts[ $type_key ] : 0,
				'enable_filter'       => absint( get_post_meta( $post_id, '_bp_group_type_enable_filter', true ) ),
				'enable_remove'       => absint( get_post_meta( $post_id, '_bp_group_type_enable_remove', true ) ),
				'role_labels'         => $this->bb_normalize_role_labels( get_post_meta( $post_id, '_bp_group_type_role_labels', true ) ),
				'label_color'         => map_deep( get_post_meta( $post_id, '_bp_group_type_label_color', true ), 'sanitize_text_field' ),
				'restrict_invites'    => absint( get_post_meta( $post_id, '_bp_group_type_restrict_invites_user_same_group_type', true ) ),
				'member_type_join'    => map_deep( get_post_meta( $post_id, '_bp_group_type_enabled_member_type_join', true ), 'sanitize_key' ),
				'member_type_invites' => map_deep( get_post_meta( $post_id, '_bp_group_type_enabled_member_type_group_invites', true ), 'sanitize_key' ),
				'visibility'          => 'private' === $post->post_status ? 'private' : 'public',
			);
		}

		// Get available member/profile types for the modal.
		$member_types = $this->bb_get_available_member_types();

		$response = array(
			'group_types'  => $group_types,
			'member_types' => $member_types,
		);

		/**
		 * Filters the response data for the admin group types list endpoint.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $response Response data array.
		 */
		wp_send_json_success( apply_filters( 'bb_admin_get_group_types_response', $response ) );
	}

	/**
	 * Create a new group type.
	 *
	 * Mirrors legacy bp_save_group_type_post_meta_box_data() for key generation,
	 * label defaults, and cache clearing.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function create_group_type() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'groups' ) ) {
			wp_send_json_error( array( 'message' => __( 'Groups component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$name       = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$visibility = isset( $_POST['visibility'] ) ? sanitize_key( wp_unslash( $_POST['visibility'] ) ) : 'public';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$meta_data = $this->bb_extract_group_type_meta_from_post();

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Group type name is required.', 'buddyboss' ) ) );
		}

		$post_type   = bp_groups_get_group_type_post_type();
		$post_status = 'private' === $visibility ? 'private' : 'publish';

		// Create the CPT post.
		$post_id = wp_insert_post(
			array(
				'post_title'  => $name,
				'post_type'   => $post_type,
				'post_status' => $post_status,
			)
		);

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => esc_html( $post_id->get_error_message() ) ) );
		}

		// Generate type key from post_name (slug), same as legacy bp_save_group_type_post_meta_box_data().
		$key  = get_post_field( 'post_name', $post_id );
		$term = term_exists( sanitize_key( $key ), 'bp_group_type' );
		if ( 0 !== $term && null !== $term ) {
			$key = $key . wp_rand( 100, 999 );
		}

		update_post_meta( $post_id, '_bp_group_type_key', sanitize_key( $key ) );

		// Save all meta fields (with post_title as default for labels, matching legacy).
		$this->bb_save_group_type_meta( $post_id, $name, $meta_data );

		// Clear group type caches.
		$this->bb_clear_group_type_cache( $post_id );

		wp_send_json_success(
			array(
				'message' => __( 'Group type created successfully.', 'buddyboss' ),
				'id'      => $post_id,
			)
		);
	}

	/**
	 * Update an existing group type.
	 *
	 * Mirrors legacy bp_save_group_type_post_meta_box_data() for label defaults
	 * and cache clearing.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function update_group_type() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'groups' ) ) {
			wp_send_json_error( array( 'message' => __( 'Groups component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$type_id    = isset( $_POST['type_id'] ) ? absint( wp_unslash( $_POST['type_id'] ) ) : 0;
		$name       = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$visibility = isset( $_POST['visibility'] ) ? sanitize_key( wp_unslash( $_POST['visibility'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$meta_data = $this->bb_extract_group_type_meta_from_post();

		if ( empty( $type_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Group type ID is required.', 'buddyboss' ) ) );
		}

		// Verify post exists and is correct type.
		$post = get_post( $type_id );
		if ( ! $post || bp_groups_get_group_type_post_type() !== $post->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Invalid group type.', 'buddyboss' ) ) );
		}

		// Update title and/or visibility if provided.
		$post_title   = ! empty( $name ) ? $name : $post->post_title;
		$update_args  = array( 'ID' => $type_id );
		$needs_update = false;

		if ( ! empty( $name ) ) {
			$update_args['post_title'] = $name;
			$needs_update              = true;
		}

		if ( ! empty( $visibility ) ) {
			$update_args['post_status'] = 'private' === $visibility ? 'private' : 'publish';
			$needs_update               = true;
		}

		if ( $needs_update ) {
			$result = wp_update_post( $update_args, true );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => esc_html( $result->get_error_message() ) ) );
			}
		}

		// Save all meta fields (with post_title as default for labels, matching legacy).
		$this->bb_save_group_type_meta( $type_id, $post_title, $meta_data );

		// Clear group type caches.
		$this->bb_clear_group_type_cache( $type_id );

		wp_send_json_success(
			array(
				'message' => __( 'Group type updated successfully.', 'buddyboss' ),
				'id'      => $type_id,
			)
		);
	}

	/**
	 * Delete a group type.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function delete_group_type() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'groups' ) ) {
			wp_send_json_error( array( 'message' => __( 'Groups component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$type_id = isset( $_POST['type_id'] ) ? absint( wp_unslash( $_POST['type_id'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $type_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Group type ID is required.', 'buddyboss' ) ) );
		}

		// Verify post exists and is correct type.
		$post = get_post( $type_id );
		if ( ! $post || bp_groups_get_group_type_post_type() !== $post->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Invalid group type.', 'buddyboss' ) ) );
		}

		$result = wp_delete_post( $type_id, true );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete group type.', 'buddyboss' ) ) );
		}

		wp_send_json_success(
			array( 'message' => __( 'Group type deleted successfully.', 'buddyboss' ) )
		);
	}

	/**
	 * Get platform settings (WordPress options) by allowlisted names.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function get_platform_settings() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$options = isset( $_POST['options'] ) ? sanitize_text_field( wp_unslash( $_POST['options'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $options ) ) {
			wp_send_json_error( array( 'message' => __( 'Options parameter is required.', 'buddyboss' ) ) );
		}

		$requested = array_map( 'sanitize_text_field', explode( ',', $options ) );
		$allowed   = $this->bb_get_allowed_platform_options();
		$settings  = array();

		foreach ( $requested as $option_name ) {
			if ( array_key_exists( $option_name, $allowed ) ) {
				$settings[ $option_name ] = bp_get_option( $option_name, '' );
			}
		}

		wp_send_json_success( $settings );
	}

	/**
	 * Save a single platform setting (WordPress option).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function save_platform_setting() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$option_name = isset( $_POST['option_name'] ) ? sanitize_text_field( wp_unslash( $_POST['option_name'] ) ) : '';
		$raw_value   = isset( $_POST['option_value'] ) ? wp_unslash( $_POST['option_value'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below per-option.
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $option_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Option name is required.', 'buddyboss' ) ) );
		}

		$allowed = $this->bb_get_allowed_platform_options();

		if ( ! array_key_exists( $option_name, $allowed ) ) {
			wp_send_json_error( array( 'message' => __( 'Option not allowed.', 'buddyboss' ) ) );
		}

		// Apply per-option sanitize callback (defined in bb_get_allowed_platform_options).
		$sanitize_fn  = $allowed[ $option_name ];
		$option_value = is_callable( $sanitize_fn ) ? call_user_func( $sanitize_fn, $raw_value ) : sanitize_text_field( $raw_value );

		bp_update_option( $option_name, $option_value );

		// Clear group type registry cache when the creation toggle changes.
		if ( 'bp-disable-group-type-creation' === $option_name ) {
			wp_cache_delete( 'bp_group_types', 'bp_groups' );
			wp_cache_delete( 'bb-group-type-label-css', 'bp_groups_group_type' );
		}

		wp_send_json_success(
			array( 'message' => __( 'Setting saved successfully.', 'buddyboss' ) )
		);
	}

	/**
	 * Get groups listing with pagination, filters, and sorting.
	 *
	 * Uses existing legacy hooks/filters from BP_Groups_List_Table so
	 * third-party plugins that add custom columns, bulk actions, or modify
	 * column output continue to work in Settings 2.0.
	 *
	 * Hooks used:
	 * - `bp_groups_list_table_get_columns`           (column definitions)
	 * - `bp_groups_list_table_get_bulk_actions`       (bulk action definitions)
	 * - `bp_groups_admin_get_group_status`            (status column filter)
	 * - `bp_groups_admin_get_group_member_count`      (members column filter)
	 * - `bp_groups_admin_get_group_last_active`       (last active column filter)
	 * - `bp_groups_admin_get_group_custom_column`     (custom column content)
	 * - `bp_groups_admin_get_group_type_column`       (group type column filter)
	 * - `bp_get_group_name`                           (group name filter)
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function get_groups() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'groups' ) ) {
			wp_send_json_error( array( 'message' => __( 'Groups component is not active.', 'buddyboss' ) ) );
		}

		/**
		 * Fires when the groups admin list is displayed. Deprecated in Settings 2.0.
		 *
		 * @since BuddyPress 1.7.0
		 * @deprecated BuddyBoss [BBVERSION] Use the {@see 'bb_admin_get_groups_response'} filter instead.
		 *
		 * @param array $args Empty array (legacy: request args; N/A in Settings 2.0).
		 */
		do_action_deprecated( 'bp_groups_admin_index', array( array() ), 'BuddyBoss [BBVERSION]', 'bb_admin_get_groups_response' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$page         = isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : 1;
		$per_page     = isset( $_POST['per_page'] ) ? absint( wp_unslash( $_POST['per_page'] ) ) : 20;
		$search       = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$status       = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'all';
		$sort         = isset( $_POST['sort'] ) ? sanitize_text_field( wp_unslash( $_POST['sort'] ) ) : 'newest';
		$group_type   = isset( $_POST['group_type'] ) ? sanitize_text_field( wp_unslash( $_POST['group_type'] ) ) : '';
		$include_meta = isset( $_POST['include_meta'] ) ? absint( wp_unslash( $_POST['include_meta'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Validate status.
		$allowed_statuses = array( 'all', 'public', 'private', 'hidden' );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = 'all';
		}

		// Validate sort.
		$allowed_sorts = array( 'newest', 'oldest', 'highest_users', 'lowest_users', 'group_types', 'last_active' );
		if ( ! in_array( $sort, $allowed_sorts, true ) ) {
			$sort = 'newest';
		}

		// Clamp per_page.
		$per_page = max( 1, min( self::PER_PAGE_CAP, $per_page ) );
		$page     = max( 1, $page );

		// Map sort to query args.
		$sort_map  = array(
			'newest'        => array(
				'type'    => 'newest',
				'orderby' => '',
				'order'   => '',
			),
			'oldest'        => array(
				'type'    => '',
				'orderby' => 'date_created',
				'order'   => 'ASC',
			),
			'highest_users' => array(
				'type'    => '',
				'orderby' => 'total_member_count',
				'order'   => 'DESC',
			),
			'lowest_users'  => array(
				'type'    => '',
				'orderby' => 'total_member_count',
				'order'   => 'ASC',
			),
			'group_types'   => array(
				'type'    => '',
				'orderby' => 'name',
				'order'   => 'ASC',
			),
			'last_active'   => array(
				'type'    => '',
				'orderby' => 'last_activity',
				'order'   => 'DESC',
			),
		);
		$sort_args = $sort_map[ $sort ];

		// Build query args.
		$query_args = array(
			'per_page'          => $per_page,
			'page'              => $page,
			'show_hidden'       => true,
			'update_meta_cache' => true,
		);

		if ( ! empty( $sort_args['type'] ) ) {
			$query_args['type'] = $sort_args['type'];
		}

		if ( ! empty( $sort_args['orderby'] ) ) {
			$query_args['orderby'] = $sort_args['orderby'];
			$query_args['order']   = $sort_args['order'];
		}

		if ( ! empty( $search ) ) {
			$query_args['search_terms'] = $search;
		}

		if ( 'all' !== $status ) {
			$query_args['status'] = array( $status );
		}

		if ( ! empty( $group_type ) ) {
			$query_args['group_type'] = $group_type;
		}

		$groups_result = groups_get_groups( $query_args );
		$groups        = $groups_result['groups'];
		$total         = (int) $groups_result['total'];

		// Build a lookup map of group type slug => human-readable label.
		$type_objects = bp_groups_get_group_types( array(), 'objects' );
		$type_labels  = array();
		foreach ( $type_objects as $type_obj ) {
			$type_labels[ $type_obj->name ] = $type_obj->labels['singular_name'];
		}

		// Prime term cache for all groups in a single query to avoid N+1 lookups.
		if ( ! empty( $groups ) ) {
			update_object_term_cache( wp_list_pluck( $groups, 'id' ), 'bp_group_type' );
		}

		// Pre-fetch group type slugs for all groups (now hits cache, not DB).
		$group_type_map = array();
		foreach ( $groups as $group ) {
			$type_slug                    = bp_groups_get_group_type( $group->id, true );
			$group_type_map[ $group->id ] = ! empty( $type_slug ) ? $type_slug : '';
		}

		// For "Group Types" sort, re-sort groups in PHP after fetch.
		// NOTE: This sort applies to the current page only. Cross-page ordering
		// is not guaranteed because the DB query fetches by name; a full
		// taxonomy-join sort would require modifying BP_Groups_Group::get().
		if ( 'group_types' === $sort ) {
			usort(
				$groups,
				function ( $a, $b ) use ( $type_labels, $group_type_map ) {
					$type_a = $group_type_map[ $a->id ];
					$type_b = $group_type_map[ $b->id ];

					$label_a = ! empty( $type_a ) && isset( $type_labels[ $type_a ] ) ? $type_labels[ $type_a ] : '';
					$label_b = ! empty( $type_b ) && isset( $type_labels[ $type_b ] ) ? $type_labels[ $type_b ] : '';

					// Groups without a type go last.
					if ( empty( $label_a ) && ! empty( $label_b ) ) {
						return 1;
					}
					if ( ! empty( $label_a ) && empty( $label_b ) ) {
						return -1;
					}

					$cmp = strcasecmp( $label_a, $label_b );
					if ( 0 !== $cmp ) {
						return $cmp;
					}

					// Within same type, sort by name.
					return strcasecmp( $a->name, $b->name );
				}
			);
		}

		// Prime user cache for creators.
		if ( ! empty( $groups ) ) {
			$creator_ids = array_unique( wp_list_pluck( $groups, 'creator_id' ) );
			if ( ! empty( $creator_ids ) ) {
				cache_users( $creator_ids );
			}
		}

		// Get columns via the same filter as BP_Groups_List_Table::get_columns().
		// This ensures third-party plugins that add columns (via bp_groups_list_table_get_columns)
		// will also have their columns rendered in Settings 2.0.
		$all_columns = apply_filters(
			'bp_groups_list_table_get_columns',
			array(
				'cb'          => '<input name type="checkbox" />',
				'comment'     => __( 'Name', 'buddyboss' ),
				'description' => __( 'Description', 'buddyboss' ),
				'status'      => __( 'Status', 'buddyboss' ),
				'members'     => __( 'Members', 'buddyboss' ),
				'last_active' => __( 'Last Active', 'buddyboss' ),
			)
		);

		// Identify custom columns (added by third-party plugins via the filter above).
		$core_columns   = array( 'cb', 'comment', 'description', 'status', 'members', 'last_active' );
		$custom_columns = array();
		foreach ( $all_columns as $col_key => $col_label ) {
			if ( ! in_array( $col_key, $core_columns, true ) ) {
				$custom_columns[ $col_key ] = $col_label;
			}
		}

		// Build response items.
		// Buffer output to capture stray HTML from legacy filters.
		ob_start();

		$items = array();
		foreach ( $groups as $group ) {
			// Cast full group object to array so third-party filters
			// receive all properties they may depend on.
			$item_array = (array) $group;

			// Apply the same filter as BP_Groups_List_Table::column_comment().
			$group_name = apply_filters_ref_array( 'bp_get_group_name', array( $group->name, $item_array ) );

			// Apply the same filter as BP_Groups_List_Table::column_status().
			$status_desc = '';
			switch ( $group->status ) {
				case 'public':
					$status_desc = __( 'Public', 'buddyboss' );
					break;
				case 'private':
					$status_desc = __( 'Private', 'buddyboss' );
					break;
				case 'hidden':
					$status_desc = __( 'Hidden', 'buddyboss' );
					break;
			}
			$status_desc = apply_filters_ref_array( 'bp_groups_admin_get_group_status', array( $status_desc, $item_array ) );

			// Apply the same filter as BP_Groups_List_Table::column_members().
			$member_count = apply_filters_ref_array(
				'bp_groups_admin_get_group_member_count',
				array( (int) $group->total_member_count, $item_array )
			);

			// Apply the same filter as BP_Groups_List_Table::column_last_active().
			$last_activity = ! empty( $group->last_activity ) ? $group->last_activity : $group->date_created;
			$last_activity = apply_filters_ref_array(
				'bp_groups_admin_get_group_last_active',
				array( $last_activity, $item_array )
			);

			$item = array(
				'id'            => (int) $group->id,
				'name'          => $group_name,
				'slug'          => $group->slug,
				'description'   => wp_trim_words( wp_strip_all_tags( apply_filters( 'bp_get_group_description', $group->description, $item_array ) ), 20 ),
				'status'        => $group->status,
				'status_label'  => $status_desc,
				'date_created'  => $group->date_created,
				'last_activity' => $last_activity,
				'total_members' => $member_count,
				'creator_id'    => (int) $group->creator_id,
				'group_type'    => apply_filters_ref_array(
					'bp_groups_admin_get_group_type_column',
					array(
						! empty( $group_type_map[ $group->id ] ) && isset( $type_labels[ $group_type_map[ $group->id ] ] )
							? $type_labels[ $group_type_map[ $group->id ] ]
							: '',
						$item_array,
					)
				),
				'avatar_url'    => bp_core_fetch_avatar(
					array(
						'item_id'    => $group->id,
						'avatar_dir' => 'group-avatars',
						'object'     => 'group',
						'type'       => 'thumb',
						'html'       => false,
					)
				),
				'permalink'     => bp_get_group_permalink( $group ),
			);

			// Render custom columns via the same filter as BP_Groups_List_Table::column_default().
			if ( ! empty( $custom_columns ) ) {
				$item['custom_columns'] = array();
				foreach ( $custom_columns as $col_key => $col_label ) {
					$item['custom_columns'][ $col_key ] = wp_kses_post(
						apply_filters(
							'bp_groups_admin_get_group_custom_column',
							'',
							$col_key,
							$item_array
						)
					);
				}
			}

			$items[] = $item;
		}

		// End output buffer started before the loop.
		ob_end_clean();

		$response = array(
			'groups' => $items,
			'total'  => $total,
		);

		// Include metadata on first request.
		if ( $include_meta ) {
			// Get counts per status — use object cache to avoid repeated queries.
			// Cache is invalidated by groups_delete_group, groups_create_group, and groups_settings_updated hooks
			// via the bp_groups_delete_group_cache / bp_groups_clear_group_count_caches callbacks in bp-groups-cache.php.
			$cache_key  = 'bb_admin_groups_status_counts';
			$counts_map = wp_cache_get( $cache_key, 'bp_groups' );

			if ( false === $counts_map ) {
				global $wpdb;
				$bp = buddypress();
				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from BuddyPress internals, not user input.
				$status_counts = $wpdb->get_results(
					"SELECT status, COUNT(id) AS cnt FROM {$bp->groups->table_name} GROUP BY status"
				);
				// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				$counts_map = array();
				foreach ( $status_counts as $row ) {
					$counts_map[ $row->status ] = (int) $row->cnt;
				}
				wp_cache_set( $cache_key, $counts_map, 'bp_groups' );
			}

			$count_all = array_sum( $counts_map );

			$response['views'] = array(
				'all'     => array(
					'label' => __( 'All', 'buddyboss' ),
					'count' => $count_all,
				),
				'public'  => array(
					'label' => __( 'Public', 'buddyboss' ),
					'count' => isset( $counts_map['public'] ) ? $counts_map['public'] : 0,
				),
				'private' => array(
					'label' => __( 'Private', 'buddyboss' ),
					'count' => isset( $counts_map['private'] ) ? $counts_map['private'] : 0,
				),
				'hidden'  => array(
					'label' => __( 'Hidden', 'buddyboss' ),
					'count' => isset( $counts_map['hidden'] ) ? $counts_map['hidden'] : 0,
				),
			);

			// Use the same filter as BP_Groups_List_Table::get_bulk_actions()
			// so third-party plugins can add their own bulk actions.
			$legacy_bulk_actions = apply_filters(
				'bp_groups_list_table_get_bulk_actions',
				array(
					'delete' => __( 'Delete', 'buddyboss' ),
				)
			);

			// Prefix with 'bulk_' to match Settings 2.0 convention.
			$bulk_actions = array();
			foreach ( $legacy_bulk_actions as $action_key => $action_label ) {
				$bulk_actions[ 'bulk_' . $action_key ] = $action_label;
			}

			// Add group type bulk actions when types exist.
			if ( ! empty( $type_objects ) ) {
				$bulk_actions['bulk_change_group_type'] = __( 'Change Group Type to', 'buddyboss' );
				$bulk_actions['bulk_remove_group_type'] = __( 'Remove Group Type', 'buddyboss' );
			}

			$response['bulk_actions'] = $bulk_actions;

			/**
			 * Fires after the bulk actions controls are rendered. Deprecated in Settings 2.0.
			 *
			 * In legacy, this hook was used to add the "Change Group Type" dropdown
			 * after the bulk actions select. Settings 2.0 handles group type bulk
			 * actions natively in the React UI.
			 *
			 * @since BuddyPress 2.7.0
			 * @deprecated BuddyBoss [BBVERSION] Group type bulk actions are now part of the React UI.
			 *
			 * @param string $which Empty string (legacy: 'top' or 'bottom'; N/A in Settings 2.0).
			 */
			do_action_deprecated( 'bp_groups_list_table_after_bulk_actions', array( '' ), 'BuddyBoss [BBVERSION]', 'bb_admin_get_groups_response' );

			// Return column definitions from the filtered list (excluding cb).
			$columns_response = array();
			foreach ( $all_columns as $col_key => $col_label ) {
				if ( 'cb' === $col_key ) {
					continue;
				}
				$columns_response[ $col_key ] = $col_label;
			}
			$response['columns'] = $columns_response;

			// Build group type lists from the already-fetched $type_objects
			// to avoid a redundant get_posts() query.
			// Labels are NOT esc_html()'d here because this is a JSON response,
			// not HTML output. The React SelectControl handles DOM escaping.
			// Double-encoding (esc_html + browser) causes "&amp;" to render literally.
			$available_types = array();
			foreach ( $type_objects as $type_obj ) {
				$available_types[] = array(
					'value' => sanitize_key( $type_obj->name ),
					'label' => $type_obj->labels['singular_name'],
				);
			}

			// Used for both filter dropdown and change-type modal.
			$response['group_types'] = $available_types;
		}

		/**
		 * Filters the groups list views (tab filters like All, Public, Private, Hidden).
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array  $views  Array of view_key => array( 'label' => string, 'count' => int ).
		 * @param string $status Current active status filter.
		 */
		if ( ! empty( $response['views'] ) ) {
			$response['views'] = apply_filters( 'bb_admin_groups_list_views', $response['views'], $status );

			/**
			 * Fires after the groups list views are filtered. Deprecated in Settings 2.0.
			 *
			 * @since BuddyBoss 1.0.0
			 * @deprecated BuddyBoss [BBVERSION] Use the {@see 'bb_admin_groups_list_views'} filter instead.
			 *
			 * @param string $url_base Empty string (legacy: URL base for view links; N/A in Settings 2.0).
			 * @param string $status   Current active status filter.
			 */
			do_action_deprecated( 'bp_groups_list_table_get_views', array( '', $status ), 'BuddyBoss [BBVERSION]', 'bb_admin_groups_list_views' );
		}

		/**
		 * Filters the full response data for the admin groups list AJAX endpoint.
		 *
		 * Allows third-party plugins to add extra data to the groups listing response.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $response Response data array.
		 */
		$response = apply_filters( 'bb_admin_get_groups_response', $response );

		wp_send_json_success( $response );
	}

	/**
	 * Delete a single group.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function delete_group() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'groups' ) ) {
			wp_send_json_error( array( 'message' => __( 'Groups component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$group_id = isset( $_POST['group_id'] ) ? absint( wp_unslash( $_POST['group_id'] ) ) : 0;

		if ( empty( $group_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Group ID is required.', 'buddyboss' ) ) );
		}

		$group = groups_get_group( $group_id );
		if ( empty( $group->id ) ) {
			wp_send_json_error( array( 'message' => __( 'Group not found.', 'buddyboss' ) ) );
		}

		$result = groups_delete_group( $group_id );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete group.', 'buddyboss' ) ) );
		}

		wp_send_json_success(
			array( 'message' => __( 'Group deleted successfully.', 'buddyboss' ) )
		);
	}

	/**
	 * Perform bulk action on groups.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function group_bulk_action() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'groups' ) ) {
			wp_send_json_error( array( 'message' => __( 'Groups component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$raw_ids   = isset( $_POST['group_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['group_ids'] ) ) : '';
		$do_action = isset( $_POST['do_action'] ) ? sanitize_key( wp_unslash( $_POST['do_action'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Validate action against the same filter as BP_Groups_List_Table::get_bulk_actions()
		// plus Settings 2.0 group type actions.
		$allowed_bulk_actions = apply_filters(
			'bp_groups_list_table_get_bulk_actions',
			array(
				'delete' => __( 'Delete', 'buddyboss' ),
			)
		);

		// Group type bulk actions are not part of the legacy filter.
		$type_actions = array( 'change_group_type', 'remove_group_type' );

		if ( ! array_key_exists( $do_action, $allowed_bulk_actions ) && ! in_array( $do_action, $type_actions, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid action.', 'buddyboss' ) ) );
		}

		if ( empty( $raw_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No groups selected.', 'buddyboss' ) ) );
		}

		$group_ids = array_filter( array_map( 'absint', explode( ',', $raw_ids ) ) );

		if ( empty( $group_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No valid group IDs provided.', 'buddyboss' ) ) );
		}

		// Cap bulk operations to prevent timeout on large selections.
		$group_ids = array_slice( $group_ids, 0, self::BULK_CAP );

		$processed = 0;
		$failed    = 0;

		// Validate group_type for change action upfront.
		$new_group_type = '';
		if ( 'change_group_type' === $do_action ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
			$new_group_type = isset( $_POST['group_type'] ) ? sanitize_key( wp_unslash( $_POST['group_type'] ) ) : '';
			if ( empty( $new_group_type ) || ! bp_groups_get_group_type_object( $new_group_type ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid group type.', 'buddyboss' ) ) );
			}
		}

		// Prime term cache for remove_group_type to avoid N+1 lookups in loop.
		if ( 'remove_group_type' === $do_action ) {
			update_object_term_cache( $group_ids, 'bp_group_type' );
		}

		foreach ( $group_ids as $group_id ) {
			if ( 'delete' === $do_action ) {
				$result = groups_delete_group( $group_id );
				if ( $result ) {
					++$processed;
				} else {
					++$failed;
				}
			} elseif ( 'change_group_type' === $do_action ) {
				$result = bp_groups_set_group_type( $group_id, $new_group_type );
				if ( ! is_wp_error( $result ) && false !== $result ) {
					++$processed;
				} else {
					++$failed;
				}
			} elseif ( 'remove_group_type' === $do_action ) {
				$current_type = bp_groups_get_group_type( $group_id, true );
				if ( ! empty( $current_type ) ) {
					$result = bp_groups_remove_group_type( $group_id, $current_type );
					if ( ! is_wp_error( $result ) && false !== $result ) {
						++$processed;
					} else {
						++$failed;
					}
				} else {
					// No type to remove; count as processed.
					++$processed;
				}
			} else {
				/**
				 * Fires for custom bulk actions added via bp_groups_list_table_get_bulk_actions filter.
				 *
				 * @since BuddyBoss [BBVERSION]
				 *
				 * @param int    $group_id  The group ID being processed.
				 * @param string $do_action The bulk action key.
				 */
				do_action( 'bb_admin_groups_bulk_action', $group_id, $do_action );
				++$processed;
			}
		}

		if ( $processed > 0 ) {
			if ( 'delete' === $do_action ) {
				$message = sprintf(
					/* translators: %d: Number of groups processed. */
					_n(
						'%d group deleted successfully.',
						'%d groups deleted successfully.',
						$processed,
						'buddyboss'
					),
					$processed
				);
			} elseif ( 'change_group_type' === $do_action ) {
				$message = sprintf(
					/* translators: %d: Number of groups processed. */
					_n(
						'Group type changed for %d group.',
						'Group type changed for %d groups.',
						$processed,
						'buddyboss'
					),
					$processed
				);
			} elseif ( 'remove_group_type' === $do_action ) {
				$message = sprintf(
					/* translators: %d: Number of groups processed. */
					_n(
						'Group type removed from %d group.',
						'Group type removed from %d groups.',
						$processed,
						'buddyboss'
					),
					$processed
				);
			} else {
				$message = sprintf(
					/* translators: %d: Number of groups processed. */
					_n(
						'%d group updated successfully.',
						'%d groups updated successfully.',
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
			wp_send_json_error( array( 'message' => __( 'No groups were processed.', 'buddyboss' ) ) );
		}
	}

	/**
	 * Create a new group.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function create_group() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'groups' ) ) {
			wp_send_json_error( array( 'message' => __( 'Groups component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$description = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';
		$status      = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'public';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Group name is required.', 'buddyboss' ) ) );
		}

		/**
		 * Filters the allowed group statuses.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $allowed_statuses Array of allowed group statuses.
		 */
		$allowed_statuses = apply_filters( 'groups_allowed_status', array( 'public', 'private', 'hidden' ) );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = 'public';
		}

		$group_id = groups_create_group(
			array(
				'name'        => $name,
				'description' => $description,
				'slug'        => groups_check_slug( sanitize_title( $name ) ),
				'status'      => $status,
				'creator_id'  => bp_loggedin_user_id(),
			)
		);

		if ( ! $group_id ) {
			wp_send_json_error( array( 'message' => __( 'Failed to create group.', 'buddyboss' ) ) );
		}

		wp_send_json_success(
			array(
				'message'  => __( 'Group created successfully.', 'buddyboss' ),
				'group_id' => $group_id,
			)
		);
	}

	/**
	 * Get a single group with registered meta fields for editing.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function get_group() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'groups' ) ) {
			wp_send_json_error( array( 'message' => __( 'Groups component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$group_id = isset( $_POST['group_id'] ) ? absint( wp_unslash( $_POST['group_id'] ) ) : 0;

		if ( empty( $group_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Group ID is required.', 'buddyboss' ) ) );
		}

		$group = groups_get_group( $group_id );
		if ( empty( $group->id ) ) {
			wp_send_json_error( array( 'message' => __( 'Group not found.', 'buddyboss' ) ) );
		}

		/**
		 * Fires after the registration of all of the default group meta boxes.
		 *
		 * Mirrors the legacy `bp_groups_admin_meta_boxes` action from bp_groups_admin_load().
		 * Third-party plugins (e.g. LearnDash, BP_Group_Extension) hook here to register
		 * meta boxes. Output is suppressed — meta boxes registered here should expose their
		 * data through the `registered_fields` array via BB_Admin_Meta_Field_Registry instead.
		 *
		 * @since BuddyPress 1.7.0
		 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX get group.
		 */
		// Synthetic $_GET['gid'] so legacy extensions (BP_Group_Extension, LearnDash)
		// that read $_GET['gid'] inside their metabox callbacks get the correct group ID.
		// Save/restore original value in case a callback exits abnormally.
		$original_gid = isset( $_GET['gid'] ) ? $_GET['gid'] : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Backing up raw value for restore; not used as input.
		$_GET['gid']  = $group_id;

		// Set a synthetic screen so legacy extensions calling get_current_screen()->id
		// inside their metabox callbacks don't fatal in the AJAX context (where
		// get_current_screen() returns null). This ensures backward compatibility
		// when old Pro (without Settings 2.0 gates) runs with new Platform.
		$had_screen = ( function_exists( 'get_current_screen' ) && null !== get_current_screen() );
		if ( ! $had_screen && function_exists( 'set_current_screen' ) ) {
			set_current_screen( 'toplevel_page_bp-groups' );
		}

		try {
			ob_start();
			do_action( 'bp_groups_admin_meta_boxes' );
			ob_end_clean();
		} catch ( \Error $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Intentionally suppress fatal errors from old extensions (e.g. get_current_screen()->id on null).
			// Suppress fatal errors from old extensions that assume a WP admin screen context.
			// The ob_start() buffer may still be open if the error occurred mid-output.
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
		} finally {
			if ( null === $original_gid ) {
				unset( $_GET['gid'] );
			} else {
				$_GET['gid'] = $original_gid;
			}
		}

		/**
		 * Fires before the group edit form is displayed so plugins can modify the group.
		 *
		 * Same hook as legacy bp_groups_admin_edit() before edit form display.
		 * Group object is passed by reference so plugins can modify it before
		 * the data is returned to the React edit modal.
		 *
		 * @since BuddyPress 1.7.0
		 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX get group.
		 *
		 * @param BP_Groups_Group $group The group object being edited, passed by reference.
		 */
		ob_start();
		do_action_ref_array( 'bp_groups_admin_edit', array( &$group ) );
		ob_end_clean();

		$data = array(
			'id'                => (int) $group->id,
			'name'              => $group->name,
			'slug'              => $group->slug,
			'description'       => $group->description,
			'status'            => $group->status,
			'parent_id'         => (int) $group->parent_id,
			'enable_forum'      => (int) $group->enable_forum,
			'creator_id'        => (int) $group->creator_id,
			'date_created'      => $group->date_created,
			'permalink'         => bp_get_group_permalink( $group ),
			'avatar_url'        => bp_core_fetch_avatar(
				array(
					'item_id'    => $group->id,
					'avatar_dir' => 'group-avatars',
					'object'     => 'group',
					'type'       => 'thumb',
					'html'       => false,
				)
			),
			'registered_fields' => bb_admin_meta_field_registry()->get_fields_data( 'groups', $group ),
		);

		/**
		 * Filters the response data for the admin single group endpoint.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array  $data  Response data array.
		 * @param object $group The group object.
		 */
		wp_send_json_success( apply_filters( 'bb_admin_get_group_response', $data, $group ) );
	}

	/**
	 * Save group data from the edit modal.
	 *
	 * Flow:
	 * 1. save_fields_data('groups', $group, 'before') — sets object properties.
	 * 2. do_action('bb_admin_before_save_group') — pre-save hook.
	 * 3. groups_edit_base_group_details() — fires groups_details_updated + cache.
	 * 4. groups_edit_group_settings() — handles privacy + permissions + cache.
	 * 5. save_fields_data('groups', $group, 'after') — saves group_type + Pro fields.
	 * 6. do_action('bb_admin_after_save_group') — post-save hook (forum lifecycle, etc.).
	 * 7. do_action('bp_group_admin_edit_after') — backward compat.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function save_group() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'groups' ) ) {
			wp_send_json_error( array( 'message' => __( 'Groups component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$group_id = isset( $_POST['group_id'] ) ? absint( wp_unslash( $_POST['group_id'] ) ) : 0;

		if ( empty( $group_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Group ID is required.', 'buddyboss' ) ) );
		}

		$group = groups_get_group( $group_id );
		if ( empty( $group->id ) ) {
			wp_send_json_error( array( 'message' => __( 'Group not found.', 'buddyboss' ) ) );
		}

		$registry = bb_admin_meta_field_registry();

		// Capture original slug before registry modifies the group object.
		$old_slug = $group->slug;

		// Save "before" fields — sets properties on the group object.
		$registry->save_fields_data( 'groups', $group, 'before' );

		/**
		 * Fires before a group is saved from the Settings 2.0 admin edit modal.
		 *
		 * Use this hook for any pre-save logic in the React admin context.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int    $group_id Group ID.
		 * @param object $group    Group object with updated field values (not yet persisted).
		 */
		do_action( 'bb_admin_before_save_group', $group_id, $group );

		$new_slug = $group->slug;

		// Save base group details (name, slug, description, parent).
		groups_edit_base_group_details(
			array(
				'group_id'    => $group_id,
				'name'        => $group->name,
				'slug'        => $group->slug,
				'description' => $group->description,
				'parent_id'   => isset( $group->parent_id ) ? (int) $group->parent_id : 0,
			)
		);

		// Write previous_slug if slug changed.
		if ( $old_slug !== $new_slug ) {
			groups_update_groupmeta( $group_id, 'previous_slug', $old_slug );
		}

		// Save group settings (status, permissions, forum).
		// These fields are read directly from $_POST rather than relying solely on
		// BB_Admin_Meta_Field_Registry because groups_edit_group_settings() requires them
		// as individual arguments (not as group meta). The registry save_fields_data()
		// would need a reference to the live $group object, and the permission fields
		// have nullable-vs-fallback semantics that cannot be expressed via a generic save_value callback.
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$status       = isset( $_POST['registered_field_status'] ) ? sanitize_key( wp_unslash( $_POST['registered_field_status'] ) ) : $group->status;
		$enable_forum = isset( $_POST['registered_field_enable_forum'] ) ? absint( wp_unslash( $_POST['registered_field_enable_forum'] ) ) : (int) $group->enable_forum;

		// Permission fields: validate against bb_groups_get_settings_status() whitelist,
		// matching the legacy admin pattern (bp-groups-admin.php). If the value is invalid
		// or not submitted, fall back via bb_groups_settings_default_fallback().
		$invite_status   = $this->bb_validate_group_permission_field( 'perm_invite', 'invite' );
		$activity_status = $this->bb_validate_group_permission_field( 'perm_activity_feed', 'activity_feed' );
		$media_status    = $this->bb_validate_group_permission_field( 'perm_media', 'media' );
		$album_status    = $this->bb_validate_group_permission_field( 'perm_album', 'album' );
		$document_status = $this->bb_validate_group_permission_field( 'perm_document', 'document' );
		$video_status    = $this->bb_validate_group_permission_field( 'perm_video', 'video' );
		$message_status  = $this->bb_validate_group_permission_field( 'perm_message', 'message' );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Validate group status against allowed values, matching create_group() pattern.
		$allowed_statuses = apply_filters( 'groups_allowed_status', array( 'public', 'private', 'hidden' ) );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = $group->status; // Fallback to current status.
		}

		// Fallback to existing groupmeta when a permission field is not visible (feature disabled).
		// This prevents clearing stored values when the controlling feature is deactivated.
		$permission_fallbacks = array(
			'activity_status' => &$activity_status,
			'media_status'    => &$media_status,
			'album_status'    => &$album_status,
			'document_status' => &$document_status,
			'video_status'    => &$video_status,
			'message_status'  => &$message_status,
		);
		foreach ( $permission_fallbacks as $meta_key => &$field_value ) {
			if ( false === $field_value ) {
				$stored      = groups_get_groupmeta( $group_id, $meta_key );
				$field_value = ! empty( $stored ) ? $stored : false;
			}
		}
		unset( $field_value );

		groups_edit_group_settings(
			$group_id,
			$enable_forum,
			$status,
			$invite_status,
			$activity_status,
			isset( $group->parent_id ) ? (int) $group->parent_id : false,
			$media_status,
			$document_status,
			$video_status,
			$album_status,
			$message_status
		);

		// Save "after" fields (group type, forum_id reassignment, Pro fields, etc.) via meta field registry.
		$registry->save_fields_data( 'groups', $group, 'after' );

		/**
		 * Fires after a group is saved from the Settings 2.0 admin edit modal.
		 *
		 * Use this hook for any post-save logic in the React admin context
		 * (e.g., cache clearing, sync operations, third-party integrations).
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int    $group_id Group ID.
		 * @param object $group    Group object after save.
		 */
		do_action( 'bb_admin_after_save_group', $group_id, $group );

		/**
		 * Fires after a group is saved from the admin edit modal.
		 *
		 * Kept for backward compatibility with third-party plugins.
		 * Wrapped in try/catch because old extensions may call check_admin_referer()
		 * with legacy nonce names that don't exist in the Settings 2.0 AJAX context,
		 * causing wp_die() → exit. We use ob_start() to capture any wp_die() output
		 * and catch \Error for any other fatals (e.g. null->method calls).
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $group_id ID of the group being edited.
		 */
		try {
			ob_start();
			do_action( 'bp_group_admin_edit_after', $group_id );
			ob_end_clean();
		} catch ( \Error $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Intentionally suppress errors from old extensions with mismatched nonces.
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
		}

		/**
		 * Filters the redirect URL after editing a group from the admin. Deprecated in Settings 2.0.
		 *
		 * Legacy was `apply_filters()` returning a redirect URL; in AJAX context the return value
		 * is discarded, but the correct deprecation type is preserved for third-party compatibility.
		 *
		 * @since BuddyPress 1.7.0
		 * @deprecated BuddyBoss [BBVERSION] Use the {@see 'bb_admin_save_group_response'} filter instead.
		 *
		 * @param string $redirect_to Empty string (legacy: URL to redirect to; N/A in Settings 2.0).
		 */
		apply_filters_deprecated( 'bp_group_admin_edit_redirect', array( '' ), 'BuddyBoss [BBVERSION]', 'bb_admin_save_group_response' );

		$response = array( 'message' => __( 'Group updated successfully.', 'buddyboss' ) );

		/**
		 * Filters the response data after saving a group from the admin edit modal.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $response Response data array.
		 * @param int   $group_id The group ID.
		 */
		wp_send_json_success( apply_filters( 'bb_admin_save_group_response', $response, $group_id ) );
	}

	/**
	 * Get group members with pagination.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function get_group_members() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'groups' ) ) {
			wp_send_json_error( array( 'message' => __( 'Groups component is not active.', 'buddyboss' ) ) );
		}

		$allowed_roles = array( 'admin', 'mod', 'member', 'banned' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$group_id = isset( $_POST['group_id'] ) ? absint( wp_unslash( $_POST['group_id'] ) ) : 0;
		$page     = isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? absint( wp_unslash( $_POST['per_page'] ) ) : 10;
		$role     = isset( $_POST['role'] ) ? sanitize_key( wp_unslash( $_POST['role'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $group_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Group ID is required.', 'buddyboss' ) ) );
		}

		$group = groups_get_group( $group_id );
		if ( empty( $group->id ) ) {
			wp_send_json_error( array( 'message' => __( 'Group not found.', 'buddyboss' ) ) );
		}

		// Validate role param if provided.
		if ( ! empty( $role ) && ! in_array( $role, $allowed_roles, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid role.', 'buddyboss' ) ) );
		}

		$per_page = max( 1, min( self::PER_PAGE_CAP, $per_page ) );
		$page     = max( 1, $page );

		// Use the specific role (or fallback to 'member') for the per-page filter.
		$filter_role = ! empty( $role ) ? $role : 'member';

		/**
		 * Filters the number of group members displayed per page in the admin edit modal.
		 *
		 * Mirrors the legacy `bp_groups_admin_members_type_per_page` filter used by
		 * bp_groups_admin_edit_metabox_members().
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int    $per_page    Number of members per page.
		 * @param string $member_type Member type slug ('admin', 'mod', 'member', 'banned').
		 */
		$per_page = (int) apply_filters( 'bp_groups_admin_members_type_per_page', $per_page, $filter_role );
		$per_page = max( 1, min( self::PER_PAGE_CAP, $per_page ) ); // Re-clamp after filter.

		// Build query args based on whether a specific role is requested.
		$query_args = array(
			'group_id' => $group_id,
			'per_page' => $per_page,
			'page'     => $page,
		);

		if ( ! empty( $role ) ) {
			if ( 'member' === $role ) {
				// For regular members, exclude admins/mods and banned.
				$query_args['exclude_admins_mods'] = true;
				$query_args['exclude_banned']      = true;
			} elseif ( 'banned' === $role ) {
				// For banned, use the group_role filter.
				$query_args['group_role']          = array( 'banned' );
				$query_args['exclude_admins_mods'] = false;
				$query_args['exclude_banned']      = false;
			} else {
				// For admin/mod roles, filter by group_role.
				$query_args['group_role']          = array( $role );
				$query_args['exclude_admins_mods'] = false;
				$query_args['exclude_banned']      = false;
			}
		} else {
			// No role filter — fetch all roles (backward compat).
			$query_args['exclude_admins_mods'] = false;
			$query_args['exclude_banned']      = false;
		}

		$members_data = groups_get_group_members( $query_args );

		// Prime user cache.
		if ( ! empty( $members_data['members'] ) ) {
			$user_ids = wp_list_pluck( $members_data['members'], 'user_id' );
			cache_users( $user_ids );
		}

		// Count group admins once to flag sole-admin members (prevents role change in React UI).
		$group_admins   = groups_get_group_admins( $group_id );
		$admin_count    = count( $group_admins );

		$members = array();
		foreach ( $members_data['members'] as $member ) {
			// Determine role.
			$member_role = 'member';
			if ( $member->is_admin ) {
				$member_role = 'admin';
			} elseif ( $member->is_mod ) {
				$member_role = 'mod';
			} elseif ( $member->is_banned ) {
				$member_role = 'banned';
			}

			/**
			 * Fires for each row in the group members management table. Deprecated in Settings 2.0.
			 *
			 * Legacy signature: do_action( 'bp_groups_admin_manage_member_row', $user_id, $group ).
			 *
			 * @since BuddyPress 1.7.0
			 * @deprecated BuddyBoss [BBVERSION] Use the {@see 'bb_admin_get_group_members_response'} filter instead.
			 *
			 * @param int             $user_id The user ID for the current member row.
			 * @param BP_Groups_Group $group   The group object.
			 */
			do_action_deprecated( 'bp_groups_admin_manage_member_row', array( (int) $member->user_id, $group ), 'BuddyBoss [BBVERSION]', 'bb_admin_get_group_members_response' );

			$members[] = array(
				'user_id'       => (int) $member->user_id,
				'name'          => bp_core_get_user_displayname( $member->user_id ),
				'avatar_url'    => bp_core_fetch_avatar(
					array(
						'item_id' => $member->user_id,
						'object'  => 'user',
						'type'    => 'thumb',
						'html'    => false,
					)
				),
				'profile_url'   => bp_core_get_user_domain( $member->user_id ),
				'role'          => $member_role,
				'is_creator'    => ( (int) $member->user_id === (int) $group->creator_id ),
				'is_sole_admin' => ( 'admin' === $member_role && 1 === $admin_count ),
			);
		}

		$response = array(
			'members' => $members,
			'total'   => (int) $members_data['count'],
		);

		/**
		 * Filters the response data for the admin group members endpoint.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $response Response data array.
		 * @param int   $group_id The group ID.
		 */
		wp_send_json_success( apply_filters( 'bb_admin_get_group_members_response', $response, $group_id ) );
	}

	/**
	 * Add, remove, or change role of a group member.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function update_group_member() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'groups' ) ) {
			wp_send_json_error( array( 'message' => __( 'Groups component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$group_id    = isset( $_POST['group_id'] ) ? absint( wp_unslash( $_POST['group_id'] ) ) : 0;
		$user_id     = isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : 0;
		$role        = isset( $_POST['role'] ) ? sanitize_key( wp_unslash( $_POST['role'] ) ) : '';
		$action_type = isset( $_POST['action_type'] ) ? sanitize_key( wp_unslash( $_POST['action_type'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $group_id ) || empty( $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Group ID and User ID are required.', 'buddyboss' ) ) );
		}

		$group = groups_get_group( $group_id );
		if ( empty( $group->id ) ) {
			wp_send_json_error( array( 'message' => __( 'Group not found.', 'buddyboss' ) ) );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			wp_send_json_error( array( 'message' => __( 'User not found.', 'buddyboss' ) ) );
		}

		// Add a new member.
		if ( 'add' === $action_type ) {
			// Set the POST key that groups_join_group() checks to identify admin-initiated joins.
			// This ensures joined_from='admin' meta is set and joined_date is recorded for
			// private/hidden groups — matching the legacy bp-groups-admin.php form submit behavior.
			$_POST['bp-groups-new-members'] = $user_id; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.

			$result = groups_join_group( $group_id, $user_id );

			unset( $_POST['bp-groups-new-members'] );

			if ( $result ) {
				wp_send_json_success( array( 'message' => __( 'Member added successfully.', 'buddyboss' ) ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Failed to add member.', 'buddyboss' ) ) );
			}
			return;
		}

		// Validate role.
		$allowed_roles = array( 'admin', 'mod', 'member', 'banned', 'remove' );
		if ( ! in_array( $role, $allowed_roles, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid role.', 'buddyboss' ) ) );
		}

		// Single instantiation for all role-change paths (avoids N+1 DB hits).
		$member_obj = new BP_Groups_Member( $user_id, $group_id );

		// Remove member.
		if ( 'remove' === $role ) {
			// Guard: do not allow removal of the last group admin (mirrors legacy bp-groups-admin.php behaviour).
			if ( $member_obj->is_admin && ! $this->bb_group_has_other_admins( $group_id, $user_id ) ) {
				wp_send_json_error( array( 'message' => __( 'You cannot remove the only group administrator.', 'buddyboss' ) ) );
			}

			$result = groups_remove_member( $user_id, $group_id );
			if ( $result ) {
				wp_send_json_success( array( 'message' => __( 'Member removed successfully.', 'buddyboss' ) ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Failed to remove member.', 'buddyboss' ) ) );
			}
			return;
		}

		// Unban first if currently banned and changing to a non-banned role.
		if ( $member_obj->is_banned && 'banned' !== $role ) {
			groups_unban_member( $user_id, $group_id );
		}

		$result = false;
		switch ( $role ) {
			case 'admin':
				$result = groups_promote_member( $user_id, $group_id, $role );
				break;
			case 'mod':
				// Guard: do not allow demoting the last group admin to moderator.
				if ( $member_obj->is_admin && ! $this->bb_group_has_other_admins( $group_id, $user_id ) ) {
					wp_send_json_error( array( 'message' => __( 'You cannot demote the only group administrator.', 'buddyboss' ) ) );
				}
				$result = groups_promote_member( $user_id, $group_id, $role );
				break;
			case 'member':
				// Guard: do not allow demotion of the last group admin.
				if ( $member_obj->is_admin && ! $this->bb_group_has_other_admins( $group_id, $user_id ) ) {
					wp_send_json_error( array( 'message' => __( 'You cannot demote the only group administrator.', 'buddyboss' ) ) );
				}
				$result = groups_demote_member( $user_id, $group_id );
				break;
			case 'banned':
				// Guard: do not allow banning the last group admin.
				if ( $member_obj->is_admin && ! $this->bb_group_has_other_admins( $group_id, $user_id ) ) {
					wp_send_json_error( array( 'message' => __( 'You cannot ban the only group administrator.', 'buddyboss' ) ) );
				}
				$result = groups_ban_member( $user_id, $group_id );
				break;
		}

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Member role updated successfully.', 'buddyboss' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to update member role.', 'buddyboss' ) ) );
		}
	}

	/**
	 * Autocomplete members for adding to a group.
	 *
	 * This is a Settings 2.0 replacement for the legacy
	 * bp_groups_admin_autocomplete_handler() which reads from $_GET.
	 * Our AJAX requests send data via POST FormData.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function member_autocomplete() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$term     = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
		$group_id = isset( $_POST['group_id'] ) ? absint( wp_unslash( $_POST['group_id'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $term ) || strlen( $term ) < 2 ) {
			wp_send_json_success( array( 'results' => array() ) );
		}

		$suggestions = bp_core_get_suggestions(
			array(
				'group_id' => $group_id ? -$group_id : 0, // Negative = exclude current members.
				'limit'    => 10,
				'term'     => $term,
				'type'     => 'members',
			)
		);

		$results = array();

		if ( $suggestions && ! is_wp_error( $suggestions ) ) {
			foreach ( $suggestions as $user ) {
				$user_id   = isset( $user->user_id ) ? (int) $user->user_id : 0;
				$results[] = array(
					'id'    => $user_id,
					'name'  => $user->name,
					'label' => sprintf(
						/* translators: 1: user display name, 2: user ID. */
						__( '%1$s (%2$s)', 'buddyboss' ),
						$user->name,
						$user_id
					),
					'image' => bp_core_fetch_avatar(
						array(
							'item_id' => $user_id,
							'object'  => 'user',
							'type'    => 'thumb',
							'html'    => false,
						)
					),
				);
			}
		}

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * Forum search autocomplete with pagination for the group edit modal.
	 *
	 * Accepts an optional search term and page number. When no term is given,
	 * returns the first page of forums ordered by title (browse mode). When a
	 * term is provided, filters by post title. Both modes support pagination
	 * via the `page` parameter; the response includes `has_more` so React
	 * knows whether to show a "Load more" button.
	 *
	 * Mirrors the visibility guards on the `forum_id` meta-field:
	 * forums must be active, group forums enabled, and the current user must
	 * be a keymaster.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function forum_autocomplete() {
		$this->bb_verify_request();

		// Mirror is_visible guards from the forum_id meta-field.
		if (
			! bp_is_active( 'forums' )
			|| ! function_exists( 'bbp_is_group_forums_active' )
			|| ! bbp_is_group_forums_active()
			|| ! function_exists( 'bbp_is_user_keymaster' )
			|| ! bbp_is_user_keymaster()
		) {
			wp_send_json_error( array( 'message' => __( 'Forums are not available.', 'buddyboss' ) ) );
		}

		if ( ! function_exists( 'bbp_get_forum_post_type' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forums component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
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
			'posts_per_page'         => $per_page + 1, // Fetch one extra to determine has_more.
			'paged'                  => $page,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'post_status'            => array( 'publish', 'private', 'hidden' ),
			'no_found_rows'          => true,  // Skip COUNT(*) — has_more uses the extra-item trick.
			'update_post_meta_cache' => false, // Post meta not needed for forum title autocomplete.
			'update_post_term_cache' => false, // Term cache not needed for forum title autocomplete.
		);

		if ( ! empty( $term ) ) {
			$query_args['s'] = $term;
		}

		$forums = get_posts( $query_args );

		$has_more = count( $forums ) > $per_page;
		if ( $has_more ) {
			array_pop( $forums ); // Remove the extra item used for has_more detection.
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
	 * Get topics for a group.
	 *
	 * Returns the group's activity topics along with configuration data
	 * (topic mode, permission types, max limit) and per-action nonces
	 * for the existing topic CRUD AJAX handlers.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function get_group_topics() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'groups' ) ) {
			wp_send_json_error( array( 'message' => __( 'Groups component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$group_id = isset( $_POST['group_id'] ) ? absint( wp_unslash( $_POST['group_id'] ) ) : 0;

		if ( empty( $group_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Group ID is required.', 'buddyboss' ) ) );
		}

		$group = groups_get_group( $group_id );
		if ( empty( $group->id ) ) {
			wp_send_json_error( array( 'message' => __( 'Group not found.', 'buddyboss' ) ) );
		}

		// Check if group activity topics feature is enabled (Pro function).
		if ( ! function_exists( 'bb_is_enabled_group_activity_topics' ) || ! bb_is_enabled_group_activity_topics() ) {
			wp_send_json_error( array( 'message' => __( 'Group activity topics feature is not enabled.', 'buddyboss' ) ) );
		}

		// Fetch topics for this group.
		$topics_data = array();
		if ( function_exists( 'bb_get_group_activity_topics' ) ) {
			$raw_topics = bb_get_group_activity_topics(
				array(
					'item_id'   => $group_id,
					'item_type' => 'groups',
					'fields'    => 'all',
				)
			);

			if ( ! empty( $raw_topics ) && is_array( $raw_topics ) ) {
				// Get permission type labels for badge display.
				$all_permission_types = function_exists( 'bb_group_activity_topic_permission_type' )
					? bb_group_activity_topic_permission_type()
					: array();

				foreach ( $raw_topics as $topic ) {
					$topic_obj        = is_object( $topic ) ? $topic : (object) $topic;
					$permission_type  = isset( $topic_obj->permission_type ) ? $topic_obj->permission_type : 'members';
					$permission_label = isset( $all_permission_types[ $permission_type ] )
						? $all_permission_types[ $permission_type ]
						: $permission_type;

					$topic_id  = isset( $topic_obj->topic_id ) ? (int) $topic_obj->topic_id : 0;
					$is_global = function_exists( 'bb_topics_manager_instance' ) && $topic_id
						? bb_topics_manager_instance()->bb_is_topic_global( $topic_id )
						: false;

					$topics_data[] = array(
						'topic_id'         => $topic_id,
						'name'             => isset( $topic_obj->name ) ? $topic_obj->name : '',
						'slug'             => isset( $topic_obj->slug ) ? $topic_obj->slug : '',
						'permission_type'  => $permission_type,
						'permission_label' => $permission_label,
						'menu_order'       => isset( $topic_obj->menu_order ) ? (int) $topic_obj->menu_order : 0,
						'is_global'        => $is_global,
					);
				}
			}
		}

		// Get topic mode.
		$topic_mode = function_exists( 'bb_get_group_activity_topic_options' )
			? bb_get_group_activity_topic_options()
			: 'only_from_activity_topics';

		// Get permission types for the form.
		$permission_types = array();
		if ( function_exists( 'bb_group_activity_topic_permission_type' ) ) {
			$raw_perm_types = bb_group_activity_topic_permission_type();
			foreach ( $raw_perm_types as $value => $label ) {
				$permission_types[] = array(
					'value' => $value,
					'label' => $label,
				);
			}
		}

		// Get max topics limit.
		$max_topics = function_exists( 'bb_topics_manager_instance' )
			? bb_topics_manager_instance()->bb_topics_limit()
			: 20;

		// Get global activity topics when mode allows selection from activity topics.
		$global_topics = array();
		if (
			in_array( $topic_mode, array( 'only_from_activity_topics', 'allow_both' ), true ) &&
			function_exists( 'bb_topics_manager_instance' )
		) {
			$raw_global = bb_topics_manager_instance()->bb_get_topics(
				array(
					'item_type' => 'activity',
					'per_page'  => self::LIST_CAP, // Sanity cap — all items are rendered in a dropdown at once.
				)
			);

			if ( ! empty( $raw_global['topics'] ) && is_array( $raw_global['topics'] ) ) {
				foreach ( $raw_global['topics'] as $gt ) {
					$global_topics[] = array(
						'topic_id' => isset( $gt->topic_id ) ? (int) $gt->topic_id : 0,
						'name'     => isset( $gt->name ) ? $gt->name : '',
						'slug'     => isset( $gt->slug ) ? $gt->slug : '',
					);
				}
			}
		}

		$response = array(
			'topics'           => $topics_data,
			'topic_mode'       => $topic_mode,
			'permission_types' => $permission_types,
			'global_topics'    => $global_topics,
			'max_topics'       => (int) $max_topics,
			// Topic nonces are consumed by Pro plugin's topic AJAX handlers.
			'nonces'           => array(
				'add'     => wp_create_nonce( 'bb_add_topic' ),
				'delete'  => wp_create_nonce( 'bb_delete_topic' ),
				'reorder' => wp_create_nonce( 'bb_update_topics_order' ),
				'migrate' => wp_create_nonce( 'bb_migrate_topic' ),
			),
		);

		/**
		 * Filters the response data for the admin group topics endpoint.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $response Response data array.
		 * @param int   $group_id The group ID.
		 */
		wp_send_json_success( apply_filters( 'bb_admin_get_group_topics_response', $response, $group_id ) );
	}

	/**
	 * Extract group type meta fields from $_POST data.
	 *
	 * Centralizes the $_POST extraction for create_group_type() and update_group_type()
	 * to avoid duplication.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array Group type meta fields.
	 */
	private function bb_extract_group_type_meta_from_post() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() in caller.
		$meta_data = array(
			'singular_label'      => isset( $_POST['singular_label'] ) ? sanitize_text_field( wp_unslash( $_POST['singular_label'] ) ) : '',
			'plural_label'        => isset( $_POST['plural_label'] ) ? sanitize_text_field( wp_unslash( $_POST['plural_label'] ) ) : '',
			'enable_filter'       => isset( $_POST['enable_filter'] ) ? absint( wp_unslash( $_POST['enable_filter'] ) ) : 0,
			'enable_remove'       => isset( $_POST['enable_remove'] ) ? absint( wp_unslash( $_POST['enable_remove'] ) ) : 0,
			'restrict_invites'    => isset( $_POST['restrict_invites'] ) ? absint( wp_unslash( $_POST['restrict_invites'] ) ) : 0,
			// Sanitized downstream in bb_save_group_type_meta() which applies sanitize_text_field per element.
			'member_type_join'    => isset( $_POST['member_type_join'] ) ? wp_unslash( $_POST['member_type_join'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			'member_type_invites' => isset( $_POST['member_type_invites'] ) ? wp_unslash( $_POST['member_type_invites'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			'role_labels'         => isset( $_POST['role_labels'] ) ? map_deep( wp_unslash( $_POST['role_labels'] ), 'sanitize_text_field' ) : array(),
			'label_color'         => isset( $_POST['label_color'] ) ? map_deep( wp_unslash( $_POST['label_color'] ), 'sanitize_text_field' ) : array(),
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return $meta_data;
	}

	/**
	 * Save group type meta fields from POST data.
	 *
	 * Mirrors legacy bp_save_group_type_post_meta_box_data():
	 * - Labels default to $post_title when empty (same as legacy).
	 * - Sanitization uses wp_kses() for labels (same as legacy).
	 * - Boolean fields use absint() (same as legacy).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int    $post_id    Post ID of the group type.
	 * @param string $post_title Post title used as default for labels.
	 * @param array  $data       Group type meta fields (singular_label, plural_label, enable_filter, etc.).
	 */
	private function bb_save_group_type_meta( $post_id, $post_title = '', $data = array() ) {
		$singular_label = isset( $data['singular_label'] ) ? wp_kses( $data['singular_label'], wp_kses_allowed_html( 'strip' ) ) : '';
		$plural_label   = isset( $data['plural_label'] ) ? wp_kses( $data['plural_label'], wp_kses_allowed_html( 'strip' ) ) : '';

		// Default labels to post_title when empty, same as legacy bp_save_group_type_post_meta_box_data().
		$singular_label = trim( $singular_label );
		$singular_label = ! empty( $singular_label ) ? $singular_label : $post_title;
		$plural_label   = trim( $plural_label );
		$plural_label   = ! empty( $plural_label ) ? $plural_label : $post_title;
		$enable_filter  = isset( $data['enable_filter'] ) ? absint( $data['enable_filter'] ) : 0;
		$enable_remove  = isset( $data['enable_remove'] ) ? absint( $data['enable_remove'] ) : 0;

		$restrict_invites = isset( $data['restrict_invites'] ) ? absint( $data['restrict_invites'] ) : 0;

		// Member type fields: empty string = "all", "none" = empty array (no types), array of IDs = "selected".
		$raw_member_type_join = isset( $data['member_type_join'] ) ? $data['member_type_join'] : '';
		if ( is_array( $raw_member_type_join ) ) {
			$member_type_join = array_map( 'sanitize_key', $raw_member_type_join );
		} elseif ( 'none' === $raw_member_type_join ) {
			$member_type_join = array();
		} else {
			$member_type_join = '';
		}

		$raw_member_type_invites = isset( $data['member_type_invites'] ) ? $data['member_type_invites'] : '';
		if ( is_array( $raw_member_type_invites ) ) {
			$member_type_invites = array_map( 'sanitize_key', $raw_member_type_invites );
		} elseif ( 'none' === $raw_member_type_invites ) {
			$member_type_invites = array();
		} else {
			$member_type_invites = '';
		}

		// Role labels — stored in legacy flat format: organizer_plural_label_name, etc.
		$role_labels     = array();
		$raw_role_labels = isset( $data['role_labels'] ) && is_array( $data['role_labels'] )
			? $data['role_labels']
			: array();
		$allowed_roles   = array( 'organizer', 'moderator', 'member' );
		foreach ( $raw_role_labels as $role_key => $labels ) {
			$sanitized_key = sanitize_key( $role_key );
			if ( in_array( $sanitized_key, $allowed_roles, true ) && is_array( $labels ) ) {
				$role_labels[ $sanitized_key . '_plural_label_name' ]   = isset( $labels['plural'] ) ? sanitize_text_field( $labels['plural'] ) : '';
				$role_labels[ $sanitized_key . '_singular_label_name' ] = isset( $labels['singular'] ) ? sanitize_text_field( $labels['singular'] ) : '';
			}
		}

		// Label color.
		$label_color = array();
		$raw_color   = isset( $data['label_color'] ) && is_array( $data['label_color'] ) ? $data['label_color'] : array();
		if ( ! empty( $raw_color ) ) {
			$allowed_types = array( 'default', 'custom' );
			$color_type    = isset( $raw_color['type'] ) && in_array( $raw_color['type'], $allowed_types, true )
				? $raw_color['type']
				: 'default';

			$label_color = array( 'type' => $color_type );

			if ( 'custom' === $color_type ) {
				$label_color['background_color'] = isset( $raw_color['background_color'] )
					? sanitize_hex_color( $raw_color['background_color'] )
					: '';
				$label_color['text_color']       = isset( $raw_color['text_color'] )
					? sanitize_hex_color( $raw_color['text_color'] )
					: '';
			}
		}

		update_post_meta( $post_id, '_bp_group_type_label_singular_name', $singular_label );
		update_post_meta( $post_id, '_bp_group_type_label_name', $plural_label );
		update_post_meta( $post_id, '_bp_group_type_enable_filter', $enable_filter );
		update_post_meta( $post_id, '_bp_group_type_enable_remove', $enable_remove );
		update_post_meta( $post_id, '_bp_group_type_restrict_invites_user_same_group_type', $restrict_invites );
		update_post_meta( $post_id, '_bp_group_type_enabled_member_type_join', $member_type_join );
		update_post_meta( $post_id, '_bp_group_type_enabled_member_type_group_invites', $member_type_invites );

		update_post_meta( $post_id, '_bp_group_type_role_labels', $role_labels );
		update_post_meta( $post_id, '_bp_group_type_label_color', $label_color );
	}

	/**
	 * Normalize legacy flat role labels to nested format for the JS modal.
	 *
	 * Legacy stores: { organizer_plural_label_name: '', organizer_singular_label_name: '', ... }
	 * JS expects:    { organizer: { plural: '', singular: '' }, ... }
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param mixed $raw Role labels meta value.
	 *
	 * @return array Nested role labels array.
	 */
	private function bb_normalize_role_labels( $raw ) {
		$default = array(
			'organizer' => array(
				'plural'   => '',
				'singular' => '',
			),
			'moderator' => array(
				'plural'   => '',
				'singular' => '',
			),
			'member'    => array(
				'plural'   => '',
				'singular' => '',
			),
		);

		if ( ! is_array( $raw ) || empty( $raw ) ) {
			return $default;
		}

		$roles  = array( 'organizer', 'moderator', 'member' );
		$result = array();
		foreach ( $roles as $role ) {
			// Legacy flat format: organizer_plural_label_name, organizer_singular_label_name.
			if ( isset( $raw[ $role . '_plural_label_name' ] ) || isset( $raw[ $role . '_singular_label_name' ] ) ) {
				$result[ $role ] = array(
					'plural'   => isset( $raw[ $role . '_plural_label_name' ] ) ? sanitize_text_field( $raw[ $role . '_plural_label_name' ] ) : '',
					'singular' => isset( $raw[ $role . '_singular_label_name' ] ) ? sanitize_text_field( $raw[ $role . '_singular_label_name' ] ) : '',
				);
			} elseif ( isset( $raw[ $role ] ) && is_array( $raw[ $role ] ) ) {
				// Nested format (pre-fix Settings 2.0 saves).
				$result[ $role ] = array(
					'plural'   => isset( $raw[ $role ]['plural'] ) ? sanitize_text_field( $raw[ $role ]['plural'] ) : '',
					'singular' => isset( $raw[ $role ]['singular'] ) ? sanitize_text_field( $raw[ $role ]['singular'] ) : '',
				);
			} else {
				$result[ $role ] = $default[ $role ];
			}
		}

		return $result;
	}

	/**
	 * Get available member/profile types for the group type modal.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array Array of member types with id and name.
	 */
	private function bb_get_available_member_types() {
		$member_types = array();

		if ( ! function_exists( 'bp_get_active_member_types' ) ) {
			return $member_types;
		}

		$member_type_ids = bp_get_active_member_types();

		if ( empty( $member_type_ids ) ) {
			return $member_types;
		}

		// Prime post cache in a single query to avoid N+1 lookups.
		// _prime_post_caches() is public since WP 6.1; guard for WP 6.0 compat.
		if ( function_exists( '_prime_post_caches' ) ) {
			_prime_post_caches( $member_type_ids );
		}

		// Prime post meta cache to avoid N+1 for _bp_member_type_key lookups.
		update_postmeta_cache( $member_type_ids );

		foreach ( $member_type_ids as $mt_id ) {
			$member_type_key = get_post_meta( $mt_id, '_bp_member_type_key', true );

			// Skip if no key found — shouldn't happen but be safe.
			if ( empty( $member_type_key ) ) {
				continue;
			}

			$member_types[] = array(
				'id'   => sanitize_key( $member_type_key ),
				'name' => get_the_title( $mt_id ),
			);
		}

		return $member_types;
	}

	/**
	 * Clear admin status counts cache.
	 *
	 * Hooked to groups_delete_group, groups_create_group, groups_settings_updated
	 * so the status tab counts stay accurate.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function bb_clear_status_counts_cache() {
		wp_cache_delete( 'bb_admin_groups_status_counts', 'bp_groups' );
	}

	/**
	 * Clear group type caches after create/update.
	 *
	 * The legacy cache clearing hook bb_groups_clear_group_type_cache_on_update()
	 * checks for the legacy nonce $_POST['_bp-group-type-nonce'] which is not present
	 * in Settings 2.0 AJAX requests. We replicate the same cache clearing here.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @see bb_groups_clear_group_type_cache_on_update() Legacy cache clearing (save_post hook).
	 * @see bb_groups_clear_group_type_cache_before_delete() Delete cache clearing (before_delete_post hook) — works automatically.
	 *
	 * @param int $post_id Post ID of the group type.
	 */
	private function bb_clear_group_type_cache( $post_id ) {
		wp_cache_delete( 'bb-group-type-label-css', 'bp_groups_group_type' );
		wp_cache_delete( 'bp_group_types', 'bp_groups' ); // Clear group type registry cache populated by bp_groups_get_group_types().

		$type_key = get_post_meta( $post_id, '_bp_group_type_key', true );
		if ( ! empty( $type_key ) ) {
			wp_cache_delete( 'bb-group-type-label-color-' . $type_key, 'bp_groups_group_type' );
		}
	}

	/**
	 * Validate a group permission field against the allowed values whitelist.
	 *
	 * Reads the value from $_POST, sanitizes it, and validates against
	 * bb_groups_get_settings_status(). If the value is not in the allowed list,
	 * falls back via bb_groups_settings_default_fallback() — matching the
	 * legacy admin pattern (bp-groups-admin.php).
	 *
	 * Returns false when the field is not present in $_POST, which tells
	 * groups_edit_group_settings() to skip updating that field.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $field_key    The POST field key suffix (e.g., 'perm_invite').
	 * @param string $setting_type The setting type for bb_groups_get_settings_status() (e.g., 'invite').
	 *
	 * @return string|false Validated permission value or false if field not submitted.
	 */
	private function bb_validate_group_permission_field( $field_key, $setting_type ) {
		$post_key = 'registered_field_' . $field_key;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request().
		if ( ! isset( $_POST[ $post_key ] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request().
		$value          = sanitize_key( wp_unslash( $_POST[ $post_key ] ) );
		$allowed_values = bb_groups_get_settings_status( $setting_type );

		if ( in_array( $value, (array) $allowed_values, true ) ) {
			return $value;
		}

		return bb_groups_settings_default_fallback( $setting_type, current( (array) $allowed_values ) );
	}
}

// Initialize.
new BB_Admin_Groups_Ajax();
