<?php
/**
 * XProfile: User's "Profile > Edit" screen handler
 *
 * @package BuddyBoss\XProfileScreens
 * @since BuddyPress 3.0.0
 */

/**
 * Handles the display of the profile edit page by loading the correct template file.
 * Also checks to make sure this can only be accessed for the logged in users profile.
 *
 * @since BuddyPress 1.0.0
 */
function xprofile_screen_edit_profile() {

	global $wpdb;

	if ( ! bp_is_my_profile() && ! bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	// Make sure a group is set.
	if ( ! bp_action_variable( 1 ) ) {
		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_profile_slug() . '/edit/group/' . bp_xprofile_base_group_id() ) );
	}

	// Check the field group exists.
	if ( ! bp_is_action_variable( 'group' ) || ! xprofile_get_field_group( bp_action_variable( 1 ) ) ) {
		bp_do_404();
		return;
	}

	// No errors.
	$errors = false;

	// Check to see if any new information has been submitted.
	if ( isset( $_POST['field_ids'] ) ) {

		// Check the nonce.
		check_admin_referer( 'bp_xprofile_edit' );

		// First, clear the data for deleted fields, if any.
		if ( isset( $_POST['deleted_field_ids'] ) && ! empty( $_POST['deleted_field_ids'] ) ) {
			$deleted_field_ids = wp_parse_id_list( $_POST['deleted_field_ids'] );
			foreach ( $deleted_field_ids as $deleted_field_id ) {
				xprofile_delete_field_data( $deleted_field_id, bp_displayed_user_id() );
			}
		}

		// Check we have field ID's.
		if ( empty( $_POST['field_ids'] ) ) {
			if ( isset( $_POST['repeater_set_sequence'] ) && (int) bp_action_variable( 1 ) > 0 ) {
				$field_set_sequence = wp_parse_id_list( $_POST['repeater_set_sequence'] );
				bp_set_profile_field_set_count( (int) bp_action_variable( 1 ), bp_displayed_user_id(), count( (array) $field_set_sequence ) );
			}
			bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_profile_slug() . '/edit/group/' . bp_action_variable( 1 ) ) );
		}

		// Explode the posted field IDs into an array so we know which
		// fields have been submitted.
		$posted_field_ids         = wp_parse_id_list( $_POST['field_ids'] );
		$is_required              = array();
		$validations              = array();
		$is_required_fields_error = array();

		// Loop through the posted fields formatting any datebox values then validate the field.
		foreach ( (array) $posted_field_ids as $field_id ) {
			bp_xprofile_maybe_format_datebox_post_data( $field_id );

			$is_required[ $field_id ] = xprofile_check_is_required_field( $field_id );
			if ( $is_required[ $field_id ] && empty( $_POST[ 'field_' . $field_id ] ) ) {
				$errors                     = true;
				$field                      = new BP_XProfile_Field( $field_id );
				$field_name                 = $field->name;
				$is_required_fields_error[] = $field_name;
			}

			$field = new BP_XProfile_Field( $field_id );
			if ( 'membertypes' === $field->type ) {

				$member_type_name = bp_get_member_type_key( $_POST[ 'field_' . $field_id ] );

				// Get selected profile type role.
				$selected_member_type_wp_roles = get_post_meta( $_POST[ 'field_' . $field_id ], '_bp_member_type_wp_roles', true );

				if ( bp_current_user_can( 'administrator' ) ) {
					if ( empty( $selected_member_type_wp_roles ) || ( isset( $selected_member_type_wp_roles[0] ) && 'none' === $selected_member_type_wp_roles[0] ) ) {
						bp_set_member_type( bp_displayed_user_id(), '' );
						bp_set_member_type( bp_displayed_user_id(), $member_type_name );

						// If selected profile type is empty then bypass required field error for admin.
						$errors                   = false;
						$is_required[ $field_id ] = false;
					} elseif (
						(
							isset( $selected_member_type_wp_roles[0] ) &&
							'administrator' !== $selected_member_type_wp_roles[0]
						) ||
						! isset( $selected_member_type_wp_roles[0] )
					) {
						$errors                  = true;
						$bp_error_message_string = __( 'Changing this profile type would remove your Administrator role and lock you out of the WordPress admin.', 'buddyboss' );
						$validations[]           = $bp_error_message_string;
					}
				} elseif ( bp_current_user_can( 'editor' ) ) {
					if ( empty( $selected_member_type_wp_roles ) || ( isset( $selected_member_type_wp_roles[0] ) && 'none' === $selected_member_type_wp_roles[0] ) ) {
						bp_set_member_type( bp_displayed_user_id(), '' );
						bp_set_member_type( bp_displayed_user_id(), $member_type_name );

						// If selected profile type is empty then bypass required field error for editor.
						$errors                   = false;
						$is_required[ $field_id ] = false;
					} elseif (
						(
							isset( $selected_member_type_wp_roles[0] ) &&
							! in_array( $selected_member_type_wp_roles[0], array( 'editor', 'administrator' ) )
						) ||
						! isset( $selected_member_type_wp_roles[0] )
					) {
						$errors                  = true;
						$bp_error_message_string = __( 'Changing this profile type would remove your Editor role and lock you out of the WordPress admin.', 'buddyboss' );
						$validations[]           = $bp_error_message_string;
					}
				} else {
					bp_set_member_type( bp_displayed_user_id(), '' );
					bp_set_member_type( bp_displayed_user_id(), $member_type_name );

					if ( isset( $selected_member_type_wp_roles[0] ) && 'none' !== $selected_member_type_wp_roles[0] ) {
						$bp_current_user = new WP_User( bp_displayed_user_id() );

						foreach ( $bp_current_user->roles as $role ) {
							// Remove role.
							$bp_current_user->remove_role( $role );
						}

						// Add role.
						$bp_current_user->add_role( $selected_member_type_wp_roles[0] );
					}
				}
			}

			if ( isset( $_POST[ 'field_' . $field_id ] ) && $message = xprofile_validate_field( $field_id, $_POST[ 'field_' . $field_id ], bp_displayed_user_id() ) ) {
				$errors        = true;
				$validations[] = $message;
			}
		}

		if ( ! empty( $errors ) ) {

			// Now we've checked for required fields, lets save the values.
			$old_values = $new_values = array();
			foreach ( (array) $posted_field_ids as $field_id ) {

				$field_visibility = xprofile_get_field_visibility_level( $field_id, bp_displayed_user_id() );

				$old_values[ $field_id ] = array(
					'value'      => xprofile_get_field_data( $field_id, bp_displayed_user_id() ),
					'visibility' => $field_visibility,
				);

				$new_values[ $field_id ] = array(
					'value'      => $_POST[ 'field_' . $field_id ] ?? '',
					'visibility' => $field_visibility,
				);
			}

			/**
			 * Fires after getting error while updating the profile.
			 *
			 * @since BuddyBoss 2.2.5
			 *
			 * @param int   $value            Displayed user ID.
			 * @param array $posted_field_ids Array of field IDs that were edited.
			 * @param bool  $errors           Whether or not any errors occurred.
			 * @param array $old_values       Array of original values before updated.
			 * @param array $new_values       Array of newly saved values after update.
			 */
			do_action( 'bb_xprofile_error_on_updated_profile', bp_displayed_user_id(), $posted_field_ids, $errors, $old_values, $new_values );
		}

		// There are validation errors.
		if ( ! empty( $errors ) && $validations ) {
			foreach ( $validations as $validation ) {
				bp_core_add_message( $validation, 'error' );
			}

			// There are errors.
		} elseif ( ! empty( $errors ) ) {
			if ( count( $is_required_fields_error ) > 1 ) {
				bp_core_add_message( __( 'Your changes have not been saved. Please fill in all required fields, and save your changes again.', 'buddyboss' ), 'error' );
			} else {
				$message_error = sprintf( __( '%s is required and not allowed to be empty.', 'buddyboss' ), implode( ', ', $is_required_fields_error ) );
				bp_core_add_message( $message_error, 'error' );
			}

			// No errors.
		} else {

			// Reset the errors var.
			$errors = false;

			// Now we've checked for required fields, lets save the values.
			$old_values = $new_values = array();
			foreach ( (array) $posted_field_ids as $field_id ) {

				// Certain types of fields (checkboxes, multiselects) may come through empty. Save them as an empty array so that they don't get overwritten by the default on the next edit.
				$value = isset( $_POST[ 'field_' . $field_id ] ) ? $_POST[ 'field_' . $field_id ] : '';

				$visibility_level = ! empty( $_POST[ 'field_' . $field_id . '_visibility' ] ) ? $_POST[ 'field_' . $field_id . '_visibility' ] : 'public';

				// Save the old and new values. They will be
				// passed to the filter and used to determine
				// whether an activity item should be posted.
				$old_values[ $field_id ] = array(
					'value'      => xprofile_get_field_data( $field_id, bp_displayed_user_id() ),
					'visibility' => xprofile_get_field_visibility_level( $field_id, bp_displayed_user_id() ),
				);

				// Update the field data and visibility level.
				xprofile_set_field_visibility_level( $field_id, bp_displayed_user_id(), $visibility_level );
				$field_updated = xprofile_set_field_data( $field_id, bp_displayed_user_id(), $value, $is_required[ $field_id ] );

				// We need to pass post value here.
				// If we get value from xprofile_get_field_data function then date format change and it will not validate as per Y-m-d 00:00:00 format.
				$new_values[ $field_id ] = array(
					'value'      => $value,
					'visibility' => xprofile_get_field_visibility_level( $field_id, bp_displayed_user_id() ),
				);

				$value = xprofile_get_field_data( $field_id, bp_displayed_user_id() );

				if ( ! $field_updated ) {
					$errors = true;
				} else {

					/**
					 * Fires on each iteration of an XProfile field being saved with no error.
					 *
					 * @since BuddyPress 1.1.0
					 *
					 * @param int    $field_id ID of the field that was saved.
					 * @param string $value    Value that was saved to the field.
					 */
					do_action( 'xprofile_profile_field_data_updated', $field_id, $value );
				}
			}

			/**
			 * Fires after all XProfile fields have been saved for the current profile.
			 *
			 * @since BuddyPress 1.0.0
			 *
			 * @param int   $value            Displayed user ID.
			 * @param array $posted_field_ids Array of field IDs that were edited.
			 * @param bool  $errors           Whether or not any errors occurred.
			 * @param array $old_values       Array of original values before updated.
			 * @param array $new_values       Array of newly saved values after update.
			 */
			do_action( 'xprofile_updated_profile', bp_displayed_user_id(), $posted_field_ids, $errors, $old_values, $new_values );

			// Set the feedback messages.
			if ( ! empty( $errors ) ) {
				bp_core_add_message( __( 'There was a problem updating some of your profile information. Please try again.', 'buddyboss' ), 'error' );
			} else {
				bp_core_add_message( __( 'Changes saved.', 'buddyboss' ) );
			}

			// Redirect back to the edit screen to display the updates and message.
			bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_profile_slug() . '/edit/group/' . bp_action_variable( 1 ) ) );
		}
	}

	/**
	 * Fires right before the loading of the XProfile edit screen template file.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'xprofile_screen_edit_profile' );

	/**
	 * Filters the template to load for the XProfile edit screen.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $template Path to the XProfile edit template to load.
	 */
	bp_core_load_template( apply_filters( 'xprofile_template_edit_profile', 'members/single/home' ) );
}
