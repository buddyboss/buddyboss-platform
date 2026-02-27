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
	 * Get the allowlisted platform settings options.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array Allowed option names.
	 */
	private function bb_get_allowed_platform_options() {
		/**
		 * Filters the allowed platform settings options for AJAX read/write.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $allowed_options Array of allowed option names.
		 */
		return apply_filters(
			'bb_admin_allowed_platform_settings',
			array(
				'bp-disable-group-type-creation',
				'bp-enable-group-auto-join',
			)
		);
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
				'post_type'      => $post_type,
				'posts_per_page' => 500, // Sanity cap — UI cannot usefully display more.
				'post_status'    => array( 'publish', 'private' ),
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		// Batch-load post meta to avoid N+1 queries.
		if ( ! empty( $posts ) ) {
			$post_ids = wp_list_pluck( $posts, 'ID' );
			update_postmeta_cache( $post_ids );
		}

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

		wp_send_json_success(
			array(
				'group_types'  => $group_types,
				'member_types' => $member_types,
			)
		);
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
		$visibility = isset( $_POST['visibility'] ) ? sanitize_key( $_POST['visibility'] ) : 'public';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

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
		$this->bb_save_group_type_meta( $post_id, $name );

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
		$type_id    = isset( $_POST['type_id'] ) ? absint( $_POST['type_id'] ) : 0;
		$name       = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$visibility = isset( $_POST['visibility'] ) ? sanitize_key( $_POST['visibility'] ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

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
		$this->bb_save_group_type_meta( $type_id, $post_title );

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
		$type_id = isset( $_POST['type_id'] ) ? absint( $_POST['type_id'] ) : 0;
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
			if ( in_array( $option_name, $allowed, true ) ) {
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
		$option_name  = isset( $_POST['option_name'] ) ? sanitize_text_field( wp_unslash( $_POST['option_name'] ) ) : '';
		$option_value = isset( $_POST['option_value'] ) ? absint( wp_unslash( $_POST['option_value'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $option_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Option name is required.', 'buddyboss' ) ) );
		}

		$allowed = $this->bb_get_allowed_platform_options();

		if ( ! in_array( $option_name, $allowed, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Option not allowed.', 'buddyboss' ) ) );
		}

		bp_update_option( $option_name, $option_value );

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

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$page         = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page     = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;
		$search       = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$status       = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'all';
		$sort         = isset( $_POST['sort'] ) ? sanitize_text_field( wp_unslash( $_POST['sort'] ) ) : 'newest';
		$group_type   = isset( $_POST['group_type'] ) ? sanitize_text_field( wp_unslash( $_POST['group_type'] ) ) : '';
		$include_meta = isset( $_POST['include_meta'] ) ? absint( $_POST['include_meta'] ) : 0;
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
		$per_page = max( 1, min( 100, $per_page ) );
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
				'description'   => wp_trim_words( wp_strip_all_tags( apply_filters( 'bp_get_group_description', $group->description, $group ) ), 20 ),
				'status'        => $group->status,
				'status_label'  => $status_desc,
				'date_created'  => $group->date_created,
				'last_activity' => $last_activity,
				'total_members' => $member_count,
				'creator_id'    => (int) $group->creator_id,
				'group_type'    => ! empty( $group_type_map[ $group->id ] ) && isset( $type_labels[ $group_type_map[ $group->id ] ] )
				? $type_labels[ $group_type_map[ $group->id ] ]
				: '',
				'avatar_url'    => bp_core_fetch_avatar(
					array(
						'item_id'    => $group->id,
						'avatar_dir' => 'group-avatars',
						'object'     => 'group',
						'type'       => 'thumb',
						'html'       => false,
					)
				),
				'edit_url'      => bp_get_admin_url( 'admin.php?page=bp-groups&action=edit&gid=' . $group->id ),
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
			// Get counts per status with a single query instead of 4 separate groups_get_groups() calls.
			global $wpdb;
			$bp = buddypress();
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from BuddyPress internals, not user input.
			$status_counts = $wpdb->get_results(
				"SELECT status, COUNT(id) AS cnt FROM {$bp->groups->table_name} GROUP BY status"
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			$counts_map = array();
			$count_all  = 0;
			foreach ( $status_counts as $row ) {
				$counts_map[ $row->status ] = (int) $row->cnt;
				$count_all                 += (int) $row->cnt;
			}

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
		$group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;

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
		$group_ids = array_slice( $group_ids, 0, 100 );

		$processed = 0;
		$errors    = 0;

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
					++$errors;
				}
			} elseif ( 'change_group_type' === $do_action ) {
				$result = bp_groups_set_group_type( $group_id, $new_group_type );
				if ( ! is_wp_error( $result ) && false !== $result ) {
					++$processed;
				} else {
					++$errors;
				}
			} elseif ( 'remove_group_type' === $do_action ) {
				$current_type = bp_groups_get_group_type( $group_id, true );
				if ( ! empty( $current_type ) ) {
					$result = bp_groups_remove_group_type( $group_id, $current_type );
					if ( ! is_wp_error( $result ) && false !== $result ) {
						++$processed;
					} else {
						++$errors;
					}
				} else {
					// No type to remove; count as processed.
					++$processed;
				}
			}
		}

		if ( $processed > 0 ) {
			$message = '';
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
			}

			wp_send_json_success( array( 'message' => $message ) );
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
		$group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;

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
		ob_start();
		do_action( 'bp_groups_admin_meta_boxes' );
		ob_end_clean();

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

		wp_send_json_success( $data );
	}

	/**
	 * Save group data from the edit modal.
	 *
	 * Flow:
	 * 1. save_fields_data('groups', $group, 'before') — sets object properties.
	 * 2. groups_edit_base_group_details() — fires groups_details_updated + cache.
	 * 3. groups_edit_group_settings() — handles privacy + permissions + cache.
	 * 4. save_fields_data('groups', $group, 'after') — saves group_type + Pro fields.
	 * 5. do_action('bp_group_admin_edit_after', $group_id) — third-party compat.
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
		$group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;

		if ( empty( $group_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Group ID is required.', 'buddyboss' ) ) );
		}

		$group = groups_get_group( $group_id );
		if ( empty( $group->id ) ) {
			wp_send_json_error( array( 'message' => __( 'Group not found.', 'buddyboss' ) ) );
		}

		$registry = bb_admin_meta_field_registry();

		// Capture original slug before Phase 1 modifies the group object.
		$old_slug = $group->slug;

		// Phase 1: "before" fields set properties on the group object.
		$registry->save_fields_data( 'groups', $group, 'before' );

		$new_slug = $group->slug;

		// Phase 2: Save base group details (name, slug, description, parent).
		$details_saved = groups_edit_base_group_details(
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

		// Phase 3: Save group settings (status, permissions, forum).
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$status          = isset( $_POST['registered_field_status'] ) ? sanitize_key( wp_unslash( $_POST['registered_field_status'] ) ) : $group->status;
		$enable_forum    = isset( $_POST['registered_field_enable_forum'] ) ? absint( wp_unslash( $_POST['registered_field_enable_forum'] ) ) : (int) $group->enable_forum;
		$invite_status   = isset( $_POST['registered_field_perm_invite'] ) ? sanitize_key( wp_unslash( $_POST['registered_field_perm_invite'] ) ) : false;
		$activity_status = isset( $_POST['registered_field_perm_activity_feed'] ) ? sanitize_key( wp_unslash( $_POST['registered_field_perm_activity_feed'] ) ) : false;
		$media_status    = isset( $_POST['registered_field_perm_media'] ) ? sanitize_key( wp_unslash( $_POST['registered_field_perm_media'] ) ) : false;
		$album_status    = isset( $_POST['registered_field_perm_album'] ) ? sanitize_key( wp_unslash( $_POST['registered_field_perm_album'] ) ) : false;
		$document_status = isset( $_POST['registered_field_perm_document'] ) ? sanitize_key( wp_unslash( $_POST['registered_field_perm_document'] ) ) : false;
		$message_status  = isset( $_POST['registered_field_perm_message'] ) ? sanitize_key( wp_unslash( $_POST['registered_field_perm_message'] ) ) : false;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Validate group status against allowed values, matching create_group() pattern.
		$allowed_statuses = apply_filters( 'groups_allowed_status', array( 'public', 'private', 'hidden' ) );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = $group->status; // Fallback to current status.
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$video_status = isset( $_POST['registered_field_perm_video'] ) ? sanitize_key( wp_unslash( $_POST['registered_field_perm_video'] ) ) : false;

		// Fallback to existing value when video field is not visible (feature disabled).
		if ( false === $video_status ) {
			$video_status = groups_get_groupmeta( $group_id, 'video_status' );
			if ( empty( $video_status ) ) {
				$video_status = false;
			}
		}

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

		// Phase 4: "after" fields save group type, Pro fields, etc.
		$registry->save_fields_data( 'groups', $group, 'after' );

		/**
		 * Fires after a group is saved from the admin edit modal.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $group_id ID of the group being edited.
		 */
		do_action( 'bp_group_admin_edit_after', $group_id );

		wp_send_json_success(
			array( 'message' => __( 'Group updated successfully.', 'buddyboss' ) )
		);
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

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
		$page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 10;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $group_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Group ID is required.', 'buddyboss' ) ) );
		}

		$group = groups_get_group( $group_id );
		if ( empty( $group->id ) ) {
			wp_send_json_error( array( 'message' => __( 'Group not found.', 'buddyboss' ) ) );
		}

		$per_page = max( 1, min( 100, $per_page ) );
		$page     = max( 1, $page );

		/**
		 * Filters the number of group members displayed per page in the admin edit modal.
		 *
		 * Mirrors the legacy `bp_groups_admin_members_type_per_page` filter used by
		 * bp_groups_admin_edit_metabox_members(). The second parameter is an empty string
		 * because the Settings 2.0 interface does not filter by member type.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int    $per_page    Number of members per page.
		 * @param string $member_type Member type slug, or empty string if not filtering by type.
		 */
		$per_page = (int) apply_filters( 'bp_groups_admin_members_type_per_page', $per_page, '' );
		$per_page = max( 1, min( 100, $per_page ) ); // Re-clamp after filter.

		$members_data = groups_get_group_members(
			array(
				'group_id'            => $group_id,
				'per_page'            => $per_page,
				'page'                => $page,
				'exclude_admins_mods' => false,
				'exclude_banned'      => false,
			)
		);

		// Prime user cache.
		if ( ! empty( $members_data['members'] ) ) {
			$user_ids = wp_list_pluck( $members_data['members'], 'user_id' );
			cache_users( $user_ids );
		}

		$members = array();
		foreach ( $members_data['members'] as $member ) {
			// Determine role.
			$role = 'member';
			if ( $member->is_admin ) {
				$role = 'admin';
			} elseif ( $member->is_mod ) {
				$role = 'mod';
			} elseif ( $member->is_banned ) {
				$role = 'banned';
			}

			$members[] = array(
				'user_id'    => (int) $member->user_id,
				'name'       => bp_core_get_user_displayname( $member->user_id ),
				'avatar_url' => bp_core_fetch_avatar(
					array(
						'item_id' => $member->user_id,
						'object'  => 'user',
						'type'    => 'thumb',
						'html'    => false,
					)
				),
				'role'       => $role,
				'is_creator' => ( (int) $member->user_id === (int) $group->creator_id ),
			);
		}

		wp_send_json_success(
			array(
				'members' => $members,
				'total'   => (int) $members_data['count'],
			)
		);
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
		$group_id    = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
		$user_id     = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
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
			$result = groups_join_group( $group_id, $user_id );
			if ( $result ) {
				wp_send_json_success( array( 'message' => __( 'Member added successfully.', 'buddyboss' ) ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Failed to add member.', 'buddyboss' ) ) );
			}
			return;
		}

		// Remove member.
		if ( 'remove' === $role ) {
			$result = groups_remove_member( $user_id, $group_id );
			if ( $result ) {
				wp_send_json_success( array( 'message' => __( 'Member removed successfully.', 'buddyboss' ) ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Failed to remove member.', 'buddyboss' ) ) );
			}
			return;
		}

		// Role changes.
		$allowed_roles = array( 'admin', 'mod', 'member', 'banned' );
		if ( ! in_array( $role, $allowed_roles, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid role.', 'buddyboss' ) ) );
		}

		// Unban first if currently banned and changing to a non-banned role.
		$member_obj = new BP_Groups_Member( $user_id, $group_id );
		if ( $member_obj->is_banned && 'banned' !== $role ) {
			groups_unban_member( $user_id, $group_id );
		}

		$result = false;
		switch ( $role ) {
			case 'admin':
			case 'mod':
				$result = groups_promote_member( $user_id, $group_id, $role );
				break;
			case 'member':
				$result = groups_demote_member( $user_id, $group_id );
				break;
			case 'banned':
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
		$group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
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
		$group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;

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
					'per_page'  => 500, // Sanity cap to prevent memory exhaustion.
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

		wp_send_json_success(
			array(
				'topics'           => $topics_data,
				'topic_mode'       => $topic_mode,
				'permission_types' => $permission_types,
				'global_topics'    => $global_topics,
				'max_topics'       => (int) $max_topics,
				'nonces'           => array(
					'add'     => wp_create_nonce( 'bb_add_topic' ),
					'delete'  => wp_create_nonce( 'bb_delete_topic' ),
					'reorder' => wp_create_nonce( 'bb_update_topics_order' ),
					'migrate' => wp_create_nonce( 'bb_migrate_topic' ),
				),
			)
		);
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
	 */
	private function bb_save_group_type_meta( $post_id, $post_title = '' ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$singular_label = isset( $_POST['singular_label'] ) ? wp_kses( wp_unslash( $_POST['singular_label'] ), wp_kses_allowed_html( 'strip' ) ) : '';
		$plural_label   = isset( $_POST['plural_label'] ) ? wp_kses( wp_unslash( $_POST['plural_label'] ), wp_kses_allowed_html( 'strip' ) ) : '';

		// Default labels to post_title when empty, same as legacy bp_save_group_type_post_meta_box_data().
		$singular_label = ! empty( trim( $singular_label ) ) ? trim( $singular_label ) : $post_title;
		$plural_label   = ! empty( trim( $plural_label ) ) ? trim( $plural_label ) : $post_title;
		$enable_filter  = isset( $_POST['enable_filter'] ) ? absint( wp_unslash( $_POST['enable_filter'] ) ) : 0;
		$enable_remove  = isset( $_POST['enable_remove'] ) ? absint( wp_unslash( $_POST['enable_remove'] ) ) : 0;

		$restrict_invites = isset( $_POST['restrict_invites'] ) ? absint( wp_unslash( $_POST['restrict_invites'] ) ) : 0;

		// Member type fields: empty string = "all", "none" = empty array (no types), array of IDs = "selected".
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_member_type_join = isset( $_POST['member_type_join'] ) ? wp_unslash( $_POST['member_type_join'] ) : '';
		if ( is_array( $raw_member_type_join ) ) {
			$member_type_join = array_map( 'sanitize_key', $raw_member_type_join );
		} elseif ( 'none' === $raw_member_type_join ) {
			$member_type_join = array();
		} else {
			$member_type_join = '';
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_member_type_invites = isset( $_POST['member_type_invites'] ) ? wp_unslash( $_POST['member_type_invites'] ) : '';
		if ( is_array( $raw_member_type_invites ) ) {
			$member_type_invites = array_map( 'sanitize_key', $raw_member_type_invites );
		} elseif ( 'none' === $raw_member_type_invites ) {
			$member_type_invites = array();
		} else {
			$member_type_invites = '';
		}

		// Role labels — stored in legacy flat format: organizer_plural_label_name, etc.
		$role_labels     = array();
		$raw_role_labels = isset( $_POST['role_labels'] ) && is_array( $_POST['role_labels'] )
			? wp_unslash( $_POST['role_labels'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below per key/value.
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
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below per field.
		$raw_color = isset( $_POST['label_color'] ) && is_array( $_POST['label_color'] ) ? wp_unslash( $_POST['label_color'] ) : array();
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
		// phpcs:enable WordPress.Security.NonceVerification.Missing

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
		_prime_post_caches( $member_type_ids );

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

		$type_key = get_post_meta( $post_id, '_bp_group_type_key', true );
		if ( ! empty( $type_key ) ) {
			wp_cache_delete( 'bb-group-type-label-color-' . $type_key, 'bp_groups_group_type' );
		}
	}
}

// Initialize.
new BB_Admin_Groups_Ajax();
