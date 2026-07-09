<?php
/**
 * BuddyBoss Profile Fields Admin AJAX Handler
 *
 * Handles AJAX requests for Profile Fields (XProfile) CRUD
 * in the Settings 2.0 admin interface.
 *
 * @since   BuddyBoss 3.0.0
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Profile_Fields_Ajax
 *
 * @since BuddyBoss 3.0.0
 */
class BB_Admin_Profile_Fields_Ajax {

	/**
	 * Nonce action (shared with BB_Admin_Settings_Ajax).
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'bb_admin_settings';

	/**
	 * BB icon font class strings for each xprofile social provider.
	 *
	 * Maps the provider's `value` slug (returned by
	 * bp_xprofile_social_network_provider()) to a full CSS class string
	 * — the base weight class (`bb-icon-l` = lined) plus the brand
	 * modifier. CustomSelectControl uses the value verbatim when it
	 * contains a space, so each entry is two classes.
	 *
	 * Hoisted to a class constant so the array literal isn't re-allocated
	 * on every AJAX call.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @var array<string, string>
	 */
	const SOCIAL_PROVIDER_ICONS = array(
		'facebook'  => 'bb-icon-l bb-icon-brand-facebook',
		'flickr'    => 'bb-icon-l bb-icon-brand-flickr',
		'instagram' => 'bb-icon-l bb-icon-brand-instagram',
		'linkedIn'  => 'bb-icon-l bb-icon-brand-linkedin',
		'medium'    => 'bb-icon-l bb-icon-brand-medium',
		'meetup'    => 'bb-icon-l bb-icon-brand-meetup',
		'pinterest' => 'bb-icon-l bb-icon-brand-pinterest',
		'quora'     => 'bb-icon-l bb-icon-brand-quora',
		'reddit'    => 'bb-icon-l bb-icon-brand-reddit',
		'snapchat'  => 'bb-icon-l bb-icon-brand-snapchat',
		'telegram'  => 'bb-icon-l bb-icon-brand-telegram',
		'tumblr'    => 'bb-icon-l bb-icon-brand-tumblr',
		'twitch'    => 'bb-icon-l bb-icon-brand-twitch',
		'twitter'   => 'bb-icon-l bb-icon-brand-twitter',
		'vk'        => 'bb-icon-l bb-icon-brand-vk',
		'whatsapp'  => 'bb-icon-l bb-icon-brand-whatsapp',
		'x'         => 'bb-icon-l bb-icon-brand-x',
		'youTube'   => 'bb-icon-l bb-icon-brand-youtube',
		'tiktok'    => 'bb-icon-l bb-icon-brand-tiktok',
		'github'    => 'bb-icon-l bb-icon-brand-github',
	);

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
		add_action( 'wp_ajax_bb_admin_get_profile_field_groups', array( $this, 'get_field_groups' ) );
		add_action( 'wp_ajax_bb_admin_create_field_group', array( $this, 'create_field_group' ) );
		add_action( 'wp_ajax_bb_admin_update_field_group', array( $this, 'update_field_group' ) );
		add_action( 'wp_ajax_bb_admin_delete_field_group', array( $this, 'delete_field_group' ) );
		add_action( 'wp_ajax_bb_admin_save_profile_field', array( $this, 'save_profile_field' ) );
		add_action( 'wp_ajax_bb_admin_delete_profile_field', array( $this, 'delete_profile_field' ) );
		add_action( 'wp_ajax_bb_admin_reorder_profile_fields', array( $this, 'reorder_fields' ) );
	}

	/**
	 * Get all field groups with their fields.
	 *
	 * Returns field groups, available field types, member types, and visibility levels.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function get_field_groups() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss' ) ) );
		}

		$groups = bp_xprofile_get_groups(
			array(
				'fetch_fields'                   => true,
				'fetch_field_data'               => false,
				'fetch_visibility_level'         => true,
				'repeater_show_main_fields_only' => true,
			)
		);

		$field_groups = array();

		// H1 fix: Prime xprofile meta cache for all fields in one query to avoid N+1.
		$all_field_ids = array();
		foreach ( $groups as $group ) {
			if ( ! empty( $group->fields ) ) {
				foreach ( $group->fields as $field ) {
					$all_field_ids[] = (int) $field->id;
				}
			}
		}
		if ( ! empty( $all_field_ids ) ) {
			bp_xprofile_update_meta_cache( $all_field_ids );
		}

		foreach ( $groups as $group ) {
			$is_repeater = 'on' === bp_xprofile_get_meta( $group->id, 'group', 'is_repeater_enabled' );

			$fields = array();
			if ( ! empty( $group->fields ) ) {
				foreach ( $group->fields as $field ) {
					// Skip cloned repeater fields whose source template field still exists.
					if ( bp_xprofile_get_meta( $field->id, 'field', '_is_repeater_clone', true ) ) {
						$cloned_from  = bp_xprofile_get_meta( $field->id, 'field', '_cloned_from', true );
						$source_field = $cloned_from ? BP_XProfile_Field::get_instance( (int) $cloned_from ) : false;
						if ( $source_field && ! empty( $source_field->id ) ) {
							continue;
						}
					}

					$fields[] = $this->bb_format_field( $field );
				}
			}

			$field_groups[] = array(
				'id'          => (int) $group->id,
				'name'        => $group->name,
				'description' => $group->description,
				'can_delete'  => (bool) $group->can_delete,
				'group_order' => (int) $group->group_order,
				'is_repeater' => $is_repeater,
				'fields'      => $fields,
			);
		}

		$response = array(
			'field_groups'      => $field_groups,
			'field_types'       => $this->bb_get_field_types(),
			'visibility_levels' => $this->bb_get_visibility_levels(),
		);

		// Include member types if enabled.
		if ( function_exists( 'bp_member_type_enable_disable' ) && true === bp_member_type_enable_disable() ) {
			$response['member_types'] = $this->bb_get_member_types();
		}

		// Include social network providers for the Social Networks field type.
		if ( function_exists( 'bp_xprofile_social_network_provider' ) ) {
			$providers                    = bp_xprofile_social_network_provider();
			$response['social_providers'] = array_map(
				array( $this, 'bb_format_social_provider' ),
				$providers
			);
		}

		wp_send_json_success( $response );
	}

	/**
	 * Create a new field group.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function create_field_group() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$name              = isset( $_POST['name'] ) ? wp_kses( wp_unslash( $_POST['name'] ), wp_kses_allowed_html( 'strip' ) ) : '';
		$description       = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$group_is_repeater = isset( $_POST['group_is_repeater'] ) ? sanitize_key( wp_unslash( $_POST['group_is_repeater'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Field set name is required.', 'buddyboss' ) ) );
		}

		$group_id = xprofile_insert_field_group(
			array(
				'name'        => $name,
				'description' => $description,
			)
		);

		if ( ! $group_id ) {
			wp_send_json_error( array( 'message' => __( 'There was an error creating the field set. Please try again.', 'buddyboss' ) ) );
		}

		// Save repeater meta.
		if ( 'on' === $group_is_repeater ) {
			bp_xprofile_update_meta( $group_id, 'group', 'is_repeater_enabled', 'on' );
		}

		/**
		 * Fires after a field group is saved via Settings 2.0.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param BP_XProfile_Group $group Field group object.
		 */
		$group = xprofile_get_field_group( $group_id );
		if ( $group ) {
			do_action( 'xprofile_groups_saved_group', $group );
		}

		wp_send_json_success(
			array(
				'message'  => __( 'Field set created successfully.', 'buddyboss' ),
				'group_id' => $group_id,
			)
		);
	}

	/**
	 * Update an existing field group.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function update_field_group() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$group_id          = isset( $_POST['group_id'] ) ? absint( wp_unslash( $_POST['group_id'] ) ) : 0;
		$name              = isset( $_POST['name'] ) ? wp_kses( wp_unslash( $_POST['name'] ), wp_kses_allowed_html( 'strip' ) ) : '';
		$description       = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$group_is_repeater = isset( $_POST['group_is_repeater'] ) ? sanitize_key( wp_unslash( $_POST['group_is_repeater'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $group_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Field set ID is required.', 'buddyboss' ) ) );
		}

		$group = xprofile_get_field_group( $group_id );
		if ( ! $group ) {
			wp_send_json_error( array( 'message' => __( 'Invalid field set.', 'buddyboss' ) ) );
		}

		$result = xprofile_insert_field_group(
			array(
				'field_group_id' => $group_id,
				'name'           => ! empty( $name ) ? $name : $group->name,
				'description'    => $description,
			)
		);

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'There was an error updating the field set. Please try again.', 'buddyboss' ) ) );
		}

		// Update base group name option for group_id=1, matching legacy bp-xprofile-admin.php:431.
		if ( 1 === $group_id ) {
			bp_update_option( 'bp-xprofile-base-group-name', ! empty( $name ) ? $name : $group->name );
		}

		// Update repeater meta.
		bp_xprofile_update_meta( $group_id, 'group', 'is_repeater_enabled', 'on' === $group_is_repeater ? 'on' : '' );

		/**
		 * Fires after a field group is saved via Settings 2.0.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param BP_XProfile_Group $group Field group object.
		 */
		$group = xprofile_get_field_group( $group_id );
		if ( $group ) {
			do_action( 'xprofile_groups_saved_group', $group );
		}

		wp_send_json_success(
			array(
				'message'  => __( 'Field set updated successfully.', 'buddyboss' ),
				'group_id' => $group_id,
			)
		);
	}

	/**
	 * Delete a field group and all its fields.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function delete_field_group() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$group_id = isset( $_POST['group_id'] ) ? absint( wp_unslash( $_POST['group_id'] ) ) : 0;

		if ( empty( $group_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Field set ID is required.', 'buddyboss' ) ) );
		}

		$group = xprofile_get_field_group( $group_id );
		if ( ! $group ) {
			wp_send_json_error( array( 'message' => __( 'Invalid field set.', 'buddyboss' ) ) );
		}

		if ( ! $group->can_delete ) {
			wp_send_json_error( array( 'message' => __( 'This field set cannot be deleted.', 'buddyboss' ) ) );
		}

		// Delete fires xprofile_group_before_delete and xprofile_group_after_delete internally.
		$result = xprofile_delete_field_group( $group_id );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'There was an error deleting the field set. Please try again.', 'buddyboss' ) ) );
		}

		/**
		 * Fires after a field group is deleted via Settings 2.0.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param BP_XProfile_Group $group Deleted field group object.
		 */
		do_action( 'xprofile_groups_deleted_group', $group );

		wp_send_json_success(
			array( 'message' => __( 'Field set deleted successfully.', 'buddyboss' ) )
		);
	}

	/**
	 * Create or update a profile field.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function save_profile_field() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$field_id    = isset( $_POST['field_id'] ) ? absint( wp_unslash( $_POST['field_id'] ) ) : 0;
		$group_id    = isset( $_POST['group_id'] ) ? absint( wp_unslash( $_POST['group_id'] ) ) : 0;
		$name        = isset( $_POST['name'] ) ? wp_kses( wp_unslash( $_POST['name'] ), wp_kses_allowed_html( 'strip' ) ) : '';
		$type        = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$is_required = isset( $_POST['is_required'] ) ? min( 1, absint( wp_unslash( $_POST['is_required'] ) ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $group_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Field set ID is required.', 'buddyboss' ) ) );
		}

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Field name is required.', 'buddyboss' ) ) );
		}

		if ( empty( $type ) ) {
			wp_send_json_error( array( 'message' => __( 'Field type is required.', 'buddyboss' ) ) );
		}

		// Validate field type against registered types.
		$allowed_types = array_keys( bp_xprofile_get_field_types() );
		if ( ! in_array( $type, $allowed_types, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid field type.', 'buddyboss' ) ) );
		}

		// Handle options for multi-option field types before insert.
		$this->bb_handle_field_options( $field_id, $type );

		// Build insert args.
		$args = array(
			'field_group_id' => $group_id,
			'type'           => $type,
			'name'           => $name,
			'description'    => $description,
			'is_required'    => (bool) $is_required,
		);

		if ( ! empty( $field_id ) ) {
			$args['field_id'] = $field_id;

			$existing_field = xprofile_get_field( $field_id );
			if ( $existing_field ) {
				$existing_group_id = (int) $existing_field->group_id;

				if ( $existing_group_id !== (int) $group_id ) {
					// Cross-field-set reassignment on edit. Block it unless the
					// move is permitted. Matches the drag guardrails enforced by
					// `reorder_fields()` (which mirrors the legacy
					// `accept: '.connectedSortable fieldset:not(.primary_field)'`
					// drop rule from `bp-xprofile/admin/js/admin.js`). The React
					// modal does not currently expose a group selector, so this
					// is defense-in-depth against direct AJAX clients.
					if ( ! $this->bb_can_move_field_to_group( $field_id, $existing_group_id, $group_id ) ) {
						wp_send_json_error(
							array( 'message' => __( 'This field cannot be moved to that field set.', 'buddyboss' ) )
						);
					}

					// Allowed move: append to the end of the destination set.
					// Reusing the source-group order would collide with whatever
					// field already holds that position in the new set.
					$args['field_order'] = $this->bb_get_next_field_order( $group_id );
				} else {
					// Same set: preserve existing field_order so the field
					// doesn't jump to position 0.
					$args['field_order'] = (int) $existing_field->field_order;
				}
			}
		}

		// Auto-assign field order for new fields (exclude repeater clones, matching legacy behavior).
		if ( empty( $field_id ) ) {
			$args['field_order'] = $this->bb_get_next_field_order( $group_id );
		}

		$saved_id = xprofile_insert_field( $args );

		if ( empty( $saved_id ) ) {
			// Singleton validation error messages.
			if ( 'membertypes' === $type ) {
				wp_send_json_error( array( 'message' => __( 'You can only have one instance of the "Profile Type" profile field.', 'buddyboss' ) ) );
			} elseif ( 'gender' === $type ) {
				wp_send_json_error( array( 'message' => __( 'You can only have one instance of the "Gender" profile field.', 'buddyboss' ) ) );
			} elseif ( 'socialnetworks' === $type ) {
				wp_send_json_error( array( 'message' => __( 'You can only have one instance of the "Social Network" profile field.', 'buddyboss' ) ) );
			}

			wp_send_json_error( array( 'message' => __( 'There was an error saving the field. Please try again.', 'buddyboss' ) ) );
		}

		// Save field metadata.
		$this->bb_save_field_meta( $saved_id );

		// Set member types.
		$this->bb_save_field_member_types( $saved_id );

		// Save field type specific settings.
		$field = xprofile_get_field( $saved_id, null, false );
		if ( $field && $field->type_obj->do_settings_section() ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified above, sanitized via map_deep below.
			$settings = isset( $_POST['field_settings'] ) ? wp_unslash( $_POST['field_settings'] ) : array();
			if ( is_array( $settings ) ) {
				$field->admin_save_settings( map_deep( $settings, 'sanitize_text_field' ) );
			}
		}

		/**
		 * Fires after a field is saved via Settings 2.0.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param BP_XProfile_Field $field Saved field object.
		 */
		do_action( 'xprofile_fields_saved_field', $field );

		wp_send_json_success(
			array(
				'message'  => __( 'Field saved successfully.', 'buddyboss' ),
				'field_id' => $saved_id,
			)
		);
	}

	/**
	 * Delete a profile field.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function delete_profile_field() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$field_id = isset( $_POST['field_id'] ) ? absint( wp_unslash( $_POST['field_id'] ) ) : 0;

		if ( empty( $field_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Field ID is required.', 'buddyboss' ) ) );
		}

		$field = xprofile_get_field( $field_id, null, false );
		if ( ! $field ) {
			wp_send_json_error( array( 'message' => __( 'Invalid field.', 'buddyboss' ) ) );
		}

		if ( ! $field->can_delete ) {
			wp_send_json_error( array( 'message' => __( 'This field cannot be deleted.', 'buddyboss' ) ) );
		}

		// Delete field and its user data, matching legacy xprofile_admin_delete_field() behavior.
		// xprofile_delete_field() defaults to $delete_data=false, so call ->delete(true) directly.
		$field_obj = new BP_XProfile_Field( $field_id );
		$result    = $field_obj->delete( true );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'There was an error deleting the field. Please try again.', 'buddyboss' ) ) );
		}

		/**
		 * Fires after a field is deleted via Settings 2.0.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param BP_XProfile_Field $field Deleted field object.
		 */
		do_action( 'xprofile_fields_deleted_field', $field );

		wp_send_json_success(
			array( 'message' => __( 'Field deleted successfully.', 'buddyboss' ) )
		);
	}

	/**
	 * Reorder field groups and fields within groups.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function reorder_fields() {
		global $wpdb;

		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below per item.
		$group_order = isset( $_POST['group_order'] ) ? wp_unslash( $_POST['group_order'] ) : array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below per item.
		$field_order = isset( $_POST['field_order'] ) ? wp_unslash( $_POST['field_order'] ) : array();
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Reorder groups.
		if ( ! empty( $group_order ) && is_array( $group_order ) ) {
			foreach ( $group_order as $position => $group_id ) {
				xprofile_update_field_group_position( absint( $group_id ), absint( $position ) );
			}
		}

		// Reorder fields within groups.
		if ( ! empty( $field_order ) && is_array( $field_order ) ) {
			// Batch-fetch field→group_id mapping to avoid N+1 xprofile_get_field() calls.
			$all_submitted_ids = array();
			foreach ( $field_order as $fields ) {
				if ( is_array( $fields ) ) {
					foreach ( $fields as $fid ) {
						$all_submitted_ids[] = absint( $fid );
					}
				}
			}

			$field_group_map = array();
			if ( ! empty( $all_submitted_ids ) ) {
				$bp           = buddypress();
				$placeholders = implode( ',', array_fill( 0, count( $all_submitted_ids ), '%d' ) );
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Placeholders are safe integers.
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT id, group_id FROM {$bp->profile->table_name_fields} WHERE id IN ( {$placeholders} )",
						...$all_submitted_ids
					)
				);
				foreach ( $rows as $row ) {
					$field_group_map[ (int) $row->id ] = (int) $row->group_id;
				}
			}

			foreach ( $field_order as $group_id => $fields ) {
				if ( ! is_array( $fields ) ) {
					continue;
				}
				$sanitized_group_id = absint( $group_id );
				foreach ( $fields as $position => $field_id ) {
					$sanitized_field_id = absint( $field_id );

					// Validate field exists before repositioning.
					if ( ! isset( $field_group_map[ $sanitized_field_id ] ) ) {
						continue;
					}

					$current_group_id = $field_group_map[ $sanitized_field_id ];

					// Cross-field-set move — only proceed when the move is permitted.
					if (
						$current_group_id !== $sanitized_group_id &&
						! $this->bb_can_move_field_to_group( $sanitized_field_id, $current_group_id, $sanitized_group_id )
					) {
						continue;
					}

					xprofile_update_field_position( $sanitized_field_id, absint( $position ), $sanitized_group_id );
				}
			}
		}

		wp_send_json_success(
			array( 'message' => __( 'Order updated successfully.', 'buddyboss' ) )
		);
	}

	/**
	 * Format a field object for the JSON response.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param BP_XProfile_Field $field Field object.
	 * @return array Formatted field data.
	 */
	private function bb_format_field( $field ) {
		$default_visibility      = bp_xprofile_get_meta( $field->id, 'field', 'default_visibility' );
		$allow_custom_visibility = bp_xprofile_get_meta( $field->id, 'field', 'allow_custom_visibility' );
		$alternate_name          = bp_xprofile_get_meta( $field->id, 'field', 'alternate_name' );
		$signup_position         = bp_xprofile_get_meta( $field->id, 'field', 'signup_position' );
		$placeholder_text        = bp_xprofile_get_meta( $field->id, 'field', '_placeholder_text' );

		// Get field options for multi-option types. `socialnetworks` is
		// kept in this list for the same reason it lives in the matching
		// save-side allowlist (`bb_handle_field_options`): the field's
		// children rows hold the picked providers, and the React modal
		// reads them back from the `options` payload to seed
		// `selectedSocialNetworks`. Without this entry, the response
		// returns `options: []`, React falls back to the hard-coded
		// default `['facebook', 'twitter', 'linkedIn']` (modal line
		// 219), and any custom provider (instagram, medium, etc.) the
		// admin saved is invisible on reopen.
		$options      = array();
		$option_types = array( 'selectbox', 'multiselectbox', 'checkbox', 'radio', 'gender', 'socialnetworks' );
		if ( in_array( $field->type, $option_types, true ) ) {
			// Note: get_children() is called per multi-option field. Object cache prevents
			// duplicate DB hits after first call, and typical groups have few such fields,
			// so a custom batch loader would be over-engineering for the typical use case.
			$children = $field->get_children();
			if ( ! empty( $children ) ) {
				foreach ( $children as $child ) {
					$options[] = array(
						'id'         => (int) $child->id,
						'name'       => $child->name,
						'is_default' => (bool) $child->is_default_option,
						'order'      => (int) $child->option_order,
					);
				}
			}
		}

		// Get member types — only return explicitly assigned types.
		// When no types are saved in meta, the field is available to ALL types,
		// so we return an empty array (Figma: badge only shown for restricted fields).
		$member_types     = array();
		$member_type_mode = 'all';
		$raw_type_meta    = bp_xprofile_get_meta( $field->id, 'field', 'member_type', false );
		$has_saved_types  = is_array( $raw_type_meta ) && ! empty( $raw_type_meta ) && ! in_array( '_none', $raw_type_meta, true );

		if ( is_array( $raw_type_meta ) && in_array( '_none', $raw_type_meta, true ) ) {
			// Field is disassociated from all types (empty set_member_types call).
			$member_type_mode = 'none';
		} elseif ( $has_saved_types && method_exists( $field, 'get_member_types' ) ) {
			$resolved_types = $field->get_member_types();
			if ( null !== $resolved_types && ! empty( $resolved_types ) ) {
				// Check if only the 'null' pseudo-type is saved (users with no profile type).
				if ( 1 === count( $resolved_types ) && in_array( 'null', $resolved_types, true ) ) {
					$member_type_mode = 'none';
				} else {
					// Filter out 'null' from the types list for the React UI.
					$member_types     = array_values(
						array_filter(
							$resolved_types,
							function ( $t ) {
								return 'null' !== $t;
							}
						)
					);
					$member_type_mode = ! empty( $member_types ) ? 'selected' : 'all';
				}
			}
		}

		// Get type-specific settings for fields that have a settings section (datebox, telephone).
		$field_settings = array();
		if ( $field->type_obj && $field->type_obj->do_settings_section() ) {
			if ( 'datebox' === $field->type ) {
				$field_settings = BP_XProfile_Field_Type_Datebox::get_field_settings( $field->id );
			} elseif ( 'telephone' === $field->type ) {
				$field_settings = $field->type_obj->get_field_settings( $field );
			}
		}

		$data = array(
			'id'                      => (int) $field->id,
			'name'                    => $field->name,
			'type'                    => $field->type,
			'is_required'             => (bool) $field->is_required,
			'can_delete'              => (bool) $field->can_delete,
			'is_default_field'        => $this->bb_is_default_field( $field->id ),
			'field_order'             => (int) $field->field_order,
			'alternate_name'          => $alternate_name ? $alternate_name : '',
			'description'             => $field->description,
			'member_types'            => $member_types,
			'member_type_mode'        => $member_type_mode,
			'visibility'              => ! empty( $default_visibility ) ? $default_visibility : 'public',
			'allow_custom_visibility' => ! empty( $allow_custom_visibility ) ? $allow_custom_visibility : 'allowed',
			'is_signup'               => ! empty( $signup_position ),
			'signup_position'         => $signup_position ? (int) $signup_position : 0,
			'placeholder'             => $placeholder_text ? $placeholder_text : '',
			'options'                 => $options,
		);

		if ( ! empty( $field_settings ) ) {
			$data['field_settings'] = $field_settings;
		}

		return $data;
	}

	/**
	 * Determine whether a field is a platform "default"/synced field.
	 *
	 * Based on legacy `BP_XProfile_Field::is_default_field()` (see
	 * `class-bp-xprofile-field.php` ~line 1832), but intentionally a superset:
	 * the synced set is always the Nickname field, plus First name when the
	 * display-name format needs it, plus Last name when the format is
	 * `first_last_name` (legacy omits Last name there, but it equally backs the
	 * display name and must stay in the base field set, so it's protected from
	 * cross-field-set moves here). WP object cache memoises the underlying
	 * lookups, so calling this per field has no extra DB cost.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param int $field_id Field ID.
	 * @return bool True when the field is a platform default field.
	 */
	private function bb_is_default_field( $field_id ) {
		$synced_field_ids = array( (int) bp_xprofile_nickname_field_id() );
		$dn_format        = function_exists( 'bp_core_display_name_format' ) ? bp_core_display_name_format() : '';
		if ( 'first_last_name' === $dn_format || 'first_name' === $dn_format ) {
			$synced_field_ids[] = (int) bp_xprofile_firstname_field_id();
		}
		if ( 'first_last_name' === $dn_format ) {
			$synced_field_ids[] = (int) bp_xprofile_lastname_field_id();
		}

		return in_array( (int) $field_id, $synced_field_ids, true );
	}

	/**
	 * Determine whether a field may be moved from one field set to another.
	 *
	 * Cross-field-set moves are blocked for fields the platform depends on
	 * staying put, and for any field entering or leaving a repeater field set
	 * (repeater sets manage their own cloned child fields and would break if a
	 * stray field were dropped in or pulled out).
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param int $field_id      Field ID being moved.
	 * @param int $from_group_id Field set the field currently belongs to.
	 * @param int $to_group_id   Field set the field is being moved into.
	 * @return bool True when the move is allowed.
	 */
	private function bb_can_move_field_to_group( $field_id, $from_group_id, $to_group_id ) {
		$field = BP_XProfile_Field::get_instance( (int) $field_id );
		if ( ! $field || empty( $field->id ) ) {
			return false;
		}

		// Required/primary fields and platform default (display-name) fields
		// must remain in their field set.
		if ( empty( $field->can_delete ) || $this->bb_is_default_field( $field_id ) ) {
			return false;
		}

		// Block moves into or out of a repeater field set.
		if (
			'on' === bp_xprofile_get_meta( (int) $from_group_id, 'group', 'is_repeater_enabled' ) ||
			'on' === bp_xprofile_get_meta( (int) $to_group_id, 'group', 'is_repeater_enabled' )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Get the next field order (max + 1) to assign within a field set.
	 *
	 * Repeater clone fields are excluded when determining the current max,
	 * matching the legacy auto-assign behavior so a clone's order never shifts
	 * the position of a newly added or moved-in field.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param int $group_id Field set (group) ID.
	 * @return int Next field order to assign.
	 */
	private function bb_get_next_field_order( $group_id ) {
		global $wpdb;
		$bp       = buddypress();
		$group_id = (int) $group_id;

		// Cloned fields should not be considered when determining the max order of fields in given group.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- BuddyPress table name properties.
		$cloned_field_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT f.id FROM {$bp->profile->table_name_fields} AS f JOIN {$bp->profile->table_name_meta} AS fm ON f.id = fm.object_id WHERE f.group_id = %d AND fm.meta_key = '_is_repeater_clone' AND fm.meta_value = 1",
				$group_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! empty( $cloned_field_ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $cloned_field_ids ), '%d' ) );
			$query_args   = array_merge( array( $group_id ), array_map( 'absint', $cloned_field_ids ) );
			$field_order  = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX(field_order) FROM {$bp->profile->table_name_fields} WHERE group_id = %d AND id NOT IN ( {$placeholders} )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Placeholders are generated dynamically.
					$query_args
				)
			);
		} else {
			$field_order = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX(field_order) FROM {$bp->profile->table_name_fields} WHERE group_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- BuddyPress table name property.
					$group_id
				)
			);
		}

		return $field_order + 1;
	}

	/**
	 * Get available field types grouped for the UI.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Grouped field types.
	 */
	private function bb_get_field_types() {
		$all_types = bp_xprofile_get_field_types();

		$multi_fields  = array();
		$single_fields = array();

		$multi_types = array( 'selectbox', 'multiselectbox', 'checkbox', 'radio' );

		foreach ( $all_types as $type_key => $class_name ) {
			$label = $this->bb_get_field_type_label( $type_key );
			$item  = array(
				'value'       => $type_key,
				'label'       => $label,
				'description' => $this->bb_get_field_type_description( $type_key ),
			);

			if ( in_array( $type_key, $multi_types, true ) ) {
				$multi_fields[] = $item;
			} else {
				$single_fields[] = $item;
			}
		}

		return array(
			'multi_fields'  => $multi_fields,
			'single_fields' => $single_fields,
		);
	}

	/**
	 * Get a human-readable label for a field type key.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $type_key Field type key.
	 * @return string Label.
	 */
	private function bb_get_field_type_label( $type_key ) {
		$labels = array(
			'textbox'        => __( 'Single Line Input', 'buddyboss' ),
			'textarea'       => __( 'Paragraph Input', 'buddyboss' ),
			'selectbox'      => __( 'Dropdown', 'buddyboss' ),
			'multiselectbox' => __( 'Multi Select', 'buddyboss' ),
			'checkbox'       => __( 'Checkboxes', 'buddyboss' ),
			'radio'          => __( 'Radio Buttons', 'buddyboss' ),
			'datebox'        => __( 'Date', 'buddyboss' ),
			'number'         => __( 'Number', 'buddyboss' ),
			'telephone'      => __( 'Phone', 'buddyboss' ),
			'url'            => __( 'Website', 'buddyboss' ),
			'gender'         => __( 'Gender', 'buddyboss' ),
			'socialnetworks' => __( 'Social Networks', 'buddyboss' ),
			'membertypes'    => __( 'Profile Type', 'buddyboss' ),
		);

		if ( isset( $labels[ $type_key ] ) ) {
			return $labels[ $type_key ];
		}

		// Fallback: Try to get label from the field type class.
		$type_obj = bp_xprofile_create_field_type( $type_key );
		if ( method_exists( $type_obj, 'get_name' ) ) {
			return $type_obj->get_name();
		}

		return ucfirst( str_replace( array( '_', '-' ), ' ', $type_key ) );
	}

	/**
	 * Get a short description for a profile field type, shown below the Type
	 * dropdown in the Add/Edit Field modal.
	 *
	 * Pro and third-party plugins that register custom field types can hook
	 * `bb_admin_profile_field_type_description` to provide their own copy.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $type_key Field type key.
	 * @return string Description, or empty string if none registered.
	 */
	private function bb_get_field_type_description( $type_key ) {
		$descriptions = array(
			'textbox'        => __( 'Displays a single-line text field where users can enter short text.', 'buddyboss' ),
			'textarea'       => __( 'Displays a multi-line text field where users can enter longer text.', 'buddyboss' ),
			'selectbox'      => __( 'Displays a dropdown list where users can select one option from multiple predefined choices.', 'buddyboss' ),
			'multiselectbox' => __( 'Displays a list where users can select multiple options.', 'buddyboss' ),
			'checkbox'       => __( 'Displays multiple options where users can select one or more choices.', 'buddyboss' ),
			'radio'          => __( 'Displays multiple options where users can select one choice.', 'buddyboss' ),
			'datebox'        => __( 'Displays a date picker for selecting a specific date.', 'buddyboss' ),
			'number'         => __( 'Displays a field for entering a numeric value.', 'buddyboss' ),
			'telephone'      => __( 'Displays a field for entering a phone number.', 'buddyboss' ),
			'url'            => __( 'Displays a field for entering a website address (URL).', 'buddyboss' ),
			'gender'         => __( 'Allows users to select their gender from the options: Male, Female, or Other.', 'buddyboss' ),
			'socialnetworks' => __( 'Select one or more social networks to display as icons in the user\'s profile.', 'buddyboss' ),
			'membertypes'    => __( 'Allows users to select their profile type.', 'buddyboss' ),
		);

		$description = isset( $descriptions[ $type_key ] ) ? $descriptions[ $type_key ] : '';

		/**
		 * Filter the description shown below the Type dropdown in the
		 * Add/Edit Profile Field modal.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $description Description copy for the field type.
		 * @param string $type_key    Field type key (e.g. 'textbox', 'datebox').
		 */
		return apply_filters( 'bb_admin_profile_field_type_description', $description, $type_key );
	}

	/**
	 * Format a social network provider for the React payload.
	 *
	 * Exposes `value`, `name`, and the BB icon font class to use for the
	 * provider's brand icon. Pro and third-party plugins that register
	 * additional providers can hook
	 * `bb_admin_profile_field_social_provider_icon` to supply their own
	 * icon mapping.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param object $provider Provider object from
	 *                         bp_xprofile_social_network_provider().
	 * @return array Provider data ready to send to React.
	 */
	private function bb_format_social_provider( $provider ) {
		$value = isset( $provider->value ) ? $provider->value : '';
		$icon  = isset( self::SOCIAL_PROVIDER_ICONS[ $value ] ) ? self::SOCIAL_PROVIDER_ICONS[ $value ] : '';

		/**
		 * Filter the BB icon font class string used for a social-network
		 * provider in the Profile Field modal.
		 *
		 * The value is a full CSS class string (base weight + brand
		 * modifier) consumed verbatim by CustomSelectControl. Pass an
		 * empty string to fall back to the legacy inline SVG provided by
		 * bp_xprofile_social_network_provider().
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $icon  Full icon class string
		 *                      (e.g. 'bb-icon-l bb-icon-brand-facebook').
		 * @param string $value Provider value/slug.
		 */
		$icon = apply_filters( 'bb_admin_profile_field_social_provider_icon', $icon, $value );

		// Pass through the legacy inline SVG so providers without a BB font
		// icon (Flickr, Meetup, Quora, VK, …) can still render a brand glyph
		// via React's inline-SVG fallback path.
		$svg = isset( $provider->svg ) ? (string) $provider->svg : '';

		return array(
			'value'    => $value,
			'name'     => isset( $provider->name ) ? $provider->name : '',
			'icon'     => $icon,
			'icon_svg' => $svg,
		);
	}

	/**
	 * Get visibility levels.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Visibility levels with id and label.
	 */
	private function bb_get_visibility_levels() {
		$levels    = bp_xprofile_get_visibility_levels();
		$formatted = array();

		foreach ( $levels as $level ) {
			$formatted[] = array(
				'id'    => $level['id'],
				'label' => $level['label'],
			);
		}

		return $formatted;
	}

	/**
	 * Get active member types.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Member types with id and name.
	 */
	private function bb_get_member_types() {
		$member_type_ids = bp_get_active_member_types();
		$member_types    = array();

		if ( empty( $member_type_ids ) ) {
			return $member_types;
		}

		// Batch-load post meta to avoid N+1 queries.
		update_postmeta_cache( $member_type_ids );
		// _prime_post_caches() is public since WP 6.1; guard for WP 6.0 compat.
		if ( function_exists( '_prime_post_caches' ) ) {
			_prime_post_caches( $member_type_ids );
		}

		foreach ( $member_type_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				continue;
			}

			$type_key = get_post_meta( $post_id, '_bp_member_type_key', true );

			$member_types[] = array(
				'id'   => $type_key,
				'name' => $post->post_title,
			);
		}

		return $member_types;
	}

	/**
	 * Handle field options for multi-option field types.
	 *
	 * Sets up POST data so that BP_XProfile_Field::save() picks up the options.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param int    $field_id Field ID (0 for new fields).
	 * @param string $type     Field type.
	 */
	private function bb_handle_field_options( $field_id, $type ) {
		// `socialnetworks` is a multi-option field type too: its picked
		// providers (facebook/twitter/linkedin/...) are persisted via the
		// same `$_POST[ "{$type}_option" ][ $i ]` array that
		// `BP_XProfile_Field_Type_Social_Networks::admin_new_field_html()`
		// reads back at field edit time (`class-bp-xprofile-field-type-
		// social-networks.php:229`). Without it here, the React form's
		// `options` array is silently dropped on save and the field
		// keeps its previous providers (the symptom the user reported:
		// edited Facebook/Twitter/LinkedIn but reopened modal still
		// showed Facebook/Twitter/Medium).
		$option_types = array( 'selectbox', 'multiselectbox', 'checkbox', 'radio', 'gender', 'socialnetworks' );

		if ( ! in_array( $type, $option_types, true ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
		$raw_options    = isset( $_POST['options'] ) ? wp_unslash( $_POST['options'] ) : array();
		$default_option = isset( $_POST['default_option'] ) ? sanitize_text_field( wp_unslash( $_POST['default_option'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! is_array( $raw_options ) ) {
			return;
		}

		// Build the format that BP_XProfile_Field::save() expects.
		// BP_XProfile_Field::save() reads $_POST directly for field options,
		// sort order, and default values. This mutation MUST be called
		// immediately before xprofile_insert_field(). Keys are cleaned up
		// by PHP's request lifecycle — no manual cleanup needed.
		$_POST['fieldtype']             = $type;
		$_POST[ 'sort_order_' . $type ] = 'custom';

		foreach ( $raw_options as $index => $option ) {
			$option_name = is_array( $option ) ? sanitize_text_field( $option['name'] ) : sanitize_text_field( $option );

			$_POST[ $type . '_option' ][ $index + 1 ] = $option_name;

			// Handle default option.
			$is_default = false;
			if ( is_array( $option ) && ! empty( $option['is_default'] ) ) {
				$is_default = true;
			} elseif ( (string) $default_option === (string) $index ) {
				$is_default = true;
			}

			if ( $is_default ) {
				if ( in_array( $type, array( 'checkbox', 'multiselectbox' ), true ) ) {
					$_POST[ 'isDefault_' . $type . '_option' ][ $index + 1 ] = 1;
				} else {
					$_POST[ 'isDefault_' . $type . '_option' ] = $index + 1;
				}
			}
		}
	}

	/**
	 * Save field metadata (visibility, alternate name, signup, placeholder).
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param int $field_id Field ID.
	 */
	private function bb_save_field_meta( $field_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$visibility              = isset( $_POST['visibility'] ) ? sanitize_key( wp_unslash( $_POST['visibility'] ) ) : '';
		$allow_custom_visibility = isset( $_POST['allow_custom_visibility'] ) ? sanitize_key( wp_unslash( $_POST['allow_custom_visibility'] ) ) : '';
		$alternate_name          = isset( $_POST['alternate_name'] ) ? sanitize_text_field( wp_unslash( $_POST['alternate_name'] ) ) : '';
		$signup_position         = isset( $_POST['signup_position'] ) ? absint( wp_unslash( $_POST['signup_position'] ) ) : 0;
		$placeholder_text        = isset( $_POST['placeholder'] ) ? sanitize_text_field( wp_unslash( $_POST['placeholder'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Validate visibility against allowed levels.
		if ( ! empty( $visibility ) ) {
			$allowed_levels = wp_list_pluck( bp_xprofile_get_visibility_levels(), 'id' );
			if ( in_array( $visibility, $allowed_levels, true ) ) {
				bp_xprofile_update_field_meta( $field_id, 'default_visibility', $visibility );
			}
		}

		// Allow custom visibility.
		if ( in_array( $allow_custom_visibility, array( 'allowed', 'disabled' ), true ) ) {
			bp_xprofile_update_field_meta( $field_id, 'allow_custom_visibility', $allow_custom_visibility );
		}

		// Alternate name.
		bp_xprofile_update_field_meta( $field_id, 'alternate_name', $alternate_name );

		// Signup position.
		if ( ! empty( $signup_position ) ) {
			bp_xprofile_update_field_meta( $field_id, 'signup_position', $signup_position );
		} else {
			bp_xprofile_delete_meta( $field_id, 'field', 'signup_position' );
		}

		// Placeholder text (new in Settings 2.0).
		if ( ! empty( $placeholder_text ) ) {
			bp_xprofile_update_field_meta( $field_id, '_placeholder_text', $placeholder_text );
		} else {
			bp_xprofile_delete_meta( $field_id, 'field', '_placeholder_text' );
		}

		// Gender option order (matching legacy bp-xprofile-admin.php:611-612).
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$gender_option_order = isset( $_POST['gender_option_order'] ) ? sanitize_text_field( wp_unslash( $_POST['gender_option_order'] ) ) : '';
		if ( ! empty( $gender_option_order ) ) {
			bp_xprofile_update_field_meta( $field_id, 'gender-option-order', $gender_option_order );
		}
	}

	/**
	 * Save member types association for a field.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param int $field_id Field ID.
	 */
	private function bb_save_field_member_types( $field_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$has_member_types = isset( $_POST['has_member_types'] ) ? absint( wp_unslash( $_POST['has_member_types'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Fetch the field once for both branches.
		$field = xprofile_get_field( $field_id, null, false );
		if ( ! $field || ! method_exists( $field, 'set_member_types' ) ) {
			return;
		}

		if ( $has_member_types ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified above, sanitized below.
			$raw_types = isset( $_POST['member_types'] ) ? wp_unslash( $_POST['member_types'] ) : array();

			$member_types = array();
			if ( is_array( $raw_types ) ) {
				foreach ( $raw_types as $raw_type ) {
					// Allow 'null' pseudo-type (users with no profile type).
					if ( 'null' === $raw_type ) {
						$member_types[] = 'null';
					} else {
						$member_types[] = sanitize_key( $raw_type );
					}
				}
			}

			$field->set_member_types( $member_types );
		} else {
			// Mode is "All Profile Types" — the field must be unrestricted
			// (available to every profile type, including future ones).
			//
			// We cannot route this through `$field->set_member_types( array() )`:
			// that path interprets an empty array as "no types" and stores the
			// `_none` flag (`class-bp-xprofile-field.php:753`), which makes
			// `get_member_type_label()` render the "No Profile Type" badge in
			// the admin list and hides the field from every member type. A
			// truly unrestricted field has NO `member_type` meta row at all.
			//
			// Delete the meta directly. `bp_xprofile_delete_meta()` invalidates
			// its own meta cache, so the next `get_member_types()` call reads
			// the empty result fresh — no need (and not permitted, since the
			// property is protected) to poke `$field->member_types`.
			bp_xprofile_delete_meta( $field->id, 'field', 'member_type' );

			// Mirror the action `set_member_types()` fires at
			// `class-bp-xprofile-field.php:788` so listeners that piggy-back on
			// member-type changes still run. The platform's own listener
			// `bp_xprofile_clear_member_type_cache` (`bp-xprofile-cache.php:232`)
			// flushes the global `field_member_types` cache key — without this
			// fire, that cache stays stale and `get_member_types()` keeps
			// returning the pre-delete value until the cache TTL/eviction.
			do_action( 'bp_xprofile_field_set_member_type', $field );
		}
	}
}

// Initialize.
new BB_Admin_Profile_Fields_Ajax();
