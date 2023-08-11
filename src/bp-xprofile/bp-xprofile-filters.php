<?php
/**
 * BuddyPress XProfile Filters.
 *
 * Apply WordPress defined filters.
 *
 * @package BuddyBoss\XProfile
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'bp_get_the_profile_group_name', 'wp_filter_kses', 1 );
add_filter( 'bp_get_the_profile_group_description', 'wp_filter_kses', 1 );
add_filter( 'bp_get_the_profile_field_value', 'xprofile_sanitize_data_value_before_display', 1, 3 );
add_filter( 'bp_get_the_profile_field_name', 'wp_filter_kses', 1 );
add_filter( 'bp_get_the_profile_field_edit_value', 'xprofile_sanitize_data_value_before_display', 1, 3 );
add_filter( 'bp_get_the_profile_field_description', 'wp_filter_kses', 1 );

add_filter( 'bp_get_the_profile_field_value', 'wptexturize' );
add_filter( 'bp_get_the_profile_field_value', 'convert_chars' );
add_filter( 'bp_get_the_profile_field_value', 'wpautop' );
add_filter( 'bp_get_the_profile_field_value', 'force_balance_tags' );
add_filter( 'bp_get_the_profile_field_value', 'make_clickable' );
add_filter( 'bp_get_the_profile_field_value', 'bp_xprofile_escape_field_data', 8, 3 );
add_filter( 'bp_get_the_profile_field_value', 'convert_smilies', 9 );
add_filter( 'bp_get_the_profile_field_value', 'xprofile_filter_format_field_value', 1, 2 );
add_filter( 'bp_get_the_profile_field_value', 'xprofile_filter_format_field_value_by_type', 8, 3 );
add_filter( 'bp_get_the_profile_field_value', 'xprofile_filter_link_profile_data', 9, 3 );

add_filter( 'bp_get_the_profile_field_edit_value', 'force_balance_tags' );
add_filter( 'bp_get_the_profile_field_edit_value', 'bp_xprofile_escape_field_data', 10, 3 );

add_filter( 'bp_get_the_profile_group_name', 'stripslashes' );
add_filter( 'bp_get_the_profile_group_description', 'stripslashes' );
add_filter( 'bp_get_the_profile_field_value', 'stripslashes' );
add_filter( 'bp_get_the_profile_field_edit_value', 'stripslashes' );
add_filter( 'bp_get_the_profile_field_name', 'stripslashes' );
add_filter( 'bp_get_the_profile_field_description', 'stripslashes' );

add_filter( 'xprofile_get_field_data', 'xprofile_filter_kses', 1 );
add_filter( 'xprofile_field_name_before_save', 'wp_filter_kses', 1 );
add_filter( 'xprofile_field_description_before_save', 'wp_filter_kses', 1 );

add_filter( 'xprofile_get_field_data', 'force_balance_tags' );
add_filter( 'xprofile_field_name_before_save', 'force_balance_tags' );
add_filter( 'xprofile_field_description_before_save', 'force_balance_tags' );

add_filter( 'xprofile_get_field_data', 'stripslashes' );
add_filter( 'xprofile_get_field_data', 'xprofile_filter_format_field_value_by_field_id', 5, 2 );

add_filter( 'bp_xprofile_set_field_data_pre_validate', 'xprofile_filter_pre_validate_value_by_field_type', 10, 3 );
add_filter( 'xprofile_data_value_before_save', 'xprofile_sanitize_data_value_before_save', 1, 4 );
add_filter( 'xprofile_filtered_data_value_before_save', 'trim', 2 );

// Save field groups.
add_filter( 'xprofile_group_name_before_save', 'wp_filter_kses' );
add_filter( 'xprofile_group_description_before_save', 'wp_filter_kses' );

add_filter( 'xprofile_group_name_before_save', 'stripslashes' );
add_filter( 'xprofile_group_description_before_save', 'stripslashes' );

// Save fields.
add_filter( 'xprofile_field_name_before_save', 'wp_filter_kses' );
add_filter( 'xprofile_field_type_before_save', 'wp_filter_kses' );
add_filter( 'xprofile_field_description_before_save', 'wp_filter_kses' );
add_filter( 'xprofile_field_order_by_before_save', 'wp_filter_kses' );

add_filter( 'xprofile_field_is_required_before_save', 'absint' );
add_filter( 'xprofile_field_field_order_before_save', 'absint' );
add_filter( 'xprofile_field_option_order_before_save', 'absint' );
add_filter( 'xprofile_field_can_delete_before_save', 'absint' );

// Un slash field value.
add_filter( 'xprofile_field_name_before_save', 'wp_unslash', 99 );
add_filter( 'xprofile_field_description_before_save', 'wp_unslash', 99 );

// Save field options.
add_filter( 'xprofile_field_options_before_save', 'bp_xprofile_sanitize_field_options' );
add_filter( 'xprofile_field_default_before_save', 'bp_xprofile_sanitize_field_default' );

add_filter( 'bp_get_the_profile_field_name', 'xprofile_filter_field_edit_name' );
add_filter( 'bp_core_get_user_displayname', 'xprofile_filter_get_user_display_name', 15, 2 );

// Saving field value
add_filter( 'xprofile_validate_field', 'bp_xprofile_validate_nickname_value', 10, 4 );
add_filter( 'xprofile_validate_field', 'bp_xprofile_validate_phone_value', 10, 4 );
add_filter( 'xprofile_validate_field', 'bp_xprofile_validate_social_networks_value', 10, 4 );

// Display name adjustment
add_filter( 'bp_set_current_user', 'bp_xprofile_adjust_current_user_display_name' );
add_filter( 'get_user_metadata', 'bp_xprofile_adjust_display_name', 10, 3 );

// Email Username
add_filter( 'new_admin_email_content', 'bp_xprofile_replace_username_to_display_name', 10, 2 );
add_filter( 'delete_site_email_content', 'bp_xprofile_replace_username_to_display_name', 10, 2 );
add_filter( 'new_network_admin_email_content', 'bp_xprofile_replace_username_to_display_name', 10, 2 );
add_filter( 'password_change_email', 'bp_xprofile_replace_username_to_display_name', 10, 2 );
add_filter( 'email_change_email', 'bp_xprofile_replace_username_to_display_name', 10, 2 );
add_filter( 'new_user_email_content', 'bp_xprofile_replace_username_to_display_name', 10, 2 );

// Profile Completion.
add_action( 'xprofile_avatar_uploaded', 'bp_core_xprofile_update_profile_completion_user_progress' ); // When profile photo uploaded from profile in Frontend.
add_action( 'xprofile_cover_image_uploaded', 'bp_core_xprofile_update_profile_completion_user_progress' ); // When cover photo uploaded from profile in Frontend.
add_action( 'bp_core_delete_existing_avatar', 'bp_core_xprofile_update_profile_completion_user_progress' ); // When profile photo deleted from profile in Frontend.
add_action( 'xprofile_cover_image_deleted', 'bp_core_xprofile_update_profile_completion_user_progress' ); // When cover photo deleted from profile in Frontend.
add_action( 'xprofile_updated_profile', 'bp_core_xprofile_update_profile_completion_user_progress', 20, 5 ); // On Profile updated from frontend.
add_action( 'wp_ajax_xprofile_reorder_fields', 'bp_core_xprofile_update_profile_completion_user_progress' ); // When fields inside fieldset are dragged and dropped in wp-admin > buddybpss > profile.

// Profile Completion Admin Actions.
add_action( 'xprofile_fields_saved_field', 'bp_core_xprofile_clear_all_user_progress_cache' ); // On field added/updated in wp-admin > Profile
add_action( 'xprofile_groups_saved_group', 'bp_core_xprofile_clear_all_user_progress_cache' ); // On profile group added/updated in wp-admin > Profile
add_action( 'xprofile_fields_deleted_field', 'bp_core_xprofile_clear_all_user_progress_cache' ); // On field deleted in wp-admin > profile.
add_action( 'xprofile_groups_deleted_group', 'bp_core_xprofile_clear_all_user_progress_cache' ); // On profile group deleted in wp-admin.
add_action( 'update_option_bp-disable-avatar-uploads', 'bp_core_xprofile_clear_all_user_progress_cache' ); // When avatar photo setting updated in wp-admin > Settings > profile.
add_action( 'update_option_bp-disable-cover-image-uploads', 'bp_core_xprofile_clear_all_user_progress_cache' ); // When cover photo setting updated in wp-admin > Settings > profile.
add_action( 'xprofile_groups_saved_group', 'bb_core_xprofile_clear_group_cache' );
add_action( 'xprofile_groups_deleted_group', 'bb_core_xprofile_clear_group_cache' );
add_action( 'xprofile_fields_deleted_field', 'bb_core_xprofile_clear_group_cache' );

// Display Name setting support
add_filter( 'bp_after_has_profile_parse_args', 'bp_xprofile_exclude_display_name_profile_fields' );

// Repair repeater field repeated in admin side.
add_filter( 'bp_repair_list', 'bb_xprofile_repeater_field_repair' );

// Repair user nicknames.
add_filter( 'bp_repair_list', 'bb_xprofile_repair_user_nicknames' );

// Validate user_nickname when user created from the backend
add_filter( 'insert_user_meta', 'bb_validate_user_nickname_on_user_register', 10, 3 );
add_action( 'user_profile_update_errors', 'bb_validate_user_nickname_on_user_update', 10, 3 );

add_filter( 'bp_before_has_profile_parse_args', 'bb_xprofile_set_social_network_param' );

// When email changed then check profile completion for gravatar.
add_action( 'profile_update', 'bb_profile_update_completion_user_progress', 10, 2 );

/**
 * Sanitize each field option name for saving to the database.
 *
 * @since BuddyPress 2.3.0
 *
 * @param mixed $field_options Options to sanitize.
 * @return mixed
 */
function bp_xprofile_sanitize_field_options( $field_options = '' ) {
	if ( is_array( $field_options ) ) {
		return array_map( 'sanitize_text_field', $field_options );
	} else {
		return sanitize_text_field( $field_options );
	}
}

/**
 * Sanitize each field option default for saving to the database.
 *
 * @since BuddyPress 2.3.0
 *
 * @param mixed $field_default Field defaults to sanitize.
 * @return array|int
 */
function bp_xprofile_sanitize_field_default( $field_default = '' ) {
	if ( is_array( $field_default ) ) {
		return array_map( 'intval', $field_default );
	} else {
		return intval( $field_default );
	}
}

/**
 * Run profile field values through kses with filterable allowed tags.
 *
 * @since BuddyPress 1.5.0
 *
 * @param string      $content  Content to filter.
 * @param object|null $data_obj The BP_XProfile_ProfileData object.
 * @param int|null    $field_id Optional. The ID of the profile field.
 * @return string $content
 */
function xprofile_filter_kses( $content, $data_obj = null, $field_id = null ) {
	global $allowedtags;

	$xprofile_allowedtags                = $allowedtags;
	$xprofile_allowedtags['a']['rel']    = array();
	$xprofile_allowedtags['a']['target'] = array();

	if ( null === $field_id && $data_obj instanceof BP_XProfile_ProfileData ) {
		$field_id = $data_obj->field_id;
	}

	// If the field supports rich text, we must allow tags that appear in wp_editor().
	if ( $field_id && bp_xprofile_is_richtext_enabled_for_field( $field_id ) ) {
		$richtext_tags = array(
			'img'  => array(
				'id'     => 1,
				'class'  => 1,
				'src'    => 1,
				'alt'    => 1,
				'width'  => 1,
				'height' => 1,
			),
			'ul'   => array(
				'id'    => 1,
				'class' => 1,
			),
			'ol'   => array(
				'id'    => 1,
				'class' => 1,
			),
			'li'   => array(
				'id'    => 1,
				'class' => 1,
			),
			'span' => array(),
			'p'    => array(),
			'a'    => array(
				'href'   => 1,
				'target' => 1,
			),
		);

		// Allow style attributes on certain elements for capable users
		if ( bp_current_user_can( 'unfiltered_html' ) ) {
			$richtext_tags['span'] = array( 'style' => 1 );
			$richtext_tags['p']    = array( 'style' => 1 );
		}

		$xprofile_allowedtags = array_merge( $allowedtags, $richtext_tags );
	}

	// If the field type is social network then allow some tags.
	if ( $field_id ) {

		$field      = xprofile_get_field( $field_id );
		$field_type = $field->type ?? '';

		if ( 'socialnetworks' === $field_type ) {
			$social_tags = array(
				'div'    => array(
					'class' => 1,
					'id'    => 1,
				),
				'span'   => array(
					'class' => 1,
				),
				'i'      => array(
					'class' => 1,
				),
				'a'      => array(
					'href'   => 1,
					'target' => 1,
					'data-*' => 1,
					'class'  => 1,
				),
				'p'      => array(),
				'h4'     => array(),
				'header' => array(
					'class' => 1,
				),
			);

			$xprofile_allowedtags = array_merge( $allowedtags, $social_tags );
		}
	}

	/**
	 * Filters the allowed tags for use within xprofile_filter_kses().
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param array                   $xprofile_allowedtags Array of allowed tags for profile field values.
	 * @param BP_XProfile_ProfileData $data_obj             The BP_XProfile_ProfileData object.
	 */
	$xprofile_allowedtags = apply_filters( 'xprofile_allowed_tags', $xprofile_allowedtags, $data_obj, $field_id );
	return wp_kses( $content, $xprofile_allowedtags );
}

/**
 * Filters profile field values for whitelisted HTML.
 *
 * @since BuddyBoss 1.2.3
 *
 * @param string $value    Field value.
 * @param string $type     Field type.
 * @param int    $field_id Field ID.
 */
function xprofile_sanitize_data_value_before_display( $value, $type, $field_id ) {
	return xprofile_filter_kses( $value, null, $field_id );
}

/**
 * Safely runs profile field data through kses and force_balance_tags.
 *
 * @since BuddyPress 1.2.6
 *
 * @param string      $field_value Field value being santized.
 * @param int         $field_id Field ID being sanitized.
 * @param bool        $reserialize Whether to reserialize arrays before returning. Defaults to true.
 * @param object|null $data_obj The BP_XProfile_ProfileData object.
 *
 * @return string
 */
function xprofile_sanitize_data_value_before_save( $field_value, $field_id = 0, $reserialize = true, $data_obj = null ) {

	// Return if empty.
	if ( empty( $field_value ) ) {
		return $field_value;
	}

	if ( isset( $data_obj->field_id ) && ! empty( $data_obj->field_id ) ) {
		$fields = xprofile_get_field( $data_obj->field_id, null, false );

		// Allows storing the 'facebook', 'twitter' and so on as array keys in the data.
		if ( isset( $fields->type ) && 'socialnetworks' === $fields->type ) {
			return $field_value;
		}
	}

	// Force reserialization if serialized (avoids mutation, retains integrity)
	if ( is_serialized( $field_value ) && ( false === $reserialize ) ) {
		$reserialize = true;
	}

	// Value might be a serialized array of options.
	$field_value = maybe_unserialize( $field_value );

	// Sanitize single field value.
	if ( ! is_array( $field_value ) ) {
		$kses_field_value     = xprofile_filter_kses( $field_value, $data_obj );
		$filtered_field_value = wp_rel_nofollow( force_balance_tags( $kses_field_value ) );

		/**
		 * Filters the kses-filtered data before saving to database.
		 *
		 * @since BuddyPress 1.5.0
		 *
		 * @param string $filtered_field_value The filtered value.
		 * @param string $field_value The original value before filtering.
		 * @param BP_XProfile_ProfileData $data_obj The BP_XProfile_ProfileData object.
		 */
		$filtered_field_value = apply_filters( 'xprofile_filtered_data_value_before_save', $filtered_field_value, $field_value, $data_obj );

		// Sanitize multiple individual option values.
	} else {
		$filtered_values = array();
		foreach ( (array) $field_value as $value ) {
			$kses_field_value = xprofile_filter_kses( $value, $data_obj );
			$filtered_value   = wp_rel_nofollow( force_balance_tags( $kses_field_value ) );

			/** This filter is documented in bp-xprofile/bp-xprofile-filters.php */
			$filtered_values[] = apply_filters( 'xprofile_filtered_data_value_before_save', $filtered_value, $value, $data_obj );
		}

		if ( ! empty( $reserialize ) ) {
			$filtered_field_value = serialize( $filtered_values );
		} else {
			$filtered_field_value = $filtered_values;
		}
	}

	return $filtered_field_value;
}

/**
 * Runs stripslashes on XProfile fields.
 *
 * @since BuddyPress 1.0.0
 *
 * @param string $field_value XProfile field_value to be filtered.
 * @param string $field_type  XProfile field_type to be filtered.
 * @return false|string $field_value Filtered XProfile field_value. False on failure.
 */
function xprofile_filter_format_field_value( $field_value, $field_type = '' ) {

	// Valid field values of 0 or '0' get caught by empty(), so we have an extra check for these. See #BP5731.
	if ( ! isset( $field_value ) || empty( $field_value ) && ( '0' !== $field_value ) ) {
		return false;
	}

	if ( 'datebox' !== $field_type ) {
		$field_value = str_replace( ']]>', ']]&gt;', $field_value );
	}

	return stripslashes( $field_value );
}

/**
 * Apply display_filter() filters as defined by BP_XProfile_Field_Type classes, when inside a bp_has_profile() loop.
 *
 * @since BuddyPress 2.1.0
 * @since BuddyPress 2.4.0 Added `$field_id` parameter.
 *
 * @param mixed      $field_value Field value.
 * @param string     $field_type  Field type.
 * @param string|int $field_id    Optional. ID of the field.
 * @return mixed
 */
function xprofile_filter_format_field_value_by_type( $field_value, $field_type = '', $field_id = '' ) {
	foreach ( bp_xprofile_get_field_types() as $type => $class ) {
		if ( $type !== $field_type ) {
			continue;
		}

		if ( method_exists( $class, 'display_filter' ) ) {
			$field_value = call_user_func( array( $class, 'display_filter' ), $field_value, $field_id );
		}
	}

	return $field_value;
}

/**
 * Apply display_filter() filters as defined by the BP_XProfile_Field_Type classes, when fetched
 * by xprofile_get_field_data().
 *
 * @since BuddyPress 2.1.0
 *
 * @param mixed $field_value Field value.
 * @param int   $field_id    Field type.
 * @return string
 */
function xprofile_filter_format_field_value_by_field_id( $field_value, $field_id ) {
	$field = xprofile_get_field( $field_id );
	return xprofile_filter_format_field_value_by_type( $field_value, $field->type, $field_id );
}

/**
 * Apply pre_validate_filter() filters as defined by the BP_XProfile_Field_Type classes before validating.
 *
 * @since BuddyPress 2.1.0
 *
 * @param mixed                  $value          Value passed to the bp_xprofile_set_field_data_pre_validate filter.
 * @param BP_XProfile_Field      $field          Field object.
 * @param BP_XProfile_Field_Type $field_type_obj Field type object.
 * @return mixed
 */
function xprofile_filter_pre_validate_value_by_field_type( $value, $field, $field_type_obj ) {
	if ( method_exists( $field_type_obj, 'pre_validate_filter' ) ) {
		$value = call_user_func( array( $field_type_obj, 'pre_validate_filter' ), $value, $field->id );
	}

	return $value;
}

/**
 * Escape field value for display.
 *
 * Most field values are simply run through esc_html(). Those that support rich text (by default, `textarea` only)
 * are sanitized using kses, which allows a whitelist of HTML tags.
 *
 * @since BuddyPress 2.4.0
 *
 * @param string $value      Field value.
 * @param string $field_type Field type.
 * @param int    $field_id   Field ID.
 * @return string
 */
function bp_xprofile_escape_field_data( $value, $field_type, $field_id ) {
	if ( bp_xprofile_is_richtext_enabled_for_field( $field_id ) ) {
		// The xprofile_filter_kses() expects a BP_XProfile_ProfileData object.
		$data_obj = null;
		if ( bp_is_user() ) {
			$data_obj = new BP_XProfile_ProfileData( $field_id, bp_displayed_user_id() );
		}

		$value = xprofile_filter_kses( $value, $data_obj, $field_id );
	} elseif ( 'socialnetworks' === $field_type ) {
		$data_obj = null;
		if ( bp_is_user() ) {
			$data_obj = new BP_XProfile_ProfileData( $field_id, bp_displayed_user_id() );
		}
		$value = xprofile_filter_kses( $value, $data_obj );
	} else {
		$value = esc_html( $value );
	}

	return $value;
}

/**
 * Filter an Extended Profile field value, and attempt to make clickable links
 * to members search results out of them.
 *
 * - Not run on datebox field types.
 * - Not run on values without commas with less than 5 words.
 * - URL's are made clickable.
 *
 * To disable globally:
 *     remove_filter( 'bp_get_the_profile_field_value', 'xprofile_filter_link_profile_data', 9, 3 );
 *
 * To disable for a single field, use the 'Autolink' settings in Dashboard > Users > Profile Fields.
 *
 * @since BuddyPress 1.1.0
 * @since BuddyBoss 1.0.0
 * Removed checking autolink, as autolinking is disabled on all fields now.
 * All this function does now is make links clickable.
 *
 * @param string $field_value Profile field data value.
 * @param string $field_type  Profile field type.
 * @return string|array
 */
function xprofile_filter_link_profile_data( $field_value, $field_type = 'textbox' ) {
	global $field;

	if ( 'datebox' === $field_type ) {
		return $field_value;
	}

	if ( strpos( $field_value, ',' ) === false && strpos( $field_value, ';' ) === false && ( count( explode( ' ', $field_value ) ) > 5 ) ) {
		return $field_value;
	}

	// If the value is a URL, make it clickable.
	if ( preg_match( '@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', $field_value ) ) {
		$field_value = make_clickable( $field_value );
	}

	return $field_value;
}

/**
 * Ensures that BP data appears in comments array.
 *
 * This filter loops through the comments return by a normal WordPress request
 * and swaps out user data with BP xprofile data, where available.
 *
 * @since BuddyPress 1.2.0
 *
 * @param array $comments Comments to filter in.
 * @param int   $post_id  Post ID the comments are for.
 * @return array $comments
 */
function xprofile_filter_comments( $comments, $post_id = 0 ) {

	// Locate comment authors with WP accounts.
	foreach ( (array) $comments as $comment ) {
		if ( $comment->user_id ) {
			$user_ids[] = $comment->user_id;
		}
	}

	// If none are found, just return the comments array.
	if ( empty( $user_ids ) ) {
		return $comments;
	}

	// Pull up the xprofile fullname of each commenter.
	if ( $fullnames = bp_core_get_user_displaynames( $user_ids ) ) {
		foreach ( (array) $fullnames as $user_id => $user_fullname ) {
			$users[ $user_id ] = trim( stripslashes( $user_fullname ) );
		}
	}

	// Loop through and match xprofile fullname with commenters.
	foreach ( (array) $comments as $i => $comment ) {
		if ( ! empty( $comment->user_id ) ) {
			if ( ! empty( $users[ $comment->user_id ] ) ) {
				$comments[ $i ]->comment_author = $users[ $comment->user_id ];
			}
		}
	}

	return $comments;
}
add_filter( 'comments_array', 'xprofile_filter_comments', 10, 2 );

/**
 * Filter BP_User_Query::populate_extras to override each queries users fullname.
 *
 * @since BuddyPress 1.7.0
 *
 * @param BP_User_Query $user_query   User query to filter.
 * @param string        $user_ids_sql SQL statement to use.
 */
function bp_xprofile_filter_user_query_populate_extras( BP_User_Query $user_query, $user_ids_sql = '' ) {

	if ( ! bp_is_active( 'xprofile' ) ) {
		return;
	}

	$user_id_names = bp_core_get_user_displaynames( $user_query->user_ids );

	// Loop through names and override each user's fullname.
	foreach ( $user_id_names as $user_id => $user_fullname ) {
		if ( isset( $user_query->results[ $user_id ] ) ) {
			$user_query->results[ $user_id ]->fullname = $user_fullname;
		}
	}
}
add_filter( 'bp_user_query_populate_extras', 'bp_xprofile_filter_user_query_populate_extras', 2, 2 );

/**
 * Parse 'xprofile_query' argument passed to BP_User_Query.
 *
 * @since BuddyPress 2.2.0
 *
 * @param BP_User_Query $q User query object.
 */
function bp_xprofile_add_xprofile_query_to_user_query( BP_User_Query $q ) {

	// Bail if no `xprofile_query` clause.
	if ( empty( $q->query_vars['xprofile_query'] ) ) {
		return;
	}

	$xprofile_query = new BP_XProfile_Query( $q->query_vars['xprofile_query'] );
	$sql            = $xprofile_query->get_sql( 'u', $q->uid_name );

	if ( ! empty( $sql['join'] ) ) {
		$q->uid_clauses['select'] .= $sql['join'];
		$q->uid_clauses['where']  .= $sql['where'];
	}
}
add_action( 'bp_pre_user_query', 'bp_xprofile_add_xprofile_query_to_user_query' );

/**
 * Filter meta queries to modify for the xprofile data schema.
 *
 * @since BuddyPress 2.0.0
 *
 * @access private Do not use.
 *
 * @param string $q SQL query.
 * @return string
 */
function bp_xprofile_filter_meta_query( $q ) {
	global $wpdb;

	$raw_q = $q;

	/*
	 * Replace quoted content with __QUOTE__ to avoid false positives.
	 * This regular expression will match nested quotes.
	 */
	$quoted_regex = "/'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'/s";
	preg_match_all( $quoted_regex, $q, $quoted_matches );
	$q = preg_replace( $quoted_regex, '__QUOTE__', $q );

	// Get the first word of the command.
	preg_match( '/^(\S+)/', $q, $first_word_matches );

	if ( empty( $first_word_matches[0] ) ) {
		return $raw_q;
	}

	// Get the field type.
	preg_match( '/xprofile_(group|field|data)_id/', $q, $matches );

	if ( empty( $matches[0] ) || empty( $matches[1] ) ) {
		return $raw_q;
	}

	switch ( $first_word_matches[0] ) {

		/**
		 * SELECT:
		 * - replace 'xprofile_{fieldtype}_id' with 'object_id'
		 * - ensure that 'object_id' is aliased to 'xprofile_{fieldtype}_id',
		 *   because update_meta_cache() needs the column name to parse
		 *   the query results
		 * - append the 'object type' WHERE clause
		 */
		case 'SELECT':
			$q = str_replace(
				array(
					$matches[0],
					'SELECT object_id',
					'WHERE ',
				),
				array(
					'object_id',
					'SELECT object_id AS ' . $matches[0],
					$wpdb->prepare( 'WHERE object_type = %s AND ', $matches[1] ),
				),
				$q
			);
			break;

		/**
		 * UPDATE and DELETE:
		 * - replace 'xprofile_{fieldtype}_id' with 'object_id'
		 * - append the 'object type' WHERE clause
		 */
		case 'UPDATE':
		case 'DELETE':
			$q = str_replace(
				array(
					$matches[0],
					'WHERE ',
				),
				array(
					'object_id',
					$wpdb->prepare( 'WHERE object_type = %s AND ', $matches[1] ),
				),
				$q
			);
			break;

		/**
		 * UPDATE and DELETE:
		 * - replace 'xprofile_{fieldtype}_id' with 'object_id'
		 * - ensure that the object_type field gets filled in
		 */
		case 'INSERT':
			$q = str_replace(
				array(
					'`' . $matches[0] . '`',
					'VALUES (',
				),
				array(
					'`object_type`,`object_id`',
					$wpdb->prepare( 'VALUES (%s,', $matches[1] ),
				),
				$q
			);
			break;
	}

	// Put quoted content back into the string.
	if ( ! empty( $quoted_matches[0] ) ) {
		for ( $i = 0; $i < count( $quoted_matches[0] ); $i++ ) {
			$quote_pos = strpos( $q, '__QUOTE__' );
			$q         = substr_replace( $q, $quoted_matches[0][ $i ], $quote_pos, 9 );
		}
	}

	return $q;
}

/**
 * Conditionally filters 'bp_get_the_profile_field_name' to return alternate name if available.
 * Filter is only applied if:
 *  1. we are on profile > edit screens
 *  2. we are on registration page
 *
 * @since BuddyBoss 1.0.0
 *
 * @global \BP_XProfile_Field_Type $field
 *
 * @param string $field_name
 * @return string
 */
function xprofile_filter_field_edit_name( $field_name ) {
	$is_field_edit_mode = false;

	$current_field = false;

	if ( bp_is_profile_component() && 'edit' == bp_current_action() ) {
		// we are on profile > edit screens, we should display alternate name, if available, instead of main name.
		$is_field_edit_mode = true;

		// we can use global $field variable here
		global $field;
		$current_field = $field;
	}

	if ( ! $is_field_edit_mode && bp_is_register_page() ) {
		// We are on registration page/form. We should display alternate name, if available, instead of main name.
		$is_field_edit_mode = true;

		// we can use global $field variable here
		global $field;
		$current_field = $field;
	}

	// @todo : Should we do it if an admin is editing user profiles in backend ( wp-admin/edit-user.php... ) ?

	if ( $is_field_edit_mode ) {
		$alternate_name = bp_get_the_profile_field_alternate_name( $current_field );

		if ( ! empty( $alternate_name ) ) {
			$field_name = $alternate_name;
		}
	}

	return $field_name;
}

/**
 * Conditionally filters 'bp_core_get_user_displayname' to return user diaplay name from xprofile.
 *
 * @since BuddyBoss 1.2.3
 *
 * @global \BP_XProfile_Field_Type $field
 *
 * @param  string $full_name
 * @param  int    $user_id
 * @return string
 */
function xprofile_filter_get_user_display_name( $full_name, $user_id ) {
	static $cache;
	if ( empty( $user_id ) ) {
		return $full_name;
	}
	$cache_key = 'bb_xprofile_filter_get_user_display_name_' . trim( $user_id );
	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	if ( ! empty( $user_id ) ) {

		$display_name = bp_xprofile_get_member_display_name( $user_id );

		if ( ! empty( $display_name ) ) {
			$full_name = $display_name;
		}

		$list_fields = bp_xprofile_get_hidden_fields_for_user( $user_id );

		if ( ! empty( $list_fields ) ) {
			$last_name_field_id = bp_xprofile_lastname_field_id();

			if ( in_array( $last_name_field_id, $list_fields ) ) {
				$last_name = xprofile_get_field_data( $last_name_field_id, $user_id );
				$full_name = str_replace( ' ' . $last_name, '', $full_name );
			}
		}
		$cache[ $cache_key ] = $full_name;
	}

	return $full_name;
}

/**
 * Validate nickname approved characters and format.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global $wpdb
 *
 * @param $retval
 * @param string $field_name
 * @param string $value
 * @param string $user_id
 * @return $retval
 */
function bp_xprofile_validate_nickname_value( $retval, $field_id, $value, $user_id = null ) {
	global $wpdb;

	if ( $field_id != bp_xprofile_nickname_field_id() ) {
		return $retval;
	}

	if ( $retval ) {
		return $retval;
	}

	$value      = strtolower( $value );
	$field_name = xprofile_get_field( $field_id )->name;

	// Empty nickname.
	if ( '' === trim( $value ) ) {
		return sprintf( __( '%s is required and not allowed to be empty.', 'buddyboss' ), $field_name );
	}

	// only alpha numeric, underscore, dash.
	if ( ! preg_match( '/^([A-Za-z0-9-_\.]+)$/', $value ) ) {
		return sprintf( __( 'Invalid %s. Only "a-z", "0-9", "-", "_" and "." are allowed.', 'buddyboss' ), $field_name );
	}

	// Check user unique identifier exist.
	$check_exists = $wpdb->get_var( // phpcs:ignore
		$wpdb->prepare(
			"SELECT count(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
			'bb_profile_slug',
			$value
		)
	);

	if ( $check_exists > 0 ) {
		// translators: Nickname field.
		return sprintf( __( 'Invalid %s.', 'buddyboss' ), $field_name );
	}

	// must be shorter then 32 characters
	$nickname_length = apply_filters( 'xprofile_nickname_max_length', 32 );
	if ( strlen( $value ) > $nickname_length ) {
		return sprintf( __( '%1$s must be shorter than %2$d characters.', 'buddyboss' ), $field_name, $nickname_length );
	}

	// Minimum of 3 characters.
	if ( strlen( $value ) < 3 ) {
		return sprintf( __( '%s must be at least 3 characters', 'buddyboss' ), $field_name );
	}

	// Register page validation for username.
	if ( ! is_user_logged_in() ) {
		// Check user has same login or not.
		$user = get_user_by( 'login', $value );

		if ( false !== $user ) {
			return sprintf( __( '%s has already been taken.', 'buddyboss' ), $field_name );
		}
	}

	$where = array(
		'meta_key = "nickname"',
		'meta_value = "' . $value . '"',
	);

	if ( $user_id ) {
		$where[] = 'user_id != ' . $user_id;
	}

	$sql = sprintf(
		'SELECT count(*) FROM %s WHERE %s',
		$wpdb->usermeta,
		implode( ' AND ', $where )
	);

	if ( $wpdb->get_var( $sql ) > 0 ) {
		return sprintf( __( '%s has already been taken.', 'buddyboss' ), $field_name );
	}

	return $retval;
}

/**
 * Validate phone number format.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $retval
 * @param $field_id
 * @param $value
 * @param null     $user_id
 *
 * @return string
 */
function bp_xprofile_validate_phone_value( $retval, $field_id, $value, $user_id = null ) {

	if ( 'telephone' !== xprofile_get_field( $field_id )->type ) {
		return $retval;
	}

	if ( $retval ) {
		return $retval;
	}

	if ( false === xprofile_check_is_required_field( $field_id ) && '' === $value ) {
		return $retval;
	}

	$international   = false;
	$selected_format = bp_xprofile_get_meta( $field_id, 'field', 'phone_format', true );
	if (
		empty( $selected_format )
		|| $selected_format == 'international'
	) {
		$international = true;
	}

	$str = trim( $value );
	$str = preg_replace( '/\s+(#|x|ext(ension)?)\.?:?\s*(\d+)/', ' ext \3', $str );

	$us_number = preg_match( '/^(\+\s*)?((0{0,2}1{1,3}[^\d]+)?\(?\s*([2-9][0-9]{2})\s*[^\d]?\s*([2-9][0-9]{2})\s*[^\d]?\s*([\d]{4})){1}(\s*([[:alpha:]#][^\d]*\d.*))?$/', $str, $matches );

	$field_name = xprofile_get_field( $field_id )->name;

	if ( empty( $str ) ) {
		/* SET ERROR: The field must be a valid U.S. phone number (e.g. 888-888-8888) */
		return sprintf( __( '%s is required and not allowed to be empty.', 'buddyboss' ), $field_name );
	}

	if ( $us_number ) {
		return $retval;
	}

	if ( ! $international ) {
		/* SET ERROR: The field must be a valid U.S. phone number (e.g. 888-888-8888) */
		return sprintf( __( 'Enter valid %s', 'buddyboss' ), $field_name );
	}

	$valid_number = preg_match( '/^(\+\s*)?(?=([.,\s()-]*\d){8})([\d(][\d.,\s()-]*)([[:alpha:]#][^\d]*\d.*)?$/', $str, $matches ) && preg_match( '/\d{2}/', $str );

	if ( $valid_number ) {
		return $retval;
	}

	/* SET ERROR: The field must be a valid phone number (e.g. 888-888-8888) */
	return sprintf( __( 'Enter valid %s', 'buddyboss' ), $field_name );
}

/**
 * Change member display_name for current_user.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_xprofile_adjust_current_user_display_name() {
	global $current_user;

	if ( ! $current_user->ID ) {
		return;
	}

	$name                             = empty( $current_user->display_name ) ? empty( $current_user->user_nicename ) ? $current_user->user_login : $current_user->user_nicename : $current_user->display_name;
	$display_name                     = bp_core_get_user_displayname( $current_user->ID );
	$current_user->data->display_name = empty( $display_name ) ? $name : $display_name;
}

/**
 * Change member display_name for user_metadata.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_xprofile_adjust_display_name( $null, $object_id, $meta_key ) {
	if ( $meta_key != 'display_name' ) {
		return $null;
	}

	return bp_core_get_user_displayname( $object_id );
}

/**
 * Change display_name for admin areas.
 *
 * @param array       $email_content Email Content array.
 * @param object|null $user User Object
 *
 * @since BuddyBoss 1.0.0
 * @update BuddyBoss 1.3.3
 *
 * @return array $email_content Password change email data of array
 */
function bp_xprofile_replace_username_to_display_name( $email_content, $user = null ) {
	if ( ! $user || empty( $user ) ) {
		$user = wp_get_current_user()->to_array();
	}

	if ( ! isset( $user['ID'] ) || ! isset( $user['display_name'] ) ) {
		return $email_content;
	}

	$email_content['message'] = str_replace(
		'###USERNAME###',
		bp_core_get_user_displayname( $user['ID'] ),
		$email_content['message']
	);

	return $email_content;
}

/**
 * Validate social networks field values.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global $wpdb
 *
 * @param $retval
 * @param string $field_name
 * @param string $value
 * @param string $user_id
 * @return $retval
 */
function bp_xprofile_validate_social_networks_value( $retval, $field_id, $value, $user_id = null ) {

	$field = xprofile_get_field( $field_id, null, false );

	// Allows storing the 'facebook', 'twitter' and so on as array keys in the data.
	if ( 'socialnetworks' !== $field->type ) {
		return $retval;
	}

	if ( $retval ) {
		return $retval;
	}

	$field_name = xprofile_get_field( $field_id )->name;

	if ( 1 === $field->is_required ) {
		foreach ( $value as $key => $val ) {
			$val = trim( $val );
			if ( empty( $val ) ) {
				return sprintf( __( '%s is required and not allowed to be empty.', 'buddyboss' ), $field_name );
			}
		}
	}

	$providers = bp_xprofile_social_network_provider();
	foreach ( $value as $k => $v ) {
		if ( '' === $v || filter_var( $v, FILTER_VALIDATE_URL ) ) {

		} else {
			$key = bp_social_network_search_key( $k, $providers );
			return sprintf( __( 'Please enter valid %s profile url.', 'buddyboss' ), $providers[ $key ]->name );
		}
	}

	return $retval;
}

/**
 * Search the key for given value from the social networks provider.
 *
 * @param $id
 * @param $array
 *
 * @since BuddyBoss 1.0.0
 *
 * @return int|string|null
 */
function bp_social_network_search_key( $id, $array ) {
	foreach ( $array as $key => $val ) {
		if ( $val->value === $id ) {
			return $key;
		}
	}
	return null;
}

/**
 * Removed Display setting field form profile if is disabled on setting page.
 *
 * @param array $args
 *
 * @return array
 *
 * @since BuddyBoss 1.5.1
 */
function bp_xprofile_exclude_display_name_profile_fields( $args ) {

	// Get the current display settings from BuddyBoss > Settings > Profiles > Display Name Format.
	$current_value = bp_get_option( 'bp-display-name-format' );

	$fields_id = array();

	if ( 'first_name' === $current_value && function_exists( 'bp_hide_last_name' ) && false === bp_hide_last_name() ) {
		$fields_id[] = bp_xprofile_lastname_field_id();
	}

	if ( 'nickname' === $current_value && function_exists( 'bp_hide_nickname_last_name' ) && false === bp_hide_nickname_last_name() ) {
		$fields_id[] = bp_xprofile_lastname_field_id();
	}

	if ( 'nickname' === $current_value && function_exists( 'bp_hide_nickname_first_name' ) && false === bp_hide_nickname_first_name() ) {
		$fields_id[] = bp_xprofile_firstname_field_id();
	}

	if ( ! empty( $fields_id ) ) {
		if ( empty( $args['exclude_fields'] ) ) {
			$args['exclude_fields'] = array();
		}
		$args['exclude_fields'] = array_merge( wp_parse_id_list( $args['exclude_fields'] ), $fields_id );
	}

	return $args;
}

/**
 * Add xprofile notification repair list item.
 *
 * @param array $repair_list Repair list items.
 *
 * @return array Repair list items.
 *
 * @since BuddyBoss 1.7.5
 */
function bb_xprofile_repeater_field_repair( $repair_list ) {
	$repair_list[] = array(
		'bp-xprofile-repeater-field-repair',
		esc_html__( 'Repair BuddyBoss profile repeater field sets', 'buddyboss' ),
		'bb_xprofile_repeater_field_repair_callback',
	);
	return $repair_list;
}

/**
 * This function will work as migration process which will remove duplicate repeater field from database by repair tool.
 * Also remove remove unnecessary data that may have remained after the migration process.
 * This function will be called only once.
 *
 * @uses bb_xprofile_repeater_field_migration
 *
 * @since BuddyBoss 1.7.5
 */
function bb_xprofile_repeater_field_repair_callback() {
	global $wpdb;
	$bp = buddypress();

	$offset = isset( $_POST['offset'] ) ? (int) ( $_POST['offset'] ) : 0;

	$clone_fields_query = "SELECT c.object_id, c.meta_value as clone_number, a.* FROM {$bp->profile->table_name_fields} a LEFT JOIN {$bp->profile->table_name_meta} b ON (a.id = b.object_id)
    LEFT JOIN {$bp->profile->table_name_meta} c ON (a.id = c.object_id)
    WHERE a.parent_id = '0' AND b.meta_key = '_is_repeater_clone' AND b.meta_value = '1' AND c.meta_key = '_clone_number' ORDER BY c.object_id, c.meta_value ASC LIMIT 50 OFFSET $offset";
	$added_fields       = $wpdb->get_results( $clone_fields_query );

	if ( $offset == 0 ) {
		$duplicate_fields = array();
		$updated_fields   = array();
	} else {
		$duplicate_fields = bp_get_option( 'bp_repair_duplicate_fields', array() );
		$updated_fields   = bp_get_option( 'bp_repair_updated_fields', array() );
	}

	if ( ! empty( $added_fields ) ) {
		foreach ( $added_fields as $field ) {
			$clone_id   = (int) $field->id;
			$main_field = bb_xprofile_top_most_template_field_id( (int) $clone_id );

			$metas = $wpdb->get_results( "SELECT * FROM {$bp->profile->table_name_meta} WHERE object_id = {$main_field} AND object_type = 'field'", ARRAY_A );
			if ( ! empty( $metas ) && ! is_wp_error( $metas ) ) {
				$field_member_types = array();
				foreach ( $metas as $meta ) {
					if ( $meta['meta_key'] === 'member_type' ) {
						$field_member_types[] = $meta;
						bp_xprofile_delete_meta( $clone_id, 'field', 'member_type' );
					} else {
						bp_xprofile_update_meta( $clone_id, 'field', $meta['meta_key'], $meta['meta_value'] );
					}
				}
				if ( ! empty( $field_member_types ) ) {
					foreach ( $field_member_types as $meta ) {
						bp_xprofile_add_meta( $clone_id, 'field', $meta['meta_key'], $meta['meta_value'] );
					}
				}
			}

			bp_xprofile_update_meta( $clone_id, 'field', '_cloned_from', $main_field );

			$data = array(
				'group_id'     => $field->group_id,
				'main_field'   => $main_field,
				'field_order'  => $field->field_order,
				'clone_number' => $field->clone_number,
			);

			if ( ! empty( $updated_fields ) && array_search( $data, $updated_fields, true ) ) {
				$duplicate_fields[] = $clone_id;
			} else {
				$updated_fields[ $clone_id ] = $data;
			}

			$offset ++;
		}

		bp_update_option( 'bp_repair_duplicate_fields', $duplicate_fields );
		bp_update_option( 'bp_repair_updated_fields', $updated_fields );

		$records_updated = sprintf( __( '%s field updated successfully.', 'buddyboss' ), bp_core_number_format( $offset ) );

		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => $records_updated,
		);
	} else {
		if ( ! empty( $duplicate_fields ) ) {
			foreach ( $duplicate_fields as $field_id ) {
				xprofile_delete_field( $field_id );
				bp_xprofile_delete_meta( $field_id, 'field' );
			}
		}

		bp_delete_option( 'bp_repair_duplicate_fields' );
		bp_delete_option( 'bp_repair_updated_fields' );

		return array(
			'status'  => 1,
			'message' => __( 'Repairing BuddyBoss profile repeater field sets &hellip; Complete!', 'buddyboss' ),
		);
	}
}

/**
 * Add xprofile repair user nicknames.
 *
 * @param array $repair_list Repair list items.
 *
 * @return array Repair list items.
 *
 * @since BuddyBoss 1.7.9
 */
function bb_xprofile_repair_user_nicknames( $repair_list ) {
	$repair_list[] = array(
		'bb-xprofile-repair-user-nicknames',
		__( 'Repair user nicknames', 'buddyboss' ),
		'bb_xprofile_repair_user_nicknames_callback',
	);
	return $repair_list;
}

/**
 * This function will work as migration process which will update user nicknames.
 *
 * @since BuddyBoss 1.7.9
 * @since BuddyBoss 2.3.50 Added support to skip updating nickname if modified by the user.
 */
function bb_xprofile_repair_user_nicknames_callback() {
	global $wpdb;

	$records_updated = 0;

	$users_query = "SELECT users.ID, users.display_name, users.user_login, users.user_nicename, meta.meta_value, xprofile.value as xprofile_nickname
					FROM `{$wpdb->users}` as users
					LEFT JOIN `{$wpdb->usermeta}` as meta ON users.ID = meta.user_ID AND meta.meta_key = 'nickname'
					LEFT JOIN `{$wpdb->base_prefix}bp_xprofile_data` as xprofile ON users.ID = xprofile.user_id AND xprofile.field_id = %d
					WHERE users.user_nicename != COALESCE(meta.meta_value, users.user_nicename) OR users.user_nicename != COALESCE(xprofile.value, users.user_nicename)";

	$records = $wpdb->get_results( $wpdb->prepare( $users_query, bp_xprofile_nickname_field_id() ) );

	if ( ! empty( $records ) ) {
		foreach ( $records as $record ) {
			$invalid = bp_xprofile_validate_nickname_value( '', bp_xprofile_nickname_field_id(), $record->xprofile_nickname, $record->ID );

			if (
				! empty( $record->xprofile_nickname ) &&
				empty( $invalid ) &&
				$record->xprofile_nickname === $record->meta_value
			) {
				// Skip updating nickname if xprofile field is modified by the user.
				continue;
			}

			$wpdb->update(
				$wpdb->usermeta,
				array( 'meta_value' => $record->user_nicename ),
				array( 'user_id' => $record->ID, 'meta_key' => 'nickname' ),
				array( '%s' ),
				array( '%d', '%s' )
			);

			$wpdb->update(
				"{$wpdb->base_prefix}bp_xprofile_data",
				array( 'value' => $record->user_nicename ),
				array( 'user_id' => $record->ID, 'field_id' => bp_xprofile_nickname_field_id() ),
				array( '%s' ),
				array( '%d', '%d' )
			);

			$records_updated ++;
		}
	}

	return array(
		'status'  => 1,
		'records' => sprintf(
			/* translators: updated records count. */
			__( '%s user nicknames updated successfully.', 'buddyboss' ),
			bp_core_number_format( $records_updated )
		),
		'message' => __( 'Repairing user nicknames &hellip; Complete!', 'buddyboss' ),
	);
}

/**
 * The user_nickname make compatible with BuddyBoss when user created from the backend.
 *
 * @since BuddyBoss 1.8.7
 *
 * @param array   $meta Default meta values and keys for the user.
 * @param WP_User $user User object.
 * @param bool    $update Whether the user is being updated rather than created.
 *
 * @return array
 */
function bb_validate_user_nickname_on_user_register( array $meta, WP_User $user, bool $update ) {

	if ( ! $update ) {
		if ( isset( $meta['nickname'] ) && ! empty( $meta['nickname'] ) ) {
			$meta['nickname'] = sanitize_title( $meta['nickname'] );
		} elseif ( isset( $user->user_nicename ) && ! empty( $user->user_nicename ) ) {
			$meta['nickname'] = sanitize_title( $user->user_nicename );
		} elseif ( isset( $user->user_login ) && ! empty( $user->user_login ) ) {
			$meta['nickname'] = sanitize_title( $user->user_login );
		}
	}

	return $meta;
}

/**
 * Validate user_nickname when user updated from the backend.
 *
 * @since BuddyBoss 1.8.7
 *
 * @param WP_Error $errors WP_Error object (passed by reference).
 * @param bool     $update Whether this is a user update.
 * @param stdClass $user   User object (passed by reference).
 */
function bb_validate_user_nickname_on_user_update( WP_Error $errors, bool $update, stdClass $user ) {

	if ( $update && isset( $user->nickname ) && ! empty( $user->nickname ) ) {
		$invalid = bp_xprofile_validate_nickname_value( '', bp_xprofile_nickname_field_id(), $user->nickname, $user->ID );

		// or use the user_nickname.
		if ( $invalid ) {
			$errors->add( 'nickname', esc_html( $invalid ) );
		}
	}
}

/**
 * Function will check if user confirmed change email address then
 * update profile completion widget based on change email's gravatar.
 *
 * @since BuddyBoss 2.0.9
 *
 * @param int   $user_id       Get current user id.
 * @param array $old_user_data Old user data.
 */
function bb_profile_update_completion_user_progress( $user_id, $old_user_data ) {
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}
	if ( defined( 'IS_PROFILE_PAGE' ) && IS_PROFILE_PAGE && isset( $_GET['newuseremail'] ) && $user_id ) {
		$new_email = get_user_meta( $user_id, '_new_email', true );
		if ( $new_email && hash_equals( $new_email['hash'], $_GET['newuseremail'] ) ) {
			bp_core_xprofile_update_profile_completion_user_progress();
		}
	}
}

/**
 * Set social network param to profile query.
 *
 * @since BuddyBoss 2.1.0
 *
 * @param array $args Arguments.
 *
 * @return array
 */
function bb_xprofile_set_social_network_param( $args = array() ) {

	if ( bp_is_user_profile() ) {
		$is_enabled_social_networks = bb_enabled_profile_header_layout_element( 'social-networks' ) && function_exists( 'bb_enabled_member_social_networks' ) && bb_enabled_member_social_networks();

		if ( ! $is_enabled_social_networks ) {
			$args['fetch_social_network_fields'] = true;
		}
	}

	return $args;
}

/**
 * Function trigger when fieldset is added or deleted or field deleted.
 *
 * @since BuddyBoss 2.1.6
 */
function bb_core_xprofile_clear_group_cache() {
	BP_XProfile_Group::$bp_xprofile_group_ids = array();
}
