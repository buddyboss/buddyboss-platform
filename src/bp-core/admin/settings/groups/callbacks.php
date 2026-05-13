<?php
/**
 * BuddyBoss Admin Settings - Groups Callbacks.
 *
 * Sanitize callback functions for Groups feature settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize group layout format setting.
 *
 * Accepts only allowed layout format values.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized layout format value.
 */
function bb_groups_sanitize_layout_format( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'list_grid', 'grid', 'list' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'grid';
	}

	return $value;
}

/**
 * Sanitize group layout default format setting.
 *
 * Accepts only 'grid' or 'list'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized default format value.
 */
function bb_groups_sanitize_default_format( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'grid', 'list' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'grid';
	}

	return $value;
}

/**
 * Sanitize group avatar type setting.
 *
 * Accepts only allowed avatar type values.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized avatar type value.
 */
function bb_groups_sanitize_avatar_type( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'buddyboss', 'group-name', 'custom' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'buddyboss';
	}

	return $value;
}

/**
 * Sanitize group cover type setting.
 *
 * Accepts only allowed cover type values.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized cover type value.
 */
function bb_groups_sanitize_cover_type( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'buddyboss', 'none', 'custom' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'buddyboss';
	}

	return $value;
}

/**
 * Sanitize group header style setting.
 *
 * Accepts only 'left' or 'centered'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized header style value.
 */
function bb_groups_sanitize_header_style( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'left', 'centered' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'left';
	}

	return $value;
}

/**
 * Sanitize group grid style setting.
 *
 * Accepts only 'left' or 'centered'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized grid style value.
 */
function bb_groups_sanitize_grid_style( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'left', 'centered' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'left';
	}

	return $value;
}

/**
 * Sanitize group toggle list elements (headers, directory elements).
 *
 * Expects an associative array where keys are element slugs and values are 0/1.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array Sanitized array of element_slug => 0|1.
 */
function bb_groups_sanitize_toggle_list( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	// No entry cap — third-party plugins or future panels may add more keys.
	// Per-entry sanitization (sanitize_key + absint) is sufficient protection.
	$sanitized = array();
	foreach ( $value as $key => $val ) {
		$sanitized[ sanitize_key( $key ) ] = absint( $val ) ? 1 : 0;
	}

	return $sanitized;
}

/**
 * Sanitize group cover image width setting.
 *
 * Accepts only 'default' or 'full'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized cover width value.
 */
function bb_groups_sanitize_cover_width( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'default', 'full' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'default';
	}

	return $value;
}

/**
 * Sanitize group cover image height setting.
 *
 * Accepts only 'small' or 'large'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized cover height value.
 */
function bb_groups_sanitize_cover_height( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'small', 'large' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'small';
	}

	return $value;
}

/**
 * Sanitize the group nav order checkbox_list value.
 *
 * Expects an associative array { slug: 0|1, ... }.
 * Sanitizes keys and normalizes values to 0 or 1.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The submitted value.
 *
 * @return array Sanitized array of slug => 0|1.
 */
function bb_sanitize_group_nav_order( $value ) {
	if ( is_string( $value ) ) {
		$value = json_decode( $value, true );
	}

	if ( ! is_array( $value ) ) {
		return array();
	}

	$sanitized = array();
	foreach ( $value as $key => $val ) {
		$sanitized[ sanitize_key( $key ) ] = absint( $val ) ? 1 : 0;
	}

	return $sanitized;
}

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
function bb_groups_enrich_cover_upload_help_text( $field_data, $field, $feature_id ) {
	if ( 'groups' !== $feature_id || 'bp-default-group-cover-type' !== ( $field_data['name'] ?? '' ) ) {
		return $field_data;
	}

	if ( ! empty( $field_data['upload_config'] ) ) {
		// Cover dimensions drive both the help-text recommendation and the
		// React `<CoverCropModal>` aspect ratio. Read AT FIELD-FORMAT TIME so
		// theme compat (loaded later than registration) is available.
		$cover_dimensions = bb_attachments_get_default_custom_cover_image_dimensions( 'groups' );

		if ( empty( $field_data['upload_config']['help_text'] ) ) {
			$field_data['upload_config']['help_text'] = sprintf(
				/* translators: 1: width in pixels, 2: height in pixels. */
				__( 'Upload a default cover image (JPG or PNG, recommended size: %1$spx × %2$spx).', 'buddyboss' ),
				(int) $cover_dimensions['width'],
				(int) $cover_dimensions['height']
			);
		}

		// Inject dimensions for the React CoverCropModal aspect ratio. Same
		// fallback contract as the members panel — modal defaults to 1950×450
		// when this key is missing.
		if ( empty( $field_data['upload_config']['dimensions'] ) && is_array( $cover_dimensions ) ) {
			$field_data['upload_config']['dimensions'] = array(
				'width'  => isset( $cover_dimensions['width'] ) ? (int) $cover_dimensions['width'] : 0,
				'height' => isset( $cover_dimensions['height'] ) ? (int) $cover_dimensions['height'] : 0,
			);
		}
	}

	return $field_data;
}

add_filter( 'bb_admin_settings_format_field_data', 'bb_groups_enrich_cover_upload_help_text', 10, 3 );

/**
 * Inject dynamic avatar dimensions into the upload help text at AJAX time.
 *
 * `bp_core_avatar_full_width`/`bp_core_avatar_full_height` return 0 at registration time
 * (bp_loaded priority 5) because avatar setup hasn't run yet. This filter runs during
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
function bb_groups_enrich_avatar_upload_help_text( $field_data, $field, $feature_id ) {
	if ( 'groups' !== $feature_id || 'bp-default-group-avatar-type' !== ( isset( $field_data['name'] ) ? $field_data['name'] : '' ) ) {
		return $field_data;
	}

	if ( ! empty( $field_data['upload_config'] ) && empty( $field_data['upload_config']['help_text'] ) ) {
		$field_data['upload_config']['help_text'] = sprintf(
			/* translators: 1: width in pixels, 2: height in pixels. */
			__( 'Upload a default avatar image (JPG or PNG, recommended size: %1$spx × %2$spx).', 'buddyboss' ),
			absint( bp_core_avatar_full_width() ),
			absint( bp_core_avatar_full_height() )
		);
	}

	return $field_data;
}

add_filter( 'bb_admin_settings_format_field_data', 'bb_groups_enrich_avatar_upload_help_text', 10, 3 );

// =========================================================================
// POST-SAVE VALIDATION HOOKS
// =========================================================================

/**
 * Validate group avatar and cover image type selections after save.
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
 * - If avatar type is 'group-name' but no image editor available, revert to 'buddyboss'.
 * - Same for cover: revert only when prev was 'custom' and image is now empty.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bb_groups_validate_image_settings_after_save( $feature_id, $settings, $saved ) {
	if ( 'groups' !== $feature_id ) {
		return;
	}

	// Only validate if avatar type or cover type was submitted.
	$has_avatar = array_key_exists( 'bp-default-group-avatar-type', $settings );
	$has_cover  = array_key_exists( 'bp-default-group-cover-type', $settings );

	if ( ! $has_avatar && ! $has_cover ) {
		return;
	}

	$pre_save_state = bb_groups_get_pre_save_state();

	// --- Avatar type validation ---
	if ( $has_avatar ) {
		$avatar_type = bb_get_default_group_avatar_type();

		// Revert only when they had Custom and then removed the image. Do not revert when they
		// just selected Custom (so Save keeps Custom and Upload Custom Avatar section stays visible).
		if ( 'custom' === $avatar_type ) {
			$custom_avatar = function_exists( 'bb_get_default_custom_upload_group_avatar' )
				? bb_get_default_custom_upload_group_avatar()
				: '';
			$prev_type     = ! empty( $pre_save_state['default_group_avatar_type'] ) ? $pre_save_state['default_group_avatar_type'] : 'buddyboss';

			if ( empty( $custom_avatar ) && 'custom' === $prev_type ) {
				bp_update_option( 'bp-default-group-avatar-type', 'buddyboss' );
			}
		}

		// If 'group-name' but no image editor available, revert.
		if ( 'group-name' === $avatar_type && empty( _wp_image_editor_choose() ) ) {
			bp_update_option( 'bp-default-group-avatar-type', 'buddyboss' );
		}
	}

	// --- Cover type validation ---
	if ( $has_cover ) {
		$cover_type = bb_get_default_group_cover_type();

		// Revert only when they had Custom and then removed the image.
		if ( 'custom' === $cover_type ) {
			$custom_cover = function_exists( 'bb_get_default_custom_upload_group_cover' )
				? bb_get_default_custom_upload_group_cover()
				: '';
			$prev_type    = ! empty( $pre_save_state['default_group_cover_type'] ) ? $pre_save_state['default_group_cover_type'] : 'buddyboss';

			if ( empty( $custom_cover ) && 'custom' === $prev_type ) {
				bp_update_option( 'bp-default-group-cover-type', 'buddyboss' );
			}
		}
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_groups_validate_image_settings_after_save', 5, 3 );

/**
 * Sync reverted image setting values into the save response.
 *
 * After bb_groups_validate_image_settings_after_save() may revert avatar/cover
 * type values (e.g., 'custom' -> 'buddyboss' when no image exists), this filter
 * updates only the affected keys in the response so React stays in sync.
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
function bb_groups_sync_reverted_image_values( $response_data, $feature_id, $settings, $saved ) {
	if ( 'groups' !== $feature_id ) {
		return $response_data;
	}

	// Only re-read the specific keys that the validation callback may have reverted.
	$revertable_keys = array( 'bp-default-group-avatar-type', 'bp-default-group-cover-type' );

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

add_filter( 'bb_admin_save_feature_settings_response', 'bb_groups_sync_reverted_image_values', 10, 4 );

/**
 * Get or set the pre-save state for groups settings.
 *
 * Uses a static variable to avoid pollutable global state.
 * Call with no arguments to read; call with an array to set.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array|null $state Optional. State array to store. Pass null (default) to read.
 *
 * @return array The stored pre-save state, or empty array if not yet captured.
 */
function bb_groups_get_pre_save_state( $state = null ) {
	static $stored = null;

	if ( null !== $state ) {
		$stored = $state;
	}

	return is_array( $stored ) ? $stored : array();
}

/**
 * Capture group settings state before save for post-save comparison.
 *
 * Captures the current value of settings that need before/after comparison
 * (e.g., restrict invites) before the save loop updates them.
 *
 * Hooked to `bb_admin_settings_before_save_feature` which fires only during
 * save requests, so no $_POST side-channel check is needed.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID being saved.
 * @param array  $settings   Full submitted settings.
 */
function bb_groups_capture_pre_save_state( $feature_id, $settings ) {
	if ( 'groups' !== $feature_id ) {
		return;
	}

	// Store the pre-save state for restrict invites and image types.
	bb_groups_get_pre_save_state(
		array(
			'restrict_invites'          => bp_enable_group_restrict_invites(),
			'default_group_avatar_type' => bb_get_default_group_avatar_type(),
			'default_group_cover_type'  => bb_get_default_group_cover_type(),
		)
	);
}

add_action( 'bb_admin_settings_before_save_feature', 'bb_groups_capture_pre_save_state', 10, 2 );

/**
 * Trigger subgroup member migration when restrict invites is enabled.
 *
 * Replicates legacy BP_Admin_Setting_Groups::settings_save() logic:
 * When group hierarchies are enabled AND restrict invites changes from
 * disabled to enabled, migrate subgroup members who are not in the parent group.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bb_groups_maybe_migrate_subgroup_members( $feature_id, $settings, $saved ) {
	if ( 'groups' !== $feature_id ) {
		return;
	}

	// Only act if restrict invites was submitted.
	if ( ! array_key_exists( 'bp-enable-group-restrict-invites', $settings ) ) {
		return;
	}

	// Get pre-save state captured by bb_groups_capture_pre_save_state().
	$pre_save_state          = bb_groups_get_pre_save_state();
	$restrict_invites_before = ! empty( $pre_save_state['restrict_invites'] ) ? $pre_save_state['restrict_invites'] : false;

	// Check: hierarchies ON, restrict invites was OFF, now ON.
	if (
		true === bp_enable_group_hierarchies() &&
		empty( $restrict_invites_before ) &&
		true === (bool) bp_enable_group_restrict_invites()
	) {
		if ( function_exists( 'bb_groups_migrate_subgroup_member' ) ) {
			bb_groups_migrate_subgroup_member();
		}
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_groups_maybe_migrate_subgroup_members', 10, 3 );
