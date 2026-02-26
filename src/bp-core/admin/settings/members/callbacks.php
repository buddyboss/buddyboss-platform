<?php
/**
 * BuddyBoss Admin Settings - Members Callbacks.
 *
 * Sanitize callback functions for Members feature settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize display name format setting.
 *
 * Accepts only allowed display name format values.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized display name format value.
 */
function bb_members_sanitize_display_name_format( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'first_name', 'first_last_name', 'nickname' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'first_name';
	}

	return $value;
}

/**
 * Sanitize profile slug format setting.
 *
 * Accepts only 'username' or 'unique_id'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized slug format value.
 */
function bb_members_sanitize_slug_format( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'username', 'unique_identifier' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'username';
	}

	return $value;
}

/**
 * Sanitize profile avatar type setting.
 *
 * Accepts only 'BuddyBoss' or 'WordPress'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized avatar type value.
 */
function bb_members_sanitize_avatar_type( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'BuddyBoss', 'WordPress' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'BuddyBoss';
	}

	return $value;
}

/**
 * Sanitize default profile avatar type setting.
 *
 * Accepts only allowed default avatar type values.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized default avatar type value.
 */
function bb_members_sanitize_default_avatar_type( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'buddyboss', 'display-name', 'custom' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'buddyboss';
	}

	return $value;
}

/**
 * Sanitize default profile cover type setting.
 *
 * Accepts only allowed cover type values.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized default cover type value.
 */
function bb_members_sanitize_default_cover_type( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'buddyboss', 'none', 'custom' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'buddyboss';
	}

	return $value;
}

/**
 * Sanitize member layout format setting.
 *
 * Accepts only allowed layout format values.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized layout format value.
 */
function bb_members_sanitize_layout_format( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'list_grid', 'grid', 'list' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'list_grid';
	}

	return $value;
}

/**
 * Sanitize member layout default format setting.
 *
 * Accepts only 'grid' or 'list'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized default format value.
 */
function bb_members_sanitize_default_format( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'grid', 'list' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'grid';
	}

	return $value;
}

/**
 * Sanitize member toggle list elements.
 *
 * Expects an associative array where keys are element slugs and values are 0/1.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array Sanitized array of element_slug => 0|1.
 */
function bb_members_sanitize_toggle_list( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$sanitized = array();
	foreach ( $value as $key => $val ) {
		$sanitized[ sanitize_key( $key ) ] = absint( $val ) ? 1 : 0;
	}

	return $sanitized;
}

/**
 * Sanitize default profile type on registration setting.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int Sanitized profile type ID.
 */
function bb_members_sanitize_default_on_registration( $value ) {
	return absint( $value );
}

/**
 * Sanitize connection messaging setting.
 *
 * Simple boolean to int conversion matching legacy
 * bp_admin_sanitize_callback_force_friendship_to_message().
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int 1 or 0.
 */
function bb_members_sanitize_force_friendship_to_message( $value ) {
	return $value ? 1 : 0;
}

/**
 * Sanitize profile header style setting.
 *
 * Accepts only 'left' or 'centered'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized header style value.
 */
function bb_members_sanitize_header_style( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'left', 'centered' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'left';
	}

	return $value;
}

/**
 * Sanitize profile cover image width setting.
 *
 * Accepts only 'default' or 'full'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized cover width value.
 */
function bb_members_sanitize_cover_width( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'default', 'full' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'default';
	}

	return $value;
}

/**
 * Sanitize profile cover image height setting.
 *
 * Accepts only 'small' or 'large'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized cover height value.
 */
function bb_members_sanitize_cover_height( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'small', 'large' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'small';
	}

	return $value;
}

// =========================================================================
// POST-SAVE VALIDATION HOOKS
//
// Priority ordering (must run in this order):
//   3  - bb_members_handle_avatar_type_wordpress     (force-disable gravatar when WordPress avatar)
//   5  - bb_members_validate_image_settings_after_save (revert invalid avatar/cover selections)
//   10 - bb_members_handle_display_name_format_change  (update xprofile field meta)
//   10 - bb_members_handle_slug_format_change          (flush caches on slug change)
//   10 - bb_members_sync_reverted_image_values (filter, syncs reverted values to response)
// =========================================================================

/**
 * Capture members settings state before save for post-save comparison.
 *
 * Captures the current value of settings that need before/after comparison
 * (e.g., avatar type, cover type, slug format) before the save loop updates them.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID being saved.
 */
function bb_members_capture_pre_save_state( $feature_id ) {
	if ( 'members' !== $feature_id ) {
		return;
	}

	// Only capture during save, not during get (read).
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_verify_request() in the calling AJAX handler.
	if ( ! isset( $_POST['action'] ) || 'bb_admin_save_feature_settings' !== sanitize_key( wp_unslash( $_POST['action'] ) ) ) {
		return;
	}

	global $bb_members_pre_save_state;
	$bb_members_pre_save_state = array(
		'profile_avatar_type'         => bb_get_profile_avatar_type(),
		'disable_avatar_uploads'      => bp_disable_avatar_uploads(),
		'default_profile_avatar_type' => bb_get_default_profile_avatar_type(),
		'enable_profile_gravatar'     => bp_enable_profile_gravatar(),
		'default_profile_cover_type'  => bb_get_default_profile_cover_type(),
		'profile_slug_format'         => bb_get_profile_slug_format(),
		'profile_search_disabled'     => bp_disable_advanced_profile_search(),
	);
}

add_action( 'bb_admin_settings_before_get_feature', 'bb_members_capture_pre_save_state', 10, 1 );

/**
 * Handle display name format change after save.
 *
 * When the display name format changes, update xprofile field meta
 * for visibility and required status based on the selected format.
 * Replicates legacy BP_Admin_Setting_Xprofile::settings_save() logic.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bb_members_handle_display_name_format_change( $feature_id, $settings, $saved ) {
	if ( 'members' !== $feature_id ) {
		return;
	}

	if ( ! array_key_exists( 'bp-display-name-format', $settings ) ) {
		return;
	}

	$display_name_format = bp_get_option( 'bp-display-name-format', 'first_name' );

	if ( 'first_last_name' === $display_name_format || 'first_name' === $display_name_format ) {
		$firstname_field_id = bp_xprofile_firstname_field_id();
		bp_xprofile_update_field_meta( $firstname_field_id, 'default_visibility', 'public' );
		bp_xprofile_update_field_meta( $firstname_field_id, 'allow_custom_visibility', 'disabled' );

		// Make the first name field required if not already.
		$field              = xprofile_get_field( $firstname_field_id );
		$field->is_required = true;
		$field->save();

		if ( 'first_last_name' === $display_name_format ) {
			$lastname_field_id = bp_xprofile_lastname_field_id();
			bp_xprofile_update_field_meta( $lastname_field_id, 'default_visibility', 'public' );
		}
	} elseif ( 'nickname' === $display_name_format ) {
		$nickname_field_id = bp_xprofile_nickname_field_id();
		bp_xprofile_update_field_meta( $nickname_field_id, 'default_visibility', 'public' );
		bp_xprofile_update_field_meta( $nickname_field_id, 'allow_custom_visibility', 'disabled' );
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_members_handle_display_name_format_change', 10, 3 );

/**
 * Handle avatar type WordPress selection after save.
 *
 * When avatar type is set to WordPress, force-disable Gravatar setting.
 * Replicates legacy BP_Admin_Setting_Xprofile::settings_save() logic.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bb_members_handle_avatar_type_wordpress( $feature_id, $settings, $saved ) {
	if ( 'members' !== $feature_id ) {
		return;
	}

	if ( ! array_key_exists( 'bp-profile-avatar-type', $settings ) ) {
		return;
	}

	$avatar_type = bb_get_profile_avatar_type();

	if ( 'WordPress' === $avatar_type ) {
		bp_update_option( 'bp-enable-profile-gravatar', '' );
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_members_handle_avatar_type_wordpress', 3, 3 );

/**
 * Validate profile avatar and cover image type selections after save.
 *
 * In the legacy admin, the Upload Custom Avatar block is always in the DOM; when the user
 * selects Custom they see it immediately and upload, then Save (hidden field has the URL).
 * In Settings 2.0 the upload is a separate step: user selects Custom → must Save first →
 * then the Upload section stays visible and they can upload. So we only revert when they
 * *had* Custom with an image and then removed the image. We do not revert when they just
 * selected Custom (no image yet), so the selection persists and they can upload after Save.
 *
 * - If avatar type is 'custom' and no custom avatar URL: revert only when previous type was
 *   also 'custom' (they removed the image). Otherwise keep 'custom' so Upload section stays open.
 * - If avatar type is 'display-name' but no image editor available, revert to 'buddyboss'.
 * - Same for cover: revert only when prev was 'custom' and image is now empty.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bb_members_validate_image_settings_after_save( $feature_id, $settings, $saved ) {
	if ( 'members' !== $feature_id ) {
		return;
	}

	$has_avatar = array_key_exists( 'bp-default-profile-avatar-type', $settings );
	$has_cover  = array_key_exists( 'bp-default-profile-cover-type', $settings );

	if ( ! $has_avatar && ! $has_cover ) {
		return;
	}

	global $bb_members_pre_save_state;

	// --- Avatar type validation ---
	if ( $has_avatar ) {
		$avatar_type = bb_get_default_profile_avatar_type();

		// Revert only when they had Custom and then removed the image. Do not revert when they
		// just selected Custom (so Save keeps Custom and Upload Custom Avatar section stays visible).
		if ( 'custom' === $avatar_type ) {
			$custom_avatar = bb_get_default_custom_upload_profile_avatar();
			$prev_type     = ! empty( $bb_members_pre_save_state['default_profile_avatar_type'] ) ? $bb_members_pre_save_state['default_profile_avatar_type'] : 'buddyboss';

			if ( empty( $custom_avatar ) && 'custom' === $prev_type ) {
				bp_update_option( 'bp-default-profile-avatar-type', 'buddyboss' );
			}
		}

		// If 'display-name' but no image editor available, revert.
		if ( 'display-name' === $avatar_type && empty( _wp_image_editor_choose() ) ) {
			bp_update_option( 'bp-default-profile-avatar-type', 'buddyboss' );
		}
	}

	// --- Cover type validation ---
	if ( $has_cover ) {
		$cover_type = bb_get_default_profile_cover_type();

		// Revert only when they had Custom and then removed the image.
		if ( 'custom' === $cover_type ) {
			$custom_cover = bb_get_default_custom_upload_profile_cover();
			$prev_type    = ! empty( $bb_members_pre_save_state['default_profile_cover_type'] ) ? $bb_members_pre_save_state['default_profile_cover_type'] : 'buddyboss';

			if ( empty( $custom_cover ) && 'custom' === $prev_type ) {
				bp_update_option( 'bp-default-profile-cover-type', 'buddyboss' );
			}
		}
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_members_validate_image_settings_after_save', 5, 3 );

/**
 * Sync reverted image setting values into the save response.
 *
 * After bb_members_validate_image_settings_after_save() may revert avatar/cover
 * type values, this filter updates only the affected keys in the response so
 * React stays in sync.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $response_data Response data being sent.
 * @param string $feature_id    Feature ID.
 * @param array  $settings      Full submitted settings.
 * @param array  $saved         Keys and values saved by core.
 *
 * @return array Modified response data.
 */
function bb_members_sync_reverted_image_values( $response_data, $feature_id, $settings, $saved ) {
	if ( 'members' !== $feature_id ) {
		return $response_data;
	}

	// Only re-read the specific keys that the validation callback may have reverted.
	$revertable_keys = array(
		'bp-default-profile-avatar-type',
		'bp-default-profile-cover-type',
		'bp-profile-avatar-type',
		'bp-disable-avatar-uploads',
		'bp-enable-profile-gravatar',
	);

	foreach ( $revertable_keys as $key ) {
		if ( isset( $saved[ $key ] ) ) {
			$actual = bp_get_option( $key, $saved[ $key ] );
			if ( $actual !== $saved[ $key ] ) {
				$response_data['saved'][ $key ] = $actual;
			}
		}
	}

	return $response_data;
}

add_filter( 'bb_admin_save_feature_settings_response', 'bb_members_sync_reverted_image_values', 10, 4 );

/**
 * Handle slug format change after save.
 *
 * When the profile slug format changes, flush all caches including
 * Performance API cache. Replicates legacy settings_save() logic.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bb_members_handle_slug_format_change( $feature_id, $settings, $saved ) {
	if ( 'members' !== $feature_id ) {
		return;
	}

	if ( ! array_key_exists( 'bb_profile_slug_format', $settings ) ) {
		return;
	}

	global $bb_members_pre_save_state;
	$slug_before = ! empty( $bb_members_pre_save_state['profile_slug_format'] ) ? $bb_members_pre_save_state['profile_slug_format'] : '';
	$slug_after  = bb_get_profile_slug_format();

	if ( $slug_before !== $slug_after ) {
		wp_cache_flush();

		// Purge all the cache for API.
		if ( class_exists( 'BuddyBoss\Performance\Cache' ) ) {
			BuddyBoss\Performance\Cache::instance()->purge_all();
		}
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_members_handle_slug_format_change', 10, 3 );

// =========================================================================
// AJAX-TIME FIELD ENRICHMENT
// =========================================================================

/**
 * Inject dynamic cover image dimensions into the upload help text at AJAX time.
 *
 * At field registration time (bp_loaded priority 5), theme compat features are
 * not yet available (they register at priority 12). This filter runs when the
 * admin fetches settings via AJAX, by which time theme compat is fully loaded.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $field_data Formatted field data.
 * @param array  $field      Original field registration data.
 * @param string $feature_id Feature ID.
 *
 * @return array Modified field data.
 */
function bb_members_enrich_cover_upload_help_text( $field_data, $field, $feature_id ) {
	if ( 'members' !== $feature_id || 'bp-default-profile-cover-type' !== ( isset( $field_data['name'] ) ? $field_data['name'] : '' ) ) {
		return $field_data;
	}

	if ( ! empty( $field_data['upload_config'] ) && empty( $field_data['upload_config']['help_text'] ) ) {
		$cover_dimensions                         = bb_attachments_get_default_custom_cover_image_dimensions( 'members' );
		$field_data['upload_config']['help_text'] = sprintf(
			/* translators: 1: width in pixels, 2: height in pixels. */
			__( 'Upload a default cover image (JPG or PNG, recommended size: %1$s×%2$s px).', 'buddyboss' ),
			(int) $cover_dimensions['width'],
			(int) $cover_dimensions['height']
		);
	}

	return $field_data;
}

add_filter( 'bb_admin_settings_format_field_data', 'bb_members_enrich_cover_upload_help_text', 10, 3 );

/**
 * Allow member profile url_getter functions in the image upload allowlist.
 *
 * The AJAX handler restricts which functions can be called as url_getters for
 * security. This filter adds the two member-profile-specific getters so that
 * saved custom avatar and cover image URLs are resolved and returned to React.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $allowed_url_getters List of allowed url_getter function names.
 *
 * @return array Extended list including member profile getter functions.
 */
function bb_members_allow_image_url_getters( $allowed_url_getters ) {
	$allowed_url_getters[] = 'bb_get_default_custom_upload_profile_avatar';
	$allowed_url_getters[] = 'bb_get_default_custom_upload_profile_cover';

	return $allowed_url_getters;
}

add_filter( 'bb_admin_settings_allowed_url_getters', 'bb_members_allow_image_url_getters' );

/**
 * Inject dynamic avatar dimensions into the upload help text at AJAX time.
 *
 * `bp_core_avatar_full_width`/`bp_core_avatar_full_height` return 0 at registration time (bp_loaded
 * priority 5) because avatar setup hasn't run yet. This filter runs during
 * the AJAX request when dimensions are fully available.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $field_data Formatted field data.
 * @param array  $field      Original field registration data.
 * @param string $feature_id Feature ID.
 *
 * @return array Modified field data.
 */
function bb_members_enrich_avatar_upload_help_text( $field_data, $field, $feature_id ) {
	if ( 'members' !== $feature_id || 'bp-default-profile-avatar-type' !== ( isset( $field_data['name'] ) ? $field_data['name'] : '' ) ) {
		return $field_data;
	}

	if ( ! empty( $field_data['upload_config'] ) && empty( $field_data['upload_config']['help_text'] ) ) {
		$field_data['upload_config']['help_text'] = sprintf(
			/* translators: 1: width in pixels, 2: height in pixels. */
			__( 'Upload a default avatar image (JPG or PNG, recommended size: %1$s×%2$s px).', 'buddyboss' ),
			absint( bp_core_avatar_full_width() ),
			absint( bp_core_avatar_full_height() )
		);
	}

	return $field_data;
}

add_filter( 'bb_admin_settings_format_field_data', 'bb_members_enrich_avatar_upload_help_text', 10, 3 );

/**
 * Inject profile header element options at AJAX time.
 *
 * bb_get_profile_header_elements() queries bp_xprofile_fields which is not
 * available at registration time (bp_loaded priority 4). This filter runs
 * during the AJAX request when xprofile is fully initialised.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $field_data Formatted field data.
 * @param array  $field      Original field registration data.
 * @param string $feature_id Feature ID.
 *
 * @return array Modified field data.
 */
function bb_members_enrich_header_elements_options( $field_data, $field, $feature_id ) {
	if ( 'members' !== $feature_id || 'bb-profile-headers-layout-elements' !== ( isset( $field_data['name'] ) ? $field_data['name'] : '' ) ) {
		return $field_data;
	}

	if ( ! empty( $field_data['options'] ) || ! function_exists( 'bb_get_profile_header_elements' ) ) {
		return $field_data;
	}

	$element_options = array();
	foreach ( bb_get_profile_header_elements() as $element ) {
		$option = array(
			'label' => $element['element_label'],
			'value' => $element['element_name'],
		);

		// Disable elements that depend on inactive features.
		if ( ! empty( $element['element_class'] ) && false !== strpos( $element['element_class'], 'bp-hide' ) ) {
			$option['disabled'] = true;
		}

		$element_options[] = $option;
	}

	$field_data['options'] = $element_options;

	return $field_data;
}

add_filter( 'bb_admin_settings_format_field_data', 'bb_members_enrich_header_elements_options', 10, 3 );
