<?php
/**
 * BuddyBoss Member Types Admin AJAX Handler
 *
 * Handles AJAX requests for Profile/Member Types CRUD
 * in the Settings 2.0 admin interface.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Member_Types_Ajax
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Admin_Member_Types_Ajax {

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
	 * @since BuddyBoss [BBVERSION]
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
	 * Get all member/profile types with meta.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function get_member_types() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss' ) ) );
		}

		$member_type_ids = bp_get_active_member_types();

		if ( empty( $member_type_ids ) ) {
			$response = array(
				'member_types' => array(),
			);

			// Include group types if groups component is active.
			if ( bp_is_active( 'groups' ) ) {
				$response['group_types'] = $this->bb_get_available_group_types();
			}

			$response['wp_roles'] = $this->bb_get_wp_roles();

			wp_send_json_success( $response );
		}

		// Batch-load post meta to avoid N+1 queries.
		update_postmeta_cache( $member_type_ids );

		// Prime post cache.
		_prime_post_caches( $member_type_ids );

		// Get member counts per type in a single SQL query.
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

			// Get invite permissions.
			$allowed_invite = get_post_meta( $post_id, '_bp_member_type_allowed_member_type_invite', true );
			if ( ! is_array( $allowed_invite ) ) {
				$allowed_invite = ! empty( $allowed_invite ) ? array( $allowed_invite ) : array();
			}

			$member_types[] = array(
				'id'                     => $post_id,
				'post_title'             => $post->post_title,
				'key'                    => $type_key,
				'singular_label'         => get_post_meta( $post_id, '_bp_member_type_label_singular_name', true ),
				'plural_label'           => get_post_meta( $post_id, '_bp_member_type_label_name', true ),
				'members_count'          => isset( $member_counts[ $type_key ] ) ? (int) $member_counts[ $type_key ] : 0,
				'enable_filter'          => absint( get_post_meta( $post_id, '_bp_member_type_enable_filter', true ) ),
				'enable_remove'          => absint( get_post_meta( $post_id, '_bp_member_type_enable_remove', true ) ),
				'enable_search_remove'   => absint( get_post_meta( $post_id, '_bp_member_type_enable_search_remove', true ) ),
				'enable_profile_field'   => absint( get_post_meta( $post_id, '_bp_member_type_enable_profile_field', true ) ),
				'group_type_create'      => array_map( 'sanitize_text_field', $group_type_create ),
				'group_type_auto_join'   => array_map( 'sanitize_text_field', $group_type_auto_join ),
				'wp_roles'               => array_map( 'sanitize_text_field', $wp_roles_meta ),
				'label_color'            => map_deep( $label_color, 'sanitize_text_field' ),
				'login_redirection'      => sanitize_key( get_post_meta( $post_id, '_bp_member_type_login_redirection', true ) ),
				'custom_login_redirection'  => esc_url_raw( get_post_meta( $post_id, '_bp_member_type_custom_login_redirection', true ) ),
				'logout_redirection'     => sanitize_key( get_post_meta( $post_id, '_bp_member_type_logout_redirection', true ) ),
				'custom_logout_redirection' => esc_url_raw( get_post_meta( $post_id, '_bp_member_type_custom_logout_redirection', true ) ),
				'visibility'             => $visibility,
				'post_password'          => ! empty( $post->post_password ) ? $post->post_password : '',
				'enable_invite'          => absint( get_post_meta( $post_id, '_bp_member_type_enable_invite', true ) ),
				'allowed_member_type_invite' => array_map( 'absint', $allowed_invite ),
				'allow_messaging_without_connection' => absint( get_post_meta( $post_id, '_bp_member_type_allow_messaging_without_connection', true ) ),
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
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function create_member_type() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$name       = isset( $_POST['name'] ) ? wp_kses( wp_unslash( $_POST['name'] ), wp_kses_allowed_html( 'strip' ) ) : '';
		$visibility = isset( $_POST['visibility'] ) ? sanitize_key( $_POST['visibility'] ) : 'publish';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile type name is required.', 'buddyboss' ) ) );
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
			wp_send_json_error( array( 'message' => esc_html( $post_id->get_error_message() ) ) );
		}

		// Generate type key from post_name (slug), same as legacy bp_save_member_type_post_metabox_data().
		$key  = get_post_field( 'post_name', $post_id );
		$term = term_exists( sanitize_key( $key ), bp_get_member_type_tax_name() );
		if ( 0 !== $term && null !== $term ) {
			$key = $key . wp_rand( 100, 999 );
		}

		update_post_meta( $post_id, '_bp_member_type_key', sanitize_key( $key ) );

		// Save all meta fields (with post_title as default for labels, matching legacy).
		$this->bb_save_member_type_meta( $post_id, $name );

		// Clear member type caches.
		$this->bb_clear_member_type_cache( $post_id );

		wp_send_json_success(
			array(
				'message' => __( 'Profile type created successfully.', 'buddyboss' ),
				'id'      => $post_id,
			)
		);
	}

	/**
	 * Update an existing member/profile type.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function update_member_type() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$type_id    = isset( $_POST['type_id'] ) ? absint( $_POST['type_id'] ) : 0;
		$name       = isset( $_POST['name'] ) ? wp_kses( wp_unslash( $_POST['name'] ), wp_kses_allowed_html( 'strip' ) ) : '';
		$visibility = isset( $_POST['visibility'] ) ? sanitize_key( $_POST['visibility'] ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $type_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile type ID is required.', 'buddyboss' ) ) );
		}

		// Verify post exists and is correct type.
		$post = get_post( $type_id );
		if ( ! $post || bp_get_member_type_post_type() !== $post->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Invalid profile type.', 'buddyboss' ) ) );
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
			if ( 'private' === $visibility ) {
				$update_args['post_status']   = 'private';
				$update_args['post_password'] = '';
			} elseif ( 'draft' === $visibility ) {
				$update_args['post_status']   = 'draft';
				$update_args['post_password'] = '';
			} elseif ( 'password_protected' === $visibility ) {
				$update_args['post_status'] = 'publish';
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
				$update_args['post_password'] = isset( $_POST['post_password'] ) ? sanitize_text_field( wp_unslash( $_POST['post_password'] ) ) : '';
			} else {
				$update_args['post_status']   = 'publish';
				$update_args['post_password'] = '';
			}
			$needs_update = true;
		}

		if ( $needs_update ) {
			$result = wp_update_post( $update_args, true );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => esc_html( $result->get_error_message() ) ) );
			}
		}

		// Save all meta fields (with post_title as default for labels, matching legacy).
		$this->bb_save_member_type_meta( $type_id, $post_title );

		// Clear member type caches.
		$this->bb_clear_member_type_cache( $type_id );

		wp_send_json_success(
			array(
				'message' => __( 'Profile type updated successfully.', 'buddyboss' ),
				'id'      => $type_id,
			)
		);
	}

	/**
	 * Delete a member/profile type.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function delete_member_type() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$type_id = isset( $_POST['type_id'] ) ? absint( $_POST['type_id'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $type_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile type ID is required.', 'buddyboss' ) ) );
		}

		// Verify post exists and is correct type.
		$post = get_post( $type_id );
		if ( ! $post || bp_get_member_type_post_type() !== $post->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Invalid profile type.', 'buddyboss' ) ) );
		}

		// wp_delete_post triggers before_delete_post which calls bp_delete_member_type()
		// for taxonomy cleanup, and bb_members_clear_member_type_cache_before_delete() for cache.
		$result = wp_delete_post( $type_id, true );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete profile type.', 'buddyboss' ) ) );
		}

		wp_send_json_success(
			array( 'message' => __( 'Profile type deleted successfully.', 'buddyboss' ) )
		);
	}

	/**
	 * Save member type meta fields from POST data.
	 *
	 * Mirrors legacy bp_save_member_type_post_metabox_data() save flow for all meta keys.
	 *
	 * @since BuddyBoss [BBVERSION]
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

		// Redirections.
		$login_redirection         = isset( $_POST['login_redirection'] ) ? sanitize_key( wp_unslash( $_POST['login_redirection'] ) ) : '';
		$custom_login_redirection  = isset( $_POST['custom_login_redirection'] ) ? esc_url_raw( wp_unslash( $_POST['custom_login_redirection'] ) ) : '';
		$logout_redirection        = isset( $_POST['logout_redirection'] ) ? sanitize_key( wp_unslash( $_POST['logout_redirection'] ) ) : '';
		$custom_logout_redirection = isset( $_POST['custom_logout_redirection'] ) ? esc_url_raw( wp_unslash( $_POST['custom_logout_redirection'] ) ) : '';

		// Messaging without connection.
		$allow_messaging_without_connection = isset( $_POST['allow_messaging_without_connection'] ) ? absint( wp_unslash( $_POST['allow_messaging_without_connection'] ) ) : 0;

		// Email invite fields.
		$enable_invite = isset( $_POST['enable_invite'] ) ? absint( wp_unslash( $_POST['enable_invite'] ) ) : 0;

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
		$raw_allowed_invite = isset( $_POST['allowed_member_type_invite'] ) ? wp_unslash( $_POST['allowed_member_type_invite'] ) : '';
		if ( is_array( $raw_allowed_invite ) ) {
			$allowed_invite = array_map( 'absint', $raw_allowed_invite );
		} else {
			$allowed_invite = '';
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Save all meta.
		update_post_meta( $post_id, '_bp_member_type_label_singular_name', $singular_label );
		update_post_meta( $post_id, '_bp_member_type_label_name', $plural_label );
		update_post_meta( $post_id, '_bp_member_type_enable_filter', $enable_filter );
		update_post_meta( $post_id, '_bp_member_type_enable_remove', $enable_remove );
		update_post_meta( $post_id, '_bp_member_type_enable_search_remove', $enable_search_remove );
		update_post_meta( $post_id, '_bp_member_type_enable_profile_field', $enable_profile_field );
		update_post_meta( $post_id, '_bp_member_type_enabled_group_type_create', $group_type_create );
		update_post_meta( $post_id, '_bp_member_type_enabled_group_type_auto_join', $group_type_auto_join );
		update_post_meta( $post_id, '_bp_member_type_label_color', $label_color );
		update_post_meta( $post_id, '_bp_member_type_login_redirection', $login_redirection );
		update_post_meta( $post_id, '_bp_member_type_custom_login_redirection', $custom_login_redirection );
		update_post_meta( $post_id, '_bp_member_type_logout_redirection', $logout_redirection );
		update_post_meta( $post_id, '_bp_member_type_custom_logout_redirection', $custom_logout_redirection );
		update_post_meta( $post_id, '_bp_member_type_enable_invite', $enable_invite );
		update_post_meta( $post_id, '_bp_member_type_allowed_member_type_invite', $allowed_invite );
		update_post_meta( $post_id, '_bp_member_type_allow_messaging_without_connection', $allow_messaging_without_connection );

		// Update messaging-without-connection option (matching legacy lines 2340-2351).
		$type_key_for_option                = get_post_meta( $post_id, '_bp_member_type_key', true );
		$profile_types_allowed_messaging    = get_option( 'bp_member_types_allowed_messaging_without_connection', array() );
		if ( ! is_array( $profile_types_allowed_messaging ) ) {
			$profile_types_allowed_messaging = array();
		}

		if ( $allow_messaging_without_connection ) {
			$profile_types_allowed_messaging[ $type_key_for_option ] = true;
		} elseif ( array_key_exists( $type_key_for_option, $profile_types_allowed_messaging ) ) {
			unset( $profile_types_allowed_messaging[ $type_key_for_option ] );
		}
		update_option( 'bp_member_types_allowed_messaging_without_connection', $profile_types_allowed_messaging );

		// WP Role assignment logic (from legacy lines 2341-2412).
		$old_wp_roles              = get_post_meta( $post_id, '_bp_member_type_wp_roles', true );
		$check_both_old_new_same   = ( $wp_roles === $old_wp_roles );

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

			if ( isset( $type_term->term_id ) ) {
				$get_user_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT u.ID FROM {$wpdb->users} u INNER JOIN {$wpdb->term_relationships} r ON u.ID = r.object_id WHERE u.user_status = 0 AND r.term_taxonomy_id = %d",
						$type_term->term_id
					)
				);

				if ( ! empty( $get_user_ids ) && in_array( get_current_user_id(), $get_user_ids ) ) {
					$bp_prevent_data_update = true;
				}
			}

			if ( true === $bp_prevent_data_update ) {
				if ( isset( $old_wp_roles[0] ) && 'administrator' === $old_wp_roles[0] ) {
					if ( ! in_array( $current_user_role, $wp_roles, true ) ) {
						$bp_error_message_string = __( 'As your profile is currently assigned to this profile type, you cannot change its associated WordPress role. Changing this setting would remove your Administrator role and lock you out of the WordPress admin. You first need to remove yourself from this profile type and then you can come back to update the associated WordPress role.', 'buddyboss' );

						/**
						 * Filters the admin error message when a user attempts to change a role
						 * that would lock them out.
						 *
						 * @since BuddyBoss 1.0.0
						 *
						 * @param string $bp_error_message_string Error message.
						 */
						$error_message = apply_filters( 'bp_member_type_admin_error_message', $bp_error_message_string );

						wp_send_json_error( array( 'message' => esc_html( $error_message ) ) );
					}
				}
			}

			update_post_meta( $post_id, '_bp_member_type_wp_roles', $wp_roles );

			// Update user roles if term exists.
			if ( $type_term ) {
				$selected_roles = get_post_meta( $post_id, '_bp_member_type_wp_roles', true );

				if ( isset( $selected_roles[0] ) && 'none' !== $selected_roles[0] ) {
					if ( isset( $get_user_ids ) && ! empty( $get_user_ids ) ) {
						foreach ( $get_user_ids as $single_user ) {
							$bp_user = new WP_User( $single_user );
							foreach ( $bp_user->roles as $role ) {
								$bp_user->remove_role( $role );
							}
							$bp_user->add_role( $selected_roles[0] );
						}
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
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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

		$type_key = get_post_meta( $post_id, '_bp_member_type_key', true );
		if ( ! empty( $type_key ) ) {
			wp_cache_delete( 'bb-member-type-label-color-' . $type_key, 'bp_member_member_type' );
		}
	}
}

// Initialize.
new BB_Admin_Member_Types_Ajax();
