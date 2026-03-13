<?php
/**
 * BuddyBoss Profile Fields Admin AJAX Handler
 *
 * Handles AJAX requests for Profile Fields (XProfile) CRUD
 * in the Settings 2.0 admin interface.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Profile_Fields_Ajax
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Admin_Profile_Fields_Ajax {

	/**
	 * Nonce action (shared with BB_Admin_Settings_Ajax).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'bb_admin_settings';

	/**
	 * Verify AJAX request (capability + nonce).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	private function bb_verify_request() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );
	}

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
	 * @since BuddyBoss [BBVERSION]
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
			$providers = bp_xprofile_social_network_provider();
			$response['social_providers'] = array_map(
				function ( $provider ) {
					return array(
						'value' => $provider->value,
						'name'  => $provider->name,
					);
				},
				$providers
			);
		}

		wp_send_json_success( $response );
	}

	/**
	 * Create a new field group.
	 *
	 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function update_field_group() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$group_id          = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
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
		 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function delete_field_group() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;

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
		 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function save_profile_field() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$field_id    = isset( $_POST['field_id'] ) ? absint( $_POST['field_id'] ) : 0;
		$group_id    = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
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

			// Preserve existing field_order on edit so the field doesn't jump to position 0.
			$existing_field = xprofile_get_field( $field_id );
			if ( $existing_field ) {
				$args['field_order'] = (int) $existing_field->field_order;
			}
		}

		// Auto-assign field order for new fields (exclude repeater clones, matching legacy behavior).
		if ( empty( $field_id ) ) {
			global $wpdb;
			$bp = buddypress();

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

			++$field_order;
			$args['field_order'] = $field_order;
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
		 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function delete_profile_field() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'xprofile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Profile component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$field_id = isset( $_POST['field_id'] ) ? absint( $_POST['field_id'] ) : 0;

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
		 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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

					// Validate field exists and belongs to this group before repositioning.
					if ( ! isset( $field_group_map[ $sanitized_field_id ] ) || $field_group_map[ $sanitized_field_id ] !== $sanitized_group_id ) {
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
	 * @since BuddyBoss [BBVERSION]
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

		// Get field options for multi-option types.
		$options      = array();
		$option_types = array( 'selectbox', 'multiselectbox', 'checkbox', 'radio', 'gender' );
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
					$member_types     = array_values( array_filter( $resolved_types, function ( $t ) {
						return 'null' !== $t;
					} ) );
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
	 * Get available field types grouped for the UI.
	 *
	 * @since BuddyBoss [BBVERSION]
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
				'value' => $type_key,
				'label' => $label,
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
	 * @since BuddyBoss [BBVERSION]
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
	 * Get visibility levels.
	 *
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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
		_prime_post_caches( $member_type_ids );

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
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int    $field_id Field ID (0 for new fields).
	 * @param string $type     Field type.
	 */
	private function bb_handle_field_options( $field_id, $type ) {
		$option_types = array( 'selectbox', 'multiselectbox', 'checkbox', 'radio', 'gender' );

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
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $field_id Field ID.
	 */
	private function bb_save_field_member_types( $field_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$has_member_types = isset( $_POST['has_member_types'] ) ? absint( wp_unslash( $_POST['has_member_types'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( $has_member_types ) {
			$field = xprofile_get_field( $field_id, null, false );
			if ( ! $field || ! method_exists( $field, 'set_member_types' ) ) {
				return;
			}

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
			// Mode is 'all' — remove all member type restrictions so the field is
			// available to every type, including types registered in the future.
			// Passing an empty array to set_member_types() deletes all meta rows,
			// which BuddyPress interprets as "unrestricted" (same as legacy behavior).
			$field = xprofile_get_field( $field_id, null, false );
			if ( $field && method_exists( $field, 'set_member_types' ) ) {
				$field->set_member_types( array() );
			}
		}
	}
}

// Initialize.
new BB_Admin_Profile_Fields_Ajax();
