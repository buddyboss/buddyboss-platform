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
	}

	/**
	 * Verify AJAX request (nonce + capability).
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function bb_verify_request() {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Security check failed.', 'buddyboss' ) ),
				403
			);
		}

		if ( ! bp_current_user_can( 'bp_moderate' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied.', 'buddyboss' ) ),
				403
			);
		}

		return true;
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
				'posts_per_page' => -1,
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

		// Get group counts per type in a single query.
		$group_counts = $this->bb_get_group_counts_by_type();

		$group_types = array();

		foreach ( $posts as $post ) {
			$post_id  = $post->ID;
			$type_key = get_post_meta( $post_id, '_bp_group_type_key', true );

			$group_types[] = array(
				'id'                  => $post_id,
				'name'                => esc_html( $type_key ),
				'post_title'          => esc_html( $post->post_title ),
				'singular_label'      => esc_html( get_post_meta( $post_id, '_bp_group_type_label_singular_name', true ) ),
				'plural_label'        => esc_html( get_post_meta( $post_id, '_bp_group_type_label_name', true ) ),
				'groups_count'        => isset( $group_counts[ $type_key ] ) ? (int) $group_counts[ $type_key ] : 0,
				'enable_filter'       => absint( get_post_meta( $post_id, '_bp_group_type_enable_filter', true ) ),
				'enable_remove'       => absint( get_post_meta( $post_id, '_bp_group_type_enable_remove', true ) ),
				'role_labels'         => get_post_meta( $post_id, '_bp_group_type_role_labels', true ),
				'label_color'         => get_post_meta( $post_id, '_bp_group_type_label_color', true ),
				'restrict_invites'    => absint( get_post_meta( $post_id, '_bp_group_type_restrict_invites_user_same_group_type', true ) ),
				'member_type_join'    => get_post_meta( $post_id, '_bp_group_type_enabled_member_type_join', true ),
				'member_type_invites' => get_post_meta( $post_id, '_bp_group_type_enabled_member_type_group_invites', true ),
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
	 * Get group counts per type using WordPress-maintained term_taxonomy.count column.
	 *
	 * Uses the same approach as bp_group_get_count_by_group_type() but fetches
	 * all types in a single query instead of one per type.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array Type slug => count.
	 */
	private function bb_get_group_counts_by_type() {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.slug, tt.count
				FROM {$wpdb->term_taxonomy} tt
				LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
				WHERE tt.taxonomy = %s",
				'bp_group_type'
			),
			OBJECT
		);

		$counts = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$counts[ $row->slug ] = (int) $row->count;
			}
		}

		return $counts;
	}

	/**
	 * Create a new group type.
	 *
	 * Mirrors legacy bp_save_group_type_post_meta_box_data() for key generation,
	 * label defaults, and cache clearing.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function create_group_type() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'groups' ) ) {
			wp_send_json_error( array( 'message' => __( 'Groups component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$name       = isset( $_POST['name'] ) ? wp_kses( wp_unslash( $_POST['name'] ), wp_kses_allowed_html( 'strip' ) ) : '';
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
			wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
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
	 */
	public function update_group_type() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'groups' ) ) {
			wp_send_json_error( array( 'message' => __( 'Groups component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$type_id    = isset( $_POST['type_id'] ) ? absint( $_POST['type_id'] ) : 0;
		$name       = isset( $_POST['name'] ) ? wp_kses( wp_unslash( $_POST['name'] ), wp_kses_allowed_html( 'strip' ) ) : '';
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
		$post_title  = ! empty( $name ) ? $name : $post->post_title;
		$update_args = array( 'ID' => $type_id );
		$needs_update = false;

		if ( ! empty( $name ) ) {
			$update_args['post_title'] = $name;
			$needs_update = true;
		}

		if ( ! empty( $visibility ) ) {
			$update_args['post_status'] = 'private' === $visibility ? 'private' : 'publish';
			$needs_update = true;
		}

		if ( $needs_update ) {
			wp_update_post( $update_args );
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
	 */
	public function delete_group_type() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'groups' ) ) {
			wp_send_json_error( array( 'message' => __( 'Groups component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
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
	 */
	public function get_platform_settings() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing
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
	 */
	public function save_platform_setting() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$option_name  = isset( $_POST['option_name'] ) ? sanitize_text_field( wp_unslash( $_POST['option_name'] ) ) : '';
		$option_value = isset( $_POST['option_value'] ) ? sanitize_text_field( wp_unslash( $_POST['option_value'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $option_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Option name is required.', 'buddyboss' ) ) );
		}

		$allowed = $this->bb_get_allowed_platform_options();

		if ( ! in_array( $option_name, $allowed, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Option not allowed.', 'buddyboss' ) ) );
		}

		$sanitized_value = intval( $option_value );
		bp_update_option( $option_name, $sanitized_value );

		wp_send_json_success(
			array( 'message' => __( 'Setting saved successfully.', 'buddyboss' ) )
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
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$singular_label = isset( $_POST['singular_label'] ) ? wp_kses( wp_unslash( $_POST['singular_label'] ), wp_kses_allowed_html( 'strip' ) ) : '';
		$plural_label   = isset( $_POST['plural_label'] ) ? wp_kses( wp_unslash( $_POST['plural_label'] ), wp_kses_allowed_html( 'strip' ) ) : '';

		// Default labels to post_title when empty, same as legacy bp_save_group_type_post_meta_box_data().
		$singular_label = ! empty( trim( $singular_label ) ) ? trim( $singular_label ) : $post_title;
		$plural_label   = ! empty( trim( $plural_label ) ) ? trim( $plural_label ) : $post_title;
		$enable_filter  = isset( $_POST['enable_filter'] ) ? absint( $_POST['enable_filter'] ) : 0;
		$enable_remove  = isset( $_POST['enable_remove'] ) ? absint( $_POST['enable_remove'] ) : 0;

		$restrict_invites = isset( $_POST['restrict_invites'] ) ? absint( $_POST['restrict_invites'] ) : 0;

		// Member type fields: empty string = "all", array of IDs = "selected".
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_member_type_join = isset( $_POST['member_type_join'] ) ? $_POST['member_type_join'] : '';
		if ( is_array( $raw_member_type_join ) ) {
			$member_type_join = array_map( 'sanitize_key', $raw_member_type_join );
		} else {
			$member_type_join = '';
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_member_type_invites = isset( $_POST['member_type_invites'] ) ? $_POST['member_type_invites'] : '';
		if ( is_array( $raw_member_type_invites ) ) {
			$member_type_invites = array_map( 'sanitize_key', $raw_member_type_invites );
		} else {
			$member_type_invites = '';
		}

		// Role labels.
		$role_labels = array();
		if ( isset( $_POST['role_labels'] ) && is_array( $_POST['role_labels'] ) ) {
			foreach ( $_POST['role_labels'] as $role_key => $labels ) {
				$sanitized_key = sanitize_key( $role_key );
				if ( is_array( $labels ) ) {
					$role_labels[ $sanitized_key ] = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $labels ) );
				}
			}
		}

		// Label color.
		$label_color = array();
		if ( isset( $_POST['label_color'] ) && is_array( $_POST['label_color'] ) ) {
			$raw_color      = $_POST['label_color'];
			$allowed_types  = array( 'default', 'custom' );
			$color_type     = isset( $raw_color['type'] ) && in_array( $raw_color['type'], $allowed_types, true )
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

		if ( ! empty( $role_labels ) ) {
			update_post_meta( $post_id, '_bp_group_type_role_labels', $role_labels );
		}

		if ( ! empty( $label_color ) ) {
			update_post_meta( $post_id, '_bp_group_type_label_color', $label_color );
		}
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

		foreach ( $member_type_ids as $mt_id ) {
			$member_types[] = array(
				'id'   => (string) $mt_id,
				'name' => esc_html( get_the_title( $mt_id ) ),
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
