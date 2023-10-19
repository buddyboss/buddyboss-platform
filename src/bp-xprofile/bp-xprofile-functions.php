<?php
/**
 * BuddyPress XProfile Filters.
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 *
 * @package BuddyBoss\XProfile
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/*** Field Group Management **************************************************/

/**
 * Fetch a set of field groups, populated with fields and field data.
 *
 * Procedural wrapper for BP_XProfile_Group::get() method.
 *
 * @since BuddyPress 2.1.0
 *
 * @param array $args See {@link BP_XProfile_Group::get()} for description of arguments.
 * @return array $groups
 */
function bp_xprofile_get_groups( $args = array() ) {

	/**
	 * For repeaters, automatically set the parameter value
	 * to determine if we should display only the template fields
	 * or only the clone fields
	 */

	if ( ! isset( $args['repeater_show_main_fields_only'] ) ) {
		$repeater_show_main_fields_only = true;

		// If on a user profile
		if ( 'profile' == bp_current_component() ) {
			$repeater_show_main_fields_only = false;
		}

		$args['repeater_show_main_fields_only'] = apply_filters( 'bp_xprofile_get_groups_repeater_show_main_fields_only', $repeater_show_main_fields_only );
	}

	$groups = BP_XProfile_Group::get( $args );

	/**
	 * Filters a set of field groups, populated with fields and field data.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param array $groups Array of field groups and field data.
	 * @param array $args   Array of arguments used to query for groups.
	 */
	return apply_filters( 'bp_xprofile_get_groups', $groups, $args );
}

/**
 * Insert a new profile field group.
 *
 * @since BuddyPress 1.0.0
 *
 * @param array|string $args {
 *    Array of arguments for field group insertion.
 *
 *    @type int|bool    $field_group_id ID of the field group to insert into.
 *    @type string|bool $name           Name of the group.
 *    @type string      $description    Field group description.
 *    @type bool        $can_delete     Whether or not the field group can be deleted.
 * }
 * @return boolean
 */
function xprofile_insert_field_group( $args = '' ) {

	// Parse the arguments.
	$r = bp_parse_args(
		$args,
		array(
			'field_group_id' => false,
			'name'           => false,
			'description'    => '',
			'can_delete'     => true,
		),
		'xprofile_insert_field_group'
	);

	// Bail if no group name.
	if ( empty( $r['name'] ) ) {
		return false;
	}

	// Create new field group object, maybe using an existing ID.
	$field_group              = new BP_XProfile_Group( $r['field_group_id'] );
	$field_group->name        = $r['name'];
	$field_group->description = $r['description'];
	$field_group->can_delete  = $r['can_delete'];

	return $field_group->save();
}

/**
 * Get a specific profile field group.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $field_group_id Field group ID to fetch.
 * @return false|BP_XProfile_Group
 */
function xprofile_get_field_group( $field_group_id = 0 ) {

	// Try to get a specific field group by ID.
	$field_group = new BP_XProfile_Group( $field_group_id );

	// Bail if group was not found.
	if ( empty( $field_group->id ) ) {
		return false;
	}

	// Return field group.
	return $field_group;
}

/**
 * Delete a specific profile field group.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $field_group_id Field group ID to delete.
 * @return boolean
 */
function xprofile_delete_field_group( $field_group_id = 0 ) {

	// Try to get a specific field group by ID.
	$field_group = xprofile_get_field_group( $field_group_id );

	// Bail if group was not found.
	if ( false === $field_group ) {
		return false;
	}

	// Return the results of trying to delete the field group.
	return $field_group->delete();
}

/**
 * Update the position of a specific profile field group.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $field_group_id Field group ID to update.
 * @param int $position       Field group position to update to.
 * @return boolean
 */
function xprofile_update_field_group_position( $field_group_id = 0, $position = 0 ) {
	return BP_XProfile_Group::update_position( $field_group_id, $position );
}

/*** Field Management *********************************************************/

/**
 * Get details of all xprofile field types.
 *
 * @since BuddyPress 2.0.0
 *
 * @return array Key/value pairs (field type => class name).
 */
function bp_xprofile_get_field_types() {
	$fields = array(
		'checkbox'       => 'BP_XProfile_Field_Type_Checkbox',
		'datebox'        => 'BP_XProfile_Field_Type_Datebox',
		'multiselectbox' => 'BP_XProfile_Field_Type_Multiselectbox',
		'number'         => 'BP_XProfile_Field_Type_Number',
		'url'            => 'BP_XProfile_Field_Type_URL',
		'radio'          => 'BP_XProfile_Field_Type_Radiobutton',
		'selectbox'      => 'BP_XProfile_Field_Type_Selectbox',
		'textarea'       => 'BP_XProfile_Field_Type_Textarea',
		'textbox'        => 'BP_XProfile_Field_Type_Textbox',
		'telephone'      => 'BP_XProfile_Field_Type_Telephone',
		'gender'         => 'BP_XProfile_Field_Type_Gender',
		'socialnetworks' => 'BP_XProfile_Field_Type_Social_Networks',
	);

	if ( function_exists( 'bp_member_type_enable_disable' ) && true === bp_member_type_enable_disable() ) {
		$fields['membertypes'] = 'BP_XProfile_Field_Type_Member_Types';
	}

	/**
	 * Filters the list of all xprofile field types.
	 *
	 * If you've added a custom field type in a plugin, register it with this filter.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param array $fields Array of field type/class name pairings.
	 */
	return apply_filters( 'bp_xprofile_get_field_types', $fields );
}

/**
 * Creates the specified field type object; used for validation and templating.
 *
 * @since BuddyPress 2.0.0
 *
 * @param string $type Type of profile field to create. See {@link bp_xprofile_get_field_types()} for default core values.
 * @return object $value If field type unknown, returns BP_XProfile_Field_Type_Textarea.
 *                       Otherwise returns an instance of the relevant child class of BP_XProfile_Field_Type.
 */
function bp_xprofile_create_field_type( $type ) {

	$field = bp_xprofile_get_field_types();
	$class = isset( $field[ $type ] ) ? $field[ $type ] : '';

	/**
	 * To handle (missing) field types, fallback to a placeholder field object if a type is unknown.
	 */
	if ( $class && class_exists( $class ) ) {
		return new $class();
	} else {
		return new BP_XProfile_Field_Type_Placeholder();
	}
}

/**
 * Insert or update an xprofile field.
 *
 * @since BuddyPress 1.1.0
 *
 * @param array|string $args {
 *     Array of arguments.
 *     @type int    $field_id          Optional. Pass the ID of an existing field to edit that field.
 *     @type int    $field_group_id    ID of the associated field group.
 *     @type int    $parent_id         Optional. ID of the parent field.
 *     @type string $type              Field type. Checked against a field_types whitelist.
 *     @type string $name              Name of the new field.
 *     @type string $description       Optional. Descriptive text for the field.
 *     @type bool   $is_required       Optional. Whether users must provide a value for the field. Default: false.
 *     @type bool   $can_delete        Optional. Whether admins can delete this field in the Dashboard interface.
 *                                     Generally this is false only for the Name field, which is required throughout BP.
 *                                     Default: true.
 *     @type string $order_by          Optional. For field types that support options (such as 'radio'), this flag
 *                                     determines whether the sort order of the options will be 'default'
 *                                     (order created) or 'custom'.
 *     @type bool   $is_default_option Optional. For the 'option' field type, setting this value to true means that
 *                                     it'll be the default value for the parent field when the user has not yet
 *                                     overridden. Default: true.
 *     @type int    $option_order      Optional. For the 'option' field type, this determines the order in which the
 *                                     options appear.
 * }
 * @return bool|int False on failure, ID of new field on success.
 */
function xprofile_insert_field( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'field_id'          => null,
			'field_group_id'    => null,
			'parent_id'         => null,
			'type'              => '',
			'name'              => '',
			'description'       => '',
			'is_required'       => false,
			'can_delete'        => true,
			'order_by'          => '',
			'is_default_option' => false,
			'option_order'      => null,
			'field_order'       => null,
		)
	);

	// Field_group_id is required.
	if ( empty( $r['field_group_id'] ) ) {
		return false;
	}

	// Check this is a non-empty, valid field type.
	if ( ! in_array( $r['type'], (array) buddypress()->profile->field_types ) ) {
		return false;
	}

	// Instantiate a new field object.
	if ( ! empty( $r['field_id'] ) ) {
		$field = xprofile_get_field( $r['field_id'] );
	} else {
		$field = new BP_XProfile_Field();
	}

	$field->group_id = $r['field_group_id'];
	$field->type     = $r['type'];

	// The 'name' field cannot be empty.
	if ( ! empty( $r['name'] ) ) {
		$field->name = $r['name'];
	}

	$field->description       = $r['description'];
	$field->order_by          = $r['order_by'];
	$field->parent_id         = (int) $r['parent_id'];
	$field->field_order       = (int) $r['field_order'];
	$field->option_order      = (int) $r['option_order'];
	$field->is_required       = (bool) $r['is_required'];
	$field->can_delete        = (bool) $r['can_delete'];
	$field->is_default_option = (bool) $r['is_default_option'];

	return $field->save();
}

/**
 * Get a profile field object.
 *
 * @since BuddyPress 1.1.0
 * @since BuddyPress 2.8.0 Added `$user_id` and `$get_data` parameters.
 *
 * @param int|object $field    ID of the field or object representing field data.
 * @param int|null   $user_id  Optional. ID of the user associated with the field.
 *                             Ignored if `$get_data` is false. If `$get_data` is
 *                             true, but no `$user_id` is provided, defaults to
 *                             logged-in user ID.
 * @param bool       $get_data Whether to fetch data for the specified `$user_id`.
 * @return BP_XProfile_Field|null Field object if found, otherwise null.
 */
function xprofile_get_field( $field, $user_id = null, $get_data = true ) {
	if ( $field instanceof BP_XProfile_Field ) {
		$_field = $field;
	} elseif ( is_object( $field ) ) {
		$_field = new BP_XProfile_Field();
		$_field->fill_data( $field );
	} else {
		$_field = BP_XProfile_Field::get_instance( $field, $user_id, $get_data );
	}

	if ( ! $_field ) {
		return null;
	}

	return $_field;
}

/**
 * Delete a profile field object.
 *
 * @since BuddyPress 1.1.0
 *
 * @param int|object $field_id ID of the field or object representing field data.
 * @return bool Whether or not the field was deleted.
 */
function xprofile_delete_field( $field_id ) {
	$field = new BP_XProfile_Field( $field_id );
	return $field->delete();
}

/*** Field Data Management *****************************************************/


/**
 * Fetches profile data for a specific field for the user.
 *
 * When the field value is serialized, this function unserializes and filters
 * each item in the array.
 *
 * @since BuddyPress 1.0.0
 *
 * @param mixed  $field        The ID of the field, or the $name of the field.
 * @param int    $user_id      The ID of the user.
 * @param string $multi_format How should array data be returned? 'comma' if you want a
 *                             comma-separated string; 'array' if you want an array.
 * @return mixed The profile field data.
 */
function xprofile_get_field_data( $field, $user_id = 0, $multi_format = 'array' ) {

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	if ( is_numeric( $field ) ) {
		$field_id = $field;
	} else {
		$field_id = xprofile_get_field_id_from_name( $field );
	}

	if ( empty( $field_id ) ) {
		return false;
	}

	$values = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $field_id, $user_id ) );

	if ( is_array( $values ) ) {
		$data = array();
		foreach ( (array) $values as $value ) {

			/**
			 * Filters the field data value for a specific field for the user.
			 *
			 * @since BuddyPress 1.0.0
			 *
			 * @param string $value    Value saved for the field.
			 * @param int    $field_id ID of the field being displayed.
			 * @param int    $user_id  ID of the user being displayed.
			 */
			$data[] = apply_filters( 'xprofile_get_field_data', $value, $field_id, $user_id );
		}

		if ( 'comma' == $multi_format ) {
			$data = implode( ', ', $data );
		}
	} else {
		/** This filter is documented in bp-xprofile/bp-xprofile-functions.php */
		$data = apply_filters( 'xprofile_get_field_data', $values, $field_id, $user_id );
	}

	return $data;
}

/**
 * A simple function to set profile data for a specific field for a specific user.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int|string $field       The ID of the field, or the $name of the field.
 * @param int        $user_id     The ID of the user.
 * @param mixed      $value       The value for the field you want to set for the user.
 * @param bool       $is_required Whether or not the field is required.
 * @return bool True on success, false on failure.
 */
function xprofile_set_field_data( $field, $user_id, $value, $is_required = false ) {

	if ( is_numeric( $field ) ) {
		$field_id = $field;
	} else {
		$field_id = xprofile_get_field_id_from_name( $field );
	}

	if ( empty( $field_id ) ) {
		return false;
	}

	$field          = xprofile_get_field( $field_id );
	$field_type     = BP_XProfile_Field::get_type( $field_id );
	$field_type_obj = bp_xprofile_create_field_type( $field_type );

	/**
	 * Filter the raw submitted profile field value.
	 *
	 * Use this filter to modify the values submitted by users before
	 * doing field-type-specific validation.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param mixed                  $value          Value passed to xprofile_set_field_data().
	 * @param BP_XProfile_Field      $field          Field object.
	 * @param BP_XProfile_Field_Type $field_type_obj Field type object.
	 */
	$value = apply_filters( 'bp_xprofile_set_field_data_pre_validate', $value, $field, $field_type_obj );

	// Special-case support for integer 0 for the number field type.
	if ( $is_required && ! is_integer( $value ) && $value !== '0' && ( empty( $value ) || ! is_array( $value ) && ! strlen( trim( $value ) ) ) ) {
		return false;
	}

	/**
	 * Certain types of fields (checkboxes, multiselects) may come through empty.
	 * Save as empty array so this isn't overwritten by the default on next edit.
	 *
	 * Special-case support for integer 0 for the number field type
	 */
	if ( empty( $value ) && ! is_integer( $value ) && $value !== '0' && $field_type_obj->accepts_null_value ) {
		$value = array();
	}

	// If the value is empty, then delete any field data that exists, unless the field is of a type
	// where null values are semantically meaningful.
	if ( empty( $value ) && ! is_integer( $value ) && $value !== '0' && ! $field_type_obj->accepts_null_value ) {
		xprofile_delete_field_data( $field_id, $user_id );
		return true;
	}

	// For certain fields, only certain parameters are acceptable, so add them to the whitelist.
	if ( $field_type_obj->supports_options ) {
		$field_type_obj->set_whitelist_values( wp_list_pluck( $field->get_children(), 'name' ) );
	}

	// Check the value is in an accepted format for this form field.
	if ( ! $field_type_obj->is_valid( $value ) ) {
		return false;
	}

	$field           = new BP_XProfile_ProfileData();
	$field->field_id = $field_id;
	$field->user_id  = $user_id;

	// Gets un/reserialized via xprofile_sanitize_data_value_before_save()
	$field->value = maybe_serialize( $value );

	return $field->save();
}

/**
 * Set the visibility level for this field.
 *
 * @since BuddyPress 1.6.0
 *
 * @param int    $field_id         The ID of the xprofile field.
 * @param int    $user_id          The ID of the user to whom the data belongs.
 * @param string $visibility_level What the visibity setting should be.
 * @return bool True on success
 */
function xprofile_set_field_visibility_level( $field_id = 0, $user_id = 0, $visibility_level = '' ) {
	if ( empty( $field_id ) || empty( $user_id ) || empty( $visibility_level ) ) {
		return false;
	}

	// Check against a whitelist.
	$allowed_values = bp_xprofile_get_visibility_levels();
	if ( ! array_key_exists( $visibility_level, $allowed_values ) ) {
		return false;
	}

	// Stored in an array in usermeta.
	$current_visibility_levels = bp_get_user_meta( $user_id, 'bp_xprofile_visibility_levels', true );

	if ( ! $current_visibility_levels ) {
		$current_visibility_levels = array();
	}

	$current_visibility_levels[ $field_id ] = $visibility_level;

	return bp_update_user_meta( $user_id, 'bp_xprofile_visibility_levels', $current_visibility_levels );
}

/**
 * Get the visibility level for a field.
 *
 * @since BuddyPress 2.0.0
 *
 * @param int $field_id The ID of the xprofile field.
 * @param int $user_id The ID of the user to whom the data belongs.
 * @return string
 */
function xprofile_get_field_visibility_level( $field_id = 0, $user_id = 0 ) {
	$current_level = '';

	if ( empty( $field_id ) || empty( $user_id ) ) {
		return $current_level;
	}

	$current_levels = bp_get_user_meta( $user_id, 'bp_xprofile_visibility_levels', true );
	$current_level  = isset( $current_levels[ $field_id ] ) ? $current_levels[ $field_id ] : '';

	// Use the user's stored level, unless custom visibility is disabled.
	$field = xprofile_get_field( $field_id );
	if ( isset( $field->allow_custom_visibility ) && 'disabled' === $field->allow_custom_visibility ) {
		$current_level = $field->default_visibility;
	}

	// If we're still empty, it means that overrides are permitted, but the
	// user has not provided a value. Use the default value.
	if ( empty( $current_level ) ) {
		$current_level = $field->default_visibility;
	}

	return $current_level;
}

/**
 * Delete XProfile field data.
 *
 * @since BuddyPress 1.1.0
 *
 * @param string $field   Field to delete.
 * @param int    $user_id User ID to delete field from.
 * @return bool Whether or not the field was deleted.
 */
function xprofile_delete_field_data( $field = '', $user_id = 0 ) {

	// Get the field ID.
	if ( is_numeric( $field ) ) {
		$field_id = (int) $field;
	} else {
		$field_id = xprofile_get_field_id_from_name( $field );
	}

	// Bail if field or user ID are empty.
	if ( empty( $field_id ) || empty( $user_id ) ) {
		return false;
	}

	// Get the profile field data to delete.
	$field = new BP_XProfile_ProfileData( $field_id, $user_id );

	// Delete the field data.
	return $field->delete();
}

/**
 * Check if field is a required field.
 *
 * @since BuddyPress 1.1.0
 *
 * @param int $field_id ID of the field to check for.
 * @return bool Whether or not field is required.
 */
function xprofile_check_is_required_field( $field_id ) {
	$field  = new BP_XProfile_Field( $field_id );
	$retval = false;

	if ( isset( $field->is_required ) ) {
		$retval = $field->is_required;
	}

	return (bool) $retval;
}

/**
 * Validate profile field.
 *
 * @since BuddyBoss 1.0.0
 */
function xprofile_validate_field( $field_id, $value, $UserId ) {
	return apply_filters( 'xprofile_validate_field', '', $field_id, $value, $UserId );
}

/**
 * Returns the ID for the field based on the field name.
 *
 * @since BuddyPress 1.0.0
 *
 * @param string $field_name The name of the field to get the ID for.
 * @return int|null $field_id on success, false on failure.
 */
function xprofile_get_field_id_from_name( $field_name ) {
	return BP_XProfile_Field::get_id_from_name( $field_name );
}

/**
 * Fetches a random piece of profile data for the user.
 *
 * @since BuddyPress 1.0.0
 *
 * @global BuddyPress $bp           The one true BuddyPress instance.
 * @global wpdb $wpdb WordPress database abstraction object.
 * @global object     $current_user WordPress global variable containing current logged in user information.
 *
 * @param int  $user_id          User ID of the user to get random data for.
 * @param bool $exclude_fullname Optional; whether or not to exclude the full name field as random data.
 *                               Defaults to true.
 * @return string|bool The fetched random data for the user, or false if no data or no match.
 */
function xprofile_get_random_profile_data( $user_id, $exclude_fullname = true ) {
	$field_data = BP_XProfile_ProfileData::get_random( $user_id, $exclude_fullname );

	if ( empty( $field_data ) ) {
		return false;
	}

	$field_data[0]->value = xprofile_format_profile_field( $field_data[0]->type, $field_data[0]->value );

	if ( empty( $field_data[0]->value ) ) {
		return false;
	}

	/**
	 * Filters a random piece of profile data for the user.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param array $field_data Array holding random profile data.
	 */
	return apply_filters( 'xprofile_get_random_profile_data', $field_data );
}

/**
 * Formats a profile field according to its type. [ TODO: Should really be moved to filters ]
 *
 * @since BuddyPress 1.0.0
 *
 * @param string $field_type  The type of field: datebox, selectbox, textbox etc.
 * @param string $field_value The actual value.
 * @return string|bool The formatted value, or false if value is empty.
 */
function xprofile_format_profile_field( $field_type, $field_value ) {

	if ( empty( $field_value ) ) {
		return false;
	}

	$field_value = bp_unserialize_profile_field( $field_value );

	if ( 'datebox' != $field_type ) {
		$content     = $field_value;
		$field_value = str_replace( ']]>', ']]&gt;', $content );
	}

	return xprofile_filter_format_field_value_by_type( stripslashes_deep( $field_value ), $field_type );
}

/**
 * Update the field position for a provided field.
 *
 * @since BuddyPress 1.1.0
 *
 * @param int $field_id       ID of the field to update.
 * @param int $position       Position to update the field to.
 * @param int $field_group_id Group ID for group the field is in.
 * @return bool
 */
function xprofile_update_field_position( $field_id, $position, $field_group_id ) {
	return BP_XProfile_Field::update_position( $field_id, $position, $field_group_id );
}

/**
 * Replace the displayed and logged-in users fullnames with the xprofile name, if required.
 *
 * The Members component uses the logged-in user's display_name to set the
 * value of buddypress()->loggedin_user->fullname. However, in cases where
 * profile sync is disabled, display_name may diverge from the xprofile
 * fullname field value, and the xprofile field should take precedence.
 *
 * Runs at bp_setup_globals:100 to ensure that all components have loaded their
 * globals before attempting any overrides.
 *
 * @since BuddyPress 2.0.0
 */
function xprofile_override_user_fullnames() {
	// If sync is enabled, the two names will match. No need to continue.
	if ( ! bp_disable_profile_sync() ) {
		return;
	}

	if ( bp_loggedin_user_id() ) {
		buddypress()->loggedin_user->fullname = bp_core_get_user_displayname( bp_loggedin_user_id() );
	}

	if ( bp_displayed_user_id() ) {
		buddypress()->displayed_user->fullname = bp_core_get_user_displayname( bp_displayed_user_id() );
	}
}
add_action( 'bp_setup_globals', 'xprofile_override_user_fullnames', 100 );

/**
 * Setup the avatar upload directory for a user.
 *
 * @since BuddyPress 1.0.0
 *
 * @package BuddyBoss Core
 *
 * @param string $directory The root directory name. Optional.
 * @param int    $user_id   The user ID. Optional.
 * @return array Array containing the path, URL, and other helpful settings.
 */
function xprofile_avatar_upload_dir( $directory = 'avatars', $user_id = 0 ) {

	// Use displayed user if no user ID was passed.
	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	// Failsafe against accidentally nooped $directory parameter.
	if ( empty( $directory ) ) {
		$directory = 'avatars';
	}

	$path      = bp_core_avatar_upload_path() . '/' . $directory . '/' . $user_id;
	$newbdir   = $path;
	$newurl    = bp_core_avatar_url() . '/' . $directory . '/' . $user_id;
	$newburl   = $newurl;
	$newsubdir = '/' . $directory . '/' . $user_id;

	/**
	 * Filters the avatar upload directory for a user.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param array $value Array containing the path, URL, and other helpful settings.
	 */
	return apply_filters(
		'xprofile_avatar_upload_dir',
		array(
			'path'    => $path,
			'url'     => $newurl,
			'subdir'  => $newsubdir,
			'basedir' => $newbdir,
			'baseurl' => $newburl,
			'error'   => false,
		)
	);
}

/**
 * When search_terms are passed to BP_User_Query, search against xprofile fields.
 *
 * @since BuddyPress 2.0.0
 *
 * @param array         $sql   Clauses in the user_id SQL query.
 * @param BP_User_Query $query User query object.
 * @return array
 */
function bp_xprofile_bp_user_query_search( $sql, BP_User_Query $query ) {
	global $wpdb;

	if ( empty( $query->query_vars['search_terms'] ) || empty( $sql['where']['search'] ) ) {
		return $sql;
	}

	static $cache = array();

	$bp = buddypress();

	$search_terms_clean = bp_esc_like( wp_kses_normalize_entities( $query->query_vars['search_terms'] ) );

	$cache_key = 'bb_xprofile_user_query_search_sql_' . sanitize_title( $search_terms_clean );

	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	if ( $query->query_vars['search_wildcard'] === 'left' ) {
		$search_terms_nospace = '%' . $search_terms_clean;
		$search_terms_space   = '%' . $search_terms_clean . ' %';
	} elseif ( $query->query_vars['search_wildcard'] === 'right' ) {
		$search_terms_nospace = $search_terms_clean . '%';
		$search_terms_space   = '% ' . $search_terms_clean . '%';
	} else {
		$search_terms_nospace = '%' . $search_terms_clean . '%';
		$search_terms_space   = '%' . $search_terms_clean . '%';
	}

	// Combine the core search (against wp_users) into a single OR clause.
	// with the xprofile_data search.
	$matched_user_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT user_id FROM {$bp->profile->table_name_data} WHERE value LIKE %s OR value LIKE %s",
			$search_terms_nospace,
			$search_terms_space
		)
	);

	// Checked profile fields based on privacy settings of particular user while searching.
	if ( ! empty( $matched_user_ids ) ) {
		$matched_user_data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$bp->profile->table_name_data} WHERE value LIKE %s OR value LIKE %s",
				$search_terms_nospace,
				$search_terms_space
			)
		);

		foreach ( $matched_user_data as $key => $user ) {
			$field_visibility = xprofile_get_field_visibility_level( $user->field_id, $user->user_id );
			if ( 'adminsonly' === $field_visibility && ! current_user_can( 'administrator' ) ) {
				if ( ( $key = array_search( $user->user_id, $matched_user_ids ) ) !== false ) {
					unset( $matched_user_ids[ $key ] );
				}
			}
			if ( 'friends' === $field_visibility && ! current_user_can( 'administrator' ) && false === friends_check_friendship( intval( $user->user_id ), bp_loggedin_user_id() ) ) {
				if ( ( $key = array_search( $user->user_id, $matched_user_ids ) ) !== false ) {
					unset( $matched_user_ids[ $key ] );
				}
			}
		}
	}

	if ( ! empty( $matched_user_ids ) ) {
		$search_core            = $sql['where']['search'];
		$search_combined        = " ( u.{$query->uid_name} IN (" . implode( ',', $matched_user_ids ) . ") OR {$search_core} )";
		$sql['where']['search'] = $search_combined;
	}

	$cache[ $cache_key ] = $sql;

	return $sql;
}
add_action( 'bp_user_query_uid_clauses', 'bp_xprofile_bp_user_query_search', 10, 2 );

/**
 * Sync xprofile data to the standard built in WordPress profile data.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $user_id ID of the user to sync.
 * @return bool
 */
function xprofile_sync_wp_profile( $user_id = 0, $field_id = null ) {

	// Bail if profile syncing is disabled.
	if ( bp_disable_profile_sync() ) {
		return true;
	}

	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	// Get First, Last and Nickname field id from DB.
	$firstname_id = bp_xprofile_firstname_field_id();
	$lastname_id  = bp_xprofile_lastname_field_id();
	$nickname_id  = bp_xprofile_nickname_field_id();

	if ( ! $field_id || $field_id == $firstname_id ) {
		$firstname = xprofile_get_field_data( bp_xprofile_firstname_field_id(), $user_id );
		bp_update_user_meta( $user_id, 'first_name', $firstname );
	}

	if ( ! $field_id || $field_id == $lastname_id ) {
		$lastname = xprofile_get_field_data( bp_xprofile_lastname_field_id(), $user_id );
		bp_update_user_meta( $user_id, 'last_name', $lastname );
	}

	if ( ! $field_id || $field_id == $nickname_id ) {
		$nickname = xprofile_get_field_data( bp_xprofile_nickname_field_id(), $user_id );
		bp_update_user_meta( $user_id, 'nickname', $nickname );
	}

	bp_xprofile_update_display_name( $user_id );
}
add_action( 'bp_core_signup_user', 'xprofile_sync_wp_profile' );
add_action( 'bp_core_activated_user', 'xprofile_sync_wp_profile' );

/**
 * Update display_name in user database.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_xprofile_update_display_name( $user_id ) {
	wp_update_user(
		array(
			'ID'           => $user_id,
			'display_name' => bp_core_get_user_displayname( $user_id ),
		)
	);
}

/**
 * When a user is deleted, we need to clean up the database and remove all the
 * profile data from each table. Also we need to clean anything up in the
 * usermeta table that this component uses.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $user_id The ID of the deleted user.
 */
function xprofile_remove_data( $user_id ) {
	BP_XProfile_ProfileData::delete_data_for_user( $user_id );
}
add_action( 'wpmu_delete_user', 'xprofile_remove_data' );
add_action( 'delete_user', 'xprofile_remove_data' );
add_action( 'bp_make_spam_user', 'xprofile_remove_data' );

/*** XProfile Meta ****************************************************/

/**
 * Delete a piece of xprofile metadata.
 *
 * @since BuddyPress 1.5.0
 *
 * @param int         $object_id   ID of the object the metadata belongs to.
 * @param string      $object_type Type of object. 'group', 'field', or 'data'.
 * @param string|bool $meta_key    Key of the metadata being deleted. If omitted, all
 *                                 metadata for the object will be deleted.
 * @param mixed       $meta_value  Optional. If provided, only metadata that matches
 *                                 the value will be permitted.
 * @param bool        $delete_all  Optional. If true, delete matching metadata entries
 *                                 for all objects, ignoring the specified object_id. Otherwise, only
 *                                 delete matching metadata entries for the specified object.
 *                                 Default: false.
 * @return bool True on success, false on failure.
 */
function bp_xprofile_delete_meta( $object_id, $object_type, $meta_key = false, $meta_value = false, $delete_all = false ) {
	global $wpdb;

	// Sanitize object type.
	if ( ! in_array( $object_type, array( 'group', 'field', 'data' ) ) ) {
		return false;
	}

	// Legacy - if no meta_key is passed, delete all for the item.
	if ( empty( $meta_key ) ) {
		$table_key  = 'xprofile_' . $object_type . 'meta';
		$table_name = $wpdb->{$table_key};
		$keys       = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM {$table_name} WHERE object_type = %s AND object_id = %d", $object_type, $object_id ) );

		// Force delete_all to false if deleting all for object.
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	add_filter( 'query', 'bp_filter_metaid_column_name' );
	add_filter( 'query', 'bp_xprofile_filter_meta_query' );

	$retval = false;
	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'xprofile_' . $object_type, $object_id, $key, $meta_value, $delete_all );
	}

	remove_filter( 'query', 'bp_xprofile_filter_meta_query' );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get a piece of xprofile metadata.
 *
 * Note that the default value of $single is true, unlike in the case of the
 * underlying get_metadata() function. This is for backward compatibility.
 *
 * @since BuddyPress 1.5.0
 *
 * @param int    $object_id   ID of the object the metadata belongs to.
 * @param string $object_type Type of object. 'group', 'field', or 'data'.
 * @param string $meta_key    Key of the metadata being fetched. If omitted, all
 *                            metadata for the object will be retrieved.
 * @param bool   $single      Optional. If true, return only the first value of the
 *                            specified meta_key. This parameter has no effect if meta_key is not
 *                            specified. Default: true.
 * @return mixed Meta value if found. False on failure.
 */
function bp_xprofile_get_meta( $object_id, $object_type, $meta_key = '', $single = true ) {
	// Sanitize object type.
	if ( ! in_array( $object_type, array( 'group', 'field', 'data' ) ) ) {
		return false;
	}

	add_filter( 'query', 'bp_filter_metaid_column_name' );
	add_filter( 'query', 'bp_xprofile_filter_meta_query' );
	$retval = get_metadata( 'xprofile_' . $object_type, $object_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );
	remove_filter( 'query', 'bp_xprofile_filter_meta_query' );

	return $retval;
}

/**
 * Update a piece of xprofile metadata.
 *
 * @since BuddyPress 1.5.0
 *
 * @param int    $object_id   ID of the object the metadata belongs to.
 * @param string $object_type Type of object. 'group', 'field', or 'data'.
 * @param string $meta_key    Key of the metadata being updated.
 * @param string $meta_value  Value of the metadata being updated.
 * @param mixed  $prev_value  Optional. If specified, only update existing
 *                            metadata entries with the specified value.
 *                            Otherwise update all entries.
 * @return bool|int Returns false on failure. On successful update of existing
 *                  metadata, returns true. On successful creation of new metadata,
 *                  returns the integer ID of the new metadata row.
 */
function bp_xprofile_update_meta( $object_id, $object_type, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	add_filter( 'query', 'bp_xprofile_filter_meta_query' );
	$retval = update_metadata( 'xprofile_' . $object_type, $object_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_xprofile_filter_meta_query' );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of xprofile metadata.
 *
 * @since BuddyPress 2.0.0
 *
 * @param int    $object_id   ID of the object the metadata belongs to.
 * @param string $object_type Type of object. 'group', 'field', or 'data'.
 * @param string $meta_key    Metadata key.
 * @param mixed  $meta_value  Metadata value.
 * @param bool   $unique      Optional. Whether to enforce a single metadata value
 *                            for the given key. If true, and the object already
 *                            has a value for the key, no change will be made.
 *                            Default false.
 * @return int|bool The meta ID on successful update, false on failure.
 */
function bp_xprofile_add_meta( $object_id, $object_type, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	add_filter( 'query', 'bp_xprofile_filter_meta_query' );
	$retval = add_metadata( 'xprofile_' . $object_type, $object_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );
	remove_filter( 'query', 'bp_xprofile_filter_meta_query' );

	return $retval;
}

/**
 * Updates the fieldgroup metadata.
 *
 * @since BuddyPress 1.5.0
 *
 * @param int    $field_group_id Group ID for the group field belongs to.
 * @param string $meta_key       Meta key to update.
 * @param string $meta_value     Meta value to update to.
 * @return bool|int
 */
function bp_xprofile_update_fieldgroup_meta( $field_group_id, $meta_key, $meta_value ) {
	return bp_xprofile_update_meta( $field_group_id, 'group', $meta_key, $meta_value );
}

/**
 * Updates the field metadata.
 *
 * @since BuddyPress 1.5.0
 *
 * @param int    $field_id   Field ID to update.
 * @param string $meta_key   Meta key to update.
 * @param string $meta_value Meta value to update to.
 * @return bool|int
 */
function bp_xprofile_update_field_meta( $field_id, $meta_key, $meta_value ) {
	return bp_xprofile_update_meta( $field_id, 'field', $meta_key, $meta_value );
}

/**
 * Updates the fielddata metadata.
 *
 * @since BuddyPress 1.5.0
 *
 * @param int    $field_data_id Field ID to update.
 * @param string $meta_key      Meta key to update.
 * @param string $meta_value    Meta value to update to.
 * @return bool|int
 */
function bp_xprofile_update_fielddata_meta( $field_data_id, $meta_key, $meta_value ) {
	return bp_xprofile_update_meta( $field_data_id, 'data', $meta_key, $meta_value );
}

/**
 * Return the field ID for the Full Name xprofile field.
 *
 * @since BuddyPress 2.0.0
 *
 * @return int Field ID.
 */
function bp_xprofile_fullname_field_id() {
	$id = wp_cache_get( 'fullname_field_id', 'bp_xprofile' );

	if ( false === $id ) {
		global $wpdb;

		$bp = buddypress();

		if ( isset( $bp->profile->table_name_fields ) ) {
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_fields} WHERE name = %s", addslashes( bp_xprofile_fullname_field_name() ) ) );
		} else {
			$table = bp_core_get_table_prefix() . 'bp_xprofile_fields';
			$id    = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE name = %s", addslashes( bp_xprofile_fullname_field_name() ) ) );
		}

		wp_cache_set( 'fullname_field_id', $id, 'bp_xprofile' );
	}

	return absint( $id );
}

/**
 * Get the group id of the base name field
 *
 * @since BuddyBoss 1.0.0
 */
function bp_xprofile_base_group_id( $defalut = 1, $get_option = true ) {
	if ( is_multisite() ) {
		$field_id = get_site_option( 'bp-xprofile-base-group-id' );
	}

	if ( empty( $field_id ) && $get_option ) {
		$field_id = bp_get_option( 'bp-xprofile-base-group-id', $defalut );
	}

	return (int) apply_filters( 'bp_xprofile_base_group_id', $field_id );
}

/**
 * Get the field id of the first name field
 *
 * @since BuddyBoss 1.0.0
 */
function bp_xprofile_firstname_field_id( $defalut = 1, $get_option = true ) {
	$field_id = 0;

	if ( is_multisite() ) {
		$field_id = get_site_option( 'bp-xprofile-firstname-field-id' );
	}

	if ( empty( $field_id ) && $get_option ) {
		$field_id = bp_get_option( 'bp-xprofile-firstname-field-id', $defalut );
	}

	return (int) apply_filters( 'bp_xprofile_firstname_field_id', $field_id );
}

/**
 * Get the field id of the last name field
 *
 * @since BuddyBoss 1.0.0
 */
function bp_xprofile_lastname_field_id( $defalut = 0, $get_option = true ) {
	$field_id = 0;

	if ( is_multisite() ) {
		$field_id = get_site_option( 'bp-xprofile-lastname-field-id' );
	}

	if ( empty( $field_id ) && $get_option ) {
		$field_id = bp_get_option( 'bp-xprofile-lastname-field-id', $defalut );
	}

	return (int) apply_filters( 'bp_xprofile_lastname_field_id', $field_id );
}

/**
 * Get the field id of the nick name field, fallback to default fullname field
 *
 * @since BuddyBoss 1.0.0
 */
function bp_xprofile_nickname_field_id( $no_fallback = false, $get_option = true ) {
	$field_id = 0;

	if ( is_multisite() ) {
		$field_id = get_site_option( 'bp-xprofile-nickname-field-id', $no_fallback ? 0 : 0 );
	}

	if ( empty( $field_id ) && $get_option ) {
		$field_id = bp_get_option( 'bp-xprofile-nickname-field-id', $no_fallback ? 0 : 0 );
	}

	// Set nickname field id to 0(zero) if first name and nickname both are same.
	$first_name_id = bp_xprofile_firstname_field_id();
	if ( $first_name_id === (int) $field_id ) {
		$field_id = 0;
	}

	return (int) apply_filters( 'bp_xprofile_nickname_field_id', $field_id );
}

/**
 * Return the field name for the Full Name xprofile field.
 *
 * @since BuddyPress 1.5.0
 *
 * @return string The field name.
 */
function bp_xprofile_fullname_field_name() {
	$field_name = BP_XPROFILE_FULLNAME_FIELD_NAME;

	/**
	 * Get the nickname field if is set
	 *
	 * @since BuddyBoss 1.0.0
	 */
	if ( $nickname_field_id = bp_xprofile_nickname_field_id( true ) ) {
		$field_name = xprofile_get_field( $nickname_field_id )->name;
	}

	/**
	 * Filters the field name for the Full Name xprofile field.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $value BP_XPROFILE_FULLNAME_FIELD_NAME Full name field constant.
	 */
	return apply_filters( 'bp_xprofile_fullname_field_name', $field_name );
}

/**
 * Is rich text enabled for this profile field?
 *
 * By default, rich text is enabled for textarea fields and disabled for all other field types.
 *
 * @since BuddyPress 2.4.0
 *
 * @param int|null $field_id Optional. Default current field ID.
 * @return bool
 */
function bp_xprofile_is_richtext_enabled_for_field( $field_id = null ) {
	if ( ! $field_id ) {
		$field_id = bp_get_the_profile_field_id();
	}

	$field = xprofile_get_field( $field_id );

	$enabled = false;
	if ( $field instanceof BP_XProfile_Field ) {
		$enabled = (bool) $field->type_obj->supports_richtext;
	}

	/**
	 * Filters whether richtext is enabled for the given field.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param bool $enabled  True if richtext is enabled for the field, otherwise false.
	 * @param int  $field_id ID of the field.
	 */
	return apply_filters( 'bp_xprofile_is_richtext_enabled_for_field', $enabled, $field_id );
}

/**
 * Get visibility levels out of the $bp global.
 *
 * @since BuddyPress 1.6.0
 *
 * @return array
 */
function bp_xprofile_get_visibility_levels() {

	/**
	 * Filters the visibility levels out of the $bp global.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param array $visibility_levels Array of visibility levels.
	 */
	return apply_filters( 'bp_xprofile_get_visibility_levels', buddypress()->profile->visibility_levels );
}

/**
 * Get the ids of fields that are hidden for this displayed/loggedin user pair.
 *
 * This is the function primarily responsible for profile field visibility. It works by determining
 * the relationship between the displayed_user (ie the profile owner) and the current_user (ie the
 * profile viewer). Then, based on that relationship, we query for the set of fields that should
 * be excluded from the profile loop.
 *
 * @since BuddyPress 1.6.0
 *
 * @see BP_XProfile_Group::get()
 *   or if you have added your own custom levels.
 *
 * @param int $displayed_user_id The id of the user the profile fields belong to.
 * @param int $current_user_id   The id of the user viewing the profile.
 * @return array An array of field ids that should be excluded from the profile query
 */
function bp_xprofile_get_hidden_fields_for_user( $displayed_user_id = 0, $current_user_id = 0 ) {
	if ( ! $displayed_user_id ) {
		$displayed_user_id = bp_displayed_user_id();
	}

	if ( ! $displayed_user_id ) {
		return array();
	}

	if ( ! $current_user_id ) {
		$current_user_id = bp_loggedin_user_id();
	}

	// @todo - This is where you'd swap out for current_user_can() checks
	$hidden_levels = bp_xprofile_get_hidden_field_types_for_user( $displayed_user_id, $current_user_id );
	$hidden_fields = bp_xprofile_get_fields_by_visibility_levels( $displayed_user_id, $hidden_levels );

	/**
	 * Filters the ids of fields that are hidden for this displayed/loggedin user pair.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param array $hidden_fields     Array of hidden fields for the displayed/logged in user.
	 * @param int   $displayed_user_id ID of the displayed user.
	 * @param int   $current_user_id   ID of the current user.
	 */
	return apply_filters( 'bp_xprofile_get_hidden_fields_for_user', $hidden_fields, $displayed_user_id, $current_user_id );
}

/**
 * Get the visibility levels that should be hidden for this user pair.
 *
 * Field visibility is determined based on the relationship between the
 * logged-in user, the displayed user, and the visibility setting for the
 * current field. (See bp_xprofile_get_hidden_fields_for_user().) This
 * utility function speeds up this matching by fetching the visibility levels
 * that should be hidden for the current user pair.
 *
 * @since BuddyPress 1.8.2
 *
 * @see bp_xprofile_get_hidden_fields_for_user()
 *
 * @param int $displayed_user_id The id of the user the profile fields belong to.
 * @param int $current_user_id   The id of the user viewing the profile.
 * @return array An array of visibility levels hidden to the current user.
 */
function bp_xprofile_get_hidden_field_types_for_user( $displayed_user_id = 0, $current_user_id = 0 ) {

	// Current user is logged in.
	if ( ! empty( $current_user_id ) ) {

		// Nothing's private when viewing your own profile, or when the
		// current user is an admin.
		if ( $displayed_user_id == $current_user_id || bp_current_user_can( 'bp_moderate' ) ) {
			$hidden_levels = array();

			// If the current user and displayed user are friends, show all.
		} elseif ( bp_is_active( 'friends' ) && friends_check_friendship( (int) $displayed_user_id, (int) $current_user_id ) ) {
			$hidden_levels = array( 'adminsonly' );

			// Current user is logged in but not friends, so exclude friends-only.
		} else {
			$hidden_levels = array( 'friends', 'adminsonly' );
		}

		// Current user is not logged in, so exclude friends-only, loggedin, and adminsonly.
	} else {
		$hidden_levels = array( 'friends', 'loggedin', 'adminsonly' );
	}

	/**
	 * Filters the visibility levels that should be hidden for this user pair.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param array $hidden_fields     Array of hidden fields for the displayed/logged in user.
	 * @param int   $displayed_user_id ID of the displayed user.
	 * @param int   $current_user_id   ID of the current user.
	 */
	return apply_filters( 'bp_xprofile_get_hidden_field_types_for_user', $hidden_levels, $displayed_user_id, $current_user_id );
}

/**
 * Fetch an array of the xprofile fields that a given user has marked with certain visibility levels.
 *
 * @since BuddyPress 1.6.0
 *
 * @see bp_xprofile_get_hidden_fields_for_user()
 *
 * @param int   $user_id The id of the profile owner.
 * @param array $levels  An array of visibility levels ('public', 'friends', 'loggedin', 'adminsonly' etc) to be
 *                       checked against.
 * @return array $field_ids The fields that match the requested visibility levels for the given user.
 */
function bp_xprofile_get_fields_by_visibility_levels( $user_id, $levels = array() ) {
	if ( ! is_array( $levels ) ) {
		$levels = (array) $levels;
	}

	$user_visibility_levels = bp_get_user_meta( $user_id, 'bp_xprofile_visibility_levels', true );
	if ( empty( $user_visibility_levels ) && ! is_array( $user_visibility_levels ) ){
		$user_visibility_levels = array();
	}

	// Parse the user-provided visibility levels with the default levels, which may take
	// precedence.
	$default_visibility_levels = BP_XProfile_Group::fetch_default_visibility_levels();

	foreach ( (array) $default_visibility_levels as $d_field_id => $defaults ) {
		// If the admin has forbidden custom visibility levels for this field, replace
		// the user-provided setting with the default specified by the admin.
		if ( isset( $defaults['allow_custom'] ) && isset( $defaults['default'] ) && 'disabled' == $defaults['allow_custom'] ) {
			$user_visibility_levels[ $d_field_id ] = $defaults['default'];
		}
	}

	$field_ids = array();
	foreach ( (array) $user_visibility_levels as $field_id => $field_visibility ) {
		if ( in_array( $field_visibility, $levels ) ) {
			$field_ids[] = $field_id;
		}
	}

	// Never allow the Nickname field to be excluded.
	$nickname_field_id = bp_xprofile_nickname_field_id();
	if ( in_array( $nickname_field_id, $field_ids ) ) {
		$key = array_search( 1, $field_ids );
		unset( $field_ids[ $key ] );
	}

	return $field_ids;
}

/**
 * Formats datebox field values passed through a POST request.
 *
 * @since BuddyPress 2.8.0
 *
 * @param int $field_id The id of the current field being looped through.
 * @return void This function only changes the global $_POST that should contain
 *              the datebox data.
 */
function bp_xprofile_maybe_format_datebox_post_data( $field_id ) {
	if ( ! isset( $_POST[ 'field_' . $field_id ] ) ) {
		if ( ! empty( $_POST[ 'field_' . $field_id . '_day' ] ) && ! empty( $_POST[ 'field_' . $field_id . '_month' ] ) && ! empty( $_POST[ 'field_' . $field_id . '_year' ] ) ) {
			// Concatenate the values.
			$date_value = $_POST[ 'field_' . $field_id . '_day' ] . ' ' . $_POST[ 'field_' . $field_id . '_month' ] . ' ' . $_POST[ 'field_' . $field_id . '_year' ];

			$timestamp = strtotime( $date_value );

			// Check that the concatenated value can be turned into a timestamp.
			if ( false !== $timestamp ) {

				// Add the timestamp to the global $_POST that should contain the datebox data.
				$_POST[ 'field_' . $field_id ] = date( 'Y-m-d H:i:s', $timestamp );
			}
		}
	}
}

/**
 * Determine a user's "mentionname", the name used for that user in @-mentions.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int|string $user_id ID of the user to get @-mention name for.
 * @return string $mentionname User name appropriate for @-mentions.
 */
function bp_activity_get_user_mentionname( $user_id ) {
	$mentionname = '';

	$userdata = bp_core_get_core_userdata( $user_id );

	if ( $userdata ) {
		if ( bp_is_username_compatibility_mode() ) {
			$mentionname = str_replace( ' ', '-', $userdata->user_login );
		} else {
			$mentionname = get_user_meta( $userdata->ID, 'nickname', true );
		}
	}

	return $mentionname;
}

/**
 * Options for at mention js script
 *
 * @since BuddyBoss 1.0.0
 */
function bp_at_mention_default_options() {
	return apply_filters(
		'bp_at_mention_js_options',
		array(
			'selectors'     => array( '.bp-suggestions', '#comments form textarea', '.wp-editor-area', '.bbp-the-content' ),
			'insert_tpl'    => '@${ID}',
			'display_tpl'   => '<li data-value="@${ID}"><img src="${image}" /><span class="username">@${ID}</span><small>${name}</small></li>',
			'extra_options' => array(),
		)
	);
}

/**
 * Social Networks xprofile field provider.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array
 */
function bp_xprofile_social_network_provider() {

	$options = array();

	$options[] = (object) array(
		'id'                => 1,
		'is_default_option' => false,
		'name'              => __( 'Facebook', 'buddyboss' ),
		'value'             => 'facebook',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="#333" d="M16 0c-8.8 0-16 7.2-16 16s7.2 16 16 16c8.8 0 16-7.2 16-16s-7.2-16-16-16v0zM20.192 10.688h-1.504c-1.184 0-1.376 0.608-1.376 1.408v1.792h2.784l-0.384 2.816h-2.4v7.296h-2.912v-7.296h-2.496v-2.816h2.496v-2.080c-0.096-2.496 1.408-3.808 3.616-3.808 0.992 0 1.888 0.096 2.176 0.096v2.592z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 2,
		'is_default_option' => false,
		'name'              => __( 'Flickr', 'buddyboss' ),
		'value'             => 'flickr',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="#333" d="M16 0c-8.837 0-16 7.212-16 16.109s7.163 16.109 16 16.109 16-7.212 16-16.109-7.163-16.109-16-16.109zM9 21c-2.761 0-5-2.239-5-5s2.239-5 5-5 5 2.239 5 5c0 2.761-2.239 5-5 5zM23 21c-2.761 0-5-2.239-5-5s2.239-5 5-5 5 2.239 5 5c0 2.761-2.239 5-5 5z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 4,
		'is_default_option' => false,
		'name'              => __( 'Instagram', 'buddyboss' ),
		'value'             => 'instagram',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="#333" d="M16 19.104c-1.696 0-3.104-1.408-3.104-3.104 0-1.728 1.408-3.104 3.104-3.104 1.728 0 3.104 1.376 3.104 3.104 0 1.696-1.376 3.104-3.104 3.104zM19.616 12.896c-0.32 0-0.512-0.192-0.416-0.384v-2.208c0-0.192 0.192-0.416 0.416-0.416h2.176c0.224 0 0.416 0.224 0.416 0.416v2.208c0 0.192-0.192 0.384-0.416 0.384h-2.176zM16 0c-8.8 0-16 7.2-16 16s7.2 16 16 16c8.8 0 16-7.2 16-16s-7.2-16-16-16v0zM24 22.112c0 0.992-0.896 1.888-1.888 1.888h-12.224c-0.992 0-1.888-0.8-1.888-1.888v-12.224c0-1.088 0.896-1.888 1.888-1.888h12.224c0.992 0 1.888 0.8 1.888 1.888v12.224zM20.896 16c0 2.688-2.208 4.896-4.896 4.896s-4.896-2.208-4.896-4.896c0-0.416 0.096-0.896 0.192-1.312h-1.504v7.008c0 0.192 0.224 0.416 0.416 0.416h11.488c0.192 0 0.416-0.224 0.416-0.416v-7.008h-1.504c0.192 0.416 0.288 0.896 0.288 1.312z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 5,
		'is_default_option' => false,
		'name'              => __( 'LinkedIn', 'buddyboss' ),
		'value'             => 'linkedIn',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#333" d="M10 0.4c-5.302 0-9.6 4.298-9.6 9.6s4.298 9.6 9.6 9.6 9.6-4.298 9.6-9.6-4.298-9.6-9.6-9.6zM7.65 13.979h-1.944v-6.256h1.944v6.256zM6.666 6.955c-0.614 0-1.011-0.435-1.011-0.973 0-0.549 0.409-0.971 1.036-0.971s1.011 0.422 1.023 0.971c0 0.538-0.396 0.973-1.048 0.973zM14.75 13.979h-1.944v-3.467c0-0.807-0.282-1.355-0.985-1.355-0.537 0-0.856 0.371-0.997 0.728-0.052 0.127-0.065 0.307-0.065 0.486v3.607h-1.945v-4.26c0-0.781-0.025-1.434-0.051-1.996h1.689l0.089 0.869h0.039c0.256-0.408 0.883-1.010 1.932-1.010 1.279 0 2.238 0.857 2.238 2.699v3.699z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 6,
		'is_default_option' => false,
		'name'              => __( 'Medium', 'buddyboss' ),
		'value'             => 'medium',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28"><path fill="#333" d="M9.328 6.578v18.328c0 0.484-0.234 0.938-0.766 0.938-0.187 0-0.359-0.047-0.516-0.125l-7.266-3.641c-0.438-0.219-0.781-0.781-0.781-1.25v-17.813c0-0.391 0.187-0.75 0.609-0.75 0.25 0 0.469 0.125 0.688 0.234l7.984 4c0.016 0.016 0.047 0.063 0.047 0.078zM10.328 8.156l8.344 13.531-8.344-4.156v-9.375zM28 8.437v16.469c0 0.516-0.297 0.875-0.812 0.875-0.266 0-0.516-0.078-0.734-0.203l-6.891-3.437zM27.953 6.563c0 0.063-8.078 13.172-8.703 14.172l-6.094-9.906 5.063-8.234c0.172-0.281 0.484-0.438 0.812-0.438 0.141 0 0.281 0.031 0.406 0.094l8.453 4.219c0.031 0.016 0.063 0.047 0.063 0.094z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 7,
		'is_default_option' => false,
		'name'              => __( 'Meetup', 'buddyboss' ),
		'value'             => 'meetup',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="#333" d="M31.971 26.984c-0.405-2.575-5.165-0.592-5.461-3.412-0.417-3.997 5.533-12.612 5.063-15.963-0.417-3.007-2.455-3.64-4.22-3.675-1.712-0.027-2.164 0.243-2.744 0.58-0.337 0.195-0.816 0.58-1.483-0.055-0.445-0.424-0.743-0.715-1.207-1.092-0.243-0.189-0.621-0.432-1.26-0.527-0.635-0.095-1.464 0-1.989 0.223-0.527 0.229-0.936 0.621-1.368 0.999-0.431 0.377-1.529 1.597-2.548 1.145-0.447-0.193-1.944-0.941-3.029-1.407-2.084-0.903-5.096 0.56-6.181 2.488-1.617 2.865-4.8 14.137-5.285 15.62-1.079 3.336 1.376 6.053 4.679 5.899 1.403-0.068 2.333-0.573 3.216-2.184 0.512-0.924 5.305-13.449 5.664-14.057 0.263-0.431 1.125-1.004 1.853-0.633 0.735 0.377 0.883 1.159 0.775 1.895-0.181 1.193-3.559 8.839-3.695 9.7-0.216 1.471 0.479 2.285 2.009 2.365 1.045 0.055 2.089-0.316 2.912-1.88 0.465-0.869 5.799-11.555 6.269-12.269 0.52-0.781 0.937-1.039 1.471-1.011 0.412 0.020 1.065 0.128 0.904 1.355-0.163 1.207-4.457 9.040-4.901 10.961-0.608 2.569 0.803 5.165 3.121 6.304 1.483 0.728 7.96 1.968 7.436-1.369z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 8,
		'is_default_option' => false,
		'name'              => __( 'Pinterest', 'buddyboss' ),
		'value'             => 'pinterest',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 28"><path fill="#333" d="M24 14c0 6.625-5.375 12-12 12-1.188 0-2.312-0.172-3.406-0.5 0.453-0.719 0.969-1.641 1.219-2.562 0 0 0.141-0.531 0.844-3.297 0.406 0.797 1.625 1.5 2.922 1.5 3.859 0 6.484-3.516 6.484-8.234 0-3.547-3.016-6.875-7.609-6.875-5.688 0-8.563 4.094-8.563 7.5 0 2.063 0.781 3.906 2.453 4.594 0.266 0.109 0.516 0 0.594-0.313 0.063-0.203 0.187-0.734 0.25-0.953 0.078-0.313 0.047-0.406-0.172-0.672-0.484-0.578-0.797-1.313-0.797-2.359 0-3.031 2.266-5.75 5.906-5.75 3.219 0 5 1.969 5 4.609 0 3.453-1.531 6.375-3.813 6.375-1.25 0-2.188-1.031-1.891-2.312 0.359-1.516 1.062-3.156 1.062-4.25 0-0.984-0.531-1.813-1.625-1.813-1.281 0-2.312 1.328-2.312 3.109 0 0 0 1.141 0.391 1.906-1.313 5.563-1.547 6.531-1.547 6.531-0.219 0.906-0.234 1.922-0.203 2.766-4.234-1.859-7.187-6.078-7.187-11 0-6.625 5.375-12 12-12s12 5.375 12 12z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 9,
		'is_default_option' => false,
		'name'              => __( 'Quora', 'buddyboss' ),
		'value'             => 'quora',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28"><path fill="#333" d="M19.609 12.297c0-6.516-2.031-9.859-6.797-9.859-4.688 0-6.719 3.344-6.719 9.859 0 6.484 2.031 9.797 6.719 9.797 0.75 0 1.422-0.078 2.047-0.266v0c-0.969-1.906-2.109-3.828-4.328-3.828-0.422 0-0.844 0.063-1.234 0.25l-0.766-1.516c0.922-0.797 2.406-1.422 4.312-1.422 2.984 0 4.5 1.437 5.719 3.266 0.703-1.563 1.047-3.672 1.047-6.281zM25.703 22.172h1.828c0.109 1.125-0.453 5.828-5.563 5.828-3.094 0-4.719-1.797-5.953-3.891v0c-1.016 0.281-2.109 0.422-3.203 0.422-6.25 0-12.359-4.984-12.359-12.234 0-7.313 6.125-12.297 12.359-12.297 6.359 0 12.406 4.953 12.406 12.297 0 4.094-1.906 7.422-4.672 9.562 0.891 1.344 1.813 2.234 3.094 2.234 1.406 0 1.969-1.078 2.063-1.922z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 10,
		'is_default_option' => false,
		'name'              => __( 'Reddit', 'buddyboss' ),
		'value'             => 'reddit',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28"><path fill="#333" d="M17.109 18.234c0.141 0.141 0.141 0.359 0 0.484-0.891 0.891-2.609 0.969-3.109 0.969s-2.219-0.078-3.109-0.969c-0.141-0.125-0.141-0.344 0-0.484 0.125-0.125 0.344-0.125 0.469 0 0.562 0.578 1.781 0.766 2.641 0.766s2.063-0.187 2.641-0.766c0.125-0.125 0.344-0.125 0.469 0zM12.313 15.406c0 0.766-0.625 1.391-1.391 1.391-0.781 0-1.406-0.625-1.406-1.391 0-0.781 0.625-1.406 1.406-1.406 0.766 0 1.391 0.625 1.391 1.406zM18.484 15.406c0 0.766-0.625 1.391-1.406 1.391-0.766 0-1.391-0.625-1.391-1.391 0-0.781 0.625-1.406 1.391-1.406 0.781 0 1.406 0.625 1.406 1.406zM22.406 13.531c0-1.031-0.844-1.859-1.875-1.859-0.531 0-1 0.219-1.344 0.562-1.266-0.875-2.969-1.437-4.859-1.5l0.984-4.422 3.125 0.703c0 0.766 0.625 1.391 1.391 1.391 0.781 0 1.406-0.641 1.406-1.406s-0.625-1.406-1.406-1.406c-0.547 0-1.016 0.328-1.25 0.781l-3.453-0.766c-0.172-0.047-0.344 0.078-0.391 0.25l-1.078 4.875c-1.875 0.078-3.563 0.641-4.828 1.516-0.344-0.359-0.828-0.578-1.359-0.578-1.031 0-1.875 0.828-1.875 1.859 0 0.75 0.438 1.375 1.062 1.687-0.063 0.281-0.094 0.578-0.094 0.875 0 2.969 3.344 5.375 7.453 5.375 4.125 0 7.469-2.406 7.469-5.375 0-0.297-0.031-0.609-0.109-0.891 0.609-0.313 1.031-0.938 1.031-1.672zM28 14c0 7.734-6.266 14-14 14s-14-6.266-14-14 6.266-14 14-14 14 6.266 14 14z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 11,
		'is_default_option' => false,
		'name'              => __( 'Snapchat', 'buddyboss' ),
		'value'             => 'snapchat',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm5.126 16.475c-.057.077-.103.4-.178.655-.086.295-.356.262-.656.203-.437-.085-.827-.109-1.281-.034-.785.131-1.601 1.292-2.969 1.292-1.472 0-2.238-1.156-3.054-1.292-.832-.138-1.31.084-1.597.084-.221 0-.307-.135-.34-.247-.074-.251-.12-.581-.178-.66-.565-.087-1.84-.309-1.873-.878-.008-.148.096-.279.243-.303 1.872-.308 3.063-2.419 2.869-2.877-.138-.325-.735-.442-.986-.541-.648-.256-.739-.55-.7-.752.053-.28.395-.468.68-.468.275 0 .76.367 1.138.158-.055-.982-.194-2.387.156-3.171.667-1.496 2.129-2.236 3.592-2.236 1.473 0 2.946.75 3.608 2.235.349.783.212 2.181.156 3.172.357.197.799-.167 1.107-.167.302 0 .712.204.719.545.005.267-.233.497-.708.684-.255.101-.848.217-.986.541-.198.468 1.03 2.573 2.869 2.876.146.024.251.154.243.303-.033.569-1.314.791-1.874.878z"/></svg>',
	);
	$options[] = (object) array(
		'id'                => 12,
		'is_default_option' => false,
		'name'              => __( 'Telegram', 'buddyboss' ),
		'value'             => 'telegram',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2000 2000"><g fill="#333" transform="matrix(0.10801485,0,0,-0.10804828,-68.2169,2112.9105)"><path d="M 9680,19553 C 8348,19524 7000,19183 5730,18554 4664,18027 3741,17331 3029,16520 2267,15650 1708,14741 1295,13700 1016,12998 825,12287 721,11570 609,10796 602,9963 701,9170 895,7612 1551,5987 2501,4710 2989,4053 3482,3554 4165,3023 5450,2027 7020,1358 8610,1130 c 484,-69 716,-85 1280,-85 477,0 563,4 930,41 1173,117 2426,511 3535,1110 837,451 1651,1068 2209,1673 999,1083 1678,2232 2129,3606 591,1800 606,3697 43,5510 -173,559 -328,930 -620,1490 -539,1035 -1238,1989 -1882,2572 -1057,956 -2188,1616 -3514,2051 -985,324 -2018,478 -3040,455 z m 4495,-5612 c 28,-14 81,-55 118,-92 115,-114 153,-230 144,-436 -4,-108 -68,-414 -771,-3723 -421,-1983 -778,-3651 -792,-3707 -106,-428 -321,-562 -684,-424 -126,48 -12,-34 -2045,1475 -214,159 -394,289 -401,290 -6,1 -258,-237 -559,-529 -301,-291 -582,-556 -624,-587 -128,-97 -288,-168 -378,-168 -18,0 -33,5 -33,10 0,17 180,2323 183,2342 1,9 457,432 1012,940 556,508 1529,1397 2163,1976 633,579 1159,1066 1167,1082 36,70 6,103 -98,108 -67,3 -77,1 -147,-35 -41,-22 -739,-458 -1550,-969 C 8047,9709 7059,9088 7042,9083 c -10,-2 -535,154 -1168,347 -632,193 -1186,362 -1230,376 -104,31 -195,90 -230,151 -71,120 -1,279 172,393 100,66 167,93 989,410 402,155 984,380 1295,500 311,120 1011,390 1555,600 545,210 1661,641 2480,957 820,316 1744,673 2055,793 311,120 619,239 685,265 210,81 267,95 380,92 82,-2 109,-6 150,-26 z" /></g></svg>',
	);
	$options[] = (object) array(
		'id'                => 13,
		'is_default_option' => false,
		'name'              => __( 'Tumblr', 'buddyboss' ),
		'value'             => 'tumblr',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#333" d="M10 0.4c-5.302 0-9.6 4.298-9.6 9.6s4.298 9.6 9.6 9.6 9.6-4.298 9.6-9.6-4.298-9.6-9.6-9.6zM12.577 14.141c-0.393 0.188-0.748 0.318-1.066 0.395-0.318 0.074-0.662 0.113-1.031 0.113-0.42 0-0.791-0.055-1.114-0.162s-0.598-0.26-0.826-0.459c-0.228-0.197-0.386-0.41-0.474-0.633-0.088-0.225-0.132-0.549-0.132-0.973v-3.262h-1.016v-1.314c0.359-0.119 0.67-0.289 0.927-0.512 0.257-0.221 0.464-0.486 0.619-0.797s0.263-0.707 0.322-1.185h1.307v2.35h2.18v1.458h-2.18v2.385c0 0.539 0.028 0.885 0.085 1.037 0.056 0.154 0.161 0.275 0.315 0.367 0.204 0.123 0.437 0.185 0.697 0.185 0.466 0 0.928-0.154 1.388-0.461v1.468z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 14,
		'is_default_option' => false,
		'name'              => __( 'Twitch', 'buddyboss' ),
		'value'             => 'twitch',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 333334 333334" fill-rule="evenodd" clip-rule="evenodd"><path fill="#333" d="M166667 0c92047 0 166667 74620 166667 166667s-74620 166667-166667 166667S0 258714 0 166667 74620 0 166667 0zm-55369 98779v105771h35251v20048c545-519 851-797 1144-1090 5944-5930 11904-11845 17813-17811 843-851 1685-1196 2882-1192 12319 40 24639 48 36958-24 905-5 2030-472 2674-1108 7680-7575 15274-15237 22935-22831 859-851 1170-1700 1169-2885-30-25681-22-51361-22-77043v-1836H111299zm95369 75234h-14630v-44767h14630v44767zm-40077-44764v44706h-14896v-44706h14896zm-40007 120108v-19807H86463c-40-830-98-1472-98-2115-4-37267-4-74534 18-111802 1-1078 192-2200 529-3224 2956-8996 5991-17968 8931-26969 381-1166 861-1584 2105-1596h60c49098 38 98194 33 147291 33 481-1 963 0 1647 0v2079c0 32119-8 64237 29 96356v63c-11 1306-409 2217-1339 3143-14244 14187-28460 28404-42648 42649-941 945-1864 1340-3205 1331-8642-61-17285 9-25927-67-1656-15-2839 418-4017 1622-5701 5827-11486 11572-17287 17300-551 545-1418 1083-2144 1090-3620 35-7240 47-10860 49h-2173c-3379-2-6758-8-10137-13-170 0-341-61-654-121z"/></svg>',
	);
	$options[] = (object) array(
		'id'                => 15,
		'is_default_option' => false,
		'name'              => __( 'Twitter', 'buddyboss' ),
		'value'             => 'twitter',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="#333" d="M16 0c-8.8 0-16 7.2-16 16s7.2 16 16 16c8.8 0 16-7.2 16-16s-7.2-16-16-16v0zM22.4 12.704v0.384c0 4.32-3.296 9.312-9.312 9.312-1.888 0-3.584-0.512-4.992-1.504h0.8c1.504 0 3.008-0.512 4.096-1.408-1.376 0-2.592-0.992-3.104-2.304 0.224 0 0.416 0.128 0.608 0.128 0.32 0 0.608 0 0.896-0.128-1.504-0.288-2.592-1.6-2.592-3.2v0c0.416 0.224 0.896 0.416 1.504 0.416-0.896-0.608-1.504-1.6-1.504-2.688 0-0.608 0.192-1.216 0.416-1.728 1.6 2.016 4 3.328 6.784 3.424-0.096-0.224-0.096-0.512-0.096-0.704 0-1.792 1.504-3.296 3.296-3.296 0.896 0 1.792 0.384 2.4 0.992 0.704-0.096 1.504-0.416 2.112-0.8-0.224 0.8-0.8 1.408-1.408 1.792 0.704-0.096 1.312-0.288 1.888-0.48-0.576 0.8-1.184 1.376-1.792 1.792v0z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 16,
		'is_default_option' => false,
		'name'              => __( 'VK', 'buddyboss' ),
		'value'             => 'vk',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#333" d="M 10,0 C 4.4770833,0 0,4.4770833 0,10 0,15.522917 4.4770833,20 10,20 15.522917,20 20,15.522917 20,10 20,4.4770833 15.522917,0 10,0 Z m 3.845833,11.282292 c 0,0 0.884375,0.872916 1.102084,1.278125 0.0062,0.0083 0.0094,0.01667 0.01146,0.02083 0.08854,0.148958 0.109375,0.264583 0.06563,0.351042 -0.07292,0.14375 -0.322917,0.214583 -0.408333,0.220833 h -1.5625 c -0.108334,0 -0.335417,-0.02813 -0.610417,-0.217708 -0.211458,-0.147917 -0.419792,-0.390625 -0.622917,-0.627084 -0.303125,-0.352083 -0.565625,-0.65625 -0.830208,-0.65625 a 0.31770833,0.31770833 0 0 0 -0.09896,0.01563 c -0.2,0.06458 -0.45625,0.35 -0.45625,1.110417 0,0.2375 -0.1875,0.373958 -0.319792,0.373958 H 9.4 c -0.24375,0 -1.5135417,-0.08542 -2.6385417,-1.271875 C 5.384375,10.427083 4.1447917,7.5125 4.134375,7.4854167 4.05625,7.296875 4.2177083,7.1958333 4.39375,7.1958333 h 1.578125 c 0.2104167,0 0.2791667,0.128125 0.3270833,0.2416667 0.05625,0.1322917 0.2625,0.6583333 0.6010417,1.25 0.5489583,0.9645833 0.8854167,1.35625 1.1552083,1.35625 A 0.3125,0.3125 0 0 0 8.2,10.00625 C 8.5520833,9.8104167 8.4864583,8.5552083 8.4708333,8.2947917 c 0,-0.048958 -0.00104,-0.5614584 -0.18125,-0.8072917 C 8.1604167,7.309375 7.940625,7.2416667 7.8072917,7.2166667 A 0.57291667,0.57291667 0 0 1 8.0145833,7.040625 C 8.25625,6.9197917 8.6916667,6.9020833 9.1239583,6.9020833 h 0.240625 c 0.4687497,0.00625 0.5895837,0.036458 0.7593747,0.079167 0.34375,0.082292 0.351042,0.3041667 0.320834,1.0635417 -0.0094,0.215625 -0.01875,0.459375 -0.01875,0.746875 0,0.0625 -0.0031,0.1291666 -0.0031,0.2 -0.01042,0.3864583 -0.02292,0.825 0.25,1.0052083 a 0.225,0.225 0 0 0 0.11875,0.034375 c 0.09479,0 0.380208,0 1.153125,-1.3260414 A 10.122917,10.122917 0 0 0 12.564608,7.378125 c 0.01563,-0.027083 0.06146,-0.1104167 0.115625,-0.1427083 A 0.27708333,0.27708333 0 0 1 12.8094,7.2052083 h 1.855208 c 0.202084,0 0.340625,0.030208 0.366667,0.1083334 C 15.077105,7.4375 15.022975,7.815625 14.176067,8.9625 l -0.378125,0.4989583 c -0.767709,1.0062497 -0.767709,1.0572917 0.04792,1.8208337 z" /></svg>',
	);
	$options[] = (object) array(
		'id'                => 17,
		'is_default_option' => false,
		'name'              => __( 'WhatsApp', 'buddyboss' ),
		'value'             => 'whatsapp',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 960 960"><g fill="#333" transform="matrix(0.1202871,0,0,-0.12028135,-73.507161,1105.1649)"><path d="m 4370,9183 c -19,-1 -87,-8 -150,-14 C 3870,9137 3530,9058 3200,8935 2127,8532 1268,7673 865,6600 547,5752 527,4817 809,3953 1236,2644 2331,1636 3676,1315 c 519,-125 1088,-142 1622,-49 362,62 742,189 1092,363 763,380 1401,1017 1781,1781 283,569 420,1148 421,1780 1,836 -241,1607 -720,2297 -226,325 -550,659 -877,905 -696,521 -1550,804 -2410,796 -99,0 -196,-3 -215,-5 z m 650,-1443 c 356,-55 643,-159 930,-338 567,-354 938,-843 1106,-1457 72,-264 99,-493 91,-782 -5,-215 -24,-362 -67,-528 -75,-286 -203,-555 -384,-811 -387,-545 -896,-880 -1536,-1013 -197,-40 -310,-51 -529,-51 -380,-1 -709,74 -1060,241 -68,33 -131,59 -140,59 -9,0 -70,-18 -136,-39 -329,-107 -1223,-391 -1232,-391 -13,0 4,54 246,773 l 191,567 -51,92 c -145,261 -251,591 -290,903 -19,151 -16,474 5,629 78,569 328,1063 737,1461 314,305 644,498 1048,614 124,36 374,84 491,95 112,10 449,-3 580,-24 z" /><path d="m 4400,7329 c -514,-63 -1008,-330 -1338,-724 -258,-307 -425,-687 -478,-1090 -25,-187 -14,-465 25,-656 50,-242 130,-450 248,-645 36,-60 75,-125 86,-145 l 21,-36 -127,-374 c -69,-205 -124,-375 -122,-377 2,-2 175,50 384,117 208,66 387,121 397,121 9,0 51,-20 93,-45 174,-101 416,-200 591,-239 169,-39 276,-50 465,-50 322,1 596,60 872,189 570,266 991,772 1143,1373 46,184 62,313 62,512 0,201 -18,353 -61,516 -101,383 -293,709 -578,984 -304,294 -665,478 -1079,551 -136,24 -477,34 -604,18 z m -389,-860 c 10,-5 26,-24 38,-42 36,-58 221,-565 221,-605 0,-52 -43,-121 -139,-224 -101,-109 -102,-118 -28,-241 125,-210 308,-413 487,-539 111,-79 338,-198 378,-198 31,0 83,49 211,200 40,47 82,93 93,103 44,38 93,19 446,-169 108,-57 205,-114 215,-126 21,-26 22,-84 1,-185 -30,-145 -78,-221 -186,-295 -209,-141 -443,-175 -658,-95 -551,205 -714,298 -986,563 -280,274 -578,711 -666,979 -71,216 -53,449 49,635 45,80 193,231 243,248 39,13 254,6 281,-9 z" /></g></svg>',
	);
	$options[] = (object) array(
		'id'                => 18,
		'is_default_option' => false,
		'name'              => __( 'YouTube', 'buddyboss' ),
		'value'             => 'youTube',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="#333" d="M16 0c-8.8 0-16 7.2-16 16s7.2 16 16 16c8.8 0 16-7.2 16-16s-7.2-16-16-16v0zM24 16.608c0 1.28-0.192 2.592-0.192 2.592s-0.192 1.088-0.608 1.6c-0.608 0.608-1.312 0.608-1.6 0.704-2.208 0.192-5.6 0.192-5.6 0.192s-4.192 0-5.408-0.192c-0.384-0.096-1.184 0-1.792-0.704-0.512-0.512-0.608-1.6-0.608-1.6s-0.192-1.312-0.192-2.592v-1.216c0-1.28 0.192-2.592 0.192-2.592s0.224-1.088 0.608-1.6c0.608-0.608 1.312-0.608 1.6-0.704 2.208-0.192 5.6-0.192 5.6-0.192s3.392 0 5.6 0.192c0.288 0 0.992 0 1.6 0.704 0.512 0.512 0.608 1.6 0.608 1.6s0.192 1.312 0.192 2.592v1.216zM14.304 18.112l4.384-2.304-4.384-2.208v4.512z"></path></svg>',
	);

	$options[] = (object) array(
		'id'                => 19,
		'is_default_option' => false,
		'name'              => __( 'X', 'buddyboss' ),
		'value'             => 'x',
		'svg'               => '<svg viewBox="0 0 24 24" fill="none"><path d="M8.54996 6.78142H7.54716L15.462 17.2186H16.4548L8.54996 6.78142Z" fill="#333"/><path d="M12 24C18.6274 24 24 18.6274 24 12C24 5.37258 18.6274 0 12 0C5.37258 0 0 5.37258 0 12C0 18.6274 5.37258 24 12 24ZM5 5.5H9.15412L12.3916 9.77458L15.8503 5.51925H18.1382L13.4978 11.2352L19 18.5H14.8583L11.3528 13.8773L7.61027 18.4872H5.31001L10.2446 12.416L5 5.5Z" fill="#333"/></svg>',
	);

	$options[] = (object) array(
		'id'                => 20,
		'is_default_option' => false,
		'name'              => __( 'TikTok', 'buddyboss' ),
		'value'             => 'tiktok',
		'svg'               => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 24C18.6274 24 24 18.6274 24 12C24 5.37258 18.6274 0 12 0C5.37258 0 0 5.37258 0 12C0 18.6274 5.37258 24 12 24ZM14.0095 5.99995C14.0095 6.16452 14.0687 6.4765 14.233 6.88207C14.3919 7.27458 14.6292 7.70455 14.9344 8.0998C15.5563 8.90546 16.3669 9.45802 17.2849 9.45802C17.7268 9.45802 18.0849 9.81619 18.0849 10.258C18.0849 10.6998 17.7268 11.058 17.2849 11.058C15.8965 11.058 14.7908 10.3368 14.0095 9.48384V14.9032C14.0095 17.0573 12.259 18.8 10.1043 18.8C7.94963 18.8 6.19922 17.0573 6.19922 14.9032C6.19922 12.749 7.94963 11.0064 10.1043 11.0064C10.5462 11.0064 10.9043 11.3646 10.9043 11.8064C10.9043 12.2482 10.5462 12.6064 10.1043 12.6064C8.82923 12.6064 7.79922 13.6367 7.79922 14.9032C7.79922 16.1696 8.82923 17.2 10.1043 17.2C11.3795 17.2 12.4095 16.1696 12.4095 14.9032V5.99995C12.4095 5.55812 12.7676 5.19995 13.2095 5.19995C13.6513 5.19995 14.0095 5.55812 14.0095 5.99995Z" fill="#333"/></svg>',
	);

	return apply_filters( 'bp_xprofile_fields_social_networks_provider', $options );
}

/**
 * Add social networks button to the member header area.
 *
 * @return string
 * @since BuddyBoss 1.0.0
 */
function bp_get_user_social_networks_urls( $user_id = null ) {

	global $wpdb;
	global $bp;

	$social_networks_field = $wpdb->get_row( "SELECT a.id, a.name FROM {$bp->table_prefix}bp_xprofile_fields a WHERE parent_id = 0 AND type = 'socialnetworks' " );
	$social_networks_id    = $social_networks_field->id;
	$social_networks_text  = $social_networks_field->name;

	$is_enabled_header_social_networks  = bb_enabled_profile_header_layout_element( 'social-networks' ) && function_exists( 'bb_enabled_member_social_networks' ) && bb_enabled_member_social_networks();

	$html = '';

	$original_option_values = array();

	$user = ( $user_id !== null && $user_id > 0 ) ? $user_id : bp_displayed_user_id();

	if ( $social_networks_id > 0 ) {
		$providers = bp_xprofile_social_network_provider();

		$original_option_values = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $social_networks_id, $user ) );

		if ( isset( $original_option_values ) && ! empty( $original_option_values ) && is_array( $original_option_values ) ) {
			$i            = 0;
			$is_more_link = count( array_filter( $original_option_values ) ) > 3;
			foreach ( $original_option_values as $key => $original_option_value ) {
				if ( '' !== $original_option_value ) {
					$key = bp_social_network_search_key( $key, $providers );

					if ( $is_more_link && 2 === $i ) {
						$html .= '<span class="social-more-link social"><a target="_blank" data-balloon-pos="up" data-balloon="' . esc_html__( 'See all', 'buddyboss' ) . '" href="#social-networks-popup" class="show-action-popup"><i class="bb-icon-rf bb-icon-ellipsis-h"></i></a></span>';
						break;
					}
					$html .= '<span class="social ' . esc_attr( $providers[ $key ]->value ) . '"><a target="_blank" data-balloon-pos="up" data-balloon="' . esc_attr( $providers[ $key ]->name ) . '" href="' . esc_url( $original_option_value ) . '"><i class="bb-icon-rf bb-icon-brand-' . esc_attr( strtolower( $providers[ $key ]->value ) ) . '"></i></a></span>';
				}
				$i++;
			}
			if ( $is_more_link ) {
				$html .= '<div style="display: none" class="bb-action-popup" id="social-networks-popup">
							<div class="modal-mask bb-white bbm-model-wrap">
								<div class="action-popup-overlay"></div>
								<div class="modal-wrapper">
									<div class="modal-container">
										<header class="bb-model-header">
											<h4>
												<span class="target_name">' . esc_attr( $social_networks_text ) . '</span>
											</h4>
											<a class="bb-close-action-popup bb-model-close-button" href="#"><span class="bb-icon-l bb-icon-times"></span></a>
										</header>
										<div class="bb-action-popup-content">';
										foreach ( $original_option_values as $key => $original_option_value ) {
											if ( '' !== $original_option_value ) {
												$key   = bp_social_network_search_key( $key, $providers );
												$html .= '<span class="social ' . esc_attr( $providers[ $key ]->value ) . '"><a target="_blank" data-balloon-pos="up" data-balloon="' . esc_attr( $providers[ $key ]->name ) . '" href="' . esc_url( $original_option_value ) . '"><i class="bb-icon-rf bb-icon-brand-' . esc_attr( strtolower( $providers[ $key ]->value ) ) . '"></i></a></span>';
											}
										}
										$html .= '</div>
									</div>
								</div>
							</div>
						</div>';
			}
		}
	}

	if ( $html !== '' ) {
		$level = xprofile_get_field_visibility_level( $social_networks_id, bp_displayed_user_id() );

		if ( bp_displayed_user_id() === bp_loggedin_user_id() ) {
			$html = '<div class="social-networks-wrap">' . $html . '</div>';
		} elseif ( 'public' === $level ) {
			$html = '<div class="social-networks-wrap">' . $html . '</div>';
		} elseif ( 'loggedin' === $level && is_user_logged_in() ) {
			$html = '<div class="social-networks-wrap">' . $html . '</div>';
		} elseif ( 'friends' === $level && is_user_logged_in() ) {
			$member_friend_status = friends_check_friendship_status( bp_loggedin_user_id(), bp_displayed_user_id() );

			if ( 'is_friend' === $member_friend_status ) {
				$html = '<div class="social-networks-wrap">' . $html . '</div>';
			} else {
				$html = '';
			}
		}
	}

	return apply_filters( 'bp_get_user_social_networks_urls', $html, $original_option_values, $social_networks_id );
}

/**
 * Decide need to add profile field select box or not.
 *
 * @since BuddyBoss 1.1.3
 *
 * @return bool
 */
function bp_check_member_type_field_have_options() {

	$arr = array();

	// Get posts of custom post type selected.
	$cache_key = 'bp_get_all_member_types_posts';
	$posts     = wp_cache_get( $cache_key, 'bp_member_member_type' );

	if ( false === $posts ) {
		$posts = new \WP_Query(
			array(
				'posts_per_page' => - 1,
				'post_type'      => bp_get_member_type_post_type(),
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		wp_cache_set( $cache_key, $posts, 'bp_member_member_type' );
	}
	if ( $posts ) {
		foreach ( $posts->posts as $post ) {
			$enabled = get_post_meta( $post->ID, '_bp_member_type_enable_profile_field', true );
			if ( '' === $enabled || '1' === $enabled ) {
				$arr[] = $post->ID;
			}
		}
	}

	if ( empty( $arr ) ) {
		return false;
	} else {
		return true;
	}

}

/**
 * Get the display_name for member based on user_id
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $display_name
 * @param int    $user_id
 *
 * @return string
 */
function bp_xprofile_get_member_display_name( $user_id = null ) {
	static $cache;
	// some cases it calls the filter directly, therefore no user id is passed
	if ( ! $user_id ) {
		return false;
	}

	$cache_key = 'bp_xprofile_get_member_display_name_' . trim( $user_id );
	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	$user = get_userdata( $user_id );

	// empty user or deleted user check.
	if ( empty( $user ) ) {
		return false;
	}

	$format = bp_core_display_name_format();

	$display_name = '';

	switch ( $format ) {
		case 'first_name':
			// Get First Name Field Id.
			$first_name_id = (int) bp_get_option( 'bp-xprofile-firstname-field-id' );

			$display_name = xprofile_get_field_data( $first_name_id, $user_id );

			if ( '' === $display_name ) {
				$display_name = get_user_meta( $user_id, 'first_name', true );
				if ( empty( $display_name ) ) {
					$display_name = get_user_meta( $user_id, 'nickname', true );
				}
				xprofile_set_field_data( $first_name_id, $user_id, $display_name );
			}

			// Get Nick Name Field Id.
			$nickname_id = (int) bp_get_option( 'bp-xprofile-nickname-field-id' );
			$nick_name   = xprofile_get_field_data( $nickname_id, $user_id );

			if ( '' === trim( $nick_name ) ) {
				// make sure nickname is valid
				$nickname = get_user_meta( $user_id, 'nickname', true );
				$nickname = sanitize_title( $nickname );
				$invalid  = bp_xprofile_validate_nickname_value( '', $nickname_id, $nickname, $user_id );

				// or use the user_nicename
				if ( ! $nickname || $invalid ) {
					$nickname = ( isset( $user->user_nicename ) ) ? $user->user_nicename : '';
				}
				xprofile_set_field_data( $nickname_id, $user_id, $nickname );
			}

			break;
		case 'first_last_name':
			// Get First Name Field Id.
			$first_name_id = (int) bp_get_option( 'bp-xprofile-firstname-field-id' );
			// Get Last Name Field Id.
			$last_name_id      = (int) bp_get_option( 'bp-xprofile-lastname-field-id' );
			$result_first_name = xprofile_get_field_data( $first_name_id, $user_id );
			$result_last_name  = xprofile_get_field_data( $last_name_id, $user_id );

			if ( '' === $result_first_name ) {
				$result_first_name = get_user_meta( $user_id, 'first_name', true );
				if ( empty( $result_first_name ) ) {
					$result_first_name = get_user_meta( $user_id, 'nickname', true );
				}
				xprofile_set_field_data( $first_name_id, $user_id, $result_first_name );
			}

			if ( '' === $result_last_name ) {
				$result_last_name = get_user_meta( $user_id, 'last_name', true );
				xprofile_set_field_data( $last_name_id, $user_id, $result_last_name );
			}

			$display_name = implode(
				' ',
				array_filter(
					array(
						isset( $result_first_name ) ? $result_first_name : '',
						isset( $result_last_name ) ? $result_last_name : '',
					)
				)
			);

			// Get Nick Name Field Id.
			$nickname_id = (int) bp_get_option( 'bp-xprofile-nickname-field-id' );
			$nick_name   = xprofile_get_field_data( $nickname_id, $user_id );

			if ( '' === trim( $nick_name ) ) {
				// make sure nickname is valid
				$nickname = get_user_meta( $user_id, 'nickname', true );
				$nickname = sanitize_title( $nickname );
				$invalid  = bp_xprofile_validate_nickname_value( '', $nickname_id, $nickname, $user_id );

				// or use the user_nicename
				if ( ! $nickname || $invalid ) {
					$nickname = $user->user_nicename;
				}
				xprofile_set_field_data( $nickname_id, $user_id, $nickname );
			}

			break;
		case 'nickname':
			// Get Nick Name Field Id.
			$nickname_id  = (int) bp_get_option( 'bp-xprofile-nickname-field-id' );
			$display_name = xprofile_get_field_data( $nickname_id, $user_id );

			if ( '' === trim( $display_name ) ) {
				// make sure nickname is valid
				$nickname = get_user_meta( $user_id, 'nickname', true );
				$nickname = sanitize_title( $nickname );
				$invalid  = bp_xprofile_validate_nickname_value( '', $nickname_id, $nickname, $user_id );

				// or use the user_nicename
				if ( ! $nickname || $invalid ) {
					$nickname = $user->user_nicename;
				}
				xprofile_set_field_data( $nickname_id, $user_id, $nickname );
				$display_name = $nickname;

			}
			break;
	}

	$name = apply_filters( 'bp_xprofile_get_member_display_name', trim( $display_name ), $user_id );

	$cache[ $cache_key ] = $name;

	return $name;
}

/**
 * Sync the standard built in WordPress profile data to xprofile data.
 *
 * @since BuddyBoss 1.4.7
 *
 * @param int $user_id sync specified user id first name, last name and nickname.
 *
 * @return void
 */
function bp_xprofile_sync_bp_profile( $user_id ) {

	if ( empty( $user_id ) ) {
		return;
	}

	$user = get_user_by( 'id', $user_id );

	if ( isset( $user->first_name ) ) {
		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $user->ID, $user->first_name );
	}

	if ( isset( $user->last_name ) ) {
		xprofile_set_field_data( bp_xprofile_lastname_field_id(), $user->ID, $user->last_name );
	}

	if ( isset( $user->nickname ) ) {
		xprofile_set_field_data( bp_xprofile_nickname_field_id(), $user->ID, $user->nickname );
	}

}
add_action( 'profile_update', 'bp_xprofile_sync_bp_profile', 999, 1 );

/**
 * Sync the standard built in xprofile data to WordPress data.
 *
 * @since BuddyBoss 1.4.7
 *
 * @param int   $user_id          ID for the user whose profile is being saved.
 * @param array $posted_field_ids Array of field IDs that were edited.
 * @param bool  $errors           Whether or not any errors occurred.
 * @param array $old_values       Array of original values before update.
 * @param array $new_values       Array of newly saved values after update.
 *
 * @return void
 */

function bp_xprofile_sync_wp_profile( $user_id, $posted_field_ids, $errors, $old_values, $new_values ) {

	if ( ! empty( $errors ) ) {
		return;
	}

	foreach ( $new_values as $field_id => $new_value ) {

		// Get First, Last and Nickname field id from DB.
		$firstname_id = bp_xprofile_firstname_field_id();
		$lastname_id  = bp_xprofile_lastname_field_id();
		$nickname_id  = bp_xprofile_nickname_field_id();

		if ( ! $field_id || $field_id == $firstname_id ) {
			bp_update_user_meta( $user_id, 'first_name', $new_value['value'] );
		}

		if ( ! $field_id || $field_id == $lastname_id ) {
			bp_update_user_meta( $user_id, 'last_name', $new_value['value'] );
		}

		if ( ! $field_id || $field_id == $nickname_id ) {
			bp_update_user_meta( $user_id, 'nickname', $new_value['value'] );
		}

	}

	bp_xprofile_update_display_name( $user_id );
}
add_action( 'xprofile_updated_profile', 'bp_xprofile_sync_wp_profile', 999, 5 );

/**
 * Return Transient name using logged in User ID.
 *
 * @param string $key - Transient prefix key
 * @param int $widget_id - Widget id part of transient name string
 *
 * @return string $transient_name
 *
 * @since BuddyBoss 1.4.9
 */
function bp_xprofile_get_profile_completion_transient_name( $key, $widget_id ) {

	$user_id        = get_current_user_id();
	$transient_name = $key . $user_id . $widget_id;

	return apply_filters( 'bp_xprofile_get_profile_completion_transient_name', $transient_name );

}

/**
 * Function returns user progress data by checking if data already exists in transient first. IF NO then follow
 * checking the progress logic.
 *
 * Clear transient when 1) Widget form settings update. 2) When Logged user profile updated. 3) When new profile fields
 * added/updated/deleted.
 *
 * @param array $settings - set of fieldset selected to show in progress & profile or cover photo selected to show in
 *                        progress.
 *
 * @return array $user_progress - user progress to render profile completion
 *
 * @since BuddyBoss 1.5.3
 */
function bp_xprofile_get_user_profile_progress_data( $settings ) {

	$user_progress         = array();
	$user_progress_options = bp_xprofile_get_selected_options_user_progress( $settings );

	// Do not proceed if no fields found based on settings.
	if ( isset( $user_progress_options['total_fields'] ) && $user_progress_options['total_fields'] <= 0 ) {
		return $user_progress;
	}

	// Format User Progress array to pass on to the template.
	$user_progress = bp_xprofile_get_user_progress_formatted( $user_progress_options );

	return $user_progress;
}

/**
 * Function returns logged in user progress based on options selected in the widget form.
 *
 * @param array $group_ids - set of fieldset selected to show in progress
 * @param array $photo_types - profile or cover photo selected to show in progress
 *
 * @return array progress_details - raw details to calculate user progress
 *
 * @since BuddyBoss 1.4.9
 */
function bp_xprofile_get_user_progress( $group_ids, $photo_types ) {

	if( empty($group_ids) ){
		$group_ids = array();
	}

	/* User Progress specific VARS. */
	$user_id                = get_current_user_id();
	$progress_details       = array();
	$grand_total_fields     = 0;
	$grand_completed_fields = 0;

	/* Profile Photo */

	// check if profile photo option still enabled.
	$is_profile_photo_disabled = bp_disable_avatar_uploads();
	if ( ! $is_profile_photo_disabled && in_array( 'profile_photo', $photo_types ) ) {

		++ $grand_total_fields;

		$is_profile_photo_uploaded = ( bp_get_user_has_avatar( $user_id ) ) ? 1 : 0;

		if ( $is_profile_photo_uploaded ) {
			++ $grand_completed_fields;
		} else {

			// check if profile gravatar option enabled.
			// blank setting will remove gravatar also
			if ( bp_enable_profile_gravatar() && 'blank' !== get_option( 'avatar_default', 'mystery' ) ) {

				/**
				 * There is not any direct way to check gravatar set for user.
				 * Need to check $profile_url is send 200 status or not.
				 */
				$profile_url = get_avatar_url( $user_id, array( 'default' => '404' ) );

				$headers = get_headers( $profile_url, 1 );
				if ( $headers[0] === 'HTTP/1.1 200 OK' && isset( $headers['Link'] ) ) {
					$is_profile_photo_uploaded = 1;
					++ $grand_completed_fields;
				}
			}
		}

		$progress_details['photo_type']['profile_photo'] = array(
			'is_uploaded' => $is_profile_photo_uploaded,
			'name'        => __( 'Profile Photo', 'buddyboss' ),
		);

	}

	/* Cover Photo */

	// check if cover photo option still enabled.
	$is_cover_photo_disabled = bp_disable_cover_image_uploads();
	if ( ! $is_cover_photo_disabled && in_array( 'cover_photo', $photo_types ) ) {

		++ $grand_total_fields;

		$is_cover_photo_uploaded = ( bp_attachments_get_user_has_cover_image( $user_id ) ) ? 1 : 0;

		if ( $is_cover_photo_uploaded ) {
			++ $grand_completed_fields;
		}

		$progress_details['photo_type']['cover_photo'] = array(
			'is_uploaded' => $is_cover_photo_uploaded,
			'name'        => __( 'Cover Photo', 'buddyboss' ),
		);

	}

	/* Groups Fields */

	// Get Groups and Group fields with Loggedin user data.
	$profile_groups = bp_xprofile_get_groups(
		array(
			'fetch_fields'                   => true,
			'fetch_field_data'               => true,
			'user_id'                        => $user_id,
			'repeater_show_main_fields_only' => false,
			'fetch_social_network_fields'    => true,
		)
	);

	foreach ( $profile_groups as $single_group_details ) {

		if ( empty( $single_group_details->fields ) ) {
			continue;
		}

		/* Single Group Specific VARS */
		$group_id              = $single_group_details->id;
		$single_group_progress = array();

		// Consider only selected Groups ids from the widget form settings, skip all others.
		if ( ! in_array( $group_id, $group_ids ) ) {
			continue;
		}

		// Check if Current Group is repeater if YES then get number of fields inside current group.
		$is_group_repeater_str = bp_xprofile_get_meta( $group_id, 'group', 'is_repeater_enabled', true );
		$is_group_repeater     = ( 'on' === $is_group_repeater_str ) ? true : false;

		/* Loop through all the fields and check if fields completed or not. */
		$group_total_fields     = 0;
		$group_completed_fields = 0;
		foreach ( $single_group_details->fields as $group_single_field ) {

			/**
			 * Added support for display name format support from platform.
			 * Get the current display settings from BuddyBoss > Settings > Profiles > Display Name Format.
			 */
			if ( function_exists( 'bp_core_hide_display_name_field' ) && true === bp_core_hide_display_name_field( $group_single_field->id ) ) {
				continue;
			}

			// If current group is repeater then only consider first set of fields.
			if ( $is_group_repeater ) {

				// If field not a "clone number 1" then stop. That means proceed with the first set of fields and restrict others.
				$field_id     = $group_single_field->id;
				$clone_number = bp_xprofile_get_meta( $field_id, 'field', '_clone_number', true );
				if ( $clone_number > 1 ) {
					continue;
				}
			}

			// For Social networks field check child field is completed or not
			if  ( 'socialnetworks' == $group_single_field->type ){
				$field_data_value = maybe_unserialize( $group_single_field->data->value );
				$children = $group_single_field->type_obj->field_obj->get_children();
				foreach ( $children as $child ){
					if ( isset( $field_data_value[$child->name] ) &&  ! empty( $field_data_value[$child->name] ) ) {
						++ $group_completed_fields;
					}
					++ $group_total_fields;
				}
			} else{
				$field_data_value = maybe_unserialize( $group_single_field->data->value );

				if ( ! empty( $field_data_value ) ) {
					++ $group_completed_fields;
				}

				++ $group_total_fields;
			}
		}

		/* Prepare array to return group specific progress details */
		$single_group_progress['group_name']             = $single_group_details->name;
		$single_group_progress['group_total_fields']     = $group_total_fields;
		$single_group_progress['group_completed_fields'] = $group_completed_fields;

		$grand_total_fields     += $group_total_fields;
		$grand_completed_fields += $group_completed_fields;

		$progress_details['groups'][ $group_id ] = $single_group_progress;

	}

	/* Total Fields vs completed fields to calculate progress percentage. */
	$progress_details['total_fields']     = $grand_total_fields;
	$progress_details['completed_fields'] = $grand_completed_fields;

	/**
	 * Filter returns User Progress array.
	 *
	 * @since BuddyBoss 1.2.5
	 */
	return apply_filters( 'xprofile_pc_user_progress', $progress_details );
}

/**
 * Function formats user progress to pass on to templates.
 *
 * @param array $user_progress_arr - raw details to calculate user progress
 *
 * @return array $user_prgress_formatted - user progress to render profile completion
 *
 * @since BuddyBoss 1.4.9
 */
function bp_xprofile_get_user_progress_formatted( $user_progress_arr ) {

	/* Groups */

	$loggedin_user_domain = bp_loggedin_user_domain();
	$profile_slug          = bp_get_profile_slug();

	// Calculate Total Progress percentage.
	$profile_completion_percentage = round( ( $user_progress_arr['completed_fields'] * 100 ) / $user_progress_arr['total_fields'] );
	$user_prgress_formatted        = array(
		'completion_percentage' => $profile_completion_percentage,
	);

	// Group specific details
	$listing_number = 1;
	if( isset( $user_progress_arr['groups'] ) ){
		foreach ( $user_progress_arr['groups'] as $group_id => $group_details ) {

			$group_link = trailingslashit( $loggedin_user_domain . $profile_slug . '/edit/group/' . $group_id );

			$user_prgress_formatted['groups'][] = array(
				'number'             => $listing_number,
				'label'              => $group_details['group_name'],
				'link'               => $group_link,
				'is_group_completed' => ( $group_details['group_total_fields'] === $group_details['group_completed_fields'] ) ? true : false,
				'total'              => $group_details['group_total_fields'],
				'completed'          => $group_details['group_completed_fields'],
			);

			$listing_number ++;
		}
	}

	/* Profile Photo */
	if ( isset( $user_progress_arr['photo_type']['profile_photo'] ) ) {

		$change_avatar_link  = trailingslashit( $loggedin_user_domain . $profile_slug . '/change-avatar' );
		$is_profile_uploaded = ( 1 === $user_progress_arr['photo_type']['profile_photo']['is_uploaded'] );

		$user_prgress_formatted['groups'][] = array(
			'number'             => $listing_number,
			'label'              => $user_progress_arr['photo_type']['profile_photo']['name'],
			'link'               => $change_avatar_link,
			'is_group_completed' => ( $is_profile_uploaded ) ? true : false,
			'total'              => 1,
			'completed'          => ( $is_profile_uploaded ) ? 1 : 0,
		);

		$listing_number ++;
	}

	/* Cover Photo */
	if ( isset( $user_progress_arr['photo_type']['cover_photo'] ) ) {

		$change_cover_link = trailingslashit( $loggedin_user_domain . $profile_slug . '/change-cover-image' );
		$is_cover_uploaded = ( 1 === $user_progress_arr['photo_type']['cover_photo']['is_uploaded'] );

		$user_prgress_formatted['groups'][] = array(
			'number'             => $listing_number,
			'label'              => $user_progress_arr['photo_type']['cover_photo']['name'],
			'link'               => $change_cover_link,
			'is_group_completed' => ( $is_cover_uploaded ) ? true : false,
			'total'              => 1,
			'completed'          => ( $is_cover_uploaded ) ? 1 : 0,
		);

		$listing_number ++;
	}

	/**
	 * Filter returns User Progress array in the template friendly format.
	 *
	 * @since BuddyBoss 1.2.5
	 */
	return apply_filters( 'xprofile_pc_user_progress_formatted', $user_prgress_formatted );
}

/**
 * Reset cover image position while uploading/deleting profile cover photo.
 *
 * @since BuddyBoss 1.5.1
 *
 * @param int $user_id User ID.
 */
function bp_xprofile_reset_cover_image_position( $user_id ) {
	if ( ! empty( (int) $user_id ) ) {
		bp_delete_user_meta( (int) $user_id, 'bp_cover_position' );
	}
}
add_action( 'xprofile_cover_image_uploaded', 'bp_xprofile_reset_cover_image_position', 10, 1 );
add_action( 'xprofile_cover_image_deleted', 'bp_xprofile_reset_cover_image_position', 10, 1 );

/**
 * Function will return the users id based on given xprofile field id and field value.
 *
 * @param int    $field_id  to check against the filed id.
 * @param string $field_val to check against the filed value.
 *
 * @since BuddyBoss 1.5.7
 *
 * @return bool|array
 */
function bp_xprofile_get_users_by_field_value( $field_id, $field_val ) {
	global $wpdb, $bp;

	$bp_table = $bp->profile->table_name_data;

	$query = $wpdb->prepare(
		"SELECT U.ID " .
		"FROM $bp_table B, $wpdb->users U " .
		"WHERE B.user_id = U.ID " .
		"AND B.field_id = %d " .
		"AND B.value = %s"
		, $field_id
		, $field_val
	);

	$get_desired = $wpdb->get_results( $query );

	if( count( $get_desired ) ) {
		return $get_desired;
	} else {
		return false;
	}
}

/**
 * Enabled the social networks for members or not.
 *
 * @since BuddyBoss 1.9.1
 *
 * @return bool True if enabled the social networks otherwise false.
 */
function bb_enabled_member_social_networks() {
	static $social_networks_id = '';

	if ( '' === $social_networks_id ) {
		global $wpdb, $bp;

		$social_networks_id = (int) $wpdb->get_var( "SELECT a.id FROM {$bp->table_prefix}bp_xprofile_fields a WHERE parent_id = 0 AND type = 'socialnetworks' " ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	return apply_filters( 'bb_enabled_member_social_networks', (bool) $social_networks_id );
}

/**
 * Get social networks field values.
 *
 * @since BuddyBoss 1.9.1
 *
 * @param int|null $user_id ID of the user or null. Default current displayed user profile ID.
 *
 * @return array
 */
function bb_get_user_social_networks_field_value( $user_id = null ) {
	global $wpdb, $bp;

	$social_networks_id = (int) $wpdb->get_var( "SELECT a.id FROM {$bp->table_prefix}bp_xprofile_fields a WHERE parent_id = 0 AND type = 'socialnetworks' " ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	$original_option_values = array();

	$user = ( null !== $user_id && 0 < $user_id ) ? $user_id : bp_displayed_user_id();

	if ( $social_networks_id > 0 ) {
		$original_option_values = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $social_networks_id, $user ) );
	}

	return $original_option_values;
}

/**
 * Get a profile Field Type object.
 *
 * @since BuddyBoss 2.3.70
 *
 * @param int $field_id ID of the field.
 *
 * @return BP_XProfile_Field_Type|null Field Type object if found, otherwise null.
 */
function bb_xprofile_get_field_type( $field_id ) {
	$field_type = null;
	$field      = xprofile_get_field( $field_id, null, false );

	if ( $field instanceof BP_XProfile_Field ) {
		$field_type = $field->type_obj;
	}

	return $field_type;
}

/**
 * Function to update xprofile social networks field values.
 *
 * @since BuddyBoss 2.4.30
 *
 * @return void
 */
function bb_xprofile_update_social_network_fields() {
	global $wpdb, $bb_background_updater;

	/**
	 * Check the google+ was setup or not in social network field.
	 */
	$table_name      = bp_core_get_table_prefix() . 'bp_xprofile_fields';
	$social_networks = $wpdb->get_col( "SELECT id FROM {$table_name} a WHERE type = 'socialnetworks'" ); //phpcs:ignore
	if (
		! empty( $social_networks ) &&
		! is_wp_error( $social_networks )
	) {
		foreach ( $social_networks as $network_field_id ) {
			$field = xprofile_get_field( $network_field_id );
			if ( ! empty( $field->id ) ) {
				$field_name      = 'google';
				$sql             = $wpdb->prepare( "SELECT id from {$table_name} WHERE parent_id = %d AND name = %s", $field->id, $field_name ); // phpcs:ignore
				$google_field_id = $wpdb->get_var( $sql ); //phpcs:ignore

				if ( ! empty( $google_field_id ) ) {
					$wpdb->query( "DELETE FROM {$table_name} WHERE id = {$google_field_id}" ); //phpcs:ignore

					$bb_background_updater->data(
						array(
							'type'     => 'xprofile',
							'group'    => 'bb_remove_google_plus_fields',
							'data_id'  => $network_field_id,
							'priority' => 5,
							'callback' => 'bb_remove_google_plus_fields',
							'args'     => array( $network_field_id, $field_name ),
						)
					);

					$bb_background_updater->save();
				}
			}
		}
		$bb_background_updater->dispatch();
	}
}

/**
 * Function to remove google+ field values from xprofile data.
 *
 * @since BuddyBoss 2.4.30
 *
 * @param int    $field_id   To check against the filed id.
 * @param string $field_name To check against the filed name.
 *
 * @return void
 */
function bb_remove_google_plus_fields( $field_id, $field_name ) {
	global $wpdb, $bb_background_updater;
	if ( empty( $field_name ) || empty( $field_id ) ) {
		return;
	}

	$table_name = bp_core_get_table_prefix() . 'bp_xprofile_data';
	$user_ids   = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$table_name} WHERE field_id = %d and value like %s limit 0, 20", $field_id, '%' . $wpdb->esc_like( $field_name ) . '%' ) ); // phpcs:ignore

	if (
		! empty( $user_ids ) &&
		! is_wp_error( $user_ids )
	) {
		foreach ( $user_ids as $user_id ) {
			$field_data = new BP_XProfile_ProfileData( $field_id, $user_id );
			$data_value = maybe_unserialize( $field_data->value );
			if ( ! empty( $data_value ) && isset( $data_value[ $field_name ] ) ) {
				$field_value = $data_value[ $field_name ];
				unset( $data_value[ $field_name ] );
				update_user_meta( $user_id, 'bb_xprofile_social_google_plus', $field_value );
				$field_data->value = maybe_serialize( $data_value );
				$field_data->save();
			}
		}

		$bb_background_updater->data(
			array(
				'type'     => 'xprofile',
				'group'    => 'bb_remove_google_plus_fields',
				'data_id'  => $field_id,
				'priority' => 5,
				'callback' => 'bb_remove_google_plus_fields',
				'args'     => array( $field_id, $field_name ),
			)
		);

		$bb_background_updater->save()->dispatch();
	}
}
