<?php
/**
 * BuddyBoss Member Types Admin AJAX Handler
 *
 * Handles AJAX requests for Profile/Member Types CRUD
 * in the Settings 2.0 admin interface.
 *
 * @since   BuddyBoss 3.0.0
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Member_Types_Ajax
 *
 * @since BuddyBoss 3.0.0
 */
class BB_Admin_Member_Types_Ajax {

	/**
	 * Nonce action (shared with BB_Admin_Settings_Ajax).
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'bb_admin_settings';

	/**
	 * Verify AJAX request (capability + nonce).
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	private function bb_verify_request() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );
	}

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
		add_action( 'wp_ajax_bb_admin_get_member_types', array( $this, 'get_member_types' ) );
		add_action( 'wp_ajax_bb_admin_create_member_type', array( $this, 'create_member_type' ) );
		add_action( 'wp_ajax_bb_admin_update_member_type', array( $this, 'update_member_type' ) );
		add_action( 'wp_ajax_bb_admin_delete_member_type', array( $this, 'delete_member_type' ) );

		// Extend platform settings allowlist for profile type settings.
		add_filter( 'bb_admin_allowed_platform_settings', array( $this, 'bb_extend_allowed_platform_settings' ) );
	}

	/**
	 * Add profile type options to the platform settings allowlist.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $allowed Allowed option names.
	 * @return array Extended allowed option names.
	 */
	public function bb_extend_allowed_platform_settings( $allowed ) {
		$allowed['bp-member-type-enable-disable']          = 'absint';
		$allowed['bp-member-type-display-on-profile']      = 'absint';
		$allowed['bp-member-type-default-on-registration'] = 'sanitize_text_field';

		return $allowed;
	}

	/**
	 * Get all member/profile types with meta.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function get_member_types() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss-platform' ) ) );
		}

		// Admin listing order (per design spec): private items first,
		// then oldest → newest within each visibility group so newly
		// added types fall to the END of their group. The callback is
		// defined as a named function in `bp-members-filters.php`
		// (`bb_admin_member_types_listing_orderby`) so it's
		// `remove_filter()`-able by string reference. See that function's
		// docblock for the full rationale.
		add_filter( 'posts_orderby', 'bb_admin_member_types_listing_orderby', 10, 2 );

		$member_type_ids = bp_get_active_member_types(
			array(
				'post_status' => array( 'publish', 'private', 'draft' ),
			)
		);

		remove_filter( 'posts_orderby', 'bb_admin_member_types_listing_orderby', 10 );

		if ( empty( $member_type_ids ) ) {
			$response = array(
				'member_types' => array(),
			);

			// Include group types if groups component is active.
			if ( bp_is_active( 'groups' ) ) {
				$response['group_types'] = $this->bb_get_available_group_types();
			}

			$response['wp_roles'] = $this->bb_get_wp_roles();

			// Include published pages for redirection dropdowns (Create modal needs them).
			$response['published_pages'] = bb_get_published_pages( true );

			wp_send_json_success( $response );
		}

		// Batch-load post meta to avoid N+1 queries.
		update_postmeta_cache( $member_type_ids );

		// _prime_post_caches() is public since WP 6.1; guard for WP 6.0 compat.
		if ( function_exists( '_prime_post_caches' ) ) {
			_prime_post_caches( $member_type_ids );
		}

		// Get member counts per type — cached to avoid repeated taxonomy queries.
		$cache_key     = 'bb_admin_member_type_counts';
		$member_counts = wp_cache_get( $cache_key, 'bp_member_type' );

		if ( false === $member_counts ) {
			global $wpdb;
			$count_rows    = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT t.slug, tt.count FROM {$wpdb->term_taxonomy} tt LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id WHERE tt.taxonomy = %s",
					bp_get_member_type_tax_name()
				)
			);
			$member_counts = array();
			foreach ( $count_rows as $row ) {
				$member_counts[ $row->slug ] = (int) $row->count;
			}
			wp_cache_set( $cache_key, $member_counts, 'bp_member_type', HOUR_IN_SECONDS );
		}

		$member_types = array();

		foreach ( $member_type_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				continue;
			}

			$type_key    = get_post_meta( $post_id, '_bp_member_type_key', true );
			$label_color = get_post_meta( $post_id, '_bp_member_type_label_color', true );

			// Normalize label_color to array.
			if ( ! is_array( $label_color ) ) {
				$label_color = array( 'type' => 'default' );
			}

			// Determine visibility from post_status.
			$visibility = 'publish';
			if ( 'private' === $post->post_status ) {
				$visibility = 'private';
			} elseif ( 'draft' === $post->post_status ) {
				$visibility = 'draft';
			} elseif ( ! empty( $post->post_password ) ) {
				$visibility = 'password_protected';
			}

			// Get group type create permissions.
			$group_type_create = get_post_meta( $post_id, '_bp_member_type_enabled_group_type_create', true );
			if ( ! is_array( $group_type_create ) ) {
				$group_type_create = ! empty( $group_type_create ) ? array( $group_type_create ) : array();
			}

			// Get group type auto join.
			$group_type_auto_join = get_post_meta( $post_id, '_bp_member_type_enabled_group_type_auto_join', true );
			if ( ! is_array( $group_type_auto_join ) ) {
				$group_type_auto_join = ! empty( $group_type_auto_join ) ? array( $group_type_auto_join ) : array();
			}

			// Get WP roles.
			$wp_roles_meta = get_post_meta( $post_id, '_bp_member_type_wp_roles', true );
			if ( ! is_array( $wp_roles_meta ) ) {
				$wp_roles_meta = ! empty( $wp_roles_meta ) ? array( $wp_roles_meta ) : array();
			}

			// Get Email Invites allowed member types.
			$invite_member_types = get_post_meta( $post_id, '_bp_member_type_allowed_member_type_invite', true );
			if ( ! is_array( $invite_member_types ) ) {
				$invite_member_types = ! empty( $invite_member_types ) ? array( $invite_member_types ) : array();
			}

			$member_types[] = array(
				'id'                                 => $post_id,
				'post_title'                         => $post->post_title,
				'key'                                => $type_key,
				'singular_label'                     => get_post_meta( $post_id, '_bp_member_type_label_singular_name', true ),
				'plural_label'                       => get_post_meta( $post_id, '_bp_member_type_label_name', true ),
				'members_count'                      => isset( $member_counts[ $type_key ] ) ? (int) $member_counts[ $type_key ] : 0,
				'enable_filter'                      => absint( get_post_meta( $post_id, '_bp_member_type_enable_filter', true ) ),
				'enable_remove'                      => absint( get_post_meta( $post_id, '_bp_member_type_enable_remove', true ) ),
				'enable_search_remove'               => absint( get_post_meta( $post_id, '_bp_member_type_enable_search_remove', true ) ),
				'enable_profile_field'               => absint( get_post_meta( $post_id, '_bp_member_type_enable_profile_field', true ) ),
				'group_type_create'                  => array_map( 'sanitize_text_field', $group_type_create ),
				'group_type_auto_join'               => array_map( 'sanitize_text_field', $group_type_auto_join ),
				'wp_roles'                           => array_map( 'sanitize_text_field', $wp_roles_meta ),
				'label_color'                        => map_deep( $label_color, 'sanitize_text_field' ),
				'login_redirection'                  => sanitize_key( get_post_meta( $post_id, '_bp_member_type_login_redirection', true ) ),
				'custom_login_redirection'           => esc_url_raw( get_post_meta( $post_id, '_bp_member_type_custom_login_redirection', true ) ),
				'logout_redirection'                 => sanitize_key( get_post_meta( $post_id, '_bp_member_type_logout_redirection', true ) ),
				'custom_logout_redirection'          => esc_url_raw( get_post_meta( $post_id, '_bp_member_type_custom_logout_redirection', true ) ),
				'visibility'                         => $visibility,
				'has_password'                       => ! empty( $post->post_password ),
				'allow_messaging_without_connection' => absint( get_post_meta( $post_id, '_bp_member_type_allow_messaging_without_connection', true ) ),
				'invite_member_types'                => array_map( 'absint', $invite_member_types ),
			);
		}

		$response = array(
			'member_types' => $member_types,
		);

		// Include group types if groups component is active.
		if ( bp_is_active( 'groups' ) ) {
			$response['group_types'] = $this->bb_get_available_group_types();
		}

		$response['wp_roles'] = $this->bb_get_wp_roles();

		// Include published pages for redirection dropdowns.
		$response['published_pages'] = bb_get_published_pages( true );

		wp_send_json_success( $response );
	}

	/**
	 * Create a new member/profile type.
	 *
	 * Mirrors legacy bp_save_member_type_post_metabox_data() for key generation,
	 * label defaults, and cache clearing.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function create_member_type() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss-platform' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$name       = isset( $_POST['name'] ) ? wp_kses( wp_unslash( $_POST['name'] ), wp_kses_allowed_html( 'strip' ) ) : '';
		$visibility = isset( $_POST['visibility'] ) ? sanitize_key( wp_unslash( $_POST['visibility'] ) ) : 'publish';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile type name is required.', 'buddyboss-platform' ) ) );
		}

		// Validate visibility against allowlist.
		$allowed_visibilities = array( 'publish', 'private', 'draft', 'password_protected' );
		if ( ! in_array( $visibility, $allowed_visibilities, true ) ) {
			$visibility = 'publish';
		}

		$post_type = bp_get_member_type_post_type();

		// Determine post_status from visibility.
		$post_status   = 'publish';
		$post_password = '';
		if ( 'private' === $visibility ) {
			$post_status = 'private';
		} elseif ( 'draft' === $visibility ) {
			$post_status = 'draft';
		} elseif ( 'password_protected' === $visibility ) {
			$post_status = 'publish';
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
			$post_password = isset( $_POST['post_password'] ) ? sanitize_text_field( wp_unslash( $_POST['post_password'] ) ) : '';
		}

		// Create the CPT post.
		$post_id = wp_insert_post(
			array(
				'post_title'    => $name,
				'post_type'     => $post_type,
				'post_status'   => $post_status,
				'post_password' => $post_password,
			)
		);

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to create profile type. Please try again.', 'buddyboss-platform' ) ) );
		}

		// Generate type key from post_name (slug), same as legacy bp_save_member_type_post_metabox_data().
		$key  = get_post_field( 'post_name', $post_id );
		$term = term_exists( sanitize_key( $key ), bp_get_member_type_tax_name() );
		if ( 0 !== $term && null !== $term ) {
			// Use wp_unique_post_slug() for robust collision avoidance instead of weak random suffix.
			$key = wp_unique_post_slug( $key, $post_id, 'publish', bp_get_member_type_post_type(), 0 );
		}

		update_post_meta( $post_id, '_bp_member_type_key', sanitize_key( $key ) );

		// Save all meta fields (with post_title as default for labels, matching legacy).
		$this->bb_save_member_type_meta( $post_id, $name );

		// Clear member type caches.
		$this->bb_clear_member_type_cache( $post_id );

		/**
		 * Fires after a member type is created via Settings 2.0.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param int $post_id The member type post ID.
		 */
		do_action( 'bb_member_type_after_save', $post_id );

		wp_send_json_success(
			array(
				'message' => __( 'Profile type created successfully.', 'buddyboss-platform' ),
				'id'      => $post_id,
			)
		);
	}

	/**
	 * Update an existing member/profile type.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function update_member_type() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss-platform' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$type_id    = isset( $_POST['type_id'] ) ? absint( wp_unslash( $_POST['type_id'] ) ) : 0;
		$name       = isset( $_POST['name'] ) ? wp_kses( wp_unslash( $_POST['name'] ), wp_kses_allowed_html( 'strip' ) ) : '';
		$visibility = isset( $_POST['visibility'] ) ? sanitize_key( wp_unslash( $_POST['visibility'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $type_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile type ID is required.', 'buddyboss-platform' ) ) );
		}

		// Verify post exists and is correct type.
		$post = get_post( $type_id );
		if ( ! $post || bp_get_member_type_post_type() !== $post->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Invalid profile type.', 'buddyboss-platform' ) ) );
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
			// Validate visibility against allowlist.
			$allowed_visibilities = array( 'publish', 'private', 'draft', 'password_protected' );
			if ( ! in_array( $visibility, $allowed_visibilities, true ) ) {
				$visibility = 'publish';
			}

			if ( 'private' === $visibility ) {
				$update_args['post_status']   = 'private';
				$update_args['post_password'] = '';
			} elseif ( 'draft' === $visibility ) {
				$update_args['post_status']   = 'draft';
				$update_args['post_password'] = '';
			} elseif ( 'password_protected' === $visibility ) {
				$update_args['post_status'] = 'publish';
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
				$post_password = isset( $_POST['post_password'] ) ? sanitize_text_field( wp_unslash( $_POST['post_password'] ) ) : '';

				// Preserve existing password when not provided in POST data.
				if ( empty( $post_password ) && ! empty( $post->post_password ) ) {
					$post_password = $post->post_password;
				}

				$update_args['post_password'] = $post_password;
			} else {
				$update_args['post_status']   = 'publish';
				$update_args['post_password'] = '';
			}
			$needs_update = true;
		}

		if ( $needs_update ) {
			$result = wp_update_post( $update_args, true );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => __( 'Failed to update profile type. Please try again.', 'buddyboss-platform' ) ) );
			}
		}

		// Save all meta fields (with post_title as default for labels, matching legacy).
		$this->bb_save_member_type_meta( $type_id, $post_title );

		// Clear member type caches.
		$this->bb_clear_member_type_cache( $type_id );

		/** This action is documented in class-bb-admin-member-types-ajax.php */
		do_action( 'bb_member_type_after_save', $type_id );

		$response = array(
			'message' => __( 'Profile type updated successfully.', 'buddyboss-platform' ),
			'id'      => $type_id,
		);

		wp_send_json_success( $response );
	}

	/**
	 * Delete a member/profile type.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function delete_member_type() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss-platform' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$type_id = isset( $_POST['type_id'] ) ? absint( wp_unslash( $_POST['type_id'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $type_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile type ID is required.', 'buddyboss-platform' ) ) );
		}

		// Verify post exists and is correct type.
		$post = get_post( $type_id );
		if ( ! $post || bp_get_member_type_post_type() !== $post->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Invalid profile type.', 'buddyboss-platform' ) ) );
		}

		// wp_delete_post triggers before_delete_post which calls bp_delete_member_type()
		// for taxonomy cleanup, and bb_members_clear_member_type_cache_before_delete() for cache.
		$result = wp_delete_post( $type_id, true );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete profile type.', 'buddyboss-platform' ) ) );
		}

		/**
		 * Fires after a member/profile type is deleted via Settings 2.0.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param int $type_id The deleted member type post ID.
		 */
		do_action( 'bb_member_type_after_delete', $type_id );

		wp_send_json_success(
			array( 'message' => __( 'Profile type deleted successfully.', 'buddyboss-platform' ) )
		);
	}

	/**
	 * Save member type meta fields from POST data.
	 *
	 * Mirrors legacy bp_save_member_type_post_metabox_data() save flow for all meta keys.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param int    $post_id    Post ID of the member type.
	 * @param string $post_title Post title (used as default for labels).
	 */
	private function bb_save_member_type_meta( $post_id, $post_title = '' ) {
		global $wpdb;

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$singular_label = isset( $_POST['singular_label'] ) ? wp_kses( wp_unslash( $_POST['singular_label'] ), wp_kses_allowed_html( 'strip' ) ) : '';
		$plural_label   = isset( $_POST['plural_label'] ) ? wp_kses( wp_unslash( $_POST['plural_label'] ), wp_kses_allowed_html( 'strip' ) ) : '';

		// Default labels to post_title when empty, same as legacy.
		$singular_label = ! empty( trim( $singular_label ) ) ? trim( $singular_label ) : $post_title;
		$plural_label   = ! empty( trim( $plural_label ) ) ? trim( $plural_label ) : $post_title;

		$enable_filter        = isset( $_POST['enable_filter'] ) ? absint( wp_unslash( $_POST['enable_filter'] ) ) : 0;
		$enable_remove        = isset( $_POST['enable_remove'] ) ? absint( wp_unslash( $_POST['enable_remove'] ) ) : 0;
		$enable_search_remove = isset( $_POST['enable_search_remove'] ) ? absint( wp_unslash( $_POST['enable_search_remove'] ) ) : 0;
		$enable_profile_field = isset( $_POST['enable_profile_field'] ) ? absint( wp_unslash( $_POST['enable_profile_field'] ) ) : 0;

		// Group type create permissions.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
		$raw_group_type_create = isset( $_POST['group_type_create'] ) ? wp_unslash( $_POST['group_type_create'] ) : '';
		if ( is_array( $raw_group_type_create ) ) {
			$group_type_create = array_map( 'sanitize_text_field', $raw_group_type_create );
		} else {
			$group_type_create = '';
		}

		// Group type auto join.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
		$raw_group_type_auto_join = isset( $_POST['group_type_auto_join'] ) ? wp_unslash( $_POST['group_type_auto_join'] ) : '';
		if ( is_array( $raw_group_type_auto_join ) ) {
			$group_type_auto_join = array_map( 'sanitize_text_field', $raw_group_type_auto_join );
		} else {
			$group_type_auto_join = '';
		}

		// WP Roles.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
		$raw_wp_roles = isset( $_POST['wp_roles'] ) ? wp_unslash( $_POST['wp_roles'] ) : array();
		if ( is_array( $raw_wp_roles ) ) {
			$wp_roles = array_filter( array_map( 'sanitize_text_field', $raw_wp_roles ) );
		} else {
			$wp_roles = ! empty( $raw_wp_roles ) ? array( sanitize_text_field( $raw_wp_roles ) ) : array();
		}

		// Validate against actual WP roles.
		$all_roles   = array_keys( wp_roles()->get_names() );
		$all_roles[] = 'none';
		$wp_roles    = array_intersect( $wp_roles, $all_roles );

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

		// Redirections — values are '' (default), '0' (custom URL), or numeric page ID.
		$login_redirection = isset( $_POST['login_redirection'] ) ? sanitize_text_field( wp_unslash( $_POST['login_redirection'] ) ) : '';
		if ( '' !== $login_redirection && '0' !== $login_redirection && ! is_numeric( $login_redirection ) ) {
			$login_redirection = '';
		}
		$custom_login_redirection = isset( $_POST['custom_login_redirection'] ) ? esc_url_raw( wp_unslash( $_POST['custom_login_redirection'] ) ) : '';
		$logout_redirection       = isset( $_POST['logout_redirection'] ) ? sanitize_text_field( wp_unslash( $_POST['logout_redirection'] ) ) : '';
		if ( '' !== $logout_redirection && '0' !== $logout_redirection && ! is_numeric( $logout_redirection ) ) {
			$logout_redirection = '';
		}
		$custom_logout_redirection = isset( $_POST['custom_logout_redirection'] ) ? esc_url_raw( wp_unslash( $_POST['custom_logout_redirection'] ) ) : '';

		// Messaging without connection.
		$allow_messaging_without_connection = isset( $_POST['allow_messaging_without_connection'] ) ? absint( wp_unslash( $_POST['allow_messaging_without_connection'] ) ) : 0;

		// Email Invites — allowed member types.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
		$raw_invite_member_types = isset( $_POST['invite_member_types'] ) ? wp_unslash( $_POST['invite_member_types'] ) : '';
		if ( is_array( $raw_invite_member_types ) ) {
			$invite_member_types = array_map( 'absint', $raw_invite_member_types );
		} else {
			$invite_member_types = array();
		}

		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Save only meta fields that were explicitly submitted in the request.
		// This prevents partial updates (e.g., saving only redirect fields from
		// the Profile Type Redirects panel) from wiping unrelated meta values.
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		if ( isset( $_POST['singular_label'] ) ) {
			update_post_meta( $post_id, '_bp_member_type_label_singular_name', $singular_label );
		}
		if ( isset( $_POST['plural_label'] ) ) {
			update_post_meta( $post_id, '_bp_member_type_label_name', $plural_label );
		}
		if ( isset( $_POST['enable_filter'] ) ) {
			update_post_meta( $post_id, '_bp_member_type_enable_filter', $enable_filter );
		}
		if ( isset( $_POST['enable_remove'] ) ) {
			update_post_meta( $post_id, '_bp_member_type_enable_remove', $enable_remove );
		}
		if ( isset( $_POST['enable_search_remove'] ) ) {
			update_post_meta( $post_id, '_bp_member_type_enable_search_remove', $enable_search_remove );
		}
		if ( isset( $_POST['enable_profile_field'] ) ) {
			update_post_meta( $post_id, '_bp_member_type_enable_profile_field', $enable_profile_field );
		}
		if ( isset( $_POST['group_type_create'] ) ) {
			update_post_meta( $post_id, '_bp_member_type_enabled_group_type_create', $group_type_create );
		}
		if ( isset( $_POST['group_type_auto_join'] ) ) {
			update_post_meta( $post_id, '_bp_member_type_enabled_group_type_auto_join', $group_type_auto_join );
		}
		if ( isset( $_POST['label_color'] ) ) {
			update_post_meta( $post_id, '_bp_member_type_label_color', $label_color );
		}
		if ( isset( $_POST['login_redirection'] ) ) {
			update_post_meta( $post_id, '_bp_member_type_login_redirection', $login_redirection );
		}
		if ( isset( $_POST['custom_login_redirection'] ) ) {
			update_post_meta( $post_id, '_bp_member_type_custom_login_redirection', $custom_login_redirection );
		}
		if ( isset( $_POST['logout_redirection'] ) ) {
			update_post_meta( $post_id, '_bp_member_type_logout_redirection', $logout_redirection );
		}
		if ( isset( $_POST['custom_logout_redirection'] ) ) {
			update_post_meta( $post_id, '_bp_member_type_custom_logout_redirection', $custom_logout_redirection );
		}
		if ( isset( $_POST['allow_messaging_without_connection'] ) ) {
			update_post_meta( $post_id, '_bp_member_type_allow_messaging_without_connection', $allow_messaging_without_connection );
		}
		if ( isset( $_POST['invite_member_types'] ) ) {
			update_post_meta( $post_id, '_bp_member_type_allowed_member_type_invite', $invite_member_types );
			// Derive enable_invite from whether any types are selected (matches legacy behavior).
			$enable_invite = ! empty( $invite_member_types ) ? 1 : 0;
			update_post_meta( $post_id, '_bp_member_type_enable_invite', $enable_invite );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Update messaging-without-connection option only when submitted.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		if ( isset( $_POST['allow_messaging_without_connection'] ) ) {
			$type_key_for_option             = get_post_meta( $post_id, '_bp_member_type_key', true );
			$profile_types_allowed_messaging = get_option( 'bp_member_types_allowed_messaging_without_connection', array() );
			if ( ! is_array( $profile_types_allowed_messaging ) ) {
				$profile_types_allowed_messaging = array();
			}

			if ( $allow_messaging_without_connection ) {
				$profile_types_allowed_messaging[ $type_key_for_option ] = true;
			} elseif ( array_key_exists( $type_key_for_option, $profile_types_allowed_messaging ) ) {
				unset( $profile_types_allowed_messaging[ $type_key_for_option ] );
			}
			update_option( 'bp_member_types_allowed_messaging_without_connection', $profile_types_allowed_messaging );
		}

		// WP Role assignment logic — only when wp_roles is submitted.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$old_wp_roles            = isset( $_POST['wp_roles'] ) ? ( get_post_meta( $post_id, '_bp_member_type_wp_roles', true ) ) : $wp_roles;
		$check_both_old_new_same = ( $wp_roles === $old_wp_roles );

		if ( false === $check_both_old_new_same ) {
			$member_type_name = bp_get_member_type_key( $post_id );
			$type_term        = get_term_by(
				'name',
				$member_type_name,
				'bp_member_type'
			);

			// Check logged user role - prevent admin lockout.
			$current_user      = new WP_User( get_current_user_id() );
			$current_user_role = isset( $current_user->roles[0] ) ? $current_user->roles[0] : '';

			$bp_prevent_data_update = false;
			$get_user_ids           = array();

			if ( isset( $type_term->term_id ) ) {
				// Batch-fetch user IDs in chunks to avoid unbounded memory usage on large sites.
				$batch_size   = 500;
				$offset       = 0;
				$get_user_ids = array();
				do {
					$batch = $wpdb->get_col(
						$wpdb->prepare(
							"SELECT u.ID FROM {$wpdb->users} u INNER JOIN {$wpdb->term_relationships} r ON u.ID = r.object_id WHERE u.user_status = 0 AND r.term_taxonomy_id = %d LIMIT %d OFFSET %d",
							$type_term->term_taxonomy_id,
							$batch_size,
							$offset
						)
					);

					$get_user_ids = array_merge( $get_user_ids, $batch );
					$offset      += $batch_size;
					$batch_count  = count( $batch );
				} while ( $batch_count === $batch_size );

				if ( ! empty( $get_user_ids ) && in_array( (string) get_current_user_id(), $get_user_ids, true ) ) {
					$bp_prevent_data_update = true;
				}
			}

			if ( true === $bp_prevent_data_update ) {
				if ( is_array( $old_wp_roles ) && in_array( 'administrator', $old_wp_roles, true ) ) {
					if ( ! in_array( $current_user_role, $wp_roles, true ) ) {
						$bp_error_message_string = __( 'As your profile is currently assigned to this profile type, you cannot change its associated WordPress role. Changing this setting would remove your Administrator role and lock you out of the WordPress admin. You first need to remove yourself from this profile type and then you can come back to update the associated WordPress role.', 'buddyboss-platform' );

						/**
						 * Filters the admin error message when a user attempts to change a role
						 * that would lock them out.
						 *
						 * @since BuddyBoss 1.0.0
						 *
						 * @param string $bp_error_message_string Error message.
						 */
						$error_message = apply_filters( 'bp_member_type_admin_error_message', $bp_error_message_string );

						wp_send_json_error( array( 'message' => $error_message ) );
					}
				}
			}

			update_post_meta( $post_id, '_bp_member_type_wp_roles', $wp_roles );

			// Update user roles if term exists (same as legacy CPT save).
			if ( $type_term ) {
				$selected_roles = get_post_meta( $post_id, '_bp_member_type_wp_roles', true );

				if ( isset( $selected_roles[0] ) && 'none' !== $selected_roles[0] && ! empty( $get_user_ids ) ) {

					// Process all users — matches legacy behavior (no cap).
					// @todo: Add background queue for large sites in a future release.
					foreach ( $get_user_ids as $single_user ) {
						$bp_user = new WP_User( $single_user );
						foreach ( $bp_user->roles as $role ) {
							$bp_user->remove_role( $role );
						}
						$bp_user->add_role( $selected_roles[0] );
					}
				}
			}
		} else {
			update_post_meta( $post_id, '_bp_member_type_wp_roles', $wp_roles );
		}
	}

	/**
	 * Get available group types for the Group Creation Permissions field.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Array of group types with id and name.
	 */
	private function bb_get_available_group_types() {
		$group_types = array();

		if ( ! function_exists( 'bp_groups_get_group_type_post_type' ) ) {
			return $group_types;
		}

		$post_type = bp_groups_get_group_type_post_type();
		$posts     = get_posts(
			array(
				'post_type'      => $post_type,
				'posts_per_page' => 500,
				'post_status'    => array( 'publish', 'private' ),
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		foreach ( $posts as $post ) {
			$group_types[] = array(
				'id'   => get_post_meta( $post->ID, '_bp_group_type_key', true ),
				'name' => $post->post_title,
			);
		}

		return $group_types;
	}

	/**
	 * Get all WordPress roles.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Array of roles with value and label.
	 */
	private function bb_get_wp_roles() {
		$roles     = array();
		$all_roles = wp_roles()->get_names();

		foreach ( $all_roles as $role_key => $role_name ) {
			$roles[] = array(
				'value' => $role_key,
				'label' => translate_user_role( $role_name ),
			);
		}

		return $roles;
	}

	/**
	 * Clear member type caches after create/update.
	 *
	 * The legacy cache clearing hook bb_members_clear_member_type_cache_on_update()
	 * checks for the legacy nonce $_POST['_bp-member-type-nonce'] which is not present
	 * in Settings 2.0 AJAX requests. We replicate the same cache clearing here.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @see bb_members_clear_member_type_cache_on_update() Legacy cache clearing (save_post hook).
	 * @see bb_members_clear_member_type_cache_before_delete() Delete cache clearing (before_delete_post hook) — works automatically.
	 *
	 * @param int $post_id Post ID of the member type.
	 */
	private function bb_clear_member_type_cache( $post_id ) {
		wp_cache_delete( 'bp_get_removed_member_types', 'bp_member_member_type' );
		wp_cache_delete( 'bp_get_all_member_types_posts', 'bp_member_member_type' );
		wp_cache_delete( 'bp_get_hidden_member_types_cache', 'bp_member_member_type' );
		wp_cache_delete( 'bb-member-type-label-css', 'bp_member_member_type' );
		wp_cache_delete( 'bb_admin_member_type_counts', 'bp_member_type' ); // Clear taxonomy counts cache.

		$type_key = get_post_meta( $post_id, '_bp_member_type_key', true );
		if ( ! empty( $type_key ) ) {
			wp_cache_delete( 'bb-member-type-label-color-' . $type_key, 'bp_member_member_type' );
		}
	}
}

// Initialize.
new BB_Admin_Member_Types_Ajax();
